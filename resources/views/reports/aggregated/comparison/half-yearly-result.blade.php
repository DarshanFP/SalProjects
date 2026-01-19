@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Half-Yearly Report Comparison</h4>
                    <div>
                        <a href="{{ route('aggregated.comparison.half-yearly-form') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-redo"></i> Compare Another
                        </a>
                        <a href="{{ route('aggregated.half-yearly.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Reports
                        </a>
                    </div>
                </div>
            </div>

            <!-- Report Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5>Report 1: {{ $report1->getPeriodLabel() }}</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Report ID:</strong> {{ $report1->report_id }}</p>
                            <p><strong>Project:</strong> {{ $report1->project_title }}</p>
                            <p><strong>Period:</strong> {{ $report1->period_from->format('d M Y') }} - {{ $report1->period_to->format('d M Y') }}</p>
                            <p><strong>Total Beneficiaries:</strong> {{ number_format($report1->total_beneficiaries ?? 0) }}</p>
                            <p><strong>Status:</strong>
                                <span class="badge {{ $report1->getStatusBadgeClass() }}">
                                    {{ $report1->getStatusLabel() }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5>Report 2: {{ $report2->getPeriodLabel() }}</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Report ID:</strong> {{ $report2->report_id }}</p>
                            <p><strong>Project:</strong> {{ $report2->project_title }}</p>
                            <p><strong>Period:</strong> {{ $report2->period_from->format('d M Y') }} - {{ $report2->period_to->format('d M Y') }}</p>
                            <p><strong>Total Beneficiaries:</strong> {{ number_format($report2->total_beneficiaries ?? 0) }}</p>
                            <p><strong>Status:</strong>
                                <span class="badge {{ $report2->getStatusBadgeClass() }}">
                                    {{ $report2->getStatusLabel() }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Structured Comparison Data -->
            @if(isset($comparison['structured_data']))
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Structured Comparison</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Report 1</th>
                                    <th>Report 2</th>
                                    <th>Change</th>
                                    <th>Change %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($comparison['structured_data']['beneficiaries']))
                                <tr>
                                    <td><strong>Total Beneficiaries</strong></td>
                                    <td>{{ number_format($comparison['structured_data']['beneficiaries']['report1']) }}</td>
                                    <td>{{ number_format($comparison['structured_data']['beneficiaries']['report2']) }}</td>
                                    <td>
                                        <span class="{{ $comparison['structured_data']['beneficiaries']['change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $comparison['structured_data']['beneficiaries']['change'] >= 0 ? '+' : '' }}{{ number_format($comparison['structured_data']['beneficiaries']['change']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{{ $comparison['structured_data']['beneficiaries']['change_percentage'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $comparison['structured_data']['beneficiaries']['change_percentage'] >= 0 ? '+' : '' }}{{ number_format($comparison['structured_data']['beneficiaries']['change_percentage'], 2) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endif

                                @if(isset($comparison['structured_data']['budget']))
                                <tr>
                                    <td><strong>Total Budget</strong></td>
                                    <td>{{ number_format($comparison['structured_data']['budget']['report1'], 2) }}</td>
                                    <td>{{ number_format($comparison['structured_data']['budget']['report2'], 2) }}</td>
                                    <td>
                                        <span class="{{ $comparison['structured_data']['budget']['change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $comparison['structured_data']['budget']['change'] >= 0 ? '+' : '' }}{{ number_format($comparison['structured_data']['budget']['change'], 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{{ $comparison['structured_data']['budget']['change_percentage'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $comparison['structured_data']['budget']['change_percentage'] >= 0 ? '+' : '' }}{{ number_format($comparison['structured_data']['budget']['change_percentage'], 2) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endif

                                @if(isset($comparison['structured_data']['expenses']))
                                <tr>
                                    <td><strong>Total Expenses</strong></td>
                                    <td>{{ number_format($comparison['structured_data']['expenses']['report1'], 2) }}</td>
                                    <td>{{ number_format($comparison['structured_data']['expenses']['report2'], 2) }}</td>
                                    <td>
                                        <span class="{{ $comparison['structured_data']['expenses']['change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $comparison['structured_data']['expenses']['change'] >= 0 ? '+' : '' }}{{ number_format($comparison['structured_data']['expenses']['change'], 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{{ $comparison['structured_data']['expenses']['change_percentage'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $comparison['structured_data']['expenses']['change_percentage'] >= 0 ? '+' : '' }}{{ number_format($comparison['structured_data']['expenses']['change_percentage'], 2) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endif

                                @if(isset($comparison['structured_data']['objectives']))
                                <tr>
                                    <td><strong>Objectives Count</strong></td>
                                    <td>{{ $comparison['structured_data']['objectives']['report1'] }}</td>
                                    <td>{{ $comparison['structured_data']['objectives']['report2'] }}</td>
                                    <td>
                                        <span class="{{ $comparison['structured_data']['objectives']['change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $comparison['structured_data']['objectives']['change'] >= 0 ? '+' : '' }}{{ $comparison['structured_data']['objectives']['change'] }}
                                        </span>
                                    </td>
                                    <td>-</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- AI Comparison Analysis (same structure as quarterly) -->
            @if(isset($comparison['summary']))
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Comparison Summary</h5>
                </div>
                <div class="card-body">
                    <p>{!! nl2br(e($comparison['summary'])) !!}</p>
                </div>
            </div>
            @endif

            @if(isset($comparison['improvements']) && count($comparison['improvements']) > 0)
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5>Improvements</h5>
                </div>
                <div class="card-body">
                    @foreach($comparison['improvements'] as $improvement)
                    <div class="mb-3 p-3 border-start border-success border-3">
                        <h6>{{ ucfirst($improvement['area'] ?? 'General') }} -
                            <span class="badge bg-{{ $improvement['magnitude'] === 'significant' ? 'success' : ($improvement['magnitude'] === 'moderate' ? 'warning' : 'info') }}">
                                {{ ucfirst($improvement['magnitude'] ?? 'minor') }}
                            </span>
                        </h6>
                        <p>{{ $improvement['description'] ?? '' }}</p>
                        @if(isset($improvement['evidence']))
                        <small class="text-muted"><strong>Evidence:</strong> {{ $improvement['evidence'] }}</small>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(isset($comparison['declines']) && count($comparison['declines']) > 0)
            <div class="card mb-3">
                <div class="card-header bg-danger text-white">
                    <h5>Declines</h5>
                </div>
                <div class="card-body">
                    @foreach($comparison['declines'] as $decline)
                    <div class="mb-3 p-3 border-start border-danger border-3">
                        <h6>{{ ucfirst($decline['area'] ?? 'General') }} -
                            <span class="badge bg-{{ $decline['magnitude'] === 'significant' ? 'danger' : ($decline['magnitude'] === 'moderate' ? 'warning' : 'info') }}">
                                {{ ucfirst($decline['magnitude'] ?? 'minor') }}
                            </span>
                        </h6>
                        <p>{{ $decline['description'] ?? '' }}</p>
                        @if(isset($decline['evidence']))
                        <small class="text-muted"><strong>Evidence:</strong> {{ $decline['evidence'] }}</small>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(isset($comparison['key_differences']) && count($comparison['key_differences']) > 0)
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Key Differences</h5>
                </div>
                <div class="card-body">
                    @foreach($comparison['key_differences'] as $diff)
                    <div class="mb-3 p-3 border rounded">
                        <h6>{{ $diff['difference'] ?? '' }}
                            <span class="badge bg-{{ $diff['significance'] === 'high' ? 'danger' : ($diff['significance'] === 'medium' ? 'warning' : 'info') }}">
                                {{ ucfirst($diff['significance'] ?? 'low') }} Significance
                            </span>
                        </h6>
                        <p><strong>Impact:</strong> {{ $diff['impact'] ?? '' }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(isset($comparison['trends']))
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Trends</h5>
                </div>
                <div class="card-body">
                    <p><strong>Direction:</strong>
                        <span class="badge bg-{{ $comparison['trends']['direction'] === 'improving' ? 'success' : ($comparison['trends']['direction'] === 'declining' ? 'danger' : 'warning') }}">
                            {{ ucfirst($comparison['trends']['direction'] ?? 'stable') }}
                        </span>
                    </p>
                    <p>{{ $comparison['trends']['description'] ?? '' }}</p>
                </div>
            </div>
            @endif

            @if(isset($comparison['insights']) && count($comparison['insights']) > 0)
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5>Key Insights</h5>
                </div>
                <div class="card-body">
                    @foreach($comparison['insights'] as $insight)
                    <div class="mb-3 p-3 border-start border-info border-3">
                        <p><strong>{{ $insight['insight'] ?? '' }}</strong></p>
                        <p class="mb-0"><em>Implications:</em> {{ $insight['implications'] ?? '' }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(isset($comparison['recommendations']) && count($comparison['recommendations']) > 0)
            <div class="card mb-3">
                <div class="card-header bg-warning text-dark">
                    <h5>Recommendations</h5>
                </div>
                <div class="card-body">
                    @foreach($comparison['recommendations'] as $rec)
                    <div class="mb-3 p-3 border rounded">
                        <h6>{{ $rec['recommendation'] ?? '' }}
                            <span class="badge bg-{{ $rec['priority'] === 'high' ? 'danger' : ($rec['priority'] === 'medium' ? 'warning' : 'info') }}">
                                {{ ucfirst($rec['priority'] ?? 'low') }} Priority
                            </span>
                        </h6>
                        <p class="mb-0"><strong>Rationale:</strong> {{ $rec['rationale'] ?? '' }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
