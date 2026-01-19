@php
    $userRole = Auth::user()->role ?? 'executor';
    $layout = match ($userRole) {
        'provincial' => 'provincial.dashboard',
        'coordinator' => 'coordinator.dashboard',
        default => 'executor.dashboard',
    };
@endphp

@extends($layout)

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>My Activities</h4>
                    <p class="text-muted mb-0">Status changes for your projects and reports</p>
                </div>
                <div class="card-body">
                    {{-- Filters --}}
                    <form method="GET" action="{{ route('activities.my-activities') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="type" class="form-label">Type</label>
                                <select name="type" id="type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="project" {{ request('type') === 'project' ? 'selected' : '' }}>Projects</option>
                                    <option value="report" {{ request('type') === 'report' ? 'selected' : '' }}>Reports</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="submitted_to_provincial" {{ request('status') === 'submitted_to_provincial' ? 'selected' : '' }}>Submitted to Provincial</option>
                                    <option value="reverted_by_provincial" {{ request('status') === 'reverted_by_provincial' ? 'selected' : '' }}>Reverted by Provincial</option>
                                    <option value="forwarded_to_coordinator" {{ request('status') === 'forwarded_to_coordinator' ? 'selected' : '' }}>Forwarded to Coordinator</option>
                                    <option value="reverted_by_coordinator" {{ request('status') === 'reverted_by_coordinator' ? 'selected' : '' }}>Reverted by Coordinator</option>
                                    <option value="approved_by_coordinator" {{ request('status') === 'approved_by_coordinator' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected_by_coordinator" {{ request('status') === 'rejected_by_coordinator' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="{{ route('activities.my-activities') }}" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>

                    {{-- Activity Table --}}
                    @include('activity-history.partials.activity-table', ['activities' => $activities])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
