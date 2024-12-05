{{-- resources/views/projects/partials/Edit/IGE/institution_info.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Institution Information</h4>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="institutional_type">Select Institutional Type:</label>
            <select name="institutional_type" class="form-control" style="background-color: #202ba3;">
                <option value="">-- Select --</option>
                <option value="Institutional" {{ old('institutional_type', $IGEinstitutionInfo->institutional_type ?? '') == 'Institutional' ? 'selected' : '' }}>Institutional</option>
                <option value="Non-Institutional" {{ old('institutional_type', $IGEinstitutionInfo->institutional_type ?? '') == 'Non-Institutional' ? 'selected' : '' }}>Non-Institutional</option>
            </select>
        </div>

        <br>

        <div class="form-group">
            <label for="age_group">Select Age Group:</label>
            <select name="age_group" class="form-control" style="background-color: #202ba3;">
                <option value="">-- Select --</option>
                <option value="CHILDREN" {{ old('age_group', $IGEinstitutionInfo->age_group ?? '') == 'CHILDREN' ? 'selected' : '' }}>CHILDREN</option>
                <option value="YOUTH" {{ old('age_group', $IGEinstitutionInfo->age_group ?? '') == 'YOUTH' ? 'selected' : '' }}>YOUTH</option>
            </select>
        </div>

        <br>

        <div class="form-group">
            <label for="previous_year_beneficiaries">Number of Beneficiaries Supported in the Previous Years:</label>
            <input type="number" name="previous_year_beneficiaries" class="form-control" style="background-color: #202ba3;" value="{{ old('previous_year_beneficiaries', $IGEinstitutionInfo->previous_year_beneficiaries ?? '') }}">
        </div>

        <br>

        <div class="form-group">
            <label for="outcome_impact">Give Outcome / Impact in the lives of the passed-out students who were given the project support:</label>
            <textarea name="outcome_impact" class="form-control" rows="4" style="background-color: #202ba3;">{{ old('outcome_impact', $IGEinstitutionInfo->outcome_impact ?? '') }}</textarea>
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
