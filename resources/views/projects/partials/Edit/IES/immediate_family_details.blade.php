{{-- resources/views/projects/partials/Edit/IES/immediate_family_details.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Details about Immediate Family Members</h4>
    </div>
    <div class="card-body">
        @if($project->iesImmediateFamilyDetails)
            @php
                $familyDetails = $project->iesImmediateFamilyDetails;
            @endphp
        @else
            @php
                $familyDetails = new \App\Models\OldProjects\IES\ProjectIESImmediateFamilyDetails();
            @endphp
        @endif

        <!-- Immediate Family Details -->
        <div class="form-group">
            <label><strong>Immediate Family Details</strong></label>
            <div class="form-check">
                <input type="checkbox" name="mother_expired" class="form-check-input" value="1" {{ old('mother_expired', $familyDetails->mother_expired) ? 'checked' : '' }}>
                <label class="form-check-label">Mother expired</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="father_expired" class="form-check-input" value="1" {{ old('father_expired', $familyDetails->father_expired) ? 'checked' : '' }}>
                <label class="form-check-label">Father expired</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="grandmother_support" class="form-check-input" value="1" {{ old('grandmother_support', $familyDetails->grandmother_support) ? 'checked' : '' }}>
                <label class="form-check-label">Grandmother supports family</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="grandfather_support" class="form-check-input" value="1" {{ old('grandfather_support', $familyDetails->grandfather_support) ? 'checked' : '' }}>
                <label class="form-check-label">Grandfather supports family</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="father_deserted" class="form-check-input" value="1" {{ old('father_deserted', $familyDetails->father_deserted) ? 'checked' : '' }}>
                <label class="form-check-label">Father deserted the family</label>
            </div>
            <div class="form-group">
                <label>Any other:</label>
                <input type="text" name="family_details_others" class="form-control" value="{{ old('family_details_others', $familyDetails->family_details_others) }}">
            </div>
        </div>

        <!-- Health of Father -->
        <div class="form-group">
            <label><strong>Health of Father</strong></label>
            <div class="form-check">
                <input type="checkbox" name="father_sick" class="form-check-input" value="1" {{ old('father_sick', $familyDetails->father_sick) ? 'checked' : '' }}>
                <label class="form-check-label">Chronically Sick</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="father_hiv_aids" class="form-check-input" value="1" {{ old('father_hiv_aids', $familyDetails->father_hiv_aids) ? 'checked' : '' }}>
                <label class="form-check-label">HIV/AIDS positive</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="father_disabled" class="form-check-input" value="1" {{ old('father_disabled', $familyDetails->father_disabled) ? 'checked' : '' }}>
                <label class="form-check-label">Disabled</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="father_alcoholic" class="form-check-input" value="1" {{ old('father_alcoholic', $familyDetails->father_alcoholic) ? 'checked' : '' }}>
                <label class="form-check-label">Alcoholic</label>
            </div>
            <div class="form-group">
                <label>Others:</label>
                <input type="text" name="father_health_others" class="form-control" value="{{ old('father_health_others', $familyDetails->father_health_others) }}">
            </div>
        </div>

        <!-- Health of Mother -->
        <div class="form-group">
            <label><strong>Health of Mother</strong></label>
            <div class="form-check">
                <input type="checkbox" name="mother_sick" class="form-check-input" value="1" {{ old('mother_sick', $familyDetails->mother_sick) ? 'checked' : '' }}>
                <label class="form-check-label">Chronically Sick</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="mother_hiv_aids" class="form-check-input" value="1" {{ old('mother_hiv_aids', $familyDetails->mother_hiv_aids) ? 'checked' : '' }}>
                <label class="form-check-label">HIV/AIDS positive</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="mother_disabled" class="form-check-input" value="1" {{ old('mother_disabled', $familyDetails->mother_disabled) ? 'checked' : '' }}>
                <label class="form-check-label">Disabled</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="mother_alcoholic" class="form-check-input" value="1" {{ old('mother_alcoholic', $familyDetails->mother_alcoholic) ? 'checked' : '' }}>
                <label class="form-check-label">Alcoholic</label>
            </div>
            <div class="form-group">
                <label>Others:</label>
                <input type="text" name="mother_health_others" class="form-control" value="{{ old('mother_health_others', $familyDetails->mother_health_others) }}">
            </div>
        </div>

        <!-- Residential Status -->
        <div class="form-group">
            <label><strong>Residential Status</strong></label>
            <div class="form-check">
                <input type="checkbox" name="own_house" class="form-check-input" value="1" {{ old('own_house', $familyDetails->own_house) ? 'checked' : '' }}>
                <label class="form-check-label">Own house</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="rented_house" class="form-check-input" value="1" {{ old('rented_house', $familyDetails->rented_house) ? 'checked' : '' }}>
                <label class="form-check-label">Rented house</label>
            </div>
            <div class="form-group">
                <label>Others:</label>
                <input type="text" name="residential_others" class="form-control" value="{{ old('residential_others', $familyDetails->residential_others) }}">
            </div>
        </div>

        <!-- Family Situation -->
        <div class="form-group">
            <label><strong>Family Situation</strong></label>
            <textarea name="family_situation" class="form-control sustainability-textarea" rows="3">{{ old('family_situation', $familyDetails->family_situation) }}</textarea>
        </div>

        <!-- Need of Project Assistance -->
        <div class="form-group">
            <label><strong>Need of Project Assistance</strong></label>
            <textarea name="assistance_need" class="form-control sustainability-textarea" rows="3">{{ old('assistance_need', $familyDetails->assistance_need) }}</textarea>
        </div>

        <!-- Financial Support -->
        <div class="form-group">
            <label><strong>Has the family of the beneficiary received financial support previously through St. Ann's projects?</strong></label>
            <div class="form-check">
                <input type="radio" name="received_support" class="form-check-input" value="1" {{ old('received_support', $familyDetails->received_support) == '1' ? 'checked' : '' }}>
                <label class="form-check-label">Yes</label>
            </div>
            <div class="form-check">
                <input type="radio" name="received_support" class="form-check-input" value="0" {{ old('received_support', $familyDetails->received_support) == '0' ? 'checked' : '' }}>
                <label class="form-check-label">No</label>
            </div>
            <div class="form-group">
                <label>If yes, give details:</label>
                <textarea name="support_details" class="form-control sustainability-textarea" rows="3">{{ old('support_details', $familyDetails->support_details) }}</textarea>
            </div>
        </div>

        <!-- Employment with St. Ann's -->
        <div class="form-group">
            <label><strong>Are the family members of the beneficiary employed with St. Ann's?</strong></label>
            <div class="form-check">
                <input type="radio" name="employed_with_stanns" class="form-check-input" value="1" {{ old('employed_with_stanns', $familyDetails->employed_with_stanns) == '1' ? 'checked' : '' }}>
                <label class="form-check-label">Yes</label>
            </div>
            <div class="form-check">
                <input type="radio" name="employed_with_stanns" class="form-check-input" value="0" {{ old('employed_with_stanns', $familyDetails->employed_with_stanns) == '0' ? 'checked' : '' }}>
                <label class="form-check-label">No</label>
            </div>
            <div class="form-group">
                <label>If yes, give details:</label>
                <textarea name="employment_details" class="form-control sustainability-textarea" rows="3">{{ old('employment_details', $familyDetails->employment_details) }}</textarea>
            </div>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    .form-control {
        color: white;
    }
</style>
