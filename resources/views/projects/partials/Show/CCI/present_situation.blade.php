{{-- resources/views/projects/partials/Show/CCI/present_situation.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Present Situation of the Inmates</h4>
    </div>
    <div class="card-body">
        <!-- Internal Challenges -->
        <div class="mb-3">
            <h5>Internal Challenges Faced from Inmates</h5>
            <p>{{ $presentSituation->internal_challenges ?? 'No internal challenges recorded.' }}</p>
        </div>

        <!-- External Challenges -->
        <div class="mb-3">
            <h5>External Challenges / Present Difficulties</h5>
            <p>{{ $presentSituation->external_challenges ?? 'No external challenges recorded.' }}</p>
        </div>
    </div>

    <div class="card-header">
        <h4>Area of Focus for the Current Year</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <h5>Main Focus Areas</h5>
            <p>{{ $presentSituation->area_of_focus ?? 'No focus areas specified.' }}</p>
        </div>
    </div>
</div>

<!-- Styles for consistency -->
{{-- <style>
    .card-body p {
        background-color: #f9f9f9;
        padding: 1rem;
        border-radius: 4px;
        border: 1px solid #ddd;
        white-space: pre-wrap; /* Preserve line breaks */
    }

    h5 {
        color: #202ba3;
        font-weight: bold;
    }
</style> --}}
