<?php

namespace App\Traits;

use App\Models\Reports\Monthly\DPActivity;
use App\Models\Reports\Monthly\DPReport;

trait HandlesReportPhotoActivity
{
    /**
     * Resolve activity_id from form value: "__unassigned__"/empty, "obj:act" (1-based indices), or activity_id string.
     */
    protected function resolveActivityId(DPReport $report, ?string $value): ?string
    {
        if ($value === null || $value === '' || trim((string) $value) === '__unassigned__') {
            return null;
        }
        $value = trim((string) $value);
        if (preg_match('/^(\d+):(\d+)$/', $value, $m)) {
            return $this->resolveActivityIdFromIndices($report, (int) $m[1], (int) $m[2]);
        }
        $activity = DPActivity::with('objective')->find($value);
        if (! $activity || ! $activity->objective || $activity->objective->report_id !== $report->report_id) {
            return null;
        }

        return $activity->activity_id;
    }

    /**
     * Resolve activity_id from 1-based objective and activity indices. Requires $report->objectives.activities.
     */
    protected function resolveActivityIdFromIndices(DPReport $report, int $objIndex, int $actIndex): ?string
    {
        if ($objIndex < 1 || $actIndex < 1) {
            return null;
        }
        $objective = $report->objectives->get($objIndex - 1);
        if (! $objective) {
            return null;
        }
        $activity = $objective->activities->get($actIndex - 1);
        if (! $activity) {
            return null;
        }

        return $activity->activity_id;
    }

    /**
     * Build activity-based filename: {ReportID}_{MMYYYY}_{Obj}_{Act}_{Inc}.{ext}. Unassigned: 00_00.
     */
    protected function buildActivityBasedFilename(DPReport $report, ?string $activity_id, int $incremental, string $extension): string
    {
        $reportId = $report->report_id;
        $mmyyyy = date('mY', strtotime($report->reporting_period_from));
        $inc = sprintf('%02d', min(99, max(1, $incremental)));
        $ext = strtolower(trim((string) ($extension ?: 'jpg')));
        if ($ext === '' || ! preg_match('/^[a-z0-9]+$/', $ext)) {
            $ext = 'jpg';
        }
        if ($activity_id === null) {
            return "{$reportId}_{$mmyyyy}_00_00_{$inc}.{$ext}";
        }
        $activity = DPActivity::with('objective')->find($activity_id);
        if (! $activity || ! $activity->objective) {
            return "{$reportId}_{$mmyyyy}_00_00_{$inc}.{$ext}";
        }
        $objective = $activity->objective;
        $objNum = 0;
        foreach ($report->objectives as $i => $o) {
            if ($o->objective_id === $objective->objective_id) {
                $objNum = $i + 1;
                break;
            }
        }
        $actNum = 0;
        foreach ($objective->activities as $i => $a) {
            if ($a->activity_id === $activity->activity_id) {
                $actNum = $i + 1;
                break;
            }
        }

        return sprintf('%s_%s_%02d_%02d_%s.%s', $reportId, $mmyyyy, $objNum, $actNum, $inc, $ext);
    }
}
