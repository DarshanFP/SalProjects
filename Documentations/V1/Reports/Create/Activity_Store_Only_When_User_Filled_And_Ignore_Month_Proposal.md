# Activity Store-When-User-Filled and Ignore-Month Logic — Proposal

**Document version:** 1.0  
**Scope:** Monthly Report Create/Edit — Objectives & Activities (partials and `ReportController::storeActivities`)  
**Requirement:** For activities, do **not** create a row when the user has not entered any user-filled field; in those cases, **ignore the `month`** value for that `project_activity_id` (i.e. do not store that activity at all).

---

## 1. Current Logic (as implemented)

### 1.1 Who Fills What

| Field | Source | User-filled? |
|-------|--------|--------------|
| **Objective block** | | |
| `objective` | Project (readonly) | No |
| `expected_outcome` | Project results (readonly) | No |
| `project_objective_id` | Project (hidden) | No |
| `not_happened`, `why_not_happened`, `changes`, `why_changes`, `lessons_learnt`, `todo_lessons_learnt` | User | **Yes** |
| **Activity block** | | |
| `activity` | Project (readonly) or user for “Add Other” | No (project) / **Yes** (Add Other only) |
| `project_activity_id` | Project (hidden) or empty for “Add Other” | No |
| **`month`** | **JavaScript** (`report-period-sync.js` → `syncReportPeriodToSections`) | **No** |
| `summary_activities` | User | **Yes** |
| `qualitative_quantitative_data` | User | **Yes** |
| `intermediate_outcomes` | User | **Yes** |

**How `month` is filled by JavaScript**

- `public/js/report-period-sync.js`:
  - On change of `#report_month` and `#report_year` (Basic Information), `syncReportPeriodToSections()` runs.
  - It selects all `select[name^="month["]` (activity “Reporting Month”) and sets `value` to the report month (1–12 or month name).
- Same sync runs on DOMContentLoaded if report month/year are already set, and when `addActivity` / `addOutlook` add new rows.
- So **`month` is driven by the report’s Reporting Month & Year**, not by explicit user choice per activity.

### 1.2 Backend: `ReportController::storeActivities`

**Location:** `app/Http/Controllers/Reports/Monthly/ReportController.php` (lines 484–544).

**Current “filled” check:**

```php
$filled = (trim((string) ($month ?? '')) !== '')
    || (trim((string) ($summary ?? '')) !== '')
    || (trim((string) ($qual ?? '')) !== '')
    || (trim((string) ($inter ?? '')) !== '')
    || (
        trim((string) ($projectActivityId ?? '')) === ''
        && trim((string) ($activityText ?? '')) !== ''
    );
```

**Implication:** If **only** `month` is non-empty (e.g. from JS sync) and the user left summary, qualitative, and intermediate outcomes empty (and it’s not an “Add Other” with activity text), `$filled` is still **true** and the activity **is stored** with only `month` set.

So: **month alone is currently enough to create an activity row.**

### 1.3 `DPActivity::hasUserFilledData()`

**Location:** `app/Models/Reports/Monthly/DPActivity.php`.

Used in **view/export** to decide which activities to show. It treats as “user-filled”:

- `month`
- `summary_activities`
- `qualitative_quantitative_data`
- `intermediate_outcomes`
- For “Add Other” (`project_activity_id` empty): `activity`

So it is aligned with the **current** backend store logic (month counts).

### 1.4 Frontend: `updateActivityStatus` (create/edit objectives partial)

**Location:** `resources/views/reports/monthly/partials/create/objectives.blade.php` (and equivalent in `partials/edit/objectives.blade.php`).

**Logic:**

- `Complete`: `month && summary && data && outcomes` all non-empty.
- `In Progress`: any one of `month`, `summary`, `data`, `outcomes` non-empty.
- `Empty`: all four empty.

So **month is treated like a user field** for the status badge. When JS has synced `month` but the user has not touched the textareas, the badge shows “In Progress”.

---

## 2. Stated Requirement

- **`month`** is filled by JavaScript; it must **not** be treated as a user-filled field for the purpose of “should we store this activity?”
- If the user has **not** entered any of the real user-filled activity fields, then:
  - **Do not create** an activity row for that `project_activity_id` (or that activity slot).
  - **Ignore `month`** for that activity (we never persist it, because we never persist the row).

So: **only store an activity when at least one user-filled field is non-empty; if none are filled, ignore that activity (and its month) on create/update.**

---

## 3. Definition of “User-Filled” for Activities

For the **store/ignore** decision, “user-filled” activity fields are:

1. **`summary_activities`** — user types in “Summary of Activities”
2. **`qualitative_quantitative_data`** — user types in “Qualitative & Quantitative Data”
3. **`intermediate_outcomes`** — user types in “Intermediate Outcomes”
4. **`activity`** — **only when it is “Add Other”** (`project_activity_id` is empty); then the user types the activity text.

**Excluded:**

- **`month`** — filled by JS from report period; does **not** count as user-filled for the store check.

---

## 4. Suggested Changes

### 4.1 Backend: `ReportController::storeActivities`

**Change the `$filled` condition** so that `month` does **not** contribute:

**From:**

```php
$filled = (trim((string) ($month ?? '')) !== '')
    || (trim((string) ($summary ?? '')) !== '')
    || (trim((string) ($qual ?? '')) !== '')
    || (trim((string) ($inter ?? '')) !== '')
    || (
        trim((string) ($projectActivityId ?? '')) === ''
        && trim((string) ($activityText ?? '')) !== ''
    );
```

**To:**

```php
// Month is filled by JS (report-period-sync), not by user. Only store when at least
// one user-filled field is present; otherwise ignore this activity (and its month).
$filled = (trim((string) ($summary ?? '')) !== '')
    || (trim((string) ($qual ?? '')) !== '')
    || (trim((string) ($inter ?? '')) !== '')
    || (
        trim((string) ($projectActivityId ?? '')) === ''
        && trim((string) ($activityText ?? '')) !== ''
    );
```

**Unchanged:**

- `if (! $filled) { continue; }` — we skip the activity and do not add it to `$keptActivityIds`, so it is not stored and is removed on sync.
- When we **do** store, we still set `'month' => $month` in `$activityData`; we are only changing the **condition** for whether to store the row.

**Effect:**

- If the user leaves summary, qualitative, intermediate (and for Add Other, activity) all empty, that activity is **not** stored, and its `month` is effectively ignored for that `project_activity_id` / slot.
- `storeActivities` is used from both `store` (create) and `update` (edit), so create and edit behave the same.

---

### 4.2 `DPActivity::hasUserFilledData()`

**Option A — Strict (recommended):** Align with the new “user-filled” definition and **remove `month`** from the check.  
Then, any row that has only `month` (e.g. from old data or a future bug) would not be considered “user-filled” and could be hidden in view/export.

```php
public function hasUserFilledData(): bool
{
    // month is JS-filled, not user-filled; exclude from check
    if (trim((string) ($this->summary_activities ?? '')) !== '') {
        return true;
    }
    if (trim((string) ($this->qualitative_quantitative_data ?? '')) !== '') {
        return true;
    }
    if (trim((string) ($this->intermediate_outcomes ?? '')) !== '') {
        return true;
    }
    if (trim((string) ($this->project_activity_id ?? '')) === '' && trim((string) ($this->activity ?? '')) !== '') {
        return true;
    }
    return false;
}
```

**Option B — Backward compatible:** Keep `month` in `hasUserFilledData()` so that historically stored “month-only” activities still appear in view/export.  
The important behavioural change is in **create/update** (we no longer create such rows); display of legacy data can stay as-is.

**Recommendation:** Prefer **Option A** for consistency, unless you need to keep showing legacy month-only rows.

---

### 4.3 Frontend: `updateActivityStatus`

**Option A — Strict:** Do **not** count `month` when deciding Empty / In Progress / Complete.  
Then, if only `month` is set (by JS), the badge stays **Empty**.

```javascript
// Exclude month (JS-filled). Only summary, qualitative, outcomes (and for Add Other: activity) count.
const summary = summaryTextarea ? summaryTextarea.value.trim() : '';
const data = dataTextarea ? dataTextarea.value.trim() : '';
const outcomes = outcomesTextarea ? outcomesTextarea.value.trim() : '';

if (summary && data && outcomes) {
    statusBadge.textContent = 'Complete';
    // ...
} else if (summary || data || outcomes) {
    statusBadge.textContent = 'In Progress';
    // ...
} else {
    statusBadge.textContent = 'Empty';
    // ...
}
```

**Option B — Keep current:** Continue to include `month` in the status.  
Then, when JS has set `month` and the user has not filled anything else, the badge shows “In Progress”.  
This gives a softer UX (user sees that “something” is there) but is inconsistent with the idea that `month` is not user input.

**Recommendation:** **Option A** if you want the badge to reflect only real user input; **Option B** if you prefer to show that the report month has been applied to that activity.

**Note:** For “Add Other” activities, `activity` is also user-filled. The current `updateActivityStatus` does not look at `activity`. If you want “Complete” to require the activity name for Add Other, the condition would need to be extended (e.g. check `project_activity_id` and `activity` for that case). That is optional and can be added later.

---

### 4.4 (Optional) Frontend: Clear `month` on submit when no user-filled fields

To harden against any future backend slip, you could clear `month` in the request for activities that have no user-filled content, so the server never sees a “user-less” month.

**Idea:** On `submit` (or in a `submit` listener), for each activity:

- If `summary`, `qualitative_quantitative_data`, and `intermediate_outcomes` are all empty, and it’s not an “Add Other” with `activity` filled, then set the corresponding `month` select to `value=""` (or remove the `name` so it is not submitted).

**Pros:** Extra safety if backend logic is ever reverted.  
**Cons:** Slightly more JS; must run before submit and correctly handle Add Other.  
**Recommendation:** Optional; the main fix is in the backend. Only add if you want defence-in-depth.

---

## 5. Summary Table

| Component | Current | Proposed |
|-----------|---------|----------|
| **`storeActivities` `$filled`** | `month` OR summary OR qual OR inter OR (Add Other + activity) | summary OR qual OR inter OR (Add Other + activity); **`month` excluded** |
| **`DPActivity::hasUserFilledData()`** | Includes `month` | **Option A:** Exclude `month`. **Option B:** Keep for legacy. |
| **`updateActivityStatus`** | month, summary, data, outcomes | **Option A:** summary, data, outcomes only. **Option B:** Keep including month. |
| **`month` in `$activityData` when we do store** | Set from request | **Unchanged** — we still save `month` when the activity is stored. |

---

## 6. Files to Touch

| File | Change |
|------|--------|
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | In `storeActivities`, remove `month` from the `$filled` expression. |
| `app/Models/Reports/Monthly/DPActivity.php` | Optionally, in `hasUserFilledData()`, remove the `month` branch. |
| `resources/views/reports/monthly/partials/create/objectives.blade.php` | Optionally, in `updateActivityStatus`, stop using `month` for Empty/In Progress/Complete. |
| `resources/views/reports/monthly/partials/edit/objectives.blade.php` | Same `updateActivityStatus` change as create, if applied. |

`report-period-sync.js` and the rest of the create/edit structure can stay as they are; the only behavioural change for “ignore month when user didn’t fill anything” is in the **store condition** and, optionally, in the display/status logic above.

---

## 7. Edge Cases

1. **Add Other with only `activity` filled**  
   - `$filled` is true; we store. `month` can be null or whatever is in the select; we still save it. No change needed.

2. **Report month not yet selected**  
   - `month` can be `""`. With the new `$filled`, that does not matter: we still only store when at least one user-filled field is present.

3. **Edit: existing activity with only `month` in DB**  
   - After the change, if the user clears the only user-filled field and saves, `storeActivities` will not include that activity in `$keptActivityIds`, and it will be **deleted** by the final `whereNotIn('activity_id', $keptActivityIds)->delete()`. So legacy “month-only” rows will disappear on next edit that doesn’t add user content. This is consistent with “do not keep activities that have no user input.”

4. **`syncReportPeriodToSections` and month in the form**  
   - JS can continue to sync `month` for all activity selects. Backend simply ignores those activities when none of the user-filled fields are filled; no need to change the sync.

---

## 8. Conclusion

- **`month`** is filled by `report-period-sync.js` from the report’s Reporting Month & Year; it is **not** user-filled.
- **Requirement:** Do not create an activity when the user has not entered any user-filled field; in that case, ignore `month` for that `project_activity_id`.
- **Minimal backend change:** In `storeActivities`, remove `(trim((string) ($month ?? '')) !== '')` from the `$filled` expression. That alone achieves the requirement for both create and edit.
- **Optional:** Align `hasUserFilledData()` and `updateActivityStatus` with the same “user-filled” definition (exclude `month`) for consistency; and, if desired, add a submit-time clear of `month` for activities with no user-filled content.

---

## 9. Implementation Status

| Phase | Status | Notes |
|-------|--------|-------|
| 1 | Done (2026-01-21) | Backend `storeActivities` — exclude `month` from `$filled` |
| 2 | Done (2026-01-21) | `DPActivity::hasUserFilledData` — exclude `month` |
| 3 | Done (2026-01-21) | 3.2 create `updateActivityStatus`; 3.3 edit `updateActivityStatus`; 3.4 edit `addActivity` (`project_activity_id` hidden for Add Other) |
| 4 | Skipped | Clear month on submit (optional hardening) |
