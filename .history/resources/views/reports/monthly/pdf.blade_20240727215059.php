<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            grid-gap: 10px;
        }
        .info-label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Monthly Report</h1>
    <p>Project ID: {{ $report->project_id }}</p>
    <p>Report ID: {{ $report->report_id }}</p>
    <p>Project Title: {{ $report->project_title }}</p>

    <!-- General Information Section -->
    <div class="info-grid">
        <div class="info-label">Project Type:</div>
        <div>{{ $report->project_type }}</div>
        <div class="info-label">Society Name:</div>
        <div>{{ $report->society_name }}</div>
        <div class="info-label">In Charge:</div>
        <div>{{ $report->in_charge }}</div>
        <div class="info-label">Total Beneficiaries:</div>
        <div>{{ $report->total_beneficiaries }}</div>
        <div class="info-label">Goal:</div>
        <div>{{ $report->goal }}</div>
        <div class="info-label">Report Month & Year:</div>
        <div>{{ \Carbon\Carbon::parse($report->report_month_year)->format('F Y') }}</div>
    </div>
</body>
</html>
