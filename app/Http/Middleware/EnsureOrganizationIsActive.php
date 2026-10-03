<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // no authenticated user
        if (! $user){
            return $next($request);
        }

        // super-admins are not tied to an organization
        if ($user->organization && ! $user->organization->is_active) {
            return response()->json([
                'message' => 'Your organization has been deactivated.',
            ], 403);
        }

        return $next($request);
    }
}
