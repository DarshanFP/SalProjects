<!-- resources/views/projects/Oldprojects/edit.blade.php -->
@extends('executor.dashboard')

{{-- @extends('layouts.app') --}}

@section('content')
<div class="container">
    <h2>Edit Project</h2>
    <form action="{{ route('projects.update', $project->project_id) }}" method="POST" enctype="multipart/form-data">

        @csrf
        @method('PUT')

        <!-- Project Basic Information -->
        <div class="mb-4 card">
            <div class="card-header">
                <h4>1. Basic Information</h4>
            </div>
            <div class="card-body">
                <!-- Project Name -->
                <div class="mb-3">
                    <label for="project_title" class="form-label">Project Name:</label>
                    <input type="text" name="project_title" id="project_title" class="form-control" value="{{ $project->project_title }}" required>
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

                <!-- Project Goal -->
                <div class="mb-3">
                    <label for="goal" class="form-label">Goal of the Project:</label>
                    <textarea name="goal" id="goal" class="form-control" rows="3" required>{{ $project->goal }}</textarea>
                </div>
            </div>
        </div>

        <!-- Logical Framework Section -->
        <div class="mb-4 card">
            <div class="card-header">
                <h4>Logical Framework</h4>
            </div>
            <div class="card-body" id="objectives-container">
                @foreach($project->objectives as $objectiveIndex => $objective)
                <div class="p-3 mb-4 border rounded objective-card">
                    <h5 class="mb-3">Objective {{ $objectiveIndex + 1 }}</h5>
                    <textarea name="objectives[{{ $objectiveIndex }}][objective]" class="mb-3 form-control objective-description" rows="2" required>{{ $objective->objective }}</textarea>

                    <div class="results-container">
                        <h6>Results / Outcomes</h6>
                        @foreach($objective->results as $resultIndex => $result)
                        <div class="mb-3 result-section">
                            <textarea name="objectives[{{ $objectiveIndex }}][results][{{ $resultIndex }}][result]" class="mb-3 form-control result-outcome" rows="2" required>{{ $result->result }}</textarea>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                        </div>
                        @endforeach
                        <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>
                    </div>

                    <div class="risks-container">
                        <h6>Risks</h6>
                        @foreach($objective->risks as $riskIndex => $risk)
                            <div class="mb-3 risk-section">
                                <textarea name="objectives[{{ $objectiveIndex }}][risks][{{ $riskIndex }}][risk]" class="mb-3 form-control risk-description" rows="2" required>{{ $risk->risk }}</textarea>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                            </div>
                        @endforeach
                        <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this)">Add Risk</button>
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
                    @include('projects.partials.edit_timeframe', ['objectiveIndex' => $objectiveIndex, 'objective' => $objective])
                </div>
                @endforeach
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" onclick="addObjective()">Add Objective</button>
                    <button type="button" class="btn btn-danger" onclick="removeLastObjective()">Remove Last Objective</button>
                </div>
            </div>
        </div>

        <!-- Project Sustainability, Monitoring, and Methodologies -->
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
                                <h4>Phase {{ $phaseIndex + 1 }}</h4>
                            </div>
                            @if($phaseIndex > 0)
                                <div class="mb-3">
                                    <label for="phases[{{ $phaseIndex }}][amount_forwarded]" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
                                    <input type="number" name="phases[{{ $phaseIndex }}][amount_forwarded]" class="form-control" value="{{ $budgets->first()->amount_forwarded ?? '' }}" oninput="calculateBudgetTotals(this.closest('.phase-card'))">
                                </div>
                            @endif
                            <div class="mb-3">
                                <label for="phases[{{ $phaseIndex }}][amount_sanctioned]" class="form-label">Amount Sanctioned in Phase {{ $phaseIndex + 1 }}: Rs.</label>
                                <input type="number" name="phases[{{ $phaseIndex }}][amount_sanctioned]" class="form-control" value="{{ $budgets->first()->amount_sanctioned ?? '' }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="phases[{{ $phaseIndex }}][opening_balance]" class="form-label">Opening balance in Phase {{ $phaseIndex + 1 }}: Rs.</label>
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
                                            <td><input type="text" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][particular]" class="form-control" value="{{ $budget->particular }}" required></td>
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
                            @if($phaseIndex > 0)
                            <div>
                                <button type="button" class="mt-3 btn btn-danger" onclick="removePhase(this)">Remove Phase</button>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <button id="addPhaseButton" type="button" class="mt-3 btn btn-primary">Add Phase</button>
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
