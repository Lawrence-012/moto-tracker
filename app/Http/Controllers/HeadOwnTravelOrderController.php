<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\TravelOrder;
use App\Models\Employee;

class HeadOwnTravelOrderController extends Controller
{
    /**
     * Display a listing of the head's own travel orders.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        // Get the tab parameter, default to 'pending'
        $tab = $request->get('tab', 'pending');
        
        // Get search term if provided
        $search = $request->get('search', '');
        
        // Get travel orders for this employee based on the selected tab
        $query = TravelOrder::where('employee_id', $employee->id);
        
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
                  });
            });
        }
        
        switch ($tab) {
            case 'approved':
                // For heads, approved means both head and VP have approved
                $query->where('head_approved', true)->where('vp_approved', true);
                break;
            case 'cancelled':
                // For heads, cancelled means either head or VP rejected
                $query->where(function ($q) {
                    $q->where('head_approved', false)
                      ->orWhere('vp_approved', false);
                });
                break;
            case 'pending':
            default:
                // For heads, pending means either:
                // 1. Not yet approved by division head, or
                // 2. Approved by division head but not yet approved by VP
                $query->where(function ($q) {
                    $q->where('head_approved', null)
                      ->orWhere(function ($q2) {
                          $q2->where('head_approved', true)
                             ->where('vp_approved', null);
                      });
                });
                break;
        }
        
        $travelOrders = $query->with('position')->orderBy('created_at', 'desc')->paginate(10)->appends(['tab' => $tab, 'search' => $search]);
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'table_body' => view('travel-orders.partials.' . ($tab === 'draft' ? 'draft-rows' : 'table-rows'), [
                    'travelOrders' => $travelOrders,
                    'tab' => $tab
                ])->render(),
                'pagination' => $travelOrders->links()->toHtml(),
            ]);
        }
        
        // Get all positions for the employee (for the create modal)
        $positions = $employee->positions()->with(['office', 'division', 'unit', 'subunit', 'class'])->get();
        
        return view('travel-orders.index', compact('travelOrders', 'tab', 'search', 'positions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        if (!$employee) {
            abort(403, 'You do not have an employee record.');
        }
        
        // Get all positions for the employee
        $positions = $employee->positions()->with(['office', 'division', 'unit', 'subunit', 'class'])->get();
        
        return view('travel-orders.create', compact('positions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'destination' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'region' => 'nullable|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'date_from' => 'required|date|before_or_equal:date_to',
            'date_to' => 'required|date|after_or_equal:date_from',
            'departure_time' => 'nullable|string|max:20', // Increased max length to accommodate any format
            'purpose' => 'required|string|max:500',
            'position_id' => 'required|exists:emp_positions,id', // Add validation for position selection
        ]);

        $user = Auth::user();
        $employee = $user->employee;
        
        // Check if employee exists
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have an employee record. Please contact your administrator to set up your employee profile.');
        }

        // Verify that the selected position belongs to the employee
        $position = $employee->positions()->where('id', $request->position_id)->first();
        if (!$position) {
            return redirect()->back()
                ->with('error', 'Invalid position selected.')
                ->withInput();
        }

        // Process departure_time to ensure it's in the correct format or null
        $departureTime = null;
        if (!empty($request->departure_time)) {
            // Trim whitespace and normalize the value
            $cleanTime = trim($request->departure_time);
            
            // Try to extract time in H:i format using regex
            if (preg_match('/([01]?[0-9]|2[0-3]):([0-5][0-9])/', $cleanTime, $matches)) {
                $departureTime = $matches[1] . ':' . $matches[2];
            } elseif (preg_match('/([01]?[0-9]|2[0-3])[.:]?([0-5][0-9])/', $cleanTime, $matches)) {
                // Handle cases where it might be formatted differently
                $departureTime = sprintf('%02d:%02d', $matches[1], $matches[2]);
            }
        }

        // Create the travel order
        $travelOrder = TravelOrder::create([
            'employee_id' => $employee->id,
            'emp_position_id' => $request->position_id, // Associate with the selected position
            'destination' => $request->destination,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'region' => $request->region,
            'distance_km' => $request->distance_km,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'departure_time' => $departureTime,
            'purpose' => $request->purpose,
            'status' => 'pending',
        ]);

        return redirect()->route('head.travel-orders.index', ['tab' => 'pending'])
            ->with('success', 'Travel order created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TravelOrder $travelOrder): View
    {
        // Ensure the head can only view their own travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        return view('travel-orders.show', compact('travelOrder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TravelOrder $travelOrder): View
    {
        // Ensure the head can only edit their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow editing if not yet approved by division head or VP
        if (!is_null($travelOrder->head_approved) || !is_null($travelOrder->vp_approved)) {
            abort(403);
        }
        
        return view('travel-orders.edit', compact('travelOrder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        // Ensure the head can only update their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow updating if not yet approved by division head or VP
        if (!is_null($travelOrder->head_approved) || !is_null($travelOrder->vp_approved)) {
            abort(403);
        }
        
        $request->validate([
            'destination' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'region' => 'nullable|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'date_from' => 'required|date|before_or_equal:date_to',
            'date_to' => 'required|date|after_or_equal:date_from',
            'departure_time' => 'nullable|string|max:20', // Increased max length to accommodate any format
            'purpose' => 'required|string|max:500',
        ]);

        // Process departure_time to ensure it's in the correct format or null
        $departureTime = null;
        if (!empty($request->departure_time)) {
            // Trim whitespace and normalize the value
            $cleanTime = trim($request->departure_time);
            
            // Try to extract time in H:i format using regex
            if (preg_match('/([01]?[0-9]|2[0-3]):([0-5][0-9])/', $cleanTime, $matches)) {
                $departureTime = $matches[1] . ':' . $matches[2];
            } elseif (preg_match('/([01]?[0-9]|2[0-3])[.:]?([0-5][0-9])/', $cleanTime, $matches)) {
                // Handle cases where it might be formatted differently
                $departureTime = sprintf('%02d:%02d', $matches[1], $matches[2]);
            }
        }

        $travelOrder->update([
            'destination' => $request->destination,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'region' => $request->region,
            'distance_km' => $request->distance_km,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'departure_time' => $departureTime,
            'purpose' => $request->purpose,
        ]);

        return redirect()->route('head.travel-orders.index', ['tab' => 'pending'])
            ->with('success', 'Travel order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TravelOrder $travelOrder): RedirectResponse
    {
        // Ensure the head can only delete their own pending travel orders
        $user = Auth::user();
        $employee = $user->employee;
        
        if ($travelOrder->employee_id !== $employee->id) {
            abort(403);
        }
        
        // Only allow deleting if not yet approved by division head or VP
        if (!is_null($travelOrder->head_approved) || !is_null($travelOrder->vp_approved)) {
            abort(403);
        }
        
        $travelOrder->delete();

        return redirect()->route('head.travel-orders.index', ['tab' => 'pending'])
            ->with('success', 'Travel order deleted successfully.');
    }
}