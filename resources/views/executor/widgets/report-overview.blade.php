{{-- Report Overview Widget - Dark Theme Compatible --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Report Overview</h5>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('executor.report.list') }}" class="text-info small">
                View All →
            </a>
            <div class="widget-drag-handle ms-2"></div>
        </div>
    </div>
    <div class="card-body">
        {{-- Report Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card bg-secondary bg-opacity-25 border-secondary">
                    <div class="card-body p-3 text-center">
                        <small class="text-muted d-block">Total Reports</small>
                        <h4 class="mb-0 text-white">{{ $reportStatusSummary['total'] ?? 0 }}</h4>
                        <small class="text-muted">All types</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning bg-opacity-25 border-warning">
                    <div class="card-body p-3 text-center">
                        <small class="text-muted d-block">Pending Reports</small>
                        <h4 class="mb-0 text-white">{{ $reportStatusSummary['pending_count'] ?? 0 }}</h4>
                        <small class="text-muted">Need attention</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success bg-opacity-25 border-success">
                    <div class="card-body p-3 text-center">
                        <small class="text-muted d-block">Approved Reports</small>
                        <h4 class="mb-0 text-white">{{ $reportStatusSummary['approved_count'] ?? 0 }}</h4>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Reports Table (Phase 3: owned scope only; controller-passed $recentReports) --}}
        <div class="mb-3">
            <h6 class="mb-3">Recent Reports</h6>
            @if(isset($recentReports) && $recentReports->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Report ID</th>
                                <th>Project</th>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentReports as $report)
                                <tr>
                                    <td>
                                        <a href="{{ route('monthly.report.show', $report->report_id) }}"
                                           class="text-primary text-decoration-none fw-bold"
                                           title="View Report">
                                            <small>{{ $report->report_id }}</small>
                                        </a>
                                    </td>
                                    <td>
                                        @if($report->project_id)
                                            <a href="{{ route('projects.show', $report->project_id) }}"
                                               class="text-white text-decoration-none"
                                               title="View Project">
                                                <small>{{ Str::limit($report->project_title ?? ($report->project->project_title ?? 'N/A'), 30) }}</small>
                                            </a>
                                            <br>
                                            <small class="text-muted">ID:
                                                <a href="{{ route('projects.show', $report->project_id) }}"
                                                   class="text-info text-decoration-none"
                                                   title="View Project">
                                                    {{ $report->project_id }}
                                                </a>
                                            </small>
                                        @else
                                            <small class="text-white">{{ Str::limit($report->project_title ?? ($report->project->project_title ?? 'N/A'), 30) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $report->report_month_year ? \Carbon\Carbon::parse($report->report_month_year)->format('M Y') : 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{
                                            $report->isApproved() ? 'success' :
                                            ($report->status === App\Models\Reports\Monthly\DPReport::STATUS_DRAFT ? 'warning' :
                                            (str_contains($report->status, 'reverted') ? 'danger' : 'secondary'))
                                        }}">
                                            {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $report->created_at->format('M d, Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @if($report->status === 'draft' || $report->isEditable())
                                                <form action="{{ route('executor.report.submit', $report->report_id) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to submit this report?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary" title="Submit">Submit</button>
                                                </form>
                                            @elseif(in_array($report->status, [App\Models\Reports\Monthly\DPReport::STATUS_DRAFT, App\Models\Reports\Monthly\DPReport::STATUS_REVERTED_BY_PROVINCIAL, App\Models\Reports\Monthly\DPReport::STATUS_REVERTED_BY_COORDINATOR]))
                                                <a href="{{ route('monthly.report.edit', $report->report_id) }}"
                                                   class="btn btn-sm btn-warning" title="Edit">Edit</a>
                                            @endif
                                            @if(\Route::has('monthly.report.show'))
                                                <a href="{{ route('monthly.report.show', $report->report_id) }}"
                                                   class="btn btn-sm btn-info" title="View">View</a>
                                            @else
                                                <a href="{{ route('monthly.report.edit', $report->report_id) }}"
                                                   class="btn btn-sm btn-info" title="View">View</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-3">
                    <p class="text-muted mb-0">No reports yet</p>
                </div>
            @endif
        </div>

        {{-- Quick Links --}}
        <div class="mt-3 pt-3 border-top border-secondary">
            <div class="row g-2">
                <div class="col-6">
                    <a href="{{ route('executor.report.pending') }}" class="btn btn-outline-warning btn-sm w-100">
Pending Reports
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('executor.report.approved') }}" class="btn btn-outline-success btn-sm w-100">
Approved Reports
                    </a>
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
</script>
@endpush

<style>
.table-sm th,
.table-sm td {
    padding: 0.5rem;
    font-size: 0.875rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(101, 113, 255, 0.1);
}
</style>
