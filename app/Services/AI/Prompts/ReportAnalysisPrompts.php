<?php

namespace App\Services\AI\Prompts;

class ReportAnalysisPrompts
{
    /**
     * Get prompt for analyzing a single monthly report
     *
     * @param array $reportData
     * @return string
     */
    public static function getMonthlyReportAnalysisPrompt(array $reportData): string
    {
        $jsonData = json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Analyze the following monthly project report and extract key information. Provide your response in JSON format with the following structure:

{
  "key_achievements": ["achievement1", "achievement2", ...],
  "objectives_progress": {
    "completed": ["objective1", ...],
    "partial": ["objective2", ...],
    "not_completed": ["objective3", ...],
    "reasons_not_completed": ["reason1", ...]
  },
  "activities_summary": "Summary of activities completed",
  "budget_status": {
    "utilization_percentage": 0.0,
    "status": "on_track|over_budget|under_budget",
    "notes": "Budget utilization notes"
  },
  "challenges": ["challenge1", "challenge2", ...],
  "lessons_learnt": ["lesson1", "lesson2", ...],
  "key_insights": ["insight1", "insight2", ...],
  "beneficiaries_impact": "Description of impact on beneficiaries"
}

Report Data:
{$jsonData}

Focus on:
1. What was achieved this month
2. Objectives progress (what happened, what didn't, why)
3. Activities completed
4. Budget utilization status
5. Challenges faced
6. Lessons learnt
7. Key insights and impact

Provide accurate, concise analysis. Only include information that is present in the report data.
PROMPT;
    }

    /**
     * Get prompt for trend analysis across multiple reports
     *
     * @param array $reportsData
     * @return string
     */
    public static function getTrendAnalysisPrompt(array $reportsData): string
    {
        $jsonData = json_encode($reportsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Analyze the following collection of monthly reports and identify trends, patterns, and overall progress. Provide your response in JSON format with the following structure:

{
  "progress_trends": {
    "objectives": "trend description",
    "activities": "trend description",
    "budget": "trend description",
    "beneficiaries": "trend description"
  },
  "recurring_challenges": ["challenge1", "challenge2", ...],
  "improvement_patterns": ["pattern1", "pattern2", ...],
  "budget_spending_trends": "Description of spending patterns",
  "beneficiary_growth_trends": "Description of beneficiary changes",
  "overall_project_health": "good|moderate|needs_attention",
  "key_milestones": ["milestone1", "milestone2", ...],
  "comparative_insights": "How the project has evolved over time"
}

Reports Data:
{$jsonData}

Focus on:
1. Progress trends over time
2. Recurring challenges
3. Improvement patterns
4. Budget spending trends
5. Beneficiary growth trends
6. Overall project health
7. Key milestones achieved

Provide comprehensive trend analysis based on the data provided.
PROMPT;
    }

    /**
     * Get prompt for executive summary generation
     *
     * @param array $analysis
     * @return string
     */
    public static function getExecutiveSummaryPrompt(array $analysis): string
    {
        $jsonData = json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Generate a concise executive summary (2-3 paragraphs) based on the following report analysis. The summary should:

1. Highlight key achievements
2. Mention major challenges (if any)
3. Provide overall assessment
4. Be professional and informative
5. Be suitable for management review

Analysis Data:
{$jsonData}

Provide only the executive summary text, no additional formatting or labels.
PROMPT;
    }

    /**
     * Get prompt for key achievements identification
     *
     * @param array $analysis
     * @return string
     */
    public static function getKeyAchievementsPrompt(array $analysis): string
    {
        $jsonData = json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Identify the top 5-7 key achievements from the following report analysis. Provide your response in JSON format:

{
  "achievements": [
    {
      "title": "Achievement title",
      "description": "Brief description",
      "impact": "Impact description"
    },
    ...
  ]
}

Analysis Data:
{$jsonData}

Focus on significant accomplishments that demonstrate project progress and impact.
PROMPT;
    }

    /**
     * Get prompt for trends identification
     *
     * @param array $analysis
     * @return string
     */
    public static function getTrendsPrompt(array $analysis): string
    {
        $jsonData = json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Identify key trends from the following analysis. Provide your response in JSON format:

{
  "trends": [
    {
      "category": "objectives|budget|beneficiaries|activities",
      "trend": "increasing|decreasing|stable|fluctuating",
      "description": "Detailed trend description",
      "significance": "Why this trend matters"
    },
    ...
  ]
}

Analysis Data:
{$jsonData}

Identify meaningful patterns and trends in the data.
PROMPT;
    }

    /**
     * Get prompt for insights generation
     *
     * @param array $analysis
     * @return string
     */
    public static function getInsightsPrompt(array $analysis): string
    {
        $jsonData = json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Generate key insights from the following analysis. Provide your response in JSON format:

{
  "insights": [
    {
      "insight": "Insight description",
      "category": "performance|budget|impact|challenges",
      "implications": "What this means for the project"
    },
    ...
  ]
}

Analysis Data:
{$jsonData}

Provide actionable insights that help understand project performance and impact.
PROMPT;
    }

    /**
     * Get prompt for recommendations generation
     *
     * @param array $analysis
     * @return string
     */
    public static function getRecommendationsPrompt(array $analysis): string
    {
        $jsonData = json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Generate actionable recommendations based on the following analysis. Provide your response in JSON format:

{
  "recommendations": [
    {
      "recommendation": "Specific recommendation",
      "priority": "high|medium|low",
      "category": "objectives|budget|activities|process",
      "rationale": "Why this recommendation is important"
    },
    ...
  ]
}

Analysis Data:
{$jsonData}

Focus on practical, actionable recommendations that can improve project outcomes.
PROMPT;
    }
}
