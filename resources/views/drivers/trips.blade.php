{{-- resources/views/drivers/trips.blade.php --}}

@extends('layouts.driver')

@push('styles')
<style>
    .trip-page {
        width: 100%;
        max-width: 1100px;
        margin: 0 auto;
        padding: 12px 12px 85px;
        min-height: calc(100vh - 70px);
        box-sizing: border-box;
    }

    .trip-layout {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .map-container {
        position: relative;
        width: 100%;
        height: clamp(400px, 55vh, 650px);
        border-radius: 14px;
        overflow: hidden;
        border: 2px solid #1e6031;
        background: #f3f4f6;
    }

    #driverMap {
        width: 100%;
        height: 100%;
    }

    .gps-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px 20px;
    }

    .gate-status-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .gate-status-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 10px 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }

    .gate-status-title {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        font-weight: 800;
        color: #1e6031;
    }

    .gate-status-value {
        margin-top: 3px;
        font-size: 12px;
        font-weight: 700;
        color: #374151;
    }

    .gate-status-description {
        margin-top: 2px;
        font-size: 10px;
        color: #6b7280;
    }

    .gate-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex: 0 0 10px;
    }

    .gate-dot.motorpool {
        background: #2563eb;
    }

    .gate-dot.main {
        background: #dc3545;
    }

    @media (min-width: 1024px) {
        .trip-page {
            max-width: 1200px;
            padding-top: 10px;
            padding-bottom: 75px;
        }

        .map-container {
            height: clamp(480px, 62vh, 720px);
        }
    }

    @media (min-width: 640px) and (max-width: 1023px) {
        .trip-page {
            max-width: 850px;
        }

        .map-container {
            height: clamp(420px, 58vh, 600px);
        }
    }

    @media (max-width: 639px) {
        .trip-page {
            max-width: 520px;
            padding: 8px 8px 85px;
        }

        .trip-header {
            margin-top: 4px !important;
        }

        .map-container {
            height: clamp(360px, 52vh, 500px);
            border-radius: 12px;
        }

        .gps-grid {
            grid-template-columns: 1fr 1fr;
            gap: 8px 10px;
        }

        .gps-grid p {
            font-size: 11px;
        }

        .gate-status-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 380px) {
        .trip-page {
            padding-left: 6px;
            padding-right: 6px;
        }

        .map-container {
            height: 380px;
        }

        .gps-grid {
            gap: 6px;
        }
    }

    @media (min-width: 1024px) and (max-height: 800px) {
        .trip-page {
            padding-top: 6px;
        }

        .trip-layout {
            gap: 7px;
        }

        .map-container {
            height: clamp(400px, 57vh, 500px);
        }

        .gps-grid {
            gap: 4px 20px;
        }
    }

    @media (min-width: 1024px) and (max-height: 650px) {
        .map-container {
            height: 390px;
        }

        .trip-page {
            padding-bottom: 70px;
        }
    }
</style>
@endpush


@section('content')

<div class="trip-page">

    <div class="trip-layout">

        {{-- =====================================================
             HEADER
        ====================================================== --}}

        <div
            class="trip-header rounded-2xl overflow-hidden mx-0 sm:mx-2 mt-1"
            style="background:#1e6031;"
        >

            <div class="px-4 py-3 text-center">

                <h1 class="text-white font-extrabold text-lg tracking-wide">
                    CLSU MOTO-TRACK
                    <span style="color:#F0AD4E;">
                        DRIVER
                    </span>
                </h1>

                <p class="text-green-100 text-xs mt-1">
                    Active Trip Ticket:
                    <strong>
                        #{{ $activeTicket->ticket_number ?? 'N/A' }}
                    </strong>
                </p>

            </div>

            <div
                class="border-t border-green-700 px-4 py-2 flex items-center justify-center gap-2"
            >

                <svg
                    class="w-4 h-4 text-white"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"
                    />
                </svg>

                <span class="text-white text-sm font-semibold">
                    Welcome, {{ Auth::user()->name }}!
                </span>

            </div>

        </div>


        {{-- =====================================================
             TRIP ALLOCATION & ALERTS
        ====================================================== --}}

        <div class="mx-0 sm:mx-2">

            <div
                class="rounded-full text-center py-2 font-bold text-sm text-white"
                style="background:#1e6031;"
            >
                TRIP ALLOCATION &amp; ALERTS
            </div>

            <div
                id="alertsBox"
                class="bg-white border border-gray-200 rounded-xl shadow-sm mt-1 p-2"
            >

                {{-- TODAY'S TRIP --}}
                @if(isset($todayTripTickets) && $todayTripTickets->count())
                    <div class="mb-2">
                        <div class="flex items-center justify-between gap-2 px-2 py-1.5">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-ticket-alt text-[#1e6031]"></i>
                                <p class="text-sm font-bold text-gray-900">
                                    Today's Trip
                                </p>
                            </div>
                            <span class="text-[10px] text-gray-500">
                                {{ now()->format('M d, Y') }}
                            </span>
                        </div>

                        <div class="space-y-2">
                            @foreach($todayTripTickets as $todayTicket)
                                @php
                                    $todayStatus = $todayTicket->status;

                                    $todayStatusStyle = match ($todayStatus) {
                                        'On-Going' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'Completed' => 'bg-green-100 text-green-800 border-green-200',
                                        default => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    };

                                    $todayStatusText = match ($todayStatus) {
                                        'On-Going' => 'ONGOING',
                                        'Completed' => 'COMPLETED',
                                        default => 'READY FOR DISPATCH',
                                    };

                                    $todayVehicle = $todayTicket->itinerary?->vehicle;
                                @endphp

                                <div class="rounded-lg border border-green-100 bg-green-50 px-3 py-2.5">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                                <span class="font-bold text-gray-900 text-sm">
                                                    {{ $todayTicket->ticket_number }}
                                                </span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[9px] font-bold {{ $todayStatusStyle }}">
                                                    {{ $todayStatusText }}
                                                </span>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-3 gap-y-1.5 text-xs">
                                                <div>
                                                    <span class="text-gray-500">Vehicle:</span>
                                                    <span class="font-semibold text-gray-900">
                                                        {{ $todayVehicle?->model ?? 'N/A' }}
                                                        @if($todayVehicle?->plate_number)
                                                            ({{ $todayVehicle->plate_number }})
                                                        @endif
                                                    </span>
                                                </div>

                                                <div>
                                                    <span class="text-gray-500">Destination:</span>
                                                    <span class="font-semibold text-gray-900">
                                                        {{ $todayTicket->itinerary?->destination ?? 'N/A' }}
                                                    </span>
                                                </div>

                                                <div>
                                                    <span class="text-gray-500">Head of Party:</span>
                                                    <span class="font-semibold text-gray-900">
                                                        {{ $todayTicket->head_of_party ?: 'Not specified' }}
                                                    </span>
                                                </div>

                                                <div>
                                                    <span class="text-gray-500">Departure:</span>
                                                    <span class="font-semibold text-gray-900">
                                                        {{ $todayTicket->itinerary?->departure_time ? \Carbon\Carbon::parse($todayTicket->itinerary->departure_time)->format('g:i A') : 'N/A' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <a href="{{ route('driver.trip-tickets.show', $todayTicket->id) }}"
                                           class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-[#1e6031] text-[#1e6031] hover:bg-white text-xs font-semibold no-underline flex-shrink-0">
                                            <i class="fas fa-eye mr-1.5"></i>
                                            View
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if(($alerts ?? collect())->count())
                        <div class="border-t border-gray-100 my-2"></div>
                    @endif
                @endif

                @forelse($alerts ?? [] as $alert)

                    <div
                        class="flex items-start gap-2
                        {{ !$loop->last ? 'mb-1.5 pb-1.5 border-b border-gray-100' : '' }}"
                    >

                        <span class="text-lg leading-none">
                            ⚠️
                        </span>

                        <div>
                            <p class="text-sm font-bold text-gray-900">
                                {{ $alert->title }}
                            </p>

                            <p class="text-xs text-gray-600">
                                {{ $alert->message }}
                            </p>
                        </div>

                    </div>

                @empty

                    <div class="flex items-start gap-2">

                        <span class="text-lg leading-none">
                            ✅
                        </span>

                        <div>
                            <p class="text-sm font-bold text-gray-900">
                                No Active Alerts
                            </p>

                            <p class="text-xs text-gray-600">
                                No maintenance issues reported for your assigned vehicle.
                            </p>
                        </div>

                    </div>

                @endforelse

            </div>

        </div>


        {{-- =====================================================
             MAP
        ====================================================== --}}

        <div class="mx-0 sm:mx-2">

            <div
                id="mapContainer"
                class="map-container"
            >

                <div id="driverMap"></div>


                {{-- LOCATION PERMISSION BANNER --}}

                <div
                    id="locationPermissionBanner"
                    class="absolute top-0 left-0 right-0 z-[1100] hidden items-center gap-2 px-3 py-2.5 shadow-md text-xs font-semibold cursor-pointer"
                >

                    <span
                        id="locationPermissionIcon"
                        class="text-base leading-none"
                    >
                        📍
                    </span>

                    <span
                        id="locationPermissionText"
                        class="flex-1"
                    ></span>

                    <span class="text-[10px] underline opacity-75">
                        Tap for help
                    </span>

                </div>


                {{-- TRIP STATUS --}}

                <div
                    id="tripStatusBadge"
                    class="absolute top-14 right-2 z-[1000] bg-[#1e6031] text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow"
                >

                    TRIP STATUS:

                    <span id="tripStatusText">
                        LOCATING...
                    </span>

                </div>


                {{-- RECENTER --}}

                <button
                    id="myLocationBtn"
                    type="button"
                    title="Recenter on my location"
                    aria-label="Recenter on my location"
                    class="absolute bottom-16 right-2 z-[1000] flex items-center justify-center rounded-full shadow-lg transition-colors"
                    style="width:44px;height:44px;background:#1a73e8;border:none;"
                >

                    <i
                        id="myLocationIcon"
                        class="fas fa-location-crosshairs"
                        style="font-size:18px;color:#ffffff;"
                    ></i>

                </button>


                {{-- LIVE TRAFFIC --}}

                <button
                    id="liveTrafficBtn"
                    type="button"
                    class="absolute bottom-2 left-2 z-[1000] bg-white text-xs font-semibold px-3 py-1.5 rounded-full border border-gray-300 shadow"
                >
                    🔊 Live Traffic
                </button>


                {{-- FULLSCREEN --}}

                <button
                    id="fullscreenBtn"
                    type="button"
                    class="absolute bottom-2 right-2 z-[1000] bg-[#1e6031] text-white text-xs font-semibold px-3 py-1.5 rounded-full shadow"
                >
                    Full Screen Geospatial Map
                </button>

            </div>

        </div>


        {{-- =====================================================
             GPS / ODOMETER
        ====================================================== --}}

        <div
            class="gps-grid mx-0 sm:mx-2 text-sm"
        >

            {{-- GPS --}}

            <div>

                <p
                    class="font-bold text-[#1e6031] text-xs flex items-center gap-1"
                >
                    📡 REAL-TIME GPS TRACKING
                </p>

                <p
                    class="text-gray-500 text-xs italic"
                    id="gpsStatusLabel"
                >
                    Waiting for GPS...
                </p>

            </div>


            {{-- COORDINATES --}}

            <div class="text-right">

                <p class="font-bold text-xs text-gray-700">
                    Lat:

                    <span id="latVal">
                        -
                    </span>
                </p>

                <p class="font-bold text-xs text-gray-700">
                    Long:

                    <span id="lngVal">
                        -
                    </span>
                </p>

            </div>


            {{-- ODOMETER --}}

            <div>

                <p
                    class="font-bold text-[#1e6031] text-xs flex items-center gap-1"
                >
                    🧭 AUTOMATED ODOMETER
                </p>

                <p class="text-gray-500 text-xs italic">
                    Calculated via Haversine Formula
                </p>

            </div>


            {{-- DISTANCE --}}

            <div class="text-right">

                <p class="text-xs text-gray-700">
                    DISTANCE TRAVELED
                </p>

                <p
                    class="font-extrabold text-lg text-[#1e6031]"
                >
                    <span id="distanceVal">
                        0.0
                    </span>

                    km 🚐
                </p>

            </div>

        </div>

    </div>

</div>

@endsection


@push('scripts')

@include('partials.google-maps-script')

<script src="{{ asset('js/gmaps-helpers.js') }}"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    /* =========================================================
       GOOGLE MAPS API CHECK
    ========================================================= */

    if (window.GOOGLE_MAPS_KEY_MISSING) {

        const mapContainer =
            document.getElementById('mapContainer');

        if (mapContainer) {

            mapContainer.innerHTML =
                '<div style="display:flex;align-items:center;justify-content:center;height:100%;background:#f3f4f6;color:#6b7280;font-size:13px;text-align:center;padding:20px;">' +
                '<div>' +
                '<i class="fas fa-map-location-dot" style="font-size:28px;display:block;margin-bottom:8px;"></i>' +
                'Google Maps API key not configured yet. Add GOOGLE_MAPS_API_KEY to your .env file.' +
                '</div>' +
                '</div>';
        }

        const gpsStatus =
            document.getElementById(
                'gpsStatusLabel'
            );

        if (gpsStatus) {
            gpsStatus.textContent =
                'Map unavailable - Google Maps API key not configured';
        }

        return;
    }


    /* =========================================================
       GATE CONSTANTS
    ========================================================= */

    const MOTORPOOL_GATE = {

        lat:
            {{ \App\Services\GeofenceService::motorpoolGateLatitude() }},

        lng:
            {{ \App\Services\GeofenceService::motorpoolGateLongitude() }}

    };


    const MAIN_GATE = {

        lat:
            {{ \App\Services\GeofenceService::mainGateLatitude() }},

        lng:
            {{ \App\Services\GeofenceService::mainGateLongitude() }}

    };


    const MOTORPOOL_GATE_RADIUS_METERS =
        {{ \App\Services\GeofenceService::MOTORPOOL_GATE_RADIUS_METERS }};


    const MAIN_GATE_RADIUS_METERS =
        {{ \App\Services\GeofenceService::MAIN_GATE_RADIUS_METERS }};


    /*
     * Compatibility center.
     *
     * The map is initially centered between the
     * Motorpool Gate and Main Gate.
     */

    const CLSU_CENTER = {

        lat:
            (
                MOTORPOOL_GATE.lat +
                MAIN_GATE.lat
            ) / 2,

        lng:
            (
                MOTORPOOL_GATE.lng +
                MAIN_GATE.lng
            ) / 2

    };


    const MAX_USABLE_ACCURACY_METERS = 35;

    const MAX_PLAUSIBLE_SPEED_KMH = 140;


    /* =========================================================
       MAP
    ========================================================= */

    const map =
        gmCreateMap(
            'driverMap',
            CLSU_CENTER,
            15
        );


    const trafficLayer =
        new google.maps.TrafficLayer();


    /* =========================================================
       CUSTOM PIN
    ========================================================= */

    function pinIcon(glyphPath, color) {

        const svg =
            `<svg xmlns="http://www.w3.org/2000/svg" width="34" height="44" viewBox="0 0 34 44">` +

            `<path d="M17 0C7.6 0 0 7.6 0 17c0 12.75 17 27 17 27s17-14.25 17-27C34 7.6 26.4 0 17 0z" fill="${color}"/>` +

            `<circle cx="17" cy="17" r="12" fill="#ffffff"/>` +

            `<g transform="translate(11,11) scale(0.5)">` +

            `<path d="${glyphPath}" fill="${color}"/>` +

            `</g>` +

            `</svg>`;


        return {

            url:
                'data:image/svg+xml;charset=UTF-8,' +
                encodeURIComponent(svg),

            scaledSize:
                new google.maps.Size(34, 44),

            anchor:
                new google.maps.Point(17, 44)

        };
    }


    const PERSON_GLYPH_PATH =
        'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z';


    /* =========================================================
       MOTORPOOL ADMIN REFERENCE
    ========================================================= */

    const ADMIN_LOCATION = {

        lat:
            {{ \App\Services\GeofenceService::CAMPUS_LAT }},

        lng:
            {{ \App\Services\GeofenceService::CAMPUS_LNG }}

    };


    const adminMarker =
        new google.maps.Marker({

            position:
                ADMIN_LOCATION,

            map,

            icon:
                pinIcon(
                    PERSON_GLYPH_PATH,
                    '#dc3545'
                ),

            title:
                'Motorpool Admin (CLSU)',

            zIndex: 1

        });


    const adminInfoWindow =
        new google.maps.InfoWindow({

            content:
                '<div style="font-size:13px;line-height:1.5;">' +
                '<strong>🔴 Motorpool Admin</strong><br>' +
                'CLSU Motor Pool reference location' +
                '</div>'

        });


    adminMarker.addListener(
        'click',
        function () {

            adminInfoWindow.open({
                anchor: adminMarker,
                map: map
            });

        }
    );


    /* Gate markers/circles are intentionally hidden from the driver UI.
     * The gate coordinates/radii above remain active for automatic geofence detection.
     */


    /* =========================================================
       INITIAL MAP BOUNDS
    ========================================================= */

    const initialBounds =
        new google.maps.LatLngBounds();

    initialBounds.extend(
        MOTORPOOL_GATE
    );

    initialBounds.extend(
        MAIN_GATE
    );

    initialBounds.extend(
        ADMIN_LOCATION
    );

    map.fitBounds(
        initialBounds
    );


    /* =========================================================
       LIVE VEHICLE
    ========================================================= */

    let vehicleMarker =
        null;

    let vehicleInfoWindow =
        null;

    let accuracyCircle =
        null;


    /* =========================================================
       FOLLOW MODE
    ========================================================= */

    let followMode =
        true;


    function setFollowMode(enabled) {

        followMode =
            enabled;

        const btn =
            document.getElementById(
                'myLocationBtn'
            );

        const icon =
            document.getElementById(
                'myLocationIcon'
            );

        if (!btn || !icon) {
            return;
        }

        if (enabled) {

            btn.style.background =
                '#1a73e8';

            icon.style.color =
                '#ffffff';

            btn.title =
                'Following your live location';

        } else {

            btn.style.background =
                '#ffffff';

            icon.style.color =
                '#1a73e8';

            btn.title =
                'Tap to recenter on my location';

        }
    }


    setFollowMode(true);


    map.addListener(
        'dragstart',
        function () {

            setFollowMode(false);

        }
    );


    /* =========================================================
       LOCATION PERMISSION
    ========================================================= */

    let permissionConfirmed =
        false;


    function updatePermissionBanner(
        state,
        message
    ) {

        const banner =
            document.getElementById(
                'locationPermissionBanner'
            );

        const icon =
            document.getElementById(
                'locationPermissionIcon'
            );

        const text =
            document.getElementById(
                'locationPermissionText'
            );

        if (!banner) {
            return;
        }


        const styles = {

            waiting: {
                bg: '#FEF3C7',
                color: '#92400E',
                icon: '📍'
            },

            granted: {
                bg: '#D1FAE5',
                color: '#065F46',
                icon: '✅'
            },

            denied: {
                bg: '#FEE2E2',
                color: '#991B1B',
                icon: '⚠️'
            }

        };


        const s =
            styles[state] ||
            styles.waiting;


        banner.classList.remove(
            'hidden'
        );

        banner.classList.add(
            'flex'
        );

        banner.style.background =
            s.bg;

        banner.style.color =
            s.color;

        if (icon) {
            icon.textContent =
                s.icon;
        }

        if (text) {
            text.textContent =
                message;
        }


        clearTimeout(
            window.__locBannerTimeout
        );


        if (state === 'granted') {

            window.__locBannerTimeout =
                setTimeout(
                    function () {

                        banner.classList.add(
                            'hidden'
                        );

                        banner.classList.remove(
                            'flex'
                        );

                    },
                    4000
                );

        }

    }


    const permissionBanner =
        document.getElementById(
            'locationPermissionBanner'
        );


    if (permissionBanner) {

        permissionBanner.addEventListener(
            'click',
            function () {

                if (
                    typeof Swal ===
                    'undefined'
                ) {
                    return;
                }


                Swal.fire({

                    icon:
                        'info',

                    title:
                        'Enable Location Access',

                    html:
                        'Live tracking needs your device\'s location.<br><br>' +
                        'On most phones: tap the lock/info icon in your browser\'s address bar &rarr; ' +
                        'Site settings &rarr; Location &rarr; Allow, then reload this page.',

                    confirmButtonText:
                        'Got it',

                    confirmButtonColor:
                        '#1e6031'

                });

            }
        );

    }


    updatePermissionBanner(
        'waiting',
        'Requesting location permission…'
    );


    if (
        navigator.permissions &&
        navigator.permissions.query
    ) {

        navigator.permissions
            .query({
                name: 'geolocation'
            })
            .then(
                function (status) {

                    const applyState =
                        function () {

                            if (
                                status.state ===
                                'granted'
                            ) {

                                updatePermissionBanner(
                                    'granted',
                                    'Location access granted - live tracking is active.'
                                );

                            } else if (
                                status.state ===
                                'denied'
                            ) {

                                updatePermissionBanner(
                                    'denied',
                                    'Location access denied - tap for how to enable it.'
                                );

                            } else {

                                updatePermissionBanner(
                                    'waiting',
                                    'Waiting for you to allow location access…'
                                );

                            }

                        };


                    applyState();


                    status.onchange =
                        applyState;

                }
            )
            .catch(
                function () {}
            );

    }


    /* =========================================================
       HAVERSINE
    ========================================================= */

    function distanceKm(
        lat1,
        lon1,
        lat2,
        lon2
    ) {

        if (
            window.google?.maps?.geometry?.spherical
        ) {

            const meters =
                google.maps.geometry.spherical
                    .computeDistanceBetween(

                        new google.maps.LatLng(
                            lat1,
                            lon1
                        ),

                        new google.maps.LatLng(
                            lat2,
                            lon2
                        )

                    );

            return meters / 1000;
        }


        const EARTH_RADIUS_KM =
            6371.0088;


        const dLat =
            (lat2 - lat1) *
            Math.PI /
            180;


        const dLon =
            (lon2 - lon1) *
            Math.PI /
            180;


        const a =
            Math.sin(dLat / 2) ** 2 +

            Math.cos(
                lat1 * Math.PI / 180
            ) *

            Math.cos(
                lat2 * Math.PI / 180
            ) *

            Math.sin(dLon / 2) ** 2;


        const c =
            2 *
            Math.atan2(
                Math.sqrt(a),
                Math.sqrt(1 - a)
            );


        return EARTH_RADIUS_KM * c;
    }


    function distanceFromPointMeters(
        lat,
        lng,
        point
    ) {

        return (
            distanceKm(
                lat,
                lng,
                point.lat,
                point.lng
            ) * 1000
        );

    }


    /* =========================================================
       STATE
    ========================================================= */

    let lastFix =
        null;

    let totalDistanceKm =
        0;

    let currentLat =
        null;

    let currentLng =
        null;


    /* =========================================================
       GATE STATUS
    ========================================================= */

    function updateGateStatus(
        nearMotorpool,
        nearMain
    ) {

        const motorpoolStatus =
            document.getElementById(
                'motorpoolGateStatus'
            );

        const mainStatus =
            document.getElementById(
                'mainGateStatus'
            );


        if (motorpoolStatus) {

            if (nearMotorpool) {

                motorpoolStatus.textContent =
                    'AT MOTORPOOL GATE';

                motorpoolStatus.style.color =
                    '#2563eb';

            } else {

                motorpoolStatus.textContent =
                    'Away from Motorpool Gate';

                motorpoolStatus.style.color =
                    '#374151';

            }

        }


        if (mainStatus) {

            if (nearMain) {

                mainStatus.textContent =
                    'AT MAIN GATE';

                mainStatus.style.color =
                    '#dc3545';

            } else {

                mainStatus.textContent =
                    'Away from Main Gate';

                mainStatus.style.color =
                    '#374151';

            }

        }

    }


    function updateStatusBadge(
        nearMotorpool,
        nearMain
    ) {

        const badge =
            document.getElementById(
                'tripStatusBadge'
            );

        const text =
            document.getElementById(
                'tripStatusText'
            );


        if (!badge || !text) {
            return;
        }


        if (nearMotorpool) {

            badge.style.background =
                '#2563eb';

            text.textContent =
                'AT MOTORPOOL GATE';

            return;

        }


        if (nearMain) {

            badge.style.background =
                '#DC3545';

            text.textContent =
                'AT MAIN GATE';

            return;

        }


        badge.style.background =
            '#1e6031';

        text.textContent =
            'TRACKING';

    }


    /* =========================================================
       SEND LOCATION TO LARAVEL
    ========================================================= */

    let lastServerUpdate =
        0;

    let sendingLocation =
        false;


    async function sendLocationToServer(
        position
    ) {

        const now =
            Date.now();

        /*
         * The server does the authoritative
         * automatic gate/trip processing.
         *
         * Do not send more frequently than
         * every 15 seconds.
         */
        if (
            now - lastServerUpdate <
            15000
        ) {
            return;
        }


        if (sendingLocation) {
            return;
        }


        const coords =
            position.coords;


        if (
            !coords ||
            typeof coords.latitude !==
                'number' ||
            typeof coords.longitude !==
                'number'
        ) {
            return;
        }


        sendingLocation =
            true;


        try {

            const response =
                await fetch(
                    @json(url('/driver/location/update')),
                    {

                        method:
                            'POST',

                        credentials:
                            'same-origin',

                        headers: {

                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json',

                            'X-CSRF-TOKEN':
                                @json(csrf_token()),

                            'X-Requested-With':
                                'XMLHttpRequest'

                        },

                        body:
                            JSON.stringify({

                                latitude:
                                    coords.latitude,

                                longitude:
                                    coords.longitude,

                                accuracy:
                                    coords.accuracy || null,

                                altitude:
                                    coords.altitude || null,

                                speed:
                                    coords.speed || null,

                                heading:
                                    coords.heading || null,

                                timestamp:
                                    position.timestamp ||
                                    Date.now()

                            })

                    }
                );


            if (!response.ok) {

                throw new Error(
                    'HTTP error! status: ' +
                    response.status
                );

            }


            const data =
                await response.json();


            lastServerUpdate =
                now;


            /*
             * Backend is authoritative.
             */
            if (data) {

                const status =
                    data.status ||
                    data.trip_status ||
                    (
                        data.trip &&
                        data.trip.status
                    );


                const completed =
                    data.completed === true ||
                    String(status || '')
                        .toLowerCase() ===
                    'completed';


                const ongoing =
                    data.auto_started === true ||
                    String(status || '')
                        .toLowerCase() ===
                    'on-going';


                const nearMotorpool =
                    data.near_motorpool_gate === true ||
                    data.at_motorpool_gate === true;


                const nearMain =
                    data.near_main_gate === true ||
                    data.at_main_gate === true;


                if (
                    nearMotorpool ||
                    nearMain
                ) {

                    updateGateStatus(
                        nearMotorpool,
                        nearMain
                    );

                }


                if (completed) {

                    setTripStatus(
                        'COMPLETED'
                    );

                } else if (ongoing) {

                    setTripStatus(
                        'ON-GOING'
                    );

                }

            }

        } catch (error) {

            console.error(
                'GPS server update failed:',
                error
            );

        } finally {

            sendingLocation =
                false;

        }

    }


    /* =========================================================
       GPS POSITION
    ========================================================= */

    function handlePosition(
        position
    ) {

        gpsFailCount =
            0;


        if (!permissionConfirmed) {

            permissionConfirmed =
                true;

            updatePermissionBanner(
                'granted',
                'Location access granted - live tracking is active.'
            );

        }


        const lat =
            position.coords.latitude;


        const lng =
            position.coords.longitude;


        const accuracy =
            position.coords.accuracy;


        const timestamp =
            position.timestamp;


        currentLat =
            lat;

        currentLng =
            lng;


        const latElement =
            document.getElementById(
                'latVal'
            );

        const lngElement =
            document.getElementById(
                'lngVal'
            );


        if (latElement) {

            latElement.textContent =
                lat.toFixed(6) +
                '° N';

        }


        if (lngElement) {

            lngElement.textContent =
                lng.toFixed(6) +
                '° E';

        }


        const usable =
            accuracy <=
            MAX_USABLE_ACCURACY_METERS;


        const gpsStatus =
            document.getElementById(
                'gpsStatusLabel'
            );


        if (gpsStatus) {

            gpsStatus.textContent =
                (
                    usable
                        ? 'GPS Active'
                        : 'GPS Active (low precision)'
                ) +

                ' • Accuracy: ±' +

                Math.round(
                    accuracy
                ) +

                'm';

        }


        /* =====================================================
           VEHICLE MARKER
        ===================================================== */

        const point = {

            lat:
                lat,

            lng:
                lng

        };


        if (!vehicleMarker) {

            vehicleMarker =
                new google.maps.Marker({

                    position:
                        point,

                    map,

                    icon: {

                        path:
                            google.maps.SymbolPath.CIRCLE,

                        scale:
                            15,

                        fillColor:
                            '#ffffff',

                        fillOpacity:
                            1,

                        strokeColor:
                            '#1e6031',

                        strokeWeight:
                            3

                    },

                    label: {

                        text:
                            '🚐',

                        fontSize:
                            '16px'

                    },

                    zIndex:
                        999

                });


            vehicleInfoWindow =
                new google.maps.InfoWindow();


            vehicleMarker.addListener(
                'click',
                function () {

                    vehicleInfoWindow.open({
                        anchor:
                            vehicleMarker,

                        map
                    });

                }
            );


            map.setCenter(
                point
            );

            map.setZoom(
                17
            );

        } else {

            vehicleMarker.setPosition(
                point
            );


            if (followMode) {

                map.panTo(
                    point
                );

            }

        }


        if (vehicleInfoWindow) {

            vehicleInfoWindow.setContent(

                '<b>🚐 Current Vehicle Location</b><br>' +

                'Latitude: ' +
                lat.toFixed(6) +
                '<br>' +

                'Longitude: ' +
                lng.toFixed(6) +
                '<br>' +

                'Accuracy: ±' +
                Math.round(accuracy) +
                ' meters'

            );

        }


        /* =====================================================
           ACCURACY CIRCLE
        ===================================================== */

        if (!accuracyCircle) {

            accuracyCircle =
                new google.maps.Circle({

                    map,

                    center:
                        point,

                    radius:
                        accuracy,

                    strokeColor:
                        '#1e6031',

                    strokeWeight:
                        1,

                    fillColor:
                        '#1e6031',

                    fillOpacity:
                        0.10,

                    clickable:
                        false

                });

        } else {

            accuracyCircle.setCenter(
                point
            );

            accuracyCircle.setRadius(
                accuracy
            );

        }


        /* =====================================================
           CLIENT-SIDE DISTANCE DISPLAY
        ===================================================== */

        if (lastFix) {

            const segmentKm =
                distanceKm(

                    lastFix.lat,
                    lastFix.lng,

                    lat,
                    lng

                );


            const elapsedHours =
                Math.max(

                    timestamp -
                    lastFix.timestamp,

                    1

                ) /
                3600000;


            const impliedSpeedKmh =
                segmentKm /
                elapsedHours;


            const bothAccurate =
                accuracy <=
                MAX_USABLE_ACCURACY_METERS &&

                lastFix.accuracy <=
                MAX_USABLE_ACCURACY_METERS;


            const plausibleSpeed =
                impliedSpeedKmh <=
                MAX_PLAUSIBLE_SPEED_KMH;


            const noiseFloorKm =
                (
                    accuracy +
                    lastFix.accuracy
                ) /
                1000;


            if (
                bothAccurate &&
                plausibleSpeed &&
                segmentKm >
                noiseFloorKm
            ) {

                totalDistanceKm +=
                    segmentKm;


                const distanceElement =
                    document.getElementById(
                        'distanceVal'
                    );


                if (distanceElement) {

                    distanceElement.textContent =
                        totalDistanceKm.toFixed(3);

                }

            }

        }


        lastFix = {

            lat:
                lat,

            lng:
                lng,

            accuracy:
                accuracy,

            timestamp:
                timestamp

        };


        /* =====================================================
           GATE DETECTION
        ===================================================== */

        const motorpoolDistanceM =
            distanceFromPointMeters(
                lat,
                lng,
                MOTORPOOL_GATE
            );


        const mainGateDistanceM =
            distanceFromPointMeters(
                lat,
                lng,
                MAIN_GATE
            );


        const nearMotorpool =
            motorpoolDistanceM <=
            MOTORPOOL_GATE_RADIUS_METERS;


        const nearMain =
            mainGateDistanceM <=
            MAIN_GATE_RADIUS_METERS;


        updateGateStatus(
            nearMotorpool,
            nearMain
        );


        updateStatusBadge(
            nearMotorpool,
            nearMain
        );


        /*
         * Send this position to Laravel.
         *
         * IMPORTANT:
         * Gate detection in the backend remains
         * authoritative for starting/completing
         * the trip.
         */
        sendLocationToServer(
            position
        );

    }


    /* =========================================================
       GPS ERROR / RETRY
    ========================================================= */

    let gpsFailCount =
        0;


    const HIGH_ACCURACY_RETRY_LIMIT =
        3;


    let usingHighAccuracy =
        true;


    function handleError(
        err
    ) {

        gpsFailCount++;


        let message =
            'Location unavailable';


        if (err.code === 1) {

            message =
                'Location permission denied - enable location access for this site in your browser settings';


            updatePermissionBanner(
                'denied',
                'Location access denied - tap for how to enable it.'
            );

        } else if (
            err.code === 2
        ) {

            message =
                'GPS location unavailable - checking device location services';

        } else if (
            err.code === 3
        ) {

            message =
                usingHighAccuracy

                    ? 'GPS signal weak - retrying (' +
                      gpsFailCount +
                      '/' +
                      HIGH_ACCURACY_RETRY_LIMIT +
                      ')...'

                    : 'Still searching for a location signal...';

        }


        const gpsStatus =
            document.getElementById(
                'gpsStatusLabel'
            );


        if (gpsStatus) {

            gpsStatus.textContent =
                message;

        }


        const tripStatus =
            document.getElementById(
                'tripStatusText'
            );


        if (tripStatus) {

            tripStatus.textContent =
                'NO SIGNAL';

        }


        if (err.code === 1) {
            return;
        }


        if (
            usingHighAccuracy &&
            gpsFailCount >=
            HIGH_ACCURACY_RETRY_LIMIT
        ) {

            usingHighAccuracy =
                false;

            gpsFailCount =
                0;

            startWatch();

        } else {

            startWatch();

        }

    }


    /* =========================================================
       START GPS
    ========================================================= */

    let watchId =
        null;


    function startWatch() {
        // Driver app does not send location. The dashcam sends GPS.
        return;

        if (watchId !== null) {

            navigator.geolocation.clearWatch(
                watchId
            );

        }


        watchId =
            navigator.geolocation.watchPosition(

                handlePosition,

                handleError,

                {

                    enableHighAccuracy:
                        usingHighAccuracy,

                    maximumAge:
                        5000,

                    timeout:
                        30000

                }

            );

    }


    /* =========================================================
       INITIAL GPS
    ========================================================= */

    if (!window.isSecureContext) {

        const gpsStatus =
            document.getElementById(
                'gpsStatusLabel'
            );

        const tripStatus =
            document.getElementById(
                'tripStatusText'
            );


        if (gpsStatus) {

            gpsStatus.textContent =
                'GPS requires a secure connection (HTTPS) - this page is not being served over HTTPS';

        }


        if (tripStatus) {

            tripStatus.textContent =
                'NO SIGNAL';

        }

    } else if (
        navigator.geolocation
    ) {

        navigator.geolocation.getCurrentPosition(

            handlePosition,

            function (error) {

                handleError(
                    error
                );

            },

            {

                enableHighAccuracy:
                    true,

                maximumAge:
                    60000,

                timeout:
                    15000

            }

        );


        startWatch();

    } else {

        const gpsStatus =
            document.getElementById(
                'gpsStatusLabel'
            );


        if (gpsStatus) {

            gpsStatus.textContent =
                'Geolocation is not supported by this browser';

        }

    }


    /* =========================================================
       MY LOCATION
    ========================================================= */

    const myLocationBtn =
        document.getElementById(
            'myLocationBtn'
        );


    if (myLocationBtn) {

        myLocationBtn.addEventListener(
            'click',
            function () {

                if (
                    currentLat !== null &&
                    currentLng !== null
                ) {

                    setFollowMode(
                        true
                    );


                    map.panTo({

                        lat:
                            currentLat,

                        lng:
                            currentLng

                    });


                    map.setZoom(
                        18
                    );


                    if (
                        vehicleMarker &&
                        vehicleInfoWindow
                    ) {

                        vehicleInfoWindow.open({

                            anchor:
                                vehicleMarker,

                            map

                        });

                    }

                } else {

                    alert(
                        'Waiting for your GPS location...'
                    );

                }

            }
        );

    }


    /* =========================================================
       FULLSCREEN MAP
    ========================================================= */

    const mapContainer =
        document.getElementById(
            'mapContainer'
        );


    const fullscreenBtn =
        document.getElementById(
            'fullscreenBtn'
        );


    let isFullscreen =
        false;


    if (
        mapContainer &&
        fullscreenBtn
    ) {

        fullscreenBtn.addEventListener(
            'click',
            function () {

                isFullscreen =
                    !isFullscreen;


                if (isFullscreen) {

                    mapContainer.style.position =
                        'fixed';

                    mapContainer.style.inset =
                        '0';

                    mapContainer.style.zIndex =
                        '9999';

                    mapContainer.style.borderRadius =
                        '0';

                    mapContainer.style.height =
                        '100vh';

                    fullscreenBtn.textContent =
                        'Exit Full Screen';

                } else {

                    mapContainer.style.position =
                        'relative';

                    mapContainer.style.inset =
                        'auto';

                    mapContainer.style.zIndex =
                        'auto';

                    mapContainer.style.borderRadius =
                        '0.75rem';

                    mapContainer.style.height =
                        '';

                    fullscreenBtn.textContent =
                        'Full Screen Geospatial Map';

                }


                setTimeout(
                    function () {

                        google.maps.event.trigger(
                            map,
                            'resize'
                        );

                    },
                    200
                );

            }
        );

    }


    /* =========================================================
       LIVE TRAFFIC
    ========================================================= */

    let trafficOn =
        false;


    const liveTrafficBtn =
        document.getElementById(
            'liveTrafficBtn'
        );


    if (liveTrafficBtn) {

        liveTrafficBtn.addEventListener(
            'click',
            function () {

                trafficOn =
                    !trafficOn;


                trafficLayer.setMap(

                    trafficOn
                        ? map
                        : null

                );


                liveTrafficBtn.style.background =
                    trafficOn
                        ? '#1e6031'
                        : 'white';


                liveTrafficBtn.style.color =
                    trafficOn
                        ? 'white'
                        : 'black';

            }
        );

    }


    /* =========================================================
       RESPONSIVE MAP RESIZE
    ========================================================= */

    window.addEventListener(
        'resize',
        function () {

            setTimeout(
                function () {

                    google.maps.event.trigger(
                        map,
                        'resize'
                    );

                },
                200
            );

        }
    );


    setTimeout(
        function () {

            google.maps.event.trigger(
                map,
                'resize'
            );

        },
        300
    );

});

</script>

@endpush