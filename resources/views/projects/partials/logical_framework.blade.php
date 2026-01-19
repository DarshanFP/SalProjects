<!-- resources/views/projects/partials/logical_framework.blade.php -->
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
            <textarea name="objectives[0][objective]" class="mb-3 form-control objective-description logical-textarea" rows="2" placeholder="Enter Objective"></textarea>

            <div class="results-container">
                <!-- Result Section -->
                <div class="mb-3 result-section">
                    <div class="result-header d-flex justify-content-between align-items-center">
                        <h6>Result 1</h6>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                    </div>
                    <textarea name="objectives[0][results][0][result]" class="mb-3 form-control result-outcome logical-textarea" rows="2" placeholder="Enter Result"></textarea>
                </div>
                <!-- Button to add more Results -->
                <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>

                <!-- Risks Section -->
                <div class="risks-container">
                    <div class="mb-3 risk-section">
                        <div class="risk-header d-flex justify-content-between align-items-center">
                            <h6>Risk 1</h6>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                        </div>
                        <textarea name="objectives[0][risks][0][risk]" class="mb-3 form-control risk-description logical-textarea" rows="2" placeholder="Enter Risk"></textarea>
                    </div>
                    <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this)">Add Risk</button>
                </div>
            </div>

            <!-- Activities Table -->
            <div class="activities-container">
                <h6>Activities and Means of Verification</h6>
                <div class="table-responsive">
                    <table class="table table-bordered activities-table">
                        <thead>
                            <tr>
                                <th scope="col" style="min-width: 50px;">No.</th>
                                <th scope="col" style="min-width: 200px;">Activities</th>
                                <th scope="col" style="min-width: 200px;">Means of Verification</th>
                                <th scope="col" style="min-width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Activity Template -->
                            <tr class="activity-row">
                                <td style="text-align: center; vertical-align: middle;">1</td>
                                <td class="table-cell-wrap">
                                    <textarea name="objectives[0][activities][0][activity]" class="form-control activity-description select-input logical-textarea" rows="2" placeholder="Enter Activity"></textarea>
                                </td>
                                <td class="table-cell-wrap">
                                    <textarea name="objectives[0][activities][0][verification]" class="form-control activity-verification select-input logical-textarea" rows="2" placeholder="Means of Verification"></textarea>
                                </td>
                                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeActivity(this)">Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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

        // Attach event listeners to existing activity descriptions for all objectives
        document.querySelectorAll('.objective-card').forEach(function(objectiveCard) {
            attachActivityEventListeners(objectiveCard);
        });
        
        // Initialize auto-resize for all logical framework textareas
        initializeLogicalTextareas();
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
        
        // Reset result and risk headers to "Result 1" and "Risk 1"
        const firstResult = objectiveTemplate.querySelector('.result-section');
        if (firstResult) {
            const resultHeader = firstResult.querySelector('h6');
            if (resultHeader) resultHeader.textContent = 'Result 1';
        }
        const firstRisk = objectiveTemplate.querySelector('.risk-section');
        if (firstRisk) {
            const riskHeader = firstRisk.querySelector('h6');
            if (riskHeader) riskHeader.textContent = 'Risk 1';
        }
        
        // Reset activity index to 1
        const firstActivity = objectiveTemplate.querySelector('.activity-row');
        if (firstActivity) {
            const indexCell = firstActivity.querySelector('td:first-child');
            if (indexCell) indexCell.textContent = '1';
        }

        // Reset the Time Frame section
        const timeFrameCard = objectiveTemplate.querySelector('.time-frame-card tbody');
        timeFrameCard.querySelectorAll('.activity-timeframe-row:not(:first-child)').forEach(row => row.remove());
        timeFrameCard.querySelectorAll('.activity-description-text').forEach(span => span.innerText = '');
        timeFrameCard.querySelectorAll('.month-checkbox').forEach(checkbox => checkbox.checked = false);

        // Update the name attributes for the new objective
        updateNameAttributes(objectiveTemplate, objectiveCount - 1);

        // Append the new objective at the end
        container.insertBefore(objectiveTemplate, container.lastElementChild);

        // Attach event listeners to the initial activity descriptions
        attachActivityEventListeners(objectiveTemplate);
        
        // Initialize auto-resize for all textareas in the new objective
        const newTextareas = objectiveTemplate.querySelectorAll('.logical-textarea');
        newTextareas.forEach(textarea => {
            autoResizeTextarea(textarea);
            textarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        });
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
        const resultsContainer = button.closest('.results-container');
        const resultTemplate = resultsContainer.querySelector('.result-section').cloneNode(true);
        const newTextarea = resultTemplate.querySelector('textarea.result-outcome');
        newTextarea.value = '';
        button.closest('.results-container').insertBefore(resultTemplate, button);
        reindexResults(resultsContainer); // Reindex all results
        updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
        // Initialize auto-resize for new textarea
        if (newTextarea) {
            autoResizeTextarea(newTextarea);
            newTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }
    }

    function removeResult(button) {
        const resultSection = button.closest('.result-section');
        const resultsContainer = resultSection.closest('.results-container');
        if (resultSection.parentNode.querySelectorAll('.result-section').length > 1) {
            resultSection.remove();
            reindexResults(resultsContainer); // Reindex all results
            updateNameAttributes(resultSection.closest('.objective-card'), getObjectiveIndex(resultSection.closest('.objective-card')));
        }
    }

    // Reindex results within an objective
    function reindexResults(resultsContainer) {
        const resultSections = resultsContainer.querySelectorAll('.result-section');
        resultSections.forEach((section, index) => {
            const header = section.querySelector('h6');
            if (header) {
                header.textContent = `Result ${index + 1}`;
            }
        });
    }

    function addRisk(button) {
        const risksContainer = button.closest('.risks-container');
        const riskTemplate = risksContainer.querySelector('.risk-section').cloneNode(true);
        const newTextarea = riskTemplate.querySelector('textarea.risk-description');
        newTextarea.value = '';

        // Append the new risk section before the "Add Risk" button
        risksContainer.insertBefore(riskTemplate, button);

        reindexRisks(risksContainer); // Reindex all risks
        updateNameAttributes(risksContainer.closest('.objective-card'), getObjectiveIndex(risksContainer.closest('.objective-card')));
        // Initialize auto-resize for new textarea
        if (newTextarea) {
            autoResizeTextarea(newTextarea);
            newTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }
    }

    function removeRisk(button) {
        const riskSection = button.closest('.risk-section');
        const risksContainer = riskSection.closest('.risks-container');
        if (riskSection.parentNode.querySelectorAll('.risk-section').length > 1) {
            riskSection.remove();
            reindexRisks(risksContainer); // Reindex all risks
            updateNameAttributes(riskSection.closest('.objective-card'), getObjectiveIndex(riskSection.closest('.objective-card')));
        }
    }

    // Reindex risks within an objective
    function reindexRisks(risksContainer) {
        const riskSections = risksContainer.querySelectorAll('.risk-section');
        riskSections.forEach((section, index) => {
            const header = section.querySelector('h6');
            if (header) {
                header.textContent = `Risk ${index + 1}`;
            }
        });
    }

    function addActivity(button) {
        const activitiesTable = button.closest('.activities-container').querySelector('tbody');
        const activityRow = activitiesTable.querySelector('.activity-row').cloneNode(true);
        const activityTextarea = activityRow.querySelector('textarea.activity-description');
        const verificationTextarea = activityRow.querySelector('textarea.activity-verification');
        activityTextarea.value = '';
        verificationTextarea.value = '';
        
        // Add index number cell
        const indexCell = document.createElement('td');
        indexCell.style.cssText = 'text-align: center; vertical-align: middle;';
        indexCell.textContent = activitiesTable.children.length + 1;
        activityRow.insertBefore(indexCell, activityRow.firstChild);
        
        activitiesTable.appendChild(activityRow);

        const objectiveCard = button.closest('.objective-card');
        const timeFrameCard = objectiveCard.querySelector('.time-frame-card tbody');
        const timeFrameRow = timeFrameCard.querySelector('.activity-timeframe-row').cloneNode(true);
        timeFrameRow.querySelector('.activity-description-text').innerText = '';
        timeFrameRow.querySelectorAll('.month-checkbox').forEach(checkbox => checkbox.checked = false);
        timeFrameCard.appendChild(timeFrameRow);

        // Attach event listeners to all activity descriptions
        attachActivityEventListeners(objectiveCard);

        reindexActivities(activitiesTable); // Reindex all activities
        updateNameAttributes(objectiveCard, getObjectiveIndex(objectiveCard));
        
        // Initialize auto-resize for new textareas
        if (activityTextarea) {
            autoResizeTextarea(activityTextarea);
            activityTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }
        if (verificationTextarea) {
            autoResizeTextarea(verificationTextarea);
            verificationTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }
    }

    function removeActivity(button) {
        const row = button.closest('tr');
        const activitiesTable = row.closest('tbody');
        const activityIndex = Array.from(row.parentNode.children).indexOf(row);
        row.remove();

        const timeFrameCard = button.closest('.objective-card').querySelector('.time-frame-card tbody');
        const timeframeRow = timeFrameCard.children[activityIndex];
        timeframeRow.remove();

        const objectiveCard = button.closest('.objective-card');

        // Reindex activities
        reindexActivities(activitiesTable);
        
        // Update name attributes and reattach event listeners
        updateNameAttributes(objectiveCard, getObjectiveIndex(objectiveCard));
        attachActivityEventListeners(objectiveCard);
    }

    // Reindex activities within an objective
    function reindexActivities(activitiesTable) {
        const activityRows = activitiesTable.querySelectorAll('.activity-row');
        activityRows.forEach((row, index) => {
            const indexCell = row.querySelector('td:first-child');
            if (indexCell) {
                indexCell.textContent = index + 1;
            }
        });
    }

    function getObjectiveIndex(objectiveCard) {
        const objectives = Array.from(document.querySelectorAll('.objective-card'));
        return objectives.indexOf(objectiveCard);
    }

    function updateNameAttributes(objectiveCard, objectiveIndex) {
        objectiveCard.querySelector('textarea.objective-description').name = `objectives[${objectiveIndex}][objective]`;

        // Update the names for results
        const results = objectiveCard.querySelectorAll('.result-section');
        results.forEach((result, resultIndex) => {
            result.querySelector('textarea.result-outcome').name = `objectives[${objectiveIndex}][results][${resultIndex}][result]`;
        });

        // Update the names for risks
        const risks = objectiveCard.querySelectorAll('.risks-container .risk-section');
        risks.forEach((riskSection, riskIndex) => {
            const riskTextarea = riskSection.querySelector('textarea.risk-description');
            riskTextarea.name = `objectives[${objectiveIndex}][risks][${riskIndex}][risk]`;
        });

        // Update the names for activities and their timeframes
        const activities = objectiveCard.querySelectorAll('.activities-table .activity-row');
        activities.forEach((activityRow, activityIndex) => {
            activityRow.querySelector('textarea.activity-description').name = `objectives[${objectiveIndex}][activities][${activityIndex}][activity]`;
            activityRow.querySelector('textarea.activity-verification').name = `objectives[${objectiveIndex}][activities][${activityIndex}][verification]`;

            // Update the timeframe for this activity
            const timeFrameRow = objectiveCard.querySelectorAll('.time-frame-card tbody .activity-timeframe-row')[activityIndex];
            if (timeFrameRow) {
                // Update the activity description
                timeFrameRow.querySelector('.activity-description-text').innerText = activityRow.querySelector('textarea.activity-description').value;

                // Update the names for the checkboxes
                timeFrameRow.querySelectorAll('.month-checkbox').forEach((checkbox, monthIndex) => {
                    checkbox.name = `objectives[${objectiveIndex}][activities][${activityIndex}][timeframe][months][${monthIndex + 1}]`;
                });
            }
        });
    }

    function updateObjectiveNumbers() {
        const objectives = document.querySelectorAll('.objective-card');
        objectives.forEach((objective, index) => {
            objective.querySelector('h5').innerText = `Objective ${index + 1}`;
            updateNameAttributes(objective, index);
        });
    }

    function addTimeFrameRow(button) {
        const timeFrameCard = button.closest('.time-frame-card');
        const tbody = timeFrameCard.querySelector('tbody');
        const newRow = tbody.querySelector('.activity-timeframe-row').cloneNode(true);

        // Add index number cell
        const indexCell = document.createElement('td');
        indexCell.style.cssText = 'text-align: center; vertical-align: middle;';
        indexCell.textContent = tbody.children.length + 1;
        newRow.insertBefore(indexCell, newRow.firstChild);

        // Clear the contents of the new row
        const textarea = newRow.querySelector('.activity-description-text textarea');
        if (textarea) {
            textarea.value = '';
            // Initialize auto-resize for new textarea
            autoResizeTextarea(textarea);
            textarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        } else {
            // Fallback if structure is different
            newRow.querySelector('.activity-description-text').innerText = '';
        }
        newRow.querySelectorAll('.month-checkbox').forEach(checkbox => checkbox.checked = false);

        tbody.appendChild(newRow);

        reindexTimeFrameRows(tbody); // Reindex all timeframe rows
        updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
    }

    function removeTimeFrameRow(button) {
        const row = button.closest('tr');
        const tbody = row.closest('tbody');
        row.remove();
        reindexTimeFrameRows(tbody); // Reindex all timeframe rows
        updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
    }

    // Reindex timeframe rows within an objective
    function reindexTimeFrameRows(tbody) {
        const timeframeRows = tbody.querySelectorAll('.activity-timeframe-row');
        timeframeRows.forEach((row, index) => {
            const indexCell = row.querySelector('td:first-child');
            if (indexCell) {
                indexCell.textContent = index + 1;
            }
        });
    }

    function attachActivityEventListeners(objectiveCard) {
        const activitiesTable = objectiveCard.querySelector('.activities-table tbody');
        const timeFrameCard = objectiveCard.querySelector('.time-frame-card tbody');
        const activityRows = activitiesTable.querySelectorAll('.activity-row');

        activityRows.forEach(function(activityRow, index) {
            const activityDescriptionTextarea = activityRow.querySelector('textarea.activity-description');

            // Remove any existing event listener to prevent duplication
            if (activityDescriptionTextarea._listener) {
                activityDescriptionTextarea.removeEventListener('input', activityDescriptionTextarea._listener);
            }

            // Define the event listener function
            const eventListener = function() {
                const activityDescription = this.value;
                const timeFrameRow = timeFrameCard.querySelectorAll('.activity-timeframe-row')[index];
                if (timeFrameRow) {
                    timeFrameRow.querySelector('.activity-description-text').innerText = activityDescription;
                }
            };

            // Attach the event listener
            activityDescriptionTextarea.addEventListener('input', eventListener);

            // Store a reference to the event listener function for future removal
            activityDescriptionTextarea._listener = eventListener;
        });
    }

    // Auto-resize function for logical framework textareas
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }

    // Initialize auto-resize for all logical framework textareas
    function initializeLogicalTextareas() {
        const logicalTextareas = document.querySelectorAll('.logical-textarea');
        logicalTextareas.forEach(textarea => {
            // Set initial height
            autoResizeTextarea(textarea);
            
            // Auto-resize on input
            textarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        });
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeLogicalTextareas();
    });

    // Re-initialize after adding new elements (using event delegation)
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('logical-textarea')) {
            autoResizeTextarea(e.target);
        }
    });
    </script>

{{--
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
    timeFrameCard.querySelectorAll('.activity-timeframe-row .activity-description-text').forEach(span => span.innerText = '');
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
    const riskTemplate = risksContainer.querySelector('.risk-section').cloneNode(true);
    riskTemplate.querySelector('textarea.risk-description').value = '';

    // Append the new risk section before the "Add Risk" button
    risksContainer.insertBefore(riskTemplate, button);

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
    const activitiesTable = button.closest('.activities-container').querySelector('tbody');
    const activityRow = activitiesTable.querySelector('.activity-row').cloneNode(true);
    activityRow.querySelector('textarea.activity-description').value = '';
    activityRow.querySelector('textarea.activity-verification').value = '';
    activitiesTable.appendChild(activityRow);

    const objectiveCard = button.closest('.objective-card');
    const timeFrameCard = objectiveCard.querySelector('.time-frame-card tbody');
    const timeFrameRow = timeFrameCard.querySelector('.activity-timeframe-row').cloneNode(true);
    timeFrameRow.querySelector('.activity-description-text').innerText = '';
    timeFrameRow.querySelectorAll('.month-checkbox').forEach(checkbox => checkbox.checked = false);
    timeFrameCard.appendChild(timeFrameRow);

    // Update the activity description in the timeframe table when the activity description changes
    activityRow.querySelector('textarea.activity-description').addEventListener('input', function() {
        const index = Array.from(activitiesTable.querySelectorAll('.activity-row')).indexOf(activityRow);
        timeFrameCard.querySelectorAll('.activity-timeframe-row')[index].querySelector('.activity-description-text').innerText = this.value;
    });

    updateNameAttributes(objectiveCard, getObjectiveIndex(objectiveCard));
}

function removeActivity(button) {
    const row = button.closest('tr');
    const activityIndex = Array.from(row.parentNode.children).indexOf(row);
    row.remove();

    const timeFrameCard = button.closest('.objective-card').querySelector('.time-frame-card tbody');
    const timeframeRow = timeFrameCard.children[activityIndex];
    timeframeRow.remove();

    updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
}

function getObjectiveIndex(objectiveCard) {
    const objectives = Array.from(document.querySelectorAll('.objective-card'));
    return objectives.indexOf(objectiveCard);
}

function updateNameAttributes(objectiveCard, objectiveIndex) {
    objectiveCard.querySelector('textarea.objective-description').name = `objectives[${objectiveIndex}][objective]`;

    // Update the names for results
    const results = objectiveCard.querySelectorAll('.result-section');
    results.forEach((result, resultIndex) => {
        result.querySelector('textarea.result-outcome').name = `objectives[${objectiveIndex}][results][${resultIndex}][result]`;
    });

    // Update the names for risks
    const risks = objectiveCard.querySelectorAll('.risks-container .risk-section');
    risks.forEach((riskSection, riskIndex) => {
        const riskTextarea = riskSection.querySelector('textarea.risk-description');
        riskTextarea.name = `objectives[${objectiveIndex}][risks][${riskIndex}][risk]`;
    });

    // Update the names for activities and their timeframes
    const activities = objectiveCard.querySelectorAll('.activities-table .activity-row');
    activities.forEach((activityRow, activityIndex) => {
        activityRow.querySelector('textarea.activity-description').name = `objectives[${objectiveIndex}][activities][${activityIndex}][activity]`;
        activityRow.querySelector('textarea.activity-verification').name = `objectives[${objectiveIndex}][activities][${activityIndex}][verification]`;

        // Update the timeframe for this activity
        const timeFrameRow = objectiveCard.querySelectorAll('.time-frame-card tbody .activity-timeframe-row')[activityIndex];
        if (timeFrameRow) {
            // Update the activity description (if needed)
            timeFrameRow.querySelector('.activity-description-text').innerText = activityRow.querySelector('textarea.activity-description').value;

            // Update the names for the checkboxes
            timeFrameRow.querySelectorAll('.month-checkbox').forEach((checkbox, monthIndex) => {
                checkbox.name = `objectives[${objectiveIndex}][activities][${activityIndex}][timeframe][months][${monthIndex + 1}]`;
            });
        }
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

    updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
}

function removeTimeFrameRow(button) {
    const row = button.closest('tr');
    row.remove();
    updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
}

</script> --}}
