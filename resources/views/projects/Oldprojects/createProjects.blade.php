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
<!-- Note: Using cloning approach instead of templates for better reliability -->

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

// Logical Framework Functions
let objectiveCount = 1;

function addObjective() {
    const container = document.getElementById('objectives-container');
    const lastObjectiveCard = container.querySelector('.objective-card:last-of-type');
    const newObjective = lastObjectiveCard.cloneNode(true);

    // Update the objective number
    newObjective.querySelector('h5').innerText = `Objective ${objectiveCount + 1}`;

    // Clear all form values
    resetFormValues(newObjective);

    // Update name attributes
    updateNameAttributes(newObjective, objectiveCount);

    // Insert before the controls
    container.insertBefore(newObjective, container.lastElementChild);

    // Attach event listeners
    attachActivityEventListeners(newObjective);
    objectiveCount++;
}

function resetFormValues(template) {
    template.querySelectorAll('textarea').forEach(textarea => textarea.value = '');
    template.querySelectorAll('input[type="checkbox"]').forEach(checkbox => checkbox.checked = false);
    template.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
}

function removeLastObjective() {
    const objectives = document.querySelectorAll('.objective-card:not(#objective-template .objective-card)');
    if (objectives.length > 1) {
        objectives[objectives.length - 1].remove();
        objectiveCount--;
        updateObjectiveNumbers();
    }
}

function addResult(button) {
    const resultsContainer = button.closest('.results-container');
    const lastResultSection = resultsContainer.querySelector('.result-section:last-of-type');
    const newResult = lastResultSection.cloneNode(true);

    // Clear the textarea value
    newResult.querySelector('textarea.result-outcome').value = '';

    resultsContainer.insertBefore(newResult, button);
    updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
}

function removeResult(button) {
    const resultSection = button.closest('.result-section');
    if (resultSection.parentNode.querySelectorAll('.result-section').length > 1) {
        resultSection.remove();
        updateNameAttributes(resultSection.closest('.objective-card'), getObjectiveIndex(resultSection.closest('.objective-card')));
    }
}

function addRisk(button) {
    const risksContainer = button.closest('.risks-container');
    const lastRiskSection = risksContainer.querySelector('.risk-section:last-of-type');
    const newRisk = lastRiskSection.cloneNode(true);

    // Clear the textarea value
    newRisk.querySelector('textarea.risk-description').value = '';

    risksContainer.insertBefore(newRisk, button);
    updateNameAttributes(risksContainer.closest('.objective-card'), getObjectiveIndex(risksContainer.closest('.objective-card')));
}

function removeRisk(button) {
    const riskSection = button.closest('.risk-section');
    if (riskSection.parentNode.querySelectorAll('.risk-section').length > 1) {
        riskSection.remove();
        updateNameAttributes(riskSection.closest('.objective-card'), getObjectiveIndex(riskSection.closest('.objective-card')));
    }
}

function addActivity(button) {
    const activitiesContainer = button.closest('.activities-container');
    const activitiesTable = activitiesContainer.querySelector('tbody');
    const lastActivityRow = activitiesTable.querySelector('.activity-row:last-of-type');
    const newActivityRow = lastActivityRow.cloneNode(true);

    // Clear the textarea values
    newActivityRow.querySelector('textarea.activity-description').value = '';
    newActivityRow.querySelector('textarea.activity-verification').value = '';

    // Append the new activity row
    activitiesTable.appendChild(newActivityRow);

    // Add corresponding time frame row
    const objectiveCard = activitiesContainer.closest('.objective-card');
    const timeFrameTbody = objectiveCard.querySelector('.time-frame-card tbody');
    const lastTimeFrameRow = timeFrameTbody.querySelector('.activity-timeframe-row:last-of-type');
    const newTimeFrameRow = lastTimeFrameRow.cloneNode(true);

    // Clear the activity description and checkboxes
    newTimeFrameRow.querySelector('textarea').value = '';
    newTimeFrameRow.querySelectorAll('.month-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Append the new time frame row
    timeFrameTbody.appendChild(newTimeFrameRow);

    // Update name attributes
    const objectiveIndex = getObjectiveIndex(objectiveCard);
    updateNameAttributes(objectiveCard, objectiveIndex);

    // Reattach event listeners
    attachActivityEventListeners(objectiveCard);
}

function removeActivity(button) {
    const activityRow = button.closest('.activity-row');
    const activitiesTable = activityRow.parentNode;

    // Ensure at least one activity row remains
    if (activitiesTable.querySelectorAll('.activity-row').length > 1) {
        // Get the activity description to find the matching timeframe row
        const activityDescription = activityRow.querySelector('textarea.activity-description').value;

        // Remove the activity row
        activityRow.remove();

        // Find and remove the corresponding timeframe row
        const objectiveCard = button.closest('.objective-card');
        const timeFrameTbody = objectiveCard.querySelector('.time-frame-card tbody');
        const timeFrameRows = timeFrameTbody.querySelectorAll('.activity-timeframe-row');

        // Find the timeframe row with matching activity description
        let matchingTimeFrameRow = null;
        timeFrameRows.forEach((timeFrameRow, index) => {
            const timeFrameDescription = timeFrameRow.querySelector('textarea').value;
            if (timeFrameDescription === activityDescription) {
                matchingTimeFrameRow = timeFrameRow;
            }
        });

        // If no exact match found, remove the timeframe row at the same index as the removed activity
        if (!matchingTimeFrameRow) {
            const activityIndex = Array.from(activitiesTable.children).indexOf(activityRow);
            if (activityIndex >= 0 && activityIndex < timeFrameRows.length) {
                matchingTimeFrameRow = timeFrameRows[activityIndex];
            }
        }

        // Remove the matching timeframe row
        if (matchingTimeFrameRow) {
            matchingTimeFrameRow.remove();
        }

        // Update name attributes
        const objectiveIndex = getObjectiveIndex(objectiveCard);
        updateNameAttributes(objectiveCard, objectiveIndex);

        // Reattach event listeners
        attachActivityEventListeners(objectiveCard);
    }
}

function getObjectiveIndex(objectiveCard) {
    const objectives = Array.from(document.querySelectorAll('.objective-card:not(#objective-template .objective-card)'));
    return objectives.indexOf(objectiveCard);
}

function updateNameAttributes(objectiveCard, objectiveIndex) {
    objectiveCard.querySelector('textarea.objective-description').name = `objectives[${objectiveIndex}][objective]`;

    const results = objectiveCard.querySelectorAll('.result-section');
    results.forEach((result, resultIndex) => {
        result.querySelector('textarea.result-outcome').name = `objectives[${objectiveIndex}][results][${resultIndex}][result]`;
    });

    const risks = objectiveCard.querySelectorAll('.risks-container .risk-section');
    risks.forEach((riskSection, riskIndex) => {
        riskSection.querySelector('textarea.risk-description').name = `objectives[${objectiveIndex}][risks][${riskIndex}][risk]`;
    });

    const activities = objectiveCard.querySelectorAll('.activities-table .activity-row');
    activities.forEach((activityRow, activityIndex) => {
        activityRow.querySelector('textarea.activity-description').name = `objectives[${objectiveIndex}][activities][${activityIndex}][activity]`;
        activityRow.querySelector('textarea.activity-verification').name = `objectives[${objectiveIndex}][activities][${activityIndex}][verification]`;

        const timeFrameRow = objectiveCard.querySelectorAll('.time-frame-card tbody .activity-timeframe-row')[activityIndex];
        if (timeFrameRow) {
            timeFrameRow.querySelector('textarea').name = `objectives[${objectiveIndex}][activities][${activityIndex}][timeframe][description]`;
            timeFrameRow.querySelectorAll('.month-checkbox').forEach((checkbox, monthIndex) => {
                checkbox.name = `objectives[${objectiveIndex}][activities][${activityIndex}][timeframe][months][${monthIndex + 1}]`;
            });
        }
    });
}

function updateObjectiveNumbers() {
    const objectives = document.querySelectorAll('.objective-card:not(#objective-template .objective-card)');
    objectives.forEach((objective, index) => {
        objective.querySelector('h5').innerText = `Objective ${index + 1}`;
        updateNameAttributes(objective, index);
    });
}

function addTimeFrameRow(button) {
    const timeFrameCard = button.closest('.time-frame-card');
    const tbody = timeFrameCard.querySelector('tbody');
    const lastTimeFrameRow = tbody.querySelector('.activity-timeframe-row:last-of-type');
    const newRow = lastTimeFrameRow.cloneNode(true);

    // Clear the textarea and checkboxes
    newRow.querySelector('textarea').value = '';
    newRow.querySelectorAll('.month-checkbox').forEach(checkbox => checkbox.checked = false);

    tbody.appendChild(newRow);
    updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
}

function removeTimeFrameRow(button) {
    const row = button.closest('tr');
    if (row.parentNode.children.length > 1) {
        row.remove();
        updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
    }
}

function attachActivityEventListeners(objectiveCard) {
    const activitiesTable = objectiveCard.querySelector('.activities-table tbody');
    const timeFrameCard = objectiveCard.querySelector('.time-frame-card tbody');
    const activityRows = activitiesTable.querySelectorAll('.activity-row');

    activityRows.forEach(function(activityRow, index) {
        const activityDescriptionTextarea = activityRow.querySelector('textarea.activity-description');
        if (activityDescriptionTextarea._listener) {
            activityDescriptionTextarea.removeEventListener('input', activityDescriptionTextarea._listener);
        }

        const eventListener = function() {
            const activityDescription = this.value;
            const timeFrameRow = timeFrameCard.querySelectorAll('.activity-timeframe-row')[index];
            if (timeFrameRow) {
                timeFrameRow.querySelector('textarea').value = activityDescription;
            }
        };

        activityDescriptionTextarea.addEventListener('input', eventListener);
        activityDescriptionTextarea._listener = eventListener;
    });
}
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
