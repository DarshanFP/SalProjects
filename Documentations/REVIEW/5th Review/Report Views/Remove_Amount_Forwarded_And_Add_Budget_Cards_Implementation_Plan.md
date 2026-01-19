# Remove Amount Forwarded Column & Add Budget Summary Cards
## Phase-Wise Implementation Plan for Monthly Reports

**Date:** January 2025  
**Status:** 📋 Implementation Plan  
**Priority:** High

---

## Executive Summary

This document outlines a comprehensive phase-wise implementation plan to:

1. **Remove "Amount Forwarded from the Previous Year" column** and all related calculations from monthly reports (create, edit, view)
2. **Add Budget Summary Cards** (similar to project views) to monthly report pages

**Scope:**
- All 12 project types (monthly reports)
- Create, Edit, and View modes
- Controllers, Models, Views, JavaScript, PDF exports
- Database schema (optional - can keep column but not use it)

---

## Requirements

### 1. Remove Amount Forwarded Column

**Remove from:**
- Table headers (column "Amount Forwarded from the Previous Year")
- Table rows (input fields `amount_forwarded[]`)
- Overview field (`amount_forwarded_overview`)
- Footer totals (`total_forwarded`)
- All calculations using `amount_forwarded`
- JavaScript functions
- Controllers and models
- PDF exports

**Update Calculations:**
- **Current:** `total_amount = amount_forwarded + amount_sanctioned`
- **New:** `total_amount = amount_sanctioned`

### 2. Add Budget Summary Cards

**Add cards showing:**
- Total Budget (Amount Sanctioned)
- Total Expenses
- Remaining Balance
- % Utilization

**Design:**
- Match project view card design
- Use same CSS classes and styling
- Dark theme compatible
- Responsive layout

---

## Impact Analysis

### Files Affected

**Views - Create Mode (7 files):**
1. `resources/views/reports/monthly/partials/create/statements_of_account.blade.php`
2. `resources/views/reports/monthly/partials/statements_of_account/development_projects.blade.php`
3. `resources/views/reports/monthly/partials/statements_of_account/individual_health.blade.php`
4. `resources/views/reports/monthly/partials/statements_of_account/individual_livelihood.blade.php`
5. `resources/views/reports/monthly/partials/statements_of_account/individual_education.blade.php`
6. `resources/views/reports/monthly/partials/statements_of_account/individual_ongoing_education.blade.php`
7. `resources/views/reports/monthly/partials/statements_of_account/institutional_education.blade.php`

**Views - Edit Mode (7 files):**
1. `resources/views/reports/monthly/partials/edit/statements_of_account.blade.php`
2. `resources/views/reports/monthly/partials/edit/statements_of_account/development_projects.blade.php`
3. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_health.blade.php`
4. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_livelihood.blade.php`
5. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_education.blade.php`
6. `resources/views/reports/monthly/partials/edit/statements_of_account/individual_ongoing_education.blade.php`
7. `resources/views/reports/monthly/partials/edit/statements_of_account/institutional_education.blade.php`

**Views - View Mode (6 files):**
1. `resources/views/reports/monthly/partials/view/statements_of_account/development_projects.blade.php`
2. `resources/views/reports/monthly/partials/view/statements_of_account/individual_health.blade.php`
3. `resources/views/reports/monthly/partials/view/statements_of_account/individual_livelihood.blade.php`
4. `resources/views/reports/monthly/partials/view/statements_of_account/individual_education.blade.php`
5. `resources/views/reports/monthly/partials/view/statements_of_account/individual_ongoing_education.blade.php`
6. `resources/views/reports/monthly/partials/view/statements_of_account/institutional_education.blade.php`

**Controllers (3 files):**
1. `app/Http/Controllers/Reports/Monthly/ReportController.php`
2. `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php`
3. `app/Http/Controllers/Reports/Monthly/ExportReportController.php`

**Models (2 files):**
1. `app/Models/Reports/Monthly/DPReport.php`
2. `app/Models/Reports/Monthly/DPAccountDetail.php`

**PDF Exports (2+ files):**
1. `resources/views/reports/monthly/PDFReport.blade.php`
2. `app/Http/Controllers/Reports/Monthly/ExportReportController.php`

**Other Views (2+ files):**
1. `resources/views/reports/monthly/ReportAll.blade.php` (main create view)
2. `resources/views/reports/monthly/edit.blade.php` (main edit view)
3. `resources/views/reports/monthly/show.blade.php` (main view page)

**Total Files Affected:** ~30+ files

---

## Phase-Wise Implementation Plan

### Phase 1: Remove Amount Forwarded - Overview Field & Table Header
**Duration:** 30 minutes  
**Priority:** High

**Tasks:**
1. Remove `amount_forwarded_overview` input field from all create/edit views
2. Remove "Amount Forwarded from the Previous Year" column header from all table `<thead>`
3. Update column numbering in headers (e.g., "Total Amount (2+3)" becomes "Total Amount (2)")

**Files to Modify:**
- All 7 create mode partials
- All 7 edit mode partials

**Deliverable:** Overview field and table column header removed

---

### Phase 2: Remove Amount Forwarded - Table Rows & Input Fields
**Duration:** 45 minutes  
**Priority:** High

**Tasks:**
1. Remove `<td>` with `amount_forwarded[]` input from all table rows (create mode)
2. Remove `<td>` with `amount_forwarded[]` input from all table rows (edit mode)
3. Remove `amount_forwarded[]` from `addAccountRow()` functions
4. Update row structure to maintain proper column alignment

**Files to Modify:**
- All 7 create mode partials
- All 7 edit mode partials
- All JavaScript `addAccountRow()` functions

**Deliverable:** Table column completely removed from all rows

---

### Phase 3: Update Calculations - JavaScript Functions
**Duration:** 45 minutes  
**Priority:** High

**Tasks:**
1. Update `calculateRowTotals()` function:
   - Remove `amountForwarded` variable
   - Change: `totalAmount = amountForwarded + amountSanctioned`
   - To: `totalAmount = amountSanctioned`
2. Update `calculateTotal()` function:
   - Remove `totalForwarded` variable
   - Remove `total_forwarded` footer cell
   - Update footer structure
3. Remove `amount_forwarded[]` references from all calculation functions

**Files to Modify:**
- All 7 create mode partials (JavaScript sections)
- All 7 edit mode partials (JavaScript sections)

**Deliverable:** All calculations updated to not use `amount_forwarded`

---

### Phase 4: Update Calculations - PHP Controllers
**Duration:** 30 minutes  
**Priority:** High

**Tasks:**
1. Update `ReportController.php`:
   - Remove `amount_forwarded` from validation rules
   - Remove `amount_forwarded_overview` from validation rules
   - Update calculation: `$totalAmount = $amountSanctioned` (remove `$amountForwarded +`)
   - Remove `amount_forwarded` from `$accountDetailData` array (or set to 0)
   - Remove `amount_forwarded_overview` from report data
2. Update `MonthlyDevelopmentProjectController.php`:
   - Remove `amount_forwarded_overview` handling
   - Remove `amountForwarded` variable usage
3. Remove `total_forwarded` footer calculations

**Files to Modify:**
- `app/Http/Controllers/Reports/Monthly/ReportController.php`
- `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php`

**Deliverable:** Controller calculations updated

---

### Phase 5: Update View Mode - Remove Column Display
**Duration:** 30 minutes  
**Priority:** Medium

**Tasks:**
1. Remove "Amount Forwarded" column header from view mode tables
2. Remove `<td>` displaying `amount_forwarded` from view mode tables
3. Remove "Amount Forwarded" from overview section
4. Remove footer `total_forwarded` column
5. Update column alignment

**Files to Modify:**
- All 6 view mode partials

**Deliverable:** View mode tables updated

---

### Phase 6: Update PDF Exports
**Duration:** 30 minutes  
**Priority:** Medium

**Tasks:**
1. Remove "Amount Forwarded" column from PDF table headers
2. Remove `amount_forwarded` data from PDF table rows
3. Remove "Amount Forwarded" from PDF overview section
4. Remove footer `total_forwarded` from PDF tables
5. Update PDF column alignment

**Files to Modify:**
- `resources/views/reports/monthly/PDFReport.blade.php`
- `app/Http/Controllers/Reports/Monthly/ExportReportController.php`

**Deliverable:** PDF exports updated

---

### Phase 7: Add Budget Summary Cards - Create Mode
**Duration:** 1 hour  
**Priority:** High

**Tasks:**
1. Add budget summary cards section above Statements of Account table
2. Cards to display:
   - **Total Budget** (Amount Sanctioned Overview) - Purple card
   - **Total Expenses** (Sum of all expenses) - Green card
   - **Remaining Balance** (Total Budget - Total Expenses) - Blue card
   - **% Utilization** (Total Expenses / Total Budget * 100) - Warning/Danger/Success based on percentage
3. Calculate values dynamically using JavaScript
4. Update cards on form input changes
5. Add CSS styling (match project view cards)
6. Ensure dark theme compatibility

**Files to Modify:**
- `resources/views/reports/monthly/ReportAll.blade.php`
- All 7 create mode partials (add cards section)

**Card Structure:**
```html
<div class="budget-summary-grid mb-3">
    <div class="budget-summary-card budget-card-primary">
        <div class="budget-summary-label">
            <i class="feather icon-dollar-sign"></i> Total Budget
        </div>
        <div class="budget-summary-value" id="card-total-budget">Rs. 0.00</div>
        <div class="budget-summary-note">Amount sanctioned</div>
    </div>
    <!-- More cards... -->
</div>
```

**Deliverable:** Budget summary cards added to create mode

---

### Phase 8: Add Budget Summary Cards - Edit Mode
**Duration:** 45 minutes  
**Priority:** High

**Tasks:**
1. Add budget summary cards section above Statements of Account table
2. Load initial values from existing report data
3. Calculate values dynamically using JavaScript
4. Update cards on form input changes
5. Use same card structure and styling as create mode

**Files to Modify:**
- `resources/views/reports/monthly/edit.blade.php`
- All 7 edit mode partials (add cards section)

**Deliverable:** Budget summary cards added to edit mode

---

### Phase 9: Add Budget Summary Cards - View Mode
**Duration:** 45 minutes  
**Priority:** Medium

**Tasks:**
1. Add budget summary cards section above Statements of Account table
2. Display values from saved report data
3. Calculate values server-side (PHP)
4. Use same card structure and styling
5. Read-only display (no JavaScript calculations needed)

**Files to Modify:**
- `resources/views/reports/monthly/show.blade.php`
- All 6 view mode partials (add cards section)

**Deliverable:** Budget summary cards added to view mode

---

### Phase 10: Add Progress Bar (Optional Enhancement)
**Duration:** 30 minutes  
**Priority:** Low

**Tasks:**
1. Add budget utilization progress bar below cards
2. Show percentage visually
3. Color-coded: Green (<70%), Yellow (70-90%), Red (>90%)
4. Match project view progress bar design

**Files to Modify:**
- All create/edit/view mode partials (optional)

**Deliverable:** Progress bar added (if desired)

---

### Phase 11: Update Dark Theme Styling for Cards
**Duration:** 30 minutes  
**Priority:** Medium

**Tasks:**
1. Ensure card CSS works with dark theme
2. Update card background colors if needed
3. Ensure text contrast is correct
4. Test hover effects in dark theme
5. Match project view card appearance

**Files to Modify:**
- All partials with cards (add/update CSS)

**Deliverable:** Cards styled for dark theme

---

### Phase 12: Testing & Verification
**Duration:** 1 hour  
**Priority:** High

**Tasks:**
1. Test create mode for all 12 project types
2. Test edit mode for all 12 project types
3. Test view mode for all 12 project types
4. Verify calculations are correct:
   - `total_amount = amount_sanctioned` (not including amount_forwarded)
   - `balance_amount = total_amount - total_expenses`
   - Cards display correct values
5. Test PDF exports
6. Verify no JavaScript errors
7. Verify responsive design
8. Cross-browser testing

**Deliverable:** All functionality tested and verified

---

### Phase 13: Cleanup & Documentation
**Duration:** 30 minutes  
**Priority:** Low

**Tasks:**
1. Remove commented code
2. Remove unused functions
3. Update code comments
4. Document changes
5. Create migration plan for database (if removing column)

**Files to Modify:**
- All modified files

**Deliverable:** Code cleaned and documented

---

## Detailed Implementation Steps

### Phase 1: Remove Overview Field & Table Header

#### Step 1.1: Remove Overview Field

**Location:** All create/edit partials  
**Change:**
```blade
{{-- Remove this entire section --}}
<div class="mb-3">
    <label for="amount_forwarded_overview" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
    <input type="number" name="amount_forwarded_overview" class="form-control" value="..." readonly>
</div>
```

**Files:**
- `partials/create/statements_of_account.blade.php`
- `partials/statements_of_account/development_projects.blade.php`
- `partials/statements_of_account/individual_health.blade.php`
- `partials/statements_of_account/individual_livelihood.blade.php`
- `partials/statements_of_account/individual_education.blade.php`
- `partials/statements_of_account/individual_ongoing_education.blade.php`
- `partials/statements_of_account/institutional_education.blade.php`
- `partials/edit/statements_of_account.blade.php`
- `partials/edit/statements_of_account/development_projects.blade.php`
- `partials/edit/statements_of_account/individual_health.blade.php`
- `partials/edit/statements_of_account/individual_livelihood.blade.php`
- `partials/edit/statements_of_account/individual_education.blade.php`
- `partials/edit/statements_of_account/individual_ongoing_education.blade.php`
- `partials/edit/statements_of_account/institutional_education.blade.php`

#### Step 1.2: Remove Table Column Header

**Location:** All create/edit partials - `<thead>` section  
**Change:**
```blade
{{-- Remove this header --}}
<th>Amount Forwarded from the Previous Year</th>

{{-- Update next header numbering --}}
{{-- Current: "Total Amount (2+3)" --}}
{{-- New: "Total Amount (2)" --}}
```

**Current Header Structure:**
```html
<thead>
    <tr>
        <th>No.</th>
        <th>Particulars</th>
        <th>Amount Forwarded from the Previous Year</th>  <!-- REMOVE -->
        <th>Amount Sanctioned Current Year</th>
        <th>Total Amount (2+3)</th>  <!-- UPDATE to (2) -->
        <th>Expenses Up to Last Month</th>
        <th>Expenses of This Month</th>
        <th>Total Expenses (5+6)</th>
        <th>Balance Amount</th>
        <th>Action</th>
    </tr>
</thead>
```

**New Header Structure:**
```html
<thead>
    <tr>
        <th>No.</th>
        <th>Particulars</th>
        <th>Amount Sanctioned Current Year</th>
        <th>Total Amount</th>  <!-- Simplified, or keep as (2) -->
        <th>Expenses Up to Last Month</th>
        <th>Expenses of This Month</th>
        <th>Total Expenses (5+6)</th>
        <th>Balance Amount</th>
        <th>Action</th>
    </tr>
</thead>
```

**Files:** All 14 create/edit partials

---

### Phase 2: Remove Table Rows & Input Fields

#### Step 2.1: Remove Column from Budget Rows (Create Mode)

**Location:** All create mode partials - `@foreach($budgets as $index => $budget)` section  
**Change:**
```blade
{{-- Remove this <td> --}}
<td><input type="number" name="amount_forwarded[]" class="form-control" value="..." readonly></td>

{{-- Update total_amount calculation --}}
{{-- Current: value="{{ old('total_amount.'.$index, ($budget->amount_forwarded ?? 0.00) + ($budget->this_phase ?? 0.00)) }}" --}}
{{-- New: value="{{ old('total_amount.'.$index, $budget->this_phase ?? 0.00) }}" --}}
```

**Files:** All 7 create mode partials

#### Step 2.2: Remove Column from Budget Rows (Edit Mode)

**Location:** All edit mode partials - Edit mode sections  
**Change:**
```blade
{{-- Remove this <td> --}}
<td><input type="number" name="amount_forwarded[]" class="form-control" value="..." readonly></td>

{{-- Update total_amount calculation --}}
{{-- Current: value="{{ old('total_amount.' . $index, $accountDetail->amount_forwarded + $accountDetail->amount_sanctioned) }}" --}}
{{-- New: value="{{ old('total_amount.' . $index, $accountDetail->amount_sanctioned) }}" --}}
```

**Files:** All 7 edit mode partials

#### Step 2.3: Update addAccountRow() Functions

**Location:** All create/edit partials - JavaScript `addAccountRow()` function  
**Change:**
```javascript
{{-- Remove this line from newRow.innerHTML --}}
<td><input type="number" name="amount_forwarded[]" class="form-control" value="0" readonly></td>

{{-- Update total_amount calculation in JavaScript --}}
{{-- Current: totalAmount = amountForwarded + amountSanctioned --}}
{{-- New: totalAmount = amountSanctioned --}}
```

**Files:** All 14 create/edit partials

---

### Phase 3: Update Calculations - JavaScript Functions

#### Step 3.1: Update calculateRowTotals() Function

**Location:** All create/edit partials  
**Change:**
```javascript
{{-- Current --}}
function calculateRowTotals(row) {
    const amountForwarded = parseFloat(row.querySelector('[name="amount_forwarded[]"]').value) || 0;
    const amountSanctioned = parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
    const expensesLastMonth = parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
    const expensesThisMonth = parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;

    const totalAmount = amountForwarded + amountSanctioned;  // CHANGE THIS
    const totalExpenses = expensesLastMonth + expensesThisMonth;
    const balanceAmount = totalAmount - totalExpenses;

    row.querySelector('[name="total_amount[]"]').value = totalAmount.toFixed(2);
    row.querySelector('[name="total_expenses[]"]').value = totalExpenses.toFixed(2);
    row.querySelector('[name="balance_amount[]"]').value = balanceAmount.toFixed(2);

    calculateTotal();
}

{{-- New --}}
function calculateRowTotals(row) {
    const amountSanctioned = parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
    const expensesLastMonth = parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
    const expensesThisMonth = parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;

    const totalAmount = amountSanctioned;  // CHANGED: No longer includes amount_forwarded
    const totalExpenses = expensesLastMonth + expensesThisMonth;
    const balanceAmount = totalAmount - totalExpenses;

    row.querySelector('[name="total_amount[]"]').value = totalAmount.toFixed(2);
    row.querySelector('[name="total_expenses[]"]').value = totalExpenses.toFixed(2);
    row.querySelector('[name="balance_amount[]"]').value = balanceAmount.toFixed(2);

    // Update balance color for this row
    const balanceInput = row.querySelector('[name="balance_amount[]"]');
    if (balanceInput) {
        updateBalanceColor(balanceInput);
    }
    
    calculateTotal();
    updateBudgetSummaryCards();  // NEW: Update cards after calculation
}
```

**Files:** All 14 create/edit partials

#### Step 3.2: Update calculateTotal() Function

**Location:** All create/edit partials  
**Change:**
```javascript
{{-- Current --}}
function calculateTotal() {
    const rows = document.querySelectorAll('#account-rows tr');
    let totalForwarded = 0;  // REMOVE
    let totalSanctioned = 0;
    let totalAmountTotal = 0;
    let totalExpensesLastMonth = 0;
    let totalExpensesThisMonth = 0;
    let totalExpensesTotal = 0;
    let totalBalance = 0;

    rows.forEach(row => {
        totalForwarded += parseFloat(row.querySelector('[name="amount_forwarded[]"]').value) || 0;  // REMOVE
        totalSanctioned += parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
        totalAmountTotal += parseFloat(row.querySelector('[name="total_amount[]"]').value) || 0;
        totalExpensesLastMonth += parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
        totalExpensesThisMonth += parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;
        totalExpensesTotal += parseFloat(row.querySelector('[name="total_expenses[]"]').value) || 0;
        totalBalance += parseFloat(row.querySelector('[name="balance_amount[]"]').value) || 0;
    });

    document.getElementById('total_forwarded').value = totalForwarded.toFixed(2);  // REMOVE
    document.getElementById('total_sanctioned').value = totalSanctioned.toFixed(2);
    document.getElementById('total_amount_total').value = totalAmountTotal.toFixed(2);
    document.getElementById('total_expenses_last_month').value = totalExpensesLastMonth.toFixed(2);
    document.getElementById('total_expenses_this_month').value = totalExpensesThisMonth.toFixed(2);
    document.getElementById('total_expenses_total').value = totalExpensesTotal.toFixed(2);
    document.getElementById('total_balance').value = totalBalance.toFixed(2);

    document.querySelector('[name="total_balance_forwarded"]').value = totalBalance.toFixed(2);
    
    updateAllBalanceColors();
}

{{-- New --}}
function calculateTotal() {
    const rows = document.querySelectorAll('#account-rows tr');
    let totalSanctioned = 0;
    let totalAmountTotal = 0;
    let totalExpensesLastMonth = 0;
    let totalExpensesThisMonth = 0;
    let totalExpensesTotal = 0;
    let totalBalance = 0;

    rows.forEach(row => {
        totalSanctioned += parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
        totalAmountTotal += parseFloat(row.querySelector('[name="total_amount[]"]').value) || 0;
        totalExpensesLastMonth += parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
        totalExpensesThisMonth += parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;
        totalExpensesTotal += parseFloat(row.querySelector('[name="total_expenses[]"]').value) || 0;
        totalBalance += parseFloat(row.querySelector('[name="balance_amount[]"]').value) || 0;
    });

    document.getElementById('total_sanctioned').value = totalSanctioned.toFixed(2);
    document.getElementById('total_amount_total').value = totalAmountTotal.toFixed(2);
    document.getElementById('total_expenses_last_month').value = totalExpensesLastMonth.toFixed(2);
    document.getElementById('total_expenses_this_month').value = totalExpensesThisMonth.toFixed(2);
    document.getElementById('total_expenses_total').value = totalExpensesTotal.toFixed(2);
    document.getElementById('total_balance').value = totalBalance.toFixed(2);

    document.querySelector('[name="total_balance_forwarded"]').value = totalBalance.toFixed(2);
    
    updateAllBalanceColors();
    updateBudgetSummaryCards();  // NEW: Update cards after calculation
}
```

**Files:** All 14 create/edit partials

#### Step 3.3: Remove Footer Column

**Location:** All create/edit partials - `<tfoot>` section  
**Change:**
```blade
{{-- Remove this footer cell --}}
<th><input type="number" id="total_forwarded" class="form-control" readonly></th>

{{-- Update footer structure --}}
<tfoot>
    <tr>
        <th>Total</th>
        <th></th>
        {{-- REMOVE: total_forwarded --}}
        <th><input type="number" id="total_sanctioned" class="form-control" readonly></th>
        <th><input type="number" id="total_amount_total" class="form-control" readonly></th>
        <th><input type="number" id="total_expenses_last_month" class="form-control" readonly></th>
        <th><input type="number" id="total_expenses_this_month" class="form-control" readonly></th>
        <th><input type="number" id="total_expenses_total" class="form-control" readonly></th>
        <th><input type="number" id="total_balance" class="form-control" readonly></th>
        <th></th>
    </tr>
</tfoot>
```

**Files:** All 14 create/edit partials

---

### Phase 4: Update Calculations - PHP Controllers

#### Step 4.1: Update ReportController.php

**Location:** `app/Http/Controllers/Reports/Monthly/ReportController.php`

**Changes:**

1. **Remove from validation rules:**
```php
{{-- Current --}}
'amount_forwarded' => 'nullable|array',
'amount_forwarded.*' => 'nullable|numeric',
'amount_forwarded_overview' => 'nullable|numeric',

{{-- New --}}
// Remove these lines
```

2. **Update calculation in store/update methods:**
```php
{{-- Current --}}
$amountForwarded = (float)($request->input("amount_forwarded.{$index}") ?? 0);
$amountSanctioned = (float)($request->input("amount_sanctioned.{$index}") ?? 0);
$totalAmount = $totalAmountInput !== null ? (float)$totalAmountInput : ($amountForwarded + $amountSanctioned);

{{-- New --}}
$amountSanctioned = (float)($request->input("amount_sanctioned.{$index}") ?? 0);
$totalAmount = $totalAmountInput !== null ? (float)$totalAmountInput : $amountSanctioned;  // CHANGED
```

3. **Update accountDetailData array:**
```php
{{-- Current --}}
$accountDetailData = [
    'report_id' => $report_id,
    'project_id' => $project_id,
    'particulars' => $particular,
    'amount_forwarded' => $amountForwarded,  // REMOVE or set to 0
    'amount_sanctioned' => $amountSanctioned,
    'total_amount' => $totalAmount,
    // ...
];

{{-- New --}}
$accountDetailData = [
    'report_id' => $report_id,
    'project_id' => $project_id,
    'particulars' => $particular,
    'amount_forwarded' => 0,  // Set to 0 (or remove if not in fillable)
    'amount_sanctioned' => $amountSanctioned,
    'total_amount' => $totalAmount,
    // ...
];
```

4. **Remove amount_forwarded_overview:**
```php
{{-- Current --}}
'amount_forwarded_overview' => $validatedData['amount_forwarded_overview'] ?? 0.0,

{{-- New --}}
// Remove this line, or set to 0
'amount_forwarded_overview' => 0.0,  // Or remove from fillable
```

5. **Update create method:**
```php
{{-- Current --}}
$amountForwarded = $project->amount_forwarded ?? 0.00;

{{-- New --}}
// Remove this variable, or keep but don't use
```

**Files:**
- `app/Http/Controllers/Reports/Monthly/ReportController.php`

#### Step 4.2: Update MonthlyDevelopmentProjectController.php

**Location:** `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php`

**Changes:**

1. **Remove from validation:**
```php
{{-- Current --}}
'amount_forwarded_overview' => 'nullable|numeric',

{{-- New --}}
// Remove this line
```

2. **Update create method:**
```php
{{-- Current --}}
$amountForwarded = $project->amount_forwarded ?? 0;

{{-- New --}}
// Remove this variable, or keep but don't use
```

**Files:**
- `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php`

---

### Phase 5: Update View Mode

#### Step 5.1: Remove Column from View Tables

**Location:** All view mode partials  
**Change:**
```blade
{{-- Remove from <thead> --}}
<th>Amount Forwarded</th>

{{-- Remove from <tbody> --}}
<td>Rs. {{ number_format($accountDetail->amount_forwarded, 2) }}</td>

{{-- Remove from footer totals --}}
<td><strong>Rs. {{ number_format($report->accountDetails->sum('amount_forwarded'), 2) }}</strong></td>
```

**Files:** All 6 view mode partials

#### Step 5.2: Remove from Overview Section

**Location:** All view mode partials  
**Change:**
```blade
{{-- Remove this section --}}
<div class="info-label"><strong>Amount Forwarded:</strong></div>
<div class="info-value">Rs. {{ number_format($report->amount_forwarded_overview, 2) }}</div>
```

**Files:** All 6 view mode partials

---

### Phase 6: Update PDF Exports

#### Step 6.1: Update PDFReport.blade.php

**Location:** `resources/views/reports/monthly/PDFReport.blade.php`

**Changes:**
1. Remove "Amount Forwarded" column from PDF table headers
2. Remove `amount_forwarded` data from PDF table rows
3. Remove "Amount Forwarded" from overview section
4. Remove footer `total_forwarded` column

**Files:**
- `resources/views/reports/monthly/PDFReport.blade.php`

#### Step 6.2: Update ExportReportController.php

**Location:** `app/Http/Controllers/Reports/Monthly/ExportReportController.php`

**Changes:**
1. Remove "Amount Forwarded" column from PDF table creation
2. Remove `amount_forwarded` from table data
3. Update column alignment

**Files:**
- `app/Http/Controllers/Reports/Monthly/ExportReportController.php`

---

### Phase 7: Add Budget Summary Cards - Create Mode

#### Step 7.1: Add Cards Section to Main Create View

**Location:** `resources/views/reports/monthly/ReportAll.blade.php`

**Add before Statements of Account section:**
```blade
<!-- Budget Summary Cards Section -->
<div class="mb-4">
    <h5 class="mb-3">Budget Summary</h5>
    <div class="budget-summary-grid mb-3">
        <div class="budget-summary-card budget-card-primary">
            <div class="budget-summary-label">
                <i class="feather icon-dollar-sign"></i> Total Budget
            </div>
            <div class="budget-summary-value" id="card-total-budget">Rs. {{ number_format($amountSanctioned ?? 0, 2) }}</div>
            <div class="budget-summary-note">Amount sanctioned</div>
        </div>
        <div class="budget-summary-card budget-card-success">
            <div class="budget-summary-label">
                <i class="feather icon-check-circle"></i> Total Expenses
            </div>
            <div class="budget-summary-value" id="card-total-expenses">Rs. 0.00</div>
            <div class="budget-summary-note">Amount spent</div>
        </div>
        <div class="budget-summary-card budget-card-info">
            <div class="budget-summary-label">
                <i class="feather icon-wallet"></i> Remaining Balance
            </div>
            <div class="budget-summary-value" id="card-remaining-balance">Rs. {{ number_format($amountSanctioned ?? 0, 2) }}</div>
            <div class="budget-summary-note">Available balance</div>
        </div>
        <div class="budget-summary-card budget-card-success" id="card-utilization">
            <div class="budget-summary-label">
                <i class="feather icon-percent"></i> Utilization
            </div>
            <div class="budget-summary-value" id="card-utilization-value">0.0%</div>
            <div class="budget-summary-note" id="card-utilization-note">100.0% remaining</div>
        </div>
    </div>
    
    <!-- Budget Progress Bar -->
    <div class="budget-progress-section">
        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Budget Utilization</span>
            <span class="text-muted" id="progress-text">0.0% used</span>
        </div>
        <div class="progress" style="height: 25px;">
            <div class="progress-bar bg-success" id="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                <strong>0.0%</strong>
            </div>
        </div>
    </div>
</div>
```

#### Step 7.2: Add JavaScript Function to Update Cards

**Location:** All create mode partials - JavaScript section

**Add function:**
```javascript
/**
 * Update budget summary cards based on current calculations
 */
function updateBudgetSummaryCards() {
    // Get totals from footer
    const totalSanctioned = parseFloat(document.getElementById('total_sanctioned')?.value || 0) || 
                           parseFloat(document.querySelector('[name="amount_sanctioned_overview"]')?.value || 0) || 0;
    const totalExpenses = parseFloat(document.getElementById('total_expenses_total')?.value || 0);
    const totalBalance = parseFloat(document.getElementById('total_balance')?.value || 0);
    
    // Calculate percentage
    const percentageUsed = totalSanctioned > 0 ? (totalExpenses / totalSanctioned) * 100 : 0;
    const percentageRemaining = 100 - percentageUsed;
    
    // Update Total Budget card
    const cardTotalBudget = document.getElementById('card-total-budget');
    if (cardTotalBudget) {
        cardTotalBudget.textContent = `Rs. ${totalSanctioned.toFixed(2)}`;
    }
    
    // Update Total Expenses card
    const cardTotalExpenses = document.getElementById('card-total-expenses');
    if (cardTotalExpenses) {
        cardTotalExpenses.textContent = `Rs. ${totalExpenses.toFixed(2)}`;
    }
    
    // Update Remaining Balance card
    const cardRemainingBalance = document.getElementById('card-remaining-balance');
    if (cardRemainingBalance) {
        cardRemainingBalance.textContent = `Rs. ${totalBalance.toFixed(2)}`;
    }
    
    // Update Utilization card
    const cardUtilization = document.getElementById('card-utilization');
    const cardUtilizationValue = document.getElementById('card-utilization-value');
    const cardUtilizationNote = document.getElementById('card-utilization-note');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    
    if (cardUtilizationValue) {
        cardUtilizationValue.textContent = `${percentageUsed.toFixed(1)}%`;
    }
    if (cardUtilizationNote) {
        cardUtilizationNote.textContent = `${percentageRemaining.toFixed(1)}% remaining`;
    }
    
    // Update card color based on percentage
    if (cardUtilization) {
        cardUtilization.className = 'budget-summary-card ';
        if (percentageUsed > 90) {
            cardUtilization.classList.add('budget-card-danger');
        } else if (percentageUsed > 70) {
            cardUtilization.classList.add('budget-card-warning');
        } else {
            cardUtilization.classList.add('budget-card-success');
        }
    }
    
    // Update progress bar
    if (progressBar) {
        progressBar.style.width = `${percentageUsed}%`;
        progressBar.setAttribute('aria-valuenow', percentageUsed);
        progressBar.innerHTML = `<strong>${percentageUsed.toFixed(1)}%</strong>`;
        
        // Update progress bar color
        progressBar.className = 'progress-bar ';
        if (percentageUsed > 90) {
            progressBar.classList.add('bg-danger');
        } else if (percentageUsed > 70) {
            progressBar.classList.add('bg-warning');
        } else {
            progressBar.classList.add('bg-success');
        }
    }
    
    if (progressText) {
        progressText.textContent = `${percentageUsed.toFixed(1)}% used`;
    }
}

// Call on page load and after calculations
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...
    updateBudgetSummaryCards();
    
    // Also update when overview amount changes
    const overviewAmount = document.querySelector('[name="amount_sanctioned_overview"]');
    if (overviewAmount) {
        overviewAmount.addEventListener('input', function() {
            updateBudgetSummaryCards();
        });
    }
});

// Call after calculateTotal()
function calculateTotal() {
    // ... existing calculation code ...
    updateAllBalanceColors();
    updateBudgetSummaryCards();  // ADD THIS
}
```

**Files:** All 7 create mode partials

#### Step 7.3: Add CSS Styling

**Location:** All create mode partials - `<style>` section

**Add CSS:**
```css
/* Budget Summary Cards Styling - Dark Theme */
.budget-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.budget-summary-card {
    background-color: #132f6b;
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 8px;
    padding: 16px 18px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.budget-summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(101, 113, 255, 0.25);
}

.budget-card-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.budget-card-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.budget-card-info {
    background: linear-gradient(135deg, #3494e6 0%, #2980b9 100%);
}

.budget-card-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.budget-card-danger {
    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
}

.budget-summary-label {
    font-size: 0.875rem;
    opacity: 0.95;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.budget-summary-label i {
    font-size: 1rem;
}

.budget-summary-value {
    font-weight: 700;
    font-size: 1.5rem;
    letter-spacing: 0.3px;
    margin-bottom: 4px;
}

.budget-summary-note {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-top: 4px;
}

.budget-progress-section {
    margin-top: 20px;
    padding: 15px;
    background-color: #0c1427;
    border: 1px solid #172340;
    border-radius: 8px;
}

.budget-progress-section .progress {
    border-radius: 10px;
    overflow: hidden;
    background-color: #172340;
}

.budget-progress-section .progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
}

.budget-progress-section .text-muted {
    color: #7987a1 !important;
}
```

**Files:** All 7 create mode partials

---

### Phase 8: Add Budget Summary Cards - Edit Mode

**Similar to Phase 7, but:**
- Load initial values from `$report` data
- Calculate from existing account details
- Update dynamically when form changes

**Files:** All 7 edit mode partials + `edit.blade.php`

---

### Phase 9: Add Budget Summary Cards - View Mode

**Similar to Phase 7, but:**
- Calculate server-side (PHP)
- Display read-only values
- No JavaScript calculations needed

**Files:** All 6 view mode partials + `show.blade.php`

---

### Phase 10: Add Progress Bar (Optional)

**Add after cards:**
```blade
<div class="budget-progress-section">
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted">Budget Utilization</span>
        <span class="text-muted">{{ number_format($percentageUsed, 1) }}% used</span>
    </div>
    <div class="progress" style="height: 25px;">
        <div class="progress-bar {{ $percentageUsed > 90 ? 'bg-danger' : ($percentageUsed > 70 ? 'bg-warning' : 'bg-success') }}"
             role="progressbar"
             style="width: {{ $percentageUsed }}%"
             aria-valuenow="{{ $percentageUsed }}"
             aria-valuemin="0"
             aria-valuemax="100">
            <strong>{{ number_format($percentageUsed, 1) }}%</strong>
        </div>
    </div>
</div>
```

---

## Testing Checklist

### Phase 12: Testing & Verification

#### Create Mode Testing
- [ ] All 12 project types load correctly
- [ ] Budget summary cards display correct values
- [ ] Cards update when expenses are entered
- [ ] Table calculations work correctly (total_amount = amount_sanctioned)
- [ ] No "Amount Forwarded" column in table
- [ ] No JavaScript errors in console
- [ ] Form submission works correctly

#### Edit Mode Testing
- [ ] All 12 project types load existing data correctly
- [ ] Budget summary cards show correct values from saved report
- [ ] Cards update when expenses are modified
- [ ] Table calculations work correctly
- [ ] No "Amount Forwarded" column in table
- [ ] Form update works correctly

#### View Mode Testing
- [ ] All 12 project types display correctly
- [ ] Budget summary cards show correct values
- [ ] Table displays correctly without "Amount Forwarded" column
- [ ] All calculations are correct

#### Cross-Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

#### PDF Export Testing
- [ ] PDF generates correctly
- [ ] No "Amount Forwarded" column in PDF
- [ ] Calculations are correct in PDF

---

## Database Considerations

### Option 1: Keep Column, Set to 0 (Recommended)
- Keep `amount_forwarded` column in database
- Always set value to 0 when saving
- No migration needed
- Backward compatible

### Option 2: Remove Column (Future)
- Create migration to drop column
- Requires careful data migration planning
- More complex, but cleaner long-term

**Recommendation:** Option 1 (keep column, set to 0) for now

---

## Risk Assessment

### Low Risk
- ✅ Removing unused column (always 0.00)
- ✅ Adding cards (visual enhancement only)

### Medium Risk
- ⚠️ Calculation changes (need thorough testing)
- ⚠️ Form submission changes (need validation)

### High Risk
- ❌ None identified

---

## Rollback Plan

If issues arise:
1. Revert all view changes
2. Revert controller changes
3. Restore calculation logic
4. Re-add column if needed

**Backup:** Git version control will allow easy rollback

---

## Success Criteria

### Removal Complete
- ✅ No "Amount Forwarded" column in any view
- ✅ All calculations use `total_amount = amount_sanctioned`
- ✅ No references to `amount_forwarded` in calculations
- ✅ PDF exports updated
- ✅ No JavaScript errors

### Cards Added
- ✅ Budget summary cards visible in create mode
- ✅ Budget summary cards visible in edit mode
- ✅ Budget summary cards visible in view mode
- ✅ Cards update dynamically
- ✅ Cards match project view design
- ✅ Dark theme compatible

---

## Timeline Estimate

| Phase | Duration | Priority |
|-------|----------|----------|
| Phase 1 | 30 min | High |
| Phase 2 | 45 min | High |
| Phase 3 | 45 min | High |
| Phase 4 | 30 min | High |
| Phase 5 | 30 min | Medium |
| Phase 6 | 30 min | Medium |
| Phase 7 | 1 hour | High |
| Phase 8 | 45 min | High |
| Phase 9 | 45 min | Medium |
| Phase 10 | 30 min | Low |
| Phase 11 | 30 min | Medium |
| Phase 12 | 1 hour | High |
| Phase 13 | 30 min | Low |
| **Total** | **~7.5 hours** | |

---

## Next Steps

1. **Review this plan** - Confirm approach and scope
2. **Start Phase 1** - Remove overview field and table header
3. **Proceed sequentially** - Complete phases in order
4. **Test after each phase** - Verify changes work
5. **Document issues** - Track any problems found

---

**Document Version:** 1.0  
**Created:** January 2025  
**Status:** Ready for Implementation

---

**End of Implementation Plan**
