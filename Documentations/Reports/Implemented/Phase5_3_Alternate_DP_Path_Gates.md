# Phase 5.3 — Alternate DP Path Authorization

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Problem

`MonthlyDevelopmentProjectController` bypassed FormRequest authorization:
- `createForm()` / `create()` had no project approval or ownership checks
- `store()` used raw `$request->validate()` and allowed `user_id = null` when unauthenticated

## Changes

| Method | Gate |
|--------|------|
| `create()` | `MonthlyReportCreateAuthorization::abortUnlessAllowed()` |
| `createForm()` | Same |
| `store()` | Auth required + `abortUnlessAllowed()` + duplicate period check |

Removed test-only `user_id = null` fallback.

## Files

- `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php`

## Manual test

1. Log in as executor A → create report for A's approved project → OK
2. Change URL `project_id` to executor B's project → 403
3. Use non-approved project → 403 with message about approved projects only
