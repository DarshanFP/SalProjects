{{-- resources/views/projects/partials/Edit/IAH/budget_details.blade.php --}}
<div class="mb-4 card iah-budget-details">
    <div class="card-header">
        <h4 class="mb-0">Edit: Estimated Cost of Treatment – Budget Details</h4>
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
                @forelse($project->iahBudgetDetails as $budget)
                    <tr>
                        <td>
                            <input type="text" name="particular[]" class="form-control"
                                   value="{{ old('particular[]', $budget->particular) }}"
                                   placeholder="Enter particular">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="amount[]" class="form-control amount-field"
                                   value="{{ old('amount[]', $budget->amount) }}"
                                   placeholder="Enter amount">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger remove-budget-item">Remove</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td>
                            <input type="text" name="particular[]" class="form-control"
                                   placeholder="Enter particular">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="amount[]" class="form-control amount-field"
                                   placeholder="Enter amount">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger remove-budget-item">Remove</button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <button type="button" class="btn btn-primary" id="add-budget-item-iah">Add More</button>

        <div class="mt-4">
            <label for="total_expenses" class="form-label">Total Expenses:</label>
            <input type="number" step="0.01" name="total_expenses" class="form-control" id="total_expenses"
                   value="{{ old('total_expenses', $project->iahBudgetDetails->sum('amount') ?? 0) }}" readonly>
        </div>

        <div class="mt-3">
            <label for="family_contribution" class="form-label">Family Contribution:</label>
            <input type="number" step="0.01" name="family_contribution" class="form-control" id="family_contribution"
                   value="{{ old('family_contribution', $project->iahBudgetDetails->first()->family_contribution ?? 0) }}">
        </div>

        <div class="mt-3">
            <label for="amount_requested" class="form-label">Total Amount Requested:</label>
            <input type="number" step="0.01" name="amount_requested" class="form-control" id="amount_requested"
                   value="{{ old('amount_requested', ($project->iahBudgetDetails->sum('amount') ?? 0)
                                - ($project->iahBudgetDetails->first()->family_contribution ?? 0)) }}"
                   readonly>
        </div>

        {{-- If you have an overall project budget field on the page, ensure it has this ID --}}
        {{-- <div class="mt-3">
            <label for="overall_project_budget" class="form-label">Overall Project Budget:</label>
            <input type="number" step="0.01" name="overall_project_budget" class="form-control" id="overall_project_budget"
                   value="{{ old('overall_project_budget', $project->overall_project_budget ?? 0) }}" readonly>
        </div> --}}

    </div>
</div>

<script>
(function(){
    document.addEventListener('DOMContentLoaded', function() {
        const budgetDetails = document.querySelector('.iah-budget-details');
        const totalExpensesInput = budgetDetails.querySelector('#total_expenses');
        const familyContributionInput = budgetDetails.querySelector('#family_contribution');
        const totalAmountRequestedInput = budgetDetails.querySelector('#amount_requested');

        // Optional: If you have an #overall_project_budget element in your form
        const overallBudget = document.getElementById('overall_project_budget');

        // Calculate total expenses
        function calculateTotalExpenses() {
            let totalExpenses = 0;
            const amountFields = budgetDetails.querySelectorAll('.amount-field');
            amountFields.forEach(function(input) {
                const value = parseFloat(input.value) || 0; // Use 0 if NaN
                totalExpenses += value;
            });
            totalExpensesInput.value = totalExpenses.toFixed(2);

            calculateTotalAmountRequested();
        }

        // Calculate total amount requested
        function calculateTotalAmountRequested() {
            const totalExpenses = parseFloat(totalExpensesInput.value) || 0;
            const familyContribution = parseFloat(familyContributionInput.value) || 0;
            const totalAmountRequested = totalExpenses - familyContribution;

            totalAmountRequestedInput.value = totalAmountRequested.toFixed(2);

            // Also update overall_project_budget if present
            if (overallBudget) {
                overallBudget.value = totalAmountRequested.toFixed(2);
            }
        }

        // Add new budget item
        budgetDetails.querySelector('#add-budget-item-iah').addEventListener('click', function () {
            const newRow = `
                <tr>
                    <td>
                        <input type="text" name="particular[]" class="form-control" placeholder="Enter particular">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="amount[]" class="form-control amount-field" placeholder="Enter amount">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger remove-budget-item">Remove</button>
                    </td>
                </tr>
            `;
            budgetDetails.querySelector('#iah-budget-list-unique').insertAdjacentHTML('beforeend', newRow);

            // Re-attach input event to newly added fields
            const newAmountFields = budgetDetails.querySelectorAll('.amount-field');
            newAmountFields.forEach(field => {
                field.removeEventListener('input', calculateTotalExpenses); // Prevent double-binding
                field.addEventListener('input', calculateTotalExpenses);
            });
        });

        // Handle remove item & input events
        budgetDetails.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('remove-budget-item')) {
                e.target.closest('tr').remove();
                calculateTotalExpenses();
            }
        });

        budgetDetails.addEventListener('input', function(e) {
            if (e.target.classList.contains('amount-field')) {
                calculateTotalExpenses();
            } else if (e.target.id === 'family_contribution') {
                calculateTotalAmountRequested();
            }
        });

        // Initial calculation
        calculateTotalExpenses();
    });
})();
</script>
