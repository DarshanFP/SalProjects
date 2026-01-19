{{-- Project Budgets Overview Widget - Dark Theme Compatible --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center position-relative">
        <h5 class="mb-0">
            <i data-feather="dollar-sign" class="me-2"></i>
            Project Budgets Overview
        </h5>
        <div class="d-flex align-items-center gap-2">
            <div class="widget-drag-handle ms-2">
                <i data-feather="move" style="width: 16px; height: 16px;" class="text-muted"></i>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <div class="mb-4">
            <form method="GET" action="{{ route('executor.dashboard') }}" class="row g-3">
                @if(request('show'))
                    <input type="hidden" name="show" value="{{ request('show') }}">
                @endif
                <div class="col-md-4">
                    <label for="project_type_overview" class="form-label">Project Type</label>
                    <select name="project_type" id="project_type_overview" class="form-select">
                        <option value="">All Project Types</option>
                        @foreach($projectTypes as $type)
                            <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i data-feather="filter" style="width: 14px; height: 14px;"></i>
                        Apply Filters
                    </button>
                    <a href="{{ route('executor.dashboard') }}" class="btn btn-secondary">
                        <i data-feather="refresh-cw" style="width: 14px; height: 14px;"></i>
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Total Summary -->
        <div class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-primary bg-opacity-25 border-primary h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Total Budget</small>
                                    <h4 class="mb-0 text-white">{{ format_indian_currency($budgetSummaries['total']['total_budget'], 2) }}</h4>
                                </div>
                                <div class="text-primary">
                                    <i data-feather="dollar-sign" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success bg-opacity-25 border-success h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Approved Expenses</small>
                                    <h4 class="mb-0 text-white">{{ format_indian_currency($budgetSummaries['total']['approved_expenses'] ?? 0, 2) }}</h4>
                                    <small class="text-muted">Coordinator approved</small>
                                </div>
                                <div class="text-success">
                                    <i data-feather="check-circle" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning bg-opacity-25 border-warning h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Unapproved Expenses</small>
                                    <h4 class="mb-0 text-white">{{ format_indian_currency($budgetSummaries['total']['unapproved_expenses'] ?? 0, 2) }}</h4>
                                    <small class="text-muted">Pending approval</small>
                                </div>
                                <div class="text-warning">
                                    <i data-feather="clock" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info bg-opacity-25 border-info h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Total Remaining</small>
                                    <h4 class="mb-0 text-white">{{ format_indian_currency($budgetSummaries['total']['total_remaining'], 2) }}</h4>
                                    <small class="text-muted">Based on approved expenses</small>
                                </div>
                                <div class="text-info">
                                    <i data-feather="trending-up" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Budget Utilization Progress Bar --}}
            @php
                $totalBudget = $budgetSummaries['total']['total_budget'];
                $approvedExpenses = $budgetSummaries['total']['approved_expenses'] ?? 0;
                $unapprovedExpenses = $budgetSummaries['total']['unapproved_expenses'] ?? 0;
                $totalRemaining = $budgetSummaries['total']['total_remaining'];

                // Calculate percentages based on total budget
                // Remaining budget = total_budget - approved_expenses (unapproved don't reduce available budget)
                $approvedPercent = $totalBudget > 0 ? ($approvedExpenses / $totalBudget) * 100 : 0;
                $unapprovedPercent = $totalBudget > 0 ? ($unapprovedExpenses / $totalBudget) * 100 : 0;
                $remainingPercent = $totalBudget > 0 ? ($totalRemaining / $totalBudget) * 100 : 100;

                // Ensure percentages are valid (remaining should equal 100 - approved)
                $remainingPercent = max(0, min(100, 100 - $approvedPercent));
            @endphp
            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">
                        <i data-feather="trending-up" style="width: 14px; height: 14px;" class="me-1"></i>
                        Budget Utilization (Based on Approved Expenses)
                    </small>
                    <small class="text-muted">
                        <span class="text-success">
                            <i data-feather="check-circle" style="width: 12px; height: 12px;" class="me-1"></i>
                            Approved: {{ format_indian_percentage($approvedPercent, 1) }}
                        </span> |
                        <span class="text-warning">
                            <i data-feather="clock" style="width: 12px; height: 12px;" class="me-1"></i>
                            Pending: {{ format_indian_percentage($unapprovedPercent, 1) }}
                        </span> |
                        <span class="text-info">
                            <i data-feather="trending-up" style="width: 12px; height: 12px;" class="me-1"></i>
                            Remaining: {{ format_indian_percentage($remainingPercent, 1) }}
                        </span>
                    </small>
                </div>
                {{-- Main Progress Bar: Approved vs Remaining (unapproved shown separately) --}}
                <div class="progress mb-2" style="height: 30px; border: 1px solid rgba(255,255,255,0.15);">
                    @if($approvedPercent > 0)
                        <div class="progress-bar bg-success"
                             style="width: {{ $approvedPercent }}%"
                             role="progressbar"
                             aria-valuenow="{{ $approvedPercent }}"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             title="Approved Expenses: {{ format_indian_currency($approvedExpenses, 2) }} ({{ format_indian_percentage($approvedPercent, 1) }} of total budget) - Reduces remaining budget">
                            @if($approvedPercent > 8)
                                <strong class="text-white">{{ format_indian_percentage($approvedPercent, 1) }} Approved</strong>
                            @elseif($approvedPercent > 0)
                                <span class="text-white">✓</span>
                            @endif
                        </div>
                    @endif
                    @if($remainingPercent > 0)
                        <div class="progress-bar bg-info"
                             style="width: {{ $remainingPercent }}%"
                             role="progressbar"
                             aria-valuenow="{{ $remainingPercent }}"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             title="Remaining Budget: {{ format_indian_currency($totalRemaining, 2) }} ({{ format_indian_percentage($remainingPercent, 1) }} of total budget) - Available after approved expenses">
                            @if($remainingPercent > 8)
                                <strong class="text-white">{{ format_indian_percentage($remainingPercent, 1) }} Remaining</strong>
                            @endif
                        </div>
                    @endif
                </div>
                {{-- Separate indicator for unapproved expenses (doesn't reduce remaining budget) --}}
                @if($unapprovedPercent > 0)
                    <div class="alert alert-warning alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i data-feather="clock" style="width: 16px; height: 16px;" class="me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Unapproved Expenses:</strong> {{ format_indian_currency($unapprovedExpenses, 2) }}
                                ({{ format_indian_percentage($unapprovedPercent, 1) }} of total budget) -
                                <span class="text-muted">These expenses are pending coordinator approval and do not reduce remaining budget until approved.</span>
                            </div>
                        </div>
                    </div>
                @endif
                <small class="text-muted mt-2 d-block">
                    <i data-feather="info" style="width: 12px; height: 12px;" class="me-1"></i>
                    <strong>Note:</strong> Remaining budget is calculated using approved expenses only. Unapproved expenses are shown above separately and do not reduce available budget until approved by coordinator.
                </small>
            </div>
        </div>

        <!-- By Project Type -->
        <div class="mb-4">
            <h6 class="mb-3">
                <i data-feather="pie-chart" class="me-1" style="width: 16px; height: 16px;"></i>
                Budget Summary by Project Type
            </h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Project Type</th>
                            <th>Total Budget</th>
                            <th>Approved Expenses</th>
                            <th>Unapproved Expenses</th>
                            <th>Total Expenses</th>
                            <th>Remaining Budget</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($budgetSummaries['by_project_type'] as $type => $summary)
                        @php
                            $typeApprovedPercent = $summary['total_budget'] > 0 ? ($summary['approved_expenses'] ?? 0) / $summary['total_budget'] * 100 : 0;
                            $typeUnapprovedPercent = $summary['total_budget'] > 0 ? ($summary['unapproved_expenses'] ?? 0) / $summary['total_budget'] * 100 : 0;
                        @endphp
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $type }}</span>
                            </td>
                            <td>{{ format_indian_currency($summary['total_budget'], 2) }}</td>
                            <td>
                                <span class="text-success fw-bold">{{ format_indian_currency($summary['approved_expenses'] ?? 0, 2) }}</span>
                                @if($summary['total_budget'] > 0)
                                    <br><small class="text-muted">({{ format_indian_percentage($typeApprovedPercent, 1) }})</small>
                                @endif
                            </td>
                            <td>
                                <span class="text-warning fw-bold">{{ format_indian_currency($summary['unapproved_expenses'] ?? 0, 2) }}</span>
                                @if($summary['total_budget'] > 0)
                                    <br><small class="text-muted">({{ format_indian_percentage($typeUnapprovedPercent, 1) }})</small>
                                @endif
                            </td>
                            <td>{{ format_indian_currency($summary['total_expenses'] ?? 0, 2) }}</td>
                            <td>
                                <span class="text-info fw-bold">{{ format_indian_currency($summary['total_remaining'], 2) }}</span>
                                <br><small class="text-muted">Based on approved</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
