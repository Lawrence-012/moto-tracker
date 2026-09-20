<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TravelOrder;
use App\Models\Employee;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use DateTimeZone;

class MotorpoolAdminTravelOrderController extends Controller
{
    /**
     * Display a listing of all approved travel orders with tabs.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // Ensure the user is a motorpool admin
        if (!$user || $user->role !== 'motorpool_admin') {
            abort(403, 'Unauthorized access');
        }
        
        $search = $request->get('search', '');
        $tab = $request->get('tab', 'pending');
        
        // Build query based on tab
        $query = TravelOrder::with(['employee', 'employee.user']);
        
        switch ($tab) {
            case 'completed':
                $query->where('status', 'completed');
                break;
            case 'cancelled':
                // Show only travel orders that were fully approved by both approvers before being cancelled
                $query->where('status', 'cancelled')
                      ->whereNotNull('approver1_approved_at')
                      ->whereNotNull('approver2_approved_at');
                break;
            case 'pending':
            default:
                // Pending shows approved status (ready for motorpool)
                $query->where('status', 'approved');
                break;
        }
        
        // Sort by approval date for approved travel orders, otherwise by creation date
        if ($tab === 'pending') {
            // For approved tab, sort by the first approval timestamp (approver1_approved_at)
            $query->orderBy('approver1_approved_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }
            
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($employeeQuery) use ($search) {
                    $employeeQuery->where('first_name', 'LIKE', "%{$search}%")
                                 ->orWhere('last_name', 'LIKE', "%{$search}%")
                                 ->orWhere('full_name', 'LIKE', "%{$search}%");
                })->orWhere('destination', 'LIKE', "%{$search}%")
                  ->orWhere('purpose', 'LIKE', "%{$search}%");
            });
        }
        
        $travelOrders = $query->paginate(10)->appends(['search' => $search, 'tab' => $tab]);

        // Get all available positions for the create modal
        $positions = \App\Models\EmpPosition::with(['office', 'unit', 'division'])
            ->orderBy('position_name')
            ->get();

        // Get all active employees with their positions for the create modal
        $employees = \App\Models\Employee::with(['positions' => function($query) {
                $query->with(['office', 'unit', 'division', 'class']);
            }])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('motorpool-admin.travel-orders.index', compact('travelOrders', 'search', 'tab', 'positions', 'employees'));
    }

    /**
     * Store a newly created travel order by motorpool admin.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Ensure the user is a motorpool admin
        if (!$user || $user->role !== 'motorpool_admin') {
            abort(403, 'Unauthorized access');
        }
        
        // Validate employee exists
        $employee = Employee::find($request->employee_id);
        if (!$employee) {
            return redirect()->back()
                ->with('error', 'Selected employee not found.')
                ->withInput();
        }

        // Convert empty strings to null for optional fields before validation
        if (empty($request->university_president_approver)) {
            $request->merge(['university_president_approver' => null]);
        }
        if (empty($request->vp_approver)) {
            $request->merge(['vp_approver' => null]);
        }
        if (empty($request->division_head_approver)) {
            $request->merge(['division_head_approver' => null]);
        }
        if (empty($request->unit_head_approver)) {
            $request->merge(['unit_head_approver' => null]);
        }

        // Validate the request
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'destination' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'region' => 'nullable|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'date_from' => 'required|date|before_or_equal:date_to',
            'date_to' => 'required|date|after_or_equal:date_from',
            'departure_time' => 'nullable|string|max:20',
            'purpose' => 'required|string|max:500',
            'position_id' => 'required|exists:emp_positions,id',
            'auto_approved' => 'nullable|boolean',
            'university_president_approver' => 'nullable|exists:employees,id',
            'vp_approver' => 'nullable|exists:employees,id',
            'division_head_approver' => 'nullable|exists:employees,id',
            'unit_head_approver' => 'nullable|exists:employees,id',
        ], [], [
            'employee_id' => 'employee',
            'destination' => 'destination',
            'date_from' => 'from date',
            'date_to' => 'to date',
            'departure_time' => 'departure time',
            'purpose' => 'purpose',
            'position_id' => 'position',
            'auto_approved' => 'auto approval',
            'university_president_approver' => 'University President approver',
            'vp_approver' => 'VP approver',
            'division_head_approver' => 'Division Head approver',
            'unit_head_approver' => 'Unit Head approver',
        ]);
        
        if ($validator->fails()) {
            // Get all error messages for debugging
            $errors = $validator->errors()->all();
            \Log::error('Validation errors:', $errors);
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Check if auto_approved is checked
        $isAutoApproved = $request->boolean('auto_approved');
        
        // Process approvers only if NOT auto-approved
        $approver1 = null;
        $approver2 = null;
        
        if (!$isAutoApproved) {
            // Collect all selected approvers
            $selectedApprovers = [];
            
            if (!empty($request->university_president_approver)) {
                $selectedApprovers[] = [
                    'employee_id' => $request->university_president_approver,
                    'rank' => $this->getOfficerRankForAssignment($request->university_president_approver)
                ];
            }
            
            if (!empty($request->vp_approver)) {
                $selectedApprovers[] = [
                    'employee_id' => $request->vp_approver,
                    'rank' => $this->getOfficerRankForAssignment($request->vp_approver)
                ];
            }
            
            if (!empty($request->division_head_approver)) {
                $selectedApprovers[] = [
                    'employee_id' => $request->division_head_approver,
                    'rank' => $this->getOfficerRankForAssignment($request->division_head_approver)
                ];
            }
            
            if (!empty($request->unit_head_approver)) {
                $selectedApprovers[] = [
                    'employee_id' => $request->unit_head_approver,
                    'rank' => $this->getOfficerRankForAssignment($request->unit_head_approver)
                ];
            }
            
            // Sort by rank
            usort($selectedApprovers, function($a, $b) {
                return $a['rank'] <=> $b['rank'];
            });
            
            // Assign approvers
            if (count($selectedApprovers) === 1) {
                $approver2 = $selectedApprovers[0]['employee_id'];
                $approver1 = null;
            } elseif (count($selectedApprovers) >= 2) {
                $approver2 = $selectedApprovers[0]['employee_id'];
                $approver1 = $selectedApprovers[1]['employee_id'];
            }
            
            // Validate at least 1 approver when not auto-approved
            if (empty($approver1) && empty($approver2)) {
                return redirect()->back()
                    ->with('error', 'Please select at least 1 approver (VP, Division Head, or Unit Head).')
                    ->withInput();
            }
            
            // Count total selected approvers
            $selectedCount = 0;
            if (!empty($request->university_president_approver)) $selectedCount++;
            if (!empty($request->vp_approver)) $selectedCount++;
            if (!empty($request->division_head_approver)) $selectedCount++;
            if (!empty($request->unit_head_approver)) $selectedCount++;
            
            if ($selectedCount > 2) {
                return redirect()->back()
                    ->with('error', 'You can only select a maximum of 2 approvers.')
                    ->withInput();
            }
        }

        // Verify that the selected position belongs to the employee
        $position = $employee->positions()->where('id', $request->position_id)->first();
        if (!$position) {
            return redirect()->back()
                ->with('error', 'Invalid position selected.')
                ->withInput();
        }

        // Process departure_time
        $departureTime = null;
        if (!empty($request->departure_time)) {
            $cleanTime = trim($request->departure_time);
            if (preg_match('/([01]?[0-9]|2[0-3]):([0-5][0-9])/', $cleanTime, $matches)) {
                $departureTime = $matches[1] . ':' . $matches[2];
            } elseif (preg_match('/([01]?[0-9]|2[0-3])[.:]?([0-5][0-9])/', $cleanTime, $matches)) {
                $departureTime = sprintf('%02d:%02d', $matches[1], $matches[2]);
            }
        }

        // Create the travel order
        $status = $isAutoApproved ? 'approved' : 'pending';
        \Log::info('Creating travel order', [
            'employee_id' => $employee->id,
            'position_id' => $request->position_id,
            'auto_approved' => $isAutoApproved,
            'status' => $status
        ]);
        
        $travelOrder = TravelOrder::create([
            'employee_id' => $employee->id,
            'emp_position_id' => $request->position_id,
            'destination' => $request->destination,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'region' => $request->region,
            'distance_km' => $request->distance_km,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'departure_time' => $departureTime,
            'purpose' => $request->purpose,
            'status' => $status,
            'approver1_employee_id' => $approver1,
            'approver2_employee_id' => $approver2,
        ]);
        
        // If auto-approved, set approval timestamps
        if ($isAutoApproved) {
            // Set both approver timestamps to now (or use a specific approver if needed)
            $travelOrder->update([
                'approver1_approved_at' => now(),
                'approver2_approved_at' => now(),
            ]);
        }

        return redirect()->route('motorpool.travel-orders.index', ['tab' => 'pending'])
            ->with('success', 'Travel order created successfully!' . ($isAutoApproved ? ' Auto-approved and sent to Motorpool.' : ' Sent for approval.'));
    }
    
    /**
     * Get officer rank for assignment (lower rank approves first)
     */
    private function getOfficerRankForAssignment($employeeId)
    {
        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->officer) {
            return 999; // Default high rank if no officer record
        }
        
        $officer = $employee->officer;
        
        // Rank: President (4) > VP (3) > Division Head (2) > Unit Head (1)
        if ($officer->president) return 4;
        if ($officer->vp) return 3;
        if ($officer->division_head) return 2;
        if ($officer->unit_head) return 1;
        
        return 999;
    }

    /**
     * Display the specified travel order.
     */
    public function show(TravelOrder $travelOrder): View
    {
        $user = Auth::user();
        
        // Ensure the user is a motorpool admin
        if (!$user || $user->role !== 'motorpool_admin') {
            abort(403, 'Unauthorized access');
        }
        
        $travelOrder->load(['employee.user', 'position', 'rejectedBy']);
        
        return view('motorpool-admin.travel-orders.show', compact('travelOrder'));
    }
    

    
    /**
     * Get employee role (Regular, Unit Head, Division Head, VP, President)
     */
    private function getEmployeeRole($employee)
    {
        if ($employee->is_president) {
            return 'PRESIDENT';
        }
        if ($employee->is_vp) {
            return 'VP';
        }
        if ($employee->is_divisionhead) {
            return 'DIVISION_HEAD';
        }
        if ($employee->is_head) {
            return 'UNIT_HEAD';
        }
        return 'REGULAR';
    }
    
    /**
     * Get approval workflow for an employee based on their role
     */
    private function getApprovalWorkflow($employee)
    {
        $role = $this->getEmployeeRole($employee);
        
        switch ($role) {
            case 'REGULAR':
                // Regular employee: approved by Unit Head & Division Head
                return [
                    'first_approver' => 'UNIT_HEAD',
                    'second_approver' => 'DIVISION_HEAD'
                ];
                
            case 'UNIT_HEAD':
                // Unit Head: approved by Division Head and VP (President if no VP)
                return [
                    'first_approver' => 'DIVISION_HEAD',
                    'second_approver' => 'VP_OR_PRESIDENT'
                ];
                
            case 'DIVISION_HEAD':
                // Division Head: approved by VP & President (if no VP then only President)
                return [
                    'first_approver' => 'VP',
                    'second_approver' => 'PRESIDENT'
                ];
                
            case 'VP':
                // VP: approved by President
                return [
                    'first_approver' => 'PRESIDENT',
                    'second_approver' => null
                ];
                
            case 'PRESIDENT':
                // President: no approval needed
                return [
                    'first_approver' => null,
                    'second_approver' => null
                ];
                
            default:
                return [
                    'first_approver' => null,
                    'second_approver' => null
                ];
        }
    }
    
    /**
     * Get the actual approver employee based on role and position
     */
    private function getApproverEmployee($approverRole, $employeePosition)
    {
        switch ($approverRole) {
            case 'UNIT_HEAD':
                // Find Unit Head in the same unit (excluding the requester)
                $unitHead = \App\Models\Employee::whereHas('officer', function($query) {
                    $query->where('unit_head', true);
                })
                ->whereHas('positions', function($query) use ($employeePosition) {
                    $query->where('unit_id', $employeePosition->unit_id)
                          ->where('employee_id', '!=', $employeePosition->employee_id);
                })->first();
                
                // If no Unit Head found in the same unit, try to find any Unit Head
                if (!$unitHead) {
                    $unitHead = \App\Models\Employee::whereHas('officer', function($query) {
                        $query->where('unit_head', true);
                    })->first();
                }
                
                return $unitHead;
                
            case 'DIVISION_HEAD':
                // Find Division Head in the same division - query officers table only
                return \App\Models\Employee::whereHas('officer', function($query) use ($employeePosition) {
                    $query->where('division_head', true);
                })->whereHas('positions', function($query) use ($employeePosition) {
                    $query->where('division_id', $employeePosition->division_id);
                })->first();
                
            case 'VP':
                // Find VP (check if President's Office first)
                if ($employeePosition && $employeePosition->office) {
                    if (stripos($employeePosition->office->office_name, 'President') !== false) {
                        // President's Office - no VP, return null
                        return null;
                    }
                }
                // Find any VP
                return \App\Models\Employee::whereHas('officer', function($query) {
                    $query->where('vp', true);
                })->first();
                
            case 'PRESIDENT':
                // Find President
                return \App\Models\Employee::whereHas('officer', function($query) {
                    $query->where('president', true);
                })->first();
                
            case 'VP_OR_PRESIDENT':
                // Special case: VP if not President's Office, otherwise President
                if ($employeePosition && $employeePosition->office) {
                    if (stripos($employeePosition->office->office_name, 'President') !== false) {
                        // President's Office - go directly to President
                        return \App\Models\Employee::whereHas('officer', function($query) {
                            $query->where('president', true);
                        })->first();
                    }
                }
                // Regular office - find VP
                return \App\Models\Employee::whereHas('officer', function($query) {
                    $query->where('vp', true);
                })->first();
                
            default:
                return null;
        }
    }
    
    /**
     * Get supervisor information based on employee's position
     */
    private function getSupervisorInfo($employee)
    {
        $name = '';
        $position = '';
        
        // If employee is President - no supervisor
        if ($employee->is_president) {
            return ['name' => 'N/A', 'position' => 'N/A'];
        }
        
        // If employee is VP - supervisor is President
        if ($employee->is_vp) {
            $president = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('president', true);
            })->first();
            if ($president) {
                $name = $president->first_name . ' ' . $president->last_name;
                $position = $president->position ? $president->position->position_name : 'President';
            }
            return ['name' => $name, 'position' => $position];
        }
        
        // If employee is Division Head - supervisor is VP
        if ($employee->is_divisionhead) {
            $vp = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('vp', true);
            })->first();
            if ($vp) {
                $name = $vp->first_name . ' ' . $vp->last_name;
                $position = $vp->position ? $vp->position->position_name : 'Vice President';
            }
            return ['name' => $name, 'position' => $position];
        }
        
        // If employee is Unit Head - supervisor is Division Head
        if ($employee->is_head) {
            // Try to find division head in the same division
            $divisionHead = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('division_head', true);
            })
                ->whereHas('positions', function($query) use ($employee) {
                    $query->where('division_id', $employee->getDivisionIdAttribute());
                })->first();
            
            if ($divisionHead) {
                $name = $divisionHead->first_name . ' ' . $divisionHead->last_name;
                $position = $divisionHead->position ? $divisionHead->position->position_name : 'Division Head';
            } else {
                // Fallback to any division head
                $divisionHead = \App\Models\Employee::whereHas('officer', function($query) {
                    $query->where('division_head', true);
                })->first();
                if ($divisionHead) {
                    $name = $divisionHead->first_name . ' ' . $divisionHead->last_name;
                    $position = $divisionHead->position ? $divisionHead->position->position_name : 'Division Head';
                }
            }
            return ['name' => $name, 'position' => $position];
        }
        
        // For regular employees - supervisor is Unit Head
        $unitHead = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('unit_head', true);
            })
            ->whereHas('positions', function($query) use ($employee) {
                $query->where('unit_id', $employee->getUnitIdAttribute());
            })->first();
        
        if ($unitHead) {
            $name = $unitHead->first_name . ' ' . $unitHead->last_name;
            $position = $unitHead->position ? $unitHead->position->position_name : 'Unit Head';
        } else {
            // Fallback to any unit head
            $unitHead = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('unit_head', true);
            })->first();
            if ($unitHead) {
                $name = $unitHead->first_name . ' ' . $unitHead->last_name;
                $position = $unitHead->position ? $unitHead->position->position_name : 'Unit Head';
            }
        }
        
        return ['name' => $name, 'position' => $position];
    }
    
    /**
     * Generate PDF for travel order - matches the employee's PDF exactly
     */
    public function generateExcel(TravelOrder $travelOrder): Response
    {
        $user = Auth::user();
        
        // Ensure the user is a motorpool admin
        if (!$user || $user->role !== 'motorpool_admin') {
            abort(403, 'Unauthorized access');
        }
        
        // Load the travel order with employee and position
        $travelOrder->load(['employee', 'position']);
        
        if (!$travelOrder->employee) {
            abort(403, 'Travel order has no employee record');
        }
        
        $employee = $travelOrder->employee;
        
        // Determine which controller to use based on employee's role
        if ($employee->is_president) {
            // President's travel order - use regular employee controller as fallback
            return $this->delegateToRegularEmployeeController($travelOrder);
        } elseif ($employee->is_vp) {
            // VP's travel order - use VP approval controller
            return $this->delegateToVPController($travelOrder);
        } elseif ($employee->is_divisionhead) {
            // Division Head's travel order - use division head controller
            return $this->delegateToDivisionHeadController($travelOrder);
        } elseif ($employee->is_head) {
            // Unit Head's travel order - use unit head controller
            return $this->delegateToUnitHeadController($travelOrder);
        } else {
            // Regular employee - use regular employee controller
            return $this->delegateToRegularEmployeeController($travelOrder);
        }
    }
    
    /**
     * Delegate to RegularEmployeeTravelOrderController
     */
    private function delegateToRegularEmployeeController(TravelOrder $travelOrder): Response
    {
        $controller = new \App\Http\Controllers\RegularEmployeeTravelOrderController();
        return $controller->generateExcel($travelOrder);
    }
    
    /**
     * Delegate to UnitHeadOwnTravelOrderController
     */
    private function delegateToUnitHeadController(TravelOrder $travelOrder): Response
    {
        $controller = new \App\Http\Controllers\UnitHeadOwnTravelOrderController();
        return $controller->generateExcel($travelOrder);
    }
    
    /**
     * Delegate to DivisionHeadOwnTravelOrderController
     */
    private function delegateToDivisionHeadController(TravelOrder $travelOrder): Response
    {
        $controller = new \App\Http\Controllers\DivisionHeadOwnTravelOrderController();
        return $controller->generateExcel($travelOrder);
    }
    
    /**
     * Delegate to VpTravelOrderController (for VP approval context)
     */
    private function delegateToVPController(TravelOrder $travelOrder): Response
    {
        $controller = new \App\Http\Controllers\VpTravelOrderController();
        return $controller->generateExcel($travelOrder);
    }
}