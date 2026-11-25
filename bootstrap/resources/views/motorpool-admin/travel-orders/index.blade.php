@extends('layouts.motorpool-admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Success Message -->
        @if(session('success'))
            <div class="mb-4 rounded-md bg-green-50 p-3">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-4 w-4 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-2">
                        <p class="text-sm font-medium text-green-800">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="main">
            <!-- Header with Title, Search and Create Button -->
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-bold" style="color: #1e6031; font-size: 1.75rem;">Travel Orders</h2>
                
                <div class="flex items-center gap-2">
                    <form id="searchForm" method="GET" action="{{ route('motorpool.travel-orders.index') }}" class="flex">
                        <input type="text" name="search" 
                               class="form-input"
                               placeholder="Search travel orders..." 
                               value="{{ request('search') }}"
                               style="height: 32px; width: 250px; font-size: 0.85rem; border: 1px solid #1e6031; border-radius: 0.375rem 0 0 0.375rem; padding: 0 12px;">
                        <button type="submit" 
                                style="background-color: #1e6031; color: #ffffff; border: 1px solid #1e6031; height: 32px; width: 40px; border-radius: 0 0.375rem 0.375rem 0; cursor:pointer;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <button type="button" onclick="openCreateTravelRequestModal()"
                            class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-[#1e6031] hover:bg-[#164f2a] border border-transparent rounded-lg shadow-sm transition-all duration-200"
                            style="height: 32px;">
                        <i class="fas fa-plus mr-2"></i> Create Travel Order
                    </button>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex space-x-8">
                    <button type="button" 
                            class="tab-button pb-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'pending' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            data-tab="pending">
                        Approved
                    </button>
                    <button type="button" 
                            class="tab-button pb-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'completed' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            data-tab="completed">
                        Completed
                    </button>
                    <button type="button" 
                            class="tab-button pb-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'cancelled' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            data-tab="cancelled">
                        Cancelled
                    </button>
                </nav>
            </div>

            <!-- Travel Orders Table -->
            @if($travelOrders->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead style="background-color: #1e6031; color: white;">
                            <tr>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">No.</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Employee Name</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Purpose</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Destination</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Date</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Time</th>
                                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Status</th>
                                <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="travel-orders-table-body">
                            @foreach($travelOrders as $index => $travelOrder)
                                <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                                    <td class="px-3 py-2 whitespace-nowrap text-sm font-medium">{{ $travelOrders->firstItem() + $loop->index }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <div class="font-semibold" style="font-size: 0.9rem;">{{ $travelOrder->employee?->first_name }} {{ $travelOrder->employee?->middle_name ? substr($travelOrder->employee?->middle_name, 0, 1) . '. ' : '' }}{{ $travelOrder->employee?->last_name }}</div>
                                        <div class="text-gray-600 text-xs mt-1">Contact Number: {{ $travelOrder->employee?->contact_num ?? '-' }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-sm max-w-[200px]">
                                        {{ Str::limit($travelOrder->purpose ?? 'No Purpose Specified', 50) }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        {{ $travelOrder->destination }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        @php
                                            $dateFrom = $travelOrder->date_from instanceof \DateTimeInterface 
                                                ? \Carbon\Carbon::instance($travelOrder->date_from) 
                                                : (\Carbon\Carbon::parse($travelOrder->date_from) ?? null);
                                            $dateTo = $travelOrder->date_to instanceof \DateTimeInterface 
                                                ? \Carbon\Carbon::instance($travelOrder->date_to) 
                                                : (\Carbon\Carbon::parse($travelOrder->date_to) ?? null);
                                            
                                            if ($dateFrom && $dateTo) {
                                                if ($dateFrom->isSameDay($dateTo)) {
                                                    echo $dateFrom->format('M j, Y');
                                                } elseif ($dateFrom->month == $dateTo->month && $dateFrom->year == $dateTo->year) {
                                                    echo $dateFrom->format('M j') . '-' . $dateTo->format('j, Y');
                                                } elseif ($dateFrom->year == $dateTo->year) {
                                                    echo $dateFrom->format('M j') . ' - ' . $dateTo->format('M j, Y');
                                                } else {
                                                    echo $dateFrom->format('M j, Y') . ' - ' . $dateTo->format('M j, Y');
                                                }
                                            } else {
                                                echo 'Not Assigned';
                                            }
                                        @endphp
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        @if($travelOrder->departure_time instanceof \DateTimeInterface)
                                            {{ $travelOrder->departure_time->format('g:i A') }}
                                        @elseif($travelOrder->departure_time)
                                            {{ date('g:i A', strtotime($travelOrder->departure_time)) }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        @php
                                            $statusColor = 'bg-green-100 text-green-800';
                                            if($travelOrder->status === 'completed') {
                                                $statusColor = 'bg-green-100 text-green-800';
                                            } elseif($travelOrder->status === 'cancelled') {
                                                $statusColor = 'bg-red-100 text-red-800';
                                            }
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                            {{ ucfirst($travelOrder->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                        @php
                                            $completedTripTicketId = null;
                                            if ($tab === 'completed') {
                                                $completedTripTicketId = \App\Models\TripTicket::whereHas('itinerary', function ($q) use ($travelOrder) {
                                                        $q->whereJsonContains('travel_order_id', $travelOrder->id);
                                                    })
                                                    ->where('status', 'Completed')
                                                    ->orderByDesc('actual_arrival_at')
                                                    ->value('id');
                                            }
                                        @endphp
                                        <div class="action-buttons flex justify-center gap-1">
                                            <button type="button" onclick="viewTravelOrderModal({{ $travelOrder->id }}, {{ $completedTripTicketId ?? 'null' }})" 
                                                    class="btn view-btn border inline-flex items-center justify-center"
                                                    title="View Travel Order Details">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="flex justify-between items-center mt-4">
                    <div class="text-sm text-gray-600">
                        Showing {{ $travelOrders->firstItem() ?? 0 }} to {{ $travelOrders->lastItem() ?? 0 }} of {{ $travelOrders->total() }} results
                    </div>
                    <div class="flex items-center space-x-2">
                        <nav>
                            <ul class="pagination">
                                @if($travelOrders->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link">Prev</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a href="{{ $travelOrders->previousPageUrl() }}" class="page-link">Prev</a>
                                    </li>
                                @endif
                                
                                <li class="page-item active">
                                    <span class="page-link">{{ $travelOrders->currentPage() }}</span>
                                </li>
                                
                                @if($travelOrders->hasMorePages())
                                    <li class="page-item">
                                        <a href="{{ $travelOrders->nextPageUrl() }}" class="page-link">Next</a>
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
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No approved travel orders found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($search)
                            No travel orders match your search "{{ $search }}".
                        @elseif($tab === 'pending')
                            There are currently no approved travel orders.
                        @elseif($tab === 'completed')
                            There are currently no completed travel orders.
                        @elseif($tab === 'cancelled')
                            There are currently no cancelled travel orders.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

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
            <form method="post" action="{{ route('motorpool.travel-orders.store') }}" id="createTravelRequestForm">
                @csrf
                <div class="modal-body px-4 py-2">
                    <div class="row">
                        <!-- Left Column - Travel Request Details -->
                        <div class="col-md-7 pe-3">
                            <h6 class="fw-semibold text-success mb-2" style="font-size: 0.9rem;">Step 1</h6>
                            
                            <!-- Employee Name Field -->
                            <div class="mb-2">
                                <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Employee Name <span class="text-danger">*</span></label>
                                <select name="employee_id" id="employee_id" class="form-control form-control-sm border-success" required>
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $employee)
                                        @php
                                            $positionData = $employee->positions->map(function($pos) {
                                                return [
                                                    'id' => $pos->id,
                                                    'position_name' => $pos->position_name,
                                                    'office_name' => $pos->office ? $pos->office->office_name : '',
                                                    'is_unit_head' => $pos->is_unit_head,
                                                    'is_division_head' => $pos->is_division_head,
                                                    'is_vp' => $pos->is_vp,
                                                    'is_president' => $pos->is_president
                                                ];
                                            })->values();
                                        @endphp
                                        <option value="{{ $employee->id }}" 
                                                data-positions='@json($positionData)'>
                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="employee_id_error"></div>
                            </div>
                            
                            <!-- Position Field -->
                            <div class="mb-2">
                                <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Position <span class="text-danger">*</span></label>
                                <select name="position_id" id="position_id" class="form-control form-control-sm border-success" required disabled>
                                    <option value="">Select a position</option>
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
                                <div class="input-group input-group-sm">
                                    <input type="text" name="destination" id="to_destination" readonly
                                           onclick="openDestinationMapModal()"
                                           class="form-control form-control-sm border-success"
                                           style="cursor: pointer; background-color: #fff;"
                                           placeholder="Type an address or place name" required>
                                    <button type="button" class="btn btn-outline-success" onclick="openDestinationMapModal()">
                                        <i class="fas fa-magnifying-glass"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="destination_error"></div>
                                <p class="mt-1 mb-0" style="font-size: 0.7rem; color: #6c757d;">
                                    <i class="fas fa-circle-info me-1"></i>Pick or search a place to drop a pin - this is what the driver will see as the exact destination.
                                </p>
                                <input type="hidden" name="latitude" id="to_latitude">
                                <input type="hidden" name="longitude" id="to_longitude">
                                <input type="hidden" name="region" id="to_region">
                                <input type="hidden" name="distance_km" id="to_distance_km">
                            </div>
                            
                            <!-- Purpose Field -->
                            <div class="mb-2">
                                <label class="small fw-semibold text-success mb-1" style="font-size: 0.8rem;">Purpose <span class="text-danger">*</span></label>
                                <textarea name="purpose" rows="3" class="form-control form-control-sm border-success" placeholder="Provide a brief description of the purpose of your travel" required></textarea>
                                <div class="invalid-feedback" id="purpose_error"></div>
                            </div>
                        </div>
                        
                        <!-- Right Column - Approval Chain -->
                        <div class="col-md-5 ps-3 border-start">
                            <h6 class="fw-semibold text-success mb-2" style="font-size: 0.9rem;">Step 2</h6>
                            <p class="small text-muted mb-2" style="font-size: 0.75rem;">Approval Chain (Select 1-2 Approvers Only)</p>
                            
                            <!-- Auto Approved Checkbox -->
                            <div class="mb-2">
                                <div class="form-check">
                                    <input type="checkbox" name="auto_approved" id="auto_approved" class="form-check-input" value="1">
                                    <label class="form-check-label fw-semibold text-success" for="auto_approved" style="font-size: 0.85rem;">
                                        Auto Approved
                                    </label>
                                    <small class="form-text text-muted d-block mt-1" style="font-size: 0.7rem;">
                                        When checked, travel order will be automatically approved and sent directly to Motorpool.
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Approver Selection Fields -->
                            <div id="approver-selection-fields">
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" id="university_president_approver_label" style="font-size: 0.8rem;">University President</label>
                                    <select name="university_president_approver" id="university_president_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="" id="university_president_approver_default">Select University President</option>
                                    </select>
                                </div>
                                
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" id="vp_approver_label" style="font-size: 0.8rem;">Vice President (VP)</label>
                                    <select name="vp_approver" id="vp_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="" id="vp_approver_default">Select Vice President</option>
                                    </select>
                                </div>
                                
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" style="font-size: 0.8rem;">Division Head</label>
                                    <select name="division_head_approver" id="division_head_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="">Select Division Head</option>
                                    </select>
                                </div>
                                
                                <div class="mb-2">
                                    <label class="small text-muted mb-1" style="font-size: 0.8rem;">Unit Head</label>
                                    <select name="unit_head_approver" id="unit_head_approver" class="form-control form-control-sm border-success approver-select">
                                        <option value="">Select Unit Head</option>
                                    </select>
                                </div>
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

            <!-- Destination map picker modal placed inside modal-content -->
            <div id="dest-picker-modal" class="d-none" style="position: fixed; inset: 0; z-index: 10000; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
                <div class="bg-white d-flex flex-column" style="width: 100%; max-width: 480px; height: 88vh; max-height: 700px; border-radius: 12px; overflow: hidden; margin: 0 12px;">
                    <!-- Header -->
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom flex-shrink-0">
                        <h6 class="fw-semibold text-success mb-0">Set Destination</h6>
                        <button type="button" onclick="closeDestinationMapModal()" class="btn btn-sm btn-link text-muted p-0">
                            <i class="fas fa-xmark fs-5"></i>
                        </button>
                    </div>

                    <!-- Search bar -->
                    <div class="px-3 py-2 border-bottom flex-shrink-0 position-relative">
                        <div class="input-group input-group-sm">
                            <input type="text" id="dest-picker-search" autocomplete="off"
                                   placeholder="Search for a place..." class="form-control border-success">
                            <button type="button" id="dest-picker-search-btn" onclick="searchDestPicker()" class="btn btn-success">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                        </div>
                        <p id="dest-picker-error" class="d-none mt-1 mb-0 text-danger" style="font-size: 0.75rem;"></p>
                        <!-- Live suggestions -->
                        <div id="dest-picker-suggestions"
                             class="position-absolute bg-white rounded shadow border"
                             style="display:none; left: 12px; right: 12px; z-index: 10; margin-top: 2px; max-height: 240px; overflow-y: auto;"></div>
                    </div>

                    <!-- Map with fixed center pin -->
                    <div class="flex-grow-1 position-relative" style="min-height: 0; background: #f3f4f6;">
                        <div id="dest-picker-map" style="width: 100%; height: 100%;"></div>
                        <div class="position-absolute top-50 start-50 translate-middle" style="margin-top: -18px; pointer-events: none;">
                            <i class="fas fa-location-dot text-danger" style="font-size: 2.25rem; filter: drop-shadow(0 2px 3px rgba(0,0,0,0.35));"></i>
                        </div>
                    </div>

                    <!-- Confirm -->
                    <div class="px-3 py-2 border-top flex-shrink-0">
                        <p class="mb-1 text-muted" style="font-size: 0.7rem;">Pinned location:</p>
                        <p id="dest-picker-address" class="mb-2 fw-semibold text-truncate" style="font-size: 0.85rem;">Move the map to set the pin</p>
                        <button type="button" id="dest-picker-confirm-btn" onclick="confirmDestPicker()" class="btn btn-success w-100 btn-sm">
                            <i class="fas fa-check me-1"></i>Confirm Location
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('partials.google-maps-script')
<script src="{{ asset('js/gmaps-helpers.js') }}"></script>
<script>
let destPickerMap = null;
let destPickerLatLng = null;
let destPickerLabel = null;

function openDestinationMapModal() {
    const modal = document.getElementById('dest-picker-modal');
    modal.classList.remove('d-none');
    modal.style.display = 'flex';
    document.getElementById('dest-picker-error').classList.add('d-none');

    const existingText = document.getElementById('to_destination').value.trim();
    document.getElementById('dest-picker-search').value = existingText;

    const existingLat = document.getElementById('to_latitude').value;
    const existingLng = document.getElementById('to_longitude').value;
    const startCenter = (existingLat && existingLng)
        ? { lat: parseFloat(existingLat), lng: parseFloat(existingLng) }
        : GMAPS_DEFAULT_CENTER;

    setTimeout(() => {
        if (!destPickerMap) {
            destPickerMap = gmCreateMap('dest-picker-map', startCenter, existingLat ? 16 : 13);
            destPickerMap.addListener('idle', onDestPickerIdle);
        } else {
            destPickerMap.setCenter(startCenter);
            destPickerMap.setZoom(existingLat ? 16 : 13);
            google.maps.event.trigger(destPickerMap, 'resize');
            destPickerMap.setCenter(startCenter);
        }

        if (existingLat && existingLng) {
            reverseGeocodeDestPicker(startCenter);
        } else if (existingText) {
            searchDestPicker();
        }

        document.getElementById('dest-picker-search').focus();
    }, 100);
}

function closeDestinationMapModal() {
    const modal = document.getElementById('dest-picker-modal');
    modal.classList.add('d-none');
    modal.style.display = 'none';
}

let suppressNextDestPickerIdle = false;

function onDestPickerIdle() {
    if (suppressNextDestPickerIdle) {
        suppressNextDestPickerIdle = false;
        return;
    }
    const center = destPickerMap.getCenter();
    reverseGeocodeDestPicker({ lat: center.lat(), lng: center.lng() });
}

function reverseGeocodeDestPicker(latLng) {
    destPickerLatLng = latLng;
    destPickerLabel = null;
    document.getElementById('dest-picker-address').textContent = 'Locating...';
    document.getElementById('dest-picker-error').classList.add('d-none');
    document.getElementById('dest-picker-confirm-btn').disabled = true;

    new google.maps.Geocoder().geocode({ location: latLng }, (results, status) => {
        if (status === 'OK' && results && results.length > 0) {
            const poiResult = results.find(r => 
                r.types.includes('point_of_interest') || 
                r.types.includes('establishment') || 
                r.types.includes('premise')
            );
            
            const targetResult = poiResult || results[0];
            
            destPickerLabel = targetResult.formatted_address || readableGooglePlaceName(targetResult);
            destPickerLatLng.region = extractRegionFromGeocodeResult(results[0]);
            
            document.getElementById('dest-picker-address').textContent = destPickerLabel;
            document.getElementById('dest-picker-confirm-btn').disabled = false;
        } else {
            document.getElementById('dest-picker-address').textContent = 'Could not determine address for this spot';
            const errorEl = document.getElementById('dest-picker-error');
            errorEl.textContent = gmGeocodeErrorMessage(status, 'this location');
            errorEl.classList.remove('d-none');
        }
    });
}

function searchDestPicker() {
    const query = document.getElementById('dest-picker-search').value.trim();
    const errorEl = document.getElementById('dest-picker-error');
    errorEl.classList.add('d-none');

    if (!query) return;

    const searchBtn = document.getElementById('dest-picker-search-btn');
    const originalHtml = searchBtn.innerHTML;
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    new google.maps.Geocoder().geocode({ address: query + ', Philippines', region: 'ph' }, (results, status) => {
        searchBtn.disabled = false;
        searchBtn.innerHTML = originalHtml;

        if (status !== 'OK' || !results || results.length === 0) {
            errorEl.textContent = gmGeocodeErrorMessage(status, query);
            errorEl.classList.remove('d-none');
            return;
        }

        const loc = results[0].geometry.location;
        const latLng = { lat: loc.lat(), lng: loc.lng() };

        destPickerLatLng = latLng;
        destPickerLabel = query.length > 3 ? query : results[0].formatted_address;
        destPickerLatLng.region = extractRegionFromGeocodeResult(results[0]);

        document.getElementById('dest-picker-address').textContent = destPickerLabel;
        document.getElementById('dest-picker-confirm-btn').disabled = false;

        suppressNextDestPickerIdle = true;
        destPickerMap.setCenter(loc);
        destPickerMap.setZoom(16);
    });
}

function confirmDestPicker() {
    if (!destPickerLatLng || !destPickerLabel) return;

    document.getElementById('to_destination').value = destPickerLabel;
    document.getElementById('to_latitude').value = destPickerLatLng.lat;
    document.getElementById('to_longitude').value = destPickerLatLng.lng;
    document.getElementById('to_region').value = destPickerLatLng.region || '';

    const km = haversineKm(GMAPS_DEFAULT_CENTER.lat, GMAPS_DEFAULT_CENTER.lng, destPickerLatLng.lat, destPickerLatLng.lng);
    document.getElementById('to_distance_km').value = km.toFixed(2);

    closeDestinationMapModal();
}

function applyDestPickerSuggestion({ lat, lng, label }) {
    destPickerLatLng = { lat, lng };
    destPickerLabel = label;
    document.getElementById('dest-picker-address').textContent = label;
    document.getElementById('dest-picker-error').classList.add('d-none');
    document.getElementById('dest-picker-confirm-btn').disabled = false;

    suppressNextDestPickerIdle = true;
    destPickerMap.setCenter({ lat, lng });
    destPickerMap.setZoom(16);

    new google.maps.Geocoder().geocode({ location: { lat, lng } }, (results, status) => {
        const stillCurrent = destPickerLatLng && destPickerLatLng.lat === lat && destPickerLatLng.lng === lng;
        if (status === 'OK' && results && results.length > 0 && stillCurrent) {
            destPickerLatLng.region = extractRegionFromGeocodeResult(results[0]);
        }
    });
}

gmAttachPlaceAutocomplete(
    document.getElementById('dest-picker-search'),
    document.getElementById('dest-picker-suggestions'),
    applyDestPickerSuggestion
);

document.getElementById('dest-picker-search').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchDestPicker();
    }
});

document.getElementById('dest-picker-modal').addEventListener('click', (e) => {
    if (e.target.id === 'dest-picker-modal') {
        closeDestinationMapModal();
    }
});

document.getElementById('to_destination').addEventListener('input', () => {
    document.getElementById('to_latitude').value = '';
    document.getElementById('to_longitude').value = '';
    document.getElementById('to_region').value = '';
    document.getElementById('to_distance_km').value = '';
});
</script>

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

<!-- Modal for Travel Order Details -->
<div id="travelOrderModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 transition-all duration-200">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0" id="travelOrderModalContent">
        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #1e6031 0%, #2d7a3e 50%, #1e6031 100%);">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                    <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white">Travel Order Details</h3>
            </div>
        </div>
        
        <div id="travelOrderDetailsContent" class="px-6 py-4"></div>
        
        <div id="pdfViewerContent" class="hidden px-6 py-4">
            <iframe id="pdfFrame" src="" class="w-full h-[70vh] border-0" style="min-height: 500px;"></iframe>
        </div>
        
        <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2">
            <button type="button" onclick="viewPdfInNewTab()" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                <i class="fas fa-file-excel mr-1"></i> View Excel
            </button>
            <button type="button" onclick="closeTravelOrderModal()" class="px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                Close
            </button>
        </div>
    </div>
</div>

<!-- PDF Modal -->
<div id="pdfModal" class="fixed inset-0 z-[60] hidden overflow-y-auto h-full w-full flex items-center justify-center p-4 bg-black bg-opacity-75">
    <div class="relative w-full max-w-6xl h-[85vh] rounded-lg overflow-hidden shadow-2xl bg-white">
        <button id="closeModal" class="absolute top-3 right-3 z-10 text-red-500 hover:text-red-700 bg-white rounded-full p-2 shadow-md hover:bg-red-50 transition">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <iframe id="pdfFrame" src="" class="w-full h-full border-0" frameborder="0" style="background-color: #f3f4f6;"></iframe>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>
let currentTravelOrderId = null;
let detailsRouteMapInstance = null;
let detailsRouteMapOverlays = [];

function viewTravelOrderModal(travelOrderId, completedTripTicketId) {
    const modal = document.getElementById('travelOrderModal');
    const modalContent = document.getElementById('travelOrderModalContent');
    
    currentTravelOrderId = travelOrderId;
    modal.classList.remove('hidden');
    
    void modal.offsetWidth;
    
    modalContent.classList.remove('scale-95', 'opacity-0');
    modalContent.classList.add('scale-100', 'opacity-100');
    document.body.style.overflow = 'hidden';
    
    fetch(`/motorpool/travel-orders/${travelOrderId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const tableElement = doc.querySelector('table.w-full');
            if (tableElement) {
                showTravelOrderModal(tableElement.outerHTML, completedTripTicketId);
            } else {
                alert('Error loading travel order details');
                closeTravelOrderModal();
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error loading travel order details: ' + error.message);
            closeTravelOrderModal();
        });
}

function showTravelOrderModal(contentHtml, completedTripTicketId) {
    const contentDiv = document.getElementById('travelOrderDetailsContent');

    const routeSectionHtml = completedTripTicketId ? `
        <div class="mt-4 border-t border-gray-200 pt-4">
            <h4 class="text-sm font-bold text-gray-900 mb-2 flex items-center gap-2">
                <i class="fas fa-route text-[#1e6031]"></i> Route Taken
            </h4>
            <div id="details-route-map" style="height: 320px; width: 100%; border-radius: 8px; background:#f3f4f6;"></div>
            <div id="details-route-summary" class="mt-2 text-xs text-gray-500"></div>
            <div id="details-route-empty" class="hidden text-sm text-gray-500 text-center py-8">
                <i class="fas fa-map-marked-alt text-3xl mb-2 block text-gray-300"></i>
                No route was recorded for this trip (the driver may not have had location sharing enabled).
            </div>
        </div>
    ` : '';

    const cleanHtml = `
        <div class="text-sm text-gray-800">
            ${contentHtml}
            ${routeSectionHtml}
        </div>
    `;
    
    contentDiv.innerHTML = cleanHtml;

    if (completedTripTicketId) {
        renderDetailsRouteMap(completedTripTicketId);
    }
}

function renderDetailsRouteMap(tripTicketId) {
    detailsRouteMapInstance = null;
    detailsRouteMapOverlays = [];

    setTimeout(() => {
        const mapEl = document.getElementById('details-route-map');
        if (!mapEl) return;

        detailsRouteMapInstance = gmCreateMap('details-route-map');

        fetch(tripTrailUrlTemplate.replace('__ID__', tripTicketId))
            .then(r => r.json())
            .then(data => {
                const points = (data.points || []).map(p => ({ lat: parseFloat(p.latitude), lng: parseFloat(p.longitude) }));

                if (points.length === 0) {
                    document.getElementById('details-route-map').style.display = 'none';
                    document.getElementById('details-route-empty').classList.remove('hidden');
                    return;
                }

                const polyline = new google.maps.Polyline({ path: points, map: detailsRouteMapInstance, strokeColor: '#1e6031', strokeWeight: 4 });
                const startMarker = gmCircleMarker(detailsRouteMapInstance, points[0], { radius: 6, color: '#1e6031', title: 'Departure' });
                gmBindInfoWindow(detailsRouteMapInstance, startMarker, 'Departure');
                const endMarker = gmCircleMarker(detailsRouteMapInstance, points[points.length - 1], { radius: 6, color: '#b91c1c', title: 'Arrival' });
                gmBindInfoWindow(detailsRouteMapInstance, endMarker, 'Arrival');
                detailsRouteMapOverlays.push(polyline, startMarker, endMarker);

                gmFitBoundsToPath(detailsRouteMapInstance, points, 20);

                document.getElementById('details-route-summary').textContent =
                    `${points.length} GPS points recorded along this trip.`;
            })
            .catch(() => {
                document.getElementById('details-route-map').style.display = 'none';
                document.getElementById('details-route-empty').classList.remove('hidden');
            });
    }, 50);
}

function viewPdfInNewTab() {
    if (currentTravelOrderId) {
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
                window.location.href = '/motorpool/travel-orders/' + currentTravelOrderId + '/excel';
            }
        });
    }
}

function closeTravelOrderModal() {
    const modal = document.getElementById('travelOrderModal');
    const modalContent = document.getElementById('travelOrderModalContent');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        currentTravelOrderId = null;
        if (detailsRouteMapInstance) {
            detailsRouteMapInstance = null;
        }
    }, 200);
}

document.getElementById('travelOrderModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTravelOrderModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTravelOrderModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchForm');
    if(searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = document.querySelector('[name="search"]');
            if(searchInput && searchInput.value.trim() === '') {
                window.location.href = '{{ route("motorpool.travel-orders.index") }}';
                e.preventDefault();
            }
        });
    }
    
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            tabButtons.forEach(btn => {
                btn.classList.remove('border-[#1e6031]', 'text-[#1e6031]');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('border-[#1e6031]', 'text-[#1e6031]');
            
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.location.href = url.toString();
        });
    });
});

window.openCreateTravelRequestModal = function() {
    const modalTitle = document.getElementById('createTravelRequestModalLabel');
    if (modalTitle) {
        modalTitle.textContent = 'Create New Travel Request';
    }
    
    const form = document.getElementById('createTravelRequestForm');
    form.action = "{{ route('motorpool.travel-orders.store') }}";
    
    const methodField = form.querySelector('input[name="_method"]');
    if (methodField) {
        methodField.remove();
    }
    
    form.reset();
    
    const positionSelect = document.getElementById('position_id');
    if (positionSelect) {
        positionSelect.innerHTML = '<option value="">Select a position</option>';
        positionSelect.disabled = true;
    }
    
    fetchApproverData();
    
    const modal = new bootstrap.Modal(document.getElementById('createTravelRequestModal'));
    modal.show();
}

function clearCreateFormData() {
    localStorage.removeItem('createTravelRequestFormData');
}

document.addEventListener('DOMContentLoaded', function() {
    clearCreateFormData();
    
    const employeeSelect = document.getElementById('employee_id');
    if (employeeSelect) {
        employeeSelect.addEventListener('change', handleEmployeeSelection);
    }
});

function handleEmployeeSelection() {
    const employeeSelect = document.getElementById('employee_id');
    const positionSelect = document.getElementById('position_id');
    
    positionSelect.innerHTML = '<option value="">Select a position</option>';
    
    const selectedOption = employeeSelect.options[employeeSelect.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        positionSelect.disabled = true;
        return;
    }
    
    const positionsData = JSON.parse(selectedOption.getAttribute('data-positions') || '[]');
    
    if (positionsData.length === 0) {
        positionSelect.disabled = true;
        positionSelect.innerHTML = '<option value="">No positions available</option>';
        return;
    }
    
    positionSelect.disabled = false;
    
    positionsData.forEach(position => {
        const option = document.createElement('option');
        option.value = position.id;
        
        let positionText = position.position_name;
        if (position.office_name) {
            positionText += ' - ' + position.office_name;
        }
        if (position.is_unit_head) {
            positionText += ' (Unit Head)';
        } else if (position.is_division_head) {
            positionText += ' (Division Head)';
        } else if (position.is_vp) {
            positionText += ' (VP)';
        } else if (position.is_president) {
            positionText += ' (President)';
        }
        
        option.textContent = positionText;
        positionSelect.appendChild(option);
    });
    
    positionSelect.onchange = function() {
        const selectedPositionId = this.value;
        if (selectedPositionId) {
            const selectedPosition = positionsData.find(p => p.id == selectedPositionId);
            if (selectedPosition) {
                updateApproverDropdowns(selectedPosition);
            }
        }
    };
}

function setupAutoApprovedToggle() {
    const autoApprovedCheckbox = document.getElementById('auto_approved');
    const approverFields = document.getElementById('approver-selection-fields');
    const approverError = document.getElementById('approver-error');
    
    if (autoApprovedCheckbox && approverFields) {
        autoApprovedCheckbox.addEventListener('change', function() {
            if (this.checked) {
                approverFields.style.display = 'none';
                if (approverError) approverError.classList.add('d-none');
                
                const approverSelects = document.querySelectorAll('.approver-select');
                approverSelects.forEach(select => {
                    select.value = '';
                    select.disabled = true;
                });
            } else {
                approverFields.style.display = 'block';
                
                const approverSelects = document.querySelectorAll('.approver-select');
                approverSelects.forEach(select => {
                    select.disabled = false;
                });
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupAutoApprovedToggle);
} else {
    setupAutoApprovedToggle();
}

function updateApproverDropdowns(position) {
    console.log('Position selected:', position);
}

function fetchApproverData() {
    fetch('/api/approvers/university-president')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('university_president_approver');
            const defaultOption = document.getElementById('university_president_approver_default');
            if (select && defaultOption && Array.isArray(data)) {
                select.innerHTML = '';
                select.appendChild(defaultOption);
                
                data.forEach(president => {
                    const option = document.createElement('option');
                    option.value = president.employee_id;
                    option.textContent = `${president.employee.first_name} ${president.employee.last_name}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error fetching university presidents:', error));
    
    fetch('/api/approvers/vp')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('vp_approver');
            const defaultOption = document.getElementById('vp_approver_default');
            if (select && defaultOption && Array.isArray(data)) {
                select.innerHTML = '';
                select.appendChild(defaultOption);
                
                data.forEach(vp => {
                    const option = document.createElement('option');
                    option.value = vp.employee_id;
                    option.textContent = `${vp.employee.first_name} ${vp.employee.last_name}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error fetching VPs:', error));
    
    fetch('/api/approvers/division-head')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('division_head_approver');
            if (select && Array.isArray(data)) {
                select.innerHTML = '<option value="">Select Division Head</option>';
                
                data.forEach(dh => {
                    const option = document.createElement('option');
                    option.value = dh.employee_id;
                    option.textContent = `${dh.employee.first_name} ${dh.employee.last_name}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error fetching division heads:', error));
    
    fetch('/api/approvers/unit-head')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('unit_head_approver');
            if (select && Array.isArray(data)) {
                select.innerHTML = '<option value="">Select Unit Head</option>';
                
                data.forEach(uh => {
                    const option = document.createElement('option');
                    option.value = uh.employee_id;
                    option.textContent = `${uh.employee.first_name} ${uh.employee.last_name}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error fetching unit heads:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    const approverSelects = document.querySelectorAll('.approver-select');
    const errorMessage = document.getElementById('approver-error');
    
    approverSelects.forEach(select => {
        select.addEventListener('change', function() {
            validateApproverSelection();
        });
    });
    
    function validateApproverSelection() {
        let selectedCount = 0;
        
        approverSelects.forEach(select => {
            if (select.value !== '') {
                selectedCount++;
            }
        });
        
        if (selectedCount > 2) {
            this.value = '';
            if (errorMessage) {
                errorMessage.classList.remove('d-none');
                setTimeout(() => {
                    errorMessage.classList.add('d-none');
                }, 3000);
            }
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.getElementById('createTravelRequestForm');
    
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            const autoApprovedCheckbox = document.getElementById('auto_approved');
            const isAutoApproved = autoApprovedCheckbox && autoApprovedCheckbox.checked;
            
            if (!isAutoApproved) {
                let selectedCount = 0;
                const approverSelects = document.querySelectorAll('.approver-select');
                
                approverSelects.forEach(select => {
                    if (select.value !== '') {
                        selectedCount++;
                    }
                });
                
                if (selectedCount === 0 || selectedCount > 2) {
                    e.preventDefault();
                    alert('Please select 1 or 2 approvers only.');
                    return false;
                }
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const createModal = document.getElementById('createTravelRequestModal');
    
    if (createModal) {
        createModal.addEventListener('shown.bs.modal', function() {
            const firstField = this.querySelector('select[name="employee_id"]');
            if (firstField) {
                firstField.focus();
            }
        });
    }
});
</script>

<div id="routeModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl">
        <div class="flex justify-between items-center px-5 py-3 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900"><i class="fas fa-route mr-1 text-[#1e6031]"></i> Route Taken</h3>
            <button type="button" onclick="closeTripRouteModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-4">
            <div id="route-map" style="height: 420px; width: 100%; border-radius: 8px; background:#f3f4f6;"></div>
            <div id="route-summary" class="mt-3 text-sm text-gray-600"></div>
            <div id="route-empty" class="hidden text-sm text-gray-500 text-center py-10">
                <i class="fas fa-map-marked-alt text-3xl mb-2 block text-gray-300"></i>
                No route was recorded for this trip (the driver may not have had location sharing enabled).
            </div>
        </div>
    </div>
</div>

<script>
let routeMapInstance = null;
let routeMapOverlays = [];
const tripTrailUrlTemplate = @json(route('api.trip-tickets.trail', ['id' => '__ID__']));

function closeTripRouteModal() {
    const modal = document.getElementById('routeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function viewTripRoute(tripTicketId) {
    const modal = document.getElementById('routeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.getElementById('route-empty').classList.add('hidden');
    document.getElementById('route-summary').textContent = '';
    document.getElementById('route-map').style.display = 'block';

    setTimeout(() => {
        if (!routeMapInstance) {
            routeMapInstance = gmCreateMap('route-map');
        } else {
            routeMapOverlays.forEach((overlay) => overlay.setMap(null));
            routeMapOverlays = [];
        }

        fetch(tripTrailUrlTemplate.replace('__ID__', tripTicketId))
            .then(r => r.json())
            .then(data => {
                const points = (data.points || []).map(p => ({ lat: parseFloat(p.latitude), lng: parseFloat(p.longitude) }));

                if (points.length === 0) {
                    document.getElementById('route-map').style.display = 'none';
                    document.getElementById('route-empty').classList.remove('hidden');
                    return;
                }

                const polyline = new google.maps.Polyline({ path: points, map: routeMapInstance, strokeColor: '#1e6031', strokeWeight: 4 });
                const startMarker = gmCircleMarker(routeMapInstance, points[0], { radius: 7, color: '#1e6031', title: 'Departure' });
                gmBindInfoWindow(routeMapInstance, startMarker, 'Departure');
                const endMarker = gmCircleMarker(routeMapInstance, points[points.length - 1], { radius: 7, color: '#b91c1c', title: 'Arrival' });
                gmBindInfoWindow(routeMapInstance, endMarker, 'Arrival');
                routeMapOverlays.push(polyline, startMarker, endMarker);

                gmFitBoundsToPath(routeMapInstance, points, 30);

                document.getElementById('route-summary').textContent =
                    `${points.length} GPS points recorded along this trip.`;
            })
            .catch(() => {
                document.getElementById('route-map').style.display = 'none';
                document.getElementById('route-empty').classList.remove('hidden');
            });
    }, 50);
}
</script>
@endsection