<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Target Group</h4>
    </div>
    <div class="card-body">
        <div class="mb-3 form-group row">
            <label for="no_of_beneficiaries" class="col-md-6 col-form-label">No of Beneficiaries:</label>
            <div class="col-md-3">
                <input type="number" name="no_of_beneficiaries" class="form-control" value="{{ $targetGroup->no_of_beneficiaries }}" style="background-color: #202ba3;">
            </div>
        </div>

        <div class="form-group">
            <label for="beneficiaries_description_problems">Description of Beneficiaries' Problems</label>
            <textarea name="beneficiaries_description_problems" class="form-control" rows="4" style="background-color: #202ba3;">{{ $targetGroup->beneficiaries_description_problems }}</textarea>
        </div>
    </div>
</div>
