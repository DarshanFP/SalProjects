<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Reports\Monthly\ReportController;
use App\Models\OldProjects\Project;
use App\Models\ProjectComment;
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

    public function ReportList(Request $request)
{
    $coordinator = Auth::user();

    // Project types the coordinator should NOT see
    $excludedTypes = [
        'Individual - Ongoing Educational support',
        'Individual - Livelihood Application',
        'Individual - Access to Health',
        'Individual - Initial - Educational support',
    ];

    // Base query for projects excluding the restricted types
    $projectsQuery = Project::whereNotIn('project_type', $excludedTypes)
        ->with('user');

    // Optional Filtering for Province
    if ($request->filled('province')) {
        $projectsQuery->whereHas('user', function($query) use ($request) {
            $query->where('province', $request->province);
        });
    }

    // Optional Filtering for Executor/User
    if ($request->filled('user_id')) {
        $projectsQuery->where('user_id', $request->user_id);
    }

    // Optional Filtering for Project Type (make sure it's not excluded)
    if ($request->filled('project_type')) {
        if (!in_array($request->project_type, $excludedTypes)) {
            $projectsQuery->where('project_type', $request->project_type);
        }
    }

    // Fetch the allowed projects
    $projects = $projectsQuery->get();

    // Fetch reports for these filtered projects
    $reports = DPReport::whereIn('project_id', $projects->pluck('project_id'))->get();

    // Fetch provinces and users for filtering options
    $provinces = User::distinct()->pluck('province');
    $users = User::all();

    // Fetch distinct project types that are allowed
    $projectTypes = Project::whereNotIn('project_type', $excludedTypes)
        ->distinct()
        ->pluck('project_type');

    // Return the ReportList view with the filtered reports, project types, etc.
    return view('coordinator.ReportList', compact('reports', 'coordinator', 'provinces', 'users', 'projectTypes'));
}




// public function ProjectList(Request $request)
// {
//     $coordinator = Auth::user();

//     // Project types the coordinator should NOT see
//     $excludedTypes = [
//         'Individual - Ongoing Educational support',
//         'Individual - Livelihood Application',
//         'Individual - Access to Health',
//         'Individual - Initial - Educational support',
//     ];

//     // Base query for projects excluding the restricted types
//     $projectsQuery = Project::whereNotIn('project_type', $excludedTypes)
//         ->with('user');

//     // Apply optional filters
//     if ($request->filled('project_type')) {
//         if (!in_array($request->project_type, $excludedTypes)) {
//             $projectsQuery->where('project_type', $request->project_type);
//         }
//     }

//     if ($request->filled('user_id')) {
//         $projectsQuery->where('user_id', $request->user_id);
//     }

//     // If province is selected, filter projects by the province of the user
//     if ($request->filled('province')) {
//         $projectsQuery->whereHas('user', function($query) use ($request) {
//             $query->where('province', $request->province);
//         });
//     }

//     // Fetch projects
//     $projects = $projectsQuery->get();

//     // Fetch list of executors for filtering
//     $users = User::all();

//     // Fetch distinct project types that are not excluded
//     $projectTypes = Project::whereNotIn('project_type', $excludedTypes)
//         ->distinct()
//         ->pluck('project_type');

//     // Fetch distinct provinces from users
//     $provinces = User::distinct()->pluck('province');

//     return view('coordinator.ProjectList', compact('projects', 'coordinator', 'projectTypes', 'users', 'provinces'));
// }
public function ProjectList(Request $request)
{
    $coordinator = Auth::user();

    // Project types the coordinator should NOT see
    $excludedTypes = [
        'Individual - Ongoing Educational support',
        'Individual - Livelihood Application',
        'Individual - Access to Health',
        'Individual - Initial - Educational support',
    ];

    // Base query for projects excluding the restricted types
    $projectsQuery = Project::whereNotIn('project_type', $excludedTypes)
        ->with('user');

    // Optional province filter
    if ($request->filled('province')) {
        $projectsQuery->whereHas('user', function($query) use ($request) {
            $query->where('province', $request->province);
        });
    }

    // Optional project_type filter
    if ($request->filled('project_type')) {
        if (!in_array($request->project_type, $excludedTypes)) {
            $projectsQuery->where('project_type', $request->project_type);
        }
    }

    // Optional executor (user_id) filter
    if ($request->filled('user_id')) {
        $projectsQuery->where('user_id', $request->user_id);
    }

    $projects = $projectsQuery->get();

    // Fetch distinct project types that are allowed
    $projectTypes = Project::whereNotIn('project_type', $excludedTypes)
        ->distinct()
        ->pluck('project_type');

    // Fetch distinct provinces from users
    $provinces = User::distinct()->pluck('province');

    // Build the users query to show only executors
    $usersQuery = User::where('role', 'executor');

    // If a province is selected, filter executors by that province
    if ($request->filled('province')) {
        $usersQuery->where('province', $request->province);
    }

    $users = $usersQuery->get();

    return view('coordinator.ProjectList', compact('projects', 'coordinator', 'projectTypes', 'users', 'provinces'));
}


// In CoordinatorController
public function getExecutorsByProvince(Request $request)
{
    $province = $request->get('province');
    // Only executors
    $usersQuery = User::where('role', 'executor');

    if ($province) {
        $usersQuery->where('province', $province);
    }

    $executors = $usersQuery->get(['id', 'name']);

    return response()->json($executors);
}


public function showProject($project_id)
{
    // Retrieve the project
    $project = Project::where('project_id', $project_id)
        ->with('user')
        ->firstOrFail();

    // Define excluded types
    $excludedTypes = [
        'Individual - Ongoing Educational support',
        'Individual - Livelihood Application',
        'Individual - Access to Health',
        'Individual - Initial - Educational support',
    ];

    // Check if the project type is restricted
    if (in_array($project->project_type, $excludedTypes)) {
        abort(403, 'Unauthorized - Project type is not accessible to coordinator.');
    }

    // If allowed, call ProjectController@show
    return app(ProjectController::class)->show($project_id);
}



    public function showMonthlyReport($report_id)
{
    $report = DPReport::with([
        'user.parent',
        // 'objectives.activities',
        // 'accountDetails',
        // 'photos',
        // 'outlooks',
        // 'annexures',
        // 'rqis_age_profile',
        // 'rqst_trainee_profile',
        // 'rqwd_inmate_profile',
        'comments.user' // Load comments with associated user
    ])->where('report_id', $report_id)->firstOrFail();

    // Coordinator can view all reports, so no additional authorization needed here
    // If you need to restrict access further, you can add authorization logic

    // return view('reports.monthly.show', compact('report'));
    return app(ReportController::class)->show($report_id);
}

// Add comment to a report
    public function addComment(Request $request, $report_id)
    {
        $coordinator = auth()->user();

        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        // Add any authorization checks if needed

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $commentId = $report->generateCommentId();

        ReportComment::create([
            'R_comment_id' => $commentId,
            'report_id' => $report->report_id,
            'user_id' => $coordinator->id,
            'comment' => $request->comment,
        ]);

        return redirect()->back()->with('success', 'Comment added successfully.');
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
    public function addProjectComment(Request $request, $project_id)
    {
        $coordinator = auth()->user();

        $project = Project::where('project_id', $project_id)->firstOrFail();

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $commentId = $project->generateProjectCommentId();

        ProjectComment::create([
            'project_comment_id' => $commentId,
            'project_id' => $project->project_id,
            'user_id' => $coordinator->id,
            'comment' => $request->comment,
        ]);

        return redirect()->back()->with('success', 'Comment added successfully.');
    }

    public function editProjectComment($id)
    {
        $comment = ProjectComment::findOrFail($id);
        $user = auth()->user();

        // Ensure the user owns this comment
        if ($comment->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        return view('projects.comments.edit', compact('comment'));
    }

    public function updateProjectComment(Request $request, $id)
    {
        $comment = ProjectComment::findOrFail($id);
        $user = auth()->user();

        if ($comment->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $comment->update([
            'comment' => $request->comment,
        ]);

        return redirect()->back()->with('success', 'Comment updated successfully.');
    }
// // Status
public function revertToProvincial($project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $coordinator = auth()->user();

    if($coordinator->role !== 'coordinator' || $project->status !== 'forwarded_to_coordinator') {
        abort(403, 'Unauthorized action.');
    }

    $project->status = 'reverted_by_coordinator';
    $project->save();

    return redirect()->back()->with('success', 'Project reverted to Provincial.');
}

public function approveProject($project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $coordinator = auth()->user();

    if($coordinator->role !== 'coordinator' || $project->status !== 'forwarded_to_coordinator') {
        abort(403, 'Unauthorized action.');
    }

    $project->status = 'approved_by_coordinator';
    $project->save();

    return redirect()->back()->with('success', 'Project approved successfully.');
}

public function rejectProject($project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $coordinator = auth()->user();

    if($coordinator->role !== 'coordinator' || $project->status !== 'forwarded_to_coordinator') {
        abort(403, 'Unauthorized action.');
    }

    $project->status = 'rejected_by_coordinator';
    $project->save();

    return redirect()->back()->with('success', 'Project rejected successfully.');
}

}
