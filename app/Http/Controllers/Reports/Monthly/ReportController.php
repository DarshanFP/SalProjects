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
use Illuminate\Support\Facades\Storage;

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
        Log::info('Entering create method', ['project_id' => $project_id]);

        $project = Project::where('project_id', $project_id)->firstOrFail();
        Log::info('Project retrieved successfully', ['project' => $project]);

        // Get budget data based on project type
        $budgets = $this->getBudgetDataByProjectType($project);
        Log::info('Budgets retrieved for project type', ['project_type' => $project->project_type, 'budgets_count' => $budgets->count()]);

        // Retrieve objectives with their results and activities
        $objectives = ProjectObjective::where('project_id', $project_id)
            ->with(['results', 'activities.timeframes'])
            ->get();

        Log::info('Objectives retrieved for the project', ['objectives' => $objectives]);

        // ReportAttachment
        $attachments = []; // Placeholder, add logic to fetch attachments if required

        $amountSanctioned = $project->amount_sanctioned ?? 0.00;
        $amountForwarded = $project->amount_forwarded ?? 0.00;
        Log::info('Sanctioned and forwarded amounts', [
            'amountSanctioned' => $amountSanctioned,
            'amountForwarded' => $amountForwarded
        ]);

        $lastExpenses = $this->getLastExpenses($project);

        $user = Auth::user();

        return view('reports.monthly.ReportAll', compact('project', 'user', 'amountSanctioned', 'amountForwarded', 'budgets', 'lastExpenses', 'attachments', 'objectives'));
    }

    /**
     * Get budget data based on project type
     */
    private function getBudgetDataByProjectType($project)
    {
        switch ($project->project_type) {
            case 'Development Projects':
            case 'Livelihood Development Projects':
            case 'Residential Skill Training Proposal 2':
            case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
            case 'CHILD CARE INSTITUTION':
            case 'Rural-Urban-Tribal':
                return $this->getDevelopmentProjectBudgets($project);

            case 'Individual - Livelihood Application':
                return $this->getILPBudgets($project);

            case 'Individual - Access to Health':
                return $this->getIAHBudgets($project);

            case 'Institutional Ongoing Group Educational proposal':
                return $this->getIGEBudgets($project);

            case 'Individual - Ongoing Educational support':
            case 'Individual - Initial - Educational support':
                return $this->getIIESBudgets($project);

            default:
                Log::warning('Unknown project type, using development project budgets as fallback', ['project_type' => $project->project_type]);
                return $this->getDevelopmentProjectBudgets($project);
        }
    }

    /**
     * Get Development Project budgets
     */
    private function getDevelopmentProjectBudgets($project)
    {
        $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
        Log::info('Retrieved highest phase for development project', ['highestPhase' => $highestPhase]);

        return ProjectBudget::where('project_id', $project->project_id)
            ->where('phase', $highestPhase)
            ->get();
    }

    /**
     * Get ILP (Individual Livelihood) budgets
     */
    private function getILPBudgets($project)
    {
        return \App\Models\OldProjects\ILP\ProjectILPBudget::where('project_id', $project->project_id)->get();
    }

    /**
     * Get IAH (Individual Access to Health) budgets
     */
    private function getIAHBudgets($project)
    {
        return \App\Models\OldProjects\IAH\ProjectIAHBudgetDetails::where('project_id', $project->project_id)->get();
    }

    /**
     * Get IGE (Institutional Group Education) budgets
     */
    private function getIGEBudgets($project)
    {
        return \App\Models\OldProjects\IGE\ProjectIGEBudget::where('project_id', $project->project_id)->get();
    }

    /**
     * Get IIES (Individual Initial Educational Support) budgets
     */
    private function getIIESBudgets($project)
    {
        $iiesExpenses = \App\Models\OldProjects\IIES\ProjectIIESExpenses::where('project_id', $project->project_id)->first();
        if ($iiesExpenses) {
            return $iiesExpenses->expenseDetails;
        }
        return collect();
    }

    /**
     * Get last expenses for the project
     */
    private function getLastExpenses($project)
    {
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
            return $lastExpenses;
        } else {
            Log::info('No last report found, lastExpenses remains empty');
            return collect();
        }
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

            // Handle multiple attachments
            $this->handleAttachments($request, $report);

            DB::commit();
            Log::info('Transaction committed and report created successfully.');
            return redirect()->route('monthly.report.index')->with('success', 'Report submitted successfully.');
        } catch (ValidationException $ve) {
            DB::rollBack();
            Log::error('Validation failed', ['errors' => $ve->errors()]);
            return back()->withErrors($ve->errors())->withInput();
        } catch (\Exception $e) {
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
            'photos.*.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            'photo_descriptions' => 'nullable|array',
            'photo_descriptions.*' => 'nullable|string',

            // Multiple Attachments
            'attachment_files' => 'nullable|array',
            'attachment_files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:2048',
            'attachment_names' => 'nullable|array',
            'attachment_names.*' => 'nullable|string|max:255',
            'attachment_descriptions' => 'nullable|array',
            'attachment_descriptions.*' => 'nullable|string|max:1000',

            // New Attachments for Updates
            'new_attachment_files' => 'nullable|array',
            'new_attachment_files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:2048',
            'new_attachment_names' => 'nullable|array',
            'new_attachment_names.*' => 'nullable|string|max:255',
            'new_attachment_descriptions' => 'nullable|array',
            'new_attachment_descriptions.*' => 'nullable|string|max:1000',

            // Legacy single attachment (for backward compatibility)
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:2048',
            'file_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',

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

            'is_budget_row' => 'nullable|array',
            'is_budget_row.*' => 'nullable|boolean',

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
            'total_balance_forwarded' => $validatedData['total_balance_forwarded'] ?? 0.0,
            'status' => 'draft'
        ]);

        if (!$report) {
            throw new Exception('Failed to create report');
        }
        Log::info('Report created successfully', ['report_id' => $report->report_id]);

        return $report;
    }

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
        $currentAccountDetailIds = [];
        $accountDetailIds = $request->input('account_detail_id', []); // Now indexed
        $isBudgetRows = $request->input('is_budget_row', []); // New field for tracking budget rows

        Log::info('Starting updateAccountDetails', [
            'report_id' => $report_id,
            'project_id' => $project_id,
            'total_particulars' => count($particulars),
            'account_detail_ids' => $accountDetailIds,
            'is_budget_rows' => $isBudgetRows
        ]);

        foreach ($particulars as $index => $particular) {
            // Get the account detail ID for this row (may be empty for new rows)
            $accountDetailId = isset($accountDetailIds[$index]) ? $accountDetailIds[$index] : null;
            $isBudgetRow = isset($isBudgetRows[$index]) ? (bool)$isBudgetRows[$index] : false;

            Log::info("Processing account detail at index {$index}", [
                'particular' => $particular,
                'account_detail_id' => $accountDetailId,
                'is_existing' => !empty($accountDetailId),
                'is_budget_row' => $isBudgetRow
            ]);

            $accountDetailData = [
                'report_id' => $report_id,
                'project_id' => $project_id,
                'particulars' => $particular,
                'amount_forwarded' => $request->input("amount_forwarded.{$index}"),
                'amount_sanctioned' => $request->input("amount_sanctioned.{$index}"),
                'total_amount' => $request->input("total_amount.{$index}"),
                'expenses_last_month' => $request->input("expenses_last_month.{$index}"),
                'expenses_this_month' => $request->input("expenses_this_month.{$index}"),
                'total_expenses' => $request->input("total_expenses.{$index}"),
                'balance_amount' => $request->input("balance_amount.{$index}"),
                'is_budget_row' => $isBudgetRow // Add the new field
            ];

            if ($accountDetailId) {
                // Update existing account detail
                $accountDetail = DPAccountDetail::where('account_detail_id', $accountDetailId)
                                               ->where('report_id', $report_id)
                                               ->first();
                if ($accountDetail) {
                    $accountDetail->update($accountDetailData);
                    $currentAccountDetailIds[] = $accountDetailId;
                    Log::info("Account detail updated: {$accountDetailId}", $accountDetailData);
                } else {
                    Log::warning("Account detail not found for ID: {$accountDetailId}");
                }
            } else {
                // Create new account detail
                $accountDetail = DPAccountDetail::create($accountDetailData);
                $currentAccountDetailIds[] = $accountDetail->account_detail_id;
                Log::info("Account detail created: {$accountDetail->account_detail_id}", $accountDetailData);
            }
        }

        // Remove any account details not included in the current request
        $deletedCount = DPAccountDetail::where('report_id', $report_id)
                      ->whereNotIn('account_detail_id', $currentAccountDetailIds)
                      ->delete();
        Log::info('Removed outdated account details for report_id: ' . $report_id, [
            'deleted_count' => $deletedCount,
            'current_ids' => $currentAccountDetailIds
        ]);
    }

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

        // Get the report to access project_id and reporting_period_from
        $report = DPReport::where('report_id', $report_id)->first();
        if (!$report) {
            Log::error('Report not found', ['report_id' => $report_id]);
            return;
        }

        // Get project to access project_id
        $project = Project::where('project_id', $report->project_id)->first();
        if (!$project) {
            Log::error('Project not found', ['project_id' => $report->project_id]);
            return;
        }

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

                    // Check file size (2MB limit)
                    if ($file->getSize() > 2097152) { // 2MB in bytes
                        Log::error('Photo file too large', [
                            'file_name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
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

                    // Create folder structure: REPORTS/{project_id}/{report_id}/photos/{month_year}/
                    $monthYear = date('m_Y', strtotime($report->reporting_period_from));
                    $folderPath = "REPORTS/{$project->project_id}/{$report_id}/photos/{$monthYear}";

                    // Store the file in the new directory structure
                    $path = $file->storeAs($folderPath, $file->getClientOriginalName(), 'public');

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

    private function updatePhotos($request, $report_id)
    {
        Log::info('Starting updatePhotos method', ['report_id' => $report_id]);

        // Get the report to access project_id and reporting_period_from
        $report = DPReport::where('report_id', $report_id)->first();
        if (!$report) {
            Log::error('Report not found', ['report_id' => $report_id]);
            return;
        }

        // Get project to access project_id
        $project = Project::where('project_id', $report->project_id)->first();
        if (!$project) {
            Log::error('Project not found', ['project_id' => $report->project_id]);
            return;
        }

        // Handle existing photos that should be kept
        $existingPhotoIds = $request->input('existing_photo_ids', []);
        $photoDescriptions = $request->input('photo_descriptions', []);

        // Update descriptions for existing photos
        foreach ($existingPhotoIds as $index => $photoId) {
            if ($photoId) {
                $photo = DPPhoto::where('photo_id', $photoId)
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
        $photos = $request->file('photos');
        if ($photos && count($photos) > 0) {
            Log::info('New photos found in request');

            foreach ($photos as $groupIndex => $files) {
                $description = $photoDescriptions[$groupIndex] ?? '';
                Log::info("Processing new photo group {$groupIndex}", ['description' => $description]);

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

                    // Check file size (2MB limit)
                    if ($file->getSize() > 2097152) { // 2MB in bytes
                        Log::error('Photo file too large', [
                            'file_name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'group_index' => $groupIndex,
                            'file_index' => $fileIndex,
                        ]);
                        continue;
                    }

                    // Generate photo_id with 4-digit suffix
                    $latestPhoto = DPPhoto::where('photo_id', 'LIKE', "{$report_id}-%")
                        ->latest('photo_id')
                        ->lockForUpdate()
                        ->first();

                    $max_suffix = $latestPhoto ? intval(substr($latestPhoto->photo_id, -4)) + 1 : 1;
                    $photo_id = "{$report_id}-" . str_pad($max_suffix, 4, '0', STR_PAD_LEFT);

                    // Create folder structure: REPORTS/{project_id}/{report_id}/photos/{month_year}/
                    $monthYear = date('m_Y', strtotime($report->reporting_period_from));
                    $folderPath = "REPORTS/{$project->project_id}/{$report_id}/photos/{$monthYear}";

                    // Store the file in the new directory structure
                    $path = $file->storeAs($folderPath, $file->getClientOriginalName(), 'public');

                    // Save file details to the database
                    DPPhoto::create([
                        'photo_id' => $photo_id,
                        'report_id' => $report_id,
                        'photo_path' => $path,
                        'description' => $description,
                    ]);

                    Log::info('New photo record created in database', ['photo_id' => $photo_id]);
                }
            }
        }

        // Handle photo deletions
        $photosToDelete = $request->input('photos_to_delete', []);
        if (!empty($photosToDelete)) {
            foreach ($photosToDelete as $photoId) {
                $photo = DPPhoto::where('photo_id', $photoId)
                               ->where('report_id', $report_id)
                               ->first();
                if ($photo) {
                    // Delete the file from storage first (faster operation)
                    $fileDeleted = false;
                    if (Storage::disk('public')->exists($photo->photo_path)) {
                        $fileDeleted = Storage::disk('public')->delete($photo->photo_path);
                        Log::info('Photo file deletion attempt', [
                            'photo_id' => $photo_id,
                            'photo_path' => $photo->photo_path,
                            'deleted' => $fileDeleted
                        ]);
                    } else {
                        Log::warning('Photo file not found in storage', [
                            'photo_id' => $photo_id,
                            'photo_path' => $photo->photo_path
                        ]);
                    }
                    // Delete the database record
                    $photo->delete();
                    Log::info("Deleted photo: {$photoId}");
                }
            }
        }

        Log::info('Exiting updatePhotos method', ['report_id' => $report_id]);
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

        $user = Auth::user();

        // Build the base query
        $reportsQuery = DPReport::with('project', 'user');

        // Apply role-based filters
        if ($user->role === 'executor') {
            // Executors can see their own reports regardless of status
            $reportsQuery->where('user_id', $user->id);
        } elseif ($user->role === 'provincial') {
            // Provincials can see reports from executors under them
            $reportsQuery->whereHas('user', function ($query) use ($user) {
                $query->where('parent_id', $user->id);
            });
        } elseif ($user->role === 'coordinator') {
            // Coordinators can see all reports (no additional filtering needed)
            // No additional filters - coordinators can see everything
        } else {
            // If role is not specified, deny access
            abort(403, 'Access denied');
        }

        $reports = $reportsQuery->orderBy('created_at', 'desc')->get();

        Log::info('Reports retrieved', ['count' => $reports->count(), 'user_role' => $user->role]);

        return view('reports.monthly.index', compact('reports'));
    }

    public function show($report_id)
    {
        Log::info('Entering show method', ['report_id' => $report_id]);

        $user = Auth::user();
        $report = DPReport::with([
            'objectives.activities.timeframes',
            'accountDetails',
            'photos',
            'outlooks',
            'attachments'
        ])->where('report_id', $report_id);

        // Apply role-based filters
        if ($user->role === 'provincial') {
            // Filter reports to those whose user (executor) has parent_id = this provincial user's id
            $report->whereHas('user', function ($query) use ($user) {
                $query->where('parent_id', $user->id);
            });
        } elseif ($user->role === 'executor') {
            // Filter reports to those created by this executor
            $report->where('user_id', $user->id);
        } elseif ($user->role === 'coordinator') {
            // Coordinator can see all reports (no filtering needed)
            // No additional filters - coordinators see everything
        }
        $report = $report->firstOrFail();
        Log::info('Report retrieved successfully', ['report' => $report]);

        // Decode expected_outcome for objectives
        foreach ($report->objectives as $objective) {
            $objective->expected_outcome = json_decode($objective->expected_outcome, true) ?? [];
        }

        // Group photos by description
        $groupedPhotos = $report->photos->groupBy('description');

        // Retrieve associated project
        $project = Project::where('project_id', $report->project_id)->firstOrFail();
        Log::info('Project retrieved successfully', ['project_id' => $project->project_id]);

        // Get budget data based on project type
        $budgets = $this->getBudgetDataByProjectType($project);
        Log::info('Budgets retrieved for project type', ['project_type' => $project->project_type, 'budgets_count' => $budgets->count()]);

        // Prepare additional data based on project type
        $annexures = [];
        $ageProfiles = [];
        $traineeProfiles = [];
        $inmateProfiles = [];
        switch ($report->project_type) {
            case 'Livelihood Development Projects':
                $annexures = $this->livelihoodAnnexureController->getAnnexures($report_id);
                break;
            case 'Institutional Ongoing Group Educational proposal':
                $ageProfiles = $this->institutionalGroupController->getAgeProfiles($report_id);
                break;
            case 'Residential Skill Training Proposal 2':
                $traineeProfiles = $this->residentialSkillTrainingController->getTraineeProfiles($report_id);
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
                break;
        }

        // Pass data to the view
        return view('reports.monthly.show', compact(
            'report',
            'groupedPhotos',
            'project',
            'budgets',
            'annexures',
            'ageProfiles',
            'traineeProfiles',
            'inmateProfiles'
        ));
    }

    public function edit($report_id)
    {
        Log::info('Entering edit method', ['report_id' => $report_id]);

        $user = Auth::user();

        // Fetch the report with necessary relationships and apply role-based filtering
        $reportQuery = DPReport::with([
            'objectives.activities.timeframes', // Add timeframes here
            'accountDetails',
            'photos',
            'outlooks',
            'attachments'
        ])->where('report_id', $report_id);

        // Apply role-based filters
        if ($user->role === 'provincial') {
            // Provincial can edit reports from executors under them
            $reportQuery->whereHas('user', function ($query) use ($user) {
                $query->where('parent_id', $user->id);
            });
        } elseif ($user->role === 'executor') {
            // Executor can only edit their own reports
            $reportQuery->where('user_id', $user->id);
        } elseif ($user->role === 'coordinator') {
            // Coordinator can edit all reports (no filtering needed)
            // No additional filters - coordinators can edit everything
        } else {
            // If role is not specified, deny access
            abort(403, 'Access denied');
        }

        $report = $reportQuery->firstOrFail();
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

        // Get budget data based on project type
        $budgets = $this->getBudgetDataByProjectType($project);
        Log::info('Budgets retrieved for project type', ['project_type' => $project->project_type, 'budgets_count' => $budgets->count()]);

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

        // Get last expenses using the same method as create
        $lastExpenses = $this->getLastExpenses($project);

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
                // Group and structure age profiles

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
            'months'
        ));
    }

    public function update(Request $request, $report_id)
    {
        Log::info('Update method initiated with data:', ['data' => $request->all(), 'report_id' => $report_id]);

        DB::beginTransaction();
        try {
            // Validate request data
            Log::info('Starting validation...');
            $validatedData = $this->validateRequest($request);
            Log::info('Validation passed', ['validatedData' => $validatedData]);

            $user = Auth::user();
            Log::info('User authenticated', ['user_id' => $user->id, 'role' => $user->role]);

            // Fetch the report with role-based filtering
            $reportQuery = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                                  ->where('report_id', $report_id);

            // Apply role-based filters
            if ($user->role === 'provincial') {
                // Provincial can update reports from executors under them
                $reportQuery->whereHas('user', function ($query) use ($user) {
                    $query->where('parent_id', $user->id);
                });
                Log::info('Applied provincial filter');
            } elseif ($user->role === 'executor') {
                // Executor can only update their own reports
                $reportQuery->where('user_id', $user->id);
                Log::info('Applied executor filter');
            } elseif ($user->role === 'coordinator') {
                // Coordinator can update all reports (no filtering needed)
                Log::info('Applied coordinator filter (no restrictions)');
            } else {
                // If role is not specified, deny access
                Log::error('Access denied - invalid role', ['role' => $user->role]);
                abort(403, 'Access denied');
            }

            $report = $reportQuery->firstOrFail();
            Log::info('Report found', ['report_id' => $report->report_id, 'user_id' => $report->user_id]);

            // Update the main report
            Log::info('Updating main report...');
            $this->updateReport($validatedData, $report);
            Log::info('Main report updated successfully');

            // Handle updated data with proper update methods
            Log::info('Processing objectives and activities...');
            $this->storeObjectivesAndActivities($request, $report_id, $report);

            Log::info('Processing account details...');
            $this->handleAccountDetails($request, $report_id, $validatedData['project_id']);

            Log::info('Processing outlooks...');
            $this->handleOutlooks($request, $report_id);

            Log::info('Processing photos...');
            $this->updatePhotos($request, $report_id);

            Log::info('Processing specific project data...');
            $this->handleSpecificProjectData($request, $report_id);

            // Handle new attachments
            Log::info('Processing attachments...');
            $this->handleUpdateAttachments($request, $report);

            DB::commit();
            Log::info('Transaction committed and report updated successfully.');
            return redirect()->route('monthly.report.index')->with('success', 'Report updated successfully.');
        } catch (ValidationException $ve) {
            DB::rollBack();
            Log::error('Validation failed', ['errors' => $ve->errors()]);
            return back()->withErrors($ve->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update report', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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
        ]);
    }

    /**
     * Handle new attachments during report update
     */
    private function handleUpdateAttachments(Request $request, $report)
    {
        Log::info('ReportController@handleUpdateAttachments - Processing new attachments', [
            'hasNewAttachmentFiles' => $request->hasFile('new_attachment_files'),
            'newAttachmentFilesCount' => $request->hasFile('new_attachment_files') ? count($request->file('new_attachment_files')) : 0
        ]);

        // Handle new attachments
        if ($request->hasFile('new_attachment_files')) {
            $attachmentFiles = $request->file('new_attachment_files');
            $attachmentNames = $request->input('new_attachment_names', []);
            $attachmentDescriptions = $request->input('new_attachment_descriptions', []);

            foreach ($attachmentFiles as $index => $file) {
                if ($file && $file->isValid()) {
                    try {
                        // Create a new request for each attachment
                        $attachmentRequest = new Request();
                        $attachmentRequest->files->set('file', $file);
                        $attachmentRequest->merge([
                            'file_name' => $attachmentNames[$index] ?? 'New_Attachment_' . ($index + 1),
                            'description' => $attachmentDescriptions[$index] ?? ''
                        ]);

                        Log::info('ReportController@handleUpdateAttachments - Processing new attachment', [
                            'index' => $index,
                            'fileName' => $attachmentNames[$index] ?? 'New_Attachment_' . ($index + 1),
                            'fileSize' => $file->getSize()
                        ]);

                        $this->reportAttachmentController->store($attachmentRequest, $report);
                    } catch (\Exception $e) {
                        Log::error('ReportController@handleUpdateAttachments - Error processing new attachment', [
                            'index' => $index,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with other attachments even if one fails
                    }
                }
            }
        }

        // Handle legacy single attachment for backward compatibility
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            Log::info('ReportController@handleUpdateAttachments - Processing legacy single attachment');
            try {
                $this->reportAttachmentController->update($request, $report->report_id);
            } catch (\Exception $e) {
                Log::error('ReportController@handleUpdateAttachments - Error processing legacy attachment', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function review($report_id)
    {
        Log::info('Entering review method', ['report_id' => $report_id]);

        $user = Auth::user();

        // Fetch the report with role-based filtering
        $reportQuery = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                              ->where('report_id', $report_id);

        // Apply role-based filters
        if ($user->role === 'provincial') {
            // Provincial can review reports from executors under them
            $reportQuery->whereHas('user', function ($query) use ($user) {
                $query->where('parent_id', $user->id);
            });
        } elseif ($user->role === 'executor') {
            // Executor can only review their own reports
            $reportQuery->where('user_id', $user->id);
        } elseif ($user->role === 'coordinator') {
            // Coordinator can review all reports (no filtering needed)
            // No additional filters - coordinators can review everything
        } else {
            // If role is not specified, deny access
            abort(403, 'Access denied');
        }

        $report = $reportQuery->firstOrFail();
        Log::info('Report retrieved for review', ['report' => $report]);

        return view('reports.monthly.review', compact('report'));
    }

    public function revert(Request $request, $report_id)
    {
        Log::info('Entering revert method', ['report_id' => $report_id, 'request' => $request->all()]);

        $user = Auth::user();

        // Find the report with role-based filtering
        $reportQuery = DPReport::where('report_id', $report_id);

        // Apply role-based filters
        if ($user->role === 'provincial') {
            // Provincial can revert reports from executors under them
            $reportQuery->whereHas('user', function ($query) use ($user) {
                $query->where('parent_id', $user->id);
            });
        } elseif ($user->role === 'executor') {
            // Executor can only revert their own reports
            $reportQuery->where('user_id', $user->id);
        } elseif ($user->role === 'coordinator') {
            // Coordinator can revert all reports (no filtering needed)
            // No additional filters - coordinators can revert everything
        } else {
            // If role is not specified, deny access
            abort(403, 'Access denied');
        }

        $report = $reportQuery->firstOrFail();

        // Determine the new status based on current status and user role
        $newStatus = 'draft'; // Default fallback

        if ($user->role === 'coordinator') {
            if ($report->status === 'forwarded_to_coordinator') {
                $newStatus = 'reverted_by_coordinator';
            }
        } elseif ($user->role === 'provincial') {
            if ($report->status === 'submitted_to_provincial') {
                $newStatus = 'reverted_by_provincial';
            } elseif ($report->status === 'reverted_by_coordinator') {
                $newStatus = 'reverted_by_provincial';
            }
        }

        $report->update([
            'status' => $newStatus,
            'revert_reason' => $request->input('revert_reason'),
        ]);
        Log::info('Report reverted', ['report' => $report]);

        return redirect()->route('monthly.report.index')->with('success', 'Report reverted successfully.');
    }

    public function submit(Request $request, $report_id)
    {
        Log::info('Entering submit method', ['report_id' => $report_id]);

        $user = Auth::user();

        // Only executors can submit reports
        if ($user->role !== 'executor') {
            abort(403, 'Only executors can submit reports.');
        }

        // Find the report
        $report = DPReport::where('report_id', $report_id)
                         ->where('user_id', $user->id)
                         ->firstOrFail();

        // Check if report can be submitted
        if (!in_array($report->status, ['draft', 'reverted_by_provincial', 'reverted_by_coordinator'])) {
            return redirect()->route('monthly.report.index')->with('error', 'Report cannot be submitted in its current status.');
        }

        $report->update([
            'status' => 'submitted_to_provincial',
        ]);

        Log::info('Report submitted', ['report' => $report]);

        return redirect()->route('monthly.report.index')->with('success', 'Report submitted to Provincial successfully.');
    }

    public function forward(Request $request, $report_id)
    {
        Log::info('Entering forward method', ['report_id' => $report_id]);

        $user = Auth::user();

        // Only provincials can forward reports
        if ($user->role !== 'provincial') {
            abort(403, 'Only provincials can forward reports.');
        }

        // Find the report
        $report = DPReport::where('report_id', $report_id)
                         ->whereHas('user', function ($query) use ($user) {
                             $query->where('parent_id', $user->id);
                         })
                         ->firstOrFail();

        // Check if report can be forwarded
        if ($report->status !== 'submitted_to_provincial') {
            return redirect()->route('monthly.report.index')->with('error', 'Report cannot be forwarded in its current status.');
        }

        $report->update([
            'status' => 'forwarded_to_coordinator',
        ]);

        Log::info('Report forwarded', ['report' => $report]);

        return redirect()->route('monthly.report.index')->with('success', 'Report forwarded to Coordinator successfully.');
    }

    public function approve(Request $request, $report_id)
    {
        Log::info('Entering approve method', ['report_id' => $report_id]);

        $user = Auth::user();

        // Only coordinators can approve reports
        if ($user->role !== 'coordinator') {
            abort(403, 'Only coordinators can approve reports.');
        }

        // Find the report
        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        // Check if report can be approved
        if ($report->status !== 'forwarded_to_coordinator') {
            return redirect()->route('monthly.report.index')->with('error', 'Report cannot be approved in its current status.');
        }

        $report->update([
            'status' => 'approved_by_coordinator',
        ]);

        Log::info('Report approved', ['report' => $report]);

        return redirect()->route('monthly.report.index')->with('success', 'Report approved successfully.');
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

    /**
     * Handle multiple attachments from form submission
     */
    private function handleAttachments(Request $request, $report)
    {
        Log::info('ReportController@handleAttachments - Processing attachments', [
            'hasAttachmentFiles' => $request->hasFile('attachment_files'),
            'attachmentFilesCount' => $request->hasFile('attachment_files') ? count($request->file('attachment_files')) : 0
        ]);

        // Handle multiple attachments
        if ($request->hasFile('attachment_files')) {
            $attachmentFiles = $request->file('attachment_files');
            $attachmentNames = $request->input('attachment_names', []);
            $attachmentDescriptions = $request->input('attachment_descriptions', []);

            foreach ($attachmentFiles as $index => $file) {
                if ($file && $file->isValid()) {
                    try {
                        // Create a new request for each attachment
                        $attachmentRequest = new Request();
                        $attachmentRequest->files->set('file', $file);
                        $attachmentRequest->merge([
                            'file_name' => $attachmentNames[$index] ?? 'Attachment_' . ($index + 1),
                            'description' => $attachmentDescriptions[$index] ?? ''
                        ]);

                        Log::info('ReportController@handleAttachments - Processing attachment', [
                            'index' => $index,
                            'fileName' => $attachmentNames[$index] ?? 'Attachment_' . ($index + 1),
                            'fileSize' => $file->getSize()
                        ]);

                        $this->reportAttachmentController->store($attachmentRequest, $report);
                    } catch (\Exception $e) {
                        Log::error('ReportController@handleAttachments - Error processing attachment', [
                            'index' => $index,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with other attachments even if one fails
                    }
                }
            }
        }

        // Handle legacy single attachment for backward compatibility
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            Log::info('ReportController@handleAttachments - Processing legacy single attachment');
            try {
                $this->reportAttachmentController->store($request, $report);
            } catch (\Exception $e) {
                Log::error('ReportController@handleAttachments - Error processing legacy attachment', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function removePhoto($photo_id)
    {
        try {
            Log::info('Starting photo removal', ['photo_id' => $photo_id]);

            $user = Auth::user();

            // Find the photo with role-based filtering
            $photoQuery = DPPhoto::where('photo_id', $photo_id)
                                ->whereHas('report', function ($query) use ($user) {
                                    if ($user->role === 'provincial') {
                                        $query->whereHas('user', function ($q) use ($user) {
                                            $q->where('parent_id', $user->id);
                                        });
                                    } elseif ($user->role === 'executor') {
                                        $query->where('user_id', $user->id);
                                    }
                                    // Coordinator can remove any photo (no additional filter)
                                });

            $photo = $photoQuery->firstOrFail();

            // Delete the file from storage first (faster operation)
            $fileDeleted = false;
            if (Storage::disk('public')->exists($photo->photo_path)) {
                $fileDeleted = Storage::disk('public')->delete($photo->photo_path);
                Log::info('Photo file deletion attempt', [
                    'photo_id' => $photo_id,
                    'photo_path' => $photo->photo_path,
                    'deleted' => $fileDeleted
                ]);
            } else {
                Log::warning('Photo file not found in storage', [
                    'photo_id' => $photo_id,
                    'photo_path' => $photo->photo_path
                ]);
            }

            // Delete the photo record from database
            $photo->delete();
            Log::info('Photo record deleted from database', ['photo_id' => $photo_id]);

            return response()->json([
                'success' => true,
                'message' => 'Photo removed successfully',
                'file_deleted' => $fileDeleted
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Photo not found for removal', ['photo_id' => $photo_id]);
            return response()->json([
                'success' => false,
                'message' => 'Photo not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to remove photo', [
                'photo_id' => $photo_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove photo: ' . $e->getMessage()
            ], 500);
        }
    }
}
