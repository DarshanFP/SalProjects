<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class ReportTitleService
{
    /**
     * Generate a descriptive report title based on analysis
     *
     * @param array $analysis
     * @param string $reportType
     * @param string $period
     * @return string
     */
    public static function generateReportTitle(
        array $analysis,
        string $reportType,
        string $period
    ): string {
        try {
            $prompt = self::getTitleGenerationPrompt($analysis, $reportType, $period);
            $response = self::callOpenAIForTitleGeneration($prompt);

            // Clean and return title
            $title = trim($response);
            $title = preg_replace('/^["\']|["\']$/', '', $title); // Remove quotes
            $title = preg_replace('/^Title:\s*/i', '', $title); // Remove "Title:" prefix

            Log::info('Report title generated', [
                'report_type' => $reportType,
                'period' => $period,
                'title' => $title
            ]);

            return $title ?: self::getDefaultTitle($reportType, $period);

        } catch (\Exception $e) {
            Log::warning('Failed to generate report title, using default', [
                'error' => $e->getMessage()
            ]);
            return self::getDefaultTitle($reportType, $period);
        }
    }

    /**
     * Generate section headings for a report
     *
     * @param array $analysis
     * @param string $reportType
     * @return array
     */
    public static function generateSectionHeadings(array $analysis, string $reportType): array
    {
        try {
            $prompt = self::getHeadingsGenerationPrompt($analysis, $reportType);
            $response = self::callOpenAIForTitleGeneration($prompt);
            $headings = self::parseHeadingsResponse($response);

            Log::info('Section headings generated', [
                'report_type' => $reportType,
                'headings_count' => count($headings)
            ]);

            return $headings;

        } catch (\Exception $e) {
            Log::warning('Failed to generate section headings, using defaults', [
                'error' => $e->getMessage()
            ]);
            return self::getDefaultHeadings($reportType);
        }
    }

    /**
     * Get prompt for title generation
     *
     * @param array $analysis
     * @param string $reportType
     * @param string $period
     * @return string
     */
    private static function getTitleGenerationPrompt(array $analysis, string $reportType, string $period): string
    {
        $jsonData = json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Generate a descriptive, professional report title based on the following analysis. The title should:

1. Be concise (10-15 words maximum)
2. Highlight key achievements or focus areas
3. Include the period ({$period})
4. Be professional and suitable for management review
5. Reflect the report type ({$reportType})

Analysis Data:
{$jsonData}

Provide only the title text, no additional formatting or labels.
PROMPT;
    }

    /**
     * Get prompt for headings generation
     *
     * @param array $analysis
     * @param string $reportType
     * @return string
     */
    private static function getHeadingsGenerationPrompt(array $analysis, string $reportType): string
    {
        $jsonData = json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $expectedSections = [
            'quarterly' => ['Executive Summary', 'Key Achievements', 'Progress Trends', 'Challenges', 'Recommendations'],
            'half_yearly' => ['Executive Summary', 'Major Achievements', 'Progress Trends', 'Quarterly Comparison', 'Strategic Insights', 'Recommendations'],
            'annual' => ['Executive Summary', 'Year-End Achievements', 'Annual Trends', 'Impact Assessment', 'Budget Performance', 'Strategic Recommendations', 'Future Outlook'],
        ];

        $sections = $expectedSections[$reportType] ?? $expectedSections['quarterly'];

        return <<<PROMPT
Generate descriptive section headings for a {$reportType} report based on the following analysis. Provide your response in JSON format:

{
  "headings": {
    "executive_summary": "Heading for executive summary section",
    "key_achievements": "Heading for achievements section",
    "progress_trends": "Heading for trends section",
    "challenges": "Heading for challenges section",
    "recommendations": "Heading for recommendations section"
  }
}

Expected sections: " . implode(', ', $sections) . "

Analysis Data:
{$jsonData}

Make headings:
- Descriptive and informative
- Professional
- 3-8 words each
- Reflective of the content in each section
PROMPT;
    }

    /**
     * Parse headings response
     *
     * @param string $response
     * @return array
     */
    private static function parseHeadingsResponse(string $response): array
    {
        try {
            $json = ResponseParser::extractJson($response);

            if ($json && isset($json['headings']) && is_array($json['headings'])) {
                return $json['headings'];
            }

            return [];

        } catch (\Exception $e) {
            Log::warning('Failed to parse headings response', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get default title
     *
     * @param string $reportType
     * @param string $period
     * @return string
     */
    private static function getDefaultTitle(string $reportType, string $period): string
    {
        $typeLabels = [
            'quarterly' => 'Quarterly Report',
            'half_yearly' => 'Half-Yearly Report',
            'annual' => 'Annual Report',
        ];

        $typeLabel = $typeLabels[$reportType] ?? 'Report';
        return "{$typeLabel} - {$period}";
    }

    /**
     * Get default headings
     *
     * @param string $reportType
     * @return array
     */
    private static function getDefaultHeadings(string $reportType): array
    {
        $defaults = [
            'quarterly' => [
                'executive_summary' => 'Executive Summary',
                'key_achievements' => 'Key Achievements',
                'progress_trends' => 'Progress Trends',
                'challenges' => 'Challenges Faced',
                'recommendations' => 'Recommendations',
            ],
            'half_yearly' => [
                'executive_summary' => 'Executive Summary',
                'major_achievements' => 'Major Achievements',
                'progress_trends' => 'Progress Trends',
                'quarterly_comparison' => 'Quarterly Comparison',
                'strategic_insights' => 'Strategic Insights',
                'recommendations' => 'Recommendations',
            ],
            'annual' => [
                'executive_summary' => 'Executive Summary',
                'year_end_achievements' => 'Year-End Achievements',
                'annual_trends' => 'Annual Trends Analysis',
                'impact_assessment' => 'Impact Assessment',
                'budget_performance' => 'Budget Performance Review',
                'strategic_recommendations' => 'Strategic Recommendations',
                'future_outlook' => 'Future Outlook',
            ],
        ];

        return $defaults[$reportType] ?? $defaults['quarterly'];
    }

    /**
     * Call OpenAI API for title/heading generation
     *
     * @param string $prompt
     * @return string
     * @throws \Exception
     */
    private static function callOpenAIForTitleGeneration(string $prompt): string
    {
        if (!config('openai.api_key')) {
            throw new \Exception('OpenAI API key is not configured.');
        }

        $model = config('ai.openai.model', 'gpt-4o-mini');
        $maxTokens = 500; // Lower for titles/headings
        $temperature = 0.7; // Slightly higher for creativity

        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert at creating professional, descriptive titles and headings for reports.'
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
            Log::error('OpenAI API call failed for title generation', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
