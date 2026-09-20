/**
 * Destination Map Picker Integration
 * Integrates OpenStreetMap with Leaflet.js for destination selection
 */

// Initialize map instance
let destinationMap = null;
let destinationMarker = null;
let isMapInitialized = false;

/**
 * Initialize the destination picker with map functionality
 */
function initDestinationMapPicker() {
   const destinationInput = document.getElementById('destination_input');
   if (!destinationInput) {
       console.warn('⚠️ Destination input not found, trying fallback selector');
       const fallback = document.querySelector('input[name="destination"]');
       if (fallback) {
           console.log('✅ Found destination input via fallback');
        } else {
           return;
        }
    }

   console.log('🗺️ Initializing map picker...');

    // Create map container if it doesn't exist
   if (!document.getElementById('destinationMapContainer')) {
       const mapContainer = document.createElement('div');
        mapContainer.id = 'destinationMapContainer';
        mapContainer.className = 'mt-3';
        mapContainer.style.display = 'none';
        mapContainer.innerHTML = `
            <div class="card border-success shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-2">
                    <small class="fw-bold"><i class="fas fa-map-marked-alt"></i> Select Destination on Map</small>
                    <button type="button" class="btn btn-sm btn-light" onclick="toggleMapPicker()" title="Close Map">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div id="destinationMap" style="height: 400px; width: 100%;"></div>
                    <div class="p-3 bg-light">
                        <div class="row g-2">
                            <div class="col-md-9">
                                <small class="text-muted d-block">
                                    <i class="fas fa-info-circle text-success"></i> 
                                    <strong>Instructions:</strong> Click anywhere on the map to select your destination.
                                    <br>The system will automatically calculate the distance from CLSU and identify the region.
                                </small>
                            </div>
                            <div class="col-md-3 text-end">
                                <button type="button" class="btn btn-sm btn-success" onclick="confirmMapSelection()">
                                    <i class="fas fa-check-circle"></i> Use Location
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Insert after the form group containing destination
       const destFormGroup = destinationInput ? destinationInput.closest('.mb-2') : null;
       if (destFormGroup) {
            destFormGroup.insertAdjacentElement('afterend', mapContainer);
           console.log('✅ Map container inserted into DOM');
        }
    }

    // Add hidden fields for coordinates and region
    addHiddenFields();

    // Load Leaflet library if not already loaded
    setupMapInitialization();

    // Listen for destination input changes
   const currentInput = destinationInput || document.querySelector('input[name="destination"]');
   if (currentInput) {
        currentInput.addEventListener('blur', function() {
           if (this.value.trim()) {
                geocodeDestination(this.value.trim());
            }
        });
    }
    
   console.log('✅ Map picker initialization complete');
}

/**
 * Add hidden fields for storing location data
 */
function addHiddenFields() {
    const form = document.getElementById('createTravelRequestForm');
   if (!form) return;

    const fields = ['latitude', 'longitude', 'region', 'distance_km'];
    fields.forEach(field => {
       if (!document.getElementById(field)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.id = field;
            input.name = field;
            form.appendChild(input);
        }
    });
}

/**
 * Toggle map visibility
 */
function toggleMapPicker() {
    const mapContainer= document.getElementById('destinationMapContainer');
   if (!mapContainer) return;

    const isVisible = mapContainer.style.display !== 'none';
    mapContainer.style.display = isVisible ? 'none' : 'block';

   if (!isVisible && !isMapInitialized) {
        initializeMap();
    } else if (!isVisible) {
        setTimeout(() => {
           if (destinationMap) {
                destinationMap.invalidateSize();
            }
        }, 100);
    }
}

/**
 * Setup map initialization when needed
 */
function setupMapInitialization() {
    // Check if Leaflet is loaded
   if (typeof L === 'undefined') {
        console.log('Loading Leaflet library...');
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => {
            console.log('Leaflet loaded successfully');
        };
        document.body.appendChild(script);
    }
}

/**
 * Initialize the Leaflet map
 */
function initializeMap() {
   if (isMapInitialized || typeof L === 'undefined') return;

    // CLSU coordinates as default center
    const clsuLat = 15.0943;
    const clsuLng = 120.9686;

    destinationMap = L.map('destinationMap').setView([clsuLat, clsuLng], 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(destinationMap);

    // Add click event to map
    destinationMap.on('click', function(e) {
        placeMarker(e.latlng.lat, e.latlng.lng);
    });

    isMapInitialized = true;
    console.log('Map initialized successfully');
}

/**
 * Place marker on map and get location details
 */
function placeMarker(lat, lng) {
   if (!destinationMap) return;

    // Remove existing marker
   if (destinationMarker) {
        destinationMap.removeLayer(destinationMarker);
    }

    destinationMarker= L.marker([lat, lng]).addTo(destinationMap);

    // Get location details from reverse geocoding
    fetchLocationDetails(lat, lng);
}

/**
 * Geocode destination address
 */
async function geocodeDestination(address) {
    try {
        const response = await fetch(`/api/location/details?address=${encodeURIComponent(address)}`);
        const data = await response.json();

       if (data.success) {
            // Update hidden fields
            document.getElementById('latitude').value = data.latitude;
            document.getElementById('longitude').value = data.longitude;
            document.getElementById('region').value = data.region;
            document.getElementById('distance_km').value = data.distance_km;

            // Update map view
           if (destinationMap && typeof L !== 'undefined') {
                destinationMap.setView([data.latitude, data.longitude], 10);
                placeMarker(data.latitude, data.longitude);
            }

            console.log('Location details:', data);
        } else {
            console.warn('Geocoding failed:', data.message);
        }
    } catch (error) {
        console.error('Geocoding error:', error);
    }
}

/**
 * Fetch location details from coordinates
 */
async function fetchLocationDetails(lat, lng) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await response.json();

       if (data && data.address) {
            // Update destination input with formatted address
            const destinationInput = document.querySelector('input[name="destination"]');
           if (destinationInput && data.display_name) {
                destinationInput.value = formatAddress(data.address);
            }

            // Calculate distance from CLSU
            calculateDistanceFromCLSU(lat, lng);

            // Extract and set region
            const region = extractRegionFromAddress(data.address);
            document.getElementById('region').value = region;
        }
    } catch (error) {
        console.error('Reverse geocoding error:', error);
    }
}

/**
 * Calculate distance from CLSU
 */
function calculateDistanceFromCLSU(lat, lng) {
    const clsuLat = 15.0943;
    const clsuLng = 120.9686;

    const distance = haversineDistance(clsuLat, clsuLng, lat, lng);
    document.getElementById('distance_km').value = distance.toFixed(2);

    console.log(`Distance from CLSU: ${distance.toFixed(2)} km`);
}

/**
 * Haversine formula to calculate distance between two coordinates
 */
function haversineDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = deg2rad(lat2 - lat1);
    const dLon = deg2rad(lon2 - lon1);
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
        Math.sin(dLon / 2) * Math.sin(dLon/ 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
   return R * c;
}

function deg2rad(deg) {
   return deg * (Math.PI / 180);
}

/**
 * Format address from OSM data
 */
function formatAddress(address) {
    const parts = [];
   if (address.road) parts.push(address.road);
   if (address.city || address.town || address.village) {
        parts.push(address.city || address.town || address.village);
    }
   if (address.county) parts.push(address.county);
   if (address.state) parts.push(address.state);
   return parts.join(', ') || 'Philippines';
}

/**
 * Extract region from address
 */
function extractRegionFromAddress(address) {
    // Check if state contains region info
   if (address.state && /Region\s*[IVX]+/i.test(address.state)) {
        const match = address.state.match(/(Region\s*[IVX]+|[A-Z]\s*-\s*[A-Z]|MIMAROPA|CAR|BARMM)/i);
       if (match) return match[0];
    }

    // Map provinces to regions
    const provinceToRegion = {
        'Palawan': 'MIMAROPA (Region IV-B)',
        'Occidental Mindoro': 'MIMAROPA (Region IV-B)',
        'Oriental Mindoro': 'MIMAROPA (Region IV-B)',
        'Marinduque': 'MIMAROPA (Region IV-B)',
        'Romblon': 'MIMAROPA (Region IV-B)',
        'Aklan': 'Western Visayas (Region VI)',
        'Antique': 'Western Visayas (Region VI)',
        'Capiz': 'Western Visayas (Region VI)',
        'Iloilo': 'Western Visayas (Region VI)',
        'Guimaras': 'Western Visayas (Region VI)',
        'Negros Occidental': 'Western Visayas (Region VI)',
        'Cebu': 'Central Visayas (Region VII)',
        'Bohol': 'Central Visayas (Region VII)',
        'Negros Oriental': 'Central Visayas (Region VII)',
        'Siquijor': 'Central Visayas (Region VII)',
        'Metro Manila': 'National Capital Region (NCR)',
        'Manila': 'National Capital Region (NCR)',
        'Benguet': 'Cordillera Administrative Region (CAR)',
        'Baguio': 'Cordillera Administrative Region (CAR)',
        'Ifugao': 'Cordillera Administrative Region (CAR)',
        'Mountain Province': 'Cordillera Administrative Region (CAR)',
        'Ilocos Norte': 'Ilocos Region (Region I)',
        'Ilocos Sur': 'Ilocos Region (Region I)',
        'La Union': 'Ilocos Region (Region I)',
        'Pangasinan': 'Ilocos Region (Region I)',
        'Batanes': 'Cagayan Valley (Region II)',
        'Cagayan': 'Cagayan Valley (Region II)',
        'Isabela': 'Cagayan Valley (Region II)',
        'Nueva Vizcaya': 'Cagayan Valley (Region II)',
        'Quirino': 'Cagayan Valley (Region II)',
        'Aurora': 'Central Luzon (Region III)',
        'Bataan': 'Central Luzon (Region III)',
        'Bulacan': 'Central Luzon (Region III)',
        'Nueva Ecija': 'Central Luzon (Region III)',
        'Pampanga': 'Central Luzon (Region III)',
        'Tarlac': 'Central Luzon (Region III)',
        'Zambales': 'Central Luzon (Region III)',
    };

    for (const [province, region] of Object.entries(provinceToRegion)) {
       if ((address.county && address.county.includes(province)) ||
            (address.state && address.state.includes(province))) {
           return region;
        }
    }

   return address.state || 'Unknown Region';
}

/**
 * Confirm the selected location from map
 */
function confirmMapSelection() {
    const lat = document.getElementById('latitude').value;
    const lng = document.getElementById('longitude').value;

   if (!lat || !lng) {
        alert('Please select a location on the map first.');
       return;
    }

    // Close the map picker
    toggleMapPicker();

    // Show confirmation message
    const distance = document.getElementById('distance_km').value;
    const region = document.getElementById('region').value;

    console.log('Selected location:', {
        latitude: lat,
        longitude: lng,
       region: region,
        distance_km: distance
    });

    // Optional: Show success message
   if (window.Swal) {
        Swal.fire({
            icon: 'success',
            title: 'Location Selected',
            html: `<p>Destination set with coordinates:</p>
                   <p><strong>Region:</strong> ${region}</p>
                   <p><strong>Distance from CLSU:</strong> ${distance} km</p>`,
            timer: 3000
        });
    }
}

// Initialize when modal is shown (Bootstrap 5 event)
document.addEventListener('shown.bs.modal', function (event) {
    // Check if the opened modal is the create travel request modal
   const modal = document.getElementById('createTravelRequestModal');
   if (modal && modal.contains(event.target)) {
       console.log('🗺️ Initializing destination map picker in modal');
        initDestinationMapPicker();
    }
});

// Also initialize on DOM ready as fallback
if (document.readyState === 'loading') {
   document.addEventListener('DOMContentLoaded', initDestinationMapPicker);
} else {
    initDestinationMapPicker();
}
