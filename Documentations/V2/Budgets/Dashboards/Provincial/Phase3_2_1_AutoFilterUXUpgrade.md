# Phase 3.2.1 — Auto Filter UX Upgrade

**Date:** 2026-03-05  
**Phase:** Provincial Project Listing UX  
**Goal:** Remove manual filter buttons and enable automatic filtering when dropdown values change.

---

## Summary

Filter dropdowns now auto-submit the form on change. Users no longer need to click Apply or Filter; selecting a value immediately refreshes the list. Reset buttons remain to clear all filters.

---

## 1. Buttons Removed

| View | Button Removed |
|------|----------------|
| `ProjectList.blade.php` | **Apply** |
| `approvedProjects.blade.php` | **Filter** |

**Reset button:** Retained on both pages. Added Reset to approvedProjects for consistency (previously had no Reset).

---

## 2. Auto-Filter Class Added

**Class:** `auto-filter`

Applied to all filter dropdowns:

| View | Fields with `auto-filter` |
|------|---------------------------|
| ProjectList | fy, project_type, user_id, status, center, society_id |
| approvedProjects | fy, project_type, user_id |

**Example:**
```html
<select name="fy" id="fy" class="form-select auto-filter">
```

---

## 3. Shared JS Listener

**Script block added to both views:**

```javascript
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.auto-filter').forEach(function(el) {
        el.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });
});
```

**Behavior:** On change of any `.auto-filter` element, the containing form is submitted via GET.

**Placement:**
- **ProjectList:** Inside existing `@push('scripts')` block, at start of `DOMContentLoaded` handler
- **approvedProjects:** Inline `<script>` block after the form

---

## 4. Query String Verification

| View | Form method | Action |
|------|-------------|--------|
| ProjectList | `method="GET"` | `route('provincial.projects.list')` |
| approvedProjects | `method="GET"` | `route('provincial.approved.projects')` |

**Result:** All filter parameters are preserved in the URL (e.g. `?fy=2026-27&center=...&status=...`).

---

## 5. Pagination Verification

| View | Pagination | withQueryString |
|------|------------|-----------------|
| ProjectList | `$projects->links()` | ✓ Controller uses `->paginate()->withQueryString()` |
| approvedProjects | `$projects->links()` | ✓ Controller uses `->paginate(25)->withQueryString()` |

Filter parameters (fy, project_type, user_id, center, etc.) persist when navigating pages.

---

## 6. Files Modified

| File | Changes |
|------|---------|
| `resources/views/provincial/ProjectList.blade.php` | Removed Apply button; added `auto-filter` to 6 selects; added JS listener in @push('scripts') |
| `resources/views/provincial/approvedProjects.blade.php` | Removed Filter button; added Reset button; added `auto-filter` to 3 selects; added script block |

---

## 7. Verification

| Item | Status |
|------|--------|
| Apply button removed | ✓ |
| Filter button removed | ✓ |
| Reset button retained/added | ✓ |
| auto-filter class on dropdowns | ✓ |
| JS listener submits form on change | ✓ |
| Form uses method="GET" | ✓ |
| Pagination uses withQueryString | ✓ |
