<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectObjective;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Reports\Monthly\DPObjective;
use App\Models\Reports\Monthly\DPActivity;
use App\Models\Reports\Monthly\DPPhoto;
use App\Models\Reports\Monthly\DPAccountDetail;
use App\Models\Reports\Monthly\DPOutlook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Helpers\LogHelper;
use App\Services\ReportPhotoOptimizationService;
use App\Services\Reports\MonthlyReportCreateAuthorization;
use App\Support\Reports\ReportResourceLookup;
use App\Traits\HandlesReportPhotoActivity;

class MonthlyDevelopmentProjectController extends Controller
{
    use HandlesReportPhotoActivity;
    public function create($project_id)
    {
        return $this->redirectToCanonicalReportCreate($project_id);
    }

    /**
     * @deprecated Phase 7 — use monthly.report.create (ReportAll). Kept for route backward compatibility.
     */
    public function createForm($project_id)
    {
        return $this->redirectToCanonicalReportCreate($project_id);
    }

    /**
     * Redirect legacy DP/NPD create URLs to the canonical monthly report create form.
     */
    private function redirectToCanonicalReportCreate(string $project_id)
    {
        $project = ReportResourceLookup::findProject($project_id);
        MonthlyReportCreateAuthorization::abortUnlessAllowed(Auth::user(), $project);

        Log::info('Legacy developmentProject create redirected to monthly.report.create (Phase 7)', [
            'project_id' => $project_id,
            'project_type' => $project->project_type,
        ]);

        return redirect()->route('monthly.report.create', ['project_id' => $project_id]);
    }


    public function store(Request $request)
    {
        Log::warning('Blocked request to deprecated route monthly.developmentProject.store', [
            'project_id' => $request->input('project_id'),
            'user_id' => auth()->id(),
        ]);

        abort(410, 'This endpoint is deprecated and deactivated. Please use the canonical monthly.report.store route.');
    }

    public function index()
    {
        // Eager load relationships to prevent N+1 queries
        $reports = DPReport::where('user_id', Auth::id())
            ->with(['user', 'project', 'accountDetails'])
            ->get();
        return view('reports.monthly.developmentProject.list', compact('reports'));
    }

    public function show($report_id)
    {
        $report = DPReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks'])->findOrFail($report_id);
        // Only show activities where the user filled at least one field.
        foreach ($report->objectives as $objective) {
            $objective->setRelation(
                'activities',
                $objective->activities->filter(fn ($a) => $a->hasUserFilledData())->values()
            );
        }
        return view('reports.monthly.developmentProject.show', compact('report'));
    }

    public function edit($report_id)
    {
        $report = DPReport::with(['objectives.activities', 'photos', 'accountDetails'])->findOrFail($report_id);
        return view('reports.monthly.developmentProject.edit', compact('report'));
    }

    public function update(Request $request, $report_id)
    {
        $report = DPReport::findOrFail($report_id);

        $validatedData = $request->validate([
            'project_title' => 'nullable|string|max:255',
            'place' => 'nullable|string|max:255',
            'society_name' => 'nullable|string|max:255',
            'commencement_month_year' => 'nullable|string|max:255',
            'in_charge' => 'nullable|string|max:255',
            'total_beneficiaries' => 'nullable|integer',
            'reporting_period' => 'nullable|string|max:255',
            'goal' => 'nullable|string',
            'account_period_start' => 'nullable|date',
            'account_period_end' => 'nullable|date',
            'total_balance_forwarded' => 'nullable|numeric',
        ]);

        $report->update($validatedData);

        return redirect()->route('monthly.developmentProject.edit', $report->report_id)->with('success', 'Report updated successfully.');
    }

    public function review($report_id)
    {
        $report = DPReport::with(['objectives.activities', 'photos', 'accountDetails'])->findOrFail($report_id);
        return view('reports.monthly.developmentProject.review', compact('report'));
    }

    public function revert(Request $request, $report_id)
    {
        // Logic to revert with feedback from senior
        // ...
    }
}
