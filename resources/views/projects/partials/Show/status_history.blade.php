{{-- Status History Section --}}
@php
    $statusHistory = $project->statusHistory ?? collect();
@endphp

@if($statusHistory->count() > 0)
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Status History</h4>
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
                        @foreach($statusHistory as $history)
                            <tr>
                                <td>
                                    {{ $history->created_at->format('Y-m-d H:i:s') }}
                                    <br>
                                    <small class="text-muted">{{ $history->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    @if($history->previous_status)
                                        <span class="badge bg-secondary">
                                            {{ $history->previous_status_label }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge
                                        @if($history->new_status === \App\Constants\ProjectStatus::APPROVED_BY_COORDINATOR) bg-success
                                        @elseif($history->new_status === \App\Constants\ProjectStatus::REVERTED_BY_PROVINCIAL || $history->new_status === \App\Constants\ProjectStatus::REVERTED_BY_COORDINATOR) bg-warning
                                        @elseif($history->new_status === \App\Constants\ProjectStatus::REJECTED_BY_COORDINATOR) bg-danger
                                        @elseif($history->new_status === \App\Constants\ProjectStatus::FORWARDED_TO_COORDINATOR) bg-info
                                        @elseif($history->new_status === \App\Constants\ProjectStatus::SUBMITTED_TO_PROVINCIAL) bg-primary
                                        @else bg-secondary
                                        @endif">
                                        {{ $history->new_status_label }}
                                    </span>
                                </td>
                                <td>
                                    {{ $history->changed_by_user_name ?? ($history->changedBy->name ?? 'Unknown') }}
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ ucfirst($history->changed_by_user_role ?? ($history->changedBy->role ?? 'Unknown')) }}
                                    </span>
                                </td>
                                <td>
                                    @if($history->notes)
                                        <span class="text-muted">{{ Str::limit($history->notes, 50) }}</span>
                                        @if(strlen($history->notes) > 50)
                                            <button type="button" class="btn btn-sm btn-link" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="{{ $history->notes }}">
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
        </div>
    </div>
@endif
