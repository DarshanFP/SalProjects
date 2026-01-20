<!-- resources/views/projects/partials/edit_timeframe.blade.php -->
<div class="mt-4 card time-frame-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6>Time Frame for Activities</h6>
        <button type="button" class="btn btn-primary btn-sm" onclick="addTimeFrameRow(this)">Add Row</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm timeframe-table" style="table-layout: fixed; width: 100%;">
                <thead>
                    <tr>
                        <th scope="col" style="width: 3%;">No.</th>
                        <th scope="col" style="width: 45%;">Activities</th>
                        @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $monthAbbreviation)
                            <th scope="col" class="month-header" style="width: calc(45% / 12);">{{ $monthAbbreviation }}</th>
                        @endforeach
                        <th scope="col" style="width: 5%;">Action</th>
                    </tr>
                </thead>
            <tbody>
                @if($objective->activities->isEmpty())
                    <tr class="activity-timeframe-row">
                            <td style="text-align: center; vertical-align: middle;">1</td>
                            <td class="activity-description-text table-cell-wrap" style="width: 45%; vertical-align: top;">
                                <textarea name="objectives[{{ $objectiveIndex }}][activities][0][timeframe][description]" class="form-control select-input logical-textarea" rows="3"></textarea>
                            </td>
                        @for($month = 1; $month <= 12; $month++)
                            <td class="text-center month-checkbox-cell" style="width: calc(45% / 12);">
                                <input type="checkbox" class="month-checkbox" value="1" name="objectives[{{ $objectiveIndex }}][activities][0][timeframe][months][{{ $month }}]">
                            </td>
                        @endfor
                        <td style="width: 5%;"><button type="button" class="btn btn-danger btn-sm" onclick="removeTimeFrameRow(this)">Remove</button></td>
                    </tr>
                @else
                    @foreach($objective->activities as $activityIndex => $activity)
                        <tr class="activity-timeframe-row">
                            <td style="text-align: center; vertical-align: middle;">{{ $activityIndex + 1 }}</td>
                            <td class="activity-description-text table-cell-wrap" style="width: 45%; vertical-align: top;">
                                <textarea name="objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][timeframe][description]" class="form-control select-input logical-textarea" rows="3">{{ $activity->activity }}</textarea>
                            </td>
                            @for($month = 1; $month <= 12; $month++)
                                <td class="text-center month-checkbox-cell" style="width: calc(45% / 12);">
                                    <input type="checkbox" class="month-checkbox" value="1" name="objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][timeframe][months][{{ $month }}]"
                                    {{ $activity->timeframes->contains('month', $month) && $activity->timeframes->where('month', $month)->first()->is_active ? 'checked' : '' }}>
                                </td>
                            @endfor
                            <td style="width: 5%;"><button type="button" class="btn btn-danger btn-sm" onclick="removeTimeFrameRow(this)">Remove</button></td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-resize function for timeframe textareas
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }

    // Apply to all timeframe textareas
    const timeframeTextareas = document.querySelectorAll('.timeframe-table .logical-textarea');
    timeframeTextareas.forEach(textarea => {
        // Set initial height
        autoResizeTextarea(textarea);
        
        // Auto-resize on input
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });
    
    // Re-initialize after adding new rows (event delegation)
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('logical-textarea') && e.target.closest('.timeframe-table')) {
            autoResizeTextarea(e.target);
        }
    });
});
</script>

<style>
.timeframe-table {
    table-layout: fixed;
    width: 100%;
}

.timeframe-table tr {
    height: auto;
}

.timeframe-table td {
    height: auto;
}

.timeframe-table th:first-child {
    width: 50%;
}

.timeframe-table .month-header {
    padding: 4px 1px !important;
    font-size: 10px;
    text-align: center;
    width: calc(45% / 12);
}

.timeframe-table .month-checkbox-cell {
    padding: 4px 1px !important;
    text-align: center;
    vertical-align: middle;
    width: calc(45% / 12);
}

.timeframe-table .activity-description-text {
    width: 50%;
    padding: 8px !important;
    vertical-align: top;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    text-align: left;
}

.timeframe-table .activity-description-text textarea {
    width: 100% !important;
    max-width: 100%;
    min-height: 60px;
    height: auto;
    resize: vertical;
    word-wrap: break-word;
    overflow-wrap: break-word;
    box-sizing: border-box;
    margin: 0;
    overflow-y: hidden;
    overflow-x: hidden;
    line-height: 1.5;
    padding: 8px 12px;
    text-align: left;
}

.timeframe-table .activity-description-text textarea:focus {
    overflow-y: auto;
}

.month-checkbox {
    margin: 0 auto;
    position: relative;
    display: block;
    transform: scale(0.85);
    cursor: pointer;
    accent-color: #6571ff;
}

.month-checkbox:checked {
    accent-color: #6571ff;
}

.table td {
    text-align: center;
    vertical-align: middle;
}

.timeframe-table td:not(.activity-description-text) {
    text-align: center;
    vertical-align: middle;
}
</style>
