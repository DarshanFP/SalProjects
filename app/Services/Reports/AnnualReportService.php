<?php

namespace App\Services\Reports;

use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Reports\Quarterly\QuarterlyReport;
use App\Models\Reports\HalfYearly\HalfYearlyReport;
use App\Models\Reports\Annual\AnnualReport;
use App\Models\Reports\Annual\AnnualReportDetail;
use App\Models\Reports\Aggregated\AggregatedReportObjective;
use App\Models\Reports\Aggregated\AggregatedReportPhoto;
use App\Models\Reports\AI\AIReportInsight;
use App\Models\Reports\AI\AIReportTitle;
use App\Models\User;
use App\Services\AI\OpenAIService;
use App\Services\AI\ReportAnalysisService;
use App\Services\AI\Prompts\AggregatedReportPrompts;
use App\Services\AI\ReportTitleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnnualReportService
{
    /**
     * Generate an annual report from half-yearly, quarterly, or monthly reports
     *
     * @param Project $project
     * @param int $year
     * @param User $user
     * @return AnnualReport
     * @throws \Exception
     */
    public static function generateAnnualReport(Project $project, int $year, User $user): AnnualReport
    {
        DB::beginTransaction();
        try {
            // Calculate year period
            $period = self::calculateYearPeriod($year);

            // Try to get half-yearly reports first (preferred)
            $halfYearlyReports = self::getHalfYearlyReportsForYear($project, $year);
            $quarterlyReports = collect();
            $monthlyReports = collect();
            $sourceType = 'half_yearly';

            // Fallback to quarterly reports
            if ($halfYearlyReports->isEmpty()) {
                $quarterlyReports = self::getQuarterlyReportsForYear($project, $year);
                $sourceType = 'quarterly';

                // Final fallback to monthly reports
                if ($quarterlyReports->isEmpty()) {
                    $monthlyReports = self::getMonthlyReportsForYear($project, $year);
                    $sourceType = 'monthly';

                    if ($monthlyReports->isEmpty()) {
                        throw new \Exception('No approved reports found for the year');
                    }
                }
            }

            // Generate report ID
            $reportId = self::generateReportId($project, $year);

            // Aggregate data
            if ($sourceType === 'half_yearly') {
                $aggregatedData = self::aggregateHalfYearlyReports($halfYearlyReports, $project, $period);
                $sourceReportIds = $halfYearlyReports->pluck('report_id')->toArray();
            } elseif ($sourceType === 'quarterly') {
                $aggregatedData = self::aggregateQuarterlyReports($quarterlyReports, $project, $period);
                $sourceReportIds = $quarterlyReports->pluck('report_id')->toArray();
            } else {
                $aggregatedData = self::aggregateMonthlyReports($monthlyReports, $project, $period);
                $sourceReportIds = $monthlyReports->pluck('report_id')->toArray();
            }

            // Create annual report
            $annualReport = AnnualReport::create([
                'report_id' => $reportId,
                'project_id' => $project->project_id,
                'generated_by_user_id' => $user->id,
                'year' => $year,
                'period_from' => $period['from'],
                'period_to' => $period['to'],
                'project_title' => $project->project_title,
                'project_type' => $project->project_type,
                'place' => $project->place,
                'society_name' => $project->society_name,
                'commencement_month_year' => $project->commencement_month_year,
                'in_charge' => $project->in_charge,
                'total_beneficiaries' => $aggregatedData['total_beneficiaries'],
                'goal' => $aggregatedData['goal'],
                'account_period_start' => $period['from'],
                'account_period_end' => $period['to'],
                'amount_sanctioned_overview' => $aggregatedData['amount_sanctioned_overview'],
                'amount_forwarded_overview' => $aggregatedData['amount_forwarded_overview'],
                'amount_in_hand' => $aggregatedData['amount_in_hand'],
                'total_balance_forwarded' => $aggregatedData['total_balance_forwarded'],
                'status' => 'draft',
                'generated_from' => $sourceReportIds,
                'generated_at' => now(),
            ]);

            // Create report details
            if ($sourceType === 'half_yearly') {
                self::createAnnualReportDetailsFromHalfYearly($annualReport, $halfYearlyReports);
            } elseif ($sourceType === 'quarterly') {
                self::createAnnualReportDetailsFromQuarterly($annualReport, $quarterlyReports);
            } else {
                self::createAnnualReportDetailsFromMonthly($annualReport, $monthlyReports);
            }

            // Aggregate objectives and photos
            if ($sourceType === 'half_yearly') {
                self::aggregateObjectivesFromHalfYearly($annualReport, $halfYearlyReports);
                self::aggregatePhotosFromHalfYearly($annualReport, $halfYearlyReports);
            } elseif ($sourceType === 'quarterly') {
                self::aggregateObjectivesFromQuarterly($annualReport, $quarterlyReports);
                self::aggregatePhotosFromQuarterly($annualReport, $quarterlyReports);
            } else {
                self::aggregateObjectivesFromMonthly($annualReport, $monthlyReports);
                self::aggregatePhotosFromMonthly($annualReport, $monthlyReports);
            }

            DB::commit();

            Log::info('Annual report generated successfully', [
                'report_id' => $reportId,
                'project_id' => $project->project_id,
                'year' => $year,
                'source_type' => $sourceType,
            ]);

            return $annualReport;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate annual report', [
                'project_id' => $project->project_id,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate year period dates
     */
    private static function calculateYearPeriod(int $year): array
    {
        $from = Carbon::create($year, 1, 1)->startOfMonth();
        $to = Carbon::create($year, 12, 31)->endOfMonth();

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Get half-yearly reports for the year
     */
    private static function getHalfYearlyReportsForYear(Project $project, int $year): Collection
    {
        return HalfYearlyReport::where('project_id', $project->project_id)
            ->where('status', HalfYearlyReport::STATUS_APPROVED_BY_COORDINATOR)
            ->where('year', $year)
            ->with(['details', 'objectives', 'photos'])
            ->orderBy('half_year', 'asc')
            ->get();
    }

    /**
     * Get quarterly reports for the year
     */
    private static function getQuarterlyReportsForYear(Project $project, int $year): Collection
    {
        return QuarterlyReport::where('project_id', $project->project_id)
            ->where('status', QuarterlyReport::STATUS_APPROVED_BY_COORDINATOR)
            ->where('year', $year)
            ->with(['details', 'objectives', 'photos'])
            ->orderBy('quarter', 'asc')
            ->get();
    }

    /**
     * Get monthly reports for the year
     */
    private static function getMonthlyReportsForYear(Project $project, int $year): Collection
    {
        $period = self::calculateYearPeriod($year);

        return DPReport::where('project_id', $project->project_id)
            ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
            ->whereBetween('report_month_year', [$period['from'], $period['to']])
            ->with(['accountDetails', 'objectives.activities', 'photos', 'attachments'])
            ->orderBy('report_month_year', 'asc')
            ->get();
    }

    /**
     * Aggregate data from half-yearly reports
     */
    private static function aggregateHalfYearlyReports(Collection $halfYearlyReports, Project $project, array $period): array
    {
        if ($halfYearlyReports->isEmpty()) {
            throw new \Exception('No half-yearly reports to aggregate');
        }

        $firstReport = $halfYearlyReports->first();

        return [
            'total_beneficiaries' => $halfYearlyReports->max('total_beneficiaries') ?? $firstReport->total_beneficiaries,
            'goal' => $firstReport->goal ?? $project->goal ?? '',
            'amount_sanctioned_overview' => $halfYearlyReports->sum('amount_sanctioned_overview'),
            'amount_forwarded_overview' => $halfYearlyReports->sum('amount_forwarded_overview'),
            'amount_in_hand' => $halfYearlyReports->sum('amount_in_hand'),
            'total_balance_forwarded' => $halfYearlyReports->last()->total_balance_forwarded ?? 0,
        ];
    }

    /**
     * Aggregate data from quarterly reports
     */
    private static function aggregateQuarterlyReports(Collection $quarterlyReports, Project $project, array $period): array
    {
        if ($quarterlyReports->isEmpty()) {
            throw new \Exception('No quarterly reports to aggregate');
        }

        $firstReport = $quarterlyReports->first();

        return [
            'total_beneficiaries' => $quarterlyReports->max('total_beneficiaries') ?? $firstReport->total_beneficiaries,
            'goal' => $firstReport->goal ?? $project->goal ?? '',
            'amount_sanctioned_overview' => $quarterlyReports->sum('amount_sanctioned_overview'),
            'amount_forwarded_overview' => $quarterlyReports->sum('amount_forwarded_overview'),
            'amount_in_hand' => $quarterlyReports->sum('amount_in_hand'),
            'total_balance_forwarded' => $quarterlyReports->last()->total_balance_forwarded ?? 0,
        ];
    }

    /**
     * Aggregate data from monthly reports
     */
    private static function aggregateMonthlyReports(Collection $monthlyReports, Project $project, array $period): array
    {
        if ($monthlyReports->isEmpty()) {
            throw new \Exception('No monthly reports to aggregate');
        }

        $firstReport = $monthlyReports->first();

        return [
            'total_beneficiaries' => $monthlyReports->max('total_beneficiaries') ?? $firstReport->total_beneficiaries,
            'goal' => $firstReport->goal ?? $project->goal ?? '',
            'amount_sanctioned_overview' => $monthlyReports->sum('amount_sanctioned_overview'),
            'amount_forwarded_overview' => $monthlyReports->sum('amount_forwarded_overview'),
            'amount_in_hand' => $monthlyReports->sum('amount_sanctioned_overview') + $monthlyReports->sum('amount_forwarded_overview'),
            'total_balance_forwarded' => $monthlyReports->last()->total_balance_forwarded ?? 0,
        ];
    }

    /**
     * Create annual report details from half-yearly reports
     */
    private static function createAnnualReportDetailsFromHalfYearly(AnnualReport $annualReport, Collection $halfYearlyReports): void
    {
        $allDetails = collect();
        foreach ($halfYearlyReports as $halfYearlyReport) {
            foreach ($halfYearlyReport->details as $detail) {
                $allDetails->push([
                    'half_yearly_report' => $halfYearlyReport,
                    'detail' => $detail,
                    'half_year' => $halfYearlyReport->half_year,
                ]);
            }
        }

        $groupedByParticulars = $allDetails->groupBy(function ($item) {
            return $item['detail']->particulars;
        });

        foreach ($groupedByParticulars as $particular => $items) {
            $firstItem = $items->first();
            $firstDetail = $firstItem['detail'];

            $openingBalance = $firstDetail->opening_balance ?? 0;
            $amountForwarded = $items->sum('detail.amount_forwarded');
            $amountSanctioned = $items->sum('detail.amount_sanctioned');
            $totalAmount = $amountForwarded + $amountSanctioned;
            $totalExpenses = $items->sum('detail.total_expenses');
            $closingBalance = $totalAmount - $totalExpenses;

            // Expenses by half-year
            $expensesByHalfYear = [];
            foreach ($items as $item) {
                $halfYear = $item['half_year'];
                $expensesByHalfYear['h' . $halfYear] = $item['detail']->total_expenses ?? 0;
            }

            // Expenses by quarter (from quarterly breakdown in half-yearly details)
            $expensesByQuarter = [];
            foreach ($items as $item) {
                $quarterlyBreakdown = $item['detail']->expenses_by_quarter ?? [];
                foreach ($quarterlyBreakdown as $quarter => $amount) {
                    if (!isset($expensesByQuarter[$quarter])) {
                        $expensesByQuarter[$quarter] = 0;
                    }
                    $expensesByQuarter[$quarter] += $amount;
                }
            }

            AnnualReportDetail::create([
                'annual_report_id' => $annualReport->id,
                'particulars' => $particular,
                'opening_balance' => $openingBalance,
                'amount_forwarded' => $amountForwarded,
                'amount_sanctioned' => $amountSanctioned,
                'total_amount' => $totalAmount,
                'total_expenses' => $totalExpenses,
                'closing_balance' => $closingBalance,
                'expenses_by_half_year' => $expensesByHalfYear,
                'expenses_by_quarter' => $expensesByQuarter,
            ]);
        }
    }

    /**
     * Create annual report details from quarterly reports
     */
    private static function createAnnualReportDetailsFromQuarterly(AnnualReport $annualReport, Collection $quarterlyReports): void
    {
        $allDetails = collect();
        foreach ($quarterlyReports as $quarterlyReport) {
            foreach ($quarterlyReport->details as $detail) {
                $allDetails->push([
                    'quarterly_report' => $quarterlyReport,
                    'detail' => $detail,
                    'quarter' => $quarterlyReport->quarter,
                ]);
            }
        }

        $groupedByParticulars = $allDetails->groupBy(function ($item) {
            return $item['detail']->particulars;
        });

        foreach ($groupedByParticulars as $particular => $items) {
            $firstItem = $items->first();
            $firstDetail = $firstItem['detail'];

            $openingBalance = $firstDetail->opening_balance ?? 0;
            $amountForwarded = $items->sum('detail.amount_forwarded');
            $amountSanctioned = $items->sum('detail.amount_sanctioned');
            $totalAmount = $amountForwarded + $amountSanctioned;
            $totalExpenses = $items->sum('detail.total_expenses');
            $closingBalance = $totalAmount - $totalExpenses;

            // Expenses by quarter
            $expensesByQuarter = [];
            foreach ($items as $item) {
                $quarter = $item['quarter'];
                $expensesByQuarter['q' . $quarter] = $item['detail']->total_expenses ?? 0;
            }

            // Expenses by half-year (approximate)
            $expensesByHalfYear = [
                'h1' => ($expensesByQuarter['q1'] ?? 0) + ($expensesByQuarter['q2'] ?? 0),
                'h2' => ($expensesByQuarter['q3'] ?? 0) + ($expensesByQuarter['q4'] ?? 0),
            ];

            AnnualReportDetail::create([
                'annual_report_id' => $annualReport->id,
                'particulars' => $particular,
                'opening_balance' => $openingBalance,
                'amount_forwarded' => $amountForwarded,
                'amount_sanctioned' => $amountSanctioned,
                'total_amount' => $totalAmount,
                'total_expenses' => $totalExpenses,
                'closing_balance' => $closingBalance,
                'expenses_by_half_year' => $expensesByHalfYear,
                'expenses_by_quarter' => $expensesByQuarter,
            ]);
        }
    }

    /**
     * Create annual report details from monthly reports
     */
    private static function createAnnualReportDetailsFromMonthly(AnnualReport $annualReport, Collection $monthlyReports): void
    {
        $allAccountDetails = collect();
        foreach ($monthlyReports as $report) {
            foreach ($report->accountDetails as $detail) {
                $allAccountDetails->push([
                    'report' => $report,
                    'detail' => $detail,
                    'month' => Carbon::parse($report->report_month_year)->month,
                    'quarter' => self::getQuarterFromMonth(Carbon::parse($report->report_month_year)->month),
                ]);
            }
        }

        $groupedByParticulars = $allAccountDetails->groupBy(function ($item) {
            return $item['detail']->particulars;
        });

        foreach ($groupedByParticulars as $particular => $items) {
            $firstItem = $items->first();
            $firstDetail = $firstItem['detail'];

            $openingBalance = $firstDetail->balance_amount ?? 0;
            $amountForwarded = $items->sum('detail.amount_forwarded');
            $amountSanctioned = $items->sum('detail.amount_sanctioned');
            $totalAmount = $amountForwarded + $amountSanctioned;
            $totalExpenses = $items->sum('detail.total_expenses');
            $closingBalance = $totalAmount - $totalExpenses;

            // Expenses by quarter
            $expensesByQuarter = [];
            foreach ($items as $item) {
                $quarter = $item['quarter'];
                if (!isset($expensesByQuarter['q' . $quarter])) {
                    $expensesByQuarter['q' . $quarter] = 0;
                }
                $expensesByQuarter['q' . $quarter] += $item['detail']->total_expenses ?? 0;
            }

            // Expenses by half-year
            $expensesByHalfYear = [
                'h1' => ($expensesByQuarter['q1'] ?? 0) + ($expensesByQuarter['q2'] ?? 0),
                'h2' => ($expensesByQuarter['q3'] ?? 0) + ($expensesByQuarter['q4'] ?? 0),
            ];

            AnnualReportDetail::create([
                'annual_report_id' => $annualReport->id,
                'particulars' => $particular,
                'opening_balance' => $openingBalance,
                'amount_forwarded' => $amountForwarded,
                'amount_sanctioned' => $amountSanctioned,
                'total_amount' => $totalAmount,
                'total_expenses' => $totalExpenses,
                'closing_balance' => $closingBalance,
                'expenses_by_half_year' => $expensesByHalfYear,
                'expenses_by_quarter' => $expensesByQuarter,
            ]);
        }
    }

    /**
     * Get quarter from month number
     */
    private static function getQuarterFromMonth(int $month): int
    {
        if ($month <= 3) return 1;
        if ($month <= 6) return 2;
        if ($month <= 9) return 3;
        return 4;
    }

    /**
     * Aggregate objectives from half-yearly reports
     */
    private static function aggregateObjectivesFromHalfYearly(AnnualReport $annualReport, Collection $halfYearlyReports): void
    {
        $objectivesMap = [];

        foreach ($halfYearlyReports as $halfYearlyReport) {
            foreach ($halfYearlyReport->objectives as $objective) {
                $key = $objective->project_objective_id ?? $objective->objective_text;

                if (!isset($objectivesMap[$key])) {
                    $objectivesMap[$key] = [
                        'objective_text' => $objective->objective_text ?? '',
                        'project_objective_id' => $objective->project_objective_id,
                        'half_yearly_breakdown' => [],
                        'cumulative_progress' => '',
                    ];
                }

                $halfYearLabel = 'H' . $halfYearlyReport->half_year . ' ' . $halfYearlyReport->year;
                $objectivesMap[$key]['half_yearly_breakdown'][$halfYearLabel] = $objective->monthly_breakdown ?? [];
            }
        }

        foreach ($objectivesMap as $key => $data) {
            AggregatedReportObjective::create([
                'report_type' => 'annual',
                'report_id' => $annualReport->id,
                'objective_text' => $data['objective_text'],
                'project_objective_id' => $data['project_objective_id'],
                'cumulative_progress' => $data['cumulative_progress'],
                'monthly_breakdown' => $data['half_yearly_breakdown'],
            ]);
        }
    }

    /**
     * Aggregate objectives from quarterly reports
     */
    private static function aggregateObjectivesFromQuarterly(AnnualReport $annualReport, Collection $quarterlyReports): void
    {
        $objectivesMap = [];

        foreach ($quarterlyReports as $quarterlyReport) {
            foreach ($quarterlyReport->objectives as $objective) {
                $key = $objective->project_objective_id ?? $objective->objective_text;

                if (!isset($objectivesMap[$key])) {
                    $objectivesMap[$key] = [
                        'objective_text' => $objective->objective_text ?? '',
                        'project_objective_id' => $objective->project_objective_id,
                        'quarterly_breakdown' => [],
                        'cumulative_progress' => '',
                    ];
                }

                $quarterLabel = 'Q' . $quarterlyReport->quarter . ' ' . $quarterlyReport->year;
                $objectivesMap[$key]['quarterly_breakdown'][$quarterLabel] = $objective->monthly_breakdown ?? [];
            }
        }

        foreach ($objectivesMap as $key => $data) {
            AggregatedReportObjective::create([
                'report_type' => 'annual',
                'report_id' => $annualReport->id,
                'objective_text' => $data['objective_text'],
                'project_objective_id' => $data['project_objective_id'],
                'cumulative_progress' => $data['cumulative_progress'],
                'monthly_breakdown' => $data['quarterly_breakdown'],
            ]);
        }
    }

    /**
     * Aggregate objectives from monthly reports
     */
    private static function aggregateObjectivesFromMonthly(AnnualReport $annualReport, Collection $monthlyReports): void
    {
        $objectivesMap = [];

        foreach ($monthlyReports as $report) {
            foreach ($report->objectives as $objective) {
                $key = $objective->project_objective_id ?? $objective->objective_text;

                if (!isset($objectivesMap[$key])) {
                    $objectivesMap[$key] = [
                        'objective_text' => $objective->objective_text ?? '',
                        'project_objective_id' => $objective->project_objective_id,
                        'monthly_breakdown' => [],
                        'cumulative_progress' => '',
                    ];
                }

                $month = Carbon::parse($report->report_month_year)->format('F Y');
                $objectivesMap[$key]['monthly_breakdown'][$month] = [
                    'progress' => $objective->not_happened ?? '',
                    'changes' => $objective->changes ?? '',
                    'lessons_learnt' => $objective->lessons_learnt ?? '',
                ];
            }
        }

        foreach ($objectivesMap as $key => $data) {
            AggregatedReportObjective::create([
                'report_type' => 'annual',
                'report_id' => $annualReport->id,
                'objective_text' => $data['objective_text'],
                'project_objective_id' => $data['project_objective_id'],
                'cumulative_progress' => $data['cumulative_progress'],
                'monthly_breakdown' => $data['monthly_breakdown'],
            ]);
        }
    }

    /**
     * Aggregate photos from half-yearly reports
     */
    private static function aggregatePhotosFromHalfYearly(AnnualReport $annualReport, Collection $halfYearlyReports, int $limit = 100): void
    {
        $photoCount = 0;

        foreach ($halfYearlyReports as $halfYearlyReport) {
            if ($photoCount >= $limit) {
                break;
            }

            foreach ($halfYearlyReport->photos as $photo) {
                if ($photoCount >= $limit) {
                    break;
                }

                AggregatedReportPhoto::create([
                    'report_type' => 'annual',
                    'report_id' => $annualReport->id,
                    'photo_path' => $photo->photo_path,
                    'description' => $photo->description,
                    'source_monthly_report_id' => $photo->source_monthly_report_id,
                    'source_month' => $photo->source_month,
                    'source_year' => $photo->source_year,
                ]);

                $photoCount++;
            }
        }
    }

    /**
     * Aggregate photos from quarterly reports
     */
    private static function aggregatePhotosFromQuarterly(AnnualReport $annualReport, Collection $quarterlyReports, int $limit = 100): void
    {
        $photoCount = 0;

        foreach ($quarterlyReports as $quarterlyReport) {
            if ($photoCount >= $limit) {
                break;
            }

            foreach ($quarterlyReport->photos as $photo) {
                if ($photoCount >= $limit) {
                    break;
                }

                AggregatedReportPhoto::create([
                    'report_type' => 'annual',
                    'report_id' => $annualReport->id,
                    'photo_path' => $photo->photo_path,
                    'description' => $photo->description,
                    'source_monthly_report_id' => $photo->source_monthly_report_id,
                    'source_month' => $photo->source_month,
                    'source_year' => $photo->source_year,
                ]);

                $photoCount++;
            }
        }
    }

    /**
     * Aggregate photos from monthly reports
     */
    private static function aggregatePhotosFromMonthly(AnnualReport $annualReport, Collection $monthlyReports, int $limit = 100): void
    {
        $photoCount = 0;

        foreach ($monthlyReports as $report) {
            if ($photoCount >= $limit) {
                break;
            }

            foreach ($report->photos as $photo) {
                if ($photoCount >= $limit) {
                    break;
                }

                AggregatedReportPhoto::create([
                    'report_type' => 'annual',
                    'report_id' => $annualReport->id,
                    'photo_path' => $photo->photo_path,
                    'description' => $photo->description,
                    'source_monthly_report_id' => $report->report_id,
                    'source_month' => Carbon::parse($report->report_month_year)->month,
                    'source_year' => Carbon::parse($report->report_month_year)->year,
                ]);

                $photoCount++;
            }
        }
    }

    /**
     * Generate trends analysis for annual report
     */
    public static function generateTrendsAnalysis(AnnualReport $annualReport): array
    {
        // This can be expanded to include charts, graphs, etc.
        return [
            'budget_vs_actual' => self::calculateBudgetVsActual($annualReport),
            'expense_trends' => self::calculateExpenseTrends($annualReport),
            'beneficiary_growth' => self::calculateBeneficiaryGrowth($annualReport),
        ];
    }

    /**
     * Calculate budget vs actual
     */
    private static function calculateBudgetVsActual(AnnualReport $annualReport): array
    {
        $totalBudget = $annualReport->amount_sanctioned_overview + $annualReport->amount_forwarded_overview;
        $totalExpenses = $annualReport->details->sum('total_expenses');
        $variance = $totalBudget - $totalExpenses;
        $variancePercentage = $totalBudget > 0 ? ($variance / $totalBudget) * 100 : 0;

        return [
            'total_budget' => $totalBudget,
            'total_expenses' => $totalExpenses,
            'variance' => $variance,
            'variance_percentage' => $variancePercentage,
        ];
    }

    /**
     * Calculate expense trends
     */
    private static function calculateExpenseTrends(AnnualReport $annualReport): array
    {
        $quarterlyExpenses = [];
        foreach ($annualReport->details as $detail) {
            $quarterlyBreakdown = $detail->expenses_by_quarter ?? [];
            foreach ($quarterlyBreakdown as $quarter => $amount) {
                if (!isset($quarterlyExpenses[$quarter])) {
                    $quarterlyExpenses[$quarter] = 0;
                }
                $quarterlyExpenses[$quarter] += $amount;
            }
        }

        return $quarterlyExpenses;
    }

    /**
     * Calculate beneficiary growth
     */
    private static function calculateBeneficiaryGrowth(AnnualReport $annualReport): array
    {
        // This would require tracking beneficiary changes over time
        // For now, return current total
        return [
            'current_total' => $annualReport->total_beneficiaries,
            'growth_rate' => 0, // Would need historical data
        ];
    }

    /**
     * Generate an annual report with AI enhancement
     *
     * @param Project $project
     * @param int $year
     * @param User $user
     * @param bool $useAI
     * @return AnnualReport
     * @throws \Exception
     */
    public static function generateAnnualReportWithAI(
        Project $project,
        int $year,
        User $user,
        bool $useAI = true
    ): AnnualReport {
        // Generate base report
        $annualReport = self::generateAnnualReport($project, $year, $user);

        // Add AI enhancements if enabled
        if ($useAI && config('ai.features.enable_ai_generation', true)) {
            try {
                $aiInsights = self::generateAIInsights($project, $year, $annualReport);

                // Store AI insights in database
                self::storeAIInsights($annualReport, $aiInsights);

                // Generate and store AI titles
                self::generateAndStoreAITitles($annualReport, $project, $year);

                Log::info('AI insights generated and stored for annual report', [
                    'report_id' => $annualReport->report_id
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to generate AI insights, continuing with base report', [
                    'report_id' => $annualReport->report_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $annualReport;
    }

    /**
     * Generate AI insights for annual report
     *
     * @param Project $project
     * @param int $year
     * @param AnnualReport $annualReport
     * @return array
     */
    private static function generateAIInsights(
        Project $project,
        int $year,
        AnnualReport $annualReport
    ): array {
        // Get all monthly reports for the year for analysis
        $monthlyReports = self::getMonthlyReportsForYear($project, $year);

        // Analyze monthly reports
        $analysisResults = [];
        foreach ($monthlyReports as $report) {
            try {
                $analysis = ReportAnalysisService::analyzeSingleReport($report);
                $analysisResults[] = $analysis;
            } catch (\Exception $e) {
                Log::warning('Failed to analyze monthly report for AI', [
                    'report_id' => $report->report_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Aggregate analysis
        $aggregatedAnalysis = self::aggregateAnalysisResults($analysisResults);

        // Generate annual report summary
        $periodInfo = [
            'year' => $year,
        ];

        $prompt = AggregatedReportPrompts::getAnnualReportPrompt($aggregatedAnalysis, $periodInfo);

        try {
            $response = self::callOpenAIForAggregatedReport($prompt);
            $responseContent = $response->choices[0]->message->content ?? '';
            $aiContent = \App\Services\AI\ResponseParser::parseAnalysisResponse($responseContent);

            // Extract token usage if available
            $tokensUsed = $response->usage->totalTokens ?? null;

            return [
                'executive_summary' => $aiContent['executive_summary'] ?? '',
                'key_achievements' => $aiContent['year_end_achievements'] ?? $aiContent['key_achievements'] ?? [],
                'progress_trends' => $aiContent['annual_trends'] ?? $aiContent['progress_trends'] ?? [],
                'challenges' => $aiContent['challenges'] ?? [],
                'recommendations' => $aiContent['strategic_recommendations'] ?? $aiContent['recommendations'] ?? [],
                'strategic_insights' => $aiContent['strategic_insights'] ?? [],
                'impact_assessment' => $aiContent['impact_assessment'] ?? [],
                'budget_performance' => $aiContent['budget_performance'] ?? [],
                'future_outlook' => $aiContent['future_outlook'] ?? [],
                'year_over_year_comparison' => $aiContent['year_over_year_comparison'] ?? [],
                'raw_analysis' => $aggregatedAnalysis,
                'tokens_used' => $tokensUsed,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate AI annual report content', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Aggregate analysis results from multiple reports
     *
     * @param array $analysisResults
     * @return array
     */
    private static function aggregateAnalysisResults(array $analysisResults): array
    {
        if (empty($analysisResults)) {
            return [];
        }

        $aggregated = [
            'key_achievements' => [],
            'objectives_progress' => [],
            'budget_status' => [],
            'challenges' => [],
            'lessons_learnt' => [],
            'key_insights' => [],
        ];

        foreach ($analysisResults as $analysis) {
            if (isset($analysis['key_achievements'])) {
                $aggregated['key_achievements'] = array_merge(
                    $aggregated['key_achievements'],
                    is_array($analysis['key_achievements']) ? $analysis['key_achievements'] : []
                );
            }

            if (isset($analysis['challenges'])) {
                $aggregated['challenges'] = array_merge(
                    $aggregated['challenges'],
                    is_array($analysis['challenges']) ? $analysis['challenges'] : []
                );
            }

            if (isset($analysis['lessons_learnt'])) {
                $aggregated['lessons_learnt'] = array_merge(
                    $aggregated['lessons_learnt'],
                    is_array($analysis['lessons_learnt']) ? $analysis['lessons_learnt'] : []
                );
            }

            if (isset($analysis['key_insights'])) {
                $aggregated['key_insights'] = array_merge(
                    $aggregated['key_insights'],
                    is_array($analysis['key_insights']) ? $analysis['key_insights'] : []
                );
            }
        }

        // Remove duplicates
        $aggregated['key_achievements'] = array_unique($aggregated['key_achievements']);
        $aggregated['challenges'] = array_unique($aggregated['challenges']);
        $aggregated['lessons_learnt'] = array_unique($aggregated['lessons_learnt']);
        $aggregated['key_insights'] = array_unique($aggregated['key_insights']);

        return $aggregated;
    }

    /**
     * Store AI insights in database
     *
     * @param AnnualReport $annualReport
     * @param array $aiInsights
     * @return AIReportInsight
     */
    public static function storeAIInsights(AnnualReport $annualReport, array $aiInsights): AIReportInsight
    {
        return AIReportInsight::updateOrCreate(
            [
                'report_type' => 'annual',
                'report_id' => $annualReport->report_id,
            ],
            [
                'executive_summary' => $aiInsights['executive_summary'] ?? null,
                'key_achievements' => $aiInsights['key_achievements'] ?? [],
                'progress_trends' => $aiInsights['progress_trends'] ?? [],
                'challenges' => $aiInsights['challenges'] ?? [],
                'recommendations' => $aiInsights['recommendations'] ?? [],
                'strategic_insights' => $aiInsights['strategic_insights'] ?? [],
                'impact_assessment' => $aiInsights['impact_assessment'] ?? [],
                'budget_performance' => $aiInsights['budget_performance'] ?? [],
                'future_outlook' => $aiInsights['future_outlook'] ?? [],
                'year_over_year_comparison' => $aiInsights['year_over_year_comparison'] ?? [],
                'ai_model_used' => config('ai.openai.model', 'gpt-4o-mini'),
                'ai_tokens_used' => $aiInsights['tokens_used'] ?? null,
                'generated_at' => now(),
            ]
        );
    }

    /**
     * Generate and store AI titles
     *
     * @param AnnualReport $annualReport
     * @param Project $project
     * @param int $year
     * @return AIReportTitle
     */
    public static function generateAndStoreAITitles(
        AnnualReport $annualReport,
        Project $project,
        int $year
    ): AIReportTitle {
        try {
            // Get aggregated analysis for title generation
            $monthlyReports = self::getMonthlyReportsForYear($project, $year);
            $analysisResults = [];
            foreach ($monthlyReports as $report) {
                try {
                    $analysis = ReportAnalysisService::analyzeSingleReport($report);
                    $analysisResults[] = $analysis;
                } catch (\Exception $e) {
                    // Skip failed analyses
                }
            }
            $aggregatedAnalysis = self::aggregateAnalysisResults($analysisResults);

            $period = (string)$year;
            $title = ReportTitleService::generateReportTitle($aggregatedAnalysis, 'annual', $period);
            $headings = ReportTitleService::generateSectionHeadings($aggregatedAnalysis, 'annual');

            return AIReportTitle::updateOrCreate(
                [
                    'report_type' => 'annual',
                    'report_id' => $annualReport->report_id,
                ],
                [
                    'report_title' => $title,
                    'section_headings' => $headings,
                    'ai_model_used' => config('ai.openai.model', 'gpt-4o-mini'),
                    'generated_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to generate AI titles, using defaults', [
                'error' => $e->getMessage()
            ]);

            $defaultTitle = "Annual Report - {$year}";
            $defaultHeadings = [
                'executive_summary' => 'Executive Summary',
                'year_end_achievements' => 'Year-End Achievements',
                'annual_trends' => 'Annual Trends Analysis',
                'impact_assessment' => 'Impact Assessment',
                'budget_performance' => 'Budget Performance Review',
                'strategic_recommendations' => 'Strategic Recommendations',
                'future_outlook' => 'Future Outlook',
            ];

            return AIReportTitle::updateOrCreate(
                [
                    'report_type' => 'annual',
                    'report_id' => $annualReport->report_id,
                ],
                [
                    'report_title' => $defaultTitle,
                    'section_headings' => $defaultHeadings,
                    'generated_at' => now(),
                ]
            );
        }
    }

    /**
     * Call OpenAI API for aggregated report generation
     *
     * @param string $prompt
     * @return \OpenAI\Responses\Chat\CreateResponse
     * @throws \Exception
     */
    private static function callOpenAIForAggregatedReport(string $prompt)
    {
        if (!config('openai.api_key')) {
            throw new \Exception('OpenAI API key is not configured.');
        }

        if (!config('ai.features.enable_ai_generation', true)) {
            throw new \Exception('AI generation is disabled.');
        }

        $model = config('ai.openai.model', 'gpt-4o-mini');
        $maxTokens = config('ai.openai.max_tokens', 6000); // Higher for annual reports
        $temperature = config('ai.openai.temperature', 0.3);

        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert report analyst specializing in development project reports. Provide accurate, concise, and actionable insights.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

            $content = $response->choices[0]->message->content ?? '';

            if (empty($content)) {
                throw new \Exception('Empty response from OpenAI API');
            }

            // Return full response object to access usage data
            return $response;
        } catch (\Exception $e) {
            Log::error('OpenAI API call failed for annual report', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get AI-generated insights for an existing annual report
     *
     * @param AnnualReport $annualReport
     * @return array
     */
    public static function getAIInsights(AnnualReport $annualReport): array
    {
        $project = $annualReport->project;
        return self::generateAIInsights(
            $project,
            $annualReport->year,
            $annualReport
        );
    }

    /**
     * Generate unique report ID
     */
    private static function generateReportId(Project $project, int $year): string
    {
        $projectTypePrefix = QuarterlyReportService::getProjectTypePrefix($project->project_type);

        return sprintf('AR-%d-%s-%s', $year, $projectTypePrefix, $project->project_id);
    }
}
