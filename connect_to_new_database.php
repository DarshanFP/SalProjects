<?php

/**
 * Script to connect old app to new database and verify connection
 * Run with: php connect_to_new_database.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

// Bootstrap Laravel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

echo "🔌 Database Connection Verification\n";
echo "===================================\n\n";

// Display current database configuration
echo "📋 Current Database Configuration:\n";
echo "---------------------------------\n";
echo "DB_CONNECTION: " . Config::get('database.default') . "\n";
echo "DB_HOST: " . Config::get('database.connections.mysql.host') . "\n";
echo "DB_PORT: " . Config::get('database.connections.mysql.port') . "\n";
echo "DB_DATABASE: " . Config::get('database.connections.mysql.database') . "\n";
echo "DB_USERNAME: " . Config::get('database.connections.mysql.username') . "\n";
echo "DB_PASSWORD: " . (Config::get('database.connections.mysql.password') ? '***' : '(empty)') . "\n";
echo "\n";

// Test database connection
echo "🔍 Testing Database Connection...\n";
echo "--------------------------------\n";

try {
    DB::connection()->getPdo();
    echo "✅ Database connection successful!\n\n";

    // Get database name
    $databaseName = DB::connection()->getDatabaseName();
    echo "📊 Connected to database: {$databaseName}\n\n";

    // Test a simple query
    echo "🧪 Running test query...\n";
    $result = DB::select('SELECT DATABASE() as db, USER() as user, VERSION() as version');
    if (!empty($result)) {
        echo "   Database: {$result[0]->db}\n";
        echo "   User: {$result[0]->user}\n";
        echo "   MySQL Version: {$result[0]->version}\n";
    }
    echo "\n";

    // Check if key tables exist
    echo "📋 Checking key tables...\n";
    $tables = ['users', 'provinces', 'centers', 'projects'];
    foreach ($tables as $table) {
        try {
            $count = DB::table($table)->count();
            echo "   ✅ {$table}: {$count} records\n";
        } catch (\Exception $e) {
            echo "   ⚠️  {$table}: Table not found or error - " . $e->getMessage() . "\n";
        }
    }
    echo "\n";

    echo "🎉 All checks passed! Your app is connected to the new database.\n";

} catch (\Exception $e) {
    echo "❌ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "💡 Troubleshooting steps:\n";
    echo "   1. Verify .env file has correct credentials\n";
    echo "   2. Run: php artisan config:clear\n";
    echo "   3. Check if Remote MySQL is enabled in hosting panel\n";
    echo "   4. Verify database hostname is correct\n";
    echo "   5. Check firewall/security settings\n";
    exit(1);
}
