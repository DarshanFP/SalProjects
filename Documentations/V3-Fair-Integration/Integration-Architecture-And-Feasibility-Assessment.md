# Integration Architecture & Feasibility Assessment

**SalProjects (This Application) ↔ Second Internal Portal**

**Document Version:** 1.0  
**Date:** February 10, 2025  
**Scope:** Architecture analysis only — no implementation.

---

## Executive Summary

This document provides a full feasibility and architecture assessment for integrating the SalProjects application with another internal portal. The second portal must be able to: fetch projects, filter by province, view full project details, write reports linked to projects, and potentially view attachments and (in a future phase) update status or add comments.

**Feasibility: YES.** Integration is feasible with a dedicated API layer, token-based authentication, and strict province-based scoping in this application.

---

## Table of Contents

1. [Feasibility Confirmation](#1-feasibility-confirmation)
2. [Phase 1 — Current System Audit](#2-phase-1--current-system-audit)
3. [Phase 2 — Data Exposure Planning](#3-phase-2--data-exposure-planning)
4. [Phase 3 — Multi-Tenancy / Province Isolation](#4-phase-3--multi-tenancy--province-isolation)
5. [Phase 4 — API Architecture Proposal](#5-phase-4--api-architecture-proposal)
6. [Phase 5 — Report Writing Architecture](#6-phase-5--report-writing-architecture)
7. [Phase 6 — Required Changes in THIS App](#7-phase-6--required-changes-in-this-app)
8. [Phase 7 — Required Changes in SECOND App](#8-phase-7--required-changes-in-second-app)
9. [Phase 8 — Risk & Security Analysis](#9-phase-8--risk--security-analysis)
10. [Deliverables Summary](#10-deliverables-summary)
11. [Implementation Roadmap & Effort Estimation](#11-implementation-roadmap--effort-estimation)

---

## 1. Feasibility Confirmation

| Criterion | Status | Notes |
|----------|--------|--------|
| **Data exists** | ✅ | Projects, province linkage (via User), reports, attachments, and status history all exist in this app. |
| **Province linkage** | ⚠️ | No `province_id` on `projects` table; province is derived via **Project → User → Province**. API design must enforce province at the **user/token** level and filter projects via that. |
| **API readiness** | ⚠️ | Current API is minimal (provinces/centers only, no auth on those routes). Projects, reports, and attachments are not exposed. A dedicated API layer with token auth and province scoping is required. |

**Conclusion:** The integration is **feasible** provided this app adds a versioned, province-scoped API and the second portal consumes it with a token bound to one or more provinces.

---

## 2. Phase 1 — Current System Audit

### 2.1 How Projects Are Stored (Models + Relationships)

| Item | Finding |
|------|--------|
| **Main model** | `App\Models\OldProjects\Project` (table: `projects`) |
| **Primary key** | `id` (bigint); business key: `project_id` (string, e.g. `DP-0001`) |
| **Core relation** | `user_id` → `User` (project "owner" / applicant/executor) |
| **Province** | **Not on Project.** Province is derived from `Project → user → province_id` or `user.province` (string). |
| **Key relationships** | `user`, `budgets`, `attachments`, `objectives`, `comments`, `reports` (DPReport), `statusHistory`, `activityHistory`, plus many type-specific relations (CCI, IES, IAH, ILP, RST, IGE, LDP, EduRUT, etc.) |

**Province relationship structure:**

- **Province model:** `provinces` table; has `centers`, `societies`, `users` (via `province_id`), and `provincialUsers` (pivot `provincial_user_province` for general users).
- **User model:** Has `province_id` (FK to `provinces`) and legacy `province` (string). General users can have many provinces via `managedProvinces()` (pivot). Provincial users: single province via `province_id`.
- **Project model:** No `province_id`. Province is inferred as project’s `user->province_id` or `user->province`.

**Are projects scoped by province?**

- **Yes, but only in UI/controllers**, not via a global scope. Logic is repeated in:
  - **ProvincialController:** `getAccessibleUserIds($user)` → projects where `user_id IN (...)` (users in that province or managed provinces).
  - **GeneralController / CoordinatorController:** Filter by `whereHas('user', fn($q) => $q->where('province', $request->province))` (string) or equivalent.
- **Not centralized:** `ProjectQueryService` has no province logic; province filtering is duplicated across controllers.

**Do user roles restrict province visibility?**

- **Executor/Applicant:** Own projects only.
- **Provincial:** Projects of users in their province (and direct children).
- **General:** Projects of users in their managed provinces (pivot + optional session filter).
- **Coordinator:** All projects (with optional province/center filters).
- **Admin:** Full access.
- **No formal Policy layer:** `AuthServiceProvider` has no policies; access is enforced ad-hoc in controllers (e.g. `getAccessibleUserIds` + `in_array($project->user_id, $accessibleUserIds)`).

### 2.2 Authentication Method and API Readiness

| Topic | Finding |
|-------|--------|
| **Auth method** | **Session-based (web guard only).** Default guard in `config/auth.php` is `web`; no `sanctum` or `api` guard defined. |
| **Sanctum** | Installed and configured (`config/sanctum.php`). User model uses `HasApiTokens`. Only route using it: `GET /api/user` with `auth:sanctum`. |
| **API routes** | `routes/api.php`: `GET /api/user` (auth:sanctum), `GET /api/provinces`, `GET /api/provinces/{id}/centers`, `GET /api/centers`, `GET /api/centers/by-province/{provinceId}`. **No project, report, or attachment routes.** |
| **API-ready?** | **Partially.** API stack exists (Sanctum, throttle, CORS on `api/*`) but app is **Blade-first**; no project/report API and no token-issuance flow for the second portal. |

### 2.3 Controllers, Resource Layer, DTOs, External Integrations

| Topic | Finding |
|-------|--------|
| **API-safe controllers** | Existing project/report logic lives in **web controllers** (ProjectController, ProvincialController, GeneralController, ReportController, etc.). They return views/redirects and use session auth. They are **not** API-safe as-is (no JSON resource contracts, no token-based province scoping). |
| **Resource layer** | **None.** No `ApiResource` or `JsonResource` classes for API responses. |
| **DTOs / Transformers** | **None.** |
| **External integrations** | **None.** No outbound API clients or webhooks; only internal Blade usage and the existing province/center API. |

---

## 3. Phase 2 — Data Exposure Planning

### 3.1 Minimum Payload (List / Dropdown)

Expose per project (and optionally from `user` for display):

- `id`, `project_id`, `project_type`, `project_title`, `status`, `created_at`, `updated_at`
- **Province:** from `user.province_id` + `user.provinceRelation->name` (prefer ID for filtering). Do **not** rely only on string `user.province` long-term (legacy).

### 3.2 Detailed View (Full Project)

- **From Project:** All fillable/needed fields (e.g. society, contacts, commencement, budget fields, goal, etc.).
- **Type-specific relations:** Only the subset for `project_type` (e.g. CCI, IES, IAH, ILP, RST, EduRUT, etc.) to avoid huge payloads and N+1.
- **Attachments:** `project_attachments` (id, project_id, file_name, description, public_url or signed URL); type-specific attachment tables if needed (IES, IIES, ILP, IAH, etc.).
- **Status history:** `project_status_history` (previous_status, new_status, changed_by_user_id/name/role, notes, created_at).
- **Budget/financials:** `budgets` (and/or resolved financials from `ProjectFinancialResolver` / `DerivedCalculationService` if you expose computed totals).
- **Reports:** List of report identifiers (e.g. `report_id`, period) for the project; full report payload only from reports endpoint.

### 3.3 Eager Loading and N+1

- **List:** `with(['user:id,name,province_id', 'user.provinceRelation:id,name'])` (and minimal user fields). Do not load all type-specific relations for list.
- **Detail:** Eager-load only the relations needed for the requested `project_type` and for attachments/statusHistory/budgets/reports as needed.
- **N+1 risks:** Type-specific relations (many hasMany/hasOne per type); nested report/account details; activity history. Mitigation: explicit `with()` per endpoint and optional “include” query params.

### 3.4 Sensitive Fields

- **User:** password, remember_token (already hidden). Consider not exposing email/phone to second portal unless required; at least restrict by role.
- **Project:** Coordinator India/Luzern contact details may be sensitive; decide per environment.
- **Reports:** Financial and beneficiary details; treat as sensitive and restrict by province/token.

---

## 4. Phase 3 — Multi-Tenancy / Province Isolation

### 4.1 Audit

| Question | Answer |
|----------|--------|
| **Is province_id stored in projects table?** | **No.** Province is derived via `project->user->province_id` (or `user.province`). |
| **Is there province_id on users?** | **Yes.** Plus pivot `provincial_user_province` for general users managing multiple provinces. |
| **Is there a policy layer enforcing province access?** | **No.** No policies; controller logic only. |
| **Is province filtering centralized?** | **No.** Repeated in multiple controllers; General/Coordinator sometimes use string `province`, Provincial uses `province_id` and `getAccessibleUserIds`. |

### 4.2 Recommended Architecture for API (Second Portal)

- **One token per “portal client” (or per province).** Each token is bound to a fixed set of province IDs (e.g. one province per token for simplicity).
- **No user login from the second portal into this app.** Second portal uses a **machine/service token** (e.g. Sanctum token for a dedicated “API user” or a custom token table keyed by province).
- **Every project API request:** Resolve allowed province IDs from the token (or from the API user’s `province_id` / managed provinces). Restrict projects with: `whereHas('user', fn($q) => $q->whereIn('province_id', $allowedProvinceIds))`. Optionally also allow filtering by `user.province` during a transition period, but standardize on `province_id`.
- **Centralize:** Implement a **ProvinceScope** or a single **API ProjectQueryService** method used by all project API controllers so province filtering cannot be bypassed.
- **Optional denormalization:** Add `province_id` to `projects` (set from `user.province_id` on create/update) for simpler indexing and API queries. Not strictly required if you always join through `users`.

---

## 5. Phase 4 — API Architecture Proposal

### 5.1 Recommended Option: REST API + Sanctum Token Auth (Option A)

- **Internal system, controlled access:** Sanctum tokens (per client or per province) are simple to issue, rotate, and revoke.
- **Province isolation:** Token (or linked API user) carries province context; middleware or base controller enforces it on every project/report request.
- **Scalability:** Stateless; no session store; suitable for a second portal and future internal clients.
- **OAuth2 (Passport)** is unnecessary unless you need delegated authorization (multiple external apps, user consent). For one internal portal, Sanctum is sufficient.
- **Option C (signed token + IP)** can be added as an extra (e.g. IP allowlist for the portal’s servers), not as the primary auth.

### 5.2 Proposed Route Design (Versioned)

Base: **`api/v1/`**

| Method | Route | Purpose |
|--------|--------|---------|
| GET | `api/v1/projects` | List projects (query: `province_id`, `project_type`, `status`, pagination). Province enforced by token. |
| GET | `api/v1/projects/{project}` | Full project detail (type-specific relations optional via `?include=...`). |
| GET | `api/v1/projects/{project}/attachments` | List attachments (project + type-specific if needed). |
| GET | `api/v1/projects/{project}/attachments/{id}/download` | Signed or proxied download URL. |
| GET | `api/v1/projects/{project}/status-history` | Status history. |
| GET | `api/v1/projects/{project}/reports` | List reports linked to project (future: include “portal-written” reports if stored here). |
| POST | `api/v1/projects/{project}/reports` | Create report linked to project (Phase 2 / report-writing). |

Use route model binding with a **scoped** Project resolver that already applies province (so that `projects/99` only resolves if that project belongs to the token’s province(s)).

### 5.3 Versioning

- Prefix: **`api/v1/`**. Keep v1 stable; introduce `api/v2/` when breaking changes are needed.

### 5.4 Token Issuance

- Either: dedicated “API user” per province with Sanctum tokens and province stored on user, or
- Custom “API client” table (client_id, name, province_ids, token hash) and middleware that resolves province from token.  
Both allow the “second portal” to call with `Authorization: Bearer <token>` and receive province-scoped data only.

---

## 6. Phase 5 — Report Writing Architecture

The second portal needs to **write reports** for projects.

| Option | Description | Safety / Integrity | Duplication | Scalability |
|--------|-------------|-------------------|-------------|-------------|
| **A) Store in THIS app** | Second portal calls `POST api/v1/projects/{id}/reports`; report stored in this app’s DB (e.g. extend or add table), linked to `project_id`. | **Highest:** single source of truth; this app owns projects and reports. | None. | Good; all reporting in one place. |
| **B) Store in second app** | Second portal stores reports in its DB with `project_id` (and maybe project snapshot). | Risk of orphan or stale references if project_id/project data change here. | Reference only; report body lives in second app. | Two systems to backup and query. |
| **C) Hybrid sync** | Second app writes locally and syncs to this app (or vice versa). | Complexity; sync failures and conflicts. | Possible duplication. | More moving parts. |

**Recommendation: A) Store reports in THIS app.**

- Single source of truth; project and report stay in one DB; province and project existence validated here.
- Second portal only needs to POST report payload and get back IDs/confirmation.
- Define a clear “portal report” type or table if current reports (e.g. DPReport) are only for internal workflow, so portal reports don’t mix with existing flows without rules.

---

## 7. Phase 6 — Required Changes in THIS App

| # | Change | Notes |
|---|--------|------|
| 1 | **Create API routes** | Add `api/v1/projects`, `projects/{id}`, `projects/{id}/attachments`, `projects/{id}/status-history`, `projects/{id}/reports` (and later POST for reports). |
| 2 | **Create API Resource classes** | E.g. `ProjectResource`, `ProjectDetailResource`, `AttachmentResource`, `StatusHistoryResource`, `ReportSummaryResource` to control fields and nesting. |
| 3 | **Implement province-scope middleware or base logic** | Resolve allowed province IDs from token (or API user); apply to all project/report queries; use in route model binding so other provinces’ projects return 404. |
| 4 | **Access tokens** | Use Sanctum (personal access tokens for API users) or custom API client table with hashed tokens; document token issuance/rotation. |
| 5 | **Report table (if new type)** | If “portal reports” are a new type, add migration and model; link to `project_id` (and optionally `province_id`). If reusing DPReport, define rules for portal-created rows (e.g. flag or type). |
| 6 | **Rate limiting** | Already: `ThrottleRequests::class.':api'` (60/min by user or IP). Consider stricter limit per token for list/detail or add a dedicated `api:portal` limiter. |
| 7 | **Refactor to service layer (optional but recommended)** | Extract project listing/detail logic used by API into a service (e.g. `ProjectApiService`) that accepts province IDs and returns query/collection; keeps controllers thin and reusable. |
| 8 | **Auth config** | Add `sanctum` guard and/or ensure API routes use `auth:sanctum` where needed; keep web guard for Blade. |
| 9 | **CORS** | If second portal is a different origin, restrict `allowed_origins` (and optionally `supports_credentials`) in `config/cors.php` instead of `*`. |

---

## 8. Phase 7 — Required Changes in SECOND App

| # | Change | Notes |
|---|--------|------|
| 1 | **HTTP client** | Use Laravel HTTP client or Guzzle to call this app’s `api/v1/*` with `Authorization: Bearer <token>`. |
| 2 | **Token storage** | Store token securely (env/config or secret store); never in frontend; rotate on schedule or on compromise. |
| 3 | **Scheduled sync (optional)** | Only if second portal needs a local cache of projects/reports; otherwise call API on demand. |
| 4 | **Caching** | Cache project list/detail per province with short TTL to reduce load; invalidate or TTL so updates are visible. |
| 5 | **Webhook listener** | Optional: if this app pushes changes (e.g. project status), second app exposes a webhook; otherwise polling or on-demand is sufficient. |
| 6 | **Role system aligned to provinces** | If the portal has its own users, map them to provinces and only request data for allowed provinces (and use tokens scoped to those provinces). |
| 7 | **Fallback when API unavailable** | Retry with backoff; show cached data or “unavailable” message; log failures and alert. |

---

## 9. Phase 8 — Risk & Security Analysis

| Risk | Mitigation |
|------|------------|
| **Data leakage across provinces** | Enforce province at token/resolver level; never return projects for provinces not attached to the token; 404 for wrong project ID; audit logs for API access. |
| **Token misuse** | Short-lived tokens or rotation; IP allowlist for server-to-server; revoke on compromise; minimal scope (e.g. read-only vs write for reports). |
| **Overfetching / N+1** | Explicit `with()` and `include`; pagination on list; avoid loading all type-specific relations for list; consider field selection (e.g. `?fields=`) later. |
| **Breaking Blade flow** | Keep all web routes and controllers unchanged; add new API routes and new (or dedicated) API controllers/services; no shared logic that assumes JSON. |
| **Performance** | Index `users.province_id`; if you add `projects.province_id`, index it; rate limiting; caching in second app; DB query review for list/detail. |
| **Sensitive fields** | Resource classes and DTOs to exclude or mask user/project/report fields; no raw model serialization. |

---

## 10. Deliverables Summary

### 10.1 Feasibility

**Yes.** Integration is feasible with a dedicated API layer, token-based auth, and province scoping in this app.

### 10.2 Required Changes in THIS App

- Add `api/v1` project, attachments, status-history, and reports routes.
- Introduce API Resources (and optionally a small service layer) for projects, attachments, status history, reports.
- Enforce province via token (or API user) in one place (middleware/resolver) and use it in all project/report API endpoints.
- Use Sanctum (or custom tokens) for second-portal access; document issuance/rotation.
- Optionally add a dedicated report table/type for “portal reports” and/or rules for using existing report tables.
- Tighten CORS and rate limiting as needed.

### 10.3 Required Changes in SECOND App

- HTTP client + Bearer token; secure token storage and rotation.
- Optional: caching, optional sync or webhook, province-aligned roles, fallback when API is down.

### 10.4 Recommended Integration Architecture

- **REST over `api/v1/`** with **Sanctum (or custom) token auth**, one token per client/province (or API user per province).
- **Province isolation:** token → allowed province IDs → filter all project/report queries and binding.
- **Reports written by second portal:** stored in this app via `POST api/v1/projects/{id}/reports`.

### 10.5 Database Changes (This App)

- **Optional but recommended:** `projects.province_id` (nullable FK to `provinces`), set from `user.province_id` on create/update, for simpler and faster API queries.
- **If new report type for portal:** new table or new `type`/flag on existing report table; link to `project_id` (and optionally `province_id`).

### 10.6 Security Model

- **Authentication:** Bearer token (Sanctum or custom) for server-to-server; no session for the portal.
- **Authorization:** Province IDs bound to token (or API user); every project/report request filtered by those IDs; 404 for out-of-scope resources.
- **Network:** Prefer IP allowlist and HTTPS only.
- **Data:** Resource classes limit exposed fields; no internal-only or sensitive fields in API responses.

---

## 11. Implementation Roadmap & Effort Estimation

### 11.1 Phased Roadmap

| Phase | Steps | Outcome |
|-------|--------|---------|
| **1 – Foundation** | Add Sanctum/custom token issuance; province-from-token middleware; central project query by province IDs. | Secure, province-scoped API auth. |
| **2 – Read API** | Implement `GET api/v1/projects`, `GET api/v1/projects/{id}` with Resources; then attachments and status-history. | Second portal can list and view projects and related data. |
| **3 – Reports read** | Implement `GET api/v1/projects/{id}/reports` (and report detail if needed). | Portal can list and view reports linked to projects. |
| **4 – Report write** | Define “portal report” storage (table/type); implement `POST api/v1/projects/{id}/reports`; validate project and province. | Portal can create reports linked to projects. |
| **5 – Hardening** | CORS, rate limits, logging, optional `province_id` on projects, documentation. | Production-ready integration. |

### 11.2 Effort Estimation (Per Phase)

| Phase | Effort | Notes |
|-------|--------|--------|
| Phase 1 – Foundation (auth, province scoping) | **Medium** | Token model, middleware, resolver; no new UI. |
| Phase 2 – Read API (projects, detail, attachments, status-history) | **Medium–High** | Many relations and type-specific data; Resources and careful eager loading. |
| Phase 3 – Reports read | **Low–Medium** | Depends on report structure and how much you expose. |
| Phase 4 – Report write | **Medium** | Validation, storage, and possibly new table/type. |
| Phase 5 – Hardening | **Low** | Config, docs, small migrations if any. |

**Overall:** Medium–High effort for this app (mostly new API surface and province enforcement); second portal effort depends on its stack and how much it caches and mirrors data.

---

## Appendix A: Key Model References

- **Project:** `App\Models\OldProjects\Project` — table `projects`; key: `project_id` (string), `user_id` (FK).
- **User:** `App\Models\User` — `province_id`, `province` (string), `managedProvinces()` (pivot).
- **Province:** `App\Models\Province` — `provinces` table.
- **Reports:** `App\Models\Reports\Monthly\DPReport` — linked via `project_id`.
- **Attachments:** `App\Models\OldProjects\ProjectAttachment` — table `project_attachments`.
- **Status history:** `App\Models\ProjectStatusHistory` — `project_id` (string).

## Appendix B: Current API Routes (Pre-Integration)

- `GET /api/user` (auth:sanctum)
- `GET /api/provinces`
- `GET /api/provinces/{id}/centers`
- `GET /api/centers`
- `GET /api/centers/by-province/{provinceId}`

No project, report, or attachment routes exist today.

---

*End of Assessment Report.*
