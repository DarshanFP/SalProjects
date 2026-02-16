# Phase 5B2 — Project Read Switch

**Completed:** 2026-02-15  
**Scope:** Projects module only. No schema changes. No user layer. No report layer. No removal of `society_name`. No legacy cleanup.

---

## 1. Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/ProjectController.php` | Read: `society_name` → relation + fallback; eager load `society` in `getProjectDetails`, `show` |
| `app/Http/Controllers/Projects/ExportController.php` | Export text uses relation + fallback; eager load `society` in `downloadDoc` |
| `app/Services/ProjectQueryService.php` | Search: join `societies`, search `societies.name` and keep `projects.society_name` fallback |
| `resources/views/projects/partials/Show/general_info.blade.php` | Display: `optional($project->society)->name ?? $project->society_name` |
| `resources/views/projects/partials/OLdshow/general_info.blade.php` | Same display fallback |
| `resources/views/projects/partials/not working show/general_info.blade.php` | Same display fallback |

---

## 2. Read Logic Changes

**Display (Blade):**

```blade
{{-- OLD --}}
<td class="value">{{ $project->society_name }}</td>

{{-- NEW (Phase 5B2) --}}
<td class="value">{{ optional($project->society)->name ?? $project->society_name }}</td>
```

**API response (ProjectController@getProjectDetails):**

```php
// OLD
'society_name' => $project->society_name,

// NEW (Phase 5B2)
'society_name' => optional($project->society)->name ?? $project->society_name,
```

**Eager load (no N+1):**

- `ProjectController@getProjectDetails`: `->with(['user', 'society', ...])`
- `ProjectController@show`: `->with([..., 'user', 'society', ...])`
- `ExportController@downloadDoc`: `->with([..., 'user', 'society'])`

---

## 3. Export Changes

**Project DOC export (ExportController):**

- General info section text no longer uses raw `$project->society_name` only.
- Uses relation with fallback so exported DOC shows canonical society name when available.

```php
// OLD
$section->addText("Society Name: {$project->society_name}");

// NEW (Phase 5B2)
$section->addText("Society Name: " . (optional($project->society)->name ?? $project->society_name));
```

Export is PhpWord (single-project document). No SQL export was changed; report modules were not modified.

---

## 4. Search Changes

**ProjectQueryService::applySearchFilter:**

- Text search by society: join `societies` and search `societies.name`.
- Kept `projects.society_name` in the same search for legacy/mismatched rows.
- Select limited to `projects.*` so Eloquent hydration remains correct.

```php
// OLD
return $query->where(function($q) use ($searchTerm) {
    $q->where('project_id', 'like', "%{$searchTerm}%")
      ->orWhere('project_title', 'like', "%{$searchTerm}%")
      ->orWhere('society_name', 'like', "%{$searchTerm}%")
      ->orWhere('place', 'like', "%{$searchTerm}%");
});

// NEW (Phase 5B2)
$query->leftJoin('societies', 'projects.society_id', '=', 'societies.id')
      ->select('projects.*');
return $query->where(function ($q) use ($searchTerm) {
    $q->where('projects.project_id', 'like', "%{$searchTerm}%")
      ->orWhere('projects.project_title', 'like', "%{$searchTerm}%")
      ->orWhere('societies.name', 'like', "%{$searchTerm}%")
      ->orWhere('projects.society_name', 'like', "%{$searchTerm}%")
      ->orWhere('projects.place', 'like', "%{$searchTerm}%");
});
```

---

## 5. Regression Results

| Test | Status |
|------|--------|
| Project index listing | Manual check: index does not display society; no change required |
| Project show page | Society shown via relation + fallback; `society` eager loaded |
| Project filtering by society | Search uses join + societies.name and society_name fallback |
| Project export (DOC) | Society name in DOC uses relation + fallback |
| Province-scoped project listing | Unchanged; no society filter in controller |
| Performance (N+1) | `society` eager loaded in show, getProjectDetails, downloadDoc |

- No null relation crash: `optional($project->society)->name` used everywhere.
- Fallback present: `?? $project->society_name` in all read paths.
- No string-based filtering by society_name only: search uses relation + fallback.

---

## 6. Risk Assessment

- **Fallback present:** All read paths use `optional($project->society)->name ?? $project->society_name`.
- **No schema change:** No migrations or DB changes.
- **Safe rollback:** Revert commits for display, export, and search; no schema rollback.

---

## 7. Updated Roadmap Snapshot

```markdown
## 1. Current Status

Structural Phases (Completed):
- Phase 0 — Audit & Data Cleanup ✅
- Phase 1 — Enforce Global Unique Society Name ✅
- Phase 2 — users.province_id NOT NULL ✅
- Phase 3 — projects.province_id Introduced & Enforced ✅
- Phase 4 — society_id Relational Identity Layer ✅
- Phase 5B1 — Project Dropdown Refactor + Dual-Write ✅
- Phase 5B2 — Project Read Switch ✅ (2026-02-15)

Application Transition (Pending):
- Phase 5B3 — User Dropdown Refactor ⏳
- Phase 5B4 — Report Layer Transition ⏳
- Phase 5B5 — Legacy Cleanup ⏳
```

---

## 8. Updated Checklist Snapshot

```markdown
## Application Layer (Pending)

[x] Phase 5B2 — Project read switch
[ ] Phase 5B3 — User dropdown refactor
[ ] Phase 5B4 — Report layer transition
[ ] Phase 5B5 — Legacy cleanup
```

---

**Next planned sub-wave:** Phase 5B3 — User Dropdown Refactor.
