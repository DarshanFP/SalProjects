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
                    <h4 class="mb-0 fp-text-center1">Approved Reports</h4>
                    <div class="btn-group">
                        <a href="{{ route('general.reports') }}" class="btn btn-sm btn-secondary">All Reports</a>
                        <a href="{{ route('general.reports.pending') }}" class="btn btn-sm btn-warning">Pending Reports</a>
                        <button type="button" class="btn btn-sm btn-success" onclick="exportReports('excel')">
                            <i data-feather="download"></i> Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="exportReports('pdf')">
                            <i data-feather="file-text"></i> Export PDF
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

                    {{-- Statistics Cards --}}
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body p-3">
                                    <small class="d-block">Total Approved Reports</small>
                                    <h3 class="mb-0">{{ $statistics['total_reports'] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body p-3">
                                    <small class="d-block">Total Amount</small>
                                    <h5 class="mb-0">{{ format_indian_currency($statistics['total_amount'] ?? 0, 2) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body p-3">
                                    <small class="d-block">Total Expenses</small>
                                    <h5 class="mb-0">{{ format_indian_currency($statistics['total_expenses'] ?? 0, 2) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body p-3">
                                    <small class="d-block">Total Balance</small>
                                    <h5 class="mb-0">{{ format_indian_currency($statistics['total_balance'] ?? 0, 2) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Statistics by Project Type --}}
                    @if(isset($statistics['by_project_type']) && count($statistics['by_project_type']) > 0)
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Statistics by Project Type</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Project Type</th>
                                                    <th class="text-end">Reports</th>
                                                    <th class="text-end">Total Amount</th>
                                                    <th class="text-end">Total Expenses</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($statistics['by_project_type'] as $type => $data)
                                                <tr>
                                                    <td><strong>{{ $type }}</strong></td>
                                                    <td class="text-end">{{ $data['count'] ?? 0 }}</td>
                                                    <td class="text-end">{{ format_indian_currency($data['total_amount'] ?? 0, 2) }}</td>
                                                    <td class="text-end">{{ format_indian_currency($data['total_expenses'] ?? 0, 2) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Statistics by Province --}}
                    @if(isset($statistics['by_province']) && count($statistics['by_province']) > 0)
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Statistics by Province</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Province</th>
                                                    <th class="text-end">Reports</th>
                                                    <th class="text-end">Total Amount</th>
                                                    <th class="text-end">Total Expenses</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($statistics['by_province'] as $province => $data)
                                                <tr>
                                                    <td><strong>{{ $province }}</strong></td>
                                                    <td class="text-end">{{ $data['count'] ?? 0 }}</td>
                                                    <td class="text-end">{{ format_indian_currency($data['total_amount'] ?? 0, 2) }}</td>
                                                    <td class="text-end">{{ format_indian_currency($data['total_expenses'] ?? 0, 2) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Filters Form --}}
                    <form method="GET" action="{{ route('general.reports.approved') }}" id="filterForm">
                        <div class="mb-3 row">
                            <div class="col-md-2">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control form-control-sm"
                                       placeholder="Report ID, Project ID..."
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm"
                                       value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm"
                                       value="{{ request('end_date') }}">
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
                                <label for="project_type" class="form-label">Project Type</label>
                                <select name="project_type" id="project_type" class="form-control form-control-sm">
                                    <option value="">All Types</option>
                                    @foreach($projectTypes ?? [] as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                            {{ Str::limit($type, 20) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                    <a href="{{ route('general.reports.approved') }}" class="btn btn-secondary btn-sm">Clear</a>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <div class="col-md-2">
                                <label for="sort_by" class="form-label">Sort By</label>
                                <select name="sort_by" id="sort_by" class="form-control form-control-sm">
                                    <option value="updated_at" {{ request('sort_by', 'updated_at') == 'updated_at' ? 'selected' : '' }}>Approval Date</option>
                                    <option value="report_id" {{ request('sort_by') == 'report_id' ? 'selected' : '' }}>Report ID</option>
                                    <option value="total_expenses" {{ request('sort_by') == 'total_expenses' ? 'selected' : '' }}>Total Expenses</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="sort_order" class="form-label">Order</label>
                                <select name="sort_order" id="sort_order" class="form-control form-control-sm">
                                    <option value="desc" {{ request('sort_order', 'desc') == 'desc' ? 'selected' : '' }}>Newest First</option>
                                    <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Oldest First</option>
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
                        </div>
                    </form>

                    {{-- Active Filters Display --}}
                    @if(request()->anyFilled(['search', 'start_date', 'end_date', 'province', 'project_type']))
                    <div class="mb-3 alert alert-info">
                        <strong>Active Filters:</strong>
                        @if(request('search'))
                            <span class="badge badge-primary me-2">Search: {{ request('search') }}</span>
                        @endif
                        @if(request('start_date'))
                            <span class="badge badge-info me-2">From: {{ request('start_date') }}</span>
                        @endif
                        @if(request('end_date'))
                            <span class="badge badge-info me-2">To: {{ request('end_date') }}</span>
                        @endif
                        @if(request('province'))
                            <span class="badge badge-primary me-2">Province: {{ request('province') }}</span>
                        @endif
                        @if(request('project_type'))
                            <span class="badge badge-success me-2">Type: {{ request('project_type') }}</span>
                        @endif
                        <a href="{{ route('general.reports.approved') }}" class="float-right btn btn-sm btn-outline-secondary">Clear All</a>
                    </div>
                    @endif

                    {{-- Reports Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="approvedReportsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Source</th>
                                    <th>Report ID</th>
                                    <th>Project ID</th>
                                    <th>Project Title</th>
                                    <th>Executor/Applicant</th>
                                    <th>Province</th>
                                    <th>Center</th>
                                    <th>Project Type</th>
                                    <th>Approved Date</th>
                                    <th>Total Amount</th>
                                    <th>Total Expenses</th>
                                    <th>Expenses This Month</th>
                                    <th>Balance Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports ?? [] as $report)
                                    @php
                                        $statusLabel = DPReport::$statusLabels[$report->status] ?? $report->status;
                                        $statusBadgeClass = match($report->status) {
                                            DPReport::STATUS_APPROVED_BY_COORDINATOR, DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR => 'bg-success',
                                            default => 'bg-success',
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
                                        <td>{{ Str::limit($report->project_title ?? 'N/A', 40) }}</td>
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
                                            <small>{{ Str::limit($report->project_type ?? 'N/A', 25) }}</small>
                                        </td>
                                        <td>
                                            <small>{{ $report->updated_at ? $report->updated_at->format('d-m-Y') : 'N/A' }}</small>
                                        </td>
                                        <td>{{ format_indian_currency($report->total_amount ?? 0, 2) }}</td>
                                        <td>{{ format_indian_currency($report->total_expenses ?? 0, 2) }}</td>
                                        <td>{{ format_indian_currency($report->expenses_this_month ?? 0, 2) }}</td>
                                        <td>{{ format_indian_currency($report->balance_amount ?? 0, 2) }}</td>
                                        <td>
                                            <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('general.showReport', $report->report_id) }}"
                                                   class="btn btn-primary btn-sm">
                                                    View
                                                </a>
                                                <a href="{{ route('monthly.report.downloadPdf', $report->report_id) }}"
                                                   class="btn btn-danger btn-sm"
                                                   target="_blank"
                                                   title="Download PDF">
                                                    <i data-feather="file-text"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="15" class="py-4 text-center text-muted">
                                            No approved reports found matching the filters.
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
    // Initialize Feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});

// Export functionality
function exportReports(format) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('export', format);

    // Redirect to current route with export parameter
    window.location.href = currentUrl.toString();
}

// Auto-submit form on filter change
document.getElementById('filterForm')?.addEventListener('change', function(e) {
    if (e.target.matches('select[name="sort_by"], select[name="sort_order"], select[name="per_page"]')) {
        this.submit();
    }
});
</script>
@endpush

@endsection
