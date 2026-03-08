# Return URL Pattern — Feasibility Audit

**Date:** 2026-03-05  
**Context:** Provincial project list filters and pagination; post-action redirects  
**Reference:** Preserve_Filters_On_Redirect_Pattern.md  

---

## Executive Summary

**Current behaviour:** After Forward Project, Revert Project, and Update Society, controllers redirect with `redirect()->route('provincial.projects.list')`, which drops all query parameters. Users lose fy, center, status, project_type, society_id, page, and per_page.

**Feasibility:** A **Return URL pattern** is feasible and recommended. Two implementation options:

1. **Laravel 8+ `redirect()->route(..., request()->query())`** — Pass current request query when redirecting. Requires the *controller* to receive the return context (e.g. via hidden input `return_to` or by using `redirect()->back()->withQueryString()` when the referer is the list).
2. **Explicit `return_to` parameter** — Forms pass `return_to={{ url()->full() }}` (or encoded list URL); controller validates and redirects to it. More reliable when the user did not arrive via the list (e.g. deep link, email).

**Recommendation:** Prefer **`redirect()->route('provincial.projects.list', request()->query())`** with query params passed from the form (hidden inputs). If the action is always triggered from the list page, **`redirect()->back()->withQueryString()`** is simpler and avoids open-redirect risk. For maximum reliability and consistency, use **hidden `return_to`** with strict URL validation (same host, path prefix `/provincial/projects-list`).

---

## Step 1 — Project Actions Identified

| Action | Route Name | Controller Method | Line (approx.) |
|--------|------------|-------------------|----------------|
| Forward project | `projects.forwardToCoordinator` | `ProvincialController::forwardToCoordinator($project_id)` | 1893–1905 |
| Revert project | `projects.revertToExecutor` | `ProvincialController::revertToExecutor($request, $project_id)` | 1879–1891 |
| Update project society | PATCH `provincial.projects.updateSociety` | `ProvincialController::updateProjectSociety($request, $project_id)` | 869–893 |

**Note:** Approve project and Reject project for provincial scope are not in ProvincialController (handled by Coordinator/General). Report actions (forward report, revert report) redirect to `provincial.report.list` and have the same filter-loss issue on the report list.

---

## Step 2 — Current Redirect Behaviour

| Method | Current Redirect |
|--------|------------------|
| `revertToExecutor` | `redirect()->route('provincial.projects.list')->with('success', ...)` |
| `forwardToCoordinator` | `redirect()->route('provincial.projects.list')->with('success', ...)` |
| `updateProjectSociety` | `redirect()->route('provincial.projects.list')->with('success', ...)` |

All three use the bare list route with **no query parameters**, so fy, center, status, project_type, society_id, page, per_page are lost.

---

## Step 3 — redirect()->back() Analysis

**Pros:**
- If the user submitted the form from the project list page, the HTTP Referer is that list URL (with query string). Laravel’s `redirect()->back()` would send them back to that URL.
- With Laravel 8+ `redirect()->back()->withQueryString()`, the redirect URL would preserve the referer’s query string when applicable.

**Cons:**
- **Referer not guaranteed:** Some clients or security policies strip Referer; some flows (e.g. open in new tab, link from email) may not set it. Then `back()` falls back to a configurable fallback URL (often `/` or previous session URL), which may not be the list.
- **Cross-tab / bookmark:** User might open the project from dashboard, then use list in another tab; after action, “back” could go to dashboard.
- **Validation redirects:** If validation fails and the controller does `redirect()->back()->withErrors()`, returning “back” is correct; combining with a safe fallback (e.g. list with request query) keeps behaviour consistent.

**Conclusion:** `redirect()->back()->withQueryString()` improves behaviour when the referer is the list, but is not sufficient when referer is missing or wrong. A **return URL** or **explicit query params** is more reliable.

---

## Step 4 — Return URL Pattern (return_to)

**Idea:** Form includes a hidden input with the current full URL (or only the path + query). After success, the controller redirects to that URL.

**Example:**
- List URL: `/provincial/projects-list?status=submitted_to_provincial&fy=2025-26&center=A&page=3`
- Form: `<input type="hidden" name="return_to" value="{{ url()->full() }}">` (or value from a dedicated “list URL” variable).
- Controller: `if ($request->filled('return_to') && $this->isSafeReturnUrl($request->return_to)) { return redirect($request->return_to)->with(...); }`

**Feasibility:** Yes. Requires:
1. Blade: add hidden input (or pass query array and build URL in controller).
2. Controller: read `return_to`, validate it (see Security), then redirect.
3. Fallback: if no `return_to` or invalid, use `redirect()->route('provincial.projects.list')` or `redirect()->route('provincial.projects.list', request()->query())`.

**Alternative without return_to:** Pass only query params: form sends hidden inputs for each param (fy, center, status, project_type, society_id, page, per_page). Controller uses `redirect()->route('provincial.projects.list', $request->only([...]))`. No open redirect; only list route with allowed params.

---

## Step 5 — Blade Button Integration

**Current (ProjectList.blade.php):**
- Forward: `<form method="POST" action="{{ route('projects.forwardToCoordinator', $project->project_id) }}">` + `@csrf` + submit button.
- Revert: same pattern for `projects.revertToExecutor`.

**Recommended integration:**

**Option A — Hidden return_to (full URL):**
```blade
<form method="POST" action="{{ route('projects.forwardToCoordinator', $project->project_id) }}" ...>
    @csrf
    <input type="hidden" name="return_to" value="{{ url()->full() }}">
    <button type="submit" ...>Forward</button>
</form>
```

**Option B — Preserve query only (no return_to):**
```blade
<form method="POST" action="{{ route('projects.forwardToCoordinator', $project->project_id) }}" ...>
    @csrf
    @foreach(request()->query() as $key => $value)
        <input type="hidden" name="return_query[{{ $key }}]" value="{{ $value }}">
    @endforeach
    <button type="submit" ...>Forward</button>
</form>
```
Controller: `redirect()->route('provincial.projects.list', $request->input('return_query', []))`.

**Option B** avoids open redirects; **Option A** needs strict URL validation.

**Update Society modal:** The modal form is submitted via JS and the action URL is set per row. When building the form or the fetch URL, append current query string or a `return_to` param (for GET-style redirect target, you’d typically pass query params or a signed return URL).

---

## Step 6 — Controller Redirect Strategy

**Option 1 — Use request query (from hidden inputs):**
```php
// After success
$query = $request->only(['fy', 'center', 'role', 'project_type', 'status', 'society_id', 'user_id', 'page', 'per_page']);
$query = array_filter($query, fn ($v) => $v !== null && $v !== '');
return redirect()->route('provincial.projects.list', $query)->with('success', ...);
```
Form passes these as hidden inputs (e.g. `return_query` array or single `return_to`).

**Option 2 — return_to with validation:**
```php
if ($request->filled('return_to') && $this->isSafeReturnUrl($request->return_to)) {
    return redirect($request->return_to)->with('success', ...);
}
return redirect()->route('provincial.projects.list')->with('success', ...);
```

**Option 3 — back() with fallback:**
```php
return redirect()->back()->withQueryString()->with('success', ...);
```
No form change; behaviour depends on referer.

---

## Step 7 — Security Analysis

**Open redirect risk:** If the controller redirects to `$request->input('return_to')` without validation, an attacker could pass `return_to=https://evil.com` and use the app as a redirector. This must be prevented.

**Safe approaches:**

1. **Allow only query params, no full URL:** Use Option B (hidden inputs for fy, center, status, etc.) and build the redirect with `route('provincial.projects.list', $params)`. No user-controlled URL.
2. **Validate return_to:**
   - Allow only same host: parse URL and require `host === config('app.url')` (or request()->getHost()).
   - Allow only path prefix: e.g. path must start with `/provincial/projects-list`.
   - Reject `javascript:`, `data:`, etc.
3. **Signed return URL:** Build the list URL server-side, sign it (e.g. `URL::signedRoute(...)` or a signed query param), put it in the form; controller verifies signature before redirecting. More work but very safe.

**Recommendation:** Prefer **Option B (pass query params only)** so the redirect target is always the list route with allowed parameters. If you still use `return_to`, enforce same-host and path-prefix validation.

---

## Step 8 — Pagination and Filter Preservation

**List URL query params (from projectList / ProjectList.blade.php and controller):**
- fy  
- project_type  
- user_id  
- status  
- center  
- society_id  
- page  
- per_page  

All of these should be preserved on redirect. Using `request()->query()` (or equivalent hidden fields) when redirecting to `provincial.projects.list` preserves them. Pagination links already use `withQueryString()` in Laravel’s paginator when the list is built with the same request; the missing piece is only the **post-action redirect** including these params.

---

## Step 9 — Risk Summary

| Risk | Severity | Mitigation |
|------|----------|------------|
| Open redirect | High | Do not redirect to raw `return_to` without validation; prefer passing only query params and using `route('provincial.projects.list', $params)`. |
| Malformed return_to | Medium | If using return_to, validate scheme, host, path prefix; reject invalid. |
| Missing query params | Low | Use `request()->query()` or hidden inputs so all list filters and page are preserved. |
| Validation failure redirect | Low | Keep `redirect()->back()->withErrors()` for validation; ensure fallback after success uses same list+query logic. |

---

## Step 10 — Recommended Implementation

1. **Blade (ProjectList.blade.php)**  
   - In each Forward and Revert form, add hidden inputs for list query params, e.g.:
     - `@foreach(request()->query() as $key => $value) <input type="hidden" name="return_query[{{ $key }}]" value="{{ $value }}"> @endforeach`
   - Or a single hidden: `return_to` with value `url()->full()` if you implement strict validation.

2. **Controller**  
   - **revertToExecutor:** Accept `return_query` (or `return_to`). On success, redirect with:
     - `redirect()->route('provincial.projects.list', $request->input('return_query', []))` (after array_filter), or  
     - validated `return_to` if used.
   - **forwardToCoordinator:** Same.
   - **updateProjectSociety:** Same (society form/modal must pass return_query or return_to).

3. **Report list**  
   - Apply the same pattern for `forwardReport` and `revertReport` so report list filters and pagination are preserved (redirect to `route('provincial.report.list', request()->query())` or equivalent).

4. **Validation failures**  
   - Keep redirecting back with errors; optional: merge `return_query` into the back URL so the “retry” form still has the same list context.

5. **Security**  
   - If using `return_to`, validate: same host, path starts with `/provincial/projects-list` (or report list path), no `javascript:`/`data:`.

This gives a clear, secure implementation plan so every project (and optionally report) action returns the user to the same filtered and paginated list page.
