{{-- resources/views/reports/monthly/partials/edit/institutional_ongoing_group.blade.php --}}
<!-- Age Profile Section -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>2. Age Profile of Children in the Institution</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered" id="age-profile-table">
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
                        'Children below 5 years' => [
                            'prefix' => 'below_5',
                            'options' => ['Bridge course']
                        ],
                        'Children between 6 to 10 years' => [
                            'prefix' => '6_10',
                            'options' => ['Primary school']
                        ],
                        'Children between 11 to 15 years' => [
                            'prefix' => '11_15',
                            'options' => ['Secondary school']
                        ],
                        '16 and above' => [
                            'prefix' => '16_above',
                            'options' => ['Undergraduate']
                        ],
                    ];

                    $ageProfilesGrouped = $ageProfiles->groupBy('age_group');
                @endphp

                @foreach ($ageGroups as $ageGroup => $data)
                    @php
                        $prefix = $data['prefix'];
                        $options = $data['options'];
                        $ageGroupData = $ageProfilesGrouped->get($ageGroup, collect());
                        $ageGroupEntries = $ageGroupData->where('education', '!=', 'Total')->values();
                        $totalEntry = $ageGroupData->where('education', 'Total')->first();
                        $entry1 = $ageGroupEntries->get(0);
                        $entry2 = $ageGroupEntries->get(1);
                    @endphp

                    <!-- First row -->
                    <tr>
                        <td>{{ $ageGroup }}</td>
                        <td>
                            <select name="education[]" class="form-control">
                                @foreach ($options as $option)
                                    <option value="{{ $option }}" {{ (isset($entry1) && $entry1->education == $option) ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                                <option value="If any other, mention here" {{ (isset($entry1) && $entry1->education == 'If any other, mention here') ? 'selected' : '' }}>If any other, mention here</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="up_to_previous_year[]" class="form-control" value="{{ $entry1->up_to_previous_year ?? '' }}" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                        </td>
                        <td>
                            <input type="number" name="present_academic_year[]" class="form-control" value="{{ $entry1->present_academic_year ?? '' }}" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                        </td>
                    </tr>

                    <!-- Second row -->
                    <tr>
                        <td>{{ $ageGroup }}</td>
                        <td>
                            <input type="text" name="education[]" class="form-control" placeholder="If any other, mention here" value="{{ (isset($entry2) && !in_array($entry2->education, array_merge($options, ['If any other, mention here']))) ? $entry2->education : '' }}" style="background-color: #202ba3;">
                        </td>
                        <td>
                            <input type="number" name="up_to_previous_year[]" class="form-control" value="{{ $entry2->up_to_previous_year ?? '' }}" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                        </td>
                        <td>
                            <input type="number" name="present_academic_year[]" class="form-control" value="{{ $entry2->present_academic_year ?? '' }}" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                        </td>
                    </tr>

                    <!-- Total row for this age group -->
                    <tr class="total-row">
                        <td style="text-align: right;" colspan="2"><strong>Total {{ $ageGroup }}</strong></td>
                        <td><input type="number" name="total_up_to_previous_{{ $prefix }}" class="form-control" value="{{ $totalEntry->up_to_previous_year ?? '' }}" readonly></td>
                        <td><input type="number" name="total_present_academic_{{ $prefix }}" class="form-control" value="{{ $totalEntry->present_academic_year ?? '' }}" readonly></td>
                    </tr>

                @endforeach

                @php
                    $grandTotalEntry = $ageProfiles->where('age_group', 'All Categories')->where('education', 'Grand Total')->first();
                @endphp

                <!-- Grand Total -->
                <tr class="total-row">
                    <td style="text-align: right;" colspan="2"><strong>Grand Total</strong></td>
                    <td><input type="number" name="grand_total_up_to_previous" class="form-control" value="{{ $grandTotalEntry->up_to_previous_year ?? '' }}" readonly></td>
                    <td><input type="number" name="grand_total_present_academic" class="form-control" value="{{ $grandTotalEntry->present_academic_year ?? '' }}" readonly></td>
                </tr>

            </tbody>
        </table>
    </div>
</div>

<script>
    // Age Profile Totals Calculation
    function calculateAgeTotals() {
        const sections = [
            { prefix: 'below_5', startIndex: 0, rows: 2 },
            { prefix: '6_10', startIndex: 2, rows: 2 },
            { prefix: '11_15', startIndex: 4, rows: 2 },
            { prefix: '16_above', startIndex: 6, rows: 2 }
        ];

        let grandTotalUpToPrevious = 0;
        let grandTotalPresentAcademic = 0;

        sections.forEach(section => {
            let totalUpToPrevious = 0;
            let totalPresentAcademic = 0;

            for (let i = 0; i < section.rows; i++) {
                const upToPreviousValue = parseFloat(document.querySelectorAll('[name="up_to_previous_year[]"]')[section.startIndex + i].value) || 0;
                const presentAcademicValue = parseFloat(document.querySelectorAll('[name="present_academic_year[]"]')[section.startIndex + i].value) || 0;

                totalUpToPrevious += upToPreviousValue;
                totalPresentAcademic += presentAcademicValue;
            }

            document.querySelector(`[name="total_up_to_previous_${section.prefix}"]`).value = totalUpToPrevious;
            document.querySelector(`[name="total_present_academic_${section.prefix}"]`).value = totalPresentAcademic;

            grandTotalUpToPrevious += totalUpToPrevious;
            grandTotalPresentAcademic += totalPresentAcademic;
        });

        document.querySelector('[name="grand_total_up_to_previous"]').value = grandTotalUpToPrevious;
        document.querySelector('[name="grand_total_present_academic"]').value = grandTotalPresentAcademic;
    }

    // Call the function on page load to initialize the totals
    document.addEventListener('DOMContentLoaded', function() {
        calculateAgeTotals();
    });
</script>

<style>
    .table th,
    .table td {
        vertical-align: middle;
        text-align: center;
        padding: 0; /* Disable padding */
    }

    .table th {
        white-space: normal; /* Allow text wrapping in the header */
    }

    .table td input {
        width: 100%;
        box-sizing: border-box;
        -moz-appearance: textfield; /* Disable number input arrows */
        padding: 0.375rem 0.75rem; /* Adjust the padding of the input */
    }

    .table td input::-webkit-outer-spin-button,
    .table td input::-webkit-inner-spin-button {
        -webkit-appearance: none; /* Disable number input arrows */
        margin: 0;
    }

    .table-container {
        overflow-x: auto;
    }

    .fp-text-center1 {
        text-align: center;
        margin-bottom: 15px; /* Adjust the value as needed */
    }

    .fp-text-margin {
        margin-bottom: 15px; /* Adjust the value as needed */
    }
</style>
