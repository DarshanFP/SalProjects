@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Generate Quarterly Report</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('aggregated.quarterly.store', $project->project_id) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Project</label>
                            <input type="text" class="form-control" value="{{ $project->project_title }}" readonly>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quarter" class="form-label">Quarter <span class="text-danger">*</span></label>
                                    <select name="quarter" id="quarter" class="form-control @error('quarter') is-invalid @enderror" required>
                                        <option value="">Select Quarter</option>
                                        @foreach([1,2,3,4] as $q)
                                            <option value="{{ $q }}" {{ old('quarter') == $q ? 'selected' : '' }}>Q{{ $q }}</option>
                                        @endforeach
                                    </select>
                                    @error('quarter')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                                    <input type="number" name="year" id="year" class="form-control @error('year') is-invalid @enderror"
                                           value="{{ old('year', date('Y')) }}" min="2020" max="{{ date('Y') + 1 }}" required>
                                    @error('year')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="use_ai" id="use_ai" class="form-check-input" value="1" {{ old('use_ai', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="use_ai">
                                    Generate AI-powered insights and summaries
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                AI will generate executive summary, key achievements, trends, challenges, and recommendations.
                            </small>
                        </div>

                        @if($monthlyReports->isNotEmpty())
                            <div class="alert alert-info">
                                <strong>Available Monthly Reports:</strong>
                                <ul class="mb-0">
                                    @foreach($monthlyReports as $period => $reports)
                                        <li>{{ $period }}: {{ $reports->count() }} report(s)</li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                No approved monthly reports found for this project. Please ensure monthly reports are approved before generating quarterly reports.
                            </div>
                        @endif

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
