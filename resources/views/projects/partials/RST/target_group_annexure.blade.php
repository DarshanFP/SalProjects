<div class="mb-3 card">
    <div class="card-header">
        <h4>Target Group Annexure</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Religion</th>
                        <th>Caste</th>
                        <th>Education Background</th>
                        <th>Family Situation</th>
                        <th>Paragraph</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="RST-annexure-rows">
                    <tr>
                        <td><input type="text" name="rst_name[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="rst_religion[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="rst_caste[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="rst_education_background[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><textarea name="rst_family_situation[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                        <td><textarea name="rst_paragraph[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                        <td><button type="button" class="btn btn-danger" onclick="removeRSTAnnexureRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addRSTAnnexureRow()">Add More</button>
    </div>
</div>

<script>
    let RSTAnnexureRowIndex = 1;

    function addRSTAnnexureRow() {
        RSTAnnexureRowIndex++;
        const newRow = `
            <tr>
                <td><input type="text" name="rst_name[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="rst_religion[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="rst_caste[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="rst_education_background[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><textarea name="rst_family_situation[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><textarea name="rst_paragraph[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><button type="button" class="btn btn-danger" onclick="removeRSTAnnexureRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('RST-annexure-rows').insertAdjacentHTML('beforeend', newRow);
    }

    function removeRSTAnnexureRow(button) {
        const row = button.closest('tr');
        row.remove();
        updateRSTAnnexureRowNumbers();
    }

    function updateRSTAnnexureRowNumbers() {
        const rows = document.querySelectorAll('#RST-annexure-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
    }
</script>
