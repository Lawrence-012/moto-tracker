<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Travel Orders for Approval') }}
            </h2>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Back to Dashboard
            </a>
        </div>
    </x-slot>
    
    <!-- SweetAlert2 Library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Search Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ request()->path() }}" class="flex gap-4">
                        <div class="flex-1">
                            <input type="text" name="search" value="{{ $search ?? '' }}" 
                                placeholder="Search employee, destination or purpose..."
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1e6031]">
                        </div>
                        <button type="submit" class="px-6 py-2 bg-[#1e6031] text-white rounded-lg hover:bg-[#164f2a] focus:outline-none focus:ring-2 focus:ring-[#1e6031]">
                            Search
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6 bg-white border-b border-gray-200">
                    <p class="text-gray-600 text-sm">
                        Review and approve travel requests from your unit members.
                    </p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <a href="{{ route('employee.travel-orders.approvals.pending', ['search' => request('search')]) }}" 
                           class="{{ !request('tab') || request('tab') === 'pending' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm flex items-center">
                            Pending
                            @if(isset($pendingCount) && $pendingCount > 0)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ !request('tab') || request('tab') === 'pending' ? 'bg-[#1e6031] text-white' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $pendingCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('employee.travel-orders.approvals.approved', ['search' => request('search')]) }}" 
                           class="{{ request('tab') === 'approved' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm flex items-center">
                            Approved
                            @if(isset($approvedCount) && $approvedCount > 0)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ request('tab') === 'approved' ? 'bg-[#1e6031] text-white' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $approvedCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('employee.travel-orders.approvals.cancelled', ['search' => request('search')]) }}" 
                           class="{{ request('tab') === 'cancelled' ? 'border-[#1e6031] text-[#1e6031]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm flex items-center">
                            Cancelled
                            @if(isset($cancelledCount) && $cancelledCount > 0)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ request('tab') === 'cancelled' ? 'bg-[#1e6031] text-white' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $cancelledCount }}
                                </span>
                            @endif
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Content Area -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <!-- Travel Orders Table -->
                    @if($travelOrders->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
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
                                                @if(!request('tab') || request('tab') === 'pending')
                                                    @if($travelOrder->approver1_employee_id == Auth::user()->employee->id && !$travelOrder->president_approved)
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            Awaiting Your Approval (Approver 1)
                                                        </span>
                                                    @elseif($travelOrder->approver2_employee_id == Auth::user()->employee->id && $travelOrder->president_approved && (!$travelOrder->vp_approved || $travelOrder->vp_approved == false))
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                            Awaiting Your Approval (Approver 2)
                                                        </span>
                                                    @endif
                                                @elseif(request('tab') === 'approved')
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Approved
                                                    </span>
                                                @elseif(request('tab') === 'cancelled')
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Cancelled
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                @if(!request('tab') || request('tab') === 'pending')
                                                    @if($travelOrder->approver1_employee_id == Auth::user()->employee->id && !$travelOrder->president_approved)
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
                                                    @elseif($travelOrder->approver2_employee_id == Auth::user()->employee->id && $travelOrder->president_approved && (!$travelOrder->vp_approved || $travelOrder->vp_approved == false))
                                                        <button onclick="showSignModal({{ $travelOrder->id }}, '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')" 
                                                            class="text-blue-600 hover:text-blue-900 mr-3 inline-flex items-center">
                                                            <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                            </svg>
                                                            Approve (As Approver 2)
                                                        </button>
                                                        <button onclick="showRejectSignModal({{ $travelOrder->id }}, '{{ $travelOrder->employee->first_name }} {{ $travelOrder->employee->last_name }}')" 
                                                            class="text-red-600 hover:text-red-900 inline-flex items-center">
                                                            <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            Reject
                                                        </button>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $travelOrders->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                @if(!request('tab') || request('tab') === 'pending')
                                    No travel orders pending approval
                                @elseif(request('tab') === 'approved')
                                    No approved travel orders
                                @elseif(request('tab') === 'cancelled')
                                    No cancelled travel orders
                                @endif
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                @if(!request('tab') || request('tab') === 'pending')
                                    All travel orders have been processed or there are no pending travel orders.
                                @elseif(request('tab') === 'approved')
                                    You haven't approved any travel orders yet.
                                @elseif(request('tab') === 'cancelled')
                                    You haven't rejected any travel orders yet.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('travel-orders.partials.approval-modals')
</x-app-layout>