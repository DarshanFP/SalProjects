@php
    use App\Models\Reports\Monthly\DPAccountDetail;

    $budgetData = $budgetOverviewData ?? [];
    $context = request('budget_context', 'combined');

    // Get data for selected context
    if ($context === 'coordinator_hierarchy') {
        $contextData = $budgetData['coordinator_hierarchy'] ?? [];
    } elseif ($context === 'direct_team') {
        $contextData = $budgetData['direct_team'] ?? [];
    } else {
        $contextData = $budgetData['combined'] ?? [];
    }

    $total = $contextData['total'] ?? [
        'budget' => 0,
        'approved_expenses' => 0,
        'unapproved_expenses' => 0,
        'remaining' => 0,
        'utilization' => 0
    ];

    $byProjectType = $contextData['by_project_type'] ?? [];
    $byProvince = $contextData['by_province'] ?? [];
    $byCenter = $contextData['by_center'] ?? [];
    $byCoordinator = $contextData['by_coordinator'] ?? [];

    // Calculate percentages for progress bar
    $totalBudget = $total['budget'] ?? 0;
    $approvedExpenses = $total['approved_expenses'] ?? 0;
    $unapprovedExpenses = $total['unapproved_expenses'] ?? 0;
    $totalRemaining = $total['remaining'] ?? 0;

    $approvedPercent = $totalBudget > 0 ? ($approvedExpenses / $totalBudget) * 100 : 0;
    $unapprovedPercent = $totalBudget > 0 ? ($unapprovedExpenses / $totalBudget) * 100 : 0;
    $remainingPercent = max(0, min(100, 100 - $approvedPercent));
@endphp

<div class="card mb-4 widget-card" data-widget-id="budget-overview">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Budget Overview</h5>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="budget-overview" title="Minimize">−</button>
        </div>
    </div>
    <div class="card-body widget-content">
        {{-- Context Tabs --}}
        <ul class="nav nav-tabs mb-3" id="budgetContextTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $context === 'coordinator_hierarchy' ? 'active' : '' }}"
                        id="coordinator-hierarchy-budget-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#coordinator-hierarchy-budget"
                        type="button"
                        role="tab"
                        onclick="setBudgetContext('coordinator_hierarchy')">
Coordinator Hierarchy
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $context === 'direct_team' ? 'active' : '' }}"
                        id="direct-team-budget-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#direct-team-budget"
                        type="button"
                        role="tab"
                        onclick="setBudgetContext('direct_team')">
Direct Team
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $context === 'combined' || !$context ? 'active' : '' }}"
                        id="combined-budget-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#combined-budget"
                        type="button"
                        role="tab"
                        onclick="setBudgetContext('combined')">
Combined
                </button>
            </li>
        </ul>

        <div class="tab-content" id="budgetContextTabContent">
            {{-- Coordinator Hierarchy Tab --}}
            <div class="tab-pane fade {{ $context === 'coordinator_hierarchy' ? 'show active' : '' }}" id="coordinator-hierarchy-budget" role="tabpanel">
                @include('general.widgets.partials.budget-overview-content', [
                    'context' => 'coordinator_hierarchy',
                    'budgetData' => $budgetData['coordinator_hierarchy'] ?? [],
                    'contextLabel' => 'Coordinator Hierarchy'
                ])
            </div>

            {{-- Direct Team Tab --}}
            <div class="tab-pane fade {{ $context === 'direct_team' ? 'show active' : '' }}" id="direct-team-budget" role="tabpanel">
                @include('general.widgets.partials.budget-overview-content', [
                    'context' => 'direct_team',
                    'budgetData' => $budgetData['direct_team'] ?? [],
                    'contextLabel' => 'Direct Team'
                ])
            </div>

            {{-- Combined Tab --}}
            <div class="tab-pane fade {{ ($context === 'combined' || !$context) ? 'show active' : '' }}" id="combined-budget" role="tabpanel">
                @include('general.widgets.partials.budget-overview-content', [
                    'context' => 'combined',
                    'budgetData' => $budgetData['combined'] ?? [],
                    'contextLabel' => 'Combined'
                ])
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function setBudgetContext(context) {
    const url = new URL(window.location.href);
    url.searchParams.set('budget_context', context);
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Initialize context tabs
    const contextTabList = document.querySelectorAll('#budgetContextTabs button[data-bs-toggle="tab"]');
    contextTabList.forEach(triggerEl => {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener('click', event => {
            event.preventDefault();
            tabTrigger.show();
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    });

    // Province-Center Filtering
    const centersMap = @json($centersMap ?? []);
    const provinceSelect = document.getElementById('budget_filter_province');
    const centerSelect = document.getElementById('budget_filter_center');

    if (provinceSelect && centerSelect && Object.keys(centersMap).length > 0) {
        function filterCentersByProvince() {
            const selectedProvince = provinceSelect.value.toUpperCase();
            const currentCenter = centerSelect.value;

            // Clear existing options except "All Centers"
            centerSelect.innerHTML = '<option value="">All Centers</option>';

            if (selectedProvince && centersMap[selectedProvince]) {
                // Add centers for selected province
                centersMap[selectedProvince].forEach(function(center) {
                    const option = document.createElement('option');
                    option.value = center;
                    option.textContent = center;
                    if (currentCenter === center) {
                        option.selected = true;
                    }
                    centerSelect.appendChild(option);
                });
            } else {
                // If no province selected, show all centers from database
                @if(isset($centers))
                    @foreach($centers as $center)
                        const option = document.createElement('option');
                        option.value = '{{ $center }}';
                        option.textContent = '{{ $center }}';
                        @if(request('center') == $center)
                            option.selected = true;
                        @endif
                        centerSelect.appendChild(option);
                    @endforeach
                @endif
            }
        }

        // Filter centers when province changes
        provinceSelect.addEventListener('change', filterCentersByProvince);

        // Initialize on page load
        filterCentersByProvince();
    }
});
</script>
@endpush
