# Test fixtures

## `sample_with_gps.jpg` (optional)

Used by `Tests\Unit\Services\ReportPhotoOptimizationServiceTest::optimize_includes_location_when_exif_gps_present`.

- **Purpose:** Assert that `ReportPhotoOptimizationService::optimize()` returns a non‑empty `location` when the input image has EXIF GPS.
- **Format:** JPEG with EXIF GPS (e.g. from a phone or `exiftool -gps:all=`).
- **Location:** `tests/fixtures/sample_with_gps.jpg`

If this file is missing, the test is **skipped** (4 other tests still run). To enable it, add any small JPEG that contains GPS in EXIF.
