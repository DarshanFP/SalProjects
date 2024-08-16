<!-- resources/views/reports/monthly/partials/edit/dl_annexure_edit.blade.php -->

<div class="mb-3 card">
    <div class="card-header">
        <h4>Annexure</h4>
    </div>
    <div class="card-header">
        <h6>PROJECT'S IMPACT IN THE LIFE OF THE BENEFICIARIES</h6>
    </div>
    <div class="card-body" id="dla_impact-container">
        @foreach ($annexures as $index => $annexure)
            <div class="impact-group" data-index="{{ $index + 1 }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Impact {{ $index + 1 }}
                    <button type="button" class="btn btn-danger btn-sm dla_remove-impact" onclick="dla_removeImpactGroup(this)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3 row">
                        <div class="col-md-1">
                            <label for="dla_s_no[{{ $index }}]" class="form-label">S No.</label>
                            <input type="text" name="dla_s_no[{{ $index }}]" class="form-control" value="{{ $index + 1 }}" readonly>
                        </div>
                        <div class="col-md-11">
                            <label for="dla_beneficiary_name[{{ $index }}]" class="form-label">Name of the Beneficiary</label>
                            <input type="text" name="dla_beneficiary_name[{{ $index }}]" class="form-control" value="{{ $annexure->dla_beneficiary_name }}" style="background-color: #202ba3;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="dla_support_date[{{ $index }}]" class="form-label">Date of support given</label>
                        <input type="date" name="dla_support_date[{{ $index }}]" class="form-control" value="{{ $annexure->dla_support_date }}" style="background-color: #202ba3;">
                    </div>
                    <div class="mb-3">
                        <label for="dla_self_employment[{{ $index }}]" class="form-label">Nature of self-employment</label>
                        <input type="text" name="dla_self_employment[{{ $index }}]" class="form-control" value="{{ $annexure->dla_self_employment }}" style="background-color: #202ba3;">
                    </div>
                    <div class="mb-3">
                        <label for="dla_amount_sanctioned[{{ $index }}]" class="form-label">Amount sanctioned</label>
                        <input type="number" name="dla_amount_sanctioned[{{ $index }}]" class="form-control" value="{{ $annexure->dla_amount_sanctioned }}" style="background-color: #202ba3;">
                    </div>
                    <div class="mb-3">
                        <label for="dla_monthly_profit[{{ $index }}]" class="form-label">Monetary profit gained - Monthly</label>
                        <input type="number" name="dla_monthly_profit[{{ $index }}]" class="form-control" value="{{ $annexure->dla_monthly_profit }}" style="background-color: #202ba3;">
                    </div>
                    <div class="mb-3">
                        <label for="dla_annual_profit[{{ $index }}]" class="form-label">Monetary profit gained - Per annum</label>
                        <input type="number" name="dla_annual_profit[{{ $index }}]" class="form-control" value="{{ $annexure->dla_annual_profit }}" style="background-color: #202ba3;">
                    </div>
                    <div class="mb-3">
                        <label for="dla_impact[{{ $index }}]" class="form-label">Project’s impact in the life of the beneficiary</label>
                        <textarea name="dla_impact[{{ $index }}]" class="form-control" style="background-color: #202ba3;">{{ $annexure->dla_impact }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="dla_challenges[{{ $index }}]" class="form-label">Challenges faced if any is it</label>
                        <textarea name="dla_challenges[{{ $index }}]" class="form-control" style="background-color: #202ba3;">{{ $annexure->dla_challenges }}</textarea>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    function dla_removeImpactGroup(button) {
        const group = button.closest('.impact-group');
        group.remove();
        dla_updateImpactGroupIndexes();
    }

    function dla_updateImpactGroupIndexes() {
        const impactGroups = document.querySelectorAll('.impact-group');
        impactGroups.forEach((group, index) => {
            const sNoInput = group.querySelector('input[name^="dla_s_no"]');
            sNoInput.value = index + 1;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        dla_updateImpactGroupIndexes();
    });
</script>
