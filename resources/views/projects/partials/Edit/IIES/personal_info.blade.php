{{-- resources/views/projects/partials/Edit/IIES/personal_info.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">
        @php
            $personalInfo = $project->iiesPersonalInfo ?? new \App\Models\OldProjects\IIES\ProjectIIESPersonalInfo();
        @endphp

        <!-- Personal Information Fields -->
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="iies_bname" class="form-control" value="{{ old('iies_bname', $personalInfo->iies_bname) }}">
        </div>

        <div class="form-group">
            <label>Age:</label>
            <input type="number" name="iies_age" class="form-control" value="{{ old('iies_age', $personalInfo->iies_age) }}">
        </div>

        <div class="form-group">
            <label for="iies_gender">Gender:</label>
            <select name="iies_gender" id="iies_gender" class="form-control">
                <option value="" disabled {{ old('iies_gender', $personalInfo->iies_gender) ? '' : 'selected' }}>Select Gender</option>
                <option value="male" {{ old('iies_gender', $personalInfo->iies_gender) == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('iies_gender', $personalInfo->iies_gender) == 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('iies_gender', $personalInfo->iies_gender) == 'other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>

        <div class="form-group">
            <label>Date of Birth:</label>
            <input type="date" name="iies_dob" class="form-control" value="{{ old('iies_dob', $personalInfo->iies_dob) }}">
        </div>

        <div class="form-group">
            <label>E-mail:</label>
            <input type="email" name="iies_email" class="form-control" value="{{ old('iies_email', $personalInfo->iies_email) }}">
        </div>

        <div class="form-group">
            <label>Contact number:</label>
            <input type="text" name="iies_contact" class="form-control" value="{{ old('iies_contact', $personalInfo->iies_contact) }}">
        </div>

        <div class="form-group">
            <label>Aadhar number:</label>
            <input type="text" name="iies_aadhar" class="form-control" value="{{ old('iies_aadhar', $personalInfo->iies_aadhar) }}">
        </div>

        <div class="form-group">
            <label>Full Address:</label>
            <textarea name="iies_full_address" class="form-control" rows="3">{{ old('iies_full_address', $personalInfo->iies_full_address) }}</textarea>
        </div>

        <div class="form-group">
            <label>Name of Father:</label>
            <input type="text" name="iies_father_name" class="form-control" value="{{ old('iies_father_name', $personalInfo->iies_father_name) }}">
        </div>

        <div class="form-group">
            <label>Name of Mother:</label>
            <input type="text" name="iies_mother_name" class="form-control" value="{{ old('iies_mother_name', $personalInfo->iies_mother_name) }}">
        </div>

        <div class="form-group">
            <label>Mother tongue:</label>
            <input type="text" name="iies_mother_tongue" class="form-control" value="{{ old('iies_mother_tongue', $personalInfo->iies_mother_tongue) }}">
        </div>

        <div class="form-group">
            <label>Current studies:</label>
            <input type="text" name="iies_current_studies" class="form-control" value="{{ old('iies_current_studies', $personalInfo->iies_current_studies) }}">
        </div>

        <div class="form-group">
            <label>Caste:</label>
            <input type="text" name="iies_bcaste" class="form-control" value="{{ old('iies_bcaste', $personalInfo->iies_bcaste) }}">
        </div>
    </div>

    <div class="card-header">
        <h4>Edit: Information about the Family</h4>
    </div>
    <div class="card-body">
        <!-- Family Information Fields -->
        <div class="form-group">
            <label>Occupation of Father:</label>
            <input type="text" name="iies_father_occupation" class="form-control" value="{{ old('iies_father_occupation', $personalInfo->iies_father_occupation) }}">
        </div>

        <div class="form-group">
            <label>Monthly income of Father:</label>
            <input type="number" step="0.01" name="iies_father_income" class="form-control" value="{{ old('iies_father_income', $personalInfo->iies_father_income) }}">
        </div>

        <div class="form-group">
            <label>Occupation of Mother:</label>
            <input type="text" name="iies_mother_occupation" class="form-control" value="{{ old('iies_mother_occupation', $personalInfo->iies_mother_occupation) }}">
        </div>

        <div class="form-group">
            <label>Monthly income of Mother:</label>
            <input type="number" step="0.01" name="iies_mother_income" class="form-control" value="{{ old('iies_mother_income', $personalInfo->iies_mother_income) }}">
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    .form-control {
        background-color: #202ba3;
        color: white;
    }
    .card-header h4 {
        margin-bottom: 0;
    }
</style>
