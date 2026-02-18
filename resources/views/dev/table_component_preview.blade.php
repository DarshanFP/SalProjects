{{--
    Developer-only preview for table components. Not used in production routes.
    To view: register a dev route that returns this view with optional $sampleCollection and $columns.
    Example: Route::get('/dev/table-preview', fn () => view('dev.table_component_preview'));
--}}
@php
    if (!isset($sampleCollection)) {
        $sampleCollection = collect([
            (object) ['particular' => 'Budget line A', 'amount_sanctioned' => 10000.50, 'total_expenses' => 3000, 'balance_amount' => 7000.50],
            (object) ['particular' => 'Budget line B', 'amount_sanctioned' => 25000, 'total_expenses' => 12000, 'balance_amount' => 13000],
            (object) ['particular' => 'Budget line C', 'amount_sanctioned' => 5000, 'total_expenses' => 5000, 'balance_amount' => 0],
        ]);
    }
    if (!isset($columns)) {
        $columns = [
            ['key' => 'particular', 'label' => 'Particulars'],
            ['key' => 'amount_sanctioned', 'label' => 'Amount Sanctioned', 'numeric' => true],
            ['key' => 'total_expenses', 'label' => 'Total Expenses', 'numeric' => true],
            ['key' => 'balance_amount', 'label' => 'Balance Amount', 'numeric' => true],
        ];
    }
    $columnsWithProjectId = [
        ['key' => 'project_id', 'label' => 'Project ID'],
        ['key' => 'particular', 'label' => 'Particulars'],
        ['key' => 'amount_sanctioned', 'label' => 'Amount Sanctioned', 'numeric' => true],
        ['key' => 'balance_amount', 'label' => 'Balance Amount', 'numeric' => true],
    ];
    $sampleWithProjectId = collect([
        (object) ['project_id' => 'PRJ-001', 'particular' => 'Line A', 'amount_sanctioned' => 10000, 'balance_amount' => 7000],
        (object) ['project_id' => 'PRJ-002', 'particular' => 'Line B', 'amount_sanctioned' => 25000, 'balance_amount' => 13000],
    ]);
    $grandTotals = ['amount_sanctioned' => 35000.50, 'total_expenses' => 20000, 'balance_amount' => 20000.50];
    $totalCount = 3;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Table component preview (dev)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h1 class="mb-4">Table component preview (dev)</h1>
        <p class="text-muted small">For developer verification only. Not used in production.</p>

        <h2 class="h5 mt-4">FinancialTable (base)</h2>
        <x-financial-table
            :collection="$sampleCollection"
            :columns="$columns"
            :serial="true"
            :paginated="false"
            :showTotals="true"
        />

        <h2 class="h5 mt-4">FinancialTable (summary + clickable project ID)</h2>
        <x-financial-table
            :collection="$sampleWithProjectId"
            :columns="$columnsWithProjectId"
            :showSummary="true"
            :grandTotals="$grandTotals"
            :totalRecordCount="$totalCount"
            :linkProjectId="true"
            projectIdColumn="project_id"
        />
    </div>
</body>
</html>
