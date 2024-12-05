<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Monthly\ReportAttachmentController;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Reports\Monthly\DPObjective;
use App\Models\Reports\Monthly\DPActivity;
use App\Models\Reports\Monthly\DPAccountDetail;
use App\Models\Reports\Monthly\DPPhoto;
use App\Models\Reports\Monthly\DPOutlook;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Models\OldProjects\ProjectObjective;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    protected $livelihoodAnnexureController;
    protected $institutionalGroupController;
    protected $residentialSkillTrainingController;
    protected $crisisInterventionCenterController;
    protected $reportAttachmentController;


    public function __construct(
        LivelihoodAnnexureController $livelihoodAnnexureController,
        InstitutionalOngoingGroupController $institutionalGroupController,
        ResidentialSkillTrainingController $residentialSkillTrainingController,
        CrisisInterventionCenterController $crisisInterventionCenterController,
        ReportAttachmentController $reportAttachmentController

    ) {
        $this->livelihoodAnnexureController = $livelihoodAnnexureController;
        $this->institutionalGroupController = $institutionalGroupController;
        $this->residentialSkillTrainingController = $residentialSkillTrainingController;
        $this->crisisInterventionCenterController = $crisisInterventionCenterController;
        $this->reportAttachmentController = $reportAttachmentController;

    }

    public function create($project_id)
    {
        // Log::info('Entering create method', ['project_id' => $project_id]);

        // $project = Project::where('project_id', $project_id)->firstOrFail();
        // Log::info('Project retrieved successfully', ['project' => $project]);

        // $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
        // Log::info('Retrieved highest phase for the project', ['highestPhase' => $highestPhase]);

        // $budgets = ProjectBudget::where('project_id', $project->project_id)
        //                         ->where('phase', $highestPhase)
        //                         ->get();
        // Log::info('Budgets retrieved for the highest phase', ['budgets' => $budgets]);

        Log::info('Entering create method', ['project_id' => $project_id]);

        $project = Project::where('project_id', $project_id)->firstOrFail();
        Log::info('Project retrieved successfully', ['project' => $project]);

        $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
        Log::info('Retrieved highest phase for the project', ['highestPhase' => $highestPhase]);

        $budgets = ProjectBudget::where('project_id', $project->project_id)
                                ->where('phase', $highestPhase)
                                ->get();
        Log::info('Budgets retrieved for the highest phase', ['budgets' => $budgets]);

        // Retrieve objectives with their results and activities
        $objectives = ProjectObjective::where('project_id', $project_id)
    ->with(['results', 'activities.timeframes'])
    ->get();

        Log::info('Objectives retrieved for the project', ['objectives' => $objectives]);


        //ReportAttachment
        $attachments = []; // Placeholder, add logic to fetch attachments if required

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

        return view('reports.monthly.ReportAll', compact('project', 'user', 'amountSanctioned', 'amountForwarded', 'budgets', 'lastExpenses', 'attachments', 'objectives'));
    }

    public function store(Request $request)
    {
        Log::info('Store method initiated with data:', ['data' => $request->all()]);

        DB::beginTransaction();
        try {
            // Validate request data
            $validatedData = $this->validateRequest($request);

            // Generate report_id
            $project_id = $validatedData['project_id'];
            $report_id = $this->generateReportId($project_id);

            // Create the main report
            $report = $this->createReport($validatedData, $report_id);

            // Handle additional report data
            $this->storeObjectivesAndActivities($request, $report_id, $report);
            $this->handleAccountDetails($request, $report_id, $project_id);
            $this->handleOutlooks($request, $report_id);
            $this->handlePhotos($request, $report_id);
            $this->handleSpecificProjectData($request, $report_id);

            // Handle attachments using ReportAttachmentController
            $this->reportAttachmentController->store($request, $report);

            DB::commit();
            Log::info('Transaction committed and report created successfully.');
            return redirect()->route('monthly.report.index')->with('success', 'Report submitted successfully.');
        } catch (ValidationException $ve) {
            DB::rollBack();
            Log::error('Validation failed', ['errors' => $ve->errors()]);
            return back()->withErrors($ve->errors())->withInput();
        }         catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create report', ['error' => $e->getMessage()]);
            return back()->withErrors(['msg' => 'Failed to create report due to an error: ' . $e->getMessage()]);
        }
    }

    private function validateRequest(Request $request)
    {
        return $request->validate([
            // Basic project information
            'project_id' => 'required|string|max:255',
            'project_title' => 'nullable|string|max:255',
            'project_type' => 'nullable|string|max:255',
            'place' => 'nullable|string|max:255',
            'society_name' => 'nullable|string|max:255',
            'commencement_month_year' => 'nullable|date',
            'in_charge' => 'nullable|string|max:255',
            'total_beneficiaries' => 'nullable|integer',

            // Reporting period
            'report_month' => 'nullable|integer|between:1,12',
            'report_year' => 'nullable|integer',
            'goal' => 'nullable|string',

            // Accounting period
            'account_period_start' => 'nullable|date',
            'account_period_end' => 'nullable|date',

            // Photos and descriptions
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|array',
            'photos.*.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:8192',


            'photo_descriptions' => 'nullable|array',
            'photo_descriptions.*' => 'nullable|string',

            // Objectives (read-only, but included for completeness)
            'objective' => 'nullable|array',
            'objective.*' => 'nullable|string',

            // Expected outcomes (read-only, but included for completeness)
            'expected_outcome' => 'nullable|array',
            'expected_outcome.*' => 'nullable|array',
            'expected_outcome.*.*' => 'nullable|string',


            // User-input fields per objective
            'not_happened' => 'nullable|array',
            'not_happened.*' => 'nullable|string',

            'why_not_happened' => 'nullable|array',
            'why_not_happened.*' => 'nullable|string',

            'changes' => 'nullable|array',
            'changes.*' => 'nullable|string|in:yes,no',

            'why_changes' => 'nullable|array',
            'why_changes.*' => 'nullable|string',

            'lessons_learnt' => 'nullable|array',
            'lessons_learnt.*' => 'nullable|string',

            'todo_lessons_learnt' => 'nullable|array',
            'todo_lessons_learnt.*' => 'nullable|string',

            // Activities under objectives
            'activity' => 'nullable|array',
            'activity.*' => 'nullable|array',
            'activity.*.*' => 'nullable|string',

            'month' => 'nullable|array',
            'month.*' => 'nullable|array',
            'month.*.*' => 'nullable|integer|between:1,12',

            'summary_activities' => 'nullable|array',
            'summary_activities.*' => 'nullable|array',
            'summary_activities.*.*' => 'nullable|array',
            'summary_activities.*.*.*' => 'nullable|string',

            'qualitative_quantitative_data' => 'nullable|array',
            'qualitative_quantitative_data.*' => 'nullable|array',
            'qualitative_quantitative_data.*.*' => 'nullable|array',
            'qualitative_quantitative_data.*.*.*' => 'nullable|string',

            'intermediate_outcomes' => 'nullable|array',
            'intermediate_outcomes.*' => 'nullable|array',
            'intermediate_outcomes.*.*' => 'nullable|array',
            'intermediate_outcomes.*.*.*' => 'nullable|string',

            // Financial details
            'particulars' => 'nullable|array',
            'particulars.*' => 'nullable|string',

            'amount_forwarded' => 'nullable|array',
            'amount_forwarded.*' => 'nullable|numeric',

            'amount_sanctioned' => 'nullable|array',
            'amount_sanctioned.*' => 'nullable|numeric',

            'total_amount' => 'nullable|array',
            'total_amount.*' => 'nullable|numeric',

            'expenses_last_month' => 'nullable|array',
            'expenses_last_month.*' => 'nullable|numeric',

            'expenses_this_month' => 'nullable|array',
            'expenses_this_month.*' => 'nullable|numeric',

            'total_expenses' => 'nullable|array',
            'total_expenses.*' => 'nullable|numeric',

            'balance_amount' => 'nullable|array',
            'balance_amount.*' => 'nullable|numeric',

            // Outlooks for next month
            'date' => 'nullable|array',
            'date.*' => 'nullable|date',

            'plan_next_month' => 'nullable|array',
            'plan_next_month.*' => 'nullable|string',

            // Overview amounts
            'amount_sanctioned_overview' => 'nullable|numeric',
            'amount_forwarded_overview' => 'nullable|numeric',
            'amount_in_hand' => 'nullable|numeric',
            'total_balance_forwarded' => 'nullable|numeric',

            // objective and activity ID of project
            'project_objective_id' => 'required|array',
            'project_objective_id.*' => 'required|string',

            'project_activity_id' => 'required|array',
            'project_activity_id.*' => 'required|array',
            'project_activity_id.*.*' => 'nullable|string',


        ]);
    }

    private function createReport($validatedData, $report_id)
    {
        $report = DPReport::create([
            'report_id' => $report_id,
            'user_id' => auth()->id() ?? null,
            'project_id' => $validatedData['project_id'],
            'project_title' => $validatedData['project_title'] ?? '',
            'project_type' => $validatedData['project_type'] ?? '',
            'place' => $validatedData['place'] ?? '',
            'society_name' => $validatedData['society_name'] ?? '',
            'commencement_month_year' => $validatedData['commencement_month_year'] ?? null,
            'in_charge' => $validatedData['in_charge'] ?? '',
            'total_beneficiaries' => $validatedData['total_beneficiaries'] ?? 0,
            'report_month_year' => isset($validatedData['report_year']) && isset($validatedData['report_month']) ? Carbon::createFromDate($validatedData['report_year'], $validatedData['report_month'], 1) : null,
            'goal' => $validatedData['goal'] ?? '',
            'account_period_start' => $validatedData['account_period_start'] ?? null,
            'account_period_end' => $validatedData['account_period_end'] ?? null,
            'amount_sanctioned_overview' => $validatedData['amount_sanctioned_overview'] ?? 0.0,
            'amount_forwarded_overview' => $validatedData['amount_forwarded_overview'] ?? 0.0,
            'amount_in_hand' => $validatedData['amount_in_hand'] ?? 0.0,
            'total_balance_forwarded' => $validatedData['total_balance_forwarded'] ?? 0.0
        ]);

        if (!$report) {
            throw new Exception('Failed to create report');
        }
        Log::info('Report created successfully', ['report_id' => $report->report_id]);

        return $report;
    }

    // private function storeObjectivesAndActivities($request, $report_id, $report)
    // {
    //     $objectivesInput = $request->input('objective', []);
    //     $expectedOutcomesInput = $request->input('expected_outcome', []);
    //     $projectObjectiveIds = $request->input('project_objective_id', []);

    //     foreach ($objectivesInput as $index => $objectiveText) {
    //         $objective_id_suffix = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
    //         $objective_id = "{$report_id}-{$objective_id_suffix}";

    //         // Retrieve project_objective_id
    //         $projectObjectiveId = $projectObjectiveIds[$index] ?? null;

    //         // Serialize expected_outcome for this objective
    //         $expectedOutcomeArray = $expectedOutcomesInput[$index] ?? [];
    //         $expectedOutcomeJson = json_encode($expectedOutcomeArray);

    //         $objectiveData = [
    //             'objective_id' => $objective_id,
    //             'report_id' => $report->report_id,
    //             'project_objective_id' => $projectObjectiveId,
    //             'objective' => $objectiveText,
    //             'expected_outcome' => $expectedOutcomeJson,
    //             'not_happened' => $request->input("not_happened.$index"),
    //             'why_not_happened' => $request->input("why_not_happened.$index"),
    //             'changes' => $request->input("changes.$index") === 'yes',
    //             'why_changes' => $request->input("why_changes.$index"),
    //             'lessons_learnt' => $request->input("lessons_learnt.$index"),
    //             'todo_lessons_learnt' => $request->input("todo_lessons_learnt.$index"),
    //         ];

    //         Log::info('Objective Data:', $objectiveData);

    //         $objective = DPObjective::create($objectiveData);
    //         Log::info('Objective Created:', $objective->toArray());

    //         // Handle activities
    //         $this->storeActivities($request, $objective, $index, $objective_id);
    //     }
    // }

    private function storeObjectivesAndActivities($request, $report_id, $report)
{
    $objectivesInput = $request->input('objective', []);
    $expectedOutcomesInput = $request->input('expected_outcome', []);
    $projectObjectiveIds = $request->input('project_objective_id', []);

    foreach ($objectivesInput as $index => $objectiveText) {
        $objective_id_suffix = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        $objective_id = "{$report_id}-{$objective_id_suffix}";

        // Retrieve project_objective_id
        $projectObjectiveId = $projectObjectiveIds[$index] ?? null;

        // Serialize expected_outcome for this objective
        $expectedOutcomeArray = $expectedOutcomesInput[$index] ?? [];
        $expectedOutcomeJson = json_encode($expectedOutcomeArray);

        $objectiveData = [
            'report_id' => $report->report_id,
            'project_objective_id' => $projectObjectiveId,
            'objective' => $objectiveText,
            'expected_outcome' => $expectedOutcomeJson,
            'not_happened' => $request->input("not_happened.$index"),
            'why_not_happened' => $request->input("why_not_happened.$index"),
            'changes' => $request->input("changes.$index") === 'yes',
            'why_changes' => $request->input("why_changes.$index"),
            'lessons_learnt' => $request->input("lessons_learnt.$index"),
            'todo_lessons_learnt' => $request->input("todo_lessons_learnt.$index"),
        ];

        Log::info('Processing objective data:', $objectiveData);

        // Check if the objective already exists
        $objective = DPObjective::where('objective_id', $objective_id)->first();

        if ($objective) {
            // Update existing objective
            $objective->update($objectiveData);
            Log::info('Objective updated:', $objective->toArray());
        } else {
            // Create a new objective
            $objectiveData['objective_id'] = $objective_id;
            $objective = DPObjective::create($objectiveData);
            Log::info('Objective created:', $objective->toArray());
        }

        // Handle activities for this objective
        $this->storeActivities($request, $objective, $index, $objective_id);
    }

    // Remove any objectives not included in the request
    DPObjective::where('report_id', $report_id)
        ->whereNotIn('objective_id', array_map(function ($index) use ($report_id) {
            return "{$report_id}-" . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        }, array_keys($objectivesInput)))
        ->delete();
}

//     private function storeActivities($request, $objective, $objectiveIndex, $objective_id)
// {
//     $activitiesInput = $request->input("activity.$objectiveIndex", []);
//     $projectActivityIds = $request->input("project_activity_id.$objectiveIndex", []);

//     foreach ($activitiesInput as $activityIndex => $activityText) {
//         $activity_id_suffix = str_pad($activityIndex + 1, 3, '0', STR_PAD_LEFT);
//         $activity_id = "{$objective_id}-{$activity_id_suffix}";

//         // Retrieve project_activity_id
//         $projectActivityId = $projectActivityIds[$activityIndex] ?? null;

//         $summaryActivities = $request->input("summary_activities.$objectiveIndex.$activityIndex.1");
//         $qualitativeQuantitativeData = $request->input("qualitative_quantitative_data.$objectiveIndex.$activityIndex.1");
//         $intermediateOutcomes = $request->input("intermediate_outcomes.$objectiveIndex.$activityIndex.1");

//         $activityData = [
//             'activity_id' => $activity_id,
//             'objective_id' => $objective->objective_id,
//             'project_activity_id' => $projectActivityId,
//             'activity' => $activityText,
//             'month' => $request->input("month.$objectiveIndex.$activityIndex"),
//             'summary_activities' => $summaryActivities,
//             'qualitative_quantitative_data' => $qualitativeQuantitativeData,
//             'intermediate_outcomes' => $intermediateOutcomes,
//         ];

//         Log::info('Activity Data:', $activityData);

//         $activity = DPActivity::create($activityData);
//         Log::info('Activity Created:', $activity->toArray());
//     }
// }

private function storeActivities($request, $objective, $objectiveIndex, $objective_id)
{
    $activitiesInput = $request->input("activity.$objectiveIndex", []);
    $projectActivityIds = $request->input("project_activity_id.$objectiveIndex", []);

    foreach ($activitiesInput as $activityIndex => $activityText) {
        $activity_id_suffix = str_pad($activityIndex + 1, 3, '0', STR_PAD_LEFT);
        $activity_id = "{$objective_id}-{$activity_id_suffix}";

        // Retrieve project_activity_id
        $projectActivityId = $projectActivityIds[$activityIndex] ?? null;

        $activityData = [
            'objective_id' => $objective->objective_id,
            'project_activity_id' => $projectActivityId,
            'activity' => $activityText,
            'month' => $request->input("month.$objectiveIndex.$activityIndex"),
            'summary_activities' => $request->input("summary_activities.$objectiveIndex.$activityIndex.1"),
            'qualitative_quantitative_data' => $request->input("qualitative_quantitative_data.$objectiveIndex.$activityIndex.1"),
            'intermediate_outcomes' => $request->input("intermediate_outcomes.$objectiveIndex.$activityIndex.1"),
        ];

        Log::info('Processing activity data:', $activityData);

        // Check if the activity already exists
        $activity = DPActivity::where('activity_id', $activity_id)->first();

        if ($activity) {
            // Update existing activity
            $activity->update($activityData);
            Log::info('Activity updated:', $activity->toArray());
        } else {
            // Create a new activity
            $activityData['activity_id'] = $activity_id;
            $activity = DPActivity::create($activityData);
            Log::info('Activity created:', $activity->toArray());
        }
    }

    // Remove any activities not included in the request
    DPActivity::where('objective_id', $objective->objective_id)
        ->whereNotIn('activity_id', array_map(function ($activityIndex) use ($objective_id) {
            return "{$objective_id}-" . str_pad($activityIndex + 1, 3, '0', STR_PAD_LEFT);
        }, array_keys($activitiesInput)))
        ->delete();
}

    private function handleAccountDetails($request, $report_id, $project_id)
    {
        $particulars = $request->input('particulars', []);
        foreach ($particulars as $index => $particular) {
            DPAccountDetail::create([
                'report_id' => $report_id,
                'project_id' => $project_id,
                'particulars' => $particular,
                'amount_forwarded' => $request->input("amount_forwarded.{$index}"),
                'amount_sanctioned' => $request->input("amount_sanctioned.{$index}"),
                'total_amount' => $request->input("total_amount.{$index}"),
                'expenses_last_month' => $request->input("expenses_last_month.{$index}"),
                'expenses_this_month' => $request->input("expenses_this_month.{$index}"),
                'total_expenses' => $request->input("total_expenses.{$index}"),
                'balance_amount' => $request->input("balance_amount.{$index}")
            ]);
        }
    }

    // private function handleOutlooks($request, $report_id)
    // {
    //     $outlookDates = $request->input('date', []);
    //     foreach ($outlookDates as $index => $date) {
    //         $outlook_id_suffix = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
    //         $outlook_id = "{$report_id}-{$outlook_id_suffix}";

    //         DPOutlook::create([
    //             'outlook_id' => $outlook_id,
    //             'report_id' => $report_id,
    //             'date' => $date,
    //             'plan_next_month' => $request->input("plan_next_month.{$index}")
    //         ]);
    //     }
    // }

    private function handleOutlooks($request, $report_id)
{
    $outlookDates = $request->input('date', []);
    $planNextMonthInputs = $request->input('plan_next_month', []);

    // Track outlook IDs in the current request for deletion later
    $currentOutlookIds = [];

    foreach ($outlookDates as $index => $date) {
        $outlook_id_suffix = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        $outlook_id = "{$report_id}-{$outlook_id_suffix}";

        // Collect current outlook IDs
        $currentOutlookIds[] = $outlook_id;

        $outlookData = [
            'report_id' => $report_id,
            'date' => $date,
            'plan_next_month' => $planNextMonthInputs[$index] ?? null,
        ];

        // Check if the outlook already exists
        $outlook = DPOutlook::where('outlook_id', $outlook_id)->first();

        if ($outlook) {
            // Update existing outlook
            $outlook->update($outlookData);
            Log::info("Outlook updated: {$outlook_id}", $outlookData);
        } else {
            // Create new outlook
            $outlookData['outlook_id'] = $outlook_id;
            DPOutlook::create($outlookData);
            Log::info("Outlook created: {$outlook_id}", $outlookData);
        }
    }

    // Remove any outlooks not included in the current request
    DPOutlook::where('report_id', $report_id)
        ->whereNotIn('outlook_id', $currentOutlookIds)
        ->delete();
    Log::info('Removed outdated outlooks for report_id: ' . $report_id);
}

    private function handlePhotos($request, $report_id)
    {
        Log::info('Starting handlePhotos method', ['report_id' => $report_id]);

        // Directly access the files from the request
        $photos = $request->file('photos');

        if ($photos && count($photos) > 0) {
            Log::info('Photos found in request');

            foreach ($photos as $groupIndex => $files) {
                $description = $request->input('photo_descriptions')[$groupIndex] ?? '';
                Log::info("Processing photo group {$groupIndex}", ['description' => $description]);

                foreach ($files as $fileIndex => $file) {
                    if (!$file->isValid()) {
                        Log::error('Invalid file detected', [
                            'error' => $file->getErrorMessage(),
                            'file_name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                            'group_index' => $groupIndex,
                            'file_index' => $fileIndex,
                        ]);
                        continue;
                    }

                    // Log the file being processed
                    Log::info('Processing file', [
                        'file_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'group_index' => $groupIndex,
                        'file_index' => $fileIndex,
                    ]);

                    // Generate photo_id with 4-digit suffix
                    $latestPhoto = DPPhoto::where('photo_id', 'LIKE', "{$report_id}-%")
                        ->latest('photo_id')
                        ->lockForUpdate()
                        ->first();

                    $max_suffix = $latestPhoto ? intval(substr($latestPhoto->photo_id, -4)) + 1 : 1;
                    $photo_id = "{$report_id}-" . str_pad($max_suffix, 4, '0', STR_PAD_LEFT);

                    // Store the file in the correct directory
                    $path = $file->store('ReportImages/Monthly', 'public');

                    // Log the successful storage of the photo
                    Log::info('Photo stored successfully', [
                        'path' => $path,
                        'photo_id' => $photo_id,
                        'group_index' => $groupIndex,
                        'file_index' => $fileIndex,
                    ]);

                    // Save file details to the database
                    DPPhoto::create([
                        'photo_id' => $photo_id,
                        'report_id' => $report_id,
                        'photo_path' => $path,
                        'description' => $description,
                    ]);

                    Log::info('Photo record created in database', ['photo_id' => $photo_id]);
                }
            }
        } else {
            Log::warning('No photos found in request', [
                'request_files' => $request->files->all(),
                'request_input' => $request->all(),
            ]);
        }

        Log::info('Exiting handlePhotos method', ['report_id' => $report_id]);
    }

    private function handleSpecificProjectData($request, $report_id)
    {
        $projectType = $request->input('project_type');

        switch ($projectType) {
            case 'Livelihood Development Projects':
                $this->livelihoodAnnexureController->handleLivelihoodAnnexure($request, $report_id);
                break;
            case 'Institutional Ongoing Group Educational proposal':
                $this->institutionalGroupController->handleInstitutionalGroup($request, $report_id);
                break;
            case 'Residential Skill Training Proposal 2':
                $this->residentialSkillTrainingController->handleTraineeProfiles($request, $report_id);
                break;
            case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                $this->crisisInterventionCenterController->handleInmateProfiles($request, $report_id);
                break;
        }
    }

    protected function generateReportId($project_id)
    {
        $latestReport = DPReport::where('report_id', 'LIKE', "{$project_id}-%")
                                ->latest('report_id')
                                ->lockForUpdate()
                                ->first();

        if ($latestReport) {
            $max_suffix = intval(explode('-', $latestReport->report_id)[2]) + 1;
        } else {
            $max_suffix = 1; // Start from 01 if no reports found
        }

        return "{$project_id}-" . str_pad($max_suffix, 2, '0', STR_PAD_LEFT);
    }

    public function index()
    {
        Log::info('Entering index method');

        $reports = DPReport::with('project', 'user')->get();
        Log::info('Reports retrieved', ['reports' => $reports]);

        return view('reports.monthly.index', compact('reports'));
    }

    public function show($report_id)
    {
        Log::info('Entering show method', ['report_id' => $report_id]);

        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks', 'attachments'])
                          ->where('report_id', $report_id)
                          ->firstOrFail();
        Log::info('Report retrieved', ['report' => $report]);

        $annexures = [];
        $ageProfiles = [];
        $traineeProfiles = [];
        $inmateProfiles = [];
         //ReportAttachment
         $attachments = []; // Placeholder, add logic to fetch attachments if required


        $projectType = $report->project_type;

        switch ($projectType) {
            case 'Livelihood Development Projects':
                $annexures = $this->livelihoodAnnexureController->getAnnexures($report_id);
                break;
            case 'Institutional Ongoing Group Educational proposal':
                $ageProfiles = $this->institutionalGroupController->getAgeProfiles($report_id);
                break;
            case 'Residential Skill Training Proposal 2':
                 $traineeProfiles = $this->residentialSkillTrainingController->getTraineeProfiles($report_id);
                // Populate the $report->education array for the view
                    $education = [];
                    foreach ($traineeProfiles as $profile) {
                        $category = $profile->education_category;
                        $number = $profile->number;

                        switch ($category) {
                            case 'Below 9th standard':
                                $education['below_9'] = $number;
                                break;
                            case '10th class failed':
                                $education['class_10_fail'] = $number;
                                break;
                            case '10th class passed':
                                $education['class_10_pass'] = $number;
                                break;
                            case 'Intermediate':
                                $education['intermediate'] = $number;
                                break;
                            case 'Intermediate and above':
                                $education['above_intermediate'] = $number;
                                break;
                            case 'Total':
                                $education['total'] = $number;
                                break;
                            default:
                                // For 'Other' category
                                $education['other'] = $category; // The category name is the 'other' text
                                $education['other_count'] = $number;
                                break;
                        }
                    }
                    $report->education = $education;
                break;
            case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                $inmateProfiles = $this->crisisInterventionCenterController->getInmateProfiles($report_id);
                break;
        }

        return view('reports.monthly.show', compact('report', 'annexures', 'ageProfiles', 'traineeProfiles', 'inmateProfiles'));
    }

    public function edit($report_id)
    {
        Log::info('Entering edit method', ['report_id' => $report_id]);

        // Fetch the report with necessary relationships
        $report = DPReport::with([
            'objectives.activities.timeframes', // Add timeframes here
            'accountDetails',
            'photos',
            'outlooks',
            'attachments'
        ])
        ->where('report_id', $report_id)
        ->firstOrFail();
        Log::info('Report retrieved for editing', ['report_id' => $report_id]);

        // Decode expected_outcome for each objective
        foreach ($report->objectives as $objective) {
            $objective->expected_outcome = json_decode($objective->expected_outcome, true) ?? [];
        }
        // Group photos by description (or other criterion)
        $groupedPhotos = $report->photos->groupBy('description');
        Log::info('Grouped photos by description', ['groupedPhotos' => $groupedPhotos]);

        // Fetch the associated project
        $project = Project::where('project_id', $report->project_id)->firstOrFail();
        Log::info('Project retrieved successfully', ['project_id' => $project->project_id]);

        // Fetch the highest phase budgets for the project
        $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
        $budgets = ProjectBudget::where('project_id', $project->project_id)
                                ->where('phase', $highestPhase)
                                ->get();
        Log::info('Budgets retrieved for the highest phase', ['highestPhase' => $highestPhase, 'budgets' => $budgets]);

        // Fetch project objectives with their results and activities
        $objectives = ProjectObjective::where('project_id', $project->project_id)
                                    ->with(['results', 'activities.timeframes'])
                                    ->get();
        Log::info('Objectives retrieved with activities and results', ['objectives' => $objectives->toArray()]);

        // Sanctioned and forwarded amounts
        $amountSanctioned = $project->amount_sanctioned ?? 0.00;
        $amountForwarded = $project->amount_forwarded ?? 0.00;
        Log::info('Sanctioned and forwarded amounts', [
            'amountSanctioned' => $amountSanctioned,
            'amountForwarded' => $amountForwarded
        ]);

        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        // Fetch last expenses if any
        $lastExpenses = collect();
        $lastReport = DPReport::where('project_id', $project->project_id)
                            ->where('report_id', '<', $report_id)
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

        // Fetch additional data based on the project type
        $annexures = [];
        $ageProfiles = [];
        $traineeProfiles = [];
        $inmateProfiles = [];

        switch ($report->project_type) {
            case 'Livelihood Development Projects':
                $annexures = $this->livelihoodAnnexureController->getAnnexures($report_id);
                Log::info('Annexures retrieved for Livelihood Development Projects', ['annexures' => $annexures]);
                break;
            case 'Institutional Ongoing Group Educational proposal':
                $ageProfiles = $this->institutionalGroupController->getAgeProfiles($report_id);
                Log::info('Age profiles retrieved for Institutional Group Projects', ['ageProfiles' => $ageProfiles]);

                /// Process the age profiles into the structure expected by the view
                $ageProfile = [];
                $totals = [];

                // Define mapping from age group names to keys used in the blade view
                $ageGroupKeys = [
                    'Children below 5 years' => 'below_5',
                    'Children between 6 to 10 years' => '6_10',
                    'Children between 11 to 15 years' => '11_15',
                    '16 and above' => '16_above',
                ];

                // Initialize totals
                foreach ($ageProfiles as $profile) {
                    $ageGroup = $profile->age_group;
                    $education = $profile->education;
                    $upToPreviousYear = $profile->up_to_previous_year;
                    $presentAcademicYear = $profile->present_academic_year;

                    if ($ageGroup === 'All Categories' && $education === 'Grand Total') {
                        // Store grand totals
                        $totals['grand']['up_to_previous'] = $upToPreviousYear;
                        $totals['grand']['present_academic'] = $presentAcademicYear;
                    } elseif (isset($ageGroupKeys[$ageGroup])) {
                        $ageGroupKey = $ageGroupKeys[$ageGroup];

                        if ($education === 'Total') {
                            // Store totals for this age group
                            $totals[$ageGroupKey]['up_to_previous'] = $upToPreviousYear;
                            $totals[$ageGroupKey]['present_academic'] = $presentAcademicYear;
                        } else {
                            // Store the data in ageProfile
                            if (!isset($ageProfile[$ageGroupKey])) {
                                $ageProfile[$ageGroupKey] = [];
                            }

                            $ageProfile[$ageGroupKey][] = [
                                'education' => $education,
                                'up_to_previous_year' => $upToPreviousYear,
                                'present_academic_year' => $presentAcademicYear,
                            ];
                        }
                    }
                }

                break;
            case 'Residential Skill Training Proposal 2':
                 $traineeProfiles = $this->residentialSkillTrainingController->getTraineeProfiles($report_id);
                Log::info('Trainee profiles retrieved for Residential Skill Training Projects', ['traineeProfiles' => $traineeProfiles]);
                // After fetching $traineeProfiles
                // Arrange them in array
                if ($report->project_type === 'Residential Skill Training Proposal 2') {
                    $education = [];
                    foreach ($traineeProfiles as $profile) {
                        $category = $profile->education_category;
                        $number = $profile->number;

                        switch ($category) {
                            case 'Below 9th standard':
                                $education['below_9'] = $number;
                                break;
                            case '10th class failed':
                                $education['class_10_fail'] = $number;
                                break;
                            case '10th class passed':
                                $education['class_10_pass'] = $number;
                                break;
                            case 'Intermediate':
                                $education['intermediate'] = $number;
                                break;
                            case 'Intermediate and above':
                                $education['above_intermediate'] = $number;
                                break;
                            case 'Total':
                                $education['total'] = $number;
                                break;
                            default:
                                // For 'Other' category
                                $education['other'] = $category; // The category name is the 'other' text
                                $education['other_count'] = $number;
                                break;
                        }
                    }
                    $report->education = $education;
                }

                break;
            case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                $inmateProfiles = $this->crisisInterventionCenterController->getInmateProfiles($report_id);
                Log::info('Inmate profiles retrieved for Crisis Intervention Projects', ['inmateProfiles' => $inmateProfiles]);
                break;
            default:
                Log::info('No specific profiles retrieved for this project type', ['projectType' => $report->project_type]);
                break;
        }

        // Return the view with all necessary data
        return view('reports.monthly.edit', compact(
            'report',
            'groupedPhotos', // Pass grouped photos
            'project',
            'budgets',
            'objectives',
            'amountSanctioned',
            'amountForwarded',
            'lastExpenses',
            'annexures',
            'ageProfiles',
            'traineeProfiles',
            'inmateProfiles',
            'ageProfile',   // Include ageProfile
            'totals',       // Include totals
            'months'
        ));
    }

    public function update(Request $request, $report_id)
    {
        Log::info('Update method initiated', ['report_id' => $report_id]);

        Log::info('Update method initiated with data:', ['data' => $request->all()]);

        DB::beginTransaction();
        try {
            // Validate request data
            $validatedData = $this->validateRequest($request);

            // Find the report
            $report = DPReport::where('report_id', $report_id)->firstOrFail();

            // Update the main report
            $this->updateReport($validatedData, $report);

            // Clear existing related data
            // $report->objectives()->delete();
            // $report->accountDetails()->delete();
            // $report->photos()->delete();
            // $report->outlooks()->delete();

            // Handle updated data
            $this->storeObjectivesAndActivities($request, $report_id, $report);
            $this->handleAccountDetails($request, $report_id, $validatedData['project_id']);
            $this->handleOutlooks($request, $report_id);
            $this->handlePhotos($request, $report_id);
            $this->handleSpecificProjectData($request, $report_id);
            // Handle attachments using ReportAttachmentController
            $this->reportAttachmentController->update($request, $report_id);


            DB::commit();
            Log::info('Transaction committed and report updated successfully.');
            return redirect()->route('monthly.report.index')->with('success', 'Report updated successfully.');
        }  catch (ValidationException $ve) {
            DB::rollBack();
            Log::error('Validation failed', ['errors' => $ve->errors()]);
            return back()->withErrors($ve->errors())->withInput();
        }

         catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update report', ['error' => $e->getMessage()]);
            return back()->withErrors(['msg' => 'Failed to update report due to an error: ' . $e->getMessage()]);
        }
    }

    private function updateReport($validatedData, $report)
    {
        $report->update([
            'project_id' => $validatedData['project_id'],
            'project_title' => $validatedData['project_title'] ?? '',
            'project_type' => $validatedData['project_type'] ?? '',
            'place' => $validatedData['place'] ?? '',
            'society_name' => $validatedData['society_name'] ?? '',
            'commencement_month_year' => $validatedData['commencement_month_year'] ?? null,
            'in_charge' => $validatedData['in_charge'] ?? '',
            'total_beneficiaries' => $validatedData['total_beneficiaries'] ?? 0,
            'report_month_year' => isset($validatedData['report_year']) && isset($validatedData['report_month']) ? Carbon::createFromDate($validatedData['report_year'], $validatedData['report_month'], 1) : null,
            'goal' => $validatedData['goal'] ?? '',
            'account_period_start' => $validatedData['account_period_start'] ?? null,
            'account_period_end' => $validatedData['account_period_end'] ?? null,
            'amount_sanctioned_overview' => $validatedData['amount_sanctioned_overview'] ?? 0,
            'amount_forwarded_overview' => $validatedData['amount_forwarded_overview'] ?? 0,
            'amount_in_hand' => $validatedData['amount_in_hand'] ?? 0,
            'total_balance_forwarded' => $validatedData['total_balance_forwarded'] ?? 0,
            'status' => 'updated',
        ]);
    }

    public function review($report_id)
    {
        Log::info('Entering review method', ['report_id' => $report_id]);

        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                          ->where('report_id', $report_id)
                          ->firstOrFail();
        Log::info('Report retrieved for review', ['report' => $report]);

        return view('reports.monthly.review', compact('report'));
    }

    public function revert(Request $request, $report_id)
    {
        Log::info('Entering revert method', ['report_id' => $report_id, 'request' => $request->all()]);

        $report = DPReport::where('report_id', $report_id)->firstOrFail();
        $report->update([
            'status' => 'reverted',
            'revert_reason' => $request->input('revert_reason'),
        ]);
        Log::info('Report reverted', ['report' => $report]);

        return redirect()->route('monthly.report.index')->with('success', 'Report reverted successfully.');
    }

    // public function downloadAttachment($id)
    public function downloadAttachment($id)
    {
        return $this->reportAttachmentController->downloadAttachment($id);
    }

    public function updateAttachment(Request $request, $report_id)
    {
        return $this->reportAttachmentController->update($request, $report_id);
    }
}
