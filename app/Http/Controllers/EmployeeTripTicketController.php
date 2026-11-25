<?php

namespace App\Http\Controllers;

use App\Models\TripTicket;
use App\Models\TravelOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Mpdf;
use Carbon\Carbon;
use Illuminate\Http\Response;

class EmployeeTripTicketController extends Controller
{
    /**
     * Display a listing of trip tickets for the employee.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Get the tab parameter, default to 'approved'
        $tab = $request->get('tab', 'approved');
        
        // Get search term if provided
        $search = $request->get('search', '');
        
        // Build query based on the selected tab
        $query = TripTicket::with(['itinerary.driver', 'itinerary.vehicle']);
        
        // Filter by employee - show trip tickets where the employee is a passenger, head of party, or the original travel order creator
        $query->where(function ($subQuery) use ($employee) {
            $employeeFullName = $employee->full_name; // e.g., "Juan Dela Cruz"
            $firstName = $employee->first_name; // e.g., "Juan"
            $lastName = $employee->last_name; // e.g., "Dela Cruz"
            
            // Check if employee is head of party (stored as full name string)
            $subQuery->where(function($headQuery) use ($employeeFullName, $firstName, $lastName) {
                $headQuery->where('head_of_party', $employeeFullName)
                          // Also match if first and last name appear in the head_of_party field
                          ->orWhereRaw('(head_of_party LIKE ? AND head_of_party LIKE ?)', ["%{$firstName}%", "%{$lastName}%"]);
            })
                     // Check if employee is a passenger (stored as array of full names OR pipe-separated string)
                     ->orWhere(function ($passengerQuery) use ($employeeFullName, $firstName, $lastName) {
                         // Search in passengers JSON array - check for EXACT full name match only
                         // This prevents false positives from partial name matching
                         $passengerQuery->whereRaw('JSON_SEARCH(passengers, "one", ?) IS NOT NULL', [$employeeFullName])
                                        ->orWhereRaw('JSON_SEARCH(passengers, "one", CONCAT("%", ?, "%")) IS NOT NULL', [$employeeFullName . '|'])
                                        ->orWhereRaw('JSON_SEARCH(passengers, "one", CONCAT("%", ?, "%")) IS NOT NULL', ['|' . $employeeFullName . '|'])
                                        ->orWhereRaw('JSON_SEARCH(passengers, "one", CONCAT("%", ?, "%")) IS NOT NULL', ['|' . $employeeFullName])
                                        ->orWhereRaw('passengers LIKE ?', ['%' . $firstName . '%'])
                                        ->orWhereRaw('passengers LIKE ?', ['%' . $lastName . '%']);
                     })
                     // Check if employee is the original travel order creator (their travel order is included in the itinerary)
                     ->orWhere(function ($orQuery) use ($employee) {
                         // Get all travel order IDs associated with this employee's travel orders
                         $travelOrderIds = TravelOrder::where('employee_id', $employee->id)->pluck('id');
                         
                         // Only check itineraries if employee has travel orders
                         if ($travelOrderIds->isNotEmpty()) {
                             $orQuery->whereHas('itinerary', function ($itineraryQuery) use ($travelOrderIds) {
                                 $itineraryQuery->where(function ($itoQuery) use ($travelOrderIds) {
                                     foreach ($travelOrderIds as $toId) {
                                         $itoQuery->orWhereRaw('JSON_CONTAINS(travel_order_id, ?)', [json_encode((int) $toId)])
                                         ->orWhereRaw('JSON_CONTAINS(travel_order_id, ?)', [json_encode((string) $toId)]);
                                     }
                                 });
                             });
                         }
                     });
        });
        
        // Apply search filter if provided
        if ($search) {
            $query->where(function ($subQuery) use ($search, $employee) {
                // Search by employee name in travel orders
                $travelOrderIds = TravelOrder::where(function ($toQuery) use ($search) {
                    $toQuery->whereHas('employee', function ($empQuery) use ($search) {
                        $empQuery->where('first_name', 'LIKE', "%{$search}%")
                                 ->orWhere('last_name', 'LIKE', "%{$search}%")
                                 ->orWhereRaw('CONCAT(first_name, " ", last_name) LIKE ?', ["%{$search}%"]);
                    });
                })->pluck('id');
                
                $subQuery->whereHas('itinerary', function ($itineraryQuery) use ($travelOrderIds) {
                    if ($travelOrderIds->isNotEmpty()) {
                        $itineraryQuery->where(function ($itoQuery) use ($travelOrderIds) {
                            foreach ($travelOrderIds as $toId) {
                                $itoQuery->orWhereRaw('JSON_CONTAINS(travel_order_id, ?)', [json_encode((int) $toId)])
                                         ->orWhereRaw('JSON_CONTAINS(travel_order_id, ?)', [json_encode((string) $toId)]);
                            }
                        });
                    }
                })
                // Search by destination or purpose
                ->orWhereHas('itinerary', function ($itineraryQuery) use ($search) {
                    $itineraryQuery->where('destination', 'LIKE', "%{$search}%")
                                   ->orWhere('purpose', 'LIKE', "%{$search}%");
                })
                // Search by passenger name (exact match in JSON array)
                ->orWhereRaw('JSON_SEARCH(passengers, "one", CONCAT("%", ?, "%")) IS NOT NULL', [$search])
                // Search by head of party name
                ->orWhere('head_of_party', 'LIKE', "%{$search}%");
            });
        }
        
        switch ($tab) {
            case 'completed':
                // Trip tickets that have been completed
                $query->where('status', 'Completed');
                break;
            case 'on-going':
                // On-Going trip tickets (currently in progress)
                $query->where('status', 'On-Going');
                break;
            case 'cancelled':
                // Cancelled trip tickets
                $query->where('status', 'Cancelled');
                break;
            case 'approved':
            default:
                // Approved trip tickets (Approved status)
                $query->where('status', 'Approved');
                break;
        }
        
        $tripTickets = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return view('trip-tickets.employee.index', compact('tripTickets', 'tab', 'search'));
    }
    
    /**
     * Display the specified trip ticket for the employee.
     */
    public function show(TripTicket $tripTicket): View
    {
        $user = Auth::user();
        $employee = $user->employee;
            
        // Check if the employee is a passenger or head of party
        $isPassenger = false;
        $employeeFullName = $employee->full_name;
        $firstName = $employee->first_name;
        $lastName = $employee->last_name;
            
        if ($tripTicket->passengers) {
            if (is_array($tripTicket->passengers)) {
                // If passengers is an array - check for exact match
                foreach ($tripTicket->passengers as $passenger) {
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
                                $isPassenger = true;
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
            } else {
                // If passengers is a string (JSON encoded)
                $passengersArray = json_decode($tripTicket->passengers, true);
                if (is_array($passengersArray)) {
                    foreach ($passengersArray as $passenger) {
                        if ($passenger === $employeeFullName) {
                            $isPassenger = true;
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
                                $isPassenger= true;
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
            }
        }
            
        // Check head of party with flexible matching
        $isHeadOfParty = ($tripTicket->head_of_party === $employeeFullName);
        if (!$isHeadOfParty) {
            // Try flexible matching: check if first and last name appear in head_of_party
            if (stripos($tripTicket->head_of_party, $firstName) !== false && stripos($tripTicket->head_of_party, $lastName) !== false) {
                $isHeadOfParty = true;
            }
        }
            
        // Check if employee is the original travel order creator
        $isTravelOrderCreator = false;
        if ($tripTicket->itinerary) {
            $travelOrders = $tripTicket->itinerary->getAllTravelOrders();
            foreach ($travelOrders as $travelOrder) {
                if ($travelOrder && $travelOrder->employee_id === $employee->id) {
                    $isTravelOrderCreator = true;
                    break;
                }
            }
        }
            
        if (!$isPassenger && !$isHeadOfParty && !$isTravelOrderCreator) {
            abort(403);
        }
            
        return view('trip-tickets.employee.show', compact('tripTicket'));
    }
    
    /**
     * Generate Excel for the specified trip ticket.
     */
    public function generateExcel(TripTicket $tripTicket): Response
    {
        $user = Auth::user();
        $employee = $user->employee;
            
        // Check if the employee is authorized to view this trip ticket
        $isPassenger = false;
        $employeeFullName = $employee->full_name;
        $firstName = $employee->first_name;
        $lastName = $employee->last_name;
                    
        if ($tripTicket->passengers) {
            if (is_array($tripTicket->passengers)) {
                // If passengers is an array - check for exact match
                foreach ($tripTicket->passengers as $passenger) {
                    // Handle both direct name and pipe-separated format
                    if ($passenger === $employeeFullName) {
                        $isPassenger = true;
                        break;
                    }
                    // Also check if it's a pipe-separated string containing the employee name
                    if (is_string($passenger) && strpos($passenger, '|') !== false) {
                        $passengerParts = explode('|', $passenger);
                        foreach ($passengerParts as $part) {
                            if (trim($part) === $employeeFullName) {
                                $isPassenger = true;
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
            } else {
                // If passengers is a string (JSON encoded)
                $passengersArray = json_decode($tripTicket->passengers, true);
                if (is_array($passengersArray)) {
                    foreach ($passengersArray as $passenger) {
                        if ($passenger === $employeeFullName) {
                            $isPassenger = true;
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
                                $isPassenger= true;
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
            }
        }
                    
        // Check head of party with flexible matching
        $isHeadOfParty = ($tripTicket->head_of_party === $employeeFullName);
        if (!$isHeadOfParty) {
            // Try flexible matching: check if first and last name appear in head_of_party
            if (stripos($tripTicket->head_of_party, $firstName) !== false && stripos($tripTicket->head_of_party, $lastName) !== false) {
                $isHeadOfParty= true;
            }
        }
            
        // Check if employee is the original travel order creator
        $isTravelOrderCreator = false;
        if ($tripTicket->itinerary) {
            $travelOrders = $tripTicket->itinerary->getAllTravelOrders();
            foreach ($travelOrders as $travelOrder) {
                if ($travelOrder && $travelOrder->employee_id === $employee->id) {
                    $isTravelOrderCreator = true;
                    break;
                }
            }
        }
            
        if (!$isPassenger && !$isHeadOfParty && !$isTravelOrderCreator) {
            abort(403);
        }
            
        // Delegate to TripTicketController's generateExcel method
        $controller = new \App\Http\Controllers\TripTicketController();
        return $controller->generateExcel($tripTicket->id);
    }

    /**
     * Get trip ticket details as JSON for employee.
     */
    public function getDetails($id): \Illuminate\Http\JsonResponse
    {
        try {
            $tripTicket = TripTicket::with([
                'itinerary.driver',
                'itinerary.vehicle'
            ])->findOrFail($id);
            
            // Check if the current user is authorized to view this trip ticket
            $user = auth()->user();
            
            if (!$user || !$user->employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this trip ticket'
                ], 403);
            }
            
            $employee = $user->employee;
            $employeeFullName = $employee->full_name;
            $firstName = $employee->first_name;
            $lastName = $employee->last_name;
                        
            // Check if employee is head of party
            $isHeadOfParty = ($tripTicket->head_of_party === $employeeFullName);
            if (!$isHeadOfParty) {
                // Try flexible matching: check if first and last name appear in head_of_party
                if (stripos($tripTicket->head_of_party, $firstName) !== false && stripos($tripTicket->head_of_party, $lastName) !== false) {
                    $isHeadOfParty = true;
                }
            }
                        
            // Check if employee is a passenger
            $isPassenger = false;
            if (is_array($tripTicket->passengers)) {
                foreach ($tripTicket->passengers as $passenger) {
                    // Handle both direct name and pipe-separated format
                    if ($passenger === $employeeFullName) {
                        $isPassenger = true;
                        break;
                    }
                    // Also check if it's a pipe-separated string containing the employee name
                    if (is_string($passenger) && strpos($passenger, '|') !== false) {
                        $passengerParts = explode('|', $passenger);
                        foreach ($passengerParts as $part) {
                            if (trim($part) === $employeeFullName) {
                                $isPassenger = true;
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
                // Handle case where passengers is stored as a plain pipe-separated string
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
            
            // Get the trip ticket data as array
            $responseData = $tripTicket->toArray();
            
            // Ensure passengers is always an array for the frontend
            $passengers = $tripTicket->passengers;
            
            if (!isset($passengers) || $passengers === null) {
                $responseData['passengers'] = [];
            } elseif (is_string($passengers)) {
                // Check if it's a pipe-separated string
                if (strpos($passengers, '|') !== false) {
                    // Convert pipe-separated string to array
                    $passengerArray = explode('|', $passengers);
                    $responseData['passengers'] = array_values(array_filter($passengerArray, function($p) {
                        return !is_null($p) && trim($p) !== '';
                    }));
                } else {
                    // If passengers is stored as a JSON string, decode it
                    $decoded = json_decode($passengers, true);
                    $responseData['passengers'] = is_array($decoded) ? array_values($decoded) : [];
                }
            } elseif (is_array($passengers)) {
                // Filter out any null or empty values and re-index
                $responseData['passengers'] = array_values(array_filter($passengers, function($p) {
                    return !is_null($p) && trim($p) !== '';
                }));
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
                
                // Build driver data
                $driverData = null;
                if ($tripTicket->itinerary->driver) {
                    $driverData = [
                        'id' => $tripTicket->itinerary->driver->id,
                        'full_name' => $tripTicket->itinerary->driver->full_name,
                        'contact_num' => $tripTicket->itinerary->driver->contact_num ?? null,
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
                        'contact_num' => $firstTravelOrder->employee->contact_num ?? null,
                    ];
                }
                
                $responseData['itinerary'] = [
                    'id' => $tripTicket->itinerary->id,
                    'driver' => $driverData,
                    'vehicle' => $vehicleData,
                    'destination' => $tripTicket->itinerary->destination ?? null,
                    'purpose' => $tripTicket->itinerary->purpose ?? null,
                    'date_from' => $tripTicket->itinerary->date_from ? $tripTicket->itinerary->date_from->format('Y-m-d') : null,
                    'date_to' => $tripTicket->itinerary->date_to ? $tripTicket->itinerary->date_to->format('Y-m-d') : null,
                    'departure_time' => $tripTicket->itinerary->departure_time ?? null,
                    'travel_order_employee' => $employeeData,
                    'travel_order_purpose' => $firstTravelOrder ? ($firstTravelOrder->purpose ?? null) : null,
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getDetails for employee trip ticket ID ' . $id . ': ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching trip ticket details: ' . $e->getMessage()
            ], 500);
        }
    }
}