{{-- Location tracking: consent modal + background GPS reporting.
     Included on every driver page via layouts/driver.blade.php so it
     keeps sending pings (and can auto-start a trip) no matter which
     page the driver is on. --}}
@php
    $driverRecord = auth()->check() ? \App\Models\Driver::where('user_id', auth()->id())->first() : null;
    // Only need the vehicle list when the odometer prompt can actually show.
    $vehiclesForOdometer = ($driverRecord && is_null($driverRecord->initial_odometer_prompted_at))
        ? \App\Models\Vehicle::where('status', '!=', 'Inactive')->orderBy('plate_number')->get(['id', 'plate_number', 'model', 'mileage'])
        : collect();
@endphp
@if($driverRecord)
<div id="location-consent-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-5">
        <div class="flex items-start gap-3">
            <div class="bg-green-100 rounded-full p-2 flex-shrink-0">
                <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900">Enable Location Sharing?</h3>
                <p class="text-sm text-gray-600 mt-1">
                    Turning this on lets the Motorpool office see your vehicle's live position on
                    the fleet map, and lets your approved trip start automatically the moment you
                    leave CLSU. You can turn it off anytime from this dialog.
                </p>
            </div>
        </div>
        <div class="mt-4 flex gap-2 justify-end">
            <button id="location-consent-decline" type="button" class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                Not now
            </button>
            <button id="location-consent-accept" type="button" class="px-4 py-2 text-sm rounded-lg bg-[#1e6031] text-white hover:bg-[#164f2a]">
                Enable Location
            </button>
        </div>
    </div>
</div>

@if($driverRecord && is_null($driverRecord->initial_odometer_prompted_at))
<!-- Vehicle + initial odometer prompt: shown at login so mileage tracking
     starts from an accurate baseline. Skippable, but skipping only
     dismisses it for this login session - it reappears next login. -->
<div id="initial-odometer-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-5">
        <div class="flex items-start gap-3">
            <div class="bg-green-100 rounded-full p-2 flex-shrink-0">
                <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m-6 0H7a2 2 0 01-2-2V9a2 2 0 012-2h1m8 10h1a2 2 0 002-2V9a2 2 0 00-2-2h-1" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900">Vehicle &amp; Odometer</h3>
                <p class="text-sm text-gray-600 mt-1">
                    Which vehicle are you driving today, and what's its current odometer reading?
                    This keeps maintenance tracking accurate. You can skip - we'll ask again next
                    time you log in.
                </p>
            </div>
        </div>
        <form id="initial-odometer-form" class="mt-4 space-y-3">
            <div>
                <label for="initial-odometer-vehicle" class="block text-xs font-medium text-gray-700 mb-1">Vehicle</label>
                <select id="initial-odometer-vehicle" required class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031]">
                    <option value="" disabled selected>Select vehicle</option>
                    @foreach($vehiclesForOdometer as $v)
                        <option value="{{ $v->id }}" data-mileage="{{ $v->mileage ?? '' }}">
                            {{ $v->plate_number }} - {{ $v->model }}
                        </option>
                    @endforeach
                </select>
                <p id="initial-odometer-last-known" class="text-xs text-gray-400 mt-1 hidden"></p>
            </div>
            <div>
                <label for="initial-odometer-km" class="block text-xs font-medium text-gray-700 mb-1">Current odometer (km)</label>
                <input type="number" id="initial-odometer-km" min="0" step="1" required
                       class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031]"
                       placeholder="e.g. 45230">
            </div>
            <div class="flex gap-2 justify-end pt-1">
                <button id="initial-odometer-skip" type="button" class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    Skip for now
                </button>
                <button id="initial-odometer-submit" type="submit" class="px-4 py-2 text-sm rounded-lg bg-[#1e6031] text-white hover:bg-[#164f2a]">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Small persistent status pill so the driver always knows sharing state.
     Positioned above the fixed bottom nav bar (not at the very edge of the
     screen) so it never sits on top of the Trips/Schedule/Profile buttons. -->
<div id="location-status-pill" class="fixed right-4 z-[9998] hidden" style="bottom: 76px;">
    <button id="location-status-btn" type="button" class="flex items-center gap-2 bg-white shadow-lg border border-gray-200 rounded-full pl-3 pr-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
        <span id="location-status-dot" class="h-2.5 w-2.5 rounded-full bg-gray-300"></span>
        <span id="location-status-text">Location off</span>
    </button>
</div>

<!-- Install App pill: lets a driver download/install Moto-Track like a native app.
     Same reasoning - kept clear of the bottom nav bar. -->
<div id="install-app-pill" class="fixed left-4 z-[9998] hidden" style="bottom: 76px;">
    <button id="install-app-btn" type="button" class="flex items-center gap-2 bg-[#1e6031] shadow-lg rounded-full pl-3 pr-4 py-2 text-xs font-semibold text-white hover:bg-[#164f2a]">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
        </svg>
        <span>Download App</span>
    </button>
</div>

<!-- iOS "Add to Home Screen" instructions (iOS Safari has no install prompt API) -->
<div id="ios-install-modal" class="fixed inset-0 z-[9999] hidden items-end sm:items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-5 mb-4 sm:mb-0">
        <div class="flex items-start gap-3">
            <div class="bg-green-100 rounded-full p-2 flex-shrink-0">
                <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900">Install Moto-Track</h3>
                <p class="text-sm text-gray-600 mt-1">To add this app to your Home Screen on iPhone/iPad:</p>
                <ol class="text-sm text-gray-600 mt-2 space-y-1.5 list-decimal list-inside">
                    <li>Tap the <strong>Share</strong> icon <span aria-hidden="true">⬆️</span> in Safari's toolbar</li>
                    <li>Scroll down and tap <strong>Add to Home Screen</strong></li>
                    <li>Tap <strong>Add</strong> to confirm</li>
                </ol>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button id="ios-install-close" type="button" class="px-4 py-2 text-sm rounded-lg bg-[#1e6031] text-white hover:bg-[#164f2a]">
                Got it
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    // Register the service worker (scoped to /driver/ only) so the driver
    // app becomes installable and works while briefly offline. This whole
    // partial only renders for an authenticated driver in the first place.
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('{{ asset('sw.js') }}', { scope: '{{ url('/driver') }}/' }).catch(() => {});
    }

    const installPill = document.getElementById('install-app-pill');
    const installBtn = document.getElementById('install-app-btn');
    const iosModal = document.getElementById('ios-install-modal');

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent) && !window.MSStream;

    let deferredInstallPrompt = null;

    if (!isStandalone) {
        // Chrome/Edge/Android: browser fires this when install criteria are met.
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredInstallPrompt = e;
            installPill.classList.remove('hidden');
        });

        // iOS Safari never fires beforeinstallprompt, so show the pill anyway
        // and explain the manual steps when tapped.
        if (isIos) {
            installPill.classList.remove('hidden');
        }

        window.addEventListener('appinstalled', function () {
            installPill.classList.add('hidden');
            deferredInstallPrompt = null;
        });
    }

    installBtn.addEventListener('click', function () {
        if (deferredInstallPrompt) {
            deferredInstallPrompt.prompt();
            deferredInstallPrompt.userChoice.finally(function () {
                deferredInstallPrompt = null;
            });
        } else if (isIos) {
            iosModal.classList.remove('hidden');
            iosModal.classList.add('flex');
        }
    });

    document.getElementById('ios-install-close').addEventListener('click', function () {
        iosModal.classList.add('hidden');
        iosModal.classList.remove('flex');
    });
})();
</script>

@if($driverRecord && is_null($driverRecord->initial_odometer_prompted_at))
<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const modal = document.getElementById('initial-odometer-modal');
    const vehicleSelect = document.getElementById('initial-odometer-vehicle');
    const kmInput = document.getElementById('initial-odometer-km');
    const lastKnownHint = document.getElementById('initial-odometer-last-known');
    const submitBtn = document.getElementById('initial-odometer-submit');
    const skipBtn = document.getElementById('initial-odometer-skip');
    const form = document.getElementById('initial-odometer-form');

    function showOdometerModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function hideOdometerModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function submitOdometer(payload) {
        return fetch('{{ route('driver.odometer.initial') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });
    }

    vehicleSelect.addEventListener('change', function () {
        const opt = vehicleSelect.options[vehicleSelect.selectedIndex];
        const mileage = opt ? opt.getAttribute('data-mileage') : '';
        if (mileage) {
            lastKnownHint.textContent = 'Last recorded: ' + mileage + ' km';
            lastKnownHint.classList.remove('hidden');
        } else {
            lastKnownHint.classList.add('hidden');
        }
    });

    skipBtn.addEventListener('click', function () {
        skipBtn.disabled = true;
        submitOdometer({ skip: true }).finally(function () {
            skipBtn.disabled = false;
            hideOdometerModal();
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitOdometer({
            vehicle_id: vehicleSelect.value,
            odometer_km: kmInput.value
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            hideOdometerModal();
            if (data && data.success && window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'Odometer saved',
                    text: 'Thanks! The vehicle\'s odometer has been updated.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        })
        .catch(function () {
            if (window.Swal) {
                Swal.fire({ icon: 'error', title: 'Something went wrong', text: 'Please try again.' });
            }
        })
        .finally(function () { submitBtn.disabled = false; });
    });

    // Exposed so the location-tracking script (loaded above) can trigger
    // this after its own prompt is resolved, keeping only one modal open
    // at a time. Shown once per login session; if skipped, sessionStorage
    // is NOT set to "answered" - only to "shown this session" - so a brand
    // new login session (new sessionStorage) prompts again.
    window.maybeShowOdometerPrompt = function () {
        if (!sessionStorage.getItem('odometerPromptShownThisSession')) {
            sessionStorage.setItem('odometerPromptShownThisSession', '1');
            showOdometerModal();
        }
    };
})();
</script>
@endif

<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const driverSharingEnabled = @json((bool) $driverRecord->location_sharing_enabled);
    const hasDecidedBefore = @json(!is_null($driverRecord->location_consent_at));

    const modal = document.getElementById('location-consent-modal');
    const pill = document.getElementById('location-status-pill');
    const dot = document.getElementById('location-status-dot');
    const text = document.getElementById('location-status-text');

    let watchId = null;
    let pingTimer = null;

    function showModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function hideModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    function setStatus(state) {
        // state: 'on' | 'off' | 'denied' | 'insecure' | 'unsupported' | 'unavailable' | 'timeout'
        pill.classList.remove('hidden');
        if (state === 'on') {
            dot.className = 'h-2.5 w-2.5 rounded-full bg-green-500 animate-pulse';
            text.textContent = 'Location sharing on';
        } else if (state === 'denied') {
            dot.className = 'h-2.5 w-2.5 rounded-full bg-red-500';
            text.textContent = 'Location permission blocked';
        } else if (state === 'insecure') {
            // Geolocation is only available on HTTPS (or localhost). Serving
            // the site over plain HTTP - very common on a LAN IP address
            // used to test the driver PWA on a phone - makes the browser
            // refuse the request outright, which looks identical to a real
            // "denied" to the driver but has nothing to do with their
            // permission choice.
            dot.className = 'h-2.5 w-2.5 rounded-full bg-red-500';
            text.textContent = 'Location needs HTTPS';
            console.warn('[MPIS] Geolocation was blocked because this page is not served over HTTPS (or localhost). Access the driver app via an https:// URL to enable location sharing.');
        } else if (state === 'unsupported') {
            dot.className = 'h-2.5 w-2.5 rounded-full bg-red-500';
            text.textContent = 'Location not supported';
        } else if (state === 'unavailable') {
            dot.className = 'h-2.5 w-2.5 rounded-full bg-amber-500';
            text.textContent = 'Location signal unavailable';
        } else if (state === 'timeout') {
            dot.className = 'h-2.5 w-2.5 rounded-full bg-amber-500';
            text.textContent = 'Location timed out';
        } else {
            dot.className = 'h-2.5 w-2.5 rounded-full bg-gray-300';
            text.textContent = 'Location off';
        }
    }

    // Maps a GeolocationPositionError to the right status state, instead of
    // collapsing every possible failure into a generic "denied" - a stale
    // GPS fix (code 2) or a slow fix (code 3) should not tell the driver
    // their permission is blocked when it isn't.
    function handleGeoError(err) {
        if (err && err.code === 1) {
            setStatus('denied');
        } else if (err && err.code === 2) {
            setStatus('unavailable');
        } else if (err && err.code === 3) {
            setStatus('timeout');
        } else {
            setStatus('denied');
        }
    }

    function sendConsent(enabled) {
        return fetch('{{ route('driver.location.consent') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ enabled: enabled })
        });
    }

    function sendPing(position) {
        const body = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            speed: position.coords.speed !== null ? position.coords.speed * 3.6 : null, // m/s -> km/h
            heading: position.coords.heading
        };

        fetch('{{ route('driver.location.update') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.auto_started && data.trip_ticket && window.Swal) {
                Swal.fire({
                    icon: 'info',
                    title: 'Trip started automatically',
                    text: `You left the CLSU campus area, so trip ${data.trip_ticket.ticket_number || ''} to ${data.trip_ticket.destination || 'your destination'} was started automatically. Don't forget to enter your odometer readings when you complete the trip.`,
                    confirmButtonText: 'Got it'
                });
            }
        })
        .catch(() => {});
    }

    function startTracking() {
        if (!navigator.geolocation) {
            setStatus('unsupported');
            return;
        }

        // The Geolocation API is only usable on a secure origin (https://)
        // or localhost - on plain http:// (e.g. testing the driver PWA on
        // a phone via a LAN IP) the browser refuses every request, which
        // otherwise looks exactly like the driver denied permission.
        if (!window.isSecureContext) {
            setStatus('insecure');
            return;
        }

        // Wait for the first real fix before showing "on" - showing it
        // optimistically before we actually have a position misrepresents
        // the state if the very first request fails.
        navigator.geolocation.getCurrentPosition(function (position) {
            setStatus('on');
            sendPing(position);
        }, handleGeoError, { enableHighAccuracy: true });

        watchId = navigator.geolocation.watchPosition(function () {}, function () {}, { enableHighAccuracy: true });

        pingTimer = setInterval(function () {
            navigator.geolocation.getCurrentPosition(function (position) {
                setStatus('on');
                sendPing(position);
            }, handleGeoError, {
                enableHighAccuracy: true,
                maximumAge: 10000,
                timeout: 15000
            });
        }, 20000);
    }

    function stopTracking() {
        if (watchId !== null) navigator.geolocation.clearWatch(watchId);
        if (pingTimer !== null) clearInterval(pingTimer);
        watchId = null;
        pingTimer = null;
        setStatus('off');
    }

    document.getElementById('location-consent-accept').addEventListener('click', function () {
        sendConsent(true).then(() => { hideModal(); startTracking(); });
    });
    document.getElementById('location-consent-decline').addEventListener('click', function () {
        sendConsent(false).then(() => { hideModal(); setStatus('off'); });
    });

    document.getElementById('location-status-btn').addEventListener('click', function () {
        if (watchId !== null) {
            sendConsent(false).then(stopTracking);
        } else {
            sendConsent(true).then(startTracking);
        }
    });

    // On page load: ask once per login session if the driver has never
    // decided, or resume silently if they already enabled sharing.
    const willShowLocationPrompt = !driverSharingEnabled && !hasDecidedBefore && !sessionStorage.getItem('locationPromptShown');

    if (driverSharingEnabled) {
        startTracking();
    } else if (willShowLocationPrompt) {
        sessionStorage.setItem('locationPromptShown', '1');
        showModal();
    } else {
        setStatus('off');
    }

    // Don't stack the odometer prompt on top of the location prompt - show
    // it once the location decision is made (or immediately if the location
    // prompt isn't showing this time). The function only exists when the
    // driver still needs the odometer prompt at all.
    if (typeof window.maybeShowOdometerPrompt === 'function') {
        if (willShowLocationPrompt) {
            document.getElementById('location-consent-accept').addEventListener('click', window.maybeShowOdometerPrompt);
            document.getElementById('location-consent-decline').addEventListener('click', window.maybeShowOdometerPrompt);
        } else {
            window.maybeShowOdometerPrompt();
        }
    }
})();
</script>
@endif

