/**
 * Report View - Hide Empty Fields
 *
 * This script automatically hides empty fields and labels in report views
 * to show only data that has been filled by the user.
 *
 * Usage: Include this script in report view pages
 */

(function() {
    'use strict';

    /**
     * Check if a value is considered empty
     * @param {string|number|null|undefined} value - The value to check
     * @returns {boolean} - True if empty, false otherwise
     */
    function isEmpty(value) {
        if (value === null || value === undefined) {
            return true;
        }

        // Convert to string and trim
        const strValue = String(value).trim();

        // Check for empty string, "N/A", "null", "undefined", "0" (for text fields)
        const emptyValues = ['', 'n/a', 'null', 'undefined', '0'];

        // For text fields, "0" might be valid, so we check if it's actually empty
        if (strValue === '' || strValue.toLowerCase() === 'n/a') {
            return true;
        }

        // Check if it's just whitespace or HTML tags only
        const textContent = strValue.replace(/<[^>]*>/g, '').trim();
        if (textContent === '') {
            return true;
        }

        return false;
    }

    /**
     * Check if an element's content is empty
     * @param {HTMLElement} element - The element to check
     * @returns {boolean} - True if empty, false otherwise
     */
    function isElementEmpty(element) {
        if (!element) return true;

        // Get text content (excluding HTML tags)
        const textContent = element.textContent || element.innerText || '';
        const trimmed = textContent.trim();

        // Check for empty or common empty indicators
        if (trimmed === '' || trimmed === 'N/A' || trimmed === 'null' || trimmed === 'undefined') {
            return true;
        }

        // For elements with only whitespace or HTML entities
        const cleanText = trimmed.replace(/&nbsp;/g, '').replace(/\s+/g, '');
        return cleanText === '';
    }

    /**
     * Hide empty field pairs in info-grid structure
     * Structure: <div class="info-label">...</div><div class="info-value">...</div>
     */
    function hideEmptyInfoGridFields() {
        const infoGrids = document.querySelectorAll('.info-grid');

        infoGrids.forEach(grid => {
            const children = Array.from(grid.children);
            let hasVisibleFields = false;

            // Process pairs (label, value)
            for (let i = 0; i < children.length; i += 2) {
                const label = children[i];
                const value = children[i + 1];

                if (!label || !value) continue;

                // Check if value is empty
                if (isElementEmpty(value)) {
                    // Hide both label and value
                    label.style.display = 'none';
                    value.style.display = 'none';
                } else {
                    hasVisibleFields = true;
                }
            }

            // Hide entire grid if no visible fields
            if (!hasVisibleFields && children.length > 0) {
                grid.closest('.card-body, .card')?.classList.add('d-none');
            }
        });
    }

    /**
     * Hide empty field pairs in Bootstrap row/col structure
     * Structure: <div class="row"><div class="col-2">...</div><div class="col-10">...</div></div>
     */
    function hideEmptyRowColFields() {
        const rows = document.querySelectorAll('.row');

        rows.forEach(row => {
            const labelCol = row.querySelector('.report-label-col, .col-2');
            const valueCol = row.querySelector('.report-value-col, .col-10');

            if (!labelCol || !valueCol) return;

            // Check if value column is empty
            if (isElementEmpty(valueCol)) {
                // Hide entire row
                row.style.display = 'none';
            }
        });
    }

    /**
     * Hide empty activity cards
     * Activities are nested within objectives
     */
    function hideEmptyActivities() {
        // For quarterly reports: activity cards are .card elements within objective cards
        const activityCards = document.querySelectorAll('.card-body .card');

        activityCards.forEach(card => {
            // Check if this is an activity card (has month or activity-related content)
            const header = card.querySelector('.card-header');
            const hasMonth = header && (
                header.textContent.includes('Month') ||
                card.textContent.includes('Summary of Activities') ||
                card.textContent.includes('Monthly Summary')
            );

            // Skip if not an activity card
            if (!hasMonth) return;

            // Get all value elements in this card
            const valueElements = card.querySelectorAll('.info-value, .report-value-col, .col-10');
            let hasAnyData = false;

            valueElements.forEach(valueEl => {
                if (!isElementEmpty(valueEl)) {
                    hasAnyData = true;
                }
            });

            // Hide entire activity card if no data
            if (!hasAnyData) {
                card.style.display = 'none';
            }
        });

        // For monthly reports: activities are in rows within objective cards
        const objectiveCards = document.querySelectorAll('.objective-card');
        objectiveCards.forEach(objectiveCard => {
            // Find activity rows (rows that come after "Activities" heading)
            const activitiesHeading = objectiveCard.querySelector('h6');
            if (!activitiesHeading || !activitiesHeading.textContent.includes('Activities')) {
                return;
            }

            // Get all rows within the objective card
            const allRows = Array.from(objectiveCard.querySelectorAll('.row'));
            const activityRows = [];

            // Find rows that come after the Activities heading
            allRows.forEach(row => {
                // Check if this row comes after the Activities heading in DOM order
                if (activitiesHeading.compareDocumentPosition(row) & Node.DOCUMENT_POSITION_FOLLOWING) {
                    activityRows.push(row);
                }
            });

            // Group rows by activity (each activity starts with a "Month" row)
            const activities = [];
            let currentActivityRows = [];

            activityRows.forEach(row => {
                const monthLabel = row.querySelector('.report-label-col, .col-2');
                const isMonthRow = monthLabel && monthLabel.textContent.trim().includes('Month');

                if (isMonthRow) {
                    // New activity starts - save previous activity if exists
                    if (currentActivityRows.length > 0) {
                        activities.push([...currentActivityRows]);
                    }
                    currentActivityRows = [row];
                } else if (currentActivityRows.length > 0) {
                    // Continue current activity
                    currentActivityRows.push(row);
                }
            });

            // Don't forget the last activity
            if (currentActivityRows.length > 0) {
                activities.push(currentActivityRows);
            }

            // Check each activity group for data
            activities.forEach(activityRows => {
                let hasAnyData = false;
                activityRows.forEach(row => {
                    const valueCol = row.querySelector('.report-value-col, .col-10');
                    if (valueCol && !isElementEmpty(valueCol)) {
                        hasAnyData = true;
                    }
                });

                // Hide all rows in this activity if no data
                if (!hasAnyData) {
                    activityRows.forEach(row => {
                        row.style.display = 'none';
                    });
                }
            });
        });
    }

    /**
     * Hide empty objective cards
     * Objectives should only be shown if they have:
     * 1. Data in objective fields (objective text, expected outcome, etc.), OR
     * 2. At least one activity with data
     */
    function hideEmptyObjectives() {
        // For monthly reports: objectives have .objective-card class
        const monthlyObjectiveCards = document.querySelectorAll('.objective-card');

        monthlyObjectiveCards.forEach(card => {
            if (card.style.display === 'none') return; // Skip already hidden

            // Find the Activities heading
            const activitiesHeading = card.querySelector('h6');

            // Check for specific objective-level fields that should keep objective visible
            // These fields come before the Activities heading
            const specificObjectiveFields = [
                'What Did Not Happen',
                'Why Some Activities Could Not Be Undertaken',
                'Have You Made Any Changes',
                'Why the Changes Were Needed',
                'What Are the Lessons Learnt',
                'What Will Be Done Differently',
                'Changes'
            ];

            let hasObjectiveLevelData = false;
            if (activitiesHeading) {
                // Check rows before Activities heading for specific objective fields
                const allRows = Array.from(card.querySelectorAll('.row'));
                allRows.forEach(row => {
                    // Check if this row comes before Activities heading
                    const position = activitiesHeading.compareDocumentPosition(row);
                    if (position & Node.DOCUMENT_POSITION_PRECEDING) {
                        const labelCol = row.querySelector('.report-label-col, .col-2');
                        if (labelCol) {
                            const labelText = labelCol.textContent || '';
                            // Check if this row is one of the specific objective fields
                            const isSpecificField = specificObjectiveFields.some(field =>
                                labelText.includes(field)
                            );

                            if (isSpecificField) {
                                const valueCol = row.querySelector('.report-value-col, .col-10');
                                if (valueCol && !isElementEmpty(valueCol)) {
                                    // Special handling for "Changes" - check if it's "Yes"
                                    if (labelText.includes('Changes')) {
                                        const valueText = (valueCol.textContent || '').trim().toLowerCase();
                                        if (valueText === 'yes' || valueText === 'true') {
                                            hasObjectiveLevelData = true;
                                        } else {
                                            // If Changes is "No", also check "Why Changes" field
                                            const whyChangesRow = allRows.find(r => {
                                                const lbl = r.querySelector('.report-label-col, .col-2');
                                                return lbl && lbl.textContent.includes('Why the Changes Were Needed');
                                            });
                                            if (whyChangesRow) {
                                                const whyValueCol = whyChangesRow.querySelector('.report-value-col, .col-10');
                                                if (whyValueCol && !isElementEmpty(whyValueCol)) {
                                                    hasObjectiveLevelData = true;
                                                }
                                            }
                                        }
                                    } else {
                                        hasObjectiveLevelData = true;
                                    }
                                }
                            }
                        }
                    }
                });
            } else {
                // If no Activities heading, check all rows for specific fields
                const allRows = Array.from(card.querySelectorAll('.row'));
                allRows.forEach(row => {
                    const labelCol = row.querySelector('.report-label-col, .col-2');
                    if (labelCol) {
                        const labelText = labelCol.textContent || '';
                        const isSpecificField = specificObjectiveFields.some(field =>
                            labelText.includes(field)
                        );

                        if (isSpecificField) {
                            const valueCol = row.querySelector('.report-value-col, .col-10');
                            if (valueCol && !isElementEmpty(valueCol)) {
                                if (labelText.includes('Changes')) {
                                    const valueText = (valueCol.textContent || '').trim().toLowerCase();
                                    if (valueText === 'yes' || valueText === 'true') {
                                        hasObjectiveLevelData = true;
                                    }
                                } else {
                                    hasObjectiveLevelData = true;
                                }
                            }
                        }
                    }
                });
            }

            // Check if there are any visible activities with data (rows after Activities heading)
            let hasVisibleActivityWithData = false;
            if (activitiesHeading) {
                const allRows = Array.from(card.querySelectorAll('.row'));

                allRows.forEach(row => {
                    // Skip if row is hidden
                    if (row.style.display === 'none') return;

                    // Check if this row comes after Activities heading
                    const position = activitiesHeading.compareDocumentPosition(row);
                    if (position & Node.DOCUMENT_POSITION_FOLLOWING) {
                        const valueCol = row.querySelector('.report-value-col, .col-10');
                        if (valueCol && !isElementEmpty(valueCol)) {
                            hasVisibleActivityWithData = true;
                        }
                    }
                });
            }

            // Show objective if it has EITHER activities with data OR objective-level data
            // Hide objective only if it has NEITHER activities with data NOR objective-level data
            if (!hasVisibleActivityWithData && !hasObjectiveLevelData) {
                card.style.display = 'none';
            }
        });

        // For quarterly reports: objectives are .card elements that contain "Objective" in header
        // and have activity cards inside
        const allCards = document.querySelectorAll('.card');
        const quarterlyObjectiveCards = [];

        allCards.forEach(card => {
            if (card.closest('.objective-card')) return; // Skip if inside monthly objective card

            const header = card.querySelector('.card-header');
            if (header && header.textContent.includes('Objective')) {
                // Check if this card contains activity cards (nested .card elements)
                const hasActivityCards = card.querySelectorAll('.card-body .card').length > 0;
                if (hasActivityCards) {
                    quarterlyObjectiveCards.push(card);
                }
            }
        });

        quarterlyObjectiveCards.forEach(card => {
            if (card.style.display === 'none') return; // Skip already hidden

            const cardBody = card.querySelector('.card-body');
            if (!cardBody) return;

            // Find the Monthly Summary heading (activities come after this)
            const monthlySummaryHeading = Array.from(cardBody.querySelectorAll('h4')).find(h4 =>
                h4.textContent.includes('Monthly Summary')
            );

            // Check for specific objective-level fields that should keep objective visible
            // These fields are in the info-grid before Monthly Summary heading
            const specificObjectiveFields = [
                'What Did Not Happen',
                'Why Some Activities Could Not Be Undertaken',
                'Have You Made Any Changes',
                'Why the Changes Were Needed',
                'What Are the Lessons Learnt',
                'What Will Be Done Differently',
                'Changes'
            ];

            let hasObjectiveLevelData = false;
            if (monthlySummaryHeading) {
                // Check info-grid sections before Monthly Summary for specific objective fields
                let element = cardBody.firstElementChild;
                while (element && element !== monthlySummaryHeading) {
                    if (element.classList && element.classList.contains('info-grid')) {
                        const labels = element.querySelectorAll('.info-label');
                        labels.forEach(label => {
                            const labelText = label.textContent || '';
                            const isSpecificField = specificObjectiveFields.some(field =>
                                labelText.includes(field)
                            );

                            if (isSpecificField) {
                                // Find corresponding value (next sibling in grid)
                                const labelIndex = Array.from(element.children).indexOf(label);
                                const valueEl = element.children[labelIndex + 1];

                                if (valueEl && valueEl.classList.contains('info-value')) {
                                    if (!isElementEmpty(valueEl)) {
                                        // Special handling for "Changes" - check if it's "Yes"
                                        if (labelText.includes('Changes')) {
                                            const valueText = (valueEl.textContent || '').trim().toLowerCase();
                                            if (valueText === 'yes' || valueText === 'true') {
                                                hasObjectiveLevelData = true;
                                            }
                                        } else {
                                            hasObjectiveLevelData = true;
                                        }
                                    }
                                }
                            }
                        });
                    }
                    element = element.nextElementSibling;
                }
            } else {
                // If no Monthly Summary, check all info-grid sections for specific fields
                const allInfoGrids = cardBody.querySelectorAll('.info-grid');
                allInfoGrids.forEach(grid => {
                    const labels = grid.querySelectorAll('.info-label');
                    labels.forEach(label => {
                        const labelText = label.textContent || '';
                        const isSpecificField = specificObjectiveFields.some(field =>
                            labelText.includes(field)
                        );

                        if (isSpecificField) {
                            const labelIndex = Array.from(grid.children).indexOf(label);
                            const valueEl = grid.children[labelIndex + 1];

                            if (valueEl && valueEl.classList.contains('info-value')) {
                                if (!isElementEmpty(valueEl)) {
                                    if (labelText.includes('Changes')) {
                                        const valueText = (valueEl.textContent || '').trim().toLowerCase();
                                        if (valueText === 'yes' || valueText === 'true') {
                                            hasObjectiveLevelData = true;
                                        }
                                    } else {
                                        hasObjectiveLevelData = true;
                                    }
                                }
                            }
                        }
                    });
                });
            }

            // Check if there are any visible activity cards with data (after Monthly Summary)
            let hasVisibleActivityWithData = false;
            if (monthlySummaryHeading) {
                const activityCards = cardBody.querySelectorAll('.card');

                activityCards.forEach(activityCard => {
                    // Skip if activity card is hidden
                    if (activityCard.style.display === 'none') return;

                    // Check if this activity card comes after Monthly Summary
                    const position = monthlySummaryHeading.compareDocumentPosition(activityCard);
                    if (position & Node.DOCUMENT_POSITION_FOLLOWING) {
                        // Check if this activity card has any data
                        const valueElements = activityCard.querySelectorAll('.info-value, .report-value-col, .col-10');
                        valueElements.forEach(valueEl => {
                            if (!isElementEmpty(valueEl)) {
                                hasVisibleActivityWithData = true;
                            }
                        });
                    }
                });
            }

            // Show objective if it has EITHER activities with data OR objective-level data
            // Hide objective only if it has NEITHER activities with data NOR objective-level data
            if (!hasVisibleActivityWithData && !hasObjectiveLevelData) {
                card.style.display = 'none';
            }
        });
    }

    /**
     * Hide empty outlook cards
     */
    function hideEmptyOutlooks() {
        const outlookCards = document.querySelectorAll('.card');

        outlookCards.forEach(card => {
            // Check if this is an outlook card
            const header = card.querySelector('.card-header');
            if (!header || !header.textContent.includes('Outlook')) return;

            const valueElements = card.querySelectorAll('.info-value, .report-value-col, .col-10');
            let hasAnyData = false;

            valueElements.forEach(valueEl => {
                if (!isElementEmpty(valueEl)) {
                    hasAnyData = true;
                }
            });

            // Hide entire outlook card if no data
            if (!hasAnyData) {
                card.style.display = 'none';
            }
        });
    }

    /**
     * Hide empty sections (entire card sections)
     */
    function hideEmptySections() {
        const sectionCards = document.querySelectorAll('.card');

        sectionCards.forEach(card => {
            // Skip if already hidden
            if (card.style.display === 'none') return;

            // Get all visible content in card body
            const cardBody = card.querySelector('.card-body');
            if (!cardBody) return;

            // Check if card body has any visible content
            const visibleChildren = Array.from(cardBody.children).filter(child => {
                return child.style.display !== 'none' && !isElementEmpty(child);
            });

            // If no visible children, hide the entire card
            if (visibleChildren.length === 0) {
                // Don't hide basic information or statements of account sections
                const header = card.querySelector('.card-header');
                if (header) {
                    const headerText = header.textContent.toLowerCase();
                    if (headerText.includes('basic information') ||
                        headerText.includes('statements of account') ||
                        headerText.includes('account details')) {
                        return; // Keep these sections visible
                    }
                }

                card.style.display = 'none';
            }
        });
    }

    /**
     * Main function to hide all empty fields
     */
    function hideEmptyFields() {
        // Wait for DOM to be fully loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(executeHiding, 100); // Small delay to ensure all content is rendered
            });
        } else {
            setTimeout(executeHiding, 100);
        }
    }

    /**
     * Execute all hiding functions
     * Order matters: hide activities first, then objectives can check for visible activities
     */
    function executeHiding() {
        // Step 1: Hide empty fields in different structures
        hideEmptyInfoGridFields();
        hideEmptyRowColFields();

        // Step 2: Hide empty activities FIRST (objectives depend on this)
        hideEmptyActivities();

        // Step 3: Hide empty objectives (checks for visible activities)
        hideEmptyObjectives();

        // Step 4: Hide other empty sections
        hideEmptyOutlooks();
        hideEmptySections();

        // Log for debugging (remove in production)
        if (window.console && console.log) {
            console.log('Report view: Empty fields hidden');
        }
    }

    // Initialize when script loads
    hideEmptyFields();

    // Also run after a short delay to catch dynamically loaded content
    setTimeout(executeHiding, 500);

    // Export function for manual triggering if needed
    window.hideEmptyReportFields = executeHiding;

})();
