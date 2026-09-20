<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\Driver;
use App\Models\TripTicket;
use App\Models\Itinerary;

class DashboardController extends Controller
{
    /**
     * Display the appropriate dashboard based on user role
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        // If user is not logged in, redirect to login
        if (!$user) {
            return redirect()->route('login');
        }

        // Determine which dashboard to show based on user role
        if ($user->isMotorpoolAdmin()) {
            // Redirect motorpool admin to their specific dashboard
            return redirect()->route('motorpool.dashboard');
        } elseif ($user->isAdmin()) {
            return view('dashboards.admin');
        } elseif ($user->isDriver()) {
            // Drivers land on the live Trips/tracking page first - the
            // Schedule of Trips (calendar) view is one tap away via the
            // bottom nav, but the live map is what they need immediately.
            return redirect()->route('driver.trips');
        } elseif ($user->isEmployee()) {
            $user->load('employee');
            
            // Check if employee is a head (division head, VP, or unit head)
            $employee = $user->employee;
            if ($employee) {
                // Determine which dashboard to show based on specific role
                if ($employee->is_president) {
                    // Get trip tickets for president
                    $myTripTickets = \App\Models\TripTicket::with(['itinerary.vehicle', 'itinerary.driver'])
                        ->where('status', 'Approved')
                        ->where(function ($query) use ($employee) {
                            $query->where('head_of_party', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                  ->orWhere('head_of_party', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%")
                                  ->orWhere(function ($passengerQuery) use ($employee) {
                                      $passengerQuery->where('passengers', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                                     ->orWhere('passengers', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%");
                                  })
                                  // Check if employee is the original travel order creator
                                  ->orWhere(function ($itineraryQuery) use ($employee) {
                                      $itineraryQuery->whereHas('itinerary', function ($subQuery) use ($employee) {
                                          $subQuery->whereRaw("JSON_CONTAINS(travel_order_id, ?)", [json_encode($employee->id)])
                                                   ->orWhereRaw("JSON_EXTRACT(travel_order_id, '$[0]') = ?", [$employee->id]);
                                      });
                                  });
                        })
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();
                    
                    return view('dashboards.president', compact('myTripTickets'));
                } elseif ($employee->is_vp) {
                    // Check if this VP belongs to the Office of the Vice President for Administration
                    $isVpOfAdministration = $employee->positions()->whereHas('office', function($query) {
                        $query->where('office_name', 'Office of the Vice President for Administration');
                    })->exists();
                    
                    // Count travel orders that require VP approval (for all VPs)
                    $pendingTravelOrders = \App\Models\TravelOrder::where(function ($query) use ($employee) {
                            // Include division head travel orders that need VP approval (division heads approve their own)
                            $query->whereHas('employee.officer', function ($officerQuery) {
                                $officerQuery->where('division_head', true)
                                      ->where('vp', false)
                                      ->where('president', false);
                            })
                            ->where(function($subQuery) {
                                // Division head travel orders don't need head approval, they go directly to VP
                                $subQuery->whereNull('approver2_approved_at'); // Changed from vp_approved_at
                            })
                            ->orWhere(function ($orQuery) {
                                // Include unit head travel orders that have been approved by division head
                                $orQuery->whereHas('employee.officer', function ($officerQuery) {
                                    $officerQuery->where('unit_head', true)
                                          ->where('division_head', false)
                                          ->where('vp', false)
                                          ->where('president', false);
                                })
                                ->whereNotNull('approver1_approved_at'); // Approved by first approver
                            });
                        })
                        ->where(function ($positionQuery) use ($employee) {
                            // Either in the same office as the VP
                            $positionQuery->whereHas('position', function ($posQuery) use ($employee) {
                                $vpPrimaryPosition = $employee->positions()->where('is_primary', true)->first();
                                $vpOfficeId = $vpPrimaryPosition ? $vpPrimaryPosition->office_id : null;
                                $posQuery->where('office_id', $vpOfficeId);
                            })
                            // OR direct to President if no VP in same office
                            ->orWhere(function ($orQuery) use ($employee) {
                                // Check if there's no VP in the same office
                                $vpPrimaryPosition = $employee->positions()->where('is_primary', true)->first();
                                $vpOfficeId = $vpPrimaryPosition ? $vpPrimaryPosition->office_id : null;
                                
                                $vpInSameOffice = \App\Models\Employee::whereHas('officer', function($query) use ($vpOfficeId) {
                                    $query->where('vp', true)
                                          ->whereHas('position', function($posQuery) use ($vpOfficeId) {
                                              $posQuery->where('office_id', $vpOfficeId);
                                          });
                                })->exists();
                                
                                // If no VP in same office, direct to President
                                if (!$vpInSameOffice) {
                                    // Check if there's a President
                                    $presidentExists = \App\Models\Employee::whereHas('officer', function($query) {
                                        $query->where('president', true);
                                    })->exists();
                                    
                                    // If President exists, include all travel orders for President approval
                                    if ($presidentExists) {
                                        $orQuery->whereNotNull('approver1_approved_at') // Only approved by first approver
                                                ->whereNull('approver2_approved_at'); // Not yet approved by second approver (VP)
                                    } else {
                                        // No President either - include nothing
                                        $orQuery->whereRaw('1 = 0');
                                    }
                                } else {
                                    // VP exists in same office 
                                    $orQuery->whereRaw('1 = 0');
                                }
                            });
                        })
                        ->whereNull('approver2_approved_at') // Changed from vp_approved_at
                        ->count();
                    
                    // Only calculate itinerary and trip ticket counts for VP of Office of the Vice President for Administration
                    if ($isVpOfAdministration) {
                        $pendingItineraries = \App\Models\Itinerary::where('unit_head_approved', true)
                            ->where(function($query) {
                                $query->where('vp_approved', false)
                                      ->orWhereNull('vp_approved');
                            })
                            ->count();
                                            
                        $pendingTripTickets = \App\Models\TripTicket::where('status', 'Pending')
                            ->count();
                    } else {
                        $pendingItineraries = 0;
                        $pendingTripTickets = 0;
                    }
                    
                    // Get trip tickets for VP
                    $myTripTickets = \App\Models\TripTicket::with(['itinerary.vehicle', 'itinerary.driver'])
                        ->where('status', 'Approved')
                        ->where(function ($query) use ($employee) {
                            $query->where('head_of_party', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                  ->orWhere('head_of_party', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%")
                                  ->orWhere(function ($passengerQuery) use ($employee) {
                                      $passengerQuery->where('passengers', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                                     ->orWhere('passengers', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%");
                                  })
                                  // Check if employee is the original travel order creator
                                  ->orWhere(function ($itineraryQuery) use ($employee) {
                                      $itineraryQuery->whereHas('itinerary', function ($subQuery) use ($employee) {
                                          // Get all travel orders and check if any belong to the employee
                                          $subQuery->whereRaw("JSON_CONTAINS(travel_order_id, ?)", [json_encode($employee->id)])
                                                   ->orWhereRaw("JSON_EXTRACT(travel_order_id, '$[0]') = ?", [$employee->id]);
                                      });
                                  });
                        })
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();
                    
                    return view('dashboards.vp', compact('isVpOfAdministration', 'pendingTravelOrders', 'pendingItineraries', 'pendingTripTickets', 'myTripTickets'));
                } elseif ($employee->is_divisionhead) {
                    // Get trip tickets for division head
                    $myTripTickets = \App\Models\TripTicket::with(['itinerary.vehicle', 'itinerary.driver'])
                        ->where('status', 'Approved')
                        ->where(function ($query) use ($employee) {
                            $query->where('head_of_party', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                  ->orWhere('head_of_party', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%")
                                  ->orWhere(function ($passengerQuery) use ($employee) {
                                      $passengerQuery->where('passengers', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                                     ->orWhere('passengers', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%");
                                  })
                                  // Check if employee is the original travel order creator
                                  ->orWhere(function ($itineraryQuery) use ($employee) {
                                      $itineraryQuery->whereHas('itinerary', function ($subQuery) use ($employee) {
                                          $subQuery->whereRaw("JSON_CONTAINS(travel_order_id, ?)", [json_encode($employee->id)])
                                                   ->orWhereRaw("JSON_EXTRACT(travel_order_id, '$[0]') = ?", [$employee->id]);
                                      });
                                  });
                        })
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();
                    
                    return view('dashboards.divisionhead', compact('myTripTickets'));
                } elseif ($employee->is_head && !$employee->is_divisionhead && !$employee->is_vp) {
                    // Get trip tickets for unit head
                    $myTripTickets = \App\Models\TripTicket::with(['itinerary.vehicle', 'itinerary.driver'])
                        ->where('status', 'Approved')
                        ->where(function ($query) use ($employee) {
                            $query->where('head_of_party', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                  ->orWhere('head_of_party', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%")
                                  ->orWhere(function ($passengerQuery) use ($employee) {
                                      $passengerQuery->where('passengers', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                                     ->orWhere('passengers', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%");
                                  })
                                  // Check if employee is the original travel order creator
                                  ->orWhere(function ($itineraryQuery) use ($employee) {
                                      $itineraryQuery->whereHas('itinerary', function ($subQuery) use ($employee) {
                                          $subQuery->whereRaw("JSON_CONTAINS(travel_order_id, ?)", [json_encode($employee->id)])
                                                   ->orWhereRaw("JSON_EXTRACT(travel_order_id, '$[0]') = ?", [$employee->id]);
                                      });
                                  });
                        })
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();
                    
                    return view('dashboards.unithead', compact('myTripTickets'));
                } elseif ($employee->is_head) {
                    // Get trip tickets for head
                    $myTripTickets = \App\Models\TripTicket::with(['itinerary.vehicle', 'itinerary.driver'])
                        ->where('status', 'Approved')
                        ->where(function ($query) use ($employee) {
                            $query->where('head_of_party', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                  ->orWhere('head_of_party', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%")
                                  ->orWhere(function ($passengerQuery) use ($employee) {
                                      $passengerQuery->where('passengers', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                                     ->orWhere('passengers', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%");
                                  })
                                  // Check if employee is the original travel order creator
                                  ->orWhere(function ($itineraryQuery) use ($employee) {
                                      $itineraryQuery->whereHas('itinerary', function ($subQuery) use ($employee) {
                                          $subQuery->whereRaw("JSON_CONTAINS(travel_order_id, ?)", [json_encode($employee->id)])
                                                   ->orWhereRaw("JSON_EXTRACT(travel_order_id, '$[0]') = ?", [$employee->id]);
                                      });
                                  });
                        })
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();
                    
                    return view('dashboards.head', compact('myTripTickets'));
                } else {
                    // Regular employee dashboard
                    $employee = $user->employee;
                    
                    // Get trip tickets where employee is passenger, head of party, or the original travel order creator
                    $myTripTickets = \App\Models\TripTicket::with(['itinerary.vehicle', 'itinerary.driver'])
                        ->where('status', 'Approved') // Only approved trip tickets
                        ->where(function ($query) use ($employee) {
                            // Check if employee is head of party
                            $query->where('head_of_party', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                  ->orWhere('head_of_party', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%")
                                  // Check if employee is a passenger
                                  ->orWhere(function ($passengerQuery) use ($employee) {
                                      $passengerQuery->where('passengers', 'LIKE', "%{$employee->first_name}%{$employee->last_name}%")
                                                     ->orWhere('passengers', 'LIKE', "%{$employee->last_name}%{$employee->first_name}%");
                                  })
                                  // Check if employee is the original travel order creator
                                  ->orWhereHas('itinerary', function ($itineraryQuery) use ($employee) {
                                      $itineraryQuery->whereRaw("JSON_CONTAINS(travel_order_id, ?)", [json_encode($employee->id)])
                                                   ->orWhereRaw("JSON_EXTRACT(travel_order_id, '$[0]') = ?", [$employee->id]);
                                  });
                        })
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();
                    
                    return view('dashboards.employee', compact('myTripTickets'));
                }
            } else {
                // Regular employee dashboard
                return view('dashboards.employee');
            }
        }

        // Default dashboard if no role matches
        return view('dashboard');
    }

    private function prepareCalendarEvents($trips)
    {
        $events = [];
        
        foreach ($trips as $trip) {
            $statusClass = '';
            switch ($trip->status) {
                case 'Approved':
                    $statusClass = 'bg-green-100 text-green-800';
                    break;
                case 'Pending':
                    $statusClass = 'bg-yellow-100 text-yellow-800';
                    break;
                case 'Rejected':
                    $statusClass = 'bg-red-100 text-red-800';
                    break;
                default:
                    $statusClass = 'bg-gray-100 text-gray-800';
            }
        
            $eventDate = $trip->date_from ?? $trip->date_to ?? now();
            
            $events[] = [
                'id' => $trip->id,
                'title' => $trip->purpose ?? 'Unnamed Trip',
                'start' => $eventDate->format('Y-m-d'),
                'allDay' => true,
                'extendedProps' => [
                    'vehicle' => $trip->vehicle ? $trip->vehicle->make . ' ' . $trip->vehicle->model : 'No vehicle assigned',
                    'status' => $trip->status,
                    'statusClass' => $statusClass,
                    'destination' => $trip->destination ?? 'Not specified',
                ]
            ];
        }
        
        return $events;
    }
}