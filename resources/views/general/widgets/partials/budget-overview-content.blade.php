@php
    $total = $budgetData['total'] ?? [
        'budget' => 0,
        'approved_expenses' => 0,
        'unapproved_expenses' => 0,
        'remaining' => 0,
        'utilization' => 0
    ];

    $byProjectType = $budgetData['by_project_type'] ?? [];
    $byProvince = $budgetData['by_province'] ?? [];
    $byCenter = $budgetData['by_center'] ?? [];
    $byCoordinator = $budgetData['by_coordinator'] ?? [];

    $totalBudget = $total['budget'] ?? 0;
    $approvedExpenses = $total['approved_expenses'] ?? 0;
    $unapprovedExpenses = $total['unapproved_expenses'] ?? 0;
    $totalRemaining = $total['remaining'] ?? 0;

    $approvedPercent = $totalBudget > 0 ? ($approvedExpenses / $totalBudget) * 100 : 0;
    $unapprovedPercent = $totalBudget > 0 ? ($unapprovedExpenses / $totalBudget) * 100 : 0;
    $remainingPercent = max(0, min(100, 100 - $approvedPercent));
@endphp

{{-- Filter Form (Always Visible) --}}
<form method="GET" action="{{ route('general.dashboard') }}" class="mb-4">
    <input type="hidden" name="budget_context" value="{{ $context }}">
    <div class="row">
        @if($context === 'coordinator_hierarchy' || $context === 'combined')
            <div class="col-md-3">
                <label for="budget_filter_province" class="form-label">Province</label>
                <select name="province" id="budget_filter_province" class="form-select form-select-sm">
                    <option value="">All Provinces</option>
                    @if(isset($provinces))
                        @foreach($provinces as $province)
                            <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                {{ $province }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
            @if($context === 'coordinator_hierarchy')
                <div class="col-md-3">
                    <label for="budget_filter_coordinator" class="form-label">Coordinator</label>
                    <select name="coordinator_id" id="budget_filter_coordinator" class="form-select form-select-sm">
                        <option value="">All Coordinators</option>
                        @if(isset($coordinators))
                            @foreach($coordinators as $coordinator)
                                <option value="{{ $coordinator->id }}" {{ request('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                    {{ $coordinator->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            @endif
        @endif
        @if($context === 'direct_team' || $context === 'combined')
            <div class="col-md-3">
                <label for="budget_filter_center" class="form-label">Center</label>
                <select name="center" id="budget_filter_center" class="form-select form-select-sm">
                    <option value="">All Centers</option>
                    @if(isset($centers))
                        @foreach($centers as $center)
                            <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                {{ $center }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
        @endif
        <div class="col-md-3">
            <label for="budget_filter_project_type" class="form-label">Project Type</label>
            <select name="project_type" id="budget_filter_project_type" class="form-select form-select-sm">
                <option value="">All Project Types</option>
                @if(isset($projectTypes))
                    @foreach($projectTypes as $type)
                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
            <a href="{{ route('general.dashboard') }}" class="btn btn-secondary btn-sm">Reset</a>
        </div>
    </div>
</form>

{{-- Active Filters Display --}}
@if(request('province') || request('center') || request('project_type') || request('coordinator_id'))
    <div class="alert alert-info mb-4">
        <strong>Active Filters:</strong>
        @if(request('province'))
            <span class="badge bg-success me-2">Province: {{ request('province') }}</span>
        @endif
        @if(request('center'))
            <span class="badge bg-success me-2">Center: {{ request('center') }}</span>
        @endif
        @if(request('project_type'))
            <span class="badge bg-info me-2">Project Type: {{ request('project_type') }}</span>
        @endif
        @if(request('coordinator_id') && isset($coordinators))
            @php
                $selectedCoordinator = $coordinators->firstWhere('id', request('coordinator_id'));
            @endphp
            @if($selectedCoordinator)
                <span class="badge bg-warning me-2">Coordinator: {{ $selectedCoordinator->name }}</span>
            @endif
        @endif
        <a href="{{ route('general.dashboard') }}" class="btn btn-sm btn-outline-secondary float-end">Clear All</a>
    </div>
@endif

{{-- Data Display Section --}}
@if($totalBudget == 0)
    {{-- Empty State --}}
    <div class="text-center py-5">
        <h5 class="text-muted">No Budget Data Available</h5>
        <p class="text-muted">
            @if(request('province') || request('center') || request('project_type') || request('coordinator_id'))
                No approved projects with budget information match the selected filters for {{ $contextLabel }}. Try adjusting your filters or clear them to see all data.
            @else
                There are no approved projects with budget information yet for {{ $contextLabel }}.
            @endif
        </p>
    </div>
@else
    {{-- Summary Cards (4 Cards) --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-primary bg-opacity-25 border-primary h-100">
                <div class="card-body p-3">
                    <small class="text-muted d-block">Total Budget</small>
                    <h4 class="mb-0 text-white">{{ format_indian_currency($totalBudget, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-success bg-opacity-25 border-success h-100">
                <div class="card-body p-3">
                    <small class="text-muted d-block">Approved Expenses</small>
                    <h4 class="mb-0 text-white">{{ format_indian_currency($approvedExpenses, 2) }}</h4>
                    <small class="text-muted">
                        {{ $context === 'coordinator_hierarchy' ? 'Coordinator approved' : 'Provincial approved' }}
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-warning bg-opacity-25 border-warning h-100">
                <div class="card-body p-3">
                    <small class="text-muted d-block">Unapproved Expenses</small>
                    <h4 class="mb-0 text-white">{{ format_indian_currency($unapprovedExpenses, 2) }}</h4>
                    <small class="text-muted">In pipeline / Pending approval</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-info bg-opacity-25 border-info h-100">
                <div class="card-body p-3">
                    <small class="text-muted d-block">Total Remaining</small>
                    <h4 class="mb-0 text-white">{{ format_indian_currency($totalRemaining, 2) }}</h4>
                    <small class="text-muted">{{ format_indian_percentage($remainingPercent, 1) }} remaining</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Budget Utilization Progress Bar --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Budget Utilization</h6>
            <small class="text-muted">{{ format_indian_percentage($total['utilization'] ?? 0, 1) }} utilized</small>
        </div>
        <div class="progress" style="height: 30px;">
            <div class="progress-bar bg-success" role="progressbar"
                 style="width: {{ $approvedPercent }}%"
                 aria-valuenow="{{ $approvedPercent }}"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 title="Approved Expenses: {{ format_indian_currency($approvedExpenses, 2) }}">
                {{ format_indian_percentage($approvedPercent, 1) }}
            </div>
            @if($unapprovedPercent > 0)
                <div class="progress-bar bg-warning" role="progressbar"
                     style="width: {{ $unapprovedPercent }}%"
                     aria-valuenow="{{ $unapprovedPercent }}"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     title="Unapproved Expenses: {{ format_indian_currency($unapprovedExpenses, 2) }}">
                    {{ format_indian_percentage($unapprovedPercent, 1) }}
                </div>
            @endif
        </div>
        <div class="mt-2">
            <small>
                <span class="badge bg-success">Approved: {{ format_indian_currency($approvedExpenses, 2) }}</span>
                @if($unapprovedExpenses > 0)
                    <span class="badge bg-warning ms-2">Unapproved: {{ format_indian_currency($unapprovedExpenses, 2) }}</span>
                @endif
                <span class="badge bg-info ms-2">Remaining: {{ format_indian_currency($totalRemaining, 2) }}</span>
            </small>
        </div>
    </div>

    {{-- Budget Breakdown Tables --}}
    <div class="row">
        {{-- By Project Type --}}
        <div class="col-md-12 mb-4">
            <h6 class="mb-3">Budget by Project Type</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Project Type</th>
                            <th class="text-end">Total Budget</th>
                            <th class="text-end">Approved Expenses</th>
                            <th class="text-end">Unapproved Expenses</th>
                            <th class="text-end">Remaining</th>
                            <th class="text-center">Utilization</th>
                            <th class="text-center">Projects</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byProjectType as $type => $data)
                            <tr>
                                <td><strong>{{ $type }}</strong></td>
                                <td class="text-end">{{ format_indian_currency($data['budget'] ?? 0, 2) }}</td>
                                <td class="text-end text-success">{{ format_indian_currency($data['approved_expenses'] ?? 0, 2) }}</td>
                                <td class="text-end text-warning">{{ format_indian_currency($data['unapproved_expenses'] ?? 0, 2) }}</td>
                                <td class="text-end text-info">{{ format_indian_currency($data['remaining'] ?? 0, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ ($data['utilization'] ?? 0) > 80 ? 'danger' : (($data['utilization'] ?? 0) > 60 ? 'warning' : 'success') }}">
                                        {{ format_indian_percentage($data['utilization'] ?? 0, 1) }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $data['projects_count'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">No data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- By Province (Coordinator Hierarchy) or By Center (Direct Team) --}}
        @if(($context === 'coordinator_hierarchy' && !empty($byProvince)) || ($context === 'direct_team' && !empty($byCenter)) || ($context === 'combined'))
            <div class="col-md-6 mb-4">
                @if($context === 'coordinator_hierarchy' || $context === 'combined')
                    <h6 class="mb-3">Budget by Province (Coordinator Hierarchy)</h6>
                    @if(!empty($byProvince))
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Province</th>
                                        <th class="text-end">Budget</th>
                                        <th class="text-end">Expenses</th>
                                        <th class="text-end">Remaining</th>
                                        <th class="text-center">Utilization</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($byProvince as $province => $data)
                                        <tr>
                                            <td><strong>{{ $province }}</strong></td>
                                            <td class="text-end">{{ format_indian_currency($data['budget'] ?? 0, 2) }}</td>
                                            <td class="text-end">{{ format_indian_currency($data['approved_expenses'] ?? 0, 2) }}</td>
                                            <td class="text-end">{{ format_indian_currency($data['remaining'] ?? 0, 2) }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ ($data['utilization'] ?? 0) > 80 ? 'danger' : (($data['utilization'] ?? 0) > 60 ? 'warning' : 'success') }}">
                                                    {{ number_format($data['utilization'] ?? 0, 1) }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No province data available</p>
                    @endif
                @elseif($context === 'direct_team')
                    <h6 class="mb-3">Budget by Center</h6>
                    @if(!empty($byCenter))
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Center</th>
                                        <th class="text-end">Budget</th>
                                        <th class="text-end">Expenses</th>
                                        <th class="text-end">Remaining</th>
                                        <th class="text-center">Utilization</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($byCenter as $center => $data)
                                        <tr>
                                            <td><strong>{{ $center }}</strong></td>
                                            <td class="text-end">{{ format_indian_currency($data['budget'] ?? 0, 2) }}</td>
                                            <td class="text-end">{{ format_indian_currency($data['approved_expenses'] ?? 0, 2) }}</td>
                                            <td class="text-end">{{ format_indian_currency($data['remaining'] ?? 0, 2) }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ ($data['utilization'] ?? 0) > 80 ? 'danger' : (($data['utilization'] ?? 0) > 60 ? 'warning' : 'success') }}">
                                                    {{ number_format($data['utilization'] ?? 0, 1) }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No center data available</p>
                    @endif
                @endif
            </div>
        @endif

        {{-- By Coordinator (Coordinator Hierarchy only) --}}
        @if($context === 'coordinator_hierarchy' && !empty($byCoordinator))
            <div class="col-md-6 mb-4">
                <h6 class="mb-3">Budget by Coordinator</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Coordinator</th>
                                <th class="text-end">Budget</th>
                                <th class="text-end">Expenses</th>
                                <th class="text-end">Remaining</th>
                                <th class="text-center">Utilization</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byCoordinator as $coordinatorName => $data)
                                <tr>
                                    <td><strong>{{ $coordinatorName }}</strong><br><small class="text-muted">{{ $data['province'] ?? 'N/A' }}</small></td>
                                    <td class="text-end">{{ format_indian_currency($data['budget'] ?? 0, 2) }}</td>
                                    <td class="text-end">{{ format_indian_currency($data['approved_expenses'] ?? 0, 2) }}</td>
                                    <td class="text-end">{{ format_indian_currency($data['remaining'] ?? 0, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ ($data['utilization'] ?? 0) > 80 ? 'danger' : (($data['utilization'] ?? 0) > 60 ? 'warning' : 'success') }}">
                                            {{ number_format($data['utilization'] ?? 0, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endif
