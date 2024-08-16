<!-- Trainees Profile Section -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>2. Information about the trainees</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="text-align: left;">Education of trainees</th>
                    <th>Number</th>
                </tr>
            </thead>
            <tbody>
                <!-- Education of trainee -->
                <tr>
                    <td style="text-align: left;">Below 9th standard</td>
                    <td><input type="number" name="education[below_9]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">10th class failed</td>
                    <td><input type="number" name="education[class_10_fail]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">10th class passed</td>
                    <td><input type="number" name="education[class_10_pass]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Intermediate</td>
                    <td><input type="number" name="education[intermediate]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td style="text-align: left;">Intermediate and above</td>
                    <td><input type="number" name="education[above_intermediate]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <tr>
                    <td><input type="text" name="education[other]" class="form-control" placeholder="If any other, mention here"></td>
                    <td><input type="number" name="education[other_count]" class="form-control" oninput="updateCounts()"></td>
                </tr>
                <!-- Total -->
                <tr>
                    <td style="text-align: left;"><strong>Total</strong></td>
                    <td><input type="number" name="education[total]" class="form-control" readonly></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    // trainee education counter
    function updateCounts() {
        // Get all input elements
        const below9 = document.querySelector('input[name="education[below_9]"]').value;
        const class10Fail = document.querySelector('input[name="education[class_10_fail]"]').value;
        const class10Pass = document.querySelector('input[name="education[class_10_pass]"]').value;
        const intermediate = document.querySelector('input[name="education[intermediate]"]').value;
        const aboveIntermediate = document.querySelector('input[name="education[above_intermediate]"]').value;
        const otherEducation = document.querySelector('input[name="education[other_count]"]').value;

        // Parse values to integers and handle empty inputs
        const total = (parseInt(below9) || 0) +
                      (parseInt(class10Fail) || 0) +
                      (parseInt(class10Pass) || 0) +
                      (parseInt(intermediate) || 0) +
                      (parseInt(aboveIntermediate) || 0) +
                      (parseInt(otherEducation) || 0);

        // Set the total value
        document.querySelector('input[name="education[total]"]').value = total;
    }
</script>

<style>
    .table th, .table td {
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
