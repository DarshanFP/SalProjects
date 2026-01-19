<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated

{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                $role = $user->role ?? 'unknown';
                
                // Redirect based on role to prevent loops
                $redirectUrl = match($role) {
                    'admin' => '/admin/dashboard',
                    'general' => '/general/dashboard', // General has own dashboard
                    'coordinator' => '/coordinator/dashboard',
                    'provincial' => '/provincial/dashboard',
                    'executor' => '/executor/dashboard',
                    'applicant' => '/executor/dashboard', // Applicants get same access as executors
                    default => '/profile', // Default to profile for unknown roles
                };
                
                Log::info('RedirectIfAuthenticated - User already authenticated, redirecting', [
                    'user_id' => $user->id ?? null,
                    'role' => $role,
                    'current_url' => $request->fullUrl(),
                    'redirect_to' => $redirectUrl,
                ]);
                
                return redirect($redirectUrl);
            }
        }

        return $next($request);
    }
}
// {
//     /**
//      * Handle an incoming request.
//      *
//      * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
//      */
//     public function handle(Request $request, Closure $next, string ...$guards): Response
//     {
//         $guards = empty($guards) ? [null] : $guards;

//         foreach ($guards as $guard) {
//             if (Auth::guard($guard)->check()) {
//                 return redirect(RouteServiceProvider::HOME);
//             }
//         }

//         return $next($request);
//     }
// }
