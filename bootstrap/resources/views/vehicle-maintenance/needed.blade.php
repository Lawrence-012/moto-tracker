@extends('layouts.motorpool-admin')

@section('content')
<style>
    /* Remove (X) button for notifications: a real remove, with Restore to undo */
    .notif-x {
        width: 28px; height: 28px; border-radius: 999px; border: 0; background: transparent;
        color: #9ca3af; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
        transition: background-color .15s ease, color .15s ease;
    }
    .notif-x:hover { background-color: #fee2e2; color: #b91c1c; }
    .notif-x:disabled { opacity: .5; cursor: wait; }
    .restore-btn { color: #1e6031 !important; border: 1px solid #1e6031 !important; background-color: transparent !important; }
    .restore-btn:hover { background-color: #1e6031 !important; color: #fff !important; }
    tr.is-removed td { opacity: .55; }
    /* Stat tiles - matches the dashboard-card pattern used on the Motorpool Admin dashboard */
    .mn-card {
        flex: 1;
        min-width: 200px;
        display: flex;
        align-items: center;
        padding: 15px;
        background: #DFF0D8;
        border-radius: 10px;
        border: 2px solid #1e6031;
        box-shadow: 0 4px 8px rgba(30, 96, 49, 0.15);
        transition: all 0.25s ease;
    }
    .mn-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(30, 96, 49, 0.20);
    }
    .mn-card .icon-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background-color: #A8DFAA;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .mn-card .icon-circle i { font-size: 20px; color: #1e6031; }
    .mn-card .mn-count { font-size: 22px; font-weight: 700; margin: 0; color: #1e6031; }
    .mn-card .mn-label { font-size: 12px; font-weight: 600; color: #1e6031; text-transform: uppercase; letter-spacing: 0.03em; }

    /* Action buttons - same family as vehicles/index.blade.php */
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
    .action-buttons .start-btn {
        color: #1e6031 !important;
        border: 1px solid #1e6031 !important;
        background-color: transparent !important;
    }
    .action-buttons .start-btn:hover { background-color: #1e6031 !important; color: #fff !important; }
    .action-buttons .log-btn {
        color: #e0a70d !important;
        border: 1px solid #e0a70d !important;
        background-color: transparent !important;
    }
    .action-buttons .log-btn:hover { background-color: #e0a70d !important; color: #fff !important; }
</style>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="main">

            <!-- Heading -->
            <div class="flex justify-between items-center mb-1">
                <h2 class="font-bold" style="color: #1e6031; font-size: 1.75rem;">Maintenance Needed</h2>
                <a href="{{ route('vehicle-maintenance.index') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-white border border-[#1e6031] rounded-md font-semibold text-xs text-[#1e6031] uppercase tracking-wider hover:bg-[#1e6031] hover:text-white transition ease-in-out duration-150">
                    <i class="fas fa-list mr-1 text-xs"></i> All Maintenance Records
                </a>
            </div>
            <p class="text-sm text-gray-500 mb-4">Everything across the fleet that currently needs attention &mdash; oil-change-due vehicles and open work orders.</p>

            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-4 rounded-md p-3" style="background-color:#DFF0D8; border:1px solid #1e6031;">
                    <p class="text-sm font-medium" style="color:#1e6031;">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Stat Tiles -->
            <div class="flex flex-wrap gap-4 mb-6">
                <div class="mn-card">
                    <div class="icon-circle"><i class="fas fa-oil-can"></i></div>
                    <div>
                        <p class="mn-count">{{ $oilChangeVehicles->count() }}</p>
                        <span class="mn-label">Oil Change Due</span>
                    </div>
                </div>
                <div class="mn-card">
                    <div class="icon-circle"><i class="fas fa-wrench"></i></div>
                    <div>
                        <p class="mn-count">{{ $maintenanceRecords->count() }}</p>
                        <span class="mn-label">Open Work Orders</span>
                    </div>
                </div>
            </div>

            <!-- Oil Change Due -->
            <div class="mb-3" style="display:flex; align-items:center; padding:5px; border-bottom:1px solid #1e6031;">
                <i class="fas fa-oil-can mr-2" style="color:#1e6031;"></i>
                <h3 class="font-bold text-sm uppercase tracking-wide" style="color:#1e6031;">Vehicles Due for Oil Change</h3>
            </div>
            <div class="overflow-x-auto mb-8">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead style="background-color: #1e6031; color: white;">
                        <tr>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Vehicle</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Current Mileage</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Last Maintenance</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">KM Since Service</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Status</th>
                            <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($oilChangeVehicles as $index => $vehicle)
                            <tr class="{{ $index % 2 == 1 ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="font-semibold" style="font-size: 0.9rem;">{{ $vehicle->plate_number }}</span>
                                    <div class="text-xs text-gray-400">{{ $vehicle->model }}</div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm">{{ number_format($vehicle->mileage) }} km</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm">{{ number_format($vehicle->last_maintenance_mileage ?? 0) }} km</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm">{{ number_format($vehicle->getKilometersSinceLastMaintenance()) }} km</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if(in_array($vehicle->id, $dismissedVehicleIds))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Dismissed by you</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border-2 border-orange-300">
                                            <i class="fas fa-oil-can mr-1"></i>Needs Oil Change
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-center">
                                    <div class="action-buttons flex justify-center space-x-1">
                                        <a href="{{ route('vehicles.maintenance', $vehicle->id) }}" class="btn view-btn border inline-flex items-center justify-center" title="View Vehicle">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="{{ route('vehicle-maintenance.create') }}?vehicle_id={{ $vehicle->id }}" class="btn log-btn border inline-flex items-center justify-center" title="Log Maintenance">
                                            <i class="fas fa-plus"></i> Log
                                        </a>
                                        @if(in_array($vehicle->id, $dismissedVehicleIds))
                                            <button type="button" class="btn restore-btn border inline-flex items-center justify-center" data-restore-oil="{{ $vehicle->id }}" title="Show this alert again">
                                                <i class="fas fa-rotate-left"></i> Restore
                                            </button>
                                        @else
                                            <button type="button" class="notif-x" data-dismiss-oil="{{ $vehicle->id }}" title="Remove from notifications" aria-label="Remove from notifications">
                                                <i class="fas fa-xmark"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">
                                    No vehicles currently due for oil change.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Open Maintenance Work Orders -->
            <div class="mb-3" style="display:flex; align-items:center; padding:5px; border-bottom:1px solid #1e6031;">
                <i class="fas fa-wrench mr-2" style="color:#1e6031;"></i>
                <h3 class="font-bold text-sm uppercase tracking-wide" style="color:#1e6031;">Open Maintenance Work Orders</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead style="background-color: #1e6031; color: white;">
                        <tr>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Vehicle</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Date Started</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Nature of Work</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Place</th>
                            <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Status</th>
                            <th style="padding: 10px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($maintenanceRecords as $index => $record)
                            <tr class="{{ $index % 2 == 1 ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0] {{ in_array($record->id, $dismissedMaintenanceIds) ? 'is-removed' : '' }}">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="font-semibold" style="font-size: 0.9rem;">{{ $record->vehicle->plate_number ?? 'N/A' }}</span>
                                    <div class="text-xs text-gray-400">{{ $record->vehicle->model ?? '' }}</div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $record->date_started->format('M d, Y') }}</td>
                                <td class="px-3 py-2 text-sm max-w-xs truncate" title="{{ $record->nature_of_work }}">{{ Str::limit($record->nature_of_work, 40) }}</td>
                                <td class="px-3 py-2 text-sm">{{ $record->place }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        @if($record->status === 'Need Maintenance') bg-red-100 text-red-800
                                        @else bg-blue-100 text-blue-800
                                        @endif">
                                        {{ $record->status }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-center">
                                    <div class="action-buttons flex justify-center space-x-1">
                                        <a href="{{ route('vehicle-maintenance.show', $record) }}" class="btn view-btn border inline-flex items-center justify-center" title="View">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        @if($record->status === 'Need Maintenance')
                                            <form action="{{ route('vehicle-maintenance.start', $record) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="btn start-btn border inline-flex items-center justify-center" title="Start Maintenance">
                                                    <i class="fas fa-play"></i> Start
                                                </button>
                                            </form>
                                        @endif
                                        @if(in_array($record->id, $dismissedMaintenanceIds))
                                            <button type="button" class="btn restore-btn border inline-flex items-center justify-center" data-restore-maint="{{ $record->id }}" title="Show this alert again">
                                                <i class="fas fa-rotate-left"></i> Restore
                                            </button>
                                        @else
                                            <button type="button" class="notif-x" data-dismiss-maint="{{ $record->id }}" title="Remove from notifications" aria-label="Remove from notifications">
                                                <i class="fas fa-xmark"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">
                                    No open maintenance work orders.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest'
    };
    const oilUrl = '{{ route("motorpool.api.vehicles.dismiss-oil-change-notification") }}';
    const maintUrl = '{{ route("vehicle-maintenance.notification.dismiss", "__ID__") }}';

    function send(url, method, body) {
        return fetch(url, {
            method: method,
            headers: headers,
            body: body ? JSON.stringify(body) : undefined
        }).then(res => {
            if (!res.ok) throw new Error('Request failed');
            return res.json();
        });
    }

    function bind(selector, handler) {
        document.querySelectorAll(selector).forEach(btn => {
            btn.addEventListener('click', () => {
                btn.disabled = true;
                handler(btn)
                    .then(() => location.reload())
                    .catch(() => { btn.disabled = false; });
            });
        });
    }

    bind('[data-dismiss-oil]', btn => send(oilUrl, 'POST', { vehicle_id: btn.dataset.dismissOil, action: 'ignore' }));
    bind('[data-restore-oil]', btn => send(oilUrl, 'POST', { vehicle_id: btn.dataset.restoreOil, action: 'restore' }));
    bind('[data-dismiss-maint]', btn => send(maintUrl.replace('__ID__', btn.dataset.dismissMaint), 'POST'));
    bind('[data-restore-maint]', btn => send(maintUrl.replace('__ID__', btn.dataset.restoreMaint), 'DELETE'));
});
</script>
@endsection
