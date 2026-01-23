# PMC Comments — Phase-Wise Implementation Plan

**Source:** `Documentations/V1/Reports/MONITORING/PMC/PMC_Comments_Review.md`  
**Scope:** Comments by Project Monitoring Committee — textarea filled by **provincial** before forwarding to coordinator; read-only block **before** the Comments section; PMC in the **Forward modal** on report show (Option B).  
**Version:** 1.0  
**Location:** `Documentations/V1/Reports/MONITORING/PMC/PMC_Implementation_Plan.md`

---

## Summary

| Phase | Name | Status | Est. effort |
|-------|------|--------|-------------|
| **1** | Migration: add `pmc_comments` to `DP_Reports` | ✅ Done | 0.25 h |
| **2** | Model: add `pmc_comments` to `DPReport` | ✅ Done | 0.25 h |
| **3** | View partial: `pmc_comments.blade.php` and include in `show.blade.php` | ✅ Done | 0.5 h |
| **4** | Forward modal: add PMC textarea to report show | ✅ Done | 0.5 h |
| **5** | Controller: `forwardReport` — save `pmc_comments` when present | ✅ Done | 0.5 h |
| **6** | (Optional) PDF export: include PMC in report PDF | ✅ Done | 0.5 h |
| **7** | Integration, testing, documentation | ✅ Done | 0.5–1 h |

**Total (Phases 1–5, 7):** ~2.5–3 h. **With Phase 6:** ~3–3.5 h.

**Design choices (from Review):**

- **Option B:** Read-only PMC block + textarea in the **report show** Forward modal; required in the modal via `required`; server-side `pmc_comments` **optional** so forward from list/queue continues to work.
- **Visibility:** Provincial and Coordinator see the block when `pmc_comments` is set; Executor sees it read-only when status is `forwarded_to_coordinator` or later.

---

## Phase 1 — Migration: Add `pmc_comments` to `DP_Reports`

**PMC_Comments_Review.md:** §3.1, §6.1

### 1.1 Goals

- Add a nullable `pmc_comments` (text) column to `DP_Reports` to store Comments by Project Monitoring Committee.

### 1.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 1.1 | Create migration `add_pmc_comments_to_dp_reports_table` | `database/migrations/YYYY_MM_DD_HHMMSS_add_pmc_comments_to_dp_reports_table.php` | `Schema::table('DP_Reports', function (Blueprint $table) { $table->text('pmc_comments')->nullable(); });` In `down()`: `$table->dropColumn('pmc_comments');` |
| 1.2 | Run `php artisan migrate` | — | Verify `DP_Reports` has `pmc_comments`. |

### 1.3 Dependencies

- None.

### 1.4 Output

- `DP_Reports.pmc_comments` (text, nullable) exists.

---

## Phase 2 — Model: Add `pmc_comments` to `DPReport`

**PMC_Comments_Review.md:** §6.2

### 2.1 Goals

- Allow mass assignment of `pmc_comments` so the controller can `$report->pmc_comments = $request->pmc_comments; $report->save();`.

### 2.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 2.1 | Add `pmc_comments` to `$fillable` | `app/Models/Reports/Monthly/DPReport.php` | Insert in the `$fillable` array (e.g. after `revert_reason`). |
| 2.2 | (Optional) Add `@property` in docblock for IDE | `DPReport.php` | `@property string|null $pmc_comments` |

### 2.3 Dependencies

- Phase 1 done (column exists).

### 2.4 Output

- `DPReport` can be updated with `pmc_comments`.

---

## Phase 3 — View Partial: `pmc_comments.blade.php` and Include in `show.blade.php`

**PMC_Comments_Review.md:** §4.2, §5, §6.3, §7

### 3.1 Goals

- Create a **read-only** card “Comments by Project Monitoring Committee” that displays `$report->pmc_comments` when it is not empty.
- Show the card only to roles that are allowed to see PMC: **provincial**, **coordinator**, and **executor/applicant** when status is `forwarded_to_coordinator` or later (e.g. `approved_by_coordinator`).
- Place the partial **after** Activity Monitoring and **before** the `</div>` that closes the main column, i.e. **before** the Comments section in reading order.

### 3.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 3.1 | **Create** `resources/views/reports/monthly/partials/view/pmc_comments.blade.php` | New file | **Content:** (a) `@php`: `$pmc = trim((string)($report->pmc_comments ?? ''));`; `$show = $pmc !== '';` (b) Visibility: `$canSee = in_array(auth()->user()->role ?? '', ['provincial','coordinator']) || (in_array(auth()->user()->role ?? '', ['executor','applicant']) && in_array($report->status ?? '', ['forwarded_to_coordinator','reverted_by_coordinator','approved_by_coordinator','approved_by_general_as_coordinator','approved_by_general_as_provincial']));` (c) `@if($show && $canSee)`: card with header “Comments by Project Monitoring Committee”, body: `{!! nl2br(e($pmc)) !!}`. (d) `@endif`. |
| 3.2 | **Include** the partial in `show.blade.php` | `resources/views/reports/monthly/show.blade.php` | Place **after** `@include('reports.monthly.partials.view.activity_monitoring')` (line 114) and **before** the `</div>` that closes the col (line 115). Insert: `@include('reports.monthly.partials.view.pmc_comments')`. The partial uses `$report` from the parent view; no extra data needed. |

### 3.3 Dependencies

- Phase 2 done (`$report->pmc_comments` exists on the model and is loaded in `show`).

### 3.4 Output

- Read-only PMC block appears after Activity Monitoring and before the Download / Comments when `pmc_comments` is set and the user may see it.

---

## Phase 4 — Forward Modal: Add PMC Textarea (Report Show)

**PMC_Comments_Review.md:** §4.2, §6.3, §8

### 4.1 Goals

- Add a **required** textarea “Comments by Project Monitoring Committee” to the **Forward to Coordinator** modal in `show.blade.php`.
- Pre-fill with `$report->pmc_comments` when re-forwarding (e.g. after revert). Use `old('pmc_comments', $report->pmc_comments ?? '')` for validation re-display.
- Enforce `required` and `maxlength` (e.g. 5000) in the HTML; optional: JS to disable the submit button until the textarea is non-empty.

### 4.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 4.1 | Add label and textarea to the Forward modal’s `<div class="modal-body">` | `resources/views/reports/monthly/show.blade.php` | Locate the form `action="{{ route('provincial.report.forward', $report->report_id) }}"` and the `modal-body`. **Before** the existing `<p>Are you sure...?</p>`, add: `<div class="mb-3"><label for="pmc_comments_show" class="form-label">Comments by Project Monitoring Committee <span class="text-danger">*</span></label><textarea name="pmc_comments" id="pmc_comments_show" class="form-control" rows="4" required maxlength="5000" placeholder="Enter PMC comments before forwarding.">{{ old('pmc_comments', $report->pmc_comments ?? '') }}</textarea><div class="form-text">Required before forwarding to Coordinator.</div></div>`. Then keep the existing confirm text. |
| 4.2 | (Optional) JS: disable “Forward to Coordinator” in the modal until `#pmc_comments_show` is non-empty | `show.blade.php` or a separate JS file | Use `input`/`change` on the textarea; toggle `button[type="submit"]` in that modal. For MVP, `required` alone is enough. |

### 4.3 Dependencies

- Phase 2 done (`$report->pmc_comments` available in the view). The Forward modal is shown only when `$canForward` is true (provincial, status in `submitted_to_provincial`, `reverted_by_coordinator`, etc.), so `$report` is in scope.

### 4.4 Output

- Provincial must fill the PMC textarea in the Forward modal on the report show page before submitting. Re-forward: previous `pmc_comments` pre-filled, editable.

---

## Phase 5 — Controller: `forwardReport` — Save `pmc_comments` When Present

**PMC_Comments_Review.md:** §4.2, §6.4, §8

### 5.1 Goals

- In `ProvincialController::forwardReport`, when the request contains `pmc_comments`, validate it (nullable, string, max 5000) and save to `$report` **before** calling `ReportStatusService::forwardToCoordinator`.
- **Do not** require `pmc_comments` on the server so that forward from **list/queue** (which does not send `pmc_comments`) continues to work. When absent, leave `$report->pmc_comments` unchanged.

### 5.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 5.1 | Validate `pmc_comments` when present | `app/Http/Controllers/ProvincialController.php` → `forwardReport()` | After loading `$report` and before `forwardToCoordinator`, add: `$request->validate(['pmc_comments' => 'nullable|string|max:5000']);` (or only validate when `$request->has('pmc_comments')` to avoid overwriting with empty: if we want to allow “clear”, we can; for now, only update when `filled`). |
| 5.2 | If `$request->filled('pmc_comments')`, assign and save | `ProvincialController::forwardReport()` | `if ($request->filled('pmc_comments')) { $report->pmc_comments = $request->pmc_comments; $report->save(); }` — placed **before** `ReportStatusService::forwardToCoordinator($report, $provincial)`. |

### 5.3 Dependencies

- Phase 2 done. `forwardReport` is used by: (a) report show Forward modal (sends `pmc_comments`), (b) list/queue forms (do not send `pmc_comments`). With `filled()` check, (b) is unaffected.

### 5.4 Output

- Forward from **report show** with `pmc_comments` in the form: value is saved on `DP_Reports` then status is updated. Forward from list/queue: no change to `pmc_comments`; forward proceeds as today.

---

## Phase 6 — (Optional) PDF Export: Include PMC in Report PDF

**PMC_Comments_Review.md:** §6.5

### 6.1 Goals

- If PMC comments must appear in the generated report PDF, add a “Comments by Project Monitoring Committee” section and `$report->pmc_comments` (when non-empty).

### 6.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 6.1 | Locate where the report PDF is built | `app/Http/Controllers/Reports/Monthly/ExportReportController.php`, `resources/views/reports/monthly/PDFReport.blade.php` (or similar) | Identify the template or logic that renders “Comments” or the final sections before download. |
| 6.2 | Add a conditional block for PMC | PDF view or ExportReportController | If `trim((string)($report->pmc_comments ?? '')) !== ''`: render a section “Comments by Project Monitoring Committee” and the text (e.g. `nl2br(e($report->pmc_comments))` for HTML or the library’s equivalent). Place it **before** any “Comments” (ReportComment) section to match the web view order. |

### 6.3 Dependencies

- Phase 2 done. Export controller and view must receive `$report` with `pmc_comments` (usually already loaded).

### 6.4 Output

- PDF includes “Comments by Project Monitoring Committee” when `pmc_comments` is set.

---

## Phase 7 — Integration, Testing, and Documentation

### 7.1 Integration

| # | Task | Notes |
|---|------|-------|
| 7.1 | Ensure `$report` in `show` is loaded with `pmc_comments` | `DP_Reports` columns are typically selected by default; no need to add to `select` if `ReportController::show` (and `ProvincialController::showMonthlyReport` → `ReportController::show`) load the full report. If a custom `select` is used, add `pmc_comments`. |
| 7.2 | Confirm Forward modal and `forwardReport` use the same `report_id` | Form `action="{{ route('provincial.report.forward', $report->report_id) }}"` and `forwardReport($report_id)` — already aligned. |

### 7.2 Testing

| # | Task | Notes |
|---|------|-------|
| 7.3 | **PMC block visibility:** (a) `pmc_comments` empty → block hidden. (b) `pmc_comments` set, provincial/coordinator → block visible, read-only. (c) `pmc_comments` set, executor, status `forwarded_to_coordinator` or `approved_by_coordinator` → block visible, read-only. (d) Executor, status `submitted_to_provincial` → block hidden. | |
| 7.4 | **Forward modal (report show):** (a) Open Forward modal, leave textarea empty, submit → browser `required` blocks or server returns validation error if we add server-side required for this path. (Current: `required` in HTML; server `nullable` so if required is bypassed, an empty string would be saved — we can add `'pmc_comments' => 'required_if:from_show,1'` and hidden `from_show=1` only in show’s form if we want server enforce. For MVP, HTML `required` is enough.) (b) Fill textarea, submit → `pmc_comments` saved, status → `forwarded_to_coordinator`. (c) Re-forward after revert: textarea pre-filled; can edit and save. | |
| 7.5 | **Forward from list/queue:** Forms that POST to `provincial.report.forward` without `pmc_comments` → forward succeeds; `pmc_comments` on the report unchanged (empty or previously set). | |
| 7.6 | **Placement:** PMC block appears after Activity Monitoring, before Download and Comments. | |

### 7.3 Documentation

| # | Task | Notes |
|---|------|-------|
| 7.7 | In **`PMC_Comments_Review.md`**, add a short “Implementation status” (e.g. §11 or after References): phases 1–5, 7 (and 6 if done) with ✅ when complete. | |
| 7.8 | In **`PMC_Implementation_Plan.md`** (this file), update the Summary **Status** column as each phase is done. | |

---

## File Checklist

### New files

| File | Phase |
|------|-------|
| `database/migrations/YYYY_MM_DD_HHMMSS_add_pmc_comments_to_dp_reports_table.php` | 1 |
| `resources/views/reports/monthly/partials/view/pmc_comments.blade.php` | 3 |

### Modified files

| File | Phase | Changes |
|------|-------|---------|
| `app/Models/Reports/Monthly/DPReport.php` | 2 | Add `pmc_comments` to `$fillable`; optional `@property` |
| `resources/views/reports/monthly/show.blade.php` | 3, 4 | **3:** `@include('reports.monthly.partials.view.pmc_comments')` after Activity Monitoring, before `</div>` (col). **4:** In Forward modal `modal-body`, add label + `<textarea name="pmc_comments" ... required maxlength="5000">{{ old('pmc_comments', $report->pmc_comments ?? '') }}</textarea>`. |
| `app/Http/Controllers/ProvincialController.php` | 5 | In `forwardReport()`: validate `pmc_comments` when present; if `filled('pmc_comments')`, set `$report->pmc_comments` and `$report->save()` before `forwardToCoordinator`. |
| `app/Http/Controllers/Reports/Monthly/ExportReportController.php` and/or `resources/views/reports/monthly/PDFReport.blade.php` (or equivalent) | 6 | Add “Comments by Project Monitoring Committee” when `$report->pmc_comments` is non-empty. |
| `Documentations/V1/Reports/MONITORING/PMC/PMC_Comments_Review.md` | 7 | Implementation status section. |
| `Documentations/V1/Reports/MONITORING/PMC/PMC_Implementation_Plan.md` | 7 | Update Summary Status. |

---

## Execution Order

1. **Phase 1** — Migration: add `pmc_comments` to `DP_Reports`; run `migrate`.
2. **Phase 2** — Model: add `pmc_comments` to `DPReport::$fillable`.
3. **Phase 3** — Create `pmc_comments.blade.php`; include it in `show.blade.php` after Activity Monitoring, before the column `</div>`.
4. **Phase 4** — In the Forward modal (report show), add the required PMC textarea.
5. **Phase 5** — In `ProvincialController::forwardReport`, if `pmc_comments` is present: validate, assign, save; then `forwardToCoordinator`.
6. **Phase 6 (optional)** — Add PMC to the report PDF.
7. **Phase 7** — Integration checks, manual testing, documentation updates.

---

## Rollback

- **Migration:** `php artisan migrate:rollback` (if the migration is last) or a dedicated `down()` that drops `pmc_comments`.
- **Model:** Remove `pmc_comments` from `$fillable`.
- **Views:** Remove the `@include` for `pmc_comments` and the textarea from the Forward modal.
- **Controller:** Remove the `pmc_comments` validate/assign/save block from `forwardReport`.
- **PDF:** Remove the PMC block from the export view/controller.

---

## References

- `Documentations/V1/Reports/MONITORING/PMC/PMC_Comments_Review.md`
- `Documentations/V1/Reports/MONITORING/PMC/Implementation_Summary.md` — Consolidated implementation log from this chat.
- `resources/views/reports/monthly/show.blade.php`
- `resources/views/reports/monthly/partials/comments.blade.php`
- `app/Http/Controllers/ProvincialController.php` (`forwardReport`, `showMonthlyReport`)
- `app/Models/Reports/Monthly/DPReport.php`
- `app/Services/ReportStatusService.php` (`forwardToCoordinator`)
- `routes/web.php` (`provincial.report.forward`)

---

**End of PMC Implementation Plan**
