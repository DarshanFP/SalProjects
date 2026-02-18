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
use App\Helpers\LogHelper;
use App\Http\Requests\Reports\Monthly\StoreMonthlyReportRequest;
use App\Http\Requests\Reports\Monthly\UpdateMonthlyReportRequest;
use App\Services\ActivityHistoryService;
use App\Services\NotificationService;
use App\Services\ProjectQueryService;
use App\Services\ReportMonitoringService;
use App\Services\ReportPhotoOptimizationService;
use App\Services\ReportStatusService;
use App\Services\Budget\BudgetAuditLogger;
use App\Models\User;
use App\Traits\HandlesReportPhotoActivity;

class ReportController extends Controller
{
    use HandlesReportPhotoActivity;
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

        // Eager load relationships to prevent N+1 queries
        $project = Project::where('project_id', $project_id)
            ->with(['user', 'budgets', 'objectives.results', 'objectives.risks', 'objectives.activities.timeframes'])
            ->firstOrFail();
        Log::info('Project retrieved successfully', ['project' => $project]);

        // Get budget data based on project type
        $budgets = $this->getBudgetDataByProjectType($project);
        Log::info('Budgets retrieved for project type', ['project_type' => $project->project_type, 'budgets' => $budgets->toArray()]);

        // Retrieve objectives with their results and activities
        $objectives = ProjectObjective::where('project_id', $project_id)
    ->with(['results', 'activities.timeframes'])
    ->get();

        Log::info('Objectives retrieved for the project', ['objectives' => $objectives]);

        // ReportAttachment
        $attachments = []; // Placeholder, add logic to fetch attachments if required

        $amountSanctioned = $project->amount_sanctioned ?? 0.00;
        $amountForwarded = 0.00; // Always set to 0 - no longer used in reports
        Log::info('Sanctioned amount', [
            'amountSanctioned' => $amountSanctioned,
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
        return \App\Services\Budget\BudgetCalculationService::getBudgetsForReport($project, true);
    }

    // Budget calculation methods removed - now using BudgetCalculationService
    // See: app/Services/Budget/BudgetCalculationService.php

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

    public function store(StoreMonthlyReportRequest $request)
    {
        $isDraftSave = $request->has('save_as_draft') && $request->input('save_as_draft') == '1';

        Log::info('Store method initiated', [
            'project_id' => $request->project_id,
            'report_month' => $request->report_month,
            'report_year' => $request->report_year,
            'save_as_draft' => $isDraftSave,
        ]);

        DB::beginTransaction();
        try {
            // Validation already done by StoreMonthlyReportRequest
            $validatedData = $request->validated();

            // Generate report_id
            $project_id = $validatedData['project_id'];
            $report_id = $this->generateReportId($project_id);

            // Create the main report
            $report = $this->createReport($validatedData, $report_id);

            // Handle additional report data (only if not empty/null for draft saves)
            if (!$isDraftSave || !empty($validatedData['objective'])) {
                $this->storeObjectivesAndActivities($request, $report_id, $report);
            }
            if (!$isDraftSave || !empty($validatedData['particulars'])) {
                $this->handleAccountDetails($request, $report_id, $project_id);
            }
            $this->handleOutlooks($request, $report_id);
            $this->handlePhotos($request, $report_id);
            $this->handleSpecificProjectData($request, $report_id);

            // Handle multiple attachments
            $this->handleAttachments($request, $report);

            // Set status based on draft save
            if ($isDraftSave) {
                $report->status = DPReport::STATUS_DRAFT;
                $report->save();
                Log::info('Report saved as draft', ['report_id' => $report_id]);
            }

            DB::commit();
            Log::info('Transaction committed and report created successfully.');

            // Only send notifications if not a draft save
            if (!$isDraftSave) {
                // Query the report again to get the integer id (since model uses report_id as primary key)
                $reportWithId = DPReport::where('report_id', $report_id)->first();
                $reportId = $reportWithId ? $reportWithId->getAttribute('id') : null;

                if (!$reportId) {
                    Log::warning('Could not retrieve report id for notification', ['report_id' => $report_id]);
                }

                // Notify coordinators about report submission
                $project = Project::where('project_id', $project_id)->with('user')->first();
                if ($project && $reportId) {
                    $coordinators = User::where('role', 'coordinator')->get();
                    foreach ($coordinators as $coordinator) {
                        NotificationService::notifyReportSubmission(
                            $coordinator,
                            $reportId,
                            $project->id
                        );
                    }

                    // Notify provincial if project has one
                    if ($project->user && $project->user->parent_id) {
                        $provincial = User::find($project->user->parent_id);
                        if ($provincial) {
                            NotificationService::notifyReportSubmission(
                                $provincial,
                                $reportId,
                                $project->id
                            );
                        }
                    }
                }
            }

            // Log activity (for new reports, previous_status should be null)
            $user = Auth::user();
            if ($isDraftSave) {
                ActivityHistoryService::logReportCreate($report, $user, 'Report saved as draft');
            } else {
                ActivityHistoryService::logReportCreate($report, $user, 'Report created');
            }

            // Redirect based on draft save
            if ($isDraftSave) {
                return redirect()->route('monthly.report.edit', $report_id)
                    ->with('success', 'Report saved as draft. You can continue editing later.');
            }

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
        // Wave 6A Phase 5: Society snapshot set from project only (not from request); set after create so not mass-assigned.
        $project = Project::where('project_id', $validatedData['project_id'])->first();

        $report = DPReport::create([
            'report_id' => $report_id,
            'user_id' => auth()->id() ?? null,
            'project_id' => $validatedData['project_id'],
            'project_title' => $validatedData['project_title'] ?? '',
            'project_type' => $validatedData['project_type'] ?? '',
            'place' => $validatedData['place'] ?? '',
            'commencement_month_year' => $validatedData['commencement_month_year'] ?? null,
            'in_charge' => $validatedData['in_charge'] ?? '',
            'total_beneficiaries' => $validatedData['total_beneficiaries'] ?? 0,
            'report_month_year' => isset($validatedData['report_year']) && isset($validatedData['report_month']) ? Carbon::createFromDate($validatedData['report_year'], $validatedData['report_month'], 1) : null,
            'goal' => $validatedData['goal'] ?? '',
            'account_period_start' => $validatedData['account_period_start'] ?? null,
            'account_period_end' => $validatedData['account_period_end'] ?? null,
            'amount_sanctioned_overview' => $validatedData['amount_sanctioned_overview'] ?? 0.0,
            'amount_forwarded_overview' => 0.0, // Always set to 0 for backward compatibility
            'amount_in_hand' => $validatedData['amount_in_hand'] ?? 0.0,
            'total_balance_forwarded' => $validatedData['total_balance_forwarded'] ?? 0.0,
            'status' => 'draft'
        ]);

        if ($project) {
            $report->society_id = $project->society_id;
            $report->society_name = $project->society_name;
            $report->province_id = $project->province_id;
            $report->save();
        }

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

    $keptActivityIds = [];

    foreach ($activitiesInput as $activityIndex => $activityText) {
        $month = $request->input("month.$objectiveIndex.$activityIndex");
        $summary = $request->input("summary_activities.$objectiveIndex.$activityIndex.1");
        $qual = $request->input("qualitative_quantitative_data.$objectiveIndex.$activityIndex.1");
        $inter = $request->input("intermediate_outcomes.$objectiveIndex.$activityIndex.1");
        $projectActivityId = $projectActivityIds[$activityIndex] ?? null;

        // Month is filled by JS (report-period-sync), not by user. Only store when at least
        // one user-filled field is present; otherwise ignore this activity (and its month).
        $filled = (trim((string) ($summary ?? '')) !== '')
            || (trim((string) ($qual ?? '')) !== '')
            || (trim((string) ($inter ?? '')) !== '')
            || (
                trim((string) ($projectActivityId ?? '')) === ''
                && trim((string) ($activityText ?? '')) !== ''
            );

        if (! $filled) {
            continue;
        }

        $activity_id_suffix = str_pad($activityIndex + 1, 3, '0', STR_PAD_LEFT);
        $activity_id = "{$objective_id}-{$activity_id_suffix}";
        $keptActivityIds[] = $activity_id;

        $activityData = [
            'objective_id' => $objective->objective_id,
            'project_activity_id' => $projectActivityId,
            'activity' => $activityText,
            'month' => $month,
            'summary_activities' => $summary,
            'qualitative_quantitative_data' => $qual,
            'intermediate_outcomes' => $inter,
        ];

        Log::info('Processing activity data:', $activityData);

        $activity = DPActivity::where('activity_id', $activity_id)->first();

        if ($activity) {
            $activity->update($activityData);
            Log::info('Activity updated:', $activity->toArray());
        } else {
            $activityData['activity_id'] = $activity_id;
            $activity = DPActivity::create($activityData);
            Log::info('Activity created:', $activity->toArray());
        }
    }

    // Remove activities not in the request or that were skipped (no user-filled field).
    DPActivity::where('objective_id', $objective->objective_id)
        ->whereNotIn('activity_id', $keptActivityIds)
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

            // Calculate values if not provided (server-side calculation as backup)
            $amountSanctioned = (float)($request->input("amount_sanctioned.{$index}") ?? 0);
            $expensesLastMonth = (float)($request->input("expenses_last_month.{$index}") ?? 0);
            $expensesThisMonth = (float)($request->input("expenses_this_month.{$index}") ?? 0);

            // Calculate total_amount if not provided (no longer includes amount_forwarded)
            $totalAmountInput = $request->input("total_amount.{$index}");
            $totalAmount = $totalAmountInput !== null ? (float)$totalAmountInput : $amountSanctioned;

            // Calculate total_expenses if not provided (column 5 + 6)
            $totalExpensesInput = $request->input("total_expenses.{$index}");
            $totalExpenses = $totalExpensesInput !== null ? (float)$totalExpensesInput : ($expensesLastMonth + $expensesThisMonth);

            // Calculate balance_amount if not provided
            $balanceAmountInput = $request->input("balance_amount.{$index}");
            $balanceAmount = $balanceAmountInput !== null ? (float)$balanceAmountInput : ($totalAmount - $totalExpenses);

            $accountDetailData = [
                'report_id' => $report_id,
                'project_id' => $project_id,
                'particulars' => $particular,
                'amount_forwarded' => 0.0, // Always set to 0 for backward compatibility
                'amount_sanctioned' => $amountSanctioned,
                'total_amount' => $totalAmount,
                'expenses_last_month' => $expensesLastMonth,
                'expenses_this_month' => $expensesThisMonth,
                'total_expenses' => $totalExpenses,
                'balance_amount' => $balanceAmount,
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
        $report->load('objectives.activities');

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
            $monthYear = date('m_Y', strtotime($report->reporting_period_from));
            $folderPath = "REPORTS/{$project->project_id}/{$report_id}/photos/{$monthYear}";
            $optimizer = app(ReportPhotoOptimizationService::class);

            foreach ($photos as $groupIndex => $files) {
                $val = $request->input("photo_activity_id.{$groupIndex}") ?? ($request->input('photo_activity_id')[$groupIndex] ?? null);
                $activity_id = $this->resolveActivityId($report, $val);

                $existingCount = $activity_id
                    ? DPPhoto::where('activity_id', $activity_id)->count()
                    : DPPhoto::where('report_id', $report_id)->whereNull('activity_id')->count();
                if ($activity_id !== null && $existingCount + count($files) > 3) {
                    $files = array_slice($files, 0, max(0, 3 - $existingCount));
                    Log::warning('Photo group truncated to 3 per activity', ['group_index' => $groupIndex, 'activity_id' => $activity_id]);
                }

                Log::info("Processing photo group {$groupIndex}", ['activity_id' => $activity_id]);
                $addedInGroup = 0;

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

                    $latestPhoto = DPPhoto::where('photo_id', 'LIKE', "{$report_id}-%")->latest('photo_id')->lockForUpdate()->first();
                    $max_suffix = $latestPhoto ? intval(substr($latestPhoto->photo_id, -4)) + 1 : 1;
                    $photo_id = "{$report_id}-" . str_pad($max_suffix, 4, '0', STR_PAD_LEFT);

                    $result = $optimizer->optimize($file);
                    $ext = $result !== null ? 'jpg' : (strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)) ?: 'jpg');
                    $incremental = $existingCount + $addedInGroup + 1;
                    $filename = $this->buildActivityBasedFilename($report, $activity_id, $incremental, $ext);
                    $path = $folderPath . '/' . $filename;

                    if ($result !== null) {
                        Storage::disk('public')->put($path, $result['data']);
                        $photo_location = $result['location'] ?? null;
                    } else {
                        $path = $file->storeAs($folderPath, $filename, 'public');
                        $photo_location = null;
                    }

                    Log::info('Photo stored successfully', ['path' => $path, 'photo_id' => $photo_id, 'group_index' => $groupIndex, 'file_index' => $fileIndex]);

                    DPPhoto::create([
                        'photo_id' => $photo_id,
                        'report_id' => $report_id,
                        'activity_id' => $activity_id,
                        'photo_path' => $path,
                        'description' => $activity_id === null ? ($request->input("photo_descriptions.{$groupIndex}") ?? null) : null,
                        'photo_location' => $photo_location,
                    ]);
                    $addedInGroup++;
                    Log::info('Photo record created in database', ['photo_id' => $photo_id]);
                }
            }
        } else {
            Log::warning('No photos found in request', [
                'request_files' => $request->files->all(),
            ]);
            LogHelper::logSafeRequest('No photos found in request - input data', $request, LogHelper::getReportAllowedFields());
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
        $report->load('objectives.activities');

        // Get project to access project_id
        $project = Project::where('project_id', $report->project_id)->first();
        if (!$project) {
            Log::error('Project not found', ['project_id' => $report->project_id]);
            return;
        }

        $existingPhotoIds = $request->input('existing_photo_ids', []);
        $photoDescriptions = $request->input('photo_descriptions', []);
        $photoActivityIds = is_array($request->input('photo_activity_id', [])) ? $request->input('photo_activity_id') : [];

        foreach ($existingPhotoIds as $index => $photoId) {
            if (!$photoId) {
                continue;
            }
            $photo = DPPhoto::where('photo_id', $photoId)->where('report_id', $report_id)->first();
            if (!$photo) {
                continue;
            }
            // Group key by activity (aligned with edit form: one section per activity)
            $groupKey = $photo->activity_id ?? '_unassigned_';
            $description = $photoDescriptions[$groupKey] ?? $photo->description ?? '';
            $updates = ['description' => $description];
            if ($request->has('photo_activity_id')) {
                $val = $photoActivityIds[$groupKey] ?? '__unassigned__';
                $resolved = $this->resolveActivityId($report, $val);
                $updates['activity_id'] = $resolved;
                $updates['description'] = $resolved === null ? $description : null;
            }
            $photo->update($updates);
            Log::info("Updated existing photo: {$photoId}");
        }

        // Handle new photo uploads (same as handlePhotos: activity_id, 3-per-activity, activity-based filename)
        $photos = $request->file('photos');
        if ($photos && count($photos) > 0) {
            Log::info('New photos found in request');
            $monthYear = date('m_Y', strtotime($report->reporting_period_from));
            $folderPath = "REPORTS/{$project->project_id}/{$report_id}/photos/{$monthYear}";
            $optimizer = app(ReportPhotoOptimizationService::class);

            foreach ($photos as $groupIndex => $files) {
                $val = $request->input("photo_activity_id.{$groupIndex}") ?? ($request->input('photo_activity_id')[$groupIndex] ?? null);
                $activity_id = $this->resolveActivityId($report, $val);

                $existingCount = $activity_id
                    ? DPPhoto::where('activity_id', $activity_id)->count()
                    : DPPhoto::where('report_id', $report_id)->whereNull('activity_id')->count();
                if ($activity_id !== null && $existingCount + count($files) > 3) {
                    $files = array_slice($files, 0, max(0, 3 - $existingCount));
                    Log::warning('Photo group truncated to 3 per activity', ['group_index' => $groupIndex, 'activity_id' => $activity_id]);
                }

                $addedInGroup = 0;
                foreach ($files as $fileIndex => $file) {
                    if (!$file->isValid() || $file->getSize() > 2097152) {
                        continue;
                    }
                    $latestPhoto = DPPhoto::where('photo_id', 'LIKE', "{$report_id}-%")->latest('photo_id')->lockForUpdate()->first();
                    $max_suffix = $latestPhoto ? intval(substr($latestPhoto->photo_id, -4)) + 1 : 1;
                    $photo_id = "{$report_id}-" . str_pad($max_suffix, 4, '0', STR_PAD_LEFT);

                    $result = $optimizer->optimize($file);
                    $ext = $result !== null ? 'jpg' : (strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)) ?: 'jpg');
                    $incremental = $existingCount + $addedInGroup + 1;
                    $filename = $this->buildActivityBasedFilename($report, $activity_id, $incremental, $ext);
                    $path = $folderPath . '/' . $filename;

                    if ($result !== null) {
                        Storage::disk('public')->put($path, $result['data']);
                        $photo_location = $result['location'] ?? null;
                    } else {
                        $path = $file->storeAs($folderPath, $filename, 'public');
                        $photo_location = null;
                    }

                    DPPhoto::create([
                        'photo_id' => $photo_id,
                        'report_id' => $report_id,
                        'activity_id' => $activity_id,
                        'photo_path' => $path,
                        'description' => $activity_id === null ? ($request->input("photo_descriptions.{$groupIndex}") ?? null) : null,
                        'photo_location' => $photo_location,
                    ]);
                    $addedInGroup++;
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
                            'photo_id' => $photoId,
                            'photo_path' => $photo->photo_path,
                            'deleted' => $fileDeleted
                        ]);
                    } else {
                        Log::warning('Photo file not found in storage', [
                            'photo_id' => $photoId,
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
        if (in_array($user->role, ['executor', 'applicant'])) {
            // Executors and applicants can see reports for projects they own or are in-charge of
            $projectIds = ProjectQueryService::getProjectIdsForUser($user);
            $reportsQuery->whereIn('project_id', $projectIds);
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
            'objectives.activities.photos',
            'accountDetails',
            'photos',
            'outlooks',
            'attachments',
            'activityHistory.changedBy'
        ])->where('report_id', $report_id);

        // Apply role-based filters
        if ($user->role === 'provincial') {
            // Filter reports to those whose user (executor) has parent_id = this provincial user's id
            $report->whereHas('user', function ($query) use ($user) {
                $query->where('parent_id', $user->id);
            });
        } elseif (in_array($user->role, ['executor', 'applicant'])) {
            // Filter reports to those for projects where user is owner or in-charge
            $projectIds = ProjectQueryService::getProjectIdsForUser($user);
            $report->whereIn('project_id', $projectIds);
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

        // Only show activities where the user filled at least one field (month, summary, qual, inter, or "Add Other" activity).
        foreach ($report->objectives as $objective) {
            $objective->setRelation(
                'activities',
                $objective->activities->filter(fn ($a) => $a->hasUserFilledData())->values()
            );
        }

        // Unassigned photos only (activity_id is null). Activity-linked photos are shown under each activity in objectives.
        $unassignedPhotos = $report->photos->whereNull('activity_id');

        // Retrieve associated project with eager loading to prevent N+1 queries.
        // objectives.activities.timeframes: required for Provincial activity monitoring (Phase 1–2).
        $project = Project::where('project_id', $report->project_id)
            ->with(['user', 'budgets', 'objectives.activities.timeframes'])
            ->firstOrFail();
        Log::info('Project retrieved successfully', ['project_id' => $project->project_id]);

        // Report month (1–12) for activity monitoring (Phase 2). Null if report_month_year not set.
        $reportMonth = $report->report_month_year
            ? (int) \Carbon\Carbon::parse($report->report_month_year)->format('n')
            : null;

        // Provincial monitoring (Phases 2–5): safe defaults so partials never see undefined (Phase 6.3).
        $monitoringPerObjective = [];
        $activitiesScheduledButNotReportedGroupedByObjective = [];
        $reportedActivityScheduleStatus = [];
        $budgetOverspendRows = [];
        $budgetNegativeBalanceRows = [];
        $budgetUtilisation = ['total_sanctioned' => 0, 'total_expenses' => 0, 'utilisation_percent' => 0, 'alerts' => []];
        $typeSpecificChecks = [];

        $report->setRelation('project', $project);
        $monitoringService = app(ReportMonitoringService::class);
        try {
            $monitoringPerObjective = $monitoringService->getMonitoringPerObjective($report);

            // Refined Activity Monitoring (Updates): grouped "scheduled but not reported", and status for inline badges
            $activitiesScheduledButNotReportedGroupedByObjective = $monitoringService->getActivitiesScheduledButNotReportedGroupedByObjective($report);
            $reportedActivityScheduleStatus = $monitoringService->getReportedActivityScheduleStatus($report);

            // Provincial budget monitoring (Phase 3)
            $budgetOverspendRows = $monitoringService->getBudgetOverspendRows($report);
            $budgetNegativeBalanceRows = $monitoringService->getNegativeBalanceRows($report);
            $budgetUtilisation = $monitoringService->getBudgetUtilisationSummary($report);
        } catch (\Throwable $e) {
            Log::warning('Report monitoring (activity/budget) failed', ['report_id' => $report->report_id, 'error' => $e->getMessage()]);
        }

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
                $report->setRelation('annexures', $annexures);
                break;
            case 'Institutional Ongoing Group Educational proposal':
                $ageProfiles = $this->institutionalGroupController->getAgeProfiles($report_id);
                $report->setRelation('rqis_age_profile', $ageProfiles);
                break;
            case 'Residential Skill Training Proposal 2':
                $traineeProfiles = $this->residentialSkillTrainingController->getTraineeProfiles($report_id);
                $report->setRelation('rqst_trainee_profile', $traineeProfiles);
                // Build report->education for RST monitoring
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
                            $education['other'] = $category;
                            $education['other_count'] = $number;
                            break;
                    }
                }
                $report->education = $education;
                break;
            case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                $inmateProfiles = $this->crisisInterventionCenterController->getInmateProfiles($report_id);
                $report->setRelation('rqwd_inmate_profile', $inmateProfiles);
                break;
        }

        // Type-specific monitoring (Phase 4): LDP, IGE, RST, CIC
        // Phase 5: Individual (ILP, IAH, IES, IIES), Development/CCI/Rural-Urban-Tribal/NEXT PHASE, Beneficiary
        try {
        if ($report->project_type === 'Livelihood Development Projects') {
            $typeSpecificChecks['ldp'] = $monitoringService->getLdpAnnexureChecks($report);
        } elseif ($report->project_type === 'Institutional Ongoing Group Educational proposal') {
            $typeSpecificChecks['ige'] = $monitoringService->getIgeAgeProfileChecks($report);
        } elseif ($report->project_type === 'Residential Skill Training Proposal 2') {
            $typeSpecificChecks['rst'] = $monitoringService->getRstTraineeChecks($report);
        } elseif ($report->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            $typeSpecificChecks['cic'] = $monitoringService->getCicInmateChecks($report);
        } elseif (in_array($report->project_type, [
            'Individual - Livelihood Application',
            'Individual - Access to Health',
            'Individual - Initial - Educational support',
            'Individual - Ongoing Educational support',
        ], true)) {
            $typeSpecificChecks['individual'] = $monitoringService->getIndividualBudgetChecks($report, $project);
        } elseif (in_array($report->project_type, [
            'Development Projects',
            'CHILD CARE INSTITUTION',
            'Rural-Urban-Tribal',
            'NEXT PHASE - DEVELOPMENT PROPOSAL',
        ], true)) {
            $typeSpecificChecks['development'] = $monitoringService->getDevelopmentAndSimilarChecks($report, $project);
        }

        $typeSpecificForBeneficiary = [
            'Livelihood Development Projects',
            'Institutional Ongoing Group Educational proposal',
            'Residential Skill Training Proposal 2',
            'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER',
            'Individual - Livelihood Application',
            'Individual - Access to Health',
            'Individual - Initial - Educational support',
            'Individual - Ongoing Educational support',
            'Development Projects',
            'CHILD CARE INSTITUTION',
            'Rural-Urban-Tribal',
            'NEXT PHASE - DEVELOPMENT PROPOSAL',
        ];
        if (in_array($report->project_type ?? '', $typeSpecificForBeneficiary, true)) {
            $typeSpecificChecks['beneficiary'] = $monitoringService->getBeneficiaryConsistencyChecks($report, $project);
        }
        } catch (\Throwable $e) {
            Log::warning('Report monitoring (type-specific) failed', ['report_id' => $report->report_id, 'error' => $e->getMessage()]);
        }

        // Phase 4 (read-only): Canonical project-level budget for display/reference; discrepancy visibility
        $projectAmountSanctioned = (float) ($project->amount_sanctioned ?? 0);
        $projectOpeningBalance = (float) ($project->opening_balance ?? 0);
        $reportSanctioned = (float) ($report->amount_sanctioned_overview ?? 0);
        $tolerance = 0.01;
        $showBudgetDiscrepancyNote = abs($reportSanctioned - $projectAmountSanctioned) > $tolerance;
        if ($showBudgetDiscrepancyNote) {
            BudgetAuditLogger::logReportProjectDiscrepancy(
                $report->report_id,
                $project->project_id,
                $reportSanctioned,
                $projectAmountSanctioned
            );
        }

        // Pass data to the view
        return view('reports.monthly.show', compact(
            'report',
            'unassignedPhotos',
            'project',
            'budgets',
            'annexures',
            'ageProfiles',
            'traineeProfiles',
            'inmateProfiles',
            'monitoringPerObjective',
            'activitiesScheduledButNotReportedGroupedByObjective',
            'reportedActivityScheduleStatus',
            'budgetOverspendRows',
            'budgetNegativeBalanceRows',
            'budgetUtilisation',
            'typeSpecificChecks',
            'projectAmountSanctioned',
            'projectOpeningBalance',
            'showBudgetDiscrepancyNote'
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
        } elseif (in_array($user->role, ['executor', 'applicant'])) {
            // Executor and applicant can edit reports for projects they own or are in-charge of
            $projectIds = ProjectQueryService::getProjectIdsForUser($user);
            $reportQuery->whereIn('project_id', $projectIds);
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
        // Group photos by activity_id (align with create: one section per activity)
        $byActivity = $report->photos->groupBy(fn ($p) => $p->activity_id ?? '_unassigned_');
        $ordered = [];
        $objNum = 0;
        foreach ($report->objectives ?? [] as $obj) {
            $objNum++;
            foreach ($obj->activities ?? [] as $act) {
                $aid = $act->activity_id ?? null;
                if ($aid && $byActivity->has($aid)) {
                    $label = 'Objective ' . $objNum . ' – ' . \Str::limit($act->activity ?? 'Activity', 50);
                    $ordered[] = ['groupKey' => $aid, 'photos' => $byActivity->get($aid), 'activityLabel' => $label];
                }
            }
        }
        // Orphan activity_ids (in photos but no longer in report objectives)
        $seen = collect($ordered)->pluck('groupKey')->all();
        foreach ($byActivity->keys() as $k) {
            if ($k === '_unassigned_' || in_array($k, $seen)) {
                continue;
            }
            $act = \App\Models\Reports\Monthly\DPActivity::where('activity_id', $k)->first();
            $label = $act ? \Str::limit($act->activity ?? 'Activity', 50) : 'Activity (removed from report)';
            $ordered[] = ['groupKey' => $k, 'photos' => $byActivity->get($k), 'activityLabel' => $label];
        }
        if ($byActivity->has('_unassigned_')) {
            $ordered[] = ['groupKey' => '_unassigned_', 'photos' => $byActivity->get('_unassigned_'), 'activityLabel' => 'Unassigned'];
        }
        $groupedPhotos = $ordered;
        Log::info('Grouped photos by activity', ['count' => count($groupedPhotos)]);

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

        // Sanctioned amount from canonical project (Phase 4: projects.amount_sanctioned)
        $amountSanctioned = $project->amount_sanctioned ?? 0.00;
        $amountForwarded = 0.00; // Always set to 0 - no longer used in reports
        Log::info('Sanctioned amount', [
            'amountSanctioned' => $amountSanctioned,
        ]);

        // Phase 4 (read-only): Optional note when report overview is 0 but project has non-zero sanctioned
        $reportSanctionedOverview = (float) ($report->amount_sanctioned_overview ?? 0);
        $projectSanctioned = (float) ($project->amount_sanctioned ?? 0);
        $showBudgetDiscrepancyNote = $reportSanctionedOverview <= 0 && $projectSanctioned > 0;

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
            'months',
            'showBudgetDiscrepancyNote'
        ));
    }

    public function update(UpdateMonthlyReportRequest $request, $report_id)
    {
        $isDraftSave = $request->has('save_as_draft') && $request->input('save_as_draft') == '1';

        Log::info('Update method initiated', [
            'report_id' => $report_id,
            'project_id' => $request->project_id,
            'report_month' => $request->report_month,
            'report_year' => $request->report_year,
            'save_as_draft' => $isDraftSave,
        ]);

        DB::beginTransaction();
        try {
            // Validation already done by UpdateMonthlyReportRequest
            $validatedData = $request->validated();
            Log::info('Validation passed', ['validatedData' => array_keys($validatedData)]);

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
            } elseif (in_array($user->role, ['executor', 'applicant'])) {
                // Executor and applicant can update reports for projects they own or are in-charge of
                $projectIds = Project::where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhere('in_charge', $user->id);
                })->pluck('project_id');
                $reportQuery->whereIn('project_id', $projectIds);
                Log::info('Applied executor/applicant filter');
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

            // Capture previous status before update (in case status changes)
            $previousStatus = $report->status;

            // Update the main report
            Log::info('Updating main report...');
            $this->updateReport($validatedData, $report);
            Log::info('Main report updated successfully');

            // Handle updated data with proper update methods (only if not empty/null for draft saves)
            if (!$isDraftSave || !empty($validatedData['objective'])) {
                Log::info('Processing objectives and activities...');
                $this->storeObjectivesAndActivities($request, $report_id, $report);
            }
            if (!$isDraftSave || !empty($validatedData['particulars'])) {
                Log::info('Processing account details...');
                $this->handleAccountDetails($request, $report_id, $validatedData['project_id']);
            }
            Log::info('Processing outlooks...');
            $this->handleOutlooks($request, $report_id);

            Log::info('Processing photos...');
            $this->updatePhotos($request, $report_id);

            Log::info('Processing specific project data...');
            $this->handleSpecificProjectData($request, $report_id);

            // Handle new attachments
            Log::info('Processing attachments...');
            $this->handleUpdateAttachments($request, $report);

            // Set status to draft if saving as draft
            $statusChanged = false;
            if ($isDraftSave && $previousStatus !== DPReport::STATUS_DRAFT) {
                $report->status = DPReport::STATUS_DRAFT;
                $report->save();
                $statusChanged = true;
                Log::info('Report saved as draft', ['report_id' => $report_id, 'previous_status' => $previousStatus]);
            }

            DB::commit();

            // Refresh report to get latest data
            $report->refresh();

            // Log activity update (pass previousStatus if status changed, otherwise it will use current status)
            if ($isDraftSave) {
                ActivityHistoryService::logReportUpdate($report, $user, 'Report saved as draft', $statusChanged ? $previousStatus : null);
            } else {
                ActivityHistoryService::logReportUpdate($report, $user, 'Report details updated', null);
            }

            Log::info('Transaction committed and report updated successfully.');

            // Redirect based on draft save
            if ($isDraftSave) {
                return redirect()->route('monthly.report.edit', $report_id)
                    ->with('success', 'Report saved as draft. You can continue editing later.');
            }

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
        // Wave 6A Phase 6: Report society snapshot is immutable; do not accept or apply society/province from request.
        if (array_key_exists('society_id', $validatedData) || array_key_exists('province_id', $validatedData)) {
            abort(403, 'Report society snapshot cannot be changed.');
        }

        $report->update([
            'project_id' => $validatedData['project_id'],
            'project_title' => $validatedData['project_title'] ?? '',
            'project_type' => $validatedData['project_type'] ?? '',
            'place' => $validatedData['place'] ?? '',
            'commencement_month_year' => $validatedData['commencement_month_year'] ?? null,
            'in_charge' => $validatedData['in_charge'] ?? '',
            'total_beneficiaries' => $validatedData['total_beneficiaries'] ?? 0,
            'report_month_year' => isset($validatedData['report_year']) && isset($validatedData['report_month']) ? Carbon::createFromDate($validatedData['report_year'], $validatedData['report_month'], 1) : null,
            'goal' => $validatedData['goal'] ?? '',
            'account_period_start' => $validatedData['account_period_start'] ?? null,
            'account_period_end' => $validatedData['account_period_end'] ?? null,
            'amount_sanctioned_overview' => $validatedData['amount_sanctioned_overview'] ?? 0,
            'amount_forwarded_overview' => 0, // Always set to 0 for backward compatibility
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
        } elseif (in_array($user->role, ['executor', 'applicant'])) {
            // Executor and applicant can review reports for projects they own or are in-charge of
            $projectIds = ProjectQueryService::getProjectIdsForUser($user);
            $reportQuery->whereIn('project_id', $projectIds);
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
        Log::info('Entering revert method', [
            'report_id' => $report_id,
            'revert_reason' => $request->revert_reason ?? 'No reason provided',
        ]);

        $user = Auth::user();

        // Find the report with role-based filtering
        $reportQuery = DPReport::where('report_id', $report_id)->with('user');

        // Apply role-based filters
        if ($user->role === 'provincial') {
            // Provincial can revert reports from executors under them
            $reportQuery->whereHas('user', function ($query) use ($user) {
                $query->where('parent_id', $user->id);
            });
        } elseif (in_array($user->role, ['executor', 'applicant'])) {
            // Executor and applicant can revert reports for projects they own or are in-charge of
            $projectIds = ProjectQueryService::getProjectIdsForUser($user);
            $reportQuery->whereIn('project_id', $projectIds);
        } elseif ($user->role === 'coordinator') {
            // Coordinator can revert all reports (no filtering needed)
            // No additional filters - coordinators can revert everything
        } else {
            // If role is not specified, deny access
            abort(403, 'Access denied');
        }

        $report = $reportQuery->firstOrFail();

        try {
            $reason = $request->input('revert_reason');

            // Use ReportStatusService to revert and log status change
            if ($user->role === 'coordinator') {
                ReportStatusService::revertByCoordinator($report, $user, $reason);
            } elseif ($user->role === 'provincial') {
                ReportStatusService::revertByProvincial($report, $user, $reason);
            } else {
                throw new \Exception('Invalid role for reverting report.');
            }

            // Notify executor about report revert (only if reverting as coordinator or provincial)
            $executor = $report->user;
            if ($executor && $report->id && in_array($user->role, ['coordinator', 'provincial'])) {
                NotificationService::notifyRevert(
                    $executor,
                    'report',
                    $report->id,
                    "Report {$report->report_id}",
                    $reason
                );
            }

            return redirect()->route('monthly.report.index')->with('success', 'Report reverted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to revert report', [
                'report_id' => $report_id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('monthly.report.index')->with('error', $e->getMessage());
        }
    }

    public function submit(Request $request, $report_id)
    {
        Log::info('Entering submit method', ['report_id' => $report_id]);

        $user = Auth::user();

        // Only executors and applicants can submit reports
        if (!in_array($user->role, ['executor', 'applicant'])) {
            abort(403, 'Only executors and applicants can submit reports.');
        }

        // Get project IDs where user is owner or in-charge
        $projectIds = Project::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('in_charge', $user->id);
        })->pluck('project_id');

        // Find the report
        $report = DPReport::where('report_id', $report_id)
                         ->whereIn('project_id', $projectIds)
                         ->firstOrFail();

        try {
            // Use ReportStatusService to submit and log status change
            ReportStatusService::submitToProvincial($report, $user);

            return redirect()->route('monthly.report.index')->with('success', 'Report submitted to Provincial successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to submit report', [
                'report_id' => $report_id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('monthly.report.index')->with('error', $e->getMessage());
        }
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

        try {
            // Use ReportStatusService to forward and log status change
            ReportStatusService::forwardToCoordinator($report, $user);

            return redirect()->route('monthly.report.index')->with('success', 'Report forwarded to Coordinator successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to forward report', [
                'report_id' => $report_id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('monthly.report.index')->with('error', $e->getMessage());
        }
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
        $report = DPReport::where('report_id', $report_id)->with('user')->firstOrFail();

        try {
            // Use ReportStatusService to approve and log status change
            ReportStatusService::approve($report, $user);

            // Notify executor about report approval
            $executor = $report->user;
            if ($executor && $report->id) {
                NotificationService::notifyApproval(
                    $executor,
                    'report',
                    $report->id,
                    "Report {$report->report_id}"
                );
            }

            return redirect()->route('monthly.report.index')->with('success', 'Report approved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to approve report', [
                'report_id' => $report_id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('monthly.report.index')->with('error', $e->getMessage());
        }
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
                                    } elseif (in_array($user->role, ['executor', 'applicant'])) {
                                        // Executor and applicant can remove photos from reports for projects they own or are in-charge of
                                        $projectIds = Project::where(function($q) use ($user) {
                                            $q->where('user_id', $user->id)
                                              ->orWhere('in_charge', $user->id);
                                        })->pluck('project_id');
                                        $query->whereIn('project_id', $projectIds);
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
