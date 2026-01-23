# Report View — Visual Distinction for “Entered in Report” vs “From Project” Fields

**Phase 1 (Option E) implemented.** See `Phase_Wise_Implementation_Plan.md` for what was done and what remains (Individual SoA, print, toggle).

**Purpose:** Propose how to show a clear visual difference in the **report view** (for all users who can view reports) between:
- **Report-entered fields:** values the Executor/Applicant **enters in the report** (create/edit).
- **From-project fields:** values **fetched from the project** (or user) and shown as readonly in create/edit.

**Audience:** Developers, UX; view is used by Executor, Applicant, Provincial, Coordinator, General.  
**Location:** `Documentations/V1/Reports/MONITORING/`

---

## 1. Current Behaviour

### 1.1 Create and Edit (Executor/Applicant)

- **Entry/editable fields** are given a **distinct background** so users see where to type:
  - `style="background-color: #202ba3;"` on:
    - Basic info: **Total Beneficiaries**, **Reporting Month**, **Reporting Year**
    - Outlook: **Date**, **Action Plan for Next Month**
    - Photos: **file input**, **description**
    - Statements of account: **Expenses This Month** (and in “Add Additional Expense Row”: Particulars, Amount Sanctioned, Expenses This Month)
  - In Objectives: **no** `#202ba3`; the **editable** ones are: **Reporting Month** (per activity), **Summary of Activities**, **Qualitative & Quantitative Data**, **Intermediate Outcomes**; plus **What Did Not Happen**, **Why Not**, **Changes**, **Why Changes**, **Lessons Learnt**, **What Will Be Done Differently**. The **readonly** (from project) are: Objective, Expected Outcomes, Activity name, Scheduled Months.
- **From-project / readonly** fields use `readonly` and often `readonly-input` or no special background (e.g. Project Type, Project ID, Title, Place, Society, Commencement, In Charge; Goal; Objective; Expected Outcomes; Activity; in SoA: Particulars, Amount Sanctioned, Total Amount, Expenses Last Month, Total Expenses, Balance Amount on budget rows).

Some deployments or themes may use a **green border** (or similar) for entry fields; the important point is that create/edit already distinguish **“where to enter”** from **“from project”**.

### 1.2 View (All roles)

- **No visual distinction:** all values are shown in:
  - `info-grid`: `info-label` + `info-value`
  - `row` + `report-label-col` + `report-value-col` (or `col-2` / `col-10`)
  - Tables: plain `<td>`
- `report-view-hide-empty.js` hides empty label+value pairs but does **not** add any “entered” vs “from project” styling.

So reviewers (Provincial, Coordinator, etc.) cannot quickly see **which values were entered in the report** and which were brought from the project.

---

## 2. Classification: “Report‑entered” vs “From‑project” in View

Use the same idea as create/edit:

- **Report‑entered:** In create/edit these are **editable** (user types/selects). In view, the value is **from the report** (DPReport, DPObjective, DPActivity, DPOutlook, DPPhoto, DPAccountDetail, type‑specific tables).
- **From‑project:** In create/edit these are **readonly** and sourced from the project (or user). In view, we still take the value from the report record (report stores a copy at save), but we treat it as “from project” for **styling** so it’s clear it was not typed in the report form.

---

## 3. Field List by View Partial

### 3.1 Basic Information (`show.blade.php`)

| Field | Source | Classification |
|-------|--------|----------------|
| Project ID | report/project | From-project |
| Report ID | report | From-project (system) |
| Project Title | report | From-project |
| Report Month & Year | report | **Report-entered** |
| Project Type | report | From-project |
| Place | report | From-project |
| Society Name | report | From-project |
| Commencement Month & Year | report | From-project |
| Sister In Charge | report | From-project |
| Total Beneficiaries | report | **Report-entered** |

### 3.2 Objectives (`partials/view/objectives.blade.php`)

| Field | Classification |
|-------|----------------|
| Objective | From-project |
| Expected Outcome | From-project |
| What Did Not Happen | **Report-entered** |
| Why Some Activities Could Not Be Undertaken | **Report-entered** |
| Changes | **Report-entered** |
| Why Changes Were Needed | **Report-entered** |
| Lessons Learnt | **Report-entered** |
| What Will Be Done Differently | **Report-entered** |
| **Per activity:** Month | **Report-entered** |
| **Per activity:** Summary of Activities | **Report-entered** |
| **Per activity:** Qualitative & Quantitative Data | **Report-entered** |
| **Per activity:** Intermediate Outcomes | **Report-entered** |

(Activity *name* would be From-project; the current view does not show it per block—only Month and the three text fields.)

### 3.3 Outlooks (`show.blade.php`)

| Field | Classification |
|-------|----------------|
| Date | **Report-entered** |
| Action Plan for Next Month | **Report-entered** |

### 3.4 Statements of Account (view)

- **Overview (info-grid or cards):**  
  Account Period, Amount Sanctioned, Total Amount, Balance Forwarded, and Budget Summary—**from report/budget**; for styling we can treat overview as neutral or “from project” except any field the executor literally types (e.g. if one is editable, mark as report-entered).

- **Table (Budgets details):**

| Column | Budget rows | Additional rows |
|--------|-------------|-----------------|
| Particulars | From-project | **Report-entered** |
| Amount Sanctioned | From-project | **Report-entered** |
| Total Amount | From-project | From-project / calculated |
| Expenses Last Month | Carried from previous report | **Report-entered** (or carried) |
| Expenses This Month | **Report-entered** | **Report-entered** |
| Total Expenses | Calculated in report | **Report-entered** (calculated) |
| Balance Amount | Calculated in report | **Report-entered** (calculated) |

For a **simple first pass:** treat as **report-entered** the cells: **Expenses Last Month, Expenses This Month, Total Expenses, Balance Amount**. Optionally: for `is_budget_row = 0`, also **Particulars** and **Amount Sanctioned**.

### 3.5 Photos (`partials/view/photos.blade.php`)

| Field | Classification |
|-------|----------------|
| Image (file) | **Report-entered** |
| Description | **Report-entered** |

### 3.6 Attachments (`partials/view/attachments.blade.php`)

| Field | Classification |
|-------|----------------|
| Name, Description, File | **Report-entered** |

### 3.7 Type‑specific (LDP, IGE, RST, CIC)

- **LDP (LivelihoodAnnexure):** all annexure fields → **Report-entered**.
- **IGE (institutional_ongoing_group):** age_group, education, up_to_previous_year, present_academic_year → **Report-entered**.
- **RST (residential_skill_training):** education categories and total → **Report-entered**.
- **CIC (crisis_intervention_center):** age_category, status, number, total → **Report-entered**.

---

## 4. Proposed Visual Treatments for View

### 4.1 Option A — Left border on value (green)

- **Report-entered:** on the value `div` or `td`:
  - `border-left: 3px solid #05a34a;`  
  - Optional: `padding-left: 0.5rem;`
- **From-project:** no extra border.

**Pros:** Clear, small change, works with `info-grid` and `report-value-col`.  
**Cons:** In dense tables, many green borders; can be tuned with a lighter or thinner border.

---

### 4.2 Option B — Background tint (green/teal)

- **Report-entered:**
  - `background-color: rgba(5, 163, 74, 0.08);` or `#0d2d1a` (dark theme) / `#e8f5e9` (light).
- **From-project:** default background.

**Pros:** Easy to scan blocks.  
**Cons:** Can look heavy in large tables; needs to fit existing dark/light theme.

---

### 4.3 Option C — Icon + tooltip

- **Report-entered:** before the value, a small icon (e.g. `fa-pen` or `fa-edit`) with `title="Entered in report"`.
- **From-project:** optional `fa-folder` / “From project” or no icon.

**Pros:** Explicit, good for accessibility if `title`/`aria-label` are used.  
**Cons:** More markup and possible clutter in tables.

---

### 4.4 Option D — Label suffix (badge/text)

- **Report-entered:** after the label, e.g. `(Entered in report)` or a small badge.
- **From-project:** `(From project)` or nothing.

**Pros:** Very explicit.  
**Cons:** Repetitive; takes space.

---

### 4.5 Option E — Combination (recommended)

- **Report-entered:**
  - `border-left: 3px solid #05a34a;`
  - `background-color: rgba(5, 163, 74, 0.06);` (or `0.08`; adjust for dark theme)
  - `padding-left: 0.5rem;` where it doesn’t break layout
- **From-project:** no extra styling.

**Pros:** Quick to see “entered” blocks, aligns with “green = entry” if that’s used in create/edit.  
**Cons:** Slightly more CSS; theme colours may need to be adjusted.

---

## 5. Recommended Implementation

### 5.1 CSS class and styles

- Introduce a single class for **report‑entered** values:
  - **Class name:** `report-value-entered` (for `div`) or `report-cell-entered` (for `td`).

**Example CSS (light or dark):**

```css
/* Report View: values entered in the report (Executor/Applicant) */
.report-value-entered,
.report-cell-entered {
    border-left: 3px solid #05a34a;
    background-color: rgba(5, 163, 74, 0.08);
    padding-left: 0.5rem;
}

/* Dark theme variant (if report view uses dark backgrounds) */
.page-content .report-value-entered,
.page-content .report-cell-entered {
    background-color: rgba(5, 163, 74, 0.12);
}
```

- **From-project:** do **not** add a class; keep default.  
- Optional: `report-value-from-project` with a subtle grey left border if you want both to have a border (e.g. `border-left: 1px solid #555`).

### 5.2 Where to add the class

- **`info-grid`:** add `report-value-entered` to the `info-value` div for report‑entered fields.
- **`row` + `report-value-col`:** add `report-value-entered` to the `report-value-col` (or `col-10`) for report‑entered fields.
- **Tables (SoA, IGE, RST, CIC, LDP):** add `report-cell-entered` to the `<td>` for report‑entered cells. For SoA, that would be the `td`s for Expenses Last Month, Expenses This Month, Total Expenses, Balance Amount (and optionally Particulars/Amount Sanctioned for additional rows).

### 5.3 Legend

Add a short legend (e.g. at the top of the first card or in the Basic Information card) so all viewers understand:

> **Green accent** (left border + light background): value **entered in the report** by Executor/Applicant.  
> **No accent**: value **from project** (or system).

(If you adopt a “from-project” style, mention it in the legend.)

### 5.4 Empty report‑entered fields

- **Option 1:** Add `report-value-entered` whenever the field is report‑entered, even if the value is empty. The green accent still shows “this is an entered field” and helps reviewers see what was supposed to be filled.
- **Option 2:** Add the class only when the value is non‑empty.

Recommendation: **Option 1** for consistency. `report-view-hide-empty.js` can still hide empty pairs; for pairs that remain visible but empty, the accent clarifies the field’s role.

---

## 6. Files to Touch

| File | Change |
|------|--------|
| `resources/views/reports/monthly/show.blade.php` | Add `report-value-entered` to Basic Info report‑entered `info-value`s; add legend; include/define CSS for `report-value-entered` (or a small `report-view-entities.css`). |
| `resources/views/reports/monthly/partials/view/objectives.blade.php` | Add `report-value-entered` to `report-value-col` for: What Did Not Happen, Why Not, Changes, Why Changes, Lessons Learnt, What Will Be Done Differently; for each activity: Month, Summary, Qualitative & Quantitative, Intermediate Outcomes. |
| `resources/views/reports/monthly/show.blade.php` (Outlooks) | Add `report-value-entered` to `info-value` for Date and Action Plan for Next Month. |
| `resources/views/reports/monthly/partials/view/statements_of_account/development_projects.blade.php` | Add `report-cell-entered` to the `td`s for Expenses Last Month, Expenses This Month, Total Expenses, Balance Amount. (Optionally for extra rows: Particulars, Amount Sanctioned.) Do the same for `individual_*`, `institutional_education` if they use the same table structure. |
| `resources/views/reports/monthly/partials/view/photos.blade.php` | Add `report-value-entered` to the value/div that shows description (and, if applicable, the block that shows the image). |
| `resources/views/reports/monthly/partials/view/attachments.blade.php` | Add `report-value-entered` to the div/cell for name, description. |
| `resources/views/reports/monthly/partials/view/LivelihoodAnnexure.blade.php` | Add `report-value-entered` to the value divs for all annexure fields. |
| `resources/views/reports/monthly/partials/view/institutional_ongoing_group.blade.php` | Add `report-cell-entered` to the `td`s for Up to Previous Year, Present Academic Year. |
| `resources/views/reports/monthly/partials/view/residential_skill_training.blade.php` | Add `report-value-entered` to the `report-value-col` (or equivalent) for each education number and total. |
| `resources/views/reports/monthly/partials/view/crisis_intervention_center.blade.php` | Add `report-cell-entered` to the `td`s for Number (and, if you show it, Total). |

### 6.1 Shared CSS

- Either:
  - Inline `<style>` in `show.blade.php` (only for report view), or
  - `public/css/report-view-entities.css` (or similar) included only in the report show layout.

---

## 7. Tables: `report-cell-entered`

For `<td>`:

```html
<td class="report-cell-entered">{{ format_indian_currency($accountDetail->expenses_this_month, 2) }}</td>
```

Apply to all report‑entered columns identified in 3.4 and 3.7. Keep `table-info` or other existing classes on total row; you can add `report-cell-entered` to the total row’s report‑entered cells as well if desired.

---

## 8. Accessibility and Print

- **Colour:** The green left border still gives meaning when the background is removed or when colour is not available. For strict WCAG, ensure contrast of `#05a34a` on the surrounding background is sufficient.
- **Screen readers:** Optional: `aria-description` or a visually hidden text, e.g. “Entered in report”, on `report-value-entered` / `report-cell-entered`. Or rely on the **legend** in the page.
- **Print/PDF:** If the report is printed or exported to PDF, include the same CSS so the green accent appears in print. If you prefer a print-only “neutral” view, you can override with `@media print { .report-value-entered, .report-cell-entered { border-left-color: #666; background: transparent; } }` or similar.

---

## 9. Optional: Toggle “Show / hide report‑entered highlighting”

For users who find the green too strong, you can add a small toggle (e.g. “Highlight fields entered in report”) that adds/removes a class on a container, e.g. `data-highlight-entries="true"`. CSS would apply the green styles only when that attribute or class is present. Not required for the first version.

---

## 10. Summary

| Aspect | Proposal |
|--------|----------|
| **Create/Edit** | Already distinguish entry fields (e.g. `#202ba3` or green border). No change. |
| **View** | Add a clear distinction for **report‑entered** vs **from‑project**. |
| **Class** | `report-value-entered` for divs (`info-value`, `report-value-col`); `report-cell-entered` for table cells. |
| **Style** | Green left border + light green background (Option E). |
| **Legend** | Short text at top of view: “Green accent = entered in report; no accent = from project.” |
| **Scope** | Basic Info, Objectives, Outlooks, Statements of Account, Photos, Attachments, LDP/IGE/RST/CIC view partials. |
| **Empty fields** | Prefer applying the class to all report‑entered fields, even when empty. |
| **Scripts** | `report-view-hide-empty.js` can stay as is; it operates on visibility, not on the new classes. |

---

**End of proposal**
