# Phase 5: Complete Report Infrastructure - Updated Requirements

**Date:** January 2025  
**Status:** 📋 **PLANNING**  
**Priority:** 🔴 **HIGH**  
**Duration:** 25 hours (updated from 20 hours)

---

## Executive Summary

Phase 5 has been expanded to include comprehensive report infrastructure with AI integration, user access controls, database storage for AI content, and complete UI implementation with edit capabilities.

---

## New Requirements Identified

### 1. User Access Requirements
- **Executor** and **Applicant** users must be able to generate:
  - Quarterly reports based on available monthly reports
  - Half-yearly reports based on available monthly/quarterly reports
  - Annual reports based on available monthly/quarterly/half-yearly reports
- Reports should be generated from approved monthly reports only
- Users should see which monthly reports are available for aggregation

### 2. Database Storage for AI Content
- AI-generated content must be stored in database tables for easy retrieval
- Need separate tables for different AI-generated sections:
  - Executive summaries
  - Key achievements
  - Progress trends
  - Challenges
  - Recommendations
  - Strategic insights (for half-yearly/annual)
  - Impact assessments (for annual)
- This allows for:
  - Easy editing of AI-generated content
  - Version history
  - Quick retrieval without re-generating
  - Better performance

### 3. Edit Functionality
- All report types (quarterly, half-yearly, annual) need edit capabilities
- Edit partials needed for:
  - Executive summary editing
  - Achievements editing
  - Trends editing
  - Challenges editing
  - Recommendations editing
  - Other AI-generated sections
- Edit should only be available for draft/reverted reports
- Changes should be tracked

### 4. Views Structure
- Create views with partials for:
  - Create form
  - Show/display view
  - Edit form (with partials for each section)
- Partials should be reusable across report types where applicable

---

## Database Schema Requirements

### New Tables Needed for AI Content Storage

**✅ FINAL DESIGN:** Based on analysis of existing report forms, we need **3 tables**:

#### 1. `ai_report_insights` Table ⭐ RECOMMENDED: Single Table Approach
Stores ALL AI-generated insights for all report types in one table.

**Rationale:** After analyzing monthly report forms, AI generates NEW content that doesn't exist in aggregated tables. This content needs to be editable, so we store it separately in a single table for simplicity and efficiency.

**Fields:**
- `id` (primary key)
- `report_type` (enum: 'quarterly', 'half_yearly', 'annual')
- `report_id` (unsignedBigInteger - ID from respective report table)
- `executive_summary` (text, nullable) - 2-5 paragraph summary
- `key_achievements` (json, nullable) - Array of achievement objects
- `progress_trends` (json, nullable) - Trends analysis object
- `challenges` (json, nullable) - Array of challenge objects
- `recommendations` (json, nullable) - Array of recommendation objects
- `strategic_insights` (json, nullable) - For half-yearly/annual
- `quarterly_comparison` (json, nullable) - For half-yearly only
- `impact_assessment` (json, nullable) - For annual only
- `budget_performance` (json, nullable) - For annual only
- `future_outlook` (json, nullable) - For annual only
- `year_over_year_comparison` (json, nullable) - For annual only
- `ai_model_used` (string, nullable)
- `ai_tokens_used` (integer, nullable)
- `generated_at` (timestamp, nullable)
- `last_edited_at` (timestamp, nullable) - When user last edited
- `last_edited_by_user_id` (foreignId, nullable) - Who edited
- `is_edited` (boolean, default false) - Whether content was edited
- `created_at`, `updated_at` (timestamps)

**Indexes:**
- Unique: `['report_type', 'report_id']`
- Index: `report_type`, `report_id`, `generated_at`, `is_edited`

**Benefits:**
- Single table for all AI insights
- Easy to query and edit
- Efficient storage
- Supports version tracking

#### 2. `ai_report_titles` Table
Stores AI-generated titles and section headings.

**Fields:**
- `id` (primary key)
- `report_type` (enum: 'quarterly', 'half_yearly', 'annual')
- `report_id` (unsignedBigInteger)
- `report_title` (string, nullable)
- `section_headings` (json, nullable) - Key-value pairs
- `ai_model_used` (string, nullable)
- `ai_tokens_used` (integer, nullable)
- `generated_at` (timestamp, nullable)
- `last_edited_at` (timestamp, nullable)
- `last_edited_by_user_id` (foreignId, nullable)
- `is_edited` (boolean, default false)
- `created_at`, `updated_at` (timestamps)

**Indexes:**
- Unique: `['report_type', 'report_id']`

#### 3. `ai_report_validation_results` Table
Stores AI validation results for all report types.

**Fields:**
- `id` (primary key)
- `report_type` (enum: 'monthly', 'quarterly', 'half_yearly', 'annual')
- `report_id` (string) - Report ID string from respective table
- `validation_results` (json) - Full validation structure
- `overall_status` (enum: 'ok', 'warning', 'error')
- `data_quality_score` (integer, nullable) - 0-100
- `overall_assessment` (string, nullable) - excellent|good|fair|poor
- `inconsistencies_count` (integer, default 0)
- `missing_info_count` (integer, default 0)
- `unusual_patterns_count` (integer, default 0)
- `potential_errors_count` (integer, default 0)
- `ai_model_used` (string, nullable)
- `ai_tokens_used` (integer, nullable)
- `validated_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)

**Indexes:**
- Unique: `['report_type', 'report_id']`
- Index: `report_type`, `report_id`, `overall_status`, `validated_at`

---

**Note:** See `Phase_5_Final_Database_Design.md` for complete migration files and model code.

---

## Updated Task Breakdown

### Task 5.1: Database Migrations for AI Content (2 hours)
**Priority:** 🔴 **HIGH**

**Migrations to Create:**
1. `create_ai_report_insights_table.php` - Single table for all AI insights
2. `create_ai_report_titles_table.php` - Titles and headings
3. `create_ai_report_validation_results_table.php` - Validation results

**Design Decision:** ✅ **Single Table Approach** for AI insights
- After analyzing existing report forms, determined that AI generates NEW content
- Single table (`ai_report_insights`) stores all AI-generated insights
- Easier to query, edit, and maintain
- Aligns with existing aggregated report structure

**Deliverables:**
- ✅ All migration files created
- ✅ Proper indexes and foreign keys
- ✅ JSON fields for structured data
- ✅ Edit tracking fields (`last_edited_at`, `is_edited`, `last_edited_by_user_id`)
- ✅ Complete migration code (see `Phase_5_Final_Database_Design.md`)

---

### Task 5.2: Models for AI Content (1 hour)
**Priority:** 🔴 **HIGH**

**Models to Create:**
1. `app/Models/Reports/AI/AIReportInsight.php`
2. `app/Models/Reports/AI/AIReportTitle.php`
3. `app/Models/Reports/AI/AIReportValidationResult.php`

**Deliverables:**
- ✅ All model files
- ✅ Relationships defined
- ✅ Accessors/mutators for JSON fields

---

### Task 5.3: Complete Aggregated Report Controllers (8 hours)
**Priority:** 🔴 **HIGH**

**Files to Create:**
1. `app/Http/Controllers/Reports/Quarterly/AggregatedQuarterlyReportController.php`
2. `app/Http/Controllers/Reports/HalfYearly/HalfYearlyReportController.php`
3. `app/Http/Controllers/Reports/Annual/AnnualReportController.php`

**Methods Required:**

**For All Controllers:**
- `index()` - List all reports (with filters)
- `create()` - Show generation form with:
  - Period selection (quarter/year, half-year/year, year)
  - Available monthly reports preview
  - AI generation toggle
  - Source report selection
- `store()` / `generate()` - Generate report:
  - With/without AI option
  - Validate available monthly reports
  - Generate using service classes
  - Store AI insights if generated
  - Store AI titles if generated
- `show()` - Display report:
  - Show all sections including AI-generated content
  - Display source reports
  - Show edit button if draft
- `edit()` - Edit report (if draft/reverted):
  - Show edit form with partials
  - Allow editing of AI-generated content
  - Allow editing of aggregated data
- `update()` - Update report:
  - Update report data
  - Update AI insights if edited
  - Track changes
- `downloadPdf()` - Export as PDF
- `downloadDoc()` - Export as Word
- `destroy()` - Delete report (if draft)

**Authorization:**
- **Generate:** Executor, Applicant, Coordinator, Provincial
- **View:** All roles (based on project access)
- **Edit:** Only if draft/reverted, by creator or Coordinator/Provincial
- **Approve:** Coordinator
- **Delete:** Only if draft, by creator or Coordinator

**AI Integration:**
- Option to generate with AI analysis
- Option to generate without AI (traditional aggregation)
- Preview AI insights before saving
- Store AI content in database tables
- Allow editing of AI-generated content

**Deliverables:**
- ✅ All controller files
- ✅ Complete CRUD operations
- ✅ AI integration
- ✅ Authorization logic
- ✅ Export functionality

---

### Task 5.4: Create Aggregated Report Views (10 hours)
**Priority:** 🔴 **HIGH**

**View Structure:**

#### Quarterly Reports:
```
resources/views/reports/quarterly/
├── index.blade.php (list all quarterly reports)
├── create.blade.php (generation form)
├── show.blade.php (display report)
├── edit.blade.php (edit form)
└── partials/
    ├── _form.blade.php (common form elements)
    ├── _executive_summary.blade.php (executive summary display/edit)
    ├── _key_achievements.blade.php (achievements display/edit)
    ├── _progress_trends.blade.php (trends display/edit)
    ├── _challenges.blade.php (challenges display/edit)
    ├── _recommendations.blade.php (recommendations display/edit)
    ├── _objectives.blade.php (objectives display/edit)
    ├── _budget.blade.php (budget display/edit)
    ├── _photos.blade.php (photos display/edit)
    └── _source_reports.blade.php (source monthly reports info)
```

#### Half-Yearly Reports:
Similar structure with additional partials:
- `_quarterly_comparison.blade.php`
- `_strategic_insights.blade.php`

#### Annual Reports:
Similar structure with additional partials:
- `_impact_assessment.blade.php`
- `_budget_performance.blade.php`
- `_future_outlook.blade.php`
- `_year_over_year_comparison.blade.php`

**Features:**
- Generation form with period selection
- Available monthly reports preview/selection
- AI generation toggle with preview
- Source report information display
- AI-enhanced sections display
- Edit forms with partials for each section
- Export buttons (PDF/Word)
- Responsive design
- Status badges
- Action buttons based on user role and report status

**Deliverables:**
- ✅ All view files for quarterly reports
- ✅ All view files for half-yearly reports
- ✅ All view files for annual reports
- ✅ All partial files
- ✅ AI-enhanced sections
- ✅ Edit functionality
- ✅ Responsive design

---

### Task 5.5: Update Service Classes to Store AI Content (2 hours)
**Priority:** 🔴 **HIGH**

**Files to Update:**
1. `app/Services/Reports/QuarterlyReportService.php`
2. `app/Services/Reports/HalfYearlyReportService.php`
3. `app/Services/Reports/AnnualReportService.php`

**Changes:**
- After generating AI insights, store in `ai_report_insights` table
- After generating titles, store in `ai_report_titles` table
- Link AI content to reports via foreign keys
- Update `generateAIInsights()` methods to save to database

**Deliverables:**
- ✅ Service classes updated
- ✅ AI content stored in database
- ✅ Proper relationships established

---

### Task 5.6: Implement PDF/Word Export (3 hours)
**Priority:** 🟡 **MEDIUM**

**Files to Create:**
1. `app/Http/Controllers/Reports/Export/AggregatedReportExportController.php`
2. Or extend existing export controllers

**Features:**
- PDF export using mPDF (already in project)
- Word export using PhpOffice/PhpWord (already in project)
- Include all sections including AI-generated content
- Proper formatting and styling
- Charts/graphs for trends (annual reports)
- Cover page with report title
- Table of contents

**Deliverables:**
- ✅ PDF export functionality
- ✅ Word export functionality
- ✅ AI sections included
- ✅ Proper formatting

---

### Task 5.7: Implement Missing Quarterly Reports (3 hours)
**Priority:** 🟡 **MEDIUM**

**Issue:** Individual project types (ILP, IAH, IES, IIES) don't have quarterly reporting

**Tasks:**
1. Verify if quarterly reporting is needed for individual projects
2. If needed, create quarterly controllers for each type
3. Create quarterly views (can reuse partials)
4. Add routes
5. Test functionality

**Note:** This may not be needed if individual projects use the same aggregated report structure.

**Deliverables:**
- ✅ Quarterly reporting for individual types (if needed)
- ✅ Routes added
- ✅ Views created

---

### Task 5.8: Report Comparison Features (3 hours)
**Priority:** 🟡 **MEDIUM**

**Files to Create:**
1. `app/Http/Controllers/Reports/Comparison/ReportComparisonController.php`
2. `resources/views/reports/comparison/` (views)

**Features:**
1. Compare Q1 2025 vs Q1 2024
2. Compare H1 vs H2
3. Year-over-year comparison
4. Visual charts/graphs
5. AI-powered comparison insights (already implemented in Phase 4)
6. Side-by-side comparison view
7. Export comparison as PDF/Word

**Deliverables:**
- ✅ Comparison controller
- ✅ Comparison views
- ✅ Visual comparison tools
- ✅ AI comparison insights integration

---

### Task 5.9: Routes and Authorization (1 hour)
**Priority:** 🔴 **HIGH**

**Routes to Add:**

```php
// Quarterly Reports
Route::middleware(['auth', 'role:executor,applicant,coordinator,provincial'])->group(function () {
    Route::resource('reports/quarterly', AggregatedQuarterlyReportController::class);
    Route::post('reports/quarterly/{report}/generate', [AggregatedQuarterlyReportController::class, 'generate']);
    Route::get('reports/quarterly/{report}/download-pdf', [AggregatedQuarterlyReportController::class, 'downloadPdf']);
    Route::get('reports/quarterly/{report}/download-doc', [AggregatedQuarterlyReportController::class, 'downloadDoc']);
});

// Half-Yearly Reports
Route::middleware(['auth', 'role:executor,applicant,coordinator,provincial'])->group(function () {
    Route::resource('reports/half-yearly', HalfYearlyReportController::class);
    // Similar routes...
});

// Annual Reports
Route::middleware(['auth', 'role:executor,applicant,coordinator,provincial'])->group(function () {
    Route::resource('reports/annual', AnnualReportController::class);
    // Similar routes...
});

// Report Comparison
Route::middleware(['auth'])->group(function () {
    Route::get('reports/compare', [ReportComparisonController::class, 'index']);
    Route::post('reports/compare', [ReportComparisonController::class, 'compare']);
});
```

**Deliverables:**
- ✅ All routes added
- ✅ Proper middleware
- ✅ Authorization checks

---

## Updated Timeline

| Task | Duration | Priority | Dependencies |
|------|----------|----------|--------------|
| 5.1: Database Migrations | 2 hours | HIGH | None |
| 5.2: Models for AI Content | 1 hour | HIGH | 5.1 |
| 5.3: Controllers | 8 hours | HIGH | 5.2, Phase 3 |
| 5.4: Views | 10 hours | HIGH | 5.3 |
| 5.5: Update Services | 2 hours | HIGH | 5.2 |
| 5.6: PDF/Word Export | 3 hours | MEDIUM | 5.3, 5.4 |
| 5.7: Missing Quarterly | 3 hours | MEDIUM | 5.3, 5.4 |
| 5.8: Comparison Features | 3 hours | MEDIUM | 5.3, 5.4, Phase 4 |
| 5.9: Routes & Auth | 1 hour | HIGH | 5.3 |
| **Total** | **33 hours** | | |

---

## Key Considerations

### 1. User Access
- Executor and Applicant users need clear UI to:
  - See available monthly reports for their projects
  - Select period for aggregation
  - Choose to use AI or not
  - Preview before generating
  - Edit generated reports

### 2. AI Content Storage
- Storing AI content in database allows:
  - Fast retrieval without re-generation
  - Easy editing
  - Version history (via timestamps)
  - Better performance
  - Cost savings (no need to regenerate)

### 3. Edit Functionality
- Edit partials allow:
  - Modular editing
  - Reusability across report types
  - Easy maintenance
  - Better UX

### 4. Performance
- Database storage of AI content improves performance
- Can cache AI insights
- Faster report display

---

## Success Criteria

### Controllers
- ✅ All CRUD operations working
- ✅ AI integration working
- ✅ Authorization working correctly
- ✅ Export functionality working

### Views
- ✅ All views created with partials
- ✅ Edit functionality working
- ✅ AI content displayed correctly
- ✅ Responsive design

### Database
- ✅ AI content stored correctly
- ✅ Relationships working
- ✅ Easy retrieval

### User Access
- ✅ Executor/Applicant can generate reports
- ✅ Clear UI for report generation
- ✅ Available reports shown clearly

---

**Document Version:** 2.0  
**Last Updated:** January 2025  
**Status:** Ready for Implementation
