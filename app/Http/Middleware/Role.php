<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Get the user's role
        $user = $request->user();
        $userRole = $user->role;

        // Parse roles - handle both comma-separated strings and individual parameters
        $allowedRoles = [];
        foreach ($roles as $role) {
            if (strpos($role, ',') !== false) {
                $allowedRoles = array_merge($allowedRoles, explode(',', $role));
            } else {
                $allowedRoles[] = $role;
            }
        }

        Log::info('Role middleware - Checking access', [
            'user_id' => $user->id,
            'user_role' => $userRole,
            'allowed_roles' => $allowedRoles,
            'current_url' => $request->fullUrl(),
            'has_access' => in_array($userRole, $allowedRoles),
        ]);

        // Check if user's role is in the allowed roles
        if (!in_array($userRole, $allowedRoles)) {
            // Redirect to the appropriate dashboard based on the user's role
            $redirectUrl = $this->getDashboardUrl($userRole);
            
            Log::warning('Role middleware - Access denied, redirecting', [
                'user_id' => $user->id,
                'user_role' => $userRole,
                'allowed_roles' => $allowedRoles,
                'redirect_url' => $redirectUrl,
            ]);
            
            return redirect($redirectUrl);
        }

        return $next($request);
    }

    /**
     * Get the dashboard URL based on the user's role.
     *
     * @param  string  $role
     * @return string
     */
    protected function getDashboardUrl(string $role): string
    {
        return match($role) {
            'admin' => '/admin/dashboard',
            'general' => '/general/dashboard', // General has own dashboard
            'coordinator' => '/coordinator/dashboard',
            'provincial' => '/provincial/dashboard',
            'executor' => '/executor/dashboard',
            'applicant' => '/executor/dashboard', // Applicants get same access as executors
            default => '/profile', // Changed to '/profile' to prevent loops with login
        };
    }
}
