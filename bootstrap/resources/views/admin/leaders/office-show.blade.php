<x-admin-layout>
        @section('page_title', 'MPIS | VP Assignment')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Back Button -->
            <div class="mb-4">
                <a href="{{ route('admin.leaders.offices') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-500 border border-gray-600 rounded-md text-white hover:bg-gray-600 hover:border-gray-700 transition-all duration-200 no-underline text-sm" style="text-decoration: none !important;">
                    <svg class="h-4 w-4 mr-1.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </div>
          <!-- VP Section -->
<div class="mb-8"> <!-- removed bg-white shadow-sm sm:rounded-lg -->
    
    <div class="flex items-center justify-between mb-4">
        <div>
            @php
                $isPresidentOffice = strpos($office->office_name, 'Office of the University President') !== false;
            @endphp
            @if($isPresidentOffice)
                <h3 class="text-2xl font-bold text-green-600 leading-snug m-0">University President</h3>
                <p class="text-gray-600 text-sm leading-snug m-0">Top-level executive leadership</p>
            @else
                <h3 class="text-2xl font-bold text-green-600 leading-snug m-0">Vice President</h3>
                <p class="text-gray-600 text-sm leading-snug m-0">Office-level leadership</p>
            @endif
        </div>

        @if($isPresidentOffice)
            <!-- Don't show status badge for President's office -->
        @else
            @if($vp)
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
        @endif
    </div>


                    
    <!-- VP Content - Overview Style -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200 shadow-sm">
        <div class="px-4 py-3">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-start">
                    <!-- VP Icon -->
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg p-2 mr-3 flex-shrink-0 mt-1">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    
                    <!-- VP Details -->
                    <div>
                        @php
                            $isPresidentOffice = strpos($office->office_name, 'Office of the University President') !== false;
                        @endphp
                        @if($vp && !$isPresidentOffice)
                            <div class="text-lg font-semibold text-gray-800 mb-0">
                                {{ $vp->first_name }} {{ $vp->last_name }}
                            </div>
                            <div class="text-gray-600 text-sm mt-0">
                                {{ $vp->position_name }}
                            </div>
                            <div class="mt-1 flex items-center text-sm text-gray-600">
                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Office Leadership Role
                            </div>
                        @elseif($isPresidentOffice)
                            <div class="text-lg font-semibold text-gray-400 mb-0">President's Office</div>
                            <div class="text-gray-500 text-sm mt-0">No VP Assignment</div>
                            <div class="mt-1 flex items-center text-sm text-gray-400">
                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                President's Office - No VP Assignment
                            </div>
                        @else
                            <div class="text-lg font-semibold text-gray-400 mb-0">No Vice President Assigned</div>
                            <div class="text-gray-500 text-sm mt-0">Office Leadership Role</div>
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
                    @php
                        $isPresidentOffice = strpos($office->office_name, 'Office of the University President') !== false;
                    @endphp
                    @if(!$isPresidentOffice)
                        <button type="button" onclick="openOfficeModal({{ $office->id }}, '{{ addslashes($office->office_name) }}', {{ $vp ? $vp->id : 'null' }})"
                               class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white {{ $vp ? 'bg-green-600 hover:bg-green-700' : 'bg-green-600 hover:bg-green-700' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 no-underline" style="text-decoration: none !important;">
                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            {{ $vp ? 'Change VP' : 'Assign VP' }}
                        </button>
                    @else
                        <span class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-500 bg-gray-100 rounded-md">
                            President's Office - No VP Assignment
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    </div>

       <!-- Divisions Section -->
            <div class="mt-10 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-green-600 leading-snug m-0">Divisions</h3>
                        <p class="text-gray-600 text-sm leading-snug m-0">Division-level leadership under this office</p>
                    </div>
                    <div class="text-sm text-gray-500">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ $office->divisions->count() }} Divisions
                        </span>
                    </div>
                </div>

                    @if($office->divisions->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No divisions found</h3>
                            <p class="mt-1 text-sm text-gray-500">Create divisions in the organization management section.</p>
                        </div>
                    @else
                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 overflow-visible">  @foreach($office->divisions as $division)
                                @php
                                    $divisionHead = $division->employees->filter(function ($employee) {
                                        return $employee->is_divisionhead;
                                    })->first();
                                @endphp
                                
<div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl border border-indigo-400 p-4 transition-all duration-300 hover:shadow-lg hover:scale-105 relative z-10">  <div class="p-0">
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
                                                    <h4 class="text-sm font-semibold text-gray-800">{{ $division->division_name }}</h4>
                                                    <div class="text-xs text-gray-600 mt-1 flex flex-wrap gap-2">
                                                        <span class="inline-flex items-center">
                                                            <svg class="h-3 w-3 mr-1 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                                            </svg>
                                                            {{ $division->units->count() }} Units
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            @if($divisionHead)
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
                                                    @if($divisionHead)
                                                        <div class="text-sm font-medium text-gray-800">{{ $divisionHead->first_name }} {{ $divisionHead->last_name }}</div>
                                                        <div class="text-xs text-gray-600">{{ $divisionHead->position_name }}</div>
                                                    @else
                                                        <div class="text-sm font-medium text-gray-400">No Division Head</div>
                                                        <div class="text-xs text-gray-400">Division Leadership Role</div>
                                                    @endif
                                                </div>
                                                
                                                <div class="flex space-x-2">
                                                    <a href="{{ route('admin.leaders.division.show', $division) }}" 
                                                       class="inline-flex items-center px-2.5 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 no-underline" style="text-decoration: none !important;">
                                                        <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        View
                                                    </a>
                                                    
                                                    <button type="button" 
                                                            onclick="openDivisionHeadModal({{ $division->id_division }}, '{{ addslashes($division->division_name) }}', {{ $divisionHead ? $divisionHead->id : 'null' }})"
                                                            class="inline-flex items-center px-2.5 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white {{ $divisionHead ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-green-600 hover:bg-green-700' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 no-underline" style="text-decoration: none !important;">
                                                        <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        {{ $divisionHead ? 'Change' : 'Assign' }}
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

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Office Leadership Modal -->
<div class="modal fade" id="officeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow">
            
            <!-- Modal header -->
            <div class="modal-header d-flex align-items-center" style="background-color:#1e6031; color:white;">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Vice President Assignment</h5>
                    <small class="text-white opacity-75 d-block" id="officeModalSubTitle" style="font-size: 0.85rem;"></small>
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
                            Select the Vice President who will lead <span id="officeModalTitle">this office</span>.
                        </p>
                        <p class="mb-0 small" style="font-size: 0.65rem;">
                            <strong>Note:</strong> The University President cannot be assigned as a Vice President.
                        </p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="modal_office_employee_id" class="form-label small fw-semibold text-success mb-2" style="font-size: 0.75rem;">
                        Select Employee
                    </label>
                    <select name="employee_id" id="modal_office_employee_id" 
                            class="form-select" style="font-size: 0.75rem;">
                        <option value="">None - Remove current assignment</option>
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
                        onclick="updateOfficeLeadershipRole()"
                        class="btn btn-sm btn-success py-1"
                        style="font-size: 0.75rem; height: 30px; background-color: #1e6031; border-color: #1e6031;">
                    Update Leadership Role
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Pass employee data to JavaScript (sorted by first name alphabetically)
    const employeeData = [
        @foreach(\App\Models\Employee::with('officer')->orderBy('first_name')->orderBy('last_name')->get() as $emp)
            {
                id: {{ $emp->id }},
                first_name: "{{ addslashes($emp->first_name) }}",
                last_name: "{{ addslashes($emp->last_name) }}",
                position_name: "{{ addslashes($emp->position_name) }}",
                emp_status: {{ $emp->emp_status }},
                is_president: {{ $emp->officer && $emp->officer->president ? 'true' : 'false' }}
            },
            @endforeach
    ];

    let currentOfficeId = null;

    function openOfficeModal(officeId, officeName, currentEmployeeId) {
        currentOfficeId = officeId;
        
        // Update modal title and subtitle to match the format you specified
        document.getElementById('officeModalTitle').textContent = officeName;
        document.getElementById('officeModalSubTitle').textContent = officeName;
        
        // Load employees via AJAX
        loadOfficeEmployees(currentEmployeeId);
        
        // Show the modal using Bootstrap's modal API
        const modalElement = document.getElementById('officeModal');
        
        // Make sure we close any existing modal instance first
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.dispose();
        }
        
        const modal = new bootstrap.Modal(modalElement);
        modalElement.addEventListener('hidden.bs.modal', function () {
            // Reset the office ID when modal is closed
            currentOfficeId = null;
        });
        
        modal.show();
    }

    function loadOfficeEmployees(currentEmployeeId) {
        // Show loading state
        const selectElement = document.getElementById('modal_office_employee_id');
        selectElement.innerHTML = '<option value="">Loading...</option>';
        selectElement.disabled = true;

        // Wait for the employee data to be available
        setTimeout(() => {
            // Clear the select element
            selectElement.innerHTML = '';
            
            // Add the "None" option
            const noneOption = document.createElement('option');
            noneOption.value = '';
            noneOption.textContent = 'None - Remove current assignment';
            selectElement.appendChild(noneOption);
            
            // Add employees to the select element (excluding presidents)
            employeeData.forEach(emp => {
                // Skip if employee is a president
                if (!emp.is_president) {
                    const option = document.createElement('option');
                    option.value = emp.id;
                    option.textContent = `${emp.first_name} ${emp.last_name} - ${emp.position_name}${emp.emp_status === 0 ? ' (Inactive)' : ''}`;
                    
                    // Pre-select the current employee if applicable
                    if (currentEmployeeId && emp.id == currentEmployeeId) {
                        option.selected = true;
                    }
                    
                    selectElement.appendChild(option);
                }
            });
            
            selectElement.disabled = false;
        }, 100); // Small delay to ensure data is loaded
    }

    function updateOfficeLeadershipRole() {
        const selectedEmployeeId = document.getElementById('modal_office_employee_id').value;
        
        // Validate that we have a current office ID
        if (!currentOfficeId) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'No office selected. Please close the modal and try again.'
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
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        formData.append('type', 'vp');
        formData.append('employee_id', selectedEmployeeId);
        formData.append('organization_id', currentOfficeId);
        
        // Submit form via AJAX to show success message
        fetch('{{ route("admin.leaders.update") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
                    const modalElement = document.getElementById('officeModal');
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

<!-- Division Head Assignment Modal -->
<div class="modal fade" id="divisionHeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow">
            
            <!-- Modal header -->
            <div class="modal-header d-flex align-items-center" style="background-color:#1e6031; color:white;">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="divisionHeadModalLabelMain">Division Head Assignment</h5>
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
                        @php
                            $eligibleEmployees = \App\Models\Employee::with(['positions', 'officer'])
                                ->whereDoesntHave('officer', function ($query) {
                                    $query->where('president', true)
                                          ->orWhere('vp', true);
                                })
                                ->orderBy('first_name')
                                ->orderBy('last_name')
                                ->get();
                        @endphp
                        @foreach($eligibleEmployees as $emp)
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
        document.getElementById('divisionHeadModalLabelMain').textContent = 'Division Head Assignment';
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
