{{-- resources/views/projects/partials/scripts.blade.php --}}
<script>

    function beforeSubmit() {
    const form = document.querySelector('form');
    if (!form) {
        console.warn('Form not found');
        return;
    }
    const formData = new FormData(form);
    // Form data validation can be added here if needed
    // Removed console.log for production
}

document.addEventListener('DOMContentLoaded', function() {
    // Update the mobile and email fields based on the selected project in-charge
    const inChargeSelect = document.getElementById('in_charge');
    if (inChargeSelect) {
        inChargeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const name = selectedOption ? selectedOption.getAttribute('data-name') : '';
            const mobile = selectedOption ? selectedOption.getAttribute('data-mobile') : '';
            const email = selectedOption ? selectedOption.getAttribute('data-email') : '';

            const nameField = document.getElementById('in_charge_name');
            const mobileField = document.getElementById('in_charge_mobile');
            const emailField = document.getElementById('in_charge_email');

            if (nameField) nameField.value = name || '';
            if (mobileField) mobileField.value = mobile || '';
            if (emailField) emailField.value = email || '';
        });
    }

    // Update all budget rows based on the selected project period
    const overallProjectPeriod = document.getElementById('overall_project_period');
    if (overallProjectPeriod) {
        overallProjectPeriod.addEventListener('change', function() {
            // Update all budget rows based on the selected project period
            updateAllBudgetRows();
        });
    }

    // Calculate initial totals when page loads
    calculateTotalAmountSanctioned();

    // Add event listener for amount_forwarded input
    const amountForwardedField = document.getElementById('amount_forwarded');
    const localContributionField = document.getElementById('local_contribution');
    if (amountForwardedField) {
        amountForwardedField.addEventListener('input', calculateBudgetFields);
        // Initial calculation on page load
        setTimeout(calculateBudgetFields, 100);
    }
});

// Calculate the budget totals for a single budget row
function calculateBudgetRowTotals(element) {
    if (!element) {
        console.warn('calculateBudgetRowTotals: element is null');
        return;
    }

    const row = element.closest('tr');
    if (!row) {
        console.warn('calculateBudgetRowTotals: row not found');
        return;
    }

    const rateQuantityInput = row.querySelector('[name$="[rate_quantity]"]');
    const rateMultiplierInput = row.querySelector('[name$="[rate_multiplier]"]');
    const rateDurationInput = row.querySelector('[name$="[rate_duration]"]');
    const thisPhaseInput = row.querySelector('[name$="[this_phase]"]');

    if (!rateQuantityInput || !rateMultiplierInput || !rateDurationInput || !thisPhaseInput) {
        console.warn('calculateBudgetRowTotals: required inputs not found');
        return;
    }

    const rateQuantity = parseFloat(rateQuantityInput.value) || 0;
    const rateMultiplier = parseFloat(rateMultiplierInput.value) || 1;
    const rateDuration = parseFloat(rateDurationInput.value) || 1;

    const thisPhase = rateQuantity * rateMultiplier * rateDuration;
    thisPhaseInput.value = thisPhase.toFixed(2);

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

    // Update the display field for overall project budget
    const overallProjectBudgetDisplayField = document.getElementById('overall_project_budget_display');
    if (overallProjectBudgetDisplayField) {
        overallProjectBudgetDisplayField.value = totalAmount.toFixed(2);
    }

    // Call calculateBudgetFields to update amount_sanctioned and opening_balance
    calculateBudgetFields();
}

// Calculate budget fields: amount_sanctioned and opening_balance
// This function implements the new budget calculation logic:
// - Amount Sanctioned = Overall Project Budget - Amount Forwarded
// - Opening Balance = Amount Sanctioned + Amount Forwarded
function calculateBudgetFields() {
    // Get all required field elements
    const overallBudgetField = document.getElementById('overall_project_budget');
    const overallBudgetDisplayField = document.getElementById('overall_project_budget_display');
    const amountForwardedField = document.getElementById('amount_forwarded');
    const amountSanctionedField = document.getElementById('amount_sanctioned_preview');
    const openingBalanceField = document.getElementById('opening_balance_preview');
    const localContributionField = document.getElementById('local_contribution');

    // Exit if required fields are not present
    if (!overallBudgetField) {
        return;
    }

    // Get values from fields
    const overallBudget = parseFloat(overallBudgetField.value) || 0;
    const amountForwarded = parseFloat(amountForwardedField?.value) || 0;
    const localContribution = parseFloat(localContributionField?.value) || 0;
    const combined = amountForwarded + localContribution;

    // Validate: amount_forwarded cannot exceed overall budget
    if (combined > overallBudget) {
        const msg = 'Amount Forwarded + Local Contribution cannot exceed Overall Project Budget (Rs. ' + overallBudget.toFixed(2) + ')';
        if (amountForwardedField || localContributionField) {
            alert(msg);
            const ratio = overallBudget > 0 ? amountForwarded / combined : 0;
            const newForwarded = (overallBudget * ratio);
            const newLocal = overallBudget - newForwarded;
            if (amountForwardedField) amountForwardedField.value = newForwarded.toFixed(2);
            if (localContributionField) localContributionField.value = newLocal.toFixed(2);
            // Recalculate after correction
            setTimeout(calculateBudgetFields, 10);
        }
        return;
    }

    // Calculate Amount Sanctioned: Overall Budget - (Amount Forwarded + Local Contribution)
    const amountSanctioned = overallBudget - combined;

    // Calculate Opening Balance: Amount Sanctioned + (Amount Forwarded + Local Contribution)
    // Note: This equals Overall Budget, but we keep the formula for clarity
    const openingBalance = amountSanctioned + combined;

    // Update the display fields
    if (overallBudgetDisplayField) {
        overallBudgetDisplayField.value = overallBudget.toFixed(2);
    }

    if (amountSanctionedField) {
        amountSanctionedField.value = amountSanctioned.toFixed(2);
    }

    if (openingBalanceField) {
        openingBalanceField.value = openingBalance.toFixed(2);
    }

    // Intentionally no console.log here (keep production console clean)
}


// Add a new budget row to the budget table
function addBudgetRow(button) {
    const tableBody = document.querySelector('.budget-rows');
    const phaseIndex = 0; // Since we only have one phase
    const rowCount = tableBody.children.length;
    const newRow = document.createElement('tr');

    newRow.innerHTML = `
        <td style="width: 5%; text-align: center; vertical-align: middle;">${rowCount + 1}</td>
        <td class="particular-cell-create" style="width: 40%;"><textarea name="phases[${phaseIndex}][budget][${rowCount}][particular]" class="form-control select-input particular-textarea" rows="1"></textarea></td>
        <td style="width: 12%;"><input type="number" name="phases[${phaseIndex}][budget][${rowCount}][rate_quantity]" class="form-control select-input budget-number-input" oninput="calculateBudgetRowTotals(this)"></td>
        <td style="width: 12%;"><input type="number" name="phases[${phaseIndex}][budget][${rowCount}][rate_multiplier]" class="form-control select-input budget-number-input" value="1" oninput="calculateBudgetRowTotals(this)"></td>
        <td style="width: 12%;"><input type="number" name="phases[${phaseIndex}][budget][${rowCount}][rate_duration]" class="form-control select-input budget-number-input" value="1" oninput="calculateBudgetRowTotals(this)"></td>
        <td style="width: 12%;"><input type="number" name="phases[${phaseIndex}][budget][${rowCount}][this_phase]" class="form-control readonly-input budget-number-input" readonly></td>
        <td style="width: 7%; padding: 4px;"><button type="button" class="btn btn-danger budget-remove-btn" onclick="removeBudgetRow(this)">Remove</button></td>
    `;

    newRow.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            calculateBudgetRowTotals(input);
        });
    });

    // Auto-resize textarea for particular column using global function
    const particularTextarea = newRow.querySelector('.particular-textarea');
    if (particularTextarea) {
        // Add auto-resize class if not already present
        if (!particularTextarea.classList.contains('auto-resize-textarea')) {
            particularTextarea.classList.add('auto-resize-textarea');
        }
        // Initialize using global function
        if (typeof initTextareaAutoResize === 'function') {
            initTextareaAutoResize(particularTextarea);
        }
    }

    tableBody.appendChild(newRow);
    reindexBudgetRows(); // Reindex all rows after adding
    calculateTotalAmountSanctioned();
}

// Remove a budget row from the budget table
function removeBudgetRow(button) {
    const tableBody = document.querySelector('.budget-rows');
    if (!tableBody) return;
    if (tableBody.querySelectorAll('tr').length <= 1) return; // Keep at least one row
    const row = button.closest('tr');
    row.remove();
    reindexBudgetRows(); // Reindex all rows after removing
    calculateTotalAmountSanctioned(); // Recalculate totals after removing a row
}

// Reindex budget rows to maintain sequential numbering
function reindexBudgetRows() {
    const tableBody = document.querySelector('.budget-rows');
    if (!tableBody) return;

    const rows = tableBody.querySelectorAll('tr');
    rows.forEach((row, index) => {
        // Update index number in first cell
        const indexCell = row.querySelector('td:first-child');
        if (indexCell) {
            indexCell.textContent = index + 1;
        }

        // Update name attributes for all inputs in the row
        row.querySelectorAll('input, textarea').forEach(input => {
            const name = input.getAttribute('name');
            if (name && name.includes('[budget]')) {
                // Replace the budget index in the name attribute
                const newName = name.replace(/\[budget\]\[\d+\]/, `[budget][${index}]`);
                input.setAttribute('name', newName);
            }
        });
    });
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
            <textarea name="attachments[${index}][description]" class="form-control sustainability-textarea" rows="3" placeholder="Brief Description"></textarea>
            <button type="button" class="mt-2 btn btn-danger" onclick="removeAttachment(this)">Remove</button>
        </div>
    `;
    attachmentsContainer.insertAdjacentHTML('beforeend', attachmentTemplate);

    // Initialize auto-resize for new attachment textarea using global function
    const newAttachment = attachmentsContainer.lastElementChild;
    if (newAttachment && typeof initDynamicTextarea === 'function') {
        initDynamicTextarea(newAttachment);
    }

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
