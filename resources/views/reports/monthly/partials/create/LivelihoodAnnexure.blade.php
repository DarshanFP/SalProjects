{{-- resources/views/reports/monthly/partials/create/LivelihoodAnnexure.blade.php --}}
<!-- Annexure Section Starts -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>6. Annexure</h4>
    </div>
    <div class="card-header">
        <h6>PROJECT'S IMPACT IN THE LIFE OF THE BENEFICIARIES </h6>
    </div>
    <div class="card-body" id="dla_impact-container">
        <div class="impact-group" data-index="1">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <span class="badge bg-warning me-2">1</span>
                    Impact 1
                </span>
                <button type="button" class="btn btn-danger btn-sm d-none dla_remove-impact" onclick="dla_removeImpactGroup(this)">Remove</button>
            </div>
            <div class="card-body">
                <div class="mb-3 row">
                    <div class="col-md-1">
                        <label for="dla_s_no[1]" class="form-label">S No.</label>
                        <input type="text" name="dla_s_no[1]" class="form-control" value="1" readonly>
                    </div>
                    <div class="col-md-11">
                        <label for="dla_beneficiary_name[1]" class="form-label">Name of the Beneficiary</label>
                        <input type="text" name="dla_beneficiary_name[1]" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="dla_support_date[1]" class="form-label">Date of support given</label>
                    <input type="date" name="dla_support_date[1]" id="dla_support_date_1" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="dla_self_employment[1]" class="form-label">Nature of self-employment</label>
                    <input type="text" name="dla_self_employment[1]" id="dla_self_employment_1" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="dla_amount_sanctioned[1]" class="form-label">Amount sanctioned</label>
                    <input type="number" name="dla_amount_sanctioned[1]" id="dla_amount_sanctioned_1" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="dla_monthly_profit[1]" class="form-label">Monetary profit gained - Monthly</label>
                    <input type="number" name="dla_monthly_profit[1]" id="dla_monthly_profit_1" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="dla_annual_profit[1]" class="form-label">Monetary profit gained - Per annum</label>
                    <input type="number" name="dla_annual_profit[1]" id="dla_annual_profit_1" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="dla_impact[1]" class="form-label">Project’s impact in the life of the beneficiary</label>
                    <textarea name="dla_impact[1]" id="dla_impact_1" class="form-control auto-resize-textarea"></textarea>
                </div>
                <div class="mb-3">
                    <label for="dla_challenges[1]" class="form-label">Challenges faced if any</label>
                    <textarea name="dla_challenges[1]" id="dla_challenges_1" class="form-control auto-resize-textarea"></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button type="button" class="btn btn-primary" onclick="dla_addImpactGroup()">Add Impact</button>
    </div>
</div>
<!-- Annexure Section Ends -->

<script>
    // Annexure Section

    function dla_addImpactGroup() {
        const impactContainer = document.getElementById('dla_impact-container');
        const currentIndex = impactContainer.children.length + 1;

        const impactTemplate = `
            <div class="impact-group" data-index="${currentIndex}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <span class="badge bg-warning me-2">${currentIndex}</span>
                        Impact ${currentIndex}
                    </span>
                    <button type="button" class="btn btn-danger btn-sm dla_remove-impact" onclick="dla_removeImpactGroup(this)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3 row">
                        <div class="col-md-1">
                            <label for="dla_s_no[${currentIndex}]" class="form-label">S No.</label>
                            <input type="text" name="dla_s_no[${currentIndex}]" id="dla_s_no_${currentIndex}" class="form-control" value="${currentIndex}" readonly>
                        </div>
                        <div class="col-md-11">
                            <label for="dla_beneficiary_name[${currentIndex}]" class="form-label">Name of the Beneficiary</label>
                            <input type="text" name="dla_beneficiary_name[${currentIndex}]" id="dla_beneficiary_name_${currentIndex}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="dla_support_date[${currentIndex}]" class="form-label">Date of support given</label>
                        <input type="date" name="dla_support_date[${currentIndex}]" id="dla_support_date_${currentIndex}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="dla_self_employment[${currentIndex}]" class="form-label">Nature of self-employment</label>
                        <input type="text" name="dla_self_employment[${currentIndex}]" id="dla_self_employment_${currentIndex}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="dla_amount_sanctioned[${currentIndex}]" class="form-label">Amount sanctioned</label>
                        <input type="number" name="dla_amount_sanctioned[${currentIndex}]" id="dla_amount_sanctioned_${currentIndex}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="dla_monthly_profit[${currentIndex}]" class="form-label">Monetary profit gained - Monthly</label>
                        <input type="number" name="dla_monthly_profit[${currentIndex}]" id="dla_monthly_profit_${currentIndex}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="dla_annual_profit[${currentIndex}]" class="form-label">Monetary profit gained - Per annum</label>
                        <input type="number" name="dla_annual_profit[${currentIndex}]" id="dla_annual_profit_${currentIndex}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="dla_impact[${currentIndex}]" class="form-label">Project’s impact in the life of the beneficiary</label>
                        <textarea name="dla_impact[${currentIndex}]" id="dla_impact_${currentIndex}" class="form-control auto-resize-textarea"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="dla_challenges[${currentIndex}]" class="form-label">Challenges faced if any</label>
                        <textarea name="dla_challenges[${currentIndex}]" id="dla_challenges_${currentIndex}" class="form-control auto-resize-textarea"></textarea>
                    </div>
                </div>
            </div>
        `;

        impactContainer.insertAdjacentHTML('beforeend', impactTemplate);

        // Initialize auto-resize for new impact group textareas using global function
        const newImpactGroup = impactContainer.lastElementChild;
        if (newImpactGroup && typeof initDynamicTextarea === 'function') {
            initDynamicTextarea(newImpactGroup);
        }

        dla_updateImpactGroupIndexes();
    }

    function dla_removeImpactGroup(button) {
        const group = button.closest('.impact-group');
        group.remove();
        dla_updateImpactGroupIndexes();
    }

    /**
     * Reindex all impact groups in LDP Annexure section after add/remove operations
     * Updates index badges, data-index attributes, "S No." field, and all form field names/IDs
     * Ensures sequential numbering (1, 2, 3, ...) for all impact groups
     *
     * @returns {void}
     */
    function dla_updateImpactGroupIndexes() {
        const impactGroups = document.querySelectorAll('.impact-group');
        impactGroups.forEach((group, index) => {
            const newIndex = index + 1;

            // Update data-index
            group.dataset.index = newIndex;

            // Update badge and header text
            const headerSpan = group.querySelector('.card-header span');
            if (headerSpan) {
                headerSpan.innerHTML = `<span class="badge bg-warning me-2">${newIndex}</span>Impact ${newIndex}`;
            }

            // Update S No. field
            const sNoInput = group.querySelector('input[name^="dla_s_no"]');
            if (sNoInput) {
                sNoInput.value = newIndex;
                sNoInput.name = `dla_s_no[${newIndex}]`;
                sNoInput.id = `dla_s_no_${newIndex}`;
                const sNoLabel = group.querySelector('label[for^="dla_s_no"]');
                if (sNoLabel) {
                    sNoLabel.setAttribute('for', `dla_s_no_${newIndex}`);
                }
            }

            // Update all other name attributes and IDs
            const beneficiaryNameInput = group.querySelector('input[name^="dla_beneficiary_name"]');
            if (beneficiaryNameInput) {
                beneficiaryNameInput.name = `dla_beneficiary_name[${newIndex}]`;
                beneficiaryNameInput.id = `dla_beneficiary_name_${newIndex}`;
                const beneficiaryNameLabel = group.querySelector('label[for^="dla_beneficiary_name"]');
                if (beneficiaryNameLabel) {
                    beneficiaryNameLabel.setAttribute('for', `dla_beneficiary_name_${newIndex}`);
                }
            }

            const supportDateInput = group.querySelector('input[name^="dla_support_date"]');
            if (supportDateInput) {
                supportDateInput.name = `dla_support_date[${newIndex}]`;
                supportDateInput.id = `dla_support_date_${newIndex}`;
                const supportDateLabel = group.querySelector('label[for^="dla_support_date"]');
                if (supportDateLabel) {
                    supportDateLabel.setAttribute('for', `dla_support_date_${newIndex}`);
                }
            }

            const selfEmploymentInput = group.querySelector('input[name^="dla_self_employment"]');
            if (selfEmploymentInput) {
                selfEmploymentInput.name = `dla_self_employment[${newIndex}]`;
                selfEmploymentInput.id = `dla_self_employment_${newIndex}`;
                const selfEmploymentLabel = group.querySelector('label[for^="dla_self_employment"]');
                if (selfEmploymentLabel) {
                    selfEmploymentLabel.setAttribute('for', `dla_self_employment_${newIndex}`);
                }
            }

            const amountSanctionedInput = group.querySelector('input[name^="dla_amount_sanctioned"]');
            if (amountSanctionedInput) {
                amountSanctionedInput.name = `dla_amount_sanctioned[${newIndex}]`;
                amountSanctionedInput.id = `dla_amount_sanctioned_${newIndex}`;
                const amountSanctionedLabel = group.querySelector('label[for^="dla_amount_sanctioned"]');
                if (amountSanctionedLabel) {
                    amountSanctionedLabel.setAttribute('for', `dla_amount_sanctioned_${newIndex}`);
                }
            }

            const monthlyProfitInput = group.querySelector('input[name^="dla_monthly_profit"]');
            if (monthlyProfitInput) {
                monthlyProfitInput.name = `dla_monthly_profit[${newIndex}]`;
                monthlyProfitInput.id = `dla_monthly_profit_${newIndex}`;
                const monthlyProfitLabel = group.querySelector('label[for^="dla_monthly_profit"]');
                if (monthlyProfitLabel) {
                    monthlyProfitLabel.setAttribute('for', `dla_monthly_profit_${newIndex}`);
                }
            }

            const annualProfitInput = group.querySelector('input[name^="dla_annual_profit"]');
            if (annualProfitInput) {
                annualProfitInput.name = `dla_annual_profit[${newIndex}]`;
                annualProfitInput.id = `dla_annual_profit_${newIndex}`;
                const annualProfitLabel = group.querySelector('label[for^="dla_annual_profit"]');
                if (annualProfitLabel) {
                    annualProfitLabel.setAttribute('for', `dla_annual_profit_${newIndex}`);
                }
            }

            const impactTextarea = group.querySelector('textarea[name^="dla_impact"]');
            if (impactTextarea) {
                impactTextarea.name = `dla_impact[${newIndex}]`;
                impactTextarea.id = `dla_impact_${newIndex}`;
                const impactLabel = group.querySelector('label[for^="dla_impact"]');
                if (impactLabel) {
                    impactLabel.setAttribute('for', `dla_impact_${newIndex}`);
                }
            }

            const challengesTextarea = group.querySelector('textarea[name^="dla_challenges"]');
            if (challengesTextarea) {
                challengesTextarea.name = `dla_challenges[${newIndex}]`;
                challengesTextarea.id = `dla_challenges_${newIndex}`;
                const challengesLabel = group.querySelector('label[for^="dla_challenges"]');
                if (challengesLabel) {
                    challengesLabel.setAttribute('for', `dla_challenges_${newIndex}`);
                }
            }

            // Update remove button visibility
            const removeButton = group.querySelector('.dla_remove-impact');
            if (removeButton) {
                if (newIndex === 1) {
                    removeButton.classList.add('d-none');
                } else {
                    removeButton.classList.remove('d-none');
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        dla_updateImpactGroupIndexes();
    });
</script>
