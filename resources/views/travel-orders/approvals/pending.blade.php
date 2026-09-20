<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Travel Orders Pending Approval') }}
            </h2>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Back to Dashboard
            </a>
        </div>
    </x-slot>
    
    <!-- SweetAlert2 Library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Success Message -->
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    <!-- Search Form -->
                    <div class="mb-6">
                        <form method="GET" action="{{ route('employee.travel-orders.approvals.pending') }}" class="flex gap-4">
                            <div class="flex-1">
                                <input type="text" name="search" value="{{ $search ?? '' }}" 
                                    placeholder="Search by employee name, destination, or purpose..."
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1e6031]">
                            </div>
                            <button type="submit" class="px-6 py-2 bg-[#1e6031] text-white rounded-lg hover:bg-[#164f2a] focus:outline-none focus:ring-2 focus:ring-[#1e6031]">
                                Search
                            </button>
                        </form>
                    </div>

                    <!-- Pending Count Badge -->
                    <div class="mb-4">
                        <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-blue-100 text-blue-800">
                            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ $pendingCount }} Travel Order(s) Pending Your Action
                        </span>
                    </div>

                    <!-- Travel Orders Table -->
                    @if($travelOrders->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stage</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($travelOrders as $travelOrder)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $travelOrder->position->position_name ?? 'N/A' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $travelOrder->destination }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900 max-w-xs truncate">{{ $travelOrder->purpose }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $travelOrder->date_from->format('M d, Y') }} - {{ $travelOrder->date_to->format('M d, Y') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($travelOrder->approved_by_employee_id == Auth::user()->employee->id && !$travelOrder->president_approved)
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Awaiting Your Approval
                                                    </span>
                                                @elseif($travelOrder->signed_by_employee_id == Auth::user()->employee->id && $travelOrder->president_approved && (!$travelOrder->vp_approved || $travelOrder->vp_approved == false))
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        Awaiting Your Signature
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                @if($travelOrder->approved_by_employee_id == Auth::user()->employee->id && !$travelOrder->president_approved)
                                                    <!-- Approver Actions -->
                                                    <button onclick="showApproveModal({{ $travelOrder->id }}, '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')" 
                                                        class="text-green-600 hover:text-green-900 mr-3 inline-flex items-center">
                                                        <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Approve
                                                    </button>
                                                    <button onclick="showRejectModal({{ $travelOrder->id }}, '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')" 
                                                        class="text-red-600 hover:text-red-900 inline-flex items-center">
                                                        <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Reject
                                                    </button>
                                                @elseif($travelOrder->signed_by_employee_id == Auth::user()->employee->id && $travelOrder->president_approved && (!$travelOrder->vp_approved || $travelOrder->vp_approved == false))
                                                    <!-- Signer Actions -->
                                                    <button onclick="showSignModal({{ $travelOrder->id }}, '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')" 
                                                        class="text-blue-600 hover:text-blue-900 mr-3 inline-flex items-center">
                                                        <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                        </svg>
                                                        Sign
                                                    </button>
                                                    <button onclick="showRejectSignModal({{ $travelOrder->id }}, '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')" 
                                                        class="text-red-600 hover:text-red-900 inline-flex items-center">
                                                        <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Reject
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $travelOrders->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No travel orders pending approval</h3>
                            <p class="mt-1 text-sm text-gray-500">All travel orders have been processed or there are no pending travel orders.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden forms for SweetAlert actions -->
    <form id="approveForm" method="POST" style="display: none;">
        @csrf
        @method('PUT')
    </form>

    <form id="rejectForm" method="POST" style="display: none;">
        @csrf
        @method('PUT')
    </form>

    <form id="signForm" method="POST" style="display: none;">
        @csrf
        @method('PUT')
    </form>

    <form id="rejectSignForm" method="POST" style="display: none;">
        @csrf
        @method('PUT')
    </form>

    <script>
        // Show Approve Modal (Simple Confirmation)
        function showApproveModal(travelOrderId, employeeName) {
            Swal.fire({
                title: 'Approve Travel Order?',
                html: '<div style="text-align: left;">' +
                      '<div style="background-color: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 15px;">' +
                      '<p style="margin: 0 0 8px 0; color: #6c757d; font-size: 13px;">Employee:</p>' +
                      '<p style="margin: 0; font-weight: 600; color: #212529; font-size: 14px;">' + employeeName + '</p>' +
                      '</div>' +
                      '<p style="margin: 0; color: #6c757d; font-size: 13px;">Are you sure you want to approve this travel order?</p>' +
                      '</div>',
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
                    // Show loading SweetAlert for 2 seconds
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
                    
                    // After 2 seconds, make AJAX request
                    setTimeout(() => {
                        fetch('/employee/travel-orders/approvals/' + travelOrderId + '/approve', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Show success message that auto-dismisses (no loading spinner)
                            Swal.fire({
                                title: 'Success!',
                                text: data.message || 'Travel order approved successfully.',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 2000
                            }).then(() => {
                                // Redirect to Approved tab after success message closes
                                window.location.href = window.location.pathname + '?tab=approved';
                            });
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Failed to approve travel order. Please try again.'
                            });
                        });
                    }, 2000);
                }
            });
        }

        // Show Reject Modal (With Reason Input)
        function showRejectModal(travelOrderId, employeeName) {
            Swal.fire({
                title: 'Reject Travel Order?',
                html: '<p class="text-left mb-2">Please provide a reason for rejecting this travel order:</p>' +
                      '<p class="text-left mb-2"><strong>Employee:</strong> ' + employeeName + '</p>' +
                      '<textarea id="rejectReason" class="swal2-textarea" rows="4" placeholder="Please explain why you are rejecting this travel order..." style="width: 100%; min-height: 100px; resize: vertical;"></textarea>' +
                      '<p class="text-left text-xs text-gray-500 mt-1">This reason will be visible to the employee.</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Reject',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const reason = document.getElementById('rejectReason').value;
                    if (!reason || reason.trim() === '') {
                        Swal.showValidationMessage('Please provide a reason for rejection');
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('rejectForm');
                    form.action = '/employee/travel-orders/approvals/' + travelOrderId + '/reject';
                    
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'remarks';
                    hiddenInput.value = result.value;
                    form.appendChild(hiddenInput);
                    
                    form.submit();
                }
            });
        }

        // Show Sign Modal (Approver 2 Approval)
        function showSignModal(travelOrderId, employeeName) {
            Swal.fire({
                title: 'Sign Travel Order?',
                html: 'Are you sure you want to sign this travel order?<br><strong>Employee:</strong> ' + employeeName + '<br><br>This will finalize the approval process.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1e6031',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Sign',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('signForm').action = '/employee/travel-orders/approvals/' + travelOrderId + '/sign';
                    document.getElementById('signForm').submit();
                }
            });
        }

        // Show Reject Sign Modal (Approver 2 Rejection with Reason)
        function showRejectSignModal(travelOrderId, employeeName) {
            Swal.fire({
                title: 'Reject Signing?',
                html: '<p class="text-left mb-2">Please provide a reason for rejecting this travel order:</p>' +
                      '<p class="text-left mb-2"><strong>Employee:</strong> ' + employeeName + '</p>' +
                      '<textarea id="rejectSignReason" class="swal2-textarea" rows="4" placeholder="Please explain why you are rejecting this travel order..." style="width: 100%; min-height: 100px; resize: vertical;"></textarea>' +
                      '<p class="text-left text-xs text-gray-500 mt-1">This reason will be visible to the employee.</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Reject',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const reason = document.getElementById('rejectSignReason').value;
                    if (!reason || reason.trim() === '') {
                        Swal.showValidationMessage('Please provide a reason for rejection');
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('rejectSignForm');
                    form.action = '/employee/travel-orders/approvals/' + travelOrderId + '/reject-sign';
                    
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'remarks';
                    hiddenInput.value = result.value;
                    form.appendChild(hiddenInput);
                    
                    form.submit();
                }
            });
        }
    </script>
</x-app-layout>
