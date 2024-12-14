<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Reports\Monthly\ReportController;
use App\Models\OldProjects\Project;
use App\Models\ProjectComment;
use App\Models\ReportComment;
use App\Models\Reports\Monthly\DPReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class ProvincialController extends Controller
{
    // Access to provincials only.
    public function __construct()
    {
        $this->middleware(['auth', 'role:provincial']);
    }

    // Index page for provincial
    public function ProvincialDashboard(Request $request)
    {
        $provincial = auth()->user();

        // Fetch reports for executors under the provincial
        $reportsQuery = DPReport::whereHas('user', function ($query) use ($provincial) {
            $query->where('parent_id', $provincial->id);
        });

        // Apply filtering if provided in the request
        if ($request->filled('place')) {
            $reportsQuery->where('place', $request->place);
        }
        if ($request->filled('user_id')) {
            $reportsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        $reports = $reportsQuery->get();

        // Fetch unique places and users for filters resources/views/projects/Coord-Prov-ProjectList.blade.php
        $places = DPReport::distinct()->pluck('place');
        $users = User::where('parent_id', $provincial->id)->get();

        return view('provincial.index', compact('reports', 'places', 'users'));
    }
    public function ReportList(Request $request)
{
    $provincial = auth()->user();

    // Fetch reports for executors under this provincial
    $reportsQuery = DPReport::whereHas('user', function ($query) use ($provincial) {
        $query->where('parent_id', $provincial->id);
    });

    // Apply any filters as needed
    if ($request->filled('place')) {
        $reportsQuery->where('place', $request->place);
    }
    if ($request->filled('user_id')) {
        $reportsQuery->where('user_id', $request->user_id);
    }
    if ($request->filled('project_type')) {
        $reportsQuery->where('project_type', $request->project_type);
    }

    $reports = $reportsQuery->get();

    // Fetch unique places and users for filters
    $places = DPReport::distinct()->pluck('place');
    $users = User::where('parent_id', $provincial->id)->get();

    return view('provincial.ReportList', compact('reports', 'places', 'users'));
}

public function ProjectList(Request $request)
{
    $provincial = auth()->user();

    // Fetch all projects where the project's user is a child of the provincial
    $projectsQuery = \App\Models\OldProjects\Project::whereHas('user', function($query) use ($provincial) {
        $query->where('parent_id', $provincial->id);
    });

    // Apply optional filters if you want:
    if ($request->filled('project_type')) {
        $projectsQuery->where('project_type', $request->project_type);
    }

    if ($request->filled('user_id')) {
        $projectsQuery->where('user_id', $request->user_id);
    }

    $projects = $projectsQuery->get();

    // Fetch distinct executors under this provincial for filtering
    $users = \App\Models\User::where('parent_id', $provincial->id)->get();

    // Distinct project types (if needed)
    $projectTypes = \App\Models\OldProjects\Project::distinct()->pluck('project_type');

    return view('provincial.ProjectList', compact('projects', 'users', 'projectTypes'));
}
public function showProject($project_id)
{
    $provincial = auth()->user();

    // Fetch the project and ensure it exists
    $project = Project::where('project_id', $project_id)
        ->with('user')
        ->firstOrFail();

    // Authorization check: the project's user must be a child (executor) of the current provincial
    if ($project->user->parent_id !== $provincial->id) {
        abort(403, 'Unauthorized');
    }

    // If passed the authorization, call ProjectController@show
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
            'comments.user'
        ])->where('report_id', $report_id)->firstOrFail();
        // // Retrieve associated project
        // $project = Project::where('project_id', $report->project_id)->firstOrFail();


        $provincial = auth()->user();

        // Authorization check: Ensure the report belongs to an executor under this provincial
        if ($report->user->parent_id !== $provincial->id) {
            abort(403, 'Unauthorized');
        }

        // return view('reports.monthly.show', compact('report', 'project'));
        return app(ReportController::class)->show($report_id);

    }

    // Add Comment in reports
        public function addComment(Request $request, $report_id)
    {
        $provincial = auth()->user();

        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        // Authorization check
        if ($report->user->parent_id !== $provincial->id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $commentId = $report->generateCommentId();

        ReportComment::create([
            'R_comment_id' => $commentId,
            'report_id' => $report->report_id,
            'user_id' => $provincial->id,
            'comment' => $request->comment,
        ]);

        return redirect()->back()->with('success', 'Comment added successfully.');
    }


    // Show Create Executor form
    public function CreateExecutor()
    {
        $provincial = auth()->user();
        $province = strtoupper($provincial->province);

        // Define the mapping of provinces to their centers
        $centersMap = [
            'VIJAYAWADA' => [
                'Ajitsingh Nagar', 'Nunna', 'Jaggayyapeta', 'Beed', 'Mangalagiri',
                'S.A.Peta', 'Thiruvur', 'Chakan', 'Megalaya', 'Rajavaram',
                'Avanigadda', 'Darjeeling', 'Sarvajan Sneha Charitable Trust, Vijayawada'
            ],
            'VISAKHAPATNAM' => [
                'Arilova', 'Malkapuram', 'Madugula', 'Rajam', 'Kapileswarapuram',
                'Erukonda', 'Navajara, Jharkhand', 'Jalaripeta',
                'Wilhelm Meyer’s Developmental Society, Visakhapatnam.',
                'Edavalli', 'Megalaya', 'Nalgonda', 'Shanthi Niwas, Madugula',
                'Malkapuram College', 'Malkapuram Hospital', 'Arilova School',
                'Morning Star, Eluru'
            ],
            'BANGALORE' => [
                'Prajyothi Welfare Centre', 'Gadag', 'Kurnool', 'Madurai',
                'Madhavaram', 'Belgaum', 'Kadirepalli', 'Munambam', 'Kuderu'
            ],
        ];

        // Get the centers for the current provincial's province
        $centers = $centersMap[$province] ?? [];

        return view('provincial.createExecutor', compact('centers'));
    }

    // Store Executor

    public function StoreExecutor(Request $request)
    {
        try {
            // Log the incoming request data
            Log::info('Attempting to store a new executor', ['request_data' => $request->all()]);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:255',
                'society_name' => 'required|string|max:255',
                'center' => 'nullable|string|max:255',
                'address' => 'nullable|string',
            ]);

            // Log post-validation data
            Log::info('Validation successful', ['validated_data' => $validatedData]);

            $provincial = auth()->user();
            // Log the details of the authenticated user
            Log::info('Authenticated provincial details', ['provincial' => $provincial]);

            $executor = User::create([
                'name' => $validatedData['name'],
                'username' => $validatedData['username'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'phone' => $validatedData['phone'],
                'society_name' => $validatedData['society_name'],
                'province' => $provincial->province,
                'center' => $validatedData['center'],
                'address' => $validatedData['address'],
                'role' => 'executor',
                'status' => 'active',
                'parent_id' => $provincial->id,
            ]);

            // Log the successful creation of the executor
            if ($executor) {
                Log::info('Executor created successfully', ['executor_id' => $executor->id]);
                $executor->assignRole('executor');
            } else {
                // Log failure to create executor
                Log::error('Failed to create executor');
            }

            return redirect()->route('provincial.createExecutor')->with('success', 'Executor created successfully.');
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error('Error storing executor', ['error' => $e->getMessage()]);
            return back()->withErrors('Failed to create executor: ' . $e->getMessage());
        }
    }



    // List of Executors
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
    $provincial = auth()->user();
    $province = strtoupper($provincial->province);

    // Define the mapping of provinces to their centers
    $centersMap = [
        'VIJAYAWADA' => [
            'Ajitsingh Nagar', 'Nunna', 'Jaggayyapeta', 'Beed', 'Mangalagiri',
            'S.A.Peta', 'Thiruvur', 'Chakan', 'Megalaya', 'Rajavaram',
            'Avanigadda', 'Darjeeling', 'Sarvajan Sneha Charitable Trust, Vijayawada'
        ],
        'VISAKHAPATNAM' => [
            'Arilova', 'Malkapuram', 'Madugula', 'Rajam', 'Kapileswarapuram',
            'Erukonda', 'Navajara, Jharkhand', 'Jalaripeta',
            'Wilhelm Meyer’s Developmental Society, Visakhapatnam.',
            'Edavalli', 'Megalaya', 'Nalgonda', 'Shanthi Niwas, Madugula',
            'Malkapuram College', 'Malkapuram Hospital', 'Arilova School',
            'Morning Star, Eluru'
        ],
        'BANGALORE' => [
            'Prajyothi Welfare Centre', 'Gadag', 'Kurnool', 'Madurai',
            'Madhavaram', 'Belgaum', 'Kadirepalli', 'Munambam', 'Kuderu'
        ],
    ];

    // Get the centers for the current provincial's province
    $centers = $centersMap[$province] ?? [];

    return view('provincial.editExecutor', compact('executor', 'centers'));
}


    // Update Executor
    public function updateExecutor(Request $request, $id)
    {
        $executor = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $executor->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $executor->id,
            'phone' => 'nullable|string|max:255',
            'society_name' => 'required|string|max:255',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $executor->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'society_name' => $request->society_name,
            'center' => $request->center,
            'address' => $request->address,
            'status' => $request->status,
        ]);

        return redirect()->route('provincial.executors')->with('success', 'Executor updated successfully.');
    }

    // Reset Executor Password
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
    public function addProjectComment(Request $request, $project_id)
    {
        $provincial = auth()->user();

        $project = Project::where('project_id', $project_id)->firstOrFail();
        // Check authorization if needed (provincial should have access)
        // If they have access, proceed

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $commentId = $project->generateProjectCommentId();

        \App\Models\ProjectComment::create([
            'project_comment_id' => $commentId,
            'project_id' => $project->project_id,
            'user_id' => $provincial->id,
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
    // Status
    public function revertToExecutor($project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $provincial = auth()->user();

    // Check if user is provincial and can revert
    if($provincial->role !== 'provincial' || !in_array($project->status, ['submitted_to_provincial','reverted_by_coordinator'])) {
        abort(403, 'Unauthorized action.');
    }

    $project->status = 'reverted_by_provincial';
    $project->save();

    return redirect()->back()->with('success', 'Project reverted to Executor.');
}

public function forwardToCoordinator($project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $provincial = auth()->user();

    if($provincial->role !== 'provincial' || !in_array($project->status, ['submitted_to_provincial','reverted_by_coordinator'])) {
        abort(403, 'Unauthorized action.');
    }

    $project->status = 'forwarded_to_coordinator';
    $project->save();

    return redirect()->back()->with('success', 'Project forwarded to Coordinator.');
}


}
