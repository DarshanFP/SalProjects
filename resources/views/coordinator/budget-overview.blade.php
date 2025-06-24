@extends('coordinator.dashboard')

@section('content')
<div class="px-4 container-fluid" style="padding-top: 80px;">
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <h1 class="mb-4">Project Budgets Overview</h1>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" onclick="exportToExcel()">
                <i class="feather icon-download"></i> Export to Excel
            </button>
            <button type="button" class="btn btn-success" onclick="exportToPDF()">
                <i class="feather icon-file-text"></i> Export to PDF
            </button>
        </div>
    </div>

    <!-- Overall Summary Cards -->
    <div class="mt-4 row">
        <div class="col-xl-4 col-md-6">
            <div class="mb-4 text-white card bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Budget</h5>
                    <h3 class="card-text">₹{{ number_format($overallTotals['total_budget'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="mb-4 text-white card bg-success">
                <div class="card-body">
                    <h5 class="card-title">Total Expenses</h5>
                    <h3 class="card-text">₹{{ number_format($overallTotals['total_expenses'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="mb-4 text-white card bg-info">
                <div class="card-body">
                    <h5 class="card-title">Total Remaining</h5>
                    <h3 class="card-text">₹{{ number_format($overallTotals['total_remaining'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Processing Section -->
    @php
        // For Project Type Chart
        $projectTypeData = collect($budgetData)->map(function($provinces, $type) {
            $totalBudget = collect($provinces)->sum(function($data) {
                return $data['total_budget'] ?? 0;
            });
            $totalExpenses = collect($provinces)->sum(function($data) {
                return $data['total_expenses'] ?? 0;
            });
            return [
                'type' => $type,
                'budget' => $totalBudget,
                'expenses' => $totalExpenses
            ];
        })->filter(function($item) {
            return $item['budget'] > 0 || $item['expenses'] > 0;
        })->values()->all();

        // For Province Chart - Fixed aggregation
        $provinceData = [];

        // First, collect all data for each province
        foreach ($budgetData as $type => $provinces) {
            foreach ($provinces as $province => $data) {
                if (!isset($provinceData[$province])) {
                    $provinceData[$province] = [
                        'province' => $province,
                        'budget' => 0,
                        'expenses' => 0
                    ];
                }
                // Add the budget and expenses for this project type to the province total
                $provinceData[$province]['budget'] += $data['total_budget'] ?? 0;
                $provinceData[$province]['expenses'] += $data['total_expenses'] ?? 0;
            }
        }

        // Convert to array and filter out provinces with no budget
        $provinceData = array_values(array_filter($provinceData, function($item) {
            return $item['budget'] > 0 || $item['expenses'] > 0;
        }));

        // Debug: Print the data
        echo "<!-- Project Type Data: " . json_encode($projectTypeData) . " -->";
        echo "<!-- Province Data: " . json_encode($provinceData) . " -->";
    @endphp

    <!-- Charts Section -->
    <div class="mt-4">
        <!-- Project Type Chart -->
        <div class="mb-4 card">
            <div class="card-header">
                <h5 class="card-title">Budget Distribution by Project Type</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Chart Column -->
                    <div class="col-md-6">
                        <div id="projectTypeChart" style="min-height: 400px;">
                            <!-- Chart will be rendered here -->
                        </div>
                    </div>
                    <!-- Data Column -->
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Project Type</th>
                                        <th class="text-end">Budget</th>
                                        <th class="text-end">Expenses</th>
                                        <th class="text-end">Remaining</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($projectTypeData as $item)
                                    <tr>
                                        <td>{{ $item['type'] }}</td>
                                        <td class="text-end">₹{{ number_format($item['budget'], 2) }}</td>
                                        <td class="text-end">₹{{ number_format($item['expenses'], 2) }}</td>
                                        <td class="text-end">₹{{ number_format($item['budget'] - $item['expenses'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="table-primary">
                                        <td><strong>Total</strong></td>
                                        <td class="text-end"><strong>₹{{ number_format(array_sum(array_column($projectTypeData, 'budget')), 2) }}</strong></td>
                                        <td class="text-end"><strong>₹{{ number_format(array_sum(array_column($projectTypeData, 'expenses')), 2) }}</strong></td>
                                        <td class="text-end"><strong>₹{{ number_format(array_sum(array_column($projectTypeData, 'budget')) - array_sum(array_column($projectTypeData, 'expenses')), 2) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Province Chart -->
        <div class="mb-4 card">
            <div class="card-header">
                <h5 class="card-title">Budget Distribution by Province</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Chart Column -->
                    <div class="col-md-6">
                        <div id="provinceChart" style="min-height: 400px;">
                            <!-- Chart will be rendered here -->
                        </div>
                    </div>
                    <!-- Data Column -->
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Province</th>
                                        <th class="text-end">Budget</th>
                                        <th class="text-end">Expenses</th>
                                        <th class="text-end">Remaining</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($provinceData as $item)
                                    <tr>
                                        <td>{{ $item['province'] }}</td>
                                        <td class="text-end">₹{{ number_format($item['budget'], 2) }}</td>
                                        <td class="text-end">₹{{ number_format($item['expenses'], 2) }}</td>
                                        <td class="text-end">₹{{ number_format($item['budget'] - $item['expenses'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="table-primary">
                                        <td><strong>Total</strong></td>
                                        <td class="text-end"><strong>₹{{ number_format(array_sum(array_column($provinceData, 'budget')), 2) }}</strong></td>
                                        <td class="text-end"><strong>₹{{ number_format(array_sum(array_column($provinceData, 'expenses')), 2) }}</strong></td>
                                        <td class="text-end"><strong>₹{{ number_format(array_sum(array_column($provinceData, 'budget')) - array_sum(array_column($provinceData, 'expenses')), 2) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- <!-- Debug Information -->
    @if(config('app.debug'))
    <div class="mt-4 card">
        <div class="card-header">
            <h5 class="card-title">Debug Information</h5>
        </div>
        <div class="card-body">
            <h6>Raw Budget Data Structure:</h6>
            <pre>{{ print_r($budgetData, true) }}</pre>

            <h6>Project Type Data:</h6>
            <pre>{{ print_r($projectTypeData, true) }}</pre>

            <h6>Province Data:</h6>
            <pre>{{ print_r($provinceData, true) }}</pre>
        </div>
    </div>
    @endif --}}

    <!-- Filters and Table -->
    <div class="mb-4 card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Budget Details</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="toggleColumns">
                        <i class="feather icon-columns"></i> Customize Columns
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="mb-4 row">
                <div class="col-md-3">
                    <select class="form-select" id="projectTypeFilter">
                        <option value="">All Project Types</option>
                        @foreach($budgetData as $type => $provinces)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="provinceFilter">
                        <option value="">All Provinces</option>
                        @php
                            $uniqueProvinces = collect($budgetData)
                                ->flatMap(function($provinces) {
                                    return array_keys($provinces);
                                })
                                ->unique()
                                ->values()
                                ->all();
                        @endphp
                        @foreach($uniqueProvinces as $province)
                            <option value="{{ $province }}">{{ $province }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search projects...">
                </div>
            </div>

            <!-- Budget Table -->
            <div class="table-responsive">
                <table class="table table-hover" id="budgetTable">
                    <thead>
                        <tr>
                            <th>Project Type</th>
                            <th>Province</th>
                            <th>Total Budget</th>
                            <th>Total Expenses</th>
                            <th>Remaining</th>
                            <th>Utilization</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($budgetData as $type => $provinces)
                            @foreach($provinces as $province => $data)
                                <tr>
                                    <td>{{ $type }}</td>
                                    <td>{{ $province }}</td>
                                    <td>₹{{ number_format($data['total_budget'], 2) }}</td>
                                    <td>₹{{ number_format($data['total_expenses'], 2) }}</td>
                                    <td>₹{{ number_format($data['total_remaining'], 2) }}</td>
                                    <td>
                                        @php
                                            $utilization = $data['total_budget'] > 0
                                                ? ($data['total_expenses'] / $data['total_budget']) * 100
                                                : 0;
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar {{ $utilization > 90 ? 'bg-danger' : ($utilization > 70 ? 'bg-warning' : 'bg-success') }}"
                                                 role="progressbar"
                                                 style="width: {{ $utilization }}%"
                                                 aria-valuenow="{{ $utilization }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                                {{ number_format($utilization, 1) }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="showProjectDetails('{{ $type }}', '{{ $province }}')">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Project Details Modal -->
<div class="modal fade" id="projectDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Project Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="projectDetailsContent">
                <!-- Content will be dynamically loaded -->
            </div>
        </div>
    </div>
</div>

<!-- Column Customization Modal -->
<div class="modal fade" id="columnCustomizationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customize Columns</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-check">
                    <input class="form-check-input column-toggle" type="checkbox" value="Project Type" checked>
                    <label class="form-check-label">Project Type</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input column-toggle" type="checkbox" value="Province" checked>
                    <label class="form-check-label">Province</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input column-toggle" type="checkbox" value="Total Budget" checked>
                    <label class="form-check-label">Total Budget</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input column-toggle" type="checkbox" value="Total Expenses" checked>
                    <label class="form-check-label">Total Expenses</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input column-toggle" type="checkbox" value="Remaining" checked>
                    <label class="form-check-label">Remaining</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input column-toggle" type="checkbox" value="Utilization" checked>
                    <label class="form-check-label">Utilization</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input column-toggle" type="checkbox" value="Actions" checked>
                    <label class="form-check-label">Actions</label>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Debug: Check if jQuery and ApexCharts are available
console.log('jQuery available:', typeof jQuery !== 'undefined');
console.log('ApexCharts available:', typeof ApexCharts !== 'undefined');

// Debug: Check if chart containers exist
console.log('Project Type Chart container:', document.querySelector("#projectTypeChart"));
console.log('Province Chart container:', document.querySelector("#provinceChart"));

// Initialize DataTable
$(document).ready(function() {
    console.log('Document ready - Initializing charts and table');

    try {
        // Verify dependencies are available
        if (typeof jQuery === 'undefined') {
            console.error('jQuery library not found!');
            return;
        }
        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts library not found!');
            document.querySelector("#projectTypeChart").innerHTML = '<div class="alert alert-danger">Chart library not loaded. Please refresh the page.</div>';
            document.querySelector("#provinceChart").innerHTML = '<div class="alert alert-danger">Chart library not loaded. Please refresh the page.</div>';
            return;
        }

        // Debug: Log the data being passed to charts
        const projectTypeData = @json($projectTypeData);
        const provinceData = @json($provinceData);
        console.log('Project Type Data for chart:', projectTypeData);
        console.log('Province Data for chart:', provinceData);

        const table = $('#budgetTable').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
        console.log('DataTable initialized successfully');

        // Initialize Charts
        initializeCharts();

        // Filter handlers
        $('#projectTypeFilter, #provinceFilter').on('change', function() {
            table.draw();
        });

        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Column customization
        $('#toggleColumns').click(function() {
            $('#columnCustomizationModal').modal('show');
        });

        $('.column-toggle').change(function() {
            const column = table.column($(this).val() + ':name');
            column.visible($(this).prop('checked'));
        });

        // Add detailed debugging for province chart data
        console.log('Raw Province Data:', @json($provinceData));
        console.log('Province Budgets:', @json(array_column($provinceData, 'budget')));
        console.log('Province Expenses:', @json(array_column($provinceData, 'expenses')));
    } catch (error) {
        console.error('Error in initialization:', error);
        // Show error in chart containers
        document.querySelector("#projectTypeChart").innerHTML = '<div class="alert alert-danger">Error initializing chart: ' + error.message + '</div>';
        document.querySelector("#provinceChart").innerHTML = '<div class="alert alert-danger">Error initializing chart: ' + error.message + '</div>';
    }
});

// Update chart options for dark theme
function initializeCharts() {
    console.log('Starting chart initialization');

    try {
        // Common chart options for dark theme
        const darkThemeOptions = {
            theme: {
                mode: 'dark',
                palette: 'palette1'
            },
            chart: {
                background: '#1e293b',
                foreColor: '#fff',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                }
            },
            colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                offsetY: 0,
                labels: {
                    colors: '#fff'
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 6
                },
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(val) {
                        return '₹' + val.toLocaleString('en-IN');
                    }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        background: 'transparent',
                        labels: {
                            show: false // Hide the center labels since we're showing data in table
                        }
                    }
                }
            }
        };

        // Project Type Chart
        const projectTypeData = @json($projectTypeData);
        const projectTypeChart = new ApexCharts(document.querySelector("#projectTypeChart"), {
            ...darkThemeOptions,
            series: projectTypeData.map(item => item.budget),
            chart: {
                ...darkThemeOptions.chart,
                type: 'donut',
                height: 400
            },
            labels: projectTypeData.map(item => item.type)
        });

        // Province Chart
        const provinceData = @json($provinceData);
        const provinceChart = new ApexCharts(document.querySelector("#provinceChart"), {
            ...darkThemeOptions,
            series: provinceData.map(item => item.budget),
            chart: {
                ...darkThemeOptions.chart,
                type: 'donut',
                height: 400
            },
            labels: provinceData.map(item => item.province)
        });

        // Render charts
        projectTypeChart.render();
        provinceChart.render();

    } catch (error) {
        console.error('Error in initializeCharts:', error);
        document.querySelector("#projectTypeChart").innerHTML = '<div class="alert alert-danger">Error rendering chart: ' + error.message + '</div>';
        document.querySelector("#provinceChart").innerHTML = '<div class="alert alert-danger">Error rendering chart: ' + error.message + '</div>';
    }
}

// Export Functions
function exportToExcel() {
    const table = $('#budgetTable').DataTable();
    table.button('.buttons-excel').trigger();
}

function exportToPDF() {
    const table = $('#budgetTable').DataTable();
    table.button('.buttons-pdf').trigger();
}

// Project Details Modal
function showProjectDetails(type, province) {
    const budgetData = @json($budgetData);
    const projects = budgetData[type][province].projects;
    let content = `
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project Title</th>
                        <th>Executor</th>
                        <th>Budget</th>
                        <th>Expenses</th>
                        <th>Remaining</th>
                    </tr>
                </thead>
                <tbody>
    `;

    projects.forEach(project => {
        content += `
            <tr>
                <td>${project.title}</td>
                <td>${project.executor}</td>
                <td>₹${project.total_budget.toLocaleString('en-IN', {maximumFractionDigits: 2})}</td>
                <td>₹${project.total_expenses.toLocaleString('en-IN', {maximumFractionDigits: 2})}</td>
                <td>₹${project.total_remaining.toLocaleString('en-IN', {maximumFractionDigits: 2})}</td>
            </tr>
        `;
    });

    content += `
                </tbody>
            </table>
        </div>
    `;

    $('#projectDetailsContent').html(content);
    $('#projectDetailsModal').modal('show');
}
</script>
@endpush

@push('styles')
<style>
/* ... existing styles ... */

/* New styles for the data tables */
.table {
    color: #fff;
    background-color: #1e293b;
    border-color: #334155;
}

.table thead th {
    border-bottom: 2px solid #334155;
    color: #94a3b8;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.875rem;
}

.table tbody td {
    border-color: #334155;
    vertical-align: middle;
}

.table-hover tbody tr:hover {
    background-color: #334155;
}

.table-primary {
    background-color: #1e40af !important;
    color: #fff;
}

.table-primary td {
    border-color: #2563eb;
}

.text-end {
    text-align: right !important;
}

/* Chart container styles */
#projectTypeChart, #provinceChart {
    background: #1e293b;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

/* Card styles */
.card {
    background-color: #1e293b;
    border: 1px solid #334155;
}

.card-header {
    background-color: #1e293b;
    border-bottom: 1px solid #334155;
    padding: 1rem;
}

.card-title {
    color: #fff;
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.card-body {
    padding: 1.5rem;
}
</style>
@endpush
@endsection

