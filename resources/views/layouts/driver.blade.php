<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('page_title', 'MPIS')</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon">

    <!-- PWA Settings -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#1e6031">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Moto-Track">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/index.global.min.js"></script>

    <!-- Laravel Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Page-Specific Styles -->
    @stack('styles')

    <!-- Global Driver Font / Accessibility Scaling -->
    <style>
        /*
         * Keep Poppins as the driver's font, but allow the browser/phone's
         * accessibility text-size setting to scale the interface naturally.
         *
         * IMPORTANT:
         * Do NOT use -webkit-text-size-adjust: 100%; here because that can
         * prevent the phone's larger-text setting from being respected.
         */
        html {
            font-size: 100%;
            text-size-adjust: auto;
            -webkit-text-size-adjust: auto;
            -moz-text-size-adjust: auto;
        }

        body,
        body * {
            font-family: 'Poppins', sans-serif;
        }

        /*
         * Use the phone/browser's root text size as the basis for the
         * driver's interface. Tailwind rem-based text classes will therefore
         * scale together with the device's preferred text size.
         */
        .driver-font-scale {
            font-size: 1rem;
        }

        /* Prevent horizontal overflow when the driver uses very large text. */
        #main-content,
        #main-content main {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        /* Let long labels/buttons wrap instead of becoming clipped. */
        button,
        a,
        label,
        p,
        span,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            overflow-wrap: anywhere;
        }

        @media (max-width: 639px) {
            /* Never force the mobile browser back to a fixed text scale. */
            html {
                text-size-adjust: auto;
                -webkit-text-size-adjust: auto;
            }

            /* Give the larger phone font room to wrap naturally. */
            #main-content main {
                width: 100%;
            }
        }
    </style>
</head>

<body class="antialiased h-full">
    <!-- Session Alert Notifications -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: @json(session('success')),
                    toast: false,
                    position: 'center',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: @json(session('error')),
                    toast: false,
                    position: 'center',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    timer: 7000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
            @endif
        });
    </script>

    <!-- Main Content Container -->
    <div class="flex min-h-screen bg-gray-50">
        <div class="flex-1 overflow-hidden" id="main-content">
            
            <!-- Page Header -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @elseif(View::hasSection('header'))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        @yield('header')
                    </div>
                </header>
            @endisset

            <!-- Page Body -->
            <main class="overflow-y-auto pb-24">
                @yield('content')
            </main>

        </div>
    </div>

    <!-- Fixed Bottom Navigation Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50">
        <div class="max-w-md mx-auto flex items-center justify-around px-3 py-2.5">
            <!-- Trips Navigation
            <a href="{{ route('driver.trips') }}"
               class="flex-1 mx-1 text-center py-2 rounded-full text-xs font-semibold {{ request()->routeIs('driver.trips') ? 'text-white font-bold' : 'text-gray-600 border border-gray-300' }}"
               style="{{ request()->routeIs('driver.trips') ? 'background:#1e6031;' : '' }}">
                🚐 Trips
            </a> -->

            <!-- Schedule Navigation
            <a href="{{ route('driver.dashboard') }}"
               class="flex-1 mx-1 text-center py-2 rounded-full text-xs font-semibold {{ request()->routeIs('driver.dashboard') ? 'text-white font-bold' : 'text-gray-600 border border-gray-300' }}"
               style="{{ request()->routeIs('driver.dashboard') ? 'background:#1e6031;' : '' }}">
                📅 Schedule of Trips
            </a> -->

        </div>
    </div>

    <!-- Driver Location Tracker Partial -->

    <!-- Automated Location Off Handler on Logout -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Intercept any logout forms across child views
            const logoutForms = document.querySelectorAll('form[action*="logout"]');

            logoutForms.forEach(form => {
                form.addEventListener('submit', function () {
                    // 1. Clear HTML5 Geolocation API active watcher
                    if (window.locationWatchId) {
                        navigator.geolocation.clearWatch(window.locationWatchId);
                    }

                    // 2. Clear any active polling interval loops
                    if (window.locationInterval) {
                        clearInterval(window.locationInterval);
                    }

                    // 3. Clear stored client-side tracking indicators
                    localStorage.removeItem('is_tracking_active');
                    localStorage.removeItem('driver_location_enabled');
                    sessionStorage.removeItem('driver_location_active');
                });
            });
        });
    </script>

    <!-- Page-Specific Scripts -->
    @stack('scripts')
</body>
</html>