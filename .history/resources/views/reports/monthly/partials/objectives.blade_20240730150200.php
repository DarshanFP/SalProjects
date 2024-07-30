<!-- resources/views/reports/monthly/partials/objectives.blade.php -->

<div id="objectives-container">
    @foreach(old('objective', ['']) as $index => $value)
    <div class="mb-3 card objective" data-index="{{ $index }}">
        <div class="card-header">
            <h4>2. Activities and Intermediate Outcomes</h4>
        </div>
        <div class="card-header d-flex justify-content-between align-items-center">
            Objective {{ $index + 1 }}
            <button type="button" class="btn btn-danger btn-sm remove-objective" onclick="removeObjective(this)">Remove</button>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="objective[{{ $index }}]" class="form-label">Objective</label>
                <textarea name="objective[{{ $index }}]" class="form-control" rows="2" style="background-color: #202ba3;">{{ $value }}</textarea>
            </div>
            <div class="mb-3">
                <label for="expected_outcome[{{ $index }}]" class="form-label">Expected Outcome</label>
                <textarea name="expected_outcome[{{ $index }}]" class="form-control" rows="2" style="background-color: #202ba3;">{{ old('expected_outcome.'.$index) }}</textarea>
            </div>
            <h4>Monthly Summary</h4>
            <div class="monthly-summary-container" data-index="{{ $index }}">
                @foreach(old("month.$index", ['']) as $activityIndex => $month)
                <div class="mb-3 card activity" data-activity-index="{{ $activityIndex }}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="form-group">
                            <label for="month[{{ $index }}][{{ $activityIndex }}]" class="form-label">Month</label>
                            <select name="month[{{ $index }}][{{ $activityIndex }}]" class="form-control">
                                <option value="" disabled selected>Select Month</option>
                                @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $m)
                                    <option value="{{ $m }}" @if($m == old("month.$index.$activityIndex")) selected @endif>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="summary_activities[{{ $index }}][{{ $activityIndex }}][1]" class="form-label">Summary of Activities</label>
                            <textarea name="summary_activities[{{ $index }}][{{ $activityIndex }}][1]" class="form-control" rows="3" style="background-color: #202ba3;">{{ old("summary_activities.$index.$activityIndex.1") }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="qualitative_quantitative_data[{{ $index }}][{{ $activityIndex }}][1]" class="form-label">Qualitative & Quantitative Data</label>
                            <textarea name="qualitative_quantitative_data[{{ $index }}][{{ $activityIndex }}][1]" class="form-control" rows="3" style="background-color: #202ba3;">{{ old("qualitative_quantitative_data.$index.$activityIndex.1") }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="intermediate_outcomes[{{ $index }}][{{ $activityIndex }}][1]" class="form-label">Intermediate Outcomes</label>
                            <textarea name="intermediate_outcomes[{{ $index }}][{{ $activityIndex }}][1]" class="form-control" rows="3" style="background-color: #202ba3;">{{ old("intermediate_outcomes.$index.$activityIndex.1") }}</textarea>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addActivity({{ $index }})">Add Activity</button>
                    <button type="button" class="btn btn-danger btn-sm remove-activity" onclick="removeActivity(this)">Remove</button>
                </div>
                @endforeach
            </div>
            <div class="mb-3">
                <label for="not_happened[{{ $index }}]" class="form-label">What Did Not Happen?</label>
                <textarea name="not_happened[{{ $index }}]" class="form-control" rows="3" style="background-color: #202ba3;">{{ old("not_happened.$index") }}</textarea>
            </div>
            <div class="mb-3">
                <label for="why_not_happened[{{ $index }}]" class="form-label">Explain Why Some Activities Could Not Be Undertaken</label>
                <textarea name="why_not_happened[{{ $index }}]" class="form-control" rows="3" style="background-color: #202ba3;">{{ old("why_not_happened.$index") }}</textarea>
            </div>
            <div class="mb-3">
                <label for="changes[{{ $index }}]" class="form-label">Have You Made Any Changes in the Project Such as New Activities or Modified the Activities Contextually?</label>
                <div>
                    <input type="radio" name="changes[{{ $index }}]" value="yes" onclick="toggleWhyChanges(this, {{ $index }})" @if(old("changes.$index") == 'yes') checked @endif> Yes
                    <input type="radio" name="changes[{{ $index }}]" value="no" onclick="toggleWhyChanges(this, {{ $index }})" @if(old("changes.$index") == 'no') checked @endif> No
                </div>
            </div>
            <div class="mb-3 d-none" id="why_changes_container_{{ $index }}">
                <label for="why_changes[{{ $index }}]" class="form-label">Explain Why the Changes Were Needed</label>
                <textarea name="why_changes[{{ $index }}]" class="form-control" rows="3" style="background-color: #202ba3;">{{ old("why_changes.$index") }}</textarea>
            </div>
            <div class="mb-3">
                <label for="lessons_learnt[{{ $index }}]" class="form-label">What Are the Lessons Learnt?</label>
                <textarea name="lessons_learnt[{{ $index }}]" class="form-control" rows="3" style="background-color: #202ba3;">{{ old("lessons_learnt.$index") }}</textarea>
            </div>
            <div class="mb-3">
                <label for="todo_lessons_learnt[{{ $index }}]" class="form-label">What Will Be Done Differently Because of the Learnings?</label>
                <textarea name="todo_lessons_learnt[{{ $index }}]" class="form-control" rows="3" style="background-color: #202ba3;">{{ old("todo_lessons_learnt.$index") }}</textarea>
            </div>
        </div>
    </div>
    @endforeach
</div>
<button type="button" class="btn btn-primary" onclick="addObjective()">Add More Objective</button>
