{{-- resources/views/projects/partials/IGE/ongoing_beneficiaries.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Ongoing Beneficiaries (Students who were supported in the previous year and requesting support this academic year)</h4>
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
                        <th>Present Group / Year of Study</th>
                        <th>Performance Details</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="IGS-ongoing-beneficiaries-rows">
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="obeneficiary_name[]" class="form-control"></td>
                        <td><input type="text" name="ocaste[]" class="form-control"></td>
                        <td><textarea name="oaddress[]" class="form-control sustainability-textarea" rows="2"></textarea></td>
                        <td><input type="text" name="ocurrent_group_year_of_study[]" class="form-control"></td>
                        <td><textarea name="operformance_details[]" class="form-control sustainability-textarea" rows="2"></textarea></td>
                        <td><button type="button" class="btn btn-danger" onclick="IGSremoveOngoingBeneficiaryRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="IGSaddOngoingBeneficiaryRow()">Add More</button>
    </div>
</div>

<!-- JavaScript to add/remove rows dynamically -->
<script>
    let IGSongoingBeneficiaryRowIndex = 1;

    window.IGSaddOngoingBeneficiaryRow = function() {
        IGSongoingBeneficiaryRowIndex++;
        const newRow = `
            <tr>
                <td>${IGSongoingBeneficiaryRowIndex}</td>
                <td><input type="text" name="obeneficiary_name[]" class="form-control"></td>
                <td><input type="text" name="ocaste[]" class="form-control"></td>
                <td><textarea name="oaddress[]" class="form-control sustainability-textarea" rows="2"></textarea></td>
                <td><input type="text" name="ocurrent_group_year_of_study[]" class="form-control"></td>
                <td><textarea name="operformance_details[]" class="form-control sustainability-textarea" rows="2"></textarea></td>
                <td><button type="button" class="btn btn-danger" onclick="IGSremoveOngoingBeneficiaryRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('IGS-ongoing-beneficiaries-rows').insertAdjacentHTML('beforeend', newRow);

        // Initialize auto-resize for newly added textareas using global function
        const newRowElement = document.getElementById('IGS-ongoing-beneficiaries-rows').lastElementChild;
        const newTextareas = newRowElement.querySelectorAll('.sustainability-textarea');
        if (newTextareas.length > 0 && typeof window.initTextareaAutoResize === 'function') {
            newTextareas.forEach(textarea => {
                window.initTextareaAutoResize(textarea);
            });
        }
    }

    window.IGSremoveOngoingBeneficiaryRow = function(button) {
        const row = button.closest('tr');
        row.remove();
        IGSupdateOngoingBeneficiaryRowNumbers();
    }

    function IGSupdateOngoingBeneficiaryRowNumbers() {
        const rows = document.querySelectorAll('#IGS-ongoing-beneficiaries-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
        IGSongoingBeneficiaryRowIndex = rows.length;
    }
</script>
