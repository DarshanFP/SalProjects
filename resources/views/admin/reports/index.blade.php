@extends('admin.layout')
@section('title', 'Reports (Read-only)')
@section('content')
@php
    use App\Models\Reports\Monthly\DPReport;
@endphp
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Monthly Reports (Read-only)</h4>
                    <p class="text-muted small mb-0">Admin visibility only. No submit, forward, or approve actions.</p>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Report ID, Project..." value="{{ request('search') }}">
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
                                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ DPReport::$statusLabels[$s] ?? $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Report ID</th>
                                    <th>Project ID</th>
                                    <th>Title</th>
                                    <th>Month/Year</th>
                                    <th>Executor</th>
                                    <th>Province</th>
                                    <th>Status</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports as $report)
                                    <tr>
                                        <td>{{ $report->report_id }}</td>
                                        <td>{{ $report->project_id }}</td>
                                        <td>{{ Str::limit($report->project_title ?? '-', 35) }}</td>
                                        <td>{{ $report->report_month_year ? \Carbon\Carbon::parse($report->report_month_year)->format('M Y') : '-' }}</td>
                                        <td>{{ $report->user->name ?? '-' }}</td>
                                        <td>{{ $report->user->province ?? '-' }}</td>
                                        <td><span class="badge bg-secondary">{{ DPReport::$statusLabels[$report->status] ?? $report->status }}</span></td>
                                        <td>
                                            <a href="{{ route('admin.reports.monthly.show', $report->report_id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted">No reports found.</td></tr>
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
                                <li class="page-item"><a class="page-link" href="{{ route('admin.reports.index', array_merge(request()->except('page'), ['page' => $pagination['current_page'] - 1])) }}">Previous</a></li>
                            @endif
                            @if($pagination['current_page'] < $pagination['last_page'])
                                <li class="page-item"><a class="page-link" href="{{ route('admin.reports.index', array_merge(request()->except('page'), ['page' => $pagination['current_page'] + 1])) }}">Next</a></li>
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
