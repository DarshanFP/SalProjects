{{-- resources/views/reports/monthly/show.blade.php --}}
@extends('executor.dashboard')

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
            @include('reports.monthly.partials.view.statements_of_account', ['budgets' => $budgets])

            <!-- Photos Section -->
            @include('reports.monthly.partials.view.photos', ['groupedPhotos' => $groupedPhotos])

            <!-- Attachments Section -->
            @include('reports.monthly.partials.view.attachments', ['report' => $report])
        </div>
    </div>
    <!-- Download Buttons -->
    <div class="card-footer">
        <a href="{{ route('monthly.report.downloadDoc', $report->report_id) }}" class="btn btn-primary">
            Download Word File
        </a>
        <a href="{{ route('monthly.report.downloadPdf', $report->report_id) }}" class="btn btn-secondary">
            Download PDF
        </a>
    </div>
</div>
@endsection

<style>
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Equal columns */
        grid-gap: 20px; /* Increased spacing between rows */
    }

    .info-label {
        font-weight: bold;
        margin-right: 10px; /* Optional spacing after labels */
    }

    .info-value {
        word-wrap: break-word;
        padding-left: 10px; /* Optional padding before values */
    }
</style>
