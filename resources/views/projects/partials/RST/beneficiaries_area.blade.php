<div class="mb-3 card">
    <div class="card-header">
        <h4>Project Area</h4>
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
                        @if(!isset($readonly) || !$readonly)
                            <th>Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="RST-project-area-rows">
                    @if(isset($beneficiaries) && $readonly)
                        @foreach($beneficiaries as $beneficiary)
                            <tr>
                                <td><input type="text" class="form-control" value="{{ $beneficiary['project_area'] ?? '' }}" readonly></td>
                                <td><input type="text" class="form-control" value="{{ $beneficiary['category'] ?? '' }}" readonly></td>
                                <td><input type="number" class="form-control" value="{{ $beneficiary['direct'] ?? 0 }}" readonly></td>
                                <td><input type="number" class="form-control" value="{{ $beneficiary['indirect'] ?? 0 }}" readonly></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td><input type="text" name="project_area[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="category_beneficiary[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="direct_beneficiaries[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="indirect_beneficiaries[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if(!isset($readonly) || !$readonly)
            <button type="button" class="mt-3 btn btn-primary" onclick="addRSTProjectAreaRow()">Add More</button>
        @endif
    </div>
</div>

<script>
    // Declare RSTprojectAreaRowIndex globally only once
    if (typeof RSTprojectAreaRowIndex === 'undefined') {
        let RSTprojectAreaRowIndex = 1;
    }

    @if(!isset($readonly) || !$readonly)
        function addRSTProjectAreaRow() {
            RSTprojectAreaRowIndex++;
            const newRow = `
                <tr>
                    <td><input type="text" name="project_area[]" class="form-control" style="background-color: #202ba3;"></td>
                    <td><input type="text" name="category_beneficiary[]" class="form-control" style="background-color: #202ba3;"></td>
                    <td><input type="number" name="direct_beneficiaries[]" class="form-control" style="background-color: #202ba3;"></td>
                    <td><input type="number" name="indirect_beneficiaries[]" class="form-control" style="background-color: #202ba3;"></td>
                    <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
                </tr>
            `;
            document.getElementById('RST-project-area-rows').insertAdjacentHTML('beforeend', newRow);
        }

        function removeRSTProjectAreaRow(button) {
            const row = button.closest('tr');
            row.remove();
        }
    @endif
</script>
