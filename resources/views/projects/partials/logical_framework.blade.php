<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Solution Analysis: Logical Framework</h4>
    </div>
    <div class="card-body" id="objectives-container">
        <!-- Objective Template -->
        <div class="mb-3 objective-card">
            <div class="objective-header d-flex justify-content-between align-items-center">
                <h5>Objective 1</h5>
            </div>
            <textarea name="objectives[0][objective]" class="mb-3 form-control objective-description" rows="2" placeholder="Enter Objective" style="background-color: #202ba3;"></textarea>

            <div class="results-container">
                <!-- Result Section -->
                <div class="mb-3 result-section">
                    <div class="result-header d-flex justify-content-between align-items-center">
                        <h6>Results / Outcome</h6>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                    </div>
                    <textarea name="objectives[0][results][0][result]" class="mb-3 form-control result-outcome" rows="2" placeholder="Enter Result" style="background-color: #202ba3;"></textarea>
                </div>
                <!-- Button to add more Results -->
                <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>

                <!-- Risks Section -->
                <div class="risks-container">
                    <div class="mb-3 risk-section">
                        <div class="risk-header d-flex justify-content-between align-items-center">
                            <h6>Risks</h6>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                        </div>
                        <textarea name="objectives[0][results][0][risks][0][risk]" class="mb-3 form-control risk-description" rows="2" placeholder="Enter Risk" style="background-color: #202ba3;"></textarea>
                    </div>
                    <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this)">Add Risk</button>
                </div>
            </div>

            <!-- Activities Table -->
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
                        <!-- Activity Template -->
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
            <!-- Include the Time Frame partial -->
            @include('projects.partials._timeframe', ['objectiveIndex' => 0])
        </div>
        <!-- End of Objective Template -->

        <!-- Objective Controls -->
        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-primary" onclick="addObjective()">Add Objective</button>
            <button type="button" class="btn btn-danger" onclick="removeLastObjective()">Remove Last Objective</button>
        </div>
    </div>
</div>


<script>

let objectiveCount = 1;

document.addEventListener('DOMContentLoaded', function() {
    objectiveCount = document.querySelectorAll('.objective-card').length;
});

function addObjective() {
    const container = document.getElementById('objectives-container');
    const objectiveTemplate = document.querySelector('.objective-card').cloneNode(true);

    // Reset the values in the cloned template
    resetFormValues(objectiveTemplate);

    // Increment the objective count and update the objective header
    objectiveTemplate.querySelector('h5').innerText = `Objective ${++objectiveCount}`;

    // Reset risks, results, and activities to only one empty row each
    objectiveTemplate.querySelectorAll('.result-section:not(:first-child)').forEach(section => section.remove());
    objectiveTemplate.querySelectorAll('.risk-section:not(:first-child)').forEach(section => section.remove());
    objectiveTemplate.querySelectorAll('.activity-row:not(:first-child)').forEach(row => row.remove());

    // Reset the Time Frame section
    const timeFrameCard = objectiveTemplate.querySelector('.time-frame-card tbody');
    timeFrameCard.querySelectorAll('.activity-timeframe-row:not(:first-child)').forEach(row => row.remove());
    timeFrameCard.querySelectorAll('.activity-timeframe-row textarea').forEach(textarea => textarea.value = '');
    timeFrameCard.querySelectorAll('.month-checkbox').forEach(checkbox => checkbox.checked = false);

    // Update the name attributes for the new objective
    updateNameAttributes(objectiveTemplate, objectiveCount - 1);

    // Append the new objective at the end
    container.insertBefore(objectiveTemplate, container.lastElementChild);
}

function resetFormValues(template) {
    // Clear values of all textareas
    template.querySelectorAll('textarea').forEach(textarea => textarea.value = '');

    // Clear all checkboxes
    template.querySelectorAll('input[type="checkbox"]').forEach(checkbox => checkbox.checked = false);

    // Reset all select dropdowns (if any) to their default value
    template.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
}

function removeLastObjective() {
    const objectives = document.querySelectorAll('.objective-card');
    if (objectives.length > 1) {
        objectives[objectiveCount - 1].remove();
        objectiveCount--;
        updateObjectiveNumbers();
    }
}

function addResult(button) {
    const resultTemplate = button.closest('.results-container').querySelector('.result-section').cloneNode(true);
    resultTemplate.querySelector('textarea.result-outcome').value = '';
    button.closest('.results-container').insertBefore(resultTemplate, button);
    updateNameAttributes(button.closest('.objective-card'), objectiveCount - 1);
}

function removeResult(button) {
    const resultSection = button.closest('.result-section');
    if (resultSection.parentNode.querySelectorAll('.result-section').length > 1) {
        resultSection.remove();
        updateNameAttributes(resultSection.closest('.objective-card'), objectiveCount - 1);
    }
}

function addRisk(button) {
    const risksContainer = button.closest('.risks-container');
    const riskTemplate = risksContainer.querySelector('.risk-section').cloneNode(true);
    riskTemplate.querySelector('textarea.risk-description').value = '';

    // Append the new risk section before the "Add Risk" button
    risksContainer.insertBefore(riskTemplate, button);

    updateNameAttributes(risksContainer.closest('.objective-card'), objectiveCount - 1);
}

function removeRisk(button) {
    const riskSection = button.closest('.risk-section');
    if (riskSection.parentNode.querySelectorAll('.risk-section').length > 1) {
        riskSection.remove();
        updateNameAttributes(riskSection.closest('.objective-card'), objectiveCount - 1);
    }
}

function addActivity(button) {
    const activitiesTable = button.closest('.activities-container').querySelector('tbody');
    const activityRow = activitiesTable.querySelector('.activity-row').cloneNode(true);
    activityRow.querySelector('textarea.activity-description').value = '';
    activityRow.querySelector('textarea.activity-verification').value = '';
    activitiesTable.appendChild(activityRow);

    const objectiveCard = button.closest('.objective-card');
    const timeFrameCard = objectiveCard.querySelector('.time-frame-card tbody');
    const timeFrameRow = timeFrameCard.querySelector('.activity-timeframe-row').cloneNode(true);
    timeFrameRow.querySelector('.activity-description-text').innerText = '';
    timeFrameCard.appendChild(timeFrameRow);

    activityRow.querySelector('textarea.activity-description').addEventListener('input', function() {
        const index = Array.from(activitiesTable.querySelectorAll('.activity-row')).indexOf(activityRow);
        timeFrameCard.querySelectorAll('.activity-timeframe-row')[index].querySelector('.activity-description-text').innerText = this.value;
    });

    updateNameAttributes(button.closest('.objective-card'), objectiveCount - 1);
}

function removeActivity(button) {
    const row = button.closest('tr');
    const activityIndex = Array.from(row.parentNode.children).indexOf(row);
    row.remove();

    const timeFrameCard = button.closest('.objective-card').querySelector('.time-frame-card tbody');
    const timeframeRow = timeFrameCard.children[activityIndex];
    timeframeRow.remove();

    updateNameAttributes(button.closest('.objective-card'), objectiveCount - 1);
}

function updateNameAttributes(objectiveCard, objectiveIndex) {
    objectiveCard.querySelector('textarea.objective-description').name = `objectives[${objectiveIndex}][objective]`;

    // Update the names for results
    const results = objectiveCard.querySelectorAll('.result-section');
    results.forEach((result, resultIndex) => {
        result.querySelector('textarea.result-outcome').name = `objectives[${objectiveIndex}][results][${resultIndex}][result]`;
    });

    // Update the names for risks (directly under the objective)
    const risks = objectiveCard.querySelectorAll('.risk-section textarea.risk-description');
    risks.forEach((risk, riskIndex) => {
        risk.name = `objectives[${objectiveIndex}][risks][${riskIndex}][risk]`;
    });

    // Update the names for activities and timeframes
    const activities = objectiveCard.querySelectorAll('.activities-table .activity-row');
    activities.forEach((activity, activityIndex) => {
        activity.querySelector('textarea.activity-description').name = `objectives[${objectiveIndex}][activities][${activityIndex}][activity]`;
        activity.querySelector('textarea.activity-verification').name = `objectives[${objectiveIndex}][activities][${activityIndex}][verification]`;

        const timeFrameRows = objectiveCard.querySelectorAll('.time-frame-card tbody .activity-timeframe-row');
        timeFrameRows.forEach((timeFrameRow, timeFrameIndex) => {
            timeFrameRow.querySelector('.activity-description-text').name = `objectives[${objectiveIndex}][activities][${timeFrameIndex}][timeframe][description]`;
            timeFrameRow.querySelectorAll('.month-checkbox').forEach((checkbox, monthIndex) => {
                checkbox.name = `objectives[${objectiveIndex}][activities][${timeFrameIndex}][timeframe][months][${monthIndex + 1}]`;
            });
        });
    });
}

function updateObjectiveNumbers() {
    const objectives = document.querySelectorAll('.objective-card');
    objectives.forEach((objective, index) => {
        objective.querySelector('h5').innerText = `Objective ${index + 1}`;
        updateNameAttributes(objective, index);
    });
}

// Time Frame specific functions
function addTimeFrameRow(button) {
    const timeFrameCard = button.closest('.time-frame-card');
    const tbody = timeFrameCard.querySelector('tbody');
    const newRow = tbody.querySelector('.activity-timeframe-row').cloneNode(true);

    // Clear the contents of the new row
    newRow.querySelector('.activity-description-text').innerText = '';
    newRow.querySelectorAll('.month-checkbox').forEach(checkbox => checkbox.checked = false);

    tbody.appendChild(newRow);

    updateNameAttributes(button.closest('.objective-card'), objectiveCount - 1);
}

function removeTimeFrameRow(button) {
    const row = button.closest('tr');
    row.remove();
    updateNameAttributes(button.closest('.objective-card'), objectiveCount - 1);
}


</script>


{{-- resources/views/projects/partials/scritp_logical.php --}}
{{--
 --}}
