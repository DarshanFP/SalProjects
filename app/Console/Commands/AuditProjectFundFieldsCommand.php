<?php

namespace App\Console\Commands;

use App\Services\Budget\ApprovedProjectFundFieldAudit;
use Illuminate\Console\Command;

/**
 * Phase 4: Audit approved projects for fund-field discrepancies (dry-run report).
 *
 * @see Documentations/Reports/Reporting_System_Phase_Wise_Implementation_Plan.md § Phase 4
 */
class AuditProjectFundFieldsCommand extends Command
{
    protected $signature = 'reports:audit-project-fund-fields
                            {--project= : Limit to a single project_id}
                            {--output= : Path to write markdown report}';

    protected $description = 'Audit approved projects for amount_sanctioned / opening_balance discrepancies';

    public function handle(ApprovedProjectFundFieldAudit $audit): int
    {
        $projectFilter = $this->option('project');
        $query = $audit->approvedProjectsQuery($projectFilter);

        $this->info('Scanning approved projects' . ($projectFilter ? " ({$projectFilter})" : '') . '…');

        $rows = [];
        $query->orderBy('project_id')->chunk(100, function ($projects) use ($audit, &$rows) {
            foreach ($projects as $project) {
                $analysis = $audit->analyze($project);
                if ($analysis['issues'] === []) {
                    continue;
                }
                $rows[] = $analysis;
            }
        });

        $this->info('Found ' . count($rows) . ' project(s) with fund-field issues.');

        $report = $this->buildMarkdownReport($rows);
        $outputPath = $this->option('output')
            ?: base_path('Documentations/Reports/Phase4_Project_Fund_Fields_Audit_' . now()->format('Y-m-d') . '.md');

        file_put_contents($outputPath, $report);
        $this->info('Report written to: ' . $outputPath);

        if ($rows !== []) {
            $this->table(
                ['project_id', 'type', 'issues', 'stored_sanctioned', 'derived_sanctioned'],
                array_map(fn ($row) => [
                    $row['project']->project_id,
                    $row['project']->project_type,
                    implode(', ', $row['issues']),
                    number_format($row['stored']['amount_sanctioned'], 2),
                    number_format($row['derived']['amount_sanctioned'], 2),
                ], $rows)
            );
        }

        return 0;
    }

    /**
     * @param list<array{project: \App\Models\OldProjects\Project, stored: array, derived: array, issues: list<string>}> $rows
     */
    private function buildMarkdownReport(array $rows): string
    {
        $lines = [
            '# Phase 4 — Project Fund Fields Audit',
            '',
            '**Date:** ' . now()->toDateTimeString(),
            '**Environment:** ' . config('app.env'),
            '**Database:** ' . config('database.connections.mysql.database'),
            '',
            '**Total projects with issues:** ' . count($rows),
            '',
            '| project_id | project_type | issues | stored_sanctioned | derived_sanctioned | stored_opening | derived_opening |',
            '|------------|--------------|--------|-------------------|--------------------|----------------|-----------------|',
        ];

        foreach ($rows as $row) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s | %s | %s |',
                $row['project']->project_id,
                $row['project']->project_type,
                implode(', ', $row['issues']),
                number_format($row['stored']['amount_sanctioned'], 2),
                number_format($row['derived']['amount_sanctioned'], 2),
                number_format($row['stored']['opening_balance'], 2),
                number_format($row['derived']['opening_balance'], 2)
            );
        }

        $lines[] = '';
        $lines[] = 'Run repair (dry-run first): `php artisan reports:repair-project-fund-fields --dry-run`';

        return implode("\n", $lines);
    }
}
