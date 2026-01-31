<!-- resources/views/projects/partials/budget.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Budget</h4>
    </div>
    <div class="card-body">
        <div id="phases-container">
            {{-- <div class="card-header">
                <h4>P</h4>
            </div> --}}
            {{-- <div class="mb-3">
                <label for="phases[0][amount_sanctioned]" class="form-label">Amount Sanctioned in First Phase: Rs.</label>
                <input type="number" name="phases[0][amount_sanctioned]" class="form-control select-input" value="{{ old('phases.0.amount_sanctioned') }}">
            </div> --}}
            <div class="table-responsive">
                <table class="table table-bordered budget-create-table" style="table-layout: fixed; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No.</th>
                            <th style="width: 40%;">Particular</th>
                            <th style="width: 12%;">Costs</th>
                            <th style="width: 12%;">Rate Multiplier</th>
                            <th style="width: 12%;">Rate Duration</th>
                            <th style="width: 12%;">This Phase (Auto)</th>
                            <th style="width: 7%;">Action</th>
                        </tr>
                    </thead>
                    <tbody class="budget-rows">
                        <tr>
                            <td style="width: 5%; text-align: center; vertical-align: middle;">1</td>
                            <td class="particular-cell-create" style="width: 40%;"><textarea name="phases[0][budget][0][particular]" class="form-control select-input particular-textarea" rows="1">{{ old('phases.0.budget.0.particular') }}</textarea></td>
                            <td style="width: 12%;"><input type="number" name="phases[0][budget][0][rate_quantity]" class="form-control select-input budget-number-input" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_quantity') }}"></td>
                            <td style="width: 12%;"><input type="number" name="phases[0][budget][0][rate_multiplier]" class="form-control select-input budget-number-input" value="{{ old('phases.0.budget.0.rate_multiplier', 1) }}" oninput="calculateBudgetRowTotals(this)"></td>
                            <td style="width: 12%;"><input type="number" name="phases[0][budget][0][rate_duration]" class="form-control select-input budget-number-input" value="{{ old('phases.0.budget.0.rate_duration', 1) }}" oninput="calculateBudgetRowTotals(this)"></td>
                            <td style="width: 12%;"><input type="number" name="phases[0][budget][0][this_phase]" class="form-control readonly-input budget-number-input" readonly value="{{ old('phases.0.budget.0.this_phase') }}"></td>
                            <td style="width: 7%; padding: 4px;"><button type="button" class="btn btn-danger budget-remove-btn" onclick="removeBudgetRow(this)">Remove</button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th style="width: 5%;"></th>
                            <th style="width: 40%;">Total</th>
                            <th style="width: 12%;"><input type="number" class="total_rate_quantity form-control readonly-input budget-number-input" readonly></th>
                            <th style="width: 12%;"><input type="number" class="total_rate_multiplier form-control readonly-input budget-number-input" readonly></th>
                            <th style="width: 12%;"><input type="number" class="total_rate_duration form-control readonly-input budget-number-input" readonly></th>
                            <th style="width: 12%;"><input type="number" class="total_this_phase form-control readonly-input budget-number-input" readonly></th>
                            <th style="width: 7%;"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <button type="button" class="btn btn-primary" onclick="addBudgetRow(this)">Add Row</button>
        </div>
        {{-- <button type="button" class="mt-3 btn btn-primary" onclick="addPhase()">Add Phase</button> --}}
        {{-- Budget Summary Fields --}}
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="overall_project_budget_display" class="form-label">
                        Overall Project Budget: Rs.
                        <small class="text-muted">(Auto-calculated from budget items)</small>
                    </label>
                    <input type="number"
                           id="overall_project_budget_display"
                           class="form-control readonly-input budget-number-input budget-summary-input"
                           readonly
                           value="{{ old('overall_project_budget', 0) }}"
                           >
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="amount_forwarded" class="form-label">
                        Amount Forwarded (Existing Funds): Rs.
                        <small class="text-muted">(Optional - Enter if you have existing funds)</small>
                    </label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           id="amount_forwarded"
                           name="amount_forwarded"
                           class="form-control select-input budget-number-input"
                           value="{{ old('amount_forwarded', 0.00) }}"
                           oninput="calculateBudgetFields()"
                           placeholder="0.00">
                    <div class="form-text text-info">
                        <i class="fas fa-info-circle"></i> Enter any funds you already have available.
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="local_contribution" class="form-label">
                        Local Contribution: Rs.
                        <small class="text-muted">(Optional - Community/Other contributions)</small>
                    </label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           id="local_contribution"
                           name="local_contribution"
                           class="form-control select-input budget-number-input"
                           value="{{ old('local_contribution', 0.00) }}"
                           oninput="calculateBudgetFields()"
                           placeholder="0.00">
                    <div class="form-text text-info">
                        <i class="fas fa-info-circle"></i> Add any community/other contributions committed.
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="amount_sanctioned_preview" class="form-label">
                        Amount Sanctioned (To Request): Rs.
                        <small class="text-muted">(Overall Budget - (Amount Forwarded + Local Contribution))</small>
                    </label>
                    <input type="number"
                           id="amount_sanctioned_preview"
                           name="amount_sanctioned_preview"
                           class="form-control readonly-input budget-number-input budget-summary-input"
                           readonly
                           value="{{ old('amount_sanctioned', 0) }}"
                           >
                    <div class="form-text">
                        <i class="fas fa-calculator"></i> This is the amount you are requesting.
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="opening_balance_preview" class="form-label">
                        Opening Balance: Rs.
                        <small class="text-muted">(Amount Sanctioned + (Amount Forwarded + Local Contribution))</small>
                    </label>
                    <input type="number"
                           id="opening_balance_preview"
                           name="opening_balance_preview"
                           class="form-control readonly-input budget-number-input budget-summary-input"
                           readonly
                           value="{{ old('opening_balance', 0) }}"
                           >
                    <div class="form-text">
                        <i class="fas fa-wallet"></i> Total funds available after approval.
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden field for overall_project_budget to be submitted --}}
        <input type="hidden" name="overall_project_budget" id="overall_project_budget" value="{{ old('overall_project_budget', 0) }}">

        {{-- Legacy fields - keeping for backward compatibility but hidden --}}
        <input type="hidden" name="total_amount_sanctioned" value="{{ old('total_amount_sanctioned') }}">
        <input type="hidden" name="total_amount_forwarded" value="{{ old('total_amount_forwarded') }}">
    </div>
</div>

<style>
.budget-create-table {
    table-layout: fixed;
    width: 100%;
    margin: 0;
    border-collapse: collapse;
}

.budget-create-table th,
.budget-create-table td {
    box-sizing: border-box;
}

.budget-create-table .particular-cell-create {
    text-align: left;
    vertical-align: top;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    padding: 8px;
}

.budget-create-table .particular-cell-create textarea {
    width: 100%;
    word-wrap: break-word;
    overflow-wrap: break-word;
    resize: vertical;
    min-height: 38px;
    height: auto;
    line-height: 1.5;
    overflow-y: hidden;
}

/* Disable scroll/spinner on number inputs */
.budget-number-input {
    -moz-appearance: textfield;
}

.budget-number-input::-webkit-outer-spin-button,
.budget-number-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.budget-number-input[type=number] {
    -moz-appearance: textfield;
}

/* Also apply to total amount fields */
input[name="total_amount_sanctioned"],
input[name="total_amount_forwarded"] {
    -moz-appearance: textfield;
}

input[name="total_amount_sanctioned"]::-webkit-outer-spin-button,
input[name="total_amount_sanctioned"]::-webkit-inner-spin-button,
input[name="total_amount_forwarded"]::-webkit-outer-spin-button,
input[name="total_amount_forwarded"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Auto-resize textarea for particular column */
.particular-textarea {
    resize: vertical;
    min-height: 38px;
    height: auto;
    overflow-y: hidden;
    line-height: 1.5;
}

.particular-textarea:focus {
    overflow-y: auto;
}

/* Smaller Remove button for Action column */
.budget-remove-btn {
    font-size: 10px;
    padding: 2px 6px;
    line-height: 1.2;
    white-space: nowrap;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.budget-create-table td:last-child {
    padding: 4px !important;
    text-align: center;
    vertical-align: middle;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-resize function
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }

    // Apply to existing textareas
    const particularTextareas = document.querySelectorAll('.particular-textarea');
    particularTextareas.forEach(textarea => {
        // Set initial height
        autoResizeTextarea(textarea);

        // Auto-resize on input
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });

    // Also handle dynamically added rows (using event delegation)
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('particular-textarea')) {
            autoResizeTextarea(e.target);
        }
    });
});
</script>
