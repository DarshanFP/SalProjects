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
                    <div class="info-grid">
                        <div class="info-label"><strong>Project ID:</strong></div>
                        <div class="info-value">{{ $report->project_id }}</div>

                        <div class="info-label"><strong>Report ID:</strong></div>
                        <div class="info-value">{{ $report->report_id }}</div>

                        <div class="info-label"><strong>Project Title:</strong></div>
                        <div class="info-value">{{ $report->project_title }}</div>

                        <div class="info-label"><strong>Report Month & Year:</strong></div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($report->report_month_year)->format('F Y') }}</div>

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
                        <div class="info-value">{{ $report->total_beneficiaries }}</div>
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

            <!-- Objectives Section -->
            @include('reports.monthly.partials.view.objectives', ['report' => $report])

            <!-- Outlook Section -->
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Outlooks</h4>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        @foreach($report->outlooks as $outlook)
                            <div class="info-label"><strong>Date:</strong></div>
                            <div class="info-value">{{ \Carbon\Carbon::parse($outlook->date)->format('d-m-Y') }}</div>

                            <div class="info-label"><strong>Action Plan for Next Month:</strong></div>
                            <div class="info-value">{{ $outlook->plan_next_month }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Statements of Account Section -->
            @include('reports.monthly.partials.view.statements_of_account', ['budgets' => $budgets, 'project' => $project])

            <!-- Photos Section -->
            @include('reports.monthly.partials.view.photos', ['groupedPhotos' => $groupedPhotos])

            <!-- Attachments Section -->
            @include('reports.monthly.partials.view.attachments', ['report' => $report])
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
    @endphp

    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="{{ route('monthly.report.index') }}" class="btn btn-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Reports
        </a>

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
