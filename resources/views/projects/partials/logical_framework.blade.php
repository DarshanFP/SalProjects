<!-- resources/views/projects/partials/logical_framework.blade.php -->
<!-- resources/views/projects/partials/logical_framework.blade.php -->
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Solution Analysis: Logical Framework</h4>
    </div>
    <div class="card-body" id="objectives-container">
        <!-- Hidden Objective Template -->
        <div id="objective-template" style="display: none;">
            <div class="mb-3 objective-card">
                <div class="objective-header d-flex justify-content-between align-items-center">
                    <h5>Objective</h5>
                </div>
                <textarea name="objectives[0][objective]" class="mb-3 form-control objective-description" rows="2" placeholder="Enter Objective" style="background-color: #202ba3;"></textarea>

                <div class="results-container">
                    <div class="mb-3 result-section">
                        <div class="result-header d-flex justify-content-between align-items-center">
                            <h6>Results / Outcome</h6>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                        </div>
                        <textarea name="objectives[0][results][0][result]" class="mb-3 form-control result-outcome" rows="2" placeholder="Enter Result" style="background-color: #202ba3;"></textarea>
                    </div>
                    <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>

                    <div class="risks-container">
                        <div class="mb-3 risk-section">
                            <div class="risk-header d-flex justify-content-between align-items-center">
                                <h6>Risks</h6>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                            </div>
                            <textarea name="objectives[0][risks][0][risk]" class="mb-3 form-control risk-description" rows="2" placeholder="Enter Risk" style="background-color: #202ba3;"></textarea>
                        </div>
                        <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this)">Add Risk</button>
                    </div>
                </div>

                <div class="activities-container">
                    <h6>Activities and Means of Verification</h6>
                    <table class="table table-bordered activities-table">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 40%;">Activities</th>
                                <th scope="col">Means of Verification</th>
                                <th scope="col" style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="activity-row">
                                <td>
                                    <textarea name="objectives[0][activities][0][activity]" class="form-control activity-description" rows="2" placeholder="Enter Activity" style="background-color: #202ba3;"></textarea>
                                </td>
                                <td>
                                    <textarea name="objectives[0][activities][0][verification]" class="form-control activity-verification" rows="2" placeholder="Means of Verification" style="background-color: #202ba3;"></textarea>
                                </td>
                                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeActivity(this)">Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="mb-3 btn btn-primary" onclick="addActivity(this)">Add Activity</button>
                </div>
                @include('projects.partials._timeframe', ['objectiveIndex' => 0])
            </div>
        </div>

        <!-- Hidden Result Template -->
        <div id="result-template" style="display: none;">
            <div class="mb-3 result-section">
                <div class="result-header d-flex justify-content-between align-items-center">
                    <h6>Results / Outcome</h6>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                </div>
                <textarea name="objectives[0][results][0][result]" class="mb-3 form-control result-outcome" rows="2" placeholder="Enter Result" style="background-color: #202ba3;"></textarea>
            </div>
        </div>

        <!-- Hidden Risk Template -->
        <div id="risk-template" style="display: none;">
            <div class="mb-3 risk-section">
                <div class="risk-header d-flex justify-content-between align-items-center">
                    <h6>Risks</h6>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                </div>
                <textarea name="objectives[0][risks][0][risk]" class="mb-3 form-control risk-description" rows="2" placeholder="Enter Risk" style="background-color: #202ba3;"></textarea>
            </div>
        </div>

        <!-- Render Predecessor Objectives or Default -->
        @if(isset($predecessorObjectives) && !empty($predecessorObjectives))
            @foreach($predecessorObjectives as $index => $obj)
                <div class="mb-3 objective-card">
                    <div class="objective-header d-flex justify-content-between align-items-center">
                        <h5>Objective {{ $index + 1 }}</h5>
                    </div>
                    <textarea name="objectives[{{ $index }}][objective]" class="mb-3 form-control objective-description" rows="2" placeholder="Enter Objective" style="background-color: #202ba3;">{{ $obj['objective'] ?? '' }}</textarea>

                    <div class="results-container">
                        @foreach($obj['results'] as $rIndex => $result)
                            <div class="mb-3 result-section">
                                <div class="result-header d-flex justify-content-between align-items-center">
                                    <h6>Results / Outcome</h6>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                                </div>
                                <textarea name="objectives[{{ $index }}][results][{{ $rIndex }}][result]" class="mb-3 form-control result-outcome" rows="2" placeholder="Enter Result" style="background-color: #202ba3;">{{ $result['result'] ?? '' }}</textarea>
                            </div>
                        @endforeach
                        <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>

                        <div class="risks-container">
                            @foreach($obj['risks'] as $rIndex => $risk)
                                <div class="mb-3 risk-section">
                                    <div class="risk-header d-flex justify-content-between align-items-center">
                                        <h6>Risks</h6>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                                    </div>
                                    <textarea name="objectives[{{ $index }}][risks][{{ $rIndex }}][risk]" class="mb-3 form-control risk-description" rows="2" placeholder="Enter Risk" style="background-color: #202ba3;">{{ $risk['risk'] ?? '' }}</textarea>
                                </div>
                            @endforeach
                            <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this)">Add Risk</button>
                        </div>
                    </div>

                    <div class="activities-container">
                        <h6>Activities and Means of Verification</h6>
                        <table class="table table-bordered activities-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 40%;">Activities</th>
                                    <th scope="col">Means of Verification</th>
                                    <th scope="col" style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($obj['activities'] as $aIndex => $activity)
                                    <tr class="activity-row">
                                        <td>
                                            <textarea name="objectives[{{ $index }}][activities][{{ $aIndex }}][activity]" class="form-control activity-description" rows="2" placeholder="Enter Activity" style="background-color: #202ba3;">{{ $activity['activity'] ?? '' }}</textarea>
                                        </td>
                                        <td>
                                            <textarea name="objectives[{{ $index }}][activities][{{ $aIndex }}][verification]" class="form-control activity-verification" rows="2" placeholder="Means of Verification" style="background-color: #202ba3;">{{ $activity['verification'] ?? '' }}</textarea>
                                        </td>
                                        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeActivity(this)">Remove</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="button" class="mb-3 btn btn-primary" onclick="addActivity(this)">Add Activity</button>
                    </div>
                    @include('projects.partials._timeframe', ['objectiveIndex' => $index, 'predecessorActivities' => $obj['activities']])
                </div>
            @endforeach
        @else
            <!-- Default Objective -->
            <div class="mb-3 objective-card">
                <div class="objective-header d-flex justify-content-between align-items-center">
                    <h5>Objective 1</h5>
                </div>
                <textarea name="objectives[0][objective]" class="mb-3 form-control objective-description" rows="2" placeholder="Enter Objective" style="background-color: #202ba3;"></textarea>

                <div class="results-container">
                    <div class="mb-3 result-section">
                        <div class="result-header d-flex justify-content-between align-items-center">
                            <h6>Results / Outcome</h6>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                        </div>
                        <textarea name="objectives[0][results][0][result]" class="mb-3 form-control result-outcome" rows="2" placeholder="Enter Result" style="background-color: #202ba3;"></textarea>
                    </div>
                    <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>

                    <div class="risks-container">
                        <div class="mb-3 risk-section">
                            <div class="risk-header d-flex justify-content-between align-items-center">
                                <h6>Risks</h6>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                            </div>
                            <textarea name="objectives[0][risks][0][risk]" class="mb-3 form-control risk-description" rows="2" placeholder="Enter Risk" style="background-color: #202ba3;"></textarea>
                        </div>
                        <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this)">Add Risk</button>
                    </div>
                </div>

                <div class="activities-container">
                    <h6>Activities and Means of Verification</h6>
                    <table class="table table-bordered activities-table">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 40%;">Activities</th>
                                <th scope="col">Means of Verification</th>
                                <th scope="col" style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="activity-row">
                                <td>
                                    <textarea name="objectives[0][activities][0][activity]" class="form-control activity-description" rows="2" placeholder="Enter Activity" style="background-color: #202ba3;"></textarea>
                                </td>
                                <td>
                                    <textarea name="objectives[0][activities][0][verification]" class="form-control activity-verification" rows="2" placeholder="Means of Verification" style="background-color: #202ba3;"></textarea>
                                </td>
                                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeActivity(this)">Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="mb-3 btn btn-primary" onclick="addActivity(this)">Add Activity</button>
                </div>
                @include('projects.partials._timeframe', ['objectiveIndex' => 0])
            </div>
        @endif

        <!-- Objective Controls -->
        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-primary" onclick="addObjective()">Add Objective</button>
            <button type="button" class="btn btn-danger" onclick="removeLastObjective()">Remove Last Objective</button>
        </div>
    </div>
</div>

<script>
let objectiveCount = {{ isset($predecessorObjectives) && !empty($predecessorObjectives) ? count($predecessorObjectives) : 1 }};

document.addEventListener('DOMContentLoaded', function() {
    // Sync objectiveCount with rendered objectives
    objectiveCount = document.querySelectorAll('.objective-card:not(#objective-template .objective-card)').length;

    // Attach event listeners to existing activity descriptions
    document.querySelectorAll('.objective-card:not(#objective-template .objective-card)').forEach(function(objectiveCard) {
        attachActivityEventListeners(objectiveCard);
    });

    console.log('DOM loaded, objective count:', objectiveCount);
    console.log('Objective template exists:', !!document.getElementById('objective-template'));
    console.log('Result template exists:', !!document.getElementById('result-template'));
    console.log('Risk template exists:', !!document.getElementById('risk-template'));
    console.log('Activity template exists:', !!document.getElementById('activity-template'));
    console.log('Timeframe template exists:', !!document.getElementById('timeframe-template'));
});

function addObjective() {
    const container = document.getElementById('objectives-container');
    const objectiveTemplate = document.getElementById('objective-template')?.querySelector('.objective-card');
    console.log('Objective template found:', objectiveTemplate);

    if (!objectiveTemplate) {
        console.error('Objective template not found');
        return;
    }

    const newObjective = objectiveTemplate.cloneNode(true);
    newObjective.querySelector('h5').innerText = `Objective ${objectiveCount + 1}`;
    resetFormValues(newObjective);
    updateNameAttributes(newObjective, objectiveCount);
    container.insertBefore(newObjective, container.lastElementChild);
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
    const resultTemplate = document.getElementById('result-template')?.querySelector('.result-section');
    console.log('Result template found:', resultTemplate);

    if (!resultTemplate) {
        console.error('Result template not found');
        return;
    }

    const newResult = resultTemplate.cloneNode(true);
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
    const riskTemplate = document.getElementById('risk-template')?.querySelector('.risk-section');
    console.log('Risk template found:', riskTemplate);

    if (!riskTemplate) {
        console.error('Risk template not found');
        return;
    }

    const newRisk = riskTemplate.cloneNode(true);
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
    const activityTemplate = document.getElementById('activity-template')?.querySelector('.activity-row');
    console.log('Activity template found:', activityTemplate);

    if (!activityTemplate) {
        console.error('Activity template not found');
        return;
    }

    const newActivity = activityTemplate.cloneNode(true);
    newActivity.querySelector('textarea.activity-description').value = '';
    newActivity.querySelector('textarea.activity-verification').value = '';
    activitiesTable.appendChild(newActivity);

    const objectiveCard = activitiesContainer.closest('.objective-card');
    const timeFrameTable = objectiveCard.querySelector('.time-frame-card tbody');
    const timeframeTemplate = document.getElementById('timeframe-template')?.querySelector('.activity-timeframe-row');
    console.log('Timeframe template found:', timeframeTemplate);

    if (!timeframeTemplate) {
        console.error('Timeframe template not found');
        return;
    }

    const newTimeframe = timeframeTemplate.cloneNode(true);
    const activityDesc = newActivity.querySelector('textarea.activity-description');
    const timeFrameDesc = newTimeframe.querySelector('textarea');
    timeFrameDesc.value = activityDesc.value;
    newTimeframe.querySelectorAll('.month-checkbox').forEach(checkbox => checkbox.checked = false);
    timeFrameTable.appendChild(newTimeframe);

    // Sync activity description to timeframe
    activityDesc.addEventListener('input', function() {
        timeFrameDesc.value = this.value;
    });

    attachActivityEventListeners(objectiveCard);
    updateNameAttributes(objectiveCard, getObjectiveIndex(objectiveCard));
}

function removeActivity(button) {
    const row = button.closest('tr');
    const activityIndex = Array.from(row.parentNode.children).indexOf(row);
    if (row.parentNode.children.length > 1) {
        row.remove();
        const timeFrameCard = button.closest('.objective-card').querySelector('.time-frame-card tbody');
        const timeframeRow = timeFrameCard.children[activityIndex];
        if (timeframeRow) timeframeRow.remove();
        const objectiveCard = button.closest('.objective-card');
        updateNameAttributes(objectiveCard, getObjectiveIndex(objectiveCard));
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
    const timeframeTemplate = document.getElementById('timeframe-template')?.querySelector('.activity-timeframe-row');
    console.log('Timeframe template found:', timeframeTemplate);

    if (!timeframeTemplate) {
        console.error('Timeframe template not found');
        return;
    }

    const newRow = timeframeTemplate.cloneNode(true);
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
