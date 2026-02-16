# Society → Project Mapping
## Post-Phase 3 Execution Status

### 1. Completed Phases

#### Phase 0 — Audit & Data Cleanup
- Duplicate societies resolved
- Canonical societies seeded
- Audit PASSED (WITH WARNINGS)

#### Phase 1 — Global Unique Society Name
- Dropped composite unique(province_id, name)
- Added unique(name)
- Verified duplicate insert rejection

#### Phase 2 — users.province_id Enforcement
- Backfilled province_id
- Verified 0 NULL
- Enforced NOT NULL
- FK integrity confirmed

#### Phase 3 — projects.province_id Introduction
- Added province_id (nullable)
- Added FK to provinces(id)
- Backfilled from users.province_id
- Verified 0 NULL
- Verified 0 orphan province_id
- Enforced NOT NULL
- Index present

### 2. Current Database State

**Societies:**
- unique(name)
- province_id nullable

**Users:**
- province_id NOT NULL
- province string still present (temporary)
- FK enforced

**Projects:**
- province_id NOT NULL
- FK enforced
- province now directly accessible without user join
- society_id not yet introduced
- society_name string still active

### 3. Data Integrity Status

- 0 duplicate society names
- 0 users with NULL province_id
- 0 projects with NULL province_id
- 0 orphan province references
- Society resolution still 85.7%

### 4. Risk Assessment

- Identity layer stable
- Province layer stable
- No destructive schema operations performed
- No data loss
- System operational

### 5. Outstanding Work

- Introduce society_id to users and projects
- Backfill society_id
- Implement dual-write
- Switch reads to society_id
- Cleanup legacy society_name

### 6. Next Phase Candidate

**Phase 4 — Introduce society_id (nullable, indexed, FK)**

No execution performed yet.
