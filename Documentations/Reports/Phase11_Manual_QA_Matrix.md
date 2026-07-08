# Phase 11 — Manual QA Matrix (All 12 Project Types)

**Purpose:** Sign-off checklist before declaring monthly reporting **12/12 complete**.  
**Environment:** Staging (use real approved projects per type; do not use production for first pass).  
**Prerequisites:** Phases 1–10 deployed; run automated pre-flight first:

```bash
php artisan reports:qa-readiness
```

**Related automated tests:**

```bash
php artisan test tests/Feature/MonthlyReportTest.php \
  tests/Unit/Services/BudgetCalculationServiceReportTest.php
```

---

## Roles needed

| Role | Used in steps |
|------|----------------|
| Executor / Applicant | 1–5, 8 (edit/resubmit) |
| Provincial | 6–7 (submit target, revert) |
| Coordinator | 9 (approve) |

Use one approved project **per type** (12 projects total). Record project IDs in [Phase11_Manual_QA_Results.md](./Phase11_Manual_QA_Results.md).

---

## Standard workflow (all 12 types)

Execute steps 1–10 for **each** project type. Mark pass/fail in the results file.

| Step | Action | Pass criteria |
|------|--------|---------------|
| **1** | Approved project → **Write Report** (`monthly.report.create`) | Create form loads; no 500/403 |
| **2** | **Save as Draft** | No SQL/validation error; row in `DP_Reports`; status = `draft` |
| **3** | **Basic info** | Place + society match **project** (not executor profile) — Phase 8 |
| **4** | **SOA rows** | Budget lines match project budget; sanctioned amounts non-zero where expected — Phase 4/6 |
| **5** | **Photo + attachment** | Files under `storage/app/public/REPORTS/{project_id}/{report_id}/…/{month_year}/` |
| **6** | **Submit to provincial** | Status → `submitted_to_provincial` |
| **7** | **Provincial revert to executor** | Status = granular revert (e.g. `reverted_to_executor`) — Phase 2 |
| **8** | **Edit + resubmit** | Executor can edit after revert; resubmit succeeds — Phase 2 |
| **9** | Forward + **coordinator approve** | Status in `APPROVED_STATUSES` |
| **10** | **Aggregated quarterly** | Create/show aggregated report; approved monthly data included |

### Step 10 detail

1. Ensure at least one **approved** monthly report exists for the project (step 9).
2. Navigate: `aggregated.quarterly.create` → select project → generate.
3. Pass: aggregated report lists monthly periods / totals consistent with approved monthlies.

---

## Per-type extra checks

After the standard steps, verify the type-specific item:

| # | Project type | Extra check (Phase reference) |
|---|--------------|-------------------------------|
| 1 | Development Projects | Overview sanctioned matches resolver when stored value is 0 (Phase 4) |
| 2 | Livelihood Development Projects | Livelihood annexure section saves on create/edit |
| 3 | Residential Skill Training Proposal 2 | Trainee profiles section present and saves |
| 4 | PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER | Inmate profiles section present and saves |
| 5 | Institutional Ongoing Group Educational proposal | Age profiles; `institutional_education` SOA partial |
| 6 | Individual - Livelihood Application | Contribution columns; `amount_sanctioned` per row (ILP) |
| 7 | Individual - Access to Health | Contribution columns (IAH) |
| 8 | Individual - Ongoing Educational support | Contribution columns (IES) |
| 9 | Individual - Initial - Educational support | Multi-source contribution columns (IIES) |
| 10 | CHILD CARE INSTITUTION | Project **edit** works when statistics row missing (Phase 3) |
| 11 | NEXT PHASE - DEVELOPMENT PROPOSAL | Create redirects to canonical form; budget fallback (Phase 7/4) |
| 12 | Rural-Urban-Tribal | Phase-based SOA rows for current phase |

---

## SOA partial routing reference

| Project type | SOA partial |
|--------------|-------------|
| Development Projects, NPD, LDP, RST, CIC, CCI, Edu-RUT | `development_projects` |
| Individual - Livelihood Application | `individual_livelihood` |
| Individual - Access to Health | `individual_health` |
| Institutional Ongoing Group Educational proposal | `institutional_education` |
| Individual - Ongoing Educational support | `individual_ongoing_education` |
| Individual - Initial - Educational support | `individual_education` |

---

## Failure handling

| Symptom | Likely cause | Check |
|---------|--------------|-------|
| 500 on create | Missing `projects.society_id` | Phase 1; run `reports:audit-project-fund-fields` |
| 403 on create | Unapproved project or wrong owner | Phase 5 auth |
| 403 on edit after revert | Status not in `EXECUTOR_EDITABLE_STATUSES` | Phase 2 |
| Empty SOA rows | Budget config / phase | `config/budget.php`; `current_phase` |
| Wrong society on form | Display bug | Phase 8 `basicInfoForCreateForm` |

Log issues in **Notes** column of [Phase11_Manual_QA_Results.md](./Phase11_Manual_QA_Results.md).

---

## Sign-off

When all 12 types pass steps 1–10 + extra checks:

- [ ] Update results file: **Overall status = PASS**
- [ ] Update `Documentations/V1/Reports/TASKS_AND_STATUS.md` Phase 11 row
- [ ] Proceed to Phase 12 (society relational alignment) if needed

**Tester / date:** _fill in results file_
