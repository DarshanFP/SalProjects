<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Decimal Bounds (DecimalBounds)
    |--------------------------------------------------------------------------
    | Per-table, per-field numeric bounds for DECIMAL columns. Aligned to
    | column precision (e.g. decimal(10,2) => max 99999999.99).
    | Single source of truth for validation and clamping.
    */

    'project_budgets' => [
        'this_phase' => ['min' => 0, 'max' => 99999999.99],
        'next_phase' => ['min' => 0, 'max' => 99999999.99],
        'rate_quantity' => ['min' => 0, 'max' => 99999999.99],
        'rate_multiplier' => ['min' => 0, 'max' => 99999999.99],
        'rate_duration' => ['min' => 0, 'max' => 99999999.99],
        'rate_increase' => ['min' => 0, 'max' => 99999999.99],
        'amount_sanctioned' => ['min' => 0, 'max' => 99999999.99],
    ],

    'project_IIES_expenses' => [
        'iies_total_expenses' => ['min' => 0, 'max' => 99999999.99],
        'iies_expected_scholarship_govt' => ['min' => 0, 'max' => 99999999.99],
        'iies_support_other_sources' => ['min' => 0, 'max' => 99999999.99],
        'iies_beneficiary_contribution' => ['min' => 0, 'max' => 99999999.99],
        'iies_balance_requested' => ['min' => 0, 'max' => 99999999.99],
    ],

    'project_IIES_financial_support' => [
        'scholarship_amt' => ['min' => 0, 'max' => 99999999.99],
        'other_scholarship_amt' => ['min' => 0, 'max' => 99999999.99],
        'family_contrib' => ['min' => 0, 'max' => 99999999.99],
    ],

    'project_IIES_family_working_members' => [
        'iies_monthly_income' => ['min' => 0, 'max' => 99999999.99],
    ],

    'projects' => [
        'overall_project_budget' => ['min' => 0, 'max' => 99999999.99],
        'amount_forwarded' => ['min' => 0, 'max' => 99999999.99],
        'local_contribution' => ['min' => 0, 'max' => 99999999.99],
        'amount_sanctioned' => ['min' => 0, 'max' => 99999999.99],
        'opening_balance' => ['min' => 0, 'max' => 99999999.99],
    ],

    'default' => [
        'min' => 0,
        'max' => 99999999.99,
    ],
];
