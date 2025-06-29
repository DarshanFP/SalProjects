@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <!-- Display any validation errors -->
            @if ($errors->any())
                <div class="alert alert-danger">
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

            <form action="{{ route('monthly.report.store') }}" method="POST" enctype="multipart/form-data" id="reportForm">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->project_id }}">

                <!-- Basic Information Section -->
                <div class="mb-3 card">
                    <div class="card-header">
                        <h4 class="fp-text-center1">TRACKING PROJECT</h4>
                        <h4 class="fp-text-center1">MONTHLY PROGRESS REPORT (common)</h4>
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

                <!-- Include Objectives Section Partial -->
                @include('reports.monthly.partials.create.objectives')

                <!-- Outlook Section -->
                <div id="outlook-container">
                    @foreach(old('date', ['']) as $index => $value)
                    <div class="mb-3 card outlook" data-index="{{ $index }}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            Outlook {{ $index + 1 }}
                            <button type="button" class="btn btn-danger btn-sm remove-outlook" onclick="removeOutlook(this)">Remove</button>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="date[{{ $index }}]" class="form-label">Date</label>
                                <input type="date" name="date[{{ $index }}]" class="form-control" value="{{ $value }}" style="background-color: #202ba3;">
                            </div>
                            <div class="mb-3">
                                <label for="plan_next_month[{{ $index }}]" class="form-label">Action Plan for Next Month</label>
                                <textarea name="plan_next_month[{{ $index }}]" class="form-control" rows="3" style="background-color: #202ba3;">{{ old("plan_next_month.$index") }}</textarea>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-primary" onclick="addOutlook()">Add More Outlook</button>

                <!-- Statements of Account Section -->
                @include('reports.monthly.partials.create.statements_of_account', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses])


                @if($project->project_type === 'Livelihood Development Projects')
                    @include('reports.monthly.partials.LivelihoodAnnexure', ['report' => $project])
                @endif

                <!-- Photos Section -->
                <div class="mb-3 card">
                    <div class="card-header">
                        <h4>5. Photos</h4>
                    </div>
                    <div class="card-body">
                        <div id="photos-container">
                            @foreach(old('photo_descriptions', ['']) as $index => $value)
                            <div class="mb-3 photo-group" data-index="{{ $index }}">
                                <label for="photo_{{ $index }}" class="form-label">Photo {{ $index + 1 }}</label>
                                <input type="file" name="photos[]" class="mb-2 form-control" accept="image/*" onchange="checkFileSize(this)" style="background-color: #202ba3;">
                                <textarea name="photo_descriptions[]" class="form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;">{{ $value }}</textarea>
                                <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="mt-3 btn btn-primary" onclick="addPhoto()">Add More Photo</button>
                    </div>
                </div>

                <!-- Attachments Section -->
                @include('reports.monthly.partials.create.attachments')

                <button type="submit" class="btn btn-primary me-2">Submit Report</button>
            </form>
        </div>
    </div>
</div>

<!-- Include Objectives Section JavaScript Partial -->

<script>
    // Photo and Description Section
    function addPhoto() {
        const photosContainer = document.getElementById('photos-container');
        const currentPhotos = photosContainer.children.length;

        if (currentPhotos < 10) {
            const index = currentPhotos;
            const newPhotoHtml = `
                <div class="mb-3 photo-group" data-index="${index}">
                    <label for="photo_${index}" class="form-label">Photo ${index + 1}</label>
                    <input type="file" name="photos[]" class="mb-2 form-control" accept="image/*" onchange="checkFileSize(this)" style="background-color: #202ba3;">
                    <textarea name="photo_descriptions[]" class="form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;"></textarea>
                    <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
                </div>
            `;
            photosContainer.insertAdjacentHTML('beforeend', newPhotoHtml);
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

    function checkFileSize(input) {
        const file = input.files[0];
        if (file && file.size > 5 * 1024 * 1024) { // 5 MB
            alert('Each photo must be less than 5 MB.');
            input.value = '';
        }
    }

    // Form submission debugging
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('#reportForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('Form submission attempted');

                // Check form data size
                const formData = new FormData(form);
                let totalSize = 0;
                for (let [key, value] of formData.entries()) {
                    if (value instanceof File) {
                        totalSize += value.size;
                    }
                }
                console.log('Total form data size:', totalSize, 'bytes');

                // Check if required fields are present
                const projectObjectiveIds = document.querySelectorAll('input[name^="project_objective_id"]');
                const projectActivityIds = document.querySelectorAll('input[name^="project_activity_id"]');

                console.log('Project objective IDs found:', projectObjectiveIds.length);
                console.log('Project activity IDs found:', projectActivityIds.length);

                // Log all form fields for debugging
                console.log('Form fields:', Array.from(formData.keys()));

                if (projectObjectiveIds.length === 0) {
                    e.preventDefault();
                    alert('Error: No project objectives found. Please refresh the page and try again.');
                    return false;
                }

                if (projectActivityIds.length === 0) {
                    e.preventDefault();
                    alert('Error: No project activities found. Please refresh the page and try again.');
                    return false;
                }

                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                }

                console.log('Form validation passed, proceeding with submission');
            });
        }

        updateOutlookRemoveButtons();
        updatePhotoLabels();
    });

    // Outlook Section
    function addOutlook() {
        const outlookContainer = document.getElementById('outlook-container');
        const index = outlookContainer.children.length;
        const newOutlookHtml = `
            <div class="mb-3 card outlook" data-index="${index}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Outlook ${index + 1}
                    <button type="button" class="btn btn-danger btn-sm remove-outlook" onclick="removeOutlook(this)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="date[${index}]" class="form-label">Date</label>
                        <input type="date" name="date[${index}]" class="form-control" style="background-color: #202ba3;">
                    </div>
                    <div class="mb-3">
                        <label for="plan_next_month[${index}]" class="form-label">Action Plan for Next Month</label>
                        <textarea name="plan_next_month[${index}]" class="form-control" rows="3" style="background-color: #202ba3;"></textarea>
                    </div>
                </div>
            </div>
        `;
        outlookContainer.insertAdjacentHTML('beforeend', newOutlookHtml);
        updateOutlookRemoveButtons();
    }

    function removeOutlook(button) {
        const outlook = button.closest('.outlook');
        outlook.remove();
        updateOutlookRemoveButtons();
    }

    function updateOutlookRemoveButtons() {
        const outlooks = document.querySelectorAll('.outlook');
        outlooks.forEach((outlook, index) => {
            const removeButton = outlook.querySelector('.remove-outlook');
            if (index === 0) {
                removeButton.classList.add('d-none');
            } else {
                removeButton.classList.remove('d-none');
            }
        });
    }
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
