<x-admin-layout>
        @section('page_title', 'MPIS | Subunits')
    <x-slot name="header">
        <!-- CSRF Token for AJAX requests -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <div class="flex justify-center items-center">
            <!-- Centered Content -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full" style="max-width: 1200px;">
                <!-- Heading and Search on same row - always -->
                <div class="flex items-center justify-between gap-3 flex-1 w-full sm:w-auto">
                    <!-- Left: Heading -->
                    <h2 class="font-bold flex items-center heading-style" style="color: #1e6031; font-size: 1.5rem; height: 32px; margin-top: 10px;">
                        <svg class="mr-2 heading-icon" style="width: 24px; height: 24px; color: #1e6031;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Subunit Management
                    </h2>

                    <!-- Right: Search Bar -->
                    <form id="searchForm" method="GET" action="{{ route('admin.subunits.index') }}">
                        <div class="flex">
                            <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                            <input type="text" name="search" class="form-input" placeholder="Search subunits..." 
                                   value="{{ request('search') }}"
                                   style="height: 32px; width: 250px; font-size: 0.85rem; border: 1px solid #1e6031; border-radius: 0.375rem 0 0 0.375rem; padding: 0 12px;">
                            <button type="submit" 
                                    style="background-color: #1e6031; color: #ffffff; border: 1px solid #1e6031; height: 32px; width: 40px; border-radius: 0 0.375rem 0.375rem 0; cursor:pointer; display: flex; align-items: center; justify-content: center;">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div> 
                    </form>
                </div>
                
                <!-- Add New Subunit Button on separate line for mobile, aligned right -->
                <div class="w-full sm:w-auto flex sm:block justify-end sm:justify-start">
                    <button type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#addSubunitModal"
                        class="inline-flex items-center px-3 py-1 bg-[#1e6031] border border-[#1e6031] rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#103c1e] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150 whitespace-nowrap" style="height: 32px;">
                        <svg class="mr-1" style="width: 10px; height: 10px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add New Subunit
                    </button>
                </div>
            </div>
        </div>
    </x-slot>

<div class="-mt-5">
    <div class="flex justify-center">
        <div style="width: 100%; max-width: 1200px;">
                <!-- Filter -->
               <div class="flex justify-between items-center mb-0" style="display:flex; align-items:center; justify-content:space-between; padding:4px 0; border-bottom:1px solid #1e6031; margin-bottom:8px;">
                    <div style="display:flex; align-items:center;">
                        <label for="statusFilter" style="margin-right: 10px; font-weight: normal; font-size: 0.875rem;">Filter by Status: </label>
                        <select id="statusFilter" name="status" class="border border-gray-300 rounded-md px-2 py-1 text-sm"
                            style="width:150px; padding:6px 8px; font-size:13px; border:1px solid #ccc; border-radius:5px;">
                            <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>All Status</option>
                            <option value="active" {{ request('status', 'all') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status', 'all') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>

<!-- Subunits Table -->
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead style="background-color: #1e6031; color: white;">
            <tr>
                <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; width: 5%;">No.</th>
                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; width: 35%;">Subunit Name</th>
                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; width: 20%;">Abbreviation</th>
                <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; width: 20%;">Status</th>
                <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; width: 20%;">Actions</th>
            </tr>
        </thead>
        <tbody id="subunits-table-body">
            @include('admin.subunits.partials.table-body')
        </tbody>
    </table>
</div>

                <!-- Pagination -->
                <div class="flex justify-between items-center mt-4" id="pagination-container" style="display: flex; flex-direction: row; align-items: center; justify-content: space-between; margin-top: 16px; gap: 8px;">
                    <div class="text-sm text-gray-600" id="pagination-info" style="white-space: nowrap;">
                        @php
                            $statusFilter = request('status');
                            $filterText = '';
                            if ($statusFilter) {
                                $filterText = ' (' . ucfirst($statusFilter) . ')';
                            }
                        @endphp
                        Showing {{ $subunits->firstItem() ?? 0 }} to {{ $subunits->lastItem() ?? 0 }} of {{ $subunits->total() }} results{!! $filterText !!}
                    </div>
                    <div class="flex items-center space-x-2">
                        <!-- Custom Pagination - Copied from vehicles page -->
                        <nav>
                            <ul class="pagination">
                                <li class="page-item {{ $subunits->currentPage() <= 1 ? 'disabled' : '' }}">
                                    <a class="page-link {{ $subunits->currentPage() <= 1 ? 'disabled-link' : '' }}" 
                                       href="{{ $subunits->currentPage() <= 1 ? 'javascript:void(0)' : $subunits->previousPageUrl() }}"
                                       {{ $subunits->currentPage() <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' }}>Prev</a>
                                </li>
                                <li class="page-item active">
                                    <span class="page-link">{{ $subunits->currentPage() }}</span>
                                </li>
                                <li class="page-item {{ $subunits->currentPage() >= $subunits->lastPage() ? 'disabled' : '' }}">
                                    <a class="page-link {{ $subunits->currentPage() >= $subunits->lastPage() ? 'disabled-link' : '' }}" 
                                       href="{{ $subunits->currentPage() >= $subunits->lastPage() ? 'javascript:void(0)' : $subunits->nextPageUrl() }}"
                                       {{ $subunits->currentPage() >= $subunits->lastPage() ? 'aria-disabled="true" tabindex="-1"' : '' }}>Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- ADD SUBUNIT MODAL -->
<div class="modal fade" id="addSubunitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- HEADER -->
            <div class="modal-header" style="background-color:#1e6031; color:white;">
                <h5 class="modal-title fw-bold">Add New Subunit</h5>
            </div>
            
            <!-- FORM -->
            <form method="POST" id="addSubunitForm">
                @csrf
                <div class="modal-body px-3 py-2">
                    <!-- Unit -->
                    <div class="mb-2">
                        <label for="unit_id" class="small fw-semibold text-success">Unit <span class="text-danger">*</span></label>
                        <select name="unit_id" id="unit_id" class="form-control form-control-sm border-success" required>
                            <option value="">Select Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->unit_name }} ({{ $unit->division->division_name }} - {{ $unit->division->office->office_abbr }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Name -->
                    <div class="mb-2">
                        <label for="subunit_name" class="small fw-semibold text-success">Subunit Name <span class="text-danger">*</span></label>
                        <input type="text" name="subunit_name" id="subunit_name" class="form-control form-control-sm border-success" placeholder="Enter subunit name" required>
                    </div>
                    
                    <!-- Abbreviation -->
                    <div class="mb-2">
                        <label for="subunit_abbr" class="small fw-semibold text-success">Abbreviation <span class="text-danger">*</span></label>
                        <input type="text" name="subunit_abbr" id="subunit_abbr" class="form-control form-control-sm border-success" placeholder="Enter abbreviation (e.g., HRD, FMD)" required>
                    </div>
                    
                    
                </div>
                
                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addSubunitCancelButton">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">Create Subunit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT SUBUNIT MODAL -->
<div class="modal fade" id="editSubunitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- HEADER -->
            <div class="modal-header" style="background-color:#1e6031; color:white;">
                <h5 class="modal-title fw-bold">Edit Subunit</h5>
            </div>
            
            <!-- FORM -->
            <form method="POST" id="editSubunitForm">
                @csrf
                @method('PUT')

                <div class="modal-body px-3 py-2">
                    <!-- Unit -->
                    <div class="mb-2">
                        <label for="edit_unit_id" class="small fw-semibold text-success">Unit <span class="text-danger">*</span></label>
                        <select name="unit_id" id="edit_unit_id" class="form-control form-control-sm border-success" required>
                            <option value="">Select Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->unit_name }} ({{ $unit->division->division_name }} - {{ $unit->division->office->office_abbr }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Name -->
                    <div class="mb-2">
                        <label for="edit_subunit_name" class="small fw-semibold text-success">Subunit Name <span class="text-danger">*</span></label>
                        <input type="text" name="subunit_name" id="edit_subunit_name" class="form-control form-control-sm border-success" placeholder="Enter subunit name" required>
                    </div>
                    
                    <!-- Abbreviation -->
                    <div class="mb-2">
                        <label for="edit_subunit_abbreviation" class="small fw-semibold text-success">Abbreviation <span class="text-danger">*</span></label>
                        <input type="text" name="subunit_abbr" id="edit_subunit_abbreviation" class="form-control form-control-sm border-success" placeholder="Enter abbreviation (e.g., HRD, FMD)" required>
                    </div>
                    
                    <!-- Status -->
                    <div class="mb-2">
                        <label for="edit_subunit_status" class="small fw-semibold text-success">Status <span class="text-danger">*</span></label>
                        <select name="subunit_isactive" id="edit_subunit_status" class="form-control form-control-sm border-success" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="editSubunitCancelButton">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">Update Subunit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
/* Remove underlines from sidebar links */
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

/* Action buttons styling to match vehicles page */
.action-buttons .btn {
    font-size: 9px;
    padding: 2px 4px;
    line-height: 1;
    height: 24px;
    min-width: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    border-radius: 3px;
    box-sizing: border-box;
}

.action-buttons .btn i,
.action-buttons .btn svg {
    font-size: 12px;
    margin-right: 2px;
    width: 12px;
    height: 12px;
}

/* Colors for action buttons */
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

.action-buttons .delete-btn {
    color: #dc3545 !important;
    border: 1px solid #dc3545 !important;
    background-color: transparent !important;
}

.action-buttons .delete-btn:hover {
    background-color: #dc3545 !important;
    color: #fff !important;
    border-color: #dc3545 !important;
}

/* Pagination styling - Simplified version */
.pagination {
    display: flex;
    justify-content: flex-end;
    list-style: none;
}

.pagination .page-link {
    color: #1e6031 !important;
    padding: 0.15rem 0.4rem;
    font-size: 0.8125rem;
    display: block;
    text-decoration: none;
    background-color: #fff !important;
    border: 1px solid #1e6031;
    border-radius: 0.25rem;
}

.page-item.disabled .page-link {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #6c757d;
    cursor: not-allowed;
}

.page-item:not(.disabled) .page-link:hover {
    background-color: #1e6031 !important;
    color: white !important;
}

.page-link.disabled-link {
    background-color: #e9ecef !important;
    border-color: #dee2e6 !important;
    color: #6c757d !important;
    cursor: not-allowed !important;
    pointer-events: none;
}

.pagination .active .page-link {
    background-color: #1e6031 !important;
    color: white !important;
    font-weight: bold;
}

.page-item.disabled .page-link {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #6c757d;
}

.page-item {
    margin: 0 2px;
}

/* Responsive styles for all screen sizes */

/* Very small phones (iPhone SE and similar - 320px to 375px) */
@media (max-width: 375px) {
    .heading-style {
        font-size: 0.85rem !important;
        white-space: nowrap !important;
    }
    
    .heading-icon {
        width: 16px !important;
        height: 16px !important;
    }
    
    /* Remove row gaps - compact layout */
    .flex.flex-col.sm\:flex-row {
        gap: 0.25rem !important;
    }
    
    .flex.items-center.justify-between.gap-3 {
        gap: 0.25rem !important;
    }
    
    form#searchForm input[name="search"] {
        width: 120px !important;
        font-size: 0.65rem !important;
        padding: 0 6px !important;
    }
    
    form#searchForm button[type="submit"] {
        width: 32px !important;
    }
    
    form#searchForm button[type="submit"] svg {
        width: 11px !important;
        height: 11px !important;
    }
    
    button[data-bs-target="#addSubunitModal"] {
        font-size: 0.6rem !important;
        padding-left: 0.4rem !important;
        padding-right: 0.4rem !important;
    }
    
    button[data-bs-target="#addSubunitModal"] svg {
        width: 8px !important;
        height: 8px !important;
    }
}

/* Small phones (375px to 414px) */
@media (min-width: 376px) and (max-width: 414px) {
    .heading-style {
        font-size: 0.9rem !important;
        white-space: nowrap !important;
    }
    
    .heading-icon {
        width: 18px !important;
        height: 18px !important;
    }
    
    /* Remove row gaps - compact layout */
    .flex.flex-col.sm\:flex-row {
        gap: 0.25rem !important;
    }
    
    .flex.items-center.justify-between.gap-3 {
        gap: 0.25rem !important;
    }
    
    form#searchForm input[name="search"] {
        width: 130px !important;
        font-size: 0.7rem !important;
        padding: 0 8px !important;
    }
    
    button[data-bs-target="#addSubunitModal"] {
        font-size: 0.65rem !important;
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
}

/* Medium phones (414px to 540px) */
@media (min-width: 415px) and (max-width: 540px) {
    .heading-style {
        font-size: 1rem !important;
        white-space: nowrap !important;
    }
    
    .heading-icon {
        width: 20px !important;
        height: 20px !important;
    }
    
    form#searchForm input[name="search"] {
        width: 150px !important;
        font-size: 0.75rem !important;
    }
    
    button[data-bs-target="#addSubunitModal"] {
        font-size: 0.7rem !important;
    }
}

/* Large phones and small tablets (540px to 768px) */
@media (min-width: 541px) and (max-width: 768px) {
    .heading-style {
        font-size: 1.1rem !important;
    }
    
    form#searchForm input[name="search"] {
        width: 170px !important;
        font-size: 0.775rem !important;
    }
    
    button[data-bs-target="#addSubunitModal"] {
        font-size: 0.75rem !important;
    }
}

/* Tablets and larger mobile devices (768px and up) */
@media (min-width: 769px) {
    .heading-style {
        font-size: 1.5rem !important;
    }
    
    form#searchForm input[name="search"] {
        width: 250px !important;
        font-size: 0.85rem !important;
    }
    
    button[data-bs-target="#addSubunitModal"] {
        font-size: 0.875rem !important;
    }
}
</style>

<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ---------- Status Filter Handler ----------
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            // Update the URL with the new status filter value
            const url = new URL(window.location);
            if (this.value === 'all') {
                url.searchParams.delete('status');
            } else {
                url.searchParams.set('status', this.value);
            }
            // Preserve search term when changing filters
            const searchTerm = document.querySelector('input[name="search"]')?.value;
            if (searchTerm) {
                url.searchParams.set('search', searchTerm);
            } else {
                url.searchParams.delete('search');
            }
            // Reset to first page when changing filters
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    }

    // Update search form to include current status when submitted
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            const statusInput = this.querySelector('input[name="status"]');
            const statusFilter = document.getElementById('statusFilter');
            if (statusInput && statusFilter) {
                statusInput.value = statusFilter.value;
            }
        });
    }

    // ---------- Common fetch headers ----------
    const getFetchHeaders = () => ({
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
    });

    // Store for Add Modal - persists data when closed via backdrop/clicking outside
    let addModalStoredData = {};
    
    // Store for Edit Modal - stores original data and current modified data separately
    let editModalOriginalData = {};
    let editModalCurrentData = {};


    
    // Also cleanup on modal hidden event
    const addModal = document.getElementById('addSubunitModal');
    const editModal = document.getElementById('editSubunitModal');
    
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', function() {
            // Let Bootstrap handle cleanup automatically
        });
    }
    
    if (editModal) {
        editModal.addEventListener('hidden.bs.modal', function() {
            // Let Bootstrap handle cleanup automatically
        });
    }

    // --- ADD MODAL SPECIFIC LOGIC ---
    const addForm = document.getElementById('addSubunitForm');
    const addCancelBtn = document.getElementById('addSubunitCancelButton');
    
    if (addModal && addForm) {
        // Handle opening the add modal
        addModal.addEventListener('show.bs.modal', function(event) {
            // Restore previously entered data if it exists
            if (addModalStoredData.subunit_name || addModalStoredData.subunit_abbr || addModalStoredData.unit_id) {
                document.querySelector('[name="subunit_name"]').value = addModalStoredData.subunit_name || '';
                document.querySelector('[name="subunit_abbr"]').value = addModalStoredData.subunit_abbr || '';
                document.querySelector('[name="unit_id"]').value = addModalStoredData.unit_id || '';
            } else {
                // If no stored data, clear the form
                addForm.reset();
                // Ensure the form is completely cleared
                document.querySelector('[name="subunit_name"]').value = '';
                document.querySelector('[name="subunit_abbr"]').value = '';
                document.querySelector('[name="unit_id"]').value = '';
            }
            
            // Ensure proper backdrop - Bootstrap should handle this automatically
            // but we'll make sure the body class is added
            document.body.classList.add('modal-open');
        });

        // Handle hiding the add modal
        addModal.addEventListener('hide.bs.modal', function() {
            // Store current form data for future visits (unless closed via cancel button)
            if (!this.closingViaCancel) {
                addModalStoredData = {
                    subunit_name: document.querySelector('[name="subunit_name"]').value,
                    subunit_abbr: document.querySelector('[name="subunit_abbr"]').value,
                    unit_id: document.querySelector('[name="unit_id"]').value
                };
            }
            
            // Reset the cancel flag
            this.closingViaCancel = false;
        });

        // Handle backdrop click (clicking outside modal) for add modal
        addModal.addEventListener('click', function(event) {
            if (event.target === addModal) {
                // Store current data when clicking outside
                addModalStoredData = {
                    subunit_name: document.querySelector('[name="subunit_name"]').value,
                    subunit_abbr: document.querySelector('[name="subunit_abbr"]').value,
                    unit_id: document.querySelector('[name="unit_id"]').value
                };
            }
        });

        // Cancel button for add modal - clears stored data
        if (addCancelBtn) {
            addCancelBtn.addEventListener('click', function() {
                // Mark that modal is closing via cancel
                addModal.closingViaCancel = true;
                
                // Clear stored data
                addModalStoredData = {};
                
                // Clear the form
                addForm.reset();
                // Ensure the form is completely cleared
                document.querySelector('[name="subunit_name"]').value = '';
                document.querySelector('[name="subunit_abbr"]').value = '';
                document.querySelector('[name="unit_id"]').value = '';
                addForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                addForm.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
                
                // Hide the modal
                bootstrap.Modal.getInstance(addModal)?.hide();
            });
        }
    }

    // --- EDIT MODAL SPECIFIC LOGIC ---
    const editForm = document.getElementById('editSubunitForm');
    const editCancelBtn = document.getElementById('editSubunitCancelButton');
    
    if (editModal && editForm) {
        // Handle opening the edit modal
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const abbr = button.getAttribute('data-abbr');
            const unitId = button.getAttribute('data-unit-id');
            const status = button.getAttribute('data-status');

            // Store the original data from the database when opening
            editModalOriginalData = {
                subunit_name: name,
                subunit_abbr: abbr,
                unit_id: unitId,
                subunit_isactive: status
            };
            
            // Load data when opening the modal - use current data if available for the same record, otherwise original
            // Check if we're editing the same subunit as before
            if (this.currentEditingId === id) {
                // Same record - use current data if available
                document.getElementById('edit_subunit_name').value = editModalCurrentData.subunit_name || name;
                document.getElementById('edit_subunit_abbreviation').value = editModalCurrentData.subunit_abbr || abbr;
                document.getElementById('edit_unit_id').value = editModalCurrentData.unit_id || unitId;
                document.getElementById('edit_subunit_status').value = editModalCurrentData.subunit_isactive || status;
            } else {
                // Different record - always use fresh data
                document.getElementById('edit_subunit_name').value = name;
                document.getElementById('edit_subunit_abbreviation').value = abbr;
                document.getElementById('edit_unit_id').value = unitId;
                document.getElementById('edit_subunit_status').value = status;
                
                // Reset current data to original for the new record
                editModalCurrentData = {...editModalOriginalData};
            }
            
            // Keep track of which record we're currently editing
            this.currentEditingId = id;
            
            editForm.action = `/admin/subunits/${id}`;
            
            // Reset the cancel flag
            this.closingViaCancel = false;
            
            // Ensure proper backdrop - Bootstrap should handle this automatically
            // but we'll make sure the body class is added
            document.body.classList.add('modal-open');
        });

        // Handle hiding the edit modal
        editModal.addEventListener('hide.bs.modal', function() {
            // If closing via cancel button, restore original values to current data
            if (this.closingViaCancel) {
                editModalCurrentData = {...editModalOriginalData};
            }
            // If not closing via cancel (clicked outside), current data remains as is
            
            // Reset the cancel flag
            this.closingViaCancel = false;
        });

        // Handle backdrop click (clicking outside modal) for edit modal
        editModal.addEventListener('click', function(event) {
            if (event.target === editModal) {
                // When clicking outside, save current form values to current data
                editModalCurrentData = {
                    subunit_name: document.getElementById('edit_subunit_name').value,
                    subunit_abbr: document.getElementById('edit_subunit_abbreviation').value,
                    unit_id: document.getElementById('edit_unit_id').value,
                    subunit_isactive: document.getElementById('edit_subunit_status').value
                };
            }
        });

        // Cancel button for edit modal - restores original data
        if (editCancelBtn) {
            editCancelBtn.addEventListener('click', function() {
                // Mark that modal is closing via cancel
                editModal.closingViaCancel = true;
                
                // Hide the modal (the hide event will handle resetting form values)
                bootstrap.Modal.getInstance(editModal)?.hide();
            });
        }
    }

    // --- FORM SUBMISSION HANDLERS ---
    if (addForm) {
        addForm.action = '{{ route("admin.subunits.store") }}';
        addForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Show loading message
            Swal.fire({
                title: 'Creating Subunit...',
                text: 'Please wait while we process your request',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
            
            // Record start time
            const startTime = Date.now();
            
            try {
                const formData = new FormData(this);
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: getFetchHeaders()
                });

                const data = await response.json();

                if (!response.ok || !data.success) throw new Error(data.message || 'Unknown error');

                // Calculate remaining time to ensure 2 seconds have passed
                const elapsed = Date.now() - startTime;
                const remainingTime = Math.max(0, 2000 - elapsed); // Ensure at least 2 seconds total
                
                // Wait for remaining time if needed
                if (remainingTime > 0) {
                    await new Promise(resolve => setTimeout(resolve, remainingTime));
                }
                
                // Close the loading toast
                Swal.close();
                
                // Show success message and refresh immediately
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#1e6031',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Close the modal first
                    const modalElement = bootstrap.Modal.getInstance(document.getElementById('addSubunitModal'));
                    if (modalElement) {
                        modalElement.hide();
                    }
                    // Then reload the page
                    location.reload();
                });

            } catch (err) {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + err.message,
                    icon: 'error',
                    confirmButtonColor: '#1e6031'
                });
            }
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Show loading message
            Swal.fire({
                title: 'Updating Subunit...',
                text: 'Please wait while we process your request',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
            
            // Record start time
            const startTime = Date.now();
            
            try {
                const formData = new FormData(this);
                formData.append('_method', 'PUT'); // Required for Laravel update

                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: getFetchHeaders()
                });

                const data = await response.json();

                if (!response.ok || !data.success) throw new Error(data.message || 'Unknown error');

                // Calculate remaining time to ensure 2 seconds have passed
                const elapsed = Date.now() - startTime;
                const remainingTime = Math.max(0, 2000 - elapsed); // Ensure at least 2 seconds total
                
                // Wait for remaining time if needed
                if (remainingTime > 0) {
                    await new Promise(resolve => setTimeout(resolve, remainingTime));
                }
                
                // Close the loading toast
                Swal.close();
                
                // Show success message and refresh immediately
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#1e6031',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Close the modal first
                    const modalElement = bootstrap.Modal.getInstance(document.getElementById('editSubunitModal'));
                    if (modalElement) {
                        modalElement.hide();
                    }
                    // Then reload the page
                    location.reload();
                });

            } catch (err) {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + err.message,
                    icon: 'error',
                    confirmButtonColor: '#1e6031'
                });
            }
        });
    }

});
</script>
</x-admin-layout>
