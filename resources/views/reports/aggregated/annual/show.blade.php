@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Annual Report: {{ $report->year }}</h4>
                    <div>
                        @if($report->isEditable() && $report->aiInsights)
                            <a href="{{ route('aggregated.annual.edit-ai', $report->report_id) }}" class="btn btn-warning btn-sm">Edit AI Content</a>
                        @endif
                        <a href="{{ route('aggregated.annual.export-pdf', $report->report_id) }}" class="btn btn-secondary btn-sm">Export PDF</a>
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
                    <div class="card-header"><h4>{{ $report->aiTitle->report_title ?? 'Annual Report' }}</h4></div>
                </div>
            @endif

            @if($report->aiInsights)
                @if($report->aiInsights->executive_summary)
                    <div class="card mb-3">
                        <div class="card-header"><h5>Executive Summary</h5></div>
                        <div class="card-body"><p>{!! nl2br(e($report->aiInsights->executive_summary)) !!}</p></div>
                    </div>
                @endif

                @if($report->aiInsights->impact_assessment)
                    <div class="card mb-3">
                        <div class="card-header"><h5>Impact Assessment</h5></div>
                        <div class="card-body">
                            <pre>{{ is_array($report->aiInsights->impact_assessment) ? json_encode($report->aiInsights->impact_assessment, JSON_PRETTY_PRINT) : $report->aiInsights->impact_assessment }}</pre>
                        </div>
                    </div>
                @endif

                @if($report->aiInsights->budget_performance)
                    <div class="card mb-3">
                        <div class="card-header"><h5>Budget Performance</h5></div>
                        <div class="card-body">
                            <pre>{{ is_array($report->aiInsights->budget_performance) ? json_encode($report->aiInsights->budget_performance, JSON_PRETTY_PRINT) : $report->aiInsights->budget_performance }}</pre>
                        </div>
                    </div>
                @endif

                @if($report->aiInsights->future_outlook)
                    <div class="card mb-3">
                        <div class="card-header"><h5>Future Outlook</h5></div>
                        <div class="card-body">
                            <pre>{{ is_array($report->aiInsights->future_outlook) ? json_encode($report->aiInsights->future_outlook, JSON_PRETTY_PRINT) : $report->aiInsights->future_outlook }}</pre>
                        </div>
                    </div>
                @endif
            @endif

            <div class="mt-3">
                <a href="{{ route('aggregated.annual.index') }}" class="btn btn-secondary">Back to List</a>
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
