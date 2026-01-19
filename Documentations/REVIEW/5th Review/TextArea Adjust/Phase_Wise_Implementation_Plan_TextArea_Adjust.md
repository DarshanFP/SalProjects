# Phase-Wise Implementation Plan: TextArea Adjust (Text Wrap & Dynamic Height)

## Overview

This document outlines the phase-wise implementation plan for ensuring ALL textareas across the entire codebase have:
- Text wrap enabled
- Dynamic height adjustment to accommodate content
- No scrollbar by default (only on focus if content is very long)
- Consistent styling matching projects create/edit partials

**Total Estimated Time:** 10-13 days  
**Priority:** High  
**Target:** All textareas across projects, reports, comments, and provincial modules

---

## Standards Reference (Projects Create/Edit Partials)

### CSS Class Pattern
- **`sustainability-textarea`** - For general textareas
- **`logical-textarea`** - For textareas in structured tables/forms

### CSS Implementation
```css
.sustainability-textarea,
.logical-textarea {
    resize: vertical;
    min-height: 80px;
    height: auto;
    overflow-y: hidden;
    line-height: 1.5;
    padding: 8px 12px;
    word-wrap: break-word;
    white-space: pre-wrap;
}

.sustainability-textarea:focus,
.logical-textarea:focus {
    overflow-y: auto;
}
```

### JavaScript Implementation
```javascript
document.addEventListener('DOMContentLoaded', function() {
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }
    
    const textareas = document.querySelectorAll('.sustainability-textarea, .logical-textarea');
    textareas.forEach(textarea => {
        autoResizeTextarea(textarea);
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });
});
```

---

## Phase 0: Global Setup (Day 1)

### Objective
Create global CSS and JavaScript files for textarea auto-resize functionality to avoid code duplication.

### Tasks

#### Task 0.1: Create Global CSS File
**File:** `public/css/custom/textarea-auto-resize.css`  
**Estimated Time:** 1 hour

**Implementation:**
```css
/* Global TextArea Auto-Resize Styles */
.sustainability-textarea,
.logical-textarea,
.auto-resize-textarea {
    resize: vertical;
    min-height: 80px;
    height: auto;
    overflow-y: hidden;
    line-height: 1.5;
    padding: 8px 12px;
    word-wrap: break-word;
    white-space: pre-wrap;
}

.sustainability-textarea:focus,
.logical-textarea:focus,
.auto-resize-textarea:focus {
    overflow-y: auto;
}

/* For readonly textareas (still need wrap) */
.sustainability-textarea[readonly],
.logical-textarea[readonly],
.auto-resize-textarea[readonly] {
    background-color: #e9ecef;
    cursor: not-allowed;
}
```

**Testing:**
- [ ] Verify CSS loads correctly
- [ ] Test styles in browser dev tools

---

#### Task 0.2: Create Global JavaScript File
**File:** `public/js/textarea-auto-resize.js`  
**Estimated Time:** 2 hours

**Implementation:**
```javascript
/**
 * Global TextArea Auto-Resize Functionality
 * Applies to all textareas with class: sustainability-textarea, logical-textarea, or auto-resize-textarea
 */

(function() {
    'use strict';

    /**
     * Auto-resize a single textarea
     * @param {HTMLTextAreaElement} textarea
     */
    function autoResizeTextarea(textarea) {
        if (!textarea) return;
        
        // Reset height to auto to get correct scrollHeight
        textarea.style.height = 'auto';
        
        // Set height to scrollHeight
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }

    /**
     * Initialize auto-resize for a textarea
     * @param {HTMLTextAreaElement} textarea
     */
    function initTextareaAutoResize(textarea) {
        if (!textarea) return;
        
        // Set initial height
        autoResizeTextarea(textarea);
        
        // Auto-resize on input
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
        
        // Auto-resize on paste (with slight delay for content to be inserted)
        textarea.addEventListener('paste', function() {
            setTimeout(() => {
                autoResizeTextarea(this);
            }, 10);
        });
    }

    /**
     * Initialize all textareas with auto-resize classes
     */
    function initAllTextareas() {
        const textareas = document.querySelectorAll('.sustainability-textarea, .logical-textarea, .auto-resize-textarea');
        textareas.forEach(textarea => {
            initTextareaAutoResize(textarea);
        });
    }

    /**
     * Initialize textarea when added dynamically
     * @param {HTMLElement} container - Container where new textarea was added
     */
    function initDynamicTextarea(container) {
        const textareas = container.querySelectorAll('.sustainability-textarea, .logical-textarea, .auto-resize-textarea');
        textareas.forEach(textarea => {
            initTextareaAutoResize(textarea);
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllTextareas);
    } else {
        initAllTextareas();
    }

    // Make functions globally available for dynamic additions
    window.initTextareaAutoResize = initTextareaAutoResize;
    window.initDynamicTextarea = initDynamicTextarea;
    window.autoResizeTextarea = autoResizeTextarea;
})();
```

**Testing:**
- [ ] Verify JavaScript loads correctly
- [ ] Test auto-resize on input
- [ ] Test auto-resize on paste
- [ ] Test initial height calculation

---

#### Task 0.3: Include Global Files in Main Layout
**File:** Check main layout file (likely `resources/views/layouts/app.blade.php` or similar)  
**Estimated Time:** 30 minutes

**Changes:**
1. Add CSS link to `<head>`:
```blade
<link rel="stylesheet" href="{{ asset('css/custom/textarea-auto-resize.css') }}">
```

2. Add JavaScript before closing `</body>`:
```blade
<script src="{{ asset('js/textarea-auto-resize.js') }}"></script>
```

**Testing:**
- [ ] Verify CSS loads on all pages
- [ ] Verify JavaScript loads on all pages
- [ ] Check no console errors

---

#### Task 0.4: Update Projects Partial Scripts (Reference Implementation)
**Files:**
- `resources/views/projects/partials/scripts.blade.php`
- `resources/views/projects/partials/scripts-edit.blade.php`

**Estimated Time:** 1 hour

**Changes:**
1. Update `addAttachment()` function to apply auto-resize to new textareas
2. Update `addTimeFrameRow()` function to apply auto-resize to new textareas
3. Use global `initDynamicTextarea()` function

**Implementation Example:**
```javascript
function addAttachment() {
    // ... existing code to create attachment HTML ...
    
    // After inserting HTML, initialize textarea auto-resize
    const newAttachment = attachmentsContainer.lastElementChild;
    if (newAttachment) {
        initDynamicTextarea(newAttachment);
    }
}
```

**Testing:**
- [ ] Test adding attachments in projects create form
- [ ] Test adding attachments in projects edit form
- [ ] Verify textareas auto-resize correctly

---

## Phase 1: Monthly Reports Module (Days 2-4)

### Objective
Apply textarea auto-resize to all monthly reports forms (create and edit).

### Tasks

#### Task 1.1: Monthly Reports - Create Form
**File:** `resources/views/reports/monthly/ReportCommonForm.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Add class `auto-resize-textarea` to `plan_next_month[]` textarea
2. Remove inline styles if present
3. Ensure proper word wrap

**Implementation:**
```blade
<textarea name="plan_next_month[{{ $index }}]" 
          class="form-control auto-resize-textarea" 
          rows="3" 
          style="background-color: #202ba3;">{{ old("plan_next_month.$index") }}</textarea>
```

**Testing:**
- [ ] Test textarea in outlook section
- [ ] Verify auto-resize works
- [ ] Test adding multiple outlook entries

---

#### Task 1.2: Monthly Reports - Create Objectives Partial
**File:** `resources/views/reports/monthly/partials/create/objectives.blade.php`  
**Estimated Time:** 4 hours

**Changes:**
1. Add class `auto-resize-textarea` to all textareas:
   - `objective[]` (readonly, but still needs wrap)
   - `expected_outcome[][]` (readonly, but still needs wrap)
   - `activity[][]` (readonly, but still needs wrap)
   - `summary_activities[][]` (editable)
   - `qualitative_quantitative_data[][]` (editable)
   - `intermediate_outcomes[][]` (editable)
   - `not_happened[]` (editable)
   - `why_not_happened[]` (editable)
   - `why_changes[]` (conditional, editable)
   - `additional_notes[]` (editable)

2. Update JavaScript functions that add activities dynamically:
   - `addActivity(index)` - Initialize textarea auto-resize after adding
   - Use `initDynamicTextarea()` function

**Implementation Example:**
```blade
<!-- For editable textareas -->
<textarea name="summary_activities[{{ $index }}][{{ $activityIndex }}][1]" 
          class="form-control activity-field auto-resize-textarea" 
          rows="3">{{ old("summary_activities.$index.$activityIndex.1", $activity->summary_activities) }}</textarea>

<!-- For readonly textareas -->
<textarea name="objective[{{ $index }}]" 
          class="form-control auto-resize-textarea" 
          rows="2" 
          readonly>{{ old("objective.$index", $objective->objective) }}</textarea>
```

**JavaScript Update:**
```javascript
function addActivity(objectiveIndex) {
    // ... existing code to create activity HTML ...
    
    // After inserting HTML, initialize textarea auto-resize
    const newActivity = activityContainer.lastElementChild;
    if (newActivity) {
        initDynamicTextarea(newActivity);
    }
}
```

**Testing:**
- [ ] Test all textarea fields in objectives section
- [ ] Test adding new activities
- [ ] Verify readonly textareas still wrap correctly
- [ ] Test conditional `why_changes` textarea

---

#### Task 1.3: Monthly Reports - Create Photos Partial
**File:** `resources/views/reports/monthly/partials/create/photos.blade.php`  
**Estimated Time:** 1 hour

**Changes:**
1. Add class `auto-resize-textarea` to `photo_descriptions[]` textarea
2. Update `addPhoto()` JavaScript function to initialize auto-resize

**Implementation:**
```blade
<textarea name="photo_descriptions[]" 
          class="form-control auto-resize-textarea" 
          rows="3" 
          placeholder="Brief Description (WHO WHERE WHAT WHEN)" 
          style="background-color: #202ba3;">{{ $value }}</textarea>
```

**JavaScript Update:**
```javascript
function addPhoto() {
    // ... existing code to create photo HTML ...
    
    // After inserting HTML, initialize textarea auto-resize
    const newPhoto = photosContainer.lastElementChild;
    if (newPhoto) {
        initDynamicTextarea(newPhoto);
    }
}
```

**Testing:**
- [ ] Test photo description textarea
- [ ] Test adding multiple photos
- [ ] Verify auto-resize works

---

#### Task 1.4: Monthly Reports - Create Attachments Partial
**File:** `resources/views/reports/monthly/partials/create/attachments.blade.php`  
**Estimated Time:** 1.5 hours

**Changes:**
1. Add class `auto-resize-textarea` to `attachment_descriptions[]` textarea
2. Update `addAttachment()` JavaScript function to initialize auto-resize
3. Update `reindexAttachments()` to maintain auto-resize

**Implementation:**
```blade
<textarea name="attachment_descriptions[]" 
          id="attachment_description_{{ $index }}"
          class="form-control auto-resize-textarea" 
          rows="2"
          placeholder="Brief description">{{ old("attachment_descriptions.$index") }}</textarea>
```

**JavaScript Update:**
```javascript
function addAttachment() {
    // ... existing code ...
    
    // After inserting HTML, initialize textarea auto-resize
    const newAttachment = attachmentsContainer.lastElementChild;
    if (newAttachment) {
        initDynamicTextarea(newAttachment);
    }
}

// In reindexAttachments(), re-initialize textareas after reindexing
function reindexAttachments() {
    // ... existing reindexing code ...
    
    // Re-initialize all textareas after reindexing
    document.querySelectorAll('#attachments-container .auto-resize-textarea').forEach(textarea => {
        autoResizeTextarea(textarea);
    });
}
```

**Testing:**
- [ ] Test attachment description textarea
- [ ] Test adding multiple attachments
- [ ] Test removing attachments (reindexing)
- [ ] Verify auto-resize maintained after reindexing

---

#### Task 1.5: Monthly Reports - Create Livelihood Annexure
**File:** `resources/views/reports/monthly/partials/create/LivelihoodAnnexure.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Review file for all textarea fields
2. Add class `auto-resize-textarea` to all textareas
3. Update any dynamic JavaScript functions

**Testing:**
- [ ] Test all textarea fields in livelihood annexure
- [ ] Verify auto-resize works

---

#### Task 1.6: Monthly Reports - Edit Form (Repeat Tasks 1.1-1.5)
**Files:**
- `resources/views/reports/monthly/edit.blade.php`
- `resources/views/reports/monthly/partials/edit/objectives.blade.php`
- `resources/views/reports/monthly/partials/edit/photos.blade.php`
- `resources/views/reports/monthly/partials/edit/attachments.blade.php`
- `resources/views/reports/monthly/partials/edit/LivelihoodAnnexure.blade.php`

**Estimated Time:** 5 hours

**Changes:**
1. Apply same changes as create forms
2. Ensure existing values load with correct height

**Testing:**
- [ ] Test all edit forms
- [ ] Verify existing data loads with correct height
- [ ] Test all dynamic additions

---

#### Task 1.7: Monthly Reports - Comments Partial
**File:** `resources/views/reports/monthly/partials/comments.blade.php`  
**Estimated Time:** 30 minutes

**Changes:**
1. Add class `auto-resize-textarea` to comment textareas
2. Ensure proper styling

**Testing:**
- [ ] Test comment textareas
- [ ] Verify auto-resize works

---

## Phase 2: Quarterly Reports Module (Days 5-7)

### Objective
Apply textarea auto-resize to all quarterly report forms.

### Tasks

#### Task 2.1: Development Project Report
**File:** `resources/views/reports/quarterly/developmentProject/reportform.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Review file and identify all textarea fields
2. Add class `auto-resize-textarea` to all textareas
3. Update any dynamic JavaScript functions

**Testing:**
- [ ] Test all textarea fields
- [ ] Verify auto-resize works

---

#### Task 2.2: Development Livelihood Report
**File:** `resources/views/reports/quarterly/developmentLivelihood/reportform.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Review file and identify all textarea fields
2. Add class `auto-resize-textarea` to all textareas
3. Update any dynamic JavaScript functions

**Testing:**
- [ ] Test all textarea fields
- [ ] Verify auto-resize works

---

#### Task 2.3: Skill Training Report
**File:** `resources/views/reports/quarterly/skillTraining/reportform.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Review file and identify all textarea fields
2. Add class `auto-resize-textarea` to all textareas
3. Update any dynamic JavaScript functions

**Testing:**
- [ ] Test all textarea fields
- [ ] Verify auto-resize works

---

#### Task 2.4: Women in Distress Report
**File:** `resources/views/reports/quarterly/womenInDistress/reportform.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Review file and identify all textarea fields
2. Add class `auto-resize-textarea` to all textareas
3. Update any dynamic JavaScript functions

**Testing:**
- [ ] Test all textarea fields
- [ ] Verify auto-resize works

---

#### Task 2.5: Institutional Support Report
**File:** `resources/views/reports/quarterly/institutionalSupport/reportform.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Review file and identify all textarea fields
2. Add class `auto-resize-textarea` to all textareas
3. Update any dynamic JavaScript functions

**Testing:**
- [ ] Test all textarea fields
- [ ] Verify auto-resize works

---

## Phase 3: Aggregated Reports Module (Days 8-9)

### Objective
Apply textarea auto-resize to aggregated reports AI edit forms.

### Tasks

#### Task 3.1: Annual Aggregated Report AI Edit
**File:** `resources/views/reports/aggregated/annual/edit-ai.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Add class `auto-resize-textarea` to `executive_summary` textarea
2. Note: Hidden textareas for JSON (impact_assessment, budget_performance, future_outlook) use CodeMirror editor
   - May need special handling or can be skipped if CodeMirror handles wrapping

**Implementation:**
```blade
<textarea name="executive_summary" 
          id="executive_summary" 
          class="form-control auto-resize-textarea" 
          rows="6">{{ old('executive_summary', $report->aiInsights->executive_summary ?? '') }}</textarea>
```

**Testing:**
- [ ] Test executive_summary textarea
- [ ] Verify auto-resize works
- [ ] Check if JSON editor textareas need special handling

---

#### Task 3.2: Quarterly Aggregated Report AI Edit
**File:** `resources/views/reports/aggregated/quarterly/edit-ai.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Same as Task 3.1

**Testing:**
- [ ] Test all textarea fields
- [ ] Verify auto-resize works

---

#### Task 3.3: Half-Yearly Aggregated Report AI Edit
**File:** `resources/views/reports/aggregated/half-yearly/edit-ai.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Same as Task 3.1

**Testing:**
- [ ] Test all textarea fields
- [ ] Verify auto-resize works

---

## Phase 4: Projects Comments Module (Day 10)

### Objective
Apply textarea auto-resize to project comments forms.

### Tasks

#### Task 4.1: Project Comments Edit
**File:** `resources/views/projects/comments/edit.blade.php`  
**Estimated Time:** 1 hour

**Changes:**
1. Add class `auto-resize-textarea` to comment textarea

**Implementation:**
```blade
<textarea name="comment" 
          id="comment" 
          class="form-control auto-resize-textarea" 
          rows="3" 
          required>{{ old('comment', $comment->comment) }}</textarea>
```

**Testing:**
- [ ] Test comment textarea
- [ ] Verify auto-resize works

---

#### Task 4.2: Project Comments Partial
**File:** `resources/views/projects/partials/ProjectComments.blade.php`  
**Estimated Time:** 1 hour

**Changes:**
1. Review file and identify all textarea fields
2. Add class `auto-resize-textarea` to all textareas

**Testing:**
- [ ] Test all comment textareas
- [ ] Verify auto-resize works

---

## Phase 5: Provincial Module (Day 11)

### Objective
Apply textarea auto-resize to provincial module forms.

### Tasks

#### Task 5.1: Create Executor Form
**File:** `resources/views/provincial/createExecutor.blade.php`  
**Estimated Time:** 1 hour

**Changes:**
1. Add class `auto-resize-textarea` to address textarea

**Implementation:**
```blade
<textarea class="form-control auto-resize-textarea" 
          id="address" 
          name="address"></textarea>
```

**Testing:**
- [ ] Test address textarea
- [ ] Verify auto-resize works

---

#### Task 5.2: Provincial Pending Reports
**File:** `resources/views/provincial/pendingReports.blade.php`  
**Estimated Time:** 30 minutes

**Changes:**
1. Review file for any textarea fields
2. Add class if textareas found

**Testing:**
- [ ] Test any textarea fields found

---

#### Task 5.3: Provincial Report List
**File:** `resources/views/provincial/ReportList.blade.php`  
**Estimated Time:** 30 minutes

**Changes:**
1. Review file for any textarea fields
2. Add class if textareas found

**Testing:**
- [ ] Test any textarea fields found

---

#### Task 5.4: Coordinator Pending Reports
**File:** `resources/views/coordinator/pendingReports.blade.php`  
**Estimated Time:** 30 minutes

**Changes:**
1. Review file for any textarea fields
2. Add class if textareas found

**Testing:**
- [ ] Test any textarea fields found

---

#### Task 5.5: Coordinator Report List
**File:** `resources/views/coordinator/ReportList.blade.php`  
**Estimated Time:** 30 minutes

**Changes:**
1. Review file for any textarea fields
2. Add class if textareas found

**Testing:**
- [ ] Test any textarea fields found

---

## Phase 6: Additional Components & Cleanup (Days 12-13)

### Objective
Review and fix any remaining textareas, perform cleanup, and final testing.

### Tasks

#### Task 6.1: Review Additional Components
**Files:**
- `resources/views/components/modal.blade.php`
- `resources/views/reports/monthly/index.blade.php`
- `resources/views/welcome.blade.php`
- Any other files from grep results

**Estimated Time:** 3 hours

**Changes:**
1. Review each file for textarea fields
2. Add class `auto-resize-textarea` if found
3. Update any dynamic JavaScript

**Testing:**
- [ ] Test all textareas found
- [ ] Verify auto-resize works

---

#### Task 6.2: Remove Inline Styles from Projects Partials
**Files:**
- All projects partials that have inline CSS for textareas

**Estimated Time:** 2 hours

**Changes:**
1. Remove inline `<style>` tags from partials (now using global CSS)
2. Keep only the class attributes on textareas
3. Remove duplicate JavaScript (now using global JS)
4. Keep only dynamic initialization calls if needed

**Testing:**
- [ ] Verify all projects partials still work correctly
- [ ] Verify no styling is lost

---

#### Task 6.3: Comprehensive Testing
**Estimated Time:** 4 hours

**Testing Checklist:**
- [ ] Test all project create forms
- [ ] Test all project edit forms
- [ ] Test all monthly report create forms
- [ ] Test all monthly report edit forms
- [ ] Test all quarterly report forms
- [ ] Test all aggregated report AI edit forms
- [ ] Test project comments
- [ ] Test provincial forms
- [ ] Test coordinator forms
- [ ] Test dynamic textarea additions (attachments, photos, activities, etc.)
- [ ] Test readonly textareas wrap correctly
- [ ] Test textareas with existing data load correctly
- [ ] Test textareas with long content
- [ ] Test textareas with line breaks
- [ ] Verify no console errors
- [ ] Verify no visual regressions

---

#### Task 6.4: Documentation Update
**Estimated Time:** 1 hour

**Changes:**
1. Update this document with completion status
2. Document any special cases or exceptions
3. Create quick reference guide for developers

---

## Implementation Notes

### Best Practices
1. **Always use classes** - Never inline styles for textarea auto-resize
2. **Initialize dynamically** - Always call `initDynamicTextarea()` after adding textareas via JavaScript
3. **Test thoroughly** - Test both new and existing data scenarios
4. **Consistent naming** - Use `auto-resize-textarea` for new implementations, maintain existing classes where appropriate

### Special Cases
1. **Readonly textareas** - Still need auto-resize for proper wrapping
2. **CodeMirror editors** - May need special handling or can be skipped
3. **Hidden textareas** - If truly hidden, may not need auto-resize
4. **Conditional textareas** - Ensure initialization when made visible

### Common Issues & Solutions
1. **Height not calculating correctly** - Ensure `height: auto` is set before reading `scrollHeight`
2. **Dynamic additions not working** - Remember to call `initDynamicTextarea()` after insertion
3. **Readonly textareas not wrapping** - Ensure `white-space: pre-wrap` is in CSS
4. **Multiple initializations** - Global script handles this, but check for duplicate event listeners

---

## Testing Checklist Template

For each file modified:

- [ ] Textarea has `auto-resize-textarea` (or appropriate class)
- [ ] Textarea wraps text correctly
- [ ] Textarea auto-resizes on input
- [ ] Textarea auto-resizes on paste
- [ ] Textarea has correct initial height if pre-filled
- [ ] Dynamic additions work correctly (if applicable)
- [ ] No console errors
- [ ] Visual appearance is correct
- [ ] Works in different browsers (Chrome, Firefox, Safari)
- [ ] Works on mobile devices (responsive)

---

## Completion Criteria

### Phase Completion
Each phase is complete when:
- [ ] All files in phase are modified
- [ ] All textareas in phase have auto-resize functionality
- [ ] All dynamic additions work correctly
- [ ] Testing checklist passed
- [ ] No console errors
- [ ] Code reviewed

### Overall Completion
Overall implementation is complete when:
- [ ] All phases are complete
- [ ] Comprehensive testing passed
- [ ] Documentation updated
- [ ] No remaining textareas without auto-resize
- [ ] Global CSS and JS are being used
- [ ] No duplicate code

---

**Document Version:** 1.0  
**Created:** January 2025  
**Status:** Ready for Implementation  
**Estimated Completion:** 13 days from start
