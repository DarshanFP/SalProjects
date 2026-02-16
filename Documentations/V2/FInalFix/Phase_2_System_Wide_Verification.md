# Phase 2 — System-Wide Verification Sweep

**Document type:** Verification (READ-ONLY)  
**Scope:** All Phase 2 guarded section controllers  
**No controller, database, or refactor changes — documentation and SQL validation only.**

---

## 1. Executive Summary

This document records the outcome of a system-wide verification sweep for Phase 2 “no delete when section absent/empty” behaviour. It confirms that:

- **Guarded controllers** use a shared pattern: a “meaningfully filled” guard runs **before** any delete and **outside** the transaction (where a transaction exists). When the guard fails, the handler returns success without mutating the section.
- **Budget controllers** (IAH Budget Details, ILP Budget) additionally enforce **BudgetSyncGuard** (403 when project is approved) and **BudgetSyncService** (sync after commit for pre-approval).
- **RevenueGoalsController** guards only **update()**; **store()** is append-only (no delete), so duplicate rows can occur if store is called multiple times without a prior delete — documented as a known limitation.
- **Response contracts** are preserved: same HTTP status and message shape on both “skipped (empty)” and “mutated (meaningful)” paths where applicable; budget controllers preserve 403 on approved projects.
- **SQL validation scripts** below are SELECT-only and can be run in production to check row-count stability, duplicate detection, and multi-table integrity (e.g. RevenueGoals three-table consistency).

**Conclusion:** Phase 2 guard coverage is in place for all listed controllers. Remaining risks are documented in Section 5 (Residual Risk Assessment).

---

## 2. Guard Coverage Table (Controller Verification Checklist)

For each controller the following is verified from source code (read-only).

| Controller | Guard method exists? | Guard before delete? | Guard outside transaction? | Response contract preserved? | update() untouched* | store() untouched* | BudgetSyncGuard (403) | BudgetSyncService | Nested deletes protected (RevenueGoals) |
|------------|----------------------|----------------------|-----------------------------|------------------------------|---------------------|---------------------|------------------------|-------------------|----------------------------------------|
| **WAVE 3** | | | | | | | | | |
| IIESFamilyWorkingMembersController | ✅ `isIIESFamilyWorkingMembersMeaningfullyFilled` | ✅ store & update | ✅ No transaction in store/update | ✅ 200 + same message | N/A (update does delete+recreate) | N/A | N/A | N/A | N/A |
| **WAVE 4** | | | | | | | | | |
| EduRUTAnnexedTargetGroupController | ✅ `isEduRUTAnnexedTargetGroupMeaningfullyFilled` | ✅ update only (store has no delete) | ✅ Guard before `DB::beginTransaction()` | ✅ 200 + same message | ✅ delegates to store logic in update | store has no guard (append-only) | N/A | N/A | N/A |
| **WAVE 1** | | | | | | | | | |
| IAHSupportDetailsController | ✅ `isIAHSupportDetailsMeaningfullyFilled` | ✅ store | ✅ Before `DB::beginTransaction()` | ✅ 200 + existing model or message | ✅ update() = store() | ✅ guard returns existing or message | N/A | N/A | N/A |
| IAHHealthConditionController | ✅ `isIAHHealthConditionMeaningfullyFilled` | ✅ store | ✅ Before `DB::beginTransaction()` | ✅ 200 + existing or message | ✅ update() = store() | ✅ guard returns existing or message | N/A | N/A | N/A |
| IAHPersonalInfoController | ✅ `isIAHPersonalInfoMeaningfullyFilled` | ✅ store | ✅ Before `DB::beginTransaction()` | ✅ 200 + existing or message | ✅ update() = store() | ✅ guard returns existing or message | N/A | N/A | N/A |
| IAHEarningMembersController | ✅ `isIAHEarningMembersMeaningfullyFilled` | ✅ store | ✅ Before `DB::beginTransaction()` | ✅ 200 + message | ✅ update() = store() | ✅ guard returns message | N/A | N/A | N/A |
| IAHBudgetDetailsController | ✅ `isIAHBudgetDetailsMeaningfullyFilled` | ✅ store | ✅ Before `DB::beginTransaction()` | ✅ 200 / 403 / 500 unchanged | ✅ 403 at entry then store() | ✅ 403 at entry, then guard | ✅ store & update | ✅ after commit | N/A |
| **WAVE 2** | | | | | | | | | |
| RiskAnalysisController | ✅ `isILPRiskAnalysisMeaningfullyFilled` | ✅ store | ✅ Before `DB::beginTransaction()` | ✅ 200 message; update adds `data` | ✅ calls store then adds `data` | ✅ guard returns message | N/A | N/A | N/A |
| StrengthWeaknessController | ✅ `isILPStrengthWeaknessMeaningfullyFilled` | ✅ store | ✅ Before `DB::beginTransaction()` | ✅ 200 + message | ✅ update() = store() | ✅ guard returns message | N/A | N/A | N/A |
| BudgetController (ILP) | ✅ `isILPBudgetMeaningfullyFilled` | ✅ store | ✅ Before `DB::beginTransaction()` | ✅ 200 / 403 / 500 unchanged | ✅ 403 at entry then store() | ✅ 403 at entry, then guard | ✅ store & update | ✅ after commit | N/A |
| RevenueGoalsController | ✅ `isILPRevenueGoalsMeaningfullyFilled` | ✅ update only | ✅ Before `DB::beginTransaction()` in update | ✅ 200 + message | ✅ guard then 3× delete + recreate | ⚠️ store() has no guard, no delete (append-only) | N/A | N/A | ✅ All 3 deletes inside same guarded transaction |

\* “Untouched” here means the intended behaviour (delegate to store or return same contract) is preserved; no code was removed that would break the contract.

---

## 3. Manual Test Matrix

Expected behaviour for each scenario (read-only specification; no implementation changes).

| Controller | A — Update General Info only | B — Submit section with meaningful data | C — Submit section empty | D — Submit partial rows (multi-row) | E — Approved project (budget only) |
|------------|------------------------------|----------------------------------------|---------------------------|-------------------------------------|------------------------------------|
| IIESFamilyWorkingMembersController | No section call; no change | Delete existing, insert new rows; 200 | 200; no delete; rows unchanged | Only complete rows inserted; 200 | N/A |
| EduRUTAnnexedTargetGroupController | No section call; no change | update: delete then insert; 200 | update: 200; no delete | Rows with at least one non-empty field counted as meaningful | N/A |
| IAHSupportDetailsController | No section call; no change | Delete then single row; 200 | 200; return existing or success; no delete | Single-row section | N/A |
| IAHHealthConditionController | No section call; no change | Delete then single row; 200 | 200; no delete | Single-row section | N/A |
| IAHPersonalInfoController | No section call; no change | Delete then single row; 200 | 200; no delete | Single-row section | N/A |
| IAHEarningMembersController | No section call; no change | Delete then insert complete rows; 200 | 200; no delete | Only rows with name+work_type+income inserted | N/A |
| IAHBudgetDetailsController | No section call; no change | Delete then insert; 200; sync if pre-approval | 200; no delete | Only rows with particular+amount | 403; no mutation; audit log |
| RiskAnalysisController | No section call; no change | Delete then single row; 200 | 200; no delete | Single-row section | N/A |
| StrengthWeaknessController | No section call; no change | Delete then single row (JSON); 200 | 200; no delete | Single record (strengths/weaknesses arrays) | N/A |
| BudgetController (ILP) | No section call; no change | Delete then insert; 200; sync if pre-approval | 200; no delete | Rows with budget_desc+cost | 403; no mutation; audit log |
| RevenueGoalsController | No section call; no change | update: delete all 3 tables then insert; 200 | update: 200; no delete | All three arrays checked for any non-empty value | N/A |

**Legend:**

- **A:** User saves only general info; section endpoints not called → no change to section data.
- **B:** Section submitted with at least one meaningful row/field → delete existing section data, then recreate from payload; 200.
- **C:** Section submitted but empty/absent → 200, no delete; row counts unchanged.
- **D:** Multi-row sections: only rows that satisfy the same “meaningful” rules as the create loop are inserted; partial rows do not cause delete-without-insert.
- **E:** Budget controllers only: when project is approved, store/update return 403 and do not mutate; BudgetAuditLogger records attempt.

---

## 4. SQL Validation Scripts (READ-ONLY)

Use these SELECTs to verify: (1) no duplicate rows per project per section, (2) row-count stability after empty submission (manual check: run count before/after), (3) RevenueGoals three-table integrity, (4) budget row consistency, (5) single-row sections at most 1 row per project.

**Table names** (from models):  
`project_IIES_family_working_members`, `project_edu_rut_annexed_target_groups`, `project_IAH_support_details`, `project_IAH_health_condition`, `project_IAH_personal_info`, `project_IAH_earning_members`, `project_IAH_budget_details`, `project_ILP_risk_analysis`, `project_ILP_strength_weakness`, `project_ILP_budget`, `project_ILP_revenue_plan_items`, `project_ILP_revenue_income`, `project_ILP_revenue_expenses`.

### 4.1 Row count per project (multi-row sections)

```sql
-- Row count check — multi-row sections (replace TABLE_NAME and project_id column as per your schema)
SELECT project_id, COUNT(*) AS row_count
FROM project_IIES_family_working_members
GROUP BY project_id;

SELECT project_id, COUNT(*) AS row_count
FROM project_edu_rut_annexed_target_groups
GROUP BY project_id;

SELECT project_id, COUNT(*) AS row_count
FROM project_IAH_earning_members
GROUP BY project_id;

SELECT project_id, COUNT(*) AS row_count
FROM project_IAH_budget_details
GROUP BY project_id;

SELECT project_id, COUNT(*) AS row_count
FROM project_ILP_budget
GROUP BY project_id;
```

### 4.2 Duplicate detection (content-based; adjust columns to match schema)

```sql
-- Duplicate detection — same project_id + key fields (example: IIES family working members)
-- Tune column names to match actual table (e.g. iies_member_name, iies_work_nature, iies_monthly_income)
SELECT project_id, iies_member_name, iies_work_nature, iies_monthly_income, COUNT(*) AS cnt
FROM project_IIES_family_working_members
GROUP BY project_id, iies_member_name, iies_work_nature, iies_monthly_income
HAVING COUNT(*) > 1;

-- IAH earning members
SELECT project_id, member_name, work_type, monthly_income, COUNT(*) AS cnt
FROM project_IAH_earning_members
GROUP BY project_id, member_name, work_type, monthly_income
HAVING COUNT(*) > 1;

-- EduRUT annexed target group (beneficiary_name, family_background, need_of_support)
SELECT project_id, beneficiary_name, family_background, need_of_support, COUNT(*) AS cnt
FROM project_edu_rut_annexed_target_groups
GROUP BY project_id, beneficiary_name, family_background, need_of_support
HAVING COUNT(*) > 1;
```

### 4.3 Single-row sections — at most 1 row per project

```sql
-- Single-row controllers: at most 1 row per project
SELECT project_id, COUNT(*) AS row_count
FROM project_IAH_support_details
GROUP BY project_id
HAVING COUNT(*) > 1;

SELECT project_id, COUNT(*) AS row_count
FROM project_IAH_health_condition
GROUP BY project_id
HAVING COUNT(*) > 1;

SELECT project_id, COUNT(*) AS row_count
FROM project_IAH_personal_info
GROUP BY project_id
HAVING COUNT(*) > 1;

SELECT project_id, COUNT(*) AS row_count
FROM project_ILP_risk_analysis
GROUP BY project_id
HAVING COUNT(*) > 1;

SELECT project_id, COUNT(*) AS row_count
FROM project_ILP_strength_weakness
GROUP BY project_id
HAVING COUNT(*) > 1;
```

### 4.4 RevenueGoals — multi-table consistency (row counts per project)

```sql
-- RevenueGoals: row counts per project for all three tables (no orphan check; projects may have 0 rows)
SELECT p.project_id,
       (SELECT COUNT(*) FROM project_ILP_revenue_plan_items r WHERE r.project_id = p.project_id) AS plan_items,
       (SELECT COUNT(*) FROM project_ILP_revenue_income i WHERE i.project_id = p.project_id) AS income_rows,
       (SELECT COUNT(*) FROM project_ILP_revenue_expenses e WHERE e.project_id = p.project_id) AS expense_rows
FROM (SELECT DISTINCT project_id FROM project_ILP_revenue_plan_items
      UNION SELECT DISTINCT project_id FROM project_ILP_revenue_income
      UNION SELECT DISTINCT project_id FROM project_ILP_revenue_expenses) p;
```

### 4.5 Budget rows consistency (IAH / ILP)

```sql
-- IAH budget details: row count per project
SELECT project_id, COUNT(*) AS row_count
FROM project_IAH_budget_details
GROUP BY project_id;

-- ILP budget: row count per project
SELECT project_id, COUNT(*) AS row_count
FROM project_ILP_budget
GROUP BY project_id;
```

**Note:** To verify “row counts before and after empty submission remain identical”, run the relevant row-count SELECT for a chosen project before and after triggering an empty section submit; counts should match.

---

## 5. Residual Risk Assessment

**Residual Risk After Phase 2**

| Risk | Assessment |
|------|------------|
| **Cross-section wipe risk** | **Low.** Guards are per-controller and per-section. Submitting empty data to one section does not touch other sections. No shared “wipe all” path introduced. |
| **Multi-table wipe risk** | **Low.** RevenueGoalsController update() deletes all three tables only after the single guard passes; all deletes are inside the same transaction, so no partial wipe of only one or two tables on success. |
| **Approved budget bypass risk** | **Low.** IAHBudgetDetailsController and ILP BudgetController check BudgetSyncGuard in both store() and update() before any mutation; 403 is returned and BudgetAuditLogger records the attempt. |
| **Partial row update limitation** | **Accepted.** Section save remains “full replace” per section: when meaningful data is submitted, existing section rows are deleted and recreated from the payload. Partial row updates (patch single row) are not supported. |
| **RevenueGoals store() duplicates** | **Known.** store() has no guard and no delete; it only appends. Repeated store() calls can create duplicate rows across the three tables. update() is guarded and replaces all. Mitigation: prefer update() for “save section” flows. |
| **Structural / transaction handling** | **Low.** Controllers that use DB::transaction() begin after the guard and roll back on exception; guard is outside transaction so no partial commit on guard failure. IIESFamilyWorkingMembersController and EduRUT store (append) do not use a transaction for the section write — consistent with original design. |

---

## 6. Final Stability Conclusion

- Phase 2 guarded controllers **do not delete when the section is absent or empty**; they return success and leave data unchanged.
- They **still delete and recreate when the section is meaningfully filled**, preserving intended “full replace” behaviour.
- **Response contracts** (200/403/500 and message shape) are preserved; **403 budget lock** and **BudgetSyncService** are preserved for IAH Budget Details and ILP Budget.
- **Duplicate rows** are not introduced by the guard logic; duplicate risk is limited to RevenueGoalsController **store()** (append-only), which is documented.
- **Transaction handling** is consistent: guard runs first, then transaction (where used), with rollback on exception.

This verification is **read-only** and does not modify any controller, database, or application behaviour. The SQL scripts are SELECT-only and safe for production use.
