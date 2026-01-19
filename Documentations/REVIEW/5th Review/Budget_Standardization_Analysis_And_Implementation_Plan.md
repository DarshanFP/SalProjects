# Budget Standardization Analysis & Implementation Plan

**Date:** January 2025  
**Status:** 📋 **ANALYSIS & PLANNING**  
**Purpose:** Review current budget calculation code and propose standardization strategy while preserving project-type-specific logic

---

## Executive Summary

This document reviews the current budget calculation implementation across all project types and proposes a **standardization strategy** that:

- ✅ **Extracts common patterns** into reusable service methods
- ✅ **Reduces code duplication** (currently duplicated in `ReportController` and `ExportReportController`)
- ✅ **Maintains project-type-specific logic** (different calculation formulas must remain)
- ✅ **Improves maintainability** (single source of truth for budget calculations)
- ✅ **Preserves functionality** (all existing calculations remain correct)

**Key Finding:** While budget **formulas** differ by project type (which is correct), the **calculation patterns** and **code structure** can be significantly standardized.

---

## Table of Contents

1. [Current Implementation Analysis](#current-implementation-analysis)
2. [Code Duplication Issues](#code-duplication-issues)
3. [Common Patterns Identified](#common-patterns-identified)
4. [What Can Be Standardized](#what-can-be-standardized)
5. [What Must Remain Project-Type-Specific](#what-must-remain-project-type-specific)
6. [Standardization Strategy](#standardization-strategy)
7. [Implementation Plan](#implementation-plan)
8. [Code Examples](#code-examples)
9. [Testing Strategy](#testing-strategy)
10. [Migration Path](#migration-path)

---

## Current Implementation Analysis

### Code Locations

Budget calculation methods are currently implemented in **2 controllers** with **significant duplication**:

1. **`app/Http/Controllers/Reports/Monthly/ReportController.php`**
   - Methods: `getBudgetDataByProjectType()`, `getDevelopmentProjectBudgets()`, `getILPBudgets()`, `getIAHBudgets()`, `getIGEBudgets()`, `getIIESBudgets()`, `getIESBudgets()`
   - **Lines:** ~300 lines
   - **Purpose:** Used for report creation/editing (calculates `amount_sanctioned` with contributions)

2. **`app/Http/Controllers/Reports/Monthly/ExportReportController.php`**
   - Methods: `getBudgetDataByProjectType()`, `getDevelopmentProjectBudgets()`, `getILPBudgets()`, `getIAHBudgets()`, `getIGEBudgets()`, `getIIESBudgets()`, `getIESBudgets()`
   - **Lines:** ~100 lines
   - **Purpose:** Used for PDF/Word export (simpler - just fetches budgets, no contribution calculation)

**Total Duplication:** ~200+ lines of duplicated code

---

### Current Method Structure

#### Pattern 1: Direct Mapping (Development Projects, IGE)

```php
// ReportController.php - Lines 131-139
private function getDevelopmentProjectBudgets($project)
{
    $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
    return ProjectBudget::where('project_id', $project->project_id)
        ->where('phase', $highestPhase)
        ->get();
}

// ExportReportController.php - Lines 389-397 (DUPLICATE)
private function getDevelopmentProjectBudgets($project)
{
    $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
    return ProjectBudget::where('project_id', $project->project_id)
        ->where('phase', $highestPhase)
        ->get();
}
```

**Issues:**
- ✅ Logic is identical
- ❌ Code is duplicated
- ⚠️ Phase selection uses `max('phase')` instead of `current_phase` (potential issue)

---

#### Pattern 2: Single Source Contribution (ILP, IAH)

```php
// ReportController.php - Lines 144-193 (ILP)
private function getILPBudgets($project)
{
    $budgets = ProjectILPBudget::where('project_id', $project->project_id)->get();
    
    if ($budgets->isEmpty()) {
        return collect();
    }
    
    // Get contribution
    $beneficiaryContribution = $budgets->first()->beneficiary_contribution ?? 0;
    $totalRows = $budgets->count();
    $contributionPerRow = $totalRows > 0 ? $beneficiaryContribution / $totalRows : 0;
    
    // Log calculation
    Log::info('ILP Budget calculation', [...]);
    
    // Map budgets with calculated amount_sanctioned
    return $budgets->map(function($budget) use ($contributionPerRow) {
        $cost = $budget->cost ?? 0;
        $finalAmount = max(0, $cost - $contributionPerRow);
        
        Log::info('ILP Budget row calculation', [...]);
        
        return (object)[
            'ILP_budget_id' => $budget->ILP_budget_id,
            'project_id' => $budget->project_id,
            'budget_desc' => $budget->budget_desc,
            'cost' => $budget->cost,
            'beneficiary_contribution' => $budget->beneficiary_contribution,
            'amount_sanctioned' => $finalAmount  // Calculated
        ];
    });
}

// ReportController.php - Lines 198-248 (IAH) - NEARLY IDENTICAL
private function getIAHBudgets($project)
{
    $budgets = ProjectIAHBudgetDetails::where('project_id', $project->project_id)->get();
    
    if ($budgets->isEmpty()) {
        return collect();
    }
    
    // Get contribution (DIFFERENT FIELD NAME)
    $familyContribution = $budgets->first()->family_contribution ?? 0;
    $totalRows = $budgets->count();
    $contributionPerRow = $totalRows > 0 ? $familyContribution / $totalRows : 0;
    
    // Log calculation
    Log::info('IAH Budget calculation', [...]);
    
    // Map budgets with calculated amount_sanctioned
    return $budgets->map(function($budget) use ($contributionPerRow) {
        $amount = $budget->amount ?? 0;  // DIFFERENT FIELD NAME
        $finalAmount = max(0, $amount - $contributionPerRow);
        
        Log::info('IAH Budget row calculation', [...]);
        
        return (object)[
            'IAH_budget_id' => $budget->IAH_budget_id,
            'project_id' => $budget->project_id,
            'particular' => $budget->particular,  // DIFFERENT FIELD NAME
            'amount' => $budget->amount,
            'family_contribution' => $budget->family_contribution,
            'amount_sanctioned' => $finalAmount  // Calculated
        ];
    });
}
```

**Issues:**
- ✅ Logic pattern is identical (single source contribution distribution)
- ❌ Code is duplicated (ILP and IAH are 95% identical)
- ❌ Field names differ (`cost` vs `amount`, `budget_desc` vs `particular`, `beneficiary_contribution` vs `family_contribution`)
- ✅ Calculation formula is the same: `max(0, original_amount - (contribution / total_rows))`

---

#### Pattern 3: Multiple Source Contribution (IIES, IES)

```php
// ReportController.php - Lines 261-324 (IIES)
private function getIIESBudgets($project)
{
    $iiesExpenses = ProjectIIESExpenses::where('project_id', $project->project_id)->first();
    if (!$iiesExpenses) {
        return collect();
    }
    
    $expenseDetails = $iiesExpenses->expenseDetails;
    if ($expenseDetails->isEmpty()) {
        return collect();
    }
    
    // Get contributions from parent (3 sources)
    $expectedScholarshipGovt = $iiesExpenses->iies_expected_scholarship_govt ?? 0;
    $supportOtherSources = $iiesExpenses->iies_support_other_sources ?? 0;
    $beneficiaryContribution = $iiesExpenses->iies_beneficiary_contribution ?? 0;
    
    $totalContribution = $expectedScholarshipGovt + $supportOtherSources + $beneficiaryContribution;
    $totalRows = $expenseDetails->count();
    $contributionPerRow = $totalRows > 0 ? $totalContribution / $totalRows : 0;
    
    Log::info('IIES Budget calculation', [...]);
    
    return $expenseDetails->map(function($detail) use ($contributionPerRow) {
        $amount = $detail->iies_amount ?? 0;
        $finalAmount = max(0, $amount - $contributionPerRow);
        
        Log::info('IIES Budget row calculation', [...]);
        
        return (object)[
            'IIES_expense_id' => $detail->IIES_expense_id,
            'iies_particular' => $detail->iies_particular,
            'iies_amount' => $detail->iies_amount,
            'amount_sanctioned' => $finalAmount
        ];
    });
}

// ReportController.php - Lines 329-392 (IES) - NEARLY IDENTICAL
private function getIESBudgets($project)
{
    // Same structure, different field names:
    // - iies_expected_scholarship_govt → expected_scholarship_govt
    // - iies_support_other_sources → support_other_sources
    // - iies_beneficiary_contribution → beneficiary_contribution
    // - iies_amount → amount
    // - iies_particular → particular
}
```

**Issues:**
- ✅ Logic pattern is identical (multiple source contribution distribution)
- ❌ Code is duplicated (IIES and IES are 95% identical)
- ❌ Field names differ (IIES uses `iies_` prefix, IES doesn't)
- ✅ Calculation formula is the same: `max(0, original_amount - (total_contribution / total_rows))`

---

## Code Duplication Issues

### Duplication Summary

| Location | Methods | Lines | Purpose |
|----------|---------|-------|---------|
| `ReportController` | 6 methods | ~300 lines | Report creation/editing (with contribution calculation) |
| `ExportReportController` | 6 methods | ~100 lines | PDF/Word export (simple fetch, no calculation) |
| **Total** | **12 methods** | **~400 lines** | **Significant duplication** |

### Specific Duplications

1. **Switch Statement:** Duplicated in both controllers (identical project type routing)
2. **Development Projects:** Duplicated (identical phase selection logic)
3. **IGE:** Duplicated (identical direct fetch)
4. **ILP/IAH:** Nearly identical (only field names differ)
5. **IIES/IES:** Nearly identical (only field names differ)

### Maintenance Issues

- ❌ **Bug Fixes:** Must be applied in 2 places
- ❌ **New Project Types:** Must be added in 2 places
- ❌ **Logic Changes:** Must be updated in 2 places
- ❌ **Testing:** Must test both locations
- ❌ **Code Reviews:** Must review both locations

---

## Common Patterns Identified

### Pattern 1: Empty Collection Handling

**Current (Repeated 6+ times):**
```php
if ($budgets->isEmpty()) {
    return collect();
}
```

**Can Be Standardized:** ✅ Yes - Common helper method

---

### Pattern 2: Contribution Distribution (Single Source)

**Current (ILP, IAH):**
```php
$contribution = $budgets->first()->{contribution_field} ?? 0;
$totalRows = $budgets->count();
$contributionPerRow = $totalRows > 0 ? $contribution / $totalRows : 0;
```

**Can Be Standardized:** ✅ Yes - Common helper method

---

### Pattern 3: Contribution Distribution (Multiple Source)

**Current (IIES, IES):**
```php
$source1 = $parent->{field1} ?? 0;
$source2 = $parent->{field2} ?? 0;
$source3 = $parent->{field3} ?? 0;
$totalContribution = $source1 + $source2 + $source3;
$totalRows = $details->count();
$contributionPerRow = $totalRows > 0 ? $totalContribution / $totalRows : 0;
```

**Can Be Standardized:** ✅ Yes - Common helper method

---

### Pattern 4: Amount Sanctioned Calculation

**Current (Repeated 4+ times):**
```php
$originalAmount = $budget->{amount_field} ?? 0;
$finalAmount = max(0, $originalAmount - $contributionPerRow);
```

**Can Be Standardized:** ✅ Yes - Common helper method

---

### Pattern 5: Budget Object Mapping

**Current (Repeated 4+ times):**
```php
return $budgets->map(function($budget) use ($contributionPerRow) {
    $amount = $budget->{amount_field} ?? 0;
    $finalAmount = max(0, $amount - $contributionPerRow);
    
    return (object)[
        '{id_field}' => $budget->{id_field},
        '{particular_field}' => $budget->{particular_field},
        '{amount_field}' => $budget->{amount_field},
        'amount_sanctioned' => $finalAmount
    ];
});
```

**Can Be Standardized:** ✅ Yes - Configuration-based mapping

---

### Pattern 6: Logging

**Current (Repeated 6+ times):**
```php
Log::info('{ProjectType} Budget calculation', [
    'total_rows' => $totalRows,
    '{contribution_field}' => $contribution,
    'contribution_per_row' => $contributionPerRow
]);

Log::info('{ProjectType} Budget row calculation', [
    '{particular_field}' => $budget->{particular_field},
    'original_amount' => $amount,
    'contribution_subtracted' => $contributionPerRow,
    'final_amount' => $finalAmount
]);
```

**Can Be Standardized:** ✅ Yes - Common logging method

---

## What Can Be Standardized

### ✅ High-Level Standardization Opportunities

1. **Service Class Creation**
   - Create `BudgetCalculationService` to centralize all budget logic
   - Single source of truth for budget calculations
   - Used by both `ReportController` and `ExportReportController`

2. **Common Helper Methods**
   - `calculateContributionPerRow($contribution, $totalRows)` - Single source
   - `calculateMultipleContributions($sources)` - Multiple sources
   - `calculateAmountSanctioned($originalAmount, $contributionPerRow)` - Amount calculation
   - `preventNegativeAmount($amount)` - Negative prevention

3. **Configuration-Based Field Mapping**
   - Define field mappings per project type in configuration
   - Reusable mapping logic
   - Easy to add new project types

4. **Strategy Pattern for Calculation Types**
   - `DirectMappingStrategy` - For Development Projects, IGE
   - `SingleSourceContributionStrategy` - For ILP, IAH
   - `MultipleSourceContributionStrategy` - For IIES, IES

5. **Common Logging**
   - Standardized logging format
   - Project type-aware logging
   - Consistent log messages

---

## What Must Remain Project-Type-Specific

### ❌ Cannot Be Standardized (Must Remain Specific)

1. **Model/Table Selection**
   - Each project type uses different models/tables
   - Must remain project-type-specific
   - Examples:
     - Development: `ProjectBudget`
     - ILP: `ProjectILPBudget`
     - IAH: `ProjectIAHBudgetDetails`
     - IGE: `ProjectIGEBudget`
     - IIES: `ProjectIIESExpenses` + `ProjectIIESExpenseDetails`
     - IES: `ProjectIESExpenses` + `ProjectIESExpenseDetails`

2. **Field Name Mappings**
   - Different field names for same concepts
   - Must remain project-type-specific
   - Examples:
     - Particulars: `particular` vs `budget_desc` vs `iies_particular`
     - Amount: `cost` vs `amount` vs `iies_amount` vs `this_phase`
     - Contribution: `beneficiary_contribution` vs `family_contribution`

3. **Phase Selection Logic**
   - Development projects use phase-based budgeting
   - Other types don't use phases
   - Must remain project-type-specific

4. **Parent-Child Relationships**
   - IIES/IES use parent-child table relationships
   - Other types use single table
   - Must remain project-type-specific

5. **Contribution Source Fields**
   - Different contribution field names per project type
   - Must remain project-type-specific
   - Examples:
     - ILP: `beneficiary_contribution` (single)
     - IAH: `family_contribution` (single)
     - IIES: `iies_expected_scholarship_govt`, `iies_support_other_sources`, `iies_beneficiary_contribution` (3 sources)
     - IES: `expected_scholarship_govt`, `support_other_sources`, `beneficiary_contribution` (3 sources)

---

## Standardization Strategy

### Architecture: Service Class with Strategy Pattern

**Proposed Structure:**

```
app/Services/Budget/
├── BudgetCalculationService.php (Main service)
├── Strategies/
│   ├── DirectMappingStrategy.php
│   ├── SingleSourceContributionStrategy.php
│   └── MultipleSourceContributionStrategy.php
└── Config/
    └── BudgetFieldMappings.php (Configuration)
```

### Service Class Responsibilities

**`BudgetCalculationService`:**
- Main entry point for all budget calculations
- Routes to appropriate strategy based on project type
- Provides common helper methods
- Handles logging

**Strategy Classes:**
- `DirectMappingStrategy` - For Development Projects, IGE
- `SingleSourceContributionStrategy` - For ILP, IAH
- `MultipleSourceContributionStrategy` - For IIES, IES

**Configuration:**
- Field mappings per project type
- Model class names per project type
- Calculation type per project type

---

## Implementation Plan

### Phase 1: Create Service Infrastructure (4 hours)

#### Task 1.1: Create BudgetCalculationService Base Structure

**File:** `app/Services/Budget/BudgetCalculationService.php`

**Responsibilities:**
- Main `getBudgetsForReport()` method
- Routes to appropriate strategy
- Common helper methods
- Logging

**Methods:**
```php
public static function getBudgetsForReport(Project $project, bool $calculateContributions = true): Collection
public static function getBudgetsForExport(Project $project): Collection
private static function getStrategyForProjectType(string $projectType): BudgetCalculationStrategyInterface
private static function calculateContributionPerRow(float $contribution, int $totalRows): float
private static function calculateAmountSanctioned(float $originalAmount, float $contributionPerRow): float
private static function preventNegativeAmount(float $amount): float
```

---

#### Task 1.2: Create Strategy Interface

**File:** `app/Services/Budget/Strategies/BudgetCalculationStrategyInterface.php`

**Interface:**
```php
interface BudgetCalculationStrategyInterface
{
    public function getBudgets(Project $project, bool $calculateContributions = true): Collection;
    public function getProjectType(): string;
}
```

---

#### Task 1.3: Create Configuration File

**File:** `app/Services/Budget/Config/BudgetFieldMappings.php`

**Structure:**
```php
return [
    'Development Projects' => [
        'model' => ProjectBudget::class,
        'strategy' => DirectMappingStrategy::class,
        'fields' => [
            'particular' => 'particular',
            'amount' => 'this_phase',
            'id' => 'id',
        ],
        'phase_based' => true,
        'phase_selection' => 'highest', // or 'current'
    ],
    'Individual - Livelihood Application' => [
        'model' => ProjectILPBudget::class,
        'strategy' => SingleSourceContributionStrategy::class,
        'fields' => [
            'particular' => 'budget_desc',
            'amount' => 'cost',
            'contribution' => 'beneficiary_contribution',
            'id' => 'ILP_budget_id',
        ],
        'phase_based' => false,
    ],
    // ... other project types
];
```

---

### Phase 2: Implement Strategy Classes (6 hours)

#### Task 2.1: DirectMappingStrategy

**File:** `app/Services/Budget/Strategies/DirectMappingStrategy.php`

**Purpose:** Handle Development Projects and IGE (no contribution calculation)

**Logic:**
- Fetch budgets from appropriate model
- Apply phase filter if needed (Development Projects)
- Return budgets directly (no contribution calculation)
- Map fields if needed

**Project Types:**
- Development Projects (DP, LDP, RST, CIC, CCI, Edu-RUT)
- IGE

---

#### Task 2.2: SingleSourceContributionStrategy

**File:** `app/Services/Budget/Strategies/SingleSourceContributionStrategy.php`

**Purpose:** Handle ILP and IAH (single contribution source)

**Logic:**
- Fetch budgets from appropriate model
- Get contribution from first row
- Calculate contribution per row
- Map budgets with calculated `amount_sanctioned`

**Project Types:**
- ILP
- IAH

**Reusable Methods:**
- `calculateContributionPerRow()`
- `calculateAmountSanctioned()`
- `mapBudgetsWithContribution()`

---

#### Task 2.3: MultipleSourceContributionStrategy

**File:** `app/Services/Budget/Strategies/MultipleSourceContributionStrategy.php`

**Purpose:** Handle IIES and IES (multiple contribution sources)

**Logic:**
- Fetch parent expense record
- Get expense details (child records)
- Calculate total contribution from 3 sources
- Calculate contribution per row
- Map expense details with calculated `amount_sanctioned`

**Project Types:**
- IIES
- IES

**Reusable Methods:**
- `calculateTotalContribution()`
- `calculateContributionPerRow()`
- `calculateAmountSanctioned()`
- `mapExpenseDetailsWithContribution()`

---

### Phase 3: Update Controllers (2 hours)

#### Task 3.1: Update ReportController

**Changes:**
- Replace all `get*Budgets()` methods with `BudgetCalculationService::getBudgetsForReport()`
- Remove duplicated methods
- Update `getBudgetDataByProjectType()` to use service

**Before:**
```php
private function getBudgetDataByProjectType($project)
{
    switch ($project->project_type) {
        case 'Development Projects':
            return $this->getDevelopmentProjectBudgets($project);
        case 'Individual - Livelihood Application':
            return $this->getILPBudgets($project);
        // ... etc
    }
}
```

**After:**
```php
private function getBudgetDataByProjectType($project)
{
    return BudgetCalculationService::getBudgetsForReport($project, true);
}
```

**Lines Removed:** ~300 lines  
**Lines Added:** ~5 lines

---

#### Task 3.2: Update ExportReportController

**Changes:**
- Replace all `get*Budgets()` methods with `BudgetCalculationService::getBudgetsForExport()`
- Remove duplicated methods
- Update `getBudgetDataByProjectType()` to use service

**Before:**
```php
private function getBudgetDataByProjectType($project)
{
    switch ($project->project_type) {
        case 'Development Projects':
            return $this->getDevelopmentProjectBudgets($project);
        // ... etc
    }
}
```

**After:**
```php
private function getBudgetDataByProjectType($project)
{
    return BudgetCalculationService::getBudgetsForExport($project);
}
```

**Lines Removed:** ~100 lines  
**Lines Added:** ~5 lines

---

### Phase 4: Testing & Verification (4 hours)

#### Task 4.1: Unit Tests

**Files to Create:**
- `tests/Unit/Services/Budget/BudgetCalculationServiceTest.php`
- `tests/Unit/Services/Budget/Strategies/DirectMappingStrategyTest.php`
- `tests/Unit/Services/Budget/Strategies/SingleSourceContributionStrategyTest.php`
- `tests/Unit/Services/Budget/Strategies/MultipleSourceContributionStrategyTest.php`

**Test Cases:**
- Test each project type's budget calculation
- Test contribution distribution
- Test negative amount prevention
- Test empty collection handling
- Test phase selection (Development Projects)

---

#### Task 4.2: Integration Tests

**Test Cases:**
- Test report creation with new service
- Test report editing with new service
- Test PDF export with new service
- Test Word export with new service
- Verify calculations match old implementation

---

#### Task 4.3: Manual Testing

**Test Scenarios:**
- Create report for each project type
- Verify `amount_sanctioned` calculations are correct
- Verify contribution distributions are correct
- Export PDF/Word for each project type
- Compare results with old implementation

---

## Code Examples

### Example 1: BudgetCalculationService Structure

```php
<?php

namespace App\Services\Budget;

use App\Models\OldProjects\Project;
use App\Services\Budget\Strategies\BudgetCalculationStrategyInterface;
use App\Services\Budget\Strategies\DirectMappingStrategy;
use App\Services\Budget\Strategies\SingleSourceContributionStrategy;
use App\Services\Budget\Strategies\MultipleSourceContributionStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BudgetCalculationService
{
    /**
     * Get budgets for report (with contribution calculation)
     */
    public static function getBudgetsForReport(Project $project, bool $calculateContributions = true): Collection
    {
        $strategy = self::getStrategyForProjectType($project->project_type);
        return $strategy->getBudgets($project, $calculateContributions);
    }

    /**
     * Get budgets for export (simple fetch, no contribution calculation)
     */
    public static function getBudgetsForExport(Project $project): Collection
    {
        $strategy = self::getStrategyForProjectType($project->project_type);
        return $strategy->getBudgets($project, false);
    }

    /**
     * Get appropriate strategy for project type
     */
    private static function getStrategyForProjectType(string $projectType): BudgetCalculationStrategyInterface
    {
        $config = config('budget.field_mappings');
        
        if (!isset($config[$projectType])) {
            Log::warning('Unknown project type, using DirectMappingStrategy', ['project_type' => $projectType]);
            return new DirectMappingStrategy('Development Projects');
        }

        $strategyClass = $config[$projectType]['strategy'];
        return new $strategyClass($projectType);
    }

    /**
     * Calculate contribution per row (single source)
     */
    public static function calculateContributionPerRow(float $contribution, int $totalRows): float
    {
        return $totalRows > 0 ? $contribution / $totalRows : 0;
    }

    /**
     * Calculate total contribution from multiple sources
     */
    public static function calculateTotalContribution(array $sources): float
    {
        return array_sum(array_map(fn($source) => $source ?? 0, $sources));
    }

    /**
     * Calculate amount sanctioned after contribution
     */
    public static function calculateAmountSanctioned(float $originalAmount, float $contributionPerRow): float
    {
        return self::preventNegativeAmount($originalAmount - $contributionPerRow);
    }

    /**
     * Prevent negative amounts
     */
    public static function preventNegativeAmount(float $amount): float
    {
        return max(0, $amount);
    }

    /**
     * Log budget calculation
     */
    public static function logCalculation(string $projectType, array $data): void
    {
        Log::info("{$projectType} Budget calculation", $data);
    }

    /**
     * Log budget row calculation
     */
    public static function logRowCalculation(string $projectType, array $data): void
    {
        Log::info("{$projectType} Budget row calculation", $data);
    }
}
```

---

### Example 2: SingleSourceContributionStrategy

```php
<?php

namespace App\Services\Budget\Strategies;

use App\Models\OldProjects\Project;
use App\Services\Budget\BudgetCalculationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SingleSourceContributionStrategy implements BudgetCalculationStrategyInterface
{
    protected string $projectType;
    protected array $config;

    public function __construct(string $projectType)
    {
        $this->projectType = $projectType;
        $this->config = config("budget.field_mappings.{$projectType}");
    }

    public function getBudgets(Project $project, bool $calculateContributions = true): Collection
    {
        $modelClass = $this->config['model'];
        $budgets = $modelClass::where('project_id', $project->project_id)->get();

        if ($budgets->isEmpty()) {
            return collect();
        }

        // If not calculating contributions (export), return as-is
        if (!$calculateContributions) {
            return $budgets;
        }

        // Get contribution field name
        $contributionField = $this->config['fields']['contribution'];
        $amountField = $this->config['fields']['amount'];
        $particularField = $this->config['fields']['particular'];
        $idField = $this->config['fields']['id'];

        // Get contribution from first row
        $contribution = $budgets->first()->{$contributionField} ?? 0;
        $totalRows = $budgets->count();
        $contributionPerRow = BudgetCalculationService::calculateContributionPerRow($contribution, $totalRows);

        // Log calculation
        BudgetCalculationService::logCalculation($this->projectType, [
            'total_rows' => $totalRows,
            $contributionField => $contribution,
            'contribution_per_row' => $contributionPerRow
        ]);

        // Map budgets with calculated amount_sanctioned
        return $budgets->map(function($budget) use ($contributionPerRow, $amountField, $particularField, $idField) {
            $originalAmount = $budget->{$amountField} ?? 0;
            $finalAmount = BudgetCalculationService::calculateAmountSanctioned($originalAmount, $contributionPerRow);

            // Log row calculation
            BudgetCalculationService::logRowCalculation($this->projectType, [
                $particularField => $budget->{$particularField},
                'original_amount' => $originalAmount,
                'contribution_subtracted' => $contributionPerRow,
                'final_amount' => $finalAmount
            ]);

            // Create budget object with calculated amount_sanctioned
            $budgetObject = (object) array_merge(
                $budget->toArray(),
                ['amount_sanctioned' => $finalAmount]
            );

            return $budgetObject;
        });
    }

    public function getProjectType(): string
    {
        return $this->projectType;
    }
}
```

---

### Example 3: Configuration File

**File:** `config/budget.php`

```php
<?php

use App\Models\OldProjects\ProjectBudget;
use App\Models\OldProjects\ILP\ProjectILPBudget;
use App\Models\OldProjects\IAH\ProjectIAHBudgetDetails;
use App\Models\OldProjects\IGE\ProjectIGEBudget;
use App\Models\OldProjects\IIES\ProjectIIESExpenses;
use App\Models\OldProjects\IES\ProjectIESExpenses;
use App\Services\Budget\Strategies\DirectMappingStrategy;
use App\Services\Budget\Strategies\SingleSourceContributionStrategy;
use App\Services\Budget\Strategies\MultipleSourceContributionStrategy;

return [
    'field_mappings' => [
        // Development Projects (Direct Mapping)
        'Development Projects' => [
            'model' => ProjectBudget::class,
            'strategy' => DirectMappingStrategy::class,
            'fields' => [
                'particular' => 'particular',
                'amount' => 'this_phase',
                'id' => 'id',
            ],
            'phase_based' => true,
            'phase_selection' => 'highest', // 'highest' or 'current'
        ],

        'Livelihood Development Projects' => [
            'model' => ProjectBudget::class,
            'strategy' => DirectMappingStrategy::class,
            'fields' => [
                'particular' => 'particular',
                'amount' => 'this_phase',
                'id' => 'id',
            ],
            'phase_based' => true,
            'phase_selection' => 'highest',
        ],

        // ... other development project types (same config)

        // ILP (Single Source Contribution)
        'Individual - Livelihood Application' => [
            'model' => ProjectILPBudget::class,
            'strategy' => SingleSourceContributionStrategy::class,
            'fields' => [
                'particular' => 'budget_desc',
                'amount' => 'cost',
                'contribution' => 'beneficiary_contribution',
                'id' => 'ILP_budget_id',
            ],
            'phase_based' => false,
        ],

        // IAH (Single Source Contribution)
        'Individual - Access to Health' => [
            'model' => ProjectIAHBudgetDetails::class,
            'strategy' => SingleSourceContributionStrategy::class,
            'fields' => [
                'particular' => 'particular',
                'amount' => 'amount',
                'contribution' => 'family_contribution',
                'id' => 'IAH_budget_id',
            ],
            'phase_based' => false,
        ],

        // IGE (Direct Mapping)
        'Institutional Ongoing Group Educational proposal' => [
            'model' => ProjectIGEBudget::class,
            'strategy' => DirectMappingStrategy::class,
            'fields' => [
                'particular' => 'particular', // Needs verification
                'amount' => 'amount', // Needs verification
                'id' => 'id',
            ],
            'phase_based' => false,
        ],

        // IIES (Multiple Source Contribution)
        'Individual - Initial - Educational support' => [
            'parent_model' => ProjectIIESExpenses::class,
            'child_relationship' => 'expenseDetails',
            'strategy' => MultipleSourceContributionStrategy::class,
            'fields' => [
                'particular' => 'iies_particular',
                'amount' => 'iies_amount',
                'id' => 'IIES_expense_id',
            ],
            'contribution_sources' => [
                'iies_expected_scholarship_govt',
                'iies_support_other_sources',
                'iies_beneficiary_contribution',
            ],
            'phase_based' => false,
        ],

        // IES (Multiple Source Contribution)
        'Individual - Ongoing Educational support' => [
            'parent_model' => ProjectIESExpenses::class,
            'child_relationship' => 'expenseDetails',
            'strategy' => MultipleSourceContributionStrategy::class,
            'fields' => [
                'particular' => 'particular',
                'amount' => 'amount',
                'id' => 'IES_expense_id',
            ],
            'contribution_sources' => [
                'expected_scholarship_govt',
                'support_other_sources',
                'beneficiary_contribution',
            ],
            'phase_based' => false,
        ],
    ],
];
```

---

## Testing Strategy

### Unit Tests

#### Test 1: DirectMappingStrategy

```php
public function test_development_projects_returns_budgets_for_highest_phase()
{
    // Create project with budgets in phases 1 and 2
    // Verify only phase 2 budgets are returned
}

public function test_ige_returns_all_budgets()
{
    // Create IGE project with budgets
    // Verify all budgets are returned
}
```

#### Test 2: SingleSourceContributionStrategy

```php
public function test_ilp_distributes_beneficiary_contribution_correctly()
{
    // Create ILP project with 3 budget rows, beneficiary_contribution = 300
    // Verify each row has contribution_per_row = 100
    // Verify amount_sanctioned = cost - 100 for each row
}

public function test_iah_distributes_family_contribution_correctly()
{
    // Similar test for IAH
}
```

#### Test 3: MultipleSourceContributionStrategy

```php
public function test_iies_combines_and_distributes_three_contribution_sources()
{
    // Create IIES project with:
    // - expected_scholarship_govt = 100
    // - support_other_sources = 200
    // - beneficiary_contribution = 300
    // - 3 expense detail rows
    // Verify total_contribution = 600
    // Verify contribution_per_row = 200
    // Verify amount_sanctioned = iies_amount - 200 for each row
}
```

#### Test 4: Common Helpers

```php
public function test_calculate_contribution_per_row()
{
    // Test division by zero (returns 0)
    // Test normal division
    // Test with decimal results
}

public function test_prevent_negative_amount()
{
    // Test positive amount (returns as-is)
    // Test negative amount (returns 0)
    // Test zero (returns 0)
}
```

---

### Integration Tests

#### Test 1: Report Creation

```php
public function test_report_creation_uses_budget_calculation_service()
{
    // Create report for each project type
    // Verify budgets are calculated correctly
    // Verify amount_sanctioned values match expected
}
```

#### Test 2: Report Export

```php
public function test_pdf_export_uses_budget_calculation_service()
{
    // Export PDF for each project type
    // Verify budgets are included correctly
}
```

---

## Migration Path

### Step 1: Create Service Infrastructure (Non-Breaking)

**Duration:** 4 hours  
**Risk:** Low (new code, doesn't affect existing)

1. Create `BudgetCalculationService` class
2. Create strategy interface
3. Create configuration file
4. Create strategy classes
5. **Do NOT update controllers yet**

**Testing:** Unit tests for new service classes

---

### Step 2: Update ReportController (Breaking Change - Needs Testing)

**Duration:** 2 hours  
**Risk:** Medium (changes existing functionality)

1. Update `ReportController::getBudgetDataByProjectType()` to use service
2. Remove old `get*Budgets()` methods
3. Test thoroughly

**Testing:**
- Create report for each project type
- Verify calculations match old implementation
- Test edge cases (empty budgets, zero contributions, etc.)

---

### Step 3: Update ExportReportController (Breaking Change - Needs Testing)

**Duration:** 1 hour  
**Risk:** Medium (changes existing functionality)

1. Update `ExportReportController::getBudgetDataByProjectType()` to use service
2. Remove old `get*Budgets()` methods
3. Test thoroughly

**Testing:**
- Export PDF for each project type
- Export Word for each project type
- Verify exports match old implementation

---

### Step 4: Verification & Cleanup (1 hour)

**Duration:** 1 hour  
**Risk:** Low

1. Remove any remaining duplicate code
2. Update documentation
3. Code review
4. Final testing

---

## Benefits of Standardization

### Code Quality

- ✅ **Reduced Duplication:** ~200 lines of duplicated code eliminated
- ✅ **Single Source of Truth:** All budget logic in one place
- ✅ **Easier Maintenance:** Changes in one location
- ✅ **Better Testing:** Centralized test coverage

### Maintainability

- ✅ **Easier to Add New Project Types:** Just add configuration
- ✅ **Easier to Fix Bugs:** Fix once, works everywhere
- ✅ **Easier Code Reviews:** Review one implementation
- ✅ **Better Documentation:** Centralized documentation

### Performance

- ✅ **No Performance Impact:** Same calculations, just organized better
- ✅ **Potential for Caching:** Can add caching at service level
- ✅ **Better Query Optimization:** Centralized query logic

---

## Risks & Mitigation

### Risk 1: Breaking Existing Functionality

**Mitigation:**
- Comprehensive testing before migration
- Side-by-side comparison with old implementation
- Gradual migration (service first, then controllers)
- Rollback plan ready

### Risk 2: Configuration Errors

**Mitigation:**
- Validate configuration on service initialization
- Unit tests for each project type configuration
- Clear error messages for misconfiguration

### Risk 3: Phase Selection Issue

**Current Issue:** Development projects use `max('phase')` instead of `current_phase`

**Recommendation:**
- Fix during standardization
- Use `$project->current_phase` instead of `max('phase')`
- Add fallback to `max('phase')` if `current_phase` is null

---

## Implementation Timeline

### Week 1: Service Infrastructure

- **Day 1-2:** Create service class and strategy interface
- **Day 3:** Create configuration file
- **Day 4:** Create strategy classes
- **Day 5:** Unit tests for service classes

**Deliverable:** Working service classes with unit tests

---

### Week 2: Controller Updates & Testing

- **Day 1:** Update `ReportController` and test
- **Day 2:** Update `ExportReportController` and test
- **Day 3:** Integration testing
- **Day 4:** Manual testing
- **Day 5:** Bug fixes and verification

**Deliverable:** Controllers updated, all tests passing

---

### Week 3: Verification & Documentation

- **Day 1:** Code review and cleanup
- **Day 2:** Documentation updates
- **Day 3:** Final testing
- **Day 4:** Production deployment preparation
- **Day 5:** Production deployment

**Deliverable:** Standardized budget system in production

---

## Success Criteria

### Functional Requirements

- ✅ All project types calculate budgets correctly
- ✅ Contribution distributions work correctly
- ✅ Reports generate with correct `amount_sanctioned` values
- ✅ PDF/Word exports include correct budgets
- ✅ No regression in existing functionality

### Code Quality Requirements

- ✅ No code duplication
- ✅ Single source of truth for budget calculations
- ✅ Configuration-based field mappings
- ✅ Comprehensive unit tests (>80% coverage)
- ✅ All integration tests passing

### Performance Requirements

- ✅ No performance degradation
- ✅ Same or better query performance
- ✅ No additional database queries

---

## Recommendations

### Immediate Actions

1. **Review Current Code:** Verify all calculations are correct before standardization
2. **Fix Phase Selection:** Change `max('phase')` to `current_phase` during standardization
3. **Verify IGE Fields:** Confirm exact field names in `project_ige_budgets` table

### Implementation Priority

1. **High Priority:** Create service infrastructure (reduces duplication immediately)
2. **High Priority:** Update `ReportController` (most used)
3. **Medium Priority:** Update `ExportReportController` (less frequently used)
4. **Low Priority:** Add caching (future enhancement)

### Future Enhancements

1. **Caching:** Cache budget calculations for frequently accessed projects
2. **Validation:** Add validation to ensure contribution totals are correct
3. **Warnings:** Add warnings if contribution exceeds original amount
4. **Performance Monitoring:** Track calculation performance

---

## Conclusion

**Standardization is Feasible and Beneficial**

While budget **formulas** must remain project-type-specific (which is correct), the **code structure** and **calculation patterns** can be significantly standardized:

- ✅ **~200 lines** of duplicated code can be eliminated
- ✅ **Single source of truth** for all budget calculations
- ✅ **Easier maintenance** and testing
- ✅ **No functionality loss** - all calculations remain correct
- ✅ **Better code organization** with strategy pattern

**Recommendation:** Proceed with standardization using the proposed service-based architecture with strategy pattern.

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Ready for Implementation  
**Estimated Total Duration:** 16 hours (2 weeks)

---

**End of Budget Standardization Analysis & Implementation Plan**
