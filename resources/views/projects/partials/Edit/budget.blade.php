<div class="mb-3 card">
    <div class="card-header">
        <h4>2. Budget</h4>
    </div>
    <div class="card-body">
        <div id="phases-container">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Particular</th>
                        <th>Costs</th>
                        <th>Rate Multiplier</th>
                        <th>Rate Duration</th>
                        <th>This Phase (Auto)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody class="budget-rows">
                    @if($project->budgets && $project->budgets->count())
                        @foreach($project->budgets as $budgetIndex => $budget)
                            <tr>
                                <td><input type="text" name="phases[0][budget][{{ $budgetIndex }}][particular]" class="form-control select-input" value="{{ old('phases.0.budget.' . $budgetIndex . '.particular', $budget->particular) }}" style="background-color: #202ba3;"></td>
                                <td><input type="number" name="phases[0][budget][{{ $budgetIndex }}][rate_quantity]" class="form-control select-input" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.' . $budgetIndex . '.rate_quantity', $budget->rate_quantity) }}" style="background-color: #202ba3;"></td>
                                <td><input type="number" name="phases[0][budget][{{ $budgetIndex }}][rate_multiplier]" class="form-control select-input" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.' . $budgetIndex . '.rate_multiplier', $budget->rate_multiplier ?? 1) }}" style="background-color: #202ba3;"></td>
                                <td><input type="number" name="phases[0][budget][{{ $budgetIndex }}][rate_duration]" class="form-control select-input" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.' . $budgetIndex . '.rate_duration', $budget->rate_duration ?? 1) }}" style="background-color: #202ba3;"></td>
                                <td><input type="number" name="phases[0][budget][{{ $budgetIndex }}][this_phase]" class="form-control readonly-input" readonly value="{{ old('phases.0.budget.' . $budgetIndex . '.this_phase', $budget->this_phase) }}"></td>
                                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td><input type="text" name="phases[0][budget][0][particular]" class="form-control select-input" value="{{ old('phases.0.budget.0.particular') }}" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="phases[0][budget][0][rate_quantity]" class="form-control select-input" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_quantity') }}" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="phases[0][budget][0][rate_multiplier]" class="form-control select-input" value="1" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_multiplier', 1) }}" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="phases[0][budget][0][rate_duration]" class="form-control select-input" value="1" oninput="calculateBudgetRowTotals(this)" value="{{ old('phases.0.budget.0.rate_duration', 1) }}" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="phases[0][budget][0][this_phase]" class="form-control readonly-input" readonly value="{{ old('phases.0.budget.0.this_phase') }}"></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th><input type="number" class="total_rate_quantity form-control readonly-input" readonly></th>
                        <th><input type="number" class="total_rate_multiplier form-control readonly-input" readonly></th>
                        <th><input type="number" class="total_rate_duration form-control readonly-input" readonly></th>
                        <th><input type="number" class="total_this_phase form-control readonly-input" readonly></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
            <button type="button" class="btn btn-primary" onclick="addBudgetRow(this)">Add Row</button>
        </div>
        <div class="mt-3" style="margin-bottom: 20px;">
            <label for="total_amount_sanctioned" class="form-label">Total Amount Requested: Rs.</label>
            <input type="number" name="total_amount_sanctioned" id="total_amount_sanctioned" class="form-control readonly-input" readonly value="{{ old('total_amount_sanctioned', $project->total_amount_sanctioned) }}">
        </div>
        <div class="mt-3" style="margin-bottom: 20px;">
            <label for="total_amount_forwarded" class="form-label">Total Amount Sanctioned : Rs.</label>
            <input type="number" name="total_amount_forwarded" class="form-control readonly-input" readonly value="{{ old('total_amount_forwarded', $project->total_amount_forwarded) }}">
        </div>
    </div>
</div>
