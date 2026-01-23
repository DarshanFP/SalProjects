<?php

namespace Tests\Unit\Services;

use App\Services\ReportPhotoOptimizationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Phase C — Photo Rearrangement: ReportPhotoOptimizationService.
 *
 * @see \App\Services\ReportPhotoOptimizationService
 */
class ReportPhotoOptimizationServiceTest extends TestCase
{
    protected ReportPhotoOptimizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReportPhotoOptimizationService::class);
    }

    /** @test */
    public function optimize_returns_jpeg_data_and_extension_for_valid_image(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $result = $this->service->optimize($file);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('extension', $result);
        $this->assertArrayHasKey('location', $result);
        $this->assertSame('jpg', $result['extension']);
        $this->assertIsString($result['data']);
        $this->assertGreaterThan(0, strlen($result['data']));
        // Fake image has no EXIF GPS
        $this->assertNull($result['location']);
    }

    /** @test */
    public function optimize_returns_null_for_nonexistent_path(): void
    {
        $path = '/nonexistent/path/' . uniqid('test_') . '.jpg';

        $result = $this->service->optimize($path);

        $this->assertNull($result);
    }

    /** @test */
    public function optimize_returns_null_for_empty_string_path(): void
    {
        $result = $this->service->optimize('');

        $this->assertNull($result);
    }

    /** @test */
    public function optimize_returns_null_when_enabled_is_false(): void
    {
        $service = new ReportPhotoOptimizationService(enabled: false);
        $file = UploadedFile::fake()->image('photo.jpg', 50, 50);

        $result = $service->optimize($file);

        $this->assertNull($result);
    }

    /**
     * @test
     * @group exif
     * Requires a JPEG with EXIF GPS at tests/fixtures/sample_with_gps.jpg — run with --group=exif
     */
    public function optimize_includes_location_when_exif_gps_present(): void
    {
        $fixture = base_path('tests/fixtures/sample_with_gps.jpg');
        if (! is_file($fixture) || ! is_readable($fixture)) {
            $this->markTestSkipped('Fixture tests/fixtures/sample_with_gps.jpg not found (optional for EXIF tests)');
        }

        $result = $this->service->optimize($fixture);

        $this->assertIsArray($result);
        $this->assertSame('jpg', $result['extension']);
        $this->assertNotNull($result['location']);
        $this->assertMatchesRegularExpression('/^-?\d+\.\d+, -?\d+\.\d+$/', $result['location']);
    }
}
