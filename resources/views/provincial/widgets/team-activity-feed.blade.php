@php
    use App\Models\Reports\Monthly\DPReport;
    use App\Constants\ProjectStatus;
@endphp
{{-- Team Activity Feed Widget --}}
<div class="card mb-4 widget-card" data-widget-id="team-activity-feed">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="activity" class="me-2"></i>Team Activity Feed
        </h5>
        <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="team-activity-feed" title="Minimize">
            <i data-feather="chevron-up"></i>
        </button>
        <div>
            <select class="form-select form-select-sm d-inline-block" id="activityTypeFilter" style="width: auto;">
                <option value="">All Activities</option>
                <option value="project">Projects</option>
                <option value="report">Reports</option>
            </select>
            <a href="{{ route('activities.team-activities') }}" class="btn btn-sm btn-outline-primary ms-2">View All</a>
        </div>
    </div>
    <div class="card-body widget-content">
        @if(!isset($teamActivities) || $teamActivities->isEmpty())
            <div class="text-center py-4">
                <i data-feather="inbox" class="text-muted" style="width: 48px; height: 48px;"></i>
                <p class="mt-3 text-muted">No recent team activities</p>
            </div>
        @else
            {{-- Activity Timeline --}}
            <div class="activity-timeline">
                @php
                    $groupedActivities = $teamActivities->groupBy(function($activity) {
                        return $activity->created_at->format('Y-m-d');
                    });
                    $today = now()->format('Y-m-d');
                    $yesterday = now()->subDay()->format('Y-m-d');
                @endphp

                @foreach($groupedActivities->take(7) as $date => $activities)
                    <div class="activity-day-group mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="activity-date-badge">
                                @if($date === $today)
                                    <span class="badge bg-primary">Today</span>
                                @elseif($date === $yesterday)
                                    <span class="badge bg-secondary">Yesterday</span>
                                @else
                                    <span class="badge bg-light text-dark">
                                        {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                                    </span>
                                @endif
                            </div>
                            <hr class="flex-grow-1 ms-2 mb-0">
                        </div>

                        @foreach($activities->take(10) as $activity)
                            @php
                                $icon = $activity->type === 'project' ? 'folder' : 'file-text';
                                $color = $activity->type === 'project' ? 'primary' : 'info';
                                
                                // Get activity description
                                $description = '';
                                if ($activity->type === 'project') {
                                    $description = "Project {$activity->related_id} status changed";
                                } else {
                                    $description = "Report {$activity->related_id} status changed";
                                }
                                
                                if ($activity->previous_status && $activity->new_status) {
                                    if ($activity->type === 'project') {
                                        $prevLabel = \App\Models\OldProjects\Project::$statusLabels[$activity->previous_status] ?? $activity->previous_status;
                                        $newLabel = \App\Models\OldProjects\Project::$statusLabels[$activity->new_status] ?? $activity->new_status;
                                    } else {
                                        $prevLabel = DPReport::$statusLabels[$activity->previous_status] ?? $activity->previous_status;
                                        $newLabel = DPReport::$statusLabels[$activity->new_status] ?? $activity->new_status;
                                    }
                                    $description .= " from {$prevLabel} to {$newLabel}";
                                }
                            @endphp
                            <div class="activity-item mb-3 activity-row" 
                                 data-type="{{ $activity->type }}"
                                 data-status="{{ $activity->new_status }}">
                                <div class="d-flex align-items-start">
                                    <div class="activity-icon me-3">
                                        <div class="rounded-circle bg-{{ $color }} bg-opacity-25 p-2">
                                            <i data-feather="{{ $icon }}" class="text-{{ $color }}" style="width: 20px; height: 20px;"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <p class="mb-1">
                                                    <strong>
                                                        @if($activity->changedBy)
                                                            {{ $activity->changedBy->name }}
                                                        @else
                                                            {{ $activity->changed_by_user_name ?? 'Unknown User' }}
                                                        @endif
                                                    </strong>
                                                    <span class="text-muted ms-2">{{ $description }}</span>
                                                </p>
                                                @if($activity->notes)
                                                    <p class="text-muted small mb-1">
                                                        <i data-feather="message-square" style="width: 14px; height: 14px;" class="me-1"></i>
                                                        {{ Str::limit($activity->notes, 100) }}
                                                    </p>
                                                @endif
                                                <small class="text-muted">
                                                    <i data-feather="clock" style="width: 14px; height: 14px;" class="me-1"></i>
                                                    {{ $activity->created_at->diffForHumans() }}
                                                </small>
                                            </div>
                                            <div class="ms-3">
                                                <span class="badge {{ $activity->new_status_badge_class ?? 'bg-secondary' }}">
                                                    {{ $activity->new_status_label ?? $activity->new_status }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            @if($activity->type === 'project')
                                                <a href="{{ route('provincial.projects.show', $activity->related_id) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i data-feather="eye"></i> View Project
                                                </a>
                                            @else
                                                <a href="{{ route('provincial.monthly.report.show', $activity->related_id) }}" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i data-feather="eye"></i> View Report
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @if($teamActivities->count() > 50)
                <div class="text-center mt-3">
                    <a href="{{ route('activities.team-activities') }}" class="btn btn-sm btn-outline-primary">
                        View All Activities ({{ $teamActivities->count() }} total)
                    </a>
                </div>
            @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Activity type filter
    const activityTypeFilter = document.getElementById('activityTypeFilter');
    if (activityTypeFilter) {
        activityTypeFilter.addEventListener('change', function() {
            const filterValue = this.value;
            document.querySelectorAll('.activity-row').forEach(row => {
                if (!filterValue || row.dataset.type === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>

<style>
.activity-timeline {
    max-height: 600px;
    overflow-y: auto;
    padding-right: 10px;
}

.activity-timeline::-webkit-scrollbar {
    width: 6px;
}

.activity-timeline::-webkit-scrollbar-track {
    background: #1e293b;
    border-radius: 3px;
}

.activity-timeline::-webkit-scrollbar-thumb {
    background: #475569;
    border-radius: 3px;
}

.activity-timeline::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

.activity-item {
    padding: 12px;
    border-left: 2px solid #334155;
    padding-left: 20px;
    transition: all 0.2s;
}

.activity-item:hover {
    background-color: rgba(255, 255, 255, 0.05);
    border-left-color: #6571ff;
}

.activity-icon {
    flex-shrink: 0;
}
</style>
@endpush
