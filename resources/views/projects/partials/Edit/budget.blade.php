<div class="mb-3 card">
    <div class="card-header">
        <h4>2. Budget</h4>
    </div>
    <div class="card-body">
        <div id="phases-container">
            @foreach($project->budgets->groupBy('phase') as $phaseIndex => $budgets)
                <div class="phase-card" data-phase="{{ $phaseIndex }}">
                    <div class="card-header">
                        <h4>Phase {{ $phaseIndex }}</h4>
                    </div>
                    @if($phaseIndex > 0)
                        <div class="mb-3">
                            <label for="phases[{{ $phaseIndex }}][amount_forwarded]" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
                            <input type="number" name="phases[{{ $phaseIndex }}][amount_forwarded]" class="form-control" value="{{ $budgets->first()->amount_forwarded ?? '' }}" oninput="calculateBudgetTotals(this.closest('.phase-card'))">
                        </div>
                    @endif
                    <div class="mb-3">
                        <label for="phases[{{ $phaseIndex }}][amount_sanctioned]" class="form-label">Amount Sanctioned in Phase {{ $phaseIndex + 1 }}: Rs.</label>
                        <input type="number" name="phases[{{ $phaseIndex }}][amount_sanctioned]" class="form-control" value="{{ $budgets->first()->amount_sanctioned ?? '' }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="phases[{{ $phaseIndex }}][opening_balance]" class="form-label">Opening balance in Phase {{ $phaseIndex + 1 }}: Rs.</label>
                        <input type="number" name="phases[{{ $phaseIndex }}][opening_balance]" class="form-control" value="{{ $budgets->first()->opening_balance ?? '' }}" readonly>
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
                            @foreach($budgets as $budgetIndex => $budget)
                                <tr>
                                    <td><input type="text" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][particular]" class="form-control" value="{{ $budget->particular }}" ></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][rate_quantity]" class="form-control" value="{{ $budget->rate_quantity }}" oninput="calculateBudgetRowTotals(this)" ></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][rate_multiplier]" class="form-control" value="{{ $budget->rate_multiplier }}" oninput="calculateBudgetRowTotals(this)" ></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][rate_duration]" class="form-control" value="{{ $budget->rate_duration }}" oninput="calculateBudgetRowTotals(this)" ></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][rate_increase]" class="form-control" value="{{ $budget->rate_increase }}" oninput="calculateBudgetRowTotals(this)" ></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][this_phase]" class="form-control" value="{{ $budget->this_phase }}" readonly></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $budgetIndex }}][next_phase]" class="form-control" value="{{ $budget->next_phase }}" readonly></td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th><input type="number" class="total_rate_quantity form-control" readonly></th>
                                <th><input type="number" class="total_rate_multiplier form-control" readonly></th>
                                <th><input type="number" class="total_rate_duration form-control" readonly></th>
                                <th><input type="number" class="total_rate_increase form-control" readonly></th>
                                <th><input type="number" class="total_this_phase form-control" readonly></th>
                                <th><input type="number" class="total_next_phase form-control" readonly></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="button" class="btn btn-primary" onclick="addBudgetRow(this)">Add Row</button>

                    @if($phaseIndex > 0)
                    <div>
                        <button type="button" class="mt-3 btn btn-danger remove-phase">Remove Phase</button>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
        <button id="addPhaseButton" type="button" class="mt-3 btn btn-primary">Add Phase</button>
        <div class="mt-3" style="margin-bottom: 20px;">
            <label for="total_amount_sanctioned" class="form-label">Total Amount Sanctioned: Rs.</label>
            <input type="number" name="total_amount_sanctioned" class="form-control" value="{{ $project->total_amount_sanctioned }}" readonly>
        </div>
    </div>
</div>
