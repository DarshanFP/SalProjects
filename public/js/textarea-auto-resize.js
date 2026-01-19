/**
 * Global TextArea Auto-Resize Functionality
 * Applies to all textareas with class: sustainability-textarea, logical-textarea, or auto-resize-textarea
 */

(function() {
    'use strict';

    /**
     * Auto-resize a single textarea
     * @param {HTMLTextAreaElement} textarea
     */
    function autoResizeTextarea(textarea) {
        if (!textarea) return;

        // Reset height to auto to get correct scrollHeight
        textarea.style.height = 'auto';

        // Set height to scrollHeight
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }

    /**
     * Initialize auto-resize for a textarea
     * @param {HTMLTextAreaElement} textarea
     */
    function initTextareaAutoResize(textarea) {
        if (!textarea) return;

        // Set initial height
        autoResizeTextarea(textarea);

        // Auto-resize on input
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });

        // Auto-resize on paste (with slight delay for content to be inserted)
        textarea.addEventListener('paste', function() {
            setTimeout(() => {
                autoResizeTextarea(this);
            }, 10);
        });
    }

    /**
     * Initialize all textareas with auto-resize classes
     */
    function initAllTextareas() {
        const textareas = document.querySelectorAll('.sustainability-textarea, .logical-textarea, .auto-resize-textarea');
        textareas.forEach(textarea => {
            initTextareaAutoResize(textarea);
        });
    }

    /**
     * Initialize textarea when added dynamically
     * @param {HTMLElement} container - Container where new textarea was added
     */
    function initDynamicTextarea(container) {
        const textareas = container.querySelectorAll('.sustainability-textarea, .logical-textarea, .auto-resize-textarea');
        textareas.forEach(textarea => {
            initTextareaAutoResize(textarea);
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllTextareas);
    } else {
        initAllTextareas();
    }

    // Make functions globally available for dynamic additions
    window.initTextareaAutoResize = initTextareaAutoResize;
    window.initDynamicTextarea = initDynamicTextarea;
    window.autoResizeTextarea = autoResizeTextarea;
})();
