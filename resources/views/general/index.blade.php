@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    {{-- Success/Error Messages --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {!! session('success') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Budget Overview (First Priority) --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            @include('general.widgets.budget-overview')
                        </div>
                    </div>

                    {{-- Actions Required (Second Priority) --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            @include('general.widgets.pending-approvals')
                        </div>
                    </div>

                    {{-- Overview & Management (Third Priority) --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            @include('general.widgets.coordinator-overview')
                            @include('general.widgets.direct-team-overview')
                        </div>
                    </div>

                    {{-- Analytics & Performance (Last Priority) --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            @include('general.widgets.system-performance')
                            @include('general.widgets.system-analytics')
                            @include('general.widgets.budget-charts')
                            @include('general.widgets.context-comparison')
                        </div>
                    </div>

                    {{-- System Health --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            @include('general.widgets.system-health')
                        </div>
                    </div>

                    {{-- Activity Feed (Last Widget) --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            @include('general.widgets.activity-feed')
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Widget Toggle Functionality (Minimize/Maximize)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Widget toggle click handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.widget-toggle')) {
            const toggle = e.target.closest('.widget-toggle');
            const widgetId = toggle.dataset.widget;
            const widgetCard = document.querySelector(`[data-widget-id="${widgetId}"]`);

            if (widgetCard) {
                const widgetContent = widgetCard.querySelector('.widget-content');
                const icon = toggle.querySelector('i');

                if (widgetContent) {
                    if (widgetContent.style.display === 'none') {
                        widgetContent.style.display = '';
                        if (icon) {
                            icon.setAttribute('data-feather', 'chevron-up');
                            if (typeof feather !== 'undefined') {
                                feather.replace();
                            }
                        }
                        toggle.title = 'Minimize';
                    } else {
                        widgetContent.style.display = 'none';
                        if (icon) {
                            icon.setAttribute('data-feather', 'chevron-down');
                            if (typeof feather !== 'undefined') {
                                feather.replace();
                            }
                        }
                        toggle.title = 'Maximize';
                    }
                }
            }
        }
    });

    // Resize charts when widgets are toggled
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                // Trigger chart resize if ApexCharts is available
                if (typeof ApexCharts !== 'undefined') {
                    setTimeout(function() {
                        window.dispatchEvent(new Event('resize'));
                    }, 100);
                }
            }
        });
    });

    // Observe all widget content elements
    document.querySelectorAll('.widget-content').forEach(function(element) {
        observer.observe(element, {
            attributes: true,
            attributeFilter: ['style']
        });
    });
});
</script>
@endpush

@push('styles')
<style>
/* Widget Card Styling */
.widget-card {
    transition: all 0.3s ease;
}

.widget-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.widget-content {
    transition: all 0.3s ease;
}

/* Loading State */
.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    color: #6c757d;
}

.loading-state i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Empty State Styling */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    opacity: 0.3;
    margin-bottom: 1rem;
}

.empty-state p {
    margin: 0;
    font-size: 0.95rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .widget-card .card-header {
        flex-direction: column;
        gap: 0.5rem;
    }

    .widget-card .card-header h5 {
        font-size: 1rem;
    }

    .widget-toggle {
        align-self: flex-end;
    }
}

/* Smooth scroll for activity feed */
.activity-feed {
    scroll-behavior: smooth;
}

/* Table responsive improvements */
.table-responsive {
    border-radius: 0.375rem;
}

/* Badge improvements */
.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

/* Button improvements */
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Card header improvements */
.card-header {
    background-color: rgba(0, 0, 0, 0.03);
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

/* Progress bar improvements */
.progress {
    border-radius: 0.5rem;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.6s ease;
}
</style>
@endpush
@endsection
