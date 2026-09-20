@extends('layouts.motorpool-admin')

@section('header')
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <svg class="inline-block h-6 w-6 text-[#1e6031] mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            {{ __('Create Trip Ticket') }}
        </h2>
        <div class="flex space-x-2">
            <a href="{{ route('trip-tickets.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-300 flex items-center shadow-md hover:shadow-lg">
                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Trip Tickets
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('trip-tickets.store') }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Itinerary Selection -->
                            <div class="md:col-span-2 bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Select Itinerary</h3>
                                
                                <div>
                                    <label for="itinerary_id" class="block text-sm font-medium text-gray-700 mb-1">Itinerary *</label>
                                    <select name="itinerary_id" id="itinerary_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e6031] focus:ring focus:ring-[#1e6031] focus:ring-opacity-50" required onchange="updateItineraryDetails()">
                                        <option value="">Select an Itinerary</option>
                                        @foreach($itineraries as $itinerary)
                                            <option value="{{ $itinerary->id }}" 
                                                data-driver="{{ $itinerary->driver?->first_name . ' ' . $itinerary->driver?->last_name }}"
                                                data-vehicle="{{ $itinerary->vehicle?->model ?? 'N/A' }}"
                                                data-plate-number="{{ $itinerary->vehicle?->plate_number ?? '' }}"
                                                data-seating-capacity="{{ $itinerary->vehicle?->seating_capacity ?? 0 }}"
                                                data-destination="{{ $itinerary->destination ?? 'N/A' }}"
                                                data-purpose="{{ $itinerary->purpose ?? 'N/A' }}">
                                                #{{ $itinerary->id }} - {{ $itinerary->driver?->first_name . ' ' . $itinerary->driver?->last_name }} - {{ $itinerary->destination }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('itinerary_id')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Itinerary Details Preview -->
                            <div class="md:col-span-2 bg-blue-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Itinerary Details</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Driver</label>
                                        <div id="driver-preview" class="w-full rounded-lg border-gray-300 bg-gray-100 p-2 min-h-[40px]">Select an itinerary to see details</div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
                                        <div id="vehicle-preview" class="w-full rounded-lg border-gray-300 bg-gray-100 p-2 min-h-[40px]">Select an itinerary to see details</div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Destination</label>
                                        <div id="destination-preview" class="w-full rounded-lg border-gray-300 bg-gray-100 p-2 min-h-[40px]">Select an itinerary to see details</div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Purpose</label>
                                        <div id="purpose-preview" class="w-full rounded-lg border-gray-300 bg-gray-100 p-2 min-h-[40px]">Select an itinerary to see details</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Trip Ticket Number -->
                            <div class="md:col-span-2 bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Trip Ticket Information</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="ticket_number" class="block text-sm font-medium text-gray-700 mb-1">Trip Ticket Number *</label>
                                        <input type="text" name="ticket_number" id="ticket_number" 
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e6031] focus:ring focus:ring-[#1e6031] focus:ring-opacity-50"
                                               placeholder="Enter trip ticket number (e.g., TT-000001)"
                                               required>
                                        @error('ticket_number')
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Travel Orders from Itinerary Section -->
                            <div class="md:col-span-2 bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Travel Orders (from Itinerary)</h3>
                                
                                <div class="mb-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Main Travel Order</label>
                                    <select class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e6031] focus:ring focus:ring-[#1e6031] focus:ring-opacity-50 text-sm" id="main_travel_order_display" disabled>
                                        <option value="">-- Select an itinerary first --</option>
                                    </select>
                                    <small class="text-gray-500 text-xs">This is the main travel order from the selected itinerary</small>
                                </div>
                                
                                <div class="mt-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Additional Travel Orders</label>
                                    <div class="flex gap-2">
                                        <select name="travel_order_id" id="additional_travel_order_select" class="flex-grow rounded-lg border-gray-300 shadow-sm focus:border-[#1e6031] focus:ring focus:ring-[#1e6031] focus:ring-opacity-50 text-sm" disabled onchange="handleAddTravelOrderClick()">
                                            <option value="">-- Select an itinerary first --</option>
                                        </select>
                                        <button type="button" id="add_travel_order_btn" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg transition duration-200" disabled onclick="handleAddTravelOrderClick()">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                    
                                    <!-- Selected Additional Travel Orders List -->
                                    <div id="additional-travel-orders-list" class="mt-2 p-2 border rounded bg-white" style="max-height: 150px; overflow-y: auto;">
                                        <p class="text-gray-500 text-sm fst-italic">No additional travel orders selected</p>
                                    </div>
                                    <small class="text-gray-500 text-xs">Total: <span id="total-travel-orders-count">1</span> travel order(s) from itinerary</small>
                                </div>
                            </div>
                            
                            <!-- Add Passengers Section -->
                            <div class="md:col-span-2 bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Passengers (from Travel Orders)</h3>
                                
                                <div class="mb-3 p-3 bg-blue-50 rounded border">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">All Passengers from Selected Travel Orders</label>
                                    <div id="all-passengers-list" class="p-2 border rounded bg-white" style="max-height: 150px; overflow-y: auto;">
                                        <p class="text-gray-500 text-sm fst-italic">Select an itinerary to see passengers</p>
                                    </div>
                                    <small class="text-gray-500 text-xs mt-1">Total: <span id="total-passengers-count">0</span> passenger(s)</small>
                                </div>
                                
                                <div id="passenger-list" class="mt-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="text-md font-medium text-gray-700">Selected for Trip Ticket:</h4>
                                        <span id="head-of-party-info" class="text-sm text-gray-500">Head of Party: <span id="current-head">None selected</span></span>
                                    </div>
                                    <ul id="passenger-items" class="list-disc list-inside bg-white p-2 rounded border"></ul>
                                    <div id="passenger_hidden_inputs_container"></div>
                                    <input type="hidden" name="travel_order_ids[]" id="travel_order_ids_hidden_inputs">
                                    <input type="hidden" name="head_of_party" id="head_of_party" value="">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <button type="button" onclick="validateAndSubmit()" class="bg-[#1e6031] hover:bg-[#164f2a] text-white px-6 py-3 rounded-lg transition duration-300 shadow-md hover:shadow-lg">
                                Create Trip Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Store all available travel orders globally
        const allTravelOrders = @json($allTravelOrders);
        
        // Store itinerary travel orders
        let itineraryTravelOrders = [];
        let additionalTravelOrdersInTicket = [];
        
        console.log('Total travel orders loaded:', allTravelOrders.length);
        
        // Initialize the travel order dropdown on page load
        document.addEventListener('DOMContentLoaded', function() {
            refreshTravelOrderDropdown();
        });
        
        // Handle Add Travel Order button click - prevent action if disabled
        function handleAddTravelOrderClick() {
            const btn = document.getElementById('add_travel_order_btn');
            const select = document.getElementById('additional_travel_order_select');
            
            console.log('=== handleAddTravelOrderClick called ===');
            console.log('Button disabled:', btn.disabled);
            console.log('Select disabled:', select.disabled);
            
            // Check if button or dropdown is disabled
            if (btn.disabled || select.disabled) {
                console.log('BLOCKED: Controls are disabled, showing warning');
                Swal.fire({
                    icon: 'warning',
                    title: 'No Itinerary Selected',
                    text: 'Please select an itinerary first before adding travel orders.',
                    timer: 2000,
                    confirmButtonColor: '#1e6031'
                });
                return;
            }
            
            console.log('PROCEEDING: Controls are enabled');
            // Call the actual function
            addAdditionalTravelOrderToTicket();
        }
        
        function updateItineraryDetails() {
            const select = document.getElementById('itinerary_id');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption.value) {
                document.getElementById('driver-preview').textContent = selectedOption.getAttribute('data-driver') || 'N/A';
                document.getElementById('vehicle-preview').textContent = selectedOption.getAttribute('data-vehicle') || 'N/A';
                document.getElementById('destination-preview').textContent = selectedOption.getAttribute('data-destination') || 'N/A';
                document.getElementById('purpose-preview').textContent = selectedOption.getAttribute('data-purpose') || 'N/A';
                
                // Clear existing passenger list
                const passengerList = document.getElementById('passenger-items');
                passengerList.innerHTML = '';
                document.getElementById('passenger_hidden_inputs').value = '';
                
                // Reset additional travel orders
                additionalTravelOrdersInTicket = [];
                
                // Enable the additional travel order dropdown and add button
                const additionalSelect = document.getElementById('additional_travel_order_select');
                const addBtn = document.getElementById('add_travel_order_btn');
                additionalSelect.disabled = false;
                addBtn.disabled = false;
                
                // Change placeholder text when enabled
                additionalSelect.innerHTML = '<option value="">-- Select to Add --</option>';
                
                // Load itinerary details including travel orders
                const itineraryId = selectedOption.value;
                if (itineraryId) {
                    // Fetch itinerary details with travel orders
                    fetch(`/api/itineraries/${itineraryId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(itineraryData => {
                            console.log('=== ITINERARY DATA ===');
                            console.log('Itinerary:', itineraryData);
                            console.log('Travel Order IDs:', itineraryData.travel_order_id);
                            
                            // Store itinerary travel orders
                            itineraryTravelOrders = itineraryData.travel_order_id || [];
                            
                            // Update main travel order display
                            updateMainTravelOrderDisplay(itineraryTravelOrders[0]);
                            
                            // Populate additional travel orders dropdown
                            populateAdditionalTravelOrdersDropdown(itineraryTravelOrders);
                            
                            // Load ALL passengers from ALL travel orders in this itinerary
                            return fetch(`/api/itineraries/${itineraryId}/passengers`);
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('=== PASSENGERS DATA ===');
                            console.log('Passengers array:', data.passengers);
                            
                            if (data.passengers && data.passengers.length > 0) {
                                // Add all passengers to the list automatically
                                data.passengers.forEach((passenger, index) => {
                                    addPassengerAutomatically(passenger.name, passenger.id, index === 0, passenger.travel_order_id);
                                });
                                
                                console.log('Added ' + data.passengers.length + ' passengers automatically');
                                
                                // Update all passengers display
                                updateAllPassengersDisplay(data.passengers);
                                
                                // Update travel order dropdown to show only remaining travel orders
                                refreshTravelOrderDropdown();
                            } else {
                                console.warn('No passengers found!');
                                updateAllPassengersDisplay([]);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching itinerary data:', error);
                            // Show user-friendly error message
                            alert('Failed to load itinerary details. Please try refreshing the page.');
                        });
                }
            } else {
                document.getElementById('driver-preview').textContent = 'Select an itinerary to see details';
                document.getElementById('vehicle-preview').textContent = 'Select an itinerary to see details';
                document.getElementById('destination-preview').textContent = 'Select an itinerary to see details';
                document.getElementById('purpose-preview').textContent = 'Select an itinerary to see details';
                
                // Clear passengers when no itinerary is selected
                document.getElementById('passenger-items').innerHTML = '';
                document.getElementById('head_of_party').value = '';
                document.getElementById('current-head').textContent = 'None selected';
                
                // Reset travel order displays and disable dropdown/button
                document.getElementById('main_travel_order_display').innerHTML = '<option value="">-- Select an itinerary first --</option>';
                
                const additionalSelect = document.getElementById('additional_travel_order_select');
                const addBtn = document.getElementById('add_travel_order_btn');
                additionalSelect.innerHTML = '<option value="">-- Select an itinerary first --</option>';
                additionalSelect.disabled = true;
                addBtn.disabled = true;
                
                document.getElementById('additional-travel-orders-list').innerHTML = '<p class="text-gray-500 text-sm fst-italic">No additional travel orders selected</p>';
                document.getElementById('total-travel-orders-count').textContent = '1';
                document.getElementById('all-passengers-list').innerHTML = '<p class="text-gray-500 text-sm fst-italic">Select an itinerary to see passengers</p>';
                document.getElementById('total-passengers-count').textContent = '0';
                
                // Reset travel order dropdown
                refreshTravelOrderDropdown();
            }
        }
        
        // Update main travel order display
        function updateMainTravelOrderDisplay(travelOrderId) {
            const mainSelect = document.getElementById('main_travel_order_display');
            
            if (!travelOrderId) {
                mainSelect.innerHTML = '<option value="">-- No main travel order --</option>';
                return;
            }
            
            // Find the travel order details
            const travelOrder = allTravelOrders.find(to => to.id == travelOrderId);
            
            if (travelOrder) {
                // Format date
                const dateFrom = travelOrder.date_from ? new Date(travelOrder.date_from).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                const departureTime = travelOrder.departure_time ? formatTime(travelOrder.departure_time) : 'N/A';
                const employeeName = travelOrder.employee_name || travelOrder.passenger_name || 'Unknown';
                const destination = travelOrder.destination || 'No destination';
                
                mainSelect.innerHTML = `<option value="${travelOrderId}">${dateFrom} (${departureTime}) - ${employeeName} (${destination})</option>`;
            } else {
                mainSelect.innerHTML = `<option value="${travelOrderId}">TO #${travelOrderId}</option>`;
            }
        }
        
        // Populate additional travel orders dropdown
        function populateAdditionalTravelOrdersDropdown(itineraryTravelOrderIds) {
            const additionalSelect = document.getElementById('additional_travel_order_select');
            
            // Clear current options
            additionalSelect.innerHTML = '<option value="">-- Select to Add --</option>';
            
            // Skip the first one (main travel order)
            const additionalTOs = itineraryTravelOrderIds.slice(1);
            
            additionalTOs.forEach(toId => {
                const travelOrder = allTravelOrders.find(to => to.id == toId);
                
                if (travelOrder) {
                    const dateFrom = travelOrder.date_from ? new Date(travelOrder.date_from).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                    const employeeName = travelOrder.employee_name || travelOrder.passenger_name || 'Unknown';
                    const destination = travelOrder.destination || 'No destination';
                    
                    const option = document.createElement('option');
                    option.value = toId;
                    option.textContent = `${dateFrom} - ${employeeName} (${destination})`;
                    option.setAttribute('data-display', `${dateFrom} - ${employeeName} (${destination})`);
                    additionalSelect.appendChild(option);
                }
            });
        }
        
        // Add additional travel order to ticket
        function addAdditionalTravelOrderToTicket() {
            // Check if itinerary is selected (dropdown should be disabled if no itinerary)
            const itinerarySelect = document.getElementById('itinerary_id');
            if (!itinerarySelect || !itinerarySelect.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Itinerary Selected',
                    text: 'Please select an itinerary first before adding travel orders.',
                    timer: 2000,
                    confirmButtonColor: '#1e6031'
                });
                return;
            }
            
            const select = document.getElementById('additional_travel_order_select');
            const selectedOption = select.options[select.selectedIndex];
            
            if (!selectedOption || !selectedOption.value) {
                return;
            }
            
            const travelOrderId = selectedOption.value;
            
            // Check if already added
            if (additionalTravelOrdersInTicket.includes(travelOrderId)) {
                alert('This travel order has already been added.');
                return;
            }
            
            // Add to array
            additionalTravelOrdersInTicket.push(travelOrderId);
            
            // Update display
            renderAdditionalTravelOrdersList();
            updateTotalTravelOrdersCount();
            
            // Remove from dropdown
            select.remove(select.selectedIndex);
            
            // Reset dropdown
            select.value = '';
            
            // Reload passengers for this travel order
            loadPassengersForTravelOrder(travelOrderId);
        }
        
        // Render additional travel orders list
        function renderAdditionalTravelOrdersList() {
            const listDiv = document.getElementById('additional-travel-orders-list');
            
            if (additionalTravelOrdersInTicket.length === 0) {
                listDiv.innerHTML = '<p class="text-gray-500 text-sm fst-italic">No additional travel orders selected</p>';
                return;
            }
            
            let html = '<ul class="list-unstyled mb-0">';
            additionalTravelOrdersInTicket.forEach(id => {
                const travelOrder = allTravelOrders.find(to => to.id == id);
                const displayText = travelOrder 
                    ? `${new Date(travelOrder.date_from).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} - ${travelOrder.employee_name || travelOrder.passenger_name || 'Unknown'} (${travelOrder.destination || 'No destination'})`
                    : `TO #${id}`;
                
                html += `<li class="d-flex justify-content-between align-items-center mb-1 p-1 bg-light rounded">
                    <span class="small">${displayText}</span>
                    <button type="button" class="btn btn-xs btn-danger" onclick="removeAdditionalTravelOrderFromTicket('${id}')" style="font-size: 0.65rem; padding: 2px 6px;">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </li>`;
            });
            html += '</ul>';
            
            listDiv.innerHTML = html;
        }
        
        // Remove additional travel order from ticket
        function removeAdditionalTravelOrderFromTicket(travelOrderId) {
            additionalTravelOrdersInTicket = additionalTravelOrdersInTicket.filter(id => id !== travelOrderId);
            renderAdditionalTravelOrdersList();
            updateTotalTravelOrdersCount();
            
            // Re-add to dropdown
            const select = document.getElementById('additional_travel_order_select');
            const travelOrder = allTravelOrders.find(to => to.id == travelOrderId);
            
            if (travelOrder) {
                const dateFrom = travelOrder.date_from ? new Date(travelOrder.date_from).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                const employeeName = travelOrder.employee_name || travelOrder.passenger_name || 'Unknown';
                const destination = travelOrder.destination || 'No destination';
                
                const option = document.createElement('option');
                option.value = travelOrderId;
                option.textContent = `${dateFrom} - ${employeeName} (${destination})`;
                option.setAttribute('data-display', `${dateFrom} - ${employeeName} (${destination})`);
                select.appendChild(option);
            }
            
            // Reload all passengers
            reloadAllPassengers();
        }
        
        // Update total travel orders count
        function updateTotalTravelOrdersCount() {
            const count = 1 + additionalTravelOrdersInTicket.length; // 1 main + additional
            document.getElementById('total-travel-orders-count').textContent = count;
        }
        
        // Load passengers for a specific travel order
        function loadPassengersForTravelOrder(travelOrderId) {
            fetch(`/api/travel-orders/${travelOrderId}/passengers`)
                .then(response => response.json())
                .then(data => {
                    if (data.passengers && data.passengers.length > 0) {
                        data.passengers.forEach(passenger => {
                            addPassengerAutomatically(passenger.name, passenger.id, false, passenger.travel_order_id);
                        });
                        reloadAllPassengers();
                    }
                })
                .catch(error => console.error('Error loading passengers:', error));
        }
        
        // Reload all passengers from all travel orders
        function reloadAllPassengers() {
            const itineraryId = document.getElementById('itinerary_id').value;
            
            if (!itineraryId) {
                return;
            }
            
            fetch(`/api/itineraries/${itineraryId}/passengers`)
                .then(response => response.json())
                .then(data => {
                    if (data.passengers) {
                        updateAllPassengersDisplay(data.passengers);
                    }
                })
                .catch(error => console.error('Error reloading passengers:', error));
        }
        
        // Update all passengers display
        function updateAllPassengersDisplay(passengers) {
            const listDiv = document.getElementById('all-passengers-list');
            const countSpan = document.getElementById('total-passengers-count');
            
            if (passengers.length === 0) {
                listDiv.innerHTML = '<p class="text-gray-500 text-sm fst-italic">No passengers in selected travel orders</p>';
                countSpan.textContent = '0';
                return;
            }
            
            let html = '<ul class="list-unstyled mb-0">';
            passengers.forEach((passenger, index) => {
                const name = passenger.name || passenger.full_name || 'Unknown';
                const position = passenger.position || passenger.official_station || '';
                
                html += `<li class="mb-1 p-1 bg-white rounded border">
                    <div>
                        <span class="fw-medium">${index + 1}. ${name}</span>
                        ${position ? `<br><small class="text-muted">${position}</small>` : ''}
                    </div>
                </li>`;
            });
            html += '</ul>';
            
            listDiv.innerHTML = html;
            countSpan.textContent = passengers.length;
        }
        
        // Format time helper
        function formatTime(timeString) {
            if (!timeString) return 'N/A';
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        }
        
        // Refresh travel order dropdown based on current passengers
        function addPassengerFromDropdown() {
            const select = document.getElementById('travel_order_id');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption.value) {
                const passengerName = selectedOption.getAttribute('data-name');
                const passengerId = selectedOption.getAttribute('data-employee-id');
                const travelOrderId = selectedOption.value; // Get the travel order ID
                
                if (passengerName) {
                    // Check if passenger is already added
                    const existingItems = document.querySelectorAll('#passenger-items li');
                    let alreadyAdded = false;
                    for (let item of existingItems) {
                        if (item.textContent.includes(passengerName)) {
                            alreadyAdded = true;
                            break;
                        }
                    }
                    
                    if (!alreadyAdded) {
                        addPassengerAutomatically(passengerName, passengerId, false, travelOrderId);
                        
                        // Remove the option from dropdown after adding
                        select.remove(select.selectedIndex);
                        
                        console.log('Added passenger:', passengerName, 'from TO:', travelOrderId);
                    } else {
                        console.log('Passenger already added:', passengerName);
                        alert('This passenger is already in the list!');
                    }
                }
                
                // Reset dropdown to placeholder
                select.value = '';
            }
        }
        

        

        
        // Add passenger automatically (used for itinerary creator)
        function addPassengerAutomatically(passengerName, passengerId, isHead, travelOrderId = null) {
            // Check if passenger is already added
            const existingItems = document.querySelectorAll('#passenger-items li');
            for (let item of existingItems) {
                if (item.textContent.includes(passengerName)) {
                    if (isHead) {
                        // If this is the creator and they're already added, make them head
                        const headBtn = item.querySelector('button');
                        if (headBtn) {
                            setAsHeadOfParty(passengerName, passengerName, headBtn);
                        }
                    }
                    return;
                }
            }
            
            // Create list item
            const listItem = document.createElement('li');
            listItem.className = 'flex justify-between items-center';
            listItem.setAttribute('data-employee-id', passengerId);
            listItem.setAttribute('data-travel-order-id', travelOrderId || '');
            
            // Create passenger info div
            const passengerInfo = document.createElement('div');
            passengerInfo.textContent = passengerName;
            
            // Create buttons container
            const buttonsContainer = document.createElement('div');
            
            // Create head of party button
            const headOfPartyBtn = document.createElement('button');
            headOfPartyBtn.textContent = ' Set as Head';
            headOfPartyBtn.className = 'text-sm bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded ml-2';
            headOfPartyBtn.onclick = function() {
                setAsHeadOfParty(passengerName, passengerName, this);
            };
            
            // Create delete button
            const deleteBtn = document.createElement('button');
            deleteBtn.textContent = ' ×';
            deleteBtn.className = 'text-red-600 ml-2';
            deleteBtn.onclick = function() {
                // Remove the corresponding hidden input
                const inputs = document.querySelectorAll('input[name="passenger_names[]"]');
                for (let input of inputs) {
                    if (input.value === passengerName) {
                        input.remove();
                        break;
                    }
                }
                
                // Remove the corresponding travel order hidden input
                const toInputs = document.querySelectorAll('input[name="travel_order_ids[]"]');
                for (let input of toInputs) {
                    if (input.value === travelOrderId) {
                        input.remove();
                        break;
                    }
                }
                
                // Remove this list item
                listItem.remove();
                
                // Update head of party if needed
                updateHeadOfParty();
                
                // Refresh travel order dropdown to show this employee's travel orders again
                refreshTravelOrderDropdown();
            };
            
            buttonsContainer.appendChild(headOfPartyBtn);
            buttonsContainer.appendChild(deleteBtn);
            listItem.appendChild(passengerInfo);
            listItem.appendChild(buttonsContainer);
            document.getElementById('passenger-items').appendChild(listItem);
            
            // Add hidden input for passenger name
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'passenger_names[]';
            hiddenInput.value = passengerName;
            hiddenInput.setAttribute('data-employee-id', passengerId);
            document.getElementById('passenger_hidden_inputs_container').appendChild(hiddenInput);
            
            // Add hidden input for travel order ID
            if (travelOrderId) {
                const toHiddenInput = document.createElement('input');
                toHiddenInput.type = 'hidden';
                toHiddenInput.name = 'travel_order_ids[]';
                toHiddenInput.value = travelOrderId;
                toHiddenInput.setAttribute('data-employee-id', passengerId);
                document.getElementById('travel_order_ids_hidden_inputs').appendChild(toHiddenInput);
                
                console.log('✓ Added travel order hidden input:', travelOrderId, 'for passenger:', passengerName);
            } else {
                console.warn('⚠ No travel order ID provided for passenger:', passengerName);
            }
            
            // If this is the first passenger or marked as head, set as head of party
            if (isHead) {
                setAsHeadOfParty(passengerName, passengerName, headOfPartyBtn);
            }
            
            // Refresh travel order dropdown to hide this employee's travel orders
            refreshTravelOrderDropdown();
        }
        
        // Refresh travel order dropdown based on current passengers
        function refreshTravelOrderDropdown() {
            const select = document.getElementById('travel_order_id');
            
            console.log('Refreshing travel order dropdown...');
            
            // Clear existing options except placeholder
            select.innerHTML = '<option value="">-- Select to Add --</option>';
            
            // Show ALL travel orders (no filtering by employee)
            allTravelOrders.forEach(to => {
                const employee = to.employee;
                if (employee) {
                    const option = document.createElement('option');
                    option.value = to.id;
                    option.textContent = `#${to.id} - ${employee.first_name} ${employee.last_name} - ${to.purpose.substring(0, 30)}...`;
                    option.setAttribute('data-name', `${employee.first_name} ${employee.last_name}`);
                    option.setAttribute('data-employee-id', employee.id);
                    select.appendChild(option);
                }
            });
            
            console.log('Refreshed dropdown: ' + (select.options.length - 1) + ' available travel orders');
        }
        

        
        // Update head of party display
            if (isHead) {
                setAsHeadOfParty(passengerName, passengerName, headOfPartyBtn);
            }
            
            // Refresh travel order dropdown to hide this passenger's travel order
            refreshTravelOrderDropdown();
        }
        
        // Set a passenger as head of party
        function setAsHeadOfParty(passengerName, passengerDisplay, element) {
            // Validate parameters to prevent errors
            if (typeof passengerDisplay === 'object' && passengerDisplay !== null) {
                // If passengerDisplay is an object (likely an HTML element), use passengerName instead
                passengerDisplay = passengerName;
            }
            
            // Set the hidden input value
            document.getElementById('head_of_party').value = passengerName;
            
            // Update the display
            document.getElementById('current-head').textContent = passengerDisplay || passengerName;
            
            // Visual feedback to show which passenger is the head of party
            const allPassengerItems = document.querySelectorAll('#passenger-items li');
            allPassengerItems.forEach(item => {
                item.style.backgroundColor = '';
                item.style.borderLeft = '1px solid #e5e7eb'; // gray-200
            });
            
            // Highlight the head of party item
            const headItem = element.closest('li');
            headItem.style.backgroundColor = '#fef3c7'; // yellow-100
            headItem.style.borderLeft = '4px solid #f59e0b'; // yellow-500
        }
        
        // Initialize the details when the page loads if an itinerary is pre-selected
        document.addEventListener('DOMContentLoaded', function() {
            updateItineraryDetails();
        });
        
        /**
         * Validate total passengers against vehicle seating capacity before submission
         */
        async function validatePassengerCapacity() {
            console.log('=== STARTING CAPACITY VALIDATION ===');
            
            const itinerarySelect = document.getElementById('itinerary_id');
            
            if (!itinerarySelect || !itinerarySelect.value) {
                console.log('No itinerary selected, returning true');
                return true;
            }
            
            const selectedOption = itinerarySelect.options[itinerarySelect.selectedIndex];
            const seatingCapacity = parseInt(selectedOption.getAttribute('data-seating-capacity')) || 0;
            
            console.log('Selected itinerary option:', selectedOption);
            console.log('Seating capacity from data attribute:', seatingCapacity);
            
            if (seatingCapacity === 0) {
                console.log('⚠️ No vehicle selected or capacity is 0, skipping validation');
                return true; // No validation if no vehicle selected
            }
            
            // Get all selected travel order IDs from hidden inputs
            const travelOrderInputs = document.querySelectorAll('#travel_order_ids_hidden_inputs input[name="travel_order_ids[]"]');
            const allTravelOrderIds = Array.from(travelOrderInputs).map(input => input.value);
            
            console.log('Travel order inputs found:', travelOrderInputs.length);
            console.log('Selected Travel Order IDs:', allTravelOrderIds);
            
            if (allTravelOrderIds.length === 0) {
                console.log('No travel orders selected, returning true');
                return true;
            }
            
            // Fetch passenger counts for all travel orders
            console.log('Fetching passenger counts for', allTravelOrderIds.length, 'travel orders...');
            
            try {
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
                
                const counts = await Promise.all(passengerCountPromises);
                const totalPassengers = counts.reduce((sum, count) => sum + count, 0);
                
                console.log('=== VALIDATION RESULTS ===');
                console.log('Vehicle capacity:', seatingCapacity);
                console.log('Total passengers:', totalPassengers);
                console.log('Individual counts:', counts);
                console.log('Validation check:', totalPassengers, '>', seatingCapacity, '=', (totalPassengers > seatingCapacity));
                
                if (totalPassengers > seatingCapacity) {
                    console.log('❌ CAPACITY EXCEEDED - Showing alert and blocking');
                    const message = `⚠️ Validation Error:\n\nTotal passengers (${totalPassengers}) exceeds vehicle seating capacity (${seatingCapacity}).\n\nPlease select fewer travel orders or use a vehicle with higher capacity.`;
                    
                    // CRITICAL: Use await and don't return until user clicks OK
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        html: message.replace(/\n/g, '<br>'),
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    });
                    
                    console.log('❌ VALIDATION FAILED - Alert closed, returning false to block submission');
                    return false; // Prevent submission
                }
                
                console.log('✓ VALIDATION PASSED - Total passengers within capacity');
                return true; // Allow submission
            } catch (error) {
                console.error('❌ ERROR during validation:', error);
                // On error, allow submission to prevent blocking
                return true;
            }
        }
        
        // Validate and submit form
        async function validateAndSubmit() {
            console.log('=== VALIDATE AND SUBMIT CALLED ===');
            
            // FIRST: Validate passenger capacity against vehicle seating capacity
            const isValid = await validatePassengerCapacity();
            console.log('Validation result:', isValid);
            
            if (!isValid) {
                console.log('❌ VALIDATION FAILED - Stopping submission');
                return; // Validation failed, stop here
            }
            
            console.log('✓ VALIDATION PASSED - Proceeding to submit...');
            
            // Get form data
            const formData = new FormData();
            const form = document.querySelector('form');
            
            // Get all form elements
            const formElements = form.querySelectorAll('input, select, textarea');
            formElements.forEach(element => {
                if (element.name && element.type !== 'submit') {
                    if (element.type === 'checkbox' || element.type === 'radio') {
                        if (element.checked) {
                            formData.append(element.name, element.value);
                        }
                    } else if (element.tagName === 'SELECT' && element.multiple) {
                        // Handle multiple select
                        Array.from(element.options).forEach(option => {
                            if (option.selected) {
                                formData.append(element.name, option.value);
                            }
                        });
                    } else {
                        formData.append(element.name, element.value);
                    }
                }
            });
            
            // Show loading alert
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while we create your trip ticket.',
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
                Swal.close(); // Close loading alert
                
                if (data.success) {
                    // Show success alert
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Trip ticket created successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Redirect to trip tickets list
                        window.location.href = '{{ route("trip-tickets.index") }}';
                    });
                } else {
                    // Show error alert with specific message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'An error occurred while creating the trip ticket.',
                        footer: data.errors ? JSON.stringify(data.errors) : ''
                    });
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                Swal.close(); // Close loading alert
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while creating the trip ticket. Please try again.',
                    footer: error.message || 'Unknown error'
                });
            });
        }
        
        // Function to refresh travel order dropdown based on selected passengers
        function refreshTravelOrderDropdown() {
            // Get all selected passenger names
            const selectedPassengers = [];
            const passengerItems = document.querySelectorAll('#passenger-items li');
            passengerItems.forEach(item => {
                const passengerName = item.querySelector('div').textContent.split('(')[0].trim();
                selectedPassengers.push(passengerName);
            });
            
            // Get the travel order dropdown
            const travelOrderSelect = document.getElementById('travel_order_id');
            const allOptions = Array.from(travelOrderSelect.children);
            
            // Clear the dropdown
            travelOrderSelect.innerHTML = '<option value="">Select a Travel Order</option>';
            
            // Add back options that don't belong to selected passengers
            allOptions.slice(1).forEach(option => {  // Skip the first option "Select a Travel Order"
                const optionText = option.textContent;
                const parts = optionText.split(' - ');
                if (parts.length >= 2) {
                    const employeeName = parts[1].trim();
                    
                    // Only add this option if the employee is not in selected passengers
                    if (!selectedPassengers.includes(employeeName)) {
                        travelOrderSelect.appendChild(option.cloneNode(true));
                    }
                } else {
                    // If format doesn't match, add it anyway (fallback)
                    travelOrderSelect.appendChild(option.cloneNode(true));
                }
            });
        }
    </script>
@endsection