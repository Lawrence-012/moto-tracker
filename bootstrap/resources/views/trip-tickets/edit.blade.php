@extends('layouts.motorpool-admin')

@section('header')
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <svg class="inline-block h-6 w-6 text-[#1e6031] mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ __('Edit Trip Ticket') }}
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

                    <form id="editTripTicketForm" method="POST" action="{{ route('trip-tickets.update', $tripTicket) }}">
                        @csrf
                        @method('PUT')
                        
                        <!-- Read-only Itinerary Information -->
                        <div class="bg-blue-50 p-4 rounded-lg mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Itinerary Information (Read Only)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Driver</label>
                                    <div class="w-full rounded-lg border-gray-300 bg-gray-100 p-2">{{ $tripTicket->itinerary->driver?->full_name ?? 'N/A' }}</div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
                                    <div class="w-full rounded-lg border-gray-300 bg-gray-100 p-2">{{ $tripTicket->itinerary->vehicle?->model ?? 'N/A' }}</div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Destination</label>
                                    <div class="w-full rounded-lg border-gray-300 bg-gray-100 p-2">{{ $tripTicket->itinerary->destination ?? 'N/A' }}</div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Purpose</label>
                                    <div class="w-full rounded-lg border-gray-300 bg-gray-100 p-2">{{ $tripTicket->itinerary->purpose ?? 'N/A' }}</div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                    <div class="w-full rounded-lg border-gray-300 bg-gray-100 p-2">{{ \Carbon\Carbon::parse($tripTicket->itinerary->date_from)->format('M j, Y') }}</div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                    <div class="w-full rounded-lg border-gray-300 bg-gray-100 p-2">{{ \Carbon\Carbon::parse($tripTicket->itinerary->date_to)->format('M j, Y') }}</div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="itinerary_id" value="{{ $tripTicket->itinerary_id }}">
                        </div>
                        
                        <!-- Travel Orders Section -->
                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Travel Orders</h3>
                            
                            <button type="button" class="btn btn-sm btn-success mb-3" onclick="openAddTravelOrderModal()" style="background-color: #1e6031; border-color: #1e6031;">
                                <i class="fas fa-plus"></i> Add Travel Order
                            </button>
                            
                            <div id="selected-travel-orders-list" class="p-3 border rounded bg-white" style="min-height: 100px;">
                                <p class="text-muted mb-0 fst-italic">No travel orders selected</p>
                            </div>
                            <small class="text-muted mt-2 block">Total: <span id="total-travel-orders-count">0</span> travel order(s)</small>
                            
                            <input type="hidden" name="travel_order_ids[]" id="hidden_travel_order_ids">
                        </div>
                        
                        <!-- Passengers Section -->
                        <div class="bg-white p-4 rounded-lg mb-6 border">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Passengers (Auto-populated from Travel Orders)</h3>
                            
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="text-md font-medium text-gray-700">Selected Passengers:</h4>
                                <span id="head-of-party-info" class="text-sm text-gray-500">Head of Party: <span id="current-head">{{ $tripTicket->head_of_party ?? 'None selected' }}</span></span>
                            </div>
                            
                            <ul id="passenger-items" class="list-none bg-gray-50 p-3 rounded">
                                @if($tripTicket->passengers && is_array($tripTicket->passengers))
                                    @foreach($tripTicket->passengers as $passenger)
                                        <li class="passenger-item flex justify-between items-center py-2 px-3 mb-2 bg-white rounded border" @if($tripTicket->head_of_party == $passenger) style="background-color: #fef3c7; border-left: 4px solid #f59e0b;" @endif>
                                            <div class="font-medium">{{ $passenger }}</div>
                                            <div>
                                                <button type="button" class="text-sm bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded ml-2" onclick="setAsHeadOfParty('{{ $passenger }}', this)">Set as Head</button>
                                                <button type="button" class="text-red-600 ml-2 hover:text-red-800 font-bold" onclick="removePassenger(this)" title="Remove">×</button>
                                            </div>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                            <small class="text-muted mt-2 block">Total: <span id="total-passengers-count">0</span> passenger(s)</small>
                            
                            <input type="hidden" name="passenger_names[]" id="passenger_hidden_inputs">
                            <input type="hidden" name="head_of_party" id="head_of_party" value="{{ $tripTicket->head_of_party ?? '' }}">
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <button type="submit" class="bg-[#1e6031] hover:bg-[#164f2a] text-white px-6 py-3 rounded-lg transition duration-300 shadow-md hover:shadow-lg">
                                Update Trip Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Travel Order Modal -->
    <div class="modal fade" id="addTravelOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content rounded-3 shadow">
                <div class="modal-header d-flex align-items-center" style="background-color:#1e6031; color:white;">
                    <h5 class="modal-title fw-bold mb-0 flex-grow-1">Add Travel Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closeAddTravelOrderModal()"></button>
                </div>
                <div class="modal-body p-3">
                    <!-- Date Range Display -->
                    <div class="alert alert-info mb-3">
                        <strong>Itinerary Dates:</strong> <span id="modal-itinerary-dates">Loading...</span>
                    </div>
                    
                    <!-- Travel Order Selection -->
                    <div class="mb-3">
                        <label for="add_travel_order_select" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.85rem;">Available Travel Orders</label>
                        <select class="form-select form-select-sm rounded" id="add_travel_order_select" style="height: 35px; font-size: 0.75rem;">
                            <option value="" disabled selected>-- Select Travel Order --</option>
                        </select>
                        <small class="text-muted mt-1 block">Only shows travel orders without itineraries that match your date range</small>
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-success w-100" onclick="addSelectedTravelOrder()" style="background-color: #1e6031; border-color: #1e6031; height: 35px;">
                        <i class="fas fa-plus"></i> Add to List
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Store all travel orders data from controller
        const allTravelOrdersData = @json($travelOrdersForEdit);
        
        // Store currently selected travel order IDs
        let selectedTravelOrderIds = [];
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeExistingData();
        });
        
        // Initialize existing travel orders and passengers
        function initializeExistingData() {
            // Load existing travel orders from trip ticket's itinerary
            @if($tripTicket->itinerary && $tripTicket->itinerary->travel_order_id)
                @php
                    $existingTOIds = is_array($tripTicket->itinerary->travel_order_id) 
                        ? array_filter($tripTicket->itinerary->travel_order_id) 
                        : [$tripTicket->itinerary->travel_order_id];
                @endphp
                
                selectedTravelOrderIds = @json($existingTOIds);
                renderSelectedTravelOrdersList();
                updateCounts();
            @endif
            
            // Initialize existing passengers hidden inputs
            const existingItems = document.querySelectorAll('#passenger-items .passenger-item');
            existingItems.forEach(item => {
                const passengerName = item.querySelector('div').textContent.trim();
                
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'passenger_names[]';
                hiddenInput.value = passengerName;
                document.getElementById('passenger-items').parentNode.appendChild(hiddenInput);
            });
            
            updateCounts();
        }
        
        // Open Add Travel Order Modal
        function openAddTravelOrderModal() {
            // Get itinerary dates
            const dateFrom = '{{ \Carbon\Carbon::parse($tripTicket->itinerary->date_from)->format('Y-m-d') }}';
            const dateTo = '{{ \Carbon\Carbon::parse($tripTicket->itinerary->date_to)->format('Y-m-d') }}';
            
            document.getElementById('modal-itinerary-dates').textContent = 
                `${formatDateDisplay(dateFrom)} - ${formatDateDisplay(dateTo)}`;
            
            // Refresh dropdown with filtered travel orders
            refreshAddTravelOrderDropdown(dateFrom, dateTo);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('addTravelOrderModal'));
            modal.show();
        }
        
        // Close Add Travel Order Modal
        function closeAddTravelOrderModal() {
            const modalEl = document.getElementById('addTravelOrderModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        }
        
        // Refresh Add Travel Order Dropdown with filtering
        function refreshAddTravelOrderDropdown(dateFromInput, dateToInput) {
            const select = document.getElementById('add_travel_order_select');
            select.innerHTML = '<option value="" disabled selected>-- Select Travel Order --</option>';
            
            const itineraryStart = new Date(dateFromInput);
            const itineraryEnd = new Date(dateToInput);
            
            allTravelOrdersData.forEach(toData => {
                // Skip if already in selected list
                if (selectedTravelOrderIds.includes(toData.id.toString())) {
                    return;
                }
                
                // Skip if already has itinerary
                if (toData.has_itinerary) {
                    return;
                }
                
                // DATE VALIDATION: Check if travel order dates overlap with itinerary dates
                if (toData.date_from && toData.date_to) {
                    const toStart = new Date(toData.date_from);
                    const toEnd = new Date(toData.date_to);
                    
                    // Check if travel order dates overlap with itinerary dates
                    const isWithinRange = (toStart <= itineraryEnd && toEnd >= itineraryStart);
                    
                    if (!isWithinRange) {
                        return; // Skip - dates don't match
                    }
                } else {
                    return; // Skip if no dates
                }
                
                // Add option
                const displayText = `${toData.formatted_date} - ${toData.employee_name} (${toData.destination})`;
                const newOption = document.createElement('option');
                newOption.value = toData.id.toString();
                newOption.textContent = displayText;
                newOption.setAttribute('data-employee-name', toData.employee_name);
                select.appendChild(newOption);
            });
            
            // Check if there are available options
            if (select.options.length === 1) {
                select.innerHTML = '<option value="" disabled>No available travel orders</option>';
            }
        }
        
        // Add Selected Travel Order to list
        function addSelectedTravelOrder() {
            const select = document.getElementById('add_travel_order_select');
            const travelOrderId = select.value;
            
            if (!travelOrderId || select.options[select.selectedIndex].disabled) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Travel Order Selected',
                    text: 'Please select a travel order first.',
                    timer: 2000
                });
                return;
            }
            
            // Add to selected array
            selectedTravelOrderIds.push(travelOrderId);
            
            // Update display
            renderSelectedTravelOrdersList();
            
            // Add passenger from travel order
            const toData = allTravelOrdersData.find(to => to.id == travelOrderId);
            if (toData) {
                addPassengerAutomatically(toData.employee_name);
            }
            
            updateCounts();
            
            // Refresh dropdown
            refreshAddTravelOrderDropdown(
                '{{ \Carbon\Carbon::parse($tripTicket->itinerary->date_from)->format('Y-m-d') }}',
                '{{ \Carbon\Carbon::parse($tripTicket->itinerary->date_to)->format('Y-m-d') }}'
            );
            
            // Reset select
            select.value = '';
        }
        
        // Remove Travel Order from list
        function removeTravelOrder(travelOrderId) {
            // Confirm removal
            Swal.fire({
                title: 'Remove Travel Order?',
                text: 'This will also remove the passenger from the list.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#1e6031',
                confirmButtonText: 'Yes, Remove',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Remove from selected array
                    selectedTravelOrderIds = selectedTravelOrderIds.filter(id => id !== travelOrderId);
                    
                    // Remove passenger from this travel order
                    const toData = allTravelOrdersData.find(to => to.id == travelOrderId);
                    if (toData) {
                        removePassengersByEmployeeName(toData.employee_name);
                    }
                    
                    // Update display
                    renderSelectedTravelOrdersList();
                    updateCounts();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Removed!',
                        text: 'Travel order removed successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        }
        
        // Render Selected Travel Orders List
        function renderSelectedTravelOrdersList() {
            const listDiv = document.getElementById('selected-travel-orders-list');
            
            if (selectedTravelOrderIds.length === 0) {
                listDiv.innerHTML = '<p class="text-muted mb-0 fst-italic">No travel orders selected</p>';
                return;
            }
            
            let html = '<ul class="list-unstyled mb-0">';
            selectedTravelOrderIds.forEach(id => {
                const toData = allTravelOrdersData.find(to => to.id == id);
                
                if (toData) {
                    const displayText = `${toData.formatted_date} - ${toData.employee_name} (${toData.destination})`;
                    
                    html += `<li class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                        <span class="small">${displayText}</span>
                        <button type="button" class="btn btn-xs btn-danger" onclick="removeTravelOrder('${id}')" style="font-size: 0.65rem; padding: 2px 6px;">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </li>`;
                }
            });
            html += '</ul>';
            
            listDiv.innerHTML = html;
        }
        
        // Add Passenger Automatically
        function addPassengerAutomatically(passengerName) {
            // Check if passenger is already added
            const existingItems = document.querySelectorAll('#passenger-items li');
            for (let item of existingItems) {
                if (item.textContent.trim() === passengerName) {
                    return; // Already exists
                }
            }
            
            // Create list item
            const listItem = document.createElement('li');
            listItem.className = 'flex justify-between items-center py-2 px-3 mb-2 bg-white rounded border passenger-item';
            
            const passengerInfo = document.createElement('div');
            passengerInfo.textContent = passengerName;
            passengerInfo.className = 'font-medium';
            
            const buttonsContainer = document.createElement('div');
            
            const headOfPartyBtn = document.createElement('button');
            headOfPartyBtn.textContent = ' Set as Head';
            headOfPartyBtn.className = 'text-sm bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded ml-2';
            headOfPartyBtn.onclick = function() {
                setAsHeadOfParty(passengerName, this);
            };
            
            const deleteBtn = document.createElement('button');
            deleteBtn.textContent = ' ×';
            deleteBtn.className = 'text-red-600 ml-2 hover:text-red-800 font-bold';
            deleteBtn.title = 'Remove';
            deleteBtn.onclick = function() {
                removePassenger(this);
            };
            
            buttonsContainer.appendChild(headOfPartyBtn);
            buttonsContainer.appendChild(deleteBtn);
            
            listItem.appendChild(passengerInfo);
            listItem.appendChild(buttonsContainer);
            document.getElementById('passenger-items').appendChild(listItem);
            
            // Add hidden input for form submission
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'passenger_names[]';
            hiddenInput.value = passengerName;
            document.getElementById('passenger-items').parentNode.appendChild(hiddenInput);
            
            updateCounts();
        }
        
        // Set as Head of Party
        function setAsHeadOfParty(passengerName, element) {
            document.getElementById('head_of_party').value = passengerName;
            document.getElementById('current-head').textContent = passengerName;
            
            // Remove highlighting from all items
            const allPassengerItems = document.querySelectorAll('#passenger-items li');
            allPassengerItems.forEach(item => {
                item.style.backgroundColor = '';
                item.style.borderLeft = '1px solid #e5e7eb';
            });
            
            // Highlight the head of party item
            const headItem = element.closest('li');
            headItem.style.backgroundColor = '#fef3c7';
            headItem.style.borderLeft = '4px solid #f59e0b';
            
            Swal.fire({
                icon: 'success',
                title: 'Head of Party Updated',
                text: `${passengerName} is now the head of party.`,
                timer: 1500,
                showConfirmButton: false
            });
        }
        
        // Remove Passenger
        function removePassenger(button) {
            const listItem = button.closest('li');
            const passengerName = listItem.querySelector('div').textContent.trim();
            
            // Remove hidden input
            const inputs = document.querySelectorAll('input[name="passenger_names[]"]');
            for (let input of inputs) {
                if (input.value === passengerName) {
                    input.remove();
                    break;
                }
            }
            
            // Clear head of party if this was the head
            if (document.getElementById('head_of_party').value === passengerName) {
                document.getElementById('head_of_party').value = '';
                document.getElementById('current-head').textContent = 'None selected';
            }
            
            // Remove list item
            listItem.remove();
            updateCounts();
        }
        
        // Remove Passengers by Employee Name (when removing travel order)
        function removePassengersByEmployeeName(employeeName) {
            const passengerItems = document.querySelectorAll('#passenger-items li.passenger-item');
            passengerItems.forEach(item => {
                const passengerName = item.querySelector('div').textContent.trim();
                if (passengerName === employeeName) {
                    // Remove hidden input
                    const inputs = document.querySelectorAll('input[name="passenger_names[]"]');
                    inputs.forEach(input => {
                        if (input.value === passengerName) {
                            input.remove();
                        }
                    });
                    
                    // Remove list item
                    item.remove();
                }
            });
            
            updateCounts();
        }
        
        // Update Counts
        function updateCounts() {
            document.getElementById('total-travel-orders-count').textContent = selectedTravelOrderIds.length;
            
            const passengerCount = document.querySelectorAll('#passenger-items li.passenger-item').length;
            document.getElementById('total-passengers-count').textContent = passengerCount;
            
            // Update hidden input for travel orders
            document.getElementById('hidden_travel_order_ids').value = selectedTravelOrderIds.join(',');
        }
        
        // Format Date Display
        function formatDateDisplay(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
        
        // Form submission handler
        document.getElementById('editTripTicketForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate travel orders
            if (selectedTravelOrderIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Travel Orders',
                    text: 'Please add at least one travel order.',
                    timer: 2000
                });
                return;
            }
            
            // Validate head of party
            if (!document.getElementById('head_of_party').value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Head of Party',
                    text: 'Please set a head of party.',
                    timer: 2000
                });
                return;
            }
            
            // Show loading state
            Swal.fire({
                title: 'Updating Trip Ticket...',
                text: 'Please wait while we update the trip ticket.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit form after 1.5 seconds
            setTimeout(() => {
                this.submit();
            }, 1500);
        });
    </script>
@endsection
