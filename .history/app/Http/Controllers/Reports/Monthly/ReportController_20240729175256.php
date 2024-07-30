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
        Log::info('Entering store method', ['request' => $request->all()]);

        $request->validate([
            'total_beneficiaries' => 'required|integer',
            'report_month' => 'required|integer',
            'report_year' => 'required|integer',
            'goal' => 'required|string',
            'objective.*' => 'required|string',
            'expected_outcome.*' => 'required|string',
            'not_happened.*' => 'required|string',
            'why_not_happened.*' => 'required|string',
            'changes.*' => 'required|string',
            'why_changes.*' => 'sometimes|string|nullable',
            'lessons_learnt.*' => 'required|string',
            'todo_lessons_learnt.*' => 'required|string',
            'date.*' => 'required|date',
            'plan_next_month.*' => 'required|string',
            'amount_forwarded.*' => 'required|numeric',
            'amount_sanctioned.*' => 'required|numeric',
            'total_amount.*' => 'required|numeric',
            'expenses_last_month.*' => 'required|numeric',
            'expenses_this_month.*' => 'required|numeric',
            'total_expenses.*' => 'required|numeric',
            'balance_amount.*' => 'required|numeric',
            'account_period_start' => 'required|date',
            'account_period_end' => 'required|date',
            'amount_sanctioned_overview' => 'required|numeric',
            'amount_forwarded_overview' => 'required|numeric',
            'amount_in_hand' => 'required|numeric',
            'total_balance_forwarded' => 'required|numeric',
            'photos.*' => 'sometimes|image|max:5120',
            'photo_descriptions.*' => 'required_with:photos.*|string',
        ]);

        DB::beginTransaction();

        try {
            Log::info('Starting transaction for storing report');

            // Generate the report ID based on the project ID and an incremental value
            $latestReport = DPReport::where('project_id', $request->project_id)->latest()->first();
            $incrementalId = $latestReport ? (int)substr($latestReport->report_id, -2) + 1 : 1;
            $reportId = $request->project_id . '-' . str_pad($incrementalId, 2, '0', STR_PAD_LEFT);

            // Store the main report details
            $report = DPReport::create([
                'report_id' => $reportId,
                'user_id' => Auth::id(),
                'project_id' => $request->project_id,
                'project_title' => $request->project_title,
                'project_type' => $request->project_type,
                'place' => $request->place,
                'society_name' => $request->society_name,
                'commencement_month_year' => $request->commencement_month_year,
                'in_charge' => $request->in_charge,
                'total_beneficiaries' => $request->total_beneficiaries,
                'report_month_year' => $request->report_year . '-' . str_pad($request->report_month, 2, '0', STR_PAD_LEFT) . '-01',
                'goal' => $request->goal,
                'account_period_start' => $request->account_period_start,
                'account_period_end' => $request->account_period_end,
                'amount_sanctioned_overview' => $request->amount_sanctioned_overview,
                'amount_forwarded_overview' => $request->amount_forwarded_overview,
                'amount_in_hand' => $request->amount_in_hand,
                'total_balance_forwarded' => $request->total_balance_forwarded,
                'status' => 'underwriting',
            ]);

            Log::info('Report stored', ['report_id' => $report->report_id]);

            // Store objectives and related data
            foreach ($request->objective as $index => $objective) {
                $objectiveModel = DPObjective::create([
                    'objective_id' => Str::uuid(),
                    'report_id' => $report->report_id,
                    'objective' => $objective,
                    'expected_outcome' => $request->expected_outcome[$index],
                    'not_happened' => $request->not_happened[$index],
                    'why_not_happened' => $request->why_not_happened[$index],
                    'changes' => $request->changes[$index] === 'yes' ? 1 : 0,
                    'why_changes' => $request->changes[$index] === 'yes' ? $request->why_changes[$index] : null,
                    'lessons_learnt' => $request->lessons_learnt[$index],
                    'todo_lessons_learnt' => $request->todo_lessons_learnt[$index],
                ]);

                Log::info('Objective stored', ['objective_id' => $objectiveModel->objective_id]);

                // Store activities related to the objective
                if (isset($request->month[$index])) {
                    foreach ($request->month[$index] as $activityIndex => $month) {
                        DPActivity::create([
                            'activity_id' => Str::uuid(),
                            'objective_id' => $objectiveModel->objective_id,
                            'month' => date('n', strtotime($month)), // Convert month name to month number
                            'summary_activities' => $request->summary_activities[$index][$activityIndex][1],
                            'qualitative_quantitative_data' => $request->qualitative_quantitative_data[$index][$activityIndex][1],
                            'intermediate_outcomes' => $request->intermediate_outcomes[$index][$activityIndex][1],
                        ]);

                        Log::info('Activity stored', ['activity_id' => Str::uuid()]);
                    }
                }
            }

            // Store outlook data
            foreach ($request->date as $index => $date) {
                DPOutlook::create([
                    'outlook_id' => Str::uuid(),
                    'report_id' => $report->report_id,
                    'date' => $date,
                    'plan_next_month' => $request->plan_next_month[$index],
                ]);

                Log::info('Outlook stored', ['outlook_id' => Str::uuid()]);
            }

            // Store account details
            foreach ($request->particulars as $index => $particular) {
                DPAccountDetail::create([
                    'account_detail_id' => $report->report_id . '-' . ($index + 1),
                    'report_id' => $report->report_id,
                    'particular' => $particular,
                    'amount_forwarded' => $request->amount_forwarded[$index],
                    'amount_sanctioned' => $request->amount_sanctioned[$index],
                    'total_amount' => $request->total_amount[$index],
                    'expenses_last_month' => $request->expenses_last_month[$index],
                    'expenses_this_month' => $request->expenses_this_month[$index],
                    'total_expenses' => $request->total_expenses[$index],
                    'balance_amount' => $request->balance_amount[$index],
                ]);

                Log::info('Account detail stored', ['account_detail_id' => $report->report_id . '-' . ($index + 1)]);
            }

            // Handle photo uploads
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $index => $photo) {
                    $path = $photo->store('photos', 'public');

                    DPPhoto::create([
                        'photo_id' => Str::uuid(),
                        'report_id' => $report->report_id,
                        'photo_path' => $path,
                        'description' => $request->photo_descriptions[$index + 1], // Adjusting for 1-based index
                    ]);

                    Log::info('Photo stored', ['photo_path' => $path]);
                }
            }

            DB::commit();
            Log::info('Transaction committed successfully');

            return redirect()->route('monthly.report.index')->with('success', 'Report submitted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing report', ['exception' => $e]);
            return redirect()->back()->withErrors('Error storing report. Please try again.')->withInput();
        }
    }
