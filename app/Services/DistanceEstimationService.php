<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DistanceEstimationService
{
    private Client $client;
    private string $baseUrl = 'https://maps.googleapis.com/maps/api';
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google.maps.server_key');
        $this->client = new Client([
            'timeout' => 10,
        ]);
    }

    /**
     * Get round-trip distance between two locations using Google's
     * Directions API (driving distance, not straight-line).
     *
     * @param string $origin Starting location
     * @param string $destination Ending location
     * @return float|null Distance in kilometers (round-trip), null if unavailable
     */
    public function getRoundTripDistance(string $origin, string $destination): ?float
    {
        $oneWay = $this->getOneWayDistance($origin, $destination);

        return $oneWay !== null ? round($oneWay * 2, 2) : null;
    }

    /**
     * Get one-way driving distance between two locations using Google's
     * Directions API. Directions API accepts free-text addresses directly
     * for origin/destination, so no separate geocoding call is needed here.
     *
     * @return float|null Distance in kilometers, null if unavailable
     */
    public function getOneWayDistance(string $origin, string $destination): ?float
    {
        if (empty($this->apiKey)) {
            Log::warning('Google Maps API key is not configured (GOOGLE_MAPS_API_KEY); falling back to the estimated distance table.');
            $fallback = $this->getFallbackDistance($destination);
            return $fallback ? $fallback / 2 : null;
        }

        try {
            $distance = $this->getGoogleDirectionsDistance($origin, $destination);

            if ($distance !== null) {
                return $distance;
            }
        } catch (\Exception $e) {
            Log::error('Google Directions API error: ' . $e->getMessage());
        }

        // API failed or returned no route - fall back to the static estimate
        // table rather than leaving the field blank.
        $fallback = $this->getFallbackDistance($destination);
        return $fallback ? $fallback / 2 : null;
    }

    /**
     * Geocode a free-text location into coordinates using Google's
     * Geocoding API. Not used by the distance calculation above (Directions
     * API takes addresses directly) but available for any other feature
     * that needs to turn a place name into a lat/lng.
     */
    public function geocodeLocation(string $location): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->client->get("{$this->baseUrl}/geocode/json", [
                'query' => [
                    'address' => $location,
                    'region' => 'ph',
                    'key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (($data['status'] ?? null) !== 'OK' || empty($data['results'])) {
                Log::warning('Google Geocoding API returned status: ' . ($data['status'] ?? 'unknown') . ' for: ' . $location);
                return null;
            }

            $coords = $data['results'][0]['geometry']['location'];

            return [
                'lat' => (float) $coords['lat'],
                'lon' => (float) $coords['lng'],
            ];
        } catch (\Exception $e) {
            Log::warning('Google Geocoding request failed for: ' . $location . ' - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get driving distance between two free-text locations using Google's
     * Directions API.
     */
    private function getGoogleDirectionsDistance(string $origin, string $destination): ?float
    {
        try {
            $response = $this->client->get("{$this->baseUrl}/directions/json", [
                'query' => [
                    'origin' => $origin . ', Philippines',
                    'destination' => $destination . ', Philippines',
                    'mode' => 'driving',
                    'region' => 'ph',
                    'key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (($data['status'] ?? null) !== 'OK' || empty($data['routes'])) {
                Log::warning('Google Directions API returned status: ' . ($data['status'] ?? 'unknown') . " for {$origin} -> {$destination}");
                return null;
            }

            // A route can have multiple legs (e.g. with waypoints); sum them
            // all to get the total driving distance in meters.
            $meters = 0;
            foreach ($data['routes'][0]['legs'] ?? [] as $leg) {
                $meters += $leg['distance']['value'] ?? 0;
            }

            return $meters > 0 ? round($meters / 1000, 2) : null;
        } catch (\Exception $e) {
            Log::warning('Google Directions request failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fallback distance estimation for common destinations from CLSU.
     * Used when the Google Directions API is unavailable (no key
     * configured, quota exceeded, network error, or no route found).
     */
    private function getFallbackDistance(string $destination): ?float
    {
        $destination = strtolower(trim($destination));

        // Common destinations from CLSU (Science City of Muñoz, Nueva Ecija)
        $distanceMap = [
            // Local destinations
            'munoz' => 5,
            'science city' => 0,
            'clsu' => 0,
            'central luzon state university' => 0,
            'campus' => 2,

            // Nearby municipalities
            'cabanatuan' => 45,
            'cabanatuan city' => 45,
            'gapan' => 35,
            'gapan city' => 35,
            'san jose' => 25,
            'san jose city' => 25,
            'quezon' => 30,

            // Provinces
            'pampanga' => 80,
            'tarlac' => 90,
            'bulacan' => 120,
            'nueva ecija' => 40,

            // Major cities
            'manila' => 160,
            'metro manila' => 160,
            'quezon city' => 150,
            'caloocan' => 155,
            'makati' => 165,
            'pasay' => 162,

            // Airports
            'naia' => 160,
            'ninoy aquino' => 160,
            'clark' => 70,
            'clark international' => 70,
        ];

        // Check for matches
        foreach ($distanceMap as $key => $distance) {
            if (strpos($destination, $key) !== false) {
                // Return round-trip distance (multiply by 2)
                return $distance * 2;
            }
        }

        // Default for unknown destinations
        return null;
    }
}
