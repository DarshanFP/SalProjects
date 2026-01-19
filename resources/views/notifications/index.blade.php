@extends('profileAll.app')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="feather icon-bell me-2"></i>Notifications
                        @if($unreadCount > 0)
                            <span class="badge bg-danger ms-2">{{ $unreadCount }} unread</span>
                        @endif
                    </h4>
                    <div class="btn-group">
                        @if($unreadCount > 0)
                            <button class="btn btn-sm btn-primary" onclick="markAllAsRead()">
                                <i class="feather icon-check"></i> Mark All Read
                            </button>
                        @endif
                        <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#preferencesModal">
                            <i class="feather icon-settings"></i> Preferences
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                <a href="{{ route('notifications.index', ['filter' => 'all']) }}"
                                   class="btn btn-sm {{ !request('filter') || request('filter') == 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    All
                                </a>
                                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                                   class="btn btn-sm {{ request('filter') == 'unread' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    Unread
                                </a>
                                <a href="{{ route('notifications.index', ['filter' => 'read']) }}"
                                   class="btn btn-sm {{ request('filter') == 'read' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    Read
                                </a>
                            </div>
                            <div class="btn-group ms-2" role="group">
                                <select class="form-select form-select-sm" onchange="filterByType(this.value)" style="width: auto; display: inline-block;">
                                    <option value="">All Types</option>
                                    <option value="approval" {{ request('type') == 'approval' ? 'selected' : '' }}>Approvals</option>
                                    <option value="rejection" {{ request('type') == 'rejection' ? 'selected' : '' }}>Rejections</option>
                                    <option value="status_change" {{ request('type') == 'status_change' ? 'selected' : '' }}>Status Changes</option>
                                    <option value="report_submission" {{ request('type') == 'report_submission' ? 'selected' : '' }}>Report Submissions</option>
                                    <option value="revert" {{ request('type') == 'revert' ? 'selected' : '' }}>Reverts</option>
                                    <option value="deadline_reminder" {{ request('type') == 'deadline_reminder' ? 'selected' : '' }}>Deadline Reminders</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications List -->
                    @forelse($notifications as $notification)
                        <div class="card mb-2 notification-item {{ !$notification->is_read ? 'border-start border-primary border-3' : '' }}"
                             id="notification-{{ $notification->id }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <h6 class="mb-0 me-2">{{ $notification->title }}</h6>
                                            @if(!$notification->is_read)
                                                <span class="badge bg-primary">New</span>
                                            @endif
                                            <span class="badge bg-secondary ms-2">{{ ucfirst(str_replace('_', ' ', $notification->type)) }}</span>
                                        </div>
                                        <p class="text-muted mb-2">{{ $notification->message }}</p>
                                        <small class="text-muted">
                                            <i class="feather icon-clock"></i> {{ $notification->created_at->diffForHumans() }}
                                            @if($notification->related_type && $notification->related_id)
                                                |
                                                @if($notification->related_type == 'project')
                                                    <a href="{{ route('projects.show', $notification->related_id) }}" class="text-primary">
                                                        View Project
                                                    </a>
                                                @elseif($notification->related_type == 'report')
                                                    <a href="{{ route('monthly.report.show', $notification->related_id) }}" class="text-primary">
                                                        View Report
                                                    </a>
                                                @endif
                                            @endif
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <div class="btn-group-vertical">
                                            @if(!$notification->is_read)
                                                <button class="btn btn-sm btn-outline-primary"
                                                        onclick="markAsRead({{ $notification->id }})"
                                                        title="Mark as read">
                                                    <i class="feather icon-check"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteNotification({{ $notification->id }})"
                                                    title="Delete">
                                                <i class="feather icon-trash-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="feather icon-bell-off" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-3">No notifications found.</p>
                        </div>
                    @endforelse

                    <!-- Pagination -->
                    @if($notifications->hasPages())
                        <div class="mt-4">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preferences Modal -->
<div class="modal fade" id="preferencesModal" tabindex="-1" aria-labelledby="preferencesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="preferencesModalLabel">Notification Preferences</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="preferencesForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="email_notifications"
                                   name="email_notifications" {{ $preferences->email_notifications ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_notifications">
                                Email Notifications
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="in_app_notifications"
                                   name="in_app_notifications" {{ $preferences->in_app_notifications ? 'checked' : '' }}>
                            <label class="form-check-label" for="in_app_notifications">
                                In-App Notifications
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notification_frequency" class="form-label">Notification Frequency</label>
                        <select class="form-select" id="notification_frequency" name="notification_frequency">
                            <option value="immediate" {{ $preferences->notification_frequency == 'immediate' ? 'selected' : '' }}>Immediate</option>
                            <option value="daily" {{ $preferences->notification_frequency == 'daily' ? 'selected' : '' }}>Daily Digest</option>
                            <option value="weekly" {{ $preferences->notification_frequency == 'weekly' ? 'selected' : '' }}>Weekly Digest</option>
                        </select>
                    </div>
                    <hr>
                    <h6>Notification Types</h6>
                    <div class="mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="status_change_notifications"
                                   name="status_change_notifications" {{ $preferences->status_change_notifications ? 'checked' : '' }}>
                            <label class="form-check-label" for="status_change_notifications">
                                Status Changes
                            </label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="report_submission_notifications"
                                   name="report_submission_notifications" {{ $preferences->report_submission_notifications ? 'checked' : '' }}>
                            <label class="form-check-label" for="report_submission_notifications">
                                Report Submissions
                            </label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="approval_notifications"
                                   name="approval_notifications" {{ $preferences->approval_notifications ? 'checked' : '' }}>
                            <label class="form-check-label" for="approval_notifications">
                                Approvals
                            </label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="rejection_notifications"
                                   name="rejection_notifications" {{ $preferences->rejection_notifications ? 'checked' : '' }}>
                            <label class="form-check-label" for="rejection_notifications">
                                Rejections
                            </label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="deadline_reminder_notifications"
                                   name="deadline_reminder_notifications" {{ $preferences->deadline_reminder_notifications ? 'checked' : '' }}>
                            <label class="form-check-label" for="deadline_reminder_notifications">
                                Deadline Reminders
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Preferences</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    fetch(`/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notification = document.getElementById(`notification-${notificationId}`);
            if (notification) {
                notification.classList.remove('border-start', 'border-primary', 'border-3');
                const badge = notification.querySelector('.badge.bg-primary');
                if (badge) badge.remove();
                const markReadBtn = notification.querySelector('button[onclick*="markAsRead"]');
                if (markReadBtn) markReadBtn.remove();
            }
            updateUnreadCount();
        }
    })
    .catch(error => console.error('Error:', error));
}

function markAllAsRead() {
    if (!confirm('Mark all notifications as read?')) return;

    fetch('/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function deleteNotification(notificationId) {
    if (!confirm('Delete this notification?')) return;

    fetch(`/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notification = document.getElementById(`notification-${notificationId}`);
            if (notification) {
                notification.remove();
            }
            updateUnreadCount();
        }
    })
    .catch(error => console.error('Error:', error));
}

function filterByType(type) {
    const url = new URL(window.location.href);
    if (type) {
        url.searchParams.set('type', type);
    } else {
        url.searchParams.delete('type');
    }
    window.location.href = url.toString();
}

function updateUnreadCount() {
    fetch('/notifications/unread-count')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                } else {
                    const header = document.querySelector('.card-header h4');
                    if (header) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge bg-danger ms-2 notification-badge';
                        newBadge.textContent = data.count + ' unread';
                        header.appendChild(newBadge);
                    }
                }
            } else {
                if (badge) badge.remove();
            }
        })
        .catch(error => console.error('Error:', error));
}

// Handle preferences form submission
document.getElementById('preferencesForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value === 'on' ? true : value;
    });

    fetch('/notifications/preferences', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Preferences updated successfully!');
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
});
</script>

<style>
.notification-item {
    transition: all 0.3s ease;
}

.notification-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>
@endsection
