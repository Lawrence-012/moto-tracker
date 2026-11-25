@extends('layouts.motorpool-admin')

@section('content')
@include('partials.google-maps-script')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="main">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-3">
                <h2 class="text-lg sm:text-2xl font-bold" style="color: #1e6031; font-size: 1.5rem;">Trip Tickets</h2>
                
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <!-- Search Bar -->
                    <div class="flex flex-1 sm:flex-initial">
                        <input type="text" 
                               id="search-input" 
                               class="form-input"
                               placeholder="Search..."
                               data-table-id="trip-tickets-table"
                               data-url="{{ route('trip-tickets.index') }}"
                               value="{{ request('search') }}"
                               style="height: 30px; width: 100%; min-width: 120px; max-width: 180px; font-size: 0.85rem; border: 1px solid #1e6031; border-right: none; border-radius: 0.375rem 0 0 0.375rem; padding: 0 12px;">
                        <button type="button" 
                                class="search-submit-btn"
                                style="background-color: #1e6031; color: #ffffff; border: 1px solid #1e6031; height: 30px; width: 40px; border-radius: 0 0.375rem 0.375rem 0; cursor:pointer; display: flex; align-items: center; justify-content: center;">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>

                    <button type="button" 
                       onclick="showCreateTripTicketModal()" 
                       class="inline-flex items-center px-2 sm:px-3 py-1.5 bg-[#1e6031] border border-[#1e6031] rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#103c1e] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150 flex-shrink-0">
                        <i class="fas fa-plus mr-1 text-xs hidden sm:inline"></i> <span class="hidden sm:inline">Create Trip Ticket</span><span class="sm:hidden">Create</span>
                    </button>
                </div>
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
                        <button type="button" 
                                class="tab-button {{ ($tab ?? 'pending') === 'pending' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                                data-tab="pending"
                                style="text-decoration: none;">
                            Pending
                        </button>
                        <button type="button" 
                                class="tab-button {{ ($tab ?? 'pending') === 'approved' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                                data-tab="approved"
                                style="text-decoration: none;">
                            Approved
                        </button>
                        <button type="button" 
                                class="tab-button {{ ($tab ?? 'pending') === 'completed' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                                data-tab="completed"
                                style="text-decoration: none;">
                            Completed
                        </button>
                        <button type="button" 
                                class="tab-button {{ ($tab ?? 'pending') === 'cancelled' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                                data-tab="cancelled"
                                style="text-decoration: none;">
                            Cancelled
                        </button>
                    </nav>
                </div>
            </div>
            
            <div id="debug-info" class="mb-4 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800" style="display:none;">
                Debug: Press F12 to open console and check for errors
            </div>
            
            <script>
                // Show debug info if there are console errors
                window.addEventListener('error', function(e) {
                    document.getElementById('debug-info').style.display = 'block';
                    document.getElementById('debug-info').innerHTML = '<strong>Error detected!</strong> Check browser console (F12) for details.';
                });
                
                // Log page load
                console.log('Trip Tickets page loaded successfully');
                console.log('Current tab parameter:', '{{ $tab ?? "pending" }}');
            </script>

            <!-- Pending Tab -->
            <div class="tab-content" id="pending-tab">
                <!-- Table with headers always shown -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead style="background-color: #1e6031; color: white;">
                            <tr>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">No.</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Driver</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Head of the Party</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Vehicle</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Destination</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Date</th>
                                <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="pending-table-body">
                            @if($pendingTripTickets->count() > 0)
                                @foreach($pendingTripTickets as $index => $ticket)
                                    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-transparent' }} hover:bg-[#f0f8f0]">
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#004d00] font-medium">{{ $loop->iteration }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#004d00] font-medium">
                                            @if($ticket->itinerary && $ticket->itinerary->driver)
                                                <div>{{ $ticket->itinerary->driver->full_name }}</div>
                                                <div class="text-gray-500 text-xs">{{ $ticket->itinerary->driver->contact_num ?? 'N/A' }}</div>
                                            @else
                                                <span class="text-gray-500">No driver</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @php
                                                $headOfPartyContact = '-';
                                                // Try to get contact from itinerary's first travel order
                                                if ($ticket->itinerary) {
                                                    $travelOrders = $ticket->itinerary->getAllTravelOrders();
                                                    if ($travelOrders && $travelOrders->count() > 0) {
                                                        $firstTO = $travelOrders->first();
                                                        if ($firstTO && $firstTO->employee) {
                                                            // Try to get contact from employee first
                                                            $headOfPartyContact = $firstTO->employee->contact_num ?? '-';
                                                            // If employee has no contact, try from user
                                                            if ($headOfPartyContact === '-' && $firstTO->employee->user && $firstTO->employee->user->contact_num) {
                                                                $headOfPartyContact = $firstTO->employee->user->contact_num;
                                                            }
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <div>{{ $ticket->head_of_party ?? 'Not set' }}</div>
                                            <div class="text-gray-500 text-xs">{{ $headOfPartyContact }}</div>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary && $ticket->itinerary->vehicle)
                                                <div>{{ $ticket->itinerary->vehicle->make }} {{ $ticket->itinerary->vehicle->model }}</div>
                                                <div class="text-gray-500 text-xs">Plate: {{ $ticket->itinerary->vehicle->plate_number }}</div>
                                            @else
                                                <span class="text-gray-500">No vehicle</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary)
                                                {{ $ticket->itinerary->destination }}
                                            @else
                                                <span class="text-gray-500">No destination</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary && $ticket->itinerary->date_from)
                                                @php
                                                    $dateFrom = \Carbon\Carbon::parse($ticket->itinerary->date_from);
                                                    $dateTo = $ticket->itinerary->date_to ? \Carbon\Carbon::parse($ticket->itinerary->date_to) : null;
                                                    
                                                    if ($dateTo && $dateFrom->format('Y-m-d') !== $dateTo->format('Y-m-d')) {
                                                        // Different dates - show range
                                                        if ($dateFrom->format('Y-m') === $dateTo->format('Y-m')) {
                                                            // Same month: Jan 1-2, 2026
                                                            echo $dateFrom->format('M j') . '-' . $dateTo->format('j, Y');
                                                        } elseif ($dateFrom->format('Y') === $dateTo->format('Y')) {
                                                            // Same year, different months: Jan 31 - Feb 1, 2026
                                                            echo $dateFrom->format('M j') . ' - ' . $dateTo->format('M j, Y');
                                                        } else {
                                                            // Different years: Dec 31, 2026 - Jan 1, 2027
                                                            echo $dateFrom->format('M j, Y') . ' - ' . $dateTo->format('M j, Y');
                                                        }
                                                    } else {
                                                        // Same date or no end date - just show date_from
                                                        echo $dateFrom->format('M d, Y');
                                                    }
                                                @endphp
                                            @else
                                                <span class="text-gray-500">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                            <div class="action-buttons flex justify-center space-x-1">
                                                <button type="button" 
                                                        class="btn view-btn border inline-flex items-center justify-center"
                                                        title="View Trip Ticket Details"
                                                        onclick="showTripTicketDetails({{ $ticket->id }})">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <button type="button" 
                                                        class="btn edit-btn border inline-flex items-center justify-center"
                                                        title="Edit Trip Ticket"
                                                        onclick="showEditTripTicketModal({{ $ticket->id }})">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" 
                                                        class="btn archive-btn border inline-flex items-center justify-center"
                                                        title="Archive Trip"
                                                        onclick="archiveTrip({{ $ticket->id }})">
                                                    <i class="fas fa-archive"></i> Archive
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <!-- Empty state row -->
                                <tr>
                                    <td colspan="7" class="px-3 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No pending trip tickets</h3>
                                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new trip ticket.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination - Always shown -->
                <div class="flex justify-between items-center mt-4">
                    <div class="text-sm text-gray-600">
                        @if($pendingTripTickets->count() > 0)
                            Showing {{ $pendingTripTickets->firstItem() ?? 0 }} to {{ $pendingTripTickets->lastItem() ?? 0 }} of {{ $pendingTripTickets->total() }} trip tickets
                        @else
                            Showing 0 to 0 of 0 trip tickets
                        @endif
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($pendingTripTickets->count() > 0 && $pendingTripTickets->lastPage() > 1)
                            {{ $pendingTripTickets->appends(['search' => request('search'), 'tab' => 'pending'])->links() }}
                        @else
                            <nav>
                                <ul class="pagination">
                                    <li class="page-item disabled">
                                        <span class="page-link">Prev</span>
                                    </li>
                                    <li class="page-item active">
                                        <span class="page-link">1</span>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link">Next</span>
                                    </li>
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Approved Tab -->
            <div class="tab-content hidden" id="approved-tab">
                <!-- Table with headers always shown -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead style="background-color: #1e6031; color: white;">
                            <tr>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">No.</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Driver</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Head of the Party</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Vehicle</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Destination</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Date</th>
                                <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="approved-table-body">
                            @if($approvedTripTickets->count() > 0)
                                @foreach($approvedTripTickets as $index => $ticket)
                                    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-transparent' }} hover:bg-[#f0f8f0]">
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#004d00] font-medium">{{ $loop->iteration }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#004d00] font-medium">
                                            @if($ticket->itinerary && $ticket->itinerary->driver)
                                                <div>{{ $ticket->itinerary->driver->full_name }}</div>
                                                <div class="text-gray-500 text-xs">{{ $ticket->itinerary->driver->contact_num ?? 'N/A' }}</div>
                                            @else
                                                <span class="text-gray-500">No driver</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @php
                                                $headOfPartyContact = '-';
                                                // Try to get contact from itinerary's first travel order
                                                if ($ticket->itinerary) {
                                                    $travelOrders = $ticket->itinerary->getAllTravelOrders();
                                                    if ($travelOrders && $travelOrders->count() > 0) {
                                                        $firstTO = $travelOrders->first();
                                                        if ($firstTO && $firstTO->employee) {
                                                            // Try to get contact from employee first
                                                            $headOfPartyContact = $firstTO->employee->contact_num ?? '-';
                                                            // If employee has no contact, try from user
                                                            if ($headOfPartyContact === '-' && $firstTO->employee->user && $firstTO->employee->user->contact_num) {
                                                                $headOfPartyContact = $firstTO->employee->user->contact_num;
                                                            }
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <div>{{ $ticket->head_of_party ?? 'Not set' }}</div>
                                            <div class="text-gray-500 text-xs">{{ $headOfPartyContact }}</div>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary && $ticket->itinerary->vehicle)
                                                <div>{{ $ticket->itinerary->vehicle->make }} {{ $ticket->itinerary->vehicle->model }}</div>
                                                <div class="text-gray-500 text-xs">Plate: {{ $ticket->itinerary->vehicle->plate_number }}</div>
                                            @else
                                                <span class="text-gray-500">No vehicle</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary)
                                                {{ $ticket->itinerary->destination }}
                                            @else
                                                <span class="text-gray-500">No destination</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary && $ticket->itinerary->date_from)
                                                @php
                                                    $dateFrom = \Carbon\Carbon::parse($ticket->itinerary->date_from);
                                                    $dateTo = $ticket->itinerary->date_to ? \Carbon\Carbon::parse($ticket->itinerary->date_to) : null;
                                                    
                                                    if ($dateTo && $dateFrom->format('Y-m-d') !== $dateTo->format('Y-m-d')) {
                                                        // Different dates - show range
                                                        if ($dateFrom->format('Y-m') === $dateTo->format('Y-m')) {
                                                            // Same month: Jan 1-2, 2026
                                                            echo $dateFrom->format('M j') . '-' . $dateTo->format('j, Y');
                                                        } elseif ($dateFrom->format('Y') === $dateTo->format('Y')) {
                                                            // Same year, different months: Jan 31 - Feb 1, 2026
                                                            echo $dateFrom->format('M j') . ' - ' . $dateTo->format('M j, Y');
                                                        } else {
                                                            // Different years: Dec 31, 2026 - Jan 1, 2027
                                                            echo $dateFrom->format('M j, Y') . ' - ' . $dateTo->format('M j, Y');
                                                        }
                                                    } else {
                                                        // Same date or no end date - just show date_from
                                                        echo $dateFrom->format('M d, Y');
                                                    }
                                                @endphp
                                            @else
                                                <span class="text-gray-500">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                            <div class="action-buttons flex justify-center space-x-1">
                                                <button type="button" 
                                                        class="btn view-btn border inline-flex items-center justify-center"
                                                        title="View Trip Ticket Details"
                                                        onclick="showTripTicketDetails({{ $ticket->id }})">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <button type="button" 
                                                        class="btn edit-btn border inline-flex items-center justify-center"
                                                        title="Edit Trip Ticket"
                                                        onclick="showEditTripTicketModal({{ $ticket->id }})">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                @php
                                                    // Check if date_from is today
                                                    $isToday = false;
                                                    if ($ticket->itinerary && $ticket->itinerary->date_from) {
                                                        $dateFrom = \Carbon\Carbon::parse($ticket->itinerary->date_from);
                                                        $today = \Carbon\Carbon::today();
                                                        $isToday = $dateFrom->isSameDay($today);
                                                    }
                                                @endphp
                                                @if($isToday)
                                                    <button type="button" 
                                                            class="btn btn-success inline-flex items-center justify-center"
                                                            title="Start Trip"
                                                            onclick="startTrip({{ $ticket->id }})"
                                                            style="background-color: #1e6031; color: white;">
                                                        <i class="fas fa-play"></i> Start
                                                    </button>
                                                @else
                                                    <button type="button" 
                                                            class="btn btn-secondary inline-flex items-center justify-center cursor-not-allowed opacity-50"
                                                            title="Start trip available only on departure date"
                                                            disabled
                                                            style="background-color: #6c757d; color: white; cursor: not-allowed; opacity: 0.5;">
                                                        <i class="fas fa-play"></i> Start
                                                    </button>
                                                @endif
                                                <button type="button" 
                                                        class="btn btn-danger inline-flex items-center justify-center"
                                                        title="Cancel Trip Ticket"
                                                        onclick="showCancelModal({{ $ticket->id }}, '{{ $ticket->ticket_number }}')"
                                                        style="background-color: #dc3545; color: white;">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <!-- Empty state row -->
                                <tr>
                                    <td colspan="7" class="px-3 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No approved trip tickets</h3>
                                        <p class="mt-1 text-sm text-gray-500">There are currently no approved trip tickets.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination - Always shown -->
                <div class="flex justify-between items-center mt-4">
                    <div class="text-sm text-gray-600">
                        @if($onGoingTripTickets->count() > 0)
                            Showing {{ $approvedTripTickets->firstItem() ?? 0 }} to {{ $approvedTripTickets->lastItem() ?? 0 }} of {{ $approvedTripTickets->total() }} trip tickets
                        @else
                            Showing 0 to 0 of 0 trip tickets
                        @endif
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($onGoingTripTickets->count() > 0 && $onGoingTripTickets->lastPage() > 1)
                            {{ $approvedTripTickets->appends(['search' => request('search'), 'tab' => 'approved'])->links() }}
                        @else
                            <nav>
                                <ul class="pagination">
                                    <li class="page-item disabled">
                                        <span class="page-link">Prev</span>
                                    </li>
                                    <li class="page-item active">
                                        <span class="page-link">1</span>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link">Next</span>
                                    </li>
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Completed Tab -->
            <div class="tab-content hidden" id="completed-tab">
                <!-- Table with headers always shown -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead style="background-color: #1e6031; color: white;">
                            <tr>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">No.</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Driver</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Head of the Party</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Vehicle</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Destination</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Date</th>
                                <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="completed-table-body">
                            @if($completedTripTickets->count() > 0)
                                @foreach($completedTripTickets as $index => $ticket)
                                    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-transparent' }} hover:bg-[#f0f8f0]">
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#004d00] font-medium">{{ $loop->iteration }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#004d00] font-medium">
                                            @if($ticket->itinerary && $ticket->itinerary->driver)
                                                <div>{{ $ticket->itinerary->driver->full_name }}</div>
                                                <div class="text-gray-500 text-xs">{{ $ticket->itinerary->driver->contact_num ?? 'N/A' }}</div>
                                            @else
                                                <span class="text-gray-500">No driver</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @php
                                                $headOfPartyContact = '-';
                                                // Try to get contact from itinerary's first travel order
                                                if ($ticket->itinerary) {
                                                    $travelOrders = $ticket->itinerary->getAllTravelOrders();
                                                    if ($travelOrders && $travelOrders->count() > 0) {
                                                        $firstTO = $travelOrders->first();
                                                        if ($firstTO && $firstTO->employee) {
                                                            // Try to get contact from employee first
                                                            $headOfPartyContact = $firstTO->employee->contact_num ?? '-';
                                                            // If employee has no contact, try from user
                                                            if ($headOfPartyContact === '-' && $firstTO->employee->user && $firstTO->employee->user->contact_num) {
                                                                $headOfPartyContact = $firstTO->employee->user->contact_num;
                                                            }
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <div>{{ $ticket->head_of_party ?? 'Not set' }}</div>
                                            <div class="text-gray-500 text-xs">{{ $headOfPartyContact }}</div>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary && $ticket->itinerary->vehicle)
                                                <div>{{ $ticket->itinerary->vehicle->make }} {{ $ticket->itinerary->vehicle->model }}</div>
                                                <div class="text-gray-500 text-xs">Plate: {{ $ticket->itinerary->vehicle->plate_number }}</div>
                                            @else
                                                <span class="text-gray-500">No vehicle</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary)
                                                {{ $ticket->itinerary->destination }}
                                            @else
                                                <span class="text-gray-500">No destination</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary && $ticket->itinerary->date_from)
                                                @php
                                                    $dateFrom = \Carbon\Carbon::parse($ticket->itinerary->date_from);
                                                    $dateTo = $ticket->itinerary->date_to ? \Carbon\Carbon::parse($ticket->itinerary->date_to) : null;
                                                    
                                                    if ($dateTo && $dateFrom->format('Y-m-d') !== $dateTo->format('Y-m-d')) {
                                                        // Different dates - show range
                                                        if ($dateFrom->format('Y-m') === $dateTo->format('Y-m')) {
                                                            // Same month: Jan 1-2, 2026
                                                            echo $dateFrom->format('M j') . '-' . $dateTo->format('j, Y');
                                                        } elseif ($dateFrom->format('Y') === $dateTo->format('Y')) {
                                                            // Same year, different months: Jan 31 - Feb 1, 2026
                                                            echo $dateFrom->format('M j') . ' - ' . $dateTo->format('M j, Y');
                                                        } else {
                                                            // Different years: Dec 31, 2026 - Jan 1, 2027
                                                            echo $dateFrom->format('M j, Y') . ' - ' . $dateTo->format('M j, Y');
                                                        }
                                                    } else {
                                                        // Same date or no end date - just show date_from
                                                        echo $dateFrom->format('M d, Y');
                                                    }
                                                @endphp
                                            @else
                                                <span class="text-gray-500">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                            <div class="action-buttons flex justify-center space-x-1">
                                                {{-- View Trip Ticket Details --}}
                                                <button type="button" 
                                                        class="btn view-btn border inline-flex items-center justify-center"
                                                        title="View Trip Ticket Details"
                                                        onclick="showTripTicketDetails({{ $ticket->id }})">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                
                                                {{-- Completion File Actions --}}
                                                @if($ticket->completion_file)
                                                    @php
                                                        // Use completion_file_link accessor
                                                        $driveLink = $ticket->completion_file_link;
                                                    @endphp
                                                    
                                                    {{-- View File Button --}}
                                                    <button type="button"
                                                       onclick="viewCompletionFile('{{ $driveLink }}')"
                                                       class="btn view-btn border inline-flex items-center justify-center"
                                                       title="View Completion File">
                                                        <i class="fas fa-file-alt"></i> View File
                                                    </button>
                                                    
                                                    {{-- Edit File Button (Replace/Update) --}}
                                                    <button type="button" 
                                                            class="btn edit-btn border inline-flex items-center justify-center"
                                                            title="Update Completion File"
                                                            onclick="showUploadFileModal({{ $ticket->id }})">
                                                        <i class="fas fa-edit"></i> Edit File
                                                    </button>
                                                @else
                                                    {{-- Add File Button (if no file yet) --}}
                                                    <button type="button" 
                                                            class="btn edit-btn border inline-flex items-center justify-center"
                                                            title="Add Completion File"
                                                            onclick="showUploadFileModal({{ $ticket->id }})">
                                                        <i class="fas fa-upload"></i> Add File
                                                    </button>
                                                @endif
                                                
                                                {{-- Archive Trip Button --}}
                                                <button type="button" 
                                                        class="btn archive-btn border inline-flex items-center justify-center"
                                                        title="Archive Trip"
                                                        onclick="archiveTrip({{ $ticket->id }})">
                                                    <i class="fas fa-archive"></i> Archive
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <!-- Empty state row -->
                                <tr>
                                    <td colspan="7" class="px-3 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No completed trip tickets</h3>
                                        <p class="mt-1 text-sm text-gray-500">There are currently no completed trip tickets.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination - Always shown -->
                <div class="flex justify-between items-center mt-4">
                    <div class="text-sm text-gray-600">
                        @if($completedTripTickets->count() > 0)
                            Showing {{ $completedTripTickets->firstItem() ?? 0 }} to {{ $completedTripTickets->lastItem() ?? 0 }} of {{ $completedTripTickets->total() }} trip tickets
                        @else
                            Showing 0 to 0 of 0 trip tickets
                        @endif
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($completedTripTickets->count() > 0 && $completedTripTickets->lastPage() > 1)
                            {{ $completedTripTickets->appends(['search' => request('search'), 'tab' => 'completed'])->links() }}
                        @else
                            <nav>
                                <ul class="pagination">
                                    <li class="page-item disabled">
                                        <span class="page-link">Prev</span>
                                    </li>
                                    <li class="page-item active">
                                        <span class="page-link">1</span>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link">Next</span>
                                    </li>
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Cancelled Tab -->
            <div class="tab-content hidden" id="cancelled-tab">
                <!-- Table with headers always shown -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead style="background-color: #1e6031; color: white;">
                            <tr>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">No.</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Driver</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Head of the Party</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Vehicle</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Destination</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Date</th>
                                <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cancelled-table-body">
                            @if($cancelledTripTickets->count() > 0)
                                @foreach($cancelledTripTickets as $index => $ticket)
                                    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-transparent' }} hover:bg-[#f0f8f0]">
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#004d00] font-medium">{{ $loop->iteration }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#004d00] font-medium">
                                            @if($ticket->itinerary && $ticket->itinerary->driver)
                                                <div>{{ $ticket->itinerary->driver->full_name }}</div>
                                                <div class="text-gray-500 text-xs">{{ $ticket->itinerary->driver->contact_num ?? 'N/A' }}</div>
                                            @else
                                                <span class="text-gray-500">No driver</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @php
                                                $headOfPartyContact = '-';
                                                // Try to get contact from itinerary's first travel order
                                                if ($ticket->itinerary) {
                                                    $travelOrders = $ticket->itinerary->getAllTravelOrders();
                                                    if ($travelOrders && $travelOrders->count() > 0) {
                                                        $firstTO = $travelOrders->first();
                                                        if ($firstTO && $firstTO->employee) {
                                                            // Try to get contact from employee first
                                                            $headOfPartyContact = $firstTO->employee->contact_num ?? '-';
                                                            // If employee has no contact, try from user
                                                            if ($headOfPartyContact === '-' && $firstTO->employee->user && $firstTO->employee->user->contact_num) {
                                                                $headOfPartyContact = $firstTO->employee->user->contact_num;
                                                            }
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <div>{{ $ticket->head_of_party ?? 'Not set' }}</div>
                                            <div class="text-gray-500 text-xs">{{ $headOfPartyContact }}</div>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary && $ticket->itinerary->vehicle)
                                                <div>{{ $ticket->itinerary->vehicle->make }} {{ $ticket->itinerary->vehicle->model }}</div>
                                                <div class="text-gray-500 text-xs">Plate: {{ $ticket->itinerary->vehicle->plate_number }}</div>
                                            @else
                                                <span class="text-gray-500">No vehicle</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary)
                                                {{ $ticket->itinerary->destination }}
                                            @else
                                                <span class="text-gray-500">No destination</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">
                                            @if($ticket->itinerary && $ticket->itinerary->date_from)
                                                @php
                                                    $dateFrom = \Carbon\Carbon::parse($ticket->itinerary->date_from);
                                                    $dateTo = $ticket->itinerary->date_to ? \Carbon\Carbon::parse($ticket->itinerary->date_to) : null;
                                                    
                                                    if ($dateTo && $dateFrom->format('Y-m-d') !== $dateTo->format('Y-m-d')) {
                                                        // Different dates - show range
                                                        if ($dateFrom->format('Y-m') === $dateTo->format('Y-m')) {
                                                            // Same month: Jan 1-2, 2026
                                                            echo $dateFrom->format('M j') . '-' . $dateTo->format('j, Y');
                                                        } elseif ($dateFrom->format('Y') === $dateTo->format('Y')) {
                                                            // Same year, different months: Jan 31 - Feb 1, 2026
                                                            echo $dateFrom->format('M j') . ' - ' . $dateTo->format('M j, Y');
                                                        } else {
                                                            // Different years: Dec 31, 2026 - Jan 1, 2027
                                                            echo $dateFrom->format('M j, Y') . ' - ' . $dateTo->format('M j, Y');
                                                        }
                                                    } else {
                                                        // Same date or no end date - just show date_from
                                                        echo $dateFrom->format('M d, Y');
                                                    }
                                                @endphp
                                            @else
                                                <span class="text-gray-500">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                            <div class="action-buttons flex justify-center space-x-1">
                                                <button type="button" 
                                                        class="btn view-btn border inline-flex items-center justify-center"
                                                        title="View Trip Ticket Details"
                                                        onclick="showTripTicketDetails({{ $ticket->id }})">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <!-- Empty state row -->
                                <tr>
                                    <td colspan="7" class="px-3 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No cancelled trip tickets</h3>
                                        <p class="mt-1 text-sm text-gray-500">There are currently no cancelled trip tickets.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination - Always shown -->
                <div class="flex justify-between items-center mt-4">
                    <div class="text-sm text-gray-600">
                        @if($cancelledTripTickets->count() > 0)
                            Showing {{ $cancelledTripTickets->firstItem() ?? 0 }} to {{ $cancelledTripTickets->lastItem() ?? 0 }} of {{ $cancelledTripTickets->total() }} trip tickets
                        @else
                            Showing 0 to 0 of 0 trip tickets
                        @endif
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($cancelledTripTickets->count() > 0 && $cancelledTripTickets->lastPage() > 1)
                            {{ $cancelledTripTickets->appends(['search' => request('search'), 'tab' => 'cancelled'])->links() }}
                        @else
                            <nav>
                                <ul class="pagination">
                                    <li class="page-item disabled">
                                        <span class="page-link">Prev</span>
                                    </li>
                                    <li class="page-item active">
                                        <span class="page-link">1</span>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link">Next</span>
                                    </li>
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>
            </div>
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

.action-buttons .end-btn {
    color: #fd7e14 !important;
    border: 1px solid #fd7e14 !important;
    background-color: transparent !important;
}

.action-buttons .end-btn:hover {
    background-color: #fd7e14 !important;
    color: #fff !important;
    border-color: #fd7e14 !important;
}

.action-buttons .archive-btn {
    color: #dc3545 !important;
    border: 1px solid #dc3545 !important;
    background-color: transparent !important;
}

.action-buttons .archive-btn:hover {
    background-color: #dc3545 !important;
    color: #fff !important;
    border-color: #dc3545 !important;
}

.action-buttons .pdf-btn {
    color: #dc3545 !important;
    border: 1px solid #dc3545 !important;
    background-color: transparent !important;
}

.action-buttons .pdf-btn:hover {
    background-color: #dc3545 !important;
    color: #fff !important;
    border-color: #dc3545 !important;
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

<!-- Cancellation Modal -->
<div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 transition-all duration-200 hidden">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full transform transition-all duration-200 scale-95 opacity-0 border-t-4 border-red-600" id="cancelModalContent">
        <!-- Modal Header -->
        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 50%, #dc3545 100%);">
            <div class="flex items-center gap-3">
                <!-- Icon Circle -->
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-lg">
                    <!-- Red X icon -->
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white">Cancel Trip Ticket?</h3>
            </div>
        </div>
        
        <!-- Modal Content -->
        <form id="cancelForm" method="POST">
            @csrf
            <input type="hidden" id="cancelTicketId" name="ticket_id">
            
            <div class="px-6 py-4">
                <div class="text-sm text-gray-800">
                    <p class="text-base font-semibold text-gray-900 mb-3">Please provide a reason for cancelling this trip ticket:</p>
                    
                    <textarea 
                        id="cancellation_reason" 
                        name="cancellation_reason" 
                        required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200" 
                        rows="4" 
                        placeholder="Please explain why you are cancelling this trip ticket..." 
                        style="min-height: 100px; resize: vertical;"></textarea>
                    <p class="text-xs text-gray-500 mt-2"><i class="fas fa-exclamation-triangle"></i> This can't be undone.</p>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2">
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <i class="fas fa-times-circle mr-1"></i> Yes, Cancel
                </button>
                <button type="button" onclick="closeCancelModal()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <i class="fas fa-check-circle mr-1"></i> No, Keep it
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Trip Ticket Details -->
<div id="tripTicketModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 transition-all duration-200">
  <div class="bg-white rounded-lg shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0 flex flex-col" id="tripTicketModalContent">
    
    <!-- Modal Header - DSU Themed -->
    <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between flex-shrink-0" style="background: linear-gradient(135deg, #1e6031 0%, #2d7a3e 50%, #1e6031 100%);">
      <div class="flex items-center gap-3">
        <!-- Icon Circle -->
        <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
          <!-- Document Icon -->
          <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <h3 class="text-lg font-bold text-white">Trip Ticket Details</h3>
      </div>
    </div>

    <!-- Content: details on the left, live map on the right -->
    <div class="flex flex-col md:flex-row flex-1 min-h-0 overflow-hidden">
      <div id="tripTicketDetailsColumn" class="px-6 py-4 md:w-[380px] md:flex-shrink-0 overflow-y-auto min-h-0">
        <div id="tripTicketDetailsContent" class="text-sm text-gray-800">
          <!-- Trip ticket details will be loaded here -->
        </div>
      </div>
      <div id="tripTicketMapColumn" class="relative flex-1 min-h-[320px] md:min-h-0 border-t md:border-t-0 md:border-l border-gray-200">
        <div id="tripTicketMapPanel" class="absolute inset-0">
          <!-- Live map / route will be rendered here -->
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2 flex-shrink-0">
      <button id="viewExcelButton" onclick="viewExcel()" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5 hidden">
        <i class="fas fa-file-excel mr-1"></i> View Excel
      </button>
      <button onclick="closeTripTicketModal()" class="px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
        Close
      </button>
    </div>
  </div>
</div>

<!-- Upload Completion File Modal -->
<div class="modal fade" id="uploadFileModal" tabindex="-1" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header d-flex align-items-center" style="background-color:#1e6031; color:white;">
                <h5 class="modal-title fw-bold mb-0 flex-grow-1" id="uploadFileModalLabel">Upload Completion File</h5>
            </div>
            <form id="uploadFileForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body px-4 py-3">
                    <input type="hidden" id="uploadTicketId" name="ticket_id">
                    
                    <div class="mb-4">
                        <label for="completion_file" class="form-label fw-semibold text-gray-700">
                            Select File (PDF, JPG, PNG - Max 5MB)
                        </label>
                        <input type="file" 
                               id="completion_file" 
                               name="completion_file" 
                               accept=".pdf,.jpg,.jpeg,.png,image/*"
                               class="form-control form-control-sm py-1 px-2"
                               style="font-size: 0.75rem; height: 30px; border: 1px solid #1e6031 !important; border-radius: 0.25rem !important;"
                               required>
                        <small class="form-text text-muted mt-1">Maximum file size: 5MB</small>
                    </div>
                </div>
                <div class="modal-footer py-2 justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm" style="background-color: #1e6031; color: white; border: none;">
                        <i class="fas fa-upload me-1"></i> Upload File
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Trip Ticket Modal -->
<div class="modal fade" id="createTripTicketModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content rounded-3 shadow">
      
      <!-- Header -->
      <div class="modal-header d-flex align-items-center" style="background-color:#1e6031; color:white;">
        <h5 class="modal-title fw-bold mb-0 flex-grow-1">Create Trip Ticket</h5>
      </div>
      
      <!-- Form -->
      <form id="createTripTicketForm" action="{{ route('trip-tickets.store') }}" method="POST">
        @csrf
        <div class="modal-body px-3 py-2">
          
          <!-- Error Display Area -->
          <div id="createTripTicketErrors" class="alert alert-danger alert-dismissible fade show mb-2" role="alert" style="display: none; font-size: 0.85rem;">
            <h6 class="fw-bold mb-2">⚠️ Trip Ticket Number Already Exists</h6>
            <p class="mb-0">Please use a different number.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          
          <!-- Itinerary Selection -->
          <div class="mb-2">
            <label for="modal_itinerary_id" class="form-label fw-semibold text-success mb-1" style="font-size: 0.9rem;">Itinerary <span class="text-danger">*</span></label>
            <select class="form-select rounded" id="modal_itinerary_id" name="itinerary_id" onchange="updateModalItineraryDetails()" style="height: 38px; font-size: 0.95rem; font-weight: 500;" required>
              <option value="" style="font-size: 0.9rem;">-- Select Itinerary --</option>
              @foreach($itineraries as $itinerary)
                <option value="{{ $itinerary->id }}" 
                    data-driver="{{ $itinerary->driver?->first_name. ' ' . $itinerary->driver?->last_name }}"
                    data-contact-num="{{ $itinerary->driver?->contact_num ?? 'N/A' }}"
                    data-vehicle="{{ $itinerary->vehicle?->model ?? 'N/A' }}"
                    data-plate-number="{{ $itinerary->vehicle?->plate_number ?? '' }}"
                    data-seating-capacity="{{ $itinerary->vehicle?->seating_capacity ?? 0 }}"
                    data-destination="{{ $itinerary->destination ?? 'N/A' }}"
                    data-purpose="{{ $itinerary->purpose ?? 'N/A' }}"
                    data-date-from="{{ $itinerary->date_from instanceof \DateTimeInterface ? $itinerary->date_from->format('Y-m-d') : $itinerary->date_from }}"
                    data-date-to="{{ $itinerary->date_to instanceof \DateTimeInterface ? $itinerary->date_to->format('Y-m-d') : $itinerary->date_to }}">
                  {{ $itinerary->driver?->first_name. ' ' . $itinerary->driver?->last_name }} - {{ $itinerary->destination }}
                </option>
              @endforeach
            </select>
          </div>
          
          <!-- Itinerary Details Preview -->
          <div class="row g-2 mb-2">
            <div class="col-6">
              <input type="text" id="modal_date-preview" class="form-control form-control-sm rounded" placeholder="Date" readonly style="height: 30px; font-size: 0.75rem; background-color: #f8f9fa;">
            </div>
            <div class="col-6">
              <input type="text" id="modal_vehicle-preview" class="form-control form-control-sm rounded" placeholder="Vehicle" readonly style="height: 30px; font-size: 0.75rem; background-color: #f8f9fa;">
            </div>
          </div>
          
          <div class="mb-2">
            <input type="text" id="modal_purpose-preview" class="form-control form-control-sm rounded" placeholder="Purpose" readonly style="height: 30px; font-size: 0.75rem; background-color: #f8f9fa;">
          </div>
          
          <!-- Trip Ticket Number -->
          <div class="mb-2">
            <label for="modal_ticket_number" class="form-label small fw-semibold text-success mb-1" style="font-size: 0.85rem;">Trip Ticket Number <span class="text-danger">*</span></label>
            <input type="text" name="ticket_number" id="modal_ticket_number" class="form-control form-control-sm rounded" placeholder="Enter trip ticket number (e.g., TT-000001)" style="height: 30px; font-size: 0.85rem;"required>
          </div>
                    
          <!-- Add Additional Travel Order -->
          <div class="mb-2">
            <label for="modal_additional_travel_order_select" class="form-label fw-semibold text-success mb-1" style="font-size: 0.9rem;">Add More Travel Orders</label>
            <select class="form-select rounded" id="modal_additional_travel_order_select" onchange="handleModalAddTravelOrderClick()" disabled style="height: 38px; font-size: 0.9rem; font-weight: 500;">
              <option value="" disabled selected>-- Select an itinerary first --</option>
              @foreach($travelOrdersWithoutTripTicket as $travelOrder)
                @php
                    // Now $travelOrder is an array from the controller
                    $dateFrom = $travelOrder['date_from'] ?? null;
                    $dateTo = $travelOrder['date_to'] ?? null;
                    $departureTime = isset($travelOrder['departure_time']) ? \Carbon\Carbon::parse($travelOrder['departure_time'])->format('g:i A') : null;
                    $employeeName = $travelOrder['employee_name'] ?? 'N/A';
                    $destination = $travelOrder['destination'] ?? 'No destination';
                    $itineraryId = $travelOrder['itinerary_id'] ?? '';
                    
                    // Format dates with proper formatting using PHP
                    $displayText = '';
                    if ($dateFrom) {
                        $fromDate = new \DateTime($dateFrom);
                        $toDate = $dateTo ? new \DateTime($dateTo) : $fromDate;
                        
                        $fromMonth = $fromDate->format('M');
                        $fromDay = (int)$fromDate->format('d');
                        $fromYear = (int)$fromDate->format('Y');
                        
                        $toMonth = $toDate->format('M');
                        $toDay = (int)$toDate->format('d');
                        $toYear = (int)$toDate->format('Y');
                        
                        // Check if same date
                        if ($fromDate == $toDate) {
                            // Same date: "Jan 31, 2026"
                            $dateDisplay = "$fromMonth $fromDay, $fromYear";
                        } else if ($fromMonth === $toMonth && $fromYear === $toYear) {
                            // Same month and year: "Jan 1-2, 2026"
                            $dateDisplay = "$fromMonth $fromDay-$toDay, $fromYear";
                        } else if ($fromYear === $toYear) {
                            // Same year, different months: "Jan 31 - Feb 1, 2026"
                            $dateDisplay = "$fromMonth $fromDay - $toMonth $toDay, $fromYear";
                        } else {
                            // Different years: "Dec 31, 2026 - Jan 1, 2027"
                            $dateDisplay = "$fromMonth $fromDay, $fromYear - $toMonth $toDay, $toYear";
                        }
                        
                        $timeDisplay = $departureTime ? "($departureTime)" : '';
                        $displayText = "$dateDisplay $timeDisplay - $employeeName ($destination)";
                    } else {
                        $displayText = "#{$travelOrder['id']} - $employeeName ($destination)";
                    }
                @endphp
                <option value="{{ $travelOrder['id'] }}"
                    data-itinerary-id="{{ $itineraryId }}"
                    data-date-from="{{ $dateFrom }}"
                    data-date-to="{{ $dateTo }}"
                    data-employee="{{ $employeeName }}"
                    data-destination="{{ $destination }}"
                    style="font-size: 0.85rem;">
                  {{ $displayText }}
                </option>
              @endforeach
            </select>
          </div>
                    
          <!-- Travel Orders from Itinerary Section -->
          <div class="mb-2">
            <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.75rem;">Travel Orders (from Itinerary)</label>
            <div id="modal-travel-orders-list" class="border rounded p-2 bg-light mb-2" style="min-height: 40px; font-size: 0.75rem;">
              <p class="text-muted mb-0 fst-italic" style="font-size: 0.75rem;">Select an itinerary to load travel orders</p>
            </div>
            <small class="text-muted" style="font-size: 0.7rem;">Total: <span id="modal-total-travel-orders">0</span> travel order(s)</small>
          </div>
                    
          <!-- Passengers Section -->
          <div class="mb-2">
            <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.85rem;">Passengers (from selected Travel Orders)</label>
            <div id="modal-passenger-list" class="border rounded p-2 bg-light">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-medium text-muted">Selected Passengers:</span>
                <div class="d-flex align-items-center gap-2">
                  <span class="small text-muted">Head of Party:</span>
                  <span id="modal_current-head" class="fw-bold text-success small">None selected</span>
                </div>
              </div>
              <ul id="modal_passenger-items" class="list-unstyled mb-0 small" style="font-size: 0.75rem; max-height: 150px; overflow-y: auto;"></ul>
              <div id="modal_passenger_hidden_inputs_container"></div>
              <input type="hidden" name="head_of_party" id="modal_head_of_party" value="">
            </div>
          </div>
          
        </div>
        
        <!-- Footer -->
        <div class="modal-footer py-1 justify-content-end">
          <button type="button" class="btn btn-sm btn-outline-secondary me-1" style="font-size: 0.75rem; height: 30px;" onclick="closeCreateTripTicketModal()">Cancel</button>
          <button type="button" id="saveTripTicketBtn" class="btn btn-sm btn-success" style="font-size: 0.75rem; height: 30px; background-color: #1e6031; border-color: #1e6031;">Create Trip Ticket</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Trip Ticket Modal -->
<div class="modal fade" id="editTripTicketModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content rounded-3 shadow">
      
      <!-- Header -->
      <div class="modal-header d-flex align-items-center" style="background-color:#1e6031; color:white;">
        <h5 class="modal-title fw-bold mb-0 flex-grow-1">Edit Trip Ticket</h5>
      </div>
      
      <!-- Form -->
      <form id="editTripTicketForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body px-3 py-2">
          
          <!-- Itinerary Information (Read-only) -->
          <div class="mb-2">
            <label class="form-label fw-semibold text-success mb-1" style="font-size: 0.9rem;">Itinerary Information</label>
            <input type="hidden" name="itinerary_id" id="edit_itinerary_id_hidden">
            
            <div class="row g-2 mb-2">
              <div class="col-6">
                <input type="text" id="edit_driver-preview" class="form-control form-control-sm rounded" placeholder="Driver" readonly style="height: 30px; font-size: 0.75rem; background-color: #f8f9fa;">
              </div>
              <div class="col-6">
                <input type="text" id="edit_date-preview" class="form-control form-control-sm rounded" placeholder="Date" readonly style="height: 30px; font-size: 0.75rem; background-color: #f8f9fa;">
              </div>
            </div>
            
            <div class="row g-2 mb-2">
              <div class="col-6">
                <input type="text" id="edit_destination-preview" class="form-control form-control-sm rounded" placeholder="Destination" readonly style="height: 30px; font-size: 0.75rem; background-color: #f8f9fa;">
              </div>
              <div class="col-6">
                <input type="text" id="edit_vehicle-preview" class="form-control form-control-sm rounded" placeholder="Vehicle" readonly style="height: 30px; font-size: 0.75rem; background-color: #f8f9fa;">
              </div>
            </div>
            
            <div class="mb-2">
              <input type="text" id="edit_purpose-preview" class="form-control form-control-sm rounded" placeholder="Purpose" readonly style="height: 30px; font-size: 0.75rem; background-color: #f8f9fa;">
            </div>
          </div>
          
          <!-- Trip Ticket Number -->
          <div class="mb-2">
            <label for="edit_ticket_number" class="form-label fw-semibold text-success mb-1" style="font-size: 0.9rem;">Trip Ticket Number <span class="text-danger">*</span></label>
            <input type="text" name="ticket_number" id="edit_ticket_number" class="form-control form-control-sm rounded" placeholder="Enter trip ticket number (e.g., TT-000001)" style="height: 30px; font-size: 0.85rem;" required>
            <small class="text-muted" style="font-size: 0.7rem;">Enter a unique trip ticket number</small>
          </div>
          
          <!-- Add Additional Travel Order -->
          <div class="mb-2">
            <label for="edit_add_travel_order_select" class="form-label fw-semibold text-success mb-1" style="font-size: 0.9rem;">Add More Travel Orders</label>
            <select class="form-select rounded" id="edit_add_travel_order_select" onchange="editAddSelectedTravelOrder()" style="height: 38px; font-size: 0.9rem; font-weight: 500;">
              <option value="" disabled selected style="font-size: 0.9rem;">-- Select to Add --</option>
            </select>
            <small class="text-muted" style="font-size: 0.7rem;">Only shows travel orders without itineraries that match your date range</small>
          </div>
                    
          <!-- Travel Orders from Itinerary Section -->
          <div class="mb-2">
            <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.75rem;">Travel Orders (from Itinerary)</label>
            <div id="edit-selected-travel-orders-list" class="border rounded p-2 bg-light mb-2" style="min-height: 40px; font-size: 0.75rem;">
              <p class="text-muted mb-0 fst-italic" style="font-size: 0.75rem;">No travel orders selected</p>
            </div>
            <small class="text-muted" style="font-size: 0.7rem;">Total: <span id="edit-total-travel-orders">0</span> travel order(s)</small>
          </div>
                    
          <!-- Passengers Section -->
          <div class="mb-2">
            <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.85rem;">Passengers (Auto-populated)</label>
            <div id="edit-passenger-list" class="border rounded p-2 bg-light">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-medium text-muted">Selected Passengers:</span>
                <div class="d-flex align-items-center gap-2">
                  <span class="small text-muted">Head:</span>
                  <span id="edit_current-head" class="fw-bold text-success small">None selected</span>
                </div>
              </div>
              <ul id="edit_passenger-items" class="list-unstyled mb-0 small" style="font-size: 0.75rem; max-height: 150px; overflow-y: auto;"></ul>
              <input type="hidden" name="passenger_names[]" id="edit_passenger_hidden_inputs">
              <input type="hidden" name="head_of_party" id="edit_head_of_party" value="">
            </div>
            <small class="text-muted" style="font-size: 0.7rem;">Total: <span id="edit-total-passengers">0</span> passenger(s)</small>
          </div>
          
        </div>
        
        <!-- Footer -->
        <div class="modal-footer py-1 justify-content-end">
          <button type="button" class="btn btn-sm btn-outline-secondary me-1" style="font-size: 0.75rem; height: 30px;" onclick="closeEditTripTicketModal()">Cancel</button>
          <button type="submit" class="btn btn-sm btn-success" style="font-size: 0.75rem; height: 30px; background-color: #1e6031; border-color: #1e6031;">
            <i class="fas fa-save"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script src="{{ asset('js/edit-trip-ticket-modal.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Get the current tab from URL parameter, default to 'pending'
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('tab') || 'pending';
    
    // Activate the correct tab based on URL parameter
    if(tabContents.length > 0) {
        // Hide all tab contents first
        tabContents.forEach(content => {
            content.classList.add('hidden');
        });
        
        // Remove active class from all buttons
        tabButtons.forEach(btn => {
            btn.classList.remove('border-[#1e6031]', 'text-[#1e6031]');
            btn.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Show the selected tab content
        const activeTabContent = document.getElementById(currentTab + '-tab');
        if (activeTabContent) {
            activeTabContent.classList.remove('hidden');
            console.log('Activated tab:', currentTab);
        } else {
            console.error('Tab content not found for:', currentTab);
            // Fallback to pending tab if specified tab not found
            const pendingTab = document.getElementById('pending-tab');
            if (pendingTab) {
                pendingTab.classList.remove('hidden');
            }
        }
        
        // Find and activate the corresponding button
        const activeButton = document.querySelector(`.tab-button[data-tab="${currentTab}"]`);
        if (activeButton) {
            activeButton.classList.remove('border-transparent', 'text-gray-500');
            activeButton.classList.add('border-[#1e6031]', 'text-[#1e6031]');
        }
    }
    
    tabButtons.forEach((button, index) => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            console.log('Tab clicked:', tabName);
            
            // Update URL with the new tab parameter without reloading
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('tab', tabName);
            window.history.pushState({ tab: tabName }, '', newUrl);
            
            // Remove active class from all buttons
            tabButtons.forEach(btn => {
                btn.classList.remove('border-[#1e6031]', 'text-[#1e6031]');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Add active class to clicked button
            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('border-[#1e6031]', 'text-[#1e6031]');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.remove('hidden');
        });
    });
    
    // Handle search form submission
    const searchForm = document.getElementById('searchForm');
    if(searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = document.querySelector('[name="search"]');
            if(searchInput && searchInput.value.trim() === '') {
                window.location.href = '{{ route("trip-tickets.index") }}';
                e.preventDefault();
            }
        });
    }
});

// Function to start a trip
async function startTrip(ticketId) {
    const result = await Swal.fire({
        title: 'Start Trip?',
        text: "Are you sure you want to start this trip?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e6031',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Start Trip!',
        cancelButtonText: 'Cancel'
    });
    
    if (result.isConfirmed) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const response = await fetch(`/trip-tickets/${ticketId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    _method: 'PATCH',
                    status: 'Approved'
                })
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                // Show success message
                await Swal.fire({
                    icon: 'success',
                    title: 'Trip Started!',
                    text: data.message || 'The trip has been started successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Reload the page to show updated status
                location.reload();
            } else {
                // Show error
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to start trip. Please try again.'
                });
            }
        } catch (error) {
            console.error('Error starting trip:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while starting the trip. Please try again.'
            });
        }
    }
}

// Function to show cancel modal
function showCancelModal(ticketId, ticketNumber) {
    const modal = document.getElementById('cancelModal');
    const modalContent = document.getElementById('cancelModalContent');
    const form = document.getElementById('cancelForm');
    
    // Set form action
    form.action = `/trip-tickets/${ticketId}/cancel`;
    
    // Set ticket ID
    document.getElementById('cancelTicketId').value = ticketId;
    
    // Clear previous input
    document.getElementById('cancellation_reason').value = '';
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Trigger reflow for animation
    void modal.offsetWidth;
    
    // Animate in
    modalContent.classList.remove('scale-95', 'opacity-0');
    modalContent.classList.add('scale-100', 'opacity-100');
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Focus on textarea
    setTimeout(() => {
        document.getElementById('cancellation_reason').focus();
    }, 100);
}

// Function to close cancel modal
function closeCancelModal() {
    const modal = document.getElementById('cancelModal');
    const modalContent = document.getElementById('cancelModalContent');
    
    // Animate out
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    // Wait for animation to complete before hiding
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }, 200);
}

// Handle cancel form submission
document.addEventListener('DOMContentLoaded', function() {
    const cancelForm = document.getElementById('cancelForm');
    if (cancelForm) {
        cancelForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const ticketId = document.getElementById('cancelTicketId').value;
            const reason = document.getElementById('cancellation_reason').value;
            
            if (!reason || reason.trim() === '') {
                alert('Please provide a reason for cancellation.');
                return;
            }
            
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                const response = await fetch(`/trip-tickets/${ticketId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                if (response.ok) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelled!',
                        text: 'Trip ticket has been cancelled successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Reload the page
                        location.reload();
                    });
                } else {
                    // Show error
                    const data = await response.json();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to cancel trip ticket. Please try again.'
                    });
                }
            } catch (error) {
                console.error('Error cancelling trip ticket:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while cancelling the trip ticket. Please try again.'
                });
            }
        });
    }
});

// Close cancel modal when clicking outside
document.getElementById('cancelModal')?.addEventListener('click', function(event) {
    if (event.target === this) {
        closeCancelModal();
    }
});

// Close cancel modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const cancelModal = document.getElementById('cancelModal');
        if (!cancelModal?.classList.contains('hidden')) {
            closeCancelModal();
        }
    }
});

// Function to end a trip
// Function to end a trip with SweetAlert2 confirmation
async function endTrip(ticketId) {
    const result = await Swal.fire({
        title: 'End Trip',
        html: `
            <div class="text-left space-y-3">
                <p class="text-sm text-gray-600">Enter the odometer readings for this completed trip. Leave them blank to use the estimated distance.</p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Odometer Start (km)</label>
                    <input id="swal-odometer-start" type="number" min="0" class="swal2-input" placeholder="e.g. 1250">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Odometer End (km)</label>
                    <input id="swal-odometer-end" type="number" min="0" class="swal2-input" placeholder="e.g. 1400">
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, end it!',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const start = document.getElementById('swal-odometer-start').value;
            const end = document.getElementById('swal-odometer-end').value;

            if (start && end && Number(end) < Number(start)) {
                Swal.showValidationMessage('Odometer end must be greater than or equal to odometer start.');
                return false;
            }

            return {
                odometer_start_km: start,
                odometer_end_km: end
            };
        }
    });

    if (result.isConfirmed) {
        Swal.fire({
            title: 'Ending Trip...',
            html: '<div class="text-sm">Updating trip status and saving the odometer readings...</div>',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        setTimeout(() => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/trip-tickets/${ticketId}/status`;
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken;
            form.appendChild(tokenInput);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PATCH';
            form.appendChild(methodInput);
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = 'Completed';
            form.appendChild(statusInput);

            if (result.value?.odometer_start_km) {
                const startInput = document.createElement('input');
                startInput.type = 'hidden';
                startInput.name = 'odometer_start_km';
                startInput.value = result.value.odometer_start_km;
                form.appendChild(startInput);
            }

            if (result.value?.odometer_end_km) {
                const endInput = document.createElement('input');
                endInput.type = 'hidden';
                endInput.name = 'odometer_end_km';
                endInput.value = result.value.odometer_end_km;
                form.appendChild(endInput);
            }
            
            document.body.appendChild(form);
            form.submit();
        }, 2000);
    }
}

// Function to archive a trip
function archiveTrip(ticketId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Are you sure you want to archive this trip ticket? This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1e6031',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, archive it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state with informative message
            Swal.fire({
                title: 'Archiving Trip...',
                html: '<div class="text-sm">Archiving trip ticket...</div>',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/trip-tickets/${ticketId}/status`;
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken;
            form.appendChild(tokenInput);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PATCH';
            form.appendChild(methodInput);
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = 'Archived';
            form.appendChild(statusInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Function to view itinerary as PDF
function viewItineraryPDF(itineraryId) {
    if (!itineraryId) {
        alert('No itinerary found for this trip ticket.');
        return;
    }
    // Open the PDF in modal instead of new tab
    openPdfModal(`/itinerary/${itineraryId}/pdf`);
}

// Function to view trip ticket as PDF
function viewTripTicketPDF(ticketId) {
    // Close the trip ticket details modal first
    const detailsModal = document.getElementById('tripTicketDetailsModal');
    if (detailsModal) {
        detailsModal.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Restore body scroll
    }
    
    // Open the PDF in modal instead of new tab
    openPdfModal(`/trip-tickets/${ticketId}/pdf`);
}

// Function to show trip ticket details in modal
function showTripTicketDetails(ticketId) {
    // Show loading state
    const contentDiv = document.getElementById('tripTicketDetailsContent');
    contentDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl mr-2"></i> Loading trip ticket details...</div>';
    
    const mapPanel = document.getElementById('tripTicketMapPanel');
    if (mapPanel) {
        mapPanel.innerHTML = '<div class="h-full w-full flex items-center justify-center bg-gray-50 text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i> Loading map...</div>';
    }
    
    // Show the modal
    const modal = document.getElementById('tripTicketModal');
    const modalContent = document.getElementById('tripTicketModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Fetch trip ticket details via AJAX
    fetch(`/api/trip-tickets/${ticketId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const ticket = data.data;
                
                // Format passengers list with +X more functionality
                let passengersHtml = '';
                if (ticket.passengers && Array.isArray(ticket.passengers) && ticket.passengers.length > 0) {
                    const validPassengers = ticket.passengers.filter(p => p && p.trim() !== '');
                    const totalPassengers = validPassengers.length;
                    
                    if (totalPassengers > 0) {
                        const firstPassenger = validPassengers[0];
                        const remainingCount = totalPassengers - 1;
                        
                        if (remainingCount > 0) {
                            const passengersStr = validPassengers.join('|');
                            const ticketNum = (ticket.ticket_number || 'Trip').replace(/'/g, "\\'");
                            passengersHtml = `<span class="text-gray-900">${firstPassenger}</span><button type="button" onclick="viewPassengersModalFromModal('${passengersStr}', '${ticketNum}')" class="ml-1 text-blue-600 hover:text-blue-800 font-medium cursor-pointer">+${remainingCount} more</button>`;
                        } else {
                            passengersHtml = `<span class="text-gray-900">${firstPassenger}</span>`;
                        }
                    } else {
                        passengersHtml = '<span class="text-gray-500">N/A</span>';
                    }
                } else {
                    passengersHtml = '<span class="text-gray-500">N/A</span>';
                }
                
                // Format date range
                let dateRangeHtml = '';
                if (ticket.itinerary && ticket.itinerary.date_from) {
                    const dateFrom = new Date(ticket.itinerary.date_from);
                    const dateTo = ticket.itinerary.date_to ? new Date(ticket.itinerary.date_to) : null;
                    
                    const monthOptions = { month: 'short' };
                    const dayOptions = { day: 'numeric' };
                    const yearOptions = { year: 'numeric' };
                    
                    if (dateTo && dateFrom.toDateString() !== dateTo.toDateString()) {
                        // Different dates - show range
                        if (dateFrom.getMonth() === dateTo.getMonth() && dateFrom.getFullYear() === dateTo.getFullYear()) {
                            // Same month: JAN 1-2, 2026
                            const monthStr = dateFrom.toLocaleDateString('en-US', monthOptions).toUpperCase();
                            const fromDay = dateFrom.toLocaleDateString('en-US', dayOptions);
                            const toDay = dateTo.toLocaleDateString('en-US', dayOptions);
                            const yearStr = dateFrom.toLocaleDateString('en-US', yearOptions);
                            dateRangeHtml = `${monthStr} ${fromDay}-${toDay}, ${yearStr}`;
                        } else if (dateFrom.getFullYear() === dateTo.getFullYear()) {
                            // Same year, different months: JAN 31 - FEB 1, 2026
                            const fromMonthStr = dateFrom.toLocaleDateString('en-US', monthOptions).toUpperCase();
                            const fromDay = dateFrom.toLocaleDateString('en-US', dayOptions);
                            const toMonthStr = dateTo.toLocaleDateString('en-US', monthOptions).toUpperCase();
                            const toDay = dateTo.toLocaleDateString('en-US', dayOptions);
                            const yearStr = dateFrom.toLocaleDateString('en-US', yearOptions);
                            dateRangeHtml = `${fromMonthStr} ${fromDay} - ${toMonthStr} ${toDay}, ${yearStr}`;
                        } else {
                            // Different years: DEC 31, 2026 - JAN 1, 2027
                            const fromMonthStr = dateFrom.toLocaleDateString('en-US', monthOptions).toUpperCase();
                            const fromDay = dateFrom.toLocaleDateString('en-US', dayOptions);
                            const fromYearStr = dateFrom.toLocaleDateString('en-US', yearOptions);
                            const toMonthStr = dateTo.toLocaleDateString('en-US', monthOptions).toUpperCase();
                            const toDay = dateTo.toLocaleDateString('en-US', dayOptions);
                            const toYearStr = dateTo.toLocaleDateString('en-US', yearOptions);
                            dateRangeHtml = `${fromMonthStr} ${fromDay}, ${fromYearStr} - ${toMonthStr} ${toDay}, ${toYearStr}`;
                        }
                    } else {
                        // Same date or no end date: JAN 1, 2026
                        const monthStr = dateFrom.toLocaleDateString('en-US', monthOptions).toUpperCase();
                        const dayStr = dateFrom.toLocaleDateString('en-US', dayOptions);
                        const yearStr = dateFrom.toLocaleDateString('en-US', yearOptions);
                        dateRangeHtml = `${monthStr} ${dayStr}, ${yearStr}`;
                    }
                } else {
                    dateRangeHtml = '<span class="text-gray-500">N/A</span>';
                }
                
                // Format the details matching VP approval format EXACTLY
                contentDiv.innerHTML = `
                    <div class="mb-3">
                        <h4 class="font-bold text-gray-900 border-b pb-1.5 mb-2 text-base">Trip Information</h4>
                        <table class="w-full">
                            <tbody>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Driver:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${ticket.itinerary && ticket.itinerary.driver ? ticket.itinerary.driver.full_name : '<span class="text-gray-500">N/A</span>'}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top">Contact Number:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${ticket.itinerary && ticket.itinerary.driver && ticket.itinerary.driver.contact_num ? ticket.itinerary.driver.contact_num : '<span class="text-gray-500">N/A</span>'}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top">Vehicle:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${ticket.itinerary && ticket.itinerary.vehicle ? `${ticket.itinerary.vehicle.model || ''} - ${ticket.itinerary.vehicle.plate_number || ''}`.trim() : 'N/A'}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top">Departure Date:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${dateRangeHtml}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top">Departure Time:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${ticket.itinerary && ticket.itinerary.departure_time ? new Date('1970-01-01T' + ticket.itinerary.departure_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : '<span class="text-gray-500">N/A</span>'}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top">Destination:</td>
                                    <td class="text-gray-900 py-0.5 align-top whitespace-pre-wrap">${(ticket.itinerary && ticket.itinerary.destination) ? ticket.itinerary.destination.trim() : '<span class="text-gray-500">N/A</span>'}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top">Purpose:</td>
                                    <td class="text-gray-900 py-0.5 align-top whitespace-pre-wrap">${(ticket.itinerary && ticket.itinerary.purpose) ? ticket.itinerary.purpose.trim() : '<span class="text-gray-500">N/A</span>'}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-gray-900 border-b pb-1.5 mb-2 text-base">Employee Information</h4>
                        <table class="w-full">
                            <tbody>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Head of Party:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${ticket.head_of_party || '<span class="text-gray-500">N/A</span>'}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top">Contact Number:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${(() => {
                                            // Try to get contact from itinerary's first travel order employee
                                            if (ticket.itinerary && ticket.itinerary.travel_orders && ticket.itinerary.travel_orders.length > 0) {
                                                const firstTO = ticket.itinerary.travel_orders[0];
                                                if (firstTO && firstTO.employee && firstTO.employee.contact_num) {
                                                    return firstTO.employee.contact_num;
                                                }
                                                // Fallback to user contact_num if employee has no contact_num
                                                if (firstTO && firstTO.employee && firstTO.employee.user && firstTO.employee.user.contact_num) {
                                                    return firstTO.employee.user.contact_num;
                                                }
                                            }
                                            // Legacy support for single travel_order
                                            if (ticket.itinerary && ticket.itinerary.travelOrder && ticket.itinerary.travelOrder.employee && ticket.itinerary.travelOrder.employee.contact_num) {
                                                return ticket.itinerary.travelOrder.employee.contact_num;
                                            }
                                            return '-';
                                        })()}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Passengers:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${passengersHtml}</td>
                                </tr>
                                <tr class="border-t pt-2 mt-1">
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Status:</td>
                                    <td class="py-0.5 align-top">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${
                                            ticket.status === 'Pending' ? 'bg-yellow-100 text-yellow-800' :
                                            ticket.status === 'Approved' ? 'bg-green-100 text-green-800' :
                                            ticket.status === 'Completed' ? 'bg-blue-100 text-blue-800' :
                                            ticket.status === 'Cancelled' ? 'bg-red-100 text-red-800' :
                                            'bg-gray-100 text-gray-800'
                                        }">
                                            ${ticket.status || 'N/A'}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                `;
                
                renderTripTicketMap(ticket);
                
                // Update View Excel button - hide only for Pending tab (Motorpool Admin)
                const viewExcelButton = document.getElementById('viewExcelButton');
                if (ticket.id) {
                    // Check if we're currently in the pending tab
                    const pendingTab = document.getElementById('pending-tab');
                    const isPendingTab = pendingTab && !pendingTab.classList.contains('hidden');
                    
                    // Hide Excel button ONLY for Pending tab
                    if (isPendingTab) {
                        viewExcelButton.classList.add('hidden');
                    } else {
                        viewExcelButton.classList.remove('hidden');
                        viewExcelButton.onclick= function() {
                            // Close the trip ticket details modal first
                            const detailsModal = document.getElementById('tripTicketDetailsModal');
                            if (detailsModal) {
                                detailsModal.classList.add('hidden');
                                document.body.style.overflow = 'auto'; // Restore body scroll
                            }
                            
                            // Open Excel in modal
                            openExcelModal(`/trip-tickets/excel/${ticket.id}`);
                        };
                    }
                } else {
                    viewExcelButton.classList.add('hidden');
                }
            } else {
                contentDiv.innerHTML = '<div class="text-center py-4 text-red-500">Error loading trip ticket details.</div>';
            }
        })
        .catch(error => {
            console.error('Error fetching trip ticket details:', error);
            contentDiv.innerHTML = '<div class="text-center py-4 text-red-500">Error loading trip ticket details.</div>';
        });
}

/**
 * Renders the right-hand map panel inside the Trip Ticket Details modal.
 * - Completed trips: draw the actual recorded GPS breadcrumb trail.
 * - On-Going trips: show the driver's live position, reverse-geocoded
 *   into a place name, plus a driving route + ETA to the destination
 *   (geocoded from the free-text destination field).
 * - Otherwise: a friendly placeholder.
 */
const tripTrailUrlTemplate = @json(route('api.trip-tickets.trail', ['id' => '__ID__']));

function renderTripTicketMap(ticket) {
    const mapPanel = document.getElementById('tripTicketMapPanel');
    const mapColumn = document.getElementById('tripTicketMapColumn');
    const detailsColumn = document.getElementById('tripTicketDetailsColumn');
    const modalContentEl = document.getElementById('tripTicketModalContent');
    if (!mapPanel || !mapColumn || !detailsColumn || !modalContentEl) return;

    const status = ticket.status;
    const driverLocation = (ticket.itinerary && ticket.itinerary.driver) ? ticket.itinerary.driver.location : null;
    const destinationText = ticket.itinerary ? ticket.itinerary.destination : null;

    // Only Completed and On-Going trips have anything meaningful to show on
    // a map - for Pending/Approved/Cancelled, drop the map column entirely
    // and let the details take the full modal width instead of showing an
    // empty placeholder panel.
    const showMapColumn = (status === 'Completed' || status === 'On-Going');

    if (showMapColumn) {
        mapColumn.classList.remove('hidden');
        detailsColumn.classList.remove('w-full');
        detailsColumn.classList.add('md:w-[380px]', 'md:flex-shrink-0');
        modalContentEl.classList.remove('max-w-2xl');
        modalContentEl.classList.add('max-w-6xl');
    } else {
        mapColumn.classList.add('hidden');
        detailsColumn.classList.remove('md:w-[380px]', 'md:flex-shrink-0');
        detailsColumn.classList.add('w-full');
        return;
    }

    if (window.GOOGLE_MAPS_KEY_MISSING) {
        mapPanel.innerHTML = mapPlaceholder('Google Maps API key not configured yet. Add GOOGLE_MAPS_API_KEY to your .env file.', 'fa-map-location-dot');
        return;
    }

    if (status === 'Completed') {
        renderCompletedRouteMap(ticket, mapPanel);
    } else if (status === 'On-Going' && driverLocation) {
        const destinationCoords = (ticket.itinerary && ticket.itinerary.destination_latitude && ticket.itinerary.destination_longitude)
            ? { lat: parseFloat(ticket.itinerary.destination_latitude), lng: parseFloat(ticket.itinerary.destination_longitude) }
            : null;
        renderLiveTrackingMap(ticket, mapPanel, driverLocation, destinationText, destinationCoords);
    } else {
        mapPanel.innerHTML = mapPlaceholder('This trip is on-going, but the driver has not shared a live location yet.', 'fa-satellite-dish');
    }
}

function initTicketMap(containerId) {
    return new google.maps.Map(document.getElementById(containerId), {
        mapTypeControl: true,
        mapTypeControlOptions: {
            style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
            position: google.maps.ControlPosition.TOP_RIGHT,
        },
        streetViewControl: false,
        fullscreenControl: false,
        zoom: 13,
    });
}

function busIconObj(scale) {
    scale = scale || 32;
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${scale}" height="${scale}" viewBox="0 0 32 32">
        <circle cx="16" cy="16" r="14" fill="#1e6031" stroke="white" stroke-width="3"/>
        <text x="16" y="21" font-size="15" text-anchor="middle">\uD83D\uDE8C</text>
    </svg>`;
    return {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
        scaledSize: new google.maps.Size(scale, scale),
        anchor: new google.maps.Point(scale / 2, scale / 2),
    };
}

function pinIconObj(color) {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="30" height="36" viewBox="0 0 30 36">
        <path d="M15 0C6.7 0 0 6.7 0 15c0 11.2 15 21 15 21s15-9.8 15-21C30 6.7 23.3 0 15 0z" fill="${color || '#b91c1c'}"/>
        <circle cx="15" cy="15" r="6" fill="white"/>
    </svg>`;
    return {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
        scaledSize: new google.maps.Size(30, 36),
        anchor: new google.maps.Point(15, 36),
    };
}

function mapPlaceholder(message, icon) {
    return `<div class="h-full w-full flex flex-col items-center justify-center bg-gray-50 text-gray-400 text-sm text-center px-6">
        <i class="fas ${icon || 'fa-map-marked-alt'} text-4xl mb-3 text-gray-300"></i>
        ${message}
    </div>`;
}

function haversineKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function renderCompletedRouteMap(ticket, mapPanel) {
    mapPanel.innerHTML = `
        <div id="ticket-detail-map" class="h-full w-full" style="background:#f3f4f6;"></div>
        <div id="ticket-detail-summary" class="absolute top-3 left-1/2 -translate-x-1/2 bg-white/95 shadow-lg rounded-lg px-4 py-2 text-xs text-gray-600 hidden"></div>
    `;

    setTimeout(() => {
        const map = initTicketMap('ticket-detail-map');

        fetch(tripTrailUrlTemplate.replace('__ID__', ticket.id))
            .then(r => r.json())
            .then(data => {
                const points = (data.points || []).map(p => ({ lat: parseFloat(p.latitude), lng: parseFloat(p.longitude) }));

                if (points.length === 0) {
                    const start = (ticket.start_latitude && ticket.start_longitude)
                        ? { lat: parseFloat(ticket.start_latitude), lng: parseFloat(ticket.start_longitude) } : null;
                    const end = (ticket.end_latitude && ticket.end_longitude)
                        ? { lat: parseFloat(ticket.end_latitude), lng: parseFloat(ticket.end_longitude) } : null;

                    if (start && end) {
                        new google.maps.Marker({ position: start, map: map, icon: busIconObj() });
                        new google.maps.Marker({ position: end, map: map, icon: pinIconObj('#b91c1c') });
                        new google.maps.Polyline({
                            path: [start, end], map: map,
                            strokeOpacity: 0,
                            icons: [{ icon: { path: 'M 0,-1 0,1', strokeOpacity: 1, strokeColor: '#1e6031', scale: 3 }, offset: '0', repeat: '12px' }],
                        });
                        const bounds = new google.maps.LatLngBounds();
                        bounds.extend(start); bounds.extend(end);
                        map.fitBounds(bounds, 30);
                    } else {
                        mapPanel.innerHTML = mapPlaceholder('No GPS route was recorded for this trip.', 'fa-map-marked-alt');
                    }
                    return;
                }

                new google.maps.Marker({ position: points[0], map: map, icon: busIconObj() });
                new google.maps.Marker({ position: points[points.length - 1], map: map, icon: pinIconObj('#b91c1c') });
                new google.maps.Polyline({ path: points, map: map, strokeColor: '#1e6031', strokeWeight: 4 });

                const bounds = new google.maps.LatLngBounds();
                points.forEach(p => bounds.extend(p));
                map.fitBounds(bounds, 30);

                const summary = document.getElementById('ticket-detail-summary');
                if (summary) {
                    let distanceText = `${points.length} GPS points recorded`;
                    if (ticket.odometer_start_km !== null && ticket.odometer_start_km !== undefined
                        && ticket.odometer_end_km !== null && ticket.odometer_end_km !== undefined) {
                        distanceText = `${ticket.odometer_end_km - ticket.odometer_start_km} km traveled (odometer)`;
                    }
                    summary.textContent = distanceText;
                    summary.classList.remove('hidden');
                }
            })
            .catch(() => {
                mapPanel.innerHTML = mapPlaceholder('Could not load the route for this trip.', 'fa-exclamation-triangle');
            });
    }, 50);
}

function renderLiveTrackingMap(ticket, mapPanel, driverLocation, destinationText, destinationCoords) {
    mapPanel.innerHTML = `
        <div id="ticket-detail-map" class="h-full w-full" style="background:#f3f4f6;"></div>
        <div id="ticket-detail-banner" class="absolute top-3 right-3 left-3 md:left-auto md:max-w-[280px] bg-[#1e6031] text-white rounded-lg shadow-lg px-4 py-3 text-xs">
            <div class="font-semibold mb-1"><i class="fas fa-location-dot mr-1"></i> Current Location</div>
            <div id="ticket-detail-location-name" class="text-white/90">Locating...</div>
            <div id="ticket-detail-distance" class="mt-1 italic text-white/80"></div>
        </div>
    `;

    setTimeout(() => {
        const map = initTicketMap('ticket-detail-map');

        const current = { lat: driverLocation.latitude, lng: driverLocation.longitude };
        new google.maps.Marker({ position: current, map: map, icon: busIconObj() });
        map.setCenter(current);
        map.setZoom(13);

        const geocoder = new google.maps.Geocoder();

        // Reverse-geocode the current position into a readable place name.
        geocoder.geocode({ location: current }, (results, status) => {
            const nameEl = document.getElementById('ticket-detail-location-name');
            if (!nameEl) return;
            if (status === 'OK' && results && results.length > 0) {
                let shortName = results[0].formatted_address;
                const sublocality = results.find(r => r.types.includes('sublocality') || r.types.includes('neighborhood'));
                if (sublocality) shortName = sublocality.formatted_address;
                nameEl.textContent = shortName;
            } else {
                nameEl.textContent = `${current.lat.toFixed(4)}, ${current.lng.toFixed(4)}`;
            }
        });

        // If the travel order already has an exact pinned destination (set via
        // the Places search + draggable pin when the trip was created), use it
        // directly - it's more accurate than re-guessing from the free-text
        // destination name, and skips an extra Geocoding API call.
        if (destinationCoords) {
            plotDestinationAndRoute(map, current, destinationCoords, destinationText);
            return;
        }

        if (!destinationText) return;

        // Fallback for older trips created before the destination pin existed:
        // forward-geocode the destination text instead.
        geocoder.geocode({ address: destinationText + ', Philippines' }, (results, status) => {
            if (status !== 'OK' || !results || results.length === 0) return;
            const dest = results[0].geometry.location;
            plotDestinationAndRoute(map, current, { lat: dest.lat(), lng: dest.lng() }, destinationText);
        });
    }, 50);
}

function plotDestinationAndRoute(map, current, dest, destinationText) {
    new google.maps.Marker({ position: dest, map: map, icon: pinIconObj('#b91c1c'), title: 'Destination: ' + destinationText });

    const directionsService = new google.maps.DirectionsService();
    directionsService.route({
        origin: current,
        destination: dest,
        travelMode: google.maps.TravelMode.DRIVING,
    }, (result, dirStatus) => {
        const distanceEl = document.getElementById('ticket-detail-distance');

        if (dirStatus === 'OK' && result.routes && result.routes.length > 0) {
            const route = result.routes[0];
            new google.maps.Polyline({
                path: route.overview_path, map: map,
                strokeColor: '#f97316', strokeWeight: 5,
            });

            const bounds = new google.maps.LatLngBounds();
            bounds.extend(current); bounds.extend(dest);
            map.fitBounds(bounds, 40);

            const leg = route.legs[0];
            if (distanceEl && leg) {
                distanceEl.textContent = `${leg.distance.text} \u00b7 about ${leg.duration.text} away from ${destinationText}`;
            }
        } else if (distanceEl) {
            new google.maps.Polyline({
                path: [current, dest], map: map,
                strokeOpacity: 0,
                icons: [{ icon: { path: 'M 0,-1 0,1', strokeOpacity: 1, strokeColor: '#f97316', scale: 3 }, offset: '0', repeat: '12px' }],
            });
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(current); bounds.extend(dest);
            map.fitBounds(bounds, 40);
            distanceEl.textContent = `${haversineKm(current.lat, current.lng, dest.lat, dest.lng).toFixed(1)} km away from ${destinationText} (straight-line)`;
        }
    });
}


// Function to format passengers array properly
function formatPassengers(passengers) {
    if (!passengers) {
        return 'N/A';
    }
    
    if (Array.isArray(passengers)) {
        if (passengers.length === 0) {
            return 'N/A';
        }
        
        // Handle array of strings
        if (typeof passengers[0] === 'string') {
            return passengers.join(', ');
        }
        
        // Handle array of objects (try to extract name properties)
        if (typeof passengers[0] === 'object') {
            return passengers.map(p => {
                if (typeof p === 'object' && p !== null) {
                    // Look for common name properties
                    return p.name || p.full_name || p.first_name + ' ' + p.last_name || p.toString();
                }
                return p;
            }).join(', ');
        }
        
        return passengers.join(', ');
    }
    
    // If it's a string, return as is
    if (typeof passengers === 'string') {
        return passengers;
    }
    
    // For any other type, return 'N/A'
    return 'N/A';
}

// Function to close the modal
function closeTripTicketModal() {
    document.getElementById('tripTicketModal').classList.add('hidden');
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
            <li class="flex items-start gap-2 text-sm text-gray-700 py-1">
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

// Close passengers modal when clicking outside
document.getElementById('passengersModal')?.addEventListener('click', function(event) {
    if (event.target === this) {
        closePassengersModal();
    }
});

// Close passengers modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('passengersModal');
        if (modal && !modal.classList.contains('hidden')) {
            closePassengersModal();
        }
    }
});

// Prevent scrolling when modal is open
const tripTicketModalElement = document.getElementById('tripTicketModal');
if (tripTicketModalElement) {
    tripTicketModalElement.addEventListener('click', function(e) {
        if (e.target.id === 'tripTicketModal') {
            closeTripTicketModal();
        }
    });
}

// Upload File Modal Functions
function showUploadFileModal(ticketId) {
    document.getElementById('uploadTicketId').value = ticketId;
    document.getElementById('uploadFileForm').action = `/trip-tickets/${ticketId}/upload-completion-file`;
    
    const modal = new bootstrap.Modal(document.getElementById('uploadFileModal'));
    modal.show();
}

function closeUploadFileModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('uploadFileModal'));
    if (modal) {
        modal.hide();
    }
}

// Initialize upload file modal event listeners
document.addEventListener('DOMContentLoaded', function() {
    const uploadModal = document.getElementById('uploadFileModal');
    if (uploadModal) {
        uploadModal.addEventListener('hidden.bs.modal', function () {
            document.getElementById('uploadFileForm').reset();
        });
    }
});

// Handle file upload form submission
document.getElementById('uploadFileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const ticketId = document.getElementById('uploadTicketId').value;
    
    // Show loading SweetAlert
    Swal.fire({
        title: 'Uploading File...',
        text: 'Please wait while we upload your completion file.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(`/trip-tickets/${ticketId}/upload-completion-file`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => {
        if (response.ok) {
            closeUploadFileModal();
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Completion file uploaded successfully.',
                timer: 3000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            return response.json().then(err => { throw err; });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Failed to upload file. Please try again.'
        });
    });
});

// Create Trip Ticket Modal Functions using Bootstrap
function showCreateTripTicketModal() {
    var myModal = new bootstrap.Modal(document.getElementById('createTripTicketModal'));
    myModal.show();
}

function closeCreateTripTicketModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('createTripTicketModal'));
    if (modal) {
        modal.hide();
    }
    // Reset form after modal is hidden
    setTimeout(() => {
        document.getElementById('createTripTicketForm').reset();
        // Clear passenger list
        document.getElementById('modal_passenger-items').innerHTML = '';
        document.getElementById('modal_head_of_party').value = '';
        document.getElementById('modal_current-head').textContent = 'None selected';
        // Clear preview fields
        document.getElementById('modal_date-preview').value = '';
        document.getElementById('modal_vehicle-preview').value = '';
        document.getElementById('modal_purpose-preview').value = '';
        // Clear travel orders list
        const travelOrdersListDiv = document.getElementById('modal-travel-orders-list');
        const totalCountSpan = document.getElementById('modal-total-travel-orders');
        travelOrdersListDiv.innerHTML = '<p class="text-muted mb-0 fst-italic small">Select an itinerary to load travel orders</p>';
        totalCountSpan.textContent = '0';
        // Reset additional travel orders array
        modalAdditionalTravelOrders = [];
            
        // Reset removed travel orders array
        modalRemovedTravelOrders = [];
            
        // Reset itinerary ID
        modalItineraryId = null;
    }, 300);
}

// Store additional travel orders in modal
let modalAdditionalTravelOrders = [];

// Store original itinerary travel order IDs (to maintain exclusion even after removal)
let modalOriginalItineraryTravelOrderIds = [];

// Store REMOVED travel order IDs (to remove from itinerary after form submission)
let modalRemovedTravelOrders = [];

// Store itinerary ID for API calls
let modalItineraryId = null;

// Store itinerary date range for filtering (needed after add/remove operations)
let modalItineraryDateFrom = null;
let modalItineraryDateTo = null;

// Handle Modal Add Travel Order click - prevent action if disabled
function handleModalAddTravelOrderClick() {
    const select = document.getElementById('modal_additional_travel_order_select');
    
    console.log('=== handleModalAddTravelOrderClick called ===');
    console.log('Select disabled:', select.disabled);
    
    // Check if dropdown is disabled
    if (select.disabled) {
        console.log('BLOCKED: Dropdown is disabled, showing warning');
        Swal.fire({
            icon: 'warning',
            title: 'No Itinerary Selected',
            text: 'Please select an itinerary first before adding travel orders.',
            timer: 2000,
            confirmButtonColor: '#1e6031'
        });
        return;
    }
    
    console.log('PROCEEDING: Dropdown is enabled');
    // Call the actual function
    addModalAdditionalTravelOrder();
}

// Update itinerary details in modal
function updateModalItineraryDetails() {
    const select = document.getElementById('modal_itinerary_id');
    const selectedOption = select.options[select.selectedIndex];
    
    // ALWAYS reset everything first when selection changes
    modalAdditionalTravelOrders = [];
    
    // Reset preview fields
    document.getElementById('modal_date-preview').value = '';
    document.getElementById('modal_vehicle-preview').value = '';
    document.getElementById('modal_purpose-preview').value = '';
    
    // Reset travel orders list
    const travelOrdersListDiv = document.getElementById('modal-travel-orders-list');
    const totalCountSpan = document.getElementById('modal-total-travel-orders');
    travelOrdersListDiv.innerHTML = '<p class="text-muted mb-0 fst-italic small">Select an itinerary to load travel orders</p>';
    totalCountSpan.textContent = '0';
    
    // Reset passenger list and head of party
    document.getElementById('modal_passenger-items').innerHTML = '';
    document.getElementById('modal_head_of_party').value = '';
    document.getElementById('modal_passenger_hidden_inputs_container').innerHTML = '';
    document.getElementById('modal_current-head').textContent = 'None selected';
    
    // Reset additional travel order dropdown and disable it
    const additionalSelect = document.getElementById('modal_additional_travel_order_select');
    if (additionalSelect) {
        additionalSelect.innerHTML = '<option value="" disabled selected>-- Select an itinerary first --</option>';
        additionalSelect.value = '';
        additionalSelect.disabled = true; // Disable until itinerary is selected
    }
    
    // If no itinerary selected, stop here
    if (!selectedOption.value) {
        console.log('No itinerary selected - all fields cleared');
        modalItineraryId = null; // Reset itinerary ID
       return;
    }
    
    // New itinerary selected - load its data
    modalItineraryId = selectedOption.value; // Store itinerary ID for API calls
    
    const driver = selectedOption.getAttribute('data-driver') || '';
    const contactNum = selectedOption.getAttribute('data-contact-num') || 'N/A';
    const vehicleModel = selectedOption.getAttribute('data-vehicle') || '';
    const plateNumber= selectedOption.getAttribute('data-plate-number') || '';
    const destination = selectedOption.getAttribute('data-destination') || '';
    const purpose = selectedOption.getAttribute('data-purpose') || '';
    const dateFrom = selectedOption.getAttribute('data-date-from') || '';
    const dateTo = selectedOption.getAttribute('data-date-to') || '';
    
    // Format dates with proper formatting
    let dateDisplay = '';
    if (dateFrom && dateTo) {
        const fromDate = new Date(dateFrom);
        const toDate = new Date(dateTo);
        
        const fromMonth = fromDate.toLocaleString('en-US', { month: 'short' });
        const fromDay = fromDate.getDate();
        const fromYear = fromDate.getFullYear();
        
        const toMonth = toDate.toLocaleString('en-US', { month: 'short' });
        const toDay = toDate.getDate();
        const toYear = toDate.getFullYear();
        
        // Check if same date
        if (fromDate.getTime() === toDate.getTime()) {
            // Same date: "Jan 31, 2026"
            dateDisplay = `${fromMonth} ${fromDay}, ${fromYear}`;
        } else if (fromMonth === toMonth && fromYear === toYear) {
            // Same month and year: "Jan 1-2, 2026"
            dateDisplay = `${fromMonth} ${fromDay}-${toDay}, ${fromYear}`;
        } else if (fromYear === toYear) {
            // Same year, different months: "Jan 31 - Feb 1, 2026"
            dateDisplay = `${fromMonth} ${fromDay} - ${toMonth} ${toDay}, ${fromYear}`;
        } else {
            // Different years: "Dec 31, 2026 - Jan 1, 2027"
            dateDisplay = `${fromMonth} ${fromDay}, ${fromYear} - ${toMonth} ${toDay}, ${toYear}`;
        }
    }
    
    // Format vehicle with model and plate number
    const vehicleDisplay = vehicleModel.trim() + (plateNumber ? ' - Plate: ' + plateNumber : '');
    
    document.getElementById('modal_date-preview').value = dateDisplay || 'N/A';
    document.getElementById('modal_vehicle-preview').value = vehicleDisplay;
    document.getElementById('modal_purpose-preview').value = purpose;
    
    // Load ALL passengers from ALL travel orders in this itinerary
    const itineraryId = selectedOption.value;
    if (itineraryId) {
        // Fetch itinerary details including travel orders
        fetch(`/api/itineraries/${itineraryId}`)
            .then(response => response.json())
            .then(data => {
                console.log('=== ITINERARY DETAILS FOR TRAVEL ORDERS ===');
                console.log('Full itinerary data:', data);
                
                if (data.itinerary && data.itinerary.travel_orders && data.itinerary.travel_orders.length > 0) {
                    // Build HTML for travel orders list with formatted dates
                    let html = '<ul class="list-unstyled mb-0">';
                    data.itinerary.travel_orders.forEach((to, index) => {
                        // Format similar to Create Itinerary modal: "Apr 3, 2026 (10:00 AM) - Employee Name (Destination)"
                        const employeeName = to.employee?.full_name || 'N/A';
                        const destination= to.destination || 'No destination';
                        const dateFrom = to.date_from || '';
                        const departureTime = to.departure_time ? `(${to.departure_time})` : '';
                        
                        // Build formatted display string
                        let displayText = '';
                        if (dateFrom) {
                            displayText = `${dateFrom} ${departureTime} - ${employeeName} (${destination})`;
                        } else {
                            displayText = `#${to.id} - ${employeeName} (${destination})`;
                        }
                        
                        // First travel order (index 0) should not have remove button
                        const removeButton = index === 0 
                            ? '' 
                            : `<button type="button" class="btn btn-xs btn-danger" onclick="removeModalTravelOrder('${to.id}')" style="font-size: 0.65rem; padding: 2px 6px;">
                                <i class="fas fa-times"></i> Remove
                               </button>`;
                        
                        html += `<li class="d-flex justify-content-between align-items-center mb-1 p-1 bg-white rounded border" id="modal-to-item-${to.id}">
                            <div><span class="small">${displayText}</span></div>
                            ${removeButton}
                        </li>`;
                    });
                    html += '</ul>';
                    
                    travelOrdersListDiv.innerHTML = html;
                    totalCountSpan.textContent = data.itinerary.travel_orders.length;
                    
                    console.log('Travel orders list populated with', data.itinerary.travel_orders.length, 'orders');
                    
                    // Get itinerary date range from selected option
                    const selectedOption = select.options[select.selectedIndex];
                    const itineraryDateFrom = selectedOption.getAttribute('data-date-from');
                    const itineraryDateTo = selectedOption.getAttribute('data-date-to');
                    
                    // Store date range for later use (after add/remove operations)
                    modalItineraryDateFrom = itineraryDateFrom;
                    modalItineraryDateTo = itineraryDateTo;
                    
                    // Populate additional travel order dropdown with remaining options (filtered by date)
                    const excludeIds = data.itinerary.travel_orders.map(to => to.id.toString());
                    console.log('=== EXCLUDING THESE TRAVEL ORDERS ===');
                    console.log('Exclude IDs (from itinerary):', excludeIds);
                    
                    // Store original itinerary travel order IDs for maintaining exclusion
                    modalOriginalItineraryTravelOrderIds = excludeIds;
                    
                    populateModalAdditionalTravelOrderDropdown(
                        excludeIds,
                        itineraryDateFrom,
                        itineraryDateTo
                    );
                    
                    // Enable the dropdown now that itinerary is selected and dropdown is populated
                    const additionalSelect = document.getElementById('modal_additional_travel_order_select');
                    if (additionalSelect) {
                        additionalSelect.disabled = false;
                        // Change placeholder to "-- Select to Add --" while keeping all travel order options
                        const allOptions = Array.from(additionalSelect.options);
                        additionalSelect.innerHTML = '<option value="" disabled selected>-- Select to Add --</option>';
                        // Re-add all travel order options (skip the first one which was the old placeholder)
                        for (let i = 1; i < allOptions.length; i++) {
                            additionalSelect.appendChild(allOptions[i]);
                        }
                    }
                    
                    // Auto-load passengers from all itinerary travel orders
                    const travelOrderIds = data.itinerary.travel_orders.map(to => to.id);
                    console.log('=== AUTO-LOADING PASSENGERS ===');
                    console.log('Itinerary has', data.itinerary.travel_orders.length, 'travel orders');
                    console.log('Travel Order IDs:', travelOrderIds);
                    console.log('Calling loadModalPassengersFromMultipleTravelOrders...');
                    
                    // Test if function exists
                    if (typeof loadModalPassengersFromMultipleTravelOrders === 'function') {
                        console.log('✓ Function exists, calling now...');
                        loadModalPassengersFromMultipleTravelOrders(travelOrderIds);
                    } else {
                        console.error('✗ Function NOT FOUND!');
                    }
                } else {
                    console.warn('No travel orders found in itinerary');
                    travelOrdersListDiv.innerHTML = '<p class="text-muted mb-0 fst-italic small">No travel orders in this itinerary</p>';
                    totalCountSpan.textContent = '0';
                }
            })
            .catch(error => {
                console.error('Error fetching itinerary details:', error);
            });
    }
}

// Populate additional travel order dropdown in modal
function populateModalAdditionalTravelOrderDropdown(excludeIds = [], itineraryDateFrom = null, itineraryDateTo = null) {
    const additionalSelect = document.getElementById('modal_additional_travel_order_select');
    
    // Get the original options from data attribute
    const originalOptions = additionalSelect.getAttribute('data-original-options');
    if (!originalOptions) {
       return;
    }
    
    // Parse the original options and filter out excluded IDs
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = '<select>' + originalOptions + '</select>';
    const allOptions = Array.from(tempDiv.querySelectorAll('option'));
    
    // Clear current options except placeholder
    additionalSelect.innerHTML = '<option value="" disabled selected>-- Select to Add --</option>';
    
    allOptions.forEach(option => {
        const optionId = option.value;
        if (!optionId) return;
        
        // Skip if already selected (in exclude list or already added as additional)
        if (excludeIds.includes(optionId.toString()) || modalAdditionalTravelOrders.includes(optionId.toString())) {
            return;
        }
        
        // Get itinerary_id from data attribute
        const optionItineraryId = option.getAttribute('data-itinerary-id');
        
        // Skip if already has an itinerary assigned (itinerary_id is not empty/null)
        if (optionItineraryId && optionItineraryId !== '' && optionItineraryId !== 'null') {
            console.log('Skipping TO', optionId, '- Already has itinerary:', optionItineraryId);
            return;
        }
        
        // Check date range overlap if itinerary dates are provided
        if (itineraryDateFrom && itineraryDateTo) {
            const optionDateFrom = option.getAttribute('data-date-from');
            const optionDateTo = option.getAttribute('data-date-to');
            
            // If travel order has dates, check for overlap
            if (optionDateFrom && optionDateTo) {
                // No overlap if: TO ends before itinerary starts OR TO starts after itinerary ends
                if (optionDateTo < itineraryDateFrom || optionDateFrom > itineraryDateTo) {
                    console.log('Skipping TO', optionId, '- Date range mismatch. TO:', optionDateFrom, 'to', optionDateTo, 'Itinerary:', itineraryDateFrom, 'to', itineraryDateTo);
                    return; // Skip - no date overlap
                }
            }
            // If TO doesn't have dates but itinerary does, we still show it
            // (assuming it might be a flexible/same-day travel order)
        }
        
        // Add to dropdown
        const newOption = option.cloneNode(true);
        additionalSelect.appendChild(newOption);
        console.log('Added TO', optionId, 'to dropdown');
    });
}

// Add additional travel order in modal
async function addModalAdditionalTravelOrder() {
    const select = document.getElementById('modal_additional_travel_order_select');
    const selectedOption = select.options[select.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select a travel order to add.',
            timer: 2000,
            showConfirmButton: false
        });
       return;
    }
    
    const travelOrderId = selectedOption.value;
    const displayText = selectedOption.textContent;
    
    // Validate: Check if travel order has an itinerary assigned (double-check before adding)
    const optionItineraryId = selectedOption.getAttribute('data-itinerary-id');
  if (optionItineraryId && optionItineraryId !== '' && optionItineraryId !== 'null') {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Travel Order',
            text: 'This travel order already has an itinerary assigned and cannot be added.',
            timer: 3000,
            showConfirmButton: true
        });
        return;
    }
    
    // Check if already added
    if (modalAdditionalTravelOrders.includes(travelOrderId)) {
        Swal.fire({
            icon: 'warning',
            title: 'Already Added',
            text: 'This travel order has already been added.',
            timer: 2000,
            showConfirmButton: false
        });
       return;
    }
    
    // Add to array
    modalAdditionalTravelOrders.push(travelOrderId);
    
    // Add to display list
    const travelOrdersListDiv = document.getElementById('modal-travel-orders-list');
    const totalCountSpan = document.getElementById('modal-total-travel-orders');
    
    const html = `<li class="d-flex justify-content-between align-items-center mb-1 p-1 bg-white rounded border" id="modal-to-item-${travelOrderId}">
        <div><span class="small">${displayText}</span></div>
        <button type="button" class="btn btn-xs btn-danger" onclick="removeModalTravelOrder('${travelOrderId}')" style="font-size: 0.65rem; padding: 2px 6px;">
            <i class="fas fa-times"></i> Remove
        </button>
    </li>`;
    
    // Remove "no travel orders" message if present
    const noOrdersMsg = travelOrdersListDiv.querySelector('.fst-italic');
    if (noOrdersMsg) {
        noOrdersMsg.remove();
    }
    
    // Append to list
    const ul = travelOrdersListDiv.querySelector('ul');
    if (ul) {
        ul.insertAdjacentHTML('beforeend', html);
    } else {
        travelOrdersListDiv.innerHTML = '<ul class="list-unstyled mb-0">' + html + '</ul>';
    }
    
    // Update count
    const currentCount = parseInt(totalCountSpan.textContent) || 0;
    totalCountSpan.textContent = currentCount +1;
    
    // Refresh dropdown to hide selected
    const currentExcludeIds = Array.from(travelOrdersListDiv.querySelectorAll('li')).map(li => li.id.replace('modal-to-item-', ''));
    
    console.log('=== AFTER ADDING ===');
    console.log('Added TO ID:', travelOrderId);
    console.log('Current exclude IDs:', currentExcludeIds);
    console.log('Using stored date range:', modalItineraryDateFrom, 'to', modalItineraryDateTo);
    
    // IMPORTANT: Pass the stored date range to maintain date filtering!
    populateModalAdditionalTravelOrderDropdown(
        currentExcludeIds,
      modalItineraryDateFrom,
      modalItineraryDateTo
    );
    
    // Reset dropdown
    select.value = '';
    
    // Reload passengers with all travel orders
    const allTravelOrderIds = [...currentExcludeIds, ...modalAdditionalTravelOrders];
    loadModalPassengersFromMultipleTravelOrders(allTravelOrderIds);
}

// Remove travel order from modal
async function removeModalTravelOrder(travelOrderId) {
    // Prevent removal of the first travel order (main itinerary travel order)
    const travelOrdersListDiv = document.getElementById('modal-travel-orders-list');
    const firstItem = travelOrdersListDiv.querySelector('li:first-child');
    if (firstItem && firstItem.id === `modal-to-item-${travelOrderId}`) {
        await Swal.fire({
            icon: 'warning',
            title: 'Cannot Remove Main Travel Order',
            text: 'The main travel order from the itinerary cannot be removed.',
            confirmButtonColor: '#1e6031'
        });
        return;
    }
    
    const item = document.getElementById(`modal-to-item-${travelOrderId}`);
    if (item) {
        item.remove();
    }
    
    // Remove from additional array if present
    modalAdditionalTravelOrders = modalAdditionalTravelOrders.filter(id => id !== travelOrderId);
    
    // Track removed travel orders for API removal after form submission
    // Only track if it was originally in the itinerary (not a newly added one)
    if (modalOriginalItineraryTravelOrderIds.includes(travelOrderId.toString()) && !modalRemovedTravelOrders.includes(travelOrderId)) {
        modalRemovedTravelOrders.push(travelOrderId);
        console.log('Tracking for removal:', travelOrderId);
    }
    
    // Update count
    const totalCountSpan = document.getElementById('modal-total-travel-orders');
    const currentCount = parseInt(totalCountSpan.textContent) || 0;
    totalCountSpan.textContent = Math.max(0, currentCount- 1);
    
    // Check if list is empty
    const remainingItems = travelOrdersListDiv.querySelectorAll('li');
    if (remainingItems.length === 0) {
        travelOrdersListDiv.innerHTML = '<p class="text-muted mb-0 fst-italic small">No travel orders selected</p>';
    }
    
    // Refresh dropdown: Show the removed TO again so it can be re-added
    const currentDisplayedIds = Array.from(travelOrdersListDiv.querySelectorAll('li')).map(li => li.id.replace('modal-to-item-', ''));
    
    // Exclude only currently displayed TOs + additional travel orders
    // This allows removed TOs to reappear in the dropdown
    const excludeIds = [...currentDisplayedIds, ...modalAdditionalTravelOrders];
    
    console.log('=== AFTER REMOVAL ===');
    console.log('Removed TO ID:', travelOrderId);
    console.log('Current displayed IDs:', currentDisplayedIds);
    console.log('Original itinerary IDs (always excluded):', modalOriginalItineraryTravelOrderIds);
    console.log('Additional travel orders:', modalAdditionalTravelOrders);
    console.log('Will exclude these IDs (removed TO will show in dropdown):', excludeIds);
    console.log('Using stored date range:', modalItineraryDateFrom, 'to', modalItineraryDateTo);
    
    // IMPORTANT: Pass the stored date range to maintain date filtering!
    populateModalAdditionalTravelOrderDropdown(
        excludeIds,
       modalItineraryDateFrom,
       modalItineraryDateTo
    );
    
    // Reload passengers with remaining travel orders
    const allTravelOrderIds = [...currentDisplayedIds, ...modalAdditionalTravelOrders];
    if (allTravelOrderIds.length > 0) {
        loadModalPassengersFromMultipleTravelOrders(allTravelOrderIds);
    } else {
        // Clear passengers
        document.getElementById('modal_passenger-items').innerHTML = '';
        document.getElementById('modal_head_of_party').value = '';
        document.getElementById('modal_current-head').textContent = 'None selected';
        document.getElementById('modal_passenger_hidden_inputs_container').innerHTML = '';
    }
}

// Load passengers from multiple travel orders in modal
function loadModalPassengersFromMultipleTravelOrders(travelOrderIds) {
    if (!travelOrderIds || travelOrderIds.length === 0) {
       return;
    }
    
    const passengersListDiv = document.getElementById('modal_passenger-items');
    const headOfPartyHidden = document.getElementById('modal_head_of_party');
    const passengerHiddenInputsContainer = document.getElementById('modal_passenger_hidden_inputs_container');
    
    // Show loading state
    passengersListDiv.innerHTML = '<li class="text-muted small">Loading passengers...</li>';
    
    // Fetch passengers for each travel order
    Promise.all(travelOrderIds.map(id => {
        const url = `/api/travel-orders/${id}/passengers`;
        console.log('Fetching:', url);
       return fetch(url).then(res => {
            console.log('Response status for TO', id, ':', res.status);
           return res.json();
        }).catch(err => {
            console.error('Fetch error for TO', id, ':', err);
           throw err;
        });
    }))
    .then(results => {
        const allPassengers = [];
        const passengerMap = new Map(); // To avoid duplicates
        
           results.forEach((data, idx) => {
            console.log('Processing travel order', idx + 1);
            console.log('Data received:', data);
            if (data.passengers && Array.isArray(data.passengers)) {
                console.log('Found passengers:', data.passengers.length);
                data.passengers.forEach(passenger => {
                    console.log('Passenger name:', passenger.name);
                    // Use name as key to avoid duplicates
                    const key = passenger.name;
                    if (!passengerMap.has(key)) {
                        passengerMap.set(key, passenger);
                        allPassengers.push(passenger);
                    }
                });
            } else {
                console.warn('No passengers found in this response');
            }
        });
        
        console.log('Total passengers:', allPassengers.length);
        
        // Display passengers
        if (allPassengers.length === 0) {
            passengersListDiv.innerHTML = '<li class="text-muted small fst-italic">No passengers in selected travel orders</li>';
            headOfPartyHidden.value = '';
            if (headOfPartySelect) {
                headOfPartySelect.innerHTML = '<option value="">-- Select Head --</option>';
            }
            passengerHiddenInputsContainer.innerHTML = '';
        } else {
            let html = '';
            allPassengers.forEach((passenger, index) => {
                const name = passenger.name || passenger.full_name || 'Unknown';
                html += `<li class="d-flex justify-content-between align-items-center mb-1 p-1 rounded" style="font-size: 0.75rem;">
                    <span class="small fw-medium">${index +1}. ${name}</span>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-xs btn-warning text-white" 
                            onclick="setModalAsHeadOfParty('${name}', '${name}', this)" 
                            style="font-size: 0.65rem; padding: 1px 4px;">Set as Head</button>
                    </div>
                </li>`;
            });
            passengersListDiv.innerHTML = html;
            
            // Set first passenger as head of party by default
            const firstName = allPassengers[0].name || allPassengers[0].full_name;
            headOfPartyHidden.value = firstName;
            
            // Update the head of party display
            document.getElementById('modal_current-head').textContent = firstName;
            
            // Update hidden inputs with all passenger names as array
            const allNames = allPassengers.map(p => p.name || p.full_name);
            
            // Clear existing passenger hidden inputs
            passengerHiddenInputsContainer.innerHTML = '';
            
            // Create individual hidden input for each passenger
            allNames.forEach((name, index) => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'passenger_names[]';
                hiddenInput.value = name;
                hiddenInput.setAttribute('data-travel-order-id', travelOrderIds[0] || '');
                passengerHiddenInputsContainer.appendChild(hiddenInput);
            });
            
            // Automatically mark the first passenger as head (visual feedback)
            const firstItem = passengersListDiv.querySelector('li');
            if (firstItem) {
                firstItem.classList.add('bg-warning', 'bg-opacity-25');
                const btn = firstItem.querySelector('button');
                if (btn) {
                    btn.textContent = '✓ Head';
                    btn.disabled = true;
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-success');
                }
            }
        }
    })
    .catch(error => {
        console.error('Error fetching passengers:', error);
        passengersListDiv.innerHTML = '<li class="text-danger small">Error loading passengers</li>';
    });
}

// Event listener for head of party selection change
document.addEventListener('DOMContentLoaded', function() {
    // Store original additional travel order dropdown options on page load
    const modalAdditionalSelect = document.getElementById('modal_additional_travel_order_select');
    if (modalAdditionalSelect) {
        // Store the complete innerHTML as data attribute for later use
        modalAdditionalSelect.setAttribute('data-original-options', modalAdditionalSelect.innerHTML);
        console.log('Stored original travel order options:', modalAdditionalSelect.querySelectorAll('option').length - 1, 'options');
    }
    
    // ALSO store edit modal options
    const editAdditionalSelect = document.getElementById('edit_additional_travel_order_select');
    if (editAdditionalSelect) {
        editAdditionalSelect.setAttribute('data-original-options', editAdditionalSelect.innerHTML);
        console.log('Stored EDIT travel order options:', editAdditionalSelect.querySelectorAll('option').length - 1, 'options');
    }
});

// Store original additional travel order dropdown options on page load
function loadModalPassengersFromTravelOrder() {
    const travelOrderId = document.getElementById('modal_travel_order_id').value;
    
    if (travelOrderId) {
        // Fetch passengers from the API
        fetch(`/api/travel-orders/${travelOrderId}/passengers`)
            .then(response => response.json())
            .then(data => {
                if (data.passengers && data.passengers.length > 0) {
                    data.passengers.forEach(passenger => {
                        // Check if passenger is already selected
                        const existingItems = document.querySelectorAll('#modal_passenger-items li');
                        let alreadyAdded = false;
                        for (let item of existingItems) {
                            if (item.textContent.includes(passenger.name)) {
                                alreadyAdded = true;
                                break;
                            }
                        }
                        
                        if (!alreadyAdded) {
                            // Add the first passenger automatically
                            addModalPassengerAutomatically(passenger.name, false);
                            return;
                        }
                    });
                } else {
                    // Fallback: add the employee who created the travel order
                    const travelOrderSelect = document.getElementById('modal_travel_order_id');
                    const selectedOption = travelOrderSelect.options[travelOrderSelect.selectedIndex];
                    const optionText = selectedOption.text;
                    
                    // Extract the employee name from the option text
                    // Format: #ID - FirstName LastName - Purpose
                    const parts = optionText.split(' - ');
                    if (parts.length >= 2) {
                        const employeeName = parts[1].trim();
                        
                        if (employeeName && employeeName !== 'N/A') {
                            // Check if passenger is already selected
                            const existingItems = document.querySelectorAll('#modal_passenger-items li');
                            let alreadyAdded = false;
                            for (let item of existingItems) {
                                if (item.textContent.includes(employeeName)) {
                                    alreadyAdded = true;
                                    break;
                                }
                            }
                            
                            if (!alreadyAdded) {
                                addModalPassengerAutomatically(employeeName, false);
                            }
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error loading passengers:', error);
                
                // Fallback: add the employee who created the travel order
                const travelOrderSelect = document.getElementById('modal_travel_order_id');
                const selectedOption = travelOrderSelect.options[travelOrderSelect.selectedIndex];
                const optionText = selectedOption.text;
                
                // Extract the employee name from the option text
                // Format: #ID - FirstName LastName - Purpose
                const parts = optionText.split(' - ');
                if (parts.length >= 2) {
                    const employeeName = parts[1].trim();
                    
                    if (employeeName && employeeName !== 'N/A') {
                        // Check if passenger is already selected
                        const existingItems = document.querySelectorAll('#modal_passenger-items li');
                        let alreadyAdded = false;
                        for (let item of existingItems) {
                            if (item.textContent.includes(employeeName)) {
                                alreadyAdded = true;
                                break;
                            }
                        }
                        
                        if (!alreadyAdded) {
                            addModalPassengerAutomatically(employeeName, false);
                        }
                    }
                }
            });
    }
}

// Add passenger automatically in modal (used for itinerary creator)
function addModalPassengerAutomatically(passengerName, isHead) {
    // Check if passenger is already added
    const existingItems = document.querySelectorAll('#modal_passenger-items li');
    for (let item of existingItems) {
        if (item.textContent.includes(passengerName)) {
            if (isHead) {
                // If this is the creator and they're already added, make them head
                const headBtn = item.querySelector('button');
                if (headBtn) {
                    setModalAsHeadOfParty(passengerName, passengerName, headBtn);
                }
            }
            return;
        }
    }
    
    // Create list item with Bootstrap styling
    const listItem = document.createElement('li');
    listItem.className = 'd-flex justify-content-between align-items-center mb-1 p-1 rounded';
    listItem.style.fontSize = '0.75rem';
    
    // Create passenger info div
    const passengerInfo = document.createElement('span');
    passengerInfo.textContent = passengerName;
    passengerInfo.className = 'fw-medium';
    
    // Create buttons container
    const buttonsContainer = document.createElement('div');
    buttonsContainer.className = 'd-flex gap-1';
    
    // Create head of party button with Bootstrap styling
    const headOfPartyBtn = document.createElement('button');
    headOfPartyBtn.type = 'button';
    headOfPartyBtn.textContent = 'Set as Head';
    headOfPartyBtn.className = 'btn btn-xs btn-warning text-white';
    headOfPartyBtn.style.fontSize = '0.65rem';
    headOfPartyBtn.style.padding = '1px 4px';
    headOfPartyBtn.onclick = function() {
        setModalAsHeadOfParty(passengerName, passengerName, this);
    };
    
    // Create delete button with Bootstrap styling
    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.textContent = '×';
    deleteBtn.className = 'btn btn-xs btn-danger text-white';
    deleteBtn.style.fontSize = '0.85rem';
    deleteBtn.style.padding = '1px 5px';
    deleteBtn.style.lineHeight = '1';
    deleteBtn.onclick = function() {
        // Remove the corresponding hidden input
        const inputs = document.querySelectorAll('input[name="passenger_names[]"]');
        for (let input of inputs) {
            if (input.value === passengerName) {
                input.remove();
                break;
            }
        }
        
        // If this passenger was the head of party, clear the head
        if (document.getElementById('modal_head_of_party').value === passengerName) {
            document.getElementById('modal_head_of_party').value = '';
            document.getElementById('modal_current-head').textContent = 'None selected';
        }
        
        // Remove the list item
        this.closest('li').remove();
        
        // Refresh travel order dropdown to show the removed passenger's travel order
        refreshModalTravelOrderDropdown();
    };
    
    buttonsContainer.appendChild(headOfPartyBtn);
    buttonsContainer.appendChild(deleteBtn);
    
    listItem.appendChild(passengerInfo);
    listItem.appendChild(buttonsContainer);
    document.getElementById('modal_passenger-items').appendChild(listItem);
    
    // Add hidden input for form submission
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'passenger_names[]';
    hiddenInput.value = passengerName;
    document.getElementById('modal-passenger-list').appendChild(hiddenInput);
    
    // If this is the creator, automatically set them as head of party
    if (isHead) {
        setModalAsHeadOfParty(passengerName, passengerName, headOfPartyBtn);
    }
    
    // Refresh travel order dropdown to hide this passenger's travel order
    refreshModalTravelOrderDropdown();
}

// Set a passenger as head of party in modal
function setModalAsHeadOfParty(passengerName, passengerDisplay, element) {
    // Validate parameters to prevent errors
    if (typeof passengerDisplay === 'object' && passengerDisplay !== null) {
        // If passengerDisplay is an object (likely an HTML element), use passengerName instead
        passengerDisplay = passengerName;
    }
    
    // Set the hidden input value
    document.getElementById('modal_head_of_party').value = passengerName;
    
    // Update the display
    document.getElementById('modal_current-head').textContent = passengerDisplay || passengerName;
    
    // Visual feedback to show which passenger is the head of party
    const allPassengerItems = document.querySelectorAll('#modal_passenger-items li');
    allPassengerItems.forEach(item => {
        item.classList.remove('bg-warning', 'bg-opacity-25');
        item.style.borderLeft = '1px solid transparent';
        
        // Reset all buttons to "Set as Head"
        const btn = item.querySelector('button');
        if (btn) {
            btn.textContent = 'Set as Head';
            btn.disabled = false;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-warning');
        }
    });
    
    // Highlight the head of party item
    const headItem = element.closest('li');
    headItem.classList.add('bg-warning', 'bg-opacity-25');
    headItem.style.borderLeft = '3px solid #ffc107';
    
    // Update the button for the selected head
    const selectedBtn = element;
    selectedBtn.textContent = '✓ Head';
    selectedBtn.disabled = true;
    selectedBtn.classList.remove('btn-warning');
    selectedBtn.classList.add('btn-success');
}

// Function to refresh travel order dropdown based on selected passengers in modal
function refreshModalTravelOrderDropdown() {
    // Get all selected passenger names
    const selectedPassengers = [];
    const passengerItems = document.querySelectorAll('#modal_passenger-items li');
    passengerItems.forEach(item => {
        // Get the text content from the first span element (passenger name)
        const passengerSpan = item.querySelector('span.fw-medium');
        const passengerName = passengerSpan ? passengerSpan.textContent.trim() : item.textContent.split('×')[0].trim();
        selectedPassengers.push(passengerName);
    });
    
    // Get the travel order dropdown
    const travelOrderSelect = document.getElementById('modal_travel_order_id');
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

// ============================================
// EDIT TRIP TICKET MODAL FUNCTIONS
// ============================================

// Show edit trip ticket modal
function showEditTripTicketModal(ticketId) {
    console.log('Opening edit modal for trip ticket ID:', ticketId);
    
    // Set the form action URL
    const form = document.getElementById('editTripTicketForm');
    form.action = `/trip-tickets/${ticketId}`;
    
    // Fetch trip ticket data first
    fetch(`/api/trip-tickets/${ticketId}`)
        .then(response => response.json())
        .then(tripResponse => {
            const data = tripResponse.data || tripResponse;
            console.log('Trip Ticket data:', data);
            
            if (!data.itinerary_id) {
                alert('No itinerary found for this trip ticket');
                return;
            }
            
            // Update preview fields directly from fetched itinerary data
            const driverName = data.itinerary && data.itinerary.driver ? (data.itinerary.driver.first_name + ' ' + data.itinerary.driver.last_name) : '';
            const vehicleModel = data.itinerary && data.itinerary.vehicle ? data.itinerary.vehicle.model : '';
            const plateNumber = data.itinerary && data.itinerary.vehicle ? data.itinerary.vehicle.plate_number : '';
            const destination = data.itinerary && data.itinerary.destination || '';
            const purpose = data.itinerary && data.itinerary.purpose || '';
            const itineraryRef = data.itinerary_id ? '#'+ data.itinerary_id : '';
            
            console.log('Setting preview fields:', { driverName, vehicleModel, destination, purpose, itineraryRef });
            
            document.getElementById('edit_itinerary-reference').textContent = itineraryRef;
            document.getElementById('edit_driver-preview').textContent = driverName;
            document.getElementById('edit_vehicle-preview').textContent = vehicleModel + (plateNumber ? ' - Plate: ' + plateNumber : '');
            document.getElementById('edit_destination-preview').textContent = destination;
            document.getElementById('edit_purpose-preview').textContent = purpose;
            
            console.log('✓ Preview fields updated');
            
            // DIRECTLY load travel orders and passengers using the itinerary ID
            if (data.itinerary_id) {
                console.log('Loading travel orders for itinerary ID:', data.itinerary_id);
                loadEditDataForItinerary(data.itinerary_id);
            }
            
            // Show the modal after a short delay
            setTimeout(() => {
                const modal = new bootstrap.Modal(document.getElementById('editTripTicketModal'));
                modal.show();
                
                console.log('✅ Modal opened successfully - All data loaded!');
            }, 150);
        })
        .catch(error => {
            console.error('Error loading trip ticket data:', error);
            alert('Failed to load trip ticket data. Please try again.');
        });
}

// Close edit trip ticket modal
function closeEditTripTicketModal() {
    const modalEl = document.getElementById('editTripTicketModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
        modal.hide();
    }
}

// Start trip function for approved trip tickets
async function startTrip(ticketId) {
    const result = await Swal.fire({
        title: 'Start Trip?',
        text: "Are you sure you want to start this trip?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e6031',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Start Trip!',
        cancelButtonText: 'Cancel'
    });
    
    if (result.isConfirmed) {
        try {
            // Show loading state
            const button = event.target.closest('button');
            const originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting...';
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '{{ csrf_token() }}';
            
            const response = await fetch(`/trip-tickets/${ticketId}/start`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({})
            });
            
            const data = await response.json();
            
            // Restore button
            button.disabled = false;
            button.innerHTML = originalHtml;
            
            if (response.ok && data.success) {
                // Show success message
                await Swal.fire({
                    icon: 'success',
                    title: 'Trip Started!',
                    text: data.message || 'The trip has been started successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Reload the page to show updated status
                location.reload();
            } else {
                // Show error
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to start trip. Please try again.'
                });
            }
        } catch (error) {
            console.error('Error starting trip:', error);
            // Restore button if there was an error
            const button = event.target.closest('button');
            if (button) {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-play"></i> Start Trip';
            }
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while starting the trip. Please try again.'
            });
        }
    }
}

// ==================================================================
// EDIT TRIP TICKET MODAL FUNCTIONS (COPIED FROM CREATE MODAL)
// ==================================================================

// Direct function to load itinerary data for edit modal
function loadEditDataForItinerary(itineraryId) {
    console.log('=== loadEditDataForItinerary called ===');
    console.log('Itinerary ID:', itineraryId);
    
    // Fetch itinerary details including travel orders
    fetch(`/api/itineraries/${itineraryId}`)
        .then(response => response.json())
        .then(data => {
            console.log('=== ITINERARY DATA FETCHED ===');
            console.log('Full data:', data);
            
            if (!data.itinerary || !data.itinerary.travel_orders) {
                console.error('No itinerary or travel orders found');
                return;
            }
            
            const travelOrders = data.itinerary.travel_orders;
            console.log('Travel orders count:', travelOrders.length);
            
            if (travelOrders.length > 0) {
                // Build HTML for travel orders list
                const travelOrdersListDiv = document.getElementById('edit-travel-orders-list');
                const totalCountSpan = document.getElementById('edit-total-travel-orders');
                
                // Remove duplicates from travel orders array
                const uniqueTravelOrders = travelOrders.filter((to, index, self) => 
                    index === self.findIndex(t => t.id === to.id)
                );
                
                console.log('Original TO count:', travelOrders.length, '| After dedup:', uniqueTravelOrders.length);
                
                let html = '<ul class="list-unstyled mb-0">';
                uniqueTravelOrders.forEach((to, index) => {
                    const employeeName = to.employee?.full_name || 'N/A';
                    const destination = to.destination || 'No destination';
                    const dateFrom = to.date_from || '';
                    const departureTime = to.departure_time ? `(${to.departure_time})` : '';
                    
                    let displayText = '';
                    if (dateFrom) {
                        displayText = `${dateFrom} ${departureTime} - ${employeeName} (${destination})`;
                    } else {
                        displayText = `#${to.id} - ${employeeName} (${destination})`;
                    }
                    
                    html += `<li class="d-flex justify-content-between align-items-center mb-1 p-1 bg-white rounded border" id="edit-to-item-${to.id}">
                        <div><span class="small">${displayText}</span></div>
                        <button type="button" class="btn btn-xs btn-danger" onclick="removeEditTravelOrder('${to.id}')" style="font-size: 0.65rem; padding: 2px 6px;">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </li>`;
                });
                html += '</ul>';
                
                travelOrdersListDiv.innerHTML = html;
                totalCountSpan.textContent = uniqueTravelOrders.length;
                
                console.log('✓ Travel orders displayed:', uniqueTravelOrders.length);
                
                // Store original itinerary travel order IDs
                const excludeIds = uniqueTravelOrders.map(to => to.id.toString());
                editOriginalItineraryTravelOrderIds = excludeIds;
                
                // Get itinerary date range from the first travel order (since they should all be similar)
                let itineraryDateFrom = null;
                let itineraryDateTo = null;
                
                if (travelOrders.length > 0 && travelOrders[0].date_from) {
                    // Use the first travel order's date as reference
                    // The API might return formatted dates like "May 4, 2026" - we need to extract YYYY-MM-DD
                    const firstDateFrom = travelOrders[0].date_from;
                    const firstDateTo = travelOrders[0].date_to || travelOrders[0].date_from;
                    
                    console.log('Raw dates from API:', firstDateFrom, '|', firstDateTo);
                    
                    // Try to parse the date - handle both formats
                    function parseDate(dateStr) {
                        if (!dateStr) return null;
                        // If it's already YYYY-MM-DD format
                        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                            return dateStr;
                        }
                        // If it's a formatted date like "May 4, 2026", try to parse it
                        const months = {
                            'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04',
                            'May': '05', 'Jun': '06', 'Jul': '07', 'Aug': '08',
                            'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
                        };
                        const match = dateStr.match(/(\w+)\s+(\d{1,2}),?\s+(\d{4})/);
                        if (match) {
                            const month = months[match[1]];
                            const day = match[2].padStart(2, '0');
                            const year = match[3];
                            return `${year}-${month}-${day}`;
                        }
                        return dateStr.split('T')[0]; // Fallback
                    }
                    
                    itineraryDateFrom = parseDate(firstDateFrom);
                    itineraryDateTo = parseDate(firstDateTo);
                    
                    console.log('Parsed itinerary dates:', itineraryDateFrom, 'to', itineraryDateTo);
                }
                
                editItineraryDateFrom = itineraryDateFrom;
                editItineraryDateTo = itineraryDateTo;
                
                console.log('Itinerary date range:', itineraryDateFrom, 'to', itineraryDateTo);
                
                // Populate additional travel order dropdown with filtered options
                populateEditAdditionalTravelOrderDropdown(excludeIds, itineraryDateFrom, itineraryDateTo);
                
                // Auto-load passengers from all travel orders
                const travelOrderIds = travelOrders.map(to => to.id);
                console.log('=== LOADING PASSENGERS ===');
                console.log('Travel Order IDs:', travelOrderIds);
                console.log('Calling loadEditPassengersFromMultipleTravelOrders...');
                
                // Check if function exists
                if (typeof loadEditPassengersFromMultipleTravelOrders === 'function') {
                    console.log('✓ Function exists');
                    loadEditPassengersFromMultipleTravelOrders(travelOrderIds);
                } else {
                    console.error('✗ Function NOT DEFINED!');
                }
            } else {
                console.warn('No travel orders in itinerary');
                const travelOrdersListDiv = document.getElementById('edit-travel-orders-list');
                const totalCountSpan = document.getElementById('edit-total-travel-orders');
                travelOrdersListDiv.innerHTML = '<p class="text-muted mb-0 fst-italic small">No travel orders in this itinerary</p>';
                totalCountSpan.textContent = '0';
            }
        })
        .catch(error => {
            console.error('Error fetching itinerary:', error);
        });
}

// Store additional travel orders in edit modal
let editAdditionalTravelOrders = [];

// Store original itinerary travel order IDs (to maintain exclusion even after removal)
let editOriginalItineraryTravelOrderIds = [];

// Store itinerary date range for filtering (needed after add/remove operations)
let editItineraryDateFrom = null;
let editItineraryDateTo = null;

// Update itinerary details in edit modal
function updateEditItineraryDetails() {
    const select = document.getElementById('edit_itinerary_id');
    const selectedOption = select.options[select.selectedIndex];
    
    // ALWAYS reset everything first when selection changes
    editAdditionalTravelOrders = [];
    
    // Reset preview fields
    document.getElementById('edit_driver-preview').textContent = '';
    document.getElementById('edit_vehicle-preview').textContent = '';
    document.getElementById('edit_destination-preview').textContent = '';
    document.getElementById('edit_purpose-preview').textContent = '';
    
    // Reset travel orders list
    const travelOrdersListDiv = document.getElementById('edit-travel-orders-list');
    const totalCountSpan = document.getElementById('edit-total-travel-orders');
    travelOrdersListDiv.innerHTML = '<p class="text-muted mb-0 fst-italic small">Select an itinerary to load travel orders</p>';
    totalCountSpan.textContent = '0';
    
    // Reset passenger list and head of party
    document.getElementById('edit_passenger-items').innerHTML = '';
    document.getElementById('edit_head_of_party').value = '';
    document.getElementById('edit_passenger_hidden_inputs').value = '';
    document.getElementById('edit_current-head').textContent = 'None selected';
    
    // Reset additional travel order dropdown
    const additionalSelect = document.getElementById('edit_additional_travel_order_select');
    if (additionalSelect) {
        additionalSelect.innerHTML = '<option value="" disabled selected>-- Select to Add --</option>';
        additionalSelect.value = '';
    }
    
    // If no itinerary selected, stop here
    if (!selectedOption.value) {
        console.log('No itinerary selected - all fields cleared');
       return;
    }
    
    // New itinerary selected - load its data
    const driver = selectedOption.getAttribute('data-driver') || '';
    const vehicleModel = selectedOption.getAttribute('data-vehicle') || '';
    const plateNumber= selectedOption.getAttribute('data-plate-number') || '';
    const destination = selectedOption.getAttribute('data-destination') || '';
    const purpose = selectedOption.getAttribute('data-purpose') || '';
    
    // Format vehicle with model and plate number
    const vehicleDisplay = vehicleModel.trim() + (plateNumber ? ' - Plate: ' + plateNumber : '');
    
    document.getElementById('edit_driver-preview').textContent = driver;
    document.getElementById('edit_vehicle-preview').textContent = vehicleDisplay;
    document.getElementById('edit_destination-preview').textContent = destination;
    document.getElementById('edit_purpose-preview').textContent = purpose;
    
    // Load ALL passengers from ALL travel orders in this itinerary
    const itineraryId = selectedOption.value;
    if (itineraryId) {
        // Fetch itinerary details including travel orders
        fetch(`/api/itineraries/${itineraryId}`)
            .then(response => response.json())
            .then(data => {
                console.log('=== ITINERARY DETAILS FOR TRAVEL ORDERS ===');
                console.log('Full itinerary data:', data);
                
                if (data.itinerary && data.itinerary.travel_orders && data.itinerary.travel_orders.length > 0) {
                    // Build HTML for travel orders list with formatted dates
                    let html = '<ul class="list-unstyled mb-0">';
                    data.itinerary.travel_orders.forEach((to, index) => {
                        // Format similar to Create Itinerary modal: "Apr 3, 2026 (10:00 AM) - Employee Name (Destination)"
                        const employeeName = to.employee?.full_name || 'N/A';
                        const destination= to.destination || 'No destination';
                        const dateFrom = to.date_from || '';
                        const departureTime = to.departure_time ? `(${to.departure_time})` : '';
                        
                        // Build formatted display string
                        let displayText = '';
                        if (dateFrom) {
                            displayText = `${dateFrom} ${departureTime} - ${employeeName} (${destination})`;
                        } else {
                            displayText = `#${to.id} - ${employeeName} (${destination})`;
                        }
                        
                        html += `<li class="d-flex justify-content-between align-items-center mb-1 p-1 bg-white rounded border" id="edit-to-item-${to.id}">
                            <div><span class="small">${displayText}</span></div>
                            <button type="button" class="btn btn-xs btn-danger" onclick="removeEditTravelOrder('${to.id}')" style="font-size: 0.65rem; padding: 2px 6px;">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </li>`;
                    });
                    html += '</ul>';
                    
                    travelOrdersListDiv.innerHTML = html;
                    totalCountSpan.textContent = data.itinerary.travel_orders.length;
                    
                    console.log('Travel orders list populated with', data.itinerary.travel_orders.length, 'orders');
                    
                    // Get itinerary date range from selected option
                    const selectedOption = select.options[select.selectedIndex];
                    const itineraryDateFrom = selectedOption.getAttribute('data-date-from');
                    const itineraryDateTo = selectedOption.getAttribute('data-date-to');
                    
                    // Store date range for later use (after add/remove operations)
                    editItineraryDateFrom = itineraryDateFrom;
                    editItineraryDateTo = itineraryDateTo;
                    
                    // Populate additional travel order dropdown with remaining options (filtered by date)
                    const excludeIds = data.itinerary.travel_orders.map(to => to.id.toString());
                    console.log('=== EXCLUDING THESE TRAVEL ORDERS ===');
                    console.log('Exclude IDs (from itinerary):', excludeIds);
                    
                    // Store original itinerary travel order IDs for maintaining exclusion
                    editOriginalItineraryTravelOrderIds = excludeIds;
                    
                    populateEditAdditionalTravelOrderDropdown(
                        excludeIds,
                        itineraryDateFrom,
                        itineraryDateTo
                    );
                    
                    // Auto-load passengers from all itinerary travel orders
                    const travelOrderIds = data.itinerary.travel_orders.map(to => to.id);
                    console.log('=== AUTO-LOADING PASSENGERS ===');
                    console.log('Itinerary has', data.itinerary.travel_orders.length, 'travel orders');
                    console.log('Travel Order IDs:', travelOrderIds);
                    console.log('Calling loadEditPassengersFromMultipleTravelOrders...');
                    
                    // Test if function exists
                    if (typeof loadEditPassengersFromMultipleTravelOrders === 'function') {
                        console.log('✓ Function exists, calling now...');
                        loadEditPassengersFromMultipleTravelOrders(travelOrderIds);
                    } else {
                        console.error('✗ Function NOT FOUND!');
                    }
                } else {
                    console.warn('No travel orders found in itinerary');
                    travelOrdersListDiv.innerHTML = '<p class="text-muted mb-0 fst-italic small">No travel orders in this itinerary</p>';
                    totalCountSpan.textContent = '0';
                }
            })
            .catch(error => {
                console.error('Error fetching itinerary details:', error);
            });
    }
}

// Populate additional travel order dropdown in edit modal
function populateEditAdditionalTravelOrderDropdown(excludeIds = [], itineraryDateFrom = null, itineraryDateTo = null) {
    console.log('=== populateEditAdditionalTravelOrderDropdown ===');
    console.log('Exclude IDs:', excludeIds);
    console.log('Itinerary Date From:', itineraryDateFrom);
    console.log('Itinerary Date To:', itineraryDateTo);
    
    const additionalSelect = document.getElementById('edit_additional_travel_order_select');
    
    // Get the original options from data attribute
    const originalOptions = additionalSelect.getAttribute('data-original-options');
    if (!originalOptions) {
        console.error('No original options found!');
       return;
    }
    
    // Parse the original options and filter out excluded IDs
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = '<select>' + originalOptions + '</select>';
    const allOptions = Array.from(tempDiv.querySelectorAll('option'));
    console.log('Total original options:', allOptions.length);
    
    // Clear current options except placeholder
    additionalSelect.innerHTML = '<option value="" disabled selected>-- Select to Add --</option>';
    
    let addedCount = 0;
    let skippedCount = 0;
    
    allOptions.forEach(option => {
        const optionId = option.value;
        if (!optionId) return;
        
        // Skip if already selected (in exclude list or already added as additional)
        if (excludeIds.includes(optionId.toString()) || editAdditionalTravelOrders.includes(optionId.toString())) {
            console.log('Skipping TO', optionId, '- Already in list');
            return;
        }
        
        // Travel orders don't have itinerary_id column - itineraries reference travel_orders
        // So we skip the itinerary_id check entirely
        
        // Check date range overlap if itinerary dates are provided
        if (itineraryDateFrom && itineraryDateTo) {
            const optionDateFrom = option.getAttribute('data-date-from');
            const optionDateTo = option.getAttribute('data-date-to');
            
            console.log('Checking TO', optionId, '- Dates:', optionDateFrom, 'to', optionDateTo);
            console.log('Itinerary dates:', itineraryDateFrom, 'to', itineraryDateTo);
            
            // If travel order has dates, check for overlap
            if (optionDateFrom && optionDateTo) {
                // No overlap if: TO ends before itinerary starts OR TO starts after itinerary ends
                if (optionDateTo < itineraryDateFrom || optionDateFrom > itineraryDateTo) {
                    console.log('❌ Skipping TO', optionId, '- Date range mismatch. TO:', optionDateFrom, 'to', optionDateTo, 'Itinerary:', itineraryDateFrom, 'to', itineraryDateTo);
                    skippedCount++;
                    return; // Skip - no date overlap
                }
                console.log('✓ TO', optionId, 'has matching date range');
            } else {
                console.log('⚠️ TO', optionId, 'missing dates - skipping');
                return;
            }
            // If TO doesn't have dates but itinerary does, we still show it
            // (assuming it might be a flexible/same-day travel order)
        }
        
        // Add to dropdown
        const newOption = option.cloneNode(true);
        additionalSelect.appendChild(newOption);
        addedCount++;
        console.log('Added TO', optionId, 'to dropdown');
    });
    
    console.log('✓ Dropdown populated:', addedCount, 'added,', skippedCount, 'skipped due to date conflict');
}

// Add additional travel order in edit modal
function addEditAdditionalTravelOrder() {
    const select = document.getElementById('edit_additional_travel_order_select');
    const selectedOption = select.options[select.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select a travel order to add.',
            timer: 2000,
            showConfirmButton: false
        });
       return;
    }
    
    const travelOrderId = selectedOption.value;
    const displayText = selectedOption.textContent;
    
    // Validate: Check if travel order has an itinerary assigned (double-check before adding)
    const optionItineraryId = selectedOption.getAttribute('data-itinerary-id');
  if (optionItineraryId && optionItineraryId !== '' && optionItineraryId !== 'null') {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Travel Order',
            text: 'This travel order already has an itinerary assigned and cannot be added.',
            timer: 3000,
            showConfirmButton: true
        });
        return;
    }
    
    // Check if already added (either as additional or from original itinerary)
    if (editAdditionalTravelOrders.includes(travelOrderId)) {
        Swal.fire({
            icon: 'warning',
            title: 'Already Added',
            text: 'This travel order has already been added.',
            timer: 2000,
            showConfirmButton: false
        });
       return;
    }
    
    // Also check if it's already in the original itinerary travel orders
    const existingBadge = document.getElementById(`edit-to-item-${travelOrderId}`);
    if (existingBadge) {
        Swal.fire({
            icon: 'warning',
            title: 'Already Present',
            text: 'This travel order is already in the list.',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }
    
    // Validate seating capacity before adding
    validateEditCapacityAndAdd(travelOrderId, displayText);
}

// Validate capacity and add travel order for edit modal
async function validateEditCapacityAndAdd(travelOrderId, displayText) {
    console.log('=== VALIDATING EDIT CAPACITY ===');
    
    // Get vehicle seating capacity from the itinerary details
    // We need to fetch it from the itinerary since we don't have a dropdown anymore
    const itineraryId = document.getElementById('edit_itinerary-reference').textContent.replace('#', '').trim();
    
    if (!itineraryId) {
        console.error('No itinerary ID found');
        proceedToAddEditTravelOrder(travelOrderId, displayText);
        return;
    }
    
    console.log('Fetching itinerary details for capacity check, ID:', itineraryId);
    
    try {
        const response = await fetch(`/api/itineraries/${itineraryId}`);
        const data = await response.json();
        
        if (!data.itinerary || !data.itinerary.vehicle) {
            console.warn('No vehicle found in itinerary, skipping validation');
            proceedToAddEditTravelOrder(travelOrderId, displayText);
            return;
        }
        
        const vehicleSeatingCapacity = parseInt(data.itinerary.vehicle.seating_capacity) || 0;
        console.log('Vehicle seating capacity:', vehicleSeatingCapacity);
        
        if (vehicleSeatingCapacity === 0) {
            console.log('⚠️ Vehicle capacity is 0, skipping validation');
            proceedToAddEditTravelOrder(travelOrderId, displayText);
            return;
        }
        
        // Get all current travel order IDs
        const allTravelOrderIds = [];
        
        // Add from displayed list
        const travelOrdersListDiv = document.getElementById('edit-travel-orders-list');
        const displayedItems = travelOrdersListDiv.querySelectorAll('li');
        displayedItems.forEach(item => {
            const id = item.id.replace('edit-to-item-', '');
            allTravelOrderIds.push(id);
        });
        
        // Add additional travel orders
        allTravelOrderIds.push(...editAdditionalTravelOrders);
        
        // Add the new one we're trying to add
        allTravelOrderIds.push(travelOrderId);
        
        console.log('Total travel order IDs after adding:', allTravelOrderIds);
        
        // Fetch passenger counts
        const passengerCountPromises = allTravelOrderIds.map(toId => {
            return fetch(`/api/travel-orders/${toId}/passengers`)
                .then(response => response.json())
                .then(data => {
                    const count = data.passengers ? data.passengers.length : 1;
                    return count;
                })
                .catch(error => {
                    console.error('Error fetching passengers:', error);
                    return 1;
                });
        });
        
        const counts = await Promise.all(passengerCountPromises);
        const totalPassengers = counts.reduce((sum, count) => sum + count, 0);
        
        console.log('Total passengers:', totalPassengers);
        console.log('Vehicle capacity:', vehicleSeatingCapacity);
        
        if (totalPassengers > vehicleSeatingCapacity) {
            const message = `⚠️ Cannot Add Travel Order:\n\nTotal passengers (${totalPassengers}) would exceed vehicle seating capacity (${vehicleSeatingCapacity}).\n\nPlease remove some travel orders or select a vehicle with higher capacity.`;
            
            await Swal.fire({
                icon: 'warning',
                title: 'Capacity Exceeded',
                html: message.replace(/\n/g, '<br>'),
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            
            console.log('❌ VALIDATION FAILED - Cannot add travel order');
            return; // Don't add
        }
        
        console.log('✓ VALIDATION PASSED');
        proceedToAddEditTravelOrder(travelOrderId, displayText);
        
    } catch (error) {
        console.error('Error during validation:', error);
        // On error, allow adding
        proceedToAddEditTravelOrder(travelOrderId, displayText);
    }
}

// Proceed to add travel order after validation
function proceedToAddEditTravelOrder(travelOrderId, displayText) {
    // Add to array
    editAdditionalTravelOrders.push(travelOrderId);
    
    // Add to display list
    const travelOrdersListDiv = document.getElementById('edit-travel-orders-list');
    const totalCountSpan = document.getElementById('edit-total-travel-orders');
    
    const html = `<li class="d-flex justify-content-between align-items-center mb-1 p-1 bg-white rounded border" id="edit-to-item-${travelOrderId}">
        <div><span class="small">${displayText}</span></div>
        <button type="button" class="btn btn-xs btn-danger" onclick="removeEditTravelOrder('${travelOrderId}')" style="font-size: 0.65rem; padding: 2px 6px;">
            <i class="fas fa-times"></i> Remove
        </button>
    </li>`;
    
    // Remove "no travel orders" message if present
    const noOrdersMsg = travelOrdersListDiv.querySelector('.fst-italic');
    if (noOrdersMsg) {
        noOrdersMsg.remove();
    }
    
    // Append to list
    const ul = travelOrdersListDiv.querySelector('ul');
    if (ul) {
        ul.insertAdjacentHTML('beforeend', html);
    } else {
        travelOrdersListDiv.innerHTML = '<ul class="list-unstyled mb-0">' + html + '</ul>';
    }
    
    // Update count
    const currentCount = parseInt(totalCountSpan.textContent) || 0;
    totalCountSpan.textContent = currentCount +1;
    
    // Refresh dropdown to hide selected
    const currentExcludeIds = Array.from(travelOrdersListDiv.querySelectorAll('li')).map(li => li.id.replace('edit-to-item-', ''));
    
    console.log('=== AFTER ADDING ===');
    console.log('Added TO ID:', travelOrderId);
    console.log('Current exclude IDs:', currentExcludeIds);
    console.log('Using stored date range:', editItineraryDateFrom, 'to', editItineraryDateTo);
    
    // IMPORTANT: Pass the stored date range to maintain date filtering!
    populateEditAdditionalTravelOrderDropdown(
        currentExcludeIds,
      editItineraryDateFrom,
      editItineraryDateTo
    );
    
    // Reset dropdown
    select.value = '';
    
    // Reload passengers with all travel orders
    const allTravelOrderIds = [...currentExcludeIds, ...editAdditionalTravelOrders];
    loadEditPassengersFromMultipleTravelOrders(allTravelOrderIds);
}

// Remove travel order from edit modal
function removeEditTravelOrder(travelOrderId) {
    console.log('=== REMOVE CLICKED ===');
    console.log('Attempting to remove TO ID:', travelOrderId);
    
    const item = document.getElementById(`edit-to-item-${travelOrderId}`);
    if (!item) {
        console.error('Travel order item NOT FOUND in DOM:', travelOrderId);
        return;
    }
    
    console.log('Found item, removing...');
    item.remove();
    
    // Remove from additional array if present
    editAdditionalTravelOrders = editAdditionalTravelOrders.filter(id => id !== travelOrderId);
    
    // Update count
    const totalCountSpan = document.getElementById('edit-total-travel-orders');
    const currentCount = parseInt(totalCountSpan.textContent) || 0;
    totalCountSpan.textContent = Math.max(0, currentCount- 1);
    
    console.log('Updated count:', totalCountSpan.textContent);
    
    // Check if list is empty
    const travelOrdersListDiv = document.getElementById('edit-travel-orders-list');
    const remainingItems = travelOrdersListDiv.querySelectorAll('li');
    if (remainingItems.length === 0) {
        travelOrdersListDiv.innerHTML = '<p class="text-muted mb-0 fst-italic small">No travel orders selected</p>';
    }
    
    // Refresh dropdown: Show the removed TO again so it can be re-added
    const currentDisplayedIds = Array.from(travelOrdersListDiv.querySelectorAll('li')).map(li => li.id.replace('edit-to-item-', ''));
    
    // Exclude only currently displayed TOs + additional travel orders
    // This allows removed TOs to reappear in the dropdown
    const excludeIds = [...currentDisplayedIds, ...editAdditionalTravelOrders];
    
    console.log('=== AFTER REMOVAL ===');
    console.log('Removed TO ID:', travelOrderId);
    console.log('Current displayed IDs:', currentDisplayedIds);
    console.log('Original itinerary IDs (always excluded):', editOriginalItineraryTravelOrderIds);
    console.log('Additional travel orders:', editAdditionalTravelOrders);
    console.log('Will exclude these IDs (removed TO will show in dropdown):', excludeIds);
    console.log('Using stored date range:', editItineraryDateFrom, 'to', editItineraryDateTo);
    
    // IMPORTANT: Pass the stored date range to maintain date filtering!
    populateEditAdditionalTravelOrderDropdown(
        excludeIds,
       editItineraryDateFrom,
       editItineraryDateTo
    );
    
    // Reload passengers with remaining travel orders
    const allTravelOrderIds = [...currentDisplayedIds, ...editAdditionalTravelOrders];
    if (allTravelOrderIds.length > 0) {
        loadEditPassengersFromMultipleTravelOrders(allTravelOrderIds);
    } else {
        // Clear passengers
        document.getElementById('edit_passenger-items').innerHTML = '';
        document.getElementById('edit_head_of_party').value = '';
        document.getElementById('edit_current-head').textContent = 'None selected';
        document.getElementById('edit_passenger_hidden_inputs').value = '';
    }
    
    console.log('✅ Removal complete!');
}

// Load passengers from multiple travel orders in edit modal
function loadEditPassengersFromMultipleTravelOrders(travelOrderIds) {
    console.log('=== loadEditPassengersFromMultipleTravelOrders START ===');
    console.log('Travel Order IDs:', travelOrderIds);
    
    if (!travelOrderIds || travelOrderIds.length === 0) {
        console.warn('No travel order IDs provided');
       return;
    }
    
    const passengersListDiv = document.getElementById('edit_passenger-items');
    const headOfPartyHidden = document.getElementById('edit_head_of_party');
    const passengerHiddenInputs = document.getElementById('edit_passenger_hidden_inputs');
    
    // Show loading state
    passengersListDiv.innerHTML = '<li class="text-muted small">Loading passengers...</li>';
    
    // Fetch passengers for each travel order
    console.log('Fetching passengers from', travelOrderIds.length, 'travel orders...');
    Promise.all(travelOrderIds.map(id => {
        const url = `/api/travel-orders/${id}/passengers`;
        console.log('  Fetching:', url);
       return fetch(url).then(res => {
            console.log('  Response status for TO', id, ':', res.status);
           return res.json();
        }).catch(err => {
            console.error('  Fetch error for TO', id, ':', err);
           throw err;
        });
    }))
    .then(results => {
        console.log('=== ALL RESULTS RECEIVED ===');
        const allPassengers = [];
        const passengerMap = new Map(); // To avoid duplicates
        
           results.forEach((data, idx) => {
            console.log('Processing result', idx + 1);
            console.log('Data received:', data);
            
            if (data && data.passengers && Array.isArray(data.passengers)) {
                console.log('Found', data.passengers.length, 'passengers');
                data.passengers.forEach(passenger => {
                    console.log('  Passenger:', passenger.name);
                    // Use name as key to avoid duplicates
                    const key = passenger.name;
                    if (!passengerMap.has(key)) {
                        passengerMap.set(key, passenger);
                        allPassengers.push(passenger);
                    }
                });
            } else {
                console.warn('No passengers array in response. Data:', data);
            }
        });
        
        console.log('Total unique passengers:', allPassengers.length);
        
        // Display passengers
        if (allPassengers.length === 0) {
            console.warn('No passengers found');
            passengersListDiv.innerHTML = '<li class="text-muted small fst-italic">No passengers in selected travel orders</li>';
            if (headOfPartyHidden) headOfPartyHidden.value = '';
            if (passengerHiddenInputs) passengerHiddenInputs.value = '';
            document.getElementById('edit-total-passengers').textContent = '0';
        } else {
            console.log('Displaying', allPassengers.length, 'passengers');
            let html = '';
            allPassengers.forEach((passenger, index) => {
                const name = passenger.name || passenger.full_name || 'Unknown';
                html += `<li class="d-flex justify-content-between align-items-center mb-1 p-1 rounded" style="font-size: 0.75rem;">
                    <span class="small fw-medium">${index + 1}. ${name}</span>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-xs btn-warning text-white" 
                            onclick="setEditAsHeadOfParty('${name}', '${name}', this)" 
                            style="font-size: 0.65rem; padding: 1px 4px;">Set as Head</button>
                    </div>
                </li>`;
            });
            passengersListDiv.innerHTML = html;
            
            // Update passenger count
            document.getElementById('edit-total-passengers').textContent = allPassengers.length;
            
            // Set first passenger as head of party by default
            const firstName = allPassengers[0].name || allPassengers[0].full_name;
            if (headOfPartyHidden) headOfPartyHidden.value = firstName;
            
            // Update the head of party display
            document.getElementById('edit_current-head').textContent = firstName;
            
            // Update hidden inputs with all passenger names as array
            const allNames = allPassengers.map(p => p.name || p.full_name);
            if (passengerHiddenInputs) passengerHiddenInputs.value = allNames.join('|');
            
            // Automatically mark the first passenger as head (visual feedback)
            const firstItem = passengersListDiv.querySelector('li');
            if (firstItem) {
                firstItem.classList.add('bg-warning', 'bg-opacity-25');
                firstItem.style.borderLeft = '3px solid #ffc107';
                const btn = firstItem.querySelector('button');
                if (btn) {
                    btn.textContent = '✓ Head';
                    btn.disabled = true;
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-success');
                }
            }
            
            console.log('✓ Passengers displayed successfully');
        }
    })
    .catch(error => {
        console.error('=== ERROR LOADING PASSENGERS ===');
        console.error('Error:', error);
        passengersListDiv.innerHTML = '<li class="text-danger small">Error loading passengers: ' + error.message + '</li>';
    });
}

// Set as head of party in edit modal
function setEditAsHeadOfParty(name, value, element) {
    // Remove highlight from all items
    const allPassengerItems = document.querySelectorAll('#edit_passenger-items li');
    allPassengerItems.forEach(item => {
        item.classList.remove('bg-warning', 'bg-opacity-25');
        item.style.borderLeft = '1px solid transparent';
        
        // Reset all buttons to "Set as Head"
        const btn = item.querySelector('button');
        if (btn) {
            btn.textContent = 'Set as Head';
            btn.disabled = false;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-warning');
        }
    });
    
    // Highlight the head of party item
    const headItem = element.closest('li');
    headItem.classList.add('bg-warning', 'bg-opacity-25');
    headItem.style.borderLeft = '3px solid #ffc107';
    
    // Update the button for the selected head
    const selectedBtn = element;
    selectedBtn.textContent = '✓ Head';
    selectedBtn.disabled = true;
    selectedBtn.classList.remove('btn-warning');
    selectedBtn.classList.add('btn-success');
    
    // Update hidden input
    document.getElementById('edit_head_of_party').value = value;
    
    // Update display text
    document.getElementById('edit_current-head').textContent = name;
}

// Event listener for edit modal
function closeEditTripTicketModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('editTripTicketModal'));
    if (modal) {
        modal.hide();
    }
    // Reset form after modal is hidden
    setTimeout(() => {
        document.getElementById('editTripTicketForm').reset();
        // Clear passenger list
        document.getElementById('edit_passenger-items').innerHTML = '';
        document.getElementById('edit_head_of_party').value = '';
        document.getElementById('edit_current-head').textContent = 'None selected';
        // Clear preview fields
        document.getElementById('edit_driver-preview').value = '';
        document.getElementById('edit_vehicle-preview').value = '';
        document.getElementById('edit_destination-preview').value = '';
        document.getElementById('edit_purpose-preview').value = '';
    }, 300);
}

// Add passenger automatically in edit modal
function addEditPassengerAutomatically(passengerName, isHead) {
    // Check if passenger is already added
    const existingItems = document.querySelectorAll('#edit_passenger-items li');
    for (let item of existingItems) {
        if (item.textContent.includes(passengerName)) {
            if (isHead) {
                // If this is the creator and they're already added, make them head
                const headBtn = item.querySelector('button');
                if (headBtn) {
                    setEditAsHeadOfParty(passengerName, passengerName, headBtn);
                }
            }
            return;
        }
    }
    
    // Create list item with Bootstrap styling
    const listItem = document.createElement('li');
    listItem.className = 'd-flex justify-content-between align-items-center mb-1 p-1 rounded';
    listItem.style.fontSize = '0.75rem';
    
    // Create passenger info div
    const passengerInfo = document.createElement('span');
    passengerInfo.textContent = passengerName;
    passengerInfo.className = 'fw-medium';
    
    // Create buttons container
    const buttonsContainer = document.createElement('div');
    buttonsContainer.className = 'd-flex gap-1';
    
    // Create head of party button with Bootstrap styling
    const headOfPartyBtn = document.createElement('button');
    headOfPartyBtn.type = 'button';
    headOfPartyBtn.textContent = 'Set as Head';
    headOfPartyBtn.className = 'btn btn-xs btn-warning text-white';
    headOfPartyBtn.style.fontSize = '0.65rem';
    headOfPartyBtn.style.padding = '1px 4px';
    headOfPartyBtn.onclick = function() {
        setEditAsHeadOfParty(passengerName, passengerName, this);
    };
    
    // Create delete button with Bootstrap styling
    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.textContent = '×';
    deleteBtn.className = 'btn btn-xs btn-danger text-white';
    deleteBtn.style.fontSize = '0.85rem';
    deleteBtn.style.padding = '1px 5px';
    deleteBtn.style.lineHeight = '1';
    deleteBtn.onclick = function() {
        // Remove the corresponding hidden input
        const inputs = document.querySelectorAll('input[name="passenger_names[]"]');
        for (let input of inputs) {
            if (input.value === passengerName) {
                input.remove();
                break;
            }
        }
        
        // If this passenger was the head of party, clear the head
        if (document.getElementById('edit_head_of_party').value === passengerName) {
            document.getElementById('edit_head_of_party').value = '';
            document.getElementById('edit_current-head').textContent = 'None selected';
        }
        
        // Remove the list item
        this.closest('li').remove();
        
        // Update passenger count
        updateEditPassengerCount();
        
        // Refresh additional travel order dropdown to show the removed passenger
        refreshEditAdditionalTravelOrderDropdown();
    };
    
    buttonsContainer.appendChild(headOfPartyBtn);
    buttonsContainer.appendChild(deleteBtn);
    
    listItem.appendChild(passengerInfo);
    listItem.appendChild(buttonsContainer);
    document.getElementById('edit_passenger-items').appendChild(listItem);
    
    // Add hidden input for form submission
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'passenger_names[]';
    hiddenInput.value = passengerName;
    document.getElementById('edit-passenger-list').appendChild(hiddenInput);
    
    // Update passenger count
    updateEditPassengerCount();
    
    // If this is the creator, automatically set them as head of party
    if (isHead) {
        setEditAsHeadOfParty(passengerName, passengerName, headOfPartyBtn);
    }
    
    // Refresh additional travel order dropdown to hide this passenger
    refreshEditAdditionalTravelOrderDropdown();
}

// Update passenger count in edit modal
function updateEditPassengerCount() {
    const passengerItems = document.querySelectorAll('#edit_passenger-items li');
    document.getElementById('edit-total-passengers').textContent = passengerItems.length;
}

// Set a passenger as head of party in edit modal
function setEditAsHeadOfParty(passengerName, passengerDisplay, element) {
    // Validate parameters to prevent errors
    if (typeof passengerDisplay === 'object' && passengerDisplay !== null) {
        // If passengerDisplay is an object (likely an HTML element), use passengerName instead
        passengerDisplay = passengerName;
    }
    
    // Set the hidden input value
    document.getElementById('edit_head_of_party').value = passengerName;
    
    // Update the display
    document.getElementById('edit_current-head').textContent = passengerDisplay || passengerName;
    
    // Visual feedback to show which passenger is the head of party
    const allPassengerItems = document.querySelectorAll('#edit_passenger-items li');
    allPassengerItems.forEach(item => {
        item.classList.remove('bg-warning', 'bg-opacity-25');
        item.style.borderLeft = '1px solid transparent';
        
        // Reset all buttons to "Set as Head"
        const btn = item.querySelector('button');
        if (btn) {
            btn.textContent = 'Set as Head';
            btn.disabled = false;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-warning');
        }
    });
    
    // Highlight the head of party item
    const headItem = element.closest('li');
    headItem.classList.add('bg-warning', 'bg-opacity-25');
    headItem.style.borderLeft = '3px solid #ffc107';
    
    // Update the button for the selected head
    const selectedBtn = element;
    selectedBtn.textContent = '✓ Head';
    selectedBtn.disabled = true;
    selectedBtn.classList.remove('btn-warning');
    selectedBtn.classList.add('btn-success');
}

// Function to refresh travel order dropdown based on selected passengers in edit modal
// OLD FUNCTION - No longer used with new travel orders badge display
function refreshEditTravelOrderDropdown() {
    // This function is no longer needed as we use the new additional travel order dropdown
    // The old single travel order select has been replaced with badge display
    // Kept for backward compatibility but disabled
    return;
}

// Handle edit trip ticket form submission with SweetAlert2 loading
document.addEventListener('DOMContentLoaded', function() {
    // Store original travel order dropdown options for reset functionality
    const travelOrderSelect = document.getElementById('modal_travel_order_id');
    if (travelOrderSelect) {
        // Store the original HTML options as a data attribute
        travelOrderSelect.setAttribute('data-original-options', travelOrderSelect.innerHTML);
    }
    
    // Store original additional travel order dropdown options
    const additionalSelect = document.getElementById('modal_additional_travel_order_select');
    if (additionalSelect) {
        additionalSelect.setAttribute('data-original-options', additionalSelect.innerHTML);
    }
    
    // Handle Update button click with validation
    const updateBtn = document.getElementById('updateTripTicketBtn');
    if (updateBtn) {
        updateBtn.addEventListener('click', async function() {
            console.log('=== UPDATE BUTTON CLICKED ===');
            
            // FIRST: Validate passenger capacity against vehicle seating capacity
            console.log('Calling validateEditTripTicketCapacity...');
            const isValid = await validateEditTripTicketCapacity({ preventDefault: () => {} });
            console.log('Validation result:', isValid);
            
            if (!isValid) {
                console.log('❌ VALIDATION FAILED - Stopping submission');
                return; // Validation failed, stop here
            }
            
            console.log('✓ VALIDATION PASSED - Showing loading and submitting');
            
            // Show loading state
            Swal.fire({
                title: 'Updating Trip Ticket...',
                text: 'Please wait while we update the trip ticket.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit the form
            document.getElementById('editTripTicketForm').submit();
        });
    }
});

/**
 * Validate trip ticket passenger capacity before submission
 */
async function validateTripTicketCapacity(event) {
  console.log('=== TRIP TICKET CAPACITY VALIDATION ===');
    
  const itinerarySelect = document.getElementById('modal_itinerary_id');
    
  if (!itinerarySelect || !itinerarySelect.value) {
  console.log('No itinerary selected, returning true');
 return true;
    }
    
  const selectedOption = itinerarySelect.options[itinerarySelect.selectedIndex];
  const vehicleSeatingCapacity = parseInt(selectedOption.getAttribute('data-seating-capacity')) || 0;
    
  console.log('Vehicle seating capacity:', vehicleSeatingCapacity);
    
  if (vehicleSeatingCapacity === 0) {
  console.log('⚠️ No vehicle or capacity is 0, skipping validation');
 return true;
    }
    
   // Get all travel order IDs (main itinerary + additional)
  const allTravelOrderIds = [];
    
  // Add main itinerary travel orders
  const itineraryId = itinerarySelect.value;
  if (itineraryId) {
      try {
          const response = await fetch(`/api/itineraries/${itineraryId}`);
          const data = await response.json();
         if (data.itinerary && data.itinerary.travel_orders) {
              data.itinerary.travel_orders.forEach(to => {
                 allTravelOrderIds.push(to.id.toString());
              });
          }
      } catch(error) {
         console.error('Error fetching itinerary travel orders:', error);
      }
    }
    
   // Add additional travel orders
  allTravelOrderIds.push(...modalAdditionalTravelOrders);
    
  console.log('Total travel order IDs:', allTravelOrderIds);
    
  if (allTravelOrderIds.length === 0) {
  console.log('No travel orders selected, returning true');
 return true;
    }
    
   // Fetch passenger counts for all travel orders
  console.log('Fetching passenger counts...');
    
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
  console.log('Vehicle capacity:', vehicleSeatingCapacity);
  console.log('Total passengers:', totalPassengers);
  console.log('Individual counts:', counts);
        
  if (totalPassengers > vehicleSeatingCapacity) {
   const message = `⚠️ Validation Error:\n\nTotal passengers (${totalPassengers}) exceeds vehicle seating capacity (${vehicleSeatingCapacity}).\n\nPlease select a vehicle with higher capacity or reduce the number of travel orders.`;
     
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
   event.preventDefault();
   return false; // Prevent submission
    }
        
  console.log('✓ VALIDATION PASSED');
 return true;
    } catch (error) {
  console.error('❌ ERROR during validation:', error);
    // On error, allow submission to prevent blocking
 return true;
    }
}

/**
 * Validate edit trip ticket passenger capacity before submission
 */
async function validateEditTripTicketCapacity(event) {
  console.log('=== EDIT TRIP TICKET CAPACITY VALIDATION ===');
    
  const itinerarySelect = document.getElementById('edit_itinerary_id');
  
  // Get seating capacity from vehicle preview or stored data
  let vehicleSeatingCapacity = 0;
  
  // Try to get from vehicle preview text (fallback method)
  const vehiclePreview = document.getElementById('edit_vehicle-preview');
  if (vehiclePreview && vehiclePreview.textContent) {
      // We need to fetch the vehicle details to get seating capacity
      // Get itinerary ID from reference
      const itineraryRef = document.getElementById('edit_itinerary-reference').textContent;
      const itineraryId = itineraryRef.replace('#', '').trim();
      
      if (itineraryId) {
          console.log('Fetching itinerary details for capacity check, ID:', itineraryId);
          try {
              const response = await fetch(`/api/itineraries/${itineraryId}`);
              const data = await response.json();
              if (data.itinerary && data.itinerary.vehicle) {
                  vehicleSeatingCapacity = parseInt(data.itinerary.vehicle.seating_capacity) || 0;
              }
          } catch(error) {
              console.error('Error fetching itinerary for validation:', error);
          }
      }
  }
    
  console.log('Vehicle seating capacity:', vehicleSeatingCapacity);
    
  if (vehicleSeatingCapacity === 0) {
  console.log('⚠️ Vehicle capacity is 0, skipping validation');
 return true;
    }
    
   // Get all travel order IDs (main itinerary + additional)
  const allTravelOrderIds = [];
    
  // Add main itinerary travel orders - get from displayed badges or stored array
  const itineraryRef = document.getElementById('edit_itinerary-reference').textContent;
  const itineraryId = itineraryRef.replace('#', '').trim();
  console.log('Main itinerary ID:', itineraryId);
  
  if (itineraryId) {
      // Get travel order IDs from the displayed list
      const travelOrderBadges = document.querySelectorAll('.edit-travel-order-badge[data-to-id]');
      travelOrderBadges.forEach(badge => {
          const toId = badge.getAttribute('data-to-id');
          if (toId) {
              allTravelOrderIds.push(toId.toString());
          }
      });
      console.log('Travel orders from main itinerary:', allTravelOrderIds);
  }
    
   // Add additional travel orders
  console.log('Additional travel orders array:', editAdditionalTravelOrders);
  allTravelOrderIds.push(...editAdditionalTravelOrders);
    
  console.log('Total travel order IDs:', allTravelOrderIds);
    
  if (allTravelOrderIds.length === 0) {
  console.log('No travel orders selected, returning true');
 return true;
    }
    
   // Fetch passenger counts for all travel orders
  console.log('Fetching passenger counts...');
    
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
  console.log('Waiting for all passenger promises to resolve...');
  const counts = await Promise.all(passengerCountPromises);
  const totalPassengers = counts.reduce((sum, count) => sum + count, 0);
        
  console.log('=== VALIDATION RESULTS ===');
  console.log('Vehicle capacity:', vehicleSeatingCapacity);
  console.log('Total passengers:', totalPassengers);
  console.log('Individual counts:', counts);
        
  if (totalPassengers > vehicleSeatingCapacity) {
   console.log('❌ CAPACITY EXCEEDED!', totalPassengers, '>', vehicleSeatingCapacity);
   const message = `⚠️ Validation Error:\n\nTotal passengers (${totalPassengers}) exceeds vehicle seating capacity (${vehicleSeatingCapacity}).\n\nPlease select a vehicle with higher capacity or reduce the number of travel orders.`;
     
     // CRITICAL: Use await and don't return until user clicks OK
     console.log('Showing SweetAlert warning...');
     await Swal.fire({
      icon: 'warning',
      title: 'Validation Error',
      html: message.replace(/\n/g, '<br>'),
      confirmButtonText: 'OK',
      allowOutsideClick: false,
      allowEscapeKey: false
    });
     
    console.log('❌ VALIDATION FAILED - Alert closed, returning false to block submission');
   event.preventDefault();
   return false; // Prevent submission
    }
        
  console.log('✓ VALIDATION PASSED');
 return true;
    } catch (error) {
  console.error('❌ ERROR during validation:', error);
    // On error, allow submission to prevent blocking
 return true;
    }
}

// Handle trip ticket form submission with validation
document.getElementById('saveTripTicketBtn').addEventListener('click', async function() {
    console.log('=== CREATE TRIP TICKET BUTTON CLICKED ===');
    
    // FIRST: Validate itinerary selection
    const itinerarySelect = document.getElementById('modal_itinerary_id');
    if (!itinerarySelect || !itinerarySelect.value) {
        await Swal.fire({
            icon: 'warning',
            title: 'No Itinerary Selected',
            text: 'Please select an itinerary before creating a trip ticket.',
            confirmButtonColor: '#1e6031'
        });
        return; // Stop here if no itinerary selected
    }
    
    // SECOND: Validate trip ticket number
    const ticketNumberInput = document.getElementById('modal_ticket_number');
    if (!ticketNumberInput || !ticketNumberInput.value.trim()) {
        await Swal.fire({
            icon: 'warning',
            title: 'Trip Ticket Number Required',
            text: 'Please enter a trip ticket number.',
            confirmButtonColor: '#1e6031'
        });
        ticketNumberInput?.focus();
        return; // Stop here if no trip ticket number entered
    }
    
    // THIRD: Validate passenger capacity against vehicle seating capacity
    const isValid = await validateTripTicketCapacity({ preventDefault: () => {} });
    console.log('Validation result:', isValid);
    
   if (!isValid) {
       console.log('Validation failed, stopping submission');
       return; // Validation failed, stop here
    }
    
    try {
        // Passengers are already in individual hidden inputs, no conversion needed
        console.log('Submitting form...');
        console.log('Form action:', document.getElementById('createTripTicketForm').action);
        console.log('Form method:', document.getElementById('createTripTicketForm').method);
        
        // FIRST: Submit the trip ticket form
        const form = document.getElementById('createTripTicketForm');
        const formData = new FormData(form);
        
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            // Success! Trip ticket created
            console.log('✓ Trip ticket created successfully');
            
            // NOW: Add/Remove additional travel orders from itinerary if any
            if ((modalAdditionalTravelOrders.length > 0 || modalRemovedTravelOrders.length > 0) && modalItineraryId) {
                console.log('Processing travel order changes...');
                
                // ADD new travel orders to itinerary
                if (modalAdditionalTravelOrders.length > 0) {
                    console.log('Adding', modalAdditionalTravelOrders.length, 'additional travel orders to itinerary...');
                    
                    for (const travelOrderId of modalAdditionalTravelOrders) {
                        try {
                            const addResponse = await fetch('/trip-tickets/add-travel-order', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    itinerary_id: modalItineraryId,
                                    travel_order_id: travelOrderId
                                })
                            });
                            
                            const addResult = await addResponse.json();
                            if (!addResponse.ok || !addResult.success) {
                                console.error('Failed to add travel order', travelOrderId, 'to itinerary:', addResult.message);
                            } else {
                                console.log('✓ Added travel order', travelOrderId, 'to itinerary');
                            }
                        } catch (error) {
                            console.error('Error adding travel order to itinerary:', error);
                        }
                    }
                }
                
                // REMOVE travel orders from itinerary
                if (modalRemovedTravelOrders.length > 0) {
                    console.log('Removing', modalRemovedTravelOrders.length, 'travel orders from itinerary...');
                    
                    for (const travelOrderId of modalRemovedTravelOrders) {
                        try {
                            const removeResponse = await fetch('/trip-tickets/remove-travel-order', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    itinerary_id: modalItineraryId,
                                    travel_order_id: travelOrderId
                                })
                            });
                            
                            const removeResult = await removeResponse.json();
                            if (!removeResponse.ok || !removeResult.success) {
                                console.error('Failed to remove travel order', travelOrderId, 'from itinerary:', removeResult.message);
                            } else {
                                console.log('✓ Removed travel order', travelOrderId, 'from itinerary');
                            }
                        } catch (error) {
                            console.error('Error removing travel order from itinerary:', error);
                        }
                    }
                }
            }
            
            // Show success message
            await Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: result.message || 'Trip ticket created successfully!',
                timer: 2000,
                showConfirmButton: false
            });
            
            // Close modal and reload
            closeCreateTripTicketModal();
            location.reload();
        } else {
            // Validation failed or error
            console.error('✗ Submission failed:', result);
            
            // Display validation errors
            displayCreateTripTicketErrors(result.errors || { general: result.message || 'An error occurred' });
        }
    } catch (error) {
        console.error('ERROR during form submission:', error);
        
        // Display error
        displayCreateTripTicketErrors({ general: error.message || 'An unexpected error occurred. Please try again.' });
    }
});

/**
 * Display validation errors in the create trip ticket modal
 */
function displayCreateTripTicketErrors(errors) {
    const errorContainer = document.getElementById('createTripTicketErrors');
    
    if (!errorContainer) {
        console.error('Error container not found!');
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred. Please try again.'
        });
        return;
    }
    
    // Check for specific error types and show appropriate message
    const titleElement = errorContainer.querySelector('h6');
    const messageElement = errorContainer.querySelector('p');
    
    if (errors.ticket_number) {
        titleElement.textContent = '⚠️ Trip Ticket Number Already Exists';
        messageElement.textContent = 'Please use a different number.';
    } else if (errors.itinerary_id) {
        titleElement.textContent = '⚠️ No Itinerary Selected';
        messageElement.textContent = 'Please select an itinerary first.';
    } else if (errors.general) {
        titleElement.textContent = '⚠️ Error';
        messageElement.textContent = errors.general;
    } else {
        // Generic error
        titleElement.textContent = '⚠️ Unable to Create Trip Ticket';
        messageElement.textContent = 'Please check your inputs and try again.';
    }
    
    // Show error container
    errorContainer.style.display = 'block';
    
    // Scroll to top of modal to show errors
    const modalBody = document.querySelector('#createTripTicketForm .modal-body');
    if (modalBody) {
        modalBody.scrollTop = 0;
    }
}

// ============================================
// NEW EDIT TRIP TICKET MODAL FUNCTIONS
// ============================================

// Store selected travel order IDs for edit modal
let editSelectedTravelOrderIds = [];
let editItineraryTravelOrdersData = []; // Store itinerary travel orders data for re-rendering

// Store all available travel orders data
const editAllTravelOrdersData = @json($travelOrdersWithoutTripTicket);

// Show edit trip ticket modal - SIMPLIFIED VERSION
function showEditTripTicketModal(ticketId) {
    console.log('=== Opening Edit Trip Ticket Modal ===');
    console.log('Trip Ticket ID:', ticketId);
    
    // Set form action
    const form = document.getElementById('editTripTicketForm');
    form.action = `/trip-tickets/${ticketId}`;
    
    // Fetch trip ticket data
    fetch(`/api/trip-tickets/${ticketId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.data) {
                Swal.fire('Error', 'Failed to load trip ticket data', 'error');
                return;
            }
            
            const tripTicket = data.data;
            const itinerary = tripTicket.itinerary;
            
            if (!itinerary) {
                Swal.fire('Error', 'No itinerary found for this trip ticket', 'error');
                return;
            }
            
            console.log('Trip Ticket loaded:', tripTicket);
            console.log('Itinerary:', itinerary);
            console.log('Itinerary ID:', itinerary ? itinerary.id : 'ITINERARY IS NULL/UNDEFINED');
            
            // Populate read-only itinerary information with new design
            const itineraryId = itinerary && itinerary.id ? itinerary.id.toString() : '';
            console.log('Setting hidden field to:', itineraryId);
            document.getElementById('edit_itinerary_id_hidden').value = itineraryId;
            console.log('Hidden field value after set:', document.getElementById('edit_itinerary_id_hidden').value);
            
            // Set trip ticket number
            document.getElementById('edit_ticket_number').value = tripTicket.ticket_number || '';
            
            // Verify it was actually set
            if (!itineraryId) {
                console.error('❌ CRITICAL: itinerary.id is missing from API response!');
                console.error('Full itinerary object:', itinerary);
            } else {
                console.log('✅ itinerary_id set successfully:', itineraryId);
            }
            
            // Format date range display
            let dateDisplay = 'N/A';
            if (itinerary.date_from && itinerary.date_to) {
                const fromDate = new Date(itinerary.date_from);
                const toDate = new Date(itinerary.date_to);
                
                const fromMonth = fromDate.toLocaleString('en-US', { month: 'short' });
                const fromDay = fromDate.getDate();
                const fromYear = fromDate.getFullYear();
                
                const toMonth = toDate.toLocaleString('en-US', { month: 'short' });
                const toDay = toDate.getDate();
                const toYear = toDate.getFullYear();
                
                if (fromDate.getTime() === toDate.getTime()) {
                    dateDisplay = `${fromMonth} ${fromDay}, ${fromYear}`;
                } else if (fromMonth === toMonth && fromYear === toYear) {
                    dateDisplay = `${fromMonth} ${fromDay}-${toDay}, ${fromYear}`;
                } else if (fromYear === toYear) {
                    dateDisplay = `${fromMonth} ${fromDay} - ${toMonth} ${toDay}, ${fromYear}`;
                } else {
                    dateDisplay = `${fromMonth} ${fromDay}, ${fromYear} - ${toMonth} ${toDay}, ${toYear}`;
                }
            }
            
            // Set preview fields
            document.getElementById('edit_driver-preview').value = itinerary.driver ? 
                `${itinerary.driver.first_name} ${itinerary.driver.last_name}` : 'N/A';
            document.getElementById('edit_date-preview').value = dateDisplay;
            document.getElementById('edit_destination-preview').value = itinerary.destination || 'N/A';
            document.getElementById('edit_vehicle-preview').value = itinerary.vehicle ? 
                `${itinerary.vehicle.model}${itinerary.vehicle.plate_number ? ' - Plate: ' + itinerary.vehicle.plate_number : ''}` : 'N/A';
            document.getElementById('edit_purpose-preview').value = itinerary.purpose || 'N/A';
            
            // Load existing travel orders from itinerary
            editSelectedTravelOrderIds = [];
            
            console.log('=== CHECKING ITINERARY DATA ===');
            console.log('itinerary.travel_orders:', itinerary.travel_orders);
            console.log('Is array?', Array.isArray(itinerary.travel_orders));
            console.log('Length?', itinerary.travel_orders ? itinerary.travel_orders.length : 0);
            
            if (itinerary.travel_orders && Array.isArray(itinerary.travel_orders) && itinerary.travel_orders.length > 0) {
                editSelectedTravelOrderIds = itinerary.travel_orders.map(to => to.id.toString());
                
                // Store the travel orders data for later re-rendering
                editItineraryTravelOrdersData = itinerary.travel_orders;
                
                console.log('✅ Found', itinerary.travel_orders.length, 'travel orders');
                console.log('Travel order IDs:', editSelectedTravelOrderIds);
                
                // First render the list to create DOM elements
                renderEditSelectedTravelOrdersList();
                
                // Then update with actual data (elements now exist)
                setTimeout(() => {
                    updateTravelOrderDisplay(itinerary.travel_orders);
                }, 50);
            } else {
                console.warn('⚠️ No travel orders found in itinerary!');
                editItineraryTravelOrdersData = [];
                // Render empty list
                renderEditSelectedTravelOrdersList();
            }
            
            console.log('Existing travel order IDs:', editSelectedTravelOrderIds);
            
            // Load passengers from selected travel orders
            if (editSelectedTravelOrderIds.length > 0) {
                loadEditPassengersFromMultipleTravelOrders(editSelectedTravelOrderIds);
            }
            
            // Initialize the inline Add Travel Order section
            openAddTravelOrderModal();
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editTripTicketModal'));
            modal.show();
            
            console.log('✅ Modal opened successfully');
        })
        .catch(error => {
            console.error('Error loading trip ticket:', error);
            Swal.fire('Error', 'Failed to load trip ticket data: ' + error.message, 'error');
        });
}

// Close edit trip ticket modal
function closeEditTripTicketModal() {
    const modalEl = document.getElementById('editTripTicketModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
        modal.hide();
    }
}

// Render selected travel orders list in edit modal
function renderEditSelectedTravelOrdersList() {
    const listDiv = document.getElementById('edit-selected-travel-orders-list');
    const countSpan = document.getElementById('edit-total-travel-orders');
    
    console.log('=== Rendering Travel Orders List ===');
    console.log('Selected TO IDs:', editSelectedTravelOrderIds);
    
    if (editSelectedTravelOrderIds.length === 0) {
        listDiv.innerHTML = '<p class="text-muted mb-0 fst-italic small">No travel orders selected</p>';
        countSpan.textContent = '0';
        console.log('No travel orders to display');
        return;
    }
    
    let html = '<ul class="list-unstyled mb-0">';
    editSelectedTravelOrderIds.forEach((id, index) => {
        const isFirst = index === 0; // Main travel order from itinerary
        html += `<li class="d-flex justify-content-between align-items-center mb-1 p-1 bg-white rounded border" id="edit-to-item-${id}">
            <div class="flex-grow-1">
                <span class="small fw-medium text-muted">Loading...</span>
            </div>
            ${!isFirst ? `<button type="button" class="btn btn-xs btn-danger ms-2" onclick="editRemoveTravelOrder('${id}')" style="font-size: 0.65rem; padding: 2px 6px;">
                <i class="fas fa-times"></i> Remove
            </button>` : ''}
        </li>`;
    });
    html += '</ul>';
    
    listDiv.innerHTML = html;
    countSpan.textContent = editSelectedTravelOrderIds.length;
    
    console.log('✅ Travel orders list rendered with', editSelectedTravelOrderIds.length, 'items');
}

// Function to update travel order display with actual data
function updateTravelOrderDisplay(travelOrders) {
    console.log('=== UPDATING TRAVEL ORDER DISPLAY ===');
    console.log('Travel orders data:', travelOrders);
    
    if (!travelOrders || !Array.isArray(travelOrders) || travelOrders.length === 0) {
        console.error('❌ No travel orders data provided or empty array');
        return;
    }
    
    console.log('Updating', travelOrders.length, 'travel order(s)...');
    
    travelOrders.forEach((to, index) => {
        const id = to.id;
        console.log(`[${index + 1}/${travelOrders.length}] Processing TO ID:`, id);
        
        // Extract data safely
        const employeeName = to.employee?.full_name || to.employee_name || 'Unknown';
        const destination = to.destination || 'No destination';
        const dateFromRaw = to.date_from;
        
        console.log('  Employee:', employeeName);
        console.log('  Destination:', destination);
        console.log('  Date From Raw:', dateFromRaw);
        
        // Parse date
        let dateFrom = 'N/A';
        if (dateFromRaw) {
            try {
                // Handle different date formats
                if (typeof dateFromRaw === 'string') {
                    dateFrom = new Date(dateFromRaw).toLocaleDateString('en-US', { 
                        month: 'short', 
                        day: 'numeric', 
                        year: 'numeric' 
                    });
                } else if (dateFromRaw instanceof Date) {
                    dateFrom = dateFrom.toLocaleDateString('en-US', { 
                        month: 'short', 
                        day: 'numeric', 
                        year: 'numeric' 
                    });
                }
            } catch (e) {
                console.error('Error parsing date:', e);
                dateFrom = 'Invalid Date';
            }
        }
        
        console.log('  Date From Formatted:', dateFrom);
        
        // Try multiple times to find the element (in case of timing issues)
        let attempts = 0;
        const maxAttempts = 5;
        const intervalId = setInterval(() => {
            attempts++;
            const itemDiv = document.getElementById(`edit-to-item-${id}`);
            
            if (itemDiv) {
                clearInterval(intervalId);
                
                // Check if this is the first travel order (main itinerary TO)
                const isFirst = itemDiv === document.querySelector('#edit-selected-travel-orders-list li:first-child');
                
                const html = `
                    <div class="flex-grow-1">
                        <span class="small fw-medium">${dateFrom} - ${employeeName} (${destination})</span>
                    </div>
                    ${!isFirst ? `<button type="button" class="btn btn-xs btn-danger ms-2" onclick="editRemoveTravelOrder('${id}')" style="font-size: 0.65rem; padding: 2px 6px;">
                        <i class="fas fa-times"></i> Remove
                    </button>` : ''}
                `;
                
                itemDiv.innerHTML = html;
                console.log(`✅ [Attempt ${attempts}] Updated TO`, id, 'display successfully', isFirst ? '(Main TO - no remove button)' : '(Additional TO)');
            } else if (attempts >= maxAttempts) {
                clearInterval(intervalId);
                console.error(`❌ [Attempt ${attempts}] Element not found for TO`, id);
            } else {
                console.log(`⏳ [Attempt ${attempts}] Waiting for element...`);
            }
        }, 100); // Check every 100ms
    });
    
    console.log('✅ Travel order display update initiated!');
}

// Open Add Travel Order Modal - NOW INLINE
function openAddTravelOrderModal() {
    console.log('=== Loading Inline Add Travel Order Section ===');
    
    // Get itinerary dates from preview field
    const dateDisplay = document.getElementById('edit_date-preview').value;
    
    // Update inline itinerary dates display (if element exists)
    const inlineDateElement = document.getElementById('edit-inline-itinerary-dates');
    if (inlineDateElement) {
        inlineDateElement.textContent = dateDisplay;
    }
    
    // Refresh dropdown with filtered travel orders
    refreshEditAddTravelOrderDropdown();
}

// Refresh Add Travel Order Dropdown with filtering
function refreshEditAddTravelOrderDropdown() {
    const select = document.getElementById('edit_add_travel_order_select');
    select.innerHTML = '<option value="" disabled selected>-- Select Travel Order --</option>';
    
    // Get itinerary date range from preview field
    const dateDisplay = document.getElementById('edit_date-preview').value;
    
    if (!dateDisplay || dateDisplay === 'N/A') {
        select.innerHTML = '<option value="" disabled>No itinerary dates</option>';
        return;
    }
    
    // Parse the date display to get start and end dates
    // Format could be: "Jan 31, 2026" or "Jan 1-2, 2026" or "Jan 31 - Feb 1, 2026" or "Dec 31, 2026 - Jan 1, 2027"
    function parseDateRange(dateStr) {
        try {
            let startDate, endDate;
            
            // Check if it contains " - " which indicates a range
            if (dateStr.includes(' - ')) {
                const parts = dateStr.split(' - ');
                // First part is start date
                const startPart = parts[0].trim();
                // Second part might be "Feb 1, 2026" or just "1, 2026" (if same month/year)
                let endPart = parts[1].trim();
                
                // If endPart doesn't have a month, prepend it from start
                if (!endPart.match(/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)/i)) {
                    const startMonth = startPart.match(/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)/i)[0];
                    endPart = startMonth + ' ' + endPart;
                }
                
                startDate = new Date(startPart);
                endDate = new Date(endPart);
            } else {
                // Single date like "Jan 31, 2026"
                startDate = new Date(dateStr);
                endDate = new Date(dateStr);
            }
            
            return { start: startDate, end: endDate };
        } catch (e) {
            console.error('Error parsing date:', e);
            return { start: null, end: null };
        }
    }
    
    const dateRange = parseDateRange(dateDisplay);
    const itineraryStart = dateRange.start;
    const itineraryEnd = dateRange.end;
    
    if (!itineraryStart || !itineraryEnd) {
        select.innerHTML = '<option value="" disabled>Error parsing dates</option>';
        return;
    }
    
    console.log('=== REFRESHING DROPDOWN ===');
    console.log('Itinerary date range:', dateDisplay);
    console.log('Parsed dates:', itineraryStart, 'to', itineraryEnd);
    console.log('Currently selected TO IDs:', editSelectedTravelOrderIds);
    console.log('All available TOs in data:', editAllTravelOrdersData.length);
    
    // Log all travel orders and their status
    editAllTravelOrdersData.forEach(toData => {
        const isSelected = editSelectedTravelOrderIds.includes(toData.id.toString());
        const hasItinerary = toData.has_itinerary;
        
        console.log(`TO #${toData.id}: ${toData.employee_name} | Selected: ${isSelected} | Has Itinerary: ${hasItinerary} | Dates: ${toData.date_from} to ${toData.date_to}`);
    });
    
    console.log('\n--- Filtering Results ---');
    
    // Filter travel orders
    editAllTravelOrdersData.forEach(toData => {
        // Skip if already selected in the list
        if (editSelectedTravelOrderIds.includes(toData.id.toString())) {
            console.log(`❌ SKIP TO #${toData.id}: Already selected`);
            return;
        }
        
        // CRITICAL: Skip if travel order already has an itinerary
        if (toData.has_itinerary) {
            console.log(`❌ SKIP TO #${toData.id}: Already has itinerary (${toData.itinerary_id || 'N/A'})`);
            return;
        }
        
        // DATE VALIDATION: Check if travel order dates overlap with itinerary dates
        if (toData.date_from && toData.date_to) {
            const toStart = new Date(toData.date_from);
            const toEnd = new Date(toData.date_to);
            
            // Set time to midnight for accurate date comparison (ignore time component)
            toStart.setHours(0, 0, 0, 0);
            toEnd.setHours(0, 0, 0, 0);
            itineraryStart.setHours(0, 0, 0, 0);
            itineraryEnd.setHours(0, 0, 0, 0);
            
            // Check overlap
            const isWithinRange = (toStart <= itineraryEnd && toEnd >= itineraryStart);
            
            if (!isWithinRange) {
                console.log(`❌ SKIP TO #${toData.id}: Dates don't match (${toData.date_from} to ${toData.date_to})`);
                return; // Skip - dates don't match
            }
        } else {
            console.log(`❌ SKIP TO #${toData.id}: No dates`);
            return; // Skip if no dates
        }
        
        // ADD TO DROPDOWN (passed all filters)
        const displayText = `${toData.formatted_date} - ${toData.employee_name} (${toData.destination})`;
        const newOption = document.createElement('option');
        newOption.value = toData.id.toString();
        newOption.textContent = displayText;
        select.appendChild(newOption);
        console.log(`✅ ADD TO #${toData.id}: ${displayText}`);
    });
    
    // Check if there are available options
    if (select.options.length === 1) {
        select.innerHTML = '<option value="" disabled>No available travel orders</option>';
    }
    
    console.log(`\n✅ DROPDOWN REFRESHED. Total options: ${select.options.length - 1}`);
}

// Add Selected Travel Order
function editAddSelectedTravelOrder() {
    const select = document.getElementById('edit_add_travel_order_select');
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
    
    // Find the travel order data from available options
    const selectedTOData = editAllTravelOrdersData.find(to => to.id.toString() === travelOrderId);
    
    // Add to selected array
    editSelectedTravelOrderIds.push(travelOrderId);
    
    // Update the stored itinerary travel orders data
    if (selectedTOData) {
        editItineraryTravelOrdersData.push(selectedTOData);
    }
    
    // Update display with fresh data
    renderEditSelectedTravelOrdersList();
    setTimeout(() => {
        updateTravelOrderDisplay(editItineraryTravelOrdersData);
    }, 50);
    
    // Reload passengers
    loadEditPassengersFromMultipleTravelOrders(editSelectedTravelOrderIds);
    
    // CRITICAL: Refresh dropdown to remove added travel order
    refreshEditAddTravelOrderDropdown();
    
    // Reset select
    select.value = '';
    
    // Show success
    Swal.fire({
        icon: 'success',
        title: 'Added!',
        text: 'Travel order added successfully.',
        timer: 1500,
        showConfirmButton: false
    });
}

// Remove Travel Order
function editRemoveTravelOrder(travelOrderId) {
    // Check if this is the first travel order (main itinerary travel order)
    const firstItem = document.querySelector('#edit-selected-travel-orders-list li:first-child');
    if (firstItem && firstItem.id === `edit-to-item-${travelOrderId}`) {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Remove Main Travel Order',
            text: 'The main travel order from the itinerary cannot be removed.',
            confirmButtonColor: '#1e6031'
        });
        return;
    }
    
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
            editSelectedTravelOrderIds = editSelectedTravelOrderIds.filter(id => id !== travelOrderId);
            
            // Remove from stored data
            editItineraryTravelOrdersData = editItineraryTravelOrdersData.filter(to => to.id.toString() !== travelOrderId);
            
            // Update display with fresh data
            renderEditSelectedTravelOrdersList();
            setTimeout(() => {
                updateTravelOrderDisplay(editItineraryTravelOrdersData);
            }, 50);
            
            // Reload passengers
            if (editSelectedTravelOrderIds.length > 0) {
                loadEditPassengersFromMultipleTravelOrders(editSelectedTravelOrderIds);
            } else {
                // Clear passengers
                document.getElementById('edit_passenger-items').innerHTML = '';
                document.getElementById('edit_head_of_party').value = '';
                document.getElementById('edit_current-head').textContent = 'None selected';
                document.getElementById('edit_passenger_hidden_inputs').value = '';
                document.getElementById('edit-total-passengers').textContent = '0';
            }
            
            // CRITICAL: Refresh dropdown to show removed travel order again
            refreshEditAddTravelOrderDropdown();
            
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

// Load passengers from multiple travel orders in edit modal (reuse existing function)
// This function already exists above, we'll keep it

// Set as head of party in edit modal (reuse existing function)
// This function already exists above, we'll keep it

// Handle edit trip ticket form submission
document.getElementById('editTripTicketForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    console.log('=== Editing Trip Ticket Form Submitted ===');
    
    // Validate travel orders
    if (editSelectedTravelOrderIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Travel Orders',
            text: 'Please add at least one travel order.',
            timer: 2000
        });
        return;
    }
    
    // Validate head of party
    const headOfParty = document.getElementById('edit_head_of_party').value;
    if (!headOfParty) {
        Swal.fire({
            icon: 'warning',
            title: 'No Head of Party',
            text: 'Please set a head of party.',
            timer: 2000
        });
        return;
    }
    
    // Prepare form data
    const formData = new FormData(this);
    
    // DEBUG: Check what's in the form BEFORE adding itinerary_id
    console.log('=== FORM DATA (BEFORE) ===');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}:`, value);
    }
    
    // Make sure itinerary_id is in the form data
    const itineraryIdElement = document.getElementById('edit_itinerary_id_hidden');
    console.log('🔍 Hidden input element:', itineraryIdElement);
    console.log('🔍 Hidden input value:', itineraryIdElement ? itineraryIdElement.value : 'ELEMENT NOT FOUND');
    
    const itineraryId = itineraryIdElement ? itineraryIdElement.value : '';
    
    if (!itineraryId) {
        console.error('❌ ERROR: itinerary_id is empty!');
    } else {
        console.log('✅ itinerary_id value:', itineraryId);
    }
    
    // ALWAYS set itinerary_id to ensure it's sent
    formData.set('itinerary_id', itineraryId);
    
    console.log('\n=== FORM DATA (AFTER) ===');
    for (let [key, value] of formData.entries()) {
        console.log(`✓ ${key}:`, value);
    }
    
    // Verify itinerary_id is now in the form data
    if (formData.get('itinerary_id')) {
        console.log('✅ itinerary_id successfully added to FormData:', formData.get('itinerary_id'));
    } else {
        console.error('❌ FAILED: itinerary_id still not in FormData after set()!');
    }
    
    // Clear existing travel_order_ids and add the correct ones
    formData.delete('travel_order_ids[]');
    editSelectedTravelOrderIds.forEach(toId => {
        formData.append('travel_order_ids[]', toId);
    });
    
    // Convert pipe-separated passenger_names to array
    const passengerHidden = document.getElementById('edit_passenger_hidden_inputs');
    if (passengerHidden && passengerHidden.value) {
        // Remove the old hidden input
        passengerHidden.remove();
        
        // Create new hidden inputs for each passenger
        const passengerNames = passengerHidden.value.split('|').filter(name => name.trim() !== '');
        passengerNames.forEach(name => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'passenger_names[]';
            hiddenInput.value = name;
            this.appendChild(hiddenInput);
        });
    }
    
    console.log('Travel Order IDs being submitted:', editSelectedTravelOrderIds);
    console.log('Head of Party:', headOfParty);
    
    // Show loading state
    Swal.fire({
        title: 'Updating Trip Ticket...',
        text: 'Please wait while we update the trip ticket.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    try {
        // Submit via AJAX
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            // Close modal
            closeEditTripTicketModal();
            
            // Show success
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Trip ticket updated successfully!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Reload page
                location.reload();
            });
        } else {
            // Hide loading
            Swal.close();
            
            // Show error
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: result.message || 'Failed to update trip ticket.',
            });
        }
    } catch (error) {
        // Hide loading
        Swal.close();
        
        // Show error
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred: ' + error.message,
        });
    }
});
</script>

<!-- Passengers List Modal -->
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
// Helper function to view Excel (called from button)
function viewExcel() {
    console.log('View Excel clicked - should be handled by button onclick');
}

// Excel Modal functions
let currentExcelUrl = '';

function openExcelModal(excelUrl) {
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
            window.open(excelUrl, '_blank');
        }
    });
}

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
</script>


@endsection