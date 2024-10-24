{{-- resources/views/projects/partials/Edu-RUT/target_group.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Target Group</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th>Beneficiary Name</th>
                    <th>Caste</th>
                    <th>Name of Institution</th>
                    <th>Class / Standard</th>
                    <th>Total Tuition Fee</th>
                    <th>Eligibility of Scholarship</th>
                    <th>Expected Amount</th>
                    <th>Contribution from Family</th>
                </tr>
            </thead>
            <tbody id="targetGroupTable">
                <tr>
                    <td>1</td>
                    <td><input type="text" name="target_group[0][beneficiary_name]" class="form-control"></td>
                    <td><input type="text" name="target_group[0][caste]" class="form-control"></td>
                    <td><input type="text" name="target_group[0][institution_name]" class="form-control"></td>
                    <td><input type="text" name="target_group[0][class_standard]" class="form-control"></td>
                    <td><input type="number" name="target_group[0][tuition_fee]" class="form-control"></td>
                    <td>
                        <select name="target_group[0][scholarship_eligibility]" class="form-control">
                            <option value="" disabled selected>Yes/No</option>
                            <option value="1">Yes</option> <!-- True -->
                            <option value="0">No</option>  <!-- False -->
                        </select>
                    </td>                    <td><input type="number" name="target_group[0][expected_amount]" class="form-control"></td>
                    <td><input type="number" name="target_group[0][family_contribution]" class="form-control"></td>
                </tr>
                <!-- Additional rows will be inserted here dynamically -->
            </tbody>
        </table>

        <!-- Add and Remove Row Buttons -->
        <button type="button" class="btn btn-primary" id="addTargetGroupRow">Add Row</button>
        <button type="button" class="btn btn-danger" id="removeTargetGroupRow">Remove Row</button>
    </div>
</div>

<script>
    (function() {
    // Add a row to the Target Group table
    document.getElementById('addTargetGroupRow').addEventListener('click', function () {
        const table = document.getElementById('targetGroupTable');
        const rowCount = table.rows.length;

        const row = `
            <tr>
                <td>${rowCount + 1}</td>
                <td><input type="text" name="target_group[${rowCount}][beneficiary_name]" class="form-control"></td>
                <td><input type="text" name="target_group[${rowCount}][caste]" class="form-control"></td>
                <td><input type="text" name="target_group[${rowCount}][institution_name]" class="form-control"></td>
                <td><input type="text" name="target_group[${rowCount}][class]" class="form-control"></td>
                <td><input type="number" name="target_group[${rowCount}][tuition_fee]" class="form-control"></td>
                <td>
                    <select name="target_group[${rowCount}][scholarship_eligibility]" class="form-control">
                        <option value="" disabled selected>Yes/No</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </td>
                <td><input type="number" name="target_group[${rowCount}][expected_amount]" class="form-control"></td>
                <td><input type="number" name="target_group[${rowCount}][family_contribution]" class="form-control"></td>
            </tr>`;

        table.insertAdjacentHTML('beforeend', row);
    });

    // Remove the last row from the Target Group table
    document.getElementById('removeTargetGroupRow').addEventListener('click', function () {
        const table = document.getElementById('targetGroupTable');
        if (table.rows.length > 1) {
            table.deleteRow(-1);
        }
    });
})();
</script>

