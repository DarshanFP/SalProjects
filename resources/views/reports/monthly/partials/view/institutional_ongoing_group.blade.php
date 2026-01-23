{{-- resources/views/reports/monthly/partials/view/institutional_ongoing_group.blade.php --}}
<!-- Age Profile Section (Show Partial) -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>2. Age Profile of Children in the Institution</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Age Group</th>
                    <th>Education</th>
                    <th>Up to Previous Year</th>
                    <th>Present Academic Year</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $ageGroups = [
                        'Children below 5 years' => 'below_5',
                        'Children between 6 to 10 years' => '6_10',
                        'Children between 11 to 15 years' => '11_15',
                        '16 and above' => '16_above',
                    ];

                    $ageProfilesGrouped = $ageProfiles->groupBy('age_group');
                @endphp

                @foreach ($ageGroups as $ageGroup => $prefix)
                    @php
                        $ageGroupData = $ageProfilesGrouped->get($ageGroup, collect());
                        $ageGroupEntries = $ageGroupData->where('education', '!=', 'Total')->values();
                        $totalEntry = $ageGroupData->where('education', 'Total')->first();
                    @endphp

                    <!-- First row -->
                    @if($ageGroupEntries->isNotEmpty())
                        @foreach ($ageGroupEntries as $entry)
                            <tr>
                                <td>{{ $ageGroup }}</td>
                                <td>{{ $entry->education }}</td>
                                <td class="report-cell-entered">{{ $entry->up_to_previous_year }}</td>
                                <td class="report-cell-entered">{{ $entry->present_academic_year }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>{{ $ageGroup }}</td>
                            <td colspan="3">No data available</td>
                        </tr>
                    @endif

                    <!-- Total row for this age group -->
                    @if($totalEntry)
                        <tr class="total-row">
                            <td style="text-align: right;" colspan="2"><strong>Total {{ $ageGroup }}</strong></td>
                            <td class="report-cell-entered">{{ $totalEntry->up_to_previous_year }}</td>
                            <td class="report-cell-entered">{{ $totalEntry->present_academic_year }}</td>
                        </tr>
                    @endif
                @endforeach

                @php
                    $grandTotalEntry = $ageProfiles->where('age_group', 'All Categories')->where('education', 'Grand Total')->first();
                @endphp

                <!-- Grand Total -->
                @if($grandTotalEntry)
                    <tr class="total-row">
                        <td style="text-align: right;" colspan="2"><strong>Grand Total</strong></td>
                        <td class="report-cell-entered">{{ $grandTotalEntry->up_to_previous_year }}</td>
                        <td class="report-cell-entered">{{ $grandTotalEntry->present_academic_year }}</td>
                    </tr>
                @else
                    <tr class="total-row">
                        <td style="text-align: right;" colspan="2"><strong>Grand Total</strong></td>
                        <td class="report-cell-entered" colspan="2">No data available</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<style>
    .table th,
    .table td {
        vertical-align: middle;
        text-align: center;
        padding: 0.375rem; /* Adjust padding as necessary */
    }

    .table th {
        white-space: normal; /* Allow text wrapping in the header */
    }

    .total-row td {
        font-weight: bold;
        background-color: #1f2ba4; /* Light grey for total rows */
    }
</style>
