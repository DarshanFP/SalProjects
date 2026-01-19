# Phase 4.1: Regression Testing Guide

**Date:** January 2025  
**Status:** 🟡 **READY FOR TESTING**

---

## Overview

This guide provides a systematic approach to regression testing the textarea auto-resize functionality after cleaning up duplicate CSS/JS code from 57+ files.

---

## Pre-Testing Checklist

Before starting, verify:

- [ ] Global CSS file is included: `public/css/custom/textarea-auto-resize.css`
- [ ] Global JS file is included: `public/js/textarea-auto-resize.js`
- [ ] Both files are loaded in main layouts (app.blade.php, dashboard layouts)
- [ ] Browser cache cleared (Ctrl+Shift+R or Cmd+Shift+R)
- [ ] JavaScript console open (F12) to check for errors

---

## Quick Test Suite (15-20 minutes)

### 1. Core Functionality Tests (5 minutes)

Test basic auto-resize on a few key pages:

1. **Project Create Page**
   - Navigate to project creation form
   - Find any textarea with class `sustainability-textarea` or `logical-textarea`
   - Type multiple lines of text
   - ✅ **Expected:** Textarea expands automatically as you type
   - ✅ **Expected:** No console errors

2. **Project Edit Page**
   - Navigate to an existing project edit form
   - Find textareas with auto-resize classes
   - Type in textareas
   - ✅ **Expected:** Textarea expands automatically
   - ✅ **Expected:** Existing content displays properly

3. **Logical Framework Section**
   - Navigate to logical framework section (create or edit)
   - Type in objective, result, risk textareas (`.logical-textarea`)
   - ✅ **Expected:** All textareas auto-resize

### 2. Dynamic Content Tests (5 minutes)

Test that dynamically added textareas work:

1. **IGE - Ongoing Beneficiaries**
   - Navigate to IGE project create/edit
   - Click "Add More" in Ongoing Beneficiaries section
   - Type in newly added textareas
   - ✅ **Expected:** New textareas auto-resize

2. **IGE - New Beneficiaries**
   - Click "Add More" in New Beneficiaries section
   - Type in newly added textareas
   - ✅ **Expected:** New textareas auto-resize

3. **RST - Target Group Annexure**
   - Navigate to RST project create/edit
   - Click "Add More" in Target Group Annexure
   - Type in newly added textareas
   - ✅ **Expected:** New textareas auto-resize

4. **ILP - Strength/Weakness**
   - Navigate to ILP project create/edit
   - Click "Add Strength" or "Add Weakness"
   - Type in newly added textarea
   - ✅ **Expected:** New textarea auto-resizes

5. **Logical Framework - Add Objective/Result/Risk**
   - Navigate to logical framework section
   - Click "Add Objective", "Add Result", or "Add Risk"
   - Type in newly added textareas
   - ✅ **Expected:** New textareas auto-resize

### 3. Visual Regression Tests (5 minutes)

Check for visual issues:

1. **Textarea Styling**
   - Check textareas have consistent styling
   - ✅ **Expected:** Proper padding, line-height, min-height
   - ✅ **Expected:** No visual glitches or layout breaks

2. **Form Layout**
   - Check forms render correctly
   - ✅ **Expected:** No overlapping elements
   - ✅ **Expected:** Proper spacing between fields

3. **Responsive Behavior**
   - Resize browser window
   - ✅ **Expected:** Textareas remain functional
   - ✅ **Expected:** Layout doesn't break

---

## Full Test Suite (1-2 hours)

### Project Type Coverage

Test all project types that were cleaned:

- [ ] **IGE** (Institution for General Education)
  - [ ] Create project
  - [ ] Edit project
  - [ ] Ongoing beneficiaries (dynamic rows)
  - [ ] New beneficiaries (dynamic rows)
  - [ ] Development monitoring

- [ ] **IES** (Institution for Educational Support)
  - [ ] Create project
  - [ ] Edit project
  - [ ] Educational background
  - [ ] Immediate family details

- [ ] **IIES** (Institution for Individual Educational Support)
  - [ ] Create project
  - [ ] Edit project
  - [ ] Education background
  - [ ] Personal info
  - [ ] Immediate family details
  - [ ] Scope of financial support

- [ ] **LDP** (Livelihood Development Project)
  - [ ] Create project
  - [ ] Edit project
  - [ ] Intervention logic
  - [ ] Target group

- [ ] **RST** (Religious Studies Training)
  - [ ] Create project
  - [ ] Edit project
  - [ ] Target group
  - [ ] Target group annexure (dynamic rows)

- [ ] **IAH** (Institution for Aged and Handicapped)
  - [ ] Create project
  - [ ] Edit project
  - [ ] Health conditions

- [ ] **ILP** (Income Livelihood Program)
  - [ ] Create project
  - [ ] Edit project
  - [ ] Strength/weakness (dynamic rows)

- [ ] **Edu-RUT** (Education Rural/Urban/Tribal)
  - [ ] Create project
  - [ ] Edit project
  - [ ] Basic info
  - [ ] Annexed target group (dynamic rows)

### Common Sections

- [ ] **General Info**
  - [ ] Full address textarea

- [ ] **Logical Framework**
  - [ ] Objective textareas
  - [ ] Result textareas
  - [ ] Risk textareas
  - [ ] Activity description textareas
  - [ ] Add/remove dynamic elements

- [ ] **Attachments**
  - [ ] Attachment description textarea

- [ ] **Budget** (if applicable)
  - [ ] Note: `.particular-textarea` uses special styling (intentional)

---

## Edge Cases & Special Scenarios

### 1. Long Text
- [ ] Type very long text (1000+ characters)
- [ ] ✅ **Expected:** Textarea expands appropriately
- [ ] ✅ **Expected:** Scrollbar appears if needed

### 2. Paste Content
- [ ] Paste multi-line text into textarea
- [ ] ✅ **Expected:** Textarea expands to fit pasted content

### 3. Delete Content
- [ ] Type text, then delete it
- [ ] ✅ **Expected:** Textarea shrinks appropriately

### 4. Multiple Textareas on Same Page
- [ ] Fill multiple textareas on same form
- [ ] ✅ **Expected:** All textareas work independently

### 5. Form Validation Errors
- [ ] Submit form with errors
- [ ] ✅ **Expected:** Textareas with errors still auto-resize

### 6. Page Refresh
- [ ] Fill textarea, refresh page
- [ ] ✅ **Expected:** Saved content displays correctly
- [ ] ✅ **Expected:** Textarea height adjusts to content

---

## Browser Compatibility

Test in multiple browsers:

- [ ] **Chrome/Edge** (Chromium)
- [ ] **Firefox**
- [ ] **Safari** (if available)

For each browser:
- [ ] Core functionality works
- [ ] Dynamic content works
- [ ] No console errors
- [ ] Visual appearance is correct

---

## Console Error Checks

While testing, monitor browser console (F12):

- [ ] No JavaScript errors
- [ ] No CSS loading errors
- [ ] No undefined function errors
- [ ] No duplicate initialization warnings (if any)

Common issues to watch for:
- `initTextareaAutoResize is not a function` - Global JS not loaded
- CSS not applied - CSS file not loaded
- Textareas not resizing - Check classes match global CSS/JS

---

## Testing Checklist Summary

### Critical Tests (Must Pass)
- [x] Global CSS/JS files loaded
- [ ] Basic auto-resize works
- [ ] Dynamic content auto-resize works
- [ ] No console errors
- [ ] Visual appearance correct

### Important Tests (Should Pass)
- [ ] All project types work
- [ ] All common sections work
- [ ] Edge cases handled
- [ ] Browser compatibility

### Optional Tests (Nice to Have)
- [ ] Performance (no lag)
- [ ] Accessibility (keyboard navigation)
- [ ] Mobile responsiveness

---

## Reporting Issues

If you find issues, document:

1. **Page/Form:** Which page or form
2. **Textarea:** Which specific textarea
3. **Expected:** What should happen
4. **Actual:** What actually happens
5. **Browser:** Which browser and version
6. **Console Errors:** Any JavaScript errors
7. **Steps to Reproduce:** Detailed steps

---

## Quick Reference

### Global Files
- **CSS:** `public/css/custom/textarea-auto-resize.css`
- **JS:** `public/js/textarea-auto-resize.js`

### CSS Classes
- `.sustainability-textarea` - Standard textareas
- `.logical-textarea` - Logical framework textareas
- `.auto-resize-textarea` - Generic auto-resize
- `.particular-textarea` - Budget textareas (special case)

### Global Functions
- `window.initTextareaAutoResize(textarea)` - Initialize single textarea
- `window.initDynamicTextarea(container)` - Initialize textareas in container
- `window.autoResizeTextarea(textarea)` - Manual resize trigger

---

## Next Steps After Testing

1. ✅ **If all tests pass:** Mark testing complete, proceed to final documentation
2. ⚠️ **If issues found:** Document issues, fix them, retest
3. 📝 **Update documentation:** Record test results

---

**Document Version:** 1.0  
**Created:** January 2025  
**Status:** Ready for Testing
