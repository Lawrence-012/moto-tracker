<x-admin-layout>
        @section('page_title', 'MPIS | Division Assignment')
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Back Button -->
            <div class="mb-4">
                <a href="{{ route('admin.leaders.office.show', $division->office) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-500 border border-gray-600 rounded-md text-white hover:bg-gray-600 hover:border-gray-700 transition-all duration-200 no-underline text-sm" style="text-decoration: none !important;">
                    <svg class="h-4 w-4 mr-1.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </div>
            
            <!-- Division Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                        <svg class="inline-block h-6 w-6 text-[#1e6031] mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        {{ $division->division_name }}
                    </h2>
                </div>
            </div>
            <!-- Division Head Section -->
            <div class="mb-8">
                
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-green-600 leading-snug m-0">Division Head</h3>
                        <p class="text-gray-500 text-xs leading-snug m-0">Division-level leadership</p>
                    </div>

                    @if($divisionHead)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 border border-green-200">
                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Assigned
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Unassigned
                        </span>
                    @endif
                </div>

                
                <!-- Division Head Content - Overview Style -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200 shadow-sm">
                    <div class="px-4 py-3">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex items-start">
                                <!-- Division Head Icon -->
                                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg p-2 mr-3 flex-shrink-0 mt-1">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                
                                <!-- Division Head Details -->
                                <div>
                                    @if($divisionHead)
                                        <div class="text-lg font-semibold text-gray-800 mb-0">
                                            {{ $divisionHead->first_name }} {{ $divisionHead->last_name }}
                                        </div>
                                        <div class="text-gray-600 text-sm mt-0">
                                            {{ $divisionHead->position_name }}
                                        </div>
                                        <div class="mt-1 flex items-center text-sm text-gray-600">
                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Division Leadership Role
                                        </div>
                                    @else
                                        <div class="text-lg font-semibold text-gray-400 mb-0">No Division Head Assigned</div>
                                        <div class="text-gray-500 text-sm mt-0">Division Leadership Role</div>
                                        <div class="mt-1 flex items-center text-sm text-gray-400">
                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            Requires Assignment
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Action Button - Moved to the right side -->
                            <div class="flex-shrink-0 self-start">
                                <button type="button" 
                                        onclick="openDivisionHeadModal({{ $division->id_division }}, '{{ addslashes($division->division_name) }}', {{ $divisionHead ? $divisionHead->id : 'null' }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white {{ $divisionHead ? 'bg-green-600 hover:bg-green-700' : 'bg-green-600 hover:bg-green-700' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150">
                                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    {{ $divisionHead ? 'Change Division Head' : 'Assign Division Head' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Units Section -->
            <div class="mt-10 mb-6">
                @if(!$division->units->isEmpty())
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-2xl font-bold text-green-600 leading-snug m-0">Units</h3>
                            <p class="text-gray-600 text-sm leading-snug m-0">Unit-level leadership under this division</p>
                        </div>
                        <div class="text-sm text-gray-500">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                {{ $division->units->count() }} Units
                            </span>
                        </div>
                    </div>
                @endif
            
                <div class="mb-6">
                @if($division->units->isEmpty())
                    <!-- Division Information -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h4 class="text-md font-semibold text-green-600 mb-4">Division Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <div>
                                        <div class="text-sm font-medium text-blue-900">Parent Office</div>
                                        <div class="text-sm text-blue-700">{{ $division->office->office_name }}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <div>
                                        <div class="text-sm font-medium text-green-900">Subunits</div>
                                        <div class="text-sm text-green-700">{{ $division->subunits_count ?? 0 }} subunits</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-purple-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 text-purple-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <div>
                                        <div class="text-sm font-medium text-purple-900">Employees</div>
                                        <div class="text-sm text-purple-700">{{ $division->employees_count ?? 0 }} employees</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($division->units as $unit)
                            @php
                                // Find unit head through officer table - must have unit_head=1 AND linked position has matching unit_id
                                $unitHead = null;
                                foreach($unit->positions as $position) {
                                    if($position->employee && $position->employee->officer && 
                                       $position->employee->officer->unit_head == true && 
                                       $position->employee->officer->emp_position_id == $position->id) {
                                        $unitHead = $position->employee;
                                        break;
                                    }
                                }
                            @endphp
                            
                            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl border border-indigo-400 p-4 transition-all duration-300 hover:shadow-lg hover:scale-105">
                                <div class="p-0">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 w-10 h-10 flex items-center justify-center shadow-md">
                                                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <h4 class="text-sm font-semibold text-gray-800">{{ $unit->unit_name }}</h4>
                                                <div class="text-xs text-gray-600 mt-1 flex flex-wrap gap-2">
                                                    <span class="inline-flex items-center">
                                                        <svg class="h-3 w-3 mr-1 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                                        </svg>
                                                        {{ $unit->subunits->count() }} Subunits
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        @if($unitHead)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                                <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Assigned
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                                                <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                Unassigned
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="border-t border-gray-100 pt-3">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                @if($unitHead)
                                                    <div class="text-sm font-medium text-gray-800">{{ $unitHead->first_name }} {{ $unitHead->last_name }}</div>
                                                    <div class="text-xs text-gray-600">{{ $unitHead->position_name }}</div>
                                                @else
                                                    <div class="text-sm font-medium text-gray-400">No Unit Head</div>
                                                    <div class="text-xs text-gray-400">Unit Leadership Role</div>
                                                @endif
                                            </div>
                                            
                                            <div class="flex space-x-2">
                                                <a href="{{ route('admin.leaders.unit.show', $unit) }}" 
                                                   class="inline-flex items-center px-2.5 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 no-underline" style="text-decoration: none !important;">
                                                    <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View
                                                </a>
                                                
                                                <button type="button" 
                                                        onclick="openUnitModal({{ $unit->id }}, '{{ addslashes($unit->unit_name) }}', {{ $unitHead ? $unitHead->id : 'null' }})"
                                                        class="inline-flex items-center px-2.5 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white {{ $unitHead ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-indigo-600 hover:bg-indigo-700' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 no-underline" style="text-decoration: none !important;">
                                                    <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    {{ $unitHead ? 'Change' : 'Assign' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Division Head Assignment Modal -->
<div class="modal fade" id="divisionHeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow">
            
            <!-- Modal header -->
            <div class="modal-header d-flex align-items-center" style="background-color:#1e6031; color:white;">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="divisionHeadModalLabel">Division Head Assignment</h5>
                    <small class="text-white opacity-75 d-block" id="divisionHeadModalLabelSub" style="font-size: 0.85rem;"></small>
                </div>
            </div>
            
            <!-- Modal body -->
            <div class="modal-body px-4 py-3">
                <div class="alert alert-info d-flex align-items-start mb-3" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle flex-shrink-0 me-2" viewBox="0 0 16 16" role="img" aria-label="Info:">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.373-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                    </svg>
                    <div>
                        <p class="mb-1 small" style="font-size: 0.65rem;">
                            Assign a Division Head to manage this division.
                        </p>
                        <p class="mb-0 small" style="font-size: 0.65rem;">
                            <strong>Note:</strong> Division Heads report to their respective Vice Presidents.
                        </p>
                        <p class="mb-0 small" style="font-size: 0.65rem;">
                            <strong>Note:</strong> Selecting a new employee will reassign this leadership role.
                        </p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="modal_division_employee_id" class="form-label small fw-semibold text-success mb-2" style="font-size: 0.75rem;">
                        Select Employee
                    </label>
                    <select name="employee_id" id="modal_division_employee_id" 
                            class="form-select" style="font-size: 0.75rem;">
                        <option value="">None - Remove current assignment</option>
                        @foreach($employees as $emp)
                            @php
                                $positionTitle = $emp->positions->first() ? $emp->positions->first()->position_title ?? $emp->position_name : $emp->position_name;
                            @endphp
                            <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} - 
                                {{ $positionTitle }}
                                @if($emp->emp_status == 0)
                                    (Inactive)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text mt-2" style="font-size: 0.65rem;">
                        Select an employee to assign this leadership role. Choose "None" to remove the current assignment.
                    </div>
                </div>
            </div>
            
            <!-- Modal footer -->
            <div class="modal-footer py-2 justify-content-end">
                <button type="button" 
                        class="btn btn-sm btn-outline-secondary me-2 py-1"
                        style="font-size: 0.75rem; height: 30px;" 
                        data-bs-dismiss="modal">
                    Cancel
                </button>
                
                <button type="button" 
                        onclick="updateDivisionHeadRole()"
                        class="btn btn-sm btn-success py-1"
                        style="font-size: 0.75rem; height: 30px; background-color: #1e6031; border-color: #1e6031;">
                    Update Leadership Role
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to open the division head modal
    function openDivisionHeadModal(divisionId, divisionName, currentDivisionHeadId) {
        // Set the current division ID
        window.currentDivisionId = divisionId;
        
        // Update modal header with division name
        document.getElementById('divisionHeadModalLabel').textContent = 'Division Head Assignment';
        document.getElementById('divisionHeadModalLabelSub').textContent = divisionName;
        
        // Set the selected value in the dropdown
        const employeeSelect = document.getElementById('modal_division_employee_id');
        employeeSelect.value = currentDivisionHeadId || '';
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('divisionHeadModal'));
        modal.show();
    }
    
    // Function to update the division head role
    function updateDivisionHeadRole() {
        const selectedEmployeeId = document.getElementById('modal_division_employee_id').value;
        
        // Validate that we have a current division ID
        if (!window.currentDivisionId) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'No division selected. Please close the modal and try again.'
            });
            return;
        }
        
        // Show loading alert
        Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we update the leadership role.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Make the AJAX request to update the leadership role
        fetch('{{ route("admin.leaders.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                type: 'division_head',
                organization_id: window.currentDivisionId,
                employee_id: selectedEmployeeId || null
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errData => {
                    throw new Error(errData.message || 'Network response was not ok');
                });
            }
            return response.json();
        })
        .then(data => {
            if(data.success) {
                // Close the loading alert and show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Leadership role updated successfully!',
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    // Close the modal and refresh the page to show updated information
                    const modalElement = document.getElementById('divisionHeadModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if(modal) {
                        modal.hide();
                    }
                    location.reload();
                });
            } else {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'An error occurred while updating the leadership role.'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: error.message || 'An error occurred while updating the leadership role.'
            });
        });
    }
</script>

<!-- Remove underlines from sidebar links -->
<style>
#desktopSidebar a,
#desktopSidebar a span,
#desktopSidebar .organization-submenu a,
#desktopSidebar .personnel-submenu a {
    text-decoration: none !important;
}

#desktopSidebar a:hover,
#desktopSidebar a span:hover,
#desktopSidebar .organization-submenu a:hover,
#desktopSidebar .personnel-submenu a:hover {
    text-decoration: none !important;
}
</style>

<!-- Unit Leadership Modal -->
<div class="modal fade" id="unitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow">
            
            <!-- Modal header -->
            <div class="modal-header d-flex align-items-center" style="background-color:#1e6031; color:white;">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Unit Leadership Assignment</h5>
                    <small class="text-white opacity-75 d-block" id="unitModalSubTitle" style="font-size: 0.85rem;"></small>
                </div>
            </div>
            
            <!-- Modal body -->
            <div class="modal-body px-4 py-3">
                <div class="alert alert-info d-flex align-items-start mb-3" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle flex-shrink-0 me-2" viewBox="0 0 16 16" role="img" aria-label="Info:">
                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                    </svg>
                    <div>
                        <p class="mb-1 small" style="font-size: 0.65rem;">
                            Select the Unit Head who will lead <span id="unitModalTitle">this unit</span>.
                        </p>
                        <p class="mb-0 small" style="font-size: 0.65rem;">
                            <strong>Note:</strong> The University President, Vice Presidents, and Division Heads cannot be assigned as Unit Heads.
                        </p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="modal_unit_employee_id" class="form-label small fw-semibold text-success mb-2" style="font-size: 0.75rem;">
                        Select Employee
                    </label>
                    <select name="employee_id" id="modal_unit_employee_id" 
                            class="form-select" style="font-size: 0.75rem;">
                        <option value="">None - Remove current assignment</option>
                        @php
                            // Get all employees with their officer and positions data
                            $allEmployees = \App\Models\Employee::with(['positions', 'officer'])
                                ->orderBy('first_name')
                                ->orderBy('last_name')
                                ->get();
                            
                            // Find current unit head for this specific unit
                            $currentUnitHead = null;
                            foreach($unit->positions as $position) {
                                if($position->employee && $position->employee->officer && 
                                   $position->employee->officer->unit_head == true && 
                                   $position->employee->officer->emp_position_id == $position->id) {
                                    $currentUnitHead = $position->employee;
                                    break;
                                }
                            }
                            
                            // Filter out President, VPs, Division Heads, and other Unit Heads (but keep current one)
                            $eligibleEmployees = $allEmployees->filter(function($emp) use ($currentUnitHead) {
                                // Exclude if president
                                if ($emp->officer && $emp->officer->president) {
                                    return false;
                                }
                                // Exclude if VP
                                if ($emp->officer && $emp->officer->vp) {
                                    return false;
                                }
                                // Exclude if division head
                                if ($emp->officer && $emp->officer->division_head) {
                                    return false;
                                }
                                // Exclude if unit head BUT allow current unit head to remain selected
                                if ($emp->officer && $emp->officer->unit_head) {
                                    if ($currentUnitHead && $emp->id == $currentUnitHead->id) {
                                        return true; // Allow current unit head
                                    }
                                    return false; // Exclude other unit heads
                                }
                                return true;
                            });
                        @endphp
                        @foreach($eligibleEmployees as $emp)
                            @php
                                $positionTitle = $emp->positions->first() ? $emp->positions->first()->position_title ?? $emp->position_name : $emp->position_name;
                            @endphp
                            <option value="{{ $emp->id }}">
                                {{ $emp->first_name }} {{ $emp->last_name }} - 
                                {{ $positionTitle }}
                                @if($emp->emp_status == 0)
                                    (Inactive)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text mt-2" style="font-size: 0.65rem;">
                        Select an employee to assign this leadership role. Choose "None" to remove the current assignment.
                    </div>
                </div>
            </div>
            
            <!-- Modal footer -->
            <div class="modal-footer py-2 justify-content-end">
                <button type="button" 
                        class="btn btn-sm btn-outline-secondary me-2 py-1"
                        style="font-size: 0.75rem; height: 30px;" 
                        data-bs-dismiss="modal">
                    Cancel
                </button>
                
                <button type="button" 
                        onclick="updateUnitLeadershipRole()"
                        class="btn btn-sm btn-success py-1"
                        style="font-size: 0.75rem; height: 30px; background-color: #1e6031; border-color: #1e6031;">
                    Update Leadership Role
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentUnitId = null;

    function openUnitModal(unitId, unitName, currentUnitHeadId) {
        currentUnitId = unitId;
        
        // Update modal title and subtitle
        document.getElementById('unitModalTitle').textContent = unitName;
        document.getElementById('unitModalSubTitle').textContent = unitName;
        
        // Set the selected value in the dropdown
        const employeeSelect = document.getElementById('modal_unit_employee_id');
        
        // If there's a current unit head, make sure they appear in the dropdown
        if (currentUnitHeadId) {
            // Check if the current unit head exists in the dropdown
            let found = false;
            for (let i = 0; i < employeeSelect.options.length; i++) {
                if (employeeSelect.options[i].value == currentUnitHeadId) {
                    found = true;
                    break;
                }
            }
            
            // If not found, we need to add them (they were filtered out but should be visible for this unit)
            if (!found) {
                // Get current unit head data from the page context
                @if(isset($division) && $division->units->isNotEmpty())
                    @foreach($division->units as $unit)
                        @php
                            $unitHead = null;
                            foreach($unit->positions as $position) {
                                if($position->employee && $position->employee->officer && 
                                   $position->employee->officer->unit_head == true && 
                                   $position->employee->officer->emp_position_id == $position->id) {
                                    $unitHead = $position->employee;
                                    break;
                                }
                            }
                            if ($unitHead) {
                                echo "if (currentUnitHeadId == {$unitHead->id}) {";
                                echo "const option = document.createElement('option');";
                                echo "option.value = {$unitHead->id};";
                                echo "option.textContent = '" . addslashes($unitHead->first_name . ' ' . $unitHead->last_name . ' - ' . $unitHead->position_name) . "';";
                                echo "employeeSelect.insertBefore(option, employeeSelect.firstChild.nextSibling);"; // Insert after "None" option
                                echo "}";
                            }
                        @endphp
                    @endforeach
                @endif
            }
        }
        
        employeeSelect.value = currentUnitHeadId || '';
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('unitModal'));
        modal.show();
    }

    function updateUnitLeadershipRole() {
        const selectedEmployeeId = document.getElementById('modal_unit_employee_id').value;
        
        // Validate that we have a current unit ID
        if (!currentUnitId) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'No unit selected. Please close the modal and try again.'
            });
            return;
        }
        
        // Show loading alert
        Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we update the leadership role.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Make the AJAX request to update the leadership role
        fetch('{{ route("admin.leaders.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                type: 'unit_head',
                organization_id: currentUnitId,
                employee_id: selectedEmployeeId || null
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errData => {
                    throw new Error(errData.message || 'Network response was not ok');
                });
            }
            return response.json();
        })
        .then(data => {
            if(data.success) {
                // Close the loading alert and show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Leadership role updated successfully!',
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    // Close the modal and refresh the page to show updated information
                    const modalElement = document.getElementById('unitModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if(modal) {
                        modal.hide();
                    }
                    location.reload();
                });
            } else {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'An error occurred while updating the leadership role.'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: error.message || 'An error occurred while updating the leadership role.'
            });
        });
    }
</script>
