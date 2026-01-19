# Approved vs Unapproved Expenses Tracking - Analysis & Implementation Plan

**Date:** January 2025  
**Purpose:** Track and display approved vs unapproved expenses in reports and projects  
**Location:** Report Views & Project Budget Views

---

## Executive Summary

Currently, the system calculates total expenses from all reports regardless of their approval status. This analysis proposes implementing a tracking system to differentiate between:
- **Approved Expenses**: Expenses from reports with status `approved_by_coordinator`
- **Unapproved Expenses**: Expenses from reports that are not yet approved (draft, submitted, forwarded, reverted)

This will provide better financial visibility and help coordinators understand which expenses have been officially approved and which are pending approval.

---

## Current State Analysis

### 1. Report Approval Workflow

**Status Flow:**
```
draft → submitted_to_provincial → forwarded_to_coordinator → approved_by_coordinator
                                 ↓
                         reverted_by_coordinator (can be edited and resubmitted)
```

**Key Status Constants:**
- `STATUS_APPROVED_BY_COORDINATOR = 'approved_by_coordinator'` - Final approved state
- All other statuses are considered "unapproved"

### 2. Current Expense Calculation

#### In Reports (Account Details Section)
**Location:** `resources/views/reports/monthly/partials/view/statements_of_account/*.blade.php`

**Current Implementation:**
- Shows total expenses from `$report->accountDetails->sum('total_expenses')`
- No differentiation between approved/unapproved
- Budget cards show total expenses without approval status

**Files Affected:**
- `development_projects.blade.php`
- `individual_health.blade.php`
- `individual_livelihood.blade.php`
- `individual_education.blade.php`
- `individual_ongoing_education.blade.php`
- `institutional_education.blade.php`

#### In Projects (Budget Overview)
**Location:** `app/Services/BudgetValidationService.php`

**Current Implementation (Line 68-73):**
```php
$totalExpenses = $project->reports->sum(function($report) {
    return $report->accountDetails->sum('total_expenses') ?? 0;
});
```

**Issue:** Calculates expenses from ALL reports, regardless of approval status.

**Note:** Some controllers (ExecutorController, CoordinatorController) already filter by `STATUS_APPROVED_BY_COORDINATOR`, but this is inconsistent.

**Location:** `resources/views/projects/partials/Show/budget.blade.php`

**Current Implementation:**
- Shows total expenses from all reports
- Budget cards display: Total Budget, Total Expenses, Remaining Balance, Utilization %
- No approved/unapproved breakdown

### 3. Database Structure

**Tables:**
- `DP_Reports` - Contains `status` field
- `DP_AccountDetails` - Contains `total_expenses` per account detail
- Relationship: `DPReport` hasMany `DPAccountDetail`

**No Additional Fields Needed:** We can determine approval status from `DP_Reports.status` field.

---

## Requirements

### 1. Report Views (Create/Edit/View)

**Account Details Section:**
- Add two new budget summary cards:
  - **Approved Expenses** (Green card) - Only if report is approved
  - **Unapproved Expenses** (Yellow/Orange card) - Expenses pending approval
- Update existing "Total Expenses" card to show breakdown:
  - Approved: Rs. X.XX
  - Pending: Rs. Y.YY
- Update Budget Utilization Progress Bar:
  - Show two segments: Approved (Green) and Pending Approval (Yellow/Orange)
  - Visual differentiation: `[====Approved====][==Pending==]`

**Logic:**
- **Create Mode:** All expenses are "unapproved" (report not yet submitted)
- **Edit Mode:** 
  - If `status === 'approved_by_coordinator'`: All expenses are "approved"
  - Otherwise: All expenses are "unapproved"
- **View Mode:**
  - If `status === 'approved_by_coordinator'`: All expenses are "approved"
  - Otherwise: All expenses are "unapproved"

### 2. Project Views (Budget Overview)

**Budget Summary Cards:**
- Add two new cards:
  - **Approved Expenses** (Green) - Sum of expenses from approved reports
  - **Unapproved Expenses** (Yellow/Orange) - Sum of expenses from unapproved reports
- Update existing "Total Expenses" card to show breakdown
- Update Budget Utilization Progress Bar:
  - Show two segments: Approved vs Pending
  - Tooltip on hover showing exact amounts

**Calculation Logic:**
```php
$approvedExpenses = $project->reports
    ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
    ->sum(function($report) {
        return $report->accountDetails->sum('total_expenses') ?? 0;
    });

$unapprovedExpenses = $project->reports
    ->where('status', '!=', DPReport::STATUS_APPROVED_BY_COORDINATOR)
    ->sum(function($report) {
        return $report->accountDetails->sum('total_expenses') ?? 0;
    });
```

### 3. Service Layer Updates

**BudgetValidationService.php:**
- Update `calculateBudgetData()` method to include:
  - `approved_expenses`
  - `unapproved_expenses`
  - `approved_percentage`
  - `unapproved_percentage`

**New Method:**
```php
public static function getApprovedUnapprovedExpenses(Project $project): array
{
    // Calculate approved and unapproved expenses separately
}
```

---

## Technical Implementation Details

### 1. Report Status Check

**Helper Method:**
```php
// In DPReport model or helper
public function isApproved(): bool
{
    return $this->status === self::STATUS_APPROVED_BY_COORDINATOR;
}
```

### 2. Expense Calculation

**For Single Report:**
- Approved: `$report->isApproved() ? $report->accountDetails->sum('total_expenses') : 0`
- Unapproved: `!$report->isApproved() ? $report->accountDetails->sum('total_expenses') : 0`

**For Project (All Reports):**
- Approved: Sum of expenses from approved reports only
- Unapproved: Sum of expenses from unapproved reports only

### 3. UI Components

**New Budget Cards:**
```html
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
```

**Updated Progress Bar:**
```html
<div class="progress" style="height: 25px;">
    <!-- Approved segment -->
    <div class="progress-bar bg-success" 
         style="width: {{ $approvedPercent }}%"
         title="Approved: Rs. {{ number_format($approvedExpenses, 2) }}">
        <strong>{{ number_format($approvedPercent, 1) }}%</strong>
    </div>
    <!-- Unapproved segment -->
    <div class="progress-bar bg-warning" 
         style="width: {{ $unapprovedPercent }}%"
         title="Pending: Rs. {{ number_format($unapprovedExpenses, 2) }}">
        <strong>{{ number_format($unapprovedPercent, 1) }}%</strong>
    </div>
</div>
```

---

## Files to Modify

### Report Views (19 files)
1. `resources/views/reports/monthly/partials/create/statements_of_account.blade.php`
2. `resources/views/reports/monthly/partials/statements_of_account/development_projects.blade.php` (create)
3. `resources/views/reports/monthly/partials/statements_of_account/individual_health.blade.php` (create)
4. `resources/views/reports/monthly/partials/statements_of_account/individual_livelihood.blade.php` (create)
5. `resources/views/reports/monthly/partials/statements_of_account/individual_education.blade.php` (create)
6. `resources/views/reports/monthly/partials/statements_of_account/individual_ongoing_education.blade.php` (create)
7. `resources/views/reports/monthly/partials/statements_of_account/institutional_education.blade.php` (create)
8. `resources/views/reports/monthly/partials/edit/statements_of_account/development_projects.blade.php`
9. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_health.blade.php`
10. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_livelihood.blade.php`
11. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_education.blade.php`
12. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_ongoing_education.blade.php`
13. `resources/views/reports/monthly/partials/edit/statements_of_account/institutional_education.blade.php`
14. `resources/views/reports/monthly/partials/view/statements_of_account/development_projects.blade.php`
15. `resources/views/reports/monthly/partials/view/statements_of_account/individual_health.blade.php`
16. `resources/views/reports/monthly/partials/view/statements_of_account/individual_livelihood.blade.php`
17. `resources/views/reports/monthly/partials/view/statements_of_account/individual_education.blade.php`
18. `resources/views/reports/monthly/partials/view/statements_of_account/individual_ongoing_education.blade.php`
19. `resources/views/reports/monthly/partials/view/statements_of_account/institutional_education.blade.php`

### Project Views (1 file)
1. `resources/views/projects/partials/Show/budget.blade.php`

### Service Layer (1 file)
1. `app/Services/BudgetValidationService.php`

### Models (Optional - Helper Method)
1. `app/Models/Reports/Monthly/DPReport.php` - Add `isApproved()` helper method

---

## Data Flow

### Report View Flow
```
User views report
    ↓
Check report status
    ↓
If approved: approvedExpenses = totalExpenses, unapprovedExpenses = 0
If not approved: approvedExpenses = 0, unapprovedExpenses = totalExpenses
    ↓
Display in budget cards and progress bar
```

### Project View Flow
```
User views project
    ↓
Load all reports for project
    ↓
Filter reports by status
    ↓
Calculate approvedExpenses from approved reports
Calculate unapprovedExpenses from unapproved reports
    ↓
Display in budget cards and progress bar
```

---

## Edge Cases & Considerations

### 1. Report Status Changes
- When coordinator approves a report, expenses automatically become "approved"
- When report is reverted, expenses become "unapproved" again
- No manual intervention needed - status-based calculation

### 2. Multiple Reports
- Project may have multiple reports (monthly reports)
- Some approved, some not
- Sum all approved separately, sum all unapproved separately

### 3. Zero Expenses
- If report has no expenses: show 0.00 for both approved and unapproved
- If project has no reports: show 0.00 for both

### 4. Performance
- Eager load relationships: `$project->load('reports.accountDetails')`
- Cache calculations if needed for frequently accessed projects

### 5. Backward Compatibility
- Existing reports without approval status will be treated as "unapproved"
- No database migration needed (uses existing `status` field)

---

## Benefits

1. **Financial Transparency**: Clear visibility of approved vs pending expenses
2. **Better Decision Making**: Coordinators can see impact of pending approvals
3. **Budget Control**: Understand which expenses are officially sanctioned
4. **Audit Trail**: Visual representation of approval status in budget views
5. **User Experience**: Intuitive color coding (Green = Approved, Yellow = Pending)

---

## Testing Considerations

1. **Test Scenarios:**
   - Report in draft status (all unapproved)
   - Report in approved status (all approved)
   - Project with mix of approved and unapproved reports
   - Project with no reports
   - Project with only approved reports
   - Project with only unapproved reports

2. **Visual Testing:**
   - Progress bar segments display correctly
   - Card colors match approval status
   - Calculations are accurate
   - Responsive design works on mobile

3. **Integration Testing:**
   - Approval workflow updates expenses correctly
   - Revert workflow updates expenses correctly
   - Project view reflects changes after report approval

---

## Next Steps

See `Approved_Unapproved_Expenses_Implementation_Plan.md` for detailed phase-wise implementation plan.
