{{-- System Activity Feed Widget --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Activity Feed</h5>
        <div class="btn-group">
            <select class="form-select form-select-sm" id="activityTypeFilter">
                <option value="">All Activities</option>
                <option value="project">Projects</option>
                <option value="report">Reports</option>
            </select>
            <select class="form-select form-select-sm" id="activityProvinceFilter">
                <option value="">All Provinces</option>
                @if(isset($systemActivityFeedData) && isset($systemActivityFeedData['activities']))
                    @php
                        $provinces = $systemActivityFeedData['activities']
                            ->map(function($activity) {
                                if ($activity->changedBy) {
                                    return $activity->changedBy->province ?? null;
                                }
                                return null;
                            })
                            ->filter()
                            ->unique()
                            ->sort()
                            ->values();
                    @endphp
                    @foreach($provinces as $province)
                        <option value="{{ $province }}">{{ $province }}</option>
                    @endforeach
                @endif
            </select>
            <a href="{{ route('activities.all-activities') }}" class="btn btn-sm btn-primary">View All</a>
        </div>
    </div>
    <div class="card-body">
        @if(isset($systemActivityFeedData) && isset($systemActivityFeedData['activities']) && $systemActivityFeedData['activities']->count() > 0)
            <div class="activity-feed" style="max-height: 500px; overflow-y: auto;">
                @php
                    $grouped = $systemActivityFeedData['grouped_activities'] ?? collect();
                @endphp
                @foreach($grouped as $date => $activities)
                    <div class="activity-date-group mb-3">
                        <h6 class="text-muted mb-2 border-bottom pb-1">{{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</h6>
                        <div class="activity-list">
                            @foreach($activities as $activity)
                                <div class="activity-item mb-3 p-3 border rounded activity-row"
                                     style="background: transparent;"
                                     data-type="{{ $activity->type }}"
                                     data-province="{{ $activity->changedBy->province ?? '' }}">
                                    <div class="d-flex align-items-start">
                                        <div class="activity-icon me-3">
                                            <div class="avatar avatar-sm bg-{{ $activity->color ?? 'primary' }} text-white rounded-circle d-flex align-items-center justify-content-center" style="min-width: 40px; min-height: 40px;">&nbsp;</div>
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
                                                <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                            </div>
                                            <div class="activity-meta">
                                                <span class="badge badge-{{ $activity->type === 'project' ? 'primary' : 'info' }} me-2">{{ ucfirst($activity->type) }}</span>
                                                @if($activity->changedBy && $activity->changedBy->province)
                                                    <span class="badge badge-secondary me-2">{{ $activity->changedBy->province }}</span>
                                                @endif
                                                @if($activity->new_status)
                                                    <span class="badge badge-{{ $activity->color ?? 'secondary' }}">
                                                        {{ ucfirst(str_replace('_', ' ', $activity->new_status)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($activity->type === 'project' && $activity->project)
                                                <div class="mt-2">
                                                    <a href="{{ route('coordinator.projects.show', $activity->related_id) }}" class="btn btn-sm btn-primary">
                                                        View Project
                                                    </a>
                                                </div>
                                            @elseif($activity->type === 'report' && $activity->report)
                                                <div class="mt-2">
                                                    <a href="{{ route('coordinator.monthly.report.show', $activity->related_id) }}" class="btn btn-sm btn-primary">
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
                <p class="text-muted">No recent activities found</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeFilter = document.getElementById('activityTypeFilter');
    const provinceFilter = document.getElementById('activityProvinceFilter');
    const activityRows = document.querySelectorAll('.activity-row');

    function applyFilters() {
        const selectedType = typeFilter ? typeFilter.value : '';
        const selectedProvince = provinceFilter ? provinceFilter.value : '';

        activityRows.forEach(row => {
            const rowType = row.getAttribute('data-type');
            const rowProvince = row.getAttribute('data-province');

            let show = true;

            if (selectedType && rowType !== selectedType) {
                show = false;
            }

            if (selectedProvince && rowProvince !== selectedProvince) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', applyFilters);
    }

    if (provinceFilter) {
        provinceFilter.addEventListener('change', applyFilters);
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
