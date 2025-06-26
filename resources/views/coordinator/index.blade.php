@extends('coordinator.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Coordinator Dashboard - Project Budgets Overview</h4>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" action="{{ route('coordinator.dashboard') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="province" class="form-label">Province</label>
                                <select name="province" id="province" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
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
                            <div class="col-md-2">
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
                                <label for="parent_id" class="form-label">Parent (Provincial)</label>
                                <select name="parent_id" id="parent_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Parents</option>
                                    @foreach($parents as $parent)
                                        <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->name }} ({{ $parent->province }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="{{ route('coordinator.dashboard') }}" class="btn btn-secondary">Clear Filters</a>
                            </div>
                        </div>
                    </form>

                    <!-- Active Filters Display -->
                    @if(request('province') || request('center') || request('role') || request('parent_id'))
                    <div class="alert alert-info mb-4">
                        <strong>Active Filters:</strong>
                        @if(request('province'))
                            <span class="badge badge-primary me-2">Province: {{ request('province') }}</span>
                        @endif
                        @if(request('center'))
                            <span class="badge badge-success me-2">Center: {{ request('center') }}</span>
                        @endif
                        @if(request('role'))
                            <span class="badge badge-warning me-2">Role: {{ ucfirst(request('role')) }}</span>
                        @endif
                        @if(request('parent_id'))
                            @php
                                $selectedParent = $parents->firstWhere('id', request('parent_id'));
                            @endphp
                            @if($selectedParent)
                                <span class="badge badge-info me-2">Parent: {{ $selectedParent->name }}</span>
                            @endif
                        @endif
                        <a href="{{ route('coordinator.dashboard') }}" class="btn btn-sm btn-outline-secondary float-right">Clear All</a>
                    </div>
                    @endif

                    <!-- Debug Information -->
                    {{-- @if(config('app.debug'))
                    <div class="alert alert-info">
                        <strong>Debug Info:</strong><br>
                        Selected Province: {{ request('province') ?: 'None' }}<br>
                        Selected Center: {{ request('center') ?: 'None' }}<br>
                        Selected Role: {{ request('role') ?: 'None' }}<br>
                        Selected Parent: {{ request('parent_id') ?: 'None' }}<br>
                        Available Provinces: {{ $provinces->count() }}<br>
                        Available Centers: {{ $centers->count() }}<br>
                        Available Parents: {{ $parents->count() }}
                    </div>
                    @endif --}}

                    <!-- Budget Summary Cards -->
                    <div class="row mb-4">
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
                    <div class="row mb-4">
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

                        <!-- Budget by Province -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Budget by Province</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Province</th>
                                                    <th>Budget</th>
                                                    <th>Expenses</th>
                                                    <th>Remaining</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($budgetSummaries['by_province'] as $province => $summary)
                                                <tr>
                                                    <td>{{ $province }}</td>
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
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Coordinator dashboard loaded');

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
    console.log('Current province:', '{{ request("province") }}');
    console.log('Current center:', '{{ request("center") }}');
    console.log('Current role:', '{{ request("role") }}');
    console.log('Current parent_id:', '{{ request("parent_id") }}');

    // Debug available options for the comprehensive filtering system
    console.log('Available provinces:', @json($provinces));
    console.log('Available centers:', @json($centers));
    console.log('Available roles:', @json($roles));
    console.log('Available parents:', @json($parents));

    // Show current filter status
    const activeFilters = [];
    if ('{{ request("province") }}') activeFilters.push('Province: {{ request("province") }}');
    if ('{{ request("center") }}') activeFilters.push('Center: {{ request("center") }}');
    if ('{{ request("role") }}') activeFilters.push('Role: {{ request("role") }}');
    if ('{{ request("parent_id") }}') activeFilters.push('Parent: {{ request("parent_id") }}');

    if (activeFilters.length > 0) {
        console.log('Active filters:', activeFilters.join(', '));
    } else {
        console.log('No active filters - showing all projects');
    }
});
</script>

@endsection
