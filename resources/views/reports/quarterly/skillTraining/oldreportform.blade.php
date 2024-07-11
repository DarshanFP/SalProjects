@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <form action="{{ route('quarterly.skillTraining.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3 card">
                    <div class="card-header">
                        <h4 class="fp-text-center1">TRACKING SKILL TRAINING PROJECT</h4>
                        <h4 class="fp-text-center1">QUARTERLY PROGRESS REPORT</h4>
                    </div>
                    <div class="card-header">
                        <h4 class="fp-text-margin">Basic Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="project_title" class="form-label">Title of the Project</label>
                            <input type="text" name="project_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="place" class="form-label">Place</label>
                            <input type="text" name="place" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="society_name" class="form-label">Name of the Society / Trust</label>
                            <input type="text" name="society_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="commencement_month_year" class="form-label">Month & Year of Commencement of the Project</label>
                            <input type="text" name="commencement_month_year" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="in_charge" class="form-label">Sister/s In-Charge</label>
                            <input type="text" name="in_charge" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="total_beneficiaries" class="form-label">Total No. of Beneficiaries</label>
                            <input type="number" name="total_beneficiaries" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="reporting_period" class="form-label">Reporting Period</label>
                            <input type="text" name="reporting_period" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3 card">
                    <div class="card-header">
                        <h4>1. Key Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="goal" class="form-label">Goal of the Project</label>
                            <textarea name="goal" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="education_of_trainees" class="form-label">Education of Trainees</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="education_of_trainees[]" value="Below 9th standard" id="below_9th_standard" onchange="toggleEducationField(this, 'below_9th_standard_count')">
                                <label class="form-check-label" for="below_9th_standard">Below 9th standard</label>
                            </div>
                            <input type="number" name="below_9th_standard_count" class="mt-2 form-control d-none" placeholder="Enter count" id="below_9th_standard_count">

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="education_of_trainees[]" value="10th class failed" id="10th_class_failed" onchange="toggleEducationField(this, '10th_class_failed_count')">
                                <label class="form-check-label" for="10th_class_failed">10th class failed</label>
                            </div>
                            <input type="number" name="10th_class_failed_count" class="mt-2 form-control d-none" placeholder="Enter count" id="10th_class_failed_count">

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="education_of_trainees[]" value="10th class passed" id="10th_class_passed" onchange="toggleEducationField(this, '10th_class_passed_count')">
                                <label class="form-check-label" for="10th_class_passed">10th class passed</label>
                            </div>
                            <input type="number" name="10th_class_passed_count" class="mt-2 form-control d-none" placeholder="Enter count" id="10th_class_passed_count">

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="education_of_trainees[]" value="Intermediate" id="intermediate" onchange="toggleEducationField(this, 'intermediate_count')">
                                <label class="form-check-label" for="intermediate">Intermediate</label>
                            </div>
                            <input type="number" name="intermediate_count" class="mt-2 form-control d-none" placeholder="Enter count" id="intermediate_count">

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="education_of_trainees[]" value="Intermediate and above" id="intermediate_and_above" onchange="toggleEducationField(this, 'intermediate_and_above_count')">
                                <label class="form-check-label" for="intermediate_and_above">Intermediate and above</label>
                            </div>
                            <input type="number" name="intermediate_and_above_count" class="mt-2 form-control d-none" placeholder="Enter count" id="intermediate_and_above_count">

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="education_of_trainees[]" value="Other" id="other_education" onchange="toggleEducationField(this, 'other_education_specify', 'other_education_count')">
                                <label class="form-check-label" for="other_education">Other</label>
                            </div>
                            <input type="text" name="other_education_specify" class="mt-2 form-control d-none" placeholder="Please specify" id="other_education_specify">
                            <input type="number" name="other_education_count" class="mt-2 form-control d-none" placeholder="Enter count" id="other_education_count">
                        </div>
                    </div>
                </div>

                <!-- Objectives Section -->
                <div id="objectives-container">
                    <div class="mb-3 card objective" data-index="1">
                        <div class="card-header">
                            <h4>2. Activities and Intermediate Outcomes</h4>
                        </div>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            Objective 1
                            <button type="button" class="btn btn-danger btn-sm d-none remove-objective" onclick="removeObjective(this)">Remove</button>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="expected_outcome_1" class="form-label">Expected Outcome</label>
                                <textarea name="expected_outcome_1" class="form-control" rows="2" required></textarea>
                            </div>
                            <h4>Monthly Summary</h4>
                            <div class="monthly-summary-container" data-index="1">
                                <div class="mb-3 card activity" data-activity-index="1">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="form-group">
                                            <label for="month_1_1" class="form-label">Month</label>
                                            <select name="month_1_1" class="form-control" required>
                                                <option value="" disabled selected>Select Month</option>
                                                @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                                                    <option value="{{ $month }}">{{ $month }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="summary_activities_1_1_1" class="form-label">Summary of Activities Undertaken During the Four Months</label>
                                            <textarea name="summary_activities_1_1_1" class="form-control" rows="3" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="qualitative_quantitative_data_1_1_1" class="form-label">Qualitative & Quantitative Data</label>
                                            <textarea name="qualitative_quantitative_data_1_1_1" class="form-control" rows="3" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="intermediate_outcomes_1_1_1" class="form-label">Intermediate Outcomes</label>
                                            <textarea name="intermediate_outcomes_1_1_1" class="form-control" rows="3" required></textarea>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="addActivity(1)">Add Activity</button>
                                    <button type="button" class="btn btn-danger btn-sm d-none remove-activity" onclick="removeActivity(this)">Remove</button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="not_happened_1" class="form-label">What Did Not Happen?</label>
                                <textarea name="not_happened_1" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="why_not_happened_1" class="form-label">Explain Why Some Activities Could Not Be Undertaken</label>
                                <textarea name="why_not_happened_1" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="changes_1" class="form-label">Have You Made Any Changes in the Project Such as New Activities or Modified the Activities Contextually?</label>
                                <div>
                                    <input type="radio" name="changes_1" value="yes" onclick="toggleWhyChanges(this, 1)" required> Yes
                                    <input type="radio" name="changes_1" value="no" onclick="toggleWhyChanges(this, 1)" required> No
                                </div>
                            </div>
                            <div class="mb-3 d-none" id="why_changes_container_1">
                                <label for="why_changes_1" class="form-label">Explain Why the Changes Were Needed</label>
                                <textarea name="why_changes_1" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="lessons_learnt_1" class="form-label">What Are the Lessons Learnt?</label>
                                <textarea name="lessons_learnt_1" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="todo_lessons_learnt_1" class="form-label">What Will Be Done Differently Because of the Learnings?</label>
                                <textarea name="todo_lessons_learnt_1" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="addObjective()">Add More Objective</button>
                <!-- Objectives Section ends -->

                <div id="outlook-container">
                    <div class="mb-3 card outlook" data-index="1">
                        <div class="card-header">
                            <h4>3. Outlook</h4>
                        </div>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            Outlook 1
                            <button type="button" class="btn btn-danger btn-sm d-none remove-outlook" onclick="removeOutlook(this)">Remove</button>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="date_1" class="form-label">Date</label>
                                <input type="date" name="date_1" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="plan_next_month_1" class="form-label">Action Plan for Next Month</label>
                                <textarea name="plan_next_month_1" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="addOutlook()">Add More Outlook</button>

                <!-- Outlook Section ends -->

                <div class="mb-3 card">
                    <div class="card-header">
                        <h4>4. Statements of Account</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="account_period" class="form-label">Account Statement Period:</label>
                            <div class="d-flex">
                                <input type="date" name="account_period_start" class="form-control" required>
                                <span class="mx-2">to</span>
                                <input type="date" name="account_period_end" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="amount_sanctioned" class="form-label">Amount Sanctioned: Rs.</label>
                            <input type="number" name="amount_sanctioned" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="amount_forwarded" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
                            <input type="number" name="amount_forwarded" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="total_amount" class="form-label">Total Amount: Rs.</label>
                            <input type="number" name="total_amount" class="form-control" required>
                        </div>

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Particulars</th>
                                    <th>Amount Forwarded from the Previous Year</th>
                                    <th>Amount Sanctioned Current Year</th>
                                    <th>Total Amount (2+3)</th>
                                    <th>Expenses Up to Last Month</th>
                                    <th>Expenses of This Month</th>
                                    <th>Total Expenses (5+6)</th>
                                    <th>Balance Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="account-rows">
                                <tr>
                                    <td><input type="text" name="particulars[]" class="form-control" required></td>
                                    <td><input type="number" name="amount_forwarded[]" class="form-control" required></td>
                                    <td><input type="number" name="amount_sanctioned[]" class="form-control" required></td>
                                    <td><input type="number" name="total_amount[]" class="form-control" required readonly></td>
                                    <td><input type="number" name="expenses_last_month[]" class="form-control" required></td>
                                    <td><input type="number" name="expenses_this_month[]" class="form-control" required></td>
                                    <td><input type="number" name="total_expenses[]" class="form-control" required readonly></td>
                                    <td><input type="number" name="balance_amount[]" class="form-control" required readonly></td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th><input type="number" id="total_forwarded" class="form-control" readonly></th>
                                    <th><input type="number" id="total_sanctioned" class="form-control" readonly></th>
                                    <th><input type="number" id="total_amount_total" class="form-control" readonly></th>
                                    <th><input type="number" id="total_expenses_last_month" class="form-control" readonly></th>
                                    <th><input type="number" id="total_expenses_this_month" class="form-control" readonly></th>
                                    <th><input type="number" id="total_expenses_total" class="form-control" readonly></th>
                                    <th><input type="number" id="total_balance" class="form-control" readonly></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                        <button type="button" class="btn btn-primary" onclick="addAccountRow()">Add Row</button>

                        <div class="mt-3">
                            <label for="total_balance_forwarded" class="form-label">Total Balance Amount Forwarded for the Following Month: Rs.</label>
                            <input type="number" name="total_balance_forwarded" class="form-control" required>
                        </div>
                    </div>
                </div>

                <!-- Statements of Account Section ends -->

                <div class="mb-3 card">
                    <div class="card-header">
                        <h4>5. Photos</h4>
                    </div>
                    <div class="card-body">
                        <div id="photos-container">
                            <div class="mb-3 photo-group" data-index="1">
                                <label for="photo_1" class="form-label">Photo 1</label>
                                <input type="file" name="photos[]" class="mb-2 form-control" accept="image/*" onchange="checkFileSize(this)" required>
                                <textarea name="photo_descriptions[]" class="form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" required></textarea>
                                <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
                            </div>
                        </div>
                        <button type="button" class="mt-3 btn btn-primary" onclick="addPhoto()">Add More Photo</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary me-2">Submit Report</button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleEducationField(checkbox, ...fieldIds) {
        fieldIds.forEach(id => {
            const field = document.getElementById(id);
            if (checkbox.checked) {
                field.classList.remove('d-none');
                field.setAttribute('required', 'required');
            } else {
                field.classList.add('d-none');
                field.removeAttribute('required');
            }
        });
    }

    function toggleWhyChanges(radio, index) {
        const container = document.getElementById(`why_changes_container_${index}`);
        if (radio.value === 'yes') {
            container.classList.remove('d-none');
            container.querySelector('textarea').setAttribute('required', 'required');
        } else {
            container.classList.add('d-none');
            container.querySelector('textarea').removeAttribute('required');
        }
    }

    function addObjective() {
        const objectivesContainer = document.getElementById('objectives-container');
        const index = objectivesContainer.children.length + 1;
        const objectiveTemplate = `
            <div class="mb-3 card objective" data-index="${index}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Objective ${index}
                    <button type="button" class="btn btn-danger btn-sm remove-objective" onclick="removeObjective(this)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="expected_outcome_${index}" class="form-label">Expected Outcome</label>
                        <textarea name="expected_outcome_${index}" class="form-control" rows="2" required></textarea>
                    </div>
                    <h4>Monthly Summary</h4>
                    <div class="monthly-summary-container" data-index="${index}">
                        <div class="mb-3 card activity" data-activity-index="1">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="form-group">
                                    <label for="month_${index}_1" class="form-label">Month</label>
                                    <select name="month_${index}_1" class="form-control" required>
                                        <option value="" disabled selected>Select Month</option>
                                        ${['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'].map(month => `<option value="${month}">${month}</option>`).join('')}
                                    </select>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm remove-activity" onclick="removeActivity(this)">Remove</button>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="summary_activities_${index}_1_1" class="form-label">Summary of Activities</label>
                                    <textarea name="summary_activities_${index}_1_1" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="qualitative_quantitative_data_${index}_1_1" class="form-label">Qualitative & Quantitative Data</label>
                                    <textarea name="qualitative_quantitative_data_${index}_1_1" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="intermediate_outcomes_${index}_1_1" class="form-label">Intermediate Outcomes</label>
                                    <textarea name="intermediate_outcomes_${index}_1_1" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="addActivity(${index})">Add Activity</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="not_happened_${index}" class="form-label">What Did Not Happen?</label>
                        <textarea name="not_happened_${index}" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="why_not_happened_${index}" class="form-label">Explain Why Some Activities Could Not Be Undertaken</label>
                        <textarea name="why_not_happened_${index}" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="changes_${index}" class="form-label">Have You Made Any Changes in the Project Such as New Activities or Modified the Activities Contextually?</label>
                        <div>
                            <input type="radio" name="changes_${index}" value="yes" onclick="toggleWhyChanges(this, ${index})" required> Yes
                            <input type="radio" name="changes_${index}" value="no" onclick="toggleWhyChanges(this, ${index})" required> No
                        </div>
                    </div>
                    <div class="mb-3 d-none" id="why_changes_container_${index}">
                        <label for="why_changes_${index}" class="form-label">Explain Why the Changes Were Needed</label>
                        <textarea name="why_changes_${index}" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="lessons_learnt_${index}" class="form-label">What Are the Lessons Learnt?</label>
                        <textarea name="lessons_learnt_${index}" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="todo_lessons_learnt_${index}" class="form-label">What Will Be Done Differently Because of the Learnings?</label>
                        <textarea name="todo_lessons_learnt_${index}" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
            </div>
        `;
        objectivesContainer.insertAdjacentHTML('beforeend', objectiveTemplate);
        updateRemoveButtons();
    }

    function addActivity(objectiveIndex) {
        const monthlySummaryContainer = document.querySelector(`.monthly-summary-container[data-index="${objectiveIndex}"]`);
        const activityIndex = monthlySummaryContainer.children.length + 1;
        const activityTemplate = `
            <div class="mb-3 card activity" data-activity-index="${activityIndex}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="form-group">
                        <label for="month_${objectiveIndex}_${activityIndex}" class="form-label">Month</label>
                        <select name="month_${objectiveIndex}_${activityIndex}" class="form-control" required>
                            <option value="" disabled selected>Select Month</option>
                            ${['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'].map(month => `<option value="${month}">${month}</option>`).join('')}
                        </select>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm remove-activity" onclick="removeActivity(this)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="summary_activities_${objectiveIndex}_${activityIndex}_1" class="form-label">Summary of Activities</label>
                        <textarea name="summary_activities_${objectiveIndex}_${activityIndex}_1" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="qualitative_quantitative_data_${objectiveIndex}_${activityIndex}_1" class="form-label">Qualitative & Quantitative Data</label>
                        <textarea name="qualitative_quantitative_data_${objectiveIndex}_${activityIndex}_1" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="intermediate_outcomes_${objectiveIndex}_${activityIndex}_1" class="form-label">Intermediate Outcomes</label>
                        <textarea name="intermediate_outcomes_${objectiveIndex}_${activityIndex}_1" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-sm" onclick="addActivity(${objectiveIndex})">Add Activity</button>
            </div>
        `;
        monthlySummaryContainer.insertAdjacentHTML('beforeend', activityTemplate);
        updateRemoveButtons();
    }

    function removeObjective(button) {
        const objective = button.closest('.objective');
        objective.remove();
        updateRemoveButtons();
    }

    function removeActivity(button) {
        const activity = button.closest('.activity');
        activity.remove();
        updateRemoveButtons();
    }

    function updateRemoveButtons() {
        const objectives = document.querySelectorAll('.objective');
        objectives.forEach((objective, index) => {
            const removeButton = objective.querySelector('.remove-objective');
            if (index === 0) {
                removeButton.classList.add('d-none');
            } else {
                removeButton.classList.remove('d-none');
            }

            const activities = objective.querySelectorAll('.activity');
            activities.forEach((activity, activityIndex) => {
                const removeActivityButton = activity.querySelector('.remove-activity');
                if (activityIndex === 0) {
                    removeActivityButton.classList.add('d-none');
                } else {
                    removeActivityButton.classList.remove('d-none');
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateRemoveButtons();
    });

    // Outlook Section
    function addOutlook() {
        const outlookContainer = document.getElementById('outlook-container');
        const index = outlookContainer.children.length + 1;
        const outlookTemplate = `
            <div class="mb-3 card outlook" data-index="${index}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Outlook ${index}
                    <button type="button" class="btn btn-danger btn-sm remove-outlook" onclick="removeOutlook(this)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="date_${index}" class="form-label">Date</label>
                        <input type="date" name="date_${index}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="plan_next_month_${index}" class="form-label">Action Plan for Next Month</label>
                        <textarea name="plan_next_month_${index}" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
            </div>
        `;
        outlookContainer.insertAdjacentHTML('beforeend', outlookTemplate);
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

    document.addEventListener('DOMContentLoaded', function() {
        updateOutlookRemoveButtons();
    });

    // Statements of Account Section
    document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('.table tbody');

    table.addEventListener('input', function(event) {
        const row = event.target.closest('tr');
        calculateRowTotals(row);
        calculateTotal();
    });
});

function calculateRowTotals(row) {
    const amountForwarded = parseFloat(row.querySelector('[name="amount_forwarded[]"]').value) || 0;
    const amountSanctioned = parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
    const expensesLastMonth = parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
    const expensesThisMonth = parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;

    const totalAmount = amountForwarded + amountSanctioned;
    const totalExpenses = expensesLastMonth + expensesThisMonth;
    const balanceAmount = totalAmount - totalExpenses;

    row.querySelector('[name="total_amount[]"]').value = totalAmount.toFixed(2);
    row.querySelector('[name="total_expenses[]"]').value = totalExpenses.toFixed(2);
    row.querySelector('[name="balance_amount[]"]').value = balanceAmount.toFixed(2);
}

function calculateTotal() {
    const rows = document.querySelectorAll('#account-rows tr');
    let totalForwarded = 0;
    let totalSanctioned = 0;
    let totalAmountTotal = 0;
    let totalExpensesLastMonth = 0;
    let totalExpensesThisMonth = 0;
    let totalExpensesTotal = 0;
    let totalBalance = 0;

    rows.forEach(row => {
        totalForwarded += parseFloat(row.querySelector('[name="amount_forwarded[]"]').value) || 0;
        totalSanctioned += parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
        totalAmountTotal += parseFloat(row.querySelector('[name="total_amount[]"]').value) || 0;
        totalExpensesLastMonth += parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
        totalExpensesThisMonth += parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;
        totalExpensesTotal += parseFloat(row.querySelector('[name="total_expenses[]"]').value) || 0;
        totalBalance += parseFloat(row.querySelector('[name="balance_amount[]"]').value) || 0;
    });

    document.getElementById('total_forwarded').value = totalForwarded.toFixed(2);
    document.getElementById('total_sanctioned').value = totalSanctioned.toFixed(2);
    document.getElementById('total_amount_total').value = totalAmountTotal.toFixed(2);
    document.getElementById('total_expenses_last_month').value = totalExpensesLastMonth.toFixed(2);
    document.getElementById('total_expenses_this_month').value = totalExpensesThisMonth.toFixed(2);
    document.getElementById('total_expenses_total').value = totalExpensesTotal.toFixed(2);
    document.getElementById('total_balance').value = totalBalance.toFixed(2);
}

function addAccountRow() {
    const tableBody = document.getElementById('account-rows');
    const newRow = document.createElement('tr');

    newRow.innerHTML = `
        <td><input type="text" name="particulars[]" class="form-control" required></td>
        <td><input type="number" name="amount_forwarded[]" class="form-control" required></td>
        <td><input type="number" name="amount_sanctioned[]" class="form-control" required></td>
        <td><input type="number" name="total_amount[]" class="form-control" required readonly></td>
        <td><input type="number" name="expenses_last_month[]" class="form-control" required></td>
        <td><input type="number" name="expenses_this_month[]" class="form-control" required></td>
        <td><input type="number" name="total_expenses[]" class="form-control" required readonly></td>
        <td><input type="number" name="balance_amount[]" class="form-control" required readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button></td>
    `;

    newRow.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            const row = input.closest('tr');
            calculateRowTotals(row);
            calculateTotal();
        });
    });

    tableBody.appendChild(newRow);
}

function removeAccountRow(button) {
    const row = button.closest('tr');
    row.remove();
    calculateTotal(); // Recalculate totals after removing a row
}


    // Photo and Description Section
    function addPhoto() {
        const photosContainer = document.getElementById('photos-container');
        const currentPhotos = photosContainer.children.length;

        if (currentPhotos < 10) {
            const index = currentPhotos + 1;
            const photoTemplate = `
                <div class="mb-3 photo-group" data-index="${index}">
                    <label for="photo_${index}" class="form-label">Photo ${index}</label>
                    <input type="file" name="photos[]" class="mb-2 form-control" accept="image/*" onchange="checkFileSize(this)" required>
                    <textarea name="photo_descriptions[]" class="form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" required></textarea>
                    <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
                </div>
            `;
            photosContainer.insertAdjacentHTML('beforeend', photoTemplate);
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
        if (file && file.size > 3 * 1024 * 1024) { // 3 MB
            alert('Each photo must be less than 3 MB.');
            input.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updatePhotoLabels();
    });
</script>

<style>
    .table th, .table td {
        vertical-align: middle;
        text-align: center;
        padding: 0; /* Disable padding */
    }

    .table th {
        white-space: normal; /* Allow text wrapping in the header */
    }

    .table td input {
        width: 100%;
        box-sizing: border-box;
        -moz-appearance: textfield; /* Disable number input arrows */
        padding: 0.375rem 0.75rem; /* Adjust the padding of the input */
    }

    .table td input::-webkit-outer-spin-button,
    .table td input::-webkit-inner-spin-button {
        -webkit-appearance: none; /* Disable number input arrows */
        margin: 0;
    }

    .table-container {
        overflow-x: auto;
    }
    .fp-text-center1 {
            text-align: center;

            margin-bottom: 15px; /* Adjust the value as needed */
    }
    .fp-text-margin {
            margin-bottom: 15px; /* Adjust the value as needed */
    }
</style>
@endsection
