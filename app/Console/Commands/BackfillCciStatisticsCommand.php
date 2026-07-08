<?php

namespace App\Console\Commands;

use App\Constants\ProjectType;
use App\Models\OldProjects\CCI\ProjectCCIStatistics;
use App\Models\OldProjects\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3: Optional data repair — create empty CCI statistics rows for CCI projects that have none.
 */
class BackfillCciStatisticsCommand extends Command
{
    protected $signature = 'projects:backfill-cci-statistics
                            {--dry-run : List projects only; do not insert}
                            {--project= : Limit to a single project_id (e.g. CCI-0001)}';

    protected $description = 'Create missing ProjectCCIStatistics rows for CCI projects (Phase 3 optional repair).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $singleProject = $this->option('project');

        $this->info('Phase 3 — Backfill missing CCI statistics records');
        if ($dryRun) {
            $this->warn('DRY RUN — no rows will be inserted.');
        }
        $this->newLine();

        $query = Project::query()
            ->where('project_type', ProjectType::CHILD_CARE_INSTITUTION)
            ->orderBy('project_id');

        if ($singleProject) {
            $query->where('project_id', $singleProject);
        }

        $created = 0;
        $skipped = 0;

        $query->chunk(100, function ($projects) use ($dryRun, &$created, &$skipped) {
            foreach ($projects as $project) {
                $exists = ProjectCCIStatistics::where('project_id', $project->project_id)->exists();

                if ($exists) {
                    $this->line("  Skip (has statistics): {$project->project_id}");
                    Log::info('CCI statistics backfill skipped — record exists', [
                        'project_id' => $project->project_id,
                    ]);
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("  Would create statistics for: {$project->project_id}");
                    Log::info('CCI statistics backfill dry-run — would create', [
                        'project_id' => $project->project_id,
                    ]);
                    $created++;
                    continue;
                }

                ProjectCCIStatistics::create([
                    'project_id' => $project->project_id,
                ]);

                $this->info("  Created statistics for: {$project->project_id}");
                Log::info('CCI statistics backfill created empty row', [
                    'project_id' => $project->project_id,
                ]);
                $created++;
            }
        });

        $this->newLine();
        $this->line("Processed (created or would create): {$created}");
        $this->line("Skipped (already had statistics): {$skipped}");

        Log::info('CCI statistics backfill command finished', [
            'dry_run' => $dryRun,
            'created_or_would_create' => $created,
            'skipped' => $skipped,
            'single_project' => $singleProject,
        ]);

        return self::SUCCESS;
    }
}
