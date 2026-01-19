{{-- resources/views/projects/partials/IES/family_working_members.blade.php --}}
{{-- <div class="mb-3 card">
    <div class="card-header">
        <h4>Details of Other Working Family Members 1</h4>
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
                <tbody id="IESfamily-working-members-rows">
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="member_name[0]" class="form-control"></td>
                        <td><input type="text" name="work_nature[0]" class="form-control"></td>
                        <td><input type="number" name="monthly_income[0]" class="form-control" step="0.01"></td>
                        <td><button type="button" class="btn btn-danger" onclick="IESremoveFamilyMemberRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="IESaddFamilyMemberRow()">Add More Family Member</button>
    </div>
</div>

<!-- JavaScript Functions -->
<script>
    // Function to add a new family member row
    function IESaddFamilyMemberRow() {
        const container = document.getElementById('IESfamily-working-members-rows');
        const rowCount = container.children.length;
        const newRow = `
            <tr>
                <td>${rowCount + 1}</td>
                <td><input type="text" name="member_name[${rowCount}]" class="form-control"></td>
                <td><input type="text" name="work_nature[${rowCount}]" class="form-control"></td>
                <td><input type="number" name="monthly_income[${rowCount}]" class="form-control" step="0.01"></td>
                <td><button type="button" class="btn btn-danger" onclick="IESremoveFamilyMemberRow(this)">Remove</button></td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', newRow);
    }

    // Function to remove a family member row
    function IESremoveFamilyMemberRow(button) {
        const row = button.closest('tr');
        row.remove();
        IESupdateFamilyMemberRowNumbers();
    }

    // Function to update row numbers and input names
    function IESupdateFamilyMemberRowNumbers() {
        const rows = document.querySelectorAll('#IESfamily-working-members-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
            row.querySelector(`input[name^="member_name"]`).setAttribute('name', `member_name[${index}]`);
            row.querySelector(`input[name^="work_nature"]`).setAttribute('name', `work_nature[${index}]`);
            row.querySelector(`input[name^="monthly_income"]`).setAttribute('name', `monthly_income[${index}]`);
        });
    }
</script>

<!-- Styles -->
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

    /* Remove spinner arrows for number inputs */
    input[type='number']::-webkit-outer-spin-button,
    input[type='number']::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type='number'] {
        -moz-appearance: textfield;
        appearance: textfield;
    }

    /* Add some spacing around the card */
    .card {
        margin: 20px 0;
    }

    .btn {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
</style> --}}
{{-- resources/views/projects/partials/family_working_members.blade.php --}}
@php
    // Provide a default in case prefix is not passed
    $prefix = $prefix ?? 'ies';
@endphp

<div class="mb-3 card">
    <div class="card-header">
        <h4>Details of Other Working Family Members</h4>
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
                <tbody id="{{ $prefix }}-family-members-rows">
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="member_name[0]" class="form-control"></td>
                        <td><input type="text" name="work_nature[0]" class="form-control"></td>
                        <td><input type="number" name="monthly_income[0]" class="form-control" step="0.01"></td>
                        <td><button type="button" class="btn btn-danger" onclick="{{ $prefix }}RemoveRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Notice the function call includes the prefix -->
        <button type="button" class="mt-3 btn btn-primary" onclick="{{ $prefix }}AddRow()">Add More Family Member</button>
    </div>
</div>

<script>
    // Add row
    function {{ $prefix }}AddRow() {
        const container = document.getElementById('{{ $prefix }}-family-members-rows');
        const rowCount = container.children.length;
        const newRow = `
            <tr>
                <td>${rowCount + 1}</td>
                <td><input type="text" name="member_name[${rowCount}]" class="form-control"></td>
                <td><input type="text" name="work_nature[${rowCount}]" class="form-control"></td>
                <td><input type="number" name="monthly_income[${rowCount}]" class="form-control" step="0.01"></td>
                <td><button type="button" class="btn btn-danger" onclick="{{ $prefix }}RemoveRow(this)">Remove</button></td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', newRow);
    }

    // Remove row
    function {{ $prefix }}RemoveRow(button) {
        const row = button.closest('tr');
        row.remove();
        {{ $prefix }}ReindexRows();
    }

    // Reindex row numbers
    function {{ $prefix }}ReindexRows() {
        const container = document.getElementById('{{ $prefix }}-family-members-rows');
        const rows = container.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
            row.querySelector('input[name^="member_name"]')
               .setAttribute('name', `member_name[${index}]`);
            row.querySelector('input[name^="work_nature"]')
               .setAttribute('name', `work_nature[${index}]`);
            row.querySelector('input[name^="monthly_income"]')
               .setAttribute('name', `monthly_income[${index}]`);
        });
    }
</script>
