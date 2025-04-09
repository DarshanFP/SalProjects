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
            <select name="project_type" id="project_type" class="form-control select-input"  style="background-color: #202ba3;">
                <option value="" disabled>Select Project Type</option>
                <option value="CHILD CARE INSTITUTION"
                    {{ $project->project_type == 'CHILD CARE INSTITUTION' ? 'selected' : '' }}>
                    CHILD CARE INSTITUTION - Welfare home for children - Ongoing
                </option>
                <option value="Development Projects"
                    {{ $project->project_type == 'Development Projects' ? 'selected' : '' }}>
                    Development Projects - Application
                </option>
                <option value="Rural-Urban-Tribal"
                    {{ $project->project_type == 'Rural-Urban-Tribal' ? 'selected' : '' }}>
                    Education Rural-Urban-Tribal
                </option>
                <option value="Institutional Ongoing Group Educational proposal"
                    {{ $project->project_type == 'Institutional Ongoing Group Educational proposal' ? 'selected' : '' }}>
                    Institutional Ongoing Group Educational proposal
                </option>
                <option value="Livelihood Development Projects"
                    {{ $project->project_type == 'Livelihood Development Projects' ? 'selected' : '' }}>
                    Livelihood Development Projects
                </option>
                <option value="PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER"
                    {{ $project->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' ? 'selected' : '' }}>
                    PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER - Application
                </option>
                {{-- <option value="NEXT PHASE - DEVELOPMENT PROPOSAL"
                    {{ $project->project_type == 'NEXT PHASE - DEVELOPMENT PROPOSAL' ? 'selected' : '' }}>
                    NEXT PHASE - DEVELOPMENT PROPOSAL
                </option> --}}
                <option value="Residential Skill Training Proposal 2"
                    {{ $project->project_type == 'Residential Skill Training Proposal 2' ? 'selected' : '' }}>
                    Residential Skill Training Proposal 2
                </option>
                <option value="Individual - Ongoing Educational support"
                    {{ $project->project_type == 'Individual - Ongoing Educational support' ? 'selected' : '' }}>
                    Individual - Ongoing Educational support - Project Application
                </option>
                <option value="Individual - Livelihood Application"
                    {{ $project->project_type == 'Individual - Livelihood Application' ? 'selected' : '' }}>
                    Individual - Livelihood Application
                </option>
                <option value="Individual - Access to Health"
                    {{ $project->project_type == 'Individual - Access to Health' ? 'selected' : '' }}>
                    Individual - Access to Health - Project Application
                </option>
                <option value="Individual - Initial - Educational support"
                    {{ $project->project_type == 'Individual - Initial - Educational support' ? 'selected' : '' }}>
                    Individual - Initial - Educational support - Project Application
                </option>
            </select>
        </div>

        {{-- PREDECESSOR PROJECT (shown only for Development or Next Phase)--}}
        <div id="predecessor-project-section" style="display: none;">
            <div class="mb-3">
                <label for="predecessor_project" class="form-label">Select Predecessor Project</label>
                <select name="predecessor_project" id="predecessor_project" class="form-control select-input">
                    <option value="" disabled>Select Predecessor Project</option>
                    @foreach($developmentProjects as $devProject)
                        <option value="{{ $devProject->project_id }}"
                            {{ $devProject->project_id == $project->predecessor_project ? 'selected' : '' }}>
                            {{ $devProject->project_title }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- PROJECT TITLE --}}
        <div class="mb-3">
            <label for="project_title" class="form-label">Project Title</label>
            <input type="text" name="project_title" id="project_title"
                   class="form-control select-input" style="background-color: #202ba3;"
                   value="{{ old('project_title', $project->project_title) }}" required>
        </div>

        {{-- NAME OF SOCIETY / TRUST --}}
        <div class="mb-3">
            <label for="society_name" class="form-label">Name of the Society / Trust</label>
            <select name="society_name" id="society_name" class="form-select" required>
                <option value="" disabled>Select Society / Trust</option>
                <option value="ST. ANN'S EDUCATIONAL SOCIETY"
                    {{ $project->society_name == "ST. ANN'S EDUCATIONAL SOCIETY" ? 'selected' : '' }}>
                    ST. ANN'S EDUCATIONAL SOCIETY
                </option>
                <option value="SARVAJANA SNEHA CHARITABLE TRUST"
                    {{ $project->society_name == "SARVAJANA SNEHA CHARITABLE TRUST" ? 'selected' : '' }}>
                    SARVAJANA SNEHA CHARITABLE TRUST
                </option>
                <option value="WILHELM MEYERS DEVELOPMENTAL SOCIETY"
                    {{ $project->society_name == "WILHELM MEYERS DEVELOPMENTAL SOCIETY" ? 'selected' : '' }}>
                    WILHELM MEYERS DEVELOPMENTAL SOCIETY
                </option>
                <option value="ST. ANNS'S SOCIETY, VISAKHAPATNAM"
                    {{ $project->society_name == "ST. ANNS'S SOCIETY, VISAKHAPATNAM" ? 'selected' : '' }}>
                    ST. ANNS'S SOCIETY, VISAKHAPATNAM
                </option>
                <option value="ST.ANN'S SOCIETY, SOUTHERN REGION"
                    {{ $project->society_name == "ST.ANN'S SOCIETY, SOUTHERN REGION" ? 'selected' : '' }}>
                    ST.ANN'S SOCIETY, SOUTHERN REGION
                </option>
            </select>
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
                <select name="in_charge" id="in_charge" class="form-control select-input me-2" style="background-color: #202ba3;">
                    <option value="" disabled>Select In-Charge</option>
                    @foreach($users as $potential_in_charge)
                        @if($potential_in_charge->province == $user->province)
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

                <input type="text" name="in_charge_mobile" id="in_charge_mobile"
                       class="form-control readonly-input me-2"
                       value="{{ old('in_charge_mobile', $project->in_charge_mobile) }}"
                       readonly>

                <input type="text" name="in_charge_email" id="in_charge_email"
                       class="form-control readonly-input"
                       value="{{ old('in_charge_email', $project->in_charge_email) }}"
                       readonly>
            </div>
        </div>

        {{-- FULL ADDRESS --}}
        <div class="mb-3">
            <label for="full_address" class="form-label">Full Address</label>
            <textarea name="full_address" id="full_address" class="form-control select-input" rows="2"
                      style="background-color: #091122;">{{ old('full_address', $project->full_address ?? $user->address) }}</textarea>
        </div>

        {{-- OVERALL PROJECT PERIOD --}}
        <div class="mb-3">
            <label for="overall_project_period" class="form-label">Overall Project Period (Years)</label>
            <select name="overall_project_period" id="overall_project_period"
                    class="form-control select-input" style="background-color: #202ba3;" required>
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
                    class="form-control select-input" style="background-color: #202ba3;" >
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
                    class="form-control select-input" style="background-color: #202ba3;">
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
                    class="form-control select-input" style="background-color: #202ba3;">
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
                   class="form-control select-input" style="background-color: #202ba3;"
                   value="{{ old('overall_project_budget', $project->overall_project_budget) }}" required>
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
        const projectTypeDropdown = document.getElementById('project_type');
        const predecessorProjectSection = document.getElementById('predecessor-project-section');
        const predecessorProjectDropdown = document.getElementById('predecessor_project');

        const overallProjectPeriodSelect = document.getElementById('overall_project_period');
        const currentPhaseSelect = document.getElementById('current_phase');

        const inChargeSelect = document.getElementById('in_charge');
        const inChargeMobile = document.getElementById('in_charge_mobile');
        const inChargeEmail = document.getElementById('in_charge_email');
        const inChargeNameHidden = document.getElementById('in_charge_name'); // if needed

        // 1. Toggle predecessor project section based on project type
        function togglePredecessorProjectSection() {
            const selectedType = projectTypeDropdown.value;
            if (selectedType === 'Development Projects' ||
                selectedType === 'NEXT PHASE - DEVELOPMENT PROPOSAL') {
                predecessorProjectSection.style.display = 'block';
            } else {
                predecessorProjectSection.style.display = 'none';
                // Optionally clear the selection if hidden
                predecessorProjectDropdown.value = '';
            }
        }

        // 2. Update the Current Phase dropdown based on Overall Project Period
        function updatePhaseOptions() {
            const projectPeriod = parseInt(overallProjectPeriodSelect.value) || 0;
            currentPhaseSelect.innerHTML = '<option value="" disabled>Select Phase</option>';
            for (let i = 1; i <= projectPeriod; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `Phase ${i}`;
                currentPhaseSelect.appendChild(option);
            }
            // If there was a previously selected phase, you can re-set it here if needed
        }

        // 3. Auto-fill in-charge phone & email when in-charge changes
        function handleInChargeChange() {
            const selectedOption = inChargeSelect.options[inChargeSelect.selectedIndex];
            inChargeMobile.value = selectedOption.dataset.mobile || '';
            inChargeEmail.value = selectedOption.dataset.email || '';
            if (inChargeNameHidden) {
                inChargeNameHidden.value = selectedOption.textContent.trim();
            }
        }

        // Event Listeners
        projectTypeDropdown.addEventListener('change', togglePredecessorProjectSection);
        overallProjectPeriodSelect.addEventListener('change', updatePhaseOptions);
        inChargeSelect.addEventListener('change', handleInChargeChange);

        // Initialize on page load
        togglePredecessorProjectSection();
        updatePhaseOptions();
        // If the in_charge is already selected, fill phone & email accordingly
        handleInChargeChange();
    });
</script>
{{--<div class="mb-4 card">
    <div class="card-header">
        <h4>1. Basic Information</h4>
    </div>
    <div class="card-body">
        <!-- Project ID, Name and Type -->
        <div class="mb-3">
            <label for="project_id" class="form-label">Project ID:</label>
            <input type="text" name="project_id" id="project_id" class="form-control" value="{{ $project->project_id }}" readonly>
        </div>
        <div class="mb-3">
            <label for="project_title" class="form-label">Project Title:</label>
            <input type="text" name="project_title" id="project_title" class="form-control select-input" value="{{ $project->project_title }}" required style="background-color: #202ba3;">
        </div>

        <!-- Project Type -->
        <div class="mb-3">
            <label for="project_type" class="form-label">Project Type:</label>
            <select name="project_type" id="project_type" class="form-control select-input" required style="background-color: #202ba3;">
                <option value="" disabled>Select Project Type</option>
                @foreach(["CHILD CARE INSTITUTION", "Development Projects", "Rural-Urban-Tribal", "Institutional Ongoing Group Educational proposal", "Livelihood Development Projects", "PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER", "NEXT PHASE - DEVELOPMENT PROPOSAL", "Residential Skill Training Proposal 2", "Individual - Ongoing Educational support", "Individual - Livelihood Application", "Individual - Access to Health", "Individual - Initial - Educational support"] as $type)
                    <option value="{{ $type }}" {{ $project->project_type == $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>

        <!-- Predecessor Project Section -->
        <div id="predecessor-project-section" style="display: none;">
            <div class="mb-3">
                <label for="predecessor_project" class="form-label">Select Predecessor Project</label>
                <select name="predecessor_project" id="predecessor_project" class="form-control select-input">
                    <option value="" disabled selected>Select Predecessor Project</option>
                    @foreach($developmentProjects as $devProject)
                        <option value="{{ $devProject->project_id }}" {{ $project->predecessor_project == $devProject->project_id ? 'selected' : '' }}>{{ $devProject->project_title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Society / Trust -->
        <div class="mb-3">
            <label for="society_name" class="form-label">Name of the Society / Trust</label>
            <select name="society_name" id="society_name" class="form-select" required>
                <option value="" disabled>Select Society / Trust</option>
                @foreach(["ST. ANN'S EDUCATIONAL SOCIETY", "SARVAJANA SNEHA CHARITABLE TRUST", "WILHELM MEYERS DEVELOPMENTAL SOCIETY", "ST. ANNS'S SOCIETY, VISAKHAPATNAM", "ST.ANN'S SOCIETY, SOUTHERN REGION"] as $society)
                    <option value="{{ $society }}" {{ $project->society_name == $society ? 'selected' : '' }}>{{ $society }}</option>
                @endforeach
            </select>
        </div>

        <!-- In-Charge Selection -->
        <div class="mb-3">
            <label for="in_charge" class="form-label">Project In-Charge</label>
            <select name="in_charge" id="in_charge" class="form-control select-input me-2" required style="background-color: #202ba3;">
                <option value="" disabled>Select In-Charge</option>
                @foreach($users as $potential_in_charge)
                    @if($potential_in_charge->province == $user->province)
                        <option value="{{ $potential_in_charge->id }}" data-name="{{ $potential_in_charge->name }}" data-mobile="{{ $potential_in_charge->phone }}" data-email="{{ $potential_in_charge->email }}" {{ $project->in_charge == $potential_in_charge->id ? 'selected' : '' }}>{{ $potential_in_charge->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <!-- Overall Project Period & Current Phase -->
        <div class="mb-3">
            <label for="overall_project_period" class="form-label">Overall Project Period</label>
            <select name="overall_project_period" id="overall_project_period" class="form-control select-input" required style="background-color: #202ba3;">
                <option value="" disabled>Select Period</option>
                @for ($i = 1; $i <= 4; $i++)
                    <option value="{{ $i }}" {{ $project->overall_project_period == $i ? 'selected' : '' }}>{{ $i }} Year(s)</option>
                @endfor
            </select>
        </div>
        <div class="mb-3">
            <label for="current_phase" class="form-label">Current Phase</label>
            <select name="current_phase" id="current_phase" class="form-control select-input" required style="background-color: #202ba3;">
                <option value="" disabled>Select Phase</option>
                @for ($i = 1; $i <= $project->overall_project_period; $i++)
                    <option value="{{ $i }}" {{ $project->current_phase == $i ? 'selected' : '' }}>Phase {{ $i }}</option>
                @endfor
            </select>
        </div>

        <!-- Overall Project Budget -->
        <div class="mb-3">
            <label for="overall_project_budget" class="form-label">Overall Project Budget</label>
            <input type="number" name="overall_project_budget" id="overall_project_budget" class="form-control select-input" value="{{ $project->overall_project_budget }}">
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const projectTypeDropdown = document.getElementById('project_type');
        const predecessorProjectSection = document.getElementById('predecessor-project-section');
        const overallProjectPeriodDropdown = document.getElementById('overall_project_period');
        const phaseSelect = document.getElementById('current_phase');

        function togglePredecessorProjectSection() {
            predecessorProjectSection.style.display = (projectTypeDropdown.value === 'NEXT PHASE - DEVELOPMENT PROPOSAL' || projectTypeDropdown.value === 'Development Projects') ? 'block' : 'none';
        }

        function updatePhaseOptions() {
            const projectPeriod = parseInt(overallProjectPeriodDropdown.value) || 0;
            phaseSelect.innerHTML = '<option value="" disabled selected>Select Phase</option>';
            for (let i = 1; i <= projectPeriod; i++) {
                phaseSelect.innerHTML += `<option value="${i}">Phase ${i}</option>`;
            }
        }

        projectTypeDropdown.addEventListener('change', togglePredecessorProjectSection);
        overallProjectPeriodDropdown.addEventListener('change', updatePhaseOptions);
        togglePredecessorProjectSection();
    });
</script>

<div class="mb-4 card">
    <div class="card-header">
        <h4>1. Basic Information</h4>
    </div>
    <div class="card-body">
        <!-- Project ID, Name and type -->
        <div class="mb-3">
            <label for="project_id" class="form-label">Project ID:</label>
            <input type="text" name="project_id" id="project_id" class="form-control" value="{{ $project->project_id }}" readonly>
        </div>
        <div class="mb-3">
            <label for="project_title" class="form-label">Project Name:</label>
            <input type="text" name="project_title" id="project_title" class="form-control" value="{{ $project->project_title }}" required>
        </div>
        <!-- Project Type -->
        <div class="mb-3">
            <label for="project_type" class="form-label">Project Type:</label>
            <select name="project_type" id="project_type" class="form-control select-input" required style="background-color: #202ba3;">
                <option value="" disabled>Select Project Type</option>
                <!-- Add available project types dynamically here -->
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
        <!-- Society / Trust -->
        <div class="mb-3">
            <label for="society_name" class="form-label">Name of the Society / Trust</label>
            <select name="society_name" id="society_name" class="form-select" required>
                <option value="" disabled selected>Select Society / Trust</option>
                <option value="ST. ANN'S EDUCATIONAL SOCIETY" {{ $project->society_name == "ST. ANN'S EDUCATIONAL SOCIETY" ? 'selected' : '' }}>ST. ANN'S EDUCATIONAL SOCIETY</option>
                <option value="SARVAJANA SNEHA CHARITABLE TRUST" {{ $project->society_name == "SARVAJANA SNEHA CHARITABLE TRUST" ? 'selected' : '' }}>SARVAJANA SNEHA CHARITABLE TRUST</option>
                <option value="WILHELM MEYERS DEVELOPMENTAL SOCIETY" {{ $project->society_name == "WILHELM MEYERS DEVELOPMENTAL SOCIETY" ? 'selected' : '' }}>WILHELM MEYERS DEVELOPMENTAL SOCIETY</option>
                <option value="ST. ANNS'S SOCIETY, VISAKHAPATNAM" {{ $project->society_name == "ST. ANNS'S SOCIETY, VISAKHAPATNAM" ? 'selected' : '' }}>ST. ANNS'S SOCIETY, VISAKHAPATNAM</option>
                <option value="ST.ANN'S SOCIETY, SOUTHERN REGION" {{ $project->society_name == "ST.ANN'S SOCIETY, SOUTHERN REGION" ? 'selected' : '' }}>ST.ANN'S SOCIETY, SOUTHERN REGION</option>
            </select>
        </div>


        <!-- In-charge -->
        <div class="mb-3">
            <label for="in_charge" class="form-label">In-charge:</label>
            <select name="in_charge" id="in_charge" class="form-control me-2" required>
                <option value="" disabled>Select In-Charge</option>
                @foreach($users as $potential_in_charge)
                    @if($potential_in_charge->province == $user->province)
                        <option value="{{ $potential_in_charge->id }}" data-mobile="{{ $potential_in_charge->phone }}" data-email="{{ $potential_in_charge->email }}" {{ $potential_in_charge->id == $project->in_charge ? 'selected' : '' }}>
                            {{ $potential_in_charge->name }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>

        <!-- In-charge Mobile -->
        <div class="mb-3">
            <label for="in_charge_mobile" class="form-label">In-charge Mobile:</label>
            <input type="text" name="in_charge_mobile" id="in_charge_mobile" class="form-control" value="{{ $project->in_charge_mobile }}" readonly>
        </div>

        <!-- In-charge Email -->
        <div class="mb-3">
            <label for="in_charge_email" class="form-label">In-charge Email:</label>
            <input type="email" name="in_charge_email" id="in_charge_email" class="form-control" value="{{ $project->in_charge_email }}" readonly>
        </div>

        <!-- Overall Project Period -->
        <div class="mb-3">
            <label for="overall_project_period" class="form-label">Overall Project Period (in years):</label>
            <input type="number" name="overall_project_period" id="overall_project_period" class="form-control" value="{{ $project->overall_project_period }}" required>
        </div>

        <!-- Current Phase -->
        <div class="mb-3">
            <label for="current_phase" class="form-label">Current Phase:</label>
            <select name="current_phase" id="current_phase" class="form-control" required>
                <option value="" disabled>Select Phase</option>
                @for($i = 1; $i <= $project->overall_project_period; $i++)
                    <option value="{{ $i }}" {{ $project->current_phase == $i ? 'selected' : '' }}>
                        {{ $i }}{{ $i === 1 ? 'st' : ($i === 2 ? 'nd' : ($i === 3 ? 'rd' : 'th')) }} Phase
                    </option>
                @endfor
            </select>
        </div>

        <!-- Overall Project Budget -->
        <div class="mb-3">
            <label for="overall_project_budget" class="form-label">Overall Project Budget (Rs.):</label>
            <input type="number" name="overall_project_budget" id="overall_project_budget" class="form-control" value="{{ $project->overall_project_budget }}" required readonly>
        </div>


    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const societyNameSelect = document.getElementById('society_name');

    societyNameSelect.addEventListener('change', function () {
        console.log(`Selected Society: ${this.value}`);
        // Additional logic can be added here if needed
    });
});
</script>
--}}
