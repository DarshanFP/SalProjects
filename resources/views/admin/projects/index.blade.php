@extends('admin.layout')
@section('title', 'Projects (Read-only)')
@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">All Projects (Read-only)</h4>
                    <p class="text-muted small mb-0">Admin visibility only. No edit, submit, or approve actions.</p>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.projects.index') }}" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Project ID, Title, Type..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="province" class="form-label">Province</label>
                            <select name="province" id="province" class="form-select">
                                <option value="">All</option>
                                @foreach($provinces as $p)
                                    <option value="{{ $p }}" {{ request('province') == $p ? 'selected' : '' }}>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All</option>
                                @foreach($statuses as $s)
                                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ \App\Models\OldProjects\Project::$statusLabels[$s] ?? $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="project_type" class="form-label">Type</label>
                            <select name="project_type" id="project_type" class="form-select">
                                <option value="">All</option>
                                @foreach($projectTypes as $t)
                                    <option value="{{ $t }}" {{ request('project_type') == $t ? 'selected' : '' }}>{{ Str::limit($t, 25) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Project ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Executor</th>
                                    <th>Province</th>
                                    <th>Status</th>
                                    <th>Budget</th>
                                    <th>Utilization</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                    <tr>
                                        <td>{{ $project->project_id }}</td>
                                        <td>{{ Str::limit($project->project_title, 40) }}</td>
                                        <td>{{ Str::limit($project->project_type ?? '-', 25) }}</td>
                                        <td>{{ $project->user->name ?? '-' }}</td>
                                        <td>{{ $project->user->province ?? '-' }}</td>
                                        <td><span class="badge bg-secondary">{{ \App\Models\OldProjects\Project::$statusLabels[$project->status] ?? $project->status }}</span></td>
                                        <td>{{ number_format($project->calculated_budget ?? 0, 2) }}</td>
                                        <td>{{ $project->budget_utilization ?? 0 }}%</td>
                                        <td>
                                            <a href="{{ route('admin.projects.show', $project->project_id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted">No projects found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($pagination['last_page'] > 1)
                    <nav class="mt-3 d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $pagination['from'] }}–{{ $pagination['to'] }} of {{ $pagination['total'] }}
                        </div>
                        <ul class="pagination mb-0">
                            @if($pagination['current_page'] > 1)
                                <li class="page-item"><a class="page-link" href="{{ route('admin.projects.index', array_merge(request()->except('page'), ['page' => $pagination['current_page'] - 1])) }}">Previous</a></li>
                            @endif
                            @if($pagination['current_page'] < $pagination['last_page'])
                                <li class="page-item"><a class="page-link" href="{{ route('admin.projects.index', array_merge(request()->except('page'), ['page' => $pagination['current_page'] + 1])) }}">Next</a></li>
                            @endif
                        </ul>
                    </nav>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
