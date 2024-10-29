<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Annexed Target Group: Livelihood Development Projects</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead style="background-color: #202ba3; color: white;">
                    <tr>
                        <th style="text-align: center;">S.No.</th>
                        <th>Beneficiary Name</th>
                        <th>Family Situation</th>
                        <th>Nature of Livelihood</th>
                        <th>Amount Requested</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="ldp-annexed-target-group-rows">
                    @foreach ($targetGroups as $index => $targetGroup)
                        <tr>
                            <td style="text-align: center;">{{ $index + 1 }}</td>
                            <td><input type="text" name="beneficiary_name_{{ $index + 1 }}" class="form-control" value="{{ $targetGroup->beneficiary_name }}" placeholder="Enter name"></td>
                            <td><textarea name="family_situation_{{ $index + 1 }}" class="form-control" rows="2" placeholder="Enter family situation">{{ $targetGroup->family_situation }}</textarea></td>
                            <td><textarea name="nature_of_livelihood_{{ $index + 1 }}" class="form-control" rows="2" placeholder="Enter nature of livelihood">{{ $targetGroup->nature_of_livelihood }}</textarea></td>
                            <td><input type="number" name="amount_requested_{{ $index + 1 }}" class="form-control" value="{{ $targetGroup->amount_requested }}" placeholder="Enter amount"></td>
                            <td><button type="button" class="btn btn-danger ldp-remove-row-btn">Remove</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" id="ldp-add-row-btn">Add Row</button>
    </div>
</div>

<script>
    (function(){
    document.addEventListener('DOMContentLoaded', function () {
        let rowCount = {{ count($targetGroups) }}; // Initial row count

        // Function to add a new row
        document.getElementById('ldp-add-row-btn').addEventListener('click', function () {
            rowCount++; // Increment row count

            const newRow = document.createElement('tr');

            newRow.innerHTML = `
                <td style="text-align: center;">${rowCount}</td>
                <td><input type="text" name="beneficiary_name_${rowCount}" class="form-control" placeholder="Enter name"></td>
                <td><textarea name="family_situation_${rowCount}" class="form-control" rows="2" placeholder="Enter family situation"></textarea></td>
                <td><textarea name="nature_of_livelihood_${rowCount}" class="form-control" rows="2" placeholder="Enter nature of livelihood"></textarea></td>
                <td><input type="number" name="amount_requested_${rowCount}" class="form-control" placeholder="Enter amount"></td>
                <td><button type="button" class="btn btn-danger ldp-remove-row-btn">Remove</button></td>
            `;

            document.getElementById('ldp-annexed-target-group-rows').appendChild(newRow);

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
            document.querySelectorAll('#ldp-annexed-target-group-rows tr').forEach((row, index) => {
                rowCount = index + 1;
                row.querySelector('td').textContent = rowCount;
                // Update input and textarea names to maintain uniqueness
                row.querySelectorAll('input, textarea').forEach(input => {
                    const name = input.getAttribute('name');
                    const newName = name.replace(/\d+$/, rowCount);
                    input.setAttribute('name', newName);
                });
            });
        }
    });
})();
</script>
