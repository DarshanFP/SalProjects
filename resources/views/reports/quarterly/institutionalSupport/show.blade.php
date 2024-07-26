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
                    <div class="mb-3">
                        <label class="form-label">Title of the Project</label>
                        <p>{{ $report->project_title }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Place</label>
                        <p>{{ $report->place }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Province</label>
                        <p>{{ $report->province }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sister/s In-Charge</label>
                        <p>{{ $report->in_charge }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Institution Type</label>
                        <p>{{ $report->institution_type }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Statistics of beneficiaries in the project</label>
                        <p>{{ $report->beneficiary_statistics }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monitoring Period</label>
                        <p>{{ $report->monitoring_period }}</p>
                    </div>
                </div>
            </div>

            <!-- Key Information Section -->
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>1. Key Information</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Goal of the Project</label>
                        <p>{{ $report->goal }}</p>
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
                    <div class="mb-3">
                        <div class="mb-3">
                            <label class="form-label">Objective</label>
                            <p>{{ $objective->objective }}</p>
                        </div>
                        <label class="form-label">Expected Outcome</label>
                        <p>{{ $objective->expected_outcome }}</p>
                    </div>
                    <h4>Monthly Summary</h4>
                    @foreach($objective->activities as $activity)
                    <div class="mb-3 card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="form-group">
                                <label class="form-label">Month</label>
                                <p>{{ $activity->month }}</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Summary of Activities</label>
                                <p>{{ $activity->summary_activities }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Qualitative & Quantitative Data</label>
                                <p>{{ $activity->qualitative_quantitative_data }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Intermediate Outcomes</label>
                                <p>{{ $activity->intermediate_outcomes }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    <div class="mb-3">
                        <label class="form-label">What Did Not Happen?</label>
                        <p>{{ $objective->not_happened }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Explain Why Some Activities Could Not Be Undertaken</label>
                        <p>{{ $objective->why_not_happened }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Have You Made Any Changes in the Project Such as New Activities or Modified the Activities Contextually?</label>
                        <p>{{ $objective->changes ? 'Yes' : 'No' }}</p>
                    </div>
                    @if($objective->changes)
                    <div class="mb-3">
                        <label class="form-label">Explain Why the Changes Were Needed</label>
                        <p>{{ $objective->why_changes }}</p>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">What Are the Lessons Learnt?</label>
                        <p>{{ $objective->lessons_learnt }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">What Will Be Done Differently Because of the Learnings?</label>
                        <p>{{ $objective->todo_lessons_learnt }}</p>
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
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <p>{{ $outlook->date }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Action Plan for Next Month</label>
                        <p>{{ $outlook->plan_next_month }}</p>
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
                    <div class="mb-3">
                        <label class="form-label">Account Statement Period</label>
                        <p>{{ $report->account_period_start }} to {{ $report->account_period_end }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount Sanctioned: Rs.</label>
                        <p>{{ $report->amount_sanctioned_overview }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
                        <p>{{ $report->amount_forwarded_overview }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Amount: Rs.</label>
                        <p>{{ $report->amount_in_hand }}</p>
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
                                <td>{{ $detail->amount_forwarded }}</td>
                                <td>{{ $detail->amount_sanctioned }}</td>
                                <td>{{ $detail->total_amount }}</td>
                                <td>{{ $detail->expenses_last_month }}</td>
                                <td>{{ $detail->expenses_this_month }}</td>
                                <td>{{ $detail->total_expenses }}</td>
                                <td>{{ $detail->balance_amount }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th>{{ $report->total_forwarded }}</th>
                                <th>{{ $report->total_sanctioned }}</th>
                                <th>{{ $report->total_amount_total }}</th>
                                <th>{{ $report->total_expenses_last_month }}</th>
                                <th>{{ $report->total_expenses_this_month }}</th>
                                <th>{{ $report->total_expenses_total }}</th>
                                <th>{{ $report->total_balance }}</th>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="mt-3">
                        <label class="form-label">Total Balance Amount Forwarded for the Following Month: Rs.</label>
                        <p>{{ $report->total_balance_forwarded }}</p>
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
