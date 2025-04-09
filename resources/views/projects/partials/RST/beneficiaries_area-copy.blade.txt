{{-- resources/views/projects/partials/RST/beneficiaries_area.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Project Area</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Project Area</th>
                        <th>Category of Beneficiary</th>
                        <th>Direct Beneficiaries</th>
                        <th>Indirect Beneficiaries</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="RST-project-area-rows">
                    <tr>
                        <td><input type="text" name="project_area[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="category_beneficiary[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="direct_beneficiaries[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="indirect_beneficiaries[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addRSTProjectAreaRow()">Add More</button>
    </div>
</div>

<script>
    let RSTprojectAreaRowIndex = 1;

    function addRSTProjectAreaRow() {
        RSTprojectAreaRowIndex++;
        const newRow = `
            <tr>
                <td><input type="text" name="project_area[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="category_beneficiary[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="number" name="direct_beneficiaries[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="number" name="indirect_beneficiaries[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('RST-project-area-rows').insertAdjacentHTML('beforeend', newRow);
    }

    function removeRSTProjectAreaRow(button) {
        const row = button.closest('tr');
        row.remove();
    }
</script>
