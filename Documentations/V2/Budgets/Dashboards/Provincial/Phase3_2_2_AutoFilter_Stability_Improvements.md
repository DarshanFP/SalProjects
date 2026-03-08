# Phase 3.2.2 — Auto-Filter Stability Improvements

**Date:** 2026-03-05  
**Phase:** Provincial Project List Auto-Filter  
**Goals:**
1. Prevent multiple form submissions when users change filters quickly
2. Ensure Reset buttons preserve the default Financial Year

---

## Summary

- **Submission guard:** A `filterSubmitting` flag blocks subsequent submits until the page navigates.
- **Reset buttons:** Reset links now include `fy={{ currentFY }}` so the default FY is preserved when clearing other filters.

---

## 1. JS Guard Implementation

**Before:**
```javascript
document.querySelectorAll('.auto-filter').forEach(function(el) {
    el.addEventListener('change', function() {
        this.closest('form').submit();
    });
});
```

**After:**
```javascript
var filterSubmitting = false;
document.querySelectorAll('.auto-filter').forEach(function(el) {
    el.addEventListener('change', function() {
        if (filterSubmitting) return;
        filterSubmitting = true;
        this.closest('form').submit();
    });
});
```

**Placement:**
- **ProjectList.blade.php:** Inside `@push('scripts')`, within the `DOMContentLoaded` handler
- **approvedProjects.blade.php:** Inline `<script>` block

**Purpose:** Prevents multiple requests when filters are changed in quick succession before the first navigation starts.

---

## 2. Reset Button Updates

### 2.1 ProjectList.blade.php

**Before:**
```blade
<a href="{{ route('provincial.projects.list') }}" class="btn btn-secondary">Reset</a>
```

**After:**
```blade
<a href="{{ route('provincial.projects.list', ['fy' => \App\Support\FinancialYearHelper::currentFY()]) }}" class="btn btn-secondary">Reset</a>
```

### 2.2 approvedProjects.blade.php

**Before:**
```blade
<a href="{{ route('provincial.approved.projects') }}" class="btn btn-secondary">Reset</a>
```

**After:**
```blade
<a href="{{ route('provincial.approved.projects', ['fy' => \App\Support\FinancialYearHelper::currentFY()]) }}" class="btn btn-secondary">Reset</a>
```

**Result:** Reset clears center, role, project_type, user_id, status, society_id, etc., but keeps the current FY in the URL.

---

## 3. Verification Results

| Item | Status |
|------|--------|
| Dropdown changes auto-submit the form | ✓ |
| Only one request fires per change (guard prevents duplicates) | ✓ |
| Reset button clears filters | ✓ |
| Reset button retains current FY | ✓ |
| Pagination preserves FY and other parameters | ✓ (unchanged; controller uses `withQueryString()`) |

---

## 4. Files Modified

| File | Changes |
|------|---------|
| `resources/views/provincial/ProjectList.blade.php` | Added `filterSubmitting` guard; Reset href includes `fy` |
| `resources/views/provincial/approvedProjects.blade.php` | Added `filterSubmitting` guard; Reset href includes `fy` |
