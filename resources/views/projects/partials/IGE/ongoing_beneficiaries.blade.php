<div class="mb-3 card">
    <div class="card-header">
        <h4>Ongoing Beneficiaries (Students who were supported in the previous year and requesting support this academic year)</h4>
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
                <tbody id="ongoing-beneficiaries-rows">
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="beneficiary_name[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="caste[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><textarea name="address[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                        <td><input type="text" name="current_group_year_of_study[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><textarea name="performance_details[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                        <td><button type="button" class="btn btn-danger" onclick="removeOngoingBeneficiaryRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addOngoingBeneficiaryRow()">Add More</button>
    </div>
</div>

<!-- JavaScript to add/remove rows dynamically -->
<script>
    (function(){
    let ongoingBeneficiaryRowIndex = 1;

    function addOngoingBeneficiaryRow() {
        ongoingBeneficiaryRowIndex++;
        const newRow = `
            <tr>
                <td>${ongoingBeneficiaryRowIndex}</td>
                <td><input type="text" name="beneficiary_name[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="caste[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><textarea name="address[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><input type="text" name="current_group_year_of_study[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><textarea name="performance_details[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><button type="button" class="btn btn-danger" onclick="removeOngoingBeneficiaryRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('ongoing-beneficiaries-rows').insertAdjacentHTML('beforeend', newRow);
    }

    function removeOngoingBeneficiaryRow(button) {
        const row = button.closest('tr');
        row.remove();
        updateOngoingBeneficiaryRowNumbers();
    }

    function updateOngoingBeneficiaryRowNumbers() {
        const rows = document.querySelectorAll('#ongoing-beneficiaries-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
        ongoingBeneficiaryRowIndex = rows.length;
    }
})();
</script>
