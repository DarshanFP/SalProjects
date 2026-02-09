{{-- resources/views/projects/Oldprojects/show.blade.php --}}
@php
    $userRole = Auth::user()->role ?? 'executor'; // Default to executor if not set
    $layout = match ($userRole) {
        'admin' => 'admin.layout',
        'provincial' => 'provincial.dashboard',
        'coordinator' => 'coordinator.dashboard',
        'general' => 'general.dashboard',
        default => 'executor.dashboard', // fallback to executor if role not matched
    };
@endphp

@extends($layout)

@section('content')
<div class="container">
    <h1 class="mb-4">Project Details</h1>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {!! session('error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Phase Information Section --}}
    @if($project->status === \App\Constants\ProjectStatus::APPROVED_BY_COORDINATOR)
        @php
            $phaseInfo = \App\Services\ProjectPhaseService::getPhaseInfo($project);
        @endphp

        <div class="mb-3 card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Phase Information</h4>
                @if(auth()->user()->role === 'executor' || auth()->user()->role === 'applicant')
                    <a href="{{ route('monthly.report.create', $project->project_id) }}" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> Write Report
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Final Commencement Date:</strong>
                           {{ $phaseInfo['final_commencement_display'] ?? 'Not set' }}</p>
                        <p><strong>Current Phase:</strong> {{ $phaseInfo['current_phase'] }}</p>
                        <p><strong>Overall Project Period:</strong> {{ $phaseInfo['overall_project_period'] }} phase(s)</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Months Elapsed in Current Phase:</strong>
                           {{ $phaseInfo['months_in_current_phase'] }} / 12</p>
                        <p><strong>Months Remaining:</strong>
                           {{ $phaseInfo['months_remaining_in_phase'] }}</p>
                        <div class="progress mb-2" style="height: 25px;">
                            <div class="progress-bar {{ $phaseInfo['is_eligible_for_completion'] ? 'bg-success' : 'bg-info' }}"
                                 role="progressbar"
                                 style="width: {{ $phaseInfo['phase_progress_percentage'] }}%"
                                 aria-valuenow="{{ $phaseInfo['phase_progress_percentage'] }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                {{ $phaseInfo['phase_progress_percentage'] }}%
                            </div>
                        </div>
                    </div>
                </div>

                @if($project->is_completed)
                    <div class="alert alert-success">
                        <strong>✓ Project Completed</strong><br>
                        Completed on: {{ $project->completed_at->format('F d, Y') }}
                        @if($project->completion_notes)
                            <br>Notes: {{ $project->completion_notes }}
                        @endif
                    </div>
                @elseif($phaseInfo['is_eligible_for_completion'])
                    <div class="alert alert-info">
                        <strong>Project Eligible for Completion</strong><br>
                        {{ $phaseInfo['months_in_current_phase'] }} months have elapsed in the current phase.
                        You can now mark this project as completed.
                    </div>

                    @if(in_array(Auth::user()->role, ['executor', 'applicant']))
                        <form action="{{ route('projects.markCompleted', $project->project_id) }}"
                              method="POST"
                              style="display:inline;"
                              onsubmit="return confirm('Are you sure you want to mark this project as completed? This action cannot be undone.');">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check-circle"></i> Mark as Completed
                            </button>
                        </form>
                    @endif
                @else
                    <div class="alert alert-warning">
                        <strong>Project Not Yet Eligible for Completion</strong><br>
                        {{ $phaseInfo['months_in_current_phase'] }} months have elapsed in the current phase.
                        Project will be eligible for completion after 10 months ({{ 10 - $phaseInfo['months_in_current_phase'] }} more month(s) remaining).
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- General Information Section -->
    <div id="general-info-section" class="mb-3 card">
        <div class="card-header">
            <h4>General Information</h4>
        </div>
        <div class="card-body">
            @include('projects.partials.Show.general_info')
        </div>
    </div>

    <!-- Key Information Section (excluded for Individual project types) -->
    @if (!in_array($project->project_type, \App\Constants\ProjectType::getIndividualTypes()))
        <div id="key-info-section" class="mb-3 card">
            <div class="card-header">
                <h4>Key Information</h4>
            </div>
            <div class="card-body">
                @include('projects.partials.Show.key_information')
            </div>
        </div>
    @endif

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
        @include('projects.partials.Show.RST.beneficiaries_area')
        @include('projects.partials.Show.RST.institution_info')
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
        @include('projects.partials.Show.IIES.personal_info')
        @include('projects.partials.Show.IIES.family_working_members')
        @include('projects.partials.Show.IIES.immediate_family_details')
        @include('projects.partials.Show.IIES.scope_financial_support')
        @include('projects.partials.Show.IIES.estimated_expenses')
        @include('projects.partials.Show.IIES.attachments')
    @endif

    {{-- @if (true) {{-- Temporarily always show IIES section for testing
        @include('projects.partials.Show.IIES.personal_info')
        @include('projects.partials.Show.IIES.family_working_members')
        @include('projects.partials.Show.IIES.immediate_family_details')
        @include('projects.partials.Show.IIES.scope_financial_support')
        @include('projects.partials.Show.IIES.estimated_expenses')

        @include('projects.partials.Show.IIES.attachments', ['IIESAttachments' => $IIESAttachments ?? []])
    @endif --}}

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
        @include('projects.partials.Show.IGE.budget', ['IGEbudget' => $budget ?? collect()])
        @include('projects.partials.Show.IGE.development_monitoring')
    @endif

    <!-- Livelihood Development Project Specific Partials -->
    @if ($project->project_type === 'Livelihood Development Projects')
        @include('projects.partials.Show.LDP.need_analysis')
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

    <!-- Comments Section -->
    <div>
        @include('projects.partials.ProjectComments', ['project' => $project])
    </div>

    <!-- Action Buttons -->
    @if(auth()->user()->role === 'admin')
        <a href="{{ route('admin.projects.index') }}" class="btn btn-primary">Back to Projects</a>
    @else
        <a href="{{ route('projects.index') }}" class="btn btn-primary">Back to Projects</a>
    @endif

    @php
        use App\Helpers\ProjectPermissionHelper;
        $user = auth()->user();
        // Check if user can edit this project using ProjectPermissionHelper
        $canEdit = ProjectPermissionHelper::canEdit($project, $user);
    @endphp

    @if($canEdit)
        <a href="{{ route('projects.edit', $project->project_id) }}" class="btn btn-warning">Edit Project</a>
    @endif

    @if(auth()->user()->role !== 'admin')
        @if(auth()->user()->role === 'provincial')
            <a href="{{ route('provincial.projects.downloadPdf', $project->project_id) }}" class="btn btn-secondary">Download PDF</a>
        @elseif(auth()->user()->role === 'coordinator')
            <a href="{{ route('coordinator.projects.downloadPdf', $project->project_id) }}" class="btn btn-secondary">Download PDF</a>
        @else
            <a href="{{ route('projects.downloadPdf', $project->project_id) }}" class="btn btn-secondary">Download PDF</a>
        @endif
    @endif

    <!-- Status Action Buttons -->
    <div>
        @include('projects.partials.actions', ['project' => $project])
    </div>

    <!-- Status History -->
    @include('projects.partials.Show.status_history', ['project' => $project])
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
        overflow-wrap: break-word;
        white-space: pre-wrap !important; /* Preserve line breaks from textareas */
        line-height: 1.6;
        padding-left: 10px; /* Optional padding before values */
    }

    /* Also preserve line breaks for form-control divs displaying textarea content */
    .card-body .form-control:not(input):not(select):not(textarea) {
        white-space: pre-wrap !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
        line-height: 1.6 !important;
    }
</style>
@endsection
