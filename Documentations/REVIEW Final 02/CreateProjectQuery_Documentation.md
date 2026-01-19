{{-- resources/views/projects/Oldprojects/createProjects.blade.php --}}
@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data"  >
                @csrf
                <div class="mb-3 card">
                    <div class="card-header">
                        <h4 class="fp-text-center1">PROJECT APPLICATION FORM</h4>
                    </div>
                    <div class="card-header">
                        <h4 class="fp-text-margin">General Information</h4>
                    </div>

                    <!-- General Information Fields -->
                    <div class="card-body">
                        @include('projects.partials.general_info')
                    </div>
                </div>

                <!-- Key Information Section -->
                @include('projects.partials.key_information')

                <!-- Logical Framework Section -->
                @include('projects.partials.logical_framework')

                <!-- Project Sustainability, Monitoring, and Evaluation Framework -->
                @include('projects.partials.sustainability')

                <!-- Budget Section -->
                @include('projects.partials.budget')

                <!-- Attachments Section -->
                @include('projects.partials.attachments')

                <button type="submit" class="btn btn-primary me-2">Submit Application</button>
            </form>
        </div>
    </div>
</div>

@include('projects.partials.scripts')

<style>
    .readonly-input {
        background-color: #0D1427;
        color: #f4f0f0;
    }

    .select-input {
        background-color: #112f6b;
        color: #f4f0f0;
    }

    .readonly-select {
        background-color: #092968;
        color: #f4f0f0;
    }

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
@endsection <!-- resources/views/projects/partials/general_info.blade.php -->
<div class="card-body">
    <div class="mb-3">
        <label for="project_type" class="form-label">Project Type</label>
        <select name="project_type" id="project_type" class="form-control select-input" required  style="background-color: #202ba3;">
            <option value="" disabled selected>Select Project Type</option>
            <!-- Add other project types here -->
            <option value="CHILD CARE INSTITUTION" {{ old('project_type') == 'CHILD CARE INSTITUTION' ? 'selected' : '' }}>CHILD CARE INSTITUTION - Welfare home for children - Ongoing</option>
            <option value="Development Projects" {{ old('project_type') == 'Development Projects' ? 'selected' : '' }}>Development Projects - Application</option>
            <option value="Rural-Urban-Tribal" {{ old('project_type') == 'Rural-Urban-Tribal' ? 'selected' : '' }}>Rural-Urban-Tribal</option>
            <option value="Institutional Ongoing Group Educational proposal" {{ old('project_type') == 'Institutional Ongoing Group Educational proposal' ? 'selected' : '' }}>Institutional Ongoing Group Educational proposal</option>
            <option value="Livelihood Development Projects" {{ old('project_type') == 'Livelihood Development Projects' ? 'selected' : '' }}>Livelihood Development Projects</option>
            <option value="PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER" {{ old('project_type') == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' ? 'selected' : '' }}>PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER - Application</option>
            <option value="NEXT PHASE - DEVELOPMENT PROPOSAL" {{ old('project_type') == 'NEXT PHASE - DEVELOPMENT PROPOSAL' ? 'selected' : '' }}>NEXT PHASE - DEVELOPMENT PROPOSAL</option>
            <option value="Residential Skill Training Proposal 2" {{ old('project_type') == 'Residential Skill Training Proposal 2' ? 'selected' : '' }}>Residential Skill Training Proposal 2</option>
            <option value="Individual - Ongoing Educational support" {{ old('project_type') == 'Individual - Ongoing Educational support' ? 'selected' : '' }}>Individual - Ongoing Educational support - Project Application</option>
            <option value="Individual - Livelihood Application" {{ old('project_type') == 'Individual - Livelihood Application' ? 'selected' : '' }}>Individual - Livelihood Application</option>
            <option value="Individual - Access to Health" {{ old('project_type') == 'Individual - Access to Health' ? 'selected' : '' }}>Individual - Access to Health - Project Application</option>
            <option value="Individual - Initial - Educational support" {{ old('project_type') == 'Individual - Initial - Educational support' ? 'selected' : '' }}>Individual - Initial - Educational support - Project Application</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="project_title" class="form-label">Project Title</label>
        <input type="text" name="project_title" class="form-control select-input" value="{{ old('project_title') }}" required  style="background-color: #202ba3;">
    </div>
    <div class="mb-3">
        <label for="society_name" class="form-label">Name of the Society / Trust</label>
        <input type="text" name="society_name" class="form-control readonly-input" value="{{ $user->society_name }}" readonly>
    </div>
    <div class="mb-3">
        <label for="president_name" class="form-label">President / Chair Person</label>
        <input type="text" name="president_name" class="form-control readonly-input" value="{{ $user->parent->name }}" readonly>
    </div>
    <div class="mb-3">
        <label for="applicant_name" class="form-label">Project Applicant</label>
        <div class="d-flex">
            <input type="text" name="applicant_name" class="form-control readonly-input me-2" value="{{ $user->name }}" readonly>
            <input type="text" name="applicant_mobile" class="form-control readonly-input me-2" value="{{ $user->phone }}" readonly>
            <input type="text" name="applicant_email" class="form-control readonly-input" value="{{ $user->email }}" readonly>
        </div>
    </div>
    <div class="mb-3">
        <label for="in_charge" class="form-label">Project In-Charge</label>
        <div class="d-flex">
            <select name="in_charge" id="in_charge" class="form-control select-input me-2" required  style="background-color: #202ba3;">
                <option value="" disabled selected>Select In-Charge</option>
                @foreach($users as $potential_in_charge)
                    @if($potential_in_charge->province == $user->province)
                        <option value="{{ $potential_in_charge->id }}" data-name="{{ $potential_in_charge->name }}" data-mobile="{{ $potential_in_charge->phone }}" data-email="{{ $potential_in_charge->email }}" {{ old('in_charge') == $potential_in_charge->id ? 'selected' : '' }}>
                            {{ $potential_in_charge->name }}
                        </option>
                    @endif
                @endforeach
            </select>
            <input type="hidden" name="in_charge_name" id="in_charge_name" style="background-color: #202ba3;">
            <input type="text" name="in_charge_mobile" id="in_charge_mobile" class="form-control readonly-input me-2" readonly>
            <input type="text" name="in_charge_email" id="in_charge_email" class="form-control readonly-input" readonly>
        </div>
    </div>
    <div class="mb-3">
        <label for="full_address" class="form-label">Full Address</label>
        <textarea name="full_address" class="form-control select-input" rows="2" required style="background-color: #091122;">{{ old('full_address', $user->address) }}</textarea>
    </div>
    <div class="mb-3">
        <label for="overall_project_period" class="form-label">Overall Project Period</label>
        <select name="overall_project_period" id="overall_project_period" class="form-control select-input" required  style="background-color: #202ba3;">
            <option value="" disabled selected>Select Period</option>
            <option value="1" {{ old('overall_project_period') == 1 ? 'selected' : '' }}>1 Year</option>
            <option value="2" {{ old('overall_project_period') == 2 ? 'selected' : '' }}>2 Years</option>
            <option value="3" {{ old('overall_project_period') == 3 ? 'selected' : '' }}>3 Years</option>
            <option value="4" {{ old('overall_project_period') == 4 ? 'selected' : '' }}>4 Years</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="current_phase" class="form-label">Current Phase</label>
        <select name="current_phase" id="current_phase" class="form-control readonly-select" required  style="background-color: #202ba3;">
            <option value="" disabled selected>Select Phase</option>
            @for ($i = 1; $i <= old('overall_project_period', 4); $i++)
                <option value="{{ $i }}" {{ old('current_phase') == $i ? 'selected' : '' }}>Phase {{ $i }}</option>
            @endfor
        </select>
    </div>
    <div class="mb-3">
    <label for="commencement_month" class="form-label">Commencement Month</label>
    <select name="commencement_month" id="commencement_month" class="form-control select-input" style="background-color: #202ba3;">
        <option value="" disabled selected>Select Month</option>
        @for ($month = 1; $month <= 12; $month++)
            <option value="{{ $month }}">{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
        @endfor
    </select>
</div>

<div class="mb-3">
    <label for="commencement_year" class="form-label">Commencement Year</label>
    <select name="commencement_year" id="commencement_year" class="form-control select-input" style="background-color: #202ba3;">
        <option value="" disabled selected>Select Year</option>
        @for ($year = now()->year; $year >= 2000; $year--)
            <option value="{{ $year }}">{{ $year }}</option>
        @endfor
    </select>
</div>

    <div class="mb-3">
        <label for="overall_project_budget" class="form-label">Overall Project Budget</label>
        <input type="number" name="overall_project_budget" id="overall_project_budget" class="form-control select-input" value="{{ old('overall_project_budget') }}" required>
    </div>
    <div class="mb-3">
        @php
            $coordinator_india = $users->firstWhere('role', 'coordinator')->firstWhere('province', 'Generalate');
        @endphp
        <label for="coordinator_india" class="form-label">Project Co-Ordinator, India</label>
        <div class="d-flex">
            @if($coordinator_india)
                <input type="hidden" name="coordinator_india" value="{{ $coordinator_india->id }}">
                <input type="text" name="coordinator_india_name" class="form-control readonly-input me-2" value="{{ $coordinator_india->name }}" readonly>
                <input type="text" name="coordinator_india_phone" class="form-control readonly-input me-2" value="{{ $coordinator_india->phone }}" readonly>
                <input type="text" name="coordinator_india_email" class="form-control readonly-input" value="{{ $coordinator_india->email }}" readonly>
            @else
                <input type="text" name="coordinator_india_name" class="form-control readonly-input me-2" placeholder="Name not found for Project Co-Ordinator, India" readonly>
                <input type="text" name="coordinator_india_phone" class="form-control readonly-input me-2" placeholder="Phone not updated for Project Co-Ordinator, India" readonly>
                <input type="text" name="coordinator_india_email" class="form-control readonly-input" placeholder="Email not found for Project Co-Ordinator, India" readonly>
            @endif
        </div>
    </div>
    <div class="mb-3">
        @php
            $coordinator_luzern = $users->firstWhere('role', 'coordinator')->firstWhere('province', 'Luzern');
        @endphp
        <label for="coordinator_luzern" class="form-label">Mission Co-Ordinator, Luzern, Switzerland</label>
        <div class="d-flex">
            @if($coordinator_luzern)
                <input type="hidden" name="coordinator_luzern" value="{{ $coordinator_luzern->id }}">
                <input type="text" name="coordinator_luzern_name" class="form-control readonly-input me-2" value="{{ $coordinator_luzern->name }}" readonly>
                <input type="text" name="coordinator_luzern_phone" class="form-control readonly-input me-2" value="{{ $coordinator_luzern->phone }}" readonly>
                <input type="text" name="coordinator_luzern_email" class="form-control readonly-input" value="{{ $coordinator_luzern->email }}" readonly>
            @else
                <input type="text" name="coordinator_luzern_name" class="form-control readonly-input me-2" placeholder="Name not found for Project Co-Ordinator, Luzern, Switzerland" readonly>
                <input type="text" name="coordinator_luzern_phone" class="form-control readonly-input me-2" placeholder="Phone not found for Project Co-Ordinator, Luzern, Switzerland" readonly>
                <input type="text" name="coordinator_luzern_email" class="form-control readonly-input" placeholder="Email not found for Project Co-Ordinator, Luzern, Switzerland" readonly>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update the current phase options based on the selected overall project period
    document.getElementById('overall_project_period').addEventListener('change', function() {
        const projectPeriod = parseInt(this.value);
        const phaseSelect = document.getElementById('current_phase');

        // Clear previous options
        phaseSelect.innerHTML = '<option value="" disabled selected>Select Phase</option>';

        // Add new options based on the selected value
        for (let i = 1; i <= projectPeriod; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.text = `Phase ${i}`;
            phaseSelect.appendChild(option);
        }
    });

    // Placeholder for future additional dynamic interactions
    // Example: You can add more event listeners here to handle other dynamic interactions

});
</script> <!-- resources/views/projects/partials/key_information.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4> Key Information</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project</label>
            <textarea name="goal" class="form-control select-input" rows="3" required  style="background-color: #202ba3;">{{ old('goal') }}</textarea>
        </div>
    </div>
</div> <!-- resources/views/projects/partials/logical_framework.blade.php -->
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Solution Analysis: Logical Framework</h4>
    </div>
    <div class="card-body" id="objectives-container">
        <!-- Objective Template -->
        <div class="mb-3 objective-card">
            <div class="objective-header d-flex justify-content-between align-items-center">
                <h5>Objective 1</h5>
            </div>
            <textarea name="objectives[0][objective]" class="mb-3 form-control objective-description" rows="2" placeholder="Enter Objective" style="background-color: #202ba3;"></textarea>

            <div class="results-container">
                <!-- Result Section -->
                <div class="mb-3 result-section">
                    <div class="result-header d-flex justify-content-between align-items-center">
                        <h6>Results / Outcome</h6>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                    </div>
                    <textarea name="objectives[0][results][0][result]" class="mb-3 form-control result-outcome" rows="2" placeholder="Enter Result" style="background-color: #202ba3;"></textarea>
                </div>
                <!-- Button to add more Results -->
                <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>

                <!-- Risks Section -->
                <div class="risks-container">
                    <div class="mb-3 risk-section">
                        <div class="risk-header d-flex justify-content-between align-items-center">
                            <h6>Risks</h6>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                        </div>
                        <textarea name="objectives[0][risks][0][risk]" class="mb-3 form-control risk-description" rows="2" placeholder="Enter Risk" style="background-color: #202ba3;"></textarea>
                    </div>
                    <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this)">Add Risk</button>
                </div>
            </div>

            <!-- Activities Table -->
            <div class="activities-container">
                <h6>Activities and Means of Verification</h6>
                <table class="table table-bordered activities-table">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 40%;">Activities</th>
                            <th scope="col">Means of Verification</th>
                            <th scope="col" style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Activity Template -->
                        <tr class="activity-row">
                            <td>
                                <textarea name="objectives[0][activities][0][activity]" class="form-control activity-description" rows="2" placeholder="Enter Activity" style="background-color: #202ba3;"></textarea>
                            </td>
                            <td>
                                <textarea name="objectives[0][activities][0][verification]" class="form-control activity-verification" rows="2" placeholder="Means of Verification" style="background-color: #202ba3;"></textarea>
                            </td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeActivity(this)">Remove</button></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="mb-3 btn btn-primary" onclick="addActivity(this)">Add Activity</button>
            </div>
            <!-- Include the Time Frame partial -->
            @include('projects.partials._timeframe', ['objectiveIndex' => 0])
        </div>
        <!-- End of Objective Template -->

        <!-- Objective Controls -->
        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-primary" onclick="addObjective()">Add Objective</button>
            <button type="button" class="btn btn-danger" onclick="removeLastObjective()">Remove Last Objective</button>
        </div>
    </div>
</div>


<script>

let objectiveCount = 1;

document.addEventListener('DOMContentLoaded', function() {
    objectiveCount = document.querySelectorAll('.objective-card').length;
});

function addObjective() {
    const container = document.getElementById('objectives-container');
    const objectiveTemplate = document.querySelector('.objective-card').cloneNode(true);

    // Reset the values in the cloned template
    resetFormValues(objectiveTemplate);

    // Increment the objective count and update the objective header
    objectiveTemplate.querySelector('h5').innerText = `Objective ${++objectiveCount}`;

    // Reset risks, results, and activities to only one empty row each
    objectiveTemplate.querySelectorAll('.result-section:not(:first-child)').forEach(section => section.remove());
    objectiveTemplate.querySelectorAll('.risk-section:not(:first-child)').forEach(section => section.remove());
    objectiveTemplate.querySelectorAll('.activity-row:not(:first-child)').forEach(row => row.remove());

    // Reset the Time Frame section
    const timeFrameCard = objectiveTemplate.querySelector('.time-frame-card tbody');
    timeFrameCard.querySelectorAll('.activity-timeframe-row:not(:first-child)').forEach(row => row.remove());
    timeFrameCard.querySelectorAll('.activity-timeframe-row .activity-description-text').forEach(span => span.innerText = '');
    timeFrameCard.querySelectorAll('.month-checkbox').forEach(checkbox => checkbox.checked = false);

    // Update the name attributes for the new objective
    updateNameAttributes(objectiveTemplate, objectiveCount - 1);

    // Append the new objective at the end
    container.insertBefore(objectiveTemplate, container.lastElementChild);
}

function resetFormValues(template) {
    // Clear values of all textareas
    template.querySelectorAll('textarea').forEach(textarea => textarea.value = '');

    // Clear all checkboxes
    template.querySelectorAll('input[type="checkbox"]').forEach(checkbox => checkbox.checked = false);

    // Reset all select dropdowns (if any) to their default value
    template.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
}

function removeLastObjective() {
    const objectives = document.querySelectorAll('.objective-card');
    if (objectives.length > 1) {
        objectives[objectiveCount - 1].remove();
        objectiveCount--;
        updateObjectiveNumbers();
    }
}

function addResult(button) {
    const resultTemplate = button.closest('.results-container').querySelector('.result-section').cloneNode(true);
    resultTemplate.querySelector('textarea.result-outcome').value = '';
    button.closest('.results-container').insertBefore(resultTemplate, button);
    updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
}

function removeResult(button) {
    const resultSection = button.closest('.result-section');
    if (resultSection.parentNode.querySelectorAll('.result-section').length > 1) {
        resultSection.remove();
        updateNameAttributes(resultSection.closest('.objective-card'), getObjectiveIndex(resultSection.closest('.objective-card')));
    }
}

function addRisk(button) {
    const risksContainer = button.closest('.risks-container');
    const riskTemplate = risksContainer.querySelector('.risk-section').cloneNode(true);
    riskTemplate.querySelector('textarea.risk-description').value = '';

    // Append the new risk section before the "Add Risk" button
    risksContainer.insertBefore(riskTemplate, button);

    updateNameAttributes(risksContainer.closest('.objective-card'), getObjectiveIndex(risksContainer.closest('.objective-card')));
}

function removeRisk(button) {
    const riskSection = button.closest('.risk-section');
    if (riskSection.parentNode.querySelectorAll('.risk-section').length > 1) {
        riskSection.remove();
        updateNameAttributes(riskSection.closest('.objective-card'), getObjectiveIndex(riskSection.closest('.objective-card')));
    }
}

function addActivity(button) {
    const activitiesTable = button.closest('.activities-container').querySelector('tbody');
    const activityRow = activitiesTable.querySelector('.activity-row').cloneNode(true);
    activityRow.querySelector('textarea.activity-description').value = '';
    activityRow.querySelector('textarea.activity-verification').value = '';
    activitiesTable.appendChild(activityRow);

    const objectiveCard = button.closest('.objective-card');
    const timeFrameCard = objectiveCard.querySelector('.time-frame-card tbody');
    const timeFrameRow = timeFrameCard.querySelector('.activity-timeframe-row').cloneNode(true);
    timeFrameRow.querySelector('.activity-description-text').innerText = '';
    timeFrameRow.querySelectorAll('.month-checkbox').forEach(checkbox => checkbox.checked = false);
    timeFrameCard.appendChild(timeFrameRow);

    // Update the activity description in the timeframe table when the activity description changes
    activityRow.querySelector('textarea.activity-description').addEventListener('input', function() {
        const index = Array.from(activitiesTable.querySelectorAll('.activity-row')).indexOf(activityRow);
        timeFrameCard.querySelectorAll('.activity-timeframe-row')[index].querySelector('.activity-description-text').innerText = this.value;
    });

    updateNameAttributes(objectiveCard, getObjectiveIndex(objectiveCard));
}

function removeActivity(button) {
    const row = button.closest('tr');
    const activityIndex = Array.from(row.parentNode.children).indexOf(row);
    row.remove();

    const timeFrameCard = button.closest('.objective-card').querySelector('.time-frame-card tbody');
    const timeframeRow = timeFrameCard.children[activityIndex];
    timeframeRow.remove();

    updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
}

function getObjectiveIndex(objectiveCard) {
    const objectives = Array.from(document.querySelectorAll('.objective-card'));
    return objectives.indexOf(objectiveCard);
}

function updateNameAttributes(objectiveCard, objectiveIndex) {
    objectiveCard.querySelector('textarea.objective-description').name = `objectives[${objectiveIndex}][objective]`;

    // Update the names for results
    const results = objectiveCard.querySelectorAll('.result-section');
    results.forEach((result, resultIndex) => {
        result.querySelector('textarea.result-outcome').name = `objectives[${objectiveIndex}][results][${resultIndex}][result]`;
    });

    // Update the names for risks
    const risks = objectiveCard.querySelectorAll('.risks-container .risk-section');
    risks.forEach((riskSection, riskIndex) => {
        const riskTextarea = riskSection.querySelector('textarea.risk-description');
        riskTextarea.name = `objectives[${objectiveIndex}][risks][${riskIndex}][risk]`;
    });

    // Update the names for activities and their timeframes
    const activities = objectiveCard.querySelectorAll('.activities-table .activity-row');
    activities.forEach((activityRow, activityIndex) => {
        activityRow.querySelector('textarea.activity-description').name = `objectives[${objectiveIndex}][activities][${activityIndex}][activity]`;
        activityRow.querySelector('textarea.activity-verification').name = `objectives[${objectiveIndex}][activities][${activityIndex}][verification]`;

        // Update the timeframe for this activity
        const timeFrameRow = objectiveCard.querySelectorAll('.time-frame-card tbody .activity-timeframe-row')[activityIndex];
        if (timeFrameRow) {
            // Update the activity description (if needed)
            timeFrameRow.querySelector('.activity-description-text').innerText = activityRow.querySelector('textarea.activity-description').value;

            // Update the names for the checkboxes
            timeFrameRow.querySelectorAll('.month-checkbox').forEach((checkbox, monthIndex) => {
                checkbox.name = `objectives[${objectiveIndex}][activities][${activityIndex}][timeframe][months][${monthIndex + 1}]`;
            });
        }
    });
}

function updateObjectiveNumbers() {
    const objectives = document.querySelectorAll('.objective-card');
    objectives.forEach((objective, index) => {
        objective.querySelector('h5').innerText = `Objective ${index + 1}`;
        updateNameAttributes(objective, index);
    });
}

// Time Frame specific functions
function addTimeFrameRow(button) {
    const timeFrameCard = button.closest('.time-frame-card');
    const tbody = timeFrameCard.querySelector('tbody');
    const newRow = tbody.querySelector('.activity-timeframe-row').cloneNode(true);

    // Clear the contents of the new row
    newRow.querySelector('.activity-description-text').innerText = '';
    newRow.querySelectorAll('.month-checkbox').forEach(checkbox => checkbox.checked = false);

    tbody.appendChild(newRow);

    updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
}

function removeTimeFrameRow(button) {
    const row = button.closest('tr');
    row.remove();
    updateNameAttributes(button.closest('.objective-card'), getObjectiveIndex(button.closest('.objective-card')));
}

</script>{{-- resources/views/projects/partials/sustainability.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Project Sustainability, Monitoring and Methodologies</h4>
    </div>
    <div class="card-body">

        <!-- Resilience Section -->
        <div class="mb-3">
            <h5>Explain the Sustainability of the Project:</h5>
            <textarea name="sustainability" class="form-control" rows="3" placeholder="Explain the resilience of the project" style="background-color: #202ba3;"></textarea>
        </div>

        <!-- Monitoring Process Section -->
        <div class="mb-3">
            <h5>Explain the Monitoring Process of the Project:</h5>
            <textarea name="monitoring_process" class="form-control" rows="3" placeholder="Explain the monitoring process of the project" style="background-color: #202ba3;"></textarea>
        </div>

        <!-- Reporting Methodology Section -->
        <div class="mb-3">
            <h5>Explain the Methodology of Reporting:</h5>
            <textarea name="reporting_methodology" class="form-control" rows="3" placeholder="Explain the methodology of reporting" style="background-color: #202ba3;"></textarea>
        </div>

        <!-- Evaluation Methodology Section -->
        <div class="mb-3">
            <h5>Explain the Methodology of Evaluation:</h5>
            <textarea name="evaluation_methodology" class="form-control" rows="3" placeholder="Explain the methodology of evaluation" style="background-color: #202ba3;"></textarea>
        </div>

    </div>
</div>  <!-- resources/views/projects/partials/budget.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>2. Budget</h4>
    </div>
    <div class="card-body">
        <div id="phases-container">
            <div class="phase-card" data-phase="0">
                <div class="card-header">
                    <h4>Phase 1</h4>
                </div>
                <div class="mb-3">
                    <label for="phases[0][amount_sanctioned]" class="form-label">Amount Sanctioned in First Phase: Rs.</label>
                    <input type="number" name="phases[0][amount_sanctioned]" class="form-control select-input" value="{{ old('phases.0.amount_sanctioned') }}" required>
                </div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Particular</th>
                            <th>Costs</th>
                            <th>Rate Multiplier</th>
                            <th>Rate Duration</th>
                            <th>Rate Increase (next phase)</th>
                            <th>This Phase (Auto)</th>
                            <th>Next Phase (Auto)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="budget-rows">
                        <tr>
                            <td><input type="text" name="phases[0][budget][0][particular]" class="form-control select-input" value="{{ old('phases.0.budget.0.particular') }}"  style="background-color: #202ba3;"></td>
                            <td><input type="number" name="phases[0][budget][0][rate_quantity]" class="form-control select-input" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_quantity') }}"  style="background-color: #202ba3;"></td>
                            <td><input type="number" name="phases[0][budget][0][rate_multiplier]" class="form-control select-input" value="1" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_multiplier', 1) }}" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="phases[0][budget][0][rate_duration]" class="form-control select-input" value="1" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_duration', 1) }}" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="phases[0][budget][0][rate_increase]" class="form-control select-input" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_increase') }}" style="background-color: #122F6B;"></td>
                            <td><input type="number" name="phases[0][budget][0][this_phase]" class="form-control readonly-input" readonly value="{{ old('phases.0.budget.0.this_phase') }}"></td>
                            <td><input type="number" name="phases[0][budget][0][next_phase]" class="form-control select-input" value="{{ old('phases.0.budget.0.next_phase') }}"></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <th><input type="number" class="total_rate_quantity form-control readonly-input" readonly></th>
                            <th><input type="number" class="total_rate_multiplier form-control readonly-input" readonly></th>
                            <th><input type="number" class="total_rate_duration form-control readonly-input" readonly></th>
                            <th><input type="number" class="total_rate_increase form-control readonly-input" readonly></th>
                            <th><input type="number" class="total_this_phase form-control readonly-input" readonly></th>
                            <th><input type="number" class="total_next_phase form-control readonly-input" readonly></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                <button type="button" class="btn btn-primary" onclick="addBudgetRow(this)">Add Row</button>
            </div>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addPhase()">Add Phase</button>
        <div class="mt-3" style="margin-bottom: 20px;">
            <label for="total_amount_sanctioned" class="form-label">Total Amount Sanctioned: Rs.</label>
            <input type="number" name="total_amount_sanctioned" class="form-control readonly-input" readonly value="{{ old('total_amount_sanctioned') }}">
        </div>
        <div class="mt-3" style="margin-bottom: 20px;">
            <label for="total_amount_forwarded" class="form-label">Total Amount Forwarded: Rs.</label>
            <input type="number" name="total_amount_forwarded" class="form-control readonly-input" readonly value="{{ old('total_amount_forwarded') }}">
        </div>
    </div>
</div> <!-- resources/views/projects/partials/attachments.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachment</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="file" class="form-label">Attachment File</label>
            <input type="file" name="file" id="file" class="mb-2 form-control" accept=".pdf" required onchange="checkFileSize(this)" style="background-color: #202ba3;">

            <label for="file_name" class="form-label">File Name</label>
            <input type="text" name="file_name" class="mb-2 form-control" placeholder="Name of File Attached" required style="background-color: #202ba3;">

            <label for="description" class="form-label">Brief Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Describe the file" required style="background-color: #202ba3;"></textarea>
        </div>
        <p id="file-size-warning" style="color: red; display: none;">File size must not exceed 10 MB!</p>
    </div>
</div>

<script>
function checkFileSize(input) {
    const file = input.files[0];
    if (file.size > 10485760) { // 10 MB in bytes
        document.getElementById('file-size-warning').style.display = 'block';
        input.value = ''; // Reset the file input
    } else {
        document.getElementById('file-size-warning').style.display = 'none';
    }
}
</script> {{-- resources/views/projects/partials/scripts.blade.php --}}
<script>
    function beforeSubmit() {
    const formData = new FormData(document.querySelector('form'));
    formData.forEach((value, key) => {
        console.log(`${key}: ${value}`);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Update the mobile and email fields based on the selected project in-charge
    document.getElementById('in_charge').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const name = selectedOption.getAttribute('data-name');
        const mobile = selectedOption.getAttribute('data-mobile');
        const email = selectedOption.getAttribute('data-email');

        document.getElementById('in_charge_name').value = name;
        document.getElementById('in_charge_mobile').value = mobile;
        document.getElementById('in_charge_email').value = email;
    });

    // Update the phase options based on the selected overall project period
    document.getElementById('overall_project_period').addEventListener('change', function() {
        const projectPeriod = parseInt(this.value);
        const phaseSelect = document.getElementById('current_phase');

        // Clear previous options
        phaseSelect.innerHTML = '<option value="" disabled selected>Select Phase</option>';

        // Add new options based on the selected value
        for (let i = 1; i <= projectPeriod; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.text = `${i}${i === 1 ? 'st' : i === 2 ? 'nd' : i === 3 ? 'rd' : 'th'} Phase`;
            phaseSelect.appendChild(option);
        }

        // Update all budget rows based on the selected project period
        updateAllBudgetRows();
    });
});

// Calculate the budget totals for a single budget row
function calculateBudgetRowTotals(element) {
    const row = element.closest('tr');
    const rateQuantity = parseFloat(row.querySelector('[name$="[rate_quantity]"]').value) || 0;
    const rateMultiplier = parseFloat(row.querySelector('[name$="[rate_multiplier]"]').value) || 1;
    const rateDuration = parseFloat(row.querySelector('[name$="[rate_duration]"]').value) || 1;
    const rateIncrease = parseFloat(row.querySelector('[name$="[rate_increase]"]').value) || 0;

    const thisPhase = rateQuantity * rateMultiplier * rateDuration;
    let nextPhase = 0;

    // Only calculate next phase value if there is a rate increase
    if (rateIncrease !== 0) {
        nextPhase = (rateQuantity + rateIncrease) * rateMultiplier * rateDuration;
    }

    row.querySelector('[name$="[this_phase]"]').value = thisPhase.toFixed(2);
    row.querySelector('[name$="[next_phase]"]').value = nextPhase.toFixed(2);

    calculateBudgetTotals(row.closest('.phase-card')); // Recalculate totals for the phase whenever a row total is updated
}

// Update all budget rows based on the selected project period
function updateAllBudgetRows() {
    const phases = document.querySelectorAll('.phase-card');
    phases.forEach(phase => {
        const rows = phase.querySelectorAll('.budget-rows tr');
        rows.forEach(row => {
            calculateBudgetRowTotals(row.querySelector('input'));
        });
    });
}

// Calculate the total budget for a phase
function calculateBudgetTotals(phaseCard) {
    const rows = phaseCard.querySelectorAll('.budget-rows tr');
    let totalRateQuantity = 0;
    let totalRateMultiplier = 0;
    let totalRateDuration = 0;
    let totalRateIncrease = 0;
    let totalThisPhase = 0;
    let totalNextPhase = 0;

    rows.forEach(row => {
        totalRateQuantity += parseFloat(row.querySelector('[name$="[rate_quantity]"]').value) || 0;
        totalRateMultiplier += parseFloat(row.querySelector('[name$="[rate_multiplier]"]').value) || 1;
        totalRateDuration += parseFloat(row.querySelector('[name$="[rate_duration]"]').value) || 1;
        totalRateIncrease += parseFloat(row.querySelector('[name$="[rate_increase]"]').value) || 0;
        totalThisPhase += parseFloat(row.querySelector('[name$="[this_phase]"]').value) || 0;
        totalNextPhase += parseFloat(row.querySelector('[name$="[next_phase]"]').value) || 0;
    });

    phaseCard.querySelector('.total_rate_quantity').value = totalRateQuantity.toFixed(2);
    phaseCard.querySelector('.total_rate_multiplier').value = totalRateMultiplier.toFixed(2);
    phaseCard.querySelector('.total_rate_duration').value = totalRateDuration.toFixed(2);
    phaseCard.querySelector('.total_rate_increase').value = totalRateIncrease.toFixed(2);
    phaseCard.querySelector('.total_this_phase').value = totalThisPhase.toFixed(2);
    phaseCard.querySelector('.total_next_phase').value = totalNextPhase.toFixed(2);

    calculateTotalAmountSanctioned();
}

// Calculate the total amount sanctioned and update the overall project budget
function calculateTotalAmountSanctioned() {
    const phases = document.querySelectorAll('.phase-card');
    let totalAmount = 0;
    let totalNextPhase = 0;
    let totalForwarded = 0;

    phases.forEach((phase, index) => {
        const thisPhaseTotal = parseFloat(phase.querySelector('.total_this_phase').value) || 0;
        phase.querySelector('[name^="phases"][name$="[amount_sanctioned]"]').value = thisPhaseTotal.toFixed(2);

        if (index > 0) {
            const amountForwarded = parseFloat(phase.querySelector('[name^="phases"][name$="[amount_forwarded]"]').value) || 0;
            const openingBalance = amountForwarded + thisPhaseTotal;
            phase.querySelector('[name^="phases"][name$="[opening_balance]"]').value = openingBalance.toFixed(2);
            totalForwarded += amountForwarded;
        }

        totalAmount += thisPhaseTotal;
    });

    const lastPhase = phases[phases.length - 1];
    const rows = lastPhase.querySelectorAll('.budget-rows tr');
    rows.forEach(row => {
        totalNextPhase += parseFloat(row.querySelector('[name$="[next_phase]"]').value) || 0;
    });

    document.querySelector('[name="total_amount_sanctioned"]').value = totalAmount.toFixed(2);
    document.querySelector('[name="total_amount_forwarded"]').value = totalForwarded.toFixed(2);
    document.getElementById('overall_project_budget').value = (totalAmount + totalNextPhase).toFixed(2);
}

// Add a new budget row to the phase card
function addBudgetRow(button) {
    const tableBody = button.closest('.phase-card').querySelector('.budget-rows');
    const phaseIndex = button.closest('.phase-card').dataset.phase;
    const newRow = document.createElement('tr');

    newRow.innerHTML = `
        <td><input type="text" name="phases[${phaseIndex}][budget][${tableBody.children.length}][particular]" class="form-control"  style="background-color: #202ba3;"></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_quantity]" class="form-control" oninput="calculateBudgetRowTotals(this)" style="background-color: #202ba3;"></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_multiplier]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" style="background-color: #202ba3;"></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_duration]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" style="background-color: #202ba3;"></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_increase]" class="form-control" oninput="calculateBudgetRowTotals(this)";"  style="background-color: #122F6B"></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][this_phase]" class="form-control readonly-input" readonly></td>
        <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][next_phase]" class="form-control" style="background-color: #122F6B"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
    `;

    newRow.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            calculateBudgetRowTotals(input);
        });
    });

    tableBody.appendChild(newRow);
    calculateBudgetTotals(tableBody.closest('.phase-card'));
}

// Remove a budget row from the phase card
function removeBudgetRow(button) {
    const row = button.closest('tr');
    const phaseCard = row.closest('.phase-card');
    row.remove();
    calculateBudgetTotals(phaseCard); // Recalculate totals after removing a row
}

// Add a new phase card
function addPhase() {
    const phasesContainer = document.getElementById('phases-container');
    const phaseCount = phasesContainer.children.length;
    const newPhase = document.createElement('div');
    newPhase.className = 'phase-card';
    newPhase.dataset.phase = phaseCount;

    newPhase.innerHTML = `
        <div class="card-header">
            <h4>Phase ${phaseCount + 1}</h4>
        </div>
        ${phaseCount > 0 ? `
        <div class="mb-3">
            <label for="phases[${phaseCount}][amount_forwarded]" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
            <input type="number" name="phases[${phaseCount}][amount_forwarded]" class="form-control" oninput="calculateBudgetTotals(this.closest('.phase-card'))">
        </div>
        ` : ''}
        <div class="mb-3">
            <label for="phases[${phaseCount}][amount_sanctioned]" class="form-label">Amount Sanctioned in Phase ${phaseCount + 1}: Rs.</label>
            <input type="number" name="phases[${phaseCount}][amount_sanctioned]" class="form-control readonly-input" readonly>
        </div>
        <div class="mb-3">
            <label for="phases[${phaseCount}][opening_balance]" class="form-label">Opening balance in Phase ${phaseCount + 1}: Rs.</label>
            <input type="number" name="phases[${phaseCount}][opening_balance]" class="form-control readonly-input" readonly>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Particular</th>
                    <th>Costs</th>
                    <th>Rate Multiplier</th>
                    <th>Rate Duration</th>
                    <th>Rate Increase (next phase)</th>
                    <th>This Phase (Auto)</th>
                    <th>Next Phase (Auto)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody class="budget-rows">
                <tr>
                    <td><input type="text" name="phases[${phaseCount}][budget][0][particular]" class="form-control"  style="background-color: #202ba3;"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_quantity]" class="form-control" oninput="calculateBudgetRowTotals(this)"  style="background-color: #202ba3;"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_multiplier]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)"  style="background-color: #202ba3;"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_duration]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)"  style="background-color: #202ba3;"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][rate_increase]" class="form-control" oninput="calculateBudgetRowTotals(this)" style="background-color: #122F6B"></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][this_phase]" class="form-control readonly-input" readonly></td>
                    <td><input type="number" name="phases[${phaseCount}][budget][0][next_phase]" class="form-control" style="background-color: #122F6B"></td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th><input type="number" class="total_rate_quantity form-control readonly-input" readonly></th>
                    <th><input type="number" class="total_rate_multiplier form-control readonly-input" readonly></th>
                    <th><input type="number" class="total_rate_duration form-control readonly-input" readonly></th>
                    <th><input type="number" class="total_rate_increase form-control readonly-input" readonly></th>
                    <th><input type="number" class="total_this_phase form-control readonly-input" readonly></th>
                    <th><input type="number" class="total_next_phase form-control"></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
        <button type="button" class="btn btn-primary" onclick="addBudgetRow(this)">Add Row</button>
        <div>
            <button type="button" class="mt-3 btn btn-danger" onclick="removePhase(this)">Remove Phase</button>
        </div>
    `;

    phasesContainer.appendChild(newPhase);
    calculateTotalAmountSanctioned();
}

// Remove a phase card
function removePhase(button) {
    const phaseCard = button.closest('.phase-card');
    phaseCard.remove();
    calculateTotalAmountSanctioned();
}

// Add a new attachment field
function addAttachment() {
    const attachmentsContainer = document.getElementById('attachments-container');
    const currentAttachments = attachmentsContainer.children.length;

    const index = currentAttachments;
    const attachmentTemplate = `
        <div class="mb-3 attachment-group" data-index="${index}">
            <label for="attachments[${index}][file]" class="form-label">Attachment ${index + 1}</label>
            <input type="file" name="attachments[${index}][file]" class="mb-2 form-control" accept=".pdf,.doc,.docx,.xlsx">
            <label for="file_name[${index}]" class="form-label">File Name</label>
            <input type="text" name="file_name[${index}]" class="mb-2 form-control" placeholder="Name of File Attached">
            <textarea name="attachments[${index}][description]" class="form-control" rows="3" placeholder="Brief Description"></textarea>
            <button type="button" class="mt-2 btn btn-danger" onclick="removeAttachment(this)">Remove</button>
        </div>
    `;
    attachmentsContainer.insertAdjacentHTML('beforeend', attachmentTemplate);
    updateAttachmentLabels();
}

// Remove an attachment field
function removeAttachment(button) {
    const attachmentGroup = button.closest('.attachment-group');
    attachmentGroup.remove();
    updateAttachmentLabels();
}

// Update the labels for the attachments
function updateAttachmentLabels() {
    const attachmentGroups = document.querySelectorAll('.attachment-group');
    attachmentGroups.forEach((group, index) => {
        const label = group.querySelector('label');
        label.textContent = `Attachment ${index + 1}`;
    });
}

// Update the attachment labels on page load
document.addEventListener('DOMContentLoaded', function() {
    updateAttachmentLabels();
});
</script> HELP ME UNDERSTAND EACH FUNCNALITY OF THIS BLADE WILE ALONG WITH ALL THE JAVASCRIPT RELATED TO EACHE OF THE PARTIALS, LIST THEM ALL
