# Frontend–Backend Contract Audit – Batch 10

## Purpose

This document continues the contract audit series, focusing on ExportReportController (Monthly), LivelihoodAnnexureController, BudgetController, DevelopmentProjectController (Quarterly), and ConfirmablePasswordController. This batch examines monthly report PDF/DOC export contracts, livelihood annexure handling, budget store/update contracts, quarterly development report CRUD contracts, and password confirmation flow.

---

## ExportReportController (Monthly) Analysis

### ExportReportController

**Location:** `App\Http\Controllers\Reports\Monthly\ExportReportController`

**Purpose:** Export monthly reports as PDF or Word; delegates to type-specific controllers for annexures/profiles.

#### Controller Design Analysis

**Strength 1: Role-Based Access**
- downloadPdf and downloadDoc both implement switch on user role (executor/applicant, provincial, coordinator/general) and check report ownership or hierarchy before serving.

**Strength 2: Error Handling**
- try/catch with Log::error and rethrow; downloadPdf has fallback (retry without photos) on view render failure.

#### Identified Contract Violations

**Violation 178: Route Parameter report_id Not Validated**
- **Pattern:** `downloadPdf($report_id)` and `downloadDoc($report_id)` accept `$report_id` from the route. No validation that `report_id` exists in `dp_reports` or matches expected format. `firstOrFail()` returns 404 on missing, but type/format is not enforced at entry.
- **Impact:** Non-existent id yields 404; invalid format could cause unexpected behavior before query. Explicit contract improves clarity and security.
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validate** – Validate report_id (e.g. `exists:dp_reports,report_id`) or use route model binding.

**Violation 179: Duplicate Permission Logic in downloadPdf and downloadDoc**
- **Pattern:** Identical switch ($user->role) and hasAccess logic (executor/applicant → report->user_id; provincial → report->user->parent_id; coordinator/general → true) appears in both downloadPdf and downloadDoc. Same pattern as AggregatedReportExportController (Violation 171).
- **Impact:** If permission rules change, both methods must be updated; risk of drift.
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **DRY** – Extract to Policy or shared method (e.g. `canDownloadMonthlyReport($user, $report)`).

**Violation 180: Possible Null Reference for Provincial Access**
- **Pattern:** Provincial branch uses `$report->user->parent_id === $user->id`. If `$report->user` is null (e.g. orphan report, deleted user), accessing `parent_id` can throw.
- **Impact:** 500 error for provincial user when report has no user or user relation not loaded.
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Defensive** – Use optional chaining or null check: `$report->user?->parent_id === $user->id` and treat null as no access.

**Violation 181: Admin Role Not Handled in Permission Switch**
- **Pattern:** Switch covers executor, applicant, provincial, coordinator, general. Role `admin` is not listed; default leaves `$hasAccess = false`, so admin gets 403 when attempting to download monthly report PDF/DOC.
- **Frontend contract:** Admin may expect to view/download all reports (e.g. from admin report list).
- **Impact:** Admin cannot download monthly reports via this controller; may be intentional (admin uses different flow) or oversight.
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Clarify** – If admin should download monthly reports, add case; otherwise document that admin uses a different entry point.

**Violation 182: Controller-to-Controller Dependency (Four Controllers Injected)**
- **Pattern:** Constructor injects LivelihoodAnnexureController, InstitutionalOngoingGroupController, ResidentialSkillTrainingController, CrisisInterventionCenterController and calls their getAnnexures/getAgeProfiles/getTraineeProfiles/getInmateProfiles methods. Same architectural pattern as ExportController (Violation 85) and AggregatedReportExportController.
- **Impact:** Tight coupling; export logic depends on four other controllers; harder to test and maintain.
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Architecture** – Prefer services or repositories for annexure/profile data; controllers should not depend on other controllers for data.

**Violation 183: ini_set('memory_limit') in downloadPdf**
- **Pattern:** `ini_set('memory_limit', '512M');` at the start of downloadPdf. Same as AggregatedReportExportController (Violation 172).
- **Impact:** Masks memory issues; environment-dependent behavior.
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **Operational** – Prefer config or queue for heavy exports; document when acceptable.

---

## LivelihoodAnnexureController Analysis

### LivelihoodAnnexureController

**Location:** `App\Http\Controllers\Reports\Monthly\LivelihoodAnnexureController`

**Purpose:** Handle livelihood annexure data (handleLivelihoodAnnexure); retrieve annexures for export (getAnnexures). Routes reference show, edit, update.

#### Controller Design Analysis

**Strength 1: Validation of Annexure Fields**
- handleLivelihoodAnnexure validates array fields (dla_beneficiary_name, dla_support_date, etc.) with types and max lengths for strings.

**Strength 2: Uses Validated Data for Persistence**
- Loop uses $validatedData; updateOrCreate uses validated values.

#### Identified Contract Violations

**Violation 184: Route Parameter report_id Not Validated**
- **Pattern:** `handleLivelihoodAnnexure(Request $request, $report_id)` and `getAnnexures($report_id)` accept `$report_id` from route or caller. No validation that report_id exists in dp_reports or that the current user may access that report. When called from ExportReportController, access was already checked; when called via route (e.g. update), report_id is not validated.
- **Impact:** Invalid or unauthorized report_id could write/read annexure data for wrong report.
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validate** – Validate report_id (exists, format) and enforce authorization (user may edit/view this report).

**Violation 185: Annexure Array Size Not Limited**
- **Pattern:** Validation allows unbounded arrays: `'dla_beneficiary_name' => 'nullable|array'`, `'dla_beneficiary_name.*' => 'nullable|string|max:255'`. No `max:` on array size. Frontend could send hundreds or thousands of rows.
- **Impact:** Memory and performance degradation; DoS potential; unbounded DB writes.
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Scalability** – Add array size limit (e.g. `dla_beneficiary_name => 'nullable|array|max:200'` or similar).

**Violation 186: Routes Reference show, edit, update Not Implemented**
- **Pattern:** Routes register `show`, `edit`, `update` for LivelihoodAnnexureController (`livelihood.annexure.show`, `livelihood.annexure.edit`, `livelihood.annexure.update`). Controller only defines `handleLivelihoodAnnexure` and `getAnnexures`. No `show`, `edit`, or `update` methods.
- **Impact:** Requests to those routes result in 404 or method-not-found. Frontend contract (links to edit/update) is broken.
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Contract** – Implement show, edit, update or remove/redirect routes; align routes with controller API.

---

## BudgetController Analysis

### BudgetController

**Location:** `App\Http\Controllers\Projects\BudgetController`

**Purpose:** Store and update project budget (phases and budget rows). Routes also reference viewBudget and addExpense.

#### Controller Design Analysis

**Strength 1: Budget Edit Guard**
- store and update check BudgetSyncGuard::canEditBudget($project) and block edits when project is approved; log blocked attempts.

**Strength 2: Validation of Numeric Budget Fields**
- Validates phases.*.budget.*.rate_quantity, rate_multiplier, rate_duration, this_phase with nullable|numeric|min:0.

#### Identified Contract Violations

**Violation 187: Validated Rules Omit Persisted Fields**
- **Pattern:** Validation includes only `rate_quantity`, `rate_multiplier`, `rate_duration`, `this_phase`. Persistence uses `$request->input('phases', [])` and creates ProjectBudget with `particular`, `rate_increase`, `next_phase` from `$budget['particular']`, `$budget['rate_increase']`, `$budget['next_phase']`. These fields are not in the validation rules.
- **Impact:** Unbounded or invalid `particular` (string), `rate_increase`, `next_phase` (numeric) can be written to DB; possible overflow, XSS, or constraint violation.
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validate** – Add rules for `particular` (string|max:255 or similar), `rate_increase`, `next_phase` (nullable|numeric|min:0) and use validated data for create.

**Violation 188: viewBudget and addExpense Referenced in Routes But Not in Controller**
- **Pattern:** Routes define `Route::get('/budgets/{project_id}', [BudgetController::class, 'viewBudget'])` and `Route::post('/budgets/{project_id}/expenses', [BudgetController::class, 'addExpense'])`. BudgetController only defines `store` and `update`. viewBudget and addExpense methods are not present.
- **Impact:** Requests to view budget or add expense hit missing methods (404 or fatal). Route-controller contract broken.
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Contract** – Implement viewBudget and addExpense or remove/redirect these routes.

---

## DevelopmentProjectController (Quarterly) Analysis

### DevelopmentProjectController

**Location:** `App\Http\Controllers\Reports\Quarterly\DevelopmentProjectController`

**Purpose:** Create, store, index, show quarterly development project reports.

#### Controller Design Analysis

**Strength 1: store() Top-Level Validation**
- Validates project_id, total_beneficiaries, reporting_period_month/year, goal, account dates, totals, photos, photo_descriptions, objective with types and bounds.

**Strength 2: Uses ProjectQueryService in index**
- index() uses ProjectQueryService::getProjectIdsForUser($user) for ownership (but then filters by user_id only on RQDPReport – see below).

#### Identified Contract Violations

**Violation 189: create($id) Route Parameter Not Validated**
- **Pattern:** `create($id)` uses `OldDevelopmentProject::findOrFail($id)`. Route parameter `$id` is not validated (e.g. exists:oldDevelopmentProjects,id). findOrFail returns 404 on missing; type/format not enforced.
- **Impact:** Invalid id could cause unexpected behavior before findOrFail; explicit contract improves robustness.
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validate** – Validate id (exists) or use route model binding; enforce authorization (user may create report for this project).

**Violation 190: store() Persists Unvalidated Nested Data**
- **Pattern:** Validation covers only top-level fields (project_id, total_beneficiaries, reporting_period_*, goal, account_*, photos, objective). Persistence uses `$request->input()` for objectives/activities (not_happened, why_not_happened, changes, why_changes, lessons_learnt, todo_lessons_learnt, month, summary_activities, qualitative_quantitative_data, intermediate_outcomes), account details (particulars, amount_forwarded, amount_sanctioned, total_amount, expenses_*, balance_amount), and outlooks (date, plan_next_month). None of these nested fields are validated.
- **Impact:** Unbounded or invalid strings/numbers can be written to DB; possible overflow, XSS, or constraint violation; inconsistent data shape.
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** 🔴 **Critical** – Add validation rules for all persisted nested fields (objectives, activities, account details, outlooks) or use FormRequest with nested rules; persist only validated data.

**Violation 191: index() Fetches All Reports Without Pagination**
- **Pattern:** `$reports = RQDPReport::where('user_id', $user->id)->with([...])->get();` – loads all reports for the user into memory. No pagination.
- **Impact:** Memory and performance degradation as report count grows; same anti-pattern as AdminReadOnlyController reportIndex (Violation 166) and ReportComparisonController (Violation 159).
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Scalability** – Use query-level pagination (e.g. `->paginate(15)`).

**Violation 192: index() Ownership Filter Inconsistent with ProjectQueryService**
- **Pattern:** Code gets `$projectIds = ProjectQueryService::getProjectIdsForUser($user)` but then queries `RQDPReport::where('user_id', $user->id)`. RQDPReport may use different project relation (e.g. project_id from old_development_projects). If quarterly reports are scoped by project ownership (user or in_charge), filtering by user_id only may be wrong when in_charge is not the report owner.
- **Impact:** Reports may be missing or incorrectly included depending on model structure; duplicate/inconsistent ownership logic.
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Clarify** – Align index filter with actual ownership (user_id vs project ownership); use getProjectIdsForUser if reports are project-scoped.

---

## ConfirmablePasswordController Analysis

### ConfirmablePasswordController

**Location:** `App\Http\Controllers\Auth\ConfirmablePasswordController`

**Purpose:** Show confirm-password view; confirm password and set session.

#### Controller Design Analysis

**Strength 1: Uses Auth Guard**
- store() uses Auth::guard('web')->validate() and ValidationException for invalid password.

#### Identified Contract Violations

**Violation 193: Password Not Explicitly Validated Before Auth Check**
- **Pattern:** store() passes `$request->password` to Auth::guard('web')->validate(). There is no explicit validation rule (e.g. required|string|min:8). Empty or missing password is rejected by Auth::validate (failure), but contract is implicit; no max length or format documented.
- **Impact:** Low – Auth validates credentials; explicit rule would clarify contract and allow custom messages (e.g. "Password is required").
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validate** – Add `$request->validate(['password' => 'required|string'])` (or match app password rules) before Auth::validate for consistent contract and messages.

---

## Summary Statistics (Batch 10)

| Category | Count |
|----------|-------|
| Controllers analyzed | 5 (ExportReportController Monthly, LivelihoodAnnexure, Budget, DevelopmentProject Quarterly, ConfirmablePassword) |
| New violations identified | 16 (178–193) |
| Phase 1 violations | 0 |
| Phase 2 violations | 7 |
| Phase 3 violations | 8 |
| Phase 4 violations | 1 |
| Critical issues | 1 (190) |

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
| Batch 8 | 5 | 0 | 0 | 7 | 0 |
| Batch 9 | 4 | 0 | 0 | 16 | 2–3 |
| Batch 10 | 5 | 0 | 0 | 16 | 1 |
| **Total** | **46** | **10** | **30** | **193** | **25–26** |

---

## Key Takeaways (Batch 10)

### Positive Patterns Observed

1. **ExportReportController (Monthly)** – Role-based access and error handling present; fallback when PDF render fails.
2. **LivelihoodAnnexureController** – Validates annexure arrays and uses validated data for updateOrCreate.
3. **BudgetController** – BudgetSyncGuard and validation for numeric budget fields; sync after update.
4. **DevelopmentProjectController (Quarterly)** – Top-level store validation and ProjectQueryService usage in index.

### Issues to Address

1. **ExportReportController** – Validate report_id; extract permission logic; fix provincial null reference; clarify admin role; reduce controller dependencies; review ini_set.
2. **LivelihoodAnnexureController** – Validate report_id and authorize; limit annexure array size; implement or fix show/edit/update routes.
3. **BudgetController** – Validate all persisted fields (particular, rate_increase, next_phase); implement or fix viewBudget and addExpense routes.
4. **DevelopmentProjectController (Quarterly)** – **Critical:** Validate all nested store data before persist; validate create($id) and authorize; paginate index; align ownership filter with project/report model.
5. **ConfirmablePasswordController** – Add explicit password validation for contract clarity.

### Cross-Batch Pattern

- **Route-controller mismatch** – Routes referencing methods that do not exist (LivelihoodAnnexureController show/edit/update, BudgetController viewBudget/addExpense) break the frontend-backend contract and should be fixed or removed.
- **Duplicate permission logic** – ExportReportController (Violation 179) continues the pattern of in-controller role checks that should be centralized (Policy or service).
- **Unvalidated nested persistence** – DevelopmentProjectController store (Violation 190) mirrors earlier batches where only top-level request is validated but nested data is persisted.

---

## DO NOT

This document is for diagnosis only:
- ❌ Do not implement fixes
- ❌ Do not add validation rules
- ❌ Do not refactor controllers

This audit serves as the foundation for phase-wise remediation planning.

---

*Document generated: January 31, 2026*  
*Batch 10 contract audit performed by: Senior Laravel Architect*
