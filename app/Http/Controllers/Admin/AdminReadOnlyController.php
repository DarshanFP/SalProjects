<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Reports\Monthly\ReportController;
use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Reports\Monthly\DPAccountDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 4: Admin read-only visibility.
 * No mutations, no approval, no budget changes.
 * Reuses existing controller show methods where safe; builds list data for admin-only views.
 */
class AdminReadOnlyController extends Controller
{
    /**
     * Project list (read-only). Admin sees all projects.
     */
    public function projectIndex(Request $request)
    {
        $projectsQuery = Project::with(['user.parent', 'reports.accountDetails', 'budgets']);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $projectsQuery->where(function ($q) use ($searchTerm) {
                $q->where('project_id', 'like', '%' . $searchTerm . '%')
                    ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('project_type', 'like', '%' . $searchTerm . '%')
                    ->orWhere('status', 'like', '%' . $searchTerm . '%');
            });
        }
        if ($request->filled('province')) {
            $projectsQuery->whereHas('user', function ($q) use ($request) {
                $q->where('province', $request->province);
            });
        }
        if ($request->filled('status')) {
            $projectsQuery->where('status', $request->status);
        }
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
        $calc = app(\App\Services\Budget\DerivedCalculationService::class);
        $totalProjects = $projectsQuery->count();
        $perPage = $request->get('per_page', 50);
        $currentPage = (int) $request->get('page', 1);
        $projects = $projectsQuery->orderBy('created_at', 'desc')
            ->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($project) use ($resolver, $calc) {
                $financials = $resolver->resolve($project);
                $projectBudget = (float) ($financials['opening_balance'] ?? 0);
                $projectApprovedReportIds = DPReport::approved()
                    ->where('project_id', $project->project_id)
                    ->pluck('report_id');
                $totalExpenses = DPAccountDetail::whereIn('report_id', $projectApprovedReportIds)->sum('total_expenses') ?? 0;
                $project->calculated_budget = $projectBudget;
                $project->calculated_expenses = $totalExpenses;
                $project->calculated_remaining = $calc->calculateRemainingBalance($projectBudget, $totalExpenses);
                $project->budget_utilization = round($calc->calculateUtilization($totalExpenses, $projectBudget), 2);
                return $project;
            });

        $filterCacheKey = 'admin_project_list_filters';
        $filterOptions = Cache::remember($filterCacheKey, now()->addMinutes(5), function () {
            return [
                'provinces' => User::distinct()->whereNotNull('province')->pluck('province')->filter()->sort()->values(),
                'projectTypes' => Project::distinct()->whereNotNull('project_type')->pluck('project_type')->filter()->sort()->values(),
                'statuses' => array_keys(Project::$statusLabels),
            ];
        });

        $pagination = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $totalProjects,
            'last_page' => (int) ceil($totalProjects / $perPage),
            'from' => $totalProjects === 0 ? 0 : (($currentPage - 1) * $perPage) + 1,
            'to' => min($currentPage * $perPage, $totalProjects),
        ];

        return view('admin.projects.index', array_merge($filterOptions, [
            'projects' => $projects,
            'pagination' => $pagination,
        ]));
    }

    /**
     * Project show (read-only). Delegate to ProjectController::show; view uses admin layout when user is admin.
     */
    public function projectShow(string $project_id)
    {
        return app(ProjectController::class)->show($project_id);
    }

    /**
     * Report list (read-only). Admin sees all monthly reports.
     */
    public function reportIndex(Request $request)
    {
        $reportsQuery = DPReport::with(['user.parent', 'project', 'accountDetails']);

        if ($request->filled('province')) {
            $reportsQuery->whereHas('user', function ($query) use ($request) {
                $query->where('province', $request->province);
            });
        }
        if ($request->filled('status')) {
            $reportsQuery->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $reportsQuery->where(function ($q) use ($searchTerm) {
                $q->where('report_id', 'like', '%' . $searchTerm . '%')
                    ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('project_id', 'like', '%' . $searchTerm . '%');
            });
        }

        $reports = $reportsQuery->orderBy('created_at', 'desc')->get()
            ->map(function ($report) {
                if (in_array($report->status, [DPReport::STATUS_FORWARDED_TO_COORDINATOR, DPReport::STATUS_SUBMITTED_TO_PROVINCIAL])) {
                    $report->days_pending = $report->created_at->diffInDays(now());
                    $report->urgency = $report->days_pending > 7 ? 'urgent' : ($report->days_pending > 3 ? 'normal' : 'low');
                } else {
                    $report->days_pending = null;
                    $report->urgency = null;
                }
                return $report;
            });

        $perPage = $request->get('per_page', 50);
        $currentPage = (int) $request->get('page', 1);
        $totalReports = $reports->count();
        $paginatedReports = $reports->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $filterOptions = Cache::remember('admin_report_list_filters', now()->addMinutes(5), function () {
            return [
                'provinces' => User::distinct()->whereNotNull('province')->pluck('province')->filter()->sort()->values(),
                'projectTypes' => DPReport::distinct()->whereNotNull('project_type')->pluck('project_type')->filter()->sort()->values(),
                'statuses' => array_keys(DPReport::$statusLabels),
            ];
        });

        $pagination = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $totalReports,
            'last_page' => (int) ceil($totalReports / $perPage),
            'from' => $totalReports === 0 ? 0 : (($currentPage - 1) * $perPage) + 1,
            'to' => min($currentPage * $perPage, $totalReports),
        ];

        return view('admin.reports.index', array_merge($filterOptions, [
            'reports' => $paginatedReports,
            'pagination' => $pagination,
        ]));
    }

    /**
     * Monthly report show (read-only). Delegate to ReportController::show; view uses admin layout when user is admin.
     */
    public function reportShow(string $report_id)
    {
        return app(ReportController::class)->show($report_id);
    }
}
