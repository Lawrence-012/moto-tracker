<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\TravelOrder;
use App\Models\TripTicket;
use App\Models\Employee;
use App\Models\Itinerary;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\VehicleMaintenance;

class MotorpoolAdminController extends Controller
{
    /**
     * Display the motorpool admin dashboard.
     */
    public function dashboard(): View
    {
        // Get current month for the month selector (format: YYYY-MM)
        $currentMonth = now()->format('Y-m');
        
        // Get counts for dashboard cards
        $itinerariesCount = Itinerary::count();
        $tripTicketsCount = TripTicket::count();
        $vehiclesCount = Vehicle::count();
        $driversCount = Driver::count();
        
        // Get trip status counts
        // PENDING card shows Approved status (ready for motorpool processing)
        $pendingCount = TripTicket::where('status', 'Approved')->count();
        // ON-GOING card shows On-Going status
        $ongoingCount = TripTicket::where('status', 'On-Going')->count();
        // COMPLETED card shows Completed status
        $completedCount = TripTicket::where('status', 'Completed')->count();
        
        // Get approved travel orders for the table
        $travelOrders = TravelOrder::where('status', 'approved')
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // --- MAINTENANCE & NOTIFICATION DATA ---
        // Fetch vehicles needing oil change (>5000km threshold) that haven't been dismissed by current user
        $vehiclesNeedingOilChange = Vehicle::all()->filter(function ($vehicle) {
            return $vehicle->needsOilChange() && !$vehicle->isOilChangeNotificationDismissed();
        });

        // Active maintenance requests
        $activeMaintenance = VehicleMaintenance::with('vehicle')
            ->whereIn('status', ['Need Maintenance', 'Under Maintenance', 'Pending'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Total maintenance notification badge count
        $maintenanceNotificationCount = $vehiclesNeedingOilChange->count() + $activeMaintenance->where('status', 'Need Maintenance')->count();
        
        return view('dashboards.motorpool-admin', compact(
            'itinerariesCount',
            'tripTicketsCount', 
            'vehiclesCount',
            'driversCount',
            'pendingCount',
            'ongoingCount',
            'completedCount',
            'travelOrders',
            'currentMonth',
            'vehiclesNeedingOilChange',
            'activeMaintenance',
            'maintenanceNotificationCount'
        ));
    }
    
    /**
     * API endpoint for calendar events
     */
    public function calendarEvents(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');
        
        \Log::info('Motorpool Calendar API Called', [
            'start' => $start,
            'end' => $end
        ]);
        
        // Get ALL trip tickets (not filtered by driver)
       $tripTickets = TripTicket::with(['itinerary', 'itinerary.driver', 'itinerary.vehicle'])
        ->whereHas('itinerary', function($query) use ($start, $end) {
            $query->whereBetween('date_from', [$start, $end]);
        })
        ->get()
        ->map(function($ticket) {
            if ($ticket->itinerary) {
                \Log::info('Itinerary Found', [
                    'itinerary_id' => $ticket->itinerary->id,
                    'driver_id_in_db' => $ticket->itinerary->driver_id,
                ]);
                
                if ($ticket->itinerary->driver) {
                    \Log::info('Driver Loaded from DB', [
                        'driver_id' => $ticket->itinerary->driver->id,
                        'full_name' => $ticket->itinerary->driver->full_name,
                        'full_name2' => $ticket->itinerary->driver->full_name2,
                        'first_name' => $ticket->itinerary->driver->first_name,
                        'last_name' => $ticket->itinerary->driver->last_name,
                    ]);
                } else {
                    \Log::error('Driver NOT loaded - checking if driver_id exists in DB');
                    $directDriver = \DB::table('drivers')->find($ticket->itinerary->driver_id);
                    \Log::info('Direct DB Query Result', ['driver' => $directDriver]);
                }
            }
            // Get the first travel order - lazy load if needed
           $firstTravelOrder = null;
           if ($ticket->itinerary) {
               $travelOrders = $ticket->itinerary->getAllTravelOrders();
               $firstTravelOrder = $travelOrders->isNotEmpty() ? $travelOrders->first() : null;
           }

            // Safely get itinerary data
           $itinerary = $ticket->itinerary;
           $driver = $itinerary ? $itinerary->driver : null;
           
           \Log::info('Trip Ticket Driver', [
               'ticket_id' => $ticket->id,
               'itinerary_id' => $itinerary?->id,
               'driver_id' => $itinerary?->driver_id,
               'driver_loaded' => !!$driver,
               'driver_first_name' => $driver?->first_name,
               'driver_last_name' => $driver?->last_name,
               'driver_full_name' => $driver?->full_name,
               'driver_full_name2' => $driver?->full_name2
           ]);
           
           $vehicle = $itinerary && $itinerary->vehicle ? $itinerary->vehicle : null;

            $employee = $firstTravelOrder && $firstTravelOrder->employee ? $firstTravelOrder->employee : null;
            
             return [
                  'id' => $ticket->id,
                  'title' => $driver && !empty(trim($driver->full_name2)) ? trim($driver->full_name2) : ($ticket->ticket_number ? $ticket->ticket_number : 'Trip'),
                  'ticket_number' => $ticket->ticket_number ? $ticket->ticket_number : 'N/A',
                  'driver_name' => $driver && !empty(trim($driver->full_name2)) ? trim($driver->full_name2) : 'No Driver Assigned',
                  'driver_contact_num' => $driver && $driver->contact_num ? $driver->contact_num : null,
                'head_of_party' => $ticket->head_of_party ? $ticket->head_of_party : 'Not specified',
                'itinerary' => [
                    // Format dates explicitly to avoid timezone issues
                    'date_from' => $itinerary && $itinerary->date_from ? $itinerary->date_from->format('Y-m-d') : null,
                    'date_to' => $itinerary && $itinerary->date_to ? $itinerary->date_to->format('Y-m-d') : null,
                    'departure_time' => $itinerary && $itinerary->departure_time ? $itinerary->departure_time : null,
                    'destination' => $itinerary && $itinerary->destination ? $itinerary->destination : null,
                    'purpose' => $itinerary && $itinerary->purpose ? $itinerary->purpose : null,
                    'vehicle' => [
                        'model' => $vehicle && $vehicle->model ? $vehicle->model : null,
                        'type' => $vehicle && $vehicle->type ? $vehicle->type : null,
                        'plate_number' => $vehicle && $vehicle->plate_number ? $vehicle->plate_number : null
                    ],
                    'travel_order_employee' => $employee ? [
                        'full_name' => $employee->full_name ? $employee->full_name : null,
                        'contact_num' => $employee->contact_num ? $employee->contact_num : null
                    ] : null
                ],
                'passengers' => is_array($ticket->passengers) ? array_values($ticket->passengers) : [],
                'status' => $ticket->status,
                'created_at' => $ticket->created_at ? $ticket->created_at->format('Y-m-d') : null
            ];
        });
        
        \Log::info('Motorpool Calendar API Response', ['count' => count($tripTickets)]);
        
       return response()->json($tripTickets);
    }
    
    /**
     * API endpoint for status counts by month
     */
    public function statusCounts(Request $request)
    {
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
            ->whereHas('itinerary', function($query) use ($startDate, $endDate) {
                $query->whereBetween('date_from', [$startDate, $endDate]);
            })
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();
        
        return response()->json([
            // pending returns Approved status (ready for motorpool)
            'pending' => $counts['Approved'] ?? 0,
            // ongoing returns On-Going status
            'ongoing' => $counts['On-Going'] ?? 0,
            // completed returns Completed status
            'completed' => $counts['Completed'] ?? 0
        ]);
    }
    
    /**
     * Display approved travel orders for the motorpool with tabs.
     */
    public function approvedTravelOrders(Request $request): View
    {
        // Get search term if provided
        $search = $request->get('search', '');
        
        // Get tab (pending, completed, cancelled)
        $tab = $request->get('tab', 'pending');
        
        // Build query based on tab
        $query = TravelOrder::with('employee');
        
        switch ($tab) {
            case 'completed':
                $query->where('status', 'completed');
                break;
            case 'cancelled':
                $query->where('status', 'cancelled');
                break;
            case 'pending':
            default:
                // Pending shows approved status (ready for motorpool)
                $query->where('status', 'approved');
                break;
        }
        
        // Apply search filter if provided
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('destination', 'LIKE', "%{$search}%")
                  ->orWhere('purpose', 'LIKE', "%{$search}%")
                  ->orWhereHas('employee', function($q) use ($search) {
                      $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        $travelOrders = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return view('motorpool-admin.travel-orders.index', compact('travelOrders', 'search', 'tab'));
    }
    
    /**
     * API endpoint for searching travel orders in dashboard
     */
    public function searchTravelOrders(Request $request)
    {
        $search = $request->get('q', '');
        
        $query = TravelOrder::where('status', 'approved')
            ->with('employee');
            
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('destination', 'LIKE', "%{$search}%")
                  ->orWhere('purpose', 'LIKE', "%{$search}%")
                  ->orWhereHas('employee', function($q) use ($search) {
                      $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        $travelOrders = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json([
            'data' => $travelOrders->items(),
            'current_page' => $travelOrders->currentPage(),
            'last_page' => $travelOrders->lastPage(),
            'total' => $travelOrders->total(),
            'per_page' => $travelOrders->perPage()
        ]);
    }
    
    /**
     * API endpoint for most used vehicles analytics
     */
    public function mostUsedVehicles(Request $request)
    {
        try {
            $period = $request->get('period', 'monthly');
            $filterValue = $request->get('filter_value', now()->format('Y-m'));
            
            // Calculate date range based on period and filter
            [$startDate, $endDate] = $this->calculateDateRange($period, $filterValue);
            
            // Get most used vehicles (completed trips only)
            $vehicles = Vehicle::select('vehicles.id', 'vehicles.model', 'vehicles.plate_number')
                ->join('itineraries', 'vehicles.id', '=', 'itineraries.vehicle_id')
                ->join('trip_tickets', 'itineraries.id', '=', 'trip_tickets.itinerary_id')
                ->whereBetween('itineraries.date_from', [$startDate, $endDate])
                ->where('trip_tickets.status', 'Completed')
                ->groupBy('vehicles.id', 'vehicles.model', 'vehicles.plate_number')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(10)
                ->get()
                ->map(function($vehicle) use ($startDate, $endDate) {
                   return [
                        'id' => $vehicle->id,
                        'label' => $vehicle->model ? "{$vehicle->model} ({$vehicle->plate_number})" : $vehicle->plate_number,
                        'count' => DB::table('itineraries')
                            ->join('trip_tickets', 'itineraries.id', '=', 'trip_tickets.itinerary_id')
                            ->where('itineraries.vehicle_id', $vehicle->id)
                            ->whereBetween('itineraries.date_from', [$startDate, $endDate])
                            ->where('trip_tickets.status', 'Completed')
                            ->count()
                    ];
                });
            
           return response()->json([
                'data' => $vehicles,
                'dateRange' => $this->formatDateRange($period, $filterValue)
            ]);
        } catch (\Exception $e) {
            \Log::error('Most Used Vehicles API Error: ' . $e->getMessage());
           return response()->json([
                'error' => 'Failed to load vehicle data',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    /**
     * API endpoint for most assigned drivers analytics
     */
    public function mostAssignedDrivers(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $filterValue = $request->get('filter_value', now()->format('Y-m'));
        
        // Calculate date range based on period and filter
        [$startDate, $endDate] = $this->calculateDateRange($period, $filterValue);
        
        // Get most assigned drivers (completed trips only)
        $drivers = Driver::select('drivers.id', 'drivers.first_name', 'drivers.last_name', 'drivers.middle_initial')
            ->join('itineraries', 'drivers.id', '=', 'itineraries.driver_id')
            ->join('trip_tickets', 'itineraries.id', '=', 'trip_tickets.itinerary_id')
            ->whereBetween('itineraries.date_from', [$startDate, $endDate])
            ->where('trip_tickets.status', 'Completed')
            ->groupBy('drivers.id', 'drivers.first_name', 'drivers.last_name', 'drivers.middle_initial')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->get()
            ->map(function($driver) use ($startDate, $endDate) {
                return [
                    'id' => $driver->id,
                    'label' => trim("{$driver->first_name} {$driver->middle_initial}. {$driver->last_name}"),
                    'count' => DB::table('itineraries')
                        ->join('trip_tickets', 'itineraries.id', '=', 'trip_tickets.itinerary_id')
                        ->where('itineraries.driver_id', $driver->id)
                        ->whereBetween('itineraries.date_from', [$startDate, $endDate])
                        ->where('trip_tickets.status', 'Completed')
                        ->count()
                ];
            });
        
        return response()->json([
            'data' => $drivers,
            'dateRange' => $this->formatDateRange($period, $filterValue)
        ]);
    }
    
    /**
     * API endpoint for completed trips analytics
     */
    public function completedTrips(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $filterValue = $request->get('filter_value', now()->format('Y-m'));
        
        // Calculate date range based on period and filter
        [$startDate, $endDate] = $this->calculateDateRange($period, $filterValue);
        
        // Get completed trips grouped by status or date
        $trips = TripTicket::select('status', DB::raw('count(*) as count'))
            ->whereHas('itinerary', function($query) use ($startDate, $endDate) {
                $query->whereBetween('date_from', [$startDate, $endDate]);
            })
            ->groupBy('status')
            ->get();
        
        $totalCompleted = $trips->where('status', 'Completed')->sum('count');
        $totalOngoing = $trips->where('status', 'On-Going')->sum('count');
        $totalPending = $trips->where('status', 'Pending')->sum('count');
        
        return response()->json([
            'data' => [
                ['status' => 'Completed', 'count' => $totalCompleted],
                ['status' => 'On-Going', 'count' => $totalOngoing],
                ['status' => 'Pending', 'count' => $totalPending]
            ],
            'dateRange' => $this->formatDateRange($period, $filterValue)
        ]);
    }
    
    /**
     * API endpoint for top destinations analytics (now showing regions)
     */
    public function topDestinations(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $filterValue = $request->get('filter_value', now()->format('Y-m'));
        
        // Calculate date range based on period and filter
        [$startDate, $endDate] = $this->calculateDateRange($period, $filterValue);
        
        // Get top 10 destinations (completed trips only)
        $destinations = Itinerary::select('destination', DB::raw('count(*) as count'))
            ->join('trip_tickets', 'itineraries.id', '=', 'trip_tickets.itinerary_id')
            ->whereBetween('itineraries.date_from', [$startDate, $endDate])
            ->where('trip_tickets.status', 'Completed')
            ->whereNotNull('destination')
            ->groupBy('destination')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->get()
            ->map(function($destination) {
                // Convert destination to region
                $region = $this->getRegionFromDestination($destination->destination);
                
                return [
                    'destination' => $region,
                    'count' => $destination->count
                ];
            })
            ->groupBy('destination')
            ->map(function($group) {
                return [
                    'destination' => $group->first()['destination'],
                    'count' => $group->sum('count')
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->take(10);
        
        return response()->json([
            'data' => $destinations,
            'dateRange' => $this->formatDateRange($period, $filterValue)
        ]);
    }
    
    /**
     * Map destination to its region
     */
    private function getRegionFromDestination($destination)
    {
        // Philippines Region Mapping
        $regionMap = [
            // MIMAROPA (Region IV-B)
            'palawan' => 'MIMAROPA (Region IV-B)',
            'puerto princesa' => 'MIMAROPA (Region IV-B)',
            'el nido' => 'MIMAROPA (Region IV-B)',
            'coron' => 'MIMAROPA (Region IV-B)',
            
            // Western Visayas (Region VI)
            'boracay' => 'Western Visayas (Region VI)',
            'iloilo' => 'Western Visayas (Region VI)',
            'bacolod' => 'Western Visayas (Region VI)',
            
            // Central Visayas (Region VII)
            'cebu' => 'Central Visayas (Region VII)',
            'bohol' => 'Central Visayas (Region VII)',
            'dumaguete' => 'Central Visayas (Region VII)',
            
            // National Capital Region (NCR)
            'manila' => 'National Capital Region (NCR)',
            'quezon city' => 'National Capital Region (NCR)',
            'makati' => 'National Capital Region (NCR)',
            
            // Cordillera Administrative Region(CAR)
            'baguio' => 'Cordillera Administrative Region (CAR)',
            'banaue' => 'Cordillera Administrative Region(CAR)',
            
            // Ilocos Region (Region I)
            'laoag' => 'Ilocos Region(Region I)',
            'vigan' => 'Ilocos Region(Region I)',
            
            // Cagayan Valley (Region II)
            'tuguegarao' => 'Cagayan Valley (Region II)',
            'ilagan' => 'Cagayan Valley (Region II)',
            
            // Central Luzon (Region III)
            'angeles' => 'Central Luzon (Region III)',
            'san fernando' => 'Central Luzon (Region III)',
            
            // Calabarzon(Region IV-A)
            'batangas' => 'Calabarzon (Region IV-A)',
            'laguna' => 'Calabarzon (Region IV-A)',
            'cavite' => 'Calabarzon (Region IV-A)',
            
            // Bicol Region (Region V)
            'legazpi' => 'Bicol Region (Region V)',
            'naga' => 'Bicol Region (Region V)',
            
            // Davao Region (Region XI)
            'davao city' => 'Davao Region (Region XI)',
            'digos' => 'Davao Region (Region XI)',
        ];
        
        // Normalize destination name (lowercase, trim)
        $normalized = strtolower(trim($destination));
        
        // Check if we have a mapping
        if (isset($regionMap[$normalized])) {
            return $regionMap[$normalized];
        }
        
        // If no mapping found, return "Other Region"
        return 'Other Region';
    }

    /**
     * API endpoint for completed trips monthly breakdown (for line chart)
     */
    public function completedTripsMonthly(Request $request)
    {
        $year = $request->get('year', date('Y'));
        
        // Get completed trips grouped by month for the entire year
        $monthlyData = TripTicket::select(
                DB::raw('MONTH(itineraries.date_from) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->join('itineraries', 'trip_tickets.itinerary_id', '=', 'itineraries.id')
            ->whereYear('itineraries.date_from', $year)
            ->where('trip_tickets.status', 'Completed')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        // Ensure all 12 months are represented
        $completeData = [];
        for ($i = 1; $i <= 12; $i++) {
            $found = false;
            foreach ($monthlyData as $item) {
                if ($item->month == $i) {
                    $completeData[] = [
                        'month' => $i,
                        'count' => $item->count
                    ];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $completeData[] = [
                    'month' => $i,
                    'count' => 0
                ];
            }
        }
        
     return response()->json([
            'data' => $completeData,
            'dateRange' => "$year - Monthly Breakdown"
        ]);
    }
    
    /**
     * API endpoint for completed trips daily breakdown within a month
     */
    public function completedTripsDaily(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get completed trips grouped by day within the selected month
        $dailyData = TripTicket::select(
                DB::raw('DAY(itineraries.date_from) as day'),
                DB::raw('COUNT(*) as count')
            )
            ->join('itineraries', 'trip_tickets.itinerary_id', '=', 'itineraries.id')
            ->whereYear('itineraries.date_from', $year)
            ->whereMonth('itineraries.date_from', $month)
            ->where('trip_tickets.status', 'Completed')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
        
        // Ensure all days in the month are represented
        $completeData = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);
        
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $found = false;
            foreach ($dailyData as $item) {
                if ($item->day == $i) {
                    $completeData[] = [
                        'day' => $i,
                        'count' => $item->count
                    ];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $completeData[] = [
                    'day' => $i,
                    'count' => 0
                ];
            }
        }
        
        $monthName = \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y');
        
        return response()->json([
            'data' => $completeData,
            'dateRange' => "$monthName - Daily Breakdown"
        ]);
    }
    
    /**
     * API endpoint for completed trips daily breakdown within a week
     */
    public function completedTripsWeekly(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $week = $request->get('week', date('W'));
        
        // Calculate start and end dates for the given ISO week
        $startDate = (new \DateTime())->setISODate($year, (int)$week)->setTime(0, 0, 0);
        $endDate = (clone $startDate)->modify('+6 days')->setTime(23, 59, 59);
        
        // Get completed trips grouped by day within the selected week
        $dailyData = TripTicket::select(
                DB::raw('DATE(itineraries.date_from) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->join('itineraries', 'trip_tickets.itinerary_id', '=', 'itineraries.id')
            ->whereBetween('itineraries.date_from', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('trip_tickets.status', 'Completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Ensure all 7 days of the week are represented
        $completeData = [];
        $currentDate = clone $startDate;
        
        for ($i = 0; $i < 7; $i++) {
            $dateStr = $currentDate->format('Y-m-d');
            $found = false;
            
            foreach ($dailyData as $item) {
                if ($item->date == $dateStr) {
                    $completeData[] = [
                        'date' => $item->date,
                        'count' => $item->count
                    ];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $completeData[] = [
                    'date' => $dateStr,
                    'count' => 0
                ];
            }
            
            $currentDate->modify('+1 day');
        }
        
        $weekStart = $startDate->format('M d');
        $weekEnd = $endDate->format('d, Y');
        
        return response()->json([
            'data' => $completeData,
            'dateRange' => "Week $week ($weekStart - $weekEnd)"
        ]);
    }
    
    /**
     * Calculate date range based on period and filter value
     */
    private function calculateDateRange($period, $filterValue)
    {
        switch ($period) {
            case 'weekly':
                // Filter value is in format YYYY-Www (e.g., 2026-W10)
                if (preg_match('/(\d{4})-W(\d{2})/', $filterValue, $matches)) {
                    $year = $matches[1];
                    $week = $matches[2];
                    $startDate = (new \DateTime())->setISODate($year, $week)->setTime(0, 0, 0)->format('Y-m-d');
                    $endDate = (new \DateTime())->setISODate($year, $week + 1)->setTime(0, 0, 0)->modify('-1 day')->format('Y-m-d');
                } else {
                    $startDate = now()->startOfWeek()->format('Y-m-d');
                    $endDate = now()->endOfWeek()->format('Y-m-d');
                }
                break;
                
            case 'yearly':
                $startDate = "$filterValue-01-01";
                $endDate = "$filterValue-12-31";
                break;
                
            case 'monthly':
            default:
                $startDate = "$filterValue-01";
                $endDate = date('Y-m-t', strtotime($startDate));
                break;
        }
        
        return [$startDate, $endDate];
    }
    
    /**
     * Format date range for display
     */
    private function formatDateRange($period, $filterValue)
    {
        [$startDate, $endDate] = $this->calculateDateRange($period, $filterValue);
        
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        
        return $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
    }
}