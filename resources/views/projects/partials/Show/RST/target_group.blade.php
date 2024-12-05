<div class="mb-3 card">
    <div class="card-header">
        <h4>Show: Target Group</h4>
    </div>
    <div class="card-body">
        <div class="mb-3 form-group row">
            <label for="tg_no_of_beneficiaries" class="col-md-6 col-form-label">No of Beneficiaries:</label>
            <div class="col-md-3">
                <input type="text" name="tg_no_of_beneficiaries"
                       value="{{ $RSTTargetGroup?->tg_no_of_beneficiaries ?? 'N/A' }}"
                       class="form-control" disabled>
            </div>
        </div>

        <div class="form-group">
            <label for="beneficiaries_description_problems">Description of Beneficiaries' Problems</label>
            <textarea name="beneficiaries_description_problems"
                      class="form-control"
                      rows="4"
                      disabled>{{ $RSTTargetGroup?->beneficiaries_description_problems ?? 'No description provided.' }}</textarea>
        </div>
    </div>
</div>
