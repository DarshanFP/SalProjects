{{-- resources/views/reports/monthly/doc.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .section-header {
            background-color: #f2f2f2;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: bold;
        }
        .info-table, .details-table, .account-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .info-table td, .details-table td, .account-table td {
            padding: 5px 10px;
            border: 1px solid #ddd;
        }
        .info-label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Monthly Report</h1>

    <!-- General Information Section -->
    <div class="section-header">Basic Information</div>
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
            <td class="info-label">Report Month & Year:</td>
            <td>{{ \Carbon\Carbon::parse($report->report_month_year)->format('F Y') }}</td>
        </tr>
    </table>

    <!-- Include Partial Based on Project Type -->
    @if($report->project_type === 'Livelihood Development Projects')
        @include('reports.monthly.partials.view.livelihoodAnnexure', ['report' => $report, 'annexures' => $annexures])
    @elseif($report->project_type === 'Institutional Ongoing Group Educational proposal')
        @include('reports.monthly.partials.view.institutional_ongoing_group', ['report' => $report, 'ageProfiles' => $ageProfiles])
    @elseif($report->project_type === 'Residential Skill Training Proposal 2')
        @include('reports.monthly.partials.view.residential_skill_training', ['report' => $report, 'traineeProfiles' => $traineeProfiles])
    @elseif($report->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER')
        @include('reports.monthly.partials.view.crisis_intervention_center', ['report' => $report, 'inmateProfiles' => $inmateProfiles])
    @endif

    <!-- Objectives Section -->
    @include('reports.monthly.partials.view.objectives', ['report' => $report])

    <!-- Outlook Section -->
    <div class="section-header">Outlooks</div>
    @foreach($report->outlooks as $outlook)
        <table class="details-table">
            <tr>
                <td class="info-label">Date:</td>
                <td>{{ \Carbon\Carbon::parse($outlook->date)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td class="info-label">Action Plan for Next Month:</td>
                <td>{{ $outlook->plan_next_month }}</td>
            </tr>
        </table>
    @endforeach

    <!-- Statements of Account Section -->
    @include('reports.monthly.partials.view.statements_of_account', ['budgets' => $budgets])

    <!-- Photos Section -->
    @include('reports.monthly.partials.view.photos', ['groupedPhotos' => $groupedPhotos])

    <!-- Attachments Section -->
    @include('reports.monthly.partials.view.attachments', ['report' => $report])
</body>
</html>
