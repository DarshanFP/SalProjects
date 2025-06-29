{{-- resources/views/reports/monthly/partials/statements_of_account.blade.php --}}
@php
    // Map project types to partial names
    $projectTypeMap = [
        'Development Projects' => 'development_projects',
        'Livelihood Development Projects' => 'development_projects',
        'Individual - Livelihood Application' => 'individual_livelihood',
        'Individual - Access to Health' => 'individual_health',
        'Institutional Ongoing Group Educational proposal' => 'institutional_education',
        'Individual - Ongoing Educational support' => 'individual_education',
        'Individual - Initial - Educational support' => 'individual_education',
        'Residential Skill Training Proposal 2' => 'development_projects',
        'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' => 'development_projects',
        'CHILD CARE INSTITUTION' => 'development_projects',
        'Rural-Urban-Tribal' => 'development_projects',
    ];

    $partialName = $projectTypeMap[$project->project_type] ?? 'fallback';
    $partialPath = "reports.monthly.partials.statements_of_account.{$partialName}";
@endphp

@if(View::exists($partialPath))
    @include($partialPath, [
        'project' => $project,
        'budgets' => $budgets ?? null,
        'report' => $report ?? null,
        'lastExpenses' => $lastExpenses ?? collect(),
        'amountSanctioned' => $amountSanctioned ?? 0,
        'amountForwarded' => $amountForwarded ?? 0
    ])
@else
    {{-- Fallback to default development projects structure --}}
    @include('reports.monthly.partials.statements_of_account.development_projects', [
        'project' => $project,
        'budgets' => $budgets ?? null,
        'report' => $report ?? null,
        'lastExpenses' => $lastExpenses ?? collect(),
        'amountSanctioned' => $amountSanctioned ?? 0,
        'amountForwarded' => $amountForwarded ?? 0
    ])
@endif
