<!-- SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Handle Approve button click with SweetAlert
function handleApprove(approveUrl, employeeName) {
    Swal.fire({
        title: 'Approve Travel Order?',
        html: 'Are you sure you want to approve this travel order?<br><strong>Employee:</strong> ' + employeeName,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e6031',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
        buttonsStyling: true,
        width: '500px',
        customClass: {
            confirmButton: 'btn btn-success px-3 py-2 text-sm',
            cancelButton: 'btn btn-secondary px-3 py-2 text-sm'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading SweetAlert for 2 seconds (matches offices/index.blade.php style)
            Swal.fire({
                title: 'Approving...',
                text: 'Please wait while we process your request',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                timer: 2000,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Make AJAX request to approve
            fetch(approveUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin'
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    throw new Error('Received non-JSON response');
                }
            })
            .then(data => {
                // Hide loading and show success message after 2 seconds
                setTimeout(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Travel order approved successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Redirect to Approved tab after success message closes
                        window.location.href = window.location.pathname + '?tab=approved';
                    });
                }, 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to approve travel order. Please try again.'
                });
            });
        }
    });
}

// Handle Reject button click with custom modal (matching cancel modal design)
function handleReject(rejectUrl, employeeName) {
    const modalHtml = `
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="rejectModal">
            <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full transform transition-all duration-200 scale-95 opacity-0 border-t-4 border-red-600" id="rejectModalContent">
                <!-- Modal Header -->
                <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 50%, #dc3545 100%);">
                    <div class="flex items-center gap-3">
                        <!-- Icon Circle -->
                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-lg">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">Reject Travel Order?</h3>
                    </div>
                </div>
                
                <!-- Modal Content -->
                <div class="px-6 py-4">
                    <div class="text-sm text-gray-800">
                        <p class="text-base font-semibold text-gray-900 mb-3">Please provide a reason for rejecting this travel order:</p>
                        
                        <textarea id="rejectReason" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200" rows="4" placeholder="Please explain why you are rejecting this travel order..." style="min-height: 100px; resize: vertical;"></textarea>
                        <p class="text-xs text-gray-500 mt-2"><i class="fas fa-exclamation-triangle"></i> This reason will be sent to the employee for their reference.</p>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2">
                    <button onclick="confirmRejectAction('${rejectUrl}')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-times-circle mr-1"></i> Yes, Reject
                    </button>
                    <button onclick="closeRejectModal(true)" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-check-circle mr-1"></i> No, Keep it
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('rejectModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Append modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Store reject URL for later use
    window.rejectUrl = rejectUrl;
    
    // Get references
    const modal = document.getElementById('rejectModal');
    const modalContent = document.getElementById('rejectModalContent');
    
    // Add click-outside-to-close functionality
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeRejectModal(false);
        }
    });
    
    // Show modal
    document.body.style.overflow = 'hidden';
    modal.classList.remove('hidden');
    
    // Animate modal in
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// Confirm reject action
function confirmRejectAction(rejectUrl) {
    const reason = document.getElementById('rejectReason').value;
    
    if (!reason || reason.trim() === '') {
        Swal.fire({
            icon: 'warning',
            title: 'Required',
            text: 'Please provide a reason for rejection',
            confirmButtonColor: '#dc3545'
        });
        return;
    }
    
    // Show loading SweetAlert
    Swal.fire({
        title: 'Rejecting...',
        text: 'Please wait while we process your request',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        timer: 2000,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Close modal
    closeRejectModal(false);
    
    // After 2 seconds, make AJAX request
    setTimeout(() => {
        // Create form and submit normally instead of AJAX
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = rejectUrl;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PUT';
        
        const remarksInput = document.createElement('input');
        remarksInput.type = 'hidden';
        remarksInput.name = 'remarks';
        remarksInput.value = reason;
        
        form.appendChild(csrfInput);
        form.appendChild(methodInput);
        form.appendChild(remarksInput);
        document.body.appendChild(form);
        form.submit();
    }, 2000);
}

// Close reject modal
function closeRejectModal(clearData = false) {
    const modal = document.getElementById('rejectModal');
    const modalContent = document.getElementById('rejectModalContent');
    
    if (modal && modalContent) {
        // Clear form data only if explicitly requested
        if (clearData) {
            const textarea = document.getElementById('rejectReason');
            if (textarea) {
                textarea.value = '';
            }
        }
        
        // Animate out
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        // Hide modal after animation completes
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            // Remove modal from DOM
            setTimeout(() => {
                modal.remove();
            }, 200);
        }, 200);
    }
}

// Auto-hide success messages after 2 seconds (no button needed)
document.addEventListener('DOMContentLoaded', function() {
    const successAlerts = document.querySelectorAll('.bg-green-100.border-green-400');
    successAlerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 2000);
    });
});
</script>

@forelse($travelOrders as $index => $travelOrder)
    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
        <td class="px-4 py-3 text-xs text-[#004d00] font-medium">{{ $loop->iteration }}</td>
        <td class="px-4 py-3 text-xs text-[#004d00] font-medium max-w-xs break-words">{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}</td>
        <td class="px-4 py-3 text-xs text-[#006400]">{{ $travelOrder->destination }}</td>
        <td class="px-4 py-3 text-xs text-[#006400]">
            @if($travelOrder->date_from == $travelOrder->date_to)
                {{ \Carbon\Carbon::parse($travelOrder->date_from)->format('M j, Y') }}
            @else
                {{ \Carbon\Carbon::parse($travelOrder->date_from)->format('M j') }} - {{ \Carbon\Carbon::parse($travelOrder->date_to)->format('j, Y') }}
            @endif
        </td>
        <td class="px-4 py-3 text-xs text-gray-600 max-w-xs break-words">
            <div class="text-[#004d00] font-medium">{{ $travelOrder->purpose }}</div>
        </td>
        <td class="px-4 py-3 text-xs text-gray-600">
            @if(isset($tab) && $tab == 'approved')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Approved
                </span>
            @elseif(isset($tab) && $tab == 'cancelled')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    Cancelled
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    Pending
                </span>
            @endif
        </td>
        <td class="px-4 py-3 text-xs text-center">
            <div class="action-buttons flex justify-center space-x-1">
                @php
                    // Determine the correct context based on the current approval page
                    $viewContext = 'regular';
                    if(isset($approvalShowRoute)) {
                        if(str_contains($approvalShowRoute, 'divisionhead')) {
                            $viewContext = 'divisionhead';
                        } elseif(str_contains($approvalShowRoute, 'vp')) {
                            $viewContext = 'vp';
                        } elseif(str_contains($approvalShowRoute, 'president')) {
                            $viewContext = 'president';
                        } elseif(str_contains($approvalShowRoute, 'motorpool')) {
                            $viewContext = 'motorpool';
                        } elseif(str_contains($approvalShowRoute, 'head')) {
                            $viewContext = 'head';
                        }
                    }
                    
                    // Check if we should only show View button (for approved/cancelled items)
                    $showOnlyView = isset($tab) && ($tab == 'approved' || $tab == 'cancelled');
                    
                    // Pass tab information for status display in modal
                    $tabParam = $tab ?? 'pending';
                @endphp
                
                <button type="button" 
                        onclick="viewTravelOrderDetails({{ $travelOrder->id }}, '{{ $viewContext }}', '{{ $tabParam }}')" 
                        class="btn view-btn border inline-flex items-center justify-center"
                        title="View Details">
                    <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View
                </button>
                
                @if(!$showOnlyView)
                    @php
                        // Determine the correct approve route based on the current approval context
                        $approveRoute = 'travel-orders.approve.head';
                        $rejectRoute = 'travel-orders.reject.head';
                        if(isset($approvalShowRoute)) {
                            if(str_contains($approvalShowRoute, 'divisionhead')) {
                                $approveRoute = 'travel-orders.approve.divisionhead';
                                $rejectRoute = 'travel-orders.reject.divisionhead';
                            } elseif(str_contains($approvalShowRoute, 'vp')) {
                                $approveRoute = 'travel-orders.approve.vp';
                                $rejectRoute = 'travel-orders.reject.vp';
                            } elseif(str_contains($approvalShowRoute, 'president')) {
                                $approveRoute = 'travel-orders.approve.president';
                                $rejectRoute = 'travel-orders.reject.president';
                            } elseif(str_contains($approvalShowRoute, 'motorpool')) {
                                $approveRoute = 'travel-orders.approve.motorpool';
                                $rejectRoute = 'travel-orders.reject.motorpool';
                            }
                        }
                    @endphp
                    <button type="button" class="btn approve-btn border inline-flex items-center justify-center" 
                            onclick="handleApprove('{{ route($approveRoute, $travelOrder) }}', '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')"
                            title="Approve">
                        <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Approve
                    </button>
                    
                    <button type="button" class="btn reject-btn border inline-flex items-center justify-center" 
                            onclick="handleReject('{{ route($rejectRoute, $travelOrder) }}', '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')"
                            title="Reject">
                        <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Reject
                    </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="px-4 py-6 text-center text-xs text-gray-500">
            @if(isset($tab))
                @switch($tab)
                    @case('pending')
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <div class="mt-1">
                                <h3 class="text-sm font-medium text-gray-900">No travel orders pending</h3>
                                <p class="mt-0.5 text-xs">You don't have any travel orders pending your approval yet.</p>
                            </div>
                        </div>
                        @break
                    @case('approved')
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <div class="mt-1">
                                <h3 class="text-sm font-medium text-gray-900">No approved travel orders</h3>
                                <p class="mt-0.5 text-xs">You don't have any approved travel orders yet.</p>
                            </div>
                        </div>
                        @break
                    @case('cancelled')
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <div class="mt-1">
                                <h3 class="text-sm font-medium text-gray-900">No cancelled travel orders</h3>
                                <p class="mt-0.5 text-xs">You don't have any cancelled travel orders yet.</p>
                            </div>
                        </div>
                        @break
                    @default
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <div class="mt-1">
                                <h3 class="text-sm font-medium text-gray-900">No travel orders found</h3>
                                <p class="mt-0.5 text-xs">There are no travel orders matching your criteria.</p>
                            </div>
                        </div>
                @endswitch
            @else
                <div class="flex flex-col items-center justify-center">
                    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <div class="mt-1">
                        <h3 class="text-sm font-medium text-gray-900">No travel orders pending</h3>
                        <p class="mt-0.5 text-xs">You don't have any travel orders pending your approval yet.</p>
                    </div>
                </div>
            @endif
        </td>
    </tr>
@endforelse

<script>
// Approve and reject button handlers are now handled by table-search.js
// This prevents duplicate event listeners

// Modal for travel order details
function viewTravelOrderDetails(id, context = 'regular', tab = 'pending') {
    // Fetch travel order details via AJAX
    let url = `/api/travel-orders/${id}`;
    if (context === 'head') {
        url = `/api/head/travel-orders/${id}`;
    } else if (context === 'divisionhead') {
        url = `/api/divisionhead/travel-orders/${id}`;
    } else if (context === 'vp') {
        url = `/api/vp/travel-orders/${id}`;
    } else if (context === 'president') {
        url = `/api/president/travel-orders/${id}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showTravelOrderModal(data.data, context, tab);
            } else {
                alert('Error loading travel order details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading travel order details');
        });
}

function showTravelOrderModal(travelOrder, context, tab = 'pending') {
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
    
    // Create modal HTML with TABLE LAYOUT - EXACT COPY FROM EMPLOYEE VIEW
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
                                        ${travelOrder.employee ? (travelOrder.employee.full_name || (travelOrder.employee.first_name + ' ' + travelOrder.employee.middle_name?.charAt(0) + '. ' + travelOrder.employee.last_name)) : ''}
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
                                        ${tab === 'approved' ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>' : tab === 'cancelled' ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Cancelled</span>' : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>'}
                                    </td>
                                </tr>
                                
                                ${travelOrder.status === 'cancelled' ? `
                                ${travelOrder.rejected_by ? `
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Rejected by:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        <span class="text-sm text-gray-900 font-medium">${travelOrder.rejected_by.full_name || 'Unknown'}</span>
                                    </td>
                                </tr>
                                ` : ''}
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
                    <button onclick="viewTravelOrderExcel(${travelOrder.id}, '${context}'); closeTravelOrderModal();" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-file-excel mr-1"></i> View Excel
                    </button>
                    ` : ''}
                    <button onclick="closeTravelOrderModal()" class="px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
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
                closeTravelOrderModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function handleEscape(e) {
            if (e.key === 'Escape') {
                closeTravelOrderModal();
                document.removeEventListener('keydown', handleEscape);
            }
        });
    }, 10);
}

function closeTravelOrderModal() {
    const modal = document.getElementById('travelOrderModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

// Excel Modal functions
let currentExcelUrl = '';

function openExcelModal(excelUrl) {
    // Directly trigger download without confirmation (confirmation already shown by caller)
    // User confirmed - open directly in new tab for download (no modal)
    window.open(excelUrl, '_blank');
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'travelOrderModal') {
        closeTravelOrderModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTravelOrderModal();
    }
});

function viewTravelOrderExcel(id, context) {
    // Show confirmation dialog before proceeding
    Swal.fire({
        title: 'Download Excel File?',
        text: 'You are about to download the Excel file. Do you want to proceed?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, proceed!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // User confirmed, proceed with fetching travel order details
            // Determine the correct API endpoint based on context
            let apiUrl = `/api/travel-orders/${id}`;
            if (context === 'head') {
                apiUrl = `/api/head/travel-orders/${id}`;
            } else if (context === 'divisionhead') {
                apiUrl = `/api/divisionhead/travel-orders/${id}`;
            } else if (context === 'vp') {
                apiUrl = `/api/vp/travel-orders/${id}`;
            } else if (context === 'president') {
                apiUrl = `/api/president/travel-orders/${id}`;
            }
            
            // Fetch travel order details to determine the correct Excel URL based on the position selected during creation
            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            console.error('API Error Response:', err);
                            throw new Error(err.message || 'Failed to fetch travel order details');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const travelOrder = data.data;
                        const position = travelOrder.position;
                        
                        let excelUrl;
                        if (position) {
                            // Determine Excel URL based on the POSITION SELECTED during travel order creation
                            // Special handling for VP context: VPs should use VP approval Excel route even for President's travel orders
                            if (context === 'vp' && position.is_president) {
                                // VP viewing President's travel order - use VP approval Excel route
                                excelUrl = `/travel-orders/${id}/approval-excel/vp`;
                            } else if (position.is_unit_head && !position.is_division_head && !position.is_vp && !position.is_president) {
                                // Unit head position - use unit head Excel route
                                excelUrl = `/unithead/travel-orders/${id}/excel`;
                            } else if (position.is_division_head && !position.is_vp && !position.is_president) {
                                // Division head position - use division head Excel route
                                excelUrl = `/divisionhead/travel-orders/${id}/excel`;
                            } else if (position.is_vp && !position.is_president) {
                                // VP position - use VP Excel route
                                excelUrl = `/travel-orders/${id}/approval-excel/vp`;
                            } else if (position.is_president) {
                                // President position - use president Excel route
                                excelUrl = `/president/travel-orders/${id}/excel`;
                            } else {
                                // Regular employee position or other - use regular employee Excel route
                                excelUrl = `/travel-orders/${id}/excel`;
                            }
                        } else {
                            // Fallback to regular employee Excel if no position is found
                            excelUrl = `/travel-orders/${id}/excel`;
                        }
                        
                        // Open Excel in modal
                        openExcelModal(excelUrl);
                    } else {
                        console.error('API returned success=false:', data);
                        alert('Error loading travel order details: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error loading travel order details: ' + error.message);
                });
        }
    });
}
</script>