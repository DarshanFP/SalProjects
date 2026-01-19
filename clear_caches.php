<?php

/**
 * Script to clear all Laravel caches
 * Run with: php clear_caches.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

echo "🧹 Clearing Laravel Caches\n";
echo "==========================\n\n";

try {
    // Clear config cache
    echo "📋 Clearing config cache...\n";
    $output = [];
    Artisan::call('config:clear', [], $output);
    echo "   ✅ Config cache cleared\n\n";

    // Clear application cache
    echo "💾 Clearing application cache...\n";
    Artisan::call('cache:clear', [], $output);
    echo "   ✅ Application cache cleared\n\n";

    // Clear route cache
    echo "🛣️  Clearing route cache...\n";
    Artisan::call('route:clear', [], $output);
    echo "   ✅ Route cache cleared\n\n";

    // Clear view cache
    echo "👁️  Clearing view cache...\n";
    Artisan::call('view:clear', [], $output);
    echo "   ✅ View cache cleared\n\n";

    // Clear compiled files manually
    echo "🔨 Clearing compiled files...\n";
    $compiledPath = base_path('bootstrap/cache');
    if (File::exists($compiledPath)) {
        $files = File::files($compiledPath);
        $deleted = 0;
        foreach ($files as $file) {
            $filename = $file->getFilename();
            if ($filename !== '.gitignore' && !str_starts_with($filename, '.')) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }
        echo "   ✅ Deleted {$deleted} compiled file(s)\n\n";
    } else {
        echo "   ℹ️  No compiled files found\n\n";
    }

    echo "🎉 All caches cleared successfully!\n";
    echo "\n";
    echo "💡 Your app is now ready to use the new database.\n";
    echo "   Try accessing your app in the browser to verify everything works.\n";

} catch (\Exception $e) {
    echo "❌ Error clearing caches: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
