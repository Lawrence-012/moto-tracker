<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('page_title', 'MPIS')</title>
            
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=libre-franklin:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/main.min.css" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/index.global.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success') && !Request::is('vehicles') && session('success') !== 'Vehicle archived successfully!')
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '{{ session('error') }}',
                toast: false,
                position: 'center',
                showConfirmButton: true,
                confirmButtonText: 'OK',
                timer: 7000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        @endif
    });
</script>
<body class="font-libre-franklin antialiased h-full">
    <div class="flex h-screen bg-gray-50">
        <!-- Collapsible Sidebar -->
        <div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 shadow-lg z-50 transition-all duration-300">
            <div class="flex flex-col h-full">
                <!-- Logo/Header Section -->
                <div class="flex items-center justify-between h-12 bg-[#1e6031] text-white px-4">
                    <h1 class="text-lg font-bold" id="sidebar-title"><span>Motorpool Admin</span></h1>
                    <button id="toggleSidebar" class="lg:block hidden text-sm focus:outline-none">
                        <svg id="collapseIcon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                </div>

                <!-- User Profile Section -->
                <div class="border-b border-gray-200 py-3 px-3">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start">
                            <div class="bg-gray-200 border-2 border-dashed rounded-xl w-8 h-8 flex items-center justify-center">
                                <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="ml-2" id="profile-text-container">
                                <p class="text-xs font-medium text-gray-700 mb-0">{{ auth()->user()->name ?? 'Motorpool Admin' }}</p>
                                <a href="{{ route('profile.edit') }}" class="text-xs font-medium text-[#1e6031] hover:text-[#007d31] truncate block mt-0">
                                    Manage Profile
                                </a>
                            </div>
                        </div>

                        <!-- Maintenance Notification Bell -->
                        <div class="relative flex-shrink-0" id="maintenance-bell-wrapper">
                            <button type="button" id="maintenanceBellBtn"
                                    class="relative w-8 h-8 flex items-center justify-center rounded-full transition-colors duration-150"
                                    style="background-color:#A8DFAA;"
                                    title="Maintenance notifications">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="#1e6031">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span id="maintenanceBellBadge"
                                      class="hidden absolute -top-1 -right-1 inline-flex items-center justify-center h-4 min-w-[1rem] px-1 rounded-full text-[10px] font-bold text-white border border-white"
                                      style="background-color:#e0a70d;">
                                    0
                                </span>
                            </button>

                            <!-- Dropdown Panel -->
                            <div id="maintenanceBellDropdown"
                                 class="hidden absolute right-0 mt-2 overflow-hidden bg-white rounded-xl z-[60]"
                                 style="width:380px; max-width:calc(100vw - 2rem); box-shadow:0 18px 40px -12px rgba(15,61,31,.35), 0 0 0 1px rgba(30,96,49,.12);">
                                <style>
                                    #maintenanceBellList .notif-remove:hover { background:#fee2e2 !important; color:#b91c1c !important; }
                                    #maintenanceBellList .notif-open:hover .notif-title { color:#1e6031; }
                                    #maintenanceBellList .notif-item { animation: notifIn .25s ease both; }
                                    @keyframes notifIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:none; } }
                                </style>

                                <!-- Header -->
                                <div style="background:linear-gradient(135deg,#1e6031 0%,#0f3d1f 100%); padding:14px 16px 12px;">
                                    <div style="display:flex; align-items:center; justify-content:space-between;">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <span style="font-size:14px; font-weight:700; color:#fff;">Maintenance Alerts</span>
                                            <span id="maintenanceBellCount" style="display:none; font-size:11px; font-weight:700; color:#0f3d1f; background:#ffd700; padding:1px 8px; border-radius:999px;"></span>
                                        </div>
                                        <a href="{{ route('vehicle-maintenance.needed') }}" style="font-size:12px; font-weight:600; color:#ffd700; text-decoration:none;">View all &rarr;</a>
                                    </div>
                                    <p style="margin:4px 0 0; font-size:11px; color:rgba(255,255,255,.7);">Remove an alert with the &times; button. You can restore it from Maintenance Needed.</p>
                                </div>

                                <!-- List -->
                                <div id="maintenanceBellList" style="max-height:360px; overflow-y:auto; background:#fff;">
                                    <div style="padding:28px 16px; text-align:center; font-size:12px; color:#9ca3af;">Loading&hellip;</div>
                                </div>

                                <!-- Undo snackbar -->
                                <div id="maintenanceBellToast" style="display:none; align-items:center; justify-content:space-between; gap:10px; padding:10px 14px; background:#111827; color:#fff; font-size:12px;">
                                    <span id="maintenanceBellToastText">Removed.</span>
                                    <button type="button" id="maintenanceBellUndo" style="background:none; border:0; color:#ffd700; font-weight:700; font-size:12px; cursor:pointer;">Undo</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <nav class="flex-1 px-2 py-2 overflow-y-auto">
                        <!-- Dashboard section -->
                        <div class="mb-1">
                            <a href="{{ route('motorpool.dashboard') }}" class="@if(request()->routeIs('motorpool.dashboard')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                                <svg class="h-5 w-5 @if(request()->routeIs('motorpool.dashboard')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span class="text-sm font-bold @if(request()->routeIs('motorpool.dashboard')) text-[#1e6031] @endif">Dashboard</span>
                            </a>
                        </div>
                        
                        <!-- Travel Orders section -->
                        <div class="mb-1">
                            <a href="{{ route('motorpool.travel-orders.index') }}" class="@if(request()->routeIs('motorpool.travel-orders.*')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                                <svg class="h-5 w-5 @if(request()->routeIs('motorpool.travel-orders.*')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span class="text-sm font-bold @if(request()->routeIs('motorpool.travel-orders.*')) text-[#1e6031] @endif">Travel Orders</span>
                            </a>
                        </div>

                        <!-- Itinerary section -->
                        <div class="mb-1">
                            <a href="{{ route('itinerary.index') }}" class="@if(request()->routeIs('itinerary.*')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                                <svg class="h-5 w-5 @if(request()->routeIs('itinerary.*')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <span class="text-sm font-bold @if(request()->routeIs('itinerary.*')) text-[#1e6031] @endif">Itinerary</span>
                            </a>
                        </div>

                        <!-- Trip Tickets section -->
                        <div class="mb-1">
                            <a href="{{ route('trip-tickets.index') }}" class="@if(request()->routeIs('trip-tickets.*')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                                <svg class="h-5 w-5 @if(request()->routeIs('trip-tickets.*')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                </svg>
                                <span class="text-sm font-bold @if(request()->routeIs('trip-tickets.*')) text-[#1e6031] @endif">Trip Tickets</span>
                            </a>
                        </div>

                        <!-- Driver Monitoring section -->
                        <div class="mb-1">
                            @php
                                $liveOngoingCount = \App\Models\TripTicket::where('status', 'On-Going')->count();
                            @endphp
                            <a href="{{ route('trip-tickets.ongoing-map') }}" class="@if(request()->routeIs('trip-tickets.ongoing-map')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                                <svg class="h-5 w-5 @if(request()->routeIs('trip-tickets.ongoing-map')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-sm font-bold @if(request()->routeIs('trip-tickets.ongoing-map')) text-[#1e6031] @endif">Driver Monitoring</span>
                                @if($liveOngoingCount > 0)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 animate-pulse" title="Trips currently on the road">
                                        <i class="fas fa-circle mr-1" style="font-size:6px;"></i>{{ $liveOngoingCount }}
                                    </span>
                                @endif
                            </a>
                        </div>

                        <!-- Vehicles section -->
                        <div class="mb-1">
                            @php
                                $activeMaintenanceCount = \App\Models\VehicleMaintenance::whereIn('status', ['Need Maintenance', 'Under Maintenance'])->count();
                                $oilChangeCount = \App\Models\Vehicle::getVehiclesNeedingOilChangeCount();
                            @endphp
                            <a href="{{ route('vehicles.index') }}" class="@if(request()->routeIs('vehicles.*')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                                <svg class="h-5 w-5 @if(request()->routeIs('vehicles.*')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm font-bold @if(request()->routeIs('vehicles.*')) text-[#1e6031] @endif">Vehicles</span>
                                @if($activeMaintenanceCount > 0)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="Vehicles with active maintenance issues">
                                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $activeMaintenanceCount }}
                                    </span>
                                @endif
                                @if($oilChangeCount > 0)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-300" title="Vehicles need oil change">
                                        <i class="fas fa-oil-can mr-1"></i>{{ $oilChangeCount }}
                                    </span>
                                @endif
                            </a>
                        </div>

                        <!-- Maintenance Needed section -->
                        <div class="mb-1">
                            <a href="{{ route('vehicle-maintenance.needed') }}" class="@if(request()->routeIs('vehicle-maintenance.needed')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                                <svg class="h-5 w-5 @if(request()->routeIs('vehicle-maintenance.needed')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-sm font-bold @if(request()->routeIs('vehicle-maintenance.needed')) text-[#1e6031] @endif">Maintenance Needed</span>
                                @php $maintenanceNeededTotal = \App\Models\VehicleMaintenance::openCountNotDismissedBy(auth()->id()) + $oilChangeCount; @endphp
                                @if($maintenanceNeededTotal > 0)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="Total items needing maintenance attention">
                                        {{ $maintenanceNeededTotal }}
                                    </span>
                                @endif
                            </a>
                            <a href="{{ route('motorpool.dashcams.index') }}" class="@if(request()->routeIs('motorpool.dashcams.*')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                                <i class="fas fa-video h-5 w-5 mr-2 flex-shrink-0 text-center text-[#004d26]"></i>
                                <span class="text-sm font-bold @if(request()->routeIs('motorpool.dashcams.*')) text-[#1e6031] @endif">Dashcams</span>
                            </a>
                        </div>

                        <!-- Drivers section -->
                        <div class="mb-1">
                            <a href="{{ route('drivers.index') }}" class="@if(request()->routeIs('drivers.*')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                                <svg class="h-5 w-5 @if(request()->routeIs('drivers.*')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span class="text-sm font-bold @if(request()->routeIs('drivers.*')) text-[#1e6031] @endif">Drivers</span>
                            </a>
                        </div>
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
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden" id="main-content" style="margin-left: 16rem;">
            @isset($header)
                <header class="bg-white shadow flex-shrink-0">
                    <div class="max-w-7xl mx-auto py-2 px-4 sm:px-6 lg:px-8 text-sm">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex-1 overflow-y-auto">
                @yield('content')
            </main>
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
                    const navIcons = document.querySelectorAll('#sidebar a.flex svg, #sidebar .motorpool-toggle svg:first-child, #sidebar .asset-toggle svg:first-child');
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
                
                // Center the icons
                const links = document.querySelectorAll('#sidebar a.flex, #sidebar button');
                links.forEach(link => {
                    link.classList.add('justify-center', 'px-0');
                    link.classList.remove('px-3');
                });
                
                // Hide dropdown arrows
                const dropdownArrows = document.querySelectorAll('#sidebar .motorpool-toggle svg:last-child, #sidebar .asset-toggle svg:last-child');
                dropdownArrows.forEach(arrow => {
                    arrow.style.display = 'none';
                });
                
                // Hide sub-menus completely
                const subMenus = document.querySelectorAll('#sidebar .motorpool-submenu, #sidebar .asset-submenu');
                subMenus.forEach(menu => {
                    menu.classList.add('hidden');
                    menu.classList.remove('block');
                });
                
                // Add click event to navigation icons to expand sidebar (excluding toggle button)
                const navIcons = document.querySelectorAll('#sidebar a.flex svg, #sidebar .motorpool-toggle svg:first-child, #sidebar .asset-toggle svg:first-child');
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
                
                // Resize calendar if it exists
                const calendarEl = document.getElementById('tripCalendar');
                if (calendarEl) {
                    setTimeout(() => {
                        window.dispatchEvent(new Event('resize'));
                    }, 300);
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
                
                // Reset link styles
                const links = document.querySelectorAll('#sidebar a.flex, #sidebar button');
                links.forEach(link => {
                    link.classList.remove('justify-center', 'px-0');
                    link.classList.add('px-3');
                });
                
                // Show dropdown arrows
                const dropdownArrows = document.querySelectorAll('#sidebar .motorpool-toggle svg:last-child, #sidebar .asset-toggle svg:last-child');
                dropdownArrows.forEach(arrow => {
                    arrow.style.display = 'block';
                });
                
                // Show sub-menus (but keep their hidden/show state based on previous toggle)
                const subMenus = document.querySelectorAll('#sidebar .motorpool-submenu, #sidebar .asset-submenu');
                subMenus.forEach(menu => {
                    // Don't automatically show them - let the toggle state control visibility
                    // Just remove the forced hidden style from collapse
                });
                
                // Remove click event from navigation icons to expand sidebar
                const navIcons = document.querySelectorAll('#sidebar a.flex svg, #sidebar .motorpool-toggle svg:first-child, #sidebar .asset-toggle svg:first-child');
                navIcons.forEach(icon => {
                    icon.removeEventListener('click', expandSidebar);
                });
                
                // Update main content margin
                if (mainContent) {
                    mainContent.style.marginLeft = '16rem';
                }
                
                // Resize calendar if it exists
                const calendarEl = document.getElementById('tripCalendar');
                if (calendarEl) {
                    setTimeout(() => {
                        window.dispatchEvent(new Event('resize'));
                    }, 300);
                }
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
                <h1 style="font-size: 18px; font-weight: bold; color: white; margin: 0;">Motorpool Admin</h1>
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

<!-- Maintenance Notification Bell -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const bellBtn = document.getElementById('maintenanceBellBtn');
        const bellBadge = document.getElementById('maintenanceBellBadge');
        const bellCount = document.getElementById('maintenanceBellCount');
        const bellDropdown = document.getElementById('maintenanceBellDropdown');
        const bellList = document.getElementById('maintenanceBellList');
        const bellWrapper = document.getElementById('maintenance-bell-wrapper');
        const toast = document.getElementById('maintenanceBellToast');
        const toastText = document.getElementById('maintenanceBellToastText');
        const toastUndo = document.getElementById('maintenanceBellUndo');

        if (!bellBtn) return;

        const notificationsUrl = '{{ route("vehicle-maintenance.notifications") }}';
        const oilDismissUrl = '{{ route("motorpool.api.vehicles.dismiss-oil-change-notification") }}';
        const maintDismissUrl = '{{ route("vehicle-maintenance.notification.dismiss", "__ID__") }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        };

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        function toneFor(item) {
            if (item.type === 'oil_change') {
                return { bg: '#FFF4E5', fg: '#B45309', bar: '#F59E0B', icon: 'fa-oil-can', label: 'Oil change due' };
            }
            if (item.status === 'Need Maintenance') {
                return { bg: '#FDECEC', fg: '#B91C1C', bar: '#EF4444', icon: 'fa-wrench', label: 'Need maintenance' };
            }
            return { bg: '#E8F1FB', fg: '#1D4ED8', bar: '#3B82F6', icon: 'fa-wrench', label: escapeHtml(item.status) };
        }

        function renderItem(item) {
            const isOil = item.type === 'oil_change';
            const id = isOil ? item.vehicle_id : item.id;
            const tone = toneFor(item);
            const title = isOil
                ? `${escapeHtml(item.plate_number)} needs an oil change`
                : `${escapeHtml(item.plate_number)} &middot; ${escapeHtml(item.status)}`;
            const sub = isOil
                ? `${escapeHtml(item.model || '')} &middot; ${escapeHtml(String(item.kilometers_since_service))} km since last service`
                : escapeHtml(item.nature_of_work || 'No description');
            const meta = isOil
                ? `Mileage ${escapeHtml(String(item.mileage ?? ''))} km`
                : `Started ${escapeHtml(item.date_started || 'N/A')}`;

            return `
                <div class="notif-item" data-kind="${isOil ? 'oil' : 'maint'}" data-id="${id}"
                     style="display:flex; gap:12px; padding:12px 12px 12px 16px; border-bottom:1px solid #f1f5f9; position:relative;">
                    <span style="position:absolute; left:0; top:0; bottom:0; width:3px; background:${tone.bar};"></span>
                    <div style="flex:0 0 36px; height:36px; border-radius:10px; background:${tone.bg}; color:${tone.fg}; display:flex; align-items:center; justify-content:center; margin-top:2px;">
                        <i class="fas ${tone.icon}" style="font-size:14px;"></i>
                    </div>
                    <a href="${item.url}" class="notif-open" style="flex:1; min-width:0; text-decoration:none; color:inherit;">
                        <div class="notif-title" style="font-size:13px; font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${title}</div>
                        <div style="font-size:12px; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px;">${sub}</div>
                        <div style="display:flex; align-items:center; gap:8px; margin-top:7px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; padding:2px 8px; border-radius:999px; background:${tone.bg}; color:${tone.fg};">${tone.label}</span>
                            <span style="font-size:11px; color:#9ca3af;">${meta}</span>
                        </div>
                    </a>
                    <button type="button" class="notif-remove" aria-label="Remove notification" title="Remove"
                            style="flex:0 0 auto; width:28px; height:28px; margin-top:2px; border:0; border-radius:999px; background:transparent; color:#9ca3af; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>`;
        }

        function emptyState() {
            return `
                <div style="padding:28px 16px; text-align:center;">
                    <div style="width:44px; height:44px; margin:0 auto 10px; border-radius:999px; background:#DFF0D8; color:#1e6031; display:flex; align-items:center; justify-content:center;">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div style="font-size:13px; font-weight:600; color:#1e6031;">All clear</div>
                    <div style="font-size:12px; color:#9ca3af; margin-top:2px;">Nothing needs maintenance right now.</div>
                </div>`;
        }

        function setCount(total) {
            if (total > 0) {
                bellBadge.textContent = total > 99 ? '99+' : total;
                bellBadge.classList.remove('hidden');
                bellCount.textContent = total + (total === 1 ? ' item' : ' items');
                bellCount.style.display = 'inline-block';
            } else {
                bellBadge.classList.add('hidden');
                bellCount.style.display = 'none';
            }
        }

        function showToast(message, undo) {
            toastText.textContent = message;
            toast.style.display = 'flex';
            toastUndo.onclick = function () {
                toast.style.display = 'none';
                undo().then(loadNotifications);
            };
            clearTimeout(showToast._timer);
            showToast._timer = setTimeout(() => { toast.style.display = 'none'; }, 5000);
        }

        function removeItem(row) {
            const isOil = row.dataset.kind === 'oil';
            const id = row.dataset.id;

            row.style.transition = 'opacity .2s, transform .2s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(12px)';

            const dismiss = isOil
                ? fetch(oilDismissUrl, { method: 'POST', headers, body: JSON.stringify({ vehicle_id: id, action: 'ignore' }) })
                : fetch(maintDismissUrl.replace('__ID__', id), { method: 'POST', headers });

            const undo = () => isOil
                ? fetch(oilDismissUrl, { method: 'POST', headers, body: JSON.stringify({ vehicle_id: id, action: 'restore' }) })
                : fetch(maintDismissUrl.replace('__ID__', id), { method: 'DELETE', headers });

            dismiss
                .then(res => res.json())
                .then(() => {
                    showToast('Notification removed.', undo);
                    loadNotifications();
                })
                .catch(() => {
                    row.style.opacity = '1';
                    row.style.transform = '';
                    showToast('Could not remove. Please try again.', () => Promise.resolve());
                });
        }

        function loadNotifications() {
            fetch(notificationsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => {
                    const total = data.total_count || 0;
                    setCount(total);

                    const items = [...(data.oil_change || []), ...(data.maintenance || [])];

                    if (items.length === 0) {
                        bellList.innerHTML = emptyState();
                        return;
                    }

                    bellList.innerHTML = items.map(renderItem).join('');

                    bellList.querySelectorAll('.notif-remove').forEach(btn => {
                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            removeItem(this.closest('.notif-item'));
                        });
                    });
                })
                .catch(() => {
                    bellList.innerHTML = '<div style="padding:24px 16px; text-align:center; font-size:12px; color:#dc2626;">Failed to load notifications.</div>';
                });
        }

        bellBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            bellDropdown.classList.toggle('hidden');
            if (!bellDropdown.classList.contains('hidden')) {
                loadNotifications();
            }
        });

        document.addEventListener('click', function (e) {
            if (bellWrapper && !bellWrapper.contains(e.target)) {
                bellDropdown.classList.add('hidden');
            }
        });

        // Initial badge load + periodic refresh (every 2 minutes)
        loadNotifications();
        setInterval(loadNotifications, 120000);
    });
</script>
</body>
</html>
