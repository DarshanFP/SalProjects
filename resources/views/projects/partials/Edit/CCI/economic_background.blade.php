<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Economic Background of Parents</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="text-align: left;">Description</th>
                        <th>Number</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left;">Agricultural Labour</td>
                        <td><input type="number" name="agricultural_labour_number" value="{{ $economicBackground->agricultural_labour_number ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Marginal farmers (less than two and half acres)</td>
                        <td><input type="number" name="marginal_farmers_number" value="{{ $economicBackground->marginal_farmers_number ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Parents in self-employment</td>
                        <td><input type="number" name="self_employed_parents_number" value="{{ $economicBackground->self_employed_parents_number ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Parents working in informal sector</td>
                        <td><input type="number" name="informal_sector_parents_number" value="{{ $economicBackground->informal_sector_parents_number ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Any other</td>
                        <td><input type="number" name="any_other_number" value="{{ $economicBackground->any_other_number ?? '' }}" class="form-control" style="background-color: #202ba3;"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mb-3">
            <label for="general_remarks" class="form-label">General Remarks</label>
            <textarea name="general_remarks" class="form-control" rows="3" style="background-color: #202ba3;">{{ $economicBackground->general_remarks ?? '' }}</textarea>
        </div>
    </div>
</div>

<!-- Styles to maintain consistency with the existing design -->
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

    /* Remove spinner arrows for number inputs */
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
