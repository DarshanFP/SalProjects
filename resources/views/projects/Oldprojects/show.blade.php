@extends('executor.dashboard')

@section('content')
<div class="container">
    <h1 class="mb-4">Project Details</h1>

    <!-- General Information Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>General Information</h4>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-label"><strong>Project ID:</strong></div>
                <div class="info-value">{{ $project->project_id }}</div>
                <div class="info-label"><strong>Project Title:</strong></div>
                <div class="info-value">{{ $project->project_title }}</div>
                <div class="info-label"><strong>Project Type:</strong></div>
                <div class="info-value">{{ $project->project_type }}</div>
                <div class="info-label"><strong>Society Name:</strong></div>
                <div class="info-value">{{ $project->society_name }}</div>
                <div class="info-label"><strong>President Name:</strong></div>
                <div class="info-value">{{ $project->president_name }}</div>
                <div class="info-label"><strong>In Charge Name:</strong></div>
                <div class="info-value">{{ $project->in_charge_name }}</div>
                <div class="info-label"><strong>Executor Name:</strong></div>
                <div class="info-value">{{ $project->executor_name }}</div>
                <div class="info-label"><strong>Executor Phone:</strong></div>
                <div class="info-value">{{ $project->executor_mobile }}</div>
                <div class="info-label"><strong>Executor Email:</strong></div>
                <div class="info-value">{{ $project->executor_email }}</div>
                <div class="info-label"><strong>Full Address:</strong></div>
                <div class="info-value">{{ $project->full_address }}</div>
                <div class="info-label"><strong>Overall Project Period:</strong></div>
                <div class="info-value">{{ $project->overall_project_period }} years</div>
                <div class="info-label"><strong>Commencement Month & Year:</strong></div>
                <div class="info-value">{{ \Carbon\Carbon::parse($project->commencement_month_year)->format('F Y') }}</div>
                <div class="info-label"><strong>Overall Project Budget:</strong></div>
                <div class="info-value">Rs. {{ number_format($project->overall_project_budget, 2) }}</div>
                <div class="info-label"><strong>Amount Forwarded:</strong></div>
                <div class="info-value">Rs. {{ number_format($project->amount_forwarded, 2) }}</div>
                <div class="info-label"><strong>Amount Sanctioned:</strong></div>
                <div class="info-value">Rs. {{ number_format($project->amount_sanctioned, 2) }}</div>
                <div class="info-label"><strong>Opening Balance:</strong></div>
                <div class="info-value">Rs. {{ number_format($project->opening_balance, 2) }}</div>
                <div class="info-label"><strong>Coordinator India Name:</strong></div>
                <div class="info-value">{{ $project->coordinator_india_name }}</div>
                <div class="info-label"><strong>Coordinator India Phone:</strong></div>
                <div class="info-value">{{ $project->coordinator_india_phone }}</div>
                <div class="info-label"><strong>Coordinator India Email:</strong></div>
                <div class="info-value">{{ $project->coordinator_india_email }}</div>
                <div class="info-label"><strong>Coordinator Luzern Name:</strong></div>
                <div class="info-value">{{ $project->coordinator_luzern_name }}</div>
                <div class="info-label"><strong>Coordinator Luzern Phone:</strong></div>
                <div class="info-value">{{ $project->coordinator_luzern_phone }}</div>
                <div class="info-label"><strong>Coordinator Luzern Email:</strong></div>
                <div class="info-value">{{ $project->coordinator_luzern_email }}</div>
                <div class="info-label"><strong>Status:</strong></div>
                <div class="info-value">{{ ucfirst($project->status) }}</div>
            </div>
        </div>
    </div>

    <!-- Key Information Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Key Information</h4>
        </div>
        <div class="card-body">
            <p><strong>Goal of the Project:</strong></p>
            <p>{{ $project->goal }}</p>
        </div>
    </div>

    <!-- Budget Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Budget</h4>
        </div>
        <div class="card-body">
            @php
                $groupedBudgets = $project->budgets->groupBy('phase');
            @endphp

            @foreach($groupedBudgets as $phase => $budgets)
                <div class="mb-3 phase-card">
                    <div class="card-header">
                        <h5>Phase {{ $phase }}</h5>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount Sanctioned in Phase {{ $phase }}: Rs.</label>
                        <p>{{ number_format($budgets->sum('this_phase'), 2) }}</p>
                    </div>
                    <table class="table table-bordered table-custom">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Particular</th>
                                <th style="width: 10%;">Costs</th>
                                <th style="width: 10%;">Rate Multiplier</th>
                                <th style="width: 10%;">Rate Duration</th>
                                <th style="width: 10%;">Rate Increase (next phase)</th>
                                <th style="width: 10%;">This Phase (Auto)</th>
                                <th style="width: 10%;">Next Phase (Auto)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($budgets as $budget)
                                <tr>
                                    <td>{{ $budget->particular }}</td>
                                    <td>{{ number_format($budget->rate_quantity, 2) }}</td>
                                    <td>{{ number_format($budget->rate_multiplier, 2) }}</td>
                                    <td>{{ number_format($budget->rate_duration, 2) }}</td>
                                    <td>{{ number_format($budget->rate_increase, 2) }}</td>
                                    <td>{{ number_format($budget->this_phase, 2) }}</td>
                                    <td>{{ number_format($budget->next_phase, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th>{{ number_format($budgets->sum('rate_quantity'), 2) }}</th>
                                <th>{{ number_format($budgets->sum('rate_multiplier'), 2) }}</th>
                                <th>{{ number_format($budgets->sum('rate_duration'), 2) }}</th>
                                <th>{{ number_format($budgets->sum('rate_increase'), 2) }}</th>
                                <th>{{ number_format($budgets->sum('this_phase'), 2) }}</th>
                                <th>{{ number_format($budgets->sum('next_phase'), 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Attachments Section -->
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Attachments</h4>
        </div>
        <div class="card-body">
            @foreach($project->attachments as $attachment)
                <div class="attachment-grid">
                    <div class="attachment-label"><strong>Attachment:</strong></div>
                    <div class="attachment-value"><a href="{{ route('download.attachment', $attachment->id) }}">{{ $attachment->file_name }}</a></div>
                    <div class="attachment-label"><strong>Description:</strong></div>
                    <div class="attachment-value">{{ $attachment->description }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <a href="{{ route('projects.index') }}" class="btn btn-primary">Back to Projects</a>
    <a href="{{ route('projects.downloadPdf', $project->project_id) }}" class="btn btn-secondary">Download PDF</a>
    <a href="{{ route('projects.downloadDoc', $project->project_id) }}" class="btn btn-secondary">Download Word</a>
</div>

<style>
    .info-grid {
        display: grid;
        grid-template-columns: 200px 1fr;
        grid-gap: 10px;
    }

    .info-label {
        font-weight: bold;
    }

    .info-value {
        word-wrap: break-word;
        margin-left: 20px;
    }

    .table th, .table td {
        vertical-align: middle;
        text-align: center;
        padding: 0;
    }

    .table th {
        white-space: normal;
    }

    .table td input {
        width: 100%;
        box-sizing: border-box;
        -moz-appearance: textfield;
        padding: 0.375rem 0.75rem;
    }

    .table td input::-webkit-outer-spin-button,
    .table td input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .table-container {
        overflow-x: auto;
    }

    .fp-text-center1 {
        text-align: center;
        margin-bottom: 15px;
    }

    .fp-text-margin {
        margin-bottom: 15px;
    }

    .phase-card {
        margin-bottom: 1.5rem;
    }

    .card-header h4, .card-header h5 {
        margin-bottom: 0;
    }

    .table-custom {
        border: 1pt solid grey;
    }

    .table-custom th, .table-custom td {
        border: 1pt solid grey;
    }

    .attachment-grid {
        display: grid;
        grid-template-columns: 200px 1fr;
        grid-gap: 10px;
        margin-bottom: 15px;
    }

    .attachment-label {
        font-weight: bold;
    }
</style>
@endsection
