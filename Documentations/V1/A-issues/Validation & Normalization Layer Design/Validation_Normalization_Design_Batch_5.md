# Validation & Normalization Layer Design – Batch 5

*Companion to Validation_Normalization_Design.md and Batches 2–4. Covers error handling, logging, JSON responses, model casting, config-driven validation, OldDevelopmentProject flow, and remaining controllers.*

---

## Error Handling & Response Consistency

### Current Behavior

| Scenario | Response | HTTP Code | User Sees |
|----------|----------|-----------|-----------|
| FormRequest validation fails | Redirect with errors | 422 | Validation errors on form |
| Inline `$request->validate()` fails | Redirect with errors | 422 | Same |
| Sub-controller catches exception | `response()->json(['error' => '...'], 500)` | 500 | **Ignored** – caller doesn't check return |
| Sub-controller throws | ProjectController catch | 500 | Redirect with generic "There was an error..." |
| Authorization denied | `abort(403, '...')` | 403 | 403 page |

### Sub-Controller Return Value Problem

When `ProjectController@store` calls `$this->iiesExpensesController->store($request, $project->project_id)`:

1. IIESExpensesController has its own `DB::beginTransaction()` (nested savepoint).
2. On exception, it catches, `DB::rollBack()`, returns `response()->json(['error' => '...'], 500)`.
3. ProjectController **does not check** the return value.
4. ProjectController continues to next sub-controller or `DB::commit()`.
5. User receives redirect with "Project created successfully" even though IIES section failed.
6. **Result:** Partial save – project exists, IIES expenses empty; user unaware.

**Design rule:** Sub-controllers invoked by ProjectController should **throw** on failure, not return error responses. ProjectController's catch block will roll back the outer transaction and redirect with error. Alternatively, ProjectController must check return values and abort/redirect when sub-controller returns an error response.

### Proposed Response Contract

| Operation | Success | Validation Failure | Server Error |
|-----------|---------|-------------------|--------------|
| Full-page form (project store) | Redirect 302 | Redirect 422 with errors | Redirect 500 with generic message |
| AJAX section save (if added) | JSON 200 | JSON 422 with errors object | JSON 500 with message |
| API (if any) | JSON 200 | JSON 422 | JSON 500 |

**Validation error format (JSON):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "iies_total_expenses": ["The iies total expenses field must be a number."]
  }
}
```

---

## Logging Strategy

### Production Log Review Findings

- **Authorization denied** (e.g. "You do not have permission to view this project") was logged at **ERROR**. These are expected security events, not bugs. Should be **WARNING** or **INFO**.
- **Validation failures** – Laravel logs these at default level; ensure they are not ERROR (user error, not system error).
- **DB constraint violations** – Log at ERROR with full context (request data, project_id) for debugging.

### Proposed Log Levels

| Event | Level | Rationale |
|-------|-------|-----------|
| Validation failure | INFO or DEBUG | User input error; not system failure |
| Authorization denied | WARNING | Expected; security audit trail |
| DB constraint violation | ERROR | System/data integrity issue |
| Uncaught exception | ERROR | Bug or unexpected state |
| Normalization applied | DEBUG | Helpful for debugging; avoid noise |
| Successful save | INFO | Audit trail |

### What to Log on Validation Failure

- **Do:** Log that validation failed; optionally log which rules failed (avoid logging full request if it contains PII).
- **Don't:** Log at ERROR level; don't log passwords or sensitive fields.

---

## Model $casts and Normalization

### Current State

| Model | Casts | Relevance |
|-------|-------|-----------|
| Project | completed_at → datetime | No impact on input |
| BudgetCorrectionAudit | decimals → float | Output only |
| Center, Society, Province | is_active → boolean | Output only |
| User | email_verified_at, password | Output/hashing |
| NotificationPreference | booleans | Output |
| ProjectIIESExpenses | **None** | Decimal columns stored as string in PHP; no input coercion |
| ProjectBudget | **None** | Same |
| ProjectCCIStatistics | **None** | Same |

### Role of $casts

- **$casts** affect **output** (when reading from DB) and **mass assignment** (when using `$model->fill($data)`). They do **not** run on raw `$model->column = $value` assignment.
- For `ProjectIIESExpenses`, assigning `$model->iies_total_expenses = ""` would store `""` – $casts do not intercept this.
- **Conclusion:** Normalization must happen **before** assignment. Model $casts are not a substitute for input normalization. They can help when using `fill()` with already-normalized data.

---

## Config-Driven Validation

### Current: attachments.php

- `max_file_size`, `allowed_file_types`, `allowed_types` (legacy).
- Controllers read config for validation logic (file type, size).
- **Gap:** Validation rules (e.g. `mimes:pdf,doc,docx`) are hardcoded in controllers; not derived from config. If config changes, validation may drift.

### Proposed

- **Option A:** Keep config for business rules (what types are allowed); validation rules in FormRequest reference config: `'file' => 'required|file|mimes:' . implode(',', config('attachments.allowed_types.project_attachments.extensions'))`.
- **Option B:** Validation rules stay in FormRequest; config is documentation. Manual sync.
- **Recommendation:** Option A for file types/sizes to avoid drift. Other validation (required, max length) stays in FormRequest.

---

## OldDevelopmentProjectController

### Flow

- **Separate from main ProjectController** – different route, different form.
- **Validation:** Inline `$request->validate()` with **stricter rules** – many fields `required`.
- **Budget:** `phases.*.budget.*` – required for description, rate_quantity, rate_multiplier, rate_duration, this_phase, next_phase. `rate_increase` nullable.
- **No max** for numeric budget columns – same overflow risk as main budget.

### Comparison to Main Project Flow

| Aspect | OldDevelopmentProject | Main Project (ProjectController) |
|--------|----------------------|----------------------------------|
| Route | Separate | projects/store |
| FormRequest | No | StoreProjectRequest |
| Budget validation | required numeric | nullable numeric, min:0 |
| Max bounds | No | No |
| Normalization | None | None |

**Proposed:** Apply same normalization (empty → 0, placeholder → null) and max bounds when refactoring. Consider FormRequest for OldDevelopmentProject.

---

## Remaining Controllers – Quick Reference

### NotificationController

- `updatePreferences` – `$request->validate()` with `sometimes|boolean`, `sometimes|in:...`.
- Uses `$validated`; updates model. **Good pattern.**

### ProfileController

- `updatePassword` – inline validate; Hash::check; update. **Good pattern.**
- `update` – uses ProfileUpdateRequest (FormRequest).

### ProvinceFilterController

- Filter update – validate filter keys; store in session. Low risk.

### ExecutorController

- Dashboard, report list – read-only. No validation for writes.

### Auth Controllers (RegisteredUserController, PasswordResetLinkController, NewPasswordController)

- Use inline `$request->validate()` or FormRequest. Standard Laravel patterns.

---

## Backward Compatibility During Rollout

### Risks

1. **Stricter validation** – New rules may reject previously accepted input (e.g. placeholder `-`).
2. **Normalization changes** – Empty string → 0 may change behavior if some flows expected null.
3. **Response format** – If switching from 500 to 422 for validation, clients expecting 500 may break.

### Mitigation

1. **Phase rollout** – Deploy normalization first (no new validation); then add validation rules.
2. **Log normalization** – When normalizing placeholder → null, log at DEBUG for first weeks to verify no unintended effects.
3. **Feature flag** – Optional: gate new validation per project type or route for gradual rollout.
4. **Client check** – If any AJAX clients consume project APIs, ensure they handle 422 with errors object.

---

## Sub-Controller Transaction Design

### Current Anti-Pattern

- ProjectController starts transaction.
- Sub-controllers (e.g. IIESExpensesController) start their own transaction (nested savepoint).
- Sub-controller catches, rollBack (savepoint), returns JSON 500.
- ProjectController ignores return; commits outer transaction.
- **Result:** Partial save; user sees success.

### Proposed

| Option | Description |
|--------|-------------|
| **A: Sub-controllers throw** | On failure, sub-controller rethrows. ProjectController catch handles. Single transaction. |
| **B: ProjectController checks return** | If sub-controller returns error response, ProjectController throws or returns it. |
| **C: No nested transactions** | Sub-controllers do not beginTransaction; they run in ProjectController's transaction. On exception, ProjectController catches and rolls back. |

**Recommendation:** **Option C** – Sub-controllers should not manage transactions when invoked by ProjectController. Remove `DB::beginTransaction()` and `DB::commit()`/`DB::rollBack()` from sub-controllers when called in project store/update flow. Let ProjectController own the transaction. Sub-controllers throw on failure.

---

## Index of All Batches

| Batch | Focus |
|-------|-------|
| Main | Core design, current state, proposed architecture, 5 model examples |
| Batch 2 | Model examples (IAH, IGE, ILP, etc.); secondary flows; priority matrix; controller inventory |
| Batch 3 | Route/FormRequest strategy; Provincial/General; reports; shared rules; IIES ImmediateFamilyDetails; Excel; nested arrays |
| Batch 4 | DB migration audit; KeyInformation, GeneralInfo, Attachment, Sustainability; frontend contract; phased rollout; testing; glossary |
| Batch 5 | Error handling; logging; JSON responses; model casts; config; OldDevelopmentProject; remaining controllers; backward compatibility; transaction design |

---

## DO NOT

- Implement code
- Refactor controllers
- Add validation rules
- Modify database schema

This document extends the architectural design for planning only.

---

*Document generated: January 31, 2026*  
*Companion to Validation_Normalization_Design.md and Batches 2–4*
