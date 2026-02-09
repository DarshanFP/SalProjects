{{-- Quick Actions Widget - Dark Theme Compatible --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Quick Actions</h5>
        <div class="widget-drag-handle"></div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Create New Project --}}
            <div class="col-12 col-sm-6 col-md-3">
                <a href="{{ route('projects.create') }}" class="btn btn-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3" style="min-height: 100px;">
                    <span class="text-center">Create New Project</span>
                </a>
            </div>

            {{-- View Reports --}}
            <div class="col-12 col-sm-6 col-md-3">
                <a href="{{ route('executor.report.list') }}" class="btn btn-success w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3" style="min-height: 100px;">
                    <span class="text-center">View Reports</span>
                </a>
            </div>

            {{-- View My Reports --}}
            <div class="col-12 col-sm-6 col-md-3">
                <a href="{{ route('executor.report.list') }}" class="btn btn-info w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3" style="min-height: 100px;">
                    <span class="text-center">View My Reports</span>
                </a>
            </div>

            {{-- View Activities --}}
            <div class="col-12 col-sm-6 col-md-3">
                <a href="{{ route('activities.my-activities') }}" class="btn btn-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3" style="min-height: 100px;">
                    <span class="text-center">View Activities</span>
                </a>
            </div>
        </div>
    </div>
</div>
