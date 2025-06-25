{{-- resources/views/projects/partials/scripts.blade.php --}}
<script>

    function beforeSubmit() {
    const formData = new FormData(document.querySelector('form'));
    formData.forEach((value, key) => {
        console.log(`${key}: ${value}`);
    });
}

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

    // Update all budget rows based on the selected project period
    document.getElementById('overall_project_period').addEventListener('change', function() {
        // Update all budget rows based on the selected project period
        updateAllBudgetRows();
    });

    // Calculate initial totals when page loads
    calculateTotalAmountSanctioned();
});

// Calculate the budget totals for a single budget row
function calculateBudgetRowTotals(element) {
    const row = element.closest('tr');
    const rateQuantity = parseFloat(row.querySelector('[name$="[rate_quantity]"]').value) || 0;
    const rateMultiplier = parseFloat(row.querySelector('[name$="[rate_multiplier]"]').value) || 1;
    const rateDuration = parseFloat(row.querySelector('[name$="[rate_duration]"]').value) || 1;

    const thisPhase = rateQuantity * rateMultiplier * rateDuration;

    row.querySelector('[name$="[this_phase]"]').value = thisPhase.toFixed(2);

    // Recalculate totals whenever a row total is updated
    calculateTotalAmountSanctioned();
}

// Update all budget rows based on the selected project period
function updateAllBudgetRows() {
    const budgetRows = document.querySelectorAll('.budget-rows tr');
    budgetRows.forEach(row => {
        calculateBudgetRowTotals(row.querySelector('input'));
    });
}

// Calculate the total budget for a phase - COMMENTED OUT TO DISABLE PHASE FUNCTIONALITY

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


// Calculate the total amount sanctioned and update the overall project budget - MODIFIED TO WORK WITHOUT PHASE CARDS
function calculateTotalAmountSanctioned() {
    // Get all budget rows directly from the budget table
    const budgetRows = document.querySelectorAll('.budget-rows tr');
    let totalAmount = 0;
    let totalRateQuantity = 0;
    let totalRateMultiplier = 0;
    let totalRateDuration = 0;

    // Calculate totals from all budget rows
    budgetRows.forEach(row => {
        const thisPhaseValue = parseFloat(row.querySelector('[name$="[this_phase]"]').value) || 0;
        const rateQuantity = parseFloat(row.querySelector('[name$="[rate_quantity]"]').value) || 0;
        const rateMultiplier = parseFloat(row.querySelector('[name$="[rate_multiplier]"]').value) || 0;
        const rateDuration = parseFloat(row.querySelector('[name$="[rate_duration]"]').value) || 0;

        totalAmount += thisPhaseValue;
        totalRateQuantity += rateQuantity;
        totalRateMultiplier += rateMultiplier;
        totalRateDuration += rateDuration;
    });

    // Update the total row in the footer
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
        totalThisPhaseField.value = totalAmount.toFixed(2);
    }

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


// Add a new budget row to the budget table
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

    newRow.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            calculateBudgetRowTotals(input);
        });
    });

    tableBody.appendChild(newRow);
    calculateTotalAmountSanctioned();
}

// Remove a budget row from the budget table
function removeBudgetRow(button) {
    const row = button.closest('tr');
    row.remove();
    calculateTotalAmountSanctioned(); // Recalculate totals after removing a row
}

// Add a new phase card - COMMENTED OUT TO DISABLE PHASE FUNCTIONALITY
/*
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
                    <td><input type="text" name="phases[${phaseCount}][budget][0][particular]" class="form-control"  style="background-color: #202ba3;"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_quantity]" class="form-control" oninput="calculateBudgetRowTotals(this)"  style="background-color: #202ba3;"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_multiplier]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)"  style="background-color: #202ba3;"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_duration]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)"  style="background-color: #202ba3;"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_increase]" class="form-control" oninput="calculateBudgetRowTotals(this)" style="background-color: #122F6B"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][this_phase]" class="form-control readonly-input" readonly></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][next_phase]" class="form-control" style="background-color: #122F6B"></td>
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
*/

// Remove a phase card - COMMENTED OUT TO DISABLE PHASE FUNCTIONALITY
/*
function removePhase(button) {
    const phaseCard = button.closest('.phase-card');
    phaseCard.remove();
    calculateTotalAmountSanctioned();
}
*/

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
