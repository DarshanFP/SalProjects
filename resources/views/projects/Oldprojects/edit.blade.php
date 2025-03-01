{{-- resources/views/projects/Oldprojects/edit.blade.php --}}
@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Edit Project</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('projects.update', $project->project_id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Project General Information -->
                        <div class="mb-3 card">
                            <div class="card-header">
                                <h4 class="fp-text-margin">General Information</h4>
                            </div>
                            <div class="card-body">
                                @include('projects.partials.Edit.general_info')
                            </div>
                        </div>

                        <!-- Key Information Section -->
                        @include('projects.partials.Edit.key_information')

                        <!-- Conditional Sections Based on Project Type -->
                        @if ($project->project_type === 'Development Projects')
                            @include('projects.partials.Edit.RST.beneficiaries_area')
                        @elseif ($project->project_type === 'CHILD CARE INSTITUTION')
                            @include('projects.partials.Edit.CCI.rationale')
                            @include('projects.partials.Edit.CCI.statistics')
                            @include('projects.partials.Edit.CCI.annexed_target_group')
                            @include('projects.partials.Edit.CCI.age_profile')
                            @include('projects.partials.Edit.CCI.personal_situation')
                            @include('projects.partials.Edit.CCI.economic_background')
                            @include('projects.partials.Edit.CCI.achievements')
                            @include('projects.partials.Edit.CCI.present_situation')
                        @elseif ($project->project_type === 'Residential Skill Training Proposal 2')
                            @include('projects.partials.Edit.RST.beneficiaries_area')
                            @include('projects.partials.Edit.RST.institution_info')
                            @include('projects.partials.Edit.RST.target_group')
                            @include('projects.partials.Edit.RST.target_group_annexure')
                            @include('projects.partials.Edit.RST.geographical_area')
                        @elseif ($project->project_type === 'Rural-Urban-Tribal')
                            @include('projects.partials.Edit.Edu-RUT.basic_info')
                            @include('projects.partials.Edit.Edu-RUT.target_group')
                            @include('projects.partials.Edit.Edu-RUT.annexed_target_group')

                        @elseif ($project->project_type === 'Individual - Ongoing Educational support')
                            @include('projects.partials.Edit.IES.personal_info')
                            @include('projects.partials.Edit.IES.family_working_members')
                            @include('projects.partials.Edit.IES.immediate_family_details')
                            @include('projects.partials.Edit.IES.educational_background')
                            @include('projects.partials.Edit.IES.estimated_expenses', ['IESExpenses' => $IESExpenses])
                            @include('projects.partials.Edit.IES.attachments')

                        @elseif ($project->project_type === 'Individual - Initial - Educational support')
                            @include('projects.partials.Edit.IIES.personal_info')
                            @include('projects.partials.Edit.IIES.family_working_members')
                            @include('projects.partials.Edit.IIES.immediate_family_details')
                            @include('projects.partials.Edit.IIES.education_background')
                            @include('projects.partials.Edit.IIES.scope_financial_support')
                            @include('projects.partials.Edit.IIES.estimated_expenses', ['iiesExpenses' => $iiesExpenses])
                            @include('projects.partials.Edit.IIES.attachments')

                        @elseif ($project->project_type === 'Individual - Livelihood Application')
                            @include('projects.partials.Edit.ILP.personal_info', ['personalInfo' => $ILPPersonalInfo])
                            @include('projects.partials.Edit.ILP.revenue_goals', ['revenueGoals' => $ILPRevenueGoals])
                            @include('projects.partials.Edit.ILP.strength_weakness', ['strengths' => $ILPStrengthWeakness['strengths'] ?? [], 'weaknesses' => $ILPStrengthWeakness['weaknesses'] ?? []])
                            @include('projects.partials.Edit.ILP.risk_analysis', ['riskAnalysis' => $ILPRiskAnalysis])
                            @include('projects.partials.Edit.ILP.attached_docs', ['attachedDocs' => $ILPAttachedDocuments])
                            @include('projects.partials.Edit.ILP.budget', ['budgets' => $ILPBudget['budgets'], 'total_amount' => $ILPBudget['total_amount'], 'beneficiary_contribution' => $ILPBudget['beneficiary_contribution'], 'amount_requested' => $ILPBudget['amount_requested']])

                        @elseif ($project->project_type === 'Individual - Access to Health')
                            @include('projects.partials.Edit.IAH.personal_info')
                            @include('projects.partials.Edit.IAH.health_conditions')
                            @include('projects.partials.Edit.IAH.earning_members')
                            @include('projects.partials.Edit.IAH.support_details')
                            @include('projects.partials.Edit.IAH.budget_details')
                            @include('projects.partials.Edit.IAH.documents')

                        @elseif ($project->project_type === 'Institutional Ongoing Group Educational proposal')
                            @include('projects.partials.Edit.IGE.institution_info')
                            @include('projects.partials.Edit.IGE.beneficiaries_supported')
                            @include('projects.partials.Edit.IGE.ongoing_beneficiaries')
                            @include('projects.partials.Edit.IGE.new_beneficiaries')
                            @include('projects.partials.Edit.IGE.budget')
                            @include('projects.partials.Edit.IGE.development_monitoring')

                        @elseif ($project->project_type === 'Livelihood Development Projects')
                            @include('projects.partials.Edit.LDP.need_analysis')
                            @include('projects.partials.Edit.LDP.intervention_logic')

                        @elseif ($project->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER')
                            @include('projects.partials.Edit.CIC.basic_info')

                        @endif

                        <!-- Default Partial Sections -->
                        @if (!in_array($project->project_type, [
                            'Individual - Ongoing Educational support',
                            'Individual - Livelihood Application',
                            'Individual - Access to Health',
                            'Individual - Initial - Educational support',
                        ]))
                            @include('projects.partials.Edit.logical_framework')
                            @include('projects.partials.Edit.sustainibility')
                            @include('projects.partials.Edit.budget')
                            @include('projects.partials.Edit.attachement')
                        @endif

                        <button type="submit" class="btn btn-primary me-2">Update Project</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('projects.partials.scripts-edit')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const projectTypeDropdown = document.getElementById('project_type');

        const sections = {
            iah: document.getElementById('iah-sections'),
            eduRUT: document.getElementById('edu-rut-sections'),
            ldp: document.getElementById('ldp-section'),
            rst: document.getElementById('rst-section'),
            ilp: document.getElementById('ilp-sections'),
        };

        function toggleSections() {
            const projectType = projectTypeDropdown.value;

            Object.values(sections).forEach(section => {
                if (section) section.style.display = 'none';
            });

            if (sections[projectType]) {
                sections[projectType].style.display = 'block';
            }
        }

        toggleSections();
        projectTypeDropdown.addEventListener('change', toggleSections);
    });
</script>

<style>
.readonly-input {
    background-color: #0D1427;
    color: #f4f0f0;
}
.select-input {
    background-color: #112f6b;
    color: #f4f0f0;
}
.readonly-select {
    background-color: #092968;
    color: #f4f0f0;
}
.table th, .table td {
    vertical-align: middle;
    text-align: center;
    padding: 0;
}
.table th {
    white-space: normal;
}
.table td input {
    width: 100%;
    box-sizing: border-box;
    padding: 0.375rem 0.75rem;
}
.table-container {
    overflow-x: auto;
}
</style>
@endsection
