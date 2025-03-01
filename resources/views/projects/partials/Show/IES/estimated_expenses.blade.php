{{-- resources/views/projects/partials/show/IES/estimated_expenses.blade.php --}}
{{-- resources/views/projects/partials/show/IES/estimated_expenses.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>SHOW - Estimated Expenses</h4>
    </div>
    <div class="card-body">
        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Particular</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody id="IES-expenses-table">
                    @if(isset($IESExpenses) && $IESExpenses->expenseDetails->count() > 0)
                        @foreach($IESExpenses->expenseDetails as $detail)
                            <tr>
                                <td>
                                    <input type="text" value="{{ $detail->particular }}"
                                           class="form-control" readonly/>
                                </td>
                                <td>
                                    <input type="number" value="{{ $detail->amount }}"
                                           class="form-control IES-expense-input"
                                           step="0.01" readonly/>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        {{-- If no data, show one empty row --}}
                        <tr>
                            <td><input type="text" class="form-control" readonly></td>
                            <td><input type="number" class="form-control IES-expense-input" step="0.01" readonly></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Totals (Read-Only) -->
        <div class="mt-3 form-group">
            <label>Total expense of the study:</label>
            <input type="number" name="total_expenses"
                   value="{{ old('total_expenses', $IESExpenses->total_expenses ?? '') }}"
                   class="form-control" readonly>
        </div>
        <div class="form-group">
            <label>Scholarship expected from government:</label>
            <input type="number" name="expected_scholarship_govt"
                   value="{{ old('expected_scholarship_govt', $IESExpenses->expected_scholarship_govt ?? '') }}"
                   class="form-control" readonly>
        </div>
        <div class="form-group">
            <label>Support from other sources:</label>
            <input type="number" name="support_other_sources"
                   value="{{ old('support_other_sources', $IESExpenses->support_other_sources ?? '') }}"
                   class="form-control" readonly>
        </div>
        <div class="form-group">
            <label>Beneficiaries’ contribution:</label>
            <input type="number" name="beneficiary_contribution"
                   value="{{ old('beneficiary_contribution', $IESExpenses->beneficiary_contribution ?? '') }}"
                   class="form-control" readonly>
        </div>
        <div class="form-group">
            <label>Balance amount requested:</label>
            <input type="number" name="balance_requested"
                   value="{{ old('balance_requested', $IESExpenses->balance_requested ?? '') }}"
                   class="form-control" readonly>
        </div>
    </div>
</div>

<!-- JavaScript to ensure totals are accurate on load -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        IEScalculateTotalExpenses();
    });

    function IEScalculateTotalExpenses() {
        let totalExpenses = 0;
        document.querySelectorAll('.IES-expense-input').forEach(input => {
            totalExpenses += parseFloat(input.value) || 0;
        });
        document.querySelector('input[name="total_expenses"]').value = totalExpenses.toFixed(2);
        IEScalculateBalanceRequested();
    }

    function IEScalculateBalanceRequested() {
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
