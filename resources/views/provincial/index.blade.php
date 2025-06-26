@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Provincial Dashboard - Project Budgets Overview</h4>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" action="{{ route('provincial.dashboard') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="center" class="form-label">Center</label>
                                <select name="center" id="center" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Centers</option>
                                    @foreach($centers as $center)
                                        <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                            {{ $center }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="role" class="form-label">Role</label>
                                <select name="role" id="role" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Roles</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                            {{ ucfirst($role) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="project_type" class="form-label">Project Type</label>
                                <select name="project_type" id="project_type" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Project Types</option>
                                    @foreach($projectTypes as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="{{ route('provincial.dashboard') }}" class="btn btn-secondary">Clear Filters</a>
                            </div>
                        </div>
                    </form>

                    <!-- Active Filters Display -->
                    @if(request('center') || request('role') || request('project_type'))
                    <div class="alert alert-info mb-4">
                        <strong>Active Filters:</strong>
                        @if(request('center'))
                            <span class="badge badge-success me-2">Center: {{ request('center') }}</span>
                        @endif
                        @if(request('role'))
                            <span class="badge badge-warning me-2">Role: {{ ucfirst(request('role')) }}</span>
                        @endif
                        @if(request('project_type'))
                            <span class="badge badge-info me-2">Project Type: {{ request('project_type') }}</span>
                        @endif
                        <a href="{{ route('provincial.dashboard') }}" class="btn btn-sm btn-outline-secondary float-right">Clear All</a>
                    </div>
                    @endif

                    <!-- Budget Summary Cards -->
                    <div class="mb-4 row">
                        <div class="col-md-4">
                            <div class="text-white card bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Total Budget</h5>
                                    <h3 class="card-text">Rs. {{ number_format($budgetSummaries['total']['total_budget']) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-white card bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Total Expenses</h5>
                                    <h3 class="card-text">Rs. {{ number_format($budgetSummaries['total']['total_expenses']) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-white card bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">Remaining Budget</h5>
                                    <h3 class="card-text">Rs. {{ number_format($budgetSummaries['total']['total_remaining']) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Budget by Project Type -->
                    <div class="mb-4 row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Budget by Project Type</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Project Type</th>
                                                    <th>Budget</th>
                                                    <th>Expenses</th>
                                                    <th>Remaining</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($budgetSummaries['by_project_type'] as $type => $summary)
                                                <tr>
                                                    <td>{{ $type }}</td>
                                                    <td>Rs. {{ number_format($summary['total_budget']) }}</td>
                                                    <td>Rs. {{ number_format($summary['total_expenses']) }}</td>
                                                    <td>Rs. {{ number_format($summary['total_remaining']) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Budget by Center -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Budget by Center</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Center</th>
                                                    <th>Budget</th>
                                                    <th>Expenses</th>
                                                    <th>Remaining</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($budgetSummaries['by_center'] as $center => $summary)
                                                <tr>
                                                    <td>{{ $center }}</td>
                                                    <td>Rs. {{ number_format($summary['total_budget']) }}</td>
                                                    <td>Rs. {{ number_format($summary['total_expenses']) }}</td>
                                                    <td>Rs. {{ number_format($summary['total_remaining']) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Debug Information -->
                    {{-- @if(config('app.debug'))
                    <div class="alert alert-info">
                        <strong>Debug Info:</strong><br>
                        Selected Center: {{ request('center') ?: 'None' }}<br>
                        Selected Role: {{ request('role') ?: 'None' }}<br>
                        Selected Project Type: {{ request('project_type') ?: 'None' }}<br>
                        Available Centers: {{ $centers->count() }}<br>
                        Available Roles: {{ count($roles) }}
                    </div>
                    @endif --}}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Provincial dashboard loaded');

    // Debug form submission
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const formData = new FormData(form);
        console.log('Form submitting with data:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
    });

    // Debug current values for the comprehensive filtering system
    console.log('Current center:', '{{ request("center") }}');
    console.log('Current role:', '{{ request("role") }}');
    console.log('Current project_type:', '{{ request("project_type") }}');

    // Debug available options for the comprehensive filtering system
    console.log('Available centers:', @json($centers));
    console.log('Available roles:', @json($roles));
    console.log('Available project types:', @json($projectTypes));

    // Show current filter status
    const activeFilters = [];
    if ('{{ request("center") }}') activeFilters.push('Center: {{ request("center") }}');
    if ('{{ request("role") }}') activeFilters.push('Role: {{ request("role") }}');
    if ('{{ request("project_type") }}') activeFilters.push('Project Type: {{ request("project_type") }}');

    if (activeFilters.length > 0) {
        console.log('Active filters:', activeFilters.join(', '));
    } else {
        console.log('No active filters - showing all projects');
    }
});
</script>
@endsection
