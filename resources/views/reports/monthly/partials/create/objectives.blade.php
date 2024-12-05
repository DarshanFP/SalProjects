{{-- resources/views/reports/monthly/partials/create/objectives.blade.php --}}
<div id="objectives-container">
    @foreach($objectives as $index => $objective)
    <div class="mb-3 card objective" data-index="{{ $index }}">
        <div class="card-header">
            <h4>Activities and Intermediate Outcomes for Objective:</h4>
        </div>
        <div class="card-header d-flex justify-content-between align-items-center">
            Objective {{ $index + 1 }}
        </div>
        <div class="card-body">
            <!-- Objective Section -->
            <div class="row">
                <!-- Objective -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Objective</label>
                        <textarea name="objective[{{ $index }}]" class="form-control" rows="2" readonly>{{ old("objective.$index", $objective->objective) }}</textarea>
                        @error("objective.$index")
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <!-- Hidden Input for project_objective_id -->
                        <input type="hidden" name="project_objective_id[{{ $index }}]" value="{{ $objective->objective_id }}">
                    </div>
                </div>
                <!-- Expected Outcomes -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Expected Outcomes</label>
                        @foreach($objective->results as $resultIndex => $result)
                            <textarea name="expected_outcome[{{ $index }}][{{ $resultIndex }}]" class="form-control" rows="2" readonly>{{ old("expected_outcome.$index.$resultIndex", $result->result) }}</textarea>
                            @error("expected_outcome.$index.$resultIndex")
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Monthly Summary Section -->
            <h4>Monthly Summary</h4>
            <div class="monthly-summary-container" data-index="{{ $index }}">
                @foreach($objective->activities as $activityIndex => $activity)
                <div class="mb-3 card activity" data-activity-index="{{ $activityIndex }}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        Activity {{ $activityIndex + 1 }}
                        <!-- Remove Activity Button -->
                        <button type="button" class="btn btn-danger btn-sm remove-activity" onclick="removeActivity(this)">Remove</button>
                    </div>
                    <div class="card-body">
                        <!-- Activity and Timeframes -->
                        <div class="row">
                            <!-- Activity -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Activity</label>
                                    <textarea name="activity[{{ $index }}][{{ $activityIndex }}]" class="form-control" rows="2" readonly>{{ old("activity.$index.$activityIndex", $activity->activity) }}</textarea>
                                    @error("activity.$index.$activityIndex")
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <!-- Hidden Input for project_activity_id -->
                                    <input type="hidden" name="project_activity_id[{{ $index }}][{{ $activityIndex }}]" value="{{ $activity->activity_id }}">
                                </div>
                            </div>
                            <!-- Time Frame Months -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Scheduled Months</label>
                                    <div class="mt-2">
                                        @php
                                            $months = ['January', 'February', 'March', 'April', 'May', 'June',
                                                       'July', 'August', 'September', 'October', 'November', 'December'];
                                        @endphp
                                        @foreach($activity->timeframes as $timeframe)
                                            @if($timeframe->is_active)
                                                <span class="badge bg-primary">{{ $months[$timeframe->month - 1] }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Select Month -->
                        <div class="mb-3">
                            <label class="form-label">Reporting Month</label>
                            <select name="month[{{ $index }}][{{ $activityIndex }}]" class="form-control">
                                <option value="" disabled selected>Select Month</option>
                                @foreach($months as $monthKey => $monthName)
                                    <option value="{{ $monthKey + 1 }}"
                                        @if(old("month.$index.$activityIndex") == $monthKey + 1) selected @endif>
                                        {{ $monthName }}
                                    </option>
                                @endforeach
                            </select>
                            @error("month.$index.$activityIndex")
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Summary of Activities -->
                        <div class="mb-3">
                            <label class="form-label">Summary of Activities</label>
                            <textarea name="summary_activities[{{ $index }}][{{ $activityIndex }}][1]" class="form-control" rows="3">{{ old("summary_activities.$index.$activityIndex.1") }}</textarea>
                            @error("summary_activities.$index.$activityIndex.1")
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Qualitative & Quantitative Data -->
                        <div class="mb-3">
                            <label class="form-label">Qualitative & Quantitative Data</label>
                            <textarea name="qualitative_quantitative_data[{{ $index }}][{{ $activityIndex }}][1]" class="form-control" rows="3">{{ old("qualitative_quantitative_data.$index.$activityIndex.1") }}</textarea>
                            @error("qualitative_quantitative_data.$index.$activityIndex.1")
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Intermediate Outcomes -->
                        <div class="mb-3">
                            <label class="form-label">Intermediate Outcomes</label>
                            <textarea name="intermediate_outcomes[{{ $index }}][{{ $activityIndex }}][1]" class="form-control" rows="3">{{ old("intermediate_outcomes.$index.$activityIndex.1") }}</textarea>
                            @error("intermediate_outcomes.$index.$activityIndex.1")
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                @endforeach
                <!-- Button to Add Activity -->
                <div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addActivity({{ $index }})">Add Other Activity</button><br>
                </div>
            </div>

            <!-- Additional Questions -->
            <!-- What Did Not Happen -->
            <div class="mb-3">
                <label class="form-label">What Did Not Happen?</label>
                <textarea name="not_happened[{{ $index }}]" class="form-control" rows="3">{{ old("not_happened.$index") }}</textarea>
                @error("not_happened.$index")
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <!-- Explain Why Some Activities Could Not Be Undertaken -->
            <div class="mb-3">
                <label class="form-label">Explain Why Some Activities Could Not Be Undertaken</label>
                <textarea name="why_not_happened[{{ $index }}]" class="form-control" rows="3">{{ old("why_not_happened.$index") }}</textarea>
                @error("why_not_happened.$index")
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <!-- Have You Made Any Changes -->
            <div class="mb-3">
                <label class="form-label">Have You Made Any Changes in the Project Such as New Activities or Modified the Activities Contextually?</label>
                <div>
                    <input type="radio" name="changes[{{ $index }}]" value="yes" onclick="toggleWhyChanges(this, {{ $index }})" @if(old("changes.$index") == 'yes') checked @endif> Yes
                    <input type="radio" name="changes[{{ $index }}]" value="no" onclick="toggleWhyChanges(this, {{ $index }})" @if(old("changes.$index") == 'no') checked @endif> No
                </div>
                @error("changes.$index")
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <!-- If Yes, Explain Why the Changes Were Needed -->
            <div class="mb-3 @if(old("changes.$index") != 'yes') d-none @endif" id="why_changes_container_{{ $index }}">
                <label class="form-label">Explain Why the Changes Were Needed</label>
                <textarea name="why_changes[{{ $index }}]" class="form-control" rows="3">{{ old("why_changes.$index") }}</textarea>
                @error("why_changes.$index")
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <!-- What Are the Lessons Learnt -->
            <div class="mb-3">
                <label class="form-label">What Are the Lessons Learnt?</label>
                <textarea name="lessons_learnt[{{ $index }}]" class="form-control" rows="3">{{ old("lessons_learnt.$index") }}</textarea>
                @error("lessons_learnt.$index")
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <!-- What Will Be Done Differently Because of the Learnings -->
            <div class="mb-3">
                <label class="form-label">What Will Be Done Differently Because of the Learnings?</label>
                <textarea name="todo_lessons_learnt[{{ $index }}]" class="form-control" rows="3">{{ old("todo_lessons_learnt.$index") }}</textarea>
                @error("todo_lessons_learnt.$index")
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>
    @endforeach
</div>

<script>
function toggleWhyChanges(radio, index) {
    const container = document.getElementById(`why_changes_container_${index}`);
    if (radio.value === 'yes') {
        container.classList.remove('d-none');
        container.querySelector('textarea').setAttribute('required', 'required');
    } else {
        container.classList.add('d-none');
        container.querySelector('textarea').removeAttribute('required');
    }
}

function addActivity(objectiveIndex) {
    const monthlySummaryContainer = document.querySelector(`.monthly-summary-container[data-index="${objectiveIndex}"]`);
    const activityIndex = monthlySummaryContainer.querySelectorAll('.activity').length;
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    const newActivityHtml = `
        <div class="mb-3 card activity" data-activity-index="${activityIndex}">
            <div class="card-header d-flex justify-content-between align-items-center">
                Activity ${activityIndex + 1}
                <button type="button" class="btn btn-danger btn-sm remove-activity" onclick="removeActivity(this)">Remove</button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Activity</label>
                    <textarea name="activity[${objectiveIndex}][${activityIndex}]" class="form-control" rows="2"></textarea>
                    <input type="hidden" name="project_activity_id[${objectiveIndex}][${activityIndex}]" value="">
                </div>
                <div class="mb-3">
                    <label class="form-label">Reporting Month</label>
                    <select name="month[${objectiveIndex}][${activityIndex}]" class="form-control">
                        <option value="" disabled selected>Select Month</option>
                        ${months.map((month, index) => `<option value="${index + 1}">${month}</option>`).join('')}
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Summary of Activities</label>
                    <textarea name="summary_activities[${objectiveIndex}][${activityIndex}][1]" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Qualitative & Quantitative Data</label>
                    <textarea name="qualitative_quantitative_data[${objectiveIndex}][${activityIndex}][1]" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Intermediate Outcomes</label>
                    <textarea name="intermediate_outcomes[${objectiveIndex}][${activityIndex}][1]" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>
    `;
    monthlySummaryContainer.insertAdjacentHTML('beforeend', newActivityHtml);
}

function removeActivity(button) {
    const activity = button.closest('.activity');
    const container = activity.closest('.monthly-summary-container');
    activity.remove();

    const activities = container.querySelectorAll('.activity');
    activities.forEach((activity, index) => {
        activity.dataset.activityIndex = index;
        activity.querySelector('textarea[name^="activity"]').name = `activity[${container.dataset.index}][${index}]`;
        activity.querySelector('input[name^="project_activity_id"]').name = `project_activity_id[${container.dataset.index}][${index}]`;
        activity.querySelector('select[name^="month"]').name = `month[${container.dataset.index}][${index}]`;
        activity.querySelector('textarea[name^="summary_activities"]').name = `summary_activities[${container.dataset.index}][${index}][1]`;
        activity.querySelector('textarea[name^="qualitative_quantitative_data"]').name = `qualitative_quantitative_data[${container.dataset.index}][${index}][1]`;
        activity.querySelector('textarea[name^="intermediate_outcomes"]').name = `intermediate_outcomes[${container.dataset.index}][${index}][1]`;
    });
}
</script>
