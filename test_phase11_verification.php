<?php
/**
 * Phase 11: Code Verification Script
 *
 * This script verifies that all required functions and structures
 * are present in the report views files.
 *
 * Run: php test_phase11_verification.php
 */

$basePath = __DIR__ . '/resources/views/reports/monthly/';

$filesToCheck = [
    // Main views
    'ReportAll.blade.php' => [
        'functions' => ['reindexOutlooks', 'addOutlook', 'removeOutlook'],
        'checks' => ['badge bg-primary', 'Outlook']
    ],
    'edit.blade.php' => [
        'functions' => ['reindexOutlooks', 'addOutlook', 'removeOutlook'],
        'checks' => ['badge bg-primary', 'Outlook']
    ],

    // Common partials - create
    'partials/create/objectives.blade.php' => [
        'functions' => ['reindexActivities', 'toggleActivityCard', 'updateActivityStatus', 'addActivity', 'removeActivity'],
        'checks' => ['activity-card', 'badge bg-success', 'activity-status']
    ],
    'partials/create/photos.blade.php' => [
        'functions' => ['reindexPhotoGroups', 'addPhotoGroup', 'removePhotoGroup'],
        'checks' => ['badge bg-info', 'Photo Group']
    ],
    'partials/create/attachments.blade.php' => [
        'functions' => ['reindexAttachments', 'addAttachment', 'removeAttachment'],
        'checks' => ['badge bg-secondary', 'Attachment File']
    ],

    // Common partials - edit
    'partials/edit/objectives.blade.php' => [
        'functions' => ['reindexActivities', 'toggleActivityCard', 'updateActivityStatus', 'addActivity', 'removeActivity'],
        'checks' => ['activity-card', 'badge bg-success', 'activity-status']
    ],
    'partials/edit/photos.blade.php' => [
        'functions' => ['reindexPhotoGroups', 'addPhotoGroup', 'removePhotoGroup'],
        'checks' => ['badge bg-info', 'Photo Group']
    ],
    'partials/edit/attachments.blade.php' => [
        'functions' => ['reindexNewAttachments', 'addNewAttachment', 'removeNewAttachment'],
        'checks' => ['badge bg-secondary', 'Attachment File']
    ],

    // Statements of account - create
    'partials/create/statements_of_account.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/statements_of_account/development_projects.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/statements_of_account/individual_livelihood.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/statements_of_account/individual_health.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/statements_of_account/individual_education.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/statements_of_account/individual_ongoing_education.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/statements_of_account/institutional_education.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],

    // Statements of account - edit
    'partials/edit/statements_of_account.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/edit/statements_of_account/development_projects.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/edit/statements_of_account/individual_livelihood.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/edit/statements_of_account/individual_health.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/edit/statements_of_account/individual_education.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/edit/statements_of_account/individual_ongoing_education.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],
    'partials/edit/statements_of_account/institutional_education.blade.php' => [
        'functions' => ['reindexAccountRows', 'addAccountRow', 'removeAccountRow'],
        'checks' => ['<th>No.</th>', 'account-rows']
    ],

    // Project type specific
    'partials/create/LivelihoodAnnexure.blade.php' => [
        'functions' => ['dla_updateImpactGroupIndexes', 'dla_addImpactGroup', 'dla_removeImpactGroup'],
        'checks' => ['badge bg-warning', 'Impact']
    ],
];

$issues = [];
$warnings = [];
$passed = [];

echo "=== Phase 11 Code Verification ===\n\n";

foreach ($filesToCheck as $file => $requirements) {
    $fullPath = $basePath . $file;

    if (!file_exists($fullPath)) {
        $issues[] = "❌ File not found: $file";
        continue;
    }

    $content = file_get_contents($fullPath);
    $fileIssues = [];
    $fileWarnings = [];

    // Check for required functions
    foreach ($requirements['functions'] as $function) {
        if (strpos($content, "function $function") === false &&
            strpos($content, "$function(") === false) {
            $fileIssues[] = "Missing function: $function()";
        }
    }

    // Check for required HTML/CSS elements
    foreach ($requirements['checks'] as $check) {
        if (strpos($content, $check) === false) {
            $fileWarnings[] = "Missing check: '$check'";
        }
    }

    // Check for console.log statements (should be removed in Phase 12)
    if (preg_match('/console\.(log|error|warn)/i', $content)) {
        $fileWarnings[] = "Contains console.log statements (cleanup needed)";
    }

    if (empty($fileIssues) && empty($fileWarnings)) {
        $passed[] = "✅ $file";
    } else {
        if (!empty($fileIssues)) {
            foreach ($fileIssues as $issue) {
                $issues[] = "❌ $file: $issue";
            }
        }
        if (!empty($fileWarnings)) {
            foreach ($fileWarnings as $warning) {
                $warnings[] = "⚠️  $file: $warning";
            }
        }
    }
}

// Print results
if (!empty($passed)) {
    echo "✅ Files verified successfully:\n";
    foreach ($passed as $pass) {
        echo "   $pass\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  Warnings:\n";
    foreach ($warnings as $warning) {
        echo "   $warning\n";
    }
    echo "\n";
}

if (!empty($issues)) {
    echo "❌ Issues found:\n";
    foreach ($issues as $issue) {
        echo "   $issue\n";
    }
    echo "\n";
}

// Summary
$totalFiles = count($filesToCheck);
$passedCount = count($passed);
$warningCount = count($warnings);
$issueCount = count($issues);

echo "=== Summary ===\n";
echo "Total files checked: $totalFiles\n";
echo "✅ Passed: $passedCount\n";
echo "⚠️  Warnings: $warningCount\n";
echo "❌ Issues: $issueCount\n";

if ($issueCount === 0 && $warningCount === 0) {
    echo "\n🎉 All files verified successfully!\n";
} elseif ($issueCount === 0) {
    echo "\n✅ All critical checks passed. Warnings are non-blocking.\n";
} else {
    echo "\n❌ Please fix issues before proceeding to Phase 12.\n";
    exit(1);
}

echo "\n";
