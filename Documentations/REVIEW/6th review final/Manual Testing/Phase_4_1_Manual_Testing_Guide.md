# Phase 4.1: Manual Browser Testing Guide

**Date:** January 2025  
**Status:** 🟡 **READY FOR TESTING**

---

## Overview

This guide provides step-by-step instructions for manual browser testing of the textarea auto-resize functionality after cleanup.

---

## Pre-Testing Checklist

### 1. Browser Setup
- [ ] Open your browser (Chrome/Edge recommended)
- [ ] Open Developer Tools: Press **F12** (or Right-click → Inspect)
- [ ] Go to **Console** tab
- [ ] Go to **Network** tab (optional, to verify file loading)
- [ ] Clear browser cache: **Ctrl+Shift+R** (Windows) or **Cmd+Shift+R** (Mac)

### 2. Application Setup
- [ ] Log in to the application (if required)
- [ ] Ensure you have access to project create/edit pages
- [ ] Have test data ready (or create new projects for testing)

---

## Testing Quick Reference

### Key URLs to Test:
- Project Create: `/projects/create` (select project type)
- Project Edit: `/projects/{id}/edit` (use existing project ID)

### Key Sections to Test:
1. **General Info** → Full Address textarea
2. **Sustainability** → Any sustainability textarea
3. **IGE** → New Beneficiaries / Ongoing Beneficiaries (dynamic rows)
4. **RST** → Target Group Annexure (dynamic rows)
5. **Logical Framework** → Objective, Result, Risk textareas
6. **Any other textarea** on the form

---

## Step-by-Step Testing Instructions

### Test 1: Basic Auto-Resize (2 minutes)

**Goal:** Verify basic auto-resize functionality works

**Steps:**
1. Navigate to any project edit page (or create new project)
2. Find a textarea (e.g., "Full Address" in General Info section)
3. Click inside the textarea
4. Type multiple lines:
   ```
   Line 1
   Line 2
   Line 3
   Line 4
   ```
5. Watch the textarea as you type - it should expand automatically
6. Delete some text - it should shrink
7. Check browser console (F12) - should be no errors

**Expected:**
- ✅ Textarea expands as you type
- ✅ Textarea shrinks when you delete
- ✅ No console errors
- ✅ Textarea looks properly styled

**If it works:** ✅ Mark as PASS, move to next test  
**If it doesn't work:** ❌ Document the issue, check console for errors

---

### Test 2: Dynamic Content - IGE (5 minutes)

**Goal:** Verify dynamically added textareas work

**Steps:**
1. Navigate to IGE project create or edit page
2. Scroll to "New Beneficiaries" section
3. Click the **"Add More"** button
4. A new row should appear with textareas
5. Type in the newly added textareas:
   - Address field (textarea)
   - Family Background and Need field (textarea)
6. Watch as you type - new textareas should auto-resize

**Expected:**
- ✅ New row appears when "Add More" clicked
- ✅ New textareas auto-resize as you type
- ✅ New textareas look the same as existing ones
- ✅ No console errors

**If it works:** ✅ Mark as PASS  
**If it doesn't work:** ❌ Document the issue, check if `window.initTextareaAutoResize` exists in console

---

### Test 3: Dynamic Content - RST (3 minutes)

**Goal:** Verify another dynamic content scenario

**Steps:**
1. Navigate to RST project create or edit page
2. Scroll to "Target Group Annexure" section
3. Click **"Add More"** button
4. Type in newly added textareas:
   - Family Situation
   - Paragraph
5. Watch as you type

**Expected:**
- ✅ New textareas auto-resize
- ✅ No console errors

---

### Test 4: Logical Framework (3 minutes)

**Goal:** Verify logical framework textareas work

**Steps:**
1. Navigate to Logical Framework section
2. Type in "Objective" textarea
3. Type in "Result" textarea
4. Click "Add Result" (if available)
5. Type in newly added result textarea

**Expected:**
- ✅ All textareas auto-resize
- ✅ New textareas work correctly

---

### Test 5: Visual Check (2 minutes)

**Goal:** Verify no visual regressions

**Steps:**
1. Browse through several project forms
2. Look at textareas visually
3. Check forms overall

**Expected:**
- ✅ Textareas look consistent
- ✅ No layout breaks
- ✅ Forms look correct

---

### Test 6: Paste Test (2 minutes)

**Goal:** Verify paste functionality works

**Steps:**
1. Copy multi-line text from somewhere
2. Paste into any textarea
3. Watch the textarea

**Expected:**
- ✅ Textarea expands to fit pasted content
- ✅ Pasted text displays correctly

---

## Troubleshooting

### Issue: Textareas don't auto-resize

**Check:**
1. Open browser console (F12)
2. Check for errors (red text)
3. Type in console: `typeof window.initTextareaAutoResize`
   - Should return: `"function"`
   - If returns: `"undefined"` → Global JS not loading

**Fix:**
- Check Network tab - verify `textarea-auto-resize.js` loads (Status 200)
- Check if file exists: `public/js/textarea-auto-resize.js`
- Clear browser cache and reload

---

### Issue: Dynamic textareas don't work

**Check:**
1. Console: `typeof window.initTextareaAutoResize` → Should be `"function"`
2. Check the JavaScript code that adds rows
3. Verify it calls: `window.initTextareaAutoResize(newTextarea)`

**Common problem:**
- Old code might use: `autoResizeTextarea(newTextarea)` (local function)
- Should use: `window.initTextareaAutoResize(newTextarea)` (global function)

---

### Issue: Console errors

**Common errors:**
- `initTextareaAutoResize is not a function` → Global JS not loaded
- `Cannot read property 'style' of null` → Element not found
- `404` errors → Files not found (check paths)

---

## Test Results Tracking

Use the checklist document: `Phase_4_1_Manual_Browser_Testing_Checklist.md` (in same folder)

Fill in:
- Date, Tester name, Browser version
- Mark each test as ✅ PASS or ❌ FAIL
- Document any issues found
- Take screenshots if issues found

---

## Success Criteria

✅ **Minimum (Quick Test):**
- Basic auto-resize works
- At least one dynamic content scenario works
- No console errors
- Visual appearance correct

✅ **Full Test:**
- All quick tests pass
- All dynamic content scenarios work
- Edge cases work (paste, long text, etc.)
- Multiple browsers work (if testing)

---

## Next Steps After Testing

### If All Tests Pass:
1. ✅ Mark regression testing as complete
2. ✅ Update documentation
3. ✅ Phase 4.1 complete!

### If Issues Found:
1. ⚠️ Document issues in detail
2. ⚠️ Check if issues existed before cleanup
3. ⚠️ Fix issues if related to cleanup
4. ⚠️ Re-test

---

## Quick Command Reference

### Browser Console Commands:
```javascript
// Check if global function exists
typeof window.initTextareaAutoResize
// Should return: "function"

// Check if textarea has class
document.querySelector('textarea.sustainability-textarea')
// Should return: textarea element

// Manually initialize a textarea (for testing)
window.initTextareaAutoResize(document.querySelector('textarea.sustainability-textarea'))
```

---

**Ready to test? Start with Test 1 above!**

**Document Version:** 1.0  
**Created:** January 2025  
**Status:** Ready for Manual Testing
