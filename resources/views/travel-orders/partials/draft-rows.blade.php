@forelse($travelOrders as $index => $travelOrder)
    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
        <td class="px-4 py-3 text-xs text-[#004d00] font-medium">{{ $travelOrders->firstItem() + $loop->index }}</td>
        <td class="px-4 py-3 text-xs text-[#004d00] font-medium max-w-xs break-words">{{ $travelOrder->destination }}</td>
        <td class="px-4 py-3 text-xs text-[#006400]">
            @if($travelOrder->date_from->format('Y-m-d') == $travelOrder->date_to->format('Y-m-d'))
                {{ $travelOrder->date_from->format('M j, Y') }}
            @elseif($travelOrder->date_from->format('Y-m') == $travelOrder->date_to->format('Y-m'))
                {{ $travelOrder->date_from->format('M j') }} - {{ $travelOrder->date_to->format('j, Y') }}
            @elseif($travelOrder->date_from->format('Y') == $travelOrder->date_to->format('Y'))
                {{ $travelOrder->date_from->format('M j') }} - {{ $travelOrder->date_to->format('M j, Y') }}
            @else
                {{ $travelOrder->date_from->format('M j, Y') }} - {{ $travelOrder->date_to->format('M j, Y') }}
            @endif
        </td>
        <td class="px-4 py-3 text-xs text-[#006400]">
            @if($travelOrder->departure_time)
                {{ date('g:i A', strtotime($travelOrder->departure_time)) }}
            @else
                <span class="text-gray-400">N/A</span>
            @endif
        </td>
        <td class="px-4 py-3 text-xs text-gray-600 max-w-xs break-words">
            <div class="text-[#004d00] font-medium">{{ Str::limit($travelOrder->purpose, 50) }}</div>
        </td>
        <td class="px-4 py-3 text-xs text-center">
            <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                Draft
            </span>
        </td>
        <td class="px-4 py-3 text-xs text-center" style="min-width: 280px;">
            <div class="action-buttons flex justify-center" style="display: flex; gap: 4px;">
                {{-- View Button --}}
                <button type="button" 
                        onclick="viewTravelOrderDetails({{ $travelOrder->id }})" 
                        class="btn view-btn border border-blue-600 inline-flex items-center justify-center px-3 py-1.5 rounded text-xs font-medium text-blue-600 hover:bg-blue-50 transition-colors duration-200"
                        title="View Travel Order"
                        style="display: inline-flex; visibility: visible; opacity: 1; cursor: pointer;">
                    <i class="fas fa-eye"></i> View
                </button>
                
                {{-- Edit Button - Yellow/Warning --}}
                <button type="button" 
                        onclick="editDraftTravelOrder({{ $travelOrder->id }})"
                        class="btn border border-yellow-500 inline-flex items-center justify-center px-3 py-1.5 rounded text-xs font-medium text-yellow-600 hover:bg-yellow-50 transition-colors duration-200"
                        title="Edit Draft"
                        style="display: inline-flex; visibility: visible; opacity: 1; cursor: pointer; color: #eab308 !important; border-color: #eab308 !important;">
                    <i class="fas fa-edit"></i> Edit
                </button>
                
                {{-- Submit for Approval Button --}}
                <button type="button" 
                        onclick="window.submitForApproval({{ $travelOrder->id }}, '{{ addslashes($travelOrder->destination) }}')"
                        class="submit-for-approval-btn btn border border-green-600 inline-flex items-center justify-center px-3 py-1.5 rounded text-xs font-medium text-green-600 hover:bg-green-600 hover:text-white transition-colors duration-200"
                        data-id="{{ $travelOrder->id }}"
                        data-title="{{ $travelOrder->destination }}"
                        title="Submit for Approval"
                        style="display: inline-flex; visibility: visible; opacity: 1; cursor: pointer; color: #16a34a !important; border-color: #16a34a !important;">
                    <i class="fas fa-paper-plane"></i> Submit for Approval
                </button>
                
                {{-- Delete Button --}}
                <button type="button" 
                        onclick="window.deleteDraft({{ $travelOrder->id }}, '{{ addslashes($travelOrder->destination) }}')"
                        class="delete-draft-btn btn border border-red-600 inline-flex items-center justify-center px-3 py-1.5 rounded text-xs font-medium text-red-600 hover:bg-red-600 hover:text-white transition-colors duration-200"
                        data-id="{{ $travelOrder->id }}"
                        data-title="{{ $travelOrder->destination }}"
                        title="Delete Draft"
                        style="display: inline-flex; visibility: visible; opacity: 1; cursor: pointer; color: #dc2626 !important; border-color: #dc2626 !important;">
                    <i class="fas fa-trash"></i> Delete
                </button>
                
                {{-- Hidden Forms for CSRF protection --}}
                <form id="submit-form-{{ $travelOrder->id }}" 
                      action="{{ route('travel-orders.submit-for-approval', $travelOrder->id) }}" 
                      method="POST" 
                      style="display: none;">
                    @csrf
                </form>
                
                <form id="delete-form-{{ $travelOrder->id }}" 
                      action="{{ route('travel-orders.delete-draft', $travelOrder->id) }}" 
                      method="POST" 
                      style="display: none;">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-4 py-6 text-center text-xs text-gray-500">
            <div class="flex flex-col items-center justify-center">
                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <div class="mt-1">
                    <h3 class="text-sm font-medium text-gray-900">No draft travel requests</h3>
                    <p class="mt-0.5 text-xs">You don't have any draft travel requests yet.</p>
                </div>
            </div>
        </td>
    </tr>
@endforelse

<script>
// View travel order details modal - CONTEXT AWARE
window.viewTravelOrderDetails = function(id) {
    // Determine the correct API endpoint based on current route/user role
    let apiUrl = `/api/travel-orders/${id}`; // Default for regular employees
    
    // Check current route to determine context
    const currentRoute = window.location.pathname;
    
    if (currentRoute.includes('/head/travel-orders') || currentRoute.includes('head.travel-orders')) {
        apiUrl = `/api/head/travel-orders/${id}`;
    } else if (currentRoute.includes('/divisionhead/travel-orders') || currentRoute.includes('divisionhead.travel-orders')) {
        apiUrl = `/api/divisionhead/travel-orders/${id}`;
    } else if (currentRoute.includes('/vp/travel-orders') || currentRoute.includes('vp.travel-orders')) {
        apiUrl = `/api/vp/travel-orders/${id}`;
    } else if (currentRoute.includes('/president/travel-orders') || currentRoute.includes('president.travel-orders')) {
        apiUrl = `/api/president/travel-orders/${id}`;
    }
    
    // Fetch travel order details via AJAX
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showTravelOrderModal(data.data);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error loading travel order details'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error loading travel order details'
            });
        });
}

window.showTravelOrderModal = function(travelOrder) {
    // Parse and format dates
    const dateFrom = new Date(travelOrder.date_from);
    const dateTo = new Date(travelOrder.date_to);
    
    let departureDateDisplay;
    const fromMonth = dateFrom.toLocaleString('en-US', { month: 'long' });
    const fromDay = dateFrom.getDate();
    const fromYear = dateFrom.getFullYear();
    
    const toMonth = dateTo.toLocaleString('en-US', { month: 'long' });
    const toDay = dateTo.getDate();
    const toYear = dateTo.getFullYear();
    
    if (dateFrom.getTime() === dateTo.getTime()) {
        // Same date: March 12, 2026
        departureDateDisplay = `${fromMonth} ${fromDay}, ${fromYear}`;
    } else if (fromMonth === toMonth && fromYear === toYear) {
        // Same month: March 12-13, 2026
        departureDateDisplay = `${fromMonth} ${fromDay}-${toDay}, ${fromYear}`;
    } else if (fromYear === toYear) {
        // Same year: March 12 - April 1, 2026
        departureDateDisplay = `${fromMonth} ${fromDay} - ${toMonth} ${toDay}, ${fromYear}`;
    } else {
        // Different years: December 31, 2025 - January 1, 2026
        departureDateDisplay = `${fromMonth} ${fromDay}, ${fromYear} - ${toMonth} ${toDay}, ${toYear}`;
    }
    
    // Format departure time
    let departureTimeDisplay = 'N/A';
    if (travelOrder.departure_time) {
        const timeMatch = travelOrder.departure_time.match(/(\d{1,2}):(\d{2})(?::\d{2})?/);
        if (timeMatch) {
            let hours = parseInt(timeMatch[1]);
            const minutes = timeMatch[2];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            departureTimeDisplay = `${hours}:${minutes} ${ampm}`;
        } else {
            departureTimeDisplay = travelOrder.departure_time;
        }
    }
    
    // Create modal HTML with TABLE LAYOUT - EXACT COPY FROM MOTORPOOL ADMIN INDEX.PHP
    const modalHtml = `
        <div id="travelOrderModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 transition-all duration-200">
            <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0" id="travelOrderModalContent">
                <!-- Modal Header - DSU Themed -->
                <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #1e6031 0%, #2d7a3e 50%, #1e6031 100%);">
                    <div class="flex items-center gap-3">
                        <!-- Icon Circle -->
                        <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                            <!-- Document Icon -->
                            <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">Travel Order Details</h3>
                    </div>
                </div>
                
                <!-- Modal Content -->
                <div class="px-6 py-4">
                    <div class="text-sm text-gray-800">
                        <table class="w-full">
                            <tbody>
                                <!-- Employee Information Section -->
                                <tr>
                                    <td colspan="2" class="font-bold text-gray-900 py-1.5 border-b">Employee Information</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Employee Name:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${travelOrder.employee?.full_name || ''}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Email:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${travelOrder.employee?.user?.email || 'N/A'}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Position:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${travelOrder.position?.position_name || 'N/A'}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Contact Number:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${travelOrder.employee?.contact_num || '-'}
                                    </td>
                                </tr>
                                
                                <!-- Travel Details Section -->
                                <tr>
                                    <td colspan="2" class="font-bold text-gray-900 py-1.5 border-b mt-2">Travel Details</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Date:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${departureDateDisplay}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Time:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${departureTimeDisplay}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Destination:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${travelOrder.destination}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Purpose:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${travelOrder.purpose}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Status:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${travelOrder.status === 'approved' ? 'bg-green-100 text-green-800' : travelOrder.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}">
                                            ${travelOrder.status.charAt(0).toUpperCase() + travelOrder.status.slice(1)}
                                        </span>
                                    </td>
                                </tr>
                                
                                ${travelOrder.status === 'cancelled' || travelOrder.rejection_reason ? `
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Cancellation Reason:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        <span class="text-sm text-gray-700">${travelOrder.rejection_reason || 'No reason provided'}</span>
                                    </td>
                                </tr>
                                ` : ''}
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2">
                    ${travelOrder.status !== 'draft' ? `
                    <button onclick="window.viewTravelOrderExcel(${travelOrder.id}); closeModal();" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-file-excel mr-1"></i> View Excel
                    </button>
                    ` : ''}
                    <button onclick="closeModal()" class="px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page and show it
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Trigger animation - EXACT COPY FROM MOTORPOOL ADMIN
    setTimeout(() => {
        const modal = document.getElementById('travelOrderModal');
        const modalContent = document.getElementById('travelOrderModalContent');
        
        // Show modal backdrop immediately
        modal.classList.remove('hidden');
        
        // Trigger reflow
        void modal.offsetWidth;
        
        // Animate in
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Close modal when clicking outside (on backdrop)
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function handleEscape(e) {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', handleEscape);
            }
        });
    }, 10);
}

// Helper function to close modal
function closeModal() {
    const modal = document.getElementById('travelOrderModal');
    const modalContent = document.getElementById('travelOrderModalContent');
    
    if (modal && modalContent) {
        // Animate out
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        // Remove modal after animation completes
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = '';
        }, 200);
    }
}

// Submit for approval function - defined globally
window.submitForApproval = function(travelOrderId, travelOrderTitle) {
    console.log('Submit clicked:', travelOrderId, travelOrderTitle);
    
    Swal.fire({
        title: 'Submit for Approval?',
        html: 'Are you sure you want to submit this travel order?<br>This will send it to your selected approver and is ready for approval',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e6031',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Submit for Approval',
        cancelButtonText: 'Cancel',
        customClass: {
            title: 'swal2-title-sm',
            htmlContainer: 'swal2-html-container-sm',
            icon: 'swal2-icon-sm'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading SweetAlert for 2 seconds
            Swal.fire({
                title: 'Submitting...',
                html: 'Please wait while we submit your travel order',
                timer: 2000,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            }).then(() => {
                // After 2 seconds, submit the form
                const form = document.getElementById(`submit-form-${travelOrderId}`);
                if (form) {
                    form.submit();
                } else {
                    console.error('Form not found for ID:', travelOrderId);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Unable to submit. Please try again.'
                    });
                }
            });
        }
    });
};

// Delete draft function - defined globally
window.deleteDraft = function(travelOrderId, travelOrderTitle) {
    console.log('Delete clicked:', travelOrderId, travelOrderTitle);
    
    Swal.fire({
        title: 'Delete Draft?',
        html: 'Are you sure you want to delete this Travel Order?<br>This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        customClass: {
            title: 'swal2-title-sm',
            htmlContainer: 'swal2-html-container-sm',
            icon: 'swal2-icon-sm'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading SweetAlert for 2 seconds
            Swal.fire({
                title: 'Deleting...',
                html: 'Please wait while we delete your draft',
                timer: 2000,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            }).then(() => {
                // After 2 seconds, submit the delete form
                const form = document.getElementById(`delete-form-${travelOrderId}`);
                if (form) {
                    form.submit();
                } else {
                    console.error('Form not found for ID:', travelOrderId);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Unable to delete. Please try again.'
                    });
                }
            });
        }
    });
};

// Edit draft travel order - opens the dedicated edit modal with pre-filled data
window.editDraftTravelOrder = function(id) {
    console.log('📝 Editing draft ID:', id);
    
    // Reset cancel flag when opening edit modal
    if (typeof isEditCancelled !== 'undefined') {
        isEditCancelled = false;
    }
    
    // Fetch the draft travel order details from controller
    fetch(`/api/travel-orders/${id}`)
        .then(response => {
            console.log('📡 API Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 Received data:', data);
            console.log('📦 Data success:', data.success);
            
            if (data.success && data.data) {
                // Get the travel order data
                const travelOrder = data.data;
                console.log('✈️ Travel Order:', travelOrder);
                console.log('📍 Destination from DB:', travelOrder.destination);
                console.log('📍 Purpose from DB:', travelOrder.purpose);
                
                // Store original database values
                if (typeof originalDraftData !== 'undefined') {
                    originalDraftData = { ...travelOrder };
                    console.log('💾 Stored original data:', originalDraftData);
                }
                
                // Set the form action and method FIRST
                const form = document.getElementById('createTravelRequestForm');
                form.action = `/travel-orders/${travelOrder.id}`;
                console.log('🔗 Form action set to:', form.action);
                
                // Add method field for PUT/PATCH request
                let methodField = document.getElementById('edit_method_field');
                if (!methodField) {
                    methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.id = 'edit_method_field';
                    form.appendChild(methodField);
                }
                methodField.value = 'PUT';
                
                // Update modal title to "Edit Travel Request"
                const modalTitle = document.getElementById('createTravelRequestModalLabel');
                if (modalTitle) {
                    modalTitle.textContent = 'Edit Travel Request';
                }
                
                // Populate fields IMMEDIATELY before showing modal
                const destinationField = document.querySelector('[name="destination"]');
                const dateFromField = document.querySelector('[name="date_from"]');
                const dateToField = document.querySelector('[name="date_to"]');
                const departureTimeField = document.querySelector('[name="departure_time"]');
                const purposeField = document.querySelector('[name="purpose"]');
                const positionField = document.querySelector('[name="position_id"]');
                
                console.log('🎯 Setting field values...');
                console.log('📦 Data to populate:', {
                    destination: travelOrder.destination,
                    date_from: travelOrder.date_from,
                    date_to: travelOrder.date_to,
                    departure_time: travelOrder.departure_time,
                    purpose: travelOrder.purpose,
                    position_id: travelOrder.position_id
                });
                
                // Fill in the values
                if (destinationField) {
                    destinationField.value = travelOrder.destination || '';
                    console.log('✅ Destination field value:', destinationField.value);
                } else {
                    console.error('❌ Destination field NOT FOUND!');
                }
                if (dateFromField) {
                    // Convert ISO date to YYYY-MM-DD format for date input
                    const dateFromValue = travelOrder.date_from ? travelOrder.date_from.split('T')[0] : '';
                    dateFromField.value = dateFromValue;
                    console.log('✅ Date From field value:', dateFromValue, '(from ISO:', travelOrder.date_from + ')');
                } else {
                    console.error('❌ Date From field NOT FOUND!');
                }
                if (dateToField) {
                    // Convert ISO date to YYYY-MM-DD format for date input
                    const dateToValue = travelOrder.date_to ? travelOrder.date_to.split('T')[0] : '';
                    dateToField.value = dateToValue;
                    console.log('✅ Date To field value:', dateToValue, '(from ISO:', travelOrder.date_to + ')');
                } else {
                    console.error('❌ Date To field NOT FOUND!');
                }
                if (departureTimeField) {
                    departureTimeField.value = travelOrder.departure_time || '';
                    console.log('✅ Departure Time field value:', departureTimeField.value);
                } else {
                    console.error('❌ Departure Time field NOT FOUND!');
                }
                if (purposeField) {
                    purposeField.value = travelOrder.purpose || '';
                    console.log('✅ Purpose field value:', purposeField.value);
                } else {
                    console.error('❌ Purpose field NOT FOUND!');
                }
                if (positionField && travelOrder.position_id) {
                    positionField.value = travelOrder.position_id || '';
                    console.log('✅ Position field value:', positionField.value);
                    // Trigger change event to populate approvers
                    const event = new Event('change');
                    positionField.dispatchEvent(event);
                }
                
                // NOW open the modal AFTER fields are populated
                const modalElement = document.getElementById('createTravelRequestModal');
                console.log('🔍 Modal element found:', !!modalElement);
                
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                    modal.show();
                    console.log('✅ Modal opened!');
                    
                    // FORCE UPDATE field values after modal is visible (in case they didn't stick)
                    setTimeout(() => {
                        console.log('🔄 Force-updating field values...');
                        if (destinationField) destinationField.value = travelOrder.destination || '';
                        if (dateFromField) dateFromField.value = travelOrder.date_from || '';
                        if (dateToField) dateToField.value = travelOrder.date_to || '';
                        if (departureTimeField) departureTimeField.value = travelOrder.departure_time || '';
                        if (purposeField) purposeField.value = travelOrder.purpose || '';
                        if (positionField && travelOrder.position_id) positionField.value = travelOrder.position_id || '';
                        
                        console.log('🔍 Final verification:');
                        console.log('  Destination:', document.querySelector('[name="destination"]')?.value);
                        console.log('  Date From:', document.querySelector('[name="date_from"]')?.value);
                        console.log('  Purpose:', document.querySelector('[name="purpose"]')?.value);
                    }, 500);
                } else {
                    console.error('❌ Modal element not found!');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Modal not found on page.'
                    });
                }
            } else {
                console.error('❌ API returned success=false:', data);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to load draft details.'
                });
            }
        })
        .catch(error => {
            console.error('💥 Error fetching draft:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while loading the draft.'
            });
        });
}
</script>
