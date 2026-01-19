{{-- resources/views/reports/monthly/ReportAll.blade.php --}}
@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <form id="createReportForm" action="{{ route('monthly.report.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->project_id }}">

                <!-- Basic Information Section -->
                <div class="mb-3 card">
                    <div class="card-header">
                        <h4 class="fp-text-center1">TRACKING -- PROJECT</h4>
                        <h4 class="fp-text-center1">MONTHLY PROGRESS REPORT</h4>
                    </div>
                    <div class="card-header">
                        <h4 class="fp-text-margin">Basic Information</h4>
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
                            <input type="text" name="place" class="form-control readonly-input" value="{{ $user->center }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="society_name" class="form-label">Name of the Society / Trust</label>
                            <input type="text" name="society_name" class="form-control readonly-input" value="{{ $user->society_name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="commencement_month_year" class="form-label">Month & Year of Commencement of the Project</label>
                            <input type="text" name="commencement_month_year" class="form-control readonly-input" value="{{ $project->commencement_month_year }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="in_charge" class="form-label">Sister/s In-Charge</label>
                            <input type="text" name="in_charge" class="form-control readonly-input" value="{{ $user->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="total_beneficiaries" class="form-label">Total No. of Beneficiaries</label>
                            <input type="number" name="total_beneficiaries" class="form-control" style="background-color: #202ba3;">
                        </div>
                        <div class="mb-3">
                            <label for="report_month_year" class="form-label">Reporting Month & Year</label>
                            <div class="d-flex">
                                <select name="report_month" id="report_month" class="form-control select-input me-2 @error('report_month') is-invalid @enderror" style="background-color: #202ba3;">
                                    <option value="" disabled selected>Select Month</option>
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}" {{ old('report_month') == $month ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                                    @endforeach
                                </select>
                                <select name="report_year" id="report_year" class="form-control select-input @error('report_year') is-invalid @enderror" style="background-color: #202ba3;">
                                    <option value="" disabled selected>Select Year</option>
                                    @for ($year = date('Y'); $year >= 2020; $year--)
                                        <option value="{{ $year }}" {{ old('report_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                                 <!-- Include specific project type partials -->

                @if($project->project_type === 'Livelihood Development Projects')
                    @include('reports.monthly.partials.create.LivelihoodAnnexure', ['report' => $project])

                @elseif($project->project_type === 'Institutional Ongoing Group Educational proposal')
                    @include('reports.monthly.partials.create.institutional_ongoing_group', ['report' => $project])

                @elseif($project->project_type === 'Residential Skill Training Proposal 2')
                    @include('reports.monthly.partials.create.residential_skill_training', ['report' => $project])

                @elseif($project->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER')
                    @include('reports.monthly.partials.create.crisis_intervention_center', ['report' => $project])



{{--
                @elseif($project->project_type === 'CHILD CARE INSTITUTION')
                    @include('reports.monthly.partials.create.child_care_institution', ['report' => $project])

                @elseif($project->project_type === 'Rural-Urban-Tribal')
                    @include('reports.monthly.partials.rural_urban_tribal', ['report' => $project])

                @elseif($project->project_type === 'NEXT PHASE - DEVELOPMENT PROPOSAL')
                    @include('reports.monthly.partials.next_phase_development', ['report' => $project])


                @elseif($project->project_type === 'Individual - Ongoing Educational support')
                    @include('reports.monthly.partials.individual_ongoing_educational', ['report' => $project])

                @elseif($project->project_type === 'Individual - Livelihood Application')
                    @include('reports.monthly.partials.individual_livelihood', ['report' => $project])

                @elseif($project->project_type === 'Individual - Access to Health')
                    @include('reports.monthly.partials.individual_access_health', ['report' => $project])

                @elseif($project->project_type === 'Individual - Initial - Educational support')
                    @include('reports.monthly.partials.individual_initial_educational', ['report' => $project]) --}}


                    @endif

                    <!-- already in ReportAll.blade.php -->
                {{-- @elseif($project->project_type === 'Development Projects')
                    @include('reports.monthly.partials.development_projects', ['report' => $project]) --}}

                 <!-- Include project type Partial Ends -->


                <!-- Include Objectives Section Partial -->
                @include('reports.monthly.partials.create.objectives')

                <!-- Outlook Section -->
                <div id="outlook-container">
                    @foreach(old('date', ['']) as $index => $value)
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
                                <input type="date" name="date[{{ $index }}]" class="form-control" value="{{ $value }}" style="background-color: #202ba3;">
                            </div>
                            <div class="mb-3">
                                <label for="plan_next_month[{{ $index }}]" class="form-label">Action Plan for Next Month</label>
                                <textarea name="plan_next_month[{{ $index }}]" class="form-control auto-resize-textarea" rows="3" style="background-color: #202ba3;">{{ old("plan_next_month.$index") }}</textarea>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <button type="button" class="btn btn-primary" onclick="addOutlook()">Add More Outlook</button>

                <!-- Statements of Account Section  -->
                @if($project->project_type === 'Individual - Initial - Educational support')
                    @include('reports.monthly.partials.statements_of_account.individual_education', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'project' => $project])
                @elseif($project->project_type === 'Individual - Ongoing Educational support')
                    @include('reports.monthly.partials.statements_of_account.individual_ongoing_education', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'project' => $project])
                @elseif($project->project_type === 'Individual - Livelihood Application')
                    @include('reports.monthly.partials.statements_of_account.individual_livelihood', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'project' => $project])
                @elseif($project->project_type === 'Individual - Access to Health')
                    @include('reports.monthly.partials.statements_of_account.individual_health', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'project' => $project])
                @elseif($project->project_type === 'Institutional - Initial - Educational support')
                    @include('reports.monthly.partials.statements_of_account.institutional_education', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'project' => $project])
                @elseif($project->project_type === 'Development Projects')
                    @include('reports.monthly.partials.statements_of_account.development_projects', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'project' => $project])
                @else
                    {{-- Fallback to generic for other project types --}}
                    @include('reports.monthly.partials.create.statements_of_account', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses, 'project' => $project])
                @endif


                <!-- Photos Section -->
                {{-- <div class="mb-3 card">
                    <div class="card-header">
                        <h4>5. Photos</h4>
                    </div>
                    <div class="card-body">
                        <div id="photos-container">
                            @foreach(old('photo_descriptions', ['']) as $index => $value)
                            <div class="mb-3 photo-group" data-index="{{ $index }}">
                                <label for="photo_{{ $index }}" class="form-label">Photo {{ $index + 1 }}</label>
                                <input type="file" name="photos[]" class="mb-2 form-control" accept="image/*" onchange="checkFileSize(this)" style="background-color: #202ba3;">
                                <textarea name="photo_descriptions[]" class="form-control auto-resize-textarea" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;">{{ $value }}</textarea>
                                <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="mt-3 btn btn-primary" onclick="addPhoto()">Add More Photo</button>
                    </div>
                </div> --}}
                {{-- <div class="mb-3 card">
                    <div class="card-header">
                        <h4>5. Photos</h4>
                    </div>
                    <div class="card-body">
                        <div id="photos-container">
                            @foreach(old('photo_descriptions', ['']) as $index => $value)
                            <div class="mb-3 photo-group" data-index="{{ $index }}">
                                <label for="photo_{{ $index }}" class="form-label">Photo {{ $index + 1 }}</label>
                                <input type="file" name="photos[]" class="mb-2 form-control" accept="image/jpeg, image/png" onchange="validateFileInput(this)" style="background-color: #202ba3;">
                                <textarea name="photo_descriptions[]" class="form-control auto-resize-textarea" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;">{{ $value }}</textarea>
                                <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="mt-3 btn btn-primary" onclick="addPhoto()">Add More Photo</button>
                    </div>
                </div> --}}
                {{-- <div class="mb-3 card">
                    <div class="card-header">
                        <h4>5. Photos</h4>
                    </div>
                    <div class="card-body">
                        <div id="photos-container">
                            @foreach(old('photo_descriptions', ['']) as $index => $value)
                            <div class="mb-3 photo-group" data-index="{{ $index }}">
                                <label for="photo_{{ $index }}" class="form-label">Photo {{ $index + 1 }}</label>
                                <input type="file" name="photos[]" class="mb-2 form-control" accept="image/jpeg, image/png" onchange="validateFileInput(this)" style="background-color: #202ba3;">
                                <textarea name="photo_descriptions[]" class="form-control auto-resize-textarea" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;">{{ $value }}</textarea>
                                <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="mt-3 btn btn-primary" onclick="addPhoto()">Add More Photo</button>
                    </div>
                </div> --}}

                @include('reports.monthly.partials.create.photos')


                @include('reports.monthly.partials.create.attachments')

                <div class="mt-4 mb-4 d-flex justify-content-end">
                    <button type="button" id="saveDraftBtn" class="btn btn-secondary me-2">
                        <i class="fas fa-save me-2"></i>Save as Draft
                    </button>
                    <button type="submit" id="submitReportBtn" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Submit Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Objectives Section JavaScript Partial -->

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
    }

    function removeOutlook(button) {
        const outlook = button.closest('.outlook');
        outlook.remove();
        reindexOutlooks();
    }

    /**
     * Reindex all outlook sections after add/remove operations
     * Updates index badges, data-index attributes, and form field names
     * Ensures sequential numbering (1, 2, 3, ...) for all outlook items
     */
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

//     function addPhoto() {
//     const photosContainer = document.getElementById('photos-container');
//     const currentPhotos = photosContainer.children.length;

//     if (currentPhotos < 10) {
//         const index = currentPhotos;
//         const newPhotoHtml = `
//             <div class="mb-3 photo-group" data-index="${index}">
//                 <label for="photo_${index}" class="form-label">Photo ${index + 1}</label>
//                 <input type="file" name="photos[]" class="mb-2 form-control" accept="image/jpeg, image/png" onchange="validateFileInput(this)" style="background-color: #202ba3;">
//                 <textarea name="photo_descriptions[]" class="form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;"></textarea>
//                 <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
//             </div>
//         `;
//         photosContainer.insertAdjacentHTML('beforeend', newPhotoHtml);
//         updatePhotoLabels();
//     } else {
//         alert('You can upload a maximum of 10 photos.');
//     }
// }

// function removePhoto(button) {
//     const photoGroup = button.closest('.photo-group');
//     photoGroup.remove();
//     updatePhotoLabels();
// }

// function updatePhotoLabels() {
//     const photoGroups = document.querySelectorAll('.photo-group');
//     photoGroups.forEach((group, index) => {
//         const label = group.querySelector('label');
//         label.textContent = `Photo ${index + 1}`;
//     });
// }

// function validateFileInput(input) {
//     const allowedTypes = ['image/jpeg', 'image/png'];
//     const maxSize = 5 * 1024 * 1024; // 5MB

//     const file = input.files[0];

//     if (file) {
//         if (!allowedTypes.includes(file.type)) {
//             alert('Invalid file type. Please upload a JPEG or PNG image.');
//             input.value = ''; // Clear the file input
//             return false;
//         }

//         if (file.size > maxSize) {
//             alert('File size exceeds 5MB. Please upload a smaller image.');
//             input.value = ''; // Clear the file input
//             return false;
//         }
//     }

//     return true;
// }

// document.addEventListener('DOMContentLoaded', function() {
//     updatePhotoLabels();
// });

//PDF issue unsolved in below code

// function addPhoto() {
//     const photosContainer = document.getElementById('photos-container');
//     const currentPhotos = photosContainer.children.length;

//     if (currentPhotos < 10) {
//         const index = currentPhotos;
//         const newPhotoHtml = `
//             <div class="mb-3 photo-group" data-index="${index}">
//                 <label for="photo_${index}" class="form-label">Photo ${index + 1}</label>
//                 <input type="file" name="photos[]" class="mb-2 form-control" accept="image/jpeg, image/png" onchange="validateFileInput(this)" style="background-color: #202ba3;">
//                 <textarea name="photo_descriptions[]" class="form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;"></textarea>
//                 <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
//             </div>
//         `;
//         photosContainer.insertAdjacentHTML('beforeend', newPhotoHtml);
//         updatePhotoLabels();
//     } else {
//         alert('You can upload a maximum of 10 photos.');
//     }
// }

// function removePhoto(button) {
//     const photoGroup = button.closest('.photo-group');
//     photoGroup.remove();
//     updatePhotoLabels();
// }

// function updatePhotoLabels() {
//     const photoGroups = document.querySelectorAll('.photo-group');
//     photoGroups.forEach((group, index) => {
//         const label = group.querySelector('label');
//         label.textContent = `Photo ${index + 1}`;
//     });
// }

// function validateFileInput(input) {
//     // Define allowed image types
//     const allowedImageTypes = ['image/jpeg', 'image/png'];

//     // Get the file from the input
//     const file = input.files[0];

//     if (file) {
//         // Check if the file type is not allowed
//         if (!allowedImageTypes.includes(file.type)) {
//             // Specifically handle the case where the file is a PDF
//             if (file.type === 'application/pdf') {
//                 alert('PDF files are not allowed. Please upload a JPEG or PNG image.');
//                 input.value = ''; // Clear the file input
//                 return false;
//             } else {
//                 alert('Invalid file type. Please upload a JPEG or PNG image.');
//                 input.value = ''; // Clear the file input
//                 return false;
//             }
//         }

//         // Define maximum file size (5MB)
//         const maxSize = 5 * 1024 * 1024; // 5MB

//         // Check if the file size exceeds the limit
//         if (file.size > maxSize) {
//             alert('File size exceeds 5MB. Please upload a smaller image.');
//             input.value = ''; // Clear the file input
//             return false;
//         }
//     }

//     return true;
// }

// // Event listener to run validation on document load (if needed)
// document.addEventListener('DOMContentLoaded', function() {
//     updatePhotoLabels();
// });function addPhoto() {


// Function to validate file input
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

// Function to add a new photo input
// function addPhoto() {
//     const photosContainer = document.getElementById('photos-container');
//     const currentPhotos = photosContainer.children.length;

//     if (currentPhotos < 10) {
//         const index = currentPhotos;
//         const newPhotoHtml = `
//             <div class="mb-3 photo-group" data-index="${index}">
//                 <label for="photo_${index}" class="form-label">Photo ${index + 1}</label>
//                 <input type="file" name="photos[]" class="mb-2 form-control" accept="image/jpeg, image/png" onchange="validateFileInput(this)" style="background-color: #202ba3;">
//                 <textarea name="photo_descriptions[]" class="form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;"></textarea>
//                 <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
//             </div>
//         `;
//         photosContainer.insertAdjacentHTML('beforeend', newPhotoHtml);
//         updatePhotoLabels();
//     } else {
//         alert('You can upload a maximum of 10 photos.');
//     }
// }

// // Function to remove a photo input
// function removePhoto(button) {
//     const photoGroup = button.closest('.photo-group');
//     photoGroup.remove();
//     updatePhotoLabels();
// }

// // Function to update photo labels
// function updatePhotoLabels() {
//     const photoGroups = document.querySelectorAll('.photo-group');
//     photoGroups.forEach((group, index) => {
//         const label = group.querySelector('label');
//         label.textContent = `Photo ${index + 1}`;
//     });
// }

document.addEventListener('DOMContentLoaded', function() {
    // updatePhotoLabels(); // Removed - function is commented out and not needed

    // Save Draft Functionality
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const createForm = document.getElementById('createReportForm');

    // Handle "Save as Draft" button click
    if (saveDraftBtn && createForm) {
        saveDraftBtn.addEventListener('click', function(e) {
            try {
                e.preventDefault();

                // Remove required attributes temporarily to allow submission
                const requiredFields = createForm.querySelectorAll('[required]');
                const removedRequired = [];
                requiredFields.forEach(field => {
                    if (field.hasAttribute('required')) {
                        removedRequired.push(field);
                        field.removeAttribute('required');
                    }
                });

                // Add hidden input to indicate draft save
                let draftInput = createForm.querySelector('input[name="save_as_draft"]');
                if (!draftInput) {
                    draftInput = document.createElement('input');
                    draftInput.type = 'hidden';
                    draftInput.name = 'save_as_draft';
                    draftInput.value = '1';
                    createForm.appendChild(draftInput);
                } else {
                    draftInput.value = '1';
                }

                // Enable all disabled fields before submission to ensure their values are included
                const disabledFields = createForm.querySelectorAll('[disabled]');
                const enabledFields = [];
                disabledFields.forEach(field => {
                    if (field.disabled) {
                        enabledFields.push(field);
                        field.disabled = false;
                    }
                });

                // Show all hidden sections temporarily to ensure values are submitted
                const hiddenSections = createForm.querySelectorAll('[style*="display: none"]');
                hiddenSections.forEach(section => {
                    section.style.display = '';
                });

                // Show loading indicator
                saveDraftBtn.disabled = true;
                const originalText = saveDraftBtn.innerHTML;
                saveDraftBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving Draft...';

                // Submit form
                createForm.submit();

                // Note: If form submission fails, we won't reach here
                // But if it does, restore button state after timeout
                setTimeout(() => {
                    saveDraftBtn.disabled = false;
                    saveDraftBtn.innerHTML = originalText;

                    // Restore required attributes (in case of validation error)
                    removedRequired.forEach(field => {
                        field.setAttribute('required', 'required');
                    });

                    // Restore disabled state
                    enabledFields.forEach(field => {
                        field.disabled = true;
                    });
                }, 5000);

            } catch (error) {
                console.error('Draft save error:', error);
                saveDraftBtn.disabled = false;
                saveDraftBtn.innerHTML = 'Save as Draft';
                alert('An error occurred while saving the draft. Please try again.');
            }
        });
    }

    // Handle form submission to check if it's a draft save
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            // Check if this is a draft save (bypass validation)
            const isDraftSave = this.querySelector('input[name="save_as_draft"]');
            if (isDraftSave && isDraftSave.value === '1') {
                // Allow draft save without validation
                return true;
            }

            // For normal submission, allow HTML5 validation
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
