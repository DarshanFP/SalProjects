# Quick Reference: Remaining Work for Text View Layout

**Date:** January 2025  
**Status:** 📋 Reference Guide

---

## Pattern Established ✅

The layout pattern has been successfully implemented for:
- ✅ Monthly Reports (main view + partials)
- ✅ Quarterly Development Project

All remaining reports should follow the **exact same pattern**.

---

## Conversion Pattern

### Before (50/50 Layout)
```html
<div class="mb-3">
    <label class="form-label">Title of the Project</label>
    <p>{{ $report->project_title }}</p>
</div>
```

### After (20/80 Layout)
```html
<div class="info-grid">
    <div class="info-label"><strong>Title of the Project:</strong></div>
    <div class="info-value">{{ $report->project_title }}</div>
</div>
```

### For Bootstrap Column Layouts

**Before:**
```html
<div class="mb-2 row">
    <div class="col-6"><strong>Label:</strong></div>
    <div class="col-6">{{ $value }}</div>
</div>
```

**After:**
```html
<div class="mb-2 row">
    <div class="col-2 report-label-col"><strong>Label:</strong></div>
    <div class="col-10 report-value-col">{{ $value }}</div>
</div>
```

---

## Files to Update

### Quarterly Reports (Follow Development Project Pattern)

1. **`resources/views/reports/quarterly/skillTraining/show.blade.php`**
   - Convert all `label/p` to `info-grid`
   - Add CSS styles (copy from development project)

2. **`resources/views/reports/quarterly/developmentLivelihood/show.blade.php`**
   - Convert all `label/p` to `info-grid`
   - Add CSS styles (copy from development project)

3. **`resources/views/reports/quarterly/institutionalSupport/show.blade.php`**
   - Convert all `label/p` to `info-grid`
   - Add CSS styles (copy from development project)

4. **`resources/views/reports/quarterly/womenInDistress/show.blade.php`**
   - Convert all `label/p` to `info-grid`
   - Add CSS styles (copy from development project)

### Aggregated Reports (Check Structure First)

1. **`resources/views/reports/aggregated/quarterly/show.blade.php`**
   - Check if uses `col-md-6` - convert to `col-md-2` and `col-md-10`
   - Or convert to `info-grid` if using label/value pairs

2. **`resources/views/reports/aggregated/half-yearly/show.blade.php`**
   - Check structure and apply appropriate pattern

3. **`resources/views/reports/aggregated/annual/show.blade.php`**
   - Check structure and apply appropriate pattern

---

## CSS Styles to Add

For each quarterly report show view, add this CSS at the end (before `@endsection`):

```html
<style>
    .info-grid {
        display: grid;
        grid-template-columns: 20% 80%;
        grid-gap: 20px;
        align-items: start;
    }

    .info-label {
        font-weight: bold;
        margin-right: 10px;
        word-wrap: break-word;
        overflow-wrap: break-word;
        word-break: break-word;
        white-space: normal;
    }

    .info-value {
        word-wrap: break-word;
        overflow-wrap: break-word;
        word-break: break-word;
        white-space: normal;
        padding-left: 10px;
    }

    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
            grid-gap: 10px;
        }
        
        .info-label {
            margin-right: 0;
            margin-bottom: 5px;
        }
        
        .info-value {
            padding-left: 0;
        }
    }
</style>
```

---

## Search & Replace Patterns

### Pattern 1: Basic Info Sections
**Find:**
```html
<div class="mb-3">
    <label class="form-label">[LABEL]</label>
    <p>{{ [VALUE] }}</p>
</div>
```

**Replace with:**
```html
<div class="info-label"><strong>[LABEL]:</strong></div>
<div class="info-value">{{ [VALUE] }}</div>
```

*(Wrap multiple in `<div class="info-grid">...</div>`)*

### Pattern 2: Multiple Fields in Card Body
**Find:**
```html
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Label 1</label>
        <p>{{ value1 }}</p>
    </div>
    <div class="mb-3">
        <label class="form-label">Label 2</label>
        <p>{{ value2 }}</p>
    </div>
</div>
```

**Replace with:**
```html
<div class="card-body">
    <div class="info-grid">
        <div class="info-label"><strong>Label 1:</strong></div>
        <div class="info-value">{{ value1 }}</div>
        <div class="info-label"><strong>Label 2:</strong></div>
        <div class="info-value">{{ value2 }}</div>
    </div>
</div>
```

---

## Important Notes

1. **Tables:** Don't change table structures - they're fine as-is
2. **Photos:** Photo sections can keep their grid layouts for images
3. **Consistency:** Use the same pattern throughout each file
4. **Text Wrapping:** All labels and values should have text wrapping classes
5. **Mobile:** Ensure responsive styles are included

---

## Testing Checklist

After updating each file:
- [ ] Visual inspection - labels should be ~20%, values ~80%
- [ ] Test with long text - should wrap properly
- [ ] Test on mobile - should stack vertically
- [ ] Check all sections in the report
- [ ] Verify no horizontal scrolling

---

**Reference:** See `resources/views/reports/quarterly/developmentProject/show.blade.php` for complete example.
