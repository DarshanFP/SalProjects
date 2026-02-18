# General Info Partial Include — Full Scan Report

**Scope:** All occurrences of `@include('projects.partials.Show.general_info')` (and variants: `show`, slashes). Also references to `general_info.blade.php`.  
**No files modified. Findings only.**

---

## 1. Every file where this partial is included

The partial **`projects.partials.Show.general_info`** (dot notation, capital S) is included in **exactly two view files**:

| # | File path | Line |
|---|-----------|------|
| 1 | `resources/views/projects/Oldprojects/show.blade.php` | 124 |
| 2 | `resources/views/projects/Oldprojects/pdf.blade.php` | 219 |

**Variant search results:**
- `@include('projects.partials.show.general_info')` (lowercase **show**) — **no matches** in codebase.
- `@include('projects.partials/Show/general_info')` or `projects.partials/show/general_info` (slash path) — **no matches** in codebase.

So only the **dot notation with capital Show** is used, in the two files above.

---

## 2. Full include line and surrounding context (10 lines)

### Include 1 — Project show view (HTML show page)

**File:** `resources/views/projects/Oldprojects/show.blade.php`  
**Full include line (124):**
```blade
            @include('projects.partials.Show.general_info')
```

**Surrounding 10 lines of context:**

```
   114|            </div>
   115|        </div>
   116|    @endif
   117|
   118|    <!-- General Information Section -->
   119|    <div id="general-info-section" class="mb-3 card">
   120|        <div class="card-header">
   121|            <h4>General Information</h4>
   122|        </div>
   123|        <div class="card-body">
   124|            @include('projects.partials.Show.general_info')
   125|        </div>
   126|    </div>
   127|
   128|    <!-- Key Information Section (excluded for Individual project types) -->
   129|    @if (!in_array($project->project_type, \App\Constants\ProjectType::getIndividualTypes()))
```

---

### Include 2 — PDF view

**File:** `resources/views/projects/Oldprojects/pdf.blade.php`  
**Full include line (219):**
```blade
        @include('projects.partials.Show.general_info')
```

**Surrounding 10 lines of context:**

```
   209|
   210|    <div class="page-header">
   211|        Project ID: {{ $project->project_id }}
   212|    </div>
   213|    <div class="card-header">
   214|        <h4>{{ $project->project_title }}</h4>
   215|    </div>
   216|
   217|    <div class="container">
   218|        <!-- General Information Section -->
   219|        <h1 class="mb-4">Project Details</h1>
   220|        @include('projects.partials.Show.general_info')
   221|
   222|        <!-- Key Information Section (excluded for Individual project types) -->
   223|        @if (!in_array($project->project_type, \App\Constants\ProjectType::getIndividualTypes()))
```

---

## 3. Whether each include is inside a conditional block

| File | Include line | Inside conditional? | Details |
|------|--------------|---------------------|--------|
| **show.blade.php** | 124 | **No** | The General Information Section (lines 118–126) is **unconditional**. It is not inside any `@if`, `@foreach`, tab switch, or layout slot. It runs for every project type and every request that renders this view. |
| **pdf.blade.php** | 219 | **No** | The include is inside `<div class="container">` but **not** inside any `@if` or `@foreach`. It runs whenever the PDF view is rendered. |

So **neither** include is inside a conditional; both run whenever their parent view is rendered.

---

## 4. Why the partial might be rendered twice / which include gets empty `$resolvedFundFields`

### 4.1 Single include per view

- In **show.blade.php** there is **one** `@include('projects.partials.Show.general_info')` (line 124).
- In **pdf.blade.php** there is **one** such include (line 219).

So the partial is **not** included twice inside the same view file. If it runs twice in one “project show” experience, possible causes are:

- Two **different** responses (e.g. one HTML show + one PDF, or two tabs), or  
- The **same** view (`projects.Oldprojects.show`) being returned by **different controllers** in different requests (see below).

### 4.2 Which controller passes data to each view

| View | Rendered by | Data passed | Has `resolvedFundFields`? |
|------|-------------|-------------|----------------------------|
| **projects.Oldprojects.show** | **ProjectController@show** | `$data` (array with many keys, including `resolvedFundFields`) | **Yes** — `$data['resolvedFundFields'] = $resolver->resolve($project);` then `return view('projects.Oldprojects.show', $data);` |
| **projects.Oldprojects.show** | **IIESPersonalInfoController** (method that returns show) | `compact('project')` only | **No** — only `$project` is passed. `$resolvedFundFields` is **not** in scope for the view or the included partial. |
| **projects.Oldprojects.show** | **SustainabilityController** (show method) | `compact('project', 'user')` only | **No** — only `$project` and `$user`. `$resolvedFundFields` is **not** in scope. |
| **projects.Oldprojects.pdf** | **ExportController** (e.g. downloadPdf) | `$data` from `ProjectDataHydrator::hydrate($project_id)` | **Yes** — hydrator sets `$data['resolvedFundFields'] = $this->financialResolver->resolve($project);` (ProjectDataHydrator.php line 284). |

So:

- **Include in show.blade.php** receives **empty/missing `$resolvedFundFields`** when the show **view** is rendered by **IIESPersonalInfoController** or **SustainabilityController** (no `resolvedFundFields` passed).
- **Include in show.blade.php** receives **populated `$resolvedFundFields`** when the show view is rendered by **ProjectController@show**.
- **Include in pdf.blade.php** receives **populated `$resolvedFundFields`** when the PDF view is rendered by **ExportController** using the hydrator.

### 4.3 Is one include “outside” the controller show context?

- **ProjectController@show** is the main “project show” entry point that builds full `$data` (including `resolvedFundFields`) and returns `projects.Oldprojects.show`. That is the **intended** show context for the browser.
- **IIESPersonalInfoController** and **SustainabilityController** also return `view('projects.Oldprojects.show', …)` but with **only** `project` (and in one case `user`). So they render the **same** show view and the **same** include at show.blade.php line 124, but **outside** the ProjectController show context: no `resolvedFundFields` is added, so the partial will see it as missing and default to `[]` → 0.00 for all financial fields.

So: the **same** include (show.blade.php line 124) is sometimes in the “full” show context (ProjectController) and sometimes in a “minimal” context (IIESPersonalInfoController or SustainabilityController). The include that can receive **empty** `$resolvedFundFields` is the one in **show.blade.php**, when the request is handled by one of those two controllers instead of ProjectController@show.

---

## 5. Other references to “general_info” (different partials)

These are **not** the same partial; they are listed only to avoid confusion:

| File | Include / reference | Partial actually used |
|------|--------------------|------------------------|
| resources/views/projects/Oldprojects/createProjects.blade.php | `@include('projects.partials.general_info')` | **projects.partials.general_info** (create form), not Show. |
| resources/views/projects/Oldprojects/edit.blade.php | `@include('projects.partials.Edit.general_info')` | **projects.partials.Edit.general_info** (edit form), not Show. |

The file **general_info.blade.php** appears in multiple paths:

- `resources/views/projects/partials/Show/general_info.blade.php` — the one included by show and pdf (this scan).
- `resources/views/projects/partials/Edit/general_info.blade.php` — edit form.
- `resources/views/projects/partials/general_info.blade.php` — create form.
- `resources/views/projects/partials/OLdshow/general_info.blade.php` — not included by show or pdf (legacy).
- `resources/views/projects/partials/not working show/general_info.blade.php` — not included by show or pdf (legacy).

---

## 6. Summary

| Question | Answer |
|----------|--------|
| Every file where **Show** general_info partial is included? | **Two:** `show.blade.php` (line 124), `pdf.blade.php` (line 219). |
| Full include line | Both: `@include('projects.partials.Show.general_info')`. |
| Inside conditional? | **No** for either; both run whenever their parent view is rendered. |
| Why might the partial be “rendered twice”? | Not twice in one view; either two separate requests (e.g. show + PDF) or the **show** view rendered by two different controllers (ProjectController vs IIES/Sustainability). |
| Which include can receive empty `$resolvedFundFields`? | The include in **show.blade.php** (line 124), when the show **view** is returned by **IIESPersonalInfoController** or **SustainabilityController** (they do not pass `resolvedFundFields`). |
| Is one include outside the controller show context? | Yes: when the **same** show view (and thus the same include at show line 124) is rendered by IIESPersonalInfoController or SustainabilityController, that is outside the normal ProjectController@show context and does not receive `resolvedFundFields`. |

No code was modified; this is a report only.
