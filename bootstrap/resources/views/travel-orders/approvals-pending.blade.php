@section('approval-content')
<!-- SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="overflow-x-auto">
    @if($travelOrders->count() > 0)
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stage</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($travelOrders as $travelOrder)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $travelOrder->position->position_name ?? 'N/A' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $travelOrder->destination }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate">{{ $travelOrder->purpose }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ $travelOrder->date_from->format('M d, Y') }} - {{ $travelOrder->date_to->format('M d, Y') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($travelOrder->approved_by_employee_id == Auth::user()->employee->id && !$travelOrder->president_approved)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Awaiting Your Approval
                                </span>
                            @elseif($travelOrder->signed_by_employee_id == Auth::user()->employee->id && $travelOrder->president_approved && (!$travelOrder->vp_approved || $travelOrder->vp_approved == false))
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    Awaiting Your Signature
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($travelOrder->approved_by_employee_id == Auth::user()->employee->id && !$travelOrder->president_approved)
                                <!-- Approver Actions -->
                                <button onclick="showApproveModal({{ $travelOrder->id }}, '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')" 
                                    class="text-green-600 hover:text-green-900 mr-3 inline-flex items-center">
                                    <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Approve
                                </button>
                                <button onclick="showRejectModal({{ $travelOrder->id }}, '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')" 
                                    class="text-red-600 hover:text-red-900 inline-flex items-center">
                                    <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Reject
                                </button>
                            @elseif($travelOrder->signed_by_employee_id == Auth::user()->employee->id && $travelOrder->president_approved && (!$travelOrder->vp_approved || $travelOrder->vp_approved == false))
                                <!-- Signer Actions -->
                                <button onclick="showSignModal({{ $travelOrder->id }}, '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')" 
                                    class="text-blue-600 hover:text-blue-900 mr-3 inline-flex items-center">
                                    <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    Sign
                                </button>
                                <button onclick="showRejectSignModal({{ $travelOrder->id }}, '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')" 
                                    class="text-red-600 hover:text-red-900 inline-flex items-center">
                                    <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Reject
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $travelOrders->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No travel orders pending approval</h3>
            <p class="mt-1 text-sm text-gray-500">All travel orders have been processed or there are no pending travel orders.</p>
        </div>
    @endif
</div>

<!-- Modals -->
@include('travel-orders.partials.approval-modals')
@endsection
