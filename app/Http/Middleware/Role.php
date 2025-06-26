<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        $userRole = $request->user()->role;

        // Parse roles - handle both comma-separated strings and individual parameters
        $allowedRoles = [];
        foreach ($roles as $role) {
            if (strpos($role, ',') !== false) {
                $allowedRoles = array_merge($allowedRoles, explode(',', $role));
            } else {
                $allowedRoles[] = $role;
            }
        }

        // Check if user's role is in the allowed roles
        if (!in_array($userRole, $allowedRoles)) {
            // Redirect to the appropriate dashboard based on the user's role
            return redirect($this->getDashboardUrl($userRole));
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
            'admin' => 'admin/dashboard',
            'coordinator' => 'coordinator/dashboard',
            'provincial' => 'provincial/dashboard',
            'executor' => 'executor/dashboard',
            default => 'dashboard',
        };
    }
}
