<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <h1>Monthly Report</h1>
    <p>Project ID: {{ $report->project_id }}</p>
    <p>Report ID: {{ $report->report_id }}</p>
    <p>Project Title: {{ $report->project_title }}</p>
</body>
</html>
