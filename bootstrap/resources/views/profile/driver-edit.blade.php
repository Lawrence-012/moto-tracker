<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>MPIS - Driver Profile</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=libre-franklin:400,500,600,700,800&display=swap"
        rel="stylesheet"
    />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* =========================================================
           GLOBAL
        ========================================================= */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            font-family: 'Libre Franklin', sans-serif;
            background: #f7f8f9;
            color: #1f2937;
        }


        /* =========================================================
           SIDEBAR
        ========================================================= */

        :root {
            --sidebar-width: 270px;
        }

        #sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 50;
            background: white;
            border-right: 1px solid #e5e7eb;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
            transition: width 0.3s ease;
        }

        #sidebar-header {
            height: 70px;
            background: #1e6031;
            color: white;
        }


        /* =========================================================
           MAIN CONTENT
        ========================================================= */

        #main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        #page-header {
            height: 70px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
        }

        #page-wrapper {
            width: 100%;
            max-width: 1250px;
            margin: 0 auto;
            padding: 28px 32px 50px;
        }


        /* =========================================================
           PROFILE GRID
        ========================================================= */

        .profile-grid {
            display: flex;
            flex-direction: column;
            max-width: 860px;
            margin: 0 auto;
            gap: 24px;
        }

        .profile-section {
            display: flex;
            flex-direction: column;
        }

        /* =========================================================
           CARDS
        ========================================================= */

        .profile-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 26px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .summary-card {
            min-height: 390px;
            position: relative;
        }

        .password-summary-card {
            min-height: 180px;
            position: relative;
        }

        .form-card {
            width: 100%;
        }

        /* Hidden forms (shown via JS toggle when EDIT is clicked) */
        .hidden {
            display: none !important;
        }


        /* =========================================================
           CARD HEADINGS
        ========================================================= */

        .card-title {
            color: #1e6031;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .card-subtitle {
            color: #4b5563;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .section-title {
            color: #111827;
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 8px;
        }


        /* =========================================================
           SUMMARY INFORMATION
        ========================================================= */

        .info-list {
            width: 100%;
        }

        .info-row {
            display: grid;
            grid-template-columns: 145px minmax(0, 1fr);
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 800;
            color: #111827;
        }

        .info-value {
            color: #4b5563;
            word-break: break-word;
        }


        /* =========================================================
           FORM
        ========================================================= */

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 6px;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            padding: 10px 12px;
            font-size: 14px;
            color: #111827;
            background: white;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input {
            height: 42px;
        }

        .form-textarea {
            min-height: 85px;
            resize: vertical;
        }

        .form-input:focus,
        .form-textarea:focus {
            border-color: #1e6031;
            box-shadow: 0 0 0 2px rgba(30, 96, 49, 0.12);
        }

        .form-input:disabled {
            background: #e5e7eb;
            color: #6b7280;
        }

        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }


        /* =========================================================
           BUTTONS
        ========================================================= */

        .green-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            background: #1e6031;
            color: white;

            border: none;
            border-radius: 999px;

            padding: 10px 22px;

            font-size: 13px;
            font-weight: 800;

            cursor: pointer;

            transition: all 0.2s ease;
        }

        .green-button:hover {
            background: #164f2a;
            transform: translateY(-1px);
        }

        .cancel-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            background: transparent;
            color: #6b7280;

            border: 1px solid #d1d5db;
            border-radius: 999px;

            padding: 9px 20px;

            font-size: 13px;
            font-weight: 700;

            cursor: pointer;

            transition: all 0.2s ease;
        }

        .cancel-button:hover {
            background: #f3f4f6;
        }

        .edit-button {
            position: absolute;
            bottom: 24px;
            right: 24px;
        }

        .save-button {
            border-radius: 999px;
            padding: 9px 22px;
        }


        /* =========================================================
           SIDEBAR NAVIGATION
        ========================================================= */

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;

            width: calc(100% - 24px);
            margin: 0 12px 8px;

            padding: 12px 16px;

            border-radius: 12px;

            text-decoration: none;

            color: #374151;

            font-size: 15px;
            font-weight: 700;

            transition: all 0.2s ease;
        }

        .sidebar-nav-link:hover {
            background: #f3f4f6;
        }

        .sidebar-nav-link.active {
            background: #d7f9e4;
            color: #1e6031;
        }

        .sidebar-nav-link svg {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }


        /* =========================================================
           SIDEBAR USER
        ========================================================= */

        .sidebar-user {
            padding: 22px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .sidebar-user-inner {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #f1f3f5;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #6b7280;
            flex-shrink: 0;
        }

        .sidebar-user-name {
            font-size: 15px;
            color: #374151;
            font-weight: 500;
        }

        .sidebar-profile-link {
            color: #1e6031;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }


        /* =========================================================
           SIDEBAR LOGOUT
        ========================================================= */

        .sidebar-logout {
            padding: 18px 12px;
            border-top: 1px solid #e5e7eb;
        }

        .logout-button {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 12px 16px;

            border: none;
            background: transparent;

            color: #374151;

            font-size: 15px;
            font-weight: 600;

            border-radius: 10px;

            cursor: pointer;
        }

        .logout-button:hover {
            background: #f3f4f6;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1200px) {

            #page-wrapper {
                padding-left: 24px;
                padding-right: 24px;
            }

            .summary-card {
                min-height: auto;
                padding-bottom: 85px;
            }

            .password-summary-card {
                min-height: auto;
                padding-bottom: 85px;
            }

        }


        .mobile-dashboard-button {
            display: none;
        }

        @media (max-width: 768px) {

            .mobile-dashboard-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                width: 100%;
                margin-bottom: 16px;
                padding: 11px 16px;
                border-radius: 10px;
                background: #1e6031;
                color: #ffffff;
                text-decoration: none;
                font-size: 13px;
                font-weight: 700;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
                transition: background 0.2s ease, transform 0.2s ease;
            }

            .mobile-dashboard-button:hover {
                background: #164f2a;
            }

            .mobile-dashboard-button:active {
                transform: translateY(1px);
            }

            .mobile-dashboard-button svg {
                width: 18px;
                height: 18px;
                flex-shrink: 0;
            }

            :root {
                --sidebar-width: 0px;
            }

            #sidebar {
                width: 0;
                overflow: hidden;
            }

            #main-content {
                margin-left: 0;
                width: 100%;
            }

            #page-wrapper {
                padding: 20px 16px 40px;
            }

            #page-header {
                height: 60px;
                padding-left: 16px;
            }

            .profile-grid {
                gap: 16px;
            }

            .profile-card {
                padding: 20px;
                border-radius: 12px;
            }

            .form-grid-3,
            .form-grid-2 {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .info-row {
                grid-template-columns: 1fr;
                gap: 4px;
            }

            .edit-button {
                position: static;
                margin-top: 20px;
            }

        }


        @media (max-width: 480px) {

            #page-wrapper {
                padding: 16px 12px 30px;
            }

            .profile-card {
                padding: 16px;
            }

            .card-title {
                font-size: 19px;
            }

            .card-subtitle {
                font-size: 13px;
            }

            .form-input,
            .form-textarea {
                font-size: 13px;
            }

            .green-button {
                font-size: 12px;
                padding: 9px 18px;
            }

        }
    </style>
</head>


<body class="font-libre-franklin antialiased h-full">

    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside id="sidebar">

        <div class="flex flex-col h-full">

            <!-- Sidebar Header -->
            <div
                id="sidebar-header"
                class="flex items-center justify-between px-6"
            >

                <h1 class="text-xl font-extrabold" id="sidebar-title">
                    <span>Driver Panel</span>
                </h1>

                <button
                    type="button"
                    class="text-white focus:outline-none"
                    id="toggleSidebar"
                >
                    <svg
                        id="collapseIcon"
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </button>

            </div>


            <!-- User -->
            <div class="sidebar-user">

                <div class="sidebar-user-inner">

                    <div class="sidebar-user-icon">

                        <svg
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>

                    </div>

                    <div id="profile-text-container">

                        <p class="sidebar-user-name">
                            {{ auth()->user()->name ?? 'Driver' }}
                        </p>

                        <a
                            href="{{ route('driver.profile.edit') }}"
                            class="sidebar-profile-link"
                        >
                            Manage Profile
                        </a>

                    </div>

                </div>

            </div>


            <!-- Navigation -->
            <nav class="flex-1 pt-5">

                <!-- Dashboard -->
                <a
                    href="{{ route('driver.dashboard') }}"
                    class="sidebar-nav-link
                    @if(request()->routeIs('driver.dashboard'))
                        active
                    @endif"
                >

                    <svg
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001 1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                        />
                    </svg>

                    <span>
                        Dashboard
                    </span>

                </a>


                <!-- Profile -->
                <a
                    href="{{ route('driver.profile.edit') }}"
                    class="sidebar-nav-link
                    @if(request()->routeIs('driver.profile.edit'))
                        active
                    @endif"
                >

                    <svg
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                        />
                    </svg>

                    <span>
                        Profile
                    </span>

                </a>

            </nav>


            <!-- Logout -->
            <div class="sidebar-logout">

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        class="logout-button"
                    >

                        <svg
                            class="w-6 h-6 text-[#1e6031]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                            />
                        </svg>

                        <span>
                            Logout
                        </span>

                    </button>

                </form>

            </div>

        </div>

    </aside>


    <!-- =========================================================
         MAIN CONTENT
    ========================================================== -->

    <div id="main-content">

        <!-- Header -->
        <header id="page-header" class="bg-white shadow">

            <div class="w-full max-w-[1250px] mx-auto px-8">

                <h2
                    class="text-2xl font-extrabold text-[#1e6031] leading-tight"
                >
                    {{ __('Manage Profile') }}
                </h2>

            </div>

        </header>


        <!-- Page -->
        <main class="overflow-y-auto">

            <div id="page-wrapper">

                <!-- Mobile Dashboard Button -->
                <a
                    href="{{ route('driver.dashboard') }}"
                    class="mobile-dashboard-button"
                    aria-label="Go to Dashboard"
                >
                    <svg
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a7 7 0 0114 0"
                        />
                    </svg>
                    <span>Go to Dashboard</span>
                </a>

                <div class="profile-grid" id="profileGrid">


                    <!-- =================================================
                         ACCOUNT SECTION (summary + form share this slot)
                    ================================================== -->

                    <div class="profile-section" id="accountSection">


                        <!-- Account Summary -->
                        <div class="profile-card summary-card" id="accountSummaryCard">

                            <h3 class="card-title">
                                Account Information
                            </h3>

                            <p class="card-subtitle">
                                Update your account's profile information
                                and email address.
                            </p>


                            <div class="info-list">

                                <div class="info-row">

                                    <div class="info-label">
                                        Full Name:
                                    </div>

                                    <div class="info-value">
                                        {{ $driver->first_name }}
                                        {{ $driver->middle_initial ? $driver->middle_initial . ' ' : '' }}
                                        {{ $driver->last_name }}
                                    </div>

                                </div>


                                <div class="info-row">

                                    <div class="info-label">
                                        Contact Number:
                                    </div>

                                    <div class="info-value">
                                        {{ $user->contact_num }}
                                    </div>

                                </div>


                                <div class="info-row">

                                    <div class="info-label">
                                        Email:
                                    </div>

                                    <div class="info-value">
                                        {{ $user->email }}
                                    </div>

                                </div>


                                <div class="info-row">

                                    <div class="info-label">
                                        Address:
                                    </div>

                                    <div class="info-value">
                                        {{ $driver->address }}
                                    </div>

                                </div>


                                <div class="info-row">

                                    <div class="info-label">
                                        Official Station:
                                    </div>

                                    <div class="info-value">
                                        {{ $driver->official_station }}
                                    </div>

                                </div>


                                <div class="info-row">

                                    <div class="info-label">
                                        Position:
                                    </div>

                                    <div class="info-value">
                                        {{ $driver->position }}
                                    </div>

                                </div>

                            </div>


                            <button
                                type="button"
                                class="green-button edit-button"
                                id="editAccountBtn"
                            >

                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536M4 20h4l10.5-10.5a2.121 2.121 0 00-3-3L5 17v3z"
                                    />
                                </svg>

                                EDIT

                            </button>

                        </div>

                        <!-- =================================================
                             PROFILE FORM (hidden until "EDIT" is clicked)
                        ================================================== -->

                        <div class="profile-card form-card hidden" id="accountFormCard">

                            <h3 class="card-title">
                                Manage Profile
                            </h3>

                            <h4 class="section-title">
                                Account Information
                            </h4>

                            <p class="card-subtitle">
                                Update your account's profile information
                                and email address.
                            </p>


                            <form
                                id="driverProfileForm"
                                method="POST"
                                action="{{ route('driver.profile.update') }}"
                            >

                                @csrf
                                @method('PATCH')


                                <!-- Name -->
                                <div class="form-grid-3">

                                    <div class="form-group">

                                        <label
                                            for="first_name"
                                            class="form-label"
                                        >
                                            First Name *
                                        </label>

                                        <input
                                            type="text"
                                            name="first_name"
                                            id="first_name"
                                            value="{{ old('first_name', $driver->first_name) }}"
                                            required
                                            class="form-input"
                                            placeholder="Enter first name"
                                        >

                                    </div>


                                    <div class="form-group">

                                        <label
                                            for="middle_initial"
                                            class="form-label"
                                        >
                                            Middle Initial
                                        </label>

                                        <input
                                            type="text"
                                            name="middle_initial"
                                            id="middle_initial"
                                            value="{{ old('middle_initial', $driver->middle_initial) }}"
                                            maxlength="10"
                                            class="form-input"
                                            placeholder="Enter M.I."
                                        >

                                    </div>


                                    <div class="form-group">

                                        <label
                                            for="last_name"
                                            class="form-label"
                                        >
                                            Last Name *
                                        </label>

                                        <input
                                            type="text"
                                            name="last_name"
                                            id="last_name"
                                            value="{{ old('last_name', $driver->last_name) }}"
                                            required
                                            class="form-input"
                                            placeholder="Enter last name"
                                        >

                                    </div>

                                </div>


                                <!-- Contact -->
                                <div class="form-group">

                                    <label
                                        for="contact_num"
                                        class="form-label"
                                    >
                                        Contact Number *
                                    </label>

                                    <input
                                        type="text"
                                        name="contact_num"
                                        id="contact_num"
                                        value="{{ old('contact_num', $user->contact_num) }}"
                                        required
                                        maxlength="11"
                                        class="form-input"
                                        placeholder="Enter 11-digit contact number"
                                    >

                                </div>


                                <!-- Email -->
                                <div class="form-group">

                                    <label
                                        for="email"
                                        class="form-label"
                                    >
                                        Email *
                                    </label>

                                    <input
                                        type="email"
                                        name="email"
                                        id="email"
                                        value="{{ old('email', $user->email) }}"
                                        required
                                        class="form-input"
                                        placeholder="Enter email address"
                                    >

                                </div>


                                <!-- Address -->
                                <div class="form-group">

                                    <label
                                        for="address"
                                        class="form-label"
                                    >
                                        Address *
                                    </label>

                                    <textarea
                                        name="address"
                                        id="address"
                                        rows="2"
                                        required
                                        class="form-textarea"
                                        placeholder="Enter full address"
                                    >{{ old('address', $driver->address) }}</textarea>

                                </div>


                                <!-- Position & Station -->
                                <div class="form-grid-2">

                                    <div class="form-group">

                                        <label
                                            for="position"
                                            class="form-label"
                                        >
                                            Position *
                                        </label>

                                        <input
                                            type="text"
                                            name="position"
                                            id="position"
                                            value="{{ old('position', $driver->position) }}"
                                            required
                                            class="form-input"
                                            placeholder="Enter position"
                                        >

                                    </div>


                                    <div class="form-group">

                                        <label
                                            for="official_station"
                                            class="form-label"
                                        >
                                            Official Station *
                                        </label>

                                        <input
                                            type="text"
                                            name="official_station"
                                            id="official_station"
                                            value="{{ old('official_station', $driver->official_station) }}"
                                            required
                                            class="form-input"
                                            placeholder="Enter official station"
                                        >

                                    </div>

                                </div>


                                <div class="form-actions">

                                    <button
                                        type="submit"
                                        class="green-button save-button"
                                    >

                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>

                                        SAVE

                                    </button>

                                    <button
                                        type="button"
                                        class="cancel-button"
                                        id="cancelAccountBtn"
                                    >
                                        CANCEL
                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>


                    <!-- =================================================
                         PASSWORD SECTION (summary + form share this slot)
                    ================================================== -->

                    <div class="profile-section" id="passwordSection">


                        <!-- Password Summary -->
                        <div class="profile-card password-summary-card" id="passwordSummaryCard">

                            <h3 class="card-title">
                                Update Password
                            </h3>

                            <p class="card-subtitle">
                                Ensure your account is using a long,
                                random password to stay secure.
                            </p>


                            <button
                                type="button"
                                class="green-button edit-button"
                                id="editPasswordBtn"
                            >

                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 11c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM5 20a7 7 0 0114 0"
                                    />
                                </svg>

                                EDIT

                            </button>

                        </div>

                        <!-- =================================================
                             PASSWORD FORM (hidden until "EDIT" is clicked)
                        ================================================== -->

                        <div class="profile-card form-card hidden" id="passwordFormCard">

                            <h3 class="card-title">
                                Update Password
                            </h3>

                            <p class="card-subtitle">
                                Ensure your account is using a long,
                                random password to stay secure.
                            </p>


                            <form
                                id="passwordForm"
                                method="POST"
                                action="{{ route('driver.profile.update') }}"
                            >

                                @csrf
                                @method('PATCH')


                                <!-- Current Password Display -->
                                <div class="form-group">

                                    <label
                                        for="current_password_display"
                                        class="form-label"
                                    >
                                        Current Password
                                    </label>

                                    <input
                                        type="password"
                                        id="current_password_display"
                                        disabled
                                        class="form-input"
                                        placeholder="••••••••"
                                    >

                                    <p class="text-xs text-gray-500 mt-1">
                                        For security reasons, you cannot view your current password.
                                    </p>

                                </div>


                                <!-- New Password -->
                                <div class="form-group">

                                    <label
                                        for="password"
                                        class="form-label"
                                    >
                                        New Password
                                    </label>

                                    <input
                                        type="password"
                                        name="password"
                                        id="password"
                                        class="form-input"
                                        placeholder="Leave blank to keep current password"
                                    >

                                </div>


                                <!-- Confirm -->
                                <div class="form-group">

                                    <label
                                        for="password_confirmation"
                                        class="form-label"
                                    >
                                        Confirm New Password
                                    </label>

                                    <input
                                        type="password"
                                        name="password_confirmation"
                                        id="password_confirmation"
                                        class="form-input"
                                        placeholder="Confirm new password"
                                    >

                                </div>


                                <div class="form-actions">

                                    <button
                                        type="submit"
                                        class="green-button save-button"
                                    >

                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>

                                        SAVE

                                    </button>

                                    <button
                                        type="button"
                                        class="cancel-button"
                                        id="cancelPasswordBtn"
                                    >
                                        CANCEL
                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </main>

    </div>


    <!-- =========================================================
         SWEET ALERT
    ========================================================== -->

    <script>

        document.addEventListener('DOMContentLoaded', function () {

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
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
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
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });

            @endif

        });

    </script>


    <!-- =========================================================
         TOGGLE EDIT FORMS (Account Info / Password)
    ========================================================== -->

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const accountSummaryCard  = document.getElementById('accountSummaryCard');
            const accountFormCard     = document.getElementById('accountFormCard');

            const passwordSummaryCard = document.getElementById('passwordSummaryCard');
            const passwordFormCard    = document.getElementById('passwordFormCard');

            const editAccountBtn   = document.getElementById('editAccountBtn');
            const editPasswordBtn  = document.getElementById('editPasswordBtn');

            const cancelAccountBtn  = document.getElementById('cancelAccountBtn');
            const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');

            // Swap the Account Info summary for its edit form, in the same slot
            editAccountBtn.addEventListener('click', function () {
                accountSummaryCard.classList.add('hidden');
                accountFormCard.classList.remove('hidden');
                accountFormCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            // Swap the Password summary for its edit form, in the same slot
            editPasswordBtn.addEventListener('click', function () {
                passwordSummaryCard.classList.add('hidden');
                passwordFormCard.classList.remove('hidden');
                passwordFormCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            // Cancel buttons swap the form back for its summary
            cancelAccountBtn.addEventListener('click', function () {
                accountFormCard.classList.add('hidden');
                accountSummaryCard.classList.remove('hidden');
            });

            cancelPasswordBtn.addEventListener('click', function () {
                passwordFormCard.classList.add('hidden');
                passwordSummaryCard.classList.remove('hidden');
            });

        });

    </script>


    <!-- =========================================================
         SIDEBAR TOGGLE
    ========================================================== -->

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const sidebar = document.getElementById('sidebar');
            const toggleButton = document.getElementById('toggleSidebar');
            const mainContent = document.getElementById('main-content');
            const collapseIcon = document.getElementById('collapseIcon');
            const sidebarTitle = document.getElementById('sidebar-title');
            const profileTextContainer = document.getElementById('profile-text-container');

            let collapsed = false;


            toggleButton.addEventListener('click', function (e) {

                e.preventDefault();
                collapsed = !collapsed;


                if (collapsed) {

                    sidebar.style.width = '78px';

                    mainContent.style.marginLeft = '78px';
                    mainContent.style.width = 'calc(100% - 78px)';


                    if (sidebarTitle) sidebarTitle.style.display = 'none';
                    if (profileTextContainer) profileTextContainer.style.display = 'none';


                    sidebar.querySelectorAll(
                        '.sidebar-user, .sidebar-nav-link span, .sidebar-logout span'
                    ).forEach(function (element) {

                        element.style.display = 'none';

                    });


                    sidebar.querySelectorAll(
                        '.sidebar-nav-link'
                    ).forEach(function (link) {

                        link.style.justifyContent = 'center';
                        link.style.paddingLeft = '10px';
                        link.style.paddingRight = '10px';

                    });


                    if (collapseIcon) {
                        collapseIcon.innerHTML = `
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        `;
                    }

                } else {

                    sidebar.style.width = '270px';

                    mainContent.style.marginLeft = '270px';
                    mainContent.style.width = 'calc(100% - 270px)';


                    if (sidebarTitle) sidebarTitle.style.display = '';
                    if (profileTextContainer) profileTextContainer.style.display = '';


                    sidebar.querySelectorAll(
                        '.sidebar-user, .sidebar-nav-link span, .sidebar-logout span'
                    ).forEach(function (element) {

                        element.style.display = '';

                    });


                    sidebar.querySelectorAll(
                        '.sidebar-nav-link'
                    ).forEach(function (link) {

                        link.style.justifyContent = '';
                        link.style.paddingLeft = '';
                        link.style.paddingRight = '';

                    });


                    if (collapseIcon) {
                        collapseIcon.innerHTML = `
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            />
                        `;
                    }

                }

            });

        });

    </script>


</body>
</html>