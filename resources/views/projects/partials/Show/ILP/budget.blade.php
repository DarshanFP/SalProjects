{{-- resources/views/projects/partials/Edit/ILP/budget.blade.php --}}
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
                @foreach ($budgets as $index => $budget)
                <tr>
                    <td>
                        <input type="text" name="budget_desc[{{ $index }}]" class="form-control" value="{{ $budget->budget_desc }}" placeholder="Enter description" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="cost[{{ $index }}]" class="form-control" value="{{ $budget->cost }}" placeholder="Enter cost" style="background-color: #202ba3;">
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
            <input type="number" step="0.01" name="total_amount" class="form-control" value="{{ $total_amount }}" placeholder="Enter total amount" style="background-color: #202ba3;">
        </div>

        <!-- Beneficiary's Contribution -->
        <div class="mb-3">
            <label for="beneficiary_contribution" class="form-label">Beneficiary’s contribution:</label>
            <input type="number" step="0.01" name="beneficiary_contribution" class="form-control" value="{{ $beneficiary_contribution }}" placeholder="Enter beneficiary's contribution" style="background-color: #202ba3;">
        </div>

        <!-- Amount Requested -->
        <div class="mb-3">
            <label for="amount_requested" class="form-label">Amount requested:</label>
            <input type="number" step="0.01" name="amount_requested" class="form-control" value="{{ $amount_requested }}" placeholder="Enter amount requested" style="background-color: #202ba3;">
        </div>

    </div>
</div>

<script>
    (function(){
    document.addEventListener('DOMContentLoaded', function () {
        const budgetBody = document.getElementById('ilp-budget');
        const addBudgetItemBtn = document.getElementById('add-budget-item');
        const removeBudgetItemBtn = document.getElementById('remove-budget-item');
        let itemIndex = {{ count($budgets) }};

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
</script>

<!-- Styles -->
<style>
    .form-control {
        background-color: #202ba3;
        color: white;
    }
</style>
