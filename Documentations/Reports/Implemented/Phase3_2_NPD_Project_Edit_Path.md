# Phase 3.2 — NPD Project Edit Path

**Date implemented:** 2026-06-13  
**Plan reference:** Phase 3 — NPD unknown project type on edit  
**Status:** ✅ Implemented

---

## Problem

`ProjectController@edit` had **no switch case** for `NEXT PHASE - DEVELOPMENT PROPOSAL`, while `show()` and `update()` already handled it.

Production log showed repeated warnings:

```
ProjectController@edit - Unknown project type {"project_type":"NEXT PHASE - DEVELOPMENT PROPOSAL"}
```

Impact: NPD projects opened edit with missing type-specific data; executors could not reliably maintain project context before writing reports.

---

## Solution

Added edit switch case aligned with Development Projects and existing show/update handlers:

```php
case ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL:
case 'NEXT PHASE - DEVELOPMENT PROPOSAL':
    $beneficiariesArea = $this->rstBeneficiariesAreaController->edit($project->project_id);
    Log::info('ProjectController@edit - NPD section data loaded ...');
    break;
```

Also normalized Development Projects case to use `ProjectType::DEVELOPMENT_PROJECTS` constant.

Enhanced default/unknown case and exception handler to log `project_id` + `project_type`.

---

## Files changed

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/ProjectController.php` | NPD + DP edit cases, logging |

**Note:** `update()` already had `ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL` (line ~1486). No change needed there.

---

## Verification

- [ ] Edit any NPD project → no "Unknown project type" warning in logs
- [ ] Log contains `NPD section data loaded`
- [ ] Edit form renders (same beneficiaries area partial as DP)
- [ ] Update NPD project still works (regression)

```bash
grep "Unknown project type.*NEXT PHASE" storage/logs/laravel.log
# Should stop after deploy
```

---

## Follow-up (later phases)

- Phase 7: Add explicit `field_mappings` for NPD in `config/budget.php`
- Phase 6: Decide if NPD needs dedicated create partial or generic fallback is sufficient
