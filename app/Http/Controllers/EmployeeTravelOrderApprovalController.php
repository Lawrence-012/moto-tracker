<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\TravelOrder;
use App\Models\Employee;

class EmployeeTravelOrderApprovalController extends Controller
{
    /**
     * Display travel orders pending approval by the current user.
     */
    public function pendingApprovals(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        $employeeId = $employee->id;
        
        // Get travel orders where this employee is the approver 1 or approver 2
        // Simpler approach: get all matching IDs first, then query
        $approver1Ids = DB::table('travel_orders')
            ->where('approver1_employee_id', $employeeId)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->whereNull('approver1_approved_at')  // Changed from president_approved check
            ->pluck('id');
        
        $approver2Ids = DB::table('travel_orders')
            ->where('approver2_employee_id', $employeeId)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->whereNotNull('approver1_approved_at')  // First approver must have approved
            ->whereNull('approver2_approved_at')  // Changed from vp_approved check
            ->pluck('id');
        
        // Combine both sets of IDs
        $allIds = array_unique(array_merge($approver1Ids->toArray(), $approver2Ids->toArray()));
        
        // Now query using these IDs
        $query = TravelOrder::whereIn('id', $allIds);
        
        // Apply search filter if provided
        $search = $request->get('search', '');
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($empQ) use ($search) {
                    $empQ->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%");
                })
                ->orWhere('destination', 'LIKE', "%{$search}%")
                ->orWhere('purpose', 'LIKE', "%{$search}%");
            });
        }
        
        // Get paginated results
        $travelOrders = $query->with(['employee', 'position', 'approver1', 'approver2'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Count for badge
        $pendingCount = count($allIds);
        
        // Get counts for all tabs
        $approvedCount = $this->getApprovedCount($employeeId, $search ?? '');
        $cancelledCount = $this->getCancelledCount($employeeId, $search ?? '');
        
        return view('travel-orders.approvals', compact('travelOrders', 'pendingCount', 'approvedCount', 'cancelledCount', 'search'));
    }

    /**
     * Display approved travel orders.
     */
    public function approvedApprovals(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        $employeeId = $employee->id;
        $search = $request->get('search', '');
        
        // Get travel orders that were approved by this employee (as approver 1 or approver 2)
        $query = TravelOrder::where('status', 'approved')
            ->where(function($q) use ($employeeId) {
                $q->where('approver1_employee_id', $employeeId)
                  ->whereNotNull('approver1_approved_at')  // Changed from president_approved
                  ->orWhere('approver2_employee_id', $employeeId)
                  ->whereNotNull('approver2_approved_at');  // Changed from vp_approved
            });
        
        // Apply search filter
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($empQ) use ($search) {
                    $empQ->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%");
                })
                ->orWhere('destination', 'LIKE', "%{$search}%")
                ->orWhere('purpose', 'LIKE', "%{$search}%");
            });
        }
        
        $travelOrders = $query->with(['employee', 'position', 'approver1', 'approver2'])
            ->orderBy('president_approved_at', 'desc')
            ->paginate(10);
        
        // Get counts for all tabs
        $pendingCount = $this->getPendingCount($employeeId, $search ?? '');
        $approvedCount = $query->count();
        $cancelledCount = $this->getCancelledCount($employeeId, $search ?? '');
        
        return view('travel-orders.approvals', compact('travelOrders', 'pendingCount', 'approvedCount', 'cancelledCount', 'search'));
    }

    /**
     * Display cancelled travel orders.
     */
    public function cancelledApprovals(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        $employeeId = $employee->id;
        $search = $request->get('search', '');
        
        // Get travel orders that were cancelled/rejected by this employee
        $query = TravelOrder::where('status', 'cancelled')
            ->where(function($q) use ($employeeId) {
                $q->where('approver1_employee_id', $employeeId)
                  ->where('president_approved', false)
                  ->orWhere('approver2_employee_id', $employeeId)
                  ->where('vp_approved', false);
            });
        
        // Apply search filter
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($empQ) use ($search) {
                    $empQ->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%");
                })
                ->orWhere('destination', 'LIKE', "%{$search}%")
                ->orWhere('purpose', 'LIKE', "%{$search}%");
            });
        }
        
        $travelOrders = $query->with(['employee', 'position', 'approver1', 'approver2'])
            ->orderBy('president_approved_at', 'desc')
            ->paginate(10);
        
        // Get counts for all tabs
        $pendingCount = $this->getPendingCount($employeeId, $search ?? '');
        $approvedCount = $this->getApprovedCount($employeeId, $search ?? '');
        $cancelledCount = $query->count();
        
        return view('travel-orders.approvals', compact('travelOrders', 'pendingCount', 'approvedCount', 'cancelledCount', 'search'));
    }

    /**
     * Helper method to get pending count.
     */
    private function getPendingCount($employeeId, $search = '')
    {
        // Get approver 1 count
        $approver1Count = DB::table('travel_orders')
            ->where('approver1_employee_id', $employeeId)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->where(function($q) {
                $q->whereNull('president_approved')
                  ->orWhere('president_approved', false);
            })
            ->count();
            
        // Get approver 2 count  
        $approver2Count = DB::table('travel_orders')
            ->where('approver2_employee_id', $employeeId)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->where('president_approved', true)
            ->where(function($q) {
                $q->whereNull('vp_approved')
                  ->orWhere('vp_approved', false);
            })
            ->count();
            
        return $approver1Count + $approver2Count;
    }
    
    /**
     * Helper method to get approved count.
     */
    private function getApprovedCount($employeeId, $search = '')
    {
        $query = TravelOrder::where('status', 'approved')
            ->where(function($q) use ($employeeId) {
                $q->where('approver1_employee_id', $employeeId)
                  ->where('president_approved', true)
                  ->orWhere('approver2_employee_id', $employeeId)
                  ->where('vp_approved', true);
            });
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($empQ) use ($search) {
                    $empQ->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%");
                })
                ->orWhere('destination', 'LIKE', "%{$search}%")
                ->orWhere('purpose', 'LIKE', "%{$search}%");
            });
        }
        
        return $query->count();
    }

    /**
     * Helper method to get cancelled count.
     */
    private function getCancelledCount($employeeId, $search = '')
    {
        $query = TravelOrder::where('status', 'cancelled')
            ->where(function($q) use ($employeeId) {
                $q->where('approver1_employee_id', $employeeId)
                  ->where('president_approved', false)
                  ->orWhere('approver2_employee_id', $employeeId)
                  ->where('vp_approved', false);
            });
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($empQ) use ($search) {
                    $empQ->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%");
                })
                ->orWhere('destination', 'LIKE', "%{$search}%")
                ->orWhere('purpose', 'LIKE', "%{$search}%");
            });
        }
        
        return $query->count();
    }

    /**
     * Approve a travel order.
     */
    public function approve(TravelOrder $travelOrder)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        // Verify that the current user is the selected approver 1
        if ($travelOrder->approver1_employee_id !== $employee->id) {
            abort(403, 'You are not authorized to approve this travel order.');
        }
        
        // Can only approve if not yet approved
        if ($travelOrder->president_approved) {
            return redirect()->back()
                ->with('error', 'This travel order has already been approved.');
        }
        
        // Mark as approved by approver 1 and update status
        // If there's an approver 2, the travel order now goes to them
        // Otherwise, it's fully approved
        $travelOrder->update([
            'president_approved' => true,
            'president_approved_at' => now(),
            'approver1_approved_at' => now(),
            'status' => $travelOrder->approver2_employee_id ? 'pending_approval_2' : 'approved',
        ]);
        
        return redirect()->route('employee.travel-orders.approvals.pending')
            ->with('success', 'Travel order approved successfully.');
    }

    /**
     * Reject a travel order.
     */
    public function reject(Request $request, TravelOrder $travelOrder)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        // Verify that the current user is the selected approver 1
        if ($travelOrder->approver1_employee_id !== $employee->id) {
            abort(403, 'You are not authorized to reject this travel order.');
        }
        
        // Can only reject if not yet approved
        if ($travelOrder->president_approved) {
            return redirect()->back()
                ->with('error', 'This travel order has already been approved and cannot be rejected.');
        }
        
        // Validate rejection reason (required)
        $validated = $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);
        
        // Mark as rejected with all details
        $travelOrder->update([
            'status' => 'cancelled',
            'rejection_reason' => $validated['remarks'],
            'rejected_by_employee_id' => $employee->id,
            'rejected_at' => now(),
        ]);
        
        return redirect()->route('employee.travel-orders.approvals.pending')
            ->with('success', 'Travel order rejected successfully.');
    }

    /**
     * Approve a travel order as approver 2.
     */
    public function sign(TravelOrder $travelOrder)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        // Verify that the current user is the selected approver 2
        if ($travelOrder->approver2_employee_id !== $employee->id) {
            abort(403, 'You are not authorized to approve this travel order.');
        }
        
        // Can only approve if already approved by approver 1
        if (!$travelOrder->president_approved) {
            return redirect()->back()
                ->with('error', 'This travel order has not been approved by Approver 1 yet.');
        }
        
        // Mark as approved by approver 2
        $travelOrder->update([
            'vp_approved' => true,
            'vp_approved_at' => now(),
            'approver2_approved_at' => now(),
            'status' => 'approved',
        ]);
        
        return redirect()->route('employee.travel-orders.approvals.pending')
            ->with('success', 'Travel order approved successfully.');
    }

    /**
     * Reject approval as approver 2.
     */
    public function rejectSign(Request $request, TravelOrder $travelOrder)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        // Verify that the current user is the selected approver 2
        if ($travelOrder->approver2_employee_id !== $employee->id) {
            abort(403, 'You are not authorized to reject this travel order.');
        }
        
        // Can only reject if already approved by approver 1 (and before approver 2 approves)
        if (!$travelOrder->approver1_approved_at) {
            return redirect()->back()
                ->with('error', 'This travel order has not been approved by Approver 1 yet.');
        }
        
        // Cannot reject if already approved by this approver
        if ($travelOrder->approver2_approved_at) {
            return redirect()->back()
                ->with('error', 'This travel order has already been approved and cannot be rejected.');
        }
        
        // Validate rejection reason (required)
        $validated = $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);
        
        // Mark as rejected with all details
        $travelOrder->update([
            'status' => 'cancelled',
            'rejection_reason' => $validated['remarks'],
            'rejected_by_employee_id' => $employee->id,
            'rejected_at' => now(),
        ]);
        
        return redirect()->route('employee.travel-orders.approvals.pending')
            ->with('success', 'Travel order approval rejected successfully.');
    }
}
