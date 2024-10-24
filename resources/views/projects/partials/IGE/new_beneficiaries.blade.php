<div class="mb-3 card">
    <div class="card-header">
        <h4>New Beneficiaries for the Current Year Proposed</h4>
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
                        <th>Group / Year of Study</th>
                        <th>Family Background and Need of Support</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="new-beneficiaries-rows">
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="beneficiary_name[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="caste[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><textarea name="address[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                        <td><input type="text" name="group_year_of_study[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><textarea name="family_background_need[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                        <td><button type="button" class="btn btn-danger" onclick="removeNewBeneficiaryRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addNewBeneficiaryRow()">Add More</button>
    </div>
</div>

<!-- JavaScript to add/remove rows dynamically -->
<script>
    let newBeneficiaryRowIndex = 1;

    function addNewBeneficiaryRow() {
        newBeneficiaryRowIndex++;
        const newRow = `
            <tr>
                <td>${newBeneficiaryRowIndex}</td>
                <td><input type="text" name="beneficiary_name[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="caste[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><textarea name="address[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><input type="text" name="group_year_of_study[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><textarea name="family_background_need[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><button type="button" class="btn btn-danger" onclick="removeNewBeneficiaryRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('new-beneficiaries-rows').insertAdjacentHTML('beforeend', newRow);
    }

    function removeNewBeneficiaryRow(button) {
        const row = button.closest('tr');
        row.remove();
        updateNewBeneficiaryRowNumbers();
    }

    function updateNewBeneficiaryRowNumbers() {
        const rows = document.querySelectorAll('#new-beneficiaries-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
        newBeneficiaryRowIndex = rows.length;
    }
</script>
