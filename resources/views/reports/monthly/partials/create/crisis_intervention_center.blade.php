{{-- resources/views/reports/monthly/partials/create/crisis_intervention_center.blade.php --}}
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
                <!-- Children below 18 yrs -->
                <tr>
                    <td style="text-align: left;">Children below 18 yrs</td>
                    <td>Unmarried</td>
                    <td><input type="number" name="inmates[children_below_18][unmarried]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Children below 18 yrs</td>
                    <td>Married</td>
                    <td><input type="number" name="inmates[children_below_18][married]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Children below 18 yrs</td>
                    <td>Divorcee</td>
                    <td><input type="number" name="inmates[children_below_18][divorcee]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Children below 18 yrs</td>
                    <td>Deserted</td>
                    <td><input type="number" name="inmates[children_below_18][deserted]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Children below 18 yrs</td>
                    <td><input type="text" name="inmates[children_below_18][other_status]" class="form-control" placeholder="If any other, mention here"></td>
                    <td><input type="number" name="inmates[children_below_18][other_count]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: right;" colspan="2"><strong>Total Children below 18 years </strong></td>
                    <td><input type="number" name="totals[children_below_18]" class="form-control" readonly></td>
                </tr>
                <!-- Women between 18 – 30 years -->
                <tr>
                    <td style="text-align: left;">Women between 18 – 30 years</td>
                    <td>Unmarried</td>
                    <td><input type="number" name="inmates[women_18_30][unmarried]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women between 18 – 30 years</td>
                    <td>Married</td>
                    <td><input type="number" name="inmates[women_18_30][married]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women between 18 – 30 years</td>
                    <td>Divorcee</td>
                    <td><input type="number" name="inmates[women_18_30][divorcee]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women between 18 – 30 years</td>
                    <td>Deserted</td>
                    <td><input type="number" name="inmates[women_18_30][deserted]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women between 18 – 30 years</td>
                    <td><input type="text" name="inmates[women_18_30][other_status]" class="form-control" placeholder="If any other, mention here"></td>
                    <td><input type="number" name="inmates[women_18_30][other_count]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: right;" colspan="2"><strong>Total Women between 18 – 30 years </strong></td>
                    <td><input type="number" name="totals[women_18_30]" class="form-control" readonly></td>
                </tr>
                <!-- Women between 31 – 50 years -->
                <tr>
                    <td style="text-align: left;">Women between 31 – 50 years</td>
                    <td>Unmarried</td>
                    <td><input type="number" name="inmates[women_31_50][unmarried]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women between 31 – 50 years</td>
                    <td>Married</td>
                    <td><input type="number" name="inmates[women_31_50][married]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women between 31 – 50 years</td>
                    <td>Divorcee</td>
                    <td><input type="number" name="inmates[women_31_50][divorcee]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women between 31 – 50 years</td>
                    <td>Deserted</td>
                    <td><input type="number" name="inmates[women_31_50][deserted]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women between 31 – 50 years</td>
                    <td><input type="text" name="inmates[women_31_50][other_status]" class="form-control" placeholder="If any other, mention here"></td>
                    <td><input type="number" name="inmates[women_31_50][other_count]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: right;" colspan="2"><strong>Total Women between 31 – 50 years</strong></td>
                    <td><input type="number" name="totals[women_31_50]" class="form-control" readonly></td>
                </tr>
                <!-- Women above 50 -->
                <tr>
                    <td style="text-align: left;">Women above 50</td>
                    <td>Unmarried</td>
                    <td><input type="number" name="inmates[women_above_50][unmarried]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women above 50</td>
                    <td>Married</td>
                    <td><input type="number" name="inmates[women_above_50][married]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women above 50</td>
                    <td>Divorcee</td>
                    <td><input type="number" name="inmates[women_above_50][divorcee]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women above 50</td>
                    <td>Deserted</td>
                    <td><input type="number" name="inmates[women_above_50][deserted]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Women above 50</td>
                    <td><input type="text" name="inmates[women_above_50][other_status]" class="form-control" placeholder="If any other, mention here"></td>
                    <td><input type="number" name="inmates[women_above_50][other_count]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: right;" colspan="2"><strong>Total Women above 50</strong></td>
                    <td><input type="number" name="totals[women_above_50]" class="form-control" readonly></td>
                </tr>
                <!-- Total -->
                <tr>
                    <td style="text-align: left;" colspan="2"><strong>Total</strong></td>
                    <td><input type="number" name="totals[all_categories]" class="form-control" readonly></td>
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
</script>

