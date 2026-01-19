# Controller Fixes — TypeHint Mismatch + Data Loss (Implemented)

## Why this work was needed (plain language)

The project create/update flow is orchestrated by a “main controller” that calls many sub-controllers for different project modules.

Two production issues were happening:

- **TypeError crashes** during update/store:
  - Example: *“Argument must be UpdateKeyInformationRequest, UpdateProjectRequest given”*
  - Root cause: sub-controller method signatures were too strict (they required a specific FormRequest), but the orchestrator passed a different request type.

- **Data loss** when saving projects:
  - Example: Project Area, Means of Verification, Objective 2 verification fields disappearing on update
  - Root cause: many sub-controllers used `$request->validated()`. Since the orchestrator’s `StoreProjectRequest`/`UpdateProjectRequest` didn’t validate every JS-generated nested field, those fields were **dropped** from `validated()` output and never persisted.

---

## Core decision (what we changed, conceptually)

### 1) Make sub-controller method signatures flexible

Instead of:

- `public function update(UpdateSomethingRequest $request, ...)`

We moved to:

- `public function update(Illuminate\Foundation\Http\FormRequest $request, ...)` **or** `Illuminate\Http\Request $request`

So the orchestrator can safely call sub-controllers without type errors.

### 2) Capture full request payload for dynamic fields

Instead of:

- `$validated = $request->validated();` (drops non-validated dynamic fields)

We moved to:

- `$validated = $request->all();` (keeps JS-generated nested arrays)
  - Or `$request->input('objectives', [])` when only a specific nested root is needed.

---

## First wave fixes (core “Old Projects” controllers)

These controllers were updated to avoid TypeErrors and be compatible with orchestrator calls:

- `app/Http/Controllers/Projects/GeneralInfoController.php`
- `app/Http/Controllers/Projects/KeyInformationController.php`
- `app/Http/Controllers/Projects/BudgetController.php`
- `app/Http/Controllers/Projects/SustainabilityController.php`
- `app/Http/Controllers/Projects/LogicalFrameworkController.php`
- `app/Http/Controllers/Projects/AttachmentController.php`

Typical pattern used:

- Accept a generic request type
- Use `method_exists($request, 'validated')` when needed
- Prefer `$request->all()` for dynamic fields to prevent data loss

---

## Large-scale rollout (all project modules)

This same fix pattern was applied across many module controllers (RST, IGE, IES, IIES, ILP, IAH, CCI, EduRUT, LDP, CIC, etc.).

Because the list is long, the complete inventory of affected controllers is maintained here:

- `Documentations/REVIEW/3rd Review/TypeHint_Mismatch_Audit.md`
- `Documentations/REVIEW/3rd Review/Data_Loss_Fix_Final_Report.md`

This doc (in the Old Projects Funds folder) is meant as the **human explanation** and **summary**; the REVIEW docs remain the authoritative “full file list”.

---

## How to validate this fix (what to test)

- **Update any project** that has:
  - `project_area[]` multi-select / dynamic fields
  - Objectives/Activities arrays (including Means of Verification)
  - Any rows added via JavaScript
- Confirm after save:
  - The same rows exist
  - No nested sections disappear
- Monitor logs:
  - `storage/logs/laravel.log` should not contain new `TypeError` entries for controller `store()`/`update()`


