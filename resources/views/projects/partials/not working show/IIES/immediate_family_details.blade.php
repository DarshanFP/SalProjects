{{-- resources/views/projects/partials/Show/IIES/immediate_family_details.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Immediate Family Details</h4>
    </div>
    <div class="card-body">
        @php
            $familyDetails = $project->iiesImmediateFamilyDetails ?? new \App\Models\OldProjects\IIES\ProjectIIESImmediateFamilyDetails();
        @endphp

        <table class="table table-bordered">
            <tr>
                <th>Father Expired</th>
                <td>{{ $familyDetails->iies_father_expired ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <th>Mother Expired</th>
                <td>{{ $familyDetails->iies_mother_expired ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <th>Grandmother Supports Family</th>
                <td>{{ $familyDetails->iies_grandmother_support ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <th>Grandfather Supports Family</th>
                <td>{{ $familyDetails->iies_grandfather_support ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <th>Father Health Issues</th>
                <td>
                    @if($familyDetails->iies_father_sick) Chronically Sick <br> @endif
                    @if($familyDetails->iies_father_hiv_aids) HIV/AIDS Positive <br> @endif
                    @if($familyDetails->iies_father_disabled) Disabled <br> @endif
                    @if($familyDetails->iies_father_alcoholic) Alcoholic <br> @endif
                    @if(!empty($familyDetails->iies_father_health_others)) Other: {{ $familyDetails->iies_father_health_others }} @endif
                </td>
            </tr>
            <tr>
                <th>Mother Health Issues</th>
                <td>
                    @if($familyDetails->iies_mother_sick) Chronically Sick <br> @endif
                    @if($familyDetails->iies_mother_hiv_aids) HIV/AIDS Positive <br> @endif
                    @if($familyDetails->iies_mother_disabled) Disabled <br> @endif
                    @if($familyDetails->iies_mother_alcoholic) Alcoholic <br> @endif
                    @if(!empty($familyDetails->iies_mother_health_others)) Other: {{ $familyDetails->iies_mother_health_others }} @endif
                </td>
            </tr>
            <tr>
                <th>Residential Status</th>
                <td>
                    @if($familyDetails->iies_own_house) Own House <br> @endif
                    @if($familyDetails->iies_rented_house) Rented House <br> @endif
                    @if(!empty($familyDetails->iies_residential_others)) Other: {{ $familyDetails->iies_residential_others }} @endif
                </td>
            </tr>
            <tr>
                <th>Family Situation</th>
                <td>{{ $familyDetails->iies_family_situation ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Assistance Need</th>
                <td>{{ $familyDetails->iies_assistance_need ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Received Support Previously?</th>
                <td>{{ $familyDetails->iies_received_support ? 'Yes' : 'No' }}</td>
            </tr>
            @if($familyDetails->iies_received_support)
            <tr>
                <th>Support Details</th>
                <td>{{ $familyDetails->iies_support_details ?? 'N/A' }}</td>
            </tr>
            @endif
        </table>
    </div>
</div>

