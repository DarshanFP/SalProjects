{{-- resources/views/projects/partials/scripts-edit.blade.php --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Bind change event handler to update mobile and email on select change
        const inChargeSelect = document.getElementById('in_charge');
        inChargeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const mobile = selectedOption.getAttribute('data-mobile');
            const email = selectedOption.getAttribute('data-email');

            document.getElementById('in_charge_mobile').value = mobile || '';
            document.getElementById('in_charge_email').value = email || '';
        });

        // Update the phase options based on the selected overall project period
        const overallProjectPeriodElement = document.getElementById('overall_project_period');
        const phaseSelectElement = document.getElementById('current_phase');

        // Save the currently selected phase before updating the options
        let currentSelectedPhase = phaseSelectElement.value;

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

        // Attach the addPhase function to the Add Phase button
        const addPhaseButton = document.getElementById('addPhaseButton');
        if (addPhaseButton) {
            addPhaseButton.addEventListener('click', addPhase);
        }

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
            checkbox.value = '1'; // Ensure value is set to '1'
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
            checkbox.value = '1'; // Ensure value is set to '1'
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

    // Calculate the budget totals for a single budget row
    function calculateBudgetRowTotals(element) {
        const row = element.closest('tr');
        const rateQuantity = parseFloat(row.querySelector('[name$="[rate_quantity]"]').value) || 0;
        const rateMultiplier = parseFloat(row.querySelector('[name$="[rate_multiplier]"]').value) || 1;
        const rateDuration = parseFloat(row.querySelector('[name$="[rate_duration]"]').value) || 1;
        const rateIncrease = parseFloat(row.querySelector('[name$="[rate_increase]"]').value) || 0;

        const thisPhase = rateQuantity * rateMultiplier * rateDuration;
        let nextPhase = 0;

        const projectPeriod = parseInt(document.getElementById('overall_project_period').value);
        if (projectPeriod !== 1) {
            nextPhase = (rateQuantity + rateIncrease) * rateMultiplier * rateDuration;
        }

        row.querySelector('[name$="[this_phase]"]').value = thisPhase.toFixed(2);
        row.querySelector('[name$="[next_phase]"]').value = nextPhase.toFixed(2);

        calculateBudgetTotals(row.closest('.phase-card'));
    }

    // Update all budget rows based on the selected project period
    function updateAllBudgetRows() {
        const phases = document.querySelectorAll('.phase-card');
        phases.forEach(phase => {
            const rows = phase.querySelectorAll('.budget-rows tr');
            rows.forEach(row => {
                calculateBudgetRowTotals(row.querySelector('input'));
            });
        });
    }

    // Calculate the total budget for a phase
    function calculateBudgetTotals(phaseCard) {
        const rows = phaseCard.querySelectorAll('.budget-rows tr');
        let totalRateQuantity = 0;
        let totalRateMultiplier = 0;
        let totalRateDuration = 0;
        let totalRateIncrease = 0;
        let totalThisPhase = 0;
        let totalNextPhase = 0;

        rows.forEach(row => {
            totalRateQuantity += parseFloat(row.querySelector('[name$="[rate_quantity]"]').value) || 0;
            totalRateMultiplier += parseFloat(row.querySelector('[name$="[rate_multiplier]"]').value) || 1;
            totalRateDuration += parseFloat(row.querySelector('[name$="[rate_duration]"]').value) || 1;
            totalRateIncrease += parseFloat(row.querySelector('[name$="[rate_increase]"]').value) || 0;
            totalThisPhase += parseFloat(row.querySelector('[name$="[this_phase]"]').value) || 0;
            totalNextPhase += parseFloat(row.querySelector('[name$="[next_phase]"]').value) || 0;
        });

        phaseCard.querySelector('.total_rate_quantity').value = totalRateQuantity.toFixed(2);
        phaseCard.querySelector('.total_rate_multiplier').value = totalRateMultiplier.toFixed(2);
        phaseCard.querySelector('.total_rate_duration').value = totalRateDuration.toFixed(2);
        phaseCard.querySelector('.total_rate_increase').value = totalRateIncrease.toFixed(2);
        phaseCard.querySelector('.total_this_phase').value = totalThisPhase.toFixed(2);
        phaseCard.querySelector('.total_next_phase').value = totalNextPhase.toFixed(2);

        calculateTotalAmountSanctioned();
    }

    // Calculate the total amount sanctioned and update the overall project budget
    function calculateTotalAmountSanctioned() {
        const phases = document.querySelectorAll('.phase-card');
        let totalAmount = 0;
        let totalNextPhase = 0;

        phases.forEach((phase, index) => {
            const thisPhaseTotal = parseFloat(phase.querySelector('.total_this_phase').value) || 0;
            phase.querySelector('[name^="phases"][name$="[amount_sanctioned]"]').value = thisPhaseTotal.toFixed(2);

            if (index > 0) {
                const amountForwarded = parseFloat(phase.querySelector('[name^="phases"][name$="[amount_forwarded]"]').value) || 0;
                const openingBalance = amountForwarded + thisPhaseTotal;
                phase.querySelector('[name^="phases"][name$="[opening_balance]"]').value = openingBalance.toFixed(2);
            }

            totalAmount += thisPhaseTotal;
        });

        const lastPhase = phases[phases.length - 1];
        const rows = lastPhase.querySelectorAll('.budget-rows tr');
        rows.forEach(row => {
            totalNextPhase += parseFloat(row.querySelector('[name$="[next_phase]"]').value) || 0;
        });

        document.querySelector('[name="total_amount_sanctioned"]').value = totalAmount.toFixed(2);
        document.getElementById('overall_project_budget').value = (totalAmount + totalNextPhase).toFixed(2);
    }

    // Add a new budget row to the phase card
    function addBudgetRow(button) {
        const tableBody = button.closest('.phase-card').querySelector('.budget-rows');
        const phaseIndex = button.closest('.phase-card').dataset.phase;
        const newRow = document.createElement('tr');

        newRow.innerHTML = `
            <td><input type="text" name="phases[${phaseIndex}][budget][${tableBody.children.length}][particular]" class="form-control" required></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_quantity]" class="form-control" oninput="calculateBudgetRowTotals(this)" required></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_multiplier]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" required></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_duration]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" required></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_increase]" class="form-control" value="0.00" oninput="calculateBudgetRowTotals(this)" required></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][this_phase]" class="form-control" readonly></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][next_phase]" class="form-control" readonly></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
        `;

        tableBody.appendChild(newRow);
        calculateBudgetTotals(tableBody.closest('.phase-card'));
    }

    // Remove a budget row from the phase card
    function removeBudgetRow(button) {
        const row = button.closest('tr');
        const phaseCard = row.closest('.phase-card');
        row.remove();
        calculateBudgetTotals(phaseCard);
    }

    // Add a new phase card
    function addPhase() {
        const phasesContainer = document.getElementById('phases-container');
        const currentPhaseCount = phasesContainer.querySelectorAll('.phase-card').length;
        const newPhaseIndex = currentPhaseCount;

        const newPhase = document.createElement('div');
        newPhase.className = 'phase-card';
        newPhase.dataset.phase = newPhaseIndex;

        newPhase.innerHTML = `
            <div class="card-header">
                <h4>Phase ${newPhaseIndex + 1}</h4>
            </div>
            ${newPhaseIndex > 0 ? `
            <div class="mb-3">
                <label for="phases[${newPhaseIndex}][amount_forwarded]" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
                <input type="number" name="phases[${newPhaseIndex}][amount_forwarded]" class="form-control" oninput="calculateBudgetTotals(this.closest('.phase-card'))">
            </div>
            ` : ''}
            <div class="mb-3">
                <label for="phases[${newPhaseIndex}][amount_sanctioned]" class="form-label">Amount Sanctioned in Phase ${newPhaseIndex + 1}: Rs.</label>
                <input type="number" name="phases[${newPhaseIndex}][amount_sanctioned]" class="form-control" readonly>
            </div>
            <div class="mb-3">
                <label for="phases[${newPhaseIndex}][opening_balance]" class="form-label">Opening balance in Phase ${newPhaseIndex + 1}: Rs.</label>
                <input type="number" name="phases[${newPhaseIndex}][opening_balance]" class="form-control" readonly>
            </div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Particular</th>
                        <th>Costs</th>
                        <th>Rate Multiplier</th>
                        <th>Rate Duration</th>
                        <th>Rate Increase (next phase)</th>
                        <th>This Phase (Auto)</th>
                        <th>Next Phase (Auto)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody class="budget-rows">
                    <tr>
                        <td><input type="text" name="phases[${newPhaseIndex}][budget][0][particular]" class="form-control" required></td>
                        <td><input type="number" name="phases[${newPhaseIndex}][budget][0][rate_quantity]" class="form-control" oninput="calculateBudgetRowTotals(this)" required></td>
                        <td><input type="number" name="phases[${newPhaseIndex}][budget][0][rate_multiplier]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" required></td>
                        <td><input type="number" name="phases[${newPhaseIndex}][budget][0][rate_duration]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" required></td>
                        <td><input type="number" name="phases[${newPhaseIndex}][budget][0][rate_increase]" class="form-control" value="0.00" oninput="calculateBudgetRowTotals(this)" required></td>
                        <td><input type="number" name="phases[${newPhaseIndex}][budget][0][this_phase]" class="form-control" readonly></td>
                        <td><input type="number" name="phases[${newPhaseIndex}][budget][0][next_phase]" class="form-control" readonly></td>
                        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th><input type="number" class="total_rate_quantity form-control" readonly></th>
                        <th><input type="number" class="total_rate_multiplier form-control" readonly></th>
                        <th><input type="number" class="total_rate_duration form-control" readonly></th>
                        <th><input type="number" class="total_rate_increase form-control" readonly></th>
                        <th><input type="number" class="total_this_phase form-control" readonly></th>
                        <th><input type="number" class="total_next_phase form-control" readonly></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
            <button type="button" class="btn btn-primary" onclick="addBudgetRow(this)">Add Row</button>
            <div>
                <button type="button" class="mt-3 btn btn-danger" onclick="removePhase(this)">Remove Phase</button>
            </div>
        `;

        phasesContainer.appendChild(newPhase);
        calculateTotalAmountSanctioned();
    }

    // Remove a phase card
    function removePhase(button) {
        const phaseCard = button.closest('.phase-card');
        phaseCard.remove();
        calculateTotalAmountSanctioned();
    }

    // Add a new attachment field
    function addAttachment() {
        const attachmentsContainer = document.getElementById('attachments-container');
        const currentAttachments = attachmentsContainer.querySelectorAll('.attachment-group').length;

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
        });
    }
    </script>
