{{-- Reports Requiring Attention Widget - Dark Theme Compatible --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center position-relative">
        <h5 class="mb-0">Reports Requiring Attention</h5>
        <div class="d-flex align-items-center gap-2">
            @if(isset($reportsRequiringAttention) && $reportsRequiringAttention['total'] > 0)
                <span class="badge bg-warning">{{ $reportsRequiringAttention['total'] }}</span>
            @endif
            <div class="widget-drag-handle ms-2"></div>
        </div>
    </div>
    <div class="card-body">
        @if(!isset($reportsRequiringAttention) || $reportsRequiringAttention['total'] == 0)
            <div class="text-center py-4">
                <p class="text-muted mb-0">No reports require attention. All reports are up to date!</p>
            </div>
        @else
            @php
                $reports = $reportsRequiringAttention['reports'];
                $grouped = $reportsRequiringAttention['grouped'];
            @endphp

            {{-- Draft Reports --}}
            @if($grouped['draft']->count() > 0)
                <div class="mb-4">
                    <h6 class="text-secondary mb-3">Draft Reports ({{ $grouped['draft']->count() }})
                    </h6>
                    <div class="list-group">
                        @foreach($grouped['draft']->take(5) as $report)
                            <div class="list-group-item bg-dark border-secondary">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-white">
                                            @if($report->project)
                                                <a href="{{ route('projects.show', $report->project_id) }}"
                                                   class="text-white text-decoration-none"
                                                   title="View Project">
                                                    {{ $report->project_title ?? $report->project->project_title ?? 'N/A' }}
                                                </a>
                                            @else
                                                {{ $report->project_title ?? 'N/A' }}
                                            @endif
                                            @if($report->project_id)
                                                <small class="text-muted ms-1">(Project:
                                                    <a href="{{ route('projects.show', $report->project_id) }}"
                                                       class="text-info text-decoration-none"
                                                       title="View Project">
                                                        {{ $report->project_id }}
                                                    </a>)
                                                </small>
                                            @endif
                                        </h6>
                                        <p class="mb-1 text-muted small">
                                            <span class="badge bg-secondary me-2">Draft</span>
                                            Report ID:
                                            @if($report->report_id)
                                                <a href="{{ route('monthly.report.show', $report->report_id) }}"
                                                   class="text-info text-decoration-none fw-bold"
                                                   title="View Report">
                                                    {{ $report->report_id }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                            @if($report->report_month_year)
                                                <span class="text-muted ms-2">- {{ $report->report_month_year }}</span>
                                            @endif
                                        </p>
                                        <small class="text-muted">Last updated: {{ $report->updated_at->diffForHumans() }}</small>
                                    </div>
                                    <div>
                                        <a href="{{ route('monthly.report.edit', $report->report_id) }}"
                                           class="btn btn-sm btn-warning">
Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($grouped['draft']->count() > 5)
                        <div class="mt-2 text-center">
                            <a href="{{ route('executor.report.pending') }}?status=draft" class="text-info small">
                                View all {{ $grouped['draft']->count() }} draft reports →
                            </a>
                        </div>
                    @endif
                </div>
            @endif


            {{-- Reverted Reports --}}
            @if($grouped['reverted']->count() > 0)
                <div class="mb-4">
                    <h6 class="text-danger mb-3">Reverted Reports ({{ $grouped['reverted']->count() }})
                    </h6>
                    <div class="list-group">
                        @foreach($grouped['reverted']->take(5) as $report)
                            <div class="list-group-item bg-dark border-danger">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-white">
                                            @if($report->project)
                                                <a href="{{ route('projects.show', $report->project_id) }}"
                                                   class="text-white text-decoration-none"
                                                   title="View Project">
                                                    {{ $report->project_title ?? $report->project->project_title ?? 'N/A' }}
                                                </a>
                                            @else
                                                {{ $report->project_title ?? 'N/A' }}
                                            @endif
                                            @if($report->project_id)
                                                <small class="text-muted ms-1">(Project:
                                                    <a href="{{ route('projects.show', $report->project_id) }}"
                                                       class="text-info text-decoration-none"
                                                       title="View Project">
                                                        {{ $report->project_id }}
                                                    </a>)
                                                </small>
                                            @endif
                                        </h6>
                                        <p class="mb-1 text-muted small">
                                            <span class="badge bg-danger me-2">
                                                {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                                            </span>
                                            Report ID:
                                            @if($report->report_id)
                                                <a href="{{ route('monthly.report.show', $report->report_id) }}"
                                                   class="text-info text-decoration-none fw-bold"
                                                   title="View Report">
                                                    {{ $report->report_id }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                            @if($report->report_month_year)
                                                <span class="text-muted ms-2">- {{ $report->report_month_year }}</span>
                                            @endif
                                        </p>
                                        @if($report->revert_reason)
                                            <small class="text-warning d-block mt-1">{{ Str::limit($report->revert_reason, 100) }}</small>
                                        @endif
                                        <small class="text-muted">Reverted: {{ $report->updated_at->diffForHumans() }}</small>
                                    </div>
                                    <div>
                                        <a href="{{ route('monthly.report.edit', $report->report_id) }}"
                                           class="btn btn-sm btn-danger">
Update
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($grouped['reverted']->count() > 5)
                        <div class="mt-2 text-center">
                            <a href="{{ route('executor.report.pending') }}?status=reverted" class="text-info small">
                                View all {{ $grouped['reverted']->count() }} reverted reports →
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="mt-3 pt-3 border-top border-secondary">
                <div class="row g-2">
                    <div class="col-12">
                        <a href="{{ route('executor.report.pending') }}" class="btn btn-outline-warning btn-sm w-100">
View All Reports Requiring Attention
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
