<script>
let objectiveCount = 1;

document.addEventListener('DOMContentLoaded', function() {
    objectiveCount = document.querySelectorAll('.objective-card').length;
});

function addObjective() {
    const container = document.getElementById('objectives-container');
    const objectiveTemplate = document.querySelector('.objective-card').cloneNode(true);

    // Reset the values in the cloned template
    objectiveTemplate.querySelector('textarea.objective-description').value = '';

    // Increment the objective count and update the objective header
    objectiveTemplate.querySelector('h5').innerText = `Objective ${++objectiveCount}`;

    // Reset risks and results to only one empty row each
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

function addRisk(button) {
    const riskSection = button.closest('.risks-container').querySelector('.risk-section').cloneNode(true);
    riskSection.querySelector('textarea.risk-description').value = '';

    // Append the new risk section and move the "Add Risk" button to the end
    button.closest('.risks-container').appendChild(riskSection);
    button.closest('.risks-container').appendChild(button);

    updateNameAttributes(riskSection.closest('.objective-card'), objectiveCount - 1);
}

function removeRisk(button) {
    const riskSection = button.closest('.risk-section');
    if (riskSection.parentNode.querySelectorAll('.risk-section').length > 1) {
        riskSection.remove();
        updateNameAttributes(riskSection.closest('.objective-card'), objectiveCount - 1);
    }
}

// function updateNameAttributes(objectiveCard, objectiveIndex) {
//     objectiveCard.querySelector('textarea.objective-description').name = `objectives[${objectiveIndex}][objective]`;

//     const results = objectiveCard.querySelectorAll('.result-section');
//     results.forEach((result, resultIndex) => {
//         result.querySelector('textarea.result-outcome').name = `objectives[${objectiveIndex}][results][${resultIndex}][result]`;

//         const risks = result.querySelectorAll('textarea.risk-description');
//         risks.forEach((risk, riskIndex) => {
//             risk.name = `objectives[${objectiveIndex}][results][${resultIndex}][risks][${riskIndex}][risk]`;
//         });
//     });

//     const activities = objectiveCard.querySelectorAll('.activities-table .activity-row');
//     activities.forEach((activity, activityIndex) => {
//         activity.querySelector('textarea.activity-description').name = `objectives[${objectiveIndex}][activities][${activityIndex}][activity]`;
//         activity.querySelector('textarea.activity-verification').name = `objectives[${objectiveIndex}][activities][${activityIndex}][verification]`;

//         const timeFrameRows = objectiveCard.querySelectorAll('.time-frame-card tbody .activity-timeframe-row');
//         timeFrameRows.forEach((timeFrameRow, timeFrameIndex) => {
//             timeFrameRow.querySelector('.activity-description-text').name = `objectives[${objectiveIndex}][activities][${timeFrameIndex}][timeframe][description]`;
//             timeFrameRow.querySelectorAll('.month-checkbox').forEach((checkbox, monthIndex) => {
//                 checkbox.name = `objectives[${objectiveIndex}][activities][${timeFrameIndex}][timeframe][months][${monthIndex + 1}]`;
//             });
//         });
//     });
// }

function updateNameAttributes(objectiveCard, objectiveIndex) {
    objectiveCard.querySelector('textarea.objective-description').name = `objectives[${objectiveIndex}][objective]`;

    const results = objectiveCard.querySelectorAll('.result-section');
    results.forEach((result, resultIndex) => {
        result.querySelector('textarea.result-outcome').name = `objectives[${objectiveIndex}][results][${resultIndex}][result]`;

        const risks = result.querySelectorAll('textarea.risk-description');
        risks.forEach((risk, riskIndex) => {
            // Update risk name to include the correct indexes
            risk.name = `objectives[${objectiveIndex}][results][${resultIndex}][risks][${riskIndex}][risk]`;
        });
    });

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
