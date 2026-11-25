<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Driver;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Allow the request only if the user has one of the given roles.
     *
     * Usage: 'role:motorpool_admin' or 'role:motorpool_admin,admin'
     * Users without a matching role receive HTTP 403 with an "Access denied" message.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Driver check: the user must have a Driver record
        if (in_array('driver', $roles, true)) {
            if (Driver::where('user_id', $user->id)->exists()) {
                return $next($request);
            }
            abort(403, 'Access denied. You are not registered as a driver.');
        }

        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Access denied. Insufficient permissions.');
    }
}
