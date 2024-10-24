<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Revenue Goals – Expected Income / Expenditure</h4>
    </div>
    <div class="card-body">

        <!-- Business Plan Items (Year-wise) -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Business Plan (Items)</th>
                    <th>Year 1</th>
                    <th>Year 2</th>
                    <th>Year 3</th>
                    <th>Year 4</th>
                </tr>
            </thead>
            <tbody id="ilp-business-plan-body">
                <tr>
                    <td>
                        <input type="text" name="business_plan_items[0][item]" class="form-control" placeholder="Business Item" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="business_plan_items[0][year_1]" class="form-control" placeholder="Year 1" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="business_plan_items[0][year_2]" class="form-control" placeholder="Year 2" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="business_plan_items[0][year_3]" class="form-control" placeholder="Year 3" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="business_plan_items[0][year_4]" class="form-control" placeholder="Year 4" style="background-color: #202ba3;">
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Add/Remove Rows for Business Plan Items -->
        <button type="button" id="add-business-plan-item" class="btn btn-primary">Add more</button>
        <button type="button" id="remove-business-plan-item" class="btn btn-danger">Remove</button>

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
                </tr>
            </thead>
            <tbody id="ilp-annual-income-body">
                <tr>
                    <td>
                        <input type="text" name="annual_income[0][desc]" class="form-control" placeholder="Income Description" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="annual_income[0][year_1]" class="form-control" placeholder="Year 1" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="annual_income[0][year_2]" class="form-control" placeholder="Year 2" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="annual_income[0][year_3]" class="form-control" placeholder="Year 3" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="annual_income[0][year_4]" class="form-control" placeholder="Year 4" style="background-color: #202ba3;">
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Add/Remove Rows for Estimated Annual Income -->
        <button type="button" id="add-annual-income-item" class="btn btn-primary">Add more</button>
        <button type="button" id="remove-annual-income-item" class="btn btn-danger">Remove</button>

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
                </tr>
            </thead>
            <tbody id="ilp-annual-expenses-body">
                <tr>
                    <td>
                        <input type="text" name="annual_expenses[0][desc]" class="form-control" placeholder="Expense Description" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="annual_expenses[0][year_1]" class="form-control" placeholder="Year 1" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="annual_expenses[0][year_2]" class="form-control" placeholder="Year 2" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="annual_expenses[0][year_3]" class="form-control" placeholder="Year 3" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="annual_expenses[0][year_4]" class="form-control" placeholder="Year 4" style="background-color: #202ba3;">
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Add/Remove Rows for Estimated Annual Expenses -->
        <button type="button" id="add-annual-expense-item" class="btn btn-primary">Add more</button>
        <button type="button" id="remove-annual-expense-item" class="btn btn-danger">Remove</button>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let businessPlanIndex = 1, incomeIndex = 1, expenseIndex = 1;

        // Business Plan Add/Remove functionality
        const businessPlanBody = document.getElementById('ilp-business-plan-body');
        document.getElementById('add-business-plan-item').addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="business_plan_items[${businessPlanIndex}][item]" class="form-control" placeholder="Business Item" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="business_plan_items[${businessPlanIndex}][year_1]" class="form-control" placeholder="Year 1" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="business_plan_items[${businessPlanIndex}][year_2]" class="form-control" placeholder="Year 2" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="business_plan_items[${businessPlanIndex}][year_3]" class="form-control" placeholder="Year 3" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="business_plan_items[${businessPlanIndex}][year_4]" class="form-control" placeholder="Year 4" style="background-color: #202ba3;"></td>
            `;
            businessPlanBody.appendChild(row);
            businessPlanIndex++;
        });
        document.getElementById('remove-business-plan-item').addEventListener('click', function () {
            if (businessPlanBody.children.length > 1) {
                businessPlanBody.removeChild(businessPlanBody.lastElementChild);
            }
        });

        // Annual Income Add/Remove functionality
        const incomeBody = document.getElementById('ilp-annual-income-body');
        document.getElementById('add-annual-income-item').addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="annual_income[${incomeIndex}][desc]" class="form-control" placeholder="Income Description" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="annual_income[${incomeIndex}][year_1]" class="form-control" placeholder="Year 1" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="annual_income[${incomeIndex}][year_2]" class="form-control" placeholder="Year 2" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="annual_income[${incomeIndex}][year_3]" class="form-control" placeholder="Year 3" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="annual_income[${incomeIndex}][year_4]" class="form-control" placeholder="Year 4" style="background-color: #202ba3;"></td>
            `;
            incomeBody.appendChild(row);
            incomeIndex++;
        });
        document.getElementById('remove-annual-income-item').addEventListener('click', function () {
            if (incomeBody.children.length > 1) {
                incomeBody.removeChild(incomeBody.lastElementChild);
            }
        });

        // Annual Expenses Add/Remove functionality
        const expensesBody = document.getElementById('ilp-annual-expenses-body');
        document.getElementById('add-annual-expense-item').addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="annual_expenses[${expenseIndex}][desc]" class="form-control" placeholder="Expense Description" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="annual_expenses[${expenseIndex}][year_1]" class="form-control" placeholder="Year 1" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="annual_expenses[${expenseIndex}][year_2]" class="form-control" placeholder="Year 2" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="annual_expenses[${expenseIndex}][year_3]" class="form-control" placeholder="Year 3" style="background-color: #202ba3;"></td>
                <td><input type="number" step="0.01" name="annual_expenses[${expenseIndex}][year_4]" class="form-control" placeholder="Year 4" style="background-color: #202ba3;"></td>
            `;
            expensesBody.appendChild(row);
            expenseIndex++;
        });
        document.getElementById('remove-annual-expense-item').addEventListener('click', function () {
            if (expensesBody.children.length > 1) {
                expensesBody.removeChild(expensesBody.lastElementChild);
            }
        });
    });
</script>
