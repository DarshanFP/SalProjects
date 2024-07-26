document.addEventListener('DOMContentLoaded', function() {
    // Update the mobile and email fields based on the selected project in-charge
    document.getElementById('in_charge').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const name = selectedOption.getAttribute('data-name');
        const mobile = selectedOption.getAttribute('data-mobile');
        const email = selectedOption.getAttribute('data-email');

        document.getElementById('in_charge_name').value = name;
        document.getElementById('in_charge_mobile').value = mobile;
        document.getElementById('in_charge_email').value = email;
    });

    // Update the phase options based on the selected overall project period
    document.getElementById('overall_project_period').addEventListener('change', function() {
        const projectPeriod = parseInt(this.value);
        const phaseSelect = document.getElementById('current_phase');

        // Clear previous options
        phaseSelect.innerHTML = '<option value="" disabled selected>Select Phase</option>';

        // Add new options based on the selected value
        for (let i = 1; i <= projectPeriod; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.text = `${i}${i === 1 ? 'st' : i === 2 ? 'nd' : i === 3 ? 'rd' : 'th'} Phase`;
            phaseSelect.appendChild(option);
        }

        // Update all budget rows based on the selected project period
        updateAllBudgetRows();
    });
});

// Calculate the budget totals for a single budget row
function calculateBudgetRowTotals(element) {
    const row = element.closest('tr');
    const rateQuantity = parseFloat(row.querySelector('[name$="[rate_quantity]"]').value) || 0;
    const rateMultiplier = parseFloat(row.querySelector('[name$="[rate_multiplier]"]').value) || 1;
    const rateDuration = parseFloat(row.querySelector('[name$="[rate_duration]"]').value) || 1;
    const rateIncrease = parseFloat(row.querySelector('[name$="[rate_increase]"]').value) || 0;

    const thisPhase = rateQuantity * rateMultiplier * rateDuration;
    let nextPhase = 0;

    // Only calculate next phase value if there is a rate increase
    if (rateIncrease !== 0) {
        nextPhase = (rateQuantity + rateIncrease) * rateMultiplier * rateDuration;
    }

    row.querySelector('[name$="[this_phase]"]').value = thisPhase.toFixed(2);
    row.querySelector('[name$="[next_phase]"]').value = nextPhase.toFixed(2);

    calculateBudgetTotals(row.closest('.phase-card')); // Recalculate totals for the phase whenever a row total is updated
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
    let totalForwarded = 0;

    phases.forEach((phase, index) => {
        const thisPhaseTotal = parseFloat(phase.querySelector('.total_this_phase').value) || 0;
        phase.querySelector('[name^="phases"][name$="[amount_sanctioned]"]').value = thisPhaseTotal.toFixed(2);

        if (index > 0) {
            const amountForwarded = parseFloat(phase.querySelector('[name^="phases"][name$="[amount_forwarded]"]').value) || 0;
            const openingBalance = amountForwarded + thisPhaseTotal;
            phase.querySelector('[name^="phases"][name$="[opening_balance]"]').value = openingBalance.toFixed(2);
            totalForwarded += amountForwarded;
        }

        totalAmount += thisPhaseTotal;
    });

    const lastPhase = phases[phases.length - 1];
    const rows = lastPhase.querySelectorAll('.budget-rows tr');
    rows.forEach(row => {
        totalNextPhase += parseFloat(row.querySelector('[name$="[next_phase]"]').value) || 0;
    });

    document.querySelector('[name="total_amount_sanctioned"]').value = totalAmount.toFixed(2);
    document.querySelector('[name="total_amount_forwarded"]').value = totalForwarded.toFixed(2);
    document.getElementById('overall_project_budget').value = (totalAmount + totalNextPhase).toFixed(2);
}

// Add a new budget row to the phase card
function addBudgetRow(button) {
    const tableBody = button.closest('.phase-card').querySelector('.budget-rows');
    const phaseIndex = button.closest('.phase-card').dataset.phase;
    const newRow = document.createElement('tr');

    newRow.innerHTML = `
        <td><input type="text" name="phases[${phaseIndex}][budget][${tableBody.children.length}][particular]" class="form-control"></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_quantity]" class="form-control" oninput="calculateBudgetRowTotals(this)"></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_multiplier]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)"></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_duration]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)"></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_increase]" class="form-control" oninput="calculateBudgetRowTotals(this)"></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][this_phase]" class="form-control readonly-input" readonly></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][next_phase]" class="form-control"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
    `;

    newRow.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            calculateBudgetRowTotals(input);
        });
    });

    tableBody.appendChild(newRow);
    calculateBudgetTotals(tableBody.closest('.phase-card'));
}

// Remove a budget row from the phase card
function removeBudgetRow(button) {
    const row = button.closest('tr');
    const phaseCard = row.closest('.phase-card');
    row.remove();
    calculateBudgetTotals(phaseCard); // Recalculate totals after removing a row
}

// Add a new phase card
function addPhase() {
    const phasesContainer = document.getElementById('phases-container');
    const phaseCount = phasesContainer.children.length;
    const newPhase = document.createElement('div');
    newPhase.className = 'phase-card';
    newPhase.dataset.phase = phaseCount;

    newPhase.innerHTML = `
        <div class="card-header">
            <h4>Phase ${phaseCount + 1}</h4>
        </div>
        ${phaseCount > 0 ? `
        <div class="mb-3">
            <label for="phases[${phaseCount}][amount_forwarded]" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
            <input type="number" name="phases[${phaseCount}][amount_forwarded]" class="form-control" oninput="calculateBudgetTotals(this.closest('.phase-card'))">
        </div>
        ` : ''}
        <div class="mb-3">
            <label for="phases[${phaseCount}][amount_sanctioned]" class="form-label">Amount Sanctioned in Phase ${phaseCount + 1}: Rs.</label>
            <input type="number" name="phases[${phaseCount}][amount_sanctioned]" class="form-control readonly-input" readonly>
        </div>
        <div class="mb-3">
            <label for="phases[${phaseCount}][opening_balance]" class="form-label">Opening balance in Phase ${phaseCount + 1}: Rs.</label>
            <input type="number" name="phases[${phaseCount}][opening_balance]" class="form-control readonly-input" readonly>
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
                    <td><input type="text" name="phases[${phaseCount}][budget][0][particular]" class="form-control"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_quantity]" class="form-control" oninput="calculateBudgetRowTotals(this)"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_multiplier]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_duration]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_increase]" class="form-control" oninput="calculateBudgetRowTotals(this)"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][this_phase]" class="form-control readonly-input" readonly></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][next_phase]" class="form-control"></td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th><input type="number" class="total_rate_quantity form-control readonly-input" readonly></th>
                    <th><input type="number" class="total_rate_multiplier form-control readonly-input" readonly></th>
                    <th><input type="number" class="total_rate_duration form-control readonly-input" readonly></th>
                    <th><input type="number" class="total_rate_increase form-control readonly-input" readonly></th>
                    <th><input type="number" class="total_this_phase form-control readonly-input" readonly></th>
                    <th><input type="number" class="total_next_phase form-control"></th>
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
    const currentAttachments = attachmentsContainer.children.length;

    const index = currentAttachments;
    const attachmentTemplate = `
        <div class="mb-3 attachment-group" data-index="${index}">
            <label for="attachments[${index}][file]" class="form-label">Attachment ${index + 1}</label>
            <input type="file" name="attachments[${index}][file]" class="mb-2 form-control" accept=".pdf,.doc,.docx,.xlsx">
            <label for="file_name[${index}]" class="form-label">File Name</label>
            <input type="text" name="file_name[${index}]" class="mb-2 form-control" placeholder="Name of File Attached">
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

// Update the attachment labels on page load
document.addEventListener('DOMContentLoaded', function() {
    updateAttachmentLabels();
});
</script>
