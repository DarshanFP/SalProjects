<div class="mb-3 card">
    <div class="card-header">
        <h4>Annexed Target Group CCI:</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead style="background-color: #202ba3; color: white;">
                    <tr>
                        <th style="text-align: center;">S.No.</th>
                        <th>Beneficiary Name</th>
                        <th>Date of Birth</th>
                        <th>Date of Joining</th>
                        <th>Class of Study</th>
                        <th>Family Background</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="annexed-target-group-rows">
                    <tr>
                        <td style="text-align: center;">1</td>
                        <td><input type="text" name="beneficiary_name_1" class="form-control" placeholder="Enter name"></td>
                        <td><input type="date" name="dob_1" class="form-control"></td>
                        <td><input type="date" name="date_of_joining_1" class="form-control"></td>
                        <td><input type="text" name="class_of_study_1" class="form-control" placeholder="Enter class"></td>
                        <td><textarea name="family_background_1" class="form-control" rows="2" placeholder="Enter family background"></textarea></td>
                        <td><button type="button" class="btn btn-danger remove-row-btn">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" id="add-row-btn">Add Row</button>
    </div>
</div>

<!-- Styles to maintain consistency with the existing design -->
<style>
    .table td input,
    .table td textarea {
        width: 100%;
        box-sizing: border-box;
    }

    .table th, .table td {
        vertical-align: middle;
        text-align: center;
        padding: 0.5rem;
    }
</style>

<!-- JavaScript to add/remove rows -->
<script>
    (function() {


    document.addEventListener('DOMContentLoaded', function() {
        let annexedRowIndex = 1;

        // Function to add new row
        function addAnnexedTargetGroupRow() {
            annexedRowIndex++;
            const tableBody = document.getElementById('annexed-target-group-rows');

            const newRow = `
                <tr>
                    <td style="text-align: center;">${annexedRowIndex}</td>
                    <td><input type="text" name="beneficiary_name_${annexedRowIndex}" class="form-control" placeholder="Enter name"></td>
                    <td><input type="date" name="dob_${annexedRowIndex}" class="form-control"></td>
                    <td><input type="date" name="date_of_joining_${annexedRowIndex}" class="form-control"></td>
                    <td><input type="text" name="class_of_study_${annexedRowIndex}" class="form-control" placeholder="Enter class"></td>
                    <td><textarea name="family_background_${annexedRowIndex}" class="form-control" rows="2" placeholder="Enter family background"></textarea></td>
                    <td><button type="button" class="btn btn-danger remove-row-btn">Remove</button></td>
                </tr>
            `;

            tableBody.insertAdjacentHTML('beforeend', newRow);  // Add new row
            addRemoveRowEventListeners();  // Add event listener to new remove button
        }

        // Function to remove a row
        function removeRow(button) {
            const row = button.closest('tr');
            row.remove();
            updateRowNumbers();
        }

        // Update row numbers after deletion
        function updateRowNumbers() {
            const rows = document.querySelectorAll('#annexed-target-group-rows tr');
            rows.forEach((row, index) => {
                row.children[0].textContent = index + 1;  // Update row number
            });
            annexedRowIndex = rows.length;  // Update the index after removal
        }

        // Add event listener to remove buttons (including newly added rows)
        function addRemoveRowEventListeners() {
            document.querySelectorAll('.remove-row-btn').forEach(button => {
                button.onclick = function() {
                    removeRow(this);
                };
            });
        }

        // Event listener for the "Add Row" button
        document.getElementById('add-row-btn').addEventListener('click', addAnnexedTargetGroupRow);

        // Initial call to ensure remove button works for the first row
        addRemoveRowEventListeners();
    });
})();
</script>
