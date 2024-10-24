{{-- resources/views/projects/partials/IAH/personal_info.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="name" class="form-label">Name:</label>
            <input type="text" name="name" class="form-control" placeholder="Enter beneficiary's name" required>
        </div>

        <div class="mb-3">
            <label for="age" class="form-label">Age:</label>
            <input type="number" name="age" class="form-control" placeholder="Enter beneficiary's age" required>
        </div>

        <div class="mb-3">
            <label for="gender" class="form-label">Gender:</label>
            <select name="gender" class="form-control" required>
                <option value="" disabled selected>Select Gender</option>
                <option value="Female">Female</option>
                <option value="Male">Male</option>
                <option value="Transgender">Transgender</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="dob" class="form-label">Date of Birth:</label>
            <input type="date" name="dob" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="aadhar" class="form-label">Aadhar Number:</label>
            <input type="text" name="aadhar" class="form-control" placeholder="Enter Aadhar number" maxlength="12" required>
        </div>

        <div class="mb-3">
            <label for="contact" class="form-label">Contact Number:</label>
            <input type="text" name="contact" class="form-control" placeholder="Enter contact number" required>
        </div>

        <div class="mb-3">
            <label for="address" class="form-label">Full Address:</label>
            <textarea name="address" class="form-control" rows="2" placeholder="Enter full address" required></textarea>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">E-mail:</label>
            <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
        </div>

        <div class="mb-3">
            <label for="guardian_name" class="form-label">Name of Father/Husband/Legal Guardian:</label>
            <input type="text" name="guardian_name" class="form-control" placeholder="Enter guardian's name" required>
        </div>

        <div class="mb-3">
            <label for="children" class="form-label">Number of Children:</label>
            <input type="number" name="children" class="form-control" placeholder="Enter number of children" min="0">
        </div>

        <div class="mb-3">
            <label for="caste" class="form-label">Caste (Specify):</label>
            <input type="text" name="caste" class="form-control" placeholder="Specify caste">
        </div>

        <div class="mb-3">
            <label for="religion" class="form-label">Religion:</label>
            <input type="text" name="religion" class="form-control" placeholder="Enter religion">
        </div>
    </div>
</div>
