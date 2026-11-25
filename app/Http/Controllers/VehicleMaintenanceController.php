<?php

namespace App\Http\Controllers;

use App\Models\VehicleMaintenance;
use App\Models\Vehicle;
use App\Models\DismissedOilChangeNotification;
use App\Models\DismissedMaintenanceNotification;
use App\Models\NotificationRead;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Storage;

class VehicleMaintenanceController extends Controller
{
    /**
     * Display a listing of the maintenance records.
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $vehicleId = $request->get('vehicle_id');
        
        // Get all vehicles for the filter dropdown
        $vehicles = Vehicle::orderBy('plate_number')->get();
        
        // Build the query
        $query = VehicleMaintenance::with('vehicle')->orderBy('created_at', 'desc');
        
        // Apply filters
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('make_or_type', 'LIKE', "%{$search}%")
                         ->orWhere('person_office_unit', 'LIKE', "%{$search}%")
                         ->orWhere('place', 'LIKE', "%{$search}%")
                         ->orWhere('nature_of_work', 'LIKE', "%{$search}%")
                         ->orWhere('conforme', 'LIKE', "%{$search}%")
                         ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                             $vehicleQuery->where('plate_number', 'LIKE', "%{$search}%")
                                          ->orWhere('model', 'LIKE', "%{$search}%");
                         });
            });
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        }
        
        $maintenanceRecords = $query->paginate(10)->appends($request->except('page'));
        
        return view('vehicle-maintenance.index', compact('maintenanceRecords', 'vehicles', 'vehicleId', 'status', 'search'));
    }

    /**
     * Show the form for creating a new maintenance record.
     */
    public function create(): View
    {
        $vehicles = Vehicle::orderBy('plate_number')->get();
        return view('vehicle-maintenance.create', compact('vehicles'));
    }

    /**
     * Store a newly created maintenance record in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'date_started' => 'required|date',
            'make_or_type' => 'required|string|max:255',
            'person_office_unit' => 'required|string|max:255',
            'place' => 'required|string|max:255',
            'nature_of_work' => 'required|string',
            'remarks' => 'nullable|string|max:2000',
            'oil_interval_km' => 'nullable|in:5000,10000',
            'oil_interval_months' => 'nullable|in:3,6',
            'materials_parts.*' => 'nullable|string|max:255',
            'mechanic_assigned.*' => 'required|string|max:255',
        ]);

        // Process materials/parts array
        $materials = array_filter($request->input('materials_parts', []));
        
        // Process mechanics array
        $mechanics = array_filter($request->input('mechanic_assigned', []));
        
        // Determine status based on date_started
        $dateStarted = \Carbon\Carbon::parse($request->date_started);
        $today = \Carbon\Carbon::today();
        
        if ($dateStarted->lte($today)) {
            $status = 'Need Maintenance'; // Past or today = Need Maintenance
        } else {
            $status = 'Pending'; // Tomorrow or future = Pending
        }
        
        VehicleMaintenance::create([
            'vehicle_id' => $request->vehicle_id,
            'remarks' => $request->remarks,
            'oil_interval_km' => $request->oil_interval_km,
            'oil_interval_months' => $request->oil_interval_months,
            'date_started' => $request->date_started,
            'make_or_type' => $request->make_or_type,
            'person_office_unit' => $request->person_office_unit,
            'place' => $request->place,
            'nature_of_work' => $request->nature_of_work,
            'materials_parts' => $materials,
            'mechanic_assigned' => $mechanics,
            'date_completed' => null,
            'conforme' => '', 
            'status' => $status,
        ]);

 return redirect()->route('vehicles.maintenance', $request->vehicle_id)
            ->with('success', 'Vehicle maintenance record created successfully.');
    }

    /**
     * Display the specified maintenance record.
     */
    public function show(VehicleMaintenance $vehicleMaintenance): View
    {
        $vehicleMaintenance->load('vehicle');
       return view('vehicle-maintenance.show', compact('vehicleMaintenance'));
    }

    /**
     * Get the specified maintenance record as JSON for API.
     */
    public function apiShow(VehicleMaintenance $vehicleMaintenance)
    {
        $vehicleMaintenance->load('vehicle');
        
        // Format dates properly for the edit modal
        $data = $vehicleMaintenance->toArray();
        $data['date_started'] = $vehicleMaintenance->date_started->format('Y-m-d');
        if ($vehicleMaintenance->date_completed) {
            $data['date_completed'] = $vehicleMaintenance->date_completed->format('Y-m-d');
        }
        
       return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Show the form for editing the specified maintenance record.
     */
    public function edit(VehicleMaintenance $vehicleMaintenance): View
    {
        $vehicleMaintenance->load('vehicle');
        $vehicles = Vehicle::orderBy('plate_number')->get();
        return view('vehicle-maintenance.edit', compact('vehicleMaintenance', 'vehicles'));
    }

    /**
     * Update the specified maintenance record in storage.
     */
    public function update(Request $request, VehicleMaintenance $vehicleMaintenance): JsonResponse|RedirectResponse
    {
        // Filter out empty mechanic names before validation
        $mechanics = $request->input('mechanic_assigned', []);
        $filteredMechanics = array_filter($mechanics, function($value) {
            return !is_null($value) && trim($value) !== '';
        });
        $request->merge(['mechanic_assigned' => $filteredMechanics]);
        
        // Filter out empty materials before validation
        $materials = $request->input('materials_parts', []);
        $filteredMaterials = array_filter($materials, function($value) {
            return !is_null($value) && trim($value) !== '';
        });
        $request->merge(['materials_parts' => $filteredMaterials]);
        
        // Log incoming data for debugging
        \Log::info('Update Maintenance - Incoming Data:', [
            'mechanic_assigned' => $request->input('mechanic_assigned'),
            'materials_parts' => $request->input('materials_parts'),
            'conforme' => $request->input('conforme'),
            'status' => $request->input('status'),
        ]);
        
        $validator = \Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'date_started' => 'required|date',
            'make_or_type' => 'required|string|max:255',
            'person_office_unit' => 'required|string|max:255',
            'place' => 'required|string|max:255',
            'nature_of_work' => 'required|string',
            'materials_parts.*' => 'nullable|string|max:255',
            'mechanic_assigned.*' => 'required|string|max:255',
            'date_completed' => 'nullable|date',
            'conforme' => 'nullable|string|max:255',
            'status' => 'required|in:Pending,Need Maintenance,Under Maintenance,Ongoing,Completed,Archived',
        ]);
        
        if ($validator->fails()) {
            \Log::error('Update Maintenance - Validation Failed:', [
                'errors' => $validator->errors()->toArray()
            ]);
            
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Process materials/parts array
            $materials = array_filter($request->input('materials_parts', []));
            
            // Process mechanics array
            $mechanics = array_filter($request->input('mechanic_assigned', []));
            
            $oldStatus = $vehicleMaintenance->status;
            
            $vehicleMaintenance->update([
                'vehicle_id' => $request->vehicle_id,
                'date_started' => $request->date_started,
                'make_or_type' => $request->make_or_type,
                'person_office_unit' => $request->person_office_unit,
                'place' => $request->place,
                'nature_of_work' => $request->nature_of_work,
                'materials_parts' => $materials,
                'mechanic_assigned' => $mechanics,
                'date_completed' => $request->date_completed,
                'conforme' => $request->conforme,
                'status' => $request->status,
            ]);
        } catch (\Exception $e) {
            \Log::error('Update Maintenance - Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating the maintenance record: ' . $e->getMessage(),
                ], 500);
            }
            
            throw $e;
        }
        
        // Log successful update
        \Log::info('Maintenance Updated Successfully:', [
            'id' => $vehicleMaintenance->id,
            'status' => $vehicleMaintenance->status,
            'nature_of_work' => $request->nature_of_work,
        ]);

        // Return JSON response for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Vehicle maintenance record updated successfully.',
                'data' => [
                    'id' => $vehicleMaintenance->id,
                    'vehicle_id' => $vehicleMaintenance->vehicle_id,
                    'redirect' => route('vehicles.maintenance', $vehicleMaintenance->vehicle_id)
                ]
            ]);
        }

        return redirect()->route('vehicles.maintenance', $vehicleMaintenance->vehicle_id)
            ->with('success', 'Vehicle maintenance record updated successfully.');
    }

    /**
     * Archive the specified maintenance record (change status to Archived and set archived flag).
     */
    public function destroy(VehicleMaintenance $vehicleMaintenance): RedirectResponse
    {
        $vehicleId = $vehicleMaintenance->vehicle_id;
        
        $vehicleMaintenance->update([
            'status' => 'Archived',
            'archived' => true,
        ]);

        return redirect()->route('vehicles.maintenance', $vehicleId)
            ->with('success', 'Maintenance record archived successfully.');
    }
    
    /**
     * Dedicated admin page: everything that needs maintenance attention fleet-wide.
     * Combines vehicles overdue for oil change (mileage-based) with open
     * maintenance records (status Need Maintenance / Under Maintenance).
     */
    public function needed(Request $request): View
    {
        // Vehicles overdue for oil change (not filtered by dismissal here —
        // this page is the admin's full record, dismissal only hides the bell/badge)
        $oilChangeVehicles = Vehicle::all()
            ->filter(fn (Vehicle $vehicle) => $vehicle->needsOilChange())
            ->values();

        // Open maintenance work orders
        $maintenanceRecords = VehicleMaintenance::with('vehicle')
            ->whereIn('status', ['Need Maintenance', 'Under Maintenance'])
            ->orderBy('date_started', 'asc')
            ->get();

        // Which oil-change vehicles / work orders the current admin has removed from the bell
        $dismissedVehicleIds = DismissedOilChangeNotification::where('user_id', auth()->id())
            ->pluck('vehicle_id')
            ->toArray();

        $dismissedMaintenanceIds = DismissedMaintenanceNotification::where('user_id', auth()->id())
            ->pluck('vehicle_maintenance_id')
            ->toArray();

        return view('vehicle-maintenance.needed', compact(
            'oilChangeVehicles',
            'maintenanceRecords',
            'dismissedVehicleIds',
            'dismissedMaintenanceIds'
        ));
    }

    /**
     * API for the header notification bell: lightweight combined feed of
     * oil-change-due vehicles (excluding ones this admin dismissed) and
     * open maintenance records.
     */
    public function notifications(): JsonResponse
    {
        $readOil = NotificationRead::where('user_id', auth()->id())
            ->where('type', 'oil_change')->pluck('ref_id')->toArray();
        $readMaint = NotificationRead::where('user_id', auth()->id())
            ->where('type', 'maintenance_record')->pluck('ref_id')->toArray();

        $dismissedVehicleIds = DismissedOilChangeNotification::where('user_id', auth()->id())
            ->pluck('vehicle_id')
            ->toArray();

        $oilChange = Vehicle::all()
            ->filter(function (Vehicle $vehicle) use ($dismissedVehicleIds) {
                if (in_array($vehicle->id, $dismissedVehicleIds)) {
                    return false;
                }
                return $vehicle->needsOilChange();
            })
            ->values()
            ->map(function (Vehicle $vehicle) use ($readOil) {
                return [
                    'type' => 'oil_change',
                    'read' => in_array($vehicle->id, $readOil),
                    'vehicle_id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'model' => $vehicle->model,
                    'mileage' => $vehicle->mileage,
                    'kilometers_since_service' => $vehicle->getKilometersSinceLastMaintenance(),
                    'url' => route('vehicles.maintenance', $vehicle->id),
                ];
            });

        $dismissedMaintenanceIds = DismissedMaintenanceNotification::where('user_id', auth()->id())
            ->pluck('vehicle_maintenance_id')
            ->toArray();

        $maintenance = VehicleMaintenance::with('vehicle')
            ->whereIn('status', ['Need Maintenance', 'Under Maintenance'])
            ->whereNotIn('id', $dismissedMaintenanceIds)
            ->orderBy('date_started', 'asc')
            ->get()
            ->map(function (VehicleMaintenance $record) use ($readMaint) {
                return [
                    'type' => 'maintenance_record',
                    'read' => in_array($record->id, $readMaint),
                    'id' => $record->id,
                    'vehicle_id' => $record->vehicle_id,
                    'plate_number' => $record->vehicle->plate_number ?? 'N/A',
                    'nature_of_work' => $record->nature_of_work,
                    'status' => $record->status,
                    'date_started' => $record->date_started?->format('M d, Y'),
                    'url' => route('vehicles.maintenance', $record->vehicle_id),
                ];
            });

        return response()->json([
            'oil_change_count' => $oilChange->count(),
            'maintenance_count' => $maintenance->count(),
            'total_count' => $oilChange->count() + $maintenance->count(),
            'unread_count' => $oilChange->where('read', false)->count() + $maintenance->where('read', false)->count(),
            'oil_change' => $oilChange,
            'maintenance' => $maintenance,
        ]);
    }

    /**
     * Admin marks a notification as read. The item stays in Maintenance Needed,
     * and only the bell count goes down.
     */
    public function markRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:oil_change,maintenance_record',
            'id' => 'required|integer',
        ]);

        NotificationRead::firstOrCreate(
            ['user_id' => auth()->id(), 'type' => $data['type'], 'ref_id' => $data['id']],
            ['read_at' => now()]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Admin removes a work-order notification from their bell / Maintenance Needed.
     * The work order itself is not changed.
     */
    public function dismissNotification(VehicleMaintenance $vehicleMaintenance): JsonResponse
    {
        DismissedMaintenanceNotification::firstOrCreate([
            'user_id' => auth()->id(),
            'vehicle_maintenance_id' => $vehicleMaintenance->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Notification removed']);
    }

    /**
     * Undo a removal so the work order shows up in the bell again.
     */
    public function restoreNotification(VehicleMaintenance $vehicleMaintenance): JsonResponse
    {
        DismissedMaintenanceNotification::where('user_id', auth()->id())
            ->where('vehicle_maintenance_id', $vehicleMaintenance->id)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Notification restored']);
    }

    /**
     * Display maintenance records for a specific vehicle.
     */
    public function maintenanceByVehicle(Request $request, Vehicle $vehicle): View
    {
        // Ensure the vehicle exists and is valid
        if (!$vehicle->exists) {
            abort(404, 'Vehicle not found');
        }
        
        $maintenanceRecords = VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get the active tab from request, default to 'pending'
        $activeTab = $request->query('tab', 'pending');
        
        return view('vehicle-maintenance.for-vehicle', compact('vehicle', 'maintenanceRecords', 'activeTab'));
    }
    
    /**
     * Start maintenance (change status to Under Maintenance).
     */
    public function startMaintenance(VehicleMaintenance $vehicleMaintenance)
    {
        $vehicleId = $vehicleMaintenance->vehicle_id;
        
        $vehicleMaintenance->update([
            'status' => 'Under Maintenance'
        ]);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Maintenance started successfully.',
                'data' => [
                    'id' => $vehicleMaintenance->id,
                    'status' => $vehicleMaintenance->status,
                    'vehicle_id' => $vehicleId
                ]
            ]);
        }

        return redirect()->route('vehicles.maintenance', $vehicleId)
            ->with('success', 'Maintenance started successfully. Status changed to Under Maintenance.');
    }
    
    /**
     * Complete maintenance(change status to Completed).
     */
    public function completeMaintenance(VehicleMaintenance $vehicleMaintenance)
    {
        $vehicleId = $vehicleMaintenance->vehicle_id;
        
        // Validate request
        $validated = request()->validate([
            'date_completed' => 'required|date',
            'conforme' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ], [
            'date_completed.required' => 'Please select a completion date',
        ]);
        
        $vehicleMaintenance->update([
            'status' => 'Completed',
            'date_completed' => $validated['date_completed'],
            'conforme' => $validated['conforme'],
            'position' => $validated['position'] ?? null,
        ]);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Maintenance completed successfully.',
                'data' => [
                    'id' => $vehicleMaintenance->id,
                    'status' => $vehicleMaintenance->status,
                    'date_completed' => $vehicleMaintenance->date_completed,
                    'conforme' => $vehicleMaintenance->conforme,
                    'position' => $vehicleMaintenance->position,
                ]
            ]);
        }

        return redirect()->route('vehicles.maintenance', $vehicleId)
            ->with('success', 'Maintenance completed successfully.');
    }
    
    /**
     * Generate Excel for vehicle maintenance record.
     */
    public function generateExcel(VehicleMaintenance $vehicleMaintenance)
    {
        $templatePath = public_path('templates/maintenance_template.xlsx');
        
        if (!file_exists($templatePath)) {
            return response()->json(['error' => 'Template file not found'], 404);
        }
        
        $spreadsheet = IOFactory::load($templatePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Fill data into the template
        $worksheet->setCellValue('B7', $vehicleMaintenance->date_started->format('F j, Y')); // Date Started
        $worksheet->setCellValue('B8', $vehicleMaintenance->make_or_type); // Make or Type
        $worksheet->setCellValue('B9', $vehicleMaintenance->person_office_unit); // Person/Office/Unit
        $worksheet->setCellValue('B10', $vehicleMaintenance->place); // Place
        $worksheet->setCellValue('B11', $vehicleMaintenance->nature_of_work); // Nature of Work
        
        // Fill Materials/Parts (B21-B30)
        $materials = $vehicleMaintenance->materials_parts;
        if (!empty($materials) && is_array($materials)) {
            for ($i = 0; $i < min(count($materials), 10); $i++) {
                $cell = 'B' . (21 + $i);
                $worksheet->setCellValue($cell, $materials[$i]);
            }
        }
        
        // Fill Mechanics (B32-B35)
        $mechanics = $vehicleMaintenance->mechanic_assigned;
        if (!empty($mechanics) && is_array($mechanics)) {
            for ($i = 0; $i < min(count($mechanics), 4); $i++) {
                $cell = 'B' . (32 + $i);
                $worksheet->setCellValue($cell, $mechanics[$i]);
            }
        }
        
        // Fill Date Completed
        if ($vehicleMaintenance->date_completed) {
            $worksheet->setCellValue('B37', $vehicleMaintenance->date_completed->format('F j, Y'));
        }
        
        // Fill Conforme
        $worksheet->setCellValue('B39', $vehicleMaintenance->conforme);
        
        // Fill Position (assuming this comes from the vehicle's office/unit)
        $worksheet->setCellValue('B40', $vehicleMaintenance->position);
        
        // Save Excel temporarily and return it
        $excelTemp = tempnam(sys_get_temp_dir(), 'maintenance_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($excelTemp);
        
        return response(file_get_contents($excelTemp))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="Vehicle_Maintenance_' . $vehicleMaintenance->id . '.xlsx"')
            ->header('Cache-Control', 'max-age=0')
            ->header('Pragma', 'public')
            ->header('Expires', '0');
    }
    
    /**
     * Generate PDF for all completed maintenance records of a vehicle.
     */
    public function generateCompletedMaintenancePDF(Vehicle $vehicle)
    {
        $completedRecords = VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->where('status', 'Completed')
            ->orderBy('date_completed', 'desc')
            ->get();
        
        if ($completedRecords->isEmpty()) {
            return response()->json(['error' => 'No completed maintenance records found'], 404);
        }
        
        try {
            // Use MPDF to generate PDF
            $html = view('vehicle-maintenance.pdf.completed-maintenance', [
                'vehicle' => $vehicle,
                'records' => $completedRecords
            ])->render();
            
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => [210, 330],
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 25,
                'margin_bottom' => 20,
            ]);
            
            $mpdf->WriteHTML($html);
            $pdfContent = $mpdf->Output('', 'S');
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="completed_maintenance_' . $vehicle->plate_number. '.pdf"');
                
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'PDF generation failed.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}