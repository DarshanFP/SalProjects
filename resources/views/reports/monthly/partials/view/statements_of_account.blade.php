{{-- resources/views/reports/monthly/partials/view/statements_of_account.blade.php --}}
@php
    // Map project types to partial names
    $projectTypeMap = [
        'Development Projects' => 'development_projects',
        'Livelihood Development Projects' => 'development_projects',
        'Individual - Livelihood Application' => 'individual_livelihood',
        'Individual - Access to Health' => 'individual_health',
        'Institutional Ongoing Group Educational proposal' => 'institutional_education',
        'Individual - Ongoing Educational support' => 'individual_ongoing_education',
        'Individual - Ongoing Educational support' => 'individual_ongoing_education',
        'Individual - Initial - Educational support' => 'individual_education',
        'Residential Skill Training Proposal 2' => 'development_projects',
        'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' => 'development_projects',
        'CHILD CARE INSTITUTION' => 'development_projects',
        'Rural-Urban-Tribal' => 'development_projects',
    ];

    $partialName = $projectTypeMap[$report->project_type] ?? 'development_projects';
    $partialPath = "reports.monthly.partials.view.statements_of_account.{$partialName}";
@endphp

@if(View::exists($partialPath))
    @include($partialPath, [
        'report' => $report,
        'budgets' => $budgets ?? null
    ])
@else
    {{-- Fallback to default development projects structure --}}
    @include('reports.monthly.partials.view.statements_of_account.development_projects', [
        'report' => $report,
        'budgets' => $budgets ?? null
    ])
@endif
