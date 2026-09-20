<x-app-layout>
    <div class="py-6 px-3 sm:px-4 md:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Back Button -->
            <div class="mb-4">
                <a href="{{ route('driver.dashboard') }}" 
                   class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition duration-300 shadow-md text-xs sm:text-sm font-medium no-underline" style="text-decoration: none;">
                    <svg class="h-3 w-3 sm:h-4 sm:w-4 mr-1.5 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span class="text-xs sm:text-sm">Back to Dashboard</span>
                </a>
            </div>
            
            @if(!$driver)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Access Denied</h3>
                        <p class="mt-2 text-base text-gray-500">
                            No driver record found for your account.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('driver.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[#1e6031] hover:bg-[#164f2a] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1e6031]">
                                Return to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <!-- Page Header -->
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <!-- ROW 1 -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <!-- Title -->
                        <h1 class="text-lg sm:text-xl font-bold text-[#1e6031] mb-0 whitespace-nowrap">
                            My Trip Tickets
                        </h1>
                    </div>

                    <p class="text-gray-600 text-xs sm:text-sm" style="margin-top: 0; line-height: 1.2;">
                        View trip tickets assigned to you as a driver.
                    </p>
                </div>

                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200 overflow-x-auto">
                    <nav class="flex space-x-4 sm:space-x-6" aria-label="Tabs">
                        <a href="{{ route('driver.trip-tickets', ['tab' => 'pending']) }}" 
                           class="{{ ($tab ?? 'pending') == 'pending' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-xs sm:text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="pending"
                           style="text-decoration: none;">
                            Pending
                        </a>
                        <a href="{{ route('driver.trip-tickets', ['tab' => 'completed']) }}" 
                           class="{{ ($tab ?? 'pending') == 'completed' ? 'border-green-500 text-green-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-xs sm:text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="completed"
                           style="text-decoration: none;">
                            Completed
                        </a>
                        <a href="{{ route('driver.trip-tickets', ['tab' => 'cancelled']) }}" 
                           class="{{ ($tab ?? 'pending') == 'cancelled' ? 'border-red-500 text-red-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-xs sm:text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="cancelled"
                           style="text-decoration: none;">
                            Cancelled
                        </a>
                    </nav>
                </div>

                <!-- Trip Tickets Table -->
                @if(!$tripTickets->isEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead style="background-color: #1e6031; color: white;">
                                <tr>
                                    <th style="padding: 8px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">No.</th>
                                    <th style="padding: 8px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Trip Ticket No.</th>
                                    <th style="padding: 8px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Vehicle</th>
                                    <th style="padding: 8px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Date</th>
                                    <th style="padding: 8px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Passengers</th>
                                    <th style="padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Status</th>
                                    <th style="padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tripTickets as $index => $ticket)
                                    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium">{{ $loop->iteration }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                                            <span class="text-gray-700 font-medium">{{ $ticket->ticket_number }}</span>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                                            @if($ticket->itinerary && $ticket->itinerary->vehicle)
                                                <span class="text-gray-700 font-medium">{{ $ticket->itinerary->vehicle->plate_number }} - {{ $ticket->itinerary->vehicle->model }}</span>
                                            @else
                                                <span class="text-gray-500">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                                            @if($ticket->itinerary && $ticket->itinerary->date_from)
                                                @php
                                                    $dateFrom = \Carbon\Carbon::parse($ticket->itinerary->date_from);
                                                    $dateTo = $ticket->itinerary->date_to ? \Carbon\Carbon::parse($ticket->itinerary->date_to) : $dateFrom;
                                                    
                                                    $isSameDay = $dateFrom->toDateString() === $dateTo->toDateString();
                                                    
                                                    if ($isSameDay) {
                                                        $formattedDate = $dateFrom->format('M d, Y');
                                                    } else {
                                                        $sameMonth = $dateFrom->month === $dateTo->month;
                                                        $sameYear = $dateFrom->year === $dateTo->year;
                                                        
                                                        if ($sameMonth && $sameYear) {
                                                            $formattedDate = $dateFrom->format('M j') . '-' . $dateTo->format('j') . ', ' . $dateFrom->format('Y');
                                                        } elseif ($sameYear) {
                                                            $formattedDate = $dateFrom->format('M j') . ' - ' . $dateTo->format('M j') . ', ' . $dateFrom->format('Y');
                                                        } else {
                                                            $formattedDate = $dateFrom->format('M d, Y') . ' - ' . $dateTo->format('M d, Y');
                                                        }
                                                    }
                                                    
                                                    $formattedTime = '';
                                                    if($ticket->itinerary->departure_time) {
                                                        $departureTime = \Carbon\Carbon::parse($ticket->itinerary->departure_time);
                                                        $formattedTime = ' at ' . $departureTime->format('g:i A');
                                                    }
                                                @endphp
                                                <span class="text-gray-700">{{ $formattedDate }}{{ $formattedTime }}</span>
                                            @else
                                                <span class="text-gray-500">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                                            @php
                                                $allPassengers = [];
                                                if ($ticket->itinerary && $ticket->itinerary->travelOrder) {
                                                    $allPassengers[] = $ticket->itinerary->travelOrder->full_name ?? null;
                                                }
                                                if ($ticket->passengers && is_array($ticket->passengers)) {
                                                    foreach ($ticket->passengers as $passenger) {
                                                        if (is_string($passenger) && strpos($passenger, '|') !== false) {
                                                            $allPassengers = array_merge($allPassengers, explode('|', $passenger));
                                                        } else if (!empty($passenger)) {
                                                            $allPassengers[] = $passenger;
                                                        }
                                                    }
                                                }
                                                $filteredPassengers = array_filter($allPassengers);
                                                $totalPassengers = count($filteredPassengers);
                                                $displayCount = min(1, $totalPassengers);
                                                $remainingCount = max(0, $totalPassengers - $displayCount);
                                            @endphp
                                            @if($totalPassengers > 0)
                                                <span class="text-gray-700">{{ implode(', ', array_slice(array_values($filteredPassengers), 0, $displayCount)) }}</span>
                                                @if($remainingCount > 0)
                                                    <button type="button" 
                                                            onclick="viewPassengersModal({{ json_encode(array_values($filteredPassengers)) }}, '{{ $ticket->ticket_number ?? 'Trip' }}')"
                                                            class="ml-1 text-blue-600 hover:text-blue-800 font-medium cursor-pointer">
                                                        +{{ $remainingCount }} more
                                                    </button>
                                                @endif
                                            @else
                                                <span class="text-gray-500">-</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center">
                                            @php
                                                $statusStyles = [
                                                    'Pending' => 'background:#FFF4E0; color:#F0AD4E; border:1px solid #F0AD4E;',
                                                    'Approved' => 'background:#DFF0D8; color:#00703C; border:1px solid #00703C;',
                                                    'On-going' => 'background:#E3F0F7; color:#056295; border:1px solid #056295;',
                                                    'On-Going' => 'background:#E3F0F7; color:#056295; border:1px solid #056295;',
                                                    'Ongoing' => 'background:#E3F0F7; color:#056295; border:1px solid #056295;',
                                                    'Completed' => 'background:#DFF0D8; color:#00703C; border:1px solid #00703C;',
                                                    'Cancelled' => 'background:#FFE0E0; color:#DC3545; border:1px solid #DC3545;',
                                                ];
                                                // For pending tab, show "Ready to go" for Approved status
                                                $displayStatus = $ticket->status;
                                                if ($tab == 'pending' && $ticket->status == 'Approved') {
                                                    $style = 'background:#FFF4E0; color:#F0AD4E; border:1px solid #F0AD4E;';
                                                    $displayStatus = 'Ready to go';
                                                } else {
                                                    $style = $statusStyles[$ticket->status] ?? 'background:#f3f4f6; color:#6b7280; border:1px solid #d1d5db;';
                                                }
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" style="{{ $style }}">
                                                {{ $displayStatus }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                            <div class="action-buttons flex justify-center gap-1">
                                                <button type="button" 
                                                        onclick="viewTripTicketDetails({{ $ticket->id }})"
                                                        class="btn view-btn border inline-flex items-center justify-center"
                                                        title="View Trip Ticket Details">
                                                    <svg class="h-3 w-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span class="text-xs">View</span>
                                                </button>
                                                @if($ticket->status === 'Approved')
                                                    <span class="inline-flex items-center px-2 py-1 rounded border border-yellow-300 text-yellow-700 bg-yellow-50 text-[11px] font-semibold">
                                                        <i class="fas fa-location-arrow mr-1"></i> Auto at Gate
                                                    </span>
                                                @elseif($ticket->status === 'On-Going')
                                                    <span class="inline-flex items-center px-2 py-1 rounded border border-blue-300 text-blue-700 bg-blue-50 text-[11px] font-semibold">
                                                        <i class="fas fa-satellite-dish mr-1"></i> GPS Tracking
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex justify-between items-center mt-4">
                        <div class="text-xs sm:text-sm text-gray-600">
                            Showing {{ $tripTickets->firstItem() ?? 0 }} to {{ $tripTickets->lastItem() ?? 0 }} of {{ $tripTickets->total() }} trip tickets
                        </div>
                        <div class="flex items-center space-x-1 sm:space-x-2">
                            <nav>
                                <ul class="pagination">
                                    {{-- Previous Button --}}
                                    @if($tripTickets->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link text-xs sm:text-sm px-2 sm:px-3 py-1">Prev</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a href="{{ $tripTickets->previousPageUrl() }}" class="page-link text-xs sm:text-sm px-2 sm:px-3 py-1">Prev</a>
                                        </li>
                                    @endif
                                    
                                    {{-- Current Page --}}
                                    <li class="page-item active">
                                        <span class="page-link text-xs sm:text-sm px-2 sm:px-3 py-1">{{ $tripTickets->currentPage() }}</span>
                                    </li>
                                    
                                    {{-- Next Button --}}
                                    @if($tripTickets->hasMorePages())
                                        <li class="page-item">
                                            <a href="{{ $tripTickets->nextPageUrl() }}" class="page-link text-xs sm:text-sm px-2 sm:px-3 py-1">Next</a>
                                        </li>
                                    @else
                                        <li class="page-item disabled">
                                            <span class="page-link">Next</span>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No trip tickets found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            You don't have any trip tickets in the "{{ ucfirst($tab ?? 'pending') }}" tab.
                        </p>
                    </div>
                @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal for Trip Ticket Details -->
    <div id="tripTicketModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
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
            <div id="tripTicketDetailsContent" class="px-6 py-4">
                <!-- Content will be loaded dynamically -->
            </div>
            
            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white">
                <button type="button" onclick="closeTripTicketModal()" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Passengers Modal -->
    <div id="passengersModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
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
    function viewPassengersModalFromModal(passengersStr, ticketNumber) {
        // Convert pipe-separated string to array
        const passengers = passengersStr.split('|').filter(p => p && p.trim() !== '');
        viewPassengersModalArray(passengers, ticketNumber);
    }
    
    function viewPassengersModal(passengers, ticketNumber) {
        viewPassengersModalArray(passengers, ticketNumber);
    }
    
    function viewPassengersModalArray(passengers, ticketNumber) {
        const modal = document.getElementById('passengersModal');
        const modalContent = document.getElementById('passengersModalContent');
        const modalBody = document.getElementById('passengersModalBody');
        
        // Build passengers list HTML
        let passengersHtml = `
            <div class="space-y-2">
                <p class="text-sm text-gray-600 mb-3">
                    <span class="font-semibold">Trip Ticket:</span> ${ticketNumber}
                </p>
                <p class="text-sm text-gray-600 mb-3">
                    <span class="font-semibold">Total Passengers:</span> ${passengers.length}
                </p>
                <div class="border-t border-gray-200 pt-3">
                    <h4 class="font-semibold text-gray-800 mb-2 text-sm">Complete List:</h4>
                    <ul class="space-y-1.5">
        `;
        
        passengers.forEach((passenger, index) => {
            passengersHtml += `
                <li class="flex items-start gap-2 text-sm text-gray-700">
                    <span class="flex-shrink-0 w-5 h-5 bg-green-100 text-green-700 rounded-full flex items-center justify-center text-xs font-semibold">${index + 1}</span>
                    <span class="flex-1">${passenger}</span>
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
    }

    function closePassengersModal() {
        const modal = document.getElementById('passengersModal');
        const modalContent = document.getElementById('passengersModalContent');
        
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 200);
    }

    // Close passengers modal when clicking outside (v2.0 - Fixed null check)
    try {
        const passengersModalElement = document.getElementById('passengersModal');
        if (passengersModalElement) {
            passengersModalElement.addEventListener('click', function(event) {
                if (event.target === this) {
                    closePassengersModal();
                }
            });
        }
    } catch (e) {
        console.warn('Error setting up passengers modal click handler:', e);
    }

    // Close passengers modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePassengersModal();
        }
    });

    function viewTripTicketDetails(ticketId) {
        const modal = document.getElementById('tripTicketModal');
        const modalContent = document.getElementById('tripTicketModalContent');
        
        // Show modal backdrop immediately
        modal.classList.remove('hidden');
        
        // Trigger reflow
        void modal.offsetWidth;
        
        // Animate in
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Fetch trip ticket details via AJAX
        fetch(`/api/trip-tickets/${ticketId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('API Response:', data);
                if (data.success) {
                    showTripTicketModal(data.data);
                } else {
                    alert('Error loading trip ticket details: ' + (data.message || 'Unknown error'));
                    closeTripTicketModal();
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Error loading trip ticket details: ' + error.message);
                closeTripTicketModal();
            });
    }

    function showTripTicketModal(ticket) {
        const modal = document.getElementById('tripTicketModal');
        const contentDiv = document.getElementById('tripTicketDetailsContent');
        
        // Debug: Log the ticket data to console
        console.log('Trip Ticket Data:', ticket);
        console.log('Passengers:', ticket.passengers);
        console.log('Passengers type:', typeof ticket.passengers);
        console.log('Is array?', Array.isArray(ticket.passengers));
        
        // Format passenger list
        let passengersHtml = '';
        if (ticket.passengers && Array.isArray(ticket.passengers) && ticket.passengers.length > 0) {
            console.log('Has passengers:', ticket.passengers);
            // Process passengers - split pipe-separated values
            const processedPassengers = [];
            ticket.passengers.forEach(passenger => {
                if (typeof passenger === 'string' && passenger.includes('|')) {
                    processedPassengers.push(...passenger.split('|').filter(p => p && p.trim() !== ''));
                } else if (passenger) {
                    processedPassengers.push(passenger);
                }
            });
            passengersHtml = '<div class="space-y-1 text-sm text-gray-900">' + processedPassengers.map(p => `<div>${p}</div>`).join('') + '</div>';
        } else {
            console.log('No passengers found or not an array');
            passengersHtml = '<span class="text-gray-500 italic text-sm">No passengers listed</span>';
        }
        
    const contentHtml = `
    <div class="text-xs text-gray-800 sm:text-sm">
        <!-- Trip Information Section -->
        <div class="mb-2 sm:mb-3">
            <h4 class="font-bold text-gray-900 border-b pb-1 mb-1.5 text-xs sm:text-sm">Trip Information</h4>
            <table class="w-full">
                <tbody>
                    <!-- Driver -->
                    <tr>
                        <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Driver:</td>
                        <td class="text-gray-900 py-0.5 align-top">${ticket.itinerary?.driver?.full_name || 'N/A'}</td>
                    </tr>

                    <!-- Vehicle -->
                    <tr>
                        <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Vehicle:</td>
                        <td class="text-gray-900 py-0.5 align-top">
                            ${ticket.itinerary?.vehicle
                                ? `${ticket.itinerary.vehicle.plate_number} - ${ticket.itinerary.vehicle.model}`
                                : 'N/A'}
                        </td>
                    </tr>

                    <!-- Destination -->
                    <tr>
                        <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Destination:</td>
                        <td class="text-gray-900 py-0.5 align-top">${ticket.itinerary?.destination || 'N/A'}</td>
                    </tr>

                    <!-- Purpose -->
                    <tr>
                        <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Purpose:</td>
                        <td class="text-gray-900 py-0.5 align-top whitespace-pre-wrap">${ticket.itinerary?.purpose || 'N/A'}</td>
                    </tr>

                    <!-- Departure Date -->
                    <tr>
                        <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Date:</td>
                        <td class="text-gray-900 py-0.5 align-top">
                            ${(() => {
                                if (!ticket.itinerary?.date_from) return 'N/A';
                                
                                const dateFrom = new Date(ticket.itinerary.date_from);
                                const dateTo = ticket.itinerary.date_to ? new Date(ticket.itinerary.date_to) : dateFrom;
                                
                                // Check if same date
                                const isSameDate = dateFrom.toDateString() === dateTo.toDateString();
                                
                                if (isSameDate) {
                                    // Same date: Show "Mar 14, 2026"
                                    return dateFrom.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                                } else {
                                    // Different dates
                                    const fromMonth = dateFrom.toLocaleDateString('en-US', { month: 'short' });
                                    const fromDay = dateFrom.getDate();
                                    const fromYear = dateFrom.getFullYear();
                                    const toMonth = dateTo.toLocaleDateString('en-US', { month: 'short' });
                                    const toDay = dateTo.getDate();
                                    const toYear = dateTo.getFullYear();
                                    
                                    // Check if same month and year
                                    if (fromMonth === toMonth && fromYear === toYear) {
                                        // Same month/year: "Jan 1-2, 2026"
                                        return `${fromMonth} ${fromDay}-${toDay}, ${fromYear}`;
                                    }
                                    // Check if same year but different month
                                    else if (fromYear === toYear) {
                                        // Same year: "Jan 31 - Feb 1, 2026"
                                        return `${fromMonth} ${fromDay} - ${toMonth} ${toDay}, ${fromYear}`;
                                    }
                                    // Different years
                                    else {
                                        // Different years: "Dec 31, 2026 - Jan 1, 2027"
                                        return `${fromMonth} ${fromDay}, ${fromYear} - ${toMonth} ${toDay}, ${toYear}`;
                                    }
                                }
                            })()}
                        </td>
                    </tr>

                    <!-- Departure Time -->
                    <tr>
                        <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Time:</td>
                        <td class="text-gray-900 py-0.5 align-top">
                            ${(() => {
                                if (!ticket.itinerary?.departure_time) return 'N/A';
                                
                                // Parse the time and format as "9:00 AM"or "1:30 PM"
                                const timeStr = ticket.itinerary.departure_time;
                                const [hours, minutes] = timeStr.split(':');
                                const hour = parseInt(hours);
                                const ampm = hour >= 12 ? 'PM' : 'AM';
                                const displayHour = hour % 12 || 12;
                                return `${displayHour}:${minutes} ${ampm}`;
                            })()}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Employee Information Section -->
        <div>
            <h4 class="font-bold text-gray-900 border-b pb-1 mb-1.5 text-xs sm:text-sm">Employee Information</h4>
            <table class="w-full">
                <tbody>
                    <!-- Head of Party -->
                    <tr>
                        <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Head of Party:</td>
                        <td class="text-gray-900 py-0.5 align-top">${ticket.head_of_party || 'Not specified'}</td>
                    </tr>

                    <!-- Contact Number -->
                    <tr>
                        <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Contact Number:</td>
                        <td class="text-gray-900 py-0.5 align-top">${ticket.itinerary?.travel_order_employee?.contact_num || '-'}</td>
                    </tr>

                    <!-- Additional Passengers -->
                    <tr>
                        <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Passengers:</td>
                        <td class="text-gray-900 py-0.5 align-top">
                            ${(() => {
                                let passengersArray = [];
                                
                                // Handle both array and pipe-separated string
                                if (Array.isArray(ticket.passengers)) {
                                    ticket.passengers.forEach(passenger => {
                                        if (typeof passenger === 'string' && passenger.includes('|')) {
                                            passengersArray.push(...passenger.split('|').filter(p => p && p.trim() !== ''));
                                        } else if (passenger) {
                                            passengersArray.push(passenger);
                                        }
                                    });
                                } else if (typeof ticket.passengers === 'string') {
                                    passengersArray = ticket.passengers.split('|').filter(p => p && p.trim() !== '');
                                }
                                
                                const validPassengers = passengersArray.filter(p => p && p.trim() !== '');
                                const totalPassengers = validPassengers.length;
                                
                                if (totalPassengers === 0) {
                                    return '<span class="text-gray-500 italic">No additional passengers</span>';
                                }
                                
                                // Show first passenger + "+X more" button if there are more
                                const firstPassenger = validPassengers[0];
                                const remainingCount = totalPassengers - 1;
                                
                                if (remainingCount > 0) {
                                    const passengersStr = validPassengers.join('|');
                                    const ticketNum = (ticket.ticket_number || 'Trip').replace(/'/g, "\\'");
                                    return `<span>${firstPassenger}</span><button type="button" onclick="viewPassengersModalFromModal('${passengersStr}', '${ticketNum}')" class="ml-1 text-blue-600 hover:text-blue-800 font-medium cursor-pointer">+${remainingCount} more</button>`;
                                } else {
                                    return `<span>${firstPassenger}</span>`;
                                }
                            })()}
                        </td>
                    </tr>

                    <!-- Status/Remarks -->
                    <tr class="border-t pt-2 mt-1">
                        <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">
                            ${['Pending', 'Approved'].includes(ticket.status) ? 'Remarks:' : 'Status:'}
                        </td>
                        <td class="py-0.5 align-top">
                            ${(() => {
                                // Handle Pending and Approved statuses with "Ready to go" or custom remarks
                                if (ticket.status === 'Pending') {
                                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" style="background:#FFF4E0; color:#F0AD4E; border:1px solid #F0AD4E;">Pending</span>';
                                }
                                
                                if (ticket.status === 'Approved') {
                                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" style="background:#FFF4E0; color:#F0AD4E; border:1px solid #F0AD4E;">Ready to go</span>';
                                }
                                
                                // Status badge colors matching dashboard cards
                                const statusColors = {
                                    'On-going': { bg: '#E3F0F7', text: '#056295', border: '#056295' },
                                    'On-Going': { bg: '#E3F0F7', text: '#056295', border: '#056295' },
                                    'Ongoing': { bg: '#E3F0F7', text: '#056295', border: '#056295' },
                                    'Completed': { bg: '#DFF0D8', text: '#00703C', border: '#00703C' },
                                    'Cancelled': { bg: '#FFE0E0', text: '#DC3545', border: '#DC3545' }
                                };
                                
                                const colors = statusColors[ticket.status] || { bg: '#f3f4f6', text: '#6b7280', border: '#d1d5db' };
                                return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" style="background:${colors.bg}; color:${colors.text}; border:1px solid ${colors.border};">${ticket.status}</span>`;
                            })()}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
`;
        contentDiv.innerHTML = contentHtml;
        modal.classList.remove('hidden');
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    function closeTripTicketModal() {
        const modal = document.getElementById('tripTicketModal');
        const modalContent = document.getElementById('tripTicketModalContent');
        
        // Animate out
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        // Wait for animation to complete before hiding
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 200); // Match this with transition duration
    }

    // Close modal when clicking outside (v2.0 - Fixed null check)
    try {
        const tripTicketModalElement = document.getElementById('tripTicketModal');
        if (tripTicketModalElement) {
            tripTicketModalElement.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeTripTicketModal();
                }
            });
        }
    } catch (e) {
        console.warn('Error setting up trip ticket modal click handler:', e);
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTripTicketModal();
        }
    });
    </script>
    
    <style>
    .action-buttons .view-btn {
        color: #0d6efd !important;
        border: 1px solid #0d6efd !important;
        background-color: transparent !important;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        border-radius: 0.25rem;
        transition: all 0.2s ease-in-out;
    }
    .action-buttons .view-btn:hover {
        background-color: #0d6efd !important;
        color: #fff !important;
        border-color: #0d6efd !important;
    }
    
    /* Pagination styling */
    .pagination {
        display: flex;
        justify-content: flex-end;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .pagination .page-link {
        color: #1e6031;
        border-color: #1e6031;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
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
        cursor: not-allowed;
    }
    
    /* Status badge colors matching dashboard cards */
    .status-badge-pending {
        background-color: #FFF4E0 !important;
       color: #F0AD4E !important;
        border: 1px solid #F0AD4E !important;
    }
    .status-badge-ongoing {
        background-color: #E3F0F7 !important;
       color: #056295 !important;
        border: 1px solid #056295 !important;
    }
    .status-badge-completed {
        background-color: #DFF0D8 !important;
       color: #00703C !important;
        border: 1px solid #00703C !important;
    }
    .status-badge-cancelled {
        background-color: #FFE0E0 !important;
       color: #DC3545 !important;
        border: 1px solid #DC3545 !important;
    }
    </style>
</x-app-layout>