<?php

namespace App\Services\AI\Prompts;

class AggregatedReportPrompts
{
    /**
     * Get prompt for generating quarterly report summary
     *
     * @param array $analysisData
     * @param array $periodInfo
     * @return string
     */
    public static function getQuarterlyReportPrompt(array $analysisData, array $periodInfo): string
    {
        $jsonData = json_encode($analysisData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $quarter = $periodInfo['quarter'] ?? '';
        $year = $periodInfo['year'] ?? '';

        return <<<PROMPT
Based on the analysis of 3 monthly reports for {$quarter} {$year}, generate a quarterly report summary that includes:

1. Executive Summary (2-3 paragraphs)
   - Highlight key achievements over the quarter
   - Mention major challenges faced
   - Provide overall assessment of progress

2. Key Achievements (top 5-7)
   - Most significant accomplishments
   - Milestones reached
   - Impact on beneficiaries

3. Progress Trends (how things changed over 3 months)
   - Objectives progress trends
   - Budget utilization trends
   - Beneficiary changes
   - Activity completion trends

4. Challenges Faced
   - Major obstacles encountered
   - Reasons for delays or issues
   - Impact on project progress

5. Recommendations for Next Quarter
   - Actionable recommendations
   - Areas needing attention
   - Suggested improvements

Focus on: Quarterly milestones, 3-month progress, significant changes
Exclude: Day-to-day details, minor activities, redundant information

Analysis Data:
{$jsonData}

Provide your response in JSON format:
{
  "executive_summary": "2-3 paragraph summary",
  "key_achievements": [
    {
      "title": "Achievement title",
      "description": "Brief description",
      "impact": "Impact description"
    }
  ],
  "progress_trends": {
    "objectives": "Trend description",
    "budget": "Trend description",
    "beneficiaries": "Trend description",
    "activities": "Trend description"
  },
  "challenges": [
    {
      "challenge": "Challenge description",
      "impact": "Impact description"
    }
  ],
  "recommendations": [
    {
      "recommendation": "Recommendation text",
      "priority": "high|medium|low",
      "rationale": "Why this is important"
    }
  ]
}
PROMPT;
    }

    /**
     * Get prompt for generating half-yearly report summary
     *
     * @param array $analysisData
     * @param array $periodInfo
     * @return string
     */
    public static function getHalfYearlyReportPrompt(array $analysisData, array $periodInfo): string
    {
        $jsonData = json_encode($analysisData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $halfYear = $periodInfo['half_year'] ?? '';
        $year = $periodInfo['year'] ?? '';

        return <<<PROMPT
Based on the analysis of 6 monthly reports or 2 quarterly reports for Half-Year {$halfYear} {$year}, generate a half-yearly report summary that includes:

1. Executive Summary (3-4 paragraphs)
   - Major achievements over 6 months
   - Overall project health
   - Strategic progress assessment

2. Major Achievements (top 10)
   - Significant accomplishments
   - Key milestones reached
   - Impact on project goals

3. Progress Trends (6-month overview)
   - Long-term progress patterns
   - Budget spending trends
   - Beneficiary growth trends
   - Objectives completion trends

4. Quarterly Comparison (Q1 vs Q2 or Q3 vs Q4)
   - Compare quarters within half-year
   - Identify improvements or declines
   - Highlight significant changes

5. Strategic Insights
   - Strategic observations
   - Patterns identified
   - Long-term implications

6. Recommendations for Next Half-Year
   - Strategic recommendations
   - Areas for improvement
   - Action items

Focus on: Major milestones, strategic progress, significant impact, 6-month overview
Exclude: Monthly details, minor activities, redundant information

Analysis Data:
{$jsonData}

Provide your response in JSON format:
{
  "executive_summary": "3-4 paragraph summary",
  "major_achievements": [
    {
      "title": "Achievement title",
      "description": "Brief description",
      "impact": "Impact description",
      "quarter": "Q1|Q2|Q3|Q4"
    }
  ],
  "progress_trends": {
    "objectives": "Trend description",
    "budget": "Trend description",
    "beneficiaries": "Trend description",
    "overall": "Overall trend description"
  },
  "quarterly_comparison": {
    "comparison": "Comparison description",
    "improvements": ["improvement1", "improvement2"],
    "declines": ["decline1", "decline2"]
  },
  "strategic_insights": [
    {
      "insight": "Insight description",
      "implications": "What this means"
    }
  ],
  "recommendations": [
    {
      "recommendation": "Recommendation text",
      "priority": "high|medium|low",
      "category": "objectives|budget|process|strategy",
      "rationale": "Why this is important"
    }
  ]
}
PROMPT;
    }

    /**
     * Get prompt for generating annual report summary
     *
     * @param array $analysisData
     * @param array $periodInfo
     * @return string
     */
    public static function getAnnualReportPrompt(array $analysisData, array $periodInfo): string
    {
        $jsonData = json_encode($analysisData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $year = $periodInfo['year'] ?? '';

        return <<<PROMPT
Based on the analysis of 12 monthly reports or aggregated reports for Year {$year}, generate an annual report summary that includes:

1. Executive Summary (4-5 paragraphs)
   - Comprehensive year-end overview
   - Major achievements and milestones
   - Overall project impact
   - Strategic assessment

2. Year-End Achievements (comprehensive list)
   - All significant accomplishments
   - Key milestones reached
   - Impact on project goals
   - Beneficiary impact

3. Annual Trends Analysis
   - Year-long progress patterns
   - Budget performance over the year
   - Beneficiary growth trends
   - Objectives completion trends
   - Seasonal patterns (if any)

4. Impact Assessment
   - Overall project impact
   - Beneficiary outcomes
   - Community impact
   - Sustainability indicators

5. Budget Performance Review
   - Budget utilization analysis
   - Expense patterns
   - Variance analysis
   - Cost-effectiveness

6. Strategic Recommendations
   - Future recommendations for next year
   - Areas needing attention
   - Strategic improvements
   - Sustainability measures

7. Future Outlook
   - Projected outcomes
   - Expected challenges
   - Opportunities
   - Next steps

Focus: Full year impact, strategic outcomes, comprehensive analysis, year-end assessment
Exclude: Monthly details, redundant information, minor activities

Analysis Data:
{$jsonData}

Provide your response in JSON format:
{
  "executive_summary": "4-5 paragraph comprehensive summary",
  "year_end_achievements": [
    {
      "title": "Achievement title",
      "description": "Brief description",
      "impact": "Impact description",
      "quarter": "Q1|Q2|Q3|Q4|Full Year"
    }
  ],
  "annual_trends": {
    "objectives": "Year-long trend description",
    "budget": "Budget trend description",
    "beneficiaries": "Beneficiary trend description",
    "activities": "Activity trend description",
    "seasonal_patterns": "Any seasonal patterns identified"
  },
  "impact_assessment": {
    "project_impact": "Overall project impact",
    "beneficiary_outcomes": "Outcomes for beneficiaries",
    "community_impact": "Community-level impact",
    "sustainability": "Sustainability indicators"
  },
  "budget_performance": {
    "utilization": "Budget utilization analysis",
    "expense_patterns": "Expense pattern description",
    "variance": "Variance analysis",
    "cost_effectiveness": "Cost-effectiveness assessment"
  },
  "strategic_recommendations": [
    {
      "recommendation": "Recommendation text",
      "priority": "high|medium|low",
      "category": "objectives|budget|strategy|sustainability",
      "rationale": "Why this is important",
      "expected_impact": "Expected impact if implemented"
    }
  ],
  "future_outlook": {
    "projected_outcomes": "Projected outcomes",
    "expected_challenges": "Expected challenges",
    "opportunities": "Opportunities identified",
    "next_steps": "Recommended next steps"
  }
}
PROMPT;
    }

    /**
     * Get prompt for filtering information by report type
     *
     * @param array $data
     * @param string $reportType
     * @return string
     */
    public static function getInformationFilterPrompt(array $data, string $reportType): string
    {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $focusAreas = [
            'quarterly' => 'Focus on 3-month trends, key milestones, significant changes. Exclude day-to-day details.',
            'half_yearly' => 'Focus on 6-month progress, major achievements, strategic insights. Exclude monthly details.',
            'annual' => 'Focus on full year impact, strategic outcomes, comprehensive analysis. Exclude monthly/quarterly details.',
        ];

        $focus = $focusAreas[$reportType] ?? 'Focus on relevant information for this report type.';

        return <<<PROMPT
Filter the following report data to include only information relevant for a {$reportType} report.

{$focus}

Data to filter:
{$jsonData}

Provide filtered data in the same structure, removing:
- Redundant information
- Day-to-day details (for quarterly/half-yearly)
- Minor activities
- Information not relevant to {$reportType} level reporting

Keep:
- Key achievements
- Significant milestones
- Major trends
- Important challenges
- Strategic insights
- Budget overview
- Beneficiary impact

Return the filtered data in JSON format.
PROMPT;
    }
}
