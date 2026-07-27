<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->status === 'inactive' || $user->trashed())) {
            // Revoke current token
            $request->user()->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Your account is inactive. Please contact system administrator.',
            ], 403);
        }

        return $next($request);
    }
}
