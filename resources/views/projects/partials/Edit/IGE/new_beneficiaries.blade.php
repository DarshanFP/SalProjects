{{-- resources/views/projects/partials/Edit/IGE/new_beneficiaries.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: New Beneficiaries</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Beneficiary Name</th>
                        <th>Caste</th>
                        <th>Address</th>
                        <th>Group / Year of Study</th> <!-- Updated to match the create partial -->
                        <th>Family Background and Need of Support</th> <!-- Added missing column -->
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="new-beneficiaries-rows">
                    @if($newBeneficiaries->count())
                        @foreach($newBeneficiaries as $index => $beneficiary)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><input type="text" name="beneficiary_name[]" class="form-control" value="{{ old('beneficiary_name.' . $index, $beneficiary->beneficiary_name) }}"></td>
                            <td><input type="text" name="caste[]" class="form-control" value="{{ old('caste.' . $index, $beneficiary->caste) }}"></td>
                            <td><textarea name="address[]" class="form-control sustainability-textarea" rows="2">{{ old('address.' . $index, $beneficiary->address) }}</textarea></td>
                            <td><input type="text" name="group_year_of_study[]" class="form-control" value="{{ old('group_year_of_study.' . $index, $beneficiary->group_year_of_study) }}"></td> <!-- Updated -->
                            <td><textarea name="family_background_need[]" class="form-control sustainability-textarea" rows="2">{{ old('family_background_need.' . $index, $beneficiary->family_background_need) }}</textarea></td> <!-- Added missing field -->
                            <td><button type="button" class="btn btn-danger" onclick="removeNewBeneficiaryRow(this)">Remove</button></td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>1</td>
                            <td><input type="text" name="beneficiary_name[]" class="form-control"></td>
                            <td><input type="text" name="caste[]" class="form-control"></td>
                            <td><textarea name="address[]" class="form-control sustainability-textarea" rows="2"></textarea></td>
                            <td><input type="text" name="group_year_of_study[]" class="form-control"></td>
                            <td><textarea name="family_background_need[]" class="form-control sustainability-textarea" rows="2"></textarea></td> <!-- Added -->
                            <td><button type="button" class="btn btn-danger" onclick="removeNewBeneficiaryRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <button type="button" class="mt-3 btn btn-primary" onclick="addNewBeneficiaryRow()">Add More</button>
        </div>
    </div>
</div>

<!-- JavaScript functions to add/remove rows -->
<script>
    let newBeneficiaryRowIndex = {{ $newBeneficiaries->count() ?? 1 }};

    function addNewBeneficiaryRow() {
        newBeneficiaryRowIndex++;
        const newRow = `
            <tr>
                <td>${newBeneficiaryRowIndex}</td>
                <td><input type="text" name="beneficiary_name[]" class="form-control"></td>
                <td><input type="text" name="caste[]" class="form-control"></td>
                <td><textarea name="address[]" class="form-control sustainability-textarea" rows="2"></textarea></td>
                <td><input type="text" name="group_year_of_study[]" class="form-control"></td> <!-- Updated -->
                <td><textarea name="family_background_need[]" class="form-control sustainability-textarea" rows="2"></textarea></td> <!-- Added -->
                <td><button type="button" class="btn btn-danger" onclick="removeNewBeneficiaryRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('new-beneficiaries-rows').insertAdjacentHTML('beforeend', newRow);

        // Initialize auto-resize for newly added textareas using global function
        const newRowElement = document.getElementById('new-beneficiaries-rows').lastElementChild;
        const newTextareas = newRowElement.querySelectorAll('.sustainability-textarea');
        if (newTextareas.length > 0 && typeof window.initTextareaAutoResize === 'function') {
            newTextareas.forEach(textarea => {
                window.initTextareaAutoResize(textarea);
            });
        }
    }

    function removeNewBeneficiaryRow(button) {
        const row = button.closest('tr');
        row.remove();
        updateNewBeneficiaryRowNumbers();
    }

    function updateNewBeneficiaryRowNumbers() {
        const rows = document.querySelectorAll('#new-beneficiaries-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
        newBeneficiaryRowIndex = rows.length;
    }

    // Initialize auto-resize for newly added textareas using global function
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize existing textareas (global script handles this, but ensure dynamic ones work)
        document.querySelectorAll('#new-beneficiaries-rows .sustainability-textarea').forEach(textarea => {
            if (typeof window.initTextareaAutoResize === 'function') {
                window.initTextareaAutoResize(textarea);
            }
        });
    });
</script>
