{{-- resources/views/reports/monthly/partials/edit/objectives.blade.php --}}
<div id="objectives-container">
    @foreach ($report->objectives as $index => $objective)
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
                            <textarea name="objective[{{ $index }}]" class="form-control readonly-input" rows="2" readonly>{{ old("objective.$index", $objective->objective) }}</textarea>
                            @error("objective.$index")
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <!-- Hidden Input for project_objective_id -->
                            <input type="hidden" name="project_objective_id[{{ $index }}]" value="{{ $objective->project_objective_id }}">
                        </div>
                    </div>
                    <!-- Expected Outcomes -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="expected_outcome[{{ $index }}][]" class="form-label">Expected Outcomes</label>
                            <div class="expected-outcomes-container">
                                @foreach ($objective->expected_outcome as $resultIndex => $expectedOutcome)
                                    <textarea name="expected_outcome[{{ $index }}][{{ $resultIndex }}]" class="mb-2 form-control" rows="2">{{ old("expected_outcome.$index.$resultIndex", $expectedOutcome) }}</textarea>
                                    @error("expected_outcome.$index.$resultIndex")
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Summary Section -->
                <h4>Monthly Summary</h4>
                <div class="monthly-summary-container" data-index="{{ $index }}">
                    @foreach ($objective->activities as $activityIndex => $activity)
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
                                            <input type="hidden" name="project_activity_id[{{ $index }}][{{ $activityIndex }}]" value="{{ $activity->project_activity_id }}">
                                        </div>
                                    </div>
                                    <!-- Time Frame Months -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Scheduled Months</label>

                                            <div class="mt-2">
                                                @if($activity->timeframes && $activity->timeframes->isNotEmpty())
                                                    @foreach($activity->timeframes as $timeframe)
                                                        @if($timeframe->is_active)
                                                            <span class="badge bg-primary">{{ $months[$timeframe->month - 1] }}</span>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <p>No scheduled months available.</p>
                                                @endif


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
                                                @if(old("month.$index.$activityIndex", $activity->month) == $monthKey + 1) selected @endif>
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
                                    <textarea name="summary_activities[{{ $index }}][{{ $activityIndex }}][1]" class="form-control" rows="3">{{ old("summary_activities.$index.$activityIndex.1", $activity->summary_activities) }}</textarea>
                                    @error("summary_activities.$index.$activityIndex.1")
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!-- Qualitative & Quantitative Data -->
                                <div class="mb-3">
                                    <label class="form-label">Qualitative & Quantitative Data</label>
                                    <textarea name="qualitative_quantitative_data[{{ $index }}][{{ $activityIndex }}][1]" class="form-control" rows="3">{{ old("qualitative_quantitative_data.$index.$activityIndex.1", $activity->qualitative_quantitative_data) }}</textarea>
                                    @error("qualitative_quantitative_data.$index.$activityIndex.1")
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!-- Intermediate Outcomes -->
                                <div class="mb-3">
                                    <label class="form-label">Intermediate Outcomes</label>
                                    <textarea name="intermediate_outcomes[{{ $index }}][{{ $activityIndex }}][1]" class="form-control" rows="3">{{ old("intermediate_outcomes.$index.$activityIndex.1", $activity->intermediate_outcomes) }}</textarea>
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
                    <textarea name="not_happened[{{ $index }}]" class="form-control" rows="3">{{ old("not_happened.$index", $objective->not_happened) }}</textarea>
                    @error("not_happened.$index")
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <!-- Explain Why Some Activities Could Not Be Undertaken -->
                <div class="mb-3">
                    <label class="form-label">Explain Why Some Activities Could Not Be Undertaken</label>
                    <textarea name="why_not_happened[{{ $index }}]" class="form-control" rows="3">{{ old("why_not_happened.$index", $objective->why_not_happened) }}</textarea>
                    @error("why_not_happened.$index")
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <!-- Have You Made Any Changes -->
                <div class="mb-3">
                    <label class="form-label">Have You Made Any Changes in the Project Such as New Activities or Modified the Activities Contextually?</label>
                    <div>
                        <input type="radio" name="changes[{{ $index }}]" value="yes" onclick="toggleWhyChanges(this, {{ $index }})" @if(old("changes.$index", $objective->changes) == 1) checked @endif> Yes
                        <input type="radio" name="changes[{{ $index }}]" value="no" onclick="toggleWhyChanges(this, {{ $index }})" @if(old("changes.$index", $objective->changes) == 0) checked @endif> No
                    </div>
                    @error("changes.$index")
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <!-- If Yes, Explain Why the Changes Were Needed -->
                <div class="mb-3 @if(old("changes.$index", $objective->changes) != 1) d-none @endif" id="why_changes_container_{{ $index }}">
                    <label class="form-label">Explain Why the Changes Were Needed</label>
                    <textarea name="why_changes[{{ $index }}]" class="form-control" rows="3">{{ old("why_changes.$index", $objective->why_changes) }}</textarea>
                    @error("why_changes.$index")
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <!-- What Are the Lessons Learnt -->
                <div class="mb-3">
                    <label class="form-label">What Are the Lessons Learnt?</label>
                    <textarea name="lessons_learnt[{{ $index }}]" class="form-control" rows="3">{{ old("lessons_learnt.$index", $objective->lessons_learnt) }}</textarea>
                    @error("lessons_learnt.$index")
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <!-- What Will Be Done Differently Because of the Learnings -->
                <div class="mb-3">
                    <label class="form-label">What Will Be Done Differently Because of the Learnings?</label>
                    <textarea name="todo_lessons_learnt[{{ $index }}]" class="form-control" rows="3">{{ old("todo_lessons_learnt.$index", $objective->todo_lessons_learnt) }}</textarea>
                    @error("todo_lessons_learnt.$index")
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>
    @endforeach
</div>

<script>

    // Function to add a new expected outcome
    function addExpectedOutcome(objectiveIndex) {
        const container = document.querySelector(`.objective[data-index="${objectiveIndex}"] .expected-outcomes-container`);
        const newIndex = container.querySelectorAll('textarea').length;

        const newField = document.createElement('textarea');
        newField.name = `expected_outcome[${objectiveIndex}][${newIndex}]`;
        newField.className = 'mb-2 form-control';
        newField.rows = 2;

        container.appendChild(newField);
    }
    // Function to toggle the "Explain Why the Changes Were Needed" field
    function toggleWhyChanges(radio, index) {
        const container = document.getElementById(`why_changes_container_${index}`);
        if (radio.value === 'yes') {
            container.classList.remove('d-none');
        } else {
            container.classList.add('d-none');
        }
    }

    // Function to add a new activity under a specific objective
    function addActivity(objectiveIndex) {
        const monthlySummaryContainer = document.querySelector(`.monthly-summary-container[data-index="${objectiveIndex}"]`);
        const activityIndex = monthlySummaryContainer.querySelectorAll('.activity').length;
        const months = ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'];

        const newActivityHtml = `
            <div class="mb-3 card activity" data-activity-index="${activityIndex}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Activity ${activityIndex + 1}
                    <button type="button" class="btn btn-danger btn-sm remove-activity" onclick="removeActivity(this)">Remove</button>
                </div>
                <div class="card-body">
                    <!-- Activity and Timeframes -->
                    <div class="row">
                        <!-- Activity -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Activity</label>
                                <textarea name="activity[${objectiveIndex}][${activityIndex}]" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <!-- Time Frame Months -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Scheduled Months</label>
                                <div class="mt-2">
                                    <!-- You can display scheduled months here if needed -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Select Month -->
                    <div class="mb-3">
                        <label class="form-label">Reporting Month</label>
                        <select name="month[${objectiveIndex}][${activityIndex}]" class="form-control">
                            <option value="" disabled selected>Select Month</option>
                            ${months.map((month, index) => `<option value="${index + 1}">${month}</option>`).join('')}
                        </select>
                    </div>
                    <!-- Summary of Activities -->
                    <div class="mb-3">
                        <label class="form-label">Summary of Activities</label>
                        <textarea name="summary_activities[${objectiveIndex}][${activityIndex}][1]" class="form-control" rows="3"></textarea>
                    </div>
                    <!-- Qualitative & Quantitative Data -->
                    <div class="mb-3">
                        <label class="form-label">Qualitative & Quantitative Data</label>
                        <textarea name="qualitative_quantitative_data[${objectiveIndex}][${activityIndex}][1]" class="form-control" rows="3"></textarea>
                    </div>
                    <!-- Intermediate Outcomes -->
                    <div class="mb-3">
                        <label class="form-label">Intermediate Outcomes</label>
                        <textarea name="intermediate_outcomes[${objectiveIndex}][${activityIndex}][1]" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        `;

        monthlySummaryContainer.insertAdjacentHTML('beforeend', newActivityHtml);
    }

    // Function to remove an activity
    function removeActivity(button) {
        const activity = button.closest('.activity');
        activity.remove();

        // Re-index activities for consistency
        const monthlySummaryContainer = activity.closest('.monthly-summary-container');
        const activities = monthlySummaryContainer.querySelectorAll('.activity');
        activities.forEach((activity, index) => {
            activity.dataset.activityIndex = index;

            // Update field names and labels dynamically
            activity.querySelectorAll('[name]').forEach(field => {
                const name = field.name;
                const updatedName = name.replace(/\[\d+\]\[\d+\]/, `[${monthlySummaryContainer.dataset.index}][${index}]`);
                field.name = updatedName;
            });

            const header = activity.querySelector('.card-header');
            if (header) {
                header.querySelector('div').textContent = `Activity ${index + 1}`;
            }
        });
    }

</script>
