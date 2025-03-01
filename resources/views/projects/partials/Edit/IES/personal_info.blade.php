{{-- resources/views/projects/partials/Edit/IES/personal_info.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">
        @php
            $personalInfo = $project->iesPersonalInfo ?? new \App\Models\OldProjects\IES\ProjectIESPersonalInfo();
        @endphp

        <!-- Personal Information Fields -->
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="bname" class="form-control" value="{{ old('bname', $personalInfo->bname) }}">
        </div>

        <div class="form-group">
            <label>Age:</label>
            <input type="number" name="age" class="form-control" value="{{ old('age', $personalInfo->age) }}">
        </div>

        <div class="form-group">
            <label>Gender:</label>
            <select name="gender" class="form-control">
                <option value="" disabled>Select Gender</option>
                <option value="male" {{ old('gender', $personalInfo->gender) == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender', $personalInfo->gender) == 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('gender', $personalInfo->gender) == 'other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>

        <div class="form-group">
            <label>Date of Birth:</label>
            <input type="date" name="dob" class="form-control" value="{{ old('dob', $personalInfo->dob) }}">
        </div>

        <div class="form-group">
            <label>E-mail:</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $personalInfo->email) }}">
        </div>

        <div class="form-group">
            <label>Contact number:</label>
            <input type="text" name="contact" class="form-control" value="{{ old('contact', $personalInfo->contact) }}">
        </div>

        <div class="form-group">
            <label>Aadhar number:</label>
            <input type="text" name="aadhar" class="form-control" value="{{ old('aadhar', $personalInfo->aadhar) }}">
        </div>

        <div class="form-group">
            <label>Full Address:</label>
            <textarea name="full_address" class="form-control" rows="3">{{ old('full_address', $personalInfo->full_address) }}</textarea>
        </div>

        <div class="form-group">
            <label>Name of Father:</label>
            <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $personalInfo->father_name) }}">
        </div>

        <div class="form-group">
            <label>Name of Mother:</label>
            <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $personalInfo->mother_name) }}">
        </div>

        <div class="form-group">
            <label>Mother tongue:</label>
            <input type="text" name="mother_tongue" class="form-control" value="{{ old('mother_tongue', $personalInfo->mother_tongue) }}">
        </div>

        <div class="form-group">
            <label>Current studies:</label>
            <input type="text" name="current_studies" class="form-control" value="{{ old('current_studies', $personalInfo->current_studies) }}">
        </div>

        <div class="form-group">
            <label>Caste:</label>
            <input type="text" name="bcaste" class="form-control" value="{{ old('bcaste', $personalInfo->bcaste) }}">
        </div>
    </div>

    <div class="card-header">
        <h4>Edit: Information about the Family</h4>
    </div>
    <div class="card-body">
        <!-- Family Information Fields -->
        <div class="form-group">
            <label>Occupation of Father:</label>
            <input type="text" name="father_occupation" class="form-control" value="{{ old('father_occupation', $personalInfo->father_occupation) }}">
        </div>

        <div class="form-group">
            <label>Monthly income of Father:</label>
            <input type="number" step="0.01" name="father_income" class="form-control" value="{{ old('father_income', $personalInfo->father_income) }}">
        </div>

        <div class="form-group">
            <label>Occupation of Mother:</label>
            <input type="text" name="mother_occupation" class="form-control" value="{{ old('mother_occupation', $personalInfo->mother_occupation) }}">
        </div>

        <div class="form-group">
            <label>Monthly income of Mother:</label>
            <input type="number" step="0.01" name="mother_income" class="form-control" value="{{ old('mother_income', $personalInfo->mother_income) }}">
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
