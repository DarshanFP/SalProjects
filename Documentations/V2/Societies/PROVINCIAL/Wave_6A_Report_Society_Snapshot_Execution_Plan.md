# Wave 6A — Report–Society Snapshot Migration & Backfill — Execution Plan

**Objective:** Add snapshot fields to reports table, backfill from projects, enforce NOT NULL after verification, align report creation, and enforce immutability. No production break; no unrelated report logic changes.

---

## Phase 0 — Pre-Check (READ-ONLY)

**Command:** `php artisan reports:society-snapshot-precheck`

- Inspects `DP_Reports` schema (project_id, society_name, society_id, province_id).
- Confirms Report belongsTo Project.
- Confirms `projects` has society_id, society_name, province_id.
- Counts total reports and reports with missing project (orphans).
- **Abort if orphan count > 0.** Log findings. Do not modify data.

---

## Phase 1 — Safe Migration (Nullable Fields)

**Migration:** `add_society_snapshot_to_dp_reports_table`

- Adds to `DP_Reports`:
  - `society_id` (unsignedBigInteger, nullable, after project_id)
  - `province_id` (unsignedBigInteger, nullable, after society_name)
  - Index on `society_id`
- Does **not** add FK or NOT NULL.

Deploy this migration, then proceed to Phase 2.

---

## Phase 2 — Backfill (Idempotent)

**Command:** `php artisan reports:backfill-society-snapshot [--chunk=200]`

- Chunks reports where `society_id IS NULL` (re-runnable).
- For each report: load project; set `society_id`, `society_name`, `province_id` from project; save in transaction.
- Logs and skips reports with no project (no destructive updates).

---

## Phase 3 — Data Verification

**Command:** `php artisan reports:verify-society-snapshot [--sample=50]`

1. Count reports where `society_id IS NULL` → must be **0**.
2. Random sample: compare `report.society_id` with `project.society_id` → must match.
3. Optionally verify aggregation (SUM by society_id) matches prior project-based logic.

**Do not run Phase 4 until this passes.**

---

## Phase 4 — Enforce Constraints

**Migration:** `enforce_report_society_snapshot_not_null_and_fk`

- Change `society_id`, `society_name`, `province_id` to NOT NULL.
- Add foreign key: `society_id` → `societies.id` ON DELETE RESTRICT.

**Note:** Requires `doctrine/dbal` for `change()` on some drivers. Install if needed: `composer require doctrine/dbal`.

---

## Phase 5 — Report Creation Alignment

- **Location:** `ReportController::createReport()` (monthly reports).
- At creation: load project by `project_id`; set `report->society_id`, `report->society_name`, `report->province_id` from project and save (snapshot at creation only).
- `society_id` is **not** accepted from request; validation unchanged for display purposes; snapshot is server-set from project only.

---

## Phase 6 — Immutability

- **DPReport model:** `society_id`, `society_name`, `province_id` are **not** in `$fillable` (never mass-assigned on update).
- **Report update:** Removed `society_name` from update array. Guard: if request contains `society_id` or `province_id` → `abort(403, 'Report society snapshot cannot be changed.')`.

---

## Rollback

- If backfill fails: do **not** run Phase 4. Fix orphans or data, re-run backfill, verify again.
- To roll back Phase 1: run migration down (drops index and society_id, province_id columns).
- Phase 4 down: drop FK, set columns back to nullable.

---

## Files Touched

| Item | Path |
|------|------|
| Pre-check command | `app/Console/Commands/ReportSocietySnapshotPreCheck.php` |
| Backfill command | `app/Console/Commands/BackfillReportSocietySnapshot.php` |
| Verify command | `app/Console/Commands/VerifyReportSocietySnapshot.php` |
| Migration Phase 1 | `database/migrations/*_add_society_snapshot_to_dp_reports_table.php` |
| Migration Phase 4 | `database/migrations/*_enforce_report_society_snapshot_not_null_and_fk.php` |
| Report creation | `app/Http/Controllers/Reports/Monthly/ReportController.php` (createReport, updateReport) |
| Model | `app/Models/Reports/Monthly/DPReport.php` (fillable, docblock) |

---

## Order of Execution

1. `php artisan reports:society-snapshot-precheck` → must pass.
2. `php artisan migrate` (Phase 1 migration).
3. `php artisan reports:backfill-society-snapshot`.
4. `php artisan reports:verify-society-snapshot` → must pass.
5. `php artisan migrate` (Phase 4 migration).
6. Phase 5 & 6 are code changes (already applied with this implementation).

Expected outcome: reports store a permanent society snapshot; historical classification is immutable; future project society changes do not alter existing reports; audit and aggregation integrity preserved.
