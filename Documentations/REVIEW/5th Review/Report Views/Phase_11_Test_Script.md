# Phase 11: Test Script for Report Views Enhancement
## Automated Verification & Manual Testing Guide

**Date:** January 2025  
**Purpose:** Systematic testing of field indexing and card-based UI across all project types

---

## Pre-Testing Setup

### 1. Environment Check
- [ ] Laravel application is running
- [ ] Database is accessible
- [ ] Test projects exist for all 12 project types
- [ ] Browser developer tools are open (F12)
- [ ] Console tab is visible for JavaScript error checking

### 2. Test Data Preparation
Create or verify test projects for each project type:

**Institutional Types:**
- [ ] Development Projects
- [ ] Livelihood Development Projects (LDP)
- [ ] Residential Skill Training Proposal 2 (RST)
- [ ] PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER (CIC)
- [ ] CHILD CARE INSTITUTION
- [ ] Rural-Urban-Tribal
- [ ] Institutional Ongoing Group Educational proposal (IGE)
- [ ] NEXT PHASE - DEVELOPMENT PROPOSAL

**Individual Types:**
- [ ] Individual - Livelihood Application (ILP)
- [ ] Individual - Access to Health (IAH)
- [ ] Individual - Ongoing Educational support (IES)
- [ ] Individual - Initial - Educational support (IIES)

---

## Automated Code Verification Script

Run this PHP script to verify code consistency:

```php
<?php
// test_phase11_verification.php
// Run: php test_phase11_verification.php

$basePath = __DIR__ . '/../../../../resources/views/reports/monthly/';

$filesToCheck = [
    // Main views
    'ReportAll.blade.php',
    'edit.blade.php',
    
    // Common partials - create
    'partials/create/objectives.blade.php',
    'partials/create/photos.blade.php',
    'partials/create/attachments.blade.php',
    
    // Common partials - edit
    'partials/edit/objectives.blade.php',
    'partials/edit/photos.blade.php',
    'partials/edit/attachments.blade.php',
    
    // Statements of account - create
    'partials/create/statements_of_account.blade.php',
    'partials/statements_of_account/development_projects.blade.php',
    'partials/statements_of_account/individual_livelihood.blade.php',
    'partials/statements_of_account/individual_health.blade.php',
    'partials/statements_of_account/individual_education.blade.php',
    'partials/statements_of_account/individual_ongoing_education.blade.php',
    'partials/statements_of_account/institutional_education.blade.php',
    
    // Statements of account - edit
    'partials/edit/statements_of_account.blade.php',
    'partials/edit/statements_of_account/development_projects.blade.php',
    'partials/edit/statements_of_account/individual_livelihood.blade.php',
    'partials/edit/statements_of_account/individual_health.blade.php',
    'partials/edit/statements_of_account/individual_education.blade.php',
    'partials/edit/statements_of_account/individual_ongoing_education.blade.php',
    'partials/edit/statements_of_account/institutional_education.blade.php',
    
    // Project type specific
    'partials/create/LivelihoodAnnexure.blade.php',
];

$issues = [];

foreach ($filesToCheck as $file) {
    $fullPath = $basePath . $file;
    if (!file_exists($fullPath)) {
        $issues[] = "❌ File not found: $file";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    
    // Check for required functions
    if (strpos($file, 'ReportAll.blade.php') !== false || strpos($file, 'edit.blade.php') !== false) {
        if (strpos($file, 'outlook') === false && strpos($content, 'reindexOutlooks') === false) {
            // Outlook section check
            if (strpos($content, 'outlook') !== false && strpos($content, 'reindexOutlooks') === false) {
                $issues[] = "⚠️  Missing reindexOutlooks() in: $file";
            }
        }
    }
    
    if (strpos($file, 'objectives') !== false) {
        if (strpos($content, 'reindexActivities') === false) {
            $issues[] = "⚠️  Missing reindexActivities() in: $file";
        }
        if (strpos($content, 'toggleActivityCard') === false) {
            $issues[] = "⚠️  Missing toggleActivityCard() in: $file";
        }
        if (strpos($content, 'updateActivityStatus') === false) {
            $issues[] = "⚠️  Missing updateActivityStatus() in: $file";
        }
        if (strpos($content, 'activity-card') === false) {
            $issues[] = "⚠️  Missing activity-card class in: $file";
        }
    }
    
    if (strpos($file, 'photos') !== false) {
        if (strpos($content, 'reindexPhotoGroups') === false) {
            $issues[] = "⚠️  Missing reindexPhotoGroups() in: $file";
        }
    }
    
    if (strpos($file, 'attachments') !== false) {
        if (strpos($content, 'reindexAttachments') === false && strpos($content, 'reindexNewAttachments') === false) {
            $issues[] = "⚠️  Missing reindex function in: $file";
        }
    }
    
    if (strpos($file, 'statements_of_account') !== false) {
        if (strpos($content, 'reindexAccountRows') === false) {
            $issues[] = "⚠️  Missing reindexAccountRows() in: $file";
        }
        if (strpos($content, '<th>No.</th>') === false && strpos($content, '<th>No.') === false) {
            $issues[] = "⚠️  Missing 'No.' column header in: $file";
        }
    }
    
    if (strpos($file, 'LivelihoodAnnexure') !== false) {
        if (strpos($content, 'dla_updateImpactGroupIndexes') === false) {
            $issues[] = "⚠️  Missing dla_updateImpactGroupIndexes() in: $file";
        }
    }
}

echo "=== Phase 11 Code Verification ===\n\n";
if (empty($issues)) {
    echo "✅ All files verified successfully!\n";
} else {
    echo "Issues found:\n";
    foreach ($issues as $issue) {
        echo "$issue\n";
    }
}
echo "\n";
```

---

## Manual Testing Script

### Test Session 1: Create Report - Development Projects

**URL:** `http://localhost:8000/reports/monthly/create/{project_id}`

#### Step 1: Outlook Section
```javascript
// Open browser console and run:
console.log("=== Testing Outlook Section ===");

// Check if reindexOutlooks function exists
console.log("reindexOutlooks exists:", typeof reindexOutlooks === 'function');

// Count initial outlooks
const initialOutlooks = document.querySelectorAll('.outlook').length;
console.log("Initial outlooks:", initialOutlooks);

// Add an outlook
addOutlook();
const afterAdd = document.querySelectorAll('.outlook').length;
console.log("After add:", afterAdd);

// Check index badges
document.querySelectorAll('.outlook').forEach((outlook, index) => {
    const badge = outlook.querySelector('.badge.bg-primary');
    const expectedIndex = index + 1;
    const actualIndex = badge ? parseInt(badge.textContent) : null;
    console.log(`Outlook ${index + 1}: Expected ${expectedIndex}, Got ${actualIndex}`, 
                expectedIndex === actualIndex ? '✅' : '❌');
});

// Remove an outlook
const removeBtn = document.querySelector('.outlook:not(:first-child) .remove-outlook');
if (removeBtn) {
    removeBtn.click();
    console.log("Removed an outlook");
    
    // Check reindexing
    document.querySelectorAll('.outlook').forEach((outlook, index) => {
        const badge = outlook.querySelector('.badge.bg-primary');
        const expectedIndex = index + 1;
        const actualIndex = badge ? parseInt(badge.textContent) : null;
        console.log(`After remove - Outlook ${index + 1}: Expected ${expectedIndex}, Got ${actualIndex}`, 
                    expectedIndex === actualIndex ? '✅' : '❌');
    });
}
```

#### Step 2: Statements of Account
```javascript
// Check if reindexAccountRows function exists
console.log("reindexAccountRows exists:", typeof reindexAccountRows === 'function');

// Check for "No." column
const hasNoColumn = document.querySelector('thead th:first-child')?.textContent.includes('No.');
console.log("Has 'No.' column:", hasNoColumn ? '✅' : '❌');

// Count initial rows
const initialRows = document.querySelectorAll('#account-rows tr').length;
console.log("Initial rows:", initialRows);

// Add a row
addAccountRow();
const afterAdd = document.querySelectorAll('#account-rows tr').length;
console.log("After add:", afterAdd);

// Check index numbers
document.querySelectorAll('#account-rows tr').forEach((row, index) => {
    const indexCell = row.querySelector('td:first-child');
    const expectedIndex = index + 1;
    const actualIndex = indexCell ? parseInt(indexCell.textContent) : null;
    console.log(`Row ${index + 1}: Expected ${expectedIndex}, Got ${actualIndex}`, 
                expectedIndex === actualIndex ? '✅' : '❌');
});
```

#### Step 3: Photos Section
```javascript
// Check if reindexPhotoGroups function exists
console.log("reindexPhotoGroups exists:", typeof reindexPhotoGroups === 'function');

// Count initial photo groups
const initialGroups = document.querySelectorAll('#new-photos-container .photo-group').length;
console.log("Initial photo groups:", initialGroups);

// Add a photo group
addPhotoGroup();
const afterAdd = document.querySelectorAll('#new-photos-container .photo-group').length;
console.log("After add:", afterAdd);

// Check index badges
document.querySelectorAll('#new-photos-container .photo-group').forEach((group, index) => {
    const badge = group.querySelector('.badge.bg-info');
    const expectedIndex = index + 1;
    const actualIndex = badge ? parseInt(badge.textContent) : null;
    console.log(`Photo Group ${index + 1}: Expected ${expectedIndex}, Got ${actualIndex}`, 
                expectedIndex === actualIndex ? '✅' : '❌');
});
```

#### Step 4: Activities Card UI
```javascript
// Check if required functions exist
console.log("toggleActivityCard exists:", typeof toggleActivityCard === 'function');
console.log("updateActivityStatus exists:", typeof updateActivityStatus === 'function');
console.log("reindexActivities exists:", typeof reindexActivities === 'function');

// Count initial activities
const initialActivities = document.querySelectorAll('.activity-card').length;
console.log("Initial activities:", initialActivities);

// Check if activities are collapsed by default
document.querySelectorAll('.activity-card').forEach((card, index) => {
    const form = card.querySelector('.activity-form');
    const isCollapsed = form && (form.style.display === 'none' || !form.style.display);
    console.log(`Activity ${index + 1} collapsed:`, isCollapsed ? '✅' : '❌');
    
    // Check status badge
    const statusBadge = card.querySelector('.activity-status');
    console.log(`Activity ${index + 1} status badge:`, statusBadge ? '✅' : '❌');
    
    // Check index badge
    const indexBadge = card.querySelector('.badge.bg-success');
    const expectedIndex = index + 1;
    const actualIndex = indexBadge ? parseInt(indexBadge.textContent) : null;
    console.log(`Activity ${index + 1} index: Expected ${expectedIndex}, Got ${actualIndex}`, 
                expectedIndex === actualIndex ? '✅' : '❌');
});

// Test card toggle
const firstCard = document.querySelector('.activity-card');
if (firstCard) {
    const header = firstCard.querySelector('.activity-card-header');
    const form = firstCard.querySelector('.activity-form');
    const initialState = form.style.display === 'none' || !form.style.display;
    
    header.click();
    const afterClick = form.style.display !== 'none';
    console.log("Card toggle test:", initialState && afterClick ? '✅' : '❌');
    
    // Click again to collapse
    header.click();
}

// Test status update
const firstActivity = document.querySelector('.activity-card');
if (firstActivity) {
    const monthSelect = firstActivity.querySelector('select[name^="month"]');
    if (monthSelect) {
        monthSelect.value = '1';
        monthSelect.dispatchEvent(new Event('change'));
        console.log("Status update test triggered");
    }
}
```

#### Step 5: Attachments Section
```javascript
// Check if reindexNewAttachments function exists
console.log("reindexNewAttachments exists:", typeof reindexNewAttachments === 'function');

// Count initial attachments
const initialAttachments = document.querySelectorAll('.new-attachment-group').length;
console.log("Initial attachments:", initialAttachments);

// Add an attachment
addNewAttachment();
const afterAdd = document.querySelectorAll('.new-attachment-group').length;
console.log("After add:", afterAdd);

// Check index badges
document.querySelectorAll('.new-attachment-group').forEach((group, index) => {
    const badge = group.querySelector('.badge.bg-secondary');
    const expectedIndex = index + 1;
    const actualIndex = badge ? parseInt(badge.textContent) : null;
    console.log(`Attachment ${index + 1}: Expected ${expectedIndex}, Got ${actualIndex}`, 
                expectedIndex === actualIndex ? '✅' : '❌');
});
```

---

## Quick Test Checklist (Copy-Paste Format)

### For Each Project Type:

```
Project Type: _______________________
Test Date: _______________________
Tester: _______________________

[ ] Outlook Section
    [ ] Index badges display correctly
    [ ] Add/remove works
    [ ] Reindexing works
    [ ] No console errors

[ ] Statements of Account
    [ ] "No." column visible
    [ ] Index numbers correct
    [ ] Add/remove works
    [ ] Reindexing works
    [ ] Calculations work
    [ ] No console errors

[ ] Photos Section
    [ ] Index badges display correctly
    [ ] Add/remove works
    [ ] Reindexing works
    [ ] File uploads work
    [ ] No console errors

[ ] Activities Section
    [ ] Cards display correctly
    [ ] Cards collapsed by default
    [ ] Toggle works
    [ ] Status badges update
    [ ] Index badges correct
    [ ] Add/remove works
    [ ] Reindexing works
    [ ] No console errors

[ ] Attachments Section
    [ ] Index badges display correctly
    [ ] Add/remove works
    [ ] Reindexing works
    [ ] No console errors

[ ] Form Submission
    [ ] Form submits successfully
    [ ] Data saves correctly
    [ ] No validation errors

[ ] Edit Mode (if applicable)
    [ ] Existing data displays correctly
    [ ] Index numbers correct
    [ ] Add/remove works
    [ ] Changes save correctly

Issues Found:
1. 
2. 
3. 

Status: [ ] Pass / [ ] Fail
```

---

## Common Issues to Watch For

### JavaScript Errors
- [ ] `reindexOutlooks is not defined`
- [ ] `reindexAccountRows is not defined`
- [ ] `reindexPhotoGroups is not defined`
- [ ] `reindexAttachments is not defined`
- [ ] `toggleActivityCard is not defined`
- [ ] `updateActivityStatus is not defined`

### Visual Issues
- [ ] Index badges not visible
- [ ] Index numbers incorrect
- [ ] Cards not collapsing/expanding
- [ ] Status badges not updating
- [ ] Styling issues

### Functional Issues
- [ ] Reindexing not working after remove
- [ ] Form fields not updating names
- [ ] Calculations breaking after reindexing
- [ ] File uploads not working after reindexing
- [ ] Form submission failing

---

## Testing Order Recommendation

1. **Start with Individual Project Types** (simpler, fewer sections)
   - Individual - Livelihood Application
   - Individual - Access to Health
   - Individual - Ongoing Educational support
   - Individual - Initial - Educational support

2. **Then Institutional Types** (more complex)
   - Development Projects
   - CHILD CARE INSTITUTION
   - Rural-Urban-Tribal
   - Institutional Ongoing Group Educational proposal
   - NEXT PHASE - DEVELOPMENT PROPOSAL

3. **Finally Special Types** (with additional sections)
   - Livelihood Development Projects (Annexure)
   - Residential Skill Training Proposal 2 (Trainee Profile)
   - PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER (Inmates Profile)

---

## Next Steps After Testing

1. Document all issues in the Issues Tracking Document
2. Prioritize issues (Critical, High, Medium, Low)
3. Fix issues one by one
4. Re-test after each fix
5. Update test results
6. Proceed to Phase 12 only after all critical issues are resolved

---

**Script Version:** 1.0  
**Last Updated:** January 2025
