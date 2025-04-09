{{-- resources/views/projects/Oldprojects/createProjects.blade.php --}}
@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">

            <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3 card">
                    <div class="card-header">
                        <h4 class="fp-text-center1">PROJECT APPLICATION FORM</h4>
                    </div>
                    <div class="card-header">
                        <h4 class="fp-text-margin">General Information</h4>
                    </div>

                    <!-- General Information Fields -->
                    <div class="card-body">
                        @include('projects.partials.general_info')
                    </div>
                </div>

                <!-- Conditionally Include Project Area for Specific Project Types -->
                <div id="project-area-section" style="display:none;">
                    @include('projects.partials.RST.beneficiaries_area')
                </div>

                <!-- Key Information Section -->
                @include('projects.partials.key_information')

                <!-- Residential Skill Training Specific Partials (After Key Information Section) -->
                <div id="rst-section" style="display:none;">
                    @include('projects.partials.RST.beneficiaries_area')
                    @include('projects.partials.RST.institution_info')
                    @include('projects.partials.RST.target_group')
                    @include('projects.partials.RST.target_group_annexure')
                    @include('projects.partials.RST.geographical_area')
                </div>

                <!-- Individual - Ongoing Educational Support Partials  -->
                <div id="ies-sections" style="display:none;">
                    @include('projects.partials.IES.personal_info')
                    @include('projects.partials.IES.family_working_members', ['prefix' => 'ies'])
                    @include('projects.partials.IES.immediate_family_details')
                    @include('projects.partials.IES.educational_background')
                    @include('projects.partials.IES.estimated_expenses')
                    @include('projects.partials.IES.attachments')
                </div>

                <!-- Individual - Initial Educational Support Partials -->
                <div id="iies-sections" style="display:none;">
                    @include('projects.partials.IIES.personal_info')
                    @include('projects.partials.IIES.family_working_members', ['prefix' => 'iies'])
                    @include('projects.partials.IIES.immediate_family_details')
                    @include('projects.partials.IIES.education_background')
                    @include('projects.partials.IIES.scope_financial_support')
                    @include('projects.partials.IIES.estimated_expenses')
                    @include('projects.partials.IIES.attachments')
                </div>

                <!-- Individual - Livelihood Application Partials -->
                <div id="ilp-sections" style="display:none;">
                    @include('projects.partials.ILP.personal_info')
                    @include('projects.partials.ILP.revenue_goals')
                    @include('projects.partials.ILP.strength_weakness')
                    @include('projects.partials.ILP.risk_analysis')
                    @include('projects.partials.ILP.attached_docs')
                    @include('projects.partials.ILP.budget')
                </div>

                <!-- Individual - Access to Health Specific Partials -->
                <div id="iah-sections" style="display:none;">
                    @include('projects.partials.IAH.personal_info')
                    @include('projects.partials.IAH.health_conditions')
                    @include('projects.partials.IAH.earning_members')
                    @include('projects.partials.IAH.support_details')
                    @include('projects.partials.IAH.budget_details')
                    @include('projects.partials.IAH.documents')
                </div>

                <!-- Institutional Ongoing Group Educational Specific Partials -->
                <div id="ige-sections" style="display:none;">
                    @include('projects.partials.IGE.institution_info')
                    @include('projects.partials.IGE.beneficiaries_supported')
                    @include('projects.partials.IGE.ongoing_beneficiaries')
                    @include('projects.partials.IGE.new_beneficiaries')
                    @include('projects.partials.IGE.budget')
                    @include('projects.partials.IGE.development_monitoring')
                </div>

                <!-- CCI Specific Partials (After Key Information Section) -->
                <div id="cci-section" style="display:none;">
                    @include('projects.partials.CCI.rationale')
                    @include('projects.partials.CCI.statistics')
                    @include('projects.partials.CCI.annexed_target_group')
                    @include('projects.partials.CCI.age_profile')
                    @include('projects.partials.CCI.personal_situation')
                    @include('projects.partials.CCI.economic_background')
                    @include('projects.partials.CCI.achievements')
                    @include('projects.partials.CCI.present_situation')
                </div>

                <!-- CIC Specific Partial (After Key Information Section) -->
                <div id="cic-section" style="display:none;">
                    @include('projects.partials.CIC.basic_info')
                </div>

                <!-- Edu-Rural-Urban-Tribal Specific Partials (After Key Information Section) -->
                <div id="edu-rut-sections" style="display:none;">
                    @include('projects.partials.Edu-RUT.basic_info')
                    @include('projects.partials.Edu-RUT.target_group')
                </div>

                <!-- Livelihood Development Project Specific Partials -->
                <div id="ldp-section" style="display:none;">
                    @include('projects.partials.LDP.need_analysis')
                    @include('projects.partials.LDP.intervention_logic')
                </div>

                <!-- Default Partial Sections -->
                <div id="default-sections">
                    @include('projects.partials.logical_framework')
                    @include('projects.partials.sustainability')
                    @include('projects.partials.budget')
                    @include('projects.partials.attachments')

                    <!-- Annexed Target Group Partial for EduRUT (After Attachments Section) -->
                    <div id="edu-rut-annexed-section" style="display:none;">
                        @include('projects.partials.Edu-RUT.annexed_target_group')
                    </div>
                </div>

                <button type="submit" class="btn btn-primary me-2">Save Project Application</button>
            </form>
        </div>
    </div>
</div>

@include('projects.partials.scripts')

<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectTypeDropdown = document.getElementById('project_type');

    // Get references to all section elements
    const iahSections = document.getElementById('iah-sections');
    const eduRUTSections = document.getElementById('edu-rut-sections');
    const eduRutAnnexedSection = document.getElementById('edu-rut-annexed-section');
    const cicSection = document.getElementById('cic-section');
    const cciSection = document.getElementById('cci-section');
    const ldpSection = document.getElementById('ldp-section');
    const rstSection = document.getElementById('rst-section');
    const igeSections = document.getElementById('ige-sections');
    const iesSections = document.getElementById('ies-sections');
    const iiesSections = document.getElementById('iies-sections');
    const ilpSections = document.getElementById('ilp-sections');
    const defaultSections = document.getElementById('default-sections');
    const projectAreaSection = document.getElementById('project-area-section'); // RST project area partial

    // Create an array of all sections for easy management
    const allSections = [
        iahSections,
        eduRUTSections,
        cicSection,
        cciSection,
        ldpSection,
        rstSection,
        igeSections,
        iesSections,
        iiesSections,
        ilpSections,
        eduRutAnnexedSection,
        projectAreaSection
    ];

    // 1) Utility function to disable inputs in a section
    function disableInputsIn(section) {
        if (!section) return;
        const fields = section.querySelectorAll('input, textarea, select, button');
        fields.forEach(field => field.disabled = true);
    }

    // 2) Utility function to enable inputs in a section
    function enableInputsIn(section) {
        if (!section) return;
        const fields = section.querySelectorAll('input, textarea, select, button');
        fields.forEach(field => field.disabled = false);
    }

    // 3) Hide all sections & disable inputs
    function hideAndDisableAll() {
        allSections.forEach(section => {
            if (section) {
                section.style.display = 'none';
                disableInputsIn(section);
            }
        });
    }

    // 4) Show relevant sections based on project type & enable inputs
    function toggleSections() {
        hideAndDisableAll(); // Hide & disable everything first
        const projectType = projectTypeDropdown.value;

        // Show relevant
        if (projectType === 'Individual - Access to Health') {
            iahSections.style.display = 'block';
            enableInputsIn(iahSections);

        } else if (projectType === 'Individual - Ongoing Educational support') {
            iesSections.style.display = 'block';
            enableInputsIn(iesSections);

        } else if (projectType === 'Individual - Initial - Educational support') {
            iiesSections.style.display = 'block';
            enableInputsIn(iiesSections);

        } else if (projectType === 'Individual - Livelihood Application') {
            ilpSections.style.display = 'block';
            enableInputsIn(ilpSections);

        } else if (projectType === 'Residential Skill Training Proposal 2') {
            rstSection.style.display = 'block';
            enableInputsIn(rstSection);
            projectAreaSection.style.display = 'block';
            enableInputsIn(projectAreaSection);

        } else if (projectType === 'Rural-Urban-Tribal') {
            eduRUTSections.style.display = 'block';
            enableInputsIn(eduRUTSections);
            eduRutAnnexedSection.style.display = 'block';
            enableInputsIn(eduRutAnnexedSection);

        } else if (projectType === 'Development Projects') {
            ldpSection.style.display = 'block';
            enableInputsIn(ldpSection);
            projectAreaSection.style.display = 'block';
            enableInputsIn(projectAreaSection);

        } else if (projectType === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            cicSection.style.display = 'block';
            enableInputsIn(cicSection);

        } else if (projectType === 'CHILD CARE INSTITUTION') {
            cciSection.style.display = 'block';
            enableInputsIn(cciSection);

        } else if (projectType === 'Institutional Ongoing Group Educational proposal') {
            igeSections.style.display = 'block';
            enableInputsIn(igeSections);

        } else if (projectType === 'Livelihood Development Projects') {
            ldpSection.style.display = 'block';
            enableInputsIn(ldpSection);
        }

        // 5) Hide or show default sections
        const projectTypesWithoutDefaultSections = [
            'Individual - Ongoing Educational support',
            'Individual - Livelihood Application',
            'Individual - Access to Health',
            'Individual - Initial - Educational support'
        ];

        if (projectTypesWithoutDefaultSections.includes(projectType)) {
            defaultSections.style.display = 'none';
            disableInputsIn(defaultSections);
        } else {
            defaultSections.style.display = 'block';
            enableInputsIn(defaultSections);
        }
    }

    // 6) Initial check when page loads
    toggleSections();

    // 7) Event listener for dropdown change
    projectTypeDropdown.addEventListener('change', toggleSections);

    // 8) On form submit, ensure only visible sections remain enabled
    const theForm = document.querySelector('form');
    theForm.addEventListener('submit', function() {
        // We already disable hidden sections in toggleSections, but let's ensure again
        hideAndDisableAll();

        // Enable only the currently visible sections
        toggleSections();
        // Now the form will only submit data from enabled (visible) sections
    });
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
