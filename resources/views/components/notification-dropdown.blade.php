@php
    use App\Services\NotificationService;
    if (auth()->check()) {
        $unreadCount = NotificationService::getUnreadCount(auth()->id());
        $recentNotifications = \App\Models\Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    } else {
        $unreadCount = 0;
        $recentNotifications = collect([]);
    }
@endphp

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i data-feather="bell"></i>
        @if($unreadCount > 0)
            <span class="badge bg-danger notification-badge position-absolute top-0 start-100 translate-middle rounded-pill">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </a>
    <ul class="dropdown-menu dropdown-menu-end notification-dropdown-menu bg-dark border-secondary" aria-labelledby="notificationDropdown" style="max-width: 380px; min-width: 320px;">
        <li class="dropdown-header bg-dark border-bottom border-secondary">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-white"><strong>Notifications</strong></span>
                @if($unreadCount > 0)
                    <button class="btn btn-sm btn-link p-0 text-info" onclick="markAllAsReadDropdown()" style="font-size: 0.75rem; text-decoration: none;">
                        <i data-feather="check" style="width: 14px; height: 14px;"></i> Mark all read
                    </button>
                @endif
            </div>
        </li>
        <li><hr class="dropdown-divider border-secondary"></li>
        <div class="notification-list" style="max-height: 350px; overflow-y: auto;">
            @forelse($recentNotifications as $notification)
                <li>
                    <a class="dropdown-item notification-item {{ !$notification->is_read ? 'unread' : '' }} text-white"
                       href="{{ route('notifications.index') }}"
                       onclick="event.preventDefault(); markAsReadDropdown({{ $notification->id }}); window.location.href='{{ route('notifications.index') }}';">
                        <div class="d-flex w-100 justify-content-between align-items-start">
                            <div class="flex-grow-1 me-2">
                                <div class="d-flex align-items-start">
                                    @php
                                        $icon = 'info';
                                        $iconColor = 'text-info';
                                        switch($notification->type) {
                                            case 'approval':
                                                $icon = 'check-circle';
                                                $iconColor = 'text-success';
                                                break;
                                            case 'rejection':
                                            case 'revert':
                                                $icon = 'x-circle';
                                                $iconColor = 'text-danger';
                                                break;
                                            case 'status_change':
                                                $icon = 'refresh-cw';
                                                $iconColor = 'text-warning';
                                                break;
                                            case 'report_submission':
                                                $icon = 'file-text';
                                                $iconColor = 'text-primary';
                                                break;
                                            case 'deadline_reminder':
                                                $icon = 'clock';
                                                $iconColor = 'text-warning';
                                                break;
                                        }
                                    @endphp
                                    <i data-feather="{{ $icon }}" class="{{ $iconColor }} me-2" style="width: 18px; height: 18px; margin-top: 2px;"></i>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-white">{{ $notification->title }}</h6>
                                        <p class="mb-1 small text-muted">{{ Str::limit($notification->message, 70) }}</p>
                                        <small class="text-muted d-flex align-items-center">
                                            <i data-feather="clock" style="width: 12px; height: 12px;" class="me-1"></i>
                                            {{ $notification->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @if(!$notification->is_read)
                                <span class="badge bg-primary rounded-pill ms-2">New</span>
                            @endif
                        </div>
                    </a>
                </li>
            @empty
                <li>
                    <div class="dropdown-item-text text-center py-4">
                        <i data-feather="bell-off" class="text-muted" style="width: 32px; height: 32px;"></i>
                        <p class="text-muted mt-2 mb-0">No notifications</p>
                    </div>
                </li>
            @endforelse
        </div>
        <li><hr class="dropdown-divider border-secondary"></li>
        <li>
            <a class="dropdown-item text-center fw-bold text-info" href="{{ route('notifications.index') }}">
                <i data-feather="list" style="width: 16px; height: 16px;"></i> View All Notifications
            </a>
        </li>
    </ul>
</li>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather icons in notification dropdown
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});

function markAsReadDropdown(notificationId) {
    fetch(`{{ route('notifications.read', ':id') }}`.replace(':id', notificationId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge count
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent.replace('+', '')) || 0;
                const newCount = currentCount - 1;
                if (newCount > 0) {
                    badge.textContent = newCount > 99 ? '99+' : newCount;
                } else {
                    badge.remove();
                }
            }

            // Remove "New" badge from notification item
            const notificationItem = event.target.closest('.notification-item');
            if (notificationItem) {
                notificationItem.classList.remove('unread');
                const newBadge = notificationItem.querySelector('.badge');
                if (newBadge) newBadge.remove();
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

function markAllAsReadDropdown() {
    if (!confirm('Mark all notifications as read?')) return;

    fetch('{{ route('notifications.mark-all-read') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove badge
            const badge = document.querySelector('.notification-badge');
            if (badge) badge.remove();

            // Remove all "New" badges and unread styling
            document.querySelectorAll('.notification-item').forEach(item => {
                item.classList.remove('unread');
                const newBadge = item.querySelector('.badge');
                if (newBadge) newBadge.remove();
            });
        }
    })
    .catch(error => {
        console.error('Error marking all as read:', error);
    });
}

// Auto-refresh notification count every 30 seconds
if (typeof notificationRefreshInterval === 'undefined') {
    var notificationRefreshInterval = setInterval(function() {
        fetch('{{ route('notifications.unread-count') }}')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                const bellLink = document.querySelector('#notificationDropdown');

                if (data.count > 0) {
                    if (badge) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                    } else if (bellLink) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge bg-danger notification-badge position-absolute top-0 start-100 translate-middle rounded-pill';
                        newBadge.textContent = data.count > 99 ? '99+' : data.count;
                        bellLink.appendChild(newBadge);
                    }
                } else {
                    if (badge) badge.remove();
                }
            })
            .catch(error => console.error('Error refreshing notifications:', error));
    }, 30000); // Refresh every 30 seconds
}
</script>

<style>
/* Dark Theme Notification Dropdown Styles */
.notification-dropdown-menu {
    max-width: 380px;
    min-width: 320px;
    background-color: #0c1427 !important;
    border: 1px solid #212a3a !important;
}

.notification-dropdown-menu .dropdown-header {
    background-color: #0c1427 !important;
    border-bottom: 1px solid #212a3a !important;
}

.notification-list {
    max-height: 350px;
    overflow-y: auto;
}

/* Custom scrollbar for dark theme */
.notification-list::-webkit-scrollbar {
    width: 6px;
}

.notification-list::-webkit-scrollbar-track {
    background: #0c1427;
}

.notification-list::-webkit-scrollbar-thumb {
    background: #41516c;
    border-radius: 3px;
}

.notification-list::-webkit-scrollbar-thumb:hover {
    background: #7987a1;
}

.notification-item {
    transition: background-color 0.2s ease;
    border-left: 3px solid transparent;
    padding: 12px 16px !important;
}

.notification-item.unread {
    background-color: rgba(101, 113, 255, 0.1) !important;
    border-left-color: #6571ff !important;
}

.notification-item:hover {
    background-color: rgba(101, 113, 255, 0.15) !important;
}

.notification-item.unread:hover {
    background-color: rgba(101, 113, 255, 0.2) !important;
}

.notification-badge {
    font-size: 0.65rem;
    padding: 3px 6px;
    min-width: 18px;
    text-align: center;
    font-weight: 600;
    top: -2px;
    right: -2px;
}

/* Ensure text is readable on dark background */
.notification-dropdown-menu .text-muted {
    color: #7987a1 !important;
}

.notification-dropdown-menu .text-white {
    color: #d0d6e1 !important;
}

/* Dropdown divider */
.notification-dropdown-menu .dropdown-divider {
    border-color: #212a3a !important;
    margin: 0.5rem 0;
}
</style>
