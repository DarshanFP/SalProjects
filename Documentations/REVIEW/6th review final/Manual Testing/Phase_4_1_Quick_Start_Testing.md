# Phase 4.1: Quick Start Testing Guide

**Status:** 🟡 **READY TO START**  
**Estimated Time:** 15-20 minutes (quick test) or 1-2 hours (full test)

---

## Quick Start (5 minutes setup)

### 1. Pre-Flight Checks

Before testing, verify:

```bash
# Check global files exist
ls -la public/css/custom/textarea-auto-resize.css
ls -la public/js/textarea-auto-resize.js

# Verify they're included in layouts (already verified in code)
```

✅ **Global CSS:** `public/css/custom/textarea-auto-resize.css`  
✅ **Global JS:** `public/js/textarea-auto-resize.js`  
✅ **Included in:** All dashboard layouts and `layoutAll/app.blade.php`

### 2. Browser Setup

- [ ] Open browser (Chrome/Edge recommended for testing)
- [ ] Open Developer Tools (F12)
- [ ] Go to Console tab
- [ ] Clear cache: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
- [ ] Check for any initial errors in console

---

## Quick Test (15-20 minutes)

### Test 1: Basic Auto-Resize (2 minutes)

1. Navigate to any project edit page (or create new project)
2. Find a textarea (e.g., "Full Address" in General Info, or any textarea in Sustainability section)
3. Type multiple lines of text
4. ✅ **Expected:** Textarea expands automatically as you type
5. ✅ **Console:** No JavaScript errors

**If this fails:** Check if global JS file is loading (check Network tab in DevTools)

---

### Test 2: Dynamic Content (5 minutes)

**Test IGE New Beneficiaries (CREATE view):**

1. Navigate to: Create IGE project
2. Go to "New Beneficiaries" section
3. Click "Add More" button
4. Type in the newly added textarea fields (address, family background)
5. ✅ **Expected:** New textareas auto-resize as you type
6. ✅ **Console:** No errors

**Alternative Test - RST Target Group Annexure:**

1. Navigate to: Create RST project
2. Go to "Target Group Annexure" section
3. Click "Add More"
4. Type in newly added textareas
5. ✅ **Expected:** New textareas auto-resize

---

### Test 3: Logical Framework (3 minutes)

1. Navigate to Logical Framework section (create or edit)
2. Type in "Objective" textarea
3. Type in "Result" textarea
4. Click "Add Result" - type in new result textarea
5. ✅ **Expected:** All textareas auto-resize
6. ✅ **Console:** No errors

---

### Test 4: Visual Check (2 minutes)

1. Check a few forms visually
2. ✅ **Expected:** Textareas have proper spacing, padding
3. ✅ **Expected:** Forms don't look broken
4. ✅ **Expected:** No overlapping elements

---

### Test 5: Edge Case - Paste (2 minutes)

1. Copy multi-line text from somewhere
2. Paste into any textarea
3. ✅ **Expected:** Textarea expands to fit pasted content
4. ✅ **Expected:** No glitches

---

## If All Quick Tests Pass ✅

**You're good to go!** The cleanup was successful.

Optional next steps:
- Run full test suite (1-2 hours) - See `Phase_4_1_Regression_Testing_Checklist.md` (in same folder)
- Test in multiple browsers
- Test more edge cases

---

## If Any Test Fails ❌

### Common Issues & Fixes

#### Issue 1: Textareas not auto-resizing

**Symptom:** Textareas stay same height, don't expand

**Check:**
1. Open browser console (F12)
2. Check for errors (red text)
3. Check Network tab - verify `textarea-auto-resize.js` loads (Status 200)
4. In Console, type: `typeof window.initTextareaAutoResize`
   - ✅ Should return: `"function"`
   - ❌ If `"undefined"`: Global JS not loading

**Fix:** Verify global JS file is included in layout being used

---

#### Issue 2: Dynamic textareas don't work

**Symptom:** Static textareas work, but newly added ones don't auto-resize

**Check:**
1. In browser console, type: `typeof window.initTextareaAutoResize`
   - Should return `"function"`
2. Check the JavaScript code that adds new rows
   - Should call: `window.initTextareaAutoResize(newTextarea)`

**Example fix needed:**
```javascript
// ❌ WRONG (old way)
autoResizeTextarea(newTextarea);

// ✅ CORRECT (new way)
if (typeof window.initTextareaAutoResize === 'function') {
    window.initTextareaAutoResize(newTextarea);
}
```

---

#### Issue 3: Console Errors

**Symptom:** JavaScript errors in console

**Common errors:**
- `initTextareaAutoResize is not a function` → Global JS not loaded
- `Cannot read property 'style' of null` → Textarea element not found (shouldn't happen after cleanup)
- CSS not applied → Check if CSS file loads (Network tab)

---

#### Issue 4: Visual Issues

**Symptom:** Textareas look different or broken

**Check:**
1. Check Network tab - verify `textarea-auto-resize.css` loads
2. Inspect textarea element (right-click → Inspect)
3. Check if CSS classes are applied:
   - `.sustainability-textarea`
   - `.logical-textarea`
   - `.auto-resize-textarea`

---

## Full Test Suite

For comprehensive testing, see:
- **Detailed Checklist:** `Phase_4_1_Regression_Testing_Checklist.md` (in same folder)
- **Testing Guide:** `Phase_4_1_Regression_Testing_Guide.md` (in same folder)

---

## Test Results Template

```
Date: _______________
Tester: _______________
Browser: _______________
Version: _______________

Quick Tests:
[ ] Test 1: Basic Auto-Resize - PASS / FAIL
[ ] Test 2: Dynamic Content - PASS / FAIL  
[ ] Test 3: Logical Framework - PASS / FAIL
[ ] Test 4: Visual Check - PASS / FAIL
[ ] Test 5: Paste Test - PASS / FAIL

Issues Found:
1. (if any)

Overall: PASS / FAIL / PARTIAL
```

---

## Key Files Reference

### Global Files (Must be loaded)
- **CSS:** `public/css/custom/textarea-auto-resize.css`
- **JS:** `public/js/textarea-auto-resize.js`

### CSS Classes
- `.sustainability-textarea` - Standard textareas (min-height: 80px)
- `.logical-textarea` - Logical framework (min-height: 80px)
- `.auto-resize-textarea` - Generic auto-resize (min-height: 80px)
- `.particular-textarea` - Budget textareas (min-height: 38px) - **Special case**

### Global Functions (Available in browser)
- `window.initTextareaAutoResize(textarea)` - Initialize single textarea
- `window.initDynamicTextarea(container)` - Initialize textareas in container
- `window.autoResizeTextarea(textarea)` - Manual resize

---

## Success Criteria

✅ **Minimum (Quick Test):**
- Basic auto-resize works
- Dynamic content works
- No console errors
- Visual appearance correct

✅ **Full Test:**
- All quick tests pass
- All project types work
- All edge cases work
- Multiple browsers work

---

**Ready to test? Start with Quick Test 1 above!**

**Document Version:** 1.0  
**Created:** January 2025  
**Status:** Ready for Testing
