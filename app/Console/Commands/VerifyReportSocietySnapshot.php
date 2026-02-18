<?php

namespace App\Console\Commands;

use App\Models\Reports\Monthly\DPReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Wave 6A Phase 3: Data verification after backfill.
 * Run after reports:backfill-society-snapshot. Do NOT proceed to Phase 4 until this passes.
 */
class VerifyReportSocietySnapshot extends Command
{
    protected $signature = 'reports:verify-society-snapshot {--sample=50 : Random sample size for match check}';

    protected $description = 'Wave 6A Phase 3: Verify report society snapshot (NULL count, sample match).';

    public function handle(): int
    {
        $sampleSize = (int) $this->option('sample');
        if ($sampleSize < 1) {
            $sampleSize = 50;
        }

        $this->info('Wave 6A Phase 3 — Report society snapshot verification');
        $this->newLine();

        // 1. Count reports where society_id IS NULL → must be 0
        $nullCount = DPReport::whereNull('society_id')->count();
        $this->line("1. Reports with society_id IS NULL: {$nullCount}");
        if ($nullCount > 0) {
            $this->error('   FAIL: Must be 0 before Phase 4. Re-run backfill or fix data.');
            Log::warning('Wave 6A Phase 3 verification failed: society_id NULL count > 0', ['count' => $nullCount]);
            return self::FAILURE;
        }
        $this->info('   OK.');
        $this->newLine();

        // 2. Random sample: report.society_id vs project.society_id must match
        $reports = DPReport::with('project')
            ->whereNotNull('society_id')
            ->inRandomOrder()
            ->limit($sampleSize)
            ->get();

        $mismatches = 0;
        foreach ($reports as $report) {
            if (!$report->project) {
                $mismatches++;
                continue;
            }
            if ((int) $report->society_id !== (int) $report->project->society_id) {
                $mismatches++;
                $this->warn("   Mismatch report_id={$report->report_id} report.society_id={$report->society_id} project.society_id={$report->project->society_id}");
            }
        }
        $this->line("2. Random sample (n={$reports->count()}): society_id match with project");
        if ($mismatches > 0) {
            $this->error("   FAIL: {$mismatches} mismatch(es) in sample.");
            Log::warning('Wave 6A Phase 3 verification: society_id mismatches in sample', ['mismatches' => $mismatches]);
            return self::FAILURE;
        }
        $this->info('   OK.');
        $this->newLine();

        $this->info('Phase 3 verification passed. Safe to run Phase 4 migration (NOT NULL + FK).');
        Log::info('Wave 6A Phase 3 verification passed');
        return self::SUCCESS;
    }
}
