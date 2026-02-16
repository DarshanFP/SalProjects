<div class="mb-3 card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Budget Overview</h4>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-light" onclick="toggleBudgetCharts()" id="toggleChartsBtn">
                <i class="feather icon-bar-chart-2"></i> Toggle Charts
            </button>
            <a href="{{ route('projects.budget.export.excel', $project->project_id) }}" class="btn btn-outline-success">
                <i class="feather icon-download"></i> Export Excel
            </a>
            <a href="{{ route('projects.budget.export.pdf', $project->project_id) }}" class="btn btn-outline-danger">
                <i class="feather icon-file-text"></i> Export PDF
            </a>
        </div>
    </div>
    <div class="card-body">
        @php
            // Use BudgetValidationService to get validated budget data
            use App\Services\BudgetValidationService;
            $budgetSummary = BudgetValidationService::getBudgetSummary($project);
            $budgetData = $budgetSummary['budget_data'];
            $validation = $budgetSummary['validation'];

            // Extract values for easier use in view
            $overallBudget = $budgetData['overall_budget'];
            $amountForwarded = $budgetData['amount_forwarded'];
            $localContribution = $budgetData['local_contribution'];
            $amountSanctioned = $budgetData['amount_sanctioned'];
            $openingBalance = $budgetData['opening_balance'];
            $totalExpenses = $budgetData['total_expenses'];
            $approvedExpenses = $budgetData['approved_expenses'] ?? 0;
            $unapprovedExpenses = $budgetData['unapproved_expenses'] ?? 0;
            $remainingBalance = $budgetData['remaining_balance'];
            $percentageUsed = $budgetData['percentage_used'];
            $approvedPercentage = $budgetData['approved_percentage'] ?? 0;
            $unapprovedPercentage = $budgetData['unapproved_percentage'] ?? 0;
            $percentageRemaining = $budgetData['percentage_remaining'];

            // Filter budgets by current phase to match Edit page logic
            $currentPhase = (int) ($project->current_phase ?? 1);
            $budgetsForShow = ($project->budgets ?? collect())->where('phase', $currentPhase)->values();
        @endphp

        <!-- Budget Validation Warnings and Errors -->
        @if(!$validation['is_valid'] || $validation['has_warnings'])
            <div class="budget-validation-section mb-4">
                @if(!empty($validation['errors']))
                    @foreach($validation['errors'] as $error)
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong><i class="feather icon-alert-circle"></i> Error:</strong> {{ $error['message'] }}
                            @if(isset($error['suggestion']))
                                <br><small>{{ $error['suggestion'] }}</small>
                            @endif
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endforeach
                @endif

                @if(!empty($validation['warnings']))
                    @foreach($validation['warnings'] as $warning)
                        <div class="alert alert-{{ $warning['severity'] === 'error' ? 'danger' : 'warning' }} alert-dismissible fade show" role="alert">
                            <strong><i class="feather icon-{{ $warning['severity'] === 'error' ? 'alert-circle' : 'alert-triangle' }}"></i> Warning:</strong> {{ $warning['message'] }}
                            @if(isset($warning['suggestion']))
                                <br><small>{{ $warning['suggestion'] }}</small>
                            @endif
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endforeach
                @endif

                @if(!empty($validation['info']))
                    @foreach($validation['info'] as $info)
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <strong><i class="feather icon-info"></i> Info:</strong> {{ $info['message'] }}
                            @if(isset($info['suggestion']))
                                <br><small>{{ $info['suggestion'] }}</small>
                            @endif
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif

        <!-- Enhanced Budget Summary Section -->
        <div class="budget-summary-section mb-4">
            <h5 class="mb-3">Budget Summary</h5>
            <div class="budget-summary-grid mb-3">
                <div class="budget-summary-card budget-card-primary">
                    <div class="budget-summary-label">
                        <i class="feather icon-dollar-sign"></i> Total Budget
                    </div>
                    <div class="budget-summary-value">{{ format_indian_currency($openingBalance, 2) }}</div>
                    <div class="budget-summary-note">Available funds</div>
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
                    <div class="budget-summary-note">From approved reports</div>
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
                <div class="budget-summary-card budget-card-{{ $percentageUsed > 90 ? 'danger' : ($percentageUsed > 70 ? 'warning' : 'success') }}">
                    <div class="budget-summary-label">
                        <i class="feather icon-percent"></i> Utilization
                    </div>
                    <div class="budget-summary-value">{{ format_indian_percentage($percentageUsed, 1) }}</div>
                    <div class="budget-summary-note">{{ format_indian_percentage($percentageRemaining, 1) }} remaining</div>
                </div>
            </div>

            <!-- Budget Progress Bar -->
            <div class="budget-progress-section" style="background-color: #1a1d2e; padding: 15px; border-radius: 8px; margin-top: 15px;">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Budget Utilization</span>
                    <span class="text-muted">{{ format_indian_percentage($percentageUsed, 1) }} used</span>
                </div>
                <div class="progress" style="height: 25px; background-color: #2a2d3e;">
                    <div class="progress-bar bg-success"
                         role="progressbar"
                         style="width: {{ $approvedPercentage }}%"
                         title="Approved: {{ format_indian_currency($approvedExpenses, 2) }}"
                         aria-valuenow="{{ $approvedPercentage }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        @if($approvedPercentage > 5)
                            <strong>{{ format_indian_percentage($approvedPercentage, 1) }}</strong>
                        @endif
                    </div>
                    <div class="progress-bar bg-warning"
                         role="progressbar"
                         style="width: {{ $unapprovedPercentage }}%"
                         title="Pending: {{ format_indian_currency($unapprovedExpenses, 2) }}"
                         aria-valuenow="{{ $unapprovedPercentage }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        @if($unapprovedPercentage > 5)
                            <strong>{{ format_indian_percentage($unapprovedPercentage, 1) }}</strong>
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

        <!-- Budget Charts Section (Initially Hidden) -->
        <div class="budget-charts-section mb-4" id="budgetChartsSection" style="display: none;">
            <h5 class="mb-3">Budget Visualization</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Budget vs Expenses</h6>
                        </div>
                        <div class="card-body">
                            <div id="budgetExpensesChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Budget Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div id="budgetDistributionChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Budget Breakdown -->
        <div class="budget-breakdown-section mb-3">
            <h5 class="mb-3">Budget Breakdown</h5>
            <div class="budget-summary-grid mb-3">
                <div class="budget-summary-card">
                    <div class="budget-summary-label">Overall Project Budget</div>
                    <div class="budget-summary-value">{{ format_indian_currency($overallBudget, 2) }}</div>
                </div>
                <div class="budget-summary-card">
                    <div class="budget-summary-label">Amount Forwarded <span class="text-muted">(Existing Funds)</span></div>
                    <div class="budget-summary-value">{{ format_indian_currency($amountForwarded, 2) }}</div>
                </div>
                <div class="budget-summary-card">
                    <div class="budget-summary-label">Local Contribution</div>
                    <div class="budget-summary-value">{{ format_indian_currency($localContribution, 2) }}</div>
                </div>
                <div class="budget-summary-card">
                    <div class="budget-summary-label">Amount Sanctioned</div>
                    <div class="budget-summary-value">{{ format_indian_currency($amountSanctioned, 2) }}</div>
                </div>
                <div class="budget-summary-card">
                    <div class="budget-summary-label">Opening Balance</div>
                    <div class="budget-summary-value">{{ format_indian_currency($openingBalance, 2) }}</div>
                </div>
            </div>
        </div>
        <!-- Budget Items Table -->
        <div class="budget-table-section">
            <h5 class="mb-3">Budget Items</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover budget-table">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 50px; text-align: center; font-weight: 600;">No.</th>
                            <th style="min-width: 200px; text-align: center; font-weight: 600;">Particular</th>
                            <th style="min-width: 100px; text-align: center; font-weight: 600;">Costs (Rs.)</th>
                            <th style="min-width: 100px; text-align: center; font-weight: 600;">Rate Multiplier</th>
                            <th style="min-width: 100px; text-align: center; font-weight: 600;">Rate Duration</th>
                            <th style="min-width: 120px; text-align: center; font-weight: 600;">This Phase (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($budgetsForShow as $index => $budget)
                            <tr>
                                <td style="text-align: center; vertical-align: middle; font-weight: 500;">{{ $index + 1 }}</td>
                                <td class="particular-cell" style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.6;">{{ $budget->particular ?? 'N/A' }}</td>
                                <td style="text-align: right; vertical-align: middle;">{{ format_indian($budget->rate_quantity ?? 0, 2) }}</td>
                                <td style="text-align: right; vertical-align: middle;">{{ format_indian($budget->rate_multiplier ?? 0, 2) }}</td>
                                <td style="text-align: right; vertical-align: middle;">{{ format_indian($budget->rate_duration ?? 0, 2) }}</td>
                                <td style="text-align: right; vertical-align: middle; font-weight: 600;">{{ format_indian($budget->this_phase ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="feather icon-info"></i> No budget items found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th style="text-align: center;"></th>
                            <th style="text-align: center; font-weight: 700;">Total</th>
                            <th style="text-align: right; font-weight: 700;">{{ format_indian($budgetsForShow->sum('rate_quantity'), 2) }}</th>
                            <th style="text-align: right; font-weight: 700;">{{ format_indian($budgetsForShow->sum('rate_multiplier'), 2) }}</th>
                            <th style="text-align: right; font-weight: 700;">{{ format_indian($budgetsForShow->sum('rate_duration'), 2) }}</th>
                            <th style="text-align: right; font-weight: 700;">{{ format_indian($budgetsForShow->sum('this_phase'), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Budget Table Styles */
.budget-table {
    table-layout: auto;
    width: 100%;
    font-size: 0.9rem;
}

.budget-table .particular-cell {
    text-align: left;
    vertical-align: top;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    line-height: 1.5;
    padding: 12px;
    min-width: 200px;
    max-width: 300px;
    height: auto;
}

.budget-table td {
    vertical-align: middle;
    height: auto;
    padding: 10px 8px;
}

.budget-table tr {
    height: auto;
}

.budget-table th {
    vertical-align: middle;
    text-align: center;
    padding: 12px 8px;
    background-color: #f8f9fa;
}

.budget-table tfoot th {
    background-color: #e9ecef;
    border-top: 2px solid #dee2e6;
}

/* Responsive summary grid for budget figures */
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

.budget-charts-section {
    margin-top: 20px;
}

.budget-charts-section .card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.budget-charts-section .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 12px 16px;
}

.budget-charts-section .card-header h6 {
    margin: 0;
    font-weight: 600;
    color: #495057;
}

/* PDF override - will be overridden by PDF template styles */
@media print {
    .budget-summary-card {
        background-color: #ffffff !important;
        color: #000000 !important;
        border: 1px solid #000000 !important;
    }
    .budget-summary-label,
    .budget-summary-value {
        color: #000000 !important;
    }
    .budget-charts-section {
        display: none !important;
    }
}

/* Budget Validation Styles */
.budget-validation-section {
    margin-bottom: 20px;
}

.budget-validation-section .alert {
    border-left: 4px solid;
    border-radius: 4px;
    margin-bottom: 10px;
}

.budget-validation-section .alert-danger {
    border-left-color: #dc3545;
    background-color: #f8d7da;
    color: #721c24;
}

.budget-validation-section .alert-warning {
    border-left-color: #ffc107;
    background-color: #fff3cd;
    color: #856404;
}

.budget-validation-section .alert-info {
    border-left-color: #0dcaf0;
    background-color: #d1ecf1;
    color: #055160;
}

.budget-validation-section .alert strong {
    display: flex;
    align-items: center;
    gap: 6px;
}

.budget-validation-section .alert small {
    display: block;
    margin-top: 6px;
    opacity: 0.9;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .budget-summary-grid {
        grid-template-columns: 1fr;
    }

    .budget-summary-value {
        font-size: 1.25rem;
    }
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle charts visibility
    window.toggleBudgetCharts = function() {
        const chartsSection = document.getElementById('budgetChartsSection');
        const toggleBtn = document.getElementById('toggleChartsBtn');

        if (chartsSection.style.display === 'none') {
            chartsSection.style.display = 'block';
            toggleBtn.innerHTML = '<i class="feather icon-eye-off"></i> Hide Charts';

            // Initialize charts if ApexCharts is available
            if (typeof ApexCharts !== 'undefined') {
                initializeBudgetCharts();
            } else {
                console.warn('ApexCharts library not loaded. Charts will not be displayed.');
            }
        } else {
            chartsSection.style.display = 'none';
            toggleBtn.innerHTML = '<i class="feather icon-bar-chart-2"></i> Toggle Charts';
        }
    };

    // Initialize budget charts
    function initializeBudgetCharts() {
        const budgetData = {
            totalBudget: {{ $openingBalance }},
            totalExpenses: {{ $totalExpenses }},
            remainingBalance: {{ $remainingBalance }}
        };

        // Budget vs Expenses Chart
        const budgetExpensesOptions = {
            series: [{
                name: 'Amount (Rs.)',
                data: [budgetData.totalBudget, budgetData.totalExpenses, budgetData.remainingBalance]
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return 'Rs. ' + val.toLocaleString('en-IN', {maximumFractionDigits: 0});
                }
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: ['Total Budget', 'Total Expenses', 'Remaining Balance']
            },
            yaxis: {
                title: {
                    text: 'Amount (Rs.)'
                },
                labels: {
                    formatter: function(val) {
                        return 'Rs. ' + val.toLocaleString('en-IN', {maximumFractionDigits: 0});
                    }
                }
            },
            fill: {
                opacity: 1,
                colors: ['#667eea', '#11998e', '#3494e6']
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return 'Rs. ' + val.toLocaleString('en-IN', {maximumFractionDigits: 2});
                    }
                }
            }
        };

        const budgetExpensesChart = new ApexCharts(document.querySelector("#budgetExpensesChart"), budgetExpensesOptions);
        budgetExpensesChart.render();

        // Budget Distribution Pie Chart
        const budgetDistributionOptions = {
            series: [budgetData.totalExpenses, budgetData.remainingBalance],
            chart: {
                type: 'donut',
                height: 300
            },
            labels: ['Total Expenses', 'Remaining Balance'],
            colors: ['#11998e', '#3494e6'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '14px',
                                fontWeight: 600
                            },
                            value: {
                                show: true,
                                fontSize: '16px',
                                fontWeight: 700,
                                formatter: function(val) {
                                    return 'Rs. ' + val.toLocaleString('en-IN', {maximumFractionDigits: 0});
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total Budget',
                                fontSize: '14px',
                                fontWeight: 600,
                                formatter: function() {
                                    return 'Rs. ' + budgetData.totalBudget.toLocaleString('en-IN', {maximumFractionDigits: 0});
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return 'Rs. ' + val.toLocaleString('en-IN', {maximumFractionDigits: 2});
                    }
                }
            },
            legend: {
                position: 'bottom'
            }
        };

        const budgetDistributionChart = new ApexCharts(document.querySelector("#budgetDistributionChart"), budgetDistributionOptions);
        budgetDistributionChart.render();
    }
});
</script>
@endpush
