# TextArea Comprehensive Audit and Phase-Wise Implementation Plan

**Date:** January 2025  
**Status:** Pending Implementation  
**Purpose:** Ensure ALL textareas across the entire codebase have:
- Text wrap enabled
- Dynamic height adjustment to accommodate content
- No scrollbar by default (only on focus if content is very long)
- Consistent styling similar to projects create/edit partials

---

## Standards Reference (Projects Create/Edit Partials)

### CSS Class Pattern
Two classes are used:
1. **`sustainability-textarea`** - For general textareas (Key Information, Sustainability, etc.)
2. **`logical-textarea`** - For textareas in Logical Framework and Time Frame sections

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

### For Dynamically Added Textareas
When adding textareas via JavaScript (e.g., attachments, photos), apply auto-resize after insertion:
```javascript
// After adding new textarea element
const newTextarea = newElement.querySelector('.sustainability-textarea');
if (newTextarea) {
    autoResizeTextarea(newTextarea);
    newTextarea.addEventListener('input', function() {
        autoResizeTextarea(this);
    });
}
```

---

## Complete TextArea Inventory

### ✅ Phase 0: Already Compliant (Projects Partials)

These have been fixed in previous reviews and follow the standards:

#### Common Sections
1. **General Info**
   - `resources/views/projects/partials/general_info.blade.php` - `full_address`
   - `resources/views/projects/partials/Edit/general_info.blade.php` - `full_address`

2. **Key Information**
   - `resources/views/projects/partials/key_information.blade.php` - All textareas (5 fields)
   - `resources/views/projects/partials/Edit/key_information.blade.php` - All textareas (5 fields)

3. **Sustainability**
   - `resources/views/projects/partials/sustainability.blade.php` - All textareas (4 fields)
   - `resources/views/projects/partials/Edit/sustainibility.blade.php` - All textareas (4 fields)

4. **Logical Framework**
   - `resources/views/projects/partials/logical_framework.blade.php` - All textareas
   - `resources/views/projects/partials/Edit/logical_framework.blade.php` - All textareas

5. **Time Frame**
   - `resources/views/projects/partials/_timeframe.blade.php` - All textareas
   - `resources/views/projects/partials/edit_timeframe.blade.php` - All textareas
   - `resources/views/projects/partials/NPD/_timeframe.blade.php` - All textareas

6. **Budget**
   - `resources/views/projects/partials/budget.blade.php` - Description fields
   - `resources/views/projects/partials/Edit/budget.blade.php` - Description fields

7. **Attachments**
   - `resources/views/projects/partials/attachments.blade.php` - Description fields
   - `resources/views/projects/partials/Edit/attachment.blade.php` - Description fields
   - `resources/views/projects/partials/NPD/attachments.blade.php` - Description fields

#### Project Type Specific (Fixed)
- **CCI:** rationale, present_situation, economic_background, personal_situation
- **ILP:** risk_analysis, strength_weakness
- **LDP:** intervention_logic
- **IIES:** education_background, personal_info, immediate_family_details, scope_financial_support
- **IES:** educational_background, personal_info, immediate_family_details, attachments
- **IAH:** health_conditions, support_details, personal_info
- **CIC:** basic_info
- **RST:** target_group, institution_info, target_group_annexure
- **IGE:** institution_info, development_monitoring, ongoing_beneficiaries, new_beneficiaries
- **Edu-RUT:** basic_info, annexed_target_group
- **NPD:** key_information, sustainability, logical_framework, attachments

---

### ⏳ Phase 1: Reports Module (Monthly Reports)

**Priority:** High  
**Estimated Time:** 3-4 days  
**Files Affected:** 12 files

#### 1.1 Monthly Reports - Create Form
- **File:** `resources/views/reports/monthly/ReportCommonForm.blade.php`
  - `plan_next_month[]` - Outlook section (dynamic rows)

- **File:** `resources/views/reports/monthly/ReportAll.blade.php`
  - `plan_next_month[]` - Outlook section (dynamic rows)
  - `photo_descriptions[]` - Photo descriptions (dynamic rows)

- **File:** `resources/views/reports/monthly/partials/create/objectives.blade.php`
  - `objective[]` - Objective field (readonly, but should still wrap)
  - `expected_outcome[][]` - Expected outcomes (readonly, but should still wrap)
  - `activity[][]` - Activity field (readonly, but should still wrap)
  - `summary_activities[][]` - Summary of activities (editable)
  - `qualitative_quantitative_data[][]` - Qualitative & Quantitative data (editable)
  - `intermediate_outcomes[][]` - Intermediate outcomes (editable)
  - `not_happened[]` - What did not happen (editable)
  - `why_not_happened[]` - Why activities could not be undertaken (editable)
  - `why_changes[]` - Why changes were made (conditional, editable)
  - `additional_notes[]` - Additional notes (editable)
  - **Total:** ~10+ textarea fields per objective, multiplied by number of objectives

- **File:** `resources/views/reports/monthly/partials/create/photos.blade.php`
  - `photo_descriptions[]` - Photo descriptions (dynamic rows)

- **File:** `resources/views/reports/monthly/partials/create/attachments.blade.php`
  - `attachment_descriptions[]` - Attachment descriptions (dynamic rows)

- **File:** `resources/views/reports/monthly/partials/create/LivelihoodAnnexure.blade.php`
  - Various textarea fields for livelihood annexure (check file for specific fields)

#### 1.2 Monthly Reports - Edit Form
- **File:** `resources/views/reports/monthly/edit.blade.php`
  - Similar structure to create form

- **File:** `resources/views/reports/monthly/partials/edit/objectives.blade.php`
  - Same fields as create form

- **File:** `resources/views/reports/monthly/partials/edit/photos.blade.php`
  - `photo_descriptions[]` - Photo descriptions (dynamic rows)

- **File:** `resources/views/reports/monthly/partials/edit/attachments.blade.php`
  - `attachment_descriptions[]` - Attachment descriptions (dynamic rows)

- **File:** `resources/views/reports/monthly/partials/edit/LivelihoodAnnexure.blade.php`
  - Various textarea fields (check file for specific fields)

- **File:** `resources/views/reports/monthly/partials/comments.blade.php`
  - Comment textarea fields

---

### ⏳ Phase 2: Reports Module (Quarterly Reports)

**Priority:** High  
**Estimated Time:** 2-3 days  
**Files Affected:** 6 files

#### 2.1 Quarterly Report Forms
- **File:** `resources/views/reports/quarterly/developmentProject/reportform.blade.php`
  - Multiple textarea fields (check file for specific fields)

- **File:** `resources/views/reports/quarterly/developmentLivelihood/reportform.blade.php`
  - Multiple textarea fields (check file for specific fields)

- **File:** `resources/views/reports/quarterly/skillTraining/reportform.blade.php`
  - Multiple textarea fields (check file for specific fields)

- **File:** `resources/views/reports/quarterly/womenInDistress/reportform.blade.php`
  - Multiple textarea fields (check file for specific fields)

- **File:** `resources/views/reports/quarterly/institutionalSupport/reportform.blade.php`
  - Multiple textarea fields (check file for specific fields)

---

### ⏳ Phase 3: Reports Module (Aggregated Reports)

**Priority:** Medium  
**Estimated Time:** 2 days  
**Files Affected:** 3 files

#### 3.1 Aggregated Reports - AI Edit Forms
- **File:** `resources/views/reports/aggregated/annual/edit-ai.blade.php`
  - `executive_summary` - Executive summary field
  - `impact_assessment` - Hidden textarea for JSON (uses CodeMirror editor, but should still wrap)
  - `budget_performance` - Hidden textarea for JSON
  - `future_outlook` - Hidden textarea for JSON

- **File:** `resources/views/reports/aggregated/quarterly/edit-ai.blade.php`
  - Similar fields to annual

- **File:** `resources/views/reports/aggregated/half-yearly/edit-ai.blade.php`
  - Similar fields to annual

**Note:** JSON editor textareas are hidden and use CodeMirror. May need special handling.

---

### ⏳ Phase 4: Projects Module (Comments & Additional Partials)

**Priority:** Medium  
**Estimated Time:** 1-2 days  
**Files Affected:** 3 files

#### 4.1 Project Comments
- **File:** `resources/views/projects/comments/edit.blade.php`
  - `comment` - Comment textarea

- **File:** `resources/views/projects/partials/ProjectComments.blade.php`
  - Comment textarea fields (check file for specific fields)

---

### ⏳ Phase 5: Provincial Module

**Priority:** Low  
**Estimated Time:** 1 day  
**Files Affected:** 3 files

#### 5.1 Provincial Forms
- **File:** `resources/views/provincial/createExecutor.blade.php`
  - `address` - Address textarea

- **File:** `resources/views/provincial/pendingReports.blade.php`
  - Check for any textarea fields

- **File:** `resources/views/provincial/ReportList.blade.php`
  - Check for any textarea fields

- **File:** `resources/views/coordinator/pendingReports.blade.php`
  - Check for any textarea fields

- **File:** `resources/views/coordinator/ReportList.blade.php`
  - Check for any textarea fields

---

### ⏳ Phase 6: Additional Components

**Priority:** Low  
**Estimated Time:** 1 day  
**Files Affected:** 2 files

#### 6.1 Additional Components
- **File:** `resources/views/components/modal.blade.php`
  - Check for any textarea fields in modal components

- **File:** `resources/views/reports/monthly/index.blade.php`
  - Check for any textarea fields

- **File:** `resources/views/welcome.blade.php`
  - Check for any textarea fields

---

## Summary Statistics

- **Total Files with Textareas:** ~150 files
- **Already Compliant:** ~60 files (projects module)
- **Needs Implementation:** ~90 files
- **Estimated Total Time:** 10-13 days

### Breakdown by Priority:
- **High Priority (Phases 1-2):** ~18 files, 5-7 days
- **Medium Priority (Phases 3-4):** ~6 files, 3-4 days
- **Low Priority (Phases 5-6):** ~6 files, 2 days

---

## Implementation Strategy

### Class Naming Convention
- Use `sustainability-textarea` for general textareas
- Use `logical-textarea` for textareas in structured tables/forms
- Consider creating a global class `auto-resize-textarea` for universal application

### Global CSS Approach
Instead of adding CSS to each file, consider:
1. Adding to main CSS file: `public/css/custom/project-forms.css`
2. Or creating: `public/css/custom/textarea-auto-resize.css`
3. Including globally in main layout

### Global JavaScript Approach
Instead of adding JavaScript to each file, consider:
1. Creating global function: `public/js/textarea-auto-resize.js`
2. Including globally in main layout
3. Auto-initializing all textareas with the class on page load

---

## Next Steps

1. **Review and validate** this inventory
2. **Create global CSS and JavaScript files** for textarea auto-resize
3. **Implement phase by phase** following the priority order
4. **Test each phase** before moving to the next
5. **Document completion** for each phase

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Ready for Implementation Review
