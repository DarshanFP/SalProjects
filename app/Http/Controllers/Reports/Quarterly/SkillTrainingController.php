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
            'objective' => 'nullable|array',
            'objective.*' => 'nullable|string',
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
                    'objective' => $request->input("objective.$index"),
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
        Log::info('Update method initiated', ['report_id' => $id]);

        DB::beginTransaction();
        try {
            $report = RQSTReport::findOrFail($id);

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
                'photos' => 'nullable|array',
                'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
                'photo_descriptions' => 'nullable|array',
                'objective' => 'nullable|array',
                'objective.*' => 'nullable|string',
            ]);

            // Update the main report
            $report->update($validatedData);

            // Handle related data updates
            $this->updateObjectivesAndActivities($request, $report->id);
            $this->updateAccountDetails($request, $report->id);
            $this->updateOutlooks($request, $report->id);
            $this->updatePhotos($request, $report->id);
            $this->updateTraineeProfiles($request, $report->id);

            DB::commit();
            Log::info('Transaction committed and report updated successfully.');
            return redirect()->route('quarterly.skillTraining.edit', $report->id)->with('success', 'Report updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update report', ['error' => $e->getMessage()]);
            return back()->withErrors(['msg' => 'Failed to update report due to an error: ' . $e->getMessage()]);
        }
    }

    private function updateObjectivesAndActivities($request, $report_id)
    {
        $expected_outcome = $request->input('expected_outcome', []);
        $objectives = $request->input('objective', []);
        $currentObjectiveIds = [];

        foreach ($expected_outcome as $index => $expectedOutcome) {
            $objectiveId = $request->input("objective_id.{$index}");

            $objectiveData = [
                'report_id' => $report_id,
                'objective' => $objectives[$index] ?? null,
                'expected_outcome' => $expectedOutcome,
                'not_happened' => $request->input("not_happened.$index"),
                'why_not_happened' => $request->input("why_not_happened.$index"),
                'changes' => $request->input("changes.$index") === 'yes',
                'why_changes' => $request->input("why_changes.$index"),
                'lessons_learnt' => $request->input("lessons_learnt.$index"),
                'todo_lessons_learnt' => $request->input("todo_lessons_learnt.$index"),
            ];

            if ($objectiveId) {
                // Update existing objective
                $objective = RQSTObjective::where('id', $objectiveId)
                                        ->where('report_id', $report_id)
                                        ->first();
                if ($objective) {
                    $objective->update($objectiveData);
                    $currentObjectiveIds[] = $objectiveId;
                    Log::info("Objective updated: {$objectiveId}");
                }
            } else {
                // Create new objective
                $objective = RQSTObjective::create($objectiveData);
                $currentObjectiveIds[] = $objective->id;
                Log::info("Objective created: {$objective->id}");
            }

            // Handle activities for this objective
            $this->updateActivities($request, $objective, $index);
        }

        // Remove objectives not included in the request
        RQSTObjective::where('report_id', $report_id)
                    ->whereNotIn('id', $currentObjectiveIds)
                    ->delete();
        Log::info('Removed outdated objectives for report_id: ' . $report_id);
    }

    private function updateActivities($request, $objective, $objectiveIndex)
    {
        $activityMonths = $request->input("month.$objectiveIndex", []);
        $currentActivityIds = [];

        foreach ($activityMonths as $activityIndex => $month) {
            $activityId = $request->input("activity_id.$objectiveIndex.$activityIndex");

            $summaryActivities = $request->input("summary_activities.$objectiveIndex.$activityIndex");
            $qualitativeQuantitativeData = $request->input("qualitative_quantitative_data.$objectiveIndex.$activityIndex");
            $intermediateOutcomes = $request->input("intermediate_outcomes.$objectiveIndex.$activityIndex");

            $activityData = [
                'objective_id' => $objective->id,
                'month' => $month,
                'summary_activities' => is_array($summaryActivities) ? implode(', ', $summaryActivities) : $summaryActivities,
                'qualitative_quantitative_data' => is_array($qualitativeQuantitativeData) ? implode(', ', $qualitativeQuantitativeData) : $qualitativeQuantitativeData,
                'intermediate_outcomes' => is_array($intermediateOutcomes) ? implode(', ', $intermediateOutcomes) : $intermediateOutcomes,
            ];

            if ($activityId) {
                // Update existing activity
                $activity = RQSTActivity::where('id', $activityId)
                                      ->where('objective_id', $objective->id)
                                      ->first();
                if ($activity) {
                    $activity->update($activityData);
                    $currentActivityIds[] = $activityId;
                    Log::info("Activity updated: {$activityId}");
                }
            } else {
                // Create new activity
                $activity = RQSTActivity::create($activityData);
                $currentActivityIds[] = $activity->id;
                Log::info("Activity created: {$activity->id}");
            }
        }

        // Remove activities not included in the request
        RQSTActivity::where('objective_id', $objective->id)
                   ->whereNotIn('id', $currentActivityIds)
                   ->delete();
        Log::info('Removed outdated activities for objective_id: ' . $objective->id);
    }

    private function updateAccountDetails($request, $report_id)
    {
        $particulars = $request->input('particulars', []);
        $currentAccountDetailIds = [];

        foreach ($particulars as $index => $particular) {
            $accountDetailId = $request->input("account_detail_id.{$index}");

            $accountDetailData = [
                'report_id' => $report_id,
                'particulars' => $particular,
                'amount_forwarded' => $request->input("amount_forwarded.$index"),
                'amount_sanctioned' => $request->input("amount_sanctioned.$index"),
                'total_amount' => $request->input("total_amount.$index"),
                'expenses_last_month' => $request->input("expenses_last_month.$index"),
                'expenses_this_month' => $request->input("expenses_this_month.$index"),
                'total_expenses' => $request->input("total_expenses.$index"),
                'balance_amount' => $request->input("balance_amount.$index"),
            ];

            if ($accountDetailId) {
                // Update existing account detail
                $accountDetail = RQSTAccountDetails::where('id', $accountDetailId)
                                                 ->where('report_id', $report_id)
                                                 ->first();
                if ($accountDetail) {
                    $accountDetail->update($accountDetailData);
                    $currentAccountDetailIds[] = $accountDetailId;
                    Log::info("Account detail updated: {$accountDetailId}");
                }
            } else {
                // Create new account detail
                $accountDetail = RQSTAccountDetails::create($accountDetailData);
                $currentAccountDetailIds[] = $accountDetail->id;
                Log::info("Account detail created: {$accountDetail->id}");
            }
        }

        // Remove account details not included in the request
        RQSTAccountDetails::where('report_id', $report_id)
                        ->whereNotIn('id', $currentAccountDetailIds)
                        ->delete();
        Log::info('Removed outdated account details for report_id: ' . $report_id);
    }

    private function updateOutlooks($request, $report_id)
    {
        $outlookDates = $request->input('date', []);
        $planNextMonths = $request->input('plan_next_month', []);
        $currentOutlookIds = [];

        foreach ($outlookDates as $index => $date) {
            $outlookId = $request->input("outlook_id.{$index}");

            $outlookData = [
                'report_id' => $report_id,
                'date' => $date,
                'plan_next_month' => $planNextMonths[$index] ?? null,
            ];

            if ($outlookId) {
                // Update existing outlook
                $outlook = RQSTOutlook::where('id', $outlookId)
                                    ->where('report_id', $report_id)
                                    ->first();
                if ($outlook) {
                    $outlook->update($outlookData);
                    $currentOutlookIds[] = $outlookId;
                    Log::info("Outlook updated: {$outlookId}");
                }
            } else {
                // Create new outlook
                $outlook = RQSTOutlook::create($outlookData);
                $currentOutlookIds[] = $outlook->id;
                Log::info("Outlook created: {$outlook->id}");
            }
        }

        // Remove outlooks not included in the request
        RQSTOutlook::where('report_id', $report_id)
                  ->whereNotIn('id', $currentOutlookIds)
                  ->delete();
        Log::info('Removed outdated outlooks for report_id: ' . $report_id);
    }

    private function updatePhotos($request, $report_id)
    {
        // Handle existing photos that should be kept
        $existingPhotoIds = $request->input('existing_photo_ids', []);
        $photoDescriptions = $request->input('photo_descriptions', []);

        // Update descriptions for existing photos
        foreach ($existingPhotoIds as $index => $photoId) {
            if ($photoId) {
                $photo = RQSTPhoto::where('id', $photoId)
                                 ->where('report_id', $report_id)
                                 ->first();
                if ($photo) {
                    $description = $photoDescriptions[$index] ?? '';
                    $photo->update(['description' => $description]);
                    Log::info("Updated existing photo description: {$photoId}");
                }
            }
        }

        // Handle new photo uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $file) {
                if ($file->isValid()) {
                    $path = $file->store('ReportImages/Quarterly', 'public');

                    $photoData = [
                        'report_id' => $report_id,
                        'path' => $path,
                        'description' => $photoDescriptions[$index] ?? '',
                    ];

                    RQSTPhoto::create($photoData);
                    Log::info("New photo created for report_id: {$report_id}");
                }
            }
        }

        // Handle photo deletions
        $photosToDelete = $request->input('photos_to_delete', []);
        if (!empty($photosToDelete)) {
            foreach ($photosToDelete as $photoId) {
                $photo = RQSTPhoto::where('id', $photoId)
                                 ->where('report_id', $report_id)
                                 ->first();
                if ($photo) {
                    // Delete the file from storage
                    if (file_exists(storage_path('app/public/' . $photo->path))) {
                        unlink(storage_path('app/public/' . $photo->path));
                    }
                    // Delete the database record
                    $photo->delete();
                    Log::info("Deleted photo: {$photoId}");
                }
            }
        }
    }

    private function updateTraineeProfiles($request, $report_id)
    {
        // This method would handle trainee profile updates
        // Implementation depends on the specific trainee profile structure
        // For now, this is a placeholder
        Log::info("Trainee profiles update method called for report_id: {$report_id}");
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
