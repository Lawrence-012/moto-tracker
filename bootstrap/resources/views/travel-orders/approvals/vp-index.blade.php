@extends('layouts.employee')

@section('header')
    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
        {{ __('Approve Travel Orders') }}
    </h2>
@endsection

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-6 pb-4 border-b border-gray-200">
            <!-- ROW 1 -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <!-- Title -->
                <h1 class="text-lg sm:text-xl font-bold text-[#1e6031] mb-0 whitespace-nowrap">
                    Travel Orders for Approval
                </h1>

                <!-- Search Bar -->
                <div class="flex items-center gap-2 w-full sm:w-auto sm:ml-auto">
                    <div class="flex flex-1 sm:flex-initial">
                        <input type="text" 
                               id="search-input" 
                               class="table-search-input form-input"
                               placeholder="Search..."
                               data-table-id="approval-table"
                               data-url="{{ route('travel-orders.approvals.vp') }}"
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
                Review and approve travel requests from employees in your office.
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
                    <a href="{{ route('travel-orders.approvals.vp', ['tab' => 'pending']) }}" 
                       class="{{ (isset($tab) && $tab == 'pending') || !isset($tab) ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                       data-tab-switch="pending"
                       style="text-decoration: none;">
                        Pending
                    </a>
                    <a href="{{ route('travel-orders.approvals.vp', ['tab' => 'approved']) }}" 
                       class="{{ isset($tab) && $tab == 'approved' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                       data-tab-switch="approved"
                       style="text-decoration: none;">
                        Approved
                    </a>
                    <a href="{{ route('travel-orders.approvals.vp', ['tab' => 'cancelled']) }}" 
                       class="{{ isset($tab) && $tab == 'cancelled' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                       data-tab-switch="cancelled"
                       style="text-decoration: none;">
                        Cancelled
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">
            <table id="approval-table" class="min-w-full divide-y divide-gray-200">
                <thead style="background-color: #1e6031; color: white;">
                    <tr>
                        <th class="w-[5%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">No.</th>
                        <th class="w-[20%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Employee</th>
                        <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Destination</th>
                        <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Date</th>
                        <th class="w-[20%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Purpose</th>
                        <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Remarks</th>
                        <th class="w-[10%]" style="padding: 8px 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Actions</th>
                    </tr>
                </thead>
                <tbody id="approval-table-body">
                    @include('travel-orders.approvals.partials.table-rows', ['travelOrders' => $travelOrders, 'tab' => $tab ?? 'pending', 'approvalShowRoute' => 'travel-orders.approval-show.vp'])
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="flex items-center justify-between gap-2 mt-2" id="pagination-container">
            <div class="text-xs sm:text-sm text-gray-600 whitespace-nowrap" id="pagination-info">
                Showing {{ $travelOrders->firstItem() ?? 0 }} to {{ $travelOrders->lastItem() ?? 0 }} of {{ $travelOrders->total() }} travel orders
            </div>
            <div class="flex items-center space-x-1">
                <nav>
                    <ul class="pagination flex items-center">
                        <li class="page-item {{ $travelOrders->currentPage() <= 1 ? 'disabled' : '' }}">
                            <a class="page-link text-xs px-2 py-1 {{ $travelOrders->currentPage() <= 1 ? 'disabled-link' : '' }}" href="{{ $travelOrders->previousPageUrl() ? $travelOrders->previousPageUrl() : '#' }}" {{ $travelOrders->currentPage() <= 1 ? 'aria-disabled="true"' : '' }}>Prev</a>
                        </li>
                        
                        @php
                            $startPage = max(1, $travelOrders->currentPage() - 2);
                            $endPage = min($travelOrders->lastPage(), $travelOrders->currentPage() + 2);
                        @endphp
                        
                        @for($i = $startPage; $i <= $endPage; $i++)
                            <li class="page-item {{ $i == $travelOrders->currentPage() ? 'active' : '' }}">
                                <a class="page-link text-xs px-2 py-1 {{ $i == $travelOrders->currentPage() ? 'active-link' : '' }}" href="?page={{ $i }}&tab={{ $tab ?? 'pending' }}&search={{ $search ?? '' }}">{{ $i }}</a>
                            </li>
                        @endfor
                        
                        <li class="page-item {{ $travelOrders->currentPage() >= $travelOrders->lastPage() ? 'disabled' : '' }}">
                            <a class="page-link text-xs px-2 py-1 {{ $travelOrders->currentPage() >= $travelOrders->lastPage() ? 'disabled-link' : '' }}" href="{{ $travelOrders->nextPageUrl() ? $travelOrders->nextPageUrl() : '#' }}" {{ $travelOrders->currentPage() >= $travelOrders->lastPage() ? 'aria-disabled="true"' : '' }}>Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* Custom pagination styling - EXACT COPY FROM MY TRAVEL REQUESTS */
.pagination {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem; /* Space between Prev/Page/Next */
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
    border-radius: 0.25rem; /* Rounded corners like Trip Tickets */
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

/* Style Prev/Next buttons */
.page-item:first-child .page-link,
.page-item:last-child .page-link {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: #1e6031 !important;
    border-color: #1e6031 !important;
    color: white !important;
    border-radius: 0.25rem; /* Rounded corners like Trip Tickets */
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

/* Action buttons styling to match travel-orders */
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

<!-- PDF Modal -->
<div id="pdfModal" class="fixed inset-0 z-50 hidden overflow-y-auto h-full w-full flex items-center justify-center p-4 bg-transparent">
    <div class="relative w-full max-w-6xl h-[85vh] rounded-lg overflow-hidden shadow-2xl bg-white">
        <!-- Close Button -->
        <button id="closeModal" class="absolute top-3 right-3 z-10 text-red-500 hover:text-red-700 bg-white rounded-full p-2 shadow-md hover:bg-red-50 transition">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <iframe id="pdfFrame" src="" class="w-full h-full border-0" frameborder="0"></iframe>
    </div>
</div>

<script>
// PDF Modal functions
function openPdfModal(pdfUrl) {
    const modal = document.getElementById('pdfModal');
    const pdfFrame = document.getElementById('pdfFrame');
    
    // Set iframe src to PDF URL
    pdfFrame.src = pdfUrl;
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden';
}

// Close modal when close button is clicked
document.getElementById('closeModal').addEventListener('click', function() {
    const modal = document.getElementById('pdfModal');
    const pdfFrame = document.getElementById('pdfFrame');
    
    modal.classList.add('hidden');
    pdfFrame.src = ''; // Clear iframe to stop PDF loading
    document.body.style.overflow = 'auto';
});

// Close modal when clicking outside
document.getElementById('pdfModal').addEventListener('click', function(e) {
    if (e.target === this) {
        const modal = document.getElementById('pdfModal');
        const pdfFrame = document.getElementById('pdfFrame');
        
        modal.classList.add('hidden');
        pdfFrame.src = '';
        document.body.style.overflow = 'auto';
    }
});

// Close modal with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('pdfModal');
        const pdfFrame = document.getElementById('pdfFrame');
        
        if (!modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
            pdfFrame.src = '';
            document.body.style.overflow = 'auto';
        }
    }
});
</script>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/table-search.js') }}"></script>
@endsection
