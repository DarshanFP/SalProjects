{{-- resources/views/projects/partials/RST/geographical_area.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Geographical Area of Beneficiaries</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Mandal</th>
                        <th>Villages</th>
                        <th>Town</th>
                        <th>No of Beneficiaries</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="geographical-area-rows">
                    <tr>
                        <td><input type="text" name="mandal[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="village[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="text" name="town[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="no_of_beneficiaries[]" class="form-control" style="background-color: #202ba3;"></td>
                        <td><button type="button" class="btn btn-danger" onclick="removeGeographicalAreaRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addGeographicalAreaRow()">Add More</button>
    </div>
</div>

<script>
    (function(){
    let geoAreaRowIndex = 1;

    function addGeographicalAreaRow() {
        geoAreaRowIndex++;
        const newRow = `
            <tr>
                <td><input type="text" name="mandal[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="village[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="town[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="number" name="no_of_beneficiaries[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><button type="button" class="btn btn-danger" onclick="removeGeographicalAreaRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('geographical-area-rows').insertAdjacentHTML('beforeend', newRow);
    }

    function removeGeographicalAreaRow(button) {
        const row = button.closest('tr');
        row.remove();
        updateGeographicalAreaRowNumbers();
    }

    function updateGeographicalAreaRowNumbers() {
        const rows = document.querySelectorAll('#geographical-area-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
    }
})();
</script>
