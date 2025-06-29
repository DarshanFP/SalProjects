{{-- resources/views/projects/partials/Edit/ILP/budget.blade.php --}}
{{-- <pre>{{ print_r($ILPBudgets, return: true) }}</pre> --}}

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
                @if (!empty($ILPBudgets['budgets']) && is_countable($ILPBudgets['budgets']) && count($ILPBudgets['budgets']) > 0)
                    @foreach ($ILPBudgets['budgets'] as $index => $budget)
                        <tr>
                            <td>
                                <div class="form-control">{{ $budget->budget_desc ?? '' }}</div>
                            </td>
                            <td>
                                <div class="form-control">{{ $budget->cost ?? 0 }}</div>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>
                            <div class="form-control">No description</div>
                        </td>
                        <td>
                            <div class="form-control">0</div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Add/Remove Budget Items -->
        <button type="button" id="add-budget-item" class="btn btn-primary">Add more</button>
        <button type="button" id="remove-budget-item" class="btn btn-danger">Remove</button>

        <!-- Total Amount -->
        <div class="mt-4 mb-3">
            <label for="total_amount" class="form-label">Total amount:</label>
            <div class="form-control">{{ $ILPBudgets['total_amount'] ?? 0 }}</div>
        </div>

        <!-- Beneficiary's Contribution -->
        <div class="mb-3">
            <label for="beneficiary_contribution" class="form-label">Beneficiary's contribution:</label>
            <div class="form-control">{{ $ILPBudgets['beneficiary_contribution'] ?? 0 }}</div>
        </div>

        <!-- Amount Requested -->
        <div class="mb-3">
            <label for="amount_requested" class="form-label">Amount requested:</label>
            <div class="form-control">{{ $ILPBudgets['amount_requested'] ?? 0 }}</div>
        </div>

    </div>
</div>

<script>
    (function(){
    document.addEventListener('DOMContentLoaded', function () {
        const budgetBody = document.getElementById('ilp-budget');
        const addBudgetItemBtn = document.getElementById('add-budget-item');
        const removeBudgetItemBtn = document.getElementById('remove-budget-item');
        let itemIndex = {{ is_countable($ILPBudgets['budgets'] ?? null) ? count($ILPBudgets['budgets']) : 0 }};

        addBudgetItemBtn.addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <input type="text" name="budget_desc[${itemIndex}]" class="form-control" placeholder="Enter description">
                </td>
                <td>
                    <input type="number" step="0.01" name="cost[${itemIndex}]" class="form-control" placeholder="Enter cost">
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
</script>
