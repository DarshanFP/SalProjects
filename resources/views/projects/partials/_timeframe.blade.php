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
                <tr class="activity-timeframe-row">
                    <td class="activity-description-text">
                        <textarea name="objectives[{{ $objectiveIndex }}][activities][0][timeframe][description]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea>
                    </td>
                    @for($month = 1; $month <= 12; $month++)
                        <td class="text-center">
                            <input type="checkbox" class="month-checkbox" value="1" name="objectives[{{ $objectiveIndex }}][activities][0][timeframe][months][{{ $month }}]">
                        </td>
                    @endfor
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeTimeFrameRow(this)">Remove</button></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-primary btn-sm" onclick="addTimeFrameRow(this, {{ $objectiveIndex }})">Add Row</button>
    </div>
</div>

<style>
/* Center the checkboxes in the table cells */
.table td {
    text-align: center;
    vertical-align: middle;
}

.month-checkbox {
    margin: 0 auto; /* Center horizontally */
    position: relative; /* Ensure it's positioned relative to its parent */
    display: block;
}
</style>
