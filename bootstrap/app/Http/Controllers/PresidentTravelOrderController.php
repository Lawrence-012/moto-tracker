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

class PresidentTravelOrderController extends Controller
{
    /**
     * Display a listing of travel orders for president approval with tab support.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Get the tab parameter, default to 'pending'
        $tab = $request->get('tab', 'pending');
        
        // Get search term if provided
        $search = $request->get('search', '');
        
        // Build the query for travel orders where President is selected as an approver
        $employeeId = $employee->id;
        
        $query = TravelOrder::where(function($q) use ($employeeId) {
            // President is either approver1 (higher rank) or approver2 (lower rank)
            $q->where('approver1_employee_id', $employeeId)
              ->orWhere('approver2_employee_id', $employeeId);
        });
        
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
                // Travel orders approved by the President (as either approver1 or approver2)
                // The President may be the sole approver or one of two approvers
                // EXCLUDE cancelled orders - they go to the cancelled tab
                $query->where('status', '!=', 'cancelled')
                    ->where(function($q) use ($employeeId) {
                        $q->where(function($q1) use ($employeeId) {
                            // President is approver1 and has approved
                            $q1->where('approver1_employee_id', $employeeId)
                               ->whereNotNull('approver1_approved_at');
                        })
                        ->orWhere(function($q2) use ($employeeId) {
                            // President is approver2 and has approved
                            $q2->where('approver2_employee_id', $employeeId)
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
                // Show travel orders where the President is selected as either approver1 or approver2
                $employeeId = $employee->id;
                
                // - Approver 2 (lower rank) approves FIRST
                // - Approver 1 (higher rank) approves SECOND
                
                $query->where('status', '!=', 'cancelled')
                      ->where('status', '!=', 'draft')
                      ->where(function($q) use ($employeeId) {
                          $q->where(function($approver2Queue) use ($employeeId) {
                              // Approver 2's queue (lower rank - approves first)
                              // Shows when: Employee is approver2 AND hasn't approved yet
                              $approver2Queue->where('approver2_employee_id', $employeeId)
                                    ->whereNull('approver2_approved_at');
                          })->orWhere(function($approver1Queue) use ($employeeId) {
                              // Approver 1's queue (higher rank - approves second)
                              // Shows when: Employee is approver1 AND approver2 has already approved
                              $approver1Queue->where('approver1_employee_id', $employeeId)
                                    ->whereNotNull('approver2_approved_at')
                                    ->whereNull('approver1_approved_at');
                          });
                      });
                break;
        }
        
        // Order by latest approval timestamp (prefer approver2, then approver1, then created_at)
        $travelOrders = $query->with('position', 'employee')
            ->orderByRaw('COALESCE(approver2_approved_at, approver1_approved_at, created_at) DESC')
            ->paginate(10)
            ->appends($request->except('page'));
        
        if ($request->ajax() || $request->get('ajax')) {
            $approvalShowRoute = 'travel-orders.approval-show.president';
            return response()->json([
                'table_body' => view('travel-orders.approvals.partials.table-rows', compact('travelOrders', 'tab', 'approvalShowRoute'))->with('travelOrders', $travelOrders->load('position', 'employee'))->render(),
                'pagination' => (string) $travelOrders->withQueryString()->links()
            ]);
        }
        
        $approvalShowRoute = 'travel-orders.approval-show.president';
        return view('travel-orders.approvals.president-index', compact('travelOrders', 'tab', 'search', 'approvalShowRoute'));
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
        
        // Check if the user is a president
        if (!$employee->is_president) {
            abort(403);
        }
        
        // Also allow if it's the president's own travel order
        $isOwn = $travelOrder->employee_id === $employee->id;
        
        // For presidents, allow viewing any travel order that requires presidential approval
        // This includes VP travel orders, division head travel orders, and unit head travel orders
        $requiresPresApproval = false;
        
        // Check if President is selected as either approver1 or approver2
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        
        if ($isApprover1 || $isApprover2) {
            $requiresPresApproval = true;
        }
        
        if (!$requiresPresApproval && !$isOwn) {
            abort(403);
        }
        
        // Set approval context flag - always use ConvertAPI for president travel orders
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
        
        // Ensure the President is selected as an approver
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        
        if (!$isApprover1 && !$isApprover2) {
            abort(403);
        }
        
        // Special case: If President is BOTH approver1 AND approver2 (VP travel requests)
        // Approve both at the same time with dual stamping
        if ($isApprover1 && $isApprover2) {
            // Check if already fully approved
            if ($travelOrder->approver1_approved_at && $travelOrder->approver2_approved_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'This travel order has already been approved.'
                ], 403);
            }
            
            // Stamp both approver1 and approver2 timestamps simultaneously
            $travelOrder->update([
                'approver1_approved_at' => now(),
                'approver2_approved_at' => now(),
                'status' => 'approved',
            ]);
        } elseif ($isApprover2) {
            // Lower rank approver (approves FIRST)
            if ($travelOrder->approver2_approved_at) {
                return redirect()->back()
                    ->with('error', 'This travel order has already been approved.');
            }
            
            if ($travelOrder->approver2_employee_id && 
                !$travelOrder->approver1_employee_id && 
                $employee->id == $travelOrder->approver2_employee_id) {
                
                // President is the only approver (VP travel case)
                $time = now();
                
                // Copy to approver1 so PDF shows both stamps
                $travelOrder->update([
                    'approver1_employee_id' => $travelOrder->approver2_employee_id,
                    'approver1_approved_at' => $time,
                    'approver2_approved_at' => $time,
                    'status' => 'approved',
                ]);
            } else {
                // Normal approval flow for approver2
                $travelOrder->update([
                    'approver2_approved_at' => now(),
                    'status' => $travelOrder->approver1_employee_id ? 'pending' : 'approved',
                ]);
            }
        } elseif ($isApprover1) {
            // Higher rank approver (approves SECOND)
            if ($travelOrder->approver1_approved_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'This travel order has already been approved.'
                ], 403);
            }
            
            // Can only approve after approver2 has approved (if approver2 exists)
            if ($travelOrder->approver2_employee_id && !$travelOrder->approver2_approved_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'This travel order must be approved by the lower-rank approver first.'
                ], 403);
            }
            
            // Approve as approver1 and set status to approved
            $travelOrder->update([
                'approver1_approved_at' => now(),
                'status' => 'approved',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Travel order approved successfully.',
            'redirect_url' => url('/travel-orders/approvals/president?tab=approved')
        ]);
    }

    /**
     * Reject a travel order.
     */
    public function reject(TravelOrder $travelOrder, Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Ensure the President is selected as an approver
        $isApprover1 = $travelOrder->approver1_employee_id === $employee->id;
        $isApprover2 = $travelOrder->approver2_employee_id === $employee->id;
        
        if (!$isApprover1 && !$isApprover2) {
            abort(403);
        }
        
        // Determine which approver role and check if can reject
        if ($isApprover2) {
            // Lower rank approver - can reject before approving
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
        
        // Special case: If President is BOTH approver1 AND approver2 (VP travel requests)
        // Clear both approval timestamps when rejecting
        if ($isApprover1 && $isApprover2) {
            $travelOrder->update([
                'status' => 'cancelled',
                'rejection_reason' => $validated['remarks'],
                'rejected_by_employee_id' => $employee->id,
                'rejected_at' => now(),
                'approver1_approved_at' => null, // Clear any partial approvals
                'approver2_approved_at' => null,
            ]);
        } else {
            // Normal rejection flow
            $travelOrder->update([
                'status' => 'cancelled',
                'rejection_reason' => $validated['remarks'],
                'rejected_by_employee_id' => $employee->id,
                'rejected_at' => now(),
            ]);
        }

        return redirect()->back()
            ->with('success', 'Travel order rejected successfully.');
    }
    
    /**
     * Generate PDF for travel order in approval context
     */
    public function generateExcel(TravelOrder $travelOrder): Response
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            abort(403);
        }
        
        // Check if the user is a president
        if (!$employee->is_president) {
            abort(403);
        }
        
        // President can view ANY travel order PDF - no restrictions needed
        // They can view all travel orders for oversight purposes
        
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
        
        if ($employee->is_head && !$employee->is_divisionhead) {
            // Unit head - approver is Division Head
            $approverEmployee = $this->getApproverEmployee('DIVISION_HEAD', $employee->position);
            $supervisorName = $approverEmployee ? $approverEmployee->first_name . ' ' . $approverEmployee->last_name : 'N/A';
            $supervisorPosition = $approverEmployee && $approverEmployee->position ? $approverEmployee->position->position_name : 'Division Head';
        } elseif ($employee->is_divisionhead && !$employee->is_vp) {
            // Division head - approver is VP (unless President's Office)
            $approverEmployee = $this->getApproverEmployee('VP', $employee->position);
            $supervisorName = $approverEmployee ? $approverEmployee->first_name . ' ' . $approverEmployee->last_name : 'N/A';
            $supervisorPosition = $approverEmployee && $approverEmployee->position ? $approverEmployee->position->position_name : 'VP';
        } elseif ($employee->is_vp) {
            // VP - no immediate supervisor, goes directly to President
            $supervisorName = '';
            $supervisorPosition = '';
        }
        
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
        
        // Load approvers from database (NEW APPROVER SYSTEM)
        $approver1 = null;
        $approver2 = null;
        
        if ($travelOrder->approver1_employee_id) {
            $approver1 = Employee::with('positions')->find($travelOrder->approver1_employee_id);
        }
        
        if ($travelOrder->approver2_employee_id) {
            $approver2 = Employee::with('positions')->find($travelOrder->approver2_employee_id);
        }
        
        // Fill names in PDF - show actual data when available
        $approver1Name = $approver1 ? $approver1->first_name . ' ' . $approver1->last_name : 'N/A';
        $approver1Position = $approver1 && $approver1->positions && $approver1->positions->first() ? $approver1->positions->first()->position_name : 'N/A';
        
        $approver2Name = $approver2 ? $approver2->first_name . ' ' . $approver2->last_name : 'N/A';
        $approver2Position = $approver2 && $approver2->positions && $approver2->positions->first() ? $approver2->positions->first()->position_name : 'N/A';
        
        // Show approver names where they exist
        $sheet->setCellValue('H19', $approver1Name);
        $sheet->setCellValue('H20', $approver1Position);
        $sheet->setCellValue('I45', $approver2Name);
        $sheet->setCellValue('I46', $approver2Position);
        
        // Traveler signature (I41/I42)
        $sheet->setCellValue('I41', $fullName);
        $sheet->setCellValue('I42', $positionName);
        
        // Add approval/cancellation status in cells H17 (Approver 1) and I43 (Approver 2)
        // Check if same person is both approvers (VP travel request scenario)
        $isSameApprover = $travelOrder->approver1_employee_id === $travelOrder->approver2_employee_id;
        
        if ($travelOrder->status === 'cancelled') {
            // CANCELLED STAMP - Show rejection information
            $cancellationText = 'CANCELLED';
            if ($travelOrder->rejected_at) {
                $cancellationText .= ' ' . $travelOrder->rejected_at->timezone('Asia/Manila')->format('M j, Y g:i A');
            }
            
            // Display CANCELLED stamp
            if ($isSameApprover && $travelOrder->approver1_employee_id && $travelOrder->approver2_employee_id) {
                // Same person is both approvers - show CANCELLED in both locations
                $sheet->setCellValue('H17', $cancellationText);
                $sheet->setCellValue('I43', $cancellationText);
            } elseif ($travelOrder->approver1_employee_id && $travelOrder->approver2_employee_id) {
                // Two different approvers - show where rejected
                if ($travelOrder->rejected_by_employee_id == $travelOrder->approver1_employee_id) {
                    $sheet->setCellValue('H17', $cancellationText);
                } elseif ($travelOrder->rejected_by_employee_id == $travelOrder->approver2_employee_id) {
                    $sheet->setCellValue('I43', $cancellationText);
                }
            } elseif ($travelOrder->approver1_employee_id) {
                // Only approver1
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
            ->header('Content-Disposition', 'attachment; filename="President_Travel_Order_' . $travelOrder->id . '.xlsx"')
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
     * Show details of a travel order for approval
     */
    public function showDetails(TravelOrder $travelOrder)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return response()->json(['error' => 'Employee record not found'], 404);
        }
        
        // Check if the user is a president
        if (!$employee->is_president) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // President can view ANY travel order - no restrictions needed
        // They can view all travel orders for oversight purposes
        
        // Load the travel order with all necessary relationships
        $travelOrder->load(['employee', 'employee.user', 'position', 'position.office', 'position.division', 'position.unit', 'rejectedBy']);

        return response()->json([
            'success' => true,
            'data' => $travelOrder
        ]);
    }
}