# IOES (Individual - Ongoing Educational Support) — Show Partials Edit Label Review

**Date:** 2026-02-09  
**Project Type:** Individual - Ongoing Educational support (IES)  
**Scope:** Show view partials incorrectly displaying "Edit:" labels and editable form content  
**Status:** Read-only analysis; findings documented for remediation

---

## Executive Summary

For **Project Type: Individual - Ongoing Educational support**, the **Show** (view) page displays three sections with incorrect "Edit:" labels and editable form UI:

1. **Edit: Details of Other Working Family Members**
2. **Edit: Details about Immediate Family Members**
3. **Edit: Educational Background / Present Education (support requested)**

These sections should display read-only data. Instead, they render the same content as the Edit page—including form inputs, checkboxes, Add/Remove buttons, and JavaScript—causing confusion and an inconsistent user experience.

**Root cause:** The `Show/IES/` partials are direct copies of the `Edit/IES/` partials and were never adapted for read-only display.

---

## 1. Problem Statement

### 1.1 Observed Behaviour

When viewing an Individual - Ongoing Educational support project on the **Show** page, users see:

| Section | Expected (read-only) | Actual |
|---------|----------------------|--------|
| Family Working Members | "Details of Other Working Family Members" with read-only table | "Edit: Details of Other Working Family Members" with editable inputs and Add/Remove buttons |
| Immediate Family Details | "Details about Immediate Family Members" with read-only display | "Edit: Details about Immediate Family Members" with checkboxes, textareas, radio buttons |
| Educational Background | "Educational Background / Present Education (support requested)" with read-only display | "Edit: Educational Background / Present Education (support requested)" with form inputs |

### 1.2 Impact

- **User confusion:** "Edit:" suggests the page is an edit form, but the Show page is meant for viewing only.
- **Inconsistent UX:** Other Individual project types (e.g. IIES) have proper read-only Show partials.
- **Security/UX risk:** Editable form elements on a Show page may encourage users to believe they can submit changes when the context is view-only.

---

## 2. Technical Analysis

### 2.1 Partial Structure by Context

| Context | Blade File | Partials Used | Expected Behaviour |
|---------|------------|---------------|--------------------|
| **Create** | `Oldprojects/createProjects.blade.php` | `IES.*` (e.g. `IES.family_working_members`) | Create form; no "Edit:" prefix |
| **Edit** | `Oldprojects/edit.blade.php` | `Edit.IES.*` | Edit form; "Edit:" prefix is correct |
| **Show** | `Oldprojects/show.blade.php` | `Show.IES.*` | Read-only view; no "Edit:" prefix; no form inputs |

### 2.2 Show Page Include Chain

**File:** `resources/views/projects/Oldprojects/show.blade.php` (lines 172–178)

```blade
@if ($project->project_type === 'Individual - Ongoing Educational support')
    @include('projects.partials.Show.IES.personal_info')
    @include('projects.partials.Show.IES.family_working_members')
    @include('projects.partials.Show.IES.immediate_family_details')
    @include('projects.partials.Show.IES.educational_background')
    @include('projects.partials.Show.IES.estimated_expenses')
    @include('projects.partials.Show.IES.attachments')
@endif
```

The Show page correctly includes `Show.IES.*` partials. The problem is the **content** of those partials.

### 2.3 Affected Files

| Partial | Path | Content Type |
|---------|------|--------------|
| `Show/IES/family_working_members.blade.php` | `resources/views/projects/partials/Show/IES/` | Duplicate of Edit partial; contains "Edit:" heading, form inputs, Add/Remove JS |
| `Show/IES/immediate_family_details.blade.php` | `resources/views/projects/partials/Show/IES/` | Duplicate of Edit partial; contains "Edit:" heading, checkboxes, textareas |
| `Show/IES/educational_background.blade.php` | `resources/views/projects/partials/Show/IES/` | Duplicate of Edit partial; contains "Edit:" heading, form inputs |

**Evidence:** Each Show partial has a Blade comment referencing the Edit file:

```blade
{{-- resources/views/projects/partials/Edit/IES/family_working_members.blade.php --}}
```

### 2.4 Content Comparison

#### `Show/IES/family_working_members.blade.php`

- **Heading:** `Edit: Details of Other Working Family Members`
- **Structure:** Editable table with `<input>`, `<button>Remove</button>`, "Add More Family Member" button
- **JavaScript:** `addFamilyMemberRow()`, `removeFamilyMemberRow()`, `updateFamilyMemberRowNumbers()`
- **Data binding:** `value="{{ old('member_name.' . $index, $member->member_name) }}"` (edit-style)

#### `Show/IES/immediate_family_details.blade.php`

- **Heading:** `Edit: Details about Immediate Family Members`
- **Structure:** Form groups with checkboxes, textareas, radio buttons
- **Data binding:** `{{ old('mother_expired', $familyDetails->mother_expired) ? 'checked' : '' }}` (edit-style)

#### `Show/IES/educational_background.blade.php`

- **Heading:** `Edit: Educational Background / Present Education (support requested)`
- **Structure:** Form inputs and textareas for previous class, amount sanctioned, academic performance, etc.
- **Data binding:** `value="{{ old('previous_class', $educationBackground->previous_class) }}"` (edit-style)

---

## 3. Comparison with IIES (Correct Implementation)

**Individual - Initial - Educational support (IIES)** has proper Show partials that serve as the reference pattern.

### 3.1 IIES Show Partial Structure

| Partial | IIES (Show) | IES (Show) |
|---------|-------------|------------|
| **family_working_members** | Heading: "Family Working Members"; read-only table with `{{ $member->iies_member_name }}` | Heading: "Edit: Details of Other Working Family Members"; editable inputs |
| **immediate_family_details** | Heading: "Immediate Family Details"; read-only table with `{{ $familyDetails->iies_father_expired ? 'Yes' : 'No' }}` | Heading: "Edit: Details about Immediate Family Members"; checkboxes, textareas |
| **educational_background** | Proper read-only display | Heading: "Edit: Educational Background..."; form inputs |

### 3.2 IIES Model/Field Mapping

| IIES Model | Relationship | Display Format |
|------------|--------------|----------------|
| `ProjectIIESFamilyWorkingMembers` | `$project->iiesFamilyWorkingMembers` | `$member->iies_member_name`, `$member->iies_work_nature`, `$member->iies_monthly_income` |
| `ProjectIIESImmediateFamilyDetails` | `$project->iiesImmediateFamilyDetails` | `$familyDetails->iies_father_expired`, etc. |

### 3.3 IES Model/Field Mapping (for Remediation)

| IES Model | Relationship | Fields to Display |
|-----------|--------------|-------------------|
| `ProjectIESFamilyWorkingMembers` | `$project->iesFamilyWorkingMembers` | `member_name`, `work_nature`, `monthly_income` |
| `ProjectIESImmediateFamilyDetails` | `$project->iesImmediateFamilyDetails` | `mother_expired`, `father_expired`, `grandmother_support`, etc. |
| `ProjectIESEducationBackground` | `$project->iesEducationBackground` | `previous_class`, `amount_sanctioned`, `present_class`, etc. |

---

## 4. Why This Occurred

### 4.1 Hypothesised Causes

1. **Copy-paste without adaptation:** Show.IES partials were likely created by copying Edit.IES partials to avoid starting from scratch. The "Edit:" prefix and editable form elements were never removed or replaced with read-only displays.

2. **Inconsistent implementation:** IIES was implemented with proper Show partials. IES (Ongoing) was not given the same treatment, possibly due to:
   - Different development phases
   - Different developers
   - Time pressure or scope cuts

3. **Intentional shortcut:** Reusing Edit partials for Show to reduce duplication, but without:
   - Removing the "Edit:" prefix
   - Converting inputs to read-only display
   - Adding a `readonly` or `disabled` flag (which would still be suboptimal for a Show view)

4. **Testing gap:** Show page for IES projects may not have been regression-tested for read-only behaviour and correct labels.

---

## 5. Remediation Checklist (Future Implementation)

When fixing, the following changes should be applied to each affected Show partial:

### 5.1 `Show/IES/family_working_members.blade.php`

- [ ] Remove "Edit:" from heading; use "Details of Other Working Family Members"
- [ ] Replace `<input>` with `{{ $member->member_name }}`, `{{ $member->work_nature }}`, `{{ $member->monthly_income }}`
- [ ] Remove "Action" column and Remove buttons
- [ ] Remove "Add More Family Member" button
- [ ] Remove `addFamilyMemberRow`, `removeFamilyMemberRow`, `updateFamilyMemberRowNumbers` JavaScript
- [ ] Handle empty state: "No family members recorded" when `$project->iesFamilyWorkingMembers` is empty
- [ ] Use `format_indian_currency()` for `monthly_income` (consistent with IIES)

### 5.2 `Show/IES/immediate_family_details.blade.php`

- [ ] Remove "Edit:" from heading; use "Details about Immediate Family Members"
- [ ] Replace checkboxes with read-only display: `{{ $familyDetails->mother_expired ? 'Yes' : 'No' }}`
- [ ] Replace textareas with `{{ $familyDetails->family_situation }}` etc.
- [ ] Replace radio buttons with Yes/No display
- [ ] Use table or definition list layout (consistent with IIES)
- [ ] Remove form-related JavaScript (e.g. textarea auto-resize if only used for edit)

### 5.3 `Show/IES/educational_background.blade.php`

- [ ] Remove "Edit:" from heading; use "Educational Background / Present Education (support requested)"
- [ ] Replace all `<input>` and `<textarea>` with `{{ $educationBackground->field_name }}`
- [ ] Use `format_indian_currency()` for numeric fields (amount_sanctioned, amount_utilized, etc.)
- [ ] Handle null `$project->iesEducationBackground` with "No data recorded" or similar
- [ ] Remove form-related JavaScript

### 5.4 Other Show.IES Partials (Verification)

| Partial | Status | Notes |
|---------|--------|------|
| `Show/IES/personal_info.blade.php` | Verify | Check for "Edit:" prefix and editable form elements |
| `Show/IES/estimated_expenses.blade.php` | Verify | Same |
| `Show/IES/attachments.blade.php` | Verify | Same |

---

## 6. File Reference Summary

| File | Purpose |
|------|---------|
| `app/Constants/ProjectType.php` | Defines `INDIVIDUAL_ONGOING_EDUCATIONAL = 'Individual - Ongoing Educational support'` |
| `resources/views/projects/Oldprojects/show.blade.php` | Main Show view; includes `Show.IES.*` partials when project type matches |
| `resources/views/projects/Oldprojects/edit.blade.php` | Edit view; includes `Edit.IES.*` (correct usage) |
| `resources/views/projects/Oldprojects/createProjects.blade.php` | Create view; includes `IES.*` (correct usage) |
| `resources/views/projects/partials/Show/IES/` | **Affected** — Show partials with Edit content |
| `resources/views/projects/partials/Edit/IES/` | Edit partials (reference; correct for Edit context) |
| `resources/views/projects/partials/IES/` | Create partials (reference; correct for Create context) |
| `resources/views/projects/partials/Show/IIES/` | Reference implementation for read-only Show partials |

---

## 7. Related Controllers and Models

| Controller | Purpose |
|------------|---------|
| `IESFamilyWorkingMembersController` | Handles family working members CRUD |
| `IESImmediateFamilyDetailsController` | Handles immediate family details CRUD |
| `IESEducationBackgroundController` | Handles education background CRUD |

| Model | Table | Relationship |
|-------|-------|--------------|
| `ProjectIESFamilyWorkingMembers` | `project_ies_family_working_members` | `$project->iesFamilyWorkingMembers` |
| `ProjectIESImmediateFamilyDetails` | `project_ies_immediate_family_details` | `$project->iesImmediateFamilyDetails` |
| `ProjectIESEducationBackground` | `project_ies_education_background` | `$project->iesEducationBackground` |

---

## 8. Conclusion

The Show page for **Individual - Ongoing Educational support** (IES) incorrectly uses Edit-style partials, resulting in "Edit:" labels and editable form elements in a read-only context. The root cause is that `Show/IES/` partials were copied from `Edit/IES/` and never adapted for read-only display.

Remediation should follow the pattern established in `Show/IIES/` partials: plain headings, read-only display, and no form inputs or JavaScript. The checklist in Section 5 can be used to implement the fixes.
