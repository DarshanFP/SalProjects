{{-- resources/views/projects/partials/Show/CCI/statistics.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Statistics of Passed out / Rehabilitated / Re-integrated Children till Date</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="text-align: left;">Description</th>
                        <th>Upto Previous Year</th>
                        <th>Current Year on Roll</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statPrevTotal = (is_numeric($statistics->reintegrated_children_previous_year ?? null) ? (int)$statistics->reintegrated_children_previous_year : 0)
                            + (is_numeric($statistics->shifted_children_previous_year ?? null) ? (int)$statistics->shifted_children_previous_year : 0)
                            + (is_numeric($statistics->pursuing_higher_studies_previous_year ?? null) ? (int)$statistics->pursuing_higher_studies_previous_year : 0)
                            + (is_numeric($statistics->settled_children_previous_year ?? null) ? (int)$statistics->settled_children_previous_year : 0)
                            + (is_numeric($statistics->working_children_previous_year ?? null) ? (int)$statistics->working_children_previous_year : 0)
                            + (is_numeric($statistics->other_category_previous_year ?? null) ? (int)$statistics->other_category_previous_year : 0);
                        $statCurrTotal = (is_numeric($statistics->reintegrated_children_current_year ?? null) ? (int)$statistics->reintegrated_children_current_year : 0)
                            + (is_numeric($statistics->shifted_children_current_year ?? null) ? (int)$statistics->shifted_children_current_year : 0)
                            + (is_numeric($statistics->pursuing_higher_studies_current_year ?? null) ? (int)$statistics->pursuing_higher_studies_current_year : 0)
                            + (is_numeric($statistics->settled_children_current_year ?? null) ? (int)$statistics->settled_children_current_year : 0)
                            + (is_numeric($statistics->working_children_current_year ?? null) ? (int)$statistics->working_children_current_year : 0)
                            + (is_numeric($statistics->other_category_current_year ?? null) ? (int)$statistics->other_category_current_year : 0);
                    @endphp
                    <tr class="table-active">
                        <td style="text-align: left;">Total number of children in the institution</td>
                        <td><strong>{{ $statPrevTotal }}</strong></td>
                        <td><strong>{{ $statCurrTotal }}</strong></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are reintegrated with their guardians/parents</td>
                        <td>{{ $statistics->reintegrated_children_previous_year ?? 'N/A' }}</td>
                        <td>{{ $statistics->reintegrated_children_current_year ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are shifted to other NGOs / Govt.</td>
                        <td>{{ $statistics->shifted_children_previous_year ?? 'N/A' }}</td>
                        <td>{{ $statistics->shifted_children_current_year ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are pursuing higher studies outside</td>
                        <td>{{ $statistics->pursuing_higher_studies_previous_year ?? 'N/A' }}</td>
                        <td>{{ $statistics->pursuing_higher_studies_current_year ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who completed the studies and settled down in life (i.e., married etc.)</td>
                        <td>{{ $statistics->settled_children_previous_year ?? 'N/A' }}</td>
                        <td>{{ $statistics->settled_children_current_year ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are now settled and working</td>
                        <td>{{ $statistics->working_children_previous_year ?? 'N/A' }}</td>
                        <td>{{ $statistics->working_children_current_year ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Any other category</td>
                        <td>{{ $statistics->other_category_previous_year ?? 'N/A' }}</td>
                        <td>{{ $statistics->other_category_current_year ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Styles for table -->
{{-- <style>
    .table th, .table td {
        text-align: left;
        padding: 0.5rem;
    }

    .table td {
        
    }

    .card-header h4 {
        color: #202ba3;
        font-weight: bold;
    }
</style> --}}
