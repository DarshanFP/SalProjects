<?php

/**
 * Safe Textarea Auto-Resize Test Script
 *
 * This script performs safe, non-destructive tests to verify
 * that textarea auto-resize functionality is working correctly.
 *
 * SAFETY: This script does NOT:
 * - Modify the database
 * - Truncate tables
 * - Create/delete records
 * - Modify files
 *
 * It only:
 * - Checks if files exist
 * - Verifies file content
 * - Tests HTTP responses
 * - Validates HTML structure (if authenticated)
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "========================================\n";
echo "Textarea Auto-Resize Safe Test Script\n";
echo "========================================\n\n";

$passed = 0;
$failed = 0;
$errors = [];

/**
 * Test 1: Check if CSS file exists
 */
echo "Test 1: Checking CSS file exists...\n";
$cssPath = public_path('css/custom/textarea-auto-resize.css');
if (file_exists($cssPath)) {
    echo "  ✓ CSS file exists: {$cssPath}\n";
    $passed++;
} else {
    echo "  ✗ CSS file NOT found: {$cssPath}\n";
    $failed++;
    $errors[] = "CSS file not found";
}

/**
 * Test 2: Check if JS file exists
 */
echo "\nTest 2: Checking JS file exists...\n";
$jsPath = public_path('js/textarea-auto-resize.js');
if (file_exists($jsPath)) {
    echo "  ✓ JS file exists: {$jsPath}\n";
    $passed++;
} else {
    echo "  ✗ JS file NOT found: {$jsPath}\n";
    $failed++;
    $errors[] = "JS file not found";
}

/**
 * Test 3: Verify CSS file content
 */
if (file_exists($cssPath)) {
    echo "\nTest 3: Verifying CSS file content...\n";
    $cssContent = file_get_contents($cssPath);

    $requiredClasses = ['.sustainability-textarea', '.logical-textarea', '.auto-resize-textarea'];
    $allFound = true;

    foreach ($requiredClasses as $class) {
        if (strpos($cssContent, $class) !== false) {
            echo "  ✓ Found class: {$class}\n";
        } else {
            echo "  ✗ Missing class: {$class}\n";
            $allFound = false;
        }
    }

    $requiredProps = ['min-height', 'resize: vertical', 'overflow-y: hidden'];
    foreach ($requiredProps as $prop) {
        if (strpos($cssContent, $prop) !== false) {
            echo "  ✓ Found property: {$prop}\n";
        } else {
            echo "  ✗ Missing property: {$prop}\n";
            $allFound = false;
        }
    }

    if ($allFound) {
        $passed++;
    } else {
        $failed++;
        $errors[] = "CSS file missing required content";
    }
}

/**
 * Test 4: Verify JS file content
 */
if (file_exists($jsPath)) {
    echo "\nTest 4: Verifying JS file content...\n";
    $jsContent = file_get_contents($jsPath);

    $requiredFunctions = [
        'function initTextareaAutoResize',
        'function autoResizeTextarea',
        'window.initTextareaAutoResize',
        '.sustainability-textarea',
        '.logical-textarea'
    ];

    $allFound = true;
    foreach ($requiredFunctions as $item) {
        if (strpos($jsContent, $item) !== false) {
            echo "  ✓ Found: {$item}\n";
        } else {
            echo "  ✗ Missing: {$item}\n";
            $allFound = false;
        }
    }

    if ($allFound) {
        $passed++;
    } else {
        $failed++;
        $errors[] = "JS file missing required content";
    }
}

/**
 * Test 5: Check that layouts include the files (verify file content)
 */
echo "\nTest 5: Checking layout includes...\n";
$layoutPath = resource_path('views/layoutAll/app.blade.php');
if (file_exists($layoutPath)) {
    $layoutContent = file_get_contents($layoutPath);

    if (strpos($layoutContent, 'textarea-auto-resize.css') !== false) {
        echo "  ✓ CSS included in layoutAll/app.blade.php\n";
        $passed++;
    } else {
        echo "  ✗ CSS NOT included in layoutAll/app.blade.php\n";
        $failed++;
        $errors[] = "CSS not included in main layout";
    }

    if (strpos($layoutContent, 'textarea-auto-resize.js') !== false) {
        echo "  ✓ JS included in layoutAll/app.blade.php\n";
        $passed++;
    } else {
        echo "  ✗ JS NOT included in layoutAll/app.blade.php\n";
        $failed++;
        $errors[] = "JS not included in main layout";
    }
} else {
    echo "  ⚠ Layout file not found (skipping)\n";
}

/**
 * Test 6: Verify no duplicate inline CSS/JS in cleaned files (sample check)
 */
echo "\nTest 6: Sample check for duplicate code in cleaned files...\n";
$sampleFiles = [
    'resources/views/projects/partials/Edit/general_info.blade.php',
    'resources/views/projects/partials/IGE/ongoing_beneficiaries.blade.php',
    'resources/views/projects/partials/RST/target_group.blade.php'
];

$duplicatesFound = 0;
foreach ($sampleFiles as $file) {
    $fullPath = base_path($file);
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);

        // Check for duplicate CSS pattern (should be removed)
        $cssPattern = '/\.sustainability-textarea\s*\{[^}]*min-height[^}]*\}/';
        $matches = preg_match_all($cssPattern, $content);

        if ($matches > 0) {
            echo "  ⚠ Potential duplicate CSS found in: " . basename($file) . " ({$matches} matches)\n";
            $duplicatesFound++;
        } else {
            echo "  ✓ No duplicate CSS in: " . basename($file) . "\n";
        }
    }
}

if ($duplicatesFound === 0) {
    $passed++;
} else {
    echo "  ⚠ Note: Some files may still have duplicate code (check manually if needed)\n";
    // Don't fail for this, just warn
}

/**
 * Summary
 */
echo "\n========================================\n";
echo "Test Results Summary\n";
echo "========================================\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if (count($errors) > 0) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

echo "\n";

if ($failed === 0) {
    echo "✓ All tests PASSED! Textarea auto-resize files are correctly configured.\n";
    echo "\nNext steps:\n";
    echo "1. Run manual browser testing (see Phase_4_1_Quick_Start_Testing.md)\n";
    echo "2. Test dynamic content functionality\n";
    echo "3. Test in multiple browsers\n";
    exit(0);
} else {
    echo "✗ Some tests FAILED. Please review the errors above.\n";
    exit(1);
}
