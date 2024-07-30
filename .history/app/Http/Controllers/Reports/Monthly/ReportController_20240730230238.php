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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;




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

        return view('reports.monthly.ReportAll', compact('project', 'user', 'amountSanctioned', 'amountForwarded', 'budgets', 'lastExpenses'));
    }

    public function store(Request $request)
{
    Log::info('Store method initiated with data:', ['data' => $request->all()]);

    DB::beginTransaction();
    try {
        // Validation logic
        $validatedData = $request->validate([
            'project_id' => 'required|string|max:255',
            'project_title' => 'nullable|string|max:255',
            'project_type' => 'nullable|string|max:255',
            'place' => 'nullable|string|max:255',
            'society_name' => 'nullable|string|max:255',
            'commencement_month_year' => 'nullable|date',
            'in_charge' => 'nullable|string|max:255',
            'total_beneficiaries' => 'nullable|integer',
            'report_month' => 'nullable|integer|between:1,12',
            'report_year' => 'nullable|integer',
            'goal' => 'nullable|string',
            'account_period_start' => 'nullable|date',
            'account_period_end' => 'nullable|date',
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
            'photo_descriptions' => 'nullable|array',
            'photo_descriptions.*' => 'nullable|string|max:255',
            'objective' => 'nullable|array',
            'objective.*' => 'nullable|string',
            'expected_outcome' => 'nullable|array',
            'expected_outcome.*' => 'nullable|string',
            'month' => 'nullable|array',
            'summary_activities' => 'nullable|array',
            'qualitative_quantitative_data' => 'nullable|array',
            'intermediate_outcomes' => 'nullable|array',
            'particulars' => 'nullable|array',
            'amount_forwarded' => 'nullable|array',
            'amount_sanctioned' => 'nullable|array',
            'total_amount' => 'nullable|array',
            'expenses_last_month' => 'nullable|array',
            'expenses_this_month' => 'nullable|array',
            'total_expenses' => 'nullable|array',
            'balance_amount' => 'nullable|array',
            'date' => 'nullable|array',
            'plan_next_month' => 'nullable|array'
        ]);

        // Generate report_id with concurrency handling
        $project_id = $validatedData['project_id'];
        $report_id = $this->generateReportId($project_id);

        // Create the main report
        $report = DPReport::create([
            'report_id' => $report_id,
            'user_id' => auth()->id() ?? null,
            'project_id' => $project_id,
            'project_title' => $validatedData['project_title'] ?? '',
            'project_type' => $validatedData['project_type'] ?? '',
            'place' => $validatedData['place'] ?? '',
            'society_name' => $validatedData['society_name'] ?? '',
            'commencement_month_year' => $validatedData['commencement_month_year'] ?? null,
            'in_charge' => $validatedData['in_charge'] ?? '',
            'total_beneficiaries' => $validatedData['total_beneficiaries'] ?? 0,
            'report_month_year' => isset($validatedData['report_year']) && isset($validatedData['report_month']) ? Carbon::createFromDate($validatedData['report_year'], $validatedData['report_month'], 1) : null,
            'goal' => $validatedData['goal'] ?? '',
            'account_period_start' => $validatedData['account_period_start'] ?? null,
            'account_period_end' => $validatedData['account_period_end'] ?? null,
            'amount_sanctioned_overview' => $validatedData['amount_sanctioned_overview'] ?? 0,
            'amount_forwarded_overview' => $validatedData['amount_forwarded_overview'] ?? 0,
            'amount_in_hand' => $validatedData['amount_in_hand'] ?? 0,
            'total_balance_forwarded' => $validatedData['total_balance_forwarded'] ?? 0
        ]);

        if (!$report) {
            throw new Exception('Failed to create report');
        }
        Log::info('Report created successfully', ['report_id' => $report->report_id]);

        // Handle objectives and activities
        foreach ($request->input('expected_outcome', []) as $index => $expectedOutcome) {
            // Generate objective_id
            $objective_id_suffix = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $objective_id = "{$report_id}-{$objective_id_suffix}";

            $objectiveData = [
                'objective_id' => $objective_id,
                'report_id' => $report->report_id,
                'objective' => $request->input("objective.$index"), // Add this line
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

            // Save activities for each objective
            foreach ($request->input("month.$index", []) as $activityIndex => $month) {
                // Generate activity_id
                $activity_id_suffix = str_pad($activityIndex + 1, 3, '0', STR_PAD_LEFT);
                $activity_id = "{$objective_id}-{$activity_id_suffix}";

                $summaryActivities = $request->input("summary_activities.$index.$activityIndex.1");
                $qualitativeQuantitativeData = $request->input("qualitative_quantitative_data.$index.$activityIndex.1");
                $intermediateOutcomes = $request->input("intermediate_outcomes.$index.$activityIndex.1");

                $activityData = [
                    'activity_id' => $activity_id,
                    'objective_id' => $objective->objective_id,
                    'month' => $month,
                    'summary_activities' => $summaryActivities,
                    'qualitative_quantitative_data' => $qualitativeQuantitativeData,
                    'intermediate_outcomes' => $intermediateOutcomes,
                ];

                Log::info('Activity Data:', $activityData);

                $activity = DPActivity::create($activityData);
                Log::info('Activity Created: ', $activity->toArray());
            }
        }

        // Handle account details
        $this->handleAccountDetails($request, $report_id, $project_id);

        // Handle outlooks
        $this->handleOutlooks($request, $report_id);

        // Handle photos
        $this->handlePhotos($request, $report_id);

        DB::commit();
        Log::info('Transaction committed and report created successfully.');
        return redirect()->route('monthly.report.index')->with('success', 'Report submitted successfully.');
    } catch (ValidationException $ve) {
        DB::rollBack();
        Log::error('Validation failed', ['error' => $ve->errors()]);
        return back()->withErrors($ve);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to create report', ['error' => $e->getMessage()]);
        return back()->withErrors(['msg' => 'Failed to create report due to an error: ' . $e->getMessage()]);
    }
}

    private function handleAccountDetails($request, $report_id, $project_id)
    {
        $particulars = $request->input('particulars', []);
        foreach ($particulars as $index => $particular) {
            DPAccountDetail::create([
                'report_id' => $report_id,
                'project_id' => $project_id,
                'particulars' => $particular,
                'amount_forwarded' => $request->input("amount_forwarded.{$index}"),
                'amount_sanctioned' => $request->input("amount_sanctioned.{$index}"),
                'total_amount' => $request->input("total_amount.{$index}"),
                'expenses_last_month' => $request->input("expenses_last_month.{$index}"),
                'expenses_this_month' => $request->input("expenses_this_month.{$index}"),
                'total_expenses' => $request->input("total_expenses.{$index}"),
                'balance_amount' => $request->input("balance_amount.{$index}")
            ]);
        }
    }
    private function handleOutlooks($request, $report_id)
    {
        $outlookDates = $request->input('date', []);
        foreach ($outlookDates as $index => $date) {
            $outlook_id_suffix = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $outlook_id = "{$report_id}-{$outlook_id_suffix}";

            DPOutlook::create([
                'outlook_id' => $outlook_id,
                'report_id' => $report_id,
                'date' => $date,
                'plan_next_month' => $request->input("plan_next_month.{$index}")
            ]);
        }
    }
    private function handlePhotos($request, $report_id)
{
    if ($request->hasFile('photos')) {
        foreach ($request->file('photos') as $index => $file) {
            // Generate photo_id with 4-digit suffix
            $latestPhoto = DPPhoto::where('photo_id', 'LIKE', "{$report_id}-%")
                                  ->latest('photo_id')
                                  ->lockForUpdate()
                                  ->first();

            $max_suffix = $latestPhoto ? intval(substr($latestPhoto->photo_id, -4)) + 1 : 1;
            $photo_id = "{$report_id}-" . str_pad($max_suffix, 4, '0', STR_PAD_LEFT);

            $path = $file->store('ReportImages/Quarterly', 'public');

            DPPhoto::create([
                'photo_id' => $photo_id,
                'report_id' => $report_id,
                'photo_path' => $path,
                'description' => $request->input('photo_descriptions')[$index] ?? ''
            ]);
        }
    }
}
    protected function generateReportId($project_id)
    {
        $latestReport = DPReport::where('report_id', 'LIKE', "{$project_id}-%")
                                ->latest('report_id')
                                ->lockForUpdate()
                                ->first();

        if ($latestReport) {
            $max_suffix = intval(explode('-', $latestReport->report_id)[2]) + 1;
        } else {
            $max_suffix = 1; // Start from 01 if no reports found
        }

        return "{$project_id}-" . str_pad($max_suffix, 2, '0', STR_PAD_LEFT);
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

        $project = Project::where('project_id', $report->project_id)->firstOrFail();
        Log::info('Project retrieved successfully', ['project' => $project]);

        return view('reports.monthly.edit', compact('report', 'project'));
    }

    // public function update(Request $request, $report_id)
    // {
    //     Log::info('Entering update method', ['report_id' => $report_id, 'request' => $request->all()]);

    //     $request->validate([
    //         // validation rules
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         Log::info('Starting transaction for updating report');

    //         $report = DPReport::where('report_id', $report_id)->firstOrFail();
    //         $report->update([
    //             'total_beneficiaries' => $request->input('total_beneficiaries'),
    //             'report_month_year' => $request->input('report_month_year'),
    //             'goal' => $request->input('goal'),
    //             'account_period_start' => $request->input('account_period_start'),
    //             'account_period_end' => $request->input('account_period_end'),
    //             'amount_sanctioned_overview' => $request->input('amount_sanctioned_overview'),
    //             'amount_forwarded_overview' => $request->input('amount_forwarded_overview'),
    //             'amount_in_hand' => $request->input('amount_in_hand'),
    //             'total_balance_forwarded' => $request->input('total_balance_forwarded'),
    //             'status' => 'updated',
    //         ]);
    //         Log::info('Report updated', ['report' => $report]);

    //         $report->objectives()->delete();
    //         $report->accountDetails()->delete();
    //         $report->photos()->delete();
    //         $report->outlooks()->delete();
    //         Log::info('Old objectives, account details, photos, and outlooks deleted');

    //         foreach ($request->input('objective', []) as $index => $objective) {
    //             $objective = DPObjective::create([
    //                 'objective_id' => Str::uuid()->toString(),
    //                 'report_id' => $report->report_id,
    //                 'objective' => $objective,
    //                 'expected_outcome' => $request->input("expected_outcome.{$index}"),
    //                 'not_happened' => $request->input("not_happened.{$index}"),
    //                 'why_not_happened' => $request->input("why_not_happened.{$index}"),
    //                 'changes' => $request->input("changes.{$index}"),
    //                 'why_changes' => $request->input("why_changes.{$index}"),
    //                 'lessons_learnt' => $request->input("lessons_learnt.{$index}"),
    //                 'todo_lessons_learnt' => $request->input("todo_lessons_learnt.{$index}"),
    //             ]);
    //             Log::info('Objective created', ['objective' => $objective]);

    //             foreach ($request->input("summary_activities.{$index}", []) as $activityIndex => $summaryActivity) {
    //                 $activity = DPActivity::create([
    //                     'activity_id' => Str::uuid()->toString(),
    //                     'objective_id' => $objective->objective_id,
    //                     'month' => $request->input("month.{$index}.{$activityIndex}"),
    //                     'summary_activities' => $summaryActivity,
    //                     'qualitative_quantitative_data' => $request->input("qualitative_quantitative_data.{$index}.{$activityIndex}"),
    //                     'intermediate_outcomes' => $request->input("intermediate_outcomes.{$index}.{$activityIndex}"),
    //                 ]);
    //                 Log::info('Activity created', ['activity' => $activity]);
    //             }
    //         }

    //         foreach ($request->input('particulars', []) as $index => $particular) {
    //             $accountDetail = DPAccountDetail::create([
    //                 'account_detail_id' => Str::uuid()->toString(),
    //                 'report_id' => $report->report_id,
    //                 'particulars' => $particular,
    //                 'amount_forwarded' => $request->input("amount_forwarded.{$index}"),
    //                 'amount_sanctioned' => $request->input("amount_sanctioned.{$index}"),
    //                 'total_amount' => $request->input("total_amount.{$index}"),
    //                 'expenses_last_month' => $request->input("expenses_last_month.{$index}"),
    //                 'expenses_this_month' => $request->input("expenses_this_month.{$index}"),
    //                 'total_expenses' => $request->input("total_expenses.{$index}"),
    //                 'balance_amount' => $request->input("balance_amount.{$index}"),
    //             ]);
    //             Log::info('Account detail created', ['accountDetail' => $accountDetail]);
    //         }

    //         foreach ($request->file('photos', []) as $index => $photo) {
    //             $photoPath = $photo->store('photos', 'public');
    //             $dpPhoto = DPPhoto::create([
    //                 'photo_id' => Str::uuid()->toString(),
    //                 'report_id' => $report->report_id,
    //                 'photo_path' => $photoPath,
    //                 'photo_name' => $photo->getClientOriginalName(),
    //                 'description' => $request->input("photo_descriptions.{$index}"),
    //             ]);
    //             Log::info('Photo uploaded and created', ['dpPhoto' => $dpPhoto]);
    //         }

    //         foreach ($request->input('date', []) as $index => $date) {
    //             $outlook = DPOutlook::create([
    //                 'outlook_id' => Str::uuid()->toString(),
    //                 'report_id' => $report->report_id,
    //                 'date' => $date,
    //                 'plan_next_month' => $request->input("plan_next_month.{$index}"),
    //             ]);
    //             Log::info('Outlook created', ['outlook' => $outlook]);
    //         }

    //         DB::commit();
    //         Log::info('Transaction committed successfully');

    //         return redirect()->route('monthly.report.index')->with('success', 'Report updated successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error updating report', ['exception' => $e]);
    //         return redirect()->back()->withErrors('An error occurred while updating the report. Please try again.');
    //     }
    // }

    

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
