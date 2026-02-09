# Frontend–Backend Contract Audit – Batch 9

## Purpose

This document continues the contract audit series, focusing on Projects\AttachmentController, Projects\BudgetController, Reports\Monthly\ReportAttachmentController, ReportMonitoringService, ProblemTreeImageService, and the ReportAttachment model. This batch examines file attachments, budget CRUD, report monitoring logic, and attachment ID generation.

---

## Projects\AttachmentController Analysis

### AttachmentController

**Location:** `App\Http\Controllers\Projects\AttachmentController`

**Size:** 354 lines

**Purpose:** Project attachment upload, download, update (add another file)

#### Service Design Analysis

**Strength 1: Validation and Config-Driven Rules**
```php
$validated = $request->validate([
    'file' => 'required|file|max:7168',
    'file_name' => 'nullable|string|max:255',
    'attachment_description' => 'nullable|string|max:1000',
]);
$allowedTypes = config('attachments.allowed_types.project_attachments');
```
✅ **Good:** Validation; config for allowed types

**Strength 2: Filename and Path Sanitization**
```php
$filename = $this->sanitizeFilename($providedFileName, $file->getClientOriginalExtension());
$projectType = $this->sanitizeProjectType($project->project_type);
```
✅ **Good:** Path traversal prevention

**Strength 3: Transaction and Rollback**
```php
DB::beginTransaction();
// ... store file, save attachment
DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    if (isset($path) && Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
    }
```
✅ **Good:** Atomicity and cleanup on failure

#### Identified Contract Violations

**Violation 172: store() Can Return null**
- **Pattern:**
  ```php
  if (!$request->hasFile('file')) {
      Log::info('AttachmentController@store - No file uploaded, skipping attachment');
      return null;
  }
  ```
  Caller may expect redirect or project; null can cause errors if not handled
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Contract** – Document or return redirect/consistent type

**Violation 173: update() Receives $project_id (String); store() Receives Project (Model Binding)**
- **Pattern:** `store(Request $request, Project $project)` vs `update(Request $request, $project_id)` then `Project::where('project_id', $project_id)->firstOrFail()`
- **Impact:** Inconsistent API; update uses string ID, store uses model
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Inconsistency** – Prefer same parameter style

**Violation 174: update() Semantics Are "Add" Not "Replace"**
- **Pattern:** Comment says "Store new file (add to existing attachments, don't replace)"; method name is `update`
- **Impact:** Callers may expect replace semantics
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Naming** – Method adds attachment; consider rename or document

**Violation 175: description From request->input() After validated Key**
- **Pattern:** `'description' => $request->input('attachment_description', '')` in store vs `$validated['attachment_description'] ?? ''` in update
- **Impact:** store uses raw input for description (validated only as nullable|string|max:1000); minor inconsistency
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Low** – Prefer validated key consistently

---

## Projects\BudgetController Analysis

### BudgetController

**Location:** `App\Http\Controllers\Projects\BudgetController`

**Size:** 158 lines

**Purpose:** Budget store/update with phase rows; addExpense (if present); guarded by BudgetSyncGuard

#### Service Design Analysis

**Strength 1: Budget Edit Guard**
```php
if (!BudgetSyncGuard::canEditBudget($project)) {
    BudgetAuditLogger::logBlockedEditAttempt(...);
    throw new HttpResponseException(redirect()->back()->with('error', self::BUDGET_LOCKED_MESSAGE));
}
```
✅ **Good:** Prevents edit when project approved; audit log

**Strength 2: Validation on Phase Budget Fields**
```php
$request->validate([
    'phases.*.budget.*.rate_quantity' => 'nullable|numeric|min:0',
    'phases.*.budget.*.rate_multiplier' => 'nullable|numeric|min:0',
    'phases.*.budget.*.rate_duration' => 'nullable|numeric|min:0',
    'phases.*.budget.*.this_phase' => 'nullable|numeric|min:0',
], [...]);
```
✅ **Good:** min:0 on numeric fields

#### Identified Contract Violations

**Violation 176: particular, rate_increase, next_phase Not Validated**
- **Pattern:**
  ```php
  $phases = $request->input('phases', []);
  foreach ($phases as $phaseIndex => $phase) {
      foreach ($phase['budget'] as $budget) {
          ProjectBudget::create([
              'particular' => $budget['particular'] ?? '',
              'rate_increase' => $budget['rate_increase'] ?? 0,
              'next_phase' => $budget['next_phase'] ?? 0,
              // ...
          ]);
      }
  }
  ```
  Validation only covers rate_quantity, rate_multiplier, rate_duration, this_phase
- **Impact:** particular unbounded length; rate_increase/next_phase can be negative or non-numeric
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validation Gap** – Add rules for particular (max length), rate_increase/next_phase (numeric|min:0)

**Violation 177: update() Delete-Then-Insert Pattern**
- **Pattern:** `ProjectBudget::where('project_id', $project->project_id)->delete();` then create all rows
- **Impact:** Same as other batches; no soft delete; IDs churn
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Consistent Pattern** – Cross-reference delete-then-insert violations

---

## Reports\Monthly\ReportAttachmentController Analysis

### ReportAttachmentController

**Location:** `App\Http\Controllers\Reports\Monthly\ReportAttachmentController`

**Size:** 358 lines

**Purpose:** Report attachment store, update (add), download, remove, test helpers

#### Service Design Analysis

**Strength 1: Validator and Config for Types**
```php
$validator = Validator::make($request->all(), [
    'file' => 'required|file|max:2048',
    'file_name' => 'required|string|max:255',
    'description' => 'nullable|string|max:1000'
]);
$allowedTypes = config('attachments.allowed_types.report_attachments');
```
✅ **Good:** Validation; config-driven MIME/extensions

**Strength 2: Transaction and Cleanup**
```php
DB::beginTransaction();
// ...
DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    if (isset($path) && Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
    }
```
✅ **Good:** Atomicity

#### Identified Contract Violations

**Violation 178: remove($id) Has No Authorization Check**
- **Pattern:** `remove($id)` does `ReportAttachment::findOrFail($id)` then `$attachment->delete()`. No check that the current user may delete this report's attachment
- **Impact:** Any user in the same role group (executor, applicant, provincial, coordinator, general) can delete any report attachment by guessing numeric `id` (IDOR)
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **SECURITY – IDOR** – Must verify user can edit the report that owns the attachment

**Violation 179: downloadAttachment($id) Has No Authorization Check**
- **Pattern:** `ReportAttachment::findOrFail($id)` then download. No check that the current user may view this report
- **Impact:** Any authenticated user in the route group can download any report attachment by id (IDOR)
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **SECURITY – IDOR** – Must verify user can view the report

**Violation 180: testFileStructure and testCreateAttachment Exposed**
- **Pattern:** Routes `test-structure/{report_id}` and `test-create-attachment/{report_id}` create files/return structure; likely for debugging
- **Impact:** If left in production, information disclosure and arbitrary file creation
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **SECURITY** – Remove from production or protect (e.g. env/role)

**Violation 181: report_month_year Used in date() Without Validation**
- **Pattern:** `$monthYear = date('m_Y', strtotime($report->report_month_year));` — if report_month_year is null/invalid, strtotime can return false
- **Impact:** date('m_Y', false) = 01_1970; wrong folder path
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Defensive** – Validate report_month_year before use

---

## ReportAttachment Model Analysis

### ReportAttachment Model

**Location:** `App\Models\Reports\Monthly\ReportAttachment`

**Size:** 78 lines

#### Identified Contract Violations

**Violation 182: Attachment ID Overflow at 99**
- **Pattern:**
  ```php
  $lastNumber = (int)substr($latestAttachment->attachment_id, -2);
  $newNumber = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);
  return $reportId . '.' . $newNumber;
  ```
  2-digit sequence per report → collision after 99 attachments per report
- **Impact:** Same pattern as Violation 89 (logical framework IDs)
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **ID Overflow** – Use at least 3–4 digits or auto-increment

---

## ReportMonitoringService Analysis

### ReportMonitoringService

**Location:** `App\Services\ReportMonitoringService`

**Size:** 746 lines

**Purpose:** Provincial monitoring: scheduled vs reported activities, budget overspend, utilisation, type-specific checks

#### Service Design Analysis

**Strength 1: Clear Method Contracts**
- getReportMonth, getActivitiesScheduledButNotReported, getActivitiesReportedButNotScheduled, getAdhocActivities, getMonitoringPerObjective, getBudgetOverspendRows, etc.
✅ **Good:** Documented return shapes and intent

**Strength 2: Defensive Null/Empty Handling**
```php
$reportMonth = $this->getReportMonth($report);
if ($reportMonth === null) {
    return [];
}
$project = $report->project;
if (! $project) {
    return [];
}
```
✅ **Good:** Early returns

#### Identified Contract Violations

**Violation 183: timeframes Cast to (int) Without Type Guarantee**
- **Pattern:** `(int) $tf->month`, `(int) $tf->is_active` — assumes numeric or numeric string
- **Impact:** If timeframes store non-numeric values, comparison may be wrong
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Assumption** – Document or validate timeframe shape

**Violation 184: N+1 in getActivitiesReportedButNotScheduled**
- **Pattern:** Inside loop: `ProjectTimeframe::where('activity_id', $pid)->where(...)->exists();` and `->pluck('month')` — two queries per activity
- **Impact:** Performance with many activities
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Performance** – Consider eager load or batch query

**Violation 185: accountDetails Row Fields Assumed (is_budget_row, amount_sanctioned, total_amount)**
- **Pattern:** getBudgetOverspendRows iterates report->accountDetails and uses is_budget_row, amount_sanctioned, total_amount, total_expenses
- **Impact:** If schema or report type omits these, undefined keys or wrong logic
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Contract** – Document expected columns; use null coalescing

---

## ProblemTreeImageService Analysis

### ProblemTreeImageService

**Location:** `App\Services\ProblemTreeImageService`

**Size:** 67 lines

**Purpose:** Resize/re-encode problem tree images; config-driven; fallback to original on error

#### Service Design Analysis

**Strength 1: Config-Driven**
```php
$cfg = config('attachments.problem_tree_optimization', []);
$this->maxDimension = $maxDimension ?? ($cfg['max_dimension'] ?? 1920);
$this->jpegQuality = $jpegQuality ?? ($cfg['jpeg_quality'] ?? 85);
$this->enabled = $enabled ?? ($cfg['enabled'] ?? true);
```
✅ **Good:** No magic numbers in code

**Strength 2: Fallback on Failure**
```php
} catch (\Throwable $e) {
    Log::warning('Problem Tree image optimization failed, will use original', [...]);
    return $this->fallbackToOriginal ? null : throw $e;
}
```
✅ **Good:** Caller can store original if null

#### Identified Contract Violations

**Violation 186: optimize() Return Type string|null; Caller Must Handle Both**
- **Pattern:** Returns JPEG binary string or null. If caller does not check null and stores return value as path, wrong data stored
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Contract** – Document that null means "use original file"

---

## Phase-wise Issue Summary (Batch 9)

### Phase 1 – Critical Data Integrity

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 178 | remove($id) no authorization | ReportAttachmentController | Critical |
| 179 | downloadAttachment($id) no authorization | ReportAttachmentController | Critical |
| 180 | Test endpoints exposed | ReportAttachmentController | Critical |
| 182 | Attachment ID overflow at 99 | ReportAttachment model | High |

### Phase 2 – Input Normalization Gaps

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 175 | description key inconsistency | AttachmentController | Low |
| 176 | particular, rate_increase, next_phase not validated | BudgetController | Medium |
| 181 | report_month_year used in date() unguarded | ReportAttachmentController | Low |
| 183 | timeframes cast without type guarantee | ReportMonitoringService | Low |

### Phase 3 – Flow & Lifecycle Assumptions

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 172 | store() can return null | AttachmentController | Low |
| 173 | store vs update parameter style | AttachmentController | Low |
| 174 | update() adds file, name suggests replace | AttachmentController | Low |
| 177 | update() delete-then-insert | BudgetController | Medium |
| 184 | N+1 in monitoring service | ReportMonitoringService | Medium |
| 185 | accountDetails fields assumed | ReportMonitoringService | Low |
| 186 | optimize() return contract | ProblemTreeImageService | Low |

---

## Strengths Identified (Batch 9)

### Excellent Implementations

**1. AttachmentController Validation and Sanitization**
- Config-driven allowed types; sanitizeFilename and sanitizeProjectType; transaction + rollback and file cleanup

**2. BudgetController BudgetSyncGuard**
- canEditBudget check; audit log on blocked attempt; clear user message

**3. ReportAttachmentController Validation and Transaction**
- Validator::make; config for report_attachments; DB transaction and file cleanup on failure

**4. ProblemTreeImageService**
- Config-driven; fallback to original on error; Log::warning

**5. ReportMonitoringService**
- Clear method contracts; defensive null/empty handling; documented return shapes

---

## Summary Statistics (Batch 9)

| Category | Count |
|----------|-------|
| Controllers analyzed | 3 (Attachment, Budget, ReportAttachment) |
| Services analyzed | 2 (ReportMonitoringService, ProblemTreeImageService) |
| Models analyzed | 1 (ReportAttachment) |
| New violations identified | 15 (172-186) |
| Phase 1 violations | 4 |
| Phase 2 violations | 4 |
| Phase 3 violations | 7 |
| Critical issues | 4 |

---

## Cumulative Statistics (All Batches)

| Batch | Controllers | Services | Models | Violations | Critical |
|-------|-------------|----------|--------|------------|----------|
| Primary | 5 | 0 | 7 | 13 | 4 |
| Extended | 8 | 2 | 6 | 22 | 0 |
| Batch 2 | 5 | 1 | 6 | 20 | 3 |
| Batch 3 | 4 | 0 | 4 | 24 | 4 |
| Batch 4 | 2 | 2 | 4 | 18 | 2 |
| Batch 5 | 2 | 2 | 0 | 19 | 2 |
| Batch 6 | 2 | 1 | 2 | 19 | 4 |
| Batch 7 | 4 | 2 | 1 | 19 | 3 |
| Batch 8 | 6 | 0 | 4 | 18 | 2 |
| Batch 9 | 3 | 2 | 1 | 15 | 4 |
| **Total** | **41** | **12** | **35** | **187** | **28** |

---

## Key Takeaways (Batch 9)

### Critical Issues to Address

1. **ReportAttachmentController remove($id)** — No check that user may delete this report's attachment → IDOR.
2. **ReportAttachmentController downloadAttachment($id)** — No check that user may view this report → IDOR.
3. **Test routes testFileStructure / testCreateAttachment** — Exposed; info disclosure and test file creation.
4. **ReportAttachment ID generation** — 2-digit suffix → overflow at 99 attachments per report.

### Positive Patterns

- Attachment and report attachment controllers: validation, config-driven types, filename/path sanitization, transactions, cleanup.
- BudgetController: BudgetSyncGuard and audit logging.
- ProblemTreeImageService: config-driven, safe fallback.
- ReportMonitoringService: clear contracts and defensive null handling.

---

## DO NOT

This document is for diagnosis only:
- ❌ Do not implement fixes
- ❌ Do not add validation rules
- ❌ Do not refactor controllers
- ❌ Do not modify models
- ❌ Do not propose solutions

This audit serves as the foundation for phase-wise remediation planning.

---

*Document generated: January 31, 2026*  
*Batch 9 contract audit performed by: Senior Laravel Architect*
