{{-- resources/views/projects/partials/Show/IES/immediate_family_details.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Details about Immediate Family Members</h4>
    </div>
    <div class="card-body">
        @php
            $familyDetails = $project->iesImmediateFamilyDetails ?? null;
        @endphp

        @if($familyDetails)
            <table class="table table-bordered">
                <tr>
                    <th>Mother Expired</th>
                    <td>{{ $familyDetails->mother_expired ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Father Expired</th>
                    <td>{{ $familyDetails->father_expired ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Grandmother Supports Family</th>
                    <td>{{ $familyDetails->grandmother_support ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Grandfather Supports Family</th>
                    <td>{{ $familyDetails->grandfather_support ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Father Deserted Family</th>
                    <td>{{ $familyDetails->father_deserted ? 'Yes' : 'No' }}</td>
                </tr>
                @if(!empty($familyDetails->family_details_others))
                <tr>
                    <th>Other Family Details</th>
                    <td>{{ $familyDetails->family_details_others }}</td>
                </tr>
                @endif
                <tr>
                    <th>Father Health Issues</th>
                    <td>
                        @if($familyDetails->father_sick) Chronically Sick <br> @endif
                        @if($familyDetails->father_hiv_aids) HIV/AIDS Positive <br> @endif
                        @if($familyDetails->father_disabled) Disabled <br> @endif
                        @if($familyDetails->father_alcoholic) Alcoholic <br> @endif
                        @if(!empty($familyDetails->father_health_others)) Other: {{ $familyDetails->father_health_others }} @endif
                        @if(!$familyDetails->father_sick && !$familyDetails->father_hiv_aids && !$familyDetails->father_disabled && !$familyDetails->father_alcoholic && empty($familyDetails->father_health_others)) N/A @endif
                    </td>
                </tr>
                <tr>
                    <th>Mother Health Issues</th>
                    <td>
                        @if($familyDetails->mother_sick) Chronically Sick <br> @endif
                        @if($familyDetails->mother_hiv_aids) HIV/AIDS Positive <br> @endif
                        @if($familyDetails->mother_disabled) Disabled <br> @endif
                        @if($familyDetails->mother_alcoholic) Alcoholic <br> @endif
                        @if(!empty($familyDetails->mother_health_others)) Other: {{ $familyDetails->mother_health_others }} @endif
                        @if(!$familyDetails->mother_sick && !$familyDetails->mother_hiv_aids && !$familyDetails->mother_disabled && !$familyDetails->mother_alcoholic && empty($familyDetails->mother_health_others)) N/A @endif
                    </td>
                </tr>
                <tr>
                    <th>Residential Status</th>
                    <td>
                        @if($familyDetails->own_house) Own House <br> @endif
                        @if($familyDetails->rented_house) Rented House <br> @endif
                        @if(!empty($familyDetails->residential_others)) Other: {{ $familyDetails->residential_others }} @endif
                        @if(!$familyDetails->own_house && !$familyDetails->rented_house && empty($familyDetails->residential_others)) N/A @endif
                    </td>
                </tr>
                <tr>
                    <th>Family Situation</th>
                    <td>{{ $familyDetails->family_situation ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Assistance Need</th>
                    <td>{{ $familyDetails->assistance_need ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Received Support Previously?</th>
                    <td>{{ $familyDetails->received_support ? 'Yes' : 'No' }}</td>
                </tr>
                @if($familyDetails->received_support)
                <tr>
                    <th>Support Details</th>
                    <td>{{ $familyDetails->support_details ?? 'N/A' }}</td>
                </tr>
                @endif
                <tr>
                    <th>Employed with St. Ann's?</th>
                    <td>{{ $familyDetails->employed_with_stanns ? 'Yes' : 'No' }}</td>
                </tr>
                @if($familyDetails->employed_with_stanns)
                <tr>
                    <th>Employment Details</th>
                    <td>{{ $familyDetails->employment_details ?? 'N/A' }}</td>
                </tr>
                @endif
            </table>
        @else
            <p class="text-muted">No family details recorded.</p>
        @endif
    </div>
</div>
