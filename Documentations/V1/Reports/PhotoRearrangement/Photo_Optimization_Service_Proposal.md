# Photo Optimization Service — WhatsApp-Style Minimal Size Storage

## 1. Goal

Reduce report photo file size to a **minimum** while keeping images **usable and unbroken**, similar to WhatsApp compression: smaller storage, faster uploads/downloads, and less risk of timeouts or memory issues. Images should remain clear enough for monitoring and review.

---

## 2. Why Not PNG for “Minimal Size”

- **PNG** is lossless and typically **larger** than JPEG for photos (often 2–5× for the same resolution).
- **JPEG** is lossy and gives much **smaller** files at acceptable quality for report photos.
- **WebP** can be 25–35% smaller than JPEG at similar quality; support is good in modern browsers and PHP GD.

**Recommendation:** Use **JPEG** as the default output (best balance of size, compatibility, and simplicity). Optionally support **WebP** for even smaller files where the stack supports it (e.g. GD/Imagick with WebP).

---

## 3. Strategy (WhatsApp-Like)

| Step | Purpose |
|------|---------|
| **1. Resize** | Cap the longest edge (e.g. 1920px or 1280px). Large phone/camera images (4K, 12MP) are reduced; small images are left as-is. |
| **2. Re-encode to JPEG** | Convert JPEG/PNG/HEIC/WebP etc. to JPEG with a fixed quality (e.g. 80–85). |
| **3. Cap file size** | Ensure the output is **≤ 350 KB**. If over, lower JPEG quality and/or resize more aggressively until under the limit. |
| **4. Strip metadata** | Remove EXIF (location, camera, etc.) to save a few KB and reduce privacy surface. *Note: GPS is extracted and stored before stripping.* |
| **5. Fallback** | If optimization fails (corrupt file, unsupported type), **store the original** so the report is not broken. |

---

## 4. Existing Stack

- **intervention/image** is already in `composer.json` (v3 with GD driver).
- **Intervention Image v3** provides:
  - `ImageManager` + `Gd\Driver`
  - `$manager->read($pathOrFile)` — accepts `UploadedFile::getRealPath()` or path
  - `$image->scaleDown($width, $height)` — scale down only, keep aspect; `scaleDown(1920, 1920)` effectively caps the longest edge at 1920
  - `$image->toJpeg(quality: 82)` — returns `EncodedImage`; `(string) $encoded` is the binary
  - `$image->profile()` / `$image->stripProfile()` — for removing metadata (if available in v3)
- No extra PHP extensions are required beyond GD; WebP is supported in GD on most modern PHP builds.

---

## 5. Service Design

### 5.1 Class and Location

```
app/Services/ReportPhotoOptimizationService.php
```

### 5.2 Public API

```php
interface ReportPhotoOptimizationServiceInterface
{
    /**
     * Optimize an uploaded image for report storage.
     * Resizes (max dimension), converts to JPEG, strips metadata.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return \App\Services\ReportPhotoOptimizationResult  { path: string, success: bool, usedFallback: bool, originalSize?: int, optimizedSize?: int }
     */
    public function optimizeForReport(\Illuminate\Http\UploadedFile $file): ReportPhotoOptimizationResult;
}
```

**Simpler alternative (stateless, no DTO):**

```php
/**
 * Returns optimized image binary (JPEG) and suggested extension.
 * On failure, returns null and the caller stores the original file.
 *
 * @param \Illuminate\Http\UploadedFile|string $file  UploadedFile or path
 * @return array{data: string, extension: string}|null  ['data' => binary, 'extension' => 'jpg'] or null to use original
 */
public function optimize($file): ?array;
```

**Recommended for minimal integration:** Return the **optimized binary + extension** so the controller can `Storage::put($path, $data)` and `storeAs` with the correct `.jpg`. On failure, return `null` and the controller uses the original file and name.

### 5.3 Config (optional)

Publish or add `config/report_photos.php`:

```php
return [
    'optimization' => [
        'enabled'       => env('REPORT_PHOTO_OPTIMIZATION', true),
        'max_dimension'     => (int) env('REPORT_PHOTO_MAX_DIMENSION', 1920),   // longest edge; 1280 for more savings
        'jpeg_quality'      => (int) env('REPORT_PHOTO_JPEG_QUALITY', 82),      // 75–85 typical; 70 more WhatsApp-like
        'max_file_size_kb'  => (int) env('REPORT_PHOTO_MAX_FILE_SIZE_KB', 350),  // hard cap; service reduces quality/size to meet it
        'strip_profile'     => (bool) env('REPORT_PHOTO_STRIP_PROFILE', true),
        'output_format'     => env('REPORT_PHOTO_OUTPUT_FORMAT', 'jpeg'),        // 'jpeg' | 'webp' (if available)
    ],
    'fallback_to_original_on_error' => true,
];
```

---

## 6. Implementation Sketch (Intervention v3)

```php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ReportPhotoOptimizationService
{
    public function __construct(
        protected int $maxDimension = 1920,
        protected int $jpegQuality = 82,
        protected int $maxFileSizeKb = 350,
        protected bool $stripProfile = true,
        protected bool $fallbackToOriginal = true,
    ) {}

    /**
     * Optimize image for report storage. Returns JPEG binary and extension, or null to use original.
     *
     * @return array{data: string, extension: string}|null
     */
    public function optimize(UploadedFile|string $file): ?array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        if (!$path || !is_readable($path)) {
            return null;
        }

        try {
            $manager = new ImageManager(new Driver());
            $image   = $manager->read($path);

            // 1) Strip metadata (EXIF) when supported
            if ($this->stripProfile && method_exists($image, 'stripProfile')) {
                $image->stripProfile();
            }

            // 2) Resize and encode to JPEG, enforcing max file size (default 350 KB)
            $limitBytes = $this->maxFileSizeKb * 1024;
            $qualities  = [$this->jpegQuality, 70, 60, 50];
            $dimensions = [$this->maxDimension, 1280, 960];

            foreach ($dimensions as $dim) {
                $image->scaleDown($dim, $dim);  // cap longest edge
                foreach ($qualities as $q) {
                    $encoded = $image->toJpeg(quality: $q);
                    $data    = (string) $encoded;
                    if (strlen($data) <= $limitBytes) {
                        return ['data' => $data, 'extension' => 'jpg'];
                    }
                }
            }
            // Fallback: use lowest quality/smallest size even if still over (avoid discarding)
            $encoded = $image->toJpeg(quality: 50);
            $data    = (string) $encoded;
            return ['data' => $data, 'extension' => 'jpg'];
        } catch (\Throwable $e) {
            Log::warning('Report photo optimization failed, will use original', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
            return $this->fallbackToOriginal ? null : throw $e;
        }
    }
}
```

- **`stripProfile`:** In Intervention v3 the method may be `profile(profile: null)` or `stripProfile()`; if not available, this step is a no-op.
- **`scaleDown(1920, 1920)`:** Keeps aspect ratio and ensures both sides ≤ 1920, so the longest edge is capped.
- **`toJpeg(quality: 82)`:** `JpegEncoder` accepts `quality` and `progressive`; 82 is a good default (slightly more compression than 85, still good for reports).
- **Return `null`:** Caller keeps the original file and `getClientOriginalName()` (or a safe variant). That way the report never “loses” a photo.

---

## 7. Output Filename and Path

- **Extension:** Use `'jpg'` when optimization succeeds; when it fails and we use the original, keep the extension from `$file->getClientOriginalName()` or sanitize to a safe one (e.g. `jpg`, `jpeg`, `png`).
- **Filename:** Keep a unique name to avoid overwrites. Current pattern:  
  `storeAs($folderPath, $file->getClientOriginalName(), 'public')`.  
  For optimized files, use the same folder and a **new base name** with `.jpg`, e.g.  
  `Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . Str::random(6) . '.jpg'`  
  or re-use the existing `$photo_id` and store as `{$photo_id}.jpg` if the photo_id is known before `storeAs`. The exact rule can follow the same uniqueness logic as today (e.g. `$file->getClientOriginalName()` but with `.jpg` when optimized).

---

## 8. Integration Points

### 8.1 Monthly Reports — `ReportController::handlePhotos()` and `updatePhotos()`

**Current:**

```php
$path = $file->storeAs($folderPath, $file->getClientOriginalName(), 'public');
DPPhoto::create([... 'photo_path' => $path, ...]);
```

**With service:**

```php
$result = app(ReportPhotoOptimizationService::class)->optimize($file);

if ($result !== null) {
    $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg';
    $path = $folderPath . '/' . $baseName; // or build a unique name
    Storage::disk('public')->put($path, $result['data']);
} else {
    $path = $file->storeAs($folderPath, $file->getClientOriginalName(), 'public');
}

DPPhoto::create([... 'photo_path' => $path, ...]);
```

- Ensure `$path` does not include a leading `/` if `Storage::disk('public')->put($path, $data)` expects a path relative to the disk root. `$folderPath` is e.g. `REPORTS/{project_id}/{report_id}/photos/{month_year}`; the final path can be `$folderPath . '/' . $baseName` and used in `put($fullPath, $data)`.

### 8.2 Monthly — `MonthlyDevelopmentProjectController` and `ReportAttachmentController`

Same pattern: before `storeAs` / `store`, call the service; if `optimize()` returns non-null, `Storage::put` with `.jpg` and use that path; otherwise keep the current behaviour.

### 8.3 Quarterly — Development, DevelopmentLivelihood, InstitutionalSupport, SkillTraining, WomenInDistress

All use `$file->store('ReportImages/Quarterly', 'public')` or similar. Replace with:

- `$result = $service->optimize($file);`
- If `$result`: build path under `ReportImages/Quarterly/` with a unique name and `.jpg`, then `Storage::disk('public')->put($path, $result['data'])`.
- If `null`: `$file->store('ReportImages/Quarterly', 'public')` as today.

### 8.4 Attachments

Report **attachments** are usually PDFs/Docs, not photos. Only apply the service to **image** uploads (e.g. in the “Photos” sections). If attachments also accept images, the same service can be used there; otherwise leave them as-is.

---

## 9. “Don’t Break” Behaviour

| Situation | Behaviour |
|-----------|-----------|
| Corrupt or unreadable file | `optimize()` returns `null` → store original. |
| Unsupported format (e.g. RAW) | Intervention may throw → catch, log, return `null` → store original. |
| GD / memory limit | Catch, log, return `null` → store original. |
| `stripProfile` not available | Omit that step; resize + encode still applied. |
| Image already tiny and low‑resolution | `scaleDown` is a no‑op when already smaller; re-encoding may still reduce a bit (e.g. PNG → JPEG) or slightly increase (already high‑compressed JPEG). |

---

## 10. Max File Size: 350 KB Cap (Required)

Stored report photos must be **≤ 350 KB** so that uploads, exports, and storage stay predictable.

**Algorithm (after resize and before return):**

1. Encode to JPEG at the configured quality (default 82). If `strlen($data) <= 350 * 1024`, return.
2. If over, re-encode at qualities **70, 60, 50** in turn; return as soon as under the limit.
3. If still over, `scaleDown` to **1280** and try qualities **82, 70, 60, 50** again.
4. If still over, `scaleDown` to **960** and try the same qualities.
5. If even the smallest size and lowest quality (960px, 50) is over 350 KB, **return that result anyway** so the photo is not dropped; the cap is best-effort for very dense images.

**Config:** `max_file_size_kb` (default **350**); override via `REPORT_PHOTO_MAX_FILE_SIZE_KB`.

---

## 11. Optional: WebP Output

For smaller files where WebP is available (GD with WebP, or Imagick):

- Use `$image->toWebp(quality: 80)` and `extension => 'webp'`.
- Ensure `photo_path` / `photo_name` and any `Content-Type` in responses use `image/webp`. Browsers and common PDF generators support WebP.

Implementation can be gated by `output_format === 'webp'` and a `function_exists('imagewebp')` (or driver) check.

---

## 12. Suggested Defaults

| Setting | Default | Comment |
|---------|---------|---------|
| `max_dimension` | 1920 | Good for screens and print-like viewing; 1280 for more aggressive size reduction. |
| `jpeg_quality` | 82 | Starting quality; lowered (70→60→50) if output exceeds `max_file_size_kb`. |
| `max_file_size_kb` | **350** | Hard cap; service reduces quality and/or resolution until under 350 KB (best-effort). |
| `strip_profile` | true | Removes EXIF after GPS is extracted; small extra gain and fewer privacy concerns. |
| `fallback_to_original_on_error` | true | Ensures reports never lose a photo when optimization fails. |
| `output_format` | `jpeg` | Safe and universal; `webp` when explicitly enabled and supported. |

---

## 13. Dependency and Registration

- **Composer:** `intervention/image` is already required; no change.
- **Laravel:** Register the service in `AppServiceProvider` or via a binding if you prefer an interface:

```php
$this->app->singleton(ReportPhotoOptimizationService::class, function () {
    $config = config('report_photos.optimization', []);
    return new ReportPhotoOptimizationService(
        maxDimension: $config['max_dimension'] ?? 1920,
        jpegQuality: $config['jpeg_quality'] ?? 82,
        maxFileSizeKb: $config['max_file_size_kb'] ?? 350,
        stripProfile: $config['strip_profile'] ?? true,
        fallbackToOriginal: config('report_photos.fallback_to_original_on_error', true),
    );
});
```

---

## 14. Summary

- **Service:** `App\Services\ReportPhotoOptimizationService::optimize(UploadedFile|string): ?array{data, extension}`.
- **Behaviour:** Resize (longest edge ≤ 1920), re-encode to JPEG, **cap output at 350 KB** (lower quality and/or 1280/960px if needed), strip EXIF when possible (after extracting GPS); on error return `null` and store the original.
- **Integration:** Run **before** every `storeAs`/`store` of report **photos** in monthly and quarterly controllers; use `Storage::put` when non-null, otherwise keep current logic.
- **Result:** Stored images ≤ 350 KB, smaller than originals in most cases, fewer timeouts and storage issues, while keeping photos usable and ensuring they **don’t break** when optimization fails.

---

*Document version: 1.0 — Part of Photo Rearrangement / Photo–Activity Mapping*
