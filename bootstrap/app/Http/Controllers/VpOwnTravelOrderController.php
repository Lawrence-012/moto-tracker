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
use DateTimeZone;

class VpOwnTravelOrderController extends Controller
{
    /**
     * Display a listing of the VP's own travel orders.
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
                // For VP, draft means status is still draft (not yet submitted)
                $query->where('status', 'draft');
                break;
            case 'pending':
                // For VP, pending means status is 'pending' and waiting for approval
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
                // For VP, approved means status is 'approved'
                // Show ALL approved travel orders where the VP is the traveler
                // Even if only the President approved (and there's no second approver)
                $query->where('status', 'approved');
                break;
            case 'completed':
                // For VP, completed means status is 'completed'
                $query->where('status', 'completed');
                break;
            case 'cancelled':
                // For VP, cancelled means status is cancelled
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
                
                // Get all positions for this officer
                $officerPositions = $officerEmployee->positions()->get();
                
                foreach ($officerPositions as $position) {
                    // Check if this position has VP or Division Head or Unit Head role
                    if ($position->is_vp) {
                        $officerOffices['vp'][] = $position->office ? $position->office->office_name : 'N/A';
                    }
                    if ($position->is_division_head) {
                        $officerOffices['division_head'][] = $position->division ? $position->division->division_name : 'N/A';
                    }
                    if ($position->is_unit_head) {
                        $officerOffices['unit_head'][] = $position->unit ? $position->unit->unit_name : 'N/A';
                    }
                    if ($position->is_president) {
                        $officerOffices['president'][] = $position->office ? $position->office->office_name : 'N/A';
                    }
                }
                
                $officers[] = [
                    'id' => $officerEmployee->id,
                    'name' => $officerEmployee->first_name . ' ' . $officerEmployee->last_name,
                    'position' => $officerEmployee->position ? $officerEmployee->position->position_name : 'N/A',
                    'employee' => [
                        'id' => $officerEmployee->id,
                        'first_name' => $officerEmployee->first_name,
                        'last_name' => $officerEmployee->last_name,
                        'positions' => $officerEmployee->positions()->get()->map(function($pos) {
                            return [
                                'id' => $pos->id,
                                'office_id' => $pos->office_id,
                                'division_id' => $pos->division_id,
                                'unit_id' => $pos->unit_id,
                                'position_name' => $pos->position_name,
                                'is_vp' => $pos->is_vp,
                                'is_division_head' => $pos->is_division_head,
                                'is_unit_head' => $pos->is_unit_head,
                                'is_president' => $pos->is_president,
                            ];
                        })
                    ],
                    'officer' => [
                        'unit_head' => $officerRecord->unit_head,
                        'division_head' => $officerRecord->division_head,
                        'vp' => $officerRecord->vp,
                        'president' => $officerRecord->president,
                        'primary_role' => $officerRecord->primary_role,
                        'role_names' => $officerRecord->role_names,
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
            'departure_time' => 'nullable|date_format:H:i',
            'purpose' => 'required|string|max:500',
            'position_id' => 'required|exists:emp_positions,id',
        ]);
        
        $user = Auth::user();
        $employee = $user->employee;
                
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record.');
        }
        
        // Verify that the selected position belongs to the employee
        $position = $employee->positions()->where('id', $request->position_id)->first();
        if (!$position) {
            return redirect()->back()
                ->with('error', 'Invalid position selected.')
                ->withInput();
        }
        
        // Process approvers from University President dropdown (VPs only select President as approver)
        // When President is the only approver, assign them to BOTH approver1 and approver2 for dual stamping
        $approver1 = null;
        $approver2 = null;
        
        if (!empty($request->president_approver)) {
            // Check if the selected approver is the President
            $presidentEmployee = Employee::find($request->president_approver);
            $isPresident = $presidentEmployee && $presidentEmployee->positions && 
                          $presidentEmployee->positions->contains('is_president', true);
            
            if ($isPresident) {
                // President is the only approver - assign to BOTH for dual stamping
                $approver1 = $request->president_approver;
                $approver2 = $request->president_approver;
            } else {
                // Fallback for non-President approvers (shouldn't happen for VPs)
                $approver2 = $request->president_approver;
            }
        }
        
        // Create the travel order as DRAFT
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
            'departure_time' => $request->departure_time,
            'purpose' => $request->purpose,
            'status' => 'draft',
            'approver1_employee_id' => $approver1,
            'approver2_employee_id' => $approver2,
        ]);
        
        return redirect()->route('vp.travel-orders.index', ['tab' => 'draft'])
            ->with('success', 'Travel order created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TravelOrder $travelOrder): View
    {
        // Ensure the VP can only view their own travel orders
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
        // Ensure the VP can only edit their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow editing if not yet approved by president
        if (!is_null($travelOrder->president_approved)) {
            abort(403);
        }
        
        return view('travel-orders.edit', compact('travelOrder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        // Ensure the VP can only update their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow updating if not yet approved by president
        if (!is_null($travelOrder->president_approved)) {
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
            'departure_time' => 'nullable|date_format:H:i',
            'purpose' => 'required|string|max:500',
        ]);

        $travelOrder->update([
            'destination' => $request->destination,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'region' => $request->region,
            'distance_km' => $request->distance_km,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'departure_time' => $request->departure_time,
            'purpose' => $request->purpose,
        ]);

        return redirect()->route('vp.travel-orders.index', ['tab' => 'pending'])
            ->with('success', 'Travel order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TravelOrder $travelOrder): RedirectResponse
    {
        // Ensure the VP can only delete their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow deleting if not yet approved by president
        if (!is_null($travelOrder->president_approved)) {
            abort(403);
        }
        
        $travelOrder->delete();

        return redirect()->route('vp.travel-orders.index', ['tab' => 'pending'])
            ->with('success', 'Travel order deleted successfully.');
    }
    
    /**
     * Generate PDF for VP's own travel order
     */
    public function generateExcel(TravelOrder $travelOrder): Response
    {
        // Ensure the VP can only view their own travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Load the travel order with all necessary relationships
        $travelOrder->load([
            'employee', 
            'employee.user',
            'position',
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
        
        // Load the Excel template
        $templatePath = public_path('templates/travel_order_template.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Fill the Excel cells with data
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
        $sheet->setCellValue('H34', '');  // Arrival Time (not in model)
        
        // Set the approvers in the correct cells based on approver1 and approver2 columns
        // For VP's own travel orders:
        // - If 2 approvers: Higher rank (Approver 1) in H19/H20, Lower rank (Approver 2) in I45/I46
        // - If 1 approver only (in approver2): Display in I45/I46, H19/H20 shows N/A
        
        if ($travelOrder->approver1_employee_id && $travelOrder->approver2_employee_id) {
            // Two approvers: Higher rank in H19/H20, Lower rank in I45/I46
            $approver1Employee = Employee::with('positions')->find($travelOrder->approver1_employee_id);
            if ($approver1Employee) {
                $positionData = $approver1Employee->positions->first();
                $sheet->setCellValue('H19', $approver1Employee->first_name . ' ' . $approver1Employee->last_name);
                $sheet->setCellValue('H20', $positionData ? $positionData->position_name : 'N/A');
            }
            
            $approver2Employee = Employee::with('positions')->find($travelOrder->approver2_employee_id);
            if ($approver2Employee) {
                $positionData = $approver2Employee->positions->first();
                $sheet->setCellValue('I45', $approver2Employee->first_name . ' ' . $approver2Employee->last_name);
                $sheet->setCellValue('I46', $positionData ? $positionData->position_name : 'N/A');
            }
        } elseif ($travelOrder->approver2_employee_id && !$travelOrder->approver1_employee_id) {
            // Only one approver (in approver2): Display in I45/I46, H19/H20 = N/A
            $sheet->setCellValue('H19', 'N/A');
            $sheet->setCellValue('H20', 'N/A');
            
            $approver2Employee = Employee::with('positions')->find($travelOrder->approver2_employee_id);
            if ($approver2Employee) {
                $positionData = $approver2Employee->positions->first();
                $sheet->setCellValue('I45', $approver2Employee->first_name . ' ' . $approver2Employee->last_name);
                $sheet->setCellValue('I46', $positionData ? $positionData->position_name : 'N/A');
            }
        } else {
            $president = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('president', true);
            })->first();
            
            $presidentName = $president ? $president->first_name . ' ' . $president->last_name : 'N/A';
            $presidentPosition = $president && $president->position ? $president->position->position_name : 'University President';
            
            $sheet->setCellValue('H19', $presidentName);
            $sheet->setCellValue('H20', $presidentPosition);
            $sheet->setCellValue('I45', $presidentName);
            $sheet->setCellValue('I46', $presidentPosition);
        }
        
        $sheet->setCellValue('I41', $fullName);  // Employee name (signatory)
        $sheet->setCellValue('I42', $positionName);  // Employee position (signatory)
        
        // President approval status - shown when President has approved VP's travel order
        // For VP's own travel orders, President is the approving authority
        // When President approves, both H17 and I43 should show the approval status
        
        // President approval status (H17) - shown when President has approved
        if ($travelOrder->president_approved_at) {
            $presidentStatus = $travelOrder->president_approved ? 'APPROVED' : 'DECLINED';
            $presidentTimestamp = $travelOrder->president_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
            $sheet->setCellValue('H17', $presidentStatus . ' ' . $presidentTimestamp);
        }
        
        // President approval status (I43) - also shown when President has approved (for VP format)
        if ($travelOrder->president_approved_at) {
            $presidentStatus = $travelOrder->president_approved ? 'APPROVED' : 'DECLINED';
            $presidentTimestamp = $travelOrder->president_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
            $sheet->setCellValue('I43', $presidentStatus . ' ' . $presidentTimestamp);
        }
        
        // NEW APPROVER SYSTEM: Show approver1 and approver2 approval status based on actual columns:
        // - If 2 approvers: Approver 1 (higher) stamp in H17, Approver 2 (lower) stamp in I43
        // - If 1 approver only (in approver2): Display stamp in I43
        
        if ($travelOrder->status === 'cancelled') {
            // CANCELLED STAMP - Show rejection information
            $cancellationText = 'CANCELLED';
            if ($travelOrder->rejected_at) {
                $cancellationText .= ' ' . $travelOrder->rejected_at->timezone('Asia/Manila')->format('M j, Y g:i A');
            }
            
            // Display CANCELLED stamp in both approver positions
            $sheet->setCellValue('H17', $cancellationText);
            $sheet->setCellValue('I43', $cancellationText);
            
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
        } elseif ($travelOrder->approver2_employee_id && !$travelOrder->approver1_employee_id) {
            // Only one approver (in approver2): Display stamp in I43
            if ($travelOrder->approver2_approved_at) {
                $sheet->setCellValue('I43', 'APPROVED ' . $travelOrder->approver2_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A'));
            }
        }
        
        // Save Excel temporarily and return it
        $excelTemp = tempnam(sys_get_temp_dir(), 'vp_travel_order_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($excelTemp);
        
        // Return Excel response for preview
        return response(file_get_contents($excelTemp))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="VP_Travel_Order_' . $travelOrder->id . '.xlsx"')
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