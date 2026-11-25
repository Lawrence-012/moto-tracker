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
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: false
                }).then(() => {
                    // Redirect to cancelled tab after success message closes
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('tab', 'cancelled');
                    window.location.href = window.location.pathname + '?tab=cancelled' + (urlParams.toString() ? '&' + urlParams.toString() : '');
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
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="overflow-y-auto">
                    @yield('content')
                    
                </main>
            </div>
        </div>
    </body>
</html>