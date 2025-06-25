<div class="mb-3 card">
    <div class="card-header">
        <h4>Budget</h4>
    </div>
    <div class="card-body">
        @php
            $groupedBudgets = $project->budgets->groupBy('phase');
        @endphp

        @foreach($groupedBudgets as $phase => $budgets)
            <div class="mb-3 phase-card">
                <div class="card-header">
                    <h5>Phase {{ $phase }}</h5>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount Sanctioned in Phase {{ $phase }}: Rs.</label>
                    <p>{{ number_format($budgets->sum('this_phase'), 2) }}</p>
                </div>
                <table class="table table-bordered table-custom">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Particular</th>
                            <th style="width: 10%;">Costs</th>
                            <th style="width: 10%;">Rate Multiplier</th>
                            <th style="width: 10%;">Rate Duration</th>
                            <th style="width: 10%;">Rate Increase (next phase)</th>
                            <th style="width: 10%;">This Phase (Auto)</th>
                            <th style="width: 10%;">Next Phase (Auto)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($budgets as $budget)
                            <tr>
                                <td>{{ $budget->particular }}</td>
                                <td>{{ number_format($budget->rate_quantity, 2) }}</td>
                                <td>{{ number_format($budget->rate_multiplier, 2) }}</td>
                                <td>{{ number_format($budget->rate_duration, 2) }}</td>
                                <td>{{ number_format($budget->rate_increase, 2) }}</td>
                                <td>{{ number_format($budget->this_phase, 2) }}</td>
                                <td>{{ number_format($budget->next_phase, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <th>{{ number_format($budgets->sum('rate_quantity'), 2) }}</th>
                            <th>{{ number_format($budgets->sum('rate_multiplier'), 2) }}</th>
                            <th>{{ number_format($budgets->sum('rate_duration'), 2) }}</th>
                            <th>{{ number_format($budgets->sum('rate_increase'), 2) }}</th>
                            <th>{{ number_format($budgets->sum('this_phase'), 2) }}</th>
                            <th>{{ number_format($budgets->sum('next_phase'), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach
    </div>
</div>
