<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">

        <!-- Name -->
        <div class="mb-3">
            <label for="name" class="form-label">Name:</label>
            <input type="text" name="name" class="form-control" placeholder="Enter beneficiary's name" style="background-color: #202ba3;">
        </div>

        <!-- Age -->
        <div class="mb-3">
            <label for="age" class="form-label">Age:</label>
            <input type="number" name="age" class="form-control" placeholder="Enter age" style="background-color: #202ba3;">
        </div>

        <!-- Gender -->
        <div class="mb-3">
            <label for="gender" class="form-label">Gender:</label>
            <select name="gender" class="form-control" style="background-color: #202ba3;">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <!-- Date of Birth -->
        <div class="mb-3">
            <label for="dob" class="form-label">Date of Birth:</label>
            <input type="date" name="dob" class="form-control" style="background-color: #202ba3;">
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label for="email" class="form-label">E-mail:</label>
            <input type="email" name="email" class="form-control" placeholder="Enter email" style="background-color: #202ba3;">
        </div>

        <!-- Contact Number -->
        <div class="mb-3">
            <label for="contact_no" class="form-label">Contact number:</label>
            <input type="text" name="contact_no" class="form-control" placeholder="Enter contact number" style="background-color: #202ba3;">
        </div>

        <!-- Aadhar ID -->
        <div class="mb-3">
            <label for="aadhar_id" class="form-label">Aadhar ID number:</label>
            <input type="text" name="aadhar_id" class="form-control" placeholder="Enter Aadhar ID" style="background-color: #202ba3;">
        </div>

        <!-- Address -->
        <div class="mb-3">
            <label for="address" class="form-label">Full Address:</label>
            <textarea name="address" class="form-control" rows="3" placeholder="Enter full address" style="background-color: #202ba3;"></textarea>
        </div>

        <!-- Occupation -->
        <div class="mb-3">
            <label for="occupation" class="form-label">Occupation:</label>
            <input type="text" name="occupation" class="form-control" placeholder="Enter occupation" style="background-color: #202ba3;">
        </div>

        <!-- Marital Status -->
        <div class="mb-3">
            <label for="marital_status" class="form-label">Marital Status:</label>
            <select name="marital_status" class="form-control" style="background-color: #202ba3;">
                <option value="Single">Single</option>
                <option value="Married">Married</option>
                <option value="Divorced">Divorced</option>
                <option value="Widowed">Widowed</option>
            </select>
        </div>

        <!-- Spouse Name (Optional) -->
        <div class="mb-3" id="spouse_name_container" style="display: none;">
            <label for="spouse_name" class="form-label">Spouse name:</label>
            <input type="text" name="spouse_name" class="form-control" placeholder="Enter spouse name" style="background-color: #202ba3;">
        </div>

        <!-- Number of Children -->
        <div class="mb-3">
            <label for="children_no" class="form-label">Number of children:</label>
            <input type="number" name="children_no" class="form-control" placeholder="Enter number of children" style="background-color: #202ba3;">
        </div>

        <!-- Children Education Qualification -->
        <div class="mb-3">
            <label for="children_edu" class="form-label">Education qualification of children:</label>
            <textarea name="children_edu" class="form-control" rows="2" placeholder="Enter children education qualification" style="background-color: #202ba3;"></textarea>
        </div>

        <!-- Religion -->
        <div class="mb-3">
            <label for="religion" class="form-label">Religion:</label>
            <input type="text" name="religion" class="form-control" placeholder="Enter religion" style="background-color: #202ba3;">
        </div>

        <!-- Caste -->
        <div class="mb-3">
            <label for="caste" class="form-label">Caste:</label>
            <input type="text" name="caste" class="form-control" placeholder="Enter caste" style="background-color: #202ba3;">
        </div>

        <!-- Present Family Situation -->
        <div class="mb-3">
            <label for="family_situation" class="form-label">Give details of the present family situation:</label>
            <textarea name="family_situation" class="form-control" rows="3" placeholder="Enter family situation" style="background-color: #202ba3;"></textarea>
        </div>

        <!-- Small-scale Business Status -->
        <div class="mb-3">
            <label for="small_business_status" class="form-label">Is the beneficiary currently into any small-scale business?</label>
            <select name="small_business_status" class="form-control" style="background-color: #202ba3;">
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>
        </div>

        <!-- Small Business Details (if applicable) -->
        <div class="mb-3" id="small_business_details_container" style="display: none;">
            <label for="small_business_details" class="form-label">If yes, give details:</label>
            <textarea name="small_business_details" class="form-control" rows="3" placeholder="Provide details" style="background-color: #202ba3;"></textarea>
        </div>

        <!-- Monthly Income -->
        <div class="mb-3">
            <label for="monthly_income" class="form-label">Current (average) monthly income:</label>
            <input type="number" step="0.01" name="monthly_income" class="form-control" placeholder="Enter monthly income" style="background-color: #202ba3;">
        </div>

        <!-- Business Plan -->
        <div class="mb-3">
            <label for="business_plan" class="form-label">Explain the Beneficiary’s present business plan:</label>
            <textarea name="business_plan" class="form-control" rows="3" placeholder="Provide details of the business plan" style="background-color: #202ba3;"></textarea>
        </div>

    </div>
</div>

<script>
    (function(){
    document.addEventListener('DOMContentLoaded', function () {
        const maritalStatusField = document.querySelector('select[name="marital_status"]');
        const spouseNameContainer = document.getElementById('spouse_name_container');
        const smallBusinessStatusField = document.querySelector('select[name="small_business_status"]');
        const smallBusinessDetailsContainer = document.getElementById('small_business_details_container');

        maritalStatusField.addEventListener('change', function () {
            if (this.value === 'Married') {
                spouseNameContainer.style.display = 'block';
            } else {
                spouseNameContainer.style.display = 'none';
            }
        });

        smallBusinessStatusField.addEventListener('change', function () {
            if (this.value === '1') {
                smallBusinessDetailsContainer.style.display = 'block';
            } else {
                smallBusinessDetailsContainer.style.display = 'none';
            }
        });
    });
})();
</script>
