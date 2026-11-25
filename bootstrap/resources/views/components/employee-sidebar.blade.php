<div class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 shadow-lg z-50 transition-all duration-300" id="sidebar">
    <div class="flex flex-col h-full">
        <!-- Logo/Header Section -->
        <div class="flex items-center justify-between h-12 bg-[#1e6031] text-white px-4">
            <h1 class="text-lg font-bold" id="sidebar-title">
                <span>
                    @if(auth()->user()->employee && auth()->user()->employee->is_president)
                        President Panel
                    @elseif(auth()->user()->employee && auth()->user()->employee->is_vp)
                        VP Panel
                    @elseif(auth()->user()->employee && auth()->user()->employee->is_head)
                        Head Panel
                    @elseif(auth()->user()->employee && auth()->user()->employee->is_divisionhead)
                        Division Head Panel
                    @else
                        Employee Panel
                    @endif
                </span>
            </h1>
            <button id="toggleSidebar" class="lg:block hidden text-sm focus:outline-none">
                <svg id="collapseIcon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </div>

        <!-- User Profile Section -->
        <div class="border-b border-gray-200 py-3 px-3">
            <div class="flex items-start">
                <div class="bg-gray-200 border-2 border-dashed rounded-xl w-8 h-8 flex items-center justify-center">
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="ml-2" id="profile-text-container">
                    <p class="text-xs font-medium text-gray-700 mb-0">
                        @if(auth()->user()->employee)
                            {{ auth()->user()->employee->first_name }} {{ auth()->user()->employee->last_name }}
                        @else
                            {{ auth()->user()->name }}
                        @endif
                    </p>
                    <a href="{{ route('profile.edit') }}" class="text-xs font-medium text-[#1e6031] hover:text-[#007d31] truncate block mt-0">
                        Manage Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <nav class="flex-1 px-2 py-2 overflow-y-auto">
                <!-- Dashboard -->
                <div class="mb-1">
                    <a href="{{ route('dashboard') }}" class="@if(request()->routeIs('dashboard')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                        <svg class="h-5 w-5 @if(request()->routeIs('dashboard')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="text-sm font-bold @if(request()->routeIs('dashboard')) text-[#1e6031] @endif">Dashboard</span>
                    </a>
                </div>
                

                
                <!-- My Travel Requests (Regular Employees) -->
                @if(auth()->user()->employee && !auth()->user()->employee->is_president && !auth()->user()->employee->is_vp && !auth()->user()->employee->is_head && !auth()->user()->employee->is_divisionhead)
                    <div class="mb-1">
                        <a href="{{ route('travel-orders.index') }}" class="@if(request()->routeIs('travel-orders.index')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                            <svg class="h-5 w-5 @if(request()->routeIs('travel-orders.index')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="text-sm font-bold @if(request()->routeIs('travel-orders.index')) text-[#1e6031] @endif">My Travel Requests</span>
                        </a>
                    </div>
                    

                @endif
                
                <!-- Division Head Travel Orders -->
                @if(auth()->user()->employee && auth()->user()->employee->is_divisionhead)
                    <div class="mb-1">
                        <a href="{{ route('divisionhead.travel-orders.index', ['tab' => 'draft']) }}" class="@if(request()->routeIs('divisionhead.travel-orders.index')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                            <svg class="h-5 w-5 @if(request()->routeIs('divisionhead.travel-orders.index')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="text-sm font-bold @if(request()->routeIs('divisionhead.travel-orders.index')) text-[#1e6031] @endif">My Travel Requests</span>
                        </a>
                    </div>
                    
                @endif
                
                <!-- Head Travel Orders -->
                @if(auth()->user()->employee && auth()->user()->employee->is_head && !auth()->user()->employee->is_divisionhead && !auth()->user()->employee->is_vp && !auth()->user()->employee->is_president)
                    <div class="mb-1">
                        <a href="{{ route('unithead.travel-orders.index', ['tab' => 'draft']) }}" class="@if(request()->routeIs('unithead.travel-orders.index')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                            <svg class="h-5 w-5 @if(request()->routeIs('unithead.travel-orders.index')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="text-sm font-bold @if(request()->routeIs('unithead.travel-orders.index')) text-[#1e6031] @endif">My Travel Requests</span>
                        </a>
                    </div>
                    
                @endif
                
                <!-- President's Travel Orders -->
                @if(auth()->user()->employee && auth()->user()->employee->is_president)
                    <div class="mb-1">
                        <a href="{{ route('president.travel-orders.index', ['tab' => 'draft']) }}" class="@if(request()->routeIs('president.travel-orders.index')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                            <svg class="h-5 w-5 @if(request()->routeIs('president.travel-orders.index')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="text-sm font-bold @if(request()->routeIs('president.travel-orders.index')) text-[#1e6031] @endif">My Travel Requests</span>
                        </a>
                    </div>
                    
                @endif
                
                <!-- VP Travel Orders -->
                @if(auth()->user()->employee && auth()->user()->employee->is_vp)
                    <div class="mb-1">
                        <a href="{{ route('vp.travel-orders.index', ['tab' => 'draft']) }}" class="@if(request()->routeIs('vp.travel-orders.index')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                            <svg class="h-5 w-5 @if(request()->routeIs('vp.travel-orders.index')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="text-sm font-bold @if(request()->routeIs('vp.travel-orders.index')) text-[#1e6031] @endif">My Travel Requests</span>
                        </a>
                    </div>
                    
                @endif
                
                <!-- Approvals section (only shown for users with approval authority) -->
                @php
                    // Check if user has any approval authority
                    $hasApprovalAuthority = false;
                    if(auth()->user()->employee) {
                        $isDivisionHead = auth()->user()->employee->is_divisionhead;
                        $isHead = auth()->user()->employee->is_head;
                        $isPresident = auth()->user()->employee->is_president;
                        $isVP = auth()->user()->employee->is_vp;
                        
                        $primaryUnitId = auth()->user()->employee->positions()
                            ->where('is_primary', 1)
                            ->value('unit_id');

                        $primaryUnitName = $primaryUnitId
                            ? \App\Models\Unit::whereKey($primaryUnitId)->value('unit_name')
                            : null;

                        $isTransportationServicesUnitHead =
                            $isHead &&
                            $primaryUnitName &&
                            str_contains(strtolower($primaryUnitName), 'transportation services');
                        
                        $isVpOfAdministration = auth()->user()->employee->positions()->whereHas('office', function($query) {
                            $query->where('office_name', 'Office of the Vice President for Administration');
                        })->exists();
                        
                        $hasApprovalAuthority = $isDivisionHead || $isHead || $isPresident || $isVP || 
                                              $isTransportationServicesUnitHead || $isVpOfAdministration;
                    }
                @endphp
                
                <!-- Trip Tickets (for all employees) -->
                @if(auth()->user()->employee)
                    <div class="mb-1">
                        <a href="{{ route('employee.trip-tickets.index') }}" class="@if(request()->routeIs('employee.trip-tickets.*')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                            <svg class="h-5 w-5 @if(request()->routeIs('employee.trip-tickets.*')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-bold @if(request()->routeIs('employee.trip-tickets.*')) text-[#1e6031] @endif">Trip Tickets</span>
                        </a>
                    </div>
                @endif
                
                @if($hasApprovalAuthority)
                <div class="mb-1">
                    <button type="button" class="w-full flex items-center px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-200 mb-1 approvals-toggle">
                        <svg class="h-5 w-5 text-[#004d26] mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-bold text-[#004d26] flex-1 text-left">Approvals</span>
                        <svg class="h-4 w-4 text-[#004d26] transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <!-- Sub-items for Approvals -->
                    <div class="ml-4 mt-1 space-y-1 approvals-submenu hidden">
                        <!-- Travel Order Approvals -->
                        @if(auth()->user()->employee && (auth()->user()->employee->is_divisionhead || auth()->user()->employee->is_head || auth()->user()->employee->is_president || auth()->user()->employee->is_vp))
                            @if((auth()->user()->employee->is_divisionhead || auth()->user()->employee->is_head || auth()->user()->employee->is_president || auth()->user()->employee->is_vp) && !$isTransportationServicesUnitHead)
                        <a href="@if(auth()->user()->employee->is_divisionhead){{ route('travel-orders.approvals.divisionhead') }}@elseif(auth()->user()->employee->is_head){{ route('travel-orders.approvals.head') }}@elseif(auth()->user()->employee->is_president){{ route('travel-orders.approvals.president') }}@elseif(auth()->user()->employee->is_vp){{ route('travel-orders.approvals.vp') }}@endif" class="@if(request()->routeIs('travel-orders.approvals.*')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                            <svg class="h-5 w-5 @if(request()->routeIs('travel-orders.approvals.*')) text-[#1e6031] @else text-[#1e6031] @endif mr-2.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-medium @if(request()->routeIs('travel-orders.approvals.*')) text-[#1e6031] @endif">Travel Order Approvals</span>
                            @php
                                $employeeId = auth()->user()->employee->id;
                                
                                // Count pending approvals matching the Pending tab logic exactly
                                $pendingCount = \Illuminate\Support\Facades\DB::table('travel_orders')
                                    ->where('status', '!=', 'cancelled')
                                    ->where('status', '!=', 'draft')
                                    ->where(function($q) use ($employeeId) {
                                        // Approver 2's queue (lower rank - approves first)
                                        // Shows when: Employee is approver2 AND hasn't approved yet
                                        $q->where(function($approver2Queue) use ($employeeId) {
                                            $approver2Queue->where('approver2_employee_id', $employeeId)
                                                  ->whereNull('approver2_approved_at');
                                        })
                                        // OR Approver 1's queue (higher rank - approves second/final)
                                        // Shows when: Employee is approver1 AND hasn't approved yet AND 
                                        // (there is no approver2 OR approver2 has already approved)
                                        ->orWhere(function($approver1Queue) use ($employeeId) {
                                            $approver1Queue->where('approver1_employee_id', $employeeId)
                                                  ->whereNull('approver1_approved_at')
                                                  ->where(function($condition) {
                                                      $condition->whereNull('approver2_employee_id')
                                                                ->orWhereNotNull('approver2_approved_at');
                                                  });
                                        });
                                    })
                                    ->count();
                            @endphp
                            @if($pendingCount > 0)
                                <span class="ml-auto inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#1e6031] text-white">
                                    {{ $pendingCount }}
                                </span>
                            @endif
                        </a>
                            @endif
                        @endif
                        
                        <!-- Itinerary Approvals -->
                        @php
                            $employee = auth()->user()->employee;

                            $isDivisionHead = $employee && (bool) $employee->is_divisionhead;
                            // Head Panel / Unit Head takes priority for the Itinerary Approval link.
                            // Some leadership records have both is_head=1 and is_vp=1.
                            // If the account is being used as Head Panel, it must open the
                            // Unit Head itinerary queue, not the VP queue.
                            $isUnitHead = $employee
                                && (bool) $employee->is_head
                                && !(bool) $employee->is_divisionhead
                                && !(bool) $employee->is_president;

                            $isVpApprover = $employee
                                && ((bool) $employee->is_vp || (bool) $employee->is_president)
                                && !$isUnitHead;

                            // IMPORTANT:
                            // A Unit Head ALWAYS opens the Unit Head Pending queue.
                            // Any VP (VPAA, VPAD, VP for Research & Extension, VP for
                            // Business Affairs) or the President ALWAYS opens the VP
                            // Pending queue — each only sees the itineraries whose
                            // passenger resolves to THEM as the next-rank approver
                            // (see App\Support\ItineraryApproverResolver).
                            // They must never be routed to each other's queue.
                            $showItineraryApprovals = $employee && !$isDivisionHead && ($isUnitHead || $isTransportationServicesUnitHead || $isVpApprover);
                        @endphp

                        @if($showItineraryApprovals)
                            @php
                                $itineraryApprovalRoute = ($isUnitHead || $isTransportationServicesUnitHead)
                                    ? route('itinerary.approvals.unit-head.pending')
                                    : route('motorpool.approvals.itineraries.vp');

                                if ($isUnitHead || $isTransportationServicesUnitHead) {
                                    // Pending ITINERARIES awaiting Unit Head approval.
                                    $pendingItineraryQuery = \App\Models\Itinerary::where('unit_head_approved', false)
                                        ->whereNull('unit_head_approved_at')
                                        ->where('status', '!=', 'Cancelled');

                                    // Transportation Services Unit Head keeps its existing
                                    // authority to see all Unit Head pending itineraries.
                                    if (!$isTransportationServicesUnitHead) {
                                        $headUnitId = $employee->positions()
                                            ->where('is_primary', 1)
                                            ->value('unit_id');

                                        if ($headUnitId) {
                                            $employeeIds = \App\Models\Employee::whereHas('positions', function ($positionQuery) use ($headUnitId) {
                                                $positionQuery->where('unit_id', $headUnitId);
                                            })->pluck('id');

                                            $travelOrderIds = \App\Models\TravelOrder::whereIn('employee_id', $employeeIds)
                                                ->pluck('id')
                                                ->map(fn ($id) => (int) $id);

                                            if ($travelOrderIds->isEmpty()) {
                                                $pendingItineraryQuery->whereRaw('1 = 0');
                                            } else {
                                                $pendingItineraryQuery->where(function ($itineraryQuery) use ($travelOrderIds) {
                                                    foreach ($travelOrderIds as $travelOrderId) {
                                                        $itineraryQuery->orWhereJsonContains('travel_order_id', $travelOrderId);
                                                    }
                                                });
                                            }
                                        } else {
                                            $pendingItineraryQuery->whereRaw('1 = 0');
                                        }
                                    }

                                    $pendingItineraryCount = $pendingItineraryQuery->count();
                                } else {
                                    // VP queue: only itineraries already approved by the
                                    // Unit Head AND whose passenger resolves to THIS
                                    // employee as the next-rank approver (VPAA only sees
                                    // Academic Affairs passengers, VPAD only sees
                                    // Administration passengers, etc).
                                    $pendingItineraryCount = \App\Models\Itinerary::where('unit_head_approved', true)
                                        ->where('vp_approved', false)
                                        ->whereNull('vp_approved_at')
                                        ->where('status', '!=', 'Cancelled')
                                        ->get()
                                        ->filter(function ($pendingItinerary) use ($employee) {
                                            return \App\Support\ItineraryApproverResolver::resolveItineraryApprovers($pendingItinerary)
                                                ->contains('id', $employee->id);
                                        })
                                        ->count();
                                }
                            @endphp

                            <a href="{{ $itineraryApprovalRoute }}" class="@if(request()->routeIs('itinerary.approvals.*') || request()->routeIs('motorpool.approvals.itineraries.vp')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                                <svg class="h-5 w-5 text-[#1e6031] mr-2.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2-2z" />
                                </svg>
                                <span class="text-sm font-medium @if(request()->routeIs('itinerary.approvals.*') || request()->routeIs('motorpool.approvals.itineraries.vp')) text-[#1e6031] @endif">Itinerary Approvals</span>
                                @if($pendingItineraryCount > 0)
                                    <span class="ml-auto inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#1e6031] text-white">
                                        {{ $pendingItineraryCount }}
                                    </span>
                                @endif
                            </a>
                        @endif

                        <!-- Trip Ticket Approvals -->
                        @if(auth()->user()->employee && $isVpOfAdministration && auth()->user()->employee->is_vp)
                        <a href="{{ route('motorpool.approvals.triptickets.vp') }}" class="@if(request()->routeIs('motorpool.approvals.triptickets.vp')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                            <svg class="h-5 w-5 @if(request()->routeIs('motorpool.approvals.triptickets.vp')) text-[#1e6031] @else text-[#1e6031] @endif mr-2.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-medium @if(request()->routeIs('motorpool.approvals.triptickets.vp')) text-[#1e6031] @endif">Trip Ticket Approvals</span>
                            @php
                                // Count pending trip ticket approvals (exclude cancelled)
                                $pendingTripTicketCount = \App\Models\TripTicket::where('status', 'Pending')->count();
                            @endphp
                            @if($pendingTripTicketCount > 0)
                                <span class="ml-auto inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#1e6031] text-white">
                                    {{ $pendingTripTicketCount }}
                                </span>
                            @endif
                        </a>
                        @endif
                    </div>
                </div>
                @endif
            </nav>
            
            <!-- Logout Button - Fixed at the bottom -->
            <div class="px-3 py-2 border-t border-gray-200 mt-auto">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                        <svg class="h-5 w-5 text-[#1e6031] mr-2.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="font-medium text-sm truncate">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebar');
            const collapseIcon = document.getElementById('collapseIcon');
            const mainContent = document.getElementById('main-content');
            const sidebarTitle = document.getElementById('sidebar-title');
            
            // Initialize icon click events for collapsed state
            function initCollapsedIconEvents() {
                if (sidebar.classList.contains('w-20')) {
                    const navIcons = document.querySelectorAll('#sidebar a.flex svg');
                    navIcons.forEach(icon => {
                        icon.addEventListener('click', function(e) {
                            // Only expand if sidebar is collapsed
                            if (sidebar.classList.contains('w-20')) {
                                e.preventDefault();
                                expandSidebar();
                            }
                        });
                    });
                }
            }
            
            // Call on initial load
            initCollapsedIconEvents();
            
            // Hide dropdown arrow if sidebar starts collapsed
            if (sidebar.classList.contains('w-20')) {
                const dropdownArrow = document.querySelector('.approvals-toggle svg:last-child');
                if (dropdownArrow) {
                    dropdownArrow.style.display = 'none';
                }
            }
            
            // Function to collapse the sidebar
            function collapseSidebar() {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                
                // Change icon to expand (right arrow)
                collapseIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />';
                
                // Hide all text spans including the sidebar title
                const textSpans = document.querySelectorAll('#sidebar span');
                textSpans.forEach(span => {
                    span.style.display = 'none';
                });
                
                // Also hide the sidebar title specifically
                const sidebarTitle = document.getElementById('sidebar-title');
                if (sidebarTitle) {
                    sidebarTitle.style.display = 'none';
                }
                
                // Hide the profile text container when collapsed
                const profileTextContainer = document.getElementById('profile-text-container');
                if (profileTextContainer) {
                    profileTextContainer.style.display = 'none';
                }
                
                // Hide the dropdown arrow icon when sidebar is collapsed
                const dropdownArrow = document.querySelector('.approvals-toggle svg:last-child');
                if (dropdownArrow) {
                    dropdownArrow.style.display = 'none';
                }
                
                // Center the icons
                const links = document.querySelectorAll('#sidebar a.flex');
                links.forEach(link => {
                    link.classList.add('justify-center', 'px-0');
                    link.classList.remove('px-3');
                });
                
                // Add click event to navigation icons to expand sidebar
                const navIcons = document.querySelectorAll('#sidebar a.flex svg');
                navIcons.forEach(icon => {
                    icon.addEventListener('click', function(e) {
                        // Only expand if sidebar is collapsed
                        if (sidebar.classList.contains('w-20')) {
                            e.preventDefault();
                            expandSidebar();
                        }
                    });
                });
                
                // Update main content margin
                if (mainContent) {
                    mainContent.style.marginLeft = '5rem';
                }
            }
            
            // Function to expand the sidebar
            function expandSidebar() {
                sidebar.classList.remove('w-20');
                sidebar.classList.add('w-64');
                
                // Change icon to collapse (left arrow)
                collapseIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />';
                
                // Show all text spans
                const textSpans = document.querySelectorAll('#sidebar span');
                textSpans.forEach(span => {
                    span.style.display = 'inline';
                });
                
                // Also show the sidebar title when expanded
                const sidebarTitle = document.getElementById('sidebar-title');
                if (sidebarTitle) {
                    sidebarTitle.style.display = 'block';
                }
                
                // Show the profile text container when expanded
                const profileTextContainer = document.getElementById('profile-text-container');
                if (profileTextContainer) {
                    profileTextContainer.style.display = 'block';
                }
                
                // Show the dropdown arrow icon when sidebar is expanded
                const dropdownArrow = document.querySelector('.approvals-toggle svg:last-child');
                if (dropdownArrow) {
                    dropdownArrow.style.display = 'block';
                }
                
                // Reset link styles
                const links = document.querySelectorAll('#sidebar a.flex');
                links.forEach(link => {
                    link.classList.remove('justify-center', 'px-0');
                    link.classList.add('px-3');
                });
                
                // Remove click event from navigation icons to expand sidebar
                const navIcons = document.querySelectorAll('#sidebar a.flex svg');
                navIcons.forEach(icon => {
                    icon.removeEventListener('click', expandSidebar);
                });
                
                // Update main content margin
                if (mainContent) {
                    mainContent.style.marginLeft = '16rem';
                }
            }
            
            // Function to save submenu state
            function saveSubmenuState() {
                const approvalsSubmenu = document.querySelector('.approvals-submenu');
                
                if (approvalsSubmenu) {
                    localStorage.setItem('approvalsSubmenuVisible', !approvalsSubmenu.classList.contains('hidden'));
                }
            }
            
            // Function to restore submenu state
            function restoreSubmenuState() {
                const approvalsSubmenu = document.querySelector('.approvals-submenu');
                
                if (approvalsSubmenu) {
                    const approvalsVisible = localStorage.getItem('approvalsSubmenuVisible');
                    if (approvalsVisible === 'true') {
                        approvalsSubmenu.classList.remove('hidden');
                        approvalsSubmenu.classList.add('block');
                        
                        // Update the arrow icon rotation
                        const approvalsArrow = document.querySelector('.approvals-toggle svg:last-child');
                        if (approvalsArrow) {
                            approvalsArrow.style.transform = 'rotate(180deg)';
                        }
                    }
                }
            }
            
            // Restore submenu state on page load
            restoreSubmenuState();
            
            // Approvals submenu toggle functionality
            const approvalsToggle = document.querySelector('.approvals-toggle');
            const approvalsSubmenu = document.querySelector('.approvals-submenu');
            
            if (approvalsToggle && approvalsSubmenu) {
                approvalsToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Only toggle if sidebar is expanded
                    if (!sidebar.classList.contains('w-20')) {
                        // Toggle submenu visibility
                        if (approvalsSubmenu.classList.contains('hidden')) {
                            approvalsSubmenu.classList.remove('hidden');
                            approvalsSubmenu.classList.add('block');
                            
                            // Rotate the arrow icon
                            const arrowIcon = this.querySelector('svg:last-child');
                            if (arrowIcon) {
                                arrowIcon.style.transform = 'rotate(180deg)';
                            }
                        } else {
                            approvalsSubmenu.classList.add('hidden');
                            approvalsSubmenu.classList.remove('block');
                            
                            // Rotate the arrow icon back
                            const arrowIcon = this.querySelector('svg:last-child');
                            if (arrowIcon) {
                                arrowIcon.style.transform = 'rotate(0deg)';
                            }
                        }
                        
                        // Save the state
                        saveSubmenuState();
                    }
                });
            }
            
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (sidebar.classList.contains('w-20')) {
                    expandSidebar();
                } else {
                    collapseSidebar();
                }
            });
        });
    </script>
</div>

<!-- Mobile Navbar (visible only on screens <= 700px) -->
<div id="mobileNavbar" style="display: none;">
    <div style="position: fixed; top: 0; left: 0; right: 0; background: #1e6031; border-bottom: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index: 40; height: 56px;">
        <div style="display: flex; align-items: center; justify-content: space-between; height: 100%; padding: 0 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <!-- Hamburger menu on the LEFT -->
                <button id="mobileMenuToggle" style="padding: 8px; color: white; background: none; border: none; cursor: pointer;">
                    <svg style="width: 24px; height: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 style="font-size: 18px; font-weight: bold; color: white; margin: 0;">
                    @if(auth()->user()->employee && auth()->user()->employee->is_president)
                        President
                    @elseif(auth()->user()->employee && auth()->user()->employee->is_vp)
                        VP
                    @elseif(auth()->user()->employee && auth()->user()->employee->is_head)
                        Head
                    @elseif(auth()->user()->employee && auth()->user()->employee->is_divisionhead)
                        Division Head
                    @else
                        Employee
                    @endif
                </h1>
            </div>
            <div style="display: flex; align-items: center;">
                <!-- Profile icon removed -->
            </div>
        </div>
    </div>
</div>

<div id="mobileOverlay" style="display: none;"></div>

<style>
    /* Mobile Styles */
    @media (max-width: 700px) {
        /* Show mobile navbar */
        #mobileNavbar {
            display: block !important;
        }
        
        /* Adjust main content for mobile */
        #main-content {
            margin-left: 0 !important;
            padding-top: 56px;
        }
        
        /* Sidebar hidden by default (off-screen) */
        #sidebar {
            transform: translateX(-100%);
        }
        
        /* Sidebar when active */
        #sidebar.active {
            transform: translateX(0) !important;
        }
        
        /* Overlay */
        #mobileOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 45;
        }
        
        #mobileOverlay.active {
            display: block !important;
        }
    }
    
    /* Desktop - hide mobile elements */
    @media (min-width: 701px) {
        #mobileNavbar {
            display: none !important;
        }
        #mobileOverlay {
            display: none !important;
        }
    }
</style>

<script>
    // Mobile Menu Toggle Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        // Open mobile menu
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                sidebar.classList.add('active');
                mobileOverlay.classList.add('active');
            });
        }
        
        // Close mobile menu
        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                mobileOverlay.classList.remove('active');
            });
        }
    });
</script>