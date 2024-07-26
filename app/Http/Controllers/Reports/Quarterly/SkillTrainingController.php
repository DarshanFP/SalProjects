<?php

namespace App\Http\Controllers\Reports\Quarterly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Quarterly\RQSTAccountDetails;
use App\Models\Reports\Quarterly\RQSTActivity;
use App\Models\Reports\Quarterly\RQSTObjective;
use App\Models\Reports\Quarterly\RQSTOutlook;
use App\Models\Reports\Quarterly\RQSTPhoto;
use App\Models\Reports\Quarterly\RQSTTraineeProfile;
use App\Models\Reports\Quarterly\RQSTReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SkillTrainingController extends Controller
{
    public function create()
    {
        return view('reports.quarterly.skillTraining.reportform');
    }

    public function store(Request $request)
{
    // Log the request data
    Log::info('Store method called');
    Log::info('Request data: ', $request->all());

    // Validate the incoming request data
    $validatedData = $request->validate([
        // Basic Information
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
        'prjct_amount_sanctioned' => 'nullable|numeric',
        'l_y_amount_forwarded' => 'nullable|numeric',
        'amount_in_hand' => 'nullable|numeric',
        'total_balance_forwarded' => 'nullable|numeric',
        'photos' => 'nullable|array',
        'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
        'photo_descriptions' => 'nullable|array',
        // Trainee Profiles
        'below_9' => 'nullable|integer',
        '10_fail' => 'nullable|integer',
        '10_pass' => 'nullable|integer',
        'intermediate' => 'nullable|integer',
        'above_intermediate' => 'nullable|integer',
        'other_education' => 'nullable|string',
        'count_other_education' => 'nullable|integer',
        'objective' => 'nullable|array', // Add this line
        'objective.*' => 'nullable|string', // Add this line
    ]);

    // Temporarily set user_id to null for testing if not authenticated
    $validatedData['user_id'] = auth()->check() ? auth()->id() : null;

    // Calculate amount_in_hand
    $validatedData['amount_in_hand'] = ($validatedData['prjct_amount_sanctioned'] ?? 0) + ($validatedData['l_y_amount_forwarded'] ?? 0);

    Log::info('Validated Data: ', $validatedData);

    DB::transaction(function () use ($validatedData, $request) {
        // Create the report
        $report = RQSTReport::create($validatedData);
        Log::info('Report Created: ', $report->toArray());

        // Save trainee profiles
        $educationCategories = [
            'below_9',
            '10_fail',
            '10_pass',
            'intermediate',
            'above_intermediate',
        ];

        foreach ($educationCategories as $category) {
            if ($request->input($category) !== null) {
                $traineeProfileData = [
                    'report_id' => $report->id,
                    'education_category' => str_replace('_', ' ', $category),
                    'number' => $request->input($category),
                ];

                Log::info('Trainee Profile Data:', $traineeProfileData);

                $traineeProfile = RQSTTraineeProfile::create($traineeProfileData);
                Log::info('Trainee Profile Created: ', $traineeProfile->toArray());
            }
        }

        // Save "other education" profile if provided
        if ($request->input('other_education') !== null && $request->input('count_other_education') !== null) {
            $traineeProfileData = [
                'report_id' => $report->id,
                'education_category' => $request->input('other_education'),
                'number' => $request->input('count_other_education'),
            ];

            Log::info('Other Education Trainee Profile Data:', $traineeProfileData);

            $traineeProfile = RQSTTraineeProfile::create($traineeProfileData);
            Log::info('Other Education Trainee Profile Created: ', $traineeProfile->toArray());
        }

        // Save objectives and activities
        foreach ($request->input('expected_outcome', []) as $index => $expectedOutcome) {
            $objectiveData = [
                'report_id' => $report->id,
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

            $objective = RQSTObjective::create($objectiveData);
            Log::info('Objective Created: ', $objective->toArray());

            // Save activities for each objective
            foreach ($request->input("month.$index", []) as $activityIndex => $month) {
                $summaryActivities = $request->input("summary_activities.$index.$activityIndex.1");
                $qualitativeQuantitativeData = $request->input("qualitative_quantitative_data.$index.$activityIndex.1");
                $intermediateOutcomes = $request->input("intermediate_outcomes.$index.$activityIndex.1");

                $activityData = [
                    'objective_id' => $objective->id,
                    'month' => $month,
                    'summary_activities' => $summaryActivities,
                    'qualitative_quantitative_data' => $qualitativeQuantitativeData,
                    'intermediate_outcomes' => $intermediateOutcomes,
                ];

                Log::info('Activity Data:', $activityData);

                $activity = RQSTActivity::create($activityData);
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

                $photo = RQSTPhoto::create($photoData);
                Log::info('Photo Created: ', $photo->toArray());
            }
        }

        // Save account details
        foreach ($request->input('particulars', []) as $index => $particularsItem) {
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

            $accountDetail = RQSTAccountDetails::create($accountDetailData);
            Log::info('Account Detail Created: ', $accountDetail->toArray());
        }

        // Save outlooks
        foreach ($request->input('date', []) as $index => $date) {
            $outlookData = [
                'report_id' => $report->id,
                'date' => $date,
                'plan_next_month' => $request->input("plan_next_month.$index"),
            ];

            Log::info('Outlook Data:', $outlookData);

            $outlook = RQSTOutlook::create($outlookData);
            Log::info('Outlook Created: ', $outlook->toArray());
        }
    });

    return redirect()->route('quarterly.skillTraining.create')->with('success', 'Report submitted successfully.');
}



    //LIST REPORTS
    public function index()
    {
        $reports = RQSTReport::where('user_id', Auth::id())->get();
        return view('reports.quarterly.skillTraining.list', compact('reports'));
    }

    // view individual report for executor when clicked on view button
    public function show($id)
    {
        $report = RQSTReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks', 'traineeProfiles'])->findOrFail($id);
        return view('reports.quarterly.skillTraining.show', compact('report'));
    }


    public function edit($id)
    {
        $report = RQSTReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks', 'traineeProfiles'])->findOrFail($id);
        return view('reports.quarterly.skillTraining.edit', compact('report'));
    }

    public function update(Request $request, $id)
    {
        // Logic to update the report
        $report = RQSTReport::findOrFail($id);

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
            'amount_sanctioned_overview' => 'nullable|numeric',
            'amount_forwarded_overview' => 'nullable|numeric',
            'total_amount_overview' => 'nullable|numeric',
            'total_balance_forwarded' => 'nullable|numeric',
        ]);

        $report->update($validatedData);

        // Handle objectives, activities, photos, and account details similarly
        // ...

        return redirect()->route('quarterly.skillTraining.edit', $report->id)->with('success', 'Report updated successfully.');
    }

    public function review($id)
    {
        $report = RQSTReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks', 'traineeProfiles'])->findOrFail($id);
        return view('reports.quarterly.skillTraining.review', compact('report'));
    }

    public function revert(Request $request, $id)
    {
        // Logic to revert with feedback from senior
        // ...
    }
}
