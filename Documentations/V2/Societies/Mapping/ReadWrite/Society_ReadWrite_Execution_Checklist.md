# Society Read/Write Execution Checklist

## Structural Layer (Completed)

[x] Phase 0 — Audit
[x] Phase 1 — Unique society name
[x] Phase 2 — users.province_id
[x] Phase 3 — projects.province_id
[x] Phase 4 — society_id relational
[x] Phase 5B1 — Project dropdown refactor

---

## Application Layer (Pending)

[x] Phase 5B2 — Project read switch
[x] Phase 5B3 — User dropdown refactor
[x] Phase 5B4 — Report layer transition
[ ] Phase 5B5 — Legacy cleanup

---

## Sub-Wave Completion Requirements

Each sub-wave must:
- Pass regression tests
- Confirm no orphan relational references
- Confirm no string-based write path remains (where applicable)
- Produce freeze document
- Update roadmap status
- Update this checklist
- Include timestamp
- Include rollback confirmation

---

## Governance Discipline

- No phase may start until previous phase freeze is reviewed.
- No merging of multiple sub-waves.
- No undocumented changes.
- No direct DB edits.
