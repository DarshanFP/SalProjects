@extends('coordinator.dashboard')

@section('content')
<div class="px-4 container-fluid" style="padding-top: 80px;">
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <h1 class="mb-4">Budget Report</h1>
        <div class="btn-group">
            <a href="{{ route('budgets.report', array_merge(request()->all(), ['format' => 'excel'])) }}" class="btn btn-success">
                <i class="feather icon-download"></i> Export to Excel
            </a>
            <a href="{{ route('budgets.report', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="btn btn-danger">
                <i class="feather icon-file-text"></i> Export to PDF
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('budgets.report') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="project_type" class="form-label">Project Type</label>
                    <select name="project_type" id="project_type" class="form-select">
                        <option value="">All Types</option>
                        @php
                            $projectTypes = \App\Models\OldProjects\Project::distinct()->pluck('project_type');
                        @endphp
                        @foreach($projectTypes as $type)
                            <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="submitted_to_provincial" {{ request('status') == 'submitted_to_provincial' ? 'selected' : '' }}>Submitted to Provincial</option>
                        <option value="forwarded_to_coordinator" {{ request('status') == 'forwarded_to_coordinator' ? 'selected' : '' }}>Forwarded to Coordinator</option>
                        <option value="approved_by_coordinator" {{ request('status') == 'approved_by_coordinator' ? 'selected' : '' }}>Approved by Coordinator</option>
                        <option value="reverted_by_coordinator" {{ request('status') == 'reverted_by_coordinator' ? 'selected' : '' }}>Reverted by Coordinator</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="feather icon-filter"></i> Apply Filters
                    </button>
                    <a href="{{ route('budgets.report') }}" class="btn btn-secondary">
                        <i class="feather icon-x"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Projects</h5>
                    <h3 class="card-text">{{ $reportData['summary']['total_projects'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Total Budget</h5>
                    <h3 class="card-text">{{ format_indian_currency($reportData['summary']['total_budget'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Total Expenses</h5>
                    <h3 class="card-text">{{ format_indian_currency($reportData['summary']['total_expenses'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Total Remaining</h5>
                    <h3 class="card-text">{{ format_indian_currency($reportData['summary']['total_remaining'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget vs Actual -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Budget vs Actual</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Project ID</th>
                            <th>Project Title</th>
                            <th>Project Type</th>
                            <th class="text-end">Budget (Rs.)</th>
                            <th class="text-end">Actual (Rs.)</th>
                            <th class="text-end">Variance (Rs.)</th>
                            <th class="text-end">Variance (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['budget_vs_actual'] as $item)
                            <tr>
                                <td>{{ $item['project_id'] }}</td>
                                <td>{{ $item['project_title'] }}</td>
                                <td>{{ $item['project_type'] }}</td>
                                <td class="text-end">{{ format_indian_currency($item['budget'], 2) }}</td>
                                <td class="text-end">{{ format_indian_currency($item['actual'], 2) }}</td>
                                <td class="text-end {{ $item['variance'] < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ format_indian_currency($item['variance'], 2) }}
                                </td>
                                <td class="text-end {{ $item['variance_percentage'] < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ format_indian_percentage($item['variance_percentage'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Expense Breakdown -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Expense Breakdown</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Project ID</th>
                            <th>Project Title</th>
                            <th>Project Type</th>
                            <th class="text-end">Total Expenses (Rs.)</th>
                            <th class="text-end">% of Budget</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['expense_breakdown'] as $item)
                            <tr>
                                <td>{{ $item['project_id'] }}</td>
                                <td>{{ $item['project_title'] }}</td>
                                <td>{{ $item['project_type'] }}</td>
                                <td class="text-end">{{ format_indian_currency($item['total_expenses'], 2) }}</td>
                                <td class="text-end">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar {{ $item['percentage_of_budget'] > 90 ? 'bg-danger' : ($item['percentage_of_budget'] > 70 ? 'bg-warning' : 'bg-success') }}"
                                             role="progressbar"
                                             style="width: {{ min(100, $item['percentage_of_budget']) }}%"
                                             aria-valuenow="{{ $item['percentage_of_budget'] }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            {{ format_indian_percentage($item['percentage_of_budget'], 1) }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Trend Analysis -->
    @if(!empty($reportData['trend_analysis']))
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Trend Analysis</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Total Expenses (Rs.)</th>
                                <th class="text-end">Number of Projects</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['trend_analysis'] as $item)
                                <tr>
                                    <td>{{ $item['month'] }}</td>
                                    <td class="text-end">{{ format_indian_currency($item['total_expenses'], 2) }}</td>
                                    <td class="text-end">{{ $item['project_count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
