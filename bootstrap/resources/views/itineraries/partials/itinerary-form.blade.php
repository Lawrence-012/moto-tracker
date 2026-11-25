<div class="max-w-3xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Create New Itinerary</h2>
            </div>
            
            <form action="{{ route('itinerary.store') }}" method="POST" class="p-6" id="itineraryForm" onsubmit="return validateCapacityBeforeSubmit(event)">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Travel Order -->
                    <div>
                        <label for="travel_order_id" class="block text-sm font-medium text-gray-700 mb-1">Travel Order</label>
                        <select name="travel_order_ids[]" id="travel_order_id" 
                                class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                                onchange="prefillTravelOrderData(this)">
                            <option value="">Select Travel Order (Optional)</option>
                            @foreach($travelOrders as $travelOrder)
                                <option value="{{ $travelOrder->id }}" 
                                        data-date-from="{{ $travelOrder->date_from instanceof \DateTimeInterface ? $travelOrder->date_from->format('Y-m-d') : $travelOrder->date_from }}"
                                        data-date-to="{{ $travelOrder->date_to instanceof \DateTimeInterface ? $travelOrder->date_to->format('Y-m-d') : $travelOrder->date_to }}"
                                        data-destination="{{ $travelOrder->destination }}"
                                        data-purpose="{{ $travelOrder->purpose }}"
                                        {{ old('travel_order_ids') && in_array($travelOrder->id, old('travel_order_ids')) ? 'selected' : '' }}>
                                    #{{ $travelOrder->id }} - {{ $travelOrder->destination ?? 'Travel' }}
                                </option>
                            @endforeach
                        </select>
                        @error('travel_order_ids.*')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Date From -->
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From *</label>
                        <input type="date" name="date_from" id="date_from" 
                               class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                               value="{{ old('date_from') }}" required>
                        @error('date_from')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Date To -->
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To *</label>
                        <input type="date" name="date_to" id="date_to" 
                               class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                               value="{{ old('date_to') }}" required>
                        @error('date_to')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Departure Time -->
                    <div>
                        <label for="departure_time" class="block text-sm font-medium text-gray-700 mb-1">Departure Time *</label>
                        <input type="time" name="departure_time" id="departure_time" 
                               class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                               value="{{ old('departure_time') }}" required>
                        @error('departure_time')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Destination -->
                    <div class="md:col-span-2">
                        <label for="destination" class="block text-sm font-medium text-gray-700 mb-1">Destination *</label>
                        <div class="relative">
                            <input type="text" name="destination" id="destination" readonly
                                   onclick="openDestinationMapModal()"
                                   class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 cursor-pointer bg-white pr-10"
                                   placeholder="Tap to search and pin a location on the map"
                                   value="{{ old('destination') }}" required>
                            <i class="fas fa-map-location-dot absolute right-3 top-1/2 -translate-y-1/2 text-purple-500 pointer-events-none"></i>
                        </div>
                        @error('destination')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p id="destination-pin-hint" class="mt-1 text-xs text-gray-500 {{ old('destination_latitude') ? '' : 'hidden' }}">
                            <i class="fas fa-circle-check text-green-600 mr-1"></i>Exact location pinned
                        </p>

                        <input type="hidden" name="destination_latitude" id="destination_latitude" value="{{ old('destination_latitude') }}">
                        <input type="hidden" name="destination_longitude" id="destination_longitude" value="{{ old('destination_longitude') }}">
                    </div>
                    
                    <!-- Vehicle -->
                    <div>
                        <label for="vehicle_id" class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
                        <select name="vehicle_id" id="vehicle_id" 
                                class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                                onchange="filterTravelOrdersByCapacity()">
                            <option value="">Select Vehicle (Optional)</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" 
                                        {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}
                                        data-seating-capacity="{{ $vehicle->seating_capacity ?? 0 }}">
                                    {{ $vehicle->model }} - {{ $vehicle->plate_number }} (Capacity: {{ $vehicle->seating_capacity ?? 0 }})
                                </option>
                            @endforeach
                        </select>
                        @error('vehicle_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                                        
                    <!-- Driver -->
                    <div>
                        <label for="driver_id" class="block text-sm font-medium text-gray-700 mb-1">Driver</label>
                        <select name="driver_id" id="driver_id" 
                                class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                            <option value="">Select Driver (Optional)</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->first_name }} {{ $driver->last_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('driver_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Purpose -->
                    <div class="md:col-span-2">
                        <label for="purpose" class="block text-sm font-medium text-gray-700 mb-1">Purpose *</label>
                        <textarea name="purpose" id="purpose" rows="3" 
                                  class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                                  placeholder="Describe the purpose of this itinerary...">{{ old('purpose') }}</textarea>
                        @error('purpose')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 pt-6 border-t border-gray-200">
                    <a href="{{ $backUrl ?? (auth()->user() && auth()->user()->employee ? (auth()->user()->employee->is_vp ? route('vp.travel-orders.index') : (auth()->user()->employee->is_head ? route('unithead.travel-orders.index') : route('dashboard'))) : route('dashboard')) }}" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Back to Dashboard
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Create Itinerary
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Destination map picker modal (Shopee/Grab-style: search a place, or pan the
     map to move the fixed center pin, then confirm) -->
<div id="destination-map-modal" class="fixed inset-0 z-[9999] hidden bg-black/50 items-end sm:items-center justify-center">
    <div class="bg-white w-full sm:max-w-lg sm:rounded-xl rounded-t-2xl overflow-hidden flex flex-col" style="height: 88vh; max-height: 720px;">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
            <h3 class="font-semibold text-gray-800">Set Destination</h3>
            <button type="button" onclick="closeDestinationMapModal()" class="text-gray-400 hover:text-gray-600 p-1">
                <i class="fas fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Search bar -->
        <div class="px-4 py-3 border-b border-gray-200 flex-shrink-0">
            <div class="flex gap-2">
                <input type="text" id="modal-destination-search" autocomplete="off"
                       placeholder="Search for a place..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                <button type="button" id="modal-destination-search-btn" onclick="searchModalDestination()"
                        class="flex-shrink-0 inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                    <i class="fas fa-magnifying-glass"></i>
                </button>
            </div>
            <p id="modal-destination-error" class="hidden mt-1.5 text-xs text-red-600"></p>
        </div>

        <!-- Map with a fixed center pin - pan the map underneath to move the pin -->
        <div class="flex-1 relative min-h-0 bg-gray-100">
            <div id="destination-modal-map" class="w-full h-full"></div>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none" style="margin-top: -18px;">
                <i class="fas fa-location-dot text-red-600 text-4xl drop-shadow-lg"></i>
            </div>
        </div>

        <!-- Confirm -->
        <div class="px-4 py-3 border-t border-gray-200 flex-shrink-0">
            <p class="text-xs text-gray-500 mb-1">Pinned location:</p>
            <p id="modal-pin-address" class="text-sm font-medium text-gray-800 mb-3 truncate">Move the map to set the pin</p>
            <button type="button" id="modal-confirm-btn" onclick="confirmModalDestination()"
                    class="w-full inline-flex items-center justify-center gap-2 bg-purple-600 text-white py-2.5 rounded-lg font-medium text-sm hover:bg-purple-700">
                <i class="fas fa-check"></i> Confirm Location
            </button>
        </div>
    </div>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps.api_key') }}"></script>
<script src="{{ asset('js/gmaps-helpers.js') }}"></script>
<script>
let destinationModalMap = null;
let destinationPinLatLng = null; // {lat, lng} of whatever the center pin is currently over
let destinationPinLabel = null;

function openDestinationMapModal() {
    const modal = document.getElementById('destination-map-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('modal-destination-error').classList.add('hidden');

    // Start the search box with whatever destination text is already there
    // (typed by hand, or auto-filled from a selected Travel Order).
    const existingText = document.getElementById('destination').value.trim();
    document.getElementById('modal-destination-search').value = existingText;

    const existingLat = document.getElementById('destination_latitude').value;
    const existingLng = document.getElementById('destination_longitude').value;
    const startCenter = (existingLat && existingLng)
        ? { lat: parseFloat(existingLat), lng: parseFloat(existingLng) }
        : GMAPS_DEFAULT_CENTER;

    setTimeout(() => {
        if (!destinationModalMap) {
            destinationModalMap = gmCreateMap('destination-modal-map', startCenter, existingLat ? 16 : 13);
            destinationModalMap.addListener('idle', onDestinationMapIdle);
        } else {
            destinationModalMap.setCenter(startCenter);
            destinationModalMap.setZoom(existingLat ? 16 : 13);
            google.maps.event.trigger(destinationModalMap, 'resize');
            destinationModalMap.setCenter(startCenter);
        }

        // If there's already pinned coordinates, or existing text to search,
        // resolve an address label for the confirm panel right away.
        if (existingLat && existingLng) {
            reverseGeocodePin(startCenter);
        } else if (existingText) {
            searchModalDestination();
        }
    }, 50);
}

function closeDestinationMapModal() {
    const modal = document.getElementById('destination-map-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Fires whenever the map finishes panning/zooming (covers dragging, the
// search button recentering it, scroll-zoom, etc.) - reads whatever
// location is currently under the fixed center pin.
function onDestinationMapIdle() {
    const center = destinationModalMap.getCenter();
    reverseGeocodePin({ lat: center.lat(), lng: center.lng() });
}

function reverseGeocodePin(latLng) {
    destinationPinLatLng = latLng;
    destinationPinLabel = null; // cleared until geocoding actually succeeds
    document.getElementById('modal-pin-address').textContent = 'Locating...';
    document.getElementById('modal-destination-error').classList.add('hidden');
    document.getElementById('modal-confirm-btn').disabled = true;

    new google.maps.Geocoder().geocode({ location: latLng }, (results, status) => {
        if (status === 'OK' && results && results.length > 0) {
            destinationPinLabel = readableGooglePlaceName(results[0]);
            document.getElementById('modal-pin-address').textContent = destinationPinLabel;
            document.getElementById('modal-confirm-btn').disabled = false;
        } else {
            // Never put raw coordinates in the destination field - show the
            // real reason instead and leave Confirm disabled until a
            // successful geocode gives us an actual place name.
            document.getElementById('modal-pin-address').textContent = 'Could not determine address for this spot';
            const errorEl = document.getElementById('modal-destination-error');
            errorEl.textContent = gmGeocodeErrorMessage(status, 'this location');
            errorEl.classList.remove('hidden');
        }
    });
}

function searchModalDestination() {
    const query = document.getElementById('modal-destination-search').value.trim();
    const errorEl = document.getElementById('modal-destination-error');
    errorEl.classList.add('hidden');

    if (!query) return;

    const searchBtn = document.getElementById('modal-destination-search-btn');
    const originalHtml = searchBtn.innerHTML;
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    new google.maps.Geocoder().geocode({ address: query + ', Philippines', region: 'ph' }, (results, status) => {
        searchBtn.disabled = false;
        searchBtn.innerHTML = originalHtml;

        if (status !== 'OK' || !results || results.length === 0) {
            errorEl.textContent = gmGeocodeErrorMessage(status, query);
            errorEl.classList.remove('hidden');
            return;
        }

        const loc = results[0].geometry.location;
        // setCenter triggers the map's 'idle' listener once panning settles,
        // which handles the reverse-geocode + label update for us.
        destinationModalMap.setCenter(loc);
        destinationModalMap.setZoom(16);
    });
}

function confirmModalDestination() {
    // Guard: only a successful geocode sets destinationPinLabel, so this
    // also blocks confirming while "Locating..." or an error is showing.
    if (!destinationPinLatLng || !destinationPinLabel) return;

    document.getElementById('destination').value = destinationPinLabel;
    document.getElementById('destination_latitude').value = destinationPinLatLng.lat;
    document.getElementById('destination_longitude').value = destinationPinLatLng.lng;
    document.getElementById('destination-pin-hint').classList.remove('hidden');

    closeDestinationMapModal();
}

// Let Enter in the modal's search box trigger a search instead of
// submitting the (closed) itinerary form behind it.
document.getElementById('modal-destination-search').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchModalDestination();
    }
});

// Clicking the dark backdrop closes the modal without confirming.
document.getElementById('destination-map-modal').addEventListener('click', (e) => {
    if (e.target.id === 'destination-map-modal') {
        closeDestinationMapModal();
    }
});

// If the destination text changes without going through the modal's
// Confirm button (e.g. auto-filled from selecting a different Travel
// Order), clear any previously-confirmed pin so a stale location can
// never be submitted for a different address.
document.getElementById('destination').addEventListener('input', () => {
    document.getElementById('destination_latitude').value = '';
    document.getElementById('destination_longitude').value = '';
    document.getElementById('destination-pin-hint').classList.add('hidden');
});
</script>