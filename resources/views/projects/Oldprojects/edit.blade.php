@extends('executor.dashboard')

@section('content')
<div class="container">
    <h1>Edit Project</h1>
    <form action="{{ route('projects.update', $project->project_id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- General Information Section -->
        <div class="mb-3 card">
            <div class="card-header">
                <h4 class="fp-text-center1">EDIT PROJECT APPLICATION FORM</h4>
            </div>
            <div class="card-header">
                <h4 class="fp-text-margin">General Information</h4>
                <h4 class="fp-text-margin">Project ID : {{ $project->project_id }}</h4>
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
                    <input type="text" name="parent_id" class="form-control" value="{{ $project->president_name }}" readonly>
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
                        <input type="text" name="executor_mobile" class="form-control me-2" value="{{ $project->executor_mobile }}" readonly>
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
                <!-- Commencement Month Selector -->
<!-- Commencement Month Selector -->
<div class="mb-3">
    <label for="commencement_month" class="form-label">Commencement Month</label>
    <select name="commencement_month" id="commencement_month" class="form-control select-input" style="background-color: #202ba3;">
        <option value="" disabled>Select Month</option>
        @for ($month = 1; $month <= 12; $month++)
            <option value="{{ $month }}" {{ $project->commencement_month == $month ? 'selected' : '' }}>
                {{ date('F', mktime(0, 0, 0, $month, 1)) }}
            </option>
        @endfor
    </select>
</div>

<!-- Commencement Year Selector -->
<div class="mb-3">
    <label for="commencement_year" class="form-label">Commencement Year</label>
    <select name="commencement_year" id="commencement_year" class="form-control select-input" style="background-color: #202ba3;">
        <option value="" disabled>Select Year</option>
        @for ($year = now()->year; $year >= 2000; $year--)
            <option value="{{ $year }}" {{ $project->commencement_year == $year ? 'selected' : '' }}>
                {{ $year }}
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

        <!-- Logical Framework Section -->
        <div class="mb-3 card">
            <div class="card-header">
                <h4>Logical Framework</h4>
            </div>
            <div class="card-body">
                @foreach($project->objectives as $objectiveIndex => $objective)
                <div class="p-3 mb-4 border rounded objective-card">
                    <h5 class="mb-3">Objective</h5>
                    <textarea name="objectives[{{ $objectiveIndex }}][objective]" class="mb-3 form-control objective-description" rows="2" required>{{ $objective->objective }}</textarea>

                    <div class="results-container">
                        <h6>Results / Outcomes</h6>
                        @foreach($objective->results as $resultIndex => $result)
                        <div class="mb-3 result-section">
                            <textarea name="objectives[{{ $objectiveIndex }}][results][{{ $resultIndex }}][result]" class="mb-3 form-control result-outcome" rows="2" required>{{ $result->result }}</textarea>
                        </div>
                        @endforeach
                        <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>
                    </div>

                    <div class="risks-container">
                        <h6>Risks</h6>
                        @foreach($objective->risks as $riskIndex => $risk)
                            <div class="mb-3 risk-section">
                                <textarea name="objectives[{{ $objectiveIndex }}][risks][{{ $riskIndex }}][risk]" class="mb-3 form-control risk-description" rows="2" required>{{ $risk->risk }}</textarea>
                            </div>
                        @endforeach
                        <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this, {{ $objectiveIndex }})">Add Risk</button>
                    </div>


                    <div class="activities-container">
                        <h6>Activities and Means of Verification</h6>
                        <table class="table table-bordered activities-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 40%;">Activities</th>
                                    <th scope="col">Means of Verification</th>
                                    <th scope="col" style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($objective->activities as $activityIndex => $activity)
                                <tr class="activity-row">
                                    <td>
                                        <textarea name="objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][activity]" class="form-control activity-description" rows="2" required>{{ $activity->activity }}</textarea>
                                    </td>
                                    <td>
                                        <textarea name="objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][verification]" class="form-control activity-verification" rows="2" required>{{ $activity->verification }}</textarea>
                                    </td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeActivity(this)">Remove</button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="button" class="mb-3 btn btn-primary" onclick="addActivity(this)">Add Activity</button>
                    </div>

                    <!-- Time Frame Section -->
                    @include('projects.partials.edit_timeframe', ['objectiveIndex' => $objectiveIndex])
                </div>
                @endforeach
            </div>
        </div>

        <div class="mb-4 card">
            <div class="card-header">
                <h4 class="mb-0">Project Sustainability, Monitoring, and Methodologies</h4>
            </div>
            <div class="card-body">
                @if($project->sustainabilities->isNotEmpty())
                    @foreach($project->sustainabilities as $sustainability)
                        <!-- Sustainability Section -->
                        <div class="mb-3">
                            <h5>Explain the Sustainability of the Project:</h5>
                            <textarea name="sustainability" class="form-control" rows="2" required>{{ $sustainability->sustainability }}</textarea>
                        </div>

                        <!-- Monitoring Process Section -->
                        <div class="mb-3">
                            <h5>Explain the Monitoring Process of the Project:</h5>
                            <textarea name="monitoring_process" class="form-control" rows="2" required>{{ $sustainability->monitoring_process }}</textarea>
                        </div>

                        <!-- Reporting Methodology Section -->
                        <div class="mb-3">
                            <h5>Explain the Methodology of Reporting:</h5>
                            <textarea name="reporting_methodology" class="form-control" rows="2" required>{{ $sustainability->reporting_methodology }}</textarea>
                        </div>

                        <!-- Evaluation Methodology Section -->
                        <div class="mb-3">
                            <h5>Explain the Methodology of Evaluation:</h5>
                            <textarea name="evaluation_methodology" class="form-control" rows="2" required>{{ $sustainability->evaluation_methodology }}</textarea>
                        </div>
                    @endforeach
                @else
                    <p>No sustainability information available for this project.</p>
                @endif
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
                                    <input type="number" name="phases[{{ $phaseIndex }}][amount_forwarded]" class="form-control" value="{{ $budgets->first()->amount_forwarded ?? '' }}" oninput="calculateBudgetTotals(this.closest('.phase-card'))">
                                </div>
                            @endif
                            <div class="mb-3">
                                <label for="phases[{{ $phaseIndex }}][amount_sanctioned]" class="form-label">Amount Sanctioned in Phase {{ $phaseIndex }}: Rs.</label>
                                <input type="number" name="phases[{{ $phaseIndex }}][amount_sanctioned]" class="form-control" value="{{ $budgets->first()->amount_sanctioned ?? '' }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="phases[{{ $phaseIndex }}][opening_balance]" class="form-label">Opening balance in Phase {{ $phaseIndex }}: Rs.</label>
                                <input type="number" name="phases[{{ $phaseIndex }}][opening_balance]" class="form-control" value="{{ $budgets->first()->opening_balance ?? '' }}" readonly>
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
                <button  id="addPhaseButton" type="button" class="mt-3 btn btn-primary" onclick="addPhase()">Add Phase</button>
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

@include('projects.partials.scripts-edit')

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
