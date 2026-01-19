# Phase 4.1: Text Area Auto-Resize - Regression Testing Checklist

**Date:** January 2025  
**Phase:** Phase 4.1 - Text Area Auto-Resize  
**Status:** 🟡 **READY FOR TESTING**

---

## Testing Overview

This document provides a comprehensive checklist for regression testing of the Text Area Auto-Resize feature implemented in Phase 4.1.

### Objectives:
1. ✅ Verify all textareas auto-resize correctly
2. ✅ Verify dynamic textareas (added via JavaScript) work correctly
3. ✅ Verify readonly textareas wrap text properly
4. ✅ Verify cross-browser compatibility
5. ✅ Verify no visual regressions
6. ✅ Verify special cases (particular-textarea, readonly-input) still work

---

## Pre-Testing Checklist

### Files Verified:
- ✅ `public/css/custom/textarea-auto-resize.css` exists and is correct
- ✅ `public/js/textarea-auto-resize.js` exists and is correct
- ✅ Global files are included in layouts:
  - `resources/views/layoutAll/app.blade.php`
  - Dashboard layouts (coordinator, provincial, executor applicant, general user)

### Browser Testing:
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile browser (optional)

---

## Test Scenarios

### 1. Core Functionality Tests

#### 1.1 Basic Auto-Resize
- [ ] **Test:** Load any page with textareas
- [ ] **Expected:** Textareas automatically adjust height based on content
- [ ] **Location:** Any project create/edit form
- [ ] **Notes:**

#### 1.2 Text Wrapping
- [ ] **Test:** Type long text without line breaks
- [ ] **Expected:** Text wraps correctly, textarea expands vertically
- [ ] **Location:** Sustainability, key information sections
- [ ] **Notes:**

#### 1.3 Multiple Lines
- [ ] **Test:** Type multiple paragraphs with line breaks
- [ ] **Expected:** Textarea expands to show all content
- [ ] **Location:** Any multi-line textarea
- [ ] **Notes:**

#### 1.4 Readonly Textareas
- [ ] **Test:** View readonly textareas (in show views)
- [ ] **Expected:** Text wraps correctly, no scrollbars unless needed
- [ ] **Location:** Project show pages
- [ ] **Notes:**

---

### 2. Dynamic Content Tests

#### 2.1 LDP Target Group (Create)
- [ ] **Test:** Click "Add More" to add new target group rows
- [ ] **Expected:** Newly added textareas auto-resize correctly
- [ ] **Location:** `projects/create` → LDP project → Target Group
- [ ] **Textareas:** `L_family_situation[]`, `L_nature_of_livelihood[]` (2 textareas per row)
- [ ] **Notes:**

#### 2.2 LDP Target Group (Edit)
- [ ] **Test:** Click "Add More" to add new target group rows
- [ ] **Expected:** Newly added textareas auto-resize correctly
- [ ] **Location:** `projects/{id}/edit` → LDP project → Target Group
- [ ] **Textareas:** `L_family_situation[]`, `L_nature_of_livelihood[]`
- [ ] **Notes:**

#### 2.3 IGE Ongoing Beneficiaries (Edit)
- [ ] **Test:** Click "Add More" to add new beneficiary rows
- [ ] **Expected:** Newly added textareas auto-resize correctly
- [ ] **Location:** `projects/{id}/edit` → IGE project → Ongoing Beneficiaries
- [ ] **Textareas:** `oaddress[]`, `operformance_details[]` (2 textareas per row)
- [ ] **Notes:**

#### 2.4 IGE New Beneficiaries (Edit)
- [ ] **Test:** Click "Add More" to add new beneficiary rows
- [ ] **Expected:** Newly added textareas auto-resize correctly
- [ ] **Location:** `projects/{id}/edit` → IGE project → New Beneficiaries
- [ ] **Textareas:** `address[]`, `family_background_need[]` (2 textareas per row)
- [ ] **Notes:**

#### 2.5 ILP Strength/Weakness (Edit)
- [ ] **Test:** Click "Add Strength" and "Add Weakness"
- [ ] **Expected:** Newly added textareas auto-resize correctly
- [ ] **Location:** `projects/{id}/edit` → ILP project → Strengths and Weaknesses
- [ ] **Textareas:** `strengths[]`, `weaknesses[]`
- [ ] **Notes:**

#### 2.6 RST Target Group Annexure (Edit)
- [ ] **Test:** Click "Add More" to add new annexure rows
- [ ] **Expected:** Newly added textareas auto-resize correctly
- [ ] **Location:** `projects/{id}/edit` → RST project → Target Group Annexure
- [ ] **Textareas:** `rst_family_situation[]`, `rst_paragraph[]` (2 textareas per row)
- [ ] **Notes:**

#### 2.7 Edu-RUT Annexed Target Group (Edit)
- [ ] **Test:** Click "Add Row" to add new target group rows
- [ ] **Expected:** Newly added textareas auto-resize correctly
- [ ] **Location:** `projects/{id}/edit` → Edu-RUT project → Annexed Target Group
- [ ] **Textareas:** `family_background`, `need_of_support` (2 textareas per row)
- [ ] **Notes:**

#### 2.8 NPD Attachments (Create)
- [ ] **Test:** Click "Add Attachment" to add new attachment rows
- [ ] **Expected:** Newly added textareas auto-resize correctly
- [ ] **Location:** `projects/create` → NPD project → Attachments
- [ ] **Textareas:** Photo description textarea
- [ ] **Notes:**

#### 2.9 Monthly Report - Livelihood Annexure
- [ ] **Test:** Add new Livelihood Annexure entries
- [ ] **Expected:** Newly added textareas auto-resize correctly
- [ ] **Location:** Monthly reports → Create → Livelihood Annexure
- [ ] **Textareas:** `dla_impact[]`, `dla_challenges[]`
- [ ] **Notes:**

---

### 3. Project Type Specific Tests

#### 3.1 Sustainability Section (All Project Types)
- [ ] **Test:** Edit sustainability textareas
- [ ] **Expected:** Textareas auto-resize correctly
- [ ] **Location:** `projects/{id}/edit` → Sustainability section
- [ ] **Classes:** `sustainability-textarea`
- [ ] **Notes:**

#### 3.2 Key Information (All Project Types)
- [ ] **Test:** Edit key information textareas
- [ ] **Expected:** Textareas auto-resize correctly
- [ ] **Location:** `projects/{id}/edit` → Key Information section
- [ ] **Classes:** `auto-resize-textarea`
- [ ] **Notes:**

#### 3.3 Logical Framework (All Project Types)
- [ ] **Test:** Edit logical framework textareas
- [ ] **Expected:** Textareas auto-resize correctly
- [ ] **Location:** `projects/{id}/edit` → Logical Framework section
- [ ] **Classes:** `logical-textarea`
- [ ] **Notes:**

#### 3.4 General Info - Full Address
- [ ] **Test:** Edit full address textarea
- [ ] **Expected:** Textarea auto-resizes correctly
- [ ] **Location:** `projects/{id}/edit` → General Info → Full Address
- [ ] **Classes:** `sustainability-textarea`
- [ ] **Notes:**

---

### 4. Special Cases Tests

#### 4.1 Budget - Particular Textarea
- [ ] **Test:** Edit budget particular column textareas
- [ ] **Expected:** Textareas auto-resize correctly (special case, not global)
- [ ] **Location:** `projects/{id}/edit` → Budget section
- [ ] **Classes:** `particular-textarea` (special case, kept separate)
- [ ] **Notes:** This uses its own CSS/JS, not global files

#### 4.2 Readonly Inputs
- [ ] **Test:** View readonly inputs with `.readonly-input` class
- [ ] **Expected:** Styling preserved (not affected by cleanup)
- [ ] **Location:** NPD attachments, other readonly fields
- [ ] **Classes:** `.readonly-input` (special case, kept separate)
- [ ] **Notes:**

---

### 5. Edge Cases

#### 5.1 Very Long Text
- [ ] **Test:** Paste very long text (1000+ characters)
- [ ] **Expected:** Textarea expands appropriately, scrollbar appears if needed
- [ ] **Location:** Any textarea
- [ ] **Notes:**

#### 5.2 Empty Textareas
- [ ] **Test:** Clear textarea content completely
- [ ] **Expected:** Textarea shrinks to minimum height (80px for most)
- [ ] **Location:** Any textarea
- [ ] **Notes:**

#### 5.3 Paste Operation
- [ ] **Test:** Paste multi-line text from clipboard
- [ ] **Expected:** Textarea expands to accommodate pasted content
- [ ] **Location:** Any textarea
- [ ] **Notes:** Global JS includes paste event handler

#### 5.4 Rapid Typing
- [ ] **Test:** Type rapidly in textarea
- [ ] **Expected:** Textarea adjusts smoothly without lag
- [ ] **Location:** Any textarea
- [ ] **Notes:**

#### 5.5 Multiple Textareas on Same Page
- [ ] **Test:** Page with many textareas (e.g., IGE beneficiaries)
- [ ] **Expected:** All textareas auto-resize independently
- [ ] **Location:** IGE new/ongoing beneficiaries pages
- [ ] **Notes:**

---

### 6. Visual Regression Tests

#### 6.1 Form Layout
- [ ] **Test:** Verify form layouts are not broken
- [ ] **Expected:** Forms display correctly, no overflow issues
- [ ] **Location:** All project create/edit forms
- [ ] **Notes:**

#### 6.2 Card Layout
- [ ] **Test:** Verify card layouts are correct
- [ ] **Expected:** Cards display correctly, textareas fit properly
- [ ] **Location:** All project partials
- [ ] **Notes:**

#### 6.3 Table Layout (Dynamic Rows)
- [ ] **Test:** Verify table layouts with dynamic rows
- [ ] **Expected:** Tables display correctly, textareas fit in cells
- [ ] **Location:** LDP target groups, IGE beneficiaries, RST annexure, etc.
- [ ] **Notes:**

#### 6.4 Minimum Height
- [ ] **Test:** Verify minimum height is correct (80px for most, 38px for particular-textarea)
- [ ] **Expected:** Textareas maintain minimum height
- [ ] **Location:** All textareas
- [ ] **Notes:**

---

### 7. Browser Compatibility Tests

#### 7.1 Chrome/Edge (Chromium)
- [ ] **Test:** Run all core functionality tests
- [ ] **Expected:** All features work correctly
- [ ] **Notes:**

#### 7.2 Firefox
- [ ] **Test:** Run all core functionality tests
- [ ] **Expected:** All features work correctly
- [ ] **Notes:**

#### 7.3 Safari
- [ ] **Test:** Run all core functionality tests
- [ ] **Expected:** All features work correctly
- [ ] **Notes:**

---

### 8. Performance Tests

#### 8.1 Page Load
- [ ] **Test:** Verify pages load quickly
- [ ] **Expected:** No noticeable slowdown from auto-resize scripts
- [ ] **Location:** All pages with textareas
- [ ] **Notes:**

#### 8.2 Dynamic Content Performance
- [ ] **Test:** Add multiple dynamic rows rapidly
- [ ] **Expected:** No performance issues
- [ ] **Location:** IGE beneficiaries, LDP target groups, etc.
- [ ] **Notes:**

---

## Test Results Summary

### Date: _______________
### Tester: _______________
### Browser: _______________

### Overall Status: [ ] PASS [ ] FAIL [ ] PARTIAL

### Issues Found:

1. **Issue:**  
   **Location:**  
   **Severity:** [ ] Critical [ ] High [ ] Medium [ ] Low  
   **Status:** [ ] Fixed [ ] Pending [ ] Known Issue  
   **Notes:**

2. **Issue:**  
   **Location:**  
   **Severity:** [ ] Critical [ ] High [ ] Medium [ ] Low  
   **Status:** [ ] Fixed [ ] Pending [ ] Known Issue  
   **Notes:**

3. **Issue:**  
   **Location:**  
   **Severity:** [ ] Critical [ ] High [ ] Medium [ ] Low  
   **Status:** [ ] Fixed [ ] Pending [ ] Known Issue  
   **Notes:**

---

## Quick Test Guide

### Minimum Required Tests (15-20 minutes):
1. ✅ Load any project edit page
2. ✅ Type in a sustainability textarea - verify auto-resize
3. ✅ Add a new dynamic row (e.g., LDP target group) - verify new textarea works
4. ✅ Test readonly textarea in show view
5. ✅ Test logical framework textarea
6. ✅ Test budget particular-textarea (special case)

### Full Test (1-2 hours):
- Complete all test scenarios above
- Test in 2-3 browsers
- Test all dynamic content scenarios
- Verify all edge cases

---

## Notes

- **Global Files:** All textareas should now use global CSS/JS files
- **Special Cases:** `.particular-textarea` and `.readonly-input` are intentionally kept separate
- **Dynamic Content:** All dynamic textareas should use `window.initTextareaAutoResize()`
- **Browser Console:** Check for JavaScript errors during testing

---

**Document Version:** 1.0  
**Created:** January 2025  
**Status:** Ready for Testing
