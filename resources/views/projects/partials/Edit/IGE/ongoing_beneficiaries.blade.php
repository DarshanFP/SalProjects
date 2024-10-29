{{-- resources/views/projects/partials/Edit/IGE/ongoing_beneficiaries.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Ongoing Beneficiaries (Students who were supported in the previous year and requesting support this academic year)</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Beneficiary Name</th>
                        <th>Caste</th>
                        <th>Address</th>
                        <th>Present Group / Year of Study</th>
                        <th>Performance Details</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="IGS-ongoing-beneficiaries-rows">
                    @forelse($ongoingBeneficiaries as $index => $beneficiary)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><input type="text" name="obeneficiary_name[]" class="form-control" value="{{ old('obeneficiary_name.' . $index, $beneficiary->obeneficiary_name) }}"></td>
                        <td><input type="text" name="ocaste[]" class="form-control" value="{{ old('ocaste.' . $index, $beneficiary->ocaste) }}"></td>
                        <td><textarea name="oaddress[]" class="form-control" rows="2">{{ old('oaddress.' . $index, $beneficiary->oaddress) }}</textarea></td>
                        <td><input type="text" name="ocurrent_group_year_of_study[]" class="form-control" value="{{ old('ocurrent_group_year_of_study.' . $index, $beneficiary->ocurrent_group_year_of_study) }}"></td>
                        <td><textarea name="operformance_details[]" class="form-control" rows="2">{{ old('operformance_details.' . $index, $beneficiary->operformance_details) }}</textarea></td>
                        <td><button type="button" class="btn btn-danger" onclick="IGSremoveOngoingBeneficiaryRow(this)">Remove</button></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">No ongoing beneficiaries found for this project.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="IGSaddOngoingBeneficiaryRow()">Add More</button>
    </div>
</div>

<!-- JavaScript to add/remove rows dynamically -->
<script>
    let IGSongoingBeneficiaryRowIndex = {{ count($ongoingBeneficiaries) }};

    function IGSaddOngoingBeneficiaryRow() {
        IGSongoingBeneficiaryRowIndex++;
        const newRow = `
            <tr>
                <td>${IGSongoingBeneficiaryRowIndex}</td>
                <td><input type="text" name="obeneficiary_name[]" class="form-control"></td>
                <td><input type="text" name="ocaste[]" class="form-control"></td>
                <td><textarea name="oaddress[]" class="form-control" rows="2"></textarea></td>
                <td><input type="text" name="ocurrent_group_year_of_study[]" class="form-control"></td>
                <td><textarea name="operformance_details[]" class="form-control" rows="2"></textarea></td>
                <td><button type="button" class="btn btn-danger" onclick="IGSremoveOngoingBeneficiaryRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('IGS-ongoing-beneficiaries-rows').insertAdjacentHTML('beforeend', newRow);
    }

    function IGSremoveOngoingBeneficiaryRow(button) {
        const row = button.closest('tr');
        row.remove();
        IGSupdateOngoingBeneficiaryRowNumbers();
    }

    function IGSupdateOngoingBeneficiaryRowNumbers() {
        const rows = document.querySelectorAll('#IGS-ongoing-beneficiaries-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
        IGSongoingBeneficiaryRowIndex = rows.length;
    }
</script>

<!-- Styles -->
<style>
    .form-control {
        background-color: #202ba3;
        color: white;
    }
</style>
