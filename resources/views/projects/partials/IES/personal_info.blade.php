{{-- resources/views/projects/partials/IES/personal_info.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">
        <!-- Personal Information Fields -->
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="name" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Age:</label>
            <input type="number" name="age" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Gender:</label>
            <input type="text" name="gender" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Date of Birth:</label>
            <input type="date" name="dob" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>E-mail:</label>
            <input type="email" name="email" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Contact number:</label>
            <input type="text" name="contact" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Aadhar number:</label>
            <input type="text" name="aadhar" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Full Address:</label>
            <textarea name="full_address" class="form-control" rows="3" style="background-color: #202ba3;"></textarea>
        </div>

        <div class="form-group">
            <label>Name of Father:</label>
            <input type="text" name="father_name" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Name of Mother:</label>
            <input type="text" name="mother_name" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Mother tongue:</label>
            <input type="text" name="mother_tongue" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Current studies:</label>
            <input type="text" name="current_studies" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Caste:</label>
            <input type="text" name="caste" class="form-control" style="background-color: #202ba3;">
        </div>
    </div>

    <div class="card-header">
        <h4>Information about the Family</h4>
    </div>
    <div class="card-body">
        <!-- Family Information Fields -->
        <div class="form-group">
            <label>Occupation of Father:</label>
            <input type="text" name="father_occupation" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Monthly income of Father:</label>
            <input type="number" step="0.01" name="father_income" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Occupation of Mother:</label>
            <input type="text" name="mother_occupation" class="form-control" style="background-color: #202ba3;">
        </div>

        <div class="form-group">
            <label>Monthly income of Mother:</label>
            <input type="number" step="0.01" name="mother_income" class="form-control" style="background-color: #202ba3;">
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
