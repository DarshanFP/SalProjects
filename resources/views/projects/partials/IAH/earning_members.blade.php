{{-- resources/views/projects/partials/IAH/earning_members.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Provide the list of earning members of the family</h4>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Family Member</th>
                    <th>Type/Nature of Work</th>
                    <th>Monthly Income</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="earning-members-list">
                <tr>
                    <td><input type="text" name="member_name[]" class="form-control" placeholder="Enter family member's name" required></td>
                    <td><input type="text" name="work_type[]" class="form-control" placeholder="Enter type/nature of work" required></td>
                    <td><input type="number" step="0.01" name="monthly_income[]" class="form-control" placeholder="Enter monthly income" required></td>
                    <td><button type="button" class="btn btn-danger remove-member">Remove</button></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-primary" id="add-member">Add More</button>
    </div>
</div>

<script>
    document.getElementById('add-member').addEventListener('click', function () {
        const newRow = `
            <tr>
                <td><input type="text" name="member_name[]" class="form-control" placeholder="Enter family member's name" required></td>
                <td><input type="text" name="work_type[]" class="form-control" placeholder="Enter type/nature of work" required></td>
                <td><input type="number" step="0.01" name="monthly_income[]" class="form-control" placeholder="Enter monthly income" required></td>
                <td><button type="button" class="btn btn-danger remove-member">Remove</button></td>
            </tr>
        `;
        document.getElementById('earning-members-list').insertAdjacentHTML('beforeend', newRow);
    });

    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove-member')) {
            e.target.closest('tr').remove();
        }
    });
</script>
