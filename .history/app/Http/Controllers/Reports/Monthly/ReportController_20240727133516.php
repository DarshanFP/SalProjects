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
            $query->select('id')
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
        // Validate the request data
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'total_beneficiaries' => 'required|integer',
            'report_month' => 'required|integer|min:1|max:12',
            'report_year' => 'required|integer|min:1900|max:' . date('Y'),
            'goal' => 'nullable|string',
            'objective.*' => 'nullable|string',
            'expected_outcome.*' => 'nullable|string',
            'summary_activities.*.*.*' => 'nullable|string',
            'qualitative_quantitative_data.*.*.*' => 'nullable|string',
            'intermediate_outcomes.*.*.*' => 'nullable|string',
            'not_happened.*' => 'nullable|string',
            'why_not_happened.*' => 'nullable|string',
            'changes.*' => 'nullable|string',
            'why_changes.*' => 'nullable|string',
            'lessons_learnt.*' => 'nullable|string',
            'todo_lessons_learnt.*' => 'nullable|string',
            'date.*' => 'nullable|date',
            'plan_next_month.*' => 'nullable|string',
            'particulars.*' => 'nullable|string',
            'amount_forwarded.*' => 'nullable|numeric',
            'amount_sanctioned.*' => 'nullable|numeric',
            'expenses_last_month.*' => 'nullable|numeric',
            'expenses_this_month.*' => 'nullable|numeric',
            'photos.*' => 'nullable|file|image|max:3072',
            'photo_descriptions.*' => 'nullable|string',
        ]);

        // Save the main report data
        $project = Project::findOrFail($request->project_id);
        $report = $project->reports()->create([
            'total_beneficiaries' => $request->total_beneficiaries,
            'report_month' => $request->report_month,
            'report_year' => $request->report_year,
            'goal' => $request->goal,
        ]);

        // Save objectives and their activities
        foreach ($request->objective as $index => $objectiveText) {
            $objective = $report->objectives()->create([
                'objective' => $objectiveText,
                'expected_outcome' => $request->expected_outcome[$index] ?? null,
                'not_happened' => $request->not_happened[$index] ?? null,
                'why_not_happened' => $request->why_not_happened[$index] ?? null,
                'changes' => $request->changes[$index] ?? null,
                'why_changes' => $request->why_changes[$index] ?? null,
                'lessons_learnt' => $request->lessons_learnt[$index] ?? null,
                'todo_lessons_learnt' => $request->todo_lessons_learnt[$index] ?? null,
            ]);

            // Save activities for each objective
            foreach ($request->summary_activities[$index] ?? [] as $activityIndex => $activities) {
                foreach ($activities as $activityData) {
                    $objective->activities()->create([
                        'month' => $request->month[$index][$activityIndex] ?? null,
                        'summary' => $activityData[0] ?? null,
                        'qualitative_quantitative_data' => $request->qualitative_quantitative_data[$index][$activityIndex][0] ?? null,
                        'intermediate_outcomes' => $request->intermediate_outcomes[$index][$activityIndex][0] ?? null,
                    ]);
                }
            }
        }

        // Save outlook data
        foreach ($request->date as $index => $date) {
            $report->outlooks()->create([
                'date' => $date,
                'plan_next_month' => $request->plan_next_month[$index] ?? null,
            ]);
        }

        // Save account statements
        foreach ($request->particulars as $index => $particular) {
            $report->accountStatements()->create([
                'particulars' => $particular,
                'amount_forwarded' => $request->amount_forwarded[$index] ?? 0,
                'amount_sanctioned' => $request->amount_sanctioned[$index] ?? 0,
                'total_amount' => ($request->amount_forwarded[$index] ?? 0) + ($request->amount_sanctioned[$index] ?? 0),
                'expenses_last_month' => $request->expenses_last_month[$index] ?? 0,
                'expenses_this_month' => $request->expenses_this_month[$index] ?? 0,
                'total_expenses' => ($request->expenses_last_month[$index] ?? 0) + ($request->expenses_this_month[$index] ?? 0),
                'balance_amount' => (($request->amount_forwarded[$index] ?? 0) + ($request->amount_sanctioned[$index] ?? 0)) - (($request->expenses_last_month[$index] ?? 0) + ($request->expenses_this_month[$index] ?? 0)),
            ]);
        }

        // Save photos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $photo) {
                $path = $photo->store('photos', 'public');
                $report->photos()->create([
                    'path' => $path,
                    'description' => $request->photo_descriptions[$index] ?? null,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Monthly report has been successfully submitted.');
    }



    public function edit($report_id)
    {
        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])->findOrFail($report_id);
        $project = Project::where('project_id', $report->project_id)->firstOrFail();
        $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
        $budgets = ProjectBudget::where('project_id', $project->project_id)
                                ->where('phase', $highestPhase)
                                ->get();
        $user = Auth::user();

        return view('reports.monthly.ReportCommonForm', compact('report', 'project', 'budgets', 'user'));
    }

    public function update(Request $request, $report_id)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,project_id',
            'total_beneficiaries' => 'required|integer',
            'report_month' => 'required|integer|min:1|max:12',
            'report_year' => 'required|integer|min:1900|max:' . date('Y'),
            // Add validation for other fields as necessary
        ]);

        $report = DPReport::findOrFail($report_id);
        $report->update([
            'project_id' => $request->project_id,
            'project_title' => $request->project_title,
            'project_type' => $request->project_type,
            'place' => $request->place,
            'society_name' => $request->society_name,
            'commencement_month_year' => $request->commencement_month_year,
            'in_charge' => $request->in_charge,
            'total_beneficiaries' => $request->total_beneficiaries,
            'report_month_year' => $request->report_year . '-' . $request->report_month . '-01',
            'goal' => $request->goal,
            'account_period_start' => $request->account_period_start,
            'account_period_end' => $request->account_period_end,
            'amount_sanctioned_overview' => $request->amount_sanctioned_overview,
            'amount_forwarded_overview' => $request->amount_forwarded_overview,
            'amount_in_hand' => $request->amount_in_hand,
            'total_balance_forwarded' => $request->total_balance_forwarded,
            'status' => 1,
        ]);

        // Update Objectives
        DPObjective::where('report_id', $report->report_id)->delete();
        if ($request->has('objective')) {
            foreach ($request->objective as $index => $objective) {
                $objectiveModel = DPObjective::create([
                    'objective_id' => Str::uuid(),
                    'report_id' => $report->report_id,
                    'objective' => $objective,
                    'expected_outcome' => $request->expected_outcome[$index],
                    'not_happened' => $request->not_happened[$index],
                    'why_not_happened' => $request->why_not_happened[$index],
                    'changes' => $request->changes[$index],
                    'why_changes' => $request->why_changes[$index] ?? null,
                    'lessons_learnt' => $request->lessons_learnt[$index],
                    'todo_lessons_learnt' => $request->todo_lessons_learnt[$index],
                ]);

                // Update Activities for each Objective
                if ($request->has('summary_activities.' . $index)) {
                    foreach ($request->summary_activities[$index] as $activityIndex => $summary_activity) {
                        DPActivity::create([
                            'activity_id' => Str::uuid(),
                            'objective_id' => $objectiveModel->objective_id,
                            'month' => $request->month[$index][$activityIndex],
                            'summary_activities' => $summary_activity,
                            'qualitative_quantitative_data' => $request->qualitative_quantitative_data[$index][$activityIndex],
                            'intermediate_outcomes' => $request->intermediate_outcomes[$index][$activityIndex],
                        ]);
                    }
                }
            }
        }

        // Update Account Details
        DPAccountDetail::where('report_id', $report->report_id)->delete();
        if ($request->has('particulars')) {
            foreach ($request->particulars as $index => $particular) {
                DPAccountDetail::create([
                    'account_detail_id' => Str::uuid(),
                    'report_id' => $report->report_id,
                    'particulars' => $particular,
                    'amount_forwarded' => $request->amount_forwarded[$index],
                    'amount_sanctioned' => $request->amount_sanctioned[$index],
                    'total_amount' => $request->total_amount[$index],
                    'expenses_last_month' => $request->expenses_last_month[$index],
                    'expenses_this_month' => $request->expenses_this_month[$index],
                    'total_expenses' => $request->total_expenses[$index],
                    'balance_amount' => $request->balance_amount[$index],
                ]);
            }
        }

        // Update Photos
        DPPhoto::where('report_id', $report->report_id)->delete();
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $photo) {
                $path = $photo->store('photos', 'public');
                DPPhoto::create([
                    'photo_id' => Str::uuid(),
                    'report_id' => $report->report_id,
                    'photo_path' => $path,
                    'description' => $request->photo_descriptions[$index],
                ]);
            }
        }

        // Update Outlooks
        DPOutlook::where('report_id', $report->report_id)->delete();
        if ($request->has('date')) {
            foreach ($request->date as $index => $date) {
                DPOutlook::create([
                    'outlook_id' => Str::uuid(),
                    'report_id' => $report->report_id,
                    'date' => $date,
                    'plan_next_month' => $request->plan_next_month[$index],
                ]);
            }
        }

        return redirect()->route('monthly.report.index')->with('success', 'Report updated successfully.');
    }

    public function show($report_id)
    {
        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])->findOrFail($report_id);
        return view('reports.monthly.developmentProject.show', compact('report'));
    }

    public function index()
    {
        $reports = DPReport::with('project', 'user')->get();
        return view('reports.monthly.developmentProject.index', compact('reports'));
    }

    public function review($report_id)
    {
        $report = DPReport::with(['objectives', 'accountDetails', 'photos', 'outlooks'])->findOrFail($report_id);
        return view('reports.monthly.developmentProject.review', compact('report'));
    }

    public function revert(Request $request, $report_id)
    {
        $report = DPReport::findOrFail($report_id);
        $report->update(['status' => 2]); // 2 for reverted status
        return redirect()->route('monthly.report.index')->with('success', 'Report reverted successfully.');
    }
}
