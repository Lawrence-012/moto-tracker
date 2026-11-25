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
             TRACKING STATUS (map removed for drivers)
        ====================================================== --}}

        <div class="mx-0 sm:mx-2">

            <div
                id="trackingStatusCard"
                class="relative overflow-hidden rounded-xl border border-[#1e6031]/20 bg-white shadow"
            >

                {{-- LOCATION PERMISSION BANNER --}}

                <div
                    id="locationPermissionBanner"
                    class="hidden items-center gap-2 px-3 py-2.5 text-xs font-semibold cursor-pointer"
                >

                    <span id="locationPermissionIcon" class="text-base leading-none">
                        📍
                    </span>

                    <span id="locationPermissionText" class="flex-1"></span>

                    <span class="text-[10px] underline opacity-75">
                        Tap for help
                    </span>

                </div>

                {{-- TRIP STATUS --}}

                <div class="flex items-center justify-between gap-3 px-4 py-4">

                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500">
                            Trip status
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Your location is shared automatically while a trip is ongoing.
                        </p>
                    </div>

                    <div
                        id="tripStatusBadge"
                        class="shrink-0 bg-[#1e6031] text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow"
                    >
                        <span id="tripStatusText">
                            LOCATING...
                        </span>
                    </div>

                </div>

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


<script>

document.addEventListener('DOMContentLoaded', function () {

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


    const MAX_USABLE_ACCURACY_METERS = 35;

    const MAX_PLAUSIBLE_SPEED_KMH = 140;


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

    /*
 * Shows the server trip state (ON-GOING / COMPLETED) on the tracking status card.
 * The map was removed for drivers, so this only updates the badge text.
 */
function setTripStatus(label) {
    const text = document.getElementById('tripStatusText');
    if (text) {
        text.textContent = label;
    }
}

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




});

</script>

@endpush