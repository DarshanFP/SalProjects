{{-- resources/views/reports/monthly/ReportEdit.blade.php --}}
@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <!-- Display any validation errors -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <h5>Please fix the following errors:</h5>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Display any success messages -->
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Display any error messages -->
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form id="editReportForm" action="{{ route('monthly.report.update', $report->report_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT') <!-- Use PUT method for updating -->
                <input type="hidden" name="project_id" value="{{ $report->project_id }}">

                <!-- Basic Information Section -->
                <div class="mb-3 card">
                    <div class="card-header">
                        <h4 class="fp-text-center1">TRACKING -1- PROJECT</h4>
                        <h4 class="fp-text-center1">MONTHLY PROGRESS REPORT -Trial-</h4>
                    </div>
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="fp-text-margin">Basic Information</h4>
                        <span class="text-muted" style="color: #d0d7e1 !important;">Report ID: {{ $report->report_id ?? 'N/A' }}</span>
                    </div>

                    <div class="card-body">
                        <!-- Basic Information Fields -->
                        <div class="mb-3">
                            <label for="project_type" class="form-label">Project Type</label>
                            <input type="text" name="project_type" class="form-control readonly-input" value="{{ $project->project_type }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="project_id_display" class="form-label">Project ID</label>
                            <input type="text" name="project_id_display" class="form-control readonly-input" value="{{ $project->project_id }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="project_title" class="form-label">Title of the Project</label>
                            <input type="text" name="project_title" class="form-control readonly-input" value="{{ $project->project_title }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="place" class="form-label">Place</label>
                            <input type="text" name="place" class="form-control readonly-input" value="{{ $report->place }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="society_name" class="form-label">Name of the Society / Trust</label>
                            <input type="text" name="society_name" class="form-control readonly-input" value="{{ $report->society_name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="commencement_month_year" class="form-label">Month & Year of Commencement of the Project</label>
                            <input type="text" name="commencement_month_year" class="form-control readonly-input" value="{{ $project->commencement_month_year }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="in_charge" class="form-label">Sister/s In-Charge</label>
                            <input type="text" name="in_charge" class="form-control readonly-input" value="{{ $report->in_charge }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="total_beneficiaries" class="form-label">Total No. of Beneficiaries</label>
                            <input type="number" name="total_beneficiaries" class="form-control" style="background-color: #202ba3;" value="{{ old('total_beneficiaries', $report->total_beneficiaries) }}">
                        </div>
                        <div class="mb-3">
                            <label for="report_month_year" class="form-label">Reporting Month & Year</label>
                            <div class="d-flex">
                                <select name="report_month" id="report_month" class="form-control select-input me-2 @error('report_month') is-invalid @enderror" style="background-color: #202ba3;">
                                    <option value="" disabled>Select Month</option>
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}" {{ (old('report_month', $report->report_month) == $month) ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                                    @endforeach
                                </select>
                                <select name="report_year" id="report_year" class="form-control select-input @error('report_year') is-invalid @enderror" style="background-color: #202ba3;">
                                    <option value="" disabled>Select Year</option>
                                    @for ($year = date('Y'); $year >= 2020; $year--)
                                        <option value="{{ $year }}" {{ (old('report_year', $report->report_year) == $year) ? 'selected' : '' }}>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Include specific project type edit partials -->
                @if($project->project_type === 'Livelihood Development Projects')
                    @include('reports.monthly.partials.edit.LivelihoodAnnexure', ['report' => $report])

                @elseif($project->project_type === 'Institutional Ongoing Group Educational proposal')
                    @include('reports.monthly.partials.edit.institutional_ongoing_group', ['report' => $report])

                @elseif($project->project_type === 'Residential Skill Training Proposal 2')
                    @include('reports.monthly.partials.edit.residential_skill_training', ['report' => $report])

                @elseif($project->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER')
                    @include('reports.monthly.partials.edit.crisis_intervention_center', ['report' => $report])

                {{-- Add other project types as needed --}}
                @endif

                <!-- Include Objectives Section Partial -->
                @include('reports.monthly.partials.edit.objectives', ['report' => $report])

                <!-- Outlook Section -->
                <div id="outlook-container">
                    @foreach($report->outlooks as $index => $outlook)
                    <div class="mb-3 card outlook" data-index="{{ $index }}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>
                                <span class="badge bg-primary me-2">{{ $index + 1 }}</span>
                                Outlook {{ $index + 1 }}
                            </span>
                            <button type="button" class="btn btn-danger btn-sm remove-outlook" onclick="removeOutlook(this)">Remove</button>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="date[{{ $index }}]" class="form-label">Date</label>
                                <input type="date" name="date[{{ $index }}]" class="form-control" value="{{ old("date.$index", $outlook->date) }}" style="background-color: #202ba3;">
                            </div>
                            <div class="mb-3">
                                <label for="plan_next_month[{{ $index }}]" class="form-label">Action Plan for Next Month</label>
                                <textarea name="plan_next_month[{{ $index }}]" class="form-control auto-resize-textarea" rows="3" style="background-color: #202ba3;">{{ old("plan_next_month.$index", $outlook->plan_next_month) }}</textarea>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <button type="button" class="btn btn-primary" onclick="addOutlook()">Add More Outlook</button>

                <!-- Phase 4 (read-only): Optional note when report overview is 0 but project has non-zero sanctioned -->
                @if(!empty($showBudgetDiscrepancyNote))
                <div class="mb-3 alert alert-info" role="alert">
                    <i class="feather icon-info"></i>
                    Project sanctioned amount has been updated; consider updating the report overview if this report should reflect the current project sanctioned amount.
                </div>
                @endif

                <!-- Statements of Account Section -->
                @if($project->project_type === 'Individual - Livelihood Application')
                    @include('reports.monthly.partials.edit.statements_of_account.individual_livelihood', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'report' => $report, 'project' => $project])
                @elseif($project->project_type === 'Individual - Access to Health')
                    @include('reports.monthly.partials.edit.statements_of_account.individual_health', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'report' => $report, 'project' => $project])
                @elseif($project->project_type === 'Individual - Ongoing Educational support')
                    @include('reports.monthly.partials.edit.statements_of_account.individual_ongoing_education', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'report' => $report, 'project' => $project])
                @elseif($project->project_type === 'Individual - Initial - Educational support')
                    @include('reports.monthly.partials.edit.statements_of_account.individual_education', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'report' => $report, 'project' => $project])
                @elseif($project->project_type === 'Institutional Ongoing Group Educational proposal')
                    @include('reports.monthly.partials.edit.statements_of_account.institutional_education', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'report' => $report, 'project' => $project])
                @elseif($project->project_type === 'Development Projects')
                    @include('reports.monthly.partials.edit.statements_of_account.development_projects', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'report' => $report, 'project' => $project])
                @else
                    {{-- Fallback to generic for other project types --}}
                    @include('reports.monthly.partials.edit.statements_of_account', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'report' => $report, 'project' => $project])
                @endif

                <!-- Photos Section -->
                @include('reports.monthly.partials.edit.photos', ['report' => $report, 'groupedPhotos' => $groupedPhotos])

                <!-- Attachments Section -->
                @include('reports.monthly.partials.edit.attachments', ['report' => $report])

                <div class="d-flex justify-content-end mt-4 mb-4">
                    <button type="button" id="saveDraftBtn" class="btn btn-secondary me-2">
                        <i class="fas fa-save me-2"></i>Save as Draft
                    </button>
                    <button type="submit" id="updateReportBtn" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Save Draft Functionality for Edit Form
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const editForm = document.getElementById('editReportForm');

    // Handle "Save as Draft" button click
    if (saveDraftBtn && editForm) {
        saveDraftBtn.addEventListener('click', function(e) {
            try {
                e.preventDefault();

                // Remove required attributes temporarily
                const requiredFields = editForm.querySelectorAll('[required]');
                const removedRequired = [];
                requiredFields.forEach(field => {
                    if (field.hasAttribute('required')) {
                        removedRequired.push(field);
                        field.removeAttribute('required');
                    }
                });

                // Add hidden input to indicate draft save
                let draftInput = editForm.querySelector('input[name="save_as_draft"]');
                if (!draftInput) {
                    draftInput = document.createElement('input');
                    draftInput.type = 'hidden';
                    draftInput.name = 'save_as_draft';
                    draftInput.value = '1';
                    editForm.appendChild(draftInput);
                } else {
                    draftInput.value = '1';
                }

                // Enable all disabled fields
                const disabledFields = editForm.querySelectorAll('[disabled]');
                const enabledFields = [];
                disabledFields.forEach(field => {
                    if (field.disabled) {
                        enabledFields.push(field);
                        field.disabled = false;
                    }
                });

                // Show loading indicator
                saveDraftBtn.disabled = true;
                const originalText = saveDraftBtn.innerHTML;
                saveDraftBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving Draft...';

                // Submit form
                editForm.submit();

            } catch (error) {
                console.error('Draft save error:', error);
                saveDraftBtn.disabled = false;
                saveDraftBtn.innerHTML = 'Save as Draft';
                alert('An error occurred while saving the draft. Please try again.');
            }
        });
    }

    // Handle form submission
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const isDraftSave = this.querySelector('input[name="save_as_draft"]');
            if (isDraftSave && isDraftSave.value === '1') {
                return true; // Allow draft save
            }

            // For normal submission, validate
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return false;
            }
        });
    }
});
</script>

<!-- Include JavaScript for dynamic sections -->
<script>
    // Outlook Section
    function addOutlook() {
        const outlookContainer = document.getElementById('outlook-container');
        const index = outlookContainer.children.length;
        const newOutlookHtml = `
            <div class="mb-3 card outlook" data-index="${index}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <span class="badge bg-primary me-2">${index + 1}</span>
                        Outlook ${index + 1}
                    </span>
                    <button type="button" class="btn btn-danger btn-sm remove-outlook" onclick="removeOutlook(this)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="date[${index}]" class="form-label">Date</label>
                        <input type="date" name="date[${index}]" class="form-control" style="background-color: #202ba3;">
                    </div>
                    <div class="mb-3">
                        <label for="plan_next_month[${index}]" class="form-label">Action Plan for Next Month</label>
                        <textarea name="plan_next_month[${index}]" class="form-control auto-resize-textarea" rows="3" style="background-color: #202ba3;"></textarea>
                    </div>
                </div>
            </div>
        `;
        outlookContainer.insertAdjacentHTML('beforeend', newOutlookHtml);

        // Initialize auto-resize for new outlook textarea using global function
        const newOutlook = outlookContainer.lastElementChild;
        if (newOutlook && typeof initDynamicTextarea === 'function') {
            initDynamicTextarea(newOutlook);
        }

        reindexOutlooks();
        if (typeof window.syncReportPeriodToSections === 'function') {
            window.syncReportPeriodToSections();
        }
    }

    function removeOutlook(button) {
        const outlook = button.closest('.outlook');
        outlook.remove();
        reindexOutlooks();
    }

    /**
     * Reindexes all outlook entries after add/remove operations
     * Updates index badges, data-index attributes, and form field names/IDs
     * Ensures sequential numbering (1, 2, 3, ...) for all outlook entries
     *
     * @returns {void}
     */
    function reindexOutlooks() {
        const outlooks = document.querySelectorAll('.outlook');
        outlooks.forEach((outlook, index) => {
            // Update data-index attribute
            outlook.dataset.index = index;

            // Update badge and header text
            const headerSpan = outlook.querySelector('.card-header span');
            if (headerSpan) {
                headerSpan.innerHTML = `<span class="badge bg-primary me-2">${index + 1}</span>Outlook ${index + 1}`;
            }

            // Update name attributes for inputs
            const dateInput = outlook.querySelector('input[name^="date"]');
            const planTextarea = outlook.querySelector('textarea[name^="plan_next_month"]');

            if (dateInput) {
                dateInput.setAttribute('name', `date[${index}]`);
                dateInput.setAttribute('id', `date[${index}]`);
            }
            if (planTextarea) {
                planTextarea.setAttribute('name', `plan_next_month[${index}]`);
                planTextarea.setAttribute('id', `plan_next_month[${index}]`);
            }
        });
        updateOutlookRemoveButtons();
    }

    function updateOutlookRemoveButtons() {
        const outlooks = document.querySelectorAll('.outlook');
        outlooks.forEach((outlook, index) => {
            const removeButton = outlook.querySelector('.remove-outlook');
            if (removeButton) {
                if (index === 0) {
                    removeButton.classList.add('d-none');
                } else {
                    removeButton.classList.remove('d-none');
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        reindexOutlooks();
    });

    // Photo and Description Section
    function addPhoto() {
        const photosContainer = document.getElementById('photos-container');
        const currentPhotos = photosContainer.children.length;

        if (currentPhotos < 10) {
            const index = currentPhotos;
            const newPhotoHtml = `
                <div class="mb-3 photo-group" data-index="${index}">
                    <label for="photo_${index}" class="form-label">Photo ${index + 1}</label>
                    <input type="file" name="new_photos[]" class="mb-2 form-control" accept="image/jpeg, image/png" onchange="validateFileInput(this)" style="background-color: #202ba3;">
                    <textarea name="new_photo_descriptions[]" class="form-control auto-resize-textarea" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;"></textarea>
                    <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
                </div>
            `;
            photosContainer.insertAdjacentHTML('beforeend', newPhotoHtml);

            // Initialize auto-resize for new photo textarea using global function
            const newPhoto = photosContainer.lastElementChild;
            if (newPhoto && typeof initDynamicTextarea === 'function') {
                initDynamicTextarea(newPhoto);
            }

            updatePhotoLabels();
        } else {
            alert('You can upload a maximum of 10 photos.');
        }
    }

    function removePhoto(button) {
        const photoGroup = button.closest('.photo-group');
        photoGroup.remove();
        updatePhotoLabels();
    }

    function updatePhotoLabels() {
        const photoGroups = document.querySelectorAll('.photo-group');
        photoGroups.forEach((group, index) => {
            const label = group.querySelector('label');
            label.textContent = `Photo ${index + 1}`;
        });
    }

    function validateFileInput(input) {
        const allowedTypes = ['image/jpeg', 'image/png'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        const file = input.files[0];

        if (file) {
            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type. Please upload a JPEG or PNG image.');
                input.value = ''; // Clear the file input
                return false;
            }

            if (file.size > maxSize) {
                alert('File size exceeds 5MB. Please upload a smaller image.');
                input.value = ''; // Clear the file input
                return false;
            }
        }

        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        updatePhotoLabels();
    });
</script>

<style>
    .table th, .table td {
        vertical-align: middle;
        text-align: center;
        padding: 0;
    }

    .table th {
        white-space: normal;
    }

    .table td input {
        width: 100%;
        box-sizing: border-box;
        -moz-appearance: textfield;
        padding: 0.375rem 0.75rem;
    }

    .table td input::-webkit-outer-spin-button,
    .table td input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .table-container {
        overflow-x: auto;
    }

    .fp-text-center1 {
        text-align: center;
        margin-bottom: 15px;
    }

    .fp-text-margin {
        margin-bottom: 15px;
    }
</style>
@endsection
