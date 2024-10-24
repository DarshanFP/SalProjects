<div class="mb-3 card">
    <div class="card-header">
        <h4>Details about Immediate Family Members</h4>
    </div>
    <div class="card-body">
        <!-- Immediate Family Details -->
        <div class="form-group">
            <label><strong>Immediate Family Details</strong></label>
            <div class="form-check">
                <input type="checkbox" name="mother_expired" class="form-check-input" value="1">
                <label class="form-check-label">Mother expired</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="father_expired" class="form-check-input" value="1">
                <label class="form-check-label">Father expired</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="grandmother_support" class="form-check-input" value="1">
                <label class="form-check-label">Grandmother supports family</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="grandfather_support" class="form-check-input" value="1">
                <label class="form-check-label">Grandfather supports family</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="father_deserted" class="form-check-input" value="1">
                <label class="form-check-label">Father deserted the family</label>
            </div>
            <div class="form-group">
                <label>Any other:</label>
                <input type="text" name="other_family_details" class="form-control" style="background-color: #202ba3;">
            </div>
        </div>

        <!-- Health of Father -->
        <div class="form-group">
            <label><strong>Health of Father</strong></label>
            <div class="form-check">
                <input type="checkbox" name="father_sick" class="form-check-input" value="1">
                <label class="form-check-label">Chronically Sick</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="father_hiv_aids" class="form-check-input" value="1">
                <label class="form-check-label">HIV/AIDS positive</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="father_disabled" class="form-check-input" value="1">
                <label class="form-check-label">Disabled</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="father_alcoholic" class="form-check-input" value="1">
                <label class="form-check-label">Alcoholic</label>
            </div>
            <div class="form-group">
                <label>Others:</label>
                <input type="text" name="father_health_others" class="form-control" style="background-color: #202ba3;">
            </div>
        </div>

        <!-- Health of Mother -->
        <div class="form-group">
            <label><strong>Health of Mother</strong></label>
            <div class="form-check">
                <input type="checkbox" name="mother_sick" class="form-check-input" value="1">
                <label class="form-check-label">Chronically Sick</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="mother_hiv_aids" class="form-check-input" value="1">
                <label class="form-check-label">HIV/AIDS positive</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="mother_disabled" class="form-check-input" value="1">
                <label class="form-check-label">Disabled</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="mother_alcoholic" class="form-check-input" value="1">
                <label class="form-check-label">Alcoholic</label>
            </div>
            <div class="form-group">
                <label>Others:</label>
                <input type="text" name="mother_health_others" class="form-control" style="background-color: #202ba3;">
            </div>
        </div>

        <!-- Residential Status -->
        <div class="form-group">
            <label><strong>Residential Status</strong></label>
            <div class="form-check">
                <input type="checkbox" name="own_house" class="form-check-input" value="1">
                <label class="form-check-label">Own house</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="rented_house" class="form-check-input" value="1">
                <label class="form-check-label">Rented house</label>
            </div>
            <div class="form-group">
                <label>Others:</label>
                <input type="text" name="residential_others" class="form-control" style="background-color: #202ba3;">
            </div>
        </div>

        <!-- Family Situation -->
        <div class="form-group">
            <label><strong>Family Situation</strong></label>
            <textarea name="family_situation" class="form-control" rows="3" style="background-color: #202ba3;"></textarea>
        </div>

        <!-- Need of Project Assistance -->
        <div class="form-group">
            <label><strong>Need of Project Assistance</strong></label>
            <textarea name="assistance_need" class="form-control" rows="3" style="background-color: #202ba3;"></textarea>
        </div>

        <!-- Financial Support -->
        <div class="form-group">
            <label><strong>Has the family of the beneficiary received financial support previously through St. Ann's projects?</strong></label>
            <div class="form-check">
                <input type="radio" name="received_support" class="form-check-input" value="1">
                <label class="form-check-label">Yes</label>
            </div>
            <div class="form-check">
                <input type="radio" name="received_support" class="form-check-input" value="0">
                <label class="form-check-label">No</label>
            </div>
            <div class="form-group">
                <label>If yes, give details:</label>
                <textarea name="support_details" class="form-control" rows="3" style="background-color: #202ba3;"></textarea>
            </div>
        </div>

        <!-- Employment with St. Ann's -->
        <div class="form-group">
            <label><strong>Are the family members of the beneficiary employed with St. Ann's?</strong></label>
            <div class="form-check">
                <input type="radio" name="employed_with_stanns" class="form-check-input" value="1">
                <label class="form-check-label">Yes</label>
            </div>
            <div class="form-check">
                <input type="radio" name="employed_with_stanns" class="form-check-input" value="0">
                <label class="form-check-label">No</label>
            </div>
            <div class="form-group">
                <label>If yes, give details:</label>
                <textarea name="employment_details" class="form-control" rows="3" style="background-color: #202ba3;"></textarea>
            </div>
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
