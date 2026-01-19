/**
 * Phase 11: Browser Console Test Scripts
 * Copy and paste these scripts into browser console on report create/edit pages
 *
 * Usage:
 * 1. Open report create/edit page
 * 2. Open browser console (F12)
 * 3. Copy and paste desired test script
 * 4. Review results
 */

// ============================================================================
// TEST SUITE 1: Function Existence Verification
// ============================================================================

/**
 * Test if all required JavaScript functions exist
 * Returns: Object with function existence status
 */
function testFunctionExistence() {
    const functions = [
        'reindexOutlooks',
        'reindexAccountRows',
        'reindexActivities',
        'reindexAttachments',
        'toggleActivityCard',
        'updateActivityStatus',
        'addOutlook',
        'removeOutlook',
        'addAccountRow',
        'removeAccountRow',
        'addPhotoGroup',
        'removePhotoGroup',
        'addAttachment',
        'removeAttachment'
    ];

    const results = {
        found: [],
        missing: [],
        total: functions.length
    };

    functions.forEach(funcName => {
        if (typeof window[funcName] === 'function') {
            results.found.push(funcName);
            console.log(`✅ ${funcName} exists`);
        } else {
            results.missing.push(funcName);
            console.error(`❌ ${funcName} is missing`);
        }
    });

    console.log('\n=== Function Existence Test Results ===');
    console.log(`Found: ${results.found.length}/${results.total}`);
    console.log(`Missing: ${results.missing.length}/${results.total}`);
    console.log('Found functions:', results.found);
    if (results.missing.length > 0) {
        console.error('Missing functions:', results.missing);
    }

    return results;
}

// ============================================================================
// TEST SUITE 2: Outlook Section Indexing
// ============================================================================

/**
 * Test Outlook section indexing
 * Returns: Object with test results
 */
function testOutlookIndexing() {
    console.log('\n=== Testing Outlook Section Indexing ===');

    const outlookContainer = document.getElementById('outlook-container');
    if (!outlookContainer) {
        console.error('❌ Outlook container not found');
        return { error: 'Outlook container not found' };
    }

    const outlooks = outlookContainer.querySelectorAll('.outlook');
    console.log(`Found ${outlooks.length} outlook entries`);

    const results = {
        total: outlooks.length,
        indexed: 0,
        badges: [],
        dataAttributes: [],
        issues: []
    };

    outlooks.forEach((outlook, index) => {
        // Check index badge
        const badge = outlook.querySelector('.badge.bg-primary');
        if (badge) {
            const badgeText = badge.textContent.trim();
            const expectedIndex = index + 1;
            if (badgeText === expectedIndex.toString()) {
                results.badges.push({ index, status: 'correct', value: badgeText });
                results.indexed++;
            } else {
                results.issues.push(`Badge at position ${index} shows ${badgeText}, expected ${expectedIndex}`);
            }
        } else {
            results.issues.push(`No badge found at position ${index}`);
        }

        // Check data-index attribute
        const dataIndex = outlook.getAttribute('data-index');
        if (dataIndex === index.toString()) {
            results.dataAttributes.push({ index, status: 'correct', value: dataIndex });
        } else {
            results.issues.push(`data-index at position ${index} is ${dataIndex}, expected ${index}`);
        }
    });

    console.log('Results:', results);
    if (results.issues.length > 0) {
        console.error('Issues found:', results.issues);
    } else {
        console.log('✅ All outlook entries properly indexed');
    }

    return results;
}

/**
 * Test adding and removing outlook entries
 */
async function testOutlookAddRemove() {
    console.log('\n=== Testing Outlook Add/Remove ===');

    const initialCount = document.querySelectorAll('.outlook').length;
    console.log(`Initial outlook count: ${initialCount}`);

    // Test adding
    if (typeof addOutlook === 'function') {
        addOutlook();
        await new Promise(resolve => setTimeout(resolve, 100));

        const afterAddCount = document.querySelectorAll('.outlook').length;
        console.log(`After add count: ${afterAddCount}`);

        if (afterAddCount === initialCount + 1) {
            console.log('✅ Add outlook works correctly');
        } else {
            console.error('❌ Add outlook failed');
        }

        // Test reindexing after add
        const reindexTest = testOutlookIndexing();
        if (reindexTest.issues.length === 0) {
            console.log('✅ Reindexing after add works correctly');
        } else {
            console.error('❌ Reindexing after add has issues');
        }

        // Test removing
        const removeButtons = document.querySelectorAll('.remove-outlook');
        if (removeButtons.length > 0) {
            const lastButton = removeButtons[removeButtons.length - 1];
            if (typeof removeOutlook === 'function') {
                removeOutlook(lastButton);
                await new Promise(resolve => setTimeout(resolve, 100));

                const afterRemoveCount = document.querySelectorAll('.outlook').length;
                console.log(`After remove count: ${afterRemoveCount}`);

                if (afterRemoveCount === initialCount) {
                    console.log('✅ Remove outlook works correctly');
                } else {
                    console.error('❌ Remove outlook failed');
                }

                // Test reindexing after remove
                const reindexAfterRemove = testOutlookIndexing();
                if (reindexAfterRemove.issues.length === 0) {
                    console.log('✅ Reindexing after remove works correctly');
                } else {
                    console.error('❌ Reindexing after remove has issues');
                }
            }
        }
    } else {
        console.error('❌ addOutlook function not found');
    }
}

// ============================================================================
// TEST SUITE 3: Statements of Account Indexing
// ============================================================================

/**
 * Test Statements of Account indexing
 */
function testStatementsOfAccountIndexing() {
    console.log('\n=== Testing Statements of Account Indexing ===');

    const accountRows = document.querySelectorAll('#account-rows tr');
    console.log(`Found ${accountRows.length} account rows`);

    const results = {
        total: accountRows.length,
        indexed: 0,
        issues: []
    };

    accountRows.forEach((row, index) => {
        const indexCell = row.querySelector('td:first-child');
        if (indexCell) {
            const cellText = indexCell.textContent.trim();
            const expectedIndex = index + 1;

            if (cellText === expectedIndex.toString()) {
                results.indexed++;
            } else {
                results.issues.push(`Row ${index}: Index shows "${cellText}", expected "${expectedIndex}"`);
            }
        } else {
            results.issues.push(`Row ${index}: No index cell found`);
        }
    });

    console.log(`Indexed correctly: ${results.indexed}/${results.total}`);
    if (results.issues.length > 0) {
        console.error('Issues:', results.issues);
    } else {
        console.log('✅ All account rows properly indexed');
    }

    return results;
}

// ============================================================================
// TEST SUITE 4: Activity Cards
// ============================================================================

/**
 * Test Activity Card structure and functionality
 */
function testActivityCards() {
    console.log('\n=== Testing Activity Cards ===');

    const activityCards = document.querySelectorAll('.activity-card');
    console.log(`Found ${activityCards.length} activity cards`);

    const results = {
        total: activityCards.length,
        withBadges: 0,
        withHeaders: 0,
        collapsed: 0,
        issues: []
    };

    activityCards.forEach((card, index) => {
        // Check for index badge
        const badge = card.querySelector('.badge.bg-success');
        if (badge) {
            results.withBadges++;
        } else {
            results.issues.push(`Card ${index}: No index badge found`);
        }

        // Check for card header
        const header = card.querySelector('.activity-card-header');
        if (header) {
            results.withHeaders++;
        } else {
            results.issues.push(`Card ${index}: No header found`);
        }

        // Check if collapsed by default
        const form = card.querySelector('.activity-form');
        if (form) {
            const isHidden = form.style.display === 'none' ||
                           window.getComputedStyle(form).display === 'none' ||
                           form.classList.contains('d-none');
            if (isHidden) {
                results.collapsed++;
            }
        }
    });

    console.log(`Cards with badges: ${results.withBadges}/${results.total}`);
    console.log(`Cards with headers: ${results.withHeaders}/${results.total}`);
    console.log(`Cards collapsed by default: ${results.collapsed}/${results.total}`);

    if (results.issues.length > 0) {
        console.error('Issues:', results.issues);
    } else {
        console.log('✅ All activity cards properly structured');
    }

    return results;
}

/**
 * Test Activity Card toggle functionality
 */
function testActivityCardToggle() {
    console.log('\n=== Testing Activity Card Toggle ===');

    const firstCard = document.querySelector('.activity-card');
    if (!firstCard) {
        console.error('❌ No activity cards found');
        return { error: 'No activity cards found' };
    }

    const header = firstCard.querySelector('.activity-card-header');
    const form = firstCard.querySelector('.activity-form');

    if (!header || !form) {
        console.error('❌ Card structure incomplete');
        return { error: 'Card structure incomplete' };
    }

    // Get initial state
    const initialDisplay = window.getComputedStyle(form).display;
    console.log(`Initial form display: ${initialDisplay}`);

    // Toggle card
    if (typeof toggleActivityCard === 'function') {
        toggleActivityCard(header);

        setTimeout(() => {
            const afterToggleDisplay = window.getComputedStyle(form).display;
            console.log(`After toggle display: ${afterToggleDisplay}`);

            if (afterToggleDisplay !== initialDisplay) {
                console.log('✅ Toggle works correctly');
            } else {
                console.error('❌ Toggle did not change display state');
            }
        }, 100);
    } else {
        console.error('❌ toggleActivityCard function not found');
    }
}

// ============================================================================
// TEST SUITE 5: Photos Section
// ============================================================================

/**
 * Test Photos section indexing
 */
function testPhotosIndexing() {
    console.log('\n=== Testing Photos Section Indexing ===');

    const photoGroups = document.querySelectorAll('.photo-group');
    console.log(`Found ${photoGroups.length} photo groups`);

    const results = {
        total: photoGroups.length,
        indexed: 0,
        issues: []
    };

    photoGroups.forEach((group, index) => {
        const badge = group.querySelector('.badge.bg-info');
        if (badge) {
            const badgeText = badge.textContent.trim();
            const expectedIndex = index + 1;

            if (badgeText === expectedIndex.toString()) {
                results.indexed++;
            } else {
                results.issues.push(`Group ${index}: Badge shows "${badgeText}", expected "${expectedIndex}"`);
            }
        } else {
            results.issues.push(`Group ${index}: No badge found`);
        }
    });

    console.log(`Indexed correctly: ${results.indexed}/${results.total}`);
    if (results.issues.length > 0) {
        console.error('Issues:', results.issues);
    } else {
        console.log('✅ All photo groups properly indexed');
    }

    return results;
}

// ============================================================================
// TEST SUITE 6: Attachments Section
// ============================================================================

/**
 * Test Attachments section indexing
 */
function testAttachmentsIndexing() {
    console.log('\n=== Testing Attachments Section Indexing ===');

    const attachmentGroups = document.querySelectorAll('.attachment-group');
    console.log(`Found ${attachmentGroups.length} attachment groups`);

    const results = {
        total: attachmentGroups.length,
        indexed: 0,
        issues: []
    };

    attachmentGroups.forEach((group, index) => {
        const badge = group.querySelector('.badge.bg-secondary');
        if (badge) {
            const badgeText = badge.textContent.trim();
            const expectedIndex = index + 1;

            if (badgeText === expectedIndex.toString()) {
                results.indexed++;
            } else {
                results.issues.push(`Group ${index}: Badge shows "${badgeText}", expected "${expectedIndex}"`);
            }
        } else {
            results.issues.push(`Group ${index}: No badge found`);
        }

        // Check data-index attribute
        const dataIndex = group.getAttribute('data-index');
        if (dataIndex !== index.toString()) {
            results.issues.push(`Group ${index}: data-index is "${dataIndex}", expected "${index}"`);
        }
    });

    console.log(`Indexed correctly: ${results.indexed}/${results.total}`);
    if (results.issues.length > 0) {
        console.error('Issues:', results.issues);
    } else {
        console.log('✅ All attachment groups properly indexed');
    }

    return results;
}

// ============================================================================
// COMPREHENSIVE TEST RUNNER
// ============================================================================

/**
 * Run all tests
 */
async function runAllTests() {
    console.log('\n========================================');
    console.log('PHASE 11: COMPREHENSIVE TEST SUITE');
    console.log('========================================\n');

    // Test 1: Function Existence
    const functionTest = testFunctionExistence();

    // Test 2: Outlook Indexing
    const outlookTest = testOutlookIndexing();

    // Test 3: Statements of Account
    const accountTest = testStatementsOfAccountIndexing();

    // Test 4: Activity Cards
    const cardTest = testActivityCards();
    testActivityCardToggle();

    // Test 5: Photos
    const photosTest = testPhotosIndexing();

    // Test 6: Attachments
    const attachmentsTest = testAttachmentsIndexing();

    // Summary
    console.log('\n========================================');
    console.log('TEST SUMMARY');
    console.log('========================================');
    console.log(`Functions found: ${functionTest.found.length}/${functionTest.total}`);
    console.log(`Outlook entries indexed: ${outlookTest.indexed || 0}/${outlookTest.total || 0}`);
    console.log(`Account rows indexed: ${accountTest.indexed || 0}/${accountTest.total || 0}`);
    console.log(`Activity cards: ${cardTest.total || 0}`);
    console.log(`Photo groups indexed: ${photosTest.indexed || 0}/${photosTest.total || 0}`);
    console.log(`Attachment groups indexed: ${attachmentsTest.indexed || 0}/${attachmentsTest.total || 0}`);

    const allPassed =
        functionTest.missing.length === 0 &&
        (!outlookTest.issues || outlookTest.issues.length === 0) &&
        (!accountTest.issues || accountTest.issues.length === 0) &&
        (!cardTest.issues || cardTest.issues.length === 0) &&
        (!photosTest.issues || photosTest.issues.length === 0) &&
        (!attachmentsTest.issues || attachmentsTest.issues.length === 0);

    if (allPassed) {
        console.log('\n✅ ALL TESTS PASSED!');
    } else {
        console.log('\n⚠️ SOME TESTS FAILED - Review issues above');
    }

    return {
        functionTest,
        outlookTest,
        accountTest,
        cardTest,
        photosTest,
        attachmentsTest,
        allPassed
    };
}

// Auto-run tests if on report page
if (window.location.pathname.includes('/reports/monthly/') &&
    (window.location.pathname.includes('/create/') || window.location.pathname.includes('/edit'))) {
    console.log('Report page detected. Tests ready to run.');
    console.log('Run runAllTests() to execute all tests, or run individual test functions.');
}
