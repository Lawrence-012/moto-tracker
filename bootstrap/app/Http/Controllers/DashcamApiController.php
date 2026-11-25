<?php

namespace App\Http\Controllers;

use App\Models\Dashcam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints used by the dashcam Android app.
 * Auth: the dashcam code, sent in the X-Dashcam-Code header (or "code" in the body).
 */
class DashcamApiController extends Controller
{
    /**
     * Pair a device with its code. Works for both a scanned QR and a manually typed code.
     */
    public function pair(Request $request): JsonResponse
    {
        $dashcam = $this->resolveDashcam($request);

        if (!$dashcam) {
            return response()->json(['success' => false, 'message' => 'Invalid dashcam code.'], 404);
        }

        if (!$dashcam->paired_at) {
            $dashcam->update(['paired_at' => now()]);
        }

        $dashcam->load('vehicle');

        return response()->json([
            'success' => true,
            'dashcam' => [
                'code' => $dashcam->code,
                'vehicle' => [
                    'id' => $dashcam->vehicle->id,
                    'plate_number' => $dashcam->vehicle->plate_number,
                    'model' => $dashcam->vehicle->model,
                ],
            ],
        ]);
    }

    /**
     * Receive a GPS fix from the dashcam.
     */
    public function location(Request $request): JsonResponse
    {
        $dashcam = $this->resolveDashcam($request);

        if (!$dashcam) {
            return response()->json(['success' => false, 'message' => 'Invalid dashcam code.'], 404);
        }

        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed_kph' => 'nullable|numeric|min:0|max:400',
            'heading' => 'nullable|numeric|between:0,360',
        ]);

        $dashcam->update([
            'last_latitude' => $data['latitude'],
            'last_longitude' => $data['longitude'],
            'last_speed_kph' => $data['speed_kph'] ?? null,
            'last_heading' => $data['heading'] ?? null,
            'last_seen_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    private function resolveDashcam(Request $request): ?Dashcam
    {
        $code = Dashcam::normalizeCode(
            $request->header('X-Dashcam-Code') ?? $request->input('code')
        );

        return $code ? Dashcam::where('code', $code)->first() : null;
    }
}
