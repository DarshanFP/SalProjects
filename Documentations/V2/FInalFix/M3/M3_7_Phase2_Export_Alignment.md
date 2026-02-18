# M3.7 — Phase 2: Export & PDF Alignment

**Scope:** Export controller (Word), PDF views. Presentation only; no resolver or DB changes.

---

## Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/ExportController.php` | `addGeneralInfoSection()`: stage-aware financial line — if `$project->isApproved()` show "Amount Sanctioned" with `$resolvedFundFields['amount_sanctioned']`; else show "Amount Requested" with `$resolvedFundFields['amount_requested']`. No direct DB usage; no inline formula. |
| `resources/views/projects/Oldprojects/pdf.blade.php` | Approval table row: label and value stage-aware — if approved, "Amount approved (Sanctioned)" and `$resolvedFundFields['amount_sanctioned']`; else "Amount Requested" and `$resolvedFundFields['amount_requested']`. |

---

## Before / After Financial Display Logic

### ExportController (Word export)

| Before | After |
|--------|--------|
| Single line: "Amount Sanctioned: {value}" using `$resolvedFundFields['amount_sanctioned']` for all projects. | If approved: "Amount Sanctioned: {amount_sanctioned}". If not approved: "Amount Requested: {amount_requested}". Values from resolver only. |

### PDF (Oldprojects/pdf.blade.php)

| Before | After |
|--------|--------|
| Row label "Amount approved (Sanctioned)" and value `$resolvedFundFields['amount_sanctioned']` for all. | Row label: "Amount approved (Sanctioned)" when approved, "Amount Requested" when not. Value: `amount_sanctioned` when approved, `amount_requested` when not. |

---

## Risk Analysis

| Risk | Mitigation |
|------|------------|
| Resolver not returning `amount_requested` | Phase 1 already adds `amount_requested` to resolver return; all consumers receive it. |
| Old Word/PDF cached | Export and PDF are generated on demand; no cache of document content. |
| `$project` missing in PDF view | `$project` and `$resolvedFundFields` are passed by controller/hydrator; no change to data flow. |
| Approved vs non-approved detection | Uses `$project->isApproved()` (existing method); no new dependency. |

---

## Validation Checklist

- [x] Export shows "Amount Sanctioned" only for approved projects.
- [x] Export shows "Amount Requested" only for non-approved projects.
- [x] No inline fallback formula in ExportController.
- [x] No direct DB usage of `amount_sanctioned` in export.
- [x] PDF label and value stage-aware using `$resolvedFundFields`.
- [x] Resolver not modified; only presentation logic changed.

---

## Manual QA Steps

1. **Word export — draft project**  
   Create or pick a non-approved project. Export to Word. In "Basic Information", expect one line "Amount Requested: Rs. X.XX" (no "Amount Sanctioned" line for that context). Opening Balance = forwarded + local.

2. **Word export — approved project**  
   Pick an approved project. Export to Word. Expect "Amount Sanctioned: Rs. X.XX". No "Amount Requested" line for that context.

3. **PDF — draft project**  
   Generate PDF for a non-approved project. In "APPROVAL" table, expect row label "Amount Requested" and value from resolver `amount_requested`.

4. **PDF — approved project**  
   Generate PDF for an approved project. Expect label "Amount approved (Sanctioned)" and value from resolver `amount_sanctioned`.

5. **Provincial project list**  
   Check summary: "Total Amount Sanctioned (Approved)" and "Total Amount Requested (Pending)" both present. Table column "Requested / Sanctioned" shows requested for draft, sanctioned for approved.

6. **Project show general info**  
   For draft: "Amount Requested" and "Amount Sanctioned" rows show requested value and 0 respectively. For approved: requested 0, sanctioned as per resolver.

---

**M3.7 Phase 2 Complete — Dashboards & Exports Aligned With Canonical Financial Semantics**
