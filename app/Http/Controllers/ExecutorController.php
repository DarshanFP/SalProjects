<?php

namespace App\Http\Controllers;

use App\Models\OldProjects\Project;
use App\Models\Reports\Quarterly\RQDPReport;
use App\Models\Reports\Monthly\DPReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExecutorController extends Controller
{
    //
    public function ExecutorDashboard(Request $request)
    {
        $executor = Auth::user();

        // Get the authenticated user's projects that are approved by coordinator
        $projectsQuery = Project::where('user_id', Auth::id())
                               ->where('status', 'approved_by_coordinator');

        // Apply filtering if provided in the request
        if ($request->filled('project_type')) {
            $projectsQuery->where('project_type', $request->project_type);
        }

        $projects = $projectsQuery->with(['reports.accountDetails', 'budgets'])->get();

        // Calculate budget summaries from projects and their reports
        $budgetSummaries = $this->calculateBudgetSummariesFromProjects($projects, $request);

        // Fetch distinct project types for filters
        $projectTypes = Project::where('user_id', Auth::id())
                              ->where('status', 'approved_by_coordinator')
                              ->distinct()
                              ->pluck('project_type');

        // Pass the projects to the executor index view
        return view('executor.index', compact('projects', 'budgetSummaries', 'projectTypes'));
    }

    public function ReportList(Request $request)
    {
        $executor = Auth::user();

        // Fetch reports for this executor
        $reportsQuery = DPReport::where('user_id', $executor->id);

        // Apply filtering if provided in the request
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        $reports = $reportsQuery->with('accountDetails')->get();

        // Calculate budget summaries
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

        // Fetch distinct project types for filters
        $projectTypes = DPReport::where('user_id', $executor->id)->distinct()->pluck('project_type');

        return view('executor.ReportList', compact('reports', 'budgetSummaries', 'projectTypes'));
    }

    public function submitReport(Request $request, $report_id)
    {
        $report = DPReport::where('report_id', $report_id)
                         ->where('user_id', Auth::id())
                         ->firstOrFail();

        // Check if report is in underwriting status
        if ($report->status !== 'underwriting') {
            return redirect()->back()->with('error', 'Report can only be submitted when in underwriting status.');
        }

        // Update report status to submitted_to_provincial
        $report->update([
            'status' => 'submitted_to_provincial'
        ]);

        return redirect()->route('executor.report.list')->with('success', 'Report submitted to provincial successfully.');
    }

    public function pendingReports(Request $request)
    {
        $executor = Auth::user();

        // Fetch pending reports for this executor (underwriting, submitted_to_provincial, reverted_by_provincial, reverted_by_coordinator)
        $reportsQuery = DPReport::where('user_id', $executor->id)
                               ->whereIn('status', ['underwriting', 'submitted_to_provincial', 'reverted_by_provincial', 'reverted_by_coordinator']);

        // Apply filtering if provided in the request
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        $reports = $reportsQuery->with('accountDetails')->get();

        // Calculate budget summaries
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

        // Fetch distinct project types for filters
        $projectTypes = DPReport::where('user_id', $executor->id)
                               ->whereIn('status', ['underwriting', 'submitted_to_provincial', 'reverted_by_provincial', 'reverted_by_coordinator'])
                               ->distinct()
                               ->pluck('project_type');

        return view('executor.pendingReports', compact('reports', 'budgetSummaries', 'projectTypes'));
    }

    public function approvedReports(Request $request)
    {
        $executor = Auth::user();

        // Fetch approved reports for this executor
        $reportsQuery = DPReport::where('user_id', $executor->id)
                               ->where('status', 'approved_by_coordinator');

        // Apply filtering if provided in the request
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        $reports = $reportsQuery->with('accountDetails')->get();

        // Calculate budget summaries
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

        // Fetch distinct project types for filters
        $projectTypes = DPReport::where('user_id', $executor->id)
                               ->where('status', 'approved_by_coordinator')
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
            if ($report->status !== 'approved_by_coordinator') continue;
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
            $budgetSummaries['total']['total_budget'] += $projectBudget;
            $budgetSummaries['total']['total_expenses'] += $totalExpenses;
            $budgetSummaries['total']['total_remaining'] += $remainingBudget;
        }
        return $budgetSummaries;
    }
}
