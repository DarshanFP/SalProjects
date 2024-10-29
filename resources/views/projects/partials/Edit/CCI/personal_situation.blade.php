<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Personal Situation of Children in the Institution</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="text-align: left;">Description</th>
                        <th>Up to last year</th>
                        <th>Current year</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left;">Children with parents</td>
                        <td><input type="number" name="children_with_parents_last_year" value="{{ $personalSituation->children_with_parents_last_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="children_with_parents_current_year" value="{{ $personalSituation->children_with_parents_current_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Semi-orphans (living with relatives)</td>
                        <td><input type="number" name="semi_orphans_last_year" value="{{ $personalSituation->semi_orphans_last_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="semi_orphans_current_year" value="{{ $personalSituation->semi_orphans_current_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Orphans</td>
                        <td><input type="number" name="orphans_last_year" value="{{ $personalSituation->orphans_last_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="orphans_current_year" value="{{ $personalSituation->orphans_current_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">HIV-infected/affected</td>
                        <td><input type="number" name="hiv_infected_last_year" value="{{ $personalSituation->hiv_infected_last_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="hiv_infected_current_year" value="{{ $personalSituation->hiv_infected_current_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Differently-abled children</td>
                        <td><input type="number" name="differently_abled_last_year" value="{{ $personalSituation->differently_abled_last_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="differently_abled_current_year" value="{{ $personalSituation->differently_abled_current_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Parents in conflict</td>
                        <td><input type="number" name="parents_in_conflict_last_year" value="{{ $personalSituation->parents_in_conflict_last_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="parents_in_conflict_current_year" value="{{ $personalSituation->parents_in_conflict_current_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Other ailments</td>
                        <td><input type="number" name="other_ailments_last_year" value="{{ $personalSituation->other_ailments_last_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="other_ailments_current_year" value="{{ $personalSituation->other_ailments_current_year ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mb-3">
            <label for="general_remarks" class="form-label">General Remarks</label>
            <textarea name="general_remarks" class="form-control" rows="3" style="background-color: #202ba3;">{{ $personalSituation->general_remarks ?? '' }}</textarea>
        </div>
    </div>
</div>
