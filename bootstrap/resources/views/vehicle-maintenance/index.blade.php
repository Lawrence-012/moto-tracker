@extends('layouts.motorpool-admin')

@section('content')
<style>
    .action-buttons .btn {
        font-size: 11px;
        padding: 3px 8px;
        line-height: 1;
        height: 26px;
        min-width: 56px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 3px;
        border-radius: 4px;
    }
    .action-buttons .btn i { font-size: 10px; margin-right: 2px; }
    .action-buttons .view-btn {
        color: #0d6efd !important;
        border: 1px solid #0d6efd !important;
        background-color: transparent !important;
    }
    .action-buttons .view-btn:hover { background-color: #0d6efd !important; color: #fff !important; }
    .action-buttons .excel-btn {
        color: #1e6031 !important;
        border: 1px solid #1e6031 !important;
        background-color: transparent !important;
    }
    .action-buttons .excel-btn:hover { background-color: #1e6031 !important; color: #fff !important; }
    .action-buttons .edit-btn {
        color: #e0a70d !important;
        border: 1px solid #e0a70d !important;
        background-color: transparent !important;
    }
    .action-buttons .edit-btn:hover { background-color: #e0a70d !important; color: #fff !important; }
    .action-buttons .archive-btn {
        color: #dc3545 !important;
        border: 1px solid #dc3545 !important;
        background-color: transparent !important;
    }
    .action-buttons .archive-btn:hover { background-color: #dc3545 !important; color: #fff !important; }

    .pagination { display: flex; justify-content: flex-end; }
</style>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="main">

            <!-- Heading -->
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-bold" style="color: #1e6031; font-size: 1.75rem;">Vehicle Maintenance</h2>
                <a href="{{ route('vehicle-maintenance.create') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-[#1e6031] border border-[#1e6031] rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#103c1e] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-plus mr-1 text-xs"></i> Add New Maintenance
                </a>
            </div>

            <!-- Filters -->
            <div class="mb-4" style="display:flex; align-items:end; gap:1rem; flex-wrap:wrap; padding:10px; border-bottom:1px solid #1e6031;">
                <div>
                    <label for="search" class="block text-xs font-semibold text-gray-600 mb-1 uppercase">Search</label>
                    <div class="relative">
                        <input type="text"
                               name="search"
                               id="search"
                               value="{{ $search }}"
                               placeholder="Search by type, office, place, etc..."
                               style="height: 32px; width: 220px; font-size: 0.85rem; border: 1px solid #1e6031; border-radius: 0.375rem; padding: 0 10px;">
                    </div>
                </div>

                <div>
                    <label for="vehicle_id" class="block text-xs font-semibold text-gray-600 mb-1 uppercase">Vehicle</label>
                    <select name="vehicle_id" id="vehicle_id"
                            style="height: 32px; width: 200px; font-size: 0.85rem; border: 1px solid #1e6031; border-radius: 0.375rem; padding: 0 8px;">
                        <option value="">All Vehicles</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" {{ $vehicleId == $vehicle->id ? 'selected' : '' }}>
                                {{ $vehicle->plate_number }} - {{ $vehicle->model }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold text-gray-600 mb-1 uppercase">Status</label>
                    <select name="status" id="status"
                            style="height: 32px; width: 160px; font-size: 0.85rem; border: 1px solid #1e6031; border-radius: 0.375rem; padding: 0 8px;">
                        <option value="">All Status</option>
                        <option value="Pending" {{ $status == 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Need Maintenance" {{ $status == 'Need Maintenance' ? 'selected' : '' }}>Need Maintenance</option>
                        <option value="Under Maintenance" {{ $status == 'Under Maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                        <option value="Ongoing" {{ $status == 'Ongoing' ? 'selected' : '' }}>Ongoing</option>
                        <option value="Completed" {{ $status == 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="Archived" {{ $status == 'Archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>

                <div>
                    <button type="button" id="apply-filters"
                            class="inline-flex items-center px-4 py-1.5 bg-[#1e6031] border border-[#1e6031] rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-[#164f2a] transition ease-in-out duration-150"
                            style="height: 32px;">
                        Apply Filters
                    </button>
                </div>
            </div>

            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-4 rounded-md p-3" style="background-color:#DFF0D8; border:1px solid #1e6031;">
                    <p class="text-sm font-medium" style="color:#1e6031;">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Maintenance Records Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead style="background-color: #1e6031; color: white;">
                        <tr>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Vehicle</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Odometer</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Date Started</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Type</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Office/Unit</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Place</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Nature of Work</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Mechanic</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Status</th>
                            <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($maintenanceRecords as $index => $record)
                            <tr class="{{ $index % 2 == 1 ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="font-semibold" style="font-size: 0.9rem;">{{ $record->vehicle->plate_number ?? 'N/A' }}</span>
                                    <div class="text-xs text-gray-400">{{ $record->vehicle->model ?? '' }}</div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm">
                                    {{ $record->odometer_reading ? $record->odometer_reading . ' km' : 'N/A' }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm">
                                    {{ $record->date_started->format('M d, Y') }}
                                </td>
                                <td class="px-3 py-2 text-sm">
                                    {{ $record->make_or_type }}
                                </td>
                                <td class="px-3 py-2 text-sm">
                                    {{ $record->person_office_unit }}
                                </td>
                                <td class="px-3 py-2 text-sm">
                                    {{ $record->place }}
                                </td>
                                <td class="px-3 py-2 text-sm max-w-xs truncate" title="{{ $record->nature_of_work }}">
                                    {{ Str::limit($record->nature_of_work, 30) }}
                                </td>
                                <td class="px-3 py-2 text-sm">
                                    {{ implode(', ', array_filter($record->mechanic_assigned)) ?: 'N/A' }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        @if($record->status === 'Pending') bg-yellow-100 text-yellow-800
                                        @elseif($record->status === 'Need Maintenance') bg-red-100 text-red-800
                                        @elseif($record->status === 'Under Maintenance') bg-blue-100 text-blue-800
                                        @elseif($record->status === 'Ongoing') bg-blue-100 text-blue-800
                                        @elseif($record->status === 'Completed') bg-green-100 text-green-800
                                        @elseif($record->status === 'Archived') bg-gray-200 text-gray-700
                                        @endif">
                                        {{ $record->status }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-center">
                                    <div class="action-buttons flex justify-center space-x-1">
                                        <a href="{{ route('vehicle-maintenance.show', $record) }}" class="btn view-btn border inline-flex items-center justify-center" title="View">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button type="button" onclick="viewMaintenanceExcelFromIndex('{{ route('vehicle-maintenance.pdf', $record) }}')" class="btn excel-btn border inline-flex items-center justify-center" title="Download Excel">
                                            <i class="fas fa-file-excel"></i> Excel
                                        </button>
                                        <a href="{{ route('vehicle-maintenance.edit', $record) }}" class="btn edit-btn border inline-flex items-center justify-center" title="Edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form action="{{ route('vehicle-maintenance.destroy', $record) }}" method="POST" class="inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn archive-btn border delete-btn inline-flex items-center justify-center" title="Archive">
                                                <i class="fas fa-archive"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-3 py-6 text-center text-sm text-gray-500">
                                    No vehicle maintenance records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $maintenanceRecords->appends(['search' => $search, 'status' => $status, 'vehicle_id' => $vehicleId])->links() }}
            </div>

        </div>
    </div>
</div>

<script>
// Excel view function - direct download with confirmation for index page
function viewMaintenanceExcelFromIndex(excelUrl) {
    Swal.fire({
        title: 'Download Excel File?',
        text: 'You are about to download the Excel file. Do you want to proceed?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e6031',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, proceed!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.open(excelUrl, '_blank');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const applyFiltersBtn = document.getElementById('apply-filters');
    const searchInput = document.getElementById('search');
    const vehicleSelect = document.getElementById('vehicle_id');
    const statusSelect = document.getElementById('status');

    function applyFilters() {
        const search = searchInput.value;
        const vehicleId = vehicleSelect.value;
        const status = statusSelect.value;

        let url = new URL('{{ route("vehicle-maintenance.index") }}', window.location.origin);

        if (search) {
            url.searchParams.set('search', search);
        }

        if (vehicleId) {
            url.searchParams.set('vehicle_id', vehicleId);
        }

        if (status) {
            url.searchParams.set('status', status);
        }

        window.location.href = url.toString();
    }

    applyFiltersBtn.addEventListener('click', applyFilters);

    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });

    vehicleSelect.addEventListener('change', applyFilters);
    statusSelect.addEventListener('change', applyFilters);

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
});
</script>
@endsection
