@extends('executor.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Monthly Report Details</div>

                <div class="card-body">
                    <div class="mb-3">
                        <h5>Project Information</h5>
                        <p><strong>Project ID:</strong> {{ $report->project_id }}</p>
                        <p><strong>Report ID:</strong> {{ $report->report_id }}</p>
                        <p><strong>Project Title:</strong> {{ $report->project_title }}</p>
                        <p><strong>Report Month Year:</strong> {{ \Carbon\Carbon::parse($report->report_month_year)->format('F Y') }}</p>
                    </div>

                    <div class="mb-3">
                        <h5>Basic Information</h5>
                        <p><strong>Project Type:</strong> {{ $report->project_type }}</p>
                        <p><strong>Place:</strong> {{ $report->place }}</p>
                        <p><strong>Society Name:</strong> {{ $report->society_name }}</p>
                        <p><strong>Commencement Month Year:</strong> {{ \Carbon\Carbon::parse($report->commencement_month_year)->format('F Y') }}</p>
                        <p><strong>In Charge:</strong> {{ $report->in_charge }}</p>
                        <p><strong>Total Beneficiaries:</strong> {{ $report->total_beneficiaries }}</p>
                        <p><strong>Goal:</strong> {{ $report->goal }}</p>
                    </div>

                    <div class="mb-3">
                        <h5>Objectives</h5>
                        @foreach($report->objectives as $objective)
                            <div class="mb-3">
                                <h6>Objective {{ $loop->iteration }}</h6>
                                <p><strong>Objective:</strong> {{ $objective->objective }}</p>
                                <p><strong>Expected Outcome:</strong> {{ $objective->expected_outcome }}</p>
                                <p><strong>What Did Not Happen:</strong> {{ $objective->not_happened }}</p>
                                <p><strong>Why Some Activities Could Not Be Undertaken:</strong> {{ $objective->why_not_happened }}</p>
                                <p><strong>Changes:</strong> {{ $objective->changes ? 'Yes' : 'No' }}</p>
                                @if($objective->changes)
                                    <p><strong>Why Changes Were Needed:</strong> {{ $objective->why_changes }}</p>
                                @endif
                                <p><strong>Lessons Learnt:</strong> {{ $objective->lessons_learnt }}</p>
                                <p><strong>What Will Be Done Differently:</strong> {{ $objective->todo_lessons_learnt }}</p>

                                <div class="mb-3">
                                    <h6>Activities</h6>
                                    @foreach($objective->activities as $activity)
                                        <div class="mb-3">
                                            <p><strong>Month:</strong> {{ $activity->month }}</p>
                                            <p><strong>Summary of Activities:</strong> {{ $activity->summary_activities }}</p>
                                            <p><strong>Qualitative & Quantitative Data:</strong> {{ $activity->qualitative_quantitative_data }}</p>
                                            <p><strong>Intermediate Outcomes:</strong> {{ $activity->intermediate_outcomes }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mb-3">
                        <h5>Outlooks</h5>
                        @foreach($report->outlooks as $outlook)
                            <div class="mb-3">
                                <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($outlook->date)->format('d-m-Y') }}</p>
                                <p><strong>Action Plan for Next Month:</strong> {{ $outlook->plan_next_month }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mb-3">
                        <h5>Account Details</h5>
                        <p><strong>Account Period:</strong> {{ \Carbon\Carbon::parse($report->account_period_start)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($report->account_period_end)->format('d-m-Y') }}</p>
                        <p><strong>Amount Sanctioned:</strong> Rs. {{ $report->amount_sanctioned_overview }}</p>
                        <p><strong>Amount Forwarded:</strong> Rs. {{ $report->amount_forwarded_overview }}</p>
                        <p><strong>Total Amount:</strong> Rs. {{ $report->amount_in_hand }}</p>
                        <p><strong>Balance Forwarded:</strong> Rs. {{ $report->total_balance_forwarded }}</p>

                        <table class="table table-bordered">
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
                                        <td>Rs. {{ $accountDetail->amount_forwarded }}</td>
                                        <td>Rs. {{ $accountDetail->amount_sanctioned }}</td>
                                        <td>Rs. {{ $accountDetail->total_amount }}</td>
                                        <td>Rs. {{ $accountDetail->expenses_last_month }}</td>
                                        <td>Rs. {{ $accountDetail->expenses_this_month }}</td>
                                        <td>Rs. {{ $accountDetail->total_expenses }}</td>
                                        <td>Rs. {{ $accountDetail->balance_amount }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-3">
                        <h5>Photos</h5>
                        <div class="row">
                            @foreach($report->photos as $photo)
                                <div class="mb-3 col-md-4">
                                    <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Photo" class="img-fluid">
                                    <p>{{ $photo->description }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <a href="{{ route('monthly.report.index') }}" class="btn btn-primary">Back to Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
