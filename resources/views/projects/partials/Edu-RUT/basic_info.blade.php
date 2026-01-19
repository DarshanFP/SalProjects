{{-- resources/views/projects/partials/Edu-RUT/basic_info.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Basic Information of Project’s Operational Area</h4>
    </div>
    <div class="card-body">

        <div class="mb-3">
            <label for="institution_type" class="form-label">Select One: (Institutional / Non-Institutional)</label>
            <select name="institution_type" id="institution_type" class="form-control select-input">
                <option value="" disabled selected>Select Type</option>
                <option value="Institutional">Institutional</option>
                <option value="Non-Institutional">Non-Institutional</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="group_type" class="form-label">Select One: (CHILDREN / YOUTH)</label>
            <select name="group_type" id="group_type" class="form-control select-input">
                <option value="" disabled selected>Select Group</option>
                <option value="CHILDREN">CHILDREN</option>
                <option value="YOUTH">YOUTH</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="category" class="form-label">Category (Rural / Urban / Tribal)</label>
            <select name="category" id="category" class="form-control select-input">
                <option value="" disabled selected>Select Category</option>
                <option value="rural">Rural</option>
                <option value="urban">Urban</option>
                <option value="tribal">Tribal</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="project_location" class="form-label">Project Location - Geographical Area</label>
            <textarea name="project_location" id="project_location" class="form-control select-input sustainability-textarea" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label for="sisters_work" class="form-label">Work of Sisters of St. Ann's in the project area over the years</label>
            <textarea name="sisters_work" id="sisters_work" class="form-control select-input sustainability-textarea" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label for="conditions" class="form-label">Prevailing Socio, Economic, and Cultural Conditions of the Beneficiaries</label>
            <textarea name="conditions" id="conditions" class="form-control select-input sustainability-textarea" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label for="problems" class="form-label">Problems Identified and Their Consequences</label>
            <textarea name="problems" id="problems" class="form-control select-input sustainability-textarea" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label for="need" class="form-label">Need of the Project</label>
            <textarea name="need" id="need" class="form-control select-input sustainability-textarea" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label for="criteria" class="form-label">Criteria for Selecting the Target Group</label>
            <textarea name="criteria" id="criteria" class="form-control select-input sustainability-textarea" rows="3"></textarea>
        </div>
    </div>
</div>
