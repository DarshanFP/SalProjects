<?php

namespace App\Http\Controllers\Reports\Aggregated;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Aggregated\AggregatedReportExportController;
use App\Models\OldProjects\Project;
use App\Models\Reports\HalfYearly\HalfYearlyReport;
use App\Models\Reports\AI\AIReportInsight;
use App\Models\Reports\AI\AIReportTitle;
use App\Services\Reports\HalfYearlyReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AggregatedHalfYearlyReportController extends Controller
{
    /**
     * Display a listing of half-yearly reports
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = HalfYearlyReport::with(['project', 'generatedBy', 'aiInsights', 'aiTitle']);

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
        if ($request->has('half_year')) {
            $query->where('half_year', $request->half_year);
        }
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        $reports = $query->orderBy('year', 'desc')
            ->orderBy('half_year', 'desc')
            ->paginate(20);

        return view('reports.aggregated.half-yearly.index', compact('reports'));
    }

    /**
     * Show the form for creating a new half-yearly report
     */
    public function create($project_id)
    {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $user = Auth::user();

        if (!in_array($user->role, ['executor', 'applicant', 'coordinator', 'provincial'])) {
            abort(403, 'Unauthorized');
        }

        // Get available quarterly/monthly reports
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

        return view('reports.aggregated.half-yearly.create', compact('project', 'quarterlyReports', 'monthlyReports'));
    }

    /**
     * Store a newly created half-yearly report
     */
    public function store(Request $request, $project_id)
    {
        $request->validate([
            'half_year' => 'required|integer|min:1|max:2',
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'use_ai' => 'nullable|boolean',
        ]);

        $project = Project::where('project_id', $project_id)->firstOrFail();
        $user = Auth::user();

        try {
            DB::beginTransaction();

            $useAI = $request->boolean('use_ai', true);
            $halfYearlyReport = HalfYearlyReportService::generateHalfYearlyReportWithAI(
                $project,
                $request->half_year,
                $request->year,
                $user,
                $useAI
            );

            DB::commit();

            return redirect()->route('aggregated.half-yearly.show', $halfYearlyReport->report_id)
                ->with('success', 'Half-yearly report generated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create half-yearly report', [
                'project_id' => $project_id,
                'error' => $e->getMessage()
            ]);

            return back()->withInput()
                ->with('error', 'Failed to generate half-yearly report: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified half-yearly report
     */
    public function show($report_id)
    {
        $report = HalfYearlyReport::with([
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

        return view('reports.aggregated.half-yearly.show', compact('report'));
    }

    /**
     * Show the form for editing AI content
     */
    public function editAI($report_id)
    {
        $report = HalfYearlyReport::with(['aiInsights', 'aiTitle'])->findOrFail($report_id);

        $user = Auth::user();
        if (!in_array($user->role, ['executor', 'applicant', 'coordinator', 'provincial'])) {
            abort(403, 'Unauthorized');
        }

        if (!$report->isEditable()) {
            return back()->with('error', 'This report cannot be edited in its current status.');
        }

        if (!$report->aiInsights) {
            try {
                $aiInsights = HalfYearlyReportService::getAIInsights($report);
                HalfYearlyReportService::storeAIInsights($report, $aiInsights);
                $report->refresh();
            } catch (\Exception $e) {
                Log::warning('Failed to generate AI insights for editing', [
                    'report_id' => $report_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('reports.aggregated.half-yearly.edit-ai', compact('report'));
    }

    /**
     * Update AI content
     */
    public function updateAI(Request $request, $report_id)
    {
        $report = HalfYearlyReport::findOrFail($report_id);

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
            'quarterly_comparison' => 'nullable|array',
            'report_title' => 'nullable|string|max:255',
            'section_headings' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $insights = $report->aiInsights ?? AIReportInsight::create([
                'report_type' => 'half_yearly',
                'report_id' => $report->report_id,
            ]);

            $insights->executive_summary = $request->executive_summary ?? $insights->executive_summary;
            if ($request->has('key_achievements')) $insights->key_achievements = $request->key_achievements;
            if ($request->has('progress_trends')) $insights->progress_trends = $request->progress_trends;
            if ($request->has('challenges')) $insights->challenges = $request->challenges;
            if ($request->has('recommendations')) $insights->recommendations = $request->recommendations;
            if ($request->has('strategic_insights')) $insights->strategic_insights = $request->strategic_insights;
            if ($request->has('quarterly_comparison')) $insights->quarterly_comparison = $request->quarterly_comparison;
            $insights->markAsEdited();
            $insights->save();

            if ($request->has('report_title') || $request->has('section_headings')) {
                $title = $report->aiTitle ?? AIReportTitle::create([
                    'report_type' => 'half_yearly',
                    'report_id' => $report->report_id,
                ]);

                if ($request->has('report_title')) $title->report_title = $request->report_title;
                if ($request->has('section_headings')) $title->section_headings = $request->section_headings;
                $title->markAsEdited();
                $title->save();
            }

            DB::commit();

            return redirect()->route('aggregated.half-yearly.show', $report->report_id)
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
        return $exportController->exportHalfYearlyPdf($report_id);
    }

    /**
     * Export report as Word
     */
    public function exportWord($report_id)
    {
        $exportController = new AggregatedReportExportController();
        return $exportController->exportHalfYearlyWord($report_id);
    }
}
