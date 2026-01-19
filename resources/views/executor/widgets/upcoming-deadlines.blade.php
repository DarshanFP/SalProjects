{{-- Upcoming Report Deadlines Widget - Dark Theme Compatible --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">
                <i data-feather="calendar" class="me-2"></i>
                Upcoming Report Deadlines
            </h5>
            <small class="text-muted">Monthly report submission deadlines</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($upcomingDeadlines['total'] > 0)
                <span class="badge bg-warning">{{ $upcomingDeadlines['total'] }}</span>
            @endif
            <div class="widget-drag-handle ms-2">
                <i data-feather="move" style="width: 16px; height: 16px;" class="text-muted"></i>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if($upcomingDeadlines['total'] == 0)
            <div class="text-center py-4">
                <i data-feather="check-circle" class="text-success" style="width: 48px; height: 48px;"></i>
                <p class="text-muted mt-3 mb-0">No upcoming report deadlines. All reports are up to date!</p>
            </div>
        @else
            {{-- Overdue Report Deadlines --}}
            @if($upcomingDeadlines['overdue']->count() > 0)
                <div class="mb-4">
                    <h6 class="text-danger mb-3">
                        <i data-feather="alert-triangle" class="me-1" style="width: 16px; height: 16px;"></i>
                        Overdue Report Deadlines ({{ $upcomingDeadlines['overdue']->count() }})
                    </h6>
                    <div class="list-group deadline-list" style="max-height: 300px; overflow-y: auto;">
                        @foreach($upcomingDeadlines['overdue'] as $deadline)
                            <div class="list-group-item bg-dark border-danger">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-white">{{ $deadline['project']->project_title }}</h6>
                                        <p class="mb-1 text-muted small">
                                            <span class="badge bg-danger me-2">Overdue</span>
                                            Monthly Report Deadline: {{ $deadline['report_month'] }}
                                        </p>
                                        <small class="text-danger">
                                            <i data-feather="clock" style="width: 12px; height: 12px;"></i>
                                            Report deadline: {{ $deadline['days_overdue'] ?? 0 }} day(s) overdue
                                        </small>
                                    </div>
                                    <div>
                                        <a href="{{ route('monthly.report.create', $deadline['project']->project_id) }}"
                                           class="btn btn-sm btn-danger">
                                            <i data-feather="file-plus" style="width: 14px; height: 14px;"></i>
                                            Create Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- This Month Report Deadlines --}}
            @if($upcomingDeadlines['this_month']->count() > 0)
                <div class="mb-4">
                    <h6 class="text-warning mb-3">
                        <i data-feather="clock" class="me-1" style="width: 16px; height: 16px;"></i>
                        Report Deadlines - Due This Month ({{ $upcomingDeadlines['this_month']->count() }})
                    </h6>
                    <div class="list-group deadline-list" style="max-height: 400px; overflow-y: auto;">
                        @foreach($upcomingDeadlines['this_month'] as $deadline)
                            <div class="list-group-item bg-dark border-warning">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-white">{{ $deadline['project']->project_title }}</h6>
                                        <p class="mb-1 text-muted small">
                                            <span class="badge bg-warning me-2">Due Soon</span>
                                            Monthly Report Deadline: {{ $deadline['report_month'] }}
                                        </p>
                                        <small class="text-warning">
                                            <i data-feather="calendar" style="width: 12px; height: 12px;"></i>
                                            Report deadline: Due in {{ $deadline['days_remaining'] }} day(s)
                                        </small>
                                    </div>
                                    <div>
                                        <a href="{{ route('monthly.report.create', $deadline['project']->project_id) }}"
                                           class="btn btn-sm btn-warning">
                                            <i data-feather="file-plus" style="width: 14px; height: 14px;"></i>
                                            Create Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Next Month Report Deadlines --}}
            @if($upcomingDeadlines['next_month']->count() > 0)
                <div class="mb-3">
                    <h6 class="text-info mb-3">
                        <i data-feather="calendar" class="me-1" style="width: 16px; height: 16px;"></i>
                        Report Deadlines - Due Next Month ({{ $upcomingDeadlines['next_month']->count() }})
                    </h6>
                    <div class="list-group deadline-list" style="max-height: 300px; overflow-y: auto;">
                        @foreach($upcomingDeadlines['next_month'] as $deadline)
                            <div class="list-group-item bg-dark border-info">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-white">{{ $deadline['project']->project_title }}</h6>
                                        <p class="mb-1 text-muted small">
                                            <span class="badge bg-info me-2">Upcoming</span>
                                            Monthly Report Deadline: {{ $deadline['report_month'] }}
                                        </p>
                                        <small class="text-info">
                                            <i data-feather="calendar" style="width: 12px; height: 12px;"></i>
                                            Report deadline: Due in {{ $deadline['days_remaining'] }} day(s)
                                        </small>
                                    </div>
                                    <div>
                                        <a href="{{ route('monthly.report.create', $deadline['project']->project_id) }}"
                                           class="btn btn-sm btn-info">
                                            <i data-feather="file-plus" style="width: 14px; height: 14px;"></i>
                                            Create Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Summary Footer --}}
            <div class="mt-3 pt-3 border-top border-secondary">
                <div class="row text-center">
                    <div class="col-12">
                        <small class="text-muted">
                            <i data-feather="info" style="width: 14px; height: 14px;" class="me-1"></i>
                            Showing all <strong>{{ $upcomingDeadlines['total'] }}</strong> report deadline(s)
                            @php
                                $countParts = [];
                                if($upcomingDeadlines['overdue']->count() > 0) {
                                    $countParts[] = '<span class="text-danger">' . $upcomingDeadlines['overdue']->count() . ' overdue</span>';
                                }
                                if($upcomingDeadlines['this_month']->count() > 0) {
                                    $countParts[] = '<span class="text-warning">' . $upcomingDeadlines['this_month']->count() . ' due this month</span>';
                                }
                                if($upcomingDeadlines['next_month']->count() > 0) {
                                    $countParts[] = '<span class="text-info">' . $upcomingDeadlines['next_month']->count() . ' due next month</span>';
                                }
                            @endphp
                            @if(count($countParts) > 0)
                                - {!! implode(', ', $countParts) !!}
                            @endif
                        </small>
                    </div>
                </div>
            </div>

            {{-- View All Link - Show link to detailed view --}}
            <div class="mt-3 pt-3 border-top border-secondary text-center">
                <a href="{{ route('executor.report.list') }}?show=deadlines" class="btn btn-outline-info btn-sm">
                    <i data-feather="list" style="width: 14px; height: 14px;"></i>
                    View all {{ $upcomingDeadlines['total'] }} report deadlines in detail page →
                </a>
            </div>
        @endif
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
/* Custom scrollbar for deadline lists */
.deadline-list::-webkit-scrollbar {
    width: 6px;
}

.deadline-list::-webkit-scrollbar-track {
    background: #0c1427;
}

.deadline-list::-webkit-scrollbar-thumb {
    background: #41516c;
    border-radius: 3px;
}

.deadline-list::-webkit-scrollbar-thumb:hover {
    background: #7987a1;
}
</style>
