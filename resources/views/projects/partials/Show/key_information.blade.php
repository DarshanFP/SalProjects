{{-- resources/views/projects/partials/Show/key_information.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Key Information</h4>
    </div>
    <div class="card-body">
        @if($project->initial_information)
            <div class="mb-3">
                <div class="info-label"><strong>Initial Information:</strong></div>
                <div class="info-value">{{ $project->initial_information }}</div>
            </div>
        @endif

        @if($project->target_beneficiaries)
            <div class="mb-3">
                <div class="info-label"><strong>Target Beneficiaries:</strong></div>
                <div class="info-value">{{ $project->target_beneficiaries }}</div>
            </div>
        @endif

        @if($project->general_situation)
            <div class="mb-3">
                <div class="info-label"><strong>General Situation:</strong></div>
                <div class="info-value">{{ $project->general_situation }}</div>
            </div>
        @endif

        @if($project->need_of_project)
            <div class="mb-3">
                <div class="info-label"><strong>Need of the Project:</strong></div>
                <div class="info-value">{{ $project->need_of_project }}</div>
            </div>
        @endif

        @if($project->goal)
            <div class="mb-3">
            <div class="info-label"><strong>Goal of the Project:</strong></div>
            <div class="info-value">{{ $project->goal }}</div>
        </div>
        @endif

        @if($project->problem_tree_file_path)
            <div class="mb-3">
                <div class="info-label"><strong>Problem Tree:</strong></div>
                <div class="info-value problem-tree-view-preview">
                    <a href="{{ $project->problem_tree_image_url }}" target="_blank" rel="noopener" title="Open full size">
                        <img src="{{ $project->problem_tree_image_url }}" alt="Problem Tree" class="img-thumbnail" style="max-height:320px; max-width:100%; cursor:pointer;">
                    </a>
                    <small class="d-block text-muted mt-1">Click image to open full size</small>
                </div>
            </div>
        @endif

        @if(!$project->initial_information && !$project->target_beneficiaries && !$project->general_situation && !$project->need_of_project && !$project->goal && !$project->problem_tree_file_path)
            <div class="text-muted">No key information provided yet.</div>
        @endif
    </div>
</div>

<style>
/* Preserve line breaks in Key Information display */
.info-value {
    white-space: pre-wrap !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    line-height: 1.6 !important;
}
</style>
