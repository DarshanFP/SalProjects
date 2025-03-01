{{-- <div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Budget</h4>
    </div>
    <div class="card-body">

        <!-- Budget Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>DESCRIPTION</th>
                    <th>Cost</th>
                </tr>
            </thead>
            <tbody id="ilp-budget">
                <tr>
                    <td>
                        <input type="text" name="budget_desc[0]" class="form-control" placeholder="Enter description" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="cost[0]" class="form-control" placeholder="Enter cost" style="background-color: #202ba3;">
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Add/Remove Budget Items -->
        <button type="button" id="add-budget-item" class="btn btn-primary">Add more</button>
        <button type="button" id="remove-budget-item" class="btn btn-danger">Remove</button>

        <!-- Total Amount -->
        <div class="mt-4 mb-3">
            <label for="total_amount" class="form-label">Total amount:</label>
            <input type="number" step="0.01" name="total_amount" class="form-control" placeholder="Enter total amount" style="background-color: #202ba3;">
        </div>

        <!-- Beneficiary's Contribution -->
        <div class="mb-3">
            <label for="beneficiary_contribution" class="form-label">Beneficiary’s contribution:</label>
            <input type="number" step="0.01" name="beneficiary_contribution" class="form-control" placeholder="Enter beneficiary's contribution" style="background-color: #202ba3;">
        </div>

        <!-- Amount Requested -->
        <div class="mb-3">
            <label for="amount_requested" class="form-label">Amount requested:</label>
            <input type="number" step="0.01" name="amount_requested" class="form-control" placeholder="Enter amount requested" style="background-color: #202ba3;">
        </div>

    </div>
</div>

<script>
    (function(){
    document.addEventListener('DOMContentLoaded', function () {
        const budgetBody = document.getElementById('ilp-budget');
        const addBudgetItemBtn = document.getElementById('add-budget-item');
        const removeBudgetItemBtn = document.getElementById('remove-budget-item');
        let itemIndex = 1;

        addBudgetItemBtn.addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <input type="text" name="budget_desc[${itemIndex}]" class="form-control" placeholder="Enter description" style="background-color: #202ba3;">
                </td>
                <td>
                    <input type="number" step="0.01" name="cost[${itemIndex}]" class="form-control" placeholder="Enter cost" style="background-color: #202ba3;">
                </td>
            `;
            budgetBody.appendChild(row);
            itemIndex++;
        });

        removeBudgetItemBtn.addEventListener('click', function () {
            if (budgetBody.children.length > 1) {
                budgetBody.removeChild(budgetBody.lastElementChild);
            }
        });
    });
})();
</script> --}}
{{-- resources/views/projects/partials/ILP/budget.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Budget</h4>
    </div>
    <div class="card-body">

        <!-- Budget Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>DESCRIPTION</th>
                    <th>Cost</th>
                </tr>
            </thead>
            <tbody id="ilp-budget">
                <tr>
                    <td>
                        <input type="text" name="budget_desc[0]" class="form-control" placeholder="Enter description" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="cost[0]" class="form-control budget-cost" placeholder="Enter cost" style="background-color: #202ba3;">
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Add/Remove Budget Items -->
        <button type="button" id="add-budget-item" class="btn btn-primary">Add more</button>
        <button type="button" id="remove-budget-item" class="btn btn-danger">Remove</button>

        <!-- Total Amount -->
        <div class="mt-4 mb-3">
            <label for="total_amount" class="form-label">Total amount:</label>
            <input type="number" step="0.01" id="total_amount" name="total_amount" class="form-control" readonly style="background-color: #0c1427;" >
        </div>

        <!-- Beneficiary's Contribution -->
        <div class="mb-3">
            <label for="beneficiary_contribution" class="form-label">Beneficiary’s contribution:</label>
            <input type="number" step="0.01" id="beneficiary_contribution" name="beneficiary_contribution" class="form-control" placeholder="Enter beneficiary's contribution" style="background-color: #202ba3;">
        </div>

        <!-- Amount Requested -->
        <div class="mb-3">
            <label for="amount_requested" class="form-label">Amount requested:</label>
            <input type="number" step="0.01" id="amount_requested" name="amount_requested" class="form-control" readonly style="background-color: #0c1427;">
        </div>

    </div>
</div>

<script>
    (function(){
        document.addEventListener('DOMContentLoaded', function () {
            const budgetBody = document.getElementById('ilp-budget');
            const addBudgetItemBtn = document.getElementById('add-budget-item');
            const removeBudgetItemBtn = document.getElementById('remove-budget-item');
            const totalAmountField = document.getElementById('total_amount');
            const beneficiaryContributionField = document.getElementById('beneficiary_contribution');
            const amountRequestedField = document.getElementById('amount_requested');
            let itemIndex = 1;

            // Function to calculate the total cost
            function calculateTotalCost() {
                let total = 0;
                const costFields = document.querySelectorAll('.budget-cost');
                costFields.forEach(field => {
                    const value = parseFloat(field.value) || 0;
                    total += value;
                });
                totalAmountField.value = total.toFixed(2);
                calculateAmountRequested();
            }

            // Function to calculate the amount requested
            function calculateAmountRequested() {
                const totalAmount = parseFloat(totalAmountField.value) || 0;
                const beneficiaryContribution = parseFloat(beneficiaryContributionField.value) || 0;
                const amountRequested = totalAmount - beneficiaryContribution;
                amountRequestedField.value = amountRequested.toFixed(2);

                // Sync with overall project budget
                const overallBudget = document.getElementById('overall_project_budget');
                if (overallBudget) {
                    overallBudget.value = amountRequested.toFixed(2);
                }
            }

            // Add a new budget item row
            addBudgetItemBtn.addEventListener('click', function () {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <input type="text" name="budget_desc[${itemIndex}]" class="form-control" placeholder="Enter description" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="cost[${itemIndex}]" class="form-control budget-cost" placeholder="Enter cost" style="background-color: #202ba3;">
                    </td>
                `;
                budgetBody.appendChild(row);
                itemIndex++;
            });

            // Remove the last budget item row
            removeBudgetItemBtn.addEventListener('click', function () {
                if (budgetBody.children.length > 1) {
                    budgetBody.removeChild(budgetBody.lastElementChild);
                    calculateTotalCost();
                }
            });

            // Recalculate totals whenever a cost field is updated
            budgetBody.addEventListener('input', function (e) {
                if (e.target.classList.contains('budget-cost')) {
                    calculateTotalCost();
                }
            });

            // Recalculate the amount requested when beneficiary's contribution changes
            beneficiaryContributionField.addEventListener('input', calculateAmountRequested);
        });
    })();
</script>
