# Project Edit - Overall Project Period & Current Phase Data Fetch Audit

**Date:** February 28, 2026  
**Module:** Project Edit - General Information Section  
**Issue:** Overall Project Period and Current Phase fields not fetching data from database  
**Status:** ⚠️ DISCREPANCY CONFIRMED

---

## Executive Summary

The edit form for projects displays the **Overall Project Period (Years)** and **Current Phase** fields in the General Information section (1. Basic Information). Upon investigation, these fields are **correctly fetching data from the database**, but there is a **JavaScript initialization issue** and a **potential data type casting problem** that could cause the fields to appear as if defaulting to Phase 1.

---

## Investigation Findings

### 1. Data Flow Analysis

#### Controller: `ProjectController@edit()`
**Location:** `app/Http/Controllers/Projects/ProjectController.php:1055-1333`

**Data Retrieval:**
```php
public function edit($project_id)
{
    // Line 1061-1063: Project fetched with relationships
    $project = Project::where('project_id', $project_id)
        ->with('budgets', 'attachments', 'objectives', 'sustainabilities')
        ->firstOrFail();
    
    // Line 1303: Project passed to view
    return view('projects.Oldprojects.edit', compact(
        'project', 'developmentProjects', 'user', 'users', /* ... */
    ));
}
```

**✅ Status:** Controller correctly fetches the project and passes it to the view.

---

### 2. View Template Analysis

#### View: `resources/views/projects/partials/Edit/general_info.blade.php`

**Overall Project Period Field (Lines 193-205):**
```blade
<div class="mb-3">
    <label for="overall_project_period" class="form-label">Overall Project Period (Years)</label>
    <select name="overall_project_period" id="overall_project_period"
            class="form-control select-input">
        <option value="" disabled>Select Period</option>
        @for($i=1; $i<=4; $i++)
            <option value="{{ $i }}" {{ (int)old('overall_project_period', $project->overall_project_period) === $i ? 'selected' : '' }}>
                {{ $i }} Year{{ $i > 1 ? 's' : '' }}
            </option>
        @endfor
    </select>
</div>
```

**Current Phase Field (Lines 207-225):**
```blade
<div class="mb-3">
    <label for="current_phase" class="form-label">Current Phase</label>
    <select name="current_phase" id="current_phase"
            class="form-control select-input" >
        <option value="" disabled>Select Phase</option>
        @php
            $selectedPeriod = (int)old('overall_project_period', $project->overall_project_period);
            // If there's no explicit overall_project_period, default to 4
            $limit = $selectedPeriod > 0 ? $selectedPeriod : 4;
        @endphp
        @for($phase = 1; $phase <= $limit; $phase++)
            <option value="{{ $phase }}"
                {{ (int)old('current_phase', $project->current_phase) === $phase ? 'selected' : '' }}>
                Phase {{ $phase }}
            </option>
        @endfor
    </select>
</div>
```

**✅ Status:** Blade template correctly uses `$project->overall_project_period` and `$project->current_phase` with proper type casting.

---

### 3. JavaScript Initialization Issue

**Location:** `resources/views/projects/partials/Edit/general_info.blade.php:348-390`

```javascript
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const overallProjectPeriodSelect = document.getElementById('overall_project_period');
        const currentPhaseSelect = document.getElementById('current_phase');

        // ...

        // 1. Update the Current Phase dropdown based on Overall Project Period
        function updatePhaseOptions() {
            const projectPeriod = parseInt(overallProjectPeriodSelect.value) || 0;
            currentPhaseSelect.innerHTML = '<option value="" disabled>Select Phase</option>';
            for (let i = 1; i <= projectPeriod; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `Phase ${i}`;
                currentPhaseSelect.appendChild(option);
            }
            // If there was a previously selected phase, you can re-set it here if needed
        }

        // ...

        // Event Listeners
        overallProjectPeriodSelect.addEventListener('change', updatePhaseOptions);
        inChargeSelect.addEventListener('change', handleInChargeChange);

        // Initialize on page load
        updatePhaseOptions();  // ⚠️ ISSUE: This clears the phase selection on page load!
        // If the in_charge is already selected, fill phone & email accordingly
        handleInChargeChange();

    });
</script>
```

**❌ PROBLEM IDENTIFIED:**

The `updatePhaseOptions()` function is called on page load (line 386), which:
1. **Clears** the `currentPhaseSelect.innerHTML` 
2. **Regenerates** the phase options dynamically
3. **Does NOT preserve** the previously selected phase value from the database

This means even though the Blade template correctly sets the `selected` attribute on the current phase option, the JavaScript immediately overwrites it by regenerating the dropdown without preserving the selection.

---

### 4. Data Type Casting Analysis

**Database Schema:**
```php
// database/migrations/2024_07_20_085634_create_projects_table.php
$table->integer('overall_project_period')->nullable();
$table->integer('current_phase')->nullable();
```

**Blade Comparison:**
```blade
{{ (int)old('overall_project_period', $project->overall_project_period) === $i ? 'selected' : '' }}
{{ (int)old('current_phase', $project->current_phase) === $phase ? 'selected' : '' }}
```

**✅ Status:** Strict type comparison (`===`) is used with proper integer casting, which is correct.

---

### 5. Potential Issues

#### Issue #1: JavaScript Overwrites Server-Side Selection ⚠️
**Severity:** HIGH  
**Impact:** The current phase selection is lost when the page loads

**Root Cause:**
- The `updatePhaseOptions()` function regenerates the phase dropdown on page load
- It does not preserve the currently selected phase from the database
- This causes the dropdown to appear as if no phase is selected (or defaults to the first option)

**Code Location:** Lines 348-390 in `general_info.blade.php`

---

#### Issue #2: Empty/Null Values Not Handled 
**Severity:** MEDIUM  
**Impact:** If `overall_project_period` or `current_phase` is NULL in the database, the comparison may fail

**Observations:**
- The `$limit` variable defaults to 4 if `overall_project_period` is 0 or NULL (line 216)
- However, if both fields are NULL, the "Select Phase" option remains selected
- This is actually correct behavior for new projects, but could be confusing for edits

**Code Location:** Lines 213-217 in `general_info.blade.php`

---

#### Issue #3: Type Coercion in JavaScript
**Severity:** LOW  
**Impact:** Potential mismatch between string and integer values

**Observation:**
```javascript
const projectPeriod = parseInt(overallProjectPeriodSelect.value) || 0;
```

The JavaScript uses `parseInt()` but doesn't preserve the selected phase when regenerating options.

---

## Root Cause Summary

### Primary Issue: JavaScript Initialization Overwrites Database Values

The `updatePhaseOptions()` function is called on page load without preserving the current selection:

```javascript
// Line 386 - Called on page load
updatePhaseOptions();
```

This function clears and rebuilds the phase dropdown but **does not restore the previously selected phase value** from the database.

### Secondary Issue: Missing Phase Preservation Logic

The comment on line 368 acknowledges this issue but doesn't implement a solution:

```javascript
// If there was a previously selected phase, you can re-set it here if needed
```

---

## Visual Evidence of Issue

### Expected Behavior:
1. User opens edit page for a project in Phase 3 of 4 years
2. "Overall Project Period" dropdown shows "4 Years" selected ✅
3. "Current Phase" dropdown shows "Phase 3" selected ✅

### Actual Behavior:
1. User opens edit page for a project in Phase 3 of 4 years
2. "Overall Project Period" dropdown shows "4 Years" selected ✅
3. "Current Phase" dropdown shows "Select Phase" or "Phase 1" (default) ❌

---

## Code Locations

| Component | File | Lines | Status |
|-----------|------|-------|--------|
| Controller | `app/Http/Controllers/Projects/ProjectController.php` | 1055-1333 | ✅ Correct |
| View Template | `resources/views/projects/partials/Edit/general_info.blade.php` | 193-225 | ✅ Correct |
| JavaScript | `resources/views/projects/partials/Edit/general_info.blade.php` | 348-390 | ❌ **Issue** |
| Database Schema | `database/migrations/2024_07_20_085634_create_projects_table.php` | 30-31 | ✅ Correct |

---

## Database Schema Verification

```sql
-- Table: projects
-- Columns:
overall_project_period INT(11) NULL DEFAULT NULL
current_phase INT(11) NULL DEFAULT NULL

-- These columns exist and store data correctly
-- No schema issues identified
```

---

## Recommended Solutions (Not Implemented)

### Solution 1: Preserve Selected Phase in JavaScript (Recommended)

**Modify the `updatePhaseOptions()` function:**

```javascript
function updatePhaseOptions() {
    const projectPeriod = parseInt(overallProjectPeriodSelect.value) || 0;
    const currentSelectedPhase = currentPhaseSelect.value; // Store current selection
    
    currentPhaseSelect.innerHTML = '<option value="" disabled>Select Phase</option>';
    for (let i = 1; i <= projectPeriod; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = `Phase ${i}`;
        
        // Restore the selection if it matches
        if (i.toString() === currentSelectedPhase) {
            option.selected = true;
        }
        
        currentPhaseSelect.appendChild(option);
    }
}
```

---

### Solution 2: Only Update Phase Options on User Change

**Modify event listeners:**

```javascript
// Remove the automatic call on page load
// updatePhaseOptions();  // Remove this line

// Only update phases when user changes the period
overallProjectPeriodSelect.addEventListener('change', updatePhaseOptions);
```

**Note:** This solution assumes the server-side rendering already generates the correct phase options, which it does.

---

### Solution 3: Add Data Attributes for JavaScript State

**In the Blade template:**

```blade
<select name="current_phase" id="current_phase"
        class="form-control select-input"
        data-initial-phase="{{ $project->current_phase }}"
        data-initial-period="{{ $project->overall_project_period }}">
```

**In JavaScript:**

```javascript
const initialPhase = currentPhaseSelect.dataset.initialPhase;
// Use initialPhase to restore selection after regeneration
```

---

## Testing Recommendations

### Test Case 1: Edit Project with Phase Data
1. Create/Find a project with `overall_project_period = 3` and `current_phase = 2`
2. Navigate to the edit page
3. **Expected:** Both dropdowns show correct values ("3 Years" and "Phase 2")
4. **Current:** Overall period is correct, but current phase may show "Select Phase"

### Test Case 2: Edit Project with NULL Phase Data
1. Create a project with NULL values for both fields
2. Navigate to the edit page
3. **Expected:** Both dropdowns show "Select Period" and "Select Phase"
4. Verify this behavior is consistent

### Test Case 3: Change Overall Period Dynamically
1. Open edit page for any project
2. Change the "Overall Project Period" dropdown
3. **Expected:** The "Current Phase" dropdown regenerates with correct number of phases
4. **Current:** This works correctly

---

## Additional Observations

### Commented-Out Legacy Code

The file contains **three versions** of similar code (lines 392-605), all commented out. This suggests:
- Multiple iterations of the same functionality
- Potential confusion about which version is active
- Risk of accidental code duplication

**Recommendation (Not Implemented):** Clean up commented code to reduce file complexity.

---

## Impact Assessment

### User Impact: MEDIUM-HIGH
- Users editing projects will see incorrect phase selections
- Users may accidentally change the phase when saving
- Data integrity is at risk if users don't notice the discrepancy

### Data Integrity: LOW
- Database values are correct
- Issue is purely in the UI rendering
- If users don't interact with the phase field, no data corruption occurs

### Frequency: HIGH
- Affects all project edits
- Affects all project types
- Visible to all roles (executor, coordinator, provincial, admin)

---

## Conclusion

The **discrepancy exists** and is caused by a **JavaScript initialization issue** that overwrites the server-side rendered selection. The controller and Blade template are functioning correctly, but the JavaScript `updatePhaseOptions()` function clears the phase dropdown on page load without preserving the database value.

**Severity:** Medium-High  
**Recommendation:** Implement Solution 1 or Solution 2 to preserve the selected phase value.

---

## Related Files

1. **Controller:** `app/Http/Controllers/Projects/ProjectController.php`
2. **View:** `resources/views/projects/Oldprojects/edit.blade.php`
3. **Partial:** `resources/views/projects/partials/Edit/general_info.blade.php`
4. **Model:** `app/Models/OldProjects/Project.php`
5. **Migration:** `database/migrations/2024_07_20_085634_create_projects_table.php`

---

**End of Audit Report**
