# Project Type Partials — Create, Edit, Show Audit Review

**Date:** 2026-02-09  
**Scope:** All project types — Create, Edit, and Show partials  
**Goal:** Identify Show partials that incorrectly use Edit-style content (form elements, "Edit:" labels, etc.)

---

## Executive Summary

Three project types have **Show partials** that display Edit-style content instead of true read-only views:

| Project Type | Affected Partial(s) | Issue Severity |
|--------------|---------------------|----------------|
| **Individual - Ongoing Educational support (IES)** | `Show/IES/estimated_expenses.blade.php` | **High** — inputs (readonly), `old()`, JavaScript |
| **Residential Skill Training (RST)** | `Show/RST/target_group.blade.php` | **Medium** — disabled input |
| **Individual - Livelihood Application (ILP)** | `Show/ILP/budget.blade.php` | **High** — Direct copy of Edit partial; "Edit Budget" heading, Add/Remove buttons, editable JS |

**Note:** IES `family_working_members`, `immediate_family_details`, and `educational_background` were **already refactored** in a previous session.

---

## 1. Architecture Overview

### 1.1 Partial Structure by Context

| Context | Blade File | Partials Used | Expected Behaviour |
|---------|------------|---------------|--------------------|
| **Create** | `Oldprojects/createProjects.blade.php` | Root partials (e.g. `IES.*`, `ILP.*`) | Form inputs; no "Edit:" prefix |
| **Edit** | `Oldprojects/edit.blade.php` | `Edit.*` partials | Form inputs; "Edit:" prefix is correct |
| **Show** | `Oldprojects/show.blade.php` | `Show.*` partials | Read-only; no form elements; no "Edit:" prefix |

### 1.2 Project Types with Type-Specific Partials

| Project Type | Show Partials | Edit Partials | Create Partials |
|--------------|---------------|---------------|-----------------|
| CHILD CARE INSTITUTION (CCI) | Show.CCI.* | Edit.CCI.* | CCI.* |
| PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER (CIC) | Show.CIC.* | Edit.CIC.* | CIC.* |
| Rural-Urban-Tribal (Edu-RUT) | Show.Edu-RUT.* | Edit.Edu-RUT.* | Edu-RUT.* |
| Residential Skill Training (RST) | Show.RST.* | Edit.RST.* | RST.* |
| Individual - Ongoing Educational (IES) | Show.IES.* | Edit.IES.* | IES.* |
| Individual - Initial Educational (IIES) | Show.IIES.* | Edit.IIES.* | IIES.* |
| Individual - Livelihood Application (ILP) | Show.ILP.* | Edit.ILP.* | ILP.* |
| Individual - Access to Health (IAH) | Show.IAH.* | Edit.IAH.* | IAH.* |
| Institutional Ongoing Group Educational (IGE) | Show.IGE.* | Edit.IGE.* | IGE.* |
| Livelihood Development Projects (LDP) | Show.LDP.* | Edit.LDP.* | LDP.* |

---

## 2. Show Partials with Issues

### 2.1 IES — Individual - Ongoing Educational support

**File:** `resources/views/projects/partials/Show/IES/estimated_expenses.blade.php`

| Issue | Details |
|-------|---------|
| Form elements | `<input type="text">`, `<input type="number">` (readonly) for expense table and totals |
| `old()` usage | `old('total_expenses', ...)`, `old('expected_scholarship_govt', ...)`, etc. |
| JavaScript | `IEScalculateTotalExpenses()`, `IEScalculateBalanceRequested()` |
| Reference | IIES has proper read-only `Show/IIES/estimated_expenses.blade.php` — use `{{ $detail->particular }}`, `{{ format_indian($detail->amount, 2) }}`, no inputs |

**Remediation:** Refactor to match `Show/IIES/estimated_expenses.blade.php` — read-only table, `{{ }}` display, `format_indian_currency()` for amounts, no `old()`, no JS.

---

### 2.2 RST — Residential Skill Training Proposal 2

**File:** `resources/views/projects/partials/Show/RST/target_group.blade.php`

| Issue | Details |
|-------|---------|
| Disabled input | `<input type="text" ... disabled>` for "No of Beneficiaries" |
| Heading | "Show: Target Group" — acceptable but inconsistent with other Show partials (no "Show:" prefix elsewhere) |

**Remediation:** Replace disabled input with plain text: `{{ $RSTTargetGroup?->tg_no_of_beneficiaries ?? 'N/A' }}`. Use heading "Target Group" (no "Show:" prefix).

---

### 2.3 ILP — Individual - Livelihood Application

**File:** `resources/views/projects/partials/Show/ILP/budget.blade.php`

| Issue | Details |
|-------|---------|
| Edit partial copy | File comment: `{{-- resources/views/projects/partials/Edit/ILP/budget.blade.php --}}` — Same content as Edit |
| Heading | "Edit Budget" — wrong for Show context |
| Add/Remove buttons | "Add more", "Remove" — editable UI |
| JavaScript | Script creates new rows with `<input>` elements |
| Table | Uses `div.form-control` for display (acceptable) but buttons + script make it editable |

**Remediation:** Create true read-only Show partial:
- Heading: "Budget" or "Budget Details"
- Read-only table: `{{ $budget->budget_desc }}`, `{{ format_indian_currency($budget->cost, 2) }}`
- Remove Add/Remove buttons and all JavaScript
- Display totals: `{{ format_indian_currency($ILPBudgets['total_amount'] ?? 0, 2) }}` etc.

---

## 3. Show Partials Verified Clean

| Project Type | Partial | Status |
|--------------|---------|--------|
| IES | personal_info | ✓ Read-only |
| IES | family_working_members | ✓ Refactored |
| IES | immediate_family_details | ✓ Refactored |
| IES | educational_background | ✓ Refactored |
| IES | attachments | ✓ Read-only |
| IIES | All partials | ✓ Read-only |
| IAH | All partials | ✓ Read-only |
| IGE | All partials | ✓ Read-only |
| CCI | All partials | ✓ Read-only |
| CIC | basic_info | ✓ Read-only |
| Edu-RUT | All partials | ✓ Read-only |
| LDP | All partials | ✓ Read-only |
| ILP | personal_info, revenue_goals, strength_weakness, risk_analysis, attached_docs | ✓ Read-only |
| RST | institution_info, target_group_annexure, geographical_area, beneficiaries_area | ✓ (target_group has issue) |

---

## 4. Create and Edit Partials — Expected Behaviour

### 4.1 Create Partials

- **Location:** Root partials (e.g. `IES.*`, `ILP.*`)
- **Expected:** Form inputs, no "Edit:" prefix
- **Status:** Create partials correctly use form elements for data entry. No issues identified.

### 4.2 Edit Partials

- **Location:** `Edit.*` partials
- **Expected:** Form inputs, "Edit:" prefix in headings
- **Status:** Edit partials correctly use "Edit:" prefix and form elements. No issues identified.

---

## 5. Remediation Priority

| Priority | Project Type | Partial | Effort |
|----------|--------------|---------|--------|
| 1 | IES | estimated_expenses | Medium — follow IIES pattern |
| 2 | ILP | budget | Medium — full refactor from Edit copy |
| 3 | RST | target_group | Low — replace disabled input with text |

---

## 6. Excluded / Edge Cases

| Item | Notes |
|------|-------|
| `Show/budget.blade.php` | Contains toggle/close buttons for charts and alerts — UI controls, not form submit. Acceptable. |
| `Show/status_history.blade.php` | Contains tooltip button — UI control. Acceptable. |
| `Show/LDP/need_analysis.blade.php` | Contains "View" / "Download" links — document links, not form inputs. Acceptable. |
| `OLdshow/`, `not working show/` | Legacy/deprecated folders. Not included in main Show flow. |
| `Show/IES/immediate_family_details.blade.php` | Grep match for "disabled" is for `father_disabled` (boolean field), not HTML attribute. |

---

## 7. Reference Implementation

**Show/IIES/estimated_expenses.blade.php** — correct pattern:
- Read-only table with `{{ $detail->iies_particular }}`, `{{ format_indian($detail->iies_amount, 2) }}`
- No inputs, no `old()`, no JavaScript
- Totals displayed with `{{ format_indian($iiesExpenses->iies_total_expenses ?? 0, 2) }}`

---

## 8. File Reference

| Category | Path |
|----------|------|
| Show view | `resources/views/projects/Oldprojects/show.blade.php` |
| Edit view | `resources/views/projects/Oldprojects/edit.blade.php` |
| Create view | `resources/views/projects/Oldprojects/createProjects.blade.php` |
| Show partials | `resources/views/projects/partials/Show/` |
| Edit partials | `resources/views/projects/partials/Edit/` |
| Create partials | `resources/views/projects/partials/` (root: IES, IIES, ILP, etc.) |
