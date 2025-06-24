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

        // Fetch reports for this executor
        $reportsQuery = DPReport::where('user_id', $executor->id);

        // Apply filtering if provided in the request
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        $reports = $reportsQuery->with('accountDetails')->get();

        // Calculate budget summaries
        $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

        // Get the authenticated user's projects
        $projects = Project::where('user_id', Auth::id())->get();

        // Fetch distinct project types for filters
        $projectTypes = DPReport::where('user_id', $executor->id)->distinct()->pluck('project_type');

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

            // Update total summary
            $budgetSummaries['total']['total_budget'] += $reportTotal;
            $budgetSummaries['total']['total_expenses'] += $reportExpenses;
            $budgetSummaries['total']['total_remaining'] += $reportRemaining;
        }

        return $budgetSummaries;
    }
}
