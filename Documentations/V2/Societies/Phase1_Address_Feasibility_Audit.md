# Phase 1 — Feasibility & Impact Audit: Add `address` to `societies`

**Date:** 2026-02-10  
**Scope:** Nullable `address` column on `societies`; full CRUD integration for General and Provincial.

---

## 1. Inspection Summary

| Item | Path | Status |
|------|------|--------|
| Societies migrations | `database/migrations/2026_01_13_144931_create_societies_table.php` | ✅ Single create migration; no other migrations alter `societies` |
| Society model | `app/Models/Society.php` | ✅ Fillable: province_id, name, is_active; no $guarded |
| GeneralController | `app/Http/Controllers/GeneralController.php` (storeSociety, updateSociety) | ✅ Inline Request::validate(); create/update arrays explicit |
| ProvincialController | `app/Http/Controllers/ProvincialController.php` (storeSociety, updateSociety) | ✅ Same pattern; province from user, not request |
| Routes | `routes/web.php` | ✅ Society routes under role:general and role:provincial; no change needed |
| General views | `resources/views/general/societies/create.blade.php`, `edit.blade.php`, `index.blade.php` | ✅ No address field; index table has Name, Province, Centers, Status, Actions |
| Provincial views | `resources/views/provincial/societies/create.blade.php`, `edit.blade.php`, `index.blade.php` | ✅ No address field |

---

## 2. Confirmations

| Check | Result |
|-------|--------|
| **Existing `address` on societies** | ❌ None. Table has: id, province_id, name, is_active, timestamps. |
| **Conflicting validation** | ✅ None. Adding `'address' => 'nullable|string|max:2000'` does not conflict with province_id/name/is_active rules. |
| **Mass-assignment** | ✅ Model uses `$fillable` (no `$guarded`). Adding `'address'` to fillable allows assignment. |
| **FormRequest for society** | ✅ None. General and Provincial use `$request->validate()` in controller. |
| **API resources** | ✅ No `app/Http/Resources` folder; no Society API resource restricting fields. |
| **Policies** | ✅ No `app/Policies` folder; society access is controller + route middleware only. |

---

## 3. Report

### Safe to proceed?

**Yes.** Adding a nullable `address` (text, max 2000 in validation) is safe.

### Structural risks

- **None.** New column is additive and nullable; no unique/index on address; no FK or constraint interaction. Provincial does not receive or set `province_id` from the form (province is from auth), so adding address does not affect province scoping.

### Unexpected dependencies

- **SocietySeeder** (`database/seeders/SocietySeeder.php`): Creates societies from existing data; does not need to set address (nullable). Optional follow-up: if seeder is run on fresh DB, address can remain null.
- **Other references:** Projects use string `society_name` (not FK to societies). Centers have optional `society_id`. No code found that serializes Society to JSON with an explicit allow-list that would exclude address.

### Conclusion

- No existing address column on societies.
- No validation, mass-assignment, FormRequest, API resource, or policy conflicts.
- Implementation can proceed as specified in Phase 2.
