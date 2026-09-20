<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use App\Models\TravelOrder;
use App\Models\Employee;
use App\Models\Unit;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PresidentOwnTravelOrderController extends Controller
{
    /**
     * Display a listing of the president's own travel orders.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Get the tab parameter, default to 'draft'
        $tab = $request->get('tab', 'draft');
        
        // Get search term if provided
        $search = $request->get('search', '');
        
        // Get travel orders for this employee based on the selected tab
        $query = TravelOrder::where('employee_id', $employee->id);
        
        switch ($tab) {
            case 'draft':
                // For president, draft means status is still draft (not yet submitted)
                $query->where('status', 'draft');
                break;
            case 'pending':
                // For president, pending means waiting for approval from selected approvers
                \Log::info('Pending tab accessed - querying for status=pending');
                $query->where('status', 'pending');
                break;
            case 'approved':
                // For president, approved means status is 'approved'
                // President's travel orders are automatically approved upon creation
                // Show ALL approved travel orders regardless of approver configuration
                $query->where('status', 'approved');
                break;
            case 'completed':
                // For president, completed means status is 'completed'
                $query->where('status', 'completed');
                break;
            case 'cancelled':
                // For president, cancelled means status is cancelled
                $query->where('status', 'cancelled');
                break;
            default:
                // Default to draft tab for President
                $query->where('status', 'draft');
                break;
        }
        
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
        
        $travelOrders = $query->with('position')->orderBy('created_at', 'desc')->paginate(10)->appends(['tab' => $tab, 'search' => $search]);
        
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
        
        // Get ALL officers with any role (VP, Division Head, Unit Head, President) - same as other controllers
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
                // Build officer_offices array showing which offices they serve in each role
                $officerOffices = [
                    'vp' => [],
                    'division_head' => [],
                    'unit_head' => [],
                    'president' => []
                ];
                
                foreach ($officerEmployee->positions as $pos) {
                    // Collect office IDs for each role
                    if ($pos->is_vp && $pos->office_id) {
                        $officerOffices['vp'][] = $pos->office_id;
                    }
                    if ($pos->is_division_head && $pos->office_id) {
                        $officerOffices['division_head'][] = $pos->office_id;
                    }
                    if ($pos->is_unit_head && $pos->office_id) {
                        $officerOffices['unit_head'][] = $pos->office_id;
                    }
                }
                
                // For president, use Office ID 1
                if ($officerRecord->president) {
                    $presidentsOffice = \App\Models\Office::where('office_name', 'Office of the University President')->first();
                    $officerOffices['president'][] = $presidentsOffice ? $presidentsOffice->id : 1;
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
                        'officer_offices' => $officerOffices,
                    ]
                ];
            }
        }
        
        return view('travel-orders.president-index', compact('travelOrders', 'tab', 'search', 'positions', 'officers', 'units'));
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
        
        // Check if the employee is President - MULTIPLE METHODS
        $isPresident = false;
        
        // Method 1: Check positions table
        foreach ($positions as $position) {
            if ($position->is_president == true) {
                $isPresident = true;
                break;
            }
        }
        
        // Method 2: Check officer table as backup
        if (!$isPresident) {
            $officerRecord = $employee->officer;
            if ($officerRecord && $officerRecord->president == true) {
                $isPresident = true;
            }
        }
        
        \Log::info('President Travel Order Create', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->first_name . ' ' . $employee->last_name,
            'isPresident (method1)' => $isPresident,
            'positions_count' => $positions->count(),
            'positions' => $positions->map(function($p) {
                return [
                    'id' => $p->id,
                    'position_name' => $p->position_name,
                    'is_president' => $p->is_president,
                    'is_vp' => $p->is_vp,
                    'is_division_head' => $p->is_division_head,
                    'is_unit_head' => $p->is_unit_head,
                ];
            }),
            'has_officer_record' => $employee->officer ? true : false,
            'officer_president' => $employee->officer ? $employee->officer->president : null,
        ]);
        
        // Get ALL officers with any role (VP, Division Head, Unit Head, President) - same as other controllers
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
                // Build officer_offices array showing which offices they serve in each role
                $officerOffices = [
                    'vp' => [],
                    'division_head' => [],
                    'unit_head' => [],
                    'president' => []
                ];
                
                foreach ($officerEmployee->positions as $pos) {
                    // Collect office IDs for each role
                    if ($pos->is_vp && $pos->office_id) {
                        $officerOffices['vp'][] = $pos->office_id;
                    }
                    if ($pos->is_division_head && $pos->office_id) {
                        $officerOffices['division_head'][] = $pos->office_id;
                    }
                    if ($pos->is_unit_head && $pos->office_id) {
                        $officerOffices['unit_head'][] = $pos->office_id;
                    }
                }
                
                // For president, use Office ID 1
                if ($officerRecord->president) {
                    $presidentsOffice = \App\Models\Office::where('office_name', 'Office of the University President')->first();
                    $officerOffices['president'][] = $presidentsOffice ? $presidentsOffice->id : 1;
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
                        'officer_offices' => $officerOffices,
                    ]
                ];
            }
        }
        
        return view('travel-orders.create', compact('positions', 'officers', 'isPresident'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'destination' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'region' => 'nullable|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'date_from' => 'required|date|before_or_equal:date_to',
            'date_to' => 'required|date|after_or_equal:date_from',
            'departure_time' => 'nullable|string|max:20', // Increased max length to accommodate any format
            'purpose' => 'required|string|max:500',
            'position_id' => 'required|exists:emp_positions,id', // Add validation for position selection
        ]);

        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record. Please contact your administrator to set up your employee profile.');
        }

        // Verify that the selected position belongs to the employee
        $position = $employee->positions()->where('id', $request->position_id)->first();
        if (!$position) {
            return redirect()->back()
                ->with('error', 'Invalid position selected.')
                ->withInput();
        }

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

        // Process approvers from dropdowns (President can now select approvers)
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
        
        // Sort by rank (higher rank first)
        usort($selectedApprovers, function($a, $b) {
            return $b['rank'] <=> $a['rank'];  // Higher rank first
        });
        
        // Assign to approver1 and approver2 based on number of selected approvers
        $approver1 = null;
        $approver2 = null;
        
        if (count($selectedApprovers) === 0) {
            // No approvers selected - President self-approves
            $approver2 = $employee->id;
        } elseif (count($selectedApprovers) === 1) {
            // Single approver - assign to approver2 (appears in I45/I46)
            $approver2 = $selectedApprovers[0]['employee_id'];
        } else {
            // Two or more approvers - assign by rank (higher rank as approver1, lower rank as approver2)
            $approver1 = $selectedApprovers[0]['employee_id'];  // Higher rank
            $approver2 = $selectedApprovers[1]['employee_id'];  // Lower rank
        }
        
        // Create the travel order as DRAFT (not yet submitted for approval)
        $travelOrder = TravelOrder::create([
            'employee_id' => $employee->id,
            'emp_position_id' => $request->position_id, // Associate with the selected position
            'destination' => $request->destination,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'region' => $request->region,
            'distance_km' => $request->distance_km,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'departure_time' => $departureTime,
            'purpose' => $request->purpose,
            'status' => 'draft', // Save as draft first
            'approver1_employee_id' => $approver1,
            'approver2_employee_id' => $approver2,
            // Don't set approval timestamps yet - wait for approvers to approve
        ]);

        // Check if request expects JSON (AJAX from modal)
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Travel request created successfully as draft.',
                'redirect_url' => route('president.travel-orders.index', ['tab' => 'draft'])
            ]);
        }

        return redirect()->route('president.travel-orders.index', ['tab' => 'draft'])
            ->with('success', 'Travel request created successfully as draft. You can edit and submit it for approval later.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TravelOrder $travelOrder): View
    {
        // Ensure the president can only view their own travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        return view('travel-orders.show', compact('travelOrder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TravelOrder $travelOrder): View
    {
        // Ensure the president can only edit their own travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow editing if status is still pending
        if ($travelOrder->status !== 'pending') {
            abort(403);
        }
        
        return view('travel-orders.edit', compact('travelOrder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        // Ensure the president can only update their own travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow updating if status is still pending
        if ($travelOrder->status !== 'pending') {
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
            'departure_time' => 'nullable|string|max:20', // Increased max length to accommodate any format
            'purpose' => 'required|string|max:500',
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
        ]);

        return redirect()->route('president.travel-orders.index')
            ->with('success', 'Travel order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TravelOrder $travelOrder): RedirectResponse
    {
        // Ensure the president can only delete their own travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow deleting if status is draft or pending
        if (!in_array($travelOrder->status, ['draft', 'pending'])) {
            abort(403);
        }
        
        $travelOrder->delete();

        return redirect()->route('president.travel-orders.index')
            ->with('success', 'Travel order deleted successfully.');
    }
    
    /**
     * Submit a draft travel order for approval.
     */
    public function submitForApproval(TravelOrder $travelOrder)
    {
        // Ensure the president can only submit their own travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to travel order'
            ], 403);
        }
        
        // Only allow submitting if status is still draft
        if ($travelOrder->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'This travel order has already been submitted.'
            ], 422);
        }
        
        // Check if approvers are set
        if (empty($travelOrder->approver1_employee_id) && empty($travelOrder->approver2_employee_id)) {
            return response()->json([
                'success' => false,
                'message' => 'No approvers selected. Please edit the travel order and select at least one approver.'
            ], 422);
        }
        
        // Update status to pending
        $travelOrder->update([
            'status' => 'pending',
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Travel request submitted for approval successfully.'
        ]);
    }
    
    /**
     * Generate PDF for president's own travel order
     */
    public function generateExcel(TravelOrder $travelOrder): Response
    {
        // Ensure the president can only view their own travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Load the travel order with all necessary relationships
        $travelOrder->load([
            'employee', 
            'employee.user',
            'employee.position',
            'employee.office',
            'employee.division',
            'employee.unit'
        ]);
        
        // Get employee information
        $employee = $travelOrder->employee;
        $fullName = $employee->first_name . ' ' . $employee->last_name;
        $positionName = $employee->position ? $employee->position->position_name : $employee->position_name;
        
        // Determine organizational unit name
        $orgUnitName = '';
        if ($employee->unit) {
            $orgUnitName = $employee->unit->unit_name;
        } elseif ($employee->division) {
            $orgUnitName = $employee->division->division_name;
        } elseif ($employee->office) {
            $orgUnitName = $employee->office->office_name;
        }
        
        // Get President information (for self-reference)
        $president = \App\Models\Employee::whereHas('officer', function($query) {
            $query->where('president', true);
        })->first();
        
        $presidentName = $president ? $president->first_name . ' ' . $president->last_name : 'N/A';
        $presidentPosition = $president && $president->position ? $president->position->position_name : 'University President';
        
        // Load Excel template
        $templatePath = public_path('templates/travel_order_template.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Fill in the basic travel order information
        $sheet->setCellValue('C9', $fullName);
        $sheet->setCellValue('C10', $travelOrder->purpose);
        $sheet->setCellValue('G11', $travelOrder->destination);
        $sheet->setCellValue('E23', $fullName);
        $sheet->setCellValue('E24', $positionName);
        $sheet->setCellValue('E25', $orgUnitName);
        $sheet->setCellValue('E26', $travelOrder->purpose);
        $sheet->setCellValue('B33', $travelOrder->date_from ? $travelOrder->date_from->format('F j, Y') : '');
        $sheet->setCellValue('B35', $travelOrder->date_to ? $travelOrder->date_to->format('F j, Y') : '');
        $sheet->setCellValue('D34', $travelOrder->destination);
        $sheet->setCellValue('G34', $travelOrder->departure_time ?? '');
        $sheet->setCellValue('H34', '');
        
        // NEW APPROVER SYSTEM: President as single approver (in approver2)
        // Single approver goes in I45/I46, H19/H20 shows N/A
        
        if ($travelOrder->status === 'cancelled') {
            // CANCELLED STAMP - Show rejection information
            $cancellationText = 'CANCELLED';
            if ($travelOrder->rejected_at) {
                $cancellationText .= ' ' . $travelOrder->rejected_at->timezone('Asia/Manila')->format('M j, Y g:i A');
            }
            
            // Display CANCELLED stamp only in the approver who rejected
            // Check which approver rejected (based on rejected_by_employee_id)
            if ($travelOrder->approver2_employee_id) {
                // Only one approver (approver2 - President): Show in I43
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
        } elseif ($travelOrder->approver2_employee_id) {
            // Get President details from approver2
            $approverEmployee = Employee::with('positions')->find($travelOrder->approver2_employee_id);
            if ($approverEmployee) {
                $positionData = $approverEmployee->positions->first();
                $approverName = $approverEmployee->first_name . ' ' . $approverEmployee->last_name;
                $approverPosition = $positionData ? $positionData->position_name : 'University President';
                
                // Single approver (President) in I45/I46
                $sheet->setCellValue('I45', $approverName);
                $sheet->setCellValue('I46', $approverPosition);
                
                // H19/H20 shows N/A for single approver
                $sheet->setCellValue('H19', 'N/A');
                $sheet->setCellValue('H20', 'N/A');
                
                // Add approval/cancellation stamp in I43
                if ($travelOrder->approver2_approved_at) {
                    $sheet->setCellValue('I43', 'APPROVED ' . $travelOrder->approver2_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A'));
                }
            }
        } else {
            // Fallback to old logic if no approver2 set
            $sheet->setCellValue('H19', $presidentName);
            $sheet->setCellValue('H20', $presidentPosition);
            $sheet->setCellValue('I45', $presidentName);
            $sheet->setCellValue('I46', $presidentPosition);
            
            // Add approval status in cells I17 and K43
            if ($travelOrder->president_approved_at) {
                $presidentStatus = $travelOrder->president_approved ? 'APPROVED' : 'CANCELLED';
                $presidentTimestamp = $travelOrder->president_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
                $sheet->setCellValue('I17', $presidentStatus . ' ' . $presidentTimestamp);
                $sheet->setCellValue('K43', $presidentStatus . ' ' . $presidentTimestamp);
            }
        }
        
        $sheet->setCellValue('I41', $fullName);
        $sheet->setCellValue('I42', $positionName);
        
        // Save Excel temporarily and return it
        $excelTemp = tempnam(sys_get_temp_dir(), 'travel_order_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($excelTemp);
        
        // Return Excel response for preview
        return response(file_get_contents($excelTemp))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="President_Travel_Order_' . $travelOrder->id . '.xlsx"')
            ->header('Cache-Control', 'max-age=0')
            ->header('Pragma', 'public')
            ->header('Expires', '0');
    }
    
    /**
     * Get officer rank for assignment (higher rank = higher number)
     */
    private static function getOfficerRankForAssignment($employeeId): int
    {
        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->officer) {
            return 0;
        }
        
        $officer = $employee->officer;
        $rank = 0;
        
        // Assign rank based on position (higher rank = higher number)
        if ($officer->president) $rank += 100;  // Highest rank
        if ($officer->vp) $rank += 50;
        if ($officer->division_head) $rank += 25;
        if ($officer->unit_head) $rank += 10;   // Lowest rank
        
        return $rank;
    }
}