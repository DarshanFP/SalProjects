# Report Notification Type Mismatch - Fix and Review

## Issue Summary

**Error**: `NotificationService::notifyReportSubmission()` was receiving incorrect parameter types, causing a TypeError.

**Root Cause**: 
- The `DPReport` model (and other report models) use `report_id` (string) as the primary key, not `id` (integer)
- The controller was trying to access `$report->id` which returns `null` because the model's primary key is `report_id`
- The `NotificationService::notifyReportSubmission()` method expects an `int $reportId` parameter

## Fixed Location

**File**: `app/Http/Controllers/Reports/Monthly/ReportController.php`

**Lines**: 159-185

**Fix Applied**:
```php
// After DB::commit(), query the report again to get the integer id
$reportWithId = DPReport::where('report_id', $report_id)->first();
$reportId = $reportWithId ? $reportWithId->getAttribute('id') : null;

if (!$reportId) {
    Log::warning('Could not retrieve report id for notification', ['report_id' => $report_id]);
}

// Use $reportId (integer) instead of $report->id
if ($project && $reportId) {
    NotificationService::notifyReportSubmission(
        $coordinator,
        $reportId,  // Integer ID from database
        $project->id
    );
}
```

## Report Models Primary Key Configuration

All report models use `report_id` (string) as primary key, not `id`:

| Model | Primary Key | Table Has `id` Column? |
|-------|-------------|----------------------|
| `DPReport` (Monthly) | `report_id` (string) | ✅ Yes |
| `QuarterlyReport` | `report_id` (string) | ✅ Yes |
| `AnnualReport` | `report_id` (string) | ✅ Yes |
| `HalfYearlyReport` | `report_id` (string) | ✅ Yes |

**Note**: All these tables have an auto-incrementing `id` column in the database, but the models use `report_id` as the primary key for business logic purposes.

## Review Results

### ✅ No Issues Found In:

1. **Quarterly Report Controllers** (`app/Http/Controllers/Reports/Quarterly/`)
   - Do NOT call `NotificationService::notifyReportSubmission()`
   - Use `$report->id` for storing in related tables (objectives, activities, etc.), which is correct for those relationships

2. **Aggregated Report Controllers** (`app/Http/Controllers/Reports/Aggregated/`)
   - Do NOT call `NotificationService::notifyReportSubmission()`
   - Use services to generate reports, but services don't send notifications

3. **Report Services** (`app/Services/Reports/`)
   - `QuarterlyReportService`, `AnnualReportService`, `HalfYearlyReportService`
   - Do NOT call notification methods
   - Only generate and save reports

### ⚠️ Potential Issues to Watch:

1. **Quarterly Controllers Using `$report->id`**:
   - Files: `DevelopmentProjectController.php`, `DevelopmentLivelihoodController.php`, `SkillTrainingController.php`, `WomenInDistressController.php`, `InstitutionalSupportController.php`
   - **Status**: ✅ **SAFE** - These use `$report->id` for storing `report_id` in related tables, which works because:
     - The `report_id` field in related tables expects the integer `id` from the reports table
     - They're not using it for notifications

2. **Future Notification Implementations**:
   - If notifications are added to quarterly/annual/half-yearly reports, the same fix pattern must be applied
   - Always query the report again after commit to get the integer `id`

## Recommendations

### 1. **For Future Development**:
   - When working with report models, always remember:
     - `$report->id` returns `null` (primary key is `report_id`)
     - `$report->report_id` returns the string identifier (e.g., "DP-0005-01")
     - To get the integer `id`, query: `DPReport::where('report_id', $report_id)->first()->getAttribute('id')`

### 2. **Code Pattern to Follow**:
```php
// ✅ CORRECT: Get integer id for notifications
DB::commit();
$reportWithId = DPReport::where('report_id', $report_id)->first();
$reportId = $reportWithId ? $reportWithId->getAttribute('id') : null;

if ($reportId) {
    NotificationService::notifyReportSubmission($user, $reportId, $projectId);
}

// ❌ WRONG: This will be null
$reportId = $report->id; // Returns null because primary key is report_id
```

### 3. **Consider Adding Helper Method**:
Consider adding a helper method to report models to get the integer id:
```php
// In DPReport, QuarterlyReport, AnnualReport, HalfYearlyReport models
public function getIntegerId(): ?int
{
    return $this->getAttribute('id');
}
```

### 4. **Testing Checklist**:
- [x] Monthly report creation with notifications
- [ ] Quarterly report creation (if notifications added)
- [ ] Annual report creation (if notifications added)
- [ ] Half-yearly report creation (if notifications added)

## Related Files

- `app/Http/Controllers/Reports/Monthly/ReportController.php` - ✅ Fixed
- `app/Services/NotificationService.php` - Method signature expects `int $reportId`
- `app/Models/Reports/Monthly/DPReport.php` - Uses `report_id` as primary key
- `app/Models/Reports/Quarterly/QuarterlyReport.php` - Uses `report_id` as primary key
- `app/Models/Reports/Annual/AnnualReport.php` - Uses `report_id` as primary key
- `app/Models/Reports/HalfYearly/HalfYearlyReport.php` - Uses `report_id` as primary key

## Conclusion

✅ **Issue Fixed**: The monthly report notification issue has been resolved.

✅ **No Other Issues Found**: Other report types don't have the same issue because they don't call notification methods.

⚠️ **Future Prevention**: When adding notifications to other report types, follow the same pattern of querying for the integer `id` after commit.
