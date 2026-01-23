{{-- Comments by Project Monitoring Committee (PMC) — read-only block. PMC_Implementation_Plan Phase 3. --}}
@php
    $pmc = trim((string)($report->pmc_comments ?? ''));
    $show = $pmc !== '';
    $canSee = in_array(auth()->user()->role ?? '', ['provincial', 'coordinator'])
        || (in_array(auth()->user()->role ?? '', ['executor', 'applicant'])
            && in_array($report->status ?? '', [
                'forwarded_to_coordinator',
                'reverted_by_coordinator',
                'approved_by_coordinator',
                'approved_by_general_as_coordinator',
                'approved_by_general_as_provincial',
            ]));
@endphp
@if($show && $canSee)
<div class="mb-3 card">
    <div class="card-header">
        <h4 class="fp-text-margin mb-0">Comments by Project Monitoring Committee</h4>
    </div>
    <div class="card-body">
        <div class="report-value-col">{!! nl2br(e($pmc)) !!}</div>
    </div>
</div>
@endif
