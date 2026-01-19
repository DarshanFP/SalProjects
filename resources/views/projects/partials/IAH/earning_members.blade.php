{{-- resources/views/projects/partials/IAH/earning_members.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Provide the list of earning members of the family</h4>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th>Family Member</th>
                    <th>Type/Nature of Work</th>
                    <th>Monthly Income</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="earning-members-list">
                <tr>
                    <td style="text-align: center; vertical-align: middle;">1</td>
                    <td><input type="text" name="member_name[]" class="form-control" placeholder="Enter family member's name"></td>
                    <td><input type="text" name="work_type[]" class="form-control" placeholder="Enter type/nature of work"></td>
                    <td><input type="number" step="0.01" name="monthly_income[]" class="form-control" placeholder="Enter monthly income"></td>
                    <td><button type="button" class="btn btn-danger remove-member">Remove</button></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-primary" id="add-member">Add More</button>
    </div>
</div>

<script>
    (function(){
    document.getElementById('add-member').addEventListener('click', function () {
        const table = document.getElementById('earning-members-list');
        const rowCount = table.children.length;
        const newRow = `
            <tr>
                <td style="text-align: center; vertical-align: middle;">${rowCount + 1}</td>
                <td><input type="text" name="member_name[]" class="form-control" placeholder="Enter family member's name"></td>
                <td><input type="text" name="work_type[]" class="form-control" placeholder="Enter type/nature of work"></td>
                <td><input type="number" step="0.01" name="monthly_income[]" class="form-control" placeholder="Enter monthly income"></td>
                <td><button type="button" class="btn btn-danger remove-member">Remove</button></td>
            </tr>
        `;
        table.insertAdjacentHTML('beforeend', newRow);
        reindexEarningMembers();
    });

    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove-member')) {
            e.target.closest('tr').remove();
            reindexEarningMembers();
        }
    });
    
    function reindexEarningMembers() {
        const rows = document.querySelectorAll('#earning-members-list tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
    }
})();
</script>
