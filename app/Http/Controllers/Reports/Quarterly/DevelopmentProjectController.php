<?php

namespace App\Http\Controllers\Reports\Quarterly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Quarterly\QRDLAnnexure;
use App\Models\Reports\Quarterly\RQDPReport;
use App\Models\Reports\Quarterly\RQDPObjective;
use App\Models\Reports\Quarterly\RQDPActivity;
use App\Models\Reports\Quarterly\RQDPPhoto;
use App\Models\Reports\Quarterly\RQDPAccountDetail;
use App\Models\Reports\Quarterly\RQDPOutlook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DevelopmentProjectController extends Controller
{
    public function create()
    {
        return view('reports.quarterly.developmentProject.reportform');
    }
    public function store(Request $request)
{
    // Log the request data
    Log::info('Store method called');
    Log::info('Request data: ', $request->all());

    // Validate the incoming request data
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
        'amount_sanctioned_overview' => 'nullable|numeric',
        'amount_forwarded_overview' => 'nullable|numeric',
        'amount_in_hand' => 'nullable|numeric',
        'photos' => 'nullable|array',
        'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
        'photo_descriptions' => 'nullable|array',
        'objective' => 'nullable|array', // Add this line
        'objective.*' => 'nullable|string', // Add this line
    ]);

    // Temporarily set user_id to null for testing if not authenticated
    $validatedData['user_id'] = auth()->check() ? auth()->id() : null;

    Log::info('Validated Data: ', $validatedData);

    // Create the report
    $report = RQDPReport::create($validatedData);
    Log::info('Report Created: ', $report->toArray());

    // Ensure input arrays are initialized
    $expected_outcome = $request->input('expected_outcome', []);
    $objectives = $request->input('objective', []); // Add this line
    $months = $request->input('month', []);

    Log::info('Expected Outcome:', $expected_outcome);
    Log::info('Months:', $months);

    // Save objectives and activities
    foreach ($expected_outcome as $index => $expectedOutcome) {
        $objectiveData = [
            'report_id' => $report->id,
            'objective' => $objectives[$index] ?? null, // Add this line
            'expected_outcome' => $expectedOutcome,
            'not_happened' => $request->input("not_happened.$index"),
            'why_not_happened' => $request->input("why_not_happened.$index"),
            'changes' => $request->input("changes.$index") === 'yes',
            'why_changes' => $request->input("why_changes.$index"),
            'lessons_learnt' => $request->input("lessons_learnt.$index"),
            'todo_lessons_learnt' => $request->input("todo_lessons_learnt.$index"),
        ];

        Log::info('Objective Data:', $objectiveData);

        $objective = RQDPObjective::create($objectiveData);
        Log::info('Objective Created: ', $objective->toArray());

        // Ensure months input array is initialized
        $activityMonths = $request->input("month.$index", []);

        // Save activities for each objective
        foreach ($activityMonths as $activityIndex => $month) {
            $summaryActivities = $request->input("summary_activities.$index.$activityIndex");
            $qualitativeQuantitativeData = $request->input("qualitative_quantitative_data.$index.$activityIndex");
            $intermediateOutcomes = $request->input("intermediate_outcomes.$index.$activityIndex");

            // Convert activity fields to strings if they are arrays
            $activityData = [
                'objective_id' => $objective->id,
                'month' => $month,
                'summary_activities' => is_array($summaryActivities) ? implode(', ', $summaryActivities) : $summaryActivities,
                'qualitative_quantitative_data' => is_array($qualitativeQuantitativeData) ? implode(', ', $qualitativeQuantitativeData) : $qualitativeQuantitativeData,
                'intermediate_outcomes' => is_array($intermediateOutcomes) ? implode(', ', $intermediateOutcomes) : $intermediateOutcomes,
            ];

            Log::info('Activity Data:', $activityData);

            $activity = RQDPActivity::create($activityData);
            Log::info('Activity Created: ', $activity->toArray());
        }
    }

    // Handle file uploads
    if ($request->hasFile('photos')) {
        foreach ($request->file('photos') as $index => $file) {
            Log::info('File Upload:', ['original_name' => $file->getClientOriginalName(), 'size' => $file->getSize()]);

            $path = $file->store('ReportImages/Quarterly', 'public');
            Log::info('File Path:', ['path' => $path]);

            $photoData = [
                'report_id' => $report->id,
                'photo_path' => $path,
                'description' => $request->photo_descriptions[$index] ?? '',
            ];

            Log::info('Photo Data:', $photoData);

            $photo = RQDPPhoto::create($photoData);
            Log::info('Photo Created: ', $photo->toArray());
        }
    }

    // Ensure particulars input array is initialized
    $particulars = $request->input('particulars', []);

    // Save account details
    foreach ($particulars as $index => $particularsItem) {
        $accountDetailData = [
            'report_id' => $report->id,
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

        $accountDetail = RQDPAccountDetail::create($accountDetailData);
        Log::info('Account Detail Created: ', $accountDetail->toArray());
    }

    // Save outlooks
    $outlookDates = $request->input('date', []);
    $planNextMonths = $request->input('plan_next_month', []);
    foreach ($outlookDates as $index => $date) {
        $outlookData = [
            'report_id' => $report->id,
            'date' => $date,
            'plan_next_month' => $planNextMonths[$index] ?? null,
        ];

        Log::info('Outlook Data:', $outlookData);

        $outlook = RQDPOutlook::create($outlookData);
        Log::info('Outlook Created: ', $outlook->toArray());
    }

    // Save annexure data
    $beneficiaryNames = $request->input('beneficiary_name', []);
    $supportDates = $request->input('support_date', []);
    $selfEmployments = $request->input('self_employment', []);
    $amountSanctioneds = $request->input('amount_sanctioned_annexure', []);
    $monthlyProfits = $request->input('monthly_profit', []);
    $annualProfits = $request->input('annual_profit', []);
    $impacts = $request->input('impact', []);
    $challenges = $request->input('challenges', []);
    foreach ($beneficiaryNames as $index => $beneficiaryName) {
        $annexureData = [
            'report_id' => $report->id,
            'beneficiary_name' => $beneficiaryName,
            'support_date' => $supportDates[$index] ?? null,
            'self_employment' => $selfEmployments[$index] ?? null,
            'amount_sanctioned' => $amountSanctioneds[$index] ?? null,
            'monthly_profit' => $monthlyProfits[$index] ?? null,
            'annual_profit' => $annualProfits[$index] ?? null,
            'impact' => $impacts[$index] ?? null,
            'challenges' => $challenges[$index] ?? null,
        ];

        Log::info('Annexure Data:', $annexureData);

        $annexure = QRDLAnnexure::create($annexureData);
        Log::info('Annexure Created: ', $annexure->toArray());
    }

    return redirect()->route('quarterly.developmentProject.create')->with('success', 'Report submitted successfully.');
}


    //LIST REPORTS
    public function index()
    {
        $reports = RQDPReport::where('user_id', Auth::id())->get();
        return view('reports.quarterly.developmentProject.list', compact('reports'));
    }

    // Show individual report when clicked on "view"
    public function show($id)
{
    $report = RQDPReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks'])->findOrFail($id);
    return view('reports.quarterly.developmentProject.show', compact('report'));
}


    public function edit($id)
    {
        // Logic to get the report data for editing
        $report = RQDPReport::with(['objectives.activities', 'photos', 'accountDetails'])->findOrFail($id);
        return view('reports.quarterly.developmentProject.edit', compact('report'));
    }
    public function update(Request $request, $id)
    {
        // Logic to update the report
        $report = RQDPReport::findOrFail($id);

        // Validate and update report data
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

        // Handle objectives, activities, photos, and account details similarly
        // ...

        return redirect()->route('quarterly.developmentProject.edit', $report->id)->with('success', 'Report updated successfully.');
    }

    public function review($id)
    {
        // Logic to get the report data for review by senior
        $report = RQDPReport::with(['objectives.activities', 'photos', 'accountDetails'])->findOrFail($id);
        return view('reports.quarterly.developmentProject.review', compact('report'));
    }

    public function revert(Request $request, $id)
    {
        // Logic to revert with feedback from senior
        // ...
    }
}
