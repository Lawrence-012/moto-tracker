<?php

namespace App\Http\Controllers;

use App\Models\TripTicket;
use App\Models\Itinerary;
use App\Models\TravelOrder;
use App\Services\DistanceEstimationService;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ConvertApi\ConvertApi;
use Carbon\Carbon;

class TripTicketController extends Controller
{
    /**
     * Display a listing of the trip tickets.
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');
        $tab = $request->get('tab', 'pending');
        
        // Get pending trip tickets (status: Pending)
        $pendingQuery = TripTicket::with(['itinerary.driver', 'itinerary.vehicle'])
            ->where('status', 'Pending')
            ->orderBy('created_at', 'desc');
        
        if ($search) {
            $pendingQuery->where(function($q) use ($search) {
                // Search by ticket number
                $q->where('ticket_number', 'LIKE', '%' . $search . '%')
                // Search by head of party
                ->orWhere('head_of_party', 'LIKE', '%' . $search . '%')
                // Search by driver name through itinerary relationship
                ->orWhereHas('itinerary.driver', function($driverQuery) use ($search) {
                    $driverQuery->where('first_name', 'LIKE', '%' . $search . '%')
                              ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                              ->orWhere('full_name', 'LIKE', '%' . $search . '%');
                })
                // Search by vehicle through itinerary relationship
                ->orWhereHas('itinerary.vehicle', function($vehicleQuery) use ($search) {
                    $vehicleQuery->where('plate_number', 'LIKE', '%' . $search . '%')
                               ->orWhere('model', 'LIKE', '%' . $search . '%')
                               ->orWhere('type', 'LIKE', '%' . $search . '%');
                })
                // Search by destination through itinerary relationship
                ->orWhereHas('itinerary', function($itinQuery) use ($search) {
                    $itinQuery->where('destination', 'LIKE', '%' . $search . '%');
                })
                // Search by date (date_from) - supports multiple formats
                ->orWhereHas('itinerary', function($itinQuery) use ($search) {
                    // Try exact date match first (YYYY-MM-DD)
                    $itinQuery->whereDate('date_from', 'LIKE', '%' . $search . '%')
                              ->orWhereRaw('DATE_FORMAT(date_from, "%M %d, %Y") LIKE ?', ['%' . $search . '%'])
                              ->orWhereRaw('DATE_FORMAT(date_from, "%b %d, %Y") LIKE ?', ['%' . $search . '%'])
                              ->orWhereRaw('DAY(date_from) = ?', [(int)$search])
                              ->orWhereRaw('MONTH(date_from) = ?', [(int)$search])
                              ->orWhereRaw('YEAR(date_from) = ?', [(int)$search]);
                });
            });
        }
        $pendingTripTickets = $pendingQuery->paginate(10, ['*'], 'pending_page');
        
        // Get approved trip tickets (status: Approved) - for Approved tab
        $approvedQuery = TripTicket::with(['itinerary.driver', 'itinerary.vehicle'])
            ->where('status', 'Approved')
            ->orderBy('created_at', 'desc');
        
        if ($search) {
            $approvedQuery->where(function($q) use ($search) {
                // Search by ticket number
                $q->where('ticket_number', 'LIKE', '%' . $search . '%')
                // Search by head of party
                ->orWhere('head_of_party', 'LIKE', '%' . $search . '%')
                // Search by driver name through itinerary relationship
                ->orWhereHas('itinerary.driver', function($driverQuery) use ($search) {
                    $driverQuery->where('first_name', 'LIKE', '%' . $search . '%')
                              ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                              ->orWhere('full_name', 'LIKE', '%' . $search . '%');
                })
                // Search by vehicle through itinerary relationship
                ->orWhereHas('itinerary.vehicle', function($vehicleQuery) use ($search) {
                    $vehicleQuery->where('plate_number', 'LIKE', '%' . $search . '%')
                               ->orWhere('model', 'LIKE', '%' . $search . '%')
                               ->orWhere('type', 'LIKE', '%' . $search . '%');
                })
                // Search by destination through itinerary relationship
                ->orWhereHas('itinerary', function($itinQuery) use ($search) {
                    $itinQuery->where('destination', 'LIKE', '%' . $search . '%');
                })
                // Search by date (date_from) - supports multiple formats
                ->orWhereHas('itinerary', function($itinQuery) use ($search) {
                    // Try exact date match first (YYYY-MM-DD)
                    $itinQuery->whereDate('date_from', 'LIKE', '%' . $search . '%')
                              ->orWhereRaw('DATE_FORMAT(date_from, "%M %d, %Y") LIKE ?', ['%' . $search . '%'])
                              ->orWhereRaw('DATE_FORMAT(date_from, "%b %d, %Y") LIKE ?', ['%' . $search . '%'])
                              ->orWhereRaw('DAY(date_from) = ?', [(int)$search])
                              ->orWhereRaw('MONTH(date_from) = ?', [(int)$search])
                              ->orWhereRaw('YEAR(date_from) = ?', [(int)$search]);
                });
            });
        }
        $approvedTripTickets = $approvedQuery->paginate(10, ['*'], 'approved_page');
        
        // Get on-going trip tickets (status: On-Going) - for On-Going tab
        $onGoingQuery = TripTicket::with(['itinerary.driver', 'itinerary.vehicle'])
            ->where('status', 'On-Going')
            ->orderBy('created_at', 'desc');
        
        if ($search) {
            $onGoingQuery->where(function($q) use ($search) {
                // Search by ticket number
                $q->where('ticket_number', 'LIKE', '%' . $search . '%')
                // Search by head of party
                ->orWhere('head_of_party', 'LIKE', '%' . $search . '%')
                // Search by driver name through itinerary relationship
                ->orWhereHas('itinerary.driver', function($driverQuery) use ($search) {
                    $driverQuery->where('first_name', 'LIKE', '%' . $search . '%')
                              ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                              ->orWhere('full_name', 'LIKE', '%' . $search . '%');
                })
                // Search by vehicle through itinerary relationship
                ->orWhereHas('itinerary.vehicle', function($vehicleQuery) use ($search) {
                    $vehicleQuery->where('plate_number', 'LIKE', '%' . $search . '%')
                               ->orWhere('model', 'LIKE', '%' . $search . '%')
                               ->orWhere('type', 'LIKE', '%' . $search . '%');
                })
                // Search by destination through itinerary relationship
                ->orWhereHas('itinerary', function($itinQuery) use ($search) {
                    $itinQuery->where('destination', 'LIKE', '%' . $search . '%');
                })
                // Search by date (date_from) - supports multiple formats
                ->orWhereHas('itinerary', function($itinQuery) use ($search) {
                    // Try exact date match first (YYYY-MM-DD)
                    $itinQuery->whereDate('date_from', 'LIKE', '%' . $search . '%')
                              ->orWhereRaw('DATE_FORMAT(date_from, "%M %d, %Y") LIKE ?', ['%' . $search . '%'])
                              ->orWhereRaw('DATE_FORMAT(date_from, "%b %d, %Y") LIKE ?', ['%' . $search . '%'])
                              ->orWhereRaw('DAY(date_from) = ?', [(int)$search])
                              ->orWhereRaw('MONTH(date_from) = ?', [(int)$search])
                              ->orWhereRaw('YEAR(date_from) = ?', [(int)$search]);
                });
            });
        }
        $onGoingTripTickets = $onGoingQuery->paginate(10, ['*'], 'ongoing_page');
        
        // Get completed trip tickets (status: Completed)
        $completedQuery = TripTicket::with(['itinerary.driver', 'itinerary.vehicle'])
            ->where('status', 'Completed')
            ->orderBy('created_at', 'desc');
        
        if ($search) {
            $completedQuery->where(function($q) use ($search) {
                // Search by ticket number
                $q->where('ticket_number', 'LIKE', '%' . $search . '%')
                // Search by head of party
                ->orWhere('head_of_party', 'LIKE', '%' . $search . '%')
                // Search by driver name through itinerary relationship
                ->orWhereHas('itinerary.driver', function($driverQuery) use ($search) {
                    $driverQuery->where('first_name', 'LIKE', '%' . $search . '%')
                              ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                              ->orWhere('full_name', 'LIKE', '%' . $search . '%');
                })
                // Search by vehicle through itinerary relationship
                ->orWhereHas('itinerary.vehicle', function($vehicleQuery) use ($search) {
                    $vehicleQuery->where('plate_number', 'LIKE', '%' . $search . '%')
                               ->orWhere('model', 'LIKE', '%' . $search . '%')
                               ->orWhere('type', 'LIKE', '%' . $search . '%');
                })
                // Search by destination through itinerary relationship
                ->orWhereHas('itinerary', function($itinQuery) use ($search) {
                    $itinQuery->where('destination', 'LIKE', '%' . $search . '%');
                })
                // Search by date (date_from) - supports multiple formats
                ->orWhereHas('itinerary', function($itinQuery) use ($search) {
                    // Try exact date match first (YYYY-MM-DD)
                    $itinQuery->whereDate('date_from', 'LIKE', '%' . $search . '%')
                              ->orWhereRaw('DATE_FORMAT(date_from, "%M %d, %Y") LIKE ?', ['%' . $search . '%'])
                              ->orWhereRaw('DATE_FORMAT(date_from, "%b %d, %Y") LIKE ?', ['%' . $search . '%'])
                              ->orWhereRaw('DAY(date_from) = ?', [(int)$search])
                              ->orWhereRaw('MONTH(date_from) = ?', [(int)$search])
                              ->orWhereRaw('YEAR(date_from) = ?', [(int)$search]);
                });
            });
        }
        $completedTripTickets = $completedQuery->paginate(10, ['*'], 'completed_page');
        
        // Get cancelled trip tickets (status: Cancelled)
        $cancelledQuery = TripTicket::with(['itinerary.driver', 'itinerary.vehicle'])
            ->where('status', 'Cancelled')
            ->orderBy('created_at', 'desc');
        
        if ($search) {
            $cancelledQuery->where(function($q) use ($search) {
                // Search by ticket number
                $q->where('ticket_number', 'LIKE', '%' . $search . '%')
                // Search by head of party
                ->orWhere('head_of_party', 'LIKE', '%' . $search . '%')
                // Search by driver name through itinerary relationship
                ->orWhereHas('itinerary.driver', function($driverQuery) use ($search) {
                    $driverQuery->where('first_name', 'LIKE', '%' . $search . '%')
                              ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                              ->orWhere('full_name', 'LIKE', '%' . $search . '%');
                })
                // Search by vehicle through itinerary relationship
                ->orWhereHas('itinerary.vehicle', function($vehicleQuery) use ($search) {
                    $vehicleQuery->where('plate_number', 'LIKE', '%' . $search . '%')
                               ->orWhere('model', 'LIKE', '%' . $search . '%')
                               ->orWhere('type', 'LIKE', '%' . $search . '%');
                })
                // Search by destination through itinerary relationship
                ->orWhereHas('itinerary', function($itinQuery) use ($search) {
                    $itinQuery->where('destination', 'LIKE', '%' . $search . '%');
                })
                // Search by date (date_from) - supports multiple formats
                ->orWhereHas('itinerary', function($itinQuery) use ($search) {
                    // Try exact date match first (YYYY-MM-DD)
                    $itinQuery->whereDate('date_from', 'LIKE', '%' . $search . '%')
                              ->orWhereRaw('DATE_FORMAT(date_from, "%M %d, %Y") LIKE ?', ['%' . $search . '%'])
                              ->orWhereRaw('DATE_FORMAT(date_from, "%b %d, %Y") LIKE ?', ['%' . $search . '%'])
                              ->orWhereRaw('DAY(date_from) = ?', [(int)$search])
                              ->orWhereRaw('MONTH(date_from) = ?', [(int)$search])
                              ->orWhereRaw('YEAR(date_from) = ?', [(int)$search]);
                });
            });
        }
        $cancelledTripTickets = $cancelledQuery->paginate(10, ['*'], 'cancelled_page');
        
        // Get all approved itineraries that DON'T have trip tickets yet(for create modal)
        $itineraries = \App\Models\Itinerary::with(['driver', 'vehicle'])
            ->where('status', 'Approved')
            ->where('unit_head_approved', true)
            ->where('vp_approved', true)
            ->whereNotNull('unit_head_approved_at')
            ->whereNotNull('vp_approved_at')
            ->whereDoesntHave('tripTickets')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get ALL approved travel orders - include those with itineraries
        $allTravelOrders = \App\Models\TravelOrder::where('status', 'approved')
            ->with(['employee'])
            ->get();
        
        $travelOrdersWithoutTripTicket = $allTravelOrders->map(function($to) {
            return [
                'id' => $to->id,
                'employee_name' => $to->employee ? $to->employee->full_name : 'Unknown',
                'destination' => $to->destination ?? 'No destination',
                'date_from' => $to->date_from ? $to->date_from->format('Y-m-d') : null,
                'date_to' => $to->date_to ? $to->date_to->format('Y-m-d') : null,
                'formatted_date' => $to->date_from ? $to->date_from->format('M j, Y') . ($to->date_to && $to->date_from != $to->date_to ? ' - ' . $to->date_to->format('M j, Y') : '') : 'N/A',
                'departure_time' => $to->departure_time,
                'itinerary_id' => $to->itinerary_id ?? null,
                'has_itinerary' => $to->itinerary_id !== null,
            ];
        })->values()->toArray();
        
        return view('trip-tickets.index', compact('pendingTripTickets', 'approvedTripTickets', 'onGoingTripTickets', 'completedTripTickets', 'cancelledTripTickets', 'search', 'tab', 'itineraries', 'travelOrdersWithoutTripTicket'));
    }

    /**
     * Show the form for creating a new trip ticket.
     */
    public function create(): View
    {
        // Get all approved itineraries that don't have trip tickets yet
        $itineraries = \App\Models\Itinerary::with(['driver', 'vehicle'])
            ->where('status', 'Approved')
            ->where('unit_head_approved', true)
            ->where('vp_approved', true)
            ->whereNotNull('unit_head_approved_at')
            ->whereNotNull('vp_approved_at')
            ->whereDoesntHave('tripTickets')
            ->get();
        
        // Get ALL approved travel orders that DON'T have an itinerary yet
        $allTravelOrders = \App\Models\TravelOrder::where('status', 'approved')
            ->whereDoesntHave('itinerary')
            ->with(['employee'])
            ->get();
        
        return view('trip-tickets.create', compact('itineraries', 'allTravelOrders'));
    }

    /**
     * Store a newly created trip ticket in storage.
     */
    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'itinerary_id' => 'required|exists:itineraries,id',
            'ticket_number' => 'required|string|unique:trip_tickets,ticket_number',
            'travel_order_ids' => 'nullable|array',
            'travel_order_ids.*' => 'exists:travel_orders,id'
        ]);
        
        // A Trip Ticket may only be created from an itinerary that has
        // completed the required approval chain.
        $itinerary = Itinerary::findOrFail($request->itinerary_id);

        if (
            $itinerary->status !== 'Approved' ||
            !$itinerary->unit_head_approved ||
            !$itinerary->vp_approved ||
            !$itinerary->unit_head_approved_at ||
            !$itinerary->vp_approved_at
        ) {
            $message = 'This itinerary cannot be used yet. It must be approved by the Head Panel/Unit Head and the final approving authority before a Trip Ticket can be created.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->back()->with('error', $message)->withInput();
        }

        // Process passengers if provided
        $passengers = [];
        if ($request->has('passenger_names') && is_array($request->passenger_names)) {
            foreach ($request->passenger_names as $passenger) {
                // If passenger contains pipe separator, split it
                if (strpos($passenger, '|') !== false) {
                    $splitPassengers = explode('|', $passenger);
                    foreach ($splitPassengers as $splitP) {
                        if (trim($splitP) !== '') {
                            $passengers[] = trim($splitP);
                        }
                    }
                } else {
                    if (trim($passenger) !== '') {
                        $passengers[] = trim($passenger);
                    }
                }
            }
            $passengers = array_filter($passengers);
        }
        
        // Get the head of party
        $headOfParty = $request->input('head_of_party', null);
        $ticketNumber = $request->input('ticket_number');
        
        // Get travel order IDs (from form submission)
        $travelOrderIds = [];
        if ($request->has('travel_order_ids') && is_array($request->travel_order_ids)) {
            $travelOrderIds = array_filter($request->travel_order_ids);
        }
        
        // Create the trip ticket
        $tripTicket = TripTicket::create([
            'itinerary_id' => $request->itinerary_id,
            'status' => 'Pending',
            'passengers' => $passengers,
            'head_of_party' => $headOfParty,
            'ticket_number' => $ticketNumber,
        ]);
        
        // Update the itinerary's travel_order_id JSON column to match the submitted travel orders
        if (!empty($travelOrderIds)) {
            $itinerary = \App\Models\Itinerary::find($request->itinerary_id);
            if ($itinerary) {
                // Update to exactly match the submitted travel order IDs (replaces existing)
                $itinerary->travel_order_id = array_values(array_unique($travelOrderIds));
                $itinerary->save();
            }
        }
        
        // Handle AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Trip ticket created successfully!',
                'trip_ticket' => $tripTicket->load(['itinerary.driver', 'itinerary.vehicle'])
            ]);
        }
        
        return redirect()->route('trip-tickets.index')
            ->with('success', 'Trip ticket created successfully.');
    }

    /**
     * Remove a travel order from an itinerary.
     */
    public function removeTravelOrder(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'itinerary_id' => 'required|exists:itineraries,id',
            'travel_order_id' => 'required|exists:travel_orders,id'
        ]);

        try {
            $itinerary = Itinerary::findOrFail($request->itinerary_id);
            
            // Get current travel order IDs
            $travelOrderIds = $itinerary->travel_order_id ?? [];
            
            // Ensure it's an array
            if (!is_array($travelOrderIds)) {
                $travelOrderIds = [$travelOrderIds];
            }
            
            // Filter out nulls
            $travelOrderIds = array_filter($travelOrderIds);
            
            // Remove the specified travel order ID
            $travelOrderIdToRemove = (int)$request->travel_order_id;
            $travelOrderIds = array_values(array_diff($travelOrderIds, [$travelOrderIdToRemove]));
            
            // Update the itinerary with the new travel order IDs
            $itinerary->travel_order_id = $travelOrderIds;
            $itinerary->save();
            
            // Reload travel orders to get updated list
            $updatedTravelOrders = $itinerary->travelOrders()->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Travel order removed from itinerary successfully',
                'travel_orders' => $updatedTravelOrders,
                'count' => count($updatedTravelOrders)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error removing travel order from itinerary', [
                'error' => $e->getMessage(),
                'itinerary_id' => $request->itinerary_id,
                'travel_order_id' => $request->travel_order_id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove travel order from itinerary'
            ], 500);
        }
    }

    /**
     * Add a travel order to an itinerary.
     */
    public function addTravelOrderToItinerary(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'itinerary_id' => 'required|exists:itineraries,id',
            'travel_order_id' => 'required|exists:travel_orders,id'
        ]);

        try {
            $itinerary = Itinerary::findOrFail($request->itinerary_id);
            
            // Get current travel order IDs
            $travelOrderIds = $itinerary->travel_order_id ?? [];
            
            // Ensure it's an array
            if (!is_array($travelOrderIds)) {
                $travelOrderIds = [$travelOrderIds];
            }
            
            // Filter out nulls
            $travelOrderIds = array_filter($travelOrderIds);
            
            // Check if already exists
            $travelOrderIdToAdd = (int)$request->travel_order_id;
            if (in_array($travelOrderIdToAdd, $travelOrderIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Travel order already exists in itinerary'
                ], 400);
            }
            
            // Add the travel order ID
            $travelOrderIds[] = $travelOrderIdToAdd;
            
            // Update the itinerary with the new travel order IDs
            $itinerary->travel_order_id = $travelOrderIds;
            $itinerary->save();
            
            // Reload travel orders to get updated list
            $updatedTravelOrders = $itinerary->travelOrders()->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Travel order added to itinerary successfully',
                'travel_orders' => $updatedTravelOrders,
                'count' => count($updatedTravelOrders)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error adding travel order to itinerary', [
                'error' => $e->getMessage(),
                'itinerary_id' => $request->itinerary_id,
                'travel_order_id' => $request->travel_order_id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add travel order to itinerary'
            ], 500);
        }
    }

    /**
     * Display the specified trip ticket.
     */
    public function show($id): View
    {
        $tripTicket = TripTicket::with(['itinerary.driver', 'itinerary.vehicle'])->findOrFail($id);
        
        return view('trip-tickets.show', compact('tripTicket'));
    }
    
    /**
     * Get trip ticket details for the modal
     */
    public function getDetails($id): \Illuminate\Http\JsonResponse
    {
        try {
            $tripTicket = TripTicket::with([
                'itinerary.driver.location',
                'itinerary.vehicle'
            ])->findOrFail($id);
            
            // Check if the current user is authorized to view this trip ticket
            $user= auth()->user();
            
            // If user is a driver, only allow if they are assigned to this itinerary
            if ($user && $user->driver) {
                if ($tripTicket->itinerary->driver_id != $user->driver->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access to this trip ticket'
                    ], 403);
                }
            }
            
            // If user is an employee (non-admin), check authorization
            if ($user && $user->employee && !$user->isVp() && !$user->isAdmin() && !$user->isMotorpoolAdmin()) {
                $employee = $user->employee;
                $employeeFullName = $employee->full_name;
                $firstName = $employee->first_name;
                $lastName = $employee->last_name;
                            
                // Check if employee is head of party
                $isHeadOfParty= ($tripTicket->head_of_party === $employeeFullName);
                if (!$isHeadOfParty) {
                    // Try flexible matching: check if first and last name appear in head_of_party
                    if (stripos($tripTicket->head_of_party, $firstName) !== false && stripos($tripTicket->head_of_party, $lastName) !== false) {
                        $isHeadOfParty = true;
                    }
                }
                            
                // Check if employee is a passenger
                $isPassenger= false;
                if (is_array($tripTicket->passengers)) {
                    foreach ($tripTicket->passengers as $passenger) {
                        if (!is_string($passenger)) {
                            continue;
                        }

                        // Handle both direct name and pipe-separated format
                        if ($passenger === $employeeFullName) {
                            $isPassenger= true;
                            break;
                        }
                        // Also check if it's a pipe-separated string containing the employee name
                        if (is_string($passenger) && strpos($passenger, '|') !== false) {
                            $passengerParts = explode('|', $passenger);
                            foreach ($passengerParts as $part) {
                                if (trim($part) === $employeeFullName) {
                                    $isPassenger= true;
                                    break 2;
                                }
                                // Flexible matching: check if first and last name appear in the part
                                if (stripos(trim($part), $firstName) !== false && stripos(trim($part), $lastName) !== false) {
                                    $isPassenger = true;
                                    break 2;
                                }
                            }
                        }
                        // Flexible matching for non-pipe-separated names
                        if (stripos($passenger, $firstName) !== false && stripos($passenger, $lastName) !== false) {
                            $isPassenger = true;
                            break;
                        }
                    }
                } elseif (is_string($tripTicket->passengers)) {
                    if (strpos($tripTicket->passengers, $employeeFullName) !== false) {
                        // Verify it's an exact match, not just a substring
                        $passengerParts = explode('|', $tripTicket->passengers);
                        foreach ($passengerParts as $part) {
                            if (trim($part) === $employeeFullName) {
                                $isPassenger = true;
                                break;
                            }
                            // Flexible matching: check if first and last name appear in the part
                            if (stripos(trim($part), $firstName) !== false && stripos(trim($part), $lastName) !== false) {
                                $isPassenger = true;
                                break;
                            }
                        }
                    } else {
                        // Try flexible matching on the whole string
                        $passengerParts = explode('|', $tripTicket->passengers);
                        foreach ($passengerParts as $part) {
                            if (stripos(trim($part), $firstName) !== false && stripos(trim($part), $lastName) !== false) {
                                $isPassenger = true;
                                break;
                            }
                        }
                    }
                }
                
                // Check if employee is travel order creator
                $isTravelOrderCreator = false;
                if ($tripTicket->itinerary) {
                    $travelOrders = $tripTicket->itinerary->getAllTravelOrders();
                    foreach ($travelOrders as $to) {
                        if ($to && $to->employee_id === $employee->id) {
                            $isTravelOrderCreator = true;
                            break;
                        }
                    }
                }
                
                if (!$isHeadOfParty && !$isPassenger && !$isTravelOrderCreator) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access to this trip ticket'
                    ], 403);
                }
            }
            
            // VPs, admins, and motorpool admins can view all trip tickets for approval purposes
        
        // Get the trip ticket data as array
        $responseData = $tripTicket->toArray();
        
        // Ensure passengers is always an array for the frontend
        // Explicitly get passengers from the model to ensure we have the array version
        $passengers = $tripTicket->passengers;
        
        if (!isset($passengers) || $passengers === null) {
            $responseData['passengers'] = [];
            \Log::info('Passengers is null, setting to empty array');
        } elseif (is_string($passengers)) {
            \Log::info('Passengers is a STRING');
            // Check if it's a pipe-separated string
            if (strpos($passengers, '|') !== false) {
                \Log::info('String contains pipe separator, converting to array');
                // Convert pipe-separated string to array
                $passengerArray = explode('|', $passengers);
                $responseData['passengers'] = array_values(array_filter($passengerArray, function($p) {
                    return !is_null($p) && trim($p) !== '';
                }));
                \Log::info('Converted array: ' . var_export($responseData['passengers'], true));
            } else {
                // If passengers is stored as a JSON string, decode it
                $decoded = json_decode($passengers, true);
                $responseData['passengers'] = is_array($decoded) ? array_values($decoded) : [];
            }
        } elseif (is_array($passengers)) {
            // Check if the array has only one element that contains a comma
            // This handles cases where data was stored as ["Name1, Name2"] instead of ["Name1", "Name2"]
            if (count($passengers) > 0 && count($passengers) === 1 && isset($passengers[0]) && strpos($passengers[0], ',') !== false) {
                $commaSeparatedArray = explode(',', $passengers[0]);
                $responseData['passengers'] = array_values(array_filter($commaSeparatedArray, function($p) {
                    return !is_null($p) && trim($p) !== '';
                }));
            } else {
                // Filter out any null or empty values and re-index
                $responseData['passengers'] = array_values(array_filter($passengers, function($p) {
                    return !is_null($p) && trim($p) !== '';
                }));
            }
        } else {
            $responseData['passengers'] = [];
        }
        
        // Also include head_of_party explicitly
        $responseData['head_of_party'] = $tripTicket->head_of_party ?? null;
        
        // Make sure itinerary relationships are loaded
        if ($tripTicket->itinerary) {
            // Get all travel orders from the itinerary
            $allTravelOrders = $tripTicket->itinerary->getAllTravelOrders();
            $firstTravelOrder = $allTravelOrders->isNotEmpty() ? $allTravelOrders->first() : null;
            
            // Build travel orders array for the edit modal
            $travelOrdersArray = [];
            if ($allTravelOrders->isNotEmpty()) {
                foreach ($allTravelOrders as $to) {
                    $travelOrdersArray[] = [
                        'id' => $to->id,
                        'employee' => $to->employee ? [
                            'full_name' => $to->employee->full_name,
                            'first_name' => $to->employee->first_name,
                            'last_name' => $to->employee->last_name,
                            'contact_num' => $to->employee->contact_num ?? null,
                        ] : null,
                        'destination' => $to->destination ?? null,
                        'purpose' => $to->purpose ?? null,
                        'date_from' => $to->date_from ? $to->date_from->format('Y-m-d') : null,
                        'date_to' => $to->date_to ? $to->date_to->format('Y-m-d') : null,
                    ];
                }
            }
            
            // Build driver data
            $driverData = null;
            if ($tripTicket->itinerary->driver) {
                $driver = $tripTicket->itinerary->driver;
                $driverData = [
                    'id' => $driver->id,
                    'full_name' => $driver->full_name,
                    'first_name' => $driver->first_name,
                    'last_name' => $driver->last_name,
                    'contact_num' => $driver->contact_num ?? null,
                    'location' => $driver->location ? [
                        'latitude' => (float) $driver->location->latitude,
                        'longitude' => (float) $driver->location->longitude,
                        'speed_kph' => $driver->location->speed_kph !== null ? (float) $driver->location->speed_kph : null,
                        'recorded_at' => optional($driver->location->recorded_at)->toIso8601String(),
                        'is_recent' => $driver->location->is_recent,
                    ] : null,
                ];
            }
            
            // Build vehicle data
            $vehicleData = null;
            if ($tripTicket->itinerary->vehicle) {
                $vehicleData = [
                    'id' => $tripTicket->itinerary->vehicle->id,
                    'make' => $tripTicket->itinerary->vehicle->make ?? null,
                    'model' => $tripTicket->itinerary->vehicle->model ?? null,
                    'plate_number' => $tripTicket->itinerary->vehicle->plate_number,
                ];
            }
            
            // Build travel order employee data
            $employeeData = null;
            if ($firstTravelOrder && $firstTravelOrder->employee) {
                $employeeData = [
                    'id' => $firstTravelOrder->employee->id,
                    'full_name' => $firstTravelOrder->employee->full_name,
                    'first_name' => $firstTravelOrder->employee->first_name,
                    'last_name' => $firstTravelOrder->employee->last_name,
                    'email' => $firstTravelOrder->employee->email ?? null,
                    'position' => $firstTravelOrder->position ? $firstTravelOrder->position->name : null,
                    'contact_num' => $firstTravelOrder->employee->contact_num ?? null,
                ];
            }
            
            $responseData['itinerary'] = [
                'id' => $tripTicket->itinerary ? $tripTicket->itinerary->id : null, // ✅ Include itinerary ID for edit modal
                'driver' => $driverData,
                'vehicle' => $vehicleData,
                'destination' => $tripTicket->itinerary->destination ?? null,
                'destination_latitude' => $firstTravelOrder->latitude ?? null,
                'destination_longitude' => $firstTravelOrder->longitude ?? null,
                'purpose' => $tripTicket->itinerary->purpose ?? null,
                'date_from' => $tripTicket->itinerary->date_from && $tripTicket->itinerary->date_from instanceof \Carbon\Carbon ? $tripTicket->itinerary->date_from->format('Y-m-d') : null,
                'date_to' => $tripTicket->itinerary->date_to && $tripTicket->itinerary->date_to instanceof \Carbon\Carbon ? $tripTicket->itinerary->date_to->format('Y-m-d') : null,
                'departure_time' => $tripTicket->itinerary->departure_time ?? null,
                'travel_orders' => $travelOrdersArray, 
                'travel_order_employee' => $employeeData,
                // Include the travel order purpose (from the employee who created it)
                'travel_order_purpose' => $firstTravelOrder ? ($firstTravelOrder->purpose ?? null) : null,
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $responseData
        ]);
    } catch (\Exception $e) {
        $errorMessage = 'Error in getDetails for trip ticket ID ' . $id . ': ' . $e->getMessage();
        \Log::error($errorMessage);
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        \Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
        
        return response()->json([
            'success' => false,
            'message' => 'Error fetching trip ticket details: ' . $e->getMessage(),
            'debug_file' => $e->getFile(),
            'debug_line' => $e->getLine()
        ], 500);
    }
}

    /**
     * Show the form for editing the specified trip ticket.
     */
    public function edit($id): View
    {
        $tripTicket = TripTicket::with('itinerary')->findOrFail($id);
        
        // Get all approved itineraries (including the current one) for selection
        $itineraries = Itinerary::with(['driver', 'vehicle'])
            ->where('unit_head_approved', true)
            ->where('vp_approved', true)
            ->whereNotNull('unit_head_approved_at')
            ->whereNotNull('vp_approved_at')
            ->where(function($query) use ($tripTicket) {
                // Either it doesn't have a trip ticket, or it's the current trip ticket's itinerary
                $query->whereDoesntHave('tripTickets')
                      ->orWhere('itineraries.id', $tripTicket->itinerary_id);
            })
            ->select('itineraries.*')
            ->get();
        
        // Get ALL travel orders that don't have trip tickets yet
        $allTravelOrders = TravelOrder::with(['employee'])
            ->where('status', 'approved')
            ->get();
        
        // Get ALL travel orders for filtering in JavaScript (with date info and itinerary status)
        $allTravelOrders = TravelOrder::with(['employee'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get all assigned travel order IDs from itineraries that have trip tickets
        $allItinerariesList = \App\Models\Itinerary::whereHas('tripTickets')->get();
        $assignedTravelOrderIds = [];
        
        foreach ($allItinerariesList as $itinerary) {
            $travelOrderIds = $itinerary->travel_order_id ?? [];
            if (is_array($travelOrderIds)) {
                $assignedTravelOrderIds = array_merge($assignedTravelOrderIds, $travelOrderIds);
            }
        }
        $assignedTravelOrderIds = array_unique($assignedTravelOrderIds);
        
        // Format travel orders with has_itinerary flag and date info for JavaScript filtering
        $travelOrdersForEdit = $allTravelOrders->map(function($to) use ($assignedTravelOrderIds) {
            return [
                'id' => $to->id,
                'employee_name' => $to->employee ? $to->employee->full_name : 'Unknown',
                'destination' => $to->destination ?? 'No destination',
                'date_from' => $to->date_from ? $to->date_from->format('Y-m-d') : null,
                'date_to' => $to->date_to ? $to->date_to->format('Y-m-d') : null,
                'formatted_date' => $to->date_from ? $to->date_from->format('M j, Y') . ($to->date_to && $to->date_from != $to->date_to ? ' - ' . $to->date_to->format('M j, Y') : '') : 'N/A',
                'has_itinerary' => in_array($to->id, $assignedTravelOrderIds),
                'purpose' => $to->purpose ?? '',
            ];
        })->values()->toArray();
        
        return view('trip-tickets.edit', compact('tripTicket', 'itineraries', 'travelOrdersForEdit'));
    }

    /**
     * Update the specified trip ticket in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'itinerary_id' => 'required|exists:itineraries,id',
            'ticket_number' => 'required|string|unique:trip_tickets,ticket_number,' . $id,
            'travel_order_ids' => 'nullable|array',
            'travel_order_ids.*' => 'exists:travel_orders,id'
        ]);
        
        $tripTicket = TripTicket::findOrFail($id);
        $itinerary = Itinerary::find($request->itinerary_id);
        
        if (!$itinerary) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid itinerary selected.'
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'Invalid itinerary selected.');
        }

        // Editing an approved/ongoing/completed ticket would bypass the approval
        // chain. Trip Ticket edits are therefore allowed only while Pending.
        if ($tripTicket->status !== 'Pending') {
            $message = 'Only Pending trip tickets can be edited. Approved or active trip tickets must follow the existing trip flow.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        // The selected itinerary must still be fully approved.
        if (
            $itinerary->status !== 'Approved' ||
            !$itinerary->unit_head_approved ||
            !$itinerary->vp_approved ||
            !$itinerary->unit_head_approved_at ||
            !$itinerary->vp_approved_at
        ) {
            $message = 'The selected itinerary is not fully approved. It must be approved by the Head Panel/Unit Head and the final approving authority first.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }
        
        // Validate that all travel orders match the itinerary date range
        $travelOrderIds = [];
        if ($request->has('travel_order_ids') && is_array($request->travel_order_ids)) {
            $travelOrderIds = array_filter($request->travel_order_ids);
            
            if (!empty($travelOrderIds)) {
                $itineraryStart = \Carbon\Carbon::parse($itinerary->date_from);
                $itineraryEnd = \Carbon\Carbon::parse($itinerary->date_to);
                
                foreach ($travelOrderIds as $toId) {
                    $travelOrder = TravelOrder::find($toId);
                    if ($travelOrder) {
                        $toStart = \Carbon\Carbon::parse($travelOrder->date_from);
                        $toEnd = \Carbon\Carbon::parse($travelOrder->date_to);
                        
                        // Check if travel order dates overlap with itinerary dates
                        $isWithinRange = ($toStart <= $itineraryEnd && $toEnd >= $itineraryStart);
                        
                        if (!$isWithinRange) {
                            if ($request->expectsJson()) {
                                return response()->json([
                                    'success' => false,
                                    'message' => "Travel order #{$toId} dates do not match the itinerary date range."
                                ], 422);
                            }
                            return redirect()->back()
                                ->with('error', "Travel order #{$toId} dates do not match the itinerary date range.");
                        }
                    }
                }
            }
        }
        
        // Process passengers if provided
        $passengers = [];
        if ($request->has('passenger_names') && is_array($request->passenger_names)) {
            foreach ($request->passenger_names as $passenger) {
                // If passenger contains pipe separator, split it
                if (strpos($passenger, '|') !== false) {
                    $splitPassengers = explode('|', $passenger);
                    foreach ($splitPassengers as $splitP) {
                        if (trim($splitP) !== '') {
                            $passengers[] = trim($splitP);
                        }
                    }
                } else {
                    if (trim($passenger) !== '') {
                        $passengers[] = trim($passenger);
                    }
                }
            }
            $passengers = array_filter($passengers);
        }
        
        // Get the head of party
        $headOfParty = $request->input('head_of_party', null);
        
        $tripTicket->update([
            'itinerary_id' => $request->itinerary_id,
            'ticket_number' => $request->ticket_number,
            'passengers' => $passengers,
            'head_of_party' => $headOfParty,
        ]);
        
        // Update the itinerary's travel_order_id JSON column to match the submitted travel orders
        if (!empty($travelOrderIds)) {
            // Update to exactly match the submitted travel order IDs
            $itinerary->travel_order_id = array_values(array_unique($travelOrderIds));
            $itinerary->save();
            
            \Log::info('Updated itinerary travel_order_id on trip ticket update:', [
                'itinerary_id' => $itinerary->id,
                'travel_order_ids' => $itinerary->travel_order_id
            ]);
        } else {
            // If no travel orders submitted, clear the itinerary's travel_order_id
            $itinerary->travel_order_id = null;
            $itinerary->save();
            
            \Log::info('Cleared itinerary travel_order_id on trip ticket update:', [
                'itinerary_id' => $itinerary->id
            ]);
        }
        
        // Send SMS notification to driver about trip ticket update
        $this->sendUpdateNotificationToDriver($tripTicket);
        
        // Return JSON response for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Trip ticket updated successfully.',
                'data' => $tripTicket
            ]);
        }
        
        // Redirect back to the previous page with success message
        return redirect()->back()
            ->with('success', 'Trip ticket updated successfully.');
    }

    /**
     * Get passengers for a specific travel order
     */
    public function getPassengersForTravelOrder($id): \Illuminate\Http\JsonResponse
    {
        $travelOrder = TravelOrder::with(['employee'])->find($id);
        
        if (!$travelOrder) {
            return response()->json(['error' => 'Travel order not found'], 404);
        }
        
        $passengers = [];
        
        // Add the main employee who created the travel order
        if ($travelOrder->employee) {
            $passengers[] = [
                'name' => $travelOrder->employee->first_name . ' ' . $travelOrder->employee->last_name,
                'role' => 'Employee',
                'type' => 'employee'
            ];
        }
        
        return response()->json([
            'employee' => $travelOrder->employee ? [
                'first_name' => $travelOrder->employee->first_name,
                'last_name' => $travelOrder->employee->last_name
            ] : null,
            'passengers' => $passengers
        ]);
    }
    
    /**
     * Update the status of the specified trip ticket.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Pending,Approved,On-Going,Completed,Cancelled,Archived',
            'odometer_start_km' => 'nullable|integer|min:0',
            'odometer_end_km' => 'nullable|integer|min:0',
        ]);

        $tripTicket = TripTicket::with('itinerary')->findOrFail($id);

        $newStatus = $request->input('status');
        $currentStatus = $tripTicket->status;
        $itinerary = $tripTicket->itinerary;

        // Enforce the workflow from the capstone: an itinerary must be fully
        // approved before the trip ticket can be approved or started, and a
        // trip can only progress Pending -> Approved -> On-Going -> Completed.
        if (in_array($newStatus, ['Approved', 'On-Going', 'Completed'], true)) {
            if (!$itinerary ||
                $itinerary->status !== 'Approved' ||
                !$itinerary->unit_head_approved ||
                !$itinerary->vp_approved ||
                !$itinerary->unit_head_approved_at ||
                !$itinerary->vp_approved_at) {
                return redirect()->back()->with('error', 'The itinerary must be fully approved by the Head Panel/Unit Head and final approving authority before this Trip Ticket can proceed.');
            }
        }

        $allowedTransitions = [
            'Pending' => ['Approved', 'Cancelled', 'Archived', 'Pending'],
            'Approved' => ['On-Going', 'Cancelled', 'Archived', 'Approved'],
            'On-Going' => ['Completed', 'Cancelled', 'On-Going'],
            'Completed' => ['Completed', 'Archived'],
            'Cancelled' => ['Cancelled', 'Archived'],
            'Archived' => ['Archived'],
        ];

        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
            return redirect()->back()->with('error', "Invalid Trip Ticket status transition from {$currentStatus} to {$newStatus}.");
        }

        if ($request->filled('odometer_start_km')) {
            $tripTicket->setAttribute('odometer_start_km', (int) $request->input('odometer_start_km'));
        }

        if ($request->filled('odometer_end_km')) {
            $tripTicket->setAttribute('odometer_end_km', (int) $request->input('odometer_end_km'));
        }
        
        // Store the old status to check if it changed to 'Completed'
        $oldStatus = $tripTicket->status;
        
        $tripTicket->update([
            'status' => $request->status,
        ]);
        
        // Send SMS notification to driver about status change (except for Archived status)
        if ($request->status !== 'Archived') {
            $this->sendStatusChangeNotificationToDriver($tripTicket, $oldStatus, $request->status);
        }
        
        // If status changed to 'Completed', create vehicle travel history
        // and propagate completion to itinerary and travel orders
        // Note: Vehicle mileage update is handled by TripTicketObserver
        if($oldStatus !== 'Completed' && $request->status === 'Completed') {
            $this->createVehicleTravelHistory($tripTicket, $request);
            $this->propagateCompletionToRelatedRecords($tripTicket);
        }
        
        $statusMessages = [
            'Approved' => 'Trip started successfully.',
            'Completed' => 'Trip completed successfully.',
            'Cancelled' => 'Trip cancelled successfully.',
            'Archived' => 'Trip archived successfully.',
            'On-Going' => 'Trip status updated successfully.',
            'Pending' => 'Trip status updated successfully.'
        ];
        
        $message = $statusMessages[$request->status] ?? 'Trip status updated successfully.';
        
        // Redirect to Completed tab if status is Completed, otherwise redirect back
        if($request->status === 'Completed') {
            return redirect()->route('trip-tickets.index', ['tab' => 'completed'])
                ->with('success', $message);
        }
        
        // Redirect back to preserve the tab for other status changes
        return redirect()->back()
            ->with('success', $message);
    }
    
    /**
     * Cancel a trip ticket with reason.
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);

        $tripTicket = TripTicket::findOrFail($id);
        
        // Store the old status
        $oldStatus = $tripTicket->status;
        
        // Update status and cancellation reason
        $tripTicket->update([
            'status' => 'Cancelled',
            'cancellation_reason' => $request->cancellation_reason,
        ]);
        
        // Send SMS notification to driver about cancellation
        $this->sendStatusChangeNotificationToDriver($tripTicket, $oldStatus, 'Cancelled');
        
        // Send SMS notifications to all passengers
        $this->sendCancellationNotificationToPassengers($tripTicket, $request->cancellation_reason);
        
        return redirect()->back()
            ->with('success', 'Trip ticket cancelled successfully.');
    }
    
    /**
     * Update vehicle mileage when trip is completed
     */
    private function updateVehicleMileage(TripTicket $tripTicket): void
    {
        // Load the itinerary with related models
        $tripTicket->load(['itinerary.vehicle']);
        
        // Check if itinerary and vehicle exist
        if (!$tripTicket->itinerary || !$tripTicket->itinerary->vehicle) {
            return;
        }
        
        // Get the estimated round-trip distance
        $distance = $this->estimateDistanceFromCLSU($tripTicket->itinerary->destination ?? '');
        
        // If we have a distance, update the vehicle's mileage
        if ($distance !== null) {
            $vehicle = $tripTicket->itinerary->vehicle;
            $vehicle->mileage += $distance;
            $vehicle->save();
        }
    }
    
    /**
     * Create vehicle travel history record from completed trip ticket
     */
    private function createVehicleTravelHistory(TripTicket $tripTicket, ?Request $request = null): void
    {
        // Load the itinerary with related models
        $tripTicket->load(['itinerary.vehicle', 'itinerary.driver']);
        
        // Check if itinerary exists
        if (!$tripTicket->itinerary) {
            return;
        }
        
        // Check if a travel history record already exists for this trip ticket
        $existingRecord = \App\Models\VehicleTravelHistory::where('trip_ticket_id', $tripTicket->id)->first();
        if ($existingRecord) {
            return; // Prevent duplicate records
        }
        
        $odometerStart = $request?->filled('odometer_start_km')
            ? (int) $request->input('odometer_start_km')
            : $tripTicket->getAttribute('odometer_start_km');
        $odometerEnd = $request?->filled('odometer_end_km')
            ? (int) $request->input('odometer_end_km')
            : $tripTicket->getAttribute('odometer_end_km');
        $distance = null;

        if ($odometerStart !== null && $odometerEnd !== null) {
            $distance = max(0, (int) $odometerEnd - (int) $odometerStart);
        }

        if ($distance === null) {
            $distance = $this->estimateDistanceFromCLSU($tripTicket->itinerary->destination ?? '');
        }

        // Prepare data for vehicle travel history
        $data = [
            'trip_ticket_id' => $tripTicket->id,
            'vehicle_id' => $tripTicket->itinerary->vehicle_id,
            'driver_id' => $tripTicket->itinerary->driver_id,
            'head_of_party' => $tripTicket->head_of_party,
            'destination' => $tripTicket->itinerary->destination ?? 'N/A',
            'departure_date' => $tripTicket->itinerary->date_from ?? now(),
            'departure_time' => $tripTicket->itinerary->departure_time,
            'arrival_date' => $tripTicket->itinerary->date_to,
            'arrival_time' => $tripTicket->itinerary->departure_time, // Using departure time as default
            'odometer_start_km' => $odometerStart,
            'odometer_end_km' => $odometerEnd,
            'distance_km' => $distance,
            'remarks' => 'Auto-generated from completed trip ticket',
        ];
        
        // Create the vehicle travel history record
        \App\Models\VehicleTravelHistory::create($data);
    }
    
    /**
     * Estimate round-trip distance from CLSU to destination using Google Maps API
     */
    private function estimateDistanceFromCLSU(string $destination): float|null
    {
        if (empty($destination)) {
            return null;
        }
        
        // Use OSRM/OpenStreetMap for accurate distance calculation
        $distanceService = new DistanceEstimationService();
        
        // CLSU is located in Science City of Muñoz, Nueva Ecija, Philippines
        $origin = 'Central Luzon State University, Science City of Muñoz, Nueva Ecija, Philippines';
        
        // Get round-trip distance (OSRM service handles the calculation)
        $roundTripDistance = $distanceService->getRoundTripDistance($origin, $destination);
        
        return $roundTripDistance;
    }
    
    /**
     * Remove the specified trip ticket from storage.
     */
    public function destroy($id): RedirectResponse
    {
        $tripTicket = TripTicket::findOrFail($id);
        $tripTicket->delete();
        
        return redirect()->route('trip-tickets.index')
            ->with('success', 'Trip ticket deleted successfully.');
    }
    
    /**
     * Generate Excel for trip ticket preview (no PDF conversion)
     */
    public function generateExcel($id): Response
    {
        // Load TripTicket with relations
        $ticket = TripTicket::with(['itinerary.driver', 'itinerary.vehicle'])->findOrFail($id);
        
        // Path to Excel template
        $templatePath = public_path('templates/trip_ticket_template.xlsx');
        if (!file_exists($templatePath)) {
            abort(500, 'Trip Ticket template not found.');
        }
        
        // Load Excel template
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Get ticket data
        $driverName = $ticket->itinerary && $ticket->itinerary->driver ? 
                     $ticket->itinerary->driver->first_name . ' ' . $ticket->itinerary->driver->last_name : 'N/A';
        $vehicleModel = $ticket->itinerary && $ticket->itinerary->vehicle ? $ticket->itinerary->vehicle->model : '';
        $vehiclePlate = $ticket->itinerary && $ticket->itinerary->vehicle ? $ticket->itinerary->vehicle->plate_number : '';
        $departureTime = optional($ticket->itinerary)->departure_time ? Carbon::parse($ticket->itinerary->departure_time)->format('h:i A') : '';
        $dateFrom = optional($ticket->itinerary)->date_from ? Carbon::parse($ticket->itinerary->date_from)->format('F d, Y') : '';
        $dateTo = optional($ticket->itinerary)->date_to ? Carbon::parse($ticket->itinerary->date_to)->format('F d, Y') : '';
        $dateDisplay = ($dateFrom && $dateTo && $dateFrom === $dateTo) ? "only" : $dateTo;
        $officerName = 'N/A'; // Officer information not directly available in current model structure
        $officerPosition = 'N/A'; // Officer information not directly available in current model structure
        
        $headPassenger = $ticket->head_of_party ?? 'N/A';
        
        // Get from itinerary
        $destinationsText = optional($ticket->itinerary)->destination ?? 'N/A';
        $purposesText = optional($ticket->itinerary)->purpose ?? 'N/A';
        
        // Find the VP of the Office of the Vice President for Administration for G27 and G28
        // Query the officers table directly for VP status
        $vp = \App\Models\Employee::whereHas('officer', function($query) {
            $query->where('vp', true);
        })->whereHas('positions', function($positionQuery) {
            $positionQuery->whereHas('office', function($officeQuery) {
                $officeQuery->where('office_name', 'Office of the Vice President for Administration');
            });
        })->first();
        
        // If not found by office, check if the employee has VP status with matching position name
        if (!$vp) {
            $vp = \App\Models\Employee::whereHas('positions', function($positionQuery) {
                $positionQuery->where('position_name', 'LIKE', '%Vice President for Administration%');
            })->whereHas('officer', function($officerQuery) {
                $officerQuery->where('vp', true);
            })->first();
        }
        
        // If still not found, try a general VP search from officers table
        if (!$vp) {
            $vp = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('vp', true);
            })->first();
        }
        
        if ($vp) {
            $officerName = $vp->first_name . ' ' . $vp->last_name;
            $officerPosition = $vp->position ? $vp->position->position_name : 'Vice President for Administration';
        } else {
            $officerName = 'Office of the Vice President for Administration';
            $officerPosition = 'Vice President for Administration';
        }
        
        // Fill Excel cells
        $sheet->setCellValue('B9', $departureTime);
        $sheet->setCellValue('B10', $dateFrom);
        $sheet->setCellValue('B11', $dateDisplay);
        $sheet->setCellValue('H9', $ticket->ticket_number ?? '');
        $sheet->setCellValue('A14', $headPassenger); // Head of Party in dedicated cell
        
        // Add passengers to cells D10-D17 (EXCLUDING head of party)
        // Head of party already has dedicated cell A14
        $passengers = $ticket->passengers ?? [];
        $headOfParty = $ticket->head_of_party ?? '';
        
        \Log::info('PDF Generation - Passenger Data', [
            'trip_ticket_id' => $ticket->id,
            'head_of_party' => $headOfParty,
            'passengers_raw' => $passengers,
            'passengers_count' => count($passengers),
            'passengers_json' => json_encode($passengers)
        ]);
        
        // Clean passengers: split any pipe-separated values and trim whitespace
        $cleanPassengers = [];
        foreach ($passengers as $passenger) {
            if (strpos($passenger, '|') !== false) {
                // Split pipe-separated passengers
                $splitNames = explode('|', $passenger);
                foreach ($splitNames as $name) {
                    if (trim($name) !== '') {
                        $cleanPassengers[] = trim($name);
                    }
                }
            } else {
                if (trim($passenger) !== '') {
                    $cleanPassengers[] = trim($passenger);
                }
            }
        }
        
        \Log::info('PDF Generation - After Cleaning', [
            'clean_passengers' => $cleanPassengers,
            'clean_count' => count($cleanPassengers)
        ]);
        
        // Filter out the head of party from the list (already in A14)
        $regularPassengers = array_filter($cleanPassengers, function($passenger) use ($headOfParty) {
            return trim($passenger) !== trim($headOfParty);
        });
        
        // Re-index array to ensure sequential keys
        $regularPassengers = array_values($regularPassengers);
        
        \Log::info('PDF Generation - After Filtering Head', [
            'regular_passengers' => $regularPassengers,
            'regular_count' => count($regularPassengers),
            'total_display' => 'Head of Party (' . $headOfParty . ') + ' . count($regularPassengers) . ' regular passengers'
        ]);
        
        // Assign passengers to cells D10 through D17 (max 8 cells)
        $passengerCells = ['D10', 'D11', 'D12', 'D13', 'D14', 'D15', 'D16', 'D17'];
        $totalPassengers = count($regularPassengers);
        
        foreach ($passengerCells as $index => $cell) {
            if ($index < $totalPassengers) {
                // If this is the last cell (D17) and there are more passengers
                if ($index === 7 && $totalPassengers > 8) {
                    $remainingCount = $totalPassengers - 8;
                    $sheet->setCellValue($cell, $regularPassengers[$index] . " (+{$remainingCount} others)");
                } else {
                    $sheet->setCellValue($cell, $regularPassengers[$index]);
                }
            } else {
                $sheet->setCellValue($cell, ''); // Clear the cell if no passenger
            }
        }
        
        $sheet->setCellValue('B19', $destinationsText);
        $sheet->setCellValue('B21', $purposesText);
        $sheet->setCellValue('C21', $vehicleModel);
        $sheet->setCellValue('C22', $vehiclePlate);
        $sheet->setCellValue('C23', $driverName);
        $sheet->setCellValue('C25', $vehicleModel);
        $sheet->setCellValue('C26', $vehiclePlate);
        $sheet->setCellValue('C27', $driverName);
        $sheet->setCellValue('G53', $driverName);
        $sheet->setCellValue('G62', $headPassenger);
        $sheet->setCellValue('G27', $officerName);
        $sheet->setCellValue('G28', $officerPosition);
        
        // Add approval/cancellation stamp in G25
        if ($ticket->status === 'Cancelled' && $ticket->updated_at) {
            // Convert to Philippine Time (Asia/Manila)
            $cancelledDate = Carbon::parse($ticket->updated_at)->timezone('Asia/Manila')->format('M j, Y g:i A');
            $sheet->setCellValue('G25', "CANCELLED {$cancelledDate}");
            
            // Add cancellation reason if available
            if ($ticket->cancellation_reason) {
                $sheet->setCellValue('G26', 'Reason: ' . substr($ticket->cancellation_reason, 0, 50));
                if (strlen($ticket->cancellation_reason) > 50) {
                    $sheet->setCellValue('G27', substr($ticket->cancellation_reason, 50, 50));
                }
            }
        } elseif (in_array($ticket->status, ['Approved', 'On-Going', 'Completed']) && $ticket->itinerary && $ticket->itinerary->vp_approved_at) {
            // Convert VP approval timestamp to Philippine Time (Asia/Manila)
            $approvedDate = Carbon::parse($ticket->itinerary->vp_approved_at)->timezone('Asia/Manila')->format('M j, Y g:i A');
            $sheet->setCellValue('G25', "APPROVED {$approvedDate}");
        }
        
        // Save Excel temporarily and return it
        $excelTemp = tempnam(sys_get_temp_dir(), 'trip_ticket_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($excelTemp);
        
        // Return Excel response for preview
        return response(file_get_contents($excelTemp))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="Trip_Ticket_' . $ticket->ticket_number . '.xlsx"')
            ->header('Cache-Control', 'max-age=0')
            ->header('Pragma', 'public')
            ->header('Expires', '0');
    }
    
    /**
     * Upload completion file for a trip ticket.
     */
    public function uploadCompletionFile(Request $request, $id)
    {
        $request->validate([
            'completion_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // Max 5MB
        ]);
        
        $tripTicket = TripTicket::findOrFail($id);
        
        if ($request->hasFile('completion_file')) {
            try {
                $file = $request->file('completion_file');
                
                // Generate filename using only the ticket number
                $ticketNumber = $tripTicket->ticket_number ?? 'TICKET-' . $tripTicket->id;
                $extension = $file->getClientOriginalExtension();
                $filename = "{$ticketNumber}.{$extension}";
                
                // Initialize Google Drive Service
                $googleDriveService = new GoogleDriveService();
                
                // Upload to Google Drive with custom filename
                $uploadedFile = $googleDriveService->uploadFile($file, $filename);
                
                if ($uploadedFile) {
                    // Delete old file from Google Drive if exists
                    if ($tripTicket->completion_file) {
                        $googleDriveService->deleteFile($tripTicket->completion_file);
                    }
                    
                    // Store only the Google Drive file ID
                    $tripTicket->completion_file = $uploadedFile['id'];
                    $tripTicket->save();
                    
                    Log::info("Completion file uploaded to Google Drive for Trip Ticket #{$id}", [
                        'file_id' => $uploadedFile['id'],
                        'file_name' => $uploadedFile['name'],
                        'file_link' => $uploadedFile['webViewLink']
                    ]);
                    
                    return redirect()->back()
                        ->with('success', 'Completion file uploaded successfully to Google Drive.');
                } else {
                    return redirect()->back()
                        ->with('error', 'Failed to upload file to Google Drive. Please try again.');
                }
            } catch (\Exception $e) {
                Log::error("Error uploading completion file to Google Drive: " . $e->getMessage());
                return redirect()->back()
                    ->with('error', 'Error uploading file: ' . $e->getMessage());
            }
        }
        
        return redirect()->back()
            ->with('error', 'No file uploaded.');
    }
    
    /**
     * Start a trip ticket (change status from Approved to On-Going).
     */
    public function start($id)
    {
        try {
            $tripTicket = TripTicket::with('itinerary')->findOrFail($id);
            
            // Verify the trip ticket is approved
            if($tripTicket->status !== 'Approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved trip tickets can be started. Current status: ' . $tripTicket->status
                ], 422);
            }

            $itinerary = $tripTicket->itinerary;
            if (!$itinerary ||
                $itinerary->status !== 'Approved' ||
                !$itinerary->unit_head_approved ||
                !$itinerary->vp_approved ||
                !$itinerary->unit_head_approved_at ||
                !$itinerary->vp_approved_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'This trip cannot start because its itinerary has not completed the required Head Panel/Unit Head and final approval.'
                ], 422);
            }
            
            // Update status to On-Going
            $tripTicket->status = 'On-Going';
            $tripTicket->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Trip started successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Propagate completion status to related itinerary and travel orders.
     * When a trip ticket is completed, also mark its itinerary and travel orders as completed.
     */
    private function propagateCompletionToRelatedRecords(TripTicket $tripTicket): void
    {
        // Load the itinerary with vehicle and driver
        $tripTicket->load(['itinerary.vehicle', 'itinerary.driver']);
        
        // Check if itinerary exists
        if (!$tripTicket->itinerary) {
            return;
        }
        
        // Update itinerary status to Completed
        $tripTicket->itinerary->status = 'Completed';
        $tripTicket->itinerary->save();
        
        // Get all travel orders associated with this itinerary
        $travelOrderIds = $tripTicket->itinerary->travel_order_id ?? [];
        
        // Handle case where it's a single ID (not array yet)
        if (!is_array($travelOrderIds)) {
            $travelOrderIds = [$travelOrderIds];
        }
        
        // Filter out nulls
        $travelOrderIds = array_filter($travelOrderIds);
        
        // Update all associated travel orders to Completed
        if (!empty($travelOrderIds)) {
            TravelOrder::whereIn('id', $travelOrderIds)->update(['status' => 'Completed']);
        }
    }
        
    /**
     * Send SMS notification to driver when trip ticket is updated.
     */
    private function sendUpdateNotificationToDriver(TripTicket $tripTicket)
    {
        \Log::info('Starting SMS update notification process', ['trip_ticket_id' => $tripTicket->id]);
            
        try {
            // Get itinerary and driver information
            $itinerary = $tripTicket->itinerary;
            if (!$itinerary || !$itinerary->driver) {
                \Log::warning('Trip ticket update: No itinerary or driver found', [
                    'trip_ticket_id' => $tripTicket->id,
                    'itinerary_exists' => !!$itinerary,
                    'driver_exists' => $itinerary ? !!$itinerary->driver : false
                ]);
                return;
            }
                
            $driver = $itinerary->driver;
            \Log::info('Driver found for update notification', [
                'driver_id' => $driver->id,
                'driver_name' => $driver->full_name,
                'contact_num' => $driver->contact_num
            ]);
                
            // Check if driver has a contact number
            if (empty($driver->contact_num)) {
                \Log::warning('Trip ticket update: Driver has no contact number', [
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->full_name
                ]);
                return;
            }
                
            // Format date range
            $dateFrom = \Carbon\Carbon::parse($itinerary->date_from)->format('M j, Y');
            $dateTo = \Carbon\Carbon::parse($itinerary->date_to)->format('M j, Y');
            $dateRange = ($dateFrom === $dateTo) ? $dateFrom : "{$dateFrom} - {$dateTo}";
                
            // Format departure time
            $departureTime = $itinerary->departure_time;
            if ($departureTime) {
                $timeObj = \Carbon\Carbon::createFromFormat('H:i:s', $departureTime);
                $formattedTime = $timeObj ? $timeObj->format('g:i A') : $departureTime;
            } else {
                $formattedTime = 'N/A';
            }
                
            // Get vehicle information
            $vehicle = $itinerary->vehicle;
            $vehicleInfo = $vehicle ? "{$vehicle->make} {$vehicle->model}" : 'N/A';
                
            // Get head of party and passengers
            $headOfParty = $tripTicket->head_of_party ?? 'N/A';
            $passengers = $tripTicket->passengers ?? [];
            $passengerCount = count($passengers);
                
            // Get head of party contact number (from travel order employee)
            $headContactNum = 'N/A';
            $travelOrders = $itinerary->getAllTravelOrders();
            if ($travelOrders && $travelOrders->count() > 0) {
                $firstTO = $travelOrders->first();
                if ($firstTO && $firstTO->employee && $firstTO->employee->contact_num) {
                    $headContactNum = $firstTO->employee->contact_num;
                }
            }
                
            // Build multi-line SMS message for UPDATE notification (no truncation to preserve formatting)
            $message = "Trip Updated.\n\n" .
                       "Driver: {$driver->full_name}\n" .
                       "Vehicle: {$vehicleInfo}\n" .
                       "To: {$itinerary->destination}\n" .
                       "Date: {$dateRange}\n" .
                       "Time: {$formattedTime}\n" .
                       "Head of the party: {$headOfParty}\n" .
                       "Head contact no.: {$headContactNum}\n" .
                       "Passengers: {$passengerCount}";
                
            \Log::info('Sending update SMS to driver', [
                'recipient' => $driver->contact_num,
                'message_length' => strlen($message),
                'message_preview' => substr($message, 0, 50)
            ]);
                
            // Send via PhilSMS API v3
            $this->sendPhilSms($driver->contact_num, $message);
                
            \Log::info('Update notification SMS sent successfully', [
                'driver_id' => $driver->id,
                'driver_contact' => $driver->contact_num,
                'trip_ticket_id' => $tripTicket->id
            ]);
                
        } catch (\Exception $e) {
            \Log::error('Failed to send update SMS notification', [
                'trip_ticket_id' => $tripTicket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw exception - SMS failure should not affect update
        }
    }
    
    /**
     * Send SMS notification to driver when trip ticket status changes (Approved, On-Going, Completed, etc.)
     */
    private function sendStatusChangeNotificationToDriver(TripTicket $tripTicket, $oldStatus, $newStatus)
    {
        \Log::info('Starting SMS status change notification process', [
            'trip_ticket_id' => $tripTicket->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);
        
        try {
            // Get itinerary and driver information
            $itinerary = $tripTicket->itinerary;
            if (!$itinerary || !$itinerary->driver) {
                \Log::warning('Trip ticket status change: No itinerary or driver found', [
                    'trip_ticket_id' => $tripTicket->id,
                    'itinerary_exists' => !!$itinerary,
                    'driver_exists' => $itinerary ? !!$itinerary->driver : false
                ]);
                return;
            }
            
            $driver = $itinerary->driver;
            \Log::info('Driver found for status change notification', [
                'driver_id' => $driver->id,
                'driver_name' => $driver->full_name,
                'contact_num' => $driver->contact_num
            ]);
            
            // Check if driver has a contact number
            if (empty($driver->contact_num)) {
                \Log::warning('Trip ticket status change: Driver has no contact number', [
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->full_name
                ]);
                return;
            }
            
            // Build status-specific messages
            $statusMessages = [
                'Approved' => "HELLO {$driver->full_name}, YOUR TRIP HAS BEEN APPROVED AND IS READY TO START. DEPARTURE: ",
                'On-Going' => "HELLO {$driver->full_name}, YOUR TRIP STATUS IS NOW ON-GOING. SAFE TRIPS!",
                'Completed' => "HELLO {$driver->full_name}, YOUR TRIP HAS BEEN MARKED AS COMPLETED. THANK YOU!",
                'Cancelled' => "HELLO {$driver->full_name}, YOUR TRIP HAS BEEN CANCELLED.",
                'Archived' => "HELLO {$driver->full_name}, YOUR TRIP HAS BEEN ARCHIVED.",
                'Pending' => "HELLO {$driver->full_name}, YOUR TRIP STATUS IS BACK TO PENDING."
            ];
            
            $baseMessage = $statusMessages[$newStatus] ?? "HELLO {$driver->full_name}, YOUR TRIP STATUS HAS CHANGED TO {$newStatus}.";
            
            // For Approved and Cancelled status, add more details with multi-line format
            if ($newStatus === 'Approved') {
                // Format departure time
                $departureTime = $itinerary->departure_time;
                if ($departureTime) {
                    $timeObj = \Carbon\Carbon::createFromFormat('H:i:s', $departureTime);
                    $formattedTime = $timeObj ? $timeObj->format('g:i A') : $departureTime;
                } else {
                    $formattedTime = 'N/A';
                }
                
                // Format date range
                $dateFrom = \Carbon\Carbon::parse($itinerary->date_from)->format('M j, Y');
                $dateTo = \Carbon\Carbon::parse($itinerary->date_to)->format('M j, Y');
                $dateRange = ($dateFrom === $dateTo) ? $dateFrom : "{$dateFrom} - {$dateTo}";
                
                $vehicleModel = $itinerary->vehicle ? $itinerary->vehicle->model : 'N/A';
                $headOfParty = $tripTicket->head_of_party ?? 'N/A';
                
                // Get head of party contact number (from travel order employee or user)
                $headContactNum = 'N/A';
                $travelOrders = $itinerary->getAllTravelOrders();
                if ($travelOrders && $travelOrders->count() > 0) {
                    $firstTO = $travelOrders->first();
                    if ($firstTO && $firstTO->employee) {
                        // Try to get contact from employee first
                        $headContactNum = $firstTO->employee->contact_num ?? 'N/A';
                        // If employee has no contact, try from user
                        if ($headContactNum === 'N/A' && $firstTO->employee->user && $firstTO->employee->user->contact_num) {
                            $headContactNum = $firstTO->employee->user->contact_num;
                        }
                    }
                }
                
                // Count total passengers (including head of party)
                $passengers = $tripTicket->passengers ?? [];
                $passengerCount = count($passengers);
                
                // Build multi-line SMS message for APPROVED status (no truncation to preserve formatting)
                $baseMessage = "Trip Approved.\n\n" .
                               "Driver: {$driver->full_name}\n" .
                               "Vehicle: {$vehicleModel}\n" .
                               "To: {$itinerary->destination}\n" .
                               "Date: {$dateRange}\n" .
                               "Time: {$formattedTime}\n" .
                               "Head of the party: {$headOfParty}\n" .
                               "Head contact no.: {$headContactNum}\n" .
                               "Passengers: {$passengerCount}";
            }
            
            // For Cancelled status, add multi-line details
            if ($newStatus === 'Cancelled') {
                // Format date
                $dateFrom = \Carbon\Carbon::parse($itinerary->date_from)->format('M j, Y');
                
                // Get cancellation reason
                $cancellationReason = $tripTicket->cancellation_reason ?? 'Not specified';
                
                // Build simplified SMS message for CANCELLED status
                $baseMessage = "Trip Cancelled.\n\n" .
                               "To: {$itinerary->destination}\n" .
                               "Date: {$dateFrom}\n" .
                               "Reason: {$cancellationReason}";
            }
            
            // No truncation to preserve multi-line formatting
            \Log::info('Sending status change SMS to driver', [
                'recipient' => $driver->contact_num,
                'message_length' => strlen($baseMessage),
                'message_preview' => substr($baseMessage, 0, 50)
            ]);
            
            // Send via PhilSMS API v3
            $this->sendPhilSms($driver->contact_num, $baseMessage);
            
            \Log::info('Status change SMS sent successfully', [
                'driver_id' => $driver->id,
                'driver_contact' => $driver->contact_num,
                'trip_ticket_id' => $tripTicket->id,
                'new_status' => $newStatus
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send status change SMS notification', [
                'trip_ticket_id' => $tripTicket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw exception - SMS failure should not affect status change
        }
    }
    
    /**
     * Send SMS notification to all passengers when trip ticket is cancelled.
     */
    private function sendCancellationNotificationToPassengers(TripTicket $tripTicket, $cancellationReason)
    {
        \Log::info('Starting SMS cancellation notification to passengers', [
            'trip_ticket_id' => $tripTicket->id
        ]);
        
        try {
            // Get itinerary information
            $itinerary = $tripTicket->itinerary;
            if (!$itinerary) {
                \Log::warning('Trip ticket cancellation: No itinerary found', [
                    'trip_ticket_id' => $tripTicket->id
                ]);
                return;
            }
            
            // Get head of party contact from travel order employee
            $headOfPartyContact = null;
            if ($itinerary->travelOrder && $itinerary->travelOrder->employee) {
                $headOfPartyContact = $itinerary->travelOrder->employee->contact_num;
            }
            
            // Get all passengers
            $passengers = $tripTicket->passengers ?? [];
            $headOfParty = $tripTicket->head_of_party ?? '';
            
            // Filter out empty passengers and head of party (already notified via travel order)
            $otherPassengers = array_filter($passengers, function($p) use ($headOfParty) {
                return trim($p) && trim($p) !== trim($headOfParty);
            });
            
            \Log::info('Passenger list for SMS notification', [
                'total_passengers' => count($passengers),
                'other_passengers_count' => count($otherPassengers),
                'has_head_of_party_contact' => !!$headOfPartyContact
            ]);
            
            // Build simplified cancellation message for all recipients
            $destination = $itinerary->destination ?? 'N/A';
            $dateFrom = \Carbon\Carbon::parse($itinerary->date_from)->format('M j, Y');
            
            // Message for head of party
            $headOfPartyMsg = "Trip Cancelled.\n\n" .
                              "To: {$destination}\n" .
                              "Date: {$dateFrom}\n" .
                              "Reason: {$cancellationReason}";
            
            // Message for other passengers (same format)
            $passengerMessage = "Trip Cancelled.\n\n" .
                                "To: {$destination}\n" .
                                "Date: {$dateFrom}\n" .
                                "Reason: {$cancellationReason}";
            
            // Send to head of party first
            if ($headOfPartyContact) {
                \Log::info('Sending cancellation SMS to head of party', [
                    'contact' => $headOfPartyContact
                ]);
                
                $this->sendPhilSms($headOfPartyContact, $headOfPartyMsg);
            }
            
            // Send to other passengers
            foreach ($otherPassengers as $passengerName) {
                // Try to find passenger's contact number from employee database
                $passengerContact = $this->findPassengerContactNumber($passengerName);
                
                if ($passengerContact) {
                    \Log::info('Sending cancellation SMS to passenger', [
                        'passenger' => $passengerName,
                        'contact' => $passengerContact
                    ]);
                    
                    $this->sendPhilSms($passengerContact, $passengerMessage);
                } else {
                    \Log::warning('Passenger contact number not found', [
                        'passenger_name' => $passengerName
                    ]);
                }
            }
            
            \Log::info('Cancellation SMS notifications sent successfully', [
                'trip_ticket_id' => $tripTicket->id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send cancellation SMS to passengers', [
                'trip_ticket_id' => $tripTicket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw exception - SMS failure should not affect cancellation
        }
    }
    
    /**
     * Try to find a passenger's contact number from the employee database.
     */
    private function findPassengerContactNumber($passengerName)
    {
        // Search for employee by full name
        $employee = \App\Models\Employee::where('full_name', 'LIKE', '%' . $passengerName . '%')
            ->orWhere('first_name', 'LIKE', '%' . $passengerName . '%')
            ->orWhere(function($q) use ($passengerName) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $passengerName . '%']);
            })
            ->first();
        
        if ($employee && $employee->contact_num) {
            return $employee->contact_num;
        }
        
        // If not found in employees, return null
        // (passenger might be external, in which case we don't have their contact)
        return null;
    }
        
    /**
     * Send SMS using PhilSMS API v3.
     */
    private function sendPhilSms($recipient, $message)
    {
        // PhilSMS API v3 configuration
        $apiKey = env('PHILSMS_API_KEY');
        $senderId = env('PHILSMS_SENDER_ID', 'PHILSMS');
            
        \Log::info('Preparing PhilSMS v3 API request', [
            'api_key_configured' => !empty($apiKey),
            'sender_id' => $senderId,
            'recipient' => $recipient
        ]);
            
        if (empty($apiKey)) {
            \Log::error('PhilSMS API key not configured - cannot send SMS');
            return;
        }
            
        // Clean phone number - remove all non-numeric characters except +
        $cleanNumber = preg_replace('/[^0-9+]/', '', $recipient);
            
        // Ensure number starts with country code
        if (strpos($cleanNumber, '+') !== 0) {
            if (substr($cleanNumber, 0, 2) === '63') {
                $cleanNumber = '+' . $cleanNumber;
            } elseif (substr($cleanNumber, 0, 1) === '0') {
                // Convert 0917... to +63917...
                $cleanNumber = '+63' . substr($cleanNumber, 1);
            } else {
                $cleanNumber = '+63' . $cleanNumber;
            }
        }
            
        // PhilSMS v3 API endpoint
        $apiUrl = 'https://dashboard.philsms.com/api/v3/sms/send';
            
        // Prepare JSON payload for PhilSMS v3
        $postData = json_encode([
            'recipient' => ltrim($cleanNumber, '+'), // Remove + for API
            'sender_id' => $senderId,
            'type' => 'plain',
            'message' => $message
        ]);
            
        \Log::debug('PhilSMS v3 POST data', [
            'url' => $apiUrl,
            'payload' => $postData
        ]);
            
        // Send HTTP POST request with Bearer token authentication
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
            
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
            
        \Log::info('PhilSMS v3 API Response', [
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ]);
            
        if ($error) {
            \Log::error('PhilSMS cURL Error', ['error' => $error]);
            throw new \Exception("PhilSMS API Error: {$error}");
        }
            
        // Parse response
        $responseData = json_decode($response, true);
            
        if ($httpCode !== 200) {
            $errorMessage = $responseData['message'] ?? "HTTP {$httpCode}";
            \Log::error('PhilSMS HTTP Error', [
                'http_code' => $httpCode,
                'error_message' => $errorMessage,
                'response' => $response
            ]);
            throw new \Exception("PhilSMS API Error: {$errorMessage}");
        }
            
        // Check if status is success
        if (isset($responseData['status']) && $responseData['status'] === 'success') {
            \Log::info('PhilSMS v3 SMS sent successfully', [
                'recipient' => $recipient,
                'response_data' => $responseData['data'] ?? null
            ]);
        } else {
            $errorMessage = $responseData['message'] ?? 'Unknown error';
            \Log::error('PhilSMS API returned error status', [
                'status' => $responseData['status'] ?? 'unknown',
                'message' => $errorMessage
            ]);
            throw new \Exception("PhilSMS API Error: {$errorMessage}");
        }
    }
}