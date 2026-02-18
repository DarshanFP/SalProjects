# IIES Update Flow Interruption — Inspection Report

**Objective:** Determine why `ProjectController@update` for IIES redirects to edit() instead of completing.

---

## Step 1 — ProjectController@update

**Location:** `app/Http/Controllers/Projects/ProjectController.php` (lines 1338–1529)

**Behavior:**

- Has a single success path: `DB::commit()` then `return redirect()->route('projects.index')`.
- For IIES (`ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL`), it calls six sub-controllers in sequence (lines 1482–1488):
  1. `iiesPersonalInfoController->update($request, $project->project_id)`
  2. `iiesFamilyWorkingMembersController->update(...)`
  3. `iiesImmediateFamilyDetailsController->update(...)`
  4. `iiesEducationBackgroundController->update(...)`
  5. `iiesFinancialSupportController->update(...)`
  6. `iiesAttachmentsController->update(...)`
  7. `iiesExpensesController->update(...)`
- **Return values of these calls are ignored** (no assignment, no check). Execution is expected to continue to `DB::commit()`, then `return redirect()->route('projects.index')`.
- **Exception handling:**
  - `catch (\Illuminate\Validation\ValidationException $e)`: `DB::rollBack(); throw $e;` — re-throws so Laravel’s handler will **redirect back** (to the previous URL, i.e. edit) with validation errors.
  - `catch (\Exception $e)`: `DB::rollBack(); return redirect()->back()->withErrors([...])->withInput();` — redirects back to edit with a generic error.

**Conclusion:** The update flow is interrupted when **any** of the IIES sub-controllers **throw** (especially `ValidationException` or any `Exception`). None of them are expected to return a redirect; the “redirect to edit” comes from exception handling.

---

## Step 2 & 3 — Per-controller report

| # | Controller | A) Returns redirect? | B) Returns view? | C) Returns void? | D) Throws ValidationException? |
|---|------------|----------------------|------------------|-------------------|----------------------------------|
| 1 | **IIESPersonalInfoController@update** | No | No | No | No (but can re-throw generic Exception in catch). |
| 2 | **IIESFamilyWorkingMembersController@update** | No | No | No | **Yes** — `$validator->validate()` (line 113). On failure, Laravel throws `ValidationException`; ProjectController re-throws it → redirect back to edit with errors. |
| 3 | **IIESImmediateFamilyDetailsController@update** | No | No | No | No (can re-throw in catch). |
| 4 | **IIESEducationBackgroundController@update** | No | No | No | No (delegates to store(); store can re-throw in catch). |
| 5 | **IIESExpensesController@update** (→ store) | No | No | No | **Yes** — `$validator->validate()` in store() (line 40). Also can throw `HttpResponseException` (403) if budget locked. |
| 6 | **IIESFinancialSupportController@update** | No | No | No | **Yes** — `$validator->validate()` (line 80). |
| 7 | **IIESAttachmentsController@update** | No | No | No | **Yes** — `throw ValidationException::withMessages($result->errorsByField)` (line 190) or in catch (line 207). |

**Return type used by all:** `response()->json([...], 200)`. That return value is **discarded** by ProjectController; it never sends a JSON response. The only way the user is sent back to the edit page is when an **exception** is thrown and handled as above.

---

## Root cause of “redirect to edit”

1. **ValidationException**  
   Thrown by:
   - **IIESFamilyWorkingMembersController**: `$validator->validate()` when family working members validation fails.
   - **IIESFinancialSupportController**: `$validator->validate()` when financial support validation fails.
   - **IIESExpensesController::store**: `$validator->validate()` when estimated expenses validation fails.
   - **IIESAttachmentsController**: explicit `throw ValidationException::withMessages(...)` when attachment validation fails.  
   ProjectController catches and re-throws → Laravel redirects **back** (to edit) with validation errors.

2. **Other exceptions**  
   Any other exception (e.g. from IIESPersonalInfoController, IIESImmediateFamilyDetailsController, or IIESExpensesController’s 403 budget-locked response) is caught by `catch (\Exception $e)` and results in `redirect()->back()->withErrors(['error' => '...'])->withInput()` — again back to edit.

**Critical point:** Sub-controllers do **not** return `redirect()`. They return `response()->json()`, which is ignored. The redirect to edit is **always** a result of an exception (usually validation) and ProjectController’s (or Laravel’s) exception handling.

---

## CRITICAL: Sub-controller contract

**Required:** Sub-controllers used inside `ProjectController@update` must **not** return redirect() and should only perform DB operations (and return void or a value the parent ignores).

**Current state:**

- **No** IIES sub-controller returns `redirect()` or `back()` — **OK**.
- **Problem:** Several sub-controllers run **their own validation** and call `$validator->validate()` or `throw ValidationException`. When that runs in the context of the full project update (single form POST), a validation failure in **one** section aborts the whole update and sends the user back to edit. So the flow is interrupted by **validation** (and its exception), not by a redirect return.

**Recommendation (for future fix, not in this report):** To avoid one section’s validation aborting the whole update, either:

- Move validation into the main `UpdateProjectRequest` (or a single request object) so it runs once before any sub-controller is called, and sub-controllers assume data is valid and only perform DB updates (no `validate()`), or  
- Have sub-controllers return validation errors (e.g. as a structure) instead of throwing, and let ProjectController decide whether to redirect back with errors or continue.

---

## Summary

| Question | Answer |
|----------|--------|
| Does ProjectController call IIES controllers directly? | Yes (lines 1482–1488). |
| Does it expect a return value? | No; return values are ignored. |
| Do any IIES sub-controllers return redirect()? | **No.** |
| Do any return view()? | **No.** |
| Do any return void? | No; all return `response()->json()` (discarded). |
| Do any throw ValidationException? | **Yes** — FamilyWorkingMembers, FinancialSupport, ExpensesController (via store), AttachmentsController. |
| Why does user see edit again? | A sub-controller throws (usually **ValidationException**); ProjectController re-throws or catches and does `redirect()->back()`, so the browser is sent back to the edit page. |
