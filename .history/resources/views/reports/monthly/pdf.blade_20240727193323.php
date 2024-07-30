<!DOCTYPE html>
<html>
<head>
    <title>Monthly Report PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .container {
            margin: 20px;
        }

        h1, h2 {
            color: #333;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            grid-gap: 10px;
        }

        .info-label {
            font-weight: bold;
        }

        .info-value {
            word-wrap: break-word;
            margin-left: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .img-fluid {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Monthly Report Details</h1>

    <div class="info-grid">
        <div class="info-label">Report ID:</div>
        <div class="info-value">{{ $report->report_id }}</div>
        <div class="info-label">Project ID:</div>
        <div class="info-value">{{ $report->project_id }}</div>
        <div class="info-label">Project Title:</div>
        <div class="info-value">{{ $report->project_title }}</div>
        <div class="info-label">Project Type:</div>
        <div class="info-value">{{ $report->project_type }}</div>
        <div class="info-label">Place:</div>
        <div class="info-value">{{ $report->place }}</div>
        <div class="info-label">Society Name:</div>
        <div class="info-value">{{ $report->society_name }}</div>
        <div class="info-label">In Charge:</div>
        <div class="info-value">{{ $report->in_charge }}</div>
        <div class="info-label">Total Beneficiaries:</div>
        <div class="info-value">{{ $report->total_beneficiaries }}</div>
        <div class="info-label">Report Month Year:</div>
        <div class="info-value">{{ \Carbon\Carbon::parse($report->report_month_year)->format('F Y') }}</div>
        <div class="info-label">Goal:</div>
        <div class="info-value">{{ $report->goal }}</div>
        <div class="info-label">Account Period Start:</div>
        <div class="info-value">{{ $report->account_period_start }}</div>
        <div class="info-label">Account Period End:</div>
        <div class="info-value">{{ $report->account_period_end }}</div>
        <div class="info-label">Amount Sanctioned Overview:</div>
        <div class="info-value">Rs. {{ number_format($report->amount_sanctioned_overview, 2) }}</div>
        <div class="info-label">Amount Forwarded Overview:</div>
        <div class="info-value">Rs. {{ number_format($report->amount_forwarded_overview, 2) }}</div>
        <div class="info-label">Amount In Hand:</div>
        <div class="info-value">Rs. {{ number_format($report->amount_in_hand, 2) }}</div>
        <div class="info-label">Total Balance Forwarded:</div>
        <div class="info-value">Rs. {{ number_format($report->total_balance_forwarded, 2) }}</div>
    </div>

    <h2>Objectives</h2>
    @foreach($report->objectives as $objective)
        <div class="info-grid">
            <div class="info-label">Objective:</div>
            <div class="info-value">{{ $objective->objective }}</div>
            <div class="info-label">Expected Outcome:</div>
            <div class="info-value">{{ $objective->expected_outcome }}</div>
            <div class="info-label">Not Happened:</div>
            <div class="info-value">{{ $objective->not_happened }}</div>
            <div class="info-label">Why Not Happened:</div>
            <div class="info-value">{{ $objective->why_not_happened }}</div>
            <div class="info-label">Changes:</div>
            <div class="info-value">{{ $objective->changes ? 'Yes' : 'No' }}</div>
            <div class="info-label">Why Changes:</div>
            <div class="info-value">{{ $objective->why_changes }}</div>
            <div class="info-label">Lessons Learnt:</div>
            <div class="info-value">{{ $objective->lessons_learnt }}</div>
            <div class="info-label">ToDo Lessons Learnt:</div>
            <div class="info-value">{{ $objective->todo_lessons_learnt }}</div>
        </div>

        <h3>Activities</h3>
        @foreach($objective->activities as $activity)
            <div class="info-grid">
                <div class="info-label">Activity Month:</div>
                <div class="info-value">{{ $activity->month }}</div>
                <div class="info-label">Summary Activities:</div>
                <div class="info-value">{{ $activity->summary_activities }}</div>
                <div class="info-label">Qualitative Quantitative Data:</div>
                <div class="info-value">{{ $activity->qualitative_quantitative_data }}</div>
                <div class="info-label">Intermediate Outcomes:</div>
                <div class="info-value">{{ $activity->intermediate_outcomes }}</div>
            </div>
        @endforeach
    @endforeach

    <h2>Account Details</h2>
    <div class="info-grid">
        <div class="info-label">Account Period:</div>
        <div class="info-value">{{ $report->account_period_start }} to {{ $report->account_period_end }}</div>
        <div class="info-label">Amount Sanctioned:</div>
        <div class="info-value">Rs. {{ number_format($report->amount_sanctioned_overview, 2) }}</div>
        <div class="info-label">Amount Forwarded:</div>
        <div class="info-value">Rs. {{ number_format($report->amount_forwarded_overview, 2) }}</div>
        <div class="info-label">Total Amount:</div>
        <div class="info-value">Rs. {{ number_format($report->amount_in_hand, 2) }}</div>
        <div class="info-label">Balance Forwarded:</div>
        <div class="info-value">Rs. {{ number_format($report->total_balance_forwarded, 2) }}</div>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>Particulars</th>
            <th>Amount Forwarded</th>
            <th>Amount Sanctioned</th>
            <th>Total Amount</th>
            <th>Expenses Last Month</th>
            <th>Expenses This Month</th>
            <th>Total Expenses</th>
            <th>Balance Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach($report->accountDetails as $accountDetail)
            <tr>
                <td>{{ $accountDetail->particulars }}</td>
                <td>Rs. {{ number_format($accountDetail->amount_forwarded, 2) }}</td>
                <td>Rs. {{ number_format($accountDetail->amount_sanctioned, 2) }}</td>
                <td>Rs. {{ number_format($accountDetail->total_amount, 2) }}</td>
                <td>Rs. {{ number_format($accountDetail->expenses_last_month, 2) }}</td>
                <td>Rs. {{ number_format($accountDetail->expenses_this_month, 2) }}</td>
                <td>Rs. {{ number_format($accountDetail->total_expenses, 2) }}</td>
                <td>Rs. {{ number_format($accountDetail->balance_amount, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th>Total</th>
            <th>Rs. {{ number_format($report->accountDetails->sum('amount_forwarded'), 2) }}</th>
            <th>Rs. {{ number_format($report->accountDetails->sum('amount_sanctioned'), 2) }}</th>
            <th>Rs. {{ number_format($report->accountDetails->sum('total_amount'), 2) }}</th>
            <th>Rs. {{ number_format($report->accountDetails->sum('expenses_last_month'), 2) }}</th>
            <th>Rs. {{ number_format($report->accountDetails->sum('expenses_this_month'), 2) }}</th>
            <th>Rs. {{ number_format($report->accountDetails->sum('total_expenses'), 2) }}</th>
            <th>Rs. {{ number_format($report->accountDetails->sum('balance_amount'), 2) }}</th>
        </tr>
        </tfoot>
    </table>

    <h2>Outlooks</h2>
    @foreach($report->outlooks as $outlook)
        <div class="info-grid">
            <div class="info-label">Date:</div>
            <div class="info-value">{{ $outlook->date }}</div>
            <div class="info-label">Plan Next Month:</div>
            <div class="info-value">{{ $outlook->plan_next_month }}</div>
        </div>
    @endforeach

    <h2>Photos</h2>
    @foreach($report->photos as $photo)
        <div class="info-grid">
            <div class="info-label">Photo:</div>
            <div class="info-value"><img src="{{ asset('storage/' . $photo->photo_path) }}" class="img-fluid" /></div>
            <div class="info-label">Description:</div>
            <div class="info-value">{{ $photo->description }}</div>
        </div>
    @endforeach
</div>
</body>
</html>
