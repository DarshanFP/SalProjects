# Phase 11 — Manual QA Results

**Status:** ⏸ Pending execution on staging  
**Last automated pre-flight:** 2026-06-13  
**Tester:** _unassigned_

---

## Automated pre-flight (`php artisan reports:qa-readiness`)

| Check | Result |
|-------|--------|
| Budget config (12/12 types) | ✅ Pass |
| SOA view partials (12/12) | ✅ Pass |
| Core report routes | ✅ Pass |
| `MonthlyReportCreateAuthorization` | ✅ Present |
| `DPReport::createWithProjectSnapshot()` | ✅ Present |
| `ReportResourceLookup` (Phase 9) | ✅ Present |

Re-run before manual session:

```bash
php artisan reports:qa-readiness
```

---

## Sign-off summary

| Metric | Value |
|--------|-------|
| Types tested | 0 / 12 |
| Types passed | 0 / 12 |
| Overall | ⏸ **PENDING** |

---

## Results by project type

Use **P** = pass, **F** = fail, **S** = skipped, **—** = not run.

| # | Project type | Test project ID | Steps 1–10 | Extra | Result | Tester | Date | Notes |
|---|--------------|-----------------|------------|-------|--------|--------|------|-------|
| 1 | Development Projects | | — | Overview/resolver | — | | | |
| 2 | Livelihood Development Projects | | — | Annexure | — | | | |
| 3 | Residential Skill Training Proposal 2 | | — | Trainee profiles | — | | | |
| 4 | PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER | | — | Inmate profiles | — | | | |
| 5 | Institutional Ongoing Group Educational proposal | | — | Age profiles / IGE SOA | — | | | |
| 6 | Individual - Livelihood Application | | — | ILP contributions | — | | | |
| 7 | Individual - Access to Health | | — | IAH contributions | — | | | |
| 8 | Individual - Ongoing Educational support | | — | IES contributions | — | | | |
| 9 | Individual - Initial - Educational support | | — | IIES contributions | — | | | |
| 10 | CHILD CARE INSTITUTION | | — | Edit without statistics | — | | | |
| 11 | NEXT PHASE - DEVELOPMENT PROPOSAL | | — | Canonical create / budget | — | | | |
| 12 | Rural-Urban-Tribal | | — | Phase-based SOA | — | | | |

---

## Step detail (optional per type)

Copy block below for each type as needed.

### Template: `{Project Type}` — `{project_id}`

| Step | Pass? | Notes |
|------|-------|-------|
| 1 Create form | | |
| 2 Draft save | | |
| 3 Basic info | | |
| 4 SOA | | |
| 5 Photo/attachment | | |
| 6 Submit | | |
| 7 Revert | | |
| 8 Edit/resubmit | | |
| 9 Approve | | |
| 10 Aggregated quarterly | | |
| Extra check | | |

---

## Issues log

| ID | Type | Step | Severity | Description | Fixed? |
|----|------|------|----------|-------------|--------|
| | | | | | |

---

## Final sign-off

- [ ] All 12 types passed standard workflow + extra checks
- [ ] No open **Critical** or **High** issues
- [ ] Automated test suite green (`MonthlyReportTest`, budget report tests)

**Approved by:** _______________ **Date:** _______________
