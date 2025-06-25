
{{-- resources/views/projects/partials/Show/CCI/present_situation.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Present Situation of the Inmates</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <!-- Internal Challenges -->
            <div class="info-label"><strong>Internal Challenges Faced from Inmates:</strong></div>
            <div class="info-value">{{ $presentSituation->internal_challenges ?? 'No internal challenges recorded.' }}</div>

            <!-- External Challenges -->
            <div class="info-label"><strong>External Challenges / Present Difficulties:</strong></div>
            <div class="info-value">{{ $presentSituation->external_challenges ?? 'No external challenges recorded.' }}</div>
        </div>
    </div>

    <div class="card-header">
        <h4>Area of Focus for the Current Year</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <!-- Main Focus Areas -->
            <div class="info-label"><strong>Main Focus Areas:</strong></div>
            <div class="info-value">{{ $presentSituation->area_of_focus ?? 'No focus areas specified.' }}</div>
        </div>
    </div>
</div>
