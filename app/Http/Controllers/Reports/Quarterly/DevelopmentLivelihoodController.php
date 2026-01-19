<?php

namespace App\Http\Controllers\Reports\Quarterly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Quarterly\QRDLAnnexure;
use App\Models\Reports\Quarterly\RQDLReport;
use App\Models\Reports\Quarterly\RQDLObjective;
use App\Models\Reports\Quarterly\RQDLActivity;
use App\Models\Reports\Quarterly\RQDLPhoto;
use App\Models\Reports\Quarterly\RQDLAccountDetail;
use App\Models\Reports\Quarterly\RQDLOutlook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DevelopmentLivelihoodController extends Controller
{
    public function create()
    {
        return view('reports.quarterly.developmentLivelihood.reportform');
    }


    public function store(Request $request)
{
    // Log the request data
    Log::info('Store method called');
    Log::info('Store method called', [
        'project_id' => $request->project_id,
        'quarter' => $request->quarter,
        'year' => $request->year,
    ]);

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
        'amount_sanctioned_overview' => 'nullable|numeric',
        'amount_forwarded_overview' => 'nullable|numeric',
        'amount_in_hand' => 'nullable|numeric',
        'total_balance_forwarded' => 'nullable|numeric',
        'photos' => 'nullable|array',
        'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
        'photo_descriptions' => 'nullable|array',
        'objective' => 'nullable|array', // Objective
        'objective.*' => 'nullable|string', // Objectives
    ]);

    // Temporarily set user_id to null for testing if not authenticated
    $validatedData['user_id'] = auth()->check() ? auth()->id() : null;

    Log::info('Validated Data: ', $validatedData);

    // Create the report
    $report = RQDLReport::create($validatedData);
    Log::info('Report Created: ', $report->toArray());

    // Ensure input arrays are initialized
    $objective = $request->input('objective', []); // Objective/Objectives
    $expected_outcome = $request->input('expected_outcome', []);
    $months = $request->input('month', []);

    Log::info('Expected Outcome:', $expected_outcome);
    Log::info('Months:', $months);

    // Save objectives and activities
    foreach ($expected_outcome as $index => $expectedOutcome) {
        $objectiveData = [
            'report_id' => $report->id,
            'objective' => $objective[$index] ?? null, // Add this line
            'expected_outcome' => $expectedOutcome,
            'not_happened' => $request->input("not_happened.$index"),
            'why_not_happened' => $request->input("why_not_happened.$index"),
            'changes' => $request->input("changes.$index") === 'yes',
            'why_changes' => $request->input("why_changes.$index"),
            'lessons_learnt' => $request->input("lessons_learnt.$index"),
            'todo_lessons_learnt' => $request->input("todo_lessons_learnt.$index"),
        ];

        Log::info('Objective Data:', $objectiveData);

        $objectiveModel = RQDLObjective::create($objectiveData);
        Log::info('Objective Created: ', $objectiveModel->toArray());

        // Ensure months input array is initialized mm
        $activityMonths = $request->input("month.$index", []);

        // Save activities for each objective
        foreach ($activityMonths as $activityIndex => $month) {
            $summaryActivities = $request->input("summary_activities.$index.$activityIndex");
            $qualitativeQuantitativeData = $request->input("qualitative_quantitative_data.$index.$activityIndex");
            $intermediateOutcomes = $request->input("intermediate_outcomes.$index.$activityIndex");

            // Convert activity fields to strings if they are arrays
            $activityData = [
                'objective_id' => $objectiveModel->id,
                'month' => $month,
                'summary_activities' => is_array($summaryActivities) ? implode(', ', $summaryActivities) : $summaryActivities,
                'qualitative_quantitative_data' => is_array($qualitativeQuantitativeData) ? implode(', ', $qualitativeQuantitativeData) : $qualitativeQuantitativeData,
                'intermediate_outcomes' => is_array($intermediateOutcomes) ? implode(', ', $intermediateOutcomes) : $intermediateOutcomes,
            ];

            Log::info('Activity Data:', $activityData);

            $activity = RQDLActivity::create($activityData);
            Log::info('Activity Created: ', $activity->toArray());
        }
    }

    // Handle file uploads
    if ($request->hasFile('photos')) {
        foreach ($request->file('photos') as $index => $file) {
            $path = $file->store('ReportImages/Quarterly', 'public');
            $photoData = [
                'report_id' => $report->id,
                'path' => $path,
                'description' => $request->photo_descriptions[$index] ?? '',
            ];

            Log::info('Photo Data:', $photoData);

            $photo = RQDLPhoto::create($photoData);
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

        $accountDetail = RQDLAccountDetail::create($accountDetailData);
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

        $outlook = RQDLOutlook::create($outlookData);
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

    return redirect()->route('quarterly.developmentLivelihood.create')->with('success', 'Report submitted successfully.');
}


    // Retrieve reports  created by the authenticated user and list them in index page
    public function index()
    {
        // Eager load relationships to prevent N+1 queries
        $reports = RQDLReport::where('user_id', Auth::id())
            ->with(['user', 'project', 'accountDetails'])
            ->get();
        return view('reports.quarterly.developmentLivelihood.list', compact('reports'));
    }

    // show individual report from list of reports when clicked view button
    public function show($id)
    {
        $report = RQDLReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks', 'annexures'])->findOrFail($id);
        return view('reports.quarterly.developmentLivelihood.show', compact('report'));
    }


    public function edit($id)
    {
        // Logic to get the report data for editing
        $report = RQDLReport::with(['objectives.activities', 'photos', 'accountDetails'])->findOrFail($id);
        return view('reports.quarterly.developmentLivelihood.edit', compact('report'));
    }

    public function update(Request $request, $id)
    {
        Log::info('Update method initiated', ['report_id' => $id]);

        DB::beginTransaction();
        try {
            $report = RQDLReport::findOrFail($id);

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
                'amount_in_hand' => 'nullable|numeric',
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
            $this->updateAnnexures($request, $report->id);

            DB::commit();
            Log::info('Transaction committed and report updated successfully.');
            return redirect()->route('quarterly.developmentLivelihood.edit', $report->id)->with('success', 'Report updated successfully.');
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
                $objective = RQDLObjective::where('id', $objectiveId)
                                        ->where('report_id', $report_id)
                                        ->first();
                if ($objective) {
                    $objective->update($objectiveData);
                    $currentObjectiveIds[] = $objectiveId;
                    Log::info("Objective updated: {$objectiveId}");
                }
            } else {
                // Create new objective
                $objective = RQDLObjective::create($objectiveData);
                $currentObjectiveIds[] = $objective->id;
                Log::info("Objective created: {$objective->id}");
            }

            // Handle activities for this objective
            $this->updateActivities($request, $objective, $index);
        }

        // Remove objectives not included in the request
        RQDLObjective::where('report_id', $report_id)
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
                $activity = RQDLActivity::where('id', $activityId)
                                      ->where('objective_id', $objective->id)
                                      ->first();
                if ($activity) {
                    $activity->update($activityData);
                    $currentActivityIds[] = $activityId;
                    Log::info("Activity updated: {$activityId}");
                }
            } else {
                // Create new activity
                $activity = RQDLActivity::create($activityData);
                $currentActivityIds[] = $activity->id;
                Log::info("Activity created: {$activity->id}");
            }
        }

        // Remove activities not included in the request
        RQDLActivity::where('objective_id', $objective->id)
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
                $accountDetail = RQDLAccountDetail::where('id', $accountDetailId)
                                                 ->where('report_id', $report_id)
                                                 ->first();
                if ($accountDetail) {
                    $accountDetail->update($accountDetailData);
                    $currentAccountDetailIds[] = $accountDetailId;
                    Log::info("Account detail updated: {$accountDetailId}");
                }
            } else {
                // Create new account detail
                $accountDetail = RQDLAccountDetail::create($accountDetailData);
                $currentAccountDetailIds[] = $accountDetail->id;
                Log::info("Account detail created: {$accountDetail->id}");
            }
        }

        // Remove account details not included in the request
        RQDLAccountDetail::where('report_id', $report_id)
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
                $outlook = RQDLOutlook::where('id', $outlookId)
                                    ->where('report_id', $report_id)
                                    ->first();
                if ($outlook) {
                    $outlook->update($outlookData);
                    $currentOutlookIds[] = $outlookId;
                    Log::info("Outlook updated: {$outlookId}");
                }
            } else {
                // Create new outlook
                $outlook = RQDLOutlook::create($outlookData);
                $currentOutlookIds[] = $outlook->id;
                Log::info("Outlook created: {$outlook->id}");
            }
        }

        // Remove outlooks not included in the request
        RQDLOutlook::where('report_id', $report_id)
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
                $photo = RQDLPhoto::where('id', $photoId)
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

                    RQDLPhoto::create($photoData);
                    Log::info("New photo created for report_id: {$report_id}");
                }
            }
        }

        // Handle photo deletions
        $photosToDelete = $request->input('photos_to_delete', []);
        if (!empty($photosToDelete)) {
            foreach ($photosToDelete as $photoId) {
                $photo = RQDLPhoto::where('id', $photoId)
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

    private function updateAnnexures($request, $report_id)
    {
        $beneficiaryNames = $request->input('beneficiary_name', []);
        $supportDates = $request->input('support_date', []);
        $selfEmployments = $request->input('self_employment', []);
        $amountSanctioneds = $request->input('amount_sanctioned_annexure', []);
        $monthlyProfits = $request->input('monthly_profit', []);
        $annualProfits = $request->input('annual_profit', []);
        $impacts = $request->input('impact', []);
        $challenges = $request->input('challenges', []);
        $currentAnnexureIds = [];

        foreach ($beneficiaryNames as $index => $beneficiaryName) {
            $annexureId = $request->input("annexure_id.{$index}");

            $annexureData = [
                'report_id' => $report_id,
                'beneficiary_name' => $beneficiaryName,
                'support_date' => $supportDates[$index] ?? null,
                'self_employment' => $selfEmployments[$index] ?? null,
                'amount_sanctioned' => $amountSanctioneds[$index] ?? null,
                'monthly_profit' => $monthlyProfits[$index] ?? null,
                'annual_profit' => $annualProfits[$index] ?? null,
                'impact' => $impacts[$index] ?? null,
                'challenges' => $challenges[$index] ?? null,
            ];

            if ($annexureId) {
                // Update existing annexure
                $annexure = QRDLAnnexure::where('id', $annexureId)
                                      ->where('report_id', $report_id)
                                      ->first();
                if ($annexure) {
                    $annexure->update($annexureData);
                    $currentAnnexureIds[] = $annexureId;
                    Log::info("Annexure updated: {$annexureId}");
                }
            } else {
                // Create new annexure
                $annexure = QRDLAnnexure::create($annexureData);
                $currentAnnexureIds[] = $annexure->id;
                Log::info("Annexure created: {$annexure->id}");
            }
        }

        // Remove annexures not included in the request
        QRDLAnnexure::where('report_id', $report_id)
                   ->whereNotIn('id', $currentAnnexureIds)
                   ->delete();
        Log::info('Removed outdated annexures for report_id: ' . $report_id);
    }

    public function review($id)
    {
        // Logic to get the report data for review by senior
        $report = RQDLReport::with(['objectives.activities', 'photos', 'accountDetails'])->findOrFail($id);
        return view('reports.quarterly.developmentLivelihood.review', compact('report'));
    }

    public function revert(Request $request, $id)
    {
        // Logic to revert with feedback from senior
        // ...
    }
}
