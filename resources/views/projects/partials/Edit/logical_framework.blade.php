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
            <textarea name="objectives[{{ $objectiveIndex }}][objective]" class="mb-3 form-control objective-description logical-textarea" rows="2">{{ $objective->objective }}</textarea>

            <div class="results-container">
                <h6>Results / Outcomes</h6>
                @if($objective->results->isEmpty())
                    <div class="mb-3 result-section">
                        <div class="result-header d-flex justify-content-between align-items-center">
                            <h6>Result 1</h6>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                        </div>
                        <textarea name="objectives[{{ $objectiveIndex }}][results][0][result]" class="mb-3 form-control result-outcome logical-textarea" rows="2" placeholder="Enter Result"></textarea>
                    </div>
                @else
                    @foreach($objective->results as $resultIndex => $result)
                    <div class="mb-3 result-section">
                        <div class="result-header d-flex justify-content-between align-items-center">
                            <h6>Result {{ $resultIndex + 1 }}</h6>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                        </div>
                        <textarea name="objectives[{{ $objectiveIndex }}][results][{{ $resultIndex }}][result]" class="mb-3 form-control result-outcome logical-textarea" rows="2">{{ $result->result }}</textarea>
                    </div>
                    @endforeach
                @endif
                <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>
            </div>

            <div class="risks-container">
                <h6>Risks</h6>
                @if($objective->risks->isEmpty())
                    <div class="mb-3 risk-section">
                        <div class="risk-header d-flex justify-content-between align-items-center">
                            <h6>Risk 1</h6>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                        </div>
                        <textarea name="objectives[{{ $objectiveIndex }}][risks][0][risk]" class="mb-3 form-control risk-description logical-textarea" rows="2" placeholder="Enter Risk"></textarea>
                    </div>
                @else
                    @foreach($objective->risks as $riskIndex => $risk)
                    <div class="mb-3 risk-section">
                        <div class="risk-header d-flex justify-content-between align-items-center">
                            <h6>Risk {{ $riskIndex + 1 }}</h6>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                        </div>
                        <textarea name="objectives[{{ $objectiveIndex }}][risks][{{ $riskIndex }}][risk]" class="mb-3 form-control risk-description logical-textarea" rows="2">{{ $risk->risk }}</textarea>
                    </div>
                    @endforeach
                @endif
                <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this)">Add Risk</button>
            </div>

            <div class="activities-container">
                <h6>Activities and Means of Verification</h6>
                <div class="table-responsive">
                    <table class="table table-bordered activities-table">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 3%;">No.</th>
                                <th scope="col" style="width: 44%;">Activities</th>
                                <th scope="col" style="width: 47%;">Means of Verification</th>
                                <th scope="col" style="width: 6%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($objective->activities->isEmpty())
                                <tr class="activity-row">
                                    <td style="text-align: center; vertical-align: middle;">1</td>
                                    <td class="table-cell-wrap">
                                    <textarea name="objectives[{{ $objectiveIndex }}][activities][0][activity]" class="form-control activity-description logical-textarea select-input" rows="2" placeholder="Enter Activity"></textarea>
                                </td>
                                <td class="table-cell-wrap">
                                    <textarea name="objectives[{{ $objectiveIndex }}][activities][0][verification]" class="form-control activity-verification logical-textarea select-input" rows="2" placeholder="Means of Verification"></textarea>
                                    </td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeActivity(this)">Remove</button></td>
                                </tr>
                            @else
                                @foreach($objective->activities as $activityIndex => $activity)
                                <tr class="activity-row">
                                    <td style="text-align: center; vertical-align: middle;">{{ $activityIndex + 1 }}</td>
                                    <td class="table-cell-wrap">
                                    <textarea name="objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][activity]" class="form-control activity-description logical-textarea select-input" rows="2">{{ $activity->activity }}</textarea>
                                </td>
                                <td class="table-cell-wrap">
                                    <textarea name="objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][verification]" class="form-control activity-verification logical-textarea select-input" rows="2">{{ $activity->verification }}</textarea>
                                    </td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeActivity(this)">Remove</button></td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
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
