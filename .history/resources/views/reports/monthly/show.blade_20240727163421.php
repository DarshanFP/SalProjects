@extends('executor.dashboard')

@section('content')
<div class="container">
    <h1 class="mb-4">Monthly Report Details</h1>

    <!-- General Information Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>General Information</h4>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-label"><strong>Project ID:</strong></div>
                <div class="info-value">{{ $report->project_id }}</div>
                <div class="info-label"><strong>Report ID:</strong></div>
                <div class="info-value">{{ $report->report_id }}</div>
                <div class="info-label"><strong>Project Title:</strong></div>
                <div class="info-value">{{ $report->project_title }}</div>
                <div class="info-label"><strong>Report Month & Year:</strong></div>
                <div class="info-value">{{ \Carbon\Carbon::parse($report->report_month_year)->format('F Y') }}</div>
                <div class="info-label"><strong>Project Type:</strong></div>
                <div class="info-value">{{ $report->project_type }}</div>
                <div class="info-label"><strong>Place:</strong></div>
                <div class="info-value">{{ $report->place }}</div>
                <div class="info-label"><strong>Society Name:</strong></div>
                <div class="info-value">{{ $report->society_name }}</div>
                <div class="info-label"><strong>Commencement Month & Year:</strong></div>
                <div class="info-value">{{ \Carbon\Carbon::parse($report->commencement_month_year)->format('F Y') }}</div>
                <div class="info-label"><strong>In Charge:</strong></div>
                <div class="info-value">{{ $report->in_charge }}</div>
                <div class="info-label"><strong>Total Beneficiaries:</strong></div>
                <div class="info-value">{{ $report->total_beneficiaries }}</div>
                <div class="info-label"><strong>Goal:</strong></div>
                <div class="info-value">{{ $report->goal }}</div>
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
                <div class="mb-3 objective-card">
                    <div class="card-header">
                        <h5>Objective {{ $loop->iteration }}</h5>
                    </div>
                    <div class="info-grid">
                        <div class="info-label"><strong>Objective:</strong></div>
                        <div class="info-value">{{ $objective->objective }}</div>
                        <div class="info-label"><strong>Expected Outcome:</strong></div>
                        <div class="info-value">{{ $objective->expected_outcome }}</div>
                        <div class="info-label"><strong>What Did Not Happen:</strong></div>
                        <div class="info-value">{{ $objective->not_happened }}</div>
                        <div class="info-label"><strong>Why Some Activities Could Not Be Undertaken:</strong></div>
                        <div class="info-value">{{ $objective->why_not_happened }}</div>
                        <div class="info-label"><strong>Changes:</strong></div>
                        <div class="info-value">{{ $objective->changes ? 'Yes' : 'No' }}</div>
                        @if($objective->changes)
                            <div class="info-label"><strong>Why Changes Were Needed:</strong></div>
                            <div class="info-value">{{ $objective->why_changes }}</div>
                        @endif
                        <div class="info-label"><strong>Lessons Learnt:</strong></div>
                        <div class="info-value">{{ $objective->lessons_learnt }}</div>
                        <div class="info-label"><strong>What Will Be Done Differently:</strong></div>
                        <div class="info-value">{{ $objective->todo_lessons_learnt }}</div>
                    </div>

                    <div class="mb-3">
                        <h6>Activities</h6>
                        <div class="info-grid">
                            @foreach($objective->activities as $activity)
                                <div class="info-label"><strong>Month:</strong></div>
                                <div class="info-value">{{ \Carbon\Carbon::createFromFormat('m', $activity->month)->format('F') }}</div>
                                <div class="info-label"><strong>Summary of Activities:</strong></div>
                                <div class="info-value">{{ $activity->summary_activities }}</div>
                                <div class="info-label"><strong>Qualitative & Quantitative Data:</strong></div>
                                <div class="info-value">{{ $activity->qualitative_quantitative_data }}</div>
                                <div class="info-label"><strong>Intermediate Outcomes:</strong></div>
                                <div class="info-value">{{ $activity->intermediate_outcomes }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Outlooks Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Outlooks</h4>
        </div>
        <div class="card-body">
            @foreach($report->outlooks as $outlook)
                <div class="mb-3 outlook-card">
                    <div class="info-grid">
                        <div class="info-label"><strong>Date:</strong></div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($outlook->date)->format('d-m-Y') }}</div>
                        <div class="info-label"><strong>Action Plan for Next Month:</strong></div>
                        <div class="info-value">{{ $outlook->plan_next_month }}</div>
                    </div>
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
            <div class="info-grid">
                <div class="info-label"><strong>Account Period:</strong></div>
                <div class="info-value">{{ \Carbon\Carbon::parse($report->account_period_start)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($report->account_period_end)->format('d-m-Y') }}</div>
                <div class="info-label"><strong>Amount Sanctioned:</strong></div>
                <div class="info-value">Rs. {{ number_format($report->amount_sanctioned_overview, 2) }}</div>
                <div class="info-label"><strong>Amount Forwarded:</strong></div>
                <div class="info-value">Rs. {{ number_format($report->amount_forwarded_overview, 2) }}</div>
                <div class="info-label"><strong>Total Amount:</strong></div>
                <div class="info-value">Rs. {{ number_format($report->amount_in_hand, 2) }}</div>
                <div class="info-label"><strong>Balance Forwarded:</strong></div>
                <div class="info-value">Rs. {{ number_format($report->total_balance_forwarded, 2) }}</div>
            </div>
            <div class="fp-text-center1">
                <h5>Budgets Details</h5>

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
                </table>
                <div>
        </div>
    </div>

    <!-- Photos Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Photos</h4>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($report->photos as $photo)
                    <div class="mb-3 col-md-4">
                        <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Photo" class="img-fluid">
                        <p>{{ $photo->description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <a href="{{ route('monthly.report.index') }}" class="btn btn-primary">Back to Reports</a>
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
