<?php

namespace App\Services\Reports;

use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Reports\Quarterly\QuarterlyReport;
use App\Models\Reports\HalfYearly\HalfYearlyReport;
use App\Models\Reports\HalfYearly\HalfYearlyReportDetail;
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

class HalfYearlyReportService
{
    /**
     * Generate a half-yearly report from quarterly or monthly reports
     *
     * @param Project $project
     * @param int $halfYear (1 or 2)
     * @param int $year
     * @param User $user
     * @return HalfYearlyReport
     * @throws \Exception
     */
    public static function generateHalfYearlyReport(Project $project, int $halfYear, int $year, User $user): HalfYearlyReport
    {
        DB::beginTransaction();
        try {
            // Calculate half-year period
            $period = self::calculateHalfYearPeriod($halfYear, $year);

            // Try to get quarterly reports first (preferred)
            $quarterlyReports = self::getQuarterlyReportsForHalfYear($project, $halfYear, $year);
            $monthlyReports = collect();
            $sourceType = 'quarterly';

            // Fallback to monthly reports if no quarterly reports
            if ($quarterlyReports->isEmpty()) {
                $monthlyReports = self::getMonthlyReportsForHalfYear($project, $halfYear, $year);
                $sourceType = 'monthly';

                if ($monthlyReports->isEmpty()) {
                    throw new \Exception('No approved reports found for the half-year');
                }
            }

            // Generate report ID
            $reportId = self::generateReportId($project, $halfYear, $year);

            // Aggregate data
            if ($sourceType === 'quarterly') {
                $aggregatedData = self::aggregateQuarterlyReports($quarterlyReports, $project, $period);
                $sourceReportIds = $quarterlyReports->pluck('report_id')->toArray();
            } else {
                $aggregatedData = self::aggregateMonthlyReports($monthlyReports, $project, $period);
                $sourceReportIds = $monthlyReports->pluck('report_id')->toArray();
            }

            // Create half-yearly report
            $halfYearlyReport = HalfYearlyReport::create([
                'report_id' => $reportId,
                'project_id' => $project->project_id,
                'generated_by_user_id' => $user->id,
                'half_year' => $halfYear,
                'year' => $year,
                'period_from' => $period['from'],
                'period_to' => $period['to'],
                'project_title' => $project->project_title,
                'project_type' => $project->project_type,
                'place' => $project->place,
                'society_name' => optional($project->society)->name ?? $project->society_name,
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
            if ($sourceType === 'quarterly') {
                self::createHalfYearlyReportDetailsFromQuarterly($halfYearlyReport, $quarterlyReports);
            } else {
                self::createHalfYearlyReportDetailsFromMonthly($halfYearlyReport, $monthlyReports);
            }

            // Aggregate objectives and photos
            if ($sourceType === 'quarterly') {
                self::aggregateObjectivesFromQuarterly($halfYearlyReport, $quarterlyReports);
                self::aggregatePhotosFromQuarterly($halfYearlyReport, $quarterlyReports);
            } else {
                self::aggregateObjectivesFromMonthly($halfYearlyReport, $monthlyReports);
                self::aggregatePhotosFromMonthly($halfYearlyReport, $monthlyReports);
            }

            DB::commit();

            Log::info('Half-yearly report generated successfully', [
                'report_id' => $reportId,
                'project_id' => $project->project_id,
                'half_year' => $halfYear,
                'year' => $year,
                'source_type' => $sourceType,
            ]);

            return $halfYearlyReport;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate half-yearly report', [
                'project_id' => $project->project_id,
                'half_year' => $halfYear,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate half-year period dates
     */
    private static function calculateHalfYearPeriod(int $halfYear, int $year): array
    {
        if ($halfYear === 1) {
            // H1: January - June
            $from = Carbon::create($year, 1, 1)->startOfMonth();
            $to = Carbon::create($year, 6, 1)->endOfMonth();
            $quarters = [1, 2]; // Q1 and Q2
        } else {
            // H2: July - December
            $from = Carbon::create($year, 7, 1)->startOfMonth();
            $to = Carbon::create($year, 12, 1)->endOfMonth();
            $quarters = [3, 4]; // Q3 and Q4
        }

        return [
            'from' => $from,
            'to' => $to,
            'quarters' => $quarters,
        ];
    }

    /**
     * Get quarterly reports for the half-year
     */
    private static function getQuarterlyReportsForHalfYear(Project $project, int $halfYear, int $year): Collection
    {
        $period = self::calculateHalfYearPeriod($halfYear, $year);

        return QuarterlyReport::where('project_id', $project->project_id)
            ->where('status', QuarterlyReport::STATUS_APPROVED_BY_COORDINATOR)
            ->where('year', $year)
            ->whereIn('quarter', $period['quarters'])
            ->with(['details', 'objectives', 'photos'])
            ->orderBy('quarter', 'asc')
            ->get();
    }

    /**
     * Get monthly reports for the half-year
     */
    private static function getMonthlyReportsForHalfYear(Project $project, int $halfYear, int $year): Collection
    {
        $period = self::calculateHalfYearPeriod($halfYear, $year);

        return DPReport::where('project_id', $project->project_id)
            ->whereIn('status', DPReport::APPROVED_STATUSES)
            ->whereBetween('report_month_year', [$period['from'], $period['to']])
            ->with(['accountDetails', 'objectives.activities', 'photos', 'attachments'])
            ->orderBy('report_month_year', 'asc')
            ->get();
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
     * Aggregate data from monthly reports (fallback)
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
     * Create half-yearly report details from quarterly reports
     */
    private static function createHalfYearlyReportDetailsFromQuarterly(HalfYearlyReport $halfYearlyReport, Collection $quarterlyReports): void
    {
        // Get all details from quarterly reports
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

        // Group by particulars
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

            HalfYearlyReportDetail::create([
                'half_yearly_report_id' => $halfYearlyReport->id,
                'particulars' => $particular,
                'opening_balance' => $openingBalance,
                'amount_forwarded' => $amountForwarded,
                'amount_sanctioned' => $amountSanctioned,
                'total_amount' => $totalAmount,
                'total_expenses' => $totalExpenses,
                'closing_balance' => $closingBalance,
                'expenses_by_quarter' => $expensesByQuarter,
            ]);
        }
    }

    /**
     * Create half-yearly report details from monthly reports
     */
    private static function createHalfYearlyReportDetailsFromMonthly(HalfYearlyReport $halfYearlyReport, Collection $monthlyReports): void
    {
        $quarterlyDetails = QuarterlyReportService::calculateQuarterlyBudget($monthlyReports);

        // Group by particulars and sum
        $groupedDetails = collect($quarterlyDetails)->groupBy('particulars');

        foreach ($groupedDetails as $particular => $details) {
            $firstDetail = $details->first();

            $openingBalance = $firstDetail['opening_balance'] ?? 0;
            $amountForwarded = $details->sum('amount_forwarded');
            $amountSanctioned = $details->sum('amount_sanctioned');
            $totalAmount = $details->sum('total_amount');
            $totalExpenses = $details->sum('total_expenses');
            $closingBalance = $totalAmount - $totalExpenses;

            // Expenses by quarter (approximate from monthly data)
            $expensesByQuarter = [];
            $quarter1Expenses = $details->sum(function ($detail) {
                return ($detail['expenses_by_month']['month1'] ?? 0) +
                       ($detail['expenses_by_month']['month2'] ?? 0) +
                       ($detail['expenses_by_month']['month3'] ?? 0);
            });
            $quarter2Expenses = $details->sum(function ($detail) {
                return ($detail['expenses_by_month']['month4'] ?? 0) +
                       ($detail['expenses_by_month']['month5'] ?? 0) +
                       ($detail['expenses_by_month']['month6'] ?? 0);
            });
            $expensesByQuarter['q1'] = $quarter1Expenses;
            $expensesByQuarter['q2'] = $quarter2Expenses;

            HalfYearlyReportDetail::create([
                'half_yearly_report_id' => $halfYearlyReport->id,
                'particulars' => $particular,
                'opening_balance' => $openingBalance,
                'amount_forwarded' => $amountForwarded,
                'amount_sanctioned' => $amountSanctioned,
                'total_amount' => $totalAmount,
                'total_expenses' => $totalExpenses,
                'closing_balance' => $closingBalance,
                'expenses_by_quarter' => $expensesByQuarter,
            ]);
        }
    }

    /**
     * Aggregate objectives from quarterly reports
     */
    private static function aggregateObjectivesFromQuarterly(HalfYearlyReport $halfYearlyReport, Collection $quarterlyReports): void
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
                'report_type' => 'half_yearly',
                'report_id' => $halfYearlyReport->id,
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
    private static function aggregateObjectivesFromMonthly(HalfYearlyReport $halfYearlyReport, Collection $monthlyReports): void
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
                'report_type' => 'half_yearly',
                'report_id' => $halfYearlyReport->id,
                'objective_text' => $data['objective_text'],
                'project_objective_id' => $data['project_objective_id'],
                'cumulative_progress' => $data['cumulative_progress'],
                'monthly_breakdown' => $data['monthly_breakdown'],
            ]);
        }
    }

    /**
     * Aggregate photos from quarterly reports
     */
    private static function aggregatePhotosFromQuarterly(HalfYearlyReport $halfYearlyReport, Collection $quarterlyReports, int $limit = 50): void
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
                    'report_type' => 'half_yearly',
                    'report_id' => $halfYearlyReport->id,
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
    private static function aggregatePhotosFromMonthly(HalfYearlyReport $halfYearlyReport, Collection $monthlyReports, int $limit = 50): void
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
                    'report_type' => 'half_yearly',
                    'report_id' => $halfYearlyReport->id,
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
     * Generate a half-yearly report with AI enhancement
     *
     * @param Project $project
     * @param int $halfYear
     * @param int $year
     * @param User $user
     * @param bool $useAI
     * @return HalfYearlyReport
     * @throws \Exception
     */
    public static function generateHalfYearlyReportWithAI(
        Project $project,
        int $halfYear,
        int $year,
        User $user,
        bool $useAI = true
    ): HalfYearlyReport {
        // Generate base report
        $halfYearlyReport = self::generateHalfYearlyReport($project, $halfYear, $year, $user);

        // Add AI enhancements if enabled
        if ($useAI && config('ai.features.enable_ai_generation', true)) {
            try {
                $aiInsights = self::generateAIInsights($project, $halfYear, $year, $halfYearlyReport);

                // Store AI insights in database
                self::storeAIInsights($halfYearlyReport, $aiInsights);

                // Generate and store AI titles
                self::generateAndStoreAITitles($halfYearlyReport, $project, $halfYear, $year);

                Log::info('AI insights generated and stored for half-yearly report', [
                    'report_id' => $halfYearlyReport->report_id
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to generate AI insights, continuing with base report', [
                    'report_id' => $halfYearlyReport->report_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $halfYearlyReport;
    }

    /**
     * Generate AI insights for half-yearly report
     *
     * @param Project $project
     * @param int $halfYear
     * @param int $year
     * @param HalfYearlyReport $halfYearlyReport
     * @return array
     */
    private static function generateAIInsights(
        Project $project,
        int $halfYear,
        int $year,
        HalfYearlyReport $halfYearlyReport
    ): array {
        // Try to get quarterly reports first, fallback to monthly
        $quarterlyReports = self::getQuarterlyReportsForHalfYear($project, $halfYear, $year);
        $monthlyReports = collect();
        $sourceType = 'quarterly';

        if ($quarterlyReports->isEmpty()) {
            $monthlyReports = self::getMonthlyReportsForHalfYear($project, $halfYear, $year);
            $sourceType = 'monthly';
        }

        // Analyze reports
        $analysisResults = [];
        if ($sourceType === 'quarterly') {
            // For quarterly, we'd need to analyze each quarterly report
            // For now, get underlying monthly reports for analysis
            foreach ($quarterlyReports as $quarterlyReport) {
                $monthlyReports = $monthlyReports->merge(
                    \App\Models\Reports\Monthly\DPReport::whereIn('report_id', $quarterlyReport->generated_from ?? [])
                        ->with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                        ->get()
                );
            }
        }

        foreach ($monthlyReports as $report) {
            try {
                $analysis = ReportAnalysisService::analyzeSingleReport($report);
                $analysisResults[] = $analysis;
            } catch (\Exception $e) {
                Log::warning('Failed to analyze report for AI', [
                    'report_id' => $report->report_id ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Aggregate analysis
        $aggregatedAnalysis = self::aggregateAnalysisResults($analysisResults);

        // Generate half-yearly report summary
        $periodInfo = [
            'half_year' => 'H' . $halfYear,
            'year' => $year,
        ];

        $prompt = AggregatedReportPrompts::getHalfYearlyReportPrompt($aggregatedAnalysis, $periodInfo);

        try {
            $response = self::callOpenAIForAggregatedReport($prompt);
            $responseContent = $response->choices[0]->message->content ?? '';
            $aiContent = \App\Services\AI\ResponseParser::parseAnalysisResponse($responseContent);

            // Extract token usage if available
            $tokensUsed = $response->usage->totalTokens ?? null;

            return [
                'executive_summary' => $aiContent['executive_summary'] ?? '',
                'key_achievements' => $aiContent['major_achievements'] ?? $aiContent['key_achievements'] ?? [],
                'progress_trends' => $aiContent['progress_trends'] ?? [],
                'challenges' => $aiContent['challenges'] ?? [],
                'recommendations' => $aiContent['recommendations'] ?? [],
                'strategic_insights' => $aiContent['strategic_insights'] ?? [],
                'quarterly_comparison' => $aiContent['quarterly_comparison'] ?? [],
                'raw_analysis' => $aggregatedAnalysis,
                'tokens_used' => $tokensUsed,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate AI half-yearly report content', [
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
     * @param HalfYearlyReport $halfYearlyReport
     * @param array $aiInsights
     * @return AIReportInsight
     */
    public static function storeAIInsights(HalfYearlyReport $halfYearlyReport, array $aiInsights): AIReportInsight
    {
        return AIReportInsight::updateOrCreate(
            [
                'report_type' => 'half_yearly',
                'report_id' => $halfYearlyReport->report_id,
            ],
            [
                'executive_summary' => $aiInsights['executive_summary'] ?? null,
                'key_achievements' => $aiInsights['key_achievements'] ?? [],
                'progress_trends' => $aiInsights['progress_trends'] ?? [],
                'challenges' => $aiInsights['challenges'] ?? [],
                'recommendations' => $aiInsights['recommendations'] ?? [],
                'strategic_insights' => $aiInsights['strategic_insights'] ?? [],
                'quarterly_comparison' => $aiInsights['quarterly_comparison'] ?? [],
                'ai_model_used' => config('ai.openai.model', 'gpt-4o-mini'),
                'ai_tokens_used' => $aiInsights['tokens_used'] ?? null,
                'generated_at' => now(),
            ]
        );
    }

    /**
     * Generate and store AI titles
     *
     * @param HalfYearlyReport $halfYearlyReport
     * @param Project $project
     * @param int $halfYear
     * @param int $year
     * @return AIReportTitle
     */
    public static function generateAndStoreAITitles(
        HalfYearlyReport $halfYearlyReport,
        Project $project,
        int $halfYear,
        int $year
    ): AIReportTitle {
        try {
            // Get aggregated analysis for title generation
            $quarterlyReports = self::getQuarterlyReportsForHalfYear($project, $halfYear, $year);
            $monthlyReports = collect();
            if ($quarterlyReports->isEmpty()) {
                $monthlyReports = self::getMonthlyReportsForHalfYear($project, $halfYear, $year);
            } else {
                foreach ($quarterlyReports as $quarterlyReport) {
                    $monthlyReports = $monthlyReports->merge(
                        \App\Models\Reports\Monthly\DPReport::whereIn('report_id', $quarterlyReport->generated_from ?? [])
                            ->with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                            ->get()
                    );
                }
            }

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

            $period = 'H' . $halfYear . ' ' . $year;
            $title = ReportTitleService::generateReportTitle($aggregatedAnalysis, 'half_yearly', $period);
            $headings = ReportTitleService::generateSectionHeadings($aggregatedAnalysis, 'half_yearly');

            return AIReportTitle::updateOrCreate(
                [
                    'report_type' => 'half_yearly',
                    'report_id' => $halfYearlyReport->report_id,
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

            $period = 'H' . $halfYear . ' ' . $year;
            $defaultTitle = "Half-Yearly Report - {$period}";
            $defaultHeadings = [
                'executive_summary' => 'Executive Summary',
                'major_achievements' => 'Major Achievements',
                'progress_trends' => 'Progress Trends',
                'quarterly_comparison' => 'Quarterly Comparison',
                'strategic_insights' => 'Strategic Insights',
                'recommendations' => 'Recommendations',
            ];

            return AIReportTitle::updateOrCreate(
                [
                    'report_type' => 'half_yearly',
                    'report_id' => $halfYearlyReport->report_id,
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
        $maxTokens = config('ai.openai.max_tokens', 4000);
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
            Log::error('OpenAI API call failed for half-yearly report', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get AI-generated insights for an existing half-yearly report
     *
     * @param HalfYearlyReport $halfYearlyReport
     * @return array
     */
    public static function getAIInsights(HalfYearlyReport $halfYearlyReport): array
    {
        $project = $halfYearlyReport->project;
        return self::generateAIInsights(
            $project,
            $halfYearlyReport->half_year,
            $halfYearlyReport->year,
            $halfYearlyReport
        );
    }

    /**
     * Generate unique report ID
     */
    private static function generateReportId(Project $project, int $halfYear, int $year): string
    {
        $projectTypePrefix = QuarterlyReportService::getProjectTypePrefix($project->project_type);
        $halfYearLabel = 'H' . $halfYear;

        return sprintf('HY-%d-%s-%s-%s', $year, $halfYearLabel, $projectTypePrefix, $project->project_id);
    }
}
