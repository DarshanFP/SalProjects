<div class="mb-3 card">
    <div class="card-header">
        <h4>Target Group Annexure</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 5%;">S.No.</th>
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
                        <td style="text-align: center; vertical-align: middle;">1</td>
                        <td><input type="text" name="rst_name[]" class="form-control"></td>
                        <td><input type="text" name="rst_religion[]" class="form-control"></td>
                        <td><input type="text" name="rst_caste[]" class="form-control"></td>
                        <td><input type="text" name="rst_education_background[]" class="form-control"></td>
                        <td><textarea name="rst_family_situation[]" class="form-control sustainability-textarea" rows="2"></textarea></td>
                        <td><textarea name="rst_paragraph[]" class="form-control sustainability-textarea" rows="2"></textarea></td>
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
        const table = document.getElementById('RST-annexure-rows');
        const rowCount = table.children.length;
        RSTAnnexureRowIndex = rowCount + 1;
        const newRow = `
            <tr>
                <td style="text-align: center; vertical-align: middle;">${rowCount + 1}</td>
                <td><input type="text" name="rst_name[]" class="form-control"></td>
                <td><input type="text" name="rst_religion[]" class="form-control"></td>
                <td><input type="text" name="rst_caste[]" class="form-control"></td>
                <td><input type="text" name="rst_education_background[]" class="form-control"></td>
                <td><textarea name="rst_family_situation[]" class="form-control sustainability-textarea" rows="2"></textarea></td>
                <td><textarea name="rst_paragraph[]" class="form-control sustainability-textarea" rows="2"></textarea></td>
                <td><button type="button" class="btn btn-danger" onclick="removeRSTAnnexureRow(this)">Remove</button></td>
            </tr>
        `;
        table.insertAdjacentHTML('beforeend', newRow);
        reindexRSTAnnexureRows();

        // Initialize auto-resize for newly added textareas using global function
        const newRowElement = table.lastElementChild;
        const newTextareas = newRowElement.querySelectorAll('.sustainability-textarea');
        if (newTextareas.length > 0 && typeof window.initTextareaAutoResize === 'function') {
            newTextareas.forEach(textarea => {
                window.initTextareaAutoResize(textarea);
            });
        }
    }

    function removeRSTAnnexureRow(button) {
        const row = button.closest('tr');
        row.remove();
        reindexRSTAnnexureRows();
    }

    function reindexRSTAnnexureRows() {
        const rows = document.querySelectorAll('#RST-annexure-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
        RSTAnnexureRowIndex = rows.length;
    }
</script>
