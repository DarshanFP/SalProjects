<?php

use App\Models\OldProjects\ProjectBudget;
use App\Models\OldProjects\IAH\ProjectIAHBudgetDetails;
use App\Models\OldProjects\IGE\ProjectIGEBudget;
use App\Models\OldProjects\ILP\ProjectILPBudget;
use App\Models\OldProjects\IIES\ProjectIIESExpenses;
use App\Models\OldProjects\IES\ProjectIESExpenses;
use App\Services\Budget\Strategies\DirectMappingStrategy;
use App\Services\Budget\Strategies\SingleSourceContributionStrategy;
use App\Services\Budget\Strategies\MultipleSourceContributionStrategy;

return [
    /*
    |--------------------------------------------------------------------------
    | Budget Alignment Feature Flags (Phase 0)
    |--------------------------------------------------------------------------
    |
    | All flags default to false. Enable per phase after validation.
    | See: Documentations/V1/Basic Info fund Mapping Issue/PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md
    |
    | To enable per environment: set in .env or override in config.
    | Example: BUDGET_RESOLVER_ENABLED=true (add to .env)
    |
    */
    'resolver_enabled' => env('BUDGET_RESOLVER_ENABLED', false),
    'sync_to_projects_on_type_save' => env('BUDGET_SYNC_ON_TYPE_SAVE', false),
    'sync_to_projects_before_approval' => env('BUDGET_SYNC_BEFORE_APPROVAL', false),
    // Phase 3: When true, blocks post-approval edits to budget fields (General Info + type-specific).
    // Default = false. Enable in STAGING first; monitor budget log for "Budget edit blocked" entries;
    // then enable in PRODUCTION. Do not auto-enable.
    'restrict_general_info_after_approval' => env('BUDGET_RESTRICT_GENERAL_INFO_AFTER_APPROVAL', false),
    'admin_reconciliation_enabled' => env('BUDGET_ADMIN_RECONCILIATION_ENABLED', false),

    /**
     * Budget calculation field mappings for each project type
     *
     * Each project type configuration includes:
     * - model: The Eloquent model class to use
     * - strategy: The strategy class to handle calculations
     * - fields: Field name mappings (particular, amount, contribution, id)
     * - phase_based: Whether this project type uses phase-based budgeting
     * - phase_selection: 'current' (preferred) or 'highest' (fallback)
     *
     * For MultipleSourceContributionStrategy:
     * - parent_model: The parent model (for IIES/IES)
     * - child_relationship: The relationship method name
     * - contribution_sources: Array of contribution field names
     */
    'field_mappings' => [
        // Development Projects - Direct Mapping (Phase-based)
        'Development Projects' => [
            'model' => ProjectBudget::class,
            'strategy' => DirectMappingStrategy::class,
            'fields' => [
                'particular' => 'particular',
                'amount' => 'this_phase',
                'id' => 'id',
            ],
            'phase_based' => true,
            'phase_selection' => 'current', // Use current_phase, fallback to max('phase')
        ],

        // Livelihood Development Projects - Direct Mapping (Phase-based)
        'Livelihood Development Projects' => [
            'model' => ProjectBudget::class,
            'strategy' => DirectMappingStrategy::class,
            'fields' => [
                'particular' => 'particular',
                'amount' => 'this_phase',
                'id' => 'id',
            ],
            'phase_based' => true,
            'phase_selection' => 'current',
        ],

        // Residential Skill Training Proposal 2 - Direct Mapping (Phase-based)
        'Residential Skill Training Proposal 2' => [
            'model' => ProjectBudget::class,
            'strategy' => DirectMappingStrategy::class,
            'fields' => [
                'particular' => 'particular',
                'amount' => 'this_phase',
                'id' => 'id',
            ],
            'phase_based' => true,
            'phase_selection' => 'current',
        ],

        // PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER - Direct Mapping (Phase-based)
        'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' => [
            'model' => ProjectBudget::class,
            'strategy' => DirectMappingStrategy::class,
            'fields' => [
                'particular' => 'particular',
                'amount' => 'this_phase',
                'id' => 'id',
            ],
            'phase_based' => true,
            'phase_selection' => 'current',
        ],

        // CHILD CARE INSTITUTION - Direct Mapping (Phase-based)
        'CHILD CARE INSTITUTION' => [
            'model' => ProjectBudget::class,
            'strategy' => DirectMappingStrategy::class,
            'fields' => [
                'particular' => 'particular',
                'amount' => 'this_phase',
                'id' => 'id',
            ],
            'phase_based' => true,
            'phase_selection' => 'current',
        ],

        // Rural-Urban-Tribal - Direct Mapping (Phase-based)
        'Rural-Urban-Tribal' => [
            'model' => ProjectBudget::class,
            'strategy' => DirectMappingStrategy::class,
            'fields' => [
                'particular' => 'particular',
                'amount' => 'this_phase',
                'id' => 'id',
            ],
            'phase_based' => true,
            'phase_selection' => 'current',
        ],

        // Individual - Livelihood Application (ILP) - Single Source Contribution
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

        // Individual - Access to Health (IAH) - Single Source Contribution
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

        // Institutional Ongoing Group Educational proposal (IGE) - Direct Mapping
        'Institutional Ongoing Group Educational proposal' => [
            'model' => ProjectIGEBudget::class,
            'strategy' => DirectMappingStrategy::class,
            'fields' => [
                'particular' => 'name', // Using 'name' field as particular (may need verification)
                'amount' => 'total_amount', // Using 'total_amount' as amount (may need verification)
                'id' => 'IGE_budget_id',
            ],
            'phase_based' => false,
        ],

        // Individual - Initial - Educational support (IIES) - Multiple Source Contribution
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

        // Individual - Ongoing Educational support (IES) - Multiple Source Contribution
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
