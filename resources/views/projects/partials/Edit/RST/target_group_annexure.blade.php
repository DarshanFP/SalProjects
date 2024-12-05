<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Target Group Annexure</h4>
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
                    RSTtargetGroupAnnexure
                </thead>
                <tbody id="RST-annexure-rows">
                    @if(isset($RSTtargetGroupAnnexure) && count($RSTtargetGroupAnnexure) > 0)
                        @foreach($RSTtargetGroupAnnexure as $index => $annexure)
                            <tr>
                                <td><input type="text" name="rst_name[]" value="{{ old('rst_name.' . $index, $annexure->rst_name) }}" class="form-control" style="background-color: #202ba3;"></td>
                                <td><input type="text" name="rst_religion[]" value="{{ old('rst_religion.' . $index, $annexure->rst_religion) }}" class="form-control" style="background-color: #202ba3;"></td>
                                <td><input type="text" name="rst_caste[]" value="{{ old('rst_caste.' . $index, $annexure->rst_caste) }}" class="form-control" style="background-color: #202ba3;"></td>
                                <td><input type="text" name="rst_education_background[]" value="{{ old('rst_education_background.' . $index, $annexure->rst_education_background) }}" class="form-control" style="background-color: #202ba3;"></td>
                                <td><textarea name="rst_family_situation[]" class="form-control" rows="2" style="background-color: #202ba3;">{{ old('rst_family_situation.' . $index, $annexure->rst_family_situation) }}</textarea></td>
                                <td><textarea name="rst_paragraph[]" class="form-control" rows="2" style="background-color: #202ba3;">{{ old('rst_paragraph.' . $index, $annexure->rst_paragraph) }}</textarea></td>
                                <td><button type="button" class="btn btn-danger" onclick="removeRSTAnnexureRow(this)">Remove</button></td>
                            </tr>
                        @endforeach
                    @else
                        <!-- Show an empty row if no annexure data exists -->
                        <tr>
                            <td><input type="text" name="rst_name[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="rst_religion[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="rst_caste[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="rst_education_background[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><textarea name="rst_family_situation[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                            <td><textarea name="rst_paragraph[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeRSTAnnexureRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addRSTAnnexureRow()">Add More</button>
    </div>
</div>

<script>
    let RSTAnnexureRowIndex = {{ isset($RSTtargetGroupAnnexure) ? count($RSTtargetGroupAnnexure) : 1 }};

    function addRSTAnnexureRow() {
        RSTAnnexureRowIndex++;
        const newRow = `
            <tr>
                <td><input type="text" name="rst_name[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="rst_religion[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="rst_caste[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="text" name="rst_education_background[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><textarea name="rst_family_situation[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><textarea name="rst_paragraph[]" class="form-control" rows="2" style="background-color: #202ba3;"></textarea></td>
                <td><button type="button" class="btn btn-danger" onclick="removeRSTAnnexureRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('RST-annexure-rows').insertAdjacentHTML('beforeend', newRow);
    }

    function removeRSTAnnexureRow(button) {
        const row = button.closest('tr');
        row.remove();
    }
</script>
