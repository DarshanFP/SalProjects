{{-- Status badge partial: if trashed show Trashed, else show workflow status --}}
@if(isset($project) && $project->trashed())
    <span class="badge bg-danger">Trashed</span>
    @if(!empty($showPreDeleteStatus))
        <small class="text-muted d-block">{{ \App\Models\OldProjects\Project::$statusLabels[$project->status] ?? $project->status }}</small>
    @endif
@else
    {{ \App\Models\OldProjects\Project::$statusLabels[$project->status] ?? $project->status ?? '—' }}
@endif
