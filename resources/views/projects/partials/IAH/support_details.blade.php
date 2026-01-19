{{-- resources/views/projects/partials/IAH/support_details.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Are the family members employed with St. Ann’s? Provide details.</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="employed_at_st_ann" class="form-label">Are the family members employed with St. Ann’s?</label>
            <div>
                <input type="radio" name="employed_at_st_ann" value="1"> Yes
                <input type="radio" name="employed_at_st_ann" value="0"> No
            </div>
        </div>

        <div class="mb-3">
            <label for="employment_details" class="form-label">If yes, provide employment details:</label>
            <textarea name="employment_details" class="form-control" rows="2" placeholder="Provide details of employment at St. Ann’s"></textarea>
        </div>

        <div class="mb-3">
            <label for="received_support" class="form-label">Has the beneficiary or family received any kind of support from St. Ann’s projects?</label>
            <div>
                <input type="radio" name="received_support" value="1"> Yes
                <input type="radio" name="received_support" value="0"> No
            </div>
        </div>

        <div class="mb-3">
            <label for="support_details" class="form-label">If yes, provide details of the support received:</label>
            <textarea name="support_details" class="form-control auto-resize-textarea" rows="2" placeholder="Provide details of support received"></textarea>
        </div>

        <div class="mb-3">
            <label for="govt_support" class="form-label">Does the beneficiary have access to Government or other support?</label>
            <div>
                <input type="radio" name="govt_support" value="1"> Yes
                <input type="radio" name="govt_support" value="0"> No
            </div>
        </div>

        <div class="mb-3">
            <label for="govt_support_nature" class="form-label">If yes, provide the nature of the support:</label>
            <textarea name="govt_support_nature" class="form-control auto-resize-textarea" rows="2" placeholder="Provide details of government or other support"></textarea>
        </div>
    </div>
</div>
