<div class="mb-4 card">
    <div class="card-header">
        <h4>Logical Framework (Edit)</h4>
    </div>
    <div class="card-body" id="objectives-container">
        @foreach($project->objectives as $objectiveIndex => $objective)
        <div class="p-3 mb-4 rounded border objective-card">
            <div class="objective-header d-flex justify-content-between align-items-center">
                <h5 class="mb-3">Objective {{ $objectiveIndex + 1 }}</h5>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeObjective(this)">Remove Objective</button>
            </div>
            <textarea name="objectives[{{ $objectiveIndex }}][objective]" class="mb-3 form-control objective-description" rows="2" >{{ $objective->objective }}</textarea>

            <div class="results-container">
                <h6>Results / Outcomes</h6>
                @foreach($objective->results as $resultIndex => $result)
                <div class="mb-3 result-section">
                    <textarea name="objectives[{{ $objectiveIndex }}][results][{{ $resultIndex }}][result]" class="mb-3 form-control result-outcome" rows="2" >{{ $result->result }}</textarea>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                </div>
                @endforeach
                <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>
            </div>

            <div class="risks-container">
                <h6>Risks</h6>
                @foreach($objective->risks as $riskIndex => $risk)
                <div class="mb-3 risk-section">
                    <textarea name="objectives[{{ $objectiveIndex }}][risks][{{ $riskIndex }}][risk]" class="mb-3 form-control risk-description" rows="2" >{{ $risk->risk }}</textarea>
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
                                <textarea name="objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][activity]" class="form-control activity-description" rows="2" >{{ $activity->activity }}</textarea>
                            </td>
                            <td>
                                <textarea name="objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][verification]" class="form-control activity-verification" rows="2" >{{ $activity->verification }}</textarea>
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
    </div>
    <!-- Place the "Add Objective" button outside the objectives-container -->
    <div class="mb-3">
        <button type="button" id="addObjectiveButton" class="btn btn-primary" onclick="addObjective()">Add Objective</button>
    </div>
</div>
