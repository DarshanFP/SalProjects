# Phase 2.4 — FormSection / ownedKeys (Design Only)

## 1. Purpose and Problem Statement

### Tie to Phase 1A Bugs

Phase 1A addressed data integrity bugs caused by unscoped request handling in multi-step project forms:

| Bug | Source | Root Cause |
|-----|--------|------------|
| **Mass assignment from unrelated sections** | Phase 1A Refactor Playbook | `$request->all()` pulls in all form data (phases, budget, IES, IIES, IGE, etc.). No ownership of which keys belong to which controller. Unrelated section data reaches the wrong controller and can pollute `fill()`. |
| **Cross-section field collisions** | Production_Errors_Analysis_070226.md; FormDataExtractor.md | `family_contribution` exists in both IES Education and IGE budget. When both sections are in the DOM, unscoped input means the IES controller may receive the IGE value (or an array) instead of its own. Result: wrong value persisted or "Array to string conversion". |
| **Array to string conversion** | Phase_Wise_Refactor_Plan.md | When `family_contribution[]` (from IGE) reached IES controller, array passed to string column → SQL error. |

Phase 1A fixed these by applying `$request->only($fillable)` at each controller. That fix works **at the controller level**: each controller defensively scopes input before use.

### Why Controller-Level `$fillable` Is Insufficient at Orchestration Level

| Limitation | Consequence |
|------------|-------------|
| **Orchestrator passes full request** | ProjectController (or equivalent) invokes sub-controllers and passes the entire `$request`. Each sub-controller receives all form data. Scoping happens only inside each controller. |
| **No structural guarantee** | If a controller forgets to scope (e.g. uses `$request->validated()` without scoped keys, or a new controller is added without the pattern), it receives unrelated data. Prevention relies on developer discipline, not structure. |
| **Implicit ownership** | Ownership is implicit in each controller's `$fillable` array. There is no single place that declares "IES Education owns these keys; Budget owns those." The orchestrator cannot route data by section. |
| **Duplication and drift** | Each controller maintains its own list of allowed keys. Adding a field requires updating the controller; no orchestration-level registry exists to drive routing. |

FormSection / ownedKeys moves the scoping **up** to the orchestration level. The orchestrator passes only `$request->only($section->ownedKeys())` to each section's controller. Controllers then receive pre-scoped data; cross-module pollution becomes structurally impossible.

### Class of Bugs This Prevents

| Bug Class | Prevention |
|-----------|------------|
| **Mass assignment from unrelated sections** | Orchestrator passes only owned keys. Unrelated data never reaches the controller. |
| **Cross-module field collisions** | Each section declares explicit ownership. No ambiguous keys. |
| **Implicit ownership assumptions** | Ownership is declared and finite. New sections must declare ownedKeys. |
| **Controllers receiving full request** | Orchestrator scopes before dispatch. Structural guarantee. |

---

## 2. Responsibilities and Non-Responsibilities

### Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Declare explicit ownership** | Each form section declares which request keys it owns via `ownedKeys(): array`. |
| **Prevent unrelated data from reaching controllers** | Orchestrator uses ownedKeys to pass only relevant data; no cross-section pollution. |
| **Enable safe orchestration** | Provides a contract the orchestrator can use to route data correctly. |

### Non-Responsibilities

| Non-Responsibility | Reason |
|-------------------|--------|
| **Request shape changes** | Does not change form field names, nesting, or request structure. Keys in ownedKeys are existing flat names. |
| **Form field namespacing** | Does not introduce `ies_education[family_contribution]` or similar. Works with current flat keys. |
| **ProjectController rewrite** | Does not mandate refactoring the orchestration flow. Design enables adoption; does not require it. |
| **Replacing FormRequests** | FormRequests and validation rules remain. FormSection provides key ownership; validation logic unchanged. |
| **Attachment handling** | Attachment controllers and file fields are out of scope. ProjectAttachmentHandler (Phase 2.2) addresses that domain. |
| **Validation** | Does not validate data. Controllers and FormRequests continue to validate. FormSection only declares which keys a section owns. |

---

## 3. Core Concept: FormSection

### What FormSection Represents

A **FormSection** represents one logical project form section: a coherent unit of data that maps to a sub-controller and a set of models. Examples:

- IES Education Background
- IES Personal Info
- IIES Attachments
- IAH Documents
- Budget (phases, budget rows)
- ILP Attached Documents

Each section corresponds to a step or partial in the multi-step project form and to a controller (or controller method) that handles it.

### ownedKeys

Each section declares **ownedKeys**: a finite array of string keys that it owns. These keys correspond to existing flat request field names (e.g. `previous_class`, `amount_sanctioned`, `family_contribution`, `this_phase`, `next_phase`). No key is owned by more than one section. Ownership is explicit and finite.

### Design Invariants

- **Explicit**: Every key a section uses must be in ownedKeys. No implicit ownership.
- **Finite**: ownedKeys returns a closed set. No "all keys except X."
- **Non-overlapping**: Keys do not overlap across sections. A collision (e.g. `family_contribution` in two sections) indicates a design problem: either namespacing (out of scope) or a single owner must be chosen.

---

## 4. ownedKeys Contract (Pseudocode Only)

### Method Signature

```
ownedKeys(): array
```

Returns an array of string keys. Each key is a request field name the section owns.

### Key Semantics

- Keys correspond to **existing flat request field names** (e.g. `particular`, `rate_quantity`, `this_phase`, `next_phase`).
- Keys include those used for `fill()` and those used for nested structures (e.g. `phases` for budget) as appropriate for the section's data shape. Exact key set is section-specific.
- **ownedKeys ≈ fillable** in spirit: the keys a section is allowed to read and persist. The difference is level: fillable is model/controller-level; ownedKeys is orchestration-level, so the orchestrator can route before the controller runs.

### Example (Conceptual)

For an IES Education section, ownedKeys might return: `['previous_class', 'amount_sanctioned', 'amount_utilized', 'scholarship_previous_year', 'academic_performance', 'present_class', 'expected_scholarship', 'family_contribution', 'reason_no_support']`.

For a Budget section, ownedKeys might return keys that allow access to the `phases` structure and its nested budget fields.

No implementation is specified; the contract is the return type and semantics.

---

## 5. Orchestration Model (Conceptual)

### How an Orchestrator Uses ownedKeys

When the orchestrator (existing ProjectController or a future coordination layer) needs to invoke a sub-controller for a given section:

1. Obtain the FormSection instance for that section.
2. Call `$section->ownedKeys()` to get the keys the section owns.
3. Extract from the request only those keys: `$request->only($section->ownedKeys())`.
4. Pass the scoped data to the sub-controller (e.g. as a modified request, or as a data array, depending on adoption approach).

The sub-controller then receives only data for its section. Data from other sections (IES, IIES, Budget, etc.) is never passed.

### Routing Semantics

Conceptually: `$request->only($section->ownedKeys())` ensures that only keys the section declares as owned are present in the data passed downstream. Keys from other sections are excluded. This prevents cross-module pollution.

### Why This Prevents Cross-Module Pollution

- **Before**: Orchestrator passes full `$request`. IES controller receives IGE's `family_contribution`, Budget's `this_phase`, etc. Controller must defensively scope.
- **After**: Orchestrator passes `$request->only($section->ownedKeys())`. IES controller receives only IES keys. IGE and Budget data never reach it. Pollution is impossible at the orchestration boundary.

### No ProjectController Redesign

This design does **not** require rewriting ProjectController's store/update flow. The design defines a contract (FormSection, ownedKeys) and a usage pattern (pass only owned keys). Adoption can be incremental: the orchestrator may adopt ownedKeys when calling one section at a time, or a facade may wrap the request before passing it to existing controllers. The design is additive; it does not mandate a specific orchestration architecture.

---

## 6. Relationship to Phase 1A

### Phase 1A: Local Fix

Phase 1A fixed the problem **at each controller**:

- Each controller uses `$request->only($fillable)` (or equivalent) before `fill()`.
- Each FormRequest or controller defines which keys it accepts.
- Scoping happens inside the controller. The orchestrator still passes the full request.

Phase 1A made each controller defensive. It works. But it relies on every controller consistently applying the pattern.

### Phase 2.4: Structural Fix

Phase 2.4 fixes the problem **at the orchestration level**:

- FormSection declares ownedKeys per section.
- The orchestrator passes only `$request->only($section->ownedKeys())` to each section.
- Scoping happens before the controller runs. Controllers receive pre-scoped data.

### FormSection Formalizes Phase 1A's Implicit Contract

Phase 1A implicitly established that each controller "owns" a set of keys (its fillable set). FormSection makes that ownership **explicit and declarative**. The orchestrator can use it. ownedKeys is, in effect, the orchestration-level analogue of fillable: it formalizes what Phase 1A controllers already assume—that they should only see their own keys.

---

## 7. Explicit Anti-Patterns Replaced

| Anti-Pattern | Replacement |
|--------------|-------------|
| **`$request->all()` passed to sub-controllers** | Orchestrator passes `$request->only($section->ownedKeys())`. Only owned keys reach the controller. |
| **Controllers receiving unrelated section data** | Pre-scoping at orchestration level. Unrelated data never dispatched. |
| **Implicit ownership assumptions** | Explicit `ownedKeys()`. No implicit "this controller handles these keys." |
| **Field collision bugs** | Each key owned by one section. No ambiguous ownership. |

---

## 8. Adoption Strategy

### Design-First

Design is complete before any implementation. Interfaces and contracts are stable.

### Pilot on One Module

Adopt FormSection for a single module first (e.g. IES Education Background or Budget). Verify that:

- The section declares ownedKeys.
- The orchestrator (or a thin adapter) passes only owned keys.
- Behavior is unchanged; no regressions.

### Incremental Rollout

After pilot success, adopt for other sections one at a time. No big-bang refactor. Each section gets a FormSection implementation and the orchestrator is updated to use it for that section's dispatch.

### Backward Compatibility Guaranteed

- Controllers that already use `$request->only($fillable)` continue to work. Receiving pre-scoped data is a subset of what they already accept.
- FormRequests and validation unchanged. ownedKeys does not replace validation.
- Request shape unchanged. No form or DOM changes.

---

## 9. What This Does NOT Solve

| Concern | Clarification |
|---------|---------------|
| **Form namespacing** | Does not change field names (e.g. no `ies_education[family_contribution]`). Works with current flat keys. |
| **DOM changes** | Does not alter partials, form structure, or conditional inclusion. |
| **Orchestration rewrite** | Does not require refactoring ProjectController's store/update flow. Design enables adoption; does not mandate architecture change. |
| **Attachment handling** | File uploads and attachment controllers are ProjectAttachmentHandler domain. |
| **Report/export handling** | Does not apply to report flows, monthly/quarterly controllers, or ExportController. |
| **Validation** | Does not validate. FormRequests and rules remain. |

---

## 10. Exit Criteria (Design Phase)

- [ ] Design document approved
- [ ] FormSection and ownedKeys interfaces stable
- [ ] Orchestration usage pattern documented
- [ ] No production code written
- [ ] Implementation deferred to a separate phase

---

Design complete — implementation deferred

**Date**: 2026-02-08

---

## GeneralInfo Address Ownership (Phase 2.4)

### Context

Phase 2.4 resolves the cross-section address collision where `full_address` was used by both:
- **General Information** (Basic Information) — project/society address → `projects.full_address`
- **IES Personal Information** (Beneficiary) — beneficiary address → `project_IES_personal_info.full_address`

When both sections were in the same form (e.g. Individual - Ongoing Educational support), the last DOM occurrence overwrote the other in the submitted request, causing the project address to be replaced by the beneficiary's address.

### Phase 2.4 Decision

- **GeneralInfo** owns `gi_full_address` — form field name for the project/society address
- **IESPersonalInfo** keeps `full_address` — form field name for the beneficiary's address
- GeneralInfoController maps `gi_full_address` → `projects.full_address` at persistence time

### Before

| Section | Form field | DB column | Issue |
|---------|------------|-----------|-------|
| General Information | `full_address` | `projects.full_address` | Collision with IES |
| IES Personal Info | `full_address` | `project_IES_personal_info.full_address` | Last in DOM overwrote General Info |

### After

| Section | Form field | DB column | Owner |
|---------|------------|-----------|-------|
| General Information | `gi_full_address` | `projects.full_address` | GeneralInfo |
| IES Personal Info | `full_address` | `project_IES_personal_info.full_address` | IESPersonalInfo |

### Implementation Scope (Phase 2.4)

- **Changed**: General Information Blade partials (create, edit), GeneralInfoController (store/update), FormRequests (StoreProjectRequest, UpdateProjectRequest, StoreGeneralInfoRequest, UpdateGeneralInfoRequest)
- **Unchanged**: Database schema, IES/ILP/IAH beneficiary forms, Project model, Show/Export logic

### Verification

- Creating IOES project: project address (gi_full_address) persists correctly; beneficiary address (full_address) persists separately
- Editing IOES project: both addresses preserved
- Existing data: no migration; `projects.full_address` column unchanged
