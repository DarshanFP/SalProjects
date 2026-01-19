<?php

namespace App\Services\AI\Prompts;

class ReportComparisonPrompts
{
    /**
     * Get prompt for comparing quarterly reports
     *
     * @param array $report1Data
     * @param array $report2Data
     * @return string
     */
    public static function getQuarterlyComparisonPrompt(array $report1Data, array $report2Data): string
    {
        $json1 = json_encode($report1Data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $json2 = json_encode($report2Data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Compare the following two quarterly reports and provide a comprehensive comparison analysis. Provide your response in JSON format:

{
  "summary": "Overall comparison summary (2-3 paragraphs)",
  "improvements": [
    {
      "area": "objectives|budget|beneficiaries|activities",
      "description": "What improved",
      "magnitude": "significant|moderate|minor",
      "evidence": "Supporting data or evidence"
    }
  ],
  "declines": [
    {
      "area": "objectives|budget|beneficiaries|activities",
      "description": "What declined",
      "magnitude": "significant|moderate|minor",
      "evidence": "Supporting data or evidence"
    }
  ],
  "key_differences": [
    {
      "difference": "Description of difference",
      "impact": "Impact of this difference",
      "significance": "high|medium|low"
    }
  ],
  "trends": {
    "direction": "improving|declining|stable|mixed",
    "description": "Overall trend description"
  },
  "insights": [
    {
      "insight": "Key insight from comparison",
      "implications": "What this means"
    }
  ],
  "recommendations": [
    {
      "recommendation": "Actionable recommendation",
      "priority": "high|medium|low",
      "rationale": "Why this is important"
    }
  ]
}

Report 1 Data:
{$json1}

Report 2 Data:
{$json2}

Focus on identifying meaningful changes, improvements, and areas of concern. Provide actionable insights.
PROMPT;
    }

    /**
     * Get prompt for comparing half-yearly reports
     *
     * @param array $report1Data
     * @param array $report2Data
     * @return string
     */
    public static function getHalfYearlyComparisonPrompt(array $report1Data, array $report2Data): string
    {
        $json1 = json_encode($report1Data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $json2 = json_encode($report2Data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Compare the following two half-yearly reports and provide a comprehensive comparison analysis. Provide your response in JSON format:

{
  "summary": "Overall comparison summary (3-4 paragraphs)",
  "improvements": [
    {
      "area": "objectives|budget|beneficiaries|activities|strategy",
      "description": "What improved",
      "magnitude": "significant|moderate|minor",
      "evidence": "Supporting data"
    }
  ],
  "declines": [
    {
      "area": "objectives|budget|beneficiaries|activities|strategy",
      "description": "What declined",
      "magnitude": "significant|moderate|minor",
      "evidence": "Supporting data"
    }
  ],
  "strategic_changes": [
    {
      "change": "Strategic change description",
      "impact": "Impact assessment",
      "significance": "high|medium|low"
    }
  ],
  "quarterly_patterns": "Comparison of quarterly patterns between reports",
  "trends": {
    "direction": "improving|declining|stable|mixed",
    "description": "Overall trend description"
  },
  "insights": [
    {
      "insight": "Strategic insight",
      "implications": "What this means"
    }
  ],
  "recommendations": [
    {
      "recommendation": "Strategic recommendation",
      "priority": "high|medium|low",
      "rationale": "Why this is important"
    }
  ]
}

Report 1 Data:
{$json1}

Report 2 Data:
{$json2}

Focus on strategic changes, long-term trends, and significant improvements or declines.
PROMPT;
    }

    /**
     * Get prompt for year-over-year comparison
     *
     * @param array $year1Data
     * @param array $year2Data
     * @return string
     */
    public static function getYearOverYearComparisonPrompt(array $year1Data, array $year2Data): string
    {
        $json1 = json_encode($year1Data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $json2 = json_encode($year2Data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Compare the following two annual reports (Year-over-Year comparison) and provide a comprehensive analysis. Provide your response in JSON format:

{
  "summary": "Overall year-over-year comparison summary (4-5 paragraphs)",
  "growth_analysis": {
    "beneficiaries": {
      "growth_rate": "Growth rate description",
      "trend": "increasing|decreasing|stable",
      "significance": "high|medium|low"
    },
    "budget": {
      "growth_rate": "Budget growth description",
      "efficiency": "more_efficient|less_efficient|stable",
      "significance": "high|medium|low"
    },
    "impact": {
      "growth_rate": "Impact growth description",
      "trend": "improving|declining|stable",
      "significance": "high|medium|low"
    }
  },
  "improvements": [
    {
      "area": "objectives|budget|beneficiaries|activities|impact|sustainability",
      "description": "What improved year-over-year",
      "magnitude": "significant|moderate|minor",
      "evidence": "Supporting data"
    }
  ],
  "declines": [
    {
      "area": "objectives|budget|beneficiaries|activities|impact|sustainability",
      "description": "What declined year-over-year",
      "magnitude": "significant|moderate|minor",
      "evidence": "Supporting data"
    }
  ],
  "strategic_changes": [
    {
      "change": "Strategic change description",
      "impact": "Impact assessment",
      "significance": "high|medium|low"
    }
  ],
  "lessons_learnt": [
    {
      "lesson": "Key lesson from year-over-year comparison",
      "application": "How to apply this lesson"
    }
  ],
  "trends": {
    "direction": "improving|declining|stable|mixed",
    "description": "Overall trend description",
    "projection": "Future projection based on trends"
  },
  "insights": [
    {
      "insight": "Strategic insight from comparison",
      "implications": "What this means for the project"
    }
  ],
  "recommendations": [
    {
      "recommendation": "Strategic recommendation for next year",
      "priority": "high|medium|low",
      "rationale": "Why this is important",
      "expected_impact": "Expected impact if implemented"
    }
  ]
}

Year 1 Data:
{$json1}

Year 2 Data:
{$json2}

Focus on year-over-year growth, strategic changes, long-term trends, and comprehensive impact assessment.
PROMPT;
    }
}
