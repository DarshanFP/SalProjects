{{-- resources/views/projects/partials/Edit/ILP/strength_weakness.blade.php--}}

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">IV. Strengths and Weaknesses of Business Initiative</h4>
    </div>
    <div class="card-body">

        <!-- Strengths Section -->
        <div class="mb-3">
            <label for="strengths" class="form-label">Strengths:</label>
            <div id="strengths-container">
                @forelse($strengths as $index => $strength)
                    <div class="mb-2">
                        <label class="form-label small"><strong>Strength {{ $index + 1 }}:</strong></label>
                        <textarea name="strengths[{{ $index }}]" class="form-control sustainability-textarea" rows="3" placeholder="Enter strengths">{{ $strength }}</textarea>
                    </div>
                @empty
                    <div class="mb-2">
                        <label class="form-label small"><strong>Strength 1:</strong></label>
                        <textarea name="strengths[0]" class="form-control sustainability-textarea" rows="3" placeholder="Enter strengths"></textarea>
                    </div>
                @endforelse
            </div>
        </div>
        <!-- Add/Remove Strengths Buttons -->
        <button type="button" id="add-strength" class="btn btn-primary">Add Strength</button>
        <button type="button" id="remove-strength" class="btn btn-danger">Remove Strength</button>

        <!-- Weaknesses Section -->
        <div class="mt-4 mb-3">
            <label for="weaknesses" class="form-label">Weaknesses:</label>
            <div id="weaknesses-container">
                @forelse($weaknesses as $index => $weakness)
                    <div class="mb-2">
                        <label class="form-label small"><strong>Weakness {{ $index + 1 }}:</strong></label>
                        <textarea name="weaknesses[{{ $index }}]" class="form-control sustainability-textarea" rows="3" placeholder="Enter weaknesses">{{ $weakness }}</textarea>
                    </div>
                @empty
                    <div class="mb-2">
                        <label class="form-label small"><strong>Weakness 1:</strong></label>
                        <textarea name="weaknesses[0]" class="form-control sustainability-textarea" rows="3" placeholder="Enter weaknesses"></textarea>
                    </div>
                @endforelse
            </div>
        </div>
        <!-- Add/Remove Weaknesses Buttons -->
        <button type="button" id="add-weakness" class="btn btn-primary">Add Weakness</button>
        <button type="button" id="remove-weakness" class="btn btn-danger">Remove Weakness</button>

    </div>
</div>

<script>
    (function() {
        document.addEventListener('DOMContentLoaded', function () {
            let strengthIndex = {{ count($strengths ?? [0]) }};
            let weaknessIndex = {{ count($weaknesses ?? [0]) }};

            // Strengths Add/Remove functionality
            const strengthsContainer = document.getElementById('strengths-container');
            document.getElementById('add-strength').addEventListener('click', function () {
                const strengthDiv = document.createElement('div');
                strengthDiv.className = 'mb-2';
                const label = document.createElement('label');
                label.className = 'form-label small';
                label.innerHTML = `<strong>Strength ${strengthIndex + 1}:</strong>`;
                const strengthTextarea = document.createElement('textarea');
                strengthTextarea.name = `strengths[${strengthIndex}]`;
                strengthTextarea.className = 'form-control sustainability-textarea';
                strengthTextarea.rows = 3;
                strengthTextarea.placeholder = 'Enter strengths';
                strengthTextarea.style.backgroundColor = '#202ba3';
                strengthDiv.appendChild(label);
                strengthDiv.appendChild(strengthTextarea);
                strengthsContainer.appendChild(strengthDiv);

                // Initialize auto-resize for newly added textarea using global function
                if (typeof window.initTextareaAutoResize === 'function') {
                    window.initTextareaAutoResize(strengthTextarea);
                }

                strengthIndex++;
                reindexStrengths();
            });
            document.getElementById('remove-strength').addEventListener('click', function () {
                if (strengthsContainer.children.length > 1) {
                    strengthsContainer.removeChild(strengthsContainer.lastElementChild);
                    strengthIndex--;
                    reindexStrengths();
                }
            });

            function reindexStrengths() {
                const strengthDivs = strengthsContainer.querySelectorAll('div.mb-2');
                strengthDivs.forEach((div, index) => {
                    const label = div.querySelector('label');
                    if (label) {
                        label.innerHTML = `<strong>Strength ${index + 1}:</strong>`;
                    }
                    const textarea = div.querySelector('textarea');
                    if (textarea) {
                        textarea.name = `strengths[${index}]`;
                    }
                });
            }

            // Weaknesses Add/Remove functionality
            const weaknessesContainer = document.getElementById('weaknesses-container');
            document.getElementById('add-weakness').addEventListener('click', function () {
                const weaknessDiv = document.createElement('div');
                weaknessDiv.className = 'mb-2';
                const label = document.createElement('label');
                label.className = 'form-label small';
                label.innerHTML = `<strong>Weakness ${weaknessIndex + 1}:</strong>`;
                const weaknessTextarea = document.createElement('textarea');
                weaknessTextarea.name = `weaknesses[${weaknessIndex}]`;
                weaknessTextarea.className = 'form-control sustainability-textarea';
                weaknessTextarea.rows = 3;
                weaknessTextarea.placeholder = 'Enter weaknesses';
                weaknessTextarea.style.backgroundColor = '#202ba3';
                weaknessDiv.appendChild(label);
                weaknessDiv.appendChild(weaknessTextarea);
                weaknessesContainer.appendChild(weaknessDiv);

                // Initialize auto-resize for newly added textarea using global function
                if (typeof window.initTextareaAutoResize === 'function') {
                    window.initTextareaAutoResize(weaknessTextarea);
                }

                weaknessIndex++;
                reindexWeaknesses();
            });
            document.getElementById('remove-weakness').addEventListener('click', function () {
                if (weaknessesContainer.children.length > 1) {
                    weaknessesContainer.removeChild(weaknessesContainer.lastElementChild);
                    weaknessIndex--;
                    reindexWeaknesses();
                }
            });

            function reindexWeaknesses() {
                const weaknessDivs = weaknessesContainer.querySelectorAll('div.mb-2');
                weaknessDivs.forEach((div, index) => {
                    const label = div.querySelector('label');
                    if (label) {
                        label.innerHTML = `<strong>Weakness ${index + 1}:</strong>`;
                    }
                    const textarea = div.querySelector('textarea');
                    if (textarea) {
                        textarea.name = `weaknesses[${index}]`;
                    }
                });
            }
        });
    })();
</script>

<style>
    .form-control {

        color: white;
    }
    textarea {
        margin-bottom: 10px;
    }
    button {
        margin-right: 5px;
    }
</style>
