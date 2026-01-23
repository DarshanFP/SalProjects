{{-- resources/views/reports/monthly/partials/view/residential_skill_training.blade.php --}}
<!-- Trainees Profile Section -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>2. Information about the Trainees</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Heading Row -->
            <div class="col-2 report-label-col"><strong>Education of Trainees</strong></div>
            <div class="col-10 report-value-col"><strong>Number</strong></div>
        </div>
        <div class="row">
            <!-- Education of Trainees -->
            <div class="col-2 report-label-col">Below 9th standard</div>
            <div class="col-10 report-value-col report-value-entered">{{ $report->education['below_9'] ?? 0 }}</div>
        </div>
        <div class="row">
            <div class="col-2 report-label-col">10th class failed</div>
            <div class="col-10 report-value-col report-value-entered">{{ $report->education['class_10_fail'] ?? 0 }}</div>
        </div>
        <div class="row">
            <div class="col-2 report-label-col">10th class passed</div>
            <div class="col-10 report-value-col report-value-entered">{{ $report->education['class_10_pass'] ?? 0 }}</div>
        </div>
        <div class="row">
            <div class="col-2 report-label-col">Intermediate</div>
            <div class="col-10 report-value-col report-value-entered">{{ $report->education['intermediate'] ?? 0 }}</div>
        </div>
        <div class="row">
            <div class="col-2 report-label-col">Intermediate and above</div>
            <div class="col-10 report-value-col report-value-entered">{{ $report->education['above_intermediate'] ?? 0 }}</div>
        </div>
        <div class="row">
            <div class="col-2 report-label-col">{{ $report->education['other'] ?? 'Other (if any)' }}</div>
            <div class="col-10 report-value-col report-value-entered">{{ $report->education['other_count'] ?? 0 }}</div>
        </div>
        <div class="row">
            <!-- Total -->
            <div class="col-2 report-label-col"><strong>Total</strong></div>
            <div class="col-10 report-value-col report-value-entered"><strong>{{ $report->education['total'] ?? 0 }}</strong></div>
        </div>
    </div>
</div>

<style>
    .card-body .row {
        margin-bottom: 10px; /* Consistent spacing between rows */
    }

    .card-header h4 {
        margin-bottom: 0; /* Consistent header alignment */
    }
</style>
