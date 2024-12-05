<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Target Group</h4>
    </div>
    <div class="card-body">
        <div class="mb-3 form-group row">
            <label for="tg_no_of_beneficiaries" class="col-md-6 col-form-label">No of Beneficiaries:</label>
            <div class="col-md-3">
                <input type="number" name="tg_no_of_beneficiaries" value="{{ old('tg_no_of_beneficiaries', $RSTtargetGroup->tg_no_of_beneficiaries ?? '') }}" class="form-control" style="background-color: #202ba3;">
            </div>
        </div>

        <div class="form-group">
            <label for="beneficiaries_description_problems">Description of Beneficiaries' Problems</label>
            <textarea name="beneficiaries_description_problems" class="form-control" rows="4" style="background-color: #202ba3;">{{ old('beneficiaries_description_problems', $RSTtargetGroup->beneficiaries_description_problems ?? '') }}</textarea>
        </div>
    </div>
</div>
