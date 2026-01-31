/**
 * Key Information word-count validation (frontend only).
 * Enforces minimum 100 words per textarea on FINAL submit; DRAFT submit is always allowed.
 *
 * Fields: initial_information, target_beneficiaries, general_situation,
 *         need_of_project, economic_situation, goal
 */
(function () {
    'use strict';

    var MIN_WORDS = 100;
    var FIELD_IDS = [
        'initial_information',
        'target_beneficiaries',
        'general_situation',
        'need_of_project',
        'economic_situation',
        'goal'
    ];
    var ERROR_CLASS = 'key-info-word-error';

    function countWords(text) {
        if (typeof text !== 'string') return 0;
        return text.trim().split(/\s+/).filter(Boolean).length;
    }

    function getOrCreateErrorEl(textarea) {
        var parent = textarea.closest('.mb-3');
        if (!parent) return null;
        var el = parent.querySelector('.' + ERROR_CLASS);
        if (!el) {
            el = document.createElement('span');
            el.className = 'text-danger ' + ERROR_CLASS + ' d-block mt-1';
            textarea.parentNode.insertBefore(el, textarea.nextSibling);
        }
        return el;
    }

    function clearError(textarea) {
        var parent = textarea.closest('.mb-3');
        if (!parent) return;
        var el = parent.querySelector('.' + ERROR_CLASS);
        if (el) el.remove();
    }

    function showError(textarea, count) {
        var el = getOrCreateErrorEl(textarea);
        if (el) {
            el.textContent = 'This field must contain at least ' + MIN_WORDS + ' words (current: ' + count + ').';
        }
    }

    function validateForm(form) {
        var errors = [];
        var firstInvalid = null;

        FIELD_IDS.forEach(function (id) {
            var textarea = form.querySelector('#' + id);
            if (!textarea) return;
            var count = countWords(textarea.value);
            if (count < MIN_WORDS) {
                errors.push({ el: textarea, count: count });
                if (!firstInvalid) firstInvalid = textarea;
            }
        });

        return { valid: errors.length === 0, errors: errors, firstInvalid: firstInvalid };
    }

    function clearAllErrors(form) {
        FIELD_IDS.forEach(function (id) {
            var textarea = form.querySelector('#' + id);
            if (textarea) clearError(textarea);
        });
    }

    function attachLiveClear(form) {
        FIELD_IDS.forEach(function (id) {
            var textarea = form.querySelector('#' + id);
            if (!textarea) return;
            textarea.addEventListener('input', function () {
                var count = countWords(this.value);
                if (count >= MIN_WORDS) clearError(this);
            });
        });
    }

    function initForm(form) {
        if (!form) return;

        form.addEventListener('submit', function (e) {
            var draftInput = form.querySelector('input[name="save_as_draft"]');
            var isDraft = draftInput && draftInput.value === '1';

            if (isDraft) {
                clearAllErrors(form);
                return;
            }

            clearAllErrors(form);
            var result = validateForm(form);

            if (!result.valid) {
                e.preventDefault();

                result.errors.forEach(function (err) {
                    showError(err.el, err.count);
                });

                if (result.firstInvalid) {
                    result.firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    result.firstInvalid.focus();
                }

                var btn = form.querySelector('#createProjectBtn') || form.querySelector('#updateProjectBtn');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = btn.id === 'createProjectBtn' ? 'Save Project Application' : 'Update Project';
                }

                return false;
            }
        }, true);

        attachLiveClear(form);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var createForm = document.getElementById('createProjectForm');
        var editForm = document.getElementById('editProjectForm');
        if (createForm) initForm(createForm);
        if (editForm) initForm(editForm);
    });
})();
