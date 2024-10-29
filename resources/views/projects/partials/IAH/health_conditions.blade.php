{{-- resources/views/projects/partials/IAH/health_conditions.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Details of Health Conditions</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="illness" class="form-label">Nature of Illness:</label>
            <input type="text" name="illness" class="form-control" placeholder="Enter nature of illness">
        </div>

        <div class="mb-3">
            <label for="treatment" class="form-label">Is the beneficiary undergoing medical treatment?</label>
            <div>
                <input type="radio" name="treatment" value="1"> Yes
                <input type="radio" name="treatment" value="0"> No
            </div>
        </div>

        <div class="mb-3">
            <label for="doctor" class="form-label">If yes, Name of Doctor:</label>
            <input type="text" name="doctor" class="form-control" placeholder="Enter doctor's name">
        </div>

        <div class="mb-3">
            <label for="hospital" class="form-label">Name of Hospital:</label>
            <input type="text" name="hospital" class="form-control" placeholder="Enter hospital's name">
        </div>

        <div class="mb-3">
            <label for="doctor_address" class="form-label">Address of Doctor/Hospital:</label>
            <textarea name="doctor_address" class="form-control" rows="2" placeholder="Enter doctor's or hospital's address"></textarea>
        </div>

        <div class="mb-3">
            <label for="health_situation" class="form-label">Please mention clearly about the health situation of the beneficiary:</label>
            <textarea name="health_situation" class="form-control" rows="3" placeholder="Provide details on the health situation"></textarea>
        </div>

        <div class="mb-3">
            <label for="family_situation" class="form-label">Give information about the present situation of the family:</label>
            <textarea name="family_situation" class="form-control" rows="3" placeholder="Provide details on the family situation"></textarea>
        </div>
    </div>
</div>
