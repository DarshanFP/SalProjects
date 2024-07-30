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
                            <label for="commencement_month_year" class="form-label">Commencement Month & Year</label>
                            <input type="text" name="commencement_month_year" class="form-control readonly-input" value="{{ $project->commencement_month_year }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="in_charge" class="form-label">Sister/s In-Charge</label>
                            <input type="text" name="in_charge" class="form-control readonly-input" value="{{ $user->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="total_beneficiaries" class="form-label">Total No. of Beneficiaries</label>
                            <input type="number" name="total_beneficiaries" class="form-control @error('total_beneficiaries') is-invalid @enderror" style="background-color: #6571ff;">
                            @error('total_beneficiaries')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="report_month_year" class="form-label">Reporting Month & Year</label>
                            <div class="d-flex">
                                <select name="report_month" id="report_month" class="form-control select-input me-2 @error('report_month') is-invalid @enderror" style="background-color: #6571ff;">
                                    <option value="" disabled selected>Select Month</option>
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}" {{ old('report_month') == $month ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                                    @endforeach
                                </select>
                                @error('report_month')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <select name="report_year" id="report_year" class="form-control select-input @error('report_year') is-invalid @enderror" style="background-color: #6571ff;">
                                    <option value="" disabled selected>Select Year</option>
                                    @for ($year = date('Y'); $year >= 1900; $year--)
                                        <option value="{{ $year }}" {{ old('report_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endfor
                                </select>
                                @error('report_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Information Section -->
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
                                <label for="objective[1]" class="form-label">Objective</label>
                                <textarea name="objective[1]" class="form-control @error('objective.1') is-invalid @enderror" rows="2" style="background-color: #6571ff;"></textarea>
                                @error('objective.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="expected_outcome[1]" class="form-label">Expected Outcome</label>
                                <textarea name="expected_outcome[1]" class="form-control @error('expected_outcome.1') is-invalid @enderror" rows="2" style="background-color: #6571ff;"></textarea>
                                @error('expected_outcome.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <h4>Monthly Summary</h4>
                            <div class="monthly-summary-container" data-index="1">
                                <div class="mb-3 card activity" data-activity-index="1">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="form-group">
                                            <label for="month[1][1]" class="form-label">Month</label>
                                            <select name="month[1][1]" class="form-control @error('month.1.1') is-invalid @enderror" style="background-color: #6571ff;">
                                                <option value="" disabled selected>Select Month</option>
                                                @foreach(range(1, 12) as $month)
        <option value="{{ $month }}" {{ old('month.1.1') == $month ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
    @endforeach
                                            </select>
                                            @error('month.1.1')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="summary_activities[1][1][1]" class="form-label">Summary of Activities Undertaken During the Month</label>
                                            <textarea name="summary_activities[1][1][1]" class="form-control @error('summary_activities.1.1.1') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                                            @error('summary_activities.1.1.1')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="qualitative_quantitative_data[1][1][1]" class="form-label">Qualitative & Quantitative Data</label>
                                            <textarea name="qualitative_quantitative_data[1][1][1]" class="form-control @error('qualitative_quantitative_data.1.1.1') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                                            @error('qualitative_quantitative_data.1.1.1')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="intermediate_outcomes[1][1][1]" class="form-label">Intermediate Outcomes</label>
                                            <textarea name="intermediate_outcomes[1][1][1]" class="form-control @error('intermediate_outcomes.1.1.1') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                                            @error('intermediate_outcomes.1.1.1')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="addActivity(1)">Add Activity</button>
                                    <button type="button" class="btn btn-danger btn-sm d-none remove-activity" onclick="removeActivity(this)">Remove</button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="not_happened[1]" class="form-label">What Did Not Happen?</label>
                                <textarea name="not_happened[1]" class="form-control @error('not_happened.1') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                                @error('not_happened.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="why_not_happened[1]" class="form-label">Explain Why Some Activities Could Not Be Undertaken</label>
                                <textarea name="why_not_happened[1]" class="form-control @error('why_not_happened.1') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                                @error('why_not_happened.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="changes[1]" class="form-label">Have You Made Any Changes in the Project Such as New Activities or Modified the Activities Contextually?</label>
                                <div>
                                    <input type="radio" name="changes[1]" value="yes" onclick="toggleWhyChanges(this, 1)"> Yes
                                    <input type="radio" name="changes[1]" value="no" onclick="toggleWhyChanges(this, 1)"> No
                                </div>
                            </div>
                            <div class="mb-3 d-none" id="why_changes_container_1">
                                <label for="why_changes[1]" class="form-label">Explain Why the Changes Were Needed</label>
                                <textarea name="why_changes[1]" class="form-control" rows="3" style="background-color: #6571ff;"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="lessons_learnt[1]" class="form-label">What Are the Lessons Learnt?</label>
                                <textarea name="lessons_learnt[1]" class="form-control @error('lessons_learnt.1') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                                @error('lessons_learnt.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="todo_lessons_learnt[1]" class="form-label">What Will Be Done Differently Because of the Learnings?</label>
                                <textarea name="todo_lessons_learnt[1]" class="form-control @error('todo_lessons_learnt.1') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                                @error('todo_lessons_learnt.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="addObjective()">Add More Objective</button>

                <!-- Outlook Section  -->
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
                                <input type="date" name="date[1]" class="form-control @error('date.1') is-invalid @enderror" style="background-color: #6571ff;">
                                @error('date.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="plan_next_month[1]" class="form-label">Action Plan for Next Month</label>
                                <textarea name="plan_next_month[1]" class="form-control @error('plan_next_month.1') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                                @error('plan_next_month.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="addOutlook()">Add More Outlook</button>

                <!-- Include Statements of Account Partial -->
                @include('reports.monthly.partials.statements_of_account', ['budgets' => $budgets, 'lastExpenses' => $lastExpenses])

                <!-- Photos Section -->
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
            <td><input type="text" name="particulars[]" class="form-control @error('particulars[]') is-invalid @enderror" style="background-color: #6571ff;"></td>
            <td><input type="number" name="amount_forwarded[]" class="form-control @error('amount_forwarded[]') is-invalid @enderror" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;"></td>
            <td><input type="number" name="amount_sanctioned[]" class="form-control @error('amount_sanctioned[]') is-invalid @enderror" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;"></td>
            <td><input type="number" name="total_amount[]" class="form-control" readonly></td>
            <td><input type="number" name="expenses_last_month[]" class="form-control @error('expenses_last_month[]') is-invalid @enderror" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;"></td>
            <td><input type="number" name="expenses_this_month[]" class="form-control @error('expenses_this_month[]') is-invalid @enderror" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;"></td>
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

    function addObjective() {
        const container = document.getElementById('objectives-container');
        const newIndex = container.children.length + 1;

        const newObjective = document.createElement('div');
        newObjective.className = 'mb-3 card objective';
        newObjective.dataset.index = newIndex;

        newObjective.innerHTML = `
            <div class="card-header">
                <h4>2. Activities and Intermediate Outcomes</h4>
            </div>
            <div class="card-header d-flex justify-content-between align-items-center">
                Objective ${newIndex}
                <button type="button" class="btn btn-danger btn-sm remove-objective" onclick="removeObjective(this)">Remove</button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="objective[${newIndex}]" class="form-label">Objective</label>
                    <textarea name="objective[${newIndex}]" class="form-control @error('objective.${newIndex}') is-invalid @enderror" rows="2" style="background-color: #6571ff;"></textarea>
                    @error('objective.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="expected_outcome[${newIndex}]" class="form-label">Expected Outcome</label>
                    <textarea name="expected_outcome[${newIndex}]" class="form-control @error('expected_outcome.${newIndex}') is-invalid @enderror" rows="2" style="background-color: #6571ff;"></textarea>
                    @error('expected_outcome.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <h4>Monthly Summary</h4>
                <div class="monthly-summary-container" data-index="${newIndex}">
                    <div class="mb-3 card activity" data-activity-index="1">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="form-group">
                                <label for="month[${newIndex}][1]" class="form-label">Month</label>
                                <select name="month[${newIndex}][1]" class="form-control @error('month.${newIndex}.1') is-invalid @enderror" style="background-color: #6571ff;">
                                    <option value="" disabled selected>Select Month</option>
                                    @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                                        <option value="{{ $month }}">{{ $month }}</option>
                                    @endforeach
                                </select>
                                @error('month.${newIndex}.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="summary_activities[${newIndex}][1][1]" class="form-label">Summary of Activities Undertaken During the Month</label>
                                <textarea name="summary_activities[${newIndex}][1][1]" class="form-control @error('summary_activities.${newIndex}.1.1') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                                @error('summary_activities.${newIndex}.1.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="qualitative_quantitative_data[${newIndex}][1][1]" class="form-label">Qualitative & Quantitative Data</label>
                                <textarea name="qualitative_quantitative_data[${newIndex}][1][1]" class="form-control @error('qualitative_quantitative_data.${newIndex}.1.1') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                                @error('qualitative_quantitative_data.${newIndex}.1.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="intermediate_outcomes[${newIndex}][1][1]" class="form-label">Intermediate Outcomes</label>
                                <textarea name="intermediate_outcomes[${newIndex}][1][1]" class="form-control @error('intermediate_outcomes.${newIndex}.1.1') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                                @error('intermediate_outcomes.${newIndex}.1.1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="addActivity(${newIndex})">Add Activity</button>
                        <button type="button" class="btn btn-danger btn-sm remove-activity" onclick="removeActivity(this)">Remove</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="not_happened[${newIndex}]" class="form-label">What Did Not Happen?</label>
                    <textarea name="not_happened[${newIndex}]" class="form-control @error('not_happened.${newIndex}') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                    @error('not_happened.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="why_not_happened[${newIndex}]" class="form-label">Explain Why Some Activities Could Not Be Undertaken</label>
                    <textarea name="why_not_happened[${newIndex}]" class="form-control @error('why_not_happened.${newIndex}') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                    @error('why_not_happened.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="changes[${newIndex}]" class="form-label">Have You Made Any Changes in the Project Such as New Activities or Modified the Activities Contextually?</label>
                    <div>
                        <input type="radio" name="changes[${newIndex}]" value="yes" onclick="toggleWhyChanges(this, ${newIndex})"> Yes
                        <input type="radio" name="changes[${newIndex}]" value="no" onclick="toggleWhyChanges(this, ${newIndex})"> No
                    </div>
                </div>
                <div class="mb-3 d-none" id="why_changes_container_${newIndex}">
                    <label for="why_changes[${newIndex}]" class="form-label">Explain Why the Changes Were Needed</label>
                    <textarea name="why_changes[${newIndex}]" class="form-control @error('why_changes.${newIndex}') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                    @error('why_changes.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="lessons_learnt[${newIndex}]" class="form-label">What Are the Lessons Learnt?</label>
                    <textarea name="lessons_learnt[${newIndex}]" class="form-control @error('lessons_learnt.${newIndex}') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                    @error('lessons_learnt.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="todo_lessons_learnt[${newIndex}]" class="form-label">What Will Be Done Differently Because of the Learnings?</label>
                    <textarea name="todo_lessons_learnt[${newIndex}]" class="form-control @error('todo_lessons_learnt.${newIndex}') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                    @error('todo_lessons_learnt.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        `;

        container.appendChild(newObjective);
    }

    function removeObjective(button) {
        const objective = button.closest('.objective');
        objective.remove();
    }

    function addActivity(objectiveIndex) {
        const container = document.querySelector(`.objective[data-index="${objectiveIndex}"] .monthly-summary-container`);
        const newIndex = container.children.length + 1;

        const newActivity = document.createElement('div');
        newActivity.className = 'mb-3 card activity';
        newActivity.dataset.activityIndex = newIndex;

        newActivity.innerHTML = `
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="form-group">
                    <label for="month[${objectiveIndex}][${newIndex}]" class="form-label">Month</label>
                    <select name="month[${objectiveIndex}][${newIndex}]" class="form-control @error('month.${objectiveIndex}.${newIndex}') is-invalid @enderror" style="background-color: #6571ff;">
                        <option value="" disabled selected>Select Month</option>
                        @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                            <option value="{{ $month }}">{{ $month }}</option>
                        @endforeach
                    </select>
                    @error('month.${objectiveIndex}.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="summary_activities[${objectiveIndex}][${newIndex}][${newIndex}]" class="form-label">Summary of Activities Undertaken During the Month</label>
                    <textarea name="summary_activities[${objectiveIndex}][${newIndex}][${newIndex}]" class="form-control @error('summary_activities.${objectiveIndex}.${newIndex}.${newIndex}') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                    @error('summary_activities.${objectiveIndex}.${newIndex}.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="qualitative_quantitative_data[${objectiveIndex}][${newIndex}][${newIndex}]" class="form-label">Qualitative & Quantitative Data</label>
                    <textarea name="qualitative_quantitative_data[${objectiveIndex}][${newIndex}][${newIndex}]" class="form-control @error('qualitative_quantitative_data.${objectiveIndex}.${newIndex}.${newIndex}') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                    @error('qualitative_quantitative_data.${objectiveIndex}.${newIndex}.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="intermediate_outcomes[${objectiveIndex}][${newIndex}][${newIndex}]" class="form-label">Intermediate Outcomes</label>
                    <textarea name="intermediate_outcomes[${objectiveIndex}][${newIndex}][${newIndex}]" class="form-control @error('intermediate_outcomes.${objectiveIndex}.${newIndex}.${newIndex}') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                    @error('intermediate_outcomes.${objectiveIndex}.${newIndex}.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="addActivity(${objectiveIndex})">Add Activity</button>
            <button type="button" class="btn btn-danger btn-sm remove-activity" onclick="removeActivity(this)">Remove</button>
        `;

        container.appendChild(newActivity);
    }

    function removeActivity(button) {
        const activity = button.closest('.activity');
        activity.remove();
    }

    function addOutlook() {
        const container = document.getElementById('outlook-container');
        const newIndex = container.children.length + 1;

        const newOutlook = document.createElement('div');
        newOutlook.className = 'mb-3 card outlook';
        newOutlook.dataset.index = newIndex;

        newOutlook.innerHTML = `
            <div class="card-header">
                <h4>3. Outlook</h4>
            </div>
            <div class="card-header d-flex justify-content-between align-items-center">
                Outlook ${newIndex}
                <button type="button" class="btn btn-danger btn-sm remove-outlook" onclick="removeOutlook(this)">Remove</button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="date[${newIndex}]" class="form-label">Date</label>
                    <input type="date" name="date[${newIndex}]" class="form-control @error('date.${newIndex}') is-invalid @enderror" style="background-color: #6571ff;">
                    @error('date.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="plan_next_month[${newIndex}]" class="form-label">Action Plan for Next Month</label>
                    <textarea name="plan_next_month[${newIndex}]" class="form-control @error('plan_next_month.${newIndex}') is-invalid @enderror" rows="3" style="background-color: #6571ff;"></textarea>
                    @error('plan_next_month.${newIndex}')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        `;

        container.appendChild(newOutlook);
    }

    function removeOutlook(button) {
        const outlook = button.closest('.outlook');
        outlook.remove();
    }

    function addPhoto() {
        const container = document.getElementById('photos-container');
        const newIndex = container.children.length + 1;

        const newPhoto = document.createElement('div');
        newPhoto.className = 'mb-3 photo-group';
        newPhoto.dataset.index = newIndex;

        newPhoto.innerHTML = `
            <label for="photo_${newIndex}" class="form-label">Photo ${newIndex}</label>
            <input type="file" name="photos[]" class="mb-2 form-control @error('photos.${newIndex}') is-invalid @enderror" accept="image/*" onchange="checkFileSize(this)" style="background-color: #6571ff;">
            @error('photos.${newIndex}')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <textarea name="photo_descriptions[]" class="form-control @error('photo_descriptions.${newIndex}') is-invalid @enderror" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #6571ff;"></textarea>
            @error('photo_descriptions.${newIndex}')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <button type="button" class="mt-2 btn btn-danger" onclick="removePhoto(this)">Remove</button>
        `;

        container.appendChild(newPhoto);
    }

    function removePhoto(button) {
        const photoGroup = button.closest('.photo-group');
        photoGroup.remove();
    }

    function checkFileSize(input) {
        const file = input.files[0];
        if (file && file.size > 5 * 1024 * 1024) {
            alert('File size should not exceed 5MB.');
            input.value = '';
        }
    }

    function toggleWhyChanges(radio, index) {
        const container = document.getElementById(`why_changes_container_${index}`);
        if (radio.value === 'yes') {
            container.classList.remove('d-none');
        } else {
            container.classList.add('d-none');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        calculateTotal();
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
