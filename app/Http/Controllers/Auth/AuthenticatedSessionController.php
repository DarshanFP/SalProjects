<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    // public function store(LoginRequest $request): RedirectResponse
    // {
    //     $request->authenticate();

    //     $request->session()->regenerate();

    //     $url = '';
    //     if ($request->user()->role === 'admin') {
    //         $url = 'admin/dashboard';
    //     } elseif ($request->user()->role === 'coordinator') {
    //         $url = 'coordinator/dashboard';
    //     } elseif ($request->user()->role === 'provincial') {
    //         $url = 'provincial/dashboard';
    //     } elseif ($request->user()->role === 'executor') {
    //         $url = 'executor/dashboard';
    //     }

    //     return redirect()->intended($url);
    // }

    public function store(LoginRequest $request): RedirectResponse
{
    Log::info('AuthenticatedSessionController@store - Starting login process', [
        'email' => $request->email,
        'intended_url' => $request->session()->get('url.intended'),
    ]);

    $request->authenticate();
    $request->session()->regenerate();

    $user = $request->user();
    $role = $user->role;

    Log::info('AuthenticatedSessionController@store - User authenticated', [
        'user_id' => $user->id,
        'role' => $role,
        'email' => $user->email,
    ]);

    $url = match($role) {
        'admin' => '/admin/dashboard',
        'general' => '/general/dashboard', // General has own dashboard
        'coordinator' => '/coordinator/dashboard',
        'provincial' => '/provincial/dashboard',
        'executor' => '/executor/dashboard',
        'applicant' => '/executor/dashboard', // Applicants get same access as executors
        default => '/profile', // Fallback to profile for unknown roles instead of login to prevent loops
    };

    Log::info('AuthenticatedSessionController@store - Redirecting after login', [
        'role' => $role,
        'redirect_url' => $url,
    ]);

    // Use direct redirect instead of intended to avoid redirect loops
    return redirect($url);
}


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
