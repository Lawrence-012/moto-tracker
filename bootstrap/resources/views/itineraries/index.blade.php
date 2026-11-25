@extends('layouts.motorpool-admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="main">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-bold" style="color: #1e6031; font-size: 1.75rem;">Itineraries</h2>
                
                <div class="flex items-center gap-2">
                    <form id="searchForm" method="GET" action="{{ route('itinerary.index') }}" class="flex">
                        <!-- Preserve current tab in search -->
                        <input type="hidden" name="tab" value="{{ $tab ?? 'pending' }}">
                        <input type="text" name="search" 
                               class="form-input"
                               placeholder="Search itineraries..." 
                               value="{{ request('search') }}"
                               style="height: 32px; width: 250px; font-size: 0.85rem; border: 1px solid #1e6031; border-radius: 0.375rem 0 0 0.375rem; padding: 0 12px;">
                        <button type="submit" 
                                style="background-color: #1e6031; color: #ffffff; border: 1px solid #1e6031; height: 32px; width: 40px; border-radius: 0 0.375rem 0.375rem 0; cursor:pointer;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <button type="button" 
                            onclick="openCreateItineraryModal()"
                            class="inline-flex items-center px-3 py-1.5 bg-[#1e6031] border border-[#1e6031] rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#103c1e] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150">
                        <i class="fas fa-plus mr-1 text-xs"></i> Create Itinerary
                    </button>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex space-x-8">
                    <button type="button" 
                            class="tab-button pb-3 px-1 border-b-2 font-medium text-sm {{ ($tab ?? 'pending') === 'pending' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            data-tab="pending">
                        Pending
                    </button>
                    <button type="button" 
                            class="tab-button pb-3 px-1 border-b-2 font-medium text-sm {{ ($tab ?? 'pending') === 'approved' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            data-tab="approved">
                        Approved
                    </button>
                    <button type="button" 
                            class="tab-button pb-3 px-1 border-b-2 font-medium text-sm {{ ($tab ?? 'pending') === 'completed' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            data-tab="completed">
                        Completed
                    </button>
                    <button type="button" 
                            class="tab-button pb-3 px-1 border-b-2 font-medium text-sm {{ ($tab ?? 'pending') === 'cancelled' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            data-tab="cancelled">
                        Cancelled
                    </button>
                </nav>
            </div>

            <!-- Current Tab Display -->
            <div class="tab-content" id="current-tab-display">
                <!-- Table with headers always shown -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead style="background-color: #1e6031; color: white;">
                            <tr>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">No.</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Driver</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Vehicle</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Purpose</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Destination</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Date</th>
                                <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="itinerary-table-body">
                            @if($currentTabItineraries->count() > 0)
                                @foreach($currentTabItineraries as $index => $itinerary)
                                    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium">{{ $loop->iteration }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <div class="font-semibold" style="font-size: 0.8rem;">{{ $itinerary->driver?->full_name ?? 'Not Assigned' }}</div>
                                            <div class="text-gray-600 text-xs" style="margin-top: 2px;">Contact Number: {{ $itinerary->driver?->contact_num ?? '-' }}</div>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                                            @if($itinerary->vehicle)
                                                <div style="font-size: 0.75rem;">Model: {{ $itinerary->vehicle->model }}</div>
                                                <div class="text-gray-600" style="font-size: 0.7rem;">Plate No: {{ $itinerary->vehicle->plate_number }}</div>
                                            @else
                                                Not Assigned
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-xs max-w-[200px]">{{ Str::limit($itinerary->purpose ?? 'No Purpose Specified', 50) }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs">{{ $itinerary->destination }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                                            @php
                                                $dateFrom = $itinerary->date_from instanceof \DateTimeInterface 
                                                    ? \Carbon\Carbon::instance($itinerary->date_from) 
                                                    : (\Carbon\Carbon::parse($itinerary->date_from) ?? null);
                                                $dateTo = $itinerary->date_to instanceof \DateTimeInterface 
                                                    ? \Carbon\Carbon::instance($itinerary->date_to) 
                                                    : (\Carbon\Carbon::parse($itinerary->date_to) ?? null);
                                                
                                                if ($dateFrom && $dateTo) {
                                                    if ($dateFrom->isSameDay($dateTo)) {
                                                        // Same date: Jan 6, 2026
                                                        echo $dateFrom->format('M j, Y');
                                                    } elseif ($dateFrom->month == $dateTo->month && $dateFrom->year == $dateTo->year) {
                                                        // Same month: Jan 6-7, 2026
                                                        echo $dateFrom->format('M j') . '-' . $dateTo->format('j, Y');
                                                    } elseif ($dateFrom->year == $dateTo->year) {
                                                        // Same year: Jan 31 - Feb 1, 2026
                                                        echo $dateFrom->format('M j') . ' - ' . $dateTo->format('M j, Y');
                                                    } else {
                                                        // Different years: Jan 31, 2026 - Feb 1, 2027
                                                        echo $dateFrom->format('M j, Y') . ' - ' . $dateTo->format('M j, Y');
                                                    }
                                                } else {
                                                    echo 'Not Assigned';
                                                }
                                            @endphp
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                            <div class="action-buttons flex justify-center gap-1">
                                                <button type="button"
                                                        class="btn view-btn border inline-flex items-center justify-center"
                                                        title="View Itinerary Details"
                                                        onclick="loadAndShowItinerary({{ $itinerary->id }})">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                @if($tab === 'completed')
                                                    {{-- Completed tab: Only show Archive button --}}
                                                    <button type="button" 
                                                            class="btn archive-btn border delete-btn inline-flex items-center justify-center"
                                                            title="Archive Itinerary"
                                                            onclick="confirmArchive({{ $itinerary->id }})">
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                @elseif($itinerary->status !== 'Approved' && $itinerary->status !== 'Cancelled')
                                                    {{-- Other tabs: Show Edit and Archive buttons --}}
                                                    <button type="button"
                                                            class="btn edit-btn border inline-flex items-center justify-center"
                                                            title="Edit Itinerary"
                                                            onclick="loadAndShowEditItinerary({{ $itinerary->id }})">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button type="button" 
                                                            class="btn archive-btn border delete-btn inline-flex items-center justify-center"
                                                            title="Archive Itinerary"
                                                            onclick="confirmArchive({{ $itinerary->id }})">
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                @endif
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
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                                            @if($tab === 'pending')
                                                No pending itineraries
                                            @elseif($tab === 'approved')
                                                No approved itineraries
                                            @elseif($tab === 'completed')
                                                No completed itineraries
                                            @else
                                                No cancelled itineraries
                                            @endif
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            @if($tab === 'pending')
                                                Get started by creating a new itinerary.
                                            @else
                                                There are no itineraries in this tab.
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination - Always shown -->
                <div class="flex justify-between items-center mt-4">
                    <div class="text-sm text-gray-600">
                        @if($currentTabItineraries->count() > 0)
                            Showing {{ $currentTabItineraries->firstItem() ?? 0 }} to {{ $currentTabItineraries->lastItem() ?? 0 }} of {{ $currentTabItineraries->total() }} itineraries
                        @else
                            Showing 0 to 0 of 0 itineraries
                        @endif
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($currentTabItineraries->count() > 0 && $currentTabItineraries->lastPage() > 1)
                            {{ $currentTabItineraries->appends(['search' => request('search'), 'tab' => request('tab')])->links() }}
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
}

.page-item {
    margin: 0 2px;
}

.page-item.active .page-link {
    background-color: #1e6031;
    border-color: #1e6031;
    color: white;
}

.page-item.disabled .page-link {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #6c757d;
    cursor: not-allowed;
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>
function confirmArchive(itineraryId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Are you sure you want to archive this itinerary? This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1e6031',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, archive it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Archiving...',
                text: 'Please wait while we archive the itinerary.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/itinerary/${itineraryId}`;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            
            form.appendChild(csrf);
            form.appendChild(method);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const currentTabDisplay = document.getElementById('current-tab-display');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Update active tab button
            tabButtons.forEach(btn => {
                btn.classList.remove('border-[#1e6031]', 'text-[#1e6031]');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('border-[#1e6031]', 'text-[#1e6031]');
            
            // Update URL and reload page with new tab
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.location.href = url.toString();
        });
    });
});

// Function to view itinerary as Excel
function viewItineraryExcel(itineraryId) {
    // Open the Excel in modal instead of new tab
    openExcelModal(`/itinerary/${itineraryId}/excel`);
}

// Function to load and show itinerary details in modal
function loadAndShowItinerary(itineraryId) {
    // Fetch itinerary details from API
    fetch(`/api/itineraries/${itineraryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
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
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while loading itinerary details. Please try again.'
            });
        });
}

// Function to load and show edit itinerary modal
function loadAndShowEditItinerary(itineraryId) {
    // Fetch itinerary details from API
    fetch(`/api/itineraries/${itineraryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show the edit itinerary modal with the data
                showEditItineraryModal(data.itinerary);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to load itinerary details.'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while loading itinerary details. Please try again.'
            });
        });
}

// Excel Modal for Itineraries and Trip Tickets - direct download
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

<!-- Excel Modal -->
<!-- Include Modal Partial -->
@include('itineraries.modals.create-itinerary-modal')
@include('itineraries.modals.view-itinerary-modal')
@include('itineraries.modals.edit-itinerary-modal')
@endsection