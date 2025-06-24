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
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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
                            <div class="col-md-4 d-flex align-items-end">
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
@endsection
