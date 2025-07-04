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

        // Debug: Log the request parameters
        \Log::info('Coordinator Dashboard Request', [
            'province' => $request->get('province'),
            'center' => $request->get('center'),
            'role' => $request->get('role'),
            'parent_id' => $request->get('parent_id')
        ]);

        // First, get approved projects with comprehensive filtering
        $projectsQuery = Project::where('status', 'approved_by_coordinator')->with('user');

        // Apply comprehensive filters based on user attributes
        if ($request->filled('province')) {
            $projectsQuery->whereHas('user', function($query) use ($request) {
                $query->where('province', $request->province);
            });
        }
        if ($request->filled('center')) {
            $projectsQuery->whereHas('user', function($query) use ($request) {
                $query->where('center', $request->center);
            });
        }
        if ($request->filled('role')) {
            $projectsQuery->whereHas('user', function($query) use ($request) {
                $query->where('role', $request->role);
            });
        }
        if ($request->filled('parent_id')) {
            $projectsQuery->whereHas('user', function($query) use ($request) {
                $query->where('parent_id', $request->parent_id);
            });
        }

        $projects = $projectsQuery->with(['user.parent', 'reports.accountDetails', 'budgets'])->get();

        // Calculate budget summaries from projects and their reports
        $budgetSummaries = $this->calculateBudgetSummariesFromProjects($projects, $request);

        // Get comprehensive filter options
        $provinces = User::whereIn('role', ['provincial', 'executor', 'applicant'])
                        ->distinct()
                        ->pluck('province')
                        ->filter()
                        ->values();

        $centers = User::whereIn('role', ['provincial', 'executor', 'applicant'])
                      ->whereNotNull('center')
                      ->where('center', '!=', '')
                      ->distinct()
                      ->pluck('center')
                      ->filter()
                      ->values();

        $roles = ['provincial', 'executor', 'applicant'];

        // Get parent options (provincials only)
        $parents = User::where('role', 'provincial')
                      ->select('id', 'name', 'province')
                      ->get();

        $projectTypes = Project::where('status', 'approved_by_coordinator')->distinct()->pluck('project_type');

        // Debug: Log the filter options
        \Log::info('Coordinator Dashboard Filter Options', [
            'selected_province' => $request->get('province'),
            'selected_center' => $request->get('center'),
            'selected_role' => $request->get('role'),
            'selected_parent_id' => $request->get('parent_id'),
            'available_provinces_count' => $provinces->count(),
            'available_centers_count' => $centers->count(),
            'available_parents_count' => $parents->count(),
            'total_projects' => $projects->count(),
            'projects_by_province' => $projects->groupBy('user.province')->map->count()->toArray(),
            'projects_with_amount_sanctioned' => $projects->where('amount_sanctioned', '>', 0)->count(),
            'projects_with_overall_budget' => $projects->where('overall_project_budget', '>', 0)->count(),
            'projects_with_budgets' => $projects->filter(function($p) { return $p->budgets && $p->budgets->count() > 0; })->count(),
            'projects_with_reports' => $projects->filter(function($p) { return $p->reports && $p->reports->count() > 0; })->count()
        ]);

        return view('coordinator.index', compact('budgetSummaries', 'provinces', 'centers', 'roles', 'parents', 'projectTypes'));
    }

    private function calculateBudgetSummaries($reports, $request, $onlyApproved = true)
    {
        $budgetSummaries = [
            'by_project_type' => [],
            'by_province' => [],
            'total' => [
                'total_budget' => 0,
                'total_expenses' => 0,
                'total_remaining' => 0
            ]
        ];

        foreach ($reports as $report) {
            // Skip non-approved reports if onlyApproved is true
            if ($onlyApproved && $report->status !== 'approved_by_coordinator') continue;

            $reportTotal = $report->accountDetails->sum('total_amount');
            $reportExpenses = $report->accountDetails->sum('total_expenses');
            $reportRemaining = $report->accountDetails->sum('balance_amount');
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
            $province = $report->user->province;
            if (!isset($budgetSummaries['by_province'][$province])) {
                $budgetSummaries['by_province'][$province] = [
                    'total_budget' => 0,
                    'total_expenses' => 0,
                    'total_remaining' => 0
                ];
            }
            $budgetSummaries['by_province'][$province]['total_budget'] += $reportTotal;
            $budgetSummaries['by_province'][$province]['total_expenses'] += $reportExpenses;
            $budgetSummaries['by_province'][$province]['total_remaining'] += $reportRemaining;
            $budgetSummaries['total']['total_budget'] += $reportTotal;
            $budgetSummaries['total']['total_expenses'] += $reportExpenses;
            $budgetSummaries['total']['total_remaining'] += $reportRemaining;
        }
        return $budgetSummaries;
    }

    private function calculateBudgetSummariesFromProjects($projects, $request)
    {
        $budgetSummaries = [
            'by_project_type' => [],
            'by_province' => [],
            'total' => [
                'total_budget' => 0,
                'total_expenses' => 0,
                'total_remaining' => 0
            ]
        ];
        foreach ($projects as $project) {
            $projectBudget = 0;
            if ($project->overall_project_budget && $project->overall_project_budget > 0) {
                $projectBudget = $project->overall_project_budget;
            } elseif ($project->amount_sanctioned && $project->amount_sanctioned > 0) {
                $projectBudget = $project->amount_sanctioned;
            } elseif ($project->budgets && $project->budgets->count() > 0) {
                $projectBudget = $project->budgets->sum('this_phase');
            }
            if ($projectBudget == 0 && $project->reports && $project->reports->count() > 0) {
                foreach ($project->reports as $report) {
                    if ($report->status === 'approved_by_coordinator' && $report->accountDetails && $report->accountDetails->count() > 0) {
                        $projectBudget = $report->accountDetails->sum('total_amount');
                        break;
                    }
                }
            }
            $totalExpenses = 0;
            if ($project->reports && $project->reports->count() > 0) {
                foreach ($project->reports as $report) {
                    if ($report->status === 'approved_by_coordinator' && $report->accountDetails && $report->accountDetails->count() > 0) {
                        $totalExpenses += $report->accountDetails->sum('total_expenses');
                    }
                }
            }
            $remainingBudget = $projectBudget - $totalExpenses;
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
            $province = $project->user->province;
            if (!isset($budgetSummaries['by_province'][$province])) {
                $budgetSummaries['by_province'][$province] = [
                    'total_budget' => 0,
                    'total_expenses' => 0,
                    'total_remaining' => 0
                ];
            }
            $budgetSummaries['by_province'][$province]['total_budget'] += $projectBudget;
            $budgetSummaries['by_province'][$province]['total_expenses'] += $totalExpenses;
            $budgetSummaries['by_province'][$province]['total_remaining'] += $remainingBudget;
            $budgetSummaries['total']['total_budget'] += $projectBudget;
            $budgetSummaries['total']['total_expenses'] += $totalExpenses;
            $budgetSummaries['total']['total_remaining'] += $remainingBudget;
        }
        return $budgetSummaries;
    }

    public function ReportList(Request $request)
    {
        $coordinator = Auth::user();

        // Base query for projects - coordinators can see all project types
        $projectsQuery = Project::with('user');

        // Apply filters
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

        // Fetch the allowed projects
        $projects = $projectsQuery->get();

        // Fetch reports for these filtered projects
        $reports = DPReport::with(['user', 'accountDetails'])
            ->whereIn('project_id', $projects->pluck('project_id'))
            ->get();

        // Fetch provinces and users for filtering options
        $provinces = User::distinct()->pluck('province');
        $users = User::all();

        // Fetch distinct project types
        $projectTypes = Project::distinct()->pluck('project_type');

        // Return the ReportList view with the filtered reports, project types, etc.
        return view('coordinator.ReportList', compact('reports', 'coordinator', 'provinces', 'users', 'projectTypes'));
    }

    public function ProjectList(Request $request)
    {
        $coordinator = Auth::user();

        // Base query for projects - coordinators can see all project types
        // Only show projects with status 'forwarded_to_coordinator'
        $projectsQuery = Project::where('status', 'forwarded_to_coordinator')
            ->with('user');

        // Optional province filter
        if ($request->filled('province')) {
            $projectsQuery->whereHas('user', function($q) use ($request) {
                $q->where('province', $request->province);
            });
        }

        // Optional project_type filter
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        // Optional executor (user_id) filter
        if ($request->filled('user_id')) {
            $projectsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('status')) {
            $projectsQuery->where('status', $request->status);
        }

        $projects = $projectsQuery->get();

        // Fetch distinct project types
        $projectTypes = Project::distinct()->pluck('project_type');

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

    public function showProject($project_id)
    {
        // Retrieve the project
        $project = Project::where('project_id', $project_id)
            ->with('user')
            ->firstOrFail();

        // Coordinator can view all projects, so no additional authorization needed here
        // If you need to restrict access further, you can add authorization logic

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
                'Morning Star, Eluru', 'Butchirajaupalem', 'Malakapuram (Hospital)',
                'Shalom', 'Berhampur, Odisha', 'Beemunipatnam', 'Mandapeta',
                'Malkapuram School', 'Bheemunipatnam', 'Arunodaya', 'Pathapatnam',
                'Paderu', 'Meyers Villa', 'Nalkonda'
            ],
            'BANGALORE' => [
                'Prajyothi Welfare Centre', 'Gadag', 'Kurnool', 'Madurai',
                'Madhavaram', 'Belgaum', 'Kadirepalli', 'Munambam', 'Kuderu',
                'Tuticorin', 'Palakkad', 'Thejas', 'Sannenahalli', 'Solavidhyapuram',
                'Kozhenchery', 'Nadavayal', 'Kodaikanal', 'PWC Bangalore', 'Taragarh', 'Chennai'
            ],
        ];

        return view('coordinator.createProvincial', compact('centersMap'));
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
            'role' => 'required|in:coordinator,provincial,executor,applicant',
            'province' => 'required|in:Bangalore,Vijayawada,Visakhapatnam,Generalate',
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
            'role' => $request->role,
            'province' => $request->province,
            'status' => $request->status,
        ]);

        $roleName = ucfirst($request->role);
        return redirect()->route('coordinator.provincials')->with('success', $roleName . ' created successfully.');
    }

    // List of Users (Provincials, Executors, Applicants)
    public function listProvincials(Request $request)
    {
        $coordinator = auth()->user();

        // Base query for all users - coordinators can see all users in the system
        $usersQuery = User::whereIn('role', ['coordinator', 'provincial', 'executor', 'applicant']);

        // Apply filters based on the three main columns: province, center, role
        if ($request->filled('province')) {
            $usersQuery->where('province', $request->province);
        }
        if ($request->filled('center')) {
            $usersQuery->where('center', $request->center);
        }
        if ($request->filled('role')) {
            $usersQuery->where('role', $request->role);
        }
        if ($request->filled('parent_id')) {
            $usersQuery->where('parent_id', $request->parent_id);
        }

        $users = $usersQuery->with('parent')->get();

        // Get filter options based on the three main columns - for all users
        $provinces = User::whereIn('role', ['coordinator', 'provincial', 'executor', 'applicant'])
                        ->distinct()
                        ->pluck('province')
                        ->filter() // Remove empty values
                        ->values();

        $centers = User::whereIn('role', ['coordinator', 'provincial', 'executor', 'applicant'])
                      ->whereNotNull('center')
                      ->where('center', '!=', '')
                      ->distinct()
                      ->pluck('center')
                      ->filter() // Remove empty values
                      ->values();

        $roles = ['coordinator', 'provincial', 'executor', 'applicant'];

        // Get parent options (provincials only)
        $parents = User::where('role', 'provincial')
                      ->select('id', 'name', 'province')
                      ->get();

        // Debug logging
        \Log::info('Users Management Filtering', [
            'request_filters' => $request->only(['province', 'center', 'role']),
            'available_provinces' => $provinces->toArray(),
            'available_centers' => $centers->toArray(),
            'total_users' => $users->count(),
            'users_by_role' => $users->groupBy('role')->map->count()->toArray(),
            'users_by_province' => $users->groupBy('province')->map->count()->toArray()
        ]);

        return view('coordinator.provincials', compact('users', 'provinces', 'centers', 'roles', 'parents'));
    }

    public function editProvincial($id)
    {
        $provincial = User::findOrFail($id);

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
                'Morning Star, Eluru', 'Butchirajaupalem', 'Malakapuram (Hospital)',
                'Shalom', 'Berhampur, Odisha', 'Beemunipatnam', 'Mandapeta',
                'Malkapuram School', 'Bheemunipatnam', 'Arunodaya', 'Pathapatnam',
                'Paderu', 'Meyers Villa', 'Nalkonda'
            ],
            'BANGALORE' => [
                'Prajyothi Welfare Centre', 'Gadag', 'Kurnool', 'Madurai',
                'Madhavaram', 'Belgaum', 'Kadirepalli', 'Munambam', 'Kuderu',
                'Tuticorin', 'Palakkad', 'Thejas', 'Sannenahalli', 'Solavidhyapuram',
                'Kozhenchery', 'Nadavayal', 'Kodaikanal', 'PWC Bangalore', 'Taragarh', 'Chennai'
            ],
        ];

        return view('coordinator.editProvincial', compact('provincial', 'centersMap'));
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
            'role' => 'required|in:coordinator,provincial,executor,applicant',
            'province' => 'required|in:Bangalore,Vijayawada,Visakhapatnam,Generalate',
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
            'role' => $request->role,
            'province' => $request->province,
            'status' => $request->status,
        ]);

        $roleName = ucfirst($request->role);
        return redirect()->route('coordinator.provincials')->with('success', $roleName . ' updated successfully.');
    }

    public function resetUserPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::findOrFail($id);
        $coordinator = auth()->user();

        // Check if the user belongs to this coordinator or if coordinator has permission
        // Coordinators can reset passwords for all users in the system
        // If you want to restrict this, you can add additional checks here
        // For example: if ($user->parent_id !== $coordinator->id) { abort(403, 'Unauthorized action.'); }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $roleName = ucfirst($user->role);
        return redirect()->route('coordinator.provincials')->with('success', $roleName . ' password reset successfully.');
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
    $project = Project::where('project_id', $project_id)->with('budgets')->firstOrFail();
    $coordinator = auth()->user();

    if($coordinator->role !== 'coordinator' || $project->status !== 'forwarded_to_coordinator') {
        abort(403, 'Unauthorized action.');
    }

    // Calculate the total amount sanctioned from budget details
    $totalAmountSanctioned = 0;

    // First, try to get from overall_project_budget (this should be the primary source)
    if ($project->overall_project_budget && $project->overall_project_budget > 0) {
        $totalAmountSanctioned = $project->overall_project_budget;
    }
    // If not available, try to get from existing amount_sanctioned if it's already set
    elseif ($project->amount_sanctioned && $project->amount_sanctioned > 0) {
        $totalAmountSanctioned = $project->amount_sanctioned;
    }
    // If still not available, calculate from budget details
    elseif ($project->budgets && $project->budgets->count() > 0) {
        $totalAmountSanctioned = $project->budgets->sum('this_phase');
    }

    // Update the project with approved status and amount_sanctioned
    $project->status = 'approved_by_coordinator';
    $project->amount_sanctioned = $totalAmountSanctioned;
    $project->save();

    // Log the approval action
    \Log::info('Project Approved by Coordinator', [
        'project_id' => $project->project_id,
        'project_title' => $project->project_title,
        'coordinator_id' => $coordinator->id,
        'coordinator_name' => $coordinator->name,
        'amount_sanctioned' => $totalAmountSanctioned,
        'budgets_count' => $project->budgets ? $project->budgets->count() : 0,
        'overall_project_budget' => $project->overall_project_budget
    ]);

    return redirect()->back()->with('success', 'Project approved successfully with sanctioned amount: Rs. ' . number_format($totalAmountSanctioned, 2));
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

public function projectBudgets(Request $request)
{
    $coordinator = Auth::user();

    // First, get approved projects (coordinators can see all approved projects)
    $projectsQuery = Project::where('status', 'approved_by_coordinator')->with('user');

    // Apply filters
    if ($request->filled('province')) {
        $projectsQuery->whereHas('user', function($query) use ($request) {
            $query->where('province', $request->province);
        });
    }
    if ($request->filled('place')) {
        $projectsQuery->whereHas('user', function($query) use ($request) {
            $query->where('center', $request->place);
        });
    }
    if ($request->filled('user_id')) {
        $projectsQuery->where('user_id', $request->user_id);
    }
    if ($request->filled('project_type')) {
        $projectsQuery->where('project_type', $request->project_type);
    }

    $projects = $projectsQuery->with(['user', 'reports.accountDetails', 'budgets'])->get();

    // Calculate budget summaries from projects and their reports
    $budgetSummaries = $this->calculateBudgetSummariesFromProjects($projects, $request);

    // Get filter options
    $provinces = User::distinct()->pluck('province');

    // Get centers based on selected province
    $placesQuery = User::whereNotNull('center')->where('center', '!=', '');
    if ($request->filled('province')) {
        $placesQuery->where('province', $request->province);
    }
    $places = $placesQuery->distinct()->pluck('center');

    // Get executors based on selected province (exclude applicants)
    $usersQuery = User::where('role', 'executor');
    if ($request->filled('province')) {
        $usersQuery->where('province', $request->province);
    }
    $users = $usersQuery->get();

    $projectTypes = Project::where('status', 'approved_by_coordinator')->distinct()->pluck('project_type');

    return view('coordinator.index', compact('budgetSummaries', 'provinces', 'places', 'users', 'projectTypes'));
}

public function budgetOverview()
{
    $coordinator = auth()->user();

    // Get provinces from users where the coordinator is the parent
    $provinces = User::where('parent_id', $coordinator->id)
        ->where('role', 'provincial')
        ->pluck('province')
        ->unique();

    // Get all projects accessible to the coordinator
    $projects = Project::whereHas('user', function($query) use ($coordinator, $provinces) {
        $query->whereIn('province', $provinces);
    })
    ->whereNotIn('project_type', [
        'NEXT PHASE - DEVELOPMENT PROPOSAL'
        // Removed individual project type exclusions - coordinators can see all project types
    ])
    ->with(['user', 'reports.accountDetails'])
    ->get();

    // Group projects by type and province
    $budgetData = [];
    foreach ($projects as $project) {
        $type = $project->project_type;
        $province = $project->user->province;

        if (!isset($budgetData[$type])) {
            $budgetData[$type] = [];
        }
        if (!isset($budgetData[$type][$province])) {
            $budgetData[$type][$province] = [
                'total_budget' => 0,
                'total_expenses' => 0,
                'total_remaining' => 0,
                'projects' => []
            ];
        }

        $projectBudget = [
            'project_id' => $project->project_id,
            'title' => $project->project_title,
            'executor' => $project->user->name,
            'total_budget' => 0,
            'total_expenses' => 0,
            'total_remaining' => 0,
            'budget_details' => []
        ];

        // Calculate budget details for each project using reports
        foreach ($project->reports as $report) {
            $totalBudget = $report->accountDetails->sum('total_amount');
            $totalExpenses = $report->accountDetails->sum('total_expenses');
            $remaining = $report->accountDetails->sum('balance_amount');

            $projectBudget['total_budget'] += $totalBudget;
            $projectBudget['total_expenses'] += $totalExpenses;
            $projectBudget['total_remaining'] += $remaining;

            // Group budget details by particular
            foreach ($report->accountDetails as $detail) {
                $particular = $detail->particulars;
                if (!isset($projectBudget['budget_details'][$particular])) {
                    $projectBudget['budget_details'][$particular] = [
                        'budget' => 0,
                        'expenses' => 0,
                        'remaining' => 0
                    ];
                }
                $projectBudget['budget_details'][$particular]['budget'] += $detail->total_amount;
                $projectBudget['budget_details'][$particular]['expenses'] += $detail->total_expenses;
                $projectBudget['budget_details'][$particular]['remaining'] += $detail->balance_amount;
            }
        }

        // Convert budget_details from associative array to indexed array
        $projectBudget['budget_details'] = array_map(function($particular, $details) {
            return array_merge(['particular' => $particular], $details);
        }, array_keys($projectBudget['budget_details']), array_values($projectBudget['budget_details']));

        // Update province totals
        $budgetData[$type][$province]['total_budget'] += $projectBudget['total_budget'];
        $budgetData[$type][$province]['total_expenses'] += $projectBudget['total_expenses'];
        $budgetData[$type][$province]['total_remaining'] += $projectBudget['total_remaining'];
        $budgetData[$type][$province]['projects'][] = $projectBudget;
    }

    // Calculate overall totals
    $overallTotals = [
        'total_budget' => 0,
        'total_expenses' => 0,
        'total_remaining' => 0
    ];

    foreach ($budgetData as $type => $provinces) {
        foreach ($provinces as $province => $data) {
            $overallTotals['total_budget'] += $data['total_budget'];
            $overallTotals['total_expenses'] += $data['total_expenses'];
            $overallTotals['total_remaining'] += $data['total_remaining'];
        }
    }

    return view('coordinator.budget-overview', [
        'budgetData' => $budgetData,
        'overallTotals' => $overallTotals,
        'provinces' => $provinces,
        'coordinator' => $coordinator
    ]);
}

    // Activate User
    public function activateUser($id)
    {
        $user = User::findOrFail($id);
        $coordinator = auth()->user();

        // Coordinators can activate any user in the system
        // If you want to restrict this, you can add additional checks here
        // For example: if ($user->parent_id !== $coordinator->id) { abort(403, 'Unauthorized action.'); }

        $user->update(['status' => 'active']);

        return redirect()->route('coordinator.provincials')->with('success', ucfirst($user->role) . ' activated successfully.');
    }

    // Deactivate User
    public function deactivateUser($id)
    {
        $user = User::findOrFail($id);
        $coordinator = auth()->user();

        // Coordinators can deactivate any user in the system
        // If you want to restrict this, you can add additional checks here
        // For example: if ($user->parent_id !== $coordinator->id) { abort(403, 'Unauthorized action.'); }

        $user->update(['status' => 'inactive']);

        return redirect()->route('coordinator.provincials')->with('success', ucfirst($user->role) . ' deactivated successfully.');
    }

    // Approved Projects for Coordinators
    public function approvedProjects(Request $request)
    {
        $coordinator = Auth::user();

        // Base query for approved projects - coordinators can see all project types
        // Use a subquery to get unique project IDs first, then fetch the full records
        $projectIds = Project::where('status', 'approved_by_coordinator')
            ->distinct()
            ->pluck('project_id');

        $projectsQuery = Project::whereIn('project_id', $projectIds)
            ->with('user');

        // Optional province filter
        if ($request->filled('province')) {
            $projectsQuery->whereHas('user', function($q) use ($request) {
                $q->where('province', $request->province);
            });
        }

        // Optional project_type filter
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        // Optional executor (user_id) filter
        if ($request->filled('user_id')) {
            $projectsQuery->where('user_id', $request->user_id);
        }

        $projects = $projectsQuery->orderBy('project_id')->orderBy('user_id')->get();

        // Fetch distinct project types
        $projectTypes = Project::distinct()->pluck('project_type');

        // Fetch distinct provinces from users
        $provinces = User::distinct()->pluck('province');

        // Build the users query to show only executors
        $usersQuery = User::where('role', 'executor');

        // If a province is selected, filter executors by that province
        if ($request->filled('province')) {
            $usersQuery->where('province', $request->province);
        }

        $users = $usersQuery->get();

        return view('coordinator.approvedProjects', compact('projects', 'coordinator', 'projectTypes', 'users', 'provinces'));
    }

    // Get executors by province for AJAX request
    public function getExecutorsByProvince(Request $request)
    {
        $province = $request->get('province');

        if (!$province) {
            return response()->json([]);
        }

        $executors = User::where('role', 'executor')
                        ->where('province', $province)
                        ->select('id', 'name', 'center')
                        ->get();

        return response()->json($executors);
    }

    public function approveReport(Request $request, $report_id)
    {
        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        // Check if report is in forwarded_to_coordinator status
        if ($report->status !== 'forwarded_to_coordinator') {
            return redirect()->back()->with('error', 'Report can only be approved when in forwarded to coordinator status.');
        }

        // Update report status to approved_by_coordinator
        $report->update([
            'status' => 'approved_by_coordinator'
        ]);

        return redirect()->route('coordinator.report.list')->with('success', 'Report approved successfully.');
    }

    public function revertReport(Request $request, $report_id)
    {
        $request->validate([
            'revert_reason' => 'required|string|max:1000'
        ]);

        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        // Check if report is in forwarded_to_coordinator status
        if ($report->status !== 'forwarded_to_coordinator') {
            return redirect()->back()->with('error', 'Report can only be reverted when in forwarded to coordinator status.');
        }

        // Update report status to reverted_by_coordinator
        $report->update([
            'status' => 'reverted_by_coordinator',
            'revert_reason' => $request->revert_reason
        ]);

        return redirect()->route('coordinator.report.list')->with('success', 'Report reverted to provincial successfully.');
    }

    public function pendingReports(Request $request)
    {
        $coordinator = Auth::user();

        // Fetch pending reports (forwarded_to_coordinator)
        $reportsQuery = DPReport::where('status', 'forwarded_to_coordinator')
                               ->with(['user', 'accountDetails']);

        // Apply filters
        if ($request->filled('province')) {
            $reportsQuery->whereHas('user', function($query) use ($request) {
                $query->where('province', $request->province);
            });
        }
        if ($request->filled('user_id')) {
            $reportsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        $reports = $reportsQuery->get();

        // Calculate budget summaries
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request, false);

        // Fetch provinces and users for filtering options
        $provinces = User::distinct()->pluck('province');
        $users = User::all();

        // Fetch distinct project types
        $projectTypes = DPReport::where('status', 'forwarded_to_coordinator')->distinct()->pluck('project_type');

        return view('coordinator.pendingReports', compact('reports', 'coordinator', 'provinces', 'users', 'projectTypes'));
    }

    public function approvedReports(Request $request)
    {
        $coordinator = Auth::user();

        // Fetch approved reports
        $reportsQuery = DPReport::where('status', 'approved_by_coordinator')
                               ->with(['user', 'accountDetails']);

        // Apply filters
        if ($request->filled('province')) {
            $reportsQuery->whereHas('user', function($query) use ($request) {
                $query->where('province', $request->province);
            });
        }
        if ($request->filled('user_id')) {
            $reportsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        $reports = $reportsQuery->get();

        // Calculate budget summaries
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request, true);

        // Fetch provinces and users for filtering options
        $provinces = User::distinct()->pluck('province');
        $users = User::all();

        // Fetch distinct project types
        $projectTypes = DPReport::where('status', 'approved_by_coordinator')->distinct()->pluck('project_type');

        return view('coordinator.approvedReports', compact('reports', 'coordinator', 'provinces', 'users', 'projectTypes'));
    }

}
