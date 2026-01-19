@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Half-Yearly Report: {{ $report->getPeriodLabel() }}</h4>
                    <div>
                        @if($report->isEditable() && $report->aiInsights)
                            <a href="{{ route('aggregated.half-yearly.edit-ai', $report->report_id) }}" class="btn btn-warning btn-sm">Edit AI Content</a>
                        @endif
                        <a href="{{ route('aggregated.half-yearly.export-pdf', $report->report_id) }}" class="btn btn-secondary btn-sm">Export PDF</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-label"><strong>Report ID:</strong></div>
                        <div class="info-value">{{ $report->report_id }}</div>
                        <div class="info-label"><strong>Project:</strong></div>
                        <div class="info-value">{{ $report->project_title }}</div>
                        <div class="info-label"><strong>Period:</strong></div>
                        <div class="info-value">{{ $report->period_from->format('d M Y') }} - {{ $report->period_to->format('d M Y') }}</div>
                        <div class="info-label"><strong>Status:</strong></div>
                        <div class="info-value">
                            <span class="badge {{ $report->getStatusBadgeClass() }}">{{ $report->getStatusLabel() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($report->aiTitle)
                <div class="card mb-3">
                    <div class="card-header">
                        <h4>{{ $report->aiTitle->report_title ?? 'Half-Yearly Progress Report' }}</h4>
                    </div>
                </div>
            @endif

            @if($report->aiInsights)
                @if($report->aiInsights->executive_summary)
                    <div class="card mb-3">
                        <div class="card-header"><h5>Executive Summary</h5></div>
                        <div class="card-body"><p>{!! nl2br(e($report->aiInsights->executive_summary)) !!}</p></div>
                    </div>
                @endif

                @if($report->aiInsights->key_achievements && count($report->aiInsights->key_achievements) > 0)
                    <div class="card mb-3">
                        <div class="card-header"><h5>Key Achievements</h5></div>
                        <div class="card-body">
                            <ul>
                                @foreach($report->aiInsights->key_achievements as $achievement)
                                    <li>{{ is_array($achievement) ? ($achievement['title'] ?? $achievement['description'] ?? '') : $achievement }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if($report->aiInsights->strategic_insights && count($report->aiInsights->strategic_insights) > 0)
                    <div class="card mb-3">
                        <div class="card-header"><h5>Strategic Insights</h5></div>
                        <div class="card-body">
                            <ul>
                                @foreach($report->aiInsights->strategic_insights as $insight)
                                    <li>{{ is_array($insight) ? ($insight['insight'] ?? '') : $insight }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if($report->aiInsights->quarterly_comparison)
                    <div class="card mb-3">
                        <div class="card-header"><h5>Quarterly Comparison</h5></div>
                        <div class="card-body">
                            <p>{{ is_array($report->aiInsights->quarterly_comparison) ? json_encode($report->aiInsights->quarterly_comparison, JSON_PRETTY_PRINT) : $report->aiInsights->quarterly_comparison }}</p>
                        </div>
                    </div>
                @endif
            @endif

            <div class="mt-3">
                <a href="{{ route('aggregated.half-yearly.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
.info-grid {
    display: grid;
    grid-template-columns: 20% 80%;
    grid-gap: 20px;
    align-items: start;
}

.info-label {
    font-weight: bold;
    margin-right: 10px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    white-space: normal;
}

.info-value {
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    white-space: normal;
    padding-left: 10px;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
        grid-gap: 10px;
    }

    .info-label {
        margin-right: 0;
        margin-bottom: 5px;
    }

    .info-value {
        padding-left: 0;
    }
}
</style>

<script src="{{ asset('js/report-view-hide-empty.js') }}"></script>
