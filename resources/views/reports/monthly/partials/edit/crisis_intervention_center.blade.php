{{-- resources/views/reports/monthly/partials/edit/crisis_intervention_center.blade.php --}}
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
            $inmateData[$ageGroupKey][$status] = $profilesSummed->get($status) ?? '';
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
            $inmateData[$ageGroupKey]['other_count'] = '';
        }

        // Get total for the age group
        $total = $profilesSummed->get('total');
        $inmateData['totals'][$ageGroupKey] = $total ?? '';
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
    $inmateData['totals']['all_categories'] = $allCategoriesTotal ?? '';
@endphp


                @foreach ($ageGroups as $ageGroupName => $ageGroupKey)
                    <!-- Age Group Rows -->
                    @foreach ($statuses as $status)
                        @php $statusLower = strtolower($status); @endphp
                        <tr>
                            <td style="text-align: left;">{{ $ageGroupName }}</td>
                            <td>{{ ucfirst($statusLower) }}</td>
                            <td>
                                <input type="number" name="inmates[{{ $ageGroupKey }}][{{ $statusLower }}]" class="form-control" value="{{ $inmateData[$ageGroupKey][$statusLower] ?? '' }}" oninput="updateCounts()">
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="text-align: left;">{{ $ageGroupName }}</td>
                        <td>
                            <input type="text" name="inmates[{{ $ageGroupKey }}][other_status]" class="form-control" placeholder="If any other, mention here" value="{{ $inmateData[$ageGroupKey]['other_status'] ?? '' }}">
                        </td>
                        <td>
                            <input type="number" name="inmates[{{ $ageGroupKey }}][other_count]" class="form-control" value="{{ $inmateData[$ageGroupKey]['other_count'] ?? '' }}" oninput="updateCounts()">
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right;" colspan="2"><strong>Total {{ $ageGroupName }}</strong></td>
                        <td>
                            <input type="number" name="totals[{{ $ageGroupKey }}]" class="form-control" value="{{ $inmateData['totals'][$ageGroupKey] ?? '' }}" readonly>
                        </td>
                    </tr>
                @endforeach

                <!-- Grand Total -->
                <tr>
                    <td style="text-align: left;" colspan="2"><strong>Total</strong></td>
                    <td>
                        <input type="number" name="totals[all_categories]" class="form-control" value="{{ $inmateData['totals']['all_categories'] ?? '' }}" readonly>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    function updateCounts() {
        const ageGroups = ['children_below_18', 'women_18_30', 'women_31_50', 'women_above_50'];
        let grandTotal = 0;

        ageGroups.forEach(ageGroup => {
            let groupTotal = 0;

            document.querySelectorAll(`input[name^="inmates[${ageGroup}]"]`).forEach(input => {
                if (!input.name.includes('other_status')) {
                    groupTotal += parseInt(input.value) || 0;
                }
            });

            document.querySelector(`input[name="totals[${ageGroup}]"]`).value = groupTotal;
            grandTotal += groupTotal;
        });

        document.querySelector('input[name="totals[all_categories]"]').value = grandTotal;
    }

    // Call the function on page load to initialize the totals
    document.addEventListener('DOMContentLoaded', function() {
        updateCounts();
    });
</script>
