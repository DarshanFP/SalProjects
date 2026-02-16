# IOES-0028 Data Loss Investigation Report

## 1. Log Findings

- **Log file provided**: `/mnt/data/laravel-3.log`
- **Result**: **Not accessible from this workspace** (file not found when attempting to open).
- **Impact**: No production stack traces / SQL errors could be extracted here.
- **Documentation note**: This report therefore relies on **static code-path tracing** and identifying **structural “drop points”** where IOES (IES) request data can be ignored, rolled back, or never persisted.

## 2. Route & Controller Analysis

### Route

The edit/update form posts to `projects.update`:

```13:16:resources/views/projects/Oldprojects/edit.blade.php
<form id="editProjectForm" action="{{ route('projects.update', $project->project_id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
```

The route definition:

```420:433:routes/web.php
Route::prefix('executor/projects')->group(function () {
    // ...
    Route::put('{project_id}/update', [ProjectController::class, 'update'])->name('projects.update');
    // ...
});
```

### Controller entrypoint

`ProjectController@update(UpdateProjectRequest $request, $project_id)` is the end-to-end update orchestrator. It:

- Starts an explicit transaction via `DB::beginTransaction()`
- Updates general info via `GeneralInfoController@update`
- For IOES/IES projects, calls the IES sub-controllers (personal info, family members, etc.)
- Commits and redirects with success even when sub-controllers return JSON errors (details below)

```1371:1533:app/Http/Controllers/Projects/ProjectController.php
public function update(UpdateProjectRequest $request, $project_id)
{
    $request->merge(['phases' => $request->input('phases', [])]);
    DB::beginTransaction();
    try {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $project = (new GeneralInfoController())->update($request, $project->project_id);
        if (!in_array($project->project_type, ProjectType::getIndividualTypes())) {
            (new KeyInformationController())->update($request, $project);
        }
        if (ProjectType::isInstitutional($project->project_type)) {
            $this->logicalFrameworkController->update($request, $project->project_id);
            $this->sustainabilityController->update($request, $project->project_id);
            (new BudgetController())->update($request, $project);
            if ($request->hasFile('file')) {
                (new AttachmentController())->update($request, $project->project_id);
            }
        }
        switch ($project->project_type) {
            case ProjectType::INDIVIDUAL_ONGOING_EDUCATIONAL:
                $this->iesPersonalInfoController->update($request, $project->project_id);
                $this->iesFamilyWorkingMembersController->update($request, $project->project_id);
                $this->iesImmediateFamilyDetailsController->update($request, $project->project_id);
                $this->iesEducationBackgroundController->update($request, $project->project_id);
                $this->iesExpensesController->update($request, $project->project_id);
                $this->iesAttachmentsController->update($request, $project->project_id);
                break;
            // ... other types ...
        }
        DB::commit();
        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    } catch (...) {
        DB::rollBack();
        // ...
    }
}
```

## 3. Request Validation Analysis

### Update request object

The update route uses `UpdateProjectRequest`, which validates **only the general project fields** (project title/type/society/budget fields, etc.).

Notably:
- It does **not** validate any IES/IOES section fields (beneficiary fields like `bname`, `age`, `previous_class`, etc.).
- This means the IES section controllers are operating on **unvalidated input** when invoked via `ProjectController@update`.

```34:92:app/Http/Requests/Projects/UpdateProjectRequest.php
public function rules(): array
{
    return [
        'project_type' => 'required|string|max:255',
        'project_title' => 'nullable|string|max:255',
        // ...
        'gi_full_address' => 'nullable|string|max:255',
        // ...
        'problem_tree_image' => 'nullable|file|image|mimes:jpeg,jpg,png|max:7168',
    ];
}
```

### A critical “drop by design” behavior in GeneralInfoController

`GeneralInfoController@update` uses `$validated = $request->validated();` and updates only those keys.

This is correct for isolating general info, but it implies:
- Any IOES/IES fields submitted at the same time are **ignored by GeneralInfoController** (they are not in validated data)
- IOES/IES persistence must therefore happen only via the type sub-controllers called after general info update

```139:219:app/Http/Controllers/Projects/GeneralInfoController.php
public function update(FormRequest $request, $project_id)
{
    $validated = $request->validated();
    // ...
    if (array_key_exists('gi_full_address', $validated)) {
        $validated['full_address'] = $validated['gi_full_address'];
        unset($validated['gi_full_address']);
    }
    $project->update($validated);
    return $project;
}
```

### IOES naming clarification (important)

IOES is not a stored `project_type` value; it’s an **ID prefix** for the human-readable type:

```17:22:app/Constants/ProjectType.php
// Individual Project Types
const INDIVIDUAL_ONGOING_EDUCATIONAL = 'Individual - Ongoing Educational support';
```

and `Project::generateProjectId()` maps that type to the `IOES-####` prefix:

```395:420:app/Models/OldProjects/Project.php
private function generateProjectId()
{
    $initialsMap = [
        // ...
        'Individual - Ongoing Educational support' => 'IOES',
        // ...
    ];
    // ...
    return $initials . '-' . $sequenceNumberPadded;
}
```

Implication: Any backend branch checks must compare against **`Individual - Ongoing Educational support`** (or the constant), not `'ioes'`.

## 4. Frontend Payload Analysis

### Edit form is a single multipart form

The edit view includes multiple partials under one form (`enctype="multipart/form-data"`), so the update request contains:
- General project fields (including `gi_full_address`)
- IES fields (beneficiary section)
- IES repeating arrays (`member_name[]`, `particulars[]`, file arrays like `aadhar_card[]`)

```13:16:resources/views/projects/Oldprojects/edit.blade.php
<form id="editProjectForm" action="{{ route('projects.update', $project->project_id) }}" method="POST" enctype="multipart/form-data">
```

### Key mismatch/collision checks

- **Project address vs beneficiary address**: explicitly separated.
  - General info uses `gi_full_address` (mapped to `projects.full_address`).
  - IES personal info uses `full_address` (beneficiary address, stored in `project_IES_personal_info.full_address`).

```216:220:resources/views/projects/partials/Edit/general_info.blade.php
<textarea name="gi_full_address" id="gi_full_address" class="form-control select-input sustainability-textarea" rows="2"
         >{{ old('gi_full_address', $project->full_address ?? $user->address) }}</textarea>
```

```52:55:resources/views/projects/partials/Edit/IES/personal_info.blade.php
<label>Full Address:</label>
<textarea name="full_address" class="form-control auto-resize-textarea" rows="3">{{ old('full_address', $personalInfo->full_address) }}</textarea>
```

So **address collision is not the likely drop point** for IOES.

### Frontend JS submission logic

This repo’s `resources/js` appears minimal (axios bootstrap only). The edit form primarily posts via normal form submit (Blade + DOM handlers).

No evidence in `resources/js` was found of pre-submit filtering (e.g., removing empty keys) that would selectively drop IOES fields.

## 5. Service Layer Analysis

### No “IOES service” layer exists

Persistence for IOES is orchestrated directly inside controllers (monolithic `ProjectController@update` + type-specific controllers). There is no distinct `ioes` service branch found.

### Central data-scoping helper

Several IOES/IES controllers use `FormDataExtractor`, which scopes request input to model fillables:

```26:50:app/Services/FormDataExtractor.php
public static function forFillable(Request $request, array $fillable, array $normalizers = []): array
{
    $data = $request->only($fillable);
    if (empty($normalizers)) {
        return ArrayToScalarNormalizer::forFillable($data, $fillable);
    }
    return static::normalize($data, $fillable, $normalizers);
}
```

**Drop risk**: any frontend key that does not exactly match model fillables will be ignored silently.

In the inspected IES edit partials, field names **do** match the fillable attributes of the corresponding models (personal info, education background, immediate family details).

## 6. Model Analysis

### Project model (general info)

`App\Models\OldProjects\Project` has appropriate `$fillable` for the general project columns, including `full_address` (project address).

```259:301:app/Models/OldProjects/Project.php
protected $fillable = [
    'user_id',
    'project_id',
    'project_type',
    'project_title',
    // ...
    'full_address',
    // ...
    'problem_tree_file_path',
    'status',
    'predecessor_project_id',
    'completed_at',
    'completion_notes'
];
```

### IOES (IES) models

Example: beneficiary personal info model’s fillables match the edit form names:

```68:88:app/Models/OldProjects/IES/ProjectIESPersonalInfo.php
protected $fillable = [
    'IES_personal_id',
    'project_id',
    'bname',
    'age',
    'gender',
    'dob',
    'email',
    'contact',
    'aadhar',
    'full_address',
    'father_name',
    'mother_name',
    'mother_tongue',
    'current_studies',
    'bcaste',
    'father_occupation',
    'father_income',
    'mother_occupation',
    'mother_income'
];
```

So **mass-assignment protection is unlikely** to be dropping IOES fields in the IES sub-models (at least for the inspected tables).

## 7. Transaction Analysis

### Outer transaction

`ProjectController@update` uses a single transaction boundary:

- `DB::beginTransaction()` at the start
- `DB::commit()` at the end
- `DB::rollBack()` in catch blocks

### Nested transactions + swallowed failures (high-probability “drop point”)

Many IES sub-controllers start their own transactions and **catch exceptions**, returning JSON errors instead of throwing.

Example: `IESPersonalInfoController@store` begins a transaction, rolls back on exception, and returns `500` JSON:

```31:66:app/Http/Controllers/Projects/IES/IESPersonalInfoController.php
DB::beginTransaction();
try {
    $personalInfo->fill($data);
    $personalInfo->save();
    DB::commit();
    return response()->json(['message' => 'IES Personal Info saved successfully.'], 200);
} catch (\Exception $e) {
    DB::rollBack();
    return response()->json(['error' => 'Failed to save IES Personal Info.'], 500);
}
```

**Critical behavior**: `ProjectController@update` does not inspect these JSON responses and does not rethrow.
So if a sub-controller fails and returns JSON 500/422:
- its data is rolled back / not written
- but the outer controller continues, commits, and redirects with **“Project updated successfully.”**

This is the strongest structural explanation for “some IOES data not being saved” without obvious user-facing errors.

### Boolean “unchecked” drop behavior (medium probability)

For checkbox-backed boolean columns, when a checkbox is unchecked, HTML does not submit the key at all.

Controllers using `FormDataExtractor::forFillable($request, $fillable)` will not see missing keys and thus will not set them to `0`.

In `IESImmediateFamilyDetailsController`, there is a safeguard for NOT NULL booleans only when the model field is null/empty:

```39:45:app/Http/Controllers/Projects/IES/IESImmediateFamilyDetailsController.php
foreach (self::NOT_NULL_BOOLEAN_FIELDS as $field) {
    $val = $familyDetails->$field;
    if ($val === null || $val === '') {
        $familyDetails->$field = 0;
    }
}
```

But if a field was previously `1` and the user unchecks it:
- the key is absent
- `fill()` doesn’t change it
- the post-fill normalization does not force it to 0 (because it’s not null)
- resulting in “update didn’t save” for unchecked booleans

## 8. Database Schema Analysis

### Existence of IOES/IES tables

The repo includes migrations for IES tables, including:
- `project_IES_personal_info`
- `project_IES_immediate_family_details`
- `project_IES_educational_background`
- `project_IES_family_working_members`
- `project_IES_expenses` and `project_IES_expense_details`
- `project_IES_attachments` + newer `project_IES_attachment_files`

Examples (migration filenames found):

- `database/migrations/2024_10_24_010908_create_project_i_e_s_personal_infos_table.php`
- `database/migrations/2024_10_24_010909_create_project_i_e_s_immediate_family_details_table.php`
- `database/migrations/2024_10_24_010909_create_project_i_e_s_education_backgrounds_table.php`
- `database/migrations/2024_10_24_010909_create_project_i_e_s_family_working_members_table.php`
- `database/migrations/2024_10_24_010909_create_project_i_e_s_expenses_table.php`
- `database/migrations/2025_01_05_115003_create_project__i_e_s_expense_details_table.php`
- `database/migrations/2024_10_24_010909_create_project_i_e_s_attachments_table.php`
- `database/migrations/2026_01_08_134425_create_project_ies_attachment_files_table.php`

No IOES-specific tables (named `project_IOES_*`) were found; IOES uses IES tables keyed by `project_id` like `IOES-0028`.

## 9. Structural Drift Comparison (IOES/IES vs IIES)

### IIES uses prefixed keys + dedicated FormRequests

In `app/Http/Requests/Projects/IIES/*`, fields are consistently prefixed (`iies_bname`, `iies_full_address`, etc.) with dedicated validation and normalizers.

In contrast, IES edit forms use unprefixed keys (`bname`, `full_address`).

This is not inherently wrong, but it creates drift in:
- validation coverage (IIES has granular request classes; IES update path uses `UpdateProjectRequest` which does not validate IES keys)
- normalization/placeholder handling (IIES requests normalize arrays/numerics; IES controllers often use direct `$request->only()` and `ArrayToScalarNormalizer` in some places only)

### Silent failure path is the major drift

Type controllers frequently return JSON responses and catch exceptions, while `ProjectController@update` treats them as fire-and-forget calls.
This structural pattern makes IOES/IES saves especially prone to “partial save with success message”.

## 10. Root Cause Hypothesis

Ranked by probability, based on code structure:

1. **High**: **Sub-controller failures are swallowed** during `ProjectController@update`.
   - IES sub-controllers frequently `catch` + `DB::rollBack()` + `return response()->json(...)`
   - `ProjectController@update` does not check the response and still commits + redirects success
   - Net effect: “some IOES-specific tables not updated” with no visible error

2. **Medium**: **Unchecked checkboxes cannot be persisted as false** for IOES immediate family booleans.
   - Missing keys on submit means prior `1` values remain unchanged
   - Users perceive this as “not saved”

3. **Medium**: **Row-level filters treat `0` as empty** in repeating sections.
   - Example: family working members only inserted when `!empty($monthlyIncome)` which excludes 0
   - Expenses details similarly skip empty values; if “0” is valid, it can be dropped

4. **Low–Medium**: **Attachment handler can fail per-field and return 422 JSON**, but the outer update still succeeds.
   - This manifests as “attachments not saved” rather than scalar fields, but is still IOES-specific data loss.

## 11. Risk Assessment

- **User-facing data integrity risk**: High. UI can show “updated successfully” while one or more IOES sections failed to persist.
- **Operational risk**: Medium–High. Without accessible logs, production incidents may appear intermittent and hard to reproduce.
- **Data consistency risk**: Medium. Partial updates can leave dependent tables out-of-sync (e.g., expenses header saved but missing details, or attachments files saved but no DB rows if failure occurs mid-way and is rolled back).

## 12. Confidence Level (Low / Medium / High)

**Medium**

Reasoning:
- The end-to-end controller routing and IOES/IES mapping is explicit and confirmed in code.
- The “swallowed failure + commit success” pattern is visible and is a strong general explanation for “data not saved.”
- However, the specific failing exception/SQL error for `ioes-0028` could not be validated because the provided production log file was not accessible from this environment.

