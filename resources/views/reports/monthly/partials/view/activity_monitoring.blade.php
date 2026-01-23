{{-- Activity Monitoring: only "scheduled but not reported" activities, grouped objective‑wise. Before Add Comment. Refined design: Updates.md §4.3 --}}
@php
    $grouped = $activitiesScheduledButNotReportedGroupedByObjective ?? [];
    $showStatuses = ['submitted_to_provincial', 'forwarded_to_coordinator'];
    $showByStatus = in_array($report->status ?? '', $showStatuses);
    $showByRole = in_array(auth()->user()->role ?? '', ['provincial', 'coordinator']);
    $hasAny = count($grouped) > 0;
@endphp
@if($showByStatus && $showByRole && $hasAny)
<div class="mb-3 card border-warning">
    <div class="card-header bg-warning bg-opacity-25">
        <h4 class="fp-text-margin mb-0">Activity Monitoring — Scheduled for this month but not reported</h4>
    </div>
    <div class="card-body">
        @foreach($grouped as $item)
        <div class="mb-4">
            <h5 class="text-secondary mb-2"><strong>Objective:</strong> {{ $item['objective'] ?? '—' }}</h5>
            <ul class="mb-0">
                @foreach($item['activities'] ?? [] as $a)
                <li>{{ $a['activity'] ?? '—' }}</li>
                @endforeach
            </ul>
        </div>
        @endforeach
        <p class="text-muted small mb-0">Check &ldquo;What did not happen&rdquo; / &ldquo;Why not&rdquo; for the relevant objective (if in the report), or consider reverting so the executor can add the objective and explain.</p>
    </div>
</div>
@endif
