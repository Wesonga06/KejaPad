<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLandlordApproved
{
    /**
     * Prevent landlords whose account is not approved from using landlord
     * functionality. Non-landlord roles pass through untouched.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isLandlord()) {
            return $next($request);
        }

        if ($user->canAccessLandlordFeatures()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Your landlord account is not approved.');
        }

        return redirect()->route('landlord.pending');
    }
}
