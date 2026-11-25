@extends('layouts.guest')

@section('content')

<!-- HEADER -->
<header class="w-full bg-[#2d8a4e] backdrop-blur-sm shadow-md">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 sm:h-20">
            <!-- Logo -->
            <div class="flex items-center gap-2 sm:gap-4">
                <img src="{{ asset('images/clsu-logo2.png') }}" 
                     alt="CLSU Logo" 
                     class="h-10 w-auto sm:h-12 md:h-14 lg:h-16 object-contain">
                
                <div class="flex flex-col justify-center min-w-[120px] sm:min-w-[150px] md:min-w-[180px] lg:min-w-[200px]">
                    <h1 class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-white leading-tight">
                        MOTOR POOL
                    </h1>
                    <h2 class="text-xs sm:text-sm md:text-base lg:text-base font-semibold text-white leading-tight">
                        INFORMATION SYSTEM
                    </h2>
                </div>
            </div>
            
            <!-- Navigation Buttons -->
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="{{ route('login') }}" 
                   class="px-2 py-0.5 text-[10px] font-semibold text-white border border-white rounded-md hover:bg-white hover:text-[#1e6031] transition">
                    Login
                </a>
            </div>
        </div>
    </div>
</header>

<style>
html {
    scroll-behavior: smooth;
}

/* Page load animation */
@keyframes slideDownFade {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<div class="about-content">

<div class="w-full bg-gray-50 py-8 sm:py-12 md:py-16">
    <div class="max-w-5xl mx-auto px-3 sm:px-4 md:px-6">
        <!-- Title -->
        <div class="text-center mb-6 sm:mb-8">
            <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-[#1e6031] mb-2 sm:mb-3">
                Motor Pool Information System
            </h2>
            <div class="w-16 sm:w-20 md:w-24 h-1 bg-[#1e6031] mx-auto"></div>
        </div>

        <!-- Content -->
        <div class="space-y-4 sm:space-y-6 text-gray-700 leading-relaxed text-center">
            <p class="text-justify max-w-4xl mx-auto text-sm sm:text-base px-2">
                The Motor Pool Unit is one of the service divisions under the Physical Plant and Site Development Services (PPSDS) of Central Luzon State University (CLSU). It is responsible for the management, maintenance, and operation of the university's transport vehicles. The unit ensures that transportation services are available to support the mobility requirements of faculty, staff, students, and official guests in relation to academic, administrative, and institutional activities.
            </p>

            <p class="text-justify max-w-4xl mx-auto text-sm sm:text-base px-2">
                Its functions include vehicle scheduling and dispatching, preventive maintenance, repair, and the implementation of safety measures to ensure reliable and efficient transportation. By maintaining a well-managed fleet, the Motor Pool contributes to the smooth operation of the university and supports its overall mission and services.
            </p>
        </div>



        <!-- Contact Information -->
        <div class="mt-6 sm:mt-8 pt-6 sm:pt-8 border-t-2 border-[#1e6031]/20">
            <h3 class="text-base sm:text-lg font-bold text-[#1e6031] mb-3 sm:mb-4 text-center px-2">
                Contact Information
            </h3>
                    <!-- Unit Head Information -->
        <div class="mt-8 sm:mt-10">
            @if($unitHeads && $unitHeads->isNotEmpty())
                @php
                    $transportationUnitHeads = $unitHeads->filter(function($unitHead) {
                        return str_contains(strtolower($unitHead['office'] ?? ''), 'transportation') || 
                               str_contains(strtolower($unitHead['unit'] ?? ''), 'transportation');
                    });
                @endphp
                
                @if($transportationUnitHeads->isNotEmpty())
                    <div class="max-w-2xl mx-auto px-2">
                        @foreach($transportationUnitHeads as $unitHead)
                            <div class="text-center mb-4 sm:mb-6 leading-tight">
                                <p class="font-bold text-[#1e6031] text-base sm:text-lg">
                                    {{ $unitHead['name'] }}
                                </p>
                                @if($unitHead['email'])
                                    <p class="text-xs sm:text-sm text-gray-600 break-all">
                                        {{ $unitHead['email'] }}
                                    </p>
                                @endif
                                <p class="text-xs sm:text-sm text-gray-700 font-semibold">
                                    {{ $unitHead['position'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
          
        </div>
    </div>
</div>
</div>

@endsection
