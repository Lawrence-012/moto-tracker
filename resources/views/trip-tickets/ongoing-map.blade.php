@extends('layouts.motorpool-admin')

@section('content')
{{-- Open-source map: Leaflet + OpenStreetMap tiles (no Google Maps key needed) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" crossorigin="">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js" crossorigin=""></script>

{{--
    Layout notes:
    - Outer wrapper is a flex column that becomes exactly the height of the
      scrollable <main> area (h-full), at every screen size, so the whole
      page fits on screen with no page-level scrolling.
    - The map/list row uses flex-1 + min-h-0 to consume exactly the leftover
      vertical space (flexbox, not CSS grid, so the row actually stretches
      to fill rather than sizing to its tallest child's content).
    - Map:list is a 3:1 flex-grow ratio - roughly 75%/25% of the row on
      desktop (side by side), and roughly 75%/25% of the column's height on
      mobile (stacked), since flex-grow distributes the same way regardless
      of row/column direction.
--}}
<div class="flex flex-col h-full p-4 sm:p-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-3 flex-shrink-0">
        <div>
            <h2 class="text-lg sm:text-2xl font-bold" style="color: #1e6031;">Vehicle Monitoring</h2>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Monitor vehicles with a registered dashcam, including the last known location when the dashcam is offline.</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-500 flex-shrink-0">
            <span id="live-indicator" class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-800">
                <i class="fas fa-circle mr-1" style="font-size:6px;"></i> Live
            </span>
            <span>Last refreshed: <span id="last-refreshed">--</span></span>
        </div>
    </div>

    <div id="location-alerts" class="hidden">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-location-slash"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-red-800 text-sm">Driver Location Alert</p>
                <div id="location-alert-list" class="mt-1 space-y-1 text-xs text-red-700"></div>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-4 flex-1 min-h-0">
        <!-- Map: ~3/4 of the available space -->
        <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden min-h-[160px]" style="flex: 3 3 0%;">
            <div id="fleet-map" class="w-full h-full" style="position:relative; isolation:isolate; z-index:0;"></div>
        </div>

        <!-- Side panel: ~1/4 of the available space, split between the two lists -->
        <div class="flex flex-col gap-4 min-h-[240px]" style="flex: 1 1 0%;">
            <!-- Trips on the road (hidden: vehicle monitoring only) -->
            <div class="hidden bg-white rounded-lg shadow border border-gray-200 flex flex-col min-h-[100px]" style="flex: 1 1 0%;">
                <div class="px-4 py-3 border-b border-gray-200 flex-shrink-0">
                    <h3 class="font-semibold text-sm text-gray-700">Drivers on trip (<span id="ongoing-count">0</span>)</h3>
                </div>
                <div id="ongoing-list" class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-100">
                    <div class="p-4 text-sm text-gray-400 text-center">Loading...</div>
                </div>
            </div>

            <!-- Drivers on site (hidden: vehicle monitoring only) -->
            <div class="hidden bg-white rounded-lg shadow border border-gray-200 flex flex-col min-h-[100px]" style="flex: 1 1 0%;">
                <div class="px-4 py-3 border-b border-gray-200 flex-shrink-0">
                    <h3 class="font-semibold text-sm text-gray-700">Drivers on site (<span id="idle-count">0</span>)</h3>
                </div>
                <div id="idle-list" class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-100">
                    <div class="p-4 text-sm text-gray-400 text-center">Loading...</div>
                </div>
            </div>

            <!-- Vehicles with a registered dashcam: on trip -->
            <div class="bg-white rounded-lg shadow border border-gray-200 flex flex-col min-h-[100px]" style="flex: 1 1 0%;">
                <div class="px-4 py-3 border-b border-gray-200 flex-shrink-0 flex items-center justify-between">
                    <h3 class="font-semibold text-sm text-gray-700">On trip (<span id="dashcam-trip-count">0</span>)</h3>
                    <button type="button" id="alert-sound-toggle" class="text-xs px-2 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">
                        Alert sound: on
                    </button>
                </div>
                <div id="dashcam-trip-list" class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-100">
                    <div class="p-4 text-sm text-gray-400 text-center">Loading...</div>
                </div>
            </div>

            <!-- Vehicles with a registered dashcam: not on trip -->
            <div class="bg-white rounded-lg shadow border border-gray-200 flex flex-col min-h-[100px]" style="flex: 1 1 0%;">
                <div class="px-4 py-3 border-b border-gray-200 flex-shrink-0">
                    <h3 class="font-semibold text-sm text-gray-700">Not on trip (<span id="dashcam-free-count">0</span>)</h3>
                </div>
                <div id="dashcam-free-list" class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-100">
                    <div class="p-4 text-sm text-gray-400 text-center">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2 text-xs text-yellow-800 flex-shrink-0">
        <i class="fas fa-info-circle mr-1"></i>
        Vehicle positions come from the dashcam in each vehicle. If a dashcam goes offline, the map keeps its
        <span class="font-semibold">last known location</span>.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const CLSU = [{{ \App\Services\GeofenceService::CAMPUS_LAT }}, {{ \App\Services\GeofenceService::CAMPUS_LNG }}];

    // OpenStreetMap tiles through Leaflet (open source, no API key)
    const map = L.map('fleet-map').setView(CLSU, 11);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    // Google-Maps-style teardrop pin with a white inner circle and a
    // centered Material Icons glyph, rendered as an inline SVG data URI
    // (no extra network request/asset needed).
    function pinIcon(glyphPath, color) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="34" height="44" viewBox="0 0 34 44">` +
            `<path d="M17 0C7.6 0 0 7.6 0 17c0 12.75 17 27 17 27s17-14.25 17-27C34 7.6 26.4 0 17 0z" fill="${color}"/>` +
            `<circle cx="17" cy="17" r="12" fill="#ffffff"/>` +
            `<g transform="translate(11,11) scale(0.5)"><path d="${glyphPath}" fill="${color}"/></g>` +
            `</svg>`;

        return L.divIcon({
            className: 'fleet-pin',
            html: svg,
            iconSize: [34, 44],
            iconAnchor: [17, 44],
            popupAnchor: [0, -40],
        });
    }

    // Material Icons glyph paths (24x24 viewBox) - "directions_bus" and "person".
    const BUS_GLYPH_PATH = 'M4 16c0 .88.39 1.67 1 2.22V20a1 1 0 001 1h1a1 1 0 001-1v-1h8v1a1 1 0 001 1h1a1 1 0 001-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM18 11H6V6h12v5z';
    const PERSON_GLYPH_PATH = 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z';

    // Motorpool Admin reference point (static - this is the office/campus
    // anchor, not a live-tracked person) - profile icon, distinct red so
    // it's never confused with a live driver/vehicle pin.
    L.marker(CLSU, {
        icon: pinIcon(PERSON_GLYPH_PATH, '#dc3545'),
        title: 'Motorpool Admin (CLSU)',
        zIndexOffset: -100,
    }).addTo(map);

    // Google Maps auto-detects its container's size via ResizeObserver, but
    // since this map sits inside a flex-based layout rather than a
    // fixed-height box, trigger a resize event as a safety net once the
    // surrounding layout has settled and again on every window resize
    // (sidebar toggle, orientation change, etc.)
    setTimeout(() => map.invalidateSize(), 150);
    window.addEventListener('resize', () => map.invalidateSize());

    let markers = {};
    let idleMarkers = {};
    let dashcamMarkers = {};

    // Moves a marker smoothly from where it is to the new position, instead of jumping
    function animateMarkerTo(marker, to, duration = 4000) {
        // Same target as before: keep the current animation, do not restart it
        if (marker.__to && marker.__to.lat === to.lat && marker.__to.lng === to.lng) return;
        marker.__to = { lat: to.lat, lng: to.lng };

        if (marker.__raf) cancelAnimationFrame(marker.__raf);
        const from = marker.getLatLng();
        const dist = map.distance(from, [to.lat, to.lng]);
        if (dist < 1) return; // no visible change
        const start = performance.now();
        const step = now => {
            const t = Math.min(1, (now - start) / duration);
            marker.setLatLng([
                from.lat + (to.lat - from.lat) * t,
                from.lng + (to.lng - from.lng) * t,
            ]);
            if (t < 1) marker.__raf = requestAnimationFrame(step);
            else marker.__raf = null;
        };
        marker.__raf = requestAnimationFrame(step);
    }

    // Alert sound is ON by default. Browsers block sound until the page gets a click or key press,
    // so sound is unlocked on the first interaction. Any alert before that plays right after it.
    let alertSoundOn = true;
    let alertAudioCtx = null;
    let alertBeepPending = false;

    function unlockAlertAudio() {
        try {
            if (!alertAudioCtx) alertAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (alertAudioCtx.state === 'suspended') alertAudioCtx.resume();
            if (alertBeepPending) {
                alertBeepPending = false;
                playAlertBeep();
            }
        } catch (e) {
            // Sound not available on this browser
        }
    }
    ['pointerdown', 'keydown', 'touchstart'].forEach(ev =>
        document.addEventListener(ev, unlockAlertAudio, { passive: true })
    );

    function playAlertBeep() {
        if (!alertSoundOn) return;
        try {
            if (!alertAudioCtx) alertAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (alertAudioCtx.state === 'suspended') {
                alertBeepPending = true; // plays on the next click or key press
                return;
            }
            const ctx = alertAudioCtx;
            [0, 0.4, 0.8].forEach(t => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.frequency.value = 880;
                gain.gain.value = 0.25;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(ctx.currentTime + t);
                osc.stop(ctx.currentTime + t + 0.25);
            });
        } catch (e) {
            // Sound not available on this browser
        }
    }

    document.getElementById('alert-sound-toggle').addEventListener('click', function () {
        alertSoundOn = !alertSoundOn;
        this.textContent = 'Alert sound: ' + (alertSoundOn ? 'on' : 'off');
        unlockAlertAudio();
        // Confirmation beep when turned on, so the sound can be checked without waiting for an alert
        if (alertSoundOn) {
            setTimeout(playAlertBeep, 150);
        }
    });

    function timeAgo(iso) {
        if (!iso) return 'no signal yet';
        const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
        if (seconds < 60) return seconds + 's ago';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
        return Math.floor(seconds / 3600) + 'h ago';
    }

    // Both driver marker groups (on-site and on-the-road) represent a
    // GPS-tracked vehicle position, so both use the vehicle/bus glyph -
    // only color distinguishes them. The profile icon is reserved for the
    // static Motorpool Admin reference pin created above.
    function vehicleIcon(status) {
        let color = '#1e6031';

        if (status === 'location_off') {
            color = '#dc2626';
        } else if (status === 'signal_lost') {
            color = '#f59e0b';
        } else if (status !== 'live') {
            color = '#9ca3af';
        }

        return pinIcon(BUS_GLYPH_PATH, color);
    }

    // Drivers on site use a distinct blue pin so they're never confused
    // with an actual moving trip on the map.
    // Dashcam-tracked vehicles: green when online, grey when the dashcam has gone quiet
    function dashcamIcon(online) {
        return pinIcon(BUS_GLYPH_PATH, online ? '#16a34a' : '#9ca3af');
    }

    function idleDriverIcon(isLive) {
        return pinIcon(BUS_GLYPH_PATH, isLive ? '#2563eb' : '#9ca3af');
    }

    function refresh() {
        fetch('{{ route('api.fleet.locations') }}')
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('ongoing-list');
                document.getElementById('ongoing-count').textContent = data.ongoing.length;
                document.getElementById('last-refreshed').textContent = new Date().toLocaleTimeString();
                document.getElementById('live-indicator').className = 'inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-800';
                document.getElementById('live-indicator').innerHTML = '<i class="fas fa-circle mr-1" style="font-size:6px;"></i> Live';

                const seenIds = [];

                // Driver location alerts are off: location now comes from the dashcam only
                const alerts = [];
                const alertBox = document.getElementById('location-alerts');
                const alertList = document.getElementById('location-alert-list');
                const currentAlertKeys = alerts.map(alert => `${alert.trip_ticket_id}:${alert.status}`).sort();

                if (alerts.length > 0) {
                    alertBox.classList.remove('hidden');
                    alertList.innerHTML = alerts.map(alert => `
                        <div class="flex flex-wrap items-center gap-1">
                            <strong>${alert.driver_name}</strong>
                            <span>&mdash; ${alert.status === 'location_off' ? 'Location sharing OFF' : 'GPS signal lost'}</span>
                            <span class="text-red-500">(last location ${timeAgo(alert.last_update)})</span>
                        </div>
                    `).join('');
                } else {
                    alertBox.classList.add('hidden');
                    alertList.innerHTML = '';
                }

                window.__driverLocationAlertKeys = window.__driverLocationAlertKeys || new Set();
                currentAlertKeys.forEach(key => {
                    if (!window.__driverLocationAlertKeys.has(key) && typeof Swal !== 'undefined') {
                        const alert = alerts.find(item => `${item.trip_ticket_id}:${item.status}` === key);
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'warning',
                            title: alert?.status === 'location_off' ? 'Driver location turned off' : 'Driver GPS signal lost',
                            text: alert ? `${alert.driver_name} — ${alert.ticket_number}` : 'A driver location needs attention.',
                            showConfirmButton: false,
                            timer: 7000,
                            timerProgressBar: true,
                        });
                    }
                });
                window.__driverLocationAlertKeys = new Set(currentAlertKeys);

                if (data.ongoing.length === 0) {
                    list.innerHTML = '<div class="p-4 text-sm text-gray-400 text-center">No trips are currently on the road.</div>';
                } else {
                    list.innerHTML = '';
                }

                data.ongoing.forEach(trip => {
                    if (trip.latitude && trip.longitude) {
                        seenIds.push(trip.trip_ticket_id);
                        const popupHtml = `
                            <div style="min-width:180px">
                                <strong>${trip.driver_name}</strong><br>
                                ${trip.vehicle}<br>
                                To: ${trip.destination}<br>
                                ${trip.speed_kph && trip.location_status === 'live' ? trip.speed_kph.toFixed(0) + ' km/h &middot; ' : ''}${timeAgo(trip.last_update)}
                                ${trip.location_status === 'location_off' ? '<br><span style="color:#dc2626;font-weight:600;">Location OFF — showing last location</span>' : ''}
                                ${trip.location_status === 'signal_lost' ? '<br><span style="color:#d97706;font-weight:600;">GPS signal lost — showing last location</span>' : ''}
                                ${trip.auto_started ? '<br><span style="color:#b45309;">Auto-started</span>' : ''}
                            </div>`;
                        const position = [trip.latitude, trip.longitude];

                        if (markers[trip.trip_ticket_id]) {
                            markers[trip.trip_ticket_id].setLatLng(position);
                            markers[trip.trip_ticket_id].setIcon(vehicleIcon(trip.location_status));
                            markers[trip.trip_ticket_id].setPopupContent(popupHtml);
                        } else {
                            markers[trip.trip_ticket_id] = L.marker(position, { icon: vehicleIcon(trip.location_status) })
                                .addTo(map)
                                .bindPopup(popupHtml);
                        }
                    }

                    const row = document.createElement('div');
                    row.className = 'p-3 hover:bg-gray-50 cursor-pointer';
                    row.innerHTML = `
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">${trip.driver_name}</p>
                                <p class="text-xs text-gray-500 truncate">${trip.vehicle}</p>
                                <p class="text-xs text-gray-500 truncate">To: ${trip.destination}</p>
                                <p class="text-xs ${trip.location_status === 'live' ? 'text-green-600' : (trip.location_status === 'location_off' ? 'text-red-600' : 'text-amber-600')} mt-1">${trip.location_status === 'live' ? 'Live' : (trip.location_status === 'location_off' ? 'Location OFF' : 'GPS signal lost')} &middot; Last location ${timeAgo(trip.last_update)}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                ${trip.auto_started ? '<span class="inline-block px-1.5 py-0.5 text-[10px] rounded bg-yellow-100 text-yellow-800">Auto</span>' : ''}
                                <button type="button" data-end-trip="${trip.trip_ticket_id}" title="End Trip"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded bg-red-600 hover:bg-red-700 text-white">
                                    <i class="fas fa-stop"></i> End
                                </button>
                            </div>
                        </div>`;
                    row.addEventListener('click', () => {
                        if (trip.latitude && trip.longitude) {
                            map.setView([trip.latitude, trip.longitude], 15);
                            if (markers[trip.trip_ticket_id]) {
                                markers[trip.trip_ticket_id].openPopup();
                            }
                        }
                    });
                    row.querySelector('[data-end-trip]').addEventListener('click', (e) => {
                        e.stopPropagation();
                        endTrip(trip.trip_ticket_id);
                    });
                    list.appendChild(row);
                });

                // Remove markers for trips no longer ongoing
                Object.keys(markers).forEach(id => {
                    if (!seenIds.includes(parseInt(id))) {
                        markers[id].remove();
                        delete markers[id];
                    }
                });

                // --- Vehicles with a registered dashcam, split by trip status ---
                const dashcams = Array.isArray(data.dashcams) ? data.dashcams : [];
                const onTrip = dashcams.filter(d => d.trip);
                const notOnTrip = dashcams.filter(d => !d.trip);
                document.getElementById('dashcam-trip-count').textContent = onTrip.length;
                document.getElementById('dashcam-free-count').textContent = notOnTrip.length;

                // GPS status: live, stale (no fix for a while), or no fix yet
                const gpsBadge = d => {
                    if (!d.gps_at) return '<span class="text-gray-400">No GPS yet</span>';
                    const ageMin = Math.floor((Date.now() - new Date(d.gps_at).getTime()) / 60000);
                    return ageMin <= 3
                        ? '<span class="text-green-700">GPS live</span>'
                        : `<span class="text-amber-600">GPS stale · ${ageMin} min</span>`;
                };

                // Current speed from the dashcam's GPS. Starts at 0 and changes as the vehicle moves.
                const currentSpeed = d => Math.round(d.speed_kph ?? 0) + ' km/h';

                const dashcamStatus = d => {
                    if (!d.online) return '<span class="text-gray-500">Offline</span>';
                    return d.latitude != null
                        ? '<span class="text-green-700">Online</span>'
                        : '<span class="text-amber-600">Online · waiting for GPS</span>';
                };

                const renderDashcamItem = d => `
                    <div class="p-3 cursor-pointer hover:bg-gray-50" data-dashcam-id="${d.dashcam_id}">
                        <div class="font-semibold text-sm">${d.plate_number} <span class="text-xs font-normal text-gray-500">${d.vehicle}</span></div>
                        <div class="text-xs mt-1">
                            <span class="font-semibold">${currentSpeed(d)}</span> &middot; Odometer ${Number(d.odometer_km).toLocaleString()} km 
                            &middot; ${dashcamStatus(d)} &middot; ${gpsBadge(d)}
                        </div>
                        ${d.trip ? `<div class="text-xs mt-1 text-sky-700 font-semibold">${d.trip.ticket_number}${d.trip.destination ? ' &rarr; ' + d.trip.destination : ''}</div>` : ''}
                        ${d.trip && d.trip.driver ? `<div class="text-xs text-gray-500">Driver: ${d.trip.driver}</div>` : ''}
                    </div>`;

                const emptyText = msg => `<div class="p-4 text-sm text-gray-400 text-center">${msg}</div>`;
                const tripList = document.getElementById('dashcam-trip-list');
                const freeList = document.getElementById('dashcam-free-list');
                tripList.innerHTML = onTrip.length ? onTrip.map(renderDashcamItem).join('') : emptyText('No vehicle is on a trip right now.');
                freeList.innerHTML = notOnTrip.length ? notOnTrip.map(renderDashcamItem).join('') : emptyText('No vehicle outside a trip.');

                [tripList, freeList].forEach(list => {
                    list.querySelectorAll('[data-dashcam-id]').forEach(el => {
                        el.addEventListener('click', () => {
                            const marker = dashcamMarkers[el.dataset.dashcamId];
                            if (marker) {
                                map.setView(marker.getLatLng(), 15);
                                marker.openPopup();
                            } else {
                                alert('This vehicle has no GPS location yet.');
                            }
                        });
                    });
                });

                // Alert when a vehicle on a trip has stopped sending GPS
                window.__gpsAlerted = window.__gpsAlerted || new Set();
                const nowAlerts = new Set();
                onTrip.forEach(d => {
                    const stale = !d.gps_at || (Date.now() - new Date(d.gps_at).getTime()) / 60000 > 3;
                    if (stale) nowAlerts.add(d.dashcam_id);
                });
                nowAlerts.forEach(id => {
                    if (window.__gpsAlerted.has(id)) return;
                    window.__gpsAlerted.add(id);
                    const d = onTrip.find(x => x.dashcam_id === id);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'warning',
                            title: `${d.plate_number} on trip: no GPS signal`,
                            text: 'The dashcam stopped sending location.',
                            showConfirmButton: false, timer: 8000, timerProgressBar: true,
                        });
                    }
                    playAlertBeep();
                });
                // Vehicles whose GPS came back are no longer alerted
                [...window.__gpsAlerted].forEach(id => { if (!nowAlerts.has(id)) window.__gpsAlerted.delete(id); });

                const seenDashcamIds = [];
                dashcams.forEach(d => {
                    if (d.latitude == null || d.longitude == null) return;
                    seenDashcamIds.push(d.dashcam_id);

                    const position = [d.latitude, d.longitude];
                    const tripPopup = d.trip
                        ? '<br><b>On trip ' + d.trip.ticket_number + '</b>' + (d.trip.destination ? ' &rarr; ' + d.trip.destination : '') + (d.trip.driver ? '<br>Driver: ' + d.trip.driver : '')
                        : '<br>No active trip';
                    const popupHtml = `
                        <div style="min-width:180px">
                            <strong>${d.plate_number}</strong><br>
                            ${d.vehicle}<br>
                            ${currentSpeed(d)} &middot; Odometer ${Number(d.odometer_km).toLocaleString()} km<br>
                            ${timeAgo(d.last_update)}<br>${gpsBadge(d)}
                            ${tripPopup}
                            ${d.online ? '' : '<br><span style="color:#6b7280;font-weight:600;">Dashcam offline — last known location</span>'}
                        </div>`;

                    if (dashcamMarkers[d.dashcam_id]) {
                        animateMarkerTo(dashcamMarkers[d.dashcam_id], { lat: d.latitude, lng: d.longitude });
                        dashcamMarkers[d.dashcam_id].setIcon(dashcamIcon(d.online));
                        dashcamMarkers[d.dashcam_id].setPopupContent(popupHtml);
                    } else {
                        dashcamMarkers[d.dashcam_id] = L.marker(position, { icon: dashcamIcon(d.online), title: d.plate_number })
                            .addTo(map)
                            .bindPopup(popupHtml);
                    }
                });

                Object.keys(dashcamMarkers).forEach(id => {
                    if (!seenDashcamIds.includes(parseInt(id))) {
                        dashcamMarkers[id].remove();
                        delete dashcamMarkers[id];
                    }
                });

                // Opened from the Dashcams page ("View on map"): center on that vehicle once.
                const focusId = new URLSearchParams(window.location.search).get('dashcam');
                if (focusId && !window.__dashcamFocused && dashcamMarkers[focusId]) {
                    window.__dashcamFocused = true;
                    map.setView(dashcamMarkers[focusId].getLatLng(), 16);
                    dashcamMarkers[focusId].openPopup();
                }

                // --- Drivers on site: sharing location, not currently on a trip ---
                const idleList = document.getElementById('idle-list');
                document.getElementById('idle-count').textContent = data.idle.length;
                const seenIdleIds = [];

                if (data.idle.length === 0) {
                    idleList.innerHTML = '<div class="p-4 text-sm text-gray-400 text-center">No drivers are currently on site.</div>';
                } else {
                    idleList.innerHTML = '';
                }

                data.idle.forEach(driver => {
                    if (driver.latitude && driver.longitude) {
                        seenIdleIds.push(driver.driver_id);
                        const popupHtml = `
                            <div style="min-width:160px">
                                <strong>${driver.driver_name}</strong><br>
                                <span style="color:#2563eb;">On site &middot; not on a trip</span><br>
                                ${timeAgo(driver.last_update)}
                            </div>`;
                        const position = [driver.latitude, driver.longitude];

                        if (idleMarkers[driver.driver_id]) {
                            idleMarkers[driver.driver_id].setLatLng(position);
                            idleMarkers[driver.driver_id].setIcon(idleDriverIcon(driver.is_live));
                            idleMarkers[driver.driver_id].setPopupContent(popupHtml);
                        } else {
                            idleMarkers[driver.driver_id] = L.marker(position, { icon: idleDriverIcon(driver.is_live) })
                                .addTo(map)
                                .bindPopup(popupHtml);
                        }
                    }

                    const row = document.createElement('div');
                    row.className = 'p-3 hover:bg-gray-50 cursor-pointer';
                    row.innerHTML = `
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">${driver.driver_name}</p>
                                <p class="text-xs text-blue-600">On site &middot; not on a trip</p>
                            </div>
                            <p class="text-xs ${driver.is_live ? 'text-green-600' : 'text-gray-400'} flex-shrink-0">${driver.is_live ? 'Live' : 'Stale'} &middot; ${timeAgo(driver.last_update)}</p>
                        </div>`;
                    row.addEventListener('click', () => {
                        if (driver.latitude && driver.longitude) {
                            map.setView([driver.latitude, driver.longitude], 15);
                            if (idleMarkers[driver.driver_id]) {
                                idleMarkers[driver.driver_id].openPopup();
                            }
                        }
                    });
                    idleList.appendChild(row);
                });

                // Remove markers for drivers no longer on site
                Object.keys(idleMarkers).forEach(id => {
                    if (!seenIdleIds.includes(parseInt(id))) {
                        idleMarkers[id].remove();
                        delete idleMarkers[id];
                    }
                });
            })
            .catch(err => {
                // Show the real error so it can be reported (also logged to the browser Console)
                console.error('Vehicle Monitoring error:', err);
                const msg = (err && err.message) ? err.message : String(err);
                document.getElementById('live-indicator').className = 'inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-800';
                document.getElementById('live-indicator').innerHTML = '<i class="fas fa-circle mr-1" style="font-size:6px;"></i> Offline';
                document.getElementById('live-indicator').title = msg;
                const note = document.getElementById('dashcam-trip-list');
                if (note) note.innerHTML = '<div class="p-4 text-xs text-red-700">Error: ' + msg + '</div>';
            });
    }

    refresh();
    setInterval(refresh, 5000);
});

// Ends an on-going trip (marks it Completed) with optional odometer readings.
// This lives here now that the duplicate On-Going tab on the Trip Tickets
// page has been removed - this fleet-monitoring page is the single place
// for managing trips that are on the road.
async function endTrip(ticketId) {
    const result = await Swal.fire({
        title: 'End Trip',
        html: `
            <div class="text-left space-y-3">
                <p class="text-sm text-gray-600">Enter the odometer readings for this completed trip. Leave them blank to use the estimated distance.</p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Odometer Start (km)</label>
                    <input id="swal-odometer-start" type="number" min="0" class="swal2-input" placeholder="e.g. 1250">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Odometer End (km)</label>
                    <input id="swal-odometer-end" type="number" min="0" class="swal2-input" placeholder="e.g. 1400">
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, end it!',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const start = document.getElementById('swal-odometer-start').value;
            const end = document.getElementById('swal-odometer-end').value;

            if (start && end && Number(end) < Number(start)) {
                Swal.showValidationMessage('Odometer end must be greater than or equal to odometer start.');
                return false;
            }

            return {
                odometer_start_km: start,
                odometer_end_km: end
            };
        }
    });

    if (result.isConfirmed) {
        Swal.fire({
            title: 'Ending Trip...',
            html: '<div class="text-sm">Updating trip status and saving the odometer readings...</div>',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        setTimeout(() => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/trip-tickets/${ticketId}/status`;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken;
            form.appendChild(tokenInput);

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PATCH';
            form.appendChild(methodInput);

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = 'Completed';
            form.appendChild(statusInput);

            if (result.value?.odometer_start_km) {
                const startInput = document.createElement('input');
                startInput.type = 'hidden';
                startInput.name = 'odometer_start_km';
                startInput.value = result.value.odometer_start_km;
                form.appendChild(startInput);
            }

            if (result.value?.odometer_end_km) {
                const endInput = document.createElement('input');
                endInput.type = 'hidden';
                endInput.name = 'odometer_end_km';
                endInput.value = result.value.odometer_end_km;
                form.appendChild(endInput);
            }

            document.body.appendChild(form);
            form.submit();
        }, 2000);
    }
}
</script>
@endsection