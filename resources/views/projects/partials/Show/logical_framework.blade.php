{{-- resources/views/projects/partials/Show/logical_framework.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Logical Framework</h4>
    </div>
    <div class="card-body">
        @foreach($project->objectives as $objective)
        <div class="p-3 mb-4 rounded border objective-card">
            <h5 class="mb-3">Objective: {{ $objective->objective }}</h5>

            <div class="mb-4 results-container">
                <h6 class="mb-3">Results / Outcomes</h6>
                @foreach($objective->results as $result)
                <div class="p-2 mb-3 rounded border result-section">
                    <p>{{ $result->result }}</p>
                </div>
                @endforeach
            </div>

            <!-- Risks Section -->
            <div class="mb-4 risks-container">
                <h6 class="mb-3">Risks</h6>
                @if($objective->risks->isNotEmpty())
                    <div class="p-2 mb-3 rounded border">
                        @foreach($objective->risks as $risk)
                            <p>{{ $risk->risk }}</p>
                        @endforeach
                    </div>
                @endif
            </div>


            <!-- Activities and Means of Verification -->
            <div class="mb-4 activities-container">
                <h6 class="mb-3">Activities and Means of Verification</h6>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 40%;">Activities</th>
                            <th scope="col">Means of Verification</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($objective->activities as $activity)
                        <tr>
                            <td>{{ $activity->activity }}</td>
                            <td>{{ $activity->verification }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Time Frame Section -->
            <div class="time-frame-container">
                <h6 class="mb-3">Time Frame for Activities</h6>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 40%;">Activities</th>
                            @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $monthAbbreviation)
                            <th scope="col">{{ $monthAbbreviation }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($objective->activities as $activity)
                        <tr class="activity-timeframe-row">
                            <td>{{ $activity->activity }}</td>
                            @foreach(range(1, 12) as $month)
                            <td class="text-center">
                                @php
                                $isChecked = $activity->timeframes->contains(function($timeframe) use ($month) {
                                    return $timeframe->month == $month && $timeframe->is_active == 1;
                                });
                                @endphp
                                @if($isChecked)
                                    <span class="checkmark-icon" style="font-size: 16px; font-weight: bold; color: #ccc;">✓</span>
                                @else
                                    <span style="font-size: 16px; color: #ccc;">○</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
</div>
