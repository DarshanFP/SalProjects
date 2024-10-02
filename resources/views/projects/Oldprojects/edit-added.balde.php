<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bind change event handler to update mobile and email on select change
    // [Your existing code for updating in-charge contact info goes here]

    // Update the phase options based on the selected overall project period
    // [Your existing code for updating phases goes here]

    // Attach the addPhase function to the Add Phase button
    // [Your existing code for adding phases goes here]

    // Initialize objective count and name attributes for existing objectives
    initializeObjectives();

    // Call calculateTotalAmountSanctioned initially to set up the correct values on page load
    calculateTotalAmountSanctioned();
});

// Variables
let objectiveCount = {{ count($project->objectives) }}; // Initialize with the current number of objectives

// Initialize objectives on page load
function initializeObjectives() {
    const objectives = document.querySelectorAll('.objective-card');
    objectives.forEach((objectiveCard, index) => {
        updateNameAttributes(objectiveCard, index);
    });
}

// Add a new objective card
function addObjective() {
    const container = document.getElementById('objectives-container');
    const template = document.querySelector('.objective-card').cloneNode(true);

    // Clear values and reset the cloned objective card
    resetFormValues(template);

    // Update the header and the name attributes
    template.querySelector('h5').innerText = `Objective ${++objectiveCount}`;

    // Reset risks, results, and activities to only one empty row each
    resetObjectiveSections(template);

    // Update the name attributes for the new objective
    updateNameAttributes(template, objectiveCount - 1);

    // Append the new objective card to the container
    container.appendChild(template);
}

// Reset form values in the cloned template
function resetFormValues(template) {
    template.querySelectorAll('textarea').forEach(textarea => textarea.value = '');
    template.querySelectorAll('input[type="checkbox"]').forEach(checkbox => checkbox.checked = false);
    template.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
}

// Reset risks, results, and activities sections
function resetObjectiveSections(template) {
    // Remove all result sections
    template.querySelectorAll('.result-section').forEach(section => section.remove());

    // Add one empty result section
    const resultsContainer = template.querySelector('.results-container');
    const resultSection = document.createElement('div');
    resultSection.className = 'mb-3 result-section';
    resultSection.innerHTML = `
        <textarea name="" class="mb-3 form-control result-outcome" rows="2" required></textarea>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
    `;
    const addResultButton = resultsContainer.querySelector('button[onclick="addResult(this)"]');
    resultsContainer.insertBefore(resultSection, addResultButton);

    // Remove all risk sections
    template.querySelectorAll('.risk-section').forEach(section => section.remove());

    // Add one empty risk section
    const risksContainer = template.querySelector('.risks-container');
    const riskSection = document.createElement('div');
    riskSection.className = 'mb-3 risk-section';
    riskSection.innerHTML = `
        <textarea name="" class="mb-3 form-control risk-description" rows="2" required></textarea>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
    `;
    const addRiskButton = risksContainer.querySelector('button[onclick="addRisk(this)"]');
    risksContainer.insertBefore(riskSection, addRiskButton);

    // Remove all activity rows
    template.querySelectorAll('.activity-row').forEach(row => row.remove());

    // Add one empty activity row
    const activitiesTableBody = template.querySelector('.activities-table tbody');
    const activityRow = document.createElement('tr');
    activityRow.className = 'activity-row';
    activityRow.innerHTML = `
        <td>
            <textarea name="" class="form-control activity-description" rows="2" required></textarea>
        </td>
        <td>
            <textarea name="" class="form-control activity-verification" rows="2" required></textarea>
        </td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeActivity(this)">Remove</button></td>
    `;
    activitiesTableBody.appendChild(activityRow);

    // Reset the Time Frame section
    const timeFrameCard = template.querySelector('.time-frame-card tbody');
    if (timeFrameCard) {
        timeFrameCard.querySelectorAll('.activity-timeframe-row').forEach(row => row.remove());

        // Add one empty time frame row
        const timeFrameRow = document.createElement('tr');
        timeFrameRow.className = 'activity-timeframe-row';
        let timeFrameRowHTML = `<td class="activity-description-text"></td>`;
        for (let i = 1; i <= 12; i++) {
            timeFrameRowHTML += `<td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name=""></td>`;
        }
        timeFrameRowHTML += `<td><button type="button" class="btn btn-danger btn-sm" onclick="removeTimeFrameRow(this)">Remove</button></td>`;
        timeFrameRow.innerHTML = timeFrameRowHTML;
        timeFrameCard.appendChild(timeFrameRow);
    }
}

// Update the name attributes for the newly added or cloned objective
function updateNameAttributes(objectiveCard, objectiveIndex) {
    objectiveCard.querySelector('textarea.objective-description').name = `objectives[${objectiveIndex}][objective]`;

    // Update the names for results
    const results = objectiveCard.querySelectorAll('.result-section');
    results.forEach((result, resultIndex) => {
        result.querySelector('textarea.result-outcome').name = `objectives[${objectiveIndex}][results][${resultIndex}][result]`;
    });

    // Update the names for risks
    const risks = objectiveCard.querySelectorAll('.risk-section');
    risks.forEach((riskSection, riskIndex) => {
        riskSection.querySelector('textarea.risk-description').name = `objectives[${objectiveIndex}][risks][${riskIndex}][risk]`;
    });

    // Update the names for activities and their timeframes
    const activities = objectiveCard.querySelectorAll('.activity-row');
    activities.forEach((activityRow, activityIndex) => {
        activityRow.querySelector('textarea.activity-description').name = `objectives[${objectiveIndex}][activities][${activityIndex}][activity]`;
        activityRow.querySelector('textarea.activity-verification').name = `objectives[${objectiveIndex}][activities][${activityIndex}][verification]`;

        // Update the timeframe for this activity if applicable
        const timeFrameRow = objectiveCard.querySelectorAll('.time-frame-card tbody .activity-timeframe-row')[activityIndex];
        if (timeFrameRow) {
            timeFrameRow.querySelector('.activity-description-text').innerText = activityRow.querySelector('textarea.activity-description').value;
            timeFrameRow.querySelectorAll('.month-checkbox').forEach((checkbox, monthIndex) => {
                checkbox.name = `objectives[${objectiveIndex}][activities][${activityIndex}][timeframe][months][${monthIndex + 1}]`;
            });
        }
    });
}

// Add Result
function addResult(button) {
    const resultsContainer = button.closest('.results-container');
    const lastResultSection = resultsContainer.querySelector('.result-section:last-of-type');
    const newResultSection = lastResultSection.cloneNode(true);

    // Clear the textarea value
    newResultSection.querySelector('textarea.result-outcome').value = '';

    // Append the new result section before the Add Result button
    resultsContainer.insertBefore(newResultSection, button);

    // Update name attributes
    const objectiveCard = button.closest('.objective-card');
    const objectiveIndex = getObjectiveIndex(objectiveCard);
    updateNameAttributes(objectiveCard, objectiveIndex);
}

// Remove Result
function removeResult(button) {
    const resultSection = button.closest('.result-section');
    const resultsContainer = resultSection.parentNode;

    // Ensure at least one result section remains
    if (resultsContainer.querySelectorAll('.result-section').length > 1) {
        resultSection.remove();

        // Update name attributes
        const objectiveCard = button.closest('.objective-card');
        const objectiveIndex = getObjectiveIndex(objectiveCard);
        updateNameAttributes(objectiveCard, objectiveIndex);
    }
}

// Add Risk
function addRisk(button) {
    const risksContainer = button.closest('.risks-container');
    const lastRiskSection = risksContainer.querySelector('.risk-section:last-of-type');
    const newRiskSection = lastRiskSection.cloneNode(true);

    // Clear the textarea value
    newRiskSection.querySelector('textarea.risk-description').value = '';

    // Append the new risk section before the Add Risk button
    risksContainer.insertBefore(newRiskSection, button);

    // Update name attributes
    const objectiveCard = button.closest('.objective-card');
    const objectiveIndex = getObjectiveIndex(objectiveCard);
    updateNameAttributes(objectiveCard, objectiveIndex);
}

// Remove Risk
function removeRisk(button) {
    const riskSection = button.closest('.risk-section');
    const risksContainer = riskSection.parentNode;

    // Ensure at least one risk section remains
    if (risksContainer.querySelectorAll('.risk-section').length > 1) {
        riskSection.remove();

        // Update name attributes
        const objectiveCard = button.closest('.objective-card');
        const objectiveIndex = getObjectiveIndex(objectiveCard);
        updateNameAttributes(objectiveCard, objectiveIndex);
    }
}

// Add Activity
function addActivity(button) {
    const activitiesTableBody = button.closest('.activities-container').querySelector('tbody');
    const lastActivityRow = activitiesTableBody.querySelector('.activity-row:last-of-type');
    const newActivityRow = lastActivityRow.cloneNode(true);

    // Clear the textarea values
    newActivityRow.querySelector('textarea.activity-description').value = '';
    newActivityRow.querySelector('textarea.activity-verification').value = '';

    // Append the new activity row
    activitiesTableBody.appendChild(newActivityRow);

    // Add corresponding time frame row
    const timeFrameTbody = button.closest('.objective-card').querySelector('.time-frame-card tbody');
    const lastTimeFrameRow = timeFrameTbody.querySelector('.activity-timeframe-row:last-of-type');
    const newTimeFrameRow = lastTimeFrameRow.cloneNode(true);

    // Clear the activity description and checkboxes
    newTimeFrameRow.querySelector('.activity-description-text').innerText = '';
    newTimeFrameRow.querySelectorAll('.month-checkbox').forEach(checkbox => {
        checkbox.checked = false;
        checkbox.name = '';
    });

    // Append the new time frame row
    timeFrameTbody.appendChild(newTimeFrameRow);

    // Update name attributes
    const objectiveCard = button.closest('.objective-card');
    const objectiveIndex = getObjectiveIndex(objectiveCard);
    updateNameAttributes(objectiveCard, objectiveIndex);
}

// Remove Activity
function removeActivity(button) {
    const activityRow = button.closest('.activity-row');
    const activitiesTableBody = activityRow.parentNode;

    // Ensure at least one activity row remains
    if (activitiesTableBody.querySelectorAll('.activity-row').length > 1) {
        const activityIndex = Array.from(activitiesTableBody.children).indexOf(activityRow);
        activityRow.remove();

        // Remove corresponding time frame row
        const timeFrameTbody = button.closest('.objective-card').querySelector('.time-frame-card tbody');
        const timeFrameRow = timeFrameTbody.children[activityIndex];
        timeFrameRow.remove();

        // Update name attributes
        const objectiveCard = button.closest('.objective-card');
        const objectiveIndex = getObjectiveIndex(objectiveCard);
        updateNameAttributes(objectiveCard, objectiveIndex);
    }
}

// Add Time Frame Row
function addTimeFrameRow(button) {
    const timeFrameTbody = button.closest('.time-frame-card').querySelector('tbody');
    const lastTimeFrameRow = timeFrameTbody.querySelector('.activity-timeframe-row:last-of-type');
    const newTimeFrameRow = lastTimeFrameRow.cloneNode(true);

    // Clear the activity description and checkboxes
    newTimeFrameRow.querySelector('.activity-description-text').innerText = '';
    newTimeFrameRow.querySelectorAll('.month-checkbox').forEach(checkbox => {
        checkbox.checked = false;
        checkbox.name = '';
    });

    // Append the new time frame row
    timeFrameTbody.appendChild(newTimeFrameRow);

    // Update name attributes
    const objectiveCard = button.closest('.objective-card');
    const objectiveIndex = getObjectiveIndex(objectiveCard);
    updateNameAttributes(objectiveCard, objectiveIndex);
}

// Remove Time Frame Row
function removeTimeFrameRow(button) {
    const timeFrameRow = button.closest('.activity-timeframe-row');
    const timeFrameTbody = timeFrameRow.parentNode;

    // Ensure at least one time frame row remains
    if (timeFrameTbody.querySelectorAll('.activity-timeframe-row').length > 1) {
        timeFrameRow.remove();

        // Update name attributes
        const objectiveCard = button.closest('.objective-card');
        const objectiveIndex = getObjectiveIndex(objectiveCard);
        updateNameAttributes(objectiveCard, objectiveIndex);
    }
}

// Get the index of an objective card
function getObjectiveIndex(objectiveCard) {
    const objectives = Array.from(document.querySelectorAll('.objective-card'));
    return objectives.indexOf(objectiveCard);
}

// Remove Last Objective
function removeLastObjective() {
    const objectives = document.querySelectorAll('.objective-card');
    if (objectives.length > 1) {
        objectives[objectives.length - 1].remove();
        objectiveCount--;
        updateObjectiveNumbers();
    }
}

// Update Objective Numbers
function updateObjectiveNumbers() {
    const objectives = document.querySelectorAll('.objective-card');
    objectives.forEach((objective, index) => {
        objective.querySelector('h5').innerText = `Objective ${index + 1}`;
        updateNameAttributes(objective, index);
    });
}

// [Include other functions like calculateBudgetRowTotals, calculateBudgetTotals, calculateTotalAmountSanctioned, addBudgetRow, removeBudgetRow, addPhase, removePhase, addAttachment, removeAttachment, updateAttachmentLabels, etc.]

</script>
