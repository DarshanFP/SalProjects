{{-- Activity History Section for Reports --}}
@php
    $activities = $report->activityHistory ?? collect();
@endphp

@if($activities->count() > 0)
    <div class="mb-3 card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Activity History</h4>
            <a href="{{ route('reports.activity-history', $report->report_id) }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-external-link-alt me-1"></i>View Full History
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Previous Status</th>
                            <th>New Status</th>
                            <th>Changed By</th>
                            <th>Role</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities->take(10) as $activity)
                            <tr>
                                <td>
                                    {{ $activity->created_at->format('Y-m-d H:i:s') }}
                                    <br>
                                    <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    @if($activity->previous_status)
                                        <span class="badge {{ $activity->previous_status_badge_class }}">
                                            {{ $activity->previous_status_label }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $activity->new_status_badge_class }}">
                                        {{ $activity->new_status_label }}
                                    </span>
                                </td>
                                <td>
                                    {{ $activity->changed_by_user_name ?? ($activity->changedBy->name ?? 'Unknown') }}
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ ucfirst($activity->changed_by_user_role ?? ($activity->changedBy->role ?? 'Unknown')) }}
                                    </span>
                                </td>
                                <td>
                                    @if($activity->notes)
                                        <span class="text-muted">{{ Str::limit($activity->notes, 50) }}</span>
                                        @if(strlen($activity->notes) > 50)
                                            <button type="button" class="btn btn-sm btn-link" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="{{ $activity->notes }}">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($activities->count() > 10)
                    <div class="text-center mt-3">
                        <a href="{{ route('reports.activity-history', $report->report_id) }}" class="btn btn-secondary">
                            View All {{ $activities->count() }} Activities
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Activity History</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No activity history found for this report.
            </div>
        </div>
    </div>
@endif
