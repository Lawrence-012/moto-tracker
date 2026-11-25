<!-- Edit Itinerary Modal -->
<div class="modal fade" id="editItineraryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content rounded-3 shadow">

      <!-- Header -->
      <div class="modal-header d-flex align-items-center" style="background-color:#1e6031; color:white;">
        <h5 class="modal-title fw-bold mb-0 flex-grow-1">Edit Itinerary</h5>
      </div>

      <!-- Form -->
      <form id="editItineraryForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body px-3 py-2">
          
          <!-- Travel Order Selection -->
          <div class="mb-2">
            <label for="edit_main_travel_order_id" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.85rem;">Main Travel Order <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm rounded" id="edit_main_travel_order_id" onchange="prefillEditMainTravelOrderData()" style="height: 35px; font-size: 0.75rem;" required>
              <option value="" disabled selected>-- Select Main Travel Order --</option>
              @foreach($travelOrdersForEdit as $travelOrder)
                @php
                    // Smart date formatting
                    $dateFrom = $travelOrder->date_from instanceof \DateTimeInterface ? $travelOrder->date_from : null;
                    $dateTo = $travelOrder->date_to instanceof \DateTimeInterface ? $travelOrder->date_to : null;
                    
                    $formattedDate = '';
                    if ($dateFrom && $dateTo) {
                        $fromMonth = $dateFrom->format('M');
                        $fromDay = $dateFrom->day;
                        $fromYear = $dateFrom->year;
                        
                        $toMonth = $dateTo->format('M');
                        $toDay = $dateTo->day;
                        $toYear = $dateTo->year;
                        
                        if ($dateFrom == $dateTo) {
                            $formattedDate = "{$fromMonth} {$fromDay}, {$fromYear}";
                        } elseif ($fromMonth === $toMonth && $fromYear === $toYear) {
                            $formattedDate = "{$fromMonth} {$fromDay}-{$toDay}, {$fromYear}";
                        } elseif ($fromYear === $toYear) {
                            $formattedDate = "{$fromMonth} {$fromDay} - {$toMonth} {$toDay}, {$fromYear}";
                        } else {
                            $formattedDate = "{$fromMonth} {$fromDay}, {$fromYear} - {$toMonth} {$toDay}, {$toYear}";
                        }
                    } elseif ($dateFrom) {
                        $formattedDate = $dateFrom->format('M j, Y');
                    } else {
                        $formattedDate = 'N/A';
                    }
                    
                    $formattedTime = '';
                    if ($travelOrder->departure_time) {
                        $timeParts = explode(':', $travelOrder->departure_time);
                        $hour = (int)$timeParts[0];
                        $minute = isset($timeParts[1]) ? $timeParts[1] : '00';
                        $ampm = $hour >= 12 ? 'PM' : 'AM';
                        $displayHour = $hour % 12;
                        $displayHour = $displayHour ? $displayHour : 12;
                        $formattedTime = "{$displayHour}:{$minute} {$ampm}";
                    } else {
                        $formattedTime = 'N/A';
                    }
                @endphp
                <option value="{{ $travelOrder->id }}" 
                        data-purpose="{{ $travelOrder->purpose }}"
                        data-date-from="{{ $dateFrom instanceof \DateTimeInterface ? $dateFrom->format('Y-m-d') : $dateFrom }}"
                        data-date-to="{{ $dateTo instanceof \DateTimeInterface ? $dateTo->format('Y-m-d') : $dateTo }}"
                        data-destination="{{ $travelOrder->destination ?? '' }}"
                        data-departure-time="{{ $travelOrder->departure_time instanceof \DateTimeInterface ? $travelOrder->departure_time->format('H:i:s') : ($travelOrder->departure_time ?? '') }}">
                    {{ $formattedDate }} ({{ $formattedTime }}) - {{ $travelOrder->employee?->full_name ?? 'Unknown' }} ({{ !empty($travelOrder->destination) ? $travelOrder->destination : 'No destination' }})
                </option>
              @endforeach
            </select>
          </div>

          <!-- Additional Travel Orders Section -->
          <div class="mb-2">
            <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.85rem;">Additional Travel Orders</label>
            <div class="input-group" style="height: 35px;">
              <select class="form-select form-select-sm rounded" id="edit_additional_travel_order_select" onchange="editAddAdditionalTravelOrder()" style="height: 35px; font-size: 0.75rem; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                <option value="" disabled selected>-- Select to Add --</option>
                @foreach($travelOrdersForEdit as $travelOrder)
                  @php
                      $dateFrom = $travelOrder->date_from instanceof \DateTimeInterface ? $travelOrder->date_from : null;
                      $dateTo = $travelOrder->date_to instanceof \DateTimeInterface ? $travelOrder->date_to : null;
                      
                      $formattedDate = '';
                      if ($dateFrom && $dateTo) {
                          $fromMonth = $dateFrom->format('M');
                          $fromDay = $dateFrom->day;
                          $fromYear = $dateFrom->year;
                          
                          if ($dateFrom == $dateTo) {
                              $formattedDate = "{$fromMonth} {$fromDay}, {$fromYear}";
                          } elseif ($fromMonth === $toMonth && $fromYear === $toYear) {
                              $formattedDate = "{$fromMonth} {$fromDay}-{$toDay}, {$fromYear}";
                          } elseif ($fromYear === $toYear) {
                              $formattedDate = "{$fromMonth} {$fromDay} - {$toMonth} {$toDay}, {$fromYear}";
                          } else {
                              $formattedDate = "{$fromMonth} {$fromDay}, {$fromYear} - {$toMonth} {$toDay}, {$toYear}";
                          }
                      } elseif ($dateFrom) {
                          $formattedDate = $dateFrom->format('M j, Y');
                      } else {
                          $formattedDate = 'N/A';
                      }
                  @endphp
                  <option value="{{ $travelOrder->id }}" 
                          data-display="{{ $formattedDate }} - {{ $travelOrder->employee?->full_name ?? 'Unknown' }} ({{ !empty($travelOrder->destination) ? $travelOrder->destination : 'No destination' }})"
                          data-destination="{{ $travelOrder->destination ?? '' }}">
                      {{ $formattedDate }} - {{ $travelOrder->employee?->full_name ?? 'Unknown' }} ({{ !empty($travelOrder->destination) ? $travelOrder->destination : 'No destination' }})
                  </option>
                @endforeach
              </select>
            </div>
            
            <!-- Selected Additional Travel Orders List - Plain Text Display -->
            <div id="edit-additional-travel-orders-list" class="mt-2" style="font-size: 0.75rem;">
              <p class="text-muted mb-0 fst-italic">No additional travel orders selected</p>
            </div>
            <small class="text-muted">Total: <span id="edit-total-travel-orders-count">1</span> travel order(s)</small>
            <input type="hidden" name="travel_order_ids[]" id="edit_hidden_main_travel_order">
          </div>
          
          <!-- Purpose -->
          <div class="mb-2">
            <label for="edit_purpose" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.75rem;">Purpose <span class="text-danger">*</span></label>
            <input type="text" name="purpose" id="edit_purpose" class="form-control form-control-sm rounded" placeholder="Enter purpose" style="height: 30px; font-size: 0.75rem;" required>
          </div>
          
          <!-- Destination -->
          <div class="mb-2">
            <label for="edit_destination" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.75rem;">Destination <span class="text-danger">*</span></label>
            <input type="text" name="destination" id="edit_destination" class="form-control form-control-sm rounded" placeholder="Enter destination" style="height: 30px; font-size: 0.75rem;" required>
          </div>

          <!-- Dates -->
          <div class="row g-2 mb-2">
            <div class="col">
              <label for="edit_date_from" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.75rem;">Date From <span class="text-danger">*</span></label>
              <input type="date" name="date_from" id="edit_date_from" class="form-control form-control-sm rounded" style="height: 30px; font-size: 0.75rem;" required>
            </div>
            <div class="col">
              <label for="edit_date_to" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.75rem;">Date To <span class="text-danger">*</span></label>
              <input type="date" name="date_to" id="edit_date_to" class="form-control form-control-sm rounded" style="height: 30px; font-size: 0.75rem;" required>
            </div>
          </div>

          <!-- Departure Time -->
          <div class="mb-2">
            <label for="edit_departure_time" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.75rem;">Departure Time <span class="text-danger">*</span></label>
            <input type="time" name="departure_time" id="edit_departure_time" class="form-control form-control-sm rounded" style="height: 30px; font-size: 0.75rem;" required>
          </div>
          
          <!-- Driver -->
          <div class="mb-2">
            <label for="edit_driver_id" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.85rem;">Driver <span class="text-danger">*</span></label>
            <select name="driver_id" id="edit_driver_id" class="form-select form-select-sm rounded" style="height: 38px; font-size: 0.85rem; min-width: 200px;" required>
              <option value="" disabled>-- Select Driver --</option>
              @php
                  $sortedDrivers = $drivers->where('availability_status', 'Active')->sortBy(function($driver) {
                      return strtolower($driver->first_name . ' ' . $driver->last_name);
                  });
              @endphp
              @foreach($sortedDrivers as $driver)
                <option value="{{ $driver->id }}" data-position="{{ $driver->position }}" data-station="{{ $driver->official_station }}">
                  {{ $driver->first_name }} {{ $driver->last_name }}
                </option>
              @endforeach
            </select>
          </div>
          
          <div class="row g-2 mb-2">
            <div class="col">
              <input type="text" id="edit_driver_position" class="form-control form-control-sm rounded" placeholder="Position" readonly style="height: 30px; font-size: 0.75rem;">
            </div>
            <div class="col">
              <input type="text" id="edit_driver_station" class="form-control form-control-sm rounded" placeholder="Station" readonly style="height: 30px; font-size: 0.75rem;">
            </div>
          </div>

          <!-- Vehicle -->
          <div class="mb-2">
            <label for="edit_vehicle_id" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.85rem;">Vehicle <span class="text-danger">*</span></label>
            <select name="vehicle_id" id="edit_vehicle_id" class="form-select form-select-sm rounded" style="height: 38px; font-size: 0.85rem; min-width: 200px;" required>
              <option value="" disabled>-- Select Vehicle --</option>
              @php
                  $sortedVehicles = $vehicles->whereIn('status', ['Available', 'Active'])->sortBy(function($vehicle) {
                      return strtolower($vehicle->plate_number);
                  });
              @endphp
              @foreach($sortedVehicles as $vehicle)
                <option value="{{ $vehicle->id }}" data-model="{{ $vehicle->model }}" data-type="{{ $vehicle->type }}" data-seating-capacity="{{ $vehicle->seating_capacity ?? 0 }}">
                  {{ $vehicle->plate_number }} - {{ $vehicle->model }}
                </option>
              @endforeach
            </select>
          </div>
          
          <div class="row g-2 mb-0">
            <div class="col">
              <input type="text" id="edit_vehicle_type" class="form-control form-control-sm rounded" placeholder="Type" readonly style="height: 30px; font-size: 0.75rem;">
            </div>
            <div class="col">
              <input type="text" id="edit_vehicle_capacity" class="form-control form-control-sm rounded" placeholder="Capacity" readonly style="height: 30px; font-size: 0.75rem;">
            </div>
          </div>
          
          <!-- Passengers Section (Read-only display of all passengers from selected travel orders) -->
          <div class="mb-2 mt-2">
            <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.85rem;">Passengers (from selected Travel Orders)</label>
            <div id="edit-passengers-list" class="mt-1" style="font-size: 0.75rem;">
              <p class="text-muted mb-0 fst-italic">Select a travel order to see passengers</p>
            </div>
            <small class="text-muted">Total: <span id="edit-total-passengers-count">0</span> passenger(s)</small>
          </div>
          
        </div>

        <!-- Footer -->
        <div class="modal-footer py-2 justify-content-end">
          <button type="button" class="btn btn-sm btn-outline-secondary me-1" style="font-size: 0.75rem; height: 30px;" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-success" style="font-size: 0.75rem; height: 30px;">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Store additional travel orders for edit mode
let editAdditionalTravelOrders = [];

// Store complete travel order data from Blade (to avoid DOM reading issues)
@php
    $editTravelOrdersData = $travelOrdersForEdit->map(function($to) {
        return [
            'id' => $to->id,
            'formattedDate' => $to->date_from ? \Carbon\Carbon::parse($to->date_from)->format('M j, Y') . ($to->date_to && $to->date_from != $to->date_to ? ' - ' . \Carbon\Carbon::parse($to->date_to)->format('M j, Y') : '') : 'N/A',
            'employeeName' => $to->employee?->full_name ?? 'Unknown',
            'destination' => $to->destination ?? 'No destination',
            'departureTime' => $to->departure_time ? \Carbon\Carbon::parse($to->departure_time)->format('g:i A') : 'N/A',
        ];
    })->values();
@endphp
const editAllTravelOrdersData = @json($editTravelOrdersData);

// Function to populate and show the edit itinerary modal
window.showEditItineraryModal = function(itineraryData) {
    console.log('=== EDIT ITINERARY DATA ===');
    console.log('Full itinerary data:', itineraryData);
    console.log('Travel order ID:', itineraryData.travel_order_id);
    console.log('Type of travel_order_id:', typeof itineraryData.travel_order_id);
    console.log('Is array?', Array.isArray(itineraryData.travel_order_id));
    
    // Store the driver and vehicle IDs for later restoration
    const savedDriverId = itineraryData.driver_id || '';
    const savedVehicleId = itineraryData.vehicle_id || '';
    
    // Set the form action URL
    const form = document.getElementById('editItineraryForm');
    form.action = `/itinerary/${itineraryData.id}`;
    
    // Populate the modal fields
    document.getElementById('edit_date_from').value = itineraryData.date_from ? formatDateForInput(itineraryData.date_from) : '';
    document.getElementById('edit_date_to').value = itineraryData.date_to ? formatDateForInput(itineraryData.date_to) : '';
    document.getElementById('edit_departure_time').value = itineraryData.departure_time || '';
    document.getElementById('edit_purpose').value = itineraryData.purpose || '';
    document.getElementById('edit_destination').value = itineraryData.destination || '';
    
    // Don't set driver and vehicle yet - wait until dropdowns are populated
    
    // Handle travel order selection - set current travel_order_id(s)
    const mainSelect = document.getElementById('edit_main_travel_order_id');
    
    // Handle both single ID and array of IDs
    let travelOrderIds = [];
    if (itineraryData.travel_order_id) {
        // If it's a string (single ID), convert to array
        if (typeof itineraryData.travel_order_id === 'string') {
            travelOrderIds = [itineraryData.travel_order_id];
        } 
        // If it's already an array, use it directly
        else if (Array.isArray(itineraryData.travel_order_id)) {
            travelOrderIds = itineraryData.travel_order_id;
        }
        // If it's an object with id property (legacy)
        else if (itineraryData.travel_order_id.id) {
            travelOrderIds = [itineraryData.travel_order_id.id];
        }
        
        console.log('Parsed travel order IDs:', travelOrderIds);
    }
    
    // Set the first travel order as main, rest as additional
    if (travelOrderIds.length > 0) {
        // Set main travel order
        mainSelect.value = travelOrderIds[0];
        prefillEditMainTravelOrderData();
        
        // Add remaining travel orders to additional list
        if (travelOrderIds.length > 1) {
            editAdditionalTravelOrders = travelOrderIds.slice(1);
            renderEditAdditionalTravelOrdersList();
            updateEditTotalCount();
            refreshEditAdditionalTravelOrderDropdown();
        }
    }
    
    // Store saved driver and vehicle IDs in data attributes for restoration after dropdowns load
    const driverSelect = document.getElementById('edit_driver_id');
    const vehicleSelect = document.getElementById('edit_vehicle_id');
    
    if (savedDriverId) {
        driverSelect.setAttribute('data-saved-value', savedDriverId);
    }
    if (savedVehicleId) {
        vehicleSelect.setAttribute('data-saved-value', savedVehicleId);
    }
    
    // Show the modal
    var myModal = new bootstrap.Modal(document.getElementById('editItineraryModal'));
    myModal.show();
};

// Helper function to format date for input field
function formatDateForInput(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().split('T')[0];
}

// Prefill data when main travel order is selected in edit mode
function prefillEditMainTravelOrderData() {
    const mainSelect = document.getElementById('edit_main_travel_order_id');
    const selectedOption = mainSelect.options[mainSelect.selectedIndex];
    
    // Update hidden input for main travel order
    document.getElementById('edit_hidden_main_travel_order').value = selectedOption.value || '';
    
    if (!selectedOption.value) {
        // Clear hidden date fields
        document.getElementById('edit_date_from').value = '';
        document.getElementById('edit_date_to').value = '';
        document.getElementById('edit_destination').value = '';
        document.getElementById('edit_departure_time').value = '';
        resetEditDriverAndVehicleOptions();
        updateEditTotalCount();
        // Refresh dropdown to show all options when main TO is cleared
        refreshEditAdditionalTravelOrderDropdown();
        // Clear passengers list
        updateEditPassengersList();
        return;
    }
    
    // CLEAR additional travel orders and passengers when main travel order changes
    editAdditionalTravelOrders = [];
    renderEditAdditionalTravelOrdersList();
    
    // Get dates from the selected option
    const dateFrom = selectedOption.getAttribute('data-date-from');
    const dateTo = selectedOption.getAttribute('data-date-to');
    const destination = selectedOption.getAttribute('data-destination');
    const departureTime = selectedOption.getAttribute('data-departure-time');
    
    if (dateFrom && dateTo) {
        document.getElementById('edit_date_from').value = dateFrom;
        document.getElementById('edit_date_to').value = dateTo;
        
        if (destination) {
            document.getElementById('edit_destination').value = destination;
        }
        if (departureTime) {
            document.getElementById('edit_departure_time').value = departureTime;
        }
        
        // Update available drivers and vehicles based on date range
        updateEditAvailableDriversForDateRange(dateFrom, dateTo);
        updateEditAvailableVehiclesForDateRange(dateFrom, dateTo);
    } else {
        resetEditDriverAndVehicleOptions();
    }
    
    updateEditTotalCount();
    
    // Refresh dropdown to hide the selected main travel order
    refreshEditAdditionalTravelOrderDropdown();
}

// Add additional travel order to the list in edit mode
function editAddAdditionalTravelOrder() {
    const select = document.getElementById('edit_additional_travel_order_select');
    const selectedOption = select.options[select.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        return;
    }
    
    const travelOrderId = selectedOption.value;
    const displayText = selectedOption.getAttribute('data-display');
    
    // Check if already added
    if (editAdditionalTravelOrders.includes(travelOrderId)) {
        Swal.fire({
            icon: 'warning',
            title: 'Already Added',
            text: 'This travel order has already been added to the list.',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }
    
    // Add to array
    editAdditionalTravelOrders.push(travelOrderId);
    
    // Update display
    renderEditAdditionalTravelOrdersList();
    updateEditTotalCount();
    
    // Refresh the dropdown to hide selected travel orders
    refreshEditAdditionalTravelOrderDropdown();
    
    // Reset dropdown
    select.value = '';
}

// Refresh additional travel order dropdown to filter out selected travel orders in edit mode
function refreshEditAdditionalTravelOrderDropdown() {
    const mainSelect = document.getElementById('edit_main_travel_order_id');
    const additionalSelect = document.getElementById('edit_additional_travel_order_select');
    
    // Get the currently selected main travel order ID
    const mainTravelOrderId = mainSelect.value;
    
    // Get the date range from the selected main travel order
    const selectedOption = mainSelect.options[mainSelect.selectedIndex];
    const mainDateFrom = selectedOption.getAttribute('data-date-from');
    const mainDateTo = selectedOption.getAttribute('data-date-to');
    
    // Clear current options except placeholder
    additionalSelect.innerHTML = '<option value="" disabled selected>-- Select to Add --</option>';
    
    // Build options from our data array (not from DOM)
    editAllTravelOrdersData.forEach(toData => {
        const optionId = toData.id.toString();
        
        // Skip if this is the main travel order
        if (optionId === mainTravelOrderId) {
            return;
        }
        
        // Skip if already in additional travel orders array
        if (editAdditionalTravelOrders.includes(optionId)) {
            return;
        }
        
        // Filter by date range if main travel order has dates
        if (mainDateFrom && mainDateTo) {
            // Get this travel order's dates from the original data
            const toElement = Array.from(document.querySelectorAll('#edit_main_travel_order_id option'))
                .find(opt => opt.value === optionId);
            
            if (toElement) {
                const toDateFrom = toElement.getAttribute('data-date-from');
                const toDateTo = toElement.getAttribute('data-date-to');
                
                // Check if travel order's date range overlaps with main travel order's date range
                if (toDateFrom && toDateTo) {
                    // No overlap if: ends before main starts OR starts after main ends
                    if (toDateTo < mainDateFrom || toDateFrom > mainDateTo) {
                        return; // Skip this travel order - no date overlap
                    }
                }
            }
        }
        
        // Create option with full format: Date - Employee Name (Destination)
        const displayText = `${toData.formattedDate} - ${toData.employeeName} (${toData.destination})`;
        const newOption = document.createElement('option');
        newOption.value = optionId;
        newOption.textContent = displayText;
        newOption.setAttribute('data-display', displayText);
        newOption.setAttribute('data-destination', toData.destination);
        additionalSelect.appendChild(newOption);
    });
}

// Remove additional travel order from the list in edit mode
function editRemoveAdditionalTravelOrder(travelOrderId) {
    editAdditionalTravelOrders = editAdditionalTravelOrders.filter(id => id !== travelOrderId);
    renderEditAdditionalTravelOrdersList();
    updateEditTotalCount();
    
    // Refresh the dropdown to show the removed travel order again
    refreshEditAdditionalTravelOrderDropdown();
}

// Render the additional travel orders list in edit mode
function renderEditAdditionalTravelOrdersList() {
    const listDiv = document.getElementById('edit-additional-travel-orders-list');
    
    if (editAdditionalTravelOrders.length === 0) {
        listDiv.innerHTML = '<p class="text-muted mb-0 fst-italic">No additional travel orders selected</p>';
        return;
    }
    
    // Display as plain numbered text with employee name and destination
    let html = '';
    editAdditionalTravelOrders.forEach((id, index) => {
        // Find the travel order data from our stored array
        const toData = editAllTravelOrdersData.find(to => to.id == id);
        
        // Display date - employee name (destination)
        const displayText = toData 
            ? `${toData.formattedDate} - ${toData.employeeName} (${toData.destination})`
            : `TO #${id}`;
        
        html += `<div class="mb-1 d-flex justify-content-between align-items-center">
            <span class="small"><strong>${index + 1}.</strong> ${displayText}</span>
            <button type="button" class="btn btn-xs btn-danger ms-2" onclick="editRemoveAdditionalTravelOrder('${id}')" style="font-size: 0.65rem; padding: 2px 6px;">
                <i class="fas fa-times"></i> Remove
            </button>
        </div>`;
    });
    
    listDiv.innerHTML = html;
}

// Update total count display in edit mode
function updateEditTotalCount() {
    const mainSelect = document.getElementById('edit_main_travel_order_id');
    const count = (mainSelect.value ? 1 : 0) + editAdditionalTravelOrders.length;
    document.getElementById('edit-total-travel-orders-count').textContent = count;
    
    // Also update passengers list when count changes
    updateEditPassengersList();
}

// Update passengers list from all selected travel orders in edit mode
function updateEditPassengersList() {
    const allTravelOrderIds = getAllEditTravelOrderIds();
    const passengersListDiv = document.getElementById('edit-passengers-list');
    const totalCountSpan = document.getElementById('edit-total-passengers-count');
    
    if (allTravelOrderIds.length === 0) {
        passengersListDiv.innerHTML = '<p class="text-muted mb-0 fst-italic">Select a travel order to see passengers</p>';
        totalCountSpan.textContent = '0';
        return;
    }
    
    // Fetch passengers for each travel order
    const allPassengers = [];
    const passengerMap = new Map(); // To avoid duplicates
    
    Promise.all(allTravelOrderIds.map(id => 
        fetch(`/api/travel-orders/${id}/passengers`).then(res => res.json())
    ))
    .then(results => {
        results.forEach(data => {
            if (data.passengers && Array.isArray(data.passengers)) {
                data.passengers.forEach(passenger => {
                    // Use employee_id as key to avoid duplicates
                    const key = passenger.employee_id || passenger.name;
                    if (!passengerMap.has(key)) {
                        passengerMap.set(key, passenger);
                        allPassengers.push(passenger);
                    }
                });
            }
        });
        
        // Display passengers as plain numbered text
        if (allPassengers.length === 0) {
            passengersListDiv.innerHTML = '<p class="text-muted mb-0 fst-italic">No passengers in selected travel orders</p>';
        } else {
            let html = '';
            allPassengers.forEach((passenger, index) => {
                const name = passenger.name || passenger.full_name || 'Unknown';
                const position = passenger.position || passenger.official_station || '';
                const office = passenger.office || '';
                
                html += `<div class="mb-1">${index + 1}. ${name}`;
                if (position) {
                    html += ` <span class="text-muted">(${position}${office ? ` - ${office}` : ''})</span>`;
                }
                html += `</div>`;
            });
            passengersListDiv.innerHTML = html;
        }
        
        totalCountSpan.textContent = allPassengers.length;
    })
    .catch(error => {
        console.error('Error fetching passengers:', error);
        passengersListDiv.innerHTML = '<p class="text-danger mb-0 fst-italic">Error loading passengers</p>';
        totalCountSpan.textContent = '0';
    });
}

// Collect all travel order IDs for form submission in edit mode
function getAllEditTravelOrderIds() {
    const mainSelect = document.getElementById('edit_main_travel_order_id');
    const ids = [];
    
    if (mainSelect.value) {
        ids.push(mainSelect.value);
    }
    
    ids.push(...editAdditionalTravelOrders);
    
    return ids;
}

// Update available drivers based on date range from travel order in edit mode
function updateEditAvailableDriversForDateRange(dateFrom, dateTo) {
    console.log('Updating drivers for date range:', dateFrom, 'to', dateTo);
    
    // Pass both dates to the API (format: dateFrom|dateTo)
    const dateParam = `${dateFrom}|${dateTo}`;
    
    fetch(`/api/available-drivers/${dateParam}`)
        .then(response => {
            console.log('Driver API response status:', response.status);
            return response.json();
        })
        .then(drivers => {
            console.log('Available drivers count:', drivers.length);
            const driverSelect = document.getElementById('edit_driver_id');
            const savedDriverId = driverSelect.getAttribute('data-saved-value') || '';
            
            // Clear existing options except the first placeholder
            driverSelect.innerHTML = '<option value="" disabled>-- Select Driver --</option>';
            
            // If there's a saved driver ID, fetch their details first and add to dropdown
            if (savedDriverId) {
                fetch(`/api/drivers/${savedDriverId}`)
                    .then(res => res.json())
                    .then(driverData => {
                        if (driverData && driverData.id) {
                            // Add the saved driver to the dropdown even if not available
                            const option = document.createElement('option');
                            option.value = driverData.id;
                            option.textContent = `${driverData.first_name} ${driverData.last_name} (Current)`;
                            option.setAttribute('data-position', driverData.position || '');
                            option.setAttribute('data-station', driverData.official_station || '');
                            option.setAttribute('data-contact', driverData.contact_num || '');
                            driverSelect.appendChild(option);
                            console.log('✅ Added current driver to dropdown:', savedDriverId);
                            console.log('   - Position:', driverData.position);
                            console.log('   - Station:', driverData.official_station);
                        }
                        
                        // Now add all available drivers
                        drivers.forEach(driver => {
                            // Don't add duplicate if it's the same as saved driver
                            if (driver.id != savedDriverId) {
                                const option = document.createElement('option');
                                option.value = driver.id;
                                option.textContent = `${driver.first_name} ${driver.last_name}`;
                                option.setAttribute('data-position', driver.position || '');
                                option.setAttribute('data-station', driver.official_station || '');
                                option.setAttribute('data-contact', driver.contact_num || '');
                                driverSelect.appendChild(option);
                            }
                        });
                        
                        // Now set the saved driver as selected
                        driverSelect.value = savedDriverId;
                        console.log('✅ Driver restored:', savedDriverId);
                        updateDriverDetailsEdit(driverSelect);
                    })
                    .catch(err => console.error('Error fetching driver details:', err));
            } else {
                // No saved driver, just add available drivers
                drivers.forEach(driver => {
                    const option = document.createElement('option');
                    option.value = driver.id;
                    option.textContent = `${driver.first_name} ${driver.last_name}`;
                    option.setAttribute('data-position', driver.position || '');
                    option.setAttribute('data-station', driver.official_station || '');
                    option.setAttribute('data-contact', driver.contact_num || '');
                    driverSelect.appendChild(option);
                });
                driverSelect.value = '';
                updateDriverDetailsEdit(driverSelect);
            }
            
            console.log('Driver dropdown updated with', drivers.length + (savedDriverId ? 1 : 0), 'drivers');
        })
        .catch(error => console.error('Error fetching available drivers:', error));
}

// Update available vehicles based on date range from travel order in edit mode
function updateEditAvailableVehiclesForDateRange(dateFrom, dateTo) {
    console.log('Updating vehicles for date range:', dateFrom, 'to', dateTo);
    
    // Pass both dates to the API (format: dateFrom|dateTo)
    const dateParam = `${dateFrom}|${dateTo}`;
    
    fetch(`/api/available-vehicles/${dateParam}`)
        .then(response => {
            console.log('Vehicle API response status:', response.status);
            return response.json();
        })
        .then(vehicles => {
            console.log('Available vehicles count:', vehicles.length);
            const vehicleSelect = document.getElementById('edit_vehicle_id');
            const savedVehicleId = vehicleSelect.getAttribute('data-saved-value') || '';
            
            // Clear existing options except the first placeholder
            vehicleSelect.innerHTML = '<option value="" disabled>-- Select Vehicle --</option>';
            
            // If there's a saved vehicle ID, fetch its details first and add to dropdown
            if (savedVehicleId) {
                fetch(`/api/vehicles/${savedVehicleId}`)
                    .then(res => res.json())
                    .then(vehicleData => {
                        if (vehicleData && vehicleData.id) {
                            // Add the saved vehicle to the dropdown even if not available
                            const option = document.createElement('option');
                            option.value = vehicleData.id;
                            option.textContent = `${vehicleData.plate_number} (Current)`;
                            option.setAttribute('data-model', vehicleData.model || '');
                            option.setAttribute('data-type', vehicleData.type || '');
                            option.setAttribute('data-capacity', vehicleData.seating_capacity || '');
                            vehicleSelect.appendChild(option);
                            console.log('✅ Added current vehicle to dropdown:', savedVehicleId);
                            console.log('   - Type:', vehicleData.type);
                            console.log('   - Capacity:', vehicleData.seating_capacity);
                        }
                        
                        // Now add all available vehicles
                        vehicles.forEach(vehicle => {
                            // Don't add duplicate if it's the same as saved vehicle
                            if (vehicle.id != savedVehicleId) {
                                const option = document.createElement('option');
                                option.value = vehicle.id;
                                option.textContent = `${vehicle.plate_number}`;
                                option.setAttribute('data-model', vehicle.model || '');
                                option.setAttribute('data-type', vehicle.type || '');
                                option.setAttribute('data-capacity', vehicle.seating_capacity || '');
                                vehicleSelect.appendChild(option);
                            }
                        });
                        
                        // Now set the saved vehicle as selected
                        vehicleSelect.value = savedVehicleId;
                        console.log('✅ Vehicle restored:', savedVehicleId);
                        updateVehicleDetailsEdit(vehicleSelect);
                    })
                    .catch(err => console.error('Error fetching vehicle details:', err));
            } else {
                // No saved vehicle, just add available vehicles
                vehicles.forEach(vehicle => {
                    const option = document.createElement('option');
                    option.value = vehicle.id;
                    option.textContent = `${vehicle.plate_number}`;
                    option.setAttribute('data-model', vehicle.model || '');
                    option.setAttribute('data-type', vehicle.type || '');
                    option.setAttribute('data-capacity', vehicle.seating_capacity || '');
                    vehicleSelect.appendChild(option);
                });
                vehicleSelect.value = '';
                updateVehicleDetailsEdit(vehicleSelect);
            }
            
            console.log('Vehicle dropdown updated with', vehicles.length + (savedVehicleId ? 1 : 0), 'vehicles');
        })
        .catch(error => console.error('Error fetching available vehicles:', error));
}

// Reset driver and vehicle options to show all available in edit mode
function resetEditDriverAndVehicleOptions() {
    // Reset driver options
    const driverSelect = document.getElementById('edit_driver_id');
    driverSelect.innerHTML = '<option value="" disabled selected>-- Select Driver --</option>';
    
    // We'll need to store the original driver options to restore them
    // For now, we'll just clear the selection
    driverSelect.value = '';
    
    // Reset vehicle options
    const vehicleSelect = document.getElementById('edit_vehicle_id');
    vehicleSelect.innerHTML = '<option value="" disabled selected>-- Select Vehicle --</option>';
    vehicleSelect.value = '';
}

// Function to update driver details
function updateDriverDetailsEdit(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const position = selectedOption.getAttribute('data-position');
    const station = selectedOption.getAttribute('data-station');
    
    document.getElementById('edit_driver_position').value = position || '';
    document.getElementById('edit_driver_station').value = station || '';
}

// Function to update vehicle details
function updateVehicleDetailsEdit(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const type = selectedOption.getAttribute('data-type');
    const capacity = selectedOption.getAttribute('data-capacity');
    
    document.getElementById('edit_vehicle_type').value = type || '';
    document.getElementById('edit_vehicle_capacity').value = capacity || '';
}

// Handle form submission
document.getElementById('editItineraryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    
    // Get all travel order IDs (main + additional)
    const allTravelOrderIds = getAllEditTravelOrderIds();
    
    // Validate that at least one travel order is selected
    if (allTravelOrderIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Travel Order Selected',
            text: 'Please select at least one travel order to update the itinerary.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    // Clear existing travel_order_ids and add the correct ones
    formData.delete('travel_order_ids[]');
    allTravelOrderIds.forEach(toId => {
        formData.append('travel_order_ids[]', toId);
    });
    
    console.log('Selected travel order IDs:', allTravelOrderIds);
    console.log('Total travel orders being saved:', allTravelOrderIds.length);
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
    submitBtn.disabled = true;
    
    // Submit the form via AJAX
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-HTTP-Method-Override': 'PUT'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success alert
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Itinerary updated successfully!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Close the modal and reload the page
                const modal = bootstrap.Modal.getInstance(document.getElementById('editItineraryModal'));
                if (modal) {
                    modal.hide();
                }
                // Reload the page to show the updated itinerary
                location.reload();
            });
        } else {
            // Show error alert
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message || 'An error occurred while updating the itinerary.'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred while updating the itinerary. Please try again.'
        });
    })
    .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Add event listeners for driver and vehicle select changes
document.addEventListener('DOMContentLoaded', function() {
    const driverSelect = document.getElementById('edit_driver_id');
    const vehicleSelect = document.getElementById('edit_vehicle_id');
    
    if (driverSelect) {
        driverSelect.addEventListener('change', function() {
            updateDriverDetailsEdit(this);
        });
    }
    
    if (vehicleSelect) {
        vehicleSelect.addEventListener('change', function() {
            updateVehicleDetailsEdit(this);
        });
    }
    
    // Add event listeners for date inputs to update available drivers and vehicles
    const dateFromInput = document.getElementById('edit_date_from');
    const dateToInput = document.getElementById('edit_date_to');
    
    if (dateFromInput) {
        dateFromInput.addEventListener('change', function() {
            const selectedDate = this.value;
            if (selectedDate) {
                updateEditAvailableDriversForDateRange(selectedDate, document.getElementById('edit_date_to').value || selectedDate);
                updateEditAvailableVehiclesForDateRange(selectedDate, document.getElementById('edit_date_to').value || selectedDate);
            } else {
                resetEditDriverAndVehicleOptions();
            }
        });
    }
    
    if (dateToInput) {
        dateToInput.addEventListener('change', function() {
            const selectedDate = this.value;
            if (selectedDate) {
                updateEditAvailableDriversForDateRange(document.getElementById('edit_date_from').value || selectedDate, selectedDate);
                updateEditAvailableVehiclesForDateRange(document.getElementById('edit_date_from').value || selectedDate, selectedDate);
            } else {
                resetEditDriverAndVehicleOptions();
            }
        });
    }
});
</script>