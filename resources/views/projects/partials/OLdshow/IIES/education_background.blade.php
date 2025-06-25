{{-- resources/views/projects/partials/Edit/IIES/education_background.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Educational Background / Present Education (Support Requested)</h4>
    </div>
    <div class="card-body">
        <!-- Previous Academic Education -->
        <div class="form-group">
            <label for="prev_education">Previous Academic Education:</label>
            <input type="text" name="prev_education" id="prev_education" class="form-control" style="background-color: #202ba3;" value="{{ old('prev_education', $educationBackground->prev_education) }}">
        </div>

        <!-- Previous Institution -->
        <div class="form-group">
            <label for="prev_institution">Name and Address of the Previous Institution:</label>
            <input type="text" name="prev_institution" id="prev_institution" class="form-control" placeholder="Institution Name" style="background-color: #202ba3;" value="{{ old('prev_institution', $educationBackground->prev_institution) }}">
            <input type="text" name="prev_insti_address" id="prev_insti_address" class="mt-2 form-control" placeholder="Institution Address" style="background-color: #202ba3;" value="{{ old('prev_insti_address', $educationBackground->prev_insti_address) }}">
        </div>

        <!-- Percentage of Marks Secured -->
        <div class="form-group">
            <label for="prev_marks">Percentage of Marks Secured:</label>
            <input type="number" name="prev_marks" id="prev_marks" class="form-control" step="0.01" style="background-color: #202ba3;" value="{{ old('prev_marks', $educationBackground->prev_marks) }}">
        </div>

        <!-- Current Studies -->
        <div class="form-group">
            <label for="current_studies">Current Studies:</label>
            <input type="text" name="current_studies" id="current_studies" class="form-control" style="background-color: #202ba3;" value="{{ old('current_studies', $educationBackground->current_studies) }}">
        </div>

        <!-- Present Institution -->
        <div class="form-group">
            <label for="curr_institution">Present Institution Name and Address:</label>
            <input type="text" name="curr_institution" id="curr_institution" class="form-control" placeholder="Institution Name" style="background-color: #202ba3;" value="{{ old('curr_institution', $educationBackground->curr_institution) }}">
            <input type="text" name="curr_insti_address" id="curr_insti_address" class="mt-2 form-control" placeholder="Institution Address" style="background-color: #202ba3;" value="{{ old('curr_insti_address', $educationBackground->curr_insti_address) }}">
        </div>

        <!-- Educational Aspirations -->
        <div class="form-group">
            <label for="aspiration">Beneficiary's Educational Aspirations:</label>
            <textarea name="aspiration" id="aspiration" class="form-control" rows="3" style="background-color: #202ba3;">{{ old('aspiration', $educationBackground->aspiration) }}</textarea>
        </div>

        <!-- Sustainability of the Support -->
        <div class="form-group">
            <label for="long_term_effect">Sustainability of Support:</label>
            <textarea name="long_term_effect" id="long_term_effect" class="form-control" rows="3" style="background-color: #202ba3;">{{ old('long_term_effect', $educationBackground->long_term_effect) }}</textarea>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    .form-control {
        background-color: #202ba3;
        color: white;
    }
</style>
