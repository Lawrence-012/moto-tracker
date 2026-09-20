<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\TravelOrder;
use App\Models\Employee;
use App\Models\Officer;
use App\Models\Unit;
use App\Services\ApprovalHierarchy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use DateTimeZone;

class RegularEmployeeTravelOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record. Please contact your administrator to set up your employee profile.');
        }
        
        // Get the tab parameter, default to 'draft'
        $tab = $request->get('tab', 'draft');
        
        // If user is President and trying to access pending tab, redirect to draft
        if ($employee->officer && $employee->officer->president && $tab === 'pending') {
            return redirect()->route('travel-orders.index', ['tab' => 'draft'])
                ->with('info', 'The Pending tab is not available for President users.');
        }
        
        // Get search term if provided
        $search = $request->get('search', '');
        
        // Get travel orders for this employee based on the selected tab
        $query = TravelOrder::where('employee_id', $employee->id);
        
        // Apply search filter if provided
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('destination', 'LIKE', "%{$search}%")
                  ->orWhere('purpose', 'LIKE', "%{$search}%")
                  ->orWhere('status', 'LIKE', "%{$search}%")
                  ->orWhere(function($dateQuery) use ($search) {
                      // Try to parse the search term as a date
                      $parsedDate = date('Y-m-d', strtotime($search));
                      if ($parsedDate && $parsedDate !== '1970-01-01') {
                          $dateQuery->whereDate('date_from', '=', $parsedDate)
                                    ->orWhereDate('date_to', '=', $parsedDate);
                      }
                      
                      // Also search by month name (e.g., "April", "March")
                      $monthNames = [
                          'january' => '01', 'february' => '02', 'march' => '03',
                          'april' => '04', 'may' => '05', 'june' => '06',
                          'july' => '07', 'august' => '08', 'september' => '09',
                          'october' => '10', 'november' => '11', 'december' => '12'
                      ];
                      $monthAbbrs = [
                          'jan' => '01', 'feb' => '02', 'mar' => '03',
                          'apr' => '04', 'jun' => '06', 'jul' => '07',
                          'aug' => '08', 'sep' => '09', 'oct' => '10',
                          'nov' => '11', 'dec' => '12'
                      ];
                      
                      $searchLower = strtolower(trim($search));
                      
                      // Check if search is a month name
                      if (isset($monthNames[$searchLower])) {
                          $month = $monthNames[$searchLower];
                          $dateQuery->whereRaw("MONTH(date_from) = ?", [$month])
                                    ->orWhereRaw("MONTH(date_to) = ?", [$month]);
                      }
                      // Check if search is a month abbreviation
                      elseif (isset($monthAbbrs[$searchLower])) {
                          $month = $monthAbbrs[$searchLower];
                          $dateQuery->whereRaw("MONTH(date_from) = ?", [$month])
                                    ->orWhereRaw("MONTH(date_to) = ?", [$month]);
                      }
                  });
            });
        }
        
        switch ($tab) {
            case 'approved':
                // Show travel orders that are fully approved
                $query->where('status', 'approved');
                break;
            case 'completed':
                // Travel orders marked as completed OR with completed trip tickets
                $query->where(function($q) {
                    $q->where('status', 'completed')  // Manual status update (legacy)
                      ->orWhere(function($q2) {
                          $q2->where('status', 'approved')
                             ->whereHas('itinerary.tripTickets', function($q3) {
                                 $q3->where('status', 'Completed');
                             });
                      });
                });
                break;
            case 'cancelled':
                $query->where('status', 'cancelled');
                break;
            case 'draft':
                // Show draft travel orders (not yet submitted for approval)
                $query->where('status', 'draft');
                break;
            case 'pending':
            default:
                // Show travel orders that are still pending approval
                $query->where('status', 'pending');
                break;
        }
        
        // Get paginated results with position information and preserve parameters
        $travelOrders = $query->with(['position', 'itinerary.tripTickets'])->orderBy('created_at', 'desc')->paginate(10)->appends(['tab' => $tab, 'search' => $search]);
        
        // Return JSON response for AJAX requests (table-search.js)
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'table_body' => view('travel-orders.partials.' . ($tab === 'draft' ? 'draft-rows' : 'table-rows'), [
                    'travelOrders' => $travelOrders,
                    'tab' => $tab
                ])->render(),
                'pagination' => $travelOrders->links()->toHtml(),
            ]);
        }
        
        // Get all positions for the employee (for the create modal)
        $positions = $employee->positions()->with(['office', 'division', 'unit', 'subunit', 'class'])->get();
        
        // Get ALL officers with any role (VP, Division Head, Unit Head, President)
        $officerEmployees = Employee::whereHas('officer', function($query) {
            $query->where(function($q) {
                $q->where('unit_head', true)
                  ->orWhere('division_head', true)
                  ->orWhere('vp', true)
                  ->orWhere('president', true);
            });
        })
        ->with(['positions.office', 'positions.division', 'positions.unit', 'user', 'officer'])
        ->get();
        
        // Get all active units to identify Transportation Services
        $units = Unit::where('unit_isactive', true)->get(['id', 'unit_name']);
        
        $officers = [];
        foreach ($officerEmployees as $officerEmployee) {
            $officerRecord = $officerEmployee->officer;
            if ($officerRecord && $officerRecord->hasAnyRole()) {
                // Get the office_id DIRECTLY from the officers table
                $officerOfficeId = $officerRecord->office_id;
                
                // Build officer_offices array based on role flags and the office_id
                $officerOffices = [];
                
                if ($officerRecord->president && $officerOfficeId) {
                    $officerOffices['president'][] = $officerOfficeId;
                }
                if ($officerRecord->vp && $officerOfficeId) {
                    $officerOffices['vp'][] = $officerOfficeId;
                }
                if ($officerRecord->division_head && $officerOfficeId) {
                    $officerOffices['division_head'][] = $officerOfficeId;
                }
                if ($officerRecord->unit_head && $officerOfficeId) {
                    $officerOffices['unit_head'][] = $officerOfficeId;
                }
                
                $officers[] = [
                    'employee' => [
                        'id' => $officerEmployee->id,
                        'first_name' => $officerEmployee->first_name,
                        'last_name' => $officerEmployee->last_name,
                        'positions' => $officerEmployee->positions->map(function($pos) {
                            return [
                                'id' => $pos->id,
                                'office_id' => $pos->office_id,
                                'division_id' => $pos->division_id,
                                'unit_id' => $pos->unit_id,
                                'position_name' => $pos->position_name,
                                'is_vp' => (bool)$pos->is_vp,
                                'is_division_head' => (bool)$pos->is_division_head,
                                'is_unit_head' => (bool)$pos->is_unit_head,
                            ];
                        }),
                    ],
                    'officer' => [
                        'unit_head' => (bool)$officerRecord->unit_head,
                        'division_head' => (bool)$officerRecord->division_head,
                        'vp' => (bool)$officerRecord->vp,
                        'president' => (bool)$officerRecord->president,
                        'primary_role' => $officerRecord->getPrimaryRole(),
                        'role_names' => $officerRecord->getRoleNames(),
                        'office_id' => $officerOfficeId, // FROM OFFICERS TABLE
                        'officer_offices' => $officerOffices, // Offices where they hold officer roles
                    ]
                ];
            }
        }
        
        return view('travel-orders.index', compact('travelOrders', 'tab', 'search', 'positions', 'officers', 'units'));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        if (!$employee) {
            abort(403, 'You do not have an employee record.');
        }
        
        // Get all positions for the employee
        $positions = $employee->positions()->with(['office', 'division', 'unit', 'subunit', 'class'])->get();
        
        // Get all officers with any role (VP, Division Head, Unit Head, President)
        $officerEmployees = Employee::whereHas('officer', function($query) {
            $query->where(function($q) {
                $q->where('unit_head', true)
                  ->orWhere('division_head', true)
                  ->orWhere('vp', true)
                  ->orWhere('president', true);
            });
        })
        ->with(['positions.office', 'positions.division', 'positions.unit', 'user', 'officer'])
        ->get();
        
        // Get all active units to identify Transportation Services
        $units = Unit::where('unit_isactive', true)->get(['id', 'unit_name']);
        
        $officers = [];
        foreach ($officerEmployees as $officerEmployee) {
            $officerRecord = $officerEmployee->officer;
            if ($officerRecord && $officerRecord->hasAnyRole()) {
                // Get the primary office for this officer based on their positions
                $primaryOfficeId = null;
                if ($officerRecord->president) {
                    // For President, use Office ID 1 (Office of the University President)
                    $presidentsOffice = \App\Models\Office::where('office_name', 'Office of the University President')->first();
                    $primaryOfficeId = $presidentsOffice ? $presidentsOffice->id : 1;
                } else {
                    // For other officers, find office based on their role
                    $primaryOfficeId = $officerEmployee->positions->firstWhere(function($pos) use ($officerRecord) {
                        if ($officerRecord->vp && $pos->is_vp) return true;
                        if ($officerRecord->division_head && $pos->is_division_head) return true;
                        if ($officerRecord->unit_head && $pos->is_unit_head) return true;
                        return false;
                    })?->office_id;
                }
                
                $officers[] = [
                    'employee' => [
                        'id' => $officerEmployee->id,
                        'last_name' => $officerEmployee->last_name,
                        'first_name' => $officerEmployee->first_name,
                        'office_id' => $primaryOfficeId, 
                        'positions' => $officerEmployee->positions->map(function($pos) {
                            return [
                                'id' => $pos->id,
                                'office_id' => $pos->office_id,
                                'division_id' => $pos->division_id,
                                'unit_id' => $pos->unit_id,
                                'position_name' => $pos->position_name,
                            ];
                        }),
                    ],
                    'officer' => [
                        'unit_head' => (bool)$officerRecord->unit_head,
                        'division_head' => (bool)$officerRecord->division_head,
                        'vp' => (bool)$officerRecord->vp,
                        'president' => (bool)$officerRecord->president,
                        'primary_role' => $officerRecord->getPrimaryRole(),
                        'role_names' => $officerRecord->getRoleNames(),
                        'office_id' => $primaryOfficeId, 
                    ]
                ];
            }
        }
        
        return view('travel-orders.create', compact('positions', 'officers', 'units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have an employee record. Please contact your administrator.'
                ], 403);
            }
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record. Please contact your administrator to set up your employee profile.');
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

        // Validate the request with custom messages
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
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
            'university_president_approver' => 'nullable|exists:employees,id',
            'vp_approver' => 'nullable|exists:employees,id',
            'division_head_approver' => 'nullable|exists:employees,id',
            'unit_head_approver' => 'nullable|exists:employees,id',
        ], [], [
            'destination' => 'destination',
            'date_from' => 'from date',
            'date_to' => 'to date',
            'departure_time' => 'departure time',
            'purpose' => 'purpose',
            'position_id' => 'position',
            'university_president_approver' => 'University President approver',
            'vp_approver' => 'VP approver',
            'division_head_approver' => 'Division Head approver',
            'unit_head_approver' => 'Unit Head approver',
        ]);
        
        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Process approvers from University President, VP, Division Head, and Unit Head dropdowns
        // Collect all selected approvers in order of selection
        $selectedApprovers = [];
        
        if (!empty($request->university_president_approver)) {
            $selectedApprovers[] = [
                'employee_id' => $request->university_president_approver,
                'rank' => self::getOfficerRankForAssignment($request->university_president_approver)
            ];
        }
        
        if (!empty($request->vp_approver)) {
            $selectedApprovers[] = [
                'employee_id' => $request->vp_approver,
                'rank' => self::getOfficerRankForAssignment($request->vp_approver)
            ];
        }
        
        if (!empty($request->division_head_approver)) {
            $selectedApprovers[] = [
                'employee_id' => $request->division_head_approver,
                'rank' => self::getOfficerRankForAssignment($request->division_head_approver)
            ];
        }
        
        if (!empty($request->unit_head_approver)) {
            $selectedApprovers[] = [
                'employee_id' => $request->unit_head_approver,
                'rank' => self::getOfficerRankForAssignment($request->unit_head_approver)
            ];
        }
        
        // Sort by rank (ASCENDING - lower rank first, then higher rank)
        // Lower rank approves FIRST (approver2), Higher rank approves SECOND (approver1)
        usort($selectedApprovers, function($a, $b) {
            return $a['rank'] <=> $b['rank'];  // Lower rank first
        });
        
        // Assign to approver1 and approver2 based on number of selected approvers
        if (count($selectedApprovers) === 1) {
            // Single approver - assign to approver2 (appears in I45/I46)
            $approver2 = $selectedApprovers[0]['employee_id'];
            $approver1 = null;
        } else {
            // Two approvers - assign by rank (lower rank first as approver2, higher rank second as approver1)
            $approver2 = $selectedApprovers[0]['employee_id'];  // Lower rank - approves FIRST
            $approver1 = $selectedApprovers[1]['employee_id'];  // Higher rank - approves SECOND
        }
        
        if (empty($approver1) && empty($approver2)) {
            return redirect()->back()
                ->with('error', 'Please select at least 1 approver (VP, Division Head, or Unit Head).')
                ->withInput();
        }
        
        // Count total selected approvers from the four dropdowns
        $presidentApprover = $request->university_president_approver;
        $vpApprover = $request->vp_approver;
        $divisionHeadApprover = $request->division_head_approver;
        $unitHeadApprover = $request->unit_head_approver;
        
        $selectedCount = 0;
        if (!empty($presidentApprover)) $selectedCount++;
        if (!empty($vpApprover)) $selectedCount++;
        if (!empty($divisionHeadApprover)) $selectedCount++;
        if (!empty($unitHeadApprover)) $selectedCount++;
        
        if ($selectedCount > 2) {
            return redirect()->back()
                ->with('error', 'You can only select a maximum of 2 approvers. Please remove one selection.')
                ->withInput();
        }

        // Verify that the selected position belongs to the employee
        $position = $employee->positions()->where('id', $request->position_id)->first();
        if (!$position) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid position selected.'
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'Invalid position selected.')
                ->withInput();
        }

        // The approvers must OUTRANK the requester (e.g. a Unit Head can only be approved by a
        // Division Head, VP or President - never by another Unit Head or by themselves).
        $approverError = ApprovalHierarchy::validateApprovers($employee, $position, array_column($selectedApprovers, 'employee_id'));
        if ($approverError) {
            return $this->approverRankError($request, $approverError);
        }

        // Process departure_time to ensure it's in the correct format or null
        $departureTime = null;
        if (!empty($request->departure_time)) {
            // Trim whitespace and normalize the value
            $cleanTime = trim($request->departure_time);
            
            if (preg_match('/([01]?[0-9]|2[0-3]):([0-5][0-9])/', $cleanTime, $matches)) {
                $departureTime = $matches[1] . ':' . $matches[2];
            } elseif (preg_match('/([01]?[0-9]|2[0-3])[.:]?([0-5][0-9])/', $cleanTime, $matches)) {
                // Handle cases where it might be formatted differently
                $departureTime = sprintf('%02d:%02d', $matches[1], $matches[2]);
            }
        }

        // Create the travel order as DRAFT (not visible to approvers yet)
        $travelOrder= TravelOrder::create([
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
            'status' => 'draft',
            'approver1_employee_id' => $approver1,
            'approver2_employee_id' => $approver2,
        ]);

        // Return JSON response for AJAX requests
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Travel order created successfully.',
                'data' => [
                    'id' => $travelOrder->id,
                    'destination' => $travelOrder->destination,
                    'date_from' => $travelOrder->date_from,
                    'date_to' => $travelOrder->date_to,
                ],
                'redirect' => route('travel-orders.index', ['tab' => 'draft'])
            ]);
        }

        return redirect()->route('travel-orders.index', ['tab' => 'draft'])
            ->with('success', 'Travel order created successfully.');
    }

    /**
     * Submit a draft travel order for approval.
     */
    public function submitForApproval(TravelOrder $travelOrder)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        // Ensure the employee can only submit their own drafts
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only drafts can be submitted
        if ($travelOrder->status !== 'draft') {
            return redirect()->route('travel-orders.index')
                ->with('error', 'This travel order is not a draft.');
        }
        
        // Validate that at least one approver is selected (approver1 OR approver2)
        if (!$travelOrder->approver1_employee_id && !$travelOrder->approver2_employee_id) {
            return redirect()->back()
                ->with('error', 'Please select an approver before submitting.');
        }
        
        // Approvers saved on the draft must still outrank the requester. Drafts created before
        // this rule existed may hold a peer/lower approver and have to be corrected first.
        $approverError = ApprovalHierarchy::validateApprovers(
            $employee,
            $travelOrder->position,
            [$travelOrder->approver1_employee_id, $travelOrder->approver2_employee_id]
        );
        if ($approverError) {
            return redirect()->back()
                ->with('error', $approverError . ' Please edit this draft and select a valid approver before submitting.');
        }
        
        // Update status to pending (now visible to approvers)
        $travelOrder->update([
            'status' => 'pending',
        ]);
        
        return redirect()->route('travel-orders.index', ['tab' => 'pending']);
    }

    /**
     * Approve a travel order (for selected approver).
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
        
        // Verify that the current user is either approver1 or approver2
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        
        if (!$isApprover1 && !$isApprover2) {
            abort(403, 'You are not authorized to approve this travel order.');
        }
        
        // Determine which approver role the user has and check if they can approve
        if ($isApprover2) {
            // Lower rank approver (approves FIRST)
            if ($travelOrder->approver2_approved_at) {
                return redirect()->back()
                    ->with('error', 'This travel order has already been approved.');
            }
            
            // Mark as approved by approver2 (first to approve)
            // After approver2 approves, it goes to approver1 (if exists)
            $travelOrder->update([
                'approver2_approved_at' => now(),
                'status' => $travelOrder->approver1_employee_id ? 'pending' : 'approved',
            ]);
        } elseif ($isApprover1) {
            // Higher rank approver (approves SECOND)
            if ($travelOrder->approver1_approved_at) {
                return redirect()->back()
                    ->with('error', 'This travel order has already been approved.');
            }
            
            // Can only approve after approver2 has approved (if approver2 exists)
            if ($travelOrder->approver2_employee_id && !$travelOrder->approver2_approved_at) {
                return redirect()->back()
                    ->with('error', 'This travel order must be approved by the lower-rank approver first.');
            }
            
            // Mark as approved by approver1 (second to approve) and set status to approved
            $travelOrder->update([
                'approver1_approved_at' => now(),
                'status' => 'approved',
            ]);
        }
        
        return redirect()->back()
            ->with('success', 'Travel order approved successfully.');
    }

    /**
     * Reject a travel order (for selected approver).
     */
    public function reject(TravelOrder $travelOrder, Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        // Verify that the current user is either approver1 or approver2
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        
        if (!$isApprover1 && !$isApprover2) {
            abort(403, 'You are not authorized to reject this travel order.');
        }
        
        // Determine which approver role the user has
        if ($isApprover2) {
            // Lower rank approver - can reject at any time before they approve
            if ($travelOrder->approver2_approved_at) {
                return redirect()->back()
                    ->with('error', 'This travel order has already been approved and cannot be rejected.');
            }
        } elseif ($isApprover1) {
            // Higher rank approver - can only reject after approver2 has approved
            if ($travelOrder->approver2_employee_id && !$travelOrder->approver2_approved_at) {
                return redirect()->back()
                    ->with('error', 'This travel order must be approved by the lower-rank approver first.');
            }
            if ($travelOrder->approver1_approved_at) {
                return redirect()->back()
                    ->with('error', 'This travel order has already been approved and cannot be rejected.');
            }
        }
        
        // Validate remarks (required)
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
        
        return redirect()->back()
            ->with('success', 'Travel order rejected successfully.');
    }

    /**
     * Sign a travel order (for selected signer).
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
        
        // Verify that the current user is the selected signer
        if ($travelOrder->approver2_employee_id !== $employee->id) {
            abort(403, 'You are not authorized to sign this travel order.');
        }
        
        // Can only sign if already approved by approver1
        if (!$travelOrder->approver1_approved_at) {
            return redirect()->back()
                ->with('error', 'This travel order has not been approved yet.');
        }
        
        // Mark as signed by approver2
        $travelOrder->update([
            'approver2_approved_at' => now(),
            'status' => 'approved',
        ]);
        
        return redirect()->back()
            ->with('success', 'Travel order signed successfully.');
    }

    /**
     * Reject signing a travel order (for selected signer).
     */
    public function rejectSign(TravelOrder $travelOrder, Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        // Verify that the current user is the selected signer
        if ($travelOrder->approver2_employee_id !== $employee->id) {
            abort(403, 'You are not authorized to reject signing this travel order.');
        }
        
        // Can only reject if already approved by approver1
        if (!$travelOrder->approver1_approved_at) {
            return redirect()->back()
                ->with('error', 'This travel order has not been approved yet.');
        }
        
        // Validate remarks (required)
        $validated = $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);
        
        // Mark as rejected at signing stage
        $travelOrder->update([
            'status' => 'cancelled',
            'remarks' => $validated['remarks'],
        ]);
        
        return redirect()->back()
            ->with('success', 'Travel order signing rejected successfully.');
    }

    /**
     * Delete a draft travel order.
     */
    public function deleteDraft(TravelOrder $travelOrder)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        // Ensure the employee can only delete their own drafts
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only drafts can be deleted
        if ($travelOrder->status !== 'draft') {
            return redirect()->route('travel-orders.index')
                ->with('error', 'This travel order cannot be deleted.');
        }
        
        $travelOrder->delete();
        
        return redirect()->route('travel-orders.index', ['tab' => 'draft']);
    }

    /**
     * Display the specified resource.
     */
    public function show(TravelOrder $travelOrder)
    {
        // Ensure the employee can only view their own travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record. Please contact your administrator to set up your employee profile.');
        }
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Load the travel order with position relationship
        $travelOrder->load(['position', 'employee']);
        
        return view('travel-orders.show', compact('travelOrder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TravelOrder $travelOrder)
    {
        // Ensure the employee can only edit their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record. Please contact your administrator to set up your employee profile.');
        }
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow editing if not yet approved
        if ($travelOrder->approver1_approved_at || $travelOrder->approver2_approved_at) {
            abort(403);
        }
        
        // Return JSON data for the modal
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $travelOrder->id,
                'emp_position_id' => $travelOrder->emp_position_id,
                'destination' => $travelOrder->destination,
                'date_from' => $travelOrder->date_from instanceof \Illuminate\Support\Carbon ? $travelOrder->date_from->format('Y-m-d') : \Carbon\Carbon::parse($travelOrder->date_from)->format('Y-m-d'),
                'date_to' => $travelOrder->date_to instanceof \Illuminate\Support\Carbon ? $travelOrder->date_to->format('Y-m-d') : \Carbon\Carbon::parse($travelOrder->date_to)->format('Y-m-d'),
                'departure_time' => is_string($travelOrder->departure_time) ? $travelOrder->departure_time : (isset($travelOrder->departure_time) ? \Carbon\Carbon::parse($travelOrder->departure_time)->format('H:i') : ''),
                'purpose' => $travelOrder->purpose,
                'approver1_employee_id' => $travelOrder->approver1_employee_id,
                'approver2_employee_id' => $travelOrder->approver2_employee_id,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TravelOrder $travelOrder)
    {
        // Ensure the employee can only update their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have an employee record. Please contact your administrator to set up your employee profile.'
                ], 403);
            }
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record. Please contact your administrator to set up your employee profile.');
        }
        
        if ($travelOrder->employee_id !== $employee->id) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        
        // Check if user has permission to edit based on their role and approval status
        $canEdit = false;
        
        // Load employee's officer record to check role flags
        $employee->load('officer');
        $isUnitHead = $employee->officer && $employee->officer->unit_head && !$employee->officer->division_head && !$employee->officer->vp && !$employee->officer->president;
        $isDivisionHead = $employee->officer && $employee->officer->division_head && !$employee->officer->vp && !$employee->officer->president;
        $isVP = $employee->officer && $employee->officer->vp && !$employee->officer->president;
        $isPresident = $employee->officer && $employee->officer->president;
        
        if ($isUnitHead) {
            // Unit Head: can edit if not approved by Division Head AND VP
            $canEdit = (!$travelOrder->divisionhead_approved && !$travelOrder->vp_approved);
        } elseif ($isDivisionHead) {
            // Division Head: can edit if not approved by VP
            $canEdit = !$travelOrder->vp_approved;
        } elseif ($isVP) {
            // VP: can edit if not approved by President
            $canEdit = !$travelOrder->president_approved;
        } elseif ($isPresident) {
            // President: can always edit
            $canEdit = true;
        } else {
            // Regular employee: can edit if not approved by Unit Head OR not approved by Division Head
            $canEdit = (!$travelOrder->head_approved || !$travelOrder->divisionhead_approved);
        }
        
        if (!$canEdit) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Cannot update approved travel orders'], 403);
            }
            abort(403);
        }
        
        $request->validate([
            'destination' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'region' => 'nullable|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'date_from' => 'required|date|before_or_equal:date_to',
            'date_to' => 'required|date|after_or_equal:date_from',
            'departure_time' => 'nullable|string|max:20', 
            'purpose' => 'required|string|max:500',
            'university_president_approver' => 'nullable|exists:employees,id',
            'vp_approver' => 'nullable|exists:employees,id',
            'division_head_approver' => 'nullable|exists:employees,id',
            'unit_head_approver' => 'nullable|exists:employees,id',
        ]);

        // Process departure_time to ensure it's in the correct format or null
        $departureTime = null;
        if (!empty($request->departure_time)) {
            // Trim whitespace and normalize the value
            $cleanTime = trim($request->departure_time);
            
            // Try to extract time in H:i format using regex
            if (preg_match('/([01]?[0-9]|2[0-3]):([0-5][0-9])/', $cleanTime, $matches)) {
                $departureTime = $matches[1] . ':' . $matches[2];
            } elseif (preg_match('/([01]?[0-9]|2[0-3])[.:]?([0-5][0-9])/', $cleanTime, $matches)) {
                // Handle cases where it might be formatted differently
                $departureTime = sprintf('%02d:%02d', $matches[1], $matches[2]);
            }
        }

        // Process approvers from University President, VP, Division Head, and Unit Head dropdowns
        // Collect all selected approvers in order of selection
        $selectedApprovers = [];
        
        if (!empty($request->university_president_approver)) {
            $selectedApprovers[] = [
                'employee_id' => $request->university_president_approver,
                'rank' => self::getOfficerRankForAssignment($request->university_president_approver)
            ];
        }
        
        if (!empty($request->vp_approver)) {
            $selectedApprovers[] = [
                'employee_id' => $request->vp_approver,
                'rank' => self::getOfficerRankForAssignment($request->vp_approver)
            ];
        }
        
        if (!empty($request->division_head_approver)) {
            $selectedApprovers[] = [
                'employee_id' => $request->division_head_approver,
                'rank' => self::getOfficerRankForAssignment($request->division_head_approver)
            ];
        }
        
        if (!empty($request->unit_head_approver)) {
            $selectedApprovers[] = [
                'employee_id' => $request->unit_head_approver,
                'rank' => self::getOfficerRankForAssignment($request->unit_head_approver)
            ];
        }
        
        // Sort by rank (ASCENDING - lower rank first, then higher rank)
        // Lower rank approves FIRST (approver2), Higher rank approves SECOND (approver1)
        usort($selectedApprovers, function($a, $b) {
            return $a['rank'] <=> $b['rank'];  // Lower rank first
        });
        
        // Assign to approver1 and approver2 based on number of selected approvers
        if (count($selectedApprovers) === 1) {
            // Single approver - assign to approver2 (appears in I45/I46)
            $approver2 = $selectedApprovers[0]['employee_id'];
            $approver1 = null;
        } else {
            // Two approvers - assign by rank (lower rank first as approver2, higher rank second as approver1)
            $approver2 = $selectedApprovers[0]['employee_id'];  // Lower rank - approves FIRST
            $approver1 = $selectedApprovers[1]['employee_id'];  // Higher rank - approves SECOND
        }
        
        if (empty($approver1) && empty($approver2)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least 1 approver.'
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'Please select at least 1 approver.')
                ->withInput();
        }
        
        // Count total selected approvers
        $selectedCount = count($selectedApprovers);
        
        if ($selectedCount > 2) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only select a maximum of 2 approvers.'
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'You can only select a maximum of 2 approvers.')
                ->withInput();
        }

        // The approvers must OUTRANK the requester (same rule as store()).
        // Only approvers that are being (re)assigned are checked, so editing the other fields of a
        // request that is already in flight is never blocked by an assignment made before this rule.
        $savedApproverIds = array_map('intval', array_filter([
            $travelOrder->approver1_employee_id,
            $travelOrder->approver2_employee_id,
        ]));
        $newApproverIds = array_diff(
            array_map('intval', array_column($selectedApprovers, 'employee_id')),
            $savedApproverIds
        );

        if (!empty($newApproverIds)) {
            // Use the position chosen in the form when it is one of the employee's own, else the saved one.
            $filedUnderPosition = $request->filled('position_id')
                ? $employee->positions()->where('id', $request->position_id)->first()
                : null;
            $filedUnderPosition = $filedUnderPosition ?: $travelOrder->position;

            $approverError = ApprovalHierarchy::validateApprovers($employee, $filedUnderPosition, $newApproverIds);
            if ($approverError) {
                return $this->approverRankError($request, $approverError);
            }
        }

        $travelOrder->update([
            'destination' => $request->destination,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'region' => $request->region,
            'distance_km' => $request->distance_km,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'departure_time' => $departureTime,
            'purpose' => $request->purpose,
            'approver1_employee_id' => $approver1,
            'approver2_employee_id' => $approver2,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Travel order updated successfully.',
                'data' => $travelOrder
            ]);
        }

        return redirect()->route('travel-orders.index')
            ->with('success', 'Travel order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TravelOrder $travelOrder): RedirectResponse
    {
        // Ensure the employee can only delete their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record. Please contact your administrator to set up your employee profile.');
        }
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Allow deletion if:
        // 1. Travel order is still in draft/pending status (no approvals yet)
        // 2. OR travel order is cancelled (users can delete their own cancelled orders)
        $isCancelled = $travelOrder->status === 'cancelled' || $travelOrder->is_cancelled;
        
        if (!$isCancelled && ($travelOrder->approver1_approved_at || $travelOrder->approver2_approved_at)) {
            abort(403);
        }
        
        $travelOrder->delete();

        // Redirect back to the appropriate tab based on status
        $redirectTab = $isCancelled ? 'cancelled' : 'draft';
        
        return redirect()->route('travel-orders.index', ['tab' => $redirectTab])
            ->with('success', 'Travel order deleted successfully.');
    }
    
    /**
     * Delete a cancelled travel order permanently.
     */
    public function deleteCancelled(TravelOrder $travelOrder): RedirectResponse|JsonResponse
    {
        // Ensure the employee can only delete their own cancelled travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have an employee record. Please contact your administrator to set up your employee profile.'
                ], 403);
            }
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record. Please contact your administrator to set up your employee profile.');
        }
        
        if ($travelOrder->employee_id !== $employee->id) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 403);
            }
            abort(403);
        }
        
        // Only cancelled travel orders can be deleted
        if ($travelOrder->status !== 'cancelled' && !$travelOrder->is_cancelled) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This travel order cannot be deleted.'
                ], 403);
            }
            return redirect()->route('travel-orders.index', ['tab' => 'cancelled'])
                ->with('error', 'This travel order cannot be deleted.');
        }
        
        $travelOrder->delete();
        
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cancelled travel order deleted successfully.'
            ]);
        }
        
        return redirect()->route('travel-orders.index', ['tab' => 'cancelled'])
            ->with('success', 'Cancelled travel order deleted successfully.');
    }
    
    /**
     * Get the creator of a travel order.
     */
    public function getCreator($id): \Illuminate\Http\JsonResponse
    {
        $travelOrder = TravelOrder::with('employee')->find($id);
        
        if (!$travelOrder) {
            return response()->json(['error' => 'Travel order not found'], 404);
        }
        
        $creatorName = '';
        if ($travelOrder->employee) {
            $creatorName = $travelOrder->employee->first_name . ' ' . $travelOrder->employee->last_name;
        }
        
        return response()->json([
            'creator_name' => $creatorName
        ]);
    }
    
    /**
     * Generate PDF for travel order (exact copy of Motorpool Admin with employee authentication)
     */
    public function generateExcel(TravelOrder $travelOrder): Response
    {
        $user = Auth::user();
        
        // Check if user is admin or motorpool admin - they can view all travel orders
        $isAdmin = $user->role === 'admin' || $user->role === 'motorpool_admin';
        
        if ($isAdmin) {
            // For admin/motorpool admin, use the travel order's employee
            $employee = $travelOrder->employee;
            
            if (!$employee) {
                abort(403, 'Travel order has no employee record');
            }
        } else {
            // For regular users, check their own employee record
            $employee = $user->employee;
            
            // Check if employee exists
            if (!$employee) {
                abort(403);
            }
        }
        
        // Load employee's primary position to check their role
        $employee->load(['positions']);
        $primaryPosition = $employee->positions()->where('is_primary', true)->first();
        
        // Check if user has permission to view this travel order PDF
        // Allow access if:
        // 1. It's their own travel order (for non-admin users)
        // 2. User is admin or motorpool admin (already handled above)
        // 3. User has approval authority over this travel order (based on position)
        // 4. User is selected as an approver on this travel order
        $isOwn = !$isAdmin && $travelOrder->employee_id === $employee->id;
        
        // Check if user is selected as an approver (either approver1 or approver2)
        $isApprover = ($travelOrder->approver1_employee_id === $employee->id || 
                      $travelOrder->approver2_employee_id === $employee->id);
        
        // Check approval authority based on travel order's position
        $hasApprovalAuthority = false;
        $position = $travelOrder->position;
        
        if ($position) {
            // Unit head can view travel orders from their unit
            if ($primaryPosition && $primaryPosition->is_unit_head && 
                !$primaryPosition->is_division_head && !$primaryPosition->is_vp && !$primaryPosition->is_president &&
                $position->unit_id === $primaryPosition->unit_id) {
                $hasApprovalAuthority = true;
            }
            
            // Division head can view travel orders from their division
            if ($primaryPosition && $primaryPosition->is_division_head && 
                !$primaryPosition->is_vp && !$primaryPosition->is_president &&
                $position->division_id === $primaryPosition->division_id) {
                $hasApprovalAuthority = true;
            }
            
            // VP can view travel orders from their office OR where they are selected as approver
            if ($primaryPosition && $primaryPosition->is_vp && !$primaryPosition->is_president) {
                // Allow if from their office OR if they are an approver
                if ($position->office_id === $primaryPosition->office_id || $isApprover) {
                    $hasApprovalAuthority = true;
                }
            }
            
            // President can view all travel orders
            if ($primaryPosition && $primaryPosition->is_president) {
                $hasApprovalAuthority = true;
            }
        }
        
        if (!$isOwn && !$isAdmin && !$hasApprovalAuthority && !$isApprover) {
            abort(403, 'Unauthorized access to travel order PDF');
        }
        
        // Load the travel order with all necessary relationships
        $travelOrder->load([
            'employee', 
            'employee.user',
            'position',  // Load the travel order's position (not employee's position)
            'employee.office',
            'employee.division',
            'employee.unit'
        ]);
        
        // Get employee information
        $employee = $travelOrder->employee;
        $fullName = $employee->first_name . ' ' . $employee->last_name;
        $positionName = $travelOrder->position ? $travelOrder->position->position_name : $employee->position_name;
        
        // Determine organizational unit name
        $orgUnitName = '';
        if ($employee->unit) {
            $orgUnitName = $employee->unit->unit_name;
        } elseif ($employee->division) {
            $orgUnitName = $employee->division->division_name;
        } elseif ($employee->office) {
            $orgUnitName = $employee->office->office_name;
        }
        
        // Get supervisor information
        $supervisorInfo = $this->getSupervisorInfo($employee);
        
        // Load the Excel template
        $templatePath = public_path('templates/travel_order_template.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Fill the Excel cells with data (EXACT COPY FROM MOTORPOOL ADMIN)
        $sheet->setCellValue('C9', $fullName);  // Employee name  
        $sheet->setCellValue('C10', $travelOrder->purpose);  // Purpose
        $sheet->setCellValue('G11', $travelOrder->destination);  // Destination
        $sheet->setCellValue('E23', $fullName);  // Employee name 
        $sheet->setCellValue('E24', $positionName);  // Position
        $sheet->setCellValue('E25', $orgUnitName);  // Unit/Division/Office
        $sheet->setCellValue('E26', $travelOrder->purpose);  // Purpose
        $sheet->setCellValue('B33', $travelOrder->date_from ? $travelOrder->date_from->format('F j, Y') : '');  // Date From
        $sheet->setCellValue('B35', $travelOrder->date_to ? $travelOrder->date_to->format('F j, Y') : '');  // Date To
        $sheet->setCellValue('D34', $travelOrder->destination);  // Destination 
        $sheet->setCellValue('G34', $travelOrder->departure_time ?? '');  // Departure Time
        $sheet->setCellValue('H34', '');  // Arrival Time 
        
        // Get the selected approvers from the travel order
        $approver = null;
        $signer = null;
        
        if ($travelOrder->approver1_employee_id) {
            $approverEmployee = Employee::with('positions')->find($travelOrder->approver1_employee_id);
            if ($approverEmployee) {
                $positionData = $approverEmployee->positions->first();
                $approver = [
                    'name' => $approverEmployee->first_name . ' ' . $approverEmployee->last_name,
                    'position' => $positionData ? $positionData->position_name : 'N/A',
                    'is_vp' => $approverEmployee->positions && $approverEmployee->positions->contains('is_vp', true),
                    'is_division_head' => $approverEmployee->positions && $approverEmployee->positions->contains('is_division_head', true),
                    'is_unit_head' => $approverEmployee->positions && $approverEmployee->positions->contains('is_unit_head', true),
                    'is_president' => $approverEmployee->positions && $approverEmployee->positions->contains('is_president', true),
                ];
            }
        }
        
        if ($travelOrder->approver2_employee_id) {
            $signerEmployee = Employee::with('positions')->find($travelOrder->approver2_employee_id);
            if ($signerEmployee) {
                $positionData = $signerEmployee->positions->first();
                $signer = [
                    'name' => $signerEmployee->first_name . ' ' . $signerEmployee->last_name,
                    'position' => $positionData ? $positionData->position_name : 'N/A',
                    'is_vp' => $signerEmployee->positions && $signerEmployee->positions->contains('is_vp', true),
                    'is_division_head' => $signerEmployee->positions && $signerEmployee->positions->contains('is_division_head', true),
                    'is_unit_head' => $signerEmployee->positions && $signerEmployee->positions->contains('is_unit_head', true),
                    'is_president' => $signerEmployee->positions && $signerEmployee->positions->contains('is_president', true),
                ];
            }
        }
        
        // PDF Placement Logic based on actual database columns:
        // - If 2 approvers: Higher rank (Approver 1) in H19/H20, Lower rank (Approver 2) in I45/I46
        // - If 1 approver only (in approver2): Display in I45/I46 (lower position), H19/H20 shows N/A
        
        if ($travelOrder->approver1_employee_id && $travelOrder->approver2_employee_id) {
            // Two approvers: Higher rank in H19/H20, Lower rank in I45/I46
            if ($approver) {
                $sheet->setCellValue('H19', $approver['name']);
                $sheet->setCellValue('H20', $approver['position']);
            }
            if ($signer) {
                $sheet->setCellValue('I45', $signer['name']);
                $sheet->setCellValue('I46', $signer['position']);
            }
        } elseif ($travelOrder->approver2_employee_id && !$travelOrder->approver1_employee_id) {
            // Only one approver (in approver2): Display in I45/I46, H19/H20 = N/A
            $sheet->setCellValue('H19', 'N/A');
            $sheet->setCellValue('H20', 'N/A');
            if ($signer) {
                $sheet->setCellValue('I45', $signer['name']);
                $sheet->setCellValue('I46', $signer['position']);
            }
        } else {
            // No approvers assigned
            $sheet->setCellValue('H19', 'N/A');
            $sheet->setCellValue('H20', 'N/A');
            $sheet->setCellValue('I45', 'N/A');
            $sheet->setCellValue('I46', 'N/A');
        }
        
        $sheet->setCellValue('I41', $fullName);  // Employee name (signatory)
        $sheet->setCellValue('I42', $positionName);  // Employee position (signatory)
        
        // Add approval/cancellation status stamps based on actual database columns:
        // - If 2 approvers: Approver 1 (higher) stamp in H17, Approver 2 (lower) stamp in I43
        // - If 1 approver only (in approver2): Display stamp in I43 (matching the single approver position)
        
        // First, check for APPROVAL stamps independently
        if ($travelOrder->approver1_employee_id && $travelOrder->approver2_employee_id) {
            // Two approvers: H17 for Approver 1 (higher), I43 for Approver 2 (lower)
            if ($travelOrder->approver1_approved_at) {
                $sheet->setCellValue('H17', 'APPROVED ' . $travelOrder->approver1_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A'));
            }
            if ($travelOrder->approver2_approved_at) {
                $sheet->setCellValue('I43', 'APPROVED ' . $travelOrder->approver2_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A'));
            }
        } elseif ($travelOrder->approver2_employee_id && !$travelOrder->approver1_employee_id) {
            // Only one approver (in approver2): Display stamp in I43
            if ($travelOrder->approver2_approved_at) {
                $sheet->setCellValue('I43', 'APPROVED ' . $travelOrder->approver2_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A'));
            }
        }
        
        // Then, check for CANCELLATION/REJECTION stamps independently
        if ($travelOrder->status === 'cancelled') {
            // CANCELLED STAMP - Show rejection information
            $cancellationText = 'CANCELLED';
            if ($travelOrder->rejected_at) {
                $cancellationText .= ' ' . $travelOrder->rejected_at->timezone('Asia/Manila')->format('M j, Y g:i A');
            }
            
            // Display CANCELLED stamp only in the approver who rejected
            // Check which approver rejected (based on rejected_by_employee_id)
            if ($travelOrder->approver1_employee_id && $travelOrder->approver2_employee_id) {
                // Two approvers: Determine which one rejected
                if ($travelOrder->rejected_by_employee_id == $travelOrder->approver1_employee_id) {
                    // Approver 1 (higher) rejected - show in H17
                    $sheet->setCellValue('H17', $cancellationText);
                    // I43 remains showing previous status (approval or empty)
                } elseif ($travelOrder->rejected_by_employee_id == $travelOrder->approver2_employee_id) {
                    // Approver 2 (lower) rejected - show in I43
                    $sheet->setCellValue('I43', $cancellationText);
                    // H17 remains showing previous status (approval or empty)
                }
            } elseif ($travelOrder->approver2_employee_id && !$travelOrder->approver1_employee_id) {
                // Only one approver (approver2): Show in I43
                if ($travelOrder->rejected_by_employee_id == $travelOrder->approver2_employee_id) {
                    $sheet->setCellValue('I43', $cancellationText);
                }
            }
            
            // Add rejection reason if available
            if ($travelOrder->rejection_reason) {
                $sheet->setCellValue('H18', 'Reason: ' . substr($travelOrder->rejection_reason, 0, 50));
                if (strlen($travelOrder->rejection_reason) > 50) {
                    $sheet->setCellValue('H19', substr($travelOrder->rejection_reason, 50, 50));
                }
            }
        }
        
        // Save Excel temporarily and return it
        $excelTemp = tempnam(sys_get_temp_dir(), 'travel_order_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($excelTemp);
        
        // Return Excel response for preview
        return response(file_get_contents($excelTemp))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="Travel_Order_' . $travelOrder->id . '.xlsx"')
            ->header('Cache-Control', 'max-age=0')
            ->header('Pragma', 'public')
            ->header('Expires', '0');
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
                // Find Division Head in the same division
                return \App\Models\Employee::whereHas('officer', function($query) {
                    $query->where('division_head', true);
                })
                ->whereHas('positions', function($query) use ($employeePosition) {
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
        // Find unit head for this specific unit
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
     * Get the rank of an officer for PDF placement
     * Higher number = higher rank
     * Rank order: President (4) > VP (3) > Division Head (2) > Unit Head (1)
     */
    private static function getOfficerRank($officer)
    {
        if (!is_array($officer)) {
            return 0;
        }
        
        // President has highest rank
        if (!empty($officer['is_president'])) {
            return 4;
        }
        
        // VP is second highest
        if (!empty($officer['is_vp'])) {
            return 3;
        }
        
        // Division Head is third
        if (!empty($officer['is_division_head'])) {
            return 2;
        }
        
        // Unit Head is fourth
        if (!empty($officer['is_unit_head'])) {
            return 1;
        }
        
        // Regular employee or unknown
        return 0;
    }
    
    /**
     * Get the rank of an officer for approval assignment
     * Lower number = lower rank = approves first
     * Rank order: Unit Head (1) < Division Head (2) < VP (3) < President (4)
     */
    private static function getOfficerRankForAssignment($employeeId)
    {
        return ApprovalHierarchy::employeeRank($employeeId);
    }

    /**
     * Response for an approver that does not outrank the requester.
     * JSON callers (the create/edit modals) get a 422 with both `message` and `errors`.
     */
    private function approverRankError(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => ['approvers' => [$message]],
            ], 422);
        }

        return redirect()->back()
            ->with('error', $message)
            ->withInput();
    }

    /**
     * Get travel order details for modal display
     */
    public function showDetails(TravelOrder $travelOrder): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found'
            ], 404);
        }
        
        // Load employee's primary position to check their role
        $employee->load(['positions']);
        $primaryPosition = $employee->positions()->where('is_primary', true)->first();
        
        // Check if user has permission to view this travel order
        // Allow access if:
        // 1. It's their own travel order
        // 2. User is admin or motorpool admin
        // 3. User has approval authority over this travel order (based on position)
        $isOwn = $travelOrder->employee_id === $employee->id;
        $isAdmin = $user->role === 'admin' || $user->role === 'motorpool_admin';
        
        // Check approval authority based on travel order's position
        $hasApprovalAuthority = false;
        $position = $travelOrder->position;
        
        if ($position) {
            // Unit head can view travel orders from their unit
            if ($primaryPosition && $primaryPosition->is_unit_head && 
                !$primaryPosition->is_division_head && !$primaryPosition->is_vp && !$primaryPosition->is_president &&
                $position->unit_id === $primaryPosition->unit_id) {
                $hasApprovalAuthority = true;
            }
            
            // Division head can view travel orders from their division
            if ($primaryPosition && $primaryPosition->is_division_head && 
                !$primaryPosition->is_vp && !$primaryPosition->is_president &&
                $position->division_id === $primaryPosition->division_id) {
                $hasApprovalAuthority = true;
            }
            
            // VP can view travel orders from their office
            if ($primaryPosition && $primaryPosition->is_vp && !$primaryPosition->is_president &&
                $position->office_id === $primaryPosition->office_id) {
                $hasApprovalAuthority = true;
            }
            
            // President can view all travel orders
            if ($primaryPosition && $primaryPosition->is_president) {
                $hasApprovalAuthority = true;
            }
        }
        
        if (!$isOwn && !$isAdmin && !$hasApprovalAuthority) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to travel order'
            ], 403);
        }
        
        // Load relationships - include user for employee email and rejectedBy, and itinerary with trip tickets
        $travelOrder->load(['employee.user', 'position', 'rejectedBy']);
        
        // Explicitly load itinerary even if it doesn't exist (returns null instead of undefined)
        $travelOrder->load('itinerary.tripTickets');
        
        return response()->json([
            'success' => true,
            'data' => $travelOrder
        ]);
    }
    
    /**
     * Cancel a travel order by updating its status to 'cancelled'.
     */
    public function cancel(Request $request, TravelOrder $travelOrder): JsonResponse
    {
        // Ensure the employee can only cancel their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have an employee record. Please contact your administrator to set up your employee profile.'
            ], 403);
        }
        
        if ($travelOrder->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to travel order'
            ], 403);
        }
        
        // Validate the cancellation reason
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);
        
        // Update the status to cancelled and store the reason
        $travelOrder->update([
            'status' => 'cancelled',
            'rejection_reason' => $validated['reason'],
            'rejected_by_employee_id' => $employee->id,
            'rejected_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Travel order cancelled successfully',
            'travel_order' => $travelOrder
        ]);
    }
}