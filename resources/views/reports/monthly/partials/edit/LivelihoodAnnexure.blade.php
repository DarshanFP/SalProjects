<!-- resources/views/reports/monthly/partials/edit/dl_annexure_edit.blade.php -->

<div class="mb-3 card">
    <div class="card-header">
        <h4>Annexure</h4>
    </div>
    <div class="card-header">
        <h6>PROJECT'S IMPACT IN THE LIFE OF THE BENEFICIARIES</h6>
    </div>
    <div class="card-body" id="dla_impact-container">
        @foreach ($annexures as $index => $annexure)
            <div class="impact-group" data-index="{{ $index + 1 }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Impact {{ $index + 1 }}
                    <button type="button" class="btn btn-danger btn-sm dla_remove-impact" onclick="dla_removeImpactGroup(this)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3 row">
                        <div class="col-md-1">
                            <label for="dla_s_no[{{ $index }}]" class="form-label">S No.</label>
                            <input type="text" name="dla_s_no[{{ $index }}]" class="form-control" value="{{ $index + 1 }}" readonly>
                        </div>
                        <div class="col-md-11">
                            <label for="dla_beneficiary_name[{{ $index }}]" class="form-label">Name of the Beneficiary</label>
                            <input type="text" name="dla_beneficiary_name[{{ $index }}]" class="form-control" value="{{ $annexure->dla_beneficiary_name }}" style="background-color: #202ba3;">
                        </div>
                    </div>
                    <!-- Repeat for other fields -->
                    <!-- Date of support given -->
                    <div class="mb-3">
                        <label for="dla_support_date[{{ $index }}]" class="form-label">Date of support given</label>
                        <input type="date" name="dla_support_date[{{ $index }}]" class="form-control" value="{{ $annexure->dla_support_date }}" style="background-color: #202ba3;">
                    </div>
                    <!-- Nature of self-employment -->
                    <div class="mb-3">
                        <label for="dla_self_employment[{{ $index }}]" class="form-label">Nature of self-employment</label>
                        <input type="text" name="dla_self_employment[{{ $index }}]" class="form-control" value="{{ $annexure->dla_self_employment }}" style="background-color: #202ba3;">
                    </div>
                    <!-- Amount sanctioned -->
                    <div class="mb-3">
                        <label for="dla_amount_sanctioned[{{ $index }}]" class="form-label">Amount sanctioned</label>
                        <input type="number" name="dla_amount_sanctioned[{{ $index }}]" class="form-control" value="{{ $annexure->dla_amount_sanctioned }}" style="background-color: #202ba3;">
                    </div>
                    <!-- Monetary profit gained - Monthly -->
                    <div class="mb-3">
                        <label for="dla_monthly_profit[{{ $index }}]" class="form-label">Monetary profit gained - Monthly</label>
                        <input type="number" name="dla_monthly_profit[{{ $index }}]" class="form-control" value="{{ $annexure->dla_monthly_profit }}" style="background-color: #202ba3;">
                    </div>
                    <!-- Monetary profit gained - Per annum -->
                    <div class="mb-3">
                        <label for="dla_annual_profit[{{ $index }}]" class="form-label">Monetary profit gained - Per annum</label>
                        <input type="number" name="dla_annual_profit[{{ $index }}]" class="form-control" value="{{ $annexure->dla_annual_profit }}" style="background-color: #202ba3;">
                    </div>
                    <!-- Project’s impact in the life of the beneficiary -->
                    <div class="mb-3">
                        <label for="dla_impact[{{ $index }}]" class="form-label">Project’s impact in the life of the beneficiary</label>
                        <textarea name="dla_impact[{{ $index }}]" class="form-control" style="background-color: #202ba3;">{{ $annexure->dla_impact }}</textarea>
                    </div>
                    <!-- Challenges faced if any -->
                    <div class="mb-3">
                        <label for="dla_challenges[{{ $index }}]" class="form-label">Challenges faced if any is it</label>
                        <textarea name="dla_challenges[{{ $index }}]" class="form-control" style="background-color: #202ba3;">{{ $annexure->dla_challenges }}</textarea>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <!-- Add the Add Impact button here -->
    <div class="card-footer">
        <button type="button" class="btn btn-primary" onclick="dla_addImpactGroup()">Add Impact</button>
    </div>
</div>

<script>
    function dla_addImpactGroup() {
        const impactContainer = document.getElementById('dla_impact-container');
        const currentIndex = impactContainer.querySelectorAll('.impact-group').length;

        const impactTemplate = `
            <div class="impact-group" data-index="${currentIndex + 1}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Impact ${currentIndex + 1}
                    <button type="button" class="btn btn-danger btn-sm dla_remove-impact" onclick="dla_removeImpactGroup(this)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3 row">
                        <div class="col-md-1">
                            <label for="dla_s_no[${currentIndex}]" class="form-label">S No.</label>
                            <input type="text" name="dla_s_no[${currentIndex}]" class="form-control" value="${currentIndex + 1}" readonly>
                        </div>
                        <div class="col-md-11">
                            <label for="dla_beneficiary_name[${currentIndex}]" class="form-label">Name of the Beneficiary</label>
                            <input type="text" name="dla_beneficiary_name[${currentIndex}]" class="form-control" style="background-color: #202ba3;">
                        </div>
                    </div>
                    <!-- Date of support given -->
                    <div class="mb-3">
                        <label for="dla_support_date[${currentIndex}]" class="form-label">Date of support given</label>
                        <input type="date" name="dla_support_date[${currentIndex}]" class="form-control" style="background-color: #202ba3;">
                    </div>
                    <!-- Nature of self-employment -->
                    <div class="mb-3">
                        <label for="dla_self_employment[${currentIndex}]" class="form-label">Nature of self-employment</label>
                        <input type="text" name="dla_self_employment[${currentIndex}]" class="form-control" style="background-color: #202ba3;">
                    </div>
                    <!-- Amount sanctioned -->
                    <div class="mb-3">
                        <label for="dla_amount_sanctioned[${currentIndex}]" class="form-label">Amount sanctioned</label>
                        <input type="number" name="dla_amount_sanctioned[${currentIndex}]" class="form-control" style="background-color: #202ba3;">
                    </div>
                    <!-- Monetary profit gained - Monthly -->
                    <div class="mb-3">
                        <label for="dla_monthly_profit[${currentIndex}]" class="form-label">Monetary profit gained - Monthly</label>
                        <input type="number" name="dla_monthly_profit[${currentIndex}]" class="form-control" style="background-color: #202ba3;">
                    </div>
                    <!-- Monetary profit gained - Per annum -->
                    <div class="mb-3">
                        <label for="dla_annual_profit[${currentIndex}]" class="form-label">Monetary profit gained - Per annum</label>
                        <input type="number" name="dla_annual_profit[${currentIndex}]" class="form-control" style="background-color: #202ba3;">
                    </div>
                    <!-- Project’s impact in the life of the beneficiary -->
                    <div class="mb-3">
                        <label for="dla_impact[${currentIndex}]" class="form-label">Project’s impact in the life of the beneficiary</label>
                        <textarea name="dla_impact[${currentIndex}]" class="form-control" style="background-color: #202ba3;"></textarea>
                    </div>
                    <!-- Challenges faced if any -->
                    <div class="mb-3">
                        <label for="dla_challenges[${currentIndex}]" class="form-label">Challenges faced if any is it</label>
                        <textarea name="dla_challenges[${currentIndex}]" class="form-control" style="background-color: #202ba3;"></textarea>
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
            group.setAttribute('data-index', index + 1);
            const header = group.querySelector('.card-header');
            header.firstChild.textContent = `Impact ${index + 1}`;

            // Update S No.
            const sNoInput = group.querySelector('input[name^="dla_s_no"]');
            sNoInput.value = index + 1;
            sNoInput.name = `dla_s_no[${index}]`;

            // Update Name of the Beneficiary
            const nameInput = group.querySelector('input[name^="dla_beneficiary_name"]');
            nameInput.name = `dla_beneficiary_name[${index}]`;

            // Update Date of support given
            const supportDateInput = group.querySelector('input[name^="dla_support_date"]');
            supportDateInput.name = `dla_support_date[${index}]`;

            // Update Nature of self-employment
            const selfEmploymentInput = group.querySelector('input[name^="dla_self_employment"]');
            selfEmploymentInput.name = `dla_self_employment[${index}]`;

            // Update Amount sanctioned
            const amountSanctionedInput = group.querySelector('input[name^="dla_amount_sanctioned"]');
            amountSanctionedInput.name = `dla_amount_sanctioned[${index}]`;

            // Update Monetary profit gained - Monthly
            const monthlyProfitInput = group.querySelector('input[name^="dla_monthly_profit"]');
            monthlyProfitInput.name = `dla_monthly_profit[${index}]`;

            // Update Monetary profit gained - Per annum
            const annualProfitInput = group.querySelector('input[name^="dla_annual_profit"]');
            annualProfitInput.name = `dla_annual_profit[${index}]`;

            // Update Project’s impact in the life of the beneficiary
            const impactTextarea = group.querySelector('textarea[name^="dla_impact"]');
            impactTextarea.name = `dla_impact[${index}]`;

            // Update Challenges faced if any
            const challengesTextarea = group.querySelector('textarea[name^="dla_challenges"]');
            challengesTextarea.name = `dla_challenges[${index}]`;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        dla_updateImpactGroupIndexes();
    });
</script>
