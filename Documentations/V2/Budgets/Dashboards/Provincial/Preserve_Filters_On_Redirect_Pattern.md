# Preserve Filters on Redirect — Laravel Admin Panel Pattern

**Date:** 2026-03-05  
**Context:** Large admin panels with multiple nested filters  

---

## Overview

Pattern to keep users on the same filtered and paginated list page after actions (approve, edit, delete, etc.), so they don’t lose center, FY, status, page, and other filters.

---

## Approaches

### 1. `withQueryString()` (Laravel 8+)

Attach the current request’s query string to the redirect:

```php
return redirect()->back()->withQueryString();
```

**Example:** User is on `/provincial/projects-list?fy=2025-26&center=A&status=approved&page=3`. After approve, they return to the same URL with all params intact.

**Use when:** Simple back redirect after actions.

---

### 2. Explicit Query Params on Named Route

Redirect to a named route and pass query params:

```php
return redirect()->route('provincial.projects.list', request()->query());
```

**Use when:** Redirecting to a specific list route instead of `back()`.

---

### 3. Session-Based Filter State

1. **Store:** On list load/filter change, put filters in session.
2. **Load:** Merge `session('filters')` with `request()->query()`.
3. **Redirect:** After actions, redirect to list route; list reads filters from session or URL.

**Use when:** Complex nested filters, tabs, or long-lived filter state.

---

### 4. Hidden Form Input

```html
<form method="POST" action="{{ route('approve', $project) }}">
    @csrf
    <input type="hidden" name="redirect_params" value="{{ json_encode(request()->query()) }}">
    <button>Approve</button>
</form>
```

Controller:

```php
$params = json_decode($request->input('redirect_params', '{}'), true);
return redirect()->route('provincial.projects.list', $params);
```

**Use when:** Per-action control over which params are preserved.

---

## Recommendation

**Primary:** Use `redirect()->back()->withQueryString()` for post-action redirects.

**Advanced:** Use session-based filter state or explicit params when you need more control or persistence across different flows.

---

## Laravel Reference

- `Illuminate\Http\RedirectResponse::withQueryString()` — Laravel 8+
- `request()->query()` — Current query string as associative array
