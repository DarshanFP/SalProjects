{{-- resources/views/reports/monthly/show.blade.php --}}
{{-- @extends('executor.dashboard') --}}
@php
    $userRole = Auth::user()->role ?? 'executor'; // Default to executor if not set
    $layout = match ($userRole) {
        'provincial' => 'provincial.dashboard',
        'coordinator' => 'coordinator.dashboard',
        default => 'executor.dashboard', // fallback to executor if role not matched
    };
@endphp

@extends($layout)

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <!-- General Information Section -->
            <div class="mb-3 card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="fp-text-margin">Basic Information</h4>
                    <span class="text-muted" style="color: #d0d7e1 !important;">Report ID: {{ $report->report_id ?? 'N/A' }}</span>
                </div>
                <div class="card-body">
                    {{-- Legend: green accent = entered in report by Executor/Applicant --}}
                    <div class="report-view-legend mb-3 px-2 py-2" style="background-color: rgba(0,0,0,0.15); border-radius: 6px; font-size: 0.9rem;">
                        <span class="report-legend-sample report-value-entered d-inline-block px-2 py-1 me-2" style="vertical-align: middle;">Sample</span>
                        <span style="color: #d0d7e1;">= Entered in report</span>
                        <span class="ms-3" style="color: #9ca3af;">No accent = From project</span>
                    </div>
                    <div class="info-grid">
                        <div class="info-label"><strong>Project ID:</strong></div>
                        <div class="info-value">{{ $report->project_id }}</div>

                        <div class="info-label"><strong>Report ID:</strong></div>
                        <div class="info-value">{{ $report->report_id }}</div>

                        <div class="info-label"><strong>Project Title:</strong></div>
                        <div class="info-value">{{ $report->project_title }}</div>

                        <div class="info-label"><strong>Report Month & Year:</strong></div>
                        <div class="info-value report-value-entered">{{ \Carbon\Carbon::parse($report->report_month_year)->format('F Y') }}</div>

                        <div class="info-label"><strong>Project Type:</strong></div>
                        <div class="info-value">{{ $project->project_type }}</div>

                        <div class="info-label"><strong>Place:</strong></div>
                        <div class="info-value">{{ $report->place }}</div>

                        <div class="info-label"><strong>Society Name:</strong></div>
                        <div class="info-value">{{ $report->society_name }}</div>

                        <div class="info-label"><strong>Commencement Month & Year:</strong></div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($report->commencement_month_year)->format('F Y') }}</div>

                        <div class="info-label"><strong>Sister In Charge:</strong></div>
                        <div class="info-value">{{ $report->in_charge }}</div>

                        <div class="info-label"><strong>Total Beneficiaries:</strong></div>
                        <div class="info-value report-value-entered">{{ $report->total_beneficiaries }}</div>
                    </div>
                </div>

            </div>

            <!-- Specific Project Type Section -->
            @if($report->project_type === 'Livelihood Development Projects')
                @include('reports.monthly.partials.view.livelihoodAnnexure', ['report' => $report, 'annexures' => $annexures])
            @elseif($report->project_type === 'Institutional Ongoing Group Educational proposal')
                @include('reports.monthly.partials.view.institutional_ongoing_group', ['report' => $report, 'ageProfiles' => $ageProfiles])
            @elseif($report->project_type === 'Residential Skill Training Proposal 2')
                @include('reports.monthly.partials.view.residential_skill_training', ['report' => $report, 'traineeProfiles' => $traineeProfiles])
            @elseif($report->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER')
                @include('reports.monthly.partials.view.crisis_intervention_center', ['report' => $report, 'inmateProfiles' => $inmateProfiles])
            @endif

            <!-- Objectives Section (includes per-objective Activity Monitoring for provincial/coordinator) -->
            @include('reports.monthly.partials.view.objectives', ['report' => $report, 'monitoringPerObjective' => $monitoringPerObjective ?? [], 'reportedActivityScheduleStatus' => $reportedActivityScheduleStatus ?? []])

            <!-- Outlook Section -->
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Outlooks</h4>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        @foreach($report->outlooks as $outlook)
                            <div class="info-label"><strong>Date:</strong></div>
                            <div class="info-value report-value-entered">{{ \Carbon\Carbon::parse($outlook->date)->format('d-m-Y') }}</div>

                            <div class="info-label"><strong>Action Plan for Next Month:</strong></div>
                            <div class="info-value report-value-entered">{{ $outlook->plan_next_month }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Statements of Account Section -->
            @include('reports.monthly.partials.view.statements_of_account', ['budgets' => $budgets, 'project' => $project])

            <!-- Budget — Monitoring (Provincial; when status under review) -->
            @include('reports.monthly.partials.view.budget_monitoring')

            <!-- Project-Type — Monitoring (LDP, IGE, RST, CIC; when status under review and any flags) -->
            @include('reports.monthly.partials.view.type_specific_monitoring')

            <!-- Photos Section -->
            @include('reports.monthly.partials.view.photos', ['unassignedPhotos' => $unassignedPhotos])

            <!-- Attachments Section -->
            @include('reports.monthly.partials.view.attachments', ['report' => $report])

            <!-- Activity Monitoring — Scheduled but not reported (objective‑wise; before Add Comment) -->
            @include('reports.monthly.partials.view.activity_monitoring')

            <!-- Comments by Project Monitoring Committee (PMC) — read-only; before Comments section -->
            @include('reports.monthly.partials.view.pmc_comments')
        </div>
    </div>
    <!-- Download Buttons -->
    <div class="card-footer">
        @php
            $downloadRoute = match ($userRole) {
                'coordinator' => route('coordinator.monthly.report.downloadPdf', $report->report_id),
                'provincial' => route('provincial.monthly.report.downloadPdf', $report->report_id),
                default => route('monthly.report.downloadPdf', $report->report_id),
            };
        @endphp
        <a href="{{ $downloadRoute }}" class="btn btn-secondary">
            Download PDF
        </a>
    </div>
</div>
@include('reports.monthly.partials.comments')

<!-- Action Buttons -->
<div class="mb-3">
    @php
        use App\Models\Reports\Monthly\DPReport;
        $user = Auth::user();
        $reportStatus = $report->status;
        $userRole = $user->role;

        // Check if user can edit report
        $canEdit = false;
        $editableStatuses = [
            DPReport::STATUS_DRAFT,
            DPReport::STATUS_REVERTED_BY_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
        ];

        if (in_array($reportStatus, $editableStatuses)) {
            if (in_array($userRole, ['executor', 'applicant'])) {
                // Check if user owns the report or is in-charge of the project
                $canEdit = ($report->user_id === $user->id || ($project && $project->in_charge == $user->id));
            } elseif (in_array($userRole, ['coordinator', 'provincial'])) {
                // Coordinator and provincial can edit based on their permissions (handled in controller)
                $canEdit = true; // Controller will handle authorization via UpdateMonthlyReportRequest
            }
        }

        // Check if user can submit to provincial
        $canSubmit = false;
        $submittableStatuses = [
            DPReport::STATUS_DRAFT,
            DPReport::STATUS_REVERTED_BY_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
        ];

        if (in_array($userRole, ['executor', 'applicant']) && in_array($reportStatus, $submittableStatuses)) {
            // Check if user owns the report or is in-charge of the project
            $canSubmit = ($report->user_id === $user->id || ($project && $project->in_charge == $user->id));
        }

        // Provincial: Forward to Coordinator (statuses allowed by ReportStatusService::forwardToCoordinator)
        $canForward = $userRole === 'provincial' && in_array($reportStatus, [
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
            DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
            DPReport::STATUS_REVERTED_TO_PROVINCIAL,
        ]);

        // Provincial: Revert Report (statuses allowed by ReportStatusService::revertByProvincial)
        $canRevert = $userRole === 'provincial' && in_array($reportStatus, [
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
            DPReport::STATUS_FORWARDED_TO_COORDINATOR,
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
        ]);

        $backToReportsUrl = match($userRole) {
            'provincial' => route('provincial.report.list'),
            'coordinator' => route('coordinator.report.list'),
            default => route('monthly.report.index'),
        };
    @endphp

    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="{{ $backToReportsUrl }}" class="btn btn-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Reports
        </a>

        @if($canForward)
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#forwardModalShow{{ $report->report_id }}">
                <i class="fas fa-share me-2"></i>Forward to Coordinator
            </button>
        @endif

        @if($canRevert)
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#revertModalShow{{ $report->report_id }}">
                <i class="fas fa-undo me-2"></i>Revert Report
            </button>
        @endif

        @if($canEdit)
            <a href="{{ route('monthly.report.edit', $report->report_id) }}" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Edit Report
            </a>
        @endif

        @if($canSubmit)
            <form action="{{ route('monthly.report.submit', $report->report_id) }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to submit this report to Provincial? This action cannot be undone easily.');">
                    <i class="fas fa-paper-plane me-2"></i>Submit to Provincial
                </button>
            </form>
        @endif
    </div>

    {{-- Forward to Coordinator Modal (Provincial) --}}
    @if($canForward)
    <div class="modal fade" id="forwardModalShow{{ $report->report_id }}" tabindex="-1" aria-labelledby="forwardModalLabelShow{{ $report->report_id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forwardModalLabelShow{{ $report->report_id }}">Forward Report to Coordinator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('provincial.report.forward', $report->report_id) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="pmc_comments_show" class="form-label">Comments by Project Monitoring Committee <span class="text-danger">*</span></label>
                            <textarea name="pmc_comments" id="pmc_comments_show" class="form-control" rows="4" required maxlength="5000" placeholder="Enter PMC comments before forwarding.">{{ old('pmc_comments', $report->pmc_comments ?? '') }}</textarea>
                            <div class="form-text">Required before forwarding to Coordinator.</div>
                        </div>
                        <p>Are you sure you want to forward this report to the coordinator?</p>
                        <p><strong>Report ID:</strong> {{ $report->report_id }}</p>
                        <p><strong>Project:</strong> {{ $report->project_title }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Forward to Coordinator</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Revert Report Modal (Provincial) --}}
    @if($canRevert)
    <div class="modal fade" id="revertModalShow{{ $report->report_id }}" tabindex="-1" aria-labelledby="revertModalLabelShow{{ $report->report_id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="revertModalLabelShow{{ $report->report_id }}">Revert Report to Executor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('provincial.report.revert', $report->report_id) }}">
                    @csrf
                    <div class="modal-body">
                        <p><strong>Report ID:</strong> {{ $report->report_id }}</p>
                        <p><strong>Project:</strong> {{ $report->project_title }}</p>
                        <div class="mb-3">
                            <label for="revert_reason_show{{ $report->report_id }}" class="form-label">Reason for Revert *</label>
                            <textarea class="form-control" id="revert_reason_show{{ $report->report_id }}" name="revert_reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Revert to Executor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Activity History Section -->
@include('reports.monthly.partials.view.activity_history', ['report' => $report])

</div>
@endsection

<script src="{{ asset('js/report-view-hide-empty.js') }}"></script>

<style>
    .info-grid {
        display: grid;
        grid-template-columns: 20% 80%; /* 20% label, 80% fillable area */
        grid-gap: 20px; /* Increased spacing between rows */
        align-items: start;
    }

    .info-label {
        font-weight: bold;
        margin-right: 10px; /* Optional spacing after labels */
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
        padding-left: 10px; /* Optional padding before values */
    }

    /* For Bootstrap column-based layouts */
    .report-label-col {
        word-wrap: break-word;
        overflow-wrap: break-word;
        word-break: break-word;
        white-space: normal;
    }

    .report-value-col {
        word-wrap: break-word;
        overflow-wrap: break-word;
        word-break: break-word;
        white-space: normal;
    }

    /* Report View: values entered in the report (Executor/Applicant) - Option E */
    .report-value-entered,
    .report-cell-entered {
        border-left: 3px solid #05a34a;
        background-color: rgba(5, 163, 74, 0.12);
        padding-left: 0.5rem;
    }
    .report-view-legend .report-legend-sample {
        min-width: 4rem;
        text-align: center;
    }

    /* Responsive behavior for mobile */
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

        .report-label-col,
        .report-value-col {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }
</style>
