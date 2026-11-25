@auth
    @if(auth()->user()->role === 'admin' || auth()->user()->hasRole('admin'))
        <x-admin-layout>
            <x-slot name="header-title">
                {{ __('Profile') }}
            </x-slot>
            
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>

                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>
        </x-admin-layout>
    @elseif(auth()->user()->isMotorpoolAdmin())
        <!DOCTYPE html>
        <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="csrf-token" content="{{ csrf_token() }}">

            <title>{{ config('app.name', 'Laravel') }}</title>

            <!-- Fonts -->
            <link rel="preconnect" href="https://fonts.bunny.net">
            <link href="https://fonts.bunny.net/css?family=libre-franklin:400,500,600&display=swap" rel="stylesheet" />

            <!-- Scripts -->
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        </head>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Show success message if exists
                @if(session('success'))
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '{{ session('success') }}',
                        toast: false,
                        position: 'center',
                        showConfirmButton: true,
                        confirmButtonText: 'OK',
                        timer: 5000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });
                @endif
                
                // Show error message if exists
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
            <div class="flex min-h-screen bg-gray-50">
                <!-- Collapsible Sidebar - Motorpool Admin -->
                <div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 shadow-lg z-50 transition-all duration-300">
                    <div class="flex flex-col h-full">
                        <!-- Logo/Header Section -->
                        <div class="flex items-center justify-between h-12 bg-[#1e6031] text-white px-4">
                            <h1 class="text-lg font-bold" id="sidebar-title"><span>Motorpool Admin</span></h1>
                            <button id="toggleSidebar" class="text-sm focus:outline-none">
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
                                    <p class="text-xs font-medium text-gray-700 mb-0">{{ auth()->user()->name ?? 'Motorpool Admin' }}</p>
                                    <a href="{{ route('profile.edit') }}" class="text-xs font-medium text-[#1e6031] hover:text-[#007d31] truncate block mt-0">
                                        Manage Profile
                                    </a>
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

                                <!-- Vehicles section -->
                                <div class="mb-1">
                                    <a href="{{ route('vehicles.index') }}" class="@if(request()->routeIs('vehicles.*')) bg-green-100 text-[#1e6031] @else text-gray-700 hover:bg-gray-100 @endif flex items-center px-3 py-2 rounded-lg transition-colors duration-200 mb-1">
                                        <svg class="h-5 w-5 @if(request()->routeIs('vehicles.*')) text-[#1e6031] @else text-[#004d26] @endif mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="text-sm font-bold @if(request()->routeIs('vehicles.*')) text-[#1e6031] @endif">Vehicles</span>
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
                <div class="flex-1 overflow-hidden" id="main-content" style="margin-left: 16rem;">
                    <!-- Page Heading -->
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                                {{ __('Profile') }}
                            </h2>
                        </div>
                    </header>

                    <!-- Page Content -->
                    <main class="overflow-y-auto">
                        <div class="py-12">
                            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                                    <div class="max-w-xl">
                                        @include('profile.partials.update-profile-information-form')
                                    </div>
                                </div>

                                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                                    <div class="max-w-xl">
                                        @include('profile.partials.update-password-form')
                                    </div>
                                </div>

                                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                                    <div class="max-w-xl">
                                        @include('profile.partials.delete-user-form')
                                    </div>
                                </div>
                            </div>
                        </div>
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
        </body>
        </html>
    @else
        <!DOCTYPE html>
        <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="csrf-token" content="{{ csrf_token() }}">

            <title>{{ config('app.name', 'Laravel') }}</title>

            <!-- Fonts -->
            <link rel="preconnect" href="https://fonts.bunny.net">
            <link href="https://fonts.bunny.net/css?family=libre-franklin:400,500,600&display=swap" rel="stylesheet" />

            <!-- Scripts -->
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        </head>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Show success message if exists
                @if(session('success'))
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '{{ session('success') }}',
                        toast: false,
                        position: 'center',
                        showConfirmButton: true,
                        confirmButtonText: 'OK',
                        timer: 5000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });
                @endif
                
                // Show error message if exists
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
            <div class="flex min-h-screen bg-gray-50">
                <!-- Collapsible Sidebar -->
                @include('components.employee-sidebar')
                
                <!-- Main Content -->
                <div class="flex-1 overflow-hidden" id="main-content" style="margin-left: 16rem;">
                    <!-- Page Heading -->
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                                {{ __('Profile') }}
                            </h2>
                        </div>
                    </header>

                    <!-- Page Content -->
                    <main class="overflow-y-auto">
                        <div class="py-12">
                            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                                    <div class="max-w-xl">
                                        @include('profile.partials.update-profile-information-form')
                                    </div>
                                </div>

                                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                                    <div class="max-w-xl">
                                        @include('profile.partials.update-password-form')
                                    </div>
                                </div>

                                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                                    <div class="max-w-xl">
                                        @include('profile.partials.delete-user-form')
                                    </div>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </body>
        </html>
    @endif
@endauth

@guest
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Profile') }}
            </h2>
        </x-slot>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </x-app-layout>
@endguest

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>