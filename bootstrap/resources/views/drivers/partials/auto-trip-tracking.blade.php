{{-- resources/views/drivers/partials/auto-trip-tracking.blade.php --}}

@php
    use App\Services\GeofenceService;

    $motorpoolGateLat = GeofenceService::motorpoolGateLatitude();
    $motorpoolGateLng = GeofenceService::motorpoolGateLongitude();

    $mainGateLat = GeofenceService::mainGateLatitude();
    $mainGateLng = GeofenceService::mainGateLongitude();

    $motorpoolGateRadius = GeofenceService::MOTORPOOL_GATE_RADIUS_METERS;
    $mainGateRadius = GeofenceService::MAIN_GATE_RADIUS_METERS;
@endphp

<div
    id="autoTripTracking"
    aria-hidden="true"
    style="display:none !important;"
    data-location-url="{{ url('/driver/location/update') }}"
    data-motorpool-lat="{{ $motorpoolGateLat }}"
    data-motorpool-lng="{{ $motorpoolGateLng }}"
    data-motorpool-radius="{{ $motorpoolGateRadius }}"
    data-main-gate-lat="{{ $mainGateLat }}"
    data-main-gate-lng="{{ $mainGateLng }}"
    data-main-gate-radius="{{ $mainGateRadius }}"
></div>

<script>
(function () {
    'use strict';

    /*
     * IMPORTANT:
     * The actual server-side automatic trip decision is handled by
     * FleetTrackingController.
     *
     * This partial is responsible for:
     *  - GPS permission/watch
     *  - sending location updates
     *  - automatic server-side trip processing
     *
     * The server remains the source of truth for trip status.
     */

    const root = document.getElementById('autoTripTracking');

    if (!root) {
        return;
    }

    const LOCATION_URL = root.dataset.locationUrl;

    const MOTORPOOL_GATE = {
        lat: parseFloat(root.dataset.motorpoolLat),
        lng: parseFloat(root.dataset.motorpoolLng),
        radius: parseFloat(root.dataset.motorpoolRadius)
    };

    const MAIN_GATE = {
        lat: parseFloat(root.dataset.mainGateLat),
        lng: parseFloat(root.dataset.mainGateLng),
        radius: parseFloat(root.dataset.mainGateRadius)
    };


    let watchId = null;
    let sending = false;
    let lastSentAt = 0;

    const SEND_INTERVAL = 15000;
    const MIN_ACCURACY = 100;


    function element(id) {
        return document.getElementById(id);
    }


    function setStatus(type, title, message) {

        const icon = element('trackingStatusIcon');
        const titleElement = element('trackingStatusTitle');
        const messageElement = element('trackingStatusMessage');

        if (icon) {
            icon.classList.remove(
                'active',
                'warning',
                'completed'
            );

            if (type) {
                icon.classList.add(type);
            }
        }

        if (titleElement) {
            titleElement.textContent = title;
        }

        if (messageElement) {
            messageElement.textContent = message;
        }
    }


    function setGpsStatus(type, text, message) {

        const badge = element('autoGpsBadge');
        const messageElement = element('autoGpsMessage');

        if (badge) {
            badge.classList.remove(
                'active',
                'error',
                'waiting'
            );

            if (type) {
                badge.classList.add(type);
            }

            badge.textContent = text;
        }

        if (messageElement) {
            messageElement.textContent = message;
        }
    }


    function haversineMeters(
        lat1,
        lng1,
        lat2,
        lng2
    ) {

        const R = 6371008.8;

        const toRad = function (value) {
            return value * Math.PI / 180;
        };

        const dLat = toRad(lat2 - lat1);
        const dLng = toRad(lng2 - lng1);

        const a =
            Math.sin(dLat / 2) ** 2 +
            Math.cos(toRad(lat1)) *
            Math.cos(toRad(lat2)) *
            Math.sin(dLng / 2) ** 2;

        return 2 * R * Math.atan2(
            Math.sqrt(a),
            Math.sqrt(1 - a)
        );
    }


    function formatDistance(distance) {

        if (!Number.isFinite(distance)) {
            return '--';
        }

        if (distance < 1000) {
            return Math.round(distance);
        }

        return (distance / 1000).toFixed(2) + ' km';
    }


    function updateGateDistance(
        distanceElementId,
        badgeElementId,
        cardElementId,
        distance,
        radius
    ) {

        const distanceElement = element(distanceElementId);
        const badge = element(badgeElementId);
        const card = element(cardElementId);

        const inside = distance <= radius;

        if (distanceElement) {
            distanceElement.textContent =
                formatDistance(distance);
        }

        if (badge) {

            badge.classList.remove(
                'detected',
                'active'
            );

            badge.textContent =
                inside
                    ? 'Detected'
                    : 'Outside';

            if (inside) {
                badge.classList.add('detected');
            }
        }

        if (card) {
            card.classList.toggle(
                'detected',
                inside
            );
        }

        return inside;
    }


    function updateGpsDisplay(position) {

        const coords = position.coords;

        const latitude = Number(coords.latitude);
        const longitude = Number(coords.longitude);
        const accuracy = Number(coords.accuracy);

        if (element('autoGpsLatitude')) {
            element('autoGpsLatitude').textContent =
                latitude.toFixed(6);
        }

        if (element('autoGpsLongitude')) {
            element('autoGpsLongitude').textContent =
                longitude.toFixed(6);
        }

        if (element('autoGpsAccuracy')) {
            element('autoGpsAccuracy').textContent =
                Number.isFinite(accuracy)
                    ? Math.round(accuracy)
                    : '--';
        }

        if (element('autoGpsLastUpdate')) {
            element('autoGpsLastUpdate').textContent =
                new Date().toLocaleTimeString();
        }


        const motorpoolDistance =
            haversineMeters(
                latitude,
                longitude,
                MOTORPOOL_GATE.lat,
                MOTORPOOL_GATE.lng
            );

        const mainGateDistance =
            haversineMeters(
                latitude,
                longitude,
                MAIN_GATE.lat,
                MAIN_GATE.lng
            );


        const atMotorpool =
            updateGateDistance(
                'motorpoolGateDistance',
                'motorpoolGateBadge',
                'motorpoolGateCard',
                motorpoolDistance,
                MOTORPOOL_GATE.radius
            );


        const atMainGate =
            updateGateDistance(
                'mainGateDistance',
                'mainGateBadge',
                'mainGateCard',
                mainGateDistance,
                MAIN_GATE.radius
            );


        /*
         * Only update the visual flow here.
         * Actual trip state is returned by the server.
         */

        if (atMotorpool) {

            const step =
                element('flowStepOdometer');

            if (step) {
                step.classList.add('active');
            }
        }

        if (atMainGate) {

            const step =
                element('flowStepMainGate');

            if (step) {
                step.classList.add('active');
            }
        }
    }


    function updateServerState(data) {

        if (!data) {
            return;
        }

        /*
         * Accept several possible response names so this partial
         * remains compatible with the tracking controller.
         */

        const trip =
            data.trip_ticket ||
            data.tripTicket ||
            data.active_trip ||
            data.activeTrip ||
            null;


        const status =
            data.status ||
            data.trip_status ||
            (trip ? trip.status : null);


        const normalizedStatus =
            status
                ? String(status).toLowerCase()
                : '';


        if (
            normalizedStatus === 'on-going' ||
            normalizedStatus === 'ongoing' ||
            normalizedStatus === 'in_progress'
        ) {

            setStatus(
                'active',
                'Trip Automatically Started',
                'The vehicle has passed the CLSU Main Gate. GPS route tracking is active.'
            );


            const mainStep =
                element('flowStepMainGate');

            const trackingStep =
                element('flowStepTracking');

            if (mainStep) {
                mainStep.classList.add('completed');
                mainStep.classList.remove('active');
            }

            if (trackingStep) {
                trackingStep.classList.add('active');
            }

            return;
        }


        if (
            normalizedStatus === 'completed'
        ) {

            setStatus(
                'completed',
                'Trip Automatically Completed',
                'The vehicle returned through the CLSU Motorpool Gate.'
            );


            [
                'flowStepOdometer',
                'flowStepMainGate',
                'flowStepTracking',
                'flowStepComplete'
            ].forEach(function (id) {

                const step = element(id);

                if (step) {
                    step.classList.add('completed');
                    step.classList.remove('active');
                }
            });

            return;
        }


        if (
            normalizedStatus === 'cancelled'
        ) {

            setStatus(
                'warning',
                'Trip Cancelled',
                'The current travel order/trip ticket has been cancelled.'
            );

            return;
        }


        if (
            normalizedStatus === 'approved' ||
            normalizedStatus === 'pending'
        ) {

            setStatus(
                null,
                'Automatic Tracking Ready',
                'The approved trip will start automatically at the CLSU Main Gate.'
            );

            return;
        }


        /*
         * Some controller responses may expose explicit flags.
         */

        if (
            data.auto_started === true ||
            data.trip_started === true
        ) {

            setStatus(
                'active',
                'Trip Automatically Started',
                'GPS route tracking is currently active.'
            );

            const trackingStep =
                element('flowStepTracking');

            if (trackingStep) {
                trackingStep.classList.add('active');
            }
        }


        if (
            data.auto_completed === true ||
            data.trip_completed === true
        ) {

            setStatus(
                'completed',
                'Trip Automatically Completed',
                'The vehicle returned through the CLSU Motorpool Gate.'
            );
        }
    }


    async function sendLocation(position) {

        const now = Date.now();

        if (
            sending ||
            now - lastSentAt < SEND_INTERVAL
        ) {
            return;
        }

        sending = true;

        const coords = position.coords;

        const payload = {
            latitude: Number(coords.latitude),
            longitude: Number(coords.longitude),
            accuracy: Number(coords.accuracy || 0),
            speed: Number.isFinite(coords.speed)
                ? Number(coords.speed)
                : null,
            heading: Number.isFinite(coords.heading)
                ? Number(coords.heading)
                : null,
            altitude: Number.isFinite(coords.altitude)
                ? Number(coords.altitude)
                : null,
            altitude_accuracy: Number.isFinite(coords.altitudeAccuracy)
                ? Number(coords.altitudeAccuracy)
                : null
        };


        try {

            const response =
                await fetch(
                    LOCATION_URL,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN':
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    ?.getAttribute('content') || ''
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload)
                    }
                );


            if (!response.ok) {
                throw new Error(
                    'HTTP ' + response.status
                );
            }


            let data = null;

            try {
                data = await response.json();
            } catch (jsonError) {
                data = null;
            }


            lastSentAt = now;

            updateServerState(data);

        } catch (error) {

            console.error(
                'Automatic location update failed:',
                error
            );

        } finally {

            sending = false;
        }
    }


    function gpsSuccess(position) {

        setGpsStatus(
            'active',
            'ON',
            'GPS location is being monitored automatically.'
        );

        updateGpsDisplay(position);

        sendLocation(position);
    }


    function gpsError(error) {

        let message =
            'Unable to obtain your GPS location.';

        switch (error.code) {

            case error.PERMISSION_DENIED:
                message =
                    'GPS permission was denied. Please allow location access.';
                break;

            case error.POSITION_UNAVAILABLE:
                message =
                    'GPS position is currently unavailable.';
                break;

            case error.TIMEOUT:
                message =
                    'GPS request timed out. Retrying...';
                break;
        }


        setGpsStatus(
            'error',
            'ERROR',
            message
        );


        setStatus(
            'warning',
            'GPS Required',
            message
        );
    }


    function startGps() {

        if (
            !('geolocation' in navigator)
        ) {

            setGpsStatus(
                'error',
                'UNAVAILABLE',
                'This device/browser does not support GPS location.'
            );

            return;
        }


        if (
            watchId !== null
        ) {
            return;
        }


        setGpsStatus(
            'waiting',
            'WAITING',
            'Requesting GPS permission...'
        );


        watchId =
            navigator.geolocation.watchPosition(
                gpsSuccess,
                gpsError,
                {
                    enableHighAccuracy: true,
                    maximumAge: 5000,
                    timeout: 15000
                }
            );
    }


    function stopGps() {

        if (
            watchId === null
        ) {
            return;
        }

        navigator.geolocation.clearWatch(
            watchId
        );

        watchId = null;
    }


    /*
     * Start automatically when the PWA page loads.
     *
     * No Start Trip or End Trip button is required.
     */

    startGps();


    /*
     * Stop the browser watcher when the page is unloaded.
     * The next PWA page load will automatically start it again.
     */

    window.addEventListener(
        'beforeunload',
        stopGps
    );


    /*
     * Make the tracker available to the parent trips page
     * without requiring another GPS watcher.
     */

    window.autoTripTracking = {
        start: startGps,
        stop: stopGps,
        update: sendLocation
    };

})();
</script>