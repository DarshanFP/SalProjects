{{-- resources/views/projects/partials/scripts-edit.blade.php --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Bind change event handler to update mobile and email on select change
        const inChargeSelect = document.getElementById('in_charge');
        if (inChargeSelect) {
            inChargeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const mobile = selectedOption.getAttribute('data-mobile');
                const email = selectedOption.getAttribute('data-email');

                document.getElementById('in_charge_mobile').value = mobile || '';
                document.getElementById('in_charge_email').value = email || '';
            });
        }

        // Update the phase options based on the selected overall project period
        const overallProjectPeriodElement = document.getElementById('overall_project_period');
        const phaseSelectElement = document.getElementById('current_phase');

        // Save the currently selected phase before updating the options
        let currentSelectedPhase = phaseSelectElement ? phaseSelectElement.value : null;

        if (overallProjectPeriodElement) {
            overallProjectPeriodElement.addEventListener('change', function() {
                const projectPeriod = parseInt(this.value);

                // Clear previous options
                phaseSelectElement.innerHTML = '<option value="" disabled>Select Phase</option>';

                // Add new options based on the selected value
                for (let i = 1; i <= projectPeriod; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.text = `${i}${i === 1 ? 'st' : i === 2 ? 'nd' : i === 3 ? 'rd' : 'th'} Phase`;

                    // If this option matches the previously selected phase, reselect it
                    if (i == currentSelectedPhase) {
                        option.selected = true;
                    }

                    phaseSelectElement.appendChild(option);
                }

                // Update all budget rows based on the selected project period
                updateAllBudgetRows();
            });

            // Manually trigger the change event once on page load to initialize the dropdown correctly
            overallProjectPeriodElement.dispatchEvent(new Event('change'));
        }

        // Attach the addPhase function to the Add Phase button - COMMENTED OUT TO DISABLE PHASE FUNCTIONALITY
        /*
        const addPhaseButton = document.getElementById('addPhaseButton');
        if (addPhaseButton) {
            addPhaseButton.addEventListener('click', addPhase);
        }
        */

        // Initialize objective count and name attributes for existing objectives
        initializeObjectives();

        // Call calculateTotalAmountSanctioned initially to set up the correct values on page load - COMMENTED OUT TO DISABLE PHASE FUNCTIONALITY
        // calculateTotalAmountSanctioned();

        // Initialize budget calculations when page loads
        calculateTotalAmountSanctioned();
    });

    // Variables
    let objectiveCount = document.querySelectorAll('.objective-card').length || 0;

    // Initialize objectives on page load
    function initializeObjectives() {
        const container = document.getElementById('objectives-container');
        if (!container) {
            // If objectives-container doesn't exist (e.g., for IAH projects), skip initialization
            return;
        }

        const objectives = document.querySelectorAll('.objective-card');
        if (objectives.length === 0) {
            // If no objectives exist, add one
            addObjective();
        } else {
            objectives.forEach((objectiveCard, index) => {
                updateNameAttributes(objectiveCard, index);
                // Sync initial activity descriptions to time frame rows
                syncActivityDescriptionsToTimeFrame(objectiveCard);
                // Attach event listeners for future changes
                attachActivityEventListeners(objectiveCard);
            });
        }

        // Move the "Add Objective" button to the end
        moveAddObjectiveButton();
    }

    // Sync activity descriptions to time frame rows on initial load
    function syncActivityDescriptionsToTimeFrame(objectiveCard) {
        const activitiesTable = objectiveCard.querySelector('.activities-table tbody');
        const timeFrameTbody = objectiveCard.querySelector('.time-frame-card tbody');
        if (!activitiesTable || !timeFrameTbody) return;

        const activityRows = activitiesTable.querySelectorAll('.activity-row');
        const timeFrameRows = timeFrameTbody.querySelectorAll('.activity-timeframe-row');

        activityRows.forEach((activityRow, index) => {
            const activityDescription = activityRow.querySelector('textarea.activity-description').value;
            const timeFrameRow = timeFrameRows[index];
            if (timeFrameRow && activityDescription) {
                const descriptionText = timeFrameRow.querySelector('.activity-description-text');
                if (descriptionText) {
                    descriptionText.innerText = activityDescription;
                }
            }
        });
    }

    // Add a new objective card
    function addObjective() {
        const container = document.getElementById('objectives-container');
        if (!container) {
            console.warn('objectives-container not found. Skipping addObjective.');
            return;
        }

        const existingObjective = document.querySelector('.objective-card');

        let template;

        if (existingObjective) {
            // Clone the existing objective card
            template = existingObjective.cloneNode(true);

            // Clear values and reset the cloned objective card
            resetFormValues(template);

            // Reset risks, results, and activities to only one empty row each
            resetObjectiveSections(template);
        } else {
            // No existing objective to clone, create a new one
            template = createNewObjectiveCard();
        }

        // Update the header and the name attributes
        template.querySelector('h5').innerText = `Objective ${++objectiveCount}`;

        // Update the name attributes for the new objective
        updateNameAttributes(template, objectiveCount - 1);

        // Append the new objective card to the container
        container.appendChild(template);

        // Move the "Add Objective" button to the end
        moveAddObjectiveButton();

        // Attach event listeners to synchronize activities and time frames
        attachActivityEventListeners(template);
    }

    // Move the "Add Objective" button to the end of the container
    function moveAddObjectiveButton() {
        const addObjectiveButton = document.getElementById('addObjectiveButton');
        const container = document.getElementById('objectives-container');

        if (!container || !addObjectiveButton) {
            return;
        }

        container.appendChild(addObjectiveButton);
    }

    // Create a new objective card from scratch
    function createNewObjectiveCard() {
        const template = document.createElement('div');
        template.className = 'mb-3 objective-card';

        template.innerHTML = `
            <div class="objective-header d-flex justify-content-between align-items-center">
                <h5></h5>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeObjective(this)">Remove Objective</button>
            </div>
            <textarea name="" class="mb-3 form-control objective-description" rows="2" placeholder="Enter Objective" ></textarea>

            <div class="results-container">
                <h6>Results</h6>
                <!-- Results Section -->
                <div class="mb-3 result-section">
                    <textarea name="" class="mb-3 form-control result-outcome" rows="2" placeholder="Enter Result" ></textarea>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                </div>
                <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>
            </div>

            <div class="risks-container">
                <h6>Risks</h6>
                <!-- Risks Section -->
                <div class="mb-3 risk-section">
                    <textarea name="" class="mb-3 form-control risk-description" rows="2" placeholder="Enter Risk"  ></textarea>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                </div>
                <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this)">Add Risk</button>
            </div>

            <div class="activities-container">
                <h6>Activities and Means of Verification</h6>
                <div class="table-responsive">
                    <table class="table table-bordered activities-table">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 40%;">Activities</th>
                                <th scope="col" style="width: 50%;">Means of Verification</th>
                                <th scope="col" style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Activity Row -->
                            <tr class="activity-row">
                                <td style="word-wrap: break-word; overflow-wrap: break-word;">
                                    <textarea name="" class="form-control activity-description" rows="2" placeholder="Enter Activity" style="width: 100%; box-sizing: border-box; resize: vertical;"></textarea>
                                </td>
                                <td style="word-wrap: break-word; overflow-wrap: break-word;">
                                    <textarea name="" class="form-control activity-verification" rows="2" placeholder="Means of Verification" style="width: 100%; box-sizing: border-box; resize: vertical;"></textarea>
                                </td>
                                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeActivity(this)">Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="mb-3 btn btn-primary" onclick="addActivity(this)">Add Activity</button>
            </div>

            <!-- Time Frame Section -->
            <div class="mt-4 card time-frame-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6>Time Frame for Activities</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 40%;">Activities</th>
                                ${['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'].map(month => `<th scope="col">${month}</th>`).join('')}
                                <th scope="col" style="width: 6%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="activity-timeframe-row">
                                <td class="activity-description-text"></td>
                                ${Array(12).fill(0).map(() => `<td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name=""></td>`).join('')}
                                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeTimeFrameRow(this)">Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        return template;
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
            <textarea name="" class="mb-3 form-control result-outcome" rows="2" placeholder="Enter Result"  ></textarea>
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
            <textarea name="" class="mb-3 form-control risk-description" rows="2" placeholder="Enter Risk"  ></textarea>
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
            <td style="word-wrap: break-word; overflow-wrap: break-word;">
                <textarea name="" class="form-control activity-description" rows="2" placeholder="Enter Activity" style="width: 100%; box-sizing: border-box; resize: vertical;"></textarea>
            </td>
            <td style="word-wrap: break-word; overflow-wrap: break-word;">
                <textarea name="" class="form-control activity-verification" rows="2" placeholder="Means of Verification" style="width: 100%; box-sizing: border-box; resize: vertical;"></textarea>
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
            timeFrameRow.innerHTML = `
                <td class="activity-description-text"></td>
                ${Array(12).fill(0).map(() => `<td class="text-center"><input type="checkbox" class="month-checkbox" value="1" name=""></td>`).join('')}
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeTimeFrameRow(this)">Remove</button></td>
            `;
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
        const timeFrameRows = objectiveCard.querySelectorAll('.time-frame-card tbody .activity-timeframe-row');

        activities.forEach((activityRow, activityIndex) => {
            const activityDescriptionTextarea = activityRow.querySelector('textarea.activity-description');
            const activityVerificationTextarea = activityRow.querySelector('textarea.activity-verification');

            if (activityDescriptionTextarea) {
                activityDescriptionTextarea.name = `objectives[${objectiveIndex}][activities][${activityIndex}][activity]`;
            }
            if (activityVerificationTextarea) {
                activityVerificationTextarea.name = `objectives[${objectiveIndex}][activities][${activityIndex}][verification]`;
            }

            // Update the timeframe for this activity if applicable
            const timeFrameRow = timeFrameRows[activityIndex];
            if (timeFrameRow) {
                const descriptionText = timeFrameRow.querySelector('.activity-description-text');
                if (descriptionText && activityDescriptionTextarea) {
                    // Sync the activity description to the time frame row
                    descriptionText.innerText = activityDescriptionTextarea.value;
                }

                // Update checkbox names
                timeFrameRow.querySelectorAll('.month-checkbox').forEach((checkbox, monthIndex) => {
                    checkbox.name = `objectives[${objectiveIndex}][activities][${activityIndex}][timeframe][months][${monthIndex + 1}]`;
                });
            }
        });
    }

    // Attach event listeners to synchronize activity descriptions with time frames
    function attachActivityEventListeners(objectiveCard) {
        const activitiesTable = objectiveCard.querySelector('.activities-table tbody');
        const timeFrameTbody = objectiveCard.querySelector('.time-frame-card tbody');
        if (!activitiesTable || !timeFrameTbody) return;

        const activityRows = activitiesTable.querySelectorAll('.activity-row');

        activityRows.forEach(function(activityRow, index) {
            const activityDescriptionTextarea = activityRow.querySelector('textarea.activity-description');
            if (!activityDescriptionTextarea) return;

            // Remove existing event listener if any
            if (activityDescriptionTextarea._listener) {
                activityDescriptionTextarea.removeEventListener('input', activityDescriptionTextarea._listener);
            }

            // Define the event listener function
            const eventListener = function() {
                const activityDescription = this.value;
                const timeFrameRows = timeFrameTbody.querySelectorAll('.activity-timeframe-row');
                const timeFrameRow = timeFrameRows[index];
                if (timeFrameRow) {
                    const descriptionText = timeFrameRow.querySelector('.activity-description-text');
                    if (descriptionText) {
                        descriptionText.innerText = activityDescription;
                    }
                }
            };

            // Attach the event listener
            activityDescriptionTextarea.addEventListener('input', eventListener);

            // Store a reference to the event listener function
            activityDescriptionTextarea._listener = eventListener;
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
        const objectiveCard = button.closest('.objective-card');
        const activitiesTableBody = button.closest('.activities-container').querySelector('tbody');
        const lastActivityRow = activitiesTableBody.querySelector('.activity-row:last-of-type');
        const newActivityRow = lastActivityRow.cloneNode(true);

        // Clear the textarea values and apply proper styling
        const activityDesc = newActivityRow.querySelector('textarea.activity-description');
        const activityVerif = newActivityRow.querySelector('textarea.activity-verification');

        if (activityDesc) {
            activityDesc.value = '';
            activityDesc.style.width = '100%';
            activityDesc.style.boxSizing = 'border-box';
            activityDesc.style.resize = 'vertical';
        }

        if (activityVerif) {
            activityVerif.value = '';
            activityVerif.style.width = '100%';
            activityVerif.style.boxSizing = 'border-box';
            activityVerif.style.resize = 'vertical';
        }

        // Ensure table cells have proper wrapping styles
        const cells = newActivityRow.querySelectorAll('td');
        cells.forEach(cell => {
            cell.style.wordWrap = 'break-word';
            cell.style.overflowWrap = 'break-word';
            cell.style.wordBreak = 'break-word';
        });

        // Append the new activity row
        activitiesTableBody.appendChild(newActivityRow);

        // Add corresponding time frame row
        const timeFrameTbody = objectiveCard.querySelector('.time-frame-card tbody');
        if (!timeFrameTbody) {
            console.error('Time frame tbody not found');
            return;
        }

        const lastTimeFrameRow = timeFrameTbody.querySelector('.activity-timeframe-row:last-of-type');
        if (!lastTimeFrameRow) {
            console.error('No existing time frame row found to clone');
            return;
        }

        const newTimeFrameRow = lastTimeFrameRow.cloneNode(true);

        // Clear the activity description and checkboxes
        const descriptionText = newTimeFrameRow.querySelector('.activity-description-text');
        if (descriptionText) {
            descriptionText.innerText = '';
        }
        newTimeFrameRow.querySelectorAll('.month-checkbox').forEach(checkbox => {
            checkbox.checked = false;
            checkbox.name = '';
            checkbox.value = '1'; // Ensure value is set to '1'
        });

        // Append the new time frame row
        timeFrameTbody.appendChild(newTimeFrameRow);

        // Update name attributes
        const objectiveIndex = getObjectiveIndex(objectiveCard);
        updateNameAttributes(objectiveCard, objectiveIndex);

        // Reattach event listeners
        attachActivityEventListeners(objectiveCard);
    }

    // Remove Activity
    function removeActivity(button) {
        const activityRow = button.closest('.activity-row');
        const activitiesTableBody = activityRow.parentNode;

        // Ensure at least one activity row remains
        if (activitiesTableBody.querySelectorAll('.activity-row').length > 1) {
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
                const timeFrameDescription = timeFrameRow.querySelector('.activity-description-text').innerText;
                if (timeFrameDescription === activityDescription) {
                    matchingTimeFrameRow = timeFrameRow;
                }
            });

            // If no exact match found, remove the timeframe row at the same index as the removed activity
            if (!matchingTimeFrameRow) {
                const activityIndex = Array.from(activitiesTableBody.children).indexOf(activityRow);
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

    // Remove Objective
    function removeObjective(button) {
        const objectiveCard = button.closest('.objective-card');
        const container = document.getElementById('objectives-container');

        // Remove the objective card
        objectiveCard.remove();

        objectiveCount--;
        updateObjectiveNumbers();

        // Move the "Add Objective" button to the end
        moveAddObjectiveButton();
    }

    // Update Objective Numbers
    function updateObjectiveNumbers() {
        const objectives = document.querySelectorAll('.objective-card');
        objectiveCount = objectives.length;
        if (objectiveCount === 0) {
            objectiveCount = 0;
            addObjective(); // Ensure at least one objective exists
        } else {
            objectives.forEach((objective, index) => {
                objective.querySelector('h5').innerText = `Objective ${index + 1}`;
                updateNameAttributes(objective, index);
            });
        }
    }

    // Add Time Frame Row
    function addTimeFrameRow(button) {
        const objectiveCard = button.closest('.objective-card');
        const timeFrameTbody = objectiveCard.querySelector('.time-frame-card tbody');
        if (!timeFrameTbody) {
            console.error('Time frame tbody not found');
            return;
        }

        const lastTimeFrameRow = timeFrameTbody.querySelector('.activity-timeframe-row:last-of-type');
        if (!lastTimeFrameRow) {
            console.error('No existing time frame row found to clone');
            return;
        }

        const newTimeFrameRow = lastTimeFrameRow.cloneNode(true);

        // Clear the activity description and checkboxes
        const descriptionText = newTimeFrameRow.querySelector('.activity-description-text');
        if (descriptionText) {
            descriptionText.innerText = '';
        }
        newTimeFrameRow.querySelectorAll('.month-checkbox').forEach(checkbox => {
            checkbox.checked = false;
            checkbox.name = '';
            checkbox.value = '1';
        });

        // Append the new time frame row
        timeFrameTbody.appendChild(newTimeFrameRow);

        // Update name attributes
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

    // Calculate the budget totals for a single budget row - UPDATED FOR SINGLE PHASE
    function calculateBudgetRowTotals(element) {
        const row = element.closest('tr');
        const rateQuantityInput = row.querySelector('[name$="[rate_quantity]"]');
        const rateMultiplierInput = row.querySelector('[name$="[rate_multiplier]"]');
        const rateDurationInput = row.querySelector('[name$="[rate_duration]"]');

        const rateQuantity = parseFloat(rateQuantityInput.value) || 0;
        const rateMultiplier = parseFloat(rateMultiplierInput.value) || 1;
        const rateDuration = parseFloat(rateDurationInput.value) || 1;

        const thisPhase = rateQuantity * rateMultiplier * rateDuration;

        row.querySelector('[name$="[this_phase]"]').value = thisPhase.toFixed(2);

        calculateBudgetTotals();
    }

    // Update all budget rows based on the selected project period
    function updateAllBudgetRows() {
        const budgetRows = document.querySelectorAll('.budget-rows tr');
        budgetRows.forEach(row => {
            calculateBudgetRowTotals(row.querySelector('input'));
        });
    }

    // Calculate the total budget for a phase - UPDATED FOR SINGLE PHASE
    function calculateBudgetTotals() {
        const budgetRows = document.querySelectorAll('.budget-rows tr');
        let totalRateQuantity = 0;
        let totalRateMultiplier = 0;
        let totalRateDuration = 0;
        let totalThisPhase = 0;

        budgetRows.forEach(row => {
            totalRateQuantity += parseFloat(row.querySelector('[name$="[rate_quantity]"]').value) || 0;
            totalRateMultiplier += parseFloat(row.querySelector('[name$="[rate_multiplier]"]').value) || 0;
            totalRateDuration += parseFloat(row.querySelector('[name$="[rate_duration]"]').value) || 0;
            totalThisPhase += parseFloat(row.querySelector('[name$="[this_phase]"]').value) || 0;
        });

        // Update total row fields
        const totalRateQuantityField = document.querySelector('.total_rate_quantity');
        const totalRateMultiplierField = document.querySelector('.total_rate_multiplier');
        const totalRateDurationField = document.querySelector('.total_rate_duration');
        const totalThisPhaseField = document.querySelector('.total_this_phase');

        if (totalRateQuantityField) {
            totalRateQuantityField.value = totalRateQuantity.toFixed(2);
        }
        if (totalRateMultiplierField) {
            totalRateMultiplierField.value = totalRateMultiplier.toFixed(2);
        }
        if (totalRateDurationField) {
            totalRateDurationField.value = totalRateDuration.toFixed(2);
        }
        if (totalThisPhaseField) {
            totalThisPhaseField.value = totalThisPhase.toFixed(2);
        }

        calculateTotalAmountSanctioned();
    }

    // Calculate the total amount sanctioned and update the overall project budget - UPDATED FOR SINGLE PHASE
    function calculateTotalAmountSanctioned() {
        // Get all budget rows directly from the budget table
        const budgetRows = document.querySelectorAll('.budget-rows tr');
        let totalAmount = 0;

        // Calculate totals from all budget rows
        budgetRows.forEach(row => {
            const thisPhaseValue = parseFloat(row.querySelector('[name$="[this_phase]"]').value) || 0;
            totalAmount += thisPhaseValue;
        });

        // Update the total amount sanctioned field
        const totalAmountSanctionedField = document.querySelector('[name="total_amount_sanctioned"]');
        if (totalAmountSanctionedField) {
            totalAmountSanctionedField.value = totalAmount.toFixed(2);
        }

        // Update the total amount forwarded field (set to 0 for single phase)
        const totalAmountForwardedField = document.querySelector('[name="total_amount_forwarded"]');
        if (totalAmountForwardedField) {
            totalAmountForwardedField.value = '0.00';
        }

        // Update the overall project budget (same as total amount sanctioned for single phase)
        const overallProjectBudgetField = document.getElementById('overall_project_budget');
        if (overallProjectBudgetField) {
            overallProjectBudgetField.value = totalAmount.toFixed(2);
        }
    }

    // Add a new budget row to the budget table - UPDATED FOR SINGLE PHASE
    function addBudgetRow(button) {
        const tableBody = document.querySelector('.budget-rows');
        const phaseIndex = 0; // Since we only have one phase
        const newRow = document.createElement('tr');

        newRow.innerHTML = `
            <td><input type="text" name="phases[${phaseIndex}][budget][${tableBody.children.length}][particular]" class="form-control" style="background-color: #202ba3;"></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_quantity]" class="form-control" oninput="calculateBudgetRowTotals(this)" style="background-color: #202ba3;"></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_multiplier]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" style="background-color: #202ba3;"></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_duration]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" style="background-color: #202ba3;"></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][this_phase]" class="form-control readonly-input" readonly></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
        `;

        tableBody.appendChild(newRow);
        calculateTotalAmountSanctioned();
    }

    // Remove a budget row from the budget table
    function removeBudgetRow(button) {
        const row = button.closest('tr');
        row.remove();
        calculateTotalAmountSanctioned(); // Recalculate totals after removing a row
    }

    // Add a new attachment field
    function addAttachment() {
        const attachmentsContainer = document.getElementById('attachments-container');
        let currentAttachments = attachmentsContainer.querySelectorAll('.attachment-group').length;

        const index = currentAttachments;
        const attachmentTemplate = `
            <div class="mb-3 attachment-group" data-index="${index}">
                <label class="form-label">Attachment ${index + 1}</label>
                <input type="file" name="attachments[${index}][file]" class="mb-2 form-control" accept=".pdf,.doc,.docx,.xlsx">
                <input type="text" name="attachments[${index}][file_name]" class="form-control" placeholder="Name of File Attached">
                <textarea name="attachments[${index}][description]" class="form-control" rows="3" placeholder="Brief Description"></textarea>
                <button type="button" class="mt-2 btn btn-danger" onclick="removeAttachment(this)">Remove</button>
            </div>
        `;
        attachmentsContainer.insertAdjacentHTML('beforeend', attachmentTemplate);
        updateAttachmentLabels();
    }

    // Remove an attachment field
    function removeAttachment(button) {
        const attachmentGroup = button.closest('.attachment-group');
        attachmentGroup.remove();
        updateAttachmentLabels();
    }

    // Update the labels for the attachments
    function updateAttachmentLabels() {
        const attachmentGroups = document.querySelectorAll('.attachment-group');
        attachmentGroups.forEach((group, index) => {
            const label = group.querySelector('label');
            label.textContent = `Attachment ${index + 1}`;
            group.dataset.index = index;
            group.querySelectorAll('input, textarea').forEach(input => {
                if (input.name) {
                    const nameParts = input.name.split('[');
                    nameParts[1] = `${index}]`;
                    input.name = nameParts.join('[');
                }
            });
        });
    }
</script>
