{{-- resources/views/projects/Oldprojects/show.blade.php --}}
@php
    $userRole = Auth::user()->role ?? 'executor'; // Default to executor if not set
    $layout = match ($userRole) {
        'provincial' => 'provincial.dashboard',
        'coordinator' => 'coordinator.dashboard',
        default => 'executor.dashboard', // fallback to executor if role not matched
    };
@endphp
@php
    \Illuminate\Support\Facades\Log::info('Blade Template - IIES Education Background:', ['data' => $IIESEducationBackground ?? 'Not Set']);
@endphp


@extends($layout)

@section('content')
<div class="container">
    <h1 class="mb-4">Project Details</h1>

    <!-- General Information Section -->
    <div id="general-info-section" class="mb-3 card">
        <div class="card-header">
            <h4>General Information</h4>
        </div>
        <div class="card-body">
            @include('projects.partials.show.general_info')
        </div>
    </div>

    <!-- Key Information Section -->
    <div id="key-info-section" class="mb-3 card">
        <div class="card-header">
            <h4>Key Information</h4>
        </div>
        <div class="card-body">
            @include('projects.partials.show.key_information')
        </div>
    </div>

    <!-- RST Beneficiaries Area for Development Projects -->
    @if ($project->project_type === 'Development Projects')
        @include('projects.partials.show.RST.beneficiaries_area')
    @endif

    <!-- CCI Specific Partials -->
    @if ($project->project_type === 'CHILD CARE INSTITUTION')
        @include('projects.partials.show.CCI.rationale')
        @include('projects.partials.show.CCI.statistics')
        @include('projects.partials.show.CCI.annexed_target_group')
        @include('projects.partials.show.CCI.age_profile')
        @include('projects.partials.show.CCI.personal_situation')
        @include('projects.partials.show.CCI.economic_background')
        @include('projects.partials.show.CCI.achievements')
        @include('projects.partials.show.CCI.present_situation')
    @endif

    <!-- Residential Skill Training Specific Partials -->
    @if ($project->project_type === 'Residential Skill Training Proposal 2')
        @include('projects.partials.show.RST.beneficiaries_area')
        @include('projects.partials.show.RST.institution_info')
        @include('projects.partials.show.RST.target_group')
        @include('projects.partials.show.RST.target_group_annexure')
        @include('projects.partials.show.RST.geographical_area')
    @endif

    <!-- Edu-Rural-Urban-Tribal Specific Partials -->
    @if ($project->project_type === 'Rural-Urban-Tribal')
        @include('projects.partials.show.Edu-RUT.basic_info')
        @include('projects.partials.show.Edu-RUT.target_group')
        @include('projects.partials.show.Edu-RUT.annexed_target_group')
    @endif

    <!-- Individual - Ongoing Educational Support Partials -->
    @if ($project->project_type === 'Individual - Ongoing Educational support')
        @include('projects.partials.show.IES.personal_info')
        @include('projects.partials.show.IES.family_working_members')
        @include('projects.partials.show.IES.immediate_family_details')
        @include('projects.partials.show.IES.educational_background')
        @include('projects.partials.show.IES.estimated_expenses')
        @include('projects.partials.show.IES.attachments')
    @endif

    <!-- Individual - Initial Educational Support Partials -->
    @if ($project->project_type === 'Individual - Initial - Educational support')

        @include('projects.partials.show.IIES.personal_info')
        @include('projects.partials.show.IIES.family_working_members')
        @include('projects.partials.show.IIES.immediate_family_details')
        {{-- @include('projects.partials.show.IIES.education_background') --}}
        {{-- @include('projects.partials.show.IIES.education_background', ['project' => $project]) --}}

        @include('projects.partials.show.IIES.attachments', [
            'IIESAttachments' => $data['IIESAttachments']
        ])

        @include('projects.partials.show.IIES.scope_financial_support')
        @include('projects.partials.show.IIES.estimated_expenses')
        @include('projects.partials.show.IIES.attachments')
        {{-- @include('projects.partials.show.IIES.attachments', ['IIESAttachments' => $IIESAttachments]) --}}

        @endif



    <!-- Individual - Livelihood Application Partials -->
    @if ($project->project_type === 'Individual - Livelihood Application')
        @include('projects.partials.show.ILP.personal_info')
        @include('projects.partials.show.ILP.revenue_goals')
        @include('projects.partials.show.ILP.strength_weakness')
        @include('projects.partials.show.ILP.risk_analysis')
        @include('projects.partials.show.ILP.attached_docs')
        @include('projects.partials.show.ILP.budget')
    @endif

    <!-- Individual - Access to Health Specific Partials -->
    @if ($project->project_type === 'Individual - Access to Health')
        @include('projects.partials.show.IAH.personal_info')
        @include('projects.partials.show.IAH.health_conditions')
        @include('projects.partials.show.IAH.earning_members')
        @include('projects.partials.show.IAH.support_details')
        @include('projects.partials.show.IAH.budget_details')
        @include('projects.partials.show.IAH.documents')
    @endif

    <!-- Institutional Ongoing Group Educational Specific Partials -->
    @if ($project->project_type === 'Institutional Ongoing Group Educational proposal')
        @include('projects.partials.show.IGE.institution_info')
        @include('projects.partials.show.IGE.beneficiaries_supported')
        @include('projects.partials.show.IGE.ongoing_beneficiaries')
        @include('projects.partials.show.IGE.new_beneficiaries')
        @include('projects.partials.show.IGE.budget')
        @include('projects.partials.show.IGE.development_monitoring')
    @endif

    <!-- Livelihood Development Project Specific Partials -->
    @if ($project->project_type === 'Livelihood Development Projects')
        @include('projects.partials.show.LDP.need_analysis')
        @include('projects.partials.show.LDP.intervention_logic')
    @endif

    <!-- CIC Specific Partial -->
    @if ($project->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER')
        @include('projects.partials.show.CIC.basic_info')
    @endif

    <!-- Default Partial Sections (These should be included for all project types except certain individual types) -->
    @if (!in_array($project->project_type, ['Individual - Ongoing Educational support', 'Individual - Livelihood Application', 'Individual - Access to Health', 'Individual - Initial - Educational support']))
        @include('projects.partials.show.logical_framework')
        @include('projects.partials.show.sustainability')
        @include('projects.partials.show.budget')
        @include('projects.partials.show.attachments')
    @endif

    <!-- Comments Section -->
    <div>
        @include('projects.partials.ProjectComments', ['project' => $project])
    </div>

    <!-- Action Buttons -->
    <a href="{{ route('projects.index') }}" class="btn btn-primary">Back to Projects</a>
    <a href="{{ route('projects.downloadPdf', $project->project_id) }}" class="btn btn-secondary">Download PDF</a>
    <a href="{{ route('projects.downloadDoc', $project->project_id) }}" class="btn btn-secondary">Download Word</a>

    <!-- Status Action Buttons -->
    <div>
        @include('projects.partials.actions', ['project' => $project])
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const projectType = "{{ $project->project_type }}";

        const allSections = [
            'general-info-section',
            'key-info-section',
            'logical-framework-section',
            'sustainability-section',
            'budget-section',
            'attachments-section',
        ];

        function toggleSections() {
            // This script is minimal since sections are conditionally rendered above.
        }

        toggleSections();
    });
</script>

<style>
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Equal columns */
        grid-gap: 20px; /* Increased spacing between rows */
    }

    .info-label {
        font-weight: bold;
        margin-right: 10px; /* Optional spacing after labels */
    }

    .info-value {
        word-wrap: break-word;
        padding-left: 10px; /* Optional padding before values */
    }
</style>
@endsection
