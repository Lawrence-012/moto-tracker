<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use App\Models\TravelOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ConvertApi\ConvertApi;
use Carbon\Carbon;

class ItineraryController extends Controller
{
    /**
     * Get vehicles available for selection (excluding those under maintenance)
     */
    private function getAvailableVehicles()
    {
        // Get vehicles that are currently under maintenance (Ongoing status)
        $underMaintenance = \App\Models\VehicleMaintenance::where('status', 'Ongoing')
            ->pluck('vehicle_id');
        
        // Only show vehicles with 'Available' or 'Active' status
        // Exclude vehicles under maintenance
       return \App\Models\Vehicle::whereIn('status', ['Available', 'Active'])
            ->whereNotIn('id', $underMaintenance)
            ->get();
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'pending');
        $search = $request->get('search');
        
        // Get all itineraries for all tabs (no filtering here)
        $query = Itinerary::with(['vehicle', 'driver.user'])
            ->orderBy('created_at', 'desc')  // Newest created first
            ->orderBy('date_from', 'desc');   // Then by date_from as secondary sort
        
        // Apply search filter if provided
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('destination', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%")
                  ->orWhereHas('driver', function($driverQuery) use ($search) {
                      $driverQuery->where('first_name', 'like', "%{$search}%")
                                 ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('vehicle', function($vehicleQuery) use ($search) {
                      $vehicleQuery->where('model', 'like', "%{$search}%")
                                  ->orWhere('plate_number', 'like', "%{$search}%")
                                  ->orWhere('type', 'like', "%{$search}%");
                  })
                  // Search by date (date_from) - supports multiple formats
                  ->orWhereRaw('DATE(date_from) LIKE ?', ['%' . $search . '%'])
                  ->orWhereRaw('DATE_FORMAT(date_from, "%M %d, %Y") LIKE ?', ['%' . $search . '%'])
                  ->orWhereRaw('DATE_FORMAT(date_from, "%b %d, %Y") LIKE ?', ['%' . $search . '%'])
                  ->orWhereRaw('DAY(date_from) = ?', [(int)$search])
                  ->orWhereRaw('MONTH(date_from) = ?', [(int)$search])
                  ->orWhereRaw('YEAR(date_from) = ?', [(int)$search]);
            });
        }
        
        // Paginate the results
        $allItineraries = $query->paginate(10);
        
        // Filter the current tab's data for display
        $currentTabItineraries = $allItineraries->filter(function($itinerary) use ($tab) {
            switch ($tab) {
                case 'approved':
                    return $itinerary->status === 'Approved';
                case 'completed':
                    return $itinerary->status === 'Completed';
                case 'cancelled':
                    return $itinerary->status === 'Cancelled';
                case 'pending':
                default:
                    return $itinerary->status === 'Not yet Approved';
            }
        });
        
        // Create a new paginator for the filtered results
        $currentPage = $allItineraries->currentPage();
        $perPage = $allItineraries->perPage();
        $filteredItems = $currentTabItineraries->values(); // Reset keys
        $totalFiltered = $filteredItems->count();
        
        // Create a custom paginator for the filtered results
        $paginatedFiltered = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredItems,
            $totalFiltered,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'pageName' => 'page']
        );
        
        // Add the search parameter to pagination links
        $paginatedFiltered->appends(['search' => $search, 'tab' => $tab]);
        
        // Get data for the modal
        $vehicles = $this->getAvailableVehicles();
        $drivers = \App\Models\Driver::where('availability_status', 'Active')
            ->with('user') 
            ->get();
        
        // Get all itineraries to check which travel orders are already assigned
        $allItinerariesList = \App\Models\Itinerary::all();
        $assignedTravelOrderIds = [];
        
        foreach ($allItinerariesList as $itinerary) {
            $travelOrderIds = $itinerary->travel_order_id ?? [];
            if (is_array($travelOrderIds)) {
                $assignedTravelOrderIds = array_merge($assignedTravelOrderIds, $travelOrderIds);
            }
        }
        
        // Remove duplicates
        $assignedTravelOrderIds = array_unique($assignedTravelOrderIds);
        
        // Filter out travel orders that already have an itinerary (for CREATE modal only)
        $allApprovedTravelOrders = TravelOrder::where('status', 'approved')
            ->with(['employee'])
            ->orderBy('created_at', 'desc')  // Latest created first
            ->get();
        
        // Filter approved travel orders to exclude those already assigned to itineraries
        $travelOrdersForCreate = $allApprovedTravelOrders->filter(function($travelOrder) use ($assignedTravelOrderIds) {
            return !in_array($travelOrder->id, $assignedTravelOrderIds);
        })->sortByDesc('created_at')->values();
        
        \Log::info('Itinerary Create - Travel Orders Query', [
            'total_approved' => TravelOrder::where('status', 'approved')->count(),
            'with_itinerary' => TravelOrder::where('status', 'approved')->whereHas('itinerary')->count(),
            'without_itinerary' => $travelOrdersForCreate->count(),
            'travel_orders' => $travelOrdersForCreate->map(function($to) {
                return [
                    'id' => $to->id,
                    'destination' => $to->destination,
                    'status' => $to->status,
                    'employee_name' => $to->employee ? $to->employee->full_name : 'N/A',
                ];
            }),
        ]);
        
        // Get ALL approved travel orders for EDIT modal
        // Include: 1) Travel orders without itinerary, 2) Travel orders with pending itinerary
        $allApprovedTravelOrdersForEdit = TravelOrder::where('status', 'approved')
            ->with(['employee'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Filter to exclude travel orders that have approved/completed itineraries
        $travelOrdersForEdit = $allApprovedTravelOrdersForEdit->filter(function($travelOrder) {
            // Check if this travel order has any itinerary
            $hasItinerary = false;
            $hasPendingItinerary = false;
            
            // Search all itineraries for this travel order
            $allItinerariesList = \App\Models\Itinerary::all();
            foreach ($allItinerariesList as $itinerary) {
                $toIds = $itinerary->travel_order_id ?? [];
                if (is_array($toIds) && in_array($travelOrder->id, $toIds)) {
                    $hasItinerary = true;
                    // Check if itinerary is pending (Not yet Approved)
                    if ($itinerary->status === 'Not yet Approved') {
                        $hasPendingItinerary = true;
                    }
                    break;
                }
            }
            
            // Include if: no itinerary OR has pending itinerary
            return !$hasItinerary || $hasPendingItinerary;
        })->values();
        
        \Log::info('Itinerary Edit - Travel Orders Query', [
            'total_approved' => TravelOrder::where('status', 'approved')->count(),
            'filtered_for_edit' => $travelOrdersForEdit->count(),
        ]);

        return view('itineraries.index', [
            'allItineraries' => $allItineraries,
            'currentTabItineraries' => $paginatedFiltered,
            'tab' => $tab,
            'search' => $search,
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'travelOrdersForCreate' => $travelOrdersForCreate,
            'travelOrdersForEdit' => $travelOrdersForEdit
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vehicles = $this->getAvailableVehicles();
        $drivers = \App\Models\Driver::where('availability_status', 'Active')
            ->with('user') 
            ->get();
        
        // Get all travel_order_ids from itineraries table (stored as JSON)
        $usedTravelOrderIds = [];
        $itineraries = \App\Models\Itinerary::select('travel_order_id')->get();
        foreach ($itineraries as $itinerary) {
            if ($itinerary->travel_order_id) {
                $usedTravelOrderIds = array_merge($usedTravelOrderIds, $itinerary->travel_order_id);
            }
        }
        $usedTravelOrderIds = array_unique(array_filter($usedTravelOrderIds));
        
        // Get approved travel orders that don't have itineraries yet
        $travelOrders = TravelOrder::where('status', 'approved')
            ->whereNotIn('id', $usedTravelOrderIds)
            ->get();
        
        // Determine which layout to use based on user's role
        $user = auth()->user();
        $isMotorpoolAdmin = $user && $user->hasRole(\App\Models\User::ROLE_MOTORPOOL_ADMIN);
        $isAdmin = $user && $user->hasRole(\App\Models\User::ROLE_ADMIN);
        
        if ($isMotorpoolAdmin || $isAdmin) {
            $layout = 'itineraries.create-motorpool';
            $backUrl = route('itinerary.index'); // Back to itinerary index for motorpool admin
        } else {
            $layout = 'itineraries.create-employee';
            $backUrl = auth()->user() && auth()->user()->employee ? 
                      (auth()->user()->employee->is_vp ? route('vp.travel-orders.index') : 
                       (auth()->user()->employee->is_head ? route('unithead.travel-orders.index') : 
                        route('dashboard'))) : route('dashboard');
        }
        
        return view($layout, compact('vehicles', 'drivers', 'travelOrders', 'backUrl'));
    }
    
    /**
     * Show the itinerary creation modal
     */
    public function createModal()
    {
        $vehicles = $this->getAvailableVehicles();
        $drivers = \App\Models\Driver::where('availability_status', 'Active')
            ->with('user') 
            ->get();
        
        // Get all itineraries to check which travel orders are already assigned
        $allItinerariesList = \App\Models\Itinerary::all();
        $assignedTravelOrderIds = [];
        
        foreach ($allItinerariesList as $itinerary) {
            $travelOrderIds = $itinerary->travel_order_id ?? [];
            if (is_array($travelOrderIds)) {
                $assignedTravelOrderIds = array_merge($assignedTravelOrderIds, $travelOrderIds);
            }
        }
        
        // Remove duplicates
        $assignedTravelOrderIds = array_unique($assignedTravelOrderIds);
        
        // Filter out travel orders that already have an itinerary (sorted by newest first)
        $travelOrders = TravelOrder::where('status', 'approved')
            ->whereNotIn('id', $assignedTravelOrderIds)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get trip tickets to check availability
        $tripTickets = \App\Models\TripTicket::with(['itinerary.vehicle', 'itinerary.driver'])->get();
        
        return view('itineraries.modals.create-itinerary-modal', compact('vehicles', 'drivers', 'travelOrders', 'tripTickets'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'destination' => 'required|string|max:255',
            'destination_latitude' => 'nullable|numeric|between:-90,90',
            'destination_longitude' => 'nullable|numeric|between:-180,180',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'departure_time' => 'required',
            'purpose' => 'required|string',
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:drivers,id',
            'travel_order_ids' => 'nullable|array',
            'travel_order_ids.*' => 'exists:travel_orders,id'
        ]);

        // Do not accept approval/status fields from the browser. A newly
        // created itinerary must enter the Head Panel/Unit Head approval queue.
        $data = $request->only([
            'destination',
            'destination_latitude',
            'destination_longitude',
            'date_from',
            'date_to',
            'departure_time',
            'purpose',
            'vehicle_id',
            'driver_id',
        ]);

        if ($request->has('travel_order_ids') && is_array($request->travel_order_ids) && count($request->travel_order_ids) > 0) {
            $data['travel_order_id'] = array_values(array_unique(array_map('intval', $request->travel_order_ids)));
        }

        $data['status'] = 'Not yet Approved';
        $data['unit_head_approved'] = false;
        $data['unit_head_approved_by'] = null;
        $data['unit_head_approved_at'] = null;
        $data['vp_approved'] = false;
        $data['vp_approved_by'] = null;
        $data['vp_approved_at'] = null;
        $data['rejection_reason'] = null;
        $data['rejected_by_employee_id'] = null;
        $data['rejected_at'] = null;

        $itinerary = Itinerary::create($data);

        if ($request->ajax() || $request->wantsJson()) {
            $itineraryData = $itinerary->toArray();
            $itineraryData['travel_orders'] = $itinerary->travelOrders(); // Call as method, not property
            
            return response()->json([
                'success' => true,
                'message' => 'Itinerary created successfully with ' . count($request->travel_order_ids ?? []) . ' travel order(s)!',
                'itinerary' => $itineraryData
            ]);
        }

        return redirect()->route('itinerary.index')->with('success', 'Itinerary created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Itinerary $itinerary)
    {
        $itinerary->load(['vehicle', 'driver']);
        return view('itineraries.show', compact('itinerary'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Itinerary $itinerary)
    {
        $vehicles = $this->getAvailableVehicles();
        $drivers = \App\Models\Driver::where('availability_status', 'Active')
            ->with('user') // Eager load user relationship for contact number
            ->get();
        
        // For edit: Show all approved travel orders EXCEPT those that have itineraries
        $travelOrders = TravelOrder::where('status', 'approved')
            ->whereDoesntHave('itinerary')
            ->orWhere('id', $itinerary->travel_order_id)
            ->orderBy('date_from', 'desc')
            ->get();
        
        // Determine which layout to use based on user's role
        $user = auth()->user();
        $isMotorpoolAdmin = $user && $user->hasRole(\App\Models\User::ROLE_MOTORPOOL_ADMIN);
        $isAdmin = $user && $user->hasRole(\App\Models\User::ROLE_ADMIN);
        
        if ($isMotorpoolAdmin || $isAdmin) {
            $layout = 'itineraries.edit-motorpool';
            $backUrl = route('itinerary.index'); // Back to itinerary index for motorpool admin
        } else {
            $layout = 'itineraries.edit-employee';
            $backUrl = auth()->user() && auth()->user()->employee ? 
                      (auth()->user()->employee->is_vp ? route('vp.travel-orders.index') : 
                       (auth()->user()->employee->is_head ? route('unithead.travel-orders.index') : 
                        route('dashboard'))) : route('dashboard');
        }
        
        return view($layout, compact('itinerary', 'vehicles', 'drivers', 'travelOrders', 'backUrl'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Itinerary $itinerary)
    {
        $request->validate([
            'destination' => 'required|string|max:255',
            'destination_latitude' => 'nullable|numeric|between:-90,90',
            'destination_longitude' => 'nullable|numeric|between:-180,180',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'departure_time' => 'required',
            'purpose' => 'required|string',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'travel_order_ids' => 'nullable|array',
            'travel_order_ids.*' => 'exists:travel_orders,id'
        ]);

        // An itinerary that has already produced an active/completed trip ticket
        // must not be altered. This prevents an approved trip from being changed
        // underneath the approval record and preserves the tracking history.
        $activeTripTicketExists = $itinerary->tripTickets()
            ->whereIn('status', ['Approved', 'On-Going', 'Completed'])
            ->exists();

        if ($activeTripTicketExists) {
            return redirect()->back()->with('error', 'This itinerary can no longer be edited because its Trip Ticket has already been approved or started.');
        }

        // Editing itinerary details invalidates previous approvals. The edited
        // itinerary must return to the Head Panel/Unit Head approval queue and
        // then pass final approval again before a Trip Ticket can be created.
        $data = $request->only([
            'destination',
            'destination_latitude',
            'destination_longitude',
            'date_from',
            'date_to',
            'departure_time',
            'purpose',
            'vehicle_id',
            'driver_id',
        ]);
        
        if ($request->has('travel_order_ids') && is_array($request->travel_order_ids)) {
            $data['travel_order_id'] = array_values(array_unique(array_map('intval', $request->travel_order_ids)));
        }

        $data['status'] = 'Not yet Approved';
        $data['unit_head_approved'] = false;
        $data['unit_head_approved_by'] = null;
        $data['unit_head_approved_at'] = null;
        $data['vp_approved'] = false;
        $data['vp_approved_by'] = null;
        $data['vp_approved_at'] = null;
        $data['rejection_reason'] = null;
        $data['rejected_by_employee_id'] = null;
        $data['rejected_at'] = null;

        $itinerary->update($data);

        if ($request->ajax() || $request->wantsJson()) {
            $itineraryData = $itinerary->toArray();
            $itineraryData['travel_orders'] = $itinerary->travelOrders(); 
            
            return response()->json([
                'success' => true,
                'message' => 'Itinerary updated successfully!',
                'itinerary' => $itineraryData
            ]);
        }

        return redirect()->route('itinerary.index')->with('success', 'Itinerary updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Itinerary $itinerary)
    {
        $itinerary->status = 'archived';
        $itinerary->save();
        return redirect()->route('itinerary.index')->with('success', 'Itinerary archived successfully.');
    }
    
    /**
     * Generate Excel for itinerary preview (no PDF conversion)
     */
    public function generateExcel(Itinerary $itinerary)
    {
        // Load the itinerary with all necessary relationships
        $itinerary->load(['vehicle', 'driver']);
        
        // Get driver information with proper null checking
        $driver = $itinerary->driver;
        $full_name = $driver ? ($driver->full_name ?? 'N/A') : 'N/A';
        $position = $driver ? ($driver->position ?? 'N/A') : 'N/A';
        $official_station = $driver ? ($driver->official_station ?? 'N/A') : 'N/A';
        
        // Get itinerary details
        $purpose = $itinerary->purpose ?? 'N/A';
        $destination = $itinerary->destination ?? 'N/A';
        
        // Get vehicle details with proper null checking
        $vehicle_model = $itinerary->vehicle ? ($itinerary->vehicle->model ?? 'N/A') : 'N/A';
        $plate_number = $itinerary->vehicle ? ($itinerary->vehicle->plate_number ?? 'N/A') : 'N/A';
        
        // Get date details
        $date_from = $itinerary->date_from ? Carbon::parse($itinerary->date_from)->format('F d, Y') : 'N/A';
        $date_to = '';
        if ($itinerary->date_to && $itinerary->date_to != $itinerary->date_from) {
            $date_to = Carbon::parse($itinerary->date_to)->format('F d, Y');
        }
        
        // Get departure time
        $departure_time = '-';
        if ($itinerary->departure_time) {
            $departure_time = Carbon::parse($itinerary->departure_time)->format('h:i A');
        }
        
        // Load the Excel template
        $templatePath = public_path('templates/itinerary_template.xlsx');
        if (!file_exists($templatePath)) {
            abort(500, 'Itinerary template not found.');
        }
        
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // === DRIVER DETAILS ===
        $sheet->setCellValue('C9', $full_name);
        $sheet->setCellValue('E23', $full_name);
        $sheet->setCellValue('E24', $position);
        $sheet->setCellValue('E25', $official_station);
        
        // === ITINERARY DETAILS ===
        $sheet->setCellValue('C10', $purpose);
        $sheet->setCellValue('E26', $purpose);
        $sheet->setCellValue('G11', $destination);
        $sheet->setCellValue('D34', $destination);
        $sheet->setCellValue('G34', $departure_time);
        
        // === VEHICLE DETAILS ===
        $sheet->setCellValue('I34', $vehicle_model);
        $sheet->setCellValue('I35', $plate_number);
        
        // === DRIVER DETAILS (NEW) ===
        $sheet->setCellValue('I41', $full_name);  // Driver name
        $sheet->setCellValue('I42', $position);   // Driver position
        
        // === OFFICER DETAILS WITH APPROVAL HIERARCHY (NEW) ===
        // H19: the officer one rank above the passenger(s) — e.g. VPAA
        //      for a passenger under Academic Affairs, VPAD for a
        //      passenger under Administration (see
        //      App\Support\ItineraryApproverResolver for the full rule)
        // I45: Unit Head of Transportation Services (lower authority)
        $higherRankOfficer = ['name' => '', 'position' => ''];
        $lowerRankOfficer = ['name' => '', 'position' => ''];

        $approvingEmployee = \App\Support\ItineraryApproverResolver::resolveItineraryApprovers($itinerary)->first();

        if ($approvingEmployee) {
            $higherRankOfficer = [
                'name' => $approvingEmployee->first_name . ' ' . $approvingEmployee->last_name,
                'position' => $approvingEmployee->position && $approvingEmployee->position->position_name
                    ? $approvingEmployee->position->position_name
                    : 'Vice President'
            ];
        } else {
            // Fallback to default if no approver could be resolved
            $higherRankOfficer = [
                'name' => 'Office of the Vice President for Administration',
                'position' => 'Vice President for Administration'
            ];
        }

        // Find the Unit Head of Transportation Services
        $transportationHead = \App\Models\Employee::whereHas('positions', function($query) {
            $query->where('position_name', 'LIKE', '%Head of Transportation Services%');
            $query->where('unit_id', 26); // Transportation Services unit ID
        })->first();
        
        // If not found through positions, try the officers table
        if (!$transportationHead) {
            $transportationHead = \App\Models\Employee::whereHas('officer', function($query) {
                $query->where('unit_head', true);
            })->whereHas('positions', function($query) {
                $query->where('unit_id', 26); // Transportation Services unit ID
            })->first();
        }
        
        // If still not found, try to find unit head of transportation services unit
        if (!$transportationHead) {
            $transportationHead = \App\Models\Employee::whereHas('positions', function($query) {
                $query->where('is_unit_head', true);
                $query->where('unit_id', 26); // Transportation Services unit ID
            })->first();
        }
        
        if ($transportationHead) {
            $lowerRankOfficer = [
                'name' => $transportationHead->first_name . ' ' . $transportationHead->last_name,
                'position' => $transportationHead->position && $transportationHead->position->position_name ? $transportationHead->position->position_name : 'Head of Transportation Services'
            ];
        } else {
            // Fallback to default if no specific transportation head found
            $lowerRankOfficer = [
                'name' => 'Head of Transportation Services',
                'position' => 'Head of Transportation Services'
            ];
        }
        
        // Set the officers in the correct cells
        // Higher rank officer in H19 (approving authority) and H20 (position)
        $sheet->setCellValue('H19', $higherRankOfficer['name']);  // Higher rank approving officer name
        $sheet->setCellValue('H20', $higherRankOfficer['position']);  // Higher rank approving officer position
        
        // Lower rank officer in I45 (approving authority) and I46 (position)
        $sheet->setCellValue('I45', $lowerRankOfficer['name']);  // Lower rank approving officer name
        $sheet->setCellValue('I46', $lowerRankOfficer['position']);  // Lower rank approving officer position
        
        // === STATUS STAMPS WITH TIMESTAMPS (Philippine Time) ===
        // Check if itinerary is cancelled first
        if ($itinerary->status === 'Cancelled' && $itinerary->updated_at) {
            $cancelledTimestamp = Carbon::parse($itinerary->updated_at)->timezone('Asia/Manila')->format('M j, Y g:i A');
            // Display CANCELLED stamp in both approval positions
            $sheet->setCellValue('H17', 'CANCELLED ' . $cancelledTimestamp);
            $sheet->setCellValue('I43', 'CANCELLED ' . $cancelledTimestamp);
            
            // Add cancellation reason if available
            if ($itinerary->cancellation_reason) {
                $sheet->setCellValue('H18', 'Reason: ' . substr($itinerary->cancellation_reason, 0, 50));
                if (strlen($itinerary->cancellation_reason) > 50) {
                    $sheet->setCellValue('H19', substr($itinerary->cancellation_reason, 50, 50));
                }
            }
        } else {
            // H17: VP Approval Status with timestamp (Philippine Time)
            // Only show status if VP has taken action (approved or declined/cancelled)
            if ($itinerary->vp_approved_at) {
                $vpTimestamp = $itinerary->vp_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
                if ($itinerary->vp_approved) {
                    $sheet->setCellValue('H17', 'APPROVED ' . $vpTimestamp);
                } else {
                    // Use CANCELLED to match Travel Order and Trip Ticket PDFs
                    $sheet->setCellValue('H17', 'CANCELLED ' . $vpTimestamp);
                }
            }
            // Leave H17 empty if no action taken yet
            
            // I43: Unit Head Approval Status with timestamp (Philippine Time)
            // Only show status if Unit Head has taken action (approved or declined/cancelled)
            if ($itinerary->unit_head_approved_at) {
                $unitHeadTimestamp = $itinerary->unit_head_approved_at->timezone('Asia/Manila')->format('M j, Y g:i A');
                if ($itinerary->unit_head_approved) {
                    $sheet->setCellValue('I43', 'APPROVED ' . $unitHeadTimestamp);
                } else {
                    // Use CANCELLED to match Travel Order and Trip Ticket PDFs
                    $sheet->setCellValue('I43', 'CANCELLED ' . $unitHeadTimestamp);
                }
            }
            // Leave I43 empty if no action taken yet
        }
        
        // === DATE DETAILS ===
        $sheet->setCellValue('B34', $date_from);
        $sheet->setCellValue('B35', $date_to);
        
        // Save Excel temporarily and return it
        $excelTemp = tempnam(sys_get_temp_dir(), 'itinerary_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($excelTemp);
        
        // Return Excel response for preview
        return response(file_get_contents($excelTemp))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="Itinerary_' . $itinerary->id . '.xlsx"')
            ->header('Cache-Control', 'max-age=0')
            ->header('Pragma', 'public')
            ->header('Expires', '0');
    }
    
    /**
     * Get the creator (employee) of an itinerary for modal display
     */
    public function getCreator($id): \Illuminate\Http\JsonResponse
    {
        $itinerary = Itinerary::find($id);
        
        if (!$itinerary) {
            return response()->json(['error' => 'Itinerary not found'], 404);
        }
        
        $creatorName = '';
        
        // Try to get the employee from the travel order associated with this itinerary
        // Since travelOrder is now an accessor, we need to manually load the employee
        $travelOrder = $itinerary->travelOrder;
        if ($travelOrder && $travelOrder->employee) {
            $creatorName = $travelOrder->employee->first_name . ' ' . $travelOrder->employee->last_name;
        }
        
        return response()->json([
            'creator_name' => $creatorName
        ]);
    }
    
    /**
     * Get all passengers (employees) from all travel orders in an itinerary
     */
    public function getPassengers($id): \Illuminate\Http\JsonResponse
    {
        $itinerary = Itinerary::find($id);
        
        if (!$itinerary) {
            return response()->json(['error' => 'Itinerary not found'], 404);
        }
        
        // Get all travel orders associated with this itinerary
        $travelOrders = $itinerary->travelOrders()->get(); // Call as method and get results
        
        \Log::info('GetPassengers - Travel order IDs from model: ', ['ids' => $itinerary->travel_order_id]);
        \Log::info('GetPassengers - Found travel orders count: ' . count($travelOrders));
        \Log::info('GetPassengers - Found travel order IDs: ', ['ids' => $travelOrders->pluck('id')->toArray()]);
        
        $passengers = [];
        
        foreach ($travelOrders as $travelOrder) {
            \Log::info('GetPassengers - Processing travel order ID: ' . $travelOrder->id);
            if ($travelOrder && $travelOrder->employee) {
                \Log::info('GetPassengers - Employee found for TO ' . $travelOrder->id . ': ' . $travelOrder->employee->full_name);
                $passengers[] = [
                    'id' => $travelOrder->employee->id,
                    'name' => $travelOrder->employee->full_name ?? ($travelOrder->employee->first_name . ' ' . $travelOrder->employee->last_name),
                    'position' => $travelOrder->employee->position ? $travelOrder->employee->position->position_name : 'N/A',
                    'office' => $travelOrder->employee->office ? $travelOrder->employee->office->office_name : 'N/A',
                    'travel_order_id' => $travelOrder->id  // Include travel order ID
                ];
            } else {
                \Log::warning('GetPassengers - No employee for travel order ID: ' . ($travelOrder->id ?? 'null'));
            }
        }
        
        \Log::info('GetPassengers - Passengers count: ' . count($passengers));
        \Log::info('GetPassengers - Passengers: ', ['passengers' => $passengers]);
        
        return response()->json([
            'success' => true,
            'passengers' => $passengers
        ]);
    }
    
    /**
     * Get travel orders for an itinerary (for Edit Trip Ticket modal).
     */
    public function getTravelOrders($id): \Illuminate\Http\JsonResponse
    {
        $itinerary = Itinerary::find($id);
        
        if (!$itinerary) {
            return response()->json(['error' => 'Itinerary not found'], 404);
        }
        
        // Get all travel orders associated with this itinerary
        $travelOrders = $itinerary->getAllTravelOrders();
        
        $formattedTravelOrders = [];
        foreach ($travelOrders as $to) {
            if ($to && $to->employee) {
                $formattedTravelOrders[] = [
                    'id' => $to->id,
                    'employee_id' => $to->employee_id,
                    'employee_name' => $to->employee->full_name ?? ($to->employee->first_name . ' ' . $to->employee->last_name),
                    'destination' => $to->destination ?? 'No destination',
                    'date_from' => $to->date_from,
                    'date_to' => $to->date_to,
                    'departure_time' => $to->departure_time,
                ];
            }
        }
        
        return response()->json(['travel_orders' => $formattedTravelOrders]);
    }
}