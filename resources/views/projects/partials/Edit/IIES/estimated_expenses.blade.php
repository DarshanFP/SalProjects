{{-- resources/views/projects/partials/Edit/IES/estimated_expenses.blade.php --}}
@php
    // This either fetches the single expense record or `null`
    $iiesExpenses = $project->iiesExpenses;
    // If it's null, fallback to a "dummy" ProjectIIESExpenses instance
    if (!$iiesExpenses) {
       $iiesExpenses = new \App\Models\OldProjects\IIES\ProjectIIESExpenses();
    }

    // If $iiesExpenses->expenseDetails is null, fallback to an empty Collection
    $expenseDetails = $iiesExpenses->expenseDetails ?? collect();
@endphp

<div class="mb-3 card">
    <div class="card-header">
        <h4>EDIT - IIES Estimated Expenses this</h4>
    </div>
    <div class="card-body">
        <!-- Table for IIES Expenses -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th>Particular</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="IIES-expenses-table">
                    @if ($iiesExpenses && $iiesExpenses->expenseDetails->count())
                        @foreach ($iiesExpenses->expenseDetails as $index => $detail)
                            <tr>
                                <td style="text-align: center; vertical-align: middle;">{{ $index + 1 }}</td>
                                <td>
                                    <input type="text" name="iies_particulars[]" class="form-control"
                                           value="{{ old('iies_particulars[]', $detail->iies_particular) }}">
                                </td>
                                <td>
                                    <input type="number" name="iies_amounts[]" class="form-control IIES-expense-input"
                                           value="{{ old('iies_amounts[]', $detail->iies_amount) }}"
                                           step="0.01" oninput="IIEScalculateTotalExpenses()">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger" onclick="IIESremoveExpenseRow(this)">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td style="text-align: center; vertical-align: middle;">1</td>
                            <td><input type="text" name="iies_particulars[]" class="form-control"></td>
                            <td><input type="number" name="iies_amounts[]" class="form-control IIES-expense-input" step="0.01" oninput="IIEScalculateTotalExpenses()"></td>
                            <td><button type="button" class="btn btn-danger" onclick="IIESremoveExpenseRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <button type="button" class="mt-2 btn btn-primary" onclick="IIESaddExpenseRow()">Add More</button>
        </div>

        <!-- Total Expenses -->
        <div class="mt-3 form-group">
            <label>Total expense of the study:</label>
            <input type="number" name="iies_total_expenses" class="form-control" step="0.01"
                   value="{{ old('iies_total_expenses', $iiesExpenses->iies_total_expenses ?? '') }}" readonly>
        </div>

        <!-- Financial Contributions -->
        <div class="form-group">
            <label>Scholarship expected from government:</label>
            <input type="number" name="iies_expected_scholarship_govt" class="form-control" step="0.01"
                   value="{{ old('iies_expected_scholarship_govt', $iiesExpenses->iies_expected_scholarship_govt ?? '') }}"
                   oninput="IIEScalculateBalanceRequested()">
        </div>
        <div class="form-group">
            <label>Support from other sources:</label>
            <input type="number" name="iies_support_other_sources" class="form-control" step="0.01"
                   value="{{ old('iies_support_other_sources', $iiesExpenses->iies_support_other_sources ?? '') }}"
                   oninput="IIEScalculateBalanceRequested()">
        </div>
        <div class="form-group">
            <label>Beneficiaries' contribution:</label>
            <input type="number" name="iies_beneficiary_contribution" class="form-control" step="0.01"
                   value="{{ old('iies_beneficiary_contribution', $iiesExpenses->iies_beneficiary_contribution ?? '') }}"
                   oninput="IIEScalculateBalanceRequested()">
        </div>

        <!-- Balance Amount Requested -->
        <div class="form-group">
            <label>Balance amount requested:</label>
            <input type="number" name="iies_balance_requested" class="form-control" step="0.01"
                   value="{{ old('iies_balance_requested', $iiesExpenses->iies_balance_requested ?? '') }}" readonly>
        </div>
    </div>
</div>


<!-- JavaScript to manage table rows and calculate totals -->
<script>
    function IIESaddExpenseRow() {
        const table = document.querySelector('#IIES-expenses-table');
        const rowCount = table.children.length;
        const row = `
            <tr>
                <td style="text-align: center; vertical-align: middle;">${rowCount + 1}</td>
                <td><input type="text" name="iies_particulars[]" class="form-control"></td>
                <td><input type="number" name="iies_amounts[]" class="form-control IIES-expense-input" step="0.01" oninput="IIEScalculateTotalExpenses()"></td>
                <td><button type="button" class="btn btn-danger" onclick="IIESremoveExpenseRow(this)">Remove</button></td>
            </tr>`;
        table.insertAdjacentHTML('beforeend', row);
        reindexIIESExpenseRows();
    }

    function IIESremoveExpenseRow(button) {
        button.closest('tr').remove();
        reindexIIESExpenseRows();
        IIEScalculateTotalExpenses();
    }
    
    function reindexIIESExpenseRows() {
        const rows = document.querySelectorAll('#IIES-expenses-table tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
    }

    function IIEScalculateTotalExpenses() {
        let totalExpenses = 0;
        document.querySelectorAll('.IIES-expense-input').forEach(input => {
            totalExpenses += parseFloat(input.value) || 0;
        });
        document.querySelector('input[name="iies_total_expenses"]').value = totalExpenses.toFixed(2);
        IIEScalculateBalanceRequested();
    }

    function IIEScalculateBalanceRequested() {
        const totalExpenses = parseFloat(document.querySelector('input[name="iies_total_expenses"]').value) || 0;
        const scholarship = parseFloat(document.querySelector('input[name="iies_expected_scholarship_govt"]').value) || 0;
        const otherSources = parseFloat(document.querySelector('input[name="iies_support_other_sources"]').value) || 0;
        const contribution = parseFloat(document.querySelector('input[name="iies_beneficiary_contribution"]').value) || 0;
        const balanceRequested = totalExpenses - (scholarship + otherSources + contribution);
        document.querySelector('input[name="iies_balance_requested"]').value = balanceRequested.toFixed(2);

        // Update the #overall_project_budget in the parent form
        const overallBudget = document.getElementById('overall_project_budget');
        if (overallBudget) {
            overallBudget.value = balanceRequested.toFixed(2);
        }
    }
</script>
