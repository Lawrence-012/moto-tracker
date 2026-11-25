@extends('layouts.employee')

@section('header')
    <div class="flex justify-center items-center">
        <!-- Centered Content -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full" style="max-width: 1200px; margin-left: auto; margin-right: auto;">
            <!-- Desktop: Heading on one line, Search and Create button below -->
            <!-- Mobile: All three stacked vertically -->
            <div class="flex flex-col gap-3 flex-1 w-full">
                <!-- Heading -->
                <h2 class="font-bold flex items-center heading-style" style="color: #1e6031; font-size: 1.5rem; height: 32px; margin-top: 10px; margin-bottom: 0;">
                    <svg class="mr-2 heading-icon" style="width: 24px; height: 24px; color: #1e6031;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    My Travel Requests
                </h2>

                <!-- Search Bar + Button (together as one unit) -->
                <form id="searchForm" method="GET" action="{{ route('vp.travel-orders.index') }}">
                    <div class="flex" style="display: flex;">
                        <input type="text" name="search" class="form-input" placeholder="Search requests..." 
                               value="{{ request('search') }}"
                               style="height: 32px; width: 250px; font-size: 0.85rem; border: 1px solid #1e6031; border-right: none; border-radius: 0.375rem 0 0 0.375rem; padding: 0 12px;">
                        <button type="submit" 
                                style="background-color: #1e6031; color: #ffffff; border: 1px solid #1e6031; height: 32px; width: 40px; border-radius: 0 0.375rem 0.375rem 0; cursor:pointer; display: flex; align-items: center; justify-content: center;">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div> 
                </form>

                <!-- Create Button aligned right -->
                <div style="margin-left: auto;">
                    <button type="button"
                        onclick="openCreateTravelRequestModal();"
                        class="inline-flex items-center px-3 py-1 bg-[#1e6031] border border-[#1e6031] rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#103c1e] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150 whitespace-nowrap"
                        style="height: 32px;">
                        <svg class="mr-1" style="width: 10px; height: 10px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Create Request
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php
        // Check if current user is President (for hiding approver selection)
        $isPresidentIndex = Auth::check() && Auth::user()->employee && 
            Auth::user()->employee->officer && 
            Auth::user()->employee->officer->president == true;

        // Approval hierarchy context: approvers must OUTRANK the requester
        // (President 4 > VP 3 > Division Head 2 > Unit Head 1 > Employee 0)
        $requesterEmployee = Auth::check() ? Auth::user()->employee : null;
        $requesterEmployeeId = $requesterEmployee->id ?? null;
        $requesterRank = \App\Services\ApprovalHierarchy::employeeRank($requesterEmployee);

        // Chain of command for every position this employee can file under:
        //   [position_id => [approver employee ids]]  (null = not chain-restricted, any officer above)
        $requesterApproverMap = [];
        if ($requesterEmployee) {
            foreach ($requesterEmployee->positions as $filingPosition) {
                $requesterApproverMap[$filingPosition->id] =
                    \App\Services\ApprovalHierarchy::eligibleApproverIds($requesterEmployee, $filingPosition);
            }
        }
    @endphp
    
    <!-- Load Bootstrap FIRST -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-full" style="max-width: 1200px; margin: 0 auto;">
  <!-- Page Header -->
<div class="mb-6 pb-4 border-b border-gray-200">

    @php
        // Determine the correct route based on current request
        $currentRoute = Route::currentRouteName();
        $routePrefix = '';
        
        if (str_starts_with($currentRoute, 'unithead.')) {
            $routePrefix = 'unithead.';
        } elseif (str_starts_with($currentRoute, 'vp.')) {
            $routePrefix = 'vp.';
        } elseif (str_starts_with($currentRoute, 'divisionhead.')) {
            $routePrefix = 'divisionhead.';
        }
        
        $baseRoute = $routePrefix . 'travel-orders.index';
    @endphp

    <!-- ROW 1 - Compressed for mobile -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">

        <!-- Title -->
        <h1 class="text-lg sm:text-xl font-bold text-[#1e6031] mb-0 whitespace-nowrap">
            My Travel Requests
        </h1>

        <!-- Search + Create -->
        <div class="flex items-center gap-2 w-full sm:w-auto">
            
            <!-- Search Bar -->
            <div class="flex flex-1 sm:flex-initial">
                <input type="text" 
                       id="search-input" 
                       class="table-search-input form-input"
                       placeholder="Search..."
                       data-table-id="travel-orders-table"
                       data-url="{{ route($baseRoute) }}"
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

            <button type="button"
               class="inline-flex items-center px-3 py-1 bg-[#1e6031] border border-[#1e6031] rounded-md font-medium text-sm text-white hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#1e6031] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150 whitespace-nowrap flex-shrink-0"
               style="height: 32px; display: flex; align-items: center;"
               onclick="openCreateTravelRequestModal();">
                <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span class="hidden sm:inline">Create Request</span>
                <span class="sm:hidden">Create</span>
            </button>

        </div>
    </div>

<p class="text-gray-600 text-sm hidden sm:block" style="margin-top: 0; line-height: 1.2;">
    Manage your travel requests and view their status
</p>

</div>

<!-- Script (UNCHANGED) -->
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
                        <a href="{{ route($baseRoute, ['tab' => 'draft', 'search' => $search ?? '']) }}" 
                           class="{{ isset($tab) && $tab == 'draft' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="draft"
                           style="text-decoration: none;">
                            Draft
                        </a>
                        <a href="{{ route($baseRoute, ['tab' => 'pending', 'search' => $search ?? '']) }}" 
                           class="{{ (isset($tab) && $tab == 'pending') || !isset($tab) ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="pending"
                           style="text-decoration: none;">
                            Pending
                        </a>
                        <a href="{{ route($baseRoute, ['tab' => 'approved', 'search' => $search ?? '']) }}" 
                           class="{{ isset($tab) && $tab == 'approved' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="approved"
                           style="text-decoration: none;">
                            Approved
                        </a>
                        <a href="{{ route($baseRoute, ['tab' => 'completed', 'search' => $search ?? '']) }}" 
                           class="{{ isset($tab) && $tab == 'completed' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="completed"
                           style="text-decoration: none;">
                            Completed
                        </a>
                        <a href="{{ route($baseRoute, ['tab' => 'cancelled', 'search' => $search ?? '']) }}" 
                           class="{{ isset($tab) && $tab == 'cancelled' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                           data-tab-switch="cancelled"
                           style="text-decoration: none;">
                            Cancelled
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Travel Orders Table -->
            <div class="overflow-x-auto">
                <table id="travel-orders-table" class="min-w-full divide-y divide-gray-200">
                    <thead style="background-color: #1e6031; color: white;">
                        <tr>
                            <th class="w-[5%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">No.</th>
                            <th class="w-[20%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Destination</th>
                            <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Date</th>
                            <th class="w-[10%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Time</th>
                            <th class="w-[20%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Purpose</th>
                            <th class="w-[15%]" style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Remarks</th>
                            <th class="w-[15%]" style="padding: 8px 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.05em;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="travel-orders-table-body">
                        @if(isset($tab) && $tab === 'draft')
                            @include('travel-orders.partials.draft-rows', ['travelOrders' => $travelOrders])
                        @else
                            @include('travel-orders.partials.table-rows', ['travelOrders' => $travelOrders, 'tab' => $tab ?? 'pending'])
                        @endif
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="flex items-center justify-between gap-2 mt-2">
                <div class="text-xs sm:text-sm text-gray-600 whitespace-nowrap">
                    Showing {{ $travelOrders->firstItem() ?? 0 }} to {{ $travelOrders->lastItem() ?? 0 }} of {{ $travelOrders->total() }} travel requests
                </div>
                <nav>
                    <ul class="pagination flex items-center space-x-1">
                            {{-- Previous Button --}}
                            @if($travelOrders->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link text-xs px-2 py-1">Prev</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link text-xs px-2 py-1" href="{{ $travelOrders->previousPageUrl() }}">Prev</a>
                                </li>
                            @endif
                            
                            {{-- Current Page --}}
                            <li class="page-item active">
                                <span class="page-link text-xs px-2 py-1">{{ $travelOrders->currentPage() }}</span>
                            </li>
                            
                            {{-- Next Button --}}
                            @if($travelOrders->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link text-xs px-2 py-1" href="{{ $travelOrders->nextPageUrl() }}">Next</a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link text-xs px-2 py-1">Next</span>
                                </li>
                            @endif
                        </ul>
                    </nav>
            </div>
        </div>
    </div>
    
    <!-- Include the table search JavaScript -->
    <script src="{{ asset('js/table-search.js') }}?v={{ time() }}"></script>
    
    <!-- Include edit draft fix - loads AFTER table-search to override broken function -->
    <script src="/js/edit-draft-fix.js"></script>
    
    <script>
    // CRITICAL: Define openCreateTravelRequestModal FIRST to ensure it's available
    window.openCreateTravelRequestModal = window.openCreateTravelRequestModal || function() {
        console.log('🔧 [FALLBACK VERSION] Creating travel request');
        const modalTitle = document.getElementById('createTravelRequestModalLabel');
        if (modalTitle) {
            modalTitle.textContent = 'Create New Travel Request';
        }
        
        const form = document.getElementById('createTravelRequestForm');
        if (!form) {
            console.error('❌ Form not found!');
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Form not found on page.'
            });
            return;
        }
        
        // Set form action based on user role (President vs others)
        const isPresidentUser = {{ $isPresidentIndex ? 'true' : 'false' }};
        if (isPresidentUser) {
            form.action = "{{ route('president.travel-orders.store') }}";
        } else {
            form.action = "{{ route('travel-orders.store') }}";
        }
        
        const methodField = document.getElementById('edit_method_field');
        if (methodField) {
            methodField.remove();
        }
        
        const savedData = localStorage.getItem('createTravelRequestFormData');
        if (savedData) {
            try {
                const formData = JSON.parse(savedData);
                if (formData.position_id) {
                    const positionField = form.querySelector('select[name="position_id"]');
                    if (positionField) positionField.value = formData.position_id;
                }
                if (formData.date_from) {
                    const dateFromField = form.querySelector('input[name="date_from"]');
                    if (dateFromField) dateFromField.value = formData.date_from;
                }
                if (formData.date_to) {
                    const dateToField = form.querySelector('input[name="date_to"]');
                    if (dateToField) dateToField.value = formData.date_to;
                }
                if (formData.departure_time) {
                    const departureTimeField = form.querySelector('input[name="departure_time"]');
                    if (departureTimeField) departureTimeField.value = formData.departure_time;
                }
                if (formData.destination) {
                    const destinationField = form.querySelector('input[name="destination"]');
                    if (destinationField) destinationField.value = formData.destination;
                }
                if (formData.purpose) {
                    const purposeField = form.querySelector('textarea[name="purpose"]');
                    if (purposeField) purposeField.value = formData.purpose;
                }
                console.log('✅ Loaded saved form data');
            } catch (error) {
                console.error('Error loading saved data:', error);
            }
        }
        
        setTimeout(() => {
            try {
                const modal = new bootstrap.Modal(document.getElementById('createTravelRequestModal'));
                modal.show();
                console.log('✅ Modal opened');
            } catch (e) {
                console.error('❌ Error opening modal:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Could not open modal. Please refresh the page.'
                });
            }
        }, 100);
    };
    
    console.log('✅ window.openCreateTravelRequestModal is now available globally!');
    </script>
    
    <script>
        function viewTravelOrderExcel(id) {
            // Fetch travel order details to determine the correct Excel URL based on the selected position
            fetch(`/api/travel-orders/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const travelOrder = data.data;
                        const position = travelOrder.position;
                        
                        let excelUrl;
                        if (position) {
                            // Determine Excel URL based on the travel order's selected position
                            if (position.is_unit_head && !position.is_division_head && !position.is_vp && !position.is_president) {
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
                        
                        // Open Excel in modal instead of new tab
                        openExcelModal(excelUrl);
                    } else {
                        alert('Error loading travel order details for Excel generation');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading travel order details for Excel generation');
                });
        }

        function openEditTravelRequestModal(id) {
            // Fetch travel order details
            fetch(`/api/travel-orders/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const travelOrder = data.data;
                        
                        // Populate the form fields
                        document.getElementById('edit_travel_order_id').value = travelOrder.id;
                        
                        // Handle position field - try multiple sources
                        if (travelOrder.position && travelOrder.position.id) {
                            document.getElementById('edit_position_id').value = travelOrder.position.id;
                        } else if (travelOrder.emp_position_id) {
                            document.getElementById('edit_position_id').value = travelOrder.emp_position_id;
                        } else if (travelOrder.position_id) {
                            document.getElementById('edit_position_id').value = travelOrder.position_id;
                        }
                        
                        document.getElementById('edit_destination').value = travelOrder.destination;
                        document.getElementById('edit_date_from').value = travelOrder.date_from;
                        document.getElementById('edit_date_to').value = travelOrder.date_to;
                        document.getElementById('edit_departure_time').value = travelOrder.departure_time || '';
                        document.getElementById('edit_purpose').value = travelOrder.purpose;
                        
                        // Set the form action URL
                        document.getElementById('editTravelRequestForm').action = `/travel-orders/${travelOrder.id}`;
                        
                        // Show the modal
                        const modal = new bootstrap.Modal(document.getElementById('editTravelRequestModal'));
                        modal.show();
                    } else {
                        alert('Error loading travel order details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading travel order details');
                });
        }
    </script>
    
    <script>
        // Simple AJAX pagination
        window.loadPage = function(event, page) {
            if (event) event.preventDefault();
            
            const searchInput = document.getElementById('search-input');
            const search = searchInput ? searchInput.value : '';
            const activeTab = document.querySelector('.border-\\[\\#1e6031\\]');
            const tab = activeTab ? activeTab.dataset.tabSwitch : 'pending';
            
            fetch(`?page=${page}&search=${encodeURIComponent(search)}&tab=${encodeURIComponent(tab)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                document.querySelector('#travel-orders-table tbody').innerHTML = data.table_body;
                location.reload(); // Simple reload to update pagination
            })
            .catch(error => console.error('Error:', error));
        };
    </script>
    
    <style>
        /* Pagination styling - EXACT COPY FROM DRIVERS */
        .pagination {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem; /* Space between Prev/Page/Next */
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
            border-radius: 0.25rem; /* Rounded corners like Trip Tickets */
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
        
        /* Style Prev/Next buttons */
        .page-item:first-child .page-link,
        .page-item:last-child .page-link {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #1e6031;
            border-color: #1e6031;
            color: white;
            border-radius: 0.25rem; /* Rounded corners like Trip Tickets */
        }
        
        .page-item:first-child .page-link:hover,
        .page-item:last-child .page-link:hover {
            background-color: #164f2a;
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
        
        /* Action buttons styling to match trip tickets */
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

        .action-buttons .archive-btn, .action-buttons .delete-btn {
            color: #dc3545 !important;
            border: 1px solid #dc3545 !important;
            background-color: transparent !important;
        }

        .action-buttons .archive-btn:hover, .action-buttons .delete-btn:hover {
            background-color: #dc3545 !important;
            color: #fff !important;
            border-color: #dc3545 !important;
        }
    </style>

    <!-- Create Travel Request Modal -->
    <div class="modal fade" id="createTravelRequestModal" tabindex="-1" aria-labelledby="createTravelRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header" style="background-color:#1e6031; color:white;">
                    <h5 class="modal-title fw-bold" id="createTravelRequestModalLabel">
                        Create New Travel Request
                    </h5>
                </div>
                
                <!-- Modal Body -->
                <form method="post" action="{{ $isPresidentIndex ? route('president.travel-orders.store') : route('travel-orders.store') }}" id="createTravelRequestForm">
                    @csrf
                    <div class="modal-body px-4 py-2">
                        <div class="row">
                            <!-- Left Column - Travel Request Details -->
                            <div class="col-md-7 pe-3">
                                <h6 class="fw-semibold text-success mb-3" style="font-size: 0.9rem;">Step 1</h6>
                                
                                <!-- Position Field -->
                                <div class="mb-2">
                                    <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Position <span class="text-danger">*</span></label>
                                    <select name="position_id" class="form-control form-control-sm border-success" required>
                                        <option value="">Select a position</option>
                                        @foreach($positions as $position)
                                            <option value="{{ $position->id }}">
                                                {{ $position->position_name }}
                                                @if($position->office) - {{ $position->office->office_name }} @endif
                                                @if($position->is_unit_head) (Unit Head) @elseif($position->is_division_head) (Division Head) @elseif($position->is_vp) (VP) @elseif($position->is_president) (President) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="position_id_error"></div>
                                </div>
                                
                                <!-- Date Fields Row -->
                                <div class="row mb-2">
                                    <div class="col-md-4">
                                        <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Date From <span class="text-danger">*</span></label>
                                        <input type="date" name="date_from" class="form-control form-control-sm border-success" required>
                                        <div class="invalid-feedback" id="date_from_error"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Date To <span class="text-danger">*</span></label>
                                        <input type="date" name="date_to" class="form-control form-control-sm border-success" required>
                                        <div class="invalid-feedback" id="date_to_error"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Departure Time <span class="text-danger">*</span></label>
                                        <input type="time" name="departure_time" class="form-control form-control-sm border-success" required>
                                        <div class="invalid-feedback" id="departure_time_error"></div>
                                    </div>
                                </div>
                                
                                <!-- Destination Field -->
                                <div class="mb-2">
                                    <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Destination <span class="text-danger">*</span></label>
                                    <input type="text" name="destination" class="form-control form-control-sm border-success" placeholder="Enter destination" required>
                                    <div class="invalid-feedback" id="destination_error"></div>
                                </div>
                                
                                <!-- Purpose Field -->
                                <div class="mb-2">
                                    <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Purpose <span class="text-danger">*</span></label>
                                    <textarea name="purpose" rows="4" class="form-control form-control-sm border-success" placeholder="Provide a brief description of the purpose of your travel" required></textarea>
                                    <div class="invalid-feedback" id="purpose_error"></div>
                                </div>
                            </div>
                            
                            <!-- Right Column - Step 2 (Always show for everyone) -->
                            <div class="col-md-5 ps-3 border-start">
                                <h6 class="fw-semibold text-success mb-1" style="font-size: 0.9rem;">Step 2</h6>
                                <p class="small text-muted mb-3" style="font-size: 0.75rem;">Approval Chain (Select 1-2 Approvers Only)</p>
                                <p id="approver_rank_hint" class="small text-success d-none mb-2" style="font-size: 0.75rem; margin-top: -0.5rem;"></p>
                                
                                <!-- University President Dropdown -->
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" id="university_president_approver_label" style="font-size: 0.8rem;">University President</label>
                                    <select name="university_president_approver" id="university_president_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="" id="university_president_approver_default">Select University President</option>
                                    </select>
                                </div>
                                
                                <!-- VP Dropdown -->
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" id="vp_approver_label" style="font-size: 0.8rem;">Vice President (VP)</label>
                                    <select name="vp_approver" id="vp_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="" id="vp_approver_default">Select Vice President</option>
                                    </select>
                                </div>
                                
                                <!-- Division Head Dropdown -->
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" style="font-size: 0.8rem;">Division Head</label>
                                    <select name="division_head_approver" id="division_head_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="">Select Division Head</option>
                                    </select>
                                </div>
                                
                                <!-- Unit Head Dropdown -->
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" style="font-size: 0.8rem;">Unit Head</label>
                                    <select name="unit_head_approver" id="unit_head_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="">Select Unit Head</option>
                                    </select>
                                </div>
                                
                                <div id="approver-error" class="alert alert-danger small py-1 px-2 mt-2 d-none">
                                    <i class="fas fa-exclamation-triangle"></i> You can only select 1 or 2 approvers.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success" id="createTravelRequestBtn">
                            <i class="fas fa-save me-1"></i>Create Travel Request
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- NEW Edit Travel Request Modal -->
    <div class="modal fade" id="editTravelRequestModal" tabindex="-1" aria-labelledby="editTravelRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header" style="background-color:#1e6031; color:white;">
                    <h5 class="modal-title fw-bold" id="editTravelRequestModalLabel">Edit Travel Request</h5>
                </div>
                
                <!-- Modal Body -->
                <form method="post" id="editTravelRequestForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="edit_travel_order_id">
                    
                    <div class="modal-body px-4 py-2">
                        <div class="row">
                            <!-- Left Column - Step 1 -->
                            <div class="col-md-7 pe-3">
                                <h6 class="fw-semibold text-success mb-3" style="font-size: 0.9rem;">Step 1</h6>
                                
                                <!-- Position Field (Editable) -->
                                <div class="mb-2">
                                    <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Position <span class="text-danger">*</span></label>
                                    <select name="position_id" id="new_edit_position_id" class="form-control form-control-sm border-success" required>
                                        <option value="">Select a position</option>
                                        @foreach($positions as $position)
                                            <option value="{{ $position->id }}">
                                                {{ $position->position_name }}
                                                @if($position->office) - {{ $position->office->office_name }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="new_edit_position_id_error"></div>
                                </div>
                                
                                <!-- Date Fields Row -->
                                <div class="row mb-2">
                                    <div class="col-md-4">
                                        <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Date From <span class="text-danger">*</span></label>
                                        <input type="date" name="date_from" id="new_edit_date_from" class="form-control form-control-sm border-success" required>
                                        <div class="invalid-feedback" id="new_edit_date_from_error"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Date To <span class="text-danger">*</span></label>
                                        <input type="date" name="date_to" id="new_edit_date_to" class="form-control form-control-sm border-success" required>
                                        <div class="invalid-feedback" id="new_edit_date_to_error"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Departure Time <span class="text-danger">*</span></label>
                                        <input type="time" name="departure_time" id="new_edit_departure_time" class="form-control form-control-sm border-success" required>
                                        <div class="invalid-feedback" id="new_edit_departure_time_error"></div>
                                    </div>
                                </div>
                                
                                <!-- Destination Field -->
                                <div class="mb-2">
                                    <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Destination <span class="text-danger">*</span></label>
                                    <input type="text" name="destination" id="new_edit_destination" class="form-control form-control-sm border-success" placeholder="Enter destination" required>
                                    <div class="invalid-feedback" id="new_edit_destination_error"></div>
                                </div>
                                
                                <!-- Purpose Field -->
                                <div class="mb-2">
                                    <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Purpose <span class="text-danger">*</span></label>
                                    <textarea name="purpose" id="new_edit_purpose" rows="4" class="form-control form-control-sm border-success" placeholder="Provide a brief description of the purpose of your travel" required></textarea>
                                    <div class="invalid-feedback" id="new_edit_purpose_error"></div>
                                </div>
                            </div>
                            
                            <!-- Right Column - Step 2 -->
                            <div class="col-md-5 ps-3 border-start">
                                <h6 class="fw-semibold text-success mb-1" style="font-size: 0.9rem;">Step 2</h6>
                                <p class="small text-muted mb-3" style="font-size: 0.75rem;" id="edit_step2_description">Approval Chain (Select 1-2 Approvers Only)</p>
                                <p id="new_edit_approver_rank_hint" class="small text-success d-none mb-2" style="font-size: 0.75rem; margin-top: -0.5rem;"></p>
                                
                                <!-- University President Dropdown -->
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" id="new_edit_university_president_approver_label" style="font-size: 0.8rem;">University President</label>
                                    <select name="university_president_approver" id="new_edit_university_president_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="" id="new_edit_university_president_approver_default">Select University President</option>
                                    </select>
                                </div>
                                
                                <!-- VP Dropdown -->
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" id="new_edit_vp_approver_label" style="font-size: 0.8rem;">Vice President (VP)</label>
                                    <select name="vp_approver" id="new_edit_vp_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="" id="new_edit_vp_approver_default">Select Vice President</option>
                                    </select>
                                </div>
                                
                                <!-- Division Head Dropdown -->
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" style="font-size: 0.8rem;">Division Head</label>
                                    <select name="division_head_approver" id="new_edit_division_head_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="">Select Division Head</option>
                                    </select>
                                </div>
                                
                                <!-- Unit Head Dropdown -->
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" style="font-size: 0.8rem;">Unit Head</label>
                                    <select name="unit_head_approver" id="new_edit_unit_head_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="">Select Unit Head</option>
                                    </select>
                                </div>
                                
                                <div id="new_edit_approver-error" class="alert alert-danger small py-1 px-2 mt-2 d-none">
                                    <i class="fas fa-exclamation-triangle"></i> You can only select 1 or 2 approvers.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success" id="new_updateTravelRequestBtn">
                            <i class="fas fa-save me-1"></i>Update Travel Request
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Modal functionality for creating travel requests - ATTACHED TO WINDOW
    window.openCreateTravelRequestModal = function() {
        // Reset modal title to "Create New Travel Request"
        const modalTitle = document.getElementById('createTravelRequestModalLabel');
        if (modalTitle) {
            modalTitle.textContent = 'Create New Travel Request';
        }
        
        // Reset form action to create new travel order
        const form = document.getElementById('createTravelRequestForm');
        
        // Set form action based on user role (President vs others)
        const isPresidentUser = {{ $isPresidentIndex ? 'true' : 'false' }};
        if (isPresidentUser) {
            form.action = "{{ route('president.travel-orders.store') }}";
        } else {
            form.action = "{{ route('travel-orders.store') }}";
        }
        
        // Remove method field if it exists (for edit)
        const methodField = document.getElementById('edit_method_field');
        if (methodField) {
            methodField.remove();
        }
        
        // Try to load saved form data from localStorage
        const savedData = localStorage.getItem('createTravelRequestFormData');
        if (savedData) {
            try {
                const formData = JSON.parse(savedData);
                
                // Populate form fields with saved data
                if (formData.position_id) {
                    const positionField = form.querySelector('select[name="position_id"]');
                    if (positionField) positionField.value = formData.position_id;
                }
                if (formData.date_from) {
                    const dateFromField = form.querySelector('input[name="date_from"]');
                    if (dateFromField) dateFromField.value = formData.date_from;
                }
                if (formData.date_to) {
                    const dateToField = form.querySelector('input[name="date_to"]');
                    if (dateToField) dateToField.value = formData.date_to;
                }
                if (formData.departure_time) {
                    const departureTimeField = form.querySelector('input[name="departure_time"]');
                    if (departureTimeField) departureTimeField.value = formData.departure_time;
                }
                if (formData.destination) {
                    const destinationField = form.querySelector('input[name="destination"]');
                    if (destinationField) destinationField.value = formData.destination;
                }
                if (formData.purpose) {
                    const purposeField = form.querySelector('textarea[name="purpose"]');
                    if (purposeField) purposeField.value = formData.purpose;
                }
                
                console.log('✅ Loaded saved form data from localStorage');
            } catch (error) {
                console.error('Error loading saved form data:', error);
            }
        }
        
        const modal = new bootstrap.Modal(document.getElementById('createTravelRequestModal'));
        modal.show();
    }
    
    // Save form data to localStorage when modal is hidden
    function saveCreateFormData() {
        const form = document.getElementById('createTravelRequestForm');
        if (!form) return;
        
        const formData = {
            position_id: form.querySelector('select[name="position_id"]')?.value || '',
            date_from: form.querySelector('input[name="date_from"]')?.value || '',
            date_to: form.querySelector('input[name="date_to"]')?.value || '',
            departure_time: form.querySelector('input[name="departure_time"]')?.value || '',
            destination: form.querySelector('input[name="destination"]')?.value || '',
            purpose: form.querySelector('textarea[name="purpose"]')?.value || ''
        };
        
        // Only save if at least one field has data
        const hasData = Object.values(formData).some(value => value !== '');
        if (hasData) {
            localStorage.setItem('createTravelRequestFormData', JSON.stringify(formData));
            console.log('💾 Saved form data to localStorage');
        }
    }
    
    // Clear saved form data after successful submission
    function clearCreateFormData() {
        localStorage.removeItem('createTravelRequestFormData');
        console.log('🗑️ Cleared saved form data');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Store officers data organized by role
        const officersData = {!! json_encode($officers) !!};
        const positions = {!! json_encode($positions) !!};
        const units = {!! json_encode($units) !!};
        
        console.log('🔄 Fresh data loaded from server');
        console.log('Officers count:', officersData.length);
        console.log('Positions count:', positions.length);
        console.log('Units count:', units.length);
        
        // Find Transportation Services unit ID
        window.transportationServicesUnitId = null;
        if (units && units.length > 0) {
            // Look for "Transportation" in unit name (case-insensitive)
            const transportationUnit = units.find(u => 
                u.unit_name && u.unit_name.toLowerCase().includes('transportation')
            );
            
            if (transportationUnit) {
                window.transportationServicesUnitId = transportationUnit.id;
                console.log('✅ Found Transportation Services Unit ID:', window.transportationServicesUnitId);
                console.log('   Unit Name:', transportationUnit.unit_name);
            } else {
                console.warn('⚠️ Transportation Services unit not found in units list');
                console.log('Available units:', units);
            }
        }
        
        // DEBUG: Log complete officers data structure
        console.log('=== COMPLETE OFFICERS DATA STRUCTURE ===');
        console.log(JSON.stringify(officersData, null, 2));
        console.log('========================\n');
        
        // DEBUG: Show first officer in detail
        if (officersData.length > 0) {
            console.log('=== FIRST OFFICER DETAILED VIEW ===');
            console.log('First Officer:', officersData[0]);
            console.log('First Officer Employee Data:', officersData[0].employee);
            console.log('First Officer Officer Data:', officersData[0].officer);
            console.log('Has president role?', officersData[0].officer?.president);
            console.log('Has vp role?', officersData[0].officer?.vp);
            console.log('Has division_head role?', officersData[0].officer?.division_head);
            console.log('Has unit_head role?', officersData[0].officer?.unit_head);
            console.log('Office ID:', officersData[0].officer?.office_id);
            console.log('=================================\n');
        } else {
            console.error('❌ NO OFFICERS DATA FOUND! Check database query.');
        }
        
        // Position change handler for CREATE form
        const positionSelect = document.querySelector('#createTravelRequestForm select[name="position_id"]');
        if (positionSelect) {
            positionSelect.addEventListener('change', function() {
                const selectedPositionId = this.value;
                console.log('[CREATE] Selected Position ID:', selectedPositionId);
                console.log('[CREATE] Officers data available:', officersData.length);
                if (selectedPositionId) {
                    console.log('[CREATE] Calling populateApproverDropdowns...');
                    populateApproverDropdowns(selectedPositionId);
                } else {
                    console.log('[CREATE] No position selected, clearing dropdowns');
                    clearApproverDropdowns();
                }
                validateApproverCount();
            });
        }
        
        // Position change handler for EDIT form
        const editPositionSelect = document.getElementById('new_edit_position_id');
        if (editPositionSelect) {
            editPositionSelect.addEventListener('change', function() {
                const selectedPositionId = this.value;
                console.log('[EDIT] Selected Position ID:', selectedPositionId);
                if (selectedPositionId) {
                    populateEditApproverDropdowns(selectedPositionId);
                } else {
                    clearEditApproverDropdowns();
                }
            });
        }
        
        // Add change listeners to approver dropdowns - SEPARATE FOR CREATE AND EDIT
        const createApproverDropdowns = document.querySelectorAll('#createTravelRequestModal .approver-select');
        createApproverDropdowns.forEach(dropdown => {
            dropdown.addEventListener('change', function() {
                console.log('[CREATE] Approver dropdown changed');
                updateHiddenApproverFields();
                validateApproverCount(); // For CREATE modal
            });
        });
        
        const editApproverDropdowns = document.querySelectorAll('#editTravelRequestModal .approver-select');
        editApproverDropdowns.forEach(dropdown => {
            dropdown.addEventListener('change', function() {
                console.log('[EDIT] Approver dropdown changed');
                // updateEditHiddenApproverFields(); // This function doesn't exist, using updateHiddenApproverFields instead
                validateEditApproverCount(); // For EDIT modal
            });
        });
        
        // ------------------------------------------------------------------
        // APPROVAL HIERARCHY - an approver must OUTRANK the requester, and heads are limited to their own
        // chain of command:  Unit Head -> Division Head of their division + VP of their office
        //                    Division Head -> VP of their office + President;  VP -> President
        //   President (4) > VP (3) > Division Head (2) > Unit Head (1) > Employee (0)
        // The server enforces the same rule (App\Services\ApprovalHierarchy); this only
        // keeps the dropdowns from offering choices that would be rejected.
        // ------------------------------------------------------------------
        const REQUESTER_EMPLOYEE_ID = {{ (int) ($requesterEmployeeId ?? 0) }};
        const REQUESTER_BASE_RANK = {{ (int) $requesterRank }};
        // position id => employee ids allowed to approve (computed by App\Services\ApprovalHierarchy)
        const APPROVER_CHAIN = {!! json_encode($requesterApproverMap) !!};
        const APPROVER_ROLE_RANK = { president: 4, vp: 3, division_head: 2, unit_head: 1 };
        const RANK_LABELS = { 1: 'Unit Head', 2: 'Division Head', 3: 'VP', 4: 'President' };

        // Rank implied by the role flags of a position (role-per-position system)
        function getPositionRank(position) {
            if (!position) return 0;
            if (position.is_president) return 4;
            if (position.is_vp) return 3;
            if (position.is_division_head) return 2;
            if (position.is_unit_head) return 1;
            return 0;
        }

        // Highest role held by an officer entry from officersData
        function getOfficerRank(officerEntry) {
            const o = (officerEntry && officerEntry.officer) || {};
            if (o.president) return 4;
            if (o.vp) return 3;
            if (o.division_head) return 2;
            if (o.unit_head) return 1;
            return 0;
        }

        // The filing position can only raise the requester's rank, never lower it
        function getRequesterRank(selectedPosition) {
            return Math.max(REQUESTER_BASE_RANK, getPositionRank(selectedPosition));
        }

        function isHigherThanRequester(officerEntry, requesterRank) {
            if (officerEntry.employee.id == REQUESTER_EMPLOYEE_ID) return false; // never yourself
            return getOfficerRank(officerEntry) > requesterRank;
        }

        // Officers that may be offered in each approver dropdown.
        // Heads are limited to their own chain of command (Division Head of their division + VP of their
        // office, President where the office has no VP); everyone else just needs to outrank the requester.
        // enforce=false returns everyone (used to DISPLAY approvers saved on a locked request).
        function getApproverOptions(selectedPosition, enforce) {
            const requesterRank = getRequesterRank(selectedPosition);

            const chainIds = (enforce && selectedPosition && Array.isArray(APPROVER_CHAIN[selectedPosition.id]))
                ? APPROVER_CHAIN[selectedPosition.id].map(Number)
                : null;

            const roleAllowed = role => !enforce || APPROVER_ROLE_RANK[role] > requesterRank;
            const canApprove = officerEntry => {
                if (!enforce) return true;
                if (officerEntry.employee.id == REQUESTER_EMPLOYEE_ID) return false; // never yourself
                if (chainIds) return chainIds.includes(Number(officerEntry.employee.id));
                return getOfficerRank(officerEntry) > requesterRank;
            };

            const presidents = !roleAllowed('president') ? [] : officersData.filter(o =>
                o.officer && o.officer.president && canApprove(o));

            const vpOfficers = !roleAllowed('vp') ? [] : officersData.filter(o =>
                o.officer && o.officer.vp && canApprove(o));

            const divisionHeads = !roleAllowed('division_head') ? [] : officersData.filter(o =>
                o.officer && o.officer.division_head && canApprove(o));

            const unitHeads = !roleAllowed('unit_head') ? [] : officersData.filter(o => {
                if (!o.officer || !o.officer.unit_head) return false;

                // Exclude Unit Head of Transportation Services unit
                const isTransportationServices = o.employee.positions.some(p => {
                    return p.unit_id && window.transportationServicesUnitId && p.unit_id === window.transportationServicesUnitId;
                });
                if (isTransportationServices) return false;

                return canApprove(o);
            });

            return { presidents, vpOfficers, divisionHeads, unitHeads, requesterRank, chainMode: !!chainIds };
        }

        // Hide the dropdowns of roles that do not outrank the requester (idPrefix: '' for create, 'new_edit_' for edit).
        // In chain-of-command mode a role with nobody in the chain is hidden too.
        function applyApproverVisibility(idPrefix, requesterRank, enforce, options) {
            const dropdowns = {
                president: { id: idPrefix + 'university_president_approver', list: options && options.presidents },
                vp: { id: idPrefix + 'vp_approver', list: options && options.vpOfficers },
                division_head: { id: idPrefix + 'division_head_approver', list: options && options.divisionHeads },
                unit_head: { id: idPrefix + 'unit_head_approver', list: options && options.unitHeads }
            };
            Object.keys(dropdowns).forEach(role => {
                const select = document.getElementById(dropdowns[role].id);
                const group = select ? select.closest('.mb-2') : null;
                if (!group) return;
                let visible = !enforce || APPROVER_ROLE_RANK[role] > requesterRank;
                if (visible && enforce && options && options.chainMode && dropdowns[role].list) {
                    visible = dropdowns[role].list.length > 0;
                }
                group.style.display = visible ? '' : 'none';
            });
        }

        // Tell the requester why some approver roles are not listed
        function updateApproverHint(hintId, requesterRank, enforce, options) {
            const hint = document.getElementById(hintId);
            if (!hint) return;

            const hide = () => { hint.classList.add('d-none'); hint.textContent = ''; };

            if (!enforce || requesterRank < 1) { hide(); return; }

            // Chain of command: name the people who can approve this request
            if (options && options.chainMode) {
                const nameOf = o => `${o.employee.first_name} ${o.employee.last_name}`;
                const parts = [];
                options.divisionHeads.forEach(o => parts.push(`${nameOf(o)} (Division Head)`));
                options.vpOfficers.forEach(o => parts.push(`${nameOf(o)} (VP)`));
                options.presidents.forEach(o => parts.push(`${nameOf(o)} (President)`));
                if (parts.length) {
                    hint.textContent = `Your approver must be from your chain of command: ${parts.join(', ')}.`;
                    hint.classList.remove('d-none');
                    return;
                }
            }

            const allowed = [];
            for (let rank = requesterRank + 1; rank <= 4; rank++) {
                if (RANK_LABELS[rank]) allowed.push(RANK_LABELS[rank]);
            }
            if (allowed.length === 0) { hide(); return; }

            hint.textContent = `Your approver must hold a higher position than ${RANK_LABELS[requesterRank]}. Allowed approvers: ${allowed.join(', ')}.`;
            hint.classList.remove('d-none');
        }

        // Function to populate approver dropdowns based on position
        function populateApproverDropdowns(positionId) {
            console.log('\n=== [POPULATE APPROVER DROPDOWNS] START ===');
            console.log('Position ID received:', positionId);
            console.log('positions array:', positions);
            console.log('officersData array:', officersData);
            
            // Find the selected position data
            const selectedPosition = positions.find(p => p.id == positionId);
            
            if (!selectedPosition) {
                console.error('❌ Position not found in positions array!');
                console.log('Available positions:', positions);
                return;
            }
            
            console.log('✅ Selected Position found:', selectedPosition.position_name);
            console.log('Selected Position Office ID:', selectedPosition.office_id);
            
            // Get the OFFICE ID from the selected position
            const selectedOfficeId = selectedPosition.office_id;
            
            console.log('Total officers in data:', officersData.length);
            if (officersData.length === 0) {
                console.error('❌ officersData is EMPTY! This is why dropdowns are empty.');
                console.log('Check controller - officers not being loaded properly');
            }
            
            // List all officers with their roles
            console.log('\n--- ALL OFFICERS IN DATA ---');
            officersData.forEach((o, idx) => {
                console.log(`${idx + 1}. ${o.employee.first_name} ${o.employee.last_name} - Roles: President=${o.officer.president}, VP=${o.officer.vp}, DivHead=${o.officer.division_head}, UnitHead=${o.officer.unit_head}, OfficeID=${o.officer.office_id}`);
            });
            console.log('--------------------------\n');
            
            // APPROVAL HIERARCHY: heads are only offered their own chain of command
            // (a Unit Head: their Division Head + the VP of their office)
            const enforceRank = true;
            const approverOptions = getApproverOptions(selectedPosition, enforceRank);
            const { presidents, vpOfficers, divisionHeads, unitHeads, requesterRank } = approverOptions;
            applyApproverVisibility('', requesterRank, enforceRank, approverOptions);
            updateApproverHint('approver_rank_hint', requesterRank, enforceRank, approverOptions);
            console.log('🔐 Requester rank:', requesterRank, '(' + (RANK_LABELS[requesterRank] || 'Employee') + ') - only higher-ranking approvers are offered');
            
            console.log('\n=== FILTERING RESULTS ===');
            console.log('📋 Presidents filtered:', presidents.length);
            if (presidents.length > 0) {
                presidents.forEach(p => console.log(`  - ${p.employee.first_name} ${p.employee.last_name} (Office ID: ${p.officer.office_id})`));
            }
            
            console.log('📋 VP Officers filtered:', vpOfficers.length);
            if (vpOfficers.length > 0) {
                vpOfficers.forEach(p => console.log(`  - ${p.employee.first_name} ${p.employee.last_name} (Office ID: ${p.officer.office_id})`));
            }
            
            console.log('📋 Division Heads filtered:', divisionHeads.length);
            if (divisionHeads.length > 0) {
                divisionHeads.forEach(p => console.log(`  - ${p.employee.first_name} ${p.employee.last_name} (Office ID: ${p.officer.office_id})`));
            }
            
            console.log('📋 Unit Heads filtered:', unitHeads.length);
            if (unitHeads.length > 0) {
                unitHeads.forEach(p => console.log(`  - ${p.employee.first_name} ${p.employee.last_name} (Office ID: ${p.officer.office_id})`));
            }
            console.log('========================\n');
            
            // Populate University President dropdown
            populateDropdown('university_president_approver', presidents, 'Select University President');
            
            // Populate VP dropdown
            populateDropdown('vp_approver', vpOfficers, 'Select Vice President');
            
            // Populate Division Head dropdown
            populateDropdown('division_head_approver', divisionHeads, 'Select Division Head');
            
            // Populate Unit Head dropdown
            populateDropdown('unit_head_approver', unitHeads, 'Select Unit Head');
        }
        
        // Function to populate a dropdown with officer options
        function populateDropdown(dropdownId, officers, defaultText) {
            const dropdown = document.getElementById(dropdownId);
            
            if (!dropdown) {
                console.error(`❌ Dropdown element ${dropdownId} not found in DOM!`);
                return;
            }
            
            dropdown.innerHTML = `<option value="">${defaultText}</option>`;
            
            console.log(`🔧 Populating ${dropdownId} with ${officers.length} officers`);
            
            if (officers.length === 0) {
                console.warn(`⚠️ No officers to populate in ${dropdownId}`);
            }
            
            officers.forEach(officer => {
                const fullName = `${officer.employee.first_name} ${officer.employee.last_name}`;
                
                // Find the position in the selected office and get its position_name
                let positionName = '';
                if (officer.employee.positions && officer.employee.positions.length > 0) {
                    // Use the first position's name (or you could filter by office_id if needed)
                    positionName = officer.employee.positions[0].position_name;
                }
                
                const option = document.createElement('option');
                option.value = officer.employee.id;
                if (positionName) {
                    option.textContent = `${fullName} - ${positionName}`;
                    console.log(`  ✅ Added: ${option.textContent}`);
                } else {
                    option.textContent = fullName;
                    console.log(`  ✅ Added: ${fullName}`);
                }
                dropdown.appendChild(option);
            });
            
            console.log(`✅ ${dropdownId} now has ${dropdown.options.length} options (including placeholder)`);
        }
        
        // Function to populate approver dropdowns for EDIT modal based on position
        function populateEditApproverDropdowns(positionId) {
            // Find the selected position data
            const selectedPosition = positions.find(p => p.id == positionId);
            
            if (!selectedPosition) {
                console.log('[EDIT] No position selected');
                return;
            }
            
            // Get the OFFICE ID from the selected position
            const selectedOfficeId = selectedPosition.office_id;
            
            // Get office name to determine if it's the President's Office
            const selectedOffice = selectedPosition.office || {};
            const isPresidentOffice = selectedOffice.office_name && 
                                      selectedOffice.office_name.includes('Office of the University President');
            
            console.log('=== [EDIT] POPULATE APPROVER DROPDOWNS ===');
            console.log('Selected Position:', selectedPosition.position_name);
            console.log('Selected Office ID:', selectedOfficeId);
            console.log('Is President Office:', isPresidentOffice);
            
            // Update VP dropdown label based on office
            const editVpLabel = document.getElementById('edit_vp_approver_label');
            const editVpPlaceholder = document.getElementById('edit_vp_approver_default');
            if (isPresidentOffice) {
                if (editVpLabel) editVpLabel.textContent = 'University President';
                if (editVpPlaceholder) editVpPlaceholder.textContent = 'Select President';
            } else {
                if (editVpLabel) editVpLabel.textContent = 'Vice President (VP)';
                if (editVpPlaceholder) editVpPlaceholder.textContent = 'Select Vice President';
            }
            
            // APPROVAL HIERARCHY: heads are only offered their own chain of command.
            // In read-only mode (non-draft) the saved approvers are only displayed, so don't filter them out.
            const editModalEl = document.getElementById('editTravelRequestModal');
            const approversLocked = !!(editModalEl && editModalEl.dataset.approversLocked === 'true');
            const enforceRank = !approversLocked;
            const approverOptions = getApproverOptions(selectedPosition, enforceRank);
            const { presidents, vpOfficers, divisionHeads, unitHeads, requesterRank } = approverOptions;
            applyApproverVisibility('new_edit_', requesterRank, enforceRank, approverOptions);
            updateApproverHint('new_edit_approver_rank_hint', requesterRank, enforceRank, approverOptions);
            
            console.log('[EDIT] Filtered VP Officers:', vpOfficers.length);
            console.log('[EDIT] Filtered Division Heads:', divisionHeads.length);
            console.log('[EDIT] Filtered Unit Heads:', unitHeads.length);
            
            // Populate EDIT modal dropdowns (using new_edit_ prefix for NEW modal)
            populateEditDropdown('new_edit_university_president_approver', presidents, 'Select University President');
            populateEditDropdown('new_edit_vp_approver', vpOfficers, 'Select Vice President');
            populateEditDropdown('new_edit_division_head_approver', divisionHeads, 'Select Division Head');
            populateEditDropdown('new_edit_unit_head_approver', unitHeads, 'Select Unit Head');
        }
        
        // Function to populate a dropdown in EDIT modal
        function populateEditDropdown(dropdownId, officers, defaultText) {
            const dropdown = document.getElementById(dropdownId);
            dropdown.innerHTML = `<option value="">${defaultText}</option>`;
            
            console.log(`[EDIT] Populating ${dropdownId} with ${officers.length} officers`);
            
            officers.forEach(officer => {
                const fullName = `${officer.employee.first_name} ${officer.employee.last_name}`;
                
                let positionName = '';
                if (officer.employee.positions && officer.employee.positions.length > 0) {
                    positionName = officer.employee.positions[0].position_name;
                }
                
                const option = document.createElement('option');
                option.value = officer.employee.id;
                if (positionName) {
                    option.textContent = `${fullName} - ${positionName}`;
                } else {
                    option.textContent = fullName;
                }
                dropdown.appendChild(option);
            });
            
            console.log(`[EDIT] ${dropdownId} now has ${dropdown.options.length} options`);
        }
        
        // Function to clear all approver dropdowns in EDIT modal
        function clearEditApproverDropdowns() {
            document.getElementById('new_edit_university_president_approver').innerHTML = '<option value="">Select University President</option>';
            document.getElementById('new_edit_vp_approver').innerHTML = '<option value="">Select Vice President</option>';
            document.getElementById('new_edit_division_head_approver').innerHTML = '<option value="">Select Division Head</option>';
            document.getElementById('new_edit_unit_head_approver').innerHTML = '<option value="">Select Unit Head</option>';
            applyApproverVisibility('new_edit_', 0, false);
            updateApproverHint('new_edit_approver_rank_hint', 0, false);
            
            const editVpLabel = document.getElementById('new_edit_vp_approver_label');
            if (editVpLabel) editVpLabel.textContent = 'Vice President (VP)';
        }
        
        // Function to clear all approver dropdowns
        function clearApproverDropdowns() {
            document.getElementById('university_president_approver').innerHTML = '<option value="">Select University President</option>';
            applyApproverVisibility('', 0, false);
            updateApproverHint('approver_rank_hint', 0, false);
            document.getElementById('vp_approver').innerHTML = '<option value="">Select Vice President</option>';
            document.getElementById('division_head_approver').innerHTML = '<option value="">Select Division Head</option>';
            document.getElementById('unit_head_approver').innerHTML = '<option value="">Select Unit Head</option>';
            
            // Reset VP label to default
            const vpLabel = document.querySelector('#vp_approver_label');
            if (vpLabel) {
                vpLabel.textContent = 'Vice President (VP)';
            }
        }
        
        // Function to update hidden approver fields based on dropdown selections
        function updateHiddenApproverFields() {
            const presidentValue = document.getElementById('university_president_approver').value;
            const vpValue = document.getElementById('vp_approver').value;
            const divisionHeaderValue = document.getElementById('division_head_approver').value;
            const unitHeadValue = document.getElementById('unit_head_approver').value;
            
            const selectedApprovers = [presidentValue, vpValue, divisionHeaderValue, unitHeadValue].filter(v => v !== '');
        }
        
        // Function to validate that only 1-2 approvers are selected
        function validateApproverCount() {
            // Check if dropdowns exist (hidden for President)
            const presidentEl = document.getElementById('university_president_approver');
            const vpEl = document.getElementById('vp_approver');
            const divisionHeadEl = document.getElementById('division_head_approver');
            const unitHeadEl = document.getElementById('unit_head_approver');
            
            // If dropdowns don't exist (President), skip validation
            if (!presidentEl || !vpEl || !divisionHeadEl || !unitHeadEl) {
                return true;
            }
            
            const presidentValue = presidentEl.value;
            const vpValue = vpEl.value;
            const divisionHeaderValue = divisionHeadEl.value;
            const unitHeadValue = unitHeadEl.value;
            
            const selectedCount = [presidentValue, vpValue, divisionHeaderValue, unitHeadValue].filter(v => v !== '').length;
            const errorDiv = document.getElementById('approver-error');
            const submitButton = document.getElementById('createTravelRequestBtn');
            
            // If error div doesn't exist (hidden for President), skip validation
            if (!errorDiv) {
                return true;
            }
            
            if (selectedCount === 0 || selectedCount > 2) {
                // Show error
                errorDiv.classList.remove('d-none');
                if (selectedCount > 2) {
                    const excessCount = selectedCount - 2;
                    const removeText = excessCount === 1 ? 'remove one selection' : `remove ${excessCount} selections`;
                    errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> You can only select 1 or 2 approvers. Please ${removeText}.`;
                } else {
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select at least 1 approver.';
                }
                // Disable submit button if more than 2 are selected
                if (selectedCount > 2) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            } else {
                // Hide error
                errorDiv.classList.add('d-none');
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
        
        // Function to validate that only 1-2 approvers are selected in EDIT modal
        function validateEditApproverCount() {
            console.log('🔍 [EDIT VALIDATION] Function called');
            
            const presidentEl = document.getElementById('new_edit_university_president_approver');
            const vpEl = document.getElementById('new_edit_vp_approver');
            const divisionHeadEl = document.getElementById('new_edit_division_head_approver');
            const unitHeadEl = document.getElementById('new_edit_unit_head_approver');
            
            // If dropdowns don't exist, skip validation
            if (!presidentEl || !vpEl || !divisionHeadEl || !unitHeadEl) {
                console.log('⚠️ [EDIT VALIDATION] Dropdowns not found, skipping');
                return true;
            }
            
            const presidentValue = presidentEl.value;
            const vpValue = vpEl.value;
            const divisionHeaderValue = divisionHeadEl.value;
            const unitHeadValue = unitHeadEl.value;
            
            const selectedCount = [presidentValue, vpValue, divisionHeaderValue, unitHeadValue].filter(v => v !== '').length;
            const errorDiv = document.getElementById('new_edit_approver-error');
            const submitButton = document.getElementById('new_updateTravelRequestBtn');
            
            console.log('📊 [EDIT VALIDATION] Selected count:', selectedCount);
            console.log('📊 [EDIT VALIDATION] Values:', { presidentValue, vpValue, divisionHeaderValue, unitHeadValue });
            
            // If error div doesn't exist, skip validation
            if (!errorDiv) {
                console.log('⚠️ [EDIT VALIDATION] Error div not found, skipping');
                return true;
            }
            
            if (selectedCount === 0 || selectedCount > 2) {
                console.log('❌ [EDIT VALIDATION] INVALID - Count is', selectedCount);
                // Show error
                errorDiv.classList.remove('d-none');
                if (selectedCount > 2) {
                    const excessCount = selectedCount - 2;
                    const removeText = excessCount === 1 ? 'remove one selection' : `remove ${excessCount} selections`;
                    errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> You can only select 1 or 2 approvers. Please ${removeText}.`;
                    console.log(`🚨 [EDIT VALIDATION] ERROR: ${selectedCount} approvers selected - need to ${removeText}`);
                } else {
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select at least 1 approver.';
                    console.log('🚨 [EDIT VALIDATION] ERROR: No approvers selected');
                }
                // Disable submit button if more than 2 are selected OR if 0 selected
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                    console.log('🔴 [EDIT VALIDATION] Button DISABLED');
                }
            } else {
                console.log('✅ [EDIT VALIDATION] VALID - Count is', selectedCount);
                // Hide error
                errorDiv.classList.add('d-none');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    console.log('🟢 [EDIT VALIDATION] Button ENABLED');
                }
            }
        }
        
        const modal = document.getElementById('createTravelRequestModal');
        const form = document.getElementById('createTravelRequestForm');
        const submitBtn = document.getElementById('createTravelRequestBtn');
        const cancelButton = form.querySelector('.btn-secondary[data-bs-dismiss="modal"]');
        
        // Flag to track if cancel button was clicked
        let isCancelled = false;
        
        // Store form data when modal is hidden (except when cancelled)
        let storedFormData = {};
        
        // Handle form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Check if dropdowns exist (hidden for President)
            const presidentEl = document.getElementById('university_president_approver');
            const vpEl = document.getElementById('vp_approver');
            const divisionHeadEl = document.getElementById('division_head_approver');
            const unitHeadEl = document.getElementById('unit_head_approver');
            
            // If dropdowns don't exist (President), skip validation and submit directly
            if (!presidentEl || !vpEl || !divisionHeadEl || !unitHeadEl) {
                console.log('President submission - skipping approver validation');
                form.submit();
                return;
            }
            
            // Validate approver count for non-President users
            const presidentValue = document.getElementById('university_president_approver').value;
            const vpValue = document.getElementById('vp_approver').value;
            const divisionHeaderValue = document.getElementById('division_head_approver').value;
            const unitHeadValue = document.getElementById('unit_head_approver').value;
            const selectedCount = [presidentValue, vpValue, divisionHeaderValue, unitHeadValue].filter(v => v !== '').length;
            
            if (selectedCount === 0 || selectedCount > 2) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Selection',
                    text: 'Please select exactly 1 or 2 approvers.',
                });
                validateApproverCount();
                return;
            }
            
            // Date Validation
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const dateFromValue = form.querySelector('[name="date_from"]').value;
            const dateToValue = form.querySelector('[name="date_to"]').value;
            
            if (dateFromValue) {
                const dateFrom = new Date(dateFromValue);
                dateFrom.setHours(0, 0, 0, 0);
                
                if (dateFrom < today) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Date',
                        text: 'Please check your departure date',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
            }
            
            if (dateFromValue && dateToValue) {
                const dateFrom = new Date(dateFromValue);
                const dateTo = new Date(dateToValue);
                
                if (dateTo < dateFrom) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Date Range',
                        text: 'Please check your return date',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
            }
            
            // Clear previous errors
            const errorElements = form.querySelectorAll('.invalid-feedback');
            errorElements.forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => input.classList.remove('is-invalid'));
            
            // Show loading SweetAlert for 2 seconds and CLOSE MODAL immediately
            const modal = bootstrap.Modal.getInstance(document.getElementById('createTravelRequestModal'));
            if (modal) {
                modal.hide();
            }
            
            Swal.fire({
                title: 'Creating Travel Request...',
                html: 'Please wait while we process your request.',
                timer: 2000,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            }).then(() => {
                // After loading completes, submit the form
                const formData = new FormData(form);
                
                // Log formData for debugging
                console.log('Form data being submitted:');
                for (let [key, value] of formData.entries()) {
                    console.log(key, value);
                }
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response OK:', response.ok);
                    
                    // Check if response is OK - surface the server's own message (e.g. an invalid approver)
                    if (!response.ok) {
                        return response.json().catch(() => ({})).then(body => {
                            const detail = (body.errors && Object.values(body.errors).flat()[0])
                                || body.message
                                || ('Server error: ' + response.status);
                            throw new Error(detail);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        // Clear saved form data
                        clearCreateFormData();
                        
                        // Show success message for 2 seconds then reload
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message || 'Travel order created successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Reload page after success message
                            window.location.reload();
                        });
                    } else {
                        // Show validation errors
                        if (data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                const errorElement = document.getElementById(field + '_error');
                                const inputElement = form.querySelector(`[name="${field}"]`);
                                
                                if (errorElement) {
                                    errorElement.textContent = data.errors[field][0];
                                    errorElement.style.display = 'block';
                                }
                                
                                if (inputElement) {
                                    inputElement.classList.add('is-invalid');
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message || 'An error occurred while creating the travel request.',
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Full error:', error);
                    
                    // Handle HTTP errors (like 422 Validation Error)
                    if (error.response) {
                        const status = error.response.status;
                        
                        // For 422 Unprocessable Entity (validation errors)
                        if (status === 422) {
                            error.response.json().then(validationErrors => {
                                console.error('Validation errors:', validationErrors);
                                
                                // Clear previous errors
                                const errorElements = form.querySelectorAll('.invalid-feedback');
                                errorElements.forEach(el => {
                                    el.textContent = '';
                                    el.style.display = 'none';
                                });
                                const inputs = form.querySelectorAll('input, select, textarea');
                                inputs.forEach(input => input.classList.remove('is-invalid'));
                                
                                // Display each validation error
                                let errorMessage = '';
                                Object.keys(validationErrors.errors || {}).forEach(field => {
                                    const messages = validationErrors.errors[field];
                                    if (messages && messages.length > 0) {
                                        errorMessage += '• ' + messages[0] + '\n';
                                        
                                        // Highlight the field
                                        const errorElement = document.getElementById(field + '_error');
                                        const inputElement = form.querySelector(`[name="${field}"]`);
                                        
                                        if (errorElement) {
                                            errorElement.textContent = messages[0];
                                            errorElement.style.display = 'block';
                                        }
                                        
                                        if (inputElement) {
                                            inputElement.classList.add('is-invalid');
                                        }
                                    }
                                });
                                
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Validation Error' + (Object.keys(validationErrors.errors || {}).length > 1 ? 's' : ''),
                                    html: '<div style="text-align: left;">' + errorMessage.replace(/\n/g, '<br>') + '</div>',
                                    confirmButtonText: 'OK'
                                });
                            }).catch(() => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Validation failed. Please check your input.',
                                });
                            });
                        } else {
                            // For other HTTP errors
                            error.response.text().then(text => {
                                console.error('Error response body:', text);
                                try {
                                    const jsonError = JSON.parse(text);
                                    console.error('Parsed error:', jsonError);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: jsonError.message || 'An error occurred while creating the travel request.',
                                    });
                                } catch(e) {
                                    console.error('Could not parse error response');
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Server Error',
                                        text: 'Server error occurred (Status: ' + status + '). Please try again or contact support.',
                                    });
                                }
                            });
                        }
                    } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
                        // Network error
                        Swal.fire({
                            icon: 'error',
                            title: 'Network Error',
                            text: 'Unable to connect to server. Please check your internet connection.',
                        });
                    } else {
                        // Other errors
                        console.error('Unexpected error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: error.message || 'An unexpected error occurred. Please try again.',
                        });
                    }
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Create Travel Request';
                });
            });
        });
        
        // Set flag when cancel button is clicked
        cancelButton.addEventListener('click', function() {
            isCancelled = true;
            // Clear approver error message immediately when cancel is clicked
            const errorDiv = document.getElementById('approver-error');
            if (errorDiv) {
                errorDiv.classList.add('d-none');
                errorDiv.innerHTML = '';
            }
            
            // Clear ALL form data and localStorage immediately when cancel is clicked
            form.reset();
            clearCreateFormData();
            
            // Clear any error messages
            const errorElements = form.querySelectorAll('.invalid-feedback');
            errorElements.forEach(element => {
                element.textContent = '';
                element.style.display = 'none';
            });
            
            // Reset selects
            const selects = form.querySelectorAll('select');
            selects.forEach(select => {
                select.classList.remove('is-invalid');
            });
            
            // Reset inputs
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.classList.remove('is-invalid');
            });
        });
        
        // Save form data to localStorage when modal is hidden (unless cancelled)
        modal.addEventListener('hidden.bs.modal', function () {
            if (!isCancelled) {
                // Save form data to localStorage for faster access next time
                saveCreateFormData();
            }
            
            // Reset the cancelled flag
            isCancelled = false;
        });
        
        // Restore form data when modal is shown
        modal.addEventListener('shown.bs.modal', function () {
            // Try to load from localStorage first (faster)
            const savedData = localStorage.getItem('createTravelRequestFormData');
            if (savedData) {
                try {
                    const formData = JSON.parse(savedData);
                    Object.keys(formData).forEach(key => {
                        const element = form.querySelector(`[name="${key}"]`);
                        if (element && element.type !== 'password' && formData[key]) {
                            element.value = formData[key];
                        }
                    });
                    console.log('✅ Restored form data from localStorage on modal show');
                } catch (error) {
                    console.error('Error restoring form data:', error);
                }
            }
        });
    });

    // Edit travel request modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editTravelRequestModal');
        const editForm = document.getElementById('editTravelRequestForm');
        const editSubmitBtn = document.getElementById('new_updateTravelRequestBtn');
        const editCancelButton = editForm.querySelector('.btn-secondary[data-bs-dismiss="modal"]');
        
        // Flag to track if cancel button was clicked
        let isEditCancelled = false;
        
        // Handle form submission
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Date Validation (same as create form)
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const dateFromValue = editForm.querySelector('[name="date_from"]').value;
            const dateToValue = editForm.querySelector('[name="date_to"]').value;
            
            if (dateFromValue) {
                const dateFrom = new Date(dateFromValue);
                dateFrom.setHours(0, 0, 0, 0);
                
                if (dateFrom < today) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Date',
                        text: 'Please check your departure date',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
            }
            
            if (dateFromValue && dateToValue) {
                const dateFrom = new Date(dateFromValue);
                const dateTo = new Date(dateToValue);
                
                if (dateTo < dateFrom) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Date Range',
                        text: 'Please check your return date',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
            }
            
            // Clear previous errors
            const errorElements = editForm.querySelectorAll('.invalid-feedback');
            errorElements.forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });
            const inputs = editForm.querySelectorAll('input, select, textarea');
            inputs.forEach(input => input.classList.remove('is-invalid'));
            
            // TEMPORARILY enable disabled dropdowns before submitting (so their values are included)
            const presidentDropdown = document.getElementById('new_edit_university_president_approver');
            const vpDropdown = document.getElementById('new_edit_vp_approver');
            const divisionHeadDropdown = document.getElementById('new_edit_division_head_approver');
            const unitHeadDropdown = document.getElementById('new_edit_unit_head_approver');
            
            const wasPresidentDisabled = presidentDropdown?.disabled;
            const wasVpDisabled = vpDropdown?.disabled;
            const wasDivisionHeadDisabled = divisionHeadDropdown?.disabled;
            const wasUnitHeadDisabled = unitHeadDropdown?.disabled;
            
            if (wasPresidentDisabled) presidentDropdown.disabled = false;
            if (wasVpDisabled) vpDropdown.disabled = false;
            if (wasDivisionHeadDisabled) divisionHeadDropdown.disabled = false;
            if (wasUnitHeadDisabled) unitHeadDropdown.disabled = false;
            
            // Disable button during submission (prevent double-click)
            editSubmitBtn.disabled = true;
            
            const formData = new FormData(editForm);
            
            fetch(editForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                // RESTORE disabled state after submission
                if (wasPresidentDisabled && presidentDropdown) presidentDropdown.disabled = true;
                if (wasVpDisabled && vpDropdown) vpDropdown.disabled = true;
                if (wasDivisionHeadDisabled && divisionHeadDropdown) divisionHeadDropdown.disabled = true;
                if (wasUnitHeadDisabled && unitHeadDropdown) unitHeadDropdown.disabled = true;
                
                if (data.success) {
                    // Close the edit modal first before showing alerts
                    const editModalInstance = bootstrap.Modal.getInstance(editModal);
                    if (editModalInstance) {
                        editModalInstance.hide();
                    }
                    
                    // Show loading SweetAlert for 2 seconds, then success message
                    Swal.fire({
                        title: 'Updating...',
                        html: 'Please wait while we update your travel request',
                        timer: 2000,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                        willClose: () => {
                            // After 2 seconds, show success message
                            Swal.fire({
                                title: 'Success!',
                                text: data.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Reload page after success message
                                setTimeout(() => {
                                    window.location.reload();
                                }, 100);
                            });
                        }
                    });
                } else {
                    // Show validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const errorElement = document.getElementById('edit_' + field + '_error');
                            const inputElement = editForm.querySelector(`[name="${field}"]`);
                            
                            if (errorElement) {
                                errorElement.textContent = data.errors[field][0];
                                errorElement.style.display = 'block';
                            }
                            
                            if (inputElement) {
                                inputElement.classList.add('is-invalid');
                            }
                        });
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'An error occurred while updating the travel request.',
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An unexpected error occurred.',
                });
            })
            .finally(() => {
                editSubmitBtn.disabled = false;
                editSubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Update Travel Request';
            });
        });
        
        // Set flag when cancel button is clicked
        editCancelButton.addEventListener('click', function() {
            isEditCancelled = true;
        });
        
        // Reset form when modal is hidden
        editModal.addEventListener('hidden.bs.modal', function () {
            if (isEditCancelled) {
                editForm.reset();
                // Clear any error messages
                const errorElements = editForm.querySelectorAll('.invalid-feedback');
                errorElements.forEach(element => {
                    element.textContent = '';
                    element.style.display = 'none';
                });
                // Reset inputs
                const inputs = editForm.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid');
                });
            }
            
            // Reset the cancelled flag
            isEditCancelled = false;
        });
    });
    
    // Define Draft Tab button functions globally
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
                    // After 2 seconds, show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Travel order submitted for approval successfully.',
                        timer: 2000,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        // After success message closes, submit the form
                        var form = document.getElementById('submit-form-' + travelOrderId);
                        if (form) {
                            form.submit();
                        } else {
                            console.error('Submit form not found!');
                        }
                    });
                });
            }
        });
    };
    
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
            cancelButtonText: 'Cancel'
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
                    // After 2 seconds, show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Draft deleted successfully.',
                        timer: 2000,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        // After success message closes, submit the form
                        var form = document.getElementById('delete-form-' + travelOrderId);
                        if (form) {
                            form.submit();
                        } else {
                            console.error('Delete form not found!');
                        }
                    });
                });
            }
        });
    };
    
    // Edit draft travel order - opens the NEW EDIT modal with pre-filled data
    window.editDraftTravelOrder = function(id) {
        console.log('📝 Editing draft ID:', id);
        
        // Fetch the draft travel order details
        fetch(`/api/travel-orders/${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const travelOrder = data.data;
                    console.log('✈️ Travel Order Data:', travelOrder);
                    console.log('📋 Status:', travelOrder.status);
                    
                    // Set the form action
                    const form = document.getElementById('editTravelRequestForm');
                    form.action = `/travel-orders/${travelOrder.id}`;
                    
                    // Check if we should show Step 2 (always show, but disable for non-drafts)
                    const isDraft = travelOrder.status === 'draft';
                    // Read-only (non-draft) approvers are only displayed, so the rank filter must not hide them
                    document.getElementById('editTravelRequestModal').dataset.approversLocked = isDraft ? 'false' : 'true';
                    const step2Section = document.querySelector('#editTravelRequestModal .col-md-5.ps-3.border-start');
                    const step2Description = document.getElementById('edit_step2_description');
                    
                    // Always show Step 2, but disable dropdowns for non-draft status
                    if (step2Section) {
                        step2Section.style.display = '';
                        
                        // Update description text based on status
                        if (step2Description) {
                            if (!isDraft) {
                                step2Description.textContent = 'Approval Chain (Read-only - Cannot be changed)';
                            } else {
                                step2Description.textContent = 'Approval Chain (Select 1-2 Approvers Only)';
                            }
                        }
                        
                        // Disable/enable all approver dropdowns based on status
                        const presidentDropdown = document.getElementById('new_edit_university_president_approver');
                        const vpDropdown = document.getElementById('new_edit_vp_approver');
                        const divisionHeadDropdown = document.getElementById('new_edit_division_head_approver');
                        const unitHeadDropdown = document.getElementById('new_edit_unit_head_approver');
                        
                        // ALSO disable position field when approvers are locked
                        const positionField = document.getElementById('new_edit_position_id');
                        const positionLabel = document.querySelector('label[for="new_edit_position_id"]');
                        
                        if (!isDraft) {
                            // Disable dropdowns for non-draft status (read-only mode)
                            if (presidentDropdown) presidentDropdown.disabled = true;
                            if (vpDropdown) vpDropdown.disabled = true;
                            if (divisionHeadDropdown) divisionHeadDropdown.disabled = true;
                            if (unitHeadDropdown) unitHeadDropdown.disabled = true;
                            
                            // ALSO disable position field
                            if (positionField) {
                                positionField.disabled = true;
                                positionField.classList.add('bg-light', 'text-muted');
                            }
                            if (positionLabel) {
                                positionLabel.classList.add('text-muted');
                            }
                            
                            console.log('🔒 Step 2 shown in read-only mode - status is not draft');
                        } else {
                            // Enable dropdowns for draft status (editable mode)
                            if (presidentDropdown) presidentDropdown.disabled = false;
                            if (vpDropdown) vpDropdown.disabled = false;
                            if (divisionHeadDropdown) divisionHeadDropdown.disabled = false;
                            if (unitHeadDropdown) unitHeadDropdown.disabled = false;
                            
                            // ALSO enable position field
                            if (positionField) {
                                positionField.disabled = false;
                                positionField.classList.remove('bg-light', 'text-muted');
                            }
                            if (positionLabel) {
                                positionLabel.classList.remove('text-muted');
                            }
                            
                            console.log('✏️ Step 2 shown in editable mode - status is draft');
                        }
                    }
                    
                    // Show the modal FIRST before populating fields
                    const modal = new bootstrap.Modal(document.getElementById('editTravelRequestModal'));
                    modal.show();
                    console.log('✅ Edit modal opened');
                                    
                    // STEP 1: Populate Position dropdown (this will trigger approver population via change event)
                    const positionSelect = document.getElementById('new_edit_position_id');
                    if (positionSelect && travelOrder.emp_position_id) {
                        positionSelect.value = travelOrder.emp_position_id;
                        console.log('✅ Position set to:', travelOrder.emp_position_id, '- Display value:', positionSelect.options[positionSelect.selectedIndex]?.text);
                                        
                        // Trigger change event IMMEDIATELY to populate approver dropdowns
                        const event = new Event('change', { bubbles: true });
                        positionSelect.dispatchEvent(event);
                        console.log('🔄 Triggered position change event to populate approvers');
                                        
                        // Select approvers AFTER a SHORT delay (just enough for dropdowns to populate)
                        setTimeout(() => {
                            const presidentDropdown = document.getElementById('new_edit_university_president_approver');
                            const vpDropdown = document.getElementById('new_edit_vp_approver');
                            const divisionHeadDropdown = document.getElementById('new_edit_division_head_approver');
                            const unitHeadDropdown = document.getElementById('new_edit_unit_head_approver');
                                            
                            console.log('🔍 Dropdown options after population:');
                            console.log('  President:', presidentDropdown ? presidentDropdown.options.length : 0, 'options');
                            console.log('  VP:', vpDropdown ? vpDropdown.options.length : 0, 'options');
                            console.log('  Division Head:', divisionHeadDropdown ? divisionHeadDropdown.options.length : 0, 'options');
                            console.log('  Unit Head:', unitHeadDropdown ? unitHeadDropdown.options.length : 0, 'options');
                                            
                            const approver1Id = travelOrder.approver1_employee_id;
                            const approver2Id = travelOrder.approver2_employee_id;
                            console.log('💾 Saved approvers - Approver 1:', approver1Id, 'Approver 2:', approver2Id);
                                            
                            function selectApproverInDropdown(approverId, dropdownName, dropdown) {
                                if (!approverId || !dropdown) return false;
                                const found = Array.from(dropdown.options).some(opt => opt.value == approverId);
                                if (found) {
                                    dropdown.value = approverId;
                                    console.log('✅', approverId, 'SELECTED in', dropdownName, 'dropdown');
                                    return true;
                                } else {
                                    console.log('⚠️', approverId, 'NOT FOUND in', dropdownName, 'dropdown');
                                    return false;
                                }
                            }
                                            
                            let approver1Selected = false;
                            if (approver1Id) {
                                approver1Selected = selectApproverInDropdown(approver1Id, 'President', presidentDropdown) ||
                                                   selectApproverInDropdown(approver1Id, 'VP', vpDropdown) ||
                                                   selectApproverInDropdown(approver1Id, 'Division Head', divisionHeadDropdown) ||
                                                   selectApproverInDropdown(approver1Id, 'Unit Head', unitHeadDropdown);
                            }
                                            
                            let approver2Selected = false;
                            if (approver2Id) {
                                approver2Selected = selectApproverInDropdown(approver2Id, 'President', presidentDropdown) ||
                                                   selectApproverInDropdown(approver2Id, 'VP', vpDropdown) ||
                                                   selectApproverInDropdown(approver2Id, 'Division Head', divisionHeadDropdown) ||
                                                   selectApproverInDropdown(approver2Id, 'Unit Head', unitHeadDropdown);
                            }
                                            
                            if (approver1Selected && approver2Selected) {
                                console.log('🎉 SUCCESS: Both approvers selected!');
                            } else if (!approver1Id && !approver2Id) {
                                console.log('ℹ️ No approvers saved for this draft');
                            } else {
                                console.log('❌ Could not select approvers');
                            }
                        }, 200); // Reduced from 500ms to 200ms
                    }
                                    
                    // Now populate other Step 1 fields immediately
                    setTimeout(() => {
                        // Date From
                        const dateFromField = document.getElementById('new_edit_date_from');
                        if (dateFromField && travelOrder.date_from) {
                            const dateFrom = new Date(travelOrder.date_from);
                            dateFromField.value = dateFrom.toISOString().split('T')[0];
                            console.log('✅ Date From:', dateFromField.value);
                        }
                                        
                        // Date To
                        const dateToField = document.getElementById('new_edit_date_to');
                        if (dateToField && travelOrder.date_to) {
                            const dateTo = new Date(travelOrder.date_to);
                            dateToField.value = dateTo.toISOString().split('T')[0];
                            console.log('✅ Date To:', dateToField.value);
                        }
                                        
                        // Departure Time
                        const departureTimeField = document.getElementById('new_edit_departure_time');
                        if (departureTimeField && travelOrder.departure_time) {
                            const timeParts = travelOrder.departure_time.split(':');
                            departureTimeField.value = timeParts[0] + ':' + timeParts[1];
                            console.log('✅ Departure Time:', departureTimeField.value);
                        }
                                        
                        // Destination
                        const destinationField = document.getElementById('new_edit_destination');
                        if (destinationField && travelOrder.destination) {
                            destinationField.value = travelOrder.destination;
                            console.log('✅ Destination:', destinationField.value);
                        }
                                        
                        // Purpose
                        const purposeField = document.getElementById('new_edit_purpose');
                        if (purposeField && travelOrder.purpose) {
                            purposeField.value = travelOrder.purpose;
                            console.log('✅ Purpose:', purposeField.value);
                        }
                                        
                        console.log('🎉 All Step 1 fields populated!');
                    }, 50); // Very short delay just to ensure modal is visible
                    
                } else {
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
    };
    
    console.log('✓ Draft button functions loaded globally!');
    
    // Submit draft for approval function
    window.submitDraftForApproval = function(id) {
        Swal.fire({
            title: 'Submit for Approval?',
            text: 'Are you sure you want to submit this travel request for approval? You will not be able to edit it after submission.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1e6031',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, submit it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading indicator
                Swal.fire({
                    title: 'Submitting...',
                    text: 'Please wait while we submit your travel request.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    timer: 2000,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                }).then(() => {
                    // Submit the draft for approval
                    fetch(`/travel-orders/${id}/submit-for-approval`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Submitted!',
                                text: data.message || 'Your travel request has been submitted for approval.',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Reload page or redirect to pending tab
                                window.location.href = window.location.href.split('?')[0] + '?tab=pending';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to submit the travel request.'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error submitting draft:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while submitting the travel request.'
                        });
                    });
                });
            }
        });
    };
    
    // Cancel button click handler - reset form to original database values
    document.addEventListener('DOMContentLoaded', function() {
        const cancelBtn = document.getElementById('cancelEditBtn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                console.log('❌ Cancel button clicked - marking edit as cancelled');
                isEditCancelled = true;
                
                // Reset form to original database values
                if (originalDraftData) {
                    const destinationField = document.querySelector('[name="destination"]');
                    const dateFromField = document.querySelector('[name="date_from"]');
                    const dateToField = document.querySelector('[name="date_to"]');
                    const departureTimeField = document.querySelector('[name="departure_time"]');
                    const purposeField = document.querySelector('[name="purpose"]');
                    const positionField = document.querySelector('[name="position_id"]');
                    
                    // Restore original values
                    if (destinationField) destinationField.value = originalDraftData.destination || '';
                    if (dateFromField) dateFromField.value = originalDraftData.date_from || '';
                    if (dateToField) dateToField.value = originalDraftData.date_to || '';
                    if (departureTimeField) departureTimeField.value = originalDraftData.departure_time || '';
                    if (purposeField) purposeField.value = originalDraftData.purpose || '';
                    if (positionField && originalDraftData.position_id) {
                        positionField.value = originalDraftData.position_id;
                        // Trigger change event to update approvers
                        const event = new Event('change');
                        positionField.dispatchEvent(event);
                    }
                    
                    console.log('✅ Form reset to original values:', originalDraftData);
                }
            });
        }
        
        // Note: The modal close handler for edit functionality is handled above
        // This section has been removed as it references undefined variables
    });
    </script>
    
    
    <script>
    // Excel preview function - direct download with confirmation
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
    
@endsection