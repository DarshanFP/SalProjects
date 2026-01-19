<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Project Area</h4>
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
                    @if(isset($beneficiariesArea) && count($beneficiariesArea) > 0)
                        @foreach($beneficiariesArea as $index => $area)
                            <tr>
                                <td><input type="text" name="project_area[]" value="{{ $area->project_area }}" class="form-control"></td>
                                <td><input type="text" name="category_beneficiary[]" value="{{ $area->category_beneficiary }}" class="form-control"></td>
                                <td><input type="number" name="direct_beneficiaries[]" value="{{ $area->direct_beneficiaries }}" class="form-control"></td>
                                <td><input type="number" name="indirect_beneficiaries[]" value="{{ $area->indirect_beneficiaries }}" class="form-control"></td>
                                <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td><input type="text" name="project_area[]" class="form-control"></td>
                            <td><input type="text" name="category_beneficiary[]" class="form-control"></td>
                            <td><input type="number" name="direct_beneficiaries[]" class="form-control"></td>
                            <td><input type="number" name="indirect_beneficiaries[]" class="form-control"></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addRSTProjectAreaRow()">Add More</button>
    </div>
</div>

<script>
    let RSTprojectAreaRowIndex = {{ isset($beneficiariesArea) ? count($beneficiariesArea) : 1 }};

    function addRSTProjectAreaRow() {
        RSTprojectAreaRowIndex++;
        const newRow = `
            <tr>
                <td><input type="text" name="project_area[]" class="form-control"></td>
                <td><input type="text" name="category_beneficiary[]" class="form-control"></td>
                <td><input type="number" name="direct_beneficiaries[]" class="form-control"></td>
                <td><input type="number" name="indirect_beneficiaries[]" class="form-control"></td>
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
