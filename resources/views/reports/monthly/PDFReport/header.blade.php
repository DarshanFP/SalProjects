<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Report PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.4;
        }
        .info-table, .details-table, .activities-table, .account-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .info-table td, .details-table td, .activities-table td, .account-table td {
            padding: 5px 10px;
            border: 1px solid #ddd;
        }
        .info-label, .details-label { font-weight: bold; width: 30%; }
        .section-header {
            background-color: #f2f2f2;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: bold;
            border-left: 4px solid #007bff;
        }
        .header-row { background-color: #f2f2f2; font-weight: bold; }
        .photo-container {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .photo {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .photo-description {
            font-style: italic;
            color: #666;
            margin-top: 5px;
        }
        .budget-row { background-color: #e8f4fd; }
        .budget-badge {
            background-color: #17a2b8;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            margin-left: 5px;
        }
        .photo-category {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            padding: 5px;
            background-color: #f8f9fa;
            border-left: 3px solid #28a745;
        }
        .photo-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .photo-item {
            flex: 1;
            min-width: 200px;
            max-width: 300px;
            text-align: center;
        }
        .no-photos {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px dashed #ddd;
        }
        .page-break {
            page-break-before: always;
        }
        .avoid-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <h1>Monthly Report</h1>
