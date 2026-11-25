@extends('layouts.employee')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ isset($travelOrder) && $travelOrder->id ? __('Edit Travel Order') : __('Create Travel Order') }}
    </h2>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Store officers data organized by role
    const officersData = @json($officers);
    const positions = @json($positions);
    const units = @json($units);
    
    // Find Transportation Services unit ID
    let transportationServicesUnitId = null;
    if (units && units.length > 0) {
        const transportationUnit = units.find(u => 
            u.unit_name && u.unit_name.toLowerCase().includes('transportation')
        );
        
        if (transportationUnit) {
            transportationServicesUnitId = transportationUnit.id;
            console.log('✅ Found Transportation Services Unit ID:', transportationServicesUnitId);
        } else {
            console.warn('⚠️ Transportation Services unit not found in units list');
        }
    }
    
    // Position change handler
    const positionSelect = document.getElementById('position_id');
    positionSelect.addEventListener('change', function() {
        const selectedPositionId = this.value;
        if (selectedPositionId) {
            populateApproverDropdowns(selectedPositionId);
        } else {
            clearApproverDropdowns();
        }
        validateApproverCount();
    });
    
    // Add change listeners to approver dropdowns
    const approverDropdowns = document.querySelectorAll('.approver-select');
    approverDropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            updateHiddenApproverFields();
            validateApproverCount();
        });
    });
    
    // Function to populate approver dropdowns based on position
    function populateApproverDropdowns(positionId) {
        // Find the selected position data
        const positions = @json($positions);
        const selectedPosition = positions.find(p => p.id == positionId);
        
        if (!selectedPosition) return;
        
        // Get office, division, unit IDs from the selected position
        const officeId = selectedPosition.office_id;
        const divisionId = selectedPosition.division_id;
        const unitId = selectedPosition.unit_id;
        
        // Check if the selected position is an officer position (VP, Division Head, or Unit Head)
        const isOfficerPosition = selectedPosition.is_vp || selectedPosition.is_division_head || selectedPosition.is_unit_head;
        
        // Filter officers by their roles and organizational units
        // If the user selected an officer position, show ALL officers (they can select from any office)
        // If the user selected a regular employee position, filter by their office
        const presidentOfficers = officersData.filter(officer => {
            // Show Presidents
            if (!officer.officer || !officer.officer.president) return false;
            
            // If selecting an officer position, show all Presidents
            if (isOfficerPosition) return true;
            
            // Otherwise, filter by office
            return !officeId || officer.employee.positions.some(p => p.office_id == officeId);
        });
        
        const vpOfficers = officersData.filter(officer => {
            // Show ONLY VPs (not President)
            if (!officer.officer || !officer.officer.vp) return false;
            
            // If selecting an officer position, show all VPs
            if (isOfficerPosition) return true;
            
            // Otherwise, filter by office
            return !officeId || officer.employee.positions.some(p => p.office_id == officeId);
        });
        
        const divisionHeads = officersData.filter(officer => {
            if (!officer.officer || !officer.officer.division_head) return false;
            
            // If selecting an officer position, show all Division Heads
            if (isOfficerPosition) return true;
            
            // Otherwise, filter by division
            return !divisionId || officer.employee.positions.some(p => p.division_id == divisionId);
        });
        
        const unitHeads = officersData.filter(officer => {
            if (!officer.officer || !officer.officer.unit_head) return false;
            
            // Exclude Unit Head of Transportation Services unit
            const isTransportationServices = officer.employee.positions.some(p => {
                return p.unit_id && transportationServicesUnitId && p.unit_id === transportationServicesUnitId;
            });
            
            if (isTransportationServices) {
                return false;
            }
            
            // If selecting an officer position, show all other Unit Heads
            if (isOfficerPosition) return true;
            
            // Otherwise, filter by unit
            return !unitId || officer.employee.positions.some(p => p.unit_id == unitId);
        });
        
        // Populate President dropdown
        populateDropdown('president_approver', presidentOfficers, 'Select University President');
        
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
        dropdown.innerHTML = `<option value="">${defaultText}</option>`;
        
        officers.forEach(officer => {
            const fullName = `${officer.employee.first_name} ${officer.employee.last_name}`;
            const role = officer.officer.getPrimaryRole();
            const option = document.createElement('option');
            option.value = officer.employee.id;
            option.textContent = `${fullName} - ${role}`;
            dropdown.appendChild(option);
        });
    }
    
    // Function to clear all approver dropdowns
    function clearApproverDropdowns() {
        document.getElementById('president_approver').innerHTML = '<option value="">Select University President</option>';
        document.getElementById('vp_approver').innerHTML = '<option value="">Select Vice President</option>';
        document.getElementById('division_head_approver').innerHTML = '<option value="">Select Division Head</option>';
        document.getElementById('unit_head_approver').innerHTML = '<option value="">Select Unit Head</option>';
    }
    
    // Function to update hidden approver fields based on dropdown selections
    function updateHiddenApproverFields() {
        const presidentValue = document.getElementById('president_approver').value;
        const vpValue = document.getElementById('vp_approver').value;
        const divisionHeaderValue = document.getElementById('division_head_approver').value;
        const unitHeadValue = document.getElementById('unit_head_approver').value;
        
        const selectedApprovers = [presidentValue, vpValue, divisionHeaderValue, unitHeadValue].filter(v => v !== '');
    }
    
    // Function to validate that only 1-2 approvers are selected (SKIP FOR PRESIDENT)
    function validateApproverCount() {
        // Check if user is President - if so, skip validation
        const isPresident = {{ $isPresident ? 'true' : 'false' }};
        if (isPresident === true) {
            return true;
        }
        
        // If error div doesn't exist (hidden by @if), skip validation
        const errorDiv = document.getElementById('approver-error');
        if (!errorDiv) {
            return true;
        }
        
        const presidentValue = document.getElementById('president_approver');
        const vpValue = document.getElementById('vp_approver');
        const divisionHeaderValue = document.getElementById('division_head_approver');
        const unitHeadValue = document.getElementById('unit_head_approver');
        
        // If dropdowns don't exist (President), skip validation
        if (!presidentValue || !vpValue || !divisionHeaderValue || !unitHeadValue) {
            return true;
        }
        
        const selectedCount = [presidentValue.value, vpValue.value, divisionHeaderValue.value, unitHeadValue.value].filter(v => v !== '').length;
        const submitButton = document.querySelector('button[type="submit"]');
        
        if (selectedCount === 0 || selectedCount > 2) {
            // Show error
            errorDiv.classList.remove('hidden');
            if (selectedCount > 2) {
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> You can only select 1 or 2 approvers. Please remove one selection.';
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
            errorDiv.classList.add('hidden');
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
    
    // Form submission handler - SKIP VALIDATION FOR PRESIDENT
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        // Check if user is President - if so, skip validation
        const isPresident = {{ $isPresident ? 'true' : 'false' }};
        if (isPresident === true) {
            console.log('PRESIDENT SUBMISSION - Skipping approver validation');
            return; // Allow submission without validation
        }
        
        const presidentValue = document.getElementById('president_approver');
        const vpValue = document.getElementById('vp_approver');
        const divisionHeaderValue = document.getElementById('division_head_approver');
        const unitHeadValue = document.getElementById('unit_head_approver');
        
        // If dropdowns don't exist (President), allow submission
        if (!presidentValue || !vpValue || !divisionHeaderValue || !unitHeadValue) {
            console.log('PRESIDENT SUBMISSION - No approver dropdowns found');
            return; // Allow submission
        }
        
        console.log('=== FORM SUBMISSION DEBUG ===');
        console.log('President value:', presidentValue.value, '(type:', typeof presidentValue.value + ')');
        console.log('VP value:', vpValue.value);
        console.log('Division Head value:', divisionHeaderValue.value);
        console.log('Unit Head value:', unitHeadValue.value);
        
        const selectedCount = [presidentValue.value, vpValue.value, divisionHeaderValue.value, unitHeadValue.value].filter(v => v !== '').length;
        
        console.log('Selected count:', selectedCount);
        console.log('Form action:', form.action);
        
        if (selectedCount === 0 || selectedCount > 2) {
            e.preventDefault();
            console.error('BLOCKING SUBMISSION - Count is', selectedCount);
            alert('Please select exactly 1 or 2 approvers.');
        } else {
            console.log('✓ ALLOWING SUBMISSION with', selectedCount, 'approver(s)');
        }
    });
    
    // Pre-populate approvers when editing
    @if(isset($travelOrder) && $travelOrder->id)
        setTimeout(function() {
            const positionSelect = document.getElementById('position_id');
            if (positionSelect.value) {
                // Trigger change event to populate approver dropdowns
                const event = new Event('change');
                positionSelect.dispatchEvent(event);
                
                // After dropdowns are populated, set the selected approvers
                setTimeout(function() {
                    // Get current approvers from travel order
                    const approver1Id = {{ $travelOrder->approver1_employee_id ?? 'null' }};
                    const approver2Id = {{ $travelOrder->approver2_employee_id ?? 'null' }};
                    
                    // Set the approvers in the dropdowns
                    if (approver1Id) {
                        // Find which dropdown this approver should go in based on their role
                        const presidentDropdown = document.getElementById('president_approver');
                        const vpDropdown = document.getElementById('vp_approver');
                        const divisionHeadDropdown = document.getElementById('division_head_approver');
                        const unitHeadDropdown = document.getElementById('unit_head_approver');
                        
                        // Check if approver1 exists in any dropdown
                        if (presidentDropdown.querySelector(`option[value="${approver1Id}"]`)) {
                            presidentDropdown.value = approver1Id;
                        } else if (vpDropdown.querySelector(`option[value="${approver1Id}"]`)) {
                            vpDropdown.value = approver1Id;
                        } else if (divisionHeadDropdown.querySelector(`option[value="${approver1Id}"]`)) {
                            divisionHeadDropdown.value = approver1Id;
                        } else if (unitHeadDropdown.querySelector(`option[value="${approver1Id}"]`)) {
                            unitHeadDropdown.value = approver1Id;
                        }
                    }
                    
                    if (approver2Id) {
                        const presidentDropdown = document.getElementById('president_approver');
                        const vpDropdown = document.getElementById('vp_approver');
                        const divisionHeadDropdown = document.getElementById('division_head_approver');
                        const unitHeadDropdown = document.getElementById('unit_head_approver');
                        
                        // Check if approver2 exists in any dropdown (excluding the one already selected)
                        if (presidentDropdown.value != approver2Id && presidentDropdown.querySelector(`option[value="${approver2Id}"]`)) {
                            presidentDropdown.value = approver2Id;
                        } else if (vpDropdown.value != approver2Id && vpDropdown.querySelector(`option[value="${approver2Id}"]`)) {
                            vpDropdown.value = approver2Id;
                        } else if (divisionHeadDropdown.value != approver2Id && divisionHeadDropdown.querySelector(`option[value="${approver2Id}"]`)) {
                            divisionHeadDropdown.value = approver2Id;
                        } else if (unitHeadDropdown.value != approver2Id && unitHeadDropdown.querySelector(`option[value="${approver2Id}"]`)) {
                            unitHeadDropdown.value = approver2Id;
                        }
                    }
                    
                    // Update hidden fields and validate
                    updateHiddenApproverFields();
                    validateApproverCount();
                }, 300);
            }
        }, 500);
    @endif
});
</script>
@endpush

@section('content')
<div class="py-6">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <!-- Page Header -->
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h1 class="text-2xl font-bold text-gray-800">
                        {{ isset($travelOrder) && $travelOrder->id ? 'Edit Travel Request' : 'Create New Travel Request' }}
                    </h1>
                    <p class="text-gray-600 mt-1">
                        {{ isset($travelOrder) && $travelOrder->id ? 'Update the details of your travel request' : 'Fill in the details for your travel request' }}
                    </p>
                </div>
                
                @if ($errors->any())
                        <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        There were {{ $errors->count() }} error(s) with your submission
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <ul class="list-disc pl-5 space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($travelOrder) && $travelOrder->id)
                        <form method="POST" action="{{ route('travel-orders.update', $travelOrder) }}">
                            @csrf
                            @method('PUT')
                    @else
                        <form method="POST" action="{{ auth()->user()->employee && auth()->user()->employee->positions->contains('is_vp', true) ? route('vp.travel-orders.store') : route('travel-orders.store') }}">
                            @csrf
                    @endif
                    
                    <div class="space-y-6">
                        <div>
                            <label for="position_id" class="block text-sm font-medium text-gray-700 mb-1">Position <span class="text-danger">*</span></label>
                            <select name="position_id" id="position_id" required
                                class="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] sm:text-sm transition duration-200">
                                <option value="">Select a position</option>
                                @foreach($positions as $position)
                                    @php
                                        $isSelected = old('position_id', isset($travelOrder) && $travelOrder->id ? $travelOrder->emp_position_id : '') == $position->id;
                                    @endphp
                                    <option value="{{ $position->id }}" {{ $isSelected ? 'selected' : '' }}>
                                        {{ $position->position_name }}
                                        @if($position->office) - {{ $position->office->office_name }} @endif
                                        @if($position->is_unit_head) (Unit Head) @elseif($position->is_division_head) (Division Head) @elseif($position->is_vp) (VP) @elseif($position->is_president) (President) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('position_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        @include('travel-orders.partials.destination-picker')
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date <span class="text-danger">*</span></label>
                                <input type="date" name="date_from" id="date_from" value="{{ old('date_from', isset($travelOrder) && $travelOrder->id ? ($travelOrder->date_from instanceof \Illuminate\Support\Carbon ? $travelOrder->date_from->format('Y-m-d') : \Carbon\Carbon::parse($travelOrder->date_from)->format('Y-m-d')) : '') }}" required
                                    class="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] sm:text-sm transition duration-200">
                                @error('date_from')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date <span class="text-danger">*</span></label>
                                <input type="date" name="date_to" id="date_to" value="{{ old('date_to', isset($travelOrder) && $travelOrder->id ? ($travelOrder->date_to instanceof \Illuminate\Support\Carbon ? $travelOrder->date_to->format('Y-m-d') : \Carbon\Carbon::parse($travelOrder->date_to)->format('Y-m-d')) : '') }}" required
                                    class="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] sm:text-sm transition duration-200">
                                @error('date_to')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="departure_time" class="block text-sm font-medium text-gray-700 mb-1">Departure Time</label>
                                <input type="time" name="departure_time" id="departure_time" value="{{ old('departure_time', isset($travelOrder) && $travelOrder->id ? (is_string($travelOrder->departure_time) ? $travelOrder->departure_time : (isset($travelOrder->departure_time) ? \Carbon\Carbon::parse($travelOrder->departure_time)->format('H:i') : '')) : '') }}"
                                    class="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] sm:text-sm transition duration-200">
                                @error('departure_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label for="purpose" class="block text-sm font-medium text-gray-700 mb-1">Purpose <span class="text-danger">*</span></label>
                            <textarea name="purpose" id="purpose" rows="4" required
                                class="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] sm:text-sm transition duration-200">{{ old('purpose', isset($travelOrder) && $travelOrder->id ? $travelOrder->purpose : '') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Provide a brief description of the purpose of your travel</p>
                            @error('purpose')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Approval Selection Section - HIDDEN FOR PRESIDENT -->
                        @php
                            // Check if current route is president travel order creation
                            $isPresidentRoute = Request::routeIs('president.travel-orders.*');
                            
                            // Also check employee's officer record
                            $isPresidentOfficer = Auth::check() && Auth::user()->employee && 
                                Auth::user()->employee->officer && 
                                Auth::user()->employee->officer->president == true;
                            
                            $hideApprovers = $isPresidentRoute || $isPresidentOfficer;
                        @endphp
                        {{-- DEBUG: Route=$isPresidentRoute, Officer=$isPresidentOfficer, Hide=$hideApprovers --}}
                        @if(!$hideApprovers)
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3">Approval Chain (Select 1-2 Approvers Only)</h3>
                            
                            <!-- President Dropdown -->
                            <div class="mb-3">
                                <label for="president_approver" class="block text-sm font-medium text-gray-700 mb-1">University President</label>
                                <select name="president_approver" id="president_approver"
                                    class="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] sm:text-sm transition duration-200 approver-select">
                                    <option value="">Select University President</option>
                                </select>
                            </div>
                            
                            <!-- VP Dropdown -->
                            <div class="mb-3">
                                <label for="vp_approver" class="block text-sm font-medium text-gray-700 mb-1">Vice President (VP)</label>
                                <select name="vp_approver" id="vp_approver"
                                    class="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] sm:text-sm transition duration-200 approver-select">
                                    <option value="">Select Vice President</option>
                                </select>
                            </div>
                            
                            <!-- Division Head Dropdown -->
                            <div class="mb-3">
                                <label for="division_head_approver" class="block text-sm font-medium text-gray-700 mb-1">Division Head</label>
                                <select name="division_head_approver" id="division_head_approver"
                                    class="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] sm:text-sm transition duration-200 approver-select">
                                    <option value="">Select Division Head</option>
                                </select>
                            </div>
                            
                            <!-- Unit Head Dropdown -->
                            <div class="mb-3">
                                <label for="unit_head_approver" class="block text-sm font-medium text-gray-700 mb-1">Unit Head</label>
                                <select name="unit_head_approver" id="unit_head_approver"
                                    class="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] sm:text-sm transition duration-200 approver-select">
                                    <option value="">Select Unit Head</option>
                                </select>
                            </div>
                            
                            <p class="text-xs text-gray-600 mt-2">
                                <i class="fas fa-info-circle"></i> Select at least 1 but no more than 2 approvers from the dropdowns above.
                            </p>
                            <div id="approver-error" class="hidden mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                                <i class="fas fa-exclamation-triangle"></i> You can only select 1 or 2 approvers. Please remove one selection.
                            </div>
                        </div>
                        @endif
                    </div>
                    
                    <div class="mt-8 flex items-center justify-end space-x-3">
                        <a href="{{ URL()->previous() }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 uppercase tracking-wider shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-[#1e6031] border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wider hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#1e6031] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm hover:shadow">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ isset($travelOrder) && $travelOrder->id ? 'Update Travel Request' : 'Submit Travel Request' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
