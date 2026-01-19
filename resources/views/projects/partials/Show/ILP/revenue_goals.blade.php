{{-- <pre>{{ print_r($ILPRevenueGoals, return: true) }}</pre> --}}

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Revenue Goals – Expected Income / Expenditure</h4>
    </div>
    <div class="card-body">

        <!-- Business Plan Items (Year-wise) -->
        <h5>Business Plan Items</h5>
        @if(isset($ILPRevenueGoals) && !empty($ILPRevenueGoals['business_plan_items']))
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th>Business Plan (Items)</th>
                        <th>Year 1</th>
                        <th>Year 2</th>
                        <th>Year 3</th>
                        <th>Year 4</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ILPRevenueGoals['business_plan_items'] as $index => $item)
                        <tr>
                            <td style="text-align: center; vertical-align: middle;">{{ $index + 1 }}</td>
                            <td>{{ $item['item'] ?? 'N/A' }}</td>
                            <td>{{ $item['year_1'] ?? 0 }}</td>
                            <td>{{ $item['year_2'] ?? 0 }}</td>
                            <td>{{ $item['year_3'] ?? 0 }}</td>
                            <td>{{ $item['year_4'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No Business Plan data available.</p>
        @endif

        <!-- Estimated Annual Income -->
        <h5 class="mt-4">Estimated Annual Income</h5>
        @if(isset($ILPRevenueGoals) && !empty($ILPRevenueGoals['annual_income']))
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th>Description</th>
                        <th>Year 1</th>
                        <th>Year 2</th>
                        <th>Year 3</th>
                        <th>Year 4</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ILPRevenueGoals['annual_income'] as $index => $income)
                        <tr>
                            <td style="text-align: center; vertical-align: middle;">{{ $index + 1 }}</td>
                            <td>{{ $income['description'] ?? 'N/A' }}</td>
                            <td>{{ $income['year_1'] ?? 0 }}</td>
                            <td>{{ $income['year_2'] ?? 0 }}</td>
                            <td>{{ $income['year_3'] ?? 0 }}</td>
                            <td>{{ $income['year_4'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No Estimated Annual Income data available.</p>
        @endif

        <!-- Estimated Annual Expenses -->
        <h5 class="mt-4">Estimated Annual Expenses</h5>
        @if(isset($ILPRevenueGoals) && !empty($ILPRevenueGoals['annual_expenses']))
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th>Description</th>
                        <th>Year 1</th>
                        <th>Year 2</th>
                        <th>Year 3</th>
                        <th>Year 4</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ILPRevenueGoals['annual_expenses'] as $index => $expense)
                        <tr>
                            <td style="text-align: center; vertical-align: middle;">{{ $index + 1 }}</td>
                            <td>{{ $expense['description'] ?? 'N/A' }}</td>
                            <td>{{ $expense['year_1'] ?? 0 }}</td>
                            <td>{{ $expense['year_2'] ?? 0 }}</td>
                            <td>{{ $expense['year_3'] ?? 0 }}</td>
                            <td>{{ $expense['year_4'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No Estimated Annual Expenses data available.</p>
        @endif

    </div>
</div>
