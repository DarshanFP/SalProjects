{{-- resources/views/projects/partials/NPD/logical_framework.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Solution Analysis: Logical Framework - Next Phase</h4>
    </div>
    <div class="card-body" id="objectives-container-npd">
        <!-- Objective Template -->
        @foreach ($predecessorObjectives as $index => $objective)
        <div class="mb-3 objective-card">
            <div class="objective-header d-flex justify-content-between align-items-center">
                <h5>Objective {{ $loop->iteration }}</h5>
            </div>
            <textarea name="objectives[{{ $index }}][objective]" class="mb-3 form-control objective-description logical-textarea" rows="2" placeholder="Enter Objective">
                {{ $objective['objective'] }}
            </textarea>

            <div class="results-container">
                @foreach ($objective['results'] as $resultIndex => $result)
                <div class="mb-3 result-section">
                    <div class="result-header d-flex justify-content-between align-items-center">
                        <h6>Results / Outcome</h6>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeResult(this)">Remove Result</button>
                    </div>
                    <textarea name="objectives[{{ $index }}][results][{{ $resultIndex }}][result]" class="mb-3 form-control result-outcome logical-textarea" rows="2" placeholder="Enter Result">
                        {{ $result['result'] }}
                    </textarea>
                </div>
                @endforeach
                <button type="button" class="mb-3 btn btn-primary" onclick="addResult(this)">Add Result</button>
            </div>

            <div class="risks-container">
                @foreach ($objective['risks'] as $riskIndex => $risk)
                <div class="mb-3 risk-section">
                    <div class="risk-header d-flex justify-content-between align-items-center">
                        <h6>Risks</h6>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRisk(this)">Remove Risk</button>
                    </div>
                    <textarea name="objectives[{{ $index }}][risks][{{ $riskIndex }}][risk]" class="mb-3 form-control risk-description logical-textarea" rows="2" placeholder="Enter Risk">
                        {{ $risk['risk'] }}
                    </textarea>
                </div>
                @endforeach
                <button type="button" class="mb-3 btn btn-primary" onclick="addRisk(this)">Add Risk</button>
            </div>
        </div>
        @endforeach

        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-primary" onclick="addObjective()">Add Objective</button>
            <button type="button" class="btn btn-danger" onclick="removeLastObjective()">Remove Last Objective</button>
        </div>
    </div>
</div>

<style>
.logical-textarea {
    resize: vertical;
    min-height: 80px;
    height: auto;
    overflow-y: hidden;
    line-height: 1.5;
    padding: 8px 12px;
}

.logical-textarea:focus {
    overflow-y: auto;
}
</style>

<script>
// Auto-resize function
function autoResizeTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = (textarea.scrollHeight) + 'px';
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize existing textareas
    const textareas = document.querySelectorAll('#objectives-container-npd .logical-textarea');
    textareas.forEach(textarea => {
        autoResizeTextarea(textarea);
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });
});

// Override addResult, addRisk functions for NPD to ensure auto-resize
function addResult(button) {
    const resultTemplate = button.closest('.results-container').querySelector('.result-section').cloneNode(true);
    const newTextarea = resultTemplate.querySelector('textarea.result-outcome');
    newTextarea.value = '';
    button.closest('.results-container').insertBefore(resultTemplate, button);
    
    // Initialize auto-resize for new textarea
    if (newTextarea) {
        autoResizeTextarea(newTextarea);
        newTextarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    }
}

function addRisk(button) {
    const risksContainer = button.closest('.risks-container');
    const riskTemplate = risksContainer.querySelector('.risk-section').cloneNode(true);
    const newTextarea = riskTemplate.querySelector('textarea.risk-description');
    newTextarea.value = '';
    risksContainer.insertBefore(riskTemplate, button);
    
    // Initialize auto-resize for new textarea
    if (newTextarea) {
        autoResizeTextarea(newTextarea);
        newTextarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    }
}

function addObjective() {
    const container = document.getElementById('objectives-container-npd');
    const objectiveTemplate = container.querySelector('.objective-card').cloneNode(true);
    
    // Reset values
    objectiveTemplate.querySelectorAll('textarea').forEach(textarea => textarea.value = '');
    objectiveTemplate.querySelectorAll('input[type="checkbox"]').forEach(checkbox => checkbox.checked = false);
    
    // Update objective number
    const objectiveCount = container.querySelectorAll('.objective-card').length;
    objectiveTemplate.querySelector('h5').innerText = `Objective ${objectiveCount + 1}`;
    
    // Reset to single result and risk
    objectiveTemplate.querySelectorAll('.result-section:not(:first-child)').forEach(section => section.remove());
    objectiveTemplate.querySelectorAll('.risk-section:not(:first-child)').forEach(section => section.remove());
    
    // Update name attributes (simplified - you may need to adjust based on your naming scheme)
    const newIndex = objectiveCount;
    objectiveTemplate.querySelectorAll('textarea, input, select').forEach(element => {
        if (element.name) {
            element.name = element.name.replace(/objectives\[\d+\]/, `objectives[${newIndex}]`);
        }
    });
    
    // Insert before the buttons
    const buttonsDiv = container.querySelector('.d-flex');
    container.insertBefore(objectiveTemplate, buttonsDiv);
    
    // Initialize auto-resize for all textareas in new objective
    objectiveTemplate.querySelectorAll('.logical-textarea').forEach(textarea => {
        autoResizeTextarea(textarea);
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });
}
</script>
