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
    Log::info('Form submission data:', $request->all());

    DB::beginTransaction();
    try {
        Log::info('Entering store method', ['request' => $request->all()]);

        $validatedData = $request->validate([
            'project_id' => 'required|string|max:255',
            'total_beneficiaries' => 'nullable|integer',
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
            'plan_next_month.*' => 'nullable|string',
            'particulars.*' => 'required|string',
            'amount_forwarded.*' => 'nullable|numeric',
            'amount_sanctioned.*' => 'nullable|numeric',
            'total_amount.*' => 'required|numeric',
            'expenses_last_month.*' => 'required|numeric',
            'expenses_this_month.*' => 'required|numeric',
            'total_expenses.*' => 'required|numeric',
            'balance_amount.*' => 'required|numeric',
            'photo_descriptions.*' => 'nullable|string',
            'photos.*' => 'nullable|image|max:5120', // 5MB max size
        ]);

        Log::info('Starting transaction for storing report');

        // Fetch the last inserted report_id for the given project_id
        $lastReport = DB::table('DPReports')
            ->where('project_id', $validatedData['project_id'])
            ->orderBy('report_id', 'desc')
            ->first();

        if ($lastReport) {
            // Extract the numeric part and increment it
            $lastNumber = (int) substr($lastReport->report_id, -2);
            $newNumber = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);
        } else {
            // If no previous report exists, start with 01
            $newNumber = '01';
        }

        // Generate the new report_id
        $reportId = $validatedData['project_id'] . '-' . $newNumber;

        // Store the main report data
        $report = DPReport::create([
            'report_id' => $reportId,
            'user_id' => Auth::id(),
            'project_id' => $validatedData['project_id'],
            'project_title' => $request->project_title,
            'project_type' => $request->project_type,
            'place' => $request->place,
            'society_name' => $request->society_name,
            'commencement_month_year' => $request->commencement_month_year,
            'in_charge' => $request->in_charge,
            'total_beneficiaries' => $validatedData['total_beneficiaries'],
            'report_month_year' => $validatedData['report_year'] . '-' . str_pad($validatedData['report_month'], 2, '0', STR_PAD_LEFT) . '-01',
            'goal' => $request->goal,
            'account_period_start' => $validatedData['account_period_start'],
            'account_period_end' => $validatedData['account_period_end'],
            'amount_sanctioned_overview' => $request->amount_sanctioned_overview,
            'amount_forwarded_overview' => $request->amount_forwarded_overview,
            'amount_in_hand' => $request->amount_in_hand,
            'total_balance_forwarded' => $request->total_balance_forwarded,
            'status' => 1,
        ]);

        Log::info('Report stored', ['report_id' => $report->report_id]);

        // Store objectives and activities
        foreach ($validatedData['objective'] as $index => $objective) {
            $objectiveId = Str::uuid()->toString();
            $dpObjective = DPObjective::create([
                'objective_id' => $objectiveId,
                'report_id' => $reportId,
                'objective' => $objective,
                'expected_outcome' => $validatedData['expected_outcome'][$index],
                'not_happened' => $validatedData['not_happened'][$index],
                'why_not_happened' => $validatedData['why_not_happened'][$index],
                'changes' => $validatedData['changes'][$index] === 'yes' ? 1 : 0,
                'why_changes' => $validatedData['why_changes'][$index] ?? null,
                'lessons_learnt' => $validatedData['lessons_learnt'][$index],
                'todo_lessons_learnt' => $validatedData['todo_lessons_learnt'][$index],
            ]);

            Log::info('Objective stored', ['objective_id' => $dpObjective->objective_id]);

            foreach ($validatedData['summary_activities'][$index] as $activityIndex => $activity) {
                $activityId = Str::uuid()->toString();
                DPActivity::create([
                    'activity_id' => $activityId,
                    'objective_id' => $dpObjective->objective_id,
                    'month' => $validatedData['month'][$index][$activityIndex],
                    'summary_activities' => $activity[1],
                    'qualitative_quantitative_data' => $validatedData['qualitative_quantitative_data'][$index][$activityIndex][1],
                    'intermediate_outcomes' => $validatedData['intermediate_outcomes'][$index][$activityIndex][1],
                ]);

                Log::info('Activity stored', ['activity_id' => $activityId]);
            }
        }

        // Store account details
        foreach ($validatedData['particulars'] as $index => $particular) {
            // Fetch the last inserted account_detail_id for the given report_id
            $lastAccountDetail = DB::table('DP_AccountDetails')
                ->where('report_id', $reportId)
                ->orderBy('account_detail_id', 'desc')
                ->first();

            if ($lastAccountDetail) {
                // Extract the numeric part and increment it
                $lastNumber = (int) substr($lastAccountDetail->account_detail_id, -4);
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                // If no previous record exists, start with 0001
                $newNumber = '0001';
            }

            // Generate the new account_detail_id
            $newAccountDetailId = $reportId . '-' . $newNumber;

            DPAccountDetail::create([
                'account_detail_id' => $newAccountDetailId,
                'report_id' => $reportId,
                'particulars' => $particular,
                'amount_forwarded' => $validatedData['amount_forwarded'][$index],
                'amount_sanctioned' => $validatedData['amount_sanctioned'][$index],
                'total_amount' => $validatedData['total_amount'][$index],
                'expenses_last_month' => $validatedData['expenses_last_month'][$index],
                'expenses_this_month' => $validatedData['expenses_this_month'][$index],
                'total_expenses' => $validatedData['total_expenses'][$index],
                'balance_amount' => $validatedData['balance_amount'][$index],
            ]);

            Log::info('Account detail stored', ['account_detail_id' => $newAccountDetailId]);
        }

        // Store outlooks
        foreach ($validatedData['date'] as $index => $date) {
            DPOutlook::create([
                'outlook_id' => Str::uuid()->toString(),
                'report_id' => $reportId,
                'date' => $date,
                'plan_next_month' => $validatedData['plan_next_month'][$index],
            ]);

            Log::info('Outlook stored', ['outlook_id' => Str::uuid()->toString()]);
        }

        // Store photos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $photo) {
                $path = $photo->store('photos', 'public');
                DPPhoto::create([
                    'photo_id' => Str::uuid()->toString(),
                    'report_id' => $reportId,
                    'photo_path' => $path,
                    'description' => $validatedData['photo_descriptions'][$index] ?? '',
                ]);

                Log::info('Photo stored', ['photo_id' => Str::uuid()->toString()]);
            }
        }

        DB::commit();

        return redirect()->route('monthly.report.index')->with('success', 'Report stored successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error storing report', ['exception' => $e]);
        return redirect()->back()->withErrors('Error storing report. Please try again.');
    }
}


    public function index()
    {
        Log::info('Entering index method');

        $reports = DPReport::with('project', 'user')->get();
        Log::info('Reports retrieved', ['reports' => $reports]);

        return view('reports.monthly.index', compact('reports'));
    }

    public function show($report_id)
    {
        Log::info('Entering show method', ['report_id' => $report_id]);

        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                          ->where('report_id', $report_id)
                          ->firstOrFail();
        Log::info('Report retrieved', ['report' => $report]);

        return view('reports.monthly.show', compact('report'));
    }

    public function edit($report_id)
    {
        Log::info('Entering edit method', ['report_id' => $report_id]);

        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                          ->where('report_id', $report_id)
                          ->firstOrFail();
        Log::info('Report retrieved for editing', ['report' => $report]);

        return view('reports.monthly.edit', compact('report'));
    }

    public function update(Request $request, $report_id)
    {
        Log::info('Entering update method', ['report_id' => $report_id, 'request' => $request->all()]);

        $request->validate([
            // validation rules
        ]);

        DB::beginTransaction();

        try {
            Log::info('Starting transaction for updating report');

            $report = DPReport::where('report_id', $report_id)->firstOrFail();
            $report->update([
                'total_beneficiaries' => $request->input('total_beneficiaries'),
                'report_month_year' => $request->input('report_month_year'),
                'goal' => $request->input('goal'),
                'account_period_start' => $request->input('account_period_start'),
                'account_period_end' => $request->input('account_period_end'),
                'amount_sanctioned_overview' => $request->input('amount_sanctioned_overview'),
                'amount_forwarded_overview' => $request->input('amount_forwarded_overview'),
                'amount_in_hand' => $request->input('amount_in_hand'),
                'total_balance_forwarded' => $request->input('total_balance_forwarded'),
                'status' => 'updated',
            ]);
            Log::info('Report updated', ['report' => $report]);

            $report->objectives()->delete();
            $report->accountDetails()->delete();
            $report->photos()->delete();
            $report->outlooks()->delete();
            Log::info('Old objectives, account details, photos, and outlooks deleted');

            foreach ($request->input('objective', []) as $index => $objective) {
                $objective = DPObjective::create([
                    'objective_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'objective' => $objective,
                    'expected_outcome' => $request->input("expected_outcome.{$index}"),
                    'not_happened' => $request->input("not_happened.{$index}"),
                    'why_not_happened' => $request->input("why_not_happened.{$index}"),
                    'changes' => $request->input("changes.{$index}"),
                    'why_changes' => $request->input("why_changes.{$index}"),
                    'lessons_learnt' => $request->input("lessons_learnt.{$index}"),
                    'todo_lessons_learnt' => $request->input("todo_lessons_learnt.{$index}"),
                ]);
                Log::info('Objective created', ['objective' => $objective]);

                foreach ($request->input("summary_activities.{$index}", []) as $activityIndex => $summaryActivity) {
                    $activity = DPActivity::create([
                        'activity_id' => Str::uuid()->toString(),
                        'objective_id' => $objective->objective_id,
                        'month' => $request->input("month.{$index}.{$activityIndex}"),
                        'summary_activities' => $summaryActivity,
                        'qualitative_quantitative_data' => $request->input("qualitative_quantitative_data.{$index}.{$activityIndex}"),
                        'intermediate_outcomes' => $request->input("intermediate_outcomes.{$index}.{$activityIndex}"),
                    ]);
                    Log::info('Activity created', ['activity' => $activity]);
                }
            }

            foreach ($request->input('particulars', []) as $index => $particular) {
                $accountDetail = DPAccountDetail::create([
                    'account_detail_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'particulars' => $particular,
                    'amount_forwarded' => $request->input("amount_forwarded.{$index}"),
                    'amount_sanctioned' => $request->input("amount_sanctioned.{$index}"),
                    'total_amount' => $request->input("total_amount.{$index}"),
                    'expenses_last_month' => $request->input("expenses_last_month.{$index}"),
                    'expenses_this_month' => $request->input("expenses_this_month.{$index}"),
                    'total_expenses' => $request->input("total_expenses.{$index}"),
                    'balance_amount' => $request->input("balance_amount.{$index}"),
                ]);
                Log::info('Account detail created', ['accountDetail' => $accountDetail]);
            }

            foreach ($request->file('photos', []) as $index => $photo) {
                $photoPath = $photo->store('photos', 'public');
                $dpPhoto = DPPhoto::create([
                    'photo_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'photo_path' => $photoPath,
                    'photo_name' => $photo->getClientOriginalName(),
                    'description' => $request->input("photo_descriptions.{$index}"),
                ]);
                Log::info('Photo uploaded and created', ['dpPhoto' => $dpPhoto]);
            }

            foreach ($request->input('date', []) as $index => $date) {
                $outlook = DPOutlook::create([
                    'outlook_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'date' => $date,
                    'plan_next_month' => $request->input("plan_next_month.{$index}"),
                ]);
                Log::info('Outlook created', ['outlook' => $outlook]);
            }

            DB::commit();
            Log::info('Transaction committed successfully');

            return redirect()->route('monthly.report.index')->with('success', 'Report updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating report', ['exception' => $e]);
            return redirect()->back()->withErrors('An error occurred while updating the report. Please try again.');
        }
    }

    public function review($report_id)
    {
        Log::info('Entering review method', ['report_id' => $report_id]);

        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                          ->where('report_id', $report_id)
                          ->firstOrFail();
        Log::info('Report retrieved for review', ['report' => $report]);

        return view('reports.monthly.review', compact('report'));
    }

    public function revert(Request $request, $report_id)
    {
        Log::info('Entering revert method', ['report_id' => $report_id, 'request' => $request->all()]);

        $report = DPReport::where('report_id', $report_id)->firstOrFail();
        $report->update([
            'status' => 'reverted',
            'revert_reason' => $request->input('revert_reason'),
        ]);
        Log::info('Report reverted', ['report' => $report]);

        return redirect()->route('monthly.report.index')->with('success', 'Report reverted successfully.');
    }


}
