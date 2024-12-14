{{-- resources/views/projects/partials/actions.blade.php --}}
{{-- resources/views/projects/partials/actions.blade.php --}}
@php
    $user = Auth::user();
    $status = $project->status;
    $userRole = $user->role;
@endphp

<div class="mb-3 card">
    <div class="card-header">
        <h4>Actions</h4>
    </div>
    <div class="card-body">
        @if($userRole === 'executor')
            @if($status === 'draft' || $status === 'reverted_by_provincial')
                <!-- Executor can submit to provincial -->
                <form action="{{ route('projects.submitToProvincial', $project->project_id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Submit to Provincial</button>
                </form>
            @endif
        @endif

        @if($userRole === 'provincial')
            @if($status === 'submitted_to_provincial' || $status === 'reverted_by_coordinator')
                <!-- Provincial can revert to executor or forward to coordinator -->
                <form action="{{ route('projects.revertToExecutor', $project->project_id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-warning">Revert to Executor</button>
                </form>

                <form action="{{ route('projects.forwardToCoordinator', $project->project_id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-success">Forward to Coordinator</button>
                </form>
            @endif
        @endif

        @if($userRole === 'coordinator')
            @if($status === 'forwarded_to_coordinator')
                <!-- Coordinator can approve, reject, or revert to provincial -->
                <form action="{{ route('projects.approve', $project->project_id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-success">Approve</button>
                </form>

                <form action="{{ route('projects.reject', $project->project_id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger">Reject</button>
                </form>

                <form action="{{ route('projects.revertToProvincial', $project->project_id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-warning">Revert to Provincial</button>
                </form>
            @endif
        @endif
    </div>
</div>
