{{-- resources/views/projects/partials/Show/CCI/personal_situation.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Personal Situation of Children in the Institution</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="text-align: left;">Description</th>
                        <th>Up to Last Year</th>
                        <th>Current Year</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left;">Children with parents</td>
                        <td>{{ $personalSituation->children_with_parents_last_year ?? 'N/A' }}</td>
                        <td>{{ $personalSituation->children_with_parents_current_year ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Semi-orphans (living with relatives)</td>
                        <td>{{ $personalSituation->semi_orphans_last_year ?? 'N/A' }}</td>
                        <td>{{ $personalSituation->semi_orphans_current_year ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Orphans</td>
                        <td>{{ $personalSituation->orphans_last_year ?? 'N/A' }}</td>
                        <td>{{ $personalSituation->orphans_current_year ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">HIV-infected/affected</td>
                        <td>{{ $personalSituation->hiv_infected_last_year ?? 'N/A' }}</td>
                        <td>{{ $personalSituation->hiv_infected_current_year ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Differently-abled children</td>
                        <td>{{ $personalSituation->differently_abled_last_year ?? 'N/A' }}</td>
                        <td>{{ $personalSituation->differently_abled_current_year ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Parents in conflict</td>
                        <td>{{ $personalSituation->parents_in_conflict_last_year ?? 'N/A' }}</td>
                        <td>{{ $personalSituation->parents_in_conflict_current_year ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Other ailments</td>
                        <td>{{ $personalSituation->other_ailments_last_year ?? 'N/A' }}</td>
                        <td>{{ $personalSituation->other_ailments_current_year ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <h5>General Remarks</h5>
            <p>{{ $personalSituation->general_remarks ?? 'No remarks provided.' }}</p>
        </div>
    </div>
</div>
