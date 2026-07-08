<?php

namespace App\Console\Commands;

use App\Constants\ProjectType;
use App\Models\OldProjects\IGE\ProjectIGEBudget;
use App\Models\Reports\Monthly\DPAccountDetail;
use App\Models\Reports\Monthly\DPReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RepairIgeParticularsCommand extends Command
{
    protected $signature = 'reports:repair-ige-particulars
                            {--dry-run : List reports and rows only; do not update database}';

    protected $description = 'Data repair: backfill missing particulars for IGE monthly report account details (Phase 13 optional repair).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Repairing missing particulars for IGE monthly report account details');
        if ($dryRun) {
            $this->warn('DRY RUN — no records will be updated.');
        }
        $this->newLine();

        $igeReports = DPReport::where('project_type', ProjectType::INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL)->get();

        if ($igeReports->isEmpty()) {
            $this->info('No IGE monthly reports found.');
            return 0;
        }

        $repairedCount = 0;

        foreach ($igeReports as $report) {
            $details = DPAccountDetail::where('report_id', $report->report_id)
                ->where('is_budget_row', true)
                ->get();

            if ($details->isEmpty()) {
                continue;
            }

            $budgets = ProjectIGEBudget::where('project_id', $report->project_id)->get()->values();

            if ($budgets->isEmpty()) {
                continue;
            }

            foreach ($details as $index => $detail) {
                if (empty(trim((string) $detail->particulars)) && isset($budgets[$index])) {
                    $correctParticular = $budgets[$index]->name;

                    if ($dryRun) {
                        $this->line("  [Dry Run] Report {$report->report_id} Row {$detail->account_detail_id}: set particulars to '{$correctParticular}'");
                    } else {
                        $detail->particulars = $correctParticular;
                        $detail->save();
                        $this->line("  Repaired Report {$report->report_id} Row {$detail->account_detail_id}: set particulars to '{$correctParticular}'");
                    }

                    $repairedCount++;
                }
            }
        }

        $this->newLine();
        $this->info("Completed. Total account detail rows repaired: {$repairedCount}");

        return 0;
    }
}
