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
                    <h4 class="mb-0 fp-text-center1">Pending Reports</h4>
                    <div class="btn-group">
                        <a href="{{ route('general.reports') }}" class="btn btn-sm btn-secondary">All Reports</a>
                        <a href="{{ route('general.reports.approved') }}" class="btn btn-sm btn-success">Approved Reports</a>
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

                    {{-- Filters Form --}}
                    <form method="GET" action="{{ route('general.reports.pending') }}" id="filterForm">
                        <div class="mb-3 row">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control form-control-sm"
                                       placeholder="Report ID, Project ID, Title..."
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="coordinator_id" class="form-label">Coordinator</label>
                                <select name="coordinator_id" id="coordinator_id" class="form-control form-control-sm">
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
                                <select name="province" id="province" class="form-control form-control-sm">
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
                                <select name="status" id="status" class="form-control form-control-sm">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses ?? [] as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ DPReport::$statusLabels[$status] ?? $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="urgency" class="form-label">Urgency</label>
                                <select name="urgency" id="urgency" class="form-control form-control-sm">
                                    <option value="">All</option>
                                    <option value="urgent" {{ request('urgency') == 'urgent' ? 'selected' : '' }}>Urgent (>7 days)</option>
                                    <option value="normal" {{ request('urgency') == 'normal' ? 'selected' : '' }}>Normal (3-7 days)</option>
                                    <option value="low" {{ request('urgency') == 'low' ? 'selected' : '' }}>Low (<3 days)</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <div class="col-md-2">
                                <label for="project_type" class="form-label">Project Type</label>
                                <select name="project_type" id="project_type" class="form-control form-control-sm">
                                    <option value="">All Types</option>
                                    @foreach($projectTypes ?? [] as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                            {{ Str::limit($type, 25) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="center" class="form-label">Center</label>
                                <select name="center" id="center" class="form-control form-control-sm">
                                    <option value="">All Centers</option>
                                    @foreach($centers ?? [] as $center)
                                        <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                            {{ $center }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="sort_by" class="form-label">Sort By</label>
                                <select name="sort_by" id="sort_by" class="form-control form-control-sm">
                                    <option value="urgency" {{ request('sort_by', 'urgency') == 'urgency' ? 'selected' : '' }}>Urgency</option>
                                    <option value="days_pending" {{ request('sort_by') == 'days_pending' ? 'selected' : '' }}>Days Pending</option>
                                    <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                    <option value="report_id" {{ request('sort_by') == 'report_id' ? 'selected' : '' }}>Report ID</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="sort_order" class="form-label">Order</label>
                                <select name="sort_order" id="sort_order" class="form-control form-control-sm">
                                    <option value="desc" {{ request('sort_order', 'desc') == 'desc' ? 'selected' : '' }}>Descending</option>
                                    <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Ascending</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="per_page" class="form-label">Per Page</label>
                                <select name="per_page" id="per_page" class="form-control form-control-sm">
                                    <option value="25" {{ request('per_page', 50) == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page', 50) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <a href="{{ route('general.reports.pending') }}" class="btn btn-secondary btn-sm w-100">Clear</a>
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
                        @if(request('province'))
                            <span class="badge badge-primary me-2">Province: {{ request('province') }}</span>
                        @endif
                        @if(request('status'))
                            <span class="badge badge-info me-2">Status: {{ DPReport::$statusLabels[request('status')] ?? request('status') }}</span>
                        @endif
                        @if(request('urgency'))
                            <span class="badge badge-{{ request('urgency') == 'urgent' ? 'danger' : (request('urgency') == 'normal' ? 'warning' : 'success') }} me-2">
                                Urgency: {{ ucfirst(request('urgency')) }}
                            </span>
                        @endif
                        @if(request('project_type'))
                            <span class="badge badge-success me-2">Type: {{ request('project_type') }}</span>
                        @endif
                        @if(request('center'))
                            <span class="badge badge-dark me-2">Center: {{ request('center') }}</span>
                        @endif
                        <a href="{{ route('general.reports.pending') }}" class="float-right btn btn-sm btn-outline-secondary">Clear All</a>
                    </div>
                    @endif

                    {{-- Bulk Actions --}}
                    <div class="mb-3">
                        <form method="POST" action="{{ route('general.reports.bulkAction') }}" id="bulkActionForm">
                            @csrf
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll()">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">Deselect All</button>
                            </div>
                            <select name="bulk_action" id="bulkAction" class="form-control form-control-sm d-inline-block" style="width: auto;">
                                <option value="">Bulk Actions</option>
                                <option value="approve_as_coordinator">Approve as Coordinator</option>
                                <option value="approve_as_provincial">Approve as Provincial</option>
                                <option value="export">Export Selected</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                        </form>
                    </div>

                    {{-- Summary Statistics --}}
                    @php
                        $urgentCount = collect($reports ?? [])->where('urgency', 'urgent')->count();
                        $normalCount = collect($reports ?? [])->where('urgency', 'normal')->count();
                        $lowCount = collect($reports ?? [])->where('urgency', 'low')->count();
                    @endphp
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body p-2">
                                    <small class="d-block">Urgent (>7 days)</small>
                                    <h5 class="mb-0">{{ $urgentCount }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body p-2">
                                    <small class="d-block">Normal (3-7 days)</small>
                                    <h5 class="mb-0">{{ $normalCount }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body p-2">
                                    <small class="d-block">Low (<3 days)</small>
                                    <h5 class="mb-0">{{ $lowCount }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body p-2">
                                    <small class="d-block">Total Pending</small>
                                    <h5 class="mb-0">{{ count($reports ?? []) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Reports Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="pendingReportsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll()">
                                    </th>
                                    <th>Source</th>
                                    <th>Report ID</th>
                                    <th>Project ID</th>
                                    <th>Project Title</th>
                                    <th>Executor/Applicant</th>
                                    <th>Province</th>
                                    <th>Status</th>
                                    <th>Days Pending</th>
                                    <th>Total Amount</th>
                                    <th>Total Expenses</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports ?? [] as $report)
                                    @php
                                        $statusLabel = DPReport::$statusLabels[$report->status] ?? $report->status;
                                        $statusBadgeClass = match($report->status) {
                                            DPReport::STATUS_FORWARDED_TO_COORDINATOR => 'bg-info',
                                            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL => 'bg-primary',
                                            DPReport::STATUS_REVERTED_BY_COORDINATOR, DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                                            DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR, DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
                                            DPReport::STATUS_REVERTED_TO_PROVINCIAL, DPReport::STATUS_REVERTED_TO_COORDINATOR => 'bg-warning',
                                            default => 'bg-primary',
                                        };
                                        $sourceLabel = $report->source ?? 'coordinator_hierarchy';
                                        $sourceBadge = $sourceLabel === 'direct_team' ? 'bg-info' : 'bg-secondary';
                                        $sourceText = $sourceLabel === 'direct_team' ? 'Direct Team' : 'Coordinator Hierarchy';
                                    @endphp
                                    <tr class="{{ $report->urgency === 'urgent' ? 'table-danger' : ($report->urgency === 'normal' ? 'table-warning' : '') }}">
                                        <td>
                                            <input type="checkbox" name="report_ids[]" value="{{ $report->report_id }}" class="report-checkbox">
                                        </td>
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
                                        <td>{{ Str::limit($report->project_title ?? 'N/A', 40) }}</td>
                                        <td>
                                            {{ $report->user->name ?? 'N/A' }}
                                            <br>
                                            <small class="text-muted">({{ $report->user->role ?? 'N/A' }})</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">{{ $report->user->province ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $report->urgency === 'urgent' ? 'danger' : ($report->urgency === 'normal' ? 'warning' : 'success') }}">
                                                {{ $report->days_pending ?? 0 }} days
                                            </span>
                                        </td>
                                        <td>{{ format_indian_currency($report->total_amount ?? 0, 2) }}</td>
                                        <td>{{ format_indian_currency($report->total_expenses ?? 0, 2) }}</td>
                                        <td>{{ format_indian_currency($report->balance_amount ?? 0, 2) }}</td>
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
                                                                <input class="form-check-input" type="radio" name="approval_context" id="revertCoordinatorContext{{ $report->report_id }}" value="coordinator" checked onchange="toggleRevertLevels('{{ $report->report_id }}', 'coordinator')">
                                                                <label class="form-check-label" for="revertCoordinatorContext{{ $report->report_id }}">
                                                                    <strong>As Coordinator</strong> (Can revert to Provincial or Coordinator)
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="approval_context" id="revertProvincialContext{{ $report->report_id }}" value="provincial" onchange="toggleRevertLevels('{{ $report->report_id }}', 'provincial')">
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
                                @empty
                                    <tr>
                                        <td colspan="13" class="py-4 text-center text-muted">
                                            No pending reports found matching the filters.
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
    // Bulk action form submission
    document.getElementById('bulkActionForm')?.addEventListener('submit', function(e) {
        const selectedReports = document.querySelectorAll('.report-checkbox:checked');
        if (selectedReports.length === 0) {
            e.preventDefault();
            alert('Please select at least one report.');
            return false;
        }

        const bulkAction = document.getElementById('bulkAction').value;
        if (!bulkAction) {
            e.preventDefault();
            alert('Please select a bulk action.');
            return false;
        }

        if (bulkAction === 'approve_as_coordinator' || bulkAction === 'approve_as_provincial') {
            if (!confirm(`Are you sure you want to ${bulkAction.replace('_', ' ')} for ${selectedReports.length} report(s)?`)) {
                e.preventDefault();
                return false;
            }
        }
    });
});

function selectAll() {
    document.querySelectorAll('.report-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('selectAllCheckbox').checked = true;
}

function deselectAll() {
    document.querySelectorAll('.report-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAllCheckbox').checked = false;
}

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    document.querySelectorAll('.report-checkbox').forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

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
</script>
@endpush

@endsection
