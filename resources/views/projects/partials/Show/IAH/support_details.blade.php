{{-- resources/views/projects/partials/Edit/IAH/support_details.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Support Details</h4>
    </div>
    <div class="card-body">
        @if($project->iahSupportDetails)
            @php
                $supportDetails = $project->iahSupportDetails;
            @endphp
        @else
            @php
                $supportDetails = new \App\Models\OldProjects\IAH\ProjectIAHSupportDetails();
            @endphp
        @endif

        <!-- Are the family members employed with St. Ann’s? -->
        <div class="mb-3">
            <label for="employed_at_st_ann" class="form-label">Are the family members employed with St. Ann’s?</label>
            <div>
                <input type="radio" name="employed_at_st_ann" value="1" {{ old('employed_at_st_ann', $supportDetails->employed_at_st_ann) == '1' ? 'checked' : '' }}> Yes
                <input type="radio" name="employed_at_st_ann" value="0" {{ old('employed_at_st_ann', $supportDetails->employed_at_st_ann) == '0' ? 'checked' : '' }}> No
            </div>
        </div>

        <!-- If yes, provide employment details -->
        <div class="mb-3">
            <label for="employment_details" class="form-label">If yes, provide employment details:</label>
            <textarea name="employment_details" class="form-control" rows="2" placeholder="Provide details of employment at St. Ann’s">{{ old('employment_details', $supportDetails->employment_details) }}</textarea>
        </div>

        <!-- Has the beneficiary or family received any kind of support from St. Ann’s projects? -->
        <div class="mb-3">
            <label for="received_support" class="form-label">Has the beneficiary or family received any kind of support from St. Ann’s projects?</label>
            <div>
                <input type="radio" name="received_support" value="1" {{ old('received_support', $supportDetails->received_support) == '1' ? 'checked' : '' }}> Yes
                <input type="radio" name="received_support" value="0" {{ old('received_support', $supportDetails->received_support) == '0' ? 'checked' : '' }}> No
            </div>
        </div>

        <!-- If yes, provide details of the support received -->
        <div class="mb-3">
            <label for="support_details" class="form-label">If yes, provide details of the support received:</label>
            <textarea name="support_details" class="form-control" rows="2" placeholder="Provide details of support received">{{ old('support_details', $supportDetails->support_details) }}</textarea>
        </div>

        <!-- Does the beneficiary have access to Government or other support? -->
        <div class="mb-3">
            <label for="govt_support" class="form-label">Does the beneficiary have access to Government or other support?</label>
            <div>
                <input type="radio" name="govt_support" value="1" {{ old('govt_support', $supportDetails->govt_support) == '1' ? 'checked' : '' }}> Yes
                <input type="radio" name="govt_support" value="0" {{ old('govt_support', $supportDetails->govt_support) == '0' ? 'checked' : '' }}> No
            </div>
        </div>

        <!-- If yes, provide the nature of the support -->
        <div class="mb-3">
            <label for="govt_support_nature" class="form-label">If yes, provide the nature of the support:</label>
            <textarea name="govt_support_nature" class="form-control" rows="2" placeholder="Provide details of government or other support">{{ old('govt_support_nature', $supportDetails->govt_support_nature) }}</textarea>
        </div>
    </div>
</div>
