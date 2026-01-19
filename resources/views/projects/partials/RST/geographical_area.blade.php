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
                <tbody id="RST-geographical-area-rows">
                    <tr>
                        <td><input type="text" name="mandal[]" class="form-control"></td>
                        <td><input type="text" name="village[]" class="form-control"></td>
                        <td><input type="text" name="town[]" class="form-control"></td>
                        <td><input type="number" name="no_of_beneficiaries[]" class="form-control"></td>
                        <td><button type="button" class="btn btn-danger" onclick="removeRSTGeographicalAreaRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addRSTGeographicalAreaRow()">Add More</button>
    </div>
</div>

<script>
    let RSTGeoAreaRowIndex = 1;

    function addRSTGeographicalAreaRow() {
        RSTGeoAreaRowIndex++;
        const newRow = `
            <tr>
                <td><input type="text" name="mandal[]" class="form-control"></td>
                <td><input type="text" name="village[]" class="form-control"></td>
                <td><input type="text" name="town[]" class="form-control"></td>
                <td><input type="number" name="no_of_beneficiaries[]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger" onclick="removeRSTGeographicalAreaRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('RST-geographical-area-rows').insertAdjacentHTML('beforeend', newRow);
    }

    function removeRSTGeographicalAreaRow(button) {
        const row = button.closest('tr');
        row.remove();
    }
</script>
