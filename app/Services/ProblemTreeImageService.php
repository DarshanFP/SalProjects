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
        Log::info('ProblemTreeImageService - Starting optimization', [
            'enabled' => $this->enabled,
            'max_dimension' => $this->maxDimension,
            'jpeg_quality' => $this->jpegQuality,
            'fallback_to_original' => $this->fallbackToOriginal,
            'original_filename' => $file->getClientOriginalName(),
            'original_size' => $file->getSize(),
        ]);

        if (!$this->enabled) {
            Log::info('ProblemTreeImageService - Optimization disabled, returning null');
            return null;
        }

        $path = $file->getRealPath();
        
        Log::info('ProblemTreeImageService - File path check', [
            'real_path' => $path,
            'path_exists' => $path !== false,
            'is_readable' => $path ? is_readable($path) : false,
        ]);

        if (!$path || !is_readable($path)) {
            Log::warning('ProblemTreeImageService - File not readable', [
                'path' => $path,
            ]);
            return null;
        }

        try {
            Log::info('ProblemTreeImageService - Creating ImageManager');
            $manager = ImageManager::gd();
            
            Log::info('ProblemTreeImageService - Reading image');
            $image = $manager->read($path);
            
            Log::info('ProblemTreeImageService - Image read successfully', [
                'width' => $image->width(),
                'height' => $image->height(),
            ]);
            
            Log::info('ProblemTreeImageService - Scaling down image');
            $image->scaleDown($this->maxDimension, $this->maxDimension);
            
            Log::info('ProblemTreeImageService - Image scaled', [
                'new_width' => $image->width(),
                'new_height' => $image->height(),
            ]);
            
            Log::info('ProblemTreeImageService - Encoding to JPEG');
            $encoded = $image->toJpeg(quality: $this->jpegQuality);
            
            $encodedSize = strlen((string) $encoded);
            $originalSize = $file->getSize();
            $reduction = $originalSize > 0 ? round((1 - ($encodedSize / $originalSize)) * 100, 2) : 0;

            Log::info('ProblemTreeImageService - Optimization completed successfully', [
                'original_size' => $originalSize,
                'optimized_size' => $encodedSize,
                'size_reduction_percent' => $reduction,
            ]);

            return (string) $encoded;
        } catch (\Throwable $e) {
            Log::warning('Problem Tree image optimization failed, will use original', [
                'path' => $path,
                'message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return $this->fallbackToOriginal ? null : throw $e;
        }
    }
}
