{{-- resources/views/projects/partials/Show/CCI/economic_background.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Economic Background of Parents</h4>
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
                        <td>{{ $economicBackground->agricultural_labour_number ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Marginal farmers (less than two and half acres)</td>
                        <td>{{ $economicBackground->marginal_farmers_number ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Parents in self-employment</td>
                        <td>{{ $economicBackground->self_employed_parents_number ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Parents working in informal sector</td>
                        <td>{{ $economicBackground->informal_sector_parents_number ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Any other</td>
                        <td>{{ $economicBackground->any_other_number ?? 'N/A' }}</td>
                    </tr>
                    @php
                        $ebTotal = (is_numeric($economicBackground->agricultural_labour_number ?? null) ? (int)$economicBackground->agricultural_labour_number : 0)
                            + (is_numeric($economicBackground->marginal_farmers_number ?? null) ? (int)$economicBackground->marginal_farmers_number : 0)
                            + (is_numeric($economicBackground->self_employed_parents_number ?? null) ? (int)$economicBackground->self_employed_parents_number : 0)
                            + (is_numeric($economicBackground->informal_sector_parents_number ?? null) ? (int)$economicBackground->informal_sector_parents_number : 0)
                            + (is_numeric($economicBackground->any_other_number ?? null) ? (int)$economicBackground->any_other_number : 0);
                    @endphp
                    <tr class="table-active">
                        <td style="text-align: right;"><strong>Total</strong></td>
                        <td><strong>{{ $ebTotal }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <h5>General Remarks</h5>
            <p>{{ $economicBackground->general_remarks ?? 'No remarks provided.' }}</p>
        </div>
    </div>
</div>

