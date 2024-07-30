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
            grid-template-columns: auto auto;
            column-gap: 20px; /* Adjust the space between the columns */
            row-gap: 10px; /* Adjust the space between the rows */
        }
        .info-label {
            font-weight: bold;
            text-align: right; /* Align labels to the right */
        }
    </style>
</head>
<body>
    <h1>Monthly Report</h1>
    <div class="info-grid">
        <div class="info-label">Project ID:</div>
        <div>{{ $report->project_id }}</div>

        <div class="info-label">Report ID:</div>
        <div>{{ $report->report_id }}</div>

        <div class="info-label">Project Title:</div>
        <div>{{ $report->project_title }}</div>

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
