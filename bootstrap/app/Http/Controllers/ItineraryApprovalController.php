<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Itinerary;
use App\Models\TravelOrder;
use App\Models\Unit;
use App\Support\ItineraryApproverResolver;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItineraryApprovalController extends Controller
{
    /**
     * Normalize itinerary.travel_order_id.
     *
     * The current database contains legacy records where this JSON field
     * may contain Employee IDs and records where it contains Travel Order IDs.
     */
    private function storedItineraryIds(Itinerary $itinerary): Collection
    {
        $value = $itinerary->travel_order_id;

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                $value = [$value];
            }
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        return collect($value)
            ->flatten()
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Return the employee IDs represented by an itinerary.
     *
     * Supports BOTH database formats:
     *
     *   travel_order_id = [employee_id]
     *   travel_order_id = [travel_order_id]
     *
     * This method is the single source of truth for Unit Head jurisdiction.
     */
    private function resolveItineraryEmployeeIds(Itinerary $itinerary): Collection
    {
        $ids = $this->storedItineraryIds($itinerary);

        if ($ids->isEmpty()) {
            return collect();
        }

        // Format 1: IDs are employee IDs.
        $employeeIds = Employee::whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        // Format 2: IDs are Travel Order IDs.
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
     * Get the Unit Head's unit.
     *
     * Primary position is preferred. If the account has no primary position,
     * fall back to any position with a unit.
     */
    private function getHeadUnitId($user): ?int
    {
        $employee = $user->employee;

        if (!$employee) {
            return null;
        }

        $unitId = $employee->positions()
            ->where('is_primary', 1)
            ->value('unit_id');

        if ($unitId) {
            return (int) $unitId;
        }

        $unitId = $employee->positions()
            ->whereNotNull('unit_id')
            ->value('unit_id');

        return $unitId ? (int) $unitId : null;
    }

    /**
     * Get every employee belonging to a unit through emp_positions.
     */
    private function getUnitEmployeeIds(?int $unitId): Collection
    {
        if (!$unitId) {
            return collect();
        }

        return Employee::whereHas('positions', function ($query) use ($unitId) {
            $query->where('unit_id', $unitId);
        })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Check whether the Unit Head is the Transportation Services Unit Head.
     *
     * Transportation Services keeps the existing behavior of seeing
     * the entire Unit Head itinerary queue.
     */
    private function isTransportationServicesUnitHead($user): bool
    {
        $employee = $user->employee;

        if (!$employee || !(bool) $employee->is_head) {
            return false;
        }

        $unitId = $this->getHeadUnitId($user);

        if (!$unitId) {
            return false;
        }

        $unitName = Unit::whereKey($unitId)->value('unit_name');

        return $unitName
            && str_contains(strtolower($unitName), 'transportation services');
    }

    /**
     * SAME jurisdiction check used by:
     * - Unit Head list
     * - Unit Head counts
     * - Unit Head approve
     * - Unit Head reject
     */
    private function itineraryBelongsToUnitHead(Itinerary $itinerary, $user): bool
    {
        if ($this->isTransportationServicesUnitHead($user)) {
            return true;
        }

        $headUnitId = $this->getHeadUnitId($user);

        if (!$headUnitId) {
            return false;
        }

        $unitEmployeeIds = $this->getUnitEmployeeIds($headUnitId);

        if ($unitEmployeeIds->isEmpty()) {
            return false;
        }

        return $this->resolveItineraryEmployeeIds($itinerary)
            ->intersect($unitEmployeeIds)
            ->isNotEmpty();
    }

    /**
     * SAME jurisdiction check used by:
     * - VP list
     * - VP counts
     * - VP approve
     * - VP reject
     *
     * The correct approver for an itinerary is whoever is ONE rank
     * higher than the passenger (Unit Head -> Division Head -> VP ->
     * President) — e.g. VPAA for a passenger under Academic Affairs,
     * VPAD for a passenger under Administration. See
     * ItineraryApproverResolver for the full rule.
     */
    private function itineraryBelongsToVp(Itinerary $itinerary, $user): bool
    {
        $employee = $user->employee;

        if (!$employee) {
            return false;
        }

        return ItineraryApproverResolver::resolveItineraryApprovers($itinerary)
            ->contains('id', $employee->id);
    }

    /**
     * Whether the account is allowed into the "final approver" queues
     * at all: any VP (they may or may not be the specific approver for
     * a given itinerary — that is checked per-itinerary via
     * itineraryBelongsToVp), plus any employee who is flagged as
     * President, since a passenger who is themselves a VP escalates to
     * the President.
     */
    private function canActAsFinalApprover($user): bool
    {
        if ($user->isVp() || $user->isAdmin() || $user->isMotorpoolAdmin()) {
            return true;
        }

        $employee = $user->employee;

        return (bool) ($employee && $employee->officer && $employee->officer->president);
    }

    /**
     * Build the Unit Head-scoped itinerary collection.
     *
     * IMPORTANT:
     * We intentionally resolve the JSON field in PHP instead of using
     * whereJsonContains(). MySQL JSON matching can differ when existing
     * records contain numeric values in one format and JSON strings in
     * another. Resolving the records here makes the list/count/action
     * behavior identical.
     */
    private function unitHeadScopedItineraries($user): Collection
    {
        $query = Itinerary::with(['driver', 'vehicle'])
            ->orderBy('created_at', 'desc');

        $itineraries = $query->get();

        if ($this->isTransportationServicesUnitHead($user)) {
            return $itineraries;
        }

        return $itineraries
            ->filter(fn (Itinerary $itinerary) =>
                $this->itineraryBelongsToUnitHead($itinerary, $user)
            )
            ->values();
    }

    /**
     * Apply the same tab/search logic to an already unit-scoped collection.
     */
    private function filterItineraryCollection(
        Collection $itineraries,
        string $tab,
        string $search
    ): Collection {
        $filtered = $itineraries;

        switch ($tab) {
            case 'approved':
                $filtered = $filtered->filter(function ($itinerary) {
                    return (bool) $itinerary->unit_head_approved
                        && !is_null($itinerary->unit_head_approved_at)
                        && $itinerary->status !== 'Cancelled';
                });
                break;

            case 'cancelled':
                $filtered = $filtered->filter(function ($itinerary) {
                    return $itinerary->status === 'Cancelled';
                });
                break;

            case 'pending':
            default:
                $filtered = $filtered->filter(function ($itinerary) {
                    return !(bool) $itinerary->unit_head_approved
                        && is_null($itinerary->unit_head_approved_at)
                        && $itinerary->status !== 'Cancelled';
                });
                break;
        }

        if ($search !== '') {
            $needle = strtolower($search);

            $filtered = $filtered->filter(function ($itinerary) use ($needle) {
                $values = [
                    $itinerary->destination,
                    $itinerary->purpose,
                    $itinerary->status,
                    $itinerary->date_from,
                    $itinerary->date_to,
                ];

                foreach ($values as $value) {
                    if ($value !== null && str_contains(strtolower((string) $value), $needle)) {
                        return true;
                    }
                }

                $driver = $itinerary->driver;

                if ($driver) {
                    $driverName = trim(
                        ($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '')
                    );

                    if (str_contains(strtolower($driverName), $needle)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        return $filtered
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Paginate an in-memory collection.
     */
    private function paginateCollection(
        Collection $items,
        Request $request,
        int $perPage = 10
    ): LengthAwarePaginator {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $items->count();

        $results = $items
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /**
     * Unit Head / Head Panel itinerary approval queue.
     */
    public function unitHeadPending(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        /*
         * Use is_head directly as the authority check so an account that
         * carries multiple leadership flags (for example is_head + is_vp)
         * is still recognized as a Head Panel account on this route.
         */
        $isHead = $employee && (bool) $employee->is_head;

        if (
            !$isHead &&
            !$user->isUnitHead() &&
            !$user->isAdmin() &&
            !$user->isMotorpoolAdmin()
        ) {
            abort(403, 'Unauthorized to view Unit Head pending itineraries');
        }

        $tab = $request->get('tab', 'pending');
        $search = trim((string) $request->get('search', ''));

        if ($user->isAdmin() || $user->isMotorpoolAdmin()) {
            $scoped = Itinerary::with(['driver', 'vehicle'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $scoped = $this->unitHeadScopedItineraries($user);
        }

        $filtered = $this->filterItineraryCollection(
            $scoped,
            $tab,
            $search
        );

        $itineraries = $this->paginateCollection(
            $filtered,
            $request,
            10
        );

        /*
         * Counts are generated from the SAME scoped collection.
         * Therefore the number shown in the tabs cannot disagree with
         * the actual records displayed by the Unit Head queue.
         */
        $pendingCount = $this->filterItineraryCollection(
            $scoped,
            'pending',
            ''
        )->count();

        $approvedCount = $this->filterItineraryCollection(
            $scoped,
            'approved',
            ''
        )->count();

        $cancelledCount = $this->filterItineraryCollection(
            $scoped,
            'cancelled',
            ''
        )->count();

        return view(
            'itineraries.approvals.unit-head-pending',
            compact(
                'itineraries',
                'tab',
                'pendingCount',
                'approvedCount',
                'cancelledCount',
                'search'
            )
        );
    }

    /**
     * Unit Head approves an itinerary.
     */
    public function approveByUnitHead($id)
    {
        $itinerary = Itinerary::findOrFail($id);
        $user = Auth::user();
        $employee = $user->employee;

        $isHead = $employee && (bool) $employee->is_head;

        if (
            !$isHead &&
            !$user->isUnitHead() &&
            !$user->isAdmin() &&
            !$user->isMotorpoolAdmin()
        ) {
            abort(403, 'Unauthorized to approve itineraries');
        }

        if ($itinerary->status === 'Cancelled') {
            abort(422, 'Cancelled itineraries cannot be approved.');
        }

        if ($itinerary->unit_head_approved) {
            abort(422, 'This itinerary has already been approved by the Unit Head.');
        }

        if (
            !$user->isAdmin() &&
            !$user->isMotorpoolAdmin() &&
            !$this->itineraryBelongsToUnitHead($itinerary, $user)
        ) {
            abort(403, 'Unauthorized to approve this itinerary.');
        }

        DB::transaction(function () use ($itinerary, $user) {
            $itinerary->update([
                'unit_head_approved' => true,
                'unit_head_approved_by' => $user->id,
                'unit_head_approved_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Itinerary approved by Unit Head successfully.',
        ]);
    }

    /**
     * Unit Head rejects an itinerary.
     */
    public function rejectByUnitHead(Request $request, $id)
    {
        $itinerary = Itinerary::findOrFail($id);
        $user = Auth::user();
        $employee = $user->employee;

        $isHead = $employee && (bool) $employee->is_head;

        if (
            !$isHead &&
            !$user->isUnitHead() &&
            !$user->isAdmin() &&
            !$user->isMotorpoolAdmin()
        ) {
            abort(403, 'Unauthorized to reject itineraries');
        }

        if ($itinerary->status === 'Cancelled') {
            abort(422, 'This itinerary has already been cancelled.');
        }

        if ($itinerary->unit_head_approved) {
            abort(422, 'This itinerary has already been approved by the Unit Head.');
        }

        if (
            !$user->isAdmin() &&
            !$user->isMotorpoolAdmin() &&
            !$this->itineraryBelongsToUnitHead($itinerary, $user)
        ) {
            abort(403, 'Unauthorized to reject this itinerary.');
        }

        $rejectionReason = trim((string) $request->input('remarks', ''));

        DB::transaction(function () use (
            $itinerary,
            $user,
            $rejectionReason
        ) {
            $itinerary->update([
                'status' => 'Cancelled',
                'rejection_reason' => $rejectionReason ?: null,
                'rejected_by_employee_id' => $user->employee?->id,
                'rejected_at' => now(),
            ]);
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Itinerary rejected by Unit Head successfully.',
            ]);
        }

        return redirect()
            ->back()
            ->with('info', 'Itinerary rejected by Unit Head.');
    }

    /**
     * VP itinerary approval queue.
     *
     * Unit Head approval is mandatory before an itinerary reaches this queue.
     */
    public function vpPending(Request $request)
    {
        $user = Auth::user();

        if (!$this->canActAsFinalApprover($user)) {
            abort(403, 'Unauthorized to view VP pending itineraries');
        }

        $tab = $request->get('tab', 'pending');
        $search = trim((string) $request->get('search', ''));

        $unitHeadApproved = Itinerary::where('unit_head_approved', true)
            ->whereNotNull('unit_head_approved_at')
            ->with(['driver', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();

        /*
         * Each VP only sees itineraries whose passenger(s) resolve to
         * them as the next-rank approver (e.g. VPAA only sees Academic
         * Affairs passengers, VPAD only sees Administration passengers).
         * Admin/Motorpool Admin retain the full queue.
         */
        if ($user->isAdmin() || $user->isMotorpoolAdmin()) {
            $scoped = $unitHeadApproved;
        } else {
            $scoped = $unitHeadApproved
                ->filter(fn (Itinerary $itinerary) => $this->itineraryBelongsToVp($itinerary, $user))
                ->values();
        }

        switch ($tab) {
            case 'approved':
                $filtered = $scoped->filter(function ($itinerary) {
                    return (bool) $itinerary->vp_approved
                        && !is_null($itinerary->vp_approved_at)
                        && $itinerary->status !== 'Cancelled';
                });
                break;

            case 'cancelled':
                $filtered = $scoped->filter(function ($itinerary) {
                    return $itinerary->status === 'Cancelled';
                });
                break;

            case 'pending':
            default:
                $filtered = $scoped->filter(function ($itinerary) {
                    return !(bool) $itinerary->vp_approved
                        && is_null($itinerary->vp_approved_at)
                        && $itinerary->status !== 'Cancelled';
                });
                break;
        }

        if ($search !== '') {
            $needle = strtolower($search);

            $filtered = $filtered->filter(function ($itinerary) use ($needle) {
                $values = [
                    $itinerary->destination,
                    $itinerary->purpose,
                    $itinerary->status,
                    $itinerary->date_from,
                    $itinerary->date_to,
                ];

                foreach ($values as $value) {
                    if ($value !== null && str_contains(strtolower((string) $value), $needle)) {
                        return true;
                    }
                }

                $driver = $itinerary->driver;

                if ($driver) {
                    $driverName = trim(
                        ($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '')
                    );

                    if (str_contains(strtolower($driverName), $needle)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $filtered = $filtered->sortByDesc('created_at')->values();

        $itineraries = $this->paginateCollection(
            $filtered,
            $request,
            10
        );

        $pendingCount = $scoped->filter(function ($itinerary) {
            return !(bool) $itinerary->vp_approved
                && is_null($itinerary->vp_approved_at)
                && $itinerary->status !== 'Cancelled';
        })->count();

        $approvedCount = $scoped->filter(function ($itinerary) {
            return (bool) $itinerary->vp_approved
                && !is_null($itinerary->vp_approved_at)
                && $itinerary->status !== 'Cancelled';
        })->count();

        $cancelledCount = $scoped
            ->filter(fn ($itinerary) => $itinerary->status === 'Cancelled')
            ->count();

        return view(
            'itineraries.approvals.vp-pending',
            compact(
                'itineraries',
                'tab',
                'pendingCount',
                'approvedCount',
                'cancelledCount',
                'search'
            )
        );
    }

    /**
     * VP approves an itinerary.
     *
     * Unit Head approval MUST already exist.
     */
    public function approveByVp($id)
    {
        $itinerary = Itinerary::findOrFail($id);
        $user = Auth::user();

        if (!$this->canActAsFinalApprover($user)) {
            abort(403, 'Unauthorized to approve itineraries');
        }

        if ($itinerary->status === 'Cancelled') {
            abort(422, 'Cancelled itineraries cannot be approved.');
        }

        if (
            !$itinerary->unit_head_approved ||
            is_null($itinerary->unit_head_approved_at)
        ) {
            abort(
                422,
                'This itinerary cannot be approved by the VP until it has been approved by the Unit Head.'
            );
        }

        if ($itinerary->vp_approved) {
            abort(422, 'This itinerary has already been approved by the VP.');
        }

        /*
         * Resolve the same linkage used by the Unit Head stage.
         * This prevents malformed/orphan itineraries from entering final approval.
         */
        if ($this->resolveItineraryEmployeeIds($itinerary)->isEmpty()) {
            abort(
                422,
                'Unable to determine the travel order employee for this itinerary.'
            );
        }

        /*
         * Only the officer one rank above the passenger (e.g. VPAA for
         * Academic Affairs, VPAD for Administration) may approve —
         * unless the account is Admin/Motorpool Admin.
         */
        if (
            !$user->isAdmin() &&
            !$user->isMotorpoolAdmin() &&
            !$this->itineraryBelongsToVp($itinerary, $user)
        ) {
            abort(403, 'This itinerary must be approved by the officer one rank above the passenger.');
        }

        DB::transaction(function () use ($itinerary, $user) {
            $itinerary->update([
                'vp_approved' => true,
                'vp_approved_by' => $user->id,
                'vp_approved_at' => now(),
                'status' => 'Approved',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Itinerary approved by VP successfully. Itinerary is now fully approved.',
        ]);
    }

    /**
     * VP rejects an itinerary.
     *
     * Existing project rule retained:
     * only VP of Administration may reject unless elevated admin.
     */
    public function rejectByVp(Request $request, $id)
    {
        $itinerary = Itinerary::findOrFail($id);
        $user = Auth::user();

        if (!$this->canActAsFinalApprover($user)) {
            abort(403, 'Unauthorized to reject itineraries');
        }

        if (
            !$itinerary->unit_head_approved ||
            is_null($itinerary->unit_head_approved_at)
        ) {
            abort(
                422,
                'This itinerary cannot be rejected by the VP until it has been approved by the Unit Head.'
            );
        }

        if ($itinerary->status === 'Cancelled') {
            abort(422, 'This itinerary has already been cancelled.');
        }

        if ($itinerary->vp_approved) {
            abort(422, 'This itinerary has already been approved by the VP.');
        }

        if ($this->resolveItineraryEmployeeIds($itinerary)->isEmpty()) {
            abort(
                422,
                'Unable to determine the travel order employee for this itinerary.'
            );
        }

        /*
         * Only the officer one rank above the passenger (e.g. VPAA for
         * Academic Affairs, VPAD for Administration) may reject —
         * unless the account is Admin/Motorpool Admin.
         */
        if (
            !$user->isAdmin() &&
            !$user->isMotorpoolAdmin() &&
            !$this->itineraryBelongsToVp($itinerary, $user)
        ) {
            abort(403, 'This itinerary must be rejected by the officer one rank above the passenger.');
        }

        $rejectionReason = trim((string) $request->input('remarks', ''));

        DB::transaction(function () use (
            $itinerary,
            $user,
            $rejectionReason
        ) {
            $itinerary->update([
                'status' => 'Cancelled',
                'rejection_reason' => $rejectionReason ?: null,
                'rejected_by_employee_id' => $user->employee?->id,
                'rejected_at' => now(),
            ]);
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Itinerary rejected by VP successfully.',
            ]);
        }

        return redirect()
            ->back()
            ->with('info', 'Itinerary rejected by VP.');
    }

    /**
     * View fully approved itineraries.
     */
    public function approved()
    {
        $user = Auth::user();

        if (
            !$user->isMotorpoolAdmin() &&
            !$user->isAdmin() &&
            !$user->isVp() &&
            !$user->isUnitHead() &&
            !($user->employee && (bool) $user->employee->is_head)
        ) {
            abort(403, 'Unauthorized to view approved itineraries');
        }

        $query = Itinerary::where('vp_approved', true)
            ->whereNotNull('vp_approved_at')
            ->where('status', '!=', 'Cancelled')
            ->with(['driver', 'vehicle'])
            ->orderBy('created_at', 'desc');

        if ($user->isMotorpoolAdmin() || $user->isAdmin()) {
            $items = $query->get();
        } elseif ($user->isVp()) {
            $items = $query->get()
                ->filter(fn ($itinerary) =>
                    $this->itineraryBelongsToVp($itinerary, $user)
                )
                ->values();
        } else {
            $items = $query->get()
                ->filter(fn ($itinerary) =>
                    $this->itineraryBelongsToUnitHead($itinerary, $user)
                )
                ->values();
        }

        return view(
            'itineraries.approvals.approved',
            ['itineraries' => $items]
        );
    }
}
