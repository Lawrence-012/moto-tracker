// Shared Google Maps helper functions used across the Trip Tickets, Ongoing
// Trips, Travel Orders, and Itinerary pages. These wrap the plain
// google.maps.* API so the map-drawing code at each call site stays short
// and consistent.
//
// Geocoding (address <-> coordinates) and routing/distance calculation use
// Google's Geocoding API and Directions API (see
// app/Services/DistanceEstimationService.php and the google.maps.Geocoder /
// google.maps.DirectionsService calls in each page).

// Sensible default center: Central Luzon State University, Nueva Ecija.
const GMAPS_DEFAULT_CENTER = { lat: 15.7378, lng: 120.9309 };

function gmCreateMap(elementId, center, zoom) {
    return new google.maps.Map(document.getElementById(elementId), {
        center: center || GMAPS_DEFAULT_CENTER,
        zoom: zoom || 13,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        clickableIcons: false,
    });
}

// Small solid-color dot marker - mirrors this project's old L.circleMarker.
function gmCircleMarker(map, position, opts) {
    opts = opts || {};
    return new google.maps.Marker({
        position: position,
        map: map,
        title: opts.title || '',
        zIndex: opts.zIndex,
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: opts.radius || 7,
            fillColor: opts.fillColor || opts.color || '#1e6031',
            fillOpacity: opts.fillOpacity === undefined ? 1 : opts.fillOpacity,
            strokeColor: opts.strokeColor || '#ffffff',
            strokeWeight: opts.strokeWeight === undefined ? 2 : opts.strokeWeight,
        },
    });
}

// Emoji-on-a-colored-badge marker - mirrors this project's old Leaflet
// divIcon bus (🚌) and pin (📍) markers.
function gmEmojiMarker(map, position, opts) {
    opts = opts || {};
    return new google.maps.Marker({
        position: position,
        map: map,
        title: opts.title || '',
        zIndex: opts.zIndex,
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: opts.scale || 15,
            fillColor: opts.bg || '#1e6031',
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 3,
        },
        label: {
            text: opts.emoji || '',
            fontSize: opts.fontSize || '14px',
        },
    });
}

// Attaches a click-to-open info window with the given HTML to a marker.
// Returns the InfoWindow so callers can update/reposition it later
// (e.g. to move a "current location" popup as a driver moves).
function gmBindInfoWindow(map, marker, html) {
    const infoWindow = new google.maps.InfoWindow({ content: html });
    marker.addListener('click', () => infoWindow.open({ anchor: marker, map }));
    return infoWindow;
}

// Picks the most useful short place name out of a Google Geocoding API
// result - prefers barangay/neighborhood-level detail, falling back to the
// city, then the full formatted address.
function readableGooglePlaceName(result) {
    if (!result) return 'Unknown location';

    const components = result.address_components || [];
    const findType = (type) => {
        const match = components.find((c) => c.types.includes(type));
        return match ? match.long_name : null;
    };

    return findType('sublocality') || findType('sublocality_level_1') || findType('neighborhood')
        || findType('locality') || result.formatted_address || 'Unknown location';
}

// Turns a Geocoder callback's status code into an accurate, actionable
// message - distinguishes "this address doesn't exist" (ZERO_RESULTS) from
// setup problems (REQUEST_DENIED etc.) instead of showing a generic "not
// found" for both, which just wastes the person's time re-typing a query
// that was never the actual problem.
function gmGeocodeErrorMessage(status, query) {
    switch (status) {
        case 'ZERO_RESULTS':
            return `Couldn't find "${query}". Try a more specific search.`;
        case 'REQUEST_DENIED':
            return "Map search isn't authorized yet - the Geocoding API needs to be enabled and allowed for this API key in Google Cloud Console.";
        case 'OVER_QUERY_LIMIT':
            return 'Too many searches right now - please wait a moment and try again.';
        default:
            return "Something went wrong searching for that place. Please try again.";
    }
}

// Fits the map viewport to an array of {lat,lng} points - mirrors Leaflet's
// map.fitBounds(layer.getBounds(), { padding }).
function gmFitBoundsToPath(map, path, paddingPx) {
    const bounds = new google.maps.LatLngBounds();
    path.forEach((p) => bounds.extend(p));
    map.fitBounds(bounds, paddingPx === undefined ? 30 : paddingPx);
}

// Rough Philippines bounding box - used to *bias* (never restrict) Place
// Autocomplete predictions toward local results without excluding anything
// genuinely outside it.
const PH_BOUNDS = { north: 21.0, south: 4.5, east: 127.0, west: 116.0 };

// Pull the province/region out of a Google Geocoding API result - for
// Philippine addresses Google puts the province under
// administrative_area_level_1 (e.g. "Nueva Ecija").
function extractRegionFromGeocodeResult(result) {
    const components = result.address_components || [];
    const match = components.find((c) => c.types.includes('administrative_area_level_1'));
    return match ? match.long_name : null;
}

// Straight-line (haversine) distance in km between two lat/lng points - used
// as a quick client-side estimate; the real driving distance is calculated
// server-side via DistanceEstimationService (Google Directions API).
function haversineKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
}

// Wires a text input up to live, Shopee/Grab-style Place Autocomplete
// suggestions rendered into `dropdownEl` (typing "Central" surfaces "Central
// Luzon State University", etc.) - this is what actually finds places that
// google.maps.Geocoder can't, since Geocoder only matches fairly complete
// addresses while Autocomplete matches partial names, POIs and campuses too.
//
// `onSelect({ lat, lng, label })` fires once the user picks a suggestion.
// Uses the current (non-deprecated) Autocomplete Data API
// (google.maps.places.AutocompleteSuggestion), with a session token so the
// whole type-then-select flow is billed as a single Places session.
//
// Fails silently (no dropdown) if the Places library isn't available for
// any reason - callers' existing "type a full address and press
// Enter/search" google.maps.Geocoder fallback keeps working regardless.
function gmAttachPlaceAutocomplete(inputEl, dropdownEl, onSelect) {
    let debounceTimer = null;
    let sessionToken = null;
    let requestSeq = 0;

    dropdownEl.style.display = 'none';

    function hideDropdown() {
        dropdownEl.innerHTML = '';
        dropdownEl.style.display = 'none';
    }

    function renderSuggestions(suggestions) {
        dropdownEl.innerHTML = '';

        if (!suggestions || !suggestions.length) {
            hideDropdown();
            return;
        }

        suggestions.forEach((suggestion) => {
            const prediction = suggestion.placePrediction;
            if (!prediction) return;

            const mainText = (prediction.mainText && prediction.mainText.text) || prediction.text.text;
            const secondaryText = prediction.secondaryText ? prediction.secondaryText.text : '';

            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'w-full text-left px-3 py-2 flex items-start gap-2 border-b border-gray-100 last:border-b-0 hover:bg-gray-50';
            item.innerHTML =
                '<i class="fas fa-location-dot mt-1" style="font-size:11px;color:#9ca3af;"></i>' +
                '<span class="min-w-0 flex-1">' +
                    '<span class="block text-sm text-gray-800 truncate">' + mainText + '</span>' +
                    (secondaryText ? '<span class="block text-xs text-gray-500 truncate">' + secondaryText + '</span>' : '') +
                '</span>';

            // mousedown (not click) fires before the input's blur event, so
            // the dropdown doesn't disappear before the selection registers.
            item.addEventListener('mousedown', (e) => {
                e.preventDefault();
                hideDropdown();
                inputEl.value = mainText;

                const place = prediction.toPlace();
                place.fetchFields({ fields: ['location', 'formattedAddress', 'displayName'] })
                    .then(() => {
                        sessionToken = null; // session concludes once details are fetched
                        if (!place.location) return;
                        onSelect({
                            lat: place.location.lat(),
                            lng: place.location.lng(),
                            label: place.formattedAddress || place.displayName || mainText,
                        });
                    })
                    .catch((err) => console.error('Failed to fetch place details', err));
            });

            dropdownEl.appendChild(item);
        });

        dropdownEl.style.display = 'block';
    }

    inputEl.addEventListener('input', () => {
        const value = inputEl.value.trim();
        clearTimeout(debounceTimer);

        if (value.length < 2) {
            hideDropdown();
            return;
        }

        if (!window.google || !google.maps.places || !google.maps.places.AutocompleteSuggestion) {
            return; // Graceful no-op - manual search/Geocoder path still works.
        }

        debounceTimer = setTimeout(() => {
            const seq = ++requestSeq;
            if (!sessionToken) {
                sessionToken = new google.maps.places.AutocompleteSessionToken();
            }

            google.maps.places.AutocompleteSuggestion.fetchAutocompleteSuggestions({
                input: value,
                sessionToken,
                locationBias: PH_BOUNDS,
                includedRegionCodes: ['ph'],
                region: 'ph',
            }).then(({ suggestions }) => {
                if (seq !== requestSeq) return; // a newer keystroke has since fired
                renderSuggestions(suggestions);
            }).catch((err) => {
                console.error('Place autocomplete failed', err);
                hideDropdown();
            });
        }, 300);
    });

    inputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') hideDropdown();
    });

    document.addEventListener('mousedown', (e) => {
        if (e.target !== inputEl && !dropdownEl.contains(e.target)) {
            hideDropdown();
        }
    });
}
