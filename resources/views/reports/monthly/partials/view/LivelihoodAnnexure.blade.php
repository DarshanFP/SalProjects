{{-- resources/views/reports/monthly/partials/view/LivelihoodAnnexure.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Annexure</h4>
    </div>
    <div class="card-header">
        <h6>PROJECT'S IMPACT IN THE LIFE OF THE BENEFICIARIES</h6>
    </div>
    <div class="card-body">
        @foreach ($annexures as $index => $annexure)
            <div class="mb-4">
                <div class="card-header">
                    <h5>Impact {{ $index + 1 }}</h5>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>S No.:</strong></div>
                    <div class="col-6 report-value-entered">{{ $index + 1 }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Name of the Beneficiary:</strong></div>
                    <div class="col-6 report-value-entered">{{ $annexure->dla_beneficiary_name }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Date of Support Given:</strong></div>
                    <div class="col-6 report-value-entered">{{ \Carbon\Carbon::parse($annexure->dla_support_date)->format('d-m-Y') }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Nature of Self-Employment:</strong></div>
                    <div class="col-6 report-value-entered">{{ $annexure->dla_self_employment }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Amount Sanctioned:</strong></div>
                    <div class="col-6 report-value-entered">{{ format_indian_currency($annexure->dla_amount_sanctioned, 2) }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Monetary Profit Gained - Monthly:</strong></div>
                    <div class="col-6 report-value-entered">{{ format_indian_currency($annexure->dla_monthly_profit, 2) }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Monetary Profit Gained - Per Annum:</strong></div>
                    <div class="col-6 report-value-entered">{{ format_indian_currency($annexure->dla_annual_profit, 2) }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Project’s Impact in the Life of the Beneficiary:</strong></div>
                    <div class="col-6 report-value-entered">{{ $annexure->dla_impact }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Challenges Faced If Any:</strong></div>
                    <div class="col-6 report-value-entered">{{ $annexure->dla_challenges }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
    .card-header {
        margin-bottom: 10px;
    }

    .row {
        margin-bottom: 10px;
    }
</style>
