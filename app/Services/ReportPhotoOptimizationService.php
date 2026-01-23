<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;

/**
 * Optimizes report photos: resize, re-encode to JPEG, cap at 350 KB, extract
 * EXIF GPS before stripping other metadata. On error returns null so the
 * caller can store the original.
 *
 * Phase 2 — Photo Rearrangement
 */
class ReportPhotoOptimizationService
{
    public function __construct(
        protected int $maxDimension = 1920,
        protected int $jpegQuality = 82,
        protected int $maxFileSizeKb = 350,
        protected bool $stripProfile = true,
        protected bool $fallbackToOriginal = true,
        protected bool $enabled = true,
    ) {}

    /**
     * Optimize image for report storage.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $file  UploadedFile or path
     * @return array{data: string, extension: string, location: ?string}|null  ['data' => binary, 'extension' => 'jpg', 'location' => ...] or null to use original
     */
    public function optimize(UploadedFile|string $file): ?array
    {
        if (! $this->enabled) {
            return null;
        }

        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        if (! $path || ! is_readable($path)) {
            return null;
        }

        $location = $this->extractGpsFromExif($path);

        try {
            $manager = ImageManager::gd();
            $image = $manager->read($path);

            if ($this->stripProfile && method_exists($image, 'removeProfile')) {
                $image->removeProfile();
            }

            $limitBytes = $this->maxFileSizeKb * 1024;
            $qualities = [$this->jpegQuality, 70, 60, 50];
            $dimensions = [$this->maxDimension, 1280, 960];

            foreach ($dimensions as $dim) {
                $image->scaleDown($dim, $dim);
                foreach ($qualities as $q) {
                    $encoded = $image->toJpeg(quality: $q);
                    $data = (string) $encoded;
                    if (strlen($data) <= $limitBytes) {
                        return ['data' => $data, 'extension' => 'jpg', 'location' => $location];
                    }
                }
            }

            $encoded = $image->toJpeg(quality: 50);
            $data = (string) $encoded;

            return ['data' => $data, 'extension' => 'jpg', 'location' => $location];
        } catch (\Throwable $e) {
            Log::warning('Report photo optimization failed, will use original', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return $this->fallbackToOriginal ? null : throw $e;
        }
    }

    /**
     * Extract GPS from EXIF and format as "lat, lng" or null if not present.
     */
    protected function extractGpsFromExif(string $path): ?string
    {
        if (! function_exists('exif_read_data')) {
            return null;
        }

        $exif = @exif_read_data($path, null, true);
        if (! is_array($exif)) {
            return null;
        }

        $gps = $exif['GPS'] ?? $exif;
        $latCoord = $gps['GPSLatitude'] ?? null;
        $lonCoord = $gps['GPSLongitude'] ?? null;
        if (empty($latCoord) || ! is_array($latCoord) || empty($lonCoord) || ! is_array($lonCoord)) {
            return null;
        }

        $lat = $this->exifCoordToDecimal($latCoord, (string) ($gps['GPSLatitudeRef'] ?? 'N'));
        $lon = $this->exifCoordToDecimal($lonCoord, (string) ($gps['GPSLongitudeRef'] ?? 'E'));

        if ($lat === null || $lon === null) {
            return null;
        }

        return sprintf('%.6f, %.6f', $lat, $lon);
    }

    /**
     * Convert EXIF GPS coordinate (deg, min, sec) to decimal. Ref S/W negates.
     */
    protected function exifCoordToDecimal(array $coord, string $ref): ?float
    {
        if (count($coord) < 3) {
            return null;
        }

        $d = $this->parseRational($coord[0] ?? 0);
        $m = $this->parseRational($coord[1] ?? 0);
        $s = $this->parseRational($coord[2] ?? 0);
        $decimal = $d + $m / 60 + $s / 3600;

        if (in_array(strtoupper(trim((string) $ref)), ['S', 'W'], true)) {
            $decimal = -$decimal;
        }

        return $decimal;
    }

    /**
     * @param  mixed  $v  int, float, or string "num/den"
     */
    protected function parseRational(mixed $v): float
    {
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }
        if (is_string($v) && str_contains($v, '/')) {
            $p = explode('/', $v, 2);
            $n = (float) ($p[0] ?? 0);
            $d = (float) ($p[1] ?? 1);

            return $d != 0 ? $n / $d : 0.0;
        }

        return (float) $v;
    }
}
