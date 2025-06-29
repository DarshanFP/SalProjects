<style>
/* Force black borders for all table cells, for both web and PDF, overriding Bootstrap and other frameworks */
#general-info-table, .general-info-table,
#general-info-table th, #general-info-table td,
.general-info-table th, .general-info-table td {
    border: 1px solid black !important;
    border-collapse: collapse;
    font-size: 1rem;
}
#general-info-table {
    width: 100%;
    margin-bottom: 20px;
}
#general-info-table td.label, .general-info-table td.label {
    width: 35%;
    font-weight: bold;
}
#general-info-table td.value, .general-info-table td.value {
    width: 65%;
}
</style>

<!-- General Information Section -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Basic Information</h4>
    </div>
    <div class="card-body">
        <table id="general-info-table" class="general-info-table">
            <tbody>
                <tr>
                    <td class="label">Project ID:</td>
                    <td class="value">{{ $project->project_id }}</td>
                </tr>
                <tr>
                    <td class="label">Project Title:</td>
                    <td class="value">{{ $project->project_title }}</td>
                </tr>
                <tr>
                    <td class="label">Project Type:</td>
                    <td class="value">{{ $project->project_type }}</td>
                </tr>
                <tr>
                    <td class="label">Society Name:</td>
                    <td class="value">{{ $project->society_name }}</td>
                </tr>
                <tr>
                    <td class="label">President Name:</td>
                    <td class="value">{{ $project->president_name }}</td>
                </tr>
                <tr>
                    <td class="label">In Charge Name:</td>
                    <td class="value">{{ $project->in_charge_name }}</td>
                </tr>
                <tr>
                    <td class="label">In Charge Phone:</td>
                    <td class="value">{{ $project->in_charge_mobile }}</td>
                </tr>
                <tr>
                    <td class="label">In Charge Email:</td>
                    <td class="value">{{ $project->in_charge_email }}</td>
                </tr>
                <tr>
                    <td class="label">Executor Name:</td>
                    <td class="value">{{ $project->executor_name }}</td>
                </tr>
                <tr>
                    <td class="label">Executor Phone:</td>
                    <td class="value">{{ $project->executor_mobile }}</td>
                </tr>
                <tr>
                    <td class="label">Executor Email:</td>
                    <td class="value">{{ $project->executor_email }}</td>
                </tr>
                <tr>
                    <td class="label">Full Address:</td>
                    <td class="value">{{ $project->full_address }}</td>
                </tr>
                <tr>
                    <td class="label">Overall Project Period:</td>
                    <td class="value">{{ $project->overall_project_period }} years</td>
                </tr>
                <tr>
                    <td class="label">Commencement Month & Year:</td>
                    <td class="value">{{ \Carbon\Carbon::parse($project->commencement_month_year)->format('F Y') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Overall Project Budget:</td>
                    <td class="value">Rs. {{ number_format($project->overall_project_budget, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Amount Forwarded:</td>
                    <td class="value">Rs. {{ number_format($project->amount_forwarded, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Amount Sanctioned:</td>
                    <td class="value">Rs. {{ number_format($project->amount_sanctioned, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Opening Balance:</td>
                    <td class="value">Rs. {{ number_format($project->opening_balance, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Coordinator India Name:</td>
                    <td class="value">{{ $project->coordinator_india_name }}</td>
                </tr>
                <tr>
                    <td class="label">Coordinator India Phone:</td>
                    <td class="value">{{ $project->coordinator_india_phone }}</td>
                </tr>
                <tr>
                    <td class="label">Coordinator India Email:</td>
                    <td class="value">{{ $project->coordinator_india_email }}</td>
                </tr>
                {{-- <tr>
                    <td class="label">Coordinator Luzern Name:</td>
                    <td class="value">{{ $project->coordinator_luzern_name }}</td>
                </tr>
                <tr>
                    <td class="label">Coordinator Luzern Phone:</td>
                    <td class="value">{{ $project->coordinator_luzern_phone }}</td>
                </tr>
                <tr>
                    <td class="label">Coordinator Luzern Email:</td>
                    <td class="value">{{ $project->coordinator_luzern_email }}</td>
                </tr> --}}
                <tr>
                    <td class="label">Status:</td>
                    <td class="value">{{ ucfirst($project->status) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
