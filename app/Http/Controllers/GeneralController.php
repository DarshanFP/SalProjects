<?php

namespace App\Http\Controllers;

use App\Models\OldProjects\Project;
use App\Models\User;
use App\Models\Province;
use App\Models\Center;
use App\Models\Society;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Reports\Monthly\DPAccountDetail;
use App\Models\ProjectComment;
use App\Models\ReportComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Constants\ProjectStatus;
use App\Services\ActivityHistoryService;
use App\Models\ActivityHistory;
use App\Services\ProjectStatusService;
use App\Services\ReportStatusService;
use App\Services\ProjectQueryService;
use App\Services\Budget\BudgetSyncService;
use App\Services\Budget\DerivedCalculationService;
use Carbon\Carbon;
use Exception;

class GeneralController extends Controller
{
    public function __construct(
        private readonly DerivedCalculationService $calculationService
    ) {
    }

    /**
     * General Dashboard - Combined view showing coordinator hierarchy + direct team
     *
     * General user has COMPLETE coordinator access for coordinator hierarchy
     * General user also acts as provincial for direct team management
     */
    public function generalDashboard(Request $request)
    {
        $general = Auth::user();

        // Verify user is general
        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can access this dashboard.');
        }

        Log::info('General Dashboard Request', [
            'user_id' => $general->id,
            'province' => $request->get('province'),
            'center' => $request->get('center'),
            'coordinator_id' => $request->get('coordinator_id'),
        ]);

        // Get coordinator IDs under general
        $coordinatorIds = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->pluck('id');

        // Get direct team IDs (executors/applicants directly under general)
        $directTeamIds = User::where('parent_id', $general->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->pluck('id');

        // Get all descendant user IDs under coordinators (recursive)
        $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

        // Get projects from coordinator hierarchy
        $projectsFromCoordinatorsQuery = ProjectQueryService::getProjectsForUsersQuery($allUserIdsUnderCoordinators);

        // Get projects from direct team
        $projectsFromDirectTeamQuery = ProjectQueryService::getProjectsForUsersQuery($directTeamIds);

        // Apply filters for coordinator hierarchy projects
        if ($request->filled('coordinator_id')) {
            $coordinatorId = $request->get('coordinator_id');
            $descendantIds = $this->getAllDescendantUserIds(collect([$coordinatorId]));
            $projectsFromCoordinatorsQuery = ProjectQueryService::getProjectsForUsersQuery($descendantIds);
        }

        // Apply filters for direct team projects
        if ($request->filled('center')) {
            $projectsFromDirectTeamQuery->whereHas('user', function($query) use ($request) {
                $query->where('center', $request->center);
            });
        }

        if ($request->filled('province')) {
            $projectsFromCoordinatorsQuery->whereHas('user', function($query) use ($request) {
                $query->where('province', $request->province);
            });
        }

        $projectsFromCoordinators = $projectsFromCoordinatorsQuery
            ->with(['user.parent', 'reports.accountDetails', 'budgets'])
            ->get();

        $projectsFromDirectTeam = $projectsFromDirectTeamQuery
            ->with(['user', 'reports.accountDetails', 'budgets'])
            ->get();

        // Combine projects
        $allProjects = $projectsFromCoordinators->merge($projectsFromDirectTeam);

        // Calculate statistics
        $statistics = [
            'total_coordinators' => $coordinatorIds->count(),
            'total_direct_team' => $directTeamIds->count(),
            'total_projects' => $allProjects->count(),
            'pending_projects_coordinators' => $projectsFromCoordinators->where('status', ProjectStatus::FORWARDED_TO_COORDINATOR)->count(),
            'pending_projects_direct_team' => $projectsFromDirectTeam->where('status', ProjectStatus::SUBMITTED_TO_PROVINCIAL)->count(),
            'pending_reports_coordinators' => 0, // Will be calculated when we add report queries
            'pending_reports_direct_team' => 0, // Will be calculated when we add report queries
        ];

        // Get filter options
        $coordinators = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->select('id', 'name', 'province')
            ->get();

        $provinces = User::whereIn('id', $allUserIdsUnderCoordinators->merge($directTeamIds))
            ->distinct()
            ->pluck('province')
            ->filter()
            ->values();

        $centers = User::whereIn('id', $directTeamIds)
            ->whereNotNull('center')
            ->where('center', '!=', '')
            ->distinct()
            ->pluck('center')
            ->filter()
            ->values();

        $projectTypes = $allProjects->pluck('project_type')->unique()->values();

        // Get centers map from database (for JavaScript filtering)
        $centersMap = $this->getCentersMap();

        // Get Phase 1 widget data (with caching)
        $pendingApprovalsData = $this->getPendingApprovalsData();
        $coordinatorOverviewData = $this->getCoordinatorOverviewData();
        $directTeamOverviewData = $this->getDirectTeamOverviewData();

        // Get Phase 2 widget data (with caching and filters)
        $budgetOverviewData = $this->getBudgetOverviewData($request);

        // Get Phase 3 widget data (with caching)
        $systemPerformanceData = $this->getSystemPerformanceData();
        $timeRange = $request->get('analytics_range', 30);
        $analyticsContext = $request->get('analytics_context', 'combined');
        $systemAnalyticsData = $this->getSystemAnalyticsData($timeRange, $analyticsContext);
        $contextComparisonData = $this->getContextComparisonData();

        // Get Phase 4 widget data (with caching)
        $activityFeedLimit = $request->get('activity_limit', 50);
        $activityFeedContext = $request->get('activity_context', 'combined');
        $systemActivityFeedData = $this->getSystemActivityFeedData($activityFeedLimit, $activityFeedContext);
        $systemHealthData = $this->getSystemHealthData();

        return view('general.index', compact(
            'statistics',
            'coordinators',
            'provinces',
            'centers',
            'centersMap',
            'projectTypes',
            'projectsFromCoordinators',
            'projectsFromDirectTeam',
            'allProjects',
            'pendingApprovalsData',
            'coordinatorOverviewData',
            'directTeamOverviewData',
            'budgetOverviewData',
            'systemPerformanceData',
            'systemAnalyticsData',
            'contextComparisonData',
            'systemActivityFeedData',
            'systemHealthData'
        ));
    }

    /**
     * Show form to create a new coordinator
     */
    public function createCoordinator()
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create coordinators.');
        }

        // Get provinces from database
        $provinces = Province::active()->orderBy('name')->get();

        // Get centers map from database
        $centersMap = $this->getCentersMap();

        return view('general.coordinators.create', compact('provinces', 'centersMap'));
    }

    /**
     * Store a new coordinator
     */
    public function storeCoordinator(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create coordinators.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'province' => 'required|exists:provinces,name',
            'status' => 'required|in:active,inactive',
        ]);

        // Get province and center IDs from database
        $province = Province::where('name', $request->province)->first();
        $provinceId = $province ? $province->id : null;

        $centerId = null;
        if ($request->filled('center') && $provinceId) {
            $center = Center::where('province_id', $provinceId)
                ->whereRaw('UPPER(name) = ?', [strtoupper($request->center)])
                ->first();
            $centerId = $center ? $center->id : null;
        }

        User::create([
            'parent_id' => $general->id, // Coordinator belongs to General
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'center' => $request->center,
            'center_id' => $centerId,
            'address' => $request->address,
            'role' => 'coordinator', // Fixed role - General can only create coordinators
            'province' => $request->province,
            'province_id' => $provinceId,
            'status' => $request->status,
        ]);

        Log::info('Coordinator created by General', [
            'general_id' => $general->id,
            'coordinator_email' => $request->email,
            'coordinator_name' => $request->name,
        ]);

        return redirect()->route('general.coordinators')->with('success', 'Coordinator created successfully.');
    }

    /**
     * List all coordinators under the General user
     */
    public function listCoordinators(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can view coordinators.');
        }

        // Base query - only coordinators under this general user
        $coordinatorsQuery = User::where('parent_id', $general->id)
            ->where('role', 'coordinator');

        // Apply filters
        if ($request->filled('province')) {
            $coordinatorsQuery->where('province', $request->province);
        }
        if ($request->filled('center')) {
            $coordinatorsQuery->where('center', $request->center);
        }
        if ($request->filled('status')) {
            $coordinatorsQuery->where('status', $request->status);
        }

        $coordinators = $coordinatorsQuery->get();

        // Get filter options based on coordinators under general
        $provinces = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->distinct()
            ->pluck('province')
            ->filter()
            ->values();

        $centers = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->whereNotNull('center')
            ->where('center', '!=', '')
            ->distinct()
            ->pluck('center')
            ->filter()
            ->values();

        return view('general.coordinators.index', compact('coordinators', 'provinces', 'centers'));
    }

    /**
     * Show form to edit a coordinator
     */
    public function editCoordinator($id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can edit coordinators.');
        }

        $coordinator = User::findOrFail($id);

        // Verify coordinator belongs to this general user
        if ($coordinator->parent_id !== $general->id || $coordinator->role !== 'coordinator') {
            abort(403, 'Unauthorized. You can only edit coordinators under your management.');
        }

        // Get provinces from database
        $provinces = Province::active()->orderBy('name')->get();

        // Get centers map from database
        $centersMap = $this->getCentersMap();

        return view('general.coordinators.edit', compact('coordinator', 'provinces', 'centersMap'));
    }

    /**
     * Update a coordinator
     */
    public function updateCoordinator(Request $request, $id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can update coordinators.');
        }

        $coordinator = User::findOrFail($id);

        // Verify coordinator belongs to this general user
        if ($coordinator->parent_id !== $general->id || $coordinator->role !== 'coordinator') {
            abort(403, 'Unauthorized. You can only update coordinators under your management.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'province' => 'required|exists:provinces,name',
            'status' => 'required|in:active,inactive',
        ]);

        // Get province and center IDs from database
        $province = Province::where('name', $request->province)->first();
        $provinceId = $province ? $province->id : null;

        $centerId = null;
        if ($request->filled('center') && $provinceId) {
            $center = Center::where('province_id', $provinceId)
                ->whereRaw('UPPER(name) = ?', [strtoupper($request->center)])
                ->first();
            $centerId = $center ? $center->id : null;
        }

        $coordinator->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'center' => $request->center,
            'center_id' => $centerId,
            'address' => $request->address,
            'province' => $request->province,
            'province_id' => $provinceId,
            'status' => $request->status,
            // Role and parent_id remain unchanged
        ]);

        Log::info('Coordinator updated by General', [
            'general_id' => $general->id,
            'coordinator_id' => $coordinator->id,
            'coordinator_email' => $coordinator->email,
        ]);

        return redirect()->route('general.coordinators')->with('success', 'Coordinator updated successfully.');
    }

    // ==================== Provincial User Management ====================

    /**
     * Show form to create a new provincial user
     */
    public function createProvincial()
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create provincial users.');
        }

        // Get provinces with their societies
        $provinces = Province::active()->with(['activeSocieties' => function($query) {
            $query->orderBy('name');
        }])->orderBy('name')->get();

        // Get centers map from database
        $centersMap = $this->getCentersMap();

        return view('general.provincials.create', compact('provinces', 'centersMap'));
    }

    /**
     * Store a new provincial user
     */
    public function storeProvincial(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create provincial users.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'society_name' => 'nullable|string|max:255',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'province' => 'required|exists:provinces,name',
            'status' => 'required|in:active,inactive',
        ]);

        // Get province and center IDs from database
        $province = Province::where('name', $request->province)->first();
        $provinceId = $province ? $province->id : null;

        $centerId = null;
        if ($request->filled('center') && $provinceId) {
            $center = Center::where('province_id', $provinceId)
                ->whereRaw('UPPER(name) = ?', [strtoupper($request->center)])
                ->first();
            $centerId = $center ? $center->id : null;
        }

        $provincial = User::create([
            'parent_id' => $general->id, // Provincial belongs to General
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'society_name' => $request->society_name,
            'center' => $request->center,
            'center_id' => $centerId,
            'address' => $request->address,
            'role' => 'provincial', // Fixed role - General can only create provincials
            'province' => $request->province,
            'province_id' => $provinceId,
            'status' => $request->status,
        ]);

        // Assign Spatie role
        if ($provincial && method_exists($provincial, 'assignRole')) {
            $provincial->assignRole('provincial');
        }

        Log::info('Provincial created by General', [
            'general_id' => $general->id,
            'provincial_email' => $request->email,
            'provincial_name' => $request->name,
            'province' => $request->province,
        ]);

        return redirect()->route('general.provincials')->with('success', 'Provincial user created successfully.');
    }

    /**
     * List all provincial users in the system
     * General users can see and manage ALL provincials (next to admin in hierarchy)
     */
    public function listProvincials(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can view provincial users.');
        }

        // Base query - ALL provincials in the system (general users have full access)
        $provincialsQuery = User::where('role', 'provincial');

        // Apply filters
        if ($request->filled('province')) {
            $provincialsQuery->where('province', $request->province);
        }
        if ($request->filled('center')) {
            $provincialsQuery->where('center', $request->center);
        }
        if ($request->filled('status')) {
            $provincialsQuery->where('status', $request->status);
        }

        $provincials = $provincialsQuery->orderBy('name')->get();

        // Get filter options based on ALL provincials in the system
        $provinces = User::where('role', 'provincial')
            ->distinct()
            ->pluck('province')
            ->filter()
            ->values();

        $centers = User::where('role', 'provincial')
            ->whereNotNull('center')
            ->where('center', '!=', '')
            ->distinct()
            ->pluck('center')
            ->filter()
            ->values();

        return view('general.provincials.index', compact('provincials', 'provinces', 'centers'));
    }

    /**
     * Show form to edit a provincial user
     * General users can edit ANY provincial user in the system
     */
    public function editProvincial($id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can edit provincial users.');
        }

        $provincial = User::findOrFail($id);

        // Verify user is a provincial (general users can edit any provincial)
        if ($provincial->role !== 'provincial') {
            abort(403, 'Unauthorized. This user is not a provincial user.');
        }

        // Get provinces with their societies
        $provinces = Province::active()->with(['activeSocieties' => function($query) {
            $query->orderBy('name');
        }])->orderBy('name')->get();

        // Get centers map from database
        $centersMap = $this->getCentersMap();

        return view('general.provincials.edit', compact('provincial', 'provinces', 'centersMap'));
    }

    /**
     * Update a provincial user
     * General users can update ANY provincial user in the system
     */
    public function updateProvincial(Request $request, $id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can update provincial users.');
        }

        $provincial = User::findOrFail($id);

        // Verify user is a provincial (general users can update any provincial)
        if ($provincial->role !== 'provincial') {
            abort(403, 'Unauthorized. This user is not a provincial user.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'society_name' => 'nullable|string|max:255',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'province' => 'required|exists:provinces,name',
            'status' => 'required|in:active,inactive',
        ]);

        // Get province and center IDs from database
        $province = Province::where('name', $request->province)->first();
        $provinceId = $province ? $province->id : null;

        $centerId = null;
        if ($request->filled('center') && $provinceId) {
            $center = Center::where('province_id', $provinceId)
                ->whereRaw('UPPER(name) = ?', [strtoupper($request->center)])
                ->first();
            $centerId = $center ? $center->id : null;
        }

        $provincial->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'society_name' => $request->society_name,
            'center' => $request->center,
            'center_id' => $centerId,
            'address' => $request->address,
            'province' => $request->province,
            'province_id' => $provinceId,
            'status' => $request->status,
            // Role and parent_id remain unchanged
        ]);

        Log::info('Provincial updated by General', [
            'general_id' => $general->id,
            'provincial_id' => $provincial->id,
            'provincial_name' => $request->name,
        ]);

        return redirect()->route('general.provincials')->with('success', 'Provincial user updated successfully.');
    }

    /**
     * Reset user password (coordinator or executor/applicant)
     * Handles both coordinators and direct team members
     */
    public function resetUserPassword(Request $request, $id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can reset passwords.');
        }

        $user = User::findOrFail($id);

        // Verify user type (general users can reset passwords for coordinators, provincials, and direct team)
        // For provincials: general users can reset password for ANY provincial (not just those they created)
        $isCoordinator = ($user->parent_id === $general->id && $user->role === 'coordinator');
        $isProvincial = ($user->role === 'provincial'); // General can reset password for any provincial
        $isDirectTeam = ($user->parent_id === $general->id && in_array($user->role, ['executor', 'applicant']));

        if (!$isCoordinator && !$isProvincial && !$isDirectTeam) {
            abort(403, 'Unauthorized. You can only reset passwords for coordinators, provincials, or direct team members.');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Redirect based on user type
        if ($isCoordinator) {
            Log::info('Coordinator password reset by General', [
                'general_id' => $general->id,
                'coordinator_id' => $user->id,
            ]);
            return redirect()->route('general.coordinators')->with('success', 'Coordinator password reset successfully.');
        } else {
            Log::info('Executor/Applicant password reset by General', [
                'general_id' => $general->id,
                'executor_id' => $user->id,
            ]);
            return redirect()->route('general.executors')->with('success', ucfirst($user->role) . ' password reset successfully.');
        }
    }

    /**
     * Activate user (coordinator or executor/applicant)
     * Handles both coordinators and direct team members
     */
    public function activateUser($id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can activate users.');
        }

        $user = User::findOrFail($id);

        // Verify user type (general users can activate coordinators, provincials, and direct team)
        // For provincials: general users can activate ANY provincial (not just those they created)
        $isCoordinator = ($user->parent_id === $general->id && $user->role === 'coordinator');
        $isProvincial = ($user->role === 'provincial'); // General can activate any provincial
        $isDirectTeam = ($user->parent_id === $general->id && in_array($user->role, ['executor', 'applicant']));

        if (!$isCoordinator && !$isProvincial && !$isDirectTeam) {
            abort(403, 'Unauthorized. You can only activate coordinators, provincials, or direct team members.');
        }

        $user->update(['status' => 'active']);

        // Redirect based on user type
        if ($isCoordinator) {
            Log::info('Coordinator activated by General', [
                'general_id' => $general->id,
                'coordinator_id' => $user->id,
            ]);
            return redirect()->route('general.coordinators')->with('success', 'Coordinator activated successfully.');
        } elseif ($isProvincial) {
            Log::info('Provincial activated by General', [
                'general_id' => $general->id,
                'provincial_id' => $user->id,
            ]);
            return redirect()->route('general.provincials')->with('success', 'Provincial user activated successfully.');
        } else {
            Log::info('Executor/Applicant activated by General', [
                'general_id' => $general->id,
                'executor_id' => $user->id,
            ]);
            return redirect()->route('general.executors')->with('success', ucfirst($user->role) . ' activated successfully.');
        }
    }

    /**
     * Deactivate user (coordinator or executor/applicant)
     * Handles both coordinators and direct team members
     */
    public function deactivateUser($id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can deactivate users.');
        }

        $user = User::findOrFail($id);

        // Verify user type (general users can deactivate coordinators, provincials, and direct team)
        // For provincials: general users can deactivate ANY provincial (not just those they created)
        $isCoordinator = ($user->parent_id === $general->id && $user->role === 'coordinator');
        $isProvincial = ($user->role === 'provincial'); // General can deactivate any provincial
        $isDirectTeam = ($user->parent_id === $general->id && in_array($user->role, ['executor', 'applicant']));

        if (!$isCoordinator && !$isProvincial && !$isDirectTeam) {
            abort(403, 'Unauthorized. You can only deactivate coordinators, provincials, or direct team members.');
        }

        $user->update(['status' => 'inactive']);

        // Redirect based on user type
        if ($isCoordinator) {
            Log::info('Coordinator deactivated by General', [
                'general_id' => $general->id,
                'coordinator_id' => $user->id,
            ]);
            return redirect()->route('general.coordinators')->with('success', 'Coordinator deactivated successfully.');
        } elseif ($isProvincial) {
            Log::info('Provincial deactivated by General', [
                'general_id' => $general->id,
                'provincial_id' => $user->id,
            ]);
            return redirect()->route('general.provincials')->with('success', 'Provincial user deactivated successfully.');
        } else {
            Log::info('Executor/Applicant deactivated by General', [
                'general_id' => $general->id,
                'executor_id' => $user->id,
            ]);
            return redirect()->route('general.executors')->with('success', ucfirst($user->role) . ' deactivated successfully.');
        }
    }

    /**
     * Show form to create a new executor/applicant (direct team)
     * General acts as Provincial for direct team management
     */
    public function createExecutor()
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create executors/applicants.');
        }

        // Get provinces with their societies
        $provinces = Province::active()->with(['activeSocieties' => function($query) {
            $query->orderBy('name');
        }])->orderBy('name')->get();

        // Get centers map from database (centers belong to provinces)
        $centersMap = $this->getCentersMap();

        return view('general.executors.create', compact('provinces', 'centersMap'));
    }

    /**
     * Store a new executor/applicant (direct team)
     * General acts as Provincial for direct team management
     */
    public function storeExecutor(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create executors/applicants.');
        }

        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:255',
                'society_name' => 'required|string|max:255',
                'role' => 'required|in:executor,applicant',
                'province' => 'required|exists:provinces,name',
                'center' => 'nullable|string|max:255',
                'address' => 'nullable|string',
            ]);

            Log::info('Attempting to store a new executor/applicant by General', [
                'general_id' => $general->id,
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'role' => $validatedData['role'],
                'province' => $validatedData['province'],
            ]);

            $executor = User::create([
                'name' => $validatedData['name'],
                'username' => $validatedData['username'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'phone' => $validatedData['phone'],
                'society_name' => $validatedData['society_name'],
                'province' => $validatedData['province'], // General can specify province
                'center' => $validatedData['center'],
                'address' => $validatedData['address'],
                'role' => $validatedData['role'],
                'status' => 'active',
                'parent_id' => $general->id, // Executor/applicant belongs to General (direct team)
            ]);

            // Assign Spatie role
            if ($executor && method_exists($executor, 'assignRole')) {
                $executor->assignRole($validatedData['role']);
            }

            Log::info('Executor/Applicant created successfully by General', [
                'general_id' => $general->id,
                'executor_id' => $executor->id,
                'role' => $validatedData['role'],
            ]);

            $roleName = ucfirst($validatedData['role']);
            return redirect()->route('general.executors')->with('success', $roleName . ' created successfully.');
        } catch (\Exception $e) {
            Log::error('Error storing executor/applicant by General', [
                'general_id' => $general->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors('Failed to create user: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * List all executors/applicants directly under the General user (direct team)
     */
    public function listExecutors(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can view direct team members.');
        }

        // Base query - only executors/applicants directly under this general user
        $executorsQuery = User::where('parent_id', $general->id)
            ->whereIn('role', ['executor', 'applicant']);

        // Apply filters
        if ($request->filled('province')) {
            $executorsQuery->where('province', $request->province);
        }
        if ($request->filled('center')) {
            $executorsQuery->where('center', $request->center);
        }
        if ($request->filled('role')) {
            $executorsQuery->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $executorsQuery->where('status', $request->status);
        }

        $executors = $executorsQuery->get();

        // Get filter options based on direct team members under general
        $provinces = User::where('parent_id', $general->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->distinct()
            ->pluck('province')
            ->filter()
            ->values();

        $centers = User::where('parent_id', $general->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->whereNotNull('center')
            ->where('center', '!=', '')
            ->distinct()
            ->pluck('center')
            ->filter()
            ->values();

        $roles = ['executor', 'applicant'];

        return view('general.executors.index', compact('executors', 'provinces', 'centers', 'roles'));
    }

    /**
     * Show form to edit an executor/applicant (direct team)
     */
    public function editExecutor($id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can edit executors/applicants.');
        }

        $executor = User::findOrFail($id);

        // Verify executor/applicant belongs to this general user (direct team)
        if ($executor->parent_id !== $general->id || !in_array($executor->role, ['executor', 'applicant'])) {
            abort(403, 'Unauthorized. You can only edit executors/applicants under your direct management.');
        }

        // Get provinces from database
        $provinces = Province::active()->orderBy('name')->get();

        // Get centers map from database
        $centersMap = $this->getCentersMap();

        // Get centers for the executor's current province
        $province = strtoupper($executor->province ?? '');
        $centers = $centersMap[$province] ?? [];

        return view('general.executors.edit', compact('executor', 'provinces', 'centersMap', 'centers'));
    }

    /**
     * Update an executor/applicant (direct team)
     */
    public function updateExecutor(Request $request, $id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can update executors/applicants.');
        }

        $executor = User::findOrFail($id);

        // Verify executor/applicant belongs to this general user (direct team)
        if ($executor->parent_id !== $general->id || !in_array($executor->role, ['executor', 'applicant'])) {
            abort(403, 'Unauthorized. You can only update executors/applicants under your direct management.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:255',
            'society_name' => 'required|string|max:255',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'role' => 'required|in:executor,applicant',
            'province' => 'required|exists:provinces,name',
            'status' => 'required|in:active,inactive',
        ]);

        // Get province and center IDs from database
        $province = Province::where('name', $request->province)->first();
        $provinceId = $province ? $province->id : null;

        $centerId = null;
        if ($request->filled('center') && $provinceId) {
            $center = Center::where('province_id', $provinceId)
                ->whereRaw('UPPER(name) = ?', [strtoupper($request->center)])
                ->first();
            $centerId = $center ? $center->id : null;
        }

        $executor->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'society_name' => $request->society_name,
            'center' => $request->center,
            'center_id' => $centerId,
            'address' => $request->address,
            'role' => $request->role,
            'province' => $request->province,
            'province_id' => $provinceId,
            'status' => $request->status,
            // parent_id remains unchanged
        ]);

        // Update Spatie role assignment
        if (method_exists($executor, 'syncRoles')) {
            $executor->syncRoles([$request->role]);
        }

        Log::info('Executor/Applicant updated by General', [
            'general_id' => $general->id,
            'executor_id' => $executor->id,
            'role' => $request->role,
        ]);

        $roleName = ucfirst($request->role);
        return redirect()->route('general.executors')->with('success', $roleName . ' updated successfully.');
    }


    /**
     * List all projects (combined: coordinator hierarchy + direct team)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function listProjects(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can view projects.');
        }

        // Get coordinator IDs under general
        $coordinatorIds = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->pluck('id');

        // Get direct team IDs (executors/applicants directly under general)
        $directTeamIds = User::where('parent_id', $general->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->pluck('id');

        // Get all descendant user IDs under coordinators (recursive)
        $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

        // Base query for projects from coordinator hierarchy
        $projectsFromCoordinatorsQuery = ProjectQueryService::getProjectsForUsersQuery($allUserIdsUnderCoordinators);

        // Base query for projects from direct team
        $projectsFromDirectTeamQuery = ProjectQueryService::getProjectsForUsersQuery($directTeamIds);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $projectsFromCoordinatorsQuery->where(function($q) use ($searchTerm) {
                $q->where('project_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_type', 'like', '%' . $searchTerm . '%')
                  ->orWhere('status', 'like', '%' . $searchTerm . '%');
            });
            $projectsFromDirectTeamQuery->where(function($q) use ($searchTerm) {
                $q->where('project_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_type', 'like', '%' . $searchTerm . '%')
                  ->orWhere('status', 'like', '%' . $searchTerm . '%');
            });
        }

        // Apply filters
        if ($request->filled('coordinator_id')) {
            $coordinatorId = $request->get('coordinator_id');
            $descendantIds = $this->getAllDescendantUserIds(collect([$coordinatorId]));
            $projectsFromCoordinatorsQuery = ProjectQueryService::getProjectsForUsersQuery($descendantIds);
        }

        if ($request->filled('province')) {
            $projectsFromCoordinatorsQuery->whereHas('user', function($q) use ($request) {
                $q->where('province', $request->province);
            });
            $projectsFromDirectTeamQuery->whereHas('user', function($q) use ($request) {
                $q->where('province', $request->province);
            });
        }

        if ($request->filled('center')) {
            $projectsFromDirectTeamQuery->whereHas('user', function($q) use ($request) {
                $q->where('center', $request->center);
            });
        }

        if ($request->filled('project_type')) {
            $projectsFromCoordinatorsQuery->where('project_type', $request->project_type);
            $projectsFromDirectTeamQuery->where('project_type', $request->project_type);
        }

        if ($request->filled('status')) {
            $projectsFromCoordinatorsQuery->where('status', $request->status);
            $projectsFromDirectTeamQuery->where('status', $request->status);
        }

        // Multiple statuses filter (for General user statuses)
        if ($request->filled('statuses')) {
            $statusesArray = is_array($request->statuses)
                ? $request->statuses
                : explode(',', $request->statuses);
            $projectsFromCoordinatorsQuery->whereIn('status', $statusesArray);
            $projectsFromDirectTeamQuery->whereIn('status', $statusesArray);
        }

        // Get projects with relationships
        $projectsFromCoordinators = $projectsFromCoordinatorsQuery
            ->with(['user.parent', 'reports.accountDetails', 'budgets', 'comments.user'])
            ->get();

        $projectsFromDirectTeam = $projectsFromDirectTeamQuery
            ->with(['user', 'reports.accountDetails', 'budgets', 'comments.user'])
            ->get();

        // Combine projects and add source indicator
        $allProjects = $projectsFromCoordinators->map(function($project) {
            $project->source = 'coordinator_hierarchy';
            return $project;
        })->merge($projectsFromDirectTeam->map(function($project) {
            $project->source = 'direct_team';
            return $project;
        }));

        // Get total count before pagination
        $totalProjects = $allProjects->count();

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if (in_array($sortBy, ['created_at', 'project_id', 'project_title', 'status'])) {
            $allProjects = $sortOrder === 'asc'
                ? $allProjects->sortBy($sortBy)->values()
                : $allProjects->sortByDesc($sortBy)->values();
        }

        // Pagination
        $perPage = $request->get('per_page', 100);
        $currentPage = $request->get('page', 1);
        $paginatedProjects = $allProjects->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Get filter options
        $coordinators = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->select('id', 'name', 'province')
            ->get();

        $provinces = User::whereIn('id', $allUserIdsUnderCoordinators->merge($directTeamIds))
            ->distinct()
            ->whereNotNull('province')
            ->pluck('province')
            ->filter()
            ->sort()
            ->values();

        $centers = User::whereIn('id', $directTeamIds)
            ->whereNotNull('center')
            ->where('center', '!=', '')
            ->distinct()
            ->pluck('center')
            ->filter()
            ->sort()
            ->values();

        $projectTypes = Project::distinct()
            ->whereNotNull('project_type')
            ->pluck('project_type')
            ->filter()
            ->sort()
            ->values();

        $statuses = array_keys(Project::$statusLabels);

        $pagination = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $totalProjects,
            'last_page' => ceil($totalProjects / $perPage),
            'from' => (($currentPage - 1) * $perPage) + 1,
            'to' => min($currentPage * $perPage, $totalProjects),
        ];

        $projects = $paginatedProjects;

        return view('general.projects.index', compact(
            'projects',
            'coordinators',
            'provinces',
            'centers',
            'projectTypes',
            'statuses',
            'pagination'
        ));
    }

    /**
     * List all reports (combined: coordinator hierarchy + direct team)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function listReports(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can view reports.');
        }

        // Get coordinator IDs under general
        $coordinatorIds = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->pluck('id');

        // Get direct team IDs (executors/applicants directly under general)
        $directTeamIds = User::where('parent_id', $general->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->pluck('id');

        // Get all descendant user IDs under coordinators (recursive)
        $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

        // Get project IDs from coordinator hierarchy and direct team
        $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
            $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                  ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
        })->pluck('project_id');

        $directTeamProjectIds = Project::where(function($query) use ($directTeamIds) {
            $query->whereIn('user_id', $directTeamIds)
                  ->orWhereIn('in_charge', $directTeamIds);
        })->pluck('project_id');

        // Base query for reports from coordinator hierarchy
        $reportsFromCoordinatorsQuery = DPReport::whereIn('project_id', $coordinatorProjectIds);

        // Base query for reports from direct team
        $reportsFromDirectTeamQuery = DPReport::whereIn('project_id', $directTeamProjectIds);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $reportsFromCoordinatorsQuery->where(function($q) use ($searchTerm) {
                $q->where('report_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_id', 'like', '%' . $searchTerm . '%');
            });
            $reportsFromDirectTeamQuery->where(function($q) use ($searchTerm) {
                $q->where('report_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_id', 'like', '%' . $searchTerm . '%');
            });
        }

        // Apply filters
        if ($request->filled('province')) {
            $reportsFromCoordinatorsQuery->whereHas('user', function($q) use ($request) {
                $q->where('province', $request->province);
            });
            $reportsFromDirectTeamQuery->whereHas('user', function($q) use ($request) {
                $q->where('province', $request->province);
            });
        }

        if ($request->filled('center')) {
            $reportsFromDirectTeamQuery->whereHas('user', function($q) use ($request) {
                $q->where('center', $request->center);
            });
        }

        if ($request->filled('project_type')) {
            $reportsFromCoordinatorsQuery->where('project_type', $request->project_type);
            $reportsFromDirectTeamQuery->where('project_type', $request->project_type);
        }

        if ($request->filled('status')) {
            $reportsFromCoordinatorsQuery->where('status', $request->status);
            $reportsFromDirectTeamQuery->where('status', $request->status);
        }

        // Multiple statuses filter (for General user statuses)
        if ($request->filled('statuses')) {
            $statusesArray = is_array($request->statuses)
                ? $request->statuses
                : explode(',', $request->statuses);
            $reportsFromCoordinatorsQuery->whereIn('status', $statusesArray);
            $reportsFromDirectTeamQuery->whereIn('status', $statusesArray);
        }

        // Get reports with relationships
        $reportsFromCoordinators = $reportsFromCoordinatorsQuery
            ->with(['user.parent', 'project', 'comments.user'])
            ->get();

        $reportsFromDirectTeam = $reportsFromDirectTeamQuery
            ->with(['user', 'project', 'comments.user'])
            ->get();

        // Combine reports and add source indicator, calculate days pending
        $allReports = $reportsFromCoordinators->map(function($report) {
            $report->source = 'coordinator_hierarchy';
            if (in_array($report->status, [DPReport::STATUS_FORWARDED_TO_COORDINATOR, DPReport::STATUS_SUBMITTED_TO_PROVINCIAL])) {
                $report->days_pending = $report->created_at->diffInDays(now());
                $report->urgency = $report->days_pending > 7 ? 'urgent' : ($report->days_pending > 3 ? 'normal' : 'low');
            } else {
                $report->days_pending = null;
                $report->urgency = null;
            }
            return $report;
        })->merge($reportsFromDirectTeam->map(function($report) {
            $report->source = 'direct_team';
            if (in_array($report->status, [DPReport::STATUS_FORWARDED_TO_COORDINATOR, DPReport::STATUS_SUBMITTED_TO_PROVINCIAL])) {
                $report->days_pending = $report->created_at->diffInDays(now());
                $report->urgency = $report->days_pending > 7 ? 'urgent' : ($report->days_pending > 3 ? 'normal' : 'low');
            } else {
                $report->days_pending = null;
                $report->urgency = null;
            }
            return $report;
        }));

        // Apply urgency filter if specified
        if ($request->filled('urgency')) {
            $allReports = $allReports->filter(function($report) use ($request) {
                return $report->urgency === $request->urgency;
            })->values();
        }

        // Priority sorting: urgent first, then by days pending, then by created_at
        $allReports = $allReports->sortBy(function($report) {
            if ($report->urgency === 'urgent') {
                return [1, $report->days_pending ?? 999, $report->created_at->timestamp];
            } elseif ($report->urgency === 'normal') {
                return [2, $report->days_pending ?? 999, $report->created_at->timestamp];
            } else {
                return [3, $report->days_pending ?? 999, $report->created_at->timestamp];
            }
        })->values();

        // Get total count before pagination
        $totalReports = $allReports->count();

        // Pagination
        $perPage = $request->get('per_page', 100);
        $currentPage = $request->get('page', 1);
        $paginatedReports = $allReports->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Get filter options
        $coordinators = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->select('id', 'name', 'province')
            ->get();

        $provinces = User::whereIn('id', $allUserIdsUnderCoordinators->merge($directTeamIds))
            ->distinct()
            ->whereNotNull('province')
            ->pluck('province')
            ->filter()
            ->sort()
            ->values();

        $centers = User::whereIn('id', $directTeamIds)
            ->whereNotNull('center')
            ->where('center', '!=', '')
            ->distinct()
            ->pluck('center')
            ->filter()
            ->sort()
            ->values();

        $projectTypes = DPReport::distinct()
            ->whereNotNull('project_type')
            ->pluck('project_type')
            ->filter()
            ->sort()
            ->values();

        $statuses = array_keys(DPReport::$statusLabels);

        $pagination = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $totalReports,
            'last_page' => ceil($totalReports / $perPage),
            'from' => (($currentPage - 1) * $perPage) + 1,
            'to' => min($currentPage * $perPage, $totalReports),
        ];

        $reports = $paginatedReports;

        return view('general.reports.index', compact(
            'reports',
            'coordinators',
            'provinces',
            'centers',
            'projectTypes',
            'statuses',
            'pagination'
        ));
    }

    /**
     * Show project details (reuse CoordinatorController logic)
     *
     * @param string $project_id
     * @return \Illuminate\View\View
     */
    public function showProject($project_id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can view project details.');
        }

        $project = Project::where('project_id', $project_id)
            ->with(['user.parent', 'comments.user', 'activityHistory.changedBy'])
            ->firstOrFail();

        // General can view all projects (has complete coordinator access)
        // Additional authorization checks can be added if needed

        // Reuse the ProjectController show method
        return app(\App\Http\Controllers\Projects\ProjectController::class)->show($project_id);
    }

    /**
     * Show report details (reuse CoordinatorController logic)
     *
     * @param string $report_id
     * @return \Illuminate\View\View
     */
    public function showReport($report_id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can view report details.');
        }

        $report = DPReport::where('report_id', $report_id)
            ->with(['user.parent', 'comments.user', 'project'])
            ->firstOrFail();

        // General can view all reports (has complete coordinator access)
        // Additional authorization checks can be added if needed

        // Reuse the ReportController show method
        return app(\App\Http\Controllers\Reports\Monthly\ReportController::class)->show($report_id);
    }

    /**
     * List pending reports (combined: coordinator hierarchy + direct team)
     * Filters by pending statuses: forwarded_to_coordinator, submitted_to_provincial, reverted statuses
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function pendingReports(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can view pending reports.');
        }

        // Get coordinator IDs under general
        $coordinatorIds = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->pluck('id');

        // Get direct team IDs (executors/applicants directly under general)
        $directTeamIds = User::where('parent_id', $general->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->pluck('id');

        // Get all descendant user IDs under coordinators (recursive)
        $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

        // Get project IDs from coordinator hierarchy and direct team
        $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
            $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                  ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
        })->pluck('project_id');

        $directTeamProjectIds = Project::where(function($query) use ($directTeamIds) {
            $query->whereIn('user_id', $directTeamIds)
                  ->orWhereIn('in_charge', $directTeamIds);
        })->pluck('project_id');

        // Pending statuses for General user
        $pendingStatuses = [
            DPReport::STATUS_FORWARDED_TO_COORDINATOR,
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
            DPReport::STATUS_REVERTED_BY_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
            DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
            DPReport::STATUS_REVERTED_TO_PROVINCIAL,
            DPReport::STATUS_REVERTED_TO_COORDINATOR,
        ];

        // Base query for reports from coordinator hierarchy (pending statuses only)
        $reportsFromCoordinatorsQuery = DPReport::whereIn('project_id', $coordinatorProjectIds)
            ->whereIn('status', $pendingStatuses);

        // Base query for reports from direct team (pending statuses only)
        $reportsFromDirectTeamQuery = DPReport::whereIn('project_id', $directTeamProjectIds)
            ->whereIn('status', $pendingStatuses);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $reportsFromCoordinatorsQuery->where(function($q) use ($searchTerm) {
                $q->where('report_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_id', 'like', '%' . $searchTerm . '%');
            });
            $reportsFromDirectTeamQuery->where(function($q) use ($searchTerm) {
                $q->where('report_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_id', 'like', '%' . $searchTerm . '%');
            });
        }

        // Apply filters
        if ($request->filled('province')) {
            $reportsFromCoordinatorsQuery->whereHas('user', function($q) use ($request) {
                $q->where('province', $request->province);
            });
            $reportsFromDirectTeamQuery->whereHas('user', function($q) use ($request) {
                $q->where('province', $request->province);
            });
        }

        if ($request->filled('center')) {
            $reportsFromDirectTeamQuery->whereHas('user', function($q) use ($request) {
                $q->where('center', $request->center);
            });
        }

        if ($request->filled('project_type')) {
            $reportsFromCoordinatorsQuery->where('project_type', $request->project_type);
            $reportsFromDirectTeamQuery->where('project_type', $request->project_type);
        }

        if ($request->filled('status')) {
            $reportsFromCoordinatorsQuery->where('status', $request->status);
            $reportsFromDirectTeamQuery->where('status', $request->status);
        }

        // Urgency filter (for pending reports)
        $urgencyFilter = $request->filled('urgency') ? $request->urgency : null;

        // Get reports with relationships
        $reportsFromCoordinators = $reportsFromCoordinatorsQuery
            ->with(['user.parent', 'project', 'comments.user', 'accountDetails'])
            ->get();

        $reportsFromDirectTeam = $reportsFromDirectTeamQuery
            ->with(['user', 'project', 'comments.user', 'accountDetails'])
            ->get();

        // Combine reports and add source indicator, calculate days pending and urgency
        $allReports = $reportsFromCoordinators->map(function($report) {
            $report->source = 'coordinator_hierarchy';
            $report->days_pending = $report->created_at->diffInDays(now());
            $report->urgency = $report->days_pending > 7 ? 'urgent' : ($report->days_pending > 3 ? 'normal' : 'low');

            // Calculate budget totals
            $report->total_amount = $report->accountDetails->sum('total_amount');
            $report->total_expenses = $report->accountDetails->sum('total_expenses');
            $report->expenses_this_month = $report->accountDetails->sum('expenses_this_month');
            $report->balance_amount = $report->accountDetails->sum('balance_amount');

            return $report;
        })->merge($reportsFromDirectTeam->map(function($report) {
            $report->source = 'direct_team';
            $report->days_pending = $report->created_at->diffInDays(now());
            $report->urgency = $report->days_pending > 7 ? 'urgent' : ($report->days_pending > 3 ? 'normal' : 'low');

            // Calculate budget totals
            $report->total_amount = $report->accountDetails->sum('total_amount');
            $report->total_expenses = $report->accountDetails->sum('total_expenses');
            $report->expenses_this_month = $report->accountDetails->sum('expenses_this_month');
            $report->balance_amount = $report->accountDetails->sum('balance_amount');

            return $report;
        }));

        // Apply urgency filter if specified
        if ($urgencyFilter) {
            $allReports = $allReports->filter(function($report) use ($urgencyFilter) {
                return $report->urgency === $urgencyFilter;
            })->values();
        }

        // Sorting options
        $sortBy = $request->get('sort_by', 'urgency'); // Default: urgency
        $sortOrder = $request->get('sort_order', 'desc'); // Default: desc

        if ($sortBy === 'urgency') {
            $allReports = $allReports->sortBy(function($report) use ($sortOrder) {
                $urgencyScore = $report->urgency === 'urgent' ? 3 : ($report->urgency === 'normal' ? 2 : 1);
                return $sortOrder === 'desc' ? -$urgencyScore : $urgencyScore;
            })->values();
        } elseif ($sortBy === 'days_pending') {
            $allReports = $sortOrder === 'desc'
                ? $allReports->sortByDesc('days_pending')->values()
                : $allReports->sortBy('days_pending')->values();
        } elseif ($sortBy === 'created_at') {
            $allReports = $sortOrder === 'desc'
                ? $allReports->sortByDesc('created_at')->values()
                : $allReports->sortBy('created_at')->values();
        } elseif ($sortBy === 'report_id') {
            $allReports = $sortOrder === 'desc'
                ? $allReports->sortByDesc('report_id')->values()
                : $allReports->sortBy('report_id')->values();
        }

        // Get total count before pagination
        $totalReports = $allReports->count();

        // Pagination
        $perPage = $request->get('per_page', 50);
        $currentPage = $request->get('page', 1);
        $paginatedReports = $allReports->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Get filter options
        $coordinators = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->select('id', 'name', 'province')
            ->get();

        $provinces = User::whereIn('id', $allUserIdsUnderCoordinators->merge($directTeamIds))
            ->distinct()
            ->whereNotNull('province')
            ->pluck('province')
            ->filter()
            ->sort()
            ->values();

        $centers = User::whereIn('id', $directTeamIds)
            ->whereNotNull('center')
            ->where('center', '!=', '')
            ->distinct()
            ->pluck('center')
            ->filter()
            ->sort()
            ->values();

        $projectTypes = DPReport::whereIn('status', $pendingStatuses)
            ->distinct()
            ->whereNotNull('project_type')
            ->pluck('project_type')
            ->filter()
            ->sort()
            ->values();

        $statuses = $pendingStatuses;

        $pagination = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $totalReports,
            'last_page' => ceil($totalReports / $perPage),
            'from' => (($currentPage - 1) * $perPage) + 1,
            'to' => min($currentPage * $perPage, $totalReports),
        ];

        return view('general.reports.pending', compact(
            'reports',
            'coordinators',
            'provinces',
            'centers',
            'projectTypes',
            'statuses',
            'pagination',
            'urgencyFilter',
            'sortBy',
            'sortOrder'
        ))->with('reports', $paginatedReports);
    }

    /**
     * List approved reports (combined: coordinator hierarchy + direct team)
     * Filters by approved statuses with date range filtering
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function approvedReports(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can view approved reports.');
        }

        // Get coordinator IDs under general
        $coordinatorIds = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->pluck('id');

        // Get direct team IDs (executors/applicants directly under general)
        $directTeamIds = User::where('parent_id', $general->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->pluck('id');

        // Get all descendant user IDs under coordinators (recursive)
        $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

        // Get project IDs from coordinator hierarchy and direct team
        $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
            $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                  ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
        })->pluck('project_id');

        $directTeamProjectIds = Project::where(function($query) use ($directTeamIds) {
            $query->whereIn('user_id', $directTeamIds)
                  ->orWhereIn('in_charge', $directTeamIds);
        })->pluck('project_id');

        // Approved statuses for General user
        $approvedStatuses = [
            DPReport::STATUS_APPROVED_BY_COORDINATOR,
            DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR,
        ];

        // Base query for reports from coordinator hierarchy (approved statuses only)
        $reportsFromCoordinatorsQuery = DPReport::whereIn('project_id', $coordinatorProjectIds)
            ->whereIn('status', $approvedStatuses);

        // Base query for reports from direct team (approved statuses only)
        $reportsFromDirectTeamQuery = DPReport::whereIn('project_id', $directTeamProjectIds)
            ->whereIn('status', $approvedStatuses);

        // Date range filter
        if ($request->filled('start_date')) {
            $reportsFromCoordinatorsQuery->whereDate('updated_at', '>=', $request->start_date);
            $reportsFromDirectTeamQuery->whereDate('updated_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $reportsFromCoordinatorsQuery->whereDate('updated_at', '<=', $request->end_date);
            $reportsFromDirectTeamQuery->whereDate('updated_at', '<=', $request->end_date);
        }

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $reportsFromCoordinatorsQuery->where(function($q) use ($searchTerm) {
                $q->where('report_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_id', 'like', '%' . $searchTerm . '%');
            });
            $reportsFromDirectTeamQuery->where(function($q) use ($searchTerm) {
                $q->where('report_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_id', 'like', '%' . $searchTerm . '%');
            });
        }

        // Apply filters
        if ($request->filled('province')) {
            $reportsFromCoordinatorsQuery->whereHas('user', function($q) use ($request) {
                $q->where('province', $request->province);
            });
            $reportsFromDirectTeamQuery->whereHas('user', function($q) use ($request) {
                $q->where('province', $request->province);
            });
        }

        if ($request->filled('center')) {
            $reportsFromDirectTeamQuery->whereHas('user', function($q) use ($request) {
                $q->where('center', $request->center);
            });
        }

        if ($request->filled('project_type')) {
            $reportsFromCoordinatorsQuery->where('project_type', $request->project_type);
            $reportsFromDirectTeamQuery->where('project_type', $request->project_type);
        }

        if ($request->filled('status')) {
            $reportsFromCoordinatorsQuery->where('status', $request->status);
            $reportsFromDirectTeamQuery->where('status', $request->status);
        }

        // Get reports with relationships
        $reportsFromCoordinators = $reportsFromCoordinatorsQuery
            ->with(['user.parent', 'project', 'comments.user', 'accountDetails'])
            ->get();

        $reportsFromDirectTeam = $reportsFromDirectTeamQuery
            ->with(['user', 'project', 'comments.user', 'accountDetails'])
            ->get();

        // Combine reports and add source indicator, calculate budget totals
        $allReports = $reportsFromCoordinators->map(function($report) {
            $report->source = 'coordinator_hierarchy';

            // Calculate budget totals
            $report->total_amount = $report->accountDetails->sum('total_amount');
            $report->total_expenses = $report->accountDetails->sum('total_expenses');
            $report->expenses_this_month = $report->accountDetails->sum('expenses_this_month');
            $report->balance_amount = $report->accountDetails->sum('balance_amount');

            return $report;
        })->merge($reportsFromDirectTeam->map(function($report) {
            $report->source = 'direct_team';

            // Calculate budget totals
            $report->total_amount = $report->accountDetails->sum('total_amount');
            $report->total_expenses = $report->accountDetails->sum('total_expenses');
            $report->expenses_this_month = $report->accountDetails->sum('expenses_this_month');
            $report->balance_amount = $report->accountDetails->sum('balance_amount');

            return $report;
        }));

        // Calculate statistics
        $statistics = [
            'total_reports' => $allReports->count(),
            'total_amount' => $allReports->sum('total_amount'),
            'total_expenses' => $allReports->sum('total_expenses'),
            'total_expenses_this_month' => $allReports->sum('expenses_this_month'),
            'total_balance' => $allReports->sum('balance_amount'),
            'by_project_type' => $allReports->groupBy('project_type')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('total_amount'),
                    'total_expenses' => $group->sum('total_expenses'),
                ];
            }),
            'by_province' => $allReports->groupBy(function($report) {
                return $report->user->province ?? 'Unknown';
            })->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('total_amount'),
                    'total_expenses' => $group->sum('total_expenses'),
                ];
            }),
        ];

        // Sorting
        $sortBy = $request->get('sort_by', 'updated_at'); // Default: approval date
        $sortOrder = $request->get('sort_order', 'desc'); // Default: newest first

        if ($sortBy === 'updated_at') {
            $allReports = $sortOrder === 'desc'
                ? $allReports->sortByDesc('updated_at')->values()
                : $allReports->sortBy('updated_at')->values();
        } elseif ($sortBy === 'report_id') {
            $allReports = $sortOrder === 'desc'
                ? $allReports->sortByDesc('report_id')->values()
                : $allReports->sortBy('report_id')->values();
        } elseif ($sortBy === 'total_expenses') {
            $allReports = $sortOrder === 'desc'
                ? $allReports->sortByDesc('total_expenses')->values()
                : $allReports->sortBy('total_expenses')->values();
        }

        // Get total count before pagination
        $totalReports = $allReports->count();

        // Pagination
        $perPage = $request->get('per_page', 50);
        $currentPage = $request->get('page', 1);
        $paginatedReports = $allReports->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Get filter options
        $coordinators = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->select('id', 'name', 'province')
            ->get();

        $provinces = User::whereIn('id', $allUserIdsUnderCoordinators->merge($directTeamIds))
            ->distinct()
            ->whereNotNull('province')
            ->pluck('province')
            ->filter()
            ->sort()
            ->values();

        $centers = User::whereIn('id', $directTeamIds)
            ->whereNotNull('center')
            ->where('center', '!=', '')
            ->distinct()
            ->pluck('center')
            ->filter()
            ->sort()
            ->values();

        $projectTypes = DPReport::whereIn('status', $approvedStatuses)
            ->distinct()
            ->whereNotNull('project_type')
            ->pluck('project_type')
            ->filter()
            ->sort()
            ->values();

        $statuses = $approvedStatuses;

        $pagination = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $totalReports,
            'last_page' => ceil($totalReports / $perPage),
            'from' => (($currentPage - 1) * $perPage) + 1,
            'to' => min($currentPage * $perPage, $totalReports),
        ];

        // Handle export request
        if ($request->filled('export')) {
            $exportFormat = $request->get('export');
            if ($exportFormat === 'excel' || $exportFormat === 'pdf') {
                // For now, redirect to a simple message - export functionality can be implemented later
                // This can be enhanced to generate Excel/PDF files with the filtered reports
                return redirect()->back()->with('info', 'Export functionality is being implemented. For now, please use individual report downloads.');
            }
        }

        $reports = $paginatedReports;

        return view('general.reports.approved', compact(
            'reports',
            'statistics',
            'coordinators',
            'provinces',
            'centers',
            'projectTypes',
            'statuses',
            'pagination'
        ));
    }

    /**
     * Add comment to a project (without status change, logged in activity history)
     *
     * @param Request $request
     * @param string $project_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addProjectComment(Request $request, $project_id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can add project comments.');
        }

        $project = Project::where('project_id', $project_id)->firstOrFail();

        // Verify General has access to this project (either from coordinator hierarchy or direct team)
        // This is handled at route level or we can add additional checks if needed

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            $commentId = $project->generateProjectCommentId();

            ProjectComment::create([
                'project_comment_id' => $commentId,
                'project_id' => $project->project_id,
                'user_id' => $general->id,
                'comment' => $request->comment,
            ]);

            // Also log comment in activity history
            ActivityHistoryService::logProjectComment($project, $general, $request->comment);

            Log::info('Project comment added by General', [
                'general_id' => $general->id,
                'project_id' => $project->project_id,
                'comment_id' => $commentId,
            ]);

            return redirect()->back()->with('success', 'Comment added successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to add project comment by General', [
                'general_id' => $general->id,
                'project_id' => $project_id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withErrors('Failed to add comment: ' . $e->getMessage());
        }
    }

    /**
     * Edit a project comment
     *
     * @param string $id Comment ID
     * @return \Illuminate\View\View
     */
    public function editProjectComment($id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can edit project comments.');
        }

        $comment = ProjectComment::findOrFail($id);

        // Ensure the General user owns this comment
        if ($comment->user_id !== $general->id) {
            abort(403, 'Unauthorized. You can only edit your own comments.');
        }

        return view('projects.comments.edit', compact('comment'));
    }

    /**
     * List all project budgets (combined: coordinator hierarchy + direct team)
     * Shows projects with budget information, filtering, and export options
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function listBudgets(Request $request)
    {
        try {
            $general = Auth::user();

            if ($general->role !== 'general') {
                abort(403, 'Access denied. Only General users can view budgets.');
            }

        // Get coordinator IDs under general
        $coordinatorIds = User::where('parent_id', $general->id)
            ->where('role', 'coordinator')
            ->pluck('id');

        // Get direct team IDs (executors/applicants directly under general)
        $directTeamIds = User::where('parent_id', $general->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->pluck('id');

        // Get all descendant user IDs under coordinators (recursive)
        $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

        // Base query for projects from coordinator hierarchy (only approved projects)
        $projectsFromCoordinatorsQuery = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
            $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                  ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
        })->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);

        // Base query for projects from direct team (only approved projects)
        $projectsFromDirectTeamQuery = Project::where(function($query) use ($directTeamIds) {
            $query->whereIn('user_id', $directTeamIds)
                  ->orWhereIn('in_charge', $directTeamIds);
        })->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);

        // Apply context filter
        $context = $request->get('budget_context', 'combined');
        if ($context === 'coordinator_hierarchy') {
            $projectsFromDirectTeamQuery->whereRaw('1 = 0'); // Exclude direct team
        } elseif ($context === 'direct_team') {
            $projectsFromCoordinatorsQuery->whereRaw('1 = 0'); // Exclude coordinator hierarchy
        }

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $projectsFromCoordinatorsQuery->where(function($q) use ($searchTerm) {
                $q->where('project_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_type', 'like', '%' . $searchTerm . '%');
            });
            $projectsFromDirectTeamQuery->where(function($q) use ($searchTerm) {
                $q->where('project_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_type', 'like', '%' . $searchTerm . '%');
            });
        }

        // Coordinator filter
        if ($request->filled('coordinator_id') && ($context === 'coordinator_hierarchy' || $context === 'combined')) {
            $coordinatorId = $request->get('coordinator_id');
            $descendantIds = $this->getAllDescendantUserIds(collect([$coordinatorId]));
            $projectsFromCoordinatorsQuery->where(function($q) use ($descendantIds) {
                // Use ProjectQueryService for consistent query pattern
                $descendantProjectIds = ProjectQueryService::getProjectIdsForUsers($descendantIds);
                $q->whereIn('project_id', $descendantProjectIds);
            });
        }

        // Province filter
        if ($request->filled('province')) {
            if ($context === 'coordinator_hierarchy' || $context === 'combined') {
                $projectsFromCoordinatorsQuery->whereHas('user', function($q) use ($request) {
                    $q->where('province', $request->province);
                });
            }
            if ($context === 'direct_team' || $context === 'combined') {
                $projectsFromDirectTeamQuery->whereHas('user', function($q) use ($request) {
                    $q->where('province', $request->province);
                });
            }
        }

        // Center filter
        if ($request->filled('center') && ($context === 'direct_team' || $context === 'combined')) {
            $projectsFromDirectTeamQuery->whereHas('user', function($q) use ($request) {
                $q->where('center', $request->center);
            });
        }

        // Project type filter
        if ($request->filled('project_type')) {
            $projectsFromCoordinatorsQuery->where('project_type', $request->project_type);
            $projectsFromDirectTeamQuery->where('project_type', $request->project_type);
        }

        // Get projects with relationships
        $projectsFromCoordinators = $projectsFromCoordinatorsQuery
            ->with(['user.parent', 'reports.accountDetails', 'budgets'])
            ->get();

        $projectsFromDirectTeam = $projectsFromDirectTeamQuery
            ->with(['user', 'reports.accountDetails', 'budgets'])
            ->get();

        // Optimize: Get all project IDs first to batch query expenses (avoid N+1 queries)
        $allProjectIds = $projectsFromCoordinators->pluck('project_id')
            ->merge($projectsFromDirectTeam->pluck('project_id'))->unique();

        $coordinatorProjectIds = $projectsFromCoordinators->pluck('project_id');
        $directTeamProjectIds = $projectsFromDirectTeam->pluck('project_id');

        // Get report IDs for approved reports (batch query to avoid N+1)
        $approvedReportIds = DPReport::whereIn('project_id', $allProjectIds)
            ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
            ->pluck('report_id');

        // Batch fetch approved expenses for all projects at once (optimize N+1 queries)
        $approvedExpensesByProject = collect();
        if ($approvedReportIds->isNotEmpty()) {
            $approvedExpensesByProject = DPAccountDetail::whereIn('report_id', $approvedReportIds)
                ->selectRaw('project_id, SUM(CAST(total_expenses AS DECIMAL(15,2))) as total_expenses')
                ->groupBy('project_id')
                ->get()
                ->pluck('total_expenses', 'project_id')
                ->map(function($value) {
                    return (float)($value ?? 0);
                });
        }

        // Get report IDs for unapproved reports (coordinator hierarchy)
        $unapprovedReportIdsCoordinator = DPReport::whereIn('project_id', $coordinatorProjectIds)
            ->where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)
            ->pluck('report_id');

        // Batch fetch unapproved expenses for coordinator hierarchy projects
        $unapprovedExpensesCoordinator = collect();
        if ($unapprovedReportIdsCoordinator->isNotEmpty()) {
            $unapprovedExpensesCoordinator = DPAccountDetail::whereIn('report_id', $unapprovedReportIdsCoordinator)
                ->selectRaw('project_id, SUM(CAST(total_expenses AS DECIMAL(15,2))) as total_expenses')
                ->groupBy('project_id')
                ->get()
                ->pluck('total_expenses', 'project_id')
                ->map(function($value) {
                    return (float)($value ?? 0);
                });
        }

        // Get report IDs for unapproved reports (direct team)
        $unapprovedReportIdsDirectTeam = DPReport::whereIn('project_id', $directTeamProjectIds)
            ->where('status', DPReport::STATUS_SUBMITTED_TO_PROVINCIAL)
            ->pluck('report_id');

        // Batch fetch unapproved expenses for direct team projects
        $unapprovedExpensesDirectTeam = collect();
        if ($unapprovedReportIdsDirectTeam->isNotEmpty()) {
            $unapprovedExpensesDirectTeam = DPAccountDetail::whereIn('report_id', $unapprovedReportIdsDirectTeam)
                ->selectRaw('project_id, SUM(CAST(total_expenses AS DECIMAL(15,2))) as total_expenses')
                ->groupBy('project_id')
                ->get()
                ->pluck('total_expenses', 'project_id')
                ->map(function($value) {
                    return (float)($value ?? 0);
                });
        }

        $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
        $calc = app(\App\Services\Budget\DerivedCalculationService::class);
        // Calculate budget information for each project (optimized)
        $allProjects = $projectsFromCoordinators->map(function($project) use ($approvedExpensesByProject, $unapprovedExpensesCoordinator, $resolver, $calc) {
            $project->source = 'coordinator_hierarchy';

            $financials = $resolver->resolve($project);
            $projectBudget = (float) ($financials['opening_balance'] ?? 0);

            // Get expenses from pre-fetched data (avoid N+1 queries)
            $totalExpenses = (float)($approvedExpensesByProject[$project->project_id] ?? 0);
            $unapprovedExpenses = (float)($unapprovedExpensesCoordinator[$project->project_id] ?? 0);

            $remainingBudget = $calc->calculateRemainingBalance($projectBudget, $totalExpenses);
            $budgetUtilization = $calc->calculateUtilization($totalExpenses, $projectBudget);

            $project->calculated_budget = $projectBudget;
            $project->calculated_expenses = $totalExpenses;
            $project->calculated_unapproved_expenses = $unapprovedExpenses;
            $project->calculated_remaining = $remainingBudget;
            $project->calculated_utilization = round($budgetUtilization, 2);

            // Health indicator
            $project->health_indicator = 'good';
            if ($budgetUtilization >= 90) {
                $project->health_indicator = 'critical';
            } elseif ($budgetUtilization >= 75) {
                $project->health_indicator = 'warning';
            } elseif ($budgetUtilization >= 50) {
                $project->health_indicator = 'moderate';
            }

            return $project;
        })->merge($projectsFromDirectTeam->map(function($project) use ($approvedExpensesByProject, $unapprovedExpensesDirectTeam, $resolver, $calc) {
            $project->source = 'direct_team';

            $financials = $resolver->resolve($project);
            $projectBudget = (float) ($financials['opening_balance'] ?? 0);

            // Get expenses from pre-fetched data (avoid N+1 queries)
            $totalExpenses = (float)($approvedExpensesByProject[$project->project_id] ?? 0);
            $unapprovedExpenses = (float)($unapprovedExpensesDirectTeam[$project->project_id] ?? 0);

            $remainingBudget = $calc->calculateRemainingBalance($projectBudget, $totalExpenses);
            $budgetUtilization = $calc->calculateUtilization($totalExpenses, $projectBudget);

            $project->calculated_budget = $projectBudget;
            $project->calculated_expenses = $totalExpenses;
            $project->calculated_unapproved_expenses = $unapprovedExpenses;
            $project->calculated_remaining = $remainingBudget;
            $project->calculated_utilization = round($budgetUtilization, 2);

            // Health indicator
            $project->health_indicator = 'good';
            if ($budgetUtilization >= 90) {
                $project->health_indicator = 'critical';
            } elseif ($budgetUtilization >= 75) {
                $project->health_indicator = 'warning';
            } elseif ($budgetUtilization >= 50) {
                $project->health_indicator = 'moderate';
            }

            return $project;
        }));

        // Get total count before pagination
        $totalProjects = $allProjects->count();

        // Apply sorting
        $sortBy = $request->get('sort_by', 'project_id');
        $sortOrder = $request->get('sort_order', 'asc');

        if (in_array($sortBy, ['project_id', 'project_title', 'project_type', 'calculated_budget', 'calculated_expenses', 'calculated_remaining', 'calculated_utilization'])) {
            $allProjects = $sortOrder === 'asc'
                ? $allProjects->sortBy($sortBy)->values()
                : $allProjects->sortByDesc($sortBy)->values();
        }

        // Pagination
        $perPage = $request->get('per_page', 50);
        $currentPage = $request->get('page', 1);
        $paginatedProjects = $allProjects->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Calculate summary statistics
        $summary = [
            'total_projects' => $totalProjects,
            'total_budget' => $allProjects->sum('calculated_budget'),
            'total_expenses' => $allProjects->sum('calculated_expenses'),
            'total_unapproved_expenses' => $allProjects->sum('calculated_unapproved_expenses'),
            'total_remaining' => $allProjects->sum('calculated_remaining'),
            'avg_utilization' => $allProjects->count() > 0 ? round($allProjects->avg('calculated_utilization'), 2) : 0,
        ];

        // Cache filter options for 5 minutes (same pattern as CoordinatorController)
        $filterCacheKey = 'general_budget_list_filters_' . $general->id;
        $filterOptions = Cache::remember($filterCacheKey, now()->addMinutes(5), function () use ($general, $allUserIdsUnderCoordinators, $directTeamIds) {
            return [
                'coordinators' => User::where('parent_id', $general->id)
                    ->where('role', 'coordinator')
                    ->select('id', 'name', 'province')
                    ->get(),
                'provinces' => User::whereIn('id', $allUserIdsUnderCoordinators->merge($directTeamIds))
                    ->distinct()
                    ->whereNotNull('province')
                    ->pluck('province')
                    ->filter()
                    ->sort()
                    ->values(),
                'centers' => User::whereIn('id', $directTeamIds)
                    ->whereNotNull('center')
                    ->where('center', '!=', '')
                    ->distinct()
                    ->pluck('center')
                    ->filter()
                    ->sort()
                    ->values(),
                'projectTypes' => Project::where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
                    ->distinct()
                    ->whereNotNull('project_type')
                    ->pluck('project_type')
                    ->filter()
                    ->sort()
                    ->values(),
            ];
        });

        $coordinators = $filterOptions['coordinators'];
        $provinces = $filterOptions['provinces'];
        $centers = $filterOptions['centers'];
        $projectTypes = $filterOptions['projectTypes'];

        $pagination = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $totalProjects,
            'last_page' => ceil($totalProjects / $perPage),
            'from' => (($currentPage - 1) * $perPage) + 1,
            'to' => min($currentPage * $perPage, $totalProjects),
        ];

        $projects = $paginatedProjects;

        return view('general.budgets.index', compact(
            'projects',
            'summary',
            'coordinators',
            'provinces',
            'centers',
            'projectTypes',
            'pagination',
            'context'
        ));
        } catch (\Exception $e) {
            $generalId = Auth::check() ? Auth::id() : null;
            Log::error('Error in GeneralController@listBudgets', [
                'general_id' => $generalId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return redirect()->route('general.dashboard')
                ->withErrors(['error' => 'Failed to load budget list. Please try again or contact support if the issue persists.']);
        }
    }

    /**
     * Update a project comment
     *
     * @param Request $request
     * @param string $id Comment ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProjectComment(Request $request, $id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can update project comments.');
        }

        $comment = ProjectComment::findOrFail($id);

        // Ensure the General user owns this comment
        if ($comment->user_id !== $general->id) {
            abort(403, 'Unauthorized. You can only update your own comments.');
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            $comment->update([
                'comment' => $request->comment,
            ]);

            // Log update in activity history
            $project = Project::where('project_id', $comment->project_id)->first();
            if ($project) {
                ActivityHistoryService::logProjectUpdate($project, $general, 'Project comment updated: ' . substr($request->comment, 0, 100));
            }

            Log::info('Project comment updated by General', [
                'general_id' => $general->id,
                'comment_id' => $id,
            ]);

            return redirect()->back()->with('success', 'Comment updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update project comment by General', [
                'general_id' => $general->id,
                'comment_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withErrors('Failed to update comment: ' . $e->getMessage());
        }
    }

    /**
     * Add comment to a report (without status change, logged in activity history)
     *
     * @param Request $request
     * @param string $report_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addReportComment(Request $request, $report_id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can add report comments.');
        }

        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        // Verify General has access to this report (either from coordinator hierarchy or direct team)
        // This is handled at route level or we can add additional checks if needed

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            $commentId = $report->generateCommentId();

            ReportComment::create([
                'R_comment_id' => $commentId,
                'report_id' => $report->report_id,
                'user_id' => $general->id,
                'comment' => $request->comment,
            ]);

            // Also log comment in activity history
            ActivityHistoryService::logReportComment($report, $general, $request->comment);

            Log::info('Report comment added by General', [
                'general_id' => $general->id,
                'report_id' => $report->report_id,
                'comment_id' => $commentId,
            ]);

            return redirect()->back()->with('success', 'Comment added successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to add report comment by General', [
                'general_id' => $general->id,
                'report_id' => $report_id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withErrors('Failed to add comment: ' . $e->getMessage());
        }
    }

    /**
     * Approve project as Coordinator or Provincial (with context selection)
     *
     * @param Request $request
     * @param string $project_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveProject(Request $request, $project_id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can approve projects.');
        }

        $project = Project::where('project_id', $project_id)->with('budgets')->firstOrFail();

        // Get approval context (coordinator or provincial)
        $approvalContext = $request->input('approval_context', 'coordinator'); // Default to coordinator

        if (!in_array($approvalContext, ['coordinator', 'provincial'])) {
            return redirect()->back()->withErrors('Invalid approval context. Must be "coordinator" or "provincial".');
        }

        try {
            if ($approvalContext === 'coordinator') {
                // Phase 2: Sync project-level budget fields before approval so validation/computation see correct data
                app(BudgetSyncService::class)->syncBeforeApproval($project);
                $project->refresh();
                // Approve as Coordinator - requires commencement date and budget validation
                $request->validate([
                    'commencement_month' => 'required|integer|min:1|max:12',
                    'commencement_year' => 'required|integer|min:2000|max:2100',
                ]);

                // Validate commencement date is not in the past
                $month = $request->input('commencement_month');
                $year = $request->input('commencement_year');
                $commencementDate = Carbon::create($year, $month, 1)->startOfMonth();
                $currentDate = Carbon::now()->startOfMonth();

                if ($commencementDate->isBefore($currentDate)) {
                    return redirect()->back()
                        ->withErrors(['commencement_date' => 'Commencement Month & Year cannot be before the current month and year.'])
                        ->withInput();
                }

                // Update commencement date before approval
                $project->commencement_month = $month;
                $project->commencement_year = $year;
                $project->commencement_month_year = $commencementDate->format('Y-m-d');
                $project->save();

                // Use General-specific service method
                ProjectStatusService::approveAsCoordinator($project, $general);

                // Budget validation and persistence (values from resolver)
                $financials = app(\App\Domain\Budget\ProjectFinancialResolver::class)->resolve($project);
                $overallBudget = (float) ($financials['overall_project_budget'] ?? 0);
                $amountForwarded = (float) ($financials['amount_forwarded'] ?? 0);
                $localContribution = (float) ($financials['local_contribution'] ?? 0);
                $combinedContribution = $amountForwarded + $localContribution;
                $amountSanctioned = (float) ($financials['amount_sanctioned'] ?? 0);
                $openingBalance = (float) ($financials['opening_balance'] ?? 0);

                // Validate: combined contribution cannot exceed overall budget
                if ($combinedContribution > $overallBudget) {
                    // Revert the approval if budget validation fails
                    $project->status = ProjectStatus::FORWARDED_TO_COORDINATOR;
                    $project->save();

                    return redirect()->back()
                        ->with('error', 'Cannot approve project: (Amount Forwarded + Local Contribution) of Rs. ' . number_format($combinedContribution, 2) . ' exceeds Overall Project Budget (Rs. ' . number_format($overallBudget, 2) . '). Please ask the executor to correct this.')
                        ->withInput();
                }

                $project->amount_sanctioned = $amountSanctioned;
                $project->opening_balance = $openingBalance;
                $project->save();

                Log::info('Project approved by General as Coordinator', [
                    'project_id' => $project->project_id,
                    'general_id' => $general->id,
                    'commencement_month' => $month,
                    'commencement_year' => $year,
                ]);

                return redirect()->back()->with('success',
                    'Project approved successfully as Coordinator.<br>' .
                    '<strong>Budget Summary:</strong><br>' .
                    'Overall Budget: Rs. ' . number_format($overallBudget, 2) . '<br>' .
                    'Amount Sanctioned: Rs. ' . number_format($amountSanctioned, 2) . '<br>' .
                    'Opening Balance: Rs. ' . number_format($openingBalance, 2) . '<br>' .
                    '<strong>Commencement Date:</strong> ' . date('F Y', mktime(0, 0, 0, $month, 1, $year))
                );
            } else {
                // Approve as Provincial - forwards to coordinator (no commencement date needed)
                ProjectStatusService::approveAsProvincial($project, $general);

                Log::info('Project approved/forwarded by General as Provincial', [
                    'project_id' => $project->project_id,
                    'general_id' => $general->id,
                ]);

                return redirect()->back()->with('success', 'Project forwarded to Coordinator successfully (approved as Provincial).');
            }
        } catch (\Exception $e) {
            Log::error('Failed to approve project by General', [
                'general_id' => $general->id,
                'project_id' => $project_id,
                'approval_context' => $approvalContext,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Revert project with context selection (Coordinator or Provincial) and level selection
     *
     * @param Request $request
     * @param string $project_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revertProject(Request $request, $project_id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can revert projects.');
        }

        $project = Project::where('project_id', $project_id)->firstOrFail();

        $request->validate([
            'revert_reason' => 'required|string|max:1000',
            'approval_context' => 'required|in:coordinator,provincial',
            'revert_level' => 'nullable|in:executor,applicant,provincial,coordinator',
            'reverted_to_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $approvalContext = $request->input('approval_context');
        $revertLevel = $request->input('revert_level');
        $revertReason = $request->input('revert_reason');
        $revertedToUserId = $request->input('reverted_to_user_id');

        try {
            if ($approvalContext === 'coordinator') {
                // Revert as Coordinator
                ProjectStatusService::revertAsCoordinator($project, $general, $revertReason, $revertLevel, $revertedToUserId);

                Log::info('Project reverted by General as Coordinator', [
                    'project_id' => $project->project_id,
                    'general_id' => $general->id,
                    'revert_level' => $revertLevel,
                ]);

                $levelMessage = $revertLevel ? " (to {$revertLevel})" : "";
                return redirect()->back()->with('success', 'Project reverted as Coordinator' . $levelMessage . '.');
            } else {
                // Revert as Provincial
                ProjectStatusService::revertAsProvincial($project, $general, $revertReason, $revertLevel, $revertedToUserId);

                Log::info('Project reverted by General as Provincial', [
                    'project_id' => $project->project_id,
                    'general_id' => $general->id,
                    'revert_level' => $revertLevel,
                ]);

                $levelMessage = $revertLevel ? " (to {$revertLevel})" : "";
                return redirect()->back()->with('success', 'Project reverted as Provincial' . $levelMessage . '.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to revert project by General', [
                'general_id' => $general->id,
                'project_id' => $project_id,
                'approval_context' => $approvalContext,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Revert project to a specific level (granular revert)
     *
     * @param Request $request
     * @param string $project_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revertProjectToLevel(Request $request, $project_id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can revert projects to specific levels.');
        }

        $project = Project::where('project_id', $project_id)->firstOrFail();

        $request->validate([
            'revert_level' => 'required|in:executor,applicant,provincial,coordinator',
            'revert_reason' => 'required|string|max:1000',
            'reverted_to_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $revertLevel = $request->input('revert_level');
        $revertReason = $request->input('revert_reason');
        $revertedToUserId = $request->input('reverted_to_user_id');

        try {
            ProjectStatusService::revertToLevel($project, $general, $revertLevel, $revertReason, $revertedToUserId);

            Log::info('Project reverted to specific level by General', [
                'project_id' => $project->project_id,
                'general_id' => $general->id,
                'revert_level' => $revertLevel,
                'reverted_to_user_id' => $revertedToUserId,
            ]);

            return redirect()->back()->with('success', "Project reverted to {$revertLevel} successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to revert project to level by General', [
                'general_id' => $general->id,
                'project_id' => $project_id,
                'revert_level' => $revertLevel,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Approve report as Coordinator or Provincial (with context selection)
     *
     * @param Request $request
     * @param string $report_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveReport(Request $request, $report_id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can approve reports.');
        }

        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        // Get approval context (coordinator or provincial)
        $approvalContext = $request->input('approval_context', 'coordinator'); // Default to coordinator

        if (!in_array($approvalContext, ['coordinator', 'provincial'])) {
            return redirect()->back()->withErrors('Invalid approval context. Must be "coordinator" or "provincial".');
        }

        try {
            if ($approvalContext === 'coordinator') {
                // Approve as Coordinator
                ReportStatusService::approveAsCoordinator($report, $general);

                Log::info('Report approved by General as Coordinator', [
                    'report_id' => $report->report_id,
                    'general_id' => $general->id,
                ]);

                return redirect()->back()->with('success', 'Report approved successfully as Coordinator.');
            } else {
                // Approve as Provincial - forwards to coordinator
                ReportStatusService::approveAsProvincial($report, $general);

                Log::info('Report approved/forwarded by General as Provincial', [
                    'report_id' => $report->report_id,
                    'general_id' => $general->id,
                ]);

                return redirect()->back()->with('success', 'Report forwarded to Coordinator successfully (approved as Provincial).');
            }
        } catch (\Exception $e) {
            Log::error('Failed to approve report by General', [
                'general_id' => $general->id,
                'report_id' => $report_id,
                'approval_context' => $approvalContext,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Revert report with context selection (Coordinator or Provincial) and level selection
     *
     * @param Request $request
     * @param string $report_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revertReport(Request $request, $report_id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can revert reports.');
        }

        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        $request->validate([
            'revert_reason' => 'required|string|max:1000',
            'approval_context' => 'required|in:coordinator,provincial',
            'revert_level' => 'nullable|in:executor,applicant,provincial,coordinator',
            'reverted_to_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $approvalContext = $request->input('approval_context');
        $revertLevel = $request->input('revert_level');
        $revertReason = $request->input('revert_reason');
        $revertedToUserId = $request->input('reverted_to_user_id');

        try {
            if ($approvalContext === 'coordinator') {
                // Revert as Coordinator
                ReportStatusService::revertAsCoordinator($report, $general, $revertReason, $revertLevel, $revertedToUserId);

                Log::info('Report reverted by General as Coordinator', [
                    'report_id' => $report->report_id,
                    'general_id' => $general->id,
                    'revert_level' => $revertLevel,
                ]);

                $levelMessage = $revertLevel ? " (to {$revertLevel})" : "";
                return redirect()->back()->with('success', 'Report reverted as Coordinator' . $levelMessage . '.');
            } else {
                // Revert as Provincial
                ReportStatusService::revertAsProvincial($report, $general, $revertReason, $revertLevel, $revertedToUserId);

                Log::info('Report reverted by General as Provincial', [
                    'report_id' => $report->report_id,
                    'general_id' => $general->id,
                    'revert_level' => $revertLevel,
                ]);

                $levelMessage = $revertLevel ? " (to {$revertLevel})" : "";
                return redirect()->back()->with('success', 'Report reverted as Provincial' . $levelMessage . '.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to revert report by General', [
                'general_id' => $general->id,
                'report_id' => $report_id,
                'approval_context' => $approvalContext,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Revert report to a specific level (granular revert)
     *
     * @param Request $request
     * @param string $report_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revertReportToLevel(Request $request, $report_id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can revert reports to specific levels.');
        }

        $report = DPReport::where('report_id', $report_id)->firstOrFail();

        $request->validate([
            'revert_level' => 'required|in:executor,applicant,provincial,coordinator',
            'revert_reason' => 'required|string|max:1000',
            'reverted_to_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $revertLevel = $request->input('revert_level');
        $revertReason = $request->input('revert_reason');
        $revertedToUserId = $request->input('reverted_to_user_id');

        try {
            ReportStatusService::revertToLevel($report, $general, $revertLevel, $revertReason, $revertedToUserId);

            Log::info('Report reverted to specific level by General', [
                'report_id' => $report->report_id,
                'general_id' => $general->id,
                'revert_level' => $revertLevel,
                'reverted_to_user_id' => $revertedToUserId,
            ]);

            return redirect()->back()->with('success', "Report reverted to {$revertLevel} successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to revert report to level by General', [
                'general_id' => $general->id,
                'report_id' => $report_id,
                'revert_level' => $revertLevel,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Edit a report comment
     *
     * @param string $id Comment ID
     * @return \Illuminate\View\View
     */
    public function editReportComment($id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can edit report comments.');
        }

        $comment = ReportComment::findOrFail($id);

        // Ensure the General user owns this comment
        if ($comment->user_id !== $general->id) {
            abort(403, 'Unauthorized. You can only edit your own comments.');
        }

        return view('reports.comments.edit', compact('comment'));
    }

    /**
     * Update a report comment
     *
     * @param Request $request
     * @param string $id Comment ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateReportComment(Request $request, $id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can update report comments.');
        }

        $comment = ReportComment::findOrFail($id);

        // Ensure the General user owns this comment
        if ($comment->user_id !== $general->id) {
            abort(403, 'Unauthorized. You can only update your own comments.');
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            $comment->update([
                'comment' => $request->comment,
            ]);

            // Log update in activity history
            $report = DPReport::where('report_id', $comment->report_id)->first();
            if ($report) {
                ActivityHistoryService::logReportUpdate($report, $general, 'Report comment updated: ' . substr($request->comment, 0, 100));
            }

            Log::info('Report comment updated by General', [
                'general_id' => $general->id,
                'comment_id' => $id,
            ]);

            return redirect()->back()->with('success', 'Comment updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update report comment by General', [
                'general_id' => $general->id,
                'comment_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withErrors('Failed to update comment: ' . $e->getMessage());
        }
    }

    /**
     * Handle bulk actions for reports (approve, export, etc.)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkActionReports(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can perform bulk actions.');
        }

        $request->validate([
            'report_ids' => 'required|array|min:1',
            'report_ids.*' => 'required|string',
            'bulk_action' => 'required|in:approve_as_coordinator,approve_as_provincial,export',
        ]);

        $reportIds = $request->input('report_ids', []);
        $bulkAction = $request->input('bulk_action');

        try {
            $successCount = 0;
            $failedCount = 0;

            foreach ($reportIds as $reportId) {
                try {
                    $report = DPReport::where('report_id', $reportId)->firstOrFail();

                    // Verify General has access to this report
                    // (This check can be enhanced to verify coordinator hierarchy or direct team access)

                    if ($bulkAction === 'approve_as_coordinator') {
                        ReportStatusService::approveAsCoordinator($report, $general);
                        $successCount++;
                    } elseif ($bulkAction === 'approve_as_provincial') {
                        ReportStatusService::approveAsProvincial($report, $general);
                        $successCount++;
                    } elseif ($bulkAction === 'export') {
                        // Export will be handled separately via export route
                        // This can trigger a batch export download
                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Bulk action failed for report', [
                        'report_id' => $reportId,
                        'action' => $bulkAction,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($bulkAction === 'export') {
                // Redirect to export with selected report IDs
                return redirect()->route('general.reports.pending', [
                    'export' => 'excel',
                    'report_ids' => $reportIds,
                ])->with('success', "Export initiated for {$successCount} report(s).");
            }

            $message = "Bulk action completed: {$successCount} report(s) processed successfully.";
            if ($failedCount > 0) {
                $message .= " {$failedCount} report(s) failed.";
            }

            return redirect()->route('general.reports.pending')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Bulk action failed', [
                'general_id' => $general->id,
                'action' => $bulkAction,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withErrors('Bulk action failed: ' . $e->getMessage());
        }
    }

    // getAllDescendantUserIds is now available from HandlesAuthorization trait via base Controller

    /**
     * Get pending approvals data for widget (with caching - 5 minutes TTL)
     * Returns pending items from both coordinator hierarchy and direct team contexts
     */
    private function getPendingApprovalsData()
    {
        $general = Auth::user();
        $cacheKey = 'general_pending_approvals_data';

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($general) {
            // Get coordinator IDs under general
            $coordinatorIds = User::where('parent_id', $general->id)
                ->where('role', 'coordinator')
                ->pluck('id');

            // Get direct team IDs (executors/applicants directly under general)
            $directTeamIds = User::where('parent_id', $general->id)
                ->whereIn('role', ['executor', 'applicant'])
                ->pluck('id');

            // Get all descendant user IDs under coordinators (recursive)
            $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

            // Get project IDs from coordinator hierarchy
            $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
                $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                      ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
            })->pluck('project_id');

            // Get project IDs from direct team
            $directTeamProjectIds = Project::where(function($query) use ($directTeamIds) {
                $query->whereIn('user_id', $directTeamIds)
                      ->orWhereIn('in_charge', $directTeamIds);
            })->pluck('project_id');

            // COORDINATOR HIERARCHY: Pending Projects (forwarded to coordinator)
            $coordinatorHierarchyPendingProjects = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
                $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                      ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
            })
            ->where('status', ProjectStatus::FORWARDED_TO_COORDINATOR)
            ->with(['user', 'user.parent'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($project) {
                $project->days_pending = $project->created_at->diffInDays(now());
                $project->urgency = $project->days_pending > 7 ? 'urgent' :
                                    ($project->days_pending > 3 ? 'normal' : 'low');
                $project->context = 'coordinator_hierarchy';
                $project->provincial = $project->user->parent;
                return $project;
            })
            ->sortByDesc(function($project) {
                return [
                    $project->urgency === 'urgent' ? 3 : ($project->urgency === 'normal' ? 2 : 1),
                    $project->days_pending
                ];
            })
            ->values();

            // COORDINATOR HIERARCHY: Pending Reports (forwarded to coordinator)
            $coordinatorHierarchyPendingReports = DPReport::whereIn('project_id', $coordinatorProjectIds)
            ->where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)
            ->with(['user', 'user.parent', 'project'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($report) {
                $report->days_pending = $report->created_at->diffInDays(now());
                $report->urgency = $report->days_pending > 7 ? 'urgent' :
                                   ($report->days_pending > 3 ? 'normal' : 'low');
                $report->context = 'coordinator_hierarchy';
                $report->provincial = $report->user->parent;
                return $report;
            })
            ->sortByDesc(function($report) {
                return [
                    $report->urgency === 'urgent' ? 3 : ($report->urgency === 'normal' ? 2 : 1),
                    $report->days_pending
                ];
            })
            ->values();

            // DIRECT TEAM: Pending Projects (submitted to provincial)
            $directTeamPendingProjects = Project::where(function($query) use ($directTeamIds) {
                $query->whereIn('user_id', $directTeamIds)
                      ->orWhereIn('in_charge', $directTeamIds);
            })
            ->where('status', ProjectStatus::SUBMITTED_TO_PROVINCIAL)
            ->with(['user'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($project) {
                $project->days_pending = $project->created_at->diffInDays(now());
                $project->urgency = $project->days_pending > 7 ? 'urgent' :
                                    ($project->days_pending > 3 ? 'normal' : 'low');
                $project->context = 'direct_team';
                return $project;
            })
            ->sortByDesc(function($project) {
                return [
                    $project->urgency === 'urgent' ? 3 : ($project->urgency === 'normal' ? 2 : 1),
                    $project->days_pending
                ];
            })
            ->values();

            // DIRECT TEAM: Pending Reports (submitted to provincial)
            $directTeamPendingReports = DPReport::whereIn('project_id', $directTeamProjectIds)
            ->where('status', DPReport::STATUS_SUBMITTED_TO_PROVINCIAL)
            ->with(['user', 'project'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($report) {
                $report->days_pending = $report->created_at->diffInDays(now());
                $report->urgency = $report->days_pending > 7 ? 'urgent' :
                                   ($report->days_pending > 3 ? 'normal' : 'low');
                $report->context = 'direct_team';
                return $report;
            })
            ->sortByDesc(function($report) {
                return [
                    $report->urgency === 'urgent' ? 3 : ($report->urgency === 'normal' ? 2 : 1),
                    $report->days_pending
                ];
            })
            ->values();

            // Combined (all pending)
            $allPendingProjects = $coordinatorHierarchyPendingProjects->merge($directTeamPendingProjects);
            $allPendingReports = $coordinatorHierarchyPendingReports->merge($directTeamPendingReports);

            // Calculate counts
            $coordinatorHierarchyProjectsCount = $coordinatorHierarchyPendingProjects->count();
            $coordinatorHierarchyReportsCount = $coordinatorHierarchyPendingReports->count();
            $directTeamProjectsCount = $directTeamPendingProjects->count();
            $directTeamReportsCount = $directTeamPendingReports->count();

            $totalPendingCount = $allPendingProjects->count() + $allPendingReports->count();

            // Urgency counts by context
            $coordHierarchyUrgentProjects = $coordinatorHierarchyPendingProjects->where('urgency', 'urgent')->count();
            $coordHierarchyNormalProjects = $coordinatorHierarchyPendingProjects->where('urgency', 'normal')->count();
            $coordHierarchyUrgentReports = $coordinatorHierarchyPendingReports->where('urgency', 'urgent')->count();
            $coordHierarchyNormalReports = $coordinatorHierarchyPendingReports->where('urgency', 'normal')->count();

            $directTeamUrgentProjects = $directTeamPendingProjects->where('urgency', 'urgent')->count();
            $directTeamNormalProjects = $directTeamPendingProjects->where('urgency', 'normal')->count();
            $directTeamUrgentReports = $directTeamPendingReports->where('urgency', 'urgent')->count();
            $directTeamNormalReports = $directTeamPendingReports->where('urgency', 'normal')->count();

            $totalUrgentCount = $coordHierarchyUrgentProjects + $coordHierarchyUrgentReports + $directTeamUrgentProjects + $directTeamUrgentReports;
            $totalNormalCount = $coordHierarchyNormalProjects + $coordHierarchyNormalReports + $directTeamNormalProjects + $directTeamNormalReports;

            return [
                // Coordinator Hierarchy
                'coordinator_hierarchy' => [
                    'pending_projects' => $coordinatorHierarchyPendingProjects,
                    'pending_reports' => $coordinatorHierarchyPendingReports,
                    'projects_count' => $coordinatorHierarchyProjectsCount,
                    'reports_count' => $coordinatorHierarchyReportsCount,
                    'total_count' => $coordinatorHierarchyProjectsCount + $coordinatorHierarchyReportsCount,
                    'urgent_projects' => $coordHierarchyUrgentProjects,
                    'normal_projects' => $coordHierarchyNormalProjects,
                    'urgent_reports' => $coordHierarchyUrgentReports,
                    'normal_reports' => $coordHierarchyNormalReports,
                ],
                // Direct Team
                'direct_team' => [
                    'pending_projects' => $directTeamPendingProjects,
                    'pending_reports' => $directTeamPendingReports,
                    'projects_count' => $directTeamProjectsCount,
                    'reports_count' => $directTeamReportsCount,
                    'total_count' => $directTeamProjectsCount + $directTeamReportsCount,
                    'urgent_projects' => $directTeamUrgentProjects,
                    'normal_projects' => $directTeamNormalProjects,
                    'urgent_reports' => $directTeamUrgentReports,
                    'normal_reports' => $directTeamNormalReports,
                ],
                // All (Combined)
                'all' => [
                    'pending_projects' => $allPendingProjects,
                    'pending_reports' => $allPendingReports,
                    'projects_count' => $allPendingProjects->count(),
                    'reports_count' => $allPendingReports->count(),
                    'total_count' => $totalPendingCount,
                    'urgent_projects' => $coordHierarchyUrgentProjects + $directTeamUrgentProjects,
                    'normal_projects' => $coordHierarchyNormalProjects + $directTeamNormalProjects,
                    'urgent_reports' => $coordHierarchyUrgentReports + $directTeamUrgentReports,
                    'normal_reports' => $coordHierarchyNormalReports + $directTeamNormalReports,
                ],
                // Summary
                'total_pending' => $totalPendingCount,
                'total_urgent' => $totalUrgentCount,
                'total_normal' => $totalNormalCount,
            ];
        });
    }

    /**
     * Get coordinator overview data for widget (with caching - 10 minutes TTL)
     * Returns statistics and list of coordinators under General user
     */
    private function getCoordinatorOverviewData()
    {
        $general = Auth::user();
        $cacheKey = 'general_coordinator_overview_data';

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($general) {
            // Get all coordinators under General
            $coordinators = User::where('parent_id', $general->id)
                ->where('role', 'coordinator')
                ->get()
                ->map(function($coordinator) {
                    // Get all descendant user IDs under this coordinator (recursive)
                    $allUserIdsUnderCoordinator = $this->getAllDescendantUserIds(collect([$coordinator->id]));

                    // Get project IDs from coordinator's hierarchy
                    $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinator) {
                        $query->whereIn('user_id', $allUserIdsUnderCoordinator)
                              ->orWhereIn('in_charge', $allUserIdsUnderCoordinator);
                    })->pluck('project_id');

                    // Team members count (all users under coordinator: provincials + executors/applicants)
                    $coordinator->team_members_count = $allUserIdsUnderCoordinator->count();

                    // Projects count (approved by coordinator)
                    $coordinator->projects_count = Project::where(function($query) use ($allUserIdsUnderCoordinator) {
                        $query->whereIn('user_id', $allUserIdsUnderCoordinator)
                              ->orWhereIn('in_charge', $allUserIdsUnderCoordinator);
                    })
                    ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
                    ->count();

                    // Pending projects count
                    $coordinator->pending_projects_count = Project::where(function($query) use ($allUserIdsUnderCoordinator) {
                        $query->whereIn('user_id', $allUserIdsUnderCoordinator)
                              ->orWhereIn('in_charge', $allUserIdsUnderCoordinator);
                    })
                    ->where('status', ProjectStatus::FORWARDED_TO_COORDINATOR)
                    ->count();

                    // Pending reports count
                    $coordinator->pending_reports_count = DPReport::whereIn('project_id', $coordinatorProjectIds)
                        ->where('status', DPReport::STATUS_FORWARDED_TO_COORDINATOR)
                        ->count();

                    // Approved reports count
                    $coordinator->approved_reports_count = DPReport::whereIn('project_id', $coordinatorProjectIds)
                        ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                        ->count();

                    // Get last activity (latest report submission or project update from coordinator hierarchy)
                    $latestReport = DPReport::whereIn('project_id', $coordinatorProjectIds)
                        ->orderBy('created_at', 'desc')
                        ->select('created_at')
                        ->first();

                    $latestProject = Project::where(function($query) use ($allUserIdsUnderCoordinator) {
                        $query->whereIn('user_id', $allUserIdsUnderCoordinator)
                              ->orWhereIn('in_charge', $allUserIdsUnderCoordinator);
                    })
                    ->orderBy('updated_at', 'desc')
                    ->select('updated_at')
                    ->first();

                    $coordinator->last_activity = null;
                    if ($latestReport && $latestProject) {
                        $coordinator->last_activity = $latestReport->created_at > $latestProject->updated_at
                            ? $latestReport->created_at
                            : $latestProject->updated_at;
                    } elseif ($latestReport) {
                        $coordinator->last_activity = $latestReport->created_at;
                    } elseif ($latestProject) {
                        $coordinator->last_activity = $latestProject->updated_at;
                    }

                    return $coordinator;
                });

            // Calculate summary statistics
            $totalCoordinators = $coordinators->count();
            $activeCoordinators = $coordinators->where('status', 'active')->count();
            $inactiveCoordinators = $coordinators->where('status', 'inactive')->count();
            $coordinatorsWithPending = $coordinators->filter(function($coord) {
                return ($coord->pending_projects_count ?? 0) > 0 || ($coord->pending_reports_count ?? 0) > 0;
            })->count();

            $totalTeamMembers = $coordinators->sum('team_members_count');
            $totalProjects = $coordinators->sum('projects_count');
            $totalPendingProjects = $coordinators->sum('pending_projects_count');
            $totalPendingReports = $coordinators->sum('pending_reports_count');
            $totalApprovedReports = $coordinators->sum('approved_reports_count');

            $averageTeamSize = $totalCoordinators > 0 ? round($totalTeamMembers / $totalCoordinators, 1) : 0;

            return [
                'coordinators' => $coordinators->take(12), // Show top 12 in widget
                'total_coordinators' => $totalCoordinators,
                'active_coordinators' => $activeCoordinators,
                'inactive_coordinators' => $inactiveCoordinators,
                'coordinators_with_pending' => $coordinatorsWithPending,
                'total_team_members' => $totalTeamMembers,
                'total_projects' => $totalProjects,
                'total_pending_projects' => $totalPendingProjects,
                'total_pending_reports' => $totalPendingReports,
                'total_approved_reports' => $totalApprovedReports,
                'average_team_size' => $averageTeamSize,
            ];
        });
    }

    /**
     * Get direct team overview data for widget (with caching - 10 minutes TTL)
     * Returns statistics and list of executors/applicants directly under General user
     */
    private function getDirectTeamOverviewData()
    {
        $general = Auth::user();
        $cacheKey = 'general_direct_team_overview_data';

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($general) {
            // Get all executors/applicants directly under General
            $teamMembers = User::where('parent_id', $general->id)
                ->whereIn('role', ['executor', 'applicant'])
                ->withCount([
                    'projects' => function($query) {
                        $query->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
                    },
                    'reports' => function($query) {
                        $query->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);
                    }
                ])
                ->get()
                ->map(function($member) {
                    // Get project IDs for this member
                    $memberProjectIds = Project::where(function($query) use ($member) {
                        $query->where('user_id', $member->id)
                              ->orWhere('in_charge', $member->id);
                    })->pluck('project_id');

                    // Pending projects count
                    $member->pending_projects_count = Project::where(function($query) use ($member) {
                        $query->where('user_id', $member->id)
                              ->orWhere('in_charge', $member->id);
                    })
                    ->where('status', ProjectStatus::SUBMITTED_TO_PROVINCIAL)
                    ->count();

                    // Pending reports count
                    $member->pending_reports_count = DPReport::whereIn('project_id', $memberProjectIds)
                        ->where('status', DPReport::STATUS_SUBMITTED_TO_PROVINCIAL)
                        ->count();

                    // Get last activity (latest report submission or project update)
                    $latestReport = DPReport::whereIn('project_id', $memberProjectIds)
                        ->orderBy('created_at', 'desc')
                        ->select('created_at')
                        ->first();

                    $latestProject = Project::where(function($query) use ($member) {
                        $query->where('user_id', $member->id)
                              ->orWhere('in_charge', $member->id);
                    })
                    ->orderBy('updated_at', 'desc')
                    ->select('updated_at')
                    ->first();

                    $member->last_activity = null;
                    if ($latestReport && $latestProject) {
                        $member->last_activity = $latestReport->created_at > $latestProject->updated_at
                            ? $latestReport->created_at
                            : $latestProject->updated_at;
                    } elseif ($latestReport) {
                        $member->last_activity = $latestReport->created_at;
                    } elseif ($latestProject) {
                        $member->last_activity = $latestProject->updated_at;
                    }

                    return $member;
                });

            // Calculate summary statistics
            $totalMembers = $teamMembers->count();
            $activeMembers = $teamMembers->where('status', 'active')->count();
            $inactiveMembers = $teamMembers->where('status', 'inactive')->count();
            $membersWithPending = $teamMembers->filter(function($member) {
                return ($member->pending_projects_count ?? 0) > 0 || ($member->pending_reports_count ?? 0) > 0;
            })->count();

            $totalProjects = $teamMembers->sum('projects_count');
            $totalPendingProjects = $teamMembers->sum('pending_projects_count');
            $totalPendingReports = $teamMembers->sum('pending_reports_count');
            $totalApprovedReports = $teamMembers->sum('reports_count');

            $averageProjectsPerMember = $totalMembers > 0 ? round($totalProjects / $totalMembers, 1) : 0;

            return [
                'team_members' => $teamMembers->take(12), // Show top 12 in widget
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'inactive_members' => $inactiveMembers,
                'members_with_pending' => $membersWithPending,
                'total_projects' => $totalProjects,
                'total_pending_projects' => $totalPendingProjects,
                'total_pending_reports' => $totalPendingReports,
                'total_approved_reports' => $totalApprovedReports,
                'average_projects_per_member' => $averageProjectsPerMember,
            ];
        });
    }

    /**
     * Get unified budget overview data for widget (with caching - 15 minutes TTL with filter hash)
     * Returns budget data for coordinator hierarchy, direct team, and combined view
     */
    private function getBudgetOverviewData($request = null)
    {
        $general = Auth::user();
        // Build cache key based on context and filters
        $context = $request ? ($request->get('budget_context', 'combined') ?? 'combined') : 'combined';
        $filterHash = md5(json_encode($request ? $request->only(['province', 'center', 'project_type', 'coordinator_id', 'budget_context']) : []));
        $cacheKey = "general_budget_overview_data_{$context}_{$filterHash}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($general, $request) {
            $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
            $calc = app(\App\Services\Budget\DerivedCalculationService::class);

            // Get coordinator IDs and direct team IDs
            $coordinatorIds = User::where('parent_id', $general->id)
                ->where('role', 'coordinator')
                ->pluck('id');

            $directTeamIds = User::where('parent_id', $general->id)
                ->whereIn('role', ['executor', 'applicant'])
                ->pluck('id');

            // Get all descendant user IDs under coordinators (recursive)
            $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

            // Get project IDs from coordinator hierarchy
            $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
                $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                      ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
            })->pluck('project_id');

            // Get project IDs from direct team
            $directTeamProjectIds = Project::where(function($query) use ($directTeamIds) {
                $query->whereIn('user_id', $directTeamIds)
                      ->orWhereIn('in_charge', $directTeamIds);
            })->pluck('project_id');

            $context = $request ? ($request->get('budget_context', 'combined') ?? 'combined') : 'combined';

            // Helper function to calculate budget data for a set of projects
            $calculateBudgetData = function($projects, $projectIds, $reportStatusApproved, $reportStatusUnapproved, $resolvedFinancials, $calc) {
                $totalBudget = $projects->sum(
                    fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0)
                );

                // Calculate approved expenses (from approved reports)
                $approvedReportIds = DPReport::where('status', $reportStatusApproved)
                    ->whereIn('project_id', $projectIds)
                    ->pluck('report_id');

                $approvedExpenses = DPAccountDetail::whereIn('report_id', $approvedReportIds)
                    ->sum('total_expenses') ?? 0;

                // Calculate unapproved expenses (from pending reports - in pipeline)
                $unapprovedReportIds = DPReport::where('status', $reportStatusUnapproved)
                    ->whereIn('project_id', $projectIds)
                    ->pluck('report_id');

                $unapprovedExpenses = DPAccountDetail::whereIn('report_id', $unapprovedReportIds)
                    ->sum('total_expenses') ?? 0;

                $totalRemaining = $calc->calculateRemainingBalance($totalBudget, $approvedExpenses);
                $utilization = $calc->calculateUtilization($approvedExpenses, $totalBudget);

                return [
                    'budget' => $totalBudget,
                    'approved_expenses' => $approvedExpenses,
                    'unapproved_expenses' => $unapprovedExpenses,
                    'remaining' => $totalRemaining,
                    'utilization' => $utilization,
                    'projects' => $projects,
                    'project_ids' => $projectIds,
                    'approved_report_ids' => $approvedReportIds,
                    'unapproved_report_ids' => $unapprovedReportIds,
                ];
            };

            // Helper function to get breakdown by project type
            $getBreakdownByProjectType = function($projects, $approvedReportIds, $unapprovedReportIds, $resolvedFinancials, $calc) {
                $breakdown = [];
                foreach ($projects->groupBy('project_type') as $type => $typeProjects) {
                    $typeProjectIds = $typeProjects->pluck('project_id');
                    $typeBudget = $typeProjects->sum(
                        fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0)
                    );

                    $typeApprovedExpenses = DPAccountDetail::whereIn('report_id', $approvedReportIds)
                        ->whereHas('report', function($q) use ($typeProjectIds) {
                            $q->whereIn('project_id', $typeProjectIds);
                        })
                        ->sum('total_expenses') ?? 0;

                    $typeUnapprovedExpenses = DPAccountDetail::whereIn('report_id', $unapprovedReportIds)
                        ->whereHas('report', function($q) use ($typeProjectIds) {
                            $q->whereIn('project_id', $typeProjectIds);
                        })
                        ->sum('total_expenses') ?? 0;

                    $typeRemaining = $calc->calculateRemainingBalance($typeBudget, $typeApprovedExpenses);
                    $typeUtilization = $calc->calculateUtilization($typeApprovedExpenses, $typeBudget);

                    $breakdown[$type] = [
                        'budget' => $typeBudget,
                        'approved_expenses' => $typeApprovedExpenses,
                        'unapproved_expenses' => $typeUnapprovedExpenses,
                        'remaining' => $typeRemaining,
                        'utilization' => $typeUtilization,
                        'projects_count' => $typeProjects->count(),
                    ];
                }
                return $breakdown;
            };

            // COORDINATOR HIERARCHY BUDGET
            $coordinatorHierarchyProjectsQuery = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
                $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                      ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
            })
            ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
            ->with(['user.parent', 'user', 'reports.accountDetails', 'budgets']);

            // Apply filters for coordinator hierarchy
            if ($request && $request->filled('province') && ($context === 'coordinator_hierarchy' || $context === 'combined')) {
                $coordinatorHierarchyProjectsQuery->whereHas('user', function($q) use ($request) {
                    $q->where('province', $request->province);
                });
            }
            if ($request && $request->filled('coordinator_id') && ($context === 'coordinator_hierarchy' || $context === 'combined')) {
                $coordinatorId = $request->get('coordinator_id');
                $descendantIds = $this->getAllDescendantUserIds(collect([$coordinatorId]));
                $coordinatorHierarchyProjectsQuery->where(function($q) use ($descendantIds) {
                    // Use ProjectQueryService for consistent query pattern
                $descendantProjectIds = ProjectQueryService::getProjectIdsForUsers($descendantIds);
                $q->whereIn('project_id', $descendantProjectIds);
                });
            }
            if ($request && $request->filled('project_type') && ($context === 'coordinator_hierarchy' || $context === 'combined')) {
                $coordinatorHierarchyProjectsQuery->where('project_type', $request->project_type);
            }

            $coordinatorHierarchyProjects = $coordinatorHierarchyProjectsQuery->get();

            // DIRECT TEAM BUDGET
            $directTeamProjectsQuery = Project::where(function($query) use ($directTeamIds) {
                $query->whereIn('user_id', $directTeamIds)
                      ->orWhereIn('in_charge', $directTeamIds);
            })
            ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR) // Direct team projects approved by coordinator (General acting as Provincial)
            ->with(['user', 'reports.accountDetails', 'budgets']);

            // Apply filters for direct team
            if ($request && $request->filled('province') && ($context === 'direct_team' || $context === 'combined')) {
                $directTeamProjectsQuery->whereHas('user', function($q) use ($request) {
                    $q->where('province', $request->province);
                });
            }
            if ($request && $request->filled('center') && ($context === 'direct_team' || $context === 'combined')) {
                $directTeamProjectsQuery->whereHas('user', function($q) use ($request) {
                    $q->where('center', $request->center);
                });
            }
            if ($request && $request->filled('project_type') && ($context === 'direct_team' || $context === 'combined')) {
                $directTeamProjectsQuery->where('project_type', $request->project_type);
            }

            $directTeamProjects = $directTeamProjectsQuery->get();

            // Memoize resolved financials (resolve each project exactly once)
            $resolvedFinancials = [];
            foreach ($coordinatorHierarchyProjects as $project) {
                $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
            }
            foreach ($directTeamProjects as $project) {
                $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
            }

            $coordinatorHierarchyData = $calculateBudgetData(
                $coordinatorHierarchyProjects,
                $coordinatorHierarchyProjects->pluck('project_id'),
                DPReport::STATUS_APPROVED_BY_COORDINATOR,
                DPReport::STATUS_FORWARDED_TO_COORDINATOR,
                $resolvedFinancials,
                $calc
            );

            $directTeamData = $calculateBudgetData(
                $directTeamProjects,
                $directTeamProjects->pluck('project_id'),
                DPReport::STATUS_APPROVED_BY_COORDINATOR, // For direct team, approved reports are also approved by coordinator
                DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                $resolvedFinancials,
                $calc
            );

            // COMBINED BUDGET
            $combinedProjects = $coordinatorHierarchyProjects->merge($directTeamProjects);
            $combinedBudget = $coordinatorHierarchyData['budget'] + $directTeamData['budget'];
            $combinedApprovedExpenses = $coordinatorHierarchyData['approved_expenses'] + $directTeamData['approved_expenses'];
            $combinedData = [
                'budget' => $combinedBudget,
                'approved_expenses' => $combinedApprovedExpenses,
                'unapproved_expenses' => $coordinatorHierarchyData['unapproved_expenses'] + $directTeamData['unapproved_expenses'],
                'remaining' => $calc->calculateRemainingBalance($combinedBudget, $combinedApprovedExpenses),
                'utilization' => $calc->calculateUtilization($combinedApprovedExpenses, $combinedBudget),
                'projects' => $combinedProjects,
            ];

            // Get breakdowns
            $coordinatorHierarchyData['by_project_type'] = $getBreakdownByProjectType(
                $coordinatorHierarchyProjects,
                $coordinatorHierarchyData['approved_report_ids'],
                $coordinatorHierarchyData['unapproved_report_ids'],
                $resolvedFinancials,
                $calc
            );
            $directTeamData['by_project_type'] = $getBreakdownByProjectType(
                $directTeamProjects,
                $directTeamData['approved_report_ids'],
                $directTeamData['unapproved_report_ids'],
                $resolvedFinancials,
                $calc
            );
            $combinedData['by_project_type'] = [];
            foreach (array_merge(array_keys($coordinatorHierarchyData['by_project_type']), array_keys($directTeamData['by_project_type'])) as $type) {
                $combinedData['by_project_type'][$type] = [
                    'budget' => ($coordinatorHierarchyData['by_project_type'][$type]['budget'] ?? 0) + ($directTeamData['by_project_type'][$type]['budget'] ?? 0),
                    'approved_expenses' => ($coordinatorHierarchyData['by_project_type'][$type]['approved_expenses'] ?? 0) + ($directTeamData['by_project_type'][$type]['approved_expenses'] ?? 0),
                    'unapproved_expenses' => ($coordinatorHierarchyData['by_project_type'][$type]['unapproved_expenses'] ?? 0) + ($directTeamData['by_project_type'][$type]['unapproved_expenses'] ?? 0),
                    'remaining' => ($coordinatorHierarchyData['by_project_type'][$type]['remaining'] ?? 0) + ($directTeamData['by_project_type'][$type]['remaining'] ?? 0),
                    'utilization' => 0,
                    'projects_count' => ($coordinatorHierarchyData['by_project_type'][$type]['projects_count'] ?? 0) + ($directTeamData['by_project_type'][$type]['projects_count'] ?? 0),
                ];
                $typeBudget = $combinedData['by_project_type'][$type]['budget'];
                $combinedData['by_project_type'][$type]['utilization'] = $calc->calculateUtilization($combinedData['by_project_type'][$type]['approved_expenses'], $typeBudget);
            }

            // Budget by Province (Coordinator Hierarchy)
            $coordinatorHierarchyData['by_province'] = [];
            foreach ($coordinatorHierarchyProjects->groupBy(function($p) { return $p->user->province ?? 'Unknown'; }) as $province => $projects) {
                $provinceProjectIds = $projects->pluck('project_id');
                $provinceBudget = $projects->sum(
                    fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0)
                );
                $provinceApprovedExpenses = DPAccountDetail::whereIn('report_id', $coordinatorHierarchyData['approved_report_ids'])
                    ->whereHas('report', function($q) use ($provinceProjectIds) {
                        $q->whereIn('project_id', $provinceProjectIds);
                    })
                    ->sum('total_expenses') ?? 0;
                $coordinatorHierarchyData['by_province'][$province] = [
                    'budget' => $provinceBudget,
                    'approved_expenses' => $provinceApprovedExpenses,
                    'remaining' => $calc->calculateRemainingBalance($provinceBudget, $provinceApprovedExpenses),
                    'utilization' => $calc->calculateUtilization($provinceApprovedExpenses, $provinceBudget),
                    'projects_count' => $projects->count(),
                ];
            }

            // Budget by Center (Direct Team)
            $directTeamData['by_center'] = [];
            foreach ($directTeamProjects->groupBy(function($p) { return $p->user->center ?? 'Unknown'; }) as $center => $projects) {
                if (empty($center) || $center === 'Unknown') continue;
                $centerProjectIds = $projects->pluck('project_id');
                $centerBudget = $projects->sum(
                    fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0)
                );
                $centerApprovedExpenses = DPAccountDetail::whereIn('report_id', $directTeamData['approved_report_ids'])
                    ->whereHas('report', function($q) use ($centerProjectIds) {
                        $q->whereIn('project_id', $centerProjectIds);
                    })
                    ->sum('total_expenses') ?? 0;
                $directTeamData['by_center'][$center] = [
                    'budget' => $centerBudget,
                    'approved_expenses' => $centerApprovedExpenses,
                    'remaining' => $calc->calculateRemainingBalance($centerBudget, $centerApprovedExpenses),
                    'utilization' => $calc->calculateUtilization($centerApprovedExpenses, $centerBudget),
                    'projects_count' => $projects->count(),
                ];
            }

            // Budget by Coordinator (Coordinator Hierarchy)
            $coordinatorHierarchyData['by_coordinator'] = [];
            foreach ($coordinatorIds as $coordinatorId) {
                $coordinator = User::find($coordinatorId);
                if (!$coordinator) continue;
                $descendantIds = $this->getAllDescendantUserIds(collect([$coordinatorId]));
                $coordinatorProjects = $coordinatorHierarchyProjects->filter(function($p) use ($descendantIds) {
                    return in_array($p->user_id, $descendantIds->toArray()) || in_array($p->in_charge, $descendantIds->toArray());
                });
                if ($coordinatorProjects->isEmpty()) continue;
                $coordinatorProjectIds = $coordinatorProjects->pluck('project_id');
                $coordinatorBudget = $coordinatorProjects->sum(
                    fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0)
                );
                $coordinatorApprovedExpenses = DPAccountDetail::whereIn('report_id', $coordinatorHierarchyData['approved_report_ids'])
                    ->whereHas('report', function($q) use ($coordinatorProjectIds) {
                        $q->whereIn('project_id', $coordinatorProjectIds);
                    })
                    ->sum('total_expenses') ?? 0;
                $coordinatorHierarchyData['by_coordinator'][$coordinator->name] = [
                    'coordinator_id' => $coordinatorId,
                    'coordinator_name' => $coordinator->name,
                    'province' => $coordinator->province ?? 'Unknown',
                    'budget' => $coordinatorBudget,
                    'approved_expenses' => $coordinatorApprovedExpenses,
                    'remaining' => $calc->calculateRemainingBalance($coordinatorBudget, $coordinatorApprovedExpenses),
                    'utilization' => $calc->calculateUtilization($coordinatorApprovedExpenses, $coordinatorBudget),
                    'projects_count' => $coordinatorProjects->count(),
                ];
            }

            // Expense Trends Over Time (last 6 months) - Combined
            $expenseTrends = [];
            $current = now()->subMonths(6)->startOfMonth();
            while ($current <= now()) {
                $monthEnd = $current->copy()->endOfMonth();

                // Coordinator hierarchy expenses
                $coordMonthReportIds = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                    ->whereIn('project_id', $coordinatorHierarchyProjects->pluck('project_id'))
                    ->whereBetween('created_at', [$current->copy()->startOfMonth(), $monthEnd])
                    ->pluck('report_id');

                $coordMonthExpenses = DPAccountDetail::whereIn('report_id', $coordMonthReportIds)
                    ->sum('total_expenses') ?? 0;

                // Direct team expenses
                $directMonthReportIds = DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                    ->whereIn('project_id', $directTeamProjects->pluck('project_id'))
                    ->whereBetween('created_at', [$current->copy()->startOfMonth(), $monthEnd])
                    ->pluck('report_id');

                $directMonthExpenses = DPAccountDetail::whereIn('report_id', $directMonthReportIds)
                    ->sum('total_expenses') ?? 0;

                $expenseTrends[] = [
                    'month' => $current->format('M Y'),
                    'month_key' => $current->format('Y-m'),
                    'expenses' => $coordMonthExpenses + $directMonthExpenses,
                    'coordinator_hierarchy_expenses' => $coordMonthExpenses,
                    'direct_team_expenses' => $directMonthExpenses,
                ];

                $current->addMonth();
            }

            // Calculate moving averages (3-month) for expense trends
            $movingAverage = function($data, $period = 3) {
                $result = [];
                for ($i = 0; $i < count($data); $i++) {
                    if ($i < $period - 1) {
                        $result[] = null; // Not enough data for moving average
                    } else {
                        $sum = 0;
                        for ($j = $i - $period + 1; $j <= $i; $j++) {
                            $sum += is_array($data[$j]) ? ($data[$j]['expenses'] ?? 0) : $data[$j];
                        }
                        $result[] = round($sum / $period, 2);
                    }
                }
                return $result;
            };

            $expenseMovingAvg = count($expenseTrends) >= 3 ? $movingAverage($expenseTrends, 3) : [];
            $coordExpenseMovingAvg = count($expenseTrends) >= 3 ? $movingAverage(array_map(function($item) { return ['expenses' => $item['coordinator_hierarchy_expenses']]; }, $expenseTrends), 3) : [];
            $directExpenseMovingAvg = count($expenseTrends) >= 3 ? $movingAverage(array_map(function($item) { return ['expenses' => $item['direct_team_expenses']]; }, $expenseTrends), 3) : [];

            // Calculate trend indicators for expenses
            $expenseTrendIndicators = [];
            if (count($expenseTrends) >= 2) {
                $firstExpenses = $expenseTrends[0]['expenses'] ?? 0;
                $lastExpenses = $expenseTrends[count($expenseTrends) - 1]['expenses'] ?? 0;
                $change = $lastExpenses - $firstExpenses;
                $expenseTrendIndicators = [
                    'change' => $change,
                    'change_percent' => $firstExpenses > 0 ? round(($change / $firstExpenses) * 100, 2) : 0,
                    'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
                ];
            }

            return [
                'coordinator_hierarchy' => [
                    'total' => [
                        'budget' => $coordinatorHierarchyData['budget'],
                        'approved_expenses' => $coordinatorHierarchyData['approved_expenses'],
                        'unapproved_expenses' => $coordinatorHierarchyData['unapproved_expenses'],
                        'remaining' => $coordinatorHierarchyData['remaining'],
                        'utilization' => $coordinatorHierarchyData['utilization'],
                    ],
                    'by_project_type' => $coordinatorHierarchyData['by_project_type'],
                    'by_province' => $coordinatorHierarchyData['by_province'],
                    'by_coordinator' => $coordinatorHierarchyData['by_coordinator'],
                ],
                'direct_team' => [
                    'total' => [
                        'budget' => $directTeamData['budget'],
                        'approved_expenses' => $directTeamData['approved_expenses'],
                        'unapproved_expenses' => $directTeamData['unapproved_expenses'],
                        'remaining' => $directTeamData['remaining'],
                        'utilization' => $directTeamData['utilization'],
                    ],
                    'by_project_type' => $directTeamData['by_project_type'],
                    'by_center' => $directTeamData['by_center'],
                ],
                'combined' => [
                    'total' => [
                        'budget' => $combinedData['budget'],
                        'approved_expenses' => $combinedData['approved_expenses'],
                        'unapproved_expenses' => $combinedData['unapproved_expenses'],
                        'remaining' => $combinedData['remaining'],
                        'utilization' => $combinedData['utilization'],
                    ],
                    'by_project_type' => $combinedData['by_project_type'],
                ],
                'expense_trends' => $expenseTrends,
                'expense_moving_avg' => $expenseMovingAvg,
                'expense_moving_avg_coordinator' => $coordExpenseMovingAvg,
                'expense_moving_avg_direct_team' => $directExpenseMovingAvg,
                'expense_trend_indicators' => $expenseTrendIndicators,
                'budget_by_context' => [
                    'coordinator_hierarchy' => $coordinatorHierarchyData['budget'],
                    'direct_team' => $directTeamData['budget'],
                ],
            ];
        });
    }

    /**
     * Get system performance data for widget (with caching - 10 minutes TTL)
     * Returns performance metrics for both coordinator hierarchy and direct team with comparisons
     */
    private function getSystemPerformanceData()
    {
        $general = Auth::user();
        $cacheKey = 'general_system_performance_data';

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($general) {
            $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
            $calc = app(\App\Services\Budget\DerivedCalculationService::class);

            // Get coordinator IDs and direct team IDs
            $coordinatorIds = User::where('parent_id', $general->id)
                ->where('role', 'coordinator')
                ->pluck('id');

            $directTeamIds = User::where('parent_id', $general->id)
                ->whereIn('role', ['executor', 'applicant'])
                ->pluck('id');

            // Get all descendant user IDs under coordinators (recursive)
            $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

            // Get project IDs from coordinator hierarchy
            $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
                $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                      ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
            })->pluck('project_id');

            // Get project IDs from direct team
            $directTeamProjectIds = Project::where(function($query) use ($directTeamIds) {
                $query->whereIn('user_id', $directTeamIds)
                      ->orWhereIn('in_charge', $directTeamIds);
            })->pluck('project_id');

            // COORDINATOR HIERARCHY: Get projects and reports
            $coordinatorHierarchyProjects = Project::whereIn('project_id', $coordinatorProjectIds)
                ->with(['user'])->get();
            $coordinatorHierarchyReports = DPReport::whereIn('project_id', $coordinatorProjectIds)
                ->with(['user'])->get();

            // DIRECT TEAM: Get projects and reports
            $directTeamProjects = Project::whereIn('project_id', $directTeamProjectIds)
                ->with(['user'])->get();
            $directTeamReports = DPReport::whereIn('project_id', $directTeamProjectIds)
                ->with(['user'])->get();

            // Memoize resolved financials (resolve each project exactly once)
            $resolvedFinancials = [];
            foreach ($coordinatorHierarchyProjects->merge($directTeamProjects) as $project) {
                $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
            }

            // Helper function to calculate performance metrics
            $calculateMetrics = function($projects, $reports, $projectIds, $resolvedFinancials, $calc) {
                $totalProjects = $projects->count();
                $totalReports = $reports->count();

                // Approved projects count
                $approvedProjects = $projects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
                $approvedProjectsCount = $approvedProjects->count();
                $projectCompletionRate = $totalProjects > 0 ? ($approvedProjectsCount / $totalProjects) * 100 : 0;

                // Approved reports count
                $approvedReports = $reports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);
                $approvedReportsCount = $approvedReports->count();
                $approvalRate = $totalReports > 0 ? ($approvedReportsCount / $totalReports) * 100 : 0;

                // Average processing time (for approved reports)
                $avgProcessingTime = 0;
                if ($approvedReports->count() > 0) {
                    $totalDays = $approvedReports->sum(function($report) {
                        return $report->created_at->diffInDays(now());
                    });
                    $avgProcessingTime = round($totalDays / $approvedReports->count(), 1);
                }

                // Report submission rate (reports per month)
                $reportsLastMonth = $reports->whereBetween('created_at', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ])->count();
                $reportsThisMonth = $reports->whereBetween('created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ])->count();
                $submissionRate = $reportsLastMonth > 0 ? (($reportsThisMonth - $reportsLastMonth) / $reportsLastMonth) * 100 : 0;

                // Budget and expenses
                $totalBudget = $approvedProjects->sum(
                    fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0)
                );

                $approvedReportIds = $approvedReports->pluck('report_id');
                $totalExpenses = DPAccountDetail::whereIn('report_id', $approvedReportIds)
                    ->sum('total_expenses') ?? 0;

                $budgetUtilization = $calc->calculateUtilization($totalExpenses, $totalBudget);

                // Projects by status
                $projectsByStatus = $projects->groupBy('status')->map->count();

                // Reports by status
                $reportsByStatus = $reports->groupBy('status')->map->count();

                return [
                    'total_projects' => $totalProjects,
                    'total_reports' => $totalReports,
                    'approved_projects' => $approvedProjectsCount,
                    'approved_reports' => $approvedReportsCount,
                    'project_completion_rate' => round($projectCompletionRate, 2),
                    'approval_rate' => round($approvalRate, 2),
                    'avg_processing_time' => $avgProcessingTime,
                    'submission_rate' => round($submissionRate, 2),
                    'total_budget' => $totalBudget,
                    'total_expenses' => $totalExpenses,
                    'budget_utilization' => round($budgetUtilization, 2),
                    'projects_by_status' => $projectsByStatus,
                    'reports_by_status' => $reportsByStatus,
                ];
            };

            // Calculate metrics for both contexts
            $coordinatorHierarchyMetrics = $calculateMetrics(
                $coordinatorHierarchyProjects,
                $coordinatorHierarchyReports,
                $coordinatorProjectIds,
                $resolvedFinancials,
                $calc
            );

            $directTeamMetrics = $calculateMetrics(
                $directTeamProjects,
                $directTeamReports,
                $directTeamProjectIds,
                $resolvedFinancials,
                $calc
            );

            // Combined metrics
            $combinedProjects = $coordinatorHierarchyProjects->merge($directTeamProjects);
            $combinedReports = $coordinatorHierarchyReports->merge($directTeamReports);
            $combinedProjectIds = $coordinatorProjectIds->merge($directTeamProjectIds);
            $combinedMetrics = $calculateMetrics(
                $combinedProjects,
                $combinedReports,
                $combinedProjectIds,
                $resolvedFinancials,
                $calc
            );

            return [
                'coordinator_hierarchy' => $coordinatorHierarchyMetrics,
                'direct_team' => $directTeamMetrics,
                'combined' => $combinedMetrics,
            ];
        });
    }

    /**
     * Get system analytics data for charts (time-based) (with caching - 15 minutes TTL)
     * Returns analytics data with context filtering support
     */
    private function getSystemAnalyticsData($timeRange = 30, $context = 'combined')
    {
        $general = Auth::user();
        $cacheKey = "general_system_analytics_data_{$timeRange}_{$context}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($general, $timeRange, $context) {
            $endDate = now();
            $startDate = now()->subDays($timeRange);

            // Get coordinator IDs and direct team IDs
            $coordinatorIds = User::where('parent_id', $general->id)
                ->where('role', 'coordinator')
                ->pluck('id');

            $directTeamIds = User::where('parent_id', $general->id)
                ->whereIn('role', ['executor', 'applicant'])
                ->pluck('id');

            // Get all descendant user IDs under coordinators (recursive)
            $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

            // Get project IDs based on context
            if ($context === 'coordinator_hierarchy') {
                $projectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
                    $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                          ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
                })->pluck('project_id');
            } elseif ($context === 'direct_team') {
                $projectIds = Project::where(function($query) use ($directTeamIds) {
                    $query->whereIn('user_id', $directTeamIds)
                          ->orWhereIn('in_charge', $directTeamIds);
                })->pluck('project_id');
            } else {
                // Combined
                $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
                    $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                          ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
                })->pluck('project_id');
                $directTeamProjectIds = Project::where(function($query) use ($directTeamIds) {
                    $query->whereIn('user_id', $directTeamIds)
                          ->orWhereIn('in_charge', $directTeamIds);
                })->pluck('project_id');
                $projectIds = $coordinatorProjectIds->merge($directTeamProjectIds);
            }

            // Projects by Status (Pie Chart data)
            $projectsByStatus = [];
            $projects = Project::whereIn('project_id', $projectIds)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            foreach ($projects->groupBy('status') as $status => $statusProjects) {
                $projectsByStatus[$status] = $statusProjects->count();
            }

            // Reports by Status (Pie Chart data)
            $reportsByStatus = [];
            $reports = DPReport::whereIn('project_id', $projectIds)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            foreach ($reports->groupBy('status') as $status => $statusReports) {
                $reportsByStatus[$status] = $statusReports->count();
            }

            // Approval Rate Trends (Line Chart) - Monthly with context breakdown
            $approvalRateTrends = [];
            $approvalRateTrendsCoordinator = [];
            $approvalRateTrendsDirectTeam = [];
            $current = $startDate->copy()->startOfMonth();

            // If combined context, get separate data for comparison
            if ($context === 'combined') {
                $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
                    $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                          ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
                })->pluck('project_id');

                $directTeamProjectIds = Project::where(function($query) use ($directTeamIds) {
                    $query->whereIn('user_id', $directTeamIds)
                          ->orWhereIn('in_charge', $directTeamIds);
                })->pluck('project_id');

                $currentTemp = $startDate->copy()->startOfMonth();
                while ($currentTemp <= $endDate) {
                    $monthEnd = $currentTemp->copy()->endOfMonth();
                    $monthLabel = $currentTemp->format('M Y');

                    // Coordinator hierarchy data
                    $coordinatorReports = DPReport::whereIn('project_id', $coordinatorProjectIds)
                        ->whereBetween('created_at', [$currentTemp->copy()->startOfMonth(), $monthEnd])
                        ->get();
                    $coordinatorApproved = $coordinatorReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count();
                    $coordinatorRate = $coordinatorReports->count() > 0 ? ($coordinatorApproved / $coordinatorReports->count()) * 100 : 0;

                    // Direct team data
                    $directTeamReports = DPReport::whereIn('project_id', $directTeamProjectIds)
                        ->whereBetween('created_at', [$currentTemp->copy()->startOfMonth(), $monthEnd])
                        ->get();
                    $directTeamApproved = $directTeamReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count();
                    $directTeamRate = $directTeamReports->count() > 0 ? ($directTeamApproved / $directTeamReports->count()) * 100 : 0;

                    // Combined rate
                    $totalReports = $coordinatorReports->count() + $directTeamReports->count();
                    $totalApproved = $coordinatorApproved + $directTeamApproved;
                    $combinedRate = $totalReports > 0 ? ($totalApproved / $totalReports) * 100 : 0;

                    $approvalRateTrends[] = [
                        'month' => $monthLabel,
                        'approval_rate' => round($combinedRate, 2),
                    ];

                    $approvalRateTrendsCoordinator[] = round($coordinatorRate, 2);
                    $approvalRateTrendsDirectTeam[] = round($directTeamRate, 2);

                    $currentTemp->addMonth();
                }
            } else {
                while ($current <= $endDate) {
                    $monthEnd = $current->copy()->endOfMonth();

                    $monthReports = DPReport::whereIn('project_id', $projectIds)
                        ->whereBetween('created_at', [$current->copy()->startOfMonth(), $monthEnd])
                        ->get();

                    $monthApproved = $monthReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count();
                    $monthApprovalRate = $monthReports->count() > 0 ? ($monthApproved / $monthReports->count()) * 100 : 0;

                    $approvalRateTrends[] = [
                        'month' => $current->format('M Y'),
                        'approval_rate' => round($monthApprovalRate, 2),
                    ];

                    $current->addMonth();
                }
            }

            // Submission Rate Trends (Line Chart) - Monthly with context breakdown
            $submissionRateTrends = [];
            $submissionRateTrendsCoordinator = [];
            $submissionRateTrendsDirectTeam = [];
            $current = $startDate->copy()->startOfMonth();

            // If combined context, get separate data for comparison
            if ($context === 'combined') {
                $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
                    $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                          ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
                })->pluck('project_id');

                $directTeamProjectIds = Project::where(function($query) use ($directTeamIds) {
                    $query->whereIn('user_id', $directTeamIds)
                          ->orWhereIn('in_charge', $directTeamIds);
                })->pluck('project_id');

                $currentTemp = $startDate->copy()->startOfMonth();
                while ($currentTemp <= $endDate) {
                    $monthEnd = $currentTemp->copy()->endOfMonth();
                    $monthLabel = $currentTemp->format('M Y');

                    $coordinatorSubmissions = DPReport::whereIn('project_id', $coordinatorProjectIds)
                        ->whereBetween('created_at', [$currentTemp->copy()->startOfMonth(), $monthEnd])
                        ->count();

                    $directTeamSubmissions = DPReport::whereIn('project_id', $directTeamProjectIds)
                        ->whereBetween('created_at', [$currentTemp->copy()->startOfMonth(), $monthEnd])
                        ->count();

                    $submissionRateTrends[] = [
                        'month' => $monthLabel,
                        'submissions' => $coordinatorSubmissions + $directTeamSubmissions,
                    ];

                    $submissionRateTrendsCoordinator[] = $coordinatorSubmissions;
                    $submissionRateTrendsDirectTeam[] = $directTeamSubmissions;

                    $currentTemp->addMonth();
                }
            } else {
                while ($current <= $endDate) {
                    $monthEnd = $current->copy()->endOfMonth();

                    $monthReports = DPReport::whereIn('project_id', $projectIds)
                        ->whereBetween('created_at', [$current->copy()->startOfMonth(), $monthEnd])
                        ->count();

                    $submissionRateTrends[] = [
                        'month' => $current->format('M Y'),
                        'submissions' => $monthReports,
                    ];

                    $current->addMonth();
                }
            }

            // Calculate moving averages (3-month) for trend analysis
            $movingAverage = function($data, $period = 3) {
                $result = [];
                for ($i = 0; $i < count($data); $i++) {
                    if ($i < $period - 1) {
                        $result[] = null; // Not enough data for moving average
                    } else {
                        $sum = 0;
                        for ($j = $i - $period + 1; $j <= $i; $j++) {
                            $sum += is_array($data[$j]) ? $data[$j]['approval_rate'] ?? $data[$j]['submissions'] ?? 0 : $data[$j];
                        }
                        $result[] = round($sum / $period, 2);
                    }
                }
                return $result;
            };

            $approvalMovingAvg = count($approvalRateTrends) >= 3 ? $movingAverage($approvalRateTrends, 3) : [];
            $submissionMovingAvg = count($submissionRateTrends) >= 3 ? $movingAverage($submissionRateTrends, 3) : [];

            // Calculate trend indicators (change from first to last)
            $trendIndicators = [];
            if (count($approvalRateTrends) >= 2) {
                $firstRate = $approvalRateTrends[0]['approval_rate'] ?? 0;
                $lastRate = $approvalRateTrends[count($approvalRateTrends) - 1]['approval_rate'] ?? 0;
                $change = $lastRate - $firstRate;
                $trendIndicators['approval_rate'] = [
                    'change' => round($change, 2),
                    'change_percent' => $firstRate > 0 ? round(($change / $firstRate) * 100, 2) : 0,
                    'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
                ];
            }

            if (count($submissionRateTrends) >= 2) {
                $firstSubmissions = $submissionRateTrends[0]['submissions'] ?? 0;
                $lastSubmissions = $submissionRateTrends[count($submissionRateTrends) - 1]['submissions'] ?? 0;
                $change = $lastSubmissions - $firstSubmissions;
                $trendIndicators['submissions'] = [
                    'change' => $change,
                    'change_percent' => $firstSubmissions > 0 ? round(($change / $firstSubmissions) * 100, 2) : 0,
                    'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
                ];
            }

            return [
                'projects_by_status' => $projectsByStatus,
                'reports_by_status' => $reportsByStatus,
                'approval_rate_trends' => $approvalRateTrends,
                'approval_rate_trends_coordinator' => $approvalRateTrendsCoordinator,
                'approval_rate_trends_direct_team' => $approvalRateTrendsDirectTeam,
                'approval_moving_avg' => $approvalMovingAvg,
                'submission_rate_trends' => $submissionRateTrends,
                'submission_rate_trends_coordinator' => $submissionRateTrendsCoordinator,
                'submission_rate_trends_direct_team' => $submissionRateTrendsDirectTeam,
                'submission_moving_avg' => $submissionMovingAvg,
                'trend_indicators' => $trendIndicators,
            ];
        });
    }

    /**
     * Get context comparison data for widget (with caching - 10 minutes TTL)
     * Returns side-by-side comparison metrics between coordinator hierarchy and direct team
     */
    private function getContextComparisonData()
    {
        $general = Auth::user();
        $cacheKey = 'general_context_comparison_data';

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($general) {
            $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
            $calc = app(\App\Services\Budget\DerivedCalculationService::class);

            // Get coordinator IDs and direct team IDs
            $coordinatorIds = User::where('parent_id', $general->id)
                ->where('role', 'coordinator')
                ->pluck('id');

            $directTeamIds = User::where('parent_id', $general->id)
                ->whereIn('role', ['executor', 'applicant'])
                ->pluck('id');

            // Get all descendant user IDs under coordinators (recursive)
            $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

            // Get project IDs
            $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
                $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                      ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
            })->pluck('project_id');

            $directTeamProjectIds = Project::where(function($query) use ($directTeamIds) {
                $query->whereIn('user_id', $directTeamIds)
                      ->orWhereIn('in_charge', $directTeamIds);
            })->pluck('project_id');

            // Get projects and reports
            $coordinatorHierarchyProjects = Project::whereIn('project_id', $coordinatorProjectIds)->get();
            $coordinatorHierarchyReports = DPReport::whereIn('project_id', $coordinatorProjectIds)->get();

            $directTeamProjects = Project::whereIn('project_id', $directTeamProjectIds)->get();
            $directTeamReports = DPReport::whereIn('project_id', $directTeamProjectIds)->get();

            // Memoize resolved financials (resolve each project exactly once)
            $resolvedFinancials = [];
            foreach ($coordinatorHierarchyProjects as $project) {
                $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
            }
            foreach ($directTeamProjects as $project) {
                $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
            }

            $coordinatorApprovedProjects = $coordinatorHierarchyProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
            $directApprovedProjects = $directTeamProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);

            $coordinatorHierarchyBudget = $coordinatorApprovedProjects->sum(
                fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0)
            );

            $directTeamBudget = $directApprovedProjects->sum(
                fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0)
            );

            $coordinatorHierarchyApprovedReportIds = $coordinatorHierarchyReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->pluck('report_id');
            $coordinatorHierarchyExpenses = DPAccountDetail::whereIn('report_id', $coordinatorHierarchyApprovedReportIds)
                ->sum('total_expenses') ?? 0;

            $directTeamApprovedReportIds = $directTeamReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->pluck('report_id');
            $directTeamExpenses = DPAccountDetail::whereIn('report_id', $directTeamApprovedReportIds)
                ->sum('total_expenses') ?? 0;

            $coordinatorHierarchyApprovalRate = $coordinatorHierarchyReports->count() > 0 ?
                ($coordinatorHierarchyReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count() / $coordinatorHierarchyReports->count()) * 100 : 0;

            $directTeamApprovalRate = $directTeamReports->count() > 0 ?
                ($directTeamReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count() / $directTeamReports->count()) * 100 : 0;

            // Calculate average processing time for approved reports
            $coordinatorApprovedReports = $coordinatorHierarchyReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);
            $coordinatorAvgProcessingTime = 0;
            if ($coordinatorApprovedReports->count() > 0) {
                $totalDays = $coordinatorApprovedReports->sum(function($report) {
                    return $report->created_at->diffInDays($report->updated_at ?? now());
                });
                $coordinatorAvgProcessingTime = round($totalDays / $coordinatorApprovedReports->count(), 1);
            }

            $directTeamApprovedReports = $directTeamReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);
            $directTeamAvgProcessingTime = 0;
            if ($directTeamApprovedReports->count() > 0) {
                $totalDays = $directTeamApprovedReports->sum(function($report) {
                    return $report->created_at->diffInDays($report->updated_at ?? now());
                });
                $directTeamAvgProcessingTime = round($totalDays / $directTeamApprovedReports->count(), 1);
            }

            // Calculate project completion rate
            $coordinatorApprovedProjects = $coordinatorHierarchyProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
            $coordinatorProjectCompletionRate = $coordinatorHierarchyProjects->count() > 0 ?
                ($coordinatorApprovedProjects->count() / $coordinatorHierarchyProjects->count()) * 100 : 0;

            $directTeamApprovedProjects = $directTeamProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
            $directTeamProjectCompletionRate = $directTeamProjects->count() > 0 ?
                ($directTeamApprovedProjects->count() / $directTeamProjects->count()) * 100 : 0;

            return [
                'coordinator_hierarchy' => [
                    'projects_count' => $coordinatorHierarchyProjects->count(),
                    'reports_count' => $coordinatorHierarchyReports->count(),
                    'budget' => $coordinatorHierarchyBudget,
                    'expenses' => $coordinatorHierarchyExpenses,
                    'budget_utilization' => $calc->calculateUtilization($coordinatorHierarchyExpenses, $coordinatorHierarchyBudget),
                    'approval_rate' => round($coordinatorHierarchyApprovalRate, 2),
                    'avg_processing_time' => $coordinatorAvgProcessingTime,
                    'project_completion_rate' => round($coordinatorProjectCompletionRate, 2),
                ],
                'direct_team' => [
                    'projects_count' => $directTeamProjects->count(),
                    'reports_count' => $directTeamReports->count(),
                    'budget' => $directTeamBudget,
                    'expenses' => $directTeamExpenses,
                    'budget_utilization' => $calc->calculateUtilization($directTeamExpenses, $directTeamBudget),
                    'approval_rate' => round($directTeamApprovalRate, 2),
                    'avg_processing_time' => $directTeamAvgProcessingTime,
                    'project_completion_rate' => round($directTeamProjectCompletionRate, 2),
                ],
            ];
        });
    }

    /**
     * Get system activity feed data for widget (with caching - 2 minutes TTL for frequent updates)
     * Returns activities from coordinator hierarchy and/or direct team based on context
     */
    private function getSystemActivityFeedData($limit = 50, $context = 'combined')
    {
        $general = Auth::user();
        $cacheKey = "general_system_activity_feed_data_{$limit}_{$context}";

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($general, $limit, $context) {
            // Get coordinator IDs and direct team IDs
            $coordinatorIds = User::where('parent_id', $general->id)
                ->where('role', 'coordinator')
                ->pluck('id');

            $directTeamIds = User::where('parent_id', $general->id)
                ->whereIn('role', ['executor', 'applicant'])
                ->pluck('id');

            // Get all descendant user IDs under coordinators (recursive)
            $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

            // Build query based on context
            $query = ActivityHistory::with(['changedBy', 'project', 'report']);

            if ($context === 'coordinator_hierarchy') {
                // Only activities from coordinator hierarchy users
                $query->whereHas('changedBy', function($q) use ($allUserIdsUnderCoordinators) {
                    $q->whereIn('id', $allUserIdsUnderCoordinators);
                });
            } elseif ($context === 'direct_team') {
                // Only activities from direct team users
                $query->whereHas('changedBy', function($q) use ($directTeamIds) {
                    $q->whereIn('id', $directTeamIds);
                });
            } else {
                // Combined: activities from both contexts
                $allRelevantUserIds = $allUserIdsUnderCoordinators->merge($directTeamIds);
                $query->whereHas('changedBy', function($q) use ($allRelevantUserIds) {
                    $q->whereIn('id', $allRelevantUserIds);
                });
            }

            // Get activities with limit
            $activities = $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($activity) use ($allUserIdsUnderCoordinators, $directTeamIds) {
                    // Format activity for display
                    $activity->formatted_message = $this->formatActivityMessage($activity);
                    $activity->icon = $this->getActivityIcon($activity);
                    $activity->color = $this->getActivityColor($activity);

                    // Determine context badge
                    $changedByUserId = $activity->changed_by_user_id;
                    if ($allUserIdsUnderCoordinators->contains($changedByUserId)) {
                        $activity->context = 'coordinator_hierarchy';
                        $activity->context_label = 'Coordinator Hierarchy';
                    } elseif ($directTeamIds->contains($changedByUserId)) {
                        $activity->context = 'direct_team';
                        $activity->context_label = 'Direct Team';
                    } else {
                        $activity->context = 'unknown';
                        $activity->context_label = 'Unknown';
                    }

                    return $activity;
                })
                ->values();

            // Group by date
            $groupedActivities = $activities->groupBy(function($activity) {
                return $activity->created_at->format('Y-m-d');
            });

            return [
                'activities' => $activities,
                'grouped_activities' => $groupedActivities,
                'total_count' => $activities->count(),
            ];
        });
    }

    /**
     * Format activity message for display
     */
    private function formatActivityMessage($activity)
    {
        $userName = $activity->changedBy->name ?? $activity->changed_by_user_name ?? 'System';
        $entityId = $activity->related_id;

        if ($activity->type === 'project') {
            $entityType = 'Project';
            $action = $activity->new_status ? 'status changed' : 'created';
            $statusInfo = $activity->new_status ?
                ' to ' . ucfirst(str_replace('_', ' ', $activity->new_status)) : '';
        } else {
            $entityType = 'Report';
            $action = $activity->new_status ? 'status changed' : 'created';
            $statusInfo = $activity->new_status ?
                ' to ' . ucfirst(str_replace('_', ' ', $activity->new_status)) : '';
        }

        return "{$userName} {$action} {$entityType} {$entityId}{$statusInfo}";
    }

    /**
     * Get activity icon based on type and status
     */
    private function getActivityIcon($activity)
    {
        if ($activity->type === 'project') {
            return 'folder';
        } else {
            return 'file-text';
        }
    }

    /**
     * Get activity color based on status
     */
    private function getActivityColor($activity)
    {
        if (!$activity->new_status) {
            return 'primary';
        }

        if (str_contains($activity->new_status, 'approved')) {
            return 'success';
        } elseif (str_contains($activity->new_status, 'reverted') || str_contains($activity->new_status, 'rejected')) {
            return 'danger';
        } elseif (str_contains($activity->new_status, 'forwarded') || str_contains($activity->new_status, 'submitted')) {
            return 'info';
        } else {
            return 'secondary';
        }
    }

    /**
     * Get system health data for widget (with caching - 5 minutes TTL)
     * Returns health metrics for both coordinator hierarchy and direct team
     */
    private function getSystemHealthData()
    {
        $general = Auth::user();
        $cacheKey = 'general_system_health_data';

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($general) {
            $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
            $calc = app(\App\Services\Budget\DerivedCalculationService::class);

            // Get coordinator IDs and direct team IDs
            $coordinatorIds = User::where('parent_id', $general->id)
                ->where('role', 'coordinator')
                ->pluck('id');

            $directTeamIds = User::where('parent_id', $general->id)
                ->whereIn('role', ['executor', 'applicant'])
                ->pluck('id');

            // Get all descendant user IDs under coordinators (recursive)
            $allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);

            // Get project IDs
            $coordinatorProjectIds = Project::where(function($query) use ($allUserIdsUnderCoordinators) {
                $query->whereIn('user_id', $allUserIdsUnderCoordinators)
                      ->orWhereIn('in_charge', $allUserIdsUnderCoordinators);
            })->pluck('project_id');

            $directTeamProjectIds = Project::where(function($query) use ($directTeamIds) {
                $query->whereIn('user_id', $directTeamIds)
                      ->orWhereIn('in_charge', $directTeamIds);
            })->pluck('project_id');

            // Get projects and reports
            $coordinatorHierarchyProjects = Project::whereIn('project_id', $coordinatorProjectIds)->get();
            $coordinatorHierarchyReports = DPReport::whereIn('project_id', $coordinatorProjectIds)->get();

            $directTeamProjects = Project::whereIn('project_id', $directTeamProjectIds)->get();
            $directTeamReports = DPReport::whereIn('project_id', $directTeamProjectIds)->get();

            // Memoize resolved financials (resolve each project exactly once)
            $resolvedFinancials = [];
            foreach ($coordinatorHierarchyProjects->merge($directTeamProjects) as $project) {
                $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
            }

            // Helper function to calculate health metrics
            $calculateHealthMetrics = function($projects, $reports, $resolvedFinancials, $calc) {
                $totalProjects = $projects->count();
                $totalReports = $reports->count();

                // Approved projects
                $approvedProjects = $projects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);
                $completionRate = $totalProjects > 0 ? ($approvedProjects->count() / $totalProjects) * 100 : 0;

                // Approved reports
                $approvedReports = $reports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);
                $approvalRate = $totalReports > 0 ? ($approvedReports->count() / $totalReports) * 100 : 0;

                // Average processing time
                $avgProcessingTime = 0;
                if ($approvedReports->count() > 0) {
                    $totalDays = $approvedReports->sum(function($report) {
                        return $report->created_at->diffInDays(now());
                    });
                    $avgProcessingTime = round($totalDays / $approvedReports->count(), 1);
                }

                // Budget utilization
                $totalBudget = $approvedProjects->sum(
                    fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0)
                );
                $approvedReportIds = $approvedReports->pluck('report_id');
                $totalExpenses = DPAccountDetail::whereIn('report_id', $approvedReportIds)
                    ->sum('total_expenses') ?? 0;
                $budgetUtilization = $calc->calculateUtilization($totalExpenses, $totalBudget);

                // Activity rate (users active in last 30 days)
                $allUserIds = $projects->pluck('user_id')->merge($projects->pluck('in_charge'))
                    ->merge($reports->pluck('user_id'))->unique()->filter();
                $recentActivities = ActivityHistory::where('created_at', '>=', now()->subDays(30))
                    ->whereIn('changed_by_user_id', $allUserIds)
                    ->distinct('changed_by_user_id')
                    ->count('changed_by_user_id');
                $totalUsers = $allUserIds->count();
                $activityRate = $totalUsers > 0 ? ($recentActivities / $totalUsers) * 100 : 0;

                // Calculate health score (0-100)
                $healthFactors = [
                    'approval_rate' => min(100, $approvalRate),
                    'budget_utilization' => min(100, $budgetUtilization),
                    'processing_time' => max(0, 100 - ($avgProcessingTime * 5)), // Better if faster
                    'completion_rate' => min(100, $completionRate),
                    'activity_rate' => min(100, $activityRate),
                ];

                $overallScore = round(
                    ($healthFactors['approval_rate'] * 0.3) +
                    (max(0, 100 - abs($healthFactors['budget_utilization'] - 70)) * 0.2) + // Optimal around 70%
                    (max(0, min(100, $healthFactors['processing_time'])) * 0.2) +
                    ($healthFactors['completion_rate'] * 0.15) +
                    ($healthFactors['activity_rate'] * 0.15)
                );

                // Determine health status
                $healthStatus = 'excellent';
                if ($overallScore < 50) {
                    $healthStatus = 'critical';
                } elseif ($overallScore < 70) {
                    $healthStatus = 'warning';
                } elseif ($overallScore < 85) {
                    $healthStatus = 'good';
                }

                // Generate alerts
                $alerts = [];
                if ($approvalRate < 60) {
                    $alerts[] = [
                        'type' => 'critical',
                        'message' => 'Low approval rate: ' . round($approvalRate, 1) . '%',
                        'icon' => 'alert-circle'
                    ];
                }
                if ($avgProcessingTime > 30) {
                    $alerts[] = [
                        'type' => 'warning',
                        'message' => 'High average processing time: ' . $avgProcessingTime . ' days',
                        'icon' => 'clock'
                    ];
                }
                if ($budgetUtilization > 90) {
                    $alerts[] = [
                        'type' => 'warning',
                        'message' => 'High budget utilization: ' . round($budgetUtilization, 1) . '%',
                        'icon' => 'dollar-sign'
                    ];
                }
                if ($activityRate < 30) {
                    $alerts[] = [
                        'type' => 'info',
                        'message' => 'Low activity rate: ' . round($activityRate, 1) . '%',
                        'icon' => 'activity'
                    ];
                }

                return [
                    'overall_score' => $overallScore,
                    'health_status' => $healthStatus,
                    'approval_rate' => round($approvalRate, 2),
                    'completion_rate' => round($completionRate, 2),
                    'avg_processing_time' => $avgProcessingTime,
                    'budget_utilization' => round($budgetUtilization, 2),
                    'activity_rate' => round($activityRate, 2),
                    'alerts' => $alerts,
                    'health_factors' => $healthFactors,
                ];
            };

            // Calculate metrics for both contexts
            $coordinatorHierarchyHealth = $calculateHealthMetrics(
                $coordinatorHierarchyProjects,
                $coordinatorHierarchyReports,
                $resolvedFinancials,
                $calc
            );

            $directTeamHealth = $calculateHealthMetrics(
                $directTeamProjects,
                $directTeamReports,
                $resolvedFinancials,
                $calc
            );

            // Combined metrics
            $combinedProjects = $coordinatorHierarchyProjects->merge($directTeamProjects);
            $combinedReports = $coordinatorHierarchyReports->merge($directTeamReports);
            $combinedHealth = $calculateHealthMetrics($combinedProjects, $combinedReports, $resolvedFinancials, $calc);

            return [
                'coordinator_hierarchy' => $coordinatorHierarchyHealth,
                'direct_team' => $directTeamHealth,
                'combined' => $combinedHealth,
            ];
        });
    }

    /**
     * Get centers map for all provinces from database
     * Returns array with province name (uppercase) as key and array of center names as value
     */
    private function getCentersMap()
    {
        return Cache::remember('centers_map', now()->addHours(24), function () {
            $centersMap = [];

            $provinces = Province::active()->with('activeCenters')->get();

            foreach ($provinces as $province) {
                $provinceKey = strtoupper($province->name);
                $centersMap[$provinceKey] = $province->activeCenters->pluck('name')->toArray();
            }

            return $centersMap;
        });
    }

    /**
     * Get centers for a specific province from database
     */
    private function getCentersByProvince($provinceName)
    {
        $province = Province::where('name', $provinceName)->first();
        if (!$province) {
            return collect([]);
        }
        return $province->activeCenters()->pluck('name');
    }

    /**
     * Show center management page for transferring centers between provinces
     */
    public function manageCenters()
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can manage centers.');
        }

        $provinces = Province::active()->with('activeCenters')->orderBy('name')->get();

        return view('general.centers.manage', compact('provinces'));
    }

    /**
     * Show transfer center form
     */
    public function showTransferCenter($centerId)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can transfer centers.');
        }

        $center = Center::with('province')->findOrFail($centerId);
        $provinces = Province::active()->where('id', '!=', $center->province_id)->orderBy('name')->get();

        // Get child users count for this center
        $childUsersCount = $this->getChildUsersCountForCenter($general->id, $center->id);

        return view('general.centers.transfer', compact('center', 'provinces', 'childUsersCount'));
    }

    /**
     * Transfer center to another province
     */
    public function transferCenter(Request $request, $centerId)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can transfer centers.');
        }

        $request->validate([
            'target_province_id' => 'required|exists:provinces,id',
            'update_child_users' => 'boolean',
        ]);

        $center = Center::with('province')->findOrFail($centerId);
        $targetProvince = Province::findOrFail($request->target_province_id);

        DB::beginTransaction();
        try {
            // Update center's province
            $oldProvinceName = $center->province->name;
            $center->province_id = $targetProvince->id;
            $center->save();

            // Clear cache
            Cache::forget('centers_map');

            // Update child users if requested
            if ($request->has('update_child_users') && $request->update_child_users) {
                $updatedCount = $this->updateChildUsersCenterRecursively(
                    $general->id,
                    $center->name,
                    $oldProvinceName,
                    $targetProvince->name
                );

                Log::info('Center transferred with child users updated', [
                    'general_id' => $general->id,
                    'center_id' => $centerId,
                    'center_name' => $center->name,
                    'old_province' => $oldProvinceName,
                    'new_province' => $targetProvince->name,
                    'users_updated' => $updatedCount,
                ]);
            }

            DB::commit();

            return redirect()->route('general.manageCenters')
                ->with('success', "Center \"{$center->name}\" has been transferred from \"{$oldProvinceName}\" to \"{$targetProvince->name}\".");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error transferring center', [
                'general_id' => $general->id,
                'center_id' => $centerId,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors('Failed to transfer center: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show manage user centers page - allows changing centers for child users
     */
    public function manageUserCenters(Request $request, $userId = null)
    {
        $user = Auth::user();

        // If userId is provided and user has permission, use that user
        // Otherwise, use current user
        if ($userId && in_array($user->role, ['general', 'coordinator'])) {
            $targetUser = User::findOrFail($userId);

            // Verify the target user is a child (or nested child) of current user
            if (!$this->isChildUser($user->id, $targetUser->id)) {
                abort(403, 'Access denied. You can only manage centers for users under your management.');
            }
        } else {
            $targetUser = $user;
        }

        // Get all child users (including nested)
        $childUsers = $this->getAllChildUsers($user->id);

        // Get provinces and centers for dropdowns
        $provinces = Province::active()->with('activeCenters')->orderBy('name')->get();
        $centersMap = $this->getCentersMap();

        return view('general.centers.manage-users', compact('childUsers', 'provinces', 'centersMap', 'targetUser'));
    }

    /**
     * Update center for a specific user and optionally their child users
     */
    public function updateUserCenter(Request $request, $userId)
    {
        $user = Auth::user();
        $targetUser = User::findOrFail($userId);

        // Verify the target user is a child (or nested child) of current user
        if (!in_array($user->role, ['general', 'coordinator', 'provincial']) &&
            $targetUser->id !== $user->id) {
            abort(403, 'Access denied. You can only manage centers for users under your management.');
        }

        if (in_array($user->role, ['general', 'coordinator']) &&
            !$this->isChildUser($user->id, $targetUser->id)) {
            abort(403, 'Access denied. You can only manage centers for users under your management.');
        }

        $request->validate([
            'province' => 'required|exists:provinces,name',
            'center' => 'nullable|string|max:255',
            'update_child_users' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Get province and center IDs
            $province = Province::where('name', $request->province)->first();
            $provinceId = $province ? $province->id : null;

            $centerId = null;
            if ($request->filled('center') && $provinceId) {
                $center = Center::where('province_id', $provinceId)
                    ->whereRaw('UPPER(name) = ?', [strtoupper($request->center)])
                    ->first();
                $centerId = $center ? $center->id : null;
            }

            // Update target user
            $oldProvince = $targetUser->province;
            $oldCenter = $targetUser->center;

            $targetUser->province = $request->province;
            $targetUser->province_id = $provinceId;
            $targetUser->center = $request->center;
            $targetUser->center_id = $centerId;
            $targetUser->save();

            $updatedCount = 1;

            // Update child users if requested
            if ($request->has('update_child_users') && $request->update_child_users) {
                $childUpdatedCount = $this->updateChildUsersCenterRecursively(
                    $targetUser->id,
                    $request->center,
                    $oldProvince,
                    $request->province
                );
                $updatedCount += $childUpdatedCount;
            }

            DB::commit();

            Log::info('User center updated', [
                'updated_by' => $user->id,
                'target_user_id' => $userId,
                'old_province' => $oldProvince,
                'new_province' => $request->province,
                'old_center' => $oldCenter,
                'new_center' => $request->center,
                'child_users_updated' => $updatedCount - 1,
            ]);

            return redirect()->back()
                ->with('success', "Center updated for user \"{$targetUser->name}\" and {$updatedCount} user(s) total.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating user center', [
                'updated_by' => $user->id,
                'target_user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors('Failed to update center: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Helper: Check if a user is a child (or nested child) of another user
     */
    private function isChildUser($parentId, $childId)
    {
        $child = User::find($childId);
        if (!$child) {
            return false;
        }

        // Direct child
        if ($child->parent_id == $parentId) {
            return true;
        }

        // Nested child - recursively check
        if ($child->parent_id) {
            return $this->isChildUser($parentId, $child->parent_id);
        }

        return false;
    }

    /**
     * Helper: Get all child users recursively
     */
    private function getAllChildUsers($userId)
    {
        $children = collect();
        $directChildren = User::where('parent_id', $userId)->get();

        foreach ($directChildren as $child) {
            $children->push($child);
            $children = $children->merge($this->getAllChildUsers($child->id));
        }

        return $children;
    }

    /**
     * Helper: Get count of child users for a center
     */
    private function getChildUsersCountForCenter($userId, $centerId)
    {
        $center = Center::find($centerId);
        if (!$center) {
            return 0;
        }

        $allChildUsers = $this->getAllChildUsers($userId);
        return $allChildUsers->where('center_id', $centerId)->count();
    }

    /**
     * Helper: Recursively update child users' center
     */
    private function updateChildUsersCenterRecursively($userId, $centerName, $oldProvince, $newProvince)
    {
        $updatedCount = 0;
        $childUsers = User::where('parent_id', $userId)->get();

        $newProvinceModel = Province::where('name', $newProvince)->first();
        $newProvinceId = $newProvinceModel ? $newProvinceModel->id : null;

        $newCenterId = null;
        if ($centerName && $newProvinceId) {
            $newCenter = Center::where('province_id', $newProvinceId)
                ->whereRaw('UPPER(name) = ?', [strtoupper($centerName)])
                ->first();
            $newCenterId = $newCenter ? $newCenter->id : null;
        }

        foreach ($childUsers as $childUser) {
            // Only update if user's current province/center matches
            if ($childUser->province == $oldProvince ||
                ($centerName && $childUser->center == $centerName)) {

                $childUser->province = $newProvince;
                $childUser->province_id = $newProvinceId;

                if ($centerName) {
                    $childUser->center = $centerName;
                    $childUser->center_id = $newCenterId;
                }

                $childUser->save();
                $updatedCount++;

                // Recursively update nested children
                $updatedCount += $this->updateChildUsersCenterRecursively(
                    $childUser->id,
                    $centerName,
                    $oldProvince,
                    $newProvince
                );
            }
        }

        return $updatedCount;
    }

    /**
     * List all provinces with their provincial users
     */
    public function listProvinces(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can manage provinces.');
        }

        // Get all provinces from database
        $provinces = Province::active()
            ->with(['provincialUsers', 'provincialUsersViaForeignKey', 'centers'])
            ->orderBy('name')
            ->get()
            ->map(function ($province) {
                // Get all provincial users (combines pivot table and province_id relationships)
                $provincialUsers = $province->getAllProvincialUsers();

                // Count users in this province using province_id
                $userCount = User::where('province_id', $province->id)->count();

                // Get centers count from relationship
                $centerCount = $province->centers()->where('is_active', true)->count();

                return [
                    'name' => $province->name,
                    'provincial_users' => $provincialUsers,
                    'user_count' => $userCount,
                    'center_count' => $centerCount,
                ];
            });

        // Apply search filter
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $provinces = $provinces->filter(function ($province) use ($search) {
                $matchesName = str_contains(strtolower($province['name']), $search);
                $matchesProvincialUsers = $province['provincial_users']->contains(function ($user) use ($search) {
                    return str_contains(strtolower($user->name), $search);
                });
                return $matchesName || $matchesProvincialUsers;
            })->values();
        }

        return view('general.provinces.index', compact('provinces'));
    }

    /**
     * Show form to create a new province
     */
    public function createProvince()
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create provinces.');
        }

        return view('general.provinces.create');
    }

    /**
     * Store a new province
     */
    public function storeProvince(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create provinces.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:provinces,name',
            'centers' => 'nullable|string',
        ]);

        try {
            // Create province in database
            $province = Province::create([
                'name' => $request->name,
                'created_by' => $general->id,
                'is_active' => true,
            ]);

            // If centers are provided, create them
            if ($request->filled('centers')) {
                $centerNames = array_filter(array_map('trim', explode("\n", $request->centers)));
                foreach ($centerNames as $centerName) {
                    if (!empty($centerName)) {
                        Center::create([
                            'province_id' => $province->id,
                            'name' => $centerName,
                            'is_active' => true,
                        ]);
                    }
                }
            }

            Log::info('Province created by General', [
                'general_id' => $general->id,
                'province_id' => $province->id,
                'province_name' => $request->name,
            ]);

            return redirect()->route('general.provinces')
                ->with('success', 'Province "' . $request->name . '" has been created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating province', [
                'general_id' => $general->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors('Failed to create province: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show form to edit province details
     */
    public function editProvince($provinceName)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can edit provinces.');
        }

        // Get province from database
        $province = Province::where('name', $provinceName)->firstOrFail();

        // Get all provincial users (combines pivot table and province_id relationships)
        $provincialUsers = $province->getAllProvincialUsers();

        // Get user count using province_id
        $userCount = User::where('province_id', $province->id)->count();

        // Get centers from database
        $centers = $province->activeCenters()->pluck('name')->toArray();
        $centersString = implode("\n", $centers);

        // Get eligible users for provincial assignment:
        // 1. Users with role='provincial' (can be assigned/reassigned via province_id)
        // 2. Users with role='general' (can be provincial for multiple provinces via pivot table)
        // 3. Users not assigned any province (province_id is null)
        $eligibleUsers = User::where(function($query) {
                $query->where('role', 'provincial')
                      ->orWhere('role', 'general')
                      ->orWhereNull('province_id');
            })
            ->select('id', 'name', 'email', 'role', 'province_id')
            ->orderBy('name')
            ->get()
            ->map(function($user) use ($province) {
                // Check if user is assigned via province_id (for provincial users)
                $isAssignedViaForeignKey = $user->province_id == $province->id && $user->role === 'provincial';

                // Check if user is assigned via pivot table (for general users)
                $isAssignedViaPivot = $province->provincialUsers()->where('users.id', $user->id)->exists();

                // User is assigned if either relationship exists
                $isAssigned = $isAssignedViaForeignKey || $isAssignedViaPivot;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'current_province_id' => $user->province_id,
                    'is_assigned_to_this_province' => $isAssigned,
                    'is_assigned_via_pivot' => $isAssignedViaPivot,
                    'is_assigned_via_foreign_key' => $isAssignedViaForeignKey,
                ];
            });

        return view('general.provinces.edit', compact('provinceName', 'province', 'provincialUsers', 'userCount', 'centersString', 'eligibleUsers'));
    }

    /**
     * Update province details
     */
    public function updateProvince(Request $request, $provinceName)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can update provinces.');
        }

        // Get province from database
        $province = Province::where('name', $provinceName)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255|unique:provinces,name,' . $province->id,
            'centers' => 'nullable|string',
            'provincial_user_ids' => 'nullable|array',
            'provincial_user_ids.*' => 'exists:users,id',
        ]);

        // Update province name if changed
        if ($request->name !== $provinceName) {
            $oldName = $province->name;
            $province->name = $request->name;
            $province->save();

            // Update all users with this province (both fields for backward compatibility)
            $userCount = User::where('province_id', $province->id)->count();
            if ($userCount > 0) {
                User::where('province_id', $province->id)->update(['province' => $request->name]);
            }

            Log::info('Province name updated by General', [
                'general_id' => $general->id,
                'province_id' => $province->id,
                'old_name' => $oldName,
                'new_name' => $request->name,
                'users_updated' => $userCount,
            ]);
        }

        // Update centers if provided
        if ($request->filled('centers')) {
            $centerNames = array_filter(array_map('trim', explode("\n", $request->centers)));

            // Get existing centers
            $existingCenters = $province->centers()->pluck('name')->toArray();

            // Add new centers
            foreach ($centerNames as $centerName) {
                if (!empty($centerName) && !in_array($centerName, $existingCenters)) {
                    Center::create([
                        'province_id' => $province->id,
                        'name' => $centerName,
                        'is_active' => true,
                    ]);
                }
            }

            // Remove centers that are no longer in the list
            $centersToRemove = array_diff($existingCenters, $centerNames);
            if (!empty($centersToRemove)) {
                Center::where('province_id', $province->id)
                    ->whereIn('name', $centersToRemove)
                    ->update(['is_active' => false]);
            }

            Log::info('Province centers updated by General', [
                'general_id' => $general->id,
                'province_id' => $province->id,
                'province' => $province->name,
                'centers_count' => count($centerNames),
            ]);
        }

        // Update provincial user assignments
        if ($request->has('provincial_user_ids')) {
            $selectedUserIds = $request->input('provincial_user_ids', []);

            // Get current provincial users (from both pivot table and province_id)
            $currentProvincialUserIds = $province->getAllProvincialUsers()->pluck('id')->toArray();

            // Users to add (in selected but not in current)
            $usersToAdd = array_diff($selectedUserIds, $currentProvincialUserIds);

            // Users to remove (in current but not in selected)
            $usersToRemove = array_diff($currentProvincialUserIds, $selectedUserIds);

            // Add users to province
            foreach ($usersToAdd as $userId) {
                $user = User::find($userId);
                if ($user) {
                    // Only assign if user is eligible (provincial, general, or unassigned)
                    if (in_array($user->role, ['provincial', 'general']) || $user->province_id === null) {
                        if ($user->role === 'general') {
                            // General users: Use pivot table (many-to-many relationship)
                            // This allows them to manage multiple provinces
                            // DO NOT overwrite province_id - it may be set for another province
                            $province->provincialUsers()->syncWithoutDetaching([$userId]);

                            // Only set province_id if it's null (first assignment)
                            // Otherwise, keep existing province_id and use pivot table for all assignments
                            if ($user->province_id === null) {
                                $user->province_id = $province->id;
                                $user->province = $province->name; // Backward compatibility
                                $user->save();
                            }

                            Log::info('General user assigned as provincial user (via pivot)', [
                                'user_id' => $user->id,
                                'user_name' => $user->name,
                                'province_id' => $province->id,
                                'province_name' => $province->name,
                                'current_province_id' => $user->province_id,
                                'total_managed_provinces' => $user->managedProvinces()->count(),
                            ]);
                        } else {
                            // Provincial users or unassigned users: Use province_id (one-to-many)
                            $user->province_id = $province->id;
                            $user->province = $province->name; // Backward compatibility

                            // If user is not provincial, set role to provincial
                            if ($user->role !== 'provincial') {
                                $user->role = 'provincial';
                            }

                            $user->save();

                            Log::info('User assigned as provincial user (via province_id)', [
                                'user_id' => $user->id,
                                'user_name' => $user->name,
                                'user_role' => $user->role,
                                'province_id' => $province->id,
                                'province_name' => $province->name,
                            ]);
                        }
                    }
                }
            }

            // Remove users from province
            foreach ($usersToRemove as $userId) {
                $user = User::find($userId);
                if ($user) {
                    if ($user->role === 'general') {
                        // General users: Remove from pivot table
                        $province->provincialUsers()->detach($userId);

                        Log::info('General user removed as provincial user (from pivot)', [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'province_id' => $province->id,
                            'province_name' => $province->name,
                        ]);
                    } else {
                        // Provincial users: Remove from province_id
                        if ($user->province_id == $province->id) {
                            $user->province_id = null;
                            $user->province = 'none'; // Backward compatibility
                            $user->save();

                            Log::info('User removed as provincial user (from province_id)', [
                                'user_id' => $user->id,
                                'user_name' => $user->name,
                                'province_id' => $province->id,
                                'province_name' => $province->name,
                            ]);
                        }
                    }
                }
            }
        }

        Log::info('Province updated by General', [
            'general_id' => $general->id,
            'province_id' => $province->id,
            'province_name' => $province->name,
        ]);

        return redirect()->route('general.provinces')
            ->with('success', 'Province "' . $province->name . '" has been updated.');
    }

    /**
     * Delete province (with validation)
     */
    public function deleteProvince($provinceName)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can delete provinces.');
        }

        // Get province from database
        $province = Province::where('name', $provinceName)->firstOrFail();

        // Check if province has users assigned
        $userCount = User::where('province_id', $province->id)->count();

        if ($userCount > 0) {
            $users = User::where('province_id', $province->id)
                ->select('id', 'name', 'email', 'role')
                ->get();

            return back()->withErrors([
                'delete_error' => "Cannot delete province. It has {$userCount} user(s) assigned. Please reassign or remove users first.",
                'users' => $users,
            ]);
        }

        // Delete the province (cascade will handle centers)
        $provinceId = $province->id;
        $provinceNameForLog = $province->name;
        $province->delete();

        Log::info('Province deleted by General', [
            'general_id' => $general->id,
            'province_id' => $provinceId,
            'province_name' => $provinceNameForLog,
        ]);

        return redirect()->route('general.provinces')
            ->with('success', 'Province "' . $provinceNameForLog . '" has been deleted.');
    }

    // ==================== Society Management ====================

    /**
     * List all societies
     */
    public function listSocieties(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can view societies.');
        }

        $query = Society::with(['province']);

        // Filter by province if provided
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        } else {
            $query->where('is_active', true); // Default to active only
        }

        $societies = $query->orderBy('name')->paginate(20);

        // Get provinces for filter dropdown
        $provinces = Province::active()->orderBy('name')->get();

        return view('general.societies.index', compact('societies', 'provinces'));
    }

    /**
     * Show form to create a new society
     */
    public function createSociety()
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create societies.');
        }

        // Get provinces from database
        $provinces = Province::active()->orderBy('name')->get();

        return view('general.societies.create', compact('provinces'));
    }

    /**
     * Store a new society
     */
    public function storeSociety(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create societies.');
        }

        $request->validate([
            'province_id' => 'required|exists:provinces,id',
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    // Check if society with same name already exists in this province
                    $existingSociety = Society::where('province_id', $request->province_id)
                        ->whereRaw('UPPER(name) = ?', [strtoupper($value)])
                        ->first();

                    if ($existingSociety) {
                        $fail('A society with this name already exists in the selected province.');
                    }
                },
            ],
        ]);

        try {
            $society = Society::create([
                'province_id' => $request->province_id,
                'name' => $request->name,
                'is_active' => true,
            ]);

            Log::info('Society created by General', [
                'general_id' => $general->id,
                'society_id' => $society->id,
                'society_name' => $request->name,
                'province_id' => $request->province_id,
            ]);

            return redirect()->route('general.societies')
                ->with('success', 'Society "' . $request->name . '" has been created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating society', [
                'general_id' => $general->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors('Failed to create society: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show form to edit society
     */
    public function editSociety($id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can edit societies.');
        }

        $society = Society::findOrFail($id);
        $provinces = Province::active()->orderBy('name')->get();

        return view('general.societies.edit', compact('society', 'provinces'));
    }

    /**
     * Update a society
     */
    public function updateSociety(Request $request, $id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can update societies.');
        }

        $society = Society::findOrFail($id);

        $request->validate([
            'province_id' => 'required|exists:provinces,id',
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request, $id) {
                    // Check if society with same name already exists in this province (excluding current)
                    $existingSociety = Society::where('province_id', $request->province_id)
                        ->whereRaw('UPPER(name) = ?', [strtoupper($value)])
                        ->where('id', '!=', $id)
                        ->first();

                    if ($existingSociety) {
                        $fail('A society with this name already exists in the selected province.');
                    }
                },
            ],
            'is_active' => 'required|boolean',
        ]);

        try {
            $society->update([
                'province_id' => $request->province_id,
                'name' => $request->name,
                'is_active' => $request->is_active,
            ]);

            Log::info('Society updated by General', [
                'general_id' => $general->id,
                'society_id' => $society->id,
                'society_name' => $request->name,
            ]);

            return redirect()->route('general.societies')
                ->with('success', 'Society "' . $request->name . '" has been updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating society', [
                'general_id' => $general->id,
                'society_id' => $society->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors('Failed to update society: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Delete a society
     */
    public function deleteSociety($id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can delete societies.');
        }

        $society = Society::findOrFail($id);

        // Check if society has centers
        $centersCount = $society->centers()->count();
        if ($centersCount > 0) {
            return redirect()->route('general.societies')
                ->with('error', 'Cannot delete society "' . $society->name . '" because it has ' . $centersCount . ' center(s) associated with it.');
        }

        try {
            $societyNameForLog = $society->name;
            $society->delete();

            Log::info('Society deleted by General', [
                'general_id' => $general->id,
                'society_id' => $id,
                'society_name' => $societyNameForLog,
            ]);

            return redirect()->route('general.societies')
                ->with('success', 'Society "' . $societyNameForLog . '" has been deleted.');
        } catch (\Exception $e) {
            Log::error('Error deleting society', [
                'general_id' => $general->id,
                'society_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('general.societies')
                ->with('error', 'Failed to delete society: ' . $e->getMessage());
        }
    }

    // ==================== Center Management (Updated for Societies) ====================

    /**
     * Show form to create a new center (linked to society)
     */
    public function createCenter()
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create centers.');
        }

        // Get provinces (centers belong to provinces, not societies)
        $provinces = Province::active()->orderBy('name')->get();

        return view('general.centers.create', compact('provinces'));
    }

    /**
     * Store a new center (linked to society)
     */
    public function storeCenter(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can create centers.');
        }

        $request->validate([
            'province_id' => 'required|exists:provinces,id',
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    // Check if center with same name already exists in this province
                    // Centers belong to provinces, so uniqueness is per province
                    $existingCenter = Center::where('province_id', $request->province_id)
                        ->whereRaw('UPPER(name) = ?', [strtoupper($value)])
                        ->first();

                    if ($existingCenter) {
                        $fail('A center with this name already exists in the selected province.');
                    }
                },
            ],
        ]);

        try {
            // Centers belong to provinces, not societies
            // All centers in a province are available to all societies in that province
            $center = Center::create([
                'province_id' => $request->province_id,
                'society_id' => null, // Centers don't belong to specific societies
                'name' => $request->name,
                'is_active' => true,
            ]);

            Log::info('Center created by General', [
                'general_id' => $general->id,
                'center_id' => $center->id,
                'center_name' => $request->name,
                'province_id' => $request->province_id,
            ]);

            // Clear the centers cache to reflect the new center
            Cache::forget('centers_map');

            return redirect()->route('general.centers')
                ->with('success', 'Center "' . $request->name . '" has been created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating center', [
                'general_id' => $general->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors('Failed to create center: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * List all centers
     */
    public function listCenters(Request $request)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can view centers.');
        }

        $query = Center::with(['province']);

        // Filter by province if provided
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        // Filter by society if provided (show centers from that society's province)
        if ($request->filled('society_id')) {
            $society = Society::find($request->society_id);
            if ($society) {
                $query->where('province_id', $society->province_id);
            }
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        } else {
            $query->where('is_active', true); // Default to active only
        }

        $centers = $query->orderBy('name')->paginate(20);

        // Get provinces and societies for filter dropdowns
        $provinces = Province::active()->orderBy('name')->get();
        $societies = Society::active()->with('province')->orderBy('name')->get();

        return view('general.centers.index', compact('centers', 'provinces', 'societies'));
    }

    /**
     * Show form to edit a center
     */
    public function editCenter($id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can edit centers.');
        }

        $center = Center::with('province')->findOrFail($id);
        $provinces = Province::active()->orderBy('name')->get();

        return view('general.centers.edit', compact('center', 'provinces'));
    }

    /**
     * Update a center
     */
    public function updateCenter(Request $request, $id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can update centers.');
        }

        $center = Center::findOrFail($id);

        $request->validate([
            'province_id' => 'required|exists:provinces,id',
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request, $id) {
                    // Check if center with same name already exists in this province (excluding current)
                    $existingCenter = Center::where('province_id', $request->province_id)
                        ->whereRaw('UPPER(name) = ?', [strtoupper($value)])
                        ->where('id', '!=', $id)
                        ->first();

                    if ($existingCenter) {
                        $fail('A center with this name already exists in the selected province.');
                    }
                },
            ],
            'is_active' => 'required|boolean',
        ]);

        try {
            $center->update([
                'province_id' => $request->province_id,
                'name' => $request->name,
                'is_active' => $request->is_active,
                // society_id remains null (centers belong to provinces)
            ]);

            Log::info('Center updated by General', [
                'general_id' => $general->id,
                'center_id' => $center->id,
                'center_name' => $request->name,
                'province_id' => $request->province_id,
            ]);

            // Clear the centers cache to reflect the updated center
            Cache::forget('centers_map');

            return redirect()->route('general.centers')
                ->with('success', 'Center "' . $request->name . '" has been updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating center', [
                'general_id' => $general->id,
                'center_id' => $center->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors('Failed to update center: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Delete a center
     */
    public function deleteCenter($id)
    {
        $general = Auth::user();

        if ($general->role !== 'general') {
            abort(403, 'Access denied. Only General users can delete centers.');
        }

        $center = Center::findOrFail($id);

        // Check if center has users
        $usersCount = $center->users()->count();
        if ($usersCount > 0) {
            return redirect()->route('general.centers')
                ->with('error', 'Cannot delete center "' . $center->name . '" because it has ' . $usersCount . ' user(s) associated with it.');
        }

        try {
            $centerNameForLog = $center->name;
            $center->delete();

            Log::info('Center deleted by General', [
                'general_id' => $general->id,
                'center_id' => $id,
                'center_name' => $centerNameForLog,
            ]);

            // Clear the centers cache to reflect the deleted center
            Cache::forget('centers_map');

            return redirect()->route('general.centers')
                ->with('success', 'Center "' . $centerNameForLog . '" has been deleted.');
        } catch (\Exception $e) {
            Log::error('Error deleting center', [
                'general_id' => $general->id,
                'center_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('general.centers')
                ->with('error', 'Failed to delete center: ' . $e->getMessage());
        }
    }

}
