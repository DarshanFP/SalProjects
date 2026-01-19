{{-- resources/views/projects/partials/Show/logical_framework.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Logical Framework</h4>
    </div>
    <div class="card-body">
        @foreach($project->objectives as $objIndex => $objective)
        <div class="p-3 mb-4 rounded border objective-card">
            <h5 class="mb-3">Objective {{ $objIndex + 1 }}: <span style="white-space: pre-wrap; line-height: 1.6; font-weight: normal;">{{ $objective->objective }}</span></h5>

            <div class="mb-4 results-container">
                <h6 class="mb-3">Results / Outcomes</h6>
                @foreach($objective->results as $resIndex => $result)
                <div class="p-2 mb-3 rounded border result-section">
                    <strong>Objective {{ $objIndex + 1 }} - Result {{ $resIndex + 1 }}:</strong>
                    <p style="white-space: pre-wrap; line-height: 1.6; margin-top: 8px;">{{ $result->result }}</p>
                </div>
                @endforeach
            </div>

            <!-- Risks Section -->
            <div class="mb-4 risks-container">
                <h6 class="mb-3">Risks</h6>
                @if($objective->risks->isNotEmpty())
                    <div class="p-2 mb-3 rounded border">
                        @foreach($objective->risks as $riskIndex => $risk)
                            <div class="mb-2">
                                <strong>Objective {{ $objIndex + 1 }} - Risk {{ $riskIndex + 1 }}:</strong>
                                <p style="white-space: pre-wrap; line-height: 1.6; margin-top: 8px;">{{ $risk->risk }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>


            <!-- Activities and Means of Verification -->
            <div class="mb-4 activities-container">
                <h6 class="mb-3">Activities and Means of Verification</h6>
                <div class="table-responsive">
                    <table class="table table-bordered activities-table" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 5%;">No.</th>
                                <th scope="col" style="width: 47.5%;">Activities</th>
                                <th scope="col" style="width: 47.5%;">Means of Verification</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($objective->activities as $actIndex => $activity)
                            <tr>
                                <td style="text-align: center; vertical-align: middle;">{{ $actIndex + 1 }}</td>
                                <td class="table-cell-wrap" style="width: 47.5%; text-align: left; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; white-space: pre-wrap; line-height: 1.6; padding: 12px;">{{ $activity->activity }}</td>
                                <td class="table-cell-wrap" style="width: 47.5%; text-align: left; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; white-space: pre-wrap; line-height: 1.6; padding: 12px;">{{ $activity->verification }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Time Frame Section -->
            <div class="time-frame-container">
                <h6 class="mb-3">Time Frame for Activities</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm timeframe-table" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 5%;">No.</th>
                                <th scope="col" style="width: 55%;">Activities</th>
                                @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $monthAbbreviation)
                                <th scope="col" class="month-header" style="width: calc(40% / 12);">{{ $monthAbbreviation }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($objective->activities as $actIndex => $activity)
                            <tr class="activity-timeframe-row">
                                <td style="text-align: center; vertical-align: middle;">{{ $actIndex + 1 }}</td>
                                <td class="table-cell-wrap activity-description-text" style="width: 55%; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; white-space: pre-wrap; line-height: 1.6;">{{ $activity->activity }}</td>
                                @foreach(range(1, 12) as $month)
                                <td class="text-center month-checkbox-cell" style="width: calc(40% / 12);">
                                    @php
                                    $isChecked = $activity->timeframes->contains(function($timeframe) use ($month) {
                                        return $timeframe->month == $month && $timeframe->is_active == 1;
                                    });
                                    @endphp
                                    @if($isChecked)
                                        <span class="checkmark-icon" style="font-size: 14px; font-weight: bold; color: #6571ff;">✓</span>
                                    @else
                                        <span style="font-size: 14px; color: #ccc;">○</span>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
.activities-table {
    table-layout: fixed;
    width: 100%;
}

.activities-table td {
    text-align: left;
    vertical-align: top;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: pre-wrap;
    line-height: 1.6;
    padding: 12px;
    height: auto;
}

.activities-table th {
    text-align: center;
    vertical-align: middle;
}

.activities-table tr {
    height: auto;
}

.timeframe-table {
    table-layout: fixed;
    width: 100%;
}

.timeframe-table th:first-child {
    width: 60%;
}

.timeframe-table .month-header {
    padding: 4px 2px !important;
    font-size: 11px;
    text-align: center;
    width: calc(40% / 12);
}

.timeframe-table .month-checkbox-cell {
    padding: 4px 2px !important;
    text-align: center;
    vertical-align: middle;
    width: calc(40% / 12);
}

.timeframe-table .activity-description-text {
    width: 60%;
    padding: 8px !important;
    vertical-align: top;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: pre-wrap;
    line-height: 1.6;
}

.timeframe-table td:not(.activity-description-text) {
    text-align: center;
    vertical-align: middle;
}
</style>
