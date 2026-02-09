# Phase 2 — Architectural Prevention

## Purpose

Phase 2 introduces **shared abstractions and contracts** so future features and maintenance follow consistent patterns. It exists to **prevent** recurrence of the classes of bugs addressed in Phase 0, 1A, and 1B — not merely to fix them once, but to make them **structurally impossible** going forward.

**Canonical reference**: `Phase_Wise_Refactor_Plan.md` → Phase 2; `Phase_Wise_Implementation_Guide.md` → §6.

**Execution**: Fix-First, Single-Deploy. Phase 2 work completed and verified locally; deployment occurs only after `PHASE_2_SIGNOFF.md` approval.

---

# TASK 1 — Phase 2 Objectives

## What Phase 2 Exists to PREVENT

| Class of Bug | Prevention Mechanism |
|--------------|----------------------|
| **Unscoped input polluting fill()** | `FormDataExtractor` + `FormSection::ownedKeys()` — only allowed keys reach controllers; normalization pipeline enforces scalar-only for fillable columns |
| **Inconsistent attachment handling** | `ProjectAttachmentHandler` — single place for validation, storage layout, error handling; no ad-hoc logic in models |
| **Numeric overflow / column-rule drift** | `BoundedNumericService` / `DecimalBounds` config — validation driven by schema or config; no JS/PHP drift |
| **Cross-module field collisions** | `FormSection::ownedKeys()` — orchestrator passes only owned keys; no ambiguous ownership |
| **Missing Spatie roles / duplicate role definitions** | `RoleGuard` / role contract — single source of truth; roles:sync on deploy; no assignRole on non-existent role |

---

## Classes of Bugs Now Structurally Impossible

After Phase 2 implementation:

- **Array-to-scalar conversion** — impossible: all fill paths go through `FormDataExtractor` with `ArrayToScalarNormalizer`; no raw `$request->all()` + `fill()`.
- **getClientOriginalExtension() on array** — impossible: all attachment paths use `ProjectAttachmentHandler` with normalize-to-array pattern; no model-level ad-hoc file handling.
- **SQL numeric overflow (this_phase, etc.)** — impossible: all numeric writes go through `BoundedNumericService` or `NumericBoundsRule` reading from config; column max enforced.
- **"There is no role named `applicant`"** — impossible: `RoleGuard` ensures Spatie roles exist before `assignRole`; sync runs on deploy.
- **Mass assignment from unrelated form sections** — impossible: orchestrator passes only `$request->only($section->ownedKeys())`; no cross-module leakage.

---

## How Phase 2 Differs from Phase 1A and Phase 1B

| Aspect | Phase 1A | Phase 1B | Phase 2 |
|-------|----------|----------|---------|
| **Focus** | Fix at the call site (controller-by-controller) | Fix structural duplication (IES/IIES models) | Introduce abstractions that enforce patterns |
| **Scope** | 46 controllers refactored to use `$request->only()` + `ArrayToScalarNormalizer` | IES/IIES attachment unification, read fallback, LogHelper | New services, interfaces, config; adoption by existing code |
| **Change type** | In-place refactor (same structure, safer input) | In-place refactor (same structure, aligned storage) | New components that controllers/models delegate to |
| **Prevention** | Manual discipline per controller | Shared pattern within IES/IIES | Structural — impossible to bypass without explicit opt-out |
| **Overlap** | N/A | Phase 2 does NOT redo 1B; 1B.1 is prerequisite for Phase 2.2 | Phase 2 builds on 1A and 1B outcomes |

**Phase 2 does not overlap Phase 1B cleanup.** Phase 1B completed attachment unification (1B.1), legacy fallback (1B.1a), and LogHelper consistency (1B.1b). Phase 2.2 (ProjectAttachmentHandler) builds on top of that aligned structure; it does not re-implement it.

---

# TASK 2 — Phase 2 Building Blocks

## 2.1 FormDataExtractor / Validation Layer

| Attribute | Description |
|-----------|--------------|
| **Responsibility** | Extracts, validates, and normalizes request data for a given set of allowed keys. Accepts `$request`, `$allowedKeys`, `$normalizers` (e.g. ArrayToScalar, PlaceholderToZero). Returns clean array safe for `fill()`. |
| **Boundaries** | Input: Request object, allowed keys, optional normalizers. Output: associative array of scalar values. Used by FormRequest `prepareForValidation()` or controller pre-fill step. |
| **Must NOT** | Change form field names; add new validation rules beyond existing; touch attachments; modify routes; run external I/O. |

---

## 2.2 ProjectAttachmentHandler

| Attribute | Description |
|-----------|--------------|
| **Responsibility** | Single service for file upload handling: validate (type, size, count), normalize to array of files, store to configured path/table, return result. Interface: `handle(Request $request, string $projectId, array $fieldConfig): AttachmentResult`. |
| **Boundaries** | Input: Request, project ID, field config (e.g. `['aadhar_card' => ['max_files' => 5, 'max_size' => 5242880]]`). Output: success/failure, paths, errors. Models (IES, IIES, IAH, ILP) delegate to this handler. |
| **Must NOT** | Change DOM structure; alter form field names; drop legacy column support (read fallback remains in models); handle report/export flows (different domain). |

---

## 2.3 FormSection / ownedKeys

| Attribute | Description |
|-----------|--------------|
| **Responsibility** | Declares data ownership for multi-step forms. Each module implements `ownedKeys(): array`. Orchestrator uses this to pass only `$request->only($section->ownedKeys())` to each controller. |
| **Boundaries** | Each section owns a finite set of keys. Orchestrator (e.g. ProjectController or future ProjectStoreOrchestrator) routes data by section. No implicit ownership. |
| **Must NOT** | Change form field names (unless Phase 2 explicitly includes namespacing — see OUT-OF-SCOPE); alter validation rules; touch attachment handling; modify report flows. |

---

## 2.4 RoleGuard / Role Contract

| Attribute | Description |
|-----------|--------------|
| **Responsibility** | Ensures Spatie roles exist before `assignRole`. Syncs `UserRole` constants with Spatie `roles` table on deploy or via scheduler. Policy layer uses `User::hasRole(UserRole::APPLICANT)` instead of string checks. |
| **Boundaries** | Role definitions in `UserRole`; seeder/command syncs to DB. Used at user creation and policy checks. |
| **Must NOT** | Change permission logic; alter route middleware definitions without coordinated plan; drop backward compatibility for existing role strings during migration. |

---

## 2.5 Numeric Bounds / Decimal Guards (BoundedNumericService / DecimalBounds)

| Attribute | Description |
|-----------|--------------|
| **Responsibility** | Config-driven numeric bounds (e.g. `project_budgets.this_phase => ['max' => 99999999.99]`). `NumericBoundsRule` reads from config. Shared `calculateAndClamp($formula, $inputs, $max)` for server-side derived fields. |
| **Boundaries** | Config defines column/field max values. Validation and controller logic use this config. No hardcoded magic numbers. |
| **Must NOT** | Alter database schema (column precision); change `config/budget.php` structure without coordinated plan; introduce new calculation formulas beyond existing budget logic. |

---

# TASK 3 — Phase 2 IN-SCOPE and OUT-OF-SCOPE

## IN-SCOPE (Phase 2 WILL Build)

| ID | Item | Description |
|----|------|-------------|
| **2.1** | FormDataExtractor / Validation Layer | Abstraction for scoped extract + normalize; FormRequests or controllers use it. |
| **2.2** | ProjectAttachmentHandler | Interface + service; IES, IIES, IAH, ILP delegate to it. |
| **2.3** | BoundedNumericService / DecimalBounds | Config-driven numeric bounds; NumericBoundsRule integration; calculateAndClamp for derived fields. |
| **2.4** | FormSection / ownedKeys | Each module declares ownedKeys(); orchestrator passes only owned keys. |
| **2.5** | RoleGuard / Role Contract | RoleGuard ensures Spatie roles exist; Policy layer uses UserRole constants. |

---

## OUT-OF-SCOPE (Phase 2 WILL NOT Build)

| Item | Reason |
|------|--------|
| **Form field namespacing** | Request-shape change (e.g. `family_contribution` → `ies_education[family_contribution]`). Deferred beyond Phase 2; ownedKeys works with current flat keys. |
| **ProjectController store/update refactor** | Per EXPLICITLY DEFERRED: changing call order or request shaping globally can break all project types. Phase 2.4 may introduce orchestrator pattern but does not mandate full rewrite. |
| **Report / Monthly / Quarterly attachment handling** | Different domain; report-specific handler deferred until ProjectAttachmentHandler proven for project flows. |
| **ExportController refactor** | Depends on project data shape; must lag behind. |
| **Database schema changes** | No column precision changes, no legacy column drops. |
| **config/budget.php structural changes** | Used by BudgetSyncService, ProjectFundFieldsResolver; coordinated plan required; not Phase 2 scope. |
| **Dropping legacy IES attachment columns** | Phase 1B preserves fallback; Phase 2 does not drop columns. |
| **Multi-step form DOM restructure** | Conditional partial inclusion unchanged; no DOM changes. |

---

## Deferred Beyond Phase 2

| Item | Prerequisite |
|------|--------------|
| Form field namespacing | Phase 2.4 (FormSection) complete; controllers use ownedKeys. |
| Full ProjectController orchestration rewrite | Phase 2.4 design proven; all sub-controllers adopt FormSection. |
| Report attachment handler | Phase 2.2 (ProjectAttachmentHandler) complete for project flows. |
| ExportController refactor | Phase 1A.4 complete (done); export tests cover all project types. |
| Schema migration for budget columns | Phase 2.3 designed; migration plan approved. |

---

# Dependencies on Phase 1A / 1B Outcomes

| Phase 2 Item | Depends On |
|--------------|------------|
| **2.1 FormDataExtractor** | Phase 1A: Controllers use scoped input; FormDataExtractor formalizes and centralizes the pattern. |
| **2.2 ProjectAttachmentHandler** | Phase 1B.1: IES and IIES use aligned storage (`*_attachment_files`); read path uses `getFilesForField()`. Without 1B.1, handler would have to support legacy column writes. |
| **2.3 BoundedNumericService** | Phase 1A.2: Budget derived-field enforcement done; Phase 0.4: overflow guard in place. Phase 2.3 extends to config-driven pattern. |
| **2.4 FormSection / ownedKeys** | Phase 1A.4: All controllers use `$request->only($fillable)`; fillable effectively IS ownedKeys. Phase 2.4 formalizes at orchestration level. |
| **2.5 RoleGuard** | Phase 1A.1: UserRole constant, seeder, roles:sync. Phase 2.5 adds guard/service to ensure sync before assignRole. |

---

# Risks

| Risk | Mitigation |
|------|------------|
| **Adoption friction** | Incremental adoption; controllers can migrate one-by-one to FormDataExtractor and ProjectAttachmentHandler. |
| **Orchestrator change breaks flows** | Phase 2.4 design must preserve existing call order; test all project types before/after. |
| **Config drift** | DecimalBounds config must be maintained when schema changes; document ownership. |
| **Report flows conflated with project flows** | Phase 2.2 explicitly excludes report attachments; do not extend handler to reports in Phase 2. |

---

# Exit Criteria

- [ ] FormDataExtractor (or equivalent) designed and adopted by at least one module.
- [ ] ProjectAttachmentHandler interface + service exist; IES and IIES delegate to it.
- [ ] BoundedNumericService / DecimalBounds config exists; NumericBoundsRule uses it.
- [ ] FormSection / ownedKeys pattern designed; at least one module declares ownedKeys.
- [ ] RoleGuard ensures Spatie roles exist before assignRole; roles:sync run on deploy.
- [ ] Local verification: representative flows (IES, IIES, budget, user creation) succeed.
- [ ] No new errors in `storage/logs/laravel.log` during exercised flows.
- [ ] Each Phase 2 item has an implementation MD in `Implementations/Phase_2/`.
- [ ] `PHASE_2_SIGNOFF.md` approved.

---

# Implementation Documentation

Store one MD file per Phase 2 architectural component (design before implementation; implementation summary after):

- `FormDataExtractor.md`
- `ProjectAttachmentHandler.md`
- `BoundedNumericService_DecimalBounds.md`
- `FormSection_ownedKeys.md`
- `RoleGuard_RoleContract.md`

**Do not create implementation MDs until Phase 2 scope is locked and entry conditions are met.**

---

*Phase 2 scope locked — 2026-02-08*

---

## Design Status

All Phase 2 architectural components (2.1–2.5) have approved design documents.
Implementation has not started.
