<div class="mt-4 card time-frame-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6>Time Frame for Activities</h6>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col" style="width: 40%;">Activities</th>
                    @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $monthAbbreviation)
                        <th scope="col">{{ $monthAbbreviation }}</th>
                    @endforeach
                    <th scope="col" style="width: 6%;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($objective->activities as $activityIndex => $activity)
                    <tr class="activity-timeframe-row">
                        <td class="activity-description-text">
                            <textarea name="objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][timeframe][description]" class="form-control" rows="2">{{ $activity->activity }}</textarea>
                        </td>
                        @for($month = 1; $month <= 12; $month++)
                            <td class="text-center">
                                <input type="checkbox" class="month-checkbox" value="1" name="objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][timeframe][months][{{ $month }}]"
                                {{ $activity->timeframes->contains('month', $month) && $activity->timeframes->where('month', $month)->first()->is_active ? 'checked' : '' }}>
                            </td>
                        @endfor
                        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeTimeFrameRow(this)">Remove</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <button type="button" class="btn btn-primary btn-sm" onclick="addTimeFrameRow(this, {{ $objectiveIndex }})">Add Row</button>
    </div>
</div>
