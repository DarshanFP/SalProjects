<!-- General Information Section -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Basic Information</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-label"><strong>Project ID:</strong></div>
            <div class="info-value">{{ $project->project_id }}</div>

            <div class="info-label"><strong>Project Title:</strong></div>
            <div class="info-value">{{ $project->project_title }}</div>

            <div class="info-label"><strong>Project Type:</strong></div>
            <div class="info-value">{{ $project->project_type }}</div>

            <div class="info-label"><strong>Society Name:</strong></div>
            <div class="info-value">{{ optional($project->society)->name ?? $project->society_name }}</div>

            <div class="info-label"><strong>President Name:</strong></div>
            <div class="info-value">{{ $project->president_name }}</div>

            <div class="info-label"><strong>In Charge Name:</strong></div>
            <div class="info-value">{{ $project->in_charge_name }}</div>

            <div class="info-label"><strong>In Charge Phone:</strong></div>
            <div class="info-value">{{ $project->in_charge_mobile }}</div>

            <div class="info-label"><strong>In Charge Email:</strong></div>
            <div class="info-value">{{ $project->in_charge_email }}</div>

            <div class="info-label"><strong>Executor Name:</strong></div>
            <div class="info-value">{{ $project->executor_name }}</div>

            <div class="info-label"><strong>Executor Phone:</strong></div>
            <div class="info-value">{{ $project->executor_mobile }}</div>

            <div class="info-label"><strong>Executor Email:</strong></div>
            <div class="info-value">{{ $project->executor_email }}</div>

            <div class="info-label"><strong>Full Address:</strong></div>
            <div class="info-value">{{ $project->full_address }}</div>

            <div class="info-label"><strong>Overall Project Period:</strong></div>
            <div class="info-value">{{ $project->overall_project_period }} years</div>

            <div class="info-label"><strong>Commencement Month & Year:</strong></div>
            <div class="info-value">{{ \Carbon\Carbon::parse($project->commencement_month_year)->format('F Y') }}</div>

            <div class="info-label"><strong>Overall Project Budget:</strong></div>
            <div class="info-value">Rs. {{ number_format($project->overall_project_budget, 2) }}</div>

            <div class="info-label"><strong>Amount Forwarded:</strong></div>
            <div class="info-value">Rs. {{ number_format($project->amount_forwarded, 2) }}</div>

            <div class="info-label"><strong>@if($project->isApproved())Amount Sanctioned:@else Amount Requested:@endif</strong></div>
            <div class="info-value">Rs. @php $rf = $resolvedFundFields ?? []; @endphp @if($project->isApproved()){{ number_format((float)($rf['amount_sanctioned'] ?? 0), 2) }}@else{{ number_format((float)($rf['amount_requested'] ?? 0), 2) }}@endif</div>

            <div class="info-label"><strong>Opening Balance:</strong></div>
            <div class="info-value">Rs. {{ number_format($project->opening_balance, 2) }}</div>

            <div class="info-label"><strong>Coordinator India Name:</strong></div>
            <div class="info-value">{{ $project->coordinator_india_name }}</div>

            <div class="info-label"><strong>Coordinator India Phone:</strong></div>
            <div class="info-value">{{ $project->coordinator_india_phone }}</div>

            <div class="info-label"><strong>Coordinator India Email:</strong></div>
            <div class="info-value">{{ $project->coordinator_india_email }}</div>

            <div class="info-label"><strong>Coordinator Luzern Name:</strong></div>
            <div class="info-value">{{ $project->coordinator_luzern_name }}</div>

            <div class="info-label"><strong>Coordinator Luzern Phone:</strong></div>
            <div class="info-value">{{ $project->coordinator_luzern_phone }}</div>

            <div class="info-label"><strong>Coordinator Luzern Email:</strong></div>
            <div class="info-value">{{ $project->coordinator_luzern_email }}</div>

            <div class="info-label"><strong>Status:</strong></div>
            <div class="info-value">{{ ucfirst($project->status) }}</div>
        </div>
    </div>
</div>
