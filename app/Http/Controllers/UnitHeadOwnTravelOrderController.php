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
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class UnitHeadOwnTravelOrderController extends Controller
{
    /**
     * Display a listing of the unit head's own travel orders.
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
            case 'draft':
                // For unit heads, draft means status is still draft (not yet submitted)
                $query->where('status', 'draft');
                break;
            case 'pending':
                // For unit heads, pending means status is 'pending' and waiting for approval
                $query->where('status', 'pending')
                      ->where(function ($q) {
                          $q->whereNull('approver1_approved_at')
                            ->orWhere(function ($q2) {
                                $q2->whereNotNull('approver1_approved_at')
                                   ->whereNull('approver2_approved_at');
                            });
                      });
                break;
            case 'approved':
                // For unit heads, approved means status is 'approved' and both approvers have approved
                $query->where('status', 'approved')
                      ->whereNotNull('approver1_approved_at')
                      ->whereNotNull('approver2_approved_at');
                break;
            case 'completed':
                // For unit heads, completed means status is 'completed'
                $query->where('status', 'completed');
                break;
            case 'cancelled':
                // For unit heads, cancelled means status is cancelled
                $query->where('status', 'cancelled');
                break;
            default:
                // Default to pending
                $query->where('status', 'pending')
                      ->where(function ($q) {
                          $q->whereNull('approver1_approved_at')
                            ->orWhere(function ($q2) {
                                $q2->whereNotNull('approver1_approved_at')
                                   ->whereNull('approver2_approved_at');
                            });
                      });
                break;
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
        
        // Get ALL officers with any role (VP, Division Head, Unit Head, President) - same as create() method
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
        
        return view('travel-orders.create', compact('positions', 'officers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
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
            'position_id' => 'required|exists:emp_positions,id',
        ]);

        $user = Auth::user();
        $employee = $user->employee;
        
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
            $cleanTime = trim($request->departure_time);
            
            if (preg_match('/([01]?[0-9]|2[0-3]):([0-5][0-9])/', $cleanTime, $matches)) {
                $departureTime = $matches[1] . ':' . $matches[2];
            } elseif (preg_match('/([01]?[0-9]|2[0-3])[.:]?([0-5][0-9])/', $cleanTime, $matches)) {
                $departureTime = sprintf('%02d:%02d', $matches[1], $matches[2]);
            }
        }

        // Create the travel order
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
            'status' => 'pending',
        ]);

        return redirect()->route('unithead.travel-orders.index', ['tab' => 'pending'])
            ->with('success', 'Travel order created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TravelOrder $travelOrder): View
    {
        // Ensure the unit head can only view their own travel orders
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
        // Ensure the unit head can only edit their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow editing if not yet approved by division head or VP
        if (!is_null($travelOrder->divisionhead_approved) || !is_null($travelOrder->vp_approved)) {
            abort(403);
        }
        
        return view('travel-orders.edit', compact('travelOrder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        // Ensure the unit head can only update their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow updating if not yet approved by division head or VP
        if (!is_null($travelOrder->divisionhead_approved) || !is_null($travelOrder->vp_approved)) {
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
        ]);

        // Process departure_time to ensure it's in the correct format or null
        $departureTime = null;
        if (!empty($request->departure_time)) {
            $cleanTime = trim($request->departure_time);
            
            if (preg_match('/([01]?[0-9]|2[0-3]):([0-5][0-9])/', $cleanTime, $matches)) {
                $departureTime = $matches[1] . ':' . $matches[2];
            } elseif (preg_match('/([01]?[0-9]|2[0-3])[.:]?([0-5][0-9])/', $cleanTime, $matches)) {
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

        return redirect()->route('unithead.travel-orders.index', ['tab' => 'pending'])
            ->with('success', 'Travel order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TravelOrder $travelOrder): RedirectResponse
    {
        // Ensure the unit head can only delete their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow deleting if not yet approved by division head or VP
        if (!is_null($travelOrder->divisionhead_approved) || !is_null($travelOrder->vp_approved)) {
            abort(403);
        }
        
        $travelOrder->delete();

        return redirect()->route('unithead.travel-orders.index', ['tab' => 'pending'])
            ->with('success', 'Travel order deleted successfully.');
    }

    /**
     * Generate PDF for unit head's own travel order (using regular employee template as basis)
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
            
            // Load positions for role checking
            $employee->load(['positions']);
            $primaryPosition = $employee->positions()->where('is_primary', true)->first();
        } else {
            // For regular users, check their own employee record
            $employee = $user->employee;
            
            // Load employee's primary position to check their role
            $employee->load(['positions']);
            $primaryPosition = $employee->positions()->where('is_primary', true)->first();
        }
        
        // Check if user has permission to view this travel order PDF
        $isOwn = !$isAdmin && $travelOrder->employee_id === $employee->id;
        
        // Check approval authority based on travel order's position
        $hasApprovalAuthority = false;
        $travelOrderPosition = $travelOrder->position;
        
        if ($travelOrderPosition) {
            // Division head can view travel orders from their division
            if ($primaryPosition && $primaryPosition->is_division_head && 
                !$primaryPosition->is_vp && !$primaryPosition->is_president &&
                $travelOrderPosition->division_id === $primaryPosition->division_id) {
                $hasApprovalAuthority = true;
            }
            
            // VP can view travel orders from their office
            if ($primaryPosition && $primaryPosition->is_vp && !$primaryPosition->is_president &&
                $travelOrderPosition->office_id === $primaryPosition->office_id) {
                $hasApprovalAuthority = true;
            }
        }
        
        if (!$isOwn && !$isAdmin && !$hasApprovalAuthority) {
            abort(403, 'Unauthorized access to travel order PDF');
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
        
        // Get supervisor information
        $supervisorInfo = $this->getSupervisorInfo($employee);
        
        // Load Excel template (using the same template as regular employees)
        $templatePath = public_path('templates/travel_order_template.xlsx');
        if (!file_exists($templatePath)) {
            abort(500, 'Travel order template not found');
        }
        
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Fill the Excel cells with data (EXACT COPY FROM REGULAR EMPLOYEE TEMPLATE)
        $sheet->setCellValue('C9', $fullName);  // Employee name  
        $sheet->setCellValue('C10', $travelOrder->purpose);  // Purpose
        $sheet->setCellValue('G11', $travelOrder->destination);  // Destination
        $sheet->setCellValue('E23', $fullName);  // Employee name (again)
        $sheet->setCellValue('E24', $positionName);  // Position
        $sheet->setCellValue('E25', $orgUnitName);  // Unit/Division/Office
        $sheet->setCellValue('E26', $travelOrder->purpose);  // Purpose
        $sheet->setCellValue('B33', $travelOrder->date_from ? $travelOrder->date_from->format('F j, Y') : '');  // Date From
        $sheet->setCellValue('B35', $travelOrder->date_to ? $travelOrder->date_to->format('F j, Y') : '');  // Date To
        $sheet->setCellValue('D34', $travelOrder->destination);  // Destination (again)
        $sheet->setCellValue('G34', $travelOrder->departure_time ?? '');  // Departure Time
        $sheet->setCellValue('H34', '');  // Arrival Time
        $sheet->setCellValue('I41', $fullName);  
        $sheet->setCellValue('I42', $positionName);
        
        // Get employee role and approval workflow
        $employeeRole = $this->getEmployeeRole($employee);
        $workflow = $this->getApprovalWorkflow($employee);
        
        // Initialize officers
        $higherRankOfficer = ['name' => '', 'position' => ''];
        $lowerRankOfficer = ['name' => '', 'position' => ''];
        
        // Get the position associated with this travel order
        $travelOrderPosition = $travelOrder->position;
        
        // For unit heads: H19/H20 = VP/President, I45/I46 = Division Head
        // Always show both approvers regardless of approval status
        
        // Get VP or President (H19/H20) - check if President's Office first
        $vpOrPresident = $this->getApproverEmployee('VP_OR_PRESIDENT', $travelOrderPosition);
        if ($vpOrPresident) {
            $higherRankOfficer['name'] = $vpOrPresident->first_name . ' ' . $vpOrPresident->last_name;
            // Determine position based on office
            if ($travelOrderPosition && $travelOrderPosition->office) {
                // Only consider it President's Office if it's specifically the Office of the University President
                // Not just any office that contains "President" in the name
                $officeName = strtolower($travelOrderPosition->office->office_name);
                if (strpos($officeName, 'office of the university president') !== false || 
                    strpos($officeName, 'office of the president') !== false) {
                    $higherRankOfficer['position'] = $vpOrPresident->position ? $vpOrPresident->position->position_name : 'University President';
                } else {
                    $higherRankOfficer['position'] = $vpOrPresident->position ? $vpOrPresident->position->position_name : 'Vice President';
                }
            } else {
                $higherRankOfficer['position'] = $vpOrPresident->position ? $vpOrPresident->position->position_name : 'Vice President';
            }
        }
        
        // Get Division Head (I45/I46)
        $divisionHead = $this->getApproverEmployee('DIVISION_HEAD', $travelOrderPosition);
        if ($divisionHead) {
            $lowerRankOfficer['name'] = $divisionHead->first_name . ' ' . $divisionHead->last_name;
            $lowerRankOfficer['position'] = $divisionHead->position ? $divisionHead->position->position_name : 'Division Head';
        }
        
        // Fallback to original supervisor info if needed
        if (empty($higherRankOfficer['name']) && empty($lowerRankOfficer['name'])) {
            // Fallback to original supervisor info if no position found
            $higherRankOfficer = $supervisorInfo;
            $lowerRankOfficer = $supervisorInfo;
        }
        
        // Set the officers in the correct cells
        // Higher rank officer in H19 (approving office) and H20 (position)
        $sheet->setCellValue('H19', $higherRankOfficer['name'] ?? 'N/A');  // Higher rank approving officer name
        $sheet->setCellValue('H20', $higherRankOfficer['position'] ?? 'N/A');  // Higher rank approving officer position
        
        // Lower rank officer in I45 (approving office) and I46 (position)
        $sheet->setCellValue('I45', $lowerRankOfficer['name'] ?? 'N/A');  // Lower rank approving officer name
        $sheet->setCellValue('I46', $lowerRankOfficer['position'] ?? 'N/A');  // Lower rank approving officer position
        
        // Add individual approval status indicators
        // Show approval status for each approver
        
        // Unit Head approval status (if applicable to this role)
        if (!is_null($travelOrder->head_approved)) {
            $status = $travelOrder->head_approved ? 'APPROVED' : 'DECLINED/CANCELLED';
            $timestamp = $travelOrder->head_approved_at ? $travelOrder->head_approved_at->format('M j, Y g:i A') : '';
            // Add indicator next to Unit Head name (you can add this to a specific cell if needed)
        }
        
        // Division Head approval status
        if (!is_null($travelOrder->divisionhead_approved)) {
            $status = $travelOrder->divisionhead_approved ? 'APPROVED' : 'DECLINED/CANCELLED';
            $timestamp = $travelOrder->divisionhead_approved_at ? $travelOrder->divisionhead_approved_at->format('M j, Y g:i A') : '';
            // Add indicator next to Division Head name
        }
        
        // VP approval status
        if (!is_null($travelOrder->vp_approved)) {
            $status = $travelOrder->vp_approved ? 'APPROVED' : 'DECLINED/CANCELLED';
            $timestamp = $travelOrder->vp_approved_at ? $travelOrder->vp_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A') : '';
            // Add indicator next to VP name
        }
        
        // President approval status
        if (!is_null($travelOrder->president_approved)) {
            $status = $travelOrder->president_approved ? 'APPROVED' : 'DECLINED/CANCELLED';
            $timestamp = $travelOrder->president_approved_at ? $travelOrder->president_approved_at->format('M j, Y g:i A') : '';
            // Add indicator next to President name
        }
        
        // Add approval status in cells H17 and I43
        // For unit head travel orders: H17 should show VP approval status when approved by VP
        // For unit head travel orders in President's Office: H17 should show President approval status
        // For division head travel orders: I43 should show VP approval status
        // H17: Show appropriate approval status based on office and approver
        
        // Check if this is a Unit Head travel order
        if ($travelOrder->employee->is_head && !$travelOrder->employee->is_divisionhead) {
            // Unit head travel order - determine approver based on office
            $travelOrderPosition = $travelOrder->position;
            $isPresidentsOffice = false;
            
            if ($travelOrderPosition && $travelOrderPosition->office) {
                $officeName = strtolower($travelOrderPosition->office->office_name);
                $isPresidentsOffice = (strpos($officeName, 'office of the university president') !== false || 
                                      strpos($officeName, 'office of the president') !== false);
            }
            
            if ($isPresidentsOffice) {
                // Unit head in President's Office - show President approval status in H17
                if ($travelOrder->president_approved_at) {
                    $presidentStatus = $travelOrder->president_approved ? 'APPROVED' : 'DECLINED';
                    // Convert to Philippine Time for user context
                    $phtTimestamp = $travelOrder->president_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
                    $sheet->setCellValue('H17', $presidentStatus . ' ' . $phtTimestamp);
                }
            } else {
                // Regular Unit Head - show VP approval status in H17 when VP has approved
                if ($travelOrder->vp_approved_at) {
                    $vpStatus = $travelOrder->vp_approved ? 'APPROVED' : 'DECLINED';
                    // Convert to Philippine Time for user context
                    $phtTimestamp = $travelOrder->vp_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
                    $sheet->setCellValue('H17', $vpStatus . ' ' . $phtTimestamp);
                }
            }
        } else {
            // For regular employees, show Unit Head approval status in H17 when applicable
            if ($travelOrder->head_approved_at) {
                $unitHeadStatus = $travelOrder->head_approved ? 'APPROVED' : 'DECLINED';
                // Convert to Philippine Time for user context
                $phtTimestamp = $travelOrder->head_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
                $sheet->setCellValue('H17', $unitHeadStatus . ' ' . $phtTimestamp);
            }
        }
        
        // I43: Show Division Head approval status for unit head travel orders
        //      For unit head travel orders, division head approval status should appear in I43 (not H17)
        if ($travelOrder->employee->is_head && !$travelOrder->employee->is_divisionhead) {
            // Unit head travel order - show division head approval status in I43
            if ($travelOrder->divisionhead_approved_at) {
                $divisionHeadStatus = $travelOrder->divisionhead_approved ? 'APPROVED' : 'DECLINED';
                // Convert to Philippine Time for user context
                $phtTimestamp = $travelOrder->divisionhead_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
                $sheet->setCellValue('I43', $divisionHeadStatus . ' ' . $phtTimestamp);
            }
        } else {
            // For other employees (regular employees, division heads, etc.), show VP or President approval status in I43
            if ($travelOrder->vp_approved_at) {
                $vpStatus = $travelOrder->vp_approved ? 'APPROVED' : 'DECLINED';
                $vpTimestamp = $travelOrder->vp_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
                $sheet->setCellValue('I43', $vpStatus . ' ' . $vpTimestamp);
            }
            
            // For President approval, show in I43 if no VP approval
            if ($travelOrder->president_approved_at && !$travelOrder->vp_approved_at) {
                $presidentStatus = $travelOrder->president_approved ? 'APPROVED' : 'DECLINED';
                $presidentTimestamp = $travelOrder->president_approved_at->format('M j, Y g:i A');
                $sheet->setCellValue('I43', $presidentStatus . ' ' . $presidentTimestamp);
            }
        }
        
        // NEW APPROVER SYSTEM: Show approver1 and approver2 approval/cancellation status
        // - If 2 approvers: Approver 1 (higher) stamp in H17, Approver 2 (lower) stamp in I43
        // - If 1 approver only: Display stamp in I43 (matching the single approver position)
        
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
                    // I43 remains empty or shows previous status if any
                } elseif ($travelOrder->rejected_by_employee_id == $travelOrder->approver2_employee_id) {
                    // Approver 2 (lower) rejected - show in I43
                    $sheet->setCellValue('I43', $cancellationText);
                    // H17 remains empty or shows previous status if any
                }
            } elseif ($travelOrder->approver1_employee_id) {
                // Only one approver (approver1): Show in H17
                if ($travelOrder->rejected_by_employee_id == $travelOrder->approver1_employee_id) {
                    $sheet->setCellValue('H17', $cancellationText);
                }
            }
            
            // Add rejection reason if available
            if ($travelOrder->rejection_reason) {
                $sheet->setCellValue('H18', 'Reason: ' . substr($travelOrder->rejection_reason, 0, 50));
                if (strlen($travelOrder->rejection_reason) > 50) {
                    $sheet->setCellValue('H19', substr($travelOrder->rejection_reason, 50, 50));
                }
            }
        } elseif ($travelOrder->approver1_employee_id && $travelOrder->approver2_employee_id) {
            // Two approvers: H17 for Approver 1 (higher), I43 for Approver 2 (lower)
            if ($travelOrder->approver1_approved_at) {
                $sheet->setCellValue('H17', 'APPROVED ' . $travelOrder->approver1_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A'));
            }
            if ($travelOrder->approver2_approved_at) {
                $sheet->setCellValue('I43', 'APPROVED ' . $travelOrder->approver2_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A'));
            }
        } elseif ($travelOrder->approver1_employee_id) {
            // Only one approver: Display stamp in I43
            if ($travelOrder->approver1_approved_at) {
                $sheet->setCellValue('I43', 'APPROVED ' . $travelOrder->approver1_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A'));
            }
        }
        
        // Save Excel temporarily and return it
        $excelTemp = tempnam(sys_get_temp_dir(), 'travel_order_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($excelTemp);
        
        // Return Excel response for preview
        return response(file_get_contents($excelTemp))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="Unit_Head_Travel_Order_' . $travelOrder->id . '.xlsx"')
            ->header('Cache-Control', 'max-age=0')
            ->header('Pragma', 'public')
            ->header('Expires', '0');
    }
        
    /**
     * Get the correct approving VP based on organizational hierarchy
     * 
     * @param mixed $position The employee position
     * @return mixed The approving VP employee or null
     */
    private function getApprovingVP($position)
    {
        if (!$position) {
            return null;
        }
            
        // Get the office of the position
        $office = $position->office;
        if (!$office) {
            return null;
        }
            
        // Check if this is the President's Office (no VP, goes directly to President)
        if (stripos($office->office_name, 'President') !== false) {
            // Return null - no VP approval needed, goes directly to President
            return null;
        }
            
        // For other offices, find the VP in charge of that office
        // This would typically be based on office_id matching VP's primary position
        $vp = \App\Models\Employee::whereHas('officer', function($query) {
            $query->where('vp', true);
        })->whereHas('positions', function($query) use ($office) {
            $query->where('office_id', $office->id_office);
        })->first();
            
        // Fallback: if no specific VP found, get any VP
        if (!$vp) {
            $vp = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('vp', true);
            })->first();
        }
            
        return $vp;
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
                $unitHead = \App\Models\Employee::whereHas('positions', function($query) use ($employeePosition) {
                    $query->where('is_unit_head', true)
                          ->where('unit_id', $employeePosition->unit_id)
                          ->where('employee_id', '!=', $employeePosition->employee_id);
                })->first();
                
                // If no Unit Head found in the same unit, try to find any Unit Head
                if (!$unitHead) {
                    $unitHead = \App\Models\Employee::whereHas('positions', function($query) {
                        $query->where('is_unit_head', true);
                    })->orWhereHas('officer', function($query) {
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
                    // Only go to President if it's specifically the Office of the University President
                    $officeName = strtolower($employeePosition->office->office_name);
                    if (strpos($officeName, 'office of the university president') !== false || 
                        strpos($officeName, 'office of the president') !== false) {
                        // President's Office - go directly to President
                        return \App\Models\Employee::whereHas('officer', function($query) {
                            $query->where('president', true);
                        })->first();
                    }
                }
                // Regular office (including VP offices) - find VP
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
        $head = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('unit_head', true);
            })
            ->whereHas('positions', function($query) use ($employee) {
                $query->where('unit_id', $employee->getUnitIdAttribute());
            })->first();
        
        if ($head) {
            $name = $head->first_name . ' ' . $head->last_name;
            $position = $head->position ? $head->position->position_name : 'Unit Head';
        } else {
            // Fallback to any unit head
            $head = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('unit_head', true);
            })->first();
            
            if ($head) {
                $name = $head->first_name . ' ' . $head->last_name;
                $position = $head->position ? $head->position->position_name : 'Unit Head';
            }
        }
        
        return ['name' => $name, 'position' => $position];
    }
}