<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Itinerary;
use App\Models\TravelOrder;
use Illuminate\Support\Collection;

/**
 * Resolves WHO must give the final (post-Unit-Head) approval on an
 * itinerary.
 *
 * Rule: the approver is whoever is ONE rank higher than the passenger
 * in the University's officer hierarchy:
 *
 *     staff -> Unit Head -> Division Head -> VP -> President
 *
 * In practice, since Unit Head approval already happens as the first
 * stage, this normally resolves to the VP of the passenger's own
 * office — e.g. a passenger under the Office of the VP for Academic
 * Affairs is approved by VPAA, a passenger under the Office of the VP
 * for Administration is approved by VPAD — but it escalates further
 * (to the next VP, or to the President) when the passenger already
 * holds a Division Head or VP position.
 *
 * This class is the single source of truth for that resolution so the
 * approval queues/actions (ItineraryApprovalController) and the
 * printed itinerary (ItineraryController::generateExcel) never
 * disagree about who the approver is.
 */
class ItineraryApproverResolver
{
    public const RANK_STAFF = 0;
    public const RANK_UNIT_HEAD = 1;
    public const RANK_DIVISION_HEAD = 2;
    public const RANK_VP = 3;
    public const RANK_PRESIDENT = 4;

    /**
     * Employee IDs represented by an itinerary's passenger(s).
     *
     * itineraries.travel_order_id is a JSON column that, for legacy
     * records, may contain either Employee IDs or Travel Order IDs.
     * Both formats are resolved to Employee IDs here.
     */
    public static function resolveItineraryEmployeeIds(Itinerary $itinerary): Collection
    {
        $ids = collect($itinerary->travel_order_id ?? [])
            ->flatten()
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $employeeIds = Employee::whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $travelOrderEmployeeIds = TravelOrder::whereIn('id', $ids)
            ->pluck('employee_id')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id);

        return $employeeIds
            ->merge($travelOrderEmployeeIds)
            ->unique()
            ->values();
    }

    /**
     * Where the employee sits in the officer hierarchy.
     * Checks the officers table first, then falls back to the
     * role-per-position flags on their primary emp_position.
     */
    public static function employeeRank(Employee $employee): int
    {
        $officer = $employee->officer;

        if ($officer) {
            if ($officer->president) {
                return self::RANK_PRESIDENT;
            }
            if ($officer->vp) {
                return self::RANK_VP;
            }
            if ($officer->division_head) {
                return self::RANK_DIVISION_HEAD;
            }
            if ($officer->unit_head) {
                return self::RANK_UNIT_HEAD;
            }
        }

        $primary = $employee->positions()->where('is_primary', true)->first();

        if ($primary) {
            if ($primary->is_president) {
                return self::RANK_PRESIDENT;
            }
            if ($primary->is_vp) {
                return self::RANK_VP;
            }
            if ($primary->is_division_head) {
                return self::RANK_DIVISION_HEAD;
            }
            if ($primary->is_unit_head) {
                return self::RANK_UNIT_HEAD;
            }
        }

        return self::RANK_STAFF;
    }

    /**
     * Find the officer flagged $flag (unit_head/division_head/vp/president)
     * whose position sits under the given scope column/value
     * (unit_id/division_id/office_id). Prefers the officer's primary
     * position but falls back to any matching position.
     */
    private static function officerWithFlag(string $flag, string $matchColumn, $matchValue): ?Employee
    {
        if (!$matchValue) {
            return null;
        }

        $employee = Employee::whereHas('officer', function ($query) use ($flag) {
            $query->where($flag, true);
        })->whereHas('positions', function ($query) use ($matchColumn, $matchValue) {
            $query->where('is_primary', true)->where($matchColumn, $matchValue);
        })->first();

        if ($employee) {
            return $employee;
        }

        return Employee::whereHas('officer', function ($query) use ($flag) {
            $query->where($flag, true);
        })->whereHas('positions', function ($query) use ($matchColumn, $matchValue) {
            $query->where($matchColumn, $matchValue);
        })->first();
    }

    /**
     * The single employee ONE rank higher than the given passenger.
     * Returns null if nobody higher can be resolved (e.g. the
     * passenger is already the President, or the org data is
     * incomplete for their unit/division/office).
     */
    public static function resolveApprovingEmployee(Employee $passenger): ?Employee
    {
        $position = $passenger->positions()->where('is_primary', true)->first()
            ?? $passenger->positions()->first();

        if (!$position) {
            return null;
        }

        $rank = self::employeeRank($passenger);

        if ($rank <= self::RANK_STAFF && $position->unit_id) {
            $unitHead = self::officerWithFlag('unit_head', 'unit_id', $position->unit_id);
            if ($unitHead && $unitHead->id !== $passenger->id) {
                return $unitHead;
            }
        }

        if ($rank <= self::RANK_UNIT_HEAD && $position->division_id) {
            $divisionHead = self::officerWithFlag('division_head', 'division_id', $position->division_id);
            if ($divisionHead && $divisionHead->id !== $passenger->id) {
                return $divisionHead;
            }
        }

        if ($rank <= self::RANK_DIVISION_HEAD && $position->office_id) {
            // office_id 2 = VPAD (Administration), 3 = VPAA (Academic Affairs), etc.
            $vp = self::officerWithFlag('vp', 'office_id', $position->office_id);
            if ($vp && $vp->id !== $passenger->id) {
                return $vp;
            }
        }

        // Top of the chain (or the passenger is already a VP): the President approves.
        $president = Employee::whereHas('officer', function ($query) {
            $query->where('president', true);
        })->first();

        return ($president && $president->id !== $passenger->id) ? $president : null;
    }

    /** Transportation Services (unit 26). Its Unit Head approves VP-level itineraries (step 2). */
    public const TRANSPORTATION_UNIT_ID = 26;

    /** True when any passenger is a VP or higher. */
    private static function hasVpPassenger(Itinerary $itinerary): bool
    {
        $passengers = Employee::whereIn('id', self::resolveItineraryEmployeeIds($itinerary))->get();

        return $passengers->contains(fn (Employee $p) => self::employeeRank($p) >= self::RANK_VP);
    }

    /**
     * STEP 1 approver:
     *  - VP-level passenger -> the higher approver (President)
     *  - otherwise -> Unit Head of the passenger's own unit
     */
    public static function stepOneApproverFor(Itinerary $itinerary): ?Employee
    {
        if (self::hasVpPassenger($itinerary)) {
            return self::resolveItineraryApprovers($itinerary)->first();
        }

        $passengers = Employee::whereIn('id', self::resolveItineraryEmployeeIds($itinerary))->get();

        foreach ($passengers as $passenger) {
            $position = $passenger->positions()->where('is_primary', true)->first();

            if (!$position || !$position->unit_id) {
                continue;
            }

            $head = self::officerWithFlag('unit_head', 'unit_id', $position->unit_id);

            if ($head && $head->id !== $passenger->id) {
                return $head;
            }
        }

        return null;
    }

    /**
     * STEP 2 (final) approver:
     *  - VP-level passenger -> Unit Head of Transportation Services
     *  - otherwise -> the higher approver one rank above the passenger
     */
    public static function finalApproverFor(Itinerary $itinerary): ?Employee
    {
        if (self::hasVpPassenger($itinerary)) {
            return self::officerWithFlag('unit_head', 'unit_id', self::TRANSPORTATION_UNIT_ID);
        }

        return self::resolveItineraryApprovers($itinerary)->first();
    }

    /** The lower-rank officer printed on the itinerary form. */
    public static function lowerOfficerFor(Itinerary $itinerary): ?Employee
    {
        return self::hasVpPassenger($itinerary)
            ? self::officerWithFlag('unit_head', 'unit_id', self::TRANSPORTATION_UNIT_ID)
            : self::stepOneApproverFor($itinerary);
    }

    /** Is this employee the STEP 1 approver for this itinerary? */
    public static function isStepOneApprover(Employee $employee, Itinerary $itinerary): bool
    {
        $approver = self::stepOneApproverFor($itinerary);

        return $approver !== null && (int) $approver->id === (int) $employee->id;
    }

    /** Is this employee the STEP 2 (final) approver for this itinerary? */
    public static function isFinalApprover(Employee $employee, Itinerary $itinerary): bool
    {
        $approver = self::finalApproverFor($itinerary);

        return $approver !== null && (int) $approver->id === (int) $employee->id;
    }

    /**
     * All distinct approving employees required for an itinerary's
     * passenger(s). Usually a single VP, but an itinerary can list
     * more than one passenger, possibly from different offices.
     */
    public static function resolveItineraryApprovers(Itinerary $itinerary): Collection
    {
        $employeeIds = self::resolveItineraryEmployeeIds($itinerary);

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        return Employee::whereIn('id', $employeeIds)
            ->get()
            ->map(fn (Employee $passenger) => self::resolveApprovingEmployee($passenger))
            ->filter()
            ->unique('id')
            ->values();
    }
}
