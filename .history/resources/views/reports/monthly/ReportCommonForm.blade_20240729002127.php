@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <form action="{{ route('monthly.report.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->project_id }}">

                <div class="mb-3 card">
                    <div class="card-header">
                        <h4 class="fp-text-center1">TRACKING DEVELOPMENT PROJECT</h4>
                        <h4 class="fp-text-center1">MONTHLY PROGRESS REPORT (common)</h4>
                    </div>
                    <div class="card-header">
                        <h4 class="fp-text-margin">Basic Information</h4>
                    </div>
                    <div class="card-body">
                        <!-- Basic Information Fields -->
                        @include('reports.monthly.partials.basic_information', ['project' => $project, 'user' => $user])
                    </div>
                </div>

                <!-- Key Information Section -->
                <div class="mb-3 card">
                    <div class="card-header">
                        <h4>1. Key Information</h4>
                    </div>
                    <div class="card-body">
                        @include('reports.monthly.partials.key_information', ['project' => $project])
                    </div>
                </div>

                <!-- Objectives Section -->
                <div id="objectives-container">
                    <div class="mb-3 card objective" data-index="1">
                        <div class="card-header">
                            <h4>2. Activities and Intermediate Outcomes</h4>
                        </div>
                        @include('reports.monthly.partials.objective', ['index' => 1])
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="addObjective()">Add More Objective</button>

                <!-- Outlook Section  -->
                <div id="outlook-container">
                    <div class="mb-3 card outlook" data-index="1">
                        <div class="card-header">
                            <h4>3. Outlook</h4>
                        </div>
                        @include('reports.monthly.partials.outlook', ['index' => 1])
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="addOutlook()">Add More Outlook</button>

                <!-- Include Statements of Account Partial -->
                @include('reports.monthly.partials.statements_of_account', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses])

                <!-- Photos Section -->
                <div class="mb-3 card">
                    <div class="card-header">
                        <h4>5. Photos</h4>
                    </div>
                    <div class="card-body">
                        @include('reports.monthly.partials.photos')
                    </div>
                </div>

                <button type="submit" class="btn btn-primary me-2">Submit Report</button>
            </form>
        </div>
    </div>
</div>

<script>
    function calculateRowTotals(row) {
        const amountForwarded = parseFloat(row.querySelector('[name="amount_forwarded[]"]').value) || 0;
        const amountSanctioned = parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
        const expensesLastMonth = parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
        const expensesThisMonth = parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;

        const totalAmount = amountForwarded + amountSanctioned;
        const totalExpenses = expensesLastMonth + expensesThisMonth;
        const balanceAmount = totalAmount - totalExpenses;

        row.querySelector('[name="total_amount[]"]').value = totalAmount.toFixed(2);
        row.querySelector('[name="total_expenses[]"]').value = totalExpenses.toFixed(2);
        row.querySelector('[name="balance_amount[]"]').value = balanceAmount.toFixed(2);

        calculateTotal(); // Recalculate totals whenever a row total is updated
    }

    function calculateTotal() {
        const rows = document.querySelectorAll('#account-rows tr');
        let totalForwarded = 0;
        let totalSanctioned = 0;
        let totalAmountTotal = 0;
        let totalExpensesLastMonth = 0;
        let totalExpensesThisMonth = 0;
        let totalExpensesTotal = 0;
        let totalBalance = 0;

        rows.forEach(row => {
            totalForwarded += parseFloat(row.querySelector('[name="amount_forwarded[]"]').value) || 0;
            totalSanctioned += parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
            totalAmountTotal += parseFloat(row.querySelector('[name="total_amount[]"]').value) || 0;
            totalExpensesLastMonth += parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
            totalExpensesThisMonth += parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;
            totalExpensesTotal += parseFloat(row.querySelector('[name="total_expenses[]"]').value) || 0;
            totalBalance += parseFloat(row.querySelector('[name="balance_amount[]"]').value) || 0;
        });

        document.getElementById('total_forwarded').value = totalForwarded.toFixed(2);
        document.getElementById('total_sanctioned').value = totalSanctioned.toFixed(2);
        document.getElementById('total_amount_total').value = totalAmountTotal.toFixed(2);
        document.getElementById('total_expenses_last_month').value = totalExpensesLastMonth.toFixed(2);
        document.getElementById('total_expenses_this_month').value = totalExpensesThisMonth.toFixed(2);
        document.getElementById('total_expenses_total').value = totalExpensesTotal.toFixed(2);
        document.getElementById('total_balance').value = totalBalance.toFixed(2);

        // Update the total balance forwarded field
        document.querySelector('[name="total_balance_forwarded"]').value = totalBalance.toFixed(2);
    }

    function addAccountRow() {
        const tableBody = document.getElementById('account-rows');
        const newRow = document.createElement('tr');

        newRow.innerHTML = `
            <td><input type="text" name="particulars[]" class="form-control" style="background-color: #6571ff;"></td>
            <td><input type="number" name="amount_forwarded[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;"></td>
            <td><input type="number" name="amount_sanctioned[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;"></td>
            <td><input type="number" name="total_amount[]" class="form-control" readonly></td>
            <td><input type="number" name="expenses_last_month[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;"></td>
            <td><input type="number" name="expenses_this_month[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;"></td>
            <td><input type="number" name="total_expenses[]" class="form-control" readonly></td>
            <td><input type="number" name="balance_amount[]" class="form-control" readonly></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button></td>
        `;

        newRow.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                const row = input.closest('tr');
                calculateRowTotals(row);
                calculateTotal();
            });
        });

        tableBody.appendChild(newRow);
    }

    function removeAccountRow(button) {
        const row = button.closest('tr');
        row.remove();
        calculateTotal(); // Recalculate totals after removing a row
    }

    document.addEventListener('DOMContentLoaded', function() {
        calculateTotal();
    });
</script>

<style>
    .readonly-input {
        background-color: #0D1427;
        color: #f4f0f0;
    }

    .select-input {
        background-color: #0e285c;
        color: #f4f0f0;
    }

    .readonly-select {
        background-color: #072c75;
        color: #f4f0f0;
    }

    .table th, .table td {
        vertical-align: middle;
        text-align: center;
        padding: 0;
    }

    .table th {
        white-space: normal;
    }

    .table td input {
        width: 100%;
        box-sizing: border-box;
        -moz-appearance: textfield;
        padding: 0.375rem 0.75rem;
    }

    .table td input::-webkit-outer-spin-button,
    .table td input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .table-container {
        overflow-x: auto;
    }

    .fp-text-center1 {
        text-align: center;
        margin-bottom: 15px;
    }

    .fp-text-margin {
        margin-bottom: 15px;
    }
</style>
@endsection
