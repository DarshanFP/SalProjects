{{-- Action Items Widget - Dark Theme Compatible --}}
<div class="card mb-4 h-100 d-flex flex-column equal-height-widget">
    <div class="card-header d-flex justify-content-between align-items-center position-relative">
        <h5 class="mb-0">Action Items</h5>
        <div class="d-flex align-items-center gap-2">
            @if($actionItems['total_pending'] > 0)
                <span class="badge bg-danger">{{ $actionItems['total_pending'] }}</span>
            @endif
            <div class="widget-drag-handle ms-2"></div>
        </div>
    </div>
    <div class="card-body flex-grow-1 action-items-scrollable">
        @if($actionItems['total_pending'] == 0)
            <div class="text-center py-4">
                <p class="text-muted mb-0">No pending action items. Great job!</p>
            </div>
        @else
            {{-- Overdue Reports --}}
            @if($actionItems['overdue_reports']->count() > 0)
                <div class="mb-4">
                    <h6 class="text-danger mb-3">Overdue Reports ({{ $actionItems['overdue_reports']->count() }})
                    </h6>
                    <div class="list-group">
                        @foreach($actionItems['overdue_reports']->take(5) as $item)
                            <div class="list-group-item bg-dark border-danger">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-white">
                                            <a href="{{ route('projects.show', $item['project']->project_id) }}"
                                               class="text-white text-decoration-none"
                                               title="View Project">
                                                {{ $item['project']->project_title }}
                                            </a>
                                            <small class="text-muted ms-1">(ID:
                                                <a href="{{ route('projects.show', $item['project']->project_id) }}"
                                                   class="text-info text-decoration-none"
                                                   title="View Project">
                                                    {{ $item['project']->project_id }}
                                                </a>)
                                            </small>
                                        </h6>
                                        <p class="mb-1 text-muted small">
                                            <span class="badge bg-danger me-2">Overdue</span>
                                            Report for: {{ $item['report_month'] }}
                                        </p>
                                        <small class="text-danger">{{ $item['days_overdue'] ?? 0 }} day(s) overdue</small>
                                    </div>
                                    <div>
                                        <a href="{{ route('monthly.report.create', $item['project']->project_id) }}"
                                           class="btn btn-sm btn-danger">
Create Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($actionItems['overdue_reports']->count() > 5)
                        <div class="mt-2 text-center">
                            <a href="{{ route('executor.report.list') }}" class="text-info small">
                                View all {{ $actionItems['overdue_reports']->count() }} overdue reports →
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Pending Reports --}}
            @if($actionItems['pending_reports']->count() > 0)
                <div class="mb-4">
                    <h6 class="text-warning mb-3">Pending Reports ({{ $actionItems['pending_reports']->count() }})
                    </h6>
                    <div class="list-group">
                        @foreach($actionItems['pending_reports']->take(5) as $report)
                            <div class="list-group-item bg-dark border-warning">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-white">
                                            @if($report->project_id)
                                                <a href="{{ route('projects.show', $report->project_id) }}"
                                                   class="text-white text-decoration-none"
                                                   title="View Project">
                                                    {{ $report->project_title ?? $report->project->project_title ?? 'N/A' }}
                                                </a>
                                            @else
                                                {{ $report->project_title ?? $report->project->project_title ?? 'N/A' }}
                                            @endif
                                            @if($report->project_id)
                                                <small class="text-muted ms-1">(ID:
                                                    <a href="{{ route('projects.show', $report->project_id) }}"
                                                       class="text-info text-decoration-none"
                                                       title="View Project">
                                                        {{ $report->project_id }}
                                                    </a>)
                                                </small>
                                            @endif
                                        </h6>
                                        <p class="mb-1 text-muted small">
                                            <span class="badge bg-{{ $report->status === 'draft' ? 'secondary' : 'danger' }} me-2">
                                                {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                                            </span>
                                            Report:
                                            @if($report->report_id)
                                                <a href="{{ route('monthly.report.show', $report->report_id) }}"
                                                   class="text-info text-decoration-none fw-bold"
                                                   title="View Report">
                                                    {{ $report->report_id }}
                                                </a>
                                                @if($report->report_month_year)
                                                    <span class="text-muted"> - {{ $report->report_month_year }}</span>
                                                @endif
                                            @else
                                                {{ $report->report_month_year ?? 'N/A' }}
                                            @endif
                                        </p>
                                    </div>
                                    <div>
                                        @if($report->status === 'draft' || $report->isEditable())
                                            <form action="{{ route('executor.report.submit', $report->report_id) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to submit this report?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">Submit</button>
                                            </form>
                                        @else
                                            <a href="{{ route('monthly.report.edit', $report->report_id) }}"
                                               class="btn btn-sm btn-warning">
Edit
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($actionItems['pending_reports']->count() > 5)
                        <div class="mt-2 text-center">
                            <a href="{{ route('executor.report.pending') }}" class="text-info small">
                                View all {{ $actionItems['pending_reports']->count() }} pending reports →
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Reverted Projects --}}
            @if($actionItems['reverted_projects']->count() > 0)
                <div class="mb-4">
                    <h6 class="text-danger mb-3">Reverted Projects ({{ $actionItems['reverted_projects']->count() }})
                    </h6>
                    <div class="list-group">
                        @foreach($actionItems['reverted_projects']->take(5) as $project)
                            <div class="list-group-item bg-dark border-danger">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-white">{{ $project->project_title }}</h6>
                                        <p class="mb-1 text-muted small">
                                            <span class="badge bg-danger me-2">
                                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                            </span>
                                            Project ID:
                                            <a href="{{ route('projects.show', $project->project_id) }}"
                                               class="text-info text-decoration-none fw-bold"
                                               title="View Project">
                                                {{ $project->project_id }}
                                            </a>
                                        </p>
                                        @if($project->revert_reason)
                                            <small class="text-muted">{{ Str::limit($project->revert_reason, 100) }}</small>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('projects.edit', $project->project_id) }}"
                                           class="btn btn-sm btn-danger">
Update
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($actionItems['reverted_projects']->count() > 5)
                        <div class="mt-2 text-center">
                            <a href="{{ route('executor.dashboard') }}?status=reverted" class="text-info small">
                                View all {{ $actionItems['reverted_projects']->count() }} reverted projects →
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="mt-3 pt-3 border-top border-secondary">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="{{ route('executor.report.pending') }}" class="btn btn-outline-warning btn-sm w-100">
View All Pending
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('executor.dashboard') }}?status=reverted" class="btn btn-outline-danger btn-sm w-100">
View Reverted
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
