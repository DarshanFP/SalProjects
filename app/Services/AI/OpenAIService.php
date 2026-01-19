<?php

namespace App\Services\AI;

use App\Models\Reports\Monthly\DPReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use OpenAI\Laravel\Facades\OpenAI;
use Exception;

class OpenAIService
{
    /**
     * Analyze a single monthly report
     *
     * @param DPReport $report
     * @return array
     * @throws Exception
     */
    public static function analyzeMonthlyReport(DPReport $report): array
    {
        try {
            // Check if analysis is cached
            $cacheKey = "ai_analysis_monthly_{$report->report_id}";

            if (config('ai.analysis.enable_caching')) {
                $cached = Cache::get($cacheKey);
                if ($cached !== null) {
                    Log::info('Using cached AI analysis', ['report_id' => $report->report_id]);
                    return $cached;
                }
            }

            // Prepare data for analysis
            $reportData = ReportDataPreparer::prepareReportForAnalysis($report);

            // Get analysis prompt
            $prompt = ReportAnalysisPrompts::getMonthlyReportAnalysisPrompt($reportData);

            // Call OpenAI API
            $response = self::callOpenAI($prompt, 'monthly_analysis');

            // Parse response
            $analysis = ResponseParser::parseAnalysisResponse($response);

            // Cache the result
            if (config('ai.analysis.enable_caching')) {
                Cache::put(
                    $cacheKey,
                    $analysis,
                    config('ai.analysis.cache_duration')
                );
            }

            Log::info('Monthly report analyzed successfully', [
                'report_id' => $report->report_id
            ]);

            return $analysis;

        } catch (Exception $e) {
            Log::error('Error analyzing monthly report', [
                'report_id' => $report->report_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Analyze multiple monthly reports
     *
     * @param Collection $reports
     * @return array
     * @throws Exception
     */
    public static function analyzeMultipleReports(Collection $reports): array
    {
        try {
            // Prepare data for analysis
            $reportsData = ReportDataPreparer::prepareCollectionForAnalysis($reports);

            // Get trend analysis prompt
            $prompt = ReportAnalysisPrompts::getTrendAnalysisPrompt($reportsData);

            // Call OpenAI API
            $response = self::callOpenAI($prompt, 'trend_analysis');

            // Parse response
            $analysis = ResponseParser::parseAnalysisResponse($response);

            Log::info('Multiple reports analyzed successfully', [
                'count' => $reports->count()
            ]);

            return $analysis;

        } catch (Exception $e) {
            Log::error('Error analyzing multiple reports', [
                'count' => $reports->count(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate executive summary from analysis
     *
     * @param array $analysis
     * @return string
     * @throws Exception
     */
    public static function generateExecutiveSummary(array $analysis): string
    {
        try {
            $prompt = ReportAnalysisPrompts::getExecutiveSummaryPrompt($analysis);
            $response = self::callOpenAI($prompt, 'executive_summary');

            return ResponseParser::parseExecutiveSummary($response);

        } catch (Exception $e) {
            Log::error('Error generating executive summary', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Identify key achievements from analysis
     *
     * @param array $analysis
     * @return array
     * @throws Exception
     */
    public static function identifyKeyAchievements(array $analysis): array
    {
        try {
            $prompt = ReportAnalysisPrompts::getKeyAchievementsPrompt($analysis);
            $response = self::callOpenAI($prompt, 'key_achievements');

            return ResponseParser::parseKeyAchievements($response);

        } catch (Exception $e) {
            Log::error('Error identifying key achievements', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Identify trends from analysis
     *
     * @param array $analysis
     * @return array
     * @throws Exception
     */
    public static function identifyTrends(array $analysis): array
    {
        try {
            $prompt = ReportAnalysisPrompts::getTrendsPrompt($analysis);
            $response = self::callOpenAI($prompt, 'trends');

            return ResponseParser::parseTrends($response);

        } catch (Exception $e) {
            Log::error('Error identifying trends', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate insights from analysis
     *
     * @param array $analysis
     * @return array
     * @throws Exception
     */
    public static function generateInsights(array $analysis): array
    {
        try {
            $prompt = ReportAnalysisPrompts::getInsightsPrompt($analysis);
            $response = self::callOpenAI($prompt, 'insights');

            return ResponseParser::parseInsights($response);

        } catch (Exception $e) {
            Log::error('Error generating insights', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate recommendations from analysis
     *
     * @param array $analysis
     * @return array
     * @throws Exception
     */
    public static function generateRecommendations(array $analysis): array
    {
        try {
            $prompt = ReportAnalysisPrompts::getRecommendationsPrompt($analysis);
            $response = self::callOpenAI($prompt, 'recommendations');

            return ResponseParser::parseRecommendations($response);

        } catch (Exception $e) {
            Log::error('Error generating recommendations', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Call OpenAI API with retry logic
     *
     * @param string $prompt
     * @param string $operationType
     * @return string
     * @throws Exception
     */
    private static function callOpenAI(string $prompt, string $operationType): string
    {
        $maxRetries = config('ai.analysis.retry_attempts', 3);
        $retryDelay = config('ai.analysis.retry_delay', 2);

        // Check if API key is configured
        if (!config('openai.api_key')) {
            throw new Exception('OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
        }

        // Check if feature is enabled
        if (!config("ai.features.enable_ai_{$operationType}", true)) {
            throw new Exception("AI feature for {$operationType} is disabled.");
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                $model = config('ai.openai.model', 'gpt-4o-mini');
                $maxTokens = config('ai.openai.max_tokens', 4000);
                $temperature = config('ai.openai.temperature', 0.3);

                Log::debug('Calling OpenAI API', [
                    'model' => $model,
                    'operation' => $operationType,
                    'attempt' => $attempt + 1,
                    'prompt_length' => strlen($prompt)
                ]);

                $response = OpenAI::chat()->create([
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
                    throw new Exception('Empty response from OpenAI API');
                }

                Log::info('OpenAI API call successful', [
                    'operation' => $operationType,
                    'tokens_used' => $response->usage->totalTokens ?? 'unknown'
                ]);

                return $content;

            } catch (Exception $e) {
                $lastException = $e;
                $attempt++;

                Log::warning('OpenAI API call failed', [
                    'operation' => $operationType,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage()
                ]);

                if ($attempt < $maxRetries) {
                    sleep($retryDelay * $attempt); // Exponential backoff
                }
            }
        }

        throw new Exception(
            "OpenAI API call failed after {$maxRetries} attempts: " .
            ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }
}
