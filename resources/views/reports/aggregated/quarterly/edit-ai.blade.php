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

                    <form action="{{ route('aggregated.quarterly.update-ai', $report->report_id) }}" method="POST" id="aiEditForm">
                        @csrf
                        @method('PUT')

                        <!-- Report Title -->
                        <div class="mb-3">
                            <label for="report_title" class="form-label">Report Title</label>
                            <input type="text" name="report_title" id="report_title" class="form-control"
                                   value="{{ old('report_title', $report->aiTitle->report_title ?? '') }}">
                        </div>

                        <!-- Executive Summary -->
                        <div class="mb-3">
                            <label for="executive_summary" class="form-label">Executive Summary</label>
                            <textarea name="executive_summary" id="executive_summary" class="form-control auto-resize-textarea" rows="6">{{ old('executive_summary', $report->aiInsights->executive_summary ?? '') }}</textarea>
                        </div>

                        <!-- Key Achievements -->
                        <div class="mb-3">
                            <label class="form-label">Key Achievements</label>
                            <div id="achievements-container">
                                @if($report->aiInsights && $report->aiInsights->key_achievements)
                                    @foreach($report->aiInsights->key_achievements as $index => $achievement)
                                        <div class="achievement-item mb-2" data-index="{{ $index }}">
                                            <div class="input-group">
                                                <input type="text" name="key_achievements[{{ $index }}][title]" class="form-control"
                                                       placeholder="Achievement Title" value="{{ is_array($achievement) ? ($achievement['title'] ?? '') : '' }}">
                                                <textarea name="key_achievements[{{ $index }}][description]" class="form-control auto-resize-textarea"
                                                          placeholder="Description" rows="2">{{ is_array($achievement) ? ($achievement['description'] ?? '') : $achievement }}</textarea>
                                                <button type="button" class="btn btn-danger remove-achievement">Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" id="add-achievement">Add Achievement</button>
                        </div>

                        <!-- Progress Trends -->
                        <div class="mb-3">
                            <label for="progress_trends" class="form-label">Progress Trends (JSON)</label>
                            <div class="position-relative">
                                <div id="progress_trends_editor" style="height: 200px; border: 1px solid #ddd; border-radius: 4px;"></div>
                                <textarea name="progress_trends" id="progress_trends" style="display: none;">{{ old('progress_trends', $report->aiInsights ? json_encode($report->aiInsights->progress_trends, JSON_PRETTY_PRINT) : '[]') }}</textarea>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-secondary" id="format_progress_trends">Format JSON</button>
                                <span id="progress_trends_error" class="text-danger ms-2"></span>
                            </div>
                            <small class="form-text text-muted">Enter valid JSON format for trends data</small>
                        </div>

                        <!-- Challenges -->
                        <div class="mb-3">
                            <label class="form-label">Challenges</label>
                            <div id="challenges-container">
                                @if($report->aiInsights && $report->aiInsights->challenges)
                                    @foreach($report->aiInsights->challenges as $index => $challenge)
                                        <div class="challenge-item mb-2" data-index="{{ $index }}">
                                            <div class="input-group">
                                                <textarea name="challenges[{{ $index }}]" class="form-control auto-resize-textarea" rows="2">{{ is_array($challenge) ? ($challenge['challenge'] ?? $challenge['description'] ?? '') : $challenge }}</textarea>
                                                <button type="button" class="btn btn-danger remove-challenge">Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" id="add-challenge">Add Challenge</button>
                        </div>

                        <!-- Recommendations -->
                        <div class="mb-3">
                            <label class="form-label">Recommendations</label>
                            <div id="recommendations-container">
                                @if($report->aiInsights && $report->aiInsights->recommendations)
                                    @foreach($report->aiInsights->recommendations as $index => $recommendation)
                                        <div class="recommendation-item mb-2" data-index="{{ $index }}">
                                            <div class="input-group">
                                                <textarea name="recommendations[{{ $index }}]" class="form-control auto-resize-textarea" rows="2">{{ is_array($recommendation) ? ($recommendation['recommendation'] ?? $recommendation['rationale'] ?? '') : $recommendation }}</textarea>
                                                <button type="button" class="btn btn-danger remove-recommendation">Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" id="add-recommendation">Add Recommendation</button>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('aggregated.quarterly.show', $report->report_id) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
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
    // Initialize Ace Editor for Progress Trends JSON field
    const progressTrendsEditor = ace.edit('progress_trends_editor');
    progressTrendsEditor.setTheme("ace/theme/monokai");
    progressTrendsEditor.getSession().setMode("ace/mode/json");
    progressTrendsEditor.setOptions({
        fontSize: 14,
        showPrintMargin: false,
        wrap: true
    });

    const progressTrendsTextarea = document.getElementById('progress_trends');
    progressTrendsEditor.setValue(progressTrendsTextarea.value || '[]');

    // Update textarea on editor change
    progressTrendsEditor.getSession().on('change', function() {
        progressTrendsTextarea.value = progressTrendsEditor.getValue();
        validateProgressTrends();
    });

    // Format button
    document.getElementById('format_progress_trends').addEventListener('click', function() {
        try {
            const value = progressTrendsEditor.getValue().trim();
            if (value) {
                const parsed = JSON.parse(value);
                const formatted = JSON.stringify(parsed, null, 2);
                progressTrendsEditor.setValue(formatted);
                progressTrendsTextarea.value = formatted;
                document.getElementById('progress_trends_error').textContent = '';
                progressTrendsEditor.getSession().setAnnotations([]);
            }
        } catch (e) {
            document.getElementById('progress_trends_error').textContent = 'Cannot format: Invalid JSON - ' + e.message;
        }
    });

    // Validate Progress Trends JSON
    function validateProgressTrends() {
        const errorElement = document.getElementById('progress_trends_error');
        try {
            const value = progressTrendsEditor.getValue().trim();
            if (value) {
                JSON.parse(value);
                errorElement.textContent = '';
                progressTrendsEditor.getSession().setAnnotations([]);
                return true;
            }
            errorElement.textContent = '';
            progressTrendsEditor.getSession().setAnnotations([]);
            return true;
        } catch (e) {
            errorElement.textContent = 'Invalid JSON: ' + e.message;
            progressTrendsEditor.getSession().setAnnotations([{
                row: e.line ? e.line - 1 : 0,
                column: e.column || 0,
                text: e.message,
                type: 'error'
            }]);
            return false;
        }
    }

    // Initial validation
    validateProgressTrends();

    let achievementIndex = {{ $report->aiInsights && $report->aiInsights->key_achievements ? count($report->aiInsights->key_achievements) : 0 }};
    let challengeIndex = {{ $report->aiInsights && $report->aiInsights->challenges ? count($report->aiInsights->challenges) : 0 }};
    let recommendationIndex = {{ $report->aiInsights && $report->aiInsights->recommendations ? count($report->aiInsights->recommendations) : 0 }};

    // Add Achievement
    document.getElementById('add-achievement')?.addEventListener('click', function() {
        const container = document.getElementById('achievements-container');
        const div = document.createElement('div');
        div.className = 'achievement-item mb-2';
        div.setAttribute('data-index', achievementIndex);
        div.innerHTML = `
            <div class="input-group">
                <input type="text" name="key_achievements[${achievementIndex}][title]" class="form-control" placeholder="Achievement Title">
                <textarea name="key_achievements[${achievementIndex}][description]" class="form-control auto-resize-textarea" placeholder="Description" rows="2"></textarea>
                <button type="button" class="btn btn-danger remove-achievement">Remove</button>
            </div>
        `;
        container.appendChild(div);

        // Initialize auto-resize for new achievement textarea using global function
        const newAchievement = container.lastElementChild;
        if (newAchievement && typeof initDynamicTextarea === 'function') {
            initDynamicTextarea(newAchievement);
        }

        achievementIndex++;
    });

    // Add Challenge
    document.getElementById('add-challenge')?.addEventListener('click', function() {
        const container = document.getElementById('challenges-container');
        const div = document.createElement('div');
        div.className = 'challenge-item mb-2';
        div.setAttribute('data-index', challengeIndex);
        div.innerHTML = `
            <div class="input-group">
                <textarea name="challenges[${challengeIndex}]" class="form-control auto-resize-textarea" rows="2" placeholder="Challenge description"></textarea>
                <button type="button" class="btn btn-danger remove-challenge">Remove</button>
            </div>
        `;
        container.appendChild(div);

        // Initialize auto-resize for new challenge textarea using global function
        const newChallenge = container.lastElementChild;
        if (newChallenge && typeof initDynamicTextarea === 'function') {
            initDynamicTextarea(newChallenge);
        }

        challengeIndex++;
    });

    // Add Recommendation
    document.getElementById('add-recommendation')?.addEventListener('click', function() {
        const container = document.getElementById('recommendations-container');
        const div = document.createElement('div');
        div.className = 'recommendation-item mb-2';
        div.setAttribute('data-index', recommendationIndex);
        div.innerHTML = `
            <div class="input-group">
                <textarea name="recommendations[${recommendationIndex}]" class="form-control auto-resize-textarea" rows="2" placeholder="Recommendation"></textarea>
                <button type="button" class="btn btn-danger remove-recommendation">Remove</button>
            </div>
        `;
        container.appendChild(div);

        // Initialize auto-resize for new recommendation textarea using global function
        const newRecommendation = container.lastElementChild;
        if (newRecommendation && typeof initDynamicTextarea === 'function') {
            initDynamicTextarea(newRecommendation);
        }

        recommendationIndex++;
    });

    // Remove handlers
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-achievement')) {
            e.target.closest('.achievement-item').remove();
        }
        if (e.target.classList.contains('remove-challenge')) {
            e.target.closest('.challenge-item').remove();
        }
        if (e.target.classList.contains('remove-recommendation')) {
            e.target.closest('.recommendation-item').remove();
        }
    });

    // Form submission validation
    document.getElementById('aiEditForm').addEventListener('submit', function(e) {
        if (!validateProgressTrends()) {
            e.preventDefault();
            alert('Please fix JSON validation errors in Progress Trends before submitting.');
            return false;
        }
    });
});
</script>

<style>
#progress_trends_editor {
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
