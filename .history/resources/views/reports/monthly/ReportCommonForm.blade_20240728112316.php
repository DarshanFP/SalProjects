@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <form action="{{ route('monthly.report.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->project_id }}">

                <div class="mb-3 card">
                    <div class="card-header">
                        <h4 class="fp-text-center1">TRACKING DEVELOPMENT PROJECT</h4>
                        <h4 class="fp-text-center1">MONTHLY PROGRESS REPORT (common)</h4>
                    </div>
                    <div class="card-header">
                        <h4 class="fp-text-margin">Basic Information</h4>
                    </div>

                    <div class="card-body">
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
                            <label for="commencement_month_year" class="form-label">Commencement Month & Year</label>
                            <input type="text" name="commencement_month_year" class="form-control readonly-input" value="{{ $project->commencement_month_year }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="in_charge" class="form-label">Sister/s In-Charge</label>
                            <input type="text" name="in_charge" class="form-control readonly-input" value="{{ $user->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="total_beneficiaries" class="form-label">Total No. of Beneficiaries</label>
                            <input type="number" name="total_beneficiaries" class="form-control" style="background-color: #6571ff;" required>
                        </div>
                        <div class="mb-3">
                            <label for="report_month_year" class="form-label">Reporting Month & Year</label>
                            <div class="d-flex">
                                <select name="report_month" id="report_month" class="form-control select-input me-2" style="background-color: #6571ff;" required>
                                    <option value="" disabled selected>Select Month</option>
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}" {{ old('report_month') == $month ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                                    @endforeach
                                </select>
                                <select name="report_year" id="report_year" class="form-control select-input" style="background-color: #6571ff;" required>
                                    <option value="" disabled selected>Select Year</option>
                                    @for ($year = date('Y'); $year >= 1900; $year--)
                                        <option value="{{ $year }}" {{ old('report_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
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
                            <textarea name="goal" class="form-control" rows="3" readonly>{{ old('goal', $project->goal) }}</textarea>
                        </div>
                    </div>
                </div>

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
                                <label for="objective[1]" class="form-label">Objective</label>
                                <textarea name="objective[1]" class="form-control" rows="2" style="background-color: #6571ff;" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="expected_outcome[1]" class="form-label">Expected Outcome</label>
                                <textarea name="expected_outcome[1]" class="form-control" rows="2" style="background-color: #6571ff;" required></textarea>
                            </div>
                            <h4>Monthly Summary</h4>
                            <div class="monthly-summary-container" data-index="1">
                                <div class="mb-3 card activity" data-activity-index="1">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="form-group">
                                            <label for="month[1][1]" class="form-label">Month</label>
                                            <select name="month[1][1]" class="form-control" style="background-color: #6571ff;" required>
                                                <option value="" disabled selected>Select Month</option>
                                                @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                                                    <option value="{{ $month }}">{{ $month }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="summary_activities[1][1][1]" class="form-label">Summary of Activities Undertaken During the Month</label>
                                            <textarea name="summary_activities[1][1][1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="qualitative_quantitative_data[1][1][1]" class="form-label">Qualitative & Quantitative Data</label>
                                            <textarea name="qualitative_quantitative_data[1][1][1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="intermediate_outcomes[1][1][1]" class="form-label">Intermediate Outcomes</label>
                                            <textarea name="intermediate_outcomes[1][1][1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="addActivity(1)">Add Activity</button>
                                    <button type="button" class="btn btn-danger btn-sm d-none remove-activity" onclick="removeActivity(this)">Remove</button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="not_happened[1]" class="form-label">What Did Not Happen?</label>
                                <textarea name="not_happened[1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="why_not_happened[1]" class="form-label">Explain Why Some Activities Could Not Be Undertaken</label>
                                <textarea name="why_not_happened[1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="changes[1]" class="form-label">Have You Made Any Changes in the Project Such as New Activities or Modified the Activities Contextually?</label>
                                <div>
                                    <input type="radio" name="changes[1]" value="yes" onclick="toggleWhyChanges(this, 1)" required> Yes
                                    <input type="radio" name="changes[1]" value="no" onclick="toggleWhyChanges(this, 1)" required> No
                                </div>
                            </div>
                            <div class="mb-3 d-none" id="why_changes_container_1">
                                <label for="why_changes[1]" class="form-label">Explain Why the Changes Were Needed</label>
                                <textarea name="why_changes[1]" class="form-control" rows="3" style="background-color: #6571ff;"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="lessons_learnt[1]" class="form-label">What Are the Lessons Learnt?</label>
                                <textarea name="lessons_learnt[1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="todo_lessons_learnt[1]" class="form-label">What Will Be Done Differently Because of the Learnings?</label>
                                <textarea name="todo_lessons_learnt[1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="addObjective()">Add More Objective</button>

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
                                <label for="date[1]" class="form-label">Date</label>
                                <input type="date" name="date[1]" class="form-control fp-custom-date-input" style="background-color: #6571ff;" required>
                            </div>
                            <div class="mb-3">
                                <label for="plan_next_month[1]" class="form-label">Action Plan for Next Month</label>
                                <textarea name="plan_next_month[1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="addOutlook()">Add More Outlook</button>

                <div class="mb-3 card">
                    <div class="card-header">
                        <h4>4. Statements of Account</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="account_period" class="form-label">Account Statement Period:</label>
                            <div class="d-flex">
                                <input type="date" name="account_period_start" class="form-control" style="background-color: #6571ff;" required>
                                <span class="mx-2">to</span>
                                <input type="date" name="account_period_end" class="form-control" style="background-color: #6571ff;" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="amount_sanctioned_overview" class="form-label">Amount Sanctioned: Rs.</label>
                            <input type="number" name="amount_sanctioned_overview" class="form-control readonly-input" value="{{ $project->amount_sanctioned }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="amount_forwarded_overview" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
                            <input type="number" name="amount_forwarded_overview" class="form-control readonly-input" value="{{ $project->amount_forwarded }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="amount_in_hand" class="form-label">Total Amount: Rs.</label>
                            <input type="number" name="amount_in_hand" class="form-control readonly-input" value="{{ $project->amount_sanctioned + $project->amount_forwarded }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="expenses_up_to_last_month" class="form-label">Expenses Up to Last Month: Rs.</label>
                            <input type="number" name="expenses_up_to_last_month" class="form-control readonly-input" value="{{ $project->expenses_up_to_last_month }}" readonly>
                        </div>
                        <div class="mb-3">

                          <!-- Debugging statement to display fetched expenses -->
                        <pre>{{ print_r($expensesUpToLastMonth, true) }}</pre>

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
                                @foreach($budgets as $index => $budget)
<tr>
    <td><input type="text" name="particulars[]" class="form-control" value="{{ $budget->particular }}" style="background-color: #6571ff;" required></td>
    <td><input type="number" name="amount_forwarded[]" class="form-control" value="{{ $budget->amount_forwarded ?? 0 }}" readonly></td>
    <td><input type="number" name="amount_sanctioned[]" class="form-control" value="{{ $budget->this_phase }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
    <td><input type="number" name="total_amount[]" class="form-control" value="{{ $budget->amount_forwarded + $budget->this_phase }}" readonly></td>
    <td><input type="number" name="expenses_last_month[]" class="form-control" value="{{ $expensesUpToLastMonth[$index] ?? 0 }}" readonly></td>
    <td><input type="number" name="expenses_this_month[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;" required></td>
    <td><input type="number" name="total_expenses[]" class="form-control" readonly></td>
    <td><input type="number" name="balance_amount[]" class="form-control" readonly></td>
    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button></td>
</tr>
@endforeach

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
                            <input type="number" name="total_balance_forwarded" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <div class="mb-3 card">
                    <div class="card-header">
                        <h4>5. Photos</h4>
                    </div>
                    <div class="card-body">
                        <div id="photos-container">
                            <div class="mb-3 photo-group" data-index="1">
                                <label for="photo_1" class="form-label">Photo 1</label>
                                <input type="file" name="photos[]" class="mb-2 form-control" accept="image/*" onchange="checkFileSize(this)" style="background-color: #6571ff;">
                                <textarea name="photo_descriptions[]" class="form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #6571ff;"></textarea>
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
                        <label for="objective[${index}]" class="form-label">Objective</label>
                        <textarea name="objective[${index}]" class="form-control" rows="2" style="background-color: #6571ff;" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="expected_outcome[${index}]" class="form-label">Expected Outcome</label>
                        <textarea name="expected_outcome[${index}]" class="form-control" rows="2" style="background-color: #6571ff;" required></textarea>
                    </div>
                    <h4>Monthly Summary</h4>
                    <div class="monthly-summary-container" data-index="${index}">
                        <div class="mb-3 card activity" data-activity-index="1">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="form-group">
                                    <label for="month[${index}][1]" class="form-label">Month</label>
                                    <select name="month[${index}][1]" class="form-control" style="background-color: #6571ff;" required>
                                        <option value="" disabled selected>Select Month</option>
                                        ${['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'].map(month => `<option value="${month}">${month}</option>`).join('')}
                                    </select>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm remove-activity" onclick="removeActivity(this)">Remove</button>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="summary_activities[${index}][1][1]" class="form-label">Summary of Activities</label>
                                    <textarea name="summary_activities[${index}][1][1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="qualitative_quantitative_data[${index}][1][1]" class="form-label">Qualitative & Quantitative Data</label>
                                    <textarea name="qualitative_quantitative_data[${index}][1][1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="intermediate_outcomes[${index}][1][1]" class="form-label">Intermediate Outcomes</label>
                                    <textarea name="intermediate_outcomes[${index}][1][1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="addActivity(${index})">Add Activity</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="not_happened[${index}]" class="form-label">What Did Not Happen?</label>
                        <textarea name="not_happened[${index}]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="why_not_happened[${index}]" class="form-label">Explain Why Some Activities Could Not Be Undertaken</label>
                        <textarea name="why_not_happened[${index}]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="changes[${index}]" class="form-label">Have You Made Any Changes in the Project Such as New Activities or Modified the Activities Contextually?</label>
                        <div>
                            <input type="radio" name="changes[${index}]" value="yes" onclick="toggleWhyChanges(this, ${index})" required> Yes
                            <input type="radio" name="changes[${index}]" value="no" onclick="toggleWhyChanges(this, ${index})" required> No
                        </div>
                    </div>
                    <div class="mb-3 d-none" id="why_changes_container_${index}">
                        <label for="why_changes[${index}]" class="form-label">Explain Why the Changes Were Needed</label>
                        <textarea name="why_changes[${index}]" class="form-control" rows="3" style="background-color: #6571ff;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="lessons_learnt[${index}]" class="form-label">What Are the Lessons Learnt?</label>
                        <textarea name="lessons_learnt[${index}]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="todo_lessons_learnt[${index}]" class="form-label">What Will Be Done Differently Because of the Learnings?</label>
                        <textarea name="todo_lessons_learnt[${index}]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
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
                        <label for="month[${objectiveIndex}][${activityIndex}]" class="form-label">Month</label>
                        <select name="month[${objectiveIndex}][${activityIndex}]" class="form-control" style="background-color: #6571ff;" required>
                            <option value="" disabled selected>Select Month</option>
                            ${['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'].map(month => `<option value="${month}">${month}</option>`).join('')}
                        </select>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm remove-activity" onclick="removeActivity(this)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="summary_activities[${objectiveIndex}][${activityIndex}][1]" class="form-label">Summary of Activities</label>
                        <textarea name="summary_activities[${objectiveIndex}][${activityIndex}][1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="qualitative_quantitative_data[${objectiveIndex}][${activityIndex}][1]" class="form-label">Qualitative & Quantitative Data</label>
                        <textarea name="qualitative_quantitative_data[${objectiveIndex}][${activityIndex}][1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="intermediate_outcomes[${objectiveIndex}][${activityIndex}][1]" class="form-label">Intermediate Outcomes</label>
                        <textarea name="intermediate_outcomes[${objectiveIndex}][${activityIndex}][1]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
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
                        <label for="date[${index}]" class="form-label">Date</label>
                        <input type="date" name="date[${index}]" class="form-control fp-custom-date-input" style="background-color: #6571ff;" required>
                    </div>
                    <div class="mb-3">
                        <label for="plan_next_month[${index}]" class="form-label">Action Plan for Next Month</label>
                        <textarea name="plan_next_month[${index}]" class="form-control" rows="3" style="background-color: #6571ff;" required></textarea>
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

    document.addEventListener('DOMContentLoaded', function() {
        const table = document.querySelector('.table tbody');

        table.addEventListener('input', function(event) {
            const row = event.target.closest('tr');
            calculateRowTotals(row);
            calculateTotal();
        });

        const prjctAmountSanctioned = document.querySelector('[name="amount_sanctioned_overview"]');
        const lyAmountForwarded = document.querySelector('[name="amount_forwarded_overview"]');

        prjctAmountSanctioned.addEventListener('input', calculateTotalAmount);
        lyAmountForwarded.addEventListener('input', calculateTotalAmount);
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

        calculateTotal(); // Recalculate totals whenever a row total is updated
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

        // Update the total balance forwarded field
        document.querySelector('[name="total_balance_forwarded"]').value = totalBalance.toFixed(2);
    }

    function addAccountRow() {
        const tableBody = document.getElementById('account-rows');
        const newRow = document.createElement('tr');

        newRow.innerHTML = `
            <td><input type="text" name="particulars[]" class="form-control" style="background-color: #6571ff;" required></td>
            <td><input type="number" name="amount_forwarded[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;" required></td>
            <td><input type="number" name="amount_sanctioned[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;" required></td>
            <td><input type="number" name="total_amount[]" class="form-control" readonly></td>
            <td><input type="number" name="expenses_last_month[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
            <td><input type="number" name="expenses_this_month[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;" required></td>
            <td><input type="number" name="total_expenses[]" class="form-control" readonly></td>
            <td><input type="number" name="balance_amount[]" class="form-control" readonly></td>
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

    function calculateTotalAmount() {
        const amountSanctioned = parseFloat(document.querySelector('[name="amount_sanctioned_overview"]').value) || 0;
        const amountForwarded = parseFloat(document.querySelector('[name="amount_forwarded_overview"]').value) || 0;
        const totalAmount = amountSanctioned + amountForwarded;

        document.querySelector('[name="amount_in_hand"]').value = totalAmount.toFixed(2);
    }

    function addPhoto() {
        const photosContainer = document.getElementById('photos-container');
        const currentPhotos = photosContainer.children.length;

        if (currentPhotos < 10) {
            const index = currentPhotos + 1;
            const photoTemplate = `
                <div class="mb-3 photo-group" data-index="${index}">
                    <label for="photo_${index}" class="form-label">Photo ${index}</label>
                    <input type="file" name="photos[]" class="mb-2 form-control" accept="image/*" onchange="checkFileSize(this)" style="background-color: #6571ff;">
                    <textarea name="photo_descriptions[]" class="form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #6571ff;"></textarea>
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
    .readonly-input {
        background-color: #0D1427;
        color: #f4f0f0;
    }

    .select-input {
        background-color: #0e285c;
        color: #f4f0f0;
    }

    .readonly-select {
        background-color: #072c75;
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
@endsection
