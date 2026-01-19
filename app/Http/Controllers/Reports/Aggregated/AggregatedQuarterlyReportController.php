<?php

namespace App\Http\Controllers\Reports\Aggregated;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Aggregated\AggregatedReportExportController;
use App\Models\OldProjects\Project;
use App\Models\Reports\Quarterly\QuarterlyReport;
use App\Models\Reports\AI\AIReportInsight;
use App\Models\Reports\AI\AIReportTitle;
use App\Services\Reports\QuarterlyReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AggregatedQuarterlyReportController extends Controller
{
    /**
     * Display a listing of quarterly reports
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get reports based on user role
        $query = QuarterlyReport::with(['project', 'generatedBy', 'aiInsights', 'aiTitle']);

        if (in_array($user->role, ['executor', 'applicant'])) {
            // Executors/applicants see reports for projects they own or are in-charge of
            $projectIds = Project::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('in_charge', $user->id);
            })->pluck('project_id');
            $query->whereIn('project_id', $projectIds);
        } elseif ($user->role === 'provincial') {
            // Provincials see reports from their executors
            $executorIds = \App\Models\User::where('province', $user->province)
                ->where('role', 'executor')
                ->pluck('id');
            $query->whereIn('generated_by_user_id', $executorIds);
        }
        // Coordinators see all reports

        // Filter by project if provided
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by quarter/year if provided
        if ($request->has('quarter')) {
            $query->where('quarter', $request->quarter);
        }
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        $reports = $query->orderBy('year', 'desc')
            ->orderBy('quarter', 'desc')
            ->paginate(20);

        return view('reports.aggregated.quarterly.index', compact('reports'));
    }

    /**
     * Show the form for creating a new quarterly report
     */
    public function create($project_id)
    {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $user = Auth::user();

        // Check if user has permission to create reports for this project
        if (!in_array($user->role, ['executor', 'applicant', 'coordinator', 'provincial'])) {
            abort(403, 'Unauthorized');
        }

        // Get available monthly reports for the project
        $monthlyReports = \App\Models\Reports\Monthly\DPReport::where('project_id', $project_id)
            ->where('status', 'approved_by_coordinator')
            ->orderBy('report_year', 'desc')
            ->orderBy('report_month', 'desc')
            ->get()
            ->groupBy(function($report) {
                return $report->report_year . '-Q' . ceil($report->report_month / 3);
            });

        return view('reports.aggregated.quarterly.create', compact('project', 'monthlyReports'));
    }

    /**
     * Store a newly created quarterly report
     */
    public function store(Request $request, $project_id)
    {
        $request->validate([
            'quarter' => 'required|integer|min:1|max:4',
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'use_ai' => 'nullable|boolean',
        ]);

        $project = Project::where('project_id', $project_id)->firstOrFail();
        $user = Auth::user();

        try {
            DB::beginTransaction();

            $useAI = $request->boolean('use_ai', true);
            $quarterlyReport = QuarterlyReportService::generateQuarterlyReportWithAI(
                $project,
                $request->quarter,
                $request->year,
                $user,
                $useAI
            );

            DB::commit();

            return redirect()->route('aggregated.quarterly.show', $quarterlyReport->report_id)
                ->with('success', 'Quarterly report generated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create quarterly report', [
                'project_id' => $project_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withInput()
                ->with('error', 'Failed to generate quarterly report: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified quarterly report
     */
    public function show($report_id)
    {
        $report = QuarterlyReport::with([
            'project',
            'generatedBy',
            'details',
            'objectives',
            'photos',
            'aiInsights',
            'aiTitle'
        ])->findOrFail($report_id);

        // Check permissions
        $user = Auth::user();
        if (in_array($user->role, ['executor', 'applicant'])) {
            // Check if user owns or is in-charge of the project
            $project = $report->project;
            if (!$project || ($project->user_id !== $user->id && $project->in_charge !== $user->id)) {
                abort(403, 'Unauthorized');
            }
        }

        return view('reports.aggregated.quarterly.show', compact('report'));
    }

    /**
     * Show the form for editing AI content
     */
    public function editAI($report_id)
    {
        $report = QuarterlyReport::with(['aiInsights', 'aiTitle'])->findOrFail($report_id);

        // Check permissions
        $user = Auth::user();
        if (!in_array($user->role, ['executor', 'applicant', 'coordinator', 'provincial'])) {
            abort(403, 'Unauthorized');
        }

        if (!$report->isEditable()) {
            return back()->with('error', 'This report cannot be edited in its current status.');
        }

        // Ensure AI insights exist
        if (!$report->aiInsights) {
            // Generate AI insights if they don't exist
            try {
                $aiInsights = QuarterlyReportService::getAIInsights($report);
                QuarterlyReportService::storeAIInsights($report, $aiInsights);
                $report->refresh();
            } catch (\Exception $e) {
                Log::warning('Failed to generate AI insights for editing', [
                    'report_id' => $report_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('reports.aggregated.quarterly.edit-ai', compact('report'));
    }

    /**
     * Update AI content
     */
    public function updateAI(Request $request, $report_id)
    {
        $report = QuarterlyReport::findOrFail($report_id);

        // Check permissions
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
            'report_title' => 'nullable|string|max:255',
            'section_headings' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Update AI insights
            $insights = $report->aiInsights ?? AIReportInsight::create([
                'report_type' => 'quarterly',
                'report_id' => $report->report_id,
            ]);

            $insights->executive_summary = $request->executive_summary ?? $insights->executive_summary;
            if ($request->has('key_achievements')) {
                $insights->key_achievements = $request->key_achievements;
            }
            if ($request->has('progress_trends')) {
                $insights->progress_trends = $request->progress_trends;
            }
            if ($request->has('challenges')) {
                $insights->challenges = $request->challenges;
            }
            if ($request->has('recommendations')) {
                $insights->recommendations = $request->recommendations;
            }
            $insights->markAsEdited();
            $insights->save();

            // Update AI titles
            if ($request->has('report_title') || $request->has('section_headings')) {
                $title = $report->aiTitle ?? AIReportTitle::create([
                    'report_type' => 'quarterly',
                    'report_id' => $report->report_id,
                ]);

                if ($request->has('report_title')) {
                    $title->report_title = $request->report_title;
                }
                if ($request->has('section_headings')) {
                    $title->section_headings = $request->section_headings;
                }
                $title->markAsEdited();
                $title->save();
            }

            DB::commit();

            return redirect()->route('aggregated.quarterly.show', $report->report_id)
                ->with('success', 'AI content updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update AI content', [
                'report_id' => $report_id,
                'error' => $e->getMessage()
            ]);

            return back()->withInput()
                ->with('error', 'Failed to update AI content: ' . $e->getMessage());
        }
    }

    /**
     * Export report as PDF
     */
    public function exportPdf($report_id)
    {
        $exportController = new AggregatedReportExportController();
        return $exportController->exportQuarterlyPdf($report_id);
    }

    /**
     * Export report as Word
     */
    public function exportWord($report_id)
    {
        $exportController = new AggregatedReportExportController();
        return $exportController->exportQuarterlyWord($report_id);
    }
}
