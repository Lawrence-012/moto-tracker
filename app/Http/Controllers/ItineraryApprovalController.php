<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\Itinerary;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Support\ItineraryApprovers;

class ItineraryApprovalController extends Controller
{
    /**
     * Show itineraries for unit head with tab filtering
     */
    /** STEP 1 approver check (the one unit higher than the passenger). */
    private function isStep1User($user, Itinerary $itinerary): bool
    {
        return $user->employee && ItineraryApprovers::isStep1($user->employee, $itinerary);
    }

    /** STEP 2 approver check (Unit Head of Transportation Services). */
    private function isStep2User($user, Itinerary $itinerary): bool
    {
        return $user->employee && ItineraryApprovers::isStep2($user->employee, $itinerary);
    }

    public function unitHeadPending(Request $request)
    {
        $user = Auth::user();
        $tab = $request->get('tab', 'pending'); // Default to pending tab
        $search = $request->get('search', ''); // Get search term
        
        // Base query
        $query = Itinerary::with(['driver', 'vehicle']);
            
        // Only the STEP 1 approver (one unit higher than the passenger) sees these. Admins see all.
        if (!$user->isMotorpoolAdmin() && !$user->isAdmin()) {
            if (!$user->employee) {
                abort(403, 'Unauthorized to view Unit Head pending itineraries');
            }
            $query->whereIn('id', ItineraryApprovers::idsForStep1($user->employee));
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
                  })
                  ->orWhereHas('driver', function($q) use ($search) {
                      $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        // Apply tab filtering
        switch ($tab) {
            case 'approved':
                // Show only itineraries approved by unit head AND NOT cancelled
                $query->where('unit_head_approved', true)
                      ->whereNotNull('unit_head_approved_at')
                      ->where('status', '!=', 'Cancelled')  // Exclude cancelled itineraries
                      ->where(function($q) {
                          $q->where(function($subQ) {
                              // VP approved
                              $subQ->where('vp_approved', true);
                          })->orWhere(function($subQ) {
                              // OR VP hasn't acted yet (not rejected)
                              $subQ->where('vp_approved', false)
                                   ;
                          });
                      })
                      ->orderBy('created_at', 'desc'); // Newest first
                break;
            case 'cancelled':
                // Show all cancelled itineraries (including those rejected by VP after unit head approved)
                $query->where('status', 'Cancelled')
                      ->orderBy('created_at', 'desc'); // Newest first
                break;
            case 'pending':
            default:
                $query->where('unit_head_approved', false)
                      
                      ->where('status', '!=', 'Cancelled')
                      ->orderBy('created_at', 'desc'); // Newest first
                break;
        }
        
        $itineraries = $query->paginate(10)->appends(['search' => $search]);
        
        // Get counts for each tab
        $pendingCount = Itinerary::where('unit_head_approved', false)
            
            ->when(!$user->isMotorpoolAdmin() && !$user->isAdmin(), function($q) use ($user) {
                // Same rule as the list: only itineraries where this user is the STEP 1 approver
                return $q->whereIn('id', $user->employee ? \App\Support\ItineraryApprovers::idsForStep1($user->employee) : []);
            })
            ->count();
            
        $approvedCount = Itinerary::where('unit_head_approved', true)
            ->whereNotNull('unit_head_approved_at')
            ->when(!$user->isMotorpoolAdmin() && !$user->isAdmin(), function($q) use ($user) {
                // Same rule as the list: only itineraries where this user is the STEP 1 approver
                return $q->whereIn('id', $user->employee ? \App\Support\ItineraryApprovers::idsForStep1($user->employee) : []);
            })
            ->count();
            
        $cancelledCount = Itinerary::where('unit_head_approved', false)
            ->whereNotNull('unit_head_approved_at')
            ->when(!$user->isMotorpoolAdmin() && !$user->isAdmin(), function($q) use ($user) {
                // Same rule as the list: only itineraries where this user is the STEP 1 approver
                return $q->whereIn('id', $user->employee ? \App\Support\ItineraryApprovers::idsForStep1($user->employee) : []);
            })
            ->count();
            
        return view('itineraries.approvals.unit-head-pending', compact('itineraries', 'tab', 'pendingCount', 'approvedCount', 'cancelledCount', 'search'));
    }
    
    /**
     * Approve itinerary by unit head
     */
    public function approveByUnitHead($id)
    {
        $itinerary = Itinerary::findOrFail($id);
        
        // Check if user has unit head role
        $user = Auth::user();
        if (!$this->isStep1User($user, $itinerary) && !$user->isAdmin() && !$user->isMotorpoolAdmin()) {
            abort(403, 'Unauthorized to approve itineraries');
        }
        
        $itinerary->update([
            'unit_head_approved' => true,
            'unit_head_approved_by' => $user->id,
            'unit_head_approved_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Itinerary approved by Unit Head successfully.'
        ]);
    }
    
    /**
     * Reject itinerary by unit head
     */
    public function rejectByUnitHead(Request $request, $id)
    {
        \Log::info('Reject called with ID: ' . $id);
        \Log::info('Request input remarks: ' . $request->input('remarks'));
        
        $itinerary = Itinerary::findOrFail($id);
        
        // Check if user has unit head role
        $user = Auth::user();
        if (!$this->isStep1User($user, $itinerary) && !$user->isAdmin() && !$user->isMotorpoolAdmin()) {
            abort(403, 'Unauthorized to reject itineraries');
        }
        
        // Get rejection reason from request
        $rejectionReason = $request->input('remarks');
        
        \Log::info('About to update itinerary with status Cancelled and reason: ' . $rejectionReason);
        
        $itinerary->update([
            'status' => 'Cancelled',
            'rejection_reason' => $rejectionReason,
            'rejected_by_employee_id' => $user->employee?->id,
            'rejected_at' => now(),
        ]);
        
        \Log::info('Update successful');
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Itinerary rejected by Unit Head successfully.'
            ]);
        }
        
        return redirect()->back()->with('info', 'Itinerary rejected by Unit Head.');
    }
    
    /**
     * Show itineraries for VP with tab filtering
     */
    public function vpPending(Request $request)
    {
        $user = Auth::user();
        $tab = $request->get('tab', 'pending'); // Default to pending tab
        $search = $request->get('search', ''); // Get search term
        
        // Base query
        $query = Itinerary::where('unit_head_approved', true)
            ->with(['driver', 'vehicle']);
            
        // Only the STEP 2 approver (Unit Head of Transportation Services) sees these. Admins see all.
        if (!$user->isMotorpoolAdmin() && !$user->isAdmin()) {
            if (!$user->employee) {
                abort(403, 'Unauthorized to view VP pending itineraries');
            }
            $query->whereIn('id', ItineraryApprovers::idsForStep2($user->employee, $tab === 'cancelled'));
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
                  })
                  ->orWhereHas('driver', function($q) use ($search) {
                      $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        /*
        // If user is a VP, only show itineraries related to their office/division
        if ($user->isVp() && !$user->isAdmin() && !$user->isMotorpoolAdmin()) {
            // Find the office that the transportation services unit belongs to
            // First, find the transportation services unit
            $transportationUnit = Unit::whereRaw('LOWER(unit_name) LIKE ?', ['%transportation services%'])->first();
            
            if ($transportationUnit) {
                // Get the division of the transportation services unit
                $transportationDivision = $transportationUnit->division;
                
                if ($transportationDivision) {
                    // Get the office of the transportation services unit
                    $transportationOffice = $transportationDivision->office;
                    
                    if ($transportationOffice) {
                        // Only show itineraries where the employee belongs to a unit in the same office
                        $query->whereHas('travelOrder.employee', function($q) use ($transportationOffice) {
                            $q->whereHas('positions', function($q2) use ($transportationOffice) {
                                $q2->whereHas('unit.division', function($q3) use ($transportationOffice) {
                                    $q3->where('office_id', $transportationOffice->id);
                                });
                            });
                        });
                    }
                }
            }
        }
        */
        
        // Apply tab filtering
        switch ($tab) {
            case 'approved':
                $query->where('vp_approved', true)
                      ->whereNotNull('vp_approved_at')
                      ->orderBy('created_at', 'desc'); // Newest first
                break;
            case 'cancelled':
                $query->where('status', 'Cancelled')
                      ->orderBy('created_at', 'desc'); // Newest first
                break;
            case 'pending':
            default:
                $query->where('vp_approved', false)
                      
                      ->where('status', '!=', 'Cancelled')
                      ->orderBy('created_at', 'desc'); // Newest first
                break;
        }
        
        $itineraries = $query->paginate(10)->appends(['search' => $search]);
        
        // Get counts for each tab
        $pendingCount = Itinerary::where('unit_head_approved', true)
            ->where('vp_approved', false)
            
            ->where('status', '!=', 'Cancelled')
            ->count();
            
        $approvedCount = Itinerary::where('unit_head_approved', true)
            ->where('vp_approved', true)
            ->whereNotNull('vp_approved_at')
            ->count();
            
        $cancelledCount = Itinerary::where('unit_head_approved', true)
            ->where('vp_approved', false)
            ->whereNotNull('vp_approved_at')
            ->count();
            
        return view('itineraries.approvals.vp-pending', compact('itineraries', 'tab', 'pendingCount', 'approvedCount', 'cancelledCount', 'search'));
    }
    
    /**
     * Approve itinerary by VP
     */
    public function approveByVp($id)
    {
        $itinerary = Itinerary::findOrFail($id);
        
        // Check if user has VP role
        $user = Auth::user();
        if (!$this->isStep2User($user, $itinerary) && !$user->isAdmin() && !$user->isMotorpoolAdmin()) {
            abort(403, 'Unauthorized to approve itineraries');
        }
        
        // All VPs can approve itineraries now
        
        // Additional check: if not admin/motorpool admin, verify user can approve this itinerary
        if (!$user->isAdmin() && !$user->isMotorpoolAdmin() && $user->isVp()) {
            /*
            // Find the office that the transportation services unit belongs to
            // First, find the transportation services unit
            $transportationUnit = Unit::whereRaw('LOWER(unit_name) LIKE ?', ['%transportation services%'])->first();
            
            if ($transportationUnit) {
                // Get the division of the transportation services unit
                $transportationDivision = $transportationUnit->division;
                
                if ($transportationDivision) {
                    // Get the office of the transportation services unit
                    $transportationOffice = $transportationDivision->office;
                    
                    if ($transportationOffice) {
                        // Verify that this itinerary belongs to the same office
                        $itineraryHasCorrectOffice = $itinerary->travelOrder->employee->positions()
                            ->whereHas('unit.division', function($q) use ($transportationOffice) {
                                $q->where('office_id', $transportationOffice->id);
                            })
                            ->exists();
                            
                        if (!$itineraryHasCorrectOffice) {
                            abort(403, 'Unauthorized to approve this itinerary - not in your office jurisdiction');
                        }
                    }
                }
            }
            */
        }
        
        $itinerary->update([
            'vp_approved' => true,
            'vp_approved_by' => $user->id,
            'vp_approved_at' => now(),
            'status' => 'Approved' // Final approval sets the status to Approved
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Itinerary approved by VP successfully. Itinerary is now fully approved.'
        ]);
    }
    
    /**
     * Reject itinerary by VP
     */
    public function rejectByVp(Request $request, $id)
    {
        $itinerary = Itinerary::findOrFail($id);
                
        // Check if user has VP role
        $user = Auth::user();
        if (!$this->isStep2User($user, $itinerary) && !$user->isAdmin() && !$user->isMotorpoolAdmin()) {
            abort(403, 'Unauthorized to reject itineraries');
        }
                
        // Additional check: if not admin/motorpool admin, verify user can reject this itinerary
        if (!$user->isAdmin() && !$user->isMotorpoolAdmin() && $user->isVp()) {
            /*
            // Find the office that the transportation services unit belongs to
            // First, find the transportation services unit
            $transportationUnit = Unit::whereRaw('LOWER(unit_name) LIKE ?', ['%transportation services%'])->first();
                    
            if ($transportationUnit) {
                // Get the division of the transportation services unit
                $transportationDivision = $transportationUnit->division;
                        
                if ($transportationDivision) {
                    // Get the office of the transportation services unit
                    $transportationOffice = $transportationDivision->office;
                            
                    if ($transportationOffice) {
                        // Verify that this itinerary belongs to the same office
                        $itineraryHasCorrectOffice = $itinerary->travelOrder->employee->positions()
                            ->whereHas('unit.division', function($q) use ($transportationOffice) {
                                $q->where('office_id', $transportationOffice->id);
                            })
                            ->exists();
                                
                        if (!$itineraryHasCorrectOffice) {
                            abort(403, 'Unauthorized to reject this itinerary - not in your office jurisdiction');
                        }
                    }
                }
            }
            */
        }
                
        // Get rejection reason from request
        $rejectionReason = $request->input('remarks');
                
        $itinerary->update([
            'status' => 'Cancelled',
            'rejection_reason' => $rejectionReason,
            'rejected_by_employee_id' => $user->employee?->id,
            'rejected_at' => now(),
        ]);
                
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Itinerary rejected by VP successfully.'
            ]);
        }
                
        return redirect()->back()->with('info', 'Itinerary rejected by VP.');
    }
    
    /**
     * Show approved itineraries
     */
    public function approved()
    {
        $user = Auth::user();
        
        $query = Itinerary::where('vp_approved', true)
            ->whereNotNull('vp_approved_at')
            ->with(['driver', 'vehicle']);
            
        // If user is not a motorpool admin, admin, or has approval authority, limit access
        if (!$user->isMotorpoolAdmin() && !$user->isAdmin() && !$user->isVp() && !$user->isUnitHead()) {
            abort(403, 'Unauthorized to view approved itineraries');
        }
        
        /*
        // If user is a VP, only show itineraries related to their office/division
        if ($user->isVp() && !$user->isAdmin() && !$user->isMotorpoolAdmin()) {
            // Find the office that the transportation services unit belongs to
            // First, find the transportation services unit
            $transportationUnit = Unit::whereRaw('LOWER(unit_name) LIKE ?', ['%transportation services%'])->first();
            
            if ($transportationUnit) {
                // Get the division of the transportation services unit
                $transportationDivision = $transportationUnit->division;
                
                if ($transportationDivision) {
                    // Get the office of the transportation services unit
                    $transportationOffice = $transportationDivision->office;
                    
                    if ($transportationOffice) {
                        // Only show itineraries where the employee belongs to a unit in the same office
                        $query->whereHas('travelOrder.employee', function($q) use ($transportationOffice) {
                            $q->whereHas('positions', function($q2) use ($transportationOffice) {
                                $q2->whereHas('unit.division', function($q3) use ($transportationOffice) {
                                    $q3->where('office_id', $transportationOffice->id);
                                });
                            });
                        });
                    }
                }
            }
        }
        */
        
        $itineraries = $query->get();
            
        return view('itineraries.approvals.approved', compact('itineraries'));
    }
}
