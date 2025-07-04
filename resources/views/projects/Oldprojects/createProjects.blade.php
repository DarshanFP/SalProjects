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
                    <div class="card-body">
                        @include('projects.partials.general_info')
                    </div>
                </div>

                <!-- Predecessor Data for NEXT PHASE - DEVELOPMENT PROPOSAL -->
                @if(!empty($predecessorBeneficiaries) && request()->input('project_type') === 'NEXT PHASE - DEVELOPMENT PROPOSAL')
                    <div class="mb-3 card" id="predecessor-section">
                        <div class="card-header">
                            <h4>Predecessor Beneficiaries (Reference)</h4>
                        </div>
                        <div class="card-body">
                            @include('projects.partials.RST.beneficiaries_area', ['beneficiaries' => $predecessorBeneficiaries, 'readonly' => true])
                        </div>
                    </div>
                @endif

                <!-- Key Information Section (Always Visible) -->
                @include('projects.partials.key_information')

                <!-- Project Area Section (Development Projects and NEXT PHASE) -->
                <div id="project-area-section" style="display:none;">
                    @php
                        $initialBeneficiaries = (!empty($predecessorBeneficiaries) && request()->input('project_type') === 'NEXT PHASE - DEVELOPMENT PROPOSAL') ? $predecessorBeneficiaries : [];
                    @endphp
                    @include('projects.partials.RST.beneficiaries_area', ['beneficiaries' => $initialBeneficiaries])
                </div>

                <!-- Residential Skill Training Specific Partials -->
                <div id="rst-section" style="display:none;">
                    @include('projects.partials.RST.institution_info')
                    @include('projects.partials.RST.target_group')
                    @include('projects.partials.RST.target_group_annexure')
                    @include('projects.partials.RST.geographical_area')
                </div>

                <!-- Individual - Ongoing Educational Support Partials -->
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

                <!-- CCI Specific Partials -->
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

                <!-- CIC Specific Partial -->
                <div id="cic-section" style="display:none;">
                    @include('projects.partials.CIC.basic_info')
                </div>

                <!-- Edu-Rural-Urban-Tribal Specific Partials -->
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
                    @include('projects.partials.logical_framework', ['predecessorObjectives' => $predecessorObjectives])
                    @include('projects.partials.sustainability')
                    @include('projects.partials.budget')
                    @include('projects.partials.attachments')
                    <div id="edu-rut-annexed-section" style="display:none;">
                        @include('projects.partials.Edu-RUT.annexed_target_group')
                    </div>
                </div>

                <button type="submit" class="btn btn-primary me-2">Save Project Application</button>
            </form>
        </div>
    </div>
</div>

<!-- Templates for Logical Framework -->
<div id="activity-template" style="display: none;">
    <tr class="activity-row">
        <td>
            <textarea name="objectives[0][activities][0][activity]" class="form-control activity-description" rows="2" placeholder="Enter Activity" style="background-color: #202ba3;"></textarea>
        </td>
        <td>
            <textarea name="objectives[0][activities][0][verification]" class="form-control activity-verification" rows="2" placeholder="Means of Verification" style="background-color: #202ba3;"></textarea>
        </td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeActivity(this)">Remove</button></td>
    </tr>
</div>

<div id="timeframe-template" style="display: none;">
    <tr class="activity-timeframe-row">
        <td class="activity-description-text">
            <textarea name="objectives[0][activities][0][timeframe][description]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea>
        </td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][1]"></td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][2]"></td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][3]"></td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][4]"></td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][5]"></td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][6]"></td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][7]"></td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][8]"></td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][9]"></td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][10]"></td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][11]"></td>
        <td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name="objectives[0][activities][0][timeframe][months][12]"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeTimeFrameRow(this)">Remove</button></td>
    </tr>
</div>

@include('projects.partials.scripts')

<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectTypeDropdown = document.getElementById('project_type');
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
    const projectAreaSection = document.getElementById('project-area-section');
    const predecessorSection = document.getElementById('predecessor-section');

    const allSections = [
        iahSections, eduRUTSections, cicSection, cciSection, ldpSection, rstSection,
        igeSections, iesSections, iiesSections, ilpSections, eduRutAnnexedSection,
        projectAreaSection, predecessorSection
    ];

    function disableInputsIn(section) {
        if (!section) return;
        const fields = section.querySelectorAll('input, textarea, select, button');
        fields.forEach(field => field.disabled = true);
    }

    function enableInputsIn(section) {
        if (!section) return;
        const fields = section.querySelectorAll('input, textarea, select, button');
        fields.forEach(field => field.disabled = false);
    }

    function hideAndDisableAll() {
        allSections.forEach(section => {
            if (section) {
                section.style.display = 'none';
                disableInputsIn(section);
            }
        });
    }

    function toggleSections() {
        hideAndDisableAll();
        const projectType = projectTypeDropdown.value;

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
        } else if (projectType === 'Development Projects' || projectType === 'NEXT PHASE - DEVELOPMENT PROPOSAL') {
            projectAreaSection.style.display = 'block';
            enableInputsIn(projectAreaSection);
            if (projectType === 'NEXT PHASE - DEVELOPMENT PROPOSAL' && predecessorSection) {
                predecessorSection.style.display = 'block';
                enableInputsIn(predecessorSection);
            }
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

        const projectTypesWithoutDefaultSections = [
            'Individual - Ongoing Educational support',
            'Individual - Livelihood Application',
            'Individual - Access to Health',
            'Individual - Initial - Educational support'
        ];
        defaultSections.style.display = projectTypesWithoutDefaultSections.includes(projectType) ? 'none' : 'block';
        if (!projectTypesWithoutDefaultSections.includes(projectType)) {
            enableInputsIn(defaultSections);
        } else {
            disableInputsIn(defaultSections);
        }
    }

    // Handle predecessor data fetched event
    document.addEventListener('predecessorDataFetched', function(event) {
        const beneficiaries = event.detail.beneficiaries_areas || [];
        const objectives = event.detail.objectives || [];
        const tbody = document.getElementById('RST-project-area-rows');
        if (tbody && projectTypeDropdown.value === 'NEXT PHASE - DEVELOPMENT PROPOSAL') {
            tbody.innerHTML = '';
            window.RSTprojectAreaRowIndex = beneficiaries.length || 1;
            if (beneficiaries.length > 0) {
                beneficiaries.forEach(b => {
                    const row = `
                        <tr>
                            <td><input type="text" name="project_area[]" class="form-control" value="${b.project_area || ''}" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="category_beneficiary[]" class="form-control" value="${b.category || ''}" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="direct_beneficiaries[]" class="form-control" value="${b.direct || 0}" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="indirect_beneficiaries[]" class="form-control" value="${b.indirect || 0}" style="background-color: #202ba3;"></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
                        </tr>
                    `;
                    tbody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                addRSTProjectAreaRow();
            }
        }

        // Populate goal field
        const goalTextarea = document.querySelector('textarea[name="goal"]');
        if (goalTextarea && event.detail.goal) {
            goalTextarea.value = event.detail.goal;
        }

        // Enhance logical framework with predecessor data (sync activities and timeframes)
        if (objectives.length > 0 && projectTypeDropdown.value === 'NEXT PHASE - DEVELOPMENT PROPOSAL') {
            const container = document.getElementById('objectives-container');
            console.log('Container found:', container);
            const objectiveCards = container.querySelectorAll('.objective-card:not(#objective-template .objective-card)');
            console.log('Objective cards found:', objectiveCards.length);

            objectiveCards.forEach((objectiveCard, index) => {
                if (index < objectives.length) {
                    const obj = objectives[index];
                    objectiveCard.querySelector('textarea.objective-description').value = obj.objective || '';

                    // Populate results
                    const resultsContainer = objectiveCard.querySelector('.results-container');
                    const defaultResult = resultsContainer.querySelector('.result-section');
                    if (defaultResult) defaultResult.remove();
                    obj.results.forEach((result, rIndex) => {
                        addResult(resultsContainer.querySelector('button[onclick="addResult(this)"]'));
                        const resultSection = resultsContainer.querySelectorAll('.result-section')[rIndex];
                        if (resultSection) {
                            resultSection.querySelector('textarea.result-outcome').value = result.result || '';
                        }
                    });

                    // Populate risks
                    const risksContainer = objectiveCard.querySelector('.risks-container');
                    const defaultRisk = risksContainer.querySelector('.risk-section');
                    if (defaultRisk) defaultRisk.remove();
                    obj.risks.forEach((risk, rIndex) => {
                        addRisk(risksContainer.querySelector('button[onclick="addRisk(this)"]'));
                        const riskSection = risksContainer.querySelectorAll('.risk-section')[rIndex];
                        if (riskSection) {
                            riskSection.querySelector('textarea.risk-description').value = risk.risk || '';
                        }
                    });

                    // Sync activities and timeframes
                    const activitiesTable = objectiveCard.querySelector('.activities-table tbody');
                    const timeFrameTable = objectiveCard.querySelector('.time-frame-card tbody');
                    const existingActivities = activitiesTable.querySelectorAll('.activity-row');
                    const existingTimeframes = timeFrameTable.querySelectorAll('.activity-timeframe-row');

                    // Remove excess rows if any
                    while (existingActivities.length > obj.activities.length) {
                        existingActivities[existingActivities.length - 1].remove();
                        existingTimeframes[existingTimeframes.length - 1].remove();
                    }

                    // Add or update rows
                    obj.activities.forEach((activity, aIndex) => {
                        let activityRow = existingActivities[aIndex];
                        let timeFrameRow = existingTimeframes[aIndex];

                        if (!activityRow) {
                            addActivity(objectiveCard.querySelector('button[onclick="addActivity(this)"]'));
                            activityRow = activitiesTable.querySelectorAll('.activity-row')[aIndex];
                            timeFrameRow = timeFrameTable.querySelectorAll('.activity-timeframe-row')[aIndex];
                        }

                        const activityDesc = activityRow.querySelector('textarea.activity-description');
                        const activityVerif = activityRow.querySelector('textarea.activity-verification');
                        const timeFrameDesc = timeFrameRow.querySelector('textarea');

                        activityDesc.value = activity.activity || '';
                        activityVerif.value = activity.verification || '';
                        timeFrameDesc.value = activity.activity || '';

                        activity.timeframes.forEach(tf => {
                            const monthCheckbox = timeFrameRow.querySelector(`input[name="objectives[${index}][activities][${aIndex}][timeframe][months][${tf.month}]"]`);
                            if (monthCheckbox && tf.is_active) {
                                monthCheckbox.checked = true;
                            }
                        });
                    });

                    attachActivityEventListeners(objectiveCard);
                    updateNameAttributes(objectiveCard, index);
                }
            });

            updateObjectiveNumbers();
            console.log('Final objective count:', window.objectiveCount);
        }
    });

    toggleSections();
    projectTypeDropdown.addEventListener('change', toggleSections);

    const theForm = document.querySelector('form');
    theForm.addEventListener('submit', function() {
        hideAndDisableAll();
        toggleSections();
    });
});
</script>

<style>
    .readonly-input { background-color: #0D1427; color: #f4f0f0; }
    .select-input { background-color: #112f6b; color: #f4f0f0; }
    .readonly-select { background-color: #092968; color: #f4f0f0; }
    .table th, .table td { vertical-align: middle; text-align: center; padding: 0; }
    .table th { white-space: normal; }
    .table td input { width: 100%; box-sizing: border-box; padding: 0.375rem 0.75rem; }
    .table-container { overflow-x: auto; }
</style>
@endsection
