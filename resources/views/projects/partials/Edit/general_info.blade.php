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
