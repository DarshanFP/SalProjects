# Phase 1A — Pattern Lock

## Purpose

Phase 1A patterns are now **locked** to prevent drift. IES module refactors are complete and verified. Any deviation introduces inconsistency, increases review cost, and risks regression. New styles invented for IIES, IAH, ILP, or other modules must be rejected.

---

## Canonical References

| Reference | Document | Role |
|-----------|----------|------|
| **Golden Template** | `IESPersonalInfoController.md` | Single-record fill pattern; the default for controllers that fill one model from request |
| **Array-Pattern Reference** | `IESFamilyWorkingMembersController.md` | Multi-record pattern; for controllers that create multiple records from array inputs (e.g. `member_name[]`, `particulars[]`) |
| **Attachment-only** | `IESAttachmentsController.md` | Exception to Pattern A/B; controller does not fill models; passes `$request` to model for file handling |

**Rule**: Any Phase 1A refactor MUST follow one of these two patterns, or the attachment-only exception. No other patterns are permitted.

---

## Allowed Phase 1A Patterns

### Pattern A — Single-Record Controllers (Golden Template)

- `$fillable` derived from model `getFillable()`, excluding `project_id` and auto-generated keys
- `$request->only($fillable)` for scoped input
- `ArrayToScalarNormalizer::forFillable($data, $fillable)` before `fill()`
- `$model->fill($data)` for persistence
- `$model->project_id` set by controller; never from request

### Pattern B — Multi-Record (Array-Driven) Controllers (Array-Pattern Reference)

- Scoped input to owned array keys only (e.g. `member_name`, `work_nature`, `monthly_income`)
- `$request->only($fillable)` where `$fillable` lists the array keys
- Scalar-to-array normalization when form sends scalar instead of array (single-item forms)
- Per-value scalar coercion inside loops when passing to `create()` — if value is array, use `reset($value)` or equivalent
- NO use of `ArrayToScalarNormalizer::forFillable()` — it would collapse arrays to single value

### Hybrid Controllers (Header + Arrays)

- Header fields: use Pattern A for scalar fillable
- Array fields (e.g. `particulars[]`, `amounts[]`): use Pattern B for iteration and per-value coercion

---

## Explicitly Forbidden Actions

- Inventing new Phase 1A styles not covered by Pattern A or Pattern B
- Mixing Phase 1B concerns (form field namespacing, attachment unification)
- Adding validation rules or FormRequest changes
- Refactoring attachments beyond Phase 0 (array handling in model)
- Refactoring multiple controllers in one change
- Touching forms, routes, or database schema
- Using `$request->all()` in any form

---

## Enforcement Rule

**Any Phase 1A refactor that does not follow one of the two canonical patterns MUST be rejected.**

Before merging, verify:
1. Controller matches Pattern A or Pattern B (or the documented hybrid)
2. Implementation MD references the correct canonical document
3. No forbidden actions are present

---

*Pattern Lock version: 1.0 — Established after IES module completion*
