<?php

namespace App\Console\Commands;

use App\Constants\ProjectType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/**
 * Phase 11: Automated pre-flight checks before manual QA on staging.
 * Verifies config, views, and routes for all 12 monthly report project types.
 */
class ReportsQaReadinessCheck extends Command
{
    protected $signature = 'reports:qa-readiness {--json : Output machine-readable JSON}';

    protected $description = 'Phase 11: Verify report pipeline readiness for all 12 project types (config, SOA views, routes).';

    /** @var array<string, string> Must match statements_of_account.blade.php router */
    private const SOA_TYPE_MAP = [
        'Development Projects' => 'development_projects',
        'NEXT PHASE - DEVELOPMENT PROPOSAL' => 'development_projects',
        'Livelihood Development Projects' => 'development_projects',
        'Individual - Livelihood Application' => 'individual_livelihood',
        'Individual - Access to Health' => 'individual_health',
        'Institutional Ongoing Group Educational proposal' => 'institutional_education',
        'Individual - Ongoing Educational support' => 'individual_ongoing_education',
        'Individual - Initial - Educational support' => 'individual_education',
        'Residential Skill Training Proposal 2' => 'development_projects',
        'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' => 'development_projects',
        'CHILD CARE INSTITUTION' => 'development_projects',
        'Rural-Urban-Tribal' => 'development_projects',
    ];

    /** @var array<string, string> Per-type extra QA focus (Phase 11 plan) */
    private const EXTRA_CHECKS = [
        'Development Projects' => 'Overview amount matches resolver (Phase 4)',
        'Livelihood Development Projects' => 'Livelihood annexure section saved',
        'Residential Skill Training Proposal 2' => 'Trainee profiles (RST)',
        'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' => 'Inmate profiles (CIC)',
        'Institutional Ongoing Group Educational proposal' => 'Age profiles; institutional_education SOA',
        'Individual - Livelihood Application' => 'Contribution columns in SOA (ILP)',
        'Individual - Access to Health' => 'Contribution columns in SOA (IAH)',
        'Individual - Ongoing Educational support' => 'Contribution columns in SOA (IES)',
        'Individual - Initial - Educational support' => 'Contribution columns in SOA (IIES)',
        'CHILD CARE INSTITUTION' => 'Project edit without statistics (Phase 3)',
        'NEXT PHASE - DEVELOPMENT PROPOSAL' => 'Edit + budget fallback (Phase 3/4)',
        'Rural-Urban-Tribal' => 'Phase-based SOA rows (Edu-RUT)',
    ];

    public function handle(): int
    {
        $types = ProjectType::all();
        $budgetConfig = config('budget.field_mappings', []);
        $rows = [];
        $failures = 0;

        foreach ($types as $type) {
            $row = [
                'type' => $type,
                'budget_config' => isset($budgetConfig[$type]),
                'soa_partial' => $this->soaPartialExists($type),
                'extra_check' => self::EXTRA_CHECKS[$type] ?? 'Standard workflow',
            ];
            $row['ready'] = $row['budget_config'] && $row['soa_partial'];
            if (!$row['ready']) {
                $failures++;
            }
            $rows[] = $row;
        }

        $routeChecks = $this->checkRoutes();
        $routeFailures = count(array_filter($routeChecks, fn ($r) => !$r['ok']));
        $failures += $routeFailures;

        if ($this->option('json')) {
            $this->line(json_encode([
                'types' => $rows,
                'routes' => $routeChecks,
                'type_failures' => count(array_filter($rows, fn ($r) => !$r['ready'])),
                'route_failures' => $routeFailures,
            ], JSON_PRETTY_PRINT));

            return $failures > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->info('Phase 11 — Report QA Readiness (automated pre-flight)');
        $this->newLine();
        $this->info('Project types (' . count($types) . '/12):');

        $tableRows = [];
        foreach ($rows as $row) {
            $tableRows[] = [
                $this->truncate($row['type'], 52),
                $row['budget_config'] ? 'yes' : 'MISSING',
                $row['soa_partial'] ? 'yes' : 'MISSING',
                $row['ready'] ? 'OK' : 'FAIL',
            ];
        }
        $this->table(['Project type', 'Budget config', 'SOA partial', 'Status'], $tableRows);

        $this->newLine();
        $this->info('Routes & services:');
        foreach ($routeChecks as $check) {
            $this->line(sprintf(
                '  %s %s',
                $check['ok'] ? '✓' : '✗',
                $check['label']
            ));
        }

        $this->newLine();
        $readyCount = count(array_filter($rows, fn ($r) => $r['ready']));
        $this->line("Types ready: {$readyCount}/" . count($types));
        $this->line('Manual QA still required: execute Documentations/Reports/Phase11_Manual_QA_Matrix.md on staging.');

        return ($failures > 0 || $routeFailures > 0) ? self::FAILURE : self::SUCCESS;
    }

    private function soaPartialExists(string $projectType): bool
    {
        $partialName = self::SOA_TYPE_MAP[$projectType] ?? 'fallback';
        $path = "reports.monthly.partials.statements_of_account.{$partialName}";

        if ($partialName === 'fallback') {
            $path = 'reports.monthly.partials.statements_of_account.development_projects';
        }

        return View::exists($path);
    }

    /**
     * @return list<array{label: string, ok: bool}>
     */
    private function checkRoutes(): array
    {
        $routes = [
            'monthly.report.create' => 'Create form',
            'monthly.report.store' => 'Store / draft save',
            'monthly.report.edit' => 'Edit report',
            'monthly.report.submit' => 'Submit to provincial',
            'monthly.report.revert' => 'Revert workflow',
            'monthly.report.approve' => 'Coordinator approve',
            'aggregated.quarterly.create' => 'Aggregated quarterly create',
            'quarterly.developmentProject.index' => 'Legacy quarterly (auth)',
        ];

        $checks = [];
        foreach ($routes as $name => $label) {
            $checks[] = [
                'label' => $label . " ({$name})",
                'ok' => Route::has($name),
            ];
        }

        $checks[] = [
            'label' => 'MonthlyReportCreateAuthorization service',
            'ok' => class_exists(\App\Services\Reports\MonthlyReportCreateAuthorization::class),
        ];
        $checks[] = [
            'label' => 'DPReport::createWithProjectSnapshot()',
            'ok' => method_exists(\App\Models\Reports\Monthly\DPReport::class, 'createWithProjectSnapshot'),
        ];
        $checks[] = [
            'label' => 'ReportResourceLookup (Phase 9 graceful 404)',
            'ok' => class_exists(\App\Support\Reports\ReportResourceLookup::class),
        ];

        return $checks;
    }

    private function truncate(string $value, int $max): string
    {
        return strlen($value) <= $max ? $value : substr($value, 0, $max - 3) . '...';
    }
}
