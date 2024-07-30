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
            display: flex;
            flex-wrap: wrap;
        }
        .info-item {
            display: flex;
            width: 100%;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            min-width: 200px; /* Adjust the width as needed */
            margin-right: 20px; /* Adjust the space as needed */
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
        <div class="info-item">
            <div class="info-label">Project Type:</div>
            <div>{{ $report->project_type }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Society Name:</div>
            <div>{{ $report->society_name }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">In Charge:</div>
            <div>{{ $report->in_charge }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Total Beneficiaries:</div>
            <div>{{ $report->total_beneficiaries }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Goal:</div>
            <div>{{ $report->goal }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Report Month & Year:</div>
            <div>{{ \Carbon\Carbon::parse($report->report_month_year)->format('F Y') }}</div>
        </div>
    </div>
</body>
</html>
