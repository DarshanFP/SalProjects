<?php

namespace App\Services\Reports;

use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Reports\Quarterly\QuarterlyReport;
use App\Models\Reports\Quarterly\QuarterlyReportDetail;
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

class QuarterlyReportService
{
    /**
     * Generate a quarterly report from monthly reports
     *
     * @param Project $project
     * @param int $quarter (1-4)
     * @param int $year
     * @param User $user
     * @return QuarterlyReport
     * @throws \Exception
     */
    public static function generateQuarterlyReport(Project $project, int $quarter, int $year, User $user): QuarterlyReport
    {
        DB::beginTransaction();
        try {
            // Calculate quarter period
            $period = self::calculateQuarterPeriod($quarter, $year);

            // Get monthly reports for the quarter
            $monthlyReports = self::getMonthlyReportsForQuarter($project, $quarter, $year);

            // Validate data
            $validation = self::validateQuarterlyData($monthlyReports, $quarter, $year);
            if (!$validation['valid']) {
                throw new \Exception('Validation failed: ' . implode(', ', $validation['errors']));
            }

            // Generate report ID
            $reportId = self::generateReportId($project, $quarter, $year);

            // Aggregate data from monthly reports
            $aggregatedData = self::aggregateMonthlyReports($monthlyReports, $project, $period);

            // Create quarterly report
            $quarterlyReport = QuarterlyReport::create([
                'report_id' => $reportId,
                'project_id' => $project->project_id,
                'generated_by_user_id' => $user->id,
                'quarter' => $quarter,
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
                'generated_from' => $monthlyReports->pluck('report_id')->toArray(),
                'generated_at' => now(),
            ]);

            // Create report details (budget/account details)
            self::createQuarterlyReportDetails($quarterlyReport, $monthlyReports, $aggregatedData);

            // Aggregate objectives
            self::aggregateObjectives($quarterlyReport, $monthlyReports);

            // Aggregate photos
            self::aggregatePhotos($quarterlyReport, $monthlyReports);

            DB::commit();

            Log::info('Quarterly report generated successfully', [
                'report_id' => $reportId,
                'project_id' => $project->project_id,
                'quarter' => $quarter,
                'year' => $year,
            ]);

            return $quarterlyReport;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate quarterly report', [
                'project_id' => $project->project_id,
                'quarter' => $quarter,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate quarter period dates
     */
    private static function calculateQuarterPeriod(int $quarter, int $year): array
    {
        $quarterMonths = [
            1 => [1, 2, 3],   // Q1: Jan-Mar
            2 => [4, 5, 6],   // Q2: Apr-Jun
            3 => [7, 8, 9],   // Q3: Jul-Sep
            4 => [10, 11, 12], // Q4: Oct-Dec
        ];

        $months = $quarterMonths[$quarter] ?? [1, 2, 3];
        $firstMonth = $months[0];
        $lastMonth = end($months);

        $from = Carbon::create($year, $firstMonth, 1)->startOfMonth();
        $to = Carbon::create($year, $lastMonth, 1)->endOfMonth();

        return [
            'from' => $from,
            'to' => $to,
            'months' => $months,
        ];
    }

    /**
     * Get approved monthly reports for the quarter
     */
    private static function getMonthlyReportsForQuarter(Project $project, int $quarter, int $year): Collection
    {
        $period = self::calculateQuarterPeriod($quarter, $year);

        return DPReport::where('project_id', $project->project_id)
            ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
            ->whereBetween('report_month_year', [$period['from'], $period['to']])
            ->with(['accountDetails', 'objectives.activities', 'photos', 'attachments'])
            ->orderBy('report_month_year', 'asc')
            ->get();
    }

    /**
     * Validate quarterly data
     */
    public static function validateQuarterlyData(Collection $monthlyReports, int $quarter, int $year): array
    {
        $errors = [];
        $warnings = [];

        if ($monthlyReports->isEmpty()) {
            $errors[] = 'No approved monthly reports found for the quarter';
        }

        $period = self::calculateQuarterPeriod($quarter, $year);
        $expectedMonths = $period['months'];
        $foundMonths = $monthlyReports->map(function ($report) {
            return Carbon::parse($report->report_month_year)->month;
        })->unique()->sort()->values();

        $missingMonths = array_diff($expectedMonths, $foundMonths->toArray());
        if (!empty($missingMonths)) {
            $warnings[] = 'Missing monthly reports for months: ' . implode(', ', $missingMonths);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Aggregate data from monthly reports
     */
    public static function aggregateMonthlyReports(Collection $monthlyReports, Project $project, array $period): array
    {
        if ($monthlyReports->isEmpty()) {
            throw new \Exception('No monthly reports to aggregate');
        }

        // Use first report for basic info
        $firstReport = $monthlyReports->first();

        // Total beneficiaries: use latest value
        $totalBeneficiaries = $monthlyReports->max('total_beneficiaries') ?? $firstReport->total_beneficiaries;

        // Goal: use from first report or project
        $goal = $firstReport->goal ?? $project->goal ?? '';

        // Budget overview: sum from all reports
        $amountSanctionedOverview = $monthlyReports->sum('amount_sanctioned_overview');
        $amountForwardedOverview = $monthlyReports->sum('amount_forwarded_overview');
        $amountInHand = $amountSanctionedOverview + $amountForwardedOverview;

        // Total balance forwarded: from last report
        $lastReport = $monthlyReports->last();
        $totalBalanceForwarded = $lastReport->total_balance_forwarded ?? 0;

        return [
            'total_beneficiaries' => $totalBeneficiaries,
            'goal' => $goal,
            'amount_sanctioned_overview' => $amountSanctionedOverview,
            'amount_forwarded_overview' => $amountForwardedOverview,
            'amount_in_hand' => $amountInHand,
            'total_balance_forwarded' => $totalBalanceForwarded,
        ];
    }

    /**
     * Calculate quarterly budget from monthly reports
     */
    private static function calculateQuarterlyBudget(Collection $monthlyReports): array
    {
        // Get all account details from all monthly reports
        $allAccountDetails = collect();
        foreach ($monthlyReports as $report) {
            foreach ($report->accountDetails as $detail) {
                $allAccountDetails->push([
                    'report' => $report,
                    'detail' => $detail,
                    'month' => Carbon::parse($report->report_month_year)->month,
                ]);
            }
        }

        // Group by particulars
        $groupedByParticulars = $allAccountDetails->groupBy(function ($item) {
            return $item['detail']->particulars;
        });

        $quarterlyDetails = [];
        foreach ($groupedByParticulars as $particular => $items) {
            $firstItem = $items->first();
            $firstReport = $firstItem['report'];
            $firstDetail = $firstItem['detail'];

            // Opening balance: from first month's balance or previous quarter
            $openingBalance = $firstDetail->balance_amount ?? 0;

            // Sum amounts
            $amountForwarded = $items->sum(function ($item) {
                return $item['detail']->amount_forwarded ?? 0;
            });
            $amountSanctioned = $items->sum(function ($item) {
                return $item['detail']->amount_sanctioned ?? 0;
            });
            $totalAmount = $amountForwarded + $amountSanctioned;

            // Sum expenses
            $totalExpenses = $items->sum(function ($item) {
                return $item['detail']->total_expenses ?? 0;
            });

            // Expenses by month
            $expensesByMonth = [];
            foreach ($items as $item) {
                $month = $item['month'];
                $expensesByMonth['month' . $month] = $item['detail']->total_expenses ?? 0;
            }

            // Closing balance
            $closingBalance = $totalAmount - $totalExpenses;

            $quarterlyDetails[] = [
                'particulars' => $particular,
                'opening_balance' => $openingBalance,
                'amount_forwarded' => $amountForwarded,
                'amount_sanctioned' => $amountSanctioned,
                'total_amount' => $totalAmount,
                'total_expenses' => $totalExpenses,
                'closing_balance' => $closingBalance,
                'expenses_by_month' => $expensesByMonth,
            ];
        }

        return $quarterlyDetails;
    }

    /**
     * Create quarterly report details
     */
    private static function createQuarterlyReportDetails(QuarterlyReport $quarterlyReport, Collection $monthlyReports, array $aggregatedData): void
    {
        $quarterlyDetails = self::calculateQuarterlyBudget($monthlyReports);

        foreach ($quarterlyDetails as $detail) {
            QuarterlyReportDetail::create([
                'quarterly_report_id' => $quarterlyReport->id, // Use id (auto-increment), not report_id (string)
                'particulars' => $detail['particulars'],
                'opening_balance' => $detail['opening_balance'],
                'amount_forwarded' => $detail['amount_forwarded'],
                'amount_sanctioned' => $detail['amount_sanctioned'],
                'total_amount' => $detail['total_amount'],
                'total_expenses' => $detail['total_expenses'],
                'closing_balance' => $detail['closing_balance'],
                'expenses_by_month' => $detail['expenses_by_month'],
            ]);
        }
    }

    /**
     * Aggregate objectives from monthly reports
     */
    private static function aggregateObjectives(QuarterlyReport $quarterlyReport, Collection $monthlyReports): void
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

                // Add monthly breakdown
                $month = Carbon::parse($report->report_month_year)->format('F Y');
                $objectivesMap[$key]['monthly_breakdown'][$month] = [
                    'progress' => $objective->not_happened ?? '',
                    'changes' => $objective->changes ?? '',
                    'lessons_learnt' => $objective->lessons_learnt ?? '',
                ];
            }
        }

        // Create aggregated objectives
        foreach ($objectivesMap as $key => $data) {
            AggregatedReportObjective::create([
                'report_type' => 'quarterly',
                'report_id' => $quarterlyReport->id,
                'objective_text' => $data['objective_text'],
                'project_objective_id' => $data['project_objective_id'],
                'cumulative_progress' => $data['cumulative_progress'],
                'monthly_breakdown' => $data['monthly_breakdown'],
            ]);
        }
    }

    /**
     * Aggregate photos from monthly reports
     */
    private static function aggregatePhotos(QuarterlyReport $quarterlyReport, Collection $monthlyReports, int $limit = 30): void
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
                    'report_type' => 'quarterly',
                    'report_id' => $quarterlyReport->id,
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
     * Generate a quarterly report with AI enhancement
     *
     * @param Project $project
     * @param int $quarter
     * @param int $year
     * @param User $user
     * @param bool $useAI
     * @return QuarterlyReport
     * @throws \Exception
     */
    public static function generateQuarterlyReportWithAI(
        Project $project,
        int $quarter,
        int $year,
        User $user,
        bool $useAI = true
    ): QuarterlyReport {
        // Generate base report
        $quarterlyReport = self::generateQuarterlyReport($project, $quarter, $year, $user);

        // Add AI enhancements if enabled
        if ($useAI && config('ai.features.enable_ai_generation', true)) {
            try {
                $aiInsights = self::generateAIInsights($project, $quarter, $year, $quarterlyReport);

                // Store AI insights in database
                self::storeAIInsights($quarterlyReport, $aiInsights);

                // Generate and store AI titles
                self::generateAndStoreAITitles($quarterlyReport, $project, $quarter, $year);

                Log::info('AI insights generated and stored for quarterly report', [
                    'report_id' => $quarterlyReport->report_id
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to generate AI insights, continuing with base report', [
                    'report_id' => $quarterlyReport->report_id,
                    'error' => $e->getMessage()
                ]);
                // Continue without AI if it fails
            }
        }

        return $quarterlyReport;
    }

    /**
     * Generate AI insights for quarterly report
     *
     * @param Project $project
     * @param int $quarter
     * @param int $year
     * @param QuarterlyReport $quarterlyReport
     * @return array
     */
    private static function generateAIInsights(
        Project $project,
        int $quarter,
        int $year,
        QuarterlyReport $quarterlyReport
    ): array {
        // Get monthly reports for analysis
        $monthlyReports = self::getMonthlyReportsForQuarter($project, $quarter, $year);

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

        // Aggregate analysis results
        $aggregatedAnalysis = self::aggregateAnalysisResults($analysisResults);

        // Generate quarterly report summary using AI
        $periodInfo = [
            'quarter' => 'Q' . $quarter,
            'year' => $year,
        ];

        $prompt = AggregatedReportPrompts::getQuarterlyReportPrompt($aggregatedAnalysis, $periodInfo);

        try {
            // Use OpenAI service to generate content
            $response = self::callOpenAIForAggregatedReport($prompt);
            $responseContent = $response->choices[0]->message->content ?? '';
            $aiContent = \App\Services\AI\ResponseParser::parseAnalysisResponse($responseContent);

            // Extract token usage if available
            $tokensUsed = $response->usage->totalTokens ?? null;

            return [
                'executive_summary' => $aiContent['executive_summary'] ?? '',
                'key_achievements' => $aiContent['key_achievements'] ?? [],
                'progress_trends' => $aiContent['progress_trends'] ?? [],
                'challenges' => $aiContent['challenges'] ?? [],
                'recommendations' => $aiContent['recommendations'] ?? [],
                'raw_analysis' => $aggregatedAnalysis,
                'tokens_used' => $tokensUsed,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate AI quarterly report content', [
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
     * @param QuarterlyReport $quarterlyReport
     * @param array $aiInsights
     * @return AIReportInsight
     */
    public static function storeAIInsights(QuarterlyReport $quarterlyReport, array $aiInsights): AIReportInsight
    {
        return AIReportInsight::updateOrCreate(
            [
                'report_type' => 'quarterly',
                'report_id' => $quarterlyReport->report_id,
            ],
            [
                'executive_summary' => $aiInsights['executive_summary'] ?? null,
                'key_achievements' => $aiInsights['key_achievements'] ?? [],
                'progress_trends' => $aiInsights['progress_trends'] ?? [],
                'challenges' => $aiInsights['challenges'] ?? [],
                'recommendations' => $aiInsights['recommendations'] ?? [],
                'ai_model_used' => config('ai.openai.model', 'gpt-4o-mini'),
                'ai_tokens_used' => $aiInsights['tokens_used'] ?? null,
                'generated_at' => now(),
            ]
        );
    }

    /**
     * Generate and store AI titles
     *
     * @param QuarterlyReport $quarterlyReport
     * @param Project $project
     * @param int $quarter
     * @param int $year
     * @return AIReportTitle
     */
    public static function generateAndStoreAITitles(
        QuarterlyReport $quarterlyReport,
        Project $project,
        int $quarter,
        int $year
    ): AIReportTitle {
        try {
            // Get aggregated analysis for title generation
            $monthlyReports = self::getMonthlyReportsForQuarter($project, $quarter, $year);
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

            $period = 'Q' . $quarter . ' ' . $year;
            $title = ReportTitleService::generateReportTitle($aggregatedAnalysis, 'quarterly', $period);
            $headings = ReportTitleService::generateSectionHeadings($aggregatedAnalysis, 'quarterly');

            return AIReportTitle::updateOrCreate(
                [
                    'report_type' => 'quarterly',
                    'report_id' => $quarterlyReport->report_id,
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

            // Use default title if AI fails
            $period = 'Q' . $quarter . ' ' . $year;
            $defaultTitle = "Quarterly Report - {$period}";
            $defaultHeadings = [
                'executive_summary' => 'Executive Summary',
                'key_achievements' => 'Key Achievements',
                'progress_trends' => 'Progress Trends',
                'challenges' => 'Challenges Faced',
                'recommendations' => 'Recommendations',
            ];

            return AIReportTitle::updateOrCreate(
                [
                    'report_type' => 'quarterly',
                    'report_id' => $quarterlyReport->report_id,
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
        // Check if API key is configured
        if (!config('openai.api_key')) {
            throw new \Exception('OpenAI API key is not configured.');
        }

        // Check if feature is enabled
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
            Log::error('OpenAI API call failed for quarterly report', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get AI-generated insights for an existing quarterly report
     *
     * @param QuarterlyReport $quarterlyReport
     * @return array
     */
    public static function getAIInsights(QuarterlyReport $quarterlyReport): array
    {
        $project = $quarterlyReport->project;
        return self::generateAIInsights(
            $project,
            $quarterlyReport->quarter,
            $quarterlyReport->year,
            $quarterlyReport
        );
    }

    /**
     * Generate unique report ID
     */
    private static function generateReportId(Project $project, int $quarter, int $year): string
    {
        $projectTypePrefix = self::getProjectTypePrefix($project->project_type);
        $quarterLabel = 'Q' . $quarter;

        // Format: QR-2025-Q1-DP-0001
        return sprintf('QR-%d-%s-%s-%s', $year, $quarterLabel, $projectTypePrefix, $project->project_id);
    }

    /**
     * Get project type prefix for report ID
     */
    public static function getProjectTypePrefix(string $projectType): string
    {
        $prefixes = [
            'Development Projects' => 'DP',
            'Livelihood Development Projects' => 'LDP',
            'Residential Skill Training Proposal 2' => 'RST',
            'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' => 'CIC',
            'CHILD CARE INSTITUTION' => 'CCI',
            'Rural-Urban-Tribal' => 'EduRUT',
            'Individual - Livelihood Application' => 'ILP',
            'Individual - Access to Health' => 'IAH',
            'Institutional Ongoing Group Educational proposal' => 'IGE',
            'Individual - Initial - Educational support' => 'IIES',
            'Individual - Ongoing Educational support' => 'IES',
        ];

        return $prefixes[$projectType] ?? 'PRJ';
    }
}
