<!-- resources/views/projects/partials/_timeframe.blade.php -->
<!-- resources/views/projects/partials/_timeframe.blade.php -->
<div class="mt-4 card time-frame-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6>Time Frame for Activities</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm timeframe-table">
                <thead>
                    <tr>
                        <th scope="col" style="min-width: 50px;">No.</th>
                        <th scope="col" style="min-width: 200px;">Activities</th>
                        @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $monthAbbreviation)
                            <th scope="col" class="month-header">{{ $monthAbbreviation }}</th>
                        @endforeach
                        <th scope="col" style="min-width: 80px;">Action</th>
                    </tr>
                </thead>
            <tbody>
                @if(isset($predecessorActivities) && !empty($predecessorActivities))
                    @foreach($predecessorActivities as $aIndex => $activity)
                        <tr class="activity-timeframe-row">
                            <td style="text-align: center; vertical-align: middle;">{{ $aIndex + 1 }}</td>
                            <td class="activity-description-text table-cell-wrap">
                                <textarea name="objectives[{{ $objectiveIndex }}][activities][{{ $aIndex }}][timeframe][description]" class="form-control select-input logical-textarea" rows="2">{{ $activity['activity'] ?? '' }}</textarea>
                            </td>
                            @for($month = 1; $month <= 12; $month++)
                                <td class="text-center month-checkbox-cell">
                                    <input type="checkbox" class="month-checkbox" value="1" name="objectives[{{ $objectiveIndex }}][activities][{{ $aIndex }}][timeframe][months][{{ $month }}]"
                                        {{ (isset($activity['timeframes']) && collect($activity['timeframes'])->contains('month', (string)$month) && collect($activity['timeframes'])->firstWhere('month', (string)$month)['is_active']) ? 'checked' : '' }}>
                                </td>
                            @endfor
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeTimeFrameRow(this)">Remove</button></td>
                        </tr>
                    @endforeach
                @else
                    <tr class="activity-timeframe-row">
                        <td style="text-align: center; vertical-align: middle;">1</td>
                        <td class="activity-description-text table-cell-wrap">
                            <textarea name="objectives[{{ $objectiveIndex }}][activities][0][timeframe][description]" class="form-control select-input logical-textarea" rows="2"></textarea>
                        </td>
                        @for($month = 1; $month <= 12; $month++)
                            <td class="text-center month-checkbox-cell">
                                <input type="checkbox" class="month-checkbox" value="1" name="objectives[{{ $objectiveIndex }}][activities][0][timeframe][months][{{ $month }}]">
                            </td>
                        @endfor
                        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeTimeFrameRow(this)">Remove</button></td>
                    </tr>
                @endif
            </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-primary btn-sm" onclick="addTimeFrameRow(this)">Add Row</button>
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
});
</script>

<style>
.timeframe-table {
    table-layout: auto;
}

.timeframe-table .month-header {
    min-width: 40px;
    max-width: 50px;
    padding: 4px 2px !important;
    font-size: 11px;
    text-align: center;
}

.timeframe-table .month-checkbox-cell {
    padding: 4px 2px !important;
    text-align: center;
    vertical-align: middle;
    width: 40px;
}

.timeframe-table .activity-description-text {
    min-width: 200px;
    max-width: 300px;
    padding: 8px !important;
}

.timeframe-table .activity-description-text textarea {
    width: 100%;
    min-height: 50px;
    resize: vertical;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Auto-resize for timeframe textareas */
.logical-textarea {
    resize: vertical;
    min-height: 50px;
    height: auto;
    overflow-y: hidden;
    line-height: 1.5;
    padding: 8px 12px;
}

.logical-textarea:focus {
    overflow-y: auto;
}

.month-checkbox {
    margin: 0 auto;
    position: relative;
    display: block;
    transform: scale(0.9);
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
</style>
