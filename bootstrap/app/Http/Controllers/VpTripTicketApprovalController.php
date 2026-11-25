<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\TripTicket;
use App\Models\Employee;

class VpTripTicketApprovalController extends Controller
{
    /**
     * Display pending trip tickets for VP approval in motorpool dashboard.
     */
    public function vpPending(Request $request)
    {
        $user = Auth::user();
        $tab = $request->get('tab', 'pending'); // Default to pending tab
        $search = $request->get('search', '');
        
        // Check if user is authorized (VP, admin, or motorpool admin)
        if (!$user->isVp() && !$user->isAdmin() && !$user->isMotorpoolAdmin()) {
            abort(403, 'Unauthorized to view VP trip ticket approvals');
        }
        
        // Additional check: Only VPs from Office of the Vice President for Administration can access
        if ($user->isVp() && !$user->isAdmin() && !$user->isMotorpoolAdmin()) {
            $isVpOfAdministration = $user->employee->positions()->whereHas('office', function($query) {
                $query->where('office_name', 'Office of the Vice President for Administration');
            })->exists();
            
            if (!$isVpOfAdministration) {
                abort(403, 'Only VP of Office of the Vice President for Administration can approve trip tickets');
            }
        }
        
        // Base query
        $query = TripTicket::with(['itinerary.driver', 'itinerary.vehicle']);
        
        // Apply search filter if provided
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereHas('itinerary', function ($itineraryQuery) use ($search) {
                    $itineraryQuery->where('destination', 'LIKE', "%{$search}%")
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
                                   ->orWhereHas('driver', function($driverQuery) use ($search) {
                                       $driverQuery->where('first_name', 'LIKE', "%{$search}%")
                                                   ->orWhere('last_name', 'LIKE', "%{$search}%");
                                   });
                });
            });
        }
            
        // Apply tab filtering
        switch ($tab) {
            case 'approved':
                // Show all trip tickets that have been approved by VP (Approved, On-Going, Completed)
                $query->whereIn('status', ['Approved', 'On-Going', 'Completed']);
                break;
            case 'cancelled':
                $query->where('status', 'Cancelled');
                break;
            case 'pending':
            default:
                $query->where('status', 'Pending');
                break;
        }
        
        $tripTickets = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // Get counts for each tab - include On-Going and Completed in approved count
        $pendingCount = TripTicket::where('status', 'Pending')->count();
        $approvedCount = TripTicket::whereIn('status', ['Approved', 'On-Going', 'Completed'])->count();
        $cancelledCount = TripTicket::where('status', 'Cancelled')->count();
            
        return view('trip-tickets.approvals.vp-pending', compact('tripTickets', 'tab', 'pendingCount', 'approvedCount', 'cancelledCount', 'search'));
    }
    
    /**
     * Display a listing of trip tickets for VP approval with tab support.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Get the tab parameter, default to 'pending'
        $tab = $request->get('tab', 'pending');
        
        // Get search term if provided
        $search = $request->get('search', '');
        
        // Build query based on the selected tab
        $query = TripTicket::with(['itinerary.driver', 'itinerary.vehicle']);
        
        // For VPs, show all pending trip tickets that need approval
        // No office-based filtering for trip tickets
        
        // Apply search filter if provided
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereHas('itinerary', function ($itineraryQuery) use ($search) {
                    $itineraryQuery->where('destination', 'LIKE', "%{$search}%")
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
                                   ->orWhereHas('driver', function($driverQuery) use ($search) {
                                       $driverQuery->where('first_name', 'LIKE', "%{$search}%")
                                                   ->orWhere('last_name', 'LIKE', "%{$search}%");
                                   });
                });
            });
        }
        
        switch ($tab) {
            case 'approved':
                // Trip tickets that have been approved by VP
                $query->whereIn('status', ['Approved', 'Completed']);
                break;
            case 'cancelled':
                // Cancelled trip tickets
                $query->whereIn('status', ['Cancelled']);
                break;
            case 'pending':
            default:
                // Pending trip tickets that need final approval. Only tickets
                // whose itinerary has completed both approval stages belong here.
                $query->where('status', 'Pending')
                      ->whereHas('itinerary', function ($itineraryQuery) {
                          $itineraryQuery->where('status', 'Approved')
                              ->where('unit_head_approved', true)
                              ->where('vp_approved', true)
                              ->whereNotNull('unit_head_approved_at')
                              ->whereNotNull('vp_approved_at');
                      });
                break;
        }
        
        $tripTickets = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return view('trip-tickets.vp-approvals.index', compact('tripTickets', 'tab', 'search'));
    }
    
    /**
     * Approve a trip ticket (change status to Approved).
     */
    public function approve(TripTicket $tripTicket)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Ensure the user is a VP
        if (!$employee->is_vp) {
            abort(403);
        }
        
        // Additional check: Only VPs from Office of the Vice President for Administration can approve
        $isVpOfAdministration = $employee->positions()->whereHas('office', function($query) {
            $query->where('office_name', 'Office of the Vice President for Administration');
        })->exists();
        
        if (!$isVpOfAdministration) {
            abort(403, 'Only VP of Office of the Vice President for Administration can approve trip tickets');
        }
        
        // Only allow approving if status is currently Pending
        if ($tripTicket->status !== 'Pending') {
            abort(403);
        }

        // Final Trip Ticket approval must never bypass the itinerary approval
        // chain. The Head Panel/Unit Head and final itinerary approval must
        // already be completed before the Trip Ticket reaches this queue.
        $itinerary = $tripTicket->itinerary;
        if (!$itinerary ||
            $itinerary->status !== 'Approved' ||
            !$itinerary->unit_head_approved ||
            !$itinerary->vp_approved ||
            !$itinerary->unit_head_approved_at ||
            !$itinerary->vp_approved_at) {
            abort(422, 'This Trip Ticket cannot be approved because its itinerary has not completed the required Head Panel/Unit Head and final approval.');
        }
        
        // Update status to Approved
        $tripTicket->update([
            'status' => 'Approved',
        ]);
        
        // Send SMS notification to driver
        $this->sendDriverSmsNotification($tripTicket);
        
        // Return JSON for AJAX requests
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Trip ticket approved successfully.',
                'redirect' => route('motorpool.approvals.triptickets.vp', ['tab' => 'pending'])
            ]);
        }
        
        return redirect()->route('motorpool.approvals.triptickets.vp', ['tab' => 'pending'])
            ->with('success', 'Trip ticket approved successfully.');
    }
    
    /**
     * Reject a trip ticket (change status to Cancelled).
     */
    public function reject(TripTicket $tripTicket, Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Ensure the user is a VP
        if (!$employee->is_vp) {
            abort(403);
        }
        
        // Only allow rejecting if status is currently Pending
        if ($tripTicket->status !== 'Pending') {
            abort(403);
        }
        
        // Update status to Cancelled and save rejection details
        $tripTicket->update([
            'status' => 'Cancelled',
            'rejected_by_id' => $user->id,
            'rejection_reason' => $request->input('remarks'),
        ]);
        
        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Trip ticket rejected successfully.'
            ]);
        }
        
        return redirect()->back()
            ->with('success', 'Trip ticket rejected successfully.');
    }
    
    /**
     * Display the specified trip ticket for VP approval.
     */
    public function show(TripTicket $tripTicket): View
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Ensure the user is a VP
        if (!$employee->is_vp) {
            abort(403);
        }
        
        return view('trip-tickets.vp-approvals.show', compact('tripTicket'));
    }
    
    /**
     * Send SMS notification to driver about approved trip ticket.
     */
    private function sendDriverSmsNotification(TripTicket $tripTicket)
    {
        \Log::info('Starting SMS notification process', ['trip_ticket_id' => $tripTicket->id]);
        
        try {
            // Get itinerary and driver information
            $itinerary = $tripTicket->itinerary;
            if (!$itinerary || !$itinerary->driver) {
                \Log::warning('Trip ticket approval: No itinerary or driver found', [
                    'trip_ticket_id' => $tripTicket->id,
                    'itinerary_exists' => !!$itinerary,
                    'driver_exists' => $itinerary ? !!$itinerary->driver : false
                ]);
                return;
            }
            
            $driver = $itinerary->driver;
            \Log::info('Driver found', [
                'driver_id' => $driver->id,
                'driver_name' => $driver->full_name,
                'contact_num' => $driver->contact_num
            ]);
            
            // Check if driver has a contact number
            if (empty($driver->contact_num)) {
                \Log::warning('Trip ticket approval: Driver has no contact number', [
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
            
            // Get head of party contact number (from travel order employee or user)
            $headContactNum = 'N/A';
            $travelOrders = $itinerary->getAllTravelOrders(); // Use correct method
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
            
            // Build multi-line SMS message for APPROVED status (no truncation to preserve formatting)
            $message = "Trip Approved.\n\n" .
                       "Driver: {$driver->full_name}\n" .
                       "Vehicle: {$vehicleInfo}\n" .
                       "To: {$itinerary->destination}\n" .
                       "Date: {$dateRange}\n" .
                       "Time: {$formattedTime}\n" .
                       "Head of the party: {$headOfParty}\n" .
                       "Head contact no.: {$headContactNum}\n" .
                       "Passengers: {$passengerCount}";
            
            \Log::info('Sending SMS to driver', [
                'recipient' => $driver->contact_num,
                'message_length' => strlen($message),
                'message_preview' => substr($message, 0, 50)
            ]);
            
            // Send via PhilSMS API v3
            $this->sendPhilSms($driver->contact_num, $message);
            
            \Log::info('Driver SMS sent successfully', [
                'driver_id' => $driver->id,
                'driver_contact' => $driver->contact_num,
                'trip_ticket_id' => $tripTicket->id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send driver SMS notification', [
                'trip_ticket_id' => $tripTicket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw exception - SMS failure should not affect approval
        }
    }
    
    /**
     * Send SMS using PhilSMS API v3 with correct format.
     */
    private function sendPhilSms($recipient, $message)
    {
        // PhilSMS API v3 configuration
        $apiKey = env('PHILSMS_API_KEY');
        $senderId = env('PHILSMS_SENDER_ID', 'PHILSMS'); // Use PHILSMS as default sender ID
        
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