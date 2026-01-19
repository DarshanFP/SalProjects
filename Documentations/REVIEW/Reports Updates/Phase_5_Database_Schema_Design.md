# Phase 5: Database Schema Design for AI Content Storage

**Date:** January 2025  
**Status:** 📋 **PLANNING**  
**Purpose:** Design database tables for storing AI-generated report content

---

## Overview

To enable easy retrieval, editing, and version tracking of AI-generated content, we need dedicated database tables. This document outlines the schema design for storing AI insights, titles, and validation results.

---

## Table 1: `ai_report_insights`

Stores all AI-generated insights for aggregated reports (quarterly, half-yearly, annual).

**Rationale:** Based on analysis of monthly report forms, AI generates NEW content that doesn't exist in aggregated tables. This content needs to be editable, so we store it separately.

### Schema

```php
Schema::create('ai_report_insights', function (Blueprint $table) {
    $table->id();
    
    // Report identification
    $table->enum('report_type', ['quarterly', 'half_yearly', 'annual']);
    $table->unsignedBigInteger('report_id')->comment('ID from respective report table');
    
    // Core AI Content (all report types)
    $table->text('executive_summary')->nullable()->comment('2-5 paragraph summary');
    $table->json('key_achievements')->nullable()->comment('Array of achievement objects');
    $table->json('progress_trends')->nullable()->comment('Trends data structure');
    $table->json('challenges')->nullable()->comment('Array of challenge objects');
    $table->json('recommendations')->nullable()->comment('Array of recommendation objects');
    
    // Half-Yearly & Annual Specific
    $table->json('strategic_insights')->nullable()->comment('Strategic insights array');
    $table->json('quarterly_comparison')->nullable()->comment('Q1 vs Q2 comparison (half-yearly only)');
    
    // Annual Specific Only
    $table->json('impact_assessment')->nullable()->comment('Impact assessment object');
    $table->json('budget_performance')->nullable()->comment('Budget performance analysis');
    $table->json('future_outlook')->nullable()->comment('Future outlook and projections');
    $table->json('year_over_year_comparison')->nullable()->comment('Year-over-year comparison');
    
    // AI Metadata
    $table->string('ai_model_used')->nullable()->comment('e.g., gpt-4o-mini');
    $table->integer('ai_tokens_used')->nullable();
    $table->timestamp('generated_at')->nullable();
    
    // Edit Tracking
    $table->timestamp('last_edited_at')->nullable()->comment('When user last edited AI content');
    $table->foreignId('last_edited_by_user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->boolean('is_edited')->default(false)->comment('Whether AI content has been manually edited');
    
    $table->timestamps();
    
    // Indexes
    $table->unique(['report_type', 'report_id'], 'unique_report_insight');
    $table->index('report_type');
    $table->index('report_id');
    $table->index('generated_at');
    $table->index('is_edited');
});
```

### JSON Structure Examples

**key_achievements:**
```json
[
  {
    "id": 1,
    "title": "Achievement title",
    "description": "Brief description",
    "impact": "Impact description",
    "quarter": "Q1",
    "month": "January 2025",
    "source_objective_id": "OBJ-001" // optional link to objective
  }
]
```

**progress_trends:**
```json
{
  "objectives": {
    "trend": "improving|declining|stable",
    "description": "Objectives showing steady progress",
    "data_points": [75, 80, 85],
    "analysis": "Detailed analysis text"
  },
  "budget": {
    "trend": "on_track|over_budget|under_budget",
    "description": "Budget utilization at 65%",
    "utilization_percentage": 65,
    "analysis": "Budget analysis text"
  },
  "beneficiaries": {
    "trend": "increasing|decreasing|stable",
    "description": "Beneficiary count increased by 15%",
    "growth_rate": 15,
    "analysis": "Beneficiary trend analysis"
  },
  "activities": {
    "trend": "improving|declining|stable",
    "description": "Activity completion rate analysis",
    "completion_rate": 85,
    "analysis": "Activity trend analysis"
  }
}
```

**challenges:**
```json
[
  {
    "id": 1,
    "challenge": "Budget constraints",
    "impact": "high|medium|low",
    "description": "Detailed challenge description",
    "evidence": "Supporting evidence",
    "recommendation": "How to address"
  }
]
```

**recommendations:**
```json
[
  {
    "id": 1,
    "recommendation": "Focus on completing pending objectives",
    "priority": "high|medium|low",
    "category": "objectives|budget|process|strategy",
    "rationale": "Why this is important",
    "expected_impact": "Expected impact if implemented",
    "action_items": ["Action 1", "Action 2"]
  }
]
```

**strategic_insights:**
```json
[
  {
    "id": 1,
    "insight": "Project is showing strong momentum",
    "category": "performance|budget|impact|sustainability",
    "implications": "What this means",
    "confidence": "high|medium|low"
  }
]
```

**impact_assessment:**
```json
{
  "project_impact": "Significant positive impact on community",
  "beneficiary_outcomes": "Improved quality of life for 150 beneficiaries",
  "community_impact": "Enhanced community engagement",
  "sustainability": "Project shows good sustainability indicators",
  "key_metrics": {
    "beneficiaries_reached": 150,
    "activities_completed": 45,
    "community_events": 12
  },
  "long_term_effects": "Expected long-term effects description"
}
```

**budget_performance:**
```json
{
  "utilization": "Budget utilization analysis text",
  "expense_patterns": "Expense pattern description",
  "variance": "Variance analysis",
  "cost_effectiveness": "Cost-effectiveness assessment",
  "efficiency_score": 85,
  "recommendations": ["Budget recommendation 1", "Budget recommendation 2"]
}
```

**future_outlook:**
```json
{
  "projected_outcomes": "Projected outcomes description",
  "expected_challenges": "Expected challenges description",
  "opportunities": "Opportunities identified",
  "next_steps": "Recommended next steps",
  "risk_assessment": "Risk assessment",
  "sustainability_plan": "Sustainability plan"
}
```

**quarterly_comparison:**
```json
{
  "comparison": "Overall comparison description",
  "improvements": ["Improvement 1", "Improvement 2"],
  "declines": ["Decline 1", "Decline 2"],
  "trends": "Trend analysis",
  "insights": ["Insight 1", "Insight 2"]
}
```

**year_over_year_comparison:**
```json
{
  "summary": "Year-over-year summary",
  "growth_analysis": {
    "beneficiaries": "Growth description",
    "budget": "Budget growth",
    "impact": "Impact growth"
  },
  "improvements": ["Improvement 1"],
  "declines": ["Decline 1"],
  "lessons_learnt": ["Lesson 1"],
  "recommendations": ["Recommendation 1"]
}
```

**progress_trends:**
```json
{
  "objectives": "Trend description",
  "budget": "Trend description",
  "beneficiaries": "Trend description",
  "activities": "Trend description"
}
```

**recommendations:**
```json
[
  {
    "recommendation": "Recommendation text",
    "priority": "high|medium|low",
    "category": "objectives|budget|process|strategy",
    "rationale": "Why this is important"
  }
]
```

---

## Table 2: `ai_report_titles`

Stores AI-generated titles and section headings for reports.

### Schema

```php
Schema::create('ai_report_titles', function (Blueprint $table) {
    $table->id();
    
    // Report identification
    $table->enum('report_type', ['quarterly', 'half_yearly', 'annual']);
    $table->unsignedBigInteger('report_id')->comment('ID from respective report table');
    
    // Titles
    $table->string('report_title')->nullable()->comment('AI-generated report title');
    
    // Section Headings (JSON object)
    $table->json('section_headings')->nullable()->comment('Key-value pairs of section headings');
    
    // AI Metadata
    $table->string('ai_model_used')->nullable();
    $table->integer('ai_tokens_used')->nullable();
    $table->timestamp('generated_at')->nullable();
    
    $table->timestamps();
    
    // Indexes
    $table->unique(['report_type', 'report_id'], 'unique_report_title');
    $table->index('report_type');
    $table->index('report_id');
});
```

### JSON Structure Example

**section_headings:**
```json
{
  "executive_summary": "Executive Summary",
  "key_achievements": "Key Achievements This Quarter",
  "progress_trends": "Progress Trends Analysis",
  "challenges": "Challenges Faced",
  "recommendations": "Recommendations for Next Quarter"
}
```

---

## Table 3: `ai_report_validation_results`

Stores AI validation results for reports.

### Schema

```php
Schema::create('ai_report_validation_results', function (Blueprint $table) {
    $table->id();
    
    // Report identification
    $table->enum('report_type', ['monthly', 'quarterly', 'half_yearly', 'annual']);
    $table->string('report_id')->comment('Report ID from respective table');
    
    // Validation Results (JSON)
    $table->json('validation_results')->comment('Full validation results structure');
    
    // Summary Fields
    $table->enum('overall_status', ['ok', 'warning', 'error'])->default('ok');
    $table->integer('data_quality_score')->nullable()->comment('0-100 score');
    $table->string('overall_assessment')->nullable()->comment('excellent|good|fair|poor');
    
    // Counts
    $table->integer('inconsistencies_count')->default(0);
    $table->integer('missing_info_count')->default(0);
    $table->integer('unusual_patterns_count')->default(0);
    $table->integer('potential_errors_count')->default(0);
    
    // AI Metadata
    $table->string('ai_model_used')->nullable();
    $table->integer('ai_tokens_used')->nullable();
    $table->timestamp('validated_at')->nullable();
    
    $table->timestamps();
    
    // Indexes
    $table->unique(['report_type', 'report_id'], 'unique_report_validation');
    $table->index('report_type');
    $table->index('report_id');
    $table->index('overall_status');
    $table->index('validated_at');
});
```

### JSON Structure Example

**validation_results:**
```json
{
  "inconsistencies": [
    {
      "type": "budget",
      "description": "Total expenses exceed budget",
      "severity": "high",
      "evidence": "Expenses: 120000, Budget: 100000",
      "recommendation": "Review expense entries"
    }
  ],
  "missing_information": [
    {
      "field": "lessons_learnt",
      "description": "No lessons learnt provided for objectives",
      "importance": "important",
      "recommendation": "Add lessons learnt"
    }
  ],
  "unusual_patterns": [
    {
      "pattern": "Sudden increase in beneficiaries",
      "significance": "medium",
      "explanation": "Beneficiaries increased by 50% this month",
      "recommendation": "Verify beneficiary data"
    }
  ],
  "potential_errors": [
    {
      "error": "Budget calculation mismatch",
      "type": "calculation",
      "severity": "high",
      "recommendation": "Recalculate budget totals"
    }
  ]
}
```

---

## Relationships

### With Report Tables

**Quarterly Reports:**
- `ai_report_insights.report_id` → `quarterly_reports.id`
- `ai_report_titles.report_id` → `quarterly_reports.id`
- `ai_report_validation_results.report_id` → `quarterly_reports.report_id` (string)

**Half-Yearly Reports:**
- `ai_report_insights.report_id` → `half_yearly_reports.id`
- `ai_report_titles.report_id` → `half_yearly_reports.id`
- `ai_report_validation_results.report_id` → `half_yearly_reports.report_id` (string)

**Annual Reports:**
- `ai_report_insights.report_id` → `annual_reports.id`
- `ai_report_titles.report_id` → `annual_reports.id`
- `ai_report_validation_results.report_id` → `annual_reports.report_id` (string)

**Monthly Reports:**
- `ai_report_validation_results.report_id` → `DP_Reports.report_id` (string)

---

## Model Relationships

### AIReportInsight Model

```php
// Polymorphic relationship helper
public function report()
{
    switch ($this->report_type) {
        case 'quarterly':
            return $this->belongsTo(QuarterlyReport::class, 'report_id');
        case 'half_yearly':
            return $this->belongsTo(HalfYearlyReport::class, 'report_id');
        case 'annual':
            return $this->belongsTo(AnnualReport::class, 'report_id');
        default:
            return null;
    }
}
```

### Report Models (Add relationships)

**QuarterlyReport:**
```php
public function aiInsights()
{
    return $this->hasOne(AIReportInsight::class, 'report_id')
                ->where('report_type', 'quarterly');
}

public function aiTitle()
{
    return $this->hasOne(AIReportTitle::class, 'report_id')
                ->where('report_type', 'quarterly');
}
```

---

## Benefits of This Design

1. **Easy Retrieval:** All AI content in one place, easy to query
2. **Editing:** Can update AI content without regenerating
3. **Performance:** Fast retrieval, no need to call API again
4. **Version History:** Timestamps track when content was generated/updated
5. **Cost Savings:** Store once, use many times
6. **Flexibility:** JSON fields allow flexible data structures
7. **Separation:** AI content separate from report data, cleaner architecture

---

## Migration File Names

1. `2026_01_XX_XXXXXX_create_ai_report_insights_table.php`
2. `2026_01_XX_XXXXXX_create_ai_report_titles_table.php`
3. `2026_01_XX_XXXXXX_create_ai_report_validation_results_table.php`

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Ready for Implementation
