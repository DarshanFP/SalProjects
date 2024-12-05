<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Target Group</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive"> <!-- Added to enable scrolling -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Beneficiary Name</th>
                        <th>Caste</th>
                        <th>Name of Institution</th>
                        <th>Class / Standard</th>
                        <th>Total Tuition Fee</th>
                        <th>Eligibility of Scholarship</th>
                        <th>Expected Amount</th>
                        <th>Contribution from Family</th>
                    </tr>
                </thead>
                <tbody>
                    @if($RUTtargetGroups && $RUTtargetGroups->count() > 0)
                        @foreach($RUTtargetGroups as $index => $group)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $group->beneficiary_name ?? 'N/A' }}</td>
                                <td>{{ $group->caste ?? 'N/A' }}</td>
                                <td>{{ $group->institution_name ?? 'N/A' }}</td>
                                <td>{{ $group->class_standard ?? 'N/A' }}</td>
                                <td>{{ $group->total_tuition_fee ?? 'N/A' }}</td>
                                <td>{{ $group->eligibility_scholarship ? 'Yes' : 'No' }}</td>
                                <td>{{ $group->expected_amount ?? 'N/A' }}</td>
                                <td>{{ $group->contribution_from_family ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" class="text-center">No target group data available.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div> <!-- Closing table-responsive -->
    </div>
</div>
