<?php

namespace App\Console\Commands;

use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Wave 6A Phase 0: Read-only pre-check before report–society snapshot migration.
 * Do NOT modify data. Abort if orphan reports exist.
 */
class ReportSocietySnapshotPreCheck extends Command
{
    protected $signature = 'reports:society-snapshot-precheck';

    protected $description = 'Wave 6A Phase 0: Pre-check reports table and project relation before society snapshot migration. Read-only.';

    public function handle(): int
    {
        $this->info('Wave 6A Phase 0 — Report–Society Snapshot Pre-Check (read-only)');
        $this->newLine();

        // 1. Inspect reports table schema (columns we care about)
        $connection = DB::connection();
        $tableName = (new DPReport())->getTable();
        $columns = $connection->getSchemaBuilder()->getColumnListing($tableName);
        $hasProjectId = in_array('project_id', $columns, true);
        $hasSocietyName = in_array('society_name', $columns, true);
        $hasSocietyId = in_array('society_id', $columns, true);
        $hasProvinceId = in_array('province_id', $columns, true);

        $this->info("1. Reports table ({$tableName}) schema:");
        $this->line("   project_id: " . ($hasProjectId ? 'yes' : 'no'));
        $this->line("   society_name: " . ($hasSocietyName ? 'yes' : 'no'));
        $this->line("   society_id: " . ($hasSocietyId ? 'yes' : 'no'));
        $this->line("   province_id: " . ($hasProvinceId ? 'yes' : 'no'));
        $this->newLine();

        // 2. Confirm Report belongsTo Project
        $this->info('2. Relationship: Report belongsTo Project — confirmed (DPReport::project()).');
        $this->newLine();

        // 3. Confirm projects table contains society_id, society_name, province_id
        $projectTable = (new Project())->getTable();
        $projectColumns = $connection->getSchemaBuilder()->getColumnListing($projectTable);
        $projectHasSocietyId = in_array('society_id', $projectColumns, true);
        $projectHasSocietyName = in_array('society_name', $projectColumns, true);
        $projectHasProvinceId = in_array('province_id', $projectColumns, true);
        $this->info("3. Projects table ({$projectTable}) contains:");
        $this->line("   society_id: " . ($projectHasSocietyId ? 'yes' : 'no'));
        $this->line("   society_name: " . ($projectHasSocietyName ? 'yes' : 'no'));
        $this->line("   province_id: " . ($projectHasProvinceId ? 'yes' : 'no'));
        if (!$projectHasSocietyId || !$projectHasSocietyName || !$projectHasProvinceId) {
            $this->error('   Abort: projects table missing required columns.');
            Log::warning('Wave 6A pre-check: projects table missing society_id, society_name, or province_id');
            return self::FAILURE;
        }
        $this->newLine();

        // 4. Count total reports and reports with missing project relation
        $totalReports = DPReport::count();
        $orphanReports = DPReport::whereDoesntHave('project')->count();
        $reportsWithProject = DPReport::whereHas('project')->count();

        $this->info('4. Counts:');
        $this->line("   Total reports: {$totalReports}");
        $this->line("   Reports with project relation: {$reportsWithProject}");
        $this->line("   Reports with missing project (orphans): {$orphanReports}");

        Log::info('Wave 6A Phase 0 pre-check', [
            'reports_table' => $tableName,
            'total_reports' => $totalReports,
            'reports_with_project' => $reportsWithProject,
            'orphan_reports' => $orphanReports,
        ]);

        if ($orphanReports > 0) {
            $this->newLine();
            $this->error("Abort: {$orphanReports} orphan report(s) exist. Fix or remove orphans before running migration and backfill.");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Pre-check passed. No orphan reports. Safe to proceed to Phase 1 migration.');
        return self::SUCCESS;
    }
}
