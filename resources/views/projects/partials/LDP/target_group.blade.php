<div class="mb-3 card">
    <div class="card-header">
        <h4>Annexed Target Group: Livelihood Development Projects</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead style="background-color: #202ba3; color: white;">
                    <tr>
                        <th>S.No</th>
                        <th>Beneficiary Name</th>
                        <th>Family Situation</th>
                        <th>Nature of Livelihood</th>
                        <th>Amount Requested</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="L-annexed-target-group-rows">
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="L_beneficiary_name[]" class="form-control" style="background-color: #202ba3;" placeholder="Enter name"></td>
                        <td><textarea name="L_family_situation[]" class="form-control" rows="2" style="background-color: #202ba3;" placeholder="Enter family situation"></textarea></td>
                        <td><textarea name="L_nature_of_livelihood[]" class="form-control" rows="2" style="background-color: #202ba3;" placeholder="Enter nature of livelihood"></textarea></td>
                        <td><input type="number" name="L_amount_requested[]" class="form-control" style="background-color: #202ba3;" placeholder="Enter amount"></td>
                        <td><button type="button" class="btn btn-danger" onclick="L_removeAnnexedTargetGroupRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="L_addAnnexedTargetGroupRow()">Add More</button>
    </div>
</div>

<!-- JavaScript to add/remove rows dynamically -->
<script>
    let L_annexedTargetGroupRowIndex = 1;

    window.L_addAnnexedTargetGroupRow = function() {
        L_annexedTargetGroupRowIndex++;
        const newRow = `
            <tr>
                <td>${L_annexedTargetGroupRowIndex}</td>
                <td><input type="text" name="L_beneficiary_name[]" class="form-control" style="background-color: #202ba3;" placeholder="Enter name"></td>
                <td><textarea name="L_family_situation[]" class="form-control" rows="2" style="background-color: #202ba3;" placeholder="Enter family situation"></textarea></td>
                <td><textarea name="L_nature_of_livelihood[]" class="form-control" rows="2" style="background-color: #202ba3;" placeholder="Enter nature of livelihood"></textarea></td>
                <td><input type="number" name="L_amount_requested[]" class="form-control" style="background-color: #202ba3;" placeholder="Enter amount"></td>
                <td><button type="button" class="btn btn-danger" onclick="L_removeAnnexedTargetGroupRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('L-annexed-target-group-rows').insertAdjacentHTML('beforeend', newRow);
    }

    window.L_removeAnnexedTargetGroupRow = function(button) {
        const row = button.closest('tr');
        row.remove();
        L_updateAnnexedTargetGroupRowNumbers();
    }

    function L_updateAnnexedTargetGroupRowNumbers() {
        const rows = document.querySelectorAll('#L-annexed-target-group-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
        L_annexedTargetGroupRowIndex = rows.length;
    }
</script>

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
