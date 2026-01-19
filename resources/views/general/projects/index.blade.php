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
                    <h4 class="mb-0 fp-text-center1">All Projects (General View - Combined)</h4>
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
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Basic Filters --}}
                    <form method="GET" action="{{ route('general.projects') }}" id="filterForm">
                        <div class="mb-3 row">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control"
                                       placeholder="Project ID, Title, Type..."
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="coordinator_id" class="form-label">Coordinator</label>
                                <select name="coordinator_id" id="coordinator_id" class="form-control">
                                    <option value="">All Coordinators</option>
                                    @foreach($coordinators ?? [] as $coordinator)
                                        <option value="{{ $coordinator->id }}" {{ request('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                            {{ $coordinator->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="province" class="form-label">Province</label>
                                <select name="province" id="province" class="form-control">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces ?? [] as $province)
                                        <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses ?? [] as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ \App\Models\OldProjects\Project::$statusLabels[$status] ?? $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="gap-2 d-flex">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('general.projects') }}" class="btn btn-secondary">Clear</a>
                                </div>
                            </div>
                        </div>

                        {{-- Advanced Filters (Collapsible) --}}
                        <div id="advancedFilters" style="display: none;">
                            <div class="pt-3 mb-3 row border-top">
                                <div class="col-md-3">
                                    <label for="center" class="form-label">Center</label>
                                    <select name="center" id="center" class="form-control">
                                        <option value="">All Centers</option>
                                        @foreach($centers ?? [] as $center)
                                            <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                                {{ $center }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="project_type" class="form-label">Project Type</label>
                                    <select name="project_type" id="project_type" class="form-control">
                                        <option value="">All Types</option>
                                        @foreach($projectTypes ?? [] as $type)
                                            <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                                {{ Str::limit($type, 25) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="sort_by" class="form-label">Sort By</label>
                                    <select name="sort_by" id="sort_by" class="form-control">
                                        <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                        <option value="project_id" {{ request('sort_by') == 'project_id' ? 'selected' : '' }}>Project ID</option>
                                        <option value="project_title" {{ request('sort_by') == 'project_title' ? 'selected' : '' }}>Title</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="sort_order" class="form-label">Order</label>
                                    <select name="sort_order" id="sort_order" class="form-control">
                                        <option value="desc" {{ request('sort_order', 'desc') == 'desc' ? 'selected' : '' }}>Descending</option>
                                        <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Ascending</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="per_page" class="form-label">Per Page</label>
                                    <select name="per_page" id="per_page" class="form-control">
                                        <option value="50" {{ request('per_page', 100) == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100</option>
                                        <option value="200" {{ request('per_page', 100) == 200 ? 'selected' : '' }}>200</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Active Filters Display --}}
                    @if(request()->anyFilled(['search', 'coordinator_id', 'province', 'status', 'project_type', 'center']))
                    <div class="mb-3 alert alert-info">
                        <strong>Active Filters:</strong>
                        @if(request('search'))
                            <span class="badge badge-primary me-2">Search: {{ request('search') }}</span>
                        @endif
                        @if(request('coordinator_id'))
                            @php $selectedCoordinator = ($coordinators ?? collect())->firstWhere('id', request('coordinator_id')); @endphp
                            @if($selectedCoordinator)
                                <span class="badge badge-warning me-2">Coordinator: {{ $selectedCoordinator->name }}</span>
                            @endif
                        @endif
                        @if(request('province'))
                            <span class="badge badge-primary me-2">Province: {{ request('province') }}</span>
                        @endif
                        @if(request('status'))
                            <span class="badge badge-info me-2">Status: {{ \App\Models\OldProjects\Project::$statusLabels[request('status')] ?? request('status') }}</span>
                        @endif
                        @if(request('project_type'))
                            <span class="badge badge-success me-2">Type: {{ request('project_type') }}</span>
                        @endif
                        @if(request('center'))
                            <span class="badge badge-dark me-2">Center: {{ request('center') }}</span>
                        @endif
                        <a href="{{ route('general.projects') }}" class="float-right btn btn-sm btn-outline-secondary">Clear All</a>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="projectsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Source</th>
                                    <th>Project ID</th>
                                    <th>Project Title</th>
                                    <th>Project Type</th>
                                    <th>Executor/Applicant</th>
                                    <th>Province</th>
                                    <th>Center</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects ?? [] as $project)
                                    @php
                                        $statusLabel = \App\Models\OldProjects\Project::$statusLabels[$project->status] ?? $project->status;
                                        $statusBadgeClass = match($project->status) {
                                            ProjectStatus::APPROVED_BY_COORDINATOR, ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR => 'bg-success',
                                            ProjectStatus::FORWARDED_TO_COORDINATOR => 'bg-info',
                                            ProjectStatus::REVERTED_BY_COORDINATOR, ProjectStatus::REVERTED_BY_PROVINCIAL,
                                            ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR, ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL,
                                            ProjectStatus::REVERTED_TO_EXECUTOR, ProjectStatus::REVERTED_TO_APPLICANT,
                                            ProjectStatus::REVERTED_TO_PROVINCIAL, ProjectStatus::REVERTED_TO_COORDINATOR => 'bg-warning',
                                            ProjectStatus::REJECTED_BY_COORDINATOR => 'bg-danger',
                                            ProjectStatus::DRAFT => 'bg-secondary',
                                            default => 'bg-primary',
                                        };
                                        $sourceLabel = $project->source ?? 'coordinator_hierarchy';
                                        $sourceBadge = $sourceLabel === 'direct_team' ? 'bg-info' : 'bg-secondary';
                                        $sourceText = $sourceLabel === 'direct_team' ? 'Direct Team' : 'Coordinator Hierarchy';
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge {{ $sourceBadge }}">{{ $sourceText }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('general.showProject', $project->project_id) }}"
                                               class="text-primary font-weight-bold">
                                                {{ $project->project_id }}
                                            </a>
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
                                            <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('general.showProject', $project->project_id) }}"
                                                   class="btn btn-primary btn-sm">
                                                    View
                                                </a>
                                                @if(in_array($project->status, [
                                                    ProjectStatus::FORWARDED_TO_COORDINATOR,
                                                    ProjectStatus::SUBMITTED_TO_PROVINCIAL,
                                                    ProjectStatus::REVERTED_BY_COORDINATOR,
                                                    ProjectStatus::REVERTED_BY_PROVINCIAL,
                                                    ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR,
                                                    ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL,
                                                    ProjectStatus::REVERTED_TO_PROVINCIAL,
                                                    ProjectStatus::REVERTED_TO_COORDINATOR
                                                ]))
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
                                                <button type="button" class="btn btn-info btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#commentModal{{ $project->project_id }}">
                                                    Comment
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Approve Modal with Dual-Role Selection -->
                                    @if(in_array($project->status, [
                                        ProjectStatus::FORWARDED_TO_COORDINATOR,
                                        ProjectStatus::SUBMITTED_TO_PROVINCIAL,
                                        ProjectStatus::REVERTED_BY_COORDINATOR,
                                        ProjectStatus::REVERTED_BY_PROVINCIAL,
                                        ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR,
                                        ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL,
                                        ProjectStatus::REVERTED_TO_PROVINCIAL,
                                        ProjectStatus::REVERTED_TO_COORDINATOR
                                    ]))
                                    <div class="modal fade" id="approveModal{{ $project->project_id }}" tabindex="-1" aria-labelledby="approveModalLabel{{ $project->project_id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="approveModalLabel{{ $project->project_id }}">Approve Project</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('general.approveProject', $project->project_id) }}" id="approveForm{{ $project->project_id }}">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Project ID:</strong> {{ $project->project_id }}</p>
                                                        <p><strong>Project Title:</strong> {{ $project->project_title }}</p>
                                                        <p><strong>Executor:</strong> {{ $project->user->name ?? 'N/A' }}</p>

                                                        <div class="mb-3">
                                                            <label class="form-label"><strong>Approve As: *</strong></label>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="approval_context" id="coordinatorContext{{ $project->project_id }}" value="coordinator" checked onchange="toggleCommencementDate({{ $project->project_id }}, true)">
                                                                <label class="form-check-label" for="coordinatorContext{{ $project->project_id }}">
                                                                    <strong>As Coordinator</strong> (Requires commencement date, final approval)
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="approval_context" id="provincialContext{{ $project->project_id }}" value="provincial" onchange="toggleCommencementDate({{ $project->project_id }}, false)">
                                                                <label class="form-check-label" for="provincialContext{{ $project->project_id }}">
                                                                    <strong>As Provincial</strong> (Forwards to coordinator level)
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <div id="commencementDateFields{{ $project->project_id }}">
                                                            <div class="mb-3 row">
                                                                <div class="col-md-6">
                                                                    <label for="commencement_month{{ $project->project_id }}" class="form-label">Commencement Month *</label>
                                                                    <select name="commencement_month" id="commencement_month{{ $project->project_id }}" class="form-control" required>
                                                                        <option value="">Select Month</option>
                                                                        @for($m = 1; $m <= 12; $m++)
                                                                            <option value="{{ $m }}" {{ old('commencement_month') == $m ? 'selected' : '' }}>
                                                                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                                            </option>
                                                                        @endfor
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="commencement_year{{ $project->project_id }}" class="form-label">Commencement Year *</label>
                                                                    <select name="commencement_year" id="commencement_year{{ $project->project_id }}" class="form-control" required>
                                                                        <option value="">Select Year</option>
                                                                        @for($y = date('Y'); $y <= date('Y') + 10; $y++)
                                                                            <option value="{{ $y }}" {{ old('commencement_year') == $y ? 'selected' : '' }}>
                                                                                {{ $y }}
                                                                            </option>
                                                                        @endfor
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="alert alert-info">
                                                                <small><strong>Note:</strong> Commencement Month & Year cannot be before the current month and year.</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">Approve</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Revert Modal with Context and Level Selection -->
                                    @if(in_array($project->status, [
                                        ProjectStatus::FORWARDED_TO_COORDINATOR,
                                        ProjectStatus::SUBMITTED_TO_PROVINCIAL,
                                        ProjectStatus::REVERTED_BY_COORDINATOR,
                                        ProjectStatus::REVERTED_BY_PROVINCIAL,
                                        ProjectStatus::APPROVED_BY_COORDINATOR,
                                        ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR
                                    ]))
                                    <div class="modal fade" id="revertModal{{ $project->project_id }}" tabindex="-1" aria-labelledby="revertModalLabel{{ $project->project_id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="revertModalLabel{{ $project->project_id }}">Revert Project</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('general.revertProject', $project->project_id) }}" id="revertForm{{ $project->project_id }}">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Project ID:</strong> {{ $project->project_id }}</p>
                                                        <p><strong>Project Title:</strong> {{ $project->project_title }}</p>
                                                        <p><strong>Executor:</strong> {{ $project->user->name ?? 'N/A' }}</p>

                                                        <div class="mb-3">
                                                            <label class="form-label"><strong>Revert As: *</strong></label>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="approval_context" id="revertCoordinatorContext{{ $project->project_id }}" value="coordinator" checked onchange="toggleRevertLevels({{ $project->project_id }}, 'coordinator')">
                                                                <label class="form-check-label" for="revertCoordinatorContext{{ $project->project_id }}">
                                                                    <strong>As Coordinator</strong> (Can revert to Provincial or Coordinator)
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="approval_context" id="revertProvincialContext{{ $project->project_id }}" value="provincial" onchange="toggleRevertLevels({{ $project->project_id }}, 'provincial')">
                                                                <label class="form-check-label" for="revertProvincialContext{{ $project->project_id }}">
                                                                    <strong>As Provincial</strong> (Can revert to Executor, Applicant, or Provincial)
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="revert_level{{ $project->project_id }}" class="form-label">Revert To Level (Optional)</label>
                                                            <select name="revert_level" id="revert_level{{ $project->project_id }}" class="form-control">
                                                                <option value="">General Revert (No specific level)</option>
                                                                <option value="provincial" class="coordinator-option">Provincial</option>
                                                                <option value="coordinator" class="coordinator-option">Coordinator</option>
                                                                <option value="executor" class="provincial-option" style="display:none;">Executor</option>
                                                                <option value="applicant" class="provincial-option" style="display:none;">Applicant</option>
                                                                <option value="provincial" class="provincial-option" style="display:none;">Provincial</option>
                                                            </select>
                                                            <small class="form-text text-muted">Select a specific level to revert to, or leave blank for general revert.</small>
                                                        </div>

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
                                                        <button type="submit" class="btn btn-warning">Revert</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Comment Modal -->
                                    <div class="modal fade" id="commentModal{{ $project->project_id }}" tabindex="-1" aria-labelledby="commentModalLabel{{ $project->project_id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="commentModalLabel{{ $project->project_id }}">Add Comment</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('general.addProjectComment', $project->project_id) }}">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Project ID:</strong> {{ $project->project_id }}</p>
                                                        <div class="mb-3">
                                                            <label for="comment{{ $project->project_id }}" class="form-label">Comment *</label>
                                                            <textarea class="form-control auto-resize-textarea"
                                                                      id="comment{{ $project->project_id }}"
                                                                      name="comment"
                                                                      rows="3"
                                                                      required
                                                                      maxlength="1000"></textarea>
                                                            <small class="form-text text-muted">Maximum 1000 characters. This comment will be logged in activity history.</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-info">Add Comment</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="9" class="py-4 text-center text-muted">
                                            No projects found matching the filters.
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

    // Toggle Commencement Date Fields based on approval context
    window.toggleCommencementDate = function(projectId, show) {
        const fields = document.getElementById('commencementDateFields' + projectId);
        const monthField = document.getElementById('commencement_month' + projectId);
        const yearField = document.getElementById('commencement_year' + projectId);

        if (fields) {
            fields.style.display = show ? 'block' : 'none';
            if (!show) {
                monthField.removeAttribute('required');
                yearField.removeAttribute('required');
            } else {
                monthField.setAttribute('required', 'required');
                yearField.setAttribute('required', 'required');
            }
        }
    };

    // Toggle Revert Levels based on context
    window.toggleRevertLevels = function(projectId, context) {
        const select = document.getElementById('revert_level' + projectId);
        const coordinatorOptions = select.querySelectorAll('.coordinator-option');
        const provincialOptions = select.querySelectorAll('.provincial-option');

        // Reset selection
        select.value = '';

        if (context === 'coordinator') {
            coordinatorOptions.forEach(opt => opt.style.display = 'block');
            provincialOptions.forEach(opt => opt.style.display = 'none');
        } else {
            coordinatorOptions.forEach(opt => opt.style.display = 'none');
            provincialOptions.forEach(opt => opt.style.display = 'block');
        }
    };

    // Initialize auto-resize textareas
    if (typeof initializeAutoResize === 'function') {
        initializeAutoResize();
    }
});
</script>
@endpush

@endsection
