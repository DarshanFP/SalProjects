{{-- resources/views/projects/partials/NPD/beneficiaries_area.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Beneficiaries Area - NEXT PHASE</h4>
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
                <tbody id="NPD-project-area-rows">
                    @if(!empty($predecessorBeneficiaries))
                        @foreach($predecessorBeneficiaries as $beneficiary)
                            <tr>
                                <td>
                                    <input type="text" name="project_area[]" class="form-control" value="{{ $beneficiary['project_area'] }}">
                                </td>
                                <td>
                                    <input type="text" name="category_beneficiary[]" class="form-control" value="{{ $beneficiary['category_beneficiary'] }}">
                                </td>
                                <td>
                                    <input type="number" name="direct_beneficiaries[]" class="form-control" value="{{ $beneficiary['direct_beneficiaries'] }}">
                                </td>
                                <td>
                                    <input type="number" name="indirect_beneficiaries[]" class="form-control" value="{{ $beneficiary['indirect_beneficiaries'] }}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger" onclick="removeNPDProjectAreaRow(this)">Remove</button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td><input type="text" name="project_area[]" class="form-control"></td>
                            <td><input type="text" name="category_beneficiary[]" class="form-control"></td>
                            <td><input type="number" name="direct_beneficiaries[]" class="form-control"></td>
                            <td><input type="number" name="indirect_beneficiaries[]" class="form-control"></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeNPDProjectAreaRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addNPDProjectAreaRow()">Add More</button>
    </div>
</div>

<script>
    let NPDprojectAreaRowIndex = {{ !empty($predecessorBeneficiaries) ? count($predecessorBeneficiaries) + 1 : 1 }};

    function addNPDProjectAreaRow() {
        const newRow = `
            <tr>
                <td><input type="text" name="project_area[]" class="form-control"></td>
                <td><input type="text" name="category_beneficiary[]" class="form-control"></td>
                <td><input type="number" name="direct_beneficiaries[]" class="form-control"></td>
                <td><input type="number" name="indirect_beneficiaries[]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger" onclick="removeNPDProjectAreaRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('NPD-project-area-rows').insertAdjacentHTML('beforeend', newRow);
    }

    function removeNPDProjectAreaRow(button) {
        const row = button.closest('tr');
        row.remove();
    }
</script>
