{{-- resources/views/projects/partials/Show/IGE/new_beneficiaries.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>New Beneficiaries</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Beneficiary Name</th>
                        <th>Caste</th>
                        <th>Address</th>
                        <th>Group / Year of Study</th>
                        <th>Family Background and Need of Support</th>
                    </tr>
                </thead>
                <tbody>
                    @if($newBeneficiaries->isNotEmpty())
                        @foreach($newBeneficiaries as $index => $beneficiary)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $beneficiary->beneficiary_name ?? 'N/A' }}</td>
                            <td>{{ $beneficiary->caste ?? 'N/A' }}</td>
                            <td>{{ $beneficiary->address ?? 'N/A' }}</td>
                            <td>{{ $beneficiary->group_year_of_study ?? 'N/A' }}</td>
                            <td>{{ $beneficiary->family_background_need ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="text-center">No new beneficiaries recorded.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
