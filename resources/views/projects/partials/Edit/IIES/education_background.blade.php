{{-- resources/views/projects/partials/Edit/IIES/education_background.blade.php --}}
{{-- <div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Educational Background / Present Education </h4>
    </div>
    <div class="card-body">
        <!-- Previous Academic Education -->
        <div class="form-group">
            <label for="prev_education">Mention the previous academic education</label>
            <input type="text" name="prev_education" id="prev_education" class="form-control"
                   value="{{ old('prev_education', $IIESIIESEducationBackground->prev_education ?? '') }}">
        </div>

        <!-- Previous Institution -->
        <div class="form-group">
            <label for="prev_institution">Name and Address of the previous institution:</label>
            <input type="text" name="prev_institution" id="prev_institution" class="form-control"
                   value="{{ old('prev_institution', $IIESIIESEducationBackground->prev_institution ?? '') }}" placeholder="Institution Name">
            <input type="text" name="prev_insti_address" id="prev_insti_address" class="mt-2 form-control"
                   value="{{ old('prev_insti_address', $IIESIIESEducationBackground->prev_insti_address ?? '') }}" placeholder="Institution Address">
        </div>

        <!-- Percentage of Marks Secured -->
        <div class="form-group">
            <label for="prev_marks">Percentage of marks secured:</label>
            <input type="number" step="0.01" name="prev_marks" id="prev_marks" class="form-control"
                   value="{{ old('prev_marks', $IIESIIESEducationBackground->prev_marks ?? '') }}">
        </div>

        <!-- Current Studies -->
        <div class="form-group">
            <label for="current_studies">Studies currently pursued:</label>
            <input type="text" name="current_studies" id="current_studies" class="form-control"
                   value="{{ old('current_studies', $IIESIIESEducationBackground->current_studies ?? '') }}">
        </div>

        <!-- Present Institution -->
        <div class="form-group">
            <label for="curr_institution">Name and Address of the Present Institution:</label>
            <input type="text" name="curr_institution" id="curr_institution" class="form-control"
                   value="{{ old('curr_institution', $IIESIIESEducationBackground->curr_institution ?? '') }}" placeholder="Institution Name">
            <input type="text" name="curr_insti_address" id="curr_insti_address" class="mt-2 form-control"
                   value="{{ old('curr_insti_address', $IIESIIESEducationBackground->curr_insti_address ?? '') }}" placeholder="Institution Address">
        </div>

        <!-- Educational Aspirations -->
        <div class="form-group">
            <label for="aspiration">What is the educational aspiration and area of interest of the beneficiary?</label>
            <textarea name="aspiration" id="aspiration" class="form-control" rows="3">{{ old('aspiration', $IIESIIESEducationBackground->aspiration ?? '') }}</textarea>
        </div>

        <!-- Sustainability of the Support -->
        <div class="form-group">
            <label for="long_term_effect">Sustainability of the support (Impact on beneficiary's life in the long run):</label>
            <textarea name="long_term_effect" id="long_term_effect" class="form-control" rows="3">{{ old('long_term_effect', $IIESIIESEducationBackground->long_term_effect ?? '') }}</textarea>
        </div>
    </div>
</div> --}}

<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Educational Background / Present Education (Support Requested)</h4>
    </div>
    <div class="card-body">
        <!-- Previous Academic Education -->
        <div class="form-group">
            <label for="prev_education">Mention the previous academic education</label>
            <input type="text" name="prev_education" id="prev_education" class="form-control"
                   value="{{ old('prev_education', $IIESEducationBackground->prev_education ?? '') }}">
        </div>

        <!-- Previous Institution -->
        <div class="form-group">
            <label for="prev_institution">Name and Address of the previous institution:</label>
            <input type="text" name="prev_institution" id="prev_institution" class="form-control"
                   value="{{ old('prev_institution', $IIESEducationBackground->prev_institution ?? '') }}" placeholder="Institution Name">
            <input type="text" name="prev_insti_address" id="prev_insti_address" class="mt-2 form-control"
                   value="{{ old('prev_insti_address', $IIESEducationBackground->prev_insti_address ?? '') }}" placeholder="Institution Address">
        </div>

        <!-- Percentage of Marks Secured -->
        <div class="form-group">
            <label for="prev_marks">Percentage of marks secured:</label>
            <input type="number" step="0.01" name="prev_marks" id="prev_marks" class="form-control"
                   value="{{ old('prev_marks', $IIESEducationBackground->prev_marks ?? '') }}">
        </div>

        <!-- Current Studies -->
        <div class="form-group">
            <label for="current_studies">Studies currently pursued:</label>
            <input type="text" name="current_studies" id="current_studies" class="form-control"
                   value="{{ old('current_studies', $IIESEducationBackground->current_studies ?? '') }}">
        </div>

        <!-- Present Institution -->
        <div class="form-group">
            <label for="curr_institution">Name and Address of the Present Institution:</label>
            <input type="text" name="curr_institution" id="curr_institution" class="form-control"
                   value="{{ old('curr_institution', $IIESEducationBackground->curr_institution ?? '') }}" placeholder="Institution Name">
            <input type="text" name="curr_insti_address" id="curr_insti_address" class="mt-2 form-control"
                   value="{{ old('curr_insti_address', $IIESEducationBackground->curr_insti_address ?? '') }}" placeholder="Institution Address">
        </div>

        <!-- Educational Aspirations -->
        <div class="form-group">
            <label for="aspiration">What is the educational aspiration and area of interest of the beneficiary?</label>
            <textarea name="aspiration" id="aspiration" class="form-control" rows="3">{{ old('aspiration', $IIESEducationBackground->aspiration ?? '') }}</textarea>
        </div>

        <!-- Sustainability of the Support -->
        <div class="form-group">
            <label for="long_term_effect">Sustainability of the support (Impact on beneficiary's life in the long run):</label>
            <textarea name="long_term_effect" id="long_term_effect" class="form-control" rows="3">{{ old('long_term_effect', $IIESEducationBackground->long_term_effect ?? '') }}</textarea>
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
