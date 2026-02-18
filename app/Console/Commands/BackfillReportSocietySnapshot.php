<?php

namespace App\Console\Commands;

use App\Models\Reports\Monthly\DPReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Wave 6A Phase 2: Idempotent backfill of report society snapshot from related project.
 * Only updates rows where society_id IS NULL. Re-runnable safely.
 */
class BackfillReportSocietySnapshot extends Command
{
    protected $signature = 'reports:backfill-society-snapshot {--chunk=200 : Chunk size}';

    protected $description = 'Wave 6A Phase 2: Backfill report society_id, society_name, province_id from project. Idempotent.';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        if ($chunkSize < 1) {
            $chunkSize = 200;
        }

        $this->info('Wave 6A Phase 2 — Backfill report society snapshot (only rows where society_id IS NULL)');
        $this->newLine();

        $totalProcessed = 0;
        $totalSkipped = 0;
        $totalFailed = 0;

        DPReport::with('project')
            ->whereNull('society_id')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($reports) use (&$totalProcessed, &$totalSkipped, &$totalFailed) {
                DB::transaction(function () use ($reports, &$totalProcessed, &$totalSkipped, &$totalFailed) {
                    foreach ($reports as $report) {
                        if (!$report->project) {
                            Log::error('Wave 6A backfill: report has no project', [
                                'report_id' => $report->report_id,
                            ]);
                            $this->warn("  Skipped (no project): {$report->report_id}");
                            $totalSkipped++;
                            continue;
                        }

                        $report->society_id = $report->project->society_id;
                        $report->society_name = $report->project->society_name;
                        $report->province_id = $report->project->province_id;
                        $report->save();
                        $totalProcessed++;
                    }
                });
            });

        $this->line("Total processed: {$totalProcessed}");
        $this->line("Total skipped (no project): {$totalSkipped}");
        $this->line("Total failed: {$totalFailed}");

        Log::info('Wave 6A Phase 2 backfill completed', [
            'processed' => $totalProcessed,
            'skipped' => $totalSkipped,
            'failed' => $totalFailed,
        ]);

        $this->newLine();
        $this->info('Backfill finished. Run Phase 3 verification before Phase 4 (NOT NULL + FK).');
        return self::SUCCESS;
    }
}
