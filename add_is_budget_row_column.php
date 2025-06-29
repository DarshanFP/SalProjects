<?php

// Script to add is_budget_row column to DP_AccountDetails table
// Run this on your production server

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    echo "Adding is_budget_row column to DP_AccountDetails table...\n";

    // Check if column already exists
    $hasColumn = Schema::hasColumn('DP_AccountDetails', 'is_budget_row');

    if ($hasColumn) {
        echo "Column 'is_budget_row' already exists in DP_AccountDetails table.\n";
    } else {
        // Add the column
        Schema::table('DP_AccountDetails', function (Blueprint $table) {
            $table->boolean('is_budget_row')->default(false)->after('balance_amount');
        });

        echo "Successfully added 'is_budget_row' column to DP_AccountDetails table.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
