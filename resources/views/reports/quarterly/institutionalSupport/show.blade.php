@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="mb-3 card">
                <div class="card-header">
                    <h4 class="fp-text-center1">INSTITUTIONAL / NON-INSTITUTIONAL SUPPORT AND WELFARE OF CHILDREN / ADOLESCENTS</h4>
                    <h4 class="fp-text-center1">QUARTERLY PROGRESS REPORT</h4>
                </div>
                <div class="card-header">
                    <h4 class="fp-text-margin">Basic Information</h4>
                </div>
                <div class="card-body">
                    <!-- Basic Information Fields -->
                    <div class="info-grid">
                        <div class="info-label"><strong>Title of the Project:</strong></div>
                        <div class="info-value">{{ $report->project_title }}</div>
                        <div class="info-label"><strong>Place:</strong></div>
                        <div class="info-value">{{ $report->place }}</div>
                        <div class="info-label"><strong>Province:</strong></div>
                        <div class="info-value">{{ $report->province }}</div>
                        <div class="info-label"><strong>Sister/s In-Charge:</strong></div>
                        <div class="info-value">{{ $report->in_charge }}</div>
                        <div class="info-label"><strong>Institution Type:</strong></div>
                        <div class="info-value">{{ $report->institution_type }}</div>
                        <div class="info-label"><strong>Statistics of beneficiaries in the project:</strong></div>
                        <div class="info-value">{{ $report->beneficiary_statistics }}</div>
                        <div class="info-label"><strong>Monitoring Period:</strong></div>
                        <div class="info-value">{{ $report->monitoring_period }}</div>
                    </div>
                </div>
            </div>

            <!-- Key Information Section -->
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>1. Key Information</h4>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-label"><strong>Goal of the Project:</strong></div>
                        <div class="info-value">{{ $report->goal }}</div>
                    </div>
                </div>
            </div>

            <!-- Age Profile Section -->
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>2. Age Profile of Children in the Institution</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Age Group</th>
                                <th>Education</th>
                                <th>Up to Previous Year</th>
                                <th>Present Academic Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report->ageProfiles as $ageProfile)
                            <tr>
                                <td>{{ $ageProfile->age_group }}</td>
                                <td>{{ $ageProfile->education }}</td>
                                <td>{{ $ageProfile->up_to_previous_year }}</td>
                                <td>{{ $ageProfile->present_academic_year }}</td>
                            </tr>
                            @endforeach
                            <!-- Totals -->
                            <tr class="total-row">
                                <td style="text-align: right;" colspan="2"><strong>Total below 5 years</strong></td>
                                <td>{{ $report->total_up_to_previous_below_5 }}</td>
                                <td>{{ $report->total_present_academic_below_5 }}</td>
                            </tr>
                            <tr class="total-row">
                                <td style="text-align: right;" colspan="2"><strong>Total between 6 to 10 years</strong></td>
                                <td>{{ $report->total_up_to_previous_6_10 }}</td>
                                <td>{{ $report->total_present_academic_6_10 }}</td>
                            </tr>
                            <tr class="total-row">
                                <td style="text-align: right;" colspan="2"><strong>Total between 11 to 15 years</strong></td>
                                <td>{{ $report->total_up_to_previous_11_15 }}</td>
                                <td>{{ $report->total_present_academic_11_15 }}</td>
                            </tr>
                            <tr class="total-row">
                                <td style="text-align: right;" colspan="2"><strong>Total 16 and above</strong></td>
                                <td>{{ $report->total_up_to_previous_16_above }}</td>
                                <td>{{ $report->total_present_academic_16_above }}</td>
                            </tr>
                            <tr class="total-row">
                                <td style="text-align: right;" colspan="2"><strong>Grand Total</strong></td>
                                <td>{{ $report->grand_total_up_to_previous }}</td>
                                <td>{{ $report->grand_total_present_academic }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Objectives Section -->
            @foreach($report->objectives as $objective)
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>3. Activities and Intermediate Outcomes</h4>
                </div>
                <div class="card-header d-flex justify-content-between align-items-center">
                    Objective {{ $loop->iteration }}
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-label"><strong>Objective:</strong></div>
                        <div class="info-value">{{ $objective->objective }}</div>
                        <div class="info-label"><strong>Expected Outcome:</strong></div>
                        <div class="info-value">{{ $objective->expected_outcome }}</div>
                    </div>
                    <h4 class="mt-4">Monthly Summary</h4>
                    @foreach($objective->activities as $activity)
                    <div class="mb-3 card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="info-grid" style="width: 100%;">
                                <div class="info-label"><strong>Month:</strong></div>
                                <div class="info-value">{{ $activity->month }}</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-label"><strong>Summary of Activities:</strong></div>
                                <div class="info-value">{{ $activity->summary_activities }}</div>
                                <div class="info-label"><strong>Qualitative & Quantitative Data:</strong></div>
                                <div class="info-value">{{ $activity->qualitative_quantitative_data }}</div>
                                <div class="info-label"><strong>Intermediate Outcomes:</strong></div>
                                <div class="info-value">{{ $activity->intermediate_outcomes }}</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    <div class="info-grid mt-3">
                        <div class="info-label"><strong>What Did Not Happen?:</strong></div>
                        <div class="info-value">{{ $objective->not_happened }}</div>
                        <div class="info-label"><strong>Explain Why Some Activities Could Not Be Undertaken:</strong></div>
                        <div class="info-value">{{ $objective->why_not_happened }}</div>
                        <div class="info-label"><strong>Have You Made Any Changes in the Project Such as New Activities or Modified the Activities Contextually?:</strong></div>
                        <div class="info-value">{{ $objective->changes ? 'Yes' : 'No' }}</div>
                        @if($objective->changes)
                        <div class="info-label"><strong>Explain Why the Changes Were Needed:</strong></div>
                        <div class="info-value">{{ $objective->why_changes }}</div>
                        @endif
                        <div class="info-label"><strong>What Are the Lessons Learnt?:</strong></div>
                        <div class="info-value">{{ $objective->lessons_learnt }}</div>
                        <div class="info-label"><strong>What Will Be Done Differently Because of the Learnings?:</strong></div>
                        <div class="info-value">{{ $objective->todo_lessons_learnt }}</div>
                    </div>
                </div>
            </div>
            @endforeach

            <!-- Outlook Section -->
            @foreach($report->outlooks as $outlook)
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>4. Outlook</h4>
                </div>
                <div class="card-header d-flex justify-content-between align-items-center">
                    Outlook {{ $loop->iteration }}
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-label"><strong>Date:</strong></div>
                        <div class="info-value">{{ $outlook->date }}</div>
                        <div class="info-label"><strong>Action Plan for Next Month:</strong></div>
                        <div class="info-value">{{ $outlook->plan_next_month }}</div>
                    </div>
                </div>
            </div>
            @endforeach

            <!-- Statements of Account Section -->
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>5. Statements of Account</h4>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-label"><strong>Account Statement Period:</strong></div>
                        <div class="info-value">{{ $report->account_period_start }} to {{ $report->account_period_end }}</div>
                        <div class="info-label"><strong>Amount Sanctioned:</strong></div>
                        <div class="info-value">{{ format_indian_currency($report->amount_sanctioned_overview ?? 0, 2) }}</div>
                        <div class="info-label"><strong>Amount Forwarded from the Last Financial Year:</strong></div>
                        <div class="info-value">{{ format_indian_currency($report->amount_forwarded_overview ?? 0, 2) }}</div>
                        <div class="info-label"><strong>Total Amount:</strong></div>
                        <div class="info-value">{{ format_indian_currency($report->amount_in_hand ?? 0, 2) }}</div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Particulars</th>
                                <th>Amount Forwarded from the Previous Year</th>
                                <th>Amount Sanctioned Current Year</th>
                                <th>Total Amount (2+3)</th>
                                <th>Expenses Up to Last Month</th>
                                <th>Expenses of This Month</th>
                                <th>Total Expenses (5+6)</th>
                                <th>Balance Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report->accountDetails as $detail)
                            <tr>
                                <td>{{ $detail->particulars }}</td>
                                <td>{{ format_indian_currency($detail->amount_forwarded ?? 0, 2) }}</td>
                                <td>{{ format_indian_currency($detail->amount_sanctioned ?? 0, 2) }}</td>
                                <td>{{ format_indian_currency($detail->total_amount ?? 0, 2) }}</td>
                                <td>{{ format_indian_currency($detail->expenses_last_month ?? 0, 2) }}</td>
                                <td>{{ format_indian_currency($detail->expenses_this_month ?? 0, 2) }}</td>
                                <td>{{ format_indian_currency($detail->total_expenses ?? 0, 2) }}</td>
                                <td>{{ format_indian_currency($detail->balance_amount ?? 0, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th>{{ format_indian_currency($report->total_forwarded ?? 0, 2) }}</th>
                                <th>{{ format_indian_currency($report->total_sanctioned ?? 0, 2) }}</th>
                                <th>{{ format_indian_currency($report->total_amount_total ?? 0, 2) }}</th>
                                <th>{{ format_indian_currency($report->total_expenses_last_month ?? 0, 2) }}</th>
                                <th>{{ format_indian_currency($report->total_expenses_this_month ?? 0, 2) }}</th>
                                <th>{{ format_indian_currency($report->total_expenses_total ?? 0, 2) }}</th>
                                <th>{{ format_indian_currency($report->total_balance ?? 0, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="info-grid mt-3">
                        <div class="info-label"><strong>Total Balance Amount Forwarded for the Following Month:</strong></div>
                        <div class="info-value">{{ format_indian_currency($report->total_balance_forwarded ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>

            <!-- Photos Section -->
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>6. Photos</h4>
                </div>
                <div class="card-body">
                    @foreach($report->photos as $photo)
                    <div class="mb-3">
                        <img src="{{ asset('storage/' . $photo->photo_path) }}" class="img-fluid" alt="Photo">
                        <p>{{ $photo->description }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
.info-grid {
    display: grid;
    grid-template-columns: 20% 80%;
    grid-gap: 20px;
    align-items: start;
}

.info-label {
    font-weight: bold;
    margin-right: 10px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    white-space: normal;
}

.info-value {
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    white-space: normal;
    padding-left: 10px;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
        grid-gap: 10px;
    }

    .info-label {
        margin-right: 0;
        margin-bottom: 5px;
    }

    .info-value {
        padding-left: 0;
    }
}
</style>

<script src="{{ asset('js/report-view-hide-empty.js') }}"></script>
