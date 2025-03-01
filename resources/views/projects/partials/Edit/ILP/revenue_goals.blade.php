
{{-- resources/views/projects/partials/Edit/ILP/revenue_goals.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Revenue Goals – Expected Income / Expenditure</h4>
    </div>
    <div class="card-body">

        <!-- Business Plan Items -->
        <h5>Business Plan Items:</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Business Plan (Items)</th>
                    <th>Year 1</th>
                    <th>Year 2</th>
                    <th>Year 3</th>
                    <th>Year 4</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="ilp-business-plan-body">
                @foreach($revenueGoals['business_plan_items'] ?? [] as $index => $item)
                    <tr>
                        <td><input type="text" name="business_plan_items[{{ $index }}][item]" class="form-control" value="{{ $item['item'] ?? '' }}"></td>
                        <td><input type="number" name="business_plan_items[{{ $index }}][year_1]" class="form-control" value="{{ $item['year_1'] ?? '' }}"></td>
                        <td><input type="number" name="business_plan_items[{{ $index }}][year_2]" class="form-control" value="{{ $item['year_2'] ?? '' }}"></td>
                        <td><input type="number" name="business_plan_items[{{ $index }}][year_3]" class="form-control" value="{{ $item['year_3'] ?? '' }}"></td>
                        <td><input type="number" name="business_plan_items[{{ $index }}][year_4]" class="form-control" value="{{ $item['year_4'] ?? '' }}"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <button type="button" id="add-business-plan-item" class="btn btn-primary">Add Item</button>

        <!-- Estimated Annual Income -->
        <h5 class="mt-4">Estimated Annual Income:</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Year 1</th>
                    <th>Year 2</th>
                    <th>Year 3</th>
                    <th>Year 4</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="ilp-annual-income-body">
                @foreach($revenueGoals['annual_income'] ?? [] as $index => $income)
                    <tr>
                        <td><input type="text" name="annual_income[{{ $index }}][description]" class="form-control" value="{{ $income['description'] ?? '' }}"></td>
                        <td><input type="number" name="annual_income[{{ $index }}][year_1]" class="form-control" value="{{ $income['year_1'] ?? '' }}"></td>
                        <td><input type="number" name="annual_income[{{ $index }}][year_2]" class="form-control" value="{{ $income['year_2'] ?? '' }}"></td>
                        <td><input type="number" name="annual_income[{{ $index }}][year_3]" class="form-control" value="{{ $income['year_3'] ?? '' }}"></td>
                        <td><input type="number" name="annual_income[{{ $index }}][year_4]" class="form-control" value="{{ $income['year_4'] ?? '' }}"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <button type="button" id="add-annual-income-item" class="btn btn-primary">Add Income</button>

        <!-- Estimated Annual Expenses -->
        <h5 class="mt-4">Estimated Annual Expenses:</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Year 1</th>
                    <th>Year 2</th>
                    <th>Year 3</th>
                    <th>Year 4</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="ilp-annual-expenses-body">
                @foreach($revenueGoals['annual_expenses'] ?? [] as $index => $expense)
                    <tr>
                        <td><input type="text" name="annual_expenses[{{ $index }}][description]" class="form-control" value="{{ $expense['description'] ?? '' }}"></td>
                        <td><input type="number" name="annual_expenses[{{ $index }}][year_1]" class="form-control" value="{{ $expense['year_1'] ?? '' }}"></td>
                        <td><input type="number" name="annual_expenses[{{ $index }}][year_2]" class="form-control" value="{{ $expense['year_2'] ?? '' }}"></td>
                        <td><input type="number" name="annual_expenses[{{ $index }}][year_3]" class="form-control" value="{{ $expense['year_3'] ?? '' }}"></td>
                        <td><input type="number" name="annual_expenses[{{ $index }}][year_4]" class="form-control" value="{{ $expense['year_4'] ?? '' }}"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <button type="button" id="add-annual-expense-item" class="btn btn-primary">Add Expense</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let businessPlanIndex = {{ count($revenueGoals['business_plan_items'] ?? []) }};
    let incomeIndex = {{ count($revenueGoals['annual_income'] ?? []) }};
    let expenseIndex = {{ count($revenueGoals['annual_expenses'] ?? []) }};

    const addRow = (tableBodyId, template, index) => {
        const tableBody = document.getElementById(tableBodyId);
        tableBody.insertAdjacentHTML('beforeend', template.replace(/INDEX/g, index));
        return index + 1;
    };

    const tableContainer = document.querySelector('.card-body');
    tableContainer.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-row')) {
            const row = event.target.closest('tr');
            row.parentNode.removeChild(row);
        }
    });

    document.getElementById('add-business-plan-item').addEventListener('click', function () {
        businessPlanIndex = addRow('ilp-business-plan-body', `
            <tr>
                <td><input type="text" name="business_plan_items[INDEX][item]" class="form-control"></td>
                <td><input type="number" name="business_plan_items[INDEX][year_1]" class="form-control"></td>
                <td><input type="number" name="business_plan_items[INDEX][year_2]" class="form-control"></td>
                <td><input type="number" name="business_plan_items[INDEX][year_3]" class="form-control"></td>
                <td><input type="number" name="business_plan_items[INDEX][year_4]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
            </tr>
        `, businessPlanIndex);
    });

    document.getElementById('add-annual-income-item').addEventListener('click', function () {
        incomeIndex = addRow('ilp-annual-income-body', `
            <tr>
                <td><input type="text" name="annual_income[INDEX][description]" class="form-control"></td>
                <td><input type="number" name="annual_income[INDEX][year_1]" class="form-control"></td>
                <td><input type="number" name="annual_income[INDEX][year_2]" class="form-control"></td>
                <td><input type="number" name="annual_income[INDEX][year_3]" class="form-control"></td>
                <td><input type="number" name="annual_income[INDEX][year_4]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
            </tr>
        `, incomeIndex);
    });

    document.getElementById('add-annual-expense-item').addEventListener('click', function () {
        expenseIndex = addRow('ilp-annual-expenses-body', `
            <tr>
                <td><input type="text" name="annual_expenses[INDEX][description]" class="form-control"></td>
                <td><input type="number" name="annual_expenses[INDEX][year_1]" class="form-control"></td>
                <td><input type="number" name="annual_expenses[INDEX][year_2]" class="form-control"></td>
                <td><input type="number" name="annual_expenses[INDEX][year_3]" class="form-control"></td>
                <td><input type="number" name="annual_expenses[INDEX][year_4]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
            </tr>
        `, expenseIndex);
    });
});

</script>

