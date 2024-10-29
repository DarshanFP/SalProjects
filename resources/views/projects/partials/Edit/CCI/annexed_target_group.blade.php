<!-- resources/views/projects/partials/CCI/edit_annexed_target_group.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Annexed Target Group CCI</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead style="background-color: #202ba3; color: white;">
                    <tr>
                        <th style="text-align: center;">S.No.</th>
                        <th>Beneficiary Name</th>
                        <th>Date of Birth</th>
                        <th>Date of Joining</th>
                        <th>Class of Study</th>
                        <th>Family Background</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="annexed-target-group-rows">
                    @foreach ($targetGroup as $index => $group)
                        <tr>
                            <td style="text-align: center;">{{ $index + 1 }}</td>
                            <td>
                                <input type="text" name="annexed_target_group[{{ $index }}][beneficiary_name]" value="{{ $group->beneficiary_name }}" class="form-control" placeholder="Enter name">
                            </td>
                            <td>
                                <input type="date" name="annexed_target_group[{{ $index }}][dob]" value="{{ $group->dob }}" class="form-control">
                            </td>
                            <td>
                                <input type="date" name="annexed_target_group[{{ $index }}][date_of_joining]" value="{{ $group->date_of_joining }}" class="form-control">
                            </td>
                            <td>
                                <input type="text" name="annexed_target_group[{{ $index }}][class_of_study]" value="{{ $group->class_of_study }}" class="form-control" placeholder="Enter class">
                            </td>
                            <td>
                                <textarea name="annexed_target_group[{{ $index }}][family_background_description]" class="form-control" rows="2" placeholder="Enter family background">{{ $group->family_background_description }}</textarea>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger remove-row-btn">Remove</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" id="add-row-btn">Add Row</button>
    </div>
</div>

<!-- JavaScript to dynamically update index -->
<script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            let annexedRowIndex = {{ count($targetGroup) }};  // Start from existing row count

            // Function to add new row
            function addAnnexedTargetGroupRow() {
                const tableBody = document.getElementById('annexed-target-group-rows');

                const newRow = `
                    <tr>
                        <td style="text-align: center;">${annexedRowIndex + 1}</td>
                        <td><input type="text" name="annexed_target_group[${annexedRowIndex}][beneficiary_name]" class="form-control" placeholder="Enter name"></td>
                        <td><input type="date" name="annexed_target_group[${annexedRowIndex}][dob]" class="form-control"></td>
                        <td><input type="date" name="annexed_target_group[${annexedRowIndex}][date_of_joining]" class="form-control"></td>
                        <td><input type="text" name="annexed_target_group[${annexedRowIndex}][class_of_study]" class="form-control" placeholder="Enter class"></td>
                        <td><textarea name="annexed_target_group[${annexedRowIndex}][family_background_description]" class="form-control" rows="2" placeholder="Enter family background"></textarea></td>
                        <td><button type="button" class="btn btn-danger remove-row-btn">Remove</button></td>
                    </tr>
                `;

                annexedRowIndex++;
                tableBody.insertAdjacentHTML('beforeend', newRow);  // Add new row
                addRemoveRowEventListeners();  // Add event listener to new remove button
            }

            // Function to remove a row
            function removeRow(button) {
                const row = button.closest('tr');
                row.remove();
                updateRowNumbers();
            }

            // Update row numbers after deletion
            function updateRowNumbers() {
                const rows = document.querySelectorAll('#annexed-target-group-rows tr');
                rows.forEach((row, index) => {
                    row.children[0].textContent = index + 1;  // Update row number
                    updateInputNames(row, index);  // Update the input field names based on new index
                });
                annexedRowIndex = rows.length;  // Update the index after removal
            }

            // Update input field names with the correct index
            function updateInputNames(row, index) {
                row.querySelectorAll('input, textarea').forEach(input => {
                    const name = input.getAttribute('name');
                    if (name) {
                        input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
                    }
                });
            }

            // Add event listener to remove buttons (including newly added rows)
            function addRemoveRowEventListeners() {
                document.querySelectorAll('.remove-row-btn').forEach(button => {
                    button.onclick = function() {
                        removeRow(this);
                    };
                });
            }

            // Event listener for the "Add Row" button
            document.getElementById('add-row-btn').addEventListener('click', addAnnexedTargetGroupRow);

            // Initial call to ensure remove button works for the first row
            addRemoveRowEventListeners();
        });
    })();
</script>
