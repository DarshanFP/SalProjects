<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Reports\Monthly\DPObjective;
use App\Models\Reports\Monthly\DPActivity;
use App\Models\Reports\Monthly\DPAccountDetail;
use App\Models\Reports\Monthly\DPPhoto;
use App\Models\Reports\Monthly\DPOutlook;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Polyfill\Uuid\Uuid;

class ReportController extends Controller
{
    public function create($project_id)
    {
        Log::info('Entering create method', ['project_id' => $project_id]);

        $project = Project::where('project_id', $project_id)->firstOrFail();
        Log::info('Project retrieved successfully', ['project' => $project]);

        $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
        Log::info('Retrieved highest phase for the project', ['highestPhase' => $highestPhase]);

        $budgets = ProjectBudget::where('project_id', $project->project_id)
                                ->where('phase', $highestPhase)
                                ->get();
        Log::info('Budgets retrieved for the highest phase', ['budgets' => $budgets]);

        $amountSanctioned = $project->amount_sanctioned ?? 0.00;
        $amountForwarded = $project->amount_forwarded ?? 0.00;
        Log::info('Sanctioned and forwarded amounts', [
            'amountSanctioned' => $amountSanctioned,
            'amountForwarded' => $amountForwarded
        ]);

        $lastExpenses = collect();

        $lastReport = DPReport::where('project_id', $project->project_id)
                              ->orderBy('created_at', 'desc')
                              ->first();

        if ($lastReport) {
            $lastExpenses = DPAccountDetail::where('report_id', $lastReport->report_id)
                                           ->get()
                                           ->keyBy('particulars')
                                           ->map(function ($item) {
                                               return $item->total_expenses;
                                           });
            Log::info('Last expenses retrieved', ['lastExpenses' => $lastExpenses]);
        } else {
            Log::info('No last report found, lastExpenses remains empty');
        }

        $user = Auth::user();

        return view('reports.monthly.ReportCommonForm', compact('project', 'user', 'amountSanctioned', 'amountForwarded', 'budgets', 'lastExpenses'));
    }

    public function store(Request $request)
{
    $validatedData = $request->validate([
        'project_id' => 'required|string|max:255',
        'total_beneficiaries' => 'required|integer',
        'report_month' => 'required|integer|between:1,12',
        'report_year' => 'required|integer',
        'account_period_start' => 'required|date|before_or_equal:account_period_end',
        'account_period_end' => 'required|date|after_or_equal:account_period_start',
        'objective.*' => 'required|string',
        'expected_outcome.*' => 'required|string',
        'month.*.*' => 'required|integer|between:1,12',
        'summary_activities.*.*.*' => 'required|string',
        'qualitative_quantitative_data.*.*.*' => 'required|string',
        'intermediate_outcomes.*.*.*' => 'required|string',
        'not_happened.*' => 'required|string',
        'why_not_happened.*' => 'required|string',
        'changes.*' => 'required|string',
        'why_changes.*' => 'required_if:changes.*,yes|string',
        'lessons_learnt.*' => 'required|string',
        'todo_lessons_learnt.*' => 'required|string',
        'date.*' => 'required|date',
        'plan_next_month.*' => 'required|string',
        'particulars.*' => 'required|string',
        'amount_forwarded.*' => 'required|numeric',
        'amount_sanctioned.*' => 'required|numeric',
        'expenses_last_month.*' => 'required|numeric',
        'expenses_this_month.*' => 'required|numeric',
        'photo_descriptions.*' => 'nullable|string',
        'photos.*' => 'nullable|image|max:5120', // 5MB max size
    ]);

    DB::beginTransaction();

    try {
        // Generate a new UUID for the report
        $reportId = (string) Uuid::uuid4();

        // Store the main report data
        $report = Report::create([
            'report_id' => $reportId,
            'project_id' => $validatedData['project_id'],
            'total_beneficiaries' => $validatedData['total_beneficiaries'],
            'report_month' => $validatedData['report_month'],
            'report_year' => $validatedData['report_year'],
            'account_period_start' => $validatedData['account_period_start'],
            'account_period_end' => $validatedData['account_period_end'],
        ]);

        // Store objectives and activities
        foreach ($validatedData['objective'] as $index => $objective) {
            $objectiveId = (string) Uuid::uuid4();
            $report->objectives()->create([
                'objective_id' => $objectiveId,
                'objective' => $objective,
                'expected_outcome' => $validatedData['expected_outcome'][$index],
            ]);

            foreach ($validatedData['month'][$index] as $activityIndex => $month) {
                $activityId = (string) Uuid::uuid4();
                DPActivity::create([
                    'activity_id' => $activityId,
                    'objective_id' => $objectiveId,
                    'month' => $month,
                    'summary_activities' => $validatedData['summary_activities'][$index][$activityIndex][1],
                    'qualitative_quantitative_data' => $validatedData['qualitative_quantitative_data'][$index][$activityIndex][1],
                    'intermediate_outcomes' => $validatedData['intermediate_outcomes'][$index][$activityIndex][1],
                ]);
            }
        }

        // Store account details
        foreach ($validatedData['particulars'] as $index => $particular) {
            $accountDetailId = (string) Uuid::uuid4();
            DPAccountDetail::create([
                'account_detail_id' => $accountDetailId,
                'report_id' => $reportId,
                'particular' => $particular,
                'amount_forwarded' => $validatedData['amount_forwarded'][$index],
                'amount_sanctioned' => $validatedData['amount_sanctioned'][$index],
                'total_amount' => $validatedData['amount_forwarded'][$index] + $validatedData['amount_sanctioned'][$index],
                'expenses_last_month' => $validatedData['expenses_last_month'][$index],
                'expenses_this_month' => $validatedData['expenses_this_month'][$index],
                'total_expenses' => $validatedData['expenses_last_month'][$index] + $validatedData['expenses_this_month'][$index],
                'balance_amount' => ($validatedData['amount_forwarded'][$index] + $validatedData['amount_sanctioned'][$index]) - ($validatedData['expenses_last_month'][$index] + $validatedData['expenses_this_month'][$index]),
            ]);
        }

        // Store outlooks
        foreach ($validatedData['date'] as $index => $date) {
            $outlookId = (string) Uuid::uuid4();
            DPOutlook::create([
                'outlook_id' => $outlookId,
                'report_id' => $reportId,
                'date' => $date,
                'plan_next_month' => $validatedData['plan_next_month'][$index],
            ]);
        }

        // Store photos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $photo) {
                $path = $photo->store('photos', 'public');
                DPPhoto::create([
                    'photo_id' => (string) Uuid::uuid4(),
                    'report_id' => $reportId,
                    'path' => $path,
                    'description' => $validatedData['photo_descriptions'][$index] ?? '',
                ]);
            }
        }

        DB::commit();

        return redirect()->route('reports.index')->with('success', 'Report stored successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error storing report', ['exception' => $e]);
        return redirect()->back()->withErrors('Error storing report. Please try again.');
    }
}

}
