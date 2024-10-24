{{-- resources/views/projects/partials/CCI/statistics.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4> Statistics of Passed out / Rehabilitated / Re-integrated Children till Date</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead >
                    <tr>
                        <th style="text-align: center;">Description</th>
                        <th>Upto Previous Year</th>
                        <th>Current Year on Roll</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left;">Total number of children in the institution</td>
                        <td><input type="text" name="total_children_previous_year" class="form-control select-input no-spinner" value="{{ old('total_children_previous_year') }}"  style="background-color: #202ba3;"></td>
                        <td><input type="text" name="total_children_current_year" class="form-control select-input no-spinner" value="{{ old('total_children_current_year') }}"  style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are reintegrated with their guardians/parents</td>
                        <td><input type="text" name="reintegrated_children_previous_year" class="form-control select-input no-spinner" value="{{ old('reintegrated_children_previous_year') }}"  style="background-color: #202ba3;"></td>
                        <td><input type="text" name="reintegrated_children_current_year" class="form-control select-input no-spinner" value="{{ old('reintegrated_children_current_year') }}"  style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are shifted to other NGOs / Govt.</td>
                        <td><input type="text" name="shifted_children_previous_year" class="form-control select-input no-spinner" value="{{ old('shifted_children_previous_year') }}"  style="background-color: #202ba3;"></td>
                        <td><input type="text" name="shifted_children_current_year" class="form-control select-input no-spinner" value="{{ old('shifted_children_current_year') }}"  style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are pursuing higher studies outside</td>
                        <td><input type="text" name="pursuing_higher_studies_previous_year" class="form-control select-input no-spinner" value="{{ old('pursuing_higher_studies_previous_year') }}"  style="background-color: #202ba3;"></td>
                        <td><input type="text" name="pursuing_higher_studies_current_year" class="form-control select-input no-spinner" value="{{ old('pursuing_higher_studies_current_year') }}"  style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who completed the studies and settled down in life (i.e., married etc.)</td>
                        <td><input type="text" name="settled_children_previous_year" class="form-control select-input no-spinner" value="{{ old('settled_children_previous_year') }}"  style="background-color: #202ba3;"></td>
                        <td><input type="text" name="settled_children_current_year" class="form-control select-input no-spinner" value="{{ old('settled_children_current_year') }}"  style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are now settled and working</td>
                        <td><input type="text" name="working_children_previous_year" class="form-control select-input no-spinner" value="{{ old('working_children_previous_year') }}"  style="background-color: #202ba3;"></td>
                        <td><input type="text" name="working_children_current_year" class="form-control select-input no-spinner" value="{{ old('working_children_current_year') }}"  style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Any other category</td>
                        <td><input type="text" name="other_category_previous_year" class="form-control select-input no-spinner" value="{{ old('other_category_previous_year') }}"  style="background-color: #202ba3;"></td>
                        <td><input type="text" name="other_category_current_year" class="form-control select-input no-spinner" value="{{ old('other_category_current_year') }}"  style="background-color: #202ba3;"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Styles to remove spinner arrows and right align text -->
<style>
    /* Remove spinner arrows for number inputs */
    input[type='text'].no-spinner {
        -moz-appearance: textfield;
        -webkit-appearance: none;
        appearance: none;
    }
    input[type='text']::-webkit-outer-spin-button,
    input[type='text']::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Align text to the right in the description column */
    td {
        text-align: right;
    }
</style>
