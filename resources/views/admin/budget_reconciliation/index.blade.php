@extends('admin.layout')
@section('title', 'Budget Reconciliation')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-2">Budget Reconciliation</h4>
            <p class="text-muted mb-0">Approved projects only. Compare stored vs resolver-computed values. No automatic correction.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.budget-reconciliation.log') }}" class="btn btn-outline-secondary">Correction Log</a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">Back to Dashboard</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="get" action="{{ route('admin.budget-reconciliation.index') }}" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Project type</label>
                    <select name="project_type" class="form-select">
                        <option value="">All</option>
                        @foreach($projectTypes as $type)
                            <option value="{{ $type }}" {{ ($filters['project_type'] ?? '') === $type ? 'selected' : '' }}>{{ Str::limit($type, 40) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From date</label>
                    <input type="date" name="approval_date_from" class="form-control" value="{{ $filters['approval_date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To date</label>
                    <input type="date" name="approval_date_to" class="form-control" value="{{ $filters['approval_date_to'] ?? '' }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" name="only_discrepancies" value="1" class="form-check-input" id="onlyDiscrepancies" {{ !empty($filters['only_discrepancies']) ? 'checked' : '' }}>
                        <label class="form-check-label" for="onlyDiscrepancies">Only discrepancies</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Project ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Stored sanctioned</th>
                            <th>Resolved sanctioned</th>
                            <th>Discrepancy</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr class="{{ $row['has_discrepancy'] ? 'table-warning' : '' }}">
                                <td>{{ $row['project']->project_id ?? $row['project']->id }}</td>
                                <td>{{ Str::limit($row['project']->project_title ?? '-', 35) }}</td>
                                <td>{{ Str::limit($row['project']->project_type ?? '-', 30) }}</td>
                                <td><span class="badge bg-success">{{ $row['project']->status ?? '-' }}</span></td>
                                <td>{{ number_format($row['stored']['amount_sanctioned'] ?? 0, 2) }}</td>
                                <td>{{ number_format($row['resolved']['amount_sanctioned'] ?? 0, 2) }}</td>
                                <td>
                                    @if($row['has_discrepancy'])
                                        <span class="badge bg-warning text-dark">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.budget-reconciliation.show', $row['project']->id) }}" class="btn btn-sm btn-outline-primary">Reconcile</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No approved projects match the filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
