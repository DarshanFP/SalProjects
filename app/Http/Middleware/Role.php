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
    public function handle(Request $request, Closure $next, $role): Response
    {
        if ($request->user()->role != $role) {
            // Redirect to the appropriate dashboard based on the user's role
            return redirect($this->getDashboardUrl($request->user()->role));
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
