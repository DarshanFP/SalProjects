<?php

namespace App\Support\Reports;

use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPPhoto;
use App\Models\Reports\Monthly\DPReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Graceful 404 lookups for report controllers (Phase 9).
 * Replaces firstOrFail() to avoid ModelNotFoundException → 500 on invalid IDs.
 */
class ReportResourceLookup
{
    public static function findProject(string $projectId, array $with = []): Project
    {
        $query = Project::where('project_id', $projectId);
        if ($with !== []) {
            $query->with($with);
        }

        $project = $query->first();
        if (!$project) {
            Log::warning('Project not found', ['project_id' => $projectId]);
            abort(404, 'Project not found.');
        }

        return $project;
    }

    public static function findReport(string $reportId, array $with = []): DPReport
    {
        $query = DPReport::where('report_id', $reportId);
        if ($with !== []) {
            $query->with($with);
        }

        $report = $query->first();
        if (!$report) {
            Log::warning('Report not found', ['report_id' => $reportId]);
            abort(404, 'Report not found.');
        }

        return $report;
    }

    /**
     * @param  Builder<DPReport>  $query  Query already scoped (e.g. role filters)
     */
    public static function firstReportOrAbort(Builder $query, string $reportId, array $logContext = []): DPReport
    {
        $report = $query->first();
        if (!$report) {
            Log::warning('Report not found', array_merge(['report_id' => $reportId], $logContext));
            abort(404, 'Report not found.');
        }

        return $report;
    }

    /**
     * @param  Builder<DPPhoto>  $query  Query already scoped (e.g. role filters)
     */
    public static function firstPhotoOrAbort(Builder $query, string $photoId, array $logContext = []): DPPhoto
    {
        $photo = $query->first();
        if (!$photo) {
            Log::warning('Photo not found', array_merge(['photo_id' => $photoId], $logContext));
            abort(404, 'Photo not found.');
        }

        return $photo;
    }
}
