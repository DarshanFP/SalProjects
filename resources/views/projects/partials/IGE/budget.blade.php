<div class="mb-3 card">
    <div class="card-header">
        <h4>Budget for Current Year</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Name</th>
                        <th>Study Proposed to be</th>
                        <th>College Fees</th>
                        <th>Hostel Fees</th>
                        <th>Total Amount</th>
                        <th>Eligibility of Scholarship (Expected Amount)</th>
                        <th>Contribution from Family</th>
                        <th>Amount Requested</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="IGE-budget-rows">
                    <tr id="budget-row-1">
                        <td>1</td>
                        <td><input type="text" name="name[]" id="name-1" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="study_proposed[]" id="study_proposed-1" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="college_fees[]" id="college_fees-1" class="form-control" step="0.01" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="hostel_fees[]" id="hostel_fees-1" class="form-control" step="0.01" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="total_amount[]" id="total_amount-1" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><input type="number" name="scholarship_eligibility[]" id="scholarship_eligibility-1" class="form-control" step="0.01" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="family_contribution[]" id="family_contribution-1" class="form-control" step="0.01" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="amount_requested[]" id="amount_requested-1" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><button type="button" class="btn btn-danger" onclick="removeIGEBudgetRow(this)">Remove</button></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td><input type="number" id="total-college-fees" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><input type="number" id="total-hostel-fees" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><input type="number" id="total-amount" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><input type="number" id="total-scholarship-eligibility" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><input type="number" id="total-family-contribution" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><input type="number" id="total-amount-requested" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addIGEBudgetRow()">Add More</button>
    </div>
</div>

<!-- JavaScript to add/remove rows dynamically and calculate totals -->
<script>
    (function(){
    document.addEventListener('DOMContentLoaded', function () {
        let budgetRowIndex = 1;

        function addIGEBudgetRow() {
            budgetRowIndex++;
            const newRow = document.createElement('tr');
            newRow.id = `budget-row-${budgetRowIndex}`;
            newRow.innerHTML = `
                <td>${budgetRowIndex}</td>
                <td><input type="text" name="name[]" id="name-${budgetRowIndex}" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="study_proposed[]" id="study_proposed-${budgetRowIndex}" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="number" name="college_fees[]" id="college_fees-${budgetRowIndex}" class="form-control college_fees" step="0.01" style="background-color: #202ba3;"></td>
                <td><input type="number" name="hostel_fees[]" id="hostel_fees-${budgetRowIndex}" class="form-control hostel_fees" step="0.01" style="background-color: #202ba3;"></td>
                <td><input type="number" name="total_amount[]" id="total_amount-${budgetRowIndex}" class="form-control total_amount" step="0.01" style="background-color: #202ba3;" readonly></td>
                <td><input type="number" name="scholarship_eligibility[]" id="scholarship_eligibility-${budgetRowIndex}" class="form-control scholarship_eligibility" step="0.01" style="background-color: #202ba3;"></td>
                <td><input type="number" name="family_contribution[]" id="family_contribution-${budgetRowIndex}" class="form-control family_contribution" step="0.01" style="background-color: #202ba3;"></td>
                <td><input type="number" name="amount_requested[]" id="amount_requested-${budgetRowIndex}" class="form-control amount_requested" step="0.01" style="background-color: #202ba3;" readonly></td>
                <td><button type="button" class="btn btn-danger" onclick="removeIGEBudgetRow(this)">Remove</button></td>
            `;
            document.getElementById('IGE-budget-rows').appendChild(newRow);
            calculateTotals();
        }

        function removeIGEBudgetRow(button) {
            const row = button.closest('tr');
            row.remove();
            updateBudgetRowNumbers();
            calculateTotals();
        }

        function updateBudgetRowNumbers() {
            const rows = document.querySelectorAll('#IGE-budget-rows tr');
            rows.forEach((row, index) => {
                row.children[0].textContent = index + 1;
                row.id = `budget-row-${index + 1}`;
                row.querySelector('input[name="name[]"]').id = `name-${index + 1}`;
                row.querySelector('input[name="study_proposed[]"]').id = `study_proposed-${index + 1}`;
                row.querySelector('input[name="college_fees[]"]').id = `college_fees-${index + 1}`;
                row.querySelector('input[name="hostel_fees[]"]').id = `hostel_fees-${index + 1}`;
                row.querySelector('input[name="total_amount[]"]').id = `total_amount-${index + 1}`;
                row.querySelector('input[name="scholarship_eligibility[]"]').id = `scholarship_eligibility-${index + 1}`;
                row.querySelector('input[name="family_contribution[]"]').id = `family_contribution-${index + 1}`;
                row.querySelector('input[name="amount_requested[]"]').id = `amount_requested-${index + 1}`;
            });
            budgetRowIndex = rows.length;
        }

        function calculateTotals() {
            let totalCollegeFees = 0;
            let totalHostelFees = 0;
            let totalIGEAmount = 0;
            let totalScholarshipEligibility = 0;
            let totalFamilyContribution = 0;
            let totalIGEAmountRequested = 0;

            document.querySelectorAll('#IGE-budget-rows tr').forEach(row => {
                const collegeFees = parseFloat(row.querySelector('input[name="college_fees[]"]').value) || 0;
                const hostelFees = parseFloat(row.querySelector('input[name="hostel_fees[]"]').value) || 0;
                const totalRowAmount = collegeFees + hostelFees;
                row.querySelector('input[name="total_amount[]"]').value = totalRowAmount.toFixed(2);

                const scholarshipEligibility = parseFloat(row.querySelector('input[name="scholarship_eligibility[]"]').value) || 0;
                const familyContribution = parseFloat(row.querySelector('input[name="family_contribution[]"]').value) || 0;
                const requestedAmount = totalRowAmount - scholarshipEligibility - familyContribution;  // Updated calculation
                row.querySelector('input[name="amount_requested[]"]').value = requestedAmount.toFixed(2);

                totalCollegeFees += collegeFees;
                totalHostelFees += hostelFees;
                totalIGEAmount += totalRowAmount;
                totalScholarshipEligibility += scholarshipEligibility;
                totalFamilyContribution += familyContribution;
                totalIGEAmountRequested += requestedAmount;
            });

            document.getElementById('total-college-fees').value = totalCollegeFees.toFixed(2);
            document.getElementById('total-hostel-fees').value = totalHostelFees.toFixed(2);
            document.getElementById('total-amount').value = totalIGEAmount.toFixed(2);
            document.getElementById('total-scholarship-eligibility').value = totalScholarshipEligibility.toFixed(2);
            document.getElementById('total-family-contribution').value = totalFamilyContribution.toFixed(2);
            document.getElementById('total-amount-requested').value = totalIGEAmountRequested.toFixed(2);
        }

        // Attach the input event listener to the table body to capture events from dynamically added rows
        document.getElementById('IGE-budget-rows').addEventListener('input', calculateTotals);

        // Expose functions to the global scope so they can be accessed from onclick attributes
        window.addIGEBudgetRow = addIGEBudgetRow;
        window.removeIGEBudgetRow = removeIGEBudgetRow;
    });
})();
</script>
