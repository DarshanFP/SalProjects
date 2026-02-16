# Production Forensic Review – 10-02-2026

## Executive Summary

**Log scope analyzed:** `storage/logs/laravel-3.log`, date filter **2026-02-10**.

- **Total log entries (2026-02-10):** 4,908
- **ERROR entries:** 15
- **Distinct error signatures (normalized):** 4

### Highest-impact findings

- **Critical (Data Integrity / Runtime Failure):** IOES project updates can attempt to write `projects.overall_project_budget = NULL`, triggering `SQLSTATE[23000] Integrity constraint violation: 1048` and aborting update flows. This is reachable via **“Save Changes” (draft)** submission, which **removes HTML `required`** constraints and bypasses HTML5 validity checks before submitting.
- **High (Runtime Failure):** PDF export for IGE projects fails with **`Undefined variable $IGEbudget`** because the PDF template includes the partial without passing the required variable (show view passes it; PDF does not). This failure is repeatable and affects at least `IOGEP-0011` and `IOGEP-0014`.
- **High (Operational / User Experience):** Password reset email delivery fails with SMTP `554 5.7.1 Disabled by user from hPanel`, producing hard errors during reset-link sending.
- **Critical (Security / Data Integrity):** `User` model uses `protected $guarded = [];` (all attributes mass-assignable). Multiple controllers update sensitive fields (`role`, `status`, `password`) from request input; in the presence of `guarded=[]`, this becomes a systemic elevation/integrity risk.

---

## Section 1 – Log Error Inventory

### 1.1 Normalized inventory table (grouped by exception/message/root location)

| Error ID | Exception | File | Line | Frequency | Category |
|---|---|---:|---:|---:|---|
| **ERR-001** | ViewException / ErrorException | `resources/views/projects/partials/Show/IGE/budget.blade.php` | 7 | **10 log lines** (≈5 attempts) | Runtime (Undefined variable) |
| **ERR-002** | SQLSTATE[23000] | `app/Http/Controllers/Projects/GeneralInfoController.php` → `ProjectController@update` | 209 → 1400 | **3** | SQL / Integrity constraint |
| **ERR-003** | `Symfony\Component\Mailer\Exception\UnexpectedResponseException` | `app/Http/Controllers/Auth/PasswordResetLinkController.php` | 35 | **1** | External dependency / Mail |
| **ERR-004** | Validation failure (password min length) | `app/Http/Controllers/ProvincialController.php` | (validation) | **1** | Validation |

### 1.2 Raw error signatures (as logged)

On 2026-02-10 the log contains **15** `production.ERROR` entries. These appear as:

- Two lines per IGE PDF attempt:
  - `ExportController@downloadPdf - Error ... Undefined variable $IGEbudget ...`
  - `Undefined variable $IGEbudget ... (Spatie\LaravelIgnition\Exceptions\ViewException ...)`
- Three SQLSTATE errors:
  - `ProjectController@update - Error during update ... Column 'overall_project_budget' cannot be null`
- One SMTP mailer error (password reset link)
- One user creation validation error (password too short)

---

## Section 2 – Detailed Error Breakdown

### Error ID: ERR-001

- **Exception:** `Spatie\LaravelIgnition\Exceptions\ViewException` (wrapped `ErrorException`)
- **Location:** `resources/views/projects/partials/Show/IGE/budget.blade.php:7`
- **Frequency:** 10 log lines (two per attempt), spanning:
  - `2026-02-10 11:16:16` → `11:17:42` for `IOGEP-0011` (4 attempts)
  - `2026-02-10 12:23:49` for `IOGEP-0014` (1 attempt)
- **Category:** Runtime (Undefined variable)

#### Log evidence (excerpt)

```2379:2390:storage/logs/laravel-3.log
[2026-02-10 11:16:16] production.ERROR: ExportController@downloadPdf - Error {"error":"Undefined variable $IGEbudget (View: /home/u160871038/domains/salprojects.org/public_html/v1/resources/views/projects/partials/Show/IGE/budget.blade.php) ...","project_id":"IOGEP-0011"}
[2026-02-10 11:16:16] production.ERROR: Undefined variable $IGEbudget {"view":{"view":"/home/.../resources/views/projects/partials/Show/IGE/budget.blade.php","data":[]},"userId":69,"exception":"[object] (Spatie\\LaravelIgnition\\Exceptions\\ViewException(code: 0): Undefined variable $IGEbudget at .../budget.blade.php:7)
```

#### Trigger condition

- User requests PDF download for an IGE project, route `projects/{project_id}/download-pdf` handled by `ExportController@downloadPdf`.

#### Explicit root cause

The budget partial **requires** a variable named `$IGEbudget`:

```7:8:resources/views/projects/partials/Show/IGE/budget.blade.php
@if($IGEbudget && $IGEbudget->isNotEmpty())
<div class="table-responsive">
```

The PDF template includes this partial **without passing the variable**:

```664:670:resources/views/projects/Oldprojects/pdf.blade.php
<div class="card-body">
    @include('projects.partials.Show.IGE.budget')
</div>
```

Whereas the standard show view includes it correctly (passing `IGEbudget`):

```228:230:resources/views/projects/Oldprojects/show.blade.php
@include('projects.partials.Show.IGE.budget', ['IGEbudget' => $budget ?? collect()])
```

So the **PDF render path** violates the view-contract used by the **show render path**.

#### Why not prevented

- There is no contract enforcement validating that every included project-type partial receives its required variables.
- PDF template uses `@include(...)` without variable binding.

#### Severity

- **High**

#### Affected modules

- Project PDF export (`ExportController@downloadPdf`, `resources/views/projects/Oldprojects/pdf.blade.php`)
- IGE show partials (`resources/views/projects/partials/Show/IGE/*`)

---

### Error ID: ERR-002

- **Exception:** `SQLSTATE[23000]: Integrity constraint violation: 1048`
- **Location (stack root):**
  - `app/Http/Controllers/Projects/GeneralInfoController.php:209` (`$project->update($validated)`)
  - Called from `app/Http/Controllers/Projects/ProjectController.php:1400`
- **Frequency:** 3 (projects affected: `IOES-0011`, `IOES-0028` twice)
- **Category:** SQL / Integrity constraint

#### Log evidence (excerpt)

```2373:2389:storage/logs/laravel-3.log
[2026-02-10 10:27:47] production.ERROR: ProjectController@update - Error during update {"project_id":"IOES-0011","error":"SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'overall_project_budget' cannot be null ...","trace":"... #8 .../app/Http/Controllers/Projects/GeneralInfoController.php(209) ... #9 .../ProjectController.php(1400) ..."}
```

```5151:5167:storage/logs/laravel-3.log
[2026-02-10 11:58:08] production.ERROR: ProjectController@update - Error during update {"project_id":"IOES-0028","error":"SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'overall_project_budget' cannot be null ..."}
```

```5811:5827:storage/logs/laravel-3.log
[2026-02-10 12:25:31] production.ERROR: ProjectController@update - Error during update {"project_id":"IOES-0028","error":"SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'overall_project_budget' cannot be null ..."}
```

#### Trigger condition

When updating an IOES (IES) project, the request arrives at:

```1371:1402:app/Http/Controllers/Projects/ProjectController.php
public function update(UpdateProjectRequest $request, $project_id)
{
    DB::beginTransaction();
    try {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $project = (new GeneralInfoController())->update($request, $project->project_id);
        // ...
```

The failure occurs inside `GeneralInfoController@update` when writing the validated data:

```139:210:app/Http/Controllers/Projects/GeneralInfoController.php
public function update(FormRequest $request, $project_id)
{
    $validated = $request->validated();
    // ...
    $project->update($validated);
    return $project;
}
```

#### Explicit root cause

`projects.overall_project_budget` is **NOT NULL** and has a default:

```31:35:database/migrations/2024_07_20_085634_create_projects_table.php
$table->decimal('overall_project_budget', 10, 2)->default(0.00);
```

But the update request validation allows `overall_project_budget` to be nullable:

```52:54:app/Http/Requests/Projects/UpdateProjectRequest.php
'overall_project_budget' => 'nullable|numeric|min:0',
```

So if the form submits **an empty value** for `overall_project_budget`, it passes validation as `null` and then `update()` attempts to write `NULL` into a non-null column.

The log shows this happens during **update**, before any type-specific persistence runs (stack roots at `GeneralInfoController.php(209)` and `ProjectController.php(1400)`).

#### How the frontend enables this state (draft “Save Changes”)

The edit page uses a button (`type="button"`) that performs a “draft save”:

- Removes `required` attributes
- Forces `save_as_draft=1`
- Submits via `editForm.submit()` (bypasses HTML5 constraint validation)

```178:208:resources/views/projects/Oldprojects/edit.blade.php
saveDraftBtn.addEventListener('click', function(e) {
    e.preventDefault();
    const requiredFields = editForm.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        field.removeAttribute('required');
    });
    // Add hidden input to indicate draft save
    // ...
    editForm.submit();
});
```

This bypass path is compatible with the backend rules and can submit an empty `overall_project_budget`, causing the SQL integrity error.

#### Why not prevented

- Backend validation accepts `null` for `overall_project_budget`.
- The draft save deliberately removes client-side `required` constraints and uses programmatic submission.

#### Severity

- **Critical** (hard failure on save; blocks updates; potential user perception of data loss)

#### Affected modules

- Project edit/update (`ProjectController@update`, `GeneralInfoController@update`)
- Edit UI draft-save script (`resources/views/projects/Oldprojects/edit.blade.php`)

---

### Error ID: ERR-003

- **Exception:** `Symfony\Component\Mailer\Exception\UnexpectedResponseException`
- **Location (stack root):** `app/Http/Controllers/Auth/PasswordResetLinkController.php:35`
- **Frequency:** 1
- **Category:** External dependency / Mail delivery

#### Log evidence (excerpt)

```4491:4513:storage/logs/laravel-3.log
[2026-02-10 11:20:15] production.ERROR: Expected response code "250" but got code "554", with message "554 5.7.1 Disabled by user from hPanel". {"exception":"[object] (Symfony\\Component\\Mailer\\Exception\\UnexpectedResponseException(code: 554): ... at .../SmtpTransport.php:338)
...
#15 .../app/Models/User.php(205): App\\Models\\User->notify()
#16 .../vendor/laravel/framework/src/Illuminate/Auth/Passwords/PasswordBroker.php(72): App\\Models\\User->sendPasswordResetNotification()
#19 .../app/Http/Controllers/Auth/PasswordResetLinkController.php(35): Password::sendResetLink()
```

#### Trigger condition

- A user requests a password reset link.

```26:42:app/Http/Controllers/Auth/PasswordResetLinkController.php
$status = Password::sendResetLink(
    $request->only('email')
);
```

#### Explicit root cause

- SMTP server returns `554 5.7.1 Disabled by user from hPanel` during mail send.
- This is not a code defect per se; it’s an **environmental/SMTP account state** defect.

#### Why not prevented

- No application-level fallback channel is configured for password reset.
- No retry/backoff or circuit-breaker is present for mail transport failure.

#### Severity

- **High** (blocks password recovery)

#### Affected modules

- Password reset flow (Auth)
- Notification delivery (`User::sendPasswordResetNotification`)

---

### Error ID: ERR-004

- **Exception:** Validation error (`password` min length)
- **Location (originating handler):** `app/Http/Controllers/ProvincialController.php` (`storeExecutor`)
- **Frequency:** 1
- **Category:** Validation

#### Log evidence (excerpt)

```5073:5076:storage/logs/laravel-3.log
[2026-02-10 11:54:19] production.INFO: Attempting to store a new executor {"name":"St.Ann's Care Centre","email":"stannsho@rediffmail.com","role":"applicant","province":null}
[2026-02-10 11:54:19] production.ERROR: Error storing user {"error":"The password field must be at least 8 characters."}
```

#### Trigger condition

Provincial creates an executor/applicant via `ProvincialController@storeExecutor`, which validates password:

```673:683:app/Http/Controllers/ProvincialController.php
$validatedData = $request->validate([
    // ...
    'password' => 'required|string|min:8|confirmed',
    // ...
]);
```

#### Explicit root cause

- Input password length < 8, so validation fails.

#### Why not prevented

- This is expected behavior; the only “forensic” concern is that the controller logs it as `production.ERROR` (rather than a structured validation response), which inflates “error” noise in production logs.

#### Severity

- **Low**

#### Affected modules

- Provincial user management

---

## Section 3 – Structural Codebase Risks

### 3.1 Transaction Risks

#### Risk: Outer orchestrators ignore sub-controller failures (partial commits possible)

Multiple project-type sub-controllers open nested transactions and return JSON errors on failure; orchestration code often **does not check return values**, and catch blocks only handle thrown exceptions.

Example (update orchestrator commits if no exception is thrown):

```1536:1556:app/Http/Controllers/Projects/ProjectController.php
} catch (\Exception $e) {
    DB::rollBack();
    Log::error('ProjectController@update - Error during update', [ /*...*/ ]);
    return redirect()->back()->withErrors([/*...*/]);
}
```

If a sub-controller fails but returns a JSON response (no exception), the orchestrator still reaches `DB::commit()`.

#### Risk: “Savepoint semantics” from nested transactions

Many controllers use `DB::beginTransaction()` even when called inside an outer transaction. In Laravel, this becomes a savepoint; rollback affects only the inner unit.

This pattern is present across IES/IIES/ILP/IAH attachments and other type controllers (see codebase scan; numerous controllers follow this).

#### Risk: Helper trait can swallow exceptions

`HandlesErrors::executeInTransaction()` begins a transaction and **returns a response from `handleException()`** rather than rethrowing:

```194:208:app/Traits/HandlesErrors.php
DB::beginTransaction();
try {
    $result = $callback();
    DB::commit();
    return $result;
} catch (Exception $e) {
    return $this->handleException($e, $context, $userMessage, $contextData);
}
```

When used inside higher-level orchestrations, this becomes a “swallow and continue” risk.

---

### 3.2 Mass Assignment Risks

#### Critical: `User` model is fully mass assignable

```78:93:app/Models/User.php
class User extends Authenticatable implements CanResetPassword
{
    // ...
    protected $guarded = [];
```

This makes any controller/service call to `User::create(...)`, `$user->update(...)`, or `$user->fill(...)` extremely sensitive to request filtering and authorization correctness.

Risk dimensions:
- **Security**: privilege escalation if `role`/`status`/`parent_id` can be mass assigned from unsafe inputs.
- **Data integrity**: unintended overwrites of user fields.

---

### 3.3 Relationship Persistence Risks

Common high-risk patterns found in type controllers:
- “Delete all then recreate” loops for `hasMany` rows (risk of partial data loss when failure happens after deletion)
- Per-row `create()` loops where falsy/zero values are skipped due to `empty()` checks (see 3.8)
- Nested relation persistence without a single authoritative transaction boundary

---

### 3.4 Validation Gaps

Patterns observed:
- Many controllers still accept `Illuminate\Http\Request` instead of a dedicated `FormRequest`, fragmenting validation.
- `UpdateProjectRequest` validates general fields but does not validate type-specific payload when the orchestrator calls type sub-controllers during update.

---

### 3.5 Frontend Payload Risks

High-risk patterns:
- **Disabled inputs do not submit.** Multiple create/edit flows disable fields when sections hidden; if re-enable logic fails, required data is silently excluded.
- **Draft save bypasses validation** via programmatic `form.submit()` and removal of `required` fields (directly implicated in ERR-002).
- Potential naming mismatch in some attachment dynamic field builders (separate arrays rather than nested keys).

---

### 3.6 Schema Drift

Concrete drift observed:
- Backend accepts `overall_project_budget = null` during update (validation), but schema defines a non-null decimal with default. (ERR-002)

---

### 3.7 Project Type Drift

#### Missing edit handler branch for NPD

`ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL` exists in constants, and is handled in store/update flows, but `ProjectController@edit` switch lacks a case for it, falling into default:

```1309:1314:app/Http/Controllers/Projects/ProjectController.php
default:
    Log::warning('ProjectController@edit - Unknown project type', [
        'project_type' => $project->project_type
    ]);
    break;
```

This matches production log warnings (not ERROR level) and represents architectural drift between project type definitions and handler completeness.

---

### 3.8 Silent Failure Patterns

#### `empty()` dropping valid zero values

Example: family working member insert logic in IES:

```78:90:app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php
if (!empty($memberName) && !empty($workNature) && !empty($monthlyIncome)) {
    ProjectIESFamilyWorkingMembers::create([ /*...*/ ]);
}
```

If monthly income is `0` (or `"0"`), it is treated as empty and **row is dropped**.

#### `array_filter()` without callback drops falsy values

In admin center processing:
- `array_filter(...)` without callback removes `'0'` and `0` values.

---

## Section 4 – High-Risk Zones (Top 10)

1. **Project orchestration + nested transactions + ignored error responses** (partial saves/data loss risk)
2. **Draft-save bypass of HTML5 validation + nullable backend rules on non-null DB columns** (ERR-002 class)
3. **PDF export include contracts not aligned with show contracts** (ERR-001 class)
4. **`User::$guarded = []`** (systemic mass assignment/security risk)
5. **Delete-then-recreate loops for `hasMany` data** (partial loss on mid-write failure)
6. **`empty()` checks on numeric fields** (drops valid `0` values; silent data loss)
7. **Controllers returning `null`/empty collections in catch blocks** (errors masked; “missing data” appears normal)
8. **Disabled inputs in dynamic forms** (payload exclusion)
9. **`?? 0` and `optional()` patterns masking missing/invalid state** (monitoring/diagnostics degraded)
10. **Project type handler drift** (constants vs actual branches; missing edit/show/export symmetry)

---

## Section 5 – Systemic Pattern Analysis

### Repeated anti-patterns

- **Controller-as-service reuse without contract alignment**: methods designed as HTTP endpoints (returning JSON/Redirect) are invoked internally as “services”, but orchestrators treat them as void.
- **Validation boundaries are inconsistent**: some flows use FormRequests with normalization; others use raw Request + partial validation.
- **View contracts differ between render paths**: show vs pdf export pass different variable names to the same partials.
- **Zero/false data drops**: `empty()` and `array_filter()` default behavior remove valid business values.

### Architectural weaknesses

- No single durable “persistence boundary” for complex multi-table writes across project types.
- Error handling is not standardized across project types (some rethrow, others swallow and return responses).
- No systematic schema-to-validation alignment checks (nullable validation on non-null columns).

---

## Section 6 – Risk Heatmap Summary

- **Critical**
  - **Data Loss / Integrity**: transaction boundary inconsistencies; draft-save can write null to non-null columns
  - **Security**: `User::$guarded=[]` mass assignment exposure
- **High**
  - **Runtime failure**: PDF export view contract mismatch (`$IGEbudget`)
  - **Availability**: mail transport failures block password reset
- **Medium**
  - **Silent data drops**: `empty()`/`array_filter()` on numeric fields; disabled form inputs excluding payload
  - **Project type drift**: missing handler symmetry (edit/show/export)
- **Low**
  - **Expected validation errors logged at ERROR level** (noise)

---

## Section 7 – Recommended Remediation Phases (outline only; not implemented)

### Phase 1: Immediate stability fixes

- Align schema + validation for `overall_project_budget` updates (prevent NULL writes).
- Align PDF export variable passing with show-contract (ensure required variables are passed to type partials).
- Add graceful handling for mail transport failures in password reset flow (user-facing messaging + alerting).

### Phase 2: Data integrity hardening

- Enforce a single transaction boundary per orchestrated save/update and ensure failures abort (no partial commits).
- Replace `empty()`-based numeric checks with explicit `null/''` checks to preserve zero values.
- Reduce “delete then recreate” patterns or ensure they are fully atomic under the outer transaction.

### Phase 3: Structural refactoring (bounded)

- Standardize FormRequests per project type section for both store and update.
- Remove controller-as-service coupling; introduce explicit service interfaces or enforce consistent return/throw behavior.

### Phase 4: Architectural boundary enforcement

- Define and enforce view-contract maps for each render path (show/pdf/export).
- Add automated checks: migrations vs model attributes vs request rules (nullable / not-null drift).
- Add centralized error taxonomy and downgrade expected validation failures from `ERROR` to structured `WARNING/INFO`.

---

## Appendix – Notes on date scope & methodology

- Parsing was done by splitting log entries by timestamp headers and filtering for `2026-02-10`.
- Errors were normalized by collapsing project-specific identifiers into shared signatures for inventory, while preserving raw excerpts for traceability.

