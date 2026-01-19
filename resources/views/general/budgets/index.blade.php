@extends('general.dashboard')

@section('content')
@php
    use App\Constants\ProjectStatus;
@endphp
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 fp-text-center1">
                        <i data-feather="dollar-sign" class="me-2"></i>Project Budgets List
                    </h4>
                    <div class="btn-group">
                        <a href="{{ route('budgets.report', array_merge(request()->all(), ['format' => 'excel'])) }}" class="btn btn-sm btn-success" title="Export to Excel">
                            <i data-feather="download" style="width: 14px; height: 14px;"></i> Excel
                        </a>
                        <a href="{{ route('budgets.report', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="btn btn-sm btn-danger" title="Export to PDF">
                            <i data-feather="file-text" style="width: 14px; height: 14px;"></i> PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Success/Error Messages --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {!! session('success') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Summary Cards --}}
                    @if(isset($summary))
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card bg-primary bg-opacity-25 border-primary h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <small class="text-muted d-block">Total Projects</small>
                                            <h4 class="mb-0 text-white">{{ $summary['total_projects'] }}</h4>
                                        </div>
                                        <div class="text-primary">
                                            <i data-feather="briefcase" style="width: 32px; height: 32px;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card bg-success bg-opacity-25 border-success h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <small class="text-muted d-block">Total Budget</small>
                                            <h4 class="mb-0 text-white">{{ format_indian_currency($summary['total_budget'], 2) }}</h4>
                                        </div>
                                        <div class="text-success">
                                            <i data-feather="dollar-sign" style="width: 32px; height: 32px;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card bg-info bg-opacity-25 border-info h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <small class="text-muted d-block">Total Expenses</small>
                                            <h4 class="mb-0 text-white">{{ format_indian_currency($summary['total_expenses'], 2) }}</h4>
                                            @if($summary['total_unapproved_expenses'] > 0)
                                                <small class="text-muted">(+ {{ format_indian_currency($summary['total_unapproved_expenses'], 2) }} pending)</small>
                                            @endif
                                        </div>
                                        <div class="text-info">
                                            <i data-feather="trending-up" style="width: 32px; height: 32px;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card bg-warning bg-opacity-25 border-warning h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <small class="text-muted d-block">Remaining Budget</small>
                                            <h4 class="mb-0 text-white">{{ format_indian_currency($summary['total_remaining'], 2) }}</h4>
                                            <small class="text-muted">Avg Utilization: {{ format_indian_percentage($summary['avg_utilization'] ?? 0, 1) }}</small>
                                        </div>
                                        <div class="text-warning">
                                            <i data-feather="trending-down" style="width: 32px; height: 32px;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Filters --}}
                    <form method="GET" action="{{ route('general.budgets') }}" id="filterForm">
                        <input type="hidden" name="budget_context" value="{{ $context ?? 'combined' }}">
                        <div class="mb-3 row">
                            <div class="col-md-2">
                                <label for="budget_context_filter" class="form-label">Context</label>
                                <select name="budget_context" id="budget_context_filter" class="form-select form-select-sm">
                                    <option value="combined" {{ ($context ?? 'combined') === 'combined' ? 'selected' : '' }}>Combined</option>
                                    <option value="coordinator_hierarchy" {{ ($context ?? '') === 'coordinator_hierarchy' ? 'selected' : '' }}>Coordinator Hierarchy</option>
                                    <option value="direct_team" {{ ($context ?? '') === 'direct_team' ? 'selected' : '' }}>Direct Team</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control form-control-sm"
                                       placeholder="Project ID, Title, Type..."
                                       value="{{ request('search') }}">
                            </div>
                            @if(($context ?? 'combined') === 'coordinator_hierarchy' || ($context ?? 'combined') === 'combined')
                                <div class="col-md-2">
                                    <label for="coordinator_id" class="form-label">Coordinator</label>
                                    <select name="coordinator_id" id="coordinator_id" class="form-select form-select-sm">
                                        <option value="">All Coordinators</option>
                                        @foreach($coordinators ?? [] as $coordinator)
                                            <option value="{{ $coordinator->id }}" {{ request('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                                {{ $coordinator->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-2">
                                <label for="province" class="form-label">Province</label>
                                <select name="province" id="province" class="form-select form-select-sm">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces ?? [] as $province)
                                        <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if(($context ?? 'combined') === 'direct_team' || ($context ?? 'combined') === 'combined')
                                <div class="col-md-2">
                                    <label for="center" class="form-label">Center</label>
                                    <select name="center" id="center" class="form-select form-select-sm">
                                        <option value="">All Centers</option>
                                        @foreach($centers ?? [] as $center)
                                            <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                                {{ $center }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-2">
                                <label for="project_type" class="form-label">Project Type</label>
                                <select name="project_type" id="project_type" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    @foreach($projectTypes ?? [] as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                            {{ Str::limit($type, 20) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="sort_by" class="form-label">Sort By</label>
                                <select name="sort_by" id="sort_by" class="form-select form-select-sm">
                                    <option value="project_id" {{ request('sort_by', 'project_id') == 'project_id' ? 'selected' : '' }}>Project ID</option>
                                    <option value="calculated_budget" {{ request('sort_by') == 'calculated_budget' ? 'selected' : '' }}>Budget</option>
                                    <option value="calculated_expenses" {{ request('sort_by') == 'calculated_expenses' ? 'selected' : '' }}>Expenses</option>
                                    <option value="calculated_utilization" {{ request('sort_by') == 'calculated_utilization' ? 'selected' : '' }}>Utilization</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label for="sort_order" class="form-label">Order</label>
                                <select name="sort_order" id="sort_order" class="form-select form-select-sm">
                                    <option value="asc" {{ request('sort_order', 'asc') == 'asc' ? 'selected' : '' }}>Asc</option>
                                    <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Desc</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                    <a href="{{ route('general.budgets') }}" class="btn btn-secondary btn-sm">Reset</a>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Active Filters Display --}}
                    @if(request()->anyFilled(['search', 'coordinator_id', 'province', 'project_type', 'center', 'budget_context']) || (request('budget_context') && request('budget_context') !== 'combined'))
                    <div class="mb-3 alert alert-info">
                        <strong>Active Filters:</strong>
                        @if(request('budget_context') && request('budget_context') !== 'combined')
                            <span class="badge bg-primary me-2">Context: {{ ucfirst(str_replace('_', ' ', request('budget_context'))) }}</span>
                        @endif
                        @if(request('search'))
                            <span class="badge bg-secondary me-2">Search: {{ request('search') }}</span>
                        @endif
                        @if(request('coordinator_id'))
                            @php $selectedCoordinator = ($coordinators ?? collect())->firstWhere('id', request('coordinator_id')); @endphp
                            @if($selectedCoordinator)
                                <span class="badge bg-warning me-2">Coordinator: {{ $selectedCoordinator->name }}</span>
                            @endif
                        @endif
                        @if(request('province'))
                            <span class="badge bg-primary me-2">Province: {{ request('province') }}</span>
                        @endif
                        @if(request('center'))
                            <span class="badge bg-dark me-2">Center: {{ request('center') }}</span>
                        @endif
                        @if(request('project_type'))
                            <span class="badge bg-success me-2">Type: {{ Str::limit(request('project_type'), 20) }}</span>
                        @endif
                        <a href="{{ route('general.budgets') }}" class="float-end btn btn-sm btn-outline-secondary">Clear All</a>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm" id="budgetsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Source</th>
                                    <th>Project ID</th>
                                    <th>Project Title</th>
                                    <th>Type</th>
                                    <th>Executor/Applicant</th>
                                    <th>Province</th>
                                    <th class="text-end">Budget</th>
                                    <th class="text-end">Approved Expenses</th>
                                    <th class="text-end">Unapproved Expenses</th>
                                    <th class="text-end">Remaining</th>
                                    <th class="text-center">Utilization</th>
                                    <th class="text-center">Health</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects ?? [] as $project)
                                    @php
                                        $sourceLabel = $project->source ?? 'coordinator_hierarchy';
                                        $sourceBadge = $sourceLabel === 'direct_team' ? 'bg-info' : 'bg-secondary';
                                        $sourceText = $sourceLabel === 'direct_team' ? 'Direct Team' : 'Coordinator Hierarchy';

                                        $healthClass = 'success';
                                        $healthLabel = 'Good';
                                        $healthIndicator = $project->health_indicator ?? 'good';
                                        if ($healthIndicator === 'critical') {
                                            $healthClass = 'danger';
                                            $healthLabel = 'Critical';
                                        } elseif ($healthIndicator === 'warning') {
                                            $healthClass = 'warning';
                                            $healthLabel = 'Warning';
                                        } elseif ($healthIndicator === 'moderate') {
                                            $healthClass = 'info';
                                            $healthLabel = 'Moderate';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge {{ $sourceBadge }}">{{ $sourceText }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('general.showProject', $project->project_id) }}"
                                               class="text-primary fw-bold">
                                                {{ $project->project_id }}
                                            </a>
                                        </td>
                                        <td>
                                            <small>{{ Str::limit($project->project_title ?? 'N/A', 35) }}</small>
                                        </td>
                                        <td>
                                            <small>{{ Str::limit($project->project_type ?? 'N/A', 20) }}</small>
                                        </td>
                                        <td>
                                            <small>{{ $project->user->name ?? 'N/A' }}</small>
                                            <br>
                                            <small class="text-muted">({{ $project->user->role ?? 'N/A' }})</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $project->user->province ?? 'N/A' }}</span>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ format_indian_currency($project->calculated_budget ?? 0, 2) }}</strong>
                                        </td>
                                        <td class="text-end text-success">
                                            <small>{{ format_indian_currency($project->calculated_expenses ?? 0, 2) }}</small>
                                        </td>
                                        <td class="text-end text-warning">
                                            @if(($project->calculated_unapproved_expenses ?? 0) > 0)
                                                <small>{{ format_indian_currency($project->calculated_unapproved_expenses, 2) }}</small>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                        <td class="text-end text-info">
                                            <small>{{ format_indian_currency($project->calculated_remaining ?? 0, 2) }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 18px; width: 80px; margin: 0 auto;">
                                                <div class="progress-bar bg-{{ $healthClass }}"
                                                     role="progressbar"
                                                     style="width: {{ min($project->calculated_utilization ?? 0, 100) }}%"
                                                     aria-valuenow="{{ $project->calculated_utilization ?? 0 }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100"
                                                     title="{{ format_indian_percentage($project->calculated_utilization ?? 0, 1) }}">
                                                    {{ format_indian_percentage($project->calculated_utilization ?? 0, 1) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $healthClass }}" title="Budget Utilization: {{ format_indian_percentage($project->calculated_utilization ?? 0, 1) }}">
                                                {{ $healthLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('general.showProject', $project->project_id) }}"
                                                   class="btn btn-primary btn-sm" title="View Project">
                                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                                </a>
                                                <a href="{{ route('projects.budget.export.excel', $project->project_id) }}"
                                                   class="btn btn-success btn-sm" title="Export Budget to Excel" target="_blank">
                                                    <i data-feather="download" style="width: 14px; height: 14px;"></i>
                                                </a>
                                                <a href="{{ route('projects.budget.export.pdf', $project->project_id) }}"
                                                   class="btn btn-danger btn-sm" title="Export Budget to PDF" target="_blank">
                                                    <i data-feather="file-text" style="width: 14px; height: 14px;"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="py-4 text-center text-muted">
                                            <i data-feather="inbox" style="width: 48px; height: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                            <br>
                                            No projects with budget information found matching the filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if(isset($pagination) && $pagination['total'] > $pagination['per_page'])
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                Showing {{ $pagination['from'] }} to {{ $pagination['to'] }} of {{ $pagination['total'] }} projects
                            </small>
                        </div>
                        <div>
                            @if($pagination['current_page'] > 1)
                                <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}"
                                   class="btn btn-sm btn-secondary">Previous</a>
                            @endif

                            @for($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['last_page'], $pagination['current_page'] + 2); $i++)
                                @if($i == $pagination['current_page'])
                                    <span class="btn btn-sm btn-primary">{{ $i }}</span>
                                @else
                                    <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                                       class="btn btn-sm btn-outline-secondary">{{ $i }}</a>
                                @endif
                            @endfor

                            @if($pagination['current_page'] < $pagination['last_page'])
                                <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}"
                                   class="btn btn-sm btn-secondary">Next</a>
                            @endif
                        </div>
                    </div>
                    @elseif(isset($pagination))
                    <div class="mt-3">
                        <small class="text-muted">
                            Showing {{ $pagination['total'] }} project(s)
                        </small>
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
    // Initialize Feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Add loading state to export buttons
    const exportButtons = document.querySelectorAll('a[href*="budgets.report"]');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<i data-feather="loader" style="width: 14px; height: 14px;" class="spinning"></i> Exporting...';
            this.classList.add('disabled');
            feather.replace();

            // Re-enable after 5 seconds as fallback (in case export takes longer)
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.remove('disabled');
                feather.replace();
            }, 5000);
        });
    });

    // Add tooltip support for better UX
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Add sorting indicators to table headers
    const sortBy = '{{ request("sort_by", "project_id") }}';
    const sortOrder = '{{ request("sort_order", "asc") }}';

    if (sortBy) {
        const header = document.querySelector(`th:contains("${sortBy}")`);
        if (header) {
            header.classList.add('sorting', sortOrder === 'asc' ? 'sorting-asc' : 'sorting-desc');
        }
    }
});
</script>
<style>
.spinning {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.sorting {
    cursor: pointer;
    position: relative;
    padding-right: 30px;
}
.sorting:after {
    content: '↕';
    position: absolute;
    right: 8px;
    opacity: 0.5;
}
.sorting-asc:after {
    content: '↑';
    opacity: 1;
}
.sorting-desc:after {
    content: '↓';
    opacity: 1;
}
</style>
@endpush

@endsection
