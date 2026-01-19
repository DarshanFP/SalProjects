@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Edit AI Content - {{ $report->year }}</h4>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('aggregated.annual.update-ai', $report->report_id) }}" method="POST" id="aiEditForm">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="report_title" class="form-label">Report Title</label>
                            <input type="text" name="report_title" id="report_title" class="form-control" value="{{ old('report_title', $report->aiTitle->report_title ?? '') }}">
                        </div>

                        <div class="mb-3">
                            <label for="executive_summary" class="form-label">Executive Summary</label>
                            <textarea name="executive_summary" id="executive_summary" class="form-control auto-resize-textarea" rows="6">{{ old('executive_summary', $report->aiInsights->executive_summary ?? '') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Impact Assessment (JSON)</label>
                            <div class="position-relative">
                                <div id="impact_assessment_editor" style="height: 250px; border: 1px solid #ddd; border-radius: 4px;"></div>
                                <textarea name="impact_assessment" id="impact_assessment" style="display: none;">{{ old('impact_assessment', $report->aiInsights ? json_encode($report->aiInsights->impact_assessment, JSON_PRETTY_PRINT) : '{}') }}</textarea>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-secondary" id="format_impact_assessment">Format JSON</button>
                                <span id="impact_assessment_error" class="text-danger ms-2"></span>
                            </div>
                            <small class="form-text text-muted">Enter valid JSON object format</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Budget Performance (JSON)</label>
                            <div class="position-relative">
                                <div id="budget_performance_editor" style="height: 250px; border: 1px solid #ddd; border-radius: 4px;"></div>
                                <textarea name="budget_performance" id="budget_performance" style="display: none;">{{ old('budget_performance', $report->aiInsights ? json_encode($report->aiInsights->budget_performance, JSON_PRETTY_PRINT) : '{}') }}</textarea>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-secondary" id="format_budget_performance">Format JSON</button>
                                <span id="budget_performance_error" class="text-danger ms-2"></span>
                            </div>
                            <small class="form-text text-muted">Enter valid JSON object format</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Future Outlook (JSON)</label>
                            <div class="position-relative">
                                <div id="future_outlook_editor" style="height: 250px; border: 1px solid #ddd; border-radius: 4px;"></div>
                                <textarea name="future_outlook" id="future_outlook" style="display: none;">{{ old('future_outlook', $report->aiInsights ? json_encode($report->aiInsights->future_outlook, JSON_PRETTY_PRINT) : '{}') }}</textarea>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-secondary" id="format_future_outlook">Format JSON</button>
                                <span id="future_outlook_error" class="text-danger ms-2"></span>
                            </div>
                            <small class="form-text text-muted">Enter valid JSON object format</small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('aggregated.annual.show', $report->report_id) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ace Editor CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.2/ace.js" type="text/javascript" charset="utf-8"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Ace Editors for JSON fields
    const editors = {};
    const jsonFields = ['impact_assessment', 'budget_performance', 'future_outlook'];

    jsonFields.forEach(function(fieldName) {
        const editorId = fieldName + '_editor';
        const textareaId = fieldName;
        const errorId = fieldName + '_error';

        const editor = ace.edit(editorId);
        editor.setTheme("ace/theme/monokai");
        editor.getSession().setMode("ace/mode/json");
        editor.setOptions({
            fontSize: 14,
            showPrintMargin: false,
            wrap: true
        });

        // Set initial value from textarea
        const textarea = document.getElementById(textareaId);
        editor.setValue(textarea.value);

        // Update textarea on editor change
        editor.getSession().on('change', function() {
            textarea.value = editor.getValue();
            validateJSON(fieldName, editor, errorId);
        });

        // Format button
        document.getElementById('format_' + fieldName).addEventListener('click', function() {
            formatJSON(editor, errorId);
        });

        // Initial validation
        validateJSON(fieldName, editor, errorId);

        editors[fieldName] = editor;
    });

    // Validate JSON
    function validateJSON(fieldName, editor, errorId) {
        const errorElement = document.getElementById(errorId);
        try {
            const value = editor.getValue().trim();
            if (value) {
                JSON.parse(value);
                errorElement.textContent = '';
                editor.getSession().setAnnotations([]);
                return true;
            }
            errorElement.textContent = '';
            editor.getSession().setAnnotations([]);
            return true;
        } catch (e) {
            errorElement.textContent = 'Invalid JSON: ' + e.message;
            editor.getSession().setAnnotations([{
                row: e.line ? e.line - 1 : 0,
                column: e.column || 0,
                text: e.message,
                type: 'error'
            }]);
            return false;
        }
    }

    // Format JSON
    function formatJSON(editor, errorId) {
        try {
            const value = editor.getValue().trim();
            if (value) {
                const parsed = JSON.parse(value);
                const formatted = JSON.stringify(parsed, null, 2);
                editor.setValue(formatted);
                document.getElementById(errorId).textContent = '';
                editor.getSession().setAnnotations([]);
            }
        } catch (e) {
            document.getElementById(errorId).textContent = 'Cannot format: Invalid JSON - ' + e.message;
        }
    }

    // Form submission validation
    document.getElementById('aiEditForm').addEventListener('submit', function(e) {
        let isValid = true;
        jsonFields.forEach(function(fieldName) {
            const editor = editors[fieldName];
            const errorId = fieldName + '_error';
            if (!validateJSON(fieldName, editor, errorId)) {
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please fix JSON validation errors before submitting.');
        }
    });
});
</script>

<style>
#impact_assessment_editor,
#budget_performance_editor,
#future_outlook_editor {
    font-family: 'Courier New', monospace;
}

.ace_editor {
    border-radius: 4px;
}

.ace_error {
    background-color: #ffebee;
}
</style>
@endsection
