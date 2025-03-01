<!-- resources/views/projects/partials/general_info.blade.php  -->
<div class="card-body">
    <div class="mb-3">
        <label for="project_type" class="form-label">Project Type</label>
        <select name="project_type" id="project_type" class="form-control select-input" required  style="background-color: #202ba3;">
            <option value="" disabled selected>Select Project Type</option>
            <!-- Add other project types here -->
            <option value="CHILD CARE INSTITUTION" {{ old('project_type') == 'CHILD CARE INSTITUTION' ? 'selected' : '' }}>CHILD CARE INSTITUTION - Welfare home for children - Ongoing</option>
            <option value="Development Projects" {{ old('project_type') == 'Development Projects' ? 'selected' : '' }}>Development Projects - Application</option>
            <option value="Rural-Urban-Tribal" {{ old('project_type') == 'Rural-Urban-Tribal' ? 'selected' : '' }}>Education Rural-Urban-Tribal</option>
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
{{-- next pahse  --}}
<div id="predecessor-project-section" style="display: none;">
    <div class="mb-3">
        <label for="predecessor_project" class="form-label">Select Predecessor Project</label>
        <select name="predecessor_project" id="predecessor_project" class="form-control select-input">
            <option value="" disabled selected>Select Predecessor Project</option>
            @foreach($developmentProjects as $project)
                <option value="{{ $project->project_id }}">{{ $project->project_title }}</option>
            @endforeach
        </select>
    </div>
</div>



    <div class="mb-3">
        <label for="project_title" class="form-label">Project Title</label>
        <input type="text" name="project_title" class="form-control select-input" value="{{ old('project_title') }}" required  style="background-color: #202ba3;">
    </div>
    <div class="mb-3">
        <label for="society_name" class="form-label">Name of the Society / Trust</label>
        <select name="society_name" id="society_name" class="form-select" required>
            <option value="" disabled selected>Select Society / Trust</option>
            <option value="ST. ANN'S EDUCATIONAL SOCIETY" {{ $user->society_name == "ST. ANN'S EDUCATIONAL SOCIETY" ? 'selected' : '' }}>ST. ANN'S EDUCATIONAL SOCIETY</option>
            <option value="SARVAJANA SNEHA CHARITABLE TRUST" {{ $user->society_name == "SARVAJANA SNEHA CHARITABLE TRUST" ? 'selected' : '' }}>SARVAJANA SNEHA CHARITABLE TRUST</option>
            <option value="WILHELM MEYERS DEVELOPMENTAL SOCIETY" {{ $user->society_name == "WILHELM MEYERS DEVELOPMENTAL SOCIETY" ? 'selected' : '' }}>WILHELM MEYERS DEVELOPMENTAL SOCIETY</option>
            <option value="ST. ANNS'S SOCIETY, VISAKHAPATNAM" {{ $user->society_name == "ST. ANNS'S SOCIETY, VISAKHAPATNAM" ? 'selected' : '' }}>ST. ANNS'S SOCIETY, VISAKHAPATNAM</option>
            <option value="ST.ANN'S SOCIETY, SOUTHERN REGION" {{ $user->society_name == "ST.ANN'S SOCIETY, SOUTHERN REGION" ? 'selected' : '' }}>ST.ANN'S SOCIETY, SOUTHERN REGION</option>
        </select>
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
            <select name="in_charge" id="in_charge" class="form-control select-input me-2"   style="background-color: #202ba3;">
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
        <textarea name="full_address" class="form-control select-input" rows="2"  style="background-color: #091122;">{{ old('full_address', $user->address) }}</textarea>
    </div>
    <div class="mb-3">
        <label for="overall_project_period" class="form-label">Overall Project Period</label>
        <select name="overall_project_period" id="overall_project_period" class="form-control select-input"  style="background-color: #202ba3;">
            <option value="" disabled selected>Select Period</option>
            <option value="1" {{ old('overall_project_period') == 1 ? 'selected' : '' }}>1 Year</option>
            <option value="2" {{ old('overall_project_period') == 2 ? 'selected' : '' }}>2 Years</option>
            <option value="3" {{ old('overall_project_period') == 3 ? 'selected' : '' }}>3 Years</option>
            <option value="4" {{ old('overall_project_period') == 4 ? 'selected' : '' }}>4 Years</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="current_phase" class="form-label">Current Phase</label>
        <select name="current_phase" id="current_phase" class="form-control readonly-select"  style="background-color: #202ba3;">
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
        <input type="number" name="overall_project_budget" id="overall_project_budget" class="form-control select-input" value="{{ old('overall_project_budget') }}">
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

{{-- <script>
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
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const projectTypeDropdown = document.getElementById('project_type');
    const predecessorProjectSection = document.getElementById('predecessor-project-section');
    const phaseSelect = document.getElementById('current_phase');
    const overallProjectPeriodDropdown = document.getElementById('overall_project_period');

    // Function to toggle predecessor project dropdown visibility
    function togglePredecessorProjectSection() {
        const selectedType = projectTypeDropdown.value;

        if (selectedType === 'Development Projects' || selectedType === 'NEXT PHASE - DEVELOPMENT PROPOSAL') {
            predecessorProjectSection.style.display = 'block';
        } else {
            predecessorProjectSection.style.display = 'none';
        }
    }

    // Function to update the phase options based on overall project period
    function updatePhaseOptions() {
        const projectPeriod = parseInt(overallProjectPeriodDropdown.value);

        // Clear previous options
        phaseSelect.innerHTML = '<option value="" disabled selected>Select Phase</option>';

        // Add new options based on the selected value
        for (let i = 1; i <= projectPeriod; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.text = `Phase ${i}`;
            phaseSelect.appendChild(option);
        }
    }

    // Event listener for project type dropdown
    projectTypeDropdown.addEventListener('change', togglePredecessorProjectSection);

    // Event listener for overall project period dropdown
    overallProjectPeriodDropdown.addEventListener('change', updatePhaseOptions);

    // Initialize the state on page load
    togglePredecessorProjectSection();
});

</script> --}}

<script>

    document.addEventListener('DOMContentLoaded', function () {
    const overallProjectPeriod = document.getElementById('overall_project_period');
    const currentPhase = document.getElementById('current_phase');
    const projectTypeDropdown = document.getElementById('project_type');
    const predecessorProjectDropdown = document.getElementById('predecessor_project');
    const predecessorProjectSection = document.getElementById('predecessor-project-section');

    // Update the current phase options dynamically
    overallProjectPeriod.addEventListener('change', function () {
        const projectPeriod = parseInt(this.value) || 0;
        currentPhase.innerHTML = '<option value="" disabled selected>Select Phase</option>';
        for (let i = 1; i <= projectPeriod; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `Phase ${i}`;
            currentPhase.appendChild(option);
        }
    });

    // Toggle Predecessor Project section based on project type
    projectTypeDropdown.addEventListener('change', function () {
        const projectType = this.value;
        predecessorProjectSection.style.display =
            // (projectType === 'NEXT PHASE - DEVELOPMENT PROPOSAL' || projectType === 'Development Projects')
            (projectType === 'NEXT PHASE - DEVELOPMENT PROPOSAL' )
            ? 'block'
            : 'none';
    });

    // Populate fields based on selected predecessor project
    predecessorProjectDropdown.addEventListener('change', function () {
        const selectedProjectId = this.value;

        if (selectedProjectId) {
            fetch(`/projects/${selectedProjectId}/details`)
                .then((response) => response.json())
                .then((data) => {
                    // Populate fields
                    document.getElementById('project_title').value = data.project_title || '';
                    document.getElementById('society_name').value = data.society_name || '';
                    document.getElementById('president_name').value = data.president_name || '';
                    document.getElementById('applicant_name').value = data.applicant_name || '';
                    document.getElementById('applicant_mobile').value = data.applicant_mobile || '';
                    document.getElementById('applicant_email').value = data.applicant_email || '';
                    document.getElementById('in_charge').value = data.in_charge || '';
                    document.getElementById('full_address').value = data.full_address || '';
                    document.getElementById('overall_project_period').value = data.overall_project_period || '';
                    document.getElementById('current_phase').value = data.current_phase || '';
                    document.getElementById('commencement_month').value = data.commencement_month || '';
                    document.getElementById('commencement_year').value = data.commencement_year || '';
                    document.getElementById('overall_project_budget').value = data.overall_project_budget || '';
                })
                .catch((error) => {
                    console.error('Error fetching predecessor project data:', error);
                    alert('Failed to fetch project details. Please try again.');
                });
        }
    });

    // Initialize visibility on page load
    if (
         projectTypeDropdown.value === 'NEXT PHASE - DEVELOPMENT PROPOSAL'
        // projectTypeDropdown.value === 'NEXT PHASE - DEVELOPMENT PROPOSAL' ||
        // projectTypeDropdown.value === 'Development Projects'
    ) {
        predecessorProjectSection.style.display = 'block';
    } else {
        predecessorProjectSection.style.display = 'none';
    }
});
// toggle trust or society
// document.addEventListener('DOMContentLoaded', function () {
//     const toggleSocietySelectBtn = document.getElementById('toggleSocietySelect');
//     const societyNameInput = document.getElementById('society_name');
//     const societyNameSelect = document.getElementById('society_name_select');

//     // Toggle between input and dropdown
//     toggleSocietySelectBtn.addEventListener('click', function () {
//         if (societyNameSelect.style.display === 'none') {
//             societyNameSelect.style.display = 'block';
//             societyNameInput.readOnly = true;
//         } else {
//             societyNameSelect.style.display = 'none';
//             societyNameInput.readOnly = false;
//             societyNameInput.value = societyNameSelect.value;
//         }
//     });

//     // Update the input when a new society is selected
//     societyNameSelect.addEventListener('change', function () {
//         societyNameInput.value = this.value;
//     });
// });


</script>
