@extends('executor.dashboard')

@section('content')
<div class="container">
    <h1>Edit Project</h1>
    <form action="{{ route('projects.update', $project->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3 card">
            <div class="card-header">
                <h4 class="fp-text-center1">EDIT PROJECT APPLICATION FORM</h4>
            </div>
            <div class="card-header">
                <h4 class="fp-text-margin">General Information</h4>
            </div>

            <!-- General Information Fields -->
            <div class="card-body">
                <div class="mb-3">
                    <label for="project_type" class="form-label">Project Type</label>
                    <select name="project_type" id="project_type" class="form-control" required>
                        <option value="" disabled>Select Project Type</option>
                        <option value="CHILD CARE INSTITUTION" {{ $project->project_type == 'CHILD CARE INSTITUTION' ? 'selected' : '' }}>CHILD CARE INSTITUTION - Welfare home for children - Ongoing</option>
                        <option value="Development Projects" {{ $project->project_type == 'Development Projects' ? 'selected' : '' }}>Development Projects - Application</option>
                        <option value="Rural-Urban-Tribal" {{ $project->project_type == 'Rural-Urban-Tribal' ? 'selected' : '' }}>Rural-Urban-Tribal</option>
                        <option value="Institutional Ongoing Group Educational proposal" {{ $project->project_type == 'Institutional Ongoing Group Educational proposal' ? 'selected' : '' }}>Institutional Ongoing Group Educational proposal</option>
                        <option value="Livelihood Development Projects" {{ $project->project_type == 'Livelihood Development Projects' ? 'selected' : '' }}>Livelihood Development Projects</option>
                        <option value="PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER" {{ $project->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' ? 'selected' : '' }}>PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER - Application</option>
                        <option value="NEXT PHASE - DEVELOPMENT PROPOSAL" {{ $project->project_type == 'NEXT PHASE - DEVELOPMENT PROPOSAL' ? 'selected' : '' }}>NEXT PHASE - DEVELOPMENT PROPOSAL</option>
                        <option value="Residential Skill Training Proposal 2" {{ $project->project_type == 'Residential Skill Training Proposal 2' ? 'selected' : '' }}>Residential Skill Training Proposal 2</option>
                        <option value="Individual - Ongoing Educational support" {{ $project->project_type == 'Individual - Ongoing Educational support' ? 'selected' : '' }}>Individual - Ongoing Educational support - Project Application</option>
                        <option value="Individual - Livelihood Application" {{ $project->project_type == 'Individual - Livelihood Application' ? 'selected' : '' }}>Individual - Livelihood Application</option>
                        <option value="Individual - Access to Health" {{ $project->project_type == 'Individual - Access to Health' ? 'selected' : '' }}>Individual - Access to Health - Project Application</option>
                        <option value="Individual - Initial - Educational support" {{ $project->project_type == 'Individual - Initial - Educational support' ? 'selected' : '' }}>Individual - Initial - Educational support - Project Application</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="project_title" class="form-label">Project Title</label>
                    <input type="text" name="project_title" id="project_title" class="form-control" value="{{ $project->project_title }}" required>
                </div>
                <div class="mb-3">
                    <label for="society_name" class="form-label">Name of the Society / Trust</label>
                    <input type="text" name="society_name" class="form-control" value="{{ $project->society_name }}" readonly>
                </div>
                <div class="mb-3">
                    <label for="president" class="form-label">President / Chair Person</label>
                    <input type="text" name="president" class="form-control" value="{{ $project->president_name }}" readonly>
                </div>
                <div class="mb-3">
                    <label for="parent_id" class="form-label">Provincial Superior</label>
                    <input type="text" name="parent_id" class="form-control" value="{{ $project->parent_id }}" readonly>
                </div>
                <div class="mb-3">
                    <label for="in_charge" class="form-label">Project In-Charge</label>
                    <div class="d-flex">
                        <select name="in_charge" id="in_charge" class="form-control me-2">
                            <option value="" disabled>Select In-Charge</option>
                            @foreach($users as $potential_in_charge)
                                @if($potential_in_charge->province == $user->province)
                                    <option value="{{ $potential_in_charge->id }}" data-mobile="{{ $potential_in_charge->phone }}" data-email="{{ $potential_in_charge->email }}" {{ $potential_in_charge->id == $project->in_charge ? 'selected' : '' }}>
                                        {{ $potential_in_charge->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <input type="text" name="in_charge_mobile" id="in_charge_mobile" class="form-control me-2" value="{{ $project->in_charge_mobile }}" readonly>
                        <input type="text" name="in_charge_email" id="in_charge_email" class="form-control" value="{{ $project->in_charge_email }}" readonly>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="executor_name" class="form-label">Project Executor</label>
                    <div class="d-flex">
                        <input type="text" name="executor_name" class="form-control me-2" value="{{ $project->executor_name }}" readonly>
                        <input type="text" name="executor_mobile" class="form-control me-2" value="{{ $project->executor_phone }}" readonly>
                        <input type="text" name="executor_email" class="form-control" value="{{ $project->executor_email }}" readonly>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="full_address" class="form-label">Full Address</label>
                    <textarea name="full_address" class="form-control" rows="2" required>{{ $project->full_address }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="overall_project_period" class="form-label">Overall Project Period</label>
                    <select name="overall_project_period" id="overall_project_period" class="form-control" required>
                        <option value="" disabled>Select Period</option>
                        <option value="1" {{ $project->overall_project_period == 1 ? 'selected' : '' }}>1 Year</option>
                        <option value="2" {{ $project->overall_project_period == 2 ? 'selected' : '' }}>2 Years</option>
                        <option value="3" {{ $project->overall_project_period == 3 ? 'selected' : '' }}>3 Years</option>
                        <option value="4" {{ $project->overall_project_period == 4 ? 'selected' : '' }}>4 Years</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="current_phase" class="form-label">Phase</label>
                    <select name="current_phase" id="current_phase" class="form-control" required>
                        <option value="" disabled>Select Phase</option>
                        @for ($i = 1; $i <= $project->overall_project_period; $i++)
                            <option value="{{ $i }}" {{ $project->current_phase == $i ? 'selected' : '' }}>
                                {{ $i }}{{ $i === 1 ? 'st' : ($i === 2 ? 'nd' : ($i === 3 ? 'rd' : 'th')) }} Phase
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="mb-3">
                    <label for="overall_project_budget" class="form-label">Overall Project Budget</label>
                    <input type="number" name="overall_project_budget" id="overall_project_budget" class="form-control" value="{{ $project->overall_project_budget }}" readonly>
                </div>
                <div class="mb-3">
                    <label for="coordinator_india" class="form-label">Project Co-Ordinator, India</label>
                    <div class="d-flex">
                        <input type="hidden" name="coordinator_india" value="{{ $project->coordinator_india }}">
                        <input type="text" name="coordinator_india_name" class="form-control me-2" value="{{ $project->coordinator_india_name }}" readonly>
                        <input type="text" name="coordinator_india_mobile" class="form-control me-2" value="{{ $project->coordinator_india_phone }}" readonly>
                        <input type="text" name="coordinator_india_email" class="form-control" value="{{ $project->coordinator_india_email }}" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="coordinator_luzern" class="form-label">Mission Co-Ordinator, Luzern, Switzerland</label>
                    <div class="d-flex">
                        <input type="hidden" name="coordinator_luzern" value="{{ $project->coordinator_luzern }}">
                        <input type="text" name="coordinator_luzern_name" class="form-control me-2" value="{{ $project->coordinator_luzern_name }}" readonly>
                        <input type="text" name="coordinator_luzern_mobile" class="form-control me-2" value="{{ $project->coordinator_luzern_phone }}" readonly>
                        <input type="text" name="coordinator_luzern_email" class="form-control" value="{{ $project->coordinator_luzern_email }}" readonly>
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
                    <textarea name="goal" class="form-control" rows="3" required>{{ $project->goal }}</textarea>
                </div>
            </div>
        </div>

        <!-- Budget Section -->
        <div class="mb-3 card">
            <div class="card-header">
                <h4>2. Budget</h4>
            </div>
            <div class="card-body">
                <div id="phases-container">
                    @foreach($project->budgets->groupBy('phase') as $phaseIndex => $budgets)
                        <div class="phase-card" data-phase="{{ $phaseIndex }}">
                            <div class="card-header">
                                <h4>Phase {{ $phaseIndex }}</h4>
                            </div>
                            @if($phaseIndex > 0)
                                <div class="mb-3">
                                    <label for="phases[{{ $phaseIndex }}][amount_forwarded]" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
                                    <input type="number" name="phases[{{ $phaseIndex }}][amount_forwarded]" class="form-control" value="{{ $budgets->first()->amount_forwarded }}" oninput="calculateBudgetTotals(this.closest('.phase-card'))">
                                </div>
                            @endif
                            <div class="mb-3">
                                <label for="phases[{{ $phaseIndex }}][amount_sanctioned]" class="form-label">Amount Sanctioned in Phase {{ $phaseIndex }}: Rs.</label>
                                <input type="number" name="phases[{{ $phaseIndex }}][amount_sanctioned]" class="form-control" value="{{ $budgets->first()->amount_sanctioned }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="phases[{{ $phaseIndex }}][opening_balance]" class="form-label">Opening balance in Phase {{ $phaseIndex }}: Rs.</label>
                                <input type="number" name="phases[{{ $phaseIndex }}][opening_balance]" class="form-control" value="{{ $budgets->first()->opening_balance }}" readonly>
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
                                    @foreach($budgets as $budgetIndex => $budget)
                                        <tr>
                                            <td><input type="string" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][particular]" class="form-control" value="{{ $budget->particular }}" required></td>
                                            <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][rate_quantity]" class="form-control" value="{{ $budget->rate_quantity }}" oninput="calculateBudgetRowTotals(this)" required></td>
                                            <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][rate_multiplier]" class="form-control" value="{{ $budget->rate_multiplier }}" oninput="calculateBudgetRowTotals(this)" required></td>
                                            <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][rate_duration]" class="form-control" value="{{ $budget->rate_duration }}" oninput="calculateBudgetRowTotals(this)" required></td>
                                            <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][rate_increase]" class="form-control" value="{{ $budget->rate_increase }}" oninput="calculateBudgetRowTotals(this)" required></td>
                                            <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][this_phase]" class="form-control" value="{{ $budget->this_phase }}" readonly></td>
                                            <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][next_phase]" class="form-control" value="{{ $budget->next_phase }}" readonly></td>
                                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th><input type="number" class="total_rate_quantity form-control" readonly></th>
                                        <th><input type="number" class="total_rate_multiplier form-control" readonly></th>
                                        <th><input type="number" class="total_rate_duration form-control" readonly></th>
                                        <th><input type="number" class="total_rate_increase form-control" readonly></th>
                                        <th><input type="number" class="total_this_phase form-control" readonly></th>
                                        <th><input type="number" class="total_next_phase form-control" readonly></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                            <button type="button" class="btn btn-primary" onclick="addBudgetRow(this)">Add Row</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="mt-3 btn btn-primary" onclick="addPhase()">Add Phase</button>
                <div class="mt-3" style="margin-bottom: 20px;">
                    <label for="total_amount_sanctioned" class="form-label">Total Amount Sanctioned: Rs.</label>
                    <input type="number" name="total_amount_sanctioned" class="form-control" value="{{ $project->total_amount_sanctioned }}" readonly>
                </div>
            </div>
        </div>

        <!-- Attachments Section -->
        <div class="mb-3 card">
            <div class="card-header">
                <h4>3. Attachments</h4>
            </div>
            <div class="card-body">
                <div id="attachments-container">
                    @foreach($project->attachments ?? [] as $attachmentIndex => $attachment)
                        <div class="mb-3 attachment-group" data-index="{{ $attachmentIndex }}">
                            <label class="form-label">Attachment {{ $attachmentIndex + 1 }}</label>
                            <input type="hidden" name="attachments[{{ $attachmentIndex }}][id]" value="{{ $attachment->id }}">
                            <input type="text" name="attachments[{{ $attachmentIndex }}][file_name]" class="form-control" placeholder="Name of File Attached" value="{{ $attachment->file_name }}">
                            <textarea name="attachments[{{ $attachmentIndex }}][description]" class="form-control" rows="3" placeholder="Brief Description">{{ $attachment->description }}</textarea>
                            <a href="{{ route('download.attachment', $attachment->id) }}" class="mt-2 btn btn-secondary">Download Existing Attachment</a>
                            <button type="button" class="mt-2 btn btn-danger" onclick="removeAttachment(this)">Remove</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="mt-3 btn btn-primary" onclick="addAttachment()">Add More Attachment</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary me-2">Update Project</button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update the mobile and email fields based on the selected project in-charge
        document.getElementById('in_charge').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const mobile = selectedOption.getAttribute('data-mobile');
            const email = selectedOption.getAttribute('data-email');

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

        const projectPeriod = parseInt(document.getElementById('overall_project_period').value);
        if (projectPeriod !== 1) {
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

        phases.forEach((phase, index) => {
            const thisPhaseTotal = parseFloat(phase.querySelector('.total_this_phase').value) || 0;
            phase.querySelector('[name^="phases"][name$="[amount_sanctioned]"]').value = thisPhaseTotal.toFixed(2);

            if (index > 0) {
                const amountForwarded = parseFloat(phase.querySelector('[name^="phases"][name$="[amount_forwarded]"]').value) || 0;
                const openingBalance = amountForwarded + thisPhaseTotal;
                phase.querySelector('[name^="phases"][name$="[opening_balance]"]').value = openingBalance.toFixed(2);
            }

            totalAmount += thisPhaseTotal;
        });

        const lastPhase = phases[phases.length - 1];
        const rows = lastPhase.querySelectorAll('.budget-rows tr');
        rows.forEach(row => {
            totalNextPhase += parseFloat(row.querySelector('[name$="[next_phase]"]').value) || 0;
        });

        document.querySelector('[name="total_amount_sanctioned"]').value = totalAmount.toFixed(2);
        document.getElementById('overall_project_budget').value = (totalAmount + totalNextPhase).toFixed(2);
    }

    // Add a new budget row to the phase card
    function addBudgetRow(button) {
        const tableBody = button.closest('.phase-card').querySelector('.budget-rows');
        const phaseIndex = button.closest('.phase-card').dataset.phase;
        const newRow = document.createElement('tr');

        newRow.innerHTML = `
            <td><input type="string" name="phases[${phaseIndex}][budget][${tableBody.children.length}][particular]" class="form-control" required></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_quantity]" class="form-control" oninput="calculateBudgetRowTotals(this)" required></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_multiplier]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" required></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_duration]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" required></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][rate_increase]" class="form-control" oninput="calculateBudgetRowTotals(this)" required></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][this_phase]" class="form-control" readonly></td>
            <td><input type="number" name="phases[${phaseIndex}][budget][${tableBody.children.length}][next_phase]" class="form-control" readonly></td>
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
                <input type="number" name="phases[${phaseCount}][amount_sanctioned]" class="form-control" readonly>
            </div>
            <div class="mb-3">
                <label for="phases[${phaseCount}][opening_balance]" class="form-label">Opening balance in Phase ${phaseCount + 1}: Rs.</label>
                <input type="number" name="phases[${phaseCount}][opening_balance]" class="form-control" readonly>
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
                        <td><input type="string" name="phases[${phaseCount}][budget][0][particular]" class="form-control" required></td>
                        <td><input type="number" name="phases[${phaseCount}][budget][0][rate_quantity]" class="form-control" oninput="calculateBudgetRowTotals(this)" required></td>
                        <td><input type="number" name="phases[${phaseCount}][budget][0][rate_multiplier]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" required></td>
                        <td><input type="number" name="phases[${phaseCount}][budget][0][rate_duration]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" required></td>
                        <td><input type="number" name="phases[${phaseCount}][budget][0][rate_increase]" class="form-control" oninput="calculateBudgetRowTotals(this)" required></td>
                        <td><input type="number" name="phases[${phaseCount}][budget][0][this_phase]" class="form-control" readonly></td>
                        <td><input type="number" name="phases[${phaseCount}][budget][0][next_phase]" class="form-control" readonly></td>
                        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th><input type="number" class="total_rate_quantity form-control" readonly></th>
                        <th><input type="number" class="total_rate_multiplier form-control" readonly></th>
                        <th><input type="number" class="total_rate_duration form-control" readonly></th>
                        <th><input type="number" class="total_rate_increase form-control" readonly></th>
                        <th><input type="number" class="total_this_phase form-control" readonly></th>
                        <th><input type="number" class="total_next_phase form-control" readonly></th>
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
                <label class="form-label">Attachment ${index + 1}</label>
                <input type="file" name="attachments[${index}][file]" class="mb-2 form-control" accept=".pdf,.doc,.docx,.xlsx">
                <input type="text" name="attachments[${index}][file_name]" class="form-control" placeholder="Name of File Attached">
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
