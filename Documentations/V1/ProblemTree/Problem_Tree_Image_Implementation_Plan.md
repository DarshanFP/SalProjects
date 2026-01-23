# Problem Tree Image – Comprehensive Implementation Plan

## 1. Overview

Add an **image attachment for “Problem Tree”** in the **Key Information** section of project create and edit. The executor or applicant can upload **one image per project**. The file is stored in the **same folder as the project’s other attachments**, with a fixed naming rule. When the user selects a **new** file and an image **already exists**, a **browser confirmation** must be shown; the new file is **only accepted if the user clicks Yes**.

---

## 2. Requirements Summary

| Item | Requirement |
|------|-------------|
| **Section** | Key Information |
| **Who can upload** | Executor or applicant |
| **Storage folder** | Same as project (project’s attachment folder) |
| **File name** | `{project_id}_Problem_Tree.{extension}` (e.g. `DP-0001_Problem_Tree.jpg`) |
| **Cardinality** | One image per project; new upload **replaces** the previous (after user confirms) |
| **Replace confirmation** | **Required:** If an image already exists and the user chooses a new file, show a browser warning. Accept the new file **only if the user clicks Yes**; if No, clear the file input and keep the existing image. |
| **Create form** | File input; no replace confirmation (no existing image) |
| **Edit form** | File input + replace confirmation when `problem_tree_file_path` is set |

---

## 3. Replace Confirmation (Browser Warning) – Detailed Spec

### 3.1 When It Applies

- **Edit form only**, and only when the project **already has** a Problem Tree image:  
  `$project->problem_tree_file_path` is not null/empty.
- **Create form:** No confirmation (no persisted image yet).

### 3.2 Trigger

- **Event:** `change` on the file input `#problem_tree_image`.
- **Condition:** `input.files.length > 0` (user has selected a new file) **and** `data-has-existing="1"` (or equivalent) on the Problem Tree block.

### 3.3 Message (Exact Wording)

> **A Problem Tree image already exists for this project. Uploading a new file will permanently replace it. Do you want to continue?**

- **Yes (OK):** Accept the new file — leave the input as-is; on submit the new file will be sent and the backend will replace the stored image.
- **No (Cancel):** Reject the new file — clear the file input (`input.value = ''`) so the existing image is kept and no new file is submitted.

### 3.4 Behaviour Summary

| User action | Has existing image? | Show confirmation? | If Yes | If No |
|-------------|---------------------|--------------------|--------|-------|
| Selects new file (Create) | No | No | — | — |
| Selects new file (Edit) | Yes | **Yes** | Keep selection; form may submit new file | Clear `#problem_tree_image`; no new file in submit |
| Selects new file (Edit) | No | No | — | — |

### 3.5 Data from Server (Edit)

The Edit Key Information partial must expose whether an image exists, e.g.:

```html
<div class="problem-tree-upload-wrapper" data-has-existing="{{ $project->problem_tree_file_path ? '1' : '0' }}">
    <input type="file" name="problem_tree_image" id="problem_tree_image" ...>
</div>
```

- `data-has-existing="1"` → show confirmation when user selects a new file.
- `data-has-existing="0"` → no confirmation.

### 3.6 JavaScript Logic (Pseudocode)

```js
// Run when DOM ready; only when #problem_tree_image exists (Edit or Create)
const input = document.getElementById('problem_tree_image');
if (!input) return;

const wrapper = input.closest('.problem-tree-upload-wrapper');
const hasExisting = wrapper && wrapper.getAttribute('data-has-existing') === '1';

input.addEventListener('change', function () {
    if (this.files.length === 0) return;
    if (!hasExisting) return; // No existing image → no confirmation

    var msg = 'A Problem Tree image already exists for this project. Uploading a new file will permanently replace it. Do you want to continue?';
    if (!confirm(msg)) {
        this.value = '';  // Clear selection; existing image remains
    }
});
```

### 3.7 Create Form

- Use the same `#problem_tree_image` ID and optionally the same wrapper class.
- Omit `data-has-existing` or set `data-has-existing="0"`. The script will not show the confirmation on Create.

---

## 4. Current Structure (Relevant Parts)

### 4.1 Key Information

| View | Path |
|------|------|
| Create | `resources/views/projects/partials/key_information.blade.php` |
| Edit | `resources/views/projects/partials/Edit/key_information.blade.php` |
| Show | `resources/views/projects/partials/Show/key_information.blade.php` |

**Controller:** `app/Http/Controllers/Projects/KeyInformationController.php`  
- `store(Request $request, Project $project)` — from `ProjectController@store`  
- `update(Request $request, Project $project)` — from `ProjectController@update`

### 4.2 Project Create / Update Flow

- **Create:** `ProjectController@store` (≈ line 549)  
  1. `GeneralInfoController@store` → project created, `project_id` available  
  2. Institutional: Logical Framework, Sustainability, Budget, common `file`  
  3. **`KeyInformationController@store($request, $project)`** — **all** project types  
  4. Type-specific logic (RST, CCI, IES, IIES, IAH, ILP, etc.)

- **Edit:** `ProjectController@update` (≈ line 1313)  
  - `KeyInformationController@update($request, $project)`

- **Forms:** `createProjects.blade.php` and `edit.blade.php` use `enctype="multipart/form-data"`.

### 4.3 Project Model and Table

- **Model:** `app/Models/OldProjects/Project.php`
- **Table:** `projects`
- **Relevant:** `project_id`, `project_type`, `initial_information`, `target_beneficiaries`, `general_situation`, `need_of_project`, `goal`.

### 4.4 Project Attachment Storage

- **Institutional (AttachmentController):**  
  `project_attachments/{sanitized_project_type}/{project_id}/`  
  `sanitized_project_type = preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $project->project_type)`
- **Individual:**  
  - IES: `project_attachments/IES/{project_id}/`  
  - IIES: `project_attachments/IIES/{project_id}/`  
  - IAH: `project_attachments/IAH/{project_id}/`  
  - ILP: `project_attachments/ILP/{project_id}/`

Problem Tree must use the **same** project folder convention.

### 4.5 Config

- `config/attachments.php` — `allowed_types`, `max_size`, `storage`.

---

## 5. Implementation Phases

### Phase 1 – Backend (Database, Model, Storage, Controller)

1. Migration: add `problem_tree_file_path` to `projects`
2. Model: `$fillable` and optional URL accessor
3. Project folder helper: `Project::getAttachmentBasePath()` or equivalent
4. `config/attachments.php`: `problem_tree` allowed types and size
5. `KeyInformationController@store` and `@update`: validate, save, replace old file, set `problem_tree_file_path`
6. Request validation: `problem_tree_image` in `StoreProjectRequest` / `UpdateProjectRequest` or in controller

### Phase 2 – Frontend (Inputs, Replace Confirmation, Show)

7. Create partial: Problem Tree file input (no `data-has-existing` / `"0"`)
8. Edit partial: Problem Tree file input + `data-has-existing` + current image preview (optional) + **replace-confirmation script**
9. Show partial: display image/link when `problem_tree_file_path` is set
10. Ensure replace confirmation runs on Edit when `data-has-existing="1"` and user selects a new file; only accept on “Yes”, clear input on “No”

### Phase 3 – Optional / Follow-up

11. Export/PDF: include Problem Tree in Key Information
12. Project delete: remove file at `problem_tree_file_path` if not already handled
13. “Remove” control on Edit to clear `problem_tree_file_path` and delete file (optional)

---

## 6. Detailed Implementation Tasks

### 6.1 Database Migration

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_add_problem_tree_file_path_to_projects_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('problem_tree_file_path')->nullable()->after('goal');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('problem_tree_file_path');
        });
    }
};
```

- **Column:** `problem_tree_file_path`, nullable, stores path like  
  `project_attachments/Development_Projects/DP-0001/DP-0001_Problem_Tree.jpg`

---

### 6.2 Model: `Project`

**File:** `app/Models/OldProjects/Project.php`

- Add to `$fillable`:

```php
'problem_tree_file_path',
```

- Optional accessor:

```php
public function getProblemTreeImageUrlAttribute(): ?string
{
    if (empty($this->problem_tree_file_path)) {
        return null;
    }
    return \Illuminate\Support\Facades\Storage::disk('public')->url($this->problem_tree_file_path);
}
```

---

### 6.3 Project Attachment Folder Helper

**Goal:** One place that returns the project’s attachment directory so Problem Tree (and optionally others) stay consistent with existing behaviour.

**Option A – Method on `Project`**

**File:** `app/Models/OldProjects/Project.php`

```php
/**
 * Base path for project attachments (e.g. project_attachments/DP/DP-0001).
 * Used for Problem Tree and aligned with AttachmentController / type-specific controllers.
 */
public function getAttachmentBasePath(): string
{
    $map = [
        'Individual - Ongoing Educational support' => 'IES',
        'Individual - Initial - Educational support' => 'IIES',
        'Individual - Access to Health' => 'IAH',
        'Individual - Livelihood Application' => 'ILP',
    ];

    $folder = $map[$this->project_type] ?? null;
    if ($folder === null) {
        $folder = preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $this->project_type);
        $folder = trim($folder, '._') ?: 'unknown_type';
    }

    return "project_attachments/{$folder}/{$this->project_id}";
}
```

**Option B – Config + helper / service**

- In `config/attachments.php`: `'project_type_folder_map' => [...]` for IES, IIES, IAH, ILP.
- Helper or `ProjectAttachmentPathService::getBasePath(Project $project)` that uses the map and falls back to sanitized `project_type`.

Use the same logic as in `AttachmentController` and type-specific controllers so paths match.

---

### 6.4 Config: `config/attachments.php`

Add under `allowed_types`:

```php
'problem_tree' => [
    'extensions' => ['jpg', 'jpeg', 'png'],
    'mime_types' => ['image/jpeg', 'image/png'],
],
```

Optional: `max_size` / `max_size_mb` for Problem Tree if it should differ from the global value.

---

### 6.5 KeyInformationController – Store and Update

**File:** `app/Http/Controllers/Projects/KeyInformationController.php`

**Dependencies:** `Illuminate\Support\Facades\Storage`, project folder helper (e.g. `$project->getAttachmentBasePath()`).

**Shared logic for `problem_tree_image` (use in both `store` and `update`):**

1. **If `!$request->hasFile('problem_tree_image')`**  
   - Do nothing for Problem Tree; only process text fields.

2. **If `$request->hasFile('problem_tree_image')`**  
   - Validate:  
     `'problem_tree_image' => 'nullable|file|image|mimes:jpeg,jpg,png|max:7168'`  
     (or use `config('attachments.allowed_types.problem_tree')` and `config('attachments.max_size')`).
   - `$file = $request->file('problem_tree_image');`
   - `$ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension());`
   - Filename: `$filename = $project->project_id . '_Problem_Tree.' . $ext;`
   - Folder: `$folder = $project->getAttachmentBasePath();`
   - Ensure directory:  
     `Storage::disk('public')->makeDirectory($folder, 0755, true);`
   - **Replace:**  
     If `$project->problem_tree_file_path` is not null and  
     `Storage::disk('public')->exists($project->problem_tree_file_path)`:  
     `Storage::disk('public')->delete($project->problem_tree_file_path);`
   - Save:  
     `$path = $file->storeAs($folder, $filename, 'public');`
   - Set: `$project->problem_tree_file_path = $path;`
   - Save project together with other Key Information fields.

**Error handling:**  
- On failure after writing the new file: delete the new file and rethrow so `ProjectController` can roll back the transaction.

**Validation:**  
- Add `problem_tree_image` to the `validate([...])` in both `store` and `update` (or a private method used by both).

---

### 6.6 Request Validation

- **StoreProjectRequest:**  
  When the create form includes the Problem Tree input, add:  
  `'problem_tree_image' => 'nullable|file|image|mimes:jpeg,jpg,png|max:7168'`
- **UpdateProjectRequest:**  
  Same rule when the edit form includes it.  
- Alternatively, validate only in `KeyInformationController`; then Request classes need not change.

---

### 6.7 Create Partial – Problem Tree Block

**File:** `resources/views/projects/partials/key_information.blade.php`

Add **before** the closing `</div>` of the card-body (e.g. after “Goal of the Project”):

```html
<!-- Problem Tree Image -->
<div class="mb-3 problem-tree-upload-wrapper" data-has-existing="0">
    <label for="problem_tree_image" class="form-label">Problem Tree (image)</label>
    <input type="file" name="problem_tree_image" id="problem_tree_image" 
           class="form-control" 
           accept="image/jpeg,image/jpg,image/png">
    <small class="form-text text-muted">One image per project. Allowed: JPG, PNG. Max 7 MB.</small>
    @error('problem_tree_image')
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>
```

- `data-has-existing="0"` so the replace-confirmation script never runs on Create.

---

### 6.8 Edit Partial – Problem Tree Block and Replace Confirmation

**File:** `resources/views/projects/partials/Edit/key_information.blade.php`

Add **before** the closing `</div>` of the card-body (e.g. after “Goal of the Project”):

```html
<!-- Problem Tree Image -->
<div class="mb-3 problem-tree-upload-wrapper" data-has-existing="{{ $project->problem_tree_file_path ? '1' : '0' }}">
    <label for="problem_tree_image" class="form-label">Problem Tree (image)</label>
    @if($project->problem_tree_file_path)
        <div class="mb-2">
            <img src="{{ $project->problem_tree_image_url ?? Storage::url($project->problem_tree_file_path) }}" 
                 alt="Problem Tree" class="img-thumbnail" style="max-height:200px;">
            <small class="d-block text-muted">Current image. Choosing a new file will replace it (after confirmation).</small>
        </div>
    @endif
    <input type="file" name="problem_tree_image" id="problem_tree_image" 
           class="form-control" 
           accept="image/jpeg,image/jpg,image/png">
    <small class="form-text text-muted">One image per project. Allowed: JPG, PNG. Max 7 MB.</small>
    @error('problem_tree_image')
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('problem_tree_image');
        if (!input) return;
        var wrapper = input.closest('.problem-tree-upload-wrapper');
        var hasExisting = wrapper && wrapper.getAttribute('data-has-existing') === '1';

        input.addEventListener('change', function () {
            if (this.files.length === 0) return;
            if (!hasExisting) return;

            var msg = 'A Problem Tree image already exists for this project. Uploading a new file will permanently replace it. Do you want to continue?';
            if (!confirm(msg)) {
                this.value = '';
            }
        });
    });
})();
</script>
```

- `data-has-existing="{{ $project->problem_tree_file_path ? '1' : '0' }}"` so the confirmation runs only when an image exists.
- `confirm()` shows the exact message; **Yes (OK)** keeps the selection, **No (Cancel)** clears `this.value` so the new file is not accepted and the existing image is retained on submit.
- Ensure `Storage` is available in the view (usually via `@php use Illuminate\Support\Facades\Storage; @endphp` or it may already be in scope). If using an accessor `problem_tree_image_url`, that can be used instead of `Storage::url(...)`.

---

### 6.9 Show Partial – Problem Tree

**File:** `resources/views/projects/partials/Show/key_information.blade.php`

Add **after** the Goal block and **before** the “No key information” / closing div:

```html
@if($project->problem_tree_file_path)
    <div class="mb-3">
        <div class="info-label"><strong>Problem Tree:</strong></div>
        <div class="info-value">
            <a href="{{ Storage::url($project->problem_tree_file_path) }}" target="_blank" rel="noopener">
                <img src="{{ Storage::url($project->problem_tree_file_path) }}" alt="Problem Tree" class="img-thumbnail" style="max-height:300px;">
            </a>
        </div>
    </div>
@endif
```

- Or use `$project->problem_tree_image_url` if the accessor is defined.

---

### 6.10 Script Placement (Edit)

- The replace-confirmation script is in the Edit Key Information partial so it stays next to the input and `data-has-existing`.
- If the Edit page loads Key Information via AJAX or a different structure, move the script to a shared JS file and initialise using a selector and `data-has-existing` on the wrapper. The logic in § 3.6 and § 6.8 stays the same.

---

### 6.11 ProjectController

- **No change** to `store` / `update`; `KeyInformationController@store` and `@update` already receive `$request` and `$project`. The replace-confirmation is purely frontend; the backend always performs replace when a new `problem_tree_image` is present.

---

### 6.12 Export / PDF (Optional)

- In `ExportController::addKeyInformationSection` (or equivalent), when `$project->problem_tree_file_path` is set, resolve the absolute path, embed or link the image in the exported document.

---

### 6.13 Project Delete (Optional / When Applicable)

- In the logic that deletes a project, before or after DB delete:  
  `Storage::disk('public')->delete($project->problem_tree_file_path);`  
  (with an `exists` check if desired.)

---

## 7. File Naming and Path Examples

| project_id | project_type (example) | Folder (example) | Filename |
|------------|------------------------|------------------|----------|
| DP-0001 | Development Projects | `project_attachments/Development_Projects/DP-0001/` | `DP-0001_Problem_Tree.jpg` |
| IIES-0002 | Individual - Initial - Educational support | `project_attachments/IIES/IIES-0002/` | `IIES-0002_Problem_Tree.png` |
| IAH-0003 | Individual - Access to Health | `project_attachments/IAH/IAH-0003/` | `IAH-0003_Problem_Tree.jpeg` |
| RSTP2-0004 | Residential Skill Training Proposal 2 | `project_attachments/Residential_Skill_Training_Proposal_2/RSTP2-0004/` | `RSTP2-0004_Problem_Tree.jpg` |

`problem_tree_file_path` in DB:  
`project_attachments/Development_Projects/DP-0001/DP-0001_Problem_Tree.jpg`  
(relative to `storage/app/public`; use `Storage::url($path)` or `asset('storage/'.$path)` for frontend.)

---

## 8. Implementation Checklist

| # | Task | Phase | Required |
|---|------|-------|----------|
| 1 | Migration: add `problem_tree_file_path` to `projects` | 1 | ✅ |
| 2 | `Project`: add `problem_tree_file_path` to `$fillable`; optional `problem_tree_image_url` accessor | 1 | ✅ |
| 3 | `Project::getAttachmentBasePath()` (or service) for project folder | 1 | ✅ |
| 4 | `config/attachments.php`: `problem_tree` allowed types (and size if needed) | 1 | ✅ |
| 5 | `KeyInformationController@store`: validate, save, replace old file, set `problem_tree_file_path` | 1 | ✅ |
| 6 | `KeyInformationController@update`: same as store | 1 | ✅ |
| 7 | `StoreProjectRequest` / `UpdateProjectRequest` or controller: `problem_tree_image` validation | 1 | ✅ |
| 8 | Create partial: Problem Tree block with `data-has-existing="0"` | 2 | ✅ |
| 9 | Edit partial: Problem Tree block with `data-has-existing` from `$project->problem_tree_file_path` | 2 | ✅ |
| 10 | Edit: **Replace-confirmation script** – on `change`, if `data-has-existing="1"` and `files.length > 0`, show `confirm(...)`; if No, set `input.value = ''` | 2 | ✅ |
| 11 | Show partial: display image/link when `problem_tree_file_path` is set | 2 | ✅ |
| 12 | Export/PDF: include Problem Tree in Key Information | 3 | Optional |
| 13 | Project delete: delete file at `problem_tree_file_path` | 3 | When delete exists |
| 14 | (Optional) Edit: “Remove” control to clear and delete Problem Tree | 3 | Optional |

---

## 9. Replace-Confirmation Compliance

| Requirement | Implementation |
|-------------|----------------|
| One image per project; new upload overwrites previous | Backend: replace old file and `problem_tree_file_path` when new `problem_tree_image` is present |
| User must get browser warning when uploading new file over existing | `confirm(...)` in `change` handler when `data-has-existing="1"` and user selects a file |
| New file accepted only if user clicks Yes | If `confirm(...)` is true, do nothing → file stays in input and is submitted |
| If user clicks No, do not accept new file | If `confirm(...)` is false, set `input.value = ''` → no new file in form submit; existing image unchanged |

---

## 10. References

- `app/Http/Controllers/Projects/KeyInformationController.php`
- `app/Http/Controllers/Projects/AttachmentController.php` (path and `sanitizeProjectType`)
- `app/Http/Controllers/Projects/ProjectController.php` (`store` ≈549–590, `update` ≈1313)
- `app/Models/OldProjects/Project.php`
- `resources/views/projects/partials/key_information.blade.php`
- `resources/views/projects/partials/Edit/key_information.blade.php`
- `resources/views/projects/partials/Show/key_information.blade.php`
- `config/attachments.php`
- `database/migrations/2024_07_20_085634_create_projects_table.php`
- `database/migrations/2026_01_07_182657_add_key_information_fields_to_projects_table.php`
