@extends('layouts.guest')

@section('content')
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
         <a href="{{ route('about') }}" 
   class="px-2 py-0.5 text-[10px] font-semibold text-white border border-white rounded-md hover:bg-white hover:text-[#1e6031] transition">
    About Us
</a>
            </div>
        </div>
    </div>
</header>
<div class="h-[calc(100vh-80px)] flex items-center justify-center overflow-hidden">

    <div class="w-full max-w-6xl rounded-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2 mx-3 sm:mx-4 md:mx-auto">

        <!-- LEFT SIDE : LOGO / BRANDING -->
        <div class="flex items-center justify-center p-6 sm:p-8 md:p-10 lg:p-12">

            <img src="{{ asset('images/mpis_bg.png') }}"
                 id="logo"
                 class="max-w-[160px] sm:max-w-[200px] md:max-w-xs lg:max-w-md w-full h-auto cursor-pointer"
                 style="transition: transform 0.3s ease;">

        </div>

   <style>
#logo{
    transition: transform 0.3s ease;
}

#logo:hover{
    transform: translateY(-5px);
}
</style>

<!-- RIGHT SIDE : LOGIN FORM -->
<div class="flex flex-col justify-center items-center p-4 sm:p-8 md:p-10 lg:p-12">

    <!-- LOGIN CARD -->
<div class="w-full max-w-md bg-gray-50 rounded-xl shadow-lg p-5 sm:p-8 md:p-10">
        <div class="mb-4 sm:mb-6 text-center">
            <h2 class="text-base sm:text-xl font-bold text-[#1e6031]">
                Login to your account
            </h2>

            <p class="text-xs text-gray-500 mt-1">
                Enter your credentials to continue
            </p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-4 sm:space-y-5 md:space-y-6">
            @csrf

            <!-- EMAIL ROW -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-[#1e6031] mb-2">
                    Email Address
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="email@clsu2.edu.ph"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] outline-none transition text-sm">
            </div>

            <!-- PASSWORD ROW -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-[#1e6031] mb-2">
                    Password
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="w-full px-4 py-3 border @error('email') border-red-500 @enderror rounded-lg focus:ring-2 focus:ring-[#1e6031] focus:border-[#1e6031] outline-none transition text-sm">
                
                @error('email')
                    <p class="text-red-500 text-xs mt-1">Email and password doesn't match.</p>
                @enderror
            </div>

            <!-- SIGN IN BUTTON ROW -->
            <div>
                <button type="submit"
                    class="w-full bg-[#1e6031] hover:bg-[#164f2a] hover:shadow-lg text-white font-semibold py-3 sm:py-3.5 rounded-lg transition text-base">
                    Sign In
                </button>
            </div>

        </form>

    </div>

    <!-- FOOTER (NOW TRANSPARENT AREA) -->
    <div class="mt-3 sm:mt-6 text-xs text-gray-500 text-center pb-4 sm:pb-8">
        © {{ date('Y') }} Central Luzon State University  
        <br>
        Motor Pool Information System
    </div>

</div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add custom validation handling
    const loginForm = document.querySelector('form[method="POST"]');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            // Client-side validation
            if (!email) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Email Required',
                    text: 'Please enter your email address',
                    confirmButtonColor: '#1e6031',
                    confirmButtonText: 'OK'
                });
                return false;
            }

            if (!password) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Password Required',
                    text: 'Please enter your password',
                    confirmButtonColor: '#1e6031',
                    confirmButtonText: 'OK'
                });
                return false;
            }
        });
    }
});
</script>

@endsection