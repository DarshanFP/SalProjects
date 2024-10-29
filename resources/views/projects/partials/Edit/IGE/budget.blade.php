{{-- resources/views/projects/partials/Edit/IGE/budget.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Budget for Current Year</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Name</th>
                        <th>Study Proposed to be</th>
                        <th>College Fees</th>
                        <th>Hostel Fees</th>
                        <th>Total Amount</th>
                        <th>Eligibility of Scholarship (Expected Amount)</th>
                        <th>Contribution from Family</th>
                        <th>Amount Requested</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="IGE-budget-rows">
                    @if($project->igeBudget && $project->igeBudget->count())
                        @foreach($project->igeBudget as $index => $budget)
                        <tr id="budget-row-{{ $index + 1 }}">
                            <td>{{ $index + 1 }}</td>
                            <td><input type="text" name="name[]" class="form-control" value="{{ old('name.' . $index, $budget->name) }}" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="study_proposed[]" class="form-control" value="{{ old('study_proposed.' . $index, $budget->study_proposed) }}" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="college_fees[]" class="form-control college_fees" value="{{ old('college_fees.' . $index, $budget->college_fees) }}" step="0.01" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="hostel_fees[]" class="form-control hostel_fees" value="{{ old('hostel_fees.' . $index, $budget->hostel_fees) }}" step="0.01" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="total_amount[]" class="form-control total_amount" value="{{ old('total_amount.' . $index, $budget->total_amount) }}" step="0.01" style="background-color: #202ba3;" readonly></td>
                            <td><input type="number" name="scholarship_eligibility[]" class="form-control scholarship_eligibility" value="{{ old('scholarship_eligibility.' . $index, $budget->scholarship_eligibility) }}" step="0.01" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="family_contribution[]" class="form-control family_contribution" value="{{ old('family_contribution.' . $index, $budget->family_contribution) }}" step="0.01" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="amount_requested[]" class="form-control amount_requested" value="{{ old('amount_requested.' . $index, $budget->amount_requested) }}" step="0.01" style="background-color: #202ba3;" readonly></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeIGEBudgetRow(this)">Remove</button></td>
                        </tr>
                        @endforeach
                    @else
                        <tr id="budget-row-1">
                            <td>1</td>
                            <td><input type="text" name="name[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="study_proposed[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="college_fees[]" class="form-control college_fees" step="0.01" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="hostel_fees[]" class="form-control hostel_fees" step="0.01" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="total_amount[]" class="form-control total_amount" step="0.01" style="background-color: #202ba3;" readonly></td>
                            <td><input type="number" name="scholarship_eligibility[]" class="form-control scholarship_eligibility" step="0.01" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="family_contribution[]" class="form-control family_contribution" step="0.01" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="amount_requested[]" class="form-control amount_requested" step="0.01" style="background-color: #202ba3;" readonly></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeIGEBudgetRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td><input type="number" id="total-college-fees" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><input type="number" id="total-hostel-fees" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><input type="number" id="total-amount" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><input type="number" id="total-scholarship-eligibility" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><input type="number" id="total-family-contribution" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td><input type="number" id="total-amount-requested" class="form-control" step="0.01" style="background-color: #202ba3;" readonly></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addIGEBudgetRow()">Add More</button>
    </div>
</div>

<!-- JavaScript to add/remove rows dynamically and calculate totals -->
<script>
    let budgetRowIndex = {{ $project->igeBudget ? $project->igeBudget->count() : 1 }};

    function addIGEBudgetRow() {
        budgetRowIndex++;
        const newRow = document.createElement('tr');
        newRow.id = `budget-row-${budgetRowIndex}`;
        newRow.innerHTML = `
            <td>${budgetRowIndex}</td>
            <td><input type="text" name="name[]" class="form-control" style="background-color: #202ba3;"></td>
            <td><input type="text" name="study_proposed[]" class="form-control" style="background-color: #202ba3;"></td>
            <td><input type="number" name="college_fees[]" class="form-control college_fees" step="0.01" style="background-color: #202ba3;"></td>
            <td><input type="number" name="hostel_fees[]" class="form-control hostel_fees" step="0.01" style="background-color: #202ba3;"></td>
            <td><input type="number" name="total_amount[]" class="form-control total_amount" step="0.01" style="background-color: #202ba3;" readonly></td>
            <td><input type="number" name="scholarship_eligibility[]" class="form-control scholarship_eligibility" step="0.01" style="background-color: #202ba3;"></td>
            <td><input type="number" name="family_contribution[]" class="form-control family_contribution" step="0.01" style="background-color: #202ba3;"></td>
            <td><input type="number" name="amount_requested[]" class="form-control amount_requested" step="0.01" style="background-color: #202ba3;" readonly></td>
            <td><button type="button" class="btn btn-danger" onclick="removeIGEBudgetRow(this)">Remove</button></td>
        `;
        document.getElementById('IGE-budget-rows').appendChild(newRow);
        calculateTotals();
    }

    function removeIGEBudgetRow(button) {
        const row = button.closest('tr');
        row.remove();
        updateBudgetRowNumbers();
        calculateTotals();
    }

    function updateBudgetRowNumbers() {
        const rows = document.querySelectorAll('#IGE-budget-rows tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
            row.id = `budget-row-${index + 1}`;
        });
        budgetRowIndex = rows.length;
    }

    function calculateTotals() {
        let totalCollegeFees = 0;
        let totalHostelFees = 0;
        let totalIGEAmount = 0;
        let totalScholarshipEligibility = 0;
        let totalFamilyContribution = 0;
        let totalIGEAmountRequested = 0;

        document.querySelectorAll('#IGE-budget-rows tr').forEach(row => {
            const collegeFees = parseFloat(row.querySelector('input[name="college_fees[]"]').value) || 0;
            const hostelFees = parseFloat(row.querySelector('input[name="hostel_fees[]"]').value) || 0;
            const totalRowAmount = collegeFees + hostelFees;
            row.querySelector('input[name="total_amount[]"]').value = totalRowAmount.toFixed(2);

            const scholarshipEligibility = parseFloat(row.querySelector('input[name="scholarship_eligibility[]"]').value) || 0;
            const familyContribution = parseFloat(row.querySelector('input[name="family_contribution[]"]').value) || 0;
            const requestedAmount = totalRowAmount - scholarshipEligibility - familyContribution;
            row.querySelector('input[name="amount_requested[]"]').value = requestedAmount.toFixed(2);

            totalCollegeFees += collegeFees;
            totalHostelFees += hostelFees;
            totalIGEAmount += totalRowAmount;
            totalScholarshipEligibility += scholarshipEligibility;
            totalFamilyContribution += familyContribution;
            totalIGEAmountRequested += requestedAmount;
        });

        document.getElementById('total-college-fees').value = totalCollegeFees.toFixed(2);
        document.getElementById('total-hostel-fees').value = totalHostelFees.toFixed(2);
        document.getElementById('total-amount').value = totalIGEAmount.toFixed(2);
        document.getElementById('total-scholarship-eligibility').value = totalScholarshipEligibility.toFixed(2);
        document.getElementById('total-family-contribution').value = totalFamilyContribution.toFixed(2);
        document.getElementById('total-amount-requested').value = totalIGEAmountRequested.toFixed(2);
    }

    document.getElementById('IGE-budget-rows').addEventListener('input', calculateTotals);
</script>

<!-- Styles -->
<style>
    .form-control {
        background-color: #202ba3;
        color: white;
    }
</style>
