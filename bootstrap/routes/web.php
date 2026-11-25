<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\SubunitController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaderController;
use App\Http\Controllers\RegularEmployeeTravelOrderController;
use App\Http\Controllers\HeadTravelOrderController;
use App\Http\Controllers\HeadOwnTravelOrderController;
use App\Http\Controllers\DivisionHeadTravelOrderController;
use App\Http\Controllers\DivisionHeadOwnTravelOrderController;
use App\Http\Controllers\UnitHeadOwnTravelOrderController;
use App\Http\Controllers\VpTravelOrderController;
use App\Http\Controllers\VpOwnTravelOrderController;
use App\Http\Controllers\PresidentTravelOrderController;
use App\Http\Controllers\PresidentOwnTravelOrderController;
use App\Http\Controllers\MotorpoolAdminController;
use App\Http\Controllers\EmployeeTravelOrderApprovalController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverDashboardController;
use App\Http\Controllers\DriverProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Auth;


// ============================================================
// PUBLIC API ROUTES FOR VALIDATION
// ============================================================

Route::get('/api/check-contact-number/{contactNumber}', [DriverController::class, 'checkContactNumber'])
    ->name('api.check-contact-number');

Route::get('/api/check-email/{email}', [DriverController::class, 'checkEmail'])
    ->name('api.check-email');

Route::get('/api/check-plate-number/{plateNumber}', [VehicleController::class, 'checkPlateNumber'])
    ->name('api.check-plate-number');


Route::get('/api/offices', function() {
    try {
        $offices = \App\Models\Office::all();

        return response()->json($offices);

    } catch (\Exception $e) {

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
})->name('api.offices');


// ============================================================
// API ROUTES FOR APPROVERS
// ============================================================

Route::middleware(['auth'])->group(function () {

    Route::get(
        '/api/approvers/university-president',
        [App\Http\Controllers\Api\ApproverController::class, 'universityPresident']
    )->name('api.approvers.university-president');

    Route::get(
        '/api/approvers/vp',
        [App\Http\Controllers\Api\ApproverController::class, 'vp']
    )->name('api.approvers.vp');

    Route::get(
        '/api/approvers/division-head',
        [App\Http\Controllers\Api\ApproverController::class, 'divisionHead']
    )->name('api.approvers.division-head');

    Route::get(
        '/api/approvers/unit-head',
        [App\Http\Controllers\Api\ApproverController::class, 'unitHead']
    )->name('api.approvers.unit-head');


    // Driver by ID
    Route::get('/api/drivers/{id}', function($id) {

        $driver = \App\Models\Driver::find($id);

        if (!$driver) {
            return response()->json([
                'error' => 'Driver not found'
            ], 404);
        }

        return response()->json($driver);

    })->name('api.drivers.show');


    // Vehicle by ID
    Route::get('/api/vehicles/{id}', function($id) {

        $vehicle = \App\Models\Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'error' => 'Vehicle not found'
            ], 404);
        }

        return response()->json($vehicle);

    })->name('api.vehicles.show');

});


// ============================================================
// ROOT
// ============================================================

Route::get('/', function () {
    return redirect('/login');
});


// ============================================================
// DRIVER DASHBOARD ROUTES
// ============================================================

Route::middleware(['auth', 'role:driver'])
    ->prefix('driver')
    ->name('driver.')
    ->group(function () {

        // Dashboard
        Route::get(
            '/dashboard',
            [DriverDashboardController::class, 'index']
        )->name('dashboard');


        // ====================================================
        // NEW TRIPS ROUTE
        // ====================================================

        Route::get(
            '/trips',
            [DriverDashboardController::class, 'trips']
        )->name('trips');


        // Itineraries
        Route::get(
            '/itineraries',
            [DriverDashboardController::class, 'itineraries']
        )->name('itineraries');

        //Trips
        Route::get('/trips', 
        [DriverDashboardController::class, 'trips']
        )->name('trips');
        
        // Trip Tickets
        Route::get(
            '/trip-tickets',
            [DriverDashboardController::class, 'tripTickets']
        )->name('trip-tickets');

        Route::get(
            '/trip-tickets/{id}',
            [DriverDashboardController::class, 'showTripTicket']
        )->name('trip-tickets.show');


        // Calendar
        Route::get(
            '/calendar/events',
            [DriverDashboardController::class, 'getCalendarEvents']
        )->name('calendar.events');


        // Driver Profile
        Route::get(
            '/profile',
            [DriverProfileController::class, 'edit']
        )->name('profile.edit');

        Route::patch(
            '/profile',
            [DriverProfileController::class, 'update']
        )->name('profile.update');


        // Driver Dashboard APIs
        Route::get(
            '/api/calendar-events',
            [DriverDashboardController::class, 'apiCalendarEvents']
        )->name('api.calendar-events');

        Route::get(
            '/api/status-counts',
            [DriverDashboardController::class, 'apiStatusCounts']
        )->name('api.status-counts');


        // Location tracking + odometer (driver side)
        Route::post(
            '/location/consent',
            [App\Http\Controllers\FleetTrackingController::class, 'consent']
        )->name('location.consent');

        Route::post(
            '/odometer/initial',
            [App\Http\Controllers\FleetTrackingController::class, 'submitInitialOdometer']
        )->name('odometer.initial');

        Route::post(
            '/location/update',
            [App\Http\Controllers\FleetTrackingController::class, 'updateLocation']
        )->name('location.update');

        Route::put(
            '/trip-tickets/{id}/start',
            [App\Http\Controllers\FleetTrackingController::class, 'startTrip']
        )->name('trip-tickets.start');

        Route::put(
            '/trip-tickets/{id}/complete',
            [App\Http\Controllers\FleetTrackingController::class, 'completeTrip']
        )->name('trip-tickets.complete');

    });


// ============================================================
// GENERAL DASHBOARD
// ============================================================

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


// ============================================================
// AUTHENTICATED ROUTES
// ============================================================

Route::middleware('auth')->group(function () {

    // ========================================================
    // PROFILE
    // ========================================================

    Route::get(
        '/profile',
        [ProfileController::class, 'edit']
    )->name('profile.edit');

    Route::patch(
        '/profile',
        [ProfileController::class, 'update']
    )->name('profile.update');

    Route::delete(
        '/profile',
        [ProfileController::class, 'destroy']
    )->name('profile.destroy');


    // ========================================================
    // ADMIN - OFFICES
    // ========================================================

    Route::get(
        '/admin/offices',
        [OfficeController::class, 'index']
    )->name('admin.offices.index');

    Route::get(
        '/admin/offices/create',
        [OfficeController::class, 'create']
    )->name('admin.offices.create');

    Route::post(
        '/admin/offices',
        [OfficeController::class, 'store']
    )->name('admin.offices.store');

    Route::get(
        '/admin/offices/{office}/edit',
        [OfficeController::class, 'edit']
    )->name('admin.offices.edit');

    Route::put(
        '/admin/offices/{office}',
        [OfficeController::class, 'update']
    )->name('admin.offices.update');


    // ========================================================
    // ADMIN - DIVISIONS
    // ========================================================

    Route::get(
        '/admin/divisions',
        [DivisionController::class, 'index']
    )->name('admin.divisions.index');

    Route::get(
        '/admin/divisions/create',
        [DivisionController::class, 'create']
    )->name('admin.divisions.create');

    Route::post(
        '/admin/divisions',
        [DivisionController::class, 'store']
    )->name('admin.divisions.store');

    Route::get(
        '/admin/divisions/{division}/edit',
        [DivisionController::class, 'edit']
    )->name('admin.divisions.edit');

    Route::put(
        '/admin/divisions/{division}',
        [DivisionController::class, 'update']
    )->name('admin.divisions.update');

    Route::delete(
        '/admin/divisions/{division}',
        [DivisionController::class, 'destroy']
    )->name('admin.divisions.destroy');


    // ========================================================
    // ADMIN - UNITS
    // ========================================================

    Route::get(
        '/admin/units',
        [UnitController::class, 'index']
    )->name('admin.units.index');

    Route::get(
        '/admin/units/create',
        [UnitController::class, 'create']
    )->name('admin.units.create');

    Route::post(
        '/admin/units',
        [UnitController::class, 'store']
    )->name('admin.units.store');

    Route::get(
        '/admin/units/{unit}/edit',
        [UnitController::class, 'edit']
    )->name('admin.units.edit');

    Route::put(
        '/admin/units/{unit}',
        [UnitController::class, 'update']
    )->name('admin.units.update');

    Route::delete(
        '/admin/units/{unit}',
        [UnitController::class, 'destroy']
    )->name('admin.units.destroy');


    // ========================================================
    // ADMIN - SUBUNITS
    // ========================================================

    Route::get(
        '/admin/subunits',
        [SubunitController::class, 'index']
    )->name('admin.subunits.index');

    Route::get(
        '/admin/subunits/create',
        [SubunitController::class, 'create']
    )->name('admin.subunits.create');

    Route::post(
        '/admin/subunits',
        [SubunitController::class, 'store']
    )->name('admin.subunits.store');

    Route::get(
        '/admin/subunits/{subunit}/edit',
        [SubunitController::class, 'edit']
    )->name('admin.subunits.edit');

    Route::put(
        '/admin/subunits/{subunit}',
        [SubunitController::class, 'update']
    )->name('admin.subunits.update');

    Route::delete(
        '/admin/subunits/{subunit}',
        [SubunitController::class, 'destroy']
    )->name('admin.subunits.destroy');


    // ========================================================
    // ADMIN - CLASSES
    // ========================================================

    Route::get(
        '/admin/classes',
        [ClassController::class, 'index']
    )->name('admin.classes.index');

    Route::get(
        '/admin/classes/create',
        [ClassController::class, 'create']
    )->name('admin.classes.create');

    Route::post(
        '/admin/classes',
        [ClassController::class, 'store']
    )->name('admin.classes.store');

    Route::get(
        '/admin/classes/{class}/edit',
        [ClassController::class, 'edit']
    )->name('admin.classes.edit');

    Route::put(
        '/admin/classes/{class}',
        [ClassController::class, 'update']
    )->name('admin.classes.update');

    Route::delete(
        '/admin/classes/{class}',
        [ClassController::class, 'destroy']
    )->name('admin.classes.destroy');


    // ========================================================
    // ADMIN - EMPLOYEES
    // ========================================================

    Route::get(
        '/admin/employees',
        [EmployeeController::class, 'index']
    )->name('admin.employees.index');

    Route::get(
        '/admin/employees/create',
        [EmployeeController::class, 'create']
    )->name('admin.employees.create');

    Route::get(
        '/admin/employees/{employee}',
        [EmployeeController::class, 'show']
    )->name('admin.employees.show');

    Route::post(
        '/admin/employees',
        [EmployeeController::class, 'store']
    )->name('admin.employees.store');

    Route::get(
        '/admin/employees/{employee}/edit',
        [EmployeeController::class, 'edit']
    )->name('admin.employees.edit');

    Route::get(
        '/admin/employees/{employee}/data',
        [EmployeeController::class, 'getEmployeeData']
    )->name('admin.employees.data');

    Route::put(
        '/admin/employees/{employee}',
        [EmployeeController::class, 'update']
    )->name('admin.employees.update');

    Route::delete(
        '/admin/employees/{employee}',
        [EmployeeController::class, 'destroy']
    )->name('admin.employees.destroy');


    // Employee AJAX Routes
    Route::get(
        '/admin/employees/get-divisions-by-office',
        [EmployeeController::class, 'getDivisionsByOffice']
    )->name('admin.employees.get-divisions-by-office');

    Route::get(
        '/admin/employees/get-units-by-division',
        [EmployeeController::class, 'getUnitsByDivision']
    )->name('admin.employees.get-units-by-division');

    Route::get(
        '/admin/employees/get-subunits-by-unit',
        [EmployeeController::class, 'getSubunitsByUnit']
    )->name('admin.employees.get-subunits-by-unit');


    // ========================================================
    // LEADERSHIP
    // ========================================================

    Route::get(
        '/admin/leaders',
        [LeaderController::class, 'index']
    )->name('admin.leaders.index');

    Route::get(
        '/admin/leaders/offices',
        [LeaderController::class, 'offices']
    )->name('admin.leaders.offices');

    Route::get(
        '/admin/leaders/offices/{office}',
        [LeaderController::class, 'showOffice']
    )->name('admin.leaders.office.show');

    Route::get(
        '/admin/leaders/divisions/{division}',
        [LeaderController::class, 'showDivision']
    )->name('admin.leaders.division.show');

    Route::get(
        '/admin/leaders/units/{unit}',
        [LeaderController::class, 'showUnit']
    )->name('admin.leaders.unit.show');

    Route::get(
        '/admin/leaders/edit/{type}/{id?}',
        [LeaderController::class, 'edit']
    )->name('admin.leaders.edit');

    Route::post(
        '/admin/leaders/update',
        [LeaderController::class, 'update']
    )->name('admin.leaders.update');


    // ========================================================
    // VEHICLE CALENDAR
    // ========================================================

    Route::get(
        '/vehicle-calendar',
        [CalendarController::class, 'index']
    )->name('vehicle-calendar.index');

    Route::get(
        '/vehicle-calendar/events',
        [CalendarController::class, 'getEvents']
    )->name('vehicle-calendar.events');


    // ========================================================
    // REGULAR EMPLOYEE TRAVEL ORDERS
    // ========================================================

    Route::get(
        '/travel-orders',
        [RegularEmployeeTravelOrderController::class, 'index']
    )->name('travel-orders.index');

    Route::get(
        '/travel-orders/create',
        [RegularEmployeeTravelOrderController::class, 'create']
    )->name('travel-orders.create');

    Route::post(
        '/travel-orders',
        [RegularEmployeeTravelOrderController::class, 'store']
    )->name('travel-orders.store');

    Route::get(
        '/travel-orders/{travelOrder}',
        [RegularEmployeeTravelOrderController::class, 'show']
    )->name('travel-orders.show');

    Route::get(
        '/travel-orders/{travelOrder}/edit',
        [RegularEmployeeTravelOrderController::class, 'edit']
    )->name('travel-orders.edit');

    Route::put(
        '/travel-orders/{travelOrder}',
        [RegularEmployeeTravelOrderController::class, 'update']
    )->name('travel-orders.update');

    Route::delete(
        '/travel-orders/{travelOrder}',
        [RegularEmployeeTravelOrderController::class, 'destroy']
    )->name('travel-orders.destroy');

    Route::patch(
        '/travel-orders/{travelOrder}/cancel',
        [RegularEmployeeTravelOrderController::class, 'cancel']
    )->name('travel-orders.cancel');

    Route::get(
        '/travel-orders/{travelOrder}/excel',
        [RegularEmployeeTravelOrderController::class, 'generateExcel']
    )->name('travel-orders.pdf');

    Route::get(
        '/api/travel-orders/{travelOrder}',
        [RegularEmployeeTravelOrderController::class, 'showDetails']
    )->name('travel-orders.details');


    // Draft travel orders
    Route::post(
        '/travel-orders/{travelOrder}/submit-for-approval',
        [RegularEmployeeTravelOrderController::class, 'submitForApproval']
    )->name('travel-orders.submit-for-approval');

    Route::delete(
        '/travel-orders/{travelOrder}/delete-draft',
        [RegularEmployeeTravelOrderController::class, 'deleteDraft']
    )->name('travel-orders.delete-draft');


    // Cancelled travel orders
    Route::delete(
        '/travel-orders/{travelOrder}/delete-cancelled',
        [RegularEmployeeTravelOrderController::class, 'deleteCancelled']
    )->name('travel-orders.delete-cancelled');


    // ========================================================
    // TRAVEL ORDER DETAILS
    // ========================================================

    Route::get(
        '/api/head/travel-orders/{travelOrder}',
        [HeadTravelOrderController::class, 'showDetails']
    )->name('head.travel-orders.details');

    Route::get(
        '/api/divisionhead/travel-orders/{travelOrder}',
        [DivisionHeadTravelOrderController::class, 'showDetails']
    )->name('divisionhead.travel-orders.details');

    Route::get(
        '/api/vp/travel-orders/{travelOrder}',
        [VpTravelOrderController::class, 'showDetails']
    )->name('vp.travel-orders.details');

    Route::get(
        '/api/president/travel-orders/{travelOrder}',
        [PresidentTravelOrderController::class, 'showDetails']
    )->name('president.travel-orders.details');


    // ========================================================
    // HEAD OWN TRAVEL ORDERS
    // ========================================================

    Route::get(
        '/head/travel-orders',
        [HeadOwnTravelOrderController::class, 'index']
    )->name('head.travel-orders.index');

    Route::get(
        '/head/travel-orders/create',
        [HeadOwnTravelOrderController::class, 'create']
    )->name('head.travel-orders.create');

    Route::post(
        '/head/travel-orders',
        [HeadOwnTravelOrderController::class, 'store']
    )->name('head.travel-orders.store');

    Route::get(
        '/head/travel-orders/{travelOrder}',
        [HeadOwnTravelOrderController::class, 'show']
    )->name('head.travel-orders.show');

    Route::get(
        '/head/travel-orders/{travelOrder}/edit',
        [HeadOwnTravelOrderController::class, 'edit']
    )->name('head.travel-orders.edit');

    Route::put(
        '/head/travel-orders/{travelOrder}',
        [HeadOwnTravelOrderController::class, 'update']
    )->name('head.travel-orders.update');

    Route::delete(
        '/head/travel-orders/{travelOrder}',
        [HeadOwnTravelOrderController::class, 'destroy']
    )->name('head.travel-orders.destroy');


    // Head approvals
    Route::get(
        '/travel-orders/approvals/head',
        [HeadTravelOrderController::class, 'index']
    )->name('travel-orders.approvals.head');

    Route::get(
        '/travel-orders/{travelOrder}/approval-show/head',
        [HeadTravelOrderController::class, 'show']
    )->name('travel-orders.approval-show.head');

    Route::get(
        '/travel-orders/{travelOrder}/approval-excel/head',
        [HeadTravelOrderController::class, 'generateExcel']
    )->name('travel-orders.approval-pdf.head');

    Route::put(
        '/travel-orders/{travelOrder}/approve/head',
        [HeadTravelOrderController::class, 'approve']
    )->name('travel-orders.approve.head');

    Route::put(
        '/travel-orders/{travelOrder}/reject/head',
        [HeadTravelOrderController::class, 'reject']
    )->name('travel-orders.reject.head');


    // ========================================================
    // UNIT HEAD OWN TRAVEL ORDERS
    // ========================================================

    Route::get(
        '/unithead/travel-orders',
        [UnitHeadOwnTravelOrderController::class, 'index']
    )->name('unithead.travel-orders.index');

    Route::get(
        '/unithead/travel-orders/create',
        [UnitHeadOwnTravelOrderController::class, 'create']
    )->name('unithead.travel-orders.create');

    Route::post(
        '/unithead/travel-orders',
        [UnitHeadOwnTravelOrderController::class, 'store']
    )->name('unithead.travel-orders.store');

    Route::get(
        '/unithead/travel-orders/{travelOrder}',
        [UnitHeadOwnTravelOrderController::class, 'show']
    )->name('unithead.travel-orders.show');

    Route::get(
        '/unithead/travel-orders/{travelOrder}/edit',
        [UnitHeadOwnTravelOrderController::class, 'edit']
    )->name('unithead.travel-orders.edit');

    Route::put(
        '/unithead/travel-orders/{travelOrder}',
        [UnitHeadOwnTravelOrderController::class, 'update']
    )->name('unithead.travel-orders.update');

    Route::delete(
        '/unithead/travel-orders/{travelOrder}',
        [UnitHeadOwnTravelOrderController::class, 'destroy']
    )->name('unithead.travel-orders.destroy');

    Route::get(
        '/unithead/travel-orders/{travelOrder}/excel',
        [UnitHeadOwnTravelOrderController::class, 'generateExcel']
    )->name('unithead.travel-orders.pdf');


    // ========================================================
    // DIVISION HEAD OWN TRAVEL ORDERS
    // ========================================================

    Route::get(
        '/divisionhead/travel-orders',
        [DivisionHeadOwnTravelOrderController::class, 'index']
    )->name('divisionhead.travel-orders.index');

    Route::get(
        '/divisionhead/travel-orders/create',
        [DivisionHeadOwnTravelOrderController::class, 'create']
    )->name('divisionhead.travel-orders.create');

    Route::post(
        '/divisionhead/travel-orders',
        [DivisionHeadOwnTravelOrderController::class, 'store']
    )->name('divisionhead.travel-orders.store');

    Route::get(
        '/divisionhead/travel-orders/{travelOrder}',
        [DivisionHeadOwnTravelOrderController::class, 'show']
    )->name('divisionhead.travel-orders.show');

    Route::get(
        '/divisionhead/travel-orders/{travelOrder}/edit',
        [DivisionHeadOwnTravelOrderController::class, 'edit']
    )->name('divisionhead.travel-orders.edit');

    Route::put(
        '/divisionhead/travel-orders/{travelOrder}',
        [DivisionHeadOwnTravelOrderController::class, 'update']
    )->name('divisionhead.travel-orders.update');

    Route::delete(
        '/divisionhead/travel-orders/{travelOrder}',
        [DivisionHeadOwnTravelOrderController::class, 'destroy']
    )->name('divisionhead.travel-orders.destroy');

    Route::get(
        '/divisionhead/travel-orders/{travelOrder}/excel',
        [DivisionHeadOwnTravelOrderController::class, 'generateExcel']
    )->name('divisionhead.travel-orders.pdf');


    // Division Head approvals
    Route::get(
        '/travel-orders/approvals/divisionhead',
        [DivisionHeadTravelOrderController::class, 'index']
    )->name('travel-orders.approvals.divisionhead');

    Route::get(
        '/travel-orders/{travelOrder}/approval-show/divisionhead',
        [DivisionHeadTravelOrderController::class, 'show']
    )->name('travel-orders.approval-show.divisionhead');

    Route::get(
        '/travel-orders/{travelOrder}/approval-excel/divisionhead',
        [DivisionHeadTravelOrderController::class, 'generateExcel']
    )->name('travel-orders.approval-pdf.divisionhead');

    Route::put(
        '/travel-orders/{travelOrder}/approve/divisionhead',
        [DivisionHeadTravelOrderController::class, 'approve']
    )->name('travel-orders.approve.divisionhead');

    Route::put(
        '/travel-orders/{travelOrder}/reject/divisionhead',
        [DivisionHeadTravelOrderController::class, 'reject']
    )->name('travel-orders.reject.divisionhead');


    // ========================================================
    // VP OWN TRAVEL ORDERS
    // ========================================================

    Route::get(
        '/vp/travel-orders',
        [VpOwnTravelOrderController::class, 'index']
    )->name('vp.travel-orders.index');

    Route::get(
        '/vp/travel-orders/create',
        [VpOwnTravelOrderController::class, 'create']
    )->name('vp.travel-orders.create');

    Route::post(
        '/vp/travel-orders',
        [VpOwnTravelOrderController::class, 'store']
    )->name('vp.travel-orders.store');

    Route::get(
        '/vp/travel-orders/{travelOrder}',
        [VpOwnTravelOrderController::class, 'show']
    )->name('vp.travel-orders.show');

    Route::get(
        '/vp/travel-orders/{travelOrder}/edit',
        [VpOwnTravelOrderController::class, 'edit']
    )->name('vp.travel-orders.edit');

    Route::put(
        '/vp/travel-orders/{travelOrder}',
        [VpOwnTravelOrderController::class, 'update']
    )->name('vp.travel-orders.update');

    Route::delete(
        '/vp/travel-orders/{travelOrder}',
        [VpOwnTravelOrderController::class, 'destroy']
    )->name('vp.travel-orders.destroy');

    Route::get(
        '/vp/travel-orders/{travelOrder}/excel',
        [VpOwnTravelOrderController::class, 'generateExcel']
    )->name('vp.travel-orders.pdf');


    // VP approvals
    Route::get(
        '/travel-orders/approvals/vp',
        [VpTravelOrderController::class, 'index']
    )->name('travel-orders.approvals.vp');

    Route::get(
        '/travel-orders/{travelOrder}/approval-show/vp',
        [VpTravelOrderController::class, 'show']
    )->name('travel-orders.approval-show.vp');

    Route::get(
        '/travel-orders/{travelOrder}/approval-excel/vp',
        [VpTravelOrderController::class, 'generateExcel']
    )->name('travel-orders.approval-pdf.vp');

    Route::put(
        '/travel-orders/{travelOrder}/approve/vp',
        [VpTravelOrderController::class, 'approve']
    )->name('travel-orders.approve.vp');

    Route::put(
        '/travel-orders/{travelOrder}/reject/vp',
        [VpTravelOrderController::class, 'reject']
    )->name('travel-orders.reject.vp');


    // ========================================================
    // PRESIDENT OWN TRAVEL ORDERS
    // ========================================================

    Route::get(
        '/president/travel-orders',
        [PresidentOwnTravelOrderController::class, 'index']
    )->name('president.travel-orders.index');

    Route::get(
        '/president/travel-orders/create',
        [PresidentOwnTravelOrderController::class, 'create']
    )->name('president.travel-orders.create');

    Route::post(
        '/president/travel-orders',
        [PresidentOwnTravelOrderController::class, 'store']
    )->name('president.travel-orders.store');

    Route::get(
        '/president/travel-orders/{travelOrder}',
        [PresidentOwnTravelOrderController::class, 'show']
    )->name('president.travel-orders.show');

    Route::get(
        '/president/travel-orders/{travelOrder}/edit',
        [PresidentOwnTravelOrderController::class, 'edit']
    )->name('president.travel-orders.edit');

    Route::put(
        '/president/travel-orders/{travelOrder}',
        [PresidentOwnTravelOrderController::class, 'update']
    )->name('president.travel-orders.update');

    Route::delete(
        '/president/travel-orders/{travelOrder}',
        [PresidentOwnTravelOrderController::class, 'destroy']
    )->name('president.travel-orders.destroy');

    Route::get(
        '/president/travel-orders/{travelOrder}/excel',
        [PresidentOwnTravelOrderController::class, 'generateExcel']
    )->name('president.travel-orders.pdf');

    Route::patch(
        '/president/travel-orders/{travelOrder}/submit-for-approval',
        [PresidentOwnTravelOrderController::class, 'submitForApproval']
    )->name('president.travel-orders.submit-for-approval');


    // President approvals
    Route::get(
        '/travel-orders/approvals/president',
        [PresidentTravelOrderController::class, 'index']
    )->name('travel-orders.approvals.president');

    Route::get(
        '/travel-orders/{travelOrder}/approval-show/president',
        [PresidentTravelOrderController::class, 'show']
    )->name('travel-orders.approval-show.president');

    Route::get(
        '/travel-orders/{travelOrder}/approval-excel/president',
        [PresidentTravelOrderController::class, 'generateExcel']
    )->name('travel-orders.approval-pdf.president');

    Route::put(
        '/travel-orders/{travelOrder}/approve/president',
        [PresidentTravelOrderController::class, 'approve']
    )->name('travel-orders.approve.president');

    Route::put(
        '/travel-orders/{travelOrder}/reject/president',
        [PresidentTravelOrderController::class, 'reject']
    )->name('travel-orders.reject.president');


    // ========================================================
    // MOTORPOOL ADMIN - APPROVED TRAVEL ORDERS
    // ========================================================

    Route::middleware(['auth', 'role:motorpool_admin'])->group(function () {

        Route::get(
            '/approved-travel-orders',
            [MotorpoolAdminController::class, 'approvedTravelOrders']
        )->name('approved-travel-orders.index');

        Route::get(
            '/api/travel-orders/search',
            [MotorpoolAdminController::class, 'searchTravelOrders']
        )->name('api.travel-orders.search');

        Route::get(
            '/api/trip-tickets/calendar-events',
            [MotorpoolAdminController::class, 'calendarEvents']
        )->name('api.trip-tickets.calendar-events');

        Route::get(
            '/api/trip-tickets/status-counts',
            [MotorpoolAdminController::class, 'statusCounts']
        )->name('api.trip-tickets.status-counts');

    });


    // ========================================================
    // VEHICLE MANAGEMENT
    // ========================================================

    Route::middleware(['auth', 'role:motorpool_admin'])->group(function () {

        Route::resource(
            'admin/vehicles',
            App\Http\Controllers\VehicleController::class
        )->parameters([
            'vehicles' => 'vehicle'
        ]);

        Route::get(
            '/admin/vehicles/{vehicle}/edit-data',
            [App\Http\Controllers\VehicleController::class, 'getForEdit']
        )->name('vehicles.edit.data');


        // Driver Management
        Route::resource(
            'drivers',
            DriverController::class
        );

        Route::get(
            '/drivers/generate-pdf',
            [DriverController::class, 'generatePDF']
        )->name('drivers.generate-pdf');


        // Trip Ticket Management
        Route::get(
            '/trip-tickets/excel/{id}',
            [App\Http\Controllers\TripTicketController::class, 'generateExcel']
        )->name('trip-tickets.excel');

        Route::resource(
            'trip-tickets',
            App\Http\Controllers\TripTicketController::class
        );

        Route::patch(
            '/trip-tickets/{id}/status',
            [App\Http\Controllers\TripTicketController::class, 'updateStatus']
        )->name('trip-tickets.status.update');

        Route::post(
            '/trip-tickets/{id}/cancel',
            [App\Http\Controllers\TripTicketController::class, 'cancel']
        )->name('trip-tickets.cancel');

        Route::put(
            '/trip-tickets/{id}/start',
            [App\Http\Controllers\TripTicketController::class, 'start']
        )->name('trip-tickets.start');

        Route::post(
            '/trip-tickets/remove-travel-order',
            [App\Http\Controllers\TripTicketController::class, 'removeTravelOrder']
        )->name('trip-tickets.remove-travel-order');

        Route::post(
            '/trip-tickets/add-travel-order',
            [App\Http\Controllers\TripTicketController::class, 'addTravelOrderToItinerary']
        )->name('trip-tickets.add-travel-order');

        Route::get(
            '/api/travel-orders/{id}/passengers',
            [App\Http\Controllers\TripTicketController::class, 'getPassengersForTravelOrder']
        );

        Route::post(
            '/trip-tickets/{id}/upload-completion-file',
            [App\Http\Controllers\TripTicketController::class, 'uploadCompletionFile']
        )->name('trip-tickets.upload-completion-file');


        // Real-time fleet monitoring (Ongoing Trips)
        Route::get(
            '/trip-tickets-ongoing/map',
            [App\Http\Controllers\FleetTrackingController::class, 'ongoingTrips']
        )->name('trip-tickets.ongoing-map');

        Route::get(
            '/api/fleet/locations',
            [App\Http\Controllers\FleetTrackingController::class, 'fleetLocations']
        )->name('api.fleet.locations');

        Route::get(
            '/api/trip-tickets/{id}/trail',
            [App\Http\Controllers\FleetTrackingController::class, 'tripTrail']
        )->name('api.trip-tickets.trail');

    });


    // ========================================================
    // COMPLETION FILES
    // ========================================================

    Route::get('/storage/completion-files/{filename}', function($filename) {

        $path = storage_path(
            'app/public/completion_files/' . $filename
        );

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path);

    })->name('storage.completion-files');


    // ========================================================
    // TRAVEL ORDER CREATOR
    // ========================================================

    Route::get(
        '/api/travel-orders/{id}/creator',
        [App\Http\Controllers\RegularEmployeeTravelOrderController::class, 'getCreator']
    );


    // ========================================================
    // EMPLOYEE TRAVEL ORDER APPROVAL ROUTES
    // (for all employees who are selected as approvers/signers)
    // ========================================================

    Route::middleware('auth')
        ->prefix('employee/travel-orders/approvals')
        ->name('employee.travel-orders.approvals.')
        ->group(function () {

            Route::get(
                '/pending',
                [EmployeeTravelOrderApprovalController::class, 'pendingApprovals']
            )->name('pending');

            Route::get(
                '/approved',
                [EmployeeTravelOrderApprovalController::class, 'approvedApprovals']
            )->name('approved');

            Route::get(
                '/cancelled',
                [EmployeeTravelOrderApprovalController::class, 'cancelledApprovals']
            )->name('cancelled');

            Route::put(
                '/{travelOrder}/approve',
                [EmployeeTravelOrderApprovalController::class, 'approve']
            )->name('approve');

            Route::put(
                '/{travelOrder}/reject',
                [EmployeeTravelOrderApprovalController::class, 'reject']
            )->name('reject');

            Route::put(
                '/{travelOrder}/sign',
                [EmployeeTravelOrderApprovalController::class, 'sign']
            )->name('sign');

            Route::put(
                '/{travelOrder}/reject-sign',
                [EmployeeTravelOrderApprovalController::class, 'rejectSign']
            )->name('reject-sign');


            // Debug route - REMOVE IN PRODUCTION
            Route::get('/debug', function () {
                return view('travel-orders.debug-pending');
            })->name('debug');

        });


    // ========================================================
    // AVAILABLE DRIVERS
    // ========================================================

    Route::get('/api/available-drivers/{date}', function($date) {

        $dateParts = explode('|', $date);

        $dateFrom = $dateParts[0];

        $dateTo = isset($dateParts[1])
            ? $dateParts[1]
            : $dateFrom;


        $driversWithItineraries =
            \App\Models\Itinerary::where(function($query) use ($dateFrom, $dateTo) {

                $query
                    ->whereRaw(
                        "DATE(itineraries.date_from) <= ?",
                        [$dateTo]
                    )
                    ->whereRaw(
                        "DATE(itineraries.date_to) >= ?",
                        [$dateFrom]
                    )
                    ->whereNotNull('driver_id')
                    ->whereNotIn(
                        'status',
                        ['Completed']
                    );

            })
            ->pluck('driver_id');


        $availableDrivers =
            \App\Models\Driver::where(
                'availability_status',
                'Active'
            )
            ->whereNotIn(
                'id',
                $driversWithItineraries
            )
            ->with('user')
            ->orderBy(
                'first_name',
                'asc'
            )
            ->orderBy(
                'last_name',
                'asc'
            )
            ->get();


        return response()->json(
            $availableDrivers
        );

    });


    // ========================================================
    // AVAILABLE VEHICLES
    // ========================================================

    Route::get('/api/available-vehicles/{date}', function($date) {

        $dateParts = explode('|', $date);

        $dateFrom = $dateParts[0];

        $dateTo = isset($dateParts[1])
            ? $dateParts[1]
            : $dateFrom;


        $vehiclesWithItineraries =
            \App\Models\Itinerary::where(function($query) use ($dateFrom, $dateTo) {

                $query
                    ->whereRaw(
                        "DATE(itineraries.date_from) <= ?",
                        [$dateTo]
                    )
                    ->whereRaw(
                        "DATE(itineraries.date_to) >= ?",
                        [$dateFrom]
                    )
                    ->whereNotNull('vehicle_id')
                    ->whereNotIn(
                        'status',
                        ['Completed']
                    );

            })
            ->pluck('vehicle_id');


        $underMaintenance =
            \App\Models\VehicleMaintenance::where(
                'status',
                'Ongoing'
            )
            ->pluck('vehicle_id');


        $availableVehicles =
            \App\Models\Vehicle::where(
                'status',
                'Active'
            )
            ->whereNotIn(
                'id',
                $vehiclesWithItineraries
            )
            ->whereNotIn(
                'id',
                $underMaintenance
            )
            ->orderBy(
                'plate_number',
                'asc'
            )
            ->get();


        return response()->json(
            $availableVehicles
        );

    });


    // ========================================================
    // VEHICLE CONFLICT CHECK
    // ========================================================

    Route::get(
        '/api/vehicles/check-conflicts',
        function (\Illuminate\Http\Request $request) {

            $dateFrom = $request->query('date_from');

            $dateTo = $request->query('date_to');


            if (!$dateFrom || !$dateTo) {

                return response()->json([
                    'conflictingVehicles' => [],
                    'underMaintenance' => []
                ]);

            }


            $conflictingVehicles =
                \App\Models\Itinerary::where(function($query) use ($dateFrom, $dateTo) {

                    $query->where(function($q) use ($dateFrom, $dateTo) {

                        $q->where(function($q2) use ($dateFrom, $dateTo) {

                            $q2
                                ->whereDate(
                                    'date_from',
                                    '<=',
                                    $dateTo
                                )
                                ->whereDate(
                                    'date_to',
                                    '>=',
                                    $dateFrom
                                );

                        });

                    });

                })
                ->pluck('vehicle_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();


            $underMaintenance =
                \App\Models\VehicleMaintenance::where(
                    'status',
                    'Ongoing'
                )
                ->pluck('vehicle_id')
                ->toArray();


            return response()->json([

                'conflictingVehicles' =>
                    $conflictingVehicles,

                'underMaintenance' =>
                    $underMaintenance

            ]);

        }
    );


    // ========================================================
    // DRIVER CONFLICT CHECK
    // ========================================================

    Route::get(
        '/api/drivers/check-conflicts',
        function (\Illuminate\Http\Request $request) {

            $dateFrom =
                $request->query('date_from');

            $dateTo =
                $request->query('date_to');


            if (!$dateFrom || !$dateTo) {

                return response()->json([
                    'conflictingDrivers' => []
                ]);

            }


            $conflictingDrivers =
                \App\Models\Itinerary::where(function($query) use ($dateFrom, $dateTo) {

                    $query->where(function($q) use ($dateFrom, $dateTo) {

                        $q->where(function($q2) use ($dateFrom, $dateTo) {

                            $q2
                                ->whereDate(
                                    'date_from',
                                    '<=',
                                    $dateTo
                                )
                                ->whereDate(
                                    'date_to',
                                    '>=',
                                    $dateFrom
                                );

                        });

                    });

                })
                ->whereHas(
                    'driver',
                    function($query) {

                        $query->where(
                            'availability_status',
                            'Active'
                        );

                    }
                )
                ->pluck('driver_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();


            return response()->json([
                'conflictingDrivers' =>
                    $conflictingDrivers
            ]);

        }
    );


    // ========================================================
    // ITINERARY DETAILS API
    // ========================================================

    Route::get('/api/itineraries/{id}', function($id) {

        $itinerary =
            \App\Models\Itinerary::with([
                'driver.user',
                'vehicle',
                'rejectedBy'
            ])->find($id);


        if (!$itinerary) {

            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found'
            ], 404);

        }


        $travelOrders = [];

        $travelOrderIds =
            $itinerary->travel_order_id ?? [];


        $travelOrderIds =
            array_values(
                array_unique(
                    $travelOrderIds,
                    SORT_NUMERIC
                )
            );


        if (!empty($travelOrderIds)) {

            $loadedTravelOrders =
                \App\Models\TravelOrder::whereIn(
                    'id',
                    $travelOrderIds
                )
                ->with(['employee'])
                ->get();


            $orderedTravelOrders =
                collect();


            foreach (
                $travelOrderIds
                as $toId
            ) {

                $to =
                    $loadedTravelOrders->firstWhere(
                        'id',
                        $toId
                    );


                if ($to) {
                    $orderedTravelOrders->push($to);
                }

            }


            foreach (
                $orderedTravelOrders
                as $to
            ) {

                $travelOrders[] = [

                    'id' =>
                        $to->id,

                    'purpose' =>
                        $to->purpose,

                    'destination' =>
                        $to->destination,

                    'date_from' =>
                        $to->date_from
                            ? \Carbon\Carbon::parse(
                                $to->date_from
                            )->format('M j, Y')
                            : null,

                    'departure_time' =>
                        $to->departure_time
                            ? \Carbon\Carbon::parse(
                                $to->departure_time
                            )->format('g:i A')
                            : null,

                    'employee' => [

                        'full_name' =>
                            $to->employee
                                ? $to->employee->full_name
                                : 'N/A',

                        'contact_num' =>
                            $to->employee
                                ? $to->employee->contact_num
                                : '-'

                    ]

                ];

            }

        }


        $dateFrom =
            $itinerary->date_from
                ? $itinerary->date_from->format('Y-m-d')
                : null;

        $dateTo =
            $itinerary->date_to
                ? $itinerary->date_to->format('Y-m-d')
                : null;


        return response()->json([

            'success' => true,

            'itinerary' => [

                'id' =>
                    $itinerary->id,

                'travel_orders' =>
                    $travelOrders,

                'travel_order_id' =>
                    $itinerary->travel_order_id,

                'driver_id' =>
                    $itinerary->driver_id,

                'vehicle_id' =>
                    $itinerary->vehicle_id,

                'date_from' =>
                    $dateFrom,

                'date_to' =>
                    $dateTo,

                'destination' =>
                    $itinerary->destination,

                'purpose' =>
                    $itinerary->purpose,

                'departure_time' =>
                    $itinerary->departure_time,

                'status' =>
                    $itinerary->status,


                'driver' =>
                    $itinerary->driver
                        ? [

                            'first_name' =>
                                $itinerary->driver->first_name,

                            'last_name' =>
                                $itinerary->driver->last_name,

                            'contact_num' =>
                                $itinerary->driver->contact_num,

                            'user' => [

                                'contact_num' =>
                                    $itinerary->driver
                                        ->user?->contact_num
                                        ?? '-'

                            ]

                        ]
                        : null,


                'vehicle' =>
                    $itinerary->vehicle
                        ? [

                            'model' =>
                                $itinerary->vehicle->model,

                            'plate_number' =>
                                $itinerary->vehicle->plate_number,

                            'seating_capacity' =>
                                $itinerary->vehicle
                                    ->seating_capacity
                                    ?? 0

                        ]
                        : null,


                'unit_head_approved' =>
                    $itinerary->unit_head_approved,

                'vp_approved' =>
                    $itinerary->vp_approved,

                'rejection_reason' =>
                    $itinerary->rejection_reason,


                'rejected_by' =>
                    $itinerary->rejectedBy
                        ? [

                            'full_name' =>
                                $itinerary
                                    ->rejectedBy
                                    ->full_name

                        ]
                        : null

            ]

        ]);

    });


    // ========================================================
    // ITINERARY APIs
    // ========================================================

    Route::get(
        '/api/itineraries/{id}/creator',
        [App\Http\Controllers\ItineraryController::class, 'getCreator']
    );

    Route::get(
        '/api/itineraries/{id}/passengers',
        [App\Http\Controllers\ItineraryController::class, 'getPassengers']
    );

    Route::get(
        '/api/itineraries/{id}/travel-orders',
        [App\Http\Controllers\ItineraryController::class, 'getTravelOrders']
    );


    // ========================================================
    // ITINERARY MANAGEMENT
    // ========================================================

    Route::resource(
        'itinerary',
        App\Http\Controllers\ItineraryController::class
    );

    Route::get(
        '/itinerary/create/modal',
        [App\Http\Controllers\ItineraryController::class, 'createModal']
    )->name('itinerary.create.modal');

    Route::get(
        '/itinerary/{itinerary}/excel',
        [App\Http\Controllers\ItineraryController::class, 'generateExcel']
    )->name('itinerary.pdf');


    // ========================================================
    // ITINERARY APPROVALS
    // ========================================================

    Route::prefix('itinerary/approvals')
        ->name('itinerary.approvals.')
        ->group(function () {

            Route::get(
                '/unit-head-pending',
                [App\Http\Controllers\ItineraryApprovalController::class, 'unitHeadPending']
            )->name('unit-head.pending');

            Route::put(
                '/{id}/unit-head-approve',
                [App\Http\Controllers\ItineraryApprovalController::class, 'approveByUnitHead']
            )->name('unit-head.approve');

            Route::put(
                '/{id}/unit-head-reject',
                [App\Http\Controllers\ItineraryApprovalController::class, 'rejectByUnitHead']
            )->name('unit-head.reject');


            Route::get(
                '/vp-pending',
                [App\Http\Controllers\ItineraryApprovalController::class, 'vpPending']
            )->name('vp.pending');

            Route::put(
                '/{id}/vp-approve',
                [App\Http\Controllers\ItineraryApprovalController::class, 'approveByVp']
            )->name('vp.approve');

            Route::put(
                '/{id}/vp-reject',
                [App\Http\Controllers\ItineraryApprovalController::class, 'rejectByVp']
            )->name('vp.reject');


            Route::get(
                '/approved',
                [App\Http\Controllers\ItineraryApprovalController::class, 'approved']
            )->name('approved');

        });


    // ========================================================
    // MOTORPOOL APPROVAL ROUTES
    // ========================================================

    Route::get(
        '/motorpool/approvals/itineraries/unit-head',
        [App\Http\Controllers\ItineraryApprovalController::class, 'unitHeadPending']
    )->name('motorpool.approvals.itineraries.unit-head');

    Route::get(
        '/motorpool/approvals/itineraries/vp',
        [App\Http\Controllers\ItineraryApprovalController::class, 'vpPending']
    )->name('motorpool.approvals.itineraries.vp');

    Route::get(
        '/motorpool/approvals/itineraries/approved',
        [App\Http\Controllers\ItineraryApprovalController::class, 'approved']
    )->name('motorpool.approvals.itineraries.approved');

    Route::get(
        '/motorpool/approvals/trip-tickets/vp',
        [App\Http\Controllers\VpTripTicketApprovalController::class, 'vpPending']
    )->name('motorpool.approvals.triptickets.vp');


    // ========================================================
    // TRIP TICKET DETAILS
    // ========================================================

    Route::get(
        '/api/trip-tickets/{id}',
        [App\Http\Controllers\TripTicketController::class, 'getDetails']
    )->name('api.trip-tickets.details');


    // ========================================================
    // VP TRIP TICKET APPROVAL
    // ========================================================

    Route::prefix('vp/approvals/trip-tickets')
        ->name('vp.trip-tickets.approvals.')
        ->group(function () {

            Route::get(
                '/',
                [App\Http\Controllers\VpTripTicketApprovalController::class, 'index']
            )->name('index');

            Route::get(
                '/{tripTicket}',
                [App\Http\Controllers\VpTripTicketApprovalController::class, 'show']
            )->name('show');

            Route::put(
                '/{tripTicket}/approve',
                [App\Http\Controllers\VpTripTicketApprovalController::class, 'approve']
            )->name('approve');

            Route::put(
                '/{tripTicket}/reject',
                [App\Http\Controllers\VpTripTicketApprovalController::class, 'reject']
            )->name('reject');

        });


    // ========================================================
    // EMPLOYEE TRIP TICKETS
    // ========================================================

    Route::prefix('employee/trip-tickets')
        ->name('employee.trip-tickets.')
        ->group(function () {

            Route::get(
                '/',
                [App\Http\Controllers\EmployeeTripTicketController::class, 'index']
            )->name('index');

            Route::get(
                '/{tripTicket}',
                [App\Http\Controllers\EmployeeTripTicketController::class, 'show']
            )->name('show');

            Route::get(
                '/{tripTicket}/excel',
                [App\Http\Controllers\EmployeeTripTicketController::class, 'generateExcel']
            )->name('pdf');

            Route::get(
                '/api/{id}',
                [App\Http\Controllers\EmployeeTripTicketController::class, 'getDetails']
            )->name('api.details');

        });


    // ========================================================
    // VEHICLE TRAVEL HISTORY
    // ========================================================

    Route::prefix('vehicles')
        ->name('vehicles.')
        ->group(function () {

            Route::get(
                '/travel-history',
                [App\Http\Controllers\VehicleTravelHistoryController::class, 'index']
            )->name('travel-history');

        });


    // ========================================================
    // VEHICLE MAINTENANCE
    // ========================================================

    Route::resource(
        'vehicle-maintenance',
        App\Http\Controllers\VehicleMaintenanceController::class
    );

    Route::get(
        '/vehicle-maintenance/{vehicleMaintenance}/excel',
        [App\Http\Controllers\VehicleMaintenanceController::class, 'generateExcel']
    )->name('vehicle-maintenance.pdf');

    Route::get(
        '/vehicles/{vehicle}/completed-maintenance-pdf',
        [App\Http\Controllers\VehicleMaintenanceController::class, 'generateCompletedMaintenancePDF']
    )->name('vehicles.completed-maintenance.pdf');

    Route::get(
        '/vehicles/{vehicle}/maintenance',
        [App\Http\Controllers\VehicleMaintenanceController::class, 'maintenanceByVehicle']
    )->name('vehicles.maintenance');

    Route::get(
        '/api/vehicle-maintenance/{vehicleMaintenance}',
        [App\Http\Controllers\VehicleMaintenanceController::class, 'apiShow']
    );

    Route::post(
        '/vehicle-maintenance/{vehicleMaintenance}/start',
        [App\Http\Controllers\VehicleMaintenanceController::class, 'startMaintenance']
    )->name('vehicle-maintenance.start');

    Route::post(
        '/vehicle-maintenance/{vehicleMaintenance}/complete',
        [App\Http\Controllers\VehicleMaintenanceController::class, 'completeMaintenance']
    )->name('vehicle-maintenance.complete');

    // Admin "Maintenance Needed" page - fleet-wide oil change + open work orders
    Route::get(
        '/maintenance/needed',
        [App\Http\Controllers\VehicleMaintenanceController::class, 'needed']
    )->name('vehicle-maintenance.needed');

    // Header notification bell feed (oil change + open maintenance records)
    Route::get(
        '/api/maintenance/notifications',
        [App\Http\Controllers\VehicleMaintenanceController::class, 'notifications']
    )->name('vehicle-maintenance.notifications');

    // Remove a work order from this admin's notifications (POST) / undo it (DELETE)
    Route::post(
        '/api/maintenance/{vehicleMaintenance}/dismiss-notification',
        [App\Http\Controllers\VehicleMaintenanceController::class, 'dismissNotification']
    )->name('vehicle-maintenance.notification.dismiss');

    Route::delete(
        '/api/maintenance/{vehicleMaintenance}/dismiss-notification',
        [App\Http\Controllers\VehicleMaintenanceController::class, 'restoreNotification']
    )->name('vehicle-maintenance.notification.restore');


    // ========================================================
    // MOTORPOOL DASHBOARD
    // ========================================================

    Route::prefix('motorpool')
        ->name('motorpool.')
        ->group(function () {

            Route::get(
                '/dashboard',
                [MotorpoolAdminController::class, 'dashboard']
            )->name('dashboard');

            Route::get(
                '/api/calendar-events',
                [MotorpoolAdminController::class, 'calendarEvents']
            )->name('api.calendar-events');

            Route::get(
                '/api/analytics/most-used-vehicles',
                [MotorpoolAdminController::class, 'mostUsedVehicles']
            )->name('api.most-used-vehicles');

            Route::get(
                '/api/analytics/most-assigned-drivers',
                [MotorpoolAdminController::class, 'mostAssignedDrivers']
            )->name('api.most-assigned-drivers');

            Route::get(
                '/api/analytics/completed-trips',
                [MotorpoolAdminController::class, 'completedTrips']
            )->name('api.completed-trips');

            Route::get(
                '/api/analytics/completed-trips-monthly',
                [MotorpoolAdminController::class, 'completedTripsMonthly']
            )->name('api.completed-trips-monthly');

            Route::get(
                '/api/analytics/completed-trips-daily',
                [MotorpoolAdminController::class, 'completedTripsDaily']
            )->name('api.completed-trips-daily');

            Route::get(
                '/api/analytics/completed-trips-weekly',
                [MotorpoolAdminController::class, 'completedTripsWeekly']
            )->name('api.completed-trips-weekly');

            Route::get(
                '/api/analytics/top-destinations',
                [MotorpoolAdminController::class, 'topDestinations']
            )->name('api.top-destinations');


            Route::get(
                '/travel-orders/{travelOrder}/excel',
                [App\Http\Controllers\MotorpoolAdminTravelOrderController::class, 'generateExcel']
            )->name('travel-orders.pdf');


            Route::resource(
                'travel-orders',
                App\Http\Controllers\MotorpoolAdminTravelOrderController::class
            )->only([
                'index',
                'show',
                'store'
            ]);


            // Oil Change Notifications
            Route::get(
                '/api/vehicles/oil-change-notification',
                [App\Http\Controllers\VehicleController::class, 'getOilChangeNotification']
            )->name('api.vehicles.oil-change-notification');

            Route::post(
                '/api/vehicles/dismiss-oil-change-notification',
                [App\Http\Controllers\VehicleController::class, 'dismissOilChangeNotification']
            )->name('api.vehicles.dismiss-oil-change-notification');

            // Dashcam registration (one dashcam per vehicle, one QR + code per dashcam)
            Route::middleware('role:motorpool_admin')->group(function () {
                Route::get('/dashcams', [App\Http\Controllers\DashcamController::class, 'index'])
                    ->name('dashcams.index');
                Route::post('/dashcams', [App\Http\Controllers\DashcamController::class, 'store'])
                    ->name('dashcams.store');
                Route::get('/dashcams/{dashcam}', [App\Http\Controllers\DashcamController::class, 'show'])
                    ->name('dashcams.show');
                Route::post('/dashcams/{dashcam}/regenerate', [App\Http\Controllers\DashcamController::class, 'regenerate'])
                    ->name('dashcams.regenerate');
                Route::delete('/dashcams/{dashcam}', [App\Http\Controllers\DashcamController::class, 'destroy'])
                    ->name('dashcams.destroy');
            });

        });

});


// ============================================================
// LOGIN
// ============================================================

Route::get('/login', function () {
    return view('auth.login');
})->name('login');


// ============================================================
// ABOUT
// ============================================================

Route::get('/about', function () {

    $unitHeads =
        \App\Models\Employee::whereHas(
            'officer',
            function($query) {
                $query->where(
                    'unit_head',
                    true
                );
            }
        )
        ->with([
            'officer',
            'positions.office',
            'positions.unit',
            'user'
        ])
        ->get()
        ->map(function($employee) {

            $primaryPosition =
                $employee->positions->firstWhere(
                    'is_primary',
                    true
                )
                ??
                $employee->positions->first();


            return [

                'name' =>
                    $employee->first_name
                    . ' '
                    . $employee->last_name,

                'position' =>
                    $primaryPosition
                        ? $primaryPosition->position_name
                        : 'Unit Head',

                'office' =>
                    $primaryPosition
                        && $primaryPosition->office
                            ? $primaryPosition->office->office_name
                            : null,

                'unit' =>
                    $primaryPosition
                        && $primaryPosition->unit
                            ? $primaryPosition->unit->unit_name
                            : null,

                'email' =>
                    $employee->user
                        ? $employee->user->email
                        : null,

                'is_primary' =>
                    $primaryPosition
                        ? $primaryPosition->is_primary
                        : false,

            ];

        });


    return view(
        'auth.about',
        compact('unitHeads')
    );

})->name('about');


// ============================================================
// LOGIN POST
// ============================================================

Route::post(
    '/login',
    [AuthenticatedSessionController::class, 'store']
);


// ============================================================
// TEST DIVISION MODAL
// ============================================================

Route::get(
    '/test-division-modal',
    function () {
        return view('test_division_modal');
    }
)->name('test.division.modal');


// ============================================================
// LOGOUT
// ============================================================

Route::post(
    '/logout',
    [AuthenticatedSessionController::class, 'destroy']
)
->middleware('auth')
->name('logout');


// ============================================================
// AUTH ROUTES
// ============================================================

require __DIR__.'/auth.php';