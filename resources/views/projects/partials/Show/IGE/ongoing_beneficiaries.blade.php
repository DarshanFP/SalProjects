<div class="mb-3 card">
    <div class="card-header">
        <h4>Ongoing Beneficiaries</h4>
    </div>
    <div class="card-body">
        @if($ongoingBeneficiaries->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Beneficiary Name</th>
                        <th>Caste</th>
                        <th>Address</th>
                        <th>Present Group / Year of Study</th>
                        <th>Performance Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ongoingBeneficiaries as $index => $beneficiary)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $beneficiary->obeneficiary_name ?? 'N/A' }}</td>
                        <td>{{ $beneficiary->ocaste ?? 'N/A' }}</td>
                        <td>{{ $beneficiary->oaddress ?? 'N/A' }}</td>
                        <td>{{ $beneficiary->ocurrent_group_year_of_study ?? 'N/A' }}</td>
                        <td>{{ $beneficiary->operformance_details ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p>No ongoing beneficiaries found for this project.</p>
        @endif
    </div>
</div>
