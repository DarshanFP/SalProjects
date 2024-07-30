<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\Project;
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

class ReportController extends Controller
{
    public function create($project_id)
    {
        // Retrieve the project details using the correct identifier
        $project = Project::where('project_id', $project_id)->firstOrFail();

        // Determine the highest phase for the given project
        $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');

        // Retrieve the budget data for the highest phase
        $budgets = ProjectBudget::where('project_id', $project->project_id)
                                ->where('phase', $highestPhase)
                                ->get();

        // Use the actual columns from the Project model
        $amountSanctioned = $project->amount_sanctioned ?? 0;  // total sanctioned amount for the project
        $amountForwarded = $project->amount_forwarded ?? 0;    // total amount forwarded for the project

        // Calculate expenses up to last month for each particular
        $expensesUpToLastMonth = DPAccountDetail::where('report_id', function($query) use ($project) {
            $query->select('id')
                ->from('dp_reports')
                ->where('project_id', $project->project_id)
                ->orderBy('created_at', 'desc')
                ->first();
        })->sum('expenses_this_month');

        $user = Auth::user();
        return view('reports.monthly.ReportCommonForm', compact('project', 'user', 'amountSanctioned', 'amountForwarded', 'budgets', 'expensesUpToLastMonth'));
    }


    public function store(Request $request)
    {
        // Log the request data
        Log::info('Store method called');
        Log::info('Request data: ', $request->all());

        // Validate the incoming request data
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
            'amount_forwarded_overview' => 'nullable|numeric',
            'amount_in_hand' => 'nullable|numeric',
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
            'photo_descriptions' => 'nullable|array',
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

        // Handle file uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $file) {
                Log::info('File Upload:', ['original_name' => $file->getClientOriginalName(), 'size' => $file->getSize()]);

                $path = $file->store('ReportImages/Monthly', 'public');
                Log::info('File Path:', ['path' => $path]);

                $photoData = [
                    'report_id' => $report->report_id,
                    'photo_path' => $path,
                    'description' => $request->photo_descriptions[$index] ?? '',
                ];

                Log::info('Photo Data:', $photoData);

                $photo = DPPhoto::create($photoData);
                Log::info('Photo Created: ', $photo->toArray());
            }
        }

        // Ensure particulars input array is initialized
        $particulars = $request->input('particulars', []);

        // Save account details
        foreach ($particulars as $index => $particularsItem) {
            $accountDetailData = [
                'report_id' => $report->report_id,
                'particulars' => $particularsItem,
                'amount_forwarded' => $request->input("amount_forwarded.$index"),
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

        return redirect()->route('monthly.report.index', ['project_id' => $request->project_id])->with('success', 'Report submitted successfully.');
    }

    public function index()
    {
        $reports = DPReport::where('user_id', Auth::id())->get();
        return view('monthly.report.index', compact('reports'));
    }

    public function show($report_id)
    {
        $report = DPReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks'])->findOrFail($report_id);
        return view('monthly.report.show', compact('report'));
    }

    public function edit($report_id)
    {
        $report = DPReport::with(['objectives.activities', 'photos', 'accountDetails'])->findOrFail($report_id);
        return view('monthly.report.edit', compact('report'));
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

        return redirect()->route('monthly.report.show', $report->report_id)->with('success', 'Report updated successfully.');
    }

    public function review($report_id)
    {
        $report = DPReport::with(['objectives.activities', 'photos', 'accountDetails'])->findOrFail($report_id);
        return view('monthly.report.review', compact('report'));
    }

    public function revert(Request $request, $report_id)
    {
        // Logic to revert with feedback from senior
        // ...
    }
}
