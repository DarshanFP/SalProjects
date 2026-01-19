<?php

namespace App\Http\Controllers\Reports\Aggregated;

use App\Http\Controllers\Controller;
use App\Models\Reports\Quarterly\QuarterlyReport;
use App\Models\Reports\HalfYearly\HalfYearlyReport;
use App\Models\Reports\Annual\AnnualReport;
use App\Services\AI\ReportComparisonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportComparisonController extends Controller
{
    /**
     * Show comparison form for quarterly reports
     */
    public function compareQuarterlyForm(Request $request)
    {
        $user = Auth::user();

        $query = QuarterlyReport::with(['project'])->where('status', 'approved_by_coordinator');

        if (in_array($user->role, ['executor', 'applicant'])) {
            // Executors/applicants see reports for projects they own or are in-charge of
            $projectIds = \App\Models\OldProjects\Project::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('in_charge', $user->id);
            })->pluck('project_id');
            $query->whereIn('project_id', $projectIds);
        } elseif ($user->role === 'provincial') {
            $executorIds = \App\Models\User::where('province', $user->province)
                ->where('role', 'executor')
                ->pluck('id');
            $query->whereIn('generated_by_user_id', $executorIds);
        }

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $reports = $query->orderBy('year', 'desc')
            ->orderBy('quarter', 'desc')
            ->get();

        return view('reports.aggregated.comparison.quarterly-form', compact('reports'));
    }

    /**
     * Compare two quarterly reports (handles both GET and POST)
     */
    public function compareQuarterly(Request $request)
    {
        // Handle GET request - show form
        if ($request->isMethod('get')) {
            return $this->compareQuarterlyForm($request);
        }

        // Handle POST request - process comparison
        $request->validate([
            'report1_id' => 'required|exists:quarterly_reports,report_id',
            'report2_id' => 'required|exists:quarterly_reports,report_id',
        ]);

        $report1 = QuarterlyReport::findOrFail($request->report1_id);
        $report2 = QuarterlyReport::findOrFail($request->report2_id);

        // Check permissions
        $user = Auth::user();
        if (in_array($user->role, ['executor', 'applicant'])) {
            // Check if user owns or is in-charge of both projects
            $project1 = $report1->project;
            $project2 = $report2->project;
            $hasAccess1 = $project1 && ($project1->user_id === $user->id || $project1->in_charge === $user->id);
            $hasAccess2 = $project2 && ($project2->user_id === $user->id || $project2->in_charge === $user->id);
            if (!$hasAccess1 || !$hasAccess2) {
                abort(403, 'Unauthorized');
            }
        }

        try {
            $comparison = ReportComparisonService::compareQuarterlyReports($report1, $report2);

            return view('reports.aggregated.comparison.quarterly-result', [
                'report1' => $report1,
                'report2' => $report2,
                'comparison' => $comparison
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to compare quarterly reports', [
                'report1_id' => $request->report1_id,
                'report2_id' => $request->report2_id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to compare reports: ' . $e->getMessage());
        }
    }

    /**
     * Show comparison form for half-yearly reports
     */
    public function compareHalfYearlyForm(Request $request)
    {
        $user = Auth::user();

        $query = HalfYearlyReport::with(['project'])->where('status', 'approved_by_coordinator');

        if (in_array($user->role, ['executor', 'applicant'])) {
            // Executors/applicants see reports for projects they own or are in-charge of
            $projectIds = \App\Models\OldProjects\Project::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('in_charge', $user->id);
            })->pluck('project_id');
            $query->whereIn('project_id', $projectIds);
        } elseif ($user->role === 'provincial') {
            $executorIds = \App\Models\User::where('province', $user->province)
                ->where('role', 'executor')
                ->pluck('id');
            $query->whereIn('generated_by_user_id', $executorIds);
        }

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $reports = $query->orderBy('year', 'desc')
            ->orderBy('half_year', 'desc')
            ->get();

        return view('reports.aggregated.comparison.half-yearly-form', compact('reports'));
    }

    /**
     * Compare two half-yearly reports (handles both GET and POST)
     */
    public function compareHalfYearly(Request $request)
    {
        // Handle GET request - show form
        if ($request->isMethod('get')) {
            return $this->compareHalfYearlyForm($request);
        }

        // Handle POST request - process comparison
        $request->validate([
            'report1_id' => 'required|exists:half_yearly_reports,report_id',
            'report2_id' => 'required|exists:half_yearly_reports,report_id',
        ]);

        $report1 = HalfYearlyReport::findOrFail($request->report1_id);
        $report2 = HalfYearlyReport::findOrFail($request->report2_id);

        $user = Auth::user();
        if (in_array($user->role, ['executor', 'applicant'])) {
            if ($report1->generated_by_user_id !== $user->id || $report2->generated_by_user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }
        }

        try {
            $comparison = ReportComparisonService::compareHalfYearlyReports($report1, $report2);

            return view('reports.aggregated.comparison.half-yearly-result', [
                'report1' => $report1,
                'report2' => $report2,
                'comparison' => $comparison
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to compare half-yearly reports', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to compare reports: ' . $e->getMessage());
        }
    }

    /**
     * Show comparison form for annual reports (year-over-year)
     */
    public function compareAnnualForm(Request $request)
    {
        $user = Auth::user();

        $query = AnnualReport::with(['project'])->where('status', 'approved_by_coordinator');

        if (in_array($user->role, ['executor', 'applicant'])) {
            // Executors/applicants see reports for projects they own or are in-charge of
            $projectIds = \App\Models\OldProjects\Project::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('in_charge', $user->id);
            })->pluck('project_id');
            $query->whereIn('project_id', $projectIds);
        } elseif ($user->role === 'provincial') {
            $executorIds = \App\Models\User::where('province', $user->province)
                ->where('role', 'executor')
                ->pluck('id');
            $query->whereIn('generated_by_user_id', $executorIds);
        }

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $reports = $query->orderBy('year', 'desc')->get();

        return view('reports.aggregated.comparison.annual-form', compact('reports'));
    }

    /**
     * Compare two annual reports (year-over-year) (handles both GET and POST)
     */
    public function compareAnnual(Request $request)
    {
        // Handle GET request - show form
        if ($request->isMethod('get')) {
            return $this->compareAnnualForm($request);
        }

        // Handle POST request - process comparison
        $request->validate([
            'report1_id' => 'required|exists:annual_reports,report_id',
            'report2_id' => 'required|exists:annual_reports,report_id',
        ]);

        $report1 = AnnualReport::findOrFail($request->report1_id);
        $report2 = AnnualReport::findOrFail($request->report2_id);

        $user = Auth::user();
        if (in_array($user->role, ['executor', 'applicant'])) {
            if ($report1->generated_by_user_id !== $user->id || $report2->generated_by_user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }
        }

        try {
            $comparison = ReportComparisonService::compareYearOverYear($report1, $report2);

            return view('reports.aggregated.comparison.annual-result', [
                'report1' => $report1,
                'report2' => $report2,
                'comparison' => $comparison
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to compare annual reports', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to compare reports: ' . $e->getMessage());
        }
    }
}
