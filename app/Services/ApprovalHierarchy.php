<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmpPosition;
use App\Models\Officer;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for "who may approve whose travel order".
 *
 *   President (4) > VP (3) > Division Head (2) > Unit Head (1) > Regular employee (0)
 *
 * Heads (Unit Head, Division Head, VP) are approved by THEIR OWN chain of command, taken from
 * the office / division / unit of the position the request is filed under:
 *
 *   Unit Head     -> the Division Head of their division  +  the VP of their office
 *   Division Head -> the VP of their office               +  the President
 *   VP            -> the President
 *
 * Where an office has no VP (e.g. the Office of the University President) the President takes
 * the VP's place. Example: an IT Head in CEn (office OVPAA) is approved by the Dean of CEn and
 * the VP for Academic Affairs; a Unit Head in PPSDS (office OVPAD) by the Director of PPSDS and
 * the VP for Administration.
 *
 * Regular employees keep the previous behaviour: they may pick any officer above them.
 *
 * The travel order modals (resources/views/travel-orders/index.blade.php) only OFFER these
 * approvers; validateApprovers() is what enforces the rule on the server.
 */
class ApprovalHierarchy
{
    public const REGULAR = 0;
    public const UNIT_HEAD = 1;
    public const DIVISION_HEAD = 2;
    public const VP = 3;
    public const PRESIDENT = 4;

    /**
     * Human readable name of a rank.
     */
    public static function label(int $rank): string
    {
        return match ($rank) {
            self::PRESIDENT => 'President',
            self::VP => 'VP',
            self::DIVISION_HEAD => 'Division Head',
            self::UNIT_HEAD => 'Unit Head',
            default => 'Employee',
        };
    }

    /**
     * Rank implied by the role flags of a single position row (emp_positions).
     */
    public static function positionRank(?EmpPosition $position): int
    {
        if (!$position) {
            return self::REGULAR;
        }

        if ($position->is_president) {
            return self::PRESIDENT;
        }
        if ($position->is_vp) {
            return self::VP;
        }
        if ($position->is_division_head) {
            return self::DIVISION_HEAD;
        }
        if ($position->is_unit_head) {
            return self::UNIT_HEAD;
        }

        return self::REGULAR;
    }

    /**
     * Rank of a person. The officers table is authoritative (that is what the admin
     * "Leaders" page writes); the emp_positions role flags are used as a fallback.
     *
     * @param Employee|int|string|null $employee An Employee model or an employee id
     */
    public static function employeeRank(Employee|int|string|null $employee): int
    {
        if ($employee === null || $employee === '') {
            return self::REGULAR;
        }

        if (!$employee instanceof Employee) {
            $employee = Employee::with(['positions', 'officer'])->find((int) $employee);
        }

        if (!$employee) {
            return self::REGULAR;
        }

        $employee->loadMissing(['positions', 'officer']);

        $officer = $employee->officer;
        if ($officer) {
            if ($officer->president) {
                return self::PRESIDENT;
            }
            if ($officer->vp) {
                return self::VP;
            }
            if ($officer->division_head) {
                return self::DIVISION_HEAD;
            }
            if ($officer->unit_head) {
                return self::UNIT_HEAD;
            }
        }

        $rank = self::REGULAR;
        foreach ($employee->positions as $position) {
            $rank = max($rank, self::positionRank($position));
        }

        return $rank;
    }

    /**
     * Rank of the person who is filing the request. The position they file under can
     * only raise it (role-per-position), never lower it.
     */
    public static function requesterRank(Employee $employee, ?EmpPosition $position = null): int
    {
        return max(self::employeeRank($employee), self::positionRank($position));
    }

    /**
     * Where a position sits in the organisation: office / division / unit ids.
     * Gaps are filled from lib_units and lib_divisions (a unit knows its division and office,
     * a division knows its office), so a position with only a unit still resolves to an office.
     *
     * @return array{office: int|null, division: int|null, unit: int|null}
     */
    public static function placement(?EmpPosition $position): array
    {
        $office = $position?->office_id ? (int) $position->office_id : null;
        $division = $position?->division_id ? (int) $position->division_id : null;
        $unit = $position?->unit_id ? (int) $position->unit_id : null;

        if ($unit && (!$division || !$office)) {
            $row = DB::table('lib_units')->where('id', $unit)->first(['unit_office', 'unit_division']);
            if ($row) {
                $division = $division ?: ($row->unit_division ? (int) $row->unit_division : null);
                $office = $office ?: ($row->unit_office ? (int) $row->unit_office : null);
            }
        }

        if ($division && !$office) {
            $officeId = DB::table('lib_divisions')->where('id_division', $division)->value('office_id');
            $office = $officeId ? (int) $officeId : null;
        }

        return ['office' => $office, 'division' => $division, 'unit' => $unit];
    }

    /**
     * Rank of an officers-table record (highest role flag wins).
     */
    private static function officerRank(Officer $officer): int
    {
        if ($officer->president) {
            return self::PRESIDENT;
        }
        if ($officer->vp) {
            return self::VP;
        }
        if ($officer->division_head) {
            return self::DIVISION_HEAD;
        }
        if ($officer->unit_head) {
            return self::UNIT_HEAD;
        }

        return self::REGULAR;
    }

    /**
     * Does this officer hold the given division / office? Uses the position their officer role is
     * linked to (officers.emp_position_id); falls back to all of their positions.
     */
    private static function officerCovers(Officer $officer, string $level, ?int $id): bool
    {
        if (!$id) {
            return false;
        }

        $positions = $officer->position
            ? [$officer->position]
            : ($officer->employee ? $officer->employee->positions->all() : []);

        foreach ($positions as $position) {
            if (self::placement($position)[$level] === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * The employees who may approve a request filed by $requester under $position.
     *
     * Returns null when the request is NOT chain-restricted (regular employees, or a head whose
     * position has no office/division/unit on record). In that case only the plain rule applies:
     * the approver must outrank the requester.
     *
     * @return int[]|null
     */
    public static function eligibleApproverIds(Employee $requester, ?EmpPosition $position): ?array
    {
        $rank = self::requesterRank($requester, $position);

        if ($rank < self::UNIT_HEAD) {
            return null;
        }
        if ($rank >= self::PRESIDENT) {
            return [];
        }

        $placement = self::placement($position);
        if (!$placement['office'] && !$placement['division']) {
            // The filing position has no org data: use the position the requester holds their officer role through
            $requester->loadMissing('officer.position');
            $placement = self::placement($requester->officer?->position);
        }
        if (!$placement['office'] && !$placement['division']) {
            return null;
        }

        $divisionHeads = [];
        $vps = [];
        $presidents = [];

        foreach (Officer::with(['employee.positions', 'position'])->get() as $officer) {
            if ((int) $officer->employee_id === (int) $requester->id) {
                continue;
            }

            $officerRank = self::officerRank($officer);
            if ($officerRank <= $rank) {
                continue;
            }

            if ($officerRank === self::DIVISION_HEAD) {
                // Only a Unit Head sits directly under a Division Head
                if ($rank === self::UNIT_HEAD && self::officerCovers($officer, 'division', $placement['division'])) {
                    $divisionHeads[] = (int) $officer->employee_id;
                }
            } elseif ($officerRank === self::VP) {
                if (self::officerCovers($officer, 'office', $placement['office'])) {
                    $vps[] = (int) $officer->employee_id;
                }
            } elseif ($officerRank === self::PRESIDENT) {
                $presidents[] = (int) $officer->employee_id;
            }
        }

        $eligible = array_merge($divisionHeads, $vps);

        // Division Heads and VPs are also approved by the President; a Unit Head only when the
        // office has no VP (the President then takes the VP's place)
        if ($rank >= self::DIVISION_HEAD || empty($vps)) {
            $eligible = array_merge($eligible, $presidents);
        }

        return array_values(array_unique($eligible));
    }

    /**
     * Check that every approver may approve this request.
     *
     * @param  Employee         $requester   The employee filing the travel order
     * @param  EmpPosition|null $position    The position the request is filed under
     * @param  array            $approverIds Employee ids (empty values are ignored)
     * @return string|null      An error message, or null when all approvers are valid
     */
    public static function validateApprovers(Employee $requester, ?EmpPosition $position, array $approverIds): ?string
    {
        $requesterRank = self::requesterRank($requester, $position);
        $eligible = self::eligibleApproverIds($requester, $position);

        foreach ($approverIds as $approverId) {
            if (empty($approverId)) {
                continue;
            }

            if ((int) $approverId === (int) $requester->id) {
                return 'You cannot select yourself as an approver.';
            }

            if ($eligible !== null) {
                if (!in_array((int) $approverId, $eligible, true)) {
                    return self::chainMessage($eligible);
                }
            } elseif (self::employeeRank($approverId) <= $requesterRank) {
                return self::higherApproverMessage($requesterRank);
            }
        }

        return null;
    }

    /**
     * Error message naming the people who may approve (chain-of-command case).
     */
    private static function chainMessage(array $eligibleIds): string
    {
        if (empty($eligibleIds)) {
            return 'There is no higher position that can approve your request.';
        }

        $people = Employee::with(['officer', 'positions'])->whereIn('id', $eligibleIds)->get()
            ->sortBy(fn (Employee $e) => self::employeeRank($e))
            ->map(function (Employee $e) {
                $name = trim((string) ($e->full_name ?: ($e->first_name . ' ' . $e->last_name)));
                return $name . ' (' . self::label(self::employeeRank($e)) . ')';
            })
            ->values()
            ->all();

        return 'Your approver must be from your chain of command. Allowed approvers: ' . implode(', ', $people) . '.';
    }

    /**
     * Error message for the plain "must outrank the requester" rule.
     */
    public static function higherApproverMessage(int $requesterRank): string
    {
        $allowed = [];
        for ($rank = self::PRESIDENT; $rank > $requesterRank; $rank--) {
            if ($rank >= self::UNIT_HEAD) {
                $allowed[] = self::label($rank);
            }
        }

        if (empty($allowed)) {
            return 'There is no higher position that can approve your request.';
        }

        // Lowest rank first (Division Head, VP, President) to match the approval order
        $allowed = array_reverse($allowed);

        if ($requesterRank === self::REGULAR) {
            return 'Your approver must be an officer: ' . implode(', ', $allowed) . '.';
        }

        return sprintf(
            'Your approver must hold a higher position than %s. Allowed approvers: %s.',
            self::label($requesterRank),
            implode(', ', $allowed)
        );
    }
}
