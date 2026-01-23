<?php

namespace App\Services;

use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectTimeframe;
use App\Models\Reports\Monthly\DPReport;
use App\Services\Budget\BudgetCalculationService;

/**
 * Service for Provincial monitoring and analysis of monthly reports.
 *
 * Provides checks for:
 * - Objectives & Activities: scheduled but not reported, reported but not scheduled, ad-hoc
 * - Budget: overspend, negative balance, utilisation
 * - Project-type-specific: LDP, IGE, RST, CIC, Individual, Development/CCI/Rural-Urban-Tribal, Beneficiary
 *
 * @see Documentations/V1/Reports/MONITORING/Provincial_Monthly_Report_Monitoring_Guide.md
 * @see Documentations/V1/Reports/MONITORING/Provincial_Monthly_Report_Monitoring_Implementation_Plan.md
 */
class ReportMonitoringService
{
    /**
     * Report month (1–12) from report_month_year. Null if not set.
     */
    public function getReportMonth(DPReport $report): ?int
    {
        if (! $report->report_month_year) {
            return null;
        }

        return (int) \Carbon\Carbon::parse($report->report_month_year)->format('n');
    }

    /**
     * Activities scheduled for the report month in the project plan but not reported.
     * Uses project.objectives.activities.timeframes (month, is_active=1) and
     * report objectives' DPActivity.project_activity_id.
     *
     * @return array<int, array{objective: string, activity: string, activity_id: string}>
     */
    public function getActivitiesScheduledButNotReported(DPReport $report): array
    {
        $reportMonth = $this->getReportMonth($report);
        if ($reportMonth === null) {
            return [];
        }

        $project = $report->project;
        if (! $project) {
            return [];
        }

        $reportedIds = $report->objectives
            ->flatMap->activities
            ->pluck('project_activity_id')
            ->filter()
            ->values()
            ->toArray();

        $result = [];
        foreach ($project->objectives ?? [] as $objective) {
            foreach ($objective->activities ?? [] as $activity) {
                $scheduledForReportMonth = collect($activity->timeframes ?? [])->contains(
                    fn ($tf) => (int) $tf->month == $reportMonth && (int) $tf->is_active === 1
                );
                if ($scheduledForReportMonth && ! in_array($activity->activity_id, $reportedIds)) {
                    $result[] = [
                        'objective' => (string) ($objective->objective ?? ''),
                        'activity' => (string) ($activity->activity ?? ''),
                        'activity_id' => (string) $activity->activity_id,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Activities reported in the report that were not scheduled for the report month in the project plan.
     *
     * @return array<int, array{objective: string, activity: string, reported_month: mixed, planned_months: array<int, mixed>}>
     */
    public function getActivitiesReportedButNotScheduled(DPReport $report): array
    {
        $reportMonth = $this->getReportMonth($report);
        if ($reportMonth === null) {
            return [];
        }

        $result = [];
        foreach ($report->objectives ?? [] as $dpObjective) {
            foreach ($dpObjective->activities ?? [] as $dpActivity) {
                $pid = $dpActivity->project_activity_id;
                if ($pid === null || $pid === '') {
                    continue;
                }
                $scheduledForReportMonth = ProjectTimeframe::where('activity_id', $pid)
                    ->where('is_active', 1)
                    ->where('month', $reportMonth)
                    ->exists();
                if ($scheduledForReportMonth) {
                    continue;
                }
                $plannedMonths = ProjectTimeframe::where('activity_id', $pid)
                    ->where('is_active', 1)
                    ->pluck('month')
                    ->values()
                    ->toArray();

                $result[] = [
                    'objective' => (string) ($dpObjective->objective ?? ''),
                    'activity' => (string) ($dpActivity->activity ?? ''),
                    'reported_month' => $dpActivity->month,
                    'planned_months' => $plannedMonths,
                ];
            }
        }

        return $result;
    }

    /**
     * Ad-hoc activities: reported with no project_activity_id (e.g. "Add Other Activity").
     *
     * @return array<int, array{activity: string, month: mixed, objective: string}>
     */
    public function getAdhocActivities(DPReport $report): array
    {
        $result = [];
        foreach ($report->objectives ?? [] as $dpObjective) {
            foreach ($dpObjective->activities ?? [] as $dpActivity) {
                $pid = $dpActivity->project_activity_id;
                if ($pid !== null && $pid !== '') {
                    continue;
                }
                $result[] = [
                    'activity' => (string) ($dpActivity->activity ?? ''),
                    'month' => $dpActivity->month,
                    'objective' => (string) ($dpObjective->objective ?? ''),
                ];
            }
        }

        return $result;
    }

    /**
     * Activities scheduled for the report month but not reported, grouped by project objective.
     * Only treats as "reported" a DPActivity with hasUserFilledData() and matching project_activity_id.
     * Includes objectives entirely missing from the report.
     *
     * @return array<int, array{objective: string, objective_id: string, activities: array<int, array{activity: string, activity_id: string}>}>
     */
    public function getActivitiesScheduledButNotReportedGroupedByObjective(DPReport $report): array
    {
        $reportMonth = $this->getReportMonth($report);
        if ($reportMonth === null) {
            return [];
        }

        $project = $report->project;
        if (! $project) {
            return [];
        }

        $reportedProjectActivityIds = $report->objectives
            ->flatMap->activities
            ->filter(fn ($a) => $a->hasUserFilledData())
            ->pluck('project_activity_id')
            ->filter(fn ($v) => $v !== null && trim((string) $v) !== '')
            ->values()
            ->toArray();

        $result = [];
        foreach ($project->objectives ?? [] as $projObjective) {
            $activitiesNotReported = [];
            foreach ($projObjective->activities ?? [] as $projActivity) {
                $scheduledForReportMonth = collect($projActivity->timeframes ?? [])->contains(
                    fn ($tf) => (int) $tf->month == $reportMonth && (int) $tf->is_active === 1
                );
                if ($scheduledForReportMonth && ! in_array($projActivity->activity_id, $reportedProjectActivityIds, true)) {
                    $activitiesNotReported[] = [
                        'activity' => (string) ($projActivity->activity ?? ''),
                        'activity_id' => (string) $projActivity->activity_id,
                    ];
                }
            }
            if (count($activitiesNotReported) > 0) {
                $result[] = [
                    'objective' => (string) ($projObjective->objective ?? ''),
                    'objective_id' => (string) $projObjective->objective_id,
                    'activities' => $activitiesNotReported,
                ];
            }
        }

        return $result;
    }

    /**
     * For each reported DPActivity with hasUserFilledData(), whether it is scheduled_reported or not_scheduled_reported.
     * scheduled_reported: project_activity_id matches a ProjectTimeframe for report month with is_active=1.
     * not_scheduled_reported: ad-hoc (empty project_activity_id) or not scheduled for report month.
     *
     * @return array<string, 'scheduled_reported'|'not_scheduled_reported'>
     */
    public function getReportedActivityScheduleStatus(DPReport $report): array
    {
        $reportMonth = $this->getReportMonth($report);
        $out = [];

        foreach ($report->objectives ?? [] as $dpObjective) {
            foreach ($dpObjective->activities ?? [] as $dpActivity) {
                if (! $dpActivity->hasUserFilledData()) {
                    continue;
                }
                $aid = $dpActivity->activity_id ?? null;
                if ($aid === null || $aid === '') {
                    continue;
                }
                $pid = $dpActivity->project_activity_id;
                if ($pid === null || trim((string) $pid) === '') {
                    $out[(string) $aid] = 'not_scheduled_reported';
                    continue;
                }
                $scheduled = ProjectTimeframe::where('activity_id', $pid)
                    ->where('is_active', 1)
                    ->where('month', $reportMonth)
                    ->exists();
                $out[(string) $aid] = $scheduled ? 'scheduled_reported' : 'not_scheduled_reported';
            }
        }

        return $out;
    }

    /**
     * Per-objective activity monitoring for provincial view: SCHEDULED–REPORTED, SCHEDULED–NOT REPORTED, NOT SCHEDULED–REPORTED.
     * Only includes activities relevant to the report month. Keys by DP objective_id for use in the view.
     *
     * @return array<string, array{scheduled_reported: array, scheduled_not_reported: array, not_scheduled_reported: array}>
     */
    public function getMonitoringPerObjective(DPReport $report): array
    {
        $reportMonth = $this->getReportMonth($report);
        $project = $report->project;
        if ($reportMonth === null || ! $project) {
            return [];
        }

        $projectObjectivesById = collect($project->objectives ?? [])->keyBy('objective_id');

        $out = [];
        foreach ($report->objectives ?? [] as $dpObjective) {
            $scheduledReported = [];
            $scheduledNotReported = [];
            $notScheduledReported = [];

            $reportedProjectActivityIds = $dpObjective->activities
                ->pluck('project_activity_id')
                ->filter(fn ($v) => $v !== null && trim((string) $v) !== '')
                ->values()
                ->toArray();

            $projObjective = $dpObjective->project_objective_id
                ? ($projectObjectivesById[$dpObjective->project_objective_id] ?? null)
                : null;

            if ($projObjective && $projObjective->activities) {
                foreach ($projObjective->activities as $projActivity) {
                    $scheduledForMonth = collect($projActivity->timeframes ?? [])->contains(
                        fn ($tf) => (int) $tf->month === $reportMonth && (int) ($tf->is_active ?? 0) === 1
                    );
                    if (! $scheduledForMonth) {
                        continue;
                    }
                    $aid = $projActivity->activity_id ?? null;
                    $name = (string) ($projActivity->activity ?? '');
                    if (in_array($aid, $reportedProjectActivityIds, true)) {
                        $scheduledReported[] = ['activity' => $name];
                    } else {
                        $scheduledNotReported[] = ['activity' => $name];
                    }
                }
            }

            foreach ($dpObjective->activities ?? [] as $dpActivity) {
                $pid = $dpActivity->project_activity_id;
                $actName = (string) ($dpActivity->activity ?? 'Ad-hoc');
                $reportedMonth = $dpActivity->month;

                if ($pid === null || trim((string) $pid) === '') {
                    $notScheduledReported[] = ['activity' => $actName, 'reported_month' => $reportedMonth, 'adhoc' => true];
                    continue;
                }

                $scheduledForMonth = ProjectTimeframe::where('activity_id', $pid)
                    ->where('is_active', 1)
                    ->where('month', $reportMonth)
                    ->exists();
                if (! $scheduledForMonth) {
                    $notScheduledReported[] = ['activity' => $actName, 'reported_month' => $reportedMonth, 'adhoc' => false];
                }
            }

            $out[$dpObjective->objective_id] = [
                'scheduled_reported' => $scheduledReported,
                'scheduled_not_reported' => $scheduledNotReported,
                'not_scheduled_reported' => $notScheduledReported,
            ];
        }

        return $out;
    }

    /**
     * Budget rows where total_expenses exceeds amount_sanctioned or total_amount.
     * Only rows with is_budget_row = 1. Cap: amount_sanctioned; if total_amount is also exceeded, use the higher excess.
     *
     * @return array<int, array{particulars: string, amount_sanctioned: float, total_expenses: float, excess: float}>
     */
    public function getBudgetOverspendRows(DPReport $report): array
    {
        $result = [];
        foreach ($report->accountDetails ?? [] as $row) {
            if ((int) ($row->is_budget_row ?? 0) !== 1) {
                continue;
            }
            $te = (float) ($row->total_expenses ?? 0);
            $as = (float) ($row->amount_sanctioned ?? 0);
            $ta = (float) ($row->total_amount ?? 0);
            $excess = 0.0;
            if ($te > $as) {
                $excess = $te - $as;
            } elseif ($te > $ta) {
                $excess = $te - $ta;
            }
            if ($excess > 0) {
                $result[] = [
                    'particulars' => (string) ($row->particulars ?? ''),
                    'amount_sanctioned' => $as,
                    'total_expenses' => $te,
                    'excess' => $excess,
                ];
            }
        }

        return $result;
    }

    /**
     * Rows with negative balance_amount. Optionally: sum(balance_amount) < 0 flagged in utilisation.
     *
     * @return array<int, array{particulars: string, balance_amount: float}>
     */
    public function getNegativeBalanceRows(DPReport $report): array
    {
        $result = [];
        foreach ($report->accountDetails ?? [] as $row) {
            $ba = (float) ($row->balance_amount ?? 0);
            if ($ba < 0) {
                $result[] = [
                    'particulars' => (string) ($row->particulars ?? ''),
                    'balance_amount' => $ba,
                ];
            }
        }

        return $result;
    }

    /**
     * Overall utilisation: total_sanctioned, total_expenses, utilisation_percent, alerts.
     * total_sanctioned: report->amount_sanctioned_overview or sum(amount_sanctioned) for is_budget_row=1.
     * alerts: high_utilization (>90%), negative_balance, overspend_row; optionally high_expenses_this_month (§6.4).
     *
     * @return array{total_sanctioned: float, total_expenses: float, utilisation_percent: float, alerts: array<int, string>}
     */
    public function getBudgetUtilisationSummary(DPReport $report): array
    {
        $overview = (float) ($report->amount_sanctioned_overview ?? 0);
        $byBudgetRows = collect($report->accountDetails ?? [])
            ->filter(fn ($r) => (int) ($r->is_budget_row ?? 0) === 1)
            ->sum('amount_sanctioned');
        $totalSanctioned = $overview > 0 ? $overview : (float) $byBudgetRows;

        $totalExpenses = (float) collect($report->accountDetails ?? [])->sum('total_expenses');
        $utilisationPercent = $totalSanctioned > 0 ? ($totalExpenses / $totalSanctioned) * 100 : 0.0;

        $overspend = $this->getBudgetOverspendRows($report);
        $negative = $this->getNegativeBalanceRows($report);

        $alerts = [];
        if ($utilisationPercent > 90) {
            $alerts[] = 'high_utilization';
        }
        if (count($negative) > 0) {
            $alerts[] = 'negative_balance';
        }
        if (count($overspend) > 0) {
            $alerts[] = 'overspend_row';
        }

        // Optional §6.4: expenses_this_month >> expenses_last_month and large in absolute terms
        $thisMonth = (float) collect($report->accountDetails ?? [])->sum('expenses_this_month');
        $lastMonth = (float) collect($report->accountDetails ?? [])->sum('expenses_last_month');
        if ($lastMonth > 0 && $thisMonth >= 10000 && $thisMonth > 2.5 * $lastMonth) {
            $alerts[] = 'high_expenses_this_month';
        }

        return [
            'total_sanctioned' => $totalSanctioned,
            'total_expenses' => $totalExpenses,
            'utilisation_percent' => $utilisationPercent,
            'alerts' => $alerts,
        ];
    }

    /**
     * LDP annexure checks. Requires report->annexures.
     *
     * @return array{alerts: array<int, string>, meta: array<string, mixed>}
     */
    public function getLdpAnnexureChecks(DPReport $report): array
    {
        $alerts = [];
        $meta = ['count' => 0];

        $annexures = $report->annexures ?? collect();
        $meta['count'] = $annexures->count();

        if ($meta['count'] === 0) {
            $alerts[] = 'LDP report has no impact annexure entries.';
            return ['alerts' => $alerts, 'meta' => $meta];
        }

        $periodStart = $report->account_period_start ? \Carbon\Carbon::parse($report->account_period_start) : null;
        $periodEnd = $report->account_period_end ? \Carbon\Carbon::parse($report->account_period_end) : null;
        $supportOutside = false;
        $incomplete = false;

        foreach ($annexures as $a) {
            if ($periodStart && $periodEnd && $a->dla_support_date) {
                try {
                    $d = \Carbon\Carbon::parse($a->dla_support_date);
                    if ($d->lt($periodStart) || $d->gt($periodEnd)) {
                        $supportOutside = true;
                    }
                } catch (\Throwable $e) {
                    // ignore unparseable date
                }
            }
            $impactEmpty = $a->dla_impact === null || trim((string) $a->dla_impact) === '';
            $amountZero = $a->dla_amount_sanctioned === null || (float) $a->dla_amount_sanctioned <= 0;
            if ($impactEmpty || $amountZero) {
                $incomplete = true;
            }
        }
        if ($supportOutside) {
            $alerts[] = 'Support date outside account period.';
        }
        if ($incomplete) {
            $alerts[] = 'Incomplete impact entry.';
        }

        return ['alerts' => $alerts, 'meta' => $meta];
    }

    /**
     * IGE age profile checks. Requires report->rqis_age_profile.
     *
     * @return array{alerts: array<int, string>, meta: array<string, mixed>}
     */
    public function getIgeAgeProfileChecks(DPReport $report): array
    {
        $alerts = [];
        $meta = [];

        $profiles = $report->rqis_age_profile ?? collect();
        $ageGroups = [
            'Children below 5 years',
            'Children between 6 to 10 years',
            'Children between 11 to 15 years',
            '16 and above',
        ];

        $found = [];
        foreach ($profiles as $p) {
            $ag = (string) ($p->age_group ?? '');
            $ed = (string) ($p->education ?? '');
            if ($ag === 'All Categories' && $ed === 'Grand Total') {
                $meta['grand_total_present_academic_year'] = (int) ($p->present_academic_year ?? 0);
            }
            if (in_array($ag, $ageGroups, true)) {
                $found[$ag] = true;
            }
        }

        if (! isset($meta['grand_total_present_academic_year'])) {
            $alerts[] = 'Age profile: Grand Total missing.';
        }

        foreach ($ageGroups as $ag) {
            if (empty($found[$ag])) {
                $alerts[] = "Age profile: missing age group {$ag}.";
            }
        }

        $gt = $meta['grand_total_present_academic_year'] ?? null;
        $tb = $report->total_beneficiaries !== null ? (int) $report->total_beneficiaries : null;
        if ($gt !== null && $tb !== null && $gt !== $tb) {
            $alerts[] = 'Grand Total (present academic year) ≠ Total beneficiaries in basic info.';
        }

        return ['alerts' => $alerts, 'meta' => $meta];
    }

    /**
     * RST trainee checks. Requires report->education (array with below_9, class_10_fail, etc., total).
     *
     * @return array{alerts: array<int, string>, meta: array<string, mixed>}
     */
    public function getRstTraineeChecks(DPReport $report): array
    {
        $alerts = [];
        $meta = [];
        $edu = $report->education ?? [];

        $b9 = (int) ($edu['below_9'] ?? 0);
        $c10f = (int) ($edu['class_10_fail'] ?? 0);
        $c10p = (int) ($edu['class_10_pass'] ?? 0);
        $int = (int) ($edu['intermediate'] ?? 0);
        $abv = (int) ($edu['above_intermediate'] ?? 0);
        $oth = (int) ($edu['other_count'] ?? 0);
        $total = isset($edu['total']) ? (int) $edu['total'] : null;

        $sum = $b9 + $c10f + $c10p + $int + $abv + $oth;
        $meta['sum_categories'] = $sum;
        $meta['total'] = $total;

        if ($total === null) {
            $alerts[] = 'RST: trainee total missing.';
            return ['alerts' => $alerts, 'meta' => $meta];
        }

        if ($total === 0 && $sum === 0) {
            return ['alerts' => $alerts, 'meta' => $meta];
        }

        if ($total !== $sum) {
            $alerts[] = 'RST: trainee total does not match sum of education categories.';
        }

        $tb = $report->total_beneficiaries !== null ? (int) $report->total_beneficiaries : null;
        if ($tb !== null && $total !== $tb) {
            $alerts[] = 'Trainee total ≠ Total beneficiaries.';
        }

        if ($sum === 0 && $total > 0) {
            $alerts[] = 'RST: all categories 0 but total > 0.';
        }

        return ['alerts' => $alerts, 'meta' => $meta];
    }

    /**
     * CIC inmate checks. Requires report->rqwd_inmate_profile.
     *
     * @return array{alerts: array<int, string>, meta: array<string, mixed>}
     */
    public function getCicInmateChecks(DPReport $report): array
    {
        $alerts = [];
        $meta = [];

        $profiles = $report->rqwd_inmate_profile ?? collect();
        $ageCats = [
            'Children below 18 yrs',
            'Women between 18 – 30 years',
            'Women between 31 – 50 years',
            'Women above 50',
        ];

        $byCategory = $profiles->groupBy('age_category');
        $grandTotal = null;

        foreach ($profiles as $p) {
            $ac = (string) ($p->age_category ?? '');
            $st = strtolower(trim((string) ($p->status ?? '')));
            if ($ac === 'All Categories' && $st === 'total') {
                $grandTotal = (int) ($p->number ?? $p->total ?? 0);
                break;
            }
        }
        $meta['grand_total'] = $grandTotal;

        if ($grandTotal === null) {
            $alerts[] = 'CIC: inmate Grand Total missing.';
        }

        foreach ($ageCats as $ac) {
            if (! $byCategory->has($ac)) {
                $alerts[] = "CIC: missing age category {$ac}.";
            }
        }

        foreach ($ageCats as $ac) {
            $rows = $byCategory->get($ac, collect());
            $sumNonTotal = $rows->filter(fn ($r) => strtolower(trim((string) ($r->status ?? ''))) !== 'total')->sum('number');
            $totalRow = $rows->first(fn ($r) => strtolower(trim((string) ($r->status ?? ''))) === 'total');
            $expected = $totalRow !== null ? (int) ($totalRow->number ?? $totalRow->total ?? 0) : null;
            if ($expected !== null && $rows->isNotEmpty() && (int) $sumNonTotal !== $expected) {
                $alerts[] = "CIC: sub-total mismatch for {$ac}.";
            }
        }

        $tb = $report->total_beneficiaries !== null ? (int) $report->total_beneficiaries : null;
        if ($grandTotal !== null && $tb !== null && $grandTotal !== $tb) {
            $alerts[] = 'Inmate Grand Total ≠ Total beneficiaries.';
        }

        return ['alerts' => $alerts, 'meta' => $meta];
    }

    /**
     * Individual (ILP, IAH, IES, IIES) budget and beneficiary checks. Requires $project.
     *
     * @return array{alerts: array<int, string>, meta: array<string, mixed>}
     */
    public function getIndividualBudgetChecks(DPReport $report, Project $project): array
    {
        $alerts = [];
        $meta = [];

        $tb = $report->total_beneficiaries !== null ? (int) $report->total_beneficiaries : null;
        if ($tb !== 1) {
            $alerts[] = 'Individual project: total beneficiaries is not 1.';
        }

        $fm = config('budget.field_mappings')[$report->project_type ?? ''] ?? null;
        if ($fm) {
            $particularKey = $fm['fields']['particular'] ?? 'particular';
            try {
                $budgets = BudgetCalculationService::getBudgetsForReport($project, false);
                $expected = $budgets->pluck($particularKey)->filter()->unique()->values()->toArray();
            } catch (\Throwable $e) {
                $expected = [];
            }
            $reported = collect($report->accountDetails ?? [])->filter(fn ($r) => (int) ($r->is_budget_row ?? 0) === 1)->pluck('particulars')->filter()->toArray();
            $expectedSet = array_flip($expected);
            $reportedSet = array_flip($reported);
            $missing = array_diff_key($expectedSet, $reportedSet);
            $extra = array_diff_key($reportedSet, $expectedSet);
            if (count($missing) > 0 || count($extra) > 0) {
                $alerts[] = 'Budget heads do not match project type structure.';
            }
        }

        $particulars = collect($report->accountDetails ?? [])->pluck('particulars')->filter()->toArray();
        if (count($particulars) !== count(array_unique($particulars))) {
            $alerts[] = 'Duplicate budget head.';
        }

        return ['alerts' => $alerts, 'meta' => $meta];
    }

    /**
     * Development, CCI, Rural-Urban-Tribal, NEXT PHASE: phase and beneficiary checks.
     *
     * @return array{alerts: array<int, string>, meta: array<string, mixed>}
     */
    public function getDevelopmentAndSimilarChecks(DPReport $report, Project $project): array
    {
        $alerts = [];
        $meta = [];

        $tbReport = $report->total_beneficiaries !== null ? (int) $report->total_beneficiaries : null;
        $tbProject = $project->target_beneficiaries;
        if ($tbProject !== null && $tbProject !== '') {
            $tbProject = is_numeric($tbProject) ? (int) $tbProject : null;
        } else {
            $tbProject = null;
        }
        if ($tbReport !== null && $tbProject !== null) {
            $diff = abs($tbReport - $tbProject);
            if ($diff > 10 && ($tbProject === 0 || $diff > $tbProject * 0.2)) {
                $alerts[] = 'Total beneficiaries vs project target — please confirm.';
            }
        }

        return ['alerts' => $alerts, 'meta' => $meta];
    }

    /**
     * Beneficiary consistency: report vs project; type-specific total vs total_beneficiaries. Applies to all relevant types.
     *
     * @return array{alerts: array<int, string>, meta: array<string, mixed>}
     */
    public function getBeneficiaryConsistencyChecks(DPReport $report, Project $project): array
    {
        $alerts = [];
        $meta = [];
        $pt = $report->project_type ?? '';

        $tbReport = $report->total_beneficiaries !== null ? (int) $report->total_beneficiaries : null;
        $tbProj = $project->target_beneficiaries;
        $tbProj = ($tbProj !== null && $tbProj !== '' && is_numeric($tbProj)) ? (int) $tbProj : null;

        if ($tbReport !== null && $tbProj !== null) {
            $individuals = ['Individual - Livelihood Application', 'Individual - Access to Health', 'Individual - Initial - Educational support', 'Individual - Ongoing Educational support'];
            if (in_array($pt, $individuals, true) && $tbReport !== 1) {
                $alerts[] = 'Report total_beneficiaries should typically be 1 for individual projects.';
            }
            if (! in_array($pt, $individuals, true) && $tbProj > 0) {
                $diff = abs($tbReport - $tbProj);
                if ($diff > 10 && $diff > $tbProj * 0.2) {
                    $alerts[] = 'Report total_beneficiaries vs project target_beneficiaries — please confirm.';
                }
            }
        }

        $typeTotal = null;
        if ($pt === 'Livelihood Development Projects') {
            $typeTotal = ($report->annexures ?? collect())->count();
        } elseif ($pt === 'Institutional Ongoing Group Educational proposal') {
            $gt = ($report->rqis_age_profile ?? collect())->first(fn ($p) => (string) ($p->age_group ?? '') === 'All Categories' && (string) ($p->education ?? '') === 'Grand Total');
            $typeTotal = $gt !== null ? (int) ($gt->present_academic_year ?? 0) : null;
        } elseif ($pt === 'Residential Skill Training Proposal 2') {
            $edu = $report->education ?? [];
            $typeTotal = isset($edu['total']) ? (int) $edu['total'] : null;
        } elseif ($pt === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            $row = ($report->rqwd_inmate_profile ?? collect())->first(fn ($p) => (string) ($p->age_category ?? '') === 'All Categories' && strtolower(trim((string) ($p->status ?? ''))) === 'total');
            $typeTotal = $row !== null ? (int) ($row->number ?? $row->total ?? 0) : null;
        }

        if ($typeTotal !== null && $tbReport !== null && $typeTotal !== $tbReport) {
            $alerts[] = 'Type-specific count ≠ Total beneficiaries.';
        }
        $meta['type_specific_total'] = $typeTotal;

        return ['alerts' => $alerts, 'meta' => $meta];
    }
}
