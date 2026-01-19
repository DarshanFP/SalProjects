<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Budget Report - {{ $project->project_id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14pt;
        }
        .project-info {
            margin-bottom: 20px;
        }
        .project-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .project-info td {
            padding: 5px;
            border: 1px solid #ddd;
        }
        .project-info td:first-child {
            font-weight: bold;
            width: 30%;
            background-color: #f5f5f5;
        }
        .budget-summary {
            margin-bottom: 20px;
        }
        .budget-summary h3 {
            background-color: #4472C4;
            color: white;
            padding: 10px;
            margin: 0;
        }
        .budget-summary table {
            width: 100%;
            border-collapse: collapse;
        }
        .budget-summary th,
        .budget-summary td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: right;
        }
        .budget-summary th {
            background-color: #4472C4;
            color: white;
            text-align: center;
        }
        .budget-summary tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .budget-items {
            margin-bottom: 20px;
        }
        .budget-items h3 {
            background-color: #4472C4;
            color: white;
            padding: 10px;
            margin: 0;
        }
        .budget-items table {
            width: 100%;
            border-collapse: collapse;
        }
        .budget-items th,
        .budget-items td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: right;
        }
        .budget-items th {
            background-color: #4472C4;
            color: white;
            text-align: center;
        }
        .budget-items td:first-child,
        .budget-items td:nth-child(2) {
            text-align: left;
        }
        .budget-items tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-left: 4px solid;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-color: #0dcaf0;
            color: #055160;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Budget Report</h1>
        <h2>Project: {{ $project->project_title ?? $project->project_id }}</h2>
        <p>Generated on: {{ date('F d, Y') }}</p>
    </div>

    <div class="project-info">
        <table>
            <tr>
                <td>Project ID</td>
                <td>{{ $project->project_id }}</td>
            </tr>
            <tr>
                <td>Project Type</td>
                <td>{{ $project->project_type }}</td>
            </tr>
            <tr>
                <td>Executor</td>
                <td>{{ $project->user->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Status</td>
                <td>{{ $project->status }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($validation['errors']) || !empty($validation['warnings']))
        <div class="budget-summary">
            <h3>Validation Warnings</h3>
            @if(!empty($validation['errors']))
                @foreach($validation['errors'] as $error)
                    <div class="alert alert-danger">
                        <strong>Error:</strong> {{ $error['message'] }}
                    </div>
                @endforeach
            @endif
            @if(!empty($validation['warnings']))
                @foreach($validation['warnings'] as $warning)
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> {{ $warning['message'] }}
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    <div class="budget-summary">
        <h3>Budget Summary</h3>
        <table>
            <tr>
                <th>Item</th>
                <th>Amount (Rs.)</th>
            </tr>
            <tr>
                <td>Overall Project Budget</td>
                <td>{{ format_indian($budgetData['overall_budget'], 2) }}</td>
            </tr>
            <tr>
                <td>Amount Forwarded</td>
                <td>{{ format_indian($budgetData['amount_forwarded'], 2) }}</td>
            </tr>
            <tr>
                <td>Local Contribution</td>
                <td>{{ format_indian($budgetData['local_contribution'], 2) }}</td>
            </tr>
            <tr>
                <td>Amount Sanctioned</td>
                <td>{{ format_indian($budgetData['amount_sanctioned'], 2) }}</td>
            </tr>
            <tr>
                <td><strong>Opening Balance</strong></td>
                <td><strong>{{ format_indian($budgetData['opening_balance'], 2) }}</strong></td>
            </tr>
            <tr>
                <td>Total Expenses</td>
                <td>{{ format_indian($budgetData['total_expenses'], 2) }}</td>
            </tr>
            <tr>
                <td><strong>Remaining Balance</strong></td>
                <td><strong>{{ format_indian($budgetData['remaining_balance'], 2) }}</strong></td>
            </tr>
            <tr>
                <td>Utilization</td>
                <td>{{ format_indian_percentage($budgetData['percentage_used'], 2) }}</td>
            </tr>
        </table>
    </div>

    @if($project->budgets && $project->budgets->count() > 0)
        <div class="budget-items">
            <h3>Budget Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Particular</th>
                        <th>Costs (Rs.)</th>
                        <th>Rate Multiplier</th>
                        <th>Rate Duration</th>
                        <th>This Phase (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->budgets as $index => $budget)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $budget->particular ?? 'N/A' }}</td>
                            <td>{{ format_indian($budget->rate_quantity ?? 0, 2) }}</td>
                            <td>{{ format_indian($budget->rate_multiplier ?? 0, 2) }}</td>
                            <td>{{ format_indian($budget->rate_duration ?? 0, 2) }}</td>
                            <td>{{ format_indian($budget->this_phase ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background-color: #E7E6E6; font-weight: bold;">
                        <td colspan="2">Total</td>
                        <td>{{ format_indian($project->budgets->sum('rate_quantity'), 2) }}</td>
                        <td>{{ format_indian($project->budgets->sum('rate_multiplier'), 2) }}</td>
                        <td>{{ format_indian($project->budgets->sum('rate_duration'), 2) }}</td>
                        <td>{{ format_indian($project->budgets->sum('this_phase'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
        <p>Generated by: {{ Auth::user()->name ?? 'System' }} on {{ date('F d, Y H:i:s') }}</p>
    </div>
</body>
</html>
