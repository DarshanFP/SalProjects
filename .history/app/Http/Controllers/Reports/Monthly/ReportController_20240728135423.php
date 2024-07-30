<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\DPAccountDetail;
use App\Models\Reports\Monthly\DPReport;
use App\Models\OldProjects\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function create($project_id)
    {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
        $budgets = ProjectBudget::where('project_id', $project->project_id)
                                ->where('phase', $highestPhase)
                                ->get();
        $amountSanctioned = $project->amount_sanctioned ?? 0;
        $amountForwarded = $project->amount_forwarded ?? 0;
        $expensesUpToLastMonth = DPAccountDetail::where('report_id', function($query) use ($project) {
            $query->select('report_id')
                ->from('dp_reports')
                ->where('project_id', $project->project_id)
                ->orderBy('created_at', 'desc')
                ->first();
        })->sum('expenses_this_month');
        $user = Auth::user();
        $newReportId = Str::uuid();

        return view('reports.monthly.ReportCommonForm', compact('project', 'user', 'amountSanctioned', 'amountForwarded', 'budgets', 'expensesUpToLastMonth', 'newReportId'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            \Log::info('Starting store method', ['request' => $request->all()]);

            $validatedData = $request->validate([
                'project_id' => 'required|string',
                'project_type' => 'required|string',
                'project_title' => 'required|string',
                'place' => 'required|string',
                'society_name' => 'required|string',
                'in_charge' => 'required|string',
                'total_beneficiaries' => 'required|integer',
                'report_month' => 'required|integer|min:1|max:12',
                'report_year' => 'required|integer|min:1900|max:2100',
                'goal' => 'required|string',
                'account_period_start' => 'required|date_format:Y-m-d',
                'account_period_end' => 'required|date_format:Y-m-d',
                'amount_sanctioned_overview' => 'required|numeric',
                'amount_forwarded_overview' => 'required|numeric',
                'amount_in_hand' => 'required|numeric',
                'total_balance_forwarded' => 'required|numeric',
                'objective.*' => 'required|string',
                'expected_outcome.*' => 'required|string',
                'summary_activities.*.*.*' => 'required|string',
                'qualitative_quantitative_data.*.*.*' => 'required|string',
                'intermediate_outcomes.*.*.*' => 'required|string',
                'not_happened.*' => 'required|string',
                'why_not_happened.*' => 'required|string',
                'changes.*' => 'required|string',
                'why_changes.*' => 'nullable|string',
                'lessons_learnt.*' => 'required|string',
                'todo_lessons_learnt.*' => 'required|string',
                'date.*' => 'required|date_format:Y-m-d',
                'plan_next_month.*' => 'required|string',
                'particulars.*' => 'required|string',
                'amount_forwarded.*' => 'required|numeric',
                'amount_sanctioned.*' => 'required|numeric',
                'total_amount.*' => 'required|numeric',
                'expenses_last_month.*' => 'required|numeric',
                'expenses_this_month.*' => 'required|numeric',
                'total_expenses.*' => 'required|numeric',
                'balance_amount.*' => 'required|numeric',
                'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
                'photo_descriptions.*' => 'nullable|string',
            ]);

            \Log::info('Validation passed', ['validatedData' => $validatedData]);

            $project = Project::where('project_id', $request->project_id)->first();
            if (!$project) {
                \Log::error('Project not found', ['project_id' => $request->project_id]);
                return redirect()->back()->withErrors(['project_id' => 'Project not found']);
            }
            \Log::info('Project found', ['project' => $project]);

            $lastReport = DPReport::where('project_id', $request->project_id)->orderBy('created_at', 'desc')->first();
            $newReportIdSuffix = $lastReport ? intval(explode('-', $lastReport->report_id)[1]) + 1 : 1;
            $newReportId = $request->project_id . '-' . str_pad($newReportIdSuffix, 2, '0', STR_PAD_LEFT);
            \Log::info('Generated new report ID', ['newReportId' => $newReportId]);

            $commencementMonthYear = '2024-01-01';

            $dpReport = DPReport::create([
                'report_id' => $newReportId,
                'user_id' => Auth::id(),
                'project_id' => $request->project_id,
                'project_title' => $request->project_title,
                'project_type' => $request->project_type,
                'place' => $request->place,
                'society_name' => $request->society_name,
                'commencement_month_year' => $commencementMonthYear,
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
                'status' => 1,
            ]);
            \Log::info('DPReport created', ['dpReport' => $dpReport]);

            foreach ($request->objective as $index => $objective) {
                $objectiveIdSuffix = $index + 1;
                $objectiveId = $newReportId . '-' . str_pad($objectiveIdSuffix, 2, '0', STR_PAD_LEFT);

                $dpObjective = DPObjective::create([
                    'objective_id' => $objectiveId,
                    'report_id' => $dpReport->report_id,
                    'objective' => $objective,
                    'expected_outcome' => $request->expected_outcome[$index],
                    'not_happened' => $request->not_happened[$index],
                    'why_not_happened' => $request->why_not_happened[$index],
                    'changes' => $request->changes[$index] === 'yes' ? 1 : 0,
                    'why_changes' => $request->why_changes[$index] ?? null,
                    'lessons_learnt' => $request->lessons_learnt[$index],
                    'todo_lessons_learnt' => $request->todo_lessons_learnt[$index],
                ]);
                \Log::info('DPObjective created', ['dpObjective' => $dpObjective]);

                foreach ($request->summary_activities[$index] as $activityIndex => $activity) {
                    $activityIdSuffix = $activityIndex + 1;
                    $activityId = $objectiveId . '-' . str_pad($activityIdSuffix, 2, '0', STR_PAD_LEFT);

                    $dpActivity = DPActivity::create([
                        'activity_id' => $activityId,
                        'objective_id' => $dpObjective->objective_id,
                        'month' => $request->report_month,
                        'summary_activities' => $activity[1],
                        'qualitative_quantitative_data' => $request->qualitative_quantitative_data[$index][$activityIndex][1],
                        'intermediate_outcomes' => $request->intermediate_outcomes[$index][$activityIndex][1],
                    ]);
                    \Log::info('DPActivity created', ['dpActivity' => $dpActivity]);
                }
            }

            foreach ($request->date as $index => $date) {
                $dpOutlook = DPOutlook::create([
                    'outlook_id' => (string) Str::uuid(),
                    'report_id' => $dpReport->report_id,
                    'date' => $date,
                    'plan_next_month' => $request->plan_next_month[$index],
                ]);
                \Log::info('DPOutlook created', ['dpOutlook' => $dpOutlook]);
            }

            foreach ($request->particulars as $index => $particular) {
                $dpAccountDetail = DPAccountDetail::create([
                    'account_detail_id' => (string) Str::uuid(),
                    'report_id' => $dpReport->report_id,
                    'particulars' => $particular,
                    'amount_forwarded' => $request->amount_forwarded[$index],
                    'amount_sanctioned' => $request->amount_sanctioned[$index],
                    'total_amount' => $request->total_amount[$index],
                    'expenses_last_month' => $request->expenses_last_month[$index],
                    'expenses_this_month' => $request->expenses_this_month[$index],
                    'total_expenses' => $request->total_expenses[$index],
                    'balance_amount' => $request->balance_amount[$index],
                ]);
                \Log::info('DPAccountDetail created', ['dpAccountDetail' => $dpAccountDetail]);
            }

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $index => $photo) {
                    $photoPath = $photo->store('photos', 'public');
                    $dpPhoto = DPPho::create([
                        'photo_id' => (string) Str::uuid(),
                        'report_id' => $dpReport->report_id,
                        'photo_path' => $photoPath,
                        'description' => $request->photo_descriptions[$index] ?? '',
                    ]);
                    \Log::info('DPPhoto created', ['dpPhoto' => $dpPhoto]);
                }
            }

            DB::commit();
            \Log::info('Store method completed successfully');
            return redirect()->route('monthly.report.index')->with('success', 'Report submitted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in store method', ['exception' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'Failed to submit report. Please try again.']);
        }
    }

    // Remaining methods (edit, update, show, index, review, revert) go here...
}
