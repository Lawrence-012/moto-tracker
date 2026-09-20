<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    // CLSU Coordinates (Central Luzon State University, Science City of Muñoz, Nueva Ecija)
    const CLSU_LAT = 15.0943;
    const CLSU_LNG = 120.9686;

    /**
     * Geocode an address to get coordinates
     */
    public function geocode(Request $request)
    {
        $request->validate([
            'address' => 'required|string|min:3'
        ]);

        $address = $request->input('address');

        // Use Google's Geocoding API
        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address . ', Philippines',
            'region' => 'ph',
            'key' => config('services.google.maps.server_key'),
        ]);

        if ($response->successful() && ($response->json('status') === 'OK') && count($response->json('results')) > 0) {
            $result = $response->json('results')[0];
            $location = $result['geometry']['location'];

            return response()->json([
                'success' => true,
                'latitude' => (float) $location['lat'],
                'longitude' => (float) $location['lng'],
                'display_name' => $result['formatted_address'],
                'region' => $this->extractRegion($this->addressComponentsToNominatimStyle($result['address_components']))
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Location not found. Please try a more specific address.'
        ], 404);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    public function calculateDistance(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');

        $distance= $this->haversineDistance(self::CLSU_LAT, self::CLSU_LNG, $lat, $lng);

        return response()->json([
            'success' => true,
            'distance_km' => round($distance, 2),
            'from' => 'CLSU, Science City of Muñoz, Nueva Ecija',
            'to' => "Coordinates: {$lat}, {$lng}"
        ]);
    }

    /**
     * Get location details including coordinates, region, and distance from CLSU
     */
    public function getLocationDetails(Request $request)
    {
        $request->validate([
            'address' => 'required|string|min:3'
        ]);

        $address = $request->input('address');

        // Geocode the address using Google's Geocoding API
        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address . ', Philippines',
            'region' => 'ph',
            'key' => config('services.google.maps.server_key'),
        ]);

        if ($response->successful() && ($response->json('status') === 'OK') && count($response->json('results')) > 0) {
            $result = $response->json('results')[0];
            $lat = (float) $result['geometry']['location']['lat'];
            $lng = (float) $result['geometry']['location']['lng'];
            $addressParts = $this->addressComponentsToNominatimStyle($result['address_components']);

            // Calculate distance from CLSU
            $distance = $this->haversineDistance(self::CLSU_LAT, self::CLSU_LNG, $lat, $lng);

            // Extract region from address details
            $region = $this->extractRegion($addressParts);

            return response()->json([
                'success' => true,
                'latitude' => $lat,
                'longitude' => $lng,
                'display_name' => $result['formatted_address'],
                'region' => $region,
                'distance_km' => round($distance, 2),
                'formatted_address' => $this->formatAddress($addressParts)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Location not found. Please try a more specific address.'
        ], 404);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * 
     * @param float $lat1 Latitude of point 1
     * @param float $lng1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lng2 Longitude of point 2
     * @return float Distance in kilometers
     */
    private function haversineDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Convert Google Geocoding API's `address_components` array into the
     * flat associative shape (road/city/county/state) that
     * extractRegion() and formatAddress() below expect, so those two
     * methods didn't need to change when this controller switched from
     * Nominatim to Google.
     */
    private function addressComponentsToNominatimStyle(array $components): array
    {
        $address = [];

        foreach ($components as $component) {
            $types = $component['types'] ?? [];

            if (in_array('route', $types)) {
                $address['road'] = $component['long_name'];
            }
            if (in_array('locality', $types)) {
                $address['city'] = $component['long_name'];
            }
            if (in_array('administrative_area_level_2', $types)) {
                $address['county'] = $component['long_name'];
            }
            if (in_array('administrative_area_level_1', $types)) {
                // For Philippine addresses Google puts the province here
                // (e.g. "Nueva Ecija"), matching how Nominatim populated
                // `state` for PH addresses.
                $address['state'] = $component['long_name'];
            }
        }

        return $address;
    }

    /**
     * Extract region from address components
     */
    private function extractRegion($address)
    {
        // Check for region in state/province field
        if (isset($address['state'])) {
            $state = $address['state'];
            if (preg_match('/(Region\s*[IVX]+|[A-Z]\s*-\s*[A-Z]|MIMAROPA|CAR|BARMM)/i', $state, $matches)) {
                return $matches[0];
            }
        }

        // Map provinces to regions
        $provinceToRegion = [
            // MIMAROPA (Region IV-B)
            'Palawan' => 'MIMAROPA (Region IV-B)',
            'Occidental Mindoro' => 'MIMAROPA (Region IV-B)',
            'Oriental Mindoro' => 'MIMAROPA (Region IV-B)',
            'Marinduque' => 'MIMAROPA (Region IV-B)',
            'Romblon' => 'MIMAROPA (Region IV-B)',
            
            // Western Visayas (Region VI)
            'Aklan' => 'Western Visayas (Region VI)',
            'Antique' => 'Western Visayas (Region VI)',
            'Capiz' => 'Western Visayas (Region VI)',
            'Iloilo' => 'Western Visayas (Region VI)',
            'Guimaras' => 'Western Visayas (Region VI)',
            'Negros Occidental' => 'Western Visayas (Region VI)',
            
            // Central Visayas (Region VII)
            'Cebu' => 'Central Visayas (Region VII)',
            'Bohol' => 'Central Visayas (Region VII)',
            'Negros Oriental' => 'Central Visayas (Region VII)',
            'Siquijor' => 'Central Visayas (Region VII)',
            
            // NCR
            'Metro Manila' => 'National Capital Region (NCR)',
            'Manila' => 'National Capital Region (NCR)',
            
            // CAR
            'Benguet' => 'Cordillera Administrative Region (CAR)',
            'Baguio' => 'Cordillera Administrative Region (CAR)',
            'Ifugao' => 'Cordillera Administrative Region (CAR)',
            'Mountain Province' => 'Cordillera Administrative Region (CAR)',
            
            // Ilocos Region (Region I)
            'Ilocos Norte' => 'Ilocos Region (Region I)',
            'Ilocos Sur' => 'Ilocos Region (Region I)',
            'La Union' => 'Ilocos Region (Region I)',
            'Pangasinan' => 'Ilocos Region (Region I)',
            
            // Cagayan Valley (Region II)
            'Batanes' => 'Cagayan Valley (Region II)',
            'Cagayan' => 'Cagayan Valley (Region II)',
            'Isabela' => 'Cagayan Valley (Region II)',
            'Nueva Vizcaya' => 'Cagayan Valley (Region II)',
            'Quirino' => 'Cagayan Valley (Region II)',
            
            // Central Luzon (Region III)
            'Aurora' => 'Central Luzon (Region III)',
            'Bataan' => 'Central Luzon (Region III)',
            'Bulacan' => 'Central Luzon (Region III)',
            'Nueva Ecija' => 'Central Luzon (Region III)',
            'Pampanga' => 'Central Luzon (Region III)',
            'Tarlac' => 'Central Luzon (Region III)',
            'Zambales' => 'Central Luzon (Region III)',
        ];

        foreach ($provinceToRegion as $province => $region) {
            if (stripos($address['state'] ?? '', $province) !== false ||
                stripos($address['county'] ?? '', $province) !== false) {
                return $region;
            }
        }

        // Default: return state or "Unknown Region"
        return $address['state'] ?? 'Unknown Region';
    }

    /**
     * Format address for display
     */
    private function formatAddress($address)
    {
        $parts = [];
        
        if (isset($address['road'])) $parts[] = $address['road'];
        if (isset($address['city'])) $parts[] = $address['city'];
        elseif (isset($address['town'])) $parts[] = $address['town'];
        if (isset($address['county'])) $parts[] = $address['county'];
        if (isset($address['state'])) $parts[] = $address['state'];
        
        return implode(', ', $parts) ?: 'Philippines';
    }
}
