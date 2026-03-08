{{-- resources/views/coordinator/approvedProjects.blade.php --}}
@extends('coordinator.dashboard')

@section('content')
@php
    use App\Constants\ProjectStatus;
@endphp
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Approved Projects (Coordinator View)</h4>
                </div>
                <div class="card-body">
                    {{-- Filters --}}
                    <form method="GET" action="{{ route('coordinator.approved.projects') }}" id="filterForm">
                        <div class="mb-3 row">
                            <div class="col-md-2">
                                <label for="fy" class="form-label">Financial Year</label>
                                <select name="fy" id="fy" class="form-select auto-filter">
                                    @foreach($fyList ?? [] as $year)
                                        <option value="{{ $year }}" {{ ($fy ?? '') == $year ? 'selected' : '' }}>FY {{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="province" class="form-label">Province</label>
                                <select name="province" id="province" class="form-select auto-filter">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces ?? [] as $province)
                                        <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>{{ $province }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="project_type" class="form-label">Project Type</label>
                                <select name="project_type" id="project_type" class="form-select auto-filter">
                                    <option value="">All Types</option>
                                    @foreach($projectTypes ?? [] as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>{{ Str::limit($type, 25) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="center" class="form-label">Center</label>
                                <select name="center" id="center" class="form-select auto-filter">
                                    <option value="">All Centers</option>
                                    @foreach($centers ?? [] as $center)
                                        <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>{{ $center }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="user_id" class="form-label">Executor</label>
                                <select name="user_id" id="user_id" class="form-select auto-filter">
                                    <option value="">All Executors</option>
                                    @foreach($users ?? [] as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ ucfirst($user->role) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="{{ route('coordinator.approved.projects') }}" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>

                    {{-- Active Filters --}}
                    @if(request()->anyFilled(['fy', 'province', 'project_type', 'user_id', 'center']))
                    <div class="mb-3 alert alert-info">
                        <strong>Active Filters:</strong>
                        @if(request('fy'))
                            <span class="badge bg-info me-2">FY: {{ request('fy') }}</span>
                        @endif
                        @if(request('province'))
                            <span class="badge bg-primary me-2">Province: {{ request('province') }}</span>
                        @endif
                        @if(request('project_type'))
                            <span class="badge bg-success me-2">Type: {{ request('project_type') }}</span>
                        @endif
                        @if(request('center'))
                            <span class="badge bg-dark me-2">Center: {{ request('center') }}</span>
                        @endif
                        @if(request('user_id'))
                            @php $selectedUser = ($users ?? collect())->firstWhere('id', request('user_id')); @endphp
                            @if($selectedUser)
                                <span class="badge bg-secondary me-2">Executor: {{ $selectedUser->name }}</span>
                            @endif
                        @endif
                        <a href="{{ route('coordinator.approved.projects') }}" class="btn btn-sm btn-outline-secondary float-end">Clear All</a>
                    </div>
                    @endif

                    <style>
                        table.my-projects-table {
                            table-layout: fixed;
                            width: 100%;
                        }

                        table.my-projects-table th,
                        table.my-projects-table td {
                            vertical-align: middle;
                            text-align: center;
                            padding: 8px;
                            white-space: normal !important;
                            word-break: break-word;
                            overflow-wrap: break-word;
                        }

                        table.my-projects-table th.project-title,
                        table.my-projects-table td.project-title,
                        table.my-projects-table th.project-type,
                        table.my-projects-table td.project-type {
                            text-align: left;
                            width: 20%;
                        }

                        table.my-projects-table th.amount-sanctioned,
                        table.my-projects-table td.amount-sanctioned,
                        table.my-projects-table th.amount-forwarded,
                        table.my-projects-table td.amount-forwarded {
                            width: 10%;
                            text-align: center;
                        }
                    </style>

                    <div class="table-responsive">
                        <table class="table table-bordered my-projects-table">
                            <thead>
                                <tr>
                                    <th>Project ID</th>
                                    <th>Executor</th>
                                    <th class="project-title">Project Title</th>
                                    <th class="project-type">Project Type</th>
                                    <th class="amount-sanctioned">Amount Sanctioned</th>
                                    <th class="amount-forwarded">Amount Forwarded</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                    <tr>
                                        <td>{{ $project->project_id }}</td>
                                        <td>{{ $project->user->name ?? 'N/A' }}</td>
                                        <td class="project-title">{{ $project->project_title }}</td>
                                        <td class="project-type">{{ $project->project_type }}</td>
                                        <td class="amount-sanctioned">{{ format_indian((float) (($resolvedFinancials[$project->project_id] ?? [])['amount_sanctioned'] ?? 0), 2) }}</td>
                                        <td class="amount-forwarded">{{ format_indian($project->amount_forwarded ?? 0, 2) }}</td>
                                        <td>
                                            {{ \App\Models\OldProjects\Project::$statusLabels[$project->status] ?? $project->status }}
                                        </td>
                                        <td>
                                            <a href="{{ route('coordinator.projects.show', $project->project_id) }}" class="btn btn-primary btn-sm">View Project</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No approved projects found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($projects->hasPages())
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $projects->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var filterSubmitting = false;
    document.querySelectorAll('.auto-filter').forEach(function(el) {
        el.addEventListener('change', function() {
            if (filterSubmitting) return;
            filterSubmitting = true;
            this.closest('form').submit();
        });
    });
});
</script>
@endpush
@endsection
