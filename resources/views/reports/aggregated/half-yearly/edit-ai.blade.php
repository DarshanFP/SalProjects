@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Edit AI Content - {{ $report->getPeriodLabel() }}</h4>
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

                    <form action="{{ route('aggregated.half-yearly.update-ai', $report->report_id) }}" method="POST" id="aiEditForm">
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
                            <label class="form-label">Key Achievements (JSON Array)</label>
                            <div class="position-relative">
                                <div id="key_achievements_editor" style="height: 200px; border: 1px solid #ddd; border-radius: 4px;"></div>
                                <textarea name="key_achievements" id="key_achievements" style="display: none;">{{ old('key_achievements', $report->aiInsights ? json_encode($report->aiInsights->key_achievements, JSON_PRETTY_PRINT) : '[]') }}</textarea>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-secondary" id="format_key_achievements">Format JSON</button>
                                <span id="key_achievements_error" class="text-danger ms-2"></span>
                            </div>
                            <small class="form-text text-muted">Enter valid JSON array format</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Strategic Insights (JSON Array)</label>
                            <div class="position-relative">
                                <div id="strategic_insights_editor" style="height: 200px; border: 1px solid #ddd; border-radius: 4px;"></div>
                                <textarea name="strategic_insights" id="strategic_insights" style="display: none;">{{ old('strategic_insights', $report->aiInsights ? json_encode($report->aiInsights->strategic_insights, JSON_PRETTY_PRINT) : '[]') }}</textarea>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-secondary" id="format_strategic_insights">Format JSON</button>
                                <span id="strategic_insights_error" class="text-danger ms-2"></span>
                            </div>
                            <small class="form-text text-muted">Enter valid JSON array format</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Quarterly Comparison (JSON Object)</label>
                            <div class="position-relative">
                                <div id="quarterly_comparison_editor" style="height: 200px; border: 1px solid #ddd; border-radius: 4px;"></div>
                                <textarea name="quarterly_comparison" id="quarterly_comparison" style="display: none;">{{ old('quarterly_comparison', $report->aiInsights ? json_encode($report->aiInsights->quarterly_comparison, JSON_PRETTY_PRINT) : '{}') }}</textarea>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-secondary" id="format_quarterly_comparison">Format JSON</button>
                                <span id="quarterly_comparison_error" class="text-danger ms-2"></span>
                            </div>
                            <small class="form-text text-muted">Enter valid JSON object format</small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('aggregated.half-yearly.show', $report->report_id) }}" class="btn btn-secondary">Cancel</a>
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
    const jsonFields = ['key_achievements', 'strategic_insights', 'quarterly_comparison'];

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
#key_achievements_editor,
#strategic_insights_editor,
#quarterly_comparison_editor {
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
