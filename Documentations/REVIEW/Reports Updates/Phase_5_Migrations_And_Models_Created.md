# Phase 5: Migrations and Models Created

**Date:** January 2025  
**Status:** ✅ **COMPLETE**  
**Purpose:** Summary of created migration files and models

---

## ✅ Migrations Created

### 1. `2026_01_09_100000_create_ai_report_insights_table.php`
**Location:** `database/migrations/`

**Purpose:** Stores all AI-generated insights for aggregated reports (quarterly, half-yearly, annual).

**Key Features:**
- Single table for all AI insights
- JSON fields for structured data (achievements, trends, challenges, recommendations, etc.)
- Report-type-specific fields (nullable for flexibility)
- Edit tracking fields (`last_edited_at`, `is_edited`, `last_edited_by_user_id`)
- Unique constraint on `report_type` + `report_id`
- Proper indexes for performance

**Fields:**
- Core content: `executive_summary`, `key_achievements`, `progress_trends`, `challenges`, `recommendations`
- Half-yearly/annual: `strategic_insights`, `quarterly_comparison`
- Annual only: `impact_assessment`, `budget_performance`, `future_outlook`, `year_over_year_comparison`
- Metadata: `ai_model_used`, `ai_tokens_used`, `generated_at`
- Edit tracking: `last_edited_at`, `last_edited_by_user_id`, `is_edited`

---

### 2. `2026_01_09_100001_create_ai_report_titles_table.php`
**Location:** `database/migrations/`

**Purpose:** Stores AI-generated titles and section headings.

**Key Features:**
- Report title (string)
- Section headings (JSON key-value pairs)
- Edit tracking fields
- Unique constraint on `report_type` + `report_id`

**Fields:**
- `report_title` (string, nullable)
- `section_headings` (json, nullable)
- Metadata and edit tracking fields

---

### 3. `2026_01_09_100002_create_ai_report_validation_results_table.php`
**Location:** `database/migrations/`

**Purpose:** Stores AI validation results for all report types (including monthly).

**Key Features:**
- Supports monthly, quarterly, half-yearly, and annual reports
- Full validation results (JSON)
- Overall status (ok/warning/error)
- Data quality score (0-100)
- Count fields for quick filtering
- Unique constraint on `report_type` + `report_id`

**Fields:**
- `validation_results` (json)
- `overall_status` (enum: ok/warning/error)
- `data_quality_score` (integer, 0-100)
- `overall_assessment` (string: excellent/good/fair/poor)
- Count fields: `inconsistencies_count`, `missing_info_count`, `unusual_patterns_count`, `potential_errors_count`

---

## ✅ Models Created

### 1. `AIReportInsight.php`
**Location:** `app/Models/Reports/AI/`

**Features:**
- Polymorphic relationship helper (`report()`)
- Relationship to `User` for edit tracking (`lastEditedBy()`)
- Helper methods:
  - `markAsEdited($userId)` - Mark content as edited
  - `hasBeenEdited()` - Check if edited
  - Array accessors with fallbacks for JSON fields
- Proper casting for JSON fields and dates

**Relationships:**
- `report()` - Polymorphic to QuarterlyReport/HalfYearlyReport/AnnualReport
- `lastEditedBy()` - BelongsTo User

---

### 2. `AIReportTitle.php`
**Location:** `app/Models/Reports/AI/`

**Features:**
- Polymorphic relationship helper (`report()`)
- Relationship to `User` for edit tracking (`lastEditedBy()`)
- Helper methods:
  - `markAsEdited($userId)` - Mark title as edited
  - `hasBeenEdited()` - Check if edited
  - `getSectionHeading($key, $default)` - Get specific heading
  - `setSectionHeading($key, $value)` - Set specific heading
- Proper casting for JSON fields

**Relationships:**
- `report()` - Polymorphic to QuarterlyReport/HalfYearlyReport/AnnualReport
- `lastEditedBy()` - BelongsTo User

---

### 3. `AIReportValidationResult.php`
**Location:** `app/Models/Reports/AI/`

**Features:**
- Polymorphic relationship helper (`report()`) - supports all report types including monthly
- Helper methods:
  - `isOk()` - Check if status is OK
  - `hasWarnings()` - Check if has warnings
  - `hasErrors()` - Check if has errors
  - `getQualityColorClass()` - Get Bootstrap color class for quality score
  - `getStatusBadgeClass()` - Get Bootstrap badge class for status
- Proper casting for JSON and dates

**Relationships:**
- `report()` - Polymorphic to DPReport/QuarterlyReport/HalfYearlyReport/AnnualReport

---

## ✅ Models Updated

### 1. `QuarterlyReport.php`
**Location:** `app/Models/Reports/Quarterly/`

**Added Relationships:**
```php
public function aiInsights()
{
    return $this->hasOne(\App\Models\Reports\AI\AIReportInsight::class, 'report_id')
                ->where('report_type', 'quarterly');
}

public function aiTitle()
{
    return $this->hasOne(\App\Models\Reports\AI\AIReportTitle::class, 'report_id')
                ->where('report_type', 'quarterly');
}
```

---

### 2. `HalfYearlyReport.php`
**Location:** `app/Models/Reports/HalfYearly/`

**Added Relationships:**
```php
public function aiInsights()
{
    return $this->hasOne(\App\Models\Reports\AI\AIReportInsight::class, 'report_id')
                ->where('report_type', 'half_yearly');
}

public function aiTitle()
{
    return $this->hasOne(\App\Models\Reports\AI\AIReportTitle::class, 'report_id')
                ->where('report_type', 'half_yearly');
}
```

---

### 3. `AnnualReport.php`
**Location:** `app/Models/Reports/Annual/`

**Added Relationships:**
```php
public function aiInsights()
{
    return $this->hasOne(\App\Models\Reports\AI\AIReportInsight::class, 'report_id')
                ->where('report_type', 'annual');
}

public function aiTitle()
{
    return $this->hasOne(\App\Models\Reports\AI\AIReportTitle::class, 'report_id')
                ->where('report_type', 'annual');
}
```

---

### 4. `DPReport.php` (Monthly Report)
**Location:** `app/Models/Reports/Monthly/`

**Added Relationship:**
```php
public function aiValidation()
{
    return $this->hasOne(\App\Models\Reports\AI\AIReportValidationResult::class, 'report_id', 'report_id')
                ->where('report_type', 'monthly');
}
```

---

## Usage Examples

### Creating AI Insights
```php
use App\Models\Reports\AI\AIReportInsight;

$insight = AIReportInsight::create([
    'report_type' => 'quarterly',
    'report_id' => $quarterlyReport->report_id,
    'executive_summary' => 'AI-generated summary...',
    'key_achievements' => [
        ['title' => 'Achievement 1', 'description' => '...'],
    ],
    'ai_model_used' => 'gpt-4o-mini',
    'generated_at' => now(),
]);
```

### Accessing AI Insights
```php
// From report model
$quarterlyReport = QuarterlyReport::find($id);
$insights = $quarterlyReport->aiInsights;
$title = $quarterlyReport->aiTitle;

// Accessing data
$summary = $insights->executive_summary;
$achievements = $insights->key_achievements; // Returns array
```

### Editing AI Content
```php
$insights = $quarterlyReport->aiInsights;
$insights->executive_summary = 'Updated summary';
$insights->markAsEdited(); // Automatically sets is_edited, last_edited_at, last_edited_by_user_id
$insights->save();
```

### Editing JSON Fields
```php
$insights = $quarterlyReport->aiInsights;
$achievements = $insights->key_achievements;
$achievements[] = ['title' => 'New Achievement', 'description' => '...'];
$insights->key_achievements = $achievements;
$insights->markAsEdited();
$insights->save();
```

---

## Database Schema Summary

### Tables Created: **3 tables**

1. ✅ `ai_report_insights` - All AI insights (single table approach)
2. ✅ `ai_report_titles` - Titles and headings
3. ✅ `ai_report_validation_results` - Validation results

### Models Created: **3 models**

1. ✅ `AIReportInsight`
2. ✅ `AIReportTitle`
3. ✅ `AIReportValidationResult`

### Models Updated: **4 models**

1. ✅ `QuarterlyReport` - Added `aiInsights()` and `aiTitle()` relationships
2. ✅ `HalfYearlyReport` - Added `aiInsights()` and `aiTitle()` relationships
3. ✅ `AnnualReport` - Added `aiInsights()` and `aiTitle()` relationships
4. ✅ `DPReport` - Added `aiValidation()` relationship

---

## Next Steps

1. ✅ **Migrations Created** - Ready to run `php artisan migrate`
2. ✅ **Models Created** - Ready to use in controllers
3. 📋 **Update Services** - Update AI services to store data in database
4. 📋 **Create Controllers** - Create aggregated report controllers
5. 📋 **Create Views** - Create views with edit functionality

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Migrations and Models Complete - Ready for Service Updates
