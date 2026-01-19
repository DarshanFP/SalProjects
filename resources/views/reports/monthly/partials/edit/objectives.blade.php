{{-- resources/views/reports/monthly/partials/edit/objectives.blade.php --}}
<div id="objectives-container">
    @foreach ($report->objectives as $index => $objective)
        <div class="mb-3 card objective objective-card" data-index="{{ $index }}">
            <div class="card-header objective-card-header" onclick="toggleObjectiveCard(this)">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-2">{{ $index + 1 }}</span>
                            <h4 class="mb-0 ms-2">Objective {{ $index + 1 }}</h4>
                        </div>
                        <div class="mt-1">
                            <small class="text-muted">Activities and Intermediate Outcomes</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-chevron-down toggle-icon me-2"></i>
                    </div>
                </div>
            </div>
            <div class="card-body objective-form" style="display: none;">
                <!-- Objective Section -->
                <div class="row">
                    <!-- Objective -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Objective</label>
                            <textarea name="objective[{{ $index }}]" class="form-control readonly-input auto-resize-textarea" rows="2" readonly>{{ old("objective.$index", $objective->objective) }}</textarea>
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
                                    <textarea name="expected_outcome[{{ $index }}][{{ $resultIndex }}]" class="mb-2 form-control auto-resize-textarea" rows="2">{{ old("expected_outcome.$index.$resultIndex", $expectedOutcome) }}</textarea>
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
                    @php
                        $months = ['January', 'February', 'March', 'April', 'May', 'June',
                                   'July', 'August', 'September', 'October', 'November', 'December'];
                        $scheduledMonths = [];
                        if($activity->timeframes && $activity->timeframes->isNotEmpty()) {
                            foreach($activity->timeframes as $timeframe) {
                                if($timeframe->is_active) {
                                    $scheduledMonths[] = $months[$timeframe->month - 1];
                                }
                            }
                        }
                        $scheduledMonthsStr = !empty($scheduledMonths) ? implode(', ', $scheduledMonths) : 'Not scheduled';
                    @endphp
                        <div class="mb-3 card activity activity-card"
                             data-objective-index="{{ $index }}"
                             data-activity-index="{{ $activityIndex }}">
                            <div class="card-header activity-card-header" onclick="toggleActivityCard(this)">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="badge bg-success me-2">{{ $activityIndex + 1 }}</span>
                                            <strong>{{ $activity->activity }}</strong>
                                        </div>
                                        <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                            <span class="badge bg-info">
                                                <i class="fas fa-calendar"></i> Scheduled: {{ $scheduledMonthsStr }}
                                            </span>
                                            <span class="badge bg-warning activity-status"
                                                  id="status-{{ $index }}-{{ $activityIndex }}">
                                                Empty
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-chevron-down toggle-icon"></i>
                                        <button type="button"
                                                class="btn btn-danger btn-sm remove-activity"
                                                onclick="event.stopPropagation(); removeActivity(this)">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body activity-form" style="display: none;">
                                <!-- Activity and Timeframes -->
                                <div class="row">
                                    <!-- Activity -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Activity</label>
                                            <textarea name="activity[{{ $index }}][{{ $activityIndex }}]" class="form-control activity-field auto-resize-textarea" rows="2" readonly>{{ old("activity.$index.$activityIndex", $activity->activity) }}</textarea>
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
                                    <select name="month[{{ $index }}][{{ $activityIndex }}]" class="form-control activity-field">
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
                                    <textarea name="summary_activities[{{ $index }}][{{ $activityIndex }}][1]" class="form-control activity-field auto-resize-textarea" rows="3">{{ old("summary_activities.$index.$activityIndex.1", $activity->summary_activities) }}</textarea>
                                    @error("summary_activities.$index.$activityIndex.1")
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!-- Qualitative & Quantitative Data -->
                                <div class="mb-3">
                                    <label class="form-label">Qualitative & Quantitative Data</label>
                                    <textarea name="qualitative_quantitative_data[{{ $index }}][{{ $activityIndex }}][1]" class="form-control activity-field auto-resize-textarea" rows="3">{{ old("qualitative_quantitative_data.$index.$activityIndex.1", $activity->qualitative_quantitative_data) }}</textarea>
                                    @error("qualitative_quantitative_data.$index.$activityIndex.1")
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!-- Intermediate Outcomes -->
                                <div class="mb-3">
                                    <label class="form-label">Intermediate Outcomes</label>
                                    <textarea name="intermediate_outcomes[{{ $index }}][{{ $activityIndex }}][1]" class="form-control activity-field auto-resize-textarea" rows="3">{{ old("intermediate_outcomes.$index.$activityIndex.1", $activity->intermediate_outcomes) }}</textarea>
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
                    <textarea name="not_happened[{{ $index }}]" class="form-control auto-resize-textarea" rows="3">{{ old("not_happened.$index", $objective->not_happened) }}</textarea>
                    @error("not_happened.$index")
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <!-- Explain Why Some Activities Could Not Be Undertaken -->
                <div class="mb-3">
                    <label class="form-label">Explain Why Some Activities Could Not Be Undertaken</label>
                    <textarea name="why_not_happened[{{ $index }}]" class="form-control auto-resize-textarea" rows="3">{{ old("why_not_happened.$index", $objective->why_not_happened) }}</textarea>
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
                    <textarea name="why_changes[{{ $index }}]" class="form-control auto-resize-textarea" rows="3">{{ old("why_changes.$index", $objective->why_changes) }}</textarea>
                    @error("why_changes.$index")
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <!-- What Are the Lessons Learnt -->
                <div class="mb-3">
                    <label class="form-label">What Are the Lessons Learnt?</label>
                    <textarea name="lessons_learnt[{{ $index }}]" class="form-control auto-resize-textarea" rows="3">{{ old("lessons_learnt.$index", $objective->lessons_learnt) }}</textarea>
                    @error("lessons_learnt.$index")
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <!-- What Will Be Done Differently Because of the Learnings -->
                <div class="mb-3">
                    <label class="form-label">What Will Be Done Differently Because of the Learnings?</label>
                    <textarea name="todo_lessons_learnt[{{ $index }}]" class="form-control auto-resize-textarea" rows="3">{{ old("todo_lessons_learnt.$index", $objective->todo_lessons_learnt) }}</textarea>
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
        newField.className = 'mb-2 form-control auto-resize-textarea';
        newField.rows = 2;

        container.appendChild(newField);

        // Initialize auto-resize for new expected outcome textarea using global function
        if (typeof initTextareaAutoResize === 'function') {
            initTextareaAutoResize(newField);
        }
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
            <div class="mb-3 card activity activity-card"
                 data-objective-index="${objectiveIndex}"
                 data-activity-index="${activityIndex}">
                <div class="card-header activity-card-header" onclick="toggleActivityCard(this)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge bg-success me-2">${activityIndex + 1}</span>
                                <strong>New Activity</strong>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                <span class="badge bg-info">
                                    <i class="fas fa-calendar"></i> Scheduled: Not scheduled
                                </span>
                                <span class="badge bg-warning activity-status"
                                      id="status-${objectiveIndex}-${activityIndex}">
                                    Empty
                                </span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-chevron-down toggle-icon"></i>
                            <button type="button"
                                    class="btn btn-danger btn-sm remove-activity"
                                    onclick="event.stopPropagation(); removeActivity(this)">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body activity-form" style="display: none;">
                    <!-- Activity and Timeframes -->
                    <div class="row">
                        <!-- Activity -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Activity</label>
                                <textarea name="activity[${objectiveIndex}][${activityIndex}]" class="form-control activity-field auto-resize-textarea" rows="2"></textarea>
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
                        <select name="month[${objectiveIndex}][${activityIndex}]" class="form-control activity-field">
                            <option value="" disabled selected>Select Month</option>
                            ${months.map((month, index) => `<option value="${index + 1}">${month}</option>`).join('')}
                        </select>
                    </div>
                    <!-- Summary of Activities -->
                    <div class="mb-3">
                        <label class="form-label">Summary of Activities</label>
                        <textarea name="summary_activities[${objectiveIndex}][${activityIndex}][1]" class="form-control activity-field auto-resize-textarea" rows="3"></textarea>
                    </div>
                    <!-- Qualitative & Quantitative Data -->
                    <div class="mb-3">
                        <label class="form-label">Qualitative & Quantitative Data</label>
                        <textarea name="qualitative_quantitative_data[${objectiveIndex}][${activityIndex}][1]" class="form-control activity-field auto-resize-textarea" rows="3"></textarea>
                    </div>
                    <!-- Intermediate Outcomes -->
                    <div class="mb-3">
                        <label class="form-label">Intermediate Outcomes</label>
                        <textarea name="intermediate_outcomes[${objectiveIndex}][${activityIndex}][1]" class="form-control activity-field auto-resize-textarea" rows="3"></textarea>
                    </div>
                </div>
            </div>
        `;

        monthlySummaryContainer.insertAdjacentHTML('beforeend', newActivityHtml);

        // Initialize auto-resize for new activity textareas using global function
        const newActivity = monthlySummaryContainer.lastElementChild;
        if (newActivity && typeof initDynamicTextarea === 'function') {
            initDynamicTextarea(newActivity);
        }

        reindexActivities(objectiveIndex);

        // Set up event listeners for the new activity
        const newActivityCard = monthlySummaryContainer.querySelector(`.activity-card[data-activity-index="${activityIndex}"]`);
        if (newActivityCard) {
            const formFields = newActivityCard.querySelectorAll('.activity-field');
            formFields.forEach((field) => {
                field.addEventListener('input', function() {
                    updateActivityStatus(objectiveIndex, activityIndex);
                });
                field.addEventListener('change', function() {
                    updateActivityStatus(objectiveIndex, activityIndex);
                });
            });
            // Initialize status for new activity
            updateActivityStatus(objectiveIndex, activityIndex);
        }
    }

    // Function to remove an activity
    function removeActivity(button) {
        const activity = button.closest('.activity');
        const container = activity.closest('.monthly-summary-container');
        const objectiveIndex = parseInt(container.dataset.index);
        activity.remove();
        reindexActivities(objectiveIndex);
    }

    // Reindex activities function
    /**
     * Reindexes all activities for a specific objective after add/remove operations
     * Updates index badges, data-activity-index attributes, and form field names/IDs
     * Ensures sequential numbering (1, 2, 3, ...) for all activities within the objective
     *
     * @param {number} objectiveIndex - The index of the objective containing the activities
     * @returns {void}
     */
    function reindexActivities(objectiveIndex) {
        const container = document.querySelector(`.monthly-summary-container[data-index="${objectiveIndex}"]`);
        if (!container) return;

        const activities = container.querySelectorAll('.activity');
        activities.forEach((activity, index) => {
            // Update data-activity-index
            activity.dataset.activityIndex = index;

            // Update badge in card header (for card-based UI)
            const headerBadge = activity.querySelector('.activity-card-header .badge.bg-success');
            if (headerBadge) {
                headerBadge.textContent = index + 1;
            }

            // Update status badge ID
            const statusBadge = activity.querySelector('.activity-status');
            if (statusBadge) {
                statusBadge.id = `status-${objectiveIndex}-${index}`;
            }

            // Update all name attributes
            const objectiveIndexValue = container.dataset.index;

            const activityTextarea = activity.querySelector('textarea[name^="activity"]');
            if (activityTextarea) {
                activityTextarea.name = `activity[${objectiveIndexValue}][${index}]`;
            }

            const projectActivityIdInput = activity.querySelector('input[name^="project_activity_id"]');
            if (projectActivityIdInput) {
                projectActivityIdInput.name = `project_activity_id[${objectiveIndexValue}][${index}]`;
            }

            const monthSelect = activity.querySelector('select[name^="month"]');
            if (monthSelect) {
                monthSelect.name = `month[${objectiveIndexValue}][${index}]`;
            }

            const summaryTextarea = activity.querySelector('textarea[name^="summary_activities"]');
            if (summaryTextarea) {
                summaryTextarea.name = `summary_activities[${objectiveIndexValue}][${index}][1]`;
            }

            const qualitativeTextarea = activity.querySelector('textarea[name^="qualitative_quantitative_data"]');
            if (qualitativeTextarea) {
                qualitativeTextarea.name = `qualitative_quantitative_data[${objectiveIndexValue}][${index}][1]`;
            }

            const outcomesTextarea = activity.querySelector('textarea[name^="intermediate_outcomes"]');
            if (outcomesTextarea) {
                outcomesTextarea.name = `intermediate_outcomes[${objectiveIndexValue}][${index}][1]`;
            }

            // Re-initialize textarea auto-resize after reindexing
            if (typeof autoResizeTextarea === 'function') {
                if (activityTextarea) autoResizeTextarea(activityTextarea);
                if (summaryTextarea) autoResizeTextarea(summaryTextarea);
                if (qualitativeTextarea) autoResizeTextarea(qualitativeTextarea);
                if (outcomesTextarea) autoResizeTextarea(outcomesTextarea);
            }

            // Update activity status after reindexing
            updateActivityStatus(objectiveIndexValue, index);
        });
    }

    // Objective Card Toggle Function
    function toggleObjectiveCard(header) {
        const card = header.closest('.objective-card');
        const form = card.querySelector('.objective-form');
        const icon = header.querySelector('.toggle-icon');

        if (form.style.display === 'none' || !form.style.display) {
            form.style.display = 'block';
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            card.classList.add('active');
        } else {
            form.style.display = 'none';
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
            card.classList.remove('active');
        }
    }

    // Activity Card Toggle Function
    function toggleActivityCard(header) {
        const card = header.closest('.activity-card');
        const form = card.querySelector('.activity-form');
        const icon = header.querySelector('.toggle-icon');

        if (form.style.display === 'none' || !form.style.display) {
            form.style.display = 'block';
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            card.classList.add('active');
        } else {
            form.style.display = 'none';
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
            card.classList.remove('active');
        }
    }

    // Update Activity Status Function
    function updateActivityStatus(objectiveIndex, activityIndex) {
        const card = document.querySelector(
            `.activity-card[data-objective-index="${objectiveIndex}"][data-activity-index="${activityIndex}"]`
        );
        if (!card) return;

        const form = card.querySelector('.activity-form');
        const statusBadge = card.querySelector('.activity-status');
        if (!form || !statusBadge) return;

        // Check if form is filled
        const monthSelect = form.querySelector('select[name^="month"]');
        const summaryTextarea = form.querySelector('textarea[name^="summary_activities"]');
        const dataTextarea = form.querySelector('textarea[name^="qualitative_quantitative_data"]');
        const outcomesTextarea = form.querySelector('textarea[name^="intermediate_outcomes"]');

        const month = monthSelect ? monthSelect.value : '';
        const summary = summaryTextarea ? summaryTextarea.value.trim() : '';
        const data = dataTextarea ? dataTextarea.value.trim() : '';
        const outcomes = outcomesTextarea ? outcomesTextarea.value.trim() : '';

        // Update status badge
        if (month && summary && data && outcomes) {
            statusBadge.textContent = 'Complete';
            statusBadge.classList.remove('bg-warning', 'bg-info');
            statusBadge.classList.add('bg-success');
        } else if (month || summary || data || outcomes) {
            statusBadge.textContent = 'In Progress';
            statusBadge.classList.remove('bg-warning', 'bg-success');
            statusBadge.classList.add('bg-info');
        } else {
            statusBadge.textContent = 'Empty';
            statusBadge.classList.remove('bg-success', 'bg-info');
            statusBadge.classList.add('bg-warning');
        }
    }

    // Initialize on page load - reindex all activities for all objectives and set up event listeners
    document.addEventListener('DOMContentLoaded', function() {
        const objectives = document.querySelectorAll('.objective');
        objectives.forEach((objective) => {
            const objectiveIndex = parseInt(objective.dataset.index);
            reindexActivities(objectiveIndex);

            // Set up event listeners for activity form fields
            const activities = objective.querySelectorAll('.activity-card');
            activities.forEach((activity) => {
                const activityIndex = parseInt(activity.dataset.activityIndex);
                const formFields = activity.querySelectorAll('.activity-field');
                formFields.forEach((field) => {
                    field.addEventListener('input', function() {
                        updateActivityStatus(objectiveIndex, activityIndex);
                    });
                    field.addEventListener('change', function() {
                        updateActivityStatus(objectiveIndex, activityIndex);
                    });
                });

                // Initialize status for existing activities
                updateActivityStatus(objectiveIndex, activityIndex);
            });
        });
    });

</script>

<style>
/* Objective Card Styling - Dark Theme */
.objective-card {
    margin-bottom: 1.5rem;
    border: 1px solid #172340;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
    background-color: #0c1427;
    color: #d0d6e1;
}

.objective-card:hover {
    box-shadow: 0 2px 8px rgba(101, 113, 255, 0.15);
    border-color: #212a3a;
}

.objective-card.active {
    border-color: #6571ff;
    box-shadow: 0 4px 12px rgba(101, 113, 255, 0.25);
}

.objective-card .objective-card-header {
    cursor: pointer;
    background-color: #0f1629;
    padding: 1.25rem;
    user-select: none;
    transition: background-color 0.2s ease;
    color: #d0d6e1;
    border-bottom: 1px solid #172340;
}

.objective-card .objective-card-header:hover {
    background-color: #131b2f;
}

.objective-card.active .objective-card-header {
    background-color: #1a2342;
    border-bottom: 2px solid #6571ff;
}

.objective-card .objective-card-header h4,
.objective-card .objective-card-header h5,
.objective-card .objective-card-header h6,
.objective-card .objective-card-header span:not(.badge),
.objective-card .objective-card-header div:not(.badge) {
    color: #d0d6e1;
}

.objective-card .objective-card-header .text-muted {
    color: #7987a1 !important;
}

.objective-form {
    padding: 1.5rem;
    background-color: #0c1427;
    border-top: 1px solid #172340;
    color: #d0d6e1;
}

.objective-form label,
.objective-form h4 {
    color: #d0d6e1;
}

.objective-form input,
.objective-form textarea,
.objective-form select {
    background-color: #070d19 !important;
    border-color: #172340 !important;
    color: #d0d6e1 !important;
}

.objective-form input:focus,
.objective-form textarea:focus,
.objective-form select:focus {
    background-color: #070d19 !important;
    border-color: #6571ff !important;
    color: #d0d6e1 !important;
    box-shadow: 0 0 0 0.2rem rgba(101, 113, 255, 0.25);
}

.objective-form input[readonly],
.objective-form textarea[readonly] {
    background-color: #0f1629 !important;
    border-color: #212a3a !important;
    color: #b8c3d9 !important;
    cursor: not-allowed;
}

.objective-form .text-danger {
    color: #ff3366 !important;
}

/* Activity Card Styling - Dark Theme */
.activity-card {
    margin-bottom: 1rem;
    border: 1px solid #172340;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
    background-color: #0c1427;
    color: #d0d6e1;
}

.activity-card:hover {
    box-shadow: 0 2px 8px rgba(101, 113, 255, 0.15);
    border-color: #212a3a;
}

.activity-card.active {
    border-color: #6571ff;
    box-shadow: 0 4px 12px rgba(101, 113, 255, 0.25);
}

.activity-card .activity-card-header {
    cursor: pointer;
    background-color: #0f1629;
    padding: 1rem;
    user-select: none;
    transition: background-color 0.2s ease;
    color: #d0d6e1;
    border-bottom: 1px solid #172340;
}

.activity-card .activity-card-header:hover {
    background-color: #131b2f;
}

.activity-card.active .activity-card-header {
    background-color: #1a2342;
    border-bottom: 2px solid #6571ff;
}

.activity-card .activity-card-header h5,
.activity-card .activity-card-header h6,
.activity-card .activity-card-header span:not(.badge),
.activity-card .activity-card-header div:not(.badge) {
    color: #d0d6e1;
}

.activity-form {
    padding: 1rem;
    background-color: #0c1427;
    border-top: 1px solid #172340;
    color: #d0d6e1;
}

.activity-form label {
    color: #d0d6e1;
}

.activity-form input,
.activity-form textarea,
.activity-form select {
    background-color: #070d19 !important;
    border-color: #172340 !important;
    color: #d0d6e1 !important;
}

.activity-form input:focus,
.activity-form textarea:focus,
.activity-form select:focus {
    background-color: #070d19 !important;
    border-color: #6571ff !important;
    color: #d0d6e1 !important;
    box-shadow: 0 0 0 0.2rem rgba(101, 113, 255, 0.25);
}

.activity-form input[readonly],
.activity-form textarea[readonly] {
    background-color: #0f1629 !important;
    border-color: #212a3a !important;
    color: #b8c3d9 !important;
    cursor: not-allowed;
}

.activity-form .text-danger {
    color: #ff3366 !important;
}

.toggle-icon {
    transition: transform 0.3s ease;
    color: #7987a1;
}

.objective-card:hover .toggle-icon,
.activity-card:hover .toggle-icon {
    color: #d0d6e1;
}

.objective-card.active .toggle-icon,
.activity-card.active .toggle-icon {
    transform: rotate(180deg);
    color: #6571ff;
}

.activity-status {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
}

/* Dark theme badge adjustments */
.objective-card .badge.bg-primary,
.activity-card .badge.bg-primary {
    background-color: #6571ff !important;
    color: #fff;
}

.objective-card .badge.bg-success,
.activity-card .badge.bg-success {
    background-color: #05a34a !important;
    color: #fff;
}

.objective-card .badge.bg-info,
.activity-card .badge.bg-info {
    background-color: #66d1d1 !important;
    color: #fff;
}

.objective-card .badge.bg-warning,
.activity-card .badge.bg-warning {
    background-color: #fbbc06 !important;
    color: #000;
}

.objective-card .badge.bg-danger,
.activity-card .badge.bg-danger {
    background-color: #ff3366 !important;
    color: #fff;
}

.activity-card-header .gap-2 {
    gap: 0.5rem;
}

/* Ensure remove button doesn't trigger card toggle */
.remove-activity {
    z-index: 10;
}

/* Dark theme button adjustments */
.remove-activity.btn-danger {
    background-color: #ff3366;
    border-color: #ff3366;
    color: #fff;
}

.remove-activity.btn-danger:hover {
    background-color: #ff1a52;
    border-color: #ff1a52;
    color: #fff;
}

/* "Add Other Activity" button styling */
.monthly-summary-container .btn-primary {
    background-color: #6571ff;
    border-color: #6571ff;
    color: #fff;
}

.monthly-summary-container .btn-primary:hover {
    background-color: #4d5bff;
    border-color: #4d5bff;
    color: #fff;
}

/* Activity card text styling */
.activity-card .activity-name {
    color: #d0d6e1;
    font-weight: 500;
}
</style>
