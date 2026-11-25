<?php

namespace App\Support;

use App\Models\EmpPosition;
use App\Models\Employee;
use App\Models\Itinerary;
use App\Models\Officer;
use App\Models\TravelOrder;
use Illuminate\Support\Collection;

/**
 * Who approves an itinerary, and in what order.
 *
 *  STEP 1 (Unit Head queue): the "one unit higher" head of the passenger
 *      staff          -> Unit Head of their unit
 *      unit head      -> Division Head of their division
 *      division head  -> VP of their office
 *      VP             -> President
 *
 *  STEP 2 (VP queue): Unit Head of Transportation Services (Sir Irwin)
 *
 * Role flags come from the `officers` table (unit_head, division_head, vp, president).
 * Units, divisions, and offices come from `emp_positions`.
 */
class ItineraryApprovers
{
    /** Unit ID of Transportation Services. */
    public const TRANSPORTATION_UNIT_ID = 26;

    /** Passengers of an itinerary (employees on its travel orders). */
    public static function passengers(Itinerary $itinerary): Collection
    {
        $ids = collect((array) ($itinerary->travel_order_id ?? []))->filter()->values();

        $employeeIds = TravelOrder::whereIn('id', $ids)->pluck('employee_id')->filter()->unique();

        return Employee::whereIn('id', $employeeIds)->get();
    }

    /** Employee ids that have a given role flag. */
    private static function holdersOf(string $flag): Collection
    {
        return Officer::where($flag, true)->pluck('employee_id');
    }

    /** Does this employee have the role flag? */
    private static function hasRole(Employee $employee, string $flag): bool
    {
        return Officer::where('employee_id', $employee->id)->where($flag, true)->exists();
    }

    /** Primary position of an employee. */
    private static function primaryPosition(Employee $employee): ?EmpPosition
    {
        return EmpPosition::where('employee_id', $employee->id)->where('is_primary', true)->first();
    }

    /** Employee holding a role in a given unit, division, or office (not the excluded person). */
    private static function holderOf(string $flag, string $column, $value, ?int $excludeId): ?Employee
    {
        if (!$value) {
            return null;
        }

        $position = EmpPosition::where($column, $value)
            ->where('is_primary', true)
            ->whereIn('employee_id', self::holdersOf($flag))
            ->with('employee')
            ->get()
            ->first(fn (EmpPosition $p) => $p->employee && (int) $p->employee->id !== (int) $excludeId);

        return $position?->employee;
    }

    /** The President. */
    private static function president(): ?Employee
    {
        $id = self::holdersOf('president')->first();

        return $id ? Employee::find($id) : null;
    }

    /** Unit Head of Transportation Services (the STEP 2 approver). */
    public static function transportationHead(): ?Employee
    {
        // Match the unit by name, or by its ID (26) as a backup
        $position = EmpPosition::where('is_primary', true)
            ->whereIn('employee_id', self::holdersOf('unit_head'))
            ->where(function ($q) {
                $q->whereHas('unit', fn ($u) => $u->whereRaw('LOWER(unit_name) LIKE ?', ['%transportation%']))
                    ->orWhere('unit_id', self::TRANSPORTATION_UNIT_ID);
            })
            ->with('employee')
            ->first();

        return $position?->employee;
    }

    /** STEP 1 approver: the one unit higher than the (first) passenger. */
    /** VP of the Office of the Vice President for Administration (office id 2). */
    public static function adminVp(): ?Employee
    {
        $position = EmpPosition::where('is_primary', true)
            ->where('office_id', 2)
            ->whereIn('employee_id', self::holdersOf('vp'))
            ->with('employee')
            ->first();

        return $position?->employee;
    }

    /** STEP 1 approver for every itinerary: VP for Administration (Ariel). */
    public static function step1For(Itinerary $itinerary): ?Employee
    {
        return self::adminVp();
    }

    /** Previous one-unit-higher rule, kept for reference only (not used). */
    private static function legacyStep1For(Itinerary $itinerary): ?Employee
    {
        $passenger = self::passengers($itinerary)->first();
        if (!$passenger) {
            return null;
        }

        $position = self::primaryPosition($passenger);
        if (!$position) {
            return self::president();
        }

        $exclude = $passenger->id;

        if (!self::hasRole($passenger, 'unit_head') && $position->unit_id) {
            $head = self::holderOf('unit_head', 'unit_id', $position->unit_id, $exclude);
            if ($head) {
                return $head;
            }
        }

        if (!self::hasRole($passenger, 'division_head') && $position->division_id) {
            $head = self::holderOf('division_head', 'division_id', $position->division_id, $exclude);
            if ($head) {
                return $head;
            }
        }

        if (!self::hasRole($passenger, 'vp') && $position->office_id) {
            $head = self::holderOf('vp', 'office_id', $position->office_id, $exclude);
            if ($head) {
                return $head;
            }
        }

        return self::president();
    }

    /** STEP 2 approver: Unit Head of Transportation Services. */
    public static function step2For(Itinerary $itinerary): ?Employee
    {
        return self::transportationHead();
    }

    public static function isStep1(Employee $employee, Itinerary $itinerary): bool
    {
        $approver = self::step1For($itinerary);

        return $approver !== null && (int) $approver->id === (int) $employee->id;
    }

    public static function isStep2(Employee $employee, Itinerary $itinerary): bool
    {
        $approver = self::step2For($itinerary);

        return $approver !== null && (int) $approver->id === (int) $employee->id;
    }

    /**
     * Should this employee see the Itinerary Approvals section at all?
     * Yes for any Unit Head, Division Head, VP, President, or the Transportation Head,
     * even when nothing is pending. The badge shows only what is pending.
     */
    public static function isApprover(Employee $employee): bool
    {
        $adminVp = self::adminVp();
        $transport = self::transportationHead();

        return ($adminVp && (int) $adminVp->id === (int) $employee->id)
            || ($transport && (int) $transport->id === (int) $employee->id);
    }

    /** STEP 1 items still waiting for this employee (not yet approved). Used by the sidebar badge. */
    public static function pendingIdsForStep1(Employee $employee): Collection
    {
        return Itinerary::where('status', '!=', 'Cancelled')
            ->where('unit_head_approved', false)
            ->get()
            ->filter(fn (Itinerary $i) => self::isStep1($employee, $i))
            ->pluck('id')->values();
    }

    /** STEP 2 items still waiting for this employee (step 1 done, not yet approved). Used by the sidebar badge. */
    public static function pendingIdsForStep2(Employee $employee): Collection
    {
        return Itinerary::where('status', '!=', 'Cancelled')
            ->where('unit_head_approved', true)
            ->where('vp_approved', false)
            ->get()
            ->filter(fn (Itinerary $i) => self::isStep2($employee, $i))
            ->pluck('id')->values();
    }

    /** Itinerary ids this employee must act on at STEP 1. */
    public static function idsForStep1(Employee $employee): Collection
    {
        return Itinerary::where('status', '!=', 'Cancelled')->get()
            ->filter(fn (Itinerary $i) => self::isStep1($employee, $i))
            ->pluck('id')->values();
    }

    /** Itinerary ids this employee must act on at STEP 2. */
    public static function idsForStep2(Employee $employee, bool $includeCancelled = false): Collection
    {
        $query = Itinerary::query();
        if (!$includeCancelled) {
            $query->where('status', '!=', 'Cancelled');
        }
        return $query->get()
            ->filter(fn (Itinerary $i) => self::isStep2($employee, $i))
            ->pluck('id')->values();
    }
}
