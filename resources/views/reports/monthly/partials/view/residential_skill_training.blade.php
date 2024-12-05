{{-- resources/views/reports/monthly/partials/view/residential_skill_training.blade.php --}}
<!-- Trainees Profile Section -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>2. Information about the Trainees</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Heading Row -->
            <div class="col-6"><strong>Education of Trainees</strong></div>
            <div class="col-6"><strong>Number</strong></div>
        </div>
        <div class="row">
            <!-- Education of Trainees -->
            <div class="col-6">Below 9th standard</div>
            <div class="col-6">{{ $report->education['below_9'] ?? 0 }}</div>

            <div class="col-6">10th class failed</div>
            <div class="col-6">{{ $report->education['class_10_fail'] ?? 0 }}</div>

            <div class="col-6">10th class passed</div>
            <div class="col-6">{{ $report->education['class_10_pass'] ?? 0 }}</div>

            <div class="col-6">Intermediate</div>
            <div class="col-6">{{ $report->education['intermediate'] ?? 0 }}</div>

            <div class="col-6">Intermediate and above</div>
            <div class="col-6">{{ $report->education['above_intermediate'] ?? 0 }}</div>

            <div class="col-6">{{ $report->education['other'] ?? 'Other (if any)' }}</div>
            <div class="col-6">{{ $report->education['other_count'] ?? 0 }}</div>

            <!-- Total -->
            <div class="col-6"><strong>Total</strong></div>
            <div class="col-6"><strong>{{ $report->education['total'] ?? 0 }}</strong></div>
        </div>
    </div>
</div>

<style>
    .card-body .row > .col-6 {
        margin-bottom: 10px; /* Consistent spacing between rows */
    }

    .card-body .row {
        margin: 0; /* Ensure proper alignment */
    }

    .card-header h4 {
        margin-bottom: 0; /* Consistent header alignment */
    }
</style>
