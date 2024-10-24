{{-- resources/views/projects/partials/Edu-RUT/annexed_target_group.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Annexed Target Group</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th>Beneficiary Name</th>
                    <th>Family Background</th>
                    <th>Need of Support</th>
                </tr>
            </thead>
            <tbody id="annexedTargetGroupTable">
                <tr>
                    <td>1</td>
                    <td><input type="text" name="annexed_target_group[0][beneficiary_name]" class="form-control"></td>
                    <td><textarea name="annexed_target_group[0][family_background]" class="form-control" rows="2"></textarea></td>
                    <td><textarea name="annexed_target_group[0][need_of_support]" class="form-control" rows="2"></textarea></td>
                </tr>
                <!-- Additional rows will be inserted here dynamically -->
            </tbody>
        </table>

        <!-- Add and Remove Row Buttons -->
        <button type="button" class="btn btn-primary" id="addAnnexedTargetGroupRow">Add Row</button>
        <button type="button" class="btn btn-danger" id="removeAnnexedTargetGroupRow">Remove Row</button>
    </div>
</div>

<script>
    (function() {


    // Add a row to the Annexed Target Group table
    document.getElementById('addAnnexedTargetGroupRow').addEventListener('click', function () {
        const table = document.getElementById('annexedTargetGroupTable');
        const rowCount = table.rows.length;

        const row = `
            <tr>
                <td>${rowCount + 1}</td>
                <td><input type="text" name="annexed_target_group[${rowCount}][beneficiary_name]" class="form-control"></td>
                <td><textarea name="annexed_target_group[${rowCount}][family_background]" class="form-control" rows="2"></textarea></td>
                <td><textarea name="annexed_target_group[${rowCount}][need_of_support]" class="form-control" rows="2"></textarea></td>
            </tr>`;

        table.insertAdjacentHTML('beforeend', row);
    });

    // Remove the last row from the Annexed Target Group table
    document.getElementById('removeAnnexedTargetGroupRow').addEventListener('click', function () {
        const table = document.getElementById('annexedTargetGroupTable');
        if (table.rows.length > 1) {
            table.deleteRow(-1);
        }
    });
})();
</script>

<!-- Below script has section for excel upload to populate table however is is not functional so far -->

{{-- <div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Annexed Target Group</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th>Beneficiary Name</th>
                    <th>Family Background</th>
                    <th>Need of Support</th>
                </tr>
            </thead>
            <tbody id="annexedTargetGroupTable">
                <!-- Rows dynamically added here -->
            </tbody>
        </table>

        <!-- Add and Remove Row Buttons -->
        <button type="button" class="btn btn-primary" id="addAnnexedTargetGroupRow">Add Row</button>
        <button type="button" class="btn btn-danger" id="removeAnnexedTargetGroupRow">Remove Row</button>

        {{-- <!-- Upload Excel File -->
        <div class="mt-3 form-group">
            <label for="annexed_target_group_excel" class="form-label">Upload Excel File</label>
            <input type="file" id="annexed_target_group_excel" name="annexed_target_group_excel" class="form-control select-input">
        </div>
        <!-- Download Template Button -->
        <div class="mb-3">
            <a href="{{ asset('downloads/AnnexedTargetGroupTemplate.xlsx') }}" class="btn btn-info">Download Annexed Target Group Excel Template</a>
        </div>
    </div>
</div>
<script>
    // Add a row to the Annexed Target Group table
    document.getElementById('addAnnexedTargetGroupRow').addEventListener('click', function () {
        const table = document.getElementById('annexedTargetGroupTable');

        const row = `
            <tr>
                <td></td>  <!-- S.No. will be updated later -->
                <td><input type="text" name="annexed_target_group[][beneficiary_name]" class="form-control select-input" style="background-color: #202ba3;"></td>
                <td><textarea name="annexed_target_group[][family_background]" class="form-control select-input" style="background-color: #202ba3;" rows="2"></textarea></td>
                <td><textarea name="annexed_target_group[][need_of_support]" class="form-control select-input" style="background-color: #202ba3;" rows="2"></textarea></td>
            </tr>`;

        table.insertAdjacentHTML('beforeend', row);
        updateAnnexedTargetGroupSNo();  // Update S.No. after adding the row
    });

    // Remove the last row from the Annexed Target Group table
    document.getElementById('removeAnnexedTargetGroupRow').addEventListener('click', function () {
        const table = document.getElementById('annexedTargetGroupTable');
        if (table.rows.length > 0) {
            table.deleteRow(-1);
            updateAnnexedTargetGroupSNo();  // Update S.No. after removing the row
        }
    });

    // Update S.No. for Annexed Target Group
    function updateAnnexedTargetGroupSNo() {
        const table = document.getElementById('annexedTargetGroupTable');
        Array.from(table.rows).forEach((row, index) => {
            row.cells[0].innerText = index + 1;  // Update the S.No.
        });
    }

    // Handle Excel file upload for the Annexed Target Group
    document.getElementById('annexed_target_group_excel').addEventListener('change', function (event) {
        const formData = new FormData();
        formData.append('excel_file', event.target.files[0]);

        fetch('/upload-annexed-target-group-excel', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                const table = document.getElementById('annexedTargetGroupTable');
                table.innerHTML = ''; // Clear existing rows

                data.forEach((row, index) => {
                    const newRow = `
                        <tr>
                            <td></td>  <!-- S.No. will be updated later -->
                            <td><input type="text" name="annexed_target_group[${index + 1}][beneficiary_name]" value="${row.beneficiary_name}" class="form-control select-input" style="background-color: #202ba3;"></td>
                            <td><textarea name="annexed_target_group[${index + 1}][family_background]" class="form-control select-input" rows="2" style="background-color: #202ba3;">${row.family_background}</textarea></td>
                            <td><textarea name="annexed_target_group[${index + 1}][need_of_support]" class="form-control select-input" rows="2" style="background-color: #202ba3;">${row.need_of_support}</textarea></td>
                        </tr>`;

                    table.insertAdjacentHTML('beforeend', newRow);
                });

                updateAnnexedTargetGroupSNo();  // Update S.No. after populating from Excel
            }
        })
        .catch(error => console.error('Error uploading Excel file:', error));
    });
</script> --}}
