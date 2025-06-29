<div class="section-header">Basic Information</div>
<table class="info-table">
    <tr>
        <td class="info-label">Report ID:</td>
        <td>{{ $report->report_id }}</td>
    </tr>
    <tr>
        <td class="info-label">Project Type:</td>
        <td>{{ $report->project_type }}</td>
    </tr>
    <tr>
        <td class="info-label">Project Name:</td>
        <td>{{ $report->project_name }}</td>
    </tr>
    <tr>
        <td class="info-label">Month:</td>
        <td>{{ $report->month }}</td>
    </tr>
    <tr>
        <td class="info-label">Year:</td>
        <td>{{ $report->year }}</td>
    </tr>
    <tr>
        <td class="info-label">Submitted By:</td>
        <td>{{ $report->user->name ?? 'N/A' }}</td>
    </tr>
    <tr>
        <td class="info-label">Submission Date:</td>
        <td>{{ $report->created_at ? $report->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
    </tr>
</table>
