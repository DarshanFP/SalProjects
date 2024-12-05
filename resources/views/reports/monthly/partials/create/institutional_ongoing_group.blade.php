{{-- resources/views/reports/monthly/partials/create/institutional_ongoing_group.blade.php --}}
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
                <!-- Children below 5 years -->
                <tr>
                    <td>Children below 5 years</td>
                    <td>
                        <select name="education[]" class="form-control">
                            <option value="Bridge course">Bridge course</option>
                            <option value="If any other, mention here">If any other, mention here</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="up_to_previous_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="present_academic_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                </tr>
                <tr>
                    <td>Children below 5 years</td>
                    <td>
                        <input type="text" name="education[]" class="form-control" placeholder="If any other, mention here" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="up_to_previous_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="present_academic_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                </tr>
                <!-- Total row for Children below 5 years -->
                <tr class="total-row">
                    <td style="text-align: right;" colspan="2"><strong>Total below 5 years</strong></td>
                    <td><input type="number" name="total_up_to_previous_below_5" class="form-control" readonly></td>
                    <td><input type="number" name="total_present_academic_below_5" class="form-control" readonly></td>
                </tr>
                <!-- Children between 6 to 10 years -->
                <tr>
                    <td>Children between 6 to 10 years</td>
                    <td>
                        <select name="education[]" class="form-control">
                            <option value="Primary school">Primary school</option>
                            <option value="If any other, mention here">If any other, mention here</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="up_to_previous_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="present_academic_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                </tr>
                <tr>
                    <td>Children between 6 to 10 years</td>
                    <td>
                        <input type="text" name="education[]" class="form-control" placeholder="If any other, mention here" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="up_to_previous_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="present_academic_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                </tr>
                <!-- Total row for Children between 6 to 10 years -->
                <tr class="total-row">
                    <td style="text-align: right;" colspan="2"><strong>Total between 6 to 10 years</strong></td>
                    <td><input type="number" name="total_up_to_previous_6_10" class="form-control" readonly></td>
                    <td><input type="number" name="total_present_academic_6_10" class="form-control" readonly></td>
                </tr>
                <!-- Children between 11 to 15 years -->
                <tr>
                    <td>Children between 11 to 15 years</td>
                    <td>
                        <select name="education[]" class="form-control">
                            <option value="Secondary school">Secondary school</option>
                            <option value="If any other, mention here">If any other, mention here</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="up_to_previous_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="present_academic_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                </tr>
                <tr>
                    <td>Children between 11 to 15 years</td>
                    <td>
                        <input type="text" name="education[]" class="form-control" placeholder="If any other, mention here" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="up_to_previous_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="present_academic_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                </tr>
                <!-- Total row for Children between 11 to 15 years -->
                <tr class="total-row">
                    <td style="text-align: right;" colspan="2"><strong>Total between 11 to 15 years</strong></td>
                    <td><input type="number" name="total_up_to_previous_11_15" class="form-control" readonly></td>
                    <td><input type="number" name="total_present_academic_11_15" class="form-control" readonly></td>
                </tr>
                <!-- 16 and above -->
                <tr>
                    <td>16 and above</td>
                    <td>
                        <select name="education[]" class="form-control">
                            <option value="Undergraduate">Undergraduate</option>
                            <option value="If any other, mention here">If any other, mention here</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="up_to_previous_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="present_academic_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                </tr>
                <tr>
                    <td>16 and above</td>
                    <td>
                        <input type="text" name="education[]" class="form-control" placeholder="If any other, mention here" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="up_to_previous_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                    <td>
                        <input type="number" name="present_academic_year[]" class="form-control" oninput="calculateAgeTotals()" style="background-color: #202ba3;">
                    </td>
                </tr>
                <!-- Total row for 16 and above -->
                <tr class="total-row">
                    <td style="text-align: right;" colspan="2"><strong>Total 16 and above</strong></td>
                    <td><input type="number" name="total_up_to_previous_16_above" class="form-control" readonly></td>
                    <td><input type="number" name="total_present_academic_16_above" class="form-control" readonly></td>
                </tr>
                <!-- Grand Total -->
                <tr class="total-row">
                    <td style="text-align: right;" colspan="2"><strong>Grand Total</strong></td>
                    <td><input type="number" name="grand_total_up_to_previous" class="form-control" readonly></td>
                    <td><input type="number" name="grand_total_present_academic" class="form-control" readonly></td>
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
