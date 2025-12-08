<div class="mb-3 card">
    <div class="card-header">
        <h4>Budget</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Total Amount Requested: Rs.</label>
            <p>{{ number_format(($project->total_amount_sanctioned && $project->total_amount_sanctioned > 0) ? $project->total_amount_sanctioned : $project->budgets->sum('this_phase'), 2) }}</p>
        </div>
        <table class="table table-bordered table-custom">
            <thead>
                <tr>
                    <th style="width: 40%;">Particular</th>
                    <th style="width: 15%;">Costs</th>
                    <th style="width: 15%;">Rate Multiplier</th>
                    <th style="width: 15%;">Rate Duration</th>
                    <th style="width: 15%;">This Phase (Auto)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($project->budgets as $budget)
                    <tr>
                        <td>{{ $budget->particular }}</td>
                        <td>{{ number_format($budget->rate_quantity, 2) }}</td>
                        <td>{{ number_format($budget->rate_multiplier, 2) }}</td>
                        <td>{{ number_format($budget->rate_duration, 2) }}</td>
                        <td>{{ number_format($budget->this_phase, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th>{{ number_format($project->budgets->sum('rate_quantity'), 2) }}</th>
                    <th>{{ number_format($project->budgets->sum('rate_multiplier'), 2) }}</th>
                    <th>{{ number_format($project->budgets->sum('rate_duration'), 2) }}</th>
                    <th>{{ number_format($project->budgets->sum('this_phase'), 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
