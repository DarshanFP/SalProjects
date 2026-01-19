{{-- Dashboard Customization Settings Panel --}}
<div class="card mb-4" id="dashboardSettingsCard">
    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
        <h5 class="mb-0 text-white">
            <i data-feather="settings" class="me-2"></i>Dashboard Customization
        </h5>
        <button type="button" class="btn btn-sm btn-light" onclick="toggleDashboardSettings()">
            <i data-feather="x"></i> Close
        </button>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="mb-3">Visible Widgets</h6>
                <p class="text-muted small mb-3">Toggle widgets on/off to customize your dashboard view</p>
                
                <div class="list-group" id="widgetToggleList">
                    @php
                        $availableWidgets = [
                            'pending-approvals' => ['label' => 'Pending Approvals', 'icon' => 'clock', 'priority' => 'critical'],
                            'approval-queue' => ['label' => 'Approval Queue', 'icon' => 'inbox', 'priority' => 'critical'],
                            'team-overview' => ['label' => 'Team Overview', 'icon' => 'users', 'priority' => 'critical'],
                            'team-performance' => ['label' => 'Team Performance', 'icon' => 'trending-up', 'priority' => 'medium'],
                            'team-activity-feed' => ['label' => 'Team Activity Feed', 'icon' => 'activity', 'priority' => 'medium'],
                            'team-budget-overview' => ['label' => 'Team Budget Overview', 'icon' => 'dollar-sign', 'priority' => 'medium'],
                            'center-comparison' => ['label' => 'Center Comparison', 'icon' => 'map-pin', 'priority' => 'low'],
                        ];
                    @endphp
                    @foreach($availableWidgets as $widgetId => $widget)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="form-check form-switch">
                                <input class="form-check-input widget-toggle-checkbox" 
                                       type="checkbox" 
                                       id="widget-{{ $widgetId }}"
                                       data-widget-id="{{ $widgetId }}"
                                       checked
                                       onchange="toggleWidgetVisibility('{{ $widgetId }}', this.checked)">
                                <label class="form-check-label ms-2" for="widget-{{ $widgetId }}">
                                    <i data-feather="{{ $widget['icon'] }}" style="width: 16px; height: 16px;" class="me-2"></i>
                                    {{ $widget['label'] }}
                                    <span class="badge bg-{{ $widget['priority'] === 'critical' ? 'danger' : ($widget['priority'] === 'medium' ? 'warning' : 'secondary') }} badge-sm ms-2">
                                        {{ ucfirst($widget['priority']) }}
                                    </span>
                                </label>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary handle" title="Drag to reorder">
                                <i data-feather="move"></i>
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetDashboardLayout()">
                        <i data-feather="rotate-ccw"></i> Reset to Default Layout
                    </button>
                </div>
            </div>

            <div class="col-md-6">
                <h6 class="mb-3">Layout Presets</h6>
                <p class="text-muted small mb-3">Choose a preset layout optimized for different workflows</p>
                
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary text-start" onclick="applyLayoutPreset('default')">
                        <i data-feather="layout" class="me-2"></i>
                        <div>
                            <strong>Default Layout</strong>
                            <br><small class="text-muted">Balanced view with all widgets</small>
                        </div>
                    </button>
                    
                    <button type="button" class="btn btn-outline-primary text-start" onclick="applyLayoutPreset('approval')">
                        <i data-feather="check-circle" class="me-2"></i>
                        <div>
                            <strong>Approval Focus</strong>
                            <br><small class="text-muted">Emphasis on approval widgets</small>
                        </div>
                    </button>
                    
                    <button type="button" class="btn btn-outline-primary text-start" onclick="applyLayoutPreset('analytics')">
                        <i data-feather="bar-chart-2" class="me-2"></i>
                        <div>
                            <strong>Analytics Focus</strong>
                            <br><small class="text-muted">Emphasis on charts and analytics</small>
                        </div>
                    </button>
                    
                    <button type="button" class="btn btn-outline-primary text-start" onclick="applyLayoutPreset('team')">
                        <i data-feather="users" class="me-2"></i>
                        <div>
                            <strong>Team Focus</strong>
                            <br><small class="text-muted">Emphasis on team management</small>
                        </div>
                    </button>
                </div>

                <div class="mt-4">
                    <h6 class="mb-3">Widget Order</h6>
                    <p class="text-muted small mb-3">Drag widgets below to reorder them on the dashboard</p>
                    
                    <div class="list-group" id="widgetOrderList">
                        @foreach($availableWidgets as $widgetId => $widget)
                            <div class="list-group-item widget-order-item" data-widget-id="{{ $widgetId }}" style="cursor: move;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i data-feather="{{ $widget['icon'] }}" style="width: 16px; height: 16px;" class="me-2"></i>
                                        {{ $widget['label'] }}
                                    </div>
                                    <div>
                                        <span class="badge bg-secondary">Position: <span class="widget-position">{{ $loop->iteration }}</span></span>
                                        <i data-feather="grip-vertical" class="ms-2 text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Dashboard customization functionality
// Make toggleDashboardSettings globally available
window.toggleDashboardSettings = function() {
    const settingsRow = document.getElementById('dashboardSettingsRow');
    
    if (settingsRow) {
        settingsRow.style.display = settingsRow.style.display === 'none' ? 'block' : 'none';
        if (settingsRow.style.display === 'block') {
            setTimeout(() => {
                settingsRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }
    
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
};

let widgetOrder = [];

document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Load saved preferences
    loadDashboardPreferences();

    // Initialize drag and drop for widget ordering
    initializeWidgetDragDrop();
});

// Toggle widget visibility
function toggleWidgetVisibility(widgetId, isVisible) {
    const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
    if (widget) {
        const widgetCard = widget.closest('.widget-card');
        if (widgetCard) {
            widgetCard.style.display = isVisible ? '' : 'none';
        }
    }
    
    // Save preferences
    saveDashboardPreferences();
}

// toggleDashboardSettings is defined above as window.toggleDashboardSettings

// Initialize drag and drop
function initializeWidgetDragDrop() {
    const orderList = document.getElementById('widgetOrderList');
    if (!orderList) return;

    // Make items sortable using HTML5 drag and drop
    const items = orderList.querySelectorAll('.widget-order-item');
    
    items.forEach(item => {
        item.draggable = true;
        
        item.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', this.dataset.widgetId);
            this.classList.add('dragging');
        });
        
        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            updateWidgetPositions();
        });
        
        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(orderList, e.clientY);
            const dragging = document.querySelector('.dragging');
            if (dragging && afterElement == null) {
                orderList.appendChild(dragging);
            } else if (dragging && afterElement) {
                orderList.insertBefore(dragging, afterElement);
            }
        });
    });
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.widget-order-item:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function updateWidgetPositions() {
    const orderList = document.getElementById('widgetOrderList');
    const items = orderList.querySelectorAll('.widget-order-item');
    
    items.forEach((item, index) => {
        const positionSpan = item.querySelector('.widget-position');
        if (positionSpan) {
            positionSpan.textContent = index + 1;
        }
    });
    
    // Get new order
    widgetOrder = Array.from(items).map(item => item.dataset.widgetId);
    
    // Reorder widgets on dashboard
    reorderDashboardWidgets(widgetOrder);
    
    // Save preferences
    saveDashboardPreferences();
}

function reorderDashboardWidgets(order) {
    const dashboardContent = document.querySelector('.page-content');
    if (!dashboardContent) return;
    
    const widgets = dashboardContent.querySelectorAll('.widget-card');
    const widgetsArray = Array.from(widgets);
    const widgetsById = {};
    
    widgetsArray.forEach(widget => {
        widgetsById[widget.dataset.widgetId] = widget;
    });
    
    // Remove all widgets from DOM
    widgetsArray.forEach(widget => widget.remove());
    
    // Add widgets back in new order
    order.forEach(widgetId => {
        if (widgetsById[widgetId]) {
            dashboardContent.appendChild(widgetsById[widgetId]);
        }
    });
    
    // Add any widgets not in order list at the end
    Object.keys(widgetsById).forEach(widgetId => {
        if (!order.includes(widgetId)) {
            dashboardContent.appendChild(widgetsById[widgetId]);
        }
    });
}

// Apply layout presets
function applyLayoutPreset(preset) {
    const presets = {
        'default': {
            visible: ['pending-approvals', 'approval-queue', 'team-overview', 'team-performance', 'team-activity-feed', 'team-budget-overview', 'center-comparison'],
            order: ['pending-approvals', 'approval-queue', 'team-overview', 'team-performance', 'team-activity-feed', 'team-budget-overview', 'center-comparison']
        },
        'approval': {
            visible: ['pending-approvals', 'approval-queue', 'team-overview'],
            order: ['pending-approvals', 'approval-queue', 'team-overview']
        },
        'analytics': {
            visible: ['team-performance', 'team-budget-overview', 'center-comparison', 'team-overview'],
            order: ['team-performance', 'team-budget-overview', 'center-comparison', 'team-overview']
        },
        'team': {
            visible: ['team-overview', 'team-activity-feed', 'team-performance', 'center-comparison'],
            order: ['team-overview', 'team-activity-feed', 'team-performance', 'center-comparison']
        }
    };
    
    const selected = presets[preset];
    if (!selected) return;
    
    // Update checkboxes
    document.querySelectorAll('.widget-toggle-checkbox').forEach(checkbox => {
        const widgetId = checkbox.dataset.widgetId;
        checkbox.checked = selected.visible.includes(widgetId);
        toggleWidgetVisibility(widgetId, checkbox.checked);
    });
    
    // Update order
    widgetOrder = selected.order;
    reorderDashboardWidgets(selected.order);
    
    // Update order list display
    updateOrderListDisplay(selected.order);
    
    // Save preferences
    saveDashboardPreferences();
    
    // Show success message
    showNotification('Layout preset applied: ' + preset, 'success');
}

function updateOrderListDisplay(order) {
    const orderList = document.getElementById('widgetOrderList');
    const items = Array.from(orderList.querySelectorAll('.widget-order-item'));
    const itemsById = {};
    
    items.forEach(item => {
        itemsById[item.dataset.widgetId] = item;
    });
    
    orderList.innerHTML = '';
    order.forEach(widgetId => {
        if (itemsById[widgetId]) {
            orderList.appendChild(itemsById[widgetId]);
        }
    });
    
    updateWidgetPositions();
}

// Save dashboard preferences to localStorage
function saveDashboardPreferences() {
    const preferences = {
        visibleWidgets: Array.from(document.querySelectorAll('.widget-toggle-checkbox:checked'))
            .map(cb => cb.dataset.widgetId),
        widgetOrder: widgetOrder.length > 0 ? widgetOrder : Array.from(document.querySelectorAll('.widget-order-item'))
            .map(item => item.dataset.widgetId),
        timestamp: new Date().toISOString()
    };
    
    localStorage.setItem('provincial_dashboard_preferences', JSON.stringify(preferences));
}

// Load dashboard preferences from localStorage
function loadDashboardPreferences() {
    const saved = localStorage.getItem('provincial_dashboard_preferences');
    if (!saved) return;
    
    try {
        const preferences = JSON.parse(saved);
        
        // Apply visible widgets
        document.querySelectorAll('.widget-toggle-checkbox').forEach(checkbox => {
            const widgetId = checkbox.dataset.widgetId;
            const isVisible = preferences.visibleWidgets && preferences.visibleWidgets.includes(widgetId);
            checkbox.checked = isVisible !== false; // Default to true if not specified
            toggleWidgetVisibility(widgetId, checkbox.checked);
        });
        
        // Apply widget order
        if (preferences.widgetOrder && preferences.widgetOrder.length > 0) {
            widgetOrder = preferences.widgetOrder;
            reorderDashboardWidgets(preferences.widgetOrder);
            updateOrderListDisplay(preferences.widgetOrder);
        }
    } catch (e) {
        console.error('Failed to load dashboard preferences:', e);
    }
}

// Reset to default layout
function resetDashboardLayout() {
    if (confirm('Reset dashboard to default layout? This will clear all customizations.')) {
        localStorage.removeItem('provincial_dashboard_preferences');
        location.reload();
    }
}

// Widget toggle (minimize/maximize)
document.addEventListener('click', function(e) {
    if (e.target.closest('.widget-toggle')) {
        const toggle = e.target.closest('.widget-toggle');
        const widgetId = toggle.dataset.widget;
        const widgetCard = document.querySelector(`[data-widget-id="${widgetId}"]`);
        
        if (widgetCard) {
            const content = widgetCard.querySelector('.widget-content');
            const icon = toggle.querySelector('i');
            
            if (content) {
                if (content.style.display === 'none') {
                    content.style.display = '';
                    icon.setAttribute('data-feather', 'chevron-up');
                } else {
                    content.style.display = 'none';
                    icon.setAttribute('data-feather', 'chevron-down');
                }
                if (typeof feather !== 'undefined') feather.replace();
            }
        }
    }
});

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<style>
.widget-order-item {
    transition: background-color 0.2s;
}

.widget-order-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.widget-order-item.dragging {
    opacity: 0.5;
}

.list-group-item {
    cursor: move;
}

.handle {
    cursor: grab;
}

.handle:active {
    cursor: grabbing;
}
</style>
@endpush
