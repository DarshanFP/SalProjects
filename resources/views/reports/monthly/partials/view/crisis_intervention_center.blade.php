{{-- resources/views/reports/monthly/partials/view/crisis_intervention_center.blade.php --}}
<!-- Inmates Profile Section -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>2. Profile of Inmates for the Last Four Months</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="text-align: left;">Age Category</th>
                    <th>Status</th>
                    <th>Number</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $ageGroups = [
                        'Children below 18 yrs' => 'children_below_18',
                        'Women between 18 – 30 years' => 'women_18_30',
                        'Women between 31 – 50 years' => 'women_31_50',
                        'Women above 50' => 'women_above_50',
                    ];

                    $statuses = ['unmarried', 'married', 'divorcee', 'deserted'];
                    $statusesLower = array_map('strtolower', $statuses);
                    $inmateData = [];

                    // Group inmate profiles by age category
                    $inmateProfilesGrouped = $inmateProfiles->groupBy('age_category');

                    // Prepare inmate data for easy access
                    foreach ($ageGroups as $ageGroup => $ageGroupKey) {
                        $profiles = $inmateProfilesGrouped->get($ageGroup, collect());

                        // Group profiles by trimmed and lowercased status
                        $profilesGroupedByStatus = $profiles->groupBy(function($item) {
                            return strtolower(trim($item->status));
                        });

                        // Sum numbers for each status
                        $profilesSummed = $profilesGroupedByStatus->map(function($items) {
                            return $items->sum('number');
                        });

                        // Initialize data for known statuses
                        foreach ($statusesLower as $status) {
                            $inmateData[$ageGroupKey][$status] = $profilesSummed->get($status) ?? 0;
                        }

                        // Handle 'other' status
                        $otherStatuses = $profilesSummed->except(array_merge($statusesLower, ['total']));
                        if ($otherStatuses->count() > 0) {
                            $otherStatus = $otherStatuses->keys()->first();
                            $otherCount = $otherStatuses->first();

                            $inmateData[$ageGroupKey]['other_status'] = $otherStatus;
                            $inmateData[$ageGroupKey]['other_count'] = $otherCount;
                        } else {
                            $inmateData[$ageGroupKey]['other_status'] = '';
                            $inmateData[$ageGroupKey]['other_count'] = 0;
                        }

                        // Get total for the age group
                        $total = $profilesSummed->get('total');
                        $inmateData['totals'][$ageGroupKey] = $total ?? 0;
                    }

                    // Get grand total
                    $allCategoriesTotal = $inmateProfilesGrouped->get('All Categories', collect())
                        ->groupBy(function($item) {
                            return strtolower(trim($item->status));
                        })
                        ->map(function($items) {
                            return $items->sum('number');
                        })
                        ->get('total');
                    $inmateData['totals']['all_categories'] = $allCategoriesTotal ?? 0;
                @endphp

                @foreach ($ageGroups as $ageGroupName => $ageGroupKey)
                    <!-- Age Group Rows -->
                    @foreach ($statuses as $status)
                        @php $statusLower = strtolower($status); @endphp
                        <tr>
                            <td style="text-align: left;">{{ $ageGroupName }}</td>
                            <td>{{ ucfirst($statusLower) }}</td>
                            <td class="report-cell-entered">{{ $inmateData[$ageGroupKey][$statusLower] ?? 0 }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="text-align: left;">{{ $ageGroupName }}</td>
                        <td>{{ $inmateData[$ageGroupKey]['other_status'] ?? '' }}</td>
                        <td class="report-cell-entered">{{ $inmateData[$ageGroupKey]['other_count'] ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;" colspan="2"><strong>Total {{ $ageGroupName }}</strong></td>
                        <td class="report-cell-entered">{{ $inmateData['totals'][$ageGroupKey] ?? 0 }}</td>
                    </tr>
                @endforeach

                <!-- Grand Total -->
                <tr>
                    <td style="text-align: left;" colspan="2"><strong>Grand Total</strong></td>
                    <td class="report-cell-entered">{{ $inmateData['totals']['all_categories'] ?? 0 }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
