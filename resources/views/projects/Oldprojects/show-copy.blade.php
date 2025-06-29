{{-- resources/views/projects/Oldprojects/show.blade.php --}}
{{-- @extends('executor.dashboard') --}}

@php
    $userRole = Auth::user()->role ?? 'executor'; // Default to executor if not set
    $layout = match ($userRole) {
        'provincial' => 'provincial.dashboard',
        'coordinator' => 'coordinator.dashboard',
        default => 'executor.dashboard', // fallback to executor if role not matched
    };
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
            @include('projects.partials.Show.general_info')
        </div>
    </div>

    <!-- Key Information Section -->
    <div id="key-info-section" class="mb-3 card">
        <div class="card-header">
            <h4>Key Information</h4>
        </div>
        <div class="card-body">
            @include('projects.partials.Show.key_information')
        </div>
    </div>

    <!-- RST Beneficiaries Area for Development Projects -->
    @if ($project->project_type === 'Development Projects')
        @include('projects.partials.Show.RST.beneficiaries_area')
    @endif

    <!-- CCI Specific Partials -->
    @if ($project->project_type === 'CHILD CARE INSTITUTION')
        @include('projects.partials.Show.CCI.rationale')
        @include('projects.partials.Show.CCI.statistics')
        @include('projects.partials.Show.CCI.annexed_target_group')
        @include('projects.partials.Show.CCI.age_profile')
        @include('projects.partials.Show.CCI.personal_situation')
        @include('projects.partials.Show.CCI.economic_background')
        @include('projects.partials.Show.CCI.achievements')
        @include('projects.partials.Show.CCI.present_situation')
    @endif

    <!-- Residential Skill Training Specific Partials -->
    @if ($project->project_type === 'Residential Skill Training Proposal 2')
        @include('projects.partials.Show.RST.institution_info')
        @include('projects.partials.Show.RST.beneficiaries_area')
        @include('projects.partials.Show.RST.target_group')
        @include('projects.partials.Show.RST.target_group_annexure')
        @include('projects.partials.Show.RST.geographical_area')
    @endif

    <!-- Edu-Rural-Urban-Tribal Specific Partials -->
    @if ($project->project_type === 'Rural-Urban-Tribal')
        @include('projects.partials.Show.Edu-RUT.basic_info')
        @include('projects.partials.Show.Edu-RUT.target_group')
        @include('projects.partials.Show.Edu-RUT.annexed_target_group')
    @endif

    <!-- Individual - Ongoing Educational Support Partials -->
    @if ($project->project_type === 'Individual - Ongoing Educational support')
        @include('projects.partials.Show.IES.personal_info')
        @include('projects.partials.Show.IES.family_working_members')
        @include('projects.partials.Show.IES.immediate_family_details')
        @include('projects.partials.Show.IES.educational_background')
        @include('projects.partials.Show.IES.estimated_expenses')
        @include('projects.partials.Show.IES.attachments')
    @endif

    <!-- Individual - Initial Educational Support Partials -->
    @if ($project->project_type === 'Individual - Initial - Educational support')
        @include('projects.partials.Show.IES.personal_info')
        @include('projects.partials.Show.IES.family_working_members')
        @include('projects.partials.Show.IES.immediate_family_details')
        @include('projects.partials.Show.IIES.education_background')
        @include('projects.partials.Show.IIES.scope_financial_support')
        @include('projects.partials.Show.IES.attachments')
    @endif

    <!-- Individual - Livelihood Application Partials -->
    @if ($project->project_type === 'Individual - Livelihood Application')
        @include('projects.partials.Show.ILP.personal_info')
        @include('projects.partials.Show.ILP.revenue_goals')
        @include('projects.partials.Show.ILP.strength_weakness')
        @include('projects.partials.Show.ILP.risk_analysis')
        @include('projects.partials.Show.ILP.attached_docs')
        @include('projects.partials.Show.ILP.budget')
    @endif

    <!-- Individual - Access to Health Specific Partials -->
    @if ($project->project_type === 'Individual - Access to Health')
        @include('projects.partials.Show.IAH.personal_info')
        @include('projects.partials.Show.IAH.health_conditions')
        @include('projects.partials.Show.IAH.earning_members')
        @include('projects.partials.Show.IAH.support_details')
        @include('projects.partials.Show.IAH.budget_details')
        @include('projects.partials.Show.IAH.documents')
    @endif

    <!-- Institutional Ongoing Group Educational Specific Partials -->
    @if ($project->project_type === 'Institutional Ongoing Group Educational proposal')
        @include('projects.partials.Show.IGE.institution_info')
        @include('projects.partials.Show.IGE.beneficiaries_supported')
        @include('projects.partials.Show.IGE.ongoing_beneficiaries')
        @include('projects.partials.Show.IGE.new_beneficiaries')
        @include('projects.partials.Show.IGE.budget')
        @include('projects.partials.Show.IGE.development_monitoring')
    @endif

    <!-- Livelihood Development Project Specific Partials -->
    @if ($project->project_type === 'Livelihood Development Projects')
        @include('projects.partials.Show.LDP.need_analysis')
        @include('projects.partials.Show.LDP.target_group')
        @include('projects.partials.Show.LDP.intervention_logic')
    @endif

    <!-- CIC Specific Partial -->
    @if ($project->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER')
        @include('projects.partials.Show.CIC.basic_info')
    @endif

    <!-- Default Partial Sections (These should be included for all project types except certain individual types) -->
    @if (!in_array($project->project_type, ['Individual - Ongoing Educational support', 'Individual - Livelihood Application', 'Individual - Access to Health', 'Individual - Initial - Educational support']))
        @include('projects.partials.Show.logical_framework')
        @include('projects.partials.Show.sustainability')
        @include('projects.partials.Show.budget')
        @include('projects.partials.Show.attachments')
    @endif

    <!-- Annexed Target Group Partial for EduRUT (After Attachments Section) -->
    <div id="edu-rut-annexed-section" style="display:none;">
        @include('projects.partials.Edu-RUT.annexed_target_group')
    </div>

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
