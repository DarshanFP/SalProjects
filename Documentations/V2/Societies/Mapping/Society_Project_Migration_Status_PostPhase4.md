# Society → Project Mapping
## Post-Phase 4 Execution Status

### Projects
- **society_id** NOT NULL
- Fully backfilled
- FK enforced (societies.id, onDelete restrict)
- **province_id** NOT NULL

### Users
- **society_id** nullable
- Backfilled where resolvable
- Unresolved allowed
- FK enforced (societies.id, onDelete restrict)

### Integrity Checks
- 0 orphan society_id in projects
- 0 NULL society_id in projects
- users.society_id allowed NULL

### Risk Assessment
- No data loss
- society_name retained (projects and users)
- Safe to implement dual-write next phase

### Next Phase Preview
**Phase 5 — Dual-write & Read Switch**
