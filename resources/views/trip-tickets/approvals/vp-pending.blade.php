@extends('layouts.employee')

@section('header')
    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
        {{ __('Approve Trip Tickets') }}
    </h2>
@endsection

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-6 pb-4 border-b border-gray-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h1 class="text-lg sm:text-xl font-bold text-[#1e6031] mb-0 whitespace-nowrap">
                    Trip Tickets for Approval
                </h1>

                <div class="flex items-center gap-2 w-full sm:w-auto sm:ml-auto">
                    <div class="flex flex-1 sm:flex-initial">
                        <input type="text" 
                               id="search-input" 
                               class="table-search-input form-input"
                               placeholder="Search employee, destination or purpose..."
                               data-table-id="approval-table"
                               data-url="{{ route('motorpool.approvals.triptickets.vp') }}"
                               value="{{ $search ?? '' }}"
                               style="height: 32px; width: 100%; min-width: 150px; max-width: 200px; font-size: 0.85rem; border: 1px solid #1e6031; border-right: none; border-radius: 0.375rem 0 0 0.375rem; padding: 0 12px;">
                        <button type="button" 
                                class="search-submit-btn"
                                style="background-color: #1e6031; color: #ffffff; border: 1px solid #1e6031; height: 32px; width: 40px; border-radius: 0 0.375rem 0.375rem 0; cursor:pointer; display: flex; align-items: center; justify-content: center;">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <p class="text-gray-600 text-sm hidden sm:block" style="margin-top: 0; line-height: 1.2;">
                Review and approve trip ticket requests from employees in your office.
            </p>
        </div>

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
        <div class="mb-4 border-b border-gray-200 overflow-x-auto">
            <nav class="flex space-x-6 min-w-max" aria-label="Tabs">
                @php
                    $searchParams = request()->get('search');
                    $tabQueryParams = ['tab' => 'pending'];
                    if($searchParams) {
                        $tabQueryParams['search'] = $searchParams;
                    }
                @endphp
                <a href="{{ route('motorpool.approvals.triptickets.vp', $tabQueryParams) }}" 
                   class="{{ (isset($tab) && $tab == 'pending') || !isset($tab) ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                   data-tab-switch="pending"
                   style="text-decoration: none;">
                    Pending
                </a>
                @php
                    $tabQueryParams = ['tab' => 'approved'];
                    if($searchParams) {
                        $tabQueryParams['search'] = $searchParams;
                    }
                @endphp
                <a href="{{ route('motorpool.approvals.triptickets.vp', $tabQueryParams) }}" 
                   class="{{ isset($tab) && $tab == 'approved' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                   data-tab-switch="approved"
                   style="text-decoration: none;">
                    Approved
                </a>
                @php
                    $tabQueryParams = ['tab' => 'cancelled'];
                    if($searchParams) {
                        $tabQueryParams['search'] = $searchParams;
                    }
                @endphp
                <a href="{{ route('motorpool.approvals.triptickets.vp', $tabQueryParams) }}" 
                   class="{{ isset($tab) && $tab == 'cancelled' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                   data-tab-switch="cancelled"
                   style="text-decoration: none;">
                    Cancelled
                </a>
            </nav>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table id="approval-table" class="min-w-full divide-y divide-gray-200">
                <thead style="background-color: #1e6031; color: white;">
                    <tr>
                        <th class="w-1/12" style="padding: 10px 12px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">No.</th>
                        <th class="w-2/12" style="padding: 10px 12px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Driver</th>
                        <th class="w-2/12" style="padding: 10px 12px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Head of the Party</th>
                        <th class="w-2/12" style="padding: 10px 12px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Vehicle</th>
                        <th class="w-2/12" style="padding: 10px 12px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Destination</th>
                        <th class="w-2/12" style="padding: 10px 12px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Date</th>
                        <th class="w-2/12" style="padding: 10px 12px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Actions</th>
                    </tr>
                </thead>
                <tbody id="approval-table-body">
                    @forelse($tripTickets as $index => $ticket)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                            <td class="px-4 py-3 text-xs text-[#004d00] font-medium">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-xs text-[#004d00] font-medium max-w-xs break-words">
                                @if($ticket->itinerary?->driver)
                                    <div class="font-semibold">{{ $ticket->itinerary->driver->full_name ?? 'N/A' }}</div>
                                    <div class="text-gray-600 mt-1" style="font-size: 0.7rem;">
                                        {{ $ticket->itinerary->driver->contact_num ?? '-' }}
                                    </div>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-[#004d00] font-medium max-w-xs break-words">
                                @php
                                    $headOfPartyName = $ticket->head_of_party ?? 'N/A';
                                    $headOfPartyContact = '-';
                                    
                                    // Try to get contact from itinerary's travel orders
                                    if ($ticket->itinerary) {
                                        $travelOrders = $ticket->itinerary->getAllTravelOrders();
                                        foreach ($travelOrders as $to) {
                                            if ($to && $to->employee && $to->employee->contact_num) {
                                                $headOfPartyContact = $to->employee->contact_num;
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                <div class="font-semibold">{{ $headOfPartyName }}</div>
                                <div class="text-gray-600 mt-1" style="font-size: 0.7rem;">
                                    {{ $headOfPartyContact }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-[#006400]">
                                @if($ticket->itinerary?->vehicle)
                                    <div class="font-semibold">{{ $ticket->itinerary->vehicle->model ?? 'N/A' }}</div>
                                    <div class="text-gray-600 mt-1" style="font-size: 0.7rem;">
                                        Plate: {{ $ticket->itinerary->vehicle->plate_number ?? 'N/A' }}
                                    </div>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-[#006400]">{{ $ticket->itinerary?->destination ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-xs text-[#006400]">
                                @php
                                    $dateFrom = $ticket->itinerary?->date_from instanceof \DateTimeInterface 
                                        ? \Carbon\Carbon::instance($ticket->itinerary->date_from) 
                                        : ($ticket->itinerary?->date_from ? \Carbon\Carbon::parse($ticket->itinerary->date_from) : null);
                                    
                                    $dateTo = $ticket->itinerary?->date_to instanceof \DateTimeInterface 
                                        ? \Carbon\Carbon::instance($ticket->itinerary->date_to) 
                                        : ($ticket->itinerary?->date_to ? \Carbon\Carbon::parse($ticket->itinerary->date_to) : null);
                                    
                                    if ($dateFrom && $dateTo) {
                                        // Check if dates are the same
                                        if ($dateFrom->isSameDay($dateTo)) {
                                            echo $dateFrom->format('M d, Y');
                                        } else {
                                            // Check if same month and year
                                            if ($dateFrom->format('F Y') === $dateTo->format('F Y')) {
                                                echo $dateFrom->format('M j') . '-' . $dateTo->format('j, Y');
                                            } else {
                                                // Different months or years
                                                echo $dateFrom->format('M d') . ' - ' . $dateTo->format('M d, Y');
                                            }
                                        }
                                    } elseif ($dateFrom) {
                                        echo $dateFrom->format('M d, Y');
                                    } else {
                                        echo 'N/A';
                                    }
                                @endphp
                            </td>
                            <td class="px-4 py-3 text-xs text-center">
                                @if($ticket->status === 'Pending')
                                    <!-- View, Approve, and Reject buttons for pending tickets -->
                                    <div class="action-buttons flex justify-center space-x-1">
                                        <button type="button" 
                                                onclick="loadAndShowTripTicket({{ $ticket->id }})" 
                                                class="btn view-btn border inline-flex items-center justify-center"
                                                title="View Details">
                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>
                                        <button type="button" 
                                                onclick="handleApprove('{{ route('vp.trip-tickets.approvals.approve', $ticket->id) }}', '{{ $ticket->itinerary?->driver?->full_name ?? 'N/A' }}')" 
                                                class="btn approve-btn border inline-flex items-center justify-center" 
                                                title="Approve">
                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Approve
                                        </button>
                                        <button type="button" 
                                                onclick="handleReject('{{ route('vp.trip-tickets.approvals.reject', $ticket->id) }}', '{{ $ticket->itinerary?->driver?->full_name ?? 'N/A' }}')" 
                                                class="btn reject-btn border inline-flex items-center justify-center" 
                                                title="Reject">
                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Reject
                                        </button>
                                    </div>
                                @else
                                    <!-- Only View button for non-pending tickets (approved, cancelled, completed, etc.) -->
                                    <div class="action-buttons flex justify-center space-x-1">
                                        <button type="button" 
                                                onclick="loadAndShowTripTicket({{ $ticket->id }})" 
                                                class="btn view-btn border inline-flex items-center justify-center"
                                                title="View Details">
                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-xs text-gray-500">
                                @if(isset($tab))
                                    @switch($tab)
                                        @case('pending')
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                <div class="mt-1">
                                                    <h3 class="text-sm font-medium text-gray-900">No trip tickets pending</h3>
                                                    <p class="mt-0.5 text-xs">You don't have any trip tickets pending your approval yet.</p>
                                                </div>
                                            </div>
                                            @break
                                        @case('approved')
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                <div class="mt-1">
                                                    <h3 class="text-sm font-medium text-gray-900">No approved trip tickets</h3>
                                                    <p class="mt-0.5 text-xs">You don't have any approved trip tickets yet.</p>
                                                </div>
                                            </div>
                                            @break
                                        @case('cancelled')
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                <div class="mt-1">
                                                    <h3 class="text-sm font-medium text-gray-900">No cancelled trip tickets</h3>
                                                    <p class="mt-0.5 text-xs">You don't have any cancelled trip tickets yet.</p>
                                                </div>
                                            </div>
                                            @break
                                        @default
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                <div class="mt-1">
                                                    <h3 class="text-sm font-medium text-gray-900">No trip tickets found</h3>
                                                    <p class="mt-0.5 text-xs">There are no trip tickets matching your criteria.</p>
                                                </div>
                                            </div>
                                    @endswitch
                                @else
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <div class="mt-1">
                                            <h3 class="text-sm font-medium text-gray-900">No trip tickets pending</h3>
                                            <p class="mt-0.5 text-xs">You don't have any trip tickets pending your approval yet.</p>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="flex items-center justify-between gap-2 mt-2" id="pagination-container">
            <div class="text-xs sm:text-sm text-gray-600 whitespace-nowrap" id="pagination-info">
                Showing {{ $tripTickets->firstItem() ?? 0 }} to {{ $tripTickets->lastItem() ?? 0 }} of {{ $tripTickets->total() }} trip tickets
            </div>
            <div class="flex items-center space-x-2">
                <nav>
                    <ul class="pagination flex items-center">
                        <li class="page-item {{ $tripTickets->currentPage() <= 1 ? 'disabled' : '' }}">
                            <a class="page-link {{ $tripTickets->currentPage() <= 1 ? 'disabled-link' : '' }} text-xs px-2 py-1" href="{{ $tripTickets->previousPageUrl() ? $tripTickets->previousPageUrl() : '#' }}" {{ $tripTickets->currentPage() <= 1 ? 'aria-disabled="true"' : '' }}>Prev</a>
                        </li>
                        
                        @php
                            $startPage = max(1, $tripTickets->currentPage() - 2);
                            $endPage = min($tripTickets->lastPage(), $tripTickets->currentPage() + 2);
                        @endphp
                        
                        @for($i = $startPage; $i <= $endPage; $i++)
                            <li class="page-item {{ $i == $tripTickets->currentPage() ? 'active' : '' }}">
                                <a class="page-link {{ $i == $tripTickets->currentPage() ? 'active-link' : '' }} text-xs px-2 py-1" href="?page={{ $i }}&tab={{ $tab ?? 'pending' }}&search={{ $search ?? '' }}">{{ $i }}</a>
                            </li>
                        @endfor
                        
                        <li class="page-item {{ $tripTickets->currentPage() >= $tripTickets->lastPage() ? 'disabled' : '' }}">
                            <a class="page-link {{ $tripTickets->currentPage() >= $tripTickets->lastPage() ? 'disabled-link' : '' }} text-xs px-2 py-1" href="{{ $tripTickets->nextPageUrl() ? $tripTickets->nextPageUrl() : '#' }}" {{ $tripTickets->currentPage() >= $tripTickets->lastPage() ? 'aria-disabled="true"' : '' }}>Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<style>
.pagination {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.pagination .page-link {
    color: #1e6031 !important;
    border-color: #1e6031 !important;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    display: block;
    text-decoration: none;
    background-color: #fff !important;
    border: 1px solid #1e6031;
    border-radius: 0.25rem;
}

.pagination .page-link:hover {
    background-color: #1e6031 !important;
    border-color: #1e6031 !important;
    color: white !important;
}

.pagination .active .page-link {
    background-color: #1e6031 !important;
    border-color: #1e6031 !important;
    color: white !important;
}

.page-item:first-child .page-link,
.page-item:last-child .page-link {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: #1e6031 !important;
    border-color: #1e6031 !important;
    color: white !important;
    border-radius: 0.25rem;
}

.page-item:first-child .page-link:hover,
.page-item:last-child .page-link:hover {
    background-color: #164f2a !important;
}

.page-item.disabled .page-link {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #6c757d;
    cursor: not-allowed;
}

.pagination .disabled-link {
    pointer-events: none;
    opacity: 0.6;
}

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

.action-buttons .approve-btn {
    color: #198754 !important;
    border: 1px solid #198754 !important;
    background-color: transparent !important;
}

.action-buttons .approve-btn:hover {
    background-color: #198754 !important;
    color: #fff !important;
    border-color: #198754 !important;
}

.action-buttons .reject-btn {
    color: #dc3545 !important;
    border: 1px solid #dc3545 !important;
    background-color: transparent !important;
}

.action-buttons .reject-btn:hover {
    background-color: #dc3545 !important;
    color: #fff !important;
    border-color: #dc3545 !important;
}
</style>

<script src="{{ asset('js/table-search.js') }}"></script>
<!-- SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Simple Trip Ticket View Modal (Inline) -->
<div id="tripTicketModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 transition-all duration-200" onclick="closeTripTicketModalOnOutsideClick(event)">
  <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0" id="tripTicketModalContent" onclick="event.stopPropagation();">
    
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
        <h3 class="text-lg font-bold text-white">Trip Ticket Details</h3>
      </div>
  
    </div>

    <!-- Content -->
    <div class="px-6 py-3">
      <div class="text-sm text-gray-800">
        <!-- Trip Information Section -->
        <div class="mb-3">
          <h4 class="font-bold text-gray-900 border-b pb-1.5 mb-2 text-base">Trip Information</h4>
          <table class="w-full">
            <tbody>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Driver:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_driver"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Contact Number:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_driver_contact"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Vehicle:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_vehicle"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Departure Date:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_departure_date"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Departure Time:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_departure_time"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Destination:</td>
                <td class="text-gray-900 py-0.5 align-top whitespace-pre-wrap" id="view_destination"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Purpose:</td>
                <td class="text-gray-900 py-0.5 align-top whitespace-pre-wrap" id="view_purpose"></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Employee Information Section -->
        <div>
          <h4 class="font-bold text-gray-900 border-b pb-1.5 mb-2 text-base">Employee Information</h4>
          <table class="w-full">
            <tbody>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Head of Party:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_head_of_party"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Contact Number:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_employee_contact"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Passengers:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_passengers"></td>
              </tr>
              <tr class="border-t pt-2 mt-1">
                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Status:</td>
                <td class="py-0.5 align-top">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" id="view_status"></span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2">
      <button type="button" 
              onclick="openPdfInModal()" 
              class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
        <i class="fas fa-file-pdf mr-1"></i> View PDF
      </button>
      <button type="button" class="px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5" onclick="closeTripTicketModal()">
        Close
      </button>
    </div>
  </div>
</div>

<!-- PDF Modal -->
<script>
// Function to load and show view trip ticket modal
function loadAndShowTripTicket(ticketId) {
    // Fetch trip ticket details from API
    fetch(`/api/trip-tickets/${ticketId}`)
        .then(response => response.json())
        .then(data => {
            console.log('API Response:', data);
            if (data.success && data.data) {
                // Show the view trip ticket modal with the data
                showViewTripTicketModal(data.data);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to load trip ticket details.'
                });
            }
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
window.showViewTripTicketModal = function(ticketData) {
    console.log('Ticket Data:', ticketData);
    
    const itinerary = ticketData.itinerary || {};
    const travelOrder = itinerary.travel_order || itinerary.travelOrder || {};
    const employee = travelOrder.employee || {};
    const vehicle = itinerary.vehicle || {};
    const driver = itinerary.driver || {};
    
    // Trip Information Section
    document.getElementById('view_driver').textContent = driver.full_name || 'N/A';
    
    // Driver Contact Number
    document.getElementById('view_driver_contact').textContent = driver.contact_num || '-';
    
    // Vehicle (Model - Plate Number)
    document.getElementById('view_vehicle').textContent = `${vehicle.model || ''} - ${vehicle.plate_number || ''}`.trim() || 'N/A';
    
    // Departure Date (with date range logic)
    let departureDateDisplay = 'N/A';
    if (itinerary.date_from && itinerary.date_to) {
        const dateFrom = new Date(itinerary.date_from);
        const dateTo = new Date(itinerary.date_to);
        const sameDay = dateFrom.toDateString() === dateTo.toDateString();
        
        if (sameDay) {
            // Same day: just show date_from
            departureDateDisplay = dateFrom.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        } else {
            // Different days - check if same month and year
            const fromMonthYear = dateFrom.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const toMonthYear = dateTo.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            
            if (fromMonthYear === toMonthYear) {
                // Same month/year: Jan 1-2, 2026
                const fromDay = dateFrom.getDate();
                const toDay = dateTo.getDate();
                const year = dateTo.getFullYear();
                const month = dateFrom.toLocaleDateString('en-US', { month: 'short' });
                departureDateDisplay = `${month} ${fromDay}-${toDay}, ${year}`;
            } else {
                // Different months or years: Dec 31, 2025 - Jan 1, 2026
                const fromFormatted = dateFrom.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                const toFormatted = dateTo.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                departureDateDisplay = `${fromFormatted} - ${toFormatted}`;
            }
        }
    } else if (itinerary.date_from) {
        // Only date_from available
        departureDateDisplay = new Date(itinerary.date_from).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }
    document.getElementById('view_departure_date').textContent = departureDateDisplay;
    
    // Departure Time (format as 9:00 AM or 1:30 PM)
    let departureTimeDisplay = 'N/A';
    if (itinerary.departure_time) {
        // Parse the time (assuming format like "09:00:00" or "09:00")
        const timeParts = itinerary.departure_time.split(':');
        if (timeParts.length >= 2) {
            let hours = parseInt(timeParts[0]);
            const minutes = timeParts[1];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // hour '0' should be '12'
            const minutesInt = parseInt(minutes);
            departureTimeDisplay = `${hours}:${minutesInt.toString().padStart(2, '0')} ${ampm}`;
        }
    }
    document.getElementById('view_departure_time').textContent = departureTimeDisplay;
    
    // Destination
    document.getElementById('view_destination').textContent = itinerary.destination || 'N/A';
    
    // Purpose
    document.getElementById('view_purpose').textContent = itinerary.purpose || 'N/A';
    
    // Employee Information Section
    // Head of Party
    document.getElementById('view_head_of_party').textContent = ticketData.head_of_party || 'Not specified';
    
    // Contact Number - Try to get from first travel order's employee
    let contactNum = '-';
    if (itinerary && itinerary.travel_orders && Array.isArray(itinerary.travel_orders) && itinerary.travel_orders.length > 0) {
        const firstTravelOrder = itinerary.travel_orders[0];
        if (firstTravelOrder && firstTravelOrder.employee) {
            // Try employee contact_num first
            if (firstTravelOrder.employee.contact_num) {
                contactNum = firstTravelOrder.employee.contact_num;
            }
            // Fallback to employee's user contact_num
            else if (firstTravelOrder.employee.user && firstTravelOrder.employee.user.contact_num) {
                contactNum = firstTravelOrder.employee.user.contact_num;
            }
        }
    }
    // Legacy support for single travel_order
    else if (employee && employee.contact_num) {
        contactNum = employee.contact_num;
    } else if (travelOrder && travelOrder.contact_num) {
        contactNum = travelOrder.contact_num;
    }
    document.getElementById('view_employee_contact').textContent = contactNum;
    
    // Passengers - Format as bulleted list (including head of party)
    const passengersElement = document.getElementById('view_passengers');
    const passengers = ticketData.passengers || [];
    
    if (Array.isArray(passengers) && passengers.length > 0) {
        // Filter out only null/empty values, but keep head of party
        const validPassengers = passengers.filter(p => p && p.trim() !== '');
        
        if (validPassengers.length > 0) {
            let passengersHtml = '<ul class="list-disc pl-5 space-y-1 text-sm text-gray-900">';
            validPassengers.forEach(passenger => {
                passengersHtml += `<li>${passenger}</li>`;
            });
            passengersHtml += '</ul>';
            passengersElement.innerHTML = passengersHtml;
        } else {
            passengersElement.textContent = 'No passengers listed';
            passengersElement.className = 'text-gray-500 italic text-sm';
        }
    } else {
        passengersElement.textContent = 'No passengers listed';
        passengersElement.className = 'text-gray-500 italic text-sm';
    }
    
    // Format status with badge
    const statusElement = document.getElementById('view_status');
    let statusText = ticketData.status || 'N/A';
    let statusClass = 'bg-gray-100 text-gray-800 border border-gray-300';
    
    if (statusText === 'Approved') {
        statusClass = 'bg-green-100 text-green-800 border border-green-300';
    } else if (statusText === 'Pending') {
        statusClass = 'bg-yellow-100 text-yellow-800 border border-yellow-300';
    } else if (statusText === 'Cancelled') {
        statusClass = 'bg-red-100 text-red-800 border border-red-300';
    }
    
    statusElement.textContent = statusText;
    statusElement.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ' + statusClass;
    
    // Show the modal
    const modal = document.getElementById('tripTicketModal');
    const modalContent = document.getElementById('tripTicketModalContent');
    
    // Store ticket ID for PDF viewing
    modal.setAttribute('data-ticket-id', ticketData.id);
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
};

// Function to close modal
function closeTripTicketModal() {
    const modal = document.getElementById('tripTicketModal');
    const modalContent = document.getElementById('tripTicketModalContent');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Function to close modal when clicking outside
function closeTripTicketModalOnOutsideClick(event) {
    if (event.target === document.getElementById('tripTicketModal')) {
        closeTripTicketModal();
    }
}

// Function to open PDF - direct download/view without modal
function openPdfInModal() {
    const modal = document.getElementById('tripTicketModal');
    const ticketId = modal.getAttribute('data-ticket-id');
    
    if (!ticketId) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Unable to load PDF. Please try again.'
        });
        return;
    }
    
    // Close the details modal first
    closeTripTicketModal();
    
    // Open PDF directly in new tab (no modal)
    const pdfUrl = '/trip-tickets/pdf/' + ticketId;
    window.open(pdfUrl, '_blank');
}

// Legacy function - kept for compatibility
function viewTripTicketPDF() {
    openPdfInModal();
}

// Handle Approve button click with SweetAlert
function handleApprove(approveUrl, driverName) {
    Swal.fire({
        title: 'Approve Trip Ticket?',
        html: 'Are you sure you want to approve this trip ticket?<br><strong>Driver:</strong> ' + driverName,
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
            // Show loading SweetAlert for exactly 2 seconds
            Swal.fire({
                title: 'Approving...',
                text: 'Please wait while we process your request',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                timer: 2000, // Exactly 2 seconds
                didOpen: () => {
                    Swal.showLoading();
                }
            }).then(() => {
                // After 2 seconds loading, make AJAX request
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
                    // Show success message for exactly 2 seconds
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Trip ticket approved successfully.',
                        timer: 2000, // Exactly 2 seconds
                        showConfirmButton: false
                    }).then(() => {
                        // Redirect after success message closes
                        const url = new URL(window.location.href);
                        url.searchParams.set('tab', 'approved');
                        window.location.href = url.toString();
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to approve trip ticket. Please try again.'
                    });
                });
            });
        }
    });
}

// Handle Reject button click with custom modal
function handleReject(rejectUrl, driverName) {
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
                        <h3 class="text-lg font-bold text-white">Reject Trip Ticket?</h3>
                    </div>
                </div>
                
                <!-- Modal Content -->
                <div class="px-6 py-4">
                    <div class="text-sm text-gray-800">
                        <p class="text-base font-semibold text-gray-900 mb-3">Please provide a reason for rejecting this trip ticket:</p>
                        
                        <textarea id="rejectReason" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200" rows="4" placeholder="Please explain why you are rejecting this trip ticket..." style="min-height: 100px; resize: vertical;"></textarea>
                        <p class="text-xs text-gray-500 mt-2"><i class="fas fa-exclamation-triangle"></i> This reason will be sent to the driver for their reference.</p>
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
    
    // Show loading SweetAlert for exactly 2 seconds
    Swal.fire({
        title: 'Rejecting...',
        text: 'Please wait while we process your request',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        timer: 2000, // Exactly 2 seconds
        didOpen: () => {
            Swal.showLoading();
        }
    }).then(() => {
        // After 2 seconds loading, close modal and make AJAX request
        closeRejectModal(false);
        
        fetch(rejectUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin',
            body: JSON.stringify({ remarks: reason })
        })
        .then(response => response.json())
        .then(data => {
            // Show success message for exactly 2 seconds
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Trip ticket rejected successfully.',
                timer: 2000, // Exactly 2 seconds
                showConfirmButton: false
            }).then(() => {
                // Redirect after success message closes
                const url = new URL(window.location.href);
                url.searchParams.set('tab', 'cancelled');
                window.location.href = url.toString();
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to reject trip ticket. Please try again.'
            });
        });
    });
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
        
        // Wait for animation to complete before hiding
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
</script>

@endsection