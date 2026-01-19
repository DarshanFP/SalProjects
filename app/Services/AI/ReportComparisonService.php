<?php

namespace App\Services\AI;

use App\Models\Reports\Quarterly\QuarterlyReport;
use App\Models\Reports\HalfYearly\HalfYearlyReport;
use App\Models\Reports\Annual\AnnualReport;
use App\Services\AI\Prompts\ReportComparisonPrompts;
use Illuminate\Support\Facades\Log;

class ReportComparisonService
{
    /**
     * Compare two quarterly reports
     *
     * @param QuarterlyReport $report1
     * @param QuarterlyReport $report2
     * @return array
     */
    public static function compareQuarterlyReports(
        QuarterlyReport $report1,
        QuarterlyReport $report2
    ): array {
        try {
            Log::info('Comparing quarterly reports', [
                'report1_id' => $report1->report_id,
                'report2_id' => $report2->report_id
            ]);

            // Prepare data for comparison
            $report1Data = self::prepareReportForComparison($report1);
            $report2Data = self::prepareReportForComparison($report2);

            // Get comparison prompt
            $prompt = ReportComparisonPrompts::getQuarterlyComparisonPrompt($report1Data, $report2Data);

            // Call OpenAI API
            $response = self::callOpenAIForComparison($prompt);
            $comparison = ResponseParser::parseAnalysisResponse($response);

            // Add structured comparison data
            $comparison['structured_data'] = self::calculateStructuredComparison($report1, $report2);

            return $comparison;

        } catch (\Exception $e) {
            Log::error('Error comparing quarterly reports', [
                'report1_id' => $report1->report_id,
                'report2_id' => $report2->report_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Compare two half-yearly reports
     *
     * @param HalfYearlyReport $report1
     * @param HalfYearlyReport $report2
     * @return array
     */
    public static function compareHalfYearlyReports(
        HalfYearlyReport $report1,
        HalfYearlyReport $report2
    ): array {
        try {
            Log::info('Comparing half-yearly reports', [
                'report1_id' => $report1->report_id,
                'report2_id' => $report2->report_id
            ]);

            $report1Data = self::prepareReportForComparison($report1);
            $report2Data = self::prepareReportForComparison($report2);

            $prompt = ReportComparisonPrompts::getHalfYearlyComparisonPrompt($report1Data, $report2Data);
            $response = self::callOpenAIForComparison($prompt);
            $comparison = ResponseParser::parseAnalysisResponse($response);

            $comparison['structured_data'] = self::calculateStructuredComparison($report1, $report2);

            return $comparison;

        } catch (\Exception $e) {
            Log::error('Error comparing half-yearly reports', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Compare year-over-year annual reports
     *
     * @param AnnualReport $year1Report
     * @param AnnualReport $year2Report
     * @return array
     */
    public static function compareYearOverYear(
        AnnualReport $year1Report,
        AnnualReport $year2Report
    ): array {
        try {
            Log::info('Comparing year-over-year reports', [
                'year1' => $year1Report->year,
                'year2' => $year2Report->year
            ]);

            $year1Data = self::prepareReportForComparison($year1Report);
            $year2Data = self::prepareReportForComparison($year2Report);

            $prompt = ReportComparisonPrompts::getYearOverYearComparisonPrompt($year1Data, $year2Data);
            $response = self::callOpenAIForComparison($prompt);
            $comparison = ResponseParser::parseAnalysisResponse($response);

            $comparison['structured_data'] = self::calculateStructuredComparison($year1Report, $year2Report);
            $comparison['growth_analysis'] = self::calculateGrowthMetrics($year1Report, $year2Report);

            return $comparison;

        } catch (\Exception $e) {
            Log::error('Error comparing year-over-year reports', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Prepare report data for comparison
     *
     * @param mixed $report
     * @return array
     */
    private static function prepareReportForComparison($report): array
    {
        $report->load(['objectives', 'details', 'photos']);

        $totalExpenses = 0;
        if (method_exists($report, 'details')) {
            $totalExpenses = $report->details->sum('total_expenses') ?? 0;
        }

        return [
            'report_id' => $report->report_id,
            'period' => self::getReportPeriod($report),
            'project_title' => $report->project_title,
            'total_beneficiaries' => $report->total_beneficiaries ?? 0,
            'total_budget' => ($report->amount_sanctioned_overview ?? 0) + ($report->amount_forwarded_overview ?? 0),
            'total_expenses' => $totalExpenses,
            'balance' => $report->total_balance_forwarded ?? 0,
            'objectives_count' => $report->objectives->count() ?? 0,
            'photos_count' => $report->photos->count() ?? 0,
        ];
    }

    /**
     * Get report period label
     *
     * @param mixed $report
     * @return string
     */
    private static function getReportPeriod($report): string
    {
        if (isset($report->quarter) && isset($report->year)) {
            return 'Q' . $report->quarter . ' ' . $report->year;
        } elseif (isset($report->half_year) && isset($report->year)) {
            return 'H' . $report->half_year . ' ' . $report->year;
        } elseif (isset($report->year)) {
            return 'Year ' . $report->year;
        }
        return 'Unknown Period';
    }

    /**
     * Calculate structured comparison data
     *
     * @param mixed $report1
     * @param mixed $report2
     * @return array
     */
    private static function calculateStructuredComparison($report1, $report2): array
    {
        $data1 = self::prepareReportForComparison($report1);
        $data2 = self::prepareReportForComparison($report2);

        return [
            'beneficiaries' => [
                'report1' => $data1['total_beneficiaries'],
                'report2' => $data2['total_beneficiaries'],
                'change' => $data2['total_beneficiaries'] - $data1['total_beneficiaries'],
                'change_percentage' => $data1['total_beneficiaries'] > 0
                    ? (($data2['total_beneficiaries'] - $data1['total_beneficiaries']) / $data1['total_beneficiaries']) * 100
                    : 0,
            ],
            'budget' => [
                'report1' => $data1['total_budget'],
                'report2' => $data2['total_budget'],
                'change' => $data2['total_budget'] - $data1['total_budget'],
                'change_percentage' => $data1['total_budget'] > 0
                    ? (($data2['total_budget'] - $data1['total_budget']) / $data1['total_budget']) * 100
                    : 0,
            ],
            'expenses' => [
                'report1' => $data1['total_expenses'],
                'report2' => $data2['total_expenses'],
                'change' => $data2['total_expenses'] - $data1['total_expenses'],
                'change_percentage' => $data1['total_expenses'] > 0
                    ? (($data2['total_expenses'] - $data1['total_expenses']) / $data1['total_expenses']) * 100
                    : 0,
            ],
            'objectives' => [
                'report1' => $data1['objectives_count'],
                'report2' => $data2['objectives_count'],
                'change' => $data2['objectives_count'] - $data1['objectives_count'],
            ],
        ];
    }

    /**
     * Calculate growth metrics for year-over-year comparison
     *
     * @param AnnualReport $year1
     * @param AnnualReport $year2
     * @return array
     */
    private static function calculateGrowthMetrics(AnnualReport $year1, AnnualReport $year2): array
    {
        $data1 = self::prepareReportForComparison($year1);
        $data2 = self::prepareReportForComparison($year2);

        return [
            'beneficiary_growth_rate' => $data1['total_beneficiaries'] > 0
                ? (($data2['total_beneficiaries'] - $data1['total_beneficiaries']) / $data1['total_beneficiaries']) * 100
                : 0,
            'budget_growth_rate' => $data1['total_budget'] > 0
                ? (($data2['total_budget'] - $data1['total_budget']) / $data1['total_budget']) * 100
                : 0,
            'expense_growth_rate' => $data1['total_expenses'] > 0
                ? (($data2['total_expenses'] - $data1['total_expenses']) / $data1['total_expenses']) * 100
                : 0,
        ];
    }

    /**
     * Call OpenAI API for comparison
     *
     * @param string $prompt
     * @return string
     * @throws \Exception
     */
    private static function callOpenAIForComparison(string $prompt): string
    {
        if (!config('openai.api_key')) {
            throw new \Exception('OpenAI API key is not configured.');
        }

        if (!config('ai.features.enable_ai_comparison', true)) {
            throw new \Exception('AI comparison is disabled.');
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
                        'content' => 'You are an expert report analyst specializing in comparing development project reports. Provide accurate, insightful comparisons highlighting improvements, declines, and key differences.'
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

            return $content;
        } catch (\Exception $e) {
            Log::error('OpenAI API call failed for report comparison', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
