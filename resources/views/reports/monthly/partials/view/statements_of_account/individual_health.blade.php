{{-- resources/views/reports/monthly/partials/view/statements_of_account/individual_health.blade.php --}}
<!-- Account Details Section -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Account Details</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-label"><strong>Account Period:</strong></div>
            <div class="info-value">{{ \Carbon\Carbon::parse($report->account_period_start)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($report->account_period_end)->format('d-m-Y') }}</div>
            <div class="info-label"><strong>Amount Sanctioned:</strong></div>
            <div class="info-value">{{ format_indian_currency($report->amount_sanctioned_overview, 2) }}</div>
            <div class="info-label"><strong>Total Amount:</strong></div>
            <div class="info-value">{{ format_indian_currency($report->amount_in_hand, 2) }}</div>
            <div class="info-label"><strong>Balance Forwarded:</strong></div>
            <div class="info-value">{{ format_indian_currency($report->total_balance_forwarded, 2) }}</div>
        </div>

        @php
            // Calculate budget summary values
            $totalBudget = $report->amount_sanctioned_overview ?? 0;
            $currentReportExpenses = $report->accountDetails->sum('total_expenses') ?? 0;

            // Calculate approved expenses from ALL approved reports in the project
            $projectApprovedExpenses = 0;
            $projectUnapprovedExpenses = 0;

            if (isset($project) && $project) {
                // Load all reports for the project if not already loaded
                if (!$project->relationLoaded('reports')) {
                    $project->load('reports.accountDetails');
                }

                foreach ($project->reports as $projectReport) {
                    if (!$projectReport->relationLoaded('accountDetails')) {
                        $projectReport->load('accountDetails');
                    }

                    $reportExpenses = $projectReport->accountDetails->sum('total_expenses') ?? 0;

                    if ($projectReport->status === \App\Models\Reports\Monthly\DPReport::STATUS_APPROVED_BY_COORDINATOR) {
                        $projectApprovedExpenses += $reportExpenses;
                    } else {
                        $projectUnapprovedExpenses += $reportExpenses;
                    }
                }
            } else {
                // Fallback: if project not available, use current report only
                $isApproved = $report->status === \App\Models\Reports\Monthly\DPReport::STATUS_APPROVED_BY_COORDINATOR;
                $projectApprovedExpenses = $isApproved ? $currentReportExpenses : 0;
                $projectUnapprovedExpenses = $isApproved ? 0 : $currentReportExpenses;
            }

            // For display: Approved = all approved reports, Unapproved = current report expenses (if not approved)
            $isCurrentReportApproved = $report->status === \App\Models\Reports\Monthly\DPReport::STATUS_APPROVED_BY_COORDINATOR;
            $approvedExpenses = $projectApprovedExpenses;
            $unapprovedExpenses = $isCurrentReportApproved ? 0 : $currentReportExpenses;

            $totalExpenses = $currentReportExpenses;
            $remainingBalance = $totalBudget - $totalExpenses;
            $utilizationPercent = $totalBudget > 0 ? ($totalExpenses / $totalBudget) * 100 : 0;
            $approvedPercent = $totalBudget > 0 ? ($approvedExpenses / $totalBudget) * 100 : 0;
            $unapprovedPercent = $totalBudget > 0 ? ($unapprovedExpenses / $totalBudget) * 100 : 0;
            $remainingPercent = 100 - $utilizationPercent;

            // Determine card color based on utilization
            $utilizationCardClass = $utilizationPercent > 90 ? 'budget-card-danger' : ($utilizationPercent > 70 ? 'budget-card-warning' : 'budget-card-success');
            $progressBarClass = $utilizationPercent > 90 ? 'bg-danger' : ($utilizationPercent > 70 ? 'bg-warning' : 'bg-success');
        @endphp

        <!-- Budget Summary Cards -->
        <div class="budget-summary-section mb-4">
            <h5 class="mb-3">Budget Summary</h5>
            <div class="budget-summary-grid mb-3">
                <div class="budget-summary-card budget-card-primary">
                    <div class="budget-summary-label">
                        <i class="feather icon-dollar-sign"></i> Total Budget
                    </div>
                    <div class="budget-summary-value">{{ format_indian_currency($totalBudget, 2) }}</div>
                    <div class="budget-summary-note">Amount sanctioned</div>
                </div>
                <div class="budget-summary-card budget-card-success">
                    <div class="budget-summary-label">
                        <i class="feather icon-check-circle"></i> Total Expenses
                    </div>
                    <div class="budget-summary-value">{{ format_indian_currency($totalExpenses, 2) }}</div>
                    <div class="budget-summary-note">Amount spent</div>
                </div>
                <div class="budget-summary-card budget-card-success">
                    <div class="budget-summary-label">
                        <i class="feather icon-check-circle"></i> Approved Expenses
                    </div>
                    <div class="budget-summary-value">{{ format_indian_currency($approvedExpenses, 2) }}</div>
                    <div class="budget-summary-note">Coordinator approved</div>
                </div>
                <div class="budget-summary-card budget-card-warning">
                    <div class="budget-summary-label">
                        <i class="feather icon-clock"></i> Unapproved Expenses
                    </div>
                    <div class="budget-summary-value">{{ format_indian_currency($unapprovedExpenses, 2) }}</div>
                    <div class="budget-summary-note">Pending approval</div>
                </div>
                <div class="budget-summary-card budget-card-info">
                    <div class="budget-summary-label">
                        <i class="feather icon-wallet"></i> Remaining Balance
                    </div>
                    <div class="budget-summary-value">{{ format_indian_currency($remainingBalance, 2) }}</div>
                    <div class="budget-summary-note">Available balance</div>
                </div>
                <div class="budget-summary-card {{ $utilizationCardClass }}">
                    <div class="budget-summary-label">
                        <i class="feather icon-percent"></i> Utilization
                    </div>
                    <div class="budget-summary-value">{{ format_indian_percentage($utilizationPercent, 1) }}</div>
                    <div class="budget-summary-note">{{ format_indian_percentage($remainingPercent, 1) }} remaining</div>
                </div>
            </div>

            <!-- Budget Progress Bar -->
            <div class="budget-progress-section" style="background-color: #1a1d2e; padding: 15px; border-radius: 8px; margin-top: 15px;">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Budget Utilization</span>
                    <span class="text-muted">{{ format_indian_percentage($utilizationPercent, 1) }} used</span>
                </div>
                <div class="progress" style="height: 25px; background-color: #2a2d3e;">
                    <div class="progress-bar bg-success"
                         role="progressbar"
                         style="width: {{ $approvedPercent }}%"
                         title="Approved: {{ format_indian_currency($approvedExpenses, 2) }}"
                         aria-valuenow="{{ $approvedPercent }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        @if($approvedPercent > 5)
                            <strong>{{ format_indian_percentage($approvedPercent, 1) }}</strong>
                        @endif
                    </div>
                    <div class="progress-bar bg-warning"
                         role="progressbar"
                         style="width: {{ $unapprovedPercent }}%"
                         title="Pending: {{ format_indian_currency($unapprovedExpenses, 2) }}"
                         aria-valuenow="{{ $unapprovedPercent }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        @if($unapprovedPercent > 5)
                            <strong>{{ format_indian_percentage($unapprovedPercent, 1) }}</strong>
                        @endif
                    </div>
                </div>
                <!-- Color Legend -->
                <div class="d-flex gap-3 mt-3 align-items-center" style="flex-wrap: wrap;">
                    <div class="d-flex align-items-center">
                        <span class="me-2" style="width: 12px; height: 12px; background-color: #28a745; border-radius: 50%; display: inline-block;"></span>
                        <small class="text-muted">Approved Expenses (Coordinator Approved)</small>
                    </div>
                    <span class="text-muted">,</span>
                    <div class="d-flex align-items-center">
                        <span class="me-2" style="width: 12px; height: 12px; background-color: #ffc107; border-radius: 50%; display: inline-block;"></span>
                        <small class="text-muted">Unapproved Expenses (Pending Approval)</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="fp-text-center1">
            <h5>Budgets Details</h5><br>

            <table class="table table-bordered table-custom">
                <thead>
                    <tr>
                        <th>Particulars</th>
                        <th>Amount Sanctioned</th>
                        <th>Total Amount</th>
                        <th>Expenses Last Month</th>
                        <th>Expenses This Month</th>
                        <th>Total Expenses</th>
                        <th>Balance Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->accountDetails as $accountDetail)
                        <tr>
                            <td>
                                {{ $accountDetail->particulars }}
                                @if($accountDetail->is_budget_row)
                                    <span class="badge bg-info ms-2">Budget Row</span>
                                @endif
                            </td>
                            <td>{{ format_indian_currency($accountDetail->amount_sanctioned, 2) }}</td>
                            <td>{{ format_indian_currency($accountDetail->total_amount, 2) }}</td>
                            <td>{{ format_indian_currency($accountDetail->expenses_last_month, 2) }}</td>
                            <td>{{ format_indian_currency($accountDetail->expenses_this_month, 2) }}</td>
                            <td>{{ format_indian_currency($accountDetail->total_expenses, 2) }}</td>
                            <td>{{ format_indian_currency($accountDetail->balance_amount, 2) }}</td>
                        </tr>
                    @endforeach
                    {{-- Total Row --}}
                    <tr class="table-info font-weight-bold">
                        <td><strong>TOTAL</strong></td>
                        <td><strong>{{ format_indian_currency($report->accountDetails->sum('amount_sanctioned'), 2) }}</strong></td>
                        <td><strong>{{ format_indian_currency($report->accountDetails->sum('total_amount'), 2) }}</strong></td>
                        <td><strong>{{ format_indian_currency($report->accountDetails->sum('expenses_last_month'), 2) }}</strong></td>
                        <td><strong>{{ format_indian_currency($report->accountDetails->sum('expenses_this_month'), 2) }}</strong></td>
                        <td><strong>{{ format_indian_currency($report->accountDetails->sum('total_expenses'), 2) }}</strong></td>
                        <td><strong>{{ format_indian_currency($report->accountDetails->sum('balance_amount'), 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Budget Summary Cards Styles */
.budget-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 15px;
}

.budget-summary-card {
    background-color: #132f6b;
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 8px;
    padding: 16px 18px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.budget-summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.budget-card-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.budget-card-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.budget-card-info {
    background: linear-gradient(135deg, #3494e6 0%, #2980b9 100%);
}

.budget-card-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.budget-card-danger {
    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
}

.budget-summary-label {
    font-size: 0.875rem;
    opacity: 0.95;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.budget-summary-label i {
    font-size: 1rem;
}

.budget-summary-value {
    font-weight: 700;
    font-size: 1.5rem;
    letter-spacing: 0.3px;
    margin-bottom: 4px;
}

.budget-summary-note {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-top: 4px;
}

.budget-progress-section {
    margin-top: 20px;
    padding: 15px;
    background-color: #1a1d2e;
    border-radius: 8px;
}

.budget-progress-section .progress {
    border-radius: 10px;
    overflow: hidden;
}

.budget-progress-section .progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
}
</style>
