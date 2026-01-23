<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;

/**
 * Resize and re-encode Problem Tree images to reduce file size.
 * Output is always JPEG. On error, returns null so the caller can store the original.
 */
class ProblemTreeImageService
{
    protected int $maxDimension;

    protected int $jpegQuality;

    protected bool $enabled;

    protected bool $fallbackToOriginal;

    public function __construct(
        ?int $maxDimension = null,
        ?int $jpegQuality = null,
        ?bool $enabled = null,
        bool $fallbackToOriginal = true,
    ) {
        $cfg = config('attachments.problem_tree_optimization', []);
        $this->maxDimension = $maxDimension ?? ($cfg['max_dimension'] ?? 1920);
        $this->jpegQuality = $jpegQuality ?? ($cfg['jpeg_quality'] ?? 85);
        $this->enabled = $enabled ?? ($cfg['enabled'] ?? true);
        $this->fallbackToOriginal = $fallbackToOriginal;
    }

    /**
     * Optimize image: resize (longest side maxDimension) and encode as JPEG.
     *
     * @return string|null  JPEG binary content, or null to use original
     */
    public function optimize(UploadedFile $file): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        $path = $file->getRealPath();
        if (!$path || !is_readable($path)) {
            return null;
        }

        try {
            $manager = ImageManager::gd();
            $image = $manager->read($path);
            $image->scaleDown($this->maxDimension, $this->maxDimension);
            $encoded = $image->toJpeg(quality: $this->jpegQuality);

            return (string) $encoded;
        } catch (\Throwable $e) {
            Log::warning('Problem Tree image optimization failed, will use original', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return $this->fallbackToOriginal ? null : throw $e;
        }
    }
}
