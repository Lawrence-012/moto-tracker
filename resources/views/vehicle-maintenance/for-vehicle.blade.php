@extends('layouts.motorpool-admin')

@section('content')
    
    @php
    // Helper function to convert to lowercase string for search
    function toLowerForSearch($value) {
        if (is_array($value)) {
            return strtolower(implode(' ', $value));
        }
        return strtolower($value ?? '');
    }
    
    // Helper function to convert to string for display
    function toDisplayString($value) {
        if (is_array($value)) {
            return implode(', ', $value);
        }
        return $value ?? '';
    }
    @endphp
    
    <!-- Back Button Row -->
    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <a href="{{ route('vehicles.show', $vehicle) }}" 
               class="inline-flex items-center px-4 py-2 bg-[#1e6031] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#103c1e] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
        </div>
    </div>
    
    <!-- Title, Search, and Add Record Row -->
    <div class="max-w-7xl mx-auto py-2 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <h3 class="text-xl font-semibold" style="color: #1e6031;">
                    {{ $vehicle->model ?? 'Unknown Vehicle' }} {{ $vehicle->plate_number ?? '' }} - Maintenance History
                </h3>
            </div>
    
            <div class="flex items-center gap-2">
                <!-- Search Bar -->
                <div class="flex">
                    <input type="text" id="searchMaintenance" placeholder="Search history..." 
                           style="height: 32px; width: 200px; font-size: 0.85rem; border: 1px solid #1e6031; border-radius: 0.375rem 0 0 0.375rem; padding: 0 12px;" 
                           value="">
                    <button type="button" 
                            style="background-color: #1e6031; color: #ffffff; border: 1px solid #1e6031; height: 32px; width: 40px; border-radius: 0 0.375rem 0.375rem 0; cursor:pointer;">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <!-- Add Record Button -->
                <button type="button" id="addMaintenanceRecordBtn" 
                   class="inline-flex items-center px-4 py-2 bg-[#1e6031] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#103c1e] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-plus-circle mr-2"></i> Add Record
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tabs and PDF Button -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 pb-2">
            <nav class="flex space-x-6" aria-label="Tabs">
                <a href="{{ request()->url() . '?tab=pending' }}" 
                   class="tab-button {{ ($activeTab ?? 'pending') === 'pending' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                   style="text-decoration: none;">
                    Pending
                </a>
                <a href="{{ request()->url() . '?tab=ongoing' }}" 
                   class="tab-button {{ ($activeTab ?? 'pending') === 'ongoing' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                   style="text-decoration: none;">
                    Under Maintenance
                </a>
                <a href="{{ request()->url() . '?tab=completed' }}" 
                   class="tab-button {{ ($activeTab ?? 'pending') === 'completed' ? 'border-[#1e6031] text-[#1e6031] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 text-sm transition-colors duration-200 no-underline"
                   style="text-decoration: none;">
                    Completed
                </a>
            </nav>
            
        </div>
    </div>
    
    <!-- PDF Preview Modal -->
    <div id="maintenancePdfPreviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-6xl h-full flex flex-col shadow-lg">
            <iframe id="maintenancePdfIframe" class="flex-1 w-full h-full border-none rounded-b-2xl"></iframe>
        </div>
    </div>
    

    <!-- Tab Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="tab-content" id="maintenanceTabContent">
                        <div class="tab-pane fade {{ ($activeTab ?? 'pending') === 'pending' ? 'show active' : '' }}" id="pending" role="tabpanel">
                            <!-- Pending Maintenance Records List -->
                            @php
                                $pendingRecords = $maintenanceRecords->filter(function($record) {
                                    return ($record->status === 'Pending' || $record->status === 'Need Maintenance') && !$record->archived;
                                });
                            @endphp
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead style="background-color: #1e6031; color: white;">
                                        <tr>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">No.</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Nature of Work</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Maintenance Type</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Mechanics Assigned</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Date Started</th>
                                            <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($pendingRecords->count() > 0)
                                            @foreach($pendingRecords as $index => $record)
                                            <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#004d00] font-medium">{{ $loop->iteration }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ $record->nature_of_work }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ $record->make_or_type }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ toDisplayString($record->mechanic_assigned) }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ $record->date_started->format('M d, Y') }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                                    @if($record->status === 'Archived')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                            Archived
                                                        </span>
                                                    @else
                                                        <div class="action-buttons flex justify-center space-x-1">
                                                            <button type="button" 
                                                                    class="btn view-btn border inline-flex items-center justify-center"
                                                                    title="View Maintenance"
                                                                    onclick="showMaintenanceDetails({{ $record->id }})">
                                                                <i class="fas fa-eye"></i> View
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn edit-btn border inline-flex items-center justify-center"
                                                                    title="Edit Maintenance"
                                                                    onclick="openEditMaintenanceModal({{ $record->id }})">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn start-btn border inline-flex items-center justify-center"
                                                                    title="Start Maintenance"
                                                                    onclick="confirmStart('{{ route('vehicle-maintenance.start', $record) }}')">
                                                                <i class="fas fa-play"></i> Start
                                                            </button>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        @else
                                            <!-- Empty state row -->
                                            <tr>
                                                <td colspan="6" class="px-3 py-12 text-center">
                                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No pending maintenance records</h3>
                                                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new maintenance record for this vehicle.</p>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination - Always shown -->
                            <div class="flex justify-between items-center mt-4">
                                <div class="text-sm text-gray-600">
                                    @if($pendingRecords->count() > 0)
                                        Showing 1 to {{ $pendingRecords->count() }} of {{ $pendingRecords->count() }} maintenance records
                                    @else
                                        Showing 0 to 0 of 0 maintenance records
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2">
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
                                </div>
                            </div>
                        </div> <!-- End of Pending tab -->
                        
                        <div class="tab-pane fade {{ ($activeTab ?? 'pending') === 'ongoing' ? 'show active' : '' }}" id="ongoing" role="tabpanel">
                            @php
                                $ongoingRecords = $maintenanceRecords->filter(function($record) {
                                    return $record->status === 'Under Maintenance';
                                });
                            @endphp
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead style="background-color: #1e6031; color: white;">
                                        <tr>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">No.</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Nature of Work</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Maintenance Type</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Mechanics Assigned</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Date Started</th>
                                            <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($ongoingRecords->count() > 0)
                                            @foreach($ongoingRecords as $index => $record)
                                            <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#004d00] font-medium">{{ $loop->iteration }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ $record->nature_of_work }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ $record->make_or_type }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ toDisplayString($record->mechanic_assigned) }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ $record->date_started->format('M d, Y') }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                                    <div class="action-buttons flex justify-center space-x-1">
                                                        <button type="button" 
                                                                class="btn view-btn border inline-flex items-center justify-center"
                                                                title="View Maintenance"
                                                                onclick="showMaintenanceDetails({{ $record->id }})">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                        <button type="button" 
                                                                class="btn edit-btn border inline-flex items-center justify-center"
                                                                title="Edit Maintenance"
                                                                onclick="openEditMaintenanceModal({{ $record->id }})">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button type="button" 
                                                                class="btn done-btn border inline-flex items-center justify-center"
                                                                title="Complete Maintenance"
                                                                onclick="confirmComplete('{{ route('vehicle-maintenance.complete', $record) }}', '{{ $record->date_started->format('Y-m-d') }}')">
                                                            <i class="fas fa-check"></i> Done
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        @else
                                            <!-- Empty state row -->
                                            <tr>
                                                <td colspan="6" class="px-3 py-12 text-center">
                                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                                    </svg>
                                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No maintenance in progress</h3>
                                                    <p class="mt-1 text-sm text-gray-500">There are no ongoing maintenance records for this vehicle.</p>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination - Always shown -->
                            <div class="flex justify-between items-center mt-4">
                                <div class="text-sm text-gray-600">
                                    @if($ongoingRecords->count() > 0)
                                        Showing 1 to {{ $ongoingRecords->count() }} of {{ $ongoingRecords->count() }} maintenance records
                                    @else
                                        Showing 0 to 0 of 0 maintenance records
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2">
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
                                </div>
                            </div>
                        </div> <!-- End of Under Maintenance tab -->
                        
                        <div class="tab-pane fade {{ ($activeTab ?? 'pending') === 'completed' ? 'show active' : '' }}" id="completed" role="tabpanel">
                            @php
                                $completedRecords = $maintenanceRecords->filter(function($record) {
                                    return $record->status === 'Completed' && !$record->archived;
                                });
                            @endphp
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead style="background-color: #1e6031; color: white;">
                                        <tr>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">No.</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Nature of Work</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Maintenance Type</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Mechanics Assigned</th>
                                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Date Started</th>
                                            <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($completedRecords->count() > 0)
                                            @foreach($completedRecords as $index => $record)
                                            <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#004d00] font-medium">{{ $loop->iteration }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ $record->nature_of_work }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ $record->make_or_type }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ toDisplayString($record->mechanic_assigned) }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-[#006400]">{{ $record->date_started->format('M d, Y') }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                                    <div class="action-buttons flex justify-center space-x-1">
                                                        <button type="button" 
                                                                class="btn view-btn border inline-flex items-center justify-center"
                                                                title="View Maintenance"
                                                                onclick="showMaintenanceDetails({{ $record->id }})">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                        <button type="button" 
                                                                class="btn archive-btn border inline-flex items-center justify-center"
                                                                title="Archive Maintenance"
                                                                onclick="confirmArchive('{{ route('vehicle-maintenance.destroy', $record) }}')">
                                                            <i class="fas fa-archive"></i> Archive
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        @else
                                            <!-- Empty state row -->
                                            <tr>
                                                <td colspan="6" class="px-3 py-12 text-center">
                                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No completed maintenance records</h3>
                                                    <p class="mt-1 text-sm text-gray-500">There are no completed maintenance records for this vehicle.</p>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination - Always shown -->
                            <div class="flex justify-between items-center mt-4">
                                <div class="text-sm text-gray-600">
                                    @if($completedRecords->count() > 0)
                                        Showing 1 to {{ $completedRecords->count() }} of {{ $completedRecords->count() }} maintenance records
                                    @else
                                        Showing 0 to 0 of 0 maintenance records
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2">
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
                                </div>
                            </div>
                        </div> <!-- End of Completed tab -->
                    </div> <!-- End of tab content -->
                </div>
            </div>
    
    <script>
  document.addEventListener('DOMContentLoaded', function() {
        // Confirm delete function
        window.confirmDelete = function(url) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Are you sure you want to delete this maintenance record? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if(result.isConfirmed) {
                    // Create and submit form
                    const form = document.createElement('form');
                    form.action = url;
                    form.method = 'POST';
                    form.innerHTML = `
                        @csrf
                        @method('DELETE')
                    `;
                  document.body.appendChild(form);
                    form.submit();
                }
            });
        };
  
        // Confirm start function
        window.confirmStart = function(url) {
            Swal.fire({
                title: 'Start Maintenance?',
                text: 'Are you sure you want to start this maintenance record?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1e6031',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, start it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if(result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Starting...',
                        html: 'Please wait while we start the maintenance.',
                        timer: 2000,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit form via fetch to handle response
                    setTimeout(() => {
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                            },
                            body: JSON.stringify({})
                        })
                        .then(response => {
                            if (response.ok) {
                                // Show success message
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Maintenance started successfully.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    allowOutsideClick: false,
                                    allowEscapeKey: false
                                }).then(() => {
                                    // Refresh page and go to Ongoing (Under Maintenance) tab
                                    window.location.href = window.location.pathname + '?tab=ongoing';
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to start maintenance.',
                                    icon: 'error'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while starting maintenance.',
                                icon: 'error'
                            });
                        });
                    }, 2000);
                }
            });
        };
        
        // Helper function to format date for display (MM/DD/YYYY)
        function formatDateForDisplay(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                return parts[1] + '/' + parts[2] + '/' + parts[0]; // MM/DD/YYYY
            }
            return dateStr;
        }
        
        // Confirm complete function
        window.confirmComplete = function(url, dateStarted) {
            // Get today's date in YYYY-MM-DD format
            const today = new Date();
            const todayStr = today.getFullYear() + '-' + 
                            String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(today.getDate()).padStart(2, '0');
            
            Swal.fire({
                title: 'Complete Maintenance?',
                html: '<p style="margin-bottom:15px;">Are you sure you want to complete this maintenance record?</p>' +
                      '<div style="display:flex; align-items:center; margin-bottom:10px;">' +
                      '<label for="complete_date_completed" style="width:140px; font-size:0.875rem; line-height:1.25rem; font-weight:500; color:#1e6031;">Date Completed <span style="color:#ef4444;">*</span></label>' +
                      '<div style="flex:1;">' +
                      '<input type="date" id="complete_date_completed" name="date_completed" ' +
                      'min="' + dateStarted + '" ' +
                      'max="' + todayStr + '" ' +
                      'placeholder="Select completion date" ' +
                      'style="width:100%; padding:0.5rem 0.75rem; border:1px solid #d1d5db; background-color:#ffffff; border-radius:0.375rem; box-shadow:0 1px 2px rgba(0,0,0,0.05); font-size:0.875rem; line-height:1.25rem; color:#1f2937;" ' +
                      'oninput="document.getElementById(\'date-error\').style.display=\'none\'">' +
                      '<div id="date-error" style="color:#ef4444; font-size:0.75rem; margin-top:0.25rem; display:none;"></div>' +
                      '</div>' +
                      '</div>' +
                      '<div style="display:flex; align-items:center; margin-bottom:10px;">' +
                      '<label for="complete_conforme" style="width:140px; font-size:0.875rem; line-height:1.25rem; font-weight:500; color:#1e6031;">Conforme</label>' +
                      '<div style="flex:1;">' +
                      '<input type="text" id="complete_conforme" name="conforme" placeholder="Enter conforme" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #d1d5db; background-color:#ffffff; border-radius:0.375rem; box-shadow:0 1px 2px rgba(0,0,0,0.05); font-size:0.875rem; line-height:1.25rem; color:#1f2937;">' +
                      '<div id="conforme-error" style="color:#ef4444; font-size:0.75rem; margin-top:0.25rem; display:none;"></div>' +
                      '</div>' +
                      '</div>' +
                      '<div style="display:flex; align-items:center; margin-bottom:10px;">' +
                      '<label for="complete_position" style="width:140px; font-size:0.875rem; line-height:1.25rem; font-weight:500; color:#1e6031;">Position</label>' +
                      '<div style="flex:1;">' +
                      '<input type="text" id="complete_position" name="position" placeholder="Enter position" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #d1d5db; background-color:#ffffff; border-radius:0.375rem; box-shadow:0 1px 2px rgba(0,0,0,0.05); font-size:0.875rem; line-height:1.25rem; color:#1f2937;">' +
                      '<div id="position-error" style="color:#ef4444; font-size:0.75rem; margin-top:0.25rem; display:none;"></div>' +
                      '</div>' +
                      '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1e6031',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, complete it!',
                cancelButtonText: 'Cancel',
                showConfirmButton: true,
                focusConfirm: false,
                customClass: {
                    confirmButton: 'swal2-confirm',
                    cancelButton: 'swal2-cancel'
                },
                didOpen: () => {
                    // Clear any previous error messages when modal opens
                    const dateErrorDiv = document.getElementById('date-error');
                    if (dateErrorDiv) {
                        dateErrorDiv.style.display = 'none';
                        dateErrorDiv.textContent = '';
                    }
                    
                    // Set min and max attributes on the date input to restrict calendar selection
                    const dateInput = document.getElementById('complete_date_completed');
                    if (dateInput) {
                        dateInput.setAttribute('min', dateStarted);
                        dateInput.setAttribute('max', todayStr);
                        // Auto-fill with today's date
                        dateInput.value = todayStr;
                    }
                },
                preConfirm: () => {
                 const dateInput = document.getElementById('complete_date_completed');
                 let dateCompleted = dateInput.value;
                 
                 console.log('RAW INPUT VALUE:', JSON.stringify(dateCompleted));
                 
                 // Handle different date formats
                 if (dateCompleted && dateCompleted.includes('/')) {
                     // Browser returned DD/MM/YYYY or MM/DD/YYYY format
                     const parts = dateCompleted.split('/');
                     if (parts.length === 3) {
                         // Try to detect format: if first part > 12, it's definitely DD/MM/YYYY
                         const first = parseInt(parts[0]);
                         const second = parseInt(parts[1]);
                         
                         if (first > 12) {
                             // Definitely DD/MM/YYYY
                             dateCompleted = parts[2] + '-' + parts[1].padStart(2, '0') + '-' + parts[0].padStart(2, '0');
                         } else if (second > 12) {
                             // Definitely MM/DD/YYYY
                             dateCompleted = parts[2] + '-' + parts[0].padStart(2, '0') + '-' + parts[1].padStart(2, '0');
                         } else {
                             // Ambiguous - assume DD/MM/YYYY based on user's locale
                             dateCompleted = parts[2] + '-' + parts[1].padStart(2, '0') + '-' + parts[0].padStart(2, '0');
                         }
                         console.log('CONVERTED from slash format to:', dateCompleted);
                     }
                 }
                 
                 console.log('FINAL VALUES:');
                 console.log('  dateCompleted:', dateCompleted);
                 console.log('  dateStarted:', dateStarted);
                 console.log('  Comparison:', dateCompleted, '>=', dateStarted, '=', (dateCompleted >= dateStarted));
                 
                 const conforme = document.getElementById('complete_conforme').value;
                 const position = document.getElementById('complete_position').value;
                 const dateErrorDiv = document.getElementById('date-error');
                 const conformeErrorDiv = document.getElementById('conforme-error');
                 const positionErrorDiv = document.getElementById('position-error');
                    
                    // Hide errors initially
                    if (dateErrorDiv) dateErrorDiv.style.display = 'none';
                    if (conformeErrorDiv) conformeErrorDiv.style.display = 'none';
                    if (positionErrorDiv) positionErrorDiv.style.display = 'none';
                    
                    let hasError = false;
                    
                    // Validate date field
                    if (!dateCompleted || dateCompleted.trim() === '') {
                        if (dateErrorDiv) {
                            dateErrorDiv.textContent = 'Please select a date';
                            dateErrorDiv.style.display = 'block';
                        }
                        hasError = true;
                    } else {
                        // Validate date range: must be between date_started and today
                        // The date input returns value in YYYY-MM-DD format regardless of display locale
                        const selectedDateStr = dateCompleted; // Should be "2026-03-16"
                        
                        console.log('Date Validation Debug:', {
                            rawValue: dateCompleted,
                            selectedDateStr: selectedDateStr,
                            startDate: dateStarted,
                            today: todayStr,
                            isValid: selectedDateStr >= dateStarted && selectedDateStr <= todayStr
                        });
                        
                        // String comparison works for YYYY-MM-DD format
                        // Date must be >= dateStarted AND <= today
                        if (selectedDateStr < dateStarted) {
                            console.log('FAIL: ' + selectedDateStr + ' is before ' + dateStarted);
                            if (dateErrorDiv) {
                                dateErrorDiv.textContent = 'Cannot be before ' + formatDateForDisplay(dateStarted);
                                dateErrorDiv.style.display = 'block';
                            }
                            hasError = true;
                        } else if (selectedDateStr > todayStr) {
                            console.log('FAIL: ' + selectedDateStr + ' > ' + todayStr);
                            if (dateErrorDiv) {
                                dateErrorDiv.textContent = 'Cannot be in the future';
                                dateErrorDiv.style.display = 'block';
                            }
                            hasError = true;
                        } else {
                            console.log('PASS: Date is valid (' + selectedDateStr + ')');
                        }
                    }
                    
                    // Validate conforme field (optional)
                    if (conforme && conforme.trim() === '') {
                        if (conformeErrorDiv) {
                            conformeErrorDiv.textContent = 'Cannot be empty';
                            conformeErrorDiv.style.display = 'block';
                        }
                        hasError = true;
                    }
                    
                    // Validate position field (optional)
                    
                    if (hasError) {
                        return false;
                    }
                    
                    // Validate that date_completed is not before date_started
                    const startDate = new Date(dateStarted);
                    const completedDate = new Date(dateCompleted);
                    
                    if (completedDate < startDate) {
                        // Show inline error
                        if (dateErrorDiv) {
                            dateErrorDiv.textContent = 'Cannot be before ' + formatDateForDisplay(dateStarted);
                            dateErrorDiv.style.display = 'block';
                        }
                        return false;
                    }
                    
                    return { 
                        date_completed: dateCompleted, 
                        conforme: conforme,
                        position: position
                    };
                },
                didOpen: () => {
                    // Clear any previous errors when modal opens
                    const dateErrorDiv = document.getElementById('date-error');
                    const conformeErrorDiv = document.getElementById('conforme-error');
                    if (dateErrorDiv) dateErrorDiv.style.display = 'none';
                    if (conformeErrorDiv) conformeErrorDiv.style.display = 'none';
                }
            }).then((result) => {
                if(result.isConfirmed) {
                 const formData = result.value;
                    // Show loading
                    Swal.fire({
                        title: 'Completing...',
                        html: 'Please wait while we complete the maintenance.',
                        timer: 2000,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit form via fetch to handle response
                    setTimeout(() => {
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                            },
                            body: JSON.stringify(formData)
                        })
                        .then(response => {
                            if (response.ok) {
                                // Show success message
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Maintenance completed successfully.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    allowOutsideClick: false,
                                    allowEscapeKey: false
                                }).then(() => {
                                    // Refresh page and go to Completed tab
                                    window.location.href = window.location.pathname + '?tab=completed';
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to complete maintenance.',
                                    icon: 'error'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while completing maintenance.',
                                icon: 'error'
                            });
                        });
                    }, 2000);
                }
            });
        };
        
        // Confirm archive function
        window.confirmArchive = function(url) {
            Swal.fire({
                title: 'Archive Maintenance?',
                text: 'Are you sure you want to archive this maintenance record?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1e6031',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, archive it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if(result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Archiving...',
                        html: 'Please wait while we archive the maintenance record.',
                        timer: 2000,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit DELETE request
                    setTimeout(() => {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        
                        const tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = '_token';
                        tokenInput.value = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                        
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'DELETE';
                        
                        form.appendChild(tokenInput);
                        form.appendChild(methodInput);
                        document.body.appendChild(form);
                        form.submit();
                    }, 2000);
                }
            });
        };

        // Show maintenance details in modal
        window.showMaintenanceDetails = function(maintenanceId) {
            // Show loading state
            const contentDiv = document.getElementById('maintenanceDetailsContent');
            contentDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl mr-2"></i> Loading maintenance details...</div>';
            
            // Show the modal
            const modal = document.getElementById('maintenanceDetailsModal');
            const modalContent = document.getElementById('maintenanceModalContent');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
            
            // Fetch maintenance details via AJAX
            fetch(`/api/vehicle-maintenance/${maintenanceId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.data) {
                        const maintenance = data.data;
                        
                        // Format materials/parts list
                        let materialsHtml = '';
                        if (maintenance.materials_parts && Array.isArray(maintenance.materials_parts) && maintenance.materials_parts.length > 0) {
                            materialsHtml = '<ul class="list-disc list-inside space-y-1">';
                            maintenance.materials_parts.forEach(material => {
                                if (material && material.trim() !== '') {
                                    materialsHtml += `<li class="text-gray-900">${material}</li>`;
                                }
                            });
                            materialsHtml += '</ul>';
                        } else {
                            materialsHtml = '<span class="text-gray-500">N/A</span>';
                        }
                        
                        // Format mechanics list
                        let mechanicsHtml = '';
                        if (maintenance.mechanic_assigned && Array.isArray(maintenance.mechanic_assigned) && maintenance.mechanic_assigned.length > 0) {
                            mechanicsHtml = '<ul class="list-disc list-inside space-y-1">';
                            maintenance.mechanic_assigned.forEach(mechanic => {
                                if(mechanic && mechanic.trim() !== '') {
                                    mechanicsHtml += `<li class="text-gray-900">${mechanic}</li>`;
                                }
                            });
                            mechanicsHtml += '</ul>';
                        } else {
                            mechanicsHtml = '<span class="text-gray-500">N/A</span>';
                        }
                        
                        // Format dates
                        const dateStarted = maintenance.date_started ? new Date(maintenance.date_started).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                        const dateCompleted = maintenance.date_completed ? new Date(maintenance.date_completed).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                        
                        // Format status badge
                        let statusClass = 'bg-gray-100 text-gray-800';
                        if (maintenance.status === 'Pending') statusClass = 'bg-yellow-100 text-yellow-800';
                        else if (maintenance.status === 'Need Maintenance') statusClass = 'bg-red-100 text-red-800';
                        else if (maintenance.status === 'Under Maintenance') statusClass = 'bg-orange-100 text-orange-800';
                        else if(maintenance.status === 'Ongoing') statusClass = 'bg-blue-100 text-blue-800';
                        else if(maintenance.status === 'Completed') statusClass = 'bg-green-100 text-green-800';
                        
                        // Build the HTML - Simplified view (same design as trip ticket modal)
                        contentDiv.innerHTML = `
                            <div class="mb-4">
                                <h4 class="font-bold text-gray-900 border-b pb-1.5 mb-2 text-base">Vehicle</h4>
                                <table class="w-full">
                                    <tbody>
                                        <tr>
                                            <td class="font-semibold text-gray-700 text-sm align-top w-1/3">Date Started:</td>
                                            <td class="text-gray-900 text-sm align-top">${dateStarted}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-semibold text-gray-700 text-sm align-top">Make or Type:</td>
                                            <td class="text-gray-900 text-sm align-top">${maintenance.make_or_type || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-semibold text-gray-700 text-sm align-top">Place:</td>
                                            <td class="text-gray-900 text-sm align-top">${maintenance.place || 'N/A'}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mb-3">
                                <h4 class="font-bold text-gray-900 border-b pb-1.5 mb-2 text-base">Work Details</h4>
                                <table class="w-full">
                                    <tbody>
                                        <tr>
                                            <td class="font-semibold text-gray-700 text-sm align-top w-1/3">Person/Office/Unit:</td>
                                            <td class="text-gray-900 text-sm align-top">${maintenance.person_office_unit || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-semibold text-gray-700 text-sm align-top">Nature of Work:</td>
                                            <td class="text-gray-900 text-sm align-top whitespace-pre-wrap">${maintenance.nature_of_work || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-semibold text-gray-700 text-sm align-top">Materials/Parts:</td>
                                            <td class="text-gray-900 text-sm align-top">${materialsHtml}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-semibold text-gray-700 text-sm align-top">Mechanics Assigned:</td>
                                            <td class="text-gray-900 text-sm align-top">${mechanicsHtml}</td>
                                        </tr>
                                        ${maintenance.status === 'Completed' ? `
                                        <tr>
                                            <td class="font-semibold text-gray-700 text-sm align-top">Date Completed:</td>
                                            <td class="text-gray-900 text-sm align-top">${dateCompleted}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-semibold text-gray-700 text-sm align-top">Conforme:</td>
                                            <td class="text-gray-900 text-sm align-top">${maintenance.conforme || '-'}</td>
                                        </tr>
                                        ` : ''}
                                    </tbody>
                                </table>
                            </div>
                        `;
                                                
                        // Show view Excel button and set click handler
                        const viewPdfBtn = document.getElementById('viewPdfBtn');
                                                
                        if (maintenance.status === 'Completed') {
                            viewPdfBtn.classList.remove('hidden');
                                                    
                            // Set up view Excel button click handler
                            viewPdfBtn.onclick = function() {
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
                                        window.open(`/vehicle-maintenance/${maintenance.id}/excel`, '_blank');
                                    }
                                });
                            };
                        } else {
                            viewPdfBtn.classList.add('hidden');
                        }
                    } else {
                        contentDiv.innerHTML = '<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-triangle text-2xl mr-2"></i> Failed to load maintenance details.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading maintenance details:', error);
                    contentDiv.innerHTML = '<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-triangle text-2xl mr-2"></i> Error loading maintenance details.</div>';
                });
        };

        // Close maintenance modal
        window.closeMaintenanceModal = function() {
            const modal = document.getElementById('maintenanceDetailsModal');
            const modalContent = document.getElementById('maintenanceModalContent');
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        };
        
        // Close modal when clicking outside
        const modal = document.getElementById('maintenanceDetailsModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeMaintenanceModal();
                }
            });
        }
        
        // Open edit maintenance modal
        window.openEditMaintenanceModal = function(maintenanceId) {
            // Fetch maintenance data
            fetch(`/api/vehicle-maintenance/${maintenanceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const maintenance = data.data;
                        
                        console.log('API Response - date_started:', maintenance.date_started);
                        console.log('API Response - full data:', maintenance);
                        
                        // Populate form fields
                       document.getElementById('edit_maintenance_id').value = maintenance.id;
                       document.getElementById('edit_vehicle_id').value = maintenance.vehicle_id;
                       document.getElementById('edit_status').value = maintenance.status || 'Pending';
                       document.getElementById('edit_conforme').value = maintenance.conforme || 'Conforme';
                       
                       // Properly format date for input field (YYYY-MM-DD)
                       let dateStarted = maintenance.date_started;
                       if (dateStarted.includes('T')) {
                           // ISO format: 2026-03-15T00:00:00.000000Z
                           dateStarted = dateStarted.split('T')[0];
                       } else if (dateStarted.includes('-')) {
                           // Already in correct format: 2026-03-15
                           // Keep as is
                       } else if (dateStarted.includes('/')) {
                           // Format: 15/03/2026 - convert to YYYY-MM-DD
                           const parts = dateStarted.split('/');
                           if (parts.length === 3) {
                               dateStarted = parts[2] + '-' + parts[1] + '-' + parts[0];
                           }
                       }
                       document.getElementById('edit_date_started').value = dateStarted;
                       
                       document.getElementById('edit_make_or_type').value = maintenance.make_or_type || '';
                       document.getElementById('edit_person_office_unit').value = maintenance.person_office_unit || '';
                       document.getElementById('edit_place').value = maintenance.place || '';
                       document.getElementById('edit_nature_of_work').value = maintenance.nature_of_work || '';
                        
                        // Set form action
                       document.getElementById('editMaintenanceForm').action = `/vehicle-maintenance/${maintenanceId}`;
                        
                        // Populate mechanics
                        const mechanicsContainer = document.getElementById('mechanics-container-edit');
                        mechanicsContainer.innerHTML = '';
                        const mechanics = Array.isArray(maintenance.mechanic_assigned) 
                            ? maintenance.mechanic_assigned 
                            : JSON.parse(maintenance.mechanic_assigned || '[]');
                        
                        if (mechanics && mechanics.length > 0) {
                            mechanics.forEach((mechanic, index) => {
                                const mechanicItem = document.createElement('div');
                                mechanicItem.className = 'mechanic-item mb-2 d-flex gap-2';
                                mechanicItem.innerHTML = `
                                    <input type="text" name="mechanic_assigned[]" required
                                           class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.70rem; height: 28px;" 
                                           placeholder="Mechanic Name" value="${mechanic}">
                                    <button type="button" class="btn btn-sm btn-danger remove-mechanic-edit py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                `;
                                mechanicsContainer.appendChild(mechanicItem);
                            });
                        } else {
                            mechanicsContainer.innerHTML = `
                                <div class="mechanic-item mb-2 d-flex gap-2">
                                    <input type="text" name="mechanic_assigned[]" required
                                           class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.70rem; height: 28px;" 
                                           placeholder="Mechanic Name">
                                    <button type="button" class="btn btn-sm btn-danger remove-mechanic-edit py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            `;
                        }
                        
                        // Populate materials/parts
                        const materialsContainer = document.getElementById('materials-container-edit');
                        materialsContainer.innerHTML = '';
                        const materials = Array.isArray(maintenance.materials_parts) 
                            ? maintenance.materials_parts 
                            : JSON.parse(maintenance.materials_parts || '[]');
                        
                        if (materials && materials.length > 0) {
                            materials.forEach((material, index) => {
                                const materialItem = document.createElement('div');
                                materialItem.className = 'material-item mb-2 d-flex gap-2';
                                materialItem.innerHTML = `
                                    <input type="text" name="materials_parts[]"
                                           class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.70rem; height: 28px;" 
                                           placeholder="Material/Part" value="${material}">
                                    <button type="button" class="btn btn-sm btn-danger remove-material-edit py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                `;
                                materialsContainer.appendChild(materialItem);
                            });
                        } else {
                            materialsContainer.innerHTML = `
                                <div class="material-item mb-2 d-flex gap-2">
                                    <input type="text" name="materials_parts[]"
                                           class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.70rem; height: 28px;" 
                                           placeholder="Material/Part">
                                    <button type="button" class="btn btn-sm btn-danger remove-material-edit py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            `;
                        }
                        
                        // Show edit modal using Bootstrap
                        const editModal = new bootstrap.Modal(document.getElementById('editMaintenanceModal'), {
                            backdrop: true,
                            keyboard: true
                        });
                        editModal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load maintenance data.'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching maintenance data:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while loading maintenance data.'
                    });
                });
        };
              
        // Handle Cancel button click - do NOT reset, just close modal
    document.getElementById('cancelEditBtn').addEventListener('click', function() {
        const editModalInstance = bootstrap.Modal.getInstance(document.getElementById('editMaintenanceModal'));
            if (editModalInstance) {
                editModalInstance.hide();
            }
        });
        
        // Auto-update status based on Date Started field in edit modal
        const editDateStartedInput = document.getElementById('edit_date_started');
        const editStatusInput = document.getElementById('edit_status');
        
        if (editDateStartedInput && editStatusInput) {
            editDateStartedInput.addEventListener('change', function() {
                const selectedDateStr = this.value; // e.g., "2025-03-16"
                const today = new Date();
                const todayStr = today.getFullYear() + '-' + 
                                String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                                String(today.getDate()).padStart(2, '0');
                
                // Compare date strings directly (YYYY-MM-DD format)
                if (selectedDateStr <= todayStr) {
                    editStatusInput.value = 'Need Maintenance'; // Past or today
                } else {
                    editStatusInput.value = 'Pending'; // Future
                }
                
                console.log('Selected date:', selectedDateStr, '| Today:', todayStr, '| Status:', editStatusInput.value);
            });
        }
        
        // Modal stays as-is when clicking outside - preserves edits
        // Next time Edit button is clicked, it will reload fresh data from database
              
        // Handle delete buttons
       document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const form = this.closest('.delete-form');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Are you sure you want to delete this maintenance record? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
            
        // Tab switching functionality
        const tabButtons = document.querySelectorAll('.tab-button');
            
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'border-[#1e6031]', 'text-[#1e6031]');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });
                    
                // Add active class to clicked button
                this.classList.remove('border-transparent', 'text-gray-500');
                this.classList.add('active', 'border-[#1e6031]', 'text-[#1e6031]');
                    
                // Hide all tab content
               document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                    
                // Show corresponding tab content
                const targetId = this.getAttribute('data-bs-target').substring(1);
               document.getElementById(targetId).classList.add('show', 'active');
            });
        });
            
        // Search functionality for maintenance records
        const searchInput = document.getElementById('searchMaintenance');
    
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
                
            // Get the active tab's records
            const activeTab = document.querySelector('.tab-pane.active.show');
            if (activeTab) {
                const records = activeTab.querySelectorAll('tr[data-record-id]');
                    
                records.forEach(record => {
                    const recordData = [
                        record.getAttribute('data-nature-of-work'),
                        record.getAttribute('data-make-or-type'),
                        record.getAttribute('data-mechanic-assigned'),
                        record.getAttribute('data-date-started')
                    ].join(' ').toLowerCase();
                        
                    if (recordData.includes(query)) {
                        record.style.display = '';
                    } else {
                        record.style.display = 'none';
                    }
                });
            }
        });
        
        // Maintenance Record Modal Functionality using Bootstrap
        const addMaintenanceBtn = document.getElementById('addMaintenanceRecordBtn');
        let maintenanceModalInstance = null;
        let shouldResetForm = false; // Track if form should be reset
            
        if (addMaintenanceBtn) {
            addMaintenanceBtn.addEventListener('click', function() {
                // Initialize Bootstrap modal if not already done
                if (!maintenanceModalInstance) {
                    maintenanceModalInstance = new bootstrap.Modal(document.getElementById('addMaintenanceModal'), {
                        backdrop: true,
                        keyboard: true
                    });
                }
                    
                // Only reset form on FIRST open or after Cancel was clicked
                if (shouldResetForm) {
                  document.getElementById('addMaintenanceForm').reset();
                    // Reset mechanics and materials containers to single item
                    const mechanicsContainer = document.getElementById('mechanics-container-modal');
                    const materialsContainer = document.getElementById('materials-container-modal');
                        
                    mechanicsContainer.innerHTML = `
                        <div class="mechanic-item mb-2 d-flex gap-2">
                            <input type="text" name="mechanic_assigned[]" required
                                   class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.75rem; height: 30px;" 
                                   placeholder="Mechanic Name">
                            <button type="button" class="btn btn-sm btn-danger remove-mechanic py-0 px-2" style="font-size: 0.70rem; height: 26px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                        
                    materialsContainer.innerHTML = `
                        <div class="material-item mb-2 d-flex gap-2">
                            <input type="text" name="materials_parts[]"
                                   class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.75rem; height: 30px;" 
                                   placeholder="Material/Part">
                            <button type="button" class="btn btn-sm btn-danger remove-material py-0 px-2" style="font-size: 0.70rem; height: 26px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                        
                    shouldResetForm = false; // Reset flag for next time
                }
                    
                maintenanceModalInstance.show();
            });
        }
            
        // Add mechanic button in modal
        const addMechanicModalBtn = document.getElementById('add-mechanic-modal');
        if (addMechanicModalBtn) {
            addMechanicModalBtn.addEventListener('click', function() {
                const container = document.getElementById('mechanics-container-modal');
                const mechanicItem = document.createElement('div');
                mechanicItem.className = 'mechanic-item mb-2 d-flex gap-2';
                mechanicItem.innerHTML = `
                    <input type="text" name="mechanic_assigned[]" required
                           class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.75rem; height: 30px;" 
                           placeholder="Mechanic Name">
                    <button type="button" class="btn btn-sm btn-danger remove-mechanic py-0 px-2" style="font-size: 0.70rem; height: 26px;">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                container.appendChild(mechanicItem);
            });
        }
            
        // Add material button in modal
        const addMaterialModalBtn = document.getElementById('add-material-modal');
        if (addMaterialModalBtn) {
            addMaterialModalBtn.addEventListener('click', function() {
                const container = document.getElementById('materials-container-modal');
                const materialItem = document.createElement('div');
                materialItem.className = 'material-item mb-2 d-flex gap-2';
                materialItem.innerHTML = `
                    <input type="text" name="materials_parts[]"
                           class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.75rem; height: 30px;" 
                           placeholder="Material/Part">
                        <button type="button" class="btn btn-sm btn-danger remove-material py-0 px-2" style="font-size: 0.70rem; height: 26px;">
                            <i class="fas fa-trash"></i>
                        </button>
                `;
                container.appendChild(materialItem);
            });
        }
            
        // Remove mechanic/material buttons using event delegation
       document.getElementById('addMaintenanceModal').addEventListener('click', function(e) {
            if (e.target && (e.target.classList.contains('remove-mechanic') || e.target.closest('.remove-mechanic'))) {
                const button = e.target.classList.contains('remove-mechanic') ? e.target : e.target.closest('.remove-mechanic');
                const container = document.getElementById('mechanics-container-modal');
                if (container && container.querySelectorAll('.mechanic-item').length > 1) {
                    button.closest('.mechanic-item').remove();
                }
            }
                
            if (e.target && (e.target.classList.contains('remove-material') || e.target.closest('.remove-material'))) {
                const button = e.target.classList.contains('remove-material') ? e.target : e.target.closest('.remove-material');
                const container = document.getElementById('materials-container-modal');
                if (container && container.querySelectorAll('.material-item').length > 1) {
                    button.closest('.material-item').remove();
                }
            }
        });
            
        // Handle Cancel button click - reset form
       document.getElementById('cancelModalBtn').addEventListener('click', function() {
            shouldResetForm = true;
            maintenanceModalInstance.hide();
        });
            
        // Handle modal close - only reset if Cancel was clicked
       document.getElementById('addMaintenanceModal').addEventListener('hidden.bs.modal', function() {
            if (shouldResetForm) {
               document.getElementById('addMaintenanceForm').reset();
                // Reset mechanics and materials containers to single item
                const mechanicsContainer = document.getElementById('mechanics-container-modal');
                const materialsContainer = document.getElementById('materials-container-modal');
                    
                mechanicsContainer.innerHTML = `
                    <div class="mechanic-item mb-2 d-flex gap-2">
                        <input type="text" name="mechanic_assigned[]" required
                               class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.75rem; height: 30px;" 
                               placeholder="Mechanic Name">
                        <button type="button" class="btn btn-sm btn-danger remove-mechanic py-0 px-2" style="font-size: 0.70rem; height: 26px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                    
                materialsContainer.innerHTML = `
                    <div class="material-item mb-2 d-flex gap-2">
                        <input type="text" name="materials_parts[]"
                               class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.75rem; height: 30px;" 
                               placeholder="Material/Part">
                        <button type="button" class="btn btn-sm btn-danger remove-material py-0 px-2" style="font-size: 0.70rem; height: 26px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                    
                shouldResetForm = false; // Reset flag
            }
        });
            
        // Auto-update status based on Date Started field in add modal
        const addDateStartedInput = document.getElementById('modal_date_started');
        const addStatusHidden = document.querySelector('input[name="status"]');
        
        if (addDateStartedInput && addStatusHidden) {
            addDateStartedInput.addEventListener('change', function() {
                const selectedDateStr = this.value; // e.g., "2025-03-16"
                const today = new Date();
                const todayStr = today.getFullYear() + '-' + 
                                String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                                String(today.getDate()).padStart(2, '0');
                
                // Compare date strings directly (YYYY-MM-DD format)
                if (selectedDateStr <= todayStr) {
                    addStatusHidden.value = 'Need Maintenance'; // Past or today
                } else {
                    addStatusHidden.value = 'Pending'; // Future
                }
                
                console.log('Add Modal - Selected:', selectedDateStr, '| Today:', todayStr, '| Status:', addStatusHidden.value);
            });
        }
        
        // Handle form submission with SweetAlert2
    document.getElementById('addMaintenanceForm').addEventListener('submit', function(e) {
            e.preventDefault();
                
            const form = this;
            const submitBtn = document.getElementById('saveMaintenanceBtn');
            const formData = new FormData(form);
                
            // Disable submit button
            submitBtn.disabled = true;
                
            // Show loading SweetAlert
            Swal.fire({
                title: 'Saving...',
                html: 'Please wait while we save the maintenance record.',
                timer: 2000,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
                
            // Submit form via AJAX
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (response.ok) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Maintenance record saved successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        // Reload the page
                        window.location.reload();
                    });
                } else {
                    // Try to get error details
                    return response.json().then(errData => {
                        console.error('Server error:', errData);
                        throw new Error(errData.message || 'Failed to save');
                    }).catch(() => {
                        throw new Error('Server returned ' + response.status);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: error.message || 'An error occurred while saving the record.'
                });
                submitBtn.disabled = false;
            });
        });
            
        // Add mechanic button in edit modal
      const addMechanicEditBtn = document.getElementById('add-mechanic-edit');
        if (addMechanicEditBtn) {
            addMechanicEditBtn.addEventListener('click', function() {
              const container = document.getElementById('mechanics-container-edit');
              const mechanicItem = document.createElement('div');
                mechanicItem.className = 'mechanic-item mb-2 d-flex gap-2';
                mechanicItem.innerHTML = `
                    <input type="text" name="mechanic_assigned[]" required
                           class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.75rem; height: 30px;" 
                           placeholder="Mechanic Name">
                    <button type="button" class="btn btn-sm btn-danger remove-mechanic-edit py-0 px-2" style="font-size: 0.70rem; height: 26px;">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
              container.appendChild(mechanicItem);
            });
        }
            
        // Add material button in edit modal
      const addMaterialEditBtn = document.getElementById('add-material-edit');
        if (addMaterialEditBtn) {
            addMaterialEditBtn.addEventListener('click', function() {
              const container = document.getElementById('materials-container-edit');
              const materialItem = document.createElement('div');
                materialItem.className = 'material-item mb-2 d-flex gap-2';
                materialItem.innerHTML = `
                    <input type="text" name="materials_parts[]"
                           class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.75rem; height: 30px;" 
                           placeholder="Material/Part">
                    <button type="button" class="btn btn-sm btn-danger remove-material-edit py-0 px-2" style="font-size: 0.70rem; height: 26px;">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
              container.appendChild(materialItem);
            });
        }
            
        // Remove mechanic/material buttons in edit modal using event delegation
     document.getElementById('editMaintenanceModal').addEventListener('click', function(e) {
            if (e.target && (e.target.classList.contains('remove-mechanic-edit') || e.target.closest('.remove-mechanic-edit'))) {
              const button = e.target.classList.contains('remove-mechanic-edit') ? e.target : e.target.closest('.remove-mechanic-edit');
              const container = document.getElementById('mechanics-container-edit');
                if (container && container.querySelectorAll('.mechanic-item').length > 1) {
                    button.closest('.mechanic-item').remove();
                }
            }
                
            if (e.target && (e.target.classList.contains('remove-material-edit') || e.target.closest('.remove-material-edit'))) {
              const button = e.target.classList.contains('remove-material-edit') ? e.target : e.target.closest('.remove-material-edit');
              const container = document.getElementById('materials-container-edit');
                if (container && container.querySelectorAll('.material-item').length > 1) {
                    button.closest('.material-item').remove();
                }
            }
        });
            
        // Handle Cancel button click - reset form
     document.getElementById('cancelEditBtn').addEventListener('click', function() {
          const editModalInstance = bootstrap.Modal.getInstance(document.getElementById('editMaintenanceModal'));
            if (editModalInstance) {
                editModalInstance.hide();
            }
        });
            
        // Handle edit form submission with SweetAlert2
     document.getElementById('editMaintenanceForm').addEventListener('submit', function(e) {
            e.preventDefault();
                
          const form = this;
          const submitBtn = document.getElementById('updateMaintenanceBtn');
          const formData = new FormData(form);
          
          // Add _method for Laravel method spoofing
          formData.append('_method', 'PUT');
                
            // Disable submit button
            submitBtn.disabled = true;
                
            // Show loading SweetAlert
            Swal.fire({
                title: 'Updating...',
                html: 'Please wait while we update the maintenance record.',
                timer: 2000,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
                
            // Submit form via AJAX with delay
            setTimeout(() => {
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response OK:', response.ok);
                    
                    // Check if response is OK
                    if (response.ok) {
                        return response.json();
                    } else {
                        // Try to get error details
                        return response.json().then(errData => {
                            console.error('Server error:', errData);
                            // If it's a validation error (422), show the validation messages
                            if (response.status === 422 && errData.errors) {
                                const errorMessages = Object.values(errData.errors).flat().join('<br>');
                                throw new Error(errorMessages);
                            }
                            throw new Error(errData.message || 'Failed to update');
                        }).catch(parseError => {
                            // If response is not JSON (e.g., HTML error page)
                            console.error('Parse error:', parseError);
                            console.error('Response content type:', response.headers.get('content-type'));
                            
                            // Try to get text response for debugging
                            return response.text().then(text => {
                                console.error('Response text:', text.substring(0, 500));
                                throw new Error('Server returned ' + response.status + '. Please check the server logs.');
                            }).catch(() => {
                                throw new Error('Server returned ' + response.status + '. Unable to parse response.');
                            });
                        });
                    }
                })
            .then(data => {
                // Success - show success message
                console.log('Update successful:', data);
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Maintenance record updated successfully.',
                    timer: 2000,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    // Close modal and reload page
                    const editModalInstance = bootstrap.Modal.getInstance(document.getElementById('editMaintenanceModal'));
                    if (editModalInstance) {
                        editModalInstance.hide();
                    }
                    // Redirect to the vehicle maintenance page
                    if (data.data && data.data.redirect) {
                        window.location.href = data.data.redirect;
                    } else {
                        window.location.reload();
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: error.message || 'An error occurred while updating the record.'
                });
                submitBtn.disabled = false;
            });
            }, 2000); // Wait 2 seconds before submitting
        });
    });
    </script>
    
    <!-- Add Maintenance Record Modal -->
    <div class="modal fade" id="addMaintenanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header py-2" style="background-color:#1e6031; color:white;">
                    <h5 class="modal-title fw-bold mb-0 flex-grow-1" style="font-size: 0.9rem;">Add New Vehicle Maintenance</h5>
                </div>

                <!-- Form -->
                <form id="addMaintenanceForm" method="POST" action="{{ route('vehicle-maintenance.store') }}">
                    @csrf
                    <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                    <input type="hidden" name="status" value="Pending">
                    
                    <div class="modal-body px-3 py-2">
                        
                        <!-- Date Started, Make/Type Row -->
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.70rem;">Date Started <span class="text-danger">*</span></label>
                                <input type="date" name="date_started" id="modal_date_started" required
                                       class="form-control form-control-sm border-success py-1 px-2 rounded" style="font-size: 0.70rem; height: 28px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.70rem;">Make or Type <span class="text-danger">*</span></label>
                                <input type="text" name="make_or_type" id="modal_make_or_type" required
                                       class="form-control form-control-sm border-success py-1 px-2 rounded" style="font-size: 0.70rem; height: 28px;" 
                                       placeholder="e.g., Crosswind - 036806">
                            </div>
                        </div>

                        <!-- Person/Office/Unit Row -->
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.70rem;">Person/Office/Unit <span class="text-danger">*</span></label>
                                <input type="text" name="person_office_unit" id="modal_person_office_unit" required
                                       class="form-control form-control-sm border-success py-1 px-2 rounded" style="font-size: 0.70rem; height: 28px;"
                                       placeholder="e.g., Motor Pool Unit">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.70rem;">Place <span class="text-danger">*</span></label>
                                <input type="text" name="place" id="modal_place" required
                                       class="form-control form-control-sm border-success py-1 px-2 rounded" style="font-size: 0.70rem; height: 28px;"
                                       placeholder="e.g., Central Luzon State University, Motorpool Garage">
                            </div>
                        </div>

                        <!-- Mechanics Assigned Row -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold text-success mb-0" style="font-size: 0.70rem;">Mechanics Assigned <span class="text-danger">*</span></label>
                                    <button type="button" id="add-mechanic-modal" class="btn btn-sm btn-success py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                        <i class="fas fa-plus me-1"></i> Add
                                    </button>
                                </div>
                                <div id="mechanics-container-modal">
                                    <div class="mechanic-item mb-2 d-flex gap-2">
                                        <input type="text" name="mechanic_assigned[]" required
                                               class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.70rem; height: 28px;" 
                                               placeholder="Mechanic Name">
                                        <button type="button" class="btn btn-sm btn-danger remove-mechanic py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nature of Work Row -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.70rem;">Nature of Work <span class="text-danger">*</span></label>
                                <textarea name="nature_of_work" id="modal_nature_of_work" rows="2" required
                                          class="form-control form-control-sm border-success py-1 px-2 rounded" style="font-size: 0.70rem;"
                                          placeholder="Describe the work to be done, e.g., Replace flat tire, Check engine diagnostics, Oil change"></textarea>
                            </div>
                        </div>

                        <!-- Materials/Parts Row -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold text-success mb-0" style="font-size: 0.70rem;">Materials/Parts</label>
                                    <button type="button" id="add-material-modal" class="btn btn-sm btn-success py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                        <i class="fas fa-plus me-1"></i> Add
                                    </button>
                                </div>
                                <div id="materials-container-modal">
                                    <div class="material-item mb-2 d-flex gap-2">
                                        <input type="text" name="materials_parts[]"
                                               class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.70rem; height: 28px;" 
                                               placeholder="Material/Parts">
                                        <button type="button" class="btn btn-sm btn-danger remove-material py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div class="modal-footer py-1 justify-content-end">
                        <button type="button" id="cancelModalBtn" class="btn btn-sm btn-outline-secondary me-2 py-1" style="font-size: 0.70rem; height: 28px;" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" id="saveMaintenanceBtn" class="btn btn-sm btn-success py-1" style="font-size: 0.70rem; height: 28px;">
                            Save Maintenance Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Maintenance Record Modal -->
    <div class="modal fade" id="editMaintenanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header py-2" style="background-color:#1e6031; color:white;">
                    <h5 class="modal-title fw-bold mb-0 flex-grow-1" style="font-size: 0.9rem;">Edit Vehicle Maintenance</h5>
                </div>

                <!-- Form -->
                <form id="editMaintenanceForm" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="edit_maintenance_id">
                    <input type="hidden" name="vehicle_id" id="edit_vehicle_id">
                    <input type="hidden" name="status" id="edit_status">
                    
                    <div class="modal-body px-3 py-2">
                        
                        <!-- Date Started, Make/Type Row -->
                        <div class="row mb-1">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.70rem;">Date Started <span class="text-danger">*</span></label>
                                <input type="date" name="date_started" id="edit_date_started" required
                                       class="form-control form-control-sm border-success py-1 px-2 rounded" style="font-size: 0.70rem; height: 28px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.70rem;">Make or Type <span class="text-danger">*</span></label>
                                <input type="text" name="make_or_type" id="edit_make_or_type" required
                                       class="form-control form-control-sm border-success py-1 px-2 rounded" style="font-size: 0.70rem; height: 28px;">
                            </div>
                        </div>

                        <!-- Person/Office/Unit, Place Row -->
                        <div class="row mb-1">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.70rem;">Person/Office/Unit <span class="text-danger">*</span></label>
                                <input type="text" name="person_office_unit" id="edit_person_office_unit" required
                                       class="form-control form-control-sm border-success py-1 px-2 rounded" style="font-size: 0.70rem; height: 28px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.70rem;">Place <span class="text-danger">*</span></label>
                                <input type="text" name="place" id="edit_place" required
                                       class="form-control form-control-sm border-success py-1 px-2 rounded" style="font-size: 0.70rem; height: 28px;">
                            </div>
                        </div>

                        <!-- Mechanics Assigned Row -->
                        <div class="row mb-1">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold text-success mb-0" style="font-size: 0.70rem;">Mechanics Assigned <span class="text-danger">*</span></label>
                                    <button type="button" id="add-mechanic-edit" class="btn btn-sm btn-success py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                        <i class="fas fa-plus me-1"></i> Add
                                    </button>
                                </div>
                                <div id="mechanics-container-edit">
                                    <div class="mechanic-item mb-2 d-flex gap-2">
                                        <input type="text" name="mechanic_assigned[]" required
                                               class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.70rem; height: 28px;" 
                                               placeholder="Mechanic Name">
                                        <button type="button" class="btn btn-sm btn-danger remove-mechanic-edit py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nature of Work Row -->
                        <div class="row mb-1">
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-success mb-1" style="font-size: 0.70rem;">Nature of Work <span class="text-danger">*</span></label>
                                <textarea name="nature_of_work" id="edit_nature_of_work" rows="2" required
                                          class="form-control form-control-sm border-success py-1 px-2 rounded" style="font-size: 0.70rem;"></textarea>
                            </div>
                        </div>

                        <!-- Materials/Parts Row -->
                        <div class="row mb-1">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold text-success mb-0" style="font-size: 0.70rem;">Materials/Parts</label>
                                    <button type="button" id="add-material-edit" class="btn btn-sm btn-success py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                        <i class="fas fa-plus me-1"></i> Add
                                    </button>
                                </div>
                                <div id="materials-container-edit">
                                    <div class="material-item mb-2 d-flex gap-2">
                                        <input type="text" name="materials_parts[]"
                                               class="form-control form-control-sm border-success py-1 px-2 rounded flex-grow-1" style="font-size: 0.70rem; height: 28px;" 
                                               placeholder="Material/Part">
                                        <button type="button" class="btn btn-sm btn-danger remove-material-edit py-0 px-2" style="font-size: 0.65rem; height: 24px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Conforme Row (Hidden) -->
                        <input type="hidden" name="conforme" id="edit_conforme" value="Conforme">

                    </div>

                    <!-- Footer -->
                    <div class="modal-footer py-1 justify-content-end">
                        <button type="button" id="cancelEditBtn" class="btn btn-sm btn-outline-secondary me-2 py-1" style="font-size: 0.70rem; height: 28px;" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" id="updateMaintenanceBtn" class="btn btn-sm btn-success py-1" style="font-size: 0.70rem; height: 28px;">
                            Update Maintenance Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Maintenance Details Modal -->
    <div id="maintenanceDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 transition-all duration-200">
        <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0" id="maintenanceModalContent">
            
            <!-- Modal Header - DSU Themed -->
            <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-start" style="background: linear-gradient(135deg, #1e6031 0%, #2d7a3e 50%, #1e6031 100%);">
                <div class="flex items-center gap-3">
                    <!-- Icon Circle -->
                    <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                        <!-- Wrench Icon -->
                        <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">Maintenance Information</h3>
                </div>
            </div>

            <!-- Content -->
            <div class="px-6 py-4">
                <div id="maintenanceDetailsContent" class="text-sm text-gray-800">
                    <!-- Maintenance details will be loaded here -->
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2">
                <button id="viewPdfBtn" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5 hidden">
                    <i class="fas fa-file-excel mr-1"></i> View Excel
                </button>
                <button onclick="closeMaintenanceModal()" class="px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    Close
                </button>
            </div>
        </div>
    </div>
    
    <script>
    // Generate PDF for completed maintenance records
    document.getElementById('generateMaintenancePDF')?.addEventListener('click', function() {
        const vehicleId = '{{ $vehicle->id }}';
        const pdfUrl = `/vehicles/${vehicleId}/completed-maintenance-pdf`;
        
        // Set iframe src to the PDF URL
        document.getElementById('maintenancePdfIframe').src = pdfUrl;
        
        // Show the modal
        const modal = document.getElementById('maintenancePdfPreviewModal');
        modal.classList.remove('hidden');
        
        // Close modal when clicking outside (on the overlay)
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeMaintenancePdfModal();
            }
        });
    });
    
    // Close maintenance PDF modal
    window.closeMaintenancePdfModal = function() {
        document.getElementById('maintenancePdfPreviewModal').classList.add('hidden');
        // Clear iframe src to stop PDF loading
        document.getElementById('maintenancePdfIframe').src = '';
    };
    
    // Oil Change Notification - Floating Panel (Upper Right)
    document.addEventListener('DOMContentLoaded', function() {
        // Check if current vehicle needs oil change
        const vehicleData = @json($vehicle);
        const mileage = parseInt(vehicleData.mileage) || 0;
        const lastMaintenanceMileage = parseInt(vehicleData.last_maintenance_mileage) || 0;
        const kilometersSinceService = mileage - lastMaintenanceMileage;
        
        // Check if user has permanently dismissed this notification (from database)
        const isDismissed = {{ $vehicle->isOilChangeNotificationDismissed() ? 'true' : 'false' }};
        
        console.log('Checking oil change notification:', {
            mileage: mileage,
            last_maintenance_mileage: lastMaintenanceMileage,
            kilometersSinceService: kilometersSinceService,
            isDismissed: isDismissed,
            shouldShow: kilometersSinceService >= 5000 && !isDismissed
        });
        
        if (kilometersSinceService >= 5000 && !isDismissed) {
            showOilChangeFloatingNotification();
        }
    });
    
    function showOilChangeFloatingNotification() {
        console.log('SHOWING OIL CHANGE NOTIFICATION');
        
        const notificationHtml = `
            <div id="oilChangeFloatingNotification" class="fixed top-4 right-4 z-[9999] w-80 bg-white rounded-lg shadow-2xl border-2 border-orange-300" style="position: fixed !important; top: 1rem !important; right: 1rem !important; z-index: 9999 !important;">
                <div class="p-3">
                    <div class="mb-2 bg-orange-50 border border-orange-200 rounded p-2">
                        <p class="text-xs text-orange-800 font-semibold">
                        This vehicle needs an oil change
                        </p>
                    </div>
                    
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Current Mileage:</span>
                            <span class="font-semibold text-orange-700">{{ number_format($vehicle->mileage ?? 0) }} km</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Last Service:</span>
                            <span class="font-semibold text-orange-700">{{ number_format($vehicle->last_maintenance_mileage ?? 0) }} km</span>
                        </div>
                        <div class="flex justify-between text-xs font-bold">
                            <span class="text-gray-600">Since Last Service:</span>
                            <span class="text-red-700">{{ number_format($vehicle->getKilometersSinceLastMaintenance()) }} km</span>
                        </div>
                        <div class="pt-1 border-t border-orange-200 mt-1">
                            <p class="text-xs text-orange-700 font-semibold">
                                ⚠️ Exceeded 5000 km threshold
                            </p>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button onclick="dismissOilChangeNotification({{ $vehicle->id }}, 'ignore')" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-semibold py-1.5 px-2 rounded transition-colors">
                            <i class="fas fa-times-circle"></i> Ignore
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing notification if any
        const existing = document.getElementById('oilChangeFloatingNotification');
        if (existing) existing.remove();
        
        console.log('Inserting notification HTML into body');
        document.body.insertAdjacentHTML('beforeend', notificationHtml);
        
        // Verify it was added
        const newNotification = document.getElementById('oilChangeFloatingNotification');
        if (newNotification) {
            console.log('SUCCESS: Notification is now in the DOM', newNotification);
        } else {
            console.error('ERROR: Notification was not added to DOM');
        }
    }
    
    function closeOilChangeFloatingNotification() {
        const notification = document.getElementById('oilChangeFloatingNotification');
        if (notification) {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }
    }
    
    function dismissOilChangeNotification(vehicleId, action) {
        fetch('{{ route("motorpool.api.vehicles.dismiss-oil-change-notification") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ vehicle_id: vehicleId, action: action })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeOilChangeFloatingNotification();
                
                // Show toast notification
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                toast.textContent = '✓ Notification dismissed permanently';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        })
        .catch(error => console.error('Error dismissing notification:', error));
    }
    </script>
@endsection

<style>
/* Action Buttons Styles - matching trip-tickets design */
.action-buttons .btn {
    font-size: 11px;
    padding: 4px 8px;
    line-height: 1;
    height: 26px;
    min-width: 55px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    border-radius: 4px;
    margin: 0 2px;
    border: 1px solid transparent;
    transition: all 0.2s ease-in-out duration-150;
    cursor: pointer;
}

/* View button - Blue */
.action-buttons .view-btn {
    color: #0d6efd !important;
    border: 1px solid #0d6efd !important;
    background-color: transparent!important;
}

.action-buttons .view-btn:hover {
    background-color: #0d6efd !important;
    color: #fff !important;
    border-color: #0d6efd !important;
}

/* Edit button - Yellow/Orange */
.action-buttons .edit-btn {
    color: #ffc107 !important;
    border: 1px solid #ffc107 !important;
    background-color: transparent!important;
}

.action-buttons .edit-btn:hover {
    background-color: #ffc107 !important;
    color: #000 !important;
    border-color: #ffc107 !important;
}

/* Start button- Green */
.action-buttons .start-btn {
    color: #198754 !important;
    border: 1px solid #198754 !important;
    background-color: transparent!important;
}

.action-buttons .start-btn:hover {
    background-color: #198754 !important;
    color: #fff !important;
    border-color: #198754 !important;
}

/* Done button - Green */
.action-buttons .done-btn {
    color: #198754 !important;
    border: 1px solid #198754 !important;
    background-color: transparent!important;
}

.action-buttons .done-btn:hover {
    background-color: #198754 !important;
    color: #fff !important;
    border-color: #198754 !important;
}

/* Archive button- Red */
.action-buttons .archive-btn {
    color: #dc3545 !important;
    border: 1px solid #dc3545 !important;
    background-color: transparent!important;
}

.action-buttons .archive-btn:hover {
    background-color: #dc3545 !important;
    color: #fff !important;
    border-color: #dc3545 !important;
}

/* Icons inside buttons */
.action-buttons .btn i {
    font-size: 11px;
    margin-right: 3px;
}

/* Pagination styles */
.pagination {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 4px;
}

.pagination .page-item {
    display: inline-block;
}

.pagination .page-link {
    display: inline-block;
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    color: #1e6031;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.2s;
}

.pagination .page-item.active .page-link {
    background-color: #1e6031;
    border-color: #1e6031;
    color: white;
}

.pagination .page-item.disabled .page-link {
    color: #9ca3af;
    cursor: not-allowed;
    background-color: #f9fafb;
}

.pagination .page-link:hover:not(.disabled) {
    background-color: #f3f4f6;
    border-color: #d1d5db;
}
</style>