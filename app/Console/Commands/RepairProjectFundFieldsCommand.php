<?php

namespace App\Console\Commands;

use App\Services\Budget\ApprovedProjectFundFieldAudit;
use App\Services\Budget\BudgetSyncService;
use Illuminate\Console\Command;

/**
 * Phase 4: Repair approved projects whose stored fund fields diverge from type tables.
 *
 * @see Documentations/Reports/Reporting_System_Phase_Wise_Implementation_Plan.md § Phase 4
 */
class RepairProjectFundFieldsCommand extends Command
{
    protected $signature = 'reports:repair-project-fund-fields
                            {--dry-run : Preview changes without writing}
                            {--project= : Limit to a single project_id}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Repair approved project fund fields from type-specific budget sources';

    public function handle(
        ApprovedProjectFundFieldAudit $audit,
        BudgetSyncService $syncService
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $projectFilter = $this->option('project');
        $query = $audit->approvedProjectsQuery($projectFilter);

        $candidates = [];
        $query->orderBy('project_id')->chunk(100, function ($projects) use ($audit, &$candidates) {
            foreach ($projects as $project) {
                $analysis = $audit->analyze($project);
                if ($analysis['issues'] === []) {
                    continue;
                }
                if (($analysis['derived']['amount_sanctioned'] ?? 0) <= ApprovedProjectFundFieldAudit::TOLERANCE) {
                    continue;
                }
                $candidates[] = $analysis;
            }
        });

        if ($candidates === []) {
            $this->info('No repairable projects found.');
            return 0;
        }

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . 'Repair candidates: ' . count($candidates));

        $this->table(
            ['project_id', 'issues', 'stored_sanctioned → derived', 'stored_opening → derived'],
            array_map(fn ($row) => [
                $row['project']->project_id,
                implode(', ', $row['issues']),
                number_format($row['stored']['amount_sanctioned'], 2) . ' → ' . number_format($row['derived']['amount_sanctioned'], 2),
                number_format($row['stored']['opening_balance'], 2) . ' → ' . number_format($row['derived']['opening_balance'], 2),
            ], $candidates)
        );

        if ($dryRun) {
            $this->warn('Dry-run only — no database changes made.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('Apply repairs to ' . count($candidates) . ' project(s)?')) {
            $this->warn('Aborted.');
            return 1;
        }

        $repaired = 0;
        $skipped = 0;

        foreach ($candidates as $row) {
            $project = $row['project']->fresh();
            if ($syncService->repairApprovedProject($project, 'cli_repair')) {
                $repaired++;
                $this->line('Repaired: ' . $project->project_id);
            } else {
                $skipped++;
            }
        }

        $this->info("Done. Repaired: {$repaired}, skipped: {$skipped}.");

        return 0;
    }
}
