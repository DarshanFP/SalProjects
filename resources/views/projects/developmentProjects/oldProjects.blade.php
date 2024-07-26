@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <form action="{{ route('projects.developmentProjects.storeOldProject') }}" method="POST" enctype="multipart/form-data">
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
                        <div class="mb-3">
                            <label for="project_type" class="form-label">Project Type</label>
                            <select name="project_type" id="project_type" class="form-control" required>
                                <option value="" disabled selected>Select Project Type</option>
                                <option value="CHILD CARE INSTITUTION">CHILD CARE INSTITUTION - Welfare home for children - Ongoing</option>
                                <option value="Development Projects">Development Projects - Application</option>
                                <option value="Rural-Urban-Tribal">Rural-Urban-Tribal</option>
                                <option value="Institutional Ongoing Group Educational proposal">Institutional Ongoing Group Educational proposal</option>
                                <option value="Livelihood Development Projects">Livelihood Development Projects</option>
                                <option value="PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER">PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER - Application</option>
                                <option value="NEXT PHASE -DEVELOPMENT PROPOSAL">NEXT PHASE - DEVELOPMENT PROPOSAL</option>
                                <option value="Residential Skill Training Proposal 2">Residential Skill Training Proposal 2</option>
                                <option value="Individual - Ongoing Educational support">Individual - Ongoing Educational support - Project Application</option>
                                <option value="Individual - Livelihood Application">Individual - Livelihood Application</option>
                                <option value="Individual - Access to Health">Individual - Access to Health - Project Application</option>
                                <option value="Individual - Initial - Educational support">Individual - Initial - Educational support - Project Application</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="project_title" class="form-label">Project Title</label>
                            <input type="text" name="project_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="society_name" class="form-label">Name of the Society / Trust</label>
                            <input type="text" name="society_name" class="form-control" value="{{ $user->society_name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="president" class="form-label">President / Chair Person</label>
                            <input type="text" name="president" class="form-control" value="{{ $user->parent->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Provincial Superior</label>
                            <input type="text" name="parent_id" class="form-control" value="{{ $user->parent->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="in_charge" class="form-label">Project In-Charge</label>
                            <div class="d-flex">
                                <select name="in_charge" id="in_charge" class="form-control me-2" required>
                                    <option value="" disabled selected>Select In-Charge</option>
                                    @foreach($users as $potential_in_charge)
                                        @if($potential_in_charge->province == $user->province)
                                            <option value="{{ $potential_in_charge->id }}" data-mobile="{{ $potential_in_charge->phone }}" data-email="{{ $potential_in_charge->email }}">
                                                {{ $potential_in_charge->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <input type="text" name="in_charge_mobile" id="in_charge_mobile" class="form-control me-2" readonly>
                                <input type="text" name="in_charge_email" id="in_charge_email" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="executor_name" class="form-label">Project Executor</label>
                            <div class="d-flex">
                                <input type="text" name="executor_name" class="form-control me-2" value="{{ $user->name }}" readonly>
                                <input type="text" name="executor_mobile" class="form-control me-2" value="{{ $user->phone }}" readonly>
                                <input type="text" name="executor_email" class="form-control" value="{{ $user->email }}" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="full_address" class="form-label">Full Address</label>
                            <textarea name="full_address" class="form-control" rows="2" required>{{ $user->address }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="overall_project_period" class="form-label">Overall Project Period</label>
                            <select name="overall_project_period" id="overall_project_period" class="form-control" required>
                                <option value="" disabled selected>Select Period</option>
                                <option value="1">1 Year</option>
                                <option value="2">2 Years</option>
                                <option value="3">3 Years</option>
                                <option value="4">4 Years</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="current_phase" class="form-label">Phase</label>
                            <select name="current_phase" id="current_phase" class="form-control" required>
                                <option value="" disabled selected>Select Phase</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="overall_project_budget" class="form-label">Overall Project Budget</label>
                            <input type="number" name="overall_project_budget" id="overall_project_budget" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            @php
                                $coordinator_india = $users->firstWhere('role', 'coordinator')->firstWhere('province', 'Generalate');
                            @endphp

                            <label for="coordinator_india" class="form-label">Project Co-Ordinator, India</label>
                            <div class="d-flex">
                                @if($coordinator_india)
                                    <input type="hidden" name="coordinator_india" value="{{ $coordinator_india->id }}">
                                    <input type="text" name="coordinator_india_name" class="form-control me-2" value="{{ $coordinator_india->name }}" readonly>
                                    <input type="text" name="coordinator_india_mobile" class="form-control me-2" value="{{ $coordinator_india->phone }}" readonly>
                                    <input type="text" name="coordinator_india_email" class="form-control" value="{{ $coordinator_india->email }}" readonly>
                                @else
                                    <input type="text" name="coordinator_india_name" class="form-control me-2" placeholder="Name" readonly>
                                    <input type="text" name="coordinator_india_mobile" class="form-control me-2" placeholder="Mobile" readonly>
                                    <input type="text" name="coordinator_india_email" class="form-control" placeholder="Email" readonly>
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
                                    <input type="text" name="coordinator_luzern_name" class="form-control me-2" value="{{ $coordinator_luzern->name }}" readonly>
                                    <input type="text" name="coordinator_luzern_mobile" class="form-control me-2" value="{{ $coordinator_luzern->phone }}" readonly>
                                    <input type="text" name="coordinator_luzern_email" class="form-control" value="{{ $coordinator_luzern->email }}" readonly>
                                @else
                                    <input type="text" name="coordinator_luzern_name" class="form-control me-2" placeholder="Name" readonly>
                                    <input type="text" name="coordinator_luzern_mobile" class="form-control me-2" placeholder="Mobile" readonly>
                                    <input type="text" name="coordinator_luzern_email" class="form-control" placeholder="Email" readonly>
                                @endif
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
                            <textarea name="goal" class="form-control" rows="3" required></textarea>
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
                            <div class="phase-card" data-phase="0">
                                <div class="card-header">
                                    <h4>Phase 1</h4>
                                </div>
                                <div class="mb-3">
                                    <label for="amount_sanctioned_1" class="form-label">Amount Sanctioned in First Phase: Rs.</label>
                                    <input type="number" name="phases[0][amount_sanctioned]" class="form-control" readonly>
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
                                            <td><input type="string" name="phases[0][budget][0][particular]" class="form-control" required></td>
                                            <td><input type="number" name="phases[0][budget][0][rate_quantity]" class="form-control" oninput="calculateBudgetRowTotals(this)" required></td>
                                            <td><input type="number" name="phases[0][budget][0][rate_multiplier]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" required></td>
                                            <td><input type="number" name="phases[0][budget][0][rate_duration]" class="form-control" value="1" oninput="calculateBudgetRowTotals(this)" required></td>
                                            <td><input type="number" name="phases[0][budget][0][rate_increase]" class="form-control" oninput="calculateBudgetRowTotals(this)" required></td>
                                            <td><input type="number" name="phases[0][budget][0][this_phase]" class="form-control" readonly></td>
                                            <td><input type="number" name="phases[0][budget][0][next_phase]" class="form-control" readonly></td>
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
                            </div>
                        </div>
                        <button type="button" class="mt-3 btn btn-primary" onclick="addPhase()">Add Phase</button>
                        <div class="mt-3" style="margin-bottom: 20px;">
                            <label for="total_amount_sanctioned" class="form-label">Total Amount Sanctioned: Rs.</label>
                            <input type="number" name="total_amount_sanctioned" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <div class="mb-3 card">
                    <div class="card-header">
                        <h4>3. Attachments</h4>
                    </div>
                    <div class="card-body">
                        <div id="attachments-container">
                            <div class="mb-3 attachment-group" data-index="1">
                                <label for="attachment_1" class="form-label">Attachment 1</label>
                                <input type="file" name="attachments[]" class="mb-2 form-control" accept=".pdf,.doc,.docx,.xlsx">
                                <div class="mb-3">
                                    <label for="file_name" class="form-label">File Name</label>
                                    <input type="text" name="file_name" class="form-control" placeholder="Name of File Attached">
                                </div>
                                <textarea name="attachment_descriptions[]" class="form-control" rows="3" placeholder="Brief Description"></textarea>
                                <button type="button" class="mt-2 btn btn-danger" onclick="removeAttachment(this)">Remove</button>
                            </div>
                        </div>
                        <button type="button" class="mt-3 btn btn-primary" onclick="addAttachment()">Add More Attachment</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary me-2">Submit Application</button>
            </form>
        </div>
    </div>
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
        });
    });

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

        console.log('Total Amount Sanctioned:', totalAmount); // Debug log

        const lastPhase = phases[phases.length - 1];
        const rows = lastPhase.querySelectorAll('.budget-rows tr');
        rows.forEach(row => {
            totalNextPhase += parseFloat(row.querySelector('[name$="[next_phase]"]').value) || 0;
        });

        console.log('Total Next Phase from Last Phase:', totalNextPhase); // Debug log

        document.querySelector('[name="total_amount_sanctioned"]').value = totalAmount.toFixed(2);
        document.getElementById('overall_project_budget').value = (totalAmount + totalNextPhase).toFixed(2);

        console.log('Overall Project Budget:', totalAmount + totalNextPhase); // Debug log
    }

    // Calculate the budget totals for a single budget row
    function calculateBudgetRowTotals(element) {
        const row = element.closest('tr');
        const rateQuantity = parseFloat(row.querySelector('[name$="[rate_quantity]"]').value) || 0;
        const rateMultiplier = parseFloat(row.querySelector('[name$="[rate_multiplier]"]').value) || 1;
        const rateDuration = parseFloat(row.querySelector('[name$="[rate_duration]"]').value) || 1;
        const rateIncrease = parseFloat(row.querySelector('[name$="[rate_increase]"]').value) || 0;

        const thisPhase = rateQuantity * rateMultiplier * rateDuration;
        const nextPhase = (rateQuantity + rateIncrease) * rateMultiplier * rateDuration;

        row.querySelector('[name$="[this_phase]"]').value = thisPhase.toFixed(2);
        row.querySelector('[name$="[next_phase]"]').value = nextPhase.toFixed(2);

        calculateBudgetTotals(row.closest('.phase-card')); // Recalculate totals for the phase whenever a row total is updated
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
                <label for="attachments[${index}][file]" class="form-label">Attachment ${index + 1}</label>
                <input type="file" name="attachments[${index}][file]" class="mb-2 form-control" accept=".pdf,.doc,.docx,.xlsx">
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
