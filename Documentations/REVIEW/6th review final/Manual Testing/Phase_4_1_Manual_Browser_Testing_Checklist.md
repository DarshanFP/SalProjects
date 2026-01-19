# Phase 4.1: Manual Browser Testing Checklist

**Date:** _______________  
**Tester:** _______________  
**Browser:** _______________ (Version: _______)  
**Status:** 🟡 **IN PROGRESS**

---

## Pre-Testing Setup

- [ ] Browser Developer Tools open (F12)
- [ ] Console tab selected
- [ ] Browser cache cleared (Ctrl+Shift+R or Cmd+Shift+R)
- [ ] Network tab ready (to check file loading)
- [ ] Application logged in (if required)

---

## Quick Test Suite (15-20 minutes)

### Test 1: Basic Auto-Resize ✅ / ❌

**Location:** Any project edit page (or create page)

**Steps:**
1. Navigate to a project edit form (any project type)
2. Find a textarea (e.g., "Full Address" in General Info section, or any textarea)
3. Click in the textarea
4. Type multiple lines of text (press Enter a few times, or type long text)
5. Watch the textarea as you type

**Expected Results:**
- [ ] Textarea expands automatically as you type more content
- [ ] Textarea shrinks when you delete content
- [ ] No JavaScript errors in console
- [ ] Textarea maintains proper padding and styling

**Actual Results:**
- [ ] ✅ PASS
- [ ] ❌ FAIL (describe issue below)

**Issues Found:**
_______________________________________________________
_______________________________________________________

**Screenshots:** (if issues found)

---

### Test 2: Dynamic Content - IGE New Beneficiaries ✅ / ❌

**Location:** IGE Project Create/Edit → New Beneficiaries section

**Steps:**
1. Navigate to IGE project create or edit page
2. Scroll to "New Beneficiaries" section
3. Click "Add More" button
4. Type in the newly added textarea fields:
   - Address field (textarea)
   - Family Background and Need field (textarea)
5. Watch as you type in the new textareas

**Expected Results:**
- [ ] New textareas appear when "Add More" is clicked
- [ ] New textareas auto-resize as you type
- [ ] New textareas have same styling as existing ones
- [ ] No JavaScript errors in console

**Actual Results:**
- [ ] ✅ PASS
- [ ] ❌ FAIL (describe issue below)

**Issues Found:**
_______________________________________________________
_______________________________________________________

---

### Test 3: Dynamic Content - RST Target Group Annexure ✅ / ❌

**Location:** RST Project Create/Edit → Target Group Annexure

**Steps:**
1. Navigate to RST project create or edit page
2. Scroll to "Target Group Annexure" section
3. Click "Add More" button
4. Type in the newly added textarea fields:
   - Family Situation (textarea)
   - Paragraph (textarea)
5. Watch as you type

**Expected Results:**
- [ ] New textareas appear when "Add More" is clicked
- [ ] New textareas auto-resize as you type
- [ ] No JavaScript errors

**Actual Results:**
- [ ] ✅ PASS
- [ ] ❌ FAIL

**Issues Found:**
_______________________________________________________
_______________________________________________________

---

### Test 4: Logical Framework ✅ / ❌

**Location:** Any project → Logical Framework section

**Steps:**
1. Navigate to Logical Framework section (create or edit)
2. Type in "Objective" textarea
3. Type in "Result" textarea (if available)
4. Click "Add Result" (if available)
5. Type in the newly added result textarea

**Expected Results:**
- [ ] All textareas (Objective, Result, Risk) auto-resize
- [ ] New textareas added via "Add Result" or "Add Risk" auto-resize
- [ ] No JavaScript errors

**Actual Results:**
- [ ] ✅ PASS
- [ ] ❌ FAIL

**Issues Found:**
_______________________________________________________
_______________________________________________________

---

### Test 5: Visual Check ✅ / ❌

**Location:** Multiple forms/pages

**Steps:**
1. Browse through several project forms (create/edit)
2. Check textareas visually

**Expected Results:**
- [ ] Textareas have consistent styling
- [ ] Textareas have proper padding and spacing
- [ ] No layout breaks or overlapping elements
- [ ] Forms look correct overall
- [ ] Textareas fit properly within their containers

**Actual Results:**
- [ ] ✅ PASS
- [ ] ❌ FAIL

**Issues Found:**
_______________________________________________________
_______________________________________________________

---

### Test 6: Paste Test ✅ / ❌

**Location:** Any textarea

**Steps:**
1. Copy multi-line text from somewhere (e.g., a document)
2. Paste into any textarea
3. Watch the textarea after pasting

**Expected Results:**
- [ ] Textarea expands to fit pasted content
- [ ] Pasted text displays correctly
- [ ] No glitches or layout issues
- [ ] Textarea continues to auto-resize if you edit pasted content

**Actual Results:**
- [ ] ✅ PASS
- [ ] ❌ FAIL

**Issues Found:**
_______________________________________________________
_______________________________________________________

---

## Console Error Check

**While testing, monitor browser console (F12 → Console tab):**

- [ ] No JavaScript errors (red text)
- [ ] No CSS loading errors
- [ ] No undefined function errors
- [ ] Check for: `initTextareaAutoResize is not a function` (should NOT appear)
- [ ] Verify global JS loaded: Type `typeof window.initTextareaAutoResize` in console → Should return `"function"`

**Errors Found:**
_______________________________________________________
_______________________________________________________

---

## Network Check (Optional)

**Check if files are loading (F12 → Network tab):**

- [ ] `textarea-auto-resize.css` loads (Status: 200)
- [ ] `textarea-auto-resize.js` loads (Status: 200)
- [ ] Files load on page load (not 404 errors)

**Issues Found:**
_______________________________________________________

---

## Overall Test Results

### Quick Test Summary:
- Test 1: Basic Auto-Resize - [ ] ✅ PASS / [ ] ❌ FAIL
- Test 2: IGE Dynamic Content - [ ] ✅ PASS / [ ] ❌ FAIL
- Test 3: RST Dynamic Content - [ ] ✅ PASS / [ ] ❌ FAIL
- Test 4: Logical Framework - [ ] ✅ PASS / [ ] ❌ FAIL
- Test 5: Visual Check - [ ] ✅ PASS / [ ] ❌ FAIL
- Test 6: Paste Test - [ ] ✅ PASS / [ ] ❌ FAIL

### Overall Status:
- [ ] ✅ **ALL TESTS PASSED** - Cleanup successful!
- [ ] ⚠️ **SOME TESTS FAILED** - Issues found (see details above)
- [ ] ❌ **MULTIPLE TESTS FAILED** - Major issues found

---

## Issues Summary

### Critical Issues (Blocking):
1. _______________________________________________________
2. _______________________________________________________

### Medium Issues (Important):
1. _______________________________________________________
2. _______________________________________________________

### Low Issues (Minor):
1. _______________________________________________________
2. _______________________________________________________

---

## Next Steps

**If All Tests Pass:**
- ✅ Mark regression testing as complete
- ✅ Update documentation
- ✅ Proceed with final cleanup (if any)

**If Issues Found:**
- ⚠️ Document issues in detail
- ⚠️ Investigate root cause
- ⚠️ Fix issues
- ⚠️ Re-test

---

## Notes

**Additional Observations:**
_______________________________________________________
_______________________________________________________
_______________________________________________________

---

**Test Completed:** [ ] YES  
**Test Date:** _______________  
**Test Time:** _______________  
**Overall Result:** [ ] PASS / [ ] FAIL / [ ] PARTIAL

---

**Document Version:** 1.0  
**Created:** January 2025  
**Status:** Ready for Manual Testing
