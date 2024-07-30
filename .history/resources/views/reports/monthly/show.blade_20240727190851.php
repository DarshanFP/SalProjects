@extends('executor.dashboard')

@section('content')
<div class="container">
    <h1 class="mb-4">Report Details</h1>

    <!-- General Information Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>General Information</h4>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-label"><strong>Report ID:</strong></div>
                <div class="info-value">{{ $report->report_id }}</div>
                <div class="info-label"><strong>Project ID:</strong></div>
                <div class="info-value">{{ $report->project_id }}</div>
                <div class="info-label"><strong>Project Title:</strong></div>
                <div class="info-value">{{ $report->project_title }}</div>
                <div class="info-label"><strong>Project Type:</strong></div>
                <div class="info-value">{{ $report->project_type }}</div>
                <div class="info-label"><strong>Place:</strong></div>
                <div class="info-value">{{ $report->place }}</div>
                <div class="info-label"><strong>Society Name:</strong></div>
                <div class="info-value">{{ $report->society_name }}</div>
                <div class="info-label"><strong>In Charge:</strong></div>
                <div class="info-value">{{ $report->in_charge }}</div>
                <div class="info-label"><strong>Total Beneficiaries:</strong></div>
                <div class="info-value">{{ $report->total_beneficiaries }}</div>
                <div class="info-label"><strong>Report Month Year:</strong></div>
                <div class="info-value">{{ $report->report_month_year }}</div>
                <div class="info-label"><strong>Goal:</strong></div>
                <div class="info-value">{{ $report->goal }}</div>
                <div class="info-label"><strong>Account Period Start:</strong></div>
                <div class="info-value">{{ $report->account_period_start }}</div>
                <div class="info-label"><strong>Account Period End:</strong></div>
                <div class="info-value">{{ $report->account_period_end }}</div>
                <div class="info-label"><strong>Amount Sanctioned Overview:</strong></div>
                <div class="info-value">Rs. {{ number_format($report->amount_sanctioned_overview, 2) }}</div>
                <div class="info-label"><strong>Amount Forwarded Overview:</strong></div>
                <div class="info-value">Rs. {{ number_format($report->amount_forwarded_overview, 2) }}</div>
                <div class="info-label"><strong>Amount In Hand:</strong></div>
                <div class="info-value">Rs. {{ number_format($report->amount_in_hand, 2) }}</div>
                <div class="info-label"><strong>Total Balance Forwarded:</strong></div>
                <div class="info-value">Rs. {{ number_format($report->total_balance_forwarded, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Objectives Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Objectives</h4>
        </div>
        <div class="card-body">
            @foreach($report->objectives as $objective)
                <div class="mb-3 objective">
                    <label>Objective:</label>
                    <p>{{ $objective->objective }}</p>
                    <label>Expected Outcome:</label>
                    <p>{{ $objective->expected_outcome }}</p>
                    <label>Not Happened:</label>
                    <p>{{ $objective->not_happened }}</p>
                    <label>Why Not Happened:</label>
                    <p>{{ $objective->why_not_happened }}</p>
                    <label>Changes:</label>
                    <p>{{ $objective->changes ? 'Yes' : 'No' }}</p>
                    <label>Why Changes:</label>
                    <p>{{ $objective->why_changes }}</p>
                    <label>Lessons Learnt:</label>
                    <p>{{ $objective->lessons_learnt }}</p>
                    <label>ToDo Lessons Learnt:</label>
                    <p>{{ $objective->todo_lessons_learnt }}</p>

                    <!-- Activities -->
                    @foreach($objective->activities as $activity)
                        <div class="mb-3 activity">
                            <label>Activity Month:</label>
                            <p>{{ $activity->month }}</p>
                            <label>Summary Activities:</label>
                            <p>{{ $activity->summary_activities }}</p>
                            <label>Qualitative Quantitative Data:</label>
                            <p>{{ $activity->qualitative_quantitative_data }}</p>
                            <label>Intermediate Outcomes:</label>
                            <p>{{ $activity->intermediate_outcomes }}</p>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <!-- Account Details Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Account Details</h4>
        </div>
        <div class="card-body">
            <p><strong>Account Period:</strong> {{ $report->account_period_start }} to {{ $report->account_period_end }}</p>
            <p><strong>Amount Sanctioned:</strong> Rs. {{ number_format($report->amount_sanctioned_overview, 2) }}</p>
            <p><strong>Amount Forwarded:</strong> Rs. {{ number_format($report->amount_forwarded_overview, 2) }}</p>
            <p><strong>Total Amount:</strong> Rs. {{ number_format($report->amount_in_hand, 2) }}</p>
            <p><strong>Balance Forwarded:</strong> Rs. {{ number_format($report->total_balance_forwarded, 2) }}</p>

            <table class="table table-bordered table-custom">
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
        </div>
    </div>

    <!-- Outlooks Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Outlooks</h4>
        </div>
        <div class="card-body">
            @foreach($report->outlooks as $outlook)
                <div class="mb-3 outlook">
                    <label>Date:</label>
                    <p>{{ $outlook->date }}</p>
                    <label>Plan Next Month:</label>
                    <p>{{ $outlook->plan_next_month }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Photos Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Photos</h4>
        </div>
        <div class="card-body">
            @foreach($report->photos as $photo)
                <div class="mb-3 photo">
                    <label>Photo:</label>
                    <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Photo" class="mb-2 img-fluid">
                    <p>{{ $photo->description }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <a href="{{ route('monthly.report.index') }}" class="btn btn-primary">Back to Reports</a>
    <a href="{{ route('monthly.report.downloadPdf', $report->report_id) }}" class="btn btn-secondary">Download PDF</a>
    <a href="{{ route('monthly.report.downloadDoc', $report->report_id) }}" class="btn btn-secondary">Download Word</a>
</div>

<style>
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

    .phase-card {
        margin-bottom: 1.5rem;
    }

    .card-header h4, .card-header h5 {
        margin-bottom: 0;
    }

    .table-custom {
        border: 1pt solid grey;
    }

    .table-custom th, .table-custom td {
        border: 1pt solid grey;
    }

    .attachment-grid {
        display: grid;
        grid-template-columns: 200px 1fr;
        grid-gap: 10px;
        margin-bottom: 15px;
    }

    .attachment-label {
        font-weight: bold;
    }
</style>
@endsection
