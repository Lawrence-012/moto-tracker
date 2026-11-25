/*
 * Destination picker for Travel Orders, using OpenStreetMap (Leaflet) and Nominatim.
 * Same behavior as the old Google version: search, pin on map, confirm.
 * Nominatim is free and open. Use it at a light pace (one request per second at most).
 */
let destPickerMap = null;
let destPickerLatLng = null;
let destPickerLabel = null;
let suppressNextDestPickerIdle = false;
let destReverseTimer = null;
let destSearchTimer = null;
let destReverseToken = 0;

const DEST_DEFAULT_CENTER = { lat: 15.734413, lng: 120.927484 }; // CLSU

function destHaversineKm(lat1, lng1, lat2, lng2) {
    const toRad = d => d * Math.PI / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return 2 * 6371 * Math.asin(Math.min(1, Math.sqrt(a)));
}

// Name shown for a place: its own name, or the first few parts of the address
function destPlaceName(item) {
    if (item.name) return item.name;
    return (item.display_name || '').split(',').slice(0, 3).join(',').trim();
}

// Region (province or city) from the address details
function destRegion(item) {
    const a = item.address || {};
    return a.state || a.province || a.county || a.city || a.town || a.municipality || '';
}

async function nominatimSearch(query, limit) {
    const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&accept-language=en'
        + '&countrycodes=ph&limit=' + limit + '&q=' + encodeURIComponent(query);
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error('Search failed (' + res.status + ')');
    return res.json();
}

async function nominatimReverse(lat, lng) {
    const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&accept-language=en'
        + '&zoom=18&lat=' + lat + '&lon=' + lng;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error('Address lookup failed (' + res.status + ')');
    return res.json();
}

function openDestinationMapModal() {
    const modal = document.getElementById('dest-picker-modal');
    modal.classList.remove('d-none');
    modal.style.display = 'flex';
    document.getElementById('dest-picker-error').classList.add('d-none');

    const existingText = document.getElementById('to_destination').value.trim();
    document.getElementById('dest-picker-search').value = existingText;

    const existingLat = document.getElementById('to_latitude').value;
    const existingLng = document.getElementById('to_longitude').value;
    const startCenter = (existingLat && existingLng)
        ? { lat: parseFloat(existingLat), lng: parseFloat(existingLng) }
        : DEST_DEFAULT_CENTER;

    setTimeout(() => {
        if (!destPickerMap) {
            destPickerMap = L.map('dest-picker-map').setView([startCenter.lat, startCenter.lng], existingLat ? 16 : 13);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            }).addTo(destPickerMap);
            destPickerMap.on('moveend', onDestPickerIdle);
            addDestCenterPin();
        } else {
            destPickerMap.invalidateSize();
            destPickerMap.setView([startCenter.lat, startCenter.lng], existingLat ? 16 : 13);
        }

        if (existingLat && existingLng) {
            reverseGeocodeDestPicker(startCenter);
        } else if (existingText) {
            searchDestPicker();
        }

        document.getElementById('dest-picker-search').focus();
    }, 100);
}

// Red pin fixed at the center of the map. The map moves under it, and the pin marks the spot.
function addDestCenterPin() {
    const mapEl = document.getElementById('dest-picker-map');
    if (!mapEl || mapEl.querySelector('.dest-center-pin')) return;
    const pin = document.createElement('div');
    pin.className = 'dest-center-pin';
    pin.innerHTML = '<i class="fas fa-location-dot" style="font-size:36px;color:#dc2626;filter:drop-shadow(0 2px 2px rgba(0,0,0,0.4));"></i>';
    pin.style.cssText = 'position:absolute;left:50%;top:50%;transform:translate(-50%,-100%);pointer-events:none;z-index:1000;';
    mapEl.style.position = 'relative';
    mapEl.appendChild(pin);
}

function closeDestinationMapModal() {
    const modal = document.getElementById('dest-picker-modal');
    modal.classList.add('d-none');
    modal.style.display = 'none';
    hideDestSuggestions();
}

// Fires when the map stops moving: reads whatever is under the center pin
function onDestPickerIdle() {
    if (suppressNextDestPickerIdle) {
        suppressNextDestPickerIdle = false;
        return;
    }
    const c = destPickerMap.getCenter();
    clearTimeout(destReverseTimer);
    destReverseTimer = setTimeout(() => reverseGeocodeDestPicker({ lat: c.lat, lng: c.lng }), 500);
}

async function reverseGeocodeDestPicker(latLng) {
    const token = ++destReverseToken;
    destPickerLatLng = latLng;
    destPickerLabel = null;
    document.getElementById('dest-picker-address').textContent = 'Locating...';
    document.getElementById('dest-picker-error').classList.add('d-none');
    document.getElementById('dest-picker-confirm-btn').disabled = true;

    try {
        const data = await nominatimReverse(latLng.lat, latLng.lng);
        if (token !== destReverseToken) return; // a newer lookup has started
        if (data && data.display_name) {
            destPickerLabel = destPlaceName(data);
            destPickerLatLng.region = destRegion(data);
            document.getElementById('dest-picker-address').textContent = destPickerLabel;
            document.getElementById('dest-picker-confirm-btn').disabled = false;
        } else {
            document.getElementById('dest-picker-address').textContent = 'Could not determine address for this spot';
        }
    } catch (e) {
        if (token !== destReverseToken) return;
        document.getElementById('dest-picker-address').textContent = 'Could not determine address for this spot';
        const errorEl = document.getElementById('dest-picker-error');
        errorEl.textContent = e.message;
        errorEl.classList.remove('d-none');
    }
}

async function searchDestPicker() {
    const query = document.getElementById('dest-picker-search').value.trim();
    const errorEl = document.getElementById('dest-picker-error');
    errorEl.classList.add('d-none');
    if (!query) return;

    const searchBtn = document.getElementById('dest-picker-search-btn');
    const originalHtml = searchBtn.innerHTML;
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const results = await nominatimSearch(query, 1);
        if (!results || results.length === 0) {
            errorEl.textContent = 'No place found for "' + query + '". Try a more specific name.';
            errorEl.classList.remove('d-none');
            return;
        }
        const item = results[0];
        const lat = parseFloat(item.lat);
        const lng = parseFloat(item.lon);
        destPickerLatLng = { lat, lng, region: destRegion(item) };
        destPickerLabel = query.length > 3 ? query : destPlaceName(item);

        document.getElementById('dest-picker-address').textContent = destPickerLabel;
        document.getElementById('dest-picker-confirm-btn').disabled = false;

        suppressNextDestPickerIdle = true;
        destPickerMap.setView([lat, lng], 16);
    } catch (e) {
        errorEl.textContent = e.message;
        errorEl.classList.remove('d-none');
    } finally {
        searchBtn.disabled = false;
        searchBtn.innerHTML = originalHtml;
    }
}

function confirmDestPicker() {
    if (!destPickerLatLng || !destPickerLabel) return;

    document.getElementById('to_destination').value = destPickerLabel;
    document.getElementById('to_latitude').value = destPickerLatLng.lat;
    document.getElementById('to_longitude').value = destPickerLatLng.lng;
    document.getElementById('to_region').value = destPickerLatLng.region || '';

    const km = destHaversineKm(DEST_DEFAULT_CENTER.lat, DEST_DEFAULT_CENTER.lng, destPickerLatLng.lat, destPickerLatLng.lng);
    document.getElementById('to_distance_km').value = km.toFixed(2);

    closeDestinationMapModal();
}

function applyDestPickerSuggestion({ lat, lng, label, region }) {
    destPickerLatLng = { lat, lng, region: region || '' };
    destPickerLabel = label;
    document.getElementById('dest-picker-address').textContent = label;
    document.getElementById('dest-picker-error').classList.add('d-none');
    document.getElementById('dest-picker-confirm-btn').disabled = false;
    hideDestSuggestions();

    suppressNextDestPickerIdle = true;
    destPickerMap.setView([lat, lng], 16);
}

// Live suggestions while typing
function hideDestSuggestions() {
    const box = document.getElementById('dest-picker-suggestions');
    if (box) { box.style.display = 'none'; box.innerHTML = ''; }
}

function renderDestSuggestions(items) {
    const box = document.getElementById('dest-picker-suggestions');
    if (!items.length) { hideDestSuggestions(); return; }
    box.innerHTML = '';
    items.forEach(item => {
        const row = document.createElement('div');
        row.style.cssText = 'padding:8px 12px; cursor:pointer; border-bottom:1px solid #eee; font-size:0.85rem;';
        row.innerHTML = '<div class="fw-semibold">' + destPlaceName(item) + '</div>'
            + '<div class="text-muted" style="font-size:0.75rem;">' + (item.display_name || '') + '</div>';
        row.addEventListener('mousedown', e => e.preventDefault());
        row.addEventListener('click', () => applyDestPickerSuggestion({
            lat: parseFloat(item.lat),
            lng: parseFloat(item.lon),
            label: destPlaceName(item),
            region: destRegion(item),
        }));
        box.appendChild(row);
    });
    box.style.display = 'block';
}

document.getElementById('dest-picker-search').addEventListener('input', function () {
    const q = this.value.trim();
    clearTimeout(destSearchTimer);
    if (q.length < 3) { hideDestSuggestions(); return; }
    destSearchTimer = setTimeout(async () => {
        try {
            renderDestSuggestions(await nominatimSearch(q, 5));
        } catch (e) {
            hideDestSuggestions();
        }
    }, 500);
});

document.getElementById('dest-picker-search').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchDestPicker();
    }
});

document.getElementById('dest-picker-modal').addEventListener('click', (e) => {
    if (e.target.id === 'dest-picker-modal') {
        closeDestinationMapModal();
    }
});
