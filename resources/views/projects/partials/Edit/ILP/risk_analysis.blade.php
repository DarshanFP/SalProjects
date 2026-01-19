{{-- resources/views/projects/partials/Edit/ILP/risk_analysis.blade.php
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit Risk Analysis</h4>
    </div>
    <div class="card-body">

        <!-- Identified Risks -->
        <div class="mb-3">
            <label for="identified_risks" class="form-label">Identify risks involved in this small business/enterprise:</label>
            <textarea name="identified_risks" class="form-control" rows="3" placeholder="Describe the risks">{{ $riskAnalysis->identified_risks }}</textarea>
        </div>

        <!-- Mitigation Measures -->
        <div class="mb-3">
            <label for="mitigation_measures" class="form-label">What are the measures proposed to face the above challenges to limit the risks?</label>
            <textarea name="mitigation_measures" class="form-control" rows="3" placeholder="Describe the mitigation measures">{{ $riskAnalysis->mitigation_measures }}</textarea>
        </div>

        <!-- Business Sustainability -->
        <div class="mb-3">
            <label for="business_sustainability" class="form-label">Explain the sustainability of the business/enterprise:</label>
            <textarea name="business_sustainability" class="form-control" rows="3" placeholder="Explain sustainability">{{ $riskAnalysis->business_sustainability }}</textarea>
        </div>

        <!-- Expected Profits and Outcomes -->
        <div class="mb-3">
            <label for="expected_profits" class="form-label">What are the other expected profits and outcomes foreseen by this initiative?</label>
            <textarea name="expected_profits" class="form-control" rows="3" placeholder="Describe expected profits and outcomes">{{ $riskAnalysis->expected_profits }}</textarea>
        </div>

    </div>
</div> --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit Risk Analysis</h4>
    </div>
    <div class="card-body">

        <!-- Identified Risks -->
        <div class="mb-3">
            <label for="identified_risks" class="form-label">Identify risks involved in this small business/enterprise:</label>
            <textarea name="identified_risks" class="form-control sustainability-textarea" rows="3" placeholder="Describe the risks">{{ $riskAnalysis->identified_risks ?? '' }}</textarea>
        </div>

        <!-- Mitigation Measures -->
        <div class="mb-3">
            <label for="mitigation_measures" class="form-label">What are the measures proposed to face the above challenges to limit the risks?</label>
            <textarea name="mitigation_measures" class="form-control sustainability-textarea" rows="3" placeholder="Describe the mitigation measures">{{ $riskAnalysis->mitigation_measures ?? '' }}</textarea>
        </div>

        <!-- Business Sustainability -->
        <div class="mb-3">
            <label for="business_sustainability" class="form-label">Explain the sustainability of the business/enterprise:</label>
            <textarea name="business_sustainability" class="form-control sustainability-textarea" rows="3" placeholder="Explain sustainability">{{ $riskAnalysis->business_sustainability ?? '' }}</textarea>
        </div>

        <!-- Expected Profits and Outcomes -->
        <div class="mb-3">
            <label for="expected_profits" class="form-label">What are the other expected profits and outcomes foreseen by this initiative?</label>
            <textarea name="expected_profits" class="form-control sustainability-textarea" rows="3" placeholder="Describe expected profits and outcomes">{{ $riskAnalysis->expected_profits ?? '' }}</textarea>
        </div>

    </div>
</div>
