@extends('general.dashboard')

@section('content')
@php
    use App\Models\Reports\Monthly\DPReport;
@endphp
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 fp-text-center1">All Reports (General View - Combined)</h4>
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
                    <form method="GET" action="{{ route('general.reports') }}" id="filterForm">
                        <div class="mb-3 row">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control"
                                       placeholder="Report ID, Project ID, Title..."
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
                                            {{ DPReport::$statusLabels[$status] ?? $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="gap-2 d-flex">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('general.reports') }}" class="btn btn-secondary">Clear</a>
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
                                    <label for="urgency" class="form-label">Urgency</label>
                                    <select name="urgency" id="urgency" class="form-control">
                                        <option value="">All</option>
                                        <option value="urgent" {{ request('urgency') == 'urgent' ? 'selected' : '' }}>Urgent (>7 days)</option>
                                        <option value="normal" {{ request('urgency') == 'normal' ? 'selected' : '' }}>Normal (3-7 days)</option>
                                        <option value="low" {{ request('urgency') == 'low' ? 'selected' : '' }}>Low (<3 days)</option>
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
                    @if(request()->anyFilled(['search', 'coordinator_id', 'province', 'status', 'project_type', 'center', 'urgency']))
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
                            <span class="badge badge-info me-2">Status: {{ DPReport::$statusLabels[request('status')] ?? request('status') }}</span>
                        @endif
                        @if(request('project_type'))
                            <span class="badge badge-success me-2">Type: {{ request('project_type') }}</span>
                        @endif
                        @if(request('center'))
                            <span class="badge badge-dark me-2">Center: {{ request('center') }}</span>
                        @endif
                        @if(request('urgency'))
                            <span class="badge badge-{{ request('urgency') == 'urgent' ? 'danger' : (request('urgency') == 'normal' ? 'warning' : 'success') }} me-2">
                                Urgency: {{ ucfirst(request('urgency')) }}
                            </span>
                        @endif
                        <a href="{{ route('general.reports') }}" class="float-right btn btn-sm btn-outline-secondary">Clear All</a>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="reportsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Source</th>
                                    <th>Report ID</th>
                                    <th>Project ID</th>
                                    <th>Project Title</th>
                                    <th>Executor/Applicant</th>
                                    <th>Province</th>
                                    <th>Center</th>
                                    <th>Status</th>
                                    <th>Days Pending</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports ?? [] as $report)
                                    @php
                                        $statusLabel = DPReport::$statusLabels[$report->status] ?? $report->status;
                                        $statusBadgeClass = match($report->status) {
                                            DPReport::STATUS_APPROVED_BY_COORDINATOR, DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR => 'bg-success',
                                            DPReport::STATUS_FORWARDED_TO_COORDINATOR => 'bg-info',
                                            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL => 'bg-primary',
                                            DPReport::STATUS_REVERTED_BY_COORDINATOR, DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                                            DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR, DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
                                            DPReport::STATUS_REVERTED_TO_EXECUTOR, DPReport::STATUS_REVERTED_TO_APPLICANT,
                                            DPReport::STATUS_REVERTED_TO_PROVINCIAL, DPReport::STATUS_REVERTED_TO_COORDINATOR => 'bg-warning',
                                            DPReport::STATUS_REJECTED_BY_COORDINATOR => 'bg-danger',
                                            DPReport::STATUS_DRAFT => 'bg-secondary',
                                            default => 'bg-primary',
                                        };
                                        $sourceLabel = $report->source ?? 'coordinator_hierarchy';
                                        $sourceBadge = $sourceLabel === 'direct_team' ? 'bg-info' : 'bg-secondary';
                                        $sourceText = $sourceLabel === 'direct_team' ? 'Direct Team' : 'Coordinator Hierarchy';
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge {{ $sourceBadge }}">{{ $sourceText }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('general.showReport', $report->report_id) }}"
                                               class="text-primary font-weight-bold">
                                                {{ $report->report_id }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('general.showProject', $report->project_id) }}"
                                               class="text-info">
                                                {{ $report->project_id }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ Str::limit($report->project_title ?? 'N/A', 40) }}
                                        </td>
                                        <td>
                                            {{ $report->user->name ?? 'N/A' }}
                                            <br>
                                            <small class="text-muted">({{ $report->user->role ?? 'N/A' }})</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">{{ $report->user->province ?? 'N/A' }}</span>
                                        </td>
                                        <td>{{ $report->user->center ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            @if($report->days_pending !== null)
                                                <span class="badge badge-{{ $report->urgency === 'urgent' ? 'danger' : ($report->urgency === 'normal' ? 'warning' : 'success') }}">
                                                    {{ $report->days_pending }} days
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('general.showReport', $report->report_id) }}"
                                                   class="btn btn-primary btn-sm">
                                                    View
                                                </a>
                                                @if(in_array($report->status, [
                                                    DPReport::STATUS_FORWARDED_TO_COORDINATOR,
                                                    DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                                                    DPReport::STATUS_REVERTED_BY_COORDINATOR,
                                                    DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                                                    DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
                                                    DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
                                                    DPReport::STATUS_REVERTED_TO_PROVINCIAL,
                                                    DPReport::STATUS_REVERTED_TO_COORDINATOR
                                                ]))
                                                    <button type="button" class="btn btn-success btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#approveModal{{ $report->report_id }}">
                                                        Approve
                                                    </button>
                                                    <button type="button" class="btn btn-warning btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#revertModal{{ $report->report_id }}">
                                                        Revert
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-info btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#commentModal{{ $report->report_id }}">
                                                    Comment
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Approve Modal with Dual-Role Selection -->
                                    @if(in_array($report->status, [
                                        DPReport::STATUS_FORWARDED_TO_COORDINATOR,
                                        DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                                        DPReport::STATUS_REVERTED_BY_COORDINATOR,
                                        DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                                        DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
                                        DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
                                        DPReport::STATUS_REVERTED_TO_PROVINCIAL,
                                        DPReport::STATUS_REVERTED_TO_COORDINATOR
                                    ]))
                                    <div class="modal fade" id="approveModal{{ $report->report_id }}" tabindex="-1" aria-labelledby="approveModalLabel{{ $report->report_id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="approveModalLabel{{ $report->report_id }}">Approve Report</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('general.approveReport', $report->report_id) }}" id="approveForm{{ $report->report_id }}">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Report ID:</strong> {{ $report->report_id }}</p>
                                                        <p><strong>Project:</strong> {{ $report->project_title }}</p>
                                                        <p><strong>Executor:</strong> {{ $report->user->name ?? 'N/A' }}</p>

                                                        <div class="mb-3">
                                                            <label class="form-label"><strong>Approve As: *</strong></label>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="approval_context" id="coordinatorContext{{ $report->report_id }}" value="coordinator" checked>
                                                                <label class="form-check-label" for="coordinatorContext{{ $report->report_id }}">
                                                                    <strong>As Coordinator</strong> (Final approval)
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="approval_context" id="provincialContext{{ $report->report_id }}" value="provincial">
                                                                <label class="form-check-label" for="provincialContext{{ $report->report_id }}">
                                                                    <strong>As Provincial</strong> (Forwards to coordinator level)
                                                                </label>
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
                                    @if(in_array($report->status, [
                                        DPReport::STATUS_FORWARDED_TO_COORDINATOR,
                                        DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                                        DPReport::STATUS_REVERTED_BY_COORDINATOR,
                                        DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                                        DPReport::STATUS_APPROVED_BY_COORDINATOR,
                                        DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR
                                    ]))
                                    <div class="modal fade" id="revertModal{{ $report->report_id }}" tabindex="-1" aria-labelledby="revertModalLabel{{ $report->report_id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="revertModalLabel{{ $report->report_id }}">Revert Report</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('general.revertReport', $report->report_id) }}" id="revertForm{{ $report->report_id }}">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Report ID:</strong> {{ $report->report_id }}</p>
                                                        <p><strong>Project:</strong> {{ $report->project_title }}</p>
                                                        <p><strong>Executor:</strong> {{ $report->user->name ?? 'N/A' }}</p>

                                                        <div class="mb-3">
                                                            <label class="form-label"><strong>Revert As: *</strong></label>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="approval_context" id="revertCoordinatorContext{{ $report->report_id }}" value="coordinator" checked onchange="toggleRevertLevels({{ $report->report_id }}, 'coordinator')">
                                                                <label class="form-check-label" for="revertCoordinatorContext{{ $report->report_id }}">
                                                                    <strong>As Coordinator</strong> (Can revert to Provincial or Coordinator)
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="approval_context" id="revertProvincialContext{{ $report->report_id }}" value="provincial" onchange="toggleRevertLevels({{ $report->report_id }}, 'provincial')">
                                                                <label class="form-check-label" for="revertProvincialContext{{ $report->report_id }}">
                                                                    <strong>As Provincial</strong> (Can revert to Executor, Applicant, or Provincial)
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="revert_level{{ $report->report_id }}" class="form-label">Revert To Level (Optional)</label>
                                                            <select name="revert_level" id="revert_level{{ $report->report_id }}" class="form-control">
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
                                                            <label for="revert_reason{{ $report->report_id }}" class="form-label">Reason for Revert *</label>
                                                            <textarea class="form-control auto-resize-textarea"
                                                                      id="revert_reason{{ $report->report_id }}"
                                                                      name="revert_reason"
                                                                      rows="3"
                                                                      required
                                                                      maxlength="1000"></textarea>
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
                                    <div class="modal fade" id="commentModal{{ $report->report_id }}" tabindex="-1" aria-labelledby="commentModalLabel{{ $report->report_id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="commentModalLabel{{ $report->report_id }}">Add Comment</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('general.addReportComment', $report->report_id) }}">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Report ID:</strong> {{ $report->report_id }}</p>
                                                        <div class="mb-3">
                                                            <label for="comment{{ $report->report_id }}" class="form-label">Comment *</label>
                                                            <textarea class="form-control auto-resize-textarea"
                                                                      id="comment{{ $report->report_id }}"
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
                                        <td colspan="10" class="py-4 text-center text-muted">
                                            No reports found matching the filters.
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
                                Showing {{ $pagination['from'] }} to {{ $pagination['to'] }} of {{ $pagination['total'] }} reports
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
                            Showing {{ $pagination['total'] }} report(s)
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

    // Toggle Revert Levels based on context
    window.toggleRevertLevels = function(reportId, context) {
        const select = document.getElementById('revert_level' + reportId);
        if (!select) return;

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
