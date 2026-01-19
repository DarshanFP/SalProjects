{{-- Report Status Summary Widget - Dark Theme Compatible --}}
<div class="card mb-4 h-100 d-flex flex-column equal-height-widget">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="bar-chart-2" class="me-2"></i>
            Report Status Summary
        </h5>
        <div class="widget-drag-handle">
            <i data-feather="move" style="width: 16px; height: 16px;" class="text-muted"></i>
        </div>
    </div>
    <div class="card-body flex-grow-1">
        <div class="row g-3">
            {{-- Draft Reports --}}
            <div class="col-md-6 col-lg-4">
                <div class="card bg-secondary bg-opacity-25 border-secondary">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Draft</h6>
                                <h4 class="mb-0 text-white">{{ $reportStatusSummary['monthly'][App\Models\Reports\Monthly\DPReport::STATUS_DRAFT] ?? 0 }}</h4>
                            </div>
                            <div class="text-secondary">
                                <i data-feather="file" style="width: 32px; height: 32px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submitted Reports --}}
            <div class="col-md-6 col-lg-4">
                <div class="card bg-info bg-opacity-25 border-info">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Submitted</h6>
                                <h4 class="mb-0 text-white">{{ $reportStatusSummary['monthly'][App\Models\Reports\Monthly\DPReport::STATUS_SUBMITTED_TO_PROVINCIAL] ?? 0 }}</h4>
                            </div>
                            <div class="text-info">
                                <i data-feather="send" style="width: 32px; height: 32px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Forwarded Reports --}}
            <div class="col-md-6 col-lg-4">
                <div class="card bg-primary bg-opacity-25 border-primary">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Forwarded</h6>
                                <h4 class="mb-0 text-white">{{ $reportStatusSummary['monthly'][App\Models\Reports\Monthly\DPReport::STATUS_FORWARDED_TO_COORDINATOR] ?? 0 }}</h4>
                            </div>
                            <div class="text-primary">
                                <i data-feather="arrow-right" style="width: 32px; height: 32px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Approved Reports --}}
            <div class="col-md-6 col-lg-4">
                <div class="card bg-success bg-opacity-25 border-success">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Approved</h6>
                                <h4 class="mb-0 text-white">{{ $reportStatusSummary['monthly'][App\Models\Reports\Monthly\DPReport::STATUS_APPROVED_BY_COORDINATOR] ?? 0 }}</h4>
                            </div>
                            <div class="text-success">
                                <i data-feather="check-circle" style="width: 32px; height: 32px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reverted Reports --}}
            <div class="col-md-6 col-lg-4">
                <div class="card bg-danger bg-opacity-25 border-danger">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Reverted</h6>
                                <h4 class="mb-0 text-white">
                                    {{ ($reportStatusSummary['monthly'][App\Models\Reports\Monthly\DPReport::STATUS_REVERTED_BY_PROVINCIAL] ?? 0) +
                                       ($reportStatusSummary['monthly'][App\Models\Reports\Monthly\DPReport::STATUS_REVERTED_BY_COORDINATOR] ?? 0) }}
                                </h4>
                            </div>
                            <div class="text-danger">
                                <i data-feather="rotate-ccw" style="width: 32px; height: 32px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Reports --}}
        <div class="mt-3 pt-3 border-top border-secondary">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Total Monthly Reports:</span>
                <strong class="text-white">{{ $reportStatusSummary['total'] }}</strong>
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="mt-3">
            <div class="row g-2">
                <div class="col-6">
                    <a href="{{ route('executor.report.list') }}" class="btn btn-outline-primary btn-sm w-100">
                        <i data-feather="list" style="width: 14px; height: 14px;"></i>
                        All Reports
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('executor.report.pending') }}" class="btn btn-outline-warning btn-sm w-100">
                        <i data-feather="clock" style="width: 14px; height: 14px;"></i>
                        Pending
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
