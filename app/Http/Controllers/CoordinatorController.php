<?php

namespace App\Http\Controllers;

use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\ReportComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class CoordinatorController extends Controller
{
    public function CoordinatorDashboard(Request $request)
    {
        $coordinator = Auth::user();

        // Fetch all projects with the user's province relationship
        $projectsQuery = Project::query()->with('user');

        // Filtering logic
        if ($request->filled('province')) {
            $projectsQuery->whereHas('user', function($query) use ($request) {
                $query->where('province', $request->province);
            });
        }
        if ($request->filled('user_id')) {
            $projectsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        $projects = $projectsQuery->get();
        $reports = DPReport::whereIn('project_id', $projects->pluck('project_id'))->get();

        $provinces = User::distinct()->pluck('province');
        $users = User::all();

        return view('coordinator.index', compact('reports', 'coordinator', 'provinces', 'users'));
    }

    // public function showReport($report_id)
    // {
    //     $report = DPReport::with([
    //         'user.parent',
    //         'objectives.activities',
    //         'accountDetails',
    //         'photos',
    //         'outlooks',
    //         'annexures',
    //         'rqis_age_profile',
    //         'rqst_trainee_profile',
    //         'rqwd_inmate_profile',
    //         'comments.user' // Load comments with associated user
    //     ])->findOrFail($report_id);

    //     return view('coordinator.index', compact('report'));
    // }

    public function showMonthlyReport($report_id)
{
    $report = DPReport::with([
        'user.parent',
        'objectives.activities',
        'accountDetails',
        'photos',
        'outlooks',
        'annexures',
        'rqis_age_profile',
        'rqst_trainee_profile',
        'rqwd_inmate_profile',
        'comments.user' // Load comments with associated user
    ])->where('report_id', $report_id)->firstOrFail();

    // Coordinator can view all reports, so no additional authorization needed here
    // If you need to restrict access further, you can add authorization logic

    return view('reports.monthly.show', compact('report'));
}


    public function storeComment(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $report = DPReport::findOrFail($id);

        $commentId = $report->generateCommentId();

        ReportComment::create([
            'R_comment_id' => $commentId,
            'report_id' => $report->report_id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
        ]);

        return redirect()->back()->with('success', 'Comment added successfully.');
    }

    // Other methods remain unchanged
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
