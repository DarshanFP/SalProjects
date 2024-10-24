<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">IV. Strengths and Weaknesses of Business Initiative</h4>
    </div>
    <div class="card-body">

        <!-- Strengths Section -->
        <div class="mb-3">
            <label for="strengths" class="form-label">Strengths:</label>
            <div id="strengths-container">
                <textarea name="strengths[0]" class="form-control" rows="3" placeholder="Enter strengths" style="background-color: #202ba3;"></textarea>
            </div>
        </div>
        <!-- Add/Remove Strengths Buttons -->
        <button type="button" id="add-strength" class="btn btn-primary">Add Strength</button>
        <button type="button" id="remove-strength" class="btn btn-danger">Remove Strength</button>

        <!-- Weaknesses Section -->
        <div class="mt-4 mb-3">
            <label for="weaknesses" class="form-label">Weaknesses:</label>
            <div id="weaknesses-container">
                <textarea name="weaknesses[0]" class="form-control" rows="3" placeholder="Enter weaknesses" style="background-color: #202ba3;"></textarea>
            </div>
        </div>
        <!-- Add/Remove Weaknesses Buttons -->
        <button type="button" id="add-weakness" class="btn btn-primary">Add Weakness</button>
        <button type="button" id="remove-weakness" class="btn btn-danger">Remove Weakness</button>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let strengthIndex = 1;
        let weaknessIndex = 1;

        // Strengths Add/Remove functionality
        const strengthsContainer = document.getElementById('strengths-container');
        document.getElementById('add-strength').addEventListener('click', function () {
            const strengthTextarea = document.createElement('textarea');
            strengthTextarea.name = `strengths[${strengthIndex}]`;
            strengthTextarea.className = 'form-control mt-2';
            strengthTextarea.rows = 3;
            strengthTextarea.placeholder = 'Enter strengths';
            strengthTextarea.style.backgroundColor = '#202ba3';
            strengthsContainer.appendChild(strengthTextarea);
            strengthIndex++;
        });
        document.getElementById('remove-strength').addEventListener('click', function () {
            if (strengthsContainer.children.length > 1) {
                strengthsContainer.removeChild(strengthsContainer.lastElementChild);
            }
        });

        // Weaknesses Add/Remove functionality
        const weaknessesContainer = document.getElementById('weaknesses-container');
        document.getElementById('add-weakness').addEventListener('click', function () {
            const weaknessTextarea = document.createElement('textarea');
            weaknessTextarea.name = `weaknesses[${weaknessIndex}]`;
            weaknessTextarea.className = 'form-control mt-2';
            weaknessTextarea.rows = 3;
            weaknessTextarea.placeholder = 'Enter weaknesses';
            weaknessTextarea.style.backgroundColor = '#202ba3';
            weaknessesContainer.appendChild(weaknessTextarea);
            weaknessIndex++;
        });
        document.getElementById('remove-weakness').addEventListener('click', function () {
            if (weaknessesContainer.children.length > 1) {
                weaknessesContainer.removeChild(weaknessesContainer.lastElementChild);
            }
        });
    });
</script>
