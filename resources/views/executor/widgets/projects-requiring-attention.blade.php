{{-- Projects Requiring Attention Widget - Dark Theme Compatible --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center position-relative">
        <h5 class="mb-0">
            <i data-feather="alert-triangle" class="me-2"></i>
            Projects Requiring Attention
        </h5>
        <div class="d-flex align-items-center gap-2">
            @if(isset($projectsRequiringAttention) && $projectsRequiringAttention['total'] > 0)
                <span class="badge bg-danger">{{ $projectsRequiringAttention['total'] }}</span>
            @endif
            <div class="widget-drag-handle ms-2">
                <i data-feather="move" style="width: 16px; height: 16px;" class="text-muted"></i>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if(!isset($projectsRequiringAttention) || $projectsRequiringAttention['total'] == 0)
            <div class="text-center py-4">
                <i data-feather="check-circle" class="text-success" style="width: 48px; height: 48px;"></i>
                <p class="text-muted mt-3 mb-0">No projects require attention. All projects are in good standing!</p>
            </div>
        @else
            @php
                $projects = $projectsRequiringAttention['projects'];
                $grouped = $projectsRequiringAttention['grouped'];
            @endphp

            {{-- Draft Projects --}}
            @if($grouped['draft']->count() > 0)
                <div class="mb-4">
                    <h6 class="text-secondary mb-3">
                        <i data-feather="file" class="me-1" style="width: 16px; height: 16px;"></i>
                        Draft Projects ({{ $grouped['draft']->count() }})
                    </h6>
                    <div class="list-group">
                        @foreach($grouped['draft']->take(5) as $project)
                            <div class="list-group-item bg-dark border-secondary">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-white">
                                            <a href="{{ route('projects.show', $project->project_id) }}"
                                               class="text-white text-decoration-none"
                                               title="View Project">
                                                {{ $project->project_title }}
                                            </a>
                                            <small class="text-muted ms-1">(ID:
                                                <a href="{{ route('projects.show', $project->project_id) }}"
                                                   class="text-info text-decoration-none"
                                                   title="View Project">
                                                    {{ $project->project_id }}
                                                </a>)
                                            </small>
                                        </h6>
                                        <p class="mb-1 text-muted small">
                                            <span class="badge bg-secondary me-2">Draft</span>
                                            @if($project->project_type)
                                                <span class="badge bg-dark me-2">{{ $project->project_type }}</span>
                                            @endif
                                            @if($project->place)
                                                <span class="text-muted">{{ $project->place }}</span>
                                            @endif
                                        </p>
                                        <small class="text-muted">
                                            <i data-feather="calendar" style="width: 12px; height: 12px;"></i>
                                            Last updated: {{ $project->updated_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div>
                                        <a href="{{ route('projects.edit', $project->project_id) }}"
                                           class="btn btn-sm btn-warning">
                                            <i data-feather="edit" style="width: 14px; height: 14px;"></i>
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($grouped['draft']->count() > 5)
                        <div class="mt-2 text-center">
                            <a href="{{ route('executor.dashboard') }}?show=needs_work&status=draft" class="text-info small">
                                View all {{ $grouped['draft']->count() }} draft projects →
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Reverted Projects --}}
            @if($grouped['reverted']->count() > 0)
                <div class="mb-4">
                    <h6 class="text-danger mb-3">
                        <i data-feather="rotate-ccw" class="me-1" style="width: 16px; height: 16px;"></i>
                        Reverted Projects ({{ $grouped['reverted']->count() }})
                    </h6>
                    <div class="list-group">
                        @foreach($grouped['reverted']->take(5) as $project)
                            <div class="list-group-item bg-dark border-danger">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-white">
                                            <a href="{{ route('projects.show', $project->project_id) }}"
                                               class="text-white text-decoration-none"
                                               title="View Project">
                                                {{ $project->project_title }}
                                            </a>
                                            <small class="text-muted ms-1">(ID:
                                                <a href="{{ route('projects.show', $project->project_id) }}"
                                                   class="text-info text-decoration-none"
                                                   title="View Project">
                                                    {{ $project->project_id }}
                                                </a>)
                                            </small>
                                        </h6>
                                        <p class="mb-1 text-muted small">
                                            <span class="badge bg-danger me-2">
                                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                            </span>
                                            @if($project->project_type)
                                                <span class="badge bg-dark me-2">{{ $project->project_type }}</span>
                                            @endif
                                            @if($project->place)
                                                <span class="text-muted">{{ $project->place }}</span>
                                            @endif
                                        </p>
                                        @if($project->revert_reason)
                                            <small class="text-warning d-block mt-1">
                                                <i data-feather="message-square" style="width: 12px; height: 12px;"></i>
                                                {{ Str::limit($project->revert_reason, 100) }}
                                            </small>
                                        @endif
                                        <small class="text-muted">
                                            <i data-feather="calendar" style="width: 12px; height: 12px;"></i>
                                            Reverted: {{ $project->updated_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div>
                                        <a href="{{ route('projects.edit', $project->project_id) }}"
                                           class="btn btn-sm btn-danger">
                                            <i data-feather="edit" style="width: 14px; height: 14px;"></i>
                                            Update
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($grouped['reverted']->count() > 5)
                        <div class="mt-2 text-center">
                            <a href="{{ route('executor.dashboard') }}?show=needs_work" class="text-info small">
                                View all {{ $grouped['reverted']->count() }} reverted projects →
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="mt-3 pt-3 border-top border-secondary">
                <div class="row g-2">
                    <div class="col-12">
                        <a href="{{ route('executor.dashboard') }}?show=needs_work" class="btn btn-outline-danger btn-sm w-100">
                            <i data-feather="folder" style="width: 14px; height: 14px;"></i>
                            View All Projects Requiring Attention
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
