<!-- resources/views/projects/partials/budget.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>2. Budget</h4>
    </div>
    <div class="card-body">
        <div id="phases-container">
            <div class="phase-card" data-phase="0">
                <div class="card-header">
                    <h4>Phase 1</h4>
                </div>
                <div class="mb-3">
                    <label for="phases[0][amount_sanctioned]" class="form-label">Amount Sanctioned in First Phase: Rs.</label>
                    <input type="number" name="phases[0][amount_sanctioned]" class="form-control select-input" value="{{ old('phases.0.amount_sanctioned') }}" required>
                </div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Particular</th>
                            <th>Costs</th>
                            <th>Rate Multiplier</th>
                            <th>Rate Duration</th>
                            <th>Rate Increase (next phase)</th>
                            <th>This Phase (Auto)</th>
                            <th>Next Phase (Auto)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="budget-rows">
                        <tr>
                            <td><input type="text" name="phases[0][budget][0][particular]" class="form-control select-input" value="{{ old('phases.0.budget.0.particular') }}"></td>
                            <td><input type="number" name="phases[0][budget][0][rate_quantity]" class="form-control select-input" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_quantity') }}"></td>
                            <td><input type="number" name="phases[0][budget][0][rate_multiplier]" class="form-control select-input" value="1" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_multiplier', 1) }}"></td>
                            <td><input type="number" name="phases[0][budget][0][rate_duration]" class="form-control select-input" value="1" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_duration', 1) }}"></td>
                            <td><input type="number" name="phases[0][budget][0][rate_increase]" class="form-control select-input" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_increase') }}"></td>
                            <td><input type="number" name="phases[0][budget][0][this_phase]" class="form-control readonly-input" readonly value="{{ old('phases.0.budget.0.this_phase') }}"></td>
                            <td><input type="number" name="phases[0][budget][0][next_phase]" class="form-control select-input" value="{{ old('phases.0.budget.0.next_phase') }}"></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <th><input type="number" class="total_rate_quantity form-control readonly-input" readonly></th>
                            <th><input type="number" class="total_rate_multiplier form-control readonly-input" readonly></th>
                            <th><input type="number" class="total_rate_duration form-control readonly-input" readonly></th>
                            <th><input type="number" class="total_rate_increase form-control readonly-input" readonly></th>
                            <th><input type="number" class="total_this_phase form-control readonly-input" readonly></th>
                            <th><input type="number" class="total_next_phase form-control readonly-input" readonly></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                <button type="button" class="btn btn-primary" onclick="addBudgetRow(this)">Add Row</button>
            </div>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addPhase()">Add Phase</button>
        <div class="mt-3" style="margin-bottom: 20px;">
            <label for="total_amount_sanctioned" class="form-label">Total Amount Sanctioned: Rs.</label>
            <input type="number" name="total_amount_sanctioned" class="form-control readonly-input" readonly value="{{ old('total_amount_sanctioned') }}">
        </div>
        <div class="mt-3" style="margin-bottom: 20px;">
            <label for="total_amount_forwarded" class="form-label">Total Amount Forwarded: Rs.</label>
            <input type="number" name="total_amount_forwarded" class="form-control readonly-input" readonly value="{{ old('total_amount_forwarded') }}">
        </div>
    </div>
</div>
