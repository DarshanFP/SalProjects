# Society Read/Write Transition Master Roadmap

## 1. Current Status

Structural Phases (Completed):
- Phase 0 — Audit & Data Cleanup ✅
- Phase 1 — Enforce Global Unique Society Name ✅
- Phase 2 — users.province_id NOT NULL ✅
- Phase 3 — projects.province_id Introduced & Enforced ✅
- Phase 4 — society_id Relational Identity Layer ✅
- Phase 5B1 — Project Dropdown Refactor + Dual-Write ✅

Application Transition (Pending):
- Phase 5B2 — Project Read Switch ✅ (2026-02-15)
- Phase 5B3 — User Dropdown Refactor ✅ (2026-02-15)
- Phase 5B4 — Report Layer Transition ✅ (2026-02-15)
- Phase 5B5 — Legacy Cleanup ⏳

---

## 2. Sub-Wave Breakdown

### Phase 5B2 — Project Read Switch (Contained)

Scope
- Display project society via relation ($project->society->name)
- Update exports to use relation
- Update search services to use society_id
- Maintain fallback to society_name for safety
- No changes to user layer

Risks
- Hidden string-based filtering
- Export format mismatch
- Performance regressions

Rollback Plan
- Revert display to society_name
- Revert export queries
- No schema rollback required

---

### Phase 5B3 — User Dropdown Refactor

Scope
- Convert executor/provincial forms to society_id
- Province-scoped dropdown enforcement
- Dual-write implementation
- Backend validation enforcement

Risks
- Incomplete coverage across user modules
- Cross-province assignment if validation incomplete

Rollback Plan
- Re-enable string-based dropdown
- Keep dual-write logic intact

---

### Phase 5B4 — Report Layer Transition

Scope
- Update report queries to use society_id
- Remove string-based joins
- Ensure export integrity
- Validate aggregation logic

Risks
- Historical report drift
- Cached query breakage

Rollback Plan
- Restore string-based report queries
- No schema changes involved

---

### Phase 5B5 — Legacy Cleanup

Scope
- Remove society_name from write paths
- Remove fallback logic
- Remove legacy controllers
- Future migration to drop society_name column

Risks
- Removing fallback too early
- Hidden legacy references

Rollback Plan
- Restore dual-write temporarily
- Reintroduce fallback logic if required

---

## 3. Execution Rules

1. One sub-wave per execution cycle.
2. No cross-wave implementation.
3. Each execution must:
   - Produce MD freeze file
   - Update this roadmap
   - Update execution checklist
   - Include timestamp
   - Include regression summary
4. No schema changes without explicit phase definition.
5. No direct DB edits outside migration discipline.

---

## 4. Mandatory MD Freeze Rule

Every future execution prompt MUST:

1. Create freeze file at:

Documentations/V2/Societies/Mapping/ReadWrite/
Society_ReadWrite_Phase5Bx_<ShortName>.md

2. Update:
   - This Master Roadmap
   - Execution Checklist

3. Embed in freeze file:
   - Updated roadmap snapshot
   - Updated checklist snapshot
   - Risk summary
   - Next planned sub-wave

No implementation is considered complete without freeze + checklist update.
