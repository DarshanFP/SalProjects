{{-- resources/views/projects/partials/Show/IGE/institution_info.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Institution Information</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="text-align: left;">Field</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left;">Institutional Type</td>
                        <td>{{ $IGEInstitutionInfo?->institutional_type ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Age Group</td>
                        <td>{{ $IGEInstitutionInfo?->age_group ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Number of Beneficiaries (Previous Years)</td>
                        <td>{{ $IGEInstitutionInfo?->previous_year_beneficiaries ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Outcome/Impact</td>
                        <td>{{ $IGEInstitutionInfo?->outcome_impact ?? 'No information provided.' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
