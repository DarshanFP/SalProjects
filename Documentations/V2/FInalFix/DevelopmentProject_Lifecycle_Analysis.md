# Development Projects — Visual Lifecycle Analysis

**Document type:** Read-only analysis  
**Project type:** Development Projects  
**Purpose:** End-to-end data flow and impact of shifting from delete+recreate to incremental update  
**Date:** 2026-02-14  
**No code modifications.**

---

## Phase 1 — Discover Actual Flow

### 1.1 Controllers Involved in Development Projects

| Controller | Used in CREATE | Used in EDIT (GET) | Used in UPDATE (POST) | Used in SHOW | Used in EXPORT |
|------------|----------------|--------------------|-----------------------|--------------|----------------|
| ProjectController | ✓ (orchestrator) | ✓ | ✓ (orchestrator) | ✓ | — |
| GeneralInfoController | ✓ store | — (data via $project) | ✓ update | — | — |
| KeyInformationController | ✓ store | — | ✓ update | — | — |
| LogicalFrameworkController | ✓ store | — (data via $project->objectives) | ✓ update | — | — |
| SustainabilityController | ✓ store | — (data via $project->sustainabilities) | ✓ update | — | — |
| BudgetController | ✓ store | — (data via $project->budgets) | ✓ update | — | — |
| AttachmentController | ✓ store (if file) | — (data via $project->attachments) | ✓ update (if file) | — | — |
| RST/BeneficiariesAreaController | ✓ store | ✓ edit | ✓ update | ✓ show | ✓ (via Hydrator) |
| ExportController | — | — | — | — | ✓ downloadPdf / downloadDoc |
| ProjectDataHydrator | — | — | — | — | ✓ (used by ExportController) |

**Note:** For Development Projects, the **only type-specific** section is **Beneficiaries Area** (RST controller). All other sections are shared “institutional” sections (general info, key information, logical framework, sustainability, budget, attachments).

---

### 1.2 Store/Update Pattern and Related Tables (per controller)

| Controller | Method | Pattern | Related DB tables | Transaction |
|------------|--------|---------|-------------------|-------------|
| GeneralInfoController | store | **Create only** — `Project::create()` | projects | No (caller has transaction) |
| GeneralInfoController | update | **Update only** — `$project->update()` | projects | No |
| KeyInformationController | store / update | **Update only** — project columns (e.g. initial_information, problem_tree_file_path) | projects | No |
| LogicalFrameworkController | store | **Insert only** — no delete on create; creates objectives → results, risks, activities → timeframes | project_objectives, project_results, project_risks, project_activities, project_timeframes | Yes (DB::transaction) |
| LogicalFrameworkController | update | **Delete+recreate** — `ProjectObjective::where('project_id')->delete()` then insert all | Same tables (cascade deletes children) | Yes |
| SustainabilityController | store | **Create only** — `ProjectSustainability::create()` | project_sustainabilities | Yes |
| SustainabilityController | update | **Update only** — first() or new, then save | project_sustainabilities | Yes |
| BudgetController | store | **Insert only** — no delete; creates rows for phase | project_budgets | No |
| BudgetController | update | **Delete+recreate** — `ProjectBudget::where('project_id')->where('phase')->delete()` then insert | project_budgets | No (BudgetSyncService after) |
| AttachmentController | store / update | **Create/add only** — new attachment rows; no bulk delete by project_id | project_attachments | No |
| RST/BeneficiariesAreaController | store | **Delete+recreate** — `ProjectDPRSTBeneficiariesArea::where('project_id')->delete()` then insert | project_RST_DP_beneficiaries_area | Yes |
| RST/BeneficiariesAreaController | update | **Same as store** — delegates to `store()` | Same | Yes |

---

### 1.3 Foreign Key Structure (Development Projects–relevant)

| Table | Primary key | Foreign key(s) | Cascade |
|-------|-------------|----------------|---------|
| projects | id, project_id (string) | — | — |
| project_objectives | id, objective_id (string unique) | project_id → projects.project_id | onDelete cascade |
| project_results | id, result_id | objective_id → project_objectives.objective_id | onDelete cascade |
| project_risks | id, risk_id | objective_id → project_objectives.objective_id | onDelete cascade |
| project_activities | id, activity_id | objective_id → project_objectives.objective_id | onDelete cascade |
| project_timeframes | id, timeframe_id | activity_id → project_activities.activity_id | onDelete cascade |
| project_sustainabilities | id | project_id → projects.project_id | cascade |
| project_budgets | id | project_id → projects.project_id | cascade |
| project_attachments | id | project_id → projects.project_id | — |
| project_RST_DP_beneficiaries_area | id, DPRST_bnfcrs_area_id | project_id → projects.project_id | — (no FK in migration; logical project_id) |

---

### 1.4 Models Used for Development Projects

| Model | Table | Used by |
|-------|-------|---------|
| Project | projects | GeneralInfo, KeyInformation, ProjectController, Export, Hydrator |
| ProjectObjective | project_objectives | LogicalFrameworkController |
| ProjectResult | project_results | LogicalFrameworkController |
| ProjectRisk | project_risks | LogicalFrameworkController |
| ProjectActivity | project_activities | LogicalFrameworkController |
| ProjectTimeframe | project_timeframes | LogicalFrameworkController |
| ProjectSustainability | project_sustainabilities | SustainabilityController |
| ProjectBudget | project_budgets | BudgetController, PhaseBasedBudgetStrategy, BudgetSyncService |
| ProjectAttachment | project_attachments | AttachmentController |
| ProjectDPRSTBeneficiariesArea | project_RST_DP_beneficiaries_area | RST/BeneficiariesAreaController |

---

### 1.5 Relationships Loaded in ProjectController@edit (Development Projects)

```text
Project::where('project_id', $project_id)
    ->with([
        'budgets',
        'attachments',
        'objectives',           // no nested results/risks/activities here
        'sustainabilities'
    ])
    ->firstOrFail();
```

- **budgetsForEdit:** `$project->budgets->where('phase', current_phase)->values()`
- **Type-specific:** `$beneficiariesArea = $this->rstBeneficiariesAreaController->edit($project->project_id)` (returns collection from `ProjectDPRSTBeneficiariesArea::where('project_id')->get()`).

---

### 1.6 Relationships Loaded in ProjectController@show (Development Projects)

```text
Project::where('project_id', $project_id)
    ->with([
        'budgets',
        'attachments',
        'objectives.results',
        'objectives.risks',
        'objectives.activities.timeframes',
        'sustainabilities',
        'user',
        'statusHistory.changedBy',
        'reports.accountDetails',
        // IIES relations loaded for all but unused for DP
        'iiesPersonalInfo', ...
    ])
    ->firstOrFail();
```

- **Type-specific:** `$data['RSTBeneficiariesArea'] = $this->rstBeneficiariesAreaController->show($project->project_id)` (same table, collection).
- **Resolved funds:** `$data['resolvedFundFields'] = app(ProjectFinancialResolver::class)->resolve($project)` (read-only aggregation from `project->budgets` and project columns).

---

## Phase 2 — Map Data Flow (Visual)

### 2.1 CREATE Flow

```text
[ Browser: projects/create form ]
        |
        | POST (StoreProjectRequest)
        v
[ Route: POST executor/projects/store -> projects.store ]
        |
        v
[ ProjectController@store ]
        |
        | DB::beginTransaction()
        v
[ GeneralInfoController@store ]
        | Project::create()  -->  projects
        v
[ KeyInformationController@store ]  (if !individual)
        | $project->update(...)  -->  projects
        v
[ LogicalFrameworkController@store ]
        | DB::transaction
        | Insert objectives -> results, risks, activities -> timeframes
        v   (project_objectives, project_results, project_risks, project_activities, project_timeframes)
[ SustainabilityController@store ]
        | ProjectSustainability::create()  -->  project_sustainabilities
        v
[ BudgetController@store ]
        | ProjectBudget::create() per row  -->  project_budgets
        v
[ AttachmentController@store ]  (if has file)
        | New attachment  -->  project_attachments
        v
[ Type handler: Development Projects ]
        v
[ RSTBeneficiariesAreaController@store ]
        | DB::beginTransaction
        | ProjectDPRSTBeneficiariesArea::where('project_id')->delete()
        | ProjectDPRSTBeneficiariesArea::create() per row
        v   -->  project_RST_DP_beneficiaries_area
        | DB::commit
        v
[ DB::commit ]
        v
[ Redirect: projects.show ]
```

---

### 2.2 UPDATE Flow

```text
[ Browser: projects/{id}/edit form ]
        |
        | PUT (UpdateProjectRequest)
        v
[ Route: PUT executor/projects/{project_id}/update -> projects.update ]
        |
        v
[ ProjectController@update ]
        |
        | DB::beginTransaction()
        v
[ GeneralInfoController@update ]
        | $project->update($validated)  -->  projects
        v
[ KeyInformationController@update ]  (if !individual)
        | $project->update(...)  -->  projects
        v
[ LogicalFrameworkController@update ]  (isInstitutional)
        | DB::transaction
        | ProjectObjective::where('project_id')->delete()   [ CASCADE: results, risks, activities, timeframes ]
        | Insert all objectives + results + risks + activities + timeframes
        v
[ SustainabilityController@update ]
        | first() or new; $sustainability->save()  -->  project_sustainabilities
        v
[ BudgetController@update ]
        | ProjectBudget::where('project_id')->where('phase')->delete()
        | ProjectBudget::create() per row  -->  project_budgets
        | app(BudgetSyncService::class)->syncFromTypeSave($project)  -->  projects (overall_project_budget, etc.)
        v
[ AttachmentController@update ]  (if has file)
        | New attachment row  -->  project_attachments
        v
[ Type: Development Projects ]
        v
[ RSTBeneficiariesAreaController@update ]
        | delegates to store()
        | ProjectDPRSTBeneficiariesArea::where('project_id')->delete()
        | ProjectDPRSTBeneficiariesArea::create() per row  -->  project_RST_DP_beneficiaries_area
        v
[ DB::commit ]
        v
[ Redirect: projects.index ]
```

---

### 2.3 SHOW Flow

```text
[ Browser: GET projects/{project_id} ]
        |
        v
[ Route: GET executor/projects/{project_id} -> projects.show ]
        |
        v
[ ProjectController@show ]
        |
        v
[ Project::with([ budgets, attachments, objectives.results, objectives.risks,
                  objectives.activities.timeframes, sustainabilities, user,
                  statusHistory.changedBy, reports.accountDetails, ... ]) ]
        |
        v
[ Permission check ]
        |
        v
[ Type switch: Development Projects ]
        | $data['RSTBeneficiariesArea'] = rstBeneficiariesAreaController->show(project_id)
        |   --> ProjectDPRSTBeneficiariesArea::where('project_id')->get()
        v
[ ProjectFinancialResolver->resolve($project) ]
        | Read-only: project->budgets (phase), project columns
        v
[ $data['resolvedFundFields'] = ... ]
        v
[ return view('projects.Oldprojects.show', $data) ]
```

---

### 2.4 EXPORT Flow (PDF / Doc)

```text
[ Browser: GET projects/{project_id}/download-pdf (or download-doc) ]
        |
        v
[ Route: ExportController@downloadPdf / downloadDoc ]
        |
        v
[ Project::with([ attachments, objectives.risks, objectives.activities.timeframes,
                  sustainabilities, budgets, user ])->firstOrFail() ]
        |
        v
[ ProjectDataHydrator->hydrate($project_id) ]
        | Same project load + switch(project_type)
        | Development Projects: $data['RSTBeneficiariesArea'] = rstBeneficiariesAreaController->show(...)
        v
[ view('projects.Oldprojects.pdf', $data) ]  (PDF)
   or
[ ExportController builds Word doc from $data ]
        v
[ Response: file download ]
```

---

## Phase 3 — Delete-Recreate Hotspots

Every section that **deletes all rows by project_id (or scope) then recreates** from request, and whether it depends on **index-based arrays**:

| # | Table(s) | Controller | Method | Risk | Child table dependencies | Service dependencies |
|---|----------|------------|--------|------|---------------------------|----------------------|
| 1 | project_objectives (cascade: results, risks, activities, timeframes) | LogicalFrameworkController | update | **High** | project_results, project_risks, project_activities, project_timeframes (FK to objective_id / activity_id) | None. Export/Hydrator read relations. |
| 2 | project_budgets | BudgetController | update | **High** | None | **BudgetSyncService** runs after save (syncFromTypeSave). **ProjectFinancialResolver** reads budgets by phase. |
| 3 | project_RST_DP_beneficiaries_area | RST/BeneficiariesAreaController | store, update | **High** | None | Export/PDF use RSTBeneficiariesArea via Hydrator/show. |

**Index-based / parallel-array usage:**

| Hotspot | Form payload | Index-based? |
|---------|--------------|--------------|
| Logical framework | `objectives[i][objective]`, `objectives[i][results][j][result]`, `objectives[i][activities][j][activity]`, etc. | Yes — no objective_id, result_id, activity_id in form. |
| Budget | `phases[0][budget][index][particular]`, etc. | Yes — keyed by array index; no row `id` in payload. |
| RST Beneficiaries area | `project_area[]`, `category_beneficiary[]`, `direct_beneficiaries[]`, `indirect_beneficiaries[]` | Yes — parallel arrays; no row id. |

---

## Phase 4 — Incremental Update Readiness Analysis

### 4.1 Per-Table Summary

| Table | Numeric PK (id)? | Child refs parent ID? | Blade sends row IDs? | JS preserves IDs when cloning? | Safe to upsert by id today? |
|-------|-------------------|------------------------|------------------------|---------------------------------|-----------------------------|
| projects | Yes (id + project_id) | N/A | N/A | N/A | N/A (single row) |
| project_objectives | Yes (id + objective_id string) | results/risks/activities use objective_id | No | No | **No** — form has no objective_id. |
| project_results | Yes (id + result_id) | — | No | No | **No** — form has no result_id. |
| project_risks | Yes | — | No | No | **No** — form has no risk_id. |
| project_activities | Yes (id + activity_id) | timeframes use activity_id | No | No | **No** — form has no activity_id. |
| project_timeframes | Yes | — | No | No | **No** — form has no timeframe id. |
| project_sustainabilities | Yes | — | N/A (1:1) | N/A | **Yes** — update-only already. |
| project_budgets | Yes (id) | — | No (index only) | No | **No** — form uses phases[0][budget][index], no id. |
| project_attachments | Yes | — | N/A (add-only) | N/A | Add-only; no replace set. |
| project_RST_DP_beneficiaries_area | Yes (id + DPRST_bnfcrs_area_id) | — | No | No | **No** — parallel arrays, no row id. |

### 4.2 Answers to Readiness Questions

1. **Does each table have numeric primary key id?**  
   **Yes** — all listed tables have numeric `id` (and some have a second unique string id e.g. objective_id, activity_id).

2. **Are child tables referencing parent IDs or only project_id?**  
   - Logical framework: **results, risks, activities** reference **objective_id** (parent). **Timeframes** reference **activity_id** (parent).  
   - Budget and beneficiaries area: only **project_id** (no parent row id).

3. **Do Blade forms send row IDs currently?**  
   **No** for multi-row sections. Budget uses index in `phases[0][budget][{{ $budgetIndex }}]`. Logical framework uses `objectives[i]`, `objectives[i][results][j]`, etc. RST beneficiaries use `project_area[]`, etc. No hidden `id` (or equivalent) per row.

4. **Does JS preserve IDs when cloning rows?**  
   **No.** Scripts (e.g. scripts-edit.blade.php) clone rows and reassign **names by index** (`objectives[${objectiveIndex}][...]`, `phases[0][budget][${rowCount}]`). No id field is set or preserved.

5. **Can we safely upsert by id today?**  
   **No** for objectives, results, risks, activities, timeframes, budget rows, and beneficiaries area — **because the frontend does not send row ids.** Backend could technically upsert by id if ids were present; the blocker is payload shape and JS behaviour.

---

## Phase 5 — Strategic Summary

### 5.1 Consolidated Lifecycle Summary (One Diagram)

```text
                    DEVELOPMENT PROJECTS LIFECYCLE
    ================================================================

    CREATE (POST store)
    -------------------
    Browser Form
         |
         v
    ProjectController@store  [Transaction]
         |
         +-> GeneralInfoController@store     --> projects (create)
         +-> KeyInformationController@store  --> projects (update)
         +-> LogicalFrameworkController@store --> objectives/results/risks/activities/timeframes (insert only)
         +-> SustainabilityController@store --> project_sustainabilities (create)
         +-> BudgetController@store          --> project_budgets (insert only)
         +-> AttachmentController@store    --> project_attachments (optional)
         +-> RSTBeneficiariesAreaController@store --> project_RST_DP_beneficiaries_area (delete+recreate)
         v
    Redirect to show

    EDIT (GET edit)
    ---------------
    ProjectController@edit
         |
         +-> Project::with(budgets, attachments, objectives, sustainabilities)
         +-> budgetsForEdit = budgets for current_phase
         +-> RSTBeneficiariesAreaController@edit --> beneficiariesArea collection
         v
    View: projects.Oldprojects.edit

    UPDATE (PUT update)
    -------------------
    Browser Form
         v
    ProjectController@update  [Transaction]
         |
         +-> GeneralInfoController@update     --> projects (update only)
         +-> KeyInformationController@update  --> projects (update only)
         +-> LogicalFrameworkController@update --> [DELETE all objectives] --> insert all  [HOTSPOT]
         +-> SustainabilityController@update  --> project_sustainabilities (update only)
         +-> BudgetController@update          --> [DELETE phase budgets] --> insert all --> BudgetSyncService  [HOTSPOT]
         +-> AttachmentController@update      --> project_attachments (add if file)
         +-> RSTBeneficiariesAreaController@update --> [DELETE all by project_id] --> insert all  [HOTSPOT]
         v
    Redirect to index

    SHOW (GET show)
    ---------------
    ProjectController@show
         |
         +-> Project::with(objectives.results|risks|activities.timeframes, budgets, sustainabilities, ...)
         +-> RSTBeneficiariesAreaController@show --> RSTBeneficiariesArea
         +-> ProjectFinancialResolver->resolve(project)
         v
    View: projects.Oldprojects.show

    EXPORT (GET downloadPdf / downloadDoc)
    -------------------------------------
    ExportController
         +-> ProjectDataHydrator->hydrate(project_id)
         |      +-> Project::with(...)
         |      +-> RSTBeneficiariesAreaController@show for Development Projects
         +-> View pdf or build Word
         v
    File download
```

---

### 5.2 Delete-Recreate Dependency Map

```text
    Development Projects — Delete-Recreate Sections

    [ LogicalFrameworkController@update ]
            |
            |  ProjectObjective::where('project_id')->delete()
            |  (cascade: results, risks, activities, timeframes)
            v
    project_objectives, project_results, project_risks,
    project_activities, project_timeframes
            |
            +-- No service dependency (Export/Hydrator only read)
            +-- Payload: index-based (objectives[i], objectives[i][results][j], ...)

    [ BudgetController@update ]
            |
            |  ProjectBudget::where('project_id')->where('phase')->delete()
            v
    project_budgets
            |
            +-- BudgetSyncService.syncFromTypeSave(project)  [depends on current set of rows]
            +-- ProjectFinancialResolver reads project->budgets
            +-- Payload: index-based (phases[0][budget][index])

    [ RSTBeneficiariesAreaController@store/update ]
            |
            |  ProjectDPRSTBeneficiariesArea::where('project_id')->delete()
            v
    project_RST_DP_beneficiaries_area
            |
            +-- Export/PDF: ProjectDataHydrator uses rstBeneficiariesAreaController->show
            +-- Payload: parallel arrays (project_area[], category_beneficiary[], ...)
```

---

### 5.3 Estimated Migration Complexity

| Section | Complexity | Reason |
|---------|------------|--------|
| General info / Key information / Sustainability | **Easy** | Already update-only or single-row; no delete-recreate. |
| Attachments | **Easy** | Add-only; no need to change to incremental replace. |
| **Budget** | **High** | Delete+recreate; index-based payload; BudgetSyncService and Resolver depend on current set. Requires Blade id + backend upsert + replace-set or _delete. |
| **Logical framework** | **High** | Nested delete+recreate; no ids in payload; 4 levels (objectives → results, risks, activities → timeframes). JS and Blade must send objective_id, result_id, activity_id, timeframe id. |
| **RST Beneficiaries area** | **Medium** | Single table delete+recreate; parallel arrays. Add row id to payload and backend upsert + replace-set or _delete. |

**Overall for Development Projects:** **Medium–High** (two high-complexity sections: budget, logical framework; one medium: beneficiaries area).

---

### 5.4 Recommended Conversion Order (if moving to incremental update)

1. **RST Beneficiaries area** — Single table, one type-specific section. Add `id` (or equivalent) to each row in Blade/JS; backend: upsert by id, delete rows not in request (replace-set) or support _delete list.
2. **Budget** — Add `phases[0][budget][*][id]` in Blade/JS; backend: for current phase, upsert by id, delete `project_budgets` for that project+phase where id not in sent list; then run BudgetSyncService (unchanged).
3. **Logical framework** — Add objective_id, result_id, activity_id (and timeframe id if needed) at each level in Blade/JS; backend: update objectives by id, create if missing; same for results/risks/activities/timeframes; delete children not in request (or explicit _delete). Highest effort.

---

### 5.5 What MUST Change in Frontend Before Backend Refactor

| Section | Required frontend change |
|---------|---------------------------|
| **Budget** | (1) Add hidden input (or equivalent) per budget row: `phases[0][budget][*][id]` with existing row id; new rows send empty or no id. (2) When JS adds/clones rows, do not add `id` for new rows; when reordering, keep id with row. (3) Optional: “Delete row” pushes id to `budget_delete_ids[]` and removes row from DOM. |
| **Logical framework** | (1) Add hidden (or equivalent) per objective: `objectives[*][id]` (objective_id). (2) Per result: `objectives[*][results][*][id]`. (3) Per risk: `objectives[*][risks][*][id]`. (4) Per activity: `objectives[*][activities][*][id]`. (5) Per timeframe: if needed for incremental, add id. (6) When cloning objective/result/risk/activity in JS, set name by index but preserve id for existing rows; new rows get no id. |
| **RST Beneficiaries area** | (1) Add per-row identifier: e.g. hidden `beneficiaries_area_id[]` or single structure with id per row. (2) When adding row in JS, new row has no id. (3) Optional: explicit _delete list when user removes a row. |

**Without these frontend changes, backend cannot reliably perform “update by id” or “delete only missing”; it would have to keep delete+recreate or risk duplicates/orphans.**

---

## Risk Assessment

| Risk | Level | Mitigation |
|------|--------|-------------|
| Data loss on partial rollout | High | Deploy backend only when frontend sends ids; or keep backend “no id → delete-recreate” fallback until all clients send ids. |
| Duplicate rows | High | If backend is changed to “create when no id” but frontend never sends id, every save creates new rows. Mitigation: require id in payload for existing rows. |
| Orphan rows | Medium | If backend stops deleting “not in request” and frontend omits _delete, removed rows stay in DB. Mitigation: replace-set (delete where id not in sent list) or explicit _delete. |
| BudgetSyncService / Resolver wrong totals | Low | Both aggregate current relations; as long as the set of budget rows after save is correct, behaviour is unchanged. |
| Export/PDF wrong data | Low | They read current relations; incremental update that preserves correct set of rows does not break export. |

---

## Strategic Recommendation

1. **Do not refactor backend to incremental update** until the corresponding **frontend sends row ids** (and optionally _delete list) for that section.
2. For **Development Projects**, implement **incremental update in this order:** (1) RST Beneficiaries area, (2) Budget, (3) Logical framework.
3. Keep **BudgetSyncService** and **ProjectFinancialResolver** as-is; they are compatible with incremental data.
4. Use **replace-set** (delete rows for project+scope where id not in request) or **explicit _delete** to avoid orphans; document the choice per section.
5. Consider a **feature flag or payload detection** (e.g. “if any row has id, use incremental path; else use delete+recreate”) to allow gradual rollout and rollback.

---

**End of document. No code was modified.**
