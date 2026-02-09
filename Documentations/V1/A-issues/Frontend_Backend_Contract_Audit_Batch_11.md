# Frontend–Backend Contract Audit – Batch 11

## Purpose

This document continues the contract audit series, focusing on CrisisInterventionCenterController (Monthly), SkillTrainingController (Quarterly), BudgetExportController, and EmailVerificationNotificationController. This batch examines monthly crisis-intervention inmate profiles, quarterly skill-training report CRUD, budget export (Excel/PDF/report), and email verification notification flow.

---

## CrisisInterventionCenterController Analysis

### CrisisInterventionCenterController

**Location:** `App\Http\Controllers\Reports\Monthly\CrisisInterventionCenterController`

**Purpose:** Handle inmate profiles (handleInmateProfiles); retrieve profiles for export (getInmateProfiles). Used by ExportReportController for monthly report PDF/DOC.

#### Controller Design Analysis

**Strength 1: Validation of Nested Inmate Fields**
- handleInmateProfiles validates nested structure (inmates.children_below_18.*, inmates.women_18_30.*, etc.) with numeric and string|max:255 for other_status/other_count.

**Strength 2: Uses Validated Data for updateOrCreate**
- Builds categories from $validatedData with null coalescing; persists number and total.

#### Identified Contract Violations

**Violation 194: Route Parameter report_id Not Validated**
- **Pattern:** `handleInmateProfiles(Request $request, $report_id)` and `getInmateProfiles($report_id)` accept `$report_id` from route or caller. No validation that report_id exists in dp_reports or matches expected format. No check that the current user may access that report when called via route.
- **Impact:** Invalid or unauthorized report_id could write/read inmate data for wrong report; same pattern as LivelihoodAnnexureController (Violation 184).
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validate** – Validate report_id (exists, format) and enforce authorization (user may edit/view this report).

**Violation 195: getInmateProfiles Has No Authorization**
- **Pattern:** `getInmateProfiles($report_id)` returns `RQWDInmatesProfile::where('report_id', $report_id)->get()`. When called from ExportReportController, access was already checked; if ever exposed as a direct route, any caller could pass any report_id and retrieve inmate profiles. No ownership or role check inside the method.
- **Impact:** If route exists or method is reused without prior access check, information disclosure.
- **Phase classification:** **Phase 1 – Data Safety & Security**
- **Status:** ⚠️ **Authorization** – Ensure getInmateProfiles is only called after report access has been verified; or add explicit authorization inside the method.

**Violation 196: User-Controlled String Used as Array Key Then as status in DB**
- **Pattern:** Categories array uses `$validatedData['inmates']['children_below_18']['other_status'] ?? 'others'` as array key, and that same value is later used as `'status'` in updateOrCreate (via the $statuses loop: `foreach ($statuses as $status => $number)`). other_status is validated as string|max:255, but using it as PHP array key and then as DB status is fragile (empty string, special characters, or very long string could cause inconsistent keys or storage issues).
- **Impact:** Low–medium – validated max length; possible empty or duplicate key behavior; brittle if validation changes.
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Robustness** – Use a fixed key (e.g. 'other') for storage and store other_status in a separate column if needed; avoid user-controlled value as primary key/status.

---

## SkillTrainingController (Quarterly) Analysis

### SkillTrainingController

**Location:** `App\Http\Controllers\Reports\Quarterly\SkillTrainingController`

**Purpose:** Create, store, index, show, edit, update quarterly skill-training reports.

#### Controller Design Analysis

**Strength 1: store() and update() Top-Level Validation**
- Validates project_title, place, society_name, total_beneficiaries, reporting_period, account dates, amounts, photos, objective with types and bounds.

**Strength 2: Transaction and Helper Usage**
- store() uses DB::transaction; update() uses DB::beginTransaction/commit; LogHelper::logSafeRequest for allowed fields.

#### Identified Contract Violations

**Violation 197: store() Persists Unvalidated Nested Data**
- **Pattern:** Validation covers only top-level fields. Persistence uses `$request->input()` for trainee profiles (below_9, 10_fail, etc., and other_education, count_other_education), objectives/activities (expected_outcome, not_happened, why_not_happened, changes, why_changes, lessons_learnt, todo_lessons_learnt, month, summary_activities, qualitative_quantitative_data, intermediate_outcomes), account details (particulars, amount_forwarded, amount_sanctioned, total_amount, expenses_*, balance_amount), and outlooks (date, plan_next_month). None of these nested fields are validated before persist.
- **Impact:** Unbounded or invalid strings/numbers can be written to DB; same critical pattern as DevelopmentProjectController store (Violation 190).
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** 🔴 **Critical** – Add validation rules for all persisted nested fields (trainee profiles, objectives, activities, account details, outlooks) or use FormRequest with nested rules; persist only validated data.

**Violation 198: index() Fetches All Reports Without Pagination**
- **Pattern:** `$reports = RQSTReport::where('user_id', Auth::id())->with([...])->get();` – loads all reports for the user. No pagination.
- **Impact:** Memory and performance degradation as report count grows; same anti-pattern as DevelopmentProjectController (Violation 191) and AdminReadOnlyController reportIndex (Violation 166).
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Scalability** – Use query-level pagination (e.g. `->paginate(15)`).

**Violation 199: show($id) and edit($id) Have No Authorization**
- **Pattern:** `show($id)` and `edit($id)` use `RQSTReport::findOrFail($id)` and return view. There is no check that the report belongs to the current user (or that the user has permission to view/edit). Any authenticated user could view or edit any skill-training report by id.
- **Impact:** **Information disclosure and unauthorized modification** – User A can view/edit User B’s reports by guessing or enumerating ids.
- **Phase classification:** **Phase 1 – Data Safety & Security**
- **Status:** 🔴 **Critical** – Before showing or editing, verify report belongs to current user (e.g. `$report->user_id === Auth::id()`) or use Policy; abort 403 if not allowed.

**Violation 200: update() Persists Unvalidated Nested Data**
- **Pattern:** update() validates only top-level fields; delegates to updateObjectivesAndActivities, updateAccountDetails, updateOutlooks, updatePhotos, updateTraineeProfiles. These methods use `$request->input()` for objectives, activities, account details, outlooks, trainee profiles without validation before persist.
- **Impact:** Same as Violation 197; unvalidated nested data can be written on update.
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** 🔴 **Critical** – Validate all nested input in update path or in shared FormRequest; persist only validated data.

**Violation 201: Route Parameter id Not Validated for show / edit / update**
- **Pattern:** show($id), edit($id), update($request, $id) accept `$id` from route. No validation that id exists or is integer. findOrFail returns 404 on missing; type not enforced.
- **Impact:** Non-integer or malformed id could cause unexpected behavior before findOrFail; explicit contract improves robustness.
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validate** – Validate id (e.g. exists:rqst_reports,id) or use route model binding.

---

## BudgetExportController Analysis

### BudgetExportController

**Location:** `App\Http\Controllers\Projects\BudgetExportController`

**Purpose:** Export project budget to Excel/PDF (single project); generate budget report (multi-project, filter by type/status/dates, view/Excel/PDF).

#### Controller Design Analysis

**Strength 1: exportExcel and exportPdf Use ProjectPermissionHelper**
- Both methods call ProjectPermissionHelper::canView($project, $user) and abort(403) if not allowed.

**Strength 2: generateReport Uses BudgetValidationService**
- prepareReportData uses BudgetValidationService::getBudgetSummary for consistent budget data.

#### Identified Contract Violations

**Violation 202: Route Parameter project_id Not Validated for exportExcel / exportPdf**
- **Pattern:** `exportExcel($project_id)` and `exportPdf($project_id)` use `Project::where('project_id', $project_id)->firstOrFail()`. Route parameter project_id is not validated (e.g. exists:projects,project_id). firstOrFail returns 404 on missing; format/type not enforced at entry.
- **Impact:** Invalid project_id could cause unexpected behavior before query; explicit contract improves clarity and security.
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validate** – Validate project_id (exists:projects,project_id) or use route model binding.

**Violation 203: generateReport Filter Parameters Not Validated**
- **Pattern:** `$filters = ['project_type' => $request->input('project_type'), 'status' => $request->input('status'), 'start_date' => $request->input('start_date'), 'end_date' => $request->input('end_date'), 'format' => $request->input('format', 'view')];` – all used in query or switch without validation. project_type and status can be any string; start_date/end_date can be invalid dates; format can be any string (switch default is 'view').
- **Impact:** Invalid dates could cause query errors or wrong results; unexpected format could fall through to default; project_type/status could inject unintended values into query.
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validate** – Validate project_type and status against allowed values (or exists); validate start_date/end_date as date; validate format as in:view,excel,pdf.

**Violation 204: generateReport Has No User/Scope Authorization**
- **Pattern:** generateReport builds `$query = Project::with([...])` and applies optional filters (project_type, status, start_date, end_date), then `$projects = $query->get()`. There is no scope by current user (e.g. projects the user may view). Route is under auth middleware only (`Route::middleware('auth')->group`), so any authenticated user (executor, applicant, provincial, coordinator, general) can hit `/budgets/report` and receive data for all projects matching the filters.
- **Impact:** **Information disclosure** – Any authenticated user can view/export budget report for all projects (or filtered set), not limited to their own or their role’s scope.
- **Phase classification:** **Phase 1 – Data Safety & Security**
- **Status:** 🔴 **Critical** – Restrict generateReport to authorized roles (e.g. coordinator, general, admin) and/or scope query by user (e.g. ProjectQueryService::getProjectIdsForUser) so users only see projects they are allowed to see.

**Violation 205: generateReport Fetches All Matching Projects Without Pagination**
- **Pattern:** `$projects = $query->get();` – loads all projects matching filters into memory. No limit or pagination.
- **Impact:** Memory and performance degradation when many projects match; same anti-pattern as other list endpoints.
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Scalability** – Consider pagination or a reasonable limit for report generation; or document as admin/coordinator-only with bounded filters.

---

## EmailVerificationNotificationController Analysis

### EmailVerificationNotificationController

**Location:** `App\Http\Controllers\Auth\EmailVerificationNotificationController`

**Purpose:** Send a new email verification notification when user requests resend.

#### Controller Design Analysis

**Strength 1: Checks Verification State**
- store() checks $request->user()->hasVerifiedEmail() and redirects if already verified; otherwise sends notification and returns back with status.

**Observation:** No user-supplied input to validate; only authenticated user and redirect. Laravel’s default throttle middleware may apply to auth routes. No contract violations identified for this batch; controller is minimal and follows framework pattern.

---

## Summary Statistics (Batch 11)

| Category | Count |
|----------|-------|
| Controllers analyzed | 4 (CrisisInterventionCenter, SkillTraining Quarterly, BudgetExport, EmailVerificationNotification) |
| New violations identified | 12 (194–205) |
| Phase 1 violations | 3 (195, 199, 204) |
| Phase 2 violations | 7 |
| Phase 3 violations | 2 |
| Phase 4 violations | 0 |
| Critical issues | 4 (197, 199, 200, 204) |

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
| Batch 11 | 4 | 0 | 0 | 12 | 4 |
| **Total** | **50** | **10** | **30** | **205** | **29–30** |

---

## Key Takeaways (Batch 11)

### Positive Patterns Observed

1. **CrisisInterventionCenterController** – Validates nested inmate structure and uses validated data for updateOrCreate.
2. **SkillTrainingController** – Top-level validation, transactions, and LogHelper usage.
3. **BudgetExportController** – exportExcel/exportPdf use ProjectPermissionHelper::canView; generateReport uses BudgetValidationService.
4. **EmailVerificationNotificationController** – Minimal, state-check and framework pattern.

### Issues to Address

1. **CrisisInterventionCenterController** – Validate report_id and enforce authorization; avoid user-controlled string as DB status key where possible.
2. **SkillTrainingController** – **Critical:** Validate all nested store/update data before persist; add authorization to show/edit; paginate index(); validate route id.
3. **BudgetExportController** – Validate project_id for export; validate generateReport filters; **Critical:** scope generateReport by user/role so only authorized users see allowed projects; add pagination or limit for report generation.

### Cross-Batch Pattern

- **Unvalidated nested persistence** – SkillTrainingController store/update (Violations 197, 200) repeats the pattern from DevelopmentProjectController (Violation 190); all quarterly/monthly report controllers that persist objectives, activities, account details, outlooks from `$request->input()` without nested validation need remediation.
- **Missing authorization on show/edit** – SkillTrainingController (Violation 199) mirrors ReportAttachmentController (Violation 174); any “get by id” or “edit by id” must verify the resource is accessible to the current user.
- **Report/list endpoints without user scope** – BudgetExportController generateReport (Violation 204) exposes all projects to any authenticated user; similar to ensuring list/filter endpoints are scoped by ownership or role.

---

## DO NOT

This document is for diagnosis only:
- ❌ Do not implement fixes
- ❌ Do not add validation rules
- ❌ Do not refactor controllers

This audit serves as the foundation for phase-wise remediation planning.

---

*Document generated: January 31, 2026*  
*Batch 11 contract audit performed by: Senior Laravel Architect*
