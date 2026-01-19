{{-- resources/views/projects/partials/Edit/IIES/financial_support.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Information on Scope of Receiving Financial Support</h4>
    </div>
    <div class="card-body">
        <!-- Government Scholarship Eligibility -->
        <div class="form-group">
            <label>Is the beneficiary eligible for the government / any other Scholarship?</label>
            <div class="form-check">
                <input type="radio" name="govt_eligible_scholarship" class="form-check-input" value="1" {{ $financialSupport->govt_eligible_scholarship == 1 ? 'checked' : '' }}>
                <label class="form-check-label">Yes</label>
            </div>
            <div class="form-check">
                <input type="radio" name="govt_eligible_scholarship" class="form-check-input" value="0" {{ $financialSupport->govt_eligible_scholarship == 0 ? 'checked' : '' }}>
                <label class="form-check-label">No</label>
            </div>
        </div>

        <!-- Expected Amount of Scholarship -->
        <div class="form-group">
            <label for="scholarship_amt">Expected amount of Scholarship:</label>
            <input type="number" name="scholarship_amt" id="scholarship_amt" class="form-control" step="0.01" value="{{ old('scholarship_amt', $financialSupport->scholarship_amt) }}">
        </div>

        <!-- Eligibility for Other Scholarships -->
        <div class="form-group">
            <label>Is the beneficiary eligible for any other Scholarship?</label>
            <div class="form-check">
                <input type="radio" name="other_eligible_scholarship" class="form-check-input" value="1" {{ $financialSupport->other_eligible_scholarship == 1 ? 'checked' : '' }}>
                <label class="form-check-label">Yes</label>
            </div>
            <div class="form-check">
                <input type="radio" name="other_eligible_scholarship" class="form-check-input" value="0" {{ $financialSupport->other_eligible_scholarship == 0 ? 'checked' : '' }}>
                <label class="form-check-label">No</label>
            </div>
        </div>

        <!-- Expected Amount of Other Scholarships -->
        <div class="form-group">
            <label for="other_scholarship_amt">Expected amount of other Scholarships:</label>
            <input type="number" name="other_scholarship_amt" id="other_scholarship_amt" class="form-control" step="0.01" value="{{ old('other_scholarship_amt', $financialSupport->other_scholarship_amt) }}">
        </div>

        <!-- Family Contribution -->
        <div class="form-group">
            <label for="family_contrib">Family contribution:</label>
            <input type="number" name="family_contrib" id="family_contrib" class="form-control" step="0.01" value="{{ old('family_contrib', $financialSupport->family_contrib) }}">
        </div>

        <!-- Reason for No Family Contribution -->
        <div class="form-group">
            <label for="no_contrib_reason">If no contribution from family, mention the reasons:</label>
            <textarea name="no_contrib_reason" id="no_contrib_reason" class="form-control" rows="3">{{ old('no_contrib_reason', $financialSupport->no_contrib_reason) }}</textarea>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    .form-control {
        background-color: #202ba3;
        color: white;
    }
</style>
