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
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .card {
            border: 1px solid #ddd;
            margin-bottom: 20px;
            border-radius: 5px;
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

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
            white-space: normal; /* Allow text wrapping */
        }

        .table th {
            background-color: #f5f5f5;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .column {
            flex: 0 0 48%;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Project Details</h1>
        <div class="card">
            <div class="card-header">
                General Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="column">
                        <p><strong>Society Name:</strong> {{ $project->society_name }}</p>
                        <p><strong>President Name:</strong> {{ $project->president_name }}</p>
                        <p><strong>In Charge Name:</strong> {{ $project->in_charge_name }}</p>
                        <p><strong>Executor Name:</strong> {{ $project->executor_name }}</p>
                        <p><strong>Executor Phone:</strong> {{ $project->executor_mobile }}</p>
                        <p><strong>Executor Email:</strong> {{ $project->executor_email }}</p>
                        <p><strong>Full Address:</strong> {{ $project->full_address }}</p>
                    </div>
                    <div class="column">
                        <p><strong>Overall Project Period:</strong> {{ $project->overall_project_period }} years</p>
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

        <div class="card">
            <div class="card-header">
                Key Information
            </div>
            <div class="card-body">
                <p><strong>Goal of the Project:</strong></p>
                <p>{{ $project->goal }}</p>
            </div>
        </div>

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
                        <div class="card-header">
                            <h4>Phase {{ $phase }}</h4>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount Sanctioned in Phase {{ $phase }}: Rs.</label>
                            <p>{{ number_format($budgets->sum('this_phase'), 2) }}</p>
                        </div>
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
</body>
</html>
