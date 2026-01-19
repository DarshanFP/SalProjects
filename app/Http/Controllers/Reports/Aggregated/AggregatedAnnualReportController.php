<?php

namespace App\Http\Controllers\Reports\Aggregated;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Aggregated\AggregatedReportExportController;
use App\Models\OldProjects\Project;
use App\Models\Reports\Annual\AnnualReport;
use App\Models\Reports\AI\AIReportInsight;
use App\Models\Reports\AI\AIReportTitle;
use App\Services\Reports\AnnualReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AggregatedAnnualReportController extends Controller
{
    /**
     * Display a listing of annual reports
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = AnnualReport::with(['project', 'generatedBy', 'aiInsights', 'aiTitle']);

        if (in_array($user->role, ['executor', 'applicant'])) {
            // Executors/applicants see reports for projects they own or are in-charge of
            $projectIds = Project::where(function($q) use ($user) {
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
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        $reports = $query->orderBy('year', 'desc')->paginate(20);

        return view('reports.aggregated.annual.index', compact('reports'));
    }

    /**
     * Show the form for creating a new annual report
     */
    public function create($project_id)
    {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $user = Auth::user();

        if (!in_array($user->role, ['executor', 'applicant', 'coordinator', 'provincial'])) {
            abort(403, 'Unauthorized');
        }

        // Get available reports
        $halfYearlyReports = \App\Models\Reports\HalfYearly\HalfYearlyReport::where('project_id', $project_id)
            ->where('status', 'approved_by_coordinator')
            ->orderBy('year', 'desc')
            ->orderBy('half_year', 'desc')
            ->get();

        $quarterlyReports = \App\Models\Reports\Quarterly\QuarterlyReport::where('project_id', $project_id)
            ->where('status', 'approved_by_coordinator')
            ->orderBy('year', 'desc')
            ->orderBy('quarter', 'desc')
            ->get();

        $monthlyReports = \App\Models\Reports\Monthly\DPReport::where('project_id', $project_id)
            ->where('status', 'approved_by_coordinator')
            ->orderBy('report_year', 'desc')
            ->orderBy('report_month', 'desc')
            ->get();

        return view('reports.aggregated.annual.create', compact('project', 'halfYearlyReports', 'quarterlyReports', 'monthlyReports'));
    }

    /**
     * Store a newly created annual report
     */
    public function store(Request $request, $project_id)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'use_ai' => 'nullable|boolean',
        ]);

        $project = Project::where('project_id', $project_id)->firstOrFail();
        $user = Auth::user();

        try {
            DB::beginTransaction();

            $useAI = $request->boolean('use_ai', true);
            $annualReport = AnnualReportService::generateAnnualReportWithAI(
                $project,
                $request->year,
                $user,
                $useAI
            );

            DB::commit();

            return redirect()->route('aggregated.annual.show', $annualReport->report_id)
                ->with('success', 'Annual report generated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create annual report', [
                'project_id' => $project_id,
                'error' => $e->getMessage()
            ]);

            return back()->withInput()
                ->with('error', 'Failed to generate annual report: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified annual report
     */
    public function show($report_id)
    {
        $report = AnnualReport::with([
            'project',
            'generatedBy',
            'details',
            'objectives',
            'photos',
            'aiInsights',
            'aiTitle'
        ])->findOrFail($report_id);

        $user = Auth::user();
        if (in_array($user->role, ['executor', 'applicant'])) {
            // Check if user owns or is in-charge of the project
            $project = $report->project;
            if (!$project || ($project->user_id !== $user->id && $project->in_charge !== $user->id)) {
                abort(403, 'Unauthorized');
            }
        }

        return view('reports.aggregated.annual.show', compact('report'));
    }

    /**
     * Show the form for editing AI content
     */
    public function editAI($report_id)
    {
        $report = AnnualReport::with(['aiInsights', 'aiTitle'])->findOrFail($report_id);

        $user = Auth::user();
        if (!in_array($user->role, ['executor', 'applicant', 'coordinator', 'provincial'])) {
            abort(403, 'Unauthorized');
        }

        if (!$report->isEditable()) {
            return back()->with('error', 'This report cannot be edited in its current status.');
        }

        if (!$report->aiInsights) {
            try {
                $aiInsights = AnnualReportService::getAIInsights($report);
                AnnualReportService::storeAIInsights($report, $aiInsights);
                $report->refresh();
            } catch (\Exception $e) {
                Log::warning('Failed to generate AI insights for editing', [
                    'report_id' => $report_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('reports.aggregated.annual.edit-ai', compact('report'));
    }

    /**
     * Update AI content
     */
    public function updateAI(Request $request, $report_id)
    {
        $report = AnnualReport::findOrFail($report_id);

        $user = Auth::user();
        if (!in_array($user->role, ['executor', 'applicant', 'coordinator', 'provincial'])) {
            abort(403, 'Unauthorized');
        }

        if (!$report->isEditable()) {
            return back()->with('error', 'This report cannot be edited in its current status.');
        }

        $request->validate([
            'executive_summary' => 'nullable|string',
            'key_achievements' => 'nullable|array',
            'progress_trends' => 'nullable|array',
            'challenges' => 'nullable|array',
            'recommendations' => 'nullable|array',
            'strategic_insights' => 'nullable|array',
            'impact_assessment' => 'nullable|array',
            'budget_performance' => 'nullable|array',
            'future_outlook' => 'nullable|array',
            'year_over_year_comparison' => 'nullable|array',
            'report_title' => 'nullable|string|max:255',
            'section_headings' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $insights = $report->aiInsights ?? AIReportInsight::create([
                'report_type' => 'annual',
                'report_id' => $report->report_id,
            ]);

            $insights->executive_summary = $request->executive_summary ?? $insights->executive_summary;
            if ($request->has('key_achievements')) $insights->key_achievements = $request->key_achievements;
            if ($request->has('progress_trends')) $insights->progress_trends = $request->progress_trends;
            if ($request->has('challenges')) $insights->challenges = $request->challenges;
            if ($request->has('recommendations')) $insights->recommendations = $request->recommendations;
            if ($request->has('strategic_insights')) $insights->strategic_insights = $request->strategic_insights;
            if ($request->has('impact_assessment')) $insights->impact_assessment = $request->impact_assessment;
            if ($request->has('budget_performance')) $insights->budget_performance = $request->budget_performance;
            if ($request->has('future_outlook')) $insights->future_outlook = $request->future_outlook;
            if ($request->has('year_over_year_comparison')) $insights->year_over_year_comparison = $request->year_over_year_comparison;
            $insights->markAsEdited();
            $insights->save();

            if ($request->has('report_title') || $request->has('section_headings')) {
                $title = $report->aiTitle ?? AIReportTitle::create([
                    'report_type' => 'annual',
                    'report_id' => $report->report_id,
                ]);

                if ($request->has('report_title')) $title->report_title = $request->report_title;
                if ($request->has('section_headings')) $title->section_headings = $request->section_headings;
                $title->markAsEdited();
                $title->save();
            }

            DB::commit();

            return redirect()->route('aggregated.annual.show', $report->report_id)
                ->with('success', 'AI content updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update AI content', [
                'report_id' => $report_id,
                'error' => $e->getMessage()
            ]);

            return back()->withInput()->with('error', 'Failed to update AI content: ' . $e->getMessage());
        }
    }

    /**
     * Export report as PDF
     */
    public function exportPdf($report_id)
    {
        $exportController = new AggregatedReportExportController();
        return $exportController->exportAnnualPdf($report_id);
    }

    /**
     * Export report as Word
     */
    public function exportWord($report_id)
    {
        $exportController = new AggregatedReportExportController();
        return $exportController->exportAnnualWord($report_id);
    }
}
