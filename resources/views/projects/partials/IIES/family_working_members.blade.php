{{-- resources/views/projects/partials/IIES/family_working_members.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Details of Other Working Family Members IIES</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Family Member</th>
                        <th>Type/Nature of Work</th>
                        <th>Monthly Income</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="iies-family-members-rows">
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="iies_member_name[0]" class="form-control"></td>
                        <td><input type="text" name="iies_work_nature[0]" class="form-control"></td>
                        <td><input type="number" name="iies_monthly_income[0]" class="form-control" step="0.01"></td>
                        <td><button type="button" class="btn btn-danger" onclick="iiesRemoveRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="iiesAddRow()">Add More Family Member</button>
    </div>
</div>

<script>
    // Add row
    function iiesAddRow() {
        const container = document.getElementById('iies-family-members-rows');
        const rowCount = container.children.length;
        const newRow = `
            <tr>
                <td>${rowCount + 1}</td>
                <td><input type="text" name="iies_member_name[${rowCount}]" class="form-control"></td>
                <td><input type="text" name="iies_work_nature[${rowCount}]" class="form-control"></td>
                <td><input type="number" name="iies_monthly_income[${rowCount}]" class="form-control" step="0.01"></td>
                <td><button type="button" class="btn btn-danger" onclick="iiesRemoveRow(this)">Remove</button></td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', newRow);
    }

    // Remove row
    function iiesRemoveRow(button) {
        const row = button.closest('tr');
        row.remove();
        iiesReindexRows();
    }

    // Reindex row numbers
    function iiesReindexRows() {
        const container = document.getElementById('iies-family-members-rows');
        const rows = container.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
            row.querySelector('input[name^="iies_member_name"]')
               .setAttribute('name', `iies_member_name[${index}]`);
            row.querySelector('input[name^="iies_work_nature"]')
               .setAttribute('name', `iies_work_nature[${index}]`);
            row.querySelector('input[name^="iies_monthly_income"]')
               .setAttribute('name', `iies_monthly_income[${index}]`);
        });
    }
</script>

<style>
    .table td input {
        width: 100%;
        box-sizing: border-box;
    }

    .table th, .table td {
        vertical-align: middle;
        text-align: center;
        padding: 0.5rem;
    }

    input[type='number']::-webkit-outer-spin-button,
    input[type='number']::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type='number'] {
        -moz-appearance: textfield;
        appearance: textfield;
    }

    .card {
        margin: 20px 0;
    }

    .btn {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
</style>
