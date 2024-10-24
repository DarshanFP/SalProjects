{{-- resources/views/projects/partials/CIC/basic_info.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Basic Information of Project</h4>
    </div>
    <div class="card-body">
        <!-- Number of beneficiaries served since inception -->
        <div class="mb-3">
            <label for="number_served_since_inception" class="form-label">Number of beneficiaries served by the Institution till date from the date of inception of the Crisis Centre</label>
            <input type="number" name="number_served_since_inception" id="number_served_since_inception" class="form-control" value="{{ old('number_served_since_inception') }}" style="background-color: #202ba3;">
        </div>

        <!-- Number of beneficiaries served in the previous year -->
        <div class="mb-3">
            <label for="number_served_previous_year" class="form-label">Number of Beneficiaries served in the previous year</label>
            <input type="number" name="number_served_previous_year" id="number_served_previous_year" class="form-control" value="{{ old('number_served_previous_year') }}" style="background-color: #202ba3;">
        </div>

        <!-- Beneficiary categories -->
        <div class="mb-3">
            <label for="beneficiary_categories" class="form-label">Explain the categories of the beneficiaries</label>
            <textarea name="beneficiary_categories" id="beneficiary_categories" class="form-control" rows="3" style="background-color: #202ba3;">{{ old('beneficiary_categories') }}</textarea>
        </div>

        <!-- Sisters' intervention -->
        <div class="mb-3">
            <label for="sisters_intervention" class="form-label">Intervention of the Sisters of St. Ann’s in this project over the years and the qualitative outcome / rehabilitation in the lives of the beneficiaries</label>
            <textarea name="sisters_intervention" id="sisters_intervention" class="form-control" rows="3" style="background-color: #202ba3;">{{ old('sisters_intervention') }}</textarea>
        </div>

        <!-- Beneficiary conditions -->
        <div class="mb-3">
            <label for="beneficiary_conditions" class="form-label">Prevailing Socio, Economic and cultural conditions of the Beneficiaries</label>
            <textarea name="beneficiary_conditions" id="beneficiary_conditions" class="form-control" rows="3" style="background-color: #202ba3;">{{ old('beneficiary_conditions') }}</textarea>
        </div>

        <!-- Beneficiary problems -->
        <div class="mb-3">
            <label for="beneficiary_problems" class="form-label">Problems encountered by the beneficiaries and their consequences</label>
            <textarea name="beneficiary_problems" id="beneficiary_problems" class="form-control" rows="3" style="background-color: #202ba3;">{{ old('beneficiary_problems') }}</textarea>
        </div>

        <!-- Institution challenges -->
        <div class="mb-3">
            <label for="institution_challenges" class="form-label">Problems / Challenges encountered by the Institution and its redressal till date</label>
            <textarea name="institution_challenges" id="institution_challenges" class="form-control" rows="3" style="background-color: #202ba3;">{{ old('institution_challenges') }}</textarea>
        </div>

        <!-- Support received -->
        <div class="mb-3">
            <label for="support_received" class="form-label">Mention the nature of support received from the Government / NGO’s / Philanthropists / Private sectors etc., if any in the previous year</label>
            <textarea name="support_received" id="support_received" class="form-control" rows="3" style="background-color: #202ba3;">{{ old('support_received') }}</textarea>
        </div>

        <!-- Need of the project -->
        <div class="mb-3">
            <label for="project_need" class="form-label">Need of the current project</label>
            <textarea name="project_need" id="project_need" class="form-control" rows="3" style="background-color: #202ba3;">{{ old('project_need') }}</textarea>
        </div>
    </div>
</div>
