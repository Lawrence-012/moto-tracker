@php
    $hasExistingTravelOrder = isset($travelOrder) && $travelOrder && $travelOrder->id;
    $existingDestination = old('destination', $hasExistingTravelOrder ? $travelOrder->destination : '');
    $existingLatitude = old('latitude', $hasExistingTravelOrder ? $travelOrder->latitude : '');
    $existingLongitude = old('longitude', $hasExistingTravelOrder ? $travelOrder->longitude : '');
    $existingRegion = old('region', $hasExistingTravelOrder ? $travelOrder->region : '');
    $existingDistanceKm = old('distance_km', $hasExistingTravelOrder ? $travelOrder->distance_km : '');
@endphp

<div>
    <label for="destination" class="block text-sm font-medium text-gray-700 mb-1">Destination <span class="text-danger">*</span></label>
    <div class="relative">
        <input type="text" name="destination" id="destination" readonly required
               onclick="openTravelDestinationPicker()"
               value="{{ $existingDestination }}"
               placeholder="Tap to search and pin the exact location"
               class="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 pr-10 cursor-pointer bg-white focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] sm:text-sm transition duration-200">
        <i class="fas fa-map-location-dot absolute right-3 top-1/2 -translate-y-1/2 text-[#1e6031] pointer-events-none"></i>
    </div>
    @error('destination')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
    <p id="travel-destination-pin-hint" class="mt-1 text-xs text-gray-500 {{ $existingLatitude ? '' : 'hidden' }}">
        <i class="fas fa-circle-check text-green-600 mr-1"></i>Exact location pinned
    </p>

    <input type="hidden" name="latitude" id="travel-destination-latitude" value="{{ $existingLatitude }}">
    <input type="hidden" name="longitude" id="travel-destination-longitude" value="{{ $existingLongitude }}">
    <input type="hidden" name="region" id="travel-destination-region" value="{{ $existingRegion }}">
    <input type="hidden" name="distance_km" id="travel-destination-distance-km" value="{{ $existingDistanceKm }}">
</div>

<!-- Two-step destination picker (Shopee/Grab-style):
     Step 1 - type an address, with live suggestions as you type.
     Step 2 - fine-tune the exact pin on a map, then confirm. -->
<div id="travel-destination-modal" class="fixed inset-0 z-[9999] hidden bg-black/50 items-end sm:items-center justify-center">
    <div class="bg-white w-full sm:max-w-lg sm:rounded-xl rounded-t-2xl overflow-hidden flex flex-col" style="height: 88vh; max-height: 720px;">

        <!-- STEP 1: address entry + live suggestions -->
        <div id="travel-destination-step1" class="flex flex-col h-full">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <h3 class="font-semibold text-gray-800">Where are you headed?</h3>
                <button type="button" onclick="closeTravelDestinationPicker()" class="text-gray-400 hover:text-gray-600 p-1">
                    <i class="fas fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="px-4 py-4 flex-1 overflow-y-auto">
                <label for="travel-destination-search" class="block text-sm font-medium text-gray-700 mb-1">Address or place name</label>
                <div class="relative">
                    <input type="text" id="travel-destination-search" autocomplete="off"
                           placeholder="e.g. Central Luzon State University"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-[#1e6031] focus:ring-[#1e6031]">
                    <div id="travel-destination-suggestions"
                         class="absolute left-0 right-0 z-10 mt-1 bg-white rounded-lg shadow-lg border border-gray-200 max-h-64 overflow-y-auto"
                         style="display:none;"></div>
                </div>
                <p id="travel-destination-step1-error" class="hidden mt-2 text-xs text-red-600"></p>
                <p class="mt-2 text-xs text-gray-500">
                    <i class="fas fa-circle-info mr-1"></i>Pick a suggestion as you type, or type the full address and press Next.
                </p>
            </div>

            <div class="px-4 py-3 border-t border-gray-200 flex-shrink-0">
                <button type="button" id="travel-destination-next-btn" onclick="goToTravelDestinationMapStep()"
                        class="w-full inline-flex items-center justify-center gap-2 bg-[#1e6031] text-white py-2.5 rounded-lg font-medium text-sm hover:bg-[#164f2a]">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- STEP 2: map pin fine-tuning -->
        <div id="travel-destination-step2" class="hidden flex-col h-full">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <button type="button" onclick="backToTravelDestinationSearch()" class="text-gray-500 hover:text-gray-700 text-sm inline-flex items-center gap-1">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <h3 class="font-semibold text-gray-800">Pin exact location</h3>
                <button type="button" onclick="closeTravelDestinationPicker()" class="text-gray-400 hover:text-gray-600 p-1">
                    <i class="fas fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Map with a fixed center pin - pan the map underneath to move the pin -->
            <div class="flex-1 relative min-h-0 bg-gray-100">
                <div id="travel-destination-map" class="w-full h-full"></div>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none" style="margin-top: -18px;">
                    <i class="fas fa-location-dot text-red-600 text-4xl drop-shadow-lg"></i>
                </div>
            </div>

            <div class="px-4 py-3 border-t border-gray-200 flex-shrink-0">
                <p class="text-xs text-gray-500 mb-1">Pinned location:</p>
                <p id="travel-destination-pin-address" class="text-sm font-medium text-gray-800 mb-3 truncate">Move the map to set the pin</p>
                <button type="button" id="travel-destination-confirm-btn" onclick="confirmTravelDestination()"
                        class="w-full inline-flex items-center justify-center gap-2 bg-[#1e6031] text-white py-2.5 rounded-lg font-medium text-sm hover:bg-[#164f2a]">
                    <i class="fas fa-check"></i> Confirm Location
                </button>
            </div>
        </div>
    </div>
</div>

@include('partials.google-maps-script')
<script src="{{ asset('js/gmaps-helpers.js') }}"></script>
<script>
let travelDestinationMap = null;
let travelDestinationPinLatLng = null; // {lat, lng, region?} of whatever the center pin is currently over
let travelDestinationPinLabel = null;

// Set right before a suggestion/reopen programmatically recenters the map,
// so the one resulting 'idle' event doesn't immediately overwrite the
// label we already know is correct with a reverse-geocoded guess.
let travelDestinationSuppressNextIdle = false;

function openTravelDestinationPicker() {
    const modal = document.getElementById('travel-destination-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const existingText = document.getElementById('destination').value.trim();
    document.getElementById('travel-destination-search').value = existingText;
    document.getElementById('travel-destination-step1-error').classList.add('hidden');

    const existingLat = document.getElementById('travel-destination-latitude').value;
    const existingLng = document.getElementById('travel-destination-longitude').value;

    if (existingLat && existingLng) {
        // Already pinned (editing an existing travel order, or reopening
        // after a previous Confirm) - jump straight to Step 2 at that exact
        // spot instead of re-searching and potentially landing somewhere
        // slightly different.
        travelDestinationPinLatLng = { lat: parseFloat(existingLat), lng: parseFloat(existingLng) };
        travelDestinationPinLabel = existingText;
        travelDestinationSuppressNextIdle = true;

        showTravelDestinationStep(2);
        document.getElementById('travel-destination-pin-address').textContent = existingText || 'Move the map to set the pin';
        document.getElementById('travel-destination-confirm-btn').disabled = false;
        initOrRecenterTravelDestinationMap(travelDestinationPinLatLng, 16);
    } else {
        showTravelDestinationStep(1);
    }
}

function closeTravelDestinationPicker() {
    const modal = document.getElementById('travel-destination-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function showTravelDestinationStep(step) {
    const step1 = document.getElementById('travel-destination-step1');
    const step2 = document.getElementById('travel-destination-step2');
    step1.classList.toggle('hidden', step !== 1);
    step1.classList.toggle('flex', step === 1);
    step2.classList.toggle('hidden', step !== 2);
    step2.classList.toggle('flex', step === 2);
}

function backToTravelDestinationSearch() {
    showTravelDestinationStep(1);
}

// Step 1 -> Step 2 via the "Next" button (or Enter): geocodes whatever
// address text is currently typed and, on success, shows the map centered
// there for pin fine-tuning.
function goToTravelDestinationMapStep() {
    const errorEl = document.getElementById('travel-destination-step1-error');
    errorEl.classList.add('hidden');

    const query = document.getElementById('travel-destination-search').value.trim();
    if (!query) {
        errorEl.textContent = 'Type an address or place name first.';
        errorEl.classList.remove('hidden');
        return;
    }

    const nextBtn = document.getElementById('travel-destination-next-btn');
    const originalHtml = nextBtn.innerHTML;
    nextBtn.disabled = true;
    nextBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';

    new google.maps.Geocoder().geocode({ address: query + ', Philippines', region: 'ph' }, (results, status) => {
        nextBtn.disabled = false;
        nextBtn.innerHTML = originalHtml;

        if (status !== 'OK' || !results || results.length === 0) {
            errorEl.textContent = gmGeocodeErrorMessage(status, query);
            errorEl.classList.remove('hidden');
            return;
        }

        const loc = results[0].geometry.location;
        showTravelDestinationStep(2);
        initOrRecenterTravelDestinationMap({ lat: loc.lat(), lng: loc.lng() }, 16);
    });
}

function initOrRecenterTravelDestinationMap(center, zoom) {
    setTimeout(() => {
        if (!travelDestinationMap) {
            travelDestinationMap = gmCreateMap('travel-destination-map', center, zoom);
            travelDestinationMap.addListener('idle', onTravelDestinationMapIdle);
        } else {
            google.maps.event.trigger(travelDestinationMap, 'resize');
            travelDestinationMap.setCenter(center);
            travelDestinationMap.setZoom(zoom);
        }
    }, 50);
}

// Fires whenever the map finishes panning/zooming - reads whatever location
// is currently under the fixed center pin.
function onTravelDestinationMapIdle() {
    if (travelDestinationSuppressNextIdle) {
        travelDestinationSuppressNextIdle = false;
        return;
    }
    const center = travelDestinationMap.getCenter();
    reverseGeocodeTravelDestinationPin({ lat: center.lat(), lng: center.lng() });
}

function reverseGeocodeTravelDestinationPin(latLng) {
    travelDestinationPinLatLng = latLng;
    travelDestinationPinLabel = null; // cleared until geocoding actually succeeds
    document.getElementById('travel-destination-pin-address').textContent = 'Locating...';
    document.getElementById('travel-destination-confirm-btn').disabled = true;

    new google.maps.Geocoder().geocode({ location: latLng }, (results, status) => {
        if (status === 'OK' && results && results.length > 0) {
            travelDestinationPinLabel = readableGooglePlaceName(results[0]);
            travelDestinationPinLatLng.region = extractRegionFromGeocodeResult(results[0]);
            document.getElementById('travel-destination-pin-address').textContent = travelDestinationPinLabel;
            document.getElementById('travel-destination-confirm-btn').disabled = false;
        } else {
            // Never put raw coordinates in the destination field - leave
            // Confirm disabled until a successful geocode gives us a name.
            document.getElementById('travel-destination-pin-address').textContent = 'Could not determine address for this spot';
        }
    });
}

// Selecting a live suggestion in Step 1 jumps straight to Step 2, already
// centered on that place and labeled with the suggestion's own (usually
// more specific - e.g. a campus/POI) name, rather than waiting on a
// reverse geocode of the exact point. Region isn't part of the Places
// suggestion, so a quick background geocode of the same point fills that
// in without touching the label already shown.
function applyTravelDestinationSuggestion({ lat, lng, label }) {
    document.getElementById('travel-destination-step1-error').classList.add('hidden');

    travelDestinationPinLatLng = { lat, lng };
    travelDestinationPinLabel = label;
    travelDestinationSuppressNextIdle = true;

    showTravelDestinationStep(2);
    document.getElementById('travel-destination-pin-address').textContent = label;
    document.getElementById('travel-destination-confirm-btn').disabled = false;

    initOrRecenterTravelDestinationMap({ lat, lng }, 16);

    new google.maps.Geocoder().geocode({ location: { lat, lng } }, (results, status) => {
        const stillCurrent = travelDestinationPinLatLng && travelDestinationPinLatLng.lat === lat && travelDestinationPinLatLng.lng === lng;
        if (status === 'OK' && results && results.length > 0 && stillCurrent) {
            travelDestinationPinLatLng.region = extractRegionFromGeocodeResult(results[0]);
        }
    });
}

gmAttachPlaceAutocomplete(
    document.getElementById('travel-destination-search'),
    document.getElementById('travel-destination-suggestions'),
    applyTravelDestinationSuggestion
);

document.getElementById('travel-destination-search').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        goToTravelDestinationMapStep();
    }
});

function confirmTravelDestination() {
    // Guard: only a successful geocode/suggestion sets travelDestinationPinLabel,
    // so this also blocks confirming while "Locating..." or an error is showing.
    if (!travelDestinationPinLatLng || !travelDestinationPinLabel) return;

    document.getElementById('destination').value = travelDestinationPinLabel;
    document.getElementById('travel-destination-latitude').value = travelDestinationPinLatLng.lat;
    document.getElementById('travel-destination-longitude').value = travelDestinationPinLatLng.lng;
    document.getElementById('travel-destination-region').value = travelDestinationPinLatLng.region || '';
    document.getElementById('travel-destination-pin-hint').classList.remove('hidden');

    // Straight-line distance from CLSU as a quick estimate; the real
    // driving distance is calculated server-side (Google Directions API)
    // the same way trip tickets already do via DistanceEstimationService.
    const km = haversineKm(GMAPS_DEFAULT_CENTER.lat, GMAPS_DEFAULT_CENTER.lng, travelDestinationPinLatLng.lat, travelDestinationPinLatLng.lng);
    document.getElementById('travel-destination-distance-km').value = km.toFixed(2);

    closeTravelDestinationPicker();
}

document.getElementById('travel-destination-modal').addEventListener('click', (e) => {
    if (e.target.id === 'travel-destination-modal') {
        closeTravelDestinationPicker();
    }
});
</script>
