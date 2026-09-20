@extends('layouts.employee')

@section('header')
    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
        {{ __('Approve Itineraries') }}
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
                    Itineraries for Approval
                </h1>

                <div class="flex items-center gap-2 w-full sm:w-auto sm:ml-auto">
                    <div class="flex flex-1 sm:flex-initial">
                        <input type="text" 
                               id="search-input" 
                               class="table-search-input form-input"
                               placeholder="Search..."
                               data-table-id="approval-table"
                               data-url="{{ route('itinerary.approvals.vp.pending') }}"
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
                Review and approve itinerary requests from employees in your office.
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
                <a href="{{ route('itinerary.approvals.vp.pending', $tabQueryParams) }}" 
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
                <a href="{{ route('itinerary.approvals.vp.pending', $tabQueryParams) }}" 
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
                <a href="{{ route('itinerary.approvals.vp.pending', $tabQueryParams) }}" 
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
                        <th class="w-[5%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">No.</th>
                        <th class="w-[20%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Employee</th>
                        <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Destination</th>
                        <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Date</th>
                        <th class="w-[20%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Purpose</th>
                        <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Remarks</th>
                        <th class="w-[10%]" style="padding: 8px 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Actions</th>
                    </tr>
                </thead>
                <tbody id="approval-table-body">
                    @forelse($itineraries as $index => $itinerary)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                            <td class="px-2 py-2 text-xs text-[#004d00] font-medium">{{ $loop->iteration }}</td>
                            <td class="px-2 py-2 text-xs text-[#004d00] font-medium max-w-xs break-words">
                                @if($itinerary->driver)
                                    <div class="font-semibold">{{ $itinerary->driver->full_name ?? 'N/A' }}</div>
                                    <div class="text-gray-600 mt-1" style="font-size: 0.7rem;">
                                        {{ $itinerary->driver->contact_num ?? 'No contact number' }}
                                    </div>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-2 py-2 text-xs text-[#006400]">{{ $itinerary->destination }}</td>
                            <td class="px-2 py-2 text-xs text-[#006400]">
                                @php
                                    $dateFrom = $itinerary->date_from instanceof \DateTimeInterface 
                                        ? \Carbon\Carbon::instance($itinerary->date_from) 
                                        : (\Carbon\Carbon::parse($itinerary->date_from) ?? null);
                                    
                                    if ($dateFrom) {
                                        echo $dateFrom->format('M j, Y');
                                    } else {
                                        echo 'N/A';
                                    }
                                @endphp
                            </td>
                            <td class="px-2 py-2 text-xs text-gray-600 max-w-xs break-words">
                                <div class="text-[#004d00] font-medium">{{ Str::limit($itinerary->purpose, 50) }}</div>
                            </td>
                            <td class="px-2 py-2 text-xs">
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
                            <td class="px-2 py-2 text-xs text-center">
                                @if(isset($tab) && ($tab == 'approved' || $tab == 'cancelled'))
                                    <!-- Only View button for approved and cancelled tabs -->
                                    <div class="action-buttons flex justify-center space-x-1">
                                        <button type="button" 
                                                onclick="loadAndShowItinerary({{ $itinerary->id }})" 
                                                class="btn view-btn border inline-flex items-center justify-center"
                                                title="View Details">
                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>
                                    </div>
                                @else
                                    <!-- View, Approve, and Reject buttons for pending tab -->
                                    <div class="action-buttons flex justify-center space-x-1">
                                        <button type="button" 
                                                onclick="loadAndShowItinerary({{ $itinerary->id }})" 
                                                class="btn view-btn border inline-flex items-center justify-center"
                                                title="View Details">
                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>
                                        <button type="button" 
                                                onclick="handleApprove('{{ route('itinerary.approvals.vp.approve', $itinerary->id) }}', '{{ $itinerary->driver?->full_name ?? 'N/A' }}')" 
                                                class="btn approve-btn border inline-flex items-center justify-center" 
                                                title="Approve">
                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Approve
                                        </button>
                                        <button type="button" 
                                                onclick="handleReject('{{ route('itinerary.approvals.vp.reject', $itinerary->id) }}', '{{ $itinerary->driver?->full_name ?? 'N/A' }}')" 
                                                class="btn reject-btn border inline-flex items-center justify-center" 
                                                title="Reject">
                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Reject
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-xs text-gray-500">
                                @if(isset($tab))
                                    @switch($tab)
                                        @case('pending')
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                <div class="mt-1">
                                                    <h3 class="text-sm font-medium text-gray-900">No itineraries pending</h3>
                                                    <p class="mt-0.5 text-xs">You don't have any itineraries pending your approval yet.</p>
                                                </div>
                                            </div>
                                            @break
                                        @case('approved')
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                <div class="mt-1">
                                                    <h3 class="text-sm font-medium text-gray-900">No approved itineraries</h3>
                                                    <p class="mt-0.5 text-xs">You don't have any approved itineraries yet.</p>
                                                </div>
                                            </div>
                                            @break
                                        @case('cancelled')
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                <div class="mt-1">
                                                    <h3 class="text-sm font-medium text-gray-900">No cancelled itineraries</h3>
                                                    <p class="mt-0.5 text-xs">You don't have any cancelled itineraries yet.</p>
                                                </div>
                                            </div>
                                            @break
                                        @default
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                <div class="mt-1">
                                                    <h3 class="text-sm font-medium text-gray-900">No itineraries found</h3>
                                                    <p class="mt-0.5 text-xs">There are no itineraries matching your criteria.</p>
                                                </div>
                                            </div>
                                    @endswitch
                                @else
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <div class="mt-1">
                                            <h3 class="text-sm font-medium text-gray-900">No itineraries pending</h3>
                                            <p class="mt-0.5 text-xs">You don't have any itineraries pending your approval yet.</p>
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
                Showing {{ $itineraries->firstItem() ?? 0 }} to {{ $itineraries->lastItem() ?? 0 }} of {{ $itineraries->total() }} itineraries
            </div>
            <div class="flex items-center space-x-2">
                <nav>
                    <ul class="pagination flex items-center">
                        <li class="page-item {{ $itineraries->currentPage() <= 1 ? 'disabled' : '' }}">
                            <a class="page-link {{ $itineraries->currentPage() <= 1 ? 'disabled-link' : '' }} text-xs px-2 py-1" href="{{ $itineraries->previousPageUrl() ? $itineraries->previousPageUrl() : '#' }}" {{ $itineraries->currentPage() <= 1 ? 'aria-disabled="true"' : '' }}>Prev</a>
                        </li>
                        
                        @php
                            $startPage = max(1, $itineraries->currentPage() - 2);
                            $endPage = min($itineraries->lastPage(), $itineraries->currentPage() + 2);
                        @endphp
                        
                        @for($i = $startPage; $i <= $endPage; $i++)
                            <li class="page-item {{ $i == $itineraries->currentPage() ? 'active' : '' }}">
                                <a class="page-link {{ $i == $itineraries->currentPage() ? 'active-link' : '' }} text-xs px-2 py-1" href="?page={{ $i }}&tab={{ $tab ?? 'pending' }}&search={{ $search ?? '' }}">{{ $i }}</a>
                            </li>
                        @endfor
                        
                        <li class="page-item {{ $itineraries->currentPage() >= $itineraries->lastPage() ? 'disabled' : '' }}">
                            <a class="page-link {{ $itineraries->currentPage() >= $itineraries->lastPage() ? 'disabled-link' : '' }} text-xs px-2 py-1" href="{{ $itineraries->nextPageUrl() ? $itineraries->nextPageUrl() : '#' }}" {{ $itineraries->currentPage() >= $itineraries->lastPage() ? 'aria-disabled="true"' : '' }}>Next</a>
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

<!-- Excel Modal -->
<script>
// Excel Modal for Itineraries - direct download
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
</script>

<!-- Include View Itinerary Modal -->
@include('itineraries.modals.view-itinerary-modal')

<script>
// Function to load and show view itinerary modal
function loadAndShowItinerary(itineraryId) {
    console.log('Loading itinerary ID:', itineraryId);
    
    // Check if the modal function exists
    if (typeof window.showViewItineraryModal !== 'function') {
        console.error('showViewItineraryModal function is not defined!');
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Modal function not available. Please refresh the page.'
        });
        return;
    }
    
    // Fetch itinerary details from API
    fetch(`/api/itineraries/${itineraryId}`)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API Response data:', data);
            if (data.success) {
                console.log('Itinerary data:', data.itinerary);
                // Show the view itinerary modal with the data
                showViewItineraryModal(data.itinerary);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to load itinerary details.'
                });
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while loading itinerary details. Please try again.'
            });
        });
}

// Handle Approve button click with SweetAlert
function handleApprove(approveUrl, driverName) {
    Swal.fire({
        title: 'Approve Itinerary?',
        html: 'Are you sure you want to approve this itinerary?<br><strong>Driver:</strong> ' + driverName,
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
                        text: data.message || 'Itinerary approved successfully.',
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
                    text: 'Failed to approve itinerary. Please try again.'
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
                        <h3 class="text-lg font-bold text-white">Reject Itinerary?</h3>
                    </div>
                </div>
                
                <!-- Modal Content -->
                <div class="px-6 py-4">
                    <div class="text-sm text-gray-800">
                        <p class="text-base font-semibold text-gray-900 mb-3">Please provide a reason for rejecting this itinerary:</p>
                        
                        <textarea id="rejectReason" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200" rows="4" placeholder="Please explain why you are rejecting this itinerary..." style="min-height: 100px; resize: vertical;"></textarea>
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
    }).then(() => {
        // Close modal before making AJAX request
        closeRejectModal(false);
        
        // After loading completes (2 seconds), make AJAX request
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
            // Show success message for 2 seconds then redirect
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Itinerary rejected by VP successfully.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Redirect to Cancelled tab immediately after success message closes
                window.location.href = window.location.pathname + '?tab=cancelled';
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to reject itinerary. Please try again.'
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
