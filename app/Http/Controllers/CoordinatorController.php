<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Reports\Monthly\ReportController;
use App\Models\OldProjects\Project;
use App\Models\ProjectComment;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Reports\Monthly\DPAccountDetail;
use App\Models\ReportComment;
use App\Models\User;
use App\Models\Province;
use App\Models\Center;
use App\Models\ActivityHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ProjectStatusService;
use App\Services\ReportStatusService;
use App\Services\NotificationService;
use App\Services\Budget\BudgetSyncService;
use App\Services\Budget\DerivedCalculationService;
use App\Constants\ProjectStatus;
use App\Http\Requests\Projects\ApproveProjectRequest;
use Carbon\Carbon;
use Exception;


class CoordinatorController extends Controller
{
    public function __construct(
        private readonly DerivedCalculationService $calculationService
    ) {
    }

    public function coordinatorDashboard(Request $request)
    {
        $coordinator = Auth::user();

        \Log::info('Coordinator Dashboard Request', [
            'province' => $request->get('province'),
            'center' => $request->get('center'),
            'role' => $request->get('role'),
            'parent_id' => $request->get('parent_id')
        ]);

        // First, get approved projects with comprehensive filtering
        $projectsQuery = Project::where('status', ProjectStatus::APPROVED_BY_COORDINATOR)->with('user');

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

        $projectTypes = Project::where('status', ProjectStatus::APPROVED_BY_COORDINATOR)->distinct()->pluck('project_type');

        // Get all projects for statistics (not just approved)
        $allProjectsQuery = Project::with('user');

        // Apply same filters for statistics
        if ($request->filled('province')) {
            $allProjectsQuery->whereHas('user', function($query) use ($request) {
                $query->where('province', $request->province);
            });
        }
        if ($request->filled('center')) {
            $allProjectsQuery->whereHas('user', function($query) use ($request) {
                $query->where('center', $request->center);
            });
        }
        if ($request->filled('role')) {
            $allProjectsQuery->whereHas('user', function($query) use ($request) {
                $query->where('role', $request->role);
            });
        }
        if ($request->filled('parent_id')) {
            $allProjectsQuery->whereHas('user', function($query) use ($request) {
                $query->where('parent_id', $request->parent_id);
            });
        }

        $allProjects = $allProjectsQuery->get();

        // Calculate project statistics
        $statistics = [
            'total_projects' => $allProjects->count(),
            'projects_by_status' => $allProjects->groupBy('status')->map->count(),
            'projects_by_type' => $allProjects->groupBy('project_type')->map->count(),
            'recent_projects' => $allProjects->sortByDesc('created_at')->take(5),
            'recent_activity' => $this->getRecentActivity($allProjects),
        ];

        \Log::info('Coordinator Dashboard Filter Options', [
            'selected_province' => $request->get('province'),
            'selected_center' => $request->get('center'),
            'selected_role' => $request->get('role'),
            'selected_parent_id' => $request->get('parent_id'),
            'available_provinces_count' => $provinces->count(),
            'available_centers_count' => $centers->count(),
            'available_parents_count' => $parents->count(),
            'total_projects' => $projects->count(),
            'projects_by_province' => $projects->groupBy('user.province')->map(fn ($group) => $group->count())->toArray(),
            'projects_with_amount_sanctioned' => $projects->where('amount_sanctioned', '>', 0)->count(),
            'projects_with_overall_budget' => $projects->where('overall_project_budget', '>', 0)->count(),
            'projects_with_budgets' => $projects->filter(function($p) { return $p->budgets && $p->budgets->count() > 0; })->count(),
            'projects_with_reports' => $projects->filter(function($p) { return $p->reports && $p->reports->count() > 0; })->count()
        ]);

        // Get Phase 1 widget data (with caching)
        $pendingApprovalsData = $this->getPendingApprovalsData();
        $provincialOverviewData = $this->getProvincialOverviewData();
        $systemPerformanceData = $this->getSystemPerformanceData();

        // Get Phase 2 widget data (with caching based on time range)
        $timeRange = $request->get('analytics_range', 30);
        $systemAnalyticsData = $this->getSystemAnalyticsData($timeRange);
        $systemActivityFeedData = $this->getSystemActivityFeedData(50);

        // Get Phase 3 widget data (with caching)
        // Pass filter parameters to budget overview (for filtering by province, center, project_type, provincial)
        $systemBudgetOverviewData = $this->getSystemBudgetOverviewData($request);
        $provinceComparisonData = $this->getProvinceComparisonData();
        $provincialManagementData = $this->getProvincialManagementData();
        $systemHealthData = $this->getSystemHealthData();

        return view('coordinator.index', compact(
            'budgetSummaries',
            'provinces',
            'centers',
            'roles',
            'parents',
            'projectTypes',
            'statistics',
            'allProjects',
            'pendingApprovalsData',
            'provincialOverviewData',
            'systemPerformanceData',
            'systemAnalyticsData',
            'systemActivityFeedData',
            'systemBudgetOverviewData',
            'provinceComparisonData',
            'provincialManagementData',
            'systemHealthData'
        ));
    }

    /**
     * Refresh dashboard cache (clear all dashboard-related cache)
     */
    public function refreshDashboard(Request $request)
    {
        try {
            // Clear all dashboard-related cache
            $this->invalidateDashboardCache();

            // Return success response
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Dashboard cache refreshed successfully.']);
            }

            return redirect()->route('coordinator.dashboard')
                ->with('success', 'Dashboard cache refreshed successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to refresh dashboard cache', [
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to refresh cache.'], 500);
            }

            return redirect()->route('coordinator.dashboard')
                ->with('error', 'Failed to refresh dashboard cache.');
        }
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
        $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
        $calc = app(\App\Services\Budget\DerivedCalculationService::class);
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
            $financials = $resolver->resolve($project);
            $projectBudget = (float) ($financials['opening_balance'] ?? 0);
            $totalExpenses = 0;
            if ($project->reports && $project->reports->count() > 0) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails && $report->accountDetails->count() > 0) {
                        $totalExpenses += $report->accountDetails->sum('total_expenses');
                    }
                }
            }
            $remainingBudget = $calc->calculateRemainingBalance($projectBudget, $totalExpenses);
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

    public function reportList(Request $request)
    {
        $coordinator = Auth::user();

        // Base query for reports - coordinators can see all reports in the system
        $reportsQuery = DPReport::with(['user.parent', 'project', 'accountDetails']);

        // Apply filters
        if ($request->filled('province')) {
            $reportsQuery->whereHas('user', function($query) use ($request) {
                $query->where('province', $request->province);
            });
        }

        if ($request->filled('provincial_id')) {
            // Filter by provincial (who forwarded the report)
            $reportsQuery->whereHas('user', function($query) use ($request) {
                $query->where('parent_id', $request->provincial_id);
            });
        }

        if ($request->filled('user_id')) {
            // Filter by executor/applicant (submitter)
            $reportsQuery->where('user_id', $request->user_id);
        }

        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        if ($request->filled('status')) {
            $reportsQuery->where('status', $request->status);
        }

        if ($request->filled('urgency')) {
            // Filter by urgency (will be applied after fetching)
        }

        if ($request->filled('center')) {
            $reportsQuery->whereHas('user', function($query) use ($request) {
                $query->where('center', $request->center);
            });
        }

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $reportsQuery->where(function($q) use ($searchTerm) {
                $q->where('report_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_id', 'like', '%' . $searchTerm . '%');
            });
        }

        // Get all reports
        $reports = $reportsQuery->get()
            ->map(function($report) {
                // Calculate days pending for pending reports
                if (in_array($report->status, [DPReport::STATUS_FORWARDED_TO_COORDINATOR, DPReport::STATUS_SUBMITTED_TO_PROVINCIAL])) {
                    $report->days_pending = $report->created_at->diffInDays(now());
                    $report->urgency = $report->days_pending > 7 ? 'urgent' :
                                      ($report->days_pending > 3 ? 'normal' : 'low');
                } else {
                    $report->days_pending = null;
                    $report->urgency = null;
                }
                return $report;
            });

        // Apply urgency filter if specified
        if ($request->filled('urgency')) {
            $reports = $reports->filter(function($report) use ($request) {
                return $report->urgency === $request->urgency;
            })->values();
        }

        // Priority sorting: urgent first, then by days pending (oldest first), then by created_at
        $reports = $reports->sortBy(function($report) {
            if ($report->urgency === 'urgent') {
                return [1, $report->days_pending ?? 999, $report->created_at->timestamp];
            } elseif ($report->urgency === 'normal') {
                return [2, $report->days_pending ?? 999, $report->created_at->timestamp];
            } else {
                return [3, $report->days_pending ?? 999, $report->created_at->timestamp];
            }
        })->values();

        // Pagination: Limit to 100 reports per page for performance
        $perPage = $request->get('per_page', 100);
        $currentPage = $request->get('page', 1);
        $totalReports = $reports->count();
        $paginatedReports = $reports->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Fetch filter options (cached for 5 minutes)
        $filterCacheKey = 'coordinator_report_list_filters';
        $filterOptions = Cache::remember($filterCacheKey, now()->addMinutes(5), function () {
            return [
                'provinces' => User::distinct()->whereNotNull('province')->pluck('province')->filter()->sort()->values(),
                'centers' => User::distinct()->whereNotNull('center')->where('center', '!=', '')->pluck('center')->filter()->sort()->values(),
                'users' => User::whereIn('role', ['executor', 'applicant'])->select('id', 'name', 'province', 'center', 'role')->get(),
                'provincials' => User::where('role', 'provincial')->select('id', 'name', 'province')->get(),
                'projectTypes' => DPReport::distinct()->whereNotNull('project_type')->pluck('project_type')->filter()->sort()->values(),
                'statuses' => array_keys(DPReport::$statusLabels),
            ];
        });

        // Create pagination metadata
        $paginationData = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $totalReports,
            'last_page' => ceil($totalReports / $perPage),
            'from' => (($currentPage - 1) * $perPage) + 1,
            'to' => min($currentPage * $perPage, $totalReports),
        ];

        // Extract filter options for compact()
        $provinces = $filterOptions['provinces'];
        $centers = $filterOptions['centers'];
        $users = $filterOptions['users'];
        $provincials = $filterOptions['provincials'];
        $projectTypes = $filterOptions['projectTypes'];
        $statuses = $filterOptions['statuses'];
        $reports = $paginatedReports;
        $pagination = $paginationData;

        // Return the ReportList view with the filtered reports, project types, etc.
        return view('coordinator.ReportList', compact(
            'reports',
            'coordinator',
            'provinces',
            'centers',
            'users',
            'provincials',
            'projectTypes',
            'statuses',
            'pagination'
        ));
    }

    public function projectList(Request $request)
    {
        $coordinator = Auth::user();

        // Base query for projects - coordinators can see ALL projects with ALL statuses
        $projectsQuery = Project::with(['user.parent', 'reports.accountDetails', 'budgets']);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $projectsQuery->where(function($q) use ($searchTerm) {
                $q->where('project_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_type', 'like', '%' . $searchTerm . '%')
                  ->orWhere('status', 'like', '%' . $searchTerm . '%');
            });
        }

        // Province filter
        if ($request->filled('province')) {
            $projectsQuery->whereHas('user', function($q) use ($request) {
                $q->where('province', $request->province);
            });
        }

        // Provincial filter (parent_id)
        if ($request->filled('provincial_id')) {
            $projectsQuery->whereHas('user', function($q) use ($request) {
                $q->where('parent_id', $request->provincial_id);
            });
        }

        // Executor/Applicant filter
        if ($request->filled('user_id')) {
            $projectsQuery->where('user_id', $request->user_id);
        }

        // Center filter
        if ($request->filled('center')) {
            $projectsQuery->whereHas('user', function($q) use ($request) {
                $q->where('center', $request->center);
            });
        }

        // Project type filter
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        // Multiple project types filter
        if ($request->filled('project_types')) {
            $projectTypesArray = is_array($request->project_types)
                ? $request->project_types
                : explode(',', $request->project_types);
            $projectsQuery->whereIn('project_type', $projectTypesArray);
        }

        // Status filter (now shows all statuses, but can filter)
        if ($request->filled('status')) {
            $projectsQuery->where('status', $request->status);
        }

        // Multiple statuses filter
        if ($request->filled('statuses')) {
            $statusesArray = is_array($request->statuses)
                ? $request->statuses
                : explode(',', $request->statuses);
            $projectsQuery->whereIn('status', $statusesArray);
        }

        // Date range filters
        if ($request->filled('start_date')) {
            $projectsQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $projectsQuery->whereDate('created_at', '<=', $request->end_date);
        }

        // Get total count before pagination
        $totalProjects = $projectsQuery->count();

        // Apply sorting at query level for better performance
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Apply sorting to query if it's a direct column
        if (in_array($sortBy, ['created_at', 'project_id', 'project_title'])) {
            $projectsQuery->orderBy($sortBy, $sortOrder);
        } else {
            // Default sorting
            $projectsQuery->orderBy('created_at', $sortOrder);
        }

        // Pagination: Limit to 100 projects per page for performance
        $perPage = $request->get('per_page', 100);
        $currentPage = $request->get('page', 1);

        $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
        $calc = app(\App\Services\Budget\DerivedCalculationService::class);
        // Get paginated projects
        $projects = $projectsQuery->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function($project) use ($resolver, $calc) {
                $financials = $resolver->resolve($project);
                $projectBudget = (float) ($financials['opening_balance'] ?? 0);

                // Calculate expenses from approved reports (optimized - use direct query instead of loading all)
                $projectApprovedReportIds = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                    ->where('project_id', $project->project_id)
                    ->pluck('report_id');

                $totalExpenses = DPAccountDetail::whereIn('report_id', $projectApprovedReportIds)
                    ->sum('total_expenses') ?? 0;

                $budgetUtilization = $calc->calculateUtilization($totalExpenses, $projectBudget);
                $remainingBudget = $calc->calculateRemainingBalance($projectBudget, $totalExpenses);

                // Health indicator based on utilization
                $healthIndicator = 'good';
                if ($budgetUtilization >= 90) {
                    $healthIndicator = 'critical';
                } elseif ($budgetUtilization >= 75) {
                    $healthIndicator = 'warning';
                } elseif ($budgetUtilization >= 50) {
                    $healthIndicator = 'moderate';
                }

                $project->calculated_budget = $projectBudget;
                $project->calculated_expenses = $totalExpenses;
                $project->calculated_remaining = $remainingBudget;
                $project->budget_utilization = round($budgetUtilization, 2);
                $project->health_indicator = $healthIndicator;
                $project->reports_count = $project->reports ? $project->reports->count() : 0;
                $project->approved_reports_count = $projectApprovedReportIds->count();

                return $project;
            });

        // Apply additional sorting for calculated fields (after fetching)
        if ($sortBy === 'budget_utilization') {
            $projects = $projects->sortBy(function($project) use ($sortOrder) {
                return $project->budget_utilization;
            }, SORT_REGULAR, $sortOrder === 'desc')->values();
        }

        // Fetch filter options (cached for 5 minutes)
        $filterCacheKey = 'coordinator_project_list_filters';
        $filterOptions = Cache::remember($filterCacheKey, now()->addMinutes(5), function () {
            return [
                'provinces' => User::distinct()->whereNotNull('province')->pluck('province')->filter()->sort()->values(),
                'centers' => User::distinct()->whereNotNull('center')->where('center', '!=', '')->pluck('center')->filter()->sort()->values(),
                'users' => User::whereIn('role', ['executor', 'applicant'])->select('id', 'name', 'province', 'center', 'role')->get(),
                'provincials' => User::where('role', 'provincial')->select('id', 'name', 'province')->get(),
                'projectTypes' => Project::distinct()->whereNotNull('project_type')->pluck('project_type')->filter()->sort()->values(),
                'statuses' => array_keys(\App\Models\OldProjects\Project::$statusLabels),
            ];
        });

        // Create pagination metadata
        $paginationData = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $totalProjects,
            'last_page' => ceil($totalProjects / $perPage),
            'from' => (($currentPage - 1) * $perPage) + 1,
            'to' => min($currentPage * $perPage, $totalProjects),
        ];

        // Extract filter options for compact()
        $provinces = $filterOptions['provinces'];
        $centers = $filterOptions['centers'];
        $users = $filterOptions['users'];
        $provincials = $filterOptions['provincials'];
        $projectTypes = $filterOptions['projectTypes'];
        $statuses = $filterOptions['statuses'];
        $pagination = $paginationData;

        // Get filter presets (stored in session for now, can be moved to database later)
        $filterPresets = session('project_filter_presets', []);

        return view('coordinator.ProjectList', compact(
            'projects',
            'coordinator',
            'projectTypes',
            'users',
            'provinces',
            'centers',
            'provincials',
            'statuses',
            'filterPresets',
            'pagination'
        ));
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
        // Get provinces from database
        $provinces = Province::active()->orderBy('name')->get();

        // Get centers map from database
        $centersMap = $this->getCentersMap();

        return view('coordinator.createProvincial', compact('provinces', 'centersMap'));
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
            'province' => 'required|exists:provinces,name',
            'status' => 'required|string|max:50',
        ]);

        // Get province and center IDs from database
        $province = Province::where('name', $request->province)->first();
        $provinceId = $province ? $province->id : null;

        $centerId = null;
        if ($request->filled('center') && $provinceId) {
            $center = Center::where('province_id', $provinceId)
                ->whereRaw('UPPER(name) = ?', [strtoupper($request->center)])
                ->first();
            $centerId = $center ? $center->id : null;
        }

        User::create([
            'parent_id' => auth()->user()->id,
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'center' => $request->center,
            'center_id' => $centerId,
            'address' => $request->address,
            'role' => $request->role,
            'province' => $request->province,
            'province_id' => $provinceId,
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

        // Get provinces from database
        $provinces = Province::active()->orderBy('name')->get();

        // Get centers map from database
        $centersMap = $this->getCentersMap();

        return view('coordinator.editProvincial', compact('provincial', 'provinces', 'centersMap'));
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
            'province' => 'required|exists:provinces,name',
            'status' => 'required|string|max:50',
        ]);

        // Get province and center IDs from database
        $province = Province::where('name', $request->province)->first();
        $provinceId = $province ? $province->id : null;

        $centerId = null;
        if ($request->filled('center') && $provinceId) {
            $center = Center::where('province_id', $provinceId)
                ->whereRaw('UPPER(name) = ?', [strtoupper($request->center)])
                ->first();
            $centerId = $center ? $center->id : null;
        }

        $provincial = User::findOrFail($id);
        $provincial->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'center' => $request->center,
            'center_id' => $centerId,
            'address' => $request->address,
            'role' => $request->role,
            'province' => $request->province,
            'province_id' => $provinceId,
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
public function revertToProvincial(Request $request, $project_id)
{
    Log::info('Coordinator revertToProvincial: start', [
        'project_id' => $project_id,
        'user_id' => auth()->id(),
        'user_role' => auth()->user()?->role,
        'has_revert_reason' => $request->has('revert_reason'),
    ]);

    $project = Project::where('project_id', $project_id)->firstOrFail();
    $coordinator = auth()->user();

    Log::info('Coordinator revertToProvincial: project loaded', [
        'project_id' => $project->project_id,
        'project_status' => $project->status,
    ]);

    $request->validate([
        'revert_reason' => 'required|string|max:1000',
    ]);

    $reason = $request->input('revert_reason');
    Log::info('Coordinator revertToProvincial: validation passed', ['project_id' => $project_id]);

    try {
        Log::info('Coordinator revertToProvincial: calling ProjectStatusService::revertByCoordinator', ['project_id' => $project_id]);
        ProjectStatusService::revertByCoordinator($project, $coordinator, $reason);
        Log::info('Coordinator revertToProvincial: revertByCoordinator succeeded', ['project_id' => $project_id, 'new_status' => $project->fresh()->status]);

        // Notify executor about revert
        $executor = $project->user;
        if ($executor) {
            Log::info('Coordinator revertToProvincial: notifying executor', ['project_id' => $project_id, 'executor_id' => $executor->id]);
            NotificationService::notifyRevert(
                $executor,
                'project',
                $project->project_id,
                "Project {$project->project_id}",
                $reason
            );
            Log::info('Coordinator revertToProvincial: notifyRevert done', ['project_id' => $project_id]);
        } else {
            Log::warning('Coordinator revertToProvincial: no executor (project.user) to notify', ['project_id' => $project_id]);
        }

        $this->invalidateDashboardCache();
        Log::info('Coordinator revertToProvincial: success, redirecting', ['project_id' => $project_id]);
        return redirect()->back()->with('success', 'Project reverted to Provincial.');
    } catch (Exception $e) {
        Log::error('Coordinator revertToProvincial: exception', [
            'project_id' => $project_id,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return redirect()->back()
            ->withErrors(['error' => $e->getMessage()])
            ->withInput($request->only('revert_reason'));
    }
}

public function approveProject(ApproveProjectRequest $request, $project_id)
{
    $validated = $request->validated();
    Log::info('Coordinator approveProject: start (ApproveProjectRequest passed)', [
        'project_id' => $project_id,
        'user_id' => auth()->id(),
        'user_role' => auth()->user()?->role,
        'commencement_month' => $validated['commencement_month'] ?? null,
        'commencement_year' => $validated['commencement_year'] ?? null,
    ]);

    $project = Project::where('project_id', $project_id)->with('budgets')->firstOrFail();
    $coordinator = auth()->user();

    Log::info('Coordinator approveProject: project loaded', [
        'project_id' => $project->project_id,
        'project_status' => $project->status,
        'budgets_count' => $project->budgets ? $project->budgets->count() : 0,
    ]);

    // Phase 2: Sync project-level budget fields before approval so validation/computation see correct data
    app(BudgetSyncService::class)->syncBeforeApproval($project);
    $project->refresh();

    // Create commencement date (validation already ensures it's not in the past)
    $commencementDate = Carbon::create(
        $validated['commencement_year'],
        $validated['commencement_month'],
        1
    )->startOfMonth();

    // Update commencement date before approval
    $project->commencement_month = $validated['commencement_month'];
    $project->commencement_year = $validated['commencement_year'];
    $project->commencement_month_year = $commencementDate->format('Y-m-d');

    try {
        Log::info('Coordinator approveProject: calling ProjectStatusService::approve', ['project_id' => $project_id]);
        ProjectStatusService::approve($project, $coordinator);
        Log::info('Coordinator approveProject: approve succeeded', ['project_id' => $project_id, 'new_status' => $project->fresh()->status]);
    } catch (Exception $e) {
        Log::error('Coordinator approveProject: ProjectStatusService::approve exception', [
            'project_id' => $project_id,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return redirect()->back()
            ->withErrors(['error' => $e->getMessage()])
            ->withInput();
    }

    // Get financial values from resolver (no inline arithmetic)
    $financials = app(\App\Domain\Budget\ProjectFinancialResolver::class)->resolve($project);
    $overallBudget = (float) ($financials['overall_project_budget'] ?? 0);
    $amountForwarded = (float) ($financials['amount_forwarded'] ?? 0);
    $localContribution = (float) ($financials['local_contribution'] ?? 0);
    $combinedContribution = $amountForwarded + $localContribution;
    $amountSanctioned = (float) ($financials['amount_sanctioned'] ?? 0);
    $openingBalance = (float) ($financials['opening_balance'] ?? 0);

    Log::info('Coordinator approveProject: budget check', [
        'project_id' => $project_id,
        'overall_project_budget' => $overallBudget,
        'amount_forwarded' => $amountForwarded,
        'local_contribution' => $localContribution,
        'combined_contribution' => $combinedContribution,
    ]);

    // Validate: combined contribution cannot exceed overall budget
    if ($combinedContribution > $overallBudget) {
        Log::warning('Coordinator approveProject: budget validation failed (combined > overall)', [
            'project_id' => $project_id,
            'combined_contribution' => $combinedContribution,
            'overall_budget' => $overallBudget,
        ]);
        return redirect()->back()
            ->with('error', 'Cannot approve project: (Amount Forwarded + Local Contribution) of Rs. ' . number_format($combinedContribution, 2) . ' exceeds Overall Project Budget (Rs. ' . number_format($overallBudget, 2) . '). Please ask the executor to correct this.');
    }

    // Update project with resolver values
    $project->amount_sanctioned = $amountSanctioned;
    $project->opening_balance = $openingBalance;
    $project->save();

    Log::info('Coordinator approveProject: budget saved', [
        'project_id' => $project_id,
        'amount_sanctioned' => $amountSanctioned,
        'opening_balance' => $openingBalance,
    ]);

    // Notify executor about approval
    $executor = $project->user;
    if ($executor) {
        Log::info('Coordinator approveProject: notifying executor', ['project_id' => $project_id, 'executor_id' => $executor->id]);
        NotificationService::notifyApproval(
            $executor,
            'project',
            $project->project_id,
            "Project {$project->project_id}"
        );
        Log::info('Coordinator approveProject: notifyApproval done', ['project_id' => $project_id]);
    } else {
        Log::warning('Coordinator approveProject: no executor (project.user) to notify', ['project_id' => $project_id]);
    }

    $this->invalidateDashboardCache();

    Log::info('Coordinator approveProject: success', [
        'project_id' => $project->project_id,
        'project_title' => $project->project_title,
        'coordinator_id' => $coordinator->id,
        'commencement_month' => $project->commencement_month,
        'commencement_year' => $project->commencement_year,
        'overall_project_budget' => $overallBudget,
        'amount_forwarded' => $amountForwarded,
        'local_contribution' => $localContribution,
        'amount_sanctioned' => $amountSanctioned,
        'opening_balance' => $openingBalance,
    ]);

    // Return success message with budget breakdown
    return redirect()->back()->with('success',
        'Project approved successfully.<br>' .
        '<strong>Budget Summary:</strong><br>' .
        'Overall Budget: Rs. ' . number_format($overallBudget, 2) . '<br>' .
        'Amount Forwarded: Rs. ' . number_format($amountForwarded, 2) . '<br>' .
        'Local Contribution: Rs. ' . number_format($localContribution, 2) . '<br>' .
        'Amount Sanctioned: Rs. ' . number_format($amountSanctioned, 2) . '<br>' .
        'Opening Balance: Rs. ' . number_format($openingBalance, 2) . '<br>' .
        '<strong>Commencement Date:</strong> ' .
        date('F Y', mktime(0, 0, 0, $project->commencement_month, 1, $project->commencement_year))
    );
}

public function rejectProject(Request $request, $project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $coordinator = auth()->user();

    if($coordinator->role !== 'coordinator' || !ProjectStatus::isForwardedToCoordinator($project->status)) {
        abort(403, 'Unauthorized action.');
    }

    $previousStatus = $project->status;
    $project->status = ProjectStatus::REJECTED_BY_COORDINATOR;
    $project->save();

    // Log status change
    \App\Services\ProjectStatusService::logStatusChange($project, $previousStatus, ProjectStatus::REJECTED_BY_COORDINATOR, $coordinator);

    // Notify executor about rejection
    $executor = $project->user;
    if ($executor) {
        $reason = $request->input('rejection_reason', 'No reason provided');
        NotificationService::notifyRejection(
            $executor,
            'project',
            $project->project_id,
            "Project {$project->project_id}",
            $reason
        );
    }

    // Invalidate cache after project rejection
    $this->invalidateDashboardCache();

    return redirect()->back()->with('success', 'Project rejected successfully.');
}

public function projectBudgets(Request $request)
{
    $coordinator = Auth::user();

    // First, get approved projects (coordinators can see all approved projects)
    $projectsQuery = Project::where('status', ProjectStatus::APPROVED_BY_COORDINATOR)->with('user');

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

    $projectTypes = Project::where('status', ProjectStatus::APPROVED_BY_COORDINATOR)->distinct()->pluck('project_type');

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

    /**
     * Get recent activity for dashboard
     *
     * @param \Illuminate\Database\Eloquent\Collection $projects
     * @return array
     */
    private function getRecentActivity($projects)
    {
        $activities = [];

        // Recent project creations
        $recentProjects = $projects->sortByDesc('created_at')->take(5);
        foreach ($recentProjects as $project) {
            $activities[] = [
                'type' => 'project_created',
                'message' => 'Project ' . $project->project_id . ' created',
                'project_title' => $project->project_title,
                'project_id' => $project->project_id,
                'timestamp' => $project->created_at,
                'user' => $project->user->name ?? 'Unknown',
            ];
        }

        // Recent status changes (if status history exists)
        $recentStatusChanges = \App\Models\ProjectStatusHistory::with('project', 'changedBy')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        foreach ($recentStatusChanges as $statusChange) {
            $activities[] = [
                'type' => 'status_changed',
                'message' => 'Project ' . $statusChange->project_id . ' status changed to ' . $statusChange->new_status,
                'project_title' => $statusChange->project->project_title ?? 'N/A',
                'project_id' => $statusChange->project_id,
                'timestamp' => $statusChange->created_at,
                'user' => $statusChange->changedBy->name ?? 'System',
                'old_status' => $statusChange->old_status,
                'new_status' => $statusChange->new_status,
            ];
        }

        // Sort by timestamp and return top 10
        usort($activities, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return array_slice($activities, 0, 10);
    }

    /**
     * Get pending approvals data for widget (with caching - 2 minutes TTL for frequent updates)
     */
    private function getPendingApprovalsData()
    {
        $cacheKey = 'coordinator_pending_approvals_data';

        return Cache::remember($cacheKey, now()->addMinutes(2), function () {
            // Get pending reports awaiting coordinator approval
            $pendingReports = DPReport::where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)
                ->with(['user', 'user.parent', 'project'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function($report) {
                    $report->days_pending = $report->created_at->diffInDays(now());
                    $report->urgency = $report->days_pending > 7 ? 'urgent' :
                                      ($report->days_pending > 3 ? 'normal' : 'low');
                    $report->provincial = $report->user->parent; // Provincial who forwarded
                    return $report;
                })
                ->sortByDesc(function($report) {
                    // Sort by urgency (urgent first), then by days pending
                    return [
                        $report->urgency === 'urgent' ? 3 : ($report->urgency === 'normal' ? 2 : 1),
                        $report->days_pending
                    ];
                })
                ->values();

            // Get pending projects awaiting coordinator approval
            $pendingProjects = Project::where('status', ProjectStatus::FORWARDED_TO_COORDINATOR)
                ->with(['user', 'user.parent'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function($project) {
                    $project->days_pending = $project->created_at->diffInDays(now());
                    $project->urgency = $project->days_pending > 7 ? 'urgent' :
                                        ($project->days_pending > 3 ? 'normal' : 'low');
                    $project->provincial = $project->user->parent; // Provincial who forwarded
                    return $project;
                })
                ->sortByDesc(function($project) {
                    // Sort by urgency (urgent first), then by days pending
                    return [
                        $project->urgency === 'urgent' ? 3 : ($project->urgency === 'normal' ? 2 : 1),
                        $project->days_pending
                    ];
                })
                ->values();

            // Calculate counts for both reports and projects
            $urgentReportsCount = $pendingReports->where('urgency', 'urgent')->count();
            $normalReportsCount = $pendingReports->where('urgency', 'normal')->count();
            $lowReportsCount = $pendingReports->where('urgency', 'low')->count();

            $urgentProjectsCount = $pendingProjects->where('urgency', 'urgent')->count();
            $normalProjectsCount = $pendingProjects->where('urgency', 'normal')->count();
            $lowProjectsCount = $pendingProjects->where('urgency', 'low')->count();

            $totalPendingCount = $pendingReports->count() + $pendingProjects->count();
            $totalUrgentCount = $urgentReportsCount + $urgentProjectsCount;
            $totalNormalCount = $normalReportsCount + $normalProjectsCount;
            $totalLowCount = $lowReportsCount + $lowProjectsCount;

            // Group by province for reports
            $pendingByProvince = $pendingReports->groupBy(function($report) {
                return $report->user->province ?? 'Unknown';
            })->map(function($reports) {
                return [
                    'count' => $reports->count(),
                    'urgent' => $reports->where('urgency', 'urgent')->count(),
                ];
            });

            return [
                'pending_reports' => $pendingReports,
                'pending_projects' => $pendingProjects,
                'pending_reports_count' => $pendingReports->count(),
                'pending_projects_count' => $pendingProjects->count(),
                'total_pending' => $totalPendingCount,
                'urgent_count' => $urgentReportsCount,
                'normal_count' => $normalReportsCount,
                'low_count' => $lowReportsCount,
                'urgent_projects_count' => $urgentProjectsCount,
                'normal_projects_count' => $normalProjectsCount,
                'low_projects_count' => $lowProjectsCount,
                'total_urgent_count' => $totalUrgentCount,
                'total_normal_count' => $totalNormalCount,
                'total_low_count' => $totalLowCount,
                'by_province' => $pendingByProvince,
            ];
        });
    }

    /**
     * Get provincial overview data for widget (with caching - 5 minutes TTL)
     */
    private function getProvincialOverviewData()
    {
        $cacheKey = 'coordinator_provincial_overview_data';

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            // Get all provincials with counts
            $provincials = User::where('role', 'provincial')
                ->withCount([
                    'children' => function($query) {
                        $query->whereIn('role', ['executor', 'applicant']);
                    },
                    'projects' => function($query) {
                        $query->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
                    }
                ])
                ->get()
                ->map(function($provincial) {
                    // Get team reports count (optimized with single query)
                    $teamUserIds = User::where('parent_id', $provincial->id)
                        ->whereIn('role', ['executor', 'applicant'])
                        ->pluck('id');

                    // Use direct count queries instead of loading all reports
                    $provincial->team_reports_pending = DPReport::whereIn('user_id', $teamUserIds)
                        ->where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)
                        ->count();

                    $provincial->team_reports_approved = DPReport::whereIn('user_id', $teamUserIds)
                        ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                        ->count();

                    // Get last activity (latest report submission or project update)
                    $latestReport = DPReport::whereIn('user_id', $teamUserIds)
                        ->orderBy('created_at', 'desc')
                        ->select('created_at')
                        ->first();

                    $latestProject = Project::whereIn('user_id', $teamUserIds)
                        ->orderBy('updated_at', 'desc')
                        ->select('updated_at')
                        ->first();

                    $provincial->last_activity = null;
                    if ($latestReport && $latestProject) {
                        $provincial->last_activity = $latestReport->created_at > $latestProject->updated_at
                            ? $latestReport->created_at
                            : $latestProject->updated_at;
                    } elseif ($latestReport) {
                        $provincial->last_activity = $latestReport->created_at;
                    } elseif ($latestProject) {
                        $provincial->last_activity = $latestProject->updated_at;
                    }

                    return $provincial;
                });

            // Calculate summary statistics
            $totalProvincials = $provincials->count();
            $activeProvincials = $provincials->where('status', 'active')->count();
            $inactiveProvincials = $provincials->where('status', 'inactive')->count();

            $totalTeamMembers = $provincials->sum('children_count');
            $totalProjects = $provincials->sum('projects_count');
            $totalPendingReports = $provincials->sum('team_reports_pending');
            $totalApprovedReports = $provincials->sum('team_reports_approved');

            return [
                'provincials' => $provincials->take(12), // Show top 12 in widget
                'total_provincials' => $totalProvincials,
                'active_provincials' => $activeProvincials,
                'inactive_provincials' => $inactiveProvincials,
                'total_team_members' => $totalTeamMembers,
                'total_projects' => $totalProjects,
                'total_pending_reports' => $totalPendingReports,
                'total_approved_reports' => $totalApprovedReports,
                'average_projects_per_provincial' => $totalProvincials > 0 ? round($totalProjects / $totalProvincials, 1) : 0,
                'average_reports_per_provincial' => $totalProvincials > 0 ? round($totalApprovedReports / $totalProvincials, 1) : 0,
            ];
        });
    }

    /**
     * Get system performance summary data for widget (with caching - 10 minutes TTL)
     */
    private function getSystemPerformanceData()
    {
        $cacheKey = 'coordinator_system_performance_data';

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
            $calc = $this->calculationService;

            // Load all projects and reports once with eager loading
            $systemProjects = Project::with(['user', 'user.parent', 'budgets'])->get();
            $systemReports = DPReport::with(['user'])->get();

            $resolvedFinancials = [];
            foreach ($systemProjects as $project) {
                $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
            }

        // Calculate system-wide metrics
        $approvedProjects = $systemProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
        $totalBudget = $approvedProjects->sum(fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0));

        // Calculate total expenses from approved reports
        $approvedReportIds = $systemReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->pluck('report_id');
        $totalExpenses = (float) (DPAccountDetail::whereIn('report_id', $approvedReportIds)->sum('total_expenses') ?? 0);

        $budgetUtilization = $calc->calculateUtilization($totalExpenses, $totalBudget);

        $approvalRate = $systemReports->count() > 0 ?
            ($systemReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count() / $systemReports->count()) * 100 : 0;

        // Projects by status
        $projectsByStatus = $systemProjects->groupBy('status')->map->count();

        // Reports by status
        $reportsByStatus = $systemReports->groupBy('status')->map->count();

        // Active users count
        $activeUsers = User::where('status', 'active')->count();

        // Province-wise breakdown - group in memory
        $provinceMetrics = [];
        $projectsByProvince = $systemProjects->groupBy(fn($p) => $p->user->province ?? null);
        $reportsByProvince = $systemReports->groupBy(fn($r) => $r->user->province ?? null);
        $provinces = $projectsByProvince->keys()->merge($reportsByProvince->keys())->unique()->filter();

        foreach ($provinces as $province) {
            $provinceProjects = $projectsByProvince->get($province, collect());
            $provinceReports = $reportsByProvince->get($province, collect());

            $provinceApprovedProjects = $provinceProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
            $provinceBudget = $provinceApprovedProjects->sum(fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0));

            // Calculate province expenses from account details directly
            $provinceApprovedReportIds = $provinceReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->pluck('report_id');
            $provinceExpenses = (float) (DPAccountDetail::whereIn('report_id', $provinceApprovedReportIds)->sum('total_expenses') ?? 0);

            $provinceMetrics[$province] = [
                'projects' => $provinceProjects->count(),
                'reports' => $provinceReports->count(),
                'budget' => $provinceBudget,
                'expenses' => $provinceExpenses,
                'utilization' => round($calc->calculateUtilization($provinceExpenses, $provinceBudget), 2),
                'approval_rate' => $provinceReports->count() > 0 ?
                    ($provinceReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count() / $provinceReports->count()) * 100 : 0,
            ];
        }

        return [
            'total_projects' => $systemProjects->count(),
            'total_reports' => $systemReports->count(),
            'total_budget' => $totalBudget,
            'total_expenses' => $totalExpenses,
            'total_remaining' => $calc->calculateRemainingBalance($totalBudget, $totalExpenses),
            'budget_utilization' => round($budgetUtilization, 2),
            'approval_rate' => round($approvalRate, 2),
            'active_users' => $activeUsers,
            'projects_by_status' => $projectsByStatus,
            'reports_by_status' => $reportsByStatus,
            'province_metrics' => $provinceMetrics,
        ];
        });
    }

    /**
     * Get system analytics data for charts (time-based) (with caching - 15 minutes TTL)
     */
    private function getSystemAnalyticsData($timeRange = 30)
    {
        $cacheKey = "coordinator_system_analytics_data_{$timeRange}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($timeRange) {
            $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
            $calc = $this->calculationService;

            $endDate = now();
            $startDate = now()->subDays($timeRange);

            // Load all projects and reports once
            $allProjects = Project::with(['user', 'user.parent', 'budgets'])->get();
            $allApprovedProjects = $allProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
            $allReports = DPReport::with(['user'])->get();

            $resolvedFinancials = [];
            foreach ($allProjects as $project) {
                $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
            }

            $projectsByProvince = $allProjects->groupBy(fn($p) => $p->user->province ?? 'Unknown');
            $approvedProjectsByProvince = $allApprovedProjects->groupBy(fn($p) => $p->user->province ?? 'Unknown');
            $projectsByType = $allApprovedProjects->groupBy('project_type');
            $provinces = $projectsByProvince->keys()->filter(fn($p) => $p !== 'Unknown')->values();
            $reportsByProvince = $allReports->groupBy(fn($r) => $r->user->province ?? 'Unknown');

            // Budget Utilization Timeline (monthly) - filter in memory
            $budgetUtilizationTimeline = [];
            $months = [];
            $current = $startDate->copy()->startOfMonth();
            while ($current <= $endDate) {
                $monthEnd = $current->copy()->endOfMonth();

                $projectsByMonth = $allApprovedProjects->filter(fn($p) => $p->created_at <= $monthEnd);
                $budgetByMonth = $projectsByMonth->sum(fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0));

                $reportsByMonth = $allReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                    ->filter(fn($r) => $r->created_at <= $monthEnd)
                    ->pluck('report_id');
                $expensesByMonth = (float) (DPAccountDetail::whereIn('report_id', $reportsByMonth)->sum('total_expenses') ?? 0);

                $utilization = $calc->calculateUtilization($expensesByMonth, $budgetByMonth);

                $budgetUtilizationTimeline[] = [
                    'month' => $current->format('M Y'),
                    'utilization' => round($utilization, 2)
                ];

                $months[] = $current->format('M Y');
                $current->addMonth();
            }

            // Budget Distribution by Province - use pre-grouped
            $provinceBudgets = $approvedProjectsByProvince->map(fn($projects) =>
                $projects->sum(fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0))
            )->filter(fn($_, $province) => $province !== 'Unknown')->toArray();

            // Budget Distribution by Project Type - use pre-grouped
            $typeBudgets = $projectsByType->map(fn($projects) =>
                $projects->sum(fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0))
            )->toArray();

        // Expense Trends Over Time (monthly) - filter reports in memory
        $expenseTrends = [];
        $current = $startDate->copy()->startOfMonth();
        while ($current <= $endDate) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $reportsInMonth = $allReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                ->filter(fn($r) => $r->created_at >= $monthStart && $r->created_at <= $monthEnd)
                ->pluck('report_id');
            $expensesInMonth = DPAccountDetail::whereIn('report_id', $reportsInMonth)->sum('total_expenses') ?? 0;

            $expenseTrends[] = [
                'month' => $current->format('M Y'),
                'expenses' => $expensesInMonth
            ];

            $current->addMonth();
        }

        // Approval Rate Trends (monthly) - filter reports in memory
        $approvalRateTrends = [];
        $current = $startDate->copy()->startOfMonth();
        while ($current <= $endDate) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $reportsInMonth = $allReports->filter(fn($r) => $r->created_at >= $monthStart && $r->created_at <= $monthEnd);
            $approvedInMonth = $reportsInMonth->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count();
            $totalInMonth = $reportsInMonth->count();

            $rate = $totalInMonth > 0 ? ($approvedInMonth / $totalInMonth) * 100 : 0;

            $approvalRateTrends[] = [
                'month' => $current->format('M Y'),
                'rate' => round($rate, 2)
            ];

            $current->addMonth();
        }

        // Report Submission Timeline (monthly) - filter reports in memory
        $reportSubmissionTimeline = [];
        $current = $startDate->copy()->startOfMonth();
        while ($current <= $endDate) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $reportsInMonth = $allReports->filter(fn($r) => $r->created_at >= $monthStart && $r->created_at <= $monthEnd);

            $reportSubmissionTimeline[] = [
                'month' => $current->format('M Y'),
                'approved' => $reportsInMonth->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count(),
                'pending' => $reportsInMonth->where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)->count(),
                'reverted' => $reportsInMonth->where('status', DPReport::STATUS_REVERTED_BY_COORDINATOR)->count(),
            ];

            $current->addMonth();
        }

        // Province Comparison Data - use pre-grouped
        $provinceComparison = [];
        foreach ($provinces as $province) {
            $provinceProjects = $projectsByProvince->get($province, collect());
            $provinceReports = $reportsByProvince->get($province, collect());
            $provinceApprovedProjects = $approvedProjectsByProvince->get($province, collect());

            $provinceBudget = $provinceApprovedProjects->sum(fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0));

            $provinceApprovedReportIds = $provinceReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->pluck('report_id');
            $provinceExpenses = (float) (DPAccountDetail::whereIn('report_id', $provinceApprovedReportIds)->sum('total_expenses') ?? 0);

            $provinceComparison[$province] = [
                'projects' => $provinceProjects->count(),
                'budget' => $provinceBudget,
                'expenses' => $provinceExpenses,
                'approval_rate' => $provinceReports->count() > 0 ?
                    ($provinceReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count() / $provinceReports->count()) * 100 : 0,
            ];
        }

        return [
            'budget_utilization_timeline' => $budgetUtilizationTimeline,
            'budget_by_province' => $provinceBudgets,
            'budget_by_project_type' => $typeBudgets,
            'expense_trends' => $expenseTrends,
            'approval_rate_trends' => $approvalRateTrends,
            'report_submission_timeline' => $reportSubmissionTimeline,
            'province_comparison' => $provinceComparison,
        ];
        });
    }

    /**
     * Get system activity feed data for widget (with caching - 2 minutes TTL for frequent updates)
     */
    private function getSystemActivityFeedData($limit = 50)
    {
        $cacheKey = "coordinator_system_activity_feed_data_{$limit}";

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($limit) {
            // Get activities directly with limit for better performance
            $activities = ActivityHistory::with(['changedBy', 'project', 'report'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($activity) {
                    // Format activity for display
                    $activity->formatted_message = $this->formatActivityMessage($activity);
                    $activity->icon = $this->getActivityIcon($activity);
                    $activity->color = $this->getActivityColor($activity);
                    return $activity;
                })
                ->values();

            // Group by date
            $groupedActivities = $activities->groupBy(function($activity) {
                return $activity->created_at->format('Y-m-d');
            });

            return [
                'activities' => $activities,
                'grouped_activities' => $groupedActivities,
                'total_count' => $activities->count(),
            ];
        });
    }

    /**
     * Format activity message for display
     */
    private function formatActivityMessage($activity)
    {
        $userName = $activity->changedBy->name ?? $activity->changed_by_user_name ?? 'System';
        $entityId = $activity->related_id;

        if ($activity->type === 'project') {
            $entityType = 'Project';
            $action = $activity->new_status ? 'status changed' : 'created';
            $statusInfo = $activity->new_status ?
                ' to ' . ucfirst(str_replace('_', ' ', $activity->new_status)) : '';
        } else {
            $entityType = 'Report';
            $action = $activity->new_status ? 'status changed' : 'created';
            $statusInfo = $activity->new_status ?
                ' to ' . ucfirst(str_replace('_', ' ', $activity->new_status)) : '';
        }

        return "{$userName} {$action} {$entityType} {$entityId}{$statusInfo}";
    }

    /**
     * Get activity icon based on type and status
     */
    private function getActivityIcon($activity)
    {
        if ($activity->type === 'project') {
            return 'icon-folder';
        } else {
            return 'icon-file-text';
        }
    }

    /**
     * Get activity color based on status
     */
    private function getActivityColor($activity)
    {
        if (!$activity->new_status) {
            return 'primary';
        }

        if (str_contains($activity->new_status, 'approved')) {
            return 'success';
        } elseif (str_contains($activity->new_status, 'reverted') || str_contains($activity->new_status, 'rejected')) {
            return 'danger';
        } elseif (str_contains($activity->new_status, 'forwarded') || str_contains($activity->new_status, 'submitted')) {
            return 'info';
        } else {
            return 'secondary';
        }
    }

    /**
     * Phase 3: Get system budget overview data with enhanced breakdowns (with caching - 15 minutes TTL)
     */
    private function getSystemBudgetOverviewData($request = null)
    {
        // Build cache key based on filters
        $filterHash = md5(json_encode($request ? $request->only(['province', 'center', 'project_type', 'parent_id', 'role']) : []));
        $cacheKey = "coordinator_system_budget_overview_data_{$filterHash}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($request) {
            // Get approved projects with relationships and apply filters
            $approvedProjectsQuery = Project::where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
                ->with(['user.parent', 'user', 'reports.accountDetails', 'budgets']);

            // Apply filters if provided
            if ($request && $request->filled('province')) {
                $approvedProjectsQuery->whereHas('user', function($query) use ($request) {
                    $query->where('province', $request->province);
                });
            }

            if ($request && $request->filled('center')) {
                $approvedProjectsQuery->whereHas('user', function($query) use ($request) {
                    $query->where('center', $request->center);
                });
            }

            if ($request && $request->filled('project_type')) {
                $approvedProjectsQuery->where('project_type', $request->project_type);
            }

            if ($request && $request->filled('parent_id')) {
                // Filter by provincial (who manages the executor/applicant)
                $approvedProjectsQuery->whereHas('user', function($query) use ($request) {
                    $query->where('parent_id', $request->parent_id);
                });
            }

            if ($request && $request->filled('role')) {
                $approvedProjectsQuery->whereHas('user', function($query) use ($request) {
                    $query->where('role', $request->role);
                });
            }

            $approvedProjects = $approvedProjectsQuery->get();

        // Calculate total budget, expenses, remaining
        $totalBudget = $approvedProjects->sum(function($p) {
            return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
        });

        // Calculate approved expenses (from approved reports)
        $approvedReportIds = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
            ->whereIn('project_id', $approvedProjects->pluck('project_id'))
            ->pluck('report_id');

        $approvedExpenses = DPAccountDetail::whereIn('report_id', $approvedReportIds)
            ->sum('total_expenses') ?? 0;

        // Calculate unapproved expenses (from reports pending approval - in pipeline)
        $unapprovedReportIds = DPReport::where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)
            ->whereIn('project_id', $approvedProjects->pluck('project_id'))
            ->pluck('report_id');

        $unapprovedExpenses = DPAccountDetail::whereIn('report_id', $unapprovedReportIds)
            ->sum('total_expenses') ?? 0;

        // Total expenses (approved + unapproved for display purposes)
        $totalExpenses = $approvedExpenses + $unapprovedExpenses;

        // Remaining budget is calculated using approved expenses only (unapproved don't reduce available budget)
        $totalRemaining = $totalBudget - $approvedExpenses;
        $utilization = $totalBudget > 0 ? ($approvedExpenses / $totalBudget) * 100 : 0;

        // Budget by Project Type
        $budgetByProjectType = [];
        foreach ($approvedProjects->groupBy('project_type') as $type => $projects) {
            $typeBudget = $projects->sum(function($p) {
                return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
            });

            $typeProjectIds = $projects->pluck('project_id');

            // Approved expenses
            $typeApprovedReportIds = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                ->whereIn('project_id', $typeProjectIds)
                ->pluck('report_id');

            $typeApprovedExpenses = DPAccountDetail::whereIn('report_id', $typeApprovedReportIds)
                ->sum('total_expenses') ?? 0;

            // Unapproved expenses (in pipeline)
            $typeUnapprovedReportIds = DPReport::where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)
                ->whereIn('project_id', $typeProjectIds)
                ->pluck('report_id');

            $typeUnapprovedExpenses = DPAccountDetail::whereIn('report_id', $typeUnapprovedReportIds)
                ->sum('total_expenses') ?? 0;

            $typeTotalExpenses = $typeApprovedExpenses + $typeUnapprovedExpenses;
            $typeRemaining = $typeBudget - $typeApprovedExpenses; // Remaining based on approved only

            $budgetByProjectType[$type] = [
                'budget' => $typeBudget,
                'approved_expenses' => $typeApprovedExpenses,
                'unapproved_expenses' => $typeUnapprovedExpenses,
                'expenses' => $typeTotalExpenses,
                'remaining' => $typeRemaining,
                'utilization' => $typeBudget > 0 ? ($typeApprovedExpenses / $typeBudget) * 100 : 0,
                'projects_count' => $projects->count(),
            ];
        }

        // Budget by Province (with approved/unapproved expenses)
        $budgetByProvince = [];
        foreach ($approvedProjects->groupBy(function($p) { return $p->user->province ?? 'Unknown'; }) as $province => $projects) {
            $provinceBudget = $projects->sum(function($p) {
                return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
            });

            $provinceProjectIds = $projects->pluck('project_id');

            // Approved expenses
            $provinceApprovedReportIds = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                ->whereIn('project_id', $provinceProjectIds)
                ->pluck('report_id');

            $provinceApprovedExpenses = DPAccountDetail::whereIn('report_id', $provinceApprovedReportIds)
                ->sum('total_expenses') ?? 0;

            // Unapproved expenses (in pipeline)
            $provinceUnapprovedReportIds = DPReport::where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)
                ->whereIn('project_id', $provinceProjectIds)
                ->pluck('report_id');

            $provinceUnapprovedExpenses = DPAccountDetail::whereIn('report_id', $provinceUnapprovedReportIds)
                ->sum('total_expenses') ?? 0;

            $provinceTotalExpenses = $provinceApprovedExpenses + $provinceUnapprovedExpenses;
            $provinceRemaining = $provinceBudget - $provinceApprovedExpenses; // Remaining based on approved only

            $budgetByProvince[$province] = [
                'budget' => $provinceBudget,
                'approved_expenses' => $provinceApprovedExpenses,
                'unapproved_expenses' => $provinceUnapprovedExpenses,
                'expenses' => $provinceTotalExpenses,
                'remaining' => $provinceRemaining,
                'utilization' => $provinceBudget > 0 ? ($provinceApprovedExpenses / $provinceBudget) * 100 : 0,
                'projects_count' => $projects->count(),
            ];
        }

        // Budget by Center (with approved/unapproved expenses)
        $budgetByCenter = [];
        foreach ($approvedProjects->groupBy(function($p) { return $p->user->center ?? 'Unknown'; }) as $center => $projects) {
            if (empty($center) || $center === 'Unknown') continue;

            $centerBudget = $projects->sum(function($p) {
                return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
            });

            $centerProjectIds = $projects->pluck('project_id');

            // Approved expenses
            $centerApprovedReportIds = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                ->whereIn('project_id', $centerProjectIds)
                ->pluck('report_id');

            $centerApprovedExpenses = DPAccountDetail::whereIn('report_id', $centerApprovedReportIds)
                ->sum('total_expenses') ?? 0;

            // Unapproved expenses (in pipeline)
            $centerUnapprovedReportIds = DPReport::where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)
                ->whereIn('project_id', $centerProjectIds)
                ->pluck('report_id');

            $centerUnapprovedExpenses = DPAccountDetail::whereIn('report_id', $centerUnapprovedReportIds)
                ->sum('total_expenses') ?? 0;

            $centerTotalExpenses = $centerApprovedExpenses + $centerUnapprovedExpenses;
            $centerRemaining = $centerBudget - $centerApprovedExpenses; // Remaining based on approved only

            $budgetByCenter[$center] = [
                'budget' => $centerBudget,
                'approved_expenses' => $centerApprovedExpenses,
                'unapproved_expenses' => $centerUnapprovedExpenses,
                'expenses' => $centerTotalExpenses,
                'remaining' => $centerRemaining,
                'utilization' => $centerBudget > 0 ? ($centerApprovedExpenses / $centerBudget) * 100 : 0,
                'projects_count' => $projects->count(),
            ];
        }

        // Budget by Provincial (who manages)
        $budgetByProvincial = [];
        foreach ($approvedProjects->groupBy(function($p) {
            return $p->user->parent ? $p->user->parent->id : 'No Provincial';
        }) as $provincialId => $projects) {
            if ($provincialId === 'No Provincial') continue;

            $provincial = $projects->first()->user->parent;
            $provincialName = $provincial ? $provincial->name : 'Unknown';

            $provincialBudget = $projects->sum(function($p) {
                return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
            });

            $provincialProjectIds = $projects->pluck('project_id');
            $provincialApprovedReportIds = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                ->whereIn('project_id', $provincialProjectIds)
                ->pluck('report_id');

            $provincialExpenses = DPAccountDetail::whereIn('report_id', $provincialApprovedReportIds)
                ->sum('total_expenses') ?? 0;

            $budgetByProvincial[$provincialName] = [
                'provincial_id' => $provincialId,
                'provincial_name' => $provincialName,
                'province' => $provincial ? $provincial->province : 'Unknown',
                'budget' => $provincialBudget,
                'expenses' => $provincialExpenses,
                'remaining' => $provincialBudget - $provincialExpenses,
                'utilization' => $provincialBudget > 0 ? ($provincialExpenses / $provincialBudget) * 100 : 0,
                'projects_count' => $projects->count(),
            ];
        }

        // Expense Trends Over Time (last 6 months)
        $expenseTrends = [];
        $current = now()->subMonths(6)->startOfMonth();
        while ($current <= now()) {
            $monthEnd = $current->copy()->endOfMonth();

            $monthReportIds = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                ->whereBetween('created_at', [$current->copy()->startOfMonth(), $monthEnd])
                ->pluck('report_id');

            $monthExpenses = DPAccountDetail::whereIn('report_id', $monthReportIds)
                ->sum('total_expenses') ?? 0;

            $expenseTrends[] = [
                'month' => $current->format('M Y'),
                'month_key' => $current->format('Y-m'),
                'expenses' => $monthExpenses,
            ];

            $current->addMonth();
        }

        // Top Projects by Budget
        $topProjectsByBudget = $approvedProjects->sortByDesc(function($p) {
            return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0;
        })->take(10)->map(function($p) {
            $projectBudget = (float) ($p->amount_sanctioned ?? $p->overall_project_budget ?? 0);

            $projectApprovedReportIds = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                ->where('project_id', $p->project_id)
                ->pluck('report_id');

            $projectExpenses = DPAccountDetail::whereIn('report_id', $projectApprovedReportIds)
                ->sum('total_expenses') ?? 0;

            return [
                'project_id' => $p->project_id,
                'project_title' => $p->project_title,
                'project_type' => $p->project_type,
                'province' => $p->user->province ?? 'Unknown',
                'budget' => $projectBudget,
                'expenses' => $projectExpenses,
                'remaining' => $projectBudget - $projectExpenses,
                'utilization' => $projectBudget > 0 ? ($projectExpenses / $projectBudget) * 100 : 0,
            ];
        })->values();

        return [
            'total' => [
                'budget' => $totalBudget,
                'approved_expenses' => $approvedExpenses,
                'unapproved_expenses' => $unapprovedExpenses,
                'expenses' => $totalExpenses,
                'remaining' => $totalRemaining,
                'utilization' => round($utilization, 2),
            ],
            'by_project_type' => $budgetByProjectType,
            'by_province' => $budgetByProvince,
            'by_center' => $budgetByCenter,
            'by_provincial' => $budgetByProvincial,
            'expense_trends' => $expenseTrends,
            'top_projects_by_budget' => $topProjectsByBudget,
        ];
        });
    }

    /**
     * Invalidate budget overview cache when data is modified
     * Note: Cache is automatically invalidated based on filter hash, so we don't need to flush all
     * The cache key includes filter hash, so different filters have different cache keys
     */

    /**
     * Phase 3: Get province performance comparison data (with caching - 15 minutes TTL)
     */
    private function getProvinceComparisonData()
    {
        $cacheKey = 'coordinator_province_comparison_data';

        return Cache::remember($cacheKey, now()->addMinutes(15), function () {
            $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
            $calc = $this->calculationService;

            // Load all projects and reports once
            $allProjects = Project::with(['user', 'user.parent', 'budgets'])->get();
            $allReports = DPReport::with(['user'])->get();

            $resolvedFinancials = [];
            foreach ($allProjects as $project) {
                $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
            }

            $projectsByProvince = $allProjects->groupBy(fn($p) => $p->user->province ?? 'Unknown');
            $reportsByProvince = $allReports->groupBy(fn($r) => $r->user->province ?? 'Unknown');

            $usersByProvinceGrouped = User::whereNotNull('province')->get()->groupBy('province');
            $provincialCountByProvince = $usersByProvinceGrouped->map(fn($users) => $users->where('role', 'provincial')->count());
            $usersCountByProvince = $usersByProvinceGrouped->map->count();

            $provinces = $projectsByProvince->keys()->merge($reportsByProvince->keys())->unique()->filter(fn($p) => $p !== 'Unknown');
            $provincePerformance = [];

        foreach ($provinces as $province) {
            $provinceProjects = $projectsByProvince->get($province, collect());
            $provinceReports = $reportsByProvince->get($province, collect());

            $provinceApprovedProjects = $provinceProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
            $provinceBudget = $provinceApprovedProjects->sum(fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0));

            $provinceApprovedReportIds = $provinceReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->pluck('report_id');
            $provinceExpenses = (float) (DPAccountDetail::whereIn('report_id', $provinceApprovedReportIds)->sum('total_expenses') ?? 0);

            $approvalRate = $provinceReports->count() > 0 ?
                ($provinceReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count() / $provinceReports->count()) * 100 : 0;

            $approvedReports = $provinceReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);
            $avgProcessingTime = 0;
            if ($approvedReports->count() > 0) {
                $totalDays = $approvedReports->sum(fn($report) => $report->created_at->diffInDays(now()));
                $avgProcessingTime = round($totalDays / $approvedReports->count(), 1);
            }

            $provincePerformance[$province] = [
                'projects' => $provinceProjects->count(),
                'approved_projects' => $provinceProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)->count(),
                'reports' => $provinceReports->count(),
                'approved_reports' => $provinceReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count(),
                'budget' => $provinceBudget,
                'expenses' => $provinceExpenses,
                'remaining' => $calc->calculateRemainingBalance($provinceBudget, $provinceExpenses),
                'utilization' => round($calc->calculateUtilization($provinceExpenses, $provinceBudget), 2),
                'approval_rate' => round($approvalRate, 2),
                'avg_processing_time' => $avgProcessingTime,
                'provincials_count' => $provincialCountByProvince->get($province, 0),
                'users_count' => $usersCountByProvince->get($province, 0),
            ];
        }

        // Calculate rankings
        $rankedByApprovalRate = collect($provincePerformance)
            ->sortByDesc('approval_rate')
            ->take(10)
            ->keys()
            ->values();

        $rankedByUtilization = collect($provincePerformance)
            ->sortByDesc('utilization')
            ->take(10)
            ->keys()
            ->values();

        $rankedByBudget = collect($provincePerformance)
            ->sortByDesc('budget')
            ->take(10)
            ->keys()
            ->values();

        return [
            'province_performance' => $provincePerformance,
            'rankings' => [
                'by_approval_rate' => $rankedByApprovalRate,
                'by_utilization' => $rankedByUtilization,
                'by_budget' => $rankedByBudget,
            ],
            'summary' => [
                'total_provinces' => count($provincePerformance),
                'top_performer' => $rankedByApprovalRate->first(),
                'highest_budget' => $rankedByBudget->first(),
                'most_utilized' => $rankedByUtilization->first(),
            ],
        ];
        });
    }

    /**
     * Phase 3: Get provincial management data with detailed stats (with caching - 10 minutes TTL)
     */
    private function getProvincialManagementData()
    {
        $cacheKey = 'coordinator_provincial_management_data';

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
            $calc = $this->calculationService;

            // Load all projects and reports once
            $allProjects = Project::with(['user', 'user.parent', 'budgets'])->get();
            $allReports = DPReport::with(['user'])->get();

            $resolvedFinancials = [];
            foreach ($allProjects as $project) {
                $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
            }

            $provincials = User::where('role', 'provincial')
                ->with(['children' => function($query) {
                    $query->whereIn('role', ['executor', 'applicant']);
                }])
                ->get()
                ->map(function($provincial) use ($allProjects, $allReports, $resolvedFinancials, $calc) {
                $teamUserIds = $provincial->children->pluck('id');
                $teamUserIdsArray = $teamUserIds->toArray();

                // Team projects - filter in memory
                $teamProjects = $allProjects->whereIn('user_id', $teamUserIdsArray);
                $approvedTeamProjects = $teamProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);

                // Team reports - filter in memory
                $teamReports = $allReports->whereIn('user_id', $teamUserIdsArray);
                $pendingTeamReports = $teamReports->where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR);
                $approvedTeamReports = $teamReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);

                // Calculate budget via resolver
                $teamBudget = $approvedTeamProjects->sum(fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0));

                $teamApprovedReportIds = $approvedTeamReports->pluck('report_id');
                $teamExpenses = (float) (DPAccountDetail::whereIn('report_id', $teamApprovedReportIds)->sum('total_expenses') ?? 0);

                // Calculate approval rate
                $approvalRate = $teamReports->count() > 0 ?
                    ($approvedTeamReports->count() / $teamReports->count()) * 100 : 0;

                // Last activity (most recent report or project)
                $lastActivity = null;
                $lastReport = $teamReports->sortByDesc('created_at')->first();
                $lastProject = $teamProjects->sortByDesc('created_at')->first();

                if ($lastReport && $lastProject) {
                    $lastActivity = $lastReport->created_at > $lastProject->created_at ?
                        $lastReport->created_at : $lastProject->created_at;
                } elseif ($lastReport) {
                    $lastActivity = $lastReport->created_at;
                } elseif ($lastProject) {
                    $lastActivity = $lastProject->created_at;
                }

                // Performance score (0-100)
                $performanceScore = 0;
                if ($teamReports->count() > 0) {
                    $scoreFactors = [
                        'approval_rate' => round($approvalRate * 0.4), // 40% weight
                        'activity' => $lastActivity ? (100 - min(90, $lastActivity->diffInDays(now()) * 2)) * 0.3 : 0, // 30% weight (recent activity)
                        'pending_reports' => max(0, 100 - ($pendingTeamReports->count() * 10)) * 0.3, // 30% weight (fewer pending is better)
                    ];
                    $performanceScore = round(array_sum($scoreFactors));
                }

                return [
                    'id' => $provincial->id,
                    'name' => $provincial->name,
                    'province' => $provincial->province ?? 'Unknown',
                    'center' => $provincial->center ?? 'Unknown',
                    'status' => $provincial->status ?? 'active',
                    'team_members_count' => $teamUserIds->count(),
                    'projects_count' => $teamProjects->count(),
                    'approved_projects_count' => $approvedTeamProjects->count(),
                    'reports_count' => $teamReports->count(),
                    'pending_reports_count' => $pendingTeamReports->count(),
                    'approved_reports_count' => $approvedTeamReports->count(),
                    'budget' => $teamBudget,
                    'expenses' => $teamExpenses,
                    'remaining' => $calc->calculateRemainingBalance($teamBudget, $teamExpenses),
                    'utilization' => round($calc->calculateUtilization($teamExpenses, $teamBudget), 2),
                    'approval_rate' => round($approvalRate, 2),
                    'last_activity' => $lastActivity,
                    'days_since_activity' => $lastActivity ? $lastActivity->diffInDays(now()) : null,
                    'performance_score' => $performanceScore,
                    'performance_level' => $performanceScore >= 80 ? 'excellent' :
                                          ($performanceScore >= 60 ? 'good' :
                                          ($performanceScore >= 40 ? 'fair' : 'poor')),
                ];
            })
            ->sortByDesc('performance_score')
            ->values();

        return [
            'provincials' => $provincials,
            'summary' => [
                'total' => $provincials->count(),
                'active' => $provincials->where('status', 'active')->count(),
                'inactive' => $provincials->where('status', '!=', 'active')->count(),
                'total_team_members' => $provincials->sum('team_members_count'),
                'total_projects' => $provincials->sum('projects_count'),
                'total_reports' => $provincials->sum('reports_count'),
                'avg_approval_rate' => $provincials->count() > 0 ?
                    round($provincials->avg('approval_rate'), 2) : 0,
                'avg_performance_score' => $provincials->count() > 0 ?
                    round($provincials->avg('performance_score'), 2) : 0,
            ],
        ];
        });
    }

    /**
     * Phase 3: Get system health indicators data (with caching - 5 minutes TTL)
     */
    private function getSystemHealthData()
    {
        $cacheKey = 'coordinator_system_health_data';

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
            $calc = $this->calculationService;

            // Load system-wide data once with eager loading
            $systemProjects = Project::with(['user', 'user.parent', 'budgets'])->get();
            $systemReports = DPReport::with(['user'])->get();

            $resolvedFinancials = [];
            foreach ($systemProjects as $project) {
                $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
            }

        $approvedProjects = $systemProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
        $totalBudget = $approvedProjects->sum(fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0));

        $approvedReportIds = $systemReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->pluck('report_id');
        $totalExpenses = (float) (DPAccountDetail::whereIn('report_id', $approvedReportIds)->sum('total_expenses') ?? 0);

        // Calculate key indicators
        $budgetUtilization = $calc->calculateUtilization($totalExpenses, $totalBudget);

        $approvalRate = $systemReports->count() > 0 ?
            ($systemReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count() / $systemReports->count()) * 100 : 0;

        // Calculate average processing time
        $approvedReports = $systemReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);
        $avgProcessingTime = 0;
        if ($approvedReports->count() > 0) {
            $totalDays = $approvedReports->sum(function($report) {
                return $report->created_at->diffInDays(now());
            });
            $avgProcessingTime = round($totalDays / $approvedReports->count(), 1);
        }

        // Report submission rate (reports per month)
        $reportsLastMonth = $systemReports->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->count();
        $reportsThisMonth = $systemReports->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $submissionRate = $reportsLastMonth > 0 ? (($reportsThisMonth - $reportsLastMonth) / $reportsLastMonth) * 100 : 0;

        // Project completion rate
        $completedProjects = $systemProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)->count();
        $completionRate = $systemProjects->count() > 0 ? ($completedProjects / $systemProjects->count()) * 100 : 0;

        // User activity rate (active users in last 30 days)
        $recentActivities = \App\Models\ActivityHistory::where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('changed_by_user_id')
            ->distinct('changed_by_user_id')
            ->count('changed_by_user_id');
        $totalUsers = User::whereIn('role', ['executor', 'applicant', 'provincial'])->count();
        $activityRate = $totalUsers > 0 ? ($recentActivities / $totalUsers) * 100 : 0;

        // Calculate overall health score (0-100)
        $healthFactors = [
            'approval_rate' => min(100, $approvalRate), // 0-100
            'budget_utilization' => min(100, $budgetUtilization), // 0-100, but lower is better for some cases
            'processing_time' => max(0, 100 - ($avgProcessingTime * 5)), // Better if faster
            'completion_rate' => min(100, $completionRate), // 0-100
            'activity_rate' => min(100, $activityRate), // 0-100
        ];

        // Weighted health score
        $overallScore = round(
            ($healthFactors['approval_rate'] * 0.3) +
            (max(0, 100 - abs($healthFactors['budget_utilization'] - 70)) * 0.2) + // Optimal around 70%
            (max(0, min(100, $healthFactors['processing_time'])) * 0.2) +
            ($healthFactors['completion_rate'] * 0.15) +
            ($healthFactors['activity_rate'] * 0.15)
        );

        $healthLevel = $overallScore >= 80 ? 'excellent' :
                      ($overallScore >= 60 ? 'good' :
                      ($overallScore >= 40 ? 'fair' : 'poor'));

        // Get alerts
        $alerts = [];
        if ($budgetUtilization >= 90) {
            $alerts[] = ['type' => 'critical', 'message' => 'Budget utilization is critical (>90%)', 'color' => 'danger'];
        } elseif ($budgetUtilization >= 75) {
            $alerts[] = ['type' => 'warning', 'message' => 'Budget utilization is high (>75%)', 'color' => 'warning'];
        }

        if ($approvalRate < 50) {
            $alerts[] = ['type' => 'critical', 'message' => 'Approval rate is below threshold (<50%)', 'color' => 'danger'];
        } elseif ($approvalRate < 70) {
            $alerts[] = ['type' => 'warning', 'message' => 'Approval rate is below expected (<70%)', 'color' => 'warning'];
        }

        if ($avgProcessingTime > 10) {
            $alerts[] = ['type' => 'warning', 'message' => 'Average processing time is high (>10 days)', 'color' => 'warning'];
        }

        $pendingReportsCount = $systemReports->where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)->count();
        if ($pendingReportsCount > 50) {
            $alerts[] = ['type' => 'warning', 'message' => "High number of pending reports ({$pendingReportsCount})", 'color' => 'warning'];
        }

        // Health trends (last 6 months)
        $healthTrends = [];
        $current = now()->subMonths(6)->startOfMonth();
        while ($current <= now()) {
            $monthEnd = $current->copy()->endOfMonth();

            $monthReports = $systemReports->whereBetween('created_at', [$current->copy()->startOfMonth(), $monthEnd]);
            $monthApprovalRate = $monthReports->count() > 0 ?
                ($monthReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count() / $monthReports->count()) * 100 : 0;

            $healthTrends[] = [
                'month' => $current->format('M Y'),
                'month_key' => $current->format('Y-m'),
                'score' => round($monthApprovalRate * 0.5 + 50), // Simplified trend calculation
            ];

            $current->addMonth();
        }

        return [
            'overall_score' => $overallScore,
            'health_level' => $healthLevel,
            'factors' => [
                'budget_utilization' => round($budgetUtilization, 2),
                'approval_rate' => round($approvalRate, 2),
                'avg_processing_time' => $avgProcessingTime,
                'submission_rate' => round($submissionRate, 2),
                'completion_rate' => round($completionRate, 2),
                'activity_rate' => round($activityRate, 2),
            ],
            'alerts' => $alerts,
            'trends' => $healthTrends,
            'summary' => [
                'total_projects' => $systemProjects->count(),
                'total_reports' => $systemReports->count(),
                'pending_reports' => $pendingReportsCount,
                'total_budget' => $totalBudget,
                'total_expenses' => $totalExpenses,
            ],
        ];
        });
    }

    // Approved Projects for Coordinators
    public function approvedProjects(Request $request)
    {
        $coordinator = Auth::user();

        // Base query for approved projects - coordinators can see all project types
        // Use a subquery to get unique project IDs first, then fetch the full records
        $projectIds = Project::where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
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
        $report = DPReport::where('report_id', $report_id)->with('user')->firstOrFail();

        try {
            $coordinator = Auth::user();
            // Use ReportStatusService to approve and log status change
            ReportStatusService::approve($report, $coordinator);

            // Notify executor about report approval
            $executor = $report->user;
            if ($executor) {
                // Get the integer id for the notification (use id attribute)
                $reportId = $report->getAttribute('id');
                if ($reportId) {
                    NotificationService::notifyApproval(
                        $executor,
                        'report',
                        $reportId,
                        "Report {$report->report_id}"
                    );
                }
            }

            // Invalidate cache after approval
            $this->invalidateDashboardCache();

            return redirect()->route('coordinator.report.list')->with('success', 'Report approved successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to approve report', [
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

        $report = DPReport::where('report_id', $report_id)->with('user')->firstOrFail();

        try {
            $coordinator = Auth::user();
            // Use ReportStatusService to revert and log status change
            ReportStatusService::revertByCoordinator($report, $coordinator, $request->revert_reason);

            // Notify executor about report revert
            $executor = $report->user;
            if ($executor) {
                // Get the integer id for the notification (use id attribute)
                $reportId = $report->getAttribute('id');
                if ($reportId) {
                    NotificationService::notifyRevert(
                        $executor,
                        'report',
                        $reportId,
                        "Report {$report->report_id}",
                        $request->revert_reason
                    );
                }
            }

            // Invalidate cache after revert
            $this->invalidateDashboardCache();

            return redirect()->route('coordinator.report.list')->with('success', 'Report reverted to provincial successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to revert report', [
                'report_id' => $report_id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Handle bulk report actions (approve/revert)
     */
    public function bulkReportAction(Request $request)
    {
        $coordinator = Auth::user();
        $action = $request->input('action');
        $reportIds = $request->input('report_ids', []);

        if (empty($reportIds) || !is_array($reportIds)) {
            return redirect()->route('coordinator.report.list')->with('error', 'No reports selected.');
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($reportIds as $reportId) {
            try {
                $report = DPReport::where('report_id', $reportId)->first();

                if (!$report) {
                    $errorCount++;
                    $errors[] = "Report {$reportId} not found.";
                    continue;
                }

                if ($action === 'bulk_approve') {
                    if ($report->isForwardedToCoordinator()) {
                        ReportStatusService::approve($report, $coordinator);
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "Report {$reportId} cannot be approved in current status.";
                    }
                } elseif ($action === 'bulk_revert') {
                    $reason = $request->input('revert_reason', 'Bulk revert by coordinator');

                    if (!$reason || trim($reason) === '') {
                        $errorCount++;
                        $errors[] = "Reason required for reverting report {$reportId}.";
                        continue;
                    }

                    if ($report->isForwardedToCoordinator()) {
                        ReportStatusService::revertByCoordinator($report, $coordinator, $reason);
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "Report {$reportId} cannot be reverted in current status.";
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Error processing report {$reportId}: " . $e->getMessage();
                \Log::error('Bulk action error', [
                    'report_id' => $reportId,
                    'action' => $action,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = "Bulk action completed: {$successCount} report(s) processed successfully.";
        if ($errorCount > 0) {
            $message .= " {$errorCount} report(s) failed.";
        }

        // Invalidate cache after bulk actions
        $this->invalidateDashboardCache();

        $redirect = redirect()->route('coordinator.report.list');
        if ($errorCount > 0) {
            return $redirect->with('warning', $message)->with('bulk_errors', $errors);
        } else {
            return $redirect->with('success', $message);
        }
    }

    /**
     * Invalidate dashboard cache when data changes
     *
     * This method is called after any action that modifies dashboard data
     * (e.g., approve/revert reports, approve/revert projects) to ensure
     * the dashboard shows fresh data. All widget cache keys are cleared.
     *
     * @return void
     */
    private function invalidateDashboardCache()
    {
        // Invalidate all dashboard-related cache keys
        Cache::forget('coordinator_pending_approvals_data');
        Cache::forget('coordinator_provincial_overview_data');
        Cache::forget('coordinator_system_performance_data');
        Cache::forget('coordinator_system_activity_feed_data_50');
        // Note: Budget overview cache now uses filter hash in key (coordinator_system_budget_overview_data_{hash})
        // Since Laravel doesn't support wildcard deletion easily, cache will expire naturally (15 min TTL)
        // For production, consider using cache tags if your driver supports it for better invalidation
        // Old cache key without filters (kept for backward compatibility during transition)
        Cache::forget('coordinator_system_budget_overview_data');
        Cache::forget('coordinator_province_comparison_data');
        Cache::forget('coordinator_provincial_management_data');
        Cache::forget('coordinator_system_health_data');

        // Also invalidate analytics cache (for all common time ranges)
        $timeRanges = [7, 30, 90, 180, 365];
        foreach ($timeRanges as $range) {
            Cache::forget("coordinator_system_analytics_data_{$range}");
        }

        // Invalidate filter option caches
        Cache::forget('coordinator_report_list_filters');
        Cache::forget('coordinator_project_list_filters');
    }

    public function pendingReports(Request $request)
    {
        $coordinator = Auth::user();

        // Fetch pending reports (forwarded_to_coordinator)
        // Eager load relationships to prevent N+1 queries
        $reportsQuery = DPReport::where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)
                               ->with(['user', 'project', 'accountDetails']);

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
        $projectTypes = DPReport::where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)->distinct()->pluck('project_type');

        return view('coordinator.pendingReports', compact('reports', 'coordinator', 'provinces', 'users', 'projectTypes'));
    }

    public function approvedReports(Request $request)
    {
        $coordinator = Auth::user();

        // Fetch approved reports
        // Eager load relationships to prevent N+1 queries
        $reportsQuery = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                               ->with(['user', 'project', 'accountDetails']);

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
        $projectTypes = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->distinct()->pluck('project_type');

        return view('coordinator.approvedReports', compact('reports', 'coordinator', 'provinces', 'users', 'projectTypes'));
    }

    /**
     * Get centers map for all provinces from database
     * Returns array with province name (uppercase) as key and array of center names as value
     */
    private function getCentersMap()
    {
        return Cache::remember('centers_map', now()->addHours(24), function () {
            $centersMap = [];

            $provinces = Province::active()->with('activeCenters')->get();

            foreach ($provinces as $province) {
                $provinceKey = strtoupper($province->name);
                $centersMap[$provinceKey] = $province->activeCenters->pluck('name')->toArray();
            }

            return $centersMap;
        });
    }

    /**
     * Show manage user centers page - allows changing centers for child users
     */
    public function manageUserCenters(Request $request, $userId = null)
    {
        $coordinator = Auth::user();

        // If userId is provided and user has permission, use that user
        // Otherwise, use current user
        if ($userId) {
            $targetUser = User::findOrFail($userId);

            // Verify the target user is a child (or nested child) of current coordinator
            if (!$this->isChildUser($coordinator->id, $targetUser->id)) {
                abort(403, 'Access denied. You can only manage centers for users under your management.');
            }
        } else {
            $targetUser = $coordinator;
        }

        // Get all child users (including nested)
        $childUsers = $this->getAllChildUsers($coordinator->id);

        // Get provinces and centers for dropdowns
        $provinces = Province::active()->with('activeCenters')->orderBy('name')->get();
        $centersMap = $this->getCentersMap();

        return view('coordinator.centers.manage-users', compact('childUsers', 'provinces', 'centersMap', 'targetUser'));
    }

    /**
     * Update center for a specific user and optionally their child users
     */
    public function updateUserCenter(Request $request, $userId)
    {
        $coordinator = Auth::user();
        $targetUser = User::findOrFail($userId);

        // Verify the target user is a child (or nested child) of current coordinator
        if (!$this->isChildUser($coordinator->id, $targetUser->id)) {
            abort(403, 'Access denied. You can only manage centers for users under your management.');
        }

        $request->validate([
            'province' => 'required|exists:provinces,name',
            'center' => 'nullable|string|max:255',
            'update_child_users' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Get province and center IDs
            $province = Province::where('name', $request->province)->first();
            $provinceId = $province ? $province->id : null;

            $centerId = null;
            if ($request->filled('center') && $provinceId) {
                $center = Center::where('province_id', $provinceId)
                    ->whereRaw('UPPER(name) = ?', [strtoupper($request->center)])
                    ->first();
                $centerId = $center ? $center->id : null;
            }

            // Update target user
            $oldProvince = $targetUser->province;
            $oldCenter = $targetUser->center;

            $targetUser->province = $request->province;
            $targetUser->province_id = $provinceId;
            $targetUser->center = $request->center;
            $targetUser->center_id = $centerId;
            $targetUser->save();

            $updatedCount = 1;

            // Update child users if requested
            if ($request->has('update_child_users') && $request->update_child_users) {
                $childUpdatedCount = $this->updateChildUsersCenterRecursively(
                    $targetUser->id,
                    $request->center,
                    $oldProvince,
                    $request->province
                );
                $updatedCount += $childUpdatedCount;
            }

            DB::commit();

            Log::info('User center updated by Coordinator', [
                'coordinator_id' => $coordinator->id,
                'target_user_id' => $userId,
                'old_province' => $oldProvince,
                'new_province' => $request->province,
                'old_center' => $oldCenter,
                'new_center' => $request->center,
                'child_users_updated' => $updatedCount - 1,
            ]);

            return redirect()->back()
                ->with('success', "Center updated for user \"{$targetUser->name}\" and {$updatedCount} user(s) total.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating user center by Coordinator', [
                'coordinator_id' => $coordinator->id,
                'target_user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors('Failed to update center: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Helper: Check if a user is a child (or nested child) of another user
     */
    private function isChildUser($parentId, $childId)
    {
        $child = User::find($childId);
        if (!$child) {
            return false;
        }

        // Direct child
        if ($child->parent_id == $parentId) {
            return true;
        }

        // Nested child - recursively check
        if ($child->parent_id) {
            return $this->isChildUser($parentId, $child->parent_id);
        }

        return false;
    }

    /**
     * Helper: Get all child users recursively
     */
    private function getAllChildUsers($userId)
    {
        $children = collect();
        $directChildren = User::where('parent_id', $userId)->get();

        foreach ($directChildren as $child) {
            $children->push($child);
            $children = $children->merge($this->getAllChildUsers($child->id));
        }

        return $children;
    }

    /**
     * Helper: Recursively update child users' center
     */
    private function updateChildUsersCenterRecursively($userId, $centerName, $oldProvince, $newProvince)
    {
        $updatedCount = 0;
        $childUsers = User::where('parent_id', $userId)->get();

        $newProvinceModel = Province::where('name', $newProvince)->first();
        $newProvinceId = $newProvinceModel ? $newProvinceModel->id : null;

        $newCenterId = null;
        if ($centerName && $newProvinceId) {
            $newCenter = Center::where('province_id', $newProvinceId)
                ->whereRaw('UPPER(name) = ?', [strtoupper($centerName)])
                ->first();
            $newCenterId = $newCenter ? $newCenter->id : null;
        }

        foreach ($childUsers as $childUser) {
            // Only update if user's current province/center matches
            if ($childUser->province == $oldProvince ||
                ($centerName && $childUser->center == $centerName)) {

                $childUser->province = $newProvince;
                $childUser->province_id = $newProvinceId;

                if ($centerName) {
                    $childUser->center = $centerName;
                    $childUser->center_id = $newCenterId;
                }

                $childUser->save();
                $updatedCount++;

                // Recursively update nested children
                $updatedCount += $this->updateChildUsersCenterRecursively(
                    $childUser->id,
                    $centerName,
                    $oldProvince,
                    $newProvince
                );
            }
        }

        return $updatedCount;
    }

}
