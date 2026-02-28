<!-- resources/views/projects/partials/edit_general_info.blade.php  -->

<div class="mb-4 card">
    <div class="card-header">
        <h4>1. Basic Information</h4>
    </div>
    <div class="card-body">
        {{-- PROJECT ID --}}
        <div class="mb-3">
            <label for="project_id" class="form-label">Project ID</label>
            <input type="text" name="project_id" id="project_id" class="form-control readonly-input"
                   value="{{ $project->project_id }}" readonly>
        </div>

        {{-- PROJECT TYPE --}}
        <div class="mb-3">
            <label for="project_type" class="form-label">Project Type</label>
            <select name="project_type" id="project_type" class="form-control select-input" >
                <option value="" disabled>Select Project Type</option>
                <option value="{{ \App\Constants\ProjectType::CHILD_CARE_INSTITUTION }}"
                    {{ $project->project_type == \App\Constants\ProjectType::CHILD_CARE_INSTITUTION ? 'selected' : '' }}>
                    CHILD CARE INSTITUTION - Welfare home for children - Ongoing
                </option>
                <option value="{{ \App\Constants\ProjectType::DEVELOPMENT_PROJECTS }}"
                    {{ $project->project_type == \App\Constants\ProjectType::DEVELOPMENT_PROJECTS ? 'selected' : '' }}>
                    Development Projects - Application
                </option>
                <option value="{{ \App\Constants\ProjectType::RURAL_URBAN_TRIBAL }}"
                    {{ $project->project_type == \App\Constants\ProjectType::RURAL_URBAN_TRIBAL ? 'selected' : '' }}>
                    Education Rural-Urban-Tribal
                </option>
                <option value="{{ \App\Constants\ProjectType::INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL }}"
                    {{ $project->project_type == \App\Constants\ProjectType::INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL ? 'selected' : '' }}>
                    Institutional Ongoing Group Educational proposal
                </option>
                <option value="{{ \App\Constants\ProjectType::LIVELIHOOD_DEVELOPMENT_PROJECTS }}"
                    {{ $project->project_type == \App\Constants\ProjectType::LIVELIHOOD_DEVELOPMENT_PROJECTS ? 'selected' : '' }}>
                    Livelihood Development Projects
                </option>
                <option value="{{ \App\Constants\ProjectType::CRISIS_INTERVENTION_CENTER }}"
                    {{ $project->project_type == \App\Constants\ProjectType::CRISIS_INTERVENTION_CENTER ? 'selected' : '' }}>
                    PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER - Application
                </option>
                <option value="{{ \App\Constants\ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL }}"
                    {{ $project->project_type == \App\Constants\ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL ? 'selected' : '' }}>
                    NEXT PHASE - DEVELOPMENT PROPOSAL
                </option>
                <option value="{{ \App\Constants\ProjectType::RESIDENTIAL_SKILL_TRAINING }}"
                    {{ $project->project_type == \App\Constants\ProjectType::RESIDENTIAL_SKILL_TRAINING ? 'selected' : '' }}>
                    Residential Skill Training Proposal 2
                </option>
                <option value="{{ \App\Constants\ProjectType::INDIVIDUAL_ONGOING_EDUCATIONAL }}"
                    {{ $project->project_type == \App\Constants\ProjectType::INDIVIDUAL_ONGOING_EDUCATIONAL ? 'selected' : '' }}>
                    Individual - Ongoing Educational support - Project Application
                </option>
                <option value="{{ \App\Constants\ProjectType::INDIVIDUAL_LIVELIHOOD_APPLICATION }}"
                    {{ $project->project_type == \App\Constants\ProjectType::INDIVIDUAL_LIVELIHOOD_APPLICATION ? 'selected' : '' }}>
                    Individual - Livelihood Application
                </option>
                <option value="{{ \App\Constants\ProjectType::INDIVIDUAL_ACCESS_TO_HEALTH }}"
                    {{ $project->project_type == \App\Constants\ProjectType::INDIVIDUAL_ACCESS_TO_HEALTH ? 'selected' : '' }}>
                    Individual - Access to Health - Project Application
                </option>
                <option value="{{ \App\Constants\ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL }}"
                    {{ $project->project_type == \App\Constants\ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL ? 'selected' : '' }}>
                    Individual - Initial - Educational support - Project Application
                </option>
            </select>
        </div>

        {{-- Predecessor Project Selection (Always Visible for All Project Types) --}}
            <div class="mb-3">
            <label for="predecessor_project" class="form-label">Select Predecessor Project (Optional)</label>
                <select name="predecessor_project" id="predecessor_project" class="form-control select-input">
                    <option value="" {{ empty($project->predecessor_project_id) ? 'selected' : '' }}>None</option>
                @if(isset($developmentProjects) && $developmentProjects->count() > 0)
                    @foreach($developmentProjects as $devProject)
                        <option value="{{ $devProject->project_id }}"
                            {{ old('predecessor_project', $project->predecessor_project_id) == $devProject->project_id ? 'selected' : '' }}>
                            {{ $devProject->project_title }} (Phase {{ $devProject->current_phase }}/{{ $devProject->overall_project_period }})
                        </option>
                    @endforeach
                @else
                    <option value="" disabled>No development projects available</option>
                @endif
                </select>
            @error('predecessor_project')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            <small class="form-text text-muted">Select a previous project if this is a continuation or related project.</small>
        </div>

        {{-- PROJECT TITLE --}}
        <div class="mb-3">
            <label for="project_title" class="form-label">Project Title</label>
            <input type="text" name="project_title" id="project_title"
                   class="form-control select-input"
                   value="{{ old('project_title', $project->project_title) }}">
        </div>

        {{-- NAME OF SOCIETY / TRUST (Phase 5B1: society_id from $societies) --}}
        <div class="mb-3">
            <label for="society_id" class="form-label">Name of the Society / Trust</label>
            <select name="society_id" id="society_id" class="form-select" required>
                <option value="" disabled>Select Society / Trust</option>
                @foreach($societies ?? [] as $society)
                    <option value="{{ $society->id }}" {{ old('society_id', $project->society_id ?? '') == $society->id ? 'selected' : '' }}>{{ $society->name }}</option>
                @endforeach
            </select>
            @error('society_id')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        {{-- PRESIDENT / CHAIR PERSON --}}
        <div class="mb-3">
            <label for="president_name" class="form-label">President / Chair Person</label>
            <input type="text" name="president_name" id="president_name"
                   class="form-control readonly-input"
                   value="{{ old('president_name', $project->president_name ?? $user->parent->name ?? '') }}"
                   readonly>
        </div>

        {{-- PROJECT APPLICANT --}}
        <div class="mb-3">
            <label for="applicant_name" class="form-label">Project Applicant</label>
            <div class="d-flex">
                <input type="text" name="applicant_name" id="applicant_name"
                       class="form-control readonly-input me-2"
                       value="{{ old('applicant_name', $project->applicant_name ?? $user->name) }}"
                       readonly>
                <input type="text" name="applicant_mobile" id="applicant_mobile"
                       class="form-control readonly-input me-2"
                       value="{{ old('applicant_mobile', $project->applicant_mobile ?? $user->phone) }}"
                       readonly>
                <input type="text" name="applicant_email" id="applicant_email"
                       class="form-control readonly-input"
                       value="{{ old('applicant_email', $project->applicant_email ?? $user->email) }}"
                       readonly>
            </div>
        </div>

        {{-- PROJECT IN-CHARGE (Dropdown + phone/email) --}}
        <div class="mb-3">
            <label for="in_charge" class="form-label">Project In-Charge</label>
            <div class="d-flex">
                <select name="in_charge" id="in_charge" class="form-control select-input me-2 {{ (int)$project->in_charge === (int)$project->user_id ? 'is-invalid' : '' }}">
                    <option value="" disabled>Select In-Charge</option>
                    @foreach($users as $potential_in_charge)
                        @if($potential_in_charge->province == $user->province && ($potential_in_charge->role == 'applicant' || $potential_in_charge->role == 'executor'))
                            <option value="{{ $potential_in_charge->id }}"
                                    data-mobile="{{ $potential_in_charge->phone }}"
                                    data-email="{{ $potential_in_charge->email }}"
                                    {{ (int)$project->in_charge === (int)$potential_in_charge->id ? 'selected' : '' }}>
                                {{ $potential_in_charge->name }}
                            </option>
                        @endif
                    @endforeach
                </select>

                {{-- Hidden or optional display of in-charge name (if you need it) --}}
                <input type="hidden" name="in_charge_name" id="in_charge_name"
                       value="{{ old('in_charge_name', $project->in_charge_name) }}">

                @php
                    use App\Constants\ProjectStatus;
                    $editableStatuses = ProjectStatus::getEditableStatuses();
                    $canEditInCharge = in_array($project->status, $editableStatuses);
                @endphp
                <input type="text" name="in_charge_mobile" id="in_charge_mobile"
                       class="form-control {{ $canEditInCharge ? 'select-input' : 'readonly-input' }} me-2"
                       value="{{ old('in_charge_mobile', $project->in_charge_mobile) }}"
                       {{ $canEditInCharge ? '' : 'readonly' }}>

                <input type="text" name="in_charge_email" id="in_charge_email"
                       class="form-control {{ $canEditInCharge ? 'select-input' : 'readonly-input' }}"
                       value="{{ old('in_charge_email', $project->in_charge_email) }}"
                       {{ $canEditInCharge ? '' : 'readonly' }}>
            </div>
            {{-- Alert if In-Charge is same as Applicant --}}
            <div id="in_charge_alert" class="alert alert-danger mt-2" style="display: none;">
                <strong>Warning:</strong> Project In-Charge cannot be the same as Project Applicant. Please select a different person from the dropdown.
            </div>
        </div>

        {{-- FULL ADDRESS (Phase 2.4: gi_full_address for GeneralInfo ownership) --}}
        <div class="mb-3">
            <label for="gi_full_address" class="form-label">Full Address</label>
            <textarea name="gi_full_address" id="gi_full_address" class="form-control select-input sustainability-textarea" rows="2"
                     >{{ old('gi_full_address', $project->full_address ?? $user->address) }}</textarea>
        </div>

        {{-- OVERALL PROJECT PERIOD --}}
        <div class="mb-3">
            <label for="overall_project_period" class="form-label">Overall Project Period (Years)</label>
            <select name="overall_project_period" id="overall_project_period"
                    class="form-control select-input">
                <option value="" disabled>Select Period</option>
                @for($i=1; $i<=4; $i++)
                    <option value="{{ $i }}" {{ (int)old('overall_project_period', $project->overall_project_period) === $i ? 'selected' : '' }}>
                        {{ $i }} Year{{ $i > 1 ? 's' : '' }}
                    </option>
                @endfor
            </select>
        </div>

        {{-- CURRENT PHASE --}}
        <div class="mb-3">
            <label for="current_phase" class="form-label">Current Phase</label>
            <select name="current_phase" id="current_phase"
                    class="form-control select-input" >
                <option value="" disabled>Select Phase</option>
                @php
                    $selectedPeriod = (int)old('overall_project_period', $project->overall_project_period);
                    // If there's no explicit overall_project_period, default to 4
                    $limit = $selectedPeriod > 0 ? $selectedPeriod : 4;
                @endphp
                @for($phase = 1; $phase <= $limit; $phase++)
                    <option value="{{ $phase }}"
                        {{ (int)old('current_phase', $project->current_phase) === $phase ? 'selected' : '' }}>
                        Phase {{ $phase }}
                    </option>
                @endfor
            </select>
        </div>

        {{-- COMMENCEMENT MONTH --}}
        <div class="mb-3">
            <label for="commencement_month" class="form-label">Commencement Month</label>
            <select name="commencement_month" id="commencement_month"
                    class="form-control select-input">
                <option value="" disabled>Select Month</option>
                @for($month = 1; $month <= 12; $month++)
                    <option value="{{ $month }}"
                        {{ (int)old('commencement_month', $project->commencement_month) === $month ? 'selected' : '' }}>
                        {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                    </option>
                @endfor
            </select>
        </div>

        {{-- COMMENCEMENT YEAR --}}
        <div class="mb-3">
            <label for="commencement_year" class="form-label">Commencement Year</label>
            <select name="commencement_year" id="commencement_year"
                    class="form-control select-input">
                <option value="" disabled>Select Year</option>
                @for($year = now()->year; $year >= 2000; $year--)
                    <option value="{{ $year }}"
                        {{ (int)old('commencement_year', $project->commencement_year) === $year ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endfor
            </select>
        </div>

        {{-- OVERALL PROJECT BUDGET --}}
        <div class="mb-3">
            <label for="overall_project_budget" class="form-label">Overall Project Budget (Rs.)</label>
            <input type="number" name="overall_project_budget" id="overall_project_budget"
                   class="form-control select-input {{ ($budgetLockedByApproval ?? false) ? 'readonly-input' : '' }}"
                   value="{{ old('overall_project_budget', $project->overall_project_budget) }}"
                   @if($budgetLockedByApproval ?? false) readonly disabled @endif>
            @if($budgetLockedByApproval ?? false)
                <div class="form-text text-warning mt-1">
                    <i class="fas fa-lock"></i> Project is approved. Budget edits are locked until the project is reverted.
                </div>
            @endif
        </div>

        {{-- COORDINATOR INDIA --}}
        <div class="mb-3">
            @php
                $coordinator_india = $users
                    ->where('role', 'coordinator')
                    ->where('province', 'Generalate')
                    ->first();
            @endphp
            <label for="coordinator_india" class="form-label">Project Co-Ordinator, India</label>
            <div class="d-flex">
                @if($coordinator_india)
                    <!-- If you want this to remain read-only as in create partial -->
                    <input type="hidden" name="coordinator_india"
                           value="{{ $coordinator_india->id }}">
                    <input type="text" name="coordinator_india_name"
                           class="form-control readonly-input me-2"
                           value="{{ $coordinator_india->name }}" readonly>
                    <input type="text" name="coordinator_india_phone"
                           class="form-control readonly-input me-2"
                           value="{{ $coordinator_india->phone }}" readonly>
                    <input type="text" name="coordinator_india_email"
                           class="form-control readonly-input"
                           value="{{ $coordinator_india->email }}" readonly>
                @else
                    <input type="text" name="coordinator_india_name"
                           class="form-control readonly-input me-2"
                           placeholder="Name not found for Project Co-Ordinator, India" readonly>
                    <input type="text" name="coordinator_india_phone"
                           class="form-control readonly-input me-2"
                           placeholder="Phone not updated for Project Co-Ordinator, India" readonly>
                    <input type="text" name="coordinator_india_email"
                           class="form-control readonly-input"
                           placeholder="Email not found for Project Co-Ordinator, India" readonly>
                @endif
            </div>
        </div>

        {{-- COORDINATOR LUZERN --}}
        <div class="mb-3">
            @php
                $coordinator_luzern = $users
                    ->where('role', 'coordinator')
                    ->where('province', 'Luzern')
                    ->first();
            @endphp
            <label for="coordinator_luzern" class="form-label">Mission Co-Ordinator, Luzern, Switzerland</label>
            <div class="d-flex">
                @if($coordinator_luzern)
                    <!-- If you want this to remain read-only as in create partial -->
                    <input type="hidden" name="coordinator_luzern"
                           value="{{ $coordinator_luzern->id }}">
                    <input type="text" name="coordinator_luzern_name"
                           class="form-control readonly-input me-2"
                           value="{{ $coordinator_luzern->name }}" readonly>
                    <input type="text" name="coordinator_luzern_phone"
                           class="form-control readonly-input me-2"
                           value="{{ $coordinator_luzern->phone }}" readonly>
                    <input type="text" name="coordinator_luzern_email"
                           class="form-control readonly-input"
                           value="{{ $coordinator_luzern->email }}" readonly>
                @else
                    <input type="text" name="coordinator_luzern_name"
                           class="form-control readonly-input me-2"
                           placeholder="Name not found for Project Co-Ordinator, Luzern, Switzerland" readonly>
                    <input type="text" name="coordinator_luzern_phone"
                           class="form-control readonly-input me-2"
                           placeholder="Phone not found for Project Co-Ordinator, Luzern, Switzerland" readonly>
                    <input type="text" name="coordinator_luzern_email"
                           class="form-control readonly-input"
                           placeholder="Email not found for Project Co-Ordinator, Luzern, Switzerland" readonly>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Inline Scripts for Dynamic Behavior --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const overallProjectPeriodSelect = document.getElementById('overall_project_period');
        const currentPhaseSelect = document.getElementById('current_phase');

        const inChargeSelect = document.getElementById('in_charge');
        const inChargeMobile = document.getElementById('in_charge_mobile');
        const inChargeEmail = document.getElementById('in_charge_email');
        const inChargeNameHidden = document.getElementById('in_charge_name'); // if needed

        // 1. Update the Current Phase dropdown based on Overall Project Period
        // Phase 2 Fix: Preserve selected phase when regenerating options
        function updatePhaseOptions() {
            const projectPeriod = parseInt(overallProjectPeriodSelect.value) || 0;
            const currentSelectedPhase = currentPhaseSelect.value; // Preserve current selection
            
            currentPhaseSelect.innerHTML = '<option value="" disabled>Select Phase</option>';
            for (let i = 1; i <= projectPeriod; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `Phase ${i}`;
                
                // Restore selection if it's still valid after period change
                if (i.toString() === currentSelectedPhase) {
                    option.selected = true;
                }
                
                currentPhaseSelect.appendChild(option);
            }
        }

        // 2. Auto-fill in-charge phone & email when in-charge changes
        function handleInChargeChange() {
            const selectedOption = inChargeSelect.options[inChargeSelect.selectedIndex];
            inChargeMobile.value = selectedOption.dataset.mobile || '';
            inChargeEmail.value = selectedOption.dataset.email || '';
            if (inChargeNameHidden) {
                inChargeNameHidden.value = selectedOption.textContent.trim();
            }
        }

        // Event Listeners
        overallProjectPeriodSelect.addEventListener('change', updatePhaseOptions);
        inChargeSelect.addEventListener('change', handleInChargeChange);

        // Initialize on page load
        // Phase 2 Fix: Removed updatePhaseOptions() call - server-side rendering already sets correct phase
        // Function only runs when user changes period dropdown, preserving database value on load
        
        // If the in_charge is already selected, fill phone & email accordingly
        handleInChargeChange();

    });
</script>
{{-- Phase 4 Cleanup: Removed 214 lines of legacy commented code (2026-02-28)
     Three duplicate implementations of general_info section removed.
     See git history if needed: lines 402-615 in previous version.
--}}
