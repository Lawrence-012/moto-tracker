<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use App\Models\TravelOrder;
use App\Models\Employee;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\JsonResponse;
use DateTimeZone;

class VpTravelOrderController extends Controller
{
    /**
     * Display a listing of travel orders for VP approval with tab support.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Ensure the user is a VP
        if (!$employee->is_vp) {
            abort(403);
        }
        
        // All VPs can access travel order approvals now
        
        // Get the tab parameter, default to 'pending'
        $tab = $request->get('tab', 'pending');
        
        // Get search term if provided
        $search = $request->get('search', '');
        
        $query = TravelOrder::query();
        
        // Apply search filter if provided
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('destination', 'LIKE', "%{$search}%")
                  ->orWhere('purpose', 'LIKE', "%{$search}%")
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
                  })
                  ->orWhereHas('employee', function($q) use ($search) {
                      $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        switch ($tab) {
            case 'approved':
                // Show travel orders where this employee has already approved
                $query->where('status', '!=', 'cancelled')
                      ->where('status', '!=', 'draft')
                      ->where(function($q) use ($employee) {
                          $q->where(function($appr1) use ($employee) {
                              // Employee approved as Approver 1
                              $appr1->where('approver1_employee_id', $employee->id)
                                    ->whereNotNull('approver1_approved_at');
                          })->orWhere(function($appr2) use ($employee) {
                              // Employee approved as Approver 2
                              $appr2->where('approver2_employee_id', $employee->id)
                                    ->whereNotNull('approver2_approved_at');
                          });
                      });
                break;
            case 'cancelled':
                // Show cancelled travel orders where THIS employee either:
                // 1. Already approved it (as approver1 or approver2) and it was later cancelled/rejected
                // 2. OR they were the one who rejected it
                // BUT NOT their own travel requests
                $query->where('status', 'cancelled')
                    ->where('employee_id', '!=', $employee->id) // Exclude own travel requests
                    ->where(function($q) use ($employee) {
                        // Either they already approved it
                        $q->where(function($approvedQ) use ($employee) {
                            $approvedQ->where(function($appr1) use ($employee) {
                                $appr1->where('approver1_employee_id', $employee->id)
                                      ->whereNotNull('approver1_approved_at');
                            })->orWhere(function($appr2) use ($employee) {
                                $appr2->where('approver2_employee_id', $employee->id)
                                      ->whereNotNull('approver2_approved_at');
                            });
                        })
                        // OR they rejected it
                        ->orWhere('rejected_by_employee_id', $employee->id);
                    });
                break;
            case 'pending':
            default:
                // Show travel orders where the VP is selected as either approver1 or approver2 and hasn't approved yet
                $employeeId = $employee->id;
                
                $query->where('status', '!=', 'cancelled')
                      ->where('status', '!=', 'draft')
                      ->where(function($q) use ($employeeId) {
                          $q->where(function($approver2Queue) use ($employeeId) {
                              // VP is Approver 2 (approves FIRST - lower rank in this context)
                              // Shows when: VP is approver2 AND hasn't approved yet
                              $approver2Queue->where('approver2_employee_id', $employeeId)
                                    ->whereNull('approver2_approved_at');
                          })->orWhere(function($approver1Queue) use ($employeeId) {
                              // VP is Approver 1 (approves SECOND/FINAL - higher rank in this context)
                              // Shows when: VP is approver1 AND 
                              // (there is no approver2 OR approver2 has already approved) AND VP hasn't approved yet
                              $approver1Queue->where('approver1_employee_id', $employeeId)
                                    ->whereNull('approver1_approved_at')
                                    ->where(function($condition) {
                                        // Either there's no approver2 (single approver scenario)
                                        // OR approver2 has already approved (dual approver scenario)
                                        $condition->whereNull('approver2_employee_id')
                                                  ->orWhereNotNull('approver2_approved_at');
                                    });
                          });
                      });
                break;
        }
        
        // Get paginated results with position information
        // Order by latest approval timestamp (prefer approver2, then approver1, then created_at)
        $travelOrders = $query->with('position', 'employee')
            ->orderByRaw('COALESCE(approver2_approved_at, approver1_approved_at, created_at) DESC')
            ->paginate(10)
            ->appends($request->except('page'));
        
        // Check if this is an AJAX request for partial updates
        if ($request->ajax() || $request->get('ajax')) {
            $approvalShowRoute = 'travel-orders.approval-show.vp';
            return response()->json([
                'table_body' => view('travel-orders.approvals.partials.table-rows', compact('travelOrders', 'tab', 'approvalShowRoute'))->with('travelOrders', $travelOrders->load('position', 'employee'))->render(),
                'pagination' => (string) $travelOrders->withQueryString()->links()
            ]);
        }
        
        $approvalShowRoute = 'travel-orders.approval-show.vp';
        return view('travel-orders.approvals.vp-index', compact('travelOrders', 'tab', 'search', 'approvalShowRoute'));
    }

    /**
     * Display the specified resource for approval.
     */
    public function show(TravelOrder $travelOrder): View
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Ensure the user is a VP
        if (!$employee->is_vp) {
            abort(403);
        }
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record. Please contact your administrator to set up your employee profile.');
        }
        
        // Check if the current user is selected as an approver (either approver1 or approver2)
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        
        // Also allow if it's the VP's own travel order
        $isOwn = $travelOrder->employee_id === $employee->id;
        
        // Allow motorpool admin to access all travel orders
        $isMotorpoolAdmin = $user->role === 'motorpool_admin';
        
        // Allow if travel order is from employee in VP's office (for oversight)
        $vpPrimaryPosition = $employee->positions()->where('is_primary', true)->first();
        $vpOfficeId = $vpPrimaryPosition ? $vpPrimaryPosition->office_id : null;
        $travelOrderPosition = $travelOrder->position;
        $travelOrderOfficeId = $travelOrderPosition ? $travelOrderPosition->office_id : null;
        $isFromOffice = $travelOrderOfficeId === $vpOfficeId;
        
        // For unit head travel orders, check if they're from the same division
        $isFromDivision = false;
        if ($travelOrder->employee->is_head && !$travelOrder->employee->is_divisionhead) {
            $vpDivisionId = $vpPrimaryPosition ? $vpPrimaryPosition->division_id : null;
            $travelOrderDivisionId = $travelOrderPosition ? $travelOrderPosition->division_id : null;
            $isFromDivision = $travelOrderDivisionId === $vpDivisionId;
        }
        
        // Check if employee is President (for President's Office travel orders)
        $isFromPresident = $travelOrder->employee->is_president;
        
        // For VPs: Also allow viewing any pending travel orders that need VP approval
        // This handles cases where the President created the request and selected this VP
        $needsVpApproval = $employee->is_vp && 
                          $travelOrder->status === 'pending' && 
                          ($travelOrder->approver1_employee_id === $employee->id || 
                           $travelOrder->approver2_employee_id === $employee->id);
        
        // Grant access if any of these conditions are met
        if (!$isApprover1 && !$isApprover2 && !$isFromOffice && !$isFromDivision && !$isOwn && !$isFromPresident && !$isMotorpoolAdmin && !$needsVpApproval) {
            abort(403);
        }
        
        // Set approval context flag - always use ConvertAPI for VP travel orders
        $approvalContext = true;
        
        return view('travel-orders.show', compact('travelOrder', 'approvalContext'));
    }
    
    /**
     * Approve a travel order.
     */
    public function approve(TravelOrder $travelOrder)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Ensure the user is a VP
        if (!$employee->is_vp) {
            abort(403);
        }
        
        // All VPs can approve travel orders now
        
        // Check if the VP is actually selected as an approver
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        
        if (!$isApprover1 && !$isApprover2) {
            abort(403, 'You are not selected as an approver for this travel order');
        }
        
        // If no position is assigned to the travel order, deny access
        $travelOrderPosition = $travelOrder->position;
        if (!$travelOrderPosition) {
            abort(403);
        }
        
        // Determine which approver role the VP has and check approval sequence
        if ($isApprover2) {
            // VP is Approver 2 (approves FIRST)
            // Just ensure not already approved
            if (!is_null($travelOrder->approver2_approved_at)) {
                abort(403, 'You have already approved this travel order');
            }
        } elseif ($isApprover1) {
            // VP is Approver 1 (approves SECOND/FINAL)
            // Ensure Approver 2 has already approved (if exists)
            if ($travelOrder->approver2_employee_id && !$travelOrder->approver2_approved_at) {
                abort(403, 'This travel order is still waiting for the first approver');
            }
            // Ensure not already approved
            if (!is_null($travelOrder->approver1_approved_at)) {
                abort(403, 'You have already approved this travel order');
            }
        }
        
        // Approve the travel order based on which approver role the VP has
        if ($isApprover2) {
            // VP is Approver 2 - update approver2 timestamp
            $travelOrder->update([
                'approver2_approved_at' => now(),
                'status' => $travelOrder->approver1_employee_id ? 'pending' : 'approved', // If there's an approver1, keep as pending; otherwise fully approved
            ]);
        } else {
            // VP is Approver 1 - update approver1 timestamp (final approval)
            $travelOrder->update([
                'approver1_approved_at' => now(),
                'status' => 'approved', // Final approval
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Travel order approved successfully.',
            'redirect_url' => url('/travel-orders/approvals/vp?tab=approved')
        ]);
    }

    /**
     * Reject a travel order.
     */
    public function reject(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Ensure the user is a VP
        if (!$employee->is_vp) {
            abort(403);
        }
        
        // All VPs can reject travel orders where they are selected as approver
        
        // Check if the VP is actually selected as an approver (either approver1 or approver2)
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        
        if (!$isApprover1 && !$isApprover2) {
            abort(403, 'You are not selected as an approver for this travel order');
        }
        
        // If no position is assigned to the travel order, deny access
        $travelOrderPosition = $travelOrder->position;
        if (!$travelOrderPosition) {
            abort(403);
        }
        
        // If travel order is in VP's pending queue, it means all previous approvals are done
        // Just ensure the VP hasn't already approved/rejected this travel order
        if ($isApprover2) {
            // VP is Approver 2 - check if already acted
            if (!is_null($travelOrder->approver2_approved_at)) {
                abort(403, 'You have already approved/rejected this travel order');
            }
        } elseif ($isApprover1) {
            // VP is Approver 1 - check if already acted
            if (!is_null($travelOrder->approver1_approved_at)) {
                abort(403, 'You have already approved/rejected this travel order');
            }
        }
        
        // Validate remarks (required)
        $validated = $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);
        
        // Reject the travel order with all details
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
     * Generate PDF for travel order in approval context
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
        } else {
            // For regular users, check their own employee record
            $employee = $user->employee;
            
            // Check if employee exists
            if (!$employee) {
                abort(403);
            }
        }
        
        // Check if user is the President (can view all travel orders for approval)
        $isPresident = $employee->is_president;
        
        // Check if VP is selected as an approver on this travel order
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        $isApprover = $isApprover1 || $isApprover2;
        
        // Check if the travel order belongs to an employee in the VP's office
        $vpPrimaryPosition = $employee->positions()->where('is_primary', true)->first();
        $vpOfficeId = $vpPrimaryPosition ? $vpPrimaryPosition->office_id : null;
        
        $travelOrderPosition = $travelOrder->position;
        
        // If no position is assigned to the travel order, deny access
        if (!$travelOrderPosition) {
            abort(403);
        }
        
        $travelOrderOfficeId = $travelOrderPosition->office_id;
        $travelOrderDivisionId = $travelOrderPosition->division_id;
        
        // For unit head travel orders, check if they're from the same division
        $isFromDivision = false;
        if ($travelOrder->employee->is_head && !$travelOrder->employee->is_divisionhead) {
            $vpPrimaryPosition = $employee->positions()->where('is_primary', true)->first();
            $vpDivisionId = $vpPrimaryPosition ? $vpPrimaryPosition->division_id : null;
            $isFromDivision = $travelOrderDivisionId === $vpDivisionId;
        }
        
        // Allow if travel order is from employee in VP's office
        $isFromOffice = $travelOrderOfficeId === $vpOfficeId;
        
        // Also allow if it's the VP's own travel order
        $isOwn = $travelOrder->employee_id === $employee->id;
        
        // Check if employee is President (for President's Office travel orders)
        $isFromPresident = $travelOrder->employee->is_president;
        
        // For VPs: Also allow viewing pending travel orders from President where VP is approver
        $needsVpApproval = $employee->is_vp && 
                          $travelOrder->status === 'pending' && 
                          ($travelOrder->approver1_employee_id === $employee->id || 
                           $travelOrder->approver2_employee_id === $employee->id);
        
        // Grant access if: from their office, own travel order, from same division, VP is approver, is President, is from President's office, or from President with VP as approver
        if (!$isFromOffice && !$isFromDivision && !$isOwn && !$isApprover && !$isPresident && !$isFromPresident && !$needsVpApproval) {
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
        
        // Get approver information for different employee types
        $supervisorName = 'N/A';
        $supervisorPosition = 'Supervisor';
        
        // Use the travel order's position, not the employee's primary position
        $travelOrderPosition = $travelOrder->position;
        
        if ($employee->is_head && !$employee->is_divisionhead) {
            // Unit head - approver is Division Head
            $approverEmployee = $this->getApproverEmployee('DIVISION_HEAD', $travelOrderPosition);
            $supervisorName = $approverEmployee ? $approverEmployee->first_name . ' ' . $approverEmployee->last_name : 'N/A';
            $supervisorPosition = $approverEmployee && $approverEmployee->position ? $approverEmployee->position->position_name : 'Division Head';
        } elseif ($employee->is_divisionhead) {
            // Division head - no immediate supervisor, goes directly to VP
            $supervisorName = '';
            $supervisorPosition = '';
        } elseif ($employee->is_vp && !$employee->is_president) {
            // VP - approver is President
            $approverEmployee = $this->getApproverEmployee('PRESIDENT', $travelOrderPosition);
            $supervisorName = $approverEmployee ? $approverEmployee->first_name . ' ' . $approverEmployee->last_name : 'N/A';
            $supervisorPosition = $approverEmployee && $approverEmployee->position ? $approverEmployee->position->position_name : 'President';
        }
        
        // Get higher rank officer (President)
        $higherRankOfficer = $this->getApproverEmployee('PRESIDENT', $travelOrderPosition);
        $higherRankName = $higherRankOfficer ? $higherRankOfficer->first_name . ' ' . $higherRankOfficer->last_name : 'N/A';
        $higherRankPosition = $higherRankOfficer && $higherRankOfficer->position ? $higherRankOfficer->position->position_name : 'President';
        
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
        
        // Get supervisor information (for backwards compatibility)
        $supervisorName = 'N/A';
        $supervisorPosition = 'Supervisor';
        
        // Use the travel order's position, not the employee's primary position
        $travelOrderPosition = $travelOrder->position;
        
        if ($employee->is_head && !$employee->is_divisionhead) {
            // Unit head - approver is Division Head
            $approverEmployee = $this->getApproverEmployee('DIVISION_HEAD', $travelOrderPosition);
            $supervisorName = $approverEmployee ? $approverEmployee->first_name . ' ' . $approverEmployee->last_name : 'N/A';
            $supervisorPosition = $approverEmployee && $approverEmployee->position ? $approverEmployee->position->position_name : 'Division Head';
        } elseif ($employee->is_divisionhead) {
            // Division head - no immediate supervisor, goes directly to VP
            $supervisorName = '';
            $supervisorPosition = '';
        } elseif ($employee->is_vp && !$employee->is_president) {
            // VP - approver is President
            $approverEmployee = $this->getApproverEmployee('PRESIDENT', $travelOrderPosition);
            $supervisorName = $approverEmployee ? $approverEmployee->first_name . ' ' . $approverEmployee->last_name : 'N/A';
            $supervisorPosition = $approverEmployee && $approverEmployee->position ? $approverEmployee->position->position_name : 'President';
        }
        
        // Get higher rank officer (President) - for backwards compatibility
        $higherRankOfficer = $this->getApproverEmployee('PRESIDENT', $travelOrderPosition);
        $higherRankName = $higherRankOfficer ? $higherRankOfficer->first_name . ' ' . $higherRankOfficer->last_name : 'N/A';
        $higherRankPosition = $higherRankOfficer && $higherRankOfficer->position ? $higherRankOfficer->position->position_name : 'President';
        
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
        
        // NEW APPROVER SYSTEM: Get the selected approvers from the travel order
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
        
        // Determine if same person is both approvers (VP travel scenario)
        $isSameApprover = $travelOrder->approver1_employee_id === $travelOrder->approver2_employee_id;
        
        // Prepare names and positions from loaded data
        $approver1Name = $approver ? $approver['name'] : 'N/A';
        $approver1Position = $approver ? $approver['position'] : 'N/A';
        
        $approver2Name = $signer ? $signer['name'] : 'N/A';
        $approver2Position = $signer ? $signer['position'] : 'N/A';
        
        // Show approver names where they exist - display actual data
        $sheet->setCellValue('H19', $approver1Name);
        $sheet->setCellValue('H20', $approver1Position);
        $sheet->setCellValue('I45', $approver2Name);
        $sheet->setCellValue('I46', $approver2Position);
        
        // Traveler signature (I41/I42)
        $sheet->setCellValue('I41', $fullName);
        $sheet->setCellValue('I42', $positionName);
        
        // Add approval status in cells H17 and I43 (VP/President)
        // For unit head travel orders: H17 should show VP approval status when approved by VP
        // For division head travel orders: I43 should show VP approval status
        // H17: Show VP approval status for unit head travel orders when approved by VP
        
        // H17: Show VP approval status for unit head travel orders when approved by VP
        //      Show Unit Head approval status for regular employees when applicable
        //      Show VP approval status for division head travel orders when approved by VP
        if ($travelOrder->employee->is_head && !$travelOrder->employee->is_divisionhead) {
            // Unit head travel order - show VP approval status in H17 when VP has approved
            if ($travelOrder->vp_approved_at) {
                $vpStatus = $travelOrder->vp_approved ? 'APPROVED' : 'DECLINED';
                $vpTimestamp = $travelOrder->vp_approved_at ? $travelOrder->vp_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A') : '';
                $sheet->setCellValue('H17', $vpStatus . ' ' . $vpTimestamp);
            }
        } else if ($travelOrder->employee->is_divisionhead && !$travelOrder->employee->is_vp) {
            // Division head travel order - show VP approval status in H17 when VP has approved
            if ($travelOrder->vp_approved_at) {
                $vpStatus = $travelOrder->vp_approved ? 'APPROVED' : 'DECLINED';
                $vpTimestamp = $travelOrder->vp_approved_at ? $travelOrder->vp_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A') : '';
                $sheet->setCellValue('H17', $vpStatus . ' ' . $vpTimestamp);
            }
        } else if ($travelOrder->employee->is_vp && !$travelOrder->employee->is_president) {
            // VP travel order - show President approval status in H17 when approved by President
            if ($travelOrder->president_approved_at) {
                $presidentStatus = $travelOrder->president_approved ? 'APPROVED' : 'DECLINED';
                $presidentTimestamp = $travelOrder->president_approved_at ? $travelOrder->president_approved_at->format('M j, Y g:i A') : '';
                $sheet->setCellValue('H17', $presidentStatus . ' ' . $presidentTimestamp);
            }
        } else {
            // For regular employees, show Unit Head approval status in H17 when applicable
            if ($travelOrder->head_approved_at) {
                $unitHeadStatus = $travelOrder->head_approved ? 'APPROVED' : 'DECLINED';
                $unitHeadTimestamp = $travelOrder->head_approved_at ? $travelOrder->head_approved_at->format('M j, Y g:i A') : '';
                $sheet->setCellValue('H17', $unitHeadStatus . ' ' . $unitHeadTimestamp);
            }
        }
        
        // I43: Show Division Head approval status for unit head travel orders
        //      Show VP approval status for division head travel orders
        //      Show President approval status for VP travel orders when approved by President
        if ($travelOrder->employee->is_head && !$travelOrder->employee->is_divisionhead) {
            // Unit head travel order - show division head approval status in I43
            if ($travelOrder->divisionhead_approved_at) {
                $divisionHeadStatus = $travelOrder->divisionhead_approved ? 'APPROVED' : 'DECLINED';
                $divisionHeadTimestamp = $travelOrder->divisionhead_approved_at ? $travelOrder->divisionhead_approved_at->format('M j, Y g:i A') : '';
                $sheet->setCellValue('I43', $divisionHeadStatus . ' ' . $divisionHeadTimestamp);
            }
        } else if ($travelOrder->employee->is_vp && !$travelOrder->employee->is_president) {
            // VP travel order - show President approval status in I43 when approved by President
            if ($travelOrder->president_approved_at) {
                $presidentStatus = $travelOrder->president_approved ? 'APPROVED' : 'DECLINED';
                $presidentTimestamp = $travelOrder->president_approved_at ? $travelOrder->president_approved_at->format('M j, Y g:i A') : '';
                $sheet->setCellValue('I43', $presidentStatus . ' ' . $presidentTimestamp);
            }
        } else {
            // Division head travel order or other - show VP approval status in I43
            if ($travelOrder->vp_approved_at) {
                $vpStatus = $travelOrder->vp_approved ? 'APPROVED' : 'DECLINED';
                $vpTimestamp = $travelOrder->vp_approved_at ? $travelOrder->vp_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A') : '';
                $sheet->setCellValue('I43', $vpStatus . ' ' . $vpTimestamp);
            }
            
            // For President approval, show in I43 if no VP approval (for non-VP employees)
            if ($travelOrder->president_approved_at && !$travelOrder->vp_approved_at && !$travelOrder->employee->is_vp) {
                $presidentStatus = $travelOrder->president_approved ? 'APPROVED' : 'DECLINED';
                $presidentTimestamp = $travelOrder->president_approved_at ? $travelOrder->president_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A') : '';
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
        } else {
            // APPROVAL STAMPS - Robust handling for all scenarios
            $approver1At = $travelOrder->approver1_approved_at;
            $approver2At = $travelOrder->approver2_approved_at;
            
            // TOP STAMP (H17) - always show approval if any exists
            if ($approver1At) {
                $sheet->setCellValue('H17', 'APPROVED ' . $approver1At->timezone('Asia/Manila')->format('M j, Y g:i A'));
            } elseif ($approver2At) {
                $sheet->setCellValue('H17', 'APPROVED ' . $approver2At->timezone('Asia/Manila')->format('M j, Y g:i A'));
            } else {
                $sheet->setCellValue('H17', 'N/A');
            }
            
            // BOTTOM STAMP (I43) - show approver2 or fallback to approver1
            if ($approver2At) {
                $sheet->setCellValue('I43', 'APPROVED ' . $approver2At->timezone('Asia/Manila')->format('M j, Y g:i A'));
            } elseif ($approver1At) {
                $sheet->setCellValue('I43', 'APPROVED ' . $approver1At->timezone('Asia/Manila')->format('M j, Y g:i A'));
            } else {
                $sheet->setCellValue('I43', 'N/A');
            }
        }
        
        // Save Excel temporarily and return it
        $excelTemp = tempnam(sys_get_temp_dir(), 'travel_order_') . '.xlsx';
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
     * Get the actual approver employee based on role and position
     */
    private function getApproverEmployee($approverRole, $employeePosition)
    {
        switch ($approverRole) {
            case 'DIVISION_HEAD':
                return \App\Models\Employee::whereHas('officer', function($query) {
                    $query->where('division_head', true);
                })
                ->whereHas('positions', function($query) use ($employeePosition) {
                    $query->where('division_id', $employeePosition->division_id);
                })->first();
                
            case 'UNIT_HEAD':
                $unitHead = \App\Models\Employee::whereHas('officer', function($query) {
                    $query->where('unit_head', true);
                })
                ->whereHas('positions', function($query) use ($employeePosition) {
                    $query->where('unit_id', $employeePosition->unit_id);
                })->first();
                
                // If no Unit Head found in the same unit, try to find any Unit Head
                if (!$unitHead) {
                    $unitHead = \App\Models\Employee::whereHas('officer', function($query) {
                        $query->where('unit_head', true);
                    })->first();
                }
                
                return $unitHead;
                
            case 'PRESIDENT':
                // Find President from officers table
                return \App\Models\Employee::whereHas('officer', function($query) {
                    $query->where('president', true);
                })->first();
                
            default:
                return null;
        }
    }
    
    /**
     * Get travel order details for modal display (approval context)
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
        
        // Check if this travel order is from someone in the VP's office (approval context)
        // OR if the VP is selected as an approver on this travel order
        $vpPrimaryPosition = $employee->positions()->where('is_primary', true)->first();
        $vpOfficeId = $vpPrimaryPosition ? $vpPrimaryPosition->office_id : null;
        
        $travelOrderPosition = $travelOrder->position;
        $travelOrderOfficeId = $travelOrderPosition ? $travelOrderPosition->office_id : null;
        
        $isFromOffice = $travelOrderOfficeId === $vpOfficeId;
        $isOwn = $travelOrder->employee_id === $employee->id;
        
        // NEW: Check if VP is selected as an approver (either approver1 or approver2)
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        $isApprover = $isApprover1 || $isApprover2;
        
        // Check if travel order is from President's office
        $isFromPresident = $travelOrder->employee->is_president;
        
        // Allow access if:
        // 1. It's from their office, OR
        // 2. It's their own travel order, OR
        // 3. They are selected as an approver on this travel order, OR
        // 4. It's from the President's office (VPS can review President's travel orders)
        if (!$isFromOffice && !$isOwn && !$isApprover && !$isFromPresident) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to travel order'
            ], 403);
        }
        
        // Load relationships
        $travelOrder->load(['employee', 'employee.user', 'position', 'rejectedBy']);
        
        return response()->json([
            'success' => true,
            'data' => $travelOrder
        ]);
    }
}