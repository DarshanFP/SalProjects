<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Institution Information</h4>
    </div>
    <div class="card-body">
        <div class="mb-3 form-group row">
            <label for="year_setup" class="col-md-6 col-form-label">Year the Training Center was set up:</label>
            <div class="col-md-3">
                <input type="number" name="year_setup" class="form-control" value="{{ $institutionInfo->year_setup }}" style="background-color: #202ba3;">
            </div>
        </div>
        <div class="mb-3 form-group row">
            <label for="total_students_trained" class="col-md-6 col-form-label">Total Students Trained Till Date:</label>
            <div class="col-md-3">
                <input type="number" name="total_students_trained" class="form-control" value="{{ $institutionInfo->total_students_trained }}" style="background-color: #202ba3;">
            </div>
        </div>
        <div class="mb-3 form-group row">
            <label for="beneficiaries_last_year" class="col-md-6 col-form-label">Beneficiaries Trained in the Last Year:</label>
            <div class="col-md-3">
                <input type="number" name="beneficiaries_last_year" class="form-control" value="{{ $institutionInfo->beneficiaries_last_year }}" style="background-color: #202ba3;">
            </div>
        </div>
        <div class="form-group">
            <label for="training_outcome">Outcome/Impact of the Training:</label>
            <textarea name="training_outcome" class="form-control" rows="4" style="background-color: #202ba3;">{{ $institutionInfo->training_outcome }}</textarea>
        </div>
    </div>
</div>
