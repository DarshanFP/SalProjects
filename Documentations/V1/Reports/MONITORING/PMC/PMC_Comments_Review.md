# Comments by Project Monitoring Committee (PMC) — Review

**Purpose:** Add a **Comments by Project Monitoring Committee** textarea, to be filled by **provincial** before they **forward** the report to the coordinator. The block is placed **before** the existing Comments section.

**Scope:** Monthly report view (`reports.monthly.show`), provincial forward flow, `DP_Reports` / `DPReport`.

**Version:** 1.0  
**Location:** `Documentations/V1/Reports/MONITORING/PMC/PMC_Comments_Review.md`

---

## 1. Current State

### 1.1 Comments section (existing)

| Item | Detail |
|------|--------|
| **Partial** | `resources/views/reports/monthly/partials/comments.blade.php` |
| **Included in** | `resources/views/reports/monthly/show.blade.php` — `@include('reports.monthly.partials.comments')` |
| **Content** | (1) List of `$report->comments` (ReportComment: user, comment, date) and (2) “Add Comment” form for provincial/coordinator. |
| **Add Comment** | POST to `provincial.monthly.report.addComment` or `coordinator.monthly.report.addComment`; creates `ReportComment` (report_id, user_id, comment). |

### 1.2 Forward to Coordinator

| Item | Detail |
|------|--------|
| **Route** | `POST /provincial/report/{report_id}/forward` → `provincial.report.forward` |
| **Controller** | `ProvincialController::forwardReport(Request $request, $report_id)` |
| **Logic** | Access check → `ReportStatusService::forwardToCoordinator($report, $user)` → status set to `forwarded_to_coordinator` → redirect. |
| **UI** | “Forward to Coordinator” button opens modal; form has only “Are you sure?”, Report ID, Project, and `@csrf`. No textarea. |
| **Where modal exists** | `show.blade.php` (report show). Also used from: `ReportList.blade.php`, `pendingReports.blade.php`, `provincial/widgets/approval-queue.blade.php` — those use simple POST to `provincial.report.forward` (no modal or minimal confirm). |

### 1.3 Report model and table

| Item | Detail |
|------|--------|
| **Model** | `App\Models\Reports\Monthly\DPReport`, table `DP_Reports`. |
| **Relevant fields** | `status`, `revert_reason` (used for revert). **No** PMC-related column. |
| **Comments relation** | `$report->comments` → `ReportComment` (separate table `report_comments`). |

### 1.4 Placement in `show.blade.php`

Order in the view:

1. Basic info, type-specific, Objectives, Outlooks, SoA, Budget monitoring, Type-specific monitoring, Photos, Attachments  
2. **Activity Monitoring** (`activity_monitoring.blade.php`)  
3. `</div></div>` (close main column/row)  
4. **Download** (card-footer)  
5. **`@include('reports.monthly.partials.comments')`** — Comments list + Add Comment  
6. Action buttons (Back, Forward, Revert, Edit, Submit) and Forward/Revert modals  
7. Activity history  

**Required placement for PMC:** **before** `@include('reports.monthly.partials.comments')`, i.e. after Activity Monitoring / before Comments. Prefer **inside the main content column, after Activity Monitoring** and before the `</div>` that closes the col, so the order is: … Activity Monitoring → **PMC block** → `</div>` (col) → row close → card-footer (Download) → Comments. That keeps PMC with the report body; Comments stay after the Download button.

---

## 2. Requirements (Clarified)

| # | Requirement | Note |
|---|-------------|------|
| R1 | **Label** | “Comments by Project Monitoring Committee”. |
| R2 | **Control** | Textarea (multi-line). |
| R3 | **Filled by** | Provincial. |
| R4 | **When** | Before they forward the report to the coordinator. |
| R5 | **Placement** | Before the Comments section. |
| R6 | **Persistence** | Stored and shown on the report (view, PDF if needed). |

Open points to decide:

- **Required or optional:** Should forward be **blocked** if PMC comments are empty? Recommendation: **required** when forwarding from the **report show** page; document that from list/queue “forward” we can either require opening the report first or allow forward with empty PMC (with a follow-up to add PMC on show).
- **Who can see:** Provincial (edit when can forward; read when has access), Coordinator (read when status is `forwarded_to_coordinator` or later). Executor: optional (e.g. read-only after approval).

---

## 3. Data Model

### 3.1 Recommended: column on `DP_Reports`

Add to `DP_Reports`:

| Column | Type | Nullable | Purpose |
|--------|------|----------|---------|
| `pmc_comments` | `text` | yes | PMC comments text. |

Optional (for audit):

| Column | Type | Nullable | Purpose |
|--------|------|----------|---------|
| `pmc_comments_at` | `timestamp` | yes | When last saved. |
| `pmc_comments_user_id` | `foreignId` → users | yes | Who last saved. |

For a first version, **`pmc_comments` alone is enough**; we can add `_at` / `_user_id` later.

### 3.2 Alternative: separate table

A `report_pmc_comments` (or similar) table could store one row per report with `report_id`, `comments`, `user_id`, `created_at`, `updated_at`. That is useful if we later need **history** of PMC comments. For a single “PMC comments at forward” block, a column on `DP_Reports` is simpler.

---

## 4. UI Options

### 4.1 Option A — Standalone card + “Save PMC Comments” (before Comments)

- **New partial** (e.g. `reports/monthly/partials/view/pmc_comments.blade.php`):
  - **Read:** If `$report->pmc_comments` is not empty, show a card “Comments by Project Monitoring Committee” with the text (read-only for coordinator; for provincial, show the same when not in “edit” mode).
  - **Edit:** For **provincial** when status allows forward (`submitted_to_provincial`, `reverted_by_coordinator`, `reverted_by_general_as_coordinator`, `reverted_to_provincial`): textarea + “Save PMC Comments” button.
- **New route** (e.g. `POST /provincial/reports/monthly/{report_id}/save-pmc-comments`) to persist `pmc_comments`.
- **Forward:**
  - **Strict:** In `forwardReport`, validate `pmc_comments` is non-empty; if empty, redirect back with error. Provincial must save PMC first, then Forward.
  - **Lenient:** Allow forward with empty `pmc_comments`; optionally show a warning in the Forward modal.
- **Placement:** Include `pmc_comments` partial **after** Activity Monitoring, **before** `@include('reports.monthly.partials.comments')` in `show.blade.php`.

**Pros:** Clear separation: “PMC comments” vs “Comments” (thread). Provincial can save PMC before deciding to forward.  
**Cons:** Two steps (Save PMC, then Forward) unless we enforce “must be non-empty at forward”.

---

### 4.2 Option B — PMC textarea inside the Forward modal (report show only)

- **Forward modal** in `show.blade.php`: add a required textarea “Comments by Project Monitoring Committee” and `name="pmc_comments"`.
- **`ProvincialController::forwardReport`:**
  - `$request->validate(['pmc_comments' => 'required|string|max:5000']);`
  - `$report->pmc_comments = $request->pmc_comments;` (and optionally `pmc_comments_at`, `pmc_comments_user_id`)
  - `$report->save();`
  - Then `ReportStatusService::forwardToCoordinator($report, $user)`.
- **View block:** New partial “Comments by Project Monitoring Committee” **before** Comments: **read-only** display of `$report->pmc_comments` when non-empty (for provincial and coordinator as per visibility rules). No separate “edit” card; editing happens only in the Forward modal.
- **Placement:** Same as above: after Activity Monitoring, before Comments.

**Pros:** One action: fill PMC and Forward together; required-by-validation is easy.  
**Cons:** PMC can only be set at forward time. If provincial reopens a reverted report, they can only “add” more by re-forwarding (we could later allow editing when status is `reverted_by_coordinator` etc.).

**Forward from list/queue:** Those forms do not have the `pmc_comments` textarea. Options:
- **A)** Require `pmc_comments` in `forwardReport`: list/queue must be changed to open the report show (or a small modal with `pmc_comments`), or we accept that forward from list sends empty and then we block (or warn).  
- **B)** In `forwardReport`, treat `pmc_comments` as **optional**: if present, save it; if absent (list/queue), do not overwrite existing and do not require. Forward from **show** always sends `pmc_comments` (required in the modal). Forward from list/queue continues to work; PMC can be left empty or filled later on the report (if we add a separate “Save PMC” for reverted reports).

---

### 4.3 Option C — Hybrid

- **Standalone card** (as in Option A) for **view** and **edit** when provincial can forward (and when status is `reverted_by_coordinator` so they can amend before re-forwarding).
- **Forward modal:**  
  - **If `pmc_comments` is empty:** Show the textarea in the modal and require it (like Option B).  
  - **If already filled:** Modal can either show it as pre-filled (editable) or just a short note “PMC comments have been recorded” and no textarea.  
- **`forwardReport`:** If the request includes `pmc_comments`, save it (overwriting any previous). Validate required only when we want to enforce it (e.g. when coming from show’s modal and we decide to require).

**Pros:** Flexible: can save early in the card or at forward in the modal; supports reverted reports.  
**Cons:** More logic and UI cases.

---

## 5. Recommendation

- **Data:** Add **`pmc_comments`** (text, nullable) to `DP_Reports`; optionally `pmc_comments_at`, `pmc_comments_user_id` in a later step.
- **UI:**
  - **Option B** is the smallest change: one new block (read-only) before Comments + textarea in the **report show** Forward modal. Required in that modal.
  - For **forward from list/queue**, keep `pmc_comments` **optional** in `forwardReport`: if provided, save; if not, leave `pmc_comments` unchanged. That keeps list/queue working; we can later add a small “Forward” modal there with `pmc_comments` or encourage “View & Forward” from show.
- **Placement:** New partial included in `show.blade.php` **after** Activity Monitoring and **before** `@include('reports.monthly.partials.comments')`.

---

## 6. Files to Touch

### 6.1 Database

| File | Change |
|------|--------|
| **New migration** | `add_pmc_comments_to_dp_reports_table` (or similar): `Schema::table('DP_Reports', fn (Blueprint $t) => $t->text('pmc_comments')->nullable();)` |

### 6.2 Model

| File | Change |
|------|--------|
| `app/Models/Reports/Monthly/DPReport.php` | Add `pmc_comments` to `$fillable`. |

### 6.3 View

| File | Change |
|------|--------|
| **New** `resources/views/reports/monthly/partials/view/pmc_comments.blade.php` | Card “Comments by Project Monitoring Committee”: show `$report->pmc_comments` when present (read-only for allowed roles). |
| `resources/views/reports/monthly/show.blade.php` | Include `@include('reports.monthly.partials.view.pmc_comments')` **before** `@include('reports.monthly.partials.comments')` (after Activity Monitoring). |
| `resources/views/reports/monthly/show.blade.php` (Forward modal) | Add `<textarea name="pmc_comments" ... required>{{ old('pmc_comments', $report->pmc_comments ?? '') }}</textarea>` and label “Comments by Project Monitoring Committee”. |

### 6.4 Controller

| File | Change |
|------|--------|
| `app/Http/Controllers/ProvincialController.php` → `forwardReport()` | Accept `pmc_comments`; if present, `$report->pmc_comments = $request->pmc_comments; $report->save();` before `forwardToCoordinator`. Do **not** require when `pmc_comments` is missing (for list/queue). If we want to require **only when forwarded from show**, we need a flag (e.g. `$request->has('pmc_comments')` and a convention that show’s form always sends it; if we require, we could reject forward when it’s empty and the referrer is show—more complex). Simpler: **require in the show modal only via `required` and/or JS**; server-side treat as optional so list/queue still work. |

### 6.5 Export (optional)

| File | Change |
|------|--------|
| `app/Http/Controllers/Reports/Monthly/ExportReportController.php` and/or view used for PDF | If PMC comments must appear in the PDF, add a “Comments by Project Monitoring Committee” section and `$report->pmc_comments`. |

### 6.6 Other forwards (list/queue)

| File | Change |
|------|--------|
| `resources/views/provincial/ReportList.blade.php`, `pendingReports.blade.php`, `provincial/widgets/approval-queue.blade.php` | If we keep `pmc_comments` optional on the server: **no change** for a first version. Alternatively: replace “Forward” with a link to report show with `?forward=1` and focus the Forward modal, or add a small modal with `pmc_comments` and POST to `provincial.report.forward`. |

---

## 7. Visibility and Permissions

| Role | When | PMC block |
|------|------|-----------|
| **Provincial** | Can forward (e.g. `submitted_to_provincial`, `reverted_by_coordinator`, …) | See Forward modal with textarea; can fill and forward. View block: read `pmc_comments` when set. |
| **Provincial** | After forward / other statuses | View block: read `pmc_comments` when set. No edit. |
| **Coordinator** | `forwarded_to_coordinator` or later | View block: read `pmc_comments` when set. |
| **Executor / Applicant** | Any | Optional: hide, or show read-only after `approved_by_coordinator` (or never). Recommendation: **show read-only** once status is `forwarded_to_coordinator` or later, so executor can see provincial’s PMC comments after forward. |

---

## 8. Validation and UX

- **Report show Forward modal:**  
  - `required` on the `pmc_comments` textarea and/or `maxlength` (e.g. 5000) to match server.  
  - Optional: JS that disables “Forward to Coordinator” until the textarea is non-empty.
- **Server:** `'pmc_comments' => 'nullable|string|max:5000'` when present. If we later enforce “required when from show”, we need a way to detect that (e.g. hidden input `from_show=1` and then `required_if:from_show,1`).

---

## 9. Implementation Order (Suggested)

1. **Migration:** add `pmc_comments` to `DP_Reports`; run migrate.
2. **Model:** add `pmc_comments` to `DPReport::$fillable`.
3. **View block:** create `pmc_comments.blade.php` (read-only when `$report->pmc_comments` is not empty) and include it in `show.blade.php` before Comments.
4. **Forward modal:** add textarea `pmc_comments` to the Forward form in `show.blade.php`; `required` and `maxlength`.
5. **Controller:** in `forwardReport`, if `$request->filled('pmc_comments')`, assign and save before `forwardToCoordinator`.
6. **Export (optional):** add PMC to PDF.
7. **List/queue (optional):** decide whether to add `pmc_comments` to those forwards or to rely on “View & Forward” from show.

---

## 10. References

- **`Documentations/V1/Reports/MONITORING/PMC/PMC_Implementation_Plan.md`** — Phase-wise implementation plan for this feature.
- **`Documentations/V1/Reports/MONITORING/PMC/Implementation_Summary.md`** — Consolidated log of what was implemented in this chat.
- `resources/views/reports/monthly/show.blade.php`
- `resources/views/reports/monthly/partials/comments.blade.php`
- `app/Http/Controllers/ProvincialController.php` (`forwardReport`, `showMonthlyReport`)
- `app/Models/Reports/Monthly/DPReport.php`
- `app/Services/ReportStatusService.php` (`forwardToCoordinator`)
- `routes/web.php` (`provincial.report.forward`)

---

## 11. Implementation status

**Plan:** `PMC_Implementation_Plan.md`

| Phase | Description | Status |
|-------|-------------|--------|
| 1 | Migration: add `pmc_comments` to `DP_Reports` | ✅ Done |
| 2 | Model: add `pmc_comments` to `DPReport::$fillable` | ✅ Done |
| 3 | View partial `pmc_comments.blade.php`; include in `show.blade.php` before Comments | ✅ Done |
| 4 | Forward modal: required PMC textarea on report show | ✅ Done |
| 5 | `ProvincialController::forwardReport`: save `pmc_comments` when present | ✅ Done |
| 6 | (Optional) PDF export: include PMC in `PDFReport.blade.php` | ✅ Done |
| 7 | Integration, testing, documentation | ✅ Done |

**Manual testing:** As provincial, open a report with status `submitted_to_provincial` or `reverted_by_coordinator`, etc. (a) Forward modal shows required “Comments by Project Monitoring Committee” textarea; (b) fill and forward → `pmc_comments` saved, status → `forwarded_to_coordinator`; (c) PMC block appears after Activity Monitoring, before Comments, when `pmc_comments` is set. As coordinator/executor, PMC block visible when allowed by status. PDF: “Comments by Project Monitoring Committee” appears when set.

---

**End of PMC Comments Review**
