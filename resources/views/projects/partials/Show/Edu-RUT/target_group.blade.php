{{-- resources/views/projects/partials/Show/Edu-RUT/target_group.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Target Group</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <style>
                table.target-group-table {
                    table-layout: fixed;
                    width: 100%;
                }

                table.target-group-table th,
                table.target-group-table td {
                    word-break: break-word;
                    white-space: normal !important;
                    overflow-wrap: break-word;
                    text-align: center;
                    vertical-align: middle;
                    padding: 8px;
                }

                /* Left-align specific columns for better readability */
                table.target-group-table th.beneficiary-name,
                table.target-group-table th.institution-name,
                table.target-group-table td.beneficiary-name,
                table.target-group-table td.institution-name {
                    text-align: left;
                }

                /* Adjust column widths for better layout */
                table.target-group-table th.s-no {
                    width: 5%;
                }

                table.target-group-table th.beneficiary-name {
                    width: 15%;
                }

                table.target-group-table th.caste,
                table.target-group-table th.class-standard {
                    width: 10%;
                }

                table.target-group-table th.institution-name {
                    width: 15%; /* Reduced from 20% to make space */
                }

                table.target-group-table th.tuition-fee {
                    width: 10%;
                }

                table.target-group-table th.scholarship-eligibility {
                    width: 12%; /* Slightly wider to fit header */
                    white-space: nowrap; /* Prevent wrapping for header */
                }

                table.target-group-table th.expected-amount {
                    width: 10%;
                }

                table.target-group-table th.family-contribution {
                    width: 13%; /* Reduced slightly to balance */
                    white-space: nowrap; /* Prevent wrapping for header */
                }
            </style>

            <table class="table table-bordered target-group-table">
                <thead>
                    <tr>
                        <th class="s-no">S.No.</th>
                        <th class="beneficiary-name">Beneficiary Name</th>
                        <th class="caste">Caste</th>
                        <th class="institution-name">Name of Institution</th>
                        <th class="class-standard">Class / Standard</th>
                        <th class="tuition-fee">Total Tuition Fee</th>
                        <th class="scholarship-eligibility">Eligibility of Scholarship</th>
                        <th class="expected-amount">Expected Amount</th>
                        <th class="family-contribution">Contribution from Family</th>
                    </tr>
                </thead>
                <tbody>
                    @if($RUTtargetGroups && $RUTtargetGroups->count() > 0)
                        @foreach($RUTtargetGroups as $index => $group)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="beneficiary-name">{{ $group->beneficiary_name ?? 'N/A' }}</td>
                                <td class="caste">{{ $group->caste ?? 'N/A' }}</td>
                                <td class="institution-name">{{ $group->institution_name ?? 'N/A' }}</td>
                                <td class="class-standard">{{ $group->class_standard ?? 'N/A' }}</td>
                                <td class="tuition-fee">{{ $group->total_tuition_fee ?? 'N/A' }}</td>
                                <td class="scholarship-eligibility">{{ $group->eligibility_scholarship ? 'Yes' : 'No' }}</td>
                                <td class="expected-amount">{{ $group->expected_amount ?? 'N/A' }}</td>
                                <td class="family-contribution">{{ $group->contribution_from_family ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" class="text-center">No target group data available.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
