
@extends('layouts.employee')

@section('title', 'My Trip Tickets')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-6 pb-4 border-b border-gray-200">
            <!-- ROW 1 -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <!-- Title -->
                <h1 class="text-lg sm:text-xl font-bold text-[#1e6031] mb-0 whitespace-nowrap">
                    My Trip Tickets
                </h1>

                <!-- Search -->
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <!-- Search Bar -->
                    <!-- <div class="flex flex-1 sm:flex-initial">
                        <input type="text" 
                               id="search-input" 
                               class="table-search-input form-input"
                               placeholder="Search..."
                               data-table-id="trip-tickets-table"
                               data-url="{{ route('employee.trip-tickets.index') }}"
                               value="{{ request('search') }}"
                               style="height: 32px; width: 100%; min-width: 150px; max-width: 200px; font-size: 0.85rem; border: 1px solid #1e6031; border-right: none; border-radius: 0.375rem 0 0 0.375rem; padding: 0 12px;">
                        <button type="button" 
                                class="search-submit-btn"
                                style="background-color: #1e6031; color: #ffffff; border: 1px solid #1e6031; height: 32px; width: 40px; border-radius: 0 0.375rem 0.375rem 0; cursor:pointer; display: flex; align-items: center; justify-content: center;">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div> -->
                </div>
            </div>

            <p class="text-gray-600 text-sm hidden sm:block" style="margin-top: 0; line-height: 1.2;">
                View trip tickets where you are listed as a passenger, head of party, or your travel order is included.
            </p>
        </div>

        <!-- Script for search -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const searchButton = document.querySelector('.search-submit-btn');
            
            function performSearch() {
                if(searchInput.value.trim() !== '') {
                    let currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('search', searchInput.value);
                    window.location.href = currentUrl.toString();
                } else {
                    let currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('search');
                    window.location.href = currentUrl.toString();
                }
            }
            
            searchButton.addEventListener('click', function(e) {
                e.preventDefault();
                performSearch();
            });
            
            searchInput.addEventListener('keypress', function(e) {
                if(e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });
        });
        </script>

            <!-- Tabs -->
            <div class="mb-4 border-b border-gray-200">
                <div class="overflow-x-auto">
                    <nav class="flex space-x-4 sm:space-x-6 min-w-max" aria-label="Tabs">
                        <a href="{{ route('employee.trip-tickets.index', ['tab' => 'approved']) }}" 
                           class="{{ $tab == 'approved' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="approved"
                           style="text-decoration: none;">
                            Approved
                        </a>
                        <a href="{{ route('employee.trip-tickets.index', ['tab' => 'on-going']) }}" 
                           class="{{ $tab == 'on-going' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="on-going"
                           style="text-decoration: none;">
                            On-Going
                        </a>
                        <a href="{{ route('employee.trip-tickets.index', ['tab' => 'completed']) }}" 
                           class="{{ $tab == 'completed' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="completed"
                           style="text-decoration: none;">
                            Completed
                        </a>
                        <a href="{{ route('employee.trip-tickets.index', ['tab' => 'cancelled']) }}" 
                           class="{{ $tab == 'cancelled' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="cancelled"
                           style="text-decoration: none;">
                            Cancelled
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Trip Tickets Table -->
            @if(!$tripTickets->isEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead style="background-color: #1e6031; color: white;">
                            <tr>
                                <th class="w-[5%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">No.</th>
                                <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Driver</th>
                                <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Head of the Party</th>
                                <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Vehicle</th>
                                <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Destination</th>
                                <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Date</th>
                                <th class="w-[10%]" style="padding: 8px 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Completion File</th>
                                <th class="w-[10%]" style="padding: 8px 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tripTickets as $index => $tripTicket)
                                <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                                    <td class="px-2 py-2 text-xs text-[#004d00] font-medium">{{ $loop->iteration }}</td>
                                    <td class="px-2 py-2 text-xs text-[#004d00] font-medium max-w-[120px] break-words">{{ $tripTicket->itinerary->driver->full_name ?? 'No driver' }}</td>
                                    <td class="px-2 py-2 text-xs text-[#006400] max-w-[120px] break-words">{{ $tripTicket->head_of_party ?? 'Not set' }}</td>
                                    <td class="px-2 py-2 text-xs text-[#006400] max-w-[120px] break-words">
                                        @if($tripTicket->itinerary && $tripTicket->itinerary->vehicle)
                                            <div class="whitespace-nowrap">{{ $tripTicket->itinerary->vehicle->make }} {{ $tripTicket->itinerary->vehicle->model }}</div>
                                            <div class="text-gray-500 text-xs whitespace-nowrap">Plate: {{ $tripTicket->itinerary->vehicle->plate_number }}</div>
                                        @else
                                            <span class="text-gray-500">No vehicle</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-xs text-[#006400] max-w-[120px] break-words">{{ $tripTicket->itinerary->destination ?? 'No destination' }}</td>
                                    <td class="px-2 py-2 text-xs text-[#006400] whitespace-nowrap">
                                        @if($tripTicket->itinerary && $tripTicket->itinerary->date_from)
                                            @php
                                                $dateFrom = \Carbon\Carbon::parse($tripTicket->itinerary->date_from);
                                                $dateTo = $tripTicket->itinerary->date_to ? \Carbon\Carbon::parse($tripTicket->itinerary->date_to) : null;
                                                
                                                if (!$dateTo || $dateFrom->isSameDay($dateTo)) {
                                                    $formattedDate = $dateFrom->format('M d, Y');
                                                } elseif ($dateFrom->isSameMonth($dateTo) && $dateFrom->year === $dateTo->year) {
                                                    $formattedDate = $dateFrom->format('M j') . '-' . $dateTo->format('d, Y');
                                                } elseif ($dateFrom->year === $dateTo->year) {
                                                    $formattedDate = $dateFrom->format('M j') . ' - ' . $dateTo->format('M j, Y');
                                                } else {
                                                    $formattedDate = $dateFrom->format('M d, Y') . ' - ' . $dateTo->format('M d, Y');
                                                }
                                            @endphp
                                            {{ $formattedDate }}
                                        @else
                                            <span class="text-gray-500">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-center text-xs">
                                        @if($tripTicket->completion_file)
                                            @php
                                                // Construct Google Drive URL from file ID
                                                $driveLink = 'https://drive.google.com/file/d/' . $tripTicket->completion_file . '/view?usp=drivesdk';
                                            @endphp
                                            <div class="action-buttons flex justify-center space-x-1">
                                                <button type="button"
                                                   onclick="viewCompletionFile('{{ $driveLink }}')"
                                                   class="btn view-btn border inline-flex items-center justify-center"
                                                   title="View Completion File">
                                                    <i class="fas fa-file-alt"></i> View File
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs">No file uploaded</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-center text-xs">
                                        <div class="action-buttons flex justify-center space-x-1">
                                            <button type="button" 
                                                    onclick="showTripTicketDetails({{ $tripTicket->id }})"
                                                    class="btn view-btn border inline-flex items-center justify-center"
                                                    title="View Trip Ticket Details">
                                                <svg class="h-3 w-3 mr-1"fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="flex items-center justify-between gap-2 mt-2">
                    <div class="text-xs sm:text-sm text-gray-600 whitespace-nowrap">
                        Showing {{ $tripTickets->firstItem() ?? 0 }} to {{ $tripTickets->lastItem() ?? 0 }} of {{ $tripTickets->total() }} trip tickets
                    </div>
                    <div class="flex items-center space-x-1">
                        @if($tripTickets->lastPage() > 1)
                            {{ $tripTickets->appends(['search' => request('search'), 'tab' => $tab])->links() }}
                        @else
                            <nav>
                                <ul class="pagination flex items-center">
                                    <li class="page-item disabled">
                                        <span class="page-link text-xs px-2 py-1">Prev</span>
                                    </li>
                                    <li class="page-item active">
                                        <span class="page-link text-xs px-2 py-1">1</span>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link text-xs px-2 py-1">Next</span>
                                    </li>
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No trip tickets found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(request('search'))
                            No trip tickets match your search "{{ request('search') }}".
                        @else
                            You don't have any trip tickets in the "{{ $tab }}" status.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.action-buttons .btn {
    font-size: 10px;
    padding: 2px 6px;
    line-height: 1;
    height: 25px;
    min-width: 50px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 3px;
    border-radius: 4px;
}

.action-buttons .btn i {
    font-size: 10px;
    margin-right: 2px;
}

.action-buttons .view-btn {
    color: #0d6efd !important;
    border: 1px solid #0d6efd !important;
    background-color: transparent !important;
}

.action-buttons .view-btn:hover {
    background-color: #0d6efd !important;
    color: #fff !important;
    border-color: #0d6efd !important;
}

.action-buttons .edit-btn {
    color: #ffc107 !important;
    border: 1px solid #ffc107 !important;
    background-color: transparent !important;
}

.action-buttons .edit-btn:hover {
    background-color: #ffc107 !important;
    color: #000 !important;
    border-color: #ffc107 !important;
}

.action-buttons .start-btn {
    color: #198754 !important;
    border: 1px solid #198754 !important;
    background-color: transparent !important;
}

.action-buttons .start-btn:hover {
    background-color: #198754 !important;
    color: #fff !important;
    border-color: #198754 !important;
}

/* Pagination styling to match drivers/vehicles */
.pagination {
    display: flex;
    justify-content: flex-end;
    list-style: none;
}

.pagination .page-link {
    color: #1e6031;
    border-color: #1e6031;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    display: block;
    text-decoration: none;
    background-color: #fff;
    border: 1px solid #1e6031;
    border-radius: 0.25rem;
}

.pagination .page-link:hover {
    background-color: #1e6031;
    border-color: #1e6031;
    color: white;
}

.pagination .active .page-link {
    background-color: #1e6031;
    border-color: #1e6031;
    color: white;
}

.page-item:first-child .page-link,
.page-item:last-child .page-link {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: #1e6031;
    border-color: #1e6031;
    color: white;
    border-radius: 0.25rem;
}

.page-item:first-child .page-link:hover,
.page-item:last-child .page-link:hover {
    background-color: #164f2a;
    border-color: #164f2a;
}

.page-item.disabled .page-link {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #6c757d;
    padding: 0.25rem 0.5rem;
}

.page-item {
    margin: 0 2px;
}

.page-item.active .page-link {
    background-color: #1e6031;
    border-color: #1e6031;
    color: white;
    padding: 0.25rem 0.5rem;
}

.page-item.disabled .page-link {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #6c757d;
    padding: 0.25rem 0.5rem;
    cursor: not-allowed;
}
</style>
       <!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Trip Ticket Details Modal (Hidden by default) -->
<div id="tripTicketModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 transition-all duration-200">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0" id="tripTicketModalContent">
        <!-- Modal Header - DSU Themed -->
        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #1e6031 0%, #2d7a3e 50%, #1e6031 100%);">
            <div class="flex items-center gap-3">
                <!-- Icon Circle -->
                <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                    <!-- Trip/Car Icon -->
                    <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 13l1-3a2 2 0 012-1h12a2 2 0 012 1l1 3M5 13h14M5 13v4a1 1 0 001 1h1m10-5v4a1 1 0 01-1 1h-1M7 18a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white">Trip Ticket Details</h3>
            </div>
        </div>
        
        <!-- Modal Content -->
        <div class="px-6 py-4">
            <div id="modalContent" class="text-sm text-gray-800">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2">
            <button type="button" id="excelButton" onclick="viewExcel()" class="hidden px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                <i class="fas fa-file-excel mr-1"></i> View Excel
            </button>
            <button type="button" onclick="closeTripTicketModal()" class="px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Passengers Modal (Hidden by default) -->
<div id="passengersModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 transition-all duration-200">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full max-h-[80vh] overflow-y-auto border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0" id="passengersModalContent">
        <!-- Modal Header -->
        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #1e6031 0%, #2d7a3e 50%, #1e6031 100%);">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                    <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white">Passengers List</h3>
            </div>
            <button type="button" onclick="closePassengersModal()" class="text-white hover:text-gray-200">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <!-- Modal Content -->
        <div id="passengersModalBody" class="px-6 py-4">
            <!-- Content will be loaded dynamically -->
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white">
            <button type="button" onclick="closePassengersModal()" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                Close
            </button>
        </div>
    </div>
</div>

<script>
let currentExcelUrl = '';

function showTripTicketDetails(ticketId) {
    const modal = document.getElementById('tripTicketModal');
    const modalContent = document.getElementById('tripTicketModalContent');
    const contentDiv = document.getElementById('modalContent');
    const excelButton = document.getElementById('excelButton');
    
    // Show modal backdrop immediately
    modal.classList.remove('hidden');
    
    // Trigger reflow
    void modal.offsetWidth;
    
    // Animate in
    modalContent.classList.remove('scale-95', 'opacity-0');
    modalContent.classList.add('scale-100', 'opacity-100');
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Hide Excel button initially
  excelButton.classList.add('hidden');
    
    // Show loading state
    contentDiv.innerHTML = `
        <div class="text-center py-8">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading trip ticket details...</p>
        </div>
    `;
    
    // Fetch trip ticket details via AJAX
    fetch(`/employee/trip-tickets/api/${ticketId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load trip ticket details');
            }
          return response.json();
        })
        .then(data => {
            if (data.success) {
                const ticket = data.trip_ticket || data.data || {};
                const itinerary = (ticket && ticket.itinerary) ? ticket.itinerary : {};
                
                // Build status badge matching dashboard cards
               let statusColors = { bg: '#DFF0D8', text: '#00703C', border: '#00703C' };
               if (ticket.status === 'Pending') statusColors = { bg: '#FFF4E0', text: '#F0AD4E', border: '#F0AD4E' };
               else if (ticket.status === 'On-Going') statusColors = { bg: '#E3F0F7', text: '#056295', border: '#056295' };
               else if (ticket.status === 'Cancelled') statusColors = { bg: '#FFE0E0', text: '#DC3545', border: '#DC3545' };
                
                // Format date range
                function formatDateRange(dateFrom, dateTo) {
                    if (!dateFrom && !dateTo) return 'N/A';
                    if (!dateFrom) return formatDate(dateTo);
                    if (!dateTo) return formatDate(dateFrom);
                    
                    const from = new Date(dateFrom);
                    const to = new Date(dateTo);
                    
                    // Check if same day
                    const sameDay = from.toDateString() === to.toDateString();
                    if (sameDay) {
                        return formatDate(dateFrom);
                    }
                    
                    // Format individual components
                    const fromMonth = from.toLocaleDateString('en-US', { month: 'short' });
                    const fromDay = from.toLocaleDateString('en-US', { day: 'numeric' });
                    const fromYear = from.toLocaleDateString('en-US', { year: 'numeric' });
                    
                    const toMonth = to.toLocaleDateString('en-US', { month: 'short' });
                    const toDay = to.toLocaleDateString('en-US', { day: 'numeric' });
                    const toYear = to.toLocaleDateString('en-US', { year: 'numeric' });
                    
                    const sameMonth = from.getMonth() === to.getMonth();
                    const sameYear = from.getFullYear() === to.getFullYear();
                    
                    if (sameMonth && sameYear) {
                        // Same month and year: Jan 1-2, 2026
                        return `${fromMonth} ${fromDay}-${toDay}, ${fromYear}`;
                    } else if (sameYear) {
                        // Same year, different months: Jan 31 - Feb 1, 2026
                        return `${fromMonth} ${fromDay} - ${toMonth} ${toDay}, ${fromYear}`;
                    } else {
                        // Different years: Dec 31, 2026 - Jan 1, 2027
                        return `${fromMonth} ${fromDay}, ${fromYear} - ${toMonth} ${toDay}, ${toYear}`;
                    }
                }
                
                // Format single date
                function formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    const monthStr = date.toLocaleDateString('en-US', { month: 'short' });
                    const dayStr = date.toLocaleDateString('en-US', { day: 'numeric' });
                    const yearStr = date.toLocaleDateString('en-US', { year: 'numeric' });
                    
                    return `${monthStr} ${dayStr}, ${yearStr}`;
                }
                
                // Format time
                function formatTime(timeString) {
                    if (!timeString) return 'N/A';
                    const parts = timeString.split(':');
                    if (parts.length >= 2) {
                        const hours = parseInt(parts[0]);
                        const minutes = parts[1];
                        const ampm = hours >= 12 ? 'PM' : 'AM';
                        const displayHours = hours % 12 || 12;
                      return `${displayHours}:${minutes} ${ampm}`;
                    }
                  return timeString;
                }
                
                // Get travel order employee info
                const toEmployee = (itinerary && itinerary.travel_order_employee) ? itinerary.travel_order_employee : {};
                
                // Build HTML content matching travel order design EXACTLY
                let html = `
                    <div class="text-sm text-gray-800">
                        <table class="w-full">
                            <tbody>
                                <!-- Travel Details Section -->
                                <tr>
                                    <td colspan="2" class="font-bold text-gray-900 py-1.5 border-b mt-2">Travel Details</td>
                                </tr>
                                ${itinerary.driver ? `
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Driver:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${itinerary.driver.full_name || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Contact Number:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${itinerary.driver.contact_num || '-'}</td>
                                </tr>
                                ` : ''}
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Date:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${formatDateRange(itinerary.date_from, itinerary.date_to)}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Time:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${formatTime(itinerary.departure_time)}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Destination:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${itinerary.destination || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Purpose:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${itinerary.travel_order_purpose || itinerary.purpose || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Status:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background:${statusColors.bg}; color:${statusColors.text}; border:1px solid ${statusColors.border};">
                                            ${ticket.status}
                                        </span>
                                    </td>
                                </tr>
                                
                                <!-- Passengers Section -->
                                <tr>
                                    <td colspan="2" class="font-bold text-gray-900 py-1.5 border-b mt-2">Passengers</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Head of Party:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${ticket.head_of_party || 'Not specified'}</td>
                                </tr>
                ${ticket.passengers && ticket.passengers.length > 0 ? `
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Passengers:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${(() => {
                                            const validPassengers = ticket.passengers.filter(p => p && p.trim() !== '');
                                            const totalPassengers = validPassengers.length;
                                            if (totalPassengers === 0) return 'No additional passengers';
                                            const firstPassenger = validPassengers[0];
                                            if (totalPassengers > 1) {
                                                const remainingCount = totalPassengers - 1;
                                                const passengersStr = validPassengers.join('|');
                                                const ticketNum = (ticket.ticket_number || 'Trip').replace(/'/g, "\\'");
                                                return `<span class="text-gray-900">${firstPassenger}</span><button type="button" onclick="viewPassengersModalFromModal('${passengersStr}', '${ticketNum}')" class="ml-1 text-blue-600 hover:text-blue-800 font-medium cursor-pointer">+${remainingCount} more</button>`;
                                            } else {
                                                return `<span class="text-gray-900">${firstPassenger}</span>`;
                                            }
                                        })()}
                                    </td>
                                </tr>
                                ` : '<tr><td colspan="2" class="text-gray-500 italic py-0.5">No additional passengers listed</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                `;
                
                contentDiv.innerHTML = html;
                
                // Show Excel button if available
                if (ticket.id) {
                    currentExcelUrl = `/employee/trip-tickets/${ticket.id}/excel`;
                  excelButton.classList.remove('hidden');
                }
            } else {
                contentDiv.innerHTML = `
                    <div class="alert alert-danger text-center py-8">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl mb-2"></i>
                        <p class="text-gray-700">Failed to load trip ticket details. Please try again.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contentDiv.innerHTML = `
                <div class="alert alert-danger text-center py-8">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl mb-2"></i>
                    <p class="text-gray-700">An error occurred while loading the trip ticket details.</p>
                </div>
            `;
        });
}

function closeTripTicketModal() {
    const modal = document.getElementById('tripTicketModal');
    const modalContent = document.getElementById('tripTicketModalContent');
    
    if (modal && modalContent) {
        // Animate out
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        // Remove modal after animation completes
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 200);
    }
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

// Excel view function - direct download with confirmation
function viewExcel() {
    if (currentExcelUrl) {
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
                window.open(currentExcelUrl, '_blank');
            }
        });
    }
}

// Function to view passengers modal from trip ticket modal
window.viewPassengersModalFromModal = function(passengersStr, ticketNumber) {
    // Convert pipe-separated string to array
    const passengers = passengersStr.split('|').filter(p => p && p.trim() !== '');
    
    const modal = document.getElementById('passengersModal');
    const modalContent = document.getElementById('passengersModalContent');
    const modalBody = document.getElementById('passengersModalBody');
    
    // Build passengers list HTML
    let passengersHtml = `
        <div class="space-y-2">
            <p class="text-sm text-gray-600 mb-2">
                <span class="font-semibold">Trip Ticket:</span> ${ticketNumber}
            </p>
            <p class="text-sm text-gray-600 mb-2">
                <span class="font-semibold">Total Passengers:</span> ${passengers.length}
            </p>
            <div class="border-t border-gray-200 pt-2">
                <h4 class="font-semibold text-gray-800 mb-2 text-sm">Complete List:</h4>
                <ul class="space-y-1">
        `;
    
    passengers.forEach((passenger, index) => {
        passengersHtml += `
                    <li class="flex items-center gap-2 text-sm text-gray-700">
                        <span class="w-5 h-5 flex items-center justify-center bg-green-100 text-green-700 rounded-full text-xs font-bold">${index + 1}</span>
                        <span>${passenger}</span>
                    </li>
        `;
    });
    
    passengersHtml += `
                </ul>
            </div>
        </div>
    `;
    
    modalBody.innerHTML = passengersHtml;
    
    // Show modal
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modalContent.classList.remove('scale-95', 'opacity-0');
    modalContent.classList.add('scale-100', 'opacity-100');
    document.body.style.overflow = 'hidden';
};

// Function to close passengers modal
window.closePassengersModal = function() {
    const modal = document.getElementById('passengersModal');
    const modalContent = document.getElementById('passengersModalContent');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }, 200);
};

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const tripTicketModal = document.getElementById('tripTicketModal');
    if (tripTicketModal) {
        tripTicketModal.addEventListener('click', function(e) {
            if (e.target === tripTicketModal) {
                closeTripTicketModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function handleEscape(e) {
            if (e.key === 'Escape') {
                closeTripTicketModal();
            }
        });
    }
    
    // Close passengers modal when clicking outside
    const passengersModal = document.getElementById('passengersModal');
    if (passengersModal) {
        passengersModal.addEventListener('click', function(e) {
            if (e.target === passengersModal) {
                closePassengersModal();
            }
        });
        
        // Close passengers modal with Escape key
        document.addEventListener('keydown', function handleEscape(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('passengersModal');
                if (modal && !modal.classList.contains('hidden')) {
                    closePassengersModal();
                }
            }
        });
    }
});

// Completion File Modal Functions
function viewCompletionFile(fileUrl) {
    if (!fileUrl || fileUrl === 'https://drive.google.com') {
        Swal.fire({
            icon: 'warning',
            title: 'No File Available',
            text: 'No completion file has been uploaded yet.',
            confirmButtonColor: '#1e6031'
        });
        return;
    }
    
    // Convert Google Drive view URL to preview URL for iframe embedding (with full toolbar)
    const previewUrl = fileUrl.replace('/view?usp=drivesdk', '/preview');
    
    // Create modal HTML - EXACTLY like vehicles generate PDF modal
    const modalHtml = `
        <div id="completionFileModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl w-full max-w-6xl h-full flex flex-col shadow-lg">
                <iframe src="${previewUrl}" class="flex-1 w-full h-full border-none rounded-b-2xl"></iframe>
            </div>
        </div>
    `;
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Close modal when clicking outside
    const modal = document.getElementById('completionFileModal');
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            modal.remove();
            document.body.style.overflow = '';
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function handleEscape(e) {
        const modal = document.getElementById('completionFileModal');
        if (modal && e.key === 'Escape') {
            modal.remove();
            document.body.style.overflow = '';
            document.removeEventListener('keydown', handleEscape);
        }
    });
}

function closeModal() {
    const modal = document.getElementById('completionFileModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

</script>



@endsection