@foreach($vehicles as $index => $vehicle)
    @php
        $rowNumber = ($vehicles->currentPage() - 1) * $vehicles->perPage() + $index + 1;
    @endphp
    {{-- DEBUG: Page {{ $vehicles->currentPage() }} | PerPage: {{ $vehicles->perPage() }} | Index: {{ $index }} | Calculated: {{ $rowNumber }} --}}
    @php
        $hasIssue = $vehicle->hasActiveMaintenanceIssues();
        // Highlight the whole row whenever the vehicle needs oil change, even if the
        // bell alert was dismissed. Dismissing only hides the notification.
        $oilDue = $vehicle->needsOilChange();
        if ($hasIssue) {
            $rowClass = 'bg-red-100';
        } elseif ($oilDue) {
            $rowClass = 'bg-amber-50';
        } else {
            $rowClass = $loop->even ? 'bg-gray-50' : 'bg-white';
        }
    @endphp
    <tr class="{{ $rowClass }} hover:bg-[#f0f8f0]">
        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium">{{ $rowNumber }}</td>
        <td class="px-3 py-2 whitespace-nowrap">
            <div class="flex items-center gap-2">
                <span class="font-semibold" style="font-size: 0.9rem;">{{ $vehicle->plate_number }}</span>
                @if($vehicle->hasActiveMaintenanceIssues())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="Has active maintenance issues">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $vehicle->getActiveMaintenanceIssuesCount() }}
                    </span>
                @endif
                @if($vehicle->needsOilChange() && !$vehicle->isOilChangeNotificationDismissed())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 border-2 border-red-300" title="Needs oil change - exceeded 5000 km since last service">
                        <i class="fas fa-oil-can mr-1"></i>Needs Oil Change
                    </span>
                @endif
            </div>
        </td>
        <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $vehicle->model }}</td>
        <td class="px-3 py-2 whitespace-nowrap text-sm">
            <div class="text-gray-600 text-xs mt-1">{{ $vehicle->type }}</div>
        </td>
        <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
            <div class="action-buttons flex justify-center space-x-1">
                <a href="{{ route('vehicles.show', $vehicle) }}" 
                   class="btn view-btn border inline-flex items-center justify-center"
                   title="View Vehicle">
                    <i class="fas fa-eye"></i> View
                </a>
                <button type="button" 
                   class="btn edit-btn border inline-flex items-center justify-center"
                   title="Edit Vehicle"
                   onclick="loadVehicleData({{ $vehicle->id }})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" class="inline delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="button" 
                            class="btn archive-btn border delete-btn inline-flex items-center justify-center"
                            title="Archive Vehicle">
                        <i class="fas fa-archive"></i> Archive
                    </button>
                </form>
            </div>
        </td>
    </tr>
@endforeach

@if($vehicles->isEmpty())
    <tr>
        <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">
            No vehicles found.
        </td>
    </tr>
@endif