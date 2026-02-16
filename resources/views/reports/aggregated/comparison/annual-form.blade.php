@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Compare Annual Reports (Year-over-Year)</h4>
                    <a href="{{ route('aggregated.annual.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Reports
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('aggregated.annual.compare.submit') }}">
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="report1_id" class="form-label"><strong>Select First Year Report</strong></label>
                                <select name="report1_id" id="report1_id" class="form-select" required>
                                    <option value="">-- Select Report --</option>
                                    @foreach($reports as $report)
                                        <option value="{{ $report->report_id }}">
                                            Year {{ $report->year }} - {{ $report->project_title }} (ID: {{ $report->report_id }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('report1_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="report2_id" class="form-label"><strong>Select Second Year Report</strong></label>
                                <select name="report2_id" id="report2_id" class="form-select" required>
                                    <option value="">-- Select Report --</option>
                                    @foreach($reports as $report)
                                        <option value="{{ $report->report_id }}">
                                            Year {{ $report->year }} - {{ $report->project_title }} (ID: {{ $report->report_id }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('report2_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Select two different annual reports to compare year-over-year performance. The comparison will analyze growth trends, changes in beneficiaries, budget utilization, and overall project progress.
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('aggregated.annual.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-chart-line"></i> Compare Reports
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
