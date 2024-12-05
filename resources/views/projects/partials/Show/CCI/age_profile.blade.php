{{-- resources/views/projects/partials/Show/CCI/age_profile.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Age Profile of Children in the Institution</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Age Category</th>
                        <th>Education</th>
                        <th>Up to Previous Year</th>
                        <th>Present Academic Year</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Children below 5 years -->
                    <tr>
                        <td style="text-align: left;" rowspan="3">Children below 5 years</td>
                        <td style="text-align: left;">Bridge course</td>
                        <td>{{ $ageProfile['education_below_5_bridge_course_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_below_5_bridge_course_current_year'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Kindergarten</td>
                        <td>{{ $ageProfile['education_below_5_kindergarten_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_below_5_kindergarten_current_year'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>{{ $ageProfile['education_below_5_other_specify'] ?? 'Other' }}</td>
                        <td>{{ $ageProfile['education_below_5_other_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_below_5_other_current_year'] ?? 'N/A' }}</td>
                    </tr>

                    <!-- Children between 6 to 10 years -->
                    <tr>
                        <td style="text-align: left;" rowspan="3">Children between 6 to 10 years</td>
                        <td style="text-align: left;">Primary school</td>
                        <td>{{ $ageProfile['education_6_10_primary_school_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_6_10_primary_school_current_year'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Bridge course</td>
                        <td>{{ $ageProfile['education_6_10_bridge_course_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_6_10_bridge_course_current_year'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>{{ $ageProfile['education_6_10_other_specify'] ?? 'Other' }}</td>
                        <td>{{ $ageProfile['education_6_10_other_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_6_10_other_current_year'] ?? 'N/A' }}</td>
                    </tr>

                    <!-- Children between 11 to 15 years -->
                    <tr>
                        <td style="text-align: left;" rowspan="3">Children between 11 to 15 years</td>
                        <td style="text-align: left;">Secondary school</td>
                        <td>{{ $ageProfile['education_11_15_secondary_school_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_11_15_secondary_school_current_year'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">High school</td>
                        <td>{{ $ageProfile['education_11_15_high_school_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_11_15_high_school_current_year'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>{{ $ageProfile['education_11_15_other_specify'] ?? 'Other' }}</td>
                        <td>{{ $ageProfile['education_11_15_other_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_11_15_other_current_year'] ?? 'N/A' }}</td>
                    </tr>

                    <!-- 16 and above -->
                    <tr>
                        <td style="text-align: left;" rowspan="3">16 and above</td>
                        <td style="text-align: left;">Undergraduate</td>
                        <td>{{ $ageProfile['education_16_above_undergraduate_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_16_above_undergraduate_current_year'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Technical/Vocational education</td>
                        <td>{{ $ageProfile['education_16_above_technical_vocational_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_16_above_technical_vocational_current_year'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>{{ $ageProfile['education_16_above_other_specify'] ?? 'Other' }}</td>
                        <td>{{ $ageProfile['education_16_above_other_prev_year'] ?? 'N/A' }}</td>
                        <td>{{ $ageProfile['education_16_above_other_current_year'] ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
