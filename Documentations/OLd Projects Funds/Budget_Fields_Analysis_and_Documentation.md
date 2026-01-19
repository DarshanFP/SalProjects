# Budget Fields Analysis and Documentation

## Overview

This document provides a comprehensive analysis of the budget-related fields in the projects table, their usage across the system, calculations, and recommendations for improvement.

---

## Table of Contents

1. [Budget Fields in Projects Table](#budget-fields-in-projects-table)
2. [Project Status Workflow](#project-status-workflow)
3. [Current Implementation Analysis](#current-implementation-analysis)
4. [Where Budget Fields Are Used](#where-budget-fields-are-used)
5. [Calculations and Logic](#calculations-and-logic)
6. [Issues Identified](#issues-identified)
7. [Recommendations for Improvement](#recommendations-for-improvement)

---

## Budget Fields in Projects Table

The `projects` table contains four budget-related fields:

### 1. **Overall Project Budget** (`overall_project_budget`)

-   **Type**: `decimal(10, 2)`
-   **Default**: `0.00`
-   **Purpose**: Represents the total budget allocated for the entire project across all phases
-   **Calculation**: Sum of all phase budgets (this_phase) + next phase amounts
-   **When Set**: During project creation/editing via JavaScript calculations

### 2. **Amount Forwarded** (`amount_forwarded`)

-   **Type**: `decimal(10, 2)`
-   **Nullable**: Yes
-   **Purpose**: Represents the amount already available with the organization (existing funds) before requesting sanction
-   **Current Status**: ⚠️ **NOT PROPERLY IMPLEMENTED** - Field exists in database but calculations and user input are missing
-   **Expected Logic**:
    -   **All Projects (Single & Multi-Phase)**: Manually entered by executor/applicant in create/edit forms if they already have some funds available
    -   **Note**: Multi-phase phase-to-phase auto-calculation is a future enhancement (existing commented code will remain commented)
-   **Business Rule**:
    -   Users should only request sanction for the difference: **`Amount Sanctioned = Overall Project Budget - Amount Forwarded`**
    -   Field must be editable in project create/edit forms for executor and applicant users
    -   Default value: `0.00` if no existing funds available

### 3. **Amount Sanctioned** (`amount_sanctioned`)

-   **Type**: `decimal(10, 2)`
-   **Nullable**: Yes
-   **Purpose**: Represents the total amount officially approved/sanctioned by the coordinator
-   **When Set**: Automatically calculated and saved when coordinator approves the project
-   **Calculation Formula**: **`Amount Sanctioned = Overall Project Budget - Amount Forwarded`**
-   **Calculation Priority** (in `CoordinatorController::approveProject`):
    1. First: Uses `overall_project_budget - amount_forwarded` if both are available
    2. Second: Uses `overall_project_budget` if > 0 (when amount_forwarded is NULL or 0)
    3. Third: Uses existing `amount_sanctioned` if > 0
    4. Fourth: Calculates from `budgets` relationship (sum of `this_phase`)
-   **Validation**: Must ensure `amount_sanctioned` cannot exceed `overall_project_budget`

### 4. **Opening Balance** (`opening_balance`)

-   **Type**: `decimal(10, 2)`
-   **Nullable**: Yes
-   **Purpose**: Represents the starting balance available for the project after sanction
-   **Calculation Formula**: **`Opening Balance = Amount Sanctioned + Amount Forwarded`**
-   **When Calculated**:
    -   Automatically calculated when project is approved by coordinator
    -   Should also be recalculated when amount_forwarded changes in edit form
-   **Current Status**: ⚠️ **NOT PROPERLY CALCULATED** - Field exists but calculations are incomplete

---

## Project Status Workflow

The system follows a multi-stage approval workflow:

```
┌─────────┐
│  DRAFT  │ ← Executor creates project
└────┬────┘
     │
     │ Submit
     ▼
┌───────────────────────────┐
│ SUBMITTED_TO_PROVINCIAL   │ ← Executor submits to Provincial
└────┬──────────────────────┘
     │
     │ Forward / Revert
     ▼
┌──────────────────────────────┐
│ FORWARDED_TO_COORDINATOR     │ ← Provincial forwards to Coordinator
└────┬─────────────────────────┘
     │
     │ Approve / Revert / Reject
     ▼
┌───────────────────────────┐
│ APPROVED_BY_COORDINATOR   │ ← Coordinator approves
└───────────────────────────┘
```

### Status Constants

Defined in `app/Constants/ProjectStatus.php`:

-   `DRAFT` - Executor still working on project
-   `REVERTED_BY_PROVINCIAL` - Returned by Provincial for changes
-   `REVERTED_BY_COORDINATOR` - Returned by Coordinator for changes
-   `SUBMITTED_TO_PROVINCIAL` - Executor submitted to Provincial
-   `FORWARDED_TO_COORDINATOR` - Provincial sent to Coordinator
-   `APPROVED_BY_COORDINATOR` - Approved by Coordinator
-   `REJECTED_BY_COORDINATOR` - Rejected by Coordinator

### Key Status Transitions

1. **Draft → Submitted**: Executor submits project
2. **Submitted → Forwarded**: Provincial approves and forwards to Coordinator
3. **Forwarded → Approved**: Coordinator approves project (at this point, `amount_sanctioned` is set)
4. **Any → Reverted**: Can be reverted at any stage, allowing edits

---

## Current Implementation Analysis

### 1. Budget Storage Structure

**Model**: `app/Models/OldProjects/Project.php`

The Project model has:

-   Direct fields: `overall_project_budget`, `amount_forwarded`, `amount_sanctioned`, `opening_balance`
-   Relationship: `budgets()` - HasMany relationship to `ProjectBudget` model

**Related Model**: `app/Models/OldProjects/ProjectBudget.php`

Each budget entry contains:

-   `phase` - Phase number
-   `particular` - Budget item description
-   `this_phase` - Amount for current phase
-   `next_phase` - Amount allocated for next phase
-   `rate_quantity`, `rate_multiplier`, `rate_duration` - Calculation components

### 2. Budget Creation Process

**Location**: Project creation/editing forms

**JavaScript Calculation** (in `resources/views/projects/partials/scripts.blade.php`):

```javascript
function calculateTotalAmountSanctioned() {
    // For single-phase projects (current implementation)
    const budgetRows = document.querySelectorAll(".budget-rows tr");
    let totalAmount = 0;

    budgetRows.forEach((row) => {
        const thisPhaseValue =
            parseFloat(row.querySelector('[name$="[this_phase]"]').value) || 0;
        totalAmount += thisPhaseValue;
    });

    // Sets overall_project_budget
    document.getElementById("overall_project_budget").value =
        totalAmount.toFixed(2);

    // Sets total_amount_forwarded to 0 (for single phase)
    document.querySelector('[name="total_amount_forwarded"]').value = "0.00";
}
```

**Issue**: The system currently only handles single-phase projects properly. Multi-phase logic exists in commented code (`CreateProjedctQuery.DOC`) but is not active.

### 3. Budget Approval Process

**Location**: `app/Http/Controllers/CoordinatorController.php::approveProject()`

```php
public function approveProject($project_id)
{
    $project = Project::where('project_id', $project_id)->with('budgets')->firstOrFail();

    // Update status via ProjectStatusService
    ProjectStatusService::approve($project, $coordinator);

    // Calculate amount_sanctioned
    $totalAmountSanctioned = 0;

    if ($project->overall_project_budget && $project->overall_project_budget > 0) {
        $totalAmountSanctioned = $project->overall_project_budget;
    } elseif ($project->amount_sanctioned && $project->amount_sanctioned > 0) {
        $totalAmountSanctioned = $project->amount_sanctioned;
    } elseif ($project->budgets && $project->budgets->count() > 0) {
        $totalAmountSanctioned = $project->budgets->sum('this_phase');
    }

    $project->amount_sanctioned = $totalAmountSanctioned;
    $project->save();
}
```

**Note**: `amount_forwarded` and `opening_balance` are **NOT** calculated during approval.

---

## Where Budget Fields Are Used

### 1. **Project Creation/Editing**

**Files**:

-   `resources/views/projects/partials/scripts.blade.php`
-   `resources/views/projects/partials/budget.blade.php`
-   `app/Http/Controllers/Projects/ProjectController.php`

**What Happens** (Current):

-   User enters budget details (particulars, rates, quantities)
-   JavaScript calculates `this_phase` for each budget item
-   `overall_project_budget` is calculated as sum of all `this_phase` values
-   `amount_forwarded` is hardcoded to `0.00` (missing user input field)

**What Should Happen** (Required):

-   User enters budget details (particulars, rates, quantities)
-   JavaScript calculates `this_phase` for each budget item
-   `overall_project_budget` is calculated as sum of all `this_phase` values
-   **User can enter `amount_forwarded` (existing funds available)**
-   **JavaScript calculates `amount_sanctioned = overall_project_budget - amount_forwarded`**
-   **JavaScript calculates `opening_balance = amount_sanctioned + amount_forwarded`**
-   All values saved to database

### 2. **Project Display**

**Files**:

-   `resources/views/projects/partials/Show/general_info.blade.php` (lines 87-101)

**What's Displayed**:

```blade
<tr>
    <td class="label">Overall Project Budget:</td>
    <td class="value">Rs. {{ number_format($project->overall_project_budget, 2) }}</td>
</tr>
<tr>
    <td class="label">Amount Forwarded:</td>
    <td class="value">Rs. {{ number_format($project->amount_forwarded, 2) }}</td>
</tr>
<tr>
    <td class="label">Amount Sanctioned:</td>
    <td class="value">Rs. {{ number_format($project->amount_sanctioned, 2) }}</td>
</tr>
<tr>
    <td class="label">Opening Balance:</td>
    <td class="value">Rs. {{ number_format($project->opening_balance, 2) }}</td>
</tr>
```

**Issue**: All fields are displayed, but `amount_forwarded` and `opening_balance` are typically NULL or 0.

### 3. **Monthly Reports - Statements of Account**

**Files**:

-   `resources/views/reports/monthly/partials/statements_of_account/development_projects.blade.php`
-   `app/Http/Controllers/Reports/Monthly/ReportController.php`

**What Happens**:

-   Reports show budget rows with `amount_forwarded` and `amount_sanctioned` columns
-   Users can input `amount_forwarded` manually in report forms
-   Formula: `total_amount = amount_forwarded + amount_sanctioned`

**Key Code**:

```blade
<td><input type="number" name="amount_forwarded[]" class="form-control"
    value="{{ old('amount_forwarded.'.$index, 0.00) }}"
    oninput="calculateRowTotals(this.closest('tr'))"
    style="background-color: #6571ff;"></td>
<td><input type="number" name="amount_sanctioned[]" class="form-control"
    value="{{ old('amount_sanctioned.'.$index, $budget->this_phase ?? 0.00) }}"
    readonly></td>
<td><input type="number" name="total_amount[]" class="form-control"
    value="{{ old('total_amount.'.$index, ($budget->amount_forwarded ?? 0.00) + ($budget->this_phase ?? 0.00)) }}"
    readonly></td>
```

**Note**: In reports, `amount_forwarded` is user-editable but not automatically calculated from previous reports.

### 4. **Dashboard Calculations**

**Files**:

-   `app/Http/Controllers/CoordinatorController.php::calculateBudgetSummariesFromProjects()`
-   `app/Http/Controllers/ProvincialController.php::calculateBudgetSummariesFromProjects()`

**What Happens**:

-   Budget summaries use `amount_sanctioned` or `overall_project_budget` as the base budget
-   `amount_forwarded` is read but not used in calculations
-   Expenses are calculated from reports
-   Remaining budget = Budget - Expenses

**Code Example** (CoordinatorController):

```php
$projectBudget = 0;
if ($project->overall_project_budget && $project->overall_project_budget > 0) {
    $projectBudget = $project->overall_project_budget;
} elseif ($project->amount_sanctioned && $project->amount_sanctioned > 0) {
    $projectBudget = $project->amount_sanctioned;
} elseif ($project->budgets && $project->budgets->count() > 0) {
    $projectBudget = $project->budgets->sum('this_phase');
}
```

---

## Calculations and Logic

### Current Calculation Flow

#### 1. **During Project Creation/Edit** (Required Implementation)

```
Budget Rows (User Input)
    ↓
Calculate: this_phase = rate_quantity × rate_multiplier × rate_duration
    ↓
Calculate: overall_project_budget = SUM(all this_phase values)
    ↓
User Input: amount_forwarded (existing funds available, defaults to 0.00)
    ↓
Calculate: amount_sanctioned = overall_project_budget - amount_forwarded
    ↓
Calculate: opening_balance = amount_sanctioned + amount_forwarded
    ↓
Save to Database: overall_project_budget, amount_forwarded, opening_balance
    ↓
Note: amount_sanctioned will be set by coordinator during approval
```

**Form Fields Required**:

-   ✅ `overall_project_budget` - Read-only (auto-calculated)
-   ✅ `amount_forwarded` - **Editable** input field for executor/applicant
-   ✅ `amount_sanctioned` - Read-only (will show calculated value, but set by coordinator on approval)
-   ✅ `opening_balance` - Read-only (auto-calculated)

#### 2. **During Project Approval** (Required Implementation)

```
Project Status = FORWARDED_TO_COORDINATOR
    ↓
Coordinator Clicks "Approve"
    ↓
ProjectStatusService::approve() → Sets status = APPROVED_BY_COORDINATOR
    ↓
Get: overall_project_budget (already set during creation)
Get: amount_forwarded (already set by executor/applicant during creation/edit)
    ↓
Calculate amount_sanctioned:
    IF overall_project_budget > 0 AND amount_forwarded is set:
        amount_sanctioned = overall_project_budget - amount_forwarded
    ELSE IF overall_project_budget > 0:
        amount_sanctioned = overall_project_budget (amount_forwarded = 0)
    ELSE IF existing amount_sanctioned > 0:
        amount_sanctioned = existing value
    ELSE:
        amount_sanctioned = SUM(budgets.this_phase) - (amount_forwarded ?? 0)
    ↓
Calculate opening_balance:
    opening_balance = amount_sanctioned + (amount_forwarded ?? 0)
    ↓
Save to database: amount_sanctioned, opening_balance
    ↓
Validation: Ensure amount_sanctioned >= 0 and amount_sanctioned <= overall_project_budget
```

#### 3. **During Report Creation**

```
Load Project Budgets
    ↓
For each budget item:
    amount_sanctioned = budget.this_phase (read-only, from project)
    amount_forwarded = user input (editable, defaults to 0.00)
    total_amount = amount_forwarded + amount_sanctioned
    ↓
User enters expenses_this_month
    ↓
Calculate:
    total_expenses = expenses_last_month + expenses_this_month
    balance_amount = total_amount - total_expenses
```

### Missing Calculations

1. **Amount Forwarded Input Field (Project Level)**

    - **Required**: Editable input field in create/edit project forms for executor and applicant users
    - **Purpose**: Allow users to specify existing funds they already have
    - **Currently**: No input field exists - hardcoded to 0.00
    - **Future (Multi-Phase)**: Can be manually entered OR auto-calculated from unspent balances of previous phases

2. **Amount Sanctioned Calculation**

    - **Required Formula**: `amount_sanctioned = overall_project_budget - amount_forwarded`
    - **Currently**: Uses `overall_project_budget` directly, ignoring `amount_forwarded`
    - **When**: Should be calculated during approval by coordinator

3. **Opening Balance Calculation (Project Level)**

    - **Required Formula**: `opening_balance = amount_sanctioned + amount_forwarded`
    - **When**:
        - Calculated in JavaScript during project creation/edit (for preview)
        - Recalculated and saved during project approval
    - **Currently**: Always NULL or 0

4. **Multi-Phase Calculations** (Future Enhancement - Keep Commented)
    - JavaScript code exists but is **intentionally kept commented**
    - Future enhancement: auto-calculate forwarded amounts between phases
    - Future enhancement: track opening balance per phase
    - **Current approach**: Manual entry of `amount_forwarded` works for all project types

---

## Issues Identified

### Critical Issues

1. **⚠️ Amount Forwarded Input Field Missing**

    - Field exists in database but **no input field in create/edit project forms**
    - Currently hardcoded to `0.00` in JavaScript
    - Executors/applicants cannot specify existing funds they have
    - Breaks the requirement: `Amount Sanctioned = Overall Budget - Amount Forwarded`

2. **⚠️ Opening Balance Never Calculated**

    - Field exists but remains NULL
    - Should be: `amount_forwarded + amount_sanctioned`
    - Affects budget visibility and reporting

3. **⚠️ Multi-Phase Support Incomplete**
    - System designed for multi-phase but only single-phase works
    - JavaScript for multi-phase calculations exists but disabled
    - No phase-to-phase amount forwarding logic

### Medium Issues

4. **Inconsistent Budget Source**

    - Different controllers use different priority logic
    - Some use `overall_project_budget`, others use `amount_sanctioned`
    - Can lead to inconsistencies in reports

5. **No Validation**

    - No validation that `amount_sanctioned` <= `overall_project_budget`
    - No validation of budget totals against individual items

6. **Manual Entry in Reports**
    - `amount_forwarded` in reports is manually entered
    - Should be automatically calculated from previous report's balance
    - Prone to errors and inconsistencies

### Minor Issues

7. **Display Issues**

    - Fields display NULL as "Rs. 0.00" which may confuse users
    - No indication when fields are not set vs. actually zero

8. **Documentation**
    - No comments explaining the intended use of `amount_forwarded`
    - Business logic for multi-phase projects not documented

---

## Recommendations for Improvement

### Priority 1: Add Amount Forwarded Input Field and Calculations

**For All Projects (Single & Multi-Phase)**:

1. **Add Amount Forwarded Input Field in Project Forms**:

    **Location**: `resources/views/projects/partials/budget.blade.php` or create form

    ```blade
    <div class="mb-3">
        <label for="amount_forwarded" class="form-label">
            Amount Forwarded (Existing Funds Available)
            <small class="text-muted">(Optional - Enter if you already have some funds)</small>
        </label>
        <input type="number"
               step="0.01"
               min="0"
               id="amount_forwarded"
               name="amount_forwarded"
               class="form-control"
               value="{{ old('amount_forwarded', $project->amount_forwarded ?? 0.00) }}"
               oninput="calculateBudgetFields()">
        <div class="form-text">
            Enter the amount you already have available. The sanctioned amount will be calculated as:
            Overall Budget - Amount Forwarded
        </div>
    </div>
    ```

2. **Add JavaScript Calculations**:

    **Location**: `resources/views/projects/partials/scripts.blade.php`

    ```javascript
    function calculateBudgetFields() {
        // Get values
        const overallBudget =
            parseFloat(
                document.getElementById("overall_project_budget").value
            ) || 0;
        const amountForwarded =
            parseFloat(document.getElementById("amount_forwarded").value) || 0;

        // Validate
        if (amountForwarded > overallBudget) {
            alert("Amount Forwarded cannot exceed Overall Project Budget");
            document.getElementById("amount_forwarded").value =
                overallBudget.toFixed(2);
            amountForwarded = overallBudget;
        }

        // Calculate Amount Sanctioned
        const amountSanctioned = overallBudget - amountForwarded;
        const amountSanctionedField =
            document.getElementById("amount_sanctioned");
        if (amountSanctionedField) {
            amountSanctionedField.value = amountSanctioned.toFixed(2);
        }

        // Calculate Opening Balance
        const openingBalance = amountSanctioned + amountForwarded;
        const openingBalanceField = document.getElementById("opening_balance");
        if (openingBalanceField) {
            openingBalanceField.value = openingBalance.toFixed(2);
        }

        // Update display
        updateBudgetSummary(
            overallBudget,
            amountForwarded,
            amountSanctioned,
            openingBalance
        );
    }

    // Call when overall budget changes
    document.addEventListener("DOMContentLoaded", function () {
        const overallBudgetField = document.getElementById(
            "overall_project_budget"
        );
        const amountForwardedField =
            document.getElementById("amount_forwarded");

        if (overallBudgetField) {
            overallBudgetField.addEventListener("input", calculateBudgetFields);
        }
        if (amountForwardedField) {
            amountForwardedField.addEventListener(
                "input",
                calculateBudgetFields
            );
        }

        // Initial calculation
        calculateBudgetFields();
    });
    ```

3. **Update Approval Logic**:

    **Location**: `app/Http/Controllers/CoordinatorController.php::approveProject()`

    ```php
    // Calculate amount_sanctioned
    $overallBudget = $project->overall_project_budget ?? 0;
    $amountForwarded = $project->amount_forwarded ?? 0;

    // Validate amount_forwarded
    if ($amountForwarded > $overallBudget) {
        return redirect()->back()
            ->with('error', 'Amount Forwarded cannot exceed Overall Project Budget');
    }

    // Calculate amount_sanctioned
    $amountSanctioned = $overallBudget - $amountForwarded;

    // Ensure non-negative
    if ($amountSanctioned < 0) {
        $amountSanctioned = 0;
    }

    // Calculate opening_balance
    $openingBalance = $amountSanctioned + $amountForwarded;

    // Update project
    $project->amount_sanctioned = $amountSanctioned;
    $project->opening_balance = $openingBalance;
    $project->save();
    ```

4. **Add Read-Only Display Fields in Form**:

    ```blade
    <div class="mb-3">
        <label class="form-label">Amount Sanctioned (Calculated)</label>
        <input type="number"
               id="amount_sanctioned"
               name="amount_sanctioned"
               class="form-control"
               readonly
               value="{{ old('amount_sanctioned', $project->amount_sanctioned ?? 0.00) }}">
        <div class="form-text">
            This will be confirmed by coordinator during approval
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Opening Balance (Calculated)</label>
        <input type="number"
               id="opening_balance"
               name="opening_balance"
               class="form-control"
               readonly
               value="{{ old('opening_balance', $project->opening_balance ?? 0.00) }}">
        <div class="form-text">
            Opening Balance = Amount Sanctioned + Amount Forwarded
        </div>
    </div>
    ```

### Priority 2: Auto-Calculate Amount Forwarded in Reports

**For Monthly Reports**:

1. **Load Previous Report's Balance**:

    ```php
    $previousReport = DPReport::where('project_id', $project->project_id)
        ->where('report_month', '<', $currentMonth)
        ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
        ->latest('report_month')
        ->first();

    $lastExpenses = [];
    if ($previousReport) {
        foreach ($previousReport->accountDetails as $detail) {
            $lastExpenses[$detail->particulars] = $detail->balance_amount;
        }
    }
    ```

2. **Set as Default in Form**:
    ```blade
    <input type="number" name="amount_forwarded[]"
        value="{{ old('amount_forwarded.'.$index, $lastExpenses[$budget->particular] ?? 0.00) }}"
        oninput="calculateRowTotals(this.closest('tr'))">
    ```

### Priority 3: Add Validation

1. **Validate Amount Sanctioned**:

    ```php
    // In CoordinatorController::approveProject()
    if ($project->overall_project_budget > 0) {
        if ($totalAmountSanctioned > $project->overall_project_budget) {
            return redirect()->back()
                ->with('error', 'Sanctioned amount cannot exceed overall budget');
        }
    }
    ```

2. **Validate Budget Totals**:
    ```php
    // In ProjectController::store()
    $calculatedTotal = $budgets->sum('this_phase');
    if (abs($calculatedTotal - $request->overall_project_budget) > 0.01) {
        return redirect()->back()
            ->with('error', 'Budget totals do not match');
    }
    ```

### Priority 4: Multi-Phase Support (Future - Keep Commented Code)

**Decision**: Keep existing multi-phase JavaScript code commented. The manual `amount_forwarded` input field will work for all project types (single and multi-phase).

1. **Current State**:

    - Multi-phase JavaScript exists in `resources/views/projects/CreateProjedctQuery.DOC` (commented)
    - This code will remain commented for now
    - Manual entry of `amount_forwarded` covers all use cases

2. **Future Enhancement** (When Needed):
    ```php
    // Service class for phase management (future implementation)
    class PhaseBudgetService {
        public static function advanceToNextPhase(Project $project) {
            // Calculate amount_forwarded from previous phase
            // Update opening_balance
            // Set new current_phase
        }
    }
    ```
3. **Why Keep Commented**:
    - Current manual approach is simpler and works for all scenarios
    - Auto-calculation adds complexity
    - Can be revisited when specific multi-phase requirements arise

### Priority 5: Improve Display and UX

1. **Show Calculation Breakdown**:

    ```blade
    @if($project->amount_forwarded > 0)
        <small class="text-muted">
            (From Phase {{ $project->current_phase - 1 }})
        </small>
    @endif
    ```

2. **Add Help Text**:
    ```blade
    <div class="form-text">
        Opening Balance = Amount Forwarded + Amount Sanctioned
    </div>
    ```

### Priority 6: Database Migration Considerations

**If implementing multi-phase fully**, consider:

1. **Add Phase to Projects Table** (if not tracking properly):

    ```php
    Schema::table('projects', function (Blueprint $table) {
        $table->integer('current_phase')->default(1)->after('overall_project_period');
    });
    ```

2. **Add Indexes for Performance**:
    ```php
    Schema::table('projects', function (Blueprint $table) {
        $table->index(['status', 'current_phase']);
    });
    ```

---

## Summary

### Current State

-   ✅ `overall_project_budget` - **Working** (calculated during creation)
-   ✅ `amount_sanctioned` - **Working** (set during approval)
-   ❌ `amount_forwarded` - **NOT WORKING** (field exists but never calculated)
-   ❌ `opening_balance` - **NOT WORKING** (field exists but never calculated)

### Intended Behavior (Updated Requirements)

-   `overall_project_budget`: Total project budget required (calculated from budget items)
-   `amount_forwarded`: Existing funds available with organization (entered by executor/applicant in create/edit forms)
-   `amount_sanctioned`: Amount to be requested for sanction = `Overall Budget - Amount Forwarded` (calculated and confirmed by coordinator)
-   `opening_balance`: Total funds available after sanction = `Amount Sanctioned + Amount Forwarded` (auto-calculated)

### Key Business Rules

1. **Executor/Applicant**:

    - Can enter `amount_forwarded` in project create/edit forms
    - System calculates `amount_sanctioned` and `opening_balance` for preview
    - Only requests sanction for: `Overall Budget - Amount Forwarded`

2. **Coordinator**:
    - Reviews the calculated `amount_sanctioned`
    - Confirms and saves `amount_sanctioned` during approval
    - System automatically calculates and saves `opening_balance`

### Business Impact

-   Multi-phase projects cannot properly track budgets across phases
-   Reports require manual entry of forwarded amounts (error-prone)
-   Budget visibility is incomplete (missing opening balances)
-   No automatic carry-forward of unspent amounts

### Next Steps (Updated Priority Order)

1. **Priority 1**: Add `amount_forwarded` input field in project create/edit forms (for all project types)
2. **Priority 1**: Implement calculations: `amount_sanctioned = overall_budget - amount_forwarded`
3. **Priority 1**: Implement calculation: `opening_balance = amount_sanctioned + amount_forwarded`
4. **Priority 2**: Update coordinator approval logic to use new calculation
5. **Priority 3**: Add validation to ensure `amount_forwarded <= overall_project_budget`
6. **Priority 4**: Auto-calculate `amount_forwarded` in reports from previous report balances
7. **Priority 5**: Improve display and user experience with help text
8. **Priority 6 (Future)**: Enable phase-to-phase auto-calculation (keep existing commented code as-is)

---

## Related Files Reference

### Models

-   `app/Models/OldProjects/Project.php` - Main project model
-   `app/Models/OldProjects/ProjectBudget.php` - Budget items model

### Controllers

-   `app/Http/Controllers/CoordinatorController.php` - Approval logic
-   `app/Http/Controllers/ProvincialController.php` - Provincial dashboard
-   `app/Http/Controllers/Projects/ProjectController.php` - Project CRUD
-   `app/Http/Controllers/Reports/Monthly/ReportController.php` - Report handling

### Views

-   `resources/views/projects/partials/Show/general_info.blade.php` - Display
-   `resources/views/projects/partials/scripts.blade.php` - Calculations
-   `resources/views/reports/monthly/partials/statements_of_account/*.blade.php` - Report forms

### Services

-   `app/Services/ProjectStatusService.php` - Status transitions
-   `app/Constants/ProjectStatus.php` - Status constants

---

## Phase-Wise Implementation Plan

This section provides a detailed, step-by-step implementation plan broken down into manageable phases.

---

### **Phase 1: Add Amount Forwarded Input Field** (Priority 1 - High)

**Objective**: Add the `amount_forwarded` input field to project create and edit forms

**Estimated Time**: 2-3 hours

**Tasks**:

1. **Locate Budget Form Partial** (30 minutes)

    - Find: `resources/views/projects/partials/budget.blade.php`
    - Identify where to add the new field (after overall_project_budget field)
    - Check if file exists for both create and edit forms

2. **Add Amount Forwarded Input Field** (45 minutes)

    ```blade
    <!-- Add after Overall Project Budget field in budget.blade.php -->
    <div class="mb-3">
        <label for="amount_forwarded" class="form-label">
            Amount Forwarded (Existing Funds Available)
            <small class="text-muted">(Optional - Enter if you already have some funds)</small>
        </label>
        <input type="number"
               step="0.01"
               min="0"
               id="amount_forwarded"
               name="amount_forwarded"
               class="form-control"
               value="{{ old('amount_forwarded', $project->amount_forwarded ?? 0.00) }}"
               oninput="calculateBudgetFields()">
        <div class="form-text">
            Enter the amount you already have available. The sanctioned amount will be calculated as:
            <strong>Overall Budget - Amount Forwarded</strong>
        </div>
    </div>
    ```

3. **Add Read-Only Display Fields** (45 minutes)

    ```blade
    <!-- Add these fields for preview purposes -->
    <div class="mb-3">
        <label class="form-label">Amount Sanctioned (Calculated)</label>
        <input type="number"
               id="amount_sanctioned"
               name="amount_sanctioned_preview"
               class="form-control"
               readonly
               value="{{ old('amount_sanctioned', $project->amount_sanctioned ?? 0.00) }}"
               style="background-color: #f8f9fa;">
        <div class="form-text">
            This will be officially confirmed by coordinator during approval
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Opening Balance (Calculated)</label>
        <input type="number"
               id="opening_balance"
               name="opening_balance_preview"
               class="form-control"
               readonly
               value="{{ old('opening_balance', $project->opening_balance ?? 0.00) }}"
               style="background-color: #f8f9fa;">
        <div class="form-text">
            Opening Balance = Amount Sanctioned + Amount Forwarded
        </div>
    </div>
    ```

4. **Update UpdateProjectRequest Validation** (30 minutes)

    - File: `app/Http/Requests/Projects/UpdateProjectRequest.php`
    - Add validation rule for `amount_forwarded`

    ```php
    'amount_forwarded' => 'nullable|numeric|min:0',
    ```

5. **Test the Form** (30 minutes)
    - Navigate to project create form
    - Verify field appears correctly
    - Test with various values
    - Check field appears in edit form

**Deliverables**:

-   ✅ Amount Forwarded input field visible in create form
-   ✅ Amount Forwarded input field visible in edit form
-   ✅ Validation rules added
-   ✅ Help text displayed correctly

---

### **Phase 2: Implement JavaScript Calculations** (Priority 1 - High)

**Objective**: Add real-time JavaScript calculations for budget fields

**Estimated Time**: 3-4 hours

**Tasks**:

1. **Update scripts.blade.php** (2 hours)

    - File: `resources/views/projects/partials/scripts.blade.php`
    - Add new function `calculateBudgetFields()`

    ```javascript
    // Add this function to scripts.blade.php
    function calculateBudgetFields() {
        // Get values
        const overallBudgetField = document.getElementById(
            "overall_project_budget"
        );
        const amountForwardedField =
            document.getElementById("amount_forwarded");
        const amountSanctionedField =
            document.getElementById("amount_sanctioned");
        const openingBalanceField = document.getElementById("opening_balance");

        if (!overallBudgetField) return;

        const overallBudget = parseFloat(overallBudgetField.value) || 0;
        const amountForwarded = parseFloat(amountForwardedField?.value) || 0;

        // Validate amount_forwarded doesn't exceed overall budget
        if (amountForwarded > overallBudget) {
            alert("Amount Forwarded cannot exceed Overall Project Budget");
            if (amountForwardedField) {
                amountForwardedField.value = overallBudget.toFixed(2);
            }
            return;
        }

        // Calculate Amount Sanctioned
        const amountSanctioned = overallBudget - amountForwarded;
        if (amountSanctionedField) {
            amountSanctionedField.value = amountSanctioned.toFixed(2);
        }

        // Calculate Opening Balance
        const openingBalance = amountSanctioned + amountForwarded;
        if (openingBalanceField) {
            openingBalanceField.value = openingBalance.toFixed(2);
        }

        // Log for debugging
        console.log("Budget Calculations:", {
            overallBudget: overallBudget.toFixed(2),
            amountForwarded: amountForwarded.toFixed(2),
            amountSanctioned: amountSanctioned.toFixed(2),
            openingBalance: openingBalance.toFixed(2),
        });
    }
    ```

2. **Update Event Listeners** (1 hour)

    ```javascript
    // Update the DOMContentLoaded event listener
    document.addEventListener("DOMContentLoaded", function () {
        // Existing code for in_charge select...

        // Add listeners for budget calculations
        const overallBudgetField = document.getElementById(
            "overall_project_budget"
        );
        const amountForwardedField =
            document.getElementById("amount_forwarded");

        if (amountForwardedField) {
            amountForwardedField.addEventListener(
                "input",
                calculateBudgetFields
            );
        }

        // Call calculateBudgetFields whenever overall budget changes
        // (this is already being set by calculateTotalAmountSanctioned)

        // Initial calculation on page load
        setTimeout(calculateBudgetFields, 100);
    });
    ```

3. **Update calculateTotalAmountSanctioned Function** (1 hour)

    ```javascript
    // Modify the existing function to call calculateBudgetFields
    function calculateTotalAmountSanctioned() {
        // ... existing code ...

        // Update the overall project budget field
        const overallProjectBudgetField = document.getElementById(
            "overall_project_budget"
        );
        if (overallProjectBudgetField) {
            overallProjectBudgetField.value = totalAmount.toFixed(2);
        }

        // Call the new budget fields calculation
        calculateBudgetFields();
    }
    ```

4. **Test JavaScript Functionality** (30 minutes)
    - Test amount_forwarded input triggers calculation
    - Verify validation when amount_forwarded > overall_budget
    - Check calculations are correct
    - Test on different browsers (Chrome, Firefox, Safari)

**Deliverables**:

-   ✅ Real-time calculation of amount_sanctioned
-   ✅ Real-time calculation of opening_balance
-   ✅ Validation prevents amount_forwarded > overall_budget
-   ✅ Calculations triggered on input changes

---

### **Phase 3: Update Controller Logic** (Priority 2 - High)

**Objective**: Save amount_forwarded and calculate fields during project save and approval

**Estimated Time**: 3-4 hours

**Tasks**:

1. **Update GeneralInfoController Store Method** (1 hour)

    - File: `app/Http/Controllers/Projects/GeneralInfoController.php`
    - Ensure `amount_forwarded` is saved during project creation

    ```php
    // In GeneralInfoController::store()
    $project = Project::create([
        // ... existing fields ...
        'overall_project_budget' => $request->overall_project_budget ?? 0,
        'amount_forwarded' => $request->amount_forwarded ?? 0,
        // Note: amount_sanctioned and opening_balance will be set by coordinator
    ]);
    ```

2. **Update GeneralInfoController Update Method** (1 hour)

    - Ensure `amount_forwarded` can be updated

    ```php
    // In GeneralInfoController::update()
    $project->update([
        // ... existing fields ...
        'overall_project_budget' => $request->overall_project_budget ?? $project->overall_project_budget,
        'amount_forwarded' => $request->amount_forwarded ?? $project->amount_forwarded,
    ]);
    ```

3. **Update CoordinatorController approveProject Method** (1.5 hours)

    - File: `app/Http/Controllers/CoordinatorController.php`
    - Update the approval logic to calculate based on amount_forwarded

    ```php
    public function approveProject($project_id)
    {
        $project = Project::where('project_id', $project_id)->with('budgets')->firstOrFail();
        $coordinator = auth()->user();

        try {
            ProjectStatusService::approve($project, $coordinator);
        } catch (Exception $e) {
            abort(403, $e->getMessage());
        }

        // Get overall budget and amount forwarded
        $overallBudget = $project->overall_project_budget ?? 0;
        $amountForwarded = $project->amount_forwarded ?? 0;

        // Validate amount_forwarded doesn't exceed overall budget
        if ($amountForwarded > $overallBudget) {
            return redirect()->back()
                ->with('error', 'Amount Forwarded cannot exceed Overall Project Budget. Please ask executor to correct this.');
        }

        // Calculate amount_sanctioned
        $amountSanctioned = $overallBudget - $amountForwarded;

        // Fallback if overall_project_budget is not set
        if ($overallBudget == 0 && $project->budgets && $project->budgets->count() > 0) {
            $overallBudget = $project->budgets->sum('this_phase');
            $amountSanctioned = $overallBudget - $amountForwarded;
        }

        // Ensure non-negative
        if ($amountSanctioned < 0) {
            $amountSanctioned = 0;
        }

        // Calculate opening_balance
        $openingBalance = $amountSanctioned + $amountForwarded;

        // Update project
        $project->amount_sanctioned = $amountSanctioned;
        $project->opening_balance = $openingBalance;
        $project->save();

        // Log the approval action
        \Log::info('Project Approved by Coordinator', [
            'project_id' => $project->project_id,
            'project_title' => $project->project_title,
            'coordinator_id' => $coordinator->id,
            'coordinator_name' => $coordinator->name,
            'overall_project_budget' => $overallBudget,
            'amount_forwarded' => $amountForwarded,
            'amount_sanctioned' => $amountSanctioned,
            'opening_balance' => $openingBalance,
        ]);

        return redirect()->back()->with('success',
            'Project approved successfully.<br>' .
            'Overall Budget: Rs. ' . number_format($overallBudget, 2) . '<br>' .
            'Amount Forwarded: Rs. ' . number_format($amountForwarded, 2) . '<br>' .
            'Amount Sanctioned: Rs. ' . number_format($amountSanctioned, 2) . '<br>' .
            'Opening Balance: Rs. ' . number_format($openingBalance, 2)
        );
    }
    ```

4. **Test Controller Methods** (30 minutes)
    - Create a new project with amount_forwarded
    - Edit project and change amount_forwarded
    - Submit and approve project
    - Verify calculations are correct in database

**Deliverables**:

-   ✅ amount_forwarded saved during project creation
-   ✅ amount_forwarded updated during project edit
-   ✅ amount_sanctioned calculated correctly during approval
-   ✅ opening_balance calculated correctly during approval
-   ✅ Validation prevents invalid data

---

### **Phase 4: Add Validation Rules** (Priority 3 - Medium)

**Objective**: Add comprehensive validation to ensure data integrity

**Estimated Time**: 2-3 hours

**Tasks**:

1. **Update Request Validation** (1 hour)

    - File: `app/Http/Requests/Projects/UpdateProjectRequest.php`
    - Add custom validation rules

    ```php
    public function rules(): array
    {
        return [
            // ... existing rules ...
            'amount_forwarded' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $overallBudget = $this->input('overall_project_budget', 0);
                    if ($value > $overallBudget) {
                        $fail('The amount forwarded cannot exceed the overall project budget.');
                    }
                },
            ],
        ];
    }
    ```

2. **Add Backend Validation in Controllers** (1 hour)

    ```php
    // In GeneralInfoController::store() and update()
    if ($request->amount_forwarded > $request->overall_project_budget) {
        return redirect()->back()
            ->withErrors(['amount_forwarded' => 'Amount Forwarded cannot exceed Overall Project Budget'])
            ->withInput();
    }
    ```

3. **Add Database-Level Validation** (Optional - 30 minutes)

    - Create migration to add CHECK constraint

    ```php
    // Migration file
    public function up()
    {
        DB::statement('ALTER TABLE projects ADD CONSTRAINT check_amount_forwarded
                       CHECK (amount_forwarded <= overall_project_budget)');
    }
    ```

4. **Test Validation** (30 minutes)
    - Test with amount_forwarded > overall_budget
    - Test with negative values
    - Test with NULL values
    - Verify error messages display correctly

**Deliverables**:

-   ✅ Validation rules prevent invalid data entry
-   ✅ User-friendly error messages
-   ✅ Both frontend and backend validation working

---

### **Phase 5: Update Display Views** (Priority 3 - Medium)

**Objective**: Ensure all views correctly display the new budget fields

**Estimated Time**: 2 hours

**Tasks**:

1. **Update Project Show View** (45 minutes)

    - File: `resources/views/projects/partials/Show/general_info.blade.php`
    - Verify fields display correctly (already showing, but may need formatting)

    ```blade
    <tr>
        <td class="label">Overall Project Budget:</td>
        <td class="value">Rs. {{ number_format($project->overall_project_budget, 2) }}</td>
    </tr>
    <tr>
        <td class="label">Amount Forwarded:</td>
        <td class="value">
            Rs. {{ number_format($project->amount_forwarded ?? 0, 2) }}
            @if($project->amount_forwarded > 0)
                <small class="text-muted">(Existing Funds Available)</small>
            @endif
        </td>
    </tr>
    <tr>
        <td class="label">Amount Sanctioned:</td>
        <td class="value">
            Rs. {{ number_format($project->amount_sanctioned ?? 0, 2) }}
            @if($project->amount_sanctioned > 0)
                <small class="text-success">✓ Approved by Coordinator</small>
            @endif
        </td>
    </tr>
    <tr>
        <td class="label">Opening Balance:</td>
        <td class="value">
            Rs. {{ number_format($project->opening_balance ?? 0, 2) }}
            @if($project->opening_balance > 0)
                <small class="text-info">(Total Funds Available)</small>
            @endif
        </td>
    </tr>
    ```

2. **Add Budget Summary Section** (45 minutes)

    ```blade
    <!-- Add a budget summary card -->
    <div class="card mt-3">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Budget Summary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <strong>Overall Project Budget:</strong><br>
                    <h4>Rs. {{ number_format($project->overall_project_budget, 2) }}</h4>
                </div>
                <div class="col-md-6">
                    <strong>Amount Forwarded (Existing Funds):</strong><br>
                    <h4>Rs. {{ number_format($project->amount_forwarded ?? 0, 2) }}</h4>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <strong>Amount Sanctioned (Requested):</strong><br>
                    <h4 class="text-primary">Rs. {{ number_format($project->amount_sanctioned ?? 0, 2) }}</h4>
                </div>
                <div class="col-md-6">
                    <strong>Opening Balance (Total Available):</strong><br>
                    <h4 class="text-success">Rs. {{ number_format($project->opening_balance ?? 0, 2) }}</h4>
                </div>
            </div>
            @if($project->amount_sanctioned > 0)
                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle"></i>
                    <strong>Calculation:</strong>
                    Amount Sanctioned (Rs. {{ number_format($project->amount_sanctioned, 2) }}) =
                    Overall Budget (Rs. {{ number_format($project->overall_project_budget, 2) }}) -
                    Amount Forwarded (Rs. {{ number_format($project->amount_forwarded ?? 0, 2) }})
                </div>
            @endif
        </div>
    </div>
    ```

3. **Update Dashboard Views** (30 minutes)
    - Ensure dashboards use correct budget field (amount_sanctioned or overall_project_budget)
    - Add tooltips/help text where needed

**Deliverables**:

-   ✅ All budget fields display correctly in project show view
-   ✅ Budget summary section added
-   ✅ Help text and tooltips added
-   ✅ Consistent formatting across all views

---

### **Phase 6: Testing and Documentation** (Priority 3 - Medium)

**Objective**: Comprehensive testing and update user documentation

**Estimated Time**: 3-4 hours

**Tasks**:

1. **Create Test Scenarios** (1 hour)

    - Test Case 1: Create project with amount_forwarded = 0
    - Test Case 2: Create project with amount_forwarded > 0
    - Test Case 3: Edit project and change amount_forwarded
    - Test Case 4: Submit and approve project
    - Test Case 5: Try to set amount_forwarded > overall_budget (should fail)
    - Test Case 6: Multi-phase project with amount_forwarded

2. **Execute Tests** (1 hour)

    - Test as executor user
    - Test as applicant user
    - Test as provincial user
    - Test as coordinator user
    - Document any issues found

3. **Fix Issues Found** (1 hour)

    - Address any bugs discovered during testing
    - Refine validation messages
    - Improve user experience based on feedback

4. **Update User Documentation** (1 hour)
    - Create user guide section explaining:
        - What is Amount Forwarded
        - When to use it
        - How calculations work
        - Examples with screenshots

**Deliverables**:

-   ✅ All test cases pass
-   ✅ No critical bugs
-   ✅ User documentation updated
-   ✅ Training materials prepared (if needed)

---

### **Phase 7 (Optional/Future): Auto-Calculate Amount Forwarded in Reports** (Priority 4 - Low)

**Objective**: Auto-populate amount_forwarded in monthly reports from previous reports

**Estimated Time**: 4-5 hours

**Tasks**:

1. **Update ReportController** (2 hours)

    - File: `app/Http/Controllers/Reports/Monthly/ReportController.php`
    - Add logic to fetch previous report's balance

    ```php
    // In create() method
    $previousReport = DPReport::where('project_id', $project->project_id)
        ->where('report_month', '<', $currentMonth)
        ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
        ->orderBy('report_month', 'desc')
        ->first();

    $lastExpenses = [];
    if ($previousReport) {
        foreach ($previousReport->accountDetails as $detail) {
            $lastExpenses[$detail->particulars] = $detail->balance_amount;
        }
    }

    // Pass to view
    return view('reports.monthly.create', compact('project', 'budgets', 'lastExpenses'));
    ```

2. **Update Report Views** (2 hours)

    - Update all statement of account views
    - Pre-populate amount_forwarded from previous report

    ```blade
    <input type="number" name="amount_forwarded[]" class="form-control"
        value="{{ old('amount_forwarded.'.$index, $lastExpenses[$budget->particular] ?? 0.00) }}"
        oninput="calculateRowTotals(this.closest('tr'))">
    ```

3. **Test Report Auto-Population** (1 hour)
    - Create first report (amount_forwarded = 0)
    - Create second report (should auto-populate from first report's balance)
    - Verify calculations are correct

**Deliverables**:

-   ✅ Reports auto-populate amount_forwarded
-   ✅ Manual override still possible
-   ✅ Reduces data entry errors

---

## Implementation Timeline

### Week 1

-   **Days 1-2**: Phase 1 (Add Input Field)
-   **Days 3-4**: Phase 2 (JavaScript Calculations)
-   **Day 5**: Phase 3 (Controller Logic - Part 1)

### Week 2

-   **Days 1-2**: Phase 3 (Controller Logic - Part 2)
-   **Day 3**: Phase 4 (Validation)
-   **Days 4-5**: Phase 5 (Update Views)

### Week 3

-   **Days 1-2**: Phase 6 (Testing)
-   **Days 3-5**: Phase 7 (Optional - Reports)

**Total Estimated Time**: 15-20 hours (2-3 weeks with testing and refinement)

---

## Risk Assessment and Mitigation

### Risks

1. **Data Migration**: Existing projects have NULL amount_forwarded
    - **Mitigation**: Default to 0.00, no migration needed
2. **User Confusion**: New field might confuse users
    - **Mitigation**: Add clear help text and examples
3. **Calculation Errors**: JavaScript or PHP calculation bugs

    - **Mitigation**: Comprehensive testing, logging

4. **Browser Compatibility**: JavaScript might not work on old browsers
    - **Mitigation**: Use standard JavaScript, test on multiple browsers

### Success Criteria

-   ✅ All projects can have amount_forwarded entered
-   ✅ Calculations are accurate (amount_sanctioned = overall_budget - amount_forwarded)
-   ✅ Opening balance calculated correctly
-   ✅ No data loss or corruption
-   ✅ User feedback is positive
-   ✅ System performance not impacted

---

## Rollback Plan

If critical issues are discovered after deployment:

1. **Immediate**: Disable JavaScript calculations (comment out function calls)
2. **Short-term**: Make amount_forwarded read-only
3. **Long-term**: Revert changes via Git, restore database if needed

---

**Document Version**: 2.0  
**Last Updated**: {{ date('Y-m-d') }}  
**Author**: Code Analysis  
**Status**: Updated per Requirements  
**Changes**:

-   Updated `amount_forwarded` to be available for ALL projects (single and multi-phase)
-   Changed calculation: `amount_sanctioned = overall_budget - amount_forwarded`
-   Updated `opening_balance` calculation: `amount_sanctioned + amount_forwarded`
-   Added requirement for input field in create/edit forms for executor/applicant users
-   Decision: Keep existing multi-phase JavaScript code commented (manual entry covers all cases)
-   **Added comprehensive phase-wise implementation plan**
