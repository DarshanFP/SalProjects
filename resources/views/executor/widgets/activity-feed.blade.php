{{-- Recent Activity Feed Widget - Dark Theme Compatible --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Activity</h5>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('activities.my-activities') }}" class="text-info small">
                View All →
            </a>
            <div class="widget-drag-handle ms-2"></div>
        </div>
    </div>
    <div class="card-body">
        @if(isset($recentActivities) && $recentActivities->count() > 0)
            <div class="activity-timeline" style="max-height: 400px; overflow-y: auto;">
                @foreach($recentActivities as $activity)
                    <div class="activity-item mb-3 pb-3 border-bottom border-secondary">
                        <div class="d-flex align-items-start">
                            {{-- Activity Icon --}}
                            <div class="activity-icon me-3">
                                @php
                                    $icon = 'info';
                                    $iconColor = 'text-info';
                                    $bgColor = 'bg-info bg-opacity-25';
                                    
                                    if ($activity->type === 'project') {
                                        if ($activity->previous_status && $activity->new_status && $activity->previous_status !== $activity->new_status) {
                                            $icon = 'refresh-cw';
                                            $iconColor = 'text-warning';
                                            $bgColor = 'bg-warning bg-opacity-25';
                                            
                                            // Status-specific icons
                                            if (str_contains($activity->new_status, 'approved')) {
                                                $icon = 'check-circle';
                                                $iconColor = 'text-success';
                                                $bgColor = 'bg-success bg-opacity-25';
                                            } elseif (str_contains($activity->new_status, 'reverted') || str_contains($activity->new_status, 'rejected')) {
                                                $icon = 'x-circle';
                                                $iconColor = 'text-danger';
                                                $bgColor = 'bg-danger bg-opacity-25';
                                            }
                                        } else {
                                            $icon = 'folder';
                                            $iconColor = 'text-primary';
                                            $bgColor = 'bg-primary bg-opacity-25';
                                        }
                                    } elseif ($activity->type === 'report') {
                                        $icon = 'file-text';
                                        $iconColor = 'text-info';
                                        $bgColor = 'bg-info bg-opacity-25';
                                        
                                        if ($activity->previous_status && $activity->new_status && $activity->previous_status !== $activity->new_status) {
                                            if (str_contains($activity->new_status, 'approved')) {
                                                $icon = 'check-circle';
                                                $iconColor = 'text-success';
                                                $bgColor = 'bg-success bg-opacity-25';
                                            } elseif (str_contains($activity->new_status, 'reverted')) {
                                                $icon = 'rotate-ccw';
                                                $iconColor = 'text-warning';
                                                $bgColor = 'bg-warning bg-opacity-25';
                                            }
                                        }
                                    }
                                @endphp
                                <div class="rounded-circle p-2 {{ $bgColor }}" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">&nbsp;</div>
                            </div>

                            {{-- Activity Content --}}
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <h6 class="mb-0 text-white">
                                            @if($activity->type === 'project')
                                                <a href="{{ route('projects.show', $activity->related_id) }}" 
                                                   class="text-white text-decoration-none"
                                                   title="View Project">
                                                    Project {{ $activity->related_id }}
                                                </a>
                                            @else
                                                <a href="{{ route('monthly.report.show', $activity->related_id) }}" 
                                                   class="text-white text-decoration-none"
                                                   title="View Report">
                                                    Report {{ $activity->related_id }}
                                                </a>
                                            @endif
                                        </h6>
                                        @if($activity->previous_status && $activity->new_status && $activity->previous_status !== $activity->new_status)
                                            <p class="mb-1 small text-muted">
                                                Status changed: 
                                                <span class="badge {{ $activity->previous_status_badge_class ?? 'bg-secondary' }} me-1">
                                                    {{ ucfirst(str_replace('_', ' ', $activity->previous_status ?? 'N/A')) }}
                                                </span>
                                                <span class="mx-1">→</span>
                                                <span class="badge {{ $activity->new_status_badge_class ?? 'bg-primary' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $activity->new_status ?? 'N/A')) }}
                                                </span>
                                            </p>
                                        @else
                                            <p class="mb-1 small text-muted">
                                                {{ $activity->notes ?? ($activity->type === 'project' ? 'Project updated' : 'Report updated') }}
                                            </p>
                                        @endif
                                    </div>
                                    <small class="text-muted ms-2">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </small>
                                </div>

                                {{-- Activity Details --}}
                                <div class="d-flex align-items-center">
                                    <small class="text-muted me-2">{{ $activity->changed_by_user_name ?? 'System' }}</small>
                                    @if($activity->type === 'project' && $activity->related_id)
                                        <a href="{{ route('projects.show', $activity->related_id) }}" class="text-info small ms-2">
                                            View Project
                                        </a>
                                    @elseif($activity->type === 'report' && $activity->related_id)
                                        <a href="{{ route('monthly.report.edit', $activity->related_id) }}" class="text-info small ms-2">
                                            View Report
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- View All Link --}}
            <div class="mt-3 pt-3 border-top border-secondary text-center">
                <a href="{{ route('activities.my-activities') }}" class="btn btn-outline-primary btn-sm">View All Activities</a>
            </div>
        @else
            <div class="text-center py-4">
                <p class="text-muted mb-0">No recent activities</p>
                <a href="{{ route('activities.my-activities') }}" class="btn btn-sm btn-outline-primary mt-2">
                    View Activity History
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
/* Activity Timeline Styling */
.activity-timeline {
    position: relative;
}

.activity-item {
    position: relative;
    padding-left: 0;
}

.activity-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 19px;
    top: 48px;
    bottom: -12px;
    width: 2px;
    background: #212a3a;
}

.activity-icon {
    position: relative;
    z-index: 1;
}

/* Custom scrollbar for activity timeline */
.activity-timeline::-webkit-scrollbar {
    width: 6px;
}

.activity-timeline::-webkit-scrollbar-track {
    background: #0c1427;
}

.activity-timeline::-webkit-scrollbar-thumb {
    background: #41516c;
    border-radius: 3px;
}

.activity-timeline::-webkit-scrollbar-thumb:hover {
    background: #7987a1;
}
</style>
