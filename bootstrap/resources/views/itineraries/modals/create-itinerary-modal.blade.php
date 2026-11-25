<!-- Create Itinerary Modal -->
<div class="modal fade" id="createItineraryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md"><!-- medium modal -->
    <div class="modal-content rounded-3 shadow">

      <!-- Header -->
      <div class="modal-header d-flex align-items-center" style="background-color:#1e6031; color:white;">
        <h5 class="modal-title fw-bold mb-0 flex-grow-1">Create Itinerary</h5>
      </div>

      <!-- Form -->
      <form id="itineraryForm" action="{{ route('itinerary.store') }}" method="POST">
        @csrf
        <div class="modal-body px-3 py-2">

        
          <!-- Travel Order Selection -->
        <div class="mb-2">
          <label for="main_travel_order_id" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.85rem;">Main Travel Order <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm rounded" id="main_travel_order_id" onchange="prefillMainTravelOrderData()" style="height: 35px; font-size: 0.75rem;" required>
            <option value="" disabled selected>-- Select Main Travel Order --</option>
            @foreach($travelOrdersForCreate as $travelOrder)
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
            <select class="form-select form-select-sm rounded" id="additional_travel_order_select" onchange="addAdditionalTravelOrder()" style="height: 35px; font-size: 0.75rem;">
              <option value="" disabled selected>-- Select to Add --</option>
              @foreach($travelOrdersForCreate as $travelOrder)
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
                    
          <!-- Selected Additional Travel Orders List -->
          <div id="additional-travel-orders-list" style="max-height: 150px; overflow-y: auto; font-size: 0.75rem; margin-top: 8px;">
            <p class="text-muted mb-0 fst-italic">No additional travel orders selected</p>
          </div>
          <small class="text-muted">Total: <span id="total-travel-orders-count">1</span> travel order(s)</small>
          <input type="hidden" name="travel_order_ids[]" id="hidden_main_travel_order">
        </div>

          <!-- Purpose -->
          <div class="mb-2">
            <label for="purpose" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.75rem;">Purpose <span class="text-danger">*</span></label>
            <input type="text" name="purpose" id="purpose" class="form-control form-control-sm rounded" value="TO DRIVE UNIVERSITY VEHICLE WHILE ON OFFICIAL TRIP" placeholder="Enter purpose" style="height: 30px; font-size: 0.75rem;" required>
          </div>
          
          <!-- Driver -->
          <div class="mb-2">
            <label for="driver_id" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.75rem;">Driver <span class="text-danger">*</span></label>
            <select name="driver_id" id="driver_id" class="form-select form-select-sm rounded" style="height: 35px; font-size: 0.75rem; text-align: left;" required>
              <option value="" disabled selected>-- Select Driver --</option>
              @php
                  $sortedDrivers = $drivers->where('availability_status', 'Active')->sortBy(function($driver) {
                      return strtolower($driver->first_name . ' ' . $driver->last_name);
                  });
              @endphp
              @foreach($sortedDrivers as $driver)
                <option value="{{ $driver->id }}" data-position="{{ $driver->position }}" data-contact="{{ $driver->contact_num ?? '' }}">
                  {{ $driver->first_name }} {{ $driver->last_name }}
                </option>
              @endforeach
            </select>
          </div>
          
          <div class="row g-2 mb-2">
            <div class="col">
              <input type="text" id="driver_position" class="form-control form-control-sm rounded" placeholder="Contact Number" readonly style="height: 30px; font-size: 0.75rem;">
            </div>
            <div class="col">
              <input type="text" id="driver_station" class="form-control form-control-sm rounded" placeholder="Position" readonly style="height: 30px; font-size: 0.75rem;">
            </div>
          </div>

          <!-- Vehicle -->
          <div class="mb-2">
            <label for="vehicle_id" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.75rem;">Vehicle <span class="text-danger">*</span></label>
            <select name="vehicle_id" id="vehicle_id" class="form-select form-select-sm rounded" style="height: 35px; font-size: 0.75rem; text-align: left;" required>
              <option value="" disabled selected>-- Select Vehicle --</option>
              @php
                  $sortedVehicles = $vehicles->whereIn('status', ['Available', 'Active'])->sortBy(function($vehicle) {
                      return strtolower($vehicle->plate_number);
                  });
              @endphp
              @foreach($sortedVehicles as $vehicle)
                <option value="{{ $vehicle->id }}" data-model="{{ $vehicle->model }}" data-type="{{ $vehicle->type }}" data-seating-capacity="{{ $vehicle->seating_capacity ?? 0 }}">
                  {{ $vehicle->plate_number }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="row g-2 mb-2">
            <div class="col">
              <input type="text" id="vehicle_model" class="form-control form-control-sm rounded" placeholder="Model" readonly style="height: 30px; font-size: 0.75rem;">
            </div>
            <div class="col">
              <input type="text" id="vehicle_type" class="form-control form-control-sm rounded" placeholder="Type" readonly style="height: 30px; font-size: 0.75rem;">
            </div>
          </div>

          <input type="hidden" name="status" value="Not yet Approved">
          <input type="hidden" id="date_from" name="date_from" value="">
          <input type="hidden" id="date_to" name="date_to" value="">
          <input type="hidden" id="destination" name="destination" value="">
          <input type="hidden" id="departure_time" name="departure_time" value="">
          
          <!-- Passengers Section (Read-only display of all passengers from selected travel orders) -->
          <div class="mb-2">
            <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.85rem;">Passengers (from selected Travel Orders)</label>
            <div id="passengers-list" style="max-height: 120px; overflow-y: auto; font-size: 0.75rem;">
              <p class="text-muted mb-0 fst-italic">Select a travel order to see passengers</p>
            </div>
            <small class="text-muted">Total: <span id="total-passengers-count">0</span> passenger(s)</small>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer py-1 justify-content-end">
          <button type="button" id="cancelItineraryBtn" class="btn btn-sm btn-outline-secondary me-1" style="font-size: 0.75rem; height: 30px;"> Cancel
          </button>
          <button type="button" id="saveItineraryBtn" class="btn btn-sm btn-success" style="font-size: 0.75rem; height: 30px; background-color: #1e6031; border-color: #1e6031;"> Save 
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Store additional travel orders
let additionalTravelOrders = [];

// Store complete travel order data from Blade (to avoid DOM reading issues)
@php
    $travelOrdersData = $travelOrdersForCreate->map(function($to) {
        return [
            'id' => $to->id,
            'formattedDate' => $to->date_from ? \Carbon\Carbon::parse($to->date_from)->format('M j, Y') . ($to->date_to && $to->date_from != $to->date_to ? ' - ' . \Carbon\Carbon::parse($to->date_to)->format('M j, Y') : '') : 'N/A',
            'employeeName' => $to->employee?->full_name ?? 'Unknown',
            'destination' => $to->destination ?? 'No destination',
            'departureTime' => $to->departure_time ? \Carbon\Carbon::parse($to->departure_time)->format('g:i A') : 'N/A',
        ];
    })->values();
@endphp
const allTravelOrdersData = @json($travelOrdersData);

// Prefill data when main travel order is selected
function prefillMainTravelOrderData() {
    const mainSelect = document.getElementById('main_travel_order_id');
    const selectedOption = mainSelect.options[mainSelect.selectedIndex];
    
    // Update hidden input for main travel order
    document.getElementById('hidden_main_travel_order').value = selectedOption.value || '';
    
    if (!selectedOption.value) {
        // Clear hidden date fields
        document.getElementById('date_from').value = '';
        document.getElementById('date_to').value = '';
        document.getElementById('destination').value = '';
        document.getElementById('departure_time').value = '';
        resetDriverAndVehicleOptions();
        updateTotalCount();
        // Refresh dropdown to show all options when main TO is cleared
        refreshAdditionalTravelOrderDropdown();
        // Clear passengers list
        updatePassengersList();
        return;
    }
    
    // Get dates from the selected option
    const dateFrom = selectedOption.getAttribute('data-date-from');
    const dateTo = selectedOption.getAttribute('data-date-to');
    const destination = selectedOption.getAttribute('data-destination');
    const departureTime = selectedOption.getAttribute('data-departure-time');
    
    // CLEAR additional travel orders when main travel order changes
    additionalTravelOrders = [];
    renderAdditionalTravelOrdersList();
    
    if (dateFrom && dateTo) {
        document.getElementById('date_from').value = dateFrom;
        document.getElementById('date_to').value = dateTo;
        
        if (destination) {
            document.getElementById('destination').value = destination;
        }
        if (departureTime) {
            document.getElementById('departure_time').value = departureTime;
        }
        
        // Update available drivers and vehicles based on date range
        updateAvailableDriversForDateRange(dateFrom, dateTo);
        updateAvailableVehiclesForDateRange(dateFrom, dateTo);
    } else {
        resetDriverAndVehicleOptions();
    }
    
    updateTotalCount();
    
    // Refresh dropdown to hide the selected main travel order
    refreshAdditionalTravelOrderDropdown();
    
    // Update passengers list with all selected travel orders (will be just the main one now)
    updatePassengersList();
}

// Add additional travel order to the list automatically when selected
function addAdditionalTravelOrder() {
    const select = document.getElementById('additional_travel_order_select');
    const selectedOption = select.options[select.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        return;
    }
    
    const travelOrderId = selectedOption.value;
    const displayText = selectedOption.getAttribute('data-display');
    
    // Check if already added
    if (additionalTravelOrders.includes(travelOrderId)) {
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
    additionalTravelOrders.push(travelOrderId);
    
    // Update display
    renderAdditionalTravelOrdersList();
    updateTotalCount();
    
    // Refresh the dropdown to hide selected travel orders
    refreshAdditionalTravelOrderDropdown();
    
    // Reset dropdown to placeholder
    select.value = '';
}

// Refresh additional travel order dropdown to filter out selected travel orders
function refreshAdditionalTravelOrderDropdown() {
   const mainSelect = document.getElementById('main_travel_order_id');
   const additionalSelect = document.getElementById('additional_travel_order_select');
    
    // Get the currently selected main travel order ID
   const mainTravelOrderId = mainSelect.value;
    
    // Get the date range from the selected main travel order
   const selectedOption = mainSelect.options[mainSelect.selectedIndex];
   const mainDateFrom = selectedOption.getAttribute('data-date-from');
   const mainDateTo = selectedOption.getAttribute('data-date-to');
    
    // Clear current options except placeholder
    additionalSelect.innerHTML = '<option value="" disabled selected>-- Select to Add --</option>';
    
    // Build options from our data array (not from DOM)
    allTravelOrdersData.forEach(toData => {
       const optionId = toData.id.toString();
        
        // Skip if this is the main travel order
        if (optionId === mainTravelOrderId) {
            return;
        }
        
        // Skip if already in additional travel orders array
        if (additionalTravelOrders.includes(optionId)) {
            return;
        }
        
        // Filter by date range if main travel order has dates
        if (mainDateFrom && mainDateTo) {
            // Get this travel order's dates from the original data
           const toElement = Array.from(document.querySelectorAll('#main_travel_order_id option'))
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
        
        // Create option with complete data
       const displayText = `${toData.formattedDate} - ${toData.employeeName} (${toData.destination})`;
       const newOption = document.createElement('option');
        newOption.value = optionId;
        newOption.textContent = displayText;
        newOption.setAttribute('data-display', displayText);
        newOption.setAttribute('data-destination', toData.destination);
        additionalSelect.appendChild(newOption);
    });
}

// Remove additional travel order from the list
function removeAdditionalTravelOrder(travelOrderId) {
    additionalTravelOrders = additionalTravelOrders.filter(id => id !== travelOrderId);
    renderAdditionalTravelOrdersList();
    updateTotalCount();
    
    // Refresh the dropdown to show the removed travel order again
    refreshAdditionalTravelOrderDropdown();
}

// Render the additional travel orders list
function renderAdditionalTravelOrdersList() {
    const listDiv = document.getElementById('additional-travel-orders-list');
    
    if (additionalTravelOrders.length === 0) {
        listDiv.innerHTML = '<p class="text-muted mb-0 fst-italic">No additional travel orders selected</p>';
        return;
    }
    
    let html = '<ul class="list-unstyled mb-0">';
    additionalTravelOrders.forEach((id, index) => {
        // Find the travel order data from our stored array
        const toData = allTravelOrdersData.find(to => to.id == id);
        
        // Build complete display text with all details
        const displayText = toData 
            ? `${toData.formattedDate} - ${toData.employeeName} (${toData.destination})`
            : `TO #${id}`;
        
        html += `<li class="mb-1 d-flex justify-content-between align-items-center">
            <span class="small"><strong>${index + 1}.</strong> ${displayText}</span>
            <button type="button" class="btn btn-xs btn-danger ms-2" onclick="removeAdditionalTravelOrder('${id}')" style="font-size: 0.65rem; padding: 2px 6px;">
                <i class="fas fa-times"></i> Remove
            </button>
        </li>`;
    });
    html += '</ul>';
    
    listDiv.innerHTML = html;
}

// Update total count display
function updateTotalCount() {
    const mainSelect = document.getElementById('main_travel_order_id');
    const count = (mainSelect.value ? 1 : 0) + additionalTravelOrders.length;
    document.getElementById('total-travel-orders-count').textContent = count;
    
    // Also update passengers list when count changes
    updatePassengersList();
}

// Update passengers list from all selected travel orders
function updatePassengersList() {
    const allTravelOrderIds = getAllTravelOrderIds();
    const passengersListDiv = document.getElementById('passengers-list');
    const totalCountSpan = document.getElementById('total-passengers-count');
    
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
        
        // Display passengers in simple numbered list
        if (allPassengers.length === 0) {
            passengersListDiv.innerHTML = '<p class="text-muted mb-0 fst-italic">No passengers in selected travel orders</p>';
        } else {
            let html = '<ul class="list-unstyled mb-0">';
            allPassengers.forEach((passenger, index) => {
                const name = passenger.name || passenger.full_name || 'Unknown';
                const position = passenger.position || passenger.official_station || '';
                const office = passenger.office || '';
                
                html += `<li class="mb-1">
                    <span class="fw-medium">${index + 1}. ${name}</span>
                    ${position ? `<span class="text-muted small"> - ${position}</span>` : ''}
                    ${office ? `<span class="text-muted small"> (${office})</span>` : ''}
                </li>`;
            });
            html += '</ul>';
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

// Collect all travel order IDs for form submission
function getAllTravelOrderIds() {
    const mainSelect = document.getElementById('main_travel_order_id');
    const ids = [];
    
    if (mainSelect.value) {
        ids.push(mainSelect.value);
    }
    
    ids.push(...additionalTravelOrders);
    
    return ids;
}

// Helper function to calculate default arrival time (2 hours after departure)
function calculateDefaultArrivalTime(departureTime) {
    if (!departureTime) return '';
    
    const [hours, minutes] = departureTime.split(':');
    let departureHours = parseInt(hours);
    let departureMinutes = parseInt(minutes);
    
    // Add 2 hours to departure time for default arrival
    departureHours += 2;
    
    // Handle overflow
    if (departureHours >= 24) {
        departureHours = departureHours % 24;
    }
    
    // Format as HH:MM
    return `${departureHours.toString().padStart(2, '0')}:${minutes}`;
}

// Update available drivers based on selected date
function updateAvailableDrivers(date) {
    fetch(`/api/available-drivers/${date}`)
        .then(response => response.json())
        .then(drivers => {
            // Sort drivers alphabetically by name
            drivers.sort((a, b) => {
                const nameA = (a.first_name + ' ' + a.last_name).toLowerCase();
                const nameB = (b.first_name + ' ' + b.last_name).toLowerCase();
                return nameA.localeCompare(nameB);
            });
            
            const driverSelect = document.getElementById('driver_id');
            const currentValue = driverSelect.value;
            
            // Clear existing options except the first placeholder
            driverSelect.innerHTML = '<option value="" disabled selected>-- Select Driver --</option>';
            
            drivers.forEach(driver => {
                const option = document.createElement('option');
                option.value = driver.id;
                option.textContent = `${driver.first_name} ${driver.last_name}`;
                option.setAttribute('data-position', driver.position || '');
                option.setAttribute('data-contact', driver.contact_num || '');
                driverSelect.appendChild(option);
            });
            
            // Restore the previous selection if the driver is still available
            if (currentValue && drivers.some(d => d.id == currentValue)) {
                driverSelect.value = currentValue;
            } else {
                driverSelect.value = '';
            }
        })
        .catch(error => console.error('Error fetching available drivers:', error));
}

// Update available vehicles based on selected date
function updateAvailableVehicles(date) {
    fetch(`/api/available-vehicles/${date}`)
        .then(response => response.json())
        .then(vehicles => {
            // Sort vehicles alphabetically by plate number (natural sort)
            vehicles.sort((a, b) => {
                return a.plate_number.toLowerCase().localeCompare(b.plate_number.toLowerCase(), undefined, {
                    numeric: true,
                    sensitivity: 'base'
                });
            });
            
            const vehicleSelect = document.getElementById('vehicle_id');
            const currentValue = vehicleSelect.value;
            
            // Clear existing options except the first placeholder
            vehicleSelect.innerHTML = '<option value="" disabled selected>-- Select Vehicle --</option>';
            
            vehicles.forEach(vehicle => {
                const option = document.createElement('option');
                option.value = vehicle.id;
                option.textContent = `${vehicle.plate_number}`;
                option.setAttribute('data-model', vehicle.model || '');
                option.setAttribute('data-type', vehicle.type || '');
                vehicleSelect.appendChild(option);
            });
            
            // Restore the previous selection if the vehicle is still available
            if (currentValue && vehicles.some(v => v.id == currentValue)) {
                vehicleSelect.value = currentValue;
            } else {
                vehicleSelect.value = '';
            }
        })
        .catch(error => console.error('Error fetching available vehicles:', error));
}

// Update available drivers based on date range from travel order
function updateAvailableDriversForDateRange(dateFrom, dateTo) {
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
            
            // Sort drivers alphabetically by name
            drivers.sort((a, b) => {
                const nameA = (a.first_name + ' ' + a.last_name).toLowerCase();
                const nameB = (b.first_name + ' ' + b.last_name).toLowerCase();
                return nameA.localeCompare(nameB);
            });
            
            const driverSelect = document.getElementById('driver_id');
            const currentValue = driverSelect.value;
            
            // Clear existing options except the first placeholder
            driverSelect.innerHTML = '<option value="" disabled selected>-- Select Driver --</option>';
            
            drivers.forEach(driver => {
                const option = document.createElement('option');
                option.value = driver.id;
                option.textContent = `${driver.first_name} ${driver.last_name}`;
                option.setAttribute('data-position', driver.position || '');
                option.setAttribute('data-contact', driver.contact_num || '');
                driverSelect.appendChild(option);
            });
            
            console.log('Driver dropdown updated with', drivers.length, 'drivers (sorted alphabetically)');
            
            // Restore the previous selection if the driver is still available
            if (currentValue && drivers.some(d => d.id == currentValue)) {
                driverSelect.value = currentValue;
            } else {
                driverSelect.value = '';
            }
        })
        .catch(error => console.error('Error fetching available drivers:', error));
}

// Update available vehicles based on date range from travel order
function updateAvailableVehiclesForDateRange(dateFrom, dateTo) {
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
            
            // Sort vehicles alphabetically by plate number (natural sort)
            vehicles.sort((a, b) => {
                return a.plate_number.toLowerCase().localeCompare(b.plate_number.toLowerCase(), undefined, {
                    numeric: true,
                    sensitivity: 'base'
                });
            });
            
            const vehicleSelect = document.getElementById('vehicle_id');
            const currentValue = vehicleSelect.value;
            
            // Clear existing options except the first placeholder
            vehicleSelect.innerHTML = '<option value="" disabled selected>-- Select Vehicle --</option>';
            
            vehicles.forEach(vehicle => {
                const option = document.createElement('option');
                option.value = vehicle.id;
                option.textContent = `${vehicle.plate_number}`;
                option.setAttribute('data-model', vehicle.model || '');
                option.setAttribute('data-type', vehicle.type || '');
                vehicleSelect.appendChild(option);
            });
            
            console.log('Vehicle dropdown updated with', vehicles.length, 'vehicles (sorted alphabetically)');
            
            // Restore the previous selection if the vehicle is still available
            if (currentValue && vehicles.some(v => v.id == currentValue)) {
                vehicleSelect.value = currentValue;
            } else {
                vehicleSelect.value = '';
            }
        })
        .catch(error => console.error('Error fetching available vehicles:', error));
}

// Reset driver and vehicle options to show all available
function resetDriverAndVehicleOptions() {
    // Reset driver options
    const driverSelect = document.getElementById('driver_id');
    driverSelect.innerHTML = '<option value="" disabled selected>-- Select Driver --</option>';
    
    // We'll need to store the original driver options to restore them
    // For now, we'll just clear the selection
    driverSelect.value = '';
    
    // Reset vehicle options
    const vehicleSelect = document.getElementById('vehicle_id');
    vehicleSelect.innerHTML = '<option value="" disabled selected>-- Select Vehicle --</option>';
    vehicleSelect.value = '';
}

// Add event listeners to date inputs to update available drivers and vehicles
document.addEventListener('DOMContentLoaded', function() {
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    
    // Store original driver and vehicle options for restoration
    const driverSelect = document.getElementById('driver_id');
    const vehicleSelect = document.getElementById('vehicle_id');
    const originalDriverOptions = Array.from(driverSelect.options).map(opt => ({
        value: opt.value,
        text: opt.text,
        position: opt.getAttribute('data-position'),
        contact: opt.getAttribute('data-contact')
    }));
    const originalVehicleOptions = Array.from(vehicleSelect.options).map(opt => ({
        value: opt.value,
        text: opt.text,
        model: opt.getAttribute('data-model'),
        type: opt.getAttribute('data-type'),
        seatingCapacity: opt.getAttribute('data-seating-capacity')
    }));
    
    if (dateFromInput) {
        dateFromInput.addEventListener('change', function() {
            const selectedDate = this.value;
            const dateTo = dateToInput.value || selectedDate;
            
            console.log('Date From changed:', selectedDate, 'Date To:', dateTo);
            if (selectedDate && dateTo) {
                updateAvailableDriversForDateRange(selectedDate, dateTo);
                updateAvailableVehiclesForDateRange(selectedDate, dateTo);
            } else {
                resetDriverAndVehicleOptions();
            }
        });
    }
    
    if (dateToInput) {
        dateToInput.addEventListener('change', function() {
            const selectedDate = this.value;
            const dateFrom = dateFromInput.value || selectedDate;
            
            console.log('Date To changed:', selectedDate, 'Date From:', dateFrom);
            if (dateFrom && selectedDate) {
                updateAvailableDriversForDateRange(dateFrom, selectedDate);
                updateAvailableVehiclesForDateRange(dateFrom, selectedDate);
            } else {
                resetDriverAndVehicleOptions();
            }
        });
    }
    
    // Also listen to travel order changes to trigger date-based filtering
    const mainTravelOrderSelect = document.getElementById('main_travel_order_id');
    if (mainTravelOrderSelect) {
        mainTravelOrderSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const dateFrom = selectedOption.getAttribute('data-date-from');
            const dateTo = selectedOption.getAttribute('data-date-to');
            
            if (dateFrom && dateTo) {
                console.log('Travel Order changed, updating for date range:', dateFrom, 'to', dateTo);
                // Small delay to allow date fields to be updated first
                setTimeout(() => {
                    updateAvailableDriversForDateRange(dateFrom, dateTo);
                    updateAvailableVehiclesForDateRange(dateFrom, dateTo);
                }, 100);
            }
        });
    }
});

// Update driver details when a driver is selected
document.getElementById('driver_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const position = selectedOption.getAttribute('data-position');
    const contact = selectedOption.getAttribute('data-contact');
    
    // First field: Contact Number
    document.getElementById('driver_position').value = contact || '';
    // Second field: Position
    document.getElementById('driver_station').value = position || '';
});

// Update vehicle details when a vehicle is selected
document.getElementById('vehicle_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const model = selectedOption.getAttribute('data-model');
    const type = selectedOption.getAttribute('data-type');
    
    document.getElementById('vehicle_model').value = model || '';
    document.getElementById('vehicle_type').value = type || '';
});

// Expose the function globally for the button to call
window.openCreateItineraryModal = function() {
    var myModal = new bootstrap.Modal(document.getElementById('createItineraryModal'));
    myModal.show();
};

/**
 * Validate total passengers against vehicle seating capacity before submission
 */
async function validatePassengerCapacity() {
  console.log('=== STARTING CAPACITY VALIDATION ===');
    
  const vehicleSelect = document.getElementById('vehicle_id');
    
  if (!vehicleSelect) {
  console.log('Vehicle select element not found, returning true');
 return true;
    }
    
  const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
  const seatingCapacity = parseInt(selectedOption.getAttribute('data-seating-capacity')) || 0;
    
  console.log('Selected vehicle option:', selectedOption);
  console.log('Seating capacity:', seatingCapacity);
    
  if (seatingCapacity === 0) {
  console.log('⚠️ No vehicle selected or capacity is 0, skipping validation');
 return true; // No validation if no vehicle selected
    }
    
   // Get all selected travel order IDs
  const allTravelOrderIds = getAllTravelOrderIds();
    
  console.log('Selected Travel Order IDs:', allTravelOrderIds);
    
  if (allTravelOrderIds.length === 0) {
  console.log('No travel orders selected, returning true');
 return true;
    }
    
   // Fetch passenger counts for all selected travel orders
  console.log('Fetching passenger counts for', allTravelOrderIds.length, 'travel orders...');
    
  const passengerCountPromises = allTravelOrderIds.map(toId => {
  console.log('  Fetching passengers for TO ID:', toId);
 return fetch(`/api/travel-orders/${toId}/passengers`)
           .then(response => {
          console.log('  Response status for TO', toId, ':', response.status);
         return response.json();
           })
           .then(data => {
          console.log('  Passenger data for TO', toId, ':', data);
          const count = data.passengers ? data.passengers.length : 1;
          console.log('  Passenger count for TO', toId, ':', count);
         return count;
           })
           .catch(error => {
          console.error('  ERROR fetching passengers for TO', toId, ':', error);
         return 1;
           });
   });
    
   try {
  const counts = await Promise.all(passengerCountPromises);
  const totalPassengers = counts.reduce((sum, count) => sum + count, 0);
        
  console.log('=== VALIDATION RESULTS ===');
  console.log('Vehicle capacity:', seatingCapacity);
  console.log('Total passengers:', totalPassengers);
  console.log('Individual counts:', counts);
        
  if (totalPassengers > seatingCapacity) {
   const message = `⚠️ Validation Error:\n\nTotal passengers (${totalPassengers}) exceeds vehicle seating capacity (${seatingCapacity}).\n\nPlease select a vehicle with higher capacity or reduce the number of travel orders.`;
     Swal.fire({
      icon: 'warning',
      title: 'Validation Error',
      html: message.replace(/\n/g, '<br>'),
      confirmButtonText: 'OK'
    });
   console.log('❌ VALIDATION FAILED - Preventing submission');
  return false; // Prevent submission
    }
        
  console.log('✓ VALIDATION PASSED');
 return true; // Allow submission
    } catch (error) {
  console.error('❌ ERROR during validation:', error);
    // On error, allow submission to prevent blocking
 return true;
    }
}

// Function to reset form (except Purpose field)
function resetItineraryForm() {
    // Reset Main Travel Order dropdown
    const mainSelect = document.getElementById('main_travel_order_id');
    mainSelect.value = '';
    
    // Reset Additional Travel Orders
    additionalTravelOrders = [];
    renderAdditionalTravelOrdersList();
    
    // Update count
    updateTotalCount();
    
    // Keep Purpose as default
    document.getElementById('purpose').value = 'TO DRIVE UNIVERSITY VEHICLE WHILE ON OFFICIAL TRIP';
    
    // Reset hidden fields
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value = '';
    document.getElementById('destination').value = '';
    document.getElementById('departure_time').value = '';
    document.getElementById('hidden_main_travel_order').value = '';
    
    // Reset Driver dropdown and fields
    document.getElementById('driver_id').value = '';
    document.getElementById('driver_position').value = '';
    document.getElementById('driver_station').value = '';
    
    // Reset Vehicle dropdown and fields
    document.getElementById('vehicle_id').value = '';
    document.getElementById('vehicle_model').value = '';
    document.getElementById('vehicle_type').value = '';
    
    // Reset additional travel order dropdown to show all options
    const additionalSelect = document.getElementById('additional_travel_order_select');
    if (additionalSelect.originalOptions) {
        additionalSelect.innerHTML = '<option value="" disabled selected>-- Select to Add --</option>';
        additionalSelect.originalOptions.forEach(option => {
            additionalSelect.appendChild(option.cloneNode(true));
        });
        delete additionalSelect.originalOptions;
    }
}

// Cancel button - reset form and close modal
document.getElementById('cancelItineraryBtn').addEventListener('click', function() {
    resetItineraryForm();
    const modal = bootstrap.Modal.getInstance(document.getElementById('createItineraryModal'));
    if (modal) {
        modal.hide();
    }
});

// Close modal when clicking outside (backdrop) - DO NOT reset form
document.getElementById('createItineraryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        // Clicked on backdrop - just close without resetting
        const modal = bootstrap.Modal.getInstance(this);
        if (modal) {
            modal.hide();
        }
    }
});

// Handle itinerary form submission with SweetAlert2 - Creates multiple itineraries if multiple travel orders selected
document.getElementById('saveItineraryBtn').addEventListener('click', async function() {
    // FIRST: Validate passenger capacity against vehicle seating capacity
    const isValid = await validatePassengerCapacity();
   if (!isValid) {
       return; // Validation failed, stop here
    }
    
    // Validate Driver selection
    const driverSelect = document.getElementById('driver_id');
    if (!driverSelect || !driverSelect.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Driver Required!',
            text: 'Please select a driver before saving.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#1e6031'
        });
        return;
    }
    
    // Validate Vehicle selection
    const vehicleSelect = document.getElementById('vehicle_id');
    if (!vehicleSelect || !vehicleSelect.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Vehicle Required!',
            text: 'Please select a vehicle before saving.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#1e6031'
        });
        return;
    }
    
    // Get form data
    const formData = new FormData();
    const form = document.getElementById('itineraryForm');
    
    // Get all form elements EXCEPT the travel_order_ids (we'll handle it separately)
    const formElements = form.querySelectorAll('input:not([name="travel_order_ids[]"]), select:not([name="travel_order_ids[]"]), textarea');
    formElements.forEach(element => {
        if (element.name) {
            formData.append(element.name, element.value);
        }
    });
    
    // Manually add CSRF token
    const csrfToken = document.querySelector('input[name="_token"]').value;
    formData.append('_token', csrfToken);
    
    // Collect all travel order IDs (main + additional)
    const allTravelOrderIds = getAllTravelOrderIds();
    
    // Validate that at least one travel order is selected
    if (allTravelOrderIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Travel Order Selected',
            text: 'Please select at least one travel order to create an itinerary.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    // Append each travel order ID as an array
    allTravelOrderIds.forEach(toId => {
        formData.append('travel_order_ids[]', toId);
    });
    
    console.log('Selected travel order IDs:', allTravelOrderIds);
    console.log('Total travel orders being saved:', allTravelOrderIds.length);
    
    // Show loading alert
    Swal.fire({
        title: 'Saving...',
        text: 'Please wait while we save your itineraries.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Submit the form via AJAX
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Error response:', text);
                throw new Error(`Server error: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Show success alert
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Itinerary saved successfully!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Close the modal and reload the page
                const modal = bootstrap.Modal.getInstance(document.getElementById('createItineraryModal'));
                if (modal) {
                    modal.hide();
                }
                // Reload the page to show the new itinerary
                location.reload();
            });
        } else {
            // Show error alert with specific message
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message || 'An error occurred while saving the itinerary.',
                footer: data.errors ? JSON.stringify(data.errors) : ''
            });
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred while saving the itinerary. Please try again.',
            footer: error.message || 'Unknown error'
        });
    });
});
</script>