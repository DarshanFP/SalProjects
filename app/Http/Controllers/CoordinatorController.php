<?php

namespace App\Http\Controllers;

use App\Models\Reports\Quarterly\RQWDReport;
use App\Models\Reports\Quarterly\RQSTReport;
use App\Models\Reports\Quarterly\RQISReport;
use App\Models\Reports\Quarterly\RQDPReport;
use App\Models\Reports\Quarterly\RQDLReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CoordinatorController extends Controller
{
    public function CoordinatorDashboard()
    {
        $coordinator = auth()->user();

        // Get IDs of Provincials under the Coordinator
        $provincialIds = User::where('parent_id', $coordinator->id)->pluck('id');

        // Get IDs of Executors under those Provincials
        $executorIds = User::whereIn('parent_id', $provincialIds)->pluck('id');

        // Fetch reports created by these Executors with Provincial info
        $rqwdReports = RQWDReport::with('user.parent')->whereIn('user_id', $executorIds)->get();
        $rqstReports = RQSTReport::with('user.parent')->whereIn('user_id', $executorIds)->get();
        $rqisReports = RQISReport::with('user.parent')->whereIn('user_id', $executorIds)->get();
        $rqdpReports = RQDPReport::with('user.parent')->whereIn('user_id', $executorIds)->get();
        $rqdlReports = RQDLReport::with('user.parent')->whereIn('user_id', $executorIds)->get();

        return view('coordinator.index', compact('rqwdReports', 'rqstReports', 'rqisReports', 'rqdpReports', 'rqdlReports'));
    }

    public function showReport($type, $id)
    {
        switch ($type) {
            case 'rqwd':
                $report = RQWDReport::with('user.parent')->findOrFail($id);
                break;
            case 'rqst':
                $report = RQSTReport::with('user.parent')->findOrFail($id);
                break;
            case 'rqis':
                $report = RQISReport::with('user.parent')->findOrFail($id);
                break;
            case 'rqdp':
                $report = RQDPReport::with('user.parent')->findOrFail($id);
                break;
            case 'rqdl':
                $report = RQDLReport::with('user.parent')->findOrFail($id);
                break;
            default:
                abort(404);
        }

        return view('coordinator.show_report', compact('report'));
    }

    //To manage Provincials
    // List all provincials
    public function createProvincial()
    {
        return view('coordinator.createProvincial');
    }

    public function storeProvincial(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'role' => 'required|string|max:50',
            'province' => 'required|string|max:255', // Add validation rule for province
            'status' => 'required|string|max:50',
        ]);

        User::create([
            'parent_id' => auth()->user()->id,
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'center' => $request->center,
            'address' => $request->address,
            'role' => 'provincial',
            'province' => $request->province, // Ensure province is being set
            'status' => $request->status,
        ]);

        return redirect()->route('coordinator.provincials')->with('success', 'Provincial created successfully.');
    }

    public function listProvincials()
    {
        $provincials = User::where('role', 'provincial')->get();
        return view('coordinator.provincials', compact('provincials'));
    }

    public function editProvincial($id)
    {
        $provincial = User::findOrFail($id);
        return view('coordinator.editProvincial', compact('provincial'));
    }

    public function updateProvincial(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'role' => 'required|string|max:50',
            'status' => 'required|string|max:50',
        ]);

        $provincial = User::findOrFail($id);
        $provincial->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'center' => $request->center,
            'address' => $request->address,
            'role' => 'provincial',
            'status' => $request->status,
        ]);

        return redirect()->route('coordinator.provincials')->with('success', 'Provincial updated successfully.');
    }

    public function resetProvincialPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $provincial = User::findOrFail($id);
        $provincial->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('coordinator.provincials')->with('success', 'Provincial password reset successfully.');
    }
}
