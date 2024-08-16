<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #000;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        h1, h2, h3 {
            text-align: center;
            margin-bottom: 20px;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .card-header {
            background-color: #f5f5f5;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 1.2em;
        }

        .card-body {
            padding: 10px;
        }

        .table, .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed; /* Ensure the table stays within the margins */
        }

        .table th, .table td, .signature-table th, .signature-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word; /* Ensure text doesn't overflow */
        }

        .signature-table td {
            padding: 8px;
            vertical-align: top;
            text-align: left;
            width: 50%; /* Default width for columns */
        }

        .signature-table tr td:nth-child(2) {
            width: 60%; /* Increase the width of the second column */
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .column {
            flex: 0 0 48%;
        }

        @page {
            margin: 20mm;
            header: page-header; /* Ensure header is associated with every page */


            margin-top: 1.6in;    /* Top margin */
            margin-bottom: 1.6in; /* Bottom margin */
            margin-left: 0.6in;   /* Side margins, previously set to 15mm approximately */
            margin-right: 0.6in;
            header: page-header;  /* Maintain header association */
            footer: page-footer;  /* Maintain footer association */

        }

        .page-header {
            text-align: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 10px;
            font-size: 0.8em;
            width: 100%;
        }

        .page-number:before {
            content: counter(page);
        }
    </style>
</head>
<body>

    <div class="page-header">
        Project ID: {{ $project->project_id }}  - Downloaded by: {{ auth()->user()->name }}
    </div>

    <div class="container">
        <h1>Project Details</h1>

        <!-- General Information Section -->
        <div class="card">
            <div class="card-header">
                General Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="column">
                        <p><strong>Project ID:</strong> {{ $project->project_id }}</p>
                        <p><strong>Project Title:</strong> {{ $project->project_title }}</p>
                        <p><strong>Project Type:</strong> {{ $project->project_type }}</p>
                        <p><strong>Society Name:</strong> {{ $project->society_name }}</p>
                        <p><strong>President Name:</strong> {{ $project->president_name }}</p>
                        <p><strong>Project In-charge:</strong> {{ $project->in_charge_name }}</p>
                        <p><strong>In Charge Phone:</strong> {{ $project->in_charge_mobile }}</p>
                        <p><strong>In Charge Email:</strong> {{ $project->in_charge_email }}</p>
                        <p><strong>Executor Name:</strong> {{ $project->executor_name }}</p>
                        <p><strong>Executor Phone:</strong> {{ $project->executor_mobile }}</p>
                        <p><strong>Executor Email:</strong> {{ $project->executor_email }}</p>
                        <p><strong>Full Address:</strong> {{ $project->full_address }}</p>
                    </div>
                    <div class="column">
                        <p><strong>Overall Project Period:</strong> {{ $project->overall_project_period }} years</p>
                        <p><strong>Commencement Month & Year:</strong> {{ \Carbon\Carbon::parse($project->commencement_month_year)->format('F Y') }}</p>
                        <p><strong>Overall Project Budget:</strong> Rs. {{ number_format($project->overall_project_budget, 2) }}</p>
                        <p><strong>Amount Forwarded:</strong> Rs. {{ number_format($project->amount_forwarded, 2) }}</p>
                        <p><strong>Amount Sanctioned:</strong> Rs. {{ number_format($project->amount_sanctioned, 2) }}</p>
                        <p><strong>Opening Balance:</strong> Rs. {{ number_format($project->opening_balance, 2) }}</p>
                        <p><strong>Coordinator India Name:</strong> {{ $project->coordinator_india_name }}</p>
                        <p><strong>Coordinator India Phone:</strong> {{ $project->coordinator_india_phone }}</p>
                        <p><strong>Coordinator India Email:</strong> {{ $project->coordinator_india_email }}</p>
                        <p><strong>Coordinator Luzern Name:</strong> {{ $project->coordinator_luzern_name }}</p>
                        <p><strong>Coordinator Luzern Phone:</strong> {{ $project->coordinator_luzern_phone }}</p>
                        <p><strong>Coordinator Luzern Email:</strong> {{ $project->coordinator_luzern_email }}</p>
                        <p><strong>Status:</strong> {{ ucfirst($project->status) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Information Section -->
        <div class="card">
            <div class="card-header">
                Key Information
            </div>
            <div class="card-body">
                <p><strong>Goal of the Project:</strong></p>
                <p>{{ $project->goal }}</p>
            </div>
        </div>

        <!-- Logical Framework Section -->
        <div class="card">
            <div class="card-header">
                Logical Framework
            </div>
            <div class="card-body">
                @foreach($project->objectives as $objective)
                <div class="mb-4 border rounded objective-card">
                    <h5 class="mb-3">Objective: {{ $objective->objective }}</h5>

                    <div class="mb-4 results-container">
                        <h6 class="mb-3">Results / Outcomes</h6>
                        @foreach($objective->results as $result)
                        <div class="mb-3 border rounded result-section">
                            <p>{{ $result->result }}</p>
                        </div>
                        @endforeach
                    </div>

                    <!-- Risks Section -->
                    <div class="mb-4 risks-container">
                        <h6 class="mb-3">Risks</h6>
                        @if($objective->risks->isNotEmpty())
                            <div class="mb-3 border rounded">
                                @foreach($objective->risks as $risk)
                                    <p>{{ $risk->risk }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>


                    <!-- Activities and Means of Verification -->
                    <div class="mb-4 activities-container">
                        <h6 class="mb-3">Activities and Means of Verification</h6>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 40%;">Activities</th>
                                    <th scope="col">Means of Verification</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($objective->activities as $activity)
                                <tr>
                                    <td>{{ $activity->activity }}</td>
                                    <td>{{ $activity->verification }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Time Frame Section -->
                    <div class="time-frame-container">
                        <h6 class="mb-3">Time Frame for Activities</h6>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 40%;">Activities</th>
                                    @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $monthAbbreviation)
                                    <th scope="col">{{ $monthAbbreviation }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($objective->activities as $activity)
                                <tr class="activity-timeframe-row">
                                    <td>{{ $activity->activity }}</td>
                                    @foreach(range(1, 12) as $month)
                                    <td>
                                        @php
                                        $isChecked = $activity->timeframes->contains(function($timeframe) use ($month) {
                                            return $timeframe->month == $month && $timeframe->is_active == 1;
                                        });
                                        @endphp
                                        <input type="checkbox" class="custom-checkbox" {{ $isChecked ? 'checked' : '' }} >
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Sustainability Section -->
        <div class="card">
            <div class="card-header">
                Project Sustainability, Monitoring and Methodologies
            </div>
            <div class="card-body">
                @forelse($project->sustainabilities as $sustainability)
                    <div class="mb-3">
                        <h5>Explain the Sustainability of the Project:</h5>
                        <p>{{ $sustainability->sustainability ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-3">
                        <h5>Explain the Monitoring Process of the Project:</h5>
                        <p>{{ $sustainability->monitoring_process ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-3">
                        <h5>Explain the Methodology of Reporting:</h5>
                        <p>{{ $sustainability->reporting_methodology ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-3">
                        <h5>Explain the Methodology of Evaluation:</h5>
                        <p>{{ $sustainability->evaluation_methodology ?? 'N/A' }}</p>
                    </div>
                @empty
                    <p>No sustainability information available for this project.</p>
                @endforelse
            </div>
        </div>

        <!-- Budget Section -->
        <div class="card">
            <div class="card-header">
                Budget
            </div>
            <div class="card-body">
                @php
                    $groupedBudgets = $project->budgets->groupBy('phase');
                @endphp

                @foreach($groupedBudgets as $phase => $budgets)
                    <div class="mb-3 phase-card">
                        <h4>Phase {{ $phase }}</h4>
                        <p>Amount Sanctioned in Phase {{ $phase }}: Rs. {{ number_format($budgets->sum('this_phase'), 2) }}</p>
                        <table class="table table-bordered">
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
        <div class="card">
            <div class="card-header">
                Attachments
            </div>
            <div class="card-body">
                @foreach($project->attachments as $attachment)
                    <div class="mb-3">
                        <p><strong>Attachment:</strong> {{ $attachment->file_name }}</p>
                        <p><strong>Description:</strong> {{ $attachment->description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Signature and Approval Sections with page break control -->
    <div class="container" style="page-break-before: always;">
        <h2>Signatures</h2>
        <table class="signature-table">
            <thead>
                <tr>
                    <th>Person</th>
                    <th>Signature</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Project Executor<br>{{ $projectRoles['executor'] ?? 'N/A' }}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Project Incharge<br>{{ $projectRoles['incharge'] ?? 'N/A' }}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>President of the Society / Chair Person of the Trust<br>{{ $projectRoles['president'] ?? 'N/A' }}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Project Sanctioned / Authorised by<br>{{ $projectRoles['authorizedBy'] }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <h3>APPROVAL - To be filled by the Project Coordinator:</h3>
        <table class="signature-table">
            <tbody>
                <tr>
                    <td>Amount approved</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Remarks if any</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Project Coordinator</td>
                    <td>{{ $projectRoles['coordinator'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Signature</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

</body>
</html>
