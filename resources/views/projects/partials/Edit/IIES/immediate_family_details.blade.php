{{-- resources/views/projects/partials/Edit/IIES/immediate_family_details.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Details about Immediate Family Members IIES</h4>
    </div>
    <div class="card-body">

        {{-- MOTHER EXPIRED --}}
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_mother_expired"
                class="form-check-input"
                value="1"
                {{-- If old() is set, use that; otherwise use DB. --}}
                {{ old('iies_mother_expired', optional($project->iiesImmediateFamilyDetails)->iies_mother_expired) ? 'checked' : '' }}
            >
            <label class="form-check-label">Mother expired</label>
        </div>

        {{-- FATHER EXPIRED --}}
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_father_expired"
                class="form-check-input"
                value="1"
                {{ old('iies_father_expired', optional($project->iiesImmediateFamilyDetails)->iies_father_expired) ? 'checked' : '' }}
            >
            <label class="form-check-label">Father expired</label>
        </div>

        {{-- GRANDMOTHER SUPPORT --}}
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_grandmother_support"
                class="form-check-input"
                value="1"
                {{ old('iies_grandmother_support', optional($project->iiesImmediateFamilyDetails)->iies_grandmother_support) ? 'checked' : '' }}
            >
            <label class="form-check-label">Grandmother supports family</label>
        </div>

        {{-- GRANDFATHER SUPPORT --}}
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_grandfather_support"
                class="form-check-input"
                value="1"
                {{ old('iies_grandfather_support', optional($project->iiesImmediateFamilyDetails)->iies_grandfather_support) ? 'checked' : '' }}
            >
            <label class="form-check-label">Grandfather supports family</label>
        </div>

        {{-- FATHER DESERTED --}}
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_father_deserted"
                class="form-check-input"
                value="1"
                {{ old('iies_father_deserted', optional($project->iiesImmediateFamilyDetails)->iies_father_deserted) ? 'checked' : '' }}
            >
            <label class="form-check-label">Father deserted the family</label>
        </div>

        {{-- ANY OTHER DETAILS --}}
        <div class="mt-2 form-group">
            <label>Any other:</label>
            <input
                type="text"
                name="iies_family_details_others"
                class="form-control"
                value="{{ old('iies_family_details_others', optional($project->iiesImmediateFamilyDetails)->iies_family_details_others) }}"
            >
        </div>

        <hr>

        <!-- HEALTH OF FATHER -->
        <div class="form-group">
            <label><strong>Health of Father</strong></label>
        </div>
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_father_sick"
                class="form-check-input"
                value="1"
                {{ old('iies_father_sick', optional($project->iiesImmediateFamilyDetails)->iies_father_sick) ? 'checked' : '' }}
            >
            <label class="form-check-label">Chronically Sick</label>
        </div>
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_father_hiv_aids"
                class="form-check-input"
                value="1"
                {{ old('iies_father_hiv_aids', optional($project->iiesImmediateFamilyDetails)->iies_father_hiv_aids) ? 'checked' : '' }}
            >
            <label class="form-check-label">HIV/AIDS positive</label>
        </div>
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_father_disabled"
                class="form-check-input"
                value="1"
                {{ old('iies_father_disabled', optional($project->iiesImmediateFamilyDetails)->iies_father_disabled) ? 'checked' : '' }}
            >
            <label class="form-check-label">Disabled</label>
        </div>
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_father_alcoholic"
                class="form-check-input"
                value="1"
                {{ old('iies_father_alcoholic', optional($project->iiesImmediateFamilyDetails)->iies_father_alcoholic) ? 'checked' : '' }}
            >
            <label class="form-check-label">Alcoholic</label>
        </div>
        <div class="mt-2 form-group">
            <label>Others (Father's Health):</label>
            <input
                type="text"
                name="iies_father_health_others"
                class="form-control"
                value="{{ old('iies_father_health_others', optional($project->iiesImmediateFamilyDetails)->iies_father_health_others) }}"
            >
        </div>

        <hr>

        <!-- HEALTH OF MOTHER -->
        <div class="form-group">
            <label><strong>Health of Mother</strong></label>
        </div>
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_mother_sick"
                class="form-check-input"
                value="1"
                {{ old('iies_mother_sick', optional($project->iiesImmediateFamilyDetails)->iies_mother_sick) ? 'checked' : '' }}
            >
            <label class="form-check-label">Chronically Sick</label>
        </div>
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_mother_hiv_aids"
                class="form-check-input"
                value="1"
                {{ old('iies_mother_hiv_aids', optional($project->iiesImmediateFamilyDetails)->iies_mother_hiv_aids) ? 'checked' : '' }}
            >
            <label class="form-check-label">HIV/AIDS positive</label>
        </div>
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_mother_disabled"
                class="form-check-input"
                value="1"
                {{ old('iies_mother_disabled', optional($project->iiesImmediateFamilyDetails)->iies_mother_disabled) ? 'checked' : '' }}
            >
            <label class="form-check-label">Disabled</label>
        </div>
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_mother_alcoholic"
                class="form-check-input"
                value="1"
                {{ old('iies_mother_alcoholic', optional($project->iiesImmediateFamilyDetails)->iies_mother_alcoholic) ? 'checked' : '' }}
            >
            <label class="form-check-label">Alcoholic</label>
        </div>
        <div class="mt-2 form-group">
            <label>Others (Mother's Health):</label>
            <input
                type="text"
                name="iies_mother_health_others"
                class="form-control"
                value="{{ old('iies_mother_health_others', optional($project->iiesImmediateFamilyDetails)->iies_mother_health_others) }}"
            >
        </div>

        <hr>

        <!-- RESIDENTIAL STATUS -->
        <div class="form-group">
            <label><strong>Residential Status</strong></label>
        </div>
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_own_house"
                class="form-check-input"
                value="1"
                {{ old('iies_own_house', optional($project->iiesImmediateFamilyDetails)->iies_own_house) ? 'checked' : '' }}
            >
            <label class="form-check-label">Own house</label>
        </div>
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_rented_house"
                class="form-check-input"
                value="1"
                {{ old('iies_rented_house', optional($project->iiesImmediateFamilyDetails)->iies_rented_house) ? 'checked' : '' }}
            >
            <label class="form-check-label">Rented house</label>
        </div>
        <div class="mt-2 form-group">
            <label>Others (Residence):</label>
            <input
                type="text"
                name="iies_residential_others"
                class="form-control"
                value="{{ old('iies_residential_others', optional($project->iiesImmediateFamilyDetails)->iies_residential_others) }}"
            >
        </div>

        <hr>

        <!-- FAMILY SITUATION -->
        <div class="form-group">
            <label><strong>Family Situation</strong></label>
            <textarea
                name="iies_family_situation"
                class="form-control sustainability-textarea"
                rows="3"
            >{{ old('iies_family_situation', optional($project->iiesImmediateFamilyDetails)->iies_family_situation) }}</textarea>
        </div>

        <!-- ASSISTANCE NEED -->
        <div class="form-group">
            <label><strong>Assistance Need</strong></label>
            <textarea
                name="iies_assistance_need"
                class="form-control sustainability-textarea"
                rows="3"
            >{{ old('iies_assistance_need', optional($project->iiesImmediateFamilyDetails)->iies_assistance_need) }}</textarea>
        </div>

        <!-- RECEIVED SUPPORT -->
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_received_support"
                class="form-check-input"
                value="1"
                {{ old('iies_received_support', optional($project->iiesImmediateFamilyDetails)->iies_received_support) ? 'checked' : '' }}
            >
            <label class="form-check-label">Family already receives some support?</label>
        </div>
        <div class="mt-2 form-group">
            <label>Support Details:</label>
            <textarea
                name="iies_support_details"
                class="form-control sustainability-textarea"
                rows="3"
            >{{ old('iies_support_details', optional($project->iiesImmediateFamilyDetails)->iies_support_details) }}</textarea>
        </div>

        <hr>

        <!-- EMPLOYMENT (ST.ANNS) -->
        <div class="form-check">
            <input
                type="checkbox"
                name="iies_employed_with_stanns"
                class="form-check-input"
                value="1"
                {{ old('iies_employed_with_stanns', optional($project->iiesImmediateFamilyDetails)->iies_employed_with_stanns) ? 'checked' : '' }}
            >
            <label class="form-check-label">Any family member employed with St.Ann's?</label>
        </div>
        <div class="mt-2 form-group">
            <label>Employment Details:</label>
            <textarea
                name="iies_employment_details"
                class="form-control sustainability-textarea"
                rows="3"
            >{{ old('iies_employment_details', optional($project->iiesImmediateFamilyDetails)->iies_employment_details) }}</textarea>
        </div>

    </div>
</div>
