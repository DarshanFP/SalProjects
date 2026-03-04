<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Economic Background of Parents</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="cci-economic-background-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">Description</th>
                        <th>Number</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left;">Agricultural Labour</td>
                        <td><input type="number" name="agricultural_labour_number" value="{{ $economicBackground->agricultural_labour_number ?? '' }}" class="form-control cci-eb-number"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Marginal farmers (less than two and half acres)</td>
                        <td><input type="number" name="marginal_farmers_number" value="{{ $economicBackground->marginal_farmers_number ?? '' }}" class="form-control cci-eb-number"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Parents in self-employment</td>
                        <td><input type="number" name="self_employed_parents_number" value="{{ $economicBackground->self_employed_parents_number ?? '' }}" class="form-control cci-eb-number"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Parents working in informal sector</td>
                        <td><input type="number" name="informal_sector_parents_number" value="{{ $economicBackground->informal_sector_parents_number ?? '' }}" class="form-control cci-eb-number"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Any other</td>
                        <td><input type="number" name="any_other_number" value="{{ $economicBackground->any_other_number ?? '' }}" class="form-control cci-eb-number"></td>
                    </tr>
                    <tr class="table-active">
                        <td style="text-align: right;"><strong>Total</strong></td>
                        <td><strong id="cci-economic-background-total">0</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mb-3">
            <label for="general_remarks" class="form-label">General Remarks</label>
            <textarea name="general_remarks" class="form-control sustainability-textarea" rows="3">{{ $economicBackground->general_remarks ?? '' }}</textarea>
        </div>
    </div>
</div>

<style>
.table td input, .table td textarea {
    width: 100%;
    box-sizing: border-box;
}

.table th, .table td {
    vertical-align: middle;
    text-align: left;
    padding: 0.5rem;
}

input[type='number']::-webkit-outer-spin-button,
input[type='number']::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type='number'] {
    -moz-appearance: textfield;
    appearance: textfield;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function calcCciEconomicBackgroundTotals() {
        var total = 0;
        document.querySelectorAll('#cci-economic-background-table .cci-eb-number').forEach(function(inp) {
            total += parseFloat(inp.value) || 0;
        });
        var totalEl = document.getElementById('cci-economic-background-total');
        if (totalEl) totalEl.textContent = total;
    }
    var table = document.getElementById('cci-economic-background-table');
    if (table) {
        table.addEventListener('input', calcCciEconomicBackgroundTotals);
        calcCciEconomicBackgroundTotals();
    }
});
</script>
