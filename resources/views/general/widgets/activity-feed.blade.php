@php
    $activityFeedData = $systemActivityFeedData ?? [];
    $activities = $activityFeedData['activities'] ?? collect();
    $groupedActivities = $activityFeedData['grouped_activities'] ?? collect();
    $context = request('activity_context', 'combined');
@endphp

{{-- Unified Activity Feed Widget --}}
<div class="card mb-4 widget-card" data-widget-id="activity-feed">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="activity" class="me-2"></i>Activity Feed
        </h5>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="activityContextFilter" onchange="updateActivityContext()">
                <option value="combined" {{ $context === 'combined' || !$context ? 'selected' : '' }}>All Activities</option>
                <option value="coordinator_hierarchy" {{ $context === 'coordinator_hierarchy' ? 'selected' : '' }}>Coordinator Hierarchy</option>
                <option value="direct_team" {{ $context === 'direct_team' ? 'selected' : '' }}>Direct Team</option>
            </select>
            <select class="form-select form-select-sm" id="activityTypeFilter">
                <option value="">All Types</option>
                <option value="project">Projects</option>
                <option value="report">Reports</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="activity-feed" title="Minimize">
                <i data-feather="chevron-up"></i>
            </button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if($activities->count() > 0)
            <div class="activity-feed" style="max-height: 500px; overflow-y: auto;">
                @foreach($groupedActivities as $date => $dateActivities)
                    <div class="activity-date-group mb-3">
                        <h6 class="text-muted mb-2 border-bottom pb-1">
                            <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                            {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}
                        </h6>
                        <div class="activity-list">
                            @foreach($dateActivities as $activity)
                                <div class="activity-item mb-3 p-3 border rounded activity-row"
                                     style="background: transparent;"
                                     data-type="{{ $activity->type }}"
                                     data-context="{{ $activity->context ?? 'unknown' }}">
                                    <div class="d-flex align-items-start">
                                        <div class="activity-icon me-3">
                                            <div class="avatar avatar-sm bg-{{ $activity->color ?? 'primary' }} text-white rounded-circle d-flex align-items-center justify-content-center">
                                                <i data-feather="{{ $activity->icon ?? 'activity' }}" style="width: 16px; height: 16px;"></i>
                                            </div>
                                        </div>
                                        <div class="activity-content flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <strong class="text-{{ $activity->color ?? 'primary' }}">
                                                        {{ $activity->changedBy->name ?? $activity->changed_by_user_name ?? 'System' }}
                                                    </strong>
                                                    <span class="text-muted ms-2">
                                                        {{ $activity->formatted_message ?? $activity->notes ?? 'Activity' }}
                                                    </span>
                                                </div>
                                                <small class="text-muted">
                                                    <i data-feather="clock" style="width: 12px; height: 12px;"></i>
                                                    {{ $activity->created_at->diffForHumans() }}
                                                </small>
                                            </div>
                                            <div class="activity-meta">
                                                <span class="badge bg-{{ $activity->type === 'project' ? 'primary' : 'info' }} me-2">
                                                    <i data-feather="{{ $activity->type === 'project' ? 'folder' : 'file-text' }}" style="width: 12px; height: 12px;"></i>
                                                    {{ ucfirst($activity->type) }}
                                                </span>
                                                @if(isset($activity->context_label))
                                                    <span class="badge bg-{{ $activity->context === 'coordinator_hierarchy' ? 'primary' : 'success' }} me-2">
                                                        {{ $activity->context_label }}
                                                    </span>
                                                @endif
                                                @if($activity->changedBy && $activity->changedBy->province)
                                                    <span class="badge bg-secondary me-2">
                                                        <i data-feather="map-pin" style="width: 12px; height: 12px;"></i>
                                                        {{ $activity->changedBy->province }}
                                                    </span>
                                                @endif
                                                @if($activity->new_status)
                                                    <span class="badge bg-{{ $activity->color ?? 'secondary' }}">
                                                        {{ ucfirst(str_replace('_', ' ', $activity->new_status)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($activity->type === 'project' && $activity->project)
                                                <div class="mt-2">
                                                    <a href="{{ route('general.showProject', $activity->related_id) }}" class="btn btn-sm btn-primary">
                                                        View Project
                                                    </a>
                                                </div>
                                            @elseif($activity->type === 'report' && $activity->report)
                                                <div class="mt-2">
                                                    <a href="{{ route('general.showReport', $activity->related_id) }}" class="btn btn-sm btn-primary">
                                                        View Report
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-4">
                <i data-feather="activity" class="text-muted" style="width: 48px; height: 48px;"></i>
                <p class="text-muted mt-3">No recent activities found</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function updateActivityContext() {
    const context = document.getElementById('activityContextFilter').value;
    const url = new URL(window.location.href);
    url.searchParams.set('activity_context', context);
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    const typeFilter = document.getElementById('activityTypeFilter');
    const activityRows = document.querySelectorAll('.activity-row');

    function applyFilters() {
        const selectedType = typeFilter ? typeFilter.value : '';

        activityRows.forEach(row => {
            const rowType = row.getAttribute('data-type');

            let show = true;

            if (selectedType && rowType !== selectedType) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', applyFilters);
    }
});
</script>

<style>
.activity-feed {
    scrollbar-width: thin;
    scrollbar-color: #ccc #f1f1f1;
}

.activity-feed::-webkit-scrollbar {
    width: 8px;
}

.activity-feed::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.activity-feed::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}

.activity-feed::-webkit-scrollbar-thumb:hover {
    background: #999;
}

.activity-item {
    transition: all 0.3s ease;
    background: transparent;
}

.activity-item:hover {
    background: rgba(102, 126, 234, 0.05);
    transform: translateX(5px);
}

.avatar {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endpush
