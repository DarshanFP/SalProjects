# CCI – Age Profile Phasewise Implementation Plan (Local Development)

---

## Purpose

This document ensures a structured, safe, and architecture-aligned implementation of the CCI Age Profile fixes. It serves as a working checklist during local development, addressing schema gaps, validation gaps, normalization alignment, and null-safety issues identified in the structural alignment audit.

---

## Architectural Principles

- **Single row per project** — use `updateOrCreate(['project_id' => $projectId], $validated)`
- **Validation-driven persistence** — controller must use `$request->validated()` / `$validator->validated()` only
- **Schema ↔ Model ↔ Validation ↔ Normalization alignment** — all layers must match
- **No structural redesign** — preserve existing table structure and relationships
- **No pattern change** — follow single-row-per-project pattern used by other CCI sections

---

## PHASE 1 — Schema Alignment

- [ ] Create new migration (add columns to existing table)
- [ ] Add `education_below_5_other_specify` as `string(255)->nullable()`
- [ ] Add `education_6_10_other_specify` as `string(255)->nullable()`
- [ ] Add `education_11_15_other_specify` as `string(255)->nullable()`
- [ ] Add `education_16_above_other_specify` as `string(255)->nullable()`
- [ ] Do NOT modify original migration file
- [ ] Verify target table name: `project_CCI_age_profile`
- [ ] Run migration locally
- [ ] Confirm all 4 new columns exist with correct types

---

## PHASE 2 — Model Alignment

- [ ] Add `education_below_5_other_specify` to `$fillable`
- [ ] Add `education_6_10_other_specify` to `$fillable`
- [ ] Add `education_11_15_other_specify` to `$fillable`
- [ ] Add `education_16_above_other_specify` to `$fillable`
- [ ] Confirm no `$guarded` conflict with `$fillable`
- [ ] Confirm `CCI_age_profile_id` boot logic unchanged

---

## PHASE 3 — Validation Expansion

- [ ] Add `education_6_10_primary_school_prev_year` with nullable + OptionalIntegerRule
- [ ] Add `education_6_10_primary_school_current_year` with nullable + OptionalIntegerRule
- [ ] Add `education_6_10_bridge_course_prev_year` with nullable + OptionalIntegerRule
- [ ] Add `education_6_10_bridge_course_current_year` with nullable + OptionalIntegerRule
- [ ] Add `education_6_10_other_prev_year` with nullable + OptionalIntegerRule
- [ ] Add `education_6_10_other_current_year` with nullable + OptionalIntegerRule
- [ ] Add `education_6_10_other_specify` with nullable|string|max:255
- [ ] Add `education_11_15_secondary_school_prev_year` with nullable + OptionalIntegerRule
- [ ] Add `education_11_15_secondary_school_current_year` with nullable + OptionalIntegerRule
- [ ] Add `education_11_15_high_school_prev_year` with nullable + OptionalIntegerRule
- [ ] Add `education_11_15_high_school_current_year` with nullable + OptionalIntegerRule
- [ ] Add `education_11_15_other_prev_year` with nullable + OptionalIntegerRule
- [ ] Add `education_11_15_other_current_year` with nullable + OptionalIntegerRule
- [ ] Add `education_11_15_other_specify` with nullable|string|max:255
- [ ] Add `education_16_above_undergraduate_prev_year` with nullable + OptionalIntegerRule
- [ ] Add `education_16_above_undergraduate_current_year` with nullable + OptionalIntegerRule
- [ ] Add `education_16_above_technical_vocational_prev_year` with nullable + OptionalIntegerRule
- [ ] Add `education_16_above_technical_vocational_current_year` with nullable + OptionalIntegerRule
- [ ] Add `education_16_above_other_prev_year` with nullable + OptionalIntegerRule
- [ ] Add `education_16_above_other_current_year` with nullable + OptionalIntegerRule
- [ ] Add `education_16_above_other_specify` with nullable|string|max:255
- [ ] Apply all rules in both StoreCCIAgeProfileRequest and UpdateCCIAgeProfileRequest
- [ ] Confirm `validated()` returns all 22+ data fields

---

## PHASE 4 — Integer Normalization Alignment

- [ ] Remove `education_below_5_other_prev_year` from INTEGER_KEYS (schema type is string)
- [ ] Remove `education_below_5_other_current_year` from INTEGER_KEYS (schema type is string)
- [ ] Add `education_6_10_primary_school_prev_year` to INTEGER_KEYS
- [ ] Add `education_6_10_primary_school_current_year` to INTEGER_KEYS
- [ ] Add `education_6_10_bridge_course_prev_year` to INTEGER_KEYS
- [ ] Add `education_6_10_bridge_course_current_year` to INTEGER_KEYS
- [ ] Add `education_11_15_secondary_school_prev_year` to INTEGER_KEYS
- [ ] Add `education_11_15_secondary_school_current_year` to INTEGER_KEYS
- [ ] Add `education_11_15_high_school_prev_year` to INTEGER_KEYS
- [ ] Add `education_11_15_high_school_current_year` to INTEGER_KEYS
- [ ] Add `education_16_above_undergraduate_prev_year` to INTEGER_KEYS
- [ ] Add `education_16_above_undergraduate_current_year` to INTEGER_KEYS
- [ ] Add `education_16_above_technical_vocational_prev_year` to INTEGER_KEYS
- [ ] Add `education_16_above_technical_vocational_current_year` to INTEGER_KEYS
- [ ] Confirm INTEGER_KEYS contains exactly 16 integer columns (all integer DB fields)
- [ ] Confirm normalization behavior unchanged for string fields

---

## PHASE 5 — Controller Null Safety

- [ ] In AgeProfileController edit(), replace `first()` with `firstOrNew(['project_id' => $projectId])`
- [ ] Confirm edit view receives an object (never null)
- [ ] Confirm show() method unchanged
- [ ] Confirm store() and update() methods unchanged

---

## PHASE 6 — Local Functional Testing

- [ ] Create new CCI project
- [ ] Fill all Age Profile fields (all 4 age categories)
- [ ] Save and verify all 22+ data fields in DB
- [ ] Edit project and change partial fields
- [ ] Leave numeric fields blank → confirm null stored
- [ ] Enter 0 in numeric field → confirm 0 stored (not null)
- [ ] Submit invalid numeric input → confirm validation error
- [ ] Open edit for project with no Age Profile row → confirm no crash
- [ ] Verify show view displays all fields correctly

---

## Implementation Completion Criteria

- [ ] All 22+ data fields persist correctly on create
- [ ] All 22+ data fields persist correctly on update
- [ ] No validation gaps (schema, model, validation aligned)
- [ ] No null edit crash (edit view always receives object)
- [ ] Schema and validation fully aligned
- [ ] Integer normalization aligned with DB types
- [ ] Architecture preserved (single-row-per-project, validation-driven)
