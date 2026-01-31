<?php

namespace App\Http\Controllers\Admin;

use App\Constants\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\BudgetCorrectionAudit;
use App\Models\OldProjects\Project;
use App\Models\User;
use App\Services\Budget\AdminCorrectionService;
use App\Services\Budget\ProjectFundFieldsResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Phase 6: Admin-only budget reconciliation.
 * View discrepancies (stored vs resolver), accept suggested / manual correction / reject.
 * All actions are audited; no automatic correction.
 *
 * @see Documentations/V1/Basic Info fund Mapping Issue/PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md §10 Phase 6a
 */
class BudgetReconciliationController extends Controller
{
    protected ProjectFundFieldsResolver $resolver;
    protected AdminCorrectionService $correctionService;

    public function __construct(ProjectFundFieldsResolver $resolver, AdminCorrectionService $correctionService)
    {
        $this->resolver = $resolver;
        $this->correctionService = $correctionService;
    }

    /**
     * Ensure only admin and feature flag allow access.
     */
    protected function authorizeReconciliation(): void
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Only administrators may access budget reconciliation.');
        }
        if (!config('budget.admin_reconciliation_enabled', false)) {
            abort(403, 'Budget reconciliation is not enabled.');
        }
    }

    /**
     * List approved projects with stored vs resolved comparison; highlight discrepancies.
     */
    public function index(Request $request)
    {
        $this->authorizeReconciliation();

        $query = Project::query()
            ->whereIn('status', [
                ProjectStatus::APPROVED_BY_COORDINATOR,
                ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR,
                ProjectStatus::APPROVED_BY_GENERAL_AS_PROVINCIAL,
            ]);

        if ($request->filled('project_type')) {
            $query->where('project_type', $request->project_type);
        }
        if ($request->filled('approval_date_from')) {
            $query->whereDate('updated_at', '>=', $request->approval_date_from);
        }
        if ($request->filled('approval_date_to')) {
            $query->whereDate('updated_at', '<=', $request->approval_date_to);
        }

        $projects = $query->orderBy('updated_at', 'desc')->get();

        $rows = [];
        foreach ($projects as $project) {
            $resolved = $this->resolver->resolve($project, true);
            $stored = $this->correctionService->getStoredValues($project);
            $hasDiscrepancy = $this->correctionService->hasDiscrepancy($resolved, $stored);

            if ($request->boolean('only_discrepancies') && !$hasDiscrepancy) {
                continue;
            }

            $rows[] = [
                'project' => $project,
                'stored' => $stored,
                'resolved' => $resolved,
                'has_discrepancy' => $hasDiscrepancy,
            ];
        }

        $projectTypes = Project::query()
            ->whereIn('status', [
                ProjectStatus::APPROVED_BY_COORDINATOR,
                ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR,
                ProjectStatus::APPROVED_BY_GENERAL_AS_PROVINCIAL,
            ])
            ->distinct()
            ->pluck('project_type')
            ->filter()
            ->sort()
            ->values();

        return view('admin.budget_reconciliation.index', [
            'rows' => $rows,
            'projectTypes' => $projectTypes,
            'filters' => $request->only(['project_type', 'approval_date_from', 'approval_date_to', 'only_discrepancies']),
        ]);
    }

    /**
     * Single project: side-by-side comparison; admin chooses accept / manual / reject.
     */
    public function show(int $id)
    {
        $this->authorizeReconciliation();

        $project = Project::findOrFail($id);
        if (!ProjectStatus::isApproved($project->status ?? '')) {
            abort(403, 'Only approved projects can be reconciled.');
        }

        $resolved = $this->resolver->resolve($project, true);
        $stored = $this->correctionService->getStoredValues($project);
        $hasDiscrepancy = $this->correctionService->hasDiscrepancy($resolved, $stored);

        $auditHistory = BudgetCorrectionAudit::where('project_id', $project->id)
            ->with('adminUser')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.budget_reconciliation.show', [
            'project' => $project,
            'stored' => $stored,
            'resolved' => $resolved,
            'has_discrepancy' => $hasDiscrepancy,
            'audit_history' => $auditHistory,
        ]);
    }

    /**
     * Accept system-suggested correction (resolver values → projects).
     */
    public function acceptSuggested(Request $request, int $id)
    {
        $this->authorizeReconciliation();

        $request->validate([
            'admin_comment' => 'nullable|string|max:2000',
        ]);

        $project = Project::findOrFail($id);
        $admin = Auth::user();
        if ($admin->role !== 'admin') {
            abort(403, 'Only administrators may apply corrections.');
        }

        try {
            $this->correctionService->acceptSuggested($project, $admin, $request->input('admin_comment'));
        } catch (ValidationException $e) {
            return redirect()
                ->route('admin.budget-reconciliation.show', $id)
                ->withErrors($e->errors())
                ->withInput();
        }

        return redirect()
            ->route('admin.budget-reconciliation.index')
            ->with('success', 'System-suggested correction applied. Project budget updated and logged.');
    }

    /**
     * Manual correction: admin-provided values + mandatory reason.
     */
    public function manualCorrection(Request $request, int $id)
    {
        $this->authorizeReconciliation();

        $request->validate([
            'overall_project_budget' => 'required|numeric|min:0',
            'amount_forwarded' => 'required|numeric|min:0',
            'local_contribution' => 'required|numeric|min:0',
            'admin_comment' => 'required|string|max:2000',
        ], [
            'admin_comment.required' => 'A reason is required for manual correction.',
        ]);

        $project = Project::findOrFail($id);
        $admin = Auth::user();
        if ($admin->role !== 'admin') {
            abort(403, 'Only administrators may apply corrections.');
        }

        $newValues = [
            'overall_project_budget' => $request->input('overall_project_budget'),
            'amount_forwarded' => $request->input('amount_forwarded'),
            'local_contribution' => $request->input('local_contribution'),
        ];
        // Sanctioned and opening will be recomputed in service.

        try {
            $this->correctionService->manualCorrection($project, $admin, $newValues, $request->input('admin_comment'));
        } catch (ValidationException $e) {
            return redirect()
                ->route('admin.budget-reconciliation.show', $id)
                ->withErrors($e->errors())
                ->withInput();
        }

        return redirect()
            ->route('admin.budget-reconciliation.index')
            ->with('success', 'Manual correction applied and logged.');
    }

    /**
     * Reject correction: no data change; project marked as "reviewed" via audit log.
     */
    public function reject(Request $request, int $id)
    {
        $this->authorizeReconciliation();

        $request->validate([
            'admin_comment' => 'nullable|string|max:2000',
        ]);

        $project = Project::findOrFail($id);
        $admin = Auth::user();
        if ($admin->role !== 'admin') {
            abort(403, 'Only administrators may record a rejection.');
        }

        try {
            $this->correctionService->rejectCorrection($project, $admin, $request->input('admin_comment'));
        } catch (ValidationException $e) {
            return redirect()
                ->route('admin.budget-reconciliation.show', $id)
                ->withErrors($e->errors())
                ->withInput();
        }

        return redirect()
            ->route('admin.budget-reconciliation.index')
            ->with('success', 'Correction rejected. No data changed; action logged.');
    }

    /**
     * Correction log: who, when, what changed; filter by project, date, user.
     */
    public function correctionLog(Request $request)
    {
        $this->authorizeReconciliation();

        $query = BudgetCorrectionAudit::query()->with(['project', 'adminUser']);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('user_id')) {
            $query->where('admin_user_id', $request->user_id);
        }
        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderByDesc('created_at')->paginate(50);

        $adminUsers = User::where('role', 'admin')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.budget_reconciliation.correction_log', [
            'logs' => $logs,
            'adminUsers' => $adminUsers,
            'filters' => $request->only(['project_id', 'user_id', 'action_type', 'date_from', 'date_to']),
        ]);
    }
}
