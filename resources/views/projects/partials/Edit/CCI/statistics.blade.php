<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Statistics of Passed out / Rehabilitated / Re-integrated Children till Date</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="text-align: center;">Description</th>
                        <th>Upto Previous Year</th>
                        <th>Current Year on Roll</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left;">Total number of children in the institution</td>
                        <td><input type="text" name="total_children_previous_year" class="form-control select-input no-spinner" value="{{ $statistics->total_children_previous_year ?? '' }}"></td>
                        <td><input type="text" name="total_children_current_year" class="form-control select-input no-spinner" value="{{ $statistics->total_children_current_year ?? '' }}"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are reintegrated with their guardians/parents</td>
                        <td><input type="text" name="reintegrated_children_previous_year" class="form-control select-input no-spinner" value="{{ $statistics->reintegrated_children_previous_year ?? '' }}"></td>
                        <td><input type="text" name="reintegrated_children_current_year" class="form-control select-input no-spinner" value="{{ $statistics->reintegrated_children_current_year ?? '' }}"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are shifted to other NGOs / Govt.</td>
                        <td><input type="text" name="shifted_children_previous_year" class="form-control select-input no-spinner" value="{{ $statistics->shifted_children_previous_year ?? '' }}"></td>
                        <td><input type="text" name="shifted_children_current_year" class="form-control select-input no-spinner" value="{{ $statistics->shifted_children_current_year ?? '' }}"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are pursuing higher studies outside</td>
                        <td><input type="text" name="pursuing_higher_studies_previous_year" class="form-control select-input no-spinner" value="{{ $statistics->pursuing_higher_studies_previous_year ?? '' }}"></td>
                        <td><input type="text" name="pursuing_higher_studies_current_year" class="form-control select-input no-spinner" value="{{ $statistics->pursuing_higher_studies_current_year ?? '' }}"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who completed the studies and settled down in life (i.e., married etc.)</td>
                        <td><input type="text" name="settled_children_previous_year" class="form-control select-input no-spinner" value="{{ $statistics->settled_children_previous_year ?? '' }}"></td>
                        <td><input type="text" name="settled_children_current_year" class="form-control select-input no-spinner" value="{{ $statistics->settled_children_current_year ?? '' }}"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are now settled and working</td>
                        <td><input type="text" name="working_children_previous_year" class="form-control select-input no-spinner" value="{{ $statistics->working_children_previous_year ?? '' }}"></td>
                        <td><input type="text" name="working_children_current_year" class="form-control select-input no-spinner" value="{{ $statistics->working_children_current_year ?? '' }}"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Any other category</td>
                        <td><input type="text" name="other_category_previous_year" class="form-control select-input no-spinner" value="{{ $statistics->other_category_previous_year ?? '' }}"></td>
                        <td><input type="text" name="other_category_current_year" class="form-control select-input no-spinner" value="{{ $statistics->other_category_current_year ?? '' }}"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
