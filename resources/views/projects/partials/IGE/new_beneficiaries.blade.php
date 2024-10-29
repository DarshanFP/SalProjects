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
                <tbody id="IGS-new-beneficiaries-rows">
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="beneficiary_name[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="caste[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><textarea name="address[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                        <td><input type="text" name="group_year_of_study[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><textarea name="family_background_need[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                        <td><button type="button" class="btn btn-danger" onclick="IGSremoveNewBeneficiaryRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="IGSaddNewBeneficiaryRow()">Add More</button>
    </div>
</div>

<!-- JavaScript to add/remove rows dynamically -->
<script>
    let IGSnewBeneficiaryRowIndex = 1;

    window.IGSaddNewBeneficiaryRow = function() {
        IGSnewBeneficiaryRowIndex++;
        const newRow = `
            <tr>
                <td>${IGSnewBeneficiaryRowIndex}</td>
                <td><input type="text" name="beneficiary_name[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="caste[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><textarea name="address[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><input type="text" name="group_year_of_study[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><textarea name="family_background_need[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><button type="button" class="btn btn-danger" onclick="IGSremoveNewBeneficiaryRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('IGS-new-beneficiaries-rows').insertAdjacentHTML('beforeend', newRow);
    }

    window.IGSremoveNewBeneficiaryRow = function(button) {
        const row = button.closest('tr');
        row.remove();
        IGSupdateNewBeneficiaryRowNumbers();
    }

    function IGSupdateNewBeneficiaryRowNumbers() {
        const rows = document.querySelectorAll('#IGS-new-beneficiaries-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
        IGSnewBeneficiaryRowIndex = rows.length;
    }
</script>
