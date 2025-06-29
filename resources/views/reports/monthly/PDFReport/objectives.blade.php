@if($report->objectives && $report->objectives->count() > 0)
    <div class="section-header">Objectives and Activities</div>
    @foreach($report->objectives as $objective)
        <div class="avoid-break">
            <h3>Objective: {{ $objective->objective_name ?? 'N/A' }}</h3>
            <p><strong>Description:</strong> {{ $objective->objective_description ?? 'N/A' }}</p>

            @if($objective->activities && $objective->activities->count() > 0)
                <table class="activities-table">
                    <tr class="header-row">
                        <td>Activity</td>
                        <td>Description</td>
                        <td>Status</td>
                        <td>Progress (%)</td>
                    </tr>
                    @foreach($objective->activities as $activity)
                        <tr>
                            <td>{{ $activity->activity_name ?? 'N/A' }}</td>
                            <td>{{ $activity->activity_description ?? 'N/A' }}</td>
                            <td>{{ $activity->status ?? 'N/A' }}</td>
                            <td>{{ $activity->progress ?? 0 }}%</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p><em>No activities found for this objective.</em></p>
            @endif
        </div>
    @endforeach
@else
    <div class="section-header">Objectives and Activities</div>
    <p><em>No objectives found for this report.</em></p>
@endif
