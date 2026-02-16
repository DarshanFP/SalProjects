# Society–Project Mapping Implementation Summary

**Final Production Model – Execution Plan**

*For full detail, rollback strategy, validation rules, and risk matrix see: `Society_Project_Mapping_PhasePlan_V2.md` (Revision 4).*

---

## Objective

Migrate from string-based `society_name` to relational `society_id`, introduce `projects.province_id`, enforce global uniqueness of society names, and implement exclusive ownership model without data loss.

---

## Architecture Overview

### Province

- Immutable
- Cannot be deleted
- Referenced by projects and societies

### Society

- Unique name (database-level unique index)
- Either:
  - Global (`province_id` NULL)
  - Owned by one province
- Cannot be deleted
- Exclusive ownership

### Project

- `province_id` (required after backfill)
- `society_id` (nullable)
- `society_name` (temporary during transition)

### User

- `province_id`
- `society_id` (nullable)
- `society_name` (temporary during transition)

---

## Phase-Wise Execution

### Phase 0 – Production Audit

Read-only validation before schema changes.

**Checks:**

- Duplicate society names
- Projects without users
- Users without province
- `society_name` values not found in societies
- Projects that would fail province backfill

**Proceed only if audit clean.**

---

### Phase 1 – Schema Preparation

**Societies**

- Drop composite unique(province_id, name)
- Add unique(name)
- Make province_id nullable
- Keep index(province_id)

**Projects**

- Add province_id (nullable initially)
- Add society_id (nullable)
- Add index(province_id)
- Add index(society_id)
- Add composite index(province_id, society_id)

**Users**

- Add society_id (nullable)

---

### Phase 2 – Backfill

**Projects**

- province_id = user->province_id
- society_id = resolved by name (normalize typo; find by name only)

**Users**

- society_id = resolved by name

**Verify:**

- No null province_id on projects
- Minimal unmatched society_name cases (log and review)

---

### Phase 3 – Enforce Constraints

- Make projects.province_id NOT NULL

---

### Phase 4 – Dual Write

During create/update:

- Save society_id
- Save society_name from relation (and province_id for projects)
- Maintain fallback reads (relation ?? society_name)

---

### Phase 5 – Read Switch

- Dropdowns use society_id
- Reporting reads from relation
- Dashboard uses province_id + society_id

---

### Phase 6 – Cleanup

- Drop society_name columns (projects, users)
- Remove fallback logic

---

## Indexing Strategy

**Projects:**

- index(province_id)
- index(society_id)
- composite index(province_id, society_id)

**Benefits:**

- Faster province grouping
- Faster society grouping
- Efficient analytics queries

---

## Invariants

- Society names globally unique (DB)
- Ownership exclusive (one province or global)
- Societies cannot be deleted
- Provinces cannot be deleted
- Reports store historical society_name snapshot

---

## Risk Status

| Risk | Status |
|------|--------|
| Duplicate society names | Eliminated (DB constraint) |
| Backfill ambiguity | Eliminated (find by name only) |
| Province mismatch | Controlled (backfill from user; verify before NOT NULL) |
| Cascade deletion | Not applicable |
| Ownership instability | Accepted governance |

---

## Final State

- Deterministic relational structure: Province → Society → Project → Reports
- Clean foreign key graph
- No string dependency (society_name dropped)
- Optimized for reporting and analytics
- Safe for future scaling

---

## Go-Live Readiness Criteria

- Audit passes
- Backfill verified (zero null province_id on projects; minimal unmatched society_name)
- Dual write active
- Monitoring stable
- No unmatched society_name (or accepted and logged)

---

*End of Implementation Summary*
