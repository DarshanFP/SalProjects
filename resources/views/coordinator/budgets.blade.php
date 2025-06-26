@extends('coordinator.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Project Budgets Overview</h4>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="mb-4">
                        <form method="GET" action="{{ route('coordinator.dashboard') }}" class="row g-3">
                            <div class="col-md-3">
                                <label for="province" class="form-label">Province</label>
                                <select name="province" id="province" class="form-select">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="place" class="form-label">Center</label>
                                <select name="place" id="place" class="form-select">
                                    <option value="">All Centers</option>
                                    @foreach($places as $place)
                                        <option value="{{ $place }}" {{ request('place') == $place ? 'selected' : '' }}>
                                            {{ $place }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="user_id" class="form-label">Executor</label>
                                <select name="user_id" id="user_id" class="form-select">
                                    <option value="">All Executors</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="project_type" class="form-label">Project Type</label>
                                <select name="project_type" id="project_type" class="form-select">
                                    <option value="">All Project Types</option>
                                    @foreach($projectTypes as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                                <a href="{{ route('coordinator.dashboard') }}" class="btn btn-secondary">Reset</a>
                            </div>
                        </form>
                    </div>

                    <!-- Total Summary -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-white card bg-primary">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Budget</h5>
                                        <h3 class="card-text">₱{{ number_format($budgetSummaries['total']['total_budget'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-white card bg-success">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Expenses</h5>
                                        <h3 class="card-text">₱{{ number_format($budgetSummaries['total']['total_expenses'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-white card bg-info">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Remaining</h5>
                                        <h3 class="card-text">₱{{ number_format($budgetSummaries['total']['total_remaining'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- By Project Type -->
                    <div class="mb-4">
                        <h5 class="mb-3 card-title">Budget Summary by Project Type</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Project Type</th>
                                        <th>Total Budget</th>
                                        <th>Total Expenses</th>
                                        <th>Remaining Budget</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($budgetSummaries['by_project_type'] as $type => $summary)
                                    <tr>
                                        <td>{{ $type }}</td>
                                        <td>₱{{ number_format($summary['total_budget'], 2) }}</td>
                                        <td>₱{{ number_format($summary['total_expenses'], 2) }}</td>
                                        <td>₱{{ number_format($summary['total_remaining'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- By Province -->
                    <div class="mb-4">
                        <h5 class="mb-3 card-title">Budget Summary by Province</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Province</th>
                                        <th>Total Budget</th>
                                        <th>Total Expenses</th>
                                        <th>Remaining Budget</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($budgetSummaries['by_province'] as $province => $summary)
                                    <tr>
                                        <td>{{ $province }}</td>
                                        <td>₱{{ number_format($summary['total_budget'], 2) }}</td>
                                        <td>₱{{ number_format($summary['total_expenses'], 2) }}</td>
                                        <td>₱{{ number_format($summary['total_remaining'], 2) }}</td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const provinceSelect = document.getElementById('province');
    const centerSelect = document.getElementById('place');
    const executorSelect = document.getElementById('user_id');

    // Function to update centers based on selected province
    function updateCenters() {
        const selectedProvince = provinceSelect.value;

        // Clear current centers
        centerSelect.innerHTML = '<option value="">All Centers</option>';

        if (selectedProvince) {
            fetch(`/coordinator/centers-by-province?province=${encodeURIComponent(selectedProvince)}`)
                .then(response => response.json())
                .then(centers => {
                    centers.forEach(center => {
                        const option = document.createElement('option');
                        option.value = center;
                        option.textContent = center;
                        centerSelect.appendChild(option);
                    });

                    // Restore selected value if it exists
                    const currentPlace = '{{ request("place") }}';
                    if (currentPlace) {
                        centerSelect.value = currentPlace;
                    }
                })
                .catch(error => {
                    console.error('Error fetching centers:', error);
                });
        }
    }

    // Function to update executors based on selected province
    function updateExecutors() {
        const selectedProvince = provinceSelect.value;

        // Clear current executors
        executorSelect.innerHTML = '<option value="">All Executors</option>';

        if (selectedProvince) {
            fetch(`/coordinator/executors-by-province?province=${encodeURIComponent(selectedProvince)}`)
                .then(response => response.json())
                .then(executors => {
                    executors.forEach(executor => {
                        const option = document.createElement('option');
                        option.value = executor.id;
                        option.textContent = executor.name;
                        executorSelect.appendChild(option);
                    });

                    // Restore selected value if it exists
                    const currentUserId = '{{ request("user_id") }}';
                    if (currentUserId) {
                        executorSelect.value = currentUserId;
                    }
                })
                .catch(error => {
                    console.error('Error fetching executors:', error);
                });
        }
    }

    // Event listeners
    provinceSelect.addEventListener('change', function() {
        updateCenters();
        updateExecutors();
    });

    // Run on page load if province is already selected
    if (provinceSelect.value) {
        updateCenters();
        updateExecutors();
    }
});
</script>

@endsection
