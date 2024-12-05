<div class="mb-3 card">
    <div class="card-header">
        <h4>Budget for Current Year</h4>
    </div>
    <div class="card-body">
        @if($IGEbudget && $IGEbudget->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Name</th>
                        <th>Study Proposed to be</th>
                        <th>College Fees</th>
                        <th>Hostel Fees</th>
                        <th>Total Amount</th>
                        <th>Eligibility of Scholarship (Expected Amount)</th>
                        <th>Contribution from Family</th>
                        <th>Amount Requested</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalCollegeFees = 0;
                        $totalHostelFees = 0;
                        $totalAmount = 0;
                        $totalScholarshipEligibility = 0;
                        $totalFamilyContribution = 0;
                        $totalAmountRequested = 0;
                    @endphp

                    @foreach($IGEbudget as $index => $budget)
                    @php
                        $collegeFees = $budget->college_fees ?? 0;
                        $hostelFees = $budget->hostel_fees ?? 0;
                        $totalRowAmount = $budget->total_amount ?? 0;
                        $scholarshipEligibility = $budget->scholarship_eligibility ?? 0;
                        $familyContribution = $budget->family_contribution ?? 0;
                        $amountRequested = $budget->amount_requested ?? 0;

                        $totalCollegeFees += $collegeFees;
                        $totalHostelFees += $hostelFees;
                        $totalAmount += $totalRowAmount;
                        $totalScholarshipEligibility += $scholarshipEligibility;
                        $totalFamilyContribution += $familyContribution;
                        $totalAmountRequested += $amountRequested;
                    @endphp

                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $budget->name ?? 'N/A' }}</td>
                        <td>{{ $budget->study_proposed ?? 'N/A' }}</td>
                        <td>{{ $collegeFees }}</td>
                        <td>{{ $hostelFees }}</td>
                        <td>{{ $totalRowAmount }}</td>
                        <td>{{ $scholarshipEligibility }}</td>
                        <td>{{ $familyContribution }}</td>
                        <td>{{ $amountRequested }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Totals:</th>
                        <th>{{ $totalCollegeFees }}</th>
                        <th>{{ $totalHostelFees }}</th>
                        <th>{{ $totalAmount }}</th>
                        <th>{{ $totalScholarshipEligibility }}</th>
                        <th>{{ $totalFamilyContribution }}</th>
                        <th>{{ $totalAmountRequested }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        <p>No budget data available for this project.</p>
        @endif
    </div>
</div>
