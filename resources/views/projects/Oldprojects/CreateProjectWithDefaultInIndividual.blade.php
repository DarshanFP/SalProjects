{{-- resources/views/projects/Oldprojects/createProjects.blade.php --}}
@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data" >
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

                <!-- Key Information Section -->
                @include('projects.partials.key_information')

                <!-- Residential Skill Training Specific Partials (After Key Information Section) -->
                <div id="rst-section" style="display:none;">
                    @include('projects.partials.RST.institution_info')
                    @include('projects.partials.RST.target_group')
                    @include('projects.partials.RST.target_group_annexure')
                    @include('projects.partials.RST.geographical_area')
                    @include('projects.partials.budget') <!-- Reuse existing budget partial -->
                    <!-- Additional RST partials like attachments can go here if needed -->
                </div>
                <!-- Individual - Ongoing Educational Support Partials -->
                <div id="ies-sections" style="display:none;">
                    @include('projects.partials.IES.personal_info')        <!-- Personal Information -->
                    @include('projects.partials.IES.family_working_members') <!-- Family Working Members -->
                    @include('projects.partials.IES.immediate_family_details') <!-- Immediate Family Details -->
                    @include('projects.partials.IES.educational_background') <!-- Educational Background -->
                    @include('projects.partials.IES.estimated_expenses') <!-- Estimated Expenses -->
                    @include('projects.partials.IES.attachments') <!-- Attachments -->
                </div>
                <!-- Individual - Inital Educational Support Partials -->
                <div id="iies-sections" style="display:none;">
                    @include('projects.partials.IES.personal_info')        <!-- Personal Information -->
                    @include('projects.partials.IES.family_working_members') <!-- Family Working Members -->
                    @include('projects.partials.IES.immediate_family_details') <!-- Immediate Family Details -->
                    @include('projects.partials.IIES.education_background') <!-- Educational Background -->
                    @include('projects.partials.IIES.scope_financial_support') <!-- Estimated Expenses -->
                    @include('projects.partials.IES.attachments') <!-- Attachments -->
                </div>


                <!-- Individual - Livelihood Application Partials -->
                <div id="ilp-sections" style="display:none;">
                    @include('projects.partials.ILP.personal_info') <!-- Personal Information -->
                    @include('projects.partials.ILP.revenue_goals') <!-- Revenue Goals -->
                    @include('projects.partials.ILP.strength_weakness') <!-- Strengths and Weaknesses -->
                    @include('projects.partials.ILP.risk_analysis') <!-- Risk Analysis -->
                    @include('projects.partials.ILP.attached_docs') <!-- Attached Documents -->
                    @include('projects.partials.ILP.budget') <!-- Budget -->
                </div>

                <!-- Individual - Access to Health Specific Partials -->
                <div id="iah-sections" style="display:none;">
                    @include('projects.partials.IAH.personal_info')        <!-- Personal Information -->
                    @include('projects.partials.IAH.health_conditions')     <!-- Health Conditions -->
                    @include('projects.partials.IAH.earning_members')       <!-- Earning Members -->
                    @include('projects.partials.IAH.support_details')       <!-- Support Details -->
                    @include('projects.partials.IAH.budget_details')        <!-- Budget Details -->
                    @include('projects.partials.IAH.documents')             <!-- Document Attachments -->
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
                    <!-- Basic Information Partial for EduRUT -->
                    @include('projects.partials.Edu-RUT.basic_info')

                    <!-- Target Group Partial for EduRUT -->
                    @include('projects.partials.Edu-RUT.target_group')
                </div>

                <!-- Livelihood Development Project Specific Partials -->
                <div id="ldp-section" style="display:none;">
                    <!-- Need Analysis Partial for LDP -->
                    @include('projects.partials.LDP.need_analysis')

                    <!-- Target Group Partial for LDP -->
                    @include('projects.partials.LDP.target_group')


                    <!-- Intervention Logic Partial for LDP -->
                    @include('projects.partials.LDP.intervention_logic')
                </div>


                <!-- Default Partial Sections -->
                <div id="default-sections">
                    <!-- Logical Framework Section -->
                    @include('projects.partials.logical_framework')

                    <!-- Project Sustainability, Monitoring, and Evaluation Framework -->
                    @include('projects.partials.sustainability')

                    <!-- Budget Section -->
                    @include('projects.partials.budget')

                    <!-- Attachments Section -->
                    @include('projects.partials.attachments')

                    <!-- Annexed Target Group Partial for EduRUT (After Attachments Section) -->
                    <div id="edu-rut-annexed-section" style="display:none;">
                        @include('projects.partials.Edu-RUT.annexed_target_group')
                    </div>
                </div>

                <button type="submit" class="btn btn-primary me-2">Submit Application</button>
            </form>
        </div>
    </div>
</div>

@include('projects.partials.scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const projectTypeDropdown = document.getElementById('project_type');

    // Get references to all section elements
    const iahSections = document.getElementById('iah-sections'); // IAH Specific Sections
    const eduRUTSections = document.getElementById('edu-rut-sections');
    const cicSection = document.getElementById('cic-section');
    const cciSection = document.getElementById('cci-section');
    const ldpSection = document.getElementById('ldp-section');
    const rstSection = document.getElementById('rst-section');
    const igeSections = document.getElementById('ige-sections');
    const iesSections = document.getElementById('ies-sections');
    const iiesSections = document.getElementById('iies-sections');
    const ilpSections = document.getElementById('ilp-sections');
    const defaultSections = document.getElementById('default-sections');

    // Create an array of all sections for easy management
    const allSections = [
        iahSections, // Add IAH to the list of sections
        eduRUTSections,
        cicSection,
        cciSection,
        ldpSection,
        rstSection,
        igeSections,
        iesSections,
        iiesSections,
        ilpSections
    ];

    function toggleSections() {
        const projectType = projectTypeDropdown.value;

        // Hide all sections
        allSections.forEach(section => {
            if (section) {
                section.style.display = 'none';
            }
        });

        // Ensure default sections are always displayed (if needed)
        if (defaultSections) {
            defaultSections.style.display = 'block';
        }

        // Show only the relevant sections based on project type
        if (projectType === 'Individual - Access to Health') {
            iahSections.style.display = 'block'; // Show IAH sections
        } else if (projectType === 'Individual - Ongoing Educational support') {
            iesSections.style.display = 'block';
        } else if (projectType === 'Residential Skill Training Proposal 2') {
            rstSection.style.display = 'block';
        } else if (projectType === 'Rural-Urban-Tribal') {
            eduRUTSections.style.display = 'block';
        } else if (projectType === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            cicSection.style.display = 'block';
        } else if (projectType === 'CHILD CARE INSTITUTION') {
            cciSection.style.display = 'block';
        } else if (projectType === 'Livelihood Development Projects') {
            ldpSection.style.display = 'block';
        } else if (projectType === 'Institutional Ongoing Group Educational proposal') {
            igeSections.style.display = 'block';
        } else if (projectType === 'Individual - Livelihood Application') {
            ilpSections.style.display = 'block';
        } else if (projectType === 'Individual - Initial - Educational support') {
            iiesSections.style.display = 'block';
        }
    }

    // Initial check when page loads
    toggleSections();

    // Event listener for dropdown change
    projectTypeDropdown.addEventListener('change', toggleSections);
});

</script>


<style>
    /* Styling for input fields and tables */
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

{{-- script befor ILP ADDED
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const projectTypeDropdown = document.getElementById('project_type');

        // Get references to all section elements
        const eduRUTSections = document.getElementById('edu-rut-sections');
        const eduRUTAnnexedSection = document.getElementById('edu-rut-annexed-section');
        const cicSection = document.getElementById('cic-section');
        const cciSection = document.getElementById('cci-section');
        const ldpSection = document.getElementById('ldp-section');
        const rstSection = document.getElementById('rst-section');
        const igeSections = document.getElementById('ige-sections');
        const iesSections = document.getElementById('ies-sections');
        const ilpSections = document.getElementById('ilp-sections'); // New ILP section

        const defaultSections = document.getElementById('default-sections');

        // Create an array of all sections for easy management
        const allSections = [
            eduRUTSections,
            eduRUTAnnexedSection,
            cicSection,
            cciSection,
            ldpSection,
            rstSection,
            igeSections,
            iesSections,
            ilpSections // Add ILP to the list of sections

        ];

        function toggleSections() {
            const projectType = projectTypeDropdown.value;

            // Hide all sections
            allSections.forEach(section => {
                if (section) {
                    section.style.display = 'none';
                }
            });

            // Ensure default sections are always displayed (if needed)
            if (defaultSections) {
                defaultSections.style.display = 'block';
            }

            // Show only the relevant sections based on project type
            if (projectType === 'Individual - Ongoing Educational support') {
                iesSections.style.display = 'block';
            } else if (projectType === 'Residential Skill Training Proposal 2') {
                rstSection.style.display = 'block';
            } else if (projectType === 'Rural-Urban-Tribal') {
                eduRUTSections.style.display = 'block';
                eduRUTAnnexedSection.style.display = 'block';
            } else if (projectType === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
                cicSection.style.display = 'block';
            } else if (projectType === 'CHILD CARE INSTITUTION') {
                cciSection.style.display = 'block';
            } else if (projectType === 'Livelihood Development Projects') {
                ldpSection.style.display = 'block';
            } else if (projectType === 'Institutional Ongoing Group Educational proposal') {
                igeSections.style.display = 'block';
            }
            // If you have a default case or need to handle other project types, you can add more conditions
        }

        // Initial check when page loads
        toggleSections();

        // Event listener for dropdown change
        projectTypeDropdown.addEventListener('change', toggleSections);
    });
</script>
 Script befor Individual Education added
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const projectTypeDropdown = document.getElementById('project_type');
        const eduRUTSections = document.getElementById('edu-rut-sections');
        const eduRUTAnnexedSection = document.getElementById('edu-rut-annexed-section');
        const cicSection = document.getElementById('cic-section');
        const cciSection = document.getElementById('cci-section'); // Added CCI section
        const ldpSection = document.getElementById('ldp-section'); // Added LDP section
        const rstSection = document.getElementById('rst-section'); // Residential Skill Training section
        const igeSections = document.getElementById('ige-sections'); // IGE section
        const iesSections = document.getElementById('ies-sections'); // IES section

        function toggleSections() {
            const projectType = projectTypeDropdown.value;

            if (projectType === 'Individual - Ongoing Educational support') {
                iesSections.style.display = 'block';
            } else if (projectType === 'Residential Skill Training Proposal 2') {
                rstSection.style.display = 'block';
                eduRUTSections.style.display = 'none';
                cicSection.style.display = 'none';
                cciSection.style.display = 'none';
            } else if (projectType === 'Rural-Urban-Tribal') {
                eduRUTSections.style.display = 'block';
                eduRUTAnnexedSection.style.display = 'block';
                cicSection.style.display = 'none';
                cciSection.style.display = 'none';
            } else if (projectType === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
                cicSection.style.display = 'block';
                eduRUTSections.style.display = 'none';
                eduRUTAnnexedSection.style.display = 'none';
                cciSection.style.display = 'none';
            } else if (projectType === 'CHILD CARE INSTITUTION') {
                cciSection.style.display = 'block';
                eduRUTSections.style.display = 'none';
                eduRUTAnnexedSection.style.display = 'none';
                cicSection.style.display = 'none';
            } else if (projectType === 'Livelihood Development Projects') {
                ldpSection.style.display = 'block';
                eduRUTSections.style.display = 'none';
                eduRUTAnnexedSection.style.display = 'none';
                cicSection.style.display = 'none';
                cciSection.style.display = 'none';
            } else if (projectType === 'Institutional Ongoing Group Educational proposal') {
                igeSections.style.display = 'block'; // Show IGE section
                eduRUTSections.style.display = 'none';
                eduRUTAnnexedSection.style.display = 'none';
                cicSection.style.display = 'none';
                cciSection.style.display = 'none'; // Hide other sections
            } else {
                // Hide all specific sections
                eduRUTSections.style.display = 'none';
                eduRUTAnnexedSection.style.display = 'none';
                cicSection.style.display = 'none';
                cciSection.style.display = 'none';
            }
        }

        // Initial check when page loads
        toggleSections();

        // Event listener for dropdown change
        projectTypeDropdown.addEventListener('change', toggleSections);
    });
</script> --}}

{{-- <script>
    document.addEventListener('DOMContentLoaded', function() {
        const projectTypeDropdown = document.getElementById('project_type');
        const eduRUTSections = document.getElementById('edu-rut-sections');
        const eduRUTAnnexedSection = document.getElementById('edu-rut-annexed-section');
        const cicSection = document.getElementById('cic-section');

        function toggleSections() {
            const projectType = projectTypeDropdown.value;

            if (projectType === 'Rural-Urban-Tribal') {
                eduRUTSections.style.display = 'block';
                eduRUTAnnexedSection.style.display = 'block';
                cicSection.style.display = 'none';
            } else if (projectType === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
                cicSection.style.display = 'block';
                eduRUTSections.style.display = 'none';
                eduRUTAnnexedSection.style.display = 'none';
            } else {
                // Hide both Edu-RUT and CIC sections if other project types
                eduRUTSections.style.display = 'none';
                eduRUTAnnexedSection.style.display = 'none';
                cicSection.style.display = 'none';
            }
        }

        // Initial check when page loads
        toggleSections();

        // Event listener for dropdown change
        projectTypeDropdown.addEventListener('change', toggleSections);
    });
</script> --}}


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
                    @include('projects.partials.RST.project_area')
                </div>
                @include('projects.partials.key_information')

                <!-- Residential Skill Training Specific Partials (After Key Information Section) -->
                 <div id="rst-section" style="display:none;">
                    @include('projects.partials.RST.institution_info')
                    @include('projects.partials.RST.target_group')
                    @include('projects.partials.RST.target_group_annexure')
                    @include('projects.partials.RST.geographical_area')
                    @include('projects.partials.budget') <!-- Reuse existing budget partial -->
                    <!-- Additional RST partials like attachments can go here if needed -->
                </div>

                <!-- Individual - Ongoing Educational Support Partials -->
                <div id="ies-sections" style="display:none;">
                    @include('projects.partials.IES.personal_info')           <!-- Personal Information -->
                    @include('projects.partials.IES.family_working_members')  <!-- Family Working Members -->
                    @include('projects.partials.IES.immediate_family_details')<!-- Immediate Family Details -->
                    @include('projects.partials.IES.educational_background')  <!-- Educational Background -->
                    @include('projects.partials.IES.estimated_expenses')      <!-- Estimated Expenses -->
                    @include('projects.partials.IES.attachments')             <!-- Attachments -->
                </div>

                <!-- Individual - Initial Educational Support Partials -->
                <div id="iies-sections" style="display:none;">
                    @include('projects.partials.IES.personal_info')             <!-- Personal Information -->
                    @include('projects.partials.IES.family_working_members')    <!-- Family Working Members -->
                    @include('projects.partials.IES.immediate_family_details')  <!-- Immediate Family Details -->
                    @include('projects.partials.IIES.education_background')     <!-- Educational Background -->
                    @include('projects.partials.IIES.scope_financial_support')  <!-- Scope of Financial Support -->
                    @include('projects.partials.IES.attachments')               <!-- Attachments -->
                </div>

                <!-- Individual - Livelihood Application Partials -->
                <div id="ilp-sections" style="display:none;">
                    @include('projects.partials.ILP.personal_info')     <!-- Personal Information -->
                    @include('projects.partials.ILP.revenue_goals')     <!-- Revenue Goals -->
                    @include('projects.partials.ILP.strength_weakness') <!-- Strengths and Weaknesses -->
                    @include('projects.partials.ILP.risk_analysis')     <!-- Risk Analysis -->
                    @include('projects.partials.ILP.attached_docs')     <!-- Attached Documents -->
                    @include('projects.partials.ILP.budget')            <!-- Budget -->
                </div>

                <!-- Individual - Access to Health Specific Partials -->
                <div id="iah-sections" style="display:none;">
                    @include('projects.partials.IAH.personal_info')         <!-- Personal Information -->
                    @include('projects.partials.IAH.health_conditions')     <!-- Health Conditions -->
                    @include('projects.partials.IAH.earning_members')       <!-- Earning Members -->
                    @include('projects.partials.IAH.support_details')       <!-- Support Details -->
                    @include('projects.partials.IAH.budget_details')        <!-- Budget Details -->
                    @include('projects.partials.IAH.documents')             <!-- Document Attachments -->
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
                    <!-- Basic Information Partial for EduRUT -->
                    @include('projects.partials.Edu-RUT.basic_info')

                    <!-- Target Group Partial for EduRUT -->
                    @include('projects.partials.Edu-RUT.target_group')
                </div>

                <!-- Livelihood Development Project Specific Partials -->
                <div id="ldp-section" style="display:none;">
                    <!-- Need Analysis Partial for LDP -->
                    @include('projects.partials.LDP.need_analysis')

                    <!-- Target Group Partial for LDP -->
                    @include('projects.partials.LDP.target_group')

                    <!-- Intervention Logic Partial for LDP -->
                    @include('projects.partials.LDP.intervention_logic')
                </div>

                <!-- Default Partial Sections -->
                <div id="default-sections">
                    <!-- Logical Framework Section -->
                    @include('projects.partials.logical_framework')

                    <!-- Project Sustainability, Monitoring, and Evaluation Framework -->
                    @include('projects.partials.sustainability')

                    <!-- Budget Section -->
                    @include('projects.partials.budget')

                    <!-- Attachments Section -->
                    @include('projects.partials.attachments')

                    <!-- Annexed Target Group Partial for EduRUT (After Attachments Section) -->
                    <div id="edu-rut-annexed-section" style="display:none;">
                        @include('projects.partials.Edu-RUT.annexed_target_group')
                    </div>
                </div>

                <button type="submit" class="btn btn-primary me-2">Submit Application</button>
            </form>
        </div>
    </div>
</div>

@include('projects.partials.scripts')
document.addEventListener('DOMContentLoaded', function() {
    const projectTypeDropdown = document.getElementById('project_type');

    // Get references to all section elements
    const iahSections = document.getElementById('iah-sections'); // IAH Specific Sections
    const eduRUTSections = document.getElementById('edu-rut-sections');
    const eduRutAnnexedSection = document.getElementById('edu-rut-annexed-section'); // EduRUT annexed section
    const cicSection = document.getElementById('cic-section');
    const cciSection = document.getElementById('cci-section');
    const ldpSection = document.getElementById('ldp-section');
    const rstSection = document.getElementById('rst-section');
    const igeSections = document.getElementById('ige-sections');
    const iesSections = document.getElementById('ies-sections');
    const iiesSections = document.getElementById('iies-sections');
    const ilpSections = document.getElementById('ilp-sections');
    const defaultSections = document.getElementById('default-sections');
    const projectAreaSection = document.getElementById('project-area-section'); // Project Area Section

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
        eduRutAnnexedSection, // Add annexed section to the list
        projectAreaSection // Add project area section
    ];

    function toggleSections() {
        const projectType = projectTypeDropdown.value;

        // Hide all sections
        allSections.forEach(section => {
            if (section) {
                section.style.display = 'none';
            }
        });

        // Create an array of project types that should not display default sections
        const projectTypesWithoutDefaultSections = [
            'Individual - Ongoing Educational support',
            'Individual - Livelihood Application',
            'Individual - Access to Health',
            'Individual - Initial - Educational support'
        ];

        // Show only the relevant sections based on project type
        if (projectType === 'Individual - Access to Health') {
            iahSections.style.display = 'block'; // Show IAH sections
        } else if (projectType === 'Individual - Ongoing Educational support') {
            iesSections.style.display = 'block';
        } else if (projectType === 'Individual - Initial - Educational support') {
            iiesSections.style.display = 'block';
        } else if (projectType === 'Individual - Livelihood Application') {
            ilpSections.style.display = 'block';
        } else if (projectType === 'Residential Skill Training Proposal 2') {
            rstSection.style.display = 'block';
            projectAreaSection.style.display = 'block'; // Show project area section
        } else if (projectType === 'Rural-Urban-Tribal') {
            eduRUTSections.style.display = 'block';
            if (eduRutAnnexedSection) {
                eduRutAnnexedSection.style.display = 'block';
            }
        } else if (projectType === 'Development Project') {
            ldpSection.style.display = 'block';
            projectAreaSection.style.display = 'block'; // Show project area section
        } else if (projectType === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            cicSection.style.display = 'block';
        } else if (projectType === 'CHILD CARE INSTITUTION') {
            cciSection.style.display = 'block';
        } else if (projectType === 'Livelihood Development Projects') {
            ldpSection.style.display = 'block';
        } else if (projectType === 'Institutional Ongoing Group Educational proposal') {
            igeSections.style.display = 'block';
        }

        // Show or hide default sections based on project type
        if (projectTypesWithoutDefaultSections.includes(projectType)) {
            if (defaultSections) {
                defaultSections.style.display = 'none';
            }
        } else {
            if (defaultSections) {
                defaultSections.style.display = 'block';
            }
        }
    }

    // Initial check when page loads
    toggleSections();

    // Event listener for dropdown change
    projectTypeDropdown.addEventListener('change', toggleSections);
});
{{--
<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectTypeDropdown = document.getElementById('project_type');

    // Get references to all section elements
    const iahSections = document.getElementById('iah-sections'); // IAH Specific Sections
    const eduRUTSections = document.getElementById('edu-rut-sections');
    const eduRutAnnexedSection = document.getElementById('edu-rut-annexed-section'); // EduRUT annexed section
    const cicSection = document.getElementById('cic-section');
    const cciSection = document.getElementById('cci-section');
    const ldpSection = document.getElementById('ldp-section');
    const rstSection = document.getElementById('rst-section');
    const igeSections = document.getElementById('ige-sections');
    const iesSections = document.getElementById('ies-sections');
    const iiesSections = document.getElementById('iies-sections');
    const ilpSections = document.getElementById('ilp-sections');
    const defaultSections = document.getElementById('default-sections');
    const projectAreaSection = document.getElementById('project-area-section'); // Project Area Section


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
        eduRutAnnexedSection, // Add annexed section to the list
        projectAreaSection // Add project area section

    ];

    function toggleSections() {
        const projectType = projectTypeDropdown.value;

        // Hide all sections
        allSections.forEach(section => {
            if (section) {
                section.style.display = 'none';
            }
        });

        // Create an array of project types that should not display default sections
        const projectTypesWithoutDefaultSections = [
            'Individual - Ongoing Educational support',
            'Individual - Livelihood Application',
            'Individual - Access to Health',
            'Individual - Initial - Educational support'
        ];

        // Show only the relevant sections based on project type
        if (projectType === 'Individual - Access to Health') {
            iahSections.style.display = 'block'; // Show IAH sections
        } else if (projectType === 'Individual - Ongoing Educational support') {
            iesSections.style.display = 'block';
        } else if (projectType === 'Individual - Initial - Educational support') {
            iiesSections.style.display = 'block';
        } else if (projectType === 'Individual - Livelihood Application') {
            ilpSections.style.display = 'block';
        }
        else if (projectType === 'Residential Skill Training Proposal 2') {
            rstSection.style.display = 'block';
        }
        else if (projectType === 'Residential Skill Training Proposal 2') {
            rstSection.style.display = 'block';
            projectAreaSection.style.display = 'block'; // Show project area section
        }
        else if (projectType === 'Rural-Urban-Tribal') {
            eduRUTSections.style.display = 'block';
            // Show the annexed Edu-RUT section
            if (eduRutAnnexedSection) {
                eduRutAnnexedSection.style.display = 'block';
            }
        }
        else if (projectType === 'Development Project') {
            ldpSection.style.display = 'block';
            projectAreaSection.style.display = 'block'; // Show project area section
        }
         else if (projectType === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            cicSection.style.display = 'block';
        } else if (projectType === 'CHILD CARE INSTITUTION') {
            cciSection.style.display = 'block';
        } else if (projectType === 'Livelihood Development Projects') {
            ldpSection.style.display = 'block';
        } else if (projectType === 'Institutional Ongoing Group Educational proposal') {
            igeSections.style.display = 'block';
        }

        // Show or hide default sections based on project type
        if (projectTypesWithoutDefaultSections.includes(projectType)) {
            // Hide default sections
            if (defaultSections) {
                defaultSections.style.display = 'none';
            }
        } else {
            // Show default sections
            if (defaultSections) {
                defaultSections.style.display = 'block';
            }
        }
    }

    // Initial check when page loads
    toggleSections();

    // Event listener for dropdown change
    projectTypeDropdown.addEventListener('change', toggleSections);
});
</script> --}}

<style>
    /* Styling for input fields and tables */
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
