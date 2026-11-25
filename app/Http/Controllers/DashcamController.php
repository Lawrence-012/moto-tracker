<?php

namespace App\Http\Controllers;

use App\Models\Dashcam;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Motorpool admin: register dashcams that checked in from the Android app.
 * The device creates its own code/QR. The admin scans or types that code and
 * links it to a vehicle. Rule: one dashcam per vehicle, one vehicle per dashcam.
 */
class DashcamController extends Controller
{
    public function index(): View
    {
        $pending = Dashcam::whereNull('vehicle_id')
            ->orderByDesc('first_seen_at')
            ->get();

        $registered = Dashcam::with('vehicle')
            ->whereNotNull('vehicle_id')
            ->get()
            ->sortBy(fn (Dashcam $d) => $d->vehicle->plate_number)
            ->values();

        $availableVehicles = Vehicle::whereDoesntHave('dashcam')
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'model']);

        return view('motorpool-admin.dashcams.index', compact('pending', 'registered', 'availableVehicles'));
    }

    /**
     * Link a pending dashcam (identified by its code) to a vehicle.
     */
    public function register(Request $request): RedirectResponse
    {
        $code = Dashcam::normalizeCode($request->input('code'));

        if (!$code) {
            return back()->withInput()->withErrors(['code' => 'Invalid code. It should look like DC-7K2M-9QXA.']);
        }

        $dashcam = Dashcam::where('code', $code)->first();

        if (!$dashcam) {
            return back()->withInput()->withErrors([
                'code' => 'No dashcam with this code. Open the dashcam app first so it checks in with the server.',
            ]);
        }

        if ($dashcam->isRegistered()) {
            return back()->withErrors(['code' => 'This dashcam is already registered to a vehicle.']);
        }

        // One dashcam per vehicle: refuse if the chosen vehicle already has one.
        $chosenVehicle = (int) $request->input('vehicle_id');
        if ($chosenVehicle && Dashcam::where('vehicle_id', $chosenVehicle)->where('id', '!=', $dashcam->id)->exists()) {
            return back()->withInput()->withErrors([
                'vehicle_id' => 'This vehicle already has a dashcam. Unlink the current dashcam first, then register the new one.',
            ]);
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id|unique:dashcams,vehicle_id',
            'odometer_km' => 'nullable|integer|min:0|max:9999999',
        ], [
            'vehicle_id.unique' => 'This vehicle already has a dashcam registered.',
        ]);

        // Starting odometer: what the admin typed, otherwise the vehicle's stored mileage.
        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        $baseline = $validated['odometer_km'] ?? (int) ($vehicle->mileage ?? 0);

        if ($validated['odometer_km'] !== null && $validated['odometer_km'] > (int) ($vehicle->mileage ?? 0)) {
            $vehicle->mileage = $validated['odometer_km'];
            $vehicle->save();
        }

        $dashcam->update([
            'vehicle_id' => $vehicle->id,
            'registered_by' => auth()->id(),
            'registered_at' => now(),
            'odometer_baseline_km' => $baseline,
            'distance_km' => 0,
            // Start a fresh trail: the next fix must not be joined to an old position.
            'last_latitude' => null,
            'last_longitude' => null,
            'last_seen_at' => null,
        ]);

        return redirect()->route('motorpool.dashcams.index')
            ->with('success', 'Dashcam registered to the vehicle.');
    }

    /**
     * Unlink a dashcam from its vehicle. It goes back to pending and can be registered again.
     */
    public function unregister(Dashcam $dashcam): RedirectResponse
    {
        $plate = $dashcam->vehicle?->plate_number ?? 'vehicle';

        $dashcam->update([
            'vehicle_id' => null,
            'registered_by' => null,
            'registered_at' => null,
        ]);

        return redirect()->route('motorpool.dashcams.index')
            ->with('success', "Dashcam unlinked from {$plate}.");
    }

    public function destroy(Dashcam $dashcam): RedirectResponse
    {
        $dashcam->delete();

        return redirect()->route('motorpool.dashcams.index')
            ->with('success', 'Dashcam removed.');
    }

    /**
     * Download a PDF listing of registered dashcams: Vehicle, Dashcam Code, QR.
     */
    public function printRegistered()
    {
        $registered = Dashcam::with('vehicle')
            ->whereNotNull('vehicle_id')
            ->get()
            ->sortBy(fn (Dashcam $d) => $d->vehicle->plate_number)
            ->values();

        return Pdf::loadView('motorpool-admin.dashcams.registered-pdf', compact('registered'))
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->download('registered-dashcams-' . now()->format('Ymd') . '.pdf');
    }
}
