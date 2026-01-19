{{-- resources/views/projects/partials/Edit/LDP/target_group.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit Annexed Target Group: Livelihood Development Projects</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="text-align: center;">S.No.</th>
                        <th>Beneficiary Name</th>
                        <th>Family Situation</th>
                        <th>Nature of Livelihood</th>
                        <th>Amount Requested</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="ldp-edit-target-group-rows">
                    @if(!empty($LDPtargetGroups) && is_array($LDPtargetGroups))
                        @foreach($LDPtargetGroups as $index => $targetGroup)
                            <tr>
                                <td style="text-align: center;">{{ $loop->iteration }}</td>
                                <td><input type="text" name="L_beneficiary_name[]" class="form-control" value="{{ $targetGroup['L_beneficiary_name'] }}" placeholder="Enter name"></td>
                                <td><textarea name="L_family_situation[]" class="form-control auto-resize-textarea" rows="2" placeholder="Enter family situation">{{ $targetGroup['L_family_situation'] }}</textarea></td>
                                <td><textarea name="L_nature_of_livelihood[]" class="form-control auto-resize-textarea" rows="2" placeholder="Enter nature of livelihood">{{ $targetGroup['L_nature_of_livelihood'] }}</textarea></td>
                                <td><input type="number" name="L_amount_requested[]" class="form-control" value="{{ $targetGroup['L_amount_requested'] }}" placeholder="Enter amount"></td>
                                <td><button type="button" class="btn btn-danger ldp-remove-row-btn">Remove</button></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6">No target groups available.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" id="ldp-add-row-btn">Add Row</button>
    </div>
</div>

<script>
    (function(){
    document.addEventListener('DOMContentLoaded', function () {
        let rowCount = {{ count($LDPtargetGroups) }}; // Initialize row count from existing records

        // Function to add a new row
        document.getElementById('ldp-add-row-btn').addEventListener('click', function () {
            rowCount++; // Increment row count

            const newRow = document.createElement('tr');

            newRow.innerHTML = `
                <td style="text-align: center;">${rowCount}</td>
                <td><input type="text" name="L_beneficiary_name[]" class="form-control" placeholder="Enter name"></td>
                <td><textarea name="L_family_situation[]" class="form-control auto-resize-textarea" rows="2" placeholder="Enter family situation"></textarea></td>
                <td><textarea name="L_nature_of_livelihood[]" class="form-control auto-resize-textarea" rows="2" placeholder="Enter nature of livelihood"></textarea></td>
                <td><input type="number" name="L_amount_requested[]" class="form-control" placeholder="Enter amount"></td>
                <td><button type="button" class="btn btn-danger ldp-remove-row-btn">Remove</button></td>
            `;

            document.getElementById('ldp-edit-target-group-rows').appendChild(newRow);

            // Initialize auto-resize for newly added textareas
            const newTextareas = newRow.querySelectorAll('.auto-resize-textarea');
            if (newTextareas.length > 0 && typeof window.initTextareaAutoResize === 'function') {
                newTextareas.forEach(textarea => {
                    window.initTextareaAutoResize(textarea);
                });
            }

            // Attach event listener to the new remove button
            newRow.querySelector('.ldp-remove-row-btn').addEventListener('click', function () {
                newRow.remove();
                updateRowNumbers(); // Update row numbers after removal
            });
        });

        // Function to remove a row
        document.querySelectorAll('.ldp-remove-row-btn').forEach(button => {
            button.addEventListener('click', function () {
                this.closest('tr').remove();
                updateRowNumbers(); // Update row numbers after removal
            });
        });

        // Function to update row numbers after adding/removing rows
        function updateRowNumbers() {
            rowCount = 0; // Reset row count
            document.querySelectorAll('#ldp-edit-target-group-rows tr').forEach((row, index) => {
                rowCount = index + 1;
                row.querySelector('td').textContent = rowCount;
                // No need to update input names since they are arrays
            });
        }
    });
})();
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
