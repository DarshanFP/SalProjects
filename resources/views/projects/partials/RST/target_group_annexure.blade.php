{{-- resources/views/projects/partials/RST/target_group_annexure.blade.php --}}
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
                <tbody id="annexure-rows">
                    <tr>
                        <td><input type="text" name="name[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="religion[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="caste[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="education_background[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><textarea name="family_situation[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                        <td><textarea name="paragraph[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                        <td><button type="button" class="btn btn-danger" onclick="removeAnnexureRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addAnnexureRow()">Add More</button>
    </div>
</div>

<script>
    let annexureRowIndex = 1;

    function addAnnexureRow() {
        annexureRowIndex++;
        const newRow = `
            <tr>
                <td><input type="text" name="name[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="religion[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="caste[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="education_background[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><textarea name="family_situation[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><textarea name="paragraph[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><button type="button" class="btn btn-danger" onclick="removeAnnexureRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('annexure-rows').insertAdjacentHTML('beforeend', newRow);
    }

    function removeAnnexureRow(button) {
        const row = button.closest('tr');
        row.remove();
        updateAnnexureRowNumbers();
    }

    function updateAnnexureRowNumbers() {
        const rows = document.querySelectorAll('#annexure-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
    }
</script>
