# Phase-wise Refactor Plan

**Context**: Production system with recurring data integrity and validation issues.  
**Based on**: Production log analysis (laravel-production-070226.log), Production_Errors_Analysis_070226.md.  
**Principle**: No full rewrite; incremental, low-risk changes over multiple sprints.

**Execution Model**: Fix-First, Single-Deploy. All phases (0 → 1A → 1B → 2) are **completed first** with local verification. Deployment occurs **only once** after all phase sign-offs. See Phase_Wise_Implementation_Guide.md § Single Deploy & Verification Strategy.

---

# PHASE 0 — Stabilization & Guard Rails (No Behavior Change)

**Goal**: Stop crashes and invalid data at the boundary without changing business logic.  
**Deploy**: Use the checklist below; each item is additive and defensive.

---

## Phase 0 Deployment Checklist

### 0.1 Add Missing `applicant` Role to Spatie

- **Preconditions**
  - DB access for production; `roles` table exists (Spatie migration run).
  - No open user-creation sessions that are mid-transaction.
- **Exact change scope**
  - `database/seeders/RolesAndPermissionsSeeder.php`: Add `Role::firstOrCreate(['name' => 'applicant', 'guard_name' => 'web']);`
  - For production: run `php artisan db:seed --class=RolesAndPermissionsSeeder` OR one-off: `Role::firstOrCreate(['name' => 'applicant', 'guard_name' => 'web']);` via tinker.
- **Post-deploy verification**
  - `SELECT * FROM roles WHERE name = 'applicant';` returns 1 row.
  - Provincial/General creates a new user with role "applicant" → no error; user appears in list.
- **Expected production impact**
  - Unblocks applicant creation; no existing behavior change.
- **Rollback criteria**
  - If `assignRole` still fails or new error appears: revert seeder; run `DELETE FROM roles WHERE name = 'applicant';` only if no users have that role.

---

### 0.2 Normalize IES Attachments File Input (Array → Iterable)

- **Preconditions**
  - 0.1 need not precede; can deploy independently.
  - `project_IES_attachments` and `project_IES_attachment_files` tables exist.
- **Exact change scope**
  - `app/Models/OldProjects/IES/ProjectIESAttachments.php`: In `handleAttachments()`, replace single-file logic with normalize-to-array + `foreach` (mirror `ProjectIIESAttachments`).
  - Only the loop structure; no change to storage paths or DB schema.
- **Post-deploy verification**
  - Upload single file for IES attachment (e.g. aadhar_card) → succeeds.
  - Upload multiple files for same field (if form supports) → all stored; no `getClientOriginalExtension()` error in logs.
- **Expected production impact**
  - IES attachment uploads work; multi-file supported.
- **Rollback criteria**
  - Any `getClientOriginalExtension()` or `Call to a member function ... on array` in logs for IES attachments → revert.

---

### 0.3 Scalar-Only Fill for IES Education Background

- **Preconditions**
  - 0.5 (ArrayToScalarNormalizer) should be deployed first, OR implement inline scalar coercion in controller.
- **Exact change scope**
  - `app/Http/Controllers/Projects/IES/IESEducationBackgroundController.php`: Replace `$request->all()` + `fill()` with `$request->only($fillable)` + array-to-scalar coercion (or `ArrayToScalarNormalizer::forFillable()`).
  - Fields: `previous_class`, `amount_sanctioned`, `amount_utilized`, `scholarship_previous_year`, `academic_performance`, `present_class`, `expected_scholarship`, `family_contribution`, `reason_no_support`.
- **Post-deploy verification**
  - Create new IES project (IOES) through full flow → Education Background saves; no "Array to string conversion" in logs.
  - Edit existing IOES project, change Education Background → saves.
- **Expected production impact**
  - IES project creation/update succeeds for Education Background section.
- **Rollback criteria**
  - "Array to string conversion" for `project_IES_educational_background` → revert.

---

### 0.4 Budget Overflow Guard — Enforce NumericBoundsRule on All Paths

- **Preconditions**
  - Confirm `BudgetController` is only invoked via `ProjectController` (no AJAX or API bypass).
- **Exact change scope**
  - `app/Http/Controllers/Projects/BudgetController.php`: Before each `ProjectBudget::create()`, clamp `this_phase` and `next_phase`: `min((float)($v ?? 0), 99999999.99)`.
  - No change to validation rules or FormRequest.
- **Post-deploy verification**
  - Edit Development Project budget with valid values → saves.
  - Attempt (in staging) to submit extreme values → either validation fails or clamped value stored; no SQL overflow.
- **Expected production impact**
  - SQL overflow for `this_phase`/`next_phase` prevented.
- **Rollback criteria**
  - `SQLSTATE[22003]: Numeric value out of range` for `project_budgets` → revert.

---

### 0.5 Add Array-to-Scalar Normalizer (Reusable)

- **Preconditions**
  - None.
- **Exact change scope**
  - `app/Support/Normalization/ArrayToScalarNormalizer.php` (new): `static function forFillable(array $data, array $fillable): array` — for each key in `$fillable`, if value is array, set to `reset($value)` or null.
  - `app/Http/Controllers/Projects/IES/IESEducationBackgroundController.php`: Use `ArrayToScalarNormalizer::forFillable($request->only($fillable), $fillable)` before `fill()`.
- **Post-deploy verification**
  - Same as 0.3; IES Education Background saves.
- **Expected production impact**
  - Reusable utility; no direct user-visible change beyond 0.3.
- **Rollback criteria**
  - If 0.3 regresses after this change, revert both 0.3 and 0.5.

---

### Phase 0 Summary

| # | Item | Effort | Risk |
|---|------|--------|------|
| 0.1 | Applicant role | 0.5 day | None |
| 0.2 | IES attachments array | 1 day | Low |
| 0.3 | IES Education Background scalar fill | 0.5 day | Low |
| 0.4 | Budget overflow guard | 0.5 day | None |
| 0.5 | ArrayToScalarNormalizer | 0.5 day | None |

**Total**: ~3 days. Deploy order: 0.1, 0.5, 0.3 (0.5 before 0.3), 0.2, 0.4. Can batch 0.1+0.5+0.3+0.4, then 0.2.

---

## PHASE 0.6 — Verification & Safety Nets

Lightweight, production-safe checks after Phase 0 deployment.

---

### 0.6.1 Verification by Phase 0 Item

| Phase 0 Item | Errors/Logs That Should Disappear | New Logs (Optional, Temporary) | Data Conditions Confirming Fix | Rollback Signal |
|--------------|-----------------------------------|--------------------------------|--------------------------------|-----------------|
| **0.1** | `Error storing user {"error":"There is no role named \`applicant\` for guard \`web\`."}` | None | `roles` table has row `name=applicant`; new applicants created successfully | `assignRole` exceptions; users created but not assignable |
| **0.2** | `Call to a member function getClientOriginalExtension() on array` (ProjectIESAttachments) | None | IES attachment uploads complete; `project_IES_attachments` or `project_IES_attachment_files` populated | Same error in logs; upload failure for IES |
| **0.3** | `Error saving IES educational background {"error":"Array to string conversion"` | Optional: `Log::debug('IES Education Background saved', ['project_id' => $id])` (remove after 1 week) | `project_IES_educational_background` has new/updated rows for IOES projects | "Array to string conversion" for that table |
| **0.4** | `SQLSTATE[22003]: Numeric value out of range: 1264 Out of range value for column 'this_phase'` | Optional: `Log::warning('Budget value clamped', [...])` when clamp triggers (remove after 2 weeks) | No overflow errors; `project_budgets.this_phase` ≤ 99999999.99 | SQL overflow for `this_phase`/`next_phase` |
| **0.5** | N/A (supporting 0.3) | None | Same as 0.3 | N/A |

---

### 0.6.2 Post-Deploy Verification Routine (Run within 24–48h)

- **Log scan** (e.g. `tail -1000 storage/logs/laravel-production-*.log`):
  - Absence of: `applicant`, `getClientOriginalExtension() on array`, `Array to string conversion` (project_IES_educational_background), `Out of range value for column 'this_phase'`.
- **DB spot checks**:
  - `SELECT name FROM roles WHERE name = 'applicant';` → 1 row.
  - `SELECT project_id, this_phase FROM project_budgets ORDER BY updated_at DESC LIMIT 20;` → all `this_phase` ≤ 99999999.99.
- **Smoke test** (manual or lightweight script):
  - Provincial creates applicant → success.
  - Create/edit IES project, save Education Background → success.
  - Upload IES attachment → success.

---

### 0.6.3 Rollback Signals (Immediate Revert)

- Any of the exact error messages above reappearing after deployment.
- Spike in 5xx or unexpected validation errors on project create/edit.
- Users reporting "cannot create applicant" or "cannot save education" or "attachment upload fails".

---

# PHASE 1 — Structural Hardening (Controlled Refactors)

**Goal**: Remove implicit assumptions and duplicate logic; clarify contracts between layers.  
**Deploy**: Per sub-phase; strict execution order within each sub-phase.

---

## PHASE 1A — Input & Authority Hardening

**Purpose**: Fix data integrity at the source (input handling, authority calculations). Risk reduction first.

**Execution order**: 1A.1 → 1A.2 → 1A.3 → 1A.4. Do not reverse.

---

### 1A.1 Centralize Role Definitions (Enum + Seeder Sync)

- **Anti-pattern**: Roles in `users.role` enum, Spatie `roles` table, routes; no single source. `applicant` was in enum but not Spatie.
- **Files**: `app/Constants/UserRole.php` (new), `database/seeders/RolesAndPermissionsSeeder.php`, `app/Console/Commands/SyncRolesCommand.php` (new, optional).
- **Target**: `UserRole::all()`, `UserRole::spatieRoles()`; seeder loops `firstOrCreate`; `php artisan roles:sync` for production.
- **Why first**: Unblocks and formalizes 0.1; no other Phase 1 task depends on it, but many reference roles.
- **Dependencies**: 0.1 done.
- **If order violated**: Role drift; new roles missing from Spatie again.

---

### 1A.2 Budget: Enforce Derived Field Bounds at Controller Level

- **Anti-pattern**: `this_phase` derived client-side; overflow reached DB despite validation.
- **Files**: `app/Http/Controllers/Projects/BudgetController.php`.
- **Target**: Recalculate `this_phase` server-side from `rate_quantity`, `rate_multiplier`, `rate_duration`; clamp to `NumericBoundsRule::MAX`.
- **Why second**: Highest data-integrity impact; independent of form/controller refactors.
- **Dependencies**: 0.4 done.
- **If order violated**: N/A (no dependency on 1A.1 for this change).

---

### 1A.3 Replace `$request->all()` + `fill()` in IES and IIES Controllers

- **Anti-pattern**: `$request->all()` + `fill()` in IES/IIES; arrays from other sections can pollute fill.
- **Files**:
  - IES: `IESEducationBackgroundController`, `IESPersonalInfoController`, `IESImmediateFamilyDetailsController`, `IESFamilyWorkingMembersController`, `IESExpensesController`, `IESAttachmentsController`
  - IIES: `EducationBackgroundController`, `IIESAttachmentsController`
- **Target**: `$request->only($fillable)` + `ArrayToScalarNormalizer::forFillable()` or FormRequest with `validated()`.
- **Why third**: IES/IIES had production errors; scoped input reduces risk before touching forms.
- **Dependencies**: 0.5, 0.3 done.
- **If order violated**: Risk of regression if form changes (1B.2) are done first without input hardening.

---

### 1A.4 Replace `$request->all()` + `fill()` in Remaining Project Controllers

- **Anti-pattern**: Same as 1A.3; 40+ controllers.
- **Modules**: IAH, ILP, IGE, CCI, RST, LDP, EduRUT, CIC (see original 1.1 list).
- **Target**: Same pattern as 1A.3; one module per sprint.
- **Why fourth**: Broader coverage after IES/IIES proven; avoids refactor collisions with 1B.
- **Dependencies**: 1A.3 done; ArrayToScalarNormalizer in use.
- **If order violated**: High volume of changes; risk of breaking multiple project types if done hastily.

---

### Phase 1A Summary

| # | Task | Effort | Dependencies |
|---|------|--------|--------------|
| 1A.1 | Role centralization | 0.5 sprint | 0.1 |
| 1A.2 | Budget derived-field enforcement | 0.5 sprint | 0.4 |
| 1A.3 | IES/IIES scoped input | 1 sprint | 0.5, 0.3 |
| 1A.4 | Remaining controllers scoped input | 2 sprints | 1A.3 |

**1A must complete before 1B.** 1B changes form structure; doing 1B before 1A risks breaking controllers that still expect unscoped input.

---

## PHASE 1B — Structural Cleanup

**Purpose**: Correct structural issues (form fields, attachment logic) after input hardening.

**Execution order**: 1B.1 → 1B.2. Do not start 1B until 1A is complete.

---

### 1B.1 Unify IES and IIES Attachment Handling

- **Anti-pattern**: IES uses old single-file columns; IIES uses `project_*_attachment_files`; logic duplicated.
- **Files**: `app/Models/OldProjects/IES/ProjectIESAttachments.php`, `app/Models/OldProjects/IIES/ProjectIIESAttachments.php` (optional shared trait).
- **Target**: IES writes to `project_IES_attachment_files`; shared `normalizeFiles`/`validateFile` pattern.
- **Why first in 1B**: 0.2 fixed crash; this completes migration. Independent of form namespacing.
- **Dependencies**: 0.2, 1A.3 (IES controller uses scoped input).
- **If order violated**: Refactoring IES model while controller still uses `$request->all()` can cause unexpected field flow.

---

### 1B.2 Normalize Form Field Names to Avoid Collision

- **Anti-pattern**: `family_contribution` (IES) and `family_contribution[]` (IGE) collide when both in DOM.
- **Files**: `resources/views/projects/partials/IES/`, `Edit/IGE/`, etc.; controllers that read these fields.
- **Target**: Namespace by section, e.g. `ies_education[family_contribution]`, or conditional partial inclusion.
- **Why second in 1B**: Requires controller changes to read prefixed input; 1A.3/1A.4 must already use scoped input so we know which keys each controller owns.
- **Dependencies**: 1A.3, 1A.4 (controllers must be refactored to read explicit keys).
- **If order violated**: Changing form names before controllers expect them will drop data; users will see empty saves.

---

### Phase 1B Summary

| # | Task | Effort | Dependencies |
|---|------|--------|--------------|
| 1B.1 | IES/IIES attachment unification | 1 sprint | 0.2, 1A.3 |
| 1B.2 | Form field namespacing | 1 sprint | 1A.3, 1A.4 |

---

### Phase 1 Execution Order (Visual)

```
0.1 → 0.5 → 0.3 → 0.2 → 0.4   (Phase 0)
         ↓
1A.1 (roles) → 1A.2 (budget) → 1A.3 (IES/IIES input) → 1A.4 (rest input)
         ↓
1B.1 (IES/IIES attachments) → 1B.2 (form namespacing)
```

**Do not fork**: 1B.1 and 1B.2 must not run in parallel; 1B.2 touches form structure that 1B.1 may rely on for attachment field names.

---

# EXPLICITLY DEFERRED (Do Not Refactor Yet)

**Purpose**: Guardrail against well-intentioned but risky refactors. Do not touch these until prerequisites are met.

---

## Modules / Files / Changes — Do Not Touch Until Phase 2

| Area | What Not to Touch | Why Dangerous | Prerequisites to Refactor |
|------|-------------------|---------------|---------------------------|
| **ProjectController store/update orchestration** | `ProjectController@store`, `ProjectController@update` — the switch/case that routes to sub-controllers | Changing call order or request shaping globally can break all project types at once. No isolation. | Phase 1A.4 complete; each sub-controller uses scoped input. Phase 2.4 (FormSection/ownedKeys) designed. |
| **Reports / Monthly / Quarterly controllers** | `ReportController`, `MonthlyDevelopmentProjectController`, `Quarterly/*Controller` — attachment and photo handling | Report flows have different validation and storage; touching them in Phase 1 risks breaking approvals, workflows. | Phase 2.2 (ProjectAttachmentHandler) exists; report-specific handler designed. |
| **ExportController** | `ExportController` — project data loading for PDF/Excel | Tightly coupled to ProjectController data shape; refactor can break exports for all project types. | Phase 1A.4 complete; export tests cover all project types. |
| **Permission / Policy layer** | `ProjectPermissionHelper`, `canEdit`, `canView` — role checks scattered in controllers | Changing role checks before `UserRole` and Spatie are synced can lock out valid users or allow invalid access. | Phase 1A.1 complete; `UserRole` constant used; `roles:sync` run. |
| **Routing and middleware** | `routes/web.php` — `role:executor,applicant` and similar | Role names in middleware are strings; changing to `UserRole::*` before 1A.1 can break if constant values differ. | Phase 1A.1 complete; `UserRole` matches DB. |
| **Database schema for budgets** | `project_budgets` — `decimal(10,2)` column definitions | Altering column precision without coordinated validation change can cause insert/update mismatches. | Phase 2.3 (BoundedNumericService / DecimalBounds config) designed; migration plan exists. |
| **Legacy attachment columns** | `project_IES_attachments.aadhar_card`, `fee_quotation`, etc. | Dropping or renaming before IES fully uses `project_IES_attachment_files` will break existing attachments and views. | Phase 1B.1 complete; migration verified; read paths use `getFilesForField()`. |
| **config/budget.php** | Budget field mappings, strategy classes | Used by `BudgetSyncService`, `ProjectFundFieldsResolver`; changing structure can break fund calculations and reports. | Phase 2.3 and budget refactor plan approved. |
| **Multi-step form DOM structure** | Conditional inclusion of partials by project type | Removing or collapsing partials before controllers are scoped will send empty data to controllers expecting full request. | Phase 1A.4 and 1B.2 complete. |

---

## Rationale

- **ProjectController**: Single point of failure; refactor only when sub-controllers are hardened and testable.
- **Reports**: Different domain (approvals, photos, attachments); conflation with project attachments increases risk.
- **ExportController**: Depends on project data shape; must lag behind project refactors.
- **Permission/Policies**: Role changes must be atomic with `UserRole` and Spatie sync.
- **Schema / config**: Requires coordinated migration, validation, and deploy; not suitable for Phase 1.
- **Legacy columns**: Keep until new structure is proven and migrated.

---

# PHASE 2 — Architectural Improvements (Prevent Future Drift)

**Goal**: Introduce shared abstractions and contracts so new features follow consistent patterns.

---

## 2.1 Validation & Normalization Layer Strategy

- **Problem**: Validation and normalization are scattered (FormRequests, controllers, inline). No standard for handling `$request->all()` in multi-step forms.
- **Abstraction**:
  - **FormDataExtractor** (or extend `NormalizesInput`): Accepts `$request`, `$allowedKeys`, `$normalizers` (e.g. `ArrayToScalar`, `PlaceholderToZero`). Returns clean array for `fill()`.
  - **Module-specific FormRequests** use `prepareForValidation()` → `FormDataExtractor` → `merge()`.
- **Long-term benefit**: All module inputs pass through same pipeline; easy to add logging, sanitization, or schema checks.
- **Trade-offs**: New abstraction to learn; some modules may need a transition period.

---

## 2.2 Standard Pattern for File Uploads

- **Problem**: IES, IIES, IAH, ILP each implement attachment handling differently. No shared validation, storage layout, or error handling.
- **Abstraction**:
  - **ProjectAttachmentHandler** (interface + service): `handle(Request $request, string $projectId, array $fieldConfig): AttachmentResult`.
  - Field config: `['aadhar_card' => ['max_files' => 5, 'max_size' => 5242880, 'allowed_types' => [...]]]`.
  - All attachment models (IES, IIES, IAH, ILP) delegate to this handler.
- **Long-term benefit**: Single place for validation, storage paths, cleanup; easier to add virus scan or resize later.
- **Trade-offs**: Requires refactoring all attachment logic; Phase 1B.1 is a stepping stone.

---

## 2.3 Standard Pattern for Numeric Calculations with Bounds

- **Problem**: Budget and other numeric fields have implicit bounds (DB column, business rule). Calculation logic duplicated in JS and PHP.
- **Abstraction**:
  - **BoundedNumericService** or **DecimalBounds** config: `project_budgets.this_phase => ['max' => 99999999.99]`.
  - **NumericBoundsRule** reads from config or a registry.
  - Shared `calculateAndClamp($formula, $inputs, $max)` for server-side derived fields.
- **Long-term benefit**: Database schema changes drive validation; no drift between column size and rule.
- **Trade-offs**: Config maintenance; possible need for DB introspect for max values.

---

## 2.4 Multi-Step Form Data Ownership

- **Problem**: ProjectController orchestrates many sub-controllers; each receives full `$request`. No clear ownership of which keys belong to which module.
- **Abstraction**:
  - **FormSection** (or similar): Each module declares `ownedKeys(): array` (e.g. `['previous_class', 'amount_sanctioned', ...]`).
  - Orchestrator passes only `$request->only($section->ownedKeys())` to each controller.
  - Optional: `ProjectStoreOrchestrator` that collects section configs and routes data.
- **Long-term benefit**: No cross-module field collisions; explicit contracts.
- **Trade-offs**: Refactor of ProjectController store/update flow; may need API/form structure changes.

---

## 2.5 Role and Permission Management Contract

- **Problem**: Roles come from `users.role`, Spatie, and middleware strings; no single contract.
- **Abstraction**:
  - **UserRole** constant class (Phase 1.3).
  - **RoleGuard**: Ensures Spatie roles exist before `assignRole`; syncs on deploy or via scheduler.
  - **Policy layer**: Use `User::hasRole(UserRole::APPLICANT)` instead of string checks.
- **Long-term benefit**: New roles require one change; deploy ensures DB consistency.
- **Trade-offs**: Deprecation of ad-hoc role checks; migration of legacy code.

---

## Phase 2 Summary

| # | Item | Problem Solved | Risk |
|---|------|----------------|------|
| 2.1 | Validation/normalization layer | Scattered validation, array-in-fill | Medium (adoption) |
| 2.2 | File upload standard | Inconsistent attachment handling | Medium (refactor scope) |
| 2.3 | Numeric bounds pattern | Column/rule drift | Low |
| 2.4 | Form data ownership | Field collision, unclear ownership | Medium (orchestrator change) |
| 2.5 | Role/permission contract | Missing roles, duplicate definitions | Low |

---

# Execution Order & Sprint Suggestions

**Sprint 1 (Stabilization)**  
- Phase 0 checklist: 0.1, 0.5, 0.3, 0.2, 0.4.  
- Run Phase 0.6 verification within 24–48h.

**Sprint 2**  
- Phase 1A.1 (roles), 1A.2 (budget derived field).

**Sprint 3–4**  
- Phase 1A.3 (IES/IIES scoped input), 1A.4 (remaining controllers, module by module).

**Sprint 5**  
- Phase 1B.1 (IES/IIES attachment unification).

**Sprint 6**  
- Phase 1B.2 (form field namespacing).

**Sprint 7+**  
- Phase 2 items; 2.1 (validation layer) and 2.5 (role contract) first.  
- Do not start Phase 2 until "EXPLICITLY DEFERRED" prerequisites are met for the target area.

---

*Document version: 1.1 — February 2026 (refined for execution readiness)*
