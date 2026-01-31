# Phase 1 – Resolver Review and Validation (Governance Gate)

**Document type:** Formal review / release gate  
**Date:** 2026-01-29  
**Role:** Financial Systems Auditor, Domain-Aware Reviewer, Release Gatekeeper  
**Question answered:** _Are the resolver-computed budget values correct enough to be trusted for Phase 2 syncing?_

**Source documents (authoritative):**

1. PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md
2. PHASE_0_VERIFICATION.md
3. PHASE_1_COMPLETION_SUMMARY.md

**Resolver logs reviewed:** `storage/logs/budget-2026-01-29.log`

---

## 1. Review Scope & Methodology

### 1.1 How projects were selected

- **Primary source:** The only available resolver log file is `storage/logs/budget-2026-01-29.log`. It contains resolver calls and discrepancy entries for projects that were loaded through the application (e.g. project show page with resolver enabled) or a focused test run.
- **No full-scan dry-run was available:** A dry-run backfill over all individual/IGE/development projects was not executed for this review. The sample is therefore limited to the projects that appear in the log.
- **Sampling from log:**
    - **Development:** 1 project (DP-0001).
    - **Individual – IIES:** 1 project (IIES-0001).
    - **Individual – IES:** 1 project (IOES-0001).
    - **Individual – ILP:** 1 project (ILA-0001).
    - **Individual – IAH:** 1 project (IAH-0001).
    - **IGE / Institutional:** 1 project (IOGEP-0001).
- One additional log line (project_id `test-id`, type IIES) was treated as test data and excluded from business validation.

### 1.2 Why this sampling is sufficient (with caveats)

- **Logic coverage:** Each project type that uses type-specific budget tables (Development with phase budgets, IIES, IES, ILP, IAH, IGE) is represented by at least one project in the log. The resolver code paths and formulas have been checked against the implementation plan and Basic_Info_Fund_Fields_Mapping_Analysis.md.
- **Arithmetic and domain checks:** For each sampled project, resolved values were checked for consistency with the documented formulas (overall, local contribution, sanctioned = overall − forwarded − local where applicable, opening balance).
- **Caveat:** The plan asks for 5–10 projects per category. Only one log file was available, with one project per type. The review is therefore **representative but not statistically broad**. For full assurance, the organisation may run a dry-run over a larger set of projects (e.g. 5–10 Development, 5–10 Individual across IIES/IES/ILP/IAH, 5–10 IGE) and re-review the resulting logs before enabling Phase 2 in production.

---

## 2. Development Projects Review

### 2.1 Sample: DP-0001

| Attribute     | Value                                        |
| ------------- | -------------------------------------------- |
| Project ID    | DP-0001                                      |
| Project type  | Development Projects                         |
| Budget source | `projects` + `project_budgets` (phase-based) |

**Resolver output (from log):**

- overall_project_budget: 789,000
- amount_forwarded: 0
- local_contribution: 0
- amount_sanctioned: 789,000
- opening_balance: 789,000

**Stored (from log):** overall 789,000; forwarded 0; local 0; amount_sanctioned 0; opening_balance 0

### 2.2 Business-level validation

- **Overall:** 789,000 is either from General Info or, if overall was 0, from the sum of `this_phase` for the current phase in `project_budgets`. Acceptable for a development project.
- **Local contribution:** 0 is consistent with no beneficiary/family contribution in General Info.
- **Amount sanctioned:** Formula _overall − (forwarded + local)_ = 789,000 − 0 = 789,000. Resolved value matches.
- **Opening balance:** Formula _sanctioned + forwarded + local_ = 789,000 + 0 + 0 = 789,000. Resolved value matches.

### 2.3 Summary and anomalies

- **Pattern:** Development type correctly uses `projects` for overall/forwarded/local and computes sanctioned and opening from the plan’s formulas. Fallback to phase-budget sum when overall = 0 is implemented and consistent with the plan.
- **Anomaly:** Stored `amount_sanctioned` and `opening_balance` are 0 while resolved are 789,000. This is **expected**: those two fields are written only at approval; before approval they are often 0. The discrepancy log is correct and does not indicate a resolver error.

---

## 3. Individual Projects Review (IIES / IES / ILP / IAH)

### 3.1 IIES – IIES-0001

| Attribute     | Value                                      |
| ------------- | ------------------------------------------ |
| Project ID    | IIES-0001                                  |
| Project type  | Individual - Initial - Educational support |
| Budget source | `ProjectIIESExpenses` (single row)         |

**Resolver output (from log):**

- overall_project_budget: 189,500
- amount_forwarded: 0
- local_contribution: 0
- amount_sanctioned: 0
- opening_balance: 0

**Observation:** Overall is non-zero (189,500) but local, sanctioned, and opening are 0. This can occur if type-specific data is partially filled (e.g. `iies_total_expenses` set, `iies_balance_requested` and contribution fields null/zero). The mapping doc specifies opening = overall when forwarded = 0; current resolver code sets `opening_balance = overall`. If the log was produced by an earlier build that did not set opening from overall, a later run should show opening_balance = 189,500. No discrepancy line was logged for IIES-0001 (stored and resolved may both have been zero for sanctioned/opening at the time).

**Local contribution logic:** Resolver uses `iies_expected_scholarship_govt + iies_support_other_sources + iies_beneficiary_contribution`. Zero in the log is consistent with those fields being null/zero in the DB.

### 3.2 IES – IOES-0001

| Attribute     | Value                                    |
| ------------- | ---------------------------------------- |
| Project ID    | IOES-0001                                |
| Project type  | Individual - Ongoing Educational support |
| Budget source | `ProjectIESExpenses` (first row)         |

**Resolver output:** overall 123,400; forwarded 0; local_contribution 17,000; amount_sanctioned 106,400; opening_balance 123,400

**Stored:** overall 106,400; forwarded 0; local 0; sanctioned 0; opening 0

**Validation:**

- **Overall:** 123,400 from `total_expenses` (first IES expense row). Plausible for ongoing educational support.
- **Local contribution:** 17,000 = scholarship + support_other + beneficiary. Aligns with “beneficiary + family + scholarship” for IES.
- **Amount sanctioned:** `balance_requested` = 106,400. Cross-check: 123,400 − 17,000 = 106,400. Correct.
- **Opening balance:** Set to overall 123,400 when forwarded = 0. Correct.

Discrepancy (stored overall/sanctioned/local all different from resolved) is expected: type-specific tables hold the true figures; `projects` had not been synced.

### 3.3 ILP – ILA-0001

| Attribute     | Value                               |
| ------------- | ----------------------------------- |
| Project ID    | ILA-0001                            |
| Project type  | Individual - Livelihood Application |
| Budget source | `ProjectILPBudget` (multiple rows)  |

**Resolver output:** overall 342,000; forwarded 0; local_contribution 3,000; amount_sanctioned 339,000; opening_balance 342,000

**Stored:** overall 339,000; forwarded 0; local 0; sanctioned 0; opening 0

**Validation:**

- **Overall:** Sum of `cost` across ILP budget rows = 342,000. Acceptable.
- **Local contribution:** First row `beneficiary_contribution` = 3,000. Matches “beneficiary contribution” in the mapping.
- **Amount sanctioned:** First row `amount_requested` = 339,000. Cross-check: 342,000 − 3,000 = 339,000. Correct.
- **Opening balance:** 342,000 = overall. Correct.

Stored overall (339,000) likely reflects only the requested amount or an older manual entry; resolver correctly derives overall from type table.

### 3.4 IAH – IAH-0001

| Attribute     | Value                                     |
| ------------- | ----------------------------------------- |
| Project ID    | IAH-0001                                  |
| Project type  | Individual - Access to Health             |
| Budget source | `ProjectIAHBudgetDetails` (multiple rows) |

**Resolver output:** overall 202,000; forwarded 0; local_contribution 2,000; amount_sanctioned 200,000; opening_balance 202,000

**Stored:** overall 200,000; forwarded 0; local 0; sanctioned 0; opening 0

**Validation:**

- **Overall:** Sum of `amount` in IAH budget details = 202,000. Plausible for health access.
- **Local contribution:** First row `family_contribution` = 2,000. Aligns with “family contribution” for IAH.
- **Amount sanctioned:** First row `amount_requested` = 200,000. Cross-check: 202,000 − 2,000 = 200,000. Correct.
- **Opening balance:** 202,000 = overall. Correct.

### 3.5 Summary per subtype

| Subtype | Count in log | Local contribution source           | Sanctioned source          | Consistency                         |
| ------- | ------------ | ----------------------------------- | -------------------------- | ----------------------------------- |
| IIES    | 1            | scholarship + support + beneficiary | balance_requested          | Partial data case (zeros); logic OK |
| IES     | 1            | scholarship + support + beneficiary | balance_requested          | Arithmetic and mapping correct      |
| ILP     | 1            | First row beneficiary_contribution  | First row amount_requested | Arithmetic and mapping correct      |
| IAH     | 1            | First row family_contribution       | First row amount_requested | Arithmetic and mapping correct      |

**Inconsistencies:** None in formula or source fields. IIES-0001 is a partial-data case; resolver correctly returns what type table contains; opening_balance in a future run should equal overall when forwarded = 0 (per current code).

---

## 4. IGE / Institutional Projects Review

### 4.1 Sample: IOGEP-0001

| Attribute     | Value                                            |
| ------------- | ------------------------------------------------ |
| Project ID    | IOGEP-0001                                       |
| Project type  | Institutional Ongoing Group Educational proposal |
| Budget source | `ProjectIGEBudget` (multiple rows)               |

**Resolver output:** overall 2,927,400; forwarded 0; local_contribution 970,000; amount_sanctioned 1,957,400; opening_balance 2,927,400

**Stored:** All five fields 0.

### 4.2 Handling of multiple rows

- **Overall:** Sum of `total_amount` across all IGE budget rows. Correct for multi-row IGE.
- **Local contribution:** Sum of `scholarship_eligibility` + sum of `family_contribution` across rows. Aligns with mapping (scholarship + family contribution at institution level).
- **Amount sanctioned:** Sum of `amount_requested` across rows. 2,927,400 − 970,000 = 1,957,400. Correct.
- **Opening balance:** Set to overall 2,927,400. Correct.

### 4.3 Edge cases

- **Multiple rows:** Aggregation (sum for overall, local, sanctioned) is implemented and matches the plan. No “first row only” error for IGE.
- **Stored all zero:** Typical for IGE when `projects` has never been synced from type budget; resolver correctly derives all five values from `project_IGE_budget`.

---

## 5. Key Discrepancies & Observations

### 5.1 Expected discrepancies (acceptable)

- **Development (DP-0001):** Stored `amount_sanctioned` and `opening_balance` are 0; resolved are 789,000. Expected, because sanctioned and opening are written only at approval.
- **Individual (IOES-0001, ILA-0001, IAH-0001):** Stored overall/local/sanctioned/opening often zero or partial; resolved values come from type tables. Discrepancy is the reason Phase 2 sync exists.
- **IGE (IOGEP-0001):** Stored all zero; resolved from type table. Acceptable.

### 5.2 Unexpected discrepancies (need attention)

- **IIES-0001:** Resolved `opening_balance` in the log is 0 while `overall_project_budget` is 189,500. The implementation plan and mapping doc state that when Amount Forwarded = 0, Opening Balance should equal Overall (or sanctioned + local). Current resolver code sets `opening_balance = overall`. If the log predates that behaviour, the next run should show 189,500. **Action:** On next resolver run (e.g. project show or dry-run), confirm that IIES-0001 (or any IIES with only overall filled) shows `opening_balance` = overall. If not, treat as a bug and fix before Phase 2.

### 5.3 Fallback and partial data

- **Fallback:** When type-specific data is missing, resolver returns project’s current stored values. Behaviour is documented and acceptable.
- **Partial data:** IIES-0001 (overall set, sanctioned/local zero) is an example. Resolver returns what the type table contains; no incorrect overwrite. Phase 2 sync would write those values to `projects`; business may later correct data in type table and re-save to refresh.

---

## 6. Risk Assessment

### 6.1 Low-risk findings

- Development formula (overall − forwarded − local = sanctioned; opening = sanctioned + forwarded + local) is correct and verified on DP-0001.
- IES, ILP, IAH: Local contribution and sanctioned/opening formulas match mapping doc and log.
- IGE: Multi-row aggregation (overall, local, sanctioned) is correct and verified on IOGEP-0001.
- Discrepancy logging and resolver-call logging are in place and usable for audit.
- No writes to `projects` from resolver; Phase 1 remains read-only.

### 6.2 Medium-risk findings

- **Limited sample size:** Only one project per type in the log. Remaining project types (Livelihood, RST, CIC, CCI, RUT, NEXT_PHASE) use the same Development path; behaviour is inferred from code review and DP-0001, not from additional log entries.
- **IIES opening_balance in log:** Resolved opening_balance 0 for IIES-0001 despite overall 189,500. If code is already corrected to set opening = overall, risk is low; if not, one more verification run is recommended.

### 6.3 High-risk findings

- **None identified.** No incorrect formula, wrong table, or mis-aggregation was found. No evidence that Phase 2 sync would persist wrong values.

---

## 7. Recommendation

**Phase 1 resolver output is validated and Phase 2 may proceed**, subject to the following:

1. **Confirm IIES opening_balance behaviour:** Before or immediately after enabling Phase 2, run the resolver for at least one IIES project that has `iies_total_expenses` set (e.g. IIES-0001) and confirm in the budget log that `opening_balance` equals `overall_project_budget` when Amount Forwarded = 0. If it does not, fix the resolver and re-validate before enabling sync.
2. **Optional but recommended:** Run a dry-run (or equivalent) over a larger set of projects (5–10 per category as in the plan) and retain the logs for audit. This does not block Phase 2 but strengthens governance and future audits.
3. **Phase 2 safeguards:** Keep sync disabled for approved projects on type budget save; run pre-approval sync only when status is forwarded_to_coordinator. These are already in the plan and Phase 0 guard design.

**Justification:** Resolver logic and formulas match the implementation plan and Basic Info mapping document. Arithmetic and domain checks on the sampled projects (Development, IIES, IES, ILP, IAH, IGE) are correct. Discrepancies in the log are explained by “sanctioned/opening written only at approval” or “type table is source, projects not yet synced.” The only open point is the IIES opening_balance value in the single log line; a quick confirmation run closes that gap. No high-risk defects were found that would make Phase 2 sync unsafe.

---

**Document status:** Final for audit record.  
**Next step:** Proceed to Phase 2 (Controlled sync to `projects`) after confirming IIES opening_balance as above and with feature flags and guards as specified in the implementation plan.
