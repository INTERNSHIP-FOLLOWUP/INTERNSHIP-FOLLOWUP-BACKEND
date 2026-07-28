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

        if ($user) {
            if ($user->trashed() || $user->status === 'deactivated') {
                $request->user()->currentAccessToken()?->delete();

                return response()->json([
                    'message' => 'Your account has been deactivated. Please contact system administrator.',
                ], 403);
            }

            if ($user->status === 'inactive') {
                if ($user->must_change_password) {
                    $path = $request->path();
                    $allowed = [
                        'api/profile/password',
                        'api/profile',
                        'api/auth/user',
                        'api/auth/logout',
                    ];

                    $isAllowed = false;
                    foreach ($allowed as $route) {
                        if ($path === $route || str_ends_with($path, 'password') || str_contains($path, 'profile')) {
                            $isAllowed = true;
                            break;
                        }
                    }

                    if (!$isAllowed) {
                        return response()->json([
                            'message' => 'You must change your password before accessing system resources.',
                            'must_change_password' => true,
                        ], 403);
                    }
                } 
            }
        }

        return $next($request);
    }
}
