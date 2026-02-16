# Phase 5B3 — User Dropdown Refactor

**Completed:** 2026-02-15  
**Scope:** User create/edit forms only. No schema changes. No project module. No report changes. No dropping `society_name`. Dual-write maintained.

---

## 1. Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/GeneralController.php` | Helper `getSocietiesForProvince()`; province-scoped societies in create/edit; `society_id` validation with `Rule::exists`; dual-write on store/update (provincial + executor) |
| `app/Http/Controllers/ProvincialController.php` | Province-scoped `$societies` in create/edit executor & provincial; `society_id` validation; dual-write on store/update |
| `resources/views/general/executors/create.blade.php` | `society_id` select; societies from `$societiesByProvince` (id + name); JS uses society id |
| `resources/views/general/executors/edit.blade.php` | `society_id` select; options from `$societies`; hardcoded list removed |
| `resources/views/general/provincials/create.blade.php` | `society_id` select; `societiesByProvince` in JS; option value = id |
| `resources/views/general/provincials/edit.blade.php` | `society_id` select; options from `$societies`; JS `societiesByProvince` for province change |
| `resources/views/provincial/createExecutor.blade.php` | `society_id` select; options from `$societies`; hardcoded list removed |
| `resources/views/provincial/editExecutor.blade.php` | `society_id` select; options from `$societies`; hardcoded list removed |
| `resources/views/provincial/provincials/create.blade.php` | `society_id` select; option value `$society->id` |
| `resources/views/provincial/provincials/edit.blade.php` | `society_id` select; option value `$society->id`; selected by `society_id` |

---

## 2. Controller Changes

**GeneralController – helper (Phase 5B3):**

```php
private function getSocietiesForProvince(?int $provinceId): \Illuminate\Database\Eloquent\Collection
{
    return Society::active()
        ->where(function ($q) use ($provinceId) {
            $q->where('province_id', $provinceId)->orWhereNull('province_id');
        })
        ->orderBy('name')
        ->get();
}
```

**GeneralController – create provincial:** Pass `societiesByProvince` (per-province societies including global).  
**GeneralController – store provincial:** Validate `society_id` with `Rule::exists(..., ->where(province_id|null))`, then dual-write:

```php
$societyId = $request->filled('society_id') ? (int) $request->society_id : null;
$societyName = null;
if ($societyId) {
    $society = Society::findOrFail($societyId);
    $societyName = $society->name;
}
// User::create([..., 'society_id' => $societyId, 'society_name' => $societyName, ...]);
```

**GeneralController – executor:** Same pattern: province-scoped societies, `society_id` validation (required for executor), dual-write on create/update.

**ProvincialController – create executor:** Load `$societies` for provincial’s province (province + global), pass to view. Validate `society_id` required, scope to provincial’s `province_id`. Dual-write on create/update. Same for provincial create/edit.

---

## 3. Blade Changes

**Select name and value:**

- Before: `<select name="society_name">` with `<option value="{{ $society->name }}">` or hardcoded options.
- After: `<select name="society_id" id="society_id">` with `<option value="{{ $society->id }}">`.

**General executor edit (no JS dropdown):**

```blade
<select class="form-control" id="society_id" name="society_id" required>
    <option value="" disabled>Select Society / Trust</option>
    @foreach($societies ?? [] as $society)
        <option value="{{ $society->id }}" {{ old('society_id', $executor->society_id ?? '') == $society->id ? 'selected' : '' }}>{{ $society->name }}</option>
    @endforeach
</select>
```

**Provincial executor create (dynamic list from controller):**

```blade
<select class="form-control" id="society_id" name="society_id" required>
    <option value="" disabled selected>Select Society / Trust</option>
    @foreach($societies ?? [] as $society)
        <option value="{{ $society->id }}">{{ $society->name }}</option>
    @endforeach
</select>
```

All hardcoded society name options were removed from general/executors/edit, provincial/createExecutor, and provincial/editExecutor.

---

## 4. Validation Enforcement

**Province-scoped existence (block cross-province):**

```php
'society_id' => [
    'nullable',  // or 'required' for executor
    Rule::exists('societies', 'id')->where(function ($q) use ($provinceId) {
        $q->where('province_id', $provinceId)->orWhereNull('province_id');
    }),
],
```

- General: `$provinceId` from selected province (request or edited user’s province).
- Provincial: `$provinceId` from auth user’s province.

Null society is allowed only where the form allows it (e.g. provincial user society optional).

---

## 5. Dual-Write Confirmation

On every user save/update that touches society:

1. Resolve society from `society_id`: if present, `Society::findOrFail($id)`.
2. Set on user model:
   - `society_id` = `$society->id` or `null`
   - `society_name` = `$society->name` or `null`
3. No path writes only `society_name`; both are set together from the selected society (or both cleared when none selected).

---

## 6. Regression Results

| Test | Status |
|------|--------|
| Create executor (General) | Form uses society_id; validation and dual-write in place |
| Edit executor (General) | society_id dropdown from $societies; dual-write on update |
| Create provincial (General) | society_id; societiesByProvince; validation and dual-write |
| Edit provincial (General) | society_id; societies + societiesByProvince for province change |
| Create executor (Provincial) | society_id from $societies; validation and dual-write |
| Edit executor (Provincial) | society_id from $societies; dual-write |
| Create provincial (Provincial) | society_id; nullable; dual-write |
| Edit provincial (Provincial) | society_id; dual-write |
| Cross-province tamper | Blocked by Rule::exists(..., ->where(province_id \| null)) |
| Null society | Allowed where validation is `nullable` (e.g. provincial society) |
| Hardcoded lists | Removed from all user forms |

---

## 7. Risk Assessment

- **Nullable maintained:** `users.society_id` remains nullable; validation uses `nullable` or `required` per form.
- **Province restriction enforced:** All society_id validation scoped to user’s province or global (province_id null).
- **No schema change:** No migrations or DB structure changes.
- **Dual-write:** Both `society_id` and `society_name` set on save; no string-only write path in user flows.

---

## 8. Updated Roadmap Snapshot

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
- Phase 5B3 — User Dropdown Refactor ✅ (2026-02-15)

Application Transition (Pending):
- Phase 5B4 — Report Layer Transition ⏳
- Phase 5B5 — Legacy Cleanup ⏳
```

---

## 9. Updated Checklist Snapshot

```markdown
[x] Phase 5B2 — Project read switch
[x] Phase 5B3 — User dropdown refactor
[ ] Phase 5B4 — Report layer transition
[ ] Phase 5B5 — Legacy cleanup
```

---

**Next planned sub-wave:** Phase 5B4 — Report Layer Transition.
