<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Target Group</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th>Beneficiary Name</th>
                    <th>Caste</th>
                    <th>Name of Institution</th>
                    <th>Class / Standard</th>
                    <th>Total Tuition Fee</th>
                    <th>Eligibility of Scholarship</th>
                    <th>Expected Amount</th>
                    <th>Contribution from Family</th>
                </tr>
            </thead>
            <tbody id="targetGroupTable">
                @if($project->target_groups && $project->target_groups->count() > 0)
                    @foreach($project->target_groups as $index => $group)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><input type="text" name="target_group[{{ $index }}][beneficiary_name]" value="{{ $group->beneficiary_name }}" class="form-control"></td>
                            <td><input type="text" name="target_group[{{ $index }}][caste]" value="{{ $group->caste }}" class="form-control"></td>
                            <td><input type="text" name="target_group[{{ $index }}][institution_name]" value="{{ $group->institution_name }}" class="form-control"></td>
                            <td><input type="text" name="target_group[{{ $index }}][class_standard]" value="{{ $group->class_standard }}" class="form-control"></td>
                            <td><input type="number" name="target_group[{{ $index }}][total_tuition_fee]" value="{{ $group->total_tuition_fee }}" class="form-control"></td>
                            <td>
                                <select name="target_group[{{ $index }}][eligibility_scholarship]" class="form-control">
                                    <option value="" disabled>Select Yes/No</option>
                                    <option value="1" {{ $group->eligibility_scholarship == 1 ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ $group->eligibility_scholarship == 0 ? 'selected' : '' }}>No</option>
                                </select>
                            </td>
                            <td><input type="number" name="target_group[{{ $index }}][expected_amount]" value="{{ $group->expected_amount }}" class="form-control"></td>
                            <td><input type="number" name="target_group[{{ $index }}][contribution_from_family]" value="{{ $group->contribution_from_family }}" class="form-control"></td>
                        </tr>
                    @endforeach
                @else
                    <!-- Display an empty row if no data is available -->
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="target_group[0][beneficiary_name]" class="form-control"></td>
                        <td><input type="text" name="target_group[0][caste]" class="form-control"></td>
                        <td><input type="text" name="target_group[0][institution_name]" class="form-control"></td>
                        <td><input type="text" name="target_group[0][class_standard]" class="form-control"></td>
                        <td><input type="number" name="target_group[0][total_tuition_fee]" class="form-control"></td>
                        <td>
                            <select name="target_group[0][eligibility_scholarship]" class="form-control">
                                <option value="" disabled selected>Yes/No</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </td>
                        <td><input type="number" name="target_group[0][expected_amount]" class="form-control"></td>
                        <td><input type="number" name="target_group[0][contribution_from_family]" class="form-control"></td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Add and Remove Row Buttons -->
        <button type="button" class="btn btn-primary" id="addTargetGroupRow">Add Row</button>
        <button type="button" class="btn btn-danger" id="removeTargetGroupRow">Remove Row</button>
    </div>
</div>

<script>
    // Add a row to the Target Group table
    document.getElementById('addTargetGroupRow').addEventListener('click', function () {
        const table = document.getElementById('targetGroupTable');
        const rowCount = table.rows.length;

        const row = `
            <tr>
                <td>${rowCount + 1}</td>
                <td><input type="text" name="target_group[${rowCount}][beneficiary_name]" class="form-control"></td>
                <td><input type="text" name="target_group[${rowCount}][caste]" class="form-control"></td>
                <td><input type="text" name="target_group[${rowCount}][institution_name]" class="form-control"></td>
                <td><input type="text" name="target_group[${rowCount}][class_standard]" class="form-control"></td>
                <td><input type="number" name="target_group[${rowCount}][total_tuition_fee]" class="form-control"></td>
                <td>
                    <select name="target_group[${rowCount}][eligibility_scholarship]" class="form-control">
                        <option value="" disabled selected>Yes/No</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </td>
                <td><input type="number" name="target_group[${rowCount}][expected_amount]" class="form-control"></td>
                <td><input type="number" name="target_group[${rowCount}][contribution_from_family]" class="form-control"></td>
            </tr>`;

        table.insertAdjacentHTML('beforeend', row);
    });

    // Remove the last row from the Target Group table
    document.getElementById('removeTargetGroupRow').addEventListener('click', function () {
        const table = document.getElementById('targetGroupTable');
        if (table.rows.length > 1) {
            table.deleteRow(-1);
        }
    });
</script>
