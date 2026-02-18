{{-- Trash listing - extends role-appropriate layout --}}
@php
    $layout = match(auth()->user()->role) {
        'admin' => 'admin.layout',
        'general' => 'general.dashboard',
        'coordinator' => 'coordinator.dashboard',
        'provincial' => 'provincial.dashboard',
        default => 'executor.dashboard',
    };
@endphp
@extends($layout)

@section('title', 'Trash - Projects')
@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 fp-text-center1">Trashed Projects</h4>
                    @php
                        $backRoute = match(auth()->user()->role) {
                            'coordinator' => route('coordinator.projects.list'),
                            'provincial' => route('provincial.projects.list'),
                            'general' => route('general.projects'),
                            'admin' => route('admin.projects.index'),
                            default => route('projects.index'),
                        };
                    @endphp
                    <a href="{{ $backRoute }}" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left"></i> Back to Projects
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <table class="table table-bordered my-projects-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Project ID</th>
                                <th style="width: 25%;">Title</th>
                                <th style="width: 15%;">Project Type</th>
                                <th style="width: 12%;">Status</th>
                                <th style="width: 13%;">Deleted at</th>
                                <th style="width: 25%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                                <tr>
                                    <td>{{ $project->project_id }}</td>
                                    <td class="project-title">{{ $project->project_title ?? '—' }}</td>
                                    <td>{{ $project->project_type ?? '—' }}</td>
                                    <td>
                                        @include('projects.partials.status-badge', ['project' => $project, 'showPreDeleteStatus' => true])
                                    </td>
                                    <td>{{ $project->deleted_at ? $project->deleted_at->format('Y-m-d H:i') : '—' }}</td>
                                    <td>
                                        <form action="{{ route('projects.restore', $project->project_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore this project from trash?');">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">Restore</button>
                                        </form>
                                        @if(auth()->user()->role === 'admin')
                                            <form action="{{ route('projects.forceDelete', $project->project_id) }}" method="POST" class="d-inline ms-1" onsubmit="return confirm('Permanently delete this project? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Permanent Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No trashed projects.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $projects->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
