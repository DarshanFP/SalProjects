@extends('executor.dashboard')

@section('content')
<div class="container">
    <h1>Project Budget for Project ID: {{ $projectBudget->project_id }}</h1>
    <h2>Total Budget: {{ $projectBudget->calculateTotalBudget() }}</h2>
    <h2>Remaining Balance: {{ $remainingBalance }}</h2>

    <h3>Expenses:</h3>
    <ul>
        @foreach ($projectBudget->dpAccountDetails as $expense)
            <li>{{ $expense->particulars }}: ${{ $expense->total_expenses }}</li>
        @endforeach
    </ul>

    <h3>Add Expense</h3>
    <form action="/budgets/{{ $projectBudget->project_id }}/expenses" method="POST">
        @csrf
        <label for="report_id">Report ID:</label>
        <input type="text" id="report_id" name="report_id" required><br>
        <label for="particulars">Particulars:</label>
        <input type="text" id="particulars" name="particulars" required><br>
        <label for="amount_forwarded">Amount Forwarded:</label>
        <input type="number" id="amount_forwarded" name="amount_forwarded"><br>
        <label for="amount_sanctioned">Amount Sanctioned:</label>
        <input type="number" id="amount_sanctioned" name="amount_sanctioned"><br>
        <label for="total_amount">Total Amount:</label>
        <input type="number" id="total_amount" name="total_amount" required><br>
        <label for="expenses_last_month">Expenses Last Month:</label>
        <input type="number" id="expenses_last_month" name="expenses_last_month"><br>
        <label for="expenses_this_month">Expenses This Month:</label>
        <input type="number" id="expenses_this_month" name="expenses_this_month" required><br>
        <label for="total_expenses">Total Expenses:</label>
        <input type="number" id="total_expenses" name="total_expenses" required><br>
        <label for="balance_amount">Balance Amount:</label>
        <input type="number" id="balance_amount" name="balance_amount" required><br>
        <button type="submit">Add Expense</button>
    </form>
</div>
@endsection
