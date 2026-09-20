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

class DivisionHeadTravelOrderController extends Controller
{
    /**
     * Display a listing of travel orders for division head approval with tab support.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Get the tab parameter, default to 'pending'
        $tab = $request->get('tab', 'pending');
        
        // Get search term if provided
        $search = $request->get('search', '');
        
        switch ($tab) {
            case 'approved':
                // Show travel orders where this employee has already approved
                $query = TravelOrder::where('status', '!=', 'cancelled')
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
                $query = TravelOrder::where('status', 'cancelled')
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
                // Show travel orders where the current employee is either approver1 or approver2
                $employeeId = $employee->id;
                
                // - Approver 2 (lower rank) approves FIRST
                // - Approver 1 (higher rank) approves SECOND
                
                $query = TravelOrder::where('status', '!=', 'cancelled')
                      ->where('status', '!=', 'draft')
                      ->where(function($q) use ($employeeId) {
                          $q->where(function($approver2Queue) use ($employeeId) {
                              // Approver 2's queue (lower rank - approves first)
                              // Shows when: Employee is approver2 AND hasn't approved yet
                              $approver2Queue->where('approver2_employee_id', $employeeId)
                                    ->whereNull('approver2_approved_at');
                          })->orWhere(function($approver1Queue) use ($employeeId) {
                              // Approver 1's queue (higher rank - approves second/final)
                              // Shows when: Employee is approver1 AND 
                              // (there is no approver2 OR approver2 has already approved) AND approver1 hasn't approved yet
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
        
        // Order by latest approval timestamp (prefer approver2, then approver1, then created_at)
        $travelOrders = $query->with(['employee', 'position', 'approver1', 'approver2'])
            ->orderByRaw('COALESCE(approver2_approved_at, approver1_approved_at, created_at) DESC')
            ->paginate(10)
            ->appends($request->except('page'));
        
        if ($request->ajax() || $request->get('ajax')) {
            return response()->json([
                'table_body' => view('travel-orders.approvals.partials.table-rows', compact('travelOrders', 'tab'))->with('travelOrders', $travelOrders->load('position', 'employee'))->render(),
                'pagination' => (string) $travelOrders->withQueryString()->links()
            ]);
        }
        
        return view('travel-orders.approvals.divisionhead-index', compact('travelOrders', 'tab', 'search'));
    }

    /**
     * Display the specified resource for approval.
     */
    public function show(TravelOrder $travelOrder): View
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record. Please contact your administrator to set up your employee profile.');
        }
        
        // Check if the current user is selected as an approver (either approver1 or approver2)
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        
        // Also allow if it's the division head's own travel order
        $isOwn = $travelOrder->employee_id === $employee->id;
        
        // Allow motorpool admin to access all travel orders
        $isMotorpoolAdmin = $user->role === 'motorpool_admin';
        
        // Allow if travel order is from employee in division head's division (for oversight)
        $divisionHeadPrimaryPosition = $employee->positions()->where('is_primary', true)->first();
        $divisionHeadDivisionId = $divisionHeadPrimaryPosition ? $divisionHeadPrimaryPosition->division_id : null;
        $employeePrimaryPosition = $travelOrder->employee->positions()->where('is_primary', true)->first();
        $travelOrderDivisionId = $employeePrimaryPosition ? $employeePrimaryPosition->division_id : null;
        $isFromDivision = $travelOrderDivisionId === $divisionHeadDivisionId;
        
        if (!$isApprover1 && !$isApprover2 && !$isOwn && !$isMotorpoolAdmin && !$isFromDivision) {
            abort(403);
        }
       
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
        
        // Check if current employee is the selected Approver 2 (lower rank - approves FIRST)
        if ($travelOrder->approver2_employee_id == $employee->id) {
            // First approval stage (Approver 2 - lower rank)
            if ($travelOrder->approver2_approved_at) {
                abort(403);  // Already approved
            }
            
            // Approve as Approver 2
            // After approver2 approves, it goes to approver1 (if exists)
            $travelOrder->update([
                'approver2_approved_at' => now(),
                'status' => $travelOrder->approver1_employee_id ? 'pending' : 'approved',
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Travel order approved successfully.'
            ]);
                
        } elseif ($travelOrder->approver1_employee_id == $employee->id) {
            // Second approval stage (Approver 1 - higher rank)
            // Can only approve after Approver 2 has approved
            if ($travelOrder->approver2_employee_id && !$travelOrder->approver2_approved_at) {
                abort(403, 'Approver 2 must approve first');  // Approver 2 hasn't approved yet
            }
            
            if ($travelOrder->approver1_approved_at) {
                abort(403);  // Already approved
            }
            
            // Approve as Approver 1 and set status to approved
            $travelOrder->update([
                'approver1_approved_at' => now(),
                'status' => 'approved',
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Travel order approved successfully. It is now fully approved.'
            ]);
        } else {
            abort(403);  // Not authorized to approve this travel order
        }
    }

    /**
     * Reject a travel order.
     */
    public function reject(TravelOrder $travelOrder, Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if current employee is the selected Approver 1 or Approver 2
        $isApprover1 = ($travelOrder->approver1_employee_id == $employee->id);
        $isApprover2 = ($travelOrder->approver2_employee_id == $employee->id);
        
        if (!$isApprover1 && !$isApprover2) {
            abort(403);  // Not authorized to reject this travel order
        }
        
        // Can only reject if not yet approved by this approver
        if ($isApprover1 && $travelOrder->approver1_approved_at) {
            abort(403, 'Already approved as Approver 1');
        }
        
        if ($isApprover2 && $travelOrder->approver2_approved_at) {
            abort(403, 'Already approved as Approver 2');
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
        
        // Check if user is selected as an approver on this travel order
        $isApprover = ($travelOrder->approver1_employee_id === $employee->id || 
                      $travelOrder->approver2_employee_id === $employee->id);
        
        // Check if the travel order belongs to an employee in the division head's division
        $divisionHeadPrimaryPosition = $employee->positions()->where('is_primary', true)->first();
        $divisionHeadDivisionId = $divisionHeadPrimaryPosition ? $divisionHeadPrimaryPosition->division_id : null;
        
        // Get the travel order's selected position division (not the employee's primary position)
        $travelOrderPosition = $travelOrder->position;
        
        // If no position is assigned to the travel order, deny access
        if (!$travelOrderPosition) {
            abort(403);
        }
        
        $travelOrderDivisionId = $travelOrderPosition->division_id;
        
        // Allow if travel order is from employee in division head's division
        $isFromDivision = $travelOrderDivisionId === $divisionHeadDivisionId;
        
        // Also allow if it's the division head's own travel order
        $isOwn = $travelOrder->employee_id === $employee->id;
        
        // Allow motorpool admin to access all travel orders
        $isMotorpoolAdmin = $user->role === 'motorpool_admin';
        
        if (!$isFromDivision && !$isOwn && !$isPresident && !$isMotorpoolAdmin && !$isApprover) {
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
        
        // Get approver information
        $approverEmployee = $this->getApproverEmployee('UNIT_HEAD', $travelOrder->position);
        $supervisorName = $approverEmployee ? $approverEmployee->first_name . ' ' . $approverEmployee->last_name : 'N/A';
        $supervisorPosition = $approverEmployee && $approverEmployee->position ? $approverEmployee->position->position_name : 'Unit Head';
        
        // Get higher rank officer (VP/President)
        $higherRankOfficer = $this->getApproverEmployee('VP_OR_PRESIDENT', $travelOrder->position);
        $higherRankName = $higherRankOfficer ? $higherRankOfficer->first_name . ' ' . $higherRankOfficer->last_name : 'N/A';
        $higherRankPosition = $higherRankOfficer && $higherRankOfficer->position ? $higherRankOfficer->position->position_name : 'VP/President';
        
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
        
        // Fill in approver information
        $sheet->setCellValue('H19', $supervisorName);
        $sheet->setCellValue('H20', $supervisorPosition);
        $sheet->setCellValue('I41', $fullName);
        $sheet->setCellValue('I42', $positionName);
        $sheet->setCellValue('I45', $higherRankName);
        $sheet->setCellValue('I46', $higherRankPosition);
        
        // Add approval/cancellation status in cells H17 (Approver 1) and I43 (Approver 2)
        // NEW APPROVER SYSTEM: Show approver1 and approver2 approval status
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
            case 'DIVISION_HEAD':
                return \App\Models\Employee::whereHas('positions', function($query) use ($employeePosition) {
                    $query->where('is_division_head', true)
                          ->where('division_id', $employeePosition->division_id);
                })->first();
                
            case 'UNIT_HEAD':
                $unitHead = \App\Models\Employee::whereHas('positions', function($query) use ($employeePosition) {
                    $query->where('is_unit_head', true)
                          ->where('unit_id', $employeePosition->unit_id);
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
                
            case 'VP_OR_PRESIDENT':
                if ($employeePosition && $employeePosition->office) {
                    $officeName = strtolower($employeePosition->office->office_name);
                    if (strpos($officeName, 'office of the university president') !== false || 
                        strpos($officeName, 'office of the president') !== false) {
                        // President's Office - goes directly to President
                        $president = \App\Models\Employee::whereHas('officer', function($query) {
                            $query->where('president', true);
                        })->first();
                        
                        // Fallback: check positions table
                        if (!$president) {
                            $president = \App\Models\Employee::whereHas('positions', function($query) {
                                $query->where('is_president', true);
                            })->first();
                        }
                        
                        return $president;
                    } else {
                        // Other offices - goes to VP
                        $vp = \App\Models\Employee::whereHas('officer', function($query) {
                            $query->where('vp', true);
                        })->first();
                        
                        // Fallback: check positions table
                        if (!$vp) {
                            $vp = \App\Models\Employee::whereHas('positions', function($query) {
                                $query->where('is_vp', true);
                            })->first();
                        }
                        
                        return $vp;
                    }
                }
                return null;
                
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
        
        // Check if user is selected as an approver on this travel order
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        $isApprover = $isApprover1 || $isApprover2;
        
        // Check if this travel order is from someone in the division head's division OR if user is VP accessing Division Head travel orders
        $divisionHeadPrimaryPosition = $employee->positions()->where('is_primary', true)->first();
        $divisionHeadDivisionId = $divisionHeadPrimaryPosition ? $divisionHeadPrimaryPosition->division_id : null;
        
        $travelOrderPosition = $travelOrder->position;
        $travelOrderDivisionId = $travelOrderPosition ? $travelOrderPosition->division_id : null;
        
        $isFromDivision = $travelOrderDivisionId === $divisionHeadDivisionId;
        $isOwn = $travelOrder->employee_id === $employee->id;
        
        // Check if user is VP with authority over this travel order
        $isVpWithAuthority = false;
        if ($divisionHeadPrimaryPosition && $divisionHeadPrimaryPosition->is_vp && 
            !$divisionHeadPrimaryPosition->is_president && $travelOrderPosition) {
            $travelOrderOfficeId = $travelOrderPosition->office_id;
            $vpOfficeId = $divisionHeadPrimaryPosition->office_id;
            // VP can access if from their office OR if they are selected as an approver
            $isVpWithAuthority = ($travelOrderOfficeId === $vpOfficeId || $isApprover);
        }
        
        // Allow access if:
        // 1. It's from their division, OR
        // 2. It's their own travel order, OR
        // 3. They're a VP with authority, OR
        // 4. They are selected as an approver on this travel order
        if (!$isFromDivision && !$isOwn && !$isVpWithAuthority && !$isApprover) {
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