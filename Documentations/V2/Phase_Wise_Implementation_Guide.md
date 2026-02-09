# Phase-wise Implementation Guide

**Purpose**: Execution manual for developers implementing the Phase-wise Refactor Plan. Converts planning documents into clear, step-by-step work with mandatory documentation.

**Audience**: Small team working on active production Laravel system.

---

# 0. SINGLE DEPLOY & VERIFICATION STRATEGY

**Fix-First, Deploy-Once**: All refactor phases (Phase 0 → Phase 1A → Phase 1B → Phase 2) are **completed first** using local testing. Deployment happens **only once** at the end.

## Execution Rules

1. **No phase may begin unless the previous phase has a sign-off MD** — even if no deployment has occurred.
2. **Deploy after phase sign-off** — not after each controller or item. Phase transitions require sign-off approval.
3. **Deployment verification happens ONLY AFTER** Phase 1A Final Sign-off, Phase 1B Sign-off, and Phase 2 Sign-off are complete.
4. **Local verification** — use local/staging to detect issues; apply fixes before final deployment.
5. **Pattern Lock** — Phase 1A patterns are non-negotiable; attachment-only controllers are documented exceptions, not new patterns.

## Sign-Off Files

| Phase | Sign-Off File | Blocks |
|-------|---------------|--------|
| 1A.3 | `Implementations/Phase_1A/PHASE_1A_3_SIGNOFF.md` | Phase 1A.4 continuation |
| 1A | `Implementations/Phase_1A/PHASE_1A_FINAL_SIGNOFF.md` | Phase 1B start |
| 1B | `Implementations/Phase_1B/PHASE_1B_SIGNOFF.md` | Phase 2 start |
| 2 | `Implementations/Phase_2/PHASE_2_SIGNOFF.md` | Deployment |

---

# 1. INTRODUCTION

## 1.1 Purpose of This Guide

This guide tells you:
- **How** to execute each phase
- **Where** to document completed work
- **What** counts as "done"

It does **not** replace the planning documents. It orchestrates them.

---

## 1.2 Relationship Between Documents

| Document | Role | When to Use |
|----------|------|-------------|
| **Phase_Wise_Refactor_Plan.md** | Master plan: phases, order, dependencies, verification, deferred items | Before starting any phase; to resolve scope and order questions |
| **Phase_1A_Refactor_Playbook.md** | Step-by-step refactor instructions for controllers | When refactoring any controller in Phase 1A |
| **Production_Errors_Analysis_070226.md** | Root-cause analysis of production errors | To understand what each fix addresses; for verification |
| **Phase_Wise_Implementation_Guide.md** (this doc) | Execution manual: how to run phases and document work | During implementation; to create and place implementation docs |
| **V2/Implementations/** | Evidence of completed work; audit trail | After each completed item; before marking phase done |

---

## 1.3 Document Flow

```
Planning / Analysis (static)          Implementation (dynamic)
────────────────────────────         ─────────────────────────

Phase_Wise_Refactor_Plan.md    ──►   Implementations/Phase_0/
Production_Errors_Analysis.md  ──►   Implementations/Phase_1A/
Phase_1A_Refactor_Playbook.md   ──►   Implementations/Phase_1B/
                                     Implementations/Phase_2/
```

---

# 2. DOCUMENTATION STRUCTURE (MANDATORY)

## 2.1 Folder Tree

```
Documentations/V2/
├── Phase_Wise_Refactor_Plan.md          # Planning
├── Phase_1A_Refactor_Playbook.md        # Planning
├── Production_Errors_Analysis_070226.md # Analysis
├── Phase_Wise_Implementation_Guide.md   # This guide
│
└── Implementations/
    ├── Phase_0/
    │   ├── 0.1_Applicant_Role.md
    │   ├── 0.2_IES_Attachments_Array.md
    │   ├── 0.3_IES_Education_Background_Scalar.md
    │   ├── 0.4_Budget_Overflow_Guard.md
    │   └── 0.5_ArrayToScalarNormalizer.md
    │
    ├── Phase_1A/
    │   ├── IESEducationBackgroundController.md
    │   ├── IESPersonalInfoController.md
    │   └── ... (one per refactored controller)
    │
    ├── Phase_1B/
    │   ├── IES_IIES_Attachment_Unification.md
    │   └── Form_Field_Namespacing.md
    │
    └── Phase_2/
        └── (design docs, one per architectural improvement)
```

---

## 2.2 Mandatory Rule

> **No implementation is complete without a corresponding MD file in V2/Implementations.**

- Before marking any phase item "done", create the implementation doc.
- The doc provides: what was changed, how it was verified, and traceability to the plan.

---

# 3. PHASE 0 — STABILIZATION

## 3.1 Objective

Stop crashes and invalid data at the boundary. No business logic change.

---

## 3.2 Execution Order

```
0.1 → 0.5 → 0.3 → 0.2 → 0.4
```

- **0.1** Applicant role (unblocks user creation)
- **0.5** ArrayToScalarNormalizer (utility)
- **0.3** IES Education Background scalar fill (depends on 0.5)
- **0.2** IES Attachments array handling
- **0.4** Budget overflow guard

---

## 3.3 Mapping to Production Errors

| Phase 0 Item | Addresses (from Production_Errors_Analysis_070226.md) |
|--------------|------------------------------------------------------|
| 0.1 | Error 1: "There is no role named `applicant` for guard `web`" |
| 0.2 | Error 4: "Call to a member function getClientOriginalExtension() on array" |
| 0.3 | Error 3: "Array to string conversion" (project_IES_educational_background) |
| 0.4 | Error 2: "Out of range value for column 'this_phase'" |
| 0.5 | Supports 0.3; shared defense for future fill() usage |

---

## 3.4 Implementation Documentation Rules

- **Location**: `Documentations/V2/Implementations/Phase_0/`
- **One MD per item**: e.g. `0.1_Applicant_Role.md`, `0.2_IES_Attachments_Array.md`, etc.
- **Created**: After the item is implemented and verified locally. (Deployment occurs after all phase sign-offs per Fix-First strategy.)

---

## 3.5 Phase 0 Implementation Doc Template

Create a new file for each Phase 0 item using this template:

```markdown
# Phase 0.X — [Item Name]

## Status
- [ ] Implemented
- [ ] Verified (local)

## Reference
- Plan: Phase_Wise_Refactor_Plan.md → Phase 0 → 0.X
- Error: Production_Errors_Analysis_070226.md → Error N (if applicable)

## Changes
- **Files modified**: (list)
- **What changed**: (brief description)

## Verification
- **Local verification**: (what was checked)
- **Log scan**: (errors that should be absent)
- **Post-deploy (final)**: Run Phase 0.6 verification after single deployment
- **Rollback**: (if needed, what to revert)

## Date
- Implemented: YYYY-MM-DD
- Verified: YYYY-MM-DD
```

---

## 3.6 Phase 0 Completion Criteria

Phase 0 is **done** when:

- [ ] All five items (0.1–0.5) implemented and verified locally
- [ ] Each item has an MD file in `Implementations/Phase_0/`
- [ ] Phase 0.6 verification (from Refactor Plan) run locally within 24–48h of Phase 0 completion
- [ ] No rollback signals detected
- [ ] Production log verification deferred until single deployment (after all phase sign-offs)

---

# 4. PHASE 1A — INPUT & AUTHORITY HARDENING

## 4.1 Canonical Reference

**Phase_1A_Refactor_Playbook.md** is the canonical source for controller refactors.

- Follow it exactly.
- Do not improvise; deviations require explicit approval.

---

## 4.2 Scope Rules

| Allowed | Forbidden |
|---------|-----------|
| Replace `$request->all()` with `$request->only($fillable)` | Change form field names |
| Apply `ArrayToScalarNormalizer::forFillable()` | Change routes |
| Exclude `project_id`, auto-generated keys from fillable | Add new validation rules (beyond existing) |
| Refactor one controller at a time | Refactor multiple controllers in one PR |
| Leave transaction, logging, response unchanged | Touch attachments, file handling |

---

## 4.3 Execution Order

```
1A.1 (roles) → 1A.2 (budget) → 1A.3 (IES/IIES controllers) → 1A.4 (remaining controllers)
```

- **1A.1**: Centralize role definitions — `UserRole` constant, seeder, `roles:sync` command
- **1A.2**: Budget derived-field enforcement — server-side recalculation, clamp
- **1A.3**: IES and IIES controllers — scoped input per playbook
- **1A.4**: Remaining project controllers — one module per sprint

---

## 4.4 Controller-by-Controller Execution

1. Pick one controller from the Phase 1A list (see Refactor Plan).
2. Open **Phase_1A_Refactor_Playbook.md**.
3. Follow Steps 1–5.
4. Run the Completion Checklist.
5. Create implementation doc in `Implementations/Phase_1A/`.
6. Verify locally; no deployment until phase sign-off.
7. Repeat for next controller.

---

## 4.5 Implementation Documentation Rules

- **Location**: `Documentations/V2/Implementations/Phase_1A/`
- **One MD per controller**: e.g. `IESEducationBackgroundController.md`, `IESPersonalInfoController.md`
- **Created**: After controller refactor is complete and verified locally. (Deployment occurs after phase sign-off per Fix-First strategy.)

---

## 4.6 Phase 1A Controller Implementation Doc Template

```markdown
# Phase 1A — [Controller Name]

## Status
- [ ] Refactored
- [ ] Merged
- [ ] Verified

## Reference
- Playbook: Phase_1A_Refactor_Playbook.md
- Plan: Phase_Wise_Refactor_Plan.md → Phase 1A

## Controller
- **Class**: `App\Http\Controllers\...\[Name]`
- **Methods refactored**: store, update (if applicable)

## Changes
- **Fillable keys used**: (list)
- **Excluded keys**: (e.g. project_id, auto-generated)
- **Pattern**: `$request->only()` + `ArrayToScalarNormalizer::forFillable()`

## Verification
- [ ] No `$request->all()` in refactored methods
- [ ] Behavioral: create/edit flow works
- [ ] Log: no new errors for this project type

## Date
- Refactored: YYYY-MM-DD
- Verified: YYYY-MM-DD
```

---

## 4.7 Phase 1A Completion Criteria

Phase 1A is **done** when:

- [ ] 1A.1 Role centralization implemented (roles:sync verified locally)
- [ ] 1A.2 Budget derived-field enforcement implemented
- [ ] 1A.3 All IES and IIES controllers refactored (8 controllers)
- [ ] 1A.4 All remaining project controllers refactored (IAH, ILP, IGE, CCI, RST, LDP, EduRUT, CIC)
- [ ] Each refactored controller has an MD in `Implementations/Phase_1A/`
- [ ] No `$request->all()` in any project sub-controller
- [ ] Phase 1A completion checklist (Refactor Plan) satisfied
- [ ] `PHASE_1A_FINAL_SIGNOFF.md` approved—required before Phase 1B

---

# 5. PHASE 1B — STRUCTURAL CLEANUP

## 5.1 Entry Conditions

**Do not start Phase 1B until Phase 1A Final Sign-off is approved.**

- `Implementations/Phase_1A/PHASE_1A_FINAL_SIGNOFF.md` must be signed off.
- All project controllers use scoped input.
- Role centralization done.
- Budget derived-field enforcement done.

---

## 5.2 What Belongs Here

- **1B.1**: Unify IES and IIES attachment handling — IES writes to `project_IES_attachment_files`; shared pattern with IIES
- **1B.2**: Form field namespacing — `family_contribution` → `ies_education[family_contribution]` etc.; conditional partial inclusion

---

## 5.3 Documentation Expectations

- **Location**: `Documentations/V2/Implementations/Phase_1B/`
- **One MD per 1B item**: e.g. `IES_IIES_Attachment_Unification.md`, `Form_Field_Namespacing.md`
- **Content**: Files changed, migration path, verification steps, rollback plan

---

## 5.4 Phase 1B Completion Criteria

Phase 1B is **done** when:

- [ ] 1B.1 IES/IIES attachment unification implemented and verified locally
- [ ] 1B.2 Form field namespacing implemented; controllers updated to read prefixed input
- [ ] Each item has an MD in `Implementations/Phase_1B/`
- [ ] No field collisions between project-type sections
- [ ] `Implementations/Phase_1B/PHASE_1B_SIGNOFF.md` approved—required before Phase 2

---

# 6. PHASE 2 — ARCHITECTURAL PREVENTION

## 6.1 Purpose

Long-term abstractions to prevent future drift. Design-heavy.

---

## 6.2 What Belongs Here

- FormDataExtractor / validation layer
- ProjectAttachmentHandler
- BoundedNumericService / DecimalBounds config
- FormSection / ownedKeys
- RoleGuard / role contract

See **Phase_Wise_Refactor_Plan.md** → Phase 2 for full list.

---

## 6.3 Documentation Expectations

- **Location**: `Documentations/V2/Implementations/Phase_2/`
- **One MD per abstraction**: design doc before implementation; implementation summary after
- **Content**: Problem, abstraction design, migration path, risks, long-term benefits

---

## 6.4 Phase 2 Entry Conditions

- Phase 1A Final Sign-off and Phase 1B Sign-off approved.
- "EXPLICITLY DEFERRED" prerequisites (Refactor Plan) met for the target area.

---

# 7. OPERATING PRINCIPLES

## 7.1 Separation: Guidance vs Implementation

| Type | Location | Purpose |
|------|----------|---------|
| **Guidance** | V2/*.md (plan, playbook, analysis) | What to do; why; how to do it correctly |
| **Implementation** | V2/Implementations/ | What was done; evidence; traceability |

- Guidance is updated only when the plan or playbook changes.
- Implementation docs are written when work is completed.

---

## 7.2 Auditability and Traceability

- Each implementation doc references the plan/playbook section.
- Production errors (Production_Errors_Analysis) map to Phase 0 items.
- Phase 0.6 verification confirms errors are gone.
- Phase 1A docs reference the playbook and checklist.

---

## 7.3 Why This Structure Prevents Regression and Drift

- **Single source of truth**: Plan and playbook define intent; no ambiguity.
- **Mandatory documentation**: "Done" requires an implementation doc; no silent changes.
- **Verification built-in**: Each phase has completion criteria and log-based checks.
- **Deferred items explicit**: "Do Not Touch" list prevents Scope creep and risky refactors.
- **Incremental execution**: One controller, one item at a time; no big-bang rewrites.

---

# 8. QUICK REFERENCE

| Phase | Start When | Doc Location | Done When |
|-------|------------|--------------|-----------|
| 0 | Immediately | Implementations/Phase_0/ | All 5 items implemented, verified locally, documented |
| 1A | Phase 0 complete | Implementations/Phase_1A/ | All controllers refactored, documented; PHASE_1A_FINAL_SIGNOFF.md approved |
| 1B | Phase 1A Final Sign-off approved | Implementations/Phase_1B/ | 1B.1, 1B.2 done, documented; PHASE_1B_SIGNOFF.md approved |
| 2 | Phase 1B Sign-off approved; deferred prerequisites met | Implementations/Phase_2/ | Per Phase 2 plan; PHASE_2_SIGNOFF.md approved |

**Deployment**: After all phase sign-offs (Fix-First, Single-Deploy).

---

*Implementation Guide version: 1.1 — Fix-First, Single-Deploy strategy*
