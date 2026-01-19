<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHistoryHelper;
use App\Models\ActivityHistory;
use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Services\ActivityHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityHistoryController extends Controller
{
    /**
     * Display my activities (for executor/applicant)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function myActivities(Request $request)
    {
        $user = Auth::user();

        // Only executor and applicant can access this
        if (!in_array($user->role, ['executor', 'applicant'])) {
            abort(403, 'Access denied');
        }

        $activities = ActivityHistoryService::getWithFilters($request->all(), $user);

        return view('activity-history.my-activities', compact('activities'));
    }

    /**
     * Display team activities (for provincial)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function teamActivities(Request $request)
    {
        $user = Auth::user();

        // Only provincial can access this
        if ($user->role !== 'provincial') {
            abort(403, 'Access denied');
        }

        $activities = ActivityHistoryService::getWithFilters($request->all(), $user);

        return view('activity-history.team-activities', compact('activities'));
    }

    /**
     * Display all activities (for coordinator/admin/general)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function allActivities(Request $request)
    {
        $user = Auth::user();

        // Coordinator, general, and admin can access this
        // General has COMPLETE coordinator access - same authorization level
        if (!in_array($user->role, ['coordinator', 'admin', 'general'])) {
            abort(403, 'Access denied');
        }

        $activities = ActivityHistoryService::getWithFilters($request->all(), $user);

        return view('activity-history.all-activities', compact('activities'));
    }

    /**
     * Display activity history for a specific project
     *
     * @param string $projectId
     * @return \Illuminate\View\View
     */
    public function projectHistory(string $projectId)
    {
        $user = Auth::user();
        $project = Project::where('project_id', $projectId)->firstOrFail();

        // Check permission
        if (!ActivityHistoryHelper::canViewProjectActivity($projectId, $user)) {
            abort(403, 'Access denied');
        }

        $activities = ActivityHistoryService::getForProject($projectId);

        return view('activity-history.project-history', compact('project', 'activities'));
    }

    /**
     * Display activity history for a specific report
     *
     * @param string $reportId
     * @return \Illuminate\View\View
     */
    public function reportHistory(string $reportId)
    {
        $user = Auth::user();
        $report = DPReport::where('report_id', $reportId)->firstOrFail();

        // Check permission
        if (!ActivityHistoryHelper::canViewReportActivity($reportId, $user)) {
            abort(403, 'Access denied');
        }

        $activities = ActivityHistoryService::getForReport($reportId);

        return view('activity-history.report-history', compact('report', 'activities'));
    }
}
