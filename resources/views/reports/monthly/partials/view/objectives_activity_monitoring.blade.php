{{-- Provincial activity monitoring: scheduled but not reported, reported but not scheduled, ad-hoc. --}}
@php
    $scheduledNotReported = $activitiesScheduledNotReported ?? [];
    $reportedNotScheduled = $activitiesReportedNotScheduled ?? [];
    $adhoc = $adhocActivities ?? [];
    $showStatuses = ['submitted_to_provincial', 'forwarded_to_coordinator'];
    $showByStatus = in_array($report->status ?? '', $showStatuses);
    $showByRole = in_array(auth()->user()->role ?? '', ['provincial', 'coordinator']);
    $hasAny = count($scheduledNotReported) > 0 || count($reportedNotScheduled) > 0 || count($adhoc) > 0;
@endphp
@if($showByStatus && $showByRole && $hasAny)
<div class="mb-3 card border-warning">
    <div class="card-header bg-warning bg-opacity-25">
        <h4 class="fp-text-margin mb-0">Objectives &amp; Activities — Monitoring</h4>
    </div>
    <div class="card-body">
        @if(count($scheduledNotReported) > 0)
        <div class="mb-4">
            <h5 class="text-secondary">Scheduled for this month but NOT reported</h5>
            <p class="text-muted small">Check &ldquo;What did not happen&rdquo; / &ldquo;Why not&rdquo; for the relevant objective.</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Objective</th>
                            <th>Activity</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($scheduledNotReported as $row)
                        <tr>
                            <td>{{ $row['objective'] ?: '—' }}</td>
                            <td>{{ $row['activity'] ?: '—' }}</td>
                            <td>Not reported this month. Check &ldquo;What did not happen&rdquo; / &ldquo;Why not&rdquo; for this objective.</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if(count($reportedNotScheduled) > 0)
        <div class="mb-4">
            <h5 class="text-secondary">NOT scheduled for this month but reported</h5>
            <p class="text-muted small">Planned for other months. Confirm if done in advance or reporting month chosen incorrectly.</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Objective</th>
                            <th>Activity</th>
                            <th>Reported month</th>
                            <th>Planned months</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportedNotScheduled as $row)
                        <tr>
                            <td>{{ $row['objective'] ?: '—' }}</td>
                            <td>{{ $row['activity'] ?: '—' }}</td>
                            <td>{{ $row['reported_month'] ?? '—' }}</td>
                            <td>{{ is_array($row['planned_months'] ?? null) ? implode(', ', $row['planned_months']) : '—' }}</td>
                            <td>Planned for other months. Confirm if done in advance or reporting month chosen incorrectly.</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if(count($adhoc) > 0)
        <div>
            <h5 class="text-secondary">Ad‑hoc activities (not in project plan)</h5>
            <p class="text-muted small">&ldquo;Add Other Activity&rdquo; — not linked to any project activity.</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Month</th>
                            <th>Objective</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adhoc as $row)
                        <tr>
                            <td>{{ $row['activity'] ?: '—' }}</td>
                            <td>{{ $row['month'] ?? '—' }}</td>
                            <td>{{ $row['objective'] ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endif
