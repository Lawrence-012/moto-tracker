<?php

namespace App\Http\Controllers;

use App\Models\TravelOrder;
use App\Models\EmpPosition;
use App\Models\Employee;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use DateTimeZone;

class DivisionHeadOwnTravelOrderController extends Controller
{
    /**
     * Display a listing of the travel orders.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        $tab = $request->get('tab', 'draft');

        $search = $request->get('search', '');

        // Get all travel orders for the current employee
        $query = TravelOrder::where('employee_id', $employee->id);

        // Apply search filter if provided
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('destination', 'LIKE', "%{$search}%")
                  ->orWhere('purpose', 'LIKE', "%{$search}%")
                  ->orWhere('status', 'LIKE', "%{$search}%")
                  ->orWhere(function($dateQuery) use ($search) {
                      $parsedDate = date('Y-m-d', strtotime($search));
                      if ($parsedDate && $parsedDate !== '1970-01-01') {
                          $dateQuery->whereDate('date_from', '=', $parsedDate)
                                    ->orWhereDate('date_to', '=', $parsedDate);
                      }
                      
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
                // For division heads, draft means status is still draft (not yet submitted)
                $query->where('status', 'draft');
                break;
            case 'pending':
                // For division heads, pending means status is 'pending' and waiting for approval
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
                // For division heads, approved means status is 'approved' and both approvers have approved
                $query->where('status', 'approved')
                      ->whereNotNull('approver1_approved_at')
                      ->whereNotNull('approver2_approved_at');
                break;
            case 'completed':
                // For division heads, completed means status is 'completed'
                $query->where('status', 'completed');
                break;
            case 'cancelled':
                // For division heads, cancelled means status is cancelled
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

        $travelOrders = $query->with('position', 'employee')->orderBy('created_at', 'desc')->paginate(10)->appends(['tab' => $tab, 'search' => $search]);
       
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
                $officerOffices = [
                    'vp' => [],
                    'division_head' => [],
                    'unit_head' => [],
                    'president' => []
                ];
                
                foreach ($officerEmployee->positions as $pos) {
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
     * Show the form for creating a new travel order.
     */
    public function create(): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Get all positions for this employee
        $positions = $employee->positions;

        return view('travel-orders.create', compact('positions'));
    }

    /**
     * Store a newly created travel order in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Validate that the user is a division head
        if (!$employee->is_divisionhead) {
            return Redirect::route('dashboard')
                ->with('error', 'Only division heads can create their own travel orders.');
        }

        $request->validate([
            'emp_position_id' => 'required|exists:emp_positions,id',
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

        // Create the travel order with initial status as pending
        $travelOrder = TravelOrder::create([
            'employee_id' => $employee->id,
            'emp_position_id' => $request->emp_position_id,
            'destination' => $request->destination,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'region' => $request->region,
            'distance_km' => $request->distance_km,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'departure_time' => $request->departure_time,
            'purpose' => $request->purpose,
            'status' => 'pending', // Initially pending
        ]);

        return Redirect::route('division-head-own-travel-orders.index')
            ->with('success', 'Travel order created successfully. Awaiting VP approval.');
    }

    /**
     * Display the specified travel order.
     */
    public function show(TravelOrder $travelOrder): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Ensure the logged-in user is the owner of the travel order
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }

        // Ensure the user is a division head
        if (!$employee->is_divisionhead) {
            return Redirect::route('dashboard')
                ->with('error', 'Access denied. Only division heads can view their own travel orders.');
        }

        return view('travel-orders.show', compact('travelOrder'));
    }

    /**
     * Show the form for editing the specified travel order.
     */
    public function edit(TravelOrder $travelOrder): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Ensure the logged-in user is the owner of the travel order
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }

        // Ensure the user is a division head
        if (!$employee->is_divisionhead) {
            return Redirect::route('dashboard')
                ->with('error', 'Access denied. Only division heads can edit their own travel orders.');
        }

        // Check if the travel order can be edited (not yet approved by anyone)
        if (!is_null($travelOrder->vp_approved)) {
            return Redirect::back()
                ->with('error', 'Cannot edit travel order after VP approval has started.');
        }

        $positions = $employee->positions;

        return view('travel-orders.edit', compact('travelOrder', 'positions'));
    }

    /**
     * Update the specified travel order in storage.
     */
    public function update(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Ensure the logged-in user is the owner of the travel order
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }

        // Ensure the user is a division head
        if (!$employee->is_divisionhead) {
            return Redirect::route('dashboard')
                ->with('error', 'Access denied. Only division heads can update their own travel orders.');
        }

        // Check if the travel order can be updated (not yet approved by anyone)
        if (!is_null($travelOrder->vp_approved)) {
            return Redirect::back()
                ->with('error', 'Cannot update travel order after VP approval has started.');
        }

        $request->validate([
            'emp_position_id' => 'required|exists:emp_positions,id',
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
            'emp_position_id' => $request->emp_position_id,
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

        return Redirect::route('division-head-own-travel-orders.index')
            ->with('success', 'Travel order updated successfully.');
    }

    /**
     * Remove the specified travel order from storage.
     */
    public function destroy(TravelOrder $travelOrder): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Ensure the logged-in user is the owner of the travel order
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }

        // Ensure the user is a division head
        if (!$employee->is_divisionhead) {
            return Redirect::route('dashboard')
                ->with('error', 'Access denied. Only division heads can delete their own travel orders.');
        }

        // Check if the travel order can be deleted (not yet approved by anyone)
        if (!is_null($travelOrder->vp_approved)) {
            return Redirect::back()
                ->with('error', 'Cannot delete travel order after VP approval has started.');
        }

        $travelOrder->delete();

        return Redirect::route('division-head-own-travel-orders.index')
            ->with('success', 'Travel order deleted successfully.');
    }
    
    /**
     * Generate PDF for division head's own travel order
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
            
            // Load employee's primary position to check their role and division
            $employee->load(['positions']);
            $primaryPosition = $employee->positions()->where('is_primary', true)->first();
        }
        
        // Check if user has permission to view this travel order PDF
        $isOwn = !$isAdmin && $travelOrder->employee_id === $employee->id;
        
        // Check if user is the President (can view all travel orders)
        $isPresident = $employee->is_president;
        
        // Check approval authority based on travel order's position
        $hasApprovalAuthority = false;
        $travelOrderPosition = $travelOrder->position;
        
        if ($primaryPosition && $travelOrderPosition) {
            // Division head can view travel orders from their division
            if ($primaryPosition->is_division_head && 
                !$primaryPosition->is_vp && !$primaryPosition->is_president &&
                $travelOrderPosition->division_id === $primaryPosition->division_id) {
                $hasApprovalAuthority = true;
            }
            
            // VP can view travel orders from their office (including Division Head travel orders)
            if ($primaryPosition->is_vp && !$primaryPosition->is_president &&
                $travelOrderPosition->office_id === $primaryPosition->office_id) {
                $hasApprovalAuthority = true;
            }
        }
        
        if (!$isOwn && !$isAdmin && !$isPresident && !$hasApprovalAuthority) {
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
        
        // Load Excel template
        $templatePath = public_path('templates/travel_order_template.xlsx');
        if (!file_exists($templatePath)) {
            abort(500, 'Travel order template not found');
        }
        
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
        $sheet->setCellValue('I41', $fullName);  
        $sheet->setCellValue('I42', $positionName);
        
        // Get the position associated with this travel order
        $travelOrderPosition = $travelOrder->position;
        
        // For division heads: H19 = President, I45 = VP (or President if no VP and under President's Office)
        
        // Get President (H19) - always the President for division heads
        $president = $this->getApproverEmployee('PRESIDENT', $travelOrderPosition);
        $presidentName = '';
        $presidentPosition = '';
        
        if ($president) {
            $presidentName = $president->first_name . ' ' . $president->last_name;
            $presidentPosition = $president->position ? $president->position->position_name : 'University President';
        } else {
            // Fallback: Find any President in the system
            $president = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('president', true);
            })->first();
            
            if ($president) {
                $presidentName = $president->first_name . ' ' . $president->last_name;
                $presidentPosition = $president->position ? $president->position->position_name : 'University President';
            }
        }
        
        // Get VP or President for I45
        $vpOrPresident = null;
        $vpOrPresidentName = '';
        $vpOrPresidentPosition = '';
        
        // Check if this is President's Office
        $isPresidentsOffice = false;
        if ($travelOrderPosition && $travelOrderPosition->office) {
            $officeName = strtolower($travelOrderPosition->office->office_name);
            if (strpos($officeName, 'office of the university president') !== false || 
                strpos($officeName, 'office of the president') !== false) {
                $isPresidentsOffice = true;
            }
        }
        
        if ($isPresidentsOffice) {
            // If it's President's Office, use President for I45 as well
            $vpOrPresident = $president;
            $vpOrPresidentName = $presidentName;
            $vpOrPresidentPosition = $presidentPosition;
        } else {
            // Otherwise, try to find VP
            $vp = $this->getApproverEmployee('VP', $travelOrderPosition);
            if ($vp) {
                $vpOrPresident = $vp;
                $vpOrPresidentName = $vp->first_name . ' ' . $vp->last_name;
                $vpOrPresidentPosition = $vp->position ? $vp->position->position_name : 'Vice President';
            } else {
                // If no VP found, fallback to President
                $vpOrPresident = $president;
                $vpOrPresidentName = $presidentName;
                $vpOrPresidentPosition = $presidentPosition;
            }
        }
        
        // Set the approvers in the correct cells based on approver1 and approver2 columns
        // For Division Head's own travel orders:
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
            // No approvers assigned - use old fallback logic
            $sheet->setCellValue('H19', $presidentName ?: 'N/A');
            $sheet->setCellValue('H20', $presidentPosition ?: 'N/A');
            $sheet->setCellValue('I45', $vpOrPresidentName ?: 'N/A');
            $sheet->setCellValue('I46', $vpOrPresidentPosition ?: 'N/A');
        }
        
        // Add approval status indicators
        // For Division Head travel orders:
        // I17: Show President approval status (highest level approver)
        // K43: Show VP approval status (first approver)
        // When President has approved, BOTH I17 and K43 show status (as requested)
        
        // President approval status (I17) - always shown when President has approved
        if ($travelOrder->president_approved_at) {
            $presidentStatus = $travelOrder->president_approved ? 'APPROVED' : 'DECLINED';
            $presidentTimestamp = $travelOrder->president_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
            $sheet->setCellValue('H17', $presidentStatus . ' ' . $presidentTimestamp);
        }
        
        // VP approval status (K43) - always shown when VP has approved
        if ($travelOrder->vp_approved_at) {
            $vpStatus = $travelOrder->vp_approved ? 'APPROVED' : 'DECLINED';
            $vpTimestamp = $travelOrder->vp_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
            $sheet->setCellValue('I43', $vpStatus . ' ' . $vpTimestamp);
        }
        
        // Special case: When President has approved, also show President status in K43
        // This ensures both I17 and K43 have status stamps when President approves
        if ($travelOrder->president_approved_at && !$travelOrder->vp_approved_at) {
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
        } else {
            // Fallback to old system for backwards compatibility
            // President approval status (H17)
            if ($travelOrder->president_approved_at) {
                $presidentStatus = $travelOrder->president_approved ? 'APPROVED' : 'DECLINED';
                $presidentTimestamp = $travelOrder->president_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
                $sheet->setCellValue('H17', $presidentStatus . ' ' . $presidentTimestamp);
            }
            
            // VP approval status (I43)
            if ($travelOrder->vp_approved_at) {
                $vpStatus = $travelOrder->vp_approved ? 'APPROVED' : 'DECLINED';
                $vpTimestamp = $travelOrder->vp_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
                $sheet->setCellValue('I43', $vpStatus . ' ' . $vpTimestamp);
            }
            
            // Special case: When President has approved without VP
            if ($travelOrder->president_approved_at && !$travelOrder->vp_approved_at) {
                $presidentStatus = $travelOrder->president_approved ? 'APPROVED' : 'DECLINED';
                $presidentTimestamp = $travelOrder->president_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
                $sheet->setCellValue('I43', $presidentStatus . ' ' . $presidentTimestamp);
            }
        }
        
        // Save Excel temporarily and return it
        $excelTemp = tempnam(sys_get_temp_dir(), 'travel_order_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($excelTemp);
        
        // Return Excel response for preview
        return response(file_get_contents($excelTemp))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="Division_Head_Travel_Order_' . $travelOrder->id . '.xlsx"')
            ->header('Cache-Control', 'max-age=0')
            ->header('Pragma', 'public')
            ->header('Expires', '0');
    }
    
    /**
     * Get the actual approver employee based on role and position
     */
    private function getApproverEmployee($approverRole, $employeePosition)
    {
        switch ($approverRole) {
            case 'PRESIDENT':
                // Find President from officers table
                return \App\Models\Employee::whereHas('officer', function($query) {
                    $query->where('president', true);
                })->first();
                
            case 'VP':
                // Find VP in the same office as the employee position
                $vp = \App\Models\Employee::whereHas('officer', function($query) {
                    $query->where('vp', true);
                })
                ->whereHas('positions', function($query) use ($employeePosition) {
                    $query->where('office_id', $employeePosition->office_id);
                })->first();
                
                // If no VP in same office, find any VP
                if (!$vp) {
                    $vp = \App\Models\Employee::whereHas('officer', function($query) {
                        $query->where('vp', true);
                    })->first();
                }
                
                return $vp;
                
            default:
                return null;
        }
    }
}