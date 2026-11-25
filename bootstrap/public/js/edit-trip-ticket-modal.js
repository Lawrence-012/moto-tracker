// Edit Trip Ticket Modal - Enhanced Functions to match Create Modal

// Update itinerary details in edit modal - Load travel orders like Create modal
function updateEditItineraryDetails() {
    const itinerarySelect = document.getElementById('edit_itinerary_id');
    if (!itinerarySelect) return;
    
    const selectedOption = itinerarySelect.options[itinerarySelect.selectedIndex];
    if (!selectedOption) return;
    
    // Get data from option attributes
    const driver = selectedOption.getAttribute('data-driver') || '';
    const vehicleModel = selectedOption.getAttribute('data-vehicle') || '';
    const plateNumber = selectedOption.getAttribute('data-plate-number') || '';
    const destination = selectedOption.getAttribute('data-destination') || '';
    const purpose = selectedOption.getAttribute('data-purpose') || '';
    
    // Format vehicle with model and plate number
    const vehicleDisplay = vehicleModel.trim() + (plateNumber ? ' - Plate: ' + plateNumber : '');
    
    // Update preview fields
    const driverPreview = document.getElementById('edit_driver-preview');
    const vehiclePreview = document.getElementById('edit_vehicle-preview');
    const destPreview = document.getElementById('edit_destination-preview');
    const purposePreview = document.getElementById('edit_purpose-preview');
    
    if (driverPreview) driverPreview.value = driver.trim();
    if (vehiclePreview) vehiclePreview.value = vehicleDisplay;
    if (destPreview) destPreview.value = destination.trim();
    if (purposePreview) purposePreview.value = purpose.trim();
    
    // Load travel orders from selected itinerary (like Create modal)
    loadEditTravelOrdersFromItinerary();
}

// Load travel orders from itinerary in edit modal
function loadEditTravelOrdersFromItinerary() {
    const itinerarySelect = document.getElementById('edit_itinerary_id');
    const selectedItineraryId = itinerarySelect.value;
    
    if (!selectedItineraryId) {
        document.getElementById('edit-travel-orders-list').innerHTML = '<p class="text-muted mb-0 fst-italic small">Select an itinerary to load travel orders</p>';
        document.getElementById('edit-total-travel-orders').textContent = '0';
        return;
    }
    
    // Clear passengers when itinerary changes
    document.getElementById('edit_passenger-items').innerHTML = '';
    document.getElementById('edit_passenger_hidden_inputs').value = '';
    document.getElementById('edit_head_of_party').value = '';
    document.getElementById('edit_current-head').textContent = 'None selected';
    
    // Find itinerary data to get travel orders - fetch from server
    fetch(`/api/itineraries/${selectedItineraryId}/travel-orders`)
        .then(response => response.json())
        .then(data => {
            const travelOrders = data.travel_orders || [];
            
            if (travelOrders.length > 0) {
                const travelOrdersListDiv = document.getElementById('edit-travel-orders-list');
                travelOrdersListDiv.innerHTML = '';
                
                let count = 0;
                travelOrders.forEach(function(to) {
                    const employeeName = to.employee_name || 'N/A';
                    const destination = to.destination || 'No destination';
                    
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-success me-1 mb-1';
                    badge.style.fontSize = '0.7rem';
                    badge.textContent = employeeName + ' (' + destination + ')';
                    
                    travelOrdersListDiv.appendChild(badge);
                    count++;
                });
                
                document.getElementById('edit-total-travel-orders').textContent = count;
                
                // Automatically add all travel order passengers
                travelOrders.forEach(function(to) {
                    const employeeName = to.employee_name || 'N/A';
                    if (employeeName !== 'N/A') {
                        addEditPassengerAutomatically(employeeName, false);
                    }
                });
                
                // Set first passenger as head of party by default
                if (travelOrders.length > 0) {
                    const firstEmployee = travelOrders[0].employee_name;
                    if (firstEmployee) {
                        setTimeout(() => {
                            const firstItem = document.querySelector('#edit_passenger-items li:first-child');
                            if (firstItem) {
                                const headBtn = firstItem.querySelector('button');
                                if (headBtn) {
                                    setEditAsHeadOfParty(firstEmployee, firstEmployee, headBtn);
                                }
                            }
                        }, 100);
                    }
                }
            } else {
                document.getElementById('edit-travel-orders-list').innerHTML = '<p class="text-muted mb-0 fst-italic small">No travel orders in this itinerary</p>';
                document.getElementById('edit-total-travel-orders').textContent = '0';
            }
        })
        .catch(error => {
            console.error('Error loading travel orders:', error);
            document.getElementById('edit-travel-orders-list').innerHTML = '<p class="text-danger mb-0 fst-italic small">Error loading travel orders</p>';
            document.getElementById('edit-total-travel-orders').textContent = '0';
        });
}

// Add additional travel order in edit modal
function addEditAdditionalTravelOrder() {
    const select = document.getElementById('edit_additional_travel_order_select');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        const employeeName = selectedOption.getAttribute('data-employee');
        
        // Check if already added
        const existingItems = document.querySelectorAll('#edit_passenger-items li');
        let alreadyAdded = false;
        for (let item of existingItems) {
            if (item.textContent.includes(employeeName)) {
                alreadyAdded = true;
                break;
            }
        }
        
        if (!alreadyAdded) {
            addEditPassengerAutomatically(employeeName, false);
            
            // Remove from dropdown after adding
            selectedOption.remove();
            select.value = '';
        } else {
            alert(employeeName + ' is already in the passenger list.');
        }
    }
}
