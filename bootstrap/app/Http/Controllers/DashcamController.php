<?php

namespace App\Http\Controllers;

use App\Models\Dashcam;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Motorpool admin: register dashcams to vehicles and show their QR / code.
 * Rule: one dashcam per vehicle, one vehicle per dashcam.
 */
class DashcamController extends Controller
{
    public function index(): View
    {
        $dashcams = Dashcam::with('vehicle')
            ->join('vehicles', 'vehicles.id', '=', 'dashcams.vehicle_id')
            ->orderBy('vehicles.plate_number')
            ->select('dashcams.*')
            ->get();

        $availableVehicles = Vehicle::whereDoesntHave('dashcam')
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'model']);

        return view('motorpool-admin.dashcams.index', compact('dashcams', 'availableVehicles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id|unique:dashcams,vehicle_id',
        ], [
            'vehicle_id.unique' => 'This vehicle already has a dashcam registered.',
        ]);

        $dashcam = DB::transaction(fn () => Dashcam::create([
            'vehicle_id' => $validated['vehicle_id'],
            'code' => Dashcam::generateCode(),
            'registered_by' => auth()->id(),
        ]));

        return redirect()->route('motorpool.dashcams.show', $dashcam)
            ->with('success', 'Dashcam registered. Scan the QR or enter the code on the dashcam app.');
    }

    public function show(Dashcam $dashcam): View
    {
        $dashcam->load('vehicle');

        return view('motorpool-admin.dashcams.show', compact('dashcam'));
    }

    /**
     * Issue a new code and QR. The old code stops working immediately.
     */
    public function regenerate(Dashcam $dashcam): RedirectResponse
    {
        $dashcam->update([
            'code' => Dashcam::generateCode(),
            'paired_at' => null,
        ]);

        return redirect()->route('motorpool.dashcams.show', $dashcam)
            ->with('success', 'New code generated. The previous code no longer works.');
    }

    public function destroy(Dashcam $dashcam): RedirectResponse
    {
        $plate = $dashcam->vehicle?->plate_number ?? 'vehicle';
        $dashcam->delete();

        return redirect()->route('motorpool.dashcams.index')
            ->with('success', "Dashcam removed from {$plate}.");
    }
}
