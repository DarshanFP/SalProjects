<div class="mb-3 card">
    <div class="card-header">
        <h4>Number of Beneficiaries to be Supported this Year</h4>
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
                <tbody id="beneficiaries-supported-rows">
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="class[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="total_number[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><button type="button" class="btn btn-danger" onclick="removeBeneficiaryRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addBeneficiaryRow()">Add More</button>
    </div>
</div>

<!-- JavaScript to add/remove rows dynamically -->
<script>
    (function(){
    let beneficiaryRowIndex = 1;

    function addBeneficiaryRow() {
        beneficiaryRowIndex++;
        const newRow = `
            <tr>
                <td>${beneficiaryRowIndex}</td>
                <td><input type="text" name="class[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="number" name="total_number[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><button type="button" class="btn btn-danger" onclick="removeBeneficiaryRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('beneficiaries-supported-rows').insertAdjacentHTML('beforeend', newRow);
    }

    function removeBeneficiaryRow(button) {
        const row = button.closest('tr');
        row.remove();
        updateBeneficiaryRowNumbers();
    }

    function updateBeneficiaryRowNumbers() {
        const rows = document.querySelectorAll('#beneficiaries-supported-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
        beneficiaryRowIndex = rows.length;
    }
})();
</script>
