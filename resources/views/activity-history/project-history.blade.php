@php
    $userRole = Auth::user()->role ?? 'executor';
    $layout = match ($userRole) {
        'provincial' => 'provincial.dashboard',
        'coordinator' => 'coordinator.dashboard',
        default => 'executor.dashboard',
    };
@endphp

@extends($layout)

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="mb-3 card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4>Project Activity History</h4>
                        <p class="text-muted mb-0">Project ID: {{ $project->project_id }}</p>
                        <p class="text-muted mb-0">Title: {{ $project->project_title }}</p>
                    </div>
                    <a href="{{ route('projects.show', $project->project_id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Project
                    </a>
                </div>
                <div class="card-body">
                    @if($activities->count() > 0)
                        @include('activity-history.partials.activity-table', ['activities' => $activities])
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No activity history found for this project.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
