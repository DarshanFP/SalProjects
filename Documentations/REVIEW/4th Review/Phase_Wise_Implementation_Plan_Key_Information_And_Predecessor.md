# Phase-Wise Implementation Plan: Key Information Enhancement & Predecessor Project Selection

**Date:** January 2025  
**Status:** Planning Phase  
**Scope:** Add additional text areas to Key Information section and enable Predecessor Project selection for all project types

---

## Executive Summary

This document outlines the phase-wise implementation plan for two major enhancements:

1. **Key Information Section Enhancement** - Add four new text areas before the existing "Goal of the Project" field:
   - Initial information
   - Target beneficiaries
   - General situation
   - Need of the project
   - Goal of the Project (existing, will remain last)

2. **Predecessor Project Selection** - Enable predecessor project selection for ALL project types (currently only available for "NEXT PHASE - DEVELOPMENT PROPOSAL")

**Key Requirements:**
- All new fields must be nullable to support "Save as Draft" functionality
- Fields should use maximum text length (TEXT type in database)
- Implementation must work for all project types (institutional and individual)
- Must work in both create and edit/update forms
- Must be displayed in show/view pages

---

## Table of Contents

1. [Overview](#overview)
2. [Phase 1: Database Migration](#phase-1-database-migration)
3. [Phase 2: Model Updates](#phase-2-model-updates)
4. [Phase 3: Controller Updates](#phase-3-controller-updates)
5. [Phase 4: View Updates - Create Forms](#phase-4-view-updates---create-forms)
6. [Phase 5: View Updates - Edit Forms](#phase-5-view-updates---edit-forms)
7. [Phase 6: View Updates - Show/View Pages](#phase-6-view-updates---showview-pages)
8. [Phase 7: Predecessor Project Selection Enhancement](#phase-7-predecessor-project-selection-enhancement)
8. [Phase 8: Validation Updates](#phase-8-validation-updates)
9. [Phase 9: Testing](#phase-9-testing)
10. [Phase 10: Documentation](#phase-10-documentation)

---

## Overview

### Current State

**Key Information Section:**
- Currently has only one field: `goal` (text, nullable)
- Located in `resources/views/projects/partials/key_information.blade.php`
- Handled by `KeyInformationController`
- Stored in `projects.goal` column

**Predecessor Project Selection:**
- Currently only visible for "NEXT PHASE - DEVELOPMENT PROPOSAL" project type
- Located in `resources/views/projects/partials/general_info.blade.php` (lines 22-32)
- Uses `predecessor_project_id` column (already exists in database)
- JavaScript toggles visibility based on project type

### Target State

**Key Information Section:**
- Five text areas in order:
  1. Initial information
  2. Target beneficiaries
  3. General situation
  4. Need of the project
  5. Goal of the Project (existing)

**Predecessor Project Selection:**
- Available for ALL project types
- Always visible (no conditional display)
- Works in both create and edit forms

---

## Phase 1: Database Migration

**Duration:** 1-2 hours  
**Priority:** CRITICAL (Must be done first)

### Task 1.1: Create Migration for New Key Information Fields

**File to Create:**
- `database/migrations/YYYY_MM_DD_HHMMSS_add_key_information_fields_to_projects_table.php`

**Migration Details:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add new text area fields to Key Information section
     * Fields are added BEFORE the 'goal' column to maintain logical order
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add new fields before 'goal' column
            $table->text('initial_information')->nullable()->before('goal');
            $table->text('target_beneficiaries')->nullable()->before('goal');
            $table->text('general_situation')->nullable()->before('goal');
            $table->text('need_of_project')->nullable()->before('goal');
            // Note: 'goal' column already exists, so we're adding before it
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'initial_information',
                'target_beneficiaries',
                'general_situation',
                'need_of_project'
            ]);
        });
    }
};
```

**Key Points:**
- All fields are `text` type (maximum length)
- All fields are `nullable()` to support save draft functionality
- Fields are added `before('goal')` to maintain logical order in database
- Migration includes proper `down()` method for rollback

### Task 1.2: Verify Predecessor Project Column

**Action:** Verify that `predecessor_project_id` column exists and is properly configured.

**Check:**
- ✅ Column exists (already confirmed in migration `2026_01_07_172101_add_predecessor_project_id_to_projects_table.php`)
- ✅ Column is nullable
- ✅ Foreign key constraint exists
- ✅ Column is after 'goal' (acceptable position)

**No changes needed** - Column already exists and is properly configured.

### Task 1.3: Run Migration

**Commands:**
```bash
php artisan migrate
```

**Verification:**
```bash
php artisan migrate:status
```

**Expected Result:**
- Migration runs successfully
- Four new columns added to `projects` table
- Columns appear before `goal` column
- All columns are nullable

---

## Phase 2: Model Updates

**Duration:** 1 hour  
**Priority:** HIGH

### Task 2.1: Update Project Model - Add Fillable Fields

**File to Modify:**
- `app/Models/OldProjects/Project.php`

**Changes:**
```php
// Add to $fillable array (around line 270)
protected $fillable = [
    // ... existing fields ...
    'initial_information',
    'target_beneficiaries',
    'general_situation',
    'need_of_project',
    'goal',
    'predecessor_project_id',
    // ... rest of fields ...
];
```

**Key Points:**
- Add all four new fields to `$fillable` array
- Ensure `goal` is already in fillable (verify)
- Ensure `predecessor_project_id` is already in fillable (verify)

### Task 2.2: Update Model PHPDoc Comments

**File to Modify:**
- `app/Models/OldProjects/Project.php`

**Changes:**
Add property documentation for new fields (around line 96-98):
```php
/**
 * @property string $status
 * @property string|null $initial_information
 * @property string|null $target_beneficiaries
 * @property string|null $general_situation
 * @property string|null $need_of_project
 * @property string $goal
 * @property string|null $predecessor_project_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
```

**Key Points:**
- Document all new fields
- Mark as nullable where appropriate
- Maintain consistent documentation style

### Task 2.3: Verify Relationships

**Action:** Verify that `predecessor()` and `successors()` relationships exist and work correctly.

**File to Check:**
- `app/Models/OldProjects/Project.php` (around lines 632-640)

**Expected:**
```php
public function predecessor()
{
    return $this->belongsTo(Project::class, 'predecessor_project_id', 'project_id');
}

public function successors()
{
    return $this->hasMany(Project::class, 'predecessor_project_id', 'project_id');
}
```

**No changes needed** - Relationships already exist.

---

## Phase 3: Controller Updates

**Duration:** 2-3 hours  
**Priority:** HIGH

### Task 3.1: Update KeyInformationController

**File to Modify:**
- `app/Http/Controllers/Projects/KeyInformationController.php`

**Changes in `store()` method:**
```php
public function store(Request $request, Project $project)
{
    $validated = $request->validate([
        'initial_information' => 'nullable|string',
        'target_beneficiaries' => 'nullable|string',
        'general_situation' => 'nullable|string',
        'need_of_project' => 'nullable|string',
        'goal' => 'nullable|string',
    ]);
    
    Log::info('KeyInformationController@store - Data received from form', [
        'project_id' => $project->project_id
    ]);

    try {
        // Update all fields if provided
        if (array_key_exists('initial_information', $validated)) {
            $project->initial_information = $validated['initial_information'];
        }
        if (array_key_exists('target_beneficiaries', $validated)) {
            $project->target_beneficiaries = $validated['target_beneficiaries'];
        }
        if (array_key_exists('general_situation', $validated)) {
            $project->general_situation = $validated['general_situation'];
        }
        if (array_key_exists('need_of_project', $validated)) {
            $project->need_of_project = $validated['need_of_project'];
        }
        if (array_key_exists('goal', $validated)) {
            $project->goal = $validated['goal'];
        }
        
        $project->save();

        Log::info('KeyInformationController@store - Data saved successfully', [
            'project_id' => $project->project_id,
        ]);

        return $project;
    } catch (\Exception $e) {
        Log::error('KeyInformationController@store - Error', ['error' => $e->getMessage()]);
        throw $e;
    }
}
```

**Changes in `update()` method:**
```php
public function update(Request $request, Project $project)
{
    $validated = $request->validate([
        'initial_information' => 'nullable|string',
        'target_beneficiaries' => 'nullable|string',
        'general_situation' => 'nullable|string',
        'need_of_project' => 'nullable|string',
        'goal' => 'nullable|string',
    ]);
    
    Log::info('KeyInformationController@update - Data received from form', [
        'project_id' => $project->project_id
    ]);

    try {
        // Update all fields if provided
        if (array_key_exists('initial_information', $validated)) {
            $project->initial_information = $validated['initial_information'];
        }
        if (array_key_exists('target_beneficiaries', $validated)) {
            $project->target_beneficiaries = $validated['target_beneficiaries'];
        }
        if (array_key_exists('general_situation', $validated)) {
            $project->general_situation = $validated['general_situation'];
        }
        if (array_key_exists('need_of_project', $validated)) {
            $project->need_of_project = $validated['need_of_project'];
        }
        if (array_key_exists('goal', $validated)) {
            $project->goal = $validated['goal'];
        }
        
        $project->save();

        Log::info('KeyInformationController@update - Data saved successfully', [
            'project_id' => $project->project_id,
        ]);

        return $project;
    } catch (\Exception $e) {
        Log::error('KeyInformationController@update - Error', ['error' => $e->getMessage()]);
        throw $e;
    }
}
```

**Key Points:**
- All fields are nullable in validation
- Update only fields that are provided in request
- Maintain existing logging pattern
- Use safe logging (no sensitive data)

### Task 3.2: Update GeneralInfoController (Predecessor Project)

**File to Modify:**
- `app/Http/Controllers/Projects/GeneralInfoController.php`

**Action:** Verify that `predecessor_project` validation and mapping already exists.

**Check:**
- ✅ Validation rule exists (line 48): `'predecessor_project' => 'nullable|string|exists:projects,project_id'`
- ✅ Mapping exists (lines 70-73): Maps `predecessor_project` to `predecessor_project_id`
- ✅ Works in both `store()` and `update()` methods

**No changes needed** - Predecessor project handling already exists in GeneralInfoController.

### Task 3.3: Update ProjectController (Predecessor Project Display)

**File to Modify:**
- `app/Http/Controllers/Projects/ProjectController.php`

**Action:** Update `create()` method to always fetch development projects for predecessor selection (not just for NEXT PHASE).

**Current Code (around line 1028):**
```php
// Fetch development projects for predecessor selection:
```

**Changes:**
- Remove conditional logic that limits predecessor project fetching
- Always fetch development projects for all project types
- Ensure `$developmentProjects` is always available in view

**Example:**
```php
// In create() method - always fetch development projects for predecessor selection
$developmentProjects = Project::where('project_type', ProjectType::DEVELOPMENT_PROJECTS)
    ->orWhere('project_type', ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL)
    ->orderBy('project_id', 'desc')
    ->get();

// Pass to view (ensure it's always passed)
return view('projects.Oldprojects.createProjects', compact(
    // ... existing variables ...
    'developmentProjects'
));
```

**Key Points:**
- Always fetch development projects
- Include both DEVELOPMENT_PROJECTS and NEXT_PHASE_DEVELOPMENT_PROPOSAL
- Order by project_id descending (newest first)
- Always pass to view

### Task 3.4: Update ProjectController (Edit Method)

**File to Modify:**
- `app/Http/Controllers/Projects/ProjectController.php`

**Action:** Update `edit()` method to always fetch development projects for predecessor selection.

**Changes:**
- Similar to `create()` method
- Always fetch and pass `$developmentProjects` to edit view
- Ensure existing project's predecessor is pre-selected

---

## Phase 4: View Updates - Create Forms

**Duration:** 2-3 hours  
**Priority:** HIGH

### Task 4.1: Update Key Information Partial (Create)

**File to Modify:**
- `resources/views/projects/partials/key_information.blade.php`

**Current Content:**
```blade
<!-- resources/views/projects/partials/key_information.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Key Information</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project</label>
            <textarea name="goal" id="goal" class="form-control select-input" rows="3" required>{{ old('goal') }}</textarea>
            @error('goal')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>
```

**New Content:**
```blade
<!-- resources/views/projects/partials/key_information.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Key Information</h4>
    </div>
    <div class="card-body">
        <!-- Initial Information -->
        <div class="mb-3">
            <label for="initial_information" class="form-label">Initial Information</label>
            <textarea name="initial_information" id="initial_information" class="form-control select-input" rows="3">{{ old('initial_information') }}</textarea>
            @error('initial_information')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Target Beneficiaries -->
        <div class="mb-3">
            <label for="target_beneficiaries" class="form-label">Target Beneficiaries</label>
            <textarea name="target_beneficiaries" id="target_beneficiaries" class="form-control select-input" rows="3">{{ old('target_beneficiaries') }}</textarea>
            @error('target_beneficiaries')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- General Situation -->
        <div class="mb-3">
            <label for="general_situation" class="form-label">General Situation</label>
            <textarea name="general_situation" id="general_situation" class="form-control select-input" rows="3">{{ old('general_situation') }}</textarea>
            @error('general_situation')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Need of the Project -->
        <div class="mb-3">
            <label for="need_of_project" class="form-label">Need of the Project</label>
            <textarea name="need_of_project" id="need_of_project" class="form-control select-input" rows="3">{{ old('need_of_project') }}</textarea>
            @error('need_of_project')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Goal of the Project (Existing, Last) -->
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project</label>
            <textarea name="goal" id="goal" class="form-control select-input" rows="3">{{ old('goal') }}</textarea>
            @error('goal')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>
```

**Key Points:**
- All fields are NOT required (nullable, supports save draft)
- Use `old()` helper for form repopulation on validation errors
- Include error display for each field
- Maintain consistent styling with existing form
- Order: Initial Information → Target Beneficiaries → General Situation → Need of Project → Goal

### Task 4.2: Update General Info Partial (Predecessor Project)

**File to Modify:**
- `resources/views/projects/partials/general_info.blade.php`

**Current Code (lines 22-32):**
```blade
<div id="predecessor-project-section" style="display: none;">
    <div class="mb-3">
        <label for="predecessor_project_id" class="form-label">Select Predecessor Project</label>
        <select name="predecessor_project_id" id="predecessor_project_id" class="form-control select-input">
            <option value="" selected>None</option>
            @foreach($developmentProjects as $project)
                <option value="{{ $project->project_id }}">{{ $project->project_title }} (Phase {{ $project->current_phase }}/{{ $project->overall_project_period }})</option>
            @endforeach
        </select>
    </div>
</div>
```

**New Code:**
```blade
<!-- Predecessor Project Selection (Always Visible for All Project Types) -->
<div class="mb-3">
    <label for="predecessor_project_id" class="form-label">Select Predecessor Project (Optional)</label>
    <select name="predecessor_project_id" id="predecessor_project_id" class="form-control select-input">
        <option value="" selected>None</option>
        @if(isset($developmentProjects) && $developmentProjects->count() > 0)
            @foreach($developmentProjects as $project)
                <option value="{{ $project->project_id }}" {{ old('predecessor_project_id') == $project->project_id ? 'selected' : '' }}>
                    {{ $project->project_title }} (Phase {{ $project->current_phase }}/{{ $project->overall_project_period }})
                </option>
            @endforeach
        @else
            <option value="" disabled>No development projects available</option>
        @endif
    </select>
    @error('predecessor_project_id')
        <span class="text-danger">{{ $message }}</span>
    @enderror
    <small class="form-text text-muted">Select a previous project if this is a continuation or related project.</small>
</div>
```

**JavaScript Changes (lines 164-261):**
Remove the conditional display logic. Update the script section:

```javascript
<script>
document.addEventListener('DOMContentLoaded', function () {
    const predecessorProjectDropdown = document.getElementById('predecessor_project_id');

    // Populate fields based on selected predecessor project
    if (predecessorProjectDropdown) {
        predecessorProjectDropdown.addEventListener('change', function () {
            const selectedProjectId = this.value;

            if (selectedProjectId) {
                const url = '/executor/projects/' + selectedProjectId + '/details';

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`Network response was not ok: ${response.status} - ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    // Populate form fields with predecessor project data
                    const fields = {
                        'project_title': data.project_title,
                        'society_name': data.society_name,
                        'president_name': data.president_name,
                        'applicant_name': data.applicant_name,
                        'applicant_mobile': data.applicant_mobile,
                        'applicant_email': data.applicant_email,
                        'in_charge': data.in_charge,
                        'in_charge_name': data.in_charge_name,
                        'in_charge_mobile': data.in_charge_mobile,
                        'in_charge_email': data.in_charge_email,
                        'full_address': data.full_address,
                        'overall_project_period': data.overall_project_period,
                        'current_phase': data.current_phase,
                        'commencement_month': data.commencement_month,
                        'commencement_year': data.commencement_year,
                        'overall_project_budget': data.overall_project_budget,
                        // Populate new Key Information fields
                        'initial_information': data.initial_information,
                        'target_beneficiaries': data.target_beneficiaries,
                        'general_situation': data.general_situation,
                        'need_of_project': data.need_of_project,
                        'goal': data.goal
                    };

                    for (const [id, value] of Object.entries(fields)) {
                        const element = document.getElementById(id);
                        if (element) {
                            element.value = value || '';
                        }
                    }

                    // Pass beneficiaries data to the parent view
                    window.predecessorBeneficiaries = data.beneficiaries_areas || [];

                    // Trigger an event to notify the parent view
                    const event = new CustomEvent('predecessorDataFetched', { detail: data });
                    document.dispatchEvent(event);
                })
                .catch(error => {
                    console.error('Error fetching predecessor project data:', error);
                    alert('Failed to fetch project details. Please try again.');
                });
            }
        });
    }
});
</script>
```

**Key Points:**
- Remove `id="predecessor-project-section"` and `style="display: none;"`
- Remove project type toggle logic
- Always show predecessor project selection
- Add null check for `$developmentProjects`
- Add `old()` helper for form repopulation
- Add error display
- Add helpful text
- Update JavaScript to populate new Key Information fields

---

## Phase 5: View Updates - Edit Forms

**Duration:** 2-3 hours  
**Priority:** HIGH

### Task 5.1: Update Key Information Partial (Edit)

**File to Modify:**
- `resources/views/projects/partials/Edit/key_information.blade.php`

**Current Content:**
```blade
<!-- resources/views/projects/partials/Edit/key_information.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Key Information</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project:</label>
            <textarea name="goal" id="goal" class="form-control" rows="3" >{{ $project->goal }}</textarea>
        </div>
    </div>
</div>
```

**New Content:**
```blade
<!-- resources/views/projects/partials/Edit/key_information.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Key Information</h4>
    </div>
    <div class="card-body">
        <!-- Initial Information -->
        <div class="mb-3">
            <label for="initial_information" class="form-label">Initial Information</label>
            <textarea name="initial_information" id="initial_information" class="form-control" rows="3">{{ old('initial_information', $project->initial_information) }}</textarea>
            @error('initial_information')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Target Beneficiaries -->
        <div class="mb-3">
            <label for="target_beneficiaries" class="form-label">Target Beneficiaries</label>
            <textarea name="target_beneficiaries" id="target_beneficiaries" class="form-control" rows="3">{{ old('target_beneficiaries', $project->target_beneficiaries) }}</textarea>
            @error('target_beneficiaries')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- General Situation -->
        <div class="mb-3">
            <label for="general_situation" class="form-label">General Situation</label>
            <textarea name="general_situation" id="general_situation" class="form-control" rows="3">{{ old('general_situation', $project->general_situation) }}</textarea>
            @error('general_situation')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Need of the Project -->
        <div class="mb-3">
            <label for="need_of_project" class="form-label">Need of the Project</label>
            <textarea name="need_of_project" id="need_of_project" class="form-control" rows="3">{{ old('need_of_project', $project->need_of_project) }}</textarea>
            @error('need_of_project')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Goal of the Project (Existing, Last) -->
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project</label>
            <textarea name="goal" id="goal" class="form-control" rows="3">{{ old('goal', $project->goal) }}</textarea>
            @error('goal')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>
```

**Key Points:**
- Use `old('field', $project->field)` for proper form repopulation
- Include error display for each field
- Maintain consistent styling
- All fields are optional (nullable)

### Task 5.2: Update General Info Partial (Edit - Predecessor Project)

**File to Modify:**
- `resources/views/projects/partials/Edit/general_info.blade.php`

**Action:** Add predecessor project selection field (similar to create form, but with existing value pre-selected).

**Add After Project Type Field:**
```blade
<!-- Predecessor Project Selection (Always Visible for All Project Types) -->
<div class="mb-3">
    <label for="predecessor_project_id" class="form-label">Select Predecessor Project (Optional)</label>
    <select name="predecessor_project_id" id="predecessor_project_id" class="form-control select-input">
        <option value="" {{ !$project->predecessor_project_id ? 'selected' : '' }}>None</option>
        @if(isset($developmentProjects) && $developmentProjects->count() > 0)
            @foreach($developmentProjects as $devProject)
                <option value="{{ $devProject->project_id }}" 
                    {{ old('predecessor_project_id', $project->predecessor_project_id) == $devProject->project_id ? 'selected' : '' }}>
                    {{ $devProject->project_title }} (Phase {{ $devProject->current_phase }}/{{ $devProject->overall_project_period }})
                </option>
            @endforeach
        @else
            <option value="" disabled>No development projects available</option>
        @endif
    </select>
    @error('predecessor_project_id')
        <span class="text-danger">{{ $message }}</span>
    @enderror
    <small class="form-text text-muted">Select a previous project if this is a continuation or related project.</small>
</div>
```

**Key Points:**
- Pre-select existing `predecessor_project_id` if set
- Use `old()` helper for validation error repopulation
- Include error display
- Add helpful text

---

## Phase 6: View Updates - Show/View Pages

**Duration:** 1-2 hours  
**Priority:** MEDIUM

### Task 6.1: Update Key Information Show Partial

**File to Modify:**
- `resources/views/projects/partials/Show/key_information.blade.php`

**Current Content:**
```blade
{{-- resources/views/projects/partials/show/key_information.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Key Information</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-label"><strong>Goal of the Project:</strong></div>
            <div class="info-value">{{ $project->goal }}</div>
        </div>
    </div>
</div>
```

**New Content:**
```blade
{{-- resources/views/projects/partials/Show/key_information.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Key Information</h4>
    </div>
    <div class="card-body">
        @if($project->initial_information)
            <div class="mb-3">
                <div class="info-label"><strong>Initial Information:</strong></div>
                <div class="info-value">{{ $project->initial_information }}</div>
            </div>
        @endif

        @if($project->target_beneficiaries)
            <div class="mb-3">
                <div class="info-label"><strong>Target Beneficiaries:</strong></div>
                <div class="info-value">{{ $project->target_beneficiaries }}</div>
            </div>
        @endif

        @if($project->general_situation)
            <div class="mb-3">
                <div class="info-label"><strong>General Situation:</strong></div>
                <div class="info-value">{{ $project->general_situation }}</div>
            </div>
        @endif

        @if($project->need_of_project)
            <div class="mb-3">
                <div class="info-label"><strong>Need of the Project:</strong></div>
                <div class="info-value">{{ $project->need_of_project }}</div>
            </div>
        @endif

        @if($project->goal)
            <div class="mb-3">
                <div class="info-label"><strong>Goal of the Project:</strong></div>
                <div class="info-value">{{ $project->goal }}</div>
            </div>
        @endif

        @if(!$project->initial_information && !$project->target_beneficiaries && !$project->general_situation && !$project->need_of_project && !$project->goal)
            <div class="text-muted">No key information provided yet.</div>
        @endif
    </div>
</div>
```

**Key Points:**
- Display fields only if they have values (conditional display)
- Maintain consistent styling with existing show pages
- Show "No key information provided yet" if all fields are empty
- Order matches form order

### Task 6.2: Update General Info Show Partial (Predecessor Project)

**File to Check:**
- `resources/views/projects/partials/Show/general_info.blade.php`

**Action:** Add predecessor project display if it exists.

**Add After Project Type Display:**
```blade
@if($project->predecessor)
    <div class="info-grid">
        <div class="info-label"><strong>Predecessor Project:</strong></div>
        <div class="info-value">
            <a href="{{ route('projects.show', $project->predecessor->project_id) }}">
                {{ $project->predecessor->project_title }} ({{ $project->predecessor->project_id }})
            </a>
        </div>
    </div>
@endif
```

**Key Points:**
- Only display if predecessor exists
- Link to predecessor project show page
- Show project title and ID

---

## Phase 7: Predecessor Project Selection Enhancement

**Duration:** 1-2 hours  
**Priority:** MEDIUM

### Task 7.1: Update ProjectController Details Endpoint

**File to Modify:**
- `app/Http/Controllers/Projects/ProjectController.php`

**Action:** Update the endpoint that returns project details for predecessor selection to include new Key Information fields.

**Find Method:** Look for route that returns project details (likely `/executor/projects/{project_id}/details`)

**Update Response:**
```php
// Ensure the response includes all Key Information fields
return response()->json([
    // ... existing fields ...
    'initial_information' => $project->initial_information,
    'target_beneficiaries' => $project->target_beneficiaries,
    'general_situation' => $project->general_situation,
    'need_of_project' => $project->need_of_project,
    'goal' => $project->goal,
    // ... rest of fields ...
]);
```

**Key Points:**
- Include all new Key Information fields in JSON response
- Ensure fields are included even if null (for JavaScript population)

---

## Phase 8: Validation Updates

**Duration:** 1 hour  
**Priority:** HIGH

### Task 8.1: Update FormRequest Classes

**Files to Modify:**
- `app/Http/Requests/Projects/StoreProjectRequest.php`
- `app/Http/Requests/Projects/UpdateProjectRequest.php`

**Add Validation Rules:**
```php
// In rules() method
'initial_information' => 'nullable|string',
'target_beneficiaries' => 'nullable|string',
'general_situation' => 'nullable|string',
'need_of_project' => 'nullable|string',
'goal' => 'nullable|string',
```

**Key Points:**
- All fields are nullable
- All fields are strings
- Add to both StoreProjectRequest and UpdateProjectRequest
- Predecessor project validation already exists (verify)

### Task 8.2: Verify Predecessor Project Validation

**Action:** Verify that predecessor project validation exists in FormRequest classes.

**Check:**
- ✅ `StoreProjectRequest` has `'predecessor_project' => 'nullable|string|exists:projects,project_id'`
- ✅ `UpdateProjectRequest` has `'predecessor_project' => 'nullable|string|exists:projects,project_id'`
- ✅ Custom error messages exist

**No changes needed** - Validation already exists.

---

## Phase 9: Testing

**Duration:** 4-6 hours  
**Priority:** CRITICAL

### Task 9.1: Database Testing

**Test Cases:**
1. ✅ Run migration successfully
2. ✅ Verify columns exist in correct order
3. ✅ Verify columns are nullable
4. ✅ Test rollback migration
5. ✅ Verify foreign key constraint for predecessor_project_id

### Task 9.2: Create Form Testing

**Test Cases:**
1. ✅ Create project with all Key Information fields filled
2. ✅ Create project with some Key Information fields filled (save draft scenario)
3. ✅ Create project with no Key Information fields (save draft scenario)
4. ✅ Create project with predecessor project selected
5. ✅ Create project without predecessor project
6. ✅ Verify predecessor project populates form fields including new Key Information fields
7. ✅ Test validation errors display correctly
8. ✅ Test form repopulation on validation errors

### Task 9.3: Edit Form Testing

**Test Cases:**
1. ✅ Edit project and update all Key Information fields
2. ✅ Edit project and update some Key Information fields
3. ✅ Edit project and clear Key Information fields
4. ✅ Edit project and change predecessor project
5. ✅ Edit project and remove predecessor project
6. ✅ Verify existing values are pre-populated
7. ✅ Test validation errors display correctly
8. ✅ Test form repopulation on validation errors

### Task 9.4: Show/View Page Testing

**Test Cases:**
1. ✅ View project with all Key Information fields filled
2. ✅ View project with some Key Information fields filled
3. ✅ View project with no Key Information fields
4. ✅ View project with predecessor project
5. ✅ View project without predecessor project
6. ✅ Verify predecessor project link works
7. ✅ Verify conditional display works (only show fields with values)

### Task 9.5: Cross-Project Type Testing

**Test Cases:**
1. ✅ Test with all institutional project types
2. ✅ Test with all individual project types
3. ✅ Verify predecessor selection works for all types
4. ✅ Verify Key Information fields work for all types

### Task 9.6: Save Draft Testing

**Test Cases:**
1. ✅ Save draft with empty Key Information fields
2. ✅ Save draft with partial Key Information fields
3. ✅ Save draft with all Key Information fields
4. ✅ Verify draft can be edited and completed later

---

## Phase 10: Documentation

**Duration:** 1-2 hours  
**Priority:** MEDIUM

### Task 10.1: Update User Documentation

**Action:** Document new Key Information fields and predecessor project selection in user guides.

**Content:**
- Explain purpose of each Key Information field
- Explain predecessor project selection
- Provide examples
- Document save draft functionality

### Task 10.2: Update Developer Documentation

**Action:** Document implementation in developer documentation.

**Content:**
- Database schema changes
- Controller changes
- View changes
- Validation rules
- Testing procedures

---

## Implementation Timeline

### Week 1: Foundation
- **Day 1-2:** Phase 1 (Database Migration) + Phase 2 (Model Updates)
- **Day 3-4:** Phase 3 (Controller Updates)
- **Day 5:** Phase 8 (Validation Updates)

### Week 2: Views
- **Day 1-2:** Phase 4 (Create Forms)
- **Day 3-4:** Phase 5 (Edit Forms)
- **Day 5:** Phase 6 (Show/View Pages)

### Week 3: Enhancement & Testing
- **Day 1:** Phase 7 (Predecessor Project Enhancement)
- **Day 2-4:** Phase 9 (Testing)
- **Day 5:** Phase 10 (Documentation)

**Total Estimated Duration:** 15-20 working days

---

## Risk Assessment

### Low Risk
- Database migration (straightforward)
- Model updates (standard Laravel patterns)
- View updates (Blade template changes)

### Medium Risk
- Controller updates (ensure all project types work)
- JavaScript updates (predecessor project population)
- Testing across all project types

### Mitigation Strategies
- Test each phase thoroughly before moving to next
- Create backup before running migration
- Test with sample data for all project types
- Get user feedback during testing phase

---

## Success Criteria

### Functional Requirements
- ✅ All five Key Information fields are available in create and edit forms
- ✅ Predecessor project selection is available for all project types
- ✅ Save draft functionality works with empty/nullable fields
- ✅ All fields display correctly in show/view pages
- ✅ Predecessor project populates all form fields including new Key Information fields

### Technical Requirements
- ✅ Database migration runs successfully
- ✅ All fields are nullable
- ✅ Validation works correctly
- ✅ Form repopulation works on validation errors
- ✅ No breaking changes to existing functionality

### User Experience Requirements
- ✅ Forms are intuitive and easy to use
- ✅ Fields are clearly labeled
- ✅ Error messages are helpful
- ✅ Predecessor project selection is clearly explained

---

## Dependencies

### Required Before Starting
- ✅ Access to database
- ✅ Development environment set up
- ✅ Understanding of existing codebase structure
- ✅ Backup of current database

### External Dependencies
- None

---

## Notes

1. **Backward Compatibility:** All new fields are nullable, so existing projects will continue to work without issues.

2. **Data Migration:** No data migration needed - existing projects will have NULL values for new fields, which is acceptable.

3. **Performance:** Adding four new text fields should have minimal performance impact. TEXT columns are efficient in MySQL/MariaDB.

4. **Future Enhancements:** Consider adding rich text editor (WYSIWYG) for Key Information fields in future if needed.

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Ready for Implementation

---

**End of Implementation Plan**

