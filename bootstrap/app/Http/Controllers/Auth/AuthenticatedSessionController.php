<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Officer;
use App\Models\Employee;
use App\Models\Driver;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // Get all Unit Heads with their information
        $unitHeads = Employee::whereHas('officer', function($query) {
            $query->where('unit_head', true);
        })
        ->with(['officer', 'positions.office', 'positions.unit', 'user'])
        ->get()
        ->map(function($employee) {
            // Get the primary position
            $primaryPosition = $employee->positions->firstWhere('is_primary', true) ?? $employee->positions->first();
            
            return [
                'name' => $employee->first_name . ' ' . $employee->last_name,
                'position' => $primaryPosition ? $primaryPosition->position_name : 'Unit Head',
                'office' => $primaryPosition && $primaryPosition->office ? $primaryPosition->office->office_name : null,
                'unit' => $primaryPosition && $primaryPosition->unit ? $primaryPosition->unit->unit_name : null,
                'email' => $employee->user ? $employee->user->email : null,
                'is_primary' => $primaryPosition ? $primaryPosition->is_primary : false,
            ];
        });
        
        return view('auth.login', compact('unitHeads'));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Redirect based on user role
        $user = Auth::user();
        
        if ($user->isMotorpoolAdmin()) {
            return redirect()->route('motorpool.dashboard');
        } elseif ($user->isAdmin()) {
            return redirect()->intended(route('dashboard', absolute: false));
        } elseif ($user->isDriver()) {
            // Drivers don't have a dashboard - redirect to employee dashboard
            return redirect()->intended(route('dashboard', absolute: false));
        } elseif ($user->isEmployee()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Default redirect
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Turn off location tracking if the logging-out user is a driver
        if ($user && method_exists($user, 'isDriver') && $user->isDriver()) {
            $driver = Driver::where('user_id', $user->id)->first();

            if ($driver) {
                $driver->update([
                    'is_tracking' => false,
                    'is_online' => false,
                ]);
            }
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}