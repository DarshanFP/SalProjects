# Society → Project Mapping
## Post-Phase 2 Execution Status

### 1. Completed Phases

#### Phase 0 — Audit & Data Cleanup
- Duplicate societies resolved
- Canonical societies seeded
- Province mismatch resolved
- Audit PASSED (WITH WARNINGS)
- Resolution rate documented

#### Phase 1 — Enforce Global Unique Society Name
- Dropped composite unique(province_id, name)
- Added unique(name)
- Verified duplicate insert fails
- Verified index list
- No application breakage

#### Phase 2 — Enforce users.province_id NOT NULL
- Backfill completed
- NULL count verified = 0
- No orphan province_id
- String vs relational province verified
- province_id set to NOT NULL
- FK integrity confirmed

### 2. Current Database State

**Societies:**
- unique(name) enforced
- province_id nullable

**Users:**
- province_id NOT NULL
- FK to provinces enforced
- province string still present (temporary)

**Projects:**
- No province_id yet
- No society_id yet

### 3. Remaining Warnings

- 1 project society typo
- 11 user society_name variants
- To be handled in society_id backfill phase

### 4. Risk Status

- Identity integrity secured
- Province integrity secured
- No destructive schema changes so far
- No data loss

### 5. Next Phase Preview

**Phase 3 — Introduce projects.province_id**

Purpose:
- Remove dependency on user join for province
- Improve reporting performance
- Prepare for society_id backfill

No execution yet.
