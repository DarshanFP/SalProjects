{{-- resources/views/projects/partials/Edit/ILP/personal_info.blade.php --}}

{{-- <pre>{{ print_r($ILPILPPersonalInfo, return: true) }}</pre> --}}

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">

        <!-- Name -->
        <div class="mb-3">
            <label for="name" class="form-label">Name:</label>
            <input type="text" name="name" class="form-control" value="{{ $ILPPersonalInfo->name ?? '' }}" placeholder="Enter beneficiary's name">
        </div>

        <!-- Age -->
        <div class="mb-3">
            <label for="age" class="form-label">Age:</label>
            <input type="number" name="age" class="form-control" value="{{ $ILPPersonalInfo->age ?? '' }}" placeholder="Enter age">
        </div>

        <!-- Gender -->
        <div class="mb-3">
            <label for="gender" class="form-label">Gender:</label>
            <select name="gender" class="form-control">
                <option value="Male" {{ isset($ILPPersonalInfo->gender) && $ILPPersonalInfo->gender == 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ isset($ILPPersonalInfo->gender) && $ILPPersonalInfo->gender == 'Female' ? 'selected' : '' }}>Female</option>
                <option value="Other" {{ isset($ILPPersonalInfo->gender) && $ILPPersonalInfo->gender == 'Other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>

        <!-- Date of Birth -->
        <div class="mb-3">
            <label for="dob" class="form-label">Date of Birth:</label>
            <input type="date" name="dob" class="form-control" value="{{ $ILPPersonalInfo->dob ?? '' }}">
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label for="email" class="form-label">E-mail:</label>
            <input type="email" name="email" class="form-control" value="{{ $ILPPersonalInfo->email ?? '' }}" placeholder="Enter email">
        </div>

        <!-- Contact Number -->
        <div class="mb-3">
            <label for="contact_no" class="form-label">Contact number:</label>
            <input type="text" name="contact_no" class="form-control" value="{{ $ILPPersonalInfo->contact_no ?? '' }}" placeholder="Enter contact number">
        </div>

        <!-- Aadhar ID -->
        <div class="mb-3">
            <label for="aadhar_id" class="form-label">Aadhar ID number:</label>
            <input type="text" name="aadhar_id" class="form-control" value="{{ $ILPPersonalInfo->aadhar_id ?? '' }}" placeholder="Enter Aadhar ID">
        </div>

        <!-- Address -->
        <div class="mb-3">
            <label for="address" class="form-label">Full Address:</label>
            <textarea name="address" class="form-control" rows="3" placeholder="Enter full address">{{ $ILPPersonalInfo->address ?? '' }}</textarea>
        </div>

        <!-- Marital Status -->
        <div class="mb-3">
            <label for="marital_status" class="form-label">Marital Status:</label>
            <select name="marital_status" class="form-control" id="marital_status">
                <option value="Single" {{ isset($ILPPersonalInfo->marital_status) && $ILPPersonalInfo->marital_status == 'Single' ? 'selected' : '' }}>Single</option>
                <option value="Married" {{ isset($ILPPersonalInfo->marital_status) && $ILPPersonalInfo->marital_status == 'Married' ? 'selected' : '' }}>Married</option>
                <option value="Divorced" {{ isset($ILPPersonalInfo->marital_status) && $ILPPersonalInfo->marital_status == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                <option value="Widowed" {{ isset($ILPPersonalInfo->marital_status) && $ILPPersonalInfo->marital_status == 'Widowed' ? 'selected' : '' }}>Widowed</option>
            </select>
        </div>

        <!-- Spouse Name (Only show if Married) -->
        <div class="mb-3" id="spouse_name_container" style="{{ isset($ILPPersonalInfo->marital_status) && $ILPPersonalInfo->marital_status == 'Married' ? '' : 'display: none;' }}">
            <label for="spouse_name" class="form-label">Spouse name:</label>
            <input type="text" name="spouse_name" class="form-control" value="{{ $ILPPersonalInfo->spouse_name ?? '' }}" placeholder="Enter spouse name">
        </div>

        <!-- Occupation -->
        <div class="mb-3">
            <label for="occupation" class="form-label">Occupation:</label>
            <input type="text" name="occupation" class="form-control" value="{{ $ILPPersonalInfo->occupation ?? '' }}" placeholder="Enter occupation">
        </div>

        <!-- Family Situation -->
        <div class="mb-3">
            <label for="family_situation" class="form-label">Present Family Situation:</label>
            <textarea name="family_situation" class="form-control" rows="3" placeholder="Describe family situation">{{ $ILPPersonalInfo->family_situation ?? '' }}</textarea>
        </div>

    </div>
</div>

<!-- Marital Status Toggle Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const maritalStatusField = document.querySelector('#marital_status');
        const spouseNameContainer = document.getElementById('spouse_name_container');

        maritalStatusField.addEventListener('change', function () {
            if (this.value === 'Married') {
                spouseNameContainer.style.display = 'block';
            } else {
                spouseNameContainer.style.display = 'none';
            }
        });
    });
</script>
