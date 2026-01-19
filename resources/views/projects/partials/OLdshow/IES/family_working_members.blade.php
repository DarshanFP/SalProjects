{{-- resources/views/projects/partials/Edit/IES/family_working_members.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Details of Other Working Family Members</h4>
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
                <tbody id="family-working-members-rows">
                    @if($project->iesFamilyWorkingMembers && $project->iesFamilyWorkingMembers->count())
                        @foreach($project->iesFamilyWorkingMembers as $index => $member)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><input type="text" name="member_name[{{ $index }}]" class="form-control" value="{{ old('member_name.' . $index, $member->member_name) }}"></td>
                            <td><input type="text" name="work_nature[{{ $index }}]" class="form-control" value="{{ old('work_nature.' . $index, $member->work_nature) }}"></td>
                            <td><input type="number" name="monthly_income[{{ $index }}]" class="form-control" step="0.01" value="{{ old('monthly_income.' . $index, $member->monthly_income) }}"></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeFamilyMemberRow(this)">Remove</button></td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>1</td>
                            <td><input type="text" name="member_name[0]" class="form-control"></td>
                            <td><input type="text" name="work_nature[0]" class="form-control"></td>
                            <td><input type="number" name="monthly_income[0]" class="form-control" step="0.01"></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeFamilyMemberRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addFamilyMemberRow()">Add More Family Member</button>
    </div>
</div>

<!-- JavaScript to add/remove rows -->
<script>
    function addFamilyMemberRow() {
        const container = document.getElementById('family-working-members-rows');
        const rowCount = container.children.length;
        const newRow = `
            <tr>
                <td>${rowCount + 1}</td>
                <td><input type="text" name="member_name[${rowCount}]" class="form-control"></td>
                <td><input type="text" name="work_nature[${rowCount}]" class="form-control"></td>
                <td><input type="number" name="monthly_income[${rowCount}]" class="form-control" step="0.01"></td>
                <td><button type="button" class="btn btn-danger" onclick="removeFamilyMemberRow(this)">Remove</button></td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', newRow);
    }

    function removeFamilyMemberRow(button) {
        const row = button.closest('tr');
        row.remove();
        updateFamilyMemberRowNumbers();
    }

    function updateFamilyMemberRowNumbers() {
        const rows = document.querySelectorAll('#family-working-members-rows tr');
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

    input[type='number']::-webkit-outer-spin-button,
    input[type='number']::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type='number'] {
        -moz-appearance: textfield;
        appearance: textfield;
    }
</style>
