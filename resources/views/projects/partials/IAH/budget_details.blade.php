<div class="mb-4 card iah-budget-details">
    <div class="card-header">
        <h4 class="mb-0">Estimated Cost of Treatment – Budget Details</h4>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Particular</th>
                    <th>Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="iah-budget-list-unique">
                <tr>
                    <td>
                        <input type="text" name="particular[]" class="form-control" placeholder="Enter particular" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="amount[]" class="form-control amount-field" placeholder="Enter amount" required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger remove-budget-item">Remove</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-primary" id="add-budget-item-iah">Add More</button>

        <div class="mt-4">
            <label for="total_expenses" class="form-label">Total Expenses:</label>
            <input type="number" step="0.01" name="total_expenses" class="form-control" id="total_expenses" placeholder="Enter total expenses" readonly>
        </div>

        <div class="mt-3">
            <label for="family_contribution" class="form-label">Family Contribution:</label>
            <input type="number" step="0.01" name="family_contribution" class="form-control" id="family_contribution" placeholder="Enter family contribution">
        </div>

        <div class="mt-3">
            <label for="amount_requested" class="form-label">Total Amount Requested:</label>
            <input type="number" step="0.01" name="amount_requested" class="form-control" id="amount_requested" placeholder="Enter total amount requested" readonly>
        </div>
    </div>
</div>

<script>
    (function(){
    document.addEventListener('DOMContentLoaded', function() {
        // Scope the JavaScript to the specific container
        const budgetDetails = document.querySelector('.iah-budget-details');
        const totalExpensesInput = budgetDetails.querySelector('#total_expenses');
        const familyContributionInput = budgetDetails.querySelector('#family_contribution');
        const totalAmountRequestedInput = budgetDetails.querySelector('#amount_requested');

        // Function to calculate the total expenses
        function calculateTotalExpenses() {
            let totalExpenses = 0;
            const amountFields = budgetDetails.querySelectorAll('.amount-field');
            amountFields.forEach(function(input) {
                const value = parseFloat(input.value);
                if (!isNaN(value)) {
                    totalExpenses += value;
                }
            });
            totalExpensesInput.value = totalExpenses.toFixed(2);
            calculateTotalAmountRequested(); // Recalculate the total amount requested
        }

        // Function to calculate the total amount requested
        function calculateTotalAmountRequested() {
            const totalExpenses = parseFloat(totalExpensesInput.value) || 0;
            const familyContribution = parseFloat(familyContributionInput.value) || 0;
            const totalAmountRequested = totalExpenses - familyContribution;
            totalAmountRequestedInput.value = totalAmountRequested.toFixed(2);
        }

        // Add event listener for "Add More" button within the scoped container
        budgetDetails.querySelector('#add-budget-item-iah').addEventListener('click', function () {
            const newRow = `
                <tr>
                    <td>
                        <input type="text" name="particular[]" class="form-control" placeholder="Enter particular" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="amount[]" class="form-control amount-field" placeholder="Enter amount" required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger remove-budget-item">Remove</button>
                    </td>
                </tr>
            `;
            budgetDetails.querySelector('#iah-budget-list-unique').insertAdjacentHTML('beforeend', newRow);

            // Attach change event to new amount input field for recalculating totals
            const newAmountField = budgetDetails.querySelectorAll('.amount-field');
            newAmountField.forEach(field => field.addEventListener('input', calculateTotalExpenses));
        });

        // Add event listener for removing budget items within the scoped container
        budgetDetails.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('remove-budget-item')) {
                e.target.closest('tr').remove();
                calculateTotalExpenses(); // Recalculate total after removal
            }
        });

        // Listen for changes in the amount fields and family contribution field
        budgetDetails.addEventListener('input', function(e) {
            if (e.target.classList.contains('amount-field')) {
                calculateTotalExpenses();
            } else if (e.target.id === 'family_contribution') {
                calculateTotalAmountRequested();
            }
        });

        // Initial calculation when page loads
        calculateTotalExpenses();
    });
})();
</script>
