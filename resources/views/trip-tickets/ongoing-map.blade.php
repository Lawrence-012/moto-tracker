@extends('layouts.motorpool-admin')

@section('content')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps.api_key') }}"></script>
<script src="{{ asset('js/gmaps-helpers.js') }}"></script>

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
            <h2 class="text-lg sm:text-2xl font-bold" style="color: #1e6031;">Driver Monitoring</h2>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Monitor drivers currently on approved trips, including the last known location when GPS sharing is turned off or unavailable.</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-500 flex-shrink-0">
            <span id="live-indicator" class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-800">
                <i class="fas fa-circle mr-1" style="font-size:6px;"></i> Live
            </span>
            <span>Last refreshed: <span id="last-refreshed">--</span></span>
        </div>
    </div>

    <div id="location-alerts" class="hidden mb-3 bg-red-50 border border-red-200 rounded-lg px-4 py-3 flex-shrink-0">
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
            <div id="fleet-map" class="w-full h-full"></div>
        </div>

        <!-- Side panel: ~1/4 of the available space, split between the two lists -->
        <div class="flex flex-col gap-4 min-h-[240px]" style="flex: 1 1 0%;">
            <!-- Trips on the road -->
            <div class="bg-white rounded-lg shadow border border-gray-200 flex flex-col min-h-[100px]" style="flex: 1 1 0%;">
                <div class="px-4 py-3 border-b border-gray-200 flex-shrink-0">
                    <h3 class="font-semibold text-sm text-gray-700">Drivers on trip (<span id="ongoing-count">0</span>)</h3>
                </div>
                <div id="ongoing-list" class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-100">
                    <div class="p-4 text-sm text-gray-400 text-center">Loading...</div>
                </div>
            </div>

            <!-- Drivers on site: sharing location, not currently on a trip -->
            <div class="bg-white rounded-lg shadow border border-gray-200 flex flex-col min-h-[100px]" style="flex: 1 1 0%;">
                <div class="px-4 py-3 border-b border-gray-200 flex-shrink-0">
                    <h3 class="font-semibold text-sm text-gray-700">Drivers on site (<span id="idle-count">0</span>)</h3>
                </div>
                <div id="idle-list" class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-100">
                    <div class="p-4 text-sm text-gray-400 text-center">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2 text-xs text-yellow-800 flex-shrink-0">
        <i class="fas fa-info-circle mr-1"></i>
        Driver positions are refreshed automatically. If a driver turns off location sharing or stops sending GPS updates during a trip,
        the system keeps the <span class="font-semibold">last known location</span> on the map and alerts the admin.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const CLSU = { lat: {{ \App\Services\GeofenceService::CAMPUS_LAT }}, lng: {{ \App\Services\GeofenceService::CAMPUS_LNG }} };

    const map = gmCreateMap('fleet-map', CLSU, 11);

    // Google-Maps-style teardrop pin with a white inner circle and a
    // centered Material Icons glyph, rendered as an inline SVG data URI
    // (no extra network request/asset needed).
    function pinIcon(glyphPath, color) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="34" height="44" viewBox="0 0 34 44">` +
            `<path d="M17 0C7.6 0 0 7.6 0 17c0 12.75 17 27 17 27s17-14.25 17-27C34 7.6 26.4 0 17 0z" fill="${color}"/>` +
            `<circle cx="17" cy="17" r="12" fill="#ffffff"/>` +
            `<g transform="translate(11,11) scale(0.5)"><path d="${glyphPath}" fill="${color}"/></g>` +
            `</svg>`;

        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(34, 44),
            anchor: new google.maps.Point(17, 44),
        };
    }

    // Material Icons glyph paths (24x24 viewBox) - "directions_bus" and "person".
    const BUS_GLYPH_PATH = 'M4 16c0 .88.39 1.67 1 2.22V20a1 1 0 001 1h1a1 1 0 001-1v-1h8v1a1 1 0 001 1h1a1 1 0 001-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM18 11H6V6h12v5z';
    const PERSON_GLYPH_PATH = 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z';

    // Motorpool Admin reference point (static - this is the office/campus
    // anchor, not a live-tracked person) - profile icon, distinct red so
    // it's never confused with a live driver/vehicle pin.
    new google.maps.Marker({
        position: CLSU,
        map,
        icon: pinIcon(PERSON_GLYPH_PATH, '#dc3545'),
        title: 'Motorpool Admin (CLSU)',
        zIndex: 1,
    });

    // Google Maps auto-detects its container's size via ResizeObserver, but
    // since this map sits inside a flex-based layout rather than a
    // fixed-height box, trigger a resize event as a safety net once the
    // surrounding layout has settled and again on every window resize
    // (sidebar toggle, orientation change, etc.)
    setTimeout(() => google.maps.event.trigger(map, 'resize'), 150);
    window.addEventListener('resize', () => google.maps.event.trigger(map, 'resize'));

    let markers = {};
    let markerInfoWindows = {};
    let idleMarkers = {};
    let idleInfoWindows = {};

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

                const alerts = Array.isArray(data.location_alerts) ? data.location_alerts : [];
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
                        const position = { lat: trip.latitude, lng: trip.longitude };

                        if (markers[trip.trip_ticket_id]) {
                            markers[trip.trip_ticket_id].setPosition(position);
                            markers[trip.trip_ticket_id].setIcon(vehicleIcon(trip.location_status));
                            markerInfoWindows[trip.trip_ticket_id].setContent(popupHtml);
                        } else {
                            const marker = new google.maps.Marker({ position, map, icon: vehicleIcon(trip.location_status) });
                            markerInfoWindows[trip.trip_ticket_id] = gmBindInfoWindow(map, marker, popupHtml);
                            markers[trip.trip_ticket_id] = marker;
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
                            map.setCenter({ lat: trip.latitude, lng: trip.longitude });
                            map.setZoom(15);
                            if (markers[trip.trip_ticket_id]) {
                                markerInfoWindows[trip.trip_ticket_id].open({ anchor: markers[trip.trip_ticket_id], map });
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
                        markers[id].setMap(null);
                        delete markers[id];
                        delete markerInfoWindows[id];
                    }
                });

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
                        const position = { lat: driver.latitude, lng: driver.longitude };

                        if (idleMarkers[driver.driver_id]) {
                            idleMarkers[driver.driver_id].setPosition(position);
                            idleMarkers[driver.driver_id].setIcon(idleDriverIcon(driver.is_live));
                            idleInfoWindows[driver.driver_id].setContent(popupHtml);
                        } else {
                            const marker = new google.maps.Marker({ position, map, icon: idleDriverIcon(driver.is_live) });
                            idleInfoWindows[driver.driver_id] = gmBindInfoWindow(map, marker, popupHtml);
                            idleMarkers[driver.driver_id] = marker;
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
                            map.setCenter({ lat: driver.latitude, lng: driver.longitude });
                            map.setZoom(15);
                            if (idleMarkers[driver.driver_id]) {
                                idleInfoWindows[driver.driver_id].open({ anchor: idleMarkers[driver.driver_id], map });
                            }
                        }
                    });
                    idleList.appendChild(row);
                });

                // Remove markers for drivers no longer on site
                Object.keys(idleMarkers).forEach(id => {
                    if (!seenIdleIds.includes(parseInt(id))) {
                        idleMarkers[id].setMap(null);
                        delete idleMarkers[id];
                        delete idleInfoWindows[id];
                    }
                });
            })
            .catch(() => {
                document.getElementById('live-indicator').className = 'inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-800';
                document.getElementById('live-indicator').innerHTML = '<i class="fas fa-circle mr-1" style="font-size:6px;"></i> Offline';
            });
    }

    refresh();
    setInterval(refresh, 15000);
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