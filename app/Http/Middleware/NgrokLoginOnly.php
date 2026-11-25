<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NgrokLoginOnly
{
    public function handle(Request $request, Closure $next)
    {
        // ngrok adds X-Forwarded-Host with the public domain
        $forwardedHost = $request->header('X-Forwarded-Host', '');

        if (str_contains($forwardedHost, 'ngrok')) {
            $allowed = $request->is(
                'login',
                'build/*', 'css/*', 'js/*', 'images/*', 'fonts/*',
                'storage/*', 'favicon.ico'
            );

            if (! $allowed) {
                abort(403, 'This page is not available publicly.');
            }
        }

        return $next($request);
    }
}