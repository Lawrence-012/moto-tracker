<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DriverProfileController extends Controller
{
    /**
     * Display the driver's profile form.
     */
    public function edit(): View
    {
        $user = Auth::user();
        
        // Get the driver record for the authenticated user
        $driver= Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return redirect()->route('dashboard')
                ->with('error', 'No driver record found for your account.');
        }
        
        return view('profile.driver-edit', [
            'user' => $user,
            'driver' => $driver,
        ]);
    }

    /**
     * Update the driver's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $driver= Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return redirect()->route('driver.profile.edit')
                ->with('error', 'No driver record found for your account.');
        }
        
        // Validate the request
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_initial' => 'nullable|string|max:10',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'address' => 'required|string|max:500',
            'contact_num' => 'required|string|size:11|unique:users,contact_num,' . $user->id,
            'position' => 'required|string|max:255',
            'official_station' => 'required|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        
        // Generate full names
        $fullName = $validated['first_name'] . ' ' . $validated['last_name'];
        $fullName2 = $validated['first_name'];
        if ($validated['middle_initial']) {
            $fullName2 .= ' ' . $validated['middle_initial'] . '.';
        }
        $fullName2 .= ' ' . $validated['last_name'];
        
        // Update driver information
        $driver->update([
            'first_name' => $validated['first_name'],
            'middle_initial' => $validated['middle_initial'] ?? null,
            'last_name' => $validated['last_name'],
            'full_name' => $fullName,
            'full_name2' => $fullName2,
            'address' => $validated['address'],
            'position' => $validated['position'],
            'official_station' => $validated['official_station'],
        ]);
        
        // Update user information
        $user->name = $fullName;
        $user->email = $validated['email'];
        $user->contact_num = $validated['contact_num'];
        
        // Update password if provided
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->save();
        
        return redirect()->route('driver.profile.edit')
            ->with('success', 'Profile updated successfully!');
    }
}
