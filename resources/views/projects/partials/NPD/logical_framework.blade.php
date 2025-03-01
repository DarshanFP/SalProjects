{{-- resources/views/projects/partials/NPD/logical_framework.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Solution Analysis: Logical Framework - Next Phase</h4>
    </div>
    <div class="card-body" id="objectives-container-npd">
        <!-- Objective Template -->
        @foreach ($predecessorObjectives as $index => $objective)
        <div class="mb-3 objective-card">
            <div class="objective-header d-flex justify-content-between align-items-center">
                <h5>Objective {{ $loop->iteration }}</h5>
            </div>
            <textarea name="objectives[{{ $index }}][objective]" class="mb-3 form-control objective-description" rows="2" placeholder="Enter Objective" style="background-color: #202ba3;">
                {{ $objective['objective'] }}
            </textarea>

            <div class="results-container">
                @foreach ($objective['results'] as $resultIndex => $result)
                <div class="mb-3 result-section">
                    <div class="result-header d-flex justify-content-between align-items-center">
                        <h6>Results / Outcome</h6>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                    </div>
                    <textarea name="objectives[{{ $index }}][results][{{ $resultIndex }}][result]" class="mb-3 form-control result-outcome" rows="2" placeholder="Enter Result" style="background-color: #202ba3;">
                        {{ $result['result'] }}
                    </textarea>
                </div>
                @endforeach
                <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>
            </div>

            <div class="risks-container">
                @foreach ($objective['risks'] as $riskIndex => $risk)
                <div class="mb-3 risk-section">
                    <div class="risk-header d-flex justify-content-between align-items-center">
                        <h6>Risks</h6>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                    </div>
                    <textarea name="objectives[{{ $index }}][risks][{{ $riskIndex }}][risk]" class="mb-3 form-control risk-description" rows="2" placeholder="Enter Risk" style="background-color: #202ba3;">
                        {{ $risk['risk'] }}
                    </textarea>
                </div>
                @endforeach
                <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this)">Add Risk</button>
            </div>
        </div>
        @endforeach

        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-primary" onclick="addObjective()">Add Objective</button>
            <button type="button" class="btn btn-danger" onclick="removeLastObjective()">Remove Last Objective</button>
        </div>
    </div>
</div>
