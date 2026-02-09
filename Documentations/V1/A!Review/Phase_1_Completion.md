# Phase 1 Completion – Critical Data Safety

## Scope (What Was Fixed)

Phase 1 implemented **Critical Data Safety** for IIES, Project Budget, and CCI (Statistics, PersonalSituation, AgeProfile, EconomicBackground) only.

### 1. FormRequest updates

- **NormalizesInput trait** – Added `prepareForValidation()` (merges normalized input before rules) and `getNormalizedInput()` for Strategy B. Trait is used by all Phase 1 FormRequests.
- **IIES Expenses** – StoreIIESExpensesRequest, UpdateIIESExpensesRequest: normalization (empty/placeholder → 0 for NOT NULL decimals, placeholder → null for amounts array); rules: required|numeric|min:0|NumericBoundsRule for main decimals; draft relaxation (nullable when `save_as_draft`).
- **IIES Financial Support** – StoreIIESFinancialSupportRequest, UpdateIIESFinancialSupportRequest: BooleanNormalizer for govt_eligible_scholarship, other_eligible_scholarship; PlaceholderNormalizer for numeric fields; rules: required|boolean for booleans, nullable|numeric|min:0|NumericBoundsRule for amounts.
- **IIES Family Working Members** – StoreIIESFamilyWorkingMembersRequest, UpdateIIESFamilyWorkingMembersRequest: PlaceholderNormalizer for iies_monthly_income.*; rules: nullable|numeric|min:0|NumericBoundsRule for monthly_income.
- **Project Budget** – StoreBudgetRequest, UpdateBudgetRequest: normalization for phases.*.budget.* decimals (empty/placeholder → 0); rules: nullable|numeric|min:0|NumericBoundsRule for all decimal columns.
- **CCI Statistics** – StoreCCIStatisticsRequest, UpdateCCIStatisticsRequest: PlaceholderNormalizer for all integer keys (placeholder → null); rules: nullable|OptionalIntegerRule for each integer field.
- **CCI PersonalSituation, AgeProfile, EconomicBackground** – Same pattern: NormalizesInput, placeholder → null for integer keys, nullable|OptionalIntegerRule.

### 2. Controller and sub-controller fixes

- **IIESExpensesController** – Removed `DB::beginTransaction`/commit/rollBack. Strategy B: StoreIIESExpensesRequest::createFrom, getNormalizedInput, Validator::make->validate(), use validated only. Throws on failure (no JSON error return). Draft: uses ?? 0 for main decimals when key missing.
- **FinancialSupportController (IIES)** – Removed transactions. Strategy B with Store/UpdateIIESFinancialSupportRequest; use validated only; throw on failure.
- **IIESFamilyWorkingMembersController** – Removed transactions. Strategy B with Store/UpdateIIESFamilyWorkingMembersRequest; use validated only; throw on failure.
- **BudgetController** – Uses StoreBudgetRequest/UpdateBudgetRequest via Strategy B (createFrom, getNormalizedInput, validate, validated only). No nested transaction (none existed).
- **CCI StatisticsController, PersonalSituationController, AgeProfileController, EconomicBackgroundController** – Removed DB::beginTransaction/commit/rollBack. Strategy B with respective Store/Update FormRequests; use validated only; throw on failure (no redirect with error return in catch).

### 3. Transaction ownership

- **ProjectController** – Catch block rethrows `\Illuminate\Validation\ValidationException` after rollBack so Laravel returns 422 with validation errors. Other exceptions still trigger rollBack and redirect with generic error.
- Sub-controllers no longer start or commit transactions; they run inside the parent’s transaction and throw on failure.

### 4. Logging

- Normalization applied is logged at DEBUG in each FormRequest’s `normalizeInput` where values change (e.g. IIES Expenses, CCI Statistics).
- Validation failures are not logged as ERROR (Laravel’s default validation handling applies).

## Out of Scope (What Was NOT Touched)

- IES, IAH, ILP, IGE, RST, EduRUT, CIC, LDP, and all other project types and their controllers/FormRequests.
- Routes, database schema, Blade views, JavaScript.
- Report flows, admin flows, bulk actions.
- Phase 2+ (IES attachments, Logical Framework, etc.).

## FormRequests Modified

| FormRequest | Changes |
|-------------|---------|
| StoreIIESExpensesRequest | NormalizesInput, prepareForValidation, normalizeInput (NOT NULL decimals → 0, amounts → null), rules with NumericBoundsRule, draft relaxation |
| UpdateIIESExpensesRequest | Same as Store |
| StoreIIESFinancialSupportRequest | NormalizesInput, BooleanNormalizer + PlaceholderNormalizer, rules required\|boolean + NumericBoundsRule |
| UpdateIIESFinancialSupportRequest | Same as Store |
| StoreIIESFamilyWorkingMembersRequest | NormalizesInput, PlaceholderNormalizer for monthly_income.*, NumericBoundsRule |
| UpdateIIESFamilyWorkingMembersRequest | Same as Store |
| StoreBudgetRequest | NormalizesInput, normalizeInput for phases.budget decimals, NumericBoundsRule for all decimals |
| UpdateBudgetRequest | Same as Store |
| StoreCCIStatisticsRequest | NormalizesInput, placeholder → null for integer keys, OptionalIntegerRule |
| UpdateCCIStatisticsRequest | Same as Store |
| StoreCCIPersonalSituationRequest | NormalizesInput, placeholder → null, OptionalIntegerRule |
| UpdateCCIPersonalSituationRequest | Same as Store |
| StoreCCIAgeProfileRequest | NormalizesInput, placeholder → null, OptionalIntegerRule |
| UpdateCCIAgeProfileRequest | Same as Store |
| StoreCCIEconomicBackgroundRequest | NormalizesInput, placeholder → null, OptionalIntegerRule, rules for integer keys |
| UpdateCCIEconomicBackgroundRequest | Same as Store |

**Trait:** `App\Http\Requests\Concerns\NormalizesInput` – added `prepareForValidation()` and `getNormalizedInput()`.

## Controllers Modified

| Controller | Changes |
|------------|---------|
| IIESExpensesController | Strategy B, remove transactions, use validated only, throw on failure |
| FinancialSupportController (IIES) | Strategy B, remove transactions, use validated only, throw on failure |
| IIESFamilyWorkingMembersController | Strategy B, remove transactions, use validated only, throw on failure |
| BudgetController | Strategy B with StoreBudgetRequest/UpdateBudgetRequest, use validated only |
| StatisticsController (CCI) | Strategy B, remove transactions, use validated only, throw on failure |
| PersonalSituationController (CCI) | Strategy B, remove transactions, use validated only, throw on failure |
| AgeProfileController (CCI) | Strategy B, remove transactions, use validated only, throw on failure |
| EconomicBackgroundController (CCI) | Strategy B, remove transactions, use validated only, throw on failure |
| ProjectController | Catch ValidationException, rollBack, rethrow |

## Production Risks Eliminated

- **NOT NULL violations** – Empty string and placeholder for IIES expenses and financial support are normalized to 0 or 1 before validation; validated data is used for persistence.
- **Numeric overflow** – NumericBoundsRule (max 99999999.99) applied to all IIES decimals, budget decimals, and IIES family working members monthly_income.
- **Placeholder in integer columns** – CCI Statistics, PersonalSituation, AgeProfile, EconomicBackground normalize `-`, `N/A`, etc. to null before validation; OptionalIntegerRule validates nullable integers.
- **Partial saves** – Sub-controllers throw on failure; parent owns transaction and rolls back on any exception; ValidationException is rethrown so the user sees validation errors.

## Verification Checklist

- [ ] No NOT NULL DB errors for IIES/CCI in production for Phase 1 flows
- [ ] No numeric overflow DB errors for budget/IIES in production
- [ ] No partial saves when a Phase 1 sub-controller fails (user sees error, transaction rolled back)
- [ ] All unit tests passing (`php artisan test tests/Unit/`)
- [ ] All Phase 1 feature tests passing (`php artisan test tests/Feature/Validation/Phase1_*`)

Run: `php artisan test tests/Unit/ tests/Feature/Validation/Phase1_*.php`
