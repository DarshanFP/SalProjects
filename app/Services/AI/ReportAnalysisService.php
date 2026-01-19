<?php

namespace App\Services\AI;

use App\Models\Reports\Monthly\DPReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReportAnalysisService
{
    /**
     * Analyze a single monthly report
     *
     * @param DPReport $report
     * @return array
     */
    public static function analyzeSingleReport(DPReport $report): array
    {
        try {
            Log::info('Starting analysis of single report', [
                'report_id' => $report->report_id
            ]);

            // Use OpenAI service to analyze the report
            $analysis = OpenAIService::analyzeMonthlyReport($report);

            // Extract additional structured information
            $analysis['report_metadata'] = [
                'report_id' => $report->report_id,
                'period' => $report->report_month_year,
                'project_title' => $report->project_title,
                'project_type' => $report->project_type,
            ];

            // Add budget analysis
            $analysis['budget_analysis'] = self::analyzeBudget($report);

            // Add objectives analysis
            $analysis['objectives_analysis'] = self::analyzeObjectives($report);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Error in analyzeSingleReport', [
                'report_id' => $report->report_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Analyze a collection of reports for trends
     *
     * @param Collection $reports
     * @return array
     */
    public static function analyzeReportCollection(Collection $reports): array
    {
        try {
            Log::info('Starting analysis of report collection', [
                'count' => $reports->count()
            ]);

            // Use OpenAI service for trend analysis
            $trendAnalysis = OpenAIService::analyzeMultipleReports($reports);

            // Add structured trend data
            $trendAnalysis['statistical_trends'] = self::calculateStatisticalTrends($reports);

            return $trendAnalysis;

        } catch (\Exception $e) {
            Log::error('Error in analyzeReportCollection', [
                'count' => $reports->count(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Extract only key information from a report
     *
     * @param DPReport $report
     * @return array
     */
    public static function extractKeyInformation(DPReport $report): array
    {
        try {
            $analysis = self::analyzeSingleReport($report);

            // Filter to only essential information
            return [
                'key_achievements' => $analysis['key_achievements'] ?? [],
                'budget_status' => $analysis['budget_status'] ?? null,
                'challenges' => $analysis['challenges'] ?? [],
                'lessons_learnt' => $analysis['lessons_learnt'] ?? [],
                'key_insights' => $analysis['key_insights'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Error in extractKeyInformation', [
                'report_id' => $report->report_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Analyze budget from report
     *
     * @param DPReport $report
     * @return array
     */
    private static function analyzeBudget(DPReport $report): array
    {
        $report->load('accountDetails');

        $totalBudget = $report->accountDetails->sum('budget');
        $totalExpenses = $report->accountDetails->sum('expenses');
        $sanctioned = (float)($report->amount_sanctioned_overview ?? 0);
        $forwarded = (float)($report->amount_forwarded_overview ?? 0);
        $balance = (float)($report->total_balance_forwarded ?? 0);

        $utilization = $sanctioned > 0 ? ($totalExpenses / $sanctioned) * 100 : 0;

        return [
            'sanctioned' => $sanctioned,
            'forwarded' => $forwarded,
            'expenses' => $totalExpenses,
            'balance' => $balance,
            'utilization_percentage' => round($utilization, 2),
            'status' => self::getBudgetStatus($utilization),
        ];
    }

    /**
     * Analyze objectives from report
     *
     * @param DPReport $report
     * @return array
     */
    private static function analyzeObjectives(DPReport $report): array
    {
        $report->load('objectives.activities');

        $objectives = [];
        foreach ($report->objectives as $objective) {
            $objectives[] = [
                'objective' => $objective->objective,
                'has_changes' => $objective->changes ?? false,
                'has_not_happened' => !empty($objective->not_happened),
                'has_lessons' => !empty($objective->lessons_learnt),
                'activities_count' => $objective->activities->count(),
            ];
        }

        return [
            'total_objectives' => count($objectives),
            'objectives_with_changes' => count(array_filter($objectives, fn($o) => $o['has_changes'])),
            'objectives_not_completed' => count(array_filter($objectives, fn($o) => $o['has_not_happened'])),
            'objectives_with_lessons' => count(array_filter($objectives, fn($o) => $o['has_lessons'])),
            'objectives' => $objectives,
        ];
    }

    /**
     * Calculate statistical trends from reports
     *
     * @param Collection $reports
     * @return array
     */
    private static function calculateStatisticalTrends(Collection $reports): array
    {
        $reports->load('accountDetails');

        $expenses = [];
        $beneficiaries = [];

        foreach ($reports->sortBy('report_month_year') as $report) {
            $totalExpenses = $report->accountDetails->sum('expenses');
            $expenses[] = [
                'period' => $report->report_month_year,
                'amount' => $totalExpenses,
            ];
            $beneficiaries[] = [
                'period' => $report->report_month_year,
                'count' => $report->total_beneficiaries ?? 0,
            ];
        }

        return [
            'expense_trend' => self::calculateTrend($expenses, 'amount'),
            'beneficiary_trend' => self::calculateTrend($beneficiaries, 'count'),
        ];
    }

    /**
     * Calculate trend direction
     *
     * @param array $data
     * @param string $key
     * @return string
     */
    private static function calculateTrend(array $data, string $key): string
    {
        if (count($data) < 2) {
            return 'insufficient_data';
        }

        $first = $data[0][$key];
        $last = end($data)[$key];

        if ($last > $first * 1.1) {
            return 'increasing';
        } elseif ($last < $first * 0.9) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Get budget status based on utilization
     *
     * @param float $utilization
     * @return string
     */
    private static function getBudgetStatus(float $utilization): string
    {
        if ($utilization > 100) {
            return 'over_budget';
        } elseif ($utilization > 80) {
            return 'high_utilization';
        } elseif ($utilization > 50) {
            return 'on_track';
        } else {
            return 'under_utilized';
        }
    }
}
