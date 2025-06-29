<?php

// Bootstrap Laravel
require_once 'vendor/autoload.php';

// Load environment variables
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Adding revert_reason column to DP_Reports table...\n";

try {
    // Get the database connection
    $db = app('Illuminate\Database\ConnectionInterface');

    // Check if the column already exists
    $columns = $db->select("SHOW COLUMNS FROM DP_Reports LIKE 'revert_reason'");

    if (empty($columns)) {
        // Add the column
        $db->statement("ALTER TABLE DP_Reports ADD COLUMN revert_reason TEXT NULL AFTER status");
        echo "Column 'revert_reason' added successfully!\n";
    } else {
        echo "Column 'revert_reason' already exists.\n";
    }

    // Verify the column was added
    $columns = $db->select("SHOW COLUMNS FROM DP_Reports LIKE 'revert_reason'");
    if (!empty($columns)) {
        echo "Verification: Column 'revert_reason' is now present in DP_Reports table.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Migration completed successfully!\n";
