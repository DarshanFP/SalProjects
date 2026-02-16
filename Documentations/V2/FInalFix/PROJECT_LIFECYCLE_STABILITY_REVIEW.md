# FinalFix Global Review — Project Lifecycle Stability & Data Integrity Verification

**Mode:** STRICTLY READ-ONLY | FULL DOCUMENTATION REVIEW | ZERO CODE MODIFICATION  
**Scope:** Project module only (deep expense/report redesign excluded).  
**Sources:** All markdown files under `Documentations/V2/FinalFix/` and subfolders M1, M2, M3, M4, M5.

---

## SECTION 1 — Historical Data Loss Causes

*From M1 and M2 documentation.*

### 1.1 What caused partial data loss during update?

- **Delete-then-recreate when section absent or empty:** Controllers that bulk-deleted section rows by `project_id` then recreated from request input ran this path even when the request did not contain the section (key absent) or contained only empty arrays. Existing section data was wiped and replaced with nothing or empty rows.  
  **Ref:** `M1_CLOSURE_REPORT.md` §1 — "Delete-then-recreate data loss"; "section key was absent or contained only empty arrays."

- **Provincial revert / partial submit treated as “replace with empty”:** When a user reverted or submitted a project edit without including a given section in the payload (e.g. partial save or tab-not-visited), the backend treated the missing section as “replace with empty,” leading to unintended data loss.  
  **Ref:** `M1_CLOSURE_REPORT.md` §1 — "Provincial revert corruption."

- **Silent wipe when section key missing:** No guard existed to skip the delete+recreate path when the section key was absent or present but empty; the mutation ran unconditionally.  
  **Ref:** `M1_CLOSURE_REPORT.md` §1 — "Silent wipe when section keys missing."

### 1.2 Which controllers were risky?

- **M1 in-scope (all had delete-recreate or attachment risk):** BudgetController, LogicalFrameworkController, EduRUTTargetGroupController, LDP/TargetGroupController, RST/BeneficiariesAreaController, RST/TargetGroupAnnexureController, RST/GeographicalAreaController, IES/IESExpensesController, IIES/IIESExpensesController, IES/IESFamilyWorkingMembersController, IGE/IGEBeneficiariesSupportedController, IGE/OngoingBeneficiariesController, IGE/NewBeneficiariesController, IGE/IGEBudgetController, IES/IESAttachmentsController, IIES/IIESAttachmentsController, IAH/IAHDocumentsController, ILP/AttachedDocumentsController.  
  **Ref:** `M1_CLOSURE_REPORT.md` §2 (table).

- **Write-path NOT NULL / numeric risks (M2.1):** GeneralInfoController (projects.in_charge, overall_project_budget); LogicalFrameworkController (objective, result, risk, activity, verification, month, is_active); IESFamilyWorkingMembersController, IAHEarningMembersController, IESExpensesController, IAHBudgetDetailsController (empty() on numeric).  
  **Ref:** `M2/M2_1_NotNull_Write_Path_Audit.md` §Write Operation Map, §STEP 5.

### 1.3 Which NOT NULL risks existed?

- **projects:** `in_charge` (NOT NULL), `overall_project_budget` (NOT NULL with default). Could receive NULL from GeneralInfoController when validated contained null (nullable rules, key present but value null).  
  **Ref:** `M2/M2_1_NotNull_Write_Path_Audit.md` §STEP 3, §STEP 4, §STEP 6.

- **LogicalFramework tables:** `project_objectives.objective`, `project_results.result`, `project_risks.risk` (description), `project_activities.activity`, `project_activities.verification`, `project_timeframes.month`, `project_timeframes.is_active` — NOT NULL; controller could create rows with null/missing values when request data was partial or malformed.  
  **Ref:** `M2/M2_1_NotNull_Write_Path_Audit.md` §STEP 2 (LogicalFrameworkController row), §STEP 6.

### 1.4 Which empty() numeric drop risks existed?

- **monthly_income:** IESFamilyWorkingMembersController, IAHEarningMembersController — `empty($monthlyIncome)` caused rows with value **0** to be skipped (PHP `empty(0)` is true).  
  **Ref:** `M2/M2_1_NotNull_Write_Path_Audit.md` §STEP 5.

- **amount:** IESExpensesController, IAHBudgetDetailsController — `empty($amount)` caused expense/budget rows with amount **0** to be skipped.  
  **Ref:** `M2/M2_1_NotNull_Write_Path_Audit.md` §STEP 5.

### 1.5 Which logical framework row risks existed?

- Loops over objectives/results/risks/activities did not validate non-empty per row; `$objectiveData['objective']`, `$resultData['result']`, `$riskData['risk']`, `$activityData['activity']`/`['verification']` could be null → NOT NULL columns. Timeframe `month`/`is_active` could be missing or null.  
  **Ref:** `M2/M2_1_NotNull_Write_Path_Audit.md` §STEP 2 (LogicalFrameworkController), §STEP 6.

---

## SECTION 2 — Update Path Safety (Current State)

*Verified from M2 closure and step docs (M2_3, M2_4, M2_5).*

### 2.1 UpdateProjectRequest guards

- **NOT NULL protection:** When route has `project_id` and project exists, merge `in_charge` and `overall_project_budget` from existing project when the key is **missing** (for all update requests). Merge only when key is absent; if key is present (even null), controller null-guard handles it.  
  **Ref:** `M2/M2_3_Step1_Projects_Table_Protection.md` §1 (UpdateProjectRequest), §2.

- **Draft merge scoping:** Draft behaviour preserved: when `save_as_draft` is true, existing logic still merges `project_type`, `in_charge`, `overall_project_budget` when the key is not *filled*. New “missing key” merge runs for all update requests when project exists.  
  **Ref:** `M2/M2_3_Step1_Projects_Table_Protection.md` §3.

- **Update-only scoping:** Project is loaded and merge runs only when `$this->route('project_id')` is present and project is found. No merge on create; create uses StoreProjectRequest.  
  **Ref:** `M2/M2_3_Step1_Projects_Table_Protection.md` §8 (Scope Correction).

### 2.2 GeneralInfoController

- **validated() usage only:** Controller uses `$request->validated()` only; no raw request usage for update payload.  
  **Ref:** `M2/M2_1_NotNull_Write_Path_Audit.md` §STEP 4 (audit); M2.3 adds guard *before* update.

- **No null overwrite of NOT NULL:** Before `$project->update($validated)`, if `in_charge` or `overall_project_budget` exists in `$validated` and value is **null**, it is replaced with existing project value (or `0.00` for budget). So the array passed to `update()` never contains null for these two keys.  
  **Ref:** `M2/M2_3_Step1_Projects_Table_Protection.md` §1 (GeneralInfoController), §2.

### 2.3 LogicalFrameworkController

- **No creation of incomplete rows:** Per-row guards added: create objective only when `objectiveData['objective']` exists, is not null, and non-empty after trim; same for result, risk, activity (verification default `''`); timeframe: month non-empty after trim, is_active default `false`. Empty/null entries are skipped.  
  **Ref:** `M2/M2_4_Step2_LogicalFramework_Row_Integrity.md` §1, §2.

- **No null into NOT NULL:** Verification and is_active never null when passed to model; month trimmed string or row skipped.  
  **Ref:** `M2/M2_4_Step2_LogicalFramework_Row_Integrity.md` §2 (table).

- **No phantom row creation:** Only rows with minimum viable content are created; M1 guard still decides whether to run the section at all.  
  **Ref:** `M2/M2_4_Step2_LogicalFramework_Row_Integrity.md` §4 (M1 unchanged).

### 2.4 Numeric integrity

- **empty() fixes applied:** IESFamilyWorkingMembersController, IAHEarningMembersController, IESExpensesController, IAHBudgetDetailsController — condition on monthly_income/amount changed from `empty()` to `$value !== null && $value !== ''`.  
  **Ref:** `M2/M2_5_Step3_Numeric_Zero_Integrity.md` §1, §2.

- **0 preserved:** Rows with `monthly_income = 0` or `amount = 0` are now created; only null or empty string skip.  
  **Ref:** `M2/M2_5_Step3_Numeric_Zero_Integrity.md` §3.

### 2.5 Conclusion on update path

- **Partial update erasing existing data:** M1 skip-empty guards prevent delete+recreate when section is absent or empty for the 18 protected controllers. M2 does not change when section runs; it only ensures that when mutation runs, NOT NULL columns do not receive null and numeric 0 is preserved. For in-scope controllers, partial update does not erase existing section data when the section key is absent or empty.  
  **Ref:** `M1_CLOSURE_REPORT.md` §6; `M2_CLOSURE_REPORT.md` §4.

- **Submitting incomplete draft corrupting existing records:** Draft save and full submit both supported; merge (missing key) and null-guard ensure `in_charge` and `overall_project_budget` are never written as null. LogicalFramework and numeric controllers only create valid rows or skip; no NOT NULL violation or 0-drop from the documented update path. Incomplete draft submit does not corrupt existing NOT NULL or numeric data for the documented scope.  
  **Ref:** `M2_CLOSURE_REPORT.md` §4, §5.

---

## SECTION 3 — Submit → Approve → Revert Cycle Integrity

*From M4 and M5 documentation.*

### 3.1 Submit

- **Status change only:** `ProjectStatusService::submitToProvincial()` sets status to `SUBMITTED_TO_PROVINCIAL` and saves; logs status change. No mutation of financial or section data.  
  **Ref:** `M4/M4_1_Workflow_Transition_Audit.md` §2.2; `M5/M5_PreDeployment_Project_Workflow_Dashboard_Safety_Audit.md` (flow through service).

### 3.2 Approve

- **sanctioned persisted:** After `ProjectStatusService::approve()` or `approveAsCoordinator()`, the controller gets financials from resolver and sets `$project->amount_sanctioned` and `$project->opening_balance` then `$project->save()`.  
  **Ref:** `M5/M5_PreDeployment_Project_Workflow_Dashboard_Safety_Audit.md` §1 (amount_sanctioned and opening_balance persistence).

- **opening_balance persisted:** Same path; both CoordinatorController and GeneralController (approve as Coordinator) persist opening_balance from resolver after approval transition.  
  **Ref:** `M5/M5_PreDeployment_Project_Workflow_Dashboard_Safety_Audit.md` §1.

### 3.3 Revert

- **sanctioned cleared:** `applyFinancialResetOnRevert()` sets `amount_sanctioned = 0` when current status is approved; invoked in all five revert methods before status change and save.  
  **Ref:** `M4/M4_2_Financial_Revert_Integrity.md`; `M5/M5_PreDeployment_Project_Workflow_Dashboard_Safety_Audit.md` §1 (Revert clears sanctioned).

- **opening_balance recalculated:** `opening_balance = amount_forwarded + local_contribution` in same reset; idempotent when status is not approved.  
  **Ref:** `M4/M4_2_Financial_Revert_Integrity.md` (Objective, Implementation detail).

- **Project remains editable:** New status after revert is one of the reverted statuses (e.g. REVERTED_BY_COORDINATOR, REVERTED_TO_EXECUTOR), which are in `ProjectStatus::getEditableStatuses()`.  
  **Ref:** `M4/M4_1_Workflow_Transition_Audit.md` §1.1 (Editable/Submittable); `app/Constants/ProjectStatus.php` getEditableStatuses() in M2.1 audit.

### 3.4 Re-submit after revert

- **No stale financial state:** Revert has already set sanctioned = 0 and opening_balance = forwarded + local. On re-submit only status changes (submit → forwarded → approve); no residual approved financials.  
  **Ref:** `M4/M4_2_Financial_Revert_Integrity.md` (Why Stale Sanctioned Was Dangerous; after M4.2 this is removed).

- **No locked fields:** Editable statuses include all reverted statuses; executor/applicant can edit and resubmit. No approval-only lock documented that would block update or submit after revert.  
  **Ref:** `M5/M5_PreDeployment_Project_Workflow_Dashboard_Safety_Audit.md` §4 (Index listing: executor index uses notApproved(); reverted are not approved).

---

## SECTION 4 — Project Editability After Revert

*From M4, M5, and ProjectStatus constants.*

- **User can edit general info:** GeneralInfoController update is driven by ProjectController@update; editability is governed by permission and status. Reverted statuses are in `getEditableStatuses()`; no doc states that general info is read-only after revert.  
  **Ref:** `M4/M4_1_Workflow_Transition_Audit.md` §1.1; `M2/M2_1_NotNull_Write_Path_Audit.md` (UpdateProjectRequest used on update).

- **User can edit logical framework:** LogicalFrameworkController update runs when the section is meaningfully filled (M1 guard); status does not lock the section. Reverted projects are editable; no doc states logical framework is locked after revert.  
  **Ref:** `Implemented/M1_LOGICAL_FRAMEWORK_GUARD_IMPLEMENTATION.md`; M2.4 applies to update() and store().

- **User can update budget:** BudgetController is in the update path; M1 guard protects budget section. No doc states budget is locked for reverted projects.  
  **Ref:** `M1_CLOSURE_REPORT.md` §2 (BudgetController guarded).

- **No approval-only locks remain:** After revert, status is non-approved; ProjectController index for executor uses `notApproved()`, so reverted projects appear in the editable list. Permissions and status semantics (getEditableStatuses) indicate no approval-only lock remains after revert.  
  **Ref:** `M5/M5_PreDeployment_Project_Workflow_Dashboard_Safety_Audit.md` §4 (No leakage of approved into editable listing).

---

## SECTION 5 — Dashboard & Listing Stability

*From M3 and M5 documentation.*

- **Approved projects segregated correctly:** All approval detection uses `ProjectStatus::APPROVED_STATUSES` (three statuses) via `scopeApproved()`, `scopeNotApproved()`, or equivalent. ProvincialController and CoordinatorController use `->approved()` and `->notApproved()`; ExecutorController uses same status set for default “approved” filter.  
  **Ref:** `M5/M5_PreDeployment_Project_Workflow_Dashboard_Safety_Audit.md` §2; `M3/M3_3_Wave1_2_Approval_Status_Centralization.md`.

- **Reverted projects appear in correct listing:** Reverted statuses are not in APPROVED_STATUSES, so they are included in `notApproved()` and in “needs_work” / editable filters. Executor index is `notApproved()`; reverted projects therefore appear in the executor editable list, not in the “approved only” dashboard view.  
  **Ref:** `M5/M5_PreDeployment_Project_Workflow_Dashboard_Safety_Audit.md` §2, §4.

- **Draft/submitted appear correctly:** Draft and submitted/forwarded statuses are non-approved; they appear under notApproved() and in pending aggregation. Stage-separated aggregation (M3.3 Wave 1.1) uses approved portfolio (opening_balance) vs pending (max(0, overall - (forwarded + local))).  
  **Ref:** `M3/M3_3_Wave1_1_Stage_Separated_Aggregation.md`; `M5/M5_PreDeployment_Project_Workflow_Dashboard_Safety_Audit.md` §3.

- **No leakage between scopes:** Approved totals use opening_balance only; pending uses the pending formula. No use of amount_sanctioned alone for aggregation; reverted projects are excluded from approved scopes.  
  **Ref:** `M5/M5_PreDeployment_Project_Workflow_Dashboard_Safety_Audit.md` §3, §4.

---

## SECTION 6 — Remaining Known Risks (Project Module Only)

*Excludes: report workflow redesign, funding source allocation. Includes: project-level, update path, lifecycle.*

### 6.1 Unguarded delete-recreate surfaces (project-related sections)

Eight controllers remain unguarded by M1 (delete-then-recreate without skip-empty guard): IIES/IIESFamilyWorkingMembersController, EduRUTAnnexedTargetGroupController, ILP/StrengthWeaknessController, IAH/IAHEarningMembersController, ILP/BudgetController, IAH/IAHBudgetDetailsController, ILP/RevenueGoalsController, ILP/RiskAnalysisController. If a project update omits these sections or sends empty data, existing section data can still be wiped.  
**Ref:** `M1_CLOSURE_REPORT.md` §5.  
**Classification:** **MEDIUM — Requires monitoring.** Bounded and documented; affects type-specific sections, not core general info / LogicalFramework / M2-protected numerics. Mitigation: avoid partial payloads that omit these sections until guarded or backlog addressed.

### 6.2 Legacy empty LogicalFramework rows

Any pre-existing rows with empty `result`/`risk`/`objective` text (or similar) were created before M2.4. M2 does not alter or delete legacy data; it only prevents *new* malformed rows.  
**Ref:** `M2_CLOSURE_REPORT.md` §3, §4.  
**Classification:** **LOW — Cosmetic / audit.** No workflow deadlock; optional cleanup or audit can be done separately.

### 6.3 Rollback on approval validation failure (GeneralController)

When budget validation fails after `approveAsCoordinator()`, GeneralController sets status back to `FORWARDED_TO_COORDINATOR` and saves (rollback). This is a deliberate exception path, not normal workflow; financials are not persisted on that path.  
**Ref:** `M5/M5_PreDeployment_Project_Workflow_Dashboard_Safety_Audit.md` §1 (Controller status assignments).  
**Classification:** **LOW — Cosmetic.** Documented and intentional; no data corruption.

### 6.4 Summary table

| Risk | Classification | Notes |
|------|----------------|--------|
| Eight unguarded delete-recreate section controllers | MEDIUM — Requires monitoring | Project-level section data at risk only when those sections are present and payload omits/empties them; documented and bounded. |
| Legacy empty LogicalFramework rows | LOW — Cosmetic | Pre-existing; no new such rows from current path. |
| GeneralController approval rollback (status revert on validation fail) | LOW — Cosmetic | Intentional; no HIGH impact. |

**No HIGH — Must fix before deployment** identified for project lifecycle, update path, or revert/resubmit flow within the reviewed documentation scope.

---

## SECTION 7 — Final Answer (Clear Yes/No)

**Is the project lifecycle fully workable for:**

**Create → Update → Submit → Approve → Revert → Update → Resubmit**

**without data corruption or workflow deadlock?**

### Answer: **SAFE WITH MONITORING**

### Reasoning

1. **Create / Update:** M1 skip-empty guards protect 18 controllers from silent wipe when section is absent or empty. M2 ensures NOT NULL columns (projects.in_charge, overall_project_budget; LogicalFramework row-level) are not written null and numeric 0 is preserved on the documented update path. Partial update does not erase existing data for guarded sections; incomplete draft does not corrupt NOT NULL or numeric data in scope.  
   **Ref:** M1 closure §6–7; M2 closure §4–5.

2. **Submit:** Status-only change via ProjectStatusService; no data mutation.  
   **Ref:** M4.1, M5 §3.

3. **Approve:** Sanctioned and opening_balance persisted after service transition; dashboard and resolver use opening_balance for approved portfolio.  
   **Ref:** M5 §1, §3.

4. **Revert:** Sanctioned cleared, opening_balance set to forwarded + local in all five revert paths; project moves to an editable status.  
   **Ref:** M4.2; M5 §1, §3, §5.

5. **Update after revert / Resubmit:** Reverted projects are editable (status in getEditableStatuses); no stale financial state after revert; resubmit flows through submit → forward → approve again. No documented approval-only lock or deadlock.  
   **Ref:** M4.2, M5 §3, §4.

6. **Residual risk:** Eight unguarded delete-recreate section controllers (MEDIUM, monitoring). No HIGH risk identified for the core lifecycle. Therefore the lifecycle is **workable** without data corruption or deadlock for the protected path; **monitoring** is recommended for the eight unguarded surfaces until they are guarded or explicitly accepted.

---

**Project Lifecycle Stability Review Complete — No Code Changes Made**
