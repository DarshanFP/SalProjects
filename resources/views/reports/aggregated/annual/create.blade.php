@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h4>Generate Annual Report</h4></div>
                <div class="card-body">
                    <form action="{{ route('aggregated.annual.store', $project->project_id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Project</label>
                            <input type="text" class="form-control" value="{{ $project->project_title }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                            <input type="number" name="year" id="year" class="form-control" value="{{ old('year', date('Y')) }}" min="2020" max="{{ date('Y') + 1 }}" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="use_ai" id="use_ai" class="form-check-input" value="1" {{ old('use_ai', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="use_ai">Generate AI-powered insights</label>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
