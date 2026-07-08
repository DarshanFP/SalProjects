@extends('coordinator.dashboard')

@section('content')
@php
    use App\Constants\ProjectStatus;
    use App\Helpers\TableFormatter;
@endphp
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 fp-text-center1">All Projects (Coordinator View)</h4>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info" onclick="toggleAdvancedFilters()" id="toggleFiltersBtn">
                            Advanced Filters
                        </button>
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
                            {!! session('error') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Basic Filters --}}
                    <form method="GET" action="{{ route('coordinator.projects.list') }}" id="filterForm">
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
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control"
                                       placeholder="Project ID, Title, Type..."
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="province" class="form-label">Province</label>
                                <select name="province" id="province" class="form-select auto-filter">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select auto-filter">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ \App\Models\OldProjects\Project::$statusLabels[$status] ?? $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="project_type" class="form-label">Project Type</label>
                                <select name="project_type" id="project_type" class="form-select auto-filter">
                                    <option value="">All Types</option>
                                    @foreach($projectTypes as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                            {{ Str::limit($type, 25) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="{{ route('coordinator.projects.list') }}" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>

                        {{-- Advanced Filters (Collapsible) --}}
                        <div id="advancedFilters" style="display: none;">
                            <div class="pt-3 mb-3 row border-top">
                                <div class="col-md-3">
                                    <label for="provincial_id" class="form-label">Provincial</label>
                                    <select name="provincial_id" id="provincial_id" class="form-select auto-filter">
                                        <option value="">All Provincials</option>
                                        @foreach($provincials as $provincial)
                                            <option value="{{ $provincial->id }}" {{ request('provincial_id') == $provincial->id ? 'selected' : '' }}>
                                                {{ $provincial->name }} ({{ $provincial->province }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="user_id" class="form-label">Executor/Applicant</label>
                                    <select name="user_id" id="user_id" class="form-select auto-filter">
                                        <option value="">All Executors/Applicants</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->role }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="center" class="form-label">Center</label>
                                    <select name="center" id="center" class="form-select auto-filter">
                                        <option value="">All Centers</option>
                                        @foreach($centers as $center)
                                            <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                                {{ $center }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="sort_by" class="form-label">Sort By</label>
                                    <select name="sort_by" id="sort_by" class="form-select auto-filter">
                                        <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                        <option value="project_id" {{ request('sort_by') == 'project_id' ? 'selected' : '' }}>Project ID</option>
                                        <option value="project_title" {{ request('sort_by') == 'project_title' ? 'selected' : '' }}>Title</option>
                                        <option value="budget_utilization" {{ request('sort_by') == 'budget_utilization' ? 'selected' : '' }}>Budget Utilization</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="sort_order" class="form-label">Order</label>
                                    <select name="sort_order" id="sort_order" class="form-select auto-filter">
                                        <option value="desc" {{ request('sort_order', 'desc') == 'desc' ? 'selected' : '' }}>Descending</option>
                                        <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Ascending</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Per-page selector (Phase 1) --}}
                    @if(isset($allowedPageSizes) && isset($currentPerPage))
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <form method="GET" action="{{ route('coordinator.projects.list') }}" class="d-flex align-items-center gap-2">
                            @foreach(request()->except('per_page', 'page') as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $v)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <label for="per_page" class="mb-0 form-label">Per page</label>
                            <select name="per_page" id="per_page" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                @foreach($allowedPageSizes as $size)
                                    <option value="{{ $size }}" {{ $currentPerPage == $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    @endif

                    {{-- Active Filters Display --}}
                    @if(request()->anyFilled(['fy', 'search', 'province', 'status', 'project_type', 'provincial_id', 'user_id', 'center', 'start_date', 'end_date']))
                    <div class="mb-3 alert alert-info">
                        <strong>Active Filters:</strong>
                        @if(request('fy'))
                            <span class="badge bg-info me-2">FY: {{ request('fy') }}</span>
                        @endif
                        @if(request('search'))
                            <span class="badge badge-primary me-2">Search: {{ request('search') }}</span>
                        @endif
                        @if(request('province'))
                            <span class="badge badge-primary me-2">Province: {{ request('province') }}</span>
                        @endif
                        @if(request('status'))
                            <span class="badge badge-info me-2">Status: {{ ProjectStatus::$statusLabels[request('status')] ?? request('status') }}</span>
                        @endif
                        @if(request('project_type'))
                            <span class="badge badge-success me-2">Type: {{ request('project_type') }}</span>
                        @endif
                        @if(request('provincial_id'))
                            @php $selectedProvincial = $provincials->firstWhere('id', request('provincial_id')); @endphp
                            @if($selectedProvincial)
                                <span class="badge badge-warning me-2">Provincial: {{ $selectedProvincial->name }}</span>
                            @endif
                        @endif
                        @if(request('user_id'))
                            @php $selectedUser = $users->firstWhere('id', request('user_id')); @endphp
                            @if($selectedUser)
                                <span class="badge badge-secondary me-2">Executor: {{ $selectedUser->name }}</span>
                            @endif
                        @endif
                        @if(request('center'))
                            <span class="badge badge-dark me-2">Center: {{ request('center') }}</span>
                        @endif
                        @if(request('start_date') || request('end_date'))
                            <span class="badge badge-secondary me-2">
                                Date: {{ request('start_date') ?: 'Any' }} to {{ request('end_date') ?: 'Any' }}
                            </span>
                        @endif
                        <a href="{{ route('coordinator.projects.list') }}" class="float-right btn btn-sm btn-outline-secondary">Clear All</a>
                    </div>
                    @endif

                    {{-- Phase 4: Grand Totals Summary Card --}}
                    @if(isset($grandTotals))
                    <div class="mb-4 row">
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <span class="text-muted small d-block">Total Projects</span>
                                    <h5 class="mb-0 fw-bold">{{ number_format($grandTotals['total_projects'] ?? 0) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <span class="text-muted small d-block">Total Budget</span>
                                    <h5 class="mb-0 fw-bold">{{ format_indian_currency($grandTotals['total_budget'] ?? 0, 2) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <span class="text-muted small d-block">Total Expenses</span>
                                    <h5 class="mb-0 fw-bold">{{ format_indian_currency($grandTotals['total_expenses'] ?? 0, 2) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <span class="text-muted small d-block">Total Remaining</span>
                                    <h5 class="mb-0 fw-bold">{{ format_indian_currency($grandTotals['total_remaining'] ?? 0, 2) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Phase 5: Status Distribution Cards --}}
                    @if(!empty($statusDistribution))
                    <div class="row mb-3">
                        @foreach($statusDistribution as $status => $count)
                            @php
                                $statusLabel = \App\Models\OldProjects\Project::$statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));
                                $borderClass = match($status) {
                                    ProjectStatus::APPROVED_BY_COORDINATOR => 'border-start border-3 border-success',
                                    ProjectStatus::FORWARDED_TO_COORDINATOR => 'border-start border-3 border-info',
                                    ProjectStatus::REVERTED_BY_COORDINATOR, ProjectStatus::REVERTED_BY_PROVINCIAL => 'border-start border-3 border-warning',
                                    ProjectStatus::REJECTED_BY_COORDINATOR => 'border-start border-3 border-danger',
                                    ProjectStatus::DRAFT => 'border-start border-3 border-secondary',
                                    default => 'border-start border-3 border-primary',
                                };
                            @endphp
                            <div class="col-md-3 mb-2">
                                <div class="card {{ $borderClass }} shadow-sm h-100">
                                    <div class="card-body py-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted small">
                                            {{ Str::limit($statusLabel, 30) }}
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $count }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover project-list-table" id="projectsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>S.No</th>
                                    <th>Project ID</th>
                                    <th>Last Action</th>
                                    <th>Project Title</th>
                                    <th>Project Type</th>
                                    <th>Executor/Applicant</th>
                                    <th>Province</th>
                                    <th>Center</th>
                                    <th>Provincial</th>
                                    <th>Status</th>
                                    <th>Budget</th>
                                    <th>Expenses</th>
                                    <th>Remaining</th>
                                    <th>Utilization</th>
                                    <th>Health</th>
                                    <th>Reports</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                    @php
                                        $budgetUtilization = $project->budget_utilization ?? 0;
                                        $healthIndicator = $project->health_indicator ?? 'good';

                                        $healthClass = 'success';
                                        $healthLabel = 'Good';
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

                                        $statusLabel = \App\Models\OldProjects\Project::$statusLabels[$project->status] ?? $project->status;
                                        $statusBadgeClass = match($project->status) {
                                            ProjectStatus::APPROVED_BY_COORDINATOR => 'bg-success',
                                            ProjectStatus::FORWARDED_TO_COORDINATOR => 'bg-info',
                                            ProjectStatus::REVERTED_BY_COORDINATOR, ProjectStatus::REVERTED_BY_PROVINCIAL => 'bg-warning',
                                            ProjectStatus::REJECTED_BY_COORDINATOR => 'bg-danger',
                                            ProjectStatus::DRAFT => 'bg-secondary',
                                            default => 'bg-primary',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ TableFormatter::resolveSerial($loop, $projects ?? null, true) }}
                                        </td>
                                        <td>
                                            <a href="{{ route('coordinator.projects.show', $project->project_id) }}"
                                               class="text-primary font-weight-bold">
                                                {{ $project->project_id }}
                                            </a>
                                        </td>
                                        <td>
                                            @php
                                                $lastAction = $project->latestActivityHistory;
                                                $lastActionName = $lastAction?->changed_by_user_name ?? $lastAction?->changedBy?->name ?? '—';
                                                $lastActionDate = $lastAction?->created_at ? $lastAction->created_at->format('d/m/Y') : '—';
                                            @endphp
                                            <span style="color: #fabc05;">{{ $lastActionName }}</span>
                                            <br>
                                            <small style="color: #66d1d1;">{{ $lastActionDate }}</small>
                                        </td>
                                        <td>
                                            {{ Str::limit($project->project_title ?? 'N/A', 40) }}
                                        </td>
                                        <td>
                                            <small>{{ Str::limit($project->project_type ?? 'N/A', 25) }}</small>
                                        </td>
                                        <td>
                                            {{ $project->user->name ?? 'N/A' }}
                                            <br>
                                            <small class="text-muted">({{ $project->user->role ?? 'N/A' }})</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">{{ $project->user->province ?? 'N/A' }}</span>
                                        </td>
                                        <td>{{ $project->user->center ?? 'N/A' }}</td>
                                        <td>
                                            @if($project->user->parent)
                                                {{ $project->user->parent->name }}
                                                <br>
                                                <small class="text-muted">({{ $project->user->parent->province ?? 'N/A' }})</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            <small>{{ format_indian_currency($project->calculated_budget ?? 0, 2) }}</small>
                                        </td>
                                        <td>
                                            <small>{{ format_indian_currency($project->calculated_expenses ?? 0, 2) }}</small>
                                        </td>
                                        <td>
                                            <small>{{ format_indian_currency($project->calculated_remaining ?? 0, 2) }}</small>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px; width: 100px;">
                                                <div class="progress-bar {{ $healthIndicator === 'critical' ? 'bg-danger' : ($healthIndicator === 'warning' ? 'bg-warning' : ($healthIndicator === 'moderate' ? 'bg-info' : 'bg-success')) }}"
                                                     role="progressbar"
                                                     style="width: {{ min($budgetUtilization, 100) }}%"
                                                     aria-valuenow="{{ $budgetUtilization }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100"
                                                     title="{{ format_indian_percentage($budgetUtilization, 1) }}">
                                                    {{ format_indian_percentage($budgetUtilization, 1) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $healthClass }}" title="Budget Utilization: {{ format_indian_percentage($budgetUtilization, 1) }}">
                                                {{ $healthLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $project->reports_count ?? 0 }} total</span>
                                            <br>
                                            <small class="text-muted">{{ $project->approved_reports_count ?? 0 }} approved</small>
                                        </td>
                                        <td class="col-actions">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('coordinator.projects.show', $project->project_id) }}"
                                                   class="btn btn-primary btn-sm">
                                                    View
                                                </a>
                                                @if(in_array($project->status, [ProjectStatus::FORWARDED_TO_COORDINATOR]))
                                                    <button type="button" class="btn btn-success btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#approveModal{{ $project->project_id }}">
                                                        Approve
                                                    </button>
                                                    <button type="button" class="btn btn-warning btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#revertModal{{ $project->project_id }}">
                                                        Revert
                                                    </button>
                                                @endif
                                                <a href="{{ route('coordinator.projects.downloadPdf', $project->project_id) }}"
                                                   class="btn btn-secondary btn-sm"
                                                   target="_blank">
                                                    Download PDF
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Approve Modal -->
                                    @if(in_array($project->status, [ProjectStatus::FORWARDED_TO_COORDINATOR]))
                                    <div class="modal fade" id="approveModal{{ $project->project_id }}" tabindex="-1" aria-labelledby="approveModalLabel{{ $project->project_id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="approveModalLabel{{ $project->project_id }}">Approve Project</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('projects.approve', $project->project_id) }}">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Project ID:</strong> {{ $project->project_id }}</p>
                                                        <p><strong>Project Title:</strong> {{ $project->project_title }}</p>
                                                        <div class="alert alert-info">
                                                            <strong>Note:</strong> Set the Commencement Month & Year. It cannot be before the current month.
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="approve_commencement_month{{ $project->project_id }}" class="form-label">Commencement Month <span class="text-danger">*</span></label>
                                                            <select name="commencement_month" id="approve_commencement_month{{ $project->project_id }}" class="form-control" required>
                                                                <option value="">Select month</option>
                                                                @for($i = 1; $i <= 12; $i++)
                                                                    <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                                                                @endfor
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="approve_commencement_year{{ $project->project_id }}" class="form-label">Commencement Year <span class="text-danger">*</span></label>
                                                            <select name="commencement_year" id="approve_commencement_year{{ $project->project_id }}" class="form-control" required>
                                                                <option value="">Select year</option>
                                                                @for($y = (int)date('Y'); $y <= (int)date('Y') + 10; $y++)
                                                                    <option value="{{ $y }}">{{ $y }}</option>
                                                                @endfor
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">Approve Project</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Revert Modal -->
                                    @if(in_array($project->status, [ProjectStatus::FORWARDED_TO_COORDINATOR]))
                                    <div class="modal fade" id="revertModal{{ $project->project_id }}" tabindex="-1" aria-labelledby="revertModalLabel{{ $project->project_id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="revertModalLabel{{ $project->project_id }}">Revert Project to Provincial</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('projects.revertToProvincial', $project->project_id) }}">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Project ID:</strong> {{ $project->project_id }}</p>
                                                        <p><strong>Project Title:</strong> {{ $project->project_title }}</p>
                                                        <p><strong>Executor:</strong> {{ $project->user->name ?? 'N/A' }}</p>
                                                        <p><strong>Provincial:</strong> {{ $project->user->parent->name ?? 'N/A' }}</p>
                                                        <div class="mb-3">
                                                            <label for="revert_reason{{ $project->project_id }}" class="form-label">Reason for Revert *</label>
                                                            <textarea class="form-control auto-resize-textarea"
                                                                      id="revert_reason{{ $project->project_id }}"
                                                                      name="revert_reason"
                                                                      rows="3"
                                                                      required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-warning">Revert to Provincial</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="17" class="py-4 text-center text-muted">
                                            No projects found matching the filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination (Phase 1: Laravel paginator) --}}
                    @if(isset($projects) && $projects->hasPages())
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <small class="text-muted">
                                Showing {{ $projects->firstItem() }} to {{ $projects->lastItem() }} of {{ $projects->total() }} projects
                            </small>
                        </div>
                        <div>
                            {{ $projects->links() }}
                        </div>
                    </div>
                    @elseif(isset($projects) && $projects->total() > 0)
                    <div class="mt-3">
                        <small class="text-muted">
                            Showing {{ $projects->total() }} project(s)
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
    // Auto-filter: submit form when any dropdown with .auto-filter is changed
    var filterSubmitting = false;
    document.querySelectorAll('.auto-filter').forEach(function(el) {
        el.addEventListener('change', function() {
            if (filterSubmitting) return;
            filterSubmitting = true;
            this.closest('form').submit();
        });
    });

    // Toggle Advanced Filters
    window.toggleAdvancedFilters = function() {
        const filters = document.getElementById('advancedFilters');
        const toggleBtn = document.getElementById('toggleFiltersBtn');

        if (filters.style.display === 'none') {
            filters.style.display = 'block';
            toggleBtn.textContent = 'Hide Advanced Filters';
        } else {
            filters.style.display = 'none';
            toggleBtn.textContent = 'Advanced Filters';
        }
    };

    // Province change handler - update executors
    const provinceSelect = document.getElementById('province');
    const executorSelect = document.getElementById('user_id');

    if (provinceSelect && executorSelect) {
        provinceSelect.addEventListener('change', function() {
            const province = this.value;

            // Filter executors by province on client side
            Array.from(executorSelect.options).forEach(option => {
                if (option.value === '') return; // Keep "All" option

                const optionText = option.textContent;
                const optionProvince = optionText.match(/\(([^)]+)\)/)?.[1];

                if (province && optionProvince && optionProvince !== province) {
                    option.style.display = 'none';
                } else {
                    option.style.display = '';
                }
            });
        });
    }
});
</script>
@endpush

@endsection
