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
        @php
            // Canonical binding: all financial values from resolvedFundFields only (no raw $project budget fields)
            $resolvedFundFields = $resolvedFundFields ?? [];
        @endphp
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
                @if($project->predecessor)
                    <tr>
                        <td class="label">Predecessor Project:</td>
                        <td class="value">
                            <a href="{{ route('projects.show', $project->predecessor->project_id) }}">
                                {{ $project->predecessor->project_title }} ({{ $project->predecessor->project_id }})
                            </a>
                        </td>
                    </tr>
                @endif
                <tr>
                    <td class="label">Society Name:</td>
                    <td class="value">{{ optional($project->society)->name ?? $project->society_name }}</td>
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
                    <td class="label">Current Phase:</td>
                    <td class="value">{{ $project->current_phase ? 'Phase ' . $project->current_phase : 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Commencement Month & Year:</td>
                    <td class="value">{{ \Carbon\Carbon::parse($project->commencement_month_year)->format('F Y') ?? 'N/A' }}</td>
                </tr>
                @php
                    \Log::info('Blade resolvedFundFields debug', [
                        'exists' => isset($resolvedFundFields),
                        'type' => isset($resolvedFundFields) ? gettype($resolvedFundFields) : null,
                        'is_array' => isset($resolvedFundFields) ? is_array($resolvedFundFields) : null,
                        'value' => $resolvedFundFields ?? null
                    ]);
                @endphp
                <tr>
                    <td class="label">Overall Project Budget:</td>
                    <td class="value">{{ number_format($resolvedFundFields['overall_project_budget'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Amount Forwarded  (Existing Funds):</td>
                    <td class="value">{{ number_format($resolvedFundFields['amount_forwarded'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Local Contribution:</td>
                    <td class="value">{{ number_format($resolvedFundFields['local_contribution'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Amount Requested:</td>
                    <td class="value">{{ number_format($resolvedFundFields['amount_requested'] ?? 0, 2) }}</td>
                </tr>
                @if($project->isApproved())
                <tr>
                    <td class="label">Amount Sanctioned:</td>
                    <td class="value">{{ number_format($resolvedFundFields['amount_sanctioned'] ?? 0, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Opening Balance:</td>
                    <td class="value">{{ number_format($resolvedFundFields['opening_balance'] ?? 0, 2) }}</td>
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
