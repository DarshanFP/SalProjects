<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit Budget</h4>
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
                @foreach ($budgets ?? [] as $index => $budget)
                <tr>
                    <td>
                        <input type="text" name="budget_desc[{{ $index }}]" class="form-control" value="{{ $budget->budget_desc ?? '' }}" placeholder="Enter description">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="cost[{{ $index }}]" class="form-control budget-cost" value="{{ $budget->cost ?? '' }}" placeholder="Enter cost">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Add/Remove Budget Items -->
        <button type="button" id="add-budget-item" class="btn btn-primary">Add more</button>
        <button type="button" id="remove-budget-item" class="btn btn-danger">Remove</button>

        <!-- Total Amount -->
        <div class="mt-4 mb-3">
            <label for="total_amount" class="form-label">Total amount:</label>
            <input type="number" step="0.01" id="total_amount" name="total_amount" class="form-control" readonly value="{{ $total_amount ?? '' }}">
        </div>

        <!-- Beneficiary's Contribution -->
        <div class="mb-3">
            <label for="beneficiary_contribution" class="form-label">Beneficiary’s contribution:</label>
            <input type="number" step="0.01" id="beneficiary_contribution" name="beneficiary_contribution" class="form-control" value="{{ $beneficiary_contribution ?? '' }}" placeholder="Enter beneficiary's contribution">
        </div>

        <!-- Amount Requested -->
        <div class="mb-3">
            <label for="amount_requested" class="form-label">Amount requested:</label>
            <input type="number" step="0.01" id="amount_requested" name="amount_requested" class="form-control" readonly value="{{ $amount_requested ?? '' }}">
        </div>

        <!-- Estimated Annual Income -->
        {{-- <div class="mb-3">
            <label for="estimated_annual_income" class="form-label">Estimated Annual Income:</label>
            <input type="number" step="0.01" id="estimated_annual_income" name="estimated_annual_income" class="form-control" value="{{ $estimated_annual_income ?? '' }}" placeholder="Enter estimated annual income">
        </div>

        <!-- Estimated Annual Expenses -->
        <div class="mb-3">
            <label for="estimated_annual_expenses" class="form-label">Estimated Annual Expenses:</label>
            <input type="number" step="0.01" id="estimated_annual_expenses" name="estimated_annual_expenses" class="form-control" value="{{ $estimated_annual_expenses ?? '' }}" placeholder="Enter estimated annual expenses">
        </div> --}}
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
            const overallBudgetField = document.getElementById('overall_project_budget');

            let itemIndex = {{ count($budgets ?? []) }};

            function calculateTotalCost() {
                let total = 0;
                document.querySelectorAll('.budget-cost').forEach(field => {
                    total += parseFloat(field.value) || 0;
                });
                totalAmountField.value = total.toFixed(2);
                calculateAmountRequested();
            }

            function calculateAmountRequested() {
                const totalAmount = parseFloat(totalAmountField.value) || 0;
                const beneficiaryContribution = parseFloat(beneficiaryContributionField.value) || 0;
                const amountRequested = totalAmount - beneficiaryContribution;

                amountRequestedField.value = amountRequested.toFixed(2);

                // **Directly reflect Amount Requested in Overall Project Budget**
                if (overallBudgetField) {
                    overallBudgetField.value = amountRequested.toFixed(2);
                }
            }

            addBudgetItemBtn.addEventListener('click', function () {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <input type="text" name="budget_desc[${itemIndex}]" class="form-control" placeholder="Enter description">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="cost[${itemIndex}]" class="form-control budget-cost" placeholder="Enter cost">
                    </td>
                `;
                budgetBody.appendChild(row);
                itemIndex++;
            });

            removeBudgetItemBtn.addEventListener('click', function () {
                if (budgetBody.children.length > 1) {
                    budgetBody.removeChild(budgetBody.lastElementChild);
                    calculateTotalCost();
                }
            });

            budgetBody.addEventListener('input', function (e) {
                if (e.target.classList.contains('budget-cost')) {
                    calculateTotalCost();
                }
            });

            beneficiaryContributionField.addEventListener('input', calculateAmountRequested);

            calculateTotalCost();
        });
    })();
</script>
