@extends('layouts.employee')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Employee Dashboard') }}
    </h2>
@endsection

@section('content')
    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200 shadow-sm mb-6">
                <div class="px-4 py-3">
                    <div class="flex items-start">
                        <!-- Icon -->
                        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg p-2 mr-3 flex-shrink-0 mt-1">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        
                        <!-- Text Content -->
                        <div>
                            <h1 class="text-lg font-semibold text-gray-800 mb-0">
                                @php
                                    $displayName = Auth::user()->name;
                                    if (Auth::user()->employee) {
                                        $emp = Auth::user()->employee;
                                        $nameParts = [];
                                        if ($emp->prefix) $nameParts[] = $emp->prefix;
                                        if ($emp->full_name) $nameParts[] = $emp->full_name;
                                        elseif ($emp->first_name) $nameParts[] = $emp->first_name;
                                        if ($emp->ext_name) $nameParts[] = $emp->ext_name;
                                        if (!empty($nameParts)) $displayName = implode(' ', $nameParts);
                                    }
                                @endphp
                                Welcome, {{ $displayName }}!
                            </h1>
                            <p class="text-gray-600 text-sm mt-0">
                                Your personal dashboard for vehicle reservations and requests
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <h2 class="text-lg font-semibold text-[#1e6031] mb-4">Quick Overview</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                <!-- Total Requests -->
                @php
                    $totalRequests = Auth::user()->employee ? Auth::user()->employee->travelOrders->count() : 0;
                    $pendingRequests = Auth::user()->employee ? Auth::user()->employee->travelOrders()->where('status', 'pending')->count() : 0;
                    $approvedRequests = Auth::user()->employee ? Auth::user()->employee->travelOrders()->where('status', 'approved')->count() : 0;
                @endphp
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200 p-3">
                    <div class="flex items-center">
                        <div class="rounded-lg bg-blue-600 p-3 flex-shrink-0">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600 mb-0">Total Requests</p>
                            <p class="text-2xl font-bold text-gray-800 mt-0">{{ $totalRequests }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Pending Requests -->
                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl border border-yellow-200 p-3">
                    <div class="flex items-center">
                        <div class="rounded-lg bg-yellow-500 p-3 flex-shrink-0">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600 mb-0">Pending</p>
                            <p class="text-2xl font-bold text-gray-800 mt-0">{{ $pendingRequests }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Approved Requests -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200 p-3">
                    <div class="flex items-center">
                        <div class="rounded-lg bg-green-600 p-3 flex-shrink-0">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600 mb-0">Approved</p>
                            <p class="text-2xl font-bold text-gray-800 mt-0">{{ $approvedRequests }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="text-lg font-semibold text-[#1e6031] mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                <!-- Pending Approvals (only shown if user has pending approvals) -->
                @php
                    $employeeId = Auth::user()->employee ? Auth::user()->employee->id : null;
                    
                    // Get approver 1 count
                    $approver1Count = 0;
                    $approver2Count = 0;
                    if ($employeeId) {
                        $approver1Count = \Illuminate\Support\Facades\DB::table('travel_orders')
                            ->where('approver1_employee_id', $employeeId)
                            ->where('status', '!=', 'cancelled')
                            ->where('status', '!=', 'draft')
                            ->where(function($q) {
                                $q->whereNull('approver1_approved_at');
                            })
                            ->count();
                        
                        // Get approver 2 count  
                        $approver2Count = \Illuminate\Support\Facades\DB::table('travel_orders')
                            ->where('approver2_employee_id', $employeeId)
                            ->whereIn('status', ['pending', 'pending_approval_2'])
                            ->whereNotNull('approver1_approved_at')
                            ->where(function($q) {
                                $q->whereNull('approver2_approved_at');
                            })
                            ->count();
                    }
                    
                    $pendingApprovalsCount = $approver1Count + $approver2Count;
                @endphp
                @if($pendingApprovalsCount > 0)
                <a href="{{ route('employee.travel-orders.approvals.pending') }}" class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200 p-3 transition-all duration-200 hover:shadow-md block">
                    <div class="flex items-start">
                        <div class="rounded-lg bg-purple-600 p-3 flex-shrink-0">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">Pending Approvals</h3>
                            <p class="text-sm text-gray-600 mt-1">Review travel orders</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-800 text-purple-100 mt-2">
                                {{ $pendingApprovalsCount }} pending
                            </span>
                        </div>
                    </div>
                </a>
                @endif
                
                <!-- My Travel Requests -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200 p-3 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-start">
                        <div class="rounded-lg bg-[#1e6031] p-3 flex-shrink-0">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">My Travel Requests</h3>
                            <p class="text-gray-600 text-sm mt-1 mb-3">Manage your travel requests and view their status.</p>
                            <a href="{{ route('travel-orders.index') }}" class="inline-flex items-center px-3 py-1.5 bg-white border border-green-300 text-green-800 rounded-lg hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 text-sm font-medium transition duration-200 shadow-sm no-underline" style="text-decoration: none;">
                                View Requests
                                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- My Trip Tickets -->
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl border border-indigo-200 p-3 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-start">
                        <div class="rounded-lg bg-indigo-600 p-3 flex-shrink-0">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">My Trip Tickets</h3>
                            <p class="text-gray-600 text-sm mt-1 mb-3">View your approved trip tickets and travel details.</p>
                            <a href="{{ route('employee.trip-tickets.index') }}" class="inline-flex items-center px-3 py-1.5 bg-white border border-indigo-300 text-indigo-800 rounded-lg hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 text-sm font-medium transition duration-200 shadow-sm no-underline" style="text-decoration: none;">
                                View Tickets
                                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                

            </div>


        </div>
    </div>
    <!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@endsection