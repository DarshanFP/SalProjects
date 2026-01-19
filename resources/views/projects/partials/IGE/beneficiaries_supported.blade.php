<div class="mb-3 card">
    <div class="card-header">
        <h4>N/umber of Beneficiaries to be Supported this Year</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Class</th>
                        <th>Total Number</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="IGS-beneficiaries-supported-rows">
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="class[]" class="form-control"></td>
                        <td><input type="number" name="total_number[]" class="form-control"></td>
                        <td><button type="button" class="btn btn-danger" onclick="IGSremoveBeneficiaryRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="IGSaddBeneficiaryRow()">Add More</button>
    </div>
</div>

<!-- JavaScript to add/remove rows dynamically -->
<script>
    let IGSbeneficiaryRowIndex = 1;

    window.IGSaddBeneficiaryRow = function() {
        IGSbeneficiaryRowIndex++;
        const newRow = `
            <tr>
                <td>${IGSbeneficiaryRowIndex}</td>
                <td><input type="text" name="class[]" class="form-control"></td>
                <td><input type="number" name="total_number[]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger" onclick="IGSremoveBeneficiaryRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('IGS-beneficiaries-supported-rows').insertAdjacentHTML('beforeend', newRow);
    }

    window.IGSremoveBeneficiaryRow = function(button) {
        const row = button.closest('tr');
        row.remove();
        IGSupdateBeneficiaryRowNumbers();
    }

    function IGSupdateBeneficiaryRowNumbers() {
        const rows = document.querySelectorAll('#IGS-beneficiaries-supported-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
        IGSbeneficiaryRowIndex = rows.length;
    }
</script>
