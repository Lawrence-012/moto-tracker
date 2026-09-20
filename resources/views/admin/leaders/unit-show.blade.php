<x-admin-layout>
        @section('page_title', 'MPIS | Unit Assignment')
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- CSRF Token for AJAX requests -->
            <meta name="csrf-token" content="{{ csrf_token() }}">
            
            <!-- Back Button -->
            <div class="mb-4">
                <a href="{{ route('admin.leaders.division.show', $unit->division) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-500 border border-gray-600 rounded-md text-white hover:bg-gray-600 hover:border-gray-700 transition-all duration-200 no-underline text-sm" style="text-decoration: none !important;">
                    <svg class="h-4 w-4 mr-1.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </div>
            
            <!-- Unit Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                        <svg class="inline-block h-6 w-6 text-[#1e6031] mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        {{ $unit->unit_name }}
                    </h2>
                </div>
            </div>
            <!-- Unit Head Section -->
            <div class="mb-8">
                
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-green-600 leading-snug m-0">Unit Head</h3>
                        <p class="text-gray-500 text-xs leading-snug m-0">Unit-level leadership</p>
                    </div>

                    @if($unitHead)
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

                <!-- Unit Head Content - Overview Style -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200 shadow-sm">
                    <div class="px-4 py-3">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex items-start">
                                <!-- Unit Head Icon -->
                                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg p-2 mr-3 flex-shrink-0 mt-1">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                
                                <!-- Unit Head Details -->
                                <div>
                                    @if($unitHead)
                                        <div class="text-lg font-semibold text-gray-800 mb-0">
                                            {{ $unitHead->first_name }} {{ $unitHead->last_name }}
                                        </div>
                                        <div class="text-gray-600 text-sm mt-0">
                                            {{ $unitHead->position_name }}
                                        </div>
                                        <div class="mt-1 flex items-center text-sm text-gray-600">
                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Unit Leadership Role
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            Reports to: {{ $unit->division->division_name }} Division Head
                                        </div>
                                    @else
                                        <div class="text-lg font-semibold text-gray-400 mb-0">No Unit Head Assigned</div>
                                        <div class="text-gray-500 text-sm mt-0">Unit Leadership Role</div>
                                        <div class="mt-1 flex items-center text-sm text-gray-400">
                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            Requires Assignment
                                        </div>
                                        <div class="mt-1 text-xs text-gray-400">
                                            Reports to: {{ $unit->division->division_name }} Division Head
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Action Button - Moved to the right side -->
                            <div class="flex-shrink-0 self-start">
                                <button type="button" onclick="openUnitModal({{ $unit->id }}, '{{ addslashes($unit->unit_name) }}', {{ $unitHead ? $unitHead->id : 'null' }})"
                                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white {{ $unitHead ? 'bg-green-600 hover:bg-green-700' : 'bg-green-600 hover:bg-green-700' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 no-underline" style="text-decoration: none !important;">
                                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    {{ $unitHead ? 'Change Unit Head' : 'Assign Unit Head' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                    
                    <!-- Subunits Section -->
                    @if($unit->subunits->isNotEmpty())
                        <div class="mt-10 mb-6">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-2xl font-bold text-green-600 leading-snug m-0">Subunits</h3>
                                    <p class="text-gray-600 text-sm leading-snug m-0">Subunit-level organization under this unit</p>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        {{ $unit->subunits->count() }} Subunits
                                    </span>
                                </div>
                            </div>
                        
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                @foreach($unit->subunits as $subunit)
                                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl border border-indigo-400 p-4 transition-all duration-300 hover:shadow-lg hover:scale-105">
                                        <div class="p-0">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 w-10 h-10 flex items-center justify-center shadow-md">
                                                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="ml-3">
                                                        <h4 class="text-sm font-semibold text-gray-800">{{ $subunit->subunit_name }}</h4>
                                                        <div class="text-xs text-gray-600 mt-1">
                                                            {{ $subunit->subunit_abbr }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="border-t border-gray-100 pt-3">
                                                <div class="text-xs text-gray-500">
                                                    Subunit Organization
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <!-- Unit Information (only shown when no subunits exist) -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h4 class="text-md font-semibold text-green-600 mb-4">Unit Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <div>
                                            <div class="text-sm font-medium text-blue-900">Parent Division</div>
                                            <div class="text-sm text-blue-700">{{ $unit->division->division_name }}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-indigo-50 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-indigo-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <div>
                                            <div class="text-sm font-medium text-indigo-900">Parent Office</div>
                                            <div class="text-sm text-indigo-700">{{ $unit->division->office->office_name }}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                        </svg>
                                        <div>
                                            <div class="text-sm font-medium text-green-900">Subunits</div>
                                            <div class="text-sm text-green-700">{{ $unit->subunits_count ?? 0 }} subunits</div>
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
                                            <div class="text-sm text-purple-700">{{ $unit->employees_count ?? 0 }} employees</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    let currentUnitModalInstance = null;

    function openUnitModal(unitId, unitName, currentEmployeeId) {
        console.log('openUnitModal called with:', unitId, unitName, currentEmployeeId);
        currentUnitId = unitId;
        
        // Update modal title and subtitle
        document.getElementById('unitModalTitle').textContent = unitName;
        document.getElementById('unitModalSubTitle').textContent = unitName;
        
        // Set the selected value in the dropdown
        const employeeSelect = document.getElementById('modal_unit_employee_id');
        employeeSelect.value = currentEmployeeId || '';
        
        // Show the modal using Bootstrap's modal API
        const modalElement = document.getElementById('unitModal');
        
        // Check if modal element exists
        if (!modalElement) {
            console.error('Unit modal element not found');
            return;
        }
        
        // Close any existing unit modal instance first
        if (currentUnitModalInstance) {
            try {
                currentUnitModalInstance.hide();
            } catch(e) {
                console.warn('Could not hide existing unit modal instance:', e);
            }
        }
        
        // Wait for Bootstrap to be available
        waitForBootstrap(function() {
            // Make sure we close any existing modal instance first
            const existingModal = bootstrap.Modal.getInstance(modalElement);
            if (existingModal) {
                existingModal.dispose();
            }
            
            const modal = new bootstrap.Modal(modalElement);
            currentUnitModalInstance = modal; // Store reference to prevent conflicts
            
            modalElement.addEventListener('hidden.bs.modal', function () {
                // Reset the unit ID when modal is closed
                currentUnitId = null;
                currentUnitModalInstance = null;
            });
            
            modal.show();
        }, function() {
            // Fallback: use direct class manipulation if Bootstrap is not ready
            console.error('Bootstrap Modal API not available for unit modal');
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            document.body.classList.add('modal-open');
            
            // Add backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.setAttribute('data-bs-dismiss', 'modal');
            document.body.appendChild(backdrop);
            
            // Add close functionality
            backdrop.addEventListener('click', function() {
                modalElement.classList.remove('show');
                modalElement.style.display = 'none';
                document.body.classList.remove('modal-open');
                document.body.removeChild(backdrop);
                currentUnitId = null;
                currentUnitModalInstance = null;
            });
        });
    }
    
    function waitForBootstrap(successCallback, errorCallback) {
        let attempts = 0;
        const maxAttempts = 20; // 2 seconds max wait
        
        function checkBootstrap() {
            if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
                successCallback();
            } else if (attempts < maxAttempts) {
                attempts++;
                setTimeout(checkBootstrap, 100); // Wait 100ms between checks
            } else {
                errorCallback();
            }
        }
        
        checkBootstrap();
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

        // Create form data
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]').value);
        formData.append('type', 'unit_head');
        formData.append('employee_id', selectedEmployeeId);
        formData.append('organization_id', currentUnitId);
        
        // Submit form via AJAX to show success message
        fetch('{{ route("admin.leaders.update") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]').value,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            // Handle the response based on content type
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                // If it's JSON, parse it normally
                return response.json();
            } else {
                // If it's HTML (redirect response), check status to determine success/error
                if (response.ok || response.status === 302) { // 302 is redirect
                    // Return success since it's likely a successful redirect
                    return { success: true, message: 'Leadership role updated successfully!' };
                } else {
                    return { success: false, message: 'An error occurred while updating the leadership role.' };
                }
            }
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
                text: 'An error occurred while updating the leadership role.'
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

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
