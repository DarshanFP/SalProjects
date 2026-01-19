<?php

namespace App\Services\AI;

use App\Models\Reports\Monthly\DPReport;
use Illuminate\Support\Facades\Log;

class ReportDataValidationService
{
    /**
     * Validate report data using AI to identify inconsistencies and issues
     *
     * @param DPReport $report
     * @return array
     */
    public static function validateReportData(DPReport $report): array
    {
        try {
            // Load relationships
            $report->load([
                'objectives.activities',
                'accountDetails',
                'photos',
                'outlooks',
                'project'
            ]);

            // Prepare report data for validation
            $reportData = self::prepareReportForValidation($report);

            // Get validation prompt
            $prompt = self::getValidationPrompt($reportData);

            // Call OpenAI API
            $response = self::callOpenAIForValidation($prompt);
            $validation = self::parseValidationResponse($response);

            // Add programmatic validations
            $programmaticChecks = self::performProgrammaticChecks($report);

            // Combine AI and programmatic validations
            $validation['programmatic_checks'] = $programmaticChecks;
            $validation['overall_status'] = self::determineOverallStatus($validation, $programmaticChecks);

            Log::info('Report data validated', [
                'report_id' => $report->report_id,
                'overall_status' => $validation['overall_status']
            ]);

            return $validation;

        } catch (\Exception $e) {
            Log::error('Error validating report data', [
                'report_id' => $report->report_id,
                'error' => $e->getMessage()
            ]);

            // Return programmatic checks only if AI fails
            return [
                'overall_status' => 'warning',
                'ai_validation' => [
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ],
                'programmatic_checks' => self::performProgrammaticChecks($report),
                'warnings' => ['AI validation unavailable, using programmatic checks only'],
            ];
        }
    }

    /**
     * Prepare report data for validation
     *
     * @param DPReport $report
     * @return array
     */
    private static function prepareReportForValidation(DPReport $report): array
    {
        $objectives = [];
        foreach ($report->objectives as $objective) {
            $objectives[] = [
                'objective' => $objective->objective ?? '',
                'not_happened' => $objective->not_happened ?? '',
                'why_not_happened' => $objective->why_not_happened ?? '',
                'changes' => $objective->changes ?? false,
                'why_changes' => $objective->why_changes ?? '',
                'lessons_learnt' => $objective->lessons_learnt ?? '',
            ];
        }

        $totalExpenses = $report->accountDetails->sum('total_expenses') ?? 0;
        $totalBudget = ($report->amount_sanctioned_overview ?? 0) + ($report->amount_forwarded_overview ?? 0);

        return [
            'report_id' => $report->report_id,
            'period' => $report->report_month_year,
            'project_title' => $report->project_title,
            'objectives' => $objectives,
            'objectives_count' => count($objectives),
            'budget' => [
                'sanctioned' => (float)($report->amount_sanctioned_overview ?? 0),
                'forwarded' => (float)($report->amount_forwarded_overview ?? 0),
                'total' => $totalBudget,
                'expenses' => $totalExpenses,
                'balance' => (float)($report->total_balance_forwarded ?? 0),
            ],
            'beneficiaries' => $report->total_beneficiaries ?? 0,
            'photos_count' => $report->photos->count(),
            'outlooks_count' => $report->outlooks->count(),
        ];
    }

    /**
     * Get validation prompt
     *
     * @param array $reportData
     * @return string
     */
    private static function getValidationPrompt(array $reportData): string
    {
        $jsonData = json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Analyze the following monthly report data and identify any inconsistencies, missing information, unusual patterns, or potential errors. Provide your response in JSON format:

{
  "inconsistencies": [
    {
      "type": "budget|objectives|beneficiaries|data_quality",
      "description": "Description of inconsistency",
      "severity": "high|medium|low",
      "evidence": "Supporting evidence",
      "recommendation": "How to fix"
    }
  ],
  "missing_information": [
    {
      "field": "Field name",
      "description": "What is missing",
      "importance": "critical|important|optional",
      "recommendation": "What should be added"
    }
  ],
  "unusual_patterns": [
    {
      "pattern": "Description of unusual pattern",
      "significance": "high|medium|low",
      "explanation": "Why this is unusual",
      "recommendation": "What to investigate"
    }
  ],
  "potential_errors": [
    {
      "error": "Description of potential error",
      "type": "calculation|data_entry|logical",
      "severity": "high|medium|low",
      "recommendation": "How to verify or fix"
    }
  ],
  "data_quality_score": 0-100,
  "overall_assessment": "excellent|good|fair|poor",
  "recommendations": [
    "General recommendation for improving data quality"
  ]
}

Report Data:
{$jsonData}

Focus on:
1. Budget calculations and consistency
2. Objectives completeness and coherence
3. Data completeness
4. Logical consistency
5. Unusual values or patterns
PROMPT;
    }

    /**
     * Parse validation response
     *
     * @param string $response
     * @return array
     */
    private static function parseValidationResponse(string $response): array
    {
        try {
            $json = ResponseParser::extractJson($response);

            if ($json) {
                return [
                    'status' => 'success',
                    'inconsistencies' => $json['inconsistencies'] ?? [],
                    'missing_information' => $json['missing_information'] ?? [],
                    'unusual_patterns' => $json['unusual_patterns'] ?? [],
                    'potential_errors' => $json['potential_errors'] ?? [],
                    'data_quality_score' => $json['data_quality_score'] ?? null,
                    'overall_assessment' => $json['overall_assessment'] ?? 'unknown',
                    'recommendations' => $json['recommendations'] ?? [],
                ];
            }

            return [
                'status' => 'partial',
                'raw_response' => $response,
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to parse validation response', [
                'error' => $e->getMessage()
            ]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Perform programmatic validation checks
     *
     * @param DPReport $report
     * @return array
     */
    private static function performProgrammaticChecks(DPReport $report): array
    {
        $checks = [];
        $warnings = [];
        $errors = [];

        // Check budget consistency
        $totalBudget = ($report->amount_sanctioned_overview ?? 0) + ($report->amount_forwarded_overview ?? 0);
        $totalExpenses = $report->accountDetails->sum('total_expenses') ?? 0;

        if ($totalExpenses > $totalBudget * 1.1) {
            $errors[] = [
                'type' => 'budget',
                'message' => 'Total expenses exceed budget by more than 10%',
                'severity' => 'high',
            ];
        } elseif ($totalExpenses > $totalBudget) {
            $warnings[] = [
                'type' => 'budget',
                'message' => 'Total expenses exceed budget',
                'severity' => 'medium',
            ];
        }

        // Check objectives completeness
        if ($report->objectives->isEmpty()) {
            $warnings[] = [
                'type' => 'objectives',
                'message' => 'No objectives found in report',
                'severity' => 'medium',
            ];
        }

        // Check for objectives without progress information
        foreach ($report->objectives as $objective) {
            if (empty($objective->not_happened) && empty($objective->lessons_learnt)) {
                $warnings[] = [
                    'type' => 'objectives',
                    'message' => "Objective '{$objective->objective}' has no progress information",
                    'severity' => 'low',
                ];
            }
        }

        // Check beneficiaries
        if (empty($report->total_beneficiaries) || $report->total_beneficiaries < 0) {
            $warnings[] = [
                'type' => 'beneficiaries',
                'message' => 'Beneficiary count is missing or invalid',
                'severity' => 'medium',
            ];
        }

        // Check photos
        if ($report->photos->isEmpty()) {
            $warnings[] = [
                'type' => 'photos',
                'message' => 'No photos attached to report',
                'severity' => 'low',
            ];
        }

        return [
            'checks_performed' => true,
            'errors' => $errors,
            'warnings' => $warnings,
            'errors_count' => count($errors),
            'warnings_count' => count($warnings),
        ];
    }

    /**
     * Determine overall validation status
     *
     * @param array $validation
     * @param array $programmaticChecks
     * @return string
     */
    private static function determineOverallStatus(array $validation, array $programmaticChecks): string
    {
        $programmaticErrors = $programmaticChecks['errors_count'] ?? 0;
        $programmaticWarnings = $programmaticChecks['warnings_count'] ?? 0;

        $aiInconsistencies = count($validation['inconsistencies'] ?? []);
        $aiErrors = count($validation['potential_errors'] ?? []);

        // Count high severity issues
        $highSeverityIssues = 0;
        foreach ($validation['inconsistencies'] ?? [] as $issue) {
            if (($issue['severity'] ?? '') === 'high') {
                $highSeverityIssues++;
            }
        }

        if ($programmaticErrors > 0 || $highSeverityIssues > 0) {
            return 'error';
        } elseif ($programmaticWarnings > 3 || $aiInconsistencies > 0 || $aiErrors > 0) {
            return 'warning';
        } else {
            return 'ok';
        }
    }

    /**
     * Call OpenAI API for validation
     *
     * @param string $prompt
     * @return string
     * @throws \Exception
     */
    private static function callOpenAIForValidation(string $prompt): string
    {
        if (!config('openai.api_key')) {
            throw new \Exception('OpenAI API key is not configured.');
        }

        $model = config('ai.openai.model', 'gpt-4o-mini');
        $maxTokens = config('ai.openai.max_tokens', 4000);
        $temperature = 0.2; // Lower temperature for validation (more focused)

        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert data validator specializing in development project reports. Identify inconsistencies, missing information, and potential errors accurately.'
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
            Log::error('OpenAI API call failed for data validation', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
