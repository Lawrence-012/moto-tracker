@extends('layouts.employee')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-800">Trip Ticket Approvals</h2>
            <p class="mt-1 text-gray-600">Manage and approve trip tickets requiring VP approval</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <a href="{{ route('vp.trip-tickets.approvals.index', ['tab' => 'pending']) }}"
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $tab == 'pending' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Pending
                    @if($tab == 'pending')
                        <span class="ml-2 bg-[#1e6031] text-white text-xs font-bold px-2 py-1 rounded-full">
                            {{ $tripTickets->total() }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('vp.trip-tickets.approvals.index', ['tab' => 'approved']) }}"
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $tab == 'approved' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Approved
                    @if($tab == 'approved')
                        <span class="ml-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                            {{ $tripTickets->total() }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('vp.trip-tickets.approvals.index', ['tab' => 'cancelled']) }}"
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $tab == 'cancelled' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Cancelled
                    @if($tab == 'cancelled')
                        <span class="ml-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                            {{ $tripTickets->total() }}
                        </span>
                    @endif
                </a>
            </nav>
        </div>

        <!-- Search Form -->
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <form method="GET" action="{{ route('vp.trip-tickets.approvals.index', ['tab' => $tab]) }}" class="flex items-center">
                <div class="flex-1 max-w-lg">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search trip tickets..." 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e6031] focus:ring focus:ring-[#1e6031] focus:ring-opacity-50">
                </div>
                <button type="submit" class="ml-2 bg-[#1e6031] hover:bg-[#164f2a] text-white px-4 py-2 rounded-lg transition duration-300">
                    Search
                </button>
                @if(request('search'))
                    <a href="{{ route('vp.trip-tickets.approvals.index', ['tab' => $tab]) }}" 
                       class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-300">
                        Clear
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Trip Tickets Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tripTickets as $tripTicket)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{{ $tripTicket->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $tripTicket->itinerary->travelOrder->employee->first_name ?? 'N/A' }} {{ $tripTicket->itinerary->travelOrder->employee->last_name ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tripTicket->itinerary->destination ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $tripTicket->itinerary->purpose ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($tripTicket->status == 'Pending') bg-yellow-100 text-yellow-800
                                    @elseif($tripTicket->status == 'Approved') bg-green-100 text-green-800
                                    @elseif($tripTicket->status == 'Completed') bg-blue-100 text-blue-800
                                    @elseif($tripTicket->status == 'Cancelled') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $tripTicket->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if($tab == 'pending')
                                    <div class="flex justify-end space-x-2">
                                        <button type="button" 
                                                class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                                onclick="loadAndShowTripTicket({{ $tripTicket->id }})">
                                            View
                                        </button>
                                        <button type="button" 
                                                onclick="approveTripTicket({{ $tripTicket->id }})"
                                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            Approve
                                        </button>
                                        <button type="button" 
                                                onclick="rejectTripTicket({{ $tripTicket->id }})"
                                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            Reject
                                        </button>
                                    </div>
                                    <form id="approve-form-{{ $tripTicket->id }}" action="{{ route('vp.trip-tickets.approvals.approve', $tripTicket->id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <form id="reject-form-{{ $tripTicket->id }}" action="{{ route('vp.trip-tickets.approvals.reject', $tripTicket->id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                @else
                                    <div class="flex justify-end">
                                        <button type="button" 
                                                class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                                onclick="loadAndShowTripTicket({{ $tripTicket->id }})">
                                            View
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                No trip tickets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($tripTickets->hasPages())
            <div class="px-6 py-3 bg-white border-t border-gray-200">
                {{ $tripTickets->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Trip Ticket Details Modal -->
<div id="tripTicketModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden items-center justify-center z-50 overflow-y-auto" style="display: none;">
  <div class="relative inline-block w-full max-w-2xl p-4 my-8 mx-auto text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
    
    <!-- Header -->
    <div class="flex items-center justify-between px-4 py-3 border-b">
      <h3 class="text-lg font-medium text-gray-900">Trip Ticket Details</h3>
      <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeTripTicketModal()">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <!-- Content -->
    <div class="p-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Ticket Number:</label>
          <p class="mt-1 text-sm text-gray-900 font-semibold" id="ticket_number"></p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Status:</label>
          <p class="mt-1 text-sm text-gray-900" id="status"></p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Head of the Party:</label>
          <p class="mt-1 text-sm text-gray-900" id="head_of_party"></p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Vehicle:</label>
          <p class="mt-1 text-sm text-gray-900" id="vehicle"></p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Driver:</label>
          <p class="mt-1 text-sm text-gray-900" id="driver"></p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Destination:</label>
          <p class="mt-1 text-sm text-gray-900" id="destination"></p>
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700">Purpose:</label>
          <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap" id="purpose"></p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Date From:</label>
          <p class="mt-1 text-sm text-gray-900" id="date_from"></p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Date To:</label>
          <p class="mt-1 text-sm text-gray-900" id="date_to"></p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Departure Time:</label>
          <p class="mt-1 text-sm text-gray-900" id="departure_time"></p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Return Time:</label>
          <p class="mt-1 text-sm text-gray-900" id="return_time"></p>
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700">Passengers:</label>
          <ul class="mt-1 text-sm text-gray-900 list-disc list-inside" id="passengers"></ul>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="px-4 py-3 border-t flex justify-end space-x-2">
      <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50" onclick="closeTripTicketModal()">
        Close
      </button>
      <button type="button" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700" onclick="openExcelInModal()">
        <i class="fas fa-file-excel mr-1"></i> View Excel
      </button>
    </div>
  </div>
</div>

<!-- Excel Modal -->
<script>
let currentTripTicketId = null;

// Function to load and show trip ticket details in modal
function loadAndShowTripTicket(ticketId) {
    currentTripTicketId = ticketId;
    
    // Fetch trip ticket details from API
    fetch(`/api/trip-tickets/${ticketId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // The API returns the trip ticket data directly (not wrapped in success property)
            populateTripTicketModal(data);
            document.getElementById('tripTicketModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while loading trip ticket details. Please try again.'
            });
        });
}

// Function to populate modal fields
function populateTripTicketModal(ticket) {
    document.getElementById('ticket_number').textContent = '#' + (ticket.id || 'N/A');
    document.getElementById('status').textContent = ticket.status || 'N/A';
    document.getElementById('head_of_party').textContent = ticket.head_of_the_party || 'N/A';
    
    // Vehicle info
    const vehicle = ticket.itinerary?.vehicle;
    document.getElementById('vehicle').textContent = vehicle ? 
        `${vehicle.plate_number || ''} - ${vehicle.model || ''}`.trim() : 'N/A';
    
    // Driver info
    const driver = ticket.itinerary?.driver;
    document.getElementById('driver').textContent = driver ? driver.full_name : 'N/A';
    
    // Itinerary info
    const itinerary = ticket.itinerary;
    document.getElementById('destination').textContent = itinerary?.destination || 'N/A';
    document.getElementById('purpose').textContent = itinerary?.purpose || 'N/A';
    document.getElementById('date_from').textContent = itinerary?.date_from ? formatDate(itinerary.date_from) : 'N/A';
    document.getElementById('date_to').textContent = itinerary?.date_to ? formatDate(itinerary.date_to) : 'N/A';
    document.getElementById('departure_time').textContent = itinerary?.departure_time ? formatTime(itinerary.departure_time) : 'N/A';
    document.getElementById('return_time').textContent = itinerary?.return_time ? formatTime(itinerary.return_time) : 'N/A';
    
    // Passengers
    const passengersList = document.getElementById('passengers');
    passengersList.innerHTML = '';
    if (ticket.passengers && ticket.passengers.length > 0) {
        ticket.passengers.forEach(passenger => {
            const li = document.createElement('li');
            li.textContent = passenger;
            passengersList.appendChild(li);
        });
    } else {
        passengersList.innerHTML = '<li>No passengers listed</li>';
    }
}

// Function to close the modal
function closeTripTicketModal() {
    document.getElementById('tripTicketModal').style.display = 'none';
    currentTripTicketId = null;
}

// Function to view trip ticket Excel - direct download without modal
function openExcelInModal() {
    if (!currentTripTicketId) {
        alert('No trip ticket selected.');
        return;
    }
    
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
            // User confirmed - open directly in new tab for download (no modal)
            window.open('/trip-tickets/' + currentTripTicketId + '/excel', '_blank');
        }
    });
}

// Function to view trip ticket PDF (legacy - kept for compatibility)
function viewTripTicketPDF() {
    if (!currentTripTicketId) {
        alert('No trip ticket selected.');
        return;
    }
    
    // Open the PDF in modal instead of new tab
    openPdfInModal();
}

// Helper function to format date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, options);
}

// Helper function to format time
function formatTime(timeString) {
    if (!timeString) return '';
    
    const [hours, minutes] = timeString.split(':');
    let hour = parseInt(hours);
    const minute = minutes;
    const ampm = hour >= 12 ? 'PM' : 'AM';
    
    hour = hour % 12;
    hour = hour ? hour : 12;
    
    return `${hour}:${minute} ${ampm}`;
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('tripTicketModal');
    if (event.target === modal) {
        closeTripTicketModal();
    }
});

// Function to approve trip ticket with AJAX and loading indicator
function approveTripTicket(ticketId) {
    Swal.fire({
        title: 'Approve Trip Ticket?',
        text: "You are about to approve this trip ticket. The driver will be notified via SMS.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e6031',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, approve it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state with informative message
            Swal.fire({
                title: 'Approving...',
                html: '<div class="text-sm">Processing approval and sending SMS notification to driver...</div>',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit form using AJAX
            const form = document.getElementById('approve-form-' + ticketId);
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Approved!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Something went wrong. Please try again.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request.'
                });
            });
        }
    });
}

// Function to reject trip ticket with AJAX and loading indicator
function rejectTripTicket(ticketId) {
    Swal.fire({
        title: 'Reject Trip Ticket?',
        text: "You are about to reject this trip ticket. This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, reject it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Prompt for rejection reason
            Swal.fire({
                title: 'Rejection Reason',
                input: 'textarea',
                inputLabel: 'Please provide a reason for rejection',
                inputPlaceholder: 'Enter rejection reason here...',
                inputAttributes: {
                    'maxlength': 500,
                    'rows': 4
                },
                showCancelButton: true,
                confirmButtonText: 'Submit',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                inputValidator: (value) => {
                    if (!value || value.trim() === '') {
                        return 'Rejection reason is required';
                    }
                }
            }).then((swalResult) => {
                if (swalResult.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Rejecting...',
                        html: '<div class="text-sm">Processing rejection...</div>',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    const form = document.getElementById('reject-form-' + ticketId);
                    const formData = new FormData(form);
                    formData.append('remarks', swalResult.value);
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Rejected!',
                                text: 'Trip ticket has been rejected.',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong. Please try again.'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while processing your request.'
                        });
                    });
                }
            });
        }
    });
}
</script>
@endsection