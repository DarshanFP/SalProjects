<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Annexed Target Group</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th>Beneficiary Name</th>
                    <th>Family Background</th>
                    <th>Need of Support</th>
                </tr>
            </thead>
            <tbody id="annexedTargetGroupTable">
                @if($project->annexed_target_groups && $project->annexed_target_groups->count() > 0)
                    @foreach($project->annexed_target_groups as $index => $group)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><input type="text" name="annexed_target_group[{{ $index }}][beneficiary_name]" value="{{ $group->beneficiary_name }}" class="form-control"></td>
                            <td><textarea name="annexed_target_group[{{ $index }}][family_background]" class="form-control" rows="2">{{ $group->family_background }}</textarea></td>
                            <td><textarea name="annexed_target_group[{{ $index }}][need_of_support]" class="form-control" rows="2">{{ $group->need_of_support }}</textarea></td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4">No Annexed Target Group data available.</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <button type="button" class="btn btn-primary" id="addAnnexedTargetGroupRow">Add Row</button>
        <button type="button" class="btn btn-danger" id="removeAnnexedTargetGroupRow">Remove Row</button>
    </div>
</div>

<script>
// Add a row to the Annexed Target Group table
document.getElementById('addAnnexedTargetGroupRow').addEventListener('click', function () {
        const table = document.getElementById('annexedTargetGroupTable');
        const rowCount = table.rows.length;

        const row = `
            <tr>
                <td>${rowCount + 1}</td>
                <td><input type="text" name="annexed_target_group[${rowCount}][beneficiary_name]" class="form-control"></td>
                <td><textarea name="annexed_target_group[${rowCount}][family_background]" class="form-control" rows="2"></textarea></td>
                <td><textarea name="annexed_target_group[${rowCount}][need_of_support]" class="form-control" rows="2"></textarea></td>
            </tr>`;

        table.insertAdjacentHTML('beforeend', row);
    });

    // Remove the last row from the Annexed Target Group table
    document.getElementById('removeAnnexedTargetGroupRow').addEventListener('click', function () {
        const table = document.getElementById('annexedTargetGroupTable');
        if (table.rows.length > 1) {
            table.deleteRow(-1);
        }
    });
    </script>
