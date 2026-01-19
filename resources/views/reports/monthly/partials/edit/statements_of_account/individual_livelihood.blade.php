{{-- resources/views/reports/monthly/partials/statements_of_account/individual_livelihood.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>4. Statements of Account</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="account_period" class="form-label">Account Statement Period:</label>
            <div class="d-flex">
                <input type="date" name="account_period_start" class="form-control @error('account_period_start') is-invalid @enderror"
                       value="{{ old('account_period_start', $report->account_period_start ?? '') }}"
                       style="background-color: #202ba3;">
                @error('account_period_start')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <span class="mx-2">to</span>
                <input type="date" name="account_period_end" class="form-control @error('account_period_end') is-invalid @enderror"
                       value="{{ old('account_period_end', $report->account_period_end ?? '') }}"
                       style="background-color: #202ba3;">
                @error('account_period_end')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="amount_sanctioned_overview" class="form-label">Amount Sanctioned: Rs.</label>
            <input type="number" name="amount_sanctioned_overview" class="form-control"
                   value="{{ old('amount_sanctioned_overview', $report->amount_sanctioned_overview ?? $amountSanctioned ?? 0.00) }}" readonly>
        </div>

        <div class="mb-3">
            <label for="amount_in_hand" class="form-label">Total Amount: Rs.</label>
            <input type="number" name="amount_in_hand" class="form-control readonly-input"
                   value="{{ old('amount_in_hand', $report->amount_in_hand ?? ($amountSanctioned ?? 0.00)) }}" readonly>
        </div>

        {{-- Hidden input for report status --}}
        <input type="hidden" name="report_status" value="{{ $report->status ?? 'draft' }}" id="report-status">

        @php
            // Calculate approved expenses from ALL approved reports in the project (excluding current report)
            $projectApprovedExpenses = 0;
            if (isset($project) && $project) {
                // Load all reports for the project if not already loaded
                if (!$project->relationLoaded('reports')) {
                    $project->load('reports.accountDetails');
                }

                foreach ($project->reports as $projectReport) {
                    // Skip current report - we'll calculate its expenses separately
                    if ($projectReport->report_id === $report->report_id) {
                        continue;
                    }

                    if (!$projectReport->relationLoaded('accountDetails')) {
                        $projectReport->load('accountDetails');
                    }

                    if ($projectReport->status === \App\Models\Reports\Monthly\DPReport::STATUS_APPROVED_BY_COORDINATOR) {
                        $projectApprovedExpenses += $projectReport->accountDetails->sum('total_expenses') ?? 0;
                    }
                }
            }
        @endphp
        {{-- Hidden input for project-level approved expenses --}}
        <input type="hidden" id="project-approved-expenses" value="{{ $projectApprovedExpenses }}">

        <!-- Budget Summary Cards -->
        <div class="budget-summary-section mb-4">
            <h5 class="mb-3">Budget Summary</h5>
            <div class="budget-summary-grid mb-3">
                <div class="budget-summary-card budget-card-primary">
                    <div class="budget-summary-label">
                        <i class="feather icon-dollar-sign"></i> Total Budget
                    </div>
                    <div class="budget-summary-value" id="card-total-budget">Rs. 0.00</div>
                    <div class="budget-summary-note">Amount sanctioned</div>
                </div>
                <div class="budget-summary-card budget-card-success">
                    <div class="budget-summary-label">
                        <i class="feather icon-check-circle"></i> Total Expenses
                    </div>
                    <div class="budget-summary-value" id="card-total-expenses">Rs. 0.00</div>
                    <div class="budget-summary-note">Amount spent</div>
                </div>
                <div class="budget-summary-card budget-card-success">
                    <div class="budget-summary-label">
                        <i class="feather icon-check-circle"></i> Approved Expenses
                    </div>
                    <div class="budget-summary-value" id="card-approved-expenses">Rs. 0.00</div>
                    <div class="budget-summary-note">Coordinator approved</div>
                </div>
                <div class="budget-summary-card budget-card-warning">
                    <div class="budget-summary-label">
                        <i class="feather icon-clock"></i> Unapproved Expenses
                    </div>
                    <div class="budget-summary-value" id="card-unapproved-expenses">Rs. 0.00</div>
                    <div class="budget-summary-note">Pending approval</div>
                </div>
                <div class="budget-summary-card budget-card-info">
                    <div class="budget-summary-label">
                        <i class="feather icon-wallet"></i> Remaining Balance
                    </div>
                    <div class="budget-summary-value" id="card-remaining-balance">Rs. 0.00</div>
                    <div class="budget-summary-note">Available balance</div>
                </div>
                <div class="budget-summary-card budget-card-success" id="card-utilization">
                    <div class="budget-summary-label">
                        <i class="feather icon-percent"></i> Utilization
                    </div>
                    <div class="budget-summary-value" id="card-utilization-percent">0.0%</div>
                    <div class="budget-summary-note" id="card-utilization-note">0.0% remaining</div>
                </div>
            </div>

            <!-- Budget Progress Bar -->
            <div class="budget-progress-section" style="background-color: #1a1d2e; padding: 15px; border-radius: 8px; margin-top: 15px;">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Budget Utilization</span>
                    <span class="text-muted" id="progress-text">0.0% used</span>
                </div>
                <div class="progress" style="height: 25px; background-color: #2a2d3e;" id="progress-bar-container">
                    <div class="progress-bar bg-success" id="progress-bar-approved"
                         role="progressbar"
                         style="width: 0%"
                         aria-valuenow="0"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                    <div class="progress-bar bg-warning" id="progress-bar-unapproved"
                         role="progressbar"
                         style="width: 0%"
                         aria-valuenow="0"
                         aria-valuemin="0"
                         aria-valuemax="100">
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

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Particulars</th>
                    <th>Amount Sanctioned Current Year</th>
                    <th>Total Amount</th>
                    <th>Expenses Up to Last Month</th>
                    <th>Expenses of This Month</th>
                    <th>Total Expenses (5+6)</th>
                    <th>Balance Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="account-rows">
                @if(isset($report) && $report->accountDetails)
                    {{-- Edit Mode: Use saved account details --}}
                    @foreach ($report->accountDetails as $index => $accountDetail)
                        <tr data-row-type="{{ $accountDetail->is_budget_row ? 'budget' : 'additional' }}">
                            <input type="hidden" name="account_detail_id[{{$index}}]" value="{{ $accountDetail->account_detail_id }}">
                            <input type="hidden" name="is_budget_row[{{$index}}]" value="{{ $accountDetail->is_budget_row ? '1' : '0' }}">
                            <td>{{ $index + 1 }}</td>
                            <td><input type="text" name="particulars[]" class="form-control" value="{{ old('particulars.' . $index, $accountDetail->particulars) }}" readonly></td>
                            <td><input type="number" name="amount_sanctioned[]" class="form-control" value="{{ old('amount_sanctioned.' . $index, $accountDetail->amount_sanctioned) }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
                            <td><input type="number" name="total_amount[]" class="form-control" value="{{ old('total_amount.' . $index, $accountDetail->amount_sanctioned) }}" readonly></td>
                            <td><input type="number" name="expenses_last_month[]" class="form-control" value="{{ old('expenses_last_month.' . $index, $accountDetail->expenses_last_month) }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
                            <td><input type="number" name="expenses_this_month[]" class="form-control" value="{{ old('expenses_this_month.' . $index, $accountDetail->expenses_this_month) }}" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="total_expenses[]" class="form-control" value="{{ old('total_expenses.' . $index, $accountDetail->total_expenses) }}" readonly></td>
                            <td><input type="number" name="balance_amount[]" class="form-control" value="{{ old('balance_amount.' . $index, $accountDetail->balance_amount) }}" readonly></td>
                            <td>
                                @if(!$accountDetail->is_budget_row)
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button>
                                @else
                                    <span class="badge bg-info">Budget Row</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @elseif(isset($budgets))
                    {{-- Create Mode: Use ILP project budgets --}}
                    @foreach($budgets as $index => $budget)
                    <tr data-row-type="budget">
                        <input type="hidden" name="is_budget_row[{{$index}}]" value="1">
                        <td>{{ $index + 1 }}</td>
                        <td><input type="text" name="particulars[]" class="form-control" value="{{ old('particulars.'.$index, $budget->budget_desc) }}" readonly></td>
                        <td><input type="number" name="amount_sanctioned[]" class="form-control" value="{{ old('amount_sanctioned.'.$index, $budget->amount_requested ?? 0.00) }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
                        <td><input type="number" name="total_amount[]" class="form-control" value="{{ old('total_amount.'.$index, $budget->amount_requested ?? 0.00) }}" readonly></td>
                        <td><input type="number" name="expenses_last_month[]" class="form-control" value="{{ old('expenses_last_month.'.$index, $lastExpenses[$budget->budget_desc] ?? 0.00) }}" readonly></td>
                        <td><input type="number" name="expenses_this_month[]" class="form-control" value="{{ old('expenses_this_month.'.$index, 0.00) }}" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="total_expenses[]" class="form-control" readonly></td>
                        <td><input type="number" name="balance_amount[]" class="form-control" readonly></td>
                        <td>
                            <span class="badge bg-info">Budget Row</span>
                        </td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th></th>
                    <th><input type="number" id="total_sanctioned" class="form-control" readonly></th>
                    <th><input type="number" id="total_amount_total" class="form-control" readonly></th>
                    <th><input type="number" id="total_expenses_last_month" class="form-control" readonly></th>
                    <th><input type="number" id="total_expenses_this_month" class="form-control" readonly></th>
                    <th><input type="number" id="total_expenses_total" class="form-control" readonly></th>
                    <th><input type="number" id="total_balance" class="form-control" readonly></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
        <button type="button" class="btn btn-primary" onclick="addAccountRow()">Add Additional Expense Row</button>

        <div class="mt-3">
            <label for="total_balance_forwarded" class="form-label">Total Balance Amount Forwarded for the Following Month: Rs.</label>
            <input type="number" name="total_balance_forwarded" class="form-control readonly-input" value="{{ old('total_balance_forwarded', $report->total_balance_forwarded ?? 0.00) }}" readonly>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    reindexAccountRows();
    calculateAllRowTotals();
    calculateTotal();
    updateAllBalanceColors();
    updateBudgetSummaryCards(); // Update budget cards on page load
});

function calculateRowTotals(row) {
    const amountSanctioned = parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
    const expensesLastMonth = parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
    const expensesThisMonth = parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;

    const totalAmount = amountSanctioned; // No longer includes amount_forwarded
    const totalExpenses = expensesLastMonth + expensesThisMonth;
    const balanceAmount = totalAmount - totalExpenses;

    row.querySelector('[name="total_amount[]"]').value = totalAmount.toFixed(2);
    row.querySelector('[name="total_expenses[]"]').value = totalExpenses.toFixed(2);
    row.querySelector('[name="balance_amount[]"]').value = balanceAmount.toFixed(2);

    // Update balance color for this row
    const balanceInput = row.querySelector('[name="balance_amount[]"]');
    if (balanceInput) {
        updateBalanceColor(balanceInput);
    }

    calculateTotal();
}

function calculateAllRowTotals() {
    const rows = document.querySelectorAll('#account-rows tr');
    rows.forEach(row => {
        calculateRowTotals(row);
    });
}

function calculateTotal() {
    const rows = document.querySelectorAll('#account-rows tr');
    let totalSanctioned = 0;
    let totalAmountTotal = 0;
    let totalExpensesLastMonth = 0;
    let totalExpensesThisMonth = 0;
    let totalExpensesTotal = 0;
    let totalBalance = 0;

    rows.forEach(row => {
        totalSanctioned += parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
        totalAmountTotal += parseFloat(row.querySelector('[name="total_amount[]"]').value) || 0;
        totalExpensesLastMonth += parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
        totalExpensesThisMonth += parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;
        totalExpensesTotal += parseFloat(row.querySelector('[name="total_expenses[]"]').value) || 0;
        totalBalance += parseFloat(row.querySelector('[name="balance_amount[]"]').value) || 0;
    });

    document.getElementById('total_sanctioned').value = totalSanctioned.toFixed(2);
    document.getElementById('total_amount_total').value = totalAmountTotal.toFixed(2);
    document.getElementById('total_expenses_last_month').value = totalExpensesLastMonth.toFixed(2);
    document.getElementById('total_expenses_this_month').value = totalExpensesThisMonth.toFixed(2);
    document.getElementById('total_expenses_total').value = totalExpensesTotal.toFixed(2);
    document.getElementById('total_balance').value = totalBalance.toFixed(2);

    document.querySelector('[name="total_balance_forwarded"]').value = totalBalance.toFixed(2);

    // Update balance colors after calculation
    updateAllBalanceColors();

    // Update budget summary cards
    updateBudgetSummaryCards();
}

function updateBalanceColor(inputElement) {
    const value = parseFloat(inputElement.value) || 0;

    if (value < 0) {
        inputElement.style.backgroundColor = 'red';
    } else {
        inputElement.style.backgroundColor = '';
    }
}

function updateAllBalanceColors() {
    const balanceFields = document.querySelectorAll('[name="balance_amount[]"], #total_balance');
    balanceFields.forEach(field => {
        updateBalanceColor(field);
    });
}

function addAccountRow() {
    const tableBody = document.getElementById('account-rows');
    const newRow = document.createElement('tr');
    const currentRowCount = tableBody.querySelectorAll('tr').length;

    newRow.setAttribute('data-row-type', 'additional');
    newRow.innerHTML = `
        <input type="hidden" name="is_budget_row[${currentRowCount}]" value="0">
        <td>${currentRowCount + 1}</td>
        <td><input type="text" name="particulars[]" class="form-control" placeholder="Enter expense description" style="background-color: #202ba3;"></td>
        <td><input type="number" name="amount_sanctioned[]" class="form-control" value="0" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
        <td><input type="number" name="total_amount[]" class="form-control" readonly></td>
        <td><input type="number" name="expenses_last_month[]" class="form-control" value="0" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
        <td><input type="number" name="expenses_this_month[]" class="form-control" value="0" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
        <td><input type="number" name="total_expenses[]" class="form-control" readonly></td>
        <td><input type="number" name="balance_amount[]" class="form-control" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button></td>
    `;

    newRow.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            const row = input.closest('tr');
            calculateRowTotals(row);
            calculateTotal();
        });
    });

    tableBody.appendChild(newRow);
    calculateRowTotals(newRow);
    reindexAccountRows();
}

function removeAccountRow(button) {
    const row = button.closest('tr');
    const rowType = row.getAttribute('data-row-type');

    if (rowType === 'budget') {
        alert('Budget rows cannot be removed. Only additional expense rows can be deleted.');
        return;
    }

    if (confirm('Are you sure you want to remove this additional expense row?')) {
        row.remove();
        reindexAccountRows();
        calculateTotal();
    }
}

/**
 * Reindexes all account detail rows after add/remove operations
 * Updates index numbers in the "No." column and form field names/IDs
 * Ensures sequential numbering (1, 2, 3, ...) for all account rows
 *
 * @returns {void}
 */
function reindexAccountRows() {
    const rows = document.querySelectorAll('#account-rows tr');
    rows.forEach((row, index) => {
        const indexCell = row.querySelector('td:first-child');
        if (indexCell) {
            indexCell.textContent = index + 1;
        }
    });
}

/**
 * Update Budget Summary Cards
 * Calculates and displays: Total Budget, Total Expenses, Remaining Balance, and % Utilization
 */
function updateBudgetSummaryCards() {
    // Get total budget from amount_sanctioned_overview input
    const totalBudgetInput = document.querySelector('[name="amount_sanctioned_overview"]');
    const totalBudget = parseFloat(totalBudgetInput ? totalBudgetInput.value : 0) || 0;

    // Get total expenses from footer total (current report expenses)
    const totalExpensesInput = document.getElementById('total_expenses_total');
    const currentReportExpenses = parseFloat(totalExpensesInput ? totalExpensesInput.value : 0) || 0;

    // Get project-level approved expenses from all OTHER approved reports
    const projectApprovedExpensesInput = document.getElementById('project-approved-expenses');
    const projectApprovedExpenses = parseFloat(projectApprovedExpensesInput ? projectApprovedExpensesInput.value : 0) || 0;

    // Check if current report is approved
    const reportStatusInput = document.getElementById('report-status');
    const reportStatus = reportStatusInput ? reportStatusInput.value : 'draft';
    const isCurrentReportApproved = reportStatus === 'approved_by_coordinator';

    // Calculate approved vs unapproved expenses
    // Approved = project approved expenses + current report expenses (if approved)
    const approvedExpenses = projectApprovedExpenses + (isCurrentReportApproved ? currentReportExpenses : 0);
    // Unapproved = current report expenses (if not approved)
    const unapprovedExpenses = isCurrentReportApproved ? 0 : currentReportExpenses;

    // Total expenses for this report
    const totalExpenses = currentReportExpenses;

    // Calculate remaining balance (based on current report budget)
    const remainingBalance = totalBudget - totalExpenses;

    // Calculate utilization percentage
    const utilizationPercent = totalBudget > 0 ? (totalExpenses / totalBudget) * 100 : 0;
    const approvedPercent = totalBudget > 0 ? (approvedExpenses / totalBudget) * 100 : 0;
    const unapprovedPercent = totalBudget > 0 ? (unapprovedExpenses / totalBudget) * 100 : 0;
    const remainingPercent = 100 - utilizationPercent;

    // Update card values
    const cardTotalBudget = document.getElementById('card-total-budget');
    const cardTotalExpenses = document.getElementById('card-total-expenses');
    const cardApprovedExpenses = document.getElementById('card-approved-expenses');
    const cardUnapprovedExpenses = document.getElementById('card-unapproved-expenses');
    const cardRemainingBalance = document.getElementById('card-remaining-balance');
    const cardUtilizationPercent = document.getElementById('card-utilization-percent');
    const cardUtilizationNote = document.getElementById('card-utilization-note');
    const cardUtilization = document.getElementById('card-utilization');

    if (cardTotalBudget) cardTotalBudget.textContent = 'Rs. ' + totalBudget.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (cardTotalExpenses) cardTotalExpenses.textContent = 'Rs. ' + totalExpenses.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (cardApprovedExpenses) cardApprovedExpenses.textContent = 'Rs. ' + approvedExpenses.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (cardUnapprovedExpenses) cardUnapprovedExpenses.textContent = 'Rs. ' + unapprovedExpenses.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (cardRemainingBalance) cardRemainingBalance.textContent = 'Rs. ' + remainingBalance.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (cardUtilizationPercent) cardUtilizationPercent.textContent = utilizationPercent.toFixed(1) + '%';
    if (cardUtilizationNote) cardUtilizationNote.textContent = remainingPercent.toFixed(1) + '% remaining';

    // Update utilization card color based on percentage
    if (cardUtilization) {
        // Remove existing color classes
        cardUtilization.classList.remove('budget-card-success', 'budget-card-warning', 'budget-card-danger');

        // Add appropriate color class
        if (utilizationPercent > 90) {
            cardUtilization.classList.add('budget-card-danger');
        } else if (utilizationPercent > 70) {
            cardUtilization.classList.add('budget-card-warning');
        } else {
            cardUtilization.classList.add('budget-card-success');
        }
    }

    // Update progress bar with two segments
    const progressBarApproved = document.getElementById('progress-bar-approved');
    const progressBarUnapproved = document.getElementById('progress-bar-unapproved');
    const progressText = document.getElementById('progress-text');

    if (progressBarApproved) {
        progressBarApproved.style.width = approvedPercent + '%';
        progressBarApproved.setAttribute('aria-valuenow', approvedPercent);
        progressBarApproved.setAttribute('title', 'Approved: Rs. ' + approvedExpenses.toFixed(2));
        if (approvedPercent > 5) {
            progressBarApproved.innerHTML = '<strong>' + approvedPercent.toFixed(1) + '%</strong>';
        } else {
            progressBarApproved.innerHTML = '';
        }
    }

    if (progressBarUnapproved) {
        progressBarUnapproved.style.width = unapprovedPercent + '%';
        progressBarUnapproved.setAttribute('aria-valuenow', unapprovedPercent);
        progressBarUnapproved.setAttribute('title', 'Pending: Rs. ' + unapprovedExpenses.toFixed(2));
        if (unapprovedPercent > 5) {
            progressBarUnapproved.innerHTML = '<strong>' + unapprovedPercent.toFixed(1) + '%</strong>';
        } else {
            progressBarUnapproved.innerHTML = '';
        }
    }

    if (progressText) {
        progressText.textContent = utilizationPercent.toFixed(1) + '% used';
    }
}
</script>

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
