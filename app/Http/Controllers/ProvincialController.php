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

        // Debug: Log the request parameters
        \Log::info('Provincial Dashboard Request', [
            'center' => $request->get('center'),
            'role' => $request->get('role'),
            'project_type' => $request->get('project_type')
        ]);

        // First, get approved projects for executors under this provincial with comprehensive filtering
        $projectsQuery = Project::whereHas('user', function ($query) use ($provincial) {
            $query->where('parent_id', $provincial->id);
        })->where('status', 'approved_by_coordinator');

        // Apply comprehensive filters
        if ($request->filled('center')) {
            $projectsQuery->whereHas('user', function ($query) use ($request) {
                $query->where('center', $request->center);
            });
        }
        if ($request->filled('role')) {
            $projectsQuery->whereHas('user', function ($query) use ($request) {
                $query->where('role', $request->role);
            });
        }
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        $projects = $projectsQuery->with(['user', 'reports.accountDetails'])->get();

        // Calculate budget summaries from projects and their reports
        $budgetSummaries = $this->calculateBudgetSummariesFromProjects($projects, $request);

        // Get comprehensive filter options for this provincial's jurisdiction
        $centers = User::where('parent_id', $provincial->id)
                      ->whereIn('role', ['executor', 'applicant'])
                      ->whereNotNull('center')
                      ->where('center', '!=', '')
                      ->distinct()
                      ->pluck('center')
                      ->filter()
                      ->values();

        $roles = ['executor', 'applicant'];

        $projectTypes = Project::whereHas('user', function ($query) use ($provincial) {
            $query->where('parent_id', $provincial->id);
        })->where('status', 'approved_by_coordinator')
        ->distinct()
        ->pluck('project_type');

        // Debug logging
        \Log::info('Provincial Dashboard Filter Options', [
            'selected_center' => $request->get('center'),
            'selected_role' => $request->get('role'),
            'selected_project_type' => $request->get('project_type'),
            'available_centers_count' => $centers->count(),
            'total_projects' => $projects->count(),
            'projects_by_center' => $projects->groupBy('user.center')->map->count()->toArray()
        ]);

        return view('provincial.index', compact('budgetSummaries', 'centers', 'roles', 'projectTypes'));
    }

    private function calculateBudgetSummariesFromProjects($projects, $request)
    {
        $budgetSummaries = [
            'by_project_type' => [],
            'by_center' => [],
            'total' => [
                'total_budget' => 0,
                'total_expenses' => 0,
                'total_remaining' => 0
            ]
        ];

        foreach ($projects as $project) {
            // Get project's sanctioned amount as base budget
            $projectBudget = $project->amount_sanctioned ?? 0;
            $projectForwarded = $project->amount_forwarded ?? 0;

            // Calculate expenses from reports if they exist
            $totalExpenses = 0;
            if ($project->reports->count() > 0) {
                foreach ($project->reports as $report) {
                    $totalExpenses += $report->accountDetails->sum('total_expenses');
                }
            }

            // Calculate remaining budget
            $remainingBudget = $projectBudget - $totalExpenses;

            // Update project type summary
            if (!isset($budgetSummaries['by_project_type'][$project->project_type])) {
                $budgetSummaries['by_project_type'][$project->project_type] = [
                    'total_budget' => 0,
                    'total_expenses' => 0,
                    'total_remaining' => 0
                ];
            }
            $budgetSummaries['by_project_type'][$project->project_type]['total_budget'] += $projectBudget;
            $budgetSummaries['by_project_type'][$project->project_type]['total_expenses'] += $totalExpenses;
            $budgetSummaries['by_project_type'][$project->project_type]['total_remaining'] += $remainingBudget;

            // Update center summary
            $center = $project->user->center ?? 'Unknown Center';
            if (!isset($budgetSummaries['by_center'][$center])) {
                $budgetSummaries['by_center'][$center] = [
                    'total_budget' => 0,
                    'total_expenses' => 0,
                    'total_remaining' => 0
                ];
            }
            $budgetSummaries['by_center'][$center]['total_budget'] += $projectBudget;
            $budgetSummaries['by_center'][$center]['total_expenses'] += $totalExpenses;
            $budgetSummaries['by_center'][$center]['total_remaining'] += $remainingBudget;

            // Update total summary
            $budgetSummaries['total']['total_budget'] += $projectBudget;
            $budgetSummaries['total']['total_expenses'] += $totalExpenses;
            $budgetSummaries['total']['total_remaining'] += $remainingBudget;
        }

        return $budgetSummaries;
    }

    public function ReportList(Request $request)
    {
        $provincial = auth()->user();

        // First, get approved projects for executors under this provincial
        $projectsQuery = Project::whereHas('user', function ($query) use ($provincial) {
            $query->where('parent_id', $provincial->id);
        })->where('status', 'approved_by_coordinator');

        // Apply filtering if provided in the request
        if ($request->filled('place')) {
            $projectsQuery->whereHas('user', function ($query) use ($request) {
                $query->where('center', $request->place);
            });
        }
        if ($request->filled('user_id')) {
            $projectsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        $projects = $projectsQuery->with(['user', 'reports.accountDetails'])->get();

        // Calculate budget summaries from projects and their reports
        $budgetSummaries = $this->calculateBudgetSummariesFromProjects($projects, $request);

        // Fetch unique centers from users of approved projects
        $places = User::whereHas('projects', function ($query) use ($provincial) {
            $query->whereHas('user', function ($subQuery) use ($provincial) {
                $subQuery->where('parent_id', $provincial->id);
            })->where('status', 'approved_by_coordinator');
        })->distinct()->pluck('center');

        $users = User::where('parent_id', $provincial->id)->get();

        // Fetch distinct project types for filters
        $projectTypes = Project::whereHas('user', function ($query) use ($provincial) {
            $query->where('parent_id', $provincial->id);
        })->where('status', 'approved_by_coordinator')->distinct()->pluck('project_type');

        return view('provincial.ReportList', compact('projects', 'budgetSummaries', 'places', 'users', 'projectTypes'));
    }

    private function calculateBudgetSummaries($reports, $request)
    {
        $budgetSummaries = [
            'by_project_type' => [],
            'by_center' => [],
            'total' => [
                'total_budget' => 0,
                'total_expenses' => 0,
                'total_remaining' => 0
            ]
        ];

        foreach ($reports as $report) {
            // Calculate totals for this report
            $reportTotal = $report->accountDetails->sum('total_amount');
            $reportExpenses = $report->accountDetails->sum('total_expenses');
            $reportRemaining = $report->accountDetails->sum('balance_amount');

            // Update project type summary
            if (!isset($budgetSummaries['by_project_type'][$report->project_type])) {
                $budgetSummaries['by_project_type'][$report->project_type] = [
                    'total_budget' => 0,
                    'total_expenses' => 0,
                    'total_remaining' => 0
                ];
            }
            $budgetSummaries['by_project_type'][$report->project_type]['total_budget'] += $reportTotal;
            $budgetSummaries['by_project_type'][$report->project_type]['total_expenses'] += $reportExpenses;
            $budgetSummaries['by_project_type'][$report->project_type]['total_remaining'] += $reportRemaining;

            // Update center summary
            $center = $report->user->center ?? 'Unknown Center';
            if (!isset($budgetSummaries['by_center'][$center])) {
                $budgetSummaries['by_center'][$center] = [
                    'total_budget' => 0,
                    'total_expenses' => 0,
                    'total_remaining' => 0
                ];
            }
            $budgetSummaries['by_center'][$center]['total_budget'] += $reportTotal;
            $budgetSummaries['by_center'][$center]['total_expenses'] += $reportExpenses;
            $budgetSummaries['by_center'][$center]['total_remaining'] += $reportRemaining;

            // Update total summary
            $budgetSummaries['total']['total_budget'] += $reportTotal;
            $budgetSummaries['total']['total_expenses'] += $reportExpenses;
            $budgetSummaries['total']['total_remaining'] += $reportRemaining;
        }

        return $budgetSummaries;
    }

    public function ProjectList(Request $request)
    {
        $provincial = auth()->user();

        // Fetch all projects where the project's user is a child of the provincial
        // Only show projects with status 'submitted_to_provincial' and 'reverted_by_coordinator'
        $projectsQuery = \App\Models\OldProjects\Project::whereHas('user', function($query) use ($provincial) {
            $query->where('parent_id', $provincial->id);
        })
        ->whereIn('status', ['submitted_to_provincial', 'reverted_by_coordinator']);

        // Apply optional filters if you want:
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        if ($request->filled('user_id')) {
            $projectsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('status')) {
            $projectsQuery->where('status', $request->status);
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
                'Avanigadda', 'Darjeeling', 'Sarvajan Sneha Charitable Trust, Vijayawada', 'St. Anns Hospital Vijayawada'
            ],
            'VISAKHAPATNAM' => [
                'Arilova', 'Malkapuram', 'Madugula', 'Rajam', 'Kapileswarapuram',
                'Erukonda', 'Navajara, Jharkhand', 'Jalaripeta',
                'Wilhelm Meyer\'s Developmental Society, Visakhapatnam.',
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
                'role' => 'required|in:executor,applicant',
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
                'role' => $validatedData['role'],
                'status' => 'active',
                'parent_id' => $provincial->id,
            ]);

            // Log the successful creation of the executor
            if ($executor) {
                Log::info('User created successfully', ['user_id' => $executor->id, 'role' => $validatedData['role']]);
                $executor->assignRole($validatedData['role']);
            } else {
                // Log failure to create user
                Log::error('Failed to create user');
            }

            $roleName = ucfirst($validatedData['role']);
            return redirect()->route('provincial.createExecutor')->with('success', $roleName . ' created successfully.');
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error('Error storing user', ['error' => $e->getMessage()]);
            return back()->withErrors('Failed to create user: ' . $e->getMessage());
        }
    }

    // List of Users (Executors and Applicants)
    public function listExecutors()
    {
        $provincial = auth()->user();
        $executors = User::where('parent_id', $provincial->id)
                        ->whereIn('role', ['executor', 'applicant'])
                        ->get();

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
                'Wilhelm Meyer\'s Developmental Society, Visakhapatnam.',
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

    // Activate User
    public function activateUser($id)
    {
        $user = User::findOrFail($id);
        $provincial = auth()->user();

        // Check if the user belongs to this provincial
        if ($user->parent_id !== $provincial->id) {
            abort(403, 'Unauthorized action.');
        }

        $user->update(['status' => 'active']);

        return redirect()->route('provincial.executors')->with('success', ucfirst($user->role) . ' activated successfully.');
    }

    // Deactivate User
    public function deactivateUser($id)
    {
        $user = User::findOrFail($id);
        $provincial = auth()->user();

        // Check if the user belongs to this provincial
        if ($user->parent_id !== $provincial->id) {
            abort(403, 'Unauthorized action.');
        }

        $user->update(['status' => 'inactive']);

        return redirect()->route('provincial.executors')->with('success', ucfirst($user->role) . ' deactivated successfully.');
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

        return redirect()->route('provincial.projects.list')->with('success', 'Project reverted to Executor.');
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

        return redirect()->route('provincial.projects.list')->with('success', 'Project forwarded to Coordinator.');
    }

    // Approved Projects for Provincials
    public function approvedProjects(Request $request)
    {
        $provincial = auth()->user();

        // Fetch approved projects for all executors under this provincial
        // Use a subquery to get unique project IDs first, then fetch the full records
        $projectIds = \App\Models\OldProjects\Project::whereHas('user', function($query) use ($provincial) {
            $query->where('parent_id', $provincial->id);
        })
        ->where('status', 'approved_by_coordinator')
        ->distinct()
        ->pluck('project_id');

        $projectsQuery = \App\Models\OldProjects\Project::whereIn('project_id', $projectIds)
            ->with('user');

        // Apply optional filters
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        if ($request->filled('user_id')) {
            $projectsQuery->where('user_id', $request->user_id);
        }

        $projects = $projectsQuery->orderBy('project_id')->orderBy('user_id')->get();

        // Fetch distinct executors under this provincial for filtering
        $users = \App\Models\User::where('parent_id', $provincial->id)->get();

        // Distinct project types
        $projectTypes = \App\Models\OldProjects\Project::distinct()->pluck('project_type');

        return view('provincial.approvedProjects', compact('projects', 'users', 'projectTypes'));
    }
}
