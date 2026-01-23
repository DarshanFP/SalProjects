<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectObjective;
use App\Models\OldProjects\ProjectBudget;
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
use App\Helpers\LogHelper;
use App\Services\ReportPhotoOptimizationService;
use App\Traits\HandlesReportPhotoActivity;

class MonthlyDevelopmentProjectController extends Controller
{
    use HandlesReportPhotoActivity;
    public function create($project_id)
    {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
        $budgets = ProjectBudget::where('project_id', $project->project_id)->where('phase', $highestPhase)->get();
        $amountSanctioned = $project->amount_sanctioned ?? 0;
        $amountForwarded = 0;
        $expensesUpToLastMonth = DPAccountDetail::where('report_id', function ($q) use ($project) {
            $q->select('id')->from('dp_reports')->where('project_id', $project->project_id)->orderBy('created_at', 'desc')->limit(1);
        })->sum('expenses_this_month');
        $user = Auth::user();

        return view('reports.monthly.ReportCommonForm', compact('project', 'user', 'amountSanctioned', 'amountForwarded', 'budgets', 'expensesUpToLastMonth'));
    }

    /**
     * Show developmentProject/reportform (aligned with activity-based photos).
     */
    public function createForm($project_id)
    {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
        $budgets = ProjectBudget::where('project_id', $project->project_id)->where('phase', $highestPhase)->get();
        $amountSanctioned = $project->amount_sanctioned ?? 0;
        $amountForwarded = 0;
        $reportId = DPReport::where('project_id', $project->project_id)->orderBy('created_at', 'desc')->value('report_id');
        $sum = $reportId ? DPAccountDetail::where('report_id', $reportId)->sum('expenses_this_month') : 0;
        $expensesUpToLastMonth = $budgets->pluck('id')->mapWithKeys(fn ($id) => [$id => $sum])->all();
        $user = Auth::user();

        return view('reports.monthly.developmentProject.reportform', compact('project', 'user', 'amountSanctioned', 'amountForwarded', 'budgets', 'expensesUpToLastMonth'));
    }


    public function store(Request $request)
    {
        Log::info('Store method called');
        LogHelper::logSafeRequest('Request data', $request, LogHelper::getReportAllowedFields());

        // developmentProject/reportform sends report_month_year (YYYY-MM); adapt to reporting_period_month/year
        if ($request->has('report_month_year') && ! $request->has('reporting_period_month')) {
            $p = explode('-', (string) $request->report_month_year);
            $request->merge([
                'reporting_period_year' => (int) ($p[0] ?? date('Y')),
                'reporting_period_month' => (int) ($p[1] ?? 1),
            ]);
        }

        $validatedData = $request->validate([
            'project_id' => 'required|exists:projects,project_id',
            'total_beneficiaries' => 'nullable|integer',
            'reporting_period_month' => 'required|integer|min:1|max:12',
            'reporting_period_year' => 'required|integer|min:1900|max:' . date('Y'),
            'goal' => 'nullable|string',
            'account_period_start' => 'nullable|date',
            'account_period_end' => 'nullable|date',
            'total_balance_forwarded' => 'nullable|numeric',
            'amount_sanctioned_overview' => 'nullable|numeric',
            'amount_in_hand' => 'nullable|numeric',
            'photos' => 'nullable|array',
            'photo_descriptions' => 'nullable|array',
            'photo_activity_id' => 'nullable|array',
            'photo_activity_id.*' => 'nullable|string|max:255',
            'objective' => 'nullable|array',
            'objective.*' => 'nullable|string',
        ]);

        // Concatenate reporting period
        $validatedData['reporting_period_from'] = date('Y-m-d', strtotime("{$request->reporting_period_year}-{$request->reporting_period_month}-01"));
        $validatedData['reporting_period_to'] = date("Y-m-t", strtotime($validatedData['reporting_period_from'])); // Get the last day of the month

        // Temporarily set user_id to null for testing if not authenticated
        $validatedData['user_id'] = auth()->check() ? auth()->id() : null;

        Log::info('Validated Data: ', $validatedData);

        // Create the report
        $report = DPReport::create($validatedData);
        Log::info('Report Created: ', $report->toArray());

        // Save objectives and activities
        $expected_outcome = $request->input('expected_outcome', []);
        $objectives = $request->input('objective', []);
        $months = $request->input('month', []);

        Log::info('Expected Outcome:', $expected_outcome);
        Log::info('Months:', $months);

        foreach ($expected_outcome as $index => $expectedOutcome) {
            $objectiveData = [
                'report_id' => $report->report_id,
                'objective' => $objectives[$index] ?? null,
                'expected_outcome' => $expectedOutcome,
                'not_happened' => $request->input("not_happened.$index"),
                'why_not_happened' => $request->input("why_not_happened.$index"),
                'changes' => $request->input("changes.$index") === 'yes',
                'why_changes' => $request->input("why_changes.$index"),
                'lessons_learnt' => $request->input("lessons_learnt.$index"),
                'todo_lessons_learnt' => $request->input("todo_lessons_learnt.$index"),
            ];

            Log::info('Objective Data:', $objectiveData);

            $objective = DPObjective::create($objectiveData);
            Log::info('Objective Created: ', $objective->toArray());

            $activityMonths = $request->input("month.$index", []);

            foreach ($activityMonths as $activityIndex => $month) {
                $summaryActivities = $request->input("summary_activities.$index.$activityIndex");
                $qualitativeQuantitativeData = $request->input("qualitative_quantitative_data.$index.$activityIndex");
                $intermediateOutcomes = $request->input("intermediate_outcomes.$index.$activityIndex");

                $summaryStr = is_array($summaryActivities) ? implode(' ', $summaryActivities) : ($summaryActivities ?? '');
                $qualStr = is_array($qualitativeQuantitativeData) ? implode(' ', $qualitativeQuantitativeData) : ($qualitativeQuantitativeData ?? '');
                $interStr = is_array($intermediateOutcomes) ? implode(' ', $intermediateOutcomes) : ($intermediateOutcomes ?? '');

                // Only store when the user has filled at least one activity field.
                $filled = (trim((string) ($month ?? '')) !== '')
                    || (trim((string) $summaryStr) !== '')
                    || (trim((string) $qualStr) !== '')
                    || (trim((string) $interStr) !== '');

                if (! $filled) {
                    continue;
                }

                $activityData = [
                    'objective_id' => $objective->objective_id,
                    'month' => date("Y-m-d", strtotime($month . " 01")), // Convert month to a full date
                    'summary_activities' => is_array($summaryActivities) ? implode(', ', $summaryActivities) : $summaryActivities,
                    'qualitative_quantitative_data' => is_array($qualitativeQuantitativeData) ? implode(', ', $qualitativeQuantitativeData) : $qualitativeQuantitativeData,
                    'intermediate_outcomes' => is_array($intermediateOutcomes) ? implode(', ', $intermediateOutcomes) : $intermediateOutcomes,
                ];

                Log::info('Activity Data:', $activityData);

                $activity = DPActivity::create($activityData);
                Log::info('Activity Created: ', $activity->toArray());
            }
        }

        // Handle file uploads: photos[groupIndex][] and photo_activity_id[groupIndex] (create photos partial)
        $report->load('objectives.activities');
        $photos = $request->file('photos');
        if ($photos && is_array($photos)) {
            $monthYear = date('m_Y', strtotime($validatedData['reporting_period_from']));
            $folderPath = "REPORTS/{$request->project_id}/{$report->report_id}/photos/{$monthYear}";
            $optimizer = app(ReportPhotoOptimizationService::class);
            $photoActivityIds = is_array($request->input('photo_activity_id', [])) ? $request->input('photo_activity_id') : [];

            foreach ($photos as $groupIndex => $files) {
                $files = is_array($files) ? $files : [$files];
                $val = $request->input("photo_activity_id.{$groupIndex}") ?? ($photoActivityIds[$groupIndex] ?? null);
                $activity_id = $this->resolveActivityId($report, $val);

                $existingCount = $activity_id
                    ? DPPhoto::where('activity_id', $activity_id)->count()
                    : DPPhoto::where('report_id', $report->report_id)->whereNull('activity_id')->count();
                if ($activity_id !== null && $existingCount + count($files) > 3) {
                    $files = array_slice($files, 0, max(0, 3 - $existingCount));
                }

                $addedInGroup = 0;
                foreach ($files as $file) {
                    if (! $file || ! $file->isValid() || $file->getSize() > 2097152) {
                        continue;
                    }

                    $latestPhoto = DPPhoto::where('photo_id', 'LIKE', "{$report->report_id}-%")->latest('photo_id')->lockForUpdate()->first();
                    $max_suffix = $latestPhoto ? (int) substr($latestPhoto->photo_id, -4) + 1 : 1;
                    $photo_id = $report->report_id . '-' . str_pad((string) $max_suffix, 4, '0', STR_PAD_LEFT);

                    $result = $optimizer->optimize($file);
                    $ext = $result !== null ? 'jpg' : (strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)) ?: 'jpg');
                    $incremental = $existingCount + $addedInGroup + 1;
                    $filename = $this->buildActivityBasedFilename($report, $activity_id, $incremental, $ext);
                    $path = $folderPath . '/' . $filename;

                    if ($result !== null) {
                        Storage::disk('public')->put($path, $result['data']);
                        $photo_location = $result['location'] ?? null;
                    } else {
                        $path = $file->storeAs($folderPath, $filename, 'public');
                        $photo_location = null;
                    }

                    DPPhoto::create([
                        'photo_id' => $photo_id,
                        'report_id' => $report->report_id,
                        'activity_id' => $activity_id,
                        'photo_path' => $path,
                        'description' => $activity_id === null ? ($request->input("photo_descriptions.{$groupIndex}") ?? $request->photo_descriptions[$groupIndex] ?? null) : null,
                        'photo_location' => $photo_location,
                    ]);
                    $addedInGroup++;
                    Log::info('Photo created', ['photo_id' => $photo_id]);
                }
            }
        }

        // Ensure particulars input array is initialized
        $particulars = $request->input('particulars', []);

        // Save account details
        foreach ($particulars as $index => $particularsItem) {
            $accountDetailData = [
                'report_id' => $report->report_id,
                'particulars' => $particularsItem,
                'amount_forwarded' => 0.0, // Always set to 0 for backward compatibility
                'amount_sanctioned' => $request->input("amount_sanctioned.$index"),
                'total_amount' => $request->input("total_amount.$index"),
                'expenses_last_month' => $request->input("expenses_last_month.$index"),
                'expenses_this_month' => $request->input("expenses_this_month.$index"),
                'total_expenses' => $request->input("total_expenses.$index"),
                'balance_amount' => $request->input("balance_amount.$index"),
            ];

            Log::info('Account Detail Data:', $accountDetailData);

            $accountDetail = DPAccountDetail::create($accountDetailData);
            Log::info('Account Detail Created: ', $accountDetail->toArray());
        }

        // Save outlooks
        $outlookDates = $request->input('date', []);
        $planNextMonths = $request->input('plan_next_month', []);
        foreach ($outlookDates as $index => $date) {
            $outlookData = [
                'report_id' => $report->report_id,
                'date' => $date,
                'plan_next_month' => $planNextMonths[$index] ?? null,
            ];

            Log::info('Outlook Data:', $outlookData);

            $outlook = DPOutlook::create($outlookData);
            Log::info('Outlook Created: ', $outlook->toArray());
        }

        return redirect()->route('monthly.developmentProject.create', ['project_id' => $request->project_id])->with('success', 'Report submitted successfully.');
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
