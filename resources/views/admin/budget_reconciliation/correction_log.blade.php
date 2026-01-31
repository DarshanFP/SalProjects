@extends('admin.layout')
@section('title', 'Correction Log')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-2">Budget Correction Log</h4>
            <p class="text-muted mb-0">Immutable audit trail: who, when, what changed. Read-only.</p>
        </div>
        <a href="{{ route('admin.budget-reconciliation.index') }}" class="btn btn-outline-primary">Back to Reconciliation</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="get" action="{{ route('admin.budget-reconciliation.log') }}" class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label">Project ID</label>
                    <input type="number" name="project_id" class="form-control" placeholder="id" value="{{ $filters['project_id'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Admin user</label>
                    <select name="user_id" class="form-select">
                        <option value="">All</option>
                        @foreach($adminUsers as $u)
                            <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Action</label>
                    <select name="action_type" class="form-select">
                        <option value="">All</option>
                        <option value="accept_suggested" {{ ($filters['action_type'] ?? '') === 'accept_suggested' ? 'selected' : '' }}>Accept suggested</option>
                        <option value="manual_correction" {{ ($filters['action_type'] ?? '') === 'manual_correction' ? 'selected' : '' }}>Manual correction</option>
                        <option value="reject" {{ ($filters['action_type'] ?? '') === 'reject' ? 'selected' : '' }}>Reject</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Who</th>
                            <th>Project</th>
                            <th>Type</th>
                            <th>Action</th>
                            <th>Old sanctioned</th>
                            <th>New sanctioned</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $log->adminUser->name ?? $log->admin_user_id }}</td>
                                <td>{{ $log->project_id }} @if($log->project)({{ $log->project->project_id ?? '-' }})@endif</td>
                                <td>{{ Str::limit($log->project_type ?? '-', 25) }}</td>
                                <td><span class="badge bg-secondary">{{ $log->action_type }}</span></td>
                                <td>{{ $log->old_sanctioned !== null ? number_format($log->old_sanctioned, 2) : '—' }}</td>
                                <td>{{ $log->new_sanctioned !== null ? number_format($log->new_sanctioned, 2) : '—' }}</td>
                                <td>{{ Str::limit($log->admin_comment ?? '—', 50) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No audit entries match the filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $logs->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
