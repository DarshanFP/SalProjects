<?php

namespace App\Providers;

use App\Services\ReportPhotoOptimizationService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()//: void
    {
        $this->app->singleton(ReportPhotoOptimizationService::class, function () {
            $opt = config('report_photos.optimization', []);
            return new ReportPhotoOptimizationService(
                maxDimension: $opt['max_dimension'] ?? 1920,
                jpegQuality: $opt['jpeg_quality'] ?? 82,
                maxFileSizeKb: $opt['max_file_size_kb'] ?? 350,
                stripProfile: $opt['strip_profile'] ?? true,
                fallbackToOriginal: config('report_photos.fallback_to_original_on_error', true),
                enabled: $opt['enabled'] ?? true,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()//: void
    {
        //
        Paginator::useBootstrap();
    }
}
