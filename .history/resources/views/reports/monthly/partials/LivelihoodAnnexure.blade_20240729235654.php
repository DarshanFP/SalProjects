<!-- Annexure Section Starts -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>6. Annexure</h4>
    </div>
    <div class="card-header">
        <h6>PROJECT'S IMPACT IN THE LIFE OF THE BENEFICIARIES</h6>
    </div>
    <div class="card-body" id="impact-container">
        <div class="impact-group" data-index="1">
            <div class="card-header d-flex justify-content-between align-items-center">
                Impact 1
                <button type="button" class="btn btn-danger btn-sm d-none remove-impact" onclick="removeImpactGroup(this)">Remove</button>
            </div>
            <div class="card-body">
                <div class="mb-3 row">
                    <div class="col-md-1">
                        <label for="s_no[1]" class="form-label">S No.</label>
                        <input type="text" name="s_no[1]" class="form-control" value="1" readonly>
                    </div>
                    <div class="col-md-11">
                        <label for="beneficiary_name[1]" class="form-label">Name of the Beneficiary</label>
                        <input type="text" name="beneficiary_name[1]" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="support_date[1]" class="form-label">Date of support given</label>
                    <input type="date" name="support_date[1]" id="support_date_1" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="self_employment[1]" class="form-label">Nature of self-employment</label>
                    <input type="text" name="self_employment[1]" id="self_employment_1" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="amount_sanctioned[1]" class="form-label">Amount sanctioned</label>
                    <input type="number" name="amount_sanctioned[1]" id="amount_sanctioned_1" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="monthly_profit[1]" class="form-label">Monetary profit gained - Monthly</label>
                    <input type="number" name="monthly_profit[1]" id="monthly_profit_1" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="annual_profit[1]" class="form-label">Monetary profit gained - Per annum</label>
                    <input type="number" name="annual_profit[1]" id="annual_profit_1" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="impact[1]" class="form-label">Project’s impact in the life of the beneficiary</label>
                    <textarea name="impact[1]" id="impact_1" class="form-control"></textarea>
                </div>
                <div class="mb-3">
                    <label for="challenges[1]" class="form-label">Challenges faced if any</label>
                    <textarea name="challenges[1]" id="challenges_1" class="form-control"></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button type="button" class="btn btn-primary" onclick="addImpactGroup()">Add another beneficiary</button>
    </div>
</div>
<!-- Annexure Section Ends -->
<script>
    // Annexure Section

    function addImpactGroup() {
    const impactContainer = document.getElementById('impact-container');
    const currentIndex = impactContainer.children.length + 1;

    const impactTemplate = `
        <div class="impact-group" data-index="${currentIndex}">
            <div class="card-header d-flex justify-content-between align-items-center">
                Impact ${currentIndex}
                <button type="button" class="btn btn-danger btn-sm remove-impact" onclick="removeImpactGroup(this)">Remove</button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="s_no[${currentIndex}]" class="form-label">S No.</label>
                    <input type="text" name="s_no[${currentIndex}]" id="s_no_${currentIndex}" class="form-control" value="${currentIndex}" readonly>
                </div>
                <div class="mb-3">
                    <label for="beneficiary_name[${currentIndex}]" class="form-label">Name of the beneficiary</label>
                    <input type="text" name="beneficiary_name[${currentIndex}]" id="beneficiary_name_${currentIndex}" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="support_date[${currentIndex}]" class="form-label">Date of support given</label>
                    <input type="date" name="support_date[${currentIndex}]" id="support_date_${currentIndex}" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="self_employment[${currentIndex}]" class="form-label">Nature of self-employment</label>
                    <input type="text" name="self_employment[${currentIndex}]" id="self_employment_${currentIndex}" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="amount_sanctioned[${currentIndex}]" class="form-label">Amount sanctioned</label>
                    <input type="number" name="amount_sanctioned[${currentIndex}]" id="amount_sanctioned_${currentIndex}" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="monthly_profit[${currentIndex}]" class="form-label">Monetary profit gained - Monthly</label>
                    <input type="number" name="monthly_profit[${currentIndex}]" id="monthly_profit_${currentIndex}" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="annual_profit[${currentIndex}]" class="form-label">Monetary profit gained - Per annum</label>
                    <input type="number" name="annual_profit[${currentIndex}]" id="annual_profit_${currentIndex}" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="impact[${currentIndex}]" class="form-label">Project’s impact in the life of the beneficiary</label>
                    <textarea name="impact[${currentIndex}]" id="impact_${currentIndex}" class="form-control"></textarea>
                </div>
                <div class="mb-3">
                    <label for="challenges[${currentIndex}]" class="form-label">Challenges faced if any</label>
                    <textarea name="challenges[${currentIndex}]" id="challenges_${currentIndex}" class="form-control"></textarea>
                </div>
            </div>
        </div>
    `;

    impactContainer.insertAdjacentHTML('beforeend', impactTemplate);
    updateImpactGroupIndexes();
}

function removeImpactGroup(button) {
    const group = button.closest('.impact-group');
    group.remove();
    updateImpactGroupIndexes();
}

function updateImpactGroupIndexes() {
    const impactGroups = document.querySelectorAll('.impact-group');
    impactGroups.forEach((group, index) => {
        const sNoInput = group.querySelector('input[name^="s_no"]');
        sNoInput.value = index + 1;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    updateImpactGroupIndexes();
});

