# Phase 3.3 — CCI Statistics Backfill Command

**Date implemented:** 2026-06-13  
**Plan reference:** Phase 3 optional data repair  
**Status:** ✅ Implemented

---

## Purpose

Optional repair for CCI projects that have **no row** in `project_CCI_statistics`. Edit forms now work without a DB row (Phase 3.1), but backfill creates persisted empty records for cleaner data and reporting consistency.

---

## Command

```bash
# Preview affected projects
php artisan projects:backfill-cci-statistics --dry-run

# Single project
php artisan projects:backfill-cci-statistics --dry-run --project=CCI-0001

# Execute (staging first)
php artisan projects:backfill-cci-statistics

# Single project execute
php artisan projects:backfill-cci-statistics --project=CCI-0001
```

---

## Behavior

1. Selects all projects where `project_type = CHILD CARE INSTITUTION`
2. Skips projects that already have `ProjectCCIStatistics`
3. Creates empty statistics row (`ProjectCCIStatistics::create(['project_id' => ...])`)
4. Auto-generates `CCI_statistics_id` via model `boot()` creating event

All actions logged to application log with `CCI statistics backfill` prefix.

---

## File added

| File | Description |
|------|-------------|
| `app/Console/Commands/BackfillCciStatisticsCommand.php` | Artisan command |

---

## When to run

- **Staging:** After Phase 3.1 deploy, run dry-run then execute for CCI-0001, CCI-0002
- **Production:** Optional; only if you want DB rows before users first save statistics section

Not required for edit form to work — Phase 3.1 empty-model pattern is sufficient for UX.

---

## Verification

```bash
grep "CCI statistics backfill" storage/logs/laravel.log
```

- [ ] Dry-run lists expected project IDs
- [ ] Execute creates rows; second run skips them
