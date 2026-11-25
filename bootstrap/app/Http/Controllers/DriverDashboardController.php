<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Driver;
use App\Models\Itinerary;
use App\Models\TripTicket;
use App\Models\Vehicle;

class DriverDashboardController extends Controller
{
    /**
     * Display the driver dashboard.
     */
    public function index(): View
    {
        $user = Auth::user();
        
        \Log::info('Driver Dashboard Access', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role
        ]);
        
        // Get the driver record for the authenticated user
        $driver = Driver::where('user_id', $user->id)->first();
        
        \Log::info('Driver Lookup Result', [
            'user_id' => $user->id,
            'driver_found' => $driver ? 'Yes' : 'No',
            'driver_id' => $driver ? $driver->id : null
        ]);
        
        // Instead of redirecting, pass null driver and handle in view
        if (!$driver) {
            $driver = null;
            $itineraries = collect();
            $tripTickets = collect(); 
            $calendarEvents = []; 
            $pendingCount = 0;
            $ongoingCount = 0;
            $completedCount = 0;
            $currentMonth = now()->format('Y-m');
            $todayTripTickets = collect();
            
            return view('dashboards.driver', compact(
                'driver', 
                'itineraries', 
                'tripTickets', 
                'calendarEvents',
                'pendingCount',
                'ongoingCount',
                'completedCount',
                'currentMonth',
                'todayTripTickets'
            ))->with('error', 'No driver record found for your account. Please contact administrator.');
        }
        
        /*
         * Today's assigned trip tickets.
         *
         * The driver sees today's approved/ongoing/completed ticket directly
         * on the dashboard. Cancelled tickets are intentionally excluded.
         * There is no manual Start/End action here; GPS/geofence controls
         * the actual trip status.
         */
        $today = now()->toDateString();

        $todayTripTickets = TripTicket::with(['itinerary.vehicle', 'itinerary.driver'])
            ->whereHas('itinerary', function ($query) use ($driver, $today) {
                $query->where('driver_id', $driver->id)
                    ->whereDate('date_from', '<=', $today)
                    ->whereDate('date_to', '>=', $today);
            })
            ->whereIn('status', ['Approved', 'On-Going', 'Completed'])
            ->orderByRaw("
                CASE status
                    WHEN 'On-Going' THEN 1
                    WHEN 'Approved' THEN 2
                    WHEN 'Completed' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('created_at', 'asc')
            ->get();

        // Get driver's itineraries (assigned to this driver) - using date_from
        $itineraries = Itinerary::with(['vehicle'])
            ->where('driver_id', $driver->id)
            ->orderBy('date_from', 'desc')
            ->paginate(10, ['*'], 'itineraries_page');
        
        // Get driver's trip tickets
        $tripTickets = TripTicket::with(['itinerary.vehicle'])
            ->whereHas('itinerary', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'triptickets_page');
        
        // Get trip ticket status counts for cards (matching the tab mappings)
        $pendingCount = TripTicket::whereHas('itinerary', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);
            })
            ->where('status', 'Approved')  // Pending card shows Approved tickets (ready to start)
            ->count();
        
        $ongoingCount = TripTicket::whereHas('itinerary', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);
            })
            ->where('status', 'On-Going')  // On-going card shows active trips
            ->count();
        
        $completedCount = TripTicket::whereHas('itinerary', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);
            })
            ->where('status', 'Completed')  // Completed card shows finished trips
            ->count();
        
        // Get upcoming trips for calendar (next 30 days) - using date_from and date_to
        // Only show Approved, On-going, and Completed trips (exclude Cancelled)
        $upcomingTrips = Itinerary::with(['vehicle'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['Approved', 'On-Going', 'Completed'])
            ->where(function($query) {
                $query->where('date_from', '>=', now())
                      ->orWhere('date_to', '>=', now());
            })
            ->where(function($query) {
                $query->where('date_from', '<=', now()->addDays(30))
                      ->orWhere('date_to', '<=', now()->addDays(30));
            })
            ->orderBy('date_from', 'asc')
            ->get();
        
        // Prepare calendar events data
        $calendarEvents = $this->prepareCalendarEvents($upcomingTrips);
        
        // Get current month for the month selector (format: YYYY-MM)
        $currentMonth = now()->format('Y-m');
        
        return view('dashboards.driver', compact(
            'driver', 
            'itineraries', 
            'tripTickets', 
            'calendarEvents',
            'pendingCount',
            'ongoingCount',
            'completedCount',
            'currentMonth',
            'todayTripTickets'
        ));
    }
    
    /**
     * Display driver's live trip tracking (GPS map + odometer) page.
     */
    public function trips(Request $request): View
    {
        $user = Auth::user();
        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No driver record found.');
        }

        /*
         * Trip ticket displayed in the driver header.
         *
         * This is now based on the driver's trip for TODAY, rather than only
         * an On-Going ticket.  This keeps the ticket number in the header
         * useful before the vehicle reaches the gate and automatically
         * changes to the correct ticket whenever a trip is assigned for the
         * current day.
         *
         * Priority:
         * 1. On-Going today
         * 2. Approved today
         * 3. Completed today
         *
         * Cancelled tickets are excluded.
         */
        $today = now()->toDateString();

        $activeTicket = TripTicket::with(['itinerary.vehicle'])
            ->whereHas('itinerary', function ($query) use ($driver, $today) {
                $query->where('driver_id', $driver->id)
                    ->whereDate('date_from', '<=', $today)
                    ->whereDate('date_to', '>=', $today);
            })
            ->whereIn('status', ['On-Going', 'Approved', 'Completed'])
            ->orderByRaw("
                CASE status
                    WHEN 'On-Going' THEN 1
                    WHEN 'Approved' THEN 2
                    WHEN 'Completed' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('created_at', 'asc')
            ->first();

        // Alerts are based on the vehicle actually in play: the one on the
        // active trip if a trip is on-going, otherwise the one assigned to
        // the driver's soonest upcoming approved trip - not a static
        // placeholder.
        $vehicle = $activeTicket?->itinerary?->vehicle;

        if (!$vehicle) {
            $upcomingItinerary = Itinerary::with('vehicle')
                ->where('driver_id', $driver->id)
                ->where('status', 'Approved')
                ->orderBy('date_from', 'asc')
                ->first();

            $vehicle = $upcomingItinerary?->vehicle;
        }

        $alerts = $this->buildVehicleAlerts($vehicle);

        // Today's assigned trip tickets are shown on the Trips page only when
        // there is an actual approved/ongoing/completed trip for today.
        // Cancelled tickets are intentionally excluded.
        $today = now()->toDateString();

        $todayTripTickets = TripTicket::with(['itinerary.vehicle', 'itinerary.driver'])
            ->whereHas('itinerary', function ($query) use ($driver, $today) {
                $query->where('driver_id', $driver->id)
                    ->whereDate('date_from', '<=', $today)
                    ->whereDate('date_to', '>=', $today);
            })
            ->whereIn('status', ['Approved', 'On-Going', 'Completed'])
            ->orderByRaw("
                CASE status
                    WHEN 'On-Going' THEN 1
                    WHEN 'Approved' THEN 2
                    WHEN 'Completed' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('created_at', 'asc')
            ->get();

        return view('drivers.trips', compact(
            'driver',
            'activeTicket',
            'alerts',
            'todayTripTickets'
        ));
    }

    /**
     * Build real, data-driven maintenance alerts for a vehicle (oil change
     * overdue/coming due, open maintenance issues, "Under Maintenance"
     * status) - used on the driver's Trips page instead of a static
     * placeholder alert.
     */
    private function buildVehicleAlerts(?Vehicle $vehicle)
    {
        $alerts = collect();

        if (!$vehicle) {
            return $alerts;
        }

        if ($vehicle->status === 'Under Maintenance') {
            $alerts->push((object) [
                'title' => 'Vehicle Under Maintenance',
                'message' => "{$vehicle->model} ({$vehicle->plate_number}) is currently marked Under Maintenance.",
            ]);
        }

        $issueCount = $vehicle->getActiveMaintenanceIssuesCount();
        if ($issueCount > 0) {
            $alerts->push((object) [
                'title' => $issueCount > 1 ? 'Open Maintenance Issues' : 'Open Maintenance Issue',
                'message' => $issueCount . ($issueCount > 1 ? ' maintenance issues are' : ' maintenance issue is') . ' currently logged for this vehicle.',
            ]);
        }

        if ($vehicle->needsOilChange()) {
            $overdueKm = max(0, $vehicle->getKilometersSinceLastMaintenance() - 5000);
            $alerts->push((object) [
                'title' => 'Engine Maintenance Overdue',
                'message' => "{$vehicle->model} ({$vehicle->plate_number}) is {$overdueKm} km past its 5,000 km maintenance interval.",
            ]);
        } else {
            $remaining = $vehicle->getRemainingMileageForMaintenance();

            // Only surface it once it's actually getting close - avoids
            // showing a "due in 4,800 km" alert right after every service.
            if ($remaining <= 500) {
                $alerts->push((object) [
                    'title' => 'Upcoming Maintenance',
                    'message' => "Engine Maintenance due in {$remaining} km",
                ]);
            }
        }

        return $alerts;
    }
    
    /**
     * Display driver's itineraries.
     */
    public function itineraries(Request $request): View
    {
        $user = Auth::user();
        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No driver record found.');
        }
        
        $search = $request->get('search');
        $status = $request->get('status');
        
        $query = Itinerary::with(['vehicle'])
            ->where('driver_id', $driver->id)
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('purpose', 'LIKE', "%{$search}%")
                      ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                          $vehicleQuery->where('make', 'LIKE', "%{$search}%")
                                      ->orWhere('model', 'LIKE', "%{$search}%");
                      });
                });
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('date_from', 'desc'); // Changed from date to date_from
        
        $itineraries = $query->paginate(15)->appends($request->except('page'));
        
        return view('drivers.itineraries', compact('itineraries', 'search', 'status', 'driver'));
    }
    
    /**
     * Display driver's trip tickets.
     */
    public function tripTickets(Request $request): View
    {
        $user = Auth::user();
        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No driver record found.');
        }
        
        $search = $request->get('search');
        $tab = $request->get('tab', 'pending');
        
        // Map tabs to statuses
        $statusMap = [
            'pending' => 'Approved',    // Pending tab shows Approved tickets (waiting to start)
            'ongoing' => 'On-Going',    // On-going tab shows active trips
            'completed' => 'Completed',  // Completed tab shows finished trips
            'cancelled' => 'Cancelled'   // Cancelled tab shows cancelled trips
        ];
        
        $status = $statusMap[$tab] ?? 'Pending';
        
        $query = TripTicket::with(['itinerary.vehicle'])
            ->whereHas('itinerary', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);
            })
            ->when($search, function ($query, $search) {
                return $query->where('ticket_number', 'LIKE', "%{$search}%")
                            ->orWhereHas('itinerary', function ($itineraryQuery) use ($search) {
                                $itineraryQuery->where('purpose', 'LIKE', "%{$search}%")
                                              ->orWhere('destination', 'LIKE', "%{$search}%");
                            });
            })
            ->where('status', $status)
            ->orderBy('created_at', 'desc');
        
        $tripTickets = $query->paginate(15)->appends($request->except('page'));
        
        // Get status counts for cards (matching the tab mappings)
        $pendingCount = TripTicket::whereHas('itinerary', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);
            })
            ->where('status', 'Approved')  // Pending tab shows Approved tickets
            ->count();
        
        $ongoingCount = TripTicket::whereHas('itinerary', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);
            })
            ->where('status', 'On-Going')  // On-going tab shows active trips
            ->count();
        
        $completedCount = TripTicket::whereHas('itinerary', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);
            })
            ->where('status', 'Completed')  // Completed tab shows finished trips
            ->count();
        
        $cancelledCount = TripTicket::whereHas('itinerary', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);
            })
            ->where('status', 'Cancelled')  // Cancelled tab shows cancelled trips
            ->count();
        
        return view('drivers.trip-tickets', compact(
            'tripTickets', 
            'search', 
            'tab', 
            'driver',
            'pendingCount',
            'ongoingCount',
            'completedCount',
            'cancelledCount'
        ));
    }
    
    /**
     * Display a single trip ticket.
     */
    public function showTripTicket(int $id): View
    {
        $user = Auth::user();
        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No driver record found.');
        }
        
        $tripTicket = TripTicket::with(['itinerary.vehicle', 'itinerary.travelOrders'])
            ->whereHas('itinerary', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);
            })
            ->findOrFail($id);
        
        return view('drivers.trip-ticket-show', compact('tripTicket', 'driver'));
    }
    
    /**
     * Get calendar events for AJAX requests.
     */
    public function getCalendarEvents(Request $request)
    {
        $user = Auth::user();
        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['error' => 'No driver record found.'], 404);
        }
        
        $startDate = $request->get('start');
        $endDate = $request->get('end');
        
        $trips = Itinerary::with(['vehicle'])
            ->where('driver_id', $driver->id)
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('date_from', [$startDate, $endDate])
                      ->orWhereBetween('date_to', [$startDate, $endDate]);
            })
            ->get();
        
        $events = $this->prepareCalendarEvents($trips);
        
        return response()->json($events);
    }
    
    /**
     * Prepare calendar events data.
     */
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
                    $statusClass= 'bg-gray-100 text-gray-800';
            }
                
            // Get date_from and date_to
            $dateFrom = $trip->date_from ?? $trip->date_to ?? now();
            $dateTo = $trip->date_to ?? $dateFrom;
                
            // Check if date_from and date_to are the same day
            $isSameDay = $dateFrom->format('Y-m-d') === $dateTo->format('Y-m-d');
                
            if ($isSameDay) {
                // Single day event - show only on that day
                $events[] = [
                    'id' => $trip->id,
                    'title' => $trip->purpose ?? 'Unnamed Trip',
                    'start' => $dateFrom->format('Y-m-d'),
                    'allDay' => true,
                    'extendedProps' => [
                        'vehicle' => $trip->vehicle ? $trip->vehicle->make . ' ' . $trip->vehicle->model : 'No vehicle assigned',
                        'status' => $trip->status,
                        'statusClass' => $statusClass,
                        'destination' => $trip->destination ?? 'Not specified',
                    ]
                ];
            } else {
                // Multi-day event - create events for each day in the range
                $currentDate = clone $dateFrom;
                while ($currentDate <= $dateTo) {
                    $events[] = [
                        'id' => $trip->id,
                        'title' => $trip->purpose ?? 'Unnamed Trip',
                        'start' => $currentDate->format('Y-m-d'),
                        'allDay' => true,
                        'extendedProps' => [
                            'vehicle' => $trip->vehicle ? $trip->vehicle->make . ' ' . $trip->vehicle->model : 'No vehicle assigned',
                            'status' => $trip->status,
                            'statusClass' => $statusClass,
                            'destination' => $trip->destination ?? 'Not specified',
                            'isMultiDayEvent' => true,
                            'originalStart' => $dateFrom->format('Y-m-d'),
                            'originalEnd' => $dateTo->format('Y-m-d'),
                        ]
                    ];
                    $currentDate->modify('+1 day');
                }
            }
        }
            
        return $events;
    }
    
    /**
     * API endpoint for calendar events (FullCalendar)
     */
    public function apiCalendarEvents(Request $request)
    {
        try {
            $user = Auth::user();
            \Log::info('apiCalendarEvents - User authenticated', ['user_id' => $user->id]);
            
            $driver = Driver::where('user_id', $user->id)->first();
            
            if (!$driver) {
                \Log::warning('Driver not found for user', ['user_id' => $user->id]);
             return response()->json(['error' => 'No driver record found.'], 404);
            }
            
            \Log::info('Driver found', ['driver_id' => $driver->id, 'driver_name' => $driver->full_name]);
            
            $start = $request->query('start');
            $end = $request->query('end');
            
            \Log::info('Fetching calendar events', [
                'driver_id' => $driver->id,
                'start' => $start,
                'end' => $end
            ]);
            
            // Get trip tickets where itinerary is assigned to this driver
            // Load itinerary with vehicle and driver relationships
            $tripTicketsQuery = TripTicket::with(['itinerary.vehicle', 'itinerary.driver'])
                ->whereHas('itinerary', function($query) use ($driver, $start, $end) {
                    $query->where('driver_id', $driver->id)
                          ->whereBetween('date_from', [$start, $end]);
                })
                ->whereIn('status', ['Approved', 'On-Going', 'Completed']);
            
            \Log::info('Trip tickets query built', ['count' => $tripTicketsQuery->count()]);
            
            $tripTickets = $tripTicketsQuery->get();
            
            \Log::info('Trip tickets retrieved', ['count' => $tripTickets->count()]);
            
            $result = [];
            foreach ($tripTickets as $ticket) {
                try {
                    \Log::info('Processing ticket', ['ticket_id' => $ticket->id]);
                    
                    // Get the first travel order using the getAllTravelOrders method
                   $firstTravelOrder= null;
                  if ($ticket->itinerary) {
                      try {
                          $travelOrders = $ticket->itinerary->getAllTravelOrders();
                         $firstTravelOrder= $travelOrders->isNotEmpty() ? $travelOrders->first() : null;
                      } catch (\Exception $e) {
                          \Log::error('Error getting travel orders', [
                              'ticket_id' => $ticket->id,
                              'itinerary_id' => $ticket->itinerary_id,
                              'error' => $e->getMessage()
                          ]);
                          $firstTravelOrder= null;
                      }
                  }
                 
                  $passengersArray = [];
                  if (is_array($ticket->passengers)) {
                      foreach ($ticket->passengers as $passenger) {
                          if (is_string($passenger) && strpos($passenger, '|') !== false) {
                              $passengersArray = array_merge($passengersArray, explode('|', $passenger));
                          } else if (!empty($passenger)) {
                              $passengersArray[] = $passenger;
                          }
                      }
                  }
                  
                  $result[] = [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number ?? 'N/A',
                        'driver_name' => $ticket->itinerary?->driver?->full_name ?? 'Driver Assigned',
                        'head_of_party' => $ticket->head_of_party ?? 'Not specified',
                        'itinerary' => [
                            // Format dates explicitly to avoid timezone issues
                            'date_from' => $ticket->itinerary?->date_from ? $ticket->itinerary->date_from->format('Y-m-d') : null,
                            'date_to' => $ticket->itinerary?->date_to ? $ticket->itinerary->date_to->format('Y-m-d') : null,
                            'departure_time' => $ticket->itinerary?->departure_time,
                            'destination' => $ticket->itinerary?->destination,
                            'purpose' => $ticket->itinerary?->purpose,
                            'vehicle' => [
                                'model' => $ticket->itinerary?->vehicle?->model,
                                'plate_number' => $ticket->itinerary?->vehicle?->plate_number
                            ],
                            'travel_order_employee' => $firstTravelOrder && $firstTravelOrder->employee ? [
                                'full_name' => $firstTravelOrder->employee->full_name,
                                'contact_num' => $firstTravelOrder->employee->contact_num
                            ] : null
                        ],
                        'passengers' => array_values(array_filter($passengersArray)),
                        'status' => $ticket->status,
                        'created_at' => $ticket->created_at ? $ticket->created_at->format('Y-m-d') : null
                    ];
                    
                    \Log::info('Ticket processed', [
                        'ticket_id' => $ticket->id,
                        'passengers_raw' => $ticket->passengers,
                        'passengers_type' => gettype($ticket->passengers),
                        'passengers_processed' => $result[count($result)-1]['passengers']
                    ]);
                    
                    \Log::info('Ticket processed successfully', ['ticket_id' => $ticket->id]);
                    
                } catch (\Exception $e) {
                    \Log::error('Error processing individual ticket', [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Skip this ticket and continue
                    continue;
                }
            }
            
            \Log::info('Returning result', ['result_count' => count($result)]);
          return response()->json($result);
           
        } catch (\Exception $e) {
            \Log::error('Error in apiCalendarEvents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
          return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * API endpoint for status counts by month
     */
    public function apiStatusCounts(Request $request)
    {
        $user = Auth::user();
        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['error' => 'No driver record found.'], 404);
        }
        
        $month = $request->query('month');
        
        if ($month) {
            [$year, $monthNum] = explode('-', $month);
            $startDate = "$year-$monthNum-01";
            $endDate = date('Y-m-t', strtotime($startDate));
        } else {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
        }
        
        $counts = TripTicket::select('status')
            ->whereHas('itinerary', function($query) use ($driver, $startDate, $endDate) {
                $query->where('driver_id', $driver->id)
                      ->whereBetween('date_from', [$startDate, $endDate]);
            })
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();
        
        return response()->json([
            'pending' => $counts['Approved'] ?? 0,      // Pending tab shows Approved tickets
            'ongoing' => $counts['On-Going'] ?? 0,       // On-going tab shows active trips
            'completed' => $counts['Completed'] ?? 0,    // Completed tab shows finished trips
            'cancelled' => $counts['Cancelled'] ?? 0     // Cancelled tab shows cancelled trips
        ]);
    }
}