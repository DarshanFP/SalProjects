{{-- resources/views/projects/partials/Edit/IES/estimated_expenses.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Estimated Expenses (give full details)</h4>
    </div>
    <div class="card-body">
        <!-- Estimated Expenses Table -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Particular</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="expenses-table">
                    @if($project->iesExpenses && $project->iesExpenses->count())
                        @foreach($project->iesExpenses as $index => $expense)
                        <tr>
                            <td><input type="text" name="particulars[]" class="form-control" value="{{ old('particulars.' . $index, $expense->particular) }}" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="amounts[]" class="form-control expense-input" step="0.01" value="{{ old('amounts.' . $index, $expense->amount) }}" style="background-color: #202ba3;" oninput="calculateTotalExpenses()"></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeExpenseRow(this)">Remove</button></td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td><input type="text" name="particulars[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="amounts[]" class="form-control expense-input" step="0.01" style="background-color: #202ba3;" oninput="calculateTotalExpenses()"></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeExpenseRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <button type="button" class="mt-2 btn btn-primary" onclick="addExpenseRow()">Add More</button>
        </div>

        <!-- Total Expense -->
        <div class="mt-3 form-group">
            <label>Total expense of the study:</label>
            <input type="number" name="total_expenses" class="form-control" step="0.01" value="{{ old('total_expenses', $project->total_expenses) }}" style="background-color: #202ba3;" readonly>
        </div>

        <!-- Financial Contributions -->
        <div class="form-group">
            <label>Scholarship expected from government:</label>
            <input type="number" name="expected_scholarship_govt" class="form-control" step="0.01" value="{{ old('expected_scholarship_govt', $project->expected_scholarship_govt) }}" style="background-color: #202ba3;" oninput="calculateBalanceRequested()">
        </div>
        <div class="form-group">
            <label>Support from other sources:</label>
            <input type="number" name="support_other_sources" class="form-control" step="0.01" value="{{ old('support_other_sources', $project->support_other_sources) }}" style="background-color: #202ba3;" oninput="calculateBalanceRequested()">
        </div>
        <div class="form-group">
            <label>Beneficiaries’ contribution:</label>
            <input type="number" name="beneficiary_contribution" class="form-control" step="0.01" value="{{ old('beneficiary_contribution', $project->beneficiary_contribution) }}" style="background-color: #202ba3;" oninput="calculateBalanceRequested()">
        </div>

        <!-- Balance Amount Requested -->
        <div class="form-group">
            <label>Balance amount requested:</label>
            <input type="number" name="balance_requested" class="form-control" step="0.01" value="{{ old('balance_requested', $project->balance_requested) }}" style="background-color: #202ba3;" readonly>
        </div>
    </div>
</div>

<!-- JavaScript to manage table rows and calculate totals -->
<script>
    function addExpenseRow() {
        const row = `
            <tr>
                <td><input type="text" name="particulars[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="number" name="amounts[]" class="form-control expense-input" step="0.01" style="background-color: #202ba3;" oninput="calculateTotalExpenses()"></td>
                <td><button type="button" class="btn btn-danger" onclick="removeExpenseRow(this)">Remove</button></td>
            </tr>`;
        document.querySelector('#expenses-table').insertAdjacentHTML('beforeend', row);
    }

    function removeExpenseRow(button) {
        button.closest('tr').remove();
        calculateTotalExpenses();
    }

    function calculateTotalExpenses() {
        let totalExpenses = 0;
        document.querySelectorAll('.expense-input').forEach(input => {
            totalExpenses += parseFloat(input.value) || 0;
        });
        document.querySelector('input[name="total_expenses"]').value = totalExpenses.toFixed(2);
        calculateBalanceRequested();
    }

    function calculateBalanceRequested() {
        const totalExpenses = parseFloat(document.querySelector('input[name="total_expenses"]').value) || 0;
        const scholarship = parseFloat(document.querySelector('input[name="expected_scholarship_govt"]').value) || 0;
        const otherSources = parseFloat(document.querySelector('input[name="support_other_sources"]').value) || 0;
        const contribution = parseFloat(document.querySelector('input[name="beneficiary_contribution"]').value) || 0;
        const balanceRequested = totalExpenses - (scholarship + otherSources + contribution);
        document.querySelector('input[name="balance_requested"]').value = balanceRequested.toFixed(2);
    }
</script>

<!-- Styles -->
<style>
    .form-control {
        background-color: #202ba3;
        color: white;
    }
</style>
