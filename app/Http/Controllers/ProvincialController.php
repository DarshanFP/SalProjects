<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Reports\Monthly\ReportController;
use App\Models\OldProjects\Project;
use App\Models\ProjectComment;
use App\Models\ReportComment;
use App\Models\Reports\Monthly\DPReport;
use App\Models\User;
use App\Models\Province;
use App\Models\Center;
use App\Models\Society;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\ProjectStatusService;
use App\Services\ReportStatusService;
use App\Services\ActivityHistoryService;
use App\Constants\ProjectStatus;
use Exception;


class ProvincialController extends Controller
{
    // Access to provincials and general users (who can be provincial for provinces)
    public function __construct()
    {
        $this->middleware(['auth', 'role:provincial,general']);
    }

    /**
     * Get all user IDs that this provincial user can access.
     * For regular provincial users: Direct children (parent_id = provincial.id)
     * For general users managing provinces: All users in managed provinces + direct children
     * Respects province filter for general users (from session)
     */
    protected function getAccessibleUserIds($provincial)
    {
        $userIds = collect();

        // Always include direct children (executors/applicants under this user)
        $directChildren = User::where('parent_id', $provincial->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->pluck('id');
        $userIds = $userIds->merge($directChildren);

        // For general users managing multiple provinces, also include users from all managed provinces
        if ($provincial->role === 'general') {
            $managedProvinces = $provincial->managedProvinces()->pluck('provinces.id');

            // Check if province filter is set in session
            $filteredProvinceIds = session('province_filter_ids', []);
            $filterAll = session('province_filter_all', true);

            // If filter is set and not "all", use filtered provinces
            if (!empty($filteredProvinceIds) && !$filterAll) {
                // Only use provinces that are both managed and in the filter
                $provincesToUse = array_intersect($managedProvinces->toArray(), $filteredProvinceIds);
            } else {
                // Use all managed provinces (default or "all" selected)
                $provincesToUse = $managedProvinces->toArray();
            }

            if (!empty($provincesToUse)) {
                $provinceUsers = User::whereIn('province_id', $provincesToUse)
                    ->whereIn('role', ['executor', 'applicant', 'provincial'])
                    ->pluck('id');
                $userIds = $userIds->merge($provinceUsers);
            }
        }

        return $userIds->unique()->values();
    }

    // Index page for provincial
    public function provincialDashboard(Request $request)
    {
        $provincial = auth()->user();

        \Log::info('Provincial Dashboard Request', [
            'user_id' => $provincial->id,
            'user_role' => $provincial->role,
            'center' => $request->get('center'),
            'role' => $request->get('role'),
            'project_type' => $request->get('project_type')
        ]);

        // Get all accessible user IDs (handles both provincial and general users)
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Get approved projects for all accessible users
        $projectsQuery = Project::whereIn('user_id', $accessibleUserIds)
            ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);

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
        $centers = User::whereIn('id', $accessibleUserIds)
                      ->whereIn('role', ['executor', 'applicant'])
                      ->whereNotNull('center')
                      ->where('center', '!=', '')
                      ->distinct()
                      ->pluck('center')
                      ->filter()
                      ->values();

        $roles = ['executor', 'applicant'];

        $projectTypes = Project::whereIn('user_id', $accessibleUserIds)
            ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
            ->distinct()
            ->pluck('project_type');

        // Widget Data: Pending Approvals (Both Projects and Reports)
        $pendingData = $this->getPendingApprovalsForDashboard($provincial);
        $pendingProjects = $pendingData['projects'];
        $pendingReports = $pendingData['reports'];
        $pendingProjectsCount = $pendingProjects->count();
        $pendingReportsCount = $pendingReports->count();
        $totalPendingCount = $pendingProjectsCount + $pendingReportsCount;

        // Calculate urgency counts for reports
        $urgentCount = $pendingReports->filter(function($report) {
            return $report->created_at->diffInDays(now()) > 7;
        })->count();
        $normalCount = $pendingReports->filter(function($report) {
            $days = $report->created_at->diffInDays(now());
            return $days > 3 && $days <= 7;
        })->count();

        // Calculate urgency counts for projects
        $urgentProjectsCount = $pendingProjects->filter(function($project) {
            return $project->created_at->diffInDays(now()) > 7;
        })->count();
        $normalProjectsCount = $pendingProjects->filter(function($project) {
            $days = $project->created_at->diffInDays(now());
            return $days > 3 && $days <= 7;
        })->count();

        // Widget Data: Team Overview
        $teamMembers = $this->getTeamMembersForDashboard($provincial);
        $teamStats = $this->calculateTeamStats($teamMembers);

        // Widget Data: Approval Queue (Both Projects and Reports)
        $approvalQueueData = $this->getApprovalQueueForDashboard($provincial);
        $approvalQueueProjects = $approvalQueueData['projects'];
        $approvalQueueReports = $approvalQueueData['reports'];
        $approvalQueue = $approvalQueueReports; // Keep for backward compatibility
        $teamMembersForQueue = User::whereIn('id', $accessibleUserIds)
            ->whereIn('role', ['executor', 'applicant'])
            ->select('id', 'name')
            ->get();

        // Ensure centers list includes centers from approval queue items
        $approvalQueueCenters = collect();
        foreach ($approvalQueueProjects as $project) {
            if ($project->user && $project->user->center) {
                $approvalQueueCenters->push(trim($project->user->center));
            }
        }
        foreach ($approvalQueueReports as $report) {
            if ($report->user && $report->user->center) {
                $approvalQueueCenters->push(trim($report->user->center));
            }
        }
        // Merge with existing centers and ensure uniqueness
        $allCenters = $centers->merge($approvalQueueCenters)->unique()->filter()->sort()->values();

        // Phase 2 Widget Data: Team Performance Summary
        $performanceMetrics = $this->calculateTeamPerformanceMetrics($provincial);
        $chartData = $this->prepareChartDataForTeamPerformance($provincial);
        $centerPerformance = $this->calculateCenterPerformance($provincial);

        // Phase 2 Widget Data: Team Activity Feed
        $teamActivities = ActivityHistoryService::getForProvincial($provincial)
            ->take(50)
            ->values();

        // Phase 3 Widget Data: Team Budget Overview (Enhanced)
        $budgetData = $this->calculateEnhancedBudgetData($provincial);

        // Phase 3 Widget Data: Center Performance Comparison
        $centerComparison = $this->prepareCenterComparisonData($provincial);

        \Log::info('Provincial Dashboard Filter Options', [
            'selected_center' => $request->get('center'),
            'selected_role' => $request->get('role'),
            'selected_project_type' => $request->get('project_type'),
            'available_centers_count' => $centers->count(),
            'total_projects' => $projects->count(),
            'projects_by_center' => $projects->groupBy(function($project) {
                return $project->user->center ?? 'Unknown';
            })->map(function($group) {
                return $group->count();
            })->toArray()
        ]);

        return view('provincial.index', compact(
            'budgetSummaries',
            'centers',
            'allCenters',
            'roles',
            'projectTypes',
            'pendingProjects',
            'pendingReports',
            'pendingProjectsCount',
            'pendingReportsCount',
            'totalPendingCount',
            'urgentCount',
            'normalCount',
            'urgentProjectsCount',
            'normalProjectsCount',
            'teamMembers',
            'teamStats',
            'approvalQueue',
            'approvalQueueProjects',
            'approvalQueueReports',
            'teamMembersForQueue',
            'performanceMetrics',
            'chartData',
            'centerPerformance',
            'teamActivities',
            'budgetData',
            'centerComparison'
        ));
    }

    private function calculateBudgetSummariesFromProjects($projects, $request)
    {
        $budgetSummaries = [
            'by_project_type' => [],
            'by_center' => [],
            'total' => [
                'total_budget' => 0,
                'approved_expenses' => 0,
                'unapproved_expenses' => 0,
                'total_expenses' => 0,
                'total_remaining' => 0
            ]
        ];

        foreach ($projects as $project) {
            // Calculate project budget from multiple sources (like executor dashboard)
            $projectBudget = 0;
            if ($project->overall_project_budget && $project->overall_project_budget > 0) {
                $projectBudget = $project->overall_project_budget;
            } elseif ($project->amount_sanctioned && $project->amount_sanctioned > 0) {
                $projectBudget = $project->amount_sanctioned;
            } elseif ($project->budgets && $project->budgets->count() > 0) {
                $projectBudget = $project->budgets->sum('this_phase');
            }

            // If no budget found, try to get from approved reports
            if ($projectBudget == 0 && $project->reports && $project->reports->count() > 0) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails && $report->accountDetails->count() > 0) {
                        $projectBudget = $report->accountDetails->sum('total_amount');
                        break;
                    }
                }
            }

            // Calculate approved and unapproved expenses separately
            // Exclude drafts and editable statuses where executor/applicant has edit access
            $approvedExpenses = 0;
            $unapprovedExpenses = 0;

            if ($project->reports && $project->reports->count() > 0) {
                // Ensure accountDetails relationship is loaded
                if (!$project->relationLoaded('reports.accountDetails')) {
                    $project->load('reports.accountDetails');
                }

                foreach ($project->reports as $report) {
                    if (!$report->accountDetails || $report->accountDetails->count() == 0) {
                        continue;
                    }

                    // Exclude drafts and editable statuses (where executor/applicant has edit access)
                    // Only show approved and unapproved expenses for reports where executor/applicant has edit access
                    // Since all projects here are for executors/applicants under provincial, they have edit access
                    $editableStatuses = [
                        DPReport::STATUS_DRAFT,
                        DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                        DPReport::STATUS_REVERTED_BY_COORDINATOR,
                        DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
                        DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
                        DPReport::STATUS_REVERTED_TO_EXECUTOR,
                        DPReport::STATUS_REVERTED_TO_APPLICANT,
                        DPReport::STATUS_REVERTED_TO_PROVINCIAL,
                        DPReport::STATUS_REVERTED_TO_COORDINATOR,
                    ];

                    // Exclude drafts and updated statuses - only include submitted/forwarded/approved reports
                    if (in_array($report->status, $editableStatuses)) {
                        continue;
                    }

                    $reportExpenses = $report->accountDetails->sum('total_expenses') ?? 0;

                    // Separate approved vs unapproved expenses based on report status
                    if ($report->isApproved()) {
                        $approvedExpenses += $reportExpenses;
                    } else {
                        // All other non-draft, non-editable statuses are unapproved (submitted, forwarded, etc.)
                        $unapprovedExpenses += $reportExpenses;
                    }
                }
            }

            $totalExpenses = $approvedExpenses + $unapprovedExpenses;
            // Only approved expenses reduce remaining budget (unapproved don't reduce available budget until approved)
            $remainingBudget = $projectBudget - $approvedExpenses;

            // Initialize project type if not exists
            if (!isset($budgetSummaries['by_project_type'][$project->project_type])) {
                $budgetSummaries['by_project_type'][$project->project_type] = [
                    'total_budget' => 0,
                    'approved_expenses' => 0,
                    'unapproved_expenses' => 0,
                    'total_expenses' => 0,
                    'total_remaining' => 0
                ];
            }

            // Add to project type summary
            $budgetSummaries['by_project_type'][$project->project_type]['total_budget'] += $projectBudget;
            $budgetSummaries['by_project_type'][$project->project_type]['approved_expenses'] += $approvedExpenses;
            $budgetSummaries['by_project_type'][$project->project_type]['unapproved_expenses'] += $unapprovedExpenses;
            $budgetSummaries['by_project_type'][$project->project_type]['total_expenses'] += $totalExpenses;
            $budgetSummaries['by_project_type'][$project->project_type]['total_remaining'] += $remainingBudget;

            // Center summary
            $center = $project->user->center ?? 'Unknown Center';
            if (!isset($budgetSummaries['by_center'][$center])) {
                $budgetSummaries['by_center'][$center] = [
                    'total_budget' => 0,
                    'approved_expenses' => 0,
                    'unapproved_expenses' => 0,
                    'total_expenses' => 0,
                    'total_remaining' => 0
                ];
            }
            $budgetSummaries['by_center'][$center]['total_budget'] += $projectBudget;
            $budgetSummaries['by_center'][$center]['approved_expenses'] += $approvedExpenses;
            $budgetSummaries['by_center'][$center]['unapproved_expenses'] += $unapprovedExpenses;
            $budgetSummaries['by_center'][$center]['total_expenses'] += $totalExpenses;
            $budgetSummaries['by_center'][$center]['total_remaining'] += $remainingBudget;

            // Add to total summary
            $budgetSummaries['total']['total_budget'] += $projectBudget;
            $budgetSummaries['total']['approved_expenses'] += $approvedExpenses;
            $budgetSummaries['total']['unapproved_expenses'] += $unapprovedExpenses;
            $budgetSummaries['total']['total_expenses'] += $totalExpenses;
            $budgetSummaries['total']['total_remaining'] += $remainingBudget;
        }

        return $budgetSummaries;
    }

    public function reportList(Request $request)
    {
        $provincial = auth()->user();

        // Get all accessible user IDs (handles both provincial and general users)
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Fetch reports for all accessible users
        $reportsQuery = DPReport::whereIn('user_id', $accessibleUserIds);

        // Apply filtering if provided in the request
        if ($request->filled('place')) {
            $reportsQuery->whereHas('user', function ($query) use ($request) {
                $query->where('center', $request->place);
            });
        }
        if ($request->filled('user_id')) {
            $reportsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        // Eager load relationships to prevent N+1 queries
        $reports = $reportsQuery->with(['user', 'project', 'accountDetails'])->get();

        // Calculate budget summaries from reports
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

        // Fetch unique centers from accessible users
        $places = User::whereIn('id', $accessibleUserIds)
                     ->whereNotNull('center')
                     ->distinct()
                     ->pluck('center');

        $users = User::whereIn('id', $accessibleUserIds)->get();

        // Fetch distinct project types for filters
        $projectTypes = DPReport::whereIn('user_id', $accessibleUserIds)
            ->distinct()
            ->pluck('project_type');

        return view('provincial.ReportList', compact('reports', 'budgetSummaries', 'places', 'users', 'projectTypes'));
    }

    private function calculateBudgetSummaries($reports, $request, $onlyApproved = true)
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
            // Skip non-approved reports if onlyApproved is true
            if ($onlyApproved && !$report->isApproved()) continue;

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
            $budgetSummaries['total']['total_budget'] += $reportTotal;
            $budgetSummaries['total']['total_expenses'] += $reportExpenses;
            $budgetSummaries['total']['total_remaining'] += $reportRemaining;
        }
        return $budgetSummaries;
    }

    public function projectList(Request $request)
    {
        $provincial = auth()->user();

        // Get all accessible user IDs (handles both provincial and general users)
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Fetch ALL projects for accessible users (all statuses)
        $projectsQuery = Project::whereIn('user_id', $accessibleUserIds);

        // Apply filters
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        if ($request->filled('user_id')) {
            $projectsQuery->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $projectsQuery->where('status', $request->status);
        }

        if ($request->filled('center')) {
            $projectsQuery->whereHas('user', function($query) use ($request) {
                $query->where('center', $request->center);
            });
        }

        $projects = $projectsQuery->with(['user', 'reports.accountDetails'])->get();

        // Calculate project health and budget utilization for each project
        $projects = $projects->map(function($project) {
            // Calculate budget utilization
            $projectBudget = $project->amount_sanctioned ?? 0;
            $totalExpenses = 0;

            if ($project->reports) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails) {
                        $totalExpenses += $report->accountDetails->sum('total_expenses') ?? 0;
                    }
                }
            }

            $utilization = $projectBudget > 0 ? (($totalExpenses / $projectBudget) * 100) : 0;

            // Determine health status
            $health = 'good';
            if ($utilization > 90) {
                $health = 'critical';
            } elseif ($utilization > 75) {
                $health = 'warning';
            }

            // Add calculated fields
            $project->budget_utilization = $utilization;
            $project->total_expenses = $totalExpenses;
            $project->health_status = $health;

            return $project;
        });

        // Get all accessible user IDs
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Fetch distinct executors/applicants for filtering
        $users = User::whereIn('id', $accessibleUserIds)
            ->whereIn('role', ['executor', 'applicant'])
            ->get();

        // Distinct project types
        $projectTypes = Project::whereIn('user_id', $accessibleUserIds)
            ->distinct()
            ->pluck('project_type');

        // Distinct centers
        $centers = User::whereIn('id', $accessibleUserIds)
            ->whereIn('role', ['executor', 'applicant'])
            ->whereNotNull('center')
            ->distinct()
            ->pluck('center')
            ->filter()
            ->values();

        // Project status distribution for chart
        $statusDistribution = $projects->groupBy('status')->map->count();

        return view('provincial.ProjectList', compact(
            'projects',
            'users',
            'projectTypes',
            'centers',
            'statusDistribution'
        ));
    }

    public function showProject($project_id)
    {
        $provincial = auth()->user();

        // Fetch the project and ensure it exists
        $project = Project::where('project_id', $project_id)
            ->with('user')
            ->firstOrFail();

        // Authorization check: the project's user must be accessible by this provincial
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);
        if (!in_array($project->user_id, $accessibleUserIds->toArray())) {
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

        // Authorization check: Ensure the report belongs to an accessible user
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);
        if (!in_array($report->user_id, $accessibleUserIds->toArray())) {
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

        // Authorization check: Ensure the report belongs to an accessible user
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);
        if (!in_array($report->user_id, $accessibleUserIds->toArray())) {
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
    public function createExecutor()
    {
        $provincial = auth()->user();
        $province = strtoupper($provincial->province);

        // Get centers map from database
        $centersMap = $this->getCentersMap();

        // Get the centers for the current provincial's province and sort in ascending order
        $centers = $centersMap[$province] ?? [];
        sort($centers); // Sort centers alphabetically in ascending order

        return view('provincial.createExecutor', compact('centers'));
    }

    // Store Executor
    public function storeExecutor(Request $request)
    {
        try {
            // Log the incoming request data
            Log::info('Attempting to store a new executor', [
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
                'province' => $request->province,
            ]);

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

            // Get province and center IDs from database
            $province = Province::where('name', $provincial->province)->first();
            $provinceId = $province ? $province->id : null;

            $centerId = null;
            if (!empty($validatedData['center']) && $provinceId) {
                $center = Center::where('province_id', $provinceId)
                    ->whereRaw('UPPER(name) = ?', [strtoupper($validatedData['center'])])
                    ->first();
                $centerId = $center ? $center->id : null;
            }

            $executor = User::create([
                'name' => $validatedData['name'],
                'username' => $validatedData['username'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'phone' => $validatedData['phone'],
                'society_name' => $validatedData['society_name'],
                'province' => $provincial->province,
                'province_id' => $provinceId,
                'center' => $validatedData['center'],
                'center_id' => $centerId,
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

        // Get all accessible user IDs
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        $executors = User::whereIn('id', $accessibleUserIds)
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

        // Get centers map from database
        $centersMap = $this->getCentersMap();

        // Get the centers for the current provincial's province and sort in ascending order
        $centers = $centersMap[$province] ?? [];
        sort($centers); // Sort centers alphabetically in ascending order

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
            'role' => 'required|in:executor,applicant',
            'status' => 'required|in:active,inactive',
        ]);

        // Get province and center IDs from database
        // Provincial users belong to their provincial's province
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();
        $provinceId = $province ? $province->id : null;

        $centerId = null;
        if ($request->filled('center') && $provinceId) {
            $center = Center::where('province_id', $provinceId)
                ->whereRaw('UPPER(name) = ?', [strtoupper($request->center)])
                ->first();
            $centerId = $center ? $center->id : null;
        }

        $executor->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'society_name' => $request->society_name,
            'center' => $request->center,
            'center_id' => $centerId,
            'address' => $request->address,
            'role' => $request->role,
            'province_id' => $provinceId,
            'status' => $request->status,
        ]);

        // Update Spatie role assignment
        $executor->syncRoles([$request->role]);

        $roleName = ucfirst($request->role);
        return redirect()->route('provincial.executors')->with('success', $roleName . ' updated successfully.');
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

        // Check if the user is accessible by this provincial
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);
        if (!in_array($user->id, $accessibleUserIds->toArray())) {
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

        // Check if the user is accessible by this provincial
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);
        if (!in_array($user->id, $accessibleUserIds->toArray())) {
            abort(403, 'Unauthorized action.');
        }

        $user->update(['status' => 'inactive']);

        return redirect()->route('provincial.executors')->with('success', ucfirst($user->role) . ' deactivated successfully.');
    }

    // Show Create Center form
    public function createCenter()
    {
        $provincial = auth()->user();

        // Get the provincial's province
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        // Get existing centers for the province
        $existingCenters = Center::where('province_id', $province->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        return view('provincial.createCenter', compact('province', 'existingCenters'));
    }

    // Store Center
    public function storeCenter(Request $request)
    {
        $provincial = auth()->user();

        // Get the provincial's province
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($province) {
                    // Check if center with same name already exists in this province
                    $existingCenter = Center::where('province_id', $province->id)
                        ->whereRaw('UPPER(name) = ?', [strtoupper($value)])
                        ->first();

                    if ($existingCenter && $existingCenter->is_active) {
                        $fail('A center with this name already exists in your province.');
                    }
                },
            ],
        ]);

        try {
            // Check if center exists but is inactive
            $existingCenter = Center::where('province_id', $province->id)
                ->whereRaw('UPPER(name) = ?', [strtoupper($request->name)])
                ->first();

            if ($existingCenter) {
                // Reactivate the center
                $existingCenter->update(['is_active' => true]);

                Log::info('Center reactivated by Provincial', [
                    'provincial_id' => $provincial->id,
                    'center_id' => $existingCenter->id,
                    'center_name' => $request->name,
                ]);
            } else {
                // Create new center
                $center = Center::create([
                    'province_id' => $province->id,
                    'name' => $request->name,
                    'is_active' => true,
                ]);

                Log::info('Center created by Provincial', [
                    'provincial_id' => $provincial->id,
                    'center_id' => $center->id,
                    'center_name' => $request->name,
                ]);
            }

            // Clear the centers cache to reflect the new center
            Cache::forget('centers_map');

            return redirect()->route('provincial.createCenter')
                ->with('success', 'Center "' . $request->name . '" created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating center by Provincial', [
                'provincial_id' => $provincial->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors('Failed to create center: ' . $e->getMessage())->withInput();
        }
    }

    // List Centers for Provincial
    public function listCenters()
    {
        $provincial = auth()->user();

        // Get the provincial's province
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        // Get all centers for the province
        $centers = Center::where('province_id', $province->id)
            ->orderBy('name')
            ->get();

        return view('provincial.centers', compact('province', 'centers'));
    }

    // Edit Center for Provincial
    public function editCenter($id)
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        $center = Center::where('id', $id)
            ->where('province_id', $province->id)
            ->firstOrFail();

        return view('provincial.centers.edit', compact('center', 'province'));
    }

    // Update Center for Provincial
    public function updateCenter(Request $request, $id)
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        $center = Center::where('id', $id)
            ->where('province_id', $province->id)
            ->firstOrFail();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($province, $id) {
                    $existingCenter = Center::where('province_id', $province->id)
                        ->whereRaw('UPPER(name) = ?', [strtoupper($value)])
                        ->where('id', '!=', $id)
                        ->first();

                    if ($existingCenter && $existingCenter->is_active) {
                        $fail('A center with this name already exists in your province.');
                    }
                },
            ],
            'is_active' => 'required|boolean',
        ]);

        try {
            $center->update([
                'name' => $request->name,
                'is_active' => $request->is_active,
            ]);

            Cache::forget('centers_map');

            Log::info('Center updated by Provincial', [
                'provincial_id' => $provincial->id,
                'center_id' => $center->id,
                'center_name' => $request->name,
            ]);

            return redirect()->route('provincial.centers')
                ->with('success', 'Center "' . $request->name . '" updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating center by Provincial', [
                'provincial_id' => $provincial->id,
                'center_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Failed to update center: ' . $e->getMessage())->withInput();
        }
    }

    // ==================== Provincial Management ====================

    // List Provincials in Province
    public function listProvincials()
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        $provincials = User::where('province_id', $province->id)
            ->where('role', 'provincial')
            ->orderBy('name')
            ->get();

        return view('provincial.provincials.index', compact('province', 'provincials'));
    }

    // Create Provincial Form
    public function createProvincial()
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        // Get centers for the province
        $centers = Center::where('province_id', $province->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get societies for the province
        $societies = Society::where('province_id', $province->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('provincial.provincials.create', compact('province', 'centers', 'societies'));
    }

    // Store Provincial
    public function storeProvincial(Request $request)
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:255',
            'society_name' => 'nullable|string|max:255',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $centerId = null;
        if (!empty($validatedData['center'])) {
            $center = Center::where('province_id', $province->id)
                ->whereRaw('UPPER(name) = ?', [strtoupper($validatedData['center'])])
                ->first();
            $centerId = $center ? $center->id : null;
        }

        try {
            $newProvincial = User::create([
                'name' => $validatedData['name'],
                'username' => $validatedData['username'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'phone' => $validatedData['phone'],
                'society_name' => $validatedData['society_name'] ?? null,
                'province' => $province->name,
                'province_id' => $province->id,
                'center' => $validatedData['center'] ?? null,
                'center_id' => $centerId,
                'address' => $validatedData['address'] ?? null,
                'role' => 'provincial',
                'status' => $validatedData['status'],
                'parent_id' => $provincial->id,
            ]);

            $newProvincial->assignRole('provincial');

            Log::info('Provincial created by Provincial', [
                'created_by' => $provincial->id,
                'new_provincial_id' => $newProvincial->id,
                'name' => $validatedData['name'],
            ]);

            return redirect()->route('provincial.provincials')
                ->with('success', 'Provincial user created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating provincial', [
                'created_by' => $provincial->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Failed to create provincial: ' . $e->getMessage())->withInput();
        }
    }

    // Edit Provincial Form
    public function editProvincial($id)
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        $targetProvincial = User::where('id', $id)
            ->where('province_id', $province->id)
            ->where('role', 'provincial')
            ->firstOrFail();

        $centers = Center::where('province_id', $province->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $societies = Society::where('province_id', $province->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('provincial.provincials.edit', compact('targetProvincial', 'province', 'centers', 'societies'));
    }

    // Update Provincial
    public function updateProvincial(Request $request, $id)
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        $targetProvincial = User::where('id', $id)
            ->where('province_id', $province->id)
            ->where('role', 'provincial')
            ->firstOrFail();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:255',
            'society_name' => 'nullable|string|max:255',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $centerId = null;
        if (!empty($validatedData['center'])) {
            $center = Center::where('province_id', $province->id)
                ->whereRaw('UPPER(name) = ?', [strtoupper($validatedData['center'])])
                ->first();
            $centerId = $center ? $center->id : null;
        }

        try {
            $targetProvincial->update([
                'name' => $validatedData['name'],
                'username' => $validatedData['username'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'society_name' => $validatedData['society_name'] ?? null,
                'center' => $validatedData['center'] ?? null,
                'center_id' => $centerId,
                'address' => $validatedData['address'] ?? null,
                'status' => $validatedData['status'],
            ]);

            Log::info('Provincial updated by Provincial', [
                'updated_by' => $provincial->id,
                'provincial_id' => $targetProvincial->id,
                'name' => $validatedData['name'],
            ]);

            return redirect()->route('provincial.provincials')
                ->with('success', 'Provincial user updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating provincial', [
                'updated_by' => $provincial->id,
                'provincial_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Failed to update provincial: ' . $e->getMessage())->withInput();
        }
    }

    // ==================== Society Management ====================

    // List Societies in Province
    public function listSocieties()
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        $societies = Society::where('province_id', $province->id)
            ->orderBy('name')
            ->get();

        return view('provincial.societies.index', compact('province', 'societies'));
    }

    // Create Society Form
    public function createSociety()
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        return view('provincial.societies.create', compact('province'));
    }

    // Store Society
    public function storeSociety(Request $request)
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($province) {
                    $existingSociety = Society::where('province_id', $province->id)
                        ->whereRaw('UPPER(name) = ?', [strtoupper($value)])
                        ->first();

                    if ($existingSociety) {
                        $fail('A society with this name already exists in your province.');
                    }
                },
            ],
        ]);

        try {
            $society = Society::create([
                'province_id' => $province->id,
                'name' => $request->name,
                'is_active' => true,
            ]);

            Log::info('Society created by Provincial', [
                'provincial_id' => $provincial->id,
                'society_id' => $society->id,
                'society_name' => $request->name,
            ]);

            return redirect()->route('provincial.societies')
                ->with('success', 'Society "' . $request->name . '" created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating society', [
                'provincial_id' => $provincial->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Failed to create society: ' . $e->getMessage())->withInput();
        }
    }

    // Edit Society Form
    public function editSociety($id)
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        $society = Society::where('id', $id)
            ->where('province_id', $province->id)
            ->firstOrFail();

        return view('provincial.societies.edit', compact('society', 'province'));
    }

    // Update Society
    public function updateSociety(Request $request, $id)
    {
        $provincial = auth()->user();
        $province = Province::where('name', $provincial->province)->first();

        if (!$province) {
            return redirect()->route('provincial.dashboard')
                ->with('error', 'Province not found. Please contact administrator.');
        }

        $society = Society::where('id', $id)
            ->where('province_id', $province->id)
            ->firstOrFail();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($province, $id) {
                    $existingSociety = Society::where('province_id', $province->id)
                        ->whereRaw('UPPER(name) = ?', [strtoupper($value)])
                        ->where('id', '!=', $id)
                        ->first();

                    if ($existingSociety) {
                        $fail('A society with this name already exists in your province.');
                    }
                },
            ],
            'is_active' => 'required|boolean',
        ]);

        try {
            $society->update([
                'name' => $request->name,
                'is_active' => $request->is_active,
            ]);

            Log::info('Society updated by Provincial', [
                'provincial_id' => $provincial->id,
                'society_id' => $society->id,
                'society_name' => $request->name,
            ]);

            return redirect()->route('provincial.societies')
                ->with('success', 'Society "' . $request->name . '" updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating society', [
                'provincial_id' => $provincial->id,
                'society_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Failed to update society: ' . $e->getMessage())->withInput();
        }
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
    public function revertToExecutor(Request $request, $project_id)
    {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $provincial = auth()->user();

        try {
            $reason = $request->input('revert_reason');
            ProjectStatusService::revertByProvincial($project, $provincial, $reason);
            return redirect()->route('provincial.projects.list')->with('success', 'Project reverted to Executor.');
        } catch (Exception $e) {
            abort(403, $e->getMessage());
        }
    }

    public function forwardToCoordinator($project_id)
    {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $provincial = auth()->user();

        try {
            ProjectStatusService::forwardToCoordinator($project, $provincial);
            return redirect()->route('provincial.projects.list')->with('success', 'Project forwarded to Coordinator.');
        } catch (Exception $e) {
            abort(403, $e->getMessage());
        }
    }

    // Approved Projects for Provincials
    public function approvedProjects(Request $request)
    {
        $provincial = auth()->user();

        // Get all accessible user IDs (handles both provincial and general users)
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Get approved projects for all accessible users
        $projectsQuery = Project::whereIn('user_id', $accessibleUserIds)
            ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);

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

        // Fetch unique centers from users of approved projects
        $places = User::whereIn('id', $accessibleUserIds)
            ->whereHas('projects', function ($query) {
                $query->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
            })
            ->whereNotNull('center')
            ->distinct()
            ->pluck('center');

        $users = User::whereIn('id', $accessibleUserIds)->get();

        // Fetch distinct project types for filters
        $projectTypes = Project::whereIn('user_id', $accessibleUserIds)
            ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
            ->distinct()
            ->pluck('project_type');

        return view('provincial.approvedProjects', compact('projects', 'places', 'users', 'projectTypes'));
    }

    public function forwardReport(Request $request, $report_id)
    {
        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        // Check if the report belongs to an accessible user
        $provincial = auth()->user();
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);
        if (!in_array($report->user_id, $accessibleUserIds->toArray())) {
            return redirect()->back()->with('error', 'You are not authorized to forward this report.');
        }

        try {
            // Use ReportStatusService to forward and log status change
            ReportStatusService::forwardToCoordinator($report, $provincial);

            return redirect()->route('provincial.report.list')->with('success', 'Report forwarded to coordinator successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to forward report', [
                'report_id' => $report_id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function revertReport(Request $request, $report_id)
    {
        $request->validate([
            'revert_reason' => 'required|string|max:1000'
        ]);

        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        // Check if the report belongs to an accessible user
        $provincial = auth()->user();
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);
        if (!in_array($report->user_id, $accessibleUserIds->toArray())) {
            return redirect()->back()->with('error', 'You are not authorized to revert this report.');
        }

        try {
            // Use ReportStatusService to revert and log status change
            ReportStatusService::revertByProvincial($report, $provincial, $request->revert_reason);

            return redirect()->route('provincial.report.list')->with('success', 'Report reverted to executor successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to revert report', [
                'report_id' => $report_id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function pendingReports(Request $request)
    {
        $provincial = auth()->user();

        // Get all accessible user IDs (handles both provincial and general users)
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Fetch pending reports for all accessible users
        $reportsQuery = DPReport::whereIn('user_id', $accessibleUserIds)
            ->whereIn('status', [DPReport::STATUS_SUBMITTED_TO_PROVINCIAL, DPReport::STATUS_REVERTED_BY_COORDINATOR]);

        // Apply filtering if provided in the request
        if ($request->filled('place')) {
            $reportsQuery->whereHas('user', function ($query) use ($request) {
                $query->where('center', $request->place);
            });
        }
        if ($request->filled('user_id')) {
            $reportsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        $reports = $reportsQuery->with(['user', 'accountDetails'])->get();

        // Calculate budget summaries from reports (include all reports for pending)
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request, false);

        // Fetch unique centers from accessible users
        $places = User::whereIn('id', $accessibleUserIds)
                     ->whereNotNull('center')
                     ->distinct()
                     ->pluck('center');

        $users = User::whereIn('id', $accessibleUserIds)->get();

        // Fetch distinct project types for filters
        $projectTypes = DPReport::whereIn('user_id', $accessibleUserIds)
            ->whereIn('status', [DPReport::STATUS_SUBMITTED_TO_PROVINCIAL, DPReport::STATUS_REVERTED_BY_COORDINATOR])
            ->distinct()
            ->pluck('project_type');

        return view('provincial.pendingReports', compact('reports', 'budgetSummaries', 'places', 'users', 'projectTypes'));
    }

    public function approvedReports(Request $request)
    {
        $provincial = auth()->user();

        // Get all accessible user IDs (handles both provincial and general users)
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Fetch approved reports for all accessible users
        $reportsQuery = DPReport::whereIn('user_id', $accessibleUserIds)
            ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);

        // Apply filtering if provided in the request
        if ($request->filled('place')) {
            $reportsQuery->whereHas('user', function ($query) use ($request) {
                $query->where('center', $request->place);
            });
        }
        if ($request->filled('user_id')) {
            $reportsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        $reports = $reportsQuery->with(['user', 'accountDetails'])->get();

        // Calculate budget summaries from reports (only approved reports)
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request, true);

        // Fetch unique centers from accessible users
        $places = User::whereIn('id', $accessibleUserIds)
                     ->whereNotNull('center')
                     ->distinct()
                     ->pluck('center');

        $users = User::whereIn('id', $accessibleUserIds)->get();

        // Fetch distinct project types for filters
        $projectTypes = DPReport::whereIn('user_id', $accessibleUserIds)
            ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
            ->distinct()
            ->pluck('project_type');

        return view('provincial.approvedReports', compact('reports', 'budgetSummaries', 'places', 'users', 'projectTypes'));
    }

    /**
     * Get pending approvals for dashboard widget (Both Projects and Reports)
     */
    private function getPendingApprovalsForDashboard($provincial)
    {
        // Get all accessible user IDs
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Get pending reports
        $pendingReports = DPReport::whereIn('user_id', $accessibleUserIds)
        ->whereIn('status', [
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_COORDINATOR
        ])
        ->with(['user', 'project'])
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(function($report) {
            $daysPending = $report->created_at->diffInDays(now());
            $report->days_pending = $daysPending;
            $report->urgency = $daysPending > 7 ? 'urgent' : ($daysPending > 3 ? 'normal' : 'low');
            $report->type = 'report';
            return $report;
        })
        ->sortByDesc(function($report) {
            return $report->urgency === 'urgent' ? 3 : ($report->urgency === 'normal' ? 2 : 1);
        })
        ->values();

        // Get pending projects
        $pendingProjects = Project::whereHas('user', function ($query) use ($provincial) {
            $query->where('parent_id', $provincial->id);
        })
        ->whereIn('status', [
            ProjectStatus::SUBMITTED_TO_PROVINCIAL,
            ProjectStatus::REVERTED_BY_COORDINATOR
        ])
        ->with(['user'])
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(function($project) {
            $daysPending = $project->created_at->diffInDays(now());
            $project->days_pending = $daysPending;
            $project->urgency = $daysPending > 7 ? 'urgent' : ($daysPending > 3 ? 'normal' : 'low');
            $project->type = 'project';
            return $project;
        })
        ->sortByDesc(function($project) {
            return $project->urgency === 'urgent' ? 3 : ($project->urgency === 'normal' ? 2 : 1);
        })
        ->values();

        return [
            'projects' => $pendingProjects,
            'reports' => $pendingReports
        ];
    }

    /**
     * Get team members for dashboard widget
     */
    private function getTeamMembersForDashboard($provincial)
    {
        // Get all accessible user IDs
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        return User::whereIn('id', $accessibleUserIds)
            ->whereIn('role', ['executor', 'applicant'])
            ->withCount([
                'projects' => function($query) {
                    $query->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
                },
                'reports' => function($query) {
                    $query->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);
                }
            ])
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Calculate team statistics
     */
    private function calculateTeamStats($teamMembers)
    {
        $totalMembers = $teamMembers->count();
        $activeMembers = $teamMembers->where('status', 'active')->count();
        $totalProjects = $teamMembers->sum('projects_count');
        $totalReports = $teamMembers->sum('reports_count');

        return [
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'inactive_members' => $totalMembers - $activeMembers,
            'total_projects' => $totalProjects,
            'total_reports' => $totalReports,
            'avg_projects_per_member' => $totalMembers > 0 ? round($totalProjects / $totalMembers, 1) : 0,
            'avg_reports_per_member' => $totalMembers > 0 ? round($totalReports / $totalMembers, 1) : 0,
        ];
    }

    /**
     * Get approval queue for dashboard widget (Both Projects and Reports)
     */
    private function getApprovalQueueForDashboard($provincial)
    {
        // Get all accessible user IDs
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Get pending reports
        $pendingReports = DPReport::whereIn('user_id', $accessibleUserIds)
        ->whereIn('status', [
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_COORDINATOR
        ])
        ->with(['user', 'project'])
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(function($report) {
            $daysPending = $report->created_at->diffInDays(now());
            $report->days_pending = $daysPending;
            $report->urgency = $daysPending > 7 ? 'urgent' : ($daysPending > 3 ? 'normal' : 'low');
            $report->type = 'report';
            return $report;
        })
        ->sortByDesc(function($report) {
            return $report->urgency === 'urgent' ? 3 : ($report->urgency === 'normal' ? 2 : 1);
        })
        ->take(20)
        ->values();

        // Get pending projects
        $pendingProjects = Project::whereHas('user', function ($query) use ($provincial) {
            $query->where('parent_id', $provincial->id);
        })
        ->whereIn('status', [
            ProjectStatus::SUBMITTED_TO_PROVINCIAL,
            ProjectStatus::REVERTED_BY_COORDINATOR
        ])
        ->with(['user'])
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(function($project) {
            $daysPending = $project->created_at->diffInDays(now());
            $project->days_pending = $daysPending;
            $project->urgency = $daysPending > 7 ? 'urgent' : ($daysPending > 3 ? 'normal' : 'low');
            $project->type = 'project';
            return $project;
        })
        ->sortByDesc(function($project) {
            return $project->urgency === 'urgent' ? 3 : ($project->urgency === 'normal' ? 2 : 1);
        })
        ->take(20)
        ->values();

        return [
            'projects' => $pendingProjects,
            'reports' => $pendingReports
        ];
    }

    /**
     * Bulk forward reports to coordinator
     */
    public function bulkForwardReports(Request $request)
    {
        $request->validate([
            'report_ids' => 'required|array|min:1',
            'report_ids.*' => 'required|string|exists:DP_Reports,report_id'
        ]);

        $provincial = auth()->user();
        $reportIds = $request->report_ids;

        $successCount = 0;
        $failedReports = [];

        foreach ($reportIds as $reportId) {
            try {
                $report = DPReport::where('report_id', trim($reportId))->first();

                if (!$report) {
                    $failedReports[] = $reportId . ' (not found)';
                    continue;
                }

                // Check authorization
                $accessibleUserIds = $this->getAccessibleUserIds($provincial);
                if (!in_array($report->user_id, $accessibleUserIds->toArray())) {
                    $failedReports[] = $reportId . ' (unauthorized)';
                    continue;
                }

                // Check if report can be forwarded
                if (!$report->isSubmittedToProvincial()) {
                    $failedReports[] = $reportId . ' (invalid status: ' . $report->status . ')';
                    continue;
                }

                // Forward report
                ReportStatusService::forwardToCoordinator($report, $provincial);
                $successCount++;
            } catch (\Exception $e) {
                Log::error('Failed to forward report in bulk', [
                    'report_id' => $reportId,
                    'error' => $e->getMessage(),
                ]);
                $failedReports[] = $reportId . ' (' . $e->getMessage() . ')';
            }
        }

        if (count($failedReports) > 0 && $successCount > 0) {
            return redirect()->back()->with([
                'success' => "Successfully forwarded {$successCount} report(s).",
                'warning' => 'Failed to forward: ' . implode(', ', $failedReports)
            ]);
        } elseif (count($failedReports) > 0) {
            return redirect()->back()->with('error', 'Failed to forward reports: ' . implode(', ', $failedReports));
        }

        return redirect()->route('provincial.dashboard')->with('success', "Successfully forwarded {$successCount} report(s) to coordinator.");
    }

    /**
     * Calculate team performance metrics
     */
    private function calculateTeamPerformanceMetrics($provincial)
    {
        // Get all accessible user IDs
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Get all team projects (all statuses)
        $teamProjects = Project::whereIn('user_id', $accessibleUserIds)
            ->with(['user', 'reports.accountDetails'])
            ->get();

        // Get all team reports (all statuses)
        $teamReports = DPReport::whereIn('user_id', $accessibleUserIds)->get();

        // Calculate projects by status
        $projectsByStatus = $teamProjects->groupBy('status')->map(function($group) {
            return $group->count();
        })->toArray();

        // Calculate reports by status
        $reportsByStatus = $teamReports->groupBy('status')->map(function($group) {
            return $group->count();
        })->toArray();

        // Calculate budget metrics (from approved projects only)
        $approvedProjects = $teamProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
        $totalBudget = $approvedProjects->sum('amount_sanctioned') ?? 0;
        $totalExpenses = 0;

        foreach ($approvedProjects as $project) {
            if ($project->reports) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails) {
                        $totalExpenses += $report->accountDetails->sum('total_expenses') ?? 0;
                    }
                }
            }
        }

        $budgetUtilization = $totalBudget > 0 ? (($totalExpenses / $totalBudget) * 100) : 0;

        // Calculate approval rate
        $totalSubmittedReports = $teamReports->whereIn('status', [
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
            DPReport::STATUS_FORWARDED_TO_COORDINATOR,
            DPReport::STATUS_APPROVED_BY_COORDINATOR
        ])->count();

        $approvedReports = $teamReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count();
        $approvalRate = $totalSubmittedReports > 0 ? (($approvedReports / $totalSubmittedReports) * 100) : 0;

        return [
            'total_projects' => $teamProjects->count(),
            'total_reports' => $teamReports->count(),
            'projects_by_status' => $projectsByStatus,
            'reports_by_status' => $reportsByStatus,
            'total_budget' => $totalBudget,
            'total_expenses' => $totalExpenses,
            'budget_utilization' => $budgetUtilization,
            'approval_rate' => $approvalRate,
            'approved_reports' => $approvedReports,
            'total_submitted_reports' => $totalSubmittedReports,
        ];
    }

    /**
     * Prepare chart data for team performance widget
     */
    private function prepareChartDataForTeamPerformance($provincial)
    {
        // Get all accessible user IDs
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Get all team projects
        $teamProjects = Project::whereIn('user_id', $accessibleUserIds)->get();

        // Get all team reports
        $teamReports = DPReport::whereIn('user_id', $accessibleUserIds)->get();

        // Projects by status
        $projectsByStatus = $teamProjects->groupBy('status')->map(function($group) {
            return $group->count();
        })->toArray();

        // Reports by status
        $reportsByStatus = $teamReports->groupBy('status')->map(function($group) {
            return $group->count();
        })->toArray();

        // Budget by project type (from approved projects)
        $approvedProjects = $teamProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
        $budgetByProjectType = [];

        foreach ($approvedProjects as $project) {
            $type = $project->project_type ?? 'Unknown';
            if (!isset($budgetByProjectType[$type])) {
                $budgetByProjectType[$type] = 0;
            }
            $budgetByProjectType[$type] += $project->amount_sanctioned ?? 0;
        }

        // Budget by center (from approved projects)
        $budgetByCenter = [];

        foreach ($approvedProjects as $project) {
            $center = $project->user->center ?? 'Unknown';
            if (!isset($budgetByCenter[$center])) {
                $budgetByCenter[$center] = 0;
            }
            $budgetByCenter[$center] += $project->amount_sanctioned ?? 0;
        }

        return [
            'projects_by_status' => $projectsByStatus,
            'reports_by_status' => $reportsByStatus,
            'budget_by_project_type' => $budgetByProjectType,
            'budget_by_center' => $budgetByCenter,
        ];
    }

    /**
     * Calculate center-wise performance
     */
    private function calculateCenterPerformance($provincial)
    {
        // Get all accessible user IDs
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        $centers = User::whereIn('id', $accessibleUserIds)
            ->whereIn('role', ['executor', 'applicant'])
            ->whereNotNull('center')
            ->distinct()
            ->pluck('center');

        $centerPerformance = [];

        foreach ($centers as $center) {
            $centerUsers = User::whereIn('id', $accessibleUserIds)
                ->where('center', $center)
                ->whereIn('role', ['executor', 'applicant'])
                ->pluck('id');

            $centerProjects = Project::whereIn('user_id', $centerUsers)->get();
            $approvedProjects = $centerProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);

            $centerBudget = $approvedProjects->sum('amount_sanctioned') ?? 0;
            $centerExpenses = 0;

            foreach ($approvedProjects as $project) {
                if ($project->reports) {
                    foreach ($project->reports as $report) {
                        if ($report->isApproved() && $report->accountDetails) {
                            $centerExpenses += $report->accountDetails->sum('total_expenses') ?? 0;
                        }
                    }
                }
            }

            $centerReports = DPReport::whereIn('user_id', $centerUsers)->get();
            $totalCenterReports = $centerReports->count();
            $approvedCenterReports = $centerReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count();
            $approvalRate = $totalCenterReports > 0 ? (($approvedCenterReports / $totalCenterReports) * 100) : 0;

            $centerPerformance[$center] = [
                'projects' => $centerProjects->count(),
                'budget' => $centerBudget,
                'expenses' => $centerExpenses,
                'reports' => $totalCenterReports,
                'approved_reports' => $approvedCenterReports,
                'total_reports' => $totalCenterReports,
            ];
        }

        return $centerPerformance;
    }

    /**
     * Calculate enhanced budget data for Team Budget Overview widget
     */
    private function calculateEnhancedBudgetData($provincial)
    {
        // Get all accessible user IDs
        $accessibleUserIds = $this->getAccessibleUserIds($provincial);

        // Get all approved projects
        $approvedProjects = Project::whereIn('user_id', $accessibleUserIds)
            ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
        ->with(['user', 'reports.accountDetails'])
        ->get();

        // Calculate totals
        $totalBudget = $approvedProjects->sum('amount_sanctioned') ?? 0;
        $totalExpenses = 0;

        foreach ($approvedProjects as $project) {
            if ($project->reports) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails) {
                        $totalExpenses += $report->accountDetails->sum('total_expenses') ?? 0;
                    }
                }
            }
        }

        $totalRemaining = $totalBudget - $totalExpenses;
        $utilization = $totalBudget > 0 ? (($totalExpenses / $totalBudget) * 100) : 0;

        // Budget by project type
        $byProjectType = [];
        foreach ($approvedProjects as $project) {
            $type = $project->project_type ?? 'Unknown';
            if (!isset($byProjectType[$type])) {
                $byProjectType[$type] = ['budget' => 0, 'expenses' => 0, 'remaining' => 0];
            }
            $byProjectType[$type]['budget'] += $project->amount_sanctioned ?? 0;

            $projectExpenses = 0;
            if ($project->reports) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails) {
                        $projectExpenses += $report->accountDetails->sum('total_expenses') ?? 0;
                    }
                }
            }
            $byProjectType[$type]['expenses'] += $projectExpenses;
            $byProjectType[$type]['remaining'] += ($project->amount_sanctioned ?? 0) - $projectExpenses;
        }

        // Budget by center
        $byCenter = [];
        foreach ($approvedProjects as $project) {
            $center = $project->user->center ?? 'Unknown';
            if (!isset($byCenter[$center])) {
                $byCenter[$center] = ['budget' => 0, 'expenses' => 0, 'remaining' => 0];
            }
            $byCenter[$center]['budget'] += $project->amount_sanctioned ?? 0;

            $projectExpenses = 0;
            if ($project->reports) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails) {
                        $projectExpenses += $report->accountDetails->sum('total_expenses') ?? 0;
                    }
                }
            }
            $byCenter[$center]['expenses'] += $projectExpenses;
            $byCenter[$center]['remaining'] += ($project->amount_sanctioned ?? 0) - $projectExpenses;
        }

        // Budget by team member
        $byTeamMember = [];
        foreach ($approvedProjects as $project) {
            $memberId = $project->user_id;
            $memberName = $project->user->name ?? 'Unknown';

            if (!isset($byTeamMember[$memberId])) {
                $byTeamMember[$memberId] = ['name' => $memberName, 'budget' => 0, 'expenses' => 0, 'remaining' => 0];
            }
            $byTeamMember[$memberId]['budget'] += $project->amount_sanctioned ?? 0;

            $projectExpenses = 0;
            if ($project->reports) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails) {
                        $projectExpenses += $report->accountDetails->sum('total_expenses') ?? 0;
                    }
                }
            }
            $byTeamMember[$memberId]['expenses'] += $projectExpenses;
            $byTeamMember[$memberId]['remaining'] += ($project->amount_sanctioned ?? 0) - $projectExpenses;
        }

        // Top projects by budget
        $topProjects = $approvedProjects->map(function($project) {
            $projectExpenses = 0;
            if ($project->reports) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails) {
                        $projectExpenses += $report->accountDetails->sum('total_expenses') ?? 0;
                    }
                }
            }

            return [
                'project_id' => $project->project_id,
                'title' => $project->project_title,
                'team_member' => $project->user->name ?? 'Unknown',
                'type' => $project->project_type ?? 'Unknown',
                'budget' => $project->amount_sanctioned ?? 0,
                'expenses' => $projectExpenses,
                'remaining' => ($project->amount_sanctioned ?? 0) - $projectExpenses,
            ];
        })->sortByDesc('budget')->take(10)->values();

        // Expense trends (monthly for last 6 months)
        $trends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            // Get all accessible user IDs
            $accessibleUserIds = $this->getAccessibleUserIds($provincial);

            $monthReports = DPReport::whereIn('user_id', $accessibleUserIds)
                ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->with('accountDetails')
            ->get();

            $monthExpenses = $monthReports->sum(function($report) {
                if (!$report->relationLoaded('accountDetails') || !$report->accountDetails) {
                    return 0;
                }
                return $report->accountDetails->sum('total_expenses') ?? 0;
            });

            $trends[] = [
                'period' => $month->format('M Y'),
                'expenses' => $monthExpenses,
            ];
        }

        return [
            'total' => [
                'budget' => $totalBudget,
                'expenses' => $totalExpenses,
                'remaining' => $totalRemaining,
                'utilization' => $utilization,
                'remaining_percentage' => $totalBudget > 0 ? (($totalRemaining / $totalBudget) * 100) : 0,
            ],
            'by_project_type' => $byProjectType,
            'by_center' => $byCenter,
            'by_team_member' => array_values($byTeamMember),
            'top_projects' => $topProjects,
            'trends' => $trends,
        ];
    }

    /**
     * Prepare center comparison data
     */
    private function prepareCenterComparisonData($provincial)
    {
        // This reuses centerPerformance but formats it for comparison widget
        $centerPerformance = $this->calculateCenterPerformance($provincial);

        // Add additional comparison metrics
        foreach ($centerPerformance as $center => &$data) {
            // Calculate additional metrics if needed
            $data['name'] = $center;
        }

        return $centerPerformance;
    }

    /**
     * Get centers map for all provinces from database
     * Returns array with province name (uppercase) as key and array of center names as value
     * Centers are sorted in ascending order
     */
    private function getCentersMap()
    {
        return Cache::remember('centers_map', now()->addHours(24), function () {
            $centersMap = [];

            $provinces = Province::active()->with(['activeCenters' => function ($query) {
                $query->orderBy('name', 'asc');
            }])->get();

            foreach ($provinces as $province) {
                $provinceKey = strtoupper($province->name);
                $centers = $province->activeCenters->pluck('name')->toArray();
                sort($centers); // Ensure ascending order
                $centersMap[$provinceKey] = $centers;
            }

            return $centersMap;
        });
    }
}
