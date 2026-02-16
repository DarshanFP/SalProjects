# Strategy Consistency Audit — Implemented Guarded Controllers

**Mode:** STRICTLY READ-ONLY | **No code changes. No controller edits. Documentation only.**

**Source:** All Guard Implementation and Guard Rule Spec documents in `Documentations/V2/FinalFix/Implemented/`.

---

## Summary of Existing Strategy

The implemented guards follow a **single dominant pattern** defined in `M1_GUARD_RULE_SPEC.md`:

1. **Guard + Delete-Recreate:** Before any transaction or delete, the controller calls a private **“meaningfully filled”** method (or a **file-presence** method for attachment controllers) with the same normalized input used for the create loop. If the method returns **false**, the controller **returns early** with a success-shaped response (JSON 200 or redirect with success message) and does **not** run delete or create. If it returns **true**, the existing delete-then-recreate logic runs unchanged.

2. **No diff-based sync:** No controller computes a diff between request and DB or performs incremental update/delete of individual rows by identity. All data-section controllers use **full replace**: delete all rows for the project (or parent+children), then create from the request payload.

3. **No row identity in payload:** Request payloads do not send stable row IDs (e.g. primary key or UUID) for “update this row, delete that row.” Rows are identified only by project_id and position in the array; the only operation is “replace section by project_id.”

4. **Section absent / section empty:** Both are treated as **skip** (no mutation). “Section absent” = section key(s) missing or normalized to empty array. “Section present but empty” = key present but value is `[]` or every row/cell is null/empty after trim. In both cases the guard returns false and the controller skips delete and create.

5. **Intentional empty section:** The spec and implementations do **not** distinguish “user intentionally cleared the section” from “section not in request.” If the user submits the section with all empty rows, the guard treats it as “present but empty” and **skips** mutation, so **existing data is preserved**. To “clear” the section the user would need a separate flow (e.g. “Clear section” button that calls a dedicated endpoint or sends a sentinel value); that flow is not described in the implementation docs.

6. **Attachment controllers:** Use a **file-presence** guard (`hasAnyIAHFile`, `hasFile` over IES_FIELDS, etc.) instead of “meaningfully filled.” Skip when no file is uploaded; no delete of existing attachment rows when update is called with no new files. Mutation is handler-based (updateOrCreate parent + file rows), not bulk delete-recreate of a child table.

---

## STEP 1 — Extracted Data Per Implemented Controller

For each Guard Implementation document, the following was extracted.

| # | Controller | Project Type | Guard Method | Early Return? | Guard In store() | Guard In update() | Delete-Recreate Preserved? | Diff-Based Sync? | Row Identity (id/uuid)? | Intentional Empty | Section Absent |
|---|------------|--------------|--------------|---------------|------------------|-------------------|---------------------------|-----------------|-------------------------|-------------------|----------------|
| 1 | EduRUTTargetGroupController | EduRUT | isEduRUTTargetGroupMeaningfullyFilled | Yes | No | Yes (only) | Yes | No | No | Skip (preserve) | Skip |
| 2 | BudgetController | Institutional | isBudgetSectionMeaningfullyFilled | Yes | N/A | Yes | Yes | No | No | Skip (preserve) | Skip |
| 3 | LogicalFrameworkController | Institutional | isLogicalFrameworkMeaningfullyFilled | Yes | N/A | Yes | Yes | No | No | Skip (preserve) | Skip |
| 4 | IESFamilyWorkingMembersController | IES | isIESFamilyWorkingMembersMeaningfullyFilled | Yes | Yes | Via store() | Yes | No | No | Skip (preserve) | Skip |
| 5 | IESExpensesController | IES | isIESExpensesMeaningfullyFilled | Yes | Yes | Via store() | Yes | No | No | Skip (preserve) | Skip |
| 6 | RST BeneficiariesAreaController | RST/DP | isBeneficiariesAreaMeaningfullyFilled | Yes | Yes | Via store() | Yes | No | No | Skip (preserve) | Skip |
| 7 | RST GeographicalAreaController | RST | isGeographicalAreaMeaningfullyFilled | Yes | Yes | Via store() | Yes | No | No | Skip (preserve) | Skip |
| 8 | RST TargetGroupAnnexureController | RST | isTargetGroupAnnexureMeaningfullyFilled | Yes | Yes | Via store() | Yes | No | No | Skip (preserve) | Skip |
| 9 | LDP TargetGroupController | LDP | isLDPTargetGroupMeaningfullyFilled | Yes | Yes | Via store() | Yes | No | No | Skip (preserve) | Skip |
| 10 | IGE NewBeneficiariesController | IGE | isIGENewBeneficiariesMeaningfullyFilled | Yes | Yes | Via store() | Yes | No | No | Skip (preserve) | Skip |
| 11 | IGE BeneficiariesSupportedController | IGE | isIGEBeneficiariesSupportedMeaningfullyFilled | Yes | Yes | Via store() | Yes | No | No | Skip (preserve) | Skip |
| 12 | IGE OngoingBeneficiariesController | IGE | isIGEOngoingBeneficiariesMeaningfullyFilled | Yes | Yes | Via store() | Yes | No | No | Skip (preserve) | Skip |
| 13 | IGE IGEBudgetController | IGE | isIGEBudgetMeaningfullyFilled | Yes | Yes | Via store() | Yes | No | No | Skip (preserve) | Skip |
| 14 | IIESExpensesController | IIES | isIIESExpensesMeaningfullyFilled | Yes | Yes | Via store() | Yes | No | No | Skip (preserve) | Skip |
| 15 | IAHDocumentsController | IAH | hasAnyIAHFile | Yes | Yes | Yes | N/A (handler) | No | No | Skip (no files) | Skip (no files) |
| 16 | IESAttachmentsController | IES | hasFile over IES_FIELDS | Yes | Yes | Yes | N/A (handler) | No | No | Skip (no files) | Skip (no files) |
| 17 | IIESAttachmentsController | IIES | hasFile over IIES_FIELDS | Yes | No (store unmodified) | Yes | N/A (handler) | No | No | Skip (no files) | Skip (no files) |
| 18 | ILP AttachedDocumentsController | ILP | hasAnyILPFile | Yes | Yes | Yes | N/A (handler) | No | No | Skip (no files) | Skip (no files) |

**Guard logic (exact description):**

- **Meaningfully filled (data sections):** Normalize request to the same arrays/scalar structure used in the create loop. Guard returns **true** iff at least one “row” (or one parent/child in nested sections) has at least one meaningful value: non-empty trimmed string or non-null numeric (per M2.5, 0 is allowed for numeric). Otherwise **false**. Empty array or all null/empty → false.
- **File-presence (attachment sections):** Guard returns **true** iff `$request->hasFile($field)` is true for at least one of the section’s file fields. Otherwise **false**. No files → skip handler; existing attachments not deleted.

**Meaningful-fill criteria:**

- **Multi-row:** At least one row with at least one cell that is non-empty string (after trim) or non-null numeric.
- **Single-row (spec only; no single-row controller in this implemented set):** At least one section field non-empty or non-null.
- **Nested:** At least one meaningful parent field or at least one child row with meaningful content (e.g. objective text, or particular+amount).
- **Attachment:** At least one file uploaded for that section’s fields.

**Behaviour:**

| Scenario | Section absent | Section present but empty | Section partially filled |
|----------|----------------|---------------------------|---------------------------|
| All data-section controllers | Skip (no delete, no create) | Skip (no delete, no create) | Execute delete+recreate (full replace) |
| Attachment controllers | Skip (no handler call) | Skip (no handler call) | Execute handler (create/update parent + files) |

---

## STEP 2 — Categorize Strategy Per Controller

| Controller | Strategy Type | Section Absent Handling | Partial Section Handling | Mutation Strategy | Risk Level |
|------------|---------------|-------------------------|---------------------------|-------------------|------------|
| EduRUTTargetGroupController | Guard + Delete-Recreate | Skip (early return, no delete) | Execute (delete all, create from payload) | Full Replace | LOW |
| BudgetController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| LogicalFrameworkController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| IESFamilyWorkingMembersController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| IESExpensesController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| RST BeneficiariesAreaController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| RST GeographicalAreaController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| RST TargetGroupAnnexureController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| LDP TargetGroupController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| IGE NewBeneficiariesController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| IGE BeneficiariesSupportedController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| IGE OngoingBeneficiariesController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| IGE IGEBudgetController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| IIESExpensesController | Guard + Delete-Recreate | Skip | Execute | Full Replace | LOW |
| IAHDocumentsController | Guard Only (file presence) | Skip (no files) | Execute (handler) | Handler-based (updateOrCreate + files) | LOW |
| IESAttachmentsController | Guard Only (file presence) | Skip (no files) | Execute (handler) | Handler-based | LOW |
| IIESAttachmentsController | Guard Only (file presence) | Skip (no files) | Execute (handler) | Handler-based | LOW |
| ILP AttachedDocumentsController | Guard Only (file presence) | Skip (no files) | Execute (handler) | Handler-based | LOW |

**Strategy types used:** Guard + Delete-Recreate (14), Guard Only (4). **Mutation strategy:** Full Replace (14), Handler-based (4). **Risk level:** All LOW after guard (no controller in this set wipes sibling data when section absent/empty).

---

## STEP 3 — Inconsistencies Detected

1. **Guard placement: update() only vs store() (and update delegates)**  
   - **EduRUTTargetGroupController** is the only one that adds the guard in **update() only**; **store()** was not modified. So a direct call to `store()` with empty/absent section could still delete and create nothing (data loss) unless store() is never called without the section. Other controllers add the guard in **store()** and have update() delegate to store(), so both paths are protected.  
   - **Inconsistency:** One controller guards only update(); all others guard the path used by both store and update (store()).

2. **IIES Attachments: guard in update() only**  
   - **IIESAttachmentsController** guard was added to **update()** only; **store()** was not modified in that doc. So store() may still call the handler with no files (document says “store() already had presence guard” — if so, behaviour is consistent; if not, store() could create empty parent).  
   - **Inconsistency:** Only IIES Attachments documents guard in update() only; IES and ILP and IAH attachments guard both store() and update().

3. **Response type on skip: JSON vs redirect**  
   - Most data-section controllers return **JSON** (200 + message) on skip. **LDP TargetGroupController** and **IGE** controllers (New, BeneficiariesSupported, Ongoing, Budget) return **redirect** (e.g. `redirect()->route('projects.edit', $projectId)->with('success', '...')` or `redirect()->back()->with('success', '...')`).  
   - **Inconsistency:** Same logical “skip” behaviour but different response types depending on controller (JSON vs redirect). This is contract-consistent per controller but not a single standard.

4. **IGE NewBeneficiaries: return true when called from update()**  
   - When guard skips and `$shouldRedirect` is false (called from ProjectController@update), the controller returns **true** instead of JSON or redirect. No other controller returns a boolean to the caller.  
   - **Inconsistency:** One controller has a dual return (redirect vs true) for skip; others always return one response type.

5. **Attachment controllers: transaction on skip**  
   - **IAH** and **ILP** attachment controllers **commit** an already-started transaction before returning on skip (so the transaction is closed cleanly). **IES** and **IIES** attachment controllers do not start a transaction before the guard, so no commit on skip.  
   - **Inconsistency:** Some attachment controllers beginTransaction before the guard and commit on skip; others run the guard before beginTransaction.

6. **“Intentional empty” vs “section absent”**  
   - No implementation differentiates “user submitted section with all empty rows” (intentional clear) from “section key not in request.” In both cases the guard returns false and **existing data is preserved**. So “intentional empty” is currently treated as **skip** (preserve), not as “delete all and create zero.”  
   - **Consistency:** All treat empty as skip. If product requirement is “user can clear section and save,” that would require a different mechanism (e.g. explicit “clear” flag or endpoint).

7. **No controller uses row identity or partial row updates**  
   - No controller implements diff-based incremental sync or “update row by id / delete row by id.” All data-section controllers **wipe sibling rows** for the project when they do run mutation (delete all by project_id, then create from payload). Within a section, there is no “partial row update” — only full replace of the section.  
   - **Consistency:** All follow full-replace; none support partial row updates. So “sibling rows” are only preserved when the guard **skips**; when the guard **passes**, all rows in that section are replaced.

8. **Single-row sections (IAH SupportDetails, HealthCondition, PersonalInfo, etc.)**  
   - The **M1_GUARD_RULE_SPEC** defines rules for single-row sections, but the **implemented** docs in this folder are for multi-row, nested, or attachment sections. The remaining HIGH-risk controllers include single-row IAH controllers; they are **not** in the Implemented set. So the “meaningfully filled” pattern for single-row (at least one field non-empty) is specified but not yet applied in an implemented doc in this audit.

---

## STEP 4 — Architecture Feasibility Conclusion

**1. Can we safely reuse the same strategy for remaining HIGH-risk controllers?**

**YES.**  

- The pattern is proven: early return before delete when section is absent or empty; when present and meaningfully filled, run existing delete+recreate.  
- Remaining HIGH-risk controllers (IAH EarningMembers, BudgetDetails, SupportDetails, HealthCondition, PersonalInfo; ILP RiskAnalysis, StrengthWeakness, RevenueGoals, Budget; IIES FamilyWorkingMembers; EduRUT AnnexedTargetGroup) are either multi-row, single-row, or nested. The spec already defines single-row and multi-row semantics. Reuse: add a private `is*MeaningfullyFilled(...)` (or for single-row “at least one section field meaningful”), call it after normalizing request data and before any delete; on false, return the same success-shaped response the controller already uses. No change to delete or create logic when guard passes.

**2. Does current pattern support partial row updates safely?**

**NO.**  

- The current pattern does **not** support “update only row 2, leave row 1 and 3 unchanged.” Request payloads do not carry row identities; the only operation is “replace entire section by project_id.” So “partial row update” in the sense of patching specific rows by id is not supported. “Partial” in the sense of “section partially filled” is supported only as “run full replace with the rows that are in the request” (guard passes if at least one row is meaningful; then delete all and create from payload). So adding guards does not introduce partial row updates; it only prevents replace when the section is absent or empty.

**3. Is delete-recreate acceptable in this multi-section draft system?**

**YES.**  

- The docs and spec assume a multi-section draft form where each section is submitted as a full set of rows (or one row). No concurrent editing or row-level versioning is specified. Delete-recreate per section, when the section is meaningfully filled, is the defined mutation strategy and is acceptable as long as: (a) only one section (or a known set) is mutated per request, or (b) the full form posts all sections and each guarded controller skips when its section is absent/empty. ProjectController@update invokes all section controllers for the project type with the full request; guards ensure that only sections with present, meaningful data are mutated. So delete-recreate is acceptable and guards make it safe against partial updates (e.g. “General Info only”).

**4. Should we move toward diff-based incremental synchronization?**

**Not required for Phase 2.**  

- Diff-based sync would allow “update row by id, delete row by id” and avoid full replace. It would require: request payloads to include stable row identities (id or UUID), backend to compute diff (new/updated/deleted rows), and to apply only those changes. That is a larger architectural change. The current guard pattern already achieves the goal of “no data wipe when section absent/empty” with minimal change. **Recommendation:** Complete Phase 2 by applying the same guard pattern to remaining HIGH-risk controllers. Consider diff-based sync only if product later requires partial row updates, conflict resolution, or audit of row-level changes.

---

## STEP 5 — Risk Evaluation and Recommendation

**Risk evaluation:**

- **Implemented guarded controllers:** Risk is **LOW**. Section absent or empty → skip; no delete. Section meaningfully filled → full replace as before.  
- **EduRUT Target Group:** Slight inconsistency (guard in update() only); if store() is ever called without the section in request, it could still wipe. Prefer adding the same guard to store() for symmetry.  
- **Remaining HIGH-risk controllers:** Risk remains **HIGH** until the same pattern is applied; then it drops to LOW for those controllers.

**Recommendation for next architectural direction:**

1. **Reuse the same strategy for all remaining HIGH-risk controllers:** Add a single “meaningfully filled” (or file-presence) guard, early return before any delete, preserve delete-recreate when guard passes. Use the same normalization and response type (JSON vs redirect) as each controller already uses.  
2. **Standardize guard placement:** Prefer guarding **store()** (and let update() delegate to store()) so both entry points are protected; if a controller’s update() does not delegate to store(), add the guard at the start of update() before delete.  
3. **Do not introduce diff-based sync in Phase 2:** Stay with full-replace per section; guards only prevent replace when section is absent or empty.  
4. **Document “intentional empty” explicitly:** If the product needs “user can clear section and save,” define a separate behaviour (e.g. explicit clear flag or endpoint) and document it; current behaviour is “empty → skip → preserve existing.”

---

## Final Decision Section

| Question | Answer | Reason |
|----------|--------|--------|
| Can we safely reuse the same strategy for remaining HIGH-risk controllers? | **YES** | Same pattern (meaningfully filled + early return before delete) is used across 14 data-section controllers; spec covers multi-row, single-row, and nested; no structural difference for remaining controllers. |
| Does current pattern support partial row updates safely? | **NO** | Pattern is full replace per section; no row identity in payload; no diff-based update. |
| Is delete-recreate acceptable in this system? | **YES** | Multi-section draft form, full request per submit; guards ensure only sections with meaningful data are mutated; acceptable for current product assumptions. |
| Should we move toward diff-based incremental sync now? | **NO** | Not required to achieve “no wipe when section absent/empty.” Complete Phase 2 with guards; consider diff-based sync only if product requirements change. |

**Dominant strategy:** Guard + Delete-Recreate (with “meaningfully filled” or file-presence check); early return on skip; full replace when guard passes; no diff-based sync; no row identity in payload.

**Next step:** Apply this strategy to the 11 HIGH-risk controllers listed in Phase 2.1 / Phase 2.2, using the same guard placement (store() or update() as appropriate) and response shape (JSON or redirect) per controller.

---

*End of Strategy Consistency Audit. No code changes were made.*
