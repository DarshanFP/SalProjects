<div class="mb-3 card">
    <div class="card-header">
        <h4>Annexure</h4>
    </div>
    <div class="card-header">
        <h6>PROJECT'S IMPACT IN THE LIFE OF THE BENEFICIARIES</h6>
    </div>
    <div class="card-body" id="dla_impact-container">
        @foreach ($annexures as $index => $annexure)
            <div class="mb-4 impact-group" data-index="{{ $index + 1 }}">
                <div class="card-header">
                    <h5>Impact {{ $index + 1 }}</h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-label"><strong>S No.:</strong></div>
                        <div class="info-value">{{ $index + 1 }}</div>

                        <div class="info-label"><strong>Name of the Beneficiary:</strong></div>
                        <div class="info-value">{{ $annexure->dla_beneficiary_name }}</div>

                        <div class="info-label"><strong>Date of Support Given:</strong></div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($annexure->dla_support_date)->format('d-m-Y') }}</div>

                        <div class="info-label"><strong>Nature of Self-Employment:</strong></div>
                        <div class="info-value">{{ $annexure->dla_self_employment }}</div>

                        <div class="info-label"><strong>Amount Sanctioned:</strong></div>
                        <div class="info-value">Rs. {{ number_format($annexure->dla_amount_sanctioned, 2) }}</div>

                        <div class="info-label"><strong>Monetary Profit Gained - Monthly:</strong></div>
                        <div class="info-value">Rs. {{ number_format($annexure->dla_monthly_profit, 2) }}</div>

                        <div class="info-label"><strong>Monetary Profit Gained - Per Annum:</strong></div>
                        <div class="info-value">Rs. {{ number_format($annexure->dla_annual_profit, 2) }}</div>

                        <div class="info-label"><strong>Project’s Impact in the Life of the Beneficiary:</strong></div>
                        <div class="info-value">{{ $annexure->dla_impact }}</div>

                        <div class="info-label"><strong>Challenges Faced If Any:</strong></div>
                        <div class="info-value">{{ $annexure->dla_challenges }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
    .info-grid {
        display: grid;
        grid-template-columns: 200px 1fr;
        grid-gap: 10px;
    }

    .info-label {
        font-weight: bold;
    }

    .info-value {
        word-wrap: break-word;
        margin-left: 20px;
    }

    .impact-group {
        border-bottom: 1px solid #ddd;
        padding-bottom: 15px;
    }
</style>
