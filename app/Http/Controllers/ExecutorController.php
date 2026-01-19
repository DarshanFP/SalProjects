<?php

namespace App\Http\Controllers;

use App\Models\OldProjects\Project;
use App\Models\Reports\Quarterly\RQDPReport;
use App\Models\Reports\Monthly\DPReport;
use App\Constants\ProjectStatus;
use App\Services\ProjectQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExecutorController extends Controller
{
    //
    public function executorDashboard(Request $request)
    {
        $user = Auth::user();

        // Get projects where user is owner or in-charge (for both executor and applicant)
        $projectsQuery = ProjectQueryService::getProjectsForUserQuery($user);

        // Determine which projects to show based on filter
        $showType = $request->get('show', 'approved'); // 'approved', 'needs_work', 'all'

        if ($showType === 'needs_work') {
            // Show projects that need work (draft, reverted statuses)
            $editableStatuses = ProjectStatus::getEditableStatuses();
            $projectsQuery->whereIn('status', $editableStatuses);
        } elseif ($showType === 'all') {
            // Show all projects (no status filter)
        } else {
            // Default: show approved projects (any approval status)
            $projectsQuery->whereIn('status', [
                ProjectStatus::APPROVED_BY_COORDINATOR,
                ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR,
                ProjectStatus::APPROVED_BY_GENERAL_AS_PROVINCIAL,
            ]);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $projectsQuery = ProjectQueryService::applySearchFilter($projectsQuery, $request->search);
        }

        // Apply filtering if provided in the request
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        // Apply specific status filter (for showing specific status if needed)
        if ($request->filled('status')) {
            $projectsQuery->where('status', $request->status);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSortFields = ['project_id', 'project_title', 'project_type', 'created_at', 'commencement_month_year'];
        if (in_array($sortBy, $allowedSortFields)) {
            $projectsQuery->orderBy($sortBy, $sortOrder);
        } else {
            $projectsQuery->orderBy('created_at', 'desc');
        }

        // Eager load relationships
        $projectsQuery->with([
            'reports' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'reports.accountDetails',
            'budgets',
            'user',
        ]);

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $projects = $projectsQuery->paginate($perPage)->appends($request->query());

        // Calculate budget summaries from APPROVED projects only (regardless of current filter)
        // Budget summaries should only reflect approved projects with active budgets
        $approvedProjectsForSummary = ProjectQueryService::getApprovedProjectsForUser($user, [
            'reports' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'reports.accountDetails',
            'budgets',
        ]);

        // Pass collection directly (method accepts both arrays and collections)
        $budgetSummaries = $this->calculateBudgetSummariesFromProjects($approvedProjectsForSummary->all(), $request);

        // Enhance projects with additional data (budget utilization, health, last report date)
        $enhancedProjects = $this->enhanceProjectsWithMetadata($projects->items());

        // Fetch distinct project types for filters (from all projects)
        $projectTypes = ProjectQueryService::getProjectsForUserQuery($user)
            ->distinct()
            ->pluck('project_type');

        // Get action items data for dashboard widgets
        $actionItems = $this->getActionItems($user);
        $reportStatusSummary = $this->getReportStatusSummary($user);
        $upcomingDeadlines = $this->getUpcomingDeadlines($user);

        // Get chart data for visual analytics (only if we have projects)
        $chartData = [];
        $reportChartData = [];
        if ($projects->total() > 0) {
            $chartData = $this->getChartData($user, $request);
        }

        // Get report chart data (always available if user has reports)
        $reportChartData = $this->getReportChartData($user, $request);

        // Get quick stats data
        $quickStats = $this->getQuickStats($user);

        // Get recent activities for feed
        $recentActivities = $this->getRecentActivities($user);

        // Get project health summary
        $projectHealthSummary = $this->getProjectHealthSummary($enhancedProjects ?? []);

        // Get projects requiring attention (draft, reverted)
        $projectsRequiringAttention = $this->getProjectsRequiringAttention($user);

        // Get reports requiring attention (draft, reverted)
        $reportsRequiringAttention = $this->getReportsRequiringAttention($user);

        // Pass the projects to the executor index view
        return view('executor.index', compact('projects', 'budgetSummaries', 'projectTypes', 'actionItems', 'reportStatusSummary', 'upcomingDeadlines', 'enhancedProjects', 'chartData', 'reportChartData', 'quickStats', 'recentActivities', 'projectHealthSummary', 'projectsRequiringAttention', 'reportsRequiringAttention', 'showType'));
    }

    public function reportList(Request $request)
    {
        $user = Auth::user();

        // Fetch reports for projects where user is owner or in-charge
        $projectIds = ProjectQueryService::getProjectIdsForUser($user);

        $reportsQuery = DPReport::whereIn('project_id', $projectIds);

        // Apply filtering if provided in the request
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        // Eager load relationships to prevent N+1 queries
        $reports = $reportsQuery->with(['user', 'project', 'accountDetails'])->get();

        // Calculate budget summaries
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

        // Fetch distinct project types for filters
        $projectTypes = DPReport::whereIn('project_id', $projectIds)->distinct()->pluck('project_type');

        // Get upcoming deadlines for the report list page
        $upcomingDeadlines = $this->getUpcomingDeadlines($user);

        return view('executor.ReportList', compact('reports', 'budgetSummaries', 'projectTypes', 'upcomingDeadlines'));
    }

    public function submitReport(Request $request, $report_id)
    {
        $user = Auth::user();

        // Get project IDs where user is owner or in-charge
        $projectIds = ProjectQueryService::getProjectIdsForUser($user);

        $report = DPReport::where('report_id', $report_id)
                         ->whereIn('project_id', $projectIds)
                         ->firstOrFail();

        try {
            // Use ReportStatusService to submit and log status change
            \App\Services\ReportStatusService::submitToProvincial($report, $user);
            return redirect()->route('executor.report.list')->with('success', 'Report submitted to provincial successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function pendingReports(Request $request)
    {
        $user = Auth::user();

        // Get project IDs where user is owner or in-charge
        $projectIds = ProjectQueryService::getProjectIdsForUser($user);

        // Fetch pending reports for projects where user is owner or in-charge
        $reportsQuery = DPReport::whereIn('project_id', $projectIds);

        // Apply status filter if provided, otherwise show all pending statuses
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'draft') {
                $reportsQuery->where('status', DPReport::STATUS_DRAFT);
            } elseif ($status === 'reverted') {
                // Filter for all reverted statuses
                $reportsQuery->where(function($query) {
                    $query->whereIn('status', [
                        DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                        DPReport::STATUS_REVERTED_BY_COORDINATOR,
                        DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
                        DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
                        DPReport::STATUS_REVERTED_TO_EXECUTOR,
                        DPReport::STATUS_REVERTED_TO_APPLICANT,
                        DPReport::STATUS_REVERTED_TO_PROVINCIAL,
                        DPReport::STATUS_REVERTED_TO_COORDINATOR,
                    ]);
                });
            } else {
                $reportsQuery->where('status', $status);
            }
        } else {
            // Default: show all pending/reverted statuses
            $reportsQuery->whereIn('status', [
                DPReport::STATUS_DRAFT,
                DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_COORDINATOR,
                DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
                DPReport::STATUS_REVERTED_TO_EXECUTOR,
                DPReport::STATUS_REVERTED_TO_APPLICANT,
            ]);
        }

        // Apply filtering if provided in the request
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        // Eager load relationships to prevent N+1 queries
        $reports = $reportsQuery->with(['user', 'project', 'accountDetails'])->get();

        // Calculate budget summaries
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

        // Fetch distinct project types for filters (from all pending reports)
        $projectTypesQuery = DPReport::whereIn('project_id', $projectIds);

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'draft') {
                $projectTypesQuery->where('status', DPReport::STATUS_DRAFT);
            } elseif ($status === 'reverted') {
                // Filter for all reverted statuses
                $projectTypesQuery->where(function($query) {
                    $query->whereIn('status', [
                        DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                        DPReport::STATUS_REVERTED_BY_COORDINATOR,
                        DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
                        DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
                        DPReport::STATUS_REVERTED_TO_EXECUTOR,
                        DPReport::STATUS_REVERTED_TO_APPLICANT,
                        DPReport::STATUS_REVERTED_TO_PROVINCIAL,
                        DPReport::STATUS_REVERTED_TO_COORDINATOR,
                    ]);
                });
            } else {
                $projectTypesQuery->where('status', $status);
            }
        } else {
            $projectTypesQuery->whereIn('status', [
                DPReport::STATUS_DRAFT,
                DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_COORDINATOR,
                DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
                DPReport::STATUS_REVERTED_TO_EXECUTOR,
                DPReport::STATUS_REVERTED_TO_APPLICANT,
            ]);
        }

        $projectTypes = $projectTypesQuery->distinct()->pluck('project_type');

        return view('executor.pendingReports', compact('reports', 'budgetSummaries', 'projectTypes'));
    }

    public function approvedReports(Request $request)
    {
        $user = Auth::user();

        // Get project IDs where user is owner or in-charge
        $projectIds = ProjectQueryService::getProjectIdsForUser($user);

        // Fetch approved reports for projects where user is owner or in-charge
        $reportsQuery = DPReport::whereIn('project_id', $projectIds)
                               ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);

        // Apply filtering if provided in the request
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        // Eager load relationships to prevent N+1 queries
        $reports = $reportsQuery->with(['user', 'project', 'accountDetails'])->get();

        // Calculate budget summaries
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

        // Fetch distinct project types for filters
        $projectTypes = DPReport::whereIn('project_id', $projectIds)
                               ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                               ->distinct()
                               ->pluck('project_type');

        return view('executor.approvedReports', compact('reports', 'budgetSummaries', 'projectTypes'));
    }

    private function calculateBudgetSummaries($reports, $request)
    {
        $budgetSummaries = [
            'by_project_type' => [],
            'total' => [
                'total_budget' => 0,
                'total_expenses' => 0,
                'total_remaining' => 0
            ]
        ];

        foreach ($reports as $report) {
            // Only include approved reports
            if (!$report->isApproved()) continue;
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
            'total' => [
                'total_budget' => 0,
                'approved_expenses' => 0,
                'unapproved_expenses' => 0,
                'total_expenses' => 0,
                'total_remaining' => 0
            ]
        ];

        foreach ($projects as $project) {
            // Calculate project budget
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

            // Calculate approved and unapproved expenses separately (following expenses tracking guidelines)
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

                    $reportExpenses = $report->accountDetails->sum('total_expenses') ?? 0;

                    // Separate approved vs unapproved expenses based on report status
                    if ($report->isApproved()) {
                        $approvedExpenses += $reportExpenses;
                    } else {
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

            // Add to total summary
            $budgetSummaries['total']['total_budget'] += $projectBudget;
            $budgetSummaries['total']['approved_expenses'] += $approvedExpenses;
            $budgetSummaries['total']['unapproved_expenses'] += $unapprovedExpenses;
            $budgetSummaries['total']['total_expenses'] += $totalExpenses;
            $budgetSummaries['total']['total_remaining'] += $remainingBudget;
        }

        return $budgetSummaries;
    }

    /**
     * Get action items for dashboard widget
     * Returns pending reports, reverted projects, and overdue reports
     */
    private function getActionItems($user)
    {
        // Get project IDs where user is owner or in-charge
        $projectIds = ProjectQueryService::getProjectIdsForUser($user);

        // Pending reports (draft, reverted)
        $pendingReports = DPReport::whereIn('project_id', $projectIds)
            ->whereIn('status', [
                DPReport::STATUS_DRAFT,
                DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_COORDINATOR,
                DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
                DPReport::STATUS_REVERTED_TO_EXECUTOR,
                DPReport::STATUS_REVERTED_TO_APPLICANT,
            ])
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->get();

        // Reverted projects (all reverted statuses)
        $revertedProjects = ProjectQueryService::getRevertedProjectsForUser($user)
            ->sortByDesc('updated_at')
            ->values();

        // Overdue reports (reports that should have been submitted)
        // Monthly reports are typically due by end of month following the report month
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();
        $overdueReports = collect();

        // Get approved projects that should have reports
        $approvedProjects = ProjectQueryService::getApprovedProjectsForUser($user);

        foreach ($approvedProjects as $project) {
            // Check if last month's report exists and is not approved
            $lastMonthReport = DPReport::where('project_id', $project->project_id)
                ->where('report_month_year', $lastMonth->format('Y-m'))
                ->first();

            // If no report exists or report is still draft and past due
            $dueDate = $now->copy()->endOfMonth(); // Reports due by end of current month
            $isPastDue = $now->gt($dueDate);
            $daysUntilDue = $now->diffInDays($dueDate, false);
            $daysOverdue = $daysUntilDue < 0 ? abs($daysUntilDue) : 0;

            // Only add to overdue if: (no report AND past due) OR (draft AND past due)
            if ($isPastDue && (!$lastMonthReport ||
                ($lastMonthReport->status === DPReport::STATUS_DRAFT))) {
                $overdueReports->push([
                    'project' => $project,
                    'due_date' => $dueDate,
                    'report_month' => $lastMonth->format('F Y'),
                    'days_overdue' => $daysOverdue,
                ]);
            }
        }

        return [
            'pending_reports' => $pendingReports,
            'reverted_projects' => $revertedProjects,
            'overdue_reports' => $overdueReports,
            'total_pending' => $pendingReports->count() + $revertedProjects->count() + $overdueReports->count(),
        ];
    }

    /**
     * Get projects requiring attention (draft, reverted statuses)
     */
    private function getProjectsRequiringAttention($user)
    {
        $projects = ProjectQueryService::getEditableProjectsForUser($user, ['user'])
            ->sortByDesc('updated_at')
            ->values();

        // Group by status
        $grouped = [
            'draft' => $projects->where('status', ProjectStatus::DRAFT),
            'reverted' => $projects->filter(function($project) {
                return str_contains($project->status, 'reverted');
            }),
            'total' => $projects->count(),
        ];

        return [
            'projects' => $projects,
            'grouped' => $grouped,
            'total' => $projects->count(),
        ];
    }

    /**
     * Get reports requiring attention (draft, reverted)
     */
    private function getReportsRequiringAttention($user)
    {
        // Get project IDs where user is owner or in-charge
        $projectIds = ProjectQueryService::getProjectIdsForUser($user);

        // Get reports that need work
        $reports = DPReport::whereIn('project_id', $projectIds)
            ->whereIn('status', [
                DPReport::STATUS_DRAFT,
                DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_COORDINATOR,
                DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
                DPReport::STATUS_REVERTED_TO_EXECUTOR,
                DPReport::STATUS_REVERTED_TO_APPLICANT,
            ])
            ->with(['project', 'user'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // Group by status
        $grouped = [
            'draft' => $reports->where('status', DPReport::STATUS_DRAFT),
            'reverted' => $reports->filter(function($report) {
                return str_contains($report->status, 'reverted');
            }),
            'total' => $reports->count(),
        ];

        return [
            'reports' => $reports,
            'grouped' => $grouped,
            'total' => $reports->count(),
        ];
    }

    /**
     * Get report status summary for dashboard widget
     */
    private function getReportStatusSummary($user)
    {
        // Get project IDs where user is owner or in-charge
        $projectIds = ProjectQueryService::getProjectIdsForUser($user);

        // Get monthly reports grouped by status
        $monthlyReports = DPReport::whereIn('project_id', $projectIds)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Initialize all statuses with 0 count
        $statuses = [
            DPReport::STATUS_DRAFT => 0,
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL => 0,
            DPReport::STATUS_FORWARDED_TO_COORDINATOR => 0,
            DPReport::STATUS_APPROVED_BY_COORDINATOR => 0,
            DPReport::STATUS_REVERTED_BY_PROVINCIAL => 0,
            DPReport::STATUS_REVERTED_BY_COORDINATOR => 0,
        ];

        foreach ($monthlyReports as $status => $data) {
            if (isset($statuses[$status])) {
                $statuses[$status] = $data->count;
            }
        }

        return [
            'monthly' => $statuses,
            'total' => array_sum($statuses),
        ];
    }

    /**
     * Get upcoming deadlines for dashboard widget
     */
    private function getUpcomingDeadlines($user)
    {
        $now = Carbon::now();
        $currentMonth = $now->format('Y-m');
        $nextMonth = $now->copy()->addMonth()->format('Y-m');

        // Get approved projects
        $approvedProjects = ProjectQueryService::getApprovedProjectsForUser($user);

        $thisMonthDeadlines = collect();
        $nextMonthDeadlines = collect();
        $overdueDeadlines = collect();

        foreach ($approvedProjects as $project) {
            // Check for last month's report (due this month)
            $lastMonth = $now->copy()->subMonth();
            $lastMonthReport = DPReport::where('project_id', $project->project_id)
                ->where('report_month_year', $lastMonth->format('Y-m'))
                ->where('status', '!=', DPReport::STATUS_DRAFT)
                ->first();

            $dueDate = $now->copy()->endOfMonth();
            $daysUntilDue = $now->diffInDays($dueDate, false);

            if (!$lastMonthReport ||
                $lastMonthReport->status === DPReport::STATUS_DRAFT) {
                if ($daysUntilDue < 0) {
                    // Overdue
                    $overdueDeadlines->push([
                        'project' => $project,
                        'report_month' => $lastMonth->format('F Y'),
                        'due_date' => $dueDate,
                        'days_overdue' => abs($daysUntilDue),
                    ]);
                } else {
                    // Due this month
                    $thisMonthDeadlines->push([
                        'project' => $project,
                        'report_month' => $lastMonth->format('F Y'),
                        'due_date' => $dueDate,
                        'days_remaining' => $daysUntilDue,
                    ]);
                }
            }

            // Check for current month's report (due next month)
            $currentMonthReport = DPReport::where('project_id', $project->project_id)
                ->where('report_month_year', $currentMonth)
                ->where('status', '!=', DPReport::STATUS_DRAFT)
                ->first();

            $nextMonthDueDate = $now->copy()->addMonth()->endOfMonth();
            if (!$currentMonthReport ||
                $currentMonthReport->status === DPReport::STATUS_DRAFT) {
                $nextMonthDeadlines->push([
                    'project' => $project,
                    'report_month' => $now->format('F Y'),
                    'due_date' => $nextMonthDueDate,
                    'days_remaining' => $now->diffInDays($nextMonthDueDate, false),
                ]);
            }
        }

        return [
            'this_month' => $thisMonthDeadlines,
            'next_month' => $nextMonthDeadlines,
            'overdue' => $overdueDeadlines,
            'total' => $thisMonthDeadlines->count() + $nextMonthDeadlines->count() + $overdueDeadlines->count(),
        ];
    }

    /**
     * Enhance projects with metadata (budget utilization, health, last report date)
     */
    private function enhanceProjectsWithMetadata($projects)
    {
        $enhanced = [];

        foreach ($projects as $project) {
            // Calculate budget
            $projectBudget = 0;
            if ($project->overall_project_budget && $project->overall_project_budget > 0) {
                $projectBudget = $project->overall_project_budget;
            } elseif ($project->amount_sanctioned && $project->amount_sanctioned > 0) {
                $projectBudget = $project->amount_sanctioned;
            } elseif ($project->budgets && $project->budgets->count() > 0) {
                $projectBudget = $project->budgets->sum('this_phase');
            }

            // Calculate expenses from approved reports
            $totalExpenses = 0;
            $lastReportDate = null;
            if ($project->reports && $project->reports->count() > 0) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails && $report->accountDetails->count() > 0) {
                        $totalExpenses += $report->accountDetails->sum('total_expenses');
                    }
                    // Get latest report date
                    if (!$lastReportDate || $report->created_at > $lastReportDate) {
                        $lastReportDate = $report->created_at;
                    }
                }
            }

            // Calculate budget utilization percentage
            $budgetUtilization = $projectBudget > 0 ? ($totalExpenses / $projectBudget) * 100 : 0;
            $remainingBudget = $projectBudget - $totalExpenses;

            // Calculate project health
            $health = $this->calculateProjectHealth($project, $budgetUtilization, $lastReportDate);

            $enhanced[$project->project_id] = [
                'budget' => $projectBudget,
                'expenses' => $totalExpenses,
                'remaining' => $remainingBudget,
                'utilization_percent' => round($budgetUtilization, 2),
                'health' => $health,
                'health_score' => $health['score'],
                'health_level' => $health['level'],
                'last_report_date' => $lastReportDate,
            ];
        }

        return $enhanced;
    }

    /**
     * Calculate project health based on multiple factors
     */
    private function calculateProjectHealth($project, $budgetUtilization, $lastReportDate)
    {
        $health = 100; // Start with perfect health
        $factors = [];

        // Budget utilization (0-40 points)
        if ($budgetUtilization > 90) {
            $health -= 40;
            $factors[] = 'Budget over 90% utilized';
        } elseif ($budgetUtilization > 75) {
            $health -= 20;
            $factors[] = 'Budget over 75% utilized';
        } elseif ($budgetUtilization > 50) {
            $health -= 10;
            $factors[] = 'Budget over 50% utilized';
        }

        // Report submission timeliness (0-30 points)
        if ($lastReportDate) {
            $daysSinceLastReport = Carbon::now()->diffInDays($lastReportDate);
            if ($daysSinceLastReport > 60) {
                $health -= 30;
                $factors[] = 'No report in over 60 days';
            } elseif ($daysSinceLastReport > 30) {
                $health -= 15;
                $factors[] = 'No report in over 30 days';
            }
        } else {
            // No reports at all
            $health -= 25;
            $factors[] = 'No reports submitted';
        }

        // Status issues (0-30 points)
        if (ProjectStatus::isReverted($project->status)) {
            // Determine severity based on revert type
            if ($project->status === ProjectStatus::REVERTED_BY_COORDINATOR) {
                $health -= 30;
                $factors[] = 'Reverted by coordinator';
            } elseif ($project->status === ProjectStatus::REVERTED_BY_PROVINCIAL) {
                $health -= 15;
                $factors[] = 'Reverted by provincial';
            } else {
                // Other revert statuses
                $health -= 10;
                $factors[] = 'Project reverted';
            }
        }

        // Ensure health is between 0 and 100
        $health = max(0, min(100, $health));

        // Determine health level
        if ($health >= 80) {
            $level = 'good';
            $color = 'success';
            $icon = 'check-circle';
        } elseif ($health >= 50) {
            $level = 'warning';
            $color = 'warning';
            $icon = 'alert-triangle';
        } else {
            $level = 'critical';
            $color = 'danger';
            $icon = 'x-circle';
        }

        return [
            'score' => $health,
            'level' => $level,
            'color' => $color,
            'icon' => $icon,
            'factors' => $factors,
        ];
    }

    /**
     * Get chart data for visual analytics
     */
    private function getChartData($user, $request)
    {
        // Get project IDs where user is owner or in-charge
        $projectIds = ProjectQueryService::getProjectIdsForUser($user);

        // Budget by Project Type Data (for pie/donut chart)
        $budgetByType = [];
        $expensesByType = [];

        $projects = ProjectQueryService::getApprovedProjectsForUser($user, ['reports.accountDetails', 'budgets']);

        foreach ($projects as $project) {
            // Calculate budget
            $projectBudget = 0;
            if ($project->overall_project_budget && $project->overall_project_budget > 0) {
                $projectBudget = $project->overall_project_budget;
            } elseif ($project->amount_sanctioned && $project->amount_sanctioned > 0) {
                $projectBudget = $project->amount_sanctioned;
            } elseif ($project->budgets && $project->budgets->count() > 0) {
                $projectBudget = $project->budgets->sum('this_phase');
            }

            // Calculate expenses from approved reports
            $totalExpenses = 0;
            if ($project->reports && $project->reports->count() > 0) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails && $report->accountDetails->count() > 0) {
                        $totalExpenses += $report->accountDetails->sum('total_expenses');
                    }
                }
            }

            if (!isset($budgetByType[$project->project_type])) {
                $budgetByType[$project->project_type] = 0;
                $expensesByType[$project->project_type] = 0;
            }

            $budgetByType[$project->project_type] += $projectBudget;
            $expensesByType[$project->project_type] += $totalExpenses;
        }

        // Monthly Expense Trends Data (for line/area chart)
        $monthlyExpenses = [];
        $reports = DPReport::whereIn('project_id', $projectIds)
            ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
            ->with('accountDetails')
            ->orderBy('report_month_year', 'asc')
            ->get();

        foreach ($reports as $report) {
            $monthYear = $report->report_month_year ? Carbon::parse($report->report_month_year)->format('Y-m') : null;
            if ($monthYear) {
                if (!isset($monthlyExpenses[$monthYear])) {
                    $monthlyExpenses[$monthYear] = 0;
                }
                $monthlyExpenses[$monthYear] += $report->accountDetails->sum('total_expenses') ?? 0;
            }
        }

        // Sort monthly expenses by date
        ksort($monthlyExpenses);

        // Budget vs Expenses by Project Type (for stacked bar chart)
        $budgetVsExpenses = [];
        foreach ($budgetByType as $type => $budget) {
            $budgetVsExpenses[$type] = [
                'budget' => $budget,
                'expenses' => $expensesByType[$type] ?? 0,
                'remaining' => $budget - ($expensesByType[$type] ?? 0),
            ];
        }

        // Budget Utilization Timeline (for line chart)
        // This will show budget utilization over time
        $budgetUtilizationTimeline = [];
        $runningExpenses = 0;
        $totalBudget = array_sum($budgetByType);

        foreach ($monthlyExpenses as $month => $expenses) {
            $runningExpenses += $expenses;
            $utilization = $totalBudget > 0 ? ($runningExpenses / $totalBudget) * 100 : 0;
            $budgetUtilizationTimeline[$month] = [
                'expenses' => $runningExpenses,
                'budget' => $totalBudget,
                'remaining' => $totalBudget - $runningExpenses,
                'utilization' => round($utilization, 2),
            ];
        }

        return [
            'budget_by_type' => $budgetByType,
            'expenses_by_type' => $expensesByType,
            'budget_vs_expenses' => $budgetVsExpenses,
            'monthly_expenses' => $monthlyExpenses,
            'budget_utilization_timeline' => $budgetUtilizationTimeline,
            'total_budget' => $totalBudget,
            'total_expenses' => array_sum($expensesByType),
            'total_remaining' => $totalBudget - array_sum($expensesByType),
        ];
    }

    /**
     * Get report chart data for visual analytics
     */
    private function getReportChartData($user, $request)
    {
        // Get project IDs where user is owner or in-charge
        $projectIds = ProjectQueryService::getProjectIdsForUser($user);

        // Report Status Distribution
        $reportStatusCounts = DPReport::whereIn('project_id', $projectIds)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(function($item) {
                return $item->count;
            })
            ->toArray();

        // Initialize all statuses with 0 count
        $statusCounts = [
            DPReport::STATUS_DRAFT => 0,
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL => 0,
            DPReport::STATUS_FORWARDED_TO_COORDINATOR => 0,
            DPReport::STATUS_APPROVED_BY_COORDINATOR => 0,
            DPReport::STATUS_REVERTED_BY_PROVINCIAL => 0,
            DPReport::STATUS_REVERTED_BY_COORDINATOR => 0,
        ];

        foreach ($reportStatusCounts as $status => $count) {
            if (isset($statusCounts[$status])) {
                $statusCounts[$status] = $count;
            }
        }

        // Report Submission Timeline (Monthly reports over time)
        $monthlyReportCounts = DPReport::whereIn('project_id', $projectIds)
            ->whereNotNull('report_month_year')
            ->selectRaw('DATE_FORMAT(report_month_year, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month')
            ->map(function($item) {
                return $item->count;
            })
            ->toArray();

        // Report Completion Rate (Approved vs Total)
        $totalReports = DPReport::whereIn('project_id', $projectIds)->count();
        $approvedReports = DPReport::whereIn('project_id', $projectIds)
            ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
            ->count();
        $completionRate = $totalReports > 0 ? ($approvedReports / $totalReports) * 100 : 0;

        // Reports by Type (if we track report types)
        $reportsByProjectType = DPReport::whereIn('project_id', $projectIds)
            ->selectRaw('project_type, COUNT(*) as count')
            ->groupBy('project_type')
            ->get()
            ->keyBy('project_type')
            ->map(function($item) {
                return $item->count;
            })
            ->toArray();

        return [
            'status_distribution' => $statusCounts,
            'monthly_submission_timeline' => $monthlyReportCounts,
            'completion_rate' => round($completionRate, 2),
            'total_reports' => $totalReports,
            'approved_reports' => $approvedReports,
            'reports_by_type' => $reportsByProjectType,
        ];
    }

    /**
     * Get quick stats for dashboard widget
     */
    private function getQuickStats($user)
    {
        // Get project IDs where user is owner or in-charge
        $projectIds = ProjectQueryService::getProjectIdsForUser($user);

        // Total projects
        $totalProjects = ProjectQueryService::getProjectsForUserQuery($user)->count();

        // Active projects (approved)
        $activeProjects = ProjectQueryService::getApprovedProjectsForUser($user)->count();

        // Total reports
        $totalReports = DPReport::whereIn('project_id', $projectIds)->count();

        // Approved reports
        $approvedReports = DPReport::whereIn('project_id', $projectIds)
            ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
            ->count();

        // Approval rate
        $approvalRate = $totalReports > 0 ? ($approvedReports / $totalReports) * 100 : 0;

        // Projects created this month
        $thisMonth = Carbon::now()->startOfMonth();
        $newProjectsThisMonth = ProjectQueryService::getProjectsForUserQuery($user)
            ->where('created_at', '>=', $thisMonth)
            ->count();

        // Total budget (from approved projects)
        $approvedProjects = ProjectQueryService::getApprovedProjectsForUser($user, ['reports.accountDetails', 'budgets']);

        $totalBudget = 0;
        $totalExpenses = 0;
        foreach ($approvedProjects as $project) {
            $projectBudget = 0;
            if ($project->overall_project_budget && $project->overall_project_budget > 0) {
                $projectBudget = $project->overall_project_budget;
            } elseif ($project->amount_sanctioned && $project->amount_sanctioned > 0) {
                $projectBudget = $project->amount_sanctioned;
            } elseif ($project->budgets && $project->budgets->count() > 0) {
                $projectBudget = $project->budgets->sum('this_phase');
            }
            $totalBudget += $projectBudget;

            if ($project->reports && $project->reports->count() > 0) {
                foreach ($project->reports as $report) {
                    if ($report->isApproved() && $report->accountDetails && $report->accountDetails->count() > 0) {
                        $totalExpenses += $report->accountDetails->sum('total_expenses');
                    }
                }
            }
        }

        $budgetUtilization = $totalBudget > 0 ? ($totalExpenses / $totalBudget) * 100 : 0;
        $averageProjectBudget = $activeProjects > 0 ? $totalBudget / $activeProjects : 0;

        // Calculate trends (vs last month)
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $projectsLastMonth = ProjectQueryService::getProjectsForUserQuery($user)
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->count();

        $reportsLastMonth = DPReport::whereIn('project_id', $projectIds)
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->count();

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'total_reports' => $totalReports,
            'approved_reports' => $approvedReports,
            'approval_rate' => round($approvalRate, 1),
            'new_projects_this_month' => $newProjectsThisMonth,
            'budget_utilization' => round($budgetUtilization, 1),
            'average_project_budget' => $averageProjectBudget,
            'total_budget' => $totalBudget,
            'total_expenses' => $totalExpenses,
            'projects_trend' => $newProjectsThisMonth - $projectsLastMonth,
            'reports_trend' => 0, // Can calculate if needed
        ];
    }

    /**
     * Get recent activities for activity feed widget
     */
    private function getRecentActivities($user, $limit = 10)
    {
        return \App\Services\ActivityHistoryService::getForExecutor($user)
            ->take($limit);
    }

    /**
     * Get project health summary
     */
    private function getProjectHealthSummary($enhancedProjects)
    {
        if (empty($enhancedProjects)) {
            return [
                'good' => 0,
                'warning' => 0,
                'critical' => 0,
                'total' => 0,
            ];
        }

        $healthCounts = [
            'good' => 0,
            'warning' => 0,
            'critical' => 0,
        ];

        foreach ($enhancedProjects as $metadata) {
            if (isset($metadata['health_level'])) {
                $level = $metadata['health_level'];
                if (isset($healthCounts[$level])) {
                    $healthCounts[$level]++;
                }
            }
        }

        return [
            'good' => $healthCounts['good'] ?? 0,
            'warning' => $healthCounts['warning'] ?? 0,
            'critical' => $healthCounts['critical'] ?? 0,
            'total' => array_sum($healthCounts),
        ];
    }
}
