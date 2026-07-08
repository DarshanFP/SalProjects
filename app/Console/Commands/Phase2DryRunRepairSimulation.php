<?php

namespace App\Console\Commands;

use App\Constants\ProjectStatus;
use App\Models\OldProjects\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 Legacy Financial Data Repair — DRY-RUN ONLY.
 * Verifies repair plan without modifying the database.
 *
 * @see Documentations/V2/Budgets/Dashboards/Legacy/Legacy_Financial_System_Stabilization_Context.md
 */
class Phase2DryRunRepairSimulation extends Command
{
    protected $signature = 'legacy:phase2-dry-run-repair {--output= : Path to write report}';
    protected $description = 'Phase 2 DRY-RUN: Simulate legacy financial repair without DB changes';

    public function handle(): int
    {
        // STEP 1 — VERIFY ENVIRONMENT
        $env = config('app.env');
        if ($env === 'production') {
            $this->error('ABORT: Dry-run must only run on development database. APP_ENV=' . $env);
            return 1;
        }
        $this->info('Environment: ' . $env . ' — OK');

        $report = [];
        $report[] = '# Phase 2 Dry-Run Repair Simulation';
        $report[] = '';
        $report[] = '**Date:** ' . now()->toDateTimeString();
        $report[] = '**Environment:** ' . $env;
        $report[] = '';

        // STEP 2 — IDENTIFY REPAIR CANDIDATES
        $candidates = $this->identifyCandidates();
        $report[] = '## 1. Environment Verification';
        $report[] = '';
        $report[] = '- **APP_ENV:** ' . $env . ' (development — OK)';
        $report[] = '- **Database:** ' . config('database.connections.mysql.database');
        $report[] = '- **No database updates:** Dry-run only';
        $report[] = '';

        $report[] = '## 2. Repair Candidates Detected';
        $report[] = '';
        $report[] = '| Case | Count | Description |';
        $report[] = '|------|-------|-------------|';
        $report[] = '| A | ' . count($candidates['A']) . ' | opening_balance IS NULL, amount_sanctioned > 0, forwarded=0, local=0 |';
        $report[] = '| B | ' . count($candidates['B']) . ' | opening_balance = 0, amount_sanctioned > 0, forwarded=0, local=0 |';
        $report[] = '| C | ' . count($candidates['C']) . ' | amount_sanctioned = 0, overall_project_budget > 0, forwarded=0, local=0 |';
        $report[] = '| D | ' . count($candidates['D']) . ' | opening_balance = amount_sanctioned AND amount_forwarded > 0 |';
        $report[] = '';

        $all = collect($candidates['A'])
            ->merge($candidates['B'])
            ->merge($candidates['C'])
            ->merge($candidates['D'])
            ->unique('id')
            ->values();

        $report[] = '**Total unique repair candidates:** ' . $all->count();
        $report[] = '';

        // STEP 3 & 4 — SIMULATE REPAIR VALUES & BUILD PREVIEW TABLE
        $report[] = '## 3. Repair Preview Table';
        $report[] = '';
        $preview = $this->buildPreviewTable($all);
        $report[] = $preview['markdown'];
        $report[] = '';

        // STEP 5 — VERIFY CANONICAL RULE
        $report[] = '## 4. Canonical Rule Validation';
        $report[] = '';
        $ruleCheck = $this->verifyCanonicalRule($preview['rows']);
        $report[] = $ruleCheck['markdown'];
        $report[] = '';

        // STEP 6 — DASHBOARD IMPACT
        $report[] = '## 5. Dashboard Totals (Resolver opening_balance)';
        $report[] = '';
        $impact = $this->simulateDashboardImpact($preview['rows']);
        $report[] = $impact['markdown'];
        $report[] = '';

        // STEP 7 — RISK & RECOMMENDATION
        $report[] = '## 6. Risk Assessment';
        $report[] = '';
        $report[] = $this->riskAssessment($ruleCheck, $impact, $all->count());
        $report[] = '';

        $report[] = '## 7. Recommendation';
        $report[] = '';
        $report[] = $this->recommendation($ruleCheck, $impact, $all->count());
        $report[] = '';

        $report[] = '---';
        $report[] = '';
        $report[] = '## Final Classification';
        $report[] = '';
        $report[] = '```';
        $report[] = $this->finalClassification($ruleCheck, $impact, $all->count());
        $report[] = '```';

        $content = implode("\n", $report);

        $outputPath = $this->option('output')
            ?: base_path('Documentations/V2/Budgets/Dashboards/Legacy/Phase2_Dry_Run_Repair_Simulation.md');
        file_put_contents($outputPath, $content);
        $this->info('Report written to: ' . $outputPath);

        return 0;
    }

    private function identifyCandidates(): array
    {
        $cols = ['id', 'project_id', 'status', 'overall_project_budget', 'amount_forwarded', 'amount_sanctioned', 'opening_balance'];
        if (Schema::hasColumn('projects', 'local_contribution')) {
            $cols[] = 'local_contribution';
        }
        $projects = Project::query()->select($cols)->get();
        $hasLocal = Schema::hasColumn('projects', 'local_contribution');


        $A = [];
        $B = [];
        $C = [];
        $D = [];

        foreach ($projects as $p) {
            $ob = $p->opening_balance === null ? null : (float) $p->opening_balance;
            $as = (float) ($p->amount_sanctioned ?? 0);
            $af = (float) ($p->amount_forwarded ?? 0);
            $lc = $hasLocal ? (float) ($p->local_contribution ?? 0) : 0;
            $opb = (float) ($p->overall_project_budget ?? 0);

            if ($as > 0 && $af <= 0 && $lc <= 0) {
                if ($ob === null) {
                    $A[] = $this->projectToRow($p, $af, $lc);
                } elseif ($ob <= 0) {
                    $B[] = $this->projectToRow($p, $af, $lc);
                }
            }
            if ($as <= 0 && $opb > 0 && $af <= 0 && $lc <= 0) {
                $C[] = $this->projectToRow($p, $af, $lc);
            }
            if ($as > 0 && $af > 0 && $ob !== null && abs((float)$ob - $as) < 0.01) {
                $D[] = $this->projectToRow($p, $af, $lc);
            }
        }

        return ['A' => $A, 'B' => $B, 'C' => $C, 'D' => $D];
    }

    private function projectToRow($p, float $af, float $lc): array
    {
        return [
            'id' => $p->id,
            'project_id' => $p->project_id,
            'status' => $p->status,
            'opening_balance' => $p->opening_balance === null ? null : (float) $p->opening_balance,
            'amount_sanctioned' => (float) ($p->amount_sanctioned ?? 0),
            'amount_forwarded' => $af,
            'local_contribution' => $lc,
            'overall_project_budget' => (float) ($p->overall_project_budget ?? 0),
        ];
    }

    private function buildPreviewTable(\Illuminate\Support\Collection $all): array
    {
        $rows = [];
        foreach ($all as $p) {
            $ob = $p['opening_balance'] ?? 0;
            $as = $p['amount_sanctioned'];
            $af = $p['amount_forwarded'];
            $lc = $p['local_contribution'];
            $opb = $p['overall_project_budget'];

            $case = $this->detectCase($p);
            $sanctionedAfter = $as;
            $openingAfter = $ob;

            if ($case === 'A' || $case === 'B') {
                $openingAfter = $as;
            } elseif ($case === 'C') {
                $sanctionedAfter = $opb;
                $openingAfter = $opb;
            } elseif ($case === 'D') {
                $openingAfter = $as + $af + $lc;
            }

            $rows[] = [
                'project_id' => $p['project_id'],
                'case' => $case,
                'sanctioned_before' => $as,
                'sanctioned_after' => $sanctionedAfter,
                'forwarded' => $af,
                'local' => $lc,
                'opening_before' => $p['opening_balance'],
                'opening_after' => round($openingAfter, 2),
            ];
        }

        $md = "| project_id | case | sanctioned_before | sanctioned_after | forwarded | local | opening_before | opening_after |\n";
        $md .= "|------------|------|-------------------|------------------|-----------|-------|----------------|---------------|\n";
        foreach ($rows as $r) {
            $ob = $r['opening_before'] === null ? 'NULL' : number_format($r['opening_before'], 2);
            $md .= sprintf(
                "| %s | %s | %s | %s | %s | %s | %s | %s |\n",
                $r['project_id'],
                $r['case'],
                number_format($r['sanctioned_before'], 2),
                number_format($r['sanctioned_after'], 2),
                number_format($r['forwarded'], 2),
                number_format($r['local'], 2),
                $ob,
                number_format($r['opening_after'], 2)
            );
        }

        return ['rows' => $rows, 'markdown' => $md];
    }

    private function detectCase(array $p): string
    {
        $ob = $p['opening_balance'];
        $as = $p['amount_sanctioned'];
        $af = $p['amount_forwarded'];
        $lc = $p['local_contribution'];
        $opb = $p['overall_project_budget'];

        if ($as > 0 && $af <= 0 && $lc <= 0) {
            if ($ob === null) return 'A';
            if ((float)$ob <= 0) return 'B';
        }
        if ($as <= 0 && $opb > 0 && $af <= 0 && $lc <= 0) return 'C';
        if ($as > 0 && $af > 0 && $ob !== null && abs((float)$ob - $as) < 0.01) return 'D';
        return '?';
    }

    private function verifyCanonicalRule(array $rows): array
    {
        $violations = [];
        foreach ($rows as $r) {
            $expected = $r['sanctioned_after'] + $r['forwarded'] + $r['local'];
            if (abs($r['opening_after'] - $expected) > 0.01) {
                $violations[] = [
                    'project_id' => $r['project_id'],
                    'opening_after' => $r['opening_after'],
                    'expected' => round($expected, 2),
                ];
            }
        }

        $md = $violations === []
            ? 'All simulated values satisfy: `opening_balance_after = amount_sanctioned_after + amount_forwarded + local_contribution`'
            : '**Violations detected:** ' . count($violations) . "\n\n| project_id | opening_after | expected |\n|------------|---------------|----------|\n"
                . implode("\n", array_map(fn ($v) => "| {$v['project_id']} | {$v['opening_after']} | {$v['expected']} |", $violations));

        return ['violations' => $violations, 'markdown' => $md];
    }

    private function simulateDashboardImpact(array $rows): array
    {
        $ids = array_column($rows, 'project_id');
        $approved = Project::query()
            ->whereIn('project_id', $ids)
            ->whereIn('status', ProjectStatus::APPROVED_STATUSES)
            ->get()
            ->keyBy('project_id');

        $beforeSum = 0;
        $afterSum = 0;
        $rowMap = collect($rows)->keyBy('project_id');
        foreach ($rowMap as $pid => $r) {
            if ($approved->has($pid)) {
                $ob = $r['opening_before'];
                $beforeSum += $ob === null ? 0 : (float) $ob;
                $afterSum += $r['opening_after'];
            }
        }

        $md = "- **Before repair (resolver opening_balance for affected approved projects):** " . number_format($beforeSum, 2) . "\n";
        $md .= "- **After repair:** " . number_format($afterSum, 2) . "\n";
        $md .= "- **Delta:** " . number_format($afterSum - $beforeSum, 2);

        return ['before' => $beforeSum, 'after' => $afterSum, 'markdown' => $md];
    }

    private function riskAssessment(array $ruleCheck, array $impact, int $count): string
    {
        $parts = [];
        if ($ruleCheck['violations'] !== []) {
            $parts[] = '- Canonical rule violations in simulation: ' . count($ruleCheck['violations']) . ' — review repair logic.';
        }
        if ($count === 0) {
            $parts[] = '- No repair candidates: low risk, nothing to execute.';
        } else {
            $delta = $impact['after'] - $impact['before'];
            $parts[] = '- Dashboard total change: ' . number_format($delta, 2) . ' (affects resolver SUM(opening_balance) for approved projects).';
            if (abs($delta) > 0) {
                $parts[] = '- Budget totals will change in Coordinator/Provincial dashboards.';
            }
        }
        return implode("\n", $parts) ?: '- No significant risks identified.';
    }

    private function recommendation(array $ruleCheck, array $impact, int $count): string
    {
        if ($ruleCheck['violations'] !== []) {
            return 'Fix simulation logic before executing Phase 2. Do not proceed.';
        }
        if ($count === 0) {
            return 'No repair needed. Database is consistent.';
        }
        return 'Review the repair preview table. If values look correct, consider executing Phase 2 repair with a production-safe migration.';
    }

    private function finalClassification(array $ruleCheck, array $impact, int $count): string
    {
        if ($ruleCheck['violations'] !== []) {
            return 'NOT_SAFE_TO_EXECUTE';
        }
        if ($count === 0) {
            return 'SAFE_TO_EXECUTE_PHASE_2';
        }
        $delta = abs($impact['after'] - $impact['before']);
        return $delta > 0 ? 'PROCEED_WITH_WARNINGS' : 'SAFE_TO_EXECUTE_PHASE_2';
    }
}
