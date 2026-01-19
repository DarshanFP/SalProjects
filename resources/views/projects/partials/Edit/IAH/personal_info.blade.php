{{-- resources/views/projects/partials/Edit/IAH/personal_info.blade.php --}}

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">
        @php
            $personalInfo = $project->iahPersonalInfo ?? new \App\Models\OldProjects\IAH\ProjectIAHPersonalInfo();
        @endphp

        <!-- Name -->
        <div class="mb-3">
            <label for="name" class="form-label">Name:</label>
            <input type="text" name="name" class="form-control" placeholder="Enter beneficiary's name" value="{{ old('name', $personalInfo->name) }}">
        </div>

        <!-- Age -->
        <div class="mb-3">
            <label for="age" class="form-label">Age:</label>
            <input type="number" name="age" class="form-control" placeholder="Enter beneficiary's age" value="{{ old('age', $personalInfo->age) }}">
        </div>

        <!-- Gender -->
        <div class="mb-3">
            <label for="gender" class="form-label">Gender:</label>
            <select name="gender" class="form-control">
                <option value="" disabled {{ old('gender', $personalInfo->gender) ? '' : 'selected' }}>Select Gender</option>
                <option value="Female" {{ old('gender', $personalInfo->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                <option value="Male" {{ old('gender', $personalInfo->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Transgender" {{ old('gender', $personalInfo->gender) == 'Transgender' ? 'selected' : '' }}>Transgender</option>
            </select>
        </div>

        <!-- Date of Birth -->
        <div class="mb-3">
            <label for="dob" class="form-label">Date of Birth:</label>
            <input type="date" name="dob" class="form-control" value="{{ old('dob', $personalInfo->dob) }}">
        </div>

        <!-- Aadhar Number -->
        <div class="mb-3">
            <label for="aadhar" class="form-label">Aadhar Number:</label>
            <input type="text" name="aadhar" class="form-control" placeholder="Enter Aadhar number" maxlength="12" value="{{ old('aadhar', $personalInfo->aadhar) }}">
        </div>

        <!-- Contact Number -->
        <div class="mb-3">
            <label for="contact" class="form-label">Contact Number:</label>
            <input type="text" name="contact" class="form-control" placeholder="Enter contact number" value="{{ old('contact', $personalInfo->contact) }}">
        </div>

        <!-- Full Address -->
        <div class="mb-3">
            <label for="address" class="form-label">Full Address:</label>
            <textarea name="address" class="form-control auto-resize-textarea" rows="2" placeholder="Enter full address">{{ old('address', $personalInfo->address) }}</textarea>
        </div>

        <!-- E-mail -->
        <div class="mb-3">
            <label for="email" class="form-label">E-mail:</label>
            <input type="email" name="email" class="form-control" placeholder="Enter email address" value="{{ old('email', $personalInfo->email) }}">
        </div>

        <!-- Guardian's Name -->
        <div class="mb-3">
            <label for="guardian_name" class="form-label">Name of Father/Husband/Legal Guardian:</label>
            <input type="text" name="guardian_name" class="form-control" placeholder="Enter guardian's name" value="{{ old('guardian_name', $personalInfo->guardian_name) }}">
        </div>

        <!-- Number of Children -->
        <div class="mb-3">
            <label for="children" class="form-label">Number of Children:</label>
            <input type="number" name="children" class="form-control" placeholder="Enter number of children" min="0" value="{{ old('children', $personalInfo->children) }}">
        </div>

        <!-- Caste -->
        <div class="mb-3">
            <label for="caste" class="form-label">Caste (Specify):</label>
            <input type="text" name="caste" class="form-control" placeholder="Specify caste" value="{{ old('caste', $personalInfo->caste) }}">
        </div>

        <!-- Religion -->
        <div class="mb-3">
            <label for="religion" class="form-label">Religion:</label>
            <input type="text" name="religion" class="form-control" placeholder="Enter religion" value="{{ old('religion', $personalInfo->religion) }}">
        </div>
    </div>
</div>
