{{-- resources/views/projects/partials/CCI/age_profile.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Age profile of Children in the institution</h4>
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
                            <td><input type="number" name="education_below_5_bridge_course_prev_year" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="education_below_5_bridge_course_current_year" class="form-control" style="background-color: #202ba3;"></td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">Kindergarten</td>
                            <td><input type="number" name="education_below_5_kindergarten_prev_year" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="education_below_5_kindergarten_current_year" class="form-control" style="background-color: #202ba3;"></td>
                        </tr>
                        <tr>
                            <td><input type="text" name="education_below_5_other_specify" class="form-control" placeholder="Specify other" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="education_below_5_other_prev_year" class="form-control"  style="background-color: #202ba3;"></td>
                            <td><input type="text" name="education_below_5_other_current_year" class="form-control"  style="background-color: #202ba3;"></td>
                        </tr>

                        <!-- Children between 6 to 10 years -->
                        <tr>
                            <td style="text-align: left;" rowspan="3">Children between 6 to 10 years</td>
                            <td style="text-align: left;">Primary school</td>
                            <td><input type="number" name="education_6_10_primary_school_prev_year" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="education_6_10_primary_school_current_year" class="form-control" style="background-color: #202ba3;"></td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">Bridge course</td>
                            <td><input type="number" name="education_6_10_bridge_course_prev_year" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="education_6_10_bridge_course_current_year" class="form-control" style="background-color: #202ba3;"></td>
                        </tr>
                        <tr>
                            <td><input type="text" name="education_6_10_other_specify" class="form-control" placeholder="Specify other" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="education_6_10_other_prev_year" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="education_6_10_other_current_year" class="form-control" style="background-color: #202ba3;"></td>
                        </tr>

                        <!-- Children between 11 to 15 years -->
                        <tr>
                            <td style="text-align: left;" rowspan="3">Children between 11 to 15 years</td>
                            <td style="text-align: left;">Secondary school</td>
                            <td><input type="number" name="education_11_15_secondary_school_prev_year" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="education_11_15_secondary_school_current_year" class="form-control" style="background-color: #202ba3;"></td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">High school</td>
                            <td><input type="number" name="education_11_15_high_school_prev_year" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="education_11_15_high_school_current_year" class="form-control" style="background-color: #202ba3;"></td>
                        </tr>
                        <tr>
                            <td><input type="text" name="education_11_15_other_specify" class="form-control" placeholder="Specify other" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="education_11_15_other_prev_year" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="education_11_15_other_current_year" class="form-control" style="background-color: #202ba3;"></td>
                        </tr>

                        <!-- 16 and above -->
                        <tr>
                            <td  style="text-align: left;" rowspan="3">>16 and above</td>
                            <td  style="text-align: left;" >Undergraduate</td>
                            <td><input type="number" name="education_16_above_undergraduate_prev_year" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="education_16_above_undergraduate_current_year" class="form-control" style="background-color: #202ba3;"></td>
                        </tr>
                        <tr>
                            <td  style="text-align: left;" >Technical/Vocational education</td>
                            <td><input type="number" name="education_16_above_technical_vocational_prev_year" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="education_16_above_technical_vocational_current_year" class="form-control" style="background-color: #202ba3;"></td>
                        </tr>
                        <tr>
                            <td><input type="text" name="education_16_above_other_specify" class="form-control" placeholder="Specify other" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="education_16_above_other_prev_year" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="text" name="education_16_above_other_current_year" class="form-control"style="background-color: #202ba3;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
</div>

<!-- Styles to maintain consistency with the existing design and remove scroll/spinner -->
<style>
    .table td input, .table td textarea {
        width: 100%;
        box-sizing: border-box;
    }

    .table th, .table td {
        vertical-align: middle;
        text-align: center;
        padding: 0.5rem;
    }

    /* Remove spinner arrows for all number inputs */
    input[type='number']::-webkit-outer-spin-button,
    input[type='number']::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type='number'] {
        -moz-appearance: textfield; /* Firefox */
        appearance: textfield; /* Default */
    }
</style>