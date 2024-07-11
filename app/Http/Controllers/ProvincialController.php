<?php

namespace App\Http\Controllers;

use App\Models\Reports\Quarterly\RQDLReport;
use App\Models\Reports\Quarterly\RQDPReport;
use App\Models\Reports\Quarterly\RQISReport;
use App\Models\Reports\Quarterly\RQSTReport;
use App\Models\Reports\Quarterly\RQWDReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProvincialController extends Controller
{
    //access to provincials only.
    public function __construct()
    {
        $this->middleware(['auth', 'role:provincial']);
    }
    //index page for provincial
    public function ProvincialDashboard()
    {
    //     return view ('provincial.index');
    // }

    $provincial = auth()->user();

    $rqwdReports = RQWDReport::whereHas('user', function ($query) use ($provincial) {
        $query->where('parent_id', $provincial->id);
    })->get();

    $rqstReports = RQSTReport::whereHas('user', function ($query) use ($provincial) {
        $query->where('parent_id', $provincial->id);
    })->get();

    $rqisReports = RQISReport::whereHas('user', function ($query) use ($provincial) {
        $query->where('parent_id', $provincial->id);
    })->get();

    $rqdpReports = RQDPReport::whereHas('user', function ($query) use ($provincial) {
        $query->where('parent_id', $provincial->id);
    })->get();

    $rqdlReports = RQDLReport::whereHas('user', function ($query) use ($provincial) {
        $query->where('parent_id', $provincial->id);
    })->get();

    return view('provincial.index', compact('rqwdReports', 'rqstReports', 'rqisReports', 'rqdpReports', 'rqdlReports'));
}

public function showReport($type, $id)
{
    switch ($type) {
        case 'rqwd':
            $report = RQWDReport::findOrFail($id);
            break;
        case 'rqst':
            $report = RQSTReport::findOrFail($id);
            break;
        case 'rqis':
            $report = RQISReport::findOrFail($id);
            break;
        case 'rqdp':
            $report = RQDPReport::findOrFail($id);
            break;
        case 'rqdl':
            $report = RQDLReport::findOrFail($id);
            break;
        default:
            abort(404);
    }

    return view('provincial.show_report', compact('report'));
}



    // show Create Executor form
    public function CreateExecutor()
        {
            return view('provincial.createExecutor');
        }

        // store Executor
        public function StoreExecutor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:255',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string',
        ]);

        $provincial = auth()->user();

        $executor = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'province' => $provincial->province, // Assign the same province as the provincial
            'center' => $request->center,
            'address' => $request->address,
            'role' => 'executor',
            'status' => 'active',
            'parent_id' => $provincial->id,
        ]);

        $executor->assignRole('executor');

        return redirect()->route('provincial.createExecutor')->with('success', 'Executor created successfully.');
    }

    // list of Executors
    public function listExecutors()
    {
        $provincial = auth()->user();
        $executors = User::where('parent_id', $provincial->id)->where('role', 'executor')->get();

        return view('provincial.executors', compact('executors'));
    }
    // Edit Executor
    public function editExecutor($id)
    {
        $executor = User::findOrFail($id);

        return view('provincial.editExecutor', compact('executor'));
    }
    // Update Executor
    public function updateExecutor(Request $request, $id)
    {
        $executor = User::findOrFail($id);

        $request->validate([
            'phone' => 'nullable|string|max:255',
            'center' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $executor->update([
            'phone' => $request->phone,
            'center' => $request->center,
            'status' => $request->status,
        ]);

        return redirect()->route('provincial.executors')->with('success', 'Executor updated successfully.');
    }
    //Reset Executor Password
    public function resetExecutorPassword(Request $request, $id)
    {
        $executor = User::findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $executor->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('provincial.executors')->with('success', 'Executor password reset successfully.');
    }


}
