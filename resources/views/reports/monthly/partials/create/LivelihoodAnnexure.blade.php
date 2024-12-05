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
                Impact 1
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
                    <textarea name="dla_impact[1]" id="dla_impact_1" class="form-control"></textarea>
                </div>
                <div class="mb-3">
                    <label for="dla_challenges[1]" class="form-label">Challenges faced if any</label>
                    <textarea name="dla_challenges[1]" id="dla_challenges_1" class="form-control"></textarea>
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
                    Impact ${currentIndex}
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
                        <textarea name="dla_impact[${currentIndex}]" id="dla_impact_${currentIndex}" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="dla_challenges[${currentIndex}]" class="form-label">Challenges faced if any</label>
                        <textarea name="dla_challenges[${currentIndex}]" id="dla_challenges_${currentIndex}" class="form-control"></textarea>
                    </div>
                </div>
            </div>
        `;

        impactContainer.insertAdjacentHTML('beforeend', impactTemplate);
        dla_updateImpactGroupIndexes();
    }

    function dla_removeImpactGroup(button) {
        const group = button.closest('.impact-group');
        group.remove();
        dla_updateImpactGroupIndexes();
    }

    function dla_updateImpactGroupIndexes() {
        const impactGroups = document.querySelectorAll('.impact-group');
        impactGroups.forEach((group, index) => {
            const sNoInput = group.querySelector('input[name^="dla_s_no"]');
            sNoInput.value = index + 1;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        dla_updateImpactGroupIndexes();
    });
</script>
