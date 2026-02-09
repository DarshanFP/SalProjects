<!-- resources/views/projects/partials/general_info.blade.php -->
<div class="card-body">
    <div class="mb-3">
        <label for="project_type" class="form-label">Project Type</label>
        <select name="project_type" id="project_type" class="form-control select-input" required>
            <option value="" disabled selected>Select Project Type</option>
            <option value="{{ \App\Constants\ProjectType::CHILD_CARE_INSTITUTION }}" {{ old('project_type') == \App\Constants\ProjectType::CHILD_CARE_INSTITUTION ? 'selected' : '' }}>CHILD CARE INSTITUTION - Welfare home for children - Ongoing</option>
            <option value="{{ \App\Constants\ProjectType::DEVELOPMENT_PROJECTS }}" {{ old('project_type') == \App\Constants\ProjectType::DEVELOPMENT_PROJECTS ? 'selected' : '' }}>Development Projects - Application</option>
            <option value="{{ \App\Constants\ProjectType::RURAL_URBAN_TRIBAL }}" {{ old('project_type') == \App\Constants\ProjectType::RURAL_URBAN_TRIBAL ? 'selected' : '' }}>Education Rural-Urban-Tribal</option>
            <option value="{{ \App\Constants\ProjectType::INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL }}" {{ old('project_type') == \App\Constants\ProjectType::INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL ? 'selected' : '' }}>Institutional Ongoing Group Educational proposal</option>
            <option value="{{ \App\Constants\ProjectType::LIVELIHOOD_DEVELOPMENT_PROJECTS }}" {{ old('project_type') == \App\Constants\ProjectType::LIVELIHOOD_DEVELOPMENT_PROJECTS ? 'selected' : '' }}>Livelihood Development Projects</option>
            <option value="{{ \App\Constants\ProjectType::CRISIS_INTERVENTION_CENTER }}" {{ old('project_type') == \App\Constants\ProjectType::CRISIS_INTERVENTION_CENTER ? 'selected' : '' }}>PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER - Application</option>
            <option value="{{ \App\Constants\ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL }}" {{ old('project_type') == \App\Constants\ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL ? 'selected' : '' }}>NEXT PHASE - DEVELOPMENT PROPOSAL</option>
            <option value="{{ \App\Constants\ProjectType::RESIDENTIAL_SKILL_TRAINING }}" {{ old('project_type') == \App\Constants\ProjectType::RESIDENTIAL_SKILL_TRAINING ? 'selected' : '' }}>Residential Skill Training Proposal 2</option>
            <option value="{{ \App\Constants\ProjectType::INDIVIDUAL_ONGOING_EDUCATIONAL }}" {{ old('project_type') == \App\Constants\ProjectType::INDIVIDUAL_ONGOING_EDUCATIONAL ? 'selected' : '' }}>Individual - Ongoing Educational support - Project Application</option>
            <option value="{{ \App\Constants\ProjectType::INDIVIDUAL_LIVELIHOOD_APPLICATION }}" {{ old('project_type') == \App\Constants\ProjectType::INDIVIDUAL_LIVELIHOOD_APPLICATION ? 'selected' : '' }}>Individual - Livelihood Application</option>
            <option value="{{ \App\Constants\ProjectType::INDIVIDUAL_ACCESS_TO_HEALTH }}" {{ old('project_type') == \App\Constants\ProjectType::INDIVIDUAL_ACCESS_TO_HEALTH ? 'selected' : '' }}>Individual - Access to Health - Project Application</option>
            <option value="{{ \App\Constants\ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL }}" {{ old('project_type') == \App\Constants\ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL ? 'selected' : '' }}>Individual - Initial - Educational support - Project Application</option>
        </select>
    </div>

    <!-- Predecessor Project Selection (Always Visible for All Project Types) -->
        <div class="mb-3">
        <label for="predecessor_project_id" class="form-label">Select Predecessor Project (Optional)</label>
            <select name="predecessor_project_id" id="predecessor_project_id" class="form-control select-input">
                <option value="" selected>None</option>
            @if(isset($developmentProjects) && $developmentProjects->count() > 0)
                @foreach($developmentProjects as $project)
                    <option value="{{ $project->project_id }}" {{ old('predecessor_project_id') == $project->project_id ? 'selected' : '' }}>
                        {{ $project->project_title }} (Phase {{ $project->current_phase }}/{{ $project->overall_project_period }})
                    </option>
                @endforeach
            @else
                <option value="" disabled>No development projects available</option>
            @endif
            </select>
        @error('predecessor_project_id')
            <span class="text-danger">{{ $message }}</span>
        @enderror
        <small class="form-text text-muted">Select a previous project if this is a continuation or related project.</small>
    </div>

    <div class="mb-3">
        <label for="project_title" class="form-label">Project Title</label>
        <input type="text" name="project_title" id="project_title" class="form-control select-input" value="{{ old('project_title') }}" required>
    </div>
    <div class="mb-3">
        <label for="society_name" class="form-label">Name of the Society / Trust</label>
        <select name="society_name" id="society_name" class="form-select" required>
            <option value="" disabled selected>Select Society / Trust</option>
            <option value="ST. ANN'S EDUCATIONAL SOCIETY" {{ $user->society_name == "ST. ANN'S EDUCATIONAL SOCIETY" ? 'selected' : '' }}>ST. ANN'S EDUCATIONAL SOCIETY</option>
            <option value="SARVAJANA SNEHA CHARITABLE TRUST" {{ $user->society_name == "SARVAJANA SNEHA CHARITABLE TRUST" ? 'selected' : '' }}>SARVAJANA SNEHA CHARITABLE TRUST</option>
            <option value="WILHELM MEYERS DEVELOPMENTAL SOCIETY" {{ $user->society_name == "WILHELM MEYERS DEVELOPMENTAL SOCIETY" ? 'selected' : '' }}>WILHELM MEYERS DEVELOPMENTAL SOCIETY</option>
            <option value="ST. ANNS'S SOCIETY, VISAKHAPATNAM" {{ $user->society_name == "ST. ANN'S SOCIETY, VISAKHAPATNAM" ? 'selected' : '' }}>ST. ANN'S SOCIETY, VISAKHAPATNAM</option>
            <option value="ST.ANN'S SOCIETY, SOUTHERN REGION" {{ $user->society_name == "ST.ANN'S SOCIETY, SOUTHERN REGION" ? 'selected' : '' }}>ST.ANN'S SOCIETY, SOUTHERN REGION</option>
            <option value="ST. ANNE'S SOCIETY" {{ $user->society_name == "ST. ANNE'S SOCIETY" ? 'selected' : '' }}>ST. ANNE'S SOCIETY</option>
            <option value="BIARA SANTA ANNA, MAUSAMBI" {{ $user->society_name == "BIARA SANTA ANNA, MAUSAMBI" ? 'selected' : '' }}>BIARA SANTA ANNA, MAUSAMBI</option>
            <option value="ST. ANN'S CONVENT, LURO" {{ $user->society_name == "ST. ANN'S CONVENT, LURO" ? 'selected' : '' }}>ST. ANN'S CONVENT, LURO</option>
            <option value="MISSIONARY SISTERS OF ST. ANN" {{ $user->society_name == "MISSIONARY SISTERS OF ST. ANN" ? 'selected' : '' }}>MISSIONARY SISTERS OF ST. ANN</option>
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
            <select name="in_charge" id="in_charge" class="form-control select-input me-2">
                <option value="" disabled selected>Select In-Charge</option>
                @foreach($users as $potential_in_charge)
                    @if($potential_in_charge->province == $user->province && ($potential_in_charge->role == 'applicant' || $potential_in_charge->role == 'executor'))
                        <option value="{{ $potential_in_charge->id }}" data-name="{{ $potential_in_charge->name }}" data-mobile="{{ $potential_in_charge->phone }}" data-email="{{ $potential_in_charge->email }}" {{ old('in_charge') == $potential_in_charge->id ? 'selected' : '' }}>
                            {{ $potential_in_charge->name }}
                        </option>
                    @endif
                @endforeach
            </select>
            <input type="hidden" name="in_charge_name" id="in_charge_name" class="select-input">
            <input type="text" name="in_charge_mobile" id="in_charge_mobile" class="form-control readonly-input me-2" readonly>
            <input type="text" name="in_charge_email" id="in_charge_email" class="form-control readonly-input" readonly>
        </div>
    </div>
    <div class="mb-3">
        <label for="gi_full_address" class="form-label">Full Address</label>
        <textarea name="gi_full_address" id="gi_full_address" class="form-control textarea-secondary sustainability-textarea" rows="2">{{ old('gi_full_address', $user->address) }}</textarea>
    </div>
    <div class="mb-3">
        <label for="overall_project_period" class="form-label">Overall Project Period</label>
        <select name="overall_project_period" id="overall_project_period" class="form-control select-input">
            <option value="" disabled selected>Select Period</option>
            <option value="1" {{ old('overall_project_period') == 1 ? 'selected' : '' }}>1 Year</option>
            <option value="2" {{ old('overall_project_period') == 2 ? 'selected' : '' }}>2 Years</option>
            <option value="3" {{ old('overall_project_period') == 3 ? 'selected' : '' }}>3 Years</option>
            <option value="4" {{ old('overall_project_period') == 4 ? 'selected' : '' }}>4 Years</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="current_phase" class="form-label">Current Phase</label>
        <select name="current_phase" id="current_phase" class="form-control select-input">
            <option value="" disabled selected>Select Phase</option>
            @for ($i = 1; $i <= 10; $i++)
                <option value="{{ $i }}" {{ old('current_phase') == $i ? 'selected' : '' }}>Phase {{ $i }}</option>
            @endfor
        </select>
    </div>
    <div class="mb-3">
        <label for="commencement_month" class="form-label">Commencement Month</label>
        <select name="commencement_month" id="commencement_month" class="form-control select-input">
            <option value="" disabled selected>Select Month</option>
            @for ($month = 1; $month <= 12; $month++)
                <option value="{{ $month }}">{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
            @endfor
        </select>
    </div>
    <div class="mb-3">
        <label for="commencement_year" class="form-label">Commencement Year</label>
        <select name="commencement_year" id="commencement_year" class="form-control select-input">
            <option value="" disabled selected>Select Year</option>
            @for ($year = now()->year; $year >= 2000; $year--)
                <option value="{{ $year }}">{{ $year }}</option>
            @endfor
        </select>
    </div>
    <div class="mb-3">
        <label for="overall_project_budget" class="form-label">Overall Project Budget</label>
        <input type="number" name="overall_project_budget" id="overall_project_budget" class="form-control select-input" value="{{ old('overall_project_budget') }}" readonly>
    </div>
    <div class="mb-3">
        @php $coordinator_india = $users->firstWhere('role', 'coordinator')->firstWhere('province', 'Generalate'); @endphp
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
    <!-- Mission Co-Ordinator, Luzern, Switzerland -->
    {{-- shall add only if there is nerw appointment for Mission Co-Ordinator, Luzern, Switzerland --}}
    {{-- <div class="mb-3">
        @php $coordinator_luzern = $users->firstWhere('role', 'coordinator')->firstWhere('province', 'Luzern'); @endphp
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
    </div> --}}
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const predecessorProjectDropdown = document.getElementById('predecessor_project_id');

    // Populate fields based on selected predecessor project
    if (predecessorProjectDropdown) {
    predecessorProjectDropdown.addEventListener('change', function () {
        const selectedProjectId = this.value;

        if (selectedProjectId) {
            const url = '/executor/projects/' + selectedProjectId + '/details';

            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`Network response was not ok: ${response.status} - ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                    // Populate form fields with predecessor project data
                const fields = {
                    'project_title': data.project_title,
                    'society_name': data.society_name,
                    'president_name': data.president_name,
                    'applicant_name': data.applicant_name,
                    'applicant_mobile': data.applicant_mobile,
                    'applicant_email': data.applicant_email,
                    'in_charge': data.in_charge,
                    'in_charge_name': data.in_charge_name,
                    'in_charge_mobile': data.in_charge_mobile,
                    'in_charge_email': data.in_charge_email,
                    'gi_full_address': data.full_address,
                    'overall_project_period': data.overall_project_period,
                    'current_phase': data.current_phase,
                    'commencement_month': data.commencement_month,
                    'commencement_year': data.commencement_year,
                        'overall_project_budget': data.overall_project_budget,
                        // Populate new Key Information fields
                        'initial_information': data.initial_information,
                        'target_beneficiaries': data.target_beneficiaries,
                        'general_situation': data.general_situation,
                        'need_of_project': data.need_of_project,
                        'goal': data.goal
                };

                for (const [id, value] of Object.entries(fields)) {
                    const element = document.getElementById(id);
                    if (element) {
                        element.value = value || '';
                            // Auto-resize Key Information textareas after populating
                            if (['initial_information', 'target_beneficiaries', 'general_situation', 'need_of_project', 'goal'].includes(id)) {
                                element.style.height = 'auto';
                                element.style.height = (element.scrollHeight) + 'px';
                            }
                    }
                }

                // Pass beneficiaries data to the parent view
                window.predecessorBeneficiaries = data.beneficiaries_areas || [];

                // Trigger an event to notify the parent view
                const event = new CustomEvent('predecessorDataFetched', { detail: data });
                document.dispatchEvent(event);
            })
            .catch(error => {
                    console.error('Error fetching predecessor project data:', error);
                    alert('Failed to fetch project details. Please try again.');
                });
        }
    });
    }

});
</script>

<style>
/* Auto-resize textarea for general info section */
.sustainability-textarea {
    resize: vertical;
    min-height: 80px;
    height: auto;
    overflow-y: hidden;
    line-height: 1.5;
    padding: 8px 12px;
}

.sustainability-textarea:focus {
    overflow-y: auto;
}
</style>

<meta name="csrf-token" content="{{ csrf_token() }}">
