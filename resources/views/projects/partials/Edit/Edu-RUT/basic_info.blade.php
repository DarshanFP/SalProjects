{{-- resources/views/projects/partials/Edit/Edu-RUT/basic_info.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Basic Information of Project’s Operational Area</h4>
    </div>
    <div class="card-body">
        @if($project->eduRUTBasicInfo)
            @php
                $basicInfo = $project->eduRUTBasicInfo;
            @endphp
        @else
            @php
                // Initialize an empty object to prevent errors in case eduRUTBasicInfo doesn't exist
                $basicInfo = new \App\Models\OldProjects\ProjectEduRUTBasicInfo();
            @endphp
        @endif

        <!-- Institution Type -->
        <div class="mb-3">
            <label for="institution_type" class="form-label">Select One: (Institutional / Non-Institutional)</label>
            <select name="institution_type" id="institution_type" class="form-control select-input">
                <option value="" disabled {{ !$basicInfo->institution_type ? 'selected' : '' }}>Select Type</option>
                <option value="Institutional" {{ $basicInfo->institution_type == 'Institutional' ? 'selected' : '' }}>Institutional</option>
                <option value="Non-Institutional" {{ $basicInfo->institution_type == 'Non-Institutional' ? 'selected' : '' }}>Non-Institutional</option>
            </select>
        </div>

        <!-- Group Type -->
        <div class="mb-3">
            <label for="group_type" class="form-label">Select One: (CHILDREN / YOUTH)</label>
            <select name="group_type" id="group_type" class="form-control select-input">
                <option value="" disabled {{ !$basicInfo->group_type ? 'selected' : '' }}>Select Group</option>
                <option value="CHILDREN" {{ $basicInfo->group_type == 'CHILDREN' ? 'selected' : '' }}>CHILDREN</option>
                <option value="YOUTH" {{ $basicInfo->group_type == 'YOUTH' ? 'selected' : '' }}>YOUTH</option>
            </select>
        </div>

        <!-- Category -->
        <div class="mb-3">
            <label for="category" class="form-label">Category (Rural / Urban / Tribal)</label>
            <select name="category" id="category" class="form-control select-input">
                <option value="" disabled {{ !$basicInfo->category ? 'selected' : '' }}>Select Category</option>
                <option value="rural" {{ $basicInfo->category == 'rural' ? 'selected' : '' }}>Rural</option>
                <option value="urban" {{ $basicInfo->category == 'urban' ? 'selected' : '' }}>Urban</option>
                <option value="tribal" {{ $basicInfo->category == 'tribal' ? 'selected' : '' }}>Tribal</option>
            </select>
        </div>

        <!-- Project Location -->
        <div class="mb-3">
            <label for="project_location" class="form-label">Project Location - Geographical Area</label>
            <textarea name="project_location" id="project_location" class="form-control sustainability-textarea" rows="3">{{ old('project_location', $basicInfo->project_location) }}</textarea>
        </div>

        <!-- Sisters' Work -->
        <div class="mb-3">
            <label for="sisters_work" class="form-label">Work of Sisters of St. Ann's in the project area over the years</label>
            <textarea name="sisters_work" id="sisters_work" class="form-control sustainability-textarea" rows="3">{{ old('sisters_work', $basicInfo->sisters_work) }}</textarea>
        </div>

        <!-- Conditions -->
        <div class="mb-3">
            <label for="conditions" class="form-label">Prevailing Socio, Economic, and Cultural Conditions of the Beneficiaries</label>
            <textarea name="conditions" id="conditions" class="form-control sustainability-textarea" rows="3">{{ old('conditions', $basicInfo->conditions) }}</textarea>
        </div>

        <!-- Problems -->
        <div class="mb-3">
            <label for="problems" class="form-label">Problems Identified and Their Consequences</label>
            <textarea name="problems" id="problems" class="form-control sustainability-textarea" rows="3">{{ old('problems', $basicInfo->problems) }}</textarea>
        </div>

        <!-- Need -->
        <div class="mb-3">
            <label for="need" class="form-label">Need of the Project</label>
            <textarea name="need" id="need" class="form-control sustainability-textarea" rows="3">{{ old('need', $basicInfo->need) }}</textarea>
        </div>

        <!-- Criteria -->
        <div class="mb-3">
            <label for="criteria" class="form-label">Criteria for Selecting the Target Group</label>
            <textarea name="criteria" id="criteria" class="form-control sustainability-textarea" rows="3">{{ old('criteria', $basicInfo->criteria) }}</textarea>
        </div>
    </div>
</div>
