@extends('layouts.motorpool-admin')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Travel Order #{{ $travelOrder->id }}</h2>
                <div>
                    <button type="button" onclick="viewExcelFromShow()" 
                       class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mr-2">
                        <i class="fas fa-file-excel mr-1"></i> View Excel
                    </button>
                    <a href="{{ route('motorpool.travel-orders.index') }}" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Back to Travel Orders
                    </a>
                </div>
            </div>
            
            <div class="px-6 py-4">
                <div class="text-sm text-gray-800">
                    <table class="w-full">
                        <tbody>
                            <!-- Employee Information Section -->
                            <tr>
                                <td colspan="2" class="font-bold text-gray-900 py-1.5 border-b">Employee Information</td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Employee Name:</td>
                                <td class="text-gray-900 py-0.5 align-top">
                                    @if($travelOrder->employee?->full_name)
                                        {{ $travelOrder->employee?->full_name }}
                                    @elseif($travelOrder->employee?->middle_name)
                                        {{ $travelOrder->employee?->first_name }} {{ substr($travelOrder->employee?->middle_name, 0, 1) }}. {{ $travelOrder->employee?->last_name }}
                                    @else
                                        {{ $travelOrder->employee?->first_name }} {{ $travelOrder->employee?->last_name }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Email:</td>
                                <td class="text-gray-900 py-0.5 align-top">
                                    {{ $travelOrder->employee?->user?->email ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Position:</td>
                                <td class="text-gray-900 py-0.5 align-top">
                                    {{ $travelOrder->position?->position_name ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Contact Number:</td>
                                <td class="text-gray-900 py-0.5 align-top">
                                    {{ $travelOrder->employee?->contact_num ?? '-' }}
                                </td>
                            </tr>
                            
                            <!-- Travel Details Section -->
                            <tr>
                                <td colspan="2" class="font-bold text-gray-900 py-1.5 border-b mt-2">Travel Details</td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Date:</td>
                                <td class="text-gray-900 py-0.5 align-top">
                                    @if($travelOrder->date_from instanceof \DateTimeInterface)
                                        {{ $travelOrder->date_from->format('F j, Y') }}
                                    @else
                                        {{ $travelOrder->date_from }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Time:</td>
                                <td class="text-gray-900 py-0.5 align-top">
                                    @if($travelOrder->departure_time instanceof \DateTimeInterface)
                                        {{ $travelOrder->departure_time->format('g:i A') }}
                                    @elseif(is_string($travelOrder->departure_time) && preg_match('/(\d{1,2}):(\d{2})(?::\d{2})?/', $travelOrder->departure_time, $matches))
                                        @php
                                            $hours = (int)$matches[1];
                                            $minutes = $matches[2];
                                            $ampm = $hours >= 12 ? 'PM' : 'AM';
                                            $hours = $hours % 12 ?: 12;
                                        @endphp
                                        {{ $hours }}:{{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }} {{ $ampm }}
                                    @else
                                        {{ $travelOrder->departure_time }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Destination:</td>
                                <td class="text-gray-900 py-0.5 align-top">{{ $travelOrder->destination }}</td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Purpose:</td>
                                <td class="text-gray-900 py-0.5 align-top">{{ $travelOrder->purpose }}</td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Status:</td>
                                <td class="text-gray-900 py-0.5 align-top">
                                    @php
                                        $statusColor = match($travelOrder->status) {
                                            'approved' => 'bg-green-100 text-green-800',
                                            'completed' => 'bg-green-500 text-white',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                        {{ ucfirst($travelOrder->status) }}
                                    </span>
                                </td>
                            </tr>
                            
                            @if($travelOrder->status === 'cancelled')
                                @if($travelOrder->rejectedBy)
                                    <tr>
                                        <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Rejected by:</td>
                                        <td class="text-gray-900 py-0.5 align-top">
                                            {{ $travelOrder->rejectedBy?->full_name ?? 'Unknown' }}
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Cancellation Reason:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        {{ $travelOrder->rejection_reason ?? 'No reason provided' }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2">
                <button type="button" onclick="viewExcelFromModal(); closeTravelOrderModal();" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <i class="fas fa-file-excel mr-1"></i> View Excel
                </button>
                <button onclick="closeTravelOrderModal()" class="px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
// Excel view function with confirmation for show page
function viewExcelFromShow() {
    const excelUrl = '{{ route('motorpool.travel-orders.pdf', $travelOrder) }}';
    
    // Show confirmation dialog before proceeding
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
            // User confirmed - use simple anchor tag click (no blob, no fetch)
            window.location.href = excelUrl;
        }
    });
}

// Excel view function with confirmation for modal
function viewExcelFromModal() {
    const excelUrl = '{{ route('motorpool.travel-orders.pdf', $travelOrder) }}';
    
    // Show confirmation dialog before proceeding
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
            // User confirmed - use simple navigation
            window.location.href = excelUrl;
        }
    });
}
</script>
