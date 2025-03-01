<div class="mb-3 card">
    <div class="card-header">
        <h4>Budget - Next Phase Development Proposal</h4>
    </div>
    <div class="card-body">
        <div id="phases-container">
            @php $phaseIndex = 0; @endphp
            @forelse($predecessorBudget['phases'] ?? [0] as $phase)
                <div class="phase-card" data-phase="{{ $phaseIndex }}">
                    <div class="card-header">
                        <h4>Phase {{ $phaseIndex + 1 }}</h4>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount Sanctioned in Phase {{ $phaseIndex + 1 }}: Rs.</label>
                        <input type="number" name="phases[{{ $phaseIndex }}][amount_sanctioned]" class="form-control select-input" value="{{ old('phases.' . $phaseIndex . '.amount_sanctioned', $phase['amount_sanctioned'] ?? '') }}">
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
                            @foreach($phase['budget'] ?? [[]] as $rowIndex => $row)
                                <tr>
                                    <td><input type="text" name="phases[{{ $phaseIndex }}][budget][{{ $rowIndex }}][particular]" class="form-control select-input" value="{{ old("phases.$phaseIndex.budget.$rowIndex.particular", $row['particular'] ?? '') }}" style="background-color: #202ba3;"></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $rowIndex }}][rate_quantity]" class="form-control select-input" value="{{ old("phases.$phaseIndex.budget.$rowIndex.rate_quantity", $row['rate_quantity'] ?? '') }}" oninput="calculateBudgetRowTotals(this)" style="background-color: #202ba3;"></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $rowIndex }}][rate_multiplier]" class="form-control select-input" value="{{ old("phases.$phaseIndex.budget.$rowIndex.rate_multiplier", $row['rate_multiplier'] ?? 1) }}" oninput="calculateBudgetRowTotals(this)" style="background-color: #202ba3;"></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $rowIndex }}][rate_duration]" class="form-control select-input" value="{{ old("phases.$phaseIndex.budget.$rowIndex.rate_duration", $row['rate_duration'] ?? 1) }}" oninput="calculateBudgetRowTotals(this)" style="background-color: #202ba3;"></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $rowIndex }}][rate_increase]" class="form-control select-input" value="{{ old("phases.$phaseIndex.budget.$rowIndex.rate_increase", $row['rate_increase'] ?? '') }}" oninput="calculateBudgetRowTotals(this)" style="background-color: #122F6B;"></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $rowIndex }}][this_phase]" class="form-control readonly-input" readonly value="{{ old("phases.$phaseIndex.budget.$rowIndex.this_phase", $row['this_phase'] ?? '') }}"></td>
                                    <td><input type="number" name="phases[{{ $phaseIndex }}][budget][{{ $rowIndex }}][next_phase]" class="form-control select-input" value="{{ old("phases.$phaseIndex.budget.$rowIndex.next_phase", $row['next_phase'] ?? '') }}"></td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)">Remove</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-primary" onclick="addBudgetRow(this)">Add Row</button>
                </div>
                @php $phaseIndex++; @endphp
            @empty
                <p>No budget data available from predecessor project.</p>
            @endforelse
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addPhase()">Add Phase</button>
        <div class="mt-3">
            <label class="form-label">Total Amount Sanctioned: Rs.</label>
            <input type="number" name="total_amount_sanctioned" class="form-control readonly-input" readonly value="{{ old('total_amount_sanctioned', $predecessorBudget['total_amount_sanctioned'] ?? '') }}">
        </div>
    </div>
</div>
