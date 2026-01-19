<div class="mb-3 card">
    <div class="card-header">
        <h4>Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">
        <!-- Personal Information Fields -->
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="iies_bname" class="form-control">
        </div>

        <div class="form-group">
            <label>Age:</label>
            <input type="number" name="iies_age" class="form-control">
        </div>

        <div class="form-group">
            <label for="iies_gender">Gender:</label>
            <select name="iies_gender" id="iies_gender" class="form-control">
                <option value="" selected disabled>Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label>Date of Birth:</label>
            <input type="date" name="iies_dob" class="form-control">
        </div>

        <div class="form-group">
            <label>E-mail:</label>
            <input type="email" name="iies_email" class="form-control">
        </div>

        <div class="form-group">
            <label>Contact number:</label>
            <input type="text" name="iies_contact" class="form-control">
        </div>

        <div class="form-group">
            <label>Aadhar number:</label>
            <input type="text" name="iies_aadhar" class="form-control">
        </div>

        <div class="form-group">
            <label>Full Address:</label>
            <textarea name="iies_full_address" class="form-control sustainability-textarea" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label>Name of Father:</label>
            <input type="text" name="iies_father_name" class="form-control">
        </div>

        <div class="form-group">
            <label>Name of Mother:</label>
            <input type="text" name="iies_mother_name" class="form-control">
        </div>

        <div class="form-group">
            <label>Mother tongue:</label>
            <input type="text" name="iies_mother_tongue" class="form-control">
        </div>

        <div class="form-group">
            <label>Current studies:</label>
            <input type="text" name="iies_current_studies" class="form-control">
        </div>

        <div class="form-group">
            <label>Caste:</label>
            <input type="text" name="iies_bcaste" class="form-control">
        </div>
    </div>

    <div class="card-header">
        <h4>Information about the Family</h4>
    </div>
    <div class="card-body">
        <!-- Family Information Fields -->
        <div class="form-group">
            <label>Occupation of Father:</label>
            <input type="text" name="iies_father_occupation" class="form-control">
        </div>

        <div class="form-group">
            <label>Monthly income of Father:</label>
            <input type="number" step="0.01" name="iies_father_income" class="form-control">
        </div>

        <div class="form-group">
            <label>Occupation of Mother:</label>
            <input type="text" name="iies_mother_occupation" class="form-control">
        </div>

        <div class="form-group">
            <label>Monthly income of Mother:</label>
            <input type="number" step="0.01" name="iies_mother_income" class="form-control">
        </div>
    </div>
</div>

