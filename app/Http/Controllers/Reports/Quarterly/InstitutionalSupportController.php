<?php

namespace App\Http\Controllers\Reports\Quarterly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Quarterly\RQISReport;
use App\Models\Reports\Quarterly\RQISObjective;
use App\Models\Reports\Quarterly\RQISActivity;
use App\Models\Reports\Quarterly\RQISPhoto;
use App\Models\Reports\Quarterly\RQISAccountDetail;
use App\Models\Reports\Quarterly\RQISOutlook;
use App\Models\Reports\Quarterly\RQISAgeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InstitutionalSupportController extends Controller
{
    public function create()
    {
        return view('reports.quarterly.institutionalSupport.reportform');
    }

    public function store(Request $request)
    {
        // Log the request data
        Log::info('Store method called');
        LogHelper::logSafeRequest('Request data', $request, LogHelper::getReportAllowedFields());

        // Validate the incoming request data
        $validatedData = $request->validate([
            'project_title' => 'nullable|string|max:255',
            'place' => 'nullable|string|max:255',
            'society_name' => 'nullable|string|max:255',
            'commencement_month_year' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'in_charge' => 'nullable|string|max:255',
            'total_beneficiaries' => 'nullable|integer',
            'institution_type' => 'nullable|string|max:255',
            'beneficiary_statistics' => 'nullable|string',
            'monitoring_period' => 'nullable|string|max:255',
            'goal' => 'nullable|string',
            'account_period_start' => 'nullable|date',
            'account_period_end' => 'nullable|date',
            'amount_sanctioned_overview' => 'nullable|numeric',
            'amount_forwarded_overview' => 'nullable|numeric',
            'total_amount_overview' => 'nullable|numeric',
            'total_balance_forwarded' => 'nullable|numeric',
            'amount_in_hand' => 'nullable|numeric',
            'total_up_to_previous_below_5' => 'nullable|integer',
            'total_present_academic_below_5' => 'nullable|integer',
            'total_up_to_previous_6_10' => 'nullable|integer',
            'total_present_academic_6_10' => 'nullable|integer',
            'total_up_to_previous_11_15' => 'nullable|integer',
            'total_present_academic_11_15' => 'nullable|integer',
            'total_up_to_previous_16_above' => 'nullable|integer',
            'total_present_academic_16_above' => 'nullable|integer',
            'grand_total_up_to_previous' => 'nullable|integer',
            'grand_total_present_academic' => 'nullable|integer',
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
        $report = RQISReport::create($validatedData);
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

            $objective = RQISObjective::create($objectiveData);
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

                $activity = RQISActivity::create($activityData);
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

                $photo = RQISPhoto::create($photoData);
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

            $accountDetail = RQISAccountDetail::create($accountDetailData);
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

            $outlook = RQISOutlook::create($outlookData);
            Log::info('Outlook Created: ', $outlook->toArray());
        }

        // Save age profiles
        $ageGroups = [
            'below_5' => 'below 5',
            '6_10' => '6 to 10',
            '11_15' => '11 to 15',
            '16_above' => '16 and above'
        ];

        foreach ($ageGroups as $key => $label) {
            for ($i = 1; $i <= 3; $i++) {
                $ageProfileData = [
                    'report_id' => $report->id,
                    'age_group' => $label,
                    'education' => $request->input("education_{$key}_{$i}"),
                    'up_to_previous_year' => $request->input("up_to_previous_{$key}_{$i}"),
                    'present_academic_year' => $request->input("present_academic_{$key}_{$i}"),
                ];

                Log::info('Age Profile Data:', $ageProfileData);

                $ageProfile = RQISAgeProfile::create($ageProfileData);
                Log::info('Age Profile Created: ', $ageProfile->toArray());
            }
        }

        // Calculate totals for age profiles
        $totals = [
            'total_up_to_previous_below_5' => 0,
            'total_present_academic_below_5' => 0,
            'total_up_to_previous_6_10' => 0,
            'total_present_academic_6_10' => 0,
            'total_up_to_previous_11_15' => 0,
            'total_present_academic_11_15' => 0,
            'total_up_to_previous_16_above' => 0,
            'total_present_academic_16_above' => 0,
        ];

        foreach ($ageGroups as $key => $label) {
            for ($i = 1; $i <= 3; $i++) {
                $totals["total_up_to_previous_{$key}"] += (int)$request->input("up_to_previous_{$key}_{$i}", 0);
                $totals["total_present_academic_{$key}"] += (int)$request->input("present_academic_{$key}_{$i}", 0);
            }
        }

        $totals['grand_total_up_to_previous'] = array_sum(array_slice($totals, 0, 4));
        $totals['grand_total_present_academic'] = array_sum(array_slice($totals, 4, 4));

        Log::info('Age Profile Totals:', $totals);

        $report->update($totals);

        return redirect()->route('quarterly.institutionalSupport.create')->with('success', 'Report submitted successfully.');
    }

    //LIST REPORTS
    public function index()
    {
        // Eager load relationships to prevent N+1 queries
        $reports = RQISReport::where('user_id', Auth::id())
            ->with(['user', 'project', 'accountDetails'])
            ->get();
        return view('reports.quarterly.institutionalSupport.list', compact('reports'));
    }

    // view individual report when clicked on "view"
    public function show($id)
{
    $report = RQISReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks', 'ageProfiles'])->findOrFail($id);
    return view('reports.quarterly.institutionalSupport.show', compact('report'));
}


    public function edit($id)
    {
        // Logic to get the report data for editing
        $report = RQISReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks', 'ageProfiles'])->findOrFail($id);
        return view('reports.quarterly.institutionalSupport.edit', compact('report'));
    }

    public function update(Request $request, $id)
    {
        // Logic to update the report
        $report = RQISReport::findOrFail($id);

        // Validate and update report data
        $validatedData = $request->validate([
            'project_title' => 'nullable|string|max:255',
            'place' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'in_charge' => 'nullable|string|max:255',
            'institution_type' => 'nullable|string|max:255',
            'beneficiary_statistics' => 'nullable|string',
            'monitoring_period' => 'nullable|string|max:255',
            'goal' => 'nullable|string',
            'account_period_start' => 'nullable|date',
            'account_period_end' => 'nullable|date',
            'amount_sanctioned_overview' => 'nullable|numeric',
            'amount_forwarded_overview' => 'nullable|numeric',
            'total_amount_overview' => 'nullable|numeric',
            'total_balance_forwarded' => 'nullable|numeric',
        ]);

        $report->update($validatedData);

        // Handle objectives, activities, photos, and account details similarly
        // ...

        return redirect()->route('quarterly.institutionalSupport.edit', $report->id)->with('success', 'Report updated successfully.');
    }

    public function review($id)
    {
        // Logic to get the report data for review by senior
        $report = RQISReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks', 'ageProfiles'])->findOrFail($id);
        return view('reports.quarterly.institutionalSupport.review', compact('report'));
    }

    public function revert(Request $request, $id)
    {
        // Logic to revert with feedback from senior
        // ...
    }
}
