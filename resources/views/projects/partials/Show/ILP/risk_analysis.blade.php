{{-- <pre>{{ print_r($ILPRiskAnalysis, true) }}</pre> --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit Risk Analysis</h4>
    </div>
    <div class="card-body">

        <!-- Identified Risks -->
        <div class="mb-3">
            <label for="identified_risks" class="form-label">Identify risks involved in this small business/enterprise:</label>
            <div class="form-control" style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.6;">{{ isset($ILPRiskAnalysis['identified_risks']) ? $ILPRiskAnalysis['identified_risks'] : '' }}</div>
        </div>

        <!-- Mitigation Measures -->
        <div class="mb-3">
            <label for="mitigation_measures" class="form-label">What are the measures proposed to face the above challenges to limit the risks?</label>
            <div class="form-control" style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.6;">{{ isset($ILPRiskAnalysis['mitigation_measures']) ? $ILPRiskAnalysis['mitigation_measures'] : '' }}</div>
        </div>

        <!-- Business Sustainability -->
        <div class="mb-3">
            <label for="business_sustainability" class="form-label">Explain the sustainability of the business/enterprise:</label>
            <div class="form-control" style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.6;">{{ isset($ILPRiskAnalysis['business_sustainability']) ? $ILPRiskAnalysis['business_sustainability'] : '' }}</div>
        </div>

        <!-- Expected Profits and Outcomes -->
        <div class="mb-3">
            <label for="expected_profits" class="form-label">What are the other expected profits and outcomes foreseen by this initiative?</label>
            <div class="form-control" style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.6;">{{ isset($ILPRiskAnalysis['expected_profits']) ? $ILPRiskAnalysis['expected_profits'] : '' }}</div>
        </div>

    </div>
</div>
