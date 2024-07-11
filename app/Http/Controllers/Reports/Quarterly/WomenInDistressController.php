<?php

namespace App\Http\Controllers\Reports\Quarterly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Quarterly\RQWDReport;
use App\Models\Reports\Quarterly\RQWDObjective;
use App\Models\Reports\Quarterly\RQWDActivity;
use App\Models\Reports\Quarterly\RQWDPhoto;
use App\Models\Reports\Quarterly\RQWDAccountDetail;
use App\Models\Reports\Quarterly\RQWDOutlook;
use App\Models\Reports\Quarterly\RQWDInmatesProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WomenInDistressController extends Controller
{
    public function create()
    {
        return view('reports.quarterly.womenInDistress.reportform');
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
            // Inmates Profiles
            'number_children_below_18_unmarried' => 'nullable|integer',
            'number_children_below_18_married' => 'nullable|integer',
            'number_children_below_18_divorcee' => 'nullable|integer',
            'number_children_below_18_deserted' => 'nullable|integer',
            'status_children_below_18_others' => 'nullable|string',
            'number_children_below_18_others' => 'nullable|integer',
            'number_women_18_30_unmarried' => 'nullable|integer',
            'number_women_18_30_married' => 'nullable|integer',
            'number_women_18_30_divorcee' => 'nullable|integer',
            'number_women_18_30_deserted' => 'nullable|integer',
            'status_women_18_30_others' => 'nullable|string',
            'number_women_18_30_others' => 'nullable|integer',
            'number_women_31_50_unmarried' => 'nullable|integer',
            'number_women_31_50_married' => 'nullable|integer',
            'number_women_31_50_divorcee' => 'nullable|integer',
            'number_women_31_50_deserted' => 'nullable|integer',
            'status_women_31_50_others' => 'nullable|string',
            'number_women_31_50_others' => 'nullable|integer',
            'number_women_above_50_unmarried' => 'nullable|integer',
            'number_women_above_50_married' => 'nullable|integer',
            'number_women_above_50_divorcee' => 'nullable|integer',
            'number_women_above_50_deserted' => 'nullable|integer',
            'status_women_above_50_others' => 'nullable|string',
            'number_women_above_50_others' => 'nullable|integer',
        ]);

        // Temporarily set user_id to null for testing if not authenticated
        $validatedData['user_id'] = auth()->check() ? auth()->id() : null;

        // Calculate amount_in_hand
        $validatedData['amount_in_hand'] = ($validatedData['prjct_amount_sanctioned'] ?? 0) + ($validatedData['l_y_amount_forwarded'] ?? 0);

        Log::info('Validated Data: ', $validatedData);

        DB::transaction(function () use ($validatedData, $request) {
            // Create the report
            $report = RQWDReport::create($validatedData);
            Log::info('Report Created: ', $report->toArray());

            // Save inmates profiles
            $ageCategories = [
                'children_below_18' => ['unmarried', 'married', 'divorcee', 'deserted', $request->input('status_children_below_18_others')],
                'women_18_30' => ['unmarried', 'married', 'divorcee', 'deserted', $request->input('status_women_18_30_others')],
                'women_31_50' => ['unmarried', 'married', 'divorcee', 'deserted', $request->input('status_women_31_50_others')],
                'women_above_50' => ['unmarried', 'married', 'divorcee', 'deserted', $request->input('status_women_above_50_others')],
            ];

            foreach ($ageCategories as $ageCategory => $statuses) {
                foreach ($statuses as $status) {
                    if ($status) {
                        $numberField = "number_{$ageCategory}_{$status}";
                        if ($request->input($numberField) !== null) {
                            $inmatesProfileData = [
                                'report_id' => $report->id,
                                'age_category' => $ageCategory,
                                'status' => $status,
                                'number' => $request->input($numberField),
                            ];

                            Log::info('Inmates Profile Data:', $inmatesProfileData);

                            $inmatesProfile = RQWDInmatesProfile::create($inmatesProfileData);
                            Log::info('Inmates Profile Created: ', $inmatesProfile->toArray());
                        }
                    }
                }
            }

            // Save objectives and activities
            foreach ($request->input('expected_outcome', []) as $index => $expectedOutcome) {
                $objectiveData = [
                    'report_id' => $report->id,
                    'expected_outcome' => $expectedOutcome,
                    'not_happened' => $request->input("not_happened.$index"),
                    'why_not_happened' => $request->input("why_not_happened.$index"),
                    'changes' => $request->input("changes.$index") === 'yes',
                    'why_changes' => $request->input("why_changes.$index"),
                    'lessons_learnt' => $request->input("lessons_learnt.$index"),
                    'todo_lessons_learnt' => $request->input("todo_lessons_learnt.$index"),
                ];

                Log::info('Objective Data:', $objectiveData);

                $objective = RQWDObjective::create($objectiveData);
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

                    $activity = RQWDActivity::create($activityData);
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

                    $photo = RQWDPhoto::create($photoData);
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

                $accountDetail = RQWDAccountDetail::create($accountDetailData);
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

                $outlook = RQWDOutlook::create($outlookData);
                Log::info('Outlook Created: ', $outlook->toArray());
            }
        });

        return redirect()->route('quarterly.womenInDistress.create')->with('success', 'Report submitted successfully.');
    }

    //LIST REPORTS
    public function index()
    {
        $reports = RQWDReport::where('user_id', Auth::id())->get();
        return view('reports.quarterly.womenInDistress.list', compact('reports'));
    }



    public function edit($id)
    {
        $report = RQWDReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks', 'inmatesProfiles'])->findOrFail($id);
        return view('reports.quarterly.womenInDistress.edit', compact('report'));
    }

    public function update(Request $request, $id)
    {
        // Logic to update the report
        $report = RQWDReport::findOrFail($id);

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

        return redirect()->route('quarterly.womenInDistress.edit', $report->id)->with('success', 'Report updated successfully.');
    }

    public function review($id)
    {
        $report = RQWDReport::with(['objectives.activities', 'photos', 'accountDetails', 'outlooks', 'inmatesProfiles'])->findOrFail($id);
        return view('reports.quarterly.womenInDistress.review', compact('report'));
    }

    public function revert(Request $request, $id)
    {
        // Logic to revert with feedback from senior
        // ...
    }
}
