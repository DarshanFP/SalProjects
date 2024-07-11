<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    protected $profileData;

    public function __construct()
    {
        // Share the profile data with all views in this controller
        $this->middleware(function ($request, $next) {
            $this->profileData = User::find(Auth::user()->id);
            view()->share('profileData', $this->profileData);
            return $next($request);
        });
    }

    public function AdminDashboard()
    {
        return view('admin.dashboard');
    }

    public function AdminLogout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
