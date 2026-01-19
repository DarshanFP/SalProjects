<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class ResponseParser
{
    /**
     * Parse AI analysis response
     *
     * @param string $response
     * @return array
     */
    public static function parseAnalysisResponse(string $response): array
    {
        try {
            // Try to extract JSON from response
            $json = self::extractJson($response);

            if ($json === null) {
                Log::warning('Could not extract JSON from AI response', [
                    'response_preview' => substr($response, 0, 200)
                ]);

                // Fallback: return structured response with raw text
                return [
                    'raw_response' => $response,
                    'parsed' => false,
                ];
            }

            return $json;

        } catch (\Exception $e) {
            Log::error('Error parsing AI analysis response', [
                'error' => $e->getMessage(),
                'response_preview' => substr($response, 0, 200)
            ]);

            return [
                'raw_response' => $response,
                'parsed' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse executive summary from response
     *
     * @param string $response
     * @return string
     */
    public static function parseExecutiveSummary(string $response): string
    {
        // Clean up the response
        $summary = trim($response);

        // Remove markdown formatting if present
        $summary = preg_replace('/^#+\s*/m', '', $summary);
        $summary = preg_replace('/\*\*(.*?)\*\*/', '$1', $summary);

        return $summary;
    }

    /**
     * Parse key achievements from response
     *
     * @param string $response
     * @return array
     */
    public static function parseKeyAchievements(string $response): array
    {
        try {
            $json = self::extractJson($response);

            if ($json && isset($json['achievements'])) {
                return $json['achievements'];
            }

            // Fallback: try to extract as array
            if (is_array($json) && isset($json[0])) {
                return $json;
            }

            // Last resort: return as single item array
            return [
                [
                    'title' => 'Achievement',
                    'description' => $response,
                    'impact' => ''
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error parsing key achievements', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Parse trends from response
     *
     * @param string $response
     * @return array
     */
    public static function parseTrends(string $response): array
    {
        try {
            $json = self::extractJson($response);

            if ($json && isset($json['trends'])) {
                return $json['trends'];
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Error parsing trends', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Parse insights from response
     *
     * @param string $response
     * @return array
     */
    public static function parseInsights(string $response): array
    {
        try {
            $json = self::extractJson($response);

            if ($json && isset($json['insights'])) {
                return $json['insights'];
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Error parsing insights', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Parse recommendations from response
     *
     * @param string $response
     * @return array
     */
    public static function parseRecommendations(string $response): array
    {
        try {
            $json = self::extractJson($response);

            if ($json && isset($json['recommendations'])) {
                return $json['recommendations'];
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Error parsing recommendations', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Extract JSON from response string
     *
     * @param string $response
     * @return array|null
     */
    private static function extractJson(string $response): ?array
    {
        // Try to find JSON in the response
        // Look for JSON code blocks first
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $json = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        // Try to find JSON object directly
        if (preg_match('/(\{.*\})/s', $response, $matches)) {
            $json = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        // Try parsing the entire response as JSON
        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return null;
    }

    /**
     * Extract JSON from response (public method for use by other services)
     *
     * @param string $response
     * @return array|null
     */
    public static function extractJson(string $response): ?array
    {
        // Try to find JSON in the response
        // Look for JSON code blocks first
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $json = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        // Try to find JSON object directly
        if (preg_match('/(\{.*\})/s', $response, $matches)) {
            $json = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        // Try parsing the entire response as JSON
        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return null;
    }
}
