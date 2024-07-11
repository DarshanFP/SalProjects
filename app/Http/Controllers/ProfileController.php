<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    protected $profileData;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->profileData = User::find(Auth::user()->id);
            view()->share('profileData', $this->profileData);
            return $next($request);
        });
    }

    public function edit()
    {
        $id = Auth::user()->id;
        $profileData = User::find($id);
        return view('profileAll.profile', compact('profileData'));
    }

    public function update(Request $request)
    {
        $id = Auth::user()->id;
        $data = User::find($id);
        $data->name = $request->name;
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->center = $request->center;
        $data->save();
        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    public function changePassword()
    {
        $id = Auth::user()->id;
        $profileData = User::find($id);
        return view('profileAll.change-password', compact('profileData'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);

        if (!Hash::check($request->old_password, Auth::user()->password)) {
            return redirect()->back()->withErrors(['old_password' => 'The old password is incorrect']);
        }

        User::whereId(Auth::user()->id)->update(['password' => Hash::make($request->new_password)]);

        return redirect()->back()->with('success', 'Password updated successfully');
    }
}
