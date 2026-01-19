{{-- Reusable Activity History Table Component --}}
@if($activities->count() > 0)
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Related ID</th>
                    <th>Previous Status</th>
                    <th>New Status</th>
                    <th>Changed By</th>
                    <th>Role</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activities as $activity)
                    <tr>
                        <td>
                            {{ $activity->created_at->format('Y-m-d H:i:s') }}
                            <br>
                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                        </td>
                        <td>
                            <span class="badge {{ $activity->type === 'project' ? 'bg-primary' : 'bg-info' }}">
                                {{ ucfirst($activity->type) }}
                            </span>
                        </td>
                        <td>
                            @if($activity->type === 'project')
                                <a href="{{ route('projects.show', $activity->related_id) }}" class="text-decoration-none">
                                    {{ $activity->related_id }}
                                </a>
                            @else
                                <a href="{{ route('monthly.report.show', $activity->related_id) }}" class="text-decoration-none">
                                    {{ $activity->related_id }}
                                </a>
                            @endif
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
    </div>
@else
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No activity history found.
    </div>
@endif
