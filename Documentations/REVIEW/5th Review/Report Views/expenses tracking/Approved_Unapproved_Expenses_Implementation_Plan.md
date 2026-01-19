# Approved vs Unapproved Expenses Tracking - Phase-Wise Implementation Plan

**Date:** January 2025  
**Based on:** Approved_Unapproved_Expenses_Tracking_Analysis.md  
**Total Phases:** 8  
**Estimated Duration:** 6-8 hours

---

## Overview

This plan implements approved vs unapproved expenses tracking across:
- Report Views (Create/Edit/View modes - 19 files)
- Project Budget Views (1 file)
- Service Layer (BudgetValidationService)

---

## Phase 1: Add Helper Method to DPReport Model
**Duration:** 15 minutes  
**Priority:** High

### Tasks:
1. Add `isApproved()` helper method to `DPReport` model
2. Add `getApprovedExpenses()` method (returns expenses if approved, 0 otherwise)
3. Add `getUnapprovedExpenses()` method (returns expenses if not approved, 0 otherwise)

### Files to Modify:
- `app/Models/Reports/Monthly/DPReport.php`

### Implementation:
```php
/**
 * Check if report is approved by coordinator
 */
public function isApproved(): bool
{
    return $this->status === self::STATUS_APPROVED_BY_COORDINATOR;
}

/**
 * Get approved expenses (only if report is approved)
 */
public function getApprovedExpenses(): float
{
    if (!$this->isApproved()) {
        return 0.0;
    }
    
    if (!$this->relationLoaded('accountDetails')) {
        $this->load('accountDetails');
    }
    
    return $this->accountDetails->sum('total_expenses') ?? 0.0;
}

/**
 * Get unapproved expenses (only if report is not approved)
 */
public function getUnapprovedExpenses(): float
{
    if ($this->isApproved()) {
        return 0.0;
    }
    
    if (!$this->relationLoaded('accountDetails')) {
        $this->load('accountDetails');
    }
    
    return $this->accountDetails->sum('total_expenses') ?? 0.0;
}
```

### Deliverable:
Helper methods added to DPReport model for easy expense calculation

---

## Phase 2: Update BudgetValidationService
**Duration:** 45 minutes  
**Priority:** High

### Tasks:
1. Update `calculateBudgetData()` to include approved/unapproved expenses
2. Add new method `getApprovedUnapprovedExpenses()` for project-level calculations
3. Ensure proper eager loading of relationships

### Files to Modify:
- `app/Services/BudgetValidationService.php`

### Implementation:
```php
// Update calculateBudgetData() method
private static function calculateBudgetData(Project $project): array
{
    // ... existing code ...
    
    // Calculate expenses from reports - SEPARATE APPROVED AND UNAPPROVED
    $approvedExpenses = 0;
    $unapprovedExpenses = 0;
    
    try {
        if (!$project->relationLoaded('reports')) {
            $project->load('reports.accountDetails');
        }
        
        foreach ($project->reports as $report) {
            if (!$report->relationLoaded('accountDetails')) {
                $report->load('accountDetails');
            }
            
            $reportExpenses = $report->accountDetails->sum('total_expenses') ?? 0;
            
            if ($report->status === \App\Models\Reports\Monthly\DPReport::STATUS_APPROVED_BY_COORDINATOR) {
                $approvedExpenses += $reportExpenses;
            } else {
                $unapprovedExpenses += $reportExpenses;
            }
        }
        
        $totalExpenses = $approvedExpenses + $unapprovedExpenses;
    } catch (\Exception $e) {
        Log::warning('Error calculating expenses', [
            'project_id' => $project->project_id,
            'error' => $e->getMessage()
        ]);
    }
    
    // ... existing code ...
    
    return [
        // ... existing fields ...
        'total_expenses' => $totalExpenses,
        'approved_expenses' => $approvedExpenses,
        'unapproved_expenses' => $unapprovedExpenses,
        'approved_percentage' => $openingBalance > 0 ? ($approvedExpenses / $openingBalance) * 100 : 0,
        'unapproved_percentage' => $openingBalance > 0 ? ($unapprovedExpenses / $openingBalance) * 100 : 0,
        // ... rest of fields ...
    ];
}
```

### Deliverable:
BudgetValidationService updated to calculate approved/unapproved expenses separately

---

## Phase 3: Update Report View Mode Partials (6 files)
**Duration:** 1.5 hours  
**Priority:** High

### Tasks:
1. Add approved/unapproved expense calculations in PHP
2. Add two new budget cards: "Approved Expenses" and "Unapproved Expenses"
3. Update progress bar to show two segments (approved + unapproved)
4. Update existing "Total Expenses" card to show breakdown

### Files to Modify:
- `resources/views/reports/monthly/partials/view/statements_of_account/development_projects.blade.php`
- `resources/views/reports/monthly/partials/view/statements_of_account/individual_health.blade.php`
- `resources/views/reports/monthly/partials/view/statements_of_account/individual_livelihood.blade.php`
- `resources/views/reports/monthly/partials/view/statements_of_account/individual_education.blade.php`
- `resources/views/reports/monthly/partials/view/statements_of_account/individual_ongoing_education.blade.php`
- `resources/views/reports/monthly/partials/view/statements_of_account/institutional_education.blade.php`

### Implementation Pattern:
```php
@php
    // Calculate budget summary values
    $totalBudget = $report->amount_sanctioned_overview ?? 0;
    $totalExpenses = $report->accountDetails->sum('total_expenses') ?? 0;
    
    // Calculate approved vs unapproved
    $isApproved = $report->status === \App\Models\Reports\Monthly\DPReport::STATUS_APPROVED_BY_COORDINATOR;
    $approvedExpenses = $isApproved ? $totalExpenses : 0;
    $unapprovedExpenses = $isApproved ? 0 : $totalExpenses;
    
    $remainingBalance = $totalBudget - $totalExpenses;
    $utilizationPercent = $totalBudget > 0 ? ($totalExpenses / $totalBudget) * 100 : 0;
    $approvedPercent = $totalBudget > 0 ? ($approvedExpenses / $totalBudget) * 100 : 0;
    $unapprovedPercent = $totalBudget > 0 ? ($unapprovedExpenses / $totalBudget) * 100 : 0;
    $remainingPercent = 100 - $utilizationPercent;
@endphp

<!-- Add two new cards after "Total Expenses" card -->
<div class="budget-summary-card budget-card-success">
    <div class="budget-summary-label">
        <i class="feather icon-check-circle"></i> Approved Expenses
    </div>
    <div class="budget-summary-value">Rs. {{ number_format($approvedExpenses, 2) }}</div>
    <div class="budget-summary-note">Coordinator approved</div>
</div>

<div class="budget-summary-card budget-card-warning">
    <div class="budget-summary-label">
        <i class="feather icon-clock"></i> Unapproved Expenses
    </div>
    <div class="budget-summary-value">Rs. {{ number_format($unapprovedExpenses, 2) }}</div>
    <div class="budget-summary-note">Pending approval</div>
</div>

<!-- Update progress bar to show two segments -->
<div class="progress" style="height: 25px;">
    <div class="progress-bar bg-success" 
         style="width: {{ $approvedPercent }}%"
         title="Approved: Rs. {{ number_format($approvedExpenses, 2) }}">
        @if($approvedPercent > 5)
            <strong>{{ number_format($approvedPercent, 1) }}%</strong>
        @endif
    </div>
    <div class="progress-bar bg-warning" 
         style="width: {{ $unapprovedPercent }}%"
         title="Pending: Rs. {{ number_format($unapprovedExpenses, 2) }}">
        @if($unapprovedPercent > 5)
            <strong>{{ number_format($unapprovedPercent, 1) }}%</strong>
        @endif
    </div>
</div>
```

### Deliverable:
All 6 view mode partials updated with approved/unapproved expense tracking

---

## Phase 4: Update Report Edit Mode Partials (6 files)
**Duration:** 1.5 hours  
**Priority:** High

### Tasks:
1. Add approved/unapproved expense calculations in JavaScript
2. Add two new budget cards (same as view mode)
3. Update progress bar JavaScript to show two segments
4. Update `updateBudgetSummaryCards()` function

### Files to Modify:
- `resources/views/reports/monthly/partials/edit/statements_of_account/development_projects.blade.php`
- `resources/views/reports/monthly/partials/edit/statements_of_account/individual_health.blade.php`
- `resources/views/reports/monthly/partials/edit/statements_of_account/individual_livelihood.blade.php`
- `resources/views/reports/monthly/partials/edit/statements_of_account/individual_education.blade.php`
- `resources/views/reports/monthly/partials/edit/statements_of_account/individual_ongoing_education.blade.php`
- `resources/views/reports/monthly/partials/edit/statements_of_account/institutional_education.blade.php`

### Implementation Pattern:
```javascript
function updateBudgetSummaryCards() {
    // ... existing code ...
    
    // Check if report is approved (from hidden input or data attribute)
    const reportStatus = document.querySelector('[name="report_status"]')?.value || 
                         document.querySelector('[data-report-status]')?.dataset.reportStatus || 
                         'draft';
    const isApproved = reportStatus === 'approved_by_coordinator';
    
    const approvedExpenses = isApproved ? totalExpenses : 0;
    const unapprovedExpenses = isApproved ? 0 : totalExpenses;
    
    const approvedPercent = totalBudget > 0 ? (approvedExpenses / totalBudget) * 100 : 0;
    const unapprovedPercent = totalBudget > 0 ? (unapprovedExpenses / totalBudget) * 100 : 0;
    
    // Update approved expenses card
    const cardApprovedExpenses = document.getElementById('card-approved-expenses');
    if (cardApprovedExpenses) {
        cardApprovedExpenses.textContent = 'Rs. ' + approvedExpenses.toLocaleString('en-IN', {
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2
        });
    }
    
    // Update unapproved expenses card
    const cardUnapprovedExpenses = document.getElementById('card-unapproved-expenses');
    if (cardUnapprovedExpenses) {
        cardUnapprovedExpenses.textContent = 'Rs. ' + unapprovedExpenses.toLocaleString('en-IN', {
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2
        });
    }
    
    // Update progress bar with two segments
    const progressBar = document.getElementById('progress-bar');
    if (progressBar) {
        progressBar.innerHTML = `
            <div class="progress-bar bg-success" 
                 style="width: ${approvedPercent}%"
                 title="Approved: Rs. ${approvedExpenses.toFixed(2)}">
                ${approvedPercent > 5 ? `<strong>${approvedPercent.toFixed(1)}%</strong>` : ''}
            </div>
            <div class="progress-bar bg-warning" 
                 style="width: ${unapprovedPercent}%"
                 title="Pending: Rs. ${unapprovedExpenses.toFixed(2)}">
                ${unapprovedPercent > 5 ? `<strong>${unapprovedPercent.toFixed(1)}%</strong>` : ''}
            </div>
        `;
    }
}
```

**Note:** Need to add hidden input in edit form to pass report status:
```html
<input type="hidden" name="report_status" value="{{ $report->status ?? 'draft' }}">
```

### Deliverable:
All 6 edit mode partials updated with approved/unapproved expense tracking

---

## Phase 5: Update Report Create Mode Partials (7 files)
**Duration:** 1.5 hours  
**Priority:** Medium

### Tasks:
1. Add approved/unapproved expense calculations in JavaScript
2. Add two new budget cards (same as edit mode)
3. Update progress bar JavaScript
4. In create mode, all expenses are "unapproved" (report not yet submitted)

### Files to Modify:
- `resources/views/reports/monthly/partials/create/statements_of_account.blade.php`
- `resources/views/reports/monthly/partials/statements_of_account/development_projects.blade.php` (create section)
- `resources/views/reports/monthly/partials/statements_of_account/individual_health.blade.php` (create section)
- `resources/views/reports/monthly/partials/statements_of_account/individual_livelihood.blade.php` (create section)
- `resources/views/reports/monthly/partials/statements_of_account/individual_education.blade.php` (create section)
- `resources/views/reports/monthly/partials/statements_of_account/individual_ongoing_education.blade.php` (create section)
- `resources/views/reports/monthly/partials/statements_of_account/institutional_education.blade.php` (create section)

### Implementation:
Same as Phase 4, but:
- `isApproved = false` (always unapproved in create mode)
- `approvedExpenses = 0`
- `unapprovedExpenses = totalExpenses`

### Deliverable:
All 7 create mode partials updated with approved/unapproved expense tracking

---

## Phase 6: Update Project Budget View
**Duration:** 1 hour  
**Priority:** High

### Tasks:
1. Update `budget.blade.php` to use BudgetValidationService data
2. Add two new budget cards: "Approved Expenses" and "Unapproved Expenses"
3. Update progress bar to show two segments
4. Update existing "Total Expenses" card to show breakdown

### Files to Modify:
- `resources/views/projects/partials/Show/budget.blade.php`

### Implementation:
```php
@php
    // Use BudgetValidationService to get validated budget data
    use App\Services\BudgetValidationService;
    $budgetSummary = BudgetValidationService::getBudgetSummary($project);
    $budgetData = $budgetSummary['budget_data'];
    
    // Extract values
    $openingBalance = $budgetData['opening_balance'];
    $totalExpenses = $budgetData['total_expenses'];
    $approvedExpenses = $budgetData['approved_expenses'] ?? 0;
    $unapprovedExpenses = $budgetData['unapproved_expenses'] ?? 0;
    $remainingBalance = $budgetData['remaining_balance'];
    $percentageUsed = $budgetData['percentage_used'];
    $approvedPercent = $budgetData['approved_percentage'] ?? 0;
    $unapprovedPercent = $budgetData['unapproved_percentage'] ?? 0;
@endphp

<!-- Add two new cards after "Total Expenses" card -->
<div class="budget-summary-card budget-card-success">
    <div class="budget-summary-label">
        <i class="feather icon-check-circle"></i> Approved Expenses
    </div>
    <div class="budget-summary-value">Rs. {{ number_format($approvedExpenses, 2) }}</div>
    <div class="budget-summary-note">From approved reports</div>
</div>

<div class="budget-summary-card budget-card-warning">
    <div class="budget-summary-label">
        <i class="feather icon-clock"></i> Unapproved Expenses
    </div>
    <div class="budget-summary-value">Rs. {{ number_format($unapprovedExpenses, 2) }}</div>
    <div class="budget-summary-note">Pending approval</div>
</div>

<!-- Update progress bar -->
<div class="progress" style="height: 25px;">
    <div class="progress-bar bg-success" 
         style="width: {{ $approvedPercent }}%"
         title="Approved: Rs. {{ number_format($approvedExpenses, 2) }}">
        @if($approvedPercent > 5)
            <strong>{{ number_format($approvedPercent, 1) }}%</strong>
        @endif
    </div>
    <div class="progress-bar bg-warning" 
         style="width: {{ $unapprovedPercent }}%"
         title="Pending: Rs. {{ number_format($unapprovedExpenses, 2) }}">
        @if($unapprovedPercent > 5)
            <strong>{{ number_format($unapprovedPercent, 1) }}%</strong>
        @endif
    </div>
</div>
```

### Deliverable:
Project budget view updated with approved/unapproved expense tracking

---

## Phase 7: Update Controllers (Consistency Check)
**Duration:** 30 minutes  
**Priority:** Medium

### Tasks:
1. Review controllers that calculate expenses
2. Ensure they use approved reports only where appropriate
3. Update if needed for consistency

### Files to Review:
- `app/Http/Controllers/ExecutorController.php` (already filters by approved)
- `app/Http/Controllers/CoordinatorController.php` (already filters by approved)
- `app/Http/Controllers/ProvincialController.php` (check if needs update)

### Implementation:
Ensure all controllers that calculate project expenses use:
```php
$approvedExpenses = $project->reports
    ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
    ->sum(function($report) {
        return $report->accountDetails->sum('total_expenses') ?? 0;
    });
```

### Deliverable:
All controllers consistently use approved reports for expense calculations

---

## Phase 8: Testing & Verification
**Duration:** 1 hour  
**Priority:** High

### Test Scenarios:

1. **Report View - Approved Report:**
   - View a report with status `approved_by_coordinator`
   - Verify: Approved Expenses = Total Expenses, Unapproved = 0
   - Verify: Progress bar shows only green segment

2. **Report View - Unapproved Report:**
   - View a report with status `draft` or `submitted_to_provincial`
   - Verify: Approved Expenses = 0, Unapproved = Total Expenses
   - Verify: Progress bar shows only yellow segment

3. **Report Edit - Approved Report:**
   - Edit an approved report
   - Verify: Cards show approved expenses correctly
   - Verify: Progress bar updates correctly

4. **Report Create:**
   - Create a new report
   - Verify: All expenses shown as unapproved
   - Verify: Progress bar shows only yellow segment

5. **Project View - Mixed Reports:**
   - Project with both approved and unapproved reports
   - Verify: Approved Expenses = sum of approved reports
   - Verify: Unapproved Expenses = sum of unapproved reports
   - Verify: Progress bar shows both segments correctly

6. **Project View - All Approved:**
   - Project with only approved reports
   - Verify: Unapproved Expenses = 0
   - Verify: Progress bar shows only green segment

7. **Project View - All Unapproved:**
   - Project with only unapproved reports
   - Verify: Approved Expenses = 0
   - Verify: Progress bar shows only yellow segment

8. **Approval Workflow:**
   - Approve a report via coordinator
   - Verify: Project view updates to show expenses as approved
   - Verify: Report view shows expenses as approved

9. **Revert Workflow:**
   - Revert an approved report
   - Verify: Expenses become unapproved again
   - Verify: Project view updates accordingly

### Deliverable:
All test scenarios pass, feature working correctly

---

## Summary

### Total Files to Modify: 27
- Models: 1 file
- Services: 1 file
- Report Views: 19 files (7 create + 6 edit + 6 view)
- Project Views: 1 file
- Controllers: 3-5 files (review/update)

### Key Features:
1. ✅ Approved/Unapproved expense tracking in reports
2. ✅ Approved/Unapproved expense tracking in projects
3. ✅ Visual differentiation in budget cards
4. ✅ Dual-segment progress bars
5. ✅ Status-based automatic calculation
6. ✅ No database changes required

### Benefits:
- Clear financial visibility
- Better decision making
- Improved budget control
- Audit trail visualization

---

## Notes

1. **No Database Migration Needed:** Uses existing `status` field in `DP_Reports` table
2. **Backward Compatible:** Existing reports treated as unapproved if not approved
3. **Performance:** Uses eager loading to minimize queries
4. **Consistency:** All calculations use same logic across views

---

## Next Steps After Implementation

1. User acceptance testing
2. Documentation updates
3. Training materials if needed
4. Monitor performance in production
