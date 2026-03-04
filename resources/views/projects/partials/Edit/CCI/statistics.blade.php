<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Statistics of Passed out / Rehabilitated / Re-integrated Children till Date</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="cci-statistics-table">
                <thead>
                    <tr>
                        <th style="text-align: center;">Description</th>
                        <th>Upto Previous Year</th>
                        <th>Current Year on Roll</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-active">
                        <td style="text-align: left;">Total number of children in the institution</td>
                        <td><input type="text" name="total_children_previous_year" id="cci-statistics-total-prev" class="form-control select-input no-spinner bg-light" value="{{ $statistics->total_children_previous_year ?? '' }}" readonly tabindex="-1"></td>
                        <td><input type="text" name="total_children_current_year" id="cci-statistics-total-current" class="form-control select-input no-spinner bg-light" value="{{ $statistics->total_children_current_year ?? '' }}" readonly tabindex="-1"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are reintegrated with their guardians/parents</td>
                        <td><input type="text" name="reintegrated_children_previous_year" class="form-control select-input no-spinner cci-stat-prev" value="{{ $statistics->reintegrated_children_previous_year ?? '' }}"></td>
                        <td><input type="text" name="reintegrated_children_current_year" class="form-control select-input no-spinner cci-stat-current" value="{{ $statistics->reintegrated_children_current_year ?? '' }}"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are shifted to other NGOs / Govt.</td>
                        <td><input type="text" name="shifted_children_previous_year" class="form-control select-input no-spinner cci-stat-prev" value="{{ $statistics->shifted_children_previous_year ?? '' }}"></td>
                        <td><input type="text" name="shifted_children_current_year" class="form-control select-input no-spinner cci-stat-current" value="{{ $statistics->shifted_children_current_year ?? '' }}"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are pursuing higher studies outside</td>
                        <td><input type="text" name="pursuing_higher_studies_previous_year" class="form-control select-input no-spinner cci-stat-prev" value="{{ $statistics->pursuing_higher_studies_previous_year ?? '' }}"></td>
                        <td><input type="text" name="pursuing_higher_studies_current_year" class="form-control select-input no-spinner cci-stat-current" value="{{ $statistics->pursuing_higher_studies_current_year ?? '' }}"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who completed the studies and settled down in life (i.e., married etc.)</td>
                        <td><input type="text" name="settled_children_previous_year" class="form-control select-input no-spinner cci-stat-prev" value="{{ $statistics->settled_children_previous_year ?? '' }}"></td>
                        <td><input type="text" name="settled_children_current_year" class="form-control select-input no-spinner cci-stat-current" value="{{ $statistics->settled_children_current_year ?? '' }}"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Children who are now settled and working</td>
                        <td><input type="text" name="working_children_previous_year" class="form-control select-input no-spinner cci-stat-prev" value="{{ $statistics->working_children_previous_year ?? '' }}"></td>
                        <td><input type="text" name="working_children_current_year" class="form-control select-input no-spinner cci-stat-current" value="{{ $statistics->working_children_current_year ?? '' }}"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Any other category</td>
                        <td><input type="text" name="other_category_previous_year" class="form-control select-input no-spinner cci-stat-prev" value="{{ $statistics->other_category_previous_year ?? '' }}"></td>
                        <td><input type="text" name="other_category_current_year" class="form-control select-input no-spinner cci-stat-current" value="{{ $statistics->other_category_current_year ?? '' }}"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function calcCciStatisticsTotals() {
        var prev = 0, curr = 0;
        document.querySelectorAll('#cci-statistics-table .cci-stat-prev').forEach(function(inp) {
            prev += parseFloat(inp.value) || 0;
        });
        document.querySelectorAll('#cci-statistics-table .cci-stat-current').forEach(function(inp) {
            curr += parseFloat(inp.value) || 0;
        });
        var prevEl = document.getElementById('cci-statistics-total-prev');
        var currEl = document.getElementById('cci-statistics-total-current');
        if (prevEl) prevEl.value = prev;
        if (currEl) currEl.value = curr;
    }
    var table = document.getElementById('cci-statistics-table');
    if (table) {
        table.addEventListener('input', calcCciStatisticsTotals);
        calcCciStatisticsTotals();
    }
});
</script>
