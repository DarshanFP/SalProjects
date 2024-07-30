<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px 10px;
        }
        .info-label {
            font-weight: bold;
            width: 30%;
        }
    </style>
</head>
<body>
    <h1>Monthly Report</h1>
    <table class="info-table">
        <tr>
            <td class="info-label">Project ID:</td>
            <td>{{ $report->project_id }}</td>
        </tr>
        <tr>
            <td class="info-label">Report ID:</td>
            <td>{{ $report->report_id }}</td>
        </tr>
        <tr>
            <td class="info-label">Project Title:</td>
            <td>{{ $report->project_title }}</td>
        </tr>
        <tr>
            <td class="info-label">Project Type:</td>
            <td>{{ $report->project_type }}</td>
        </tr>
        <tr>
            <td class="info-label">Society Name:</td>
            <td>{{ $report->society_name }}</td>
        </tr>
        <tr>
            <td class="info-label">In Charge:</td>
            <td>{{ $report->in_charge }}</td>
        </tr>
        <tr>
            <td class="info-label">Total Beneficiaries:</td>
            <td>{{ $report->total_beneficiaries }}</td>
        </tr>
        <tr>
            <td class="info-label">Goal:</td>
            <td>{{ $report->goal }}</td>
        </tr>
        <tr>
            <td class="info-label">Report Month & Year:</td>
            <td>{{ \Carbon\Carbon::parse($report->report_month_year)->format('F Y') }}</td>
        </tr>
    </table>
</body>
</html>
