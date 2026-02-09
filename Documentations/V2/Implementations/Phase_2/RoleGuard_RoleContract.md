# Phase 2.5 — RoleGuard / Role Contract (Design Only)

## 1. Purpose and Problem Statement

### Tie to Phase 0 Role-Related Production Errors

Phase 0.1 addressed the production error that blocked applicant creation:

| Error | Source | Root Cause |
|-------|--------|------------|
| **"There is no role named `applicant` for guard `web`"** | Production_Errors_Analysis_070226.md; Phase 0.1 | The `applicant` role existed in the `users.role` enum but had never been added to the Spatie `roles` table. When Provincial/General attempted to create a new user with `assignRole('applicant')`, Spatie threw because the role did not exist for the web guard. |

Phase 0.1 fixed this by adding `applicant` to the seeder and running it. That fix **unblocked** production, but it relied on the seeder having been run. No structural guarantee ensures roles exist before assignment.

### Why Relying on Seeders + Discipline Is Insufficient

| Limitation | Consequence |
|------------|-------------|
| **Seeders can be skipped** | Fresh deploys, new environments, or partial migrations may not run seeders. A role can be missing from the Spatie table even when the application expects it. |
| **New roles require manual steps** | Adding a role means: update UserRole constant, add to seeder, run seeder, update controllers. No single place enforces that all required roles exist before `assignRole` is called. |
| **Multiple sources of truth** | Roles appear in `users.role` enum, Spatie `roles` table, middleware strings (`role:applicant,executor`), and controller logic. Adding a role requires coordinated changes; drift is easy. |
| **No runtime safety** | If a role is missing, `assignRole` fails at runtime. There is no guard that ensures roles exist before assignment. |
| **Duplicate definitions** | Role names are scattered. A typo or mismatch (e.g. `applicant` vs `Applicant`) causes silent failure or unexpected behavior. |

RoleGuard and the Role Contract exist to **centralize** role definitions and **guarantee** that required roles exist before assignment, so "There is no role named X" becomes structurally impossible for any role in the contract.

### Class of Bugs This Prevents

| Bug Class | Prevention |
|-----------|------------|
| **Missing Spatie roles** | RoleGuard ensures roles from the contract exist in the Spatie table before `assignRole`. Sync runs on deploy or before assignment. |
| **assignRole on non-existent role** | Runtime guard verifies role existence (or creates it per policy) before assignment. No raw `assignRole` without guard. |
| **Duplicate role definitions** | Role Contract is the single source of truth. Controllers, policies, and middleware reference the contract, not string literals. |
| **Role drift across environments** | Sync mechanism runs on deploy. Staging and production stay aligned with the contract. |

---

## 2. Responsibilities and Non-Responsibilities

### Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Ensure required roles exist before assignment** | Before `assignRole` is invoked, the system verifies (or ensures) the role exists in the Spatie `roles` table. |
| **Provide a single source of truth for role names** | A central Role Contract (enumeration or constants) defines all application roles. Controllers and policies reference it instead of string literals. |
| **Guard runtime role assignment** | RoleGuard (or equivalent) runs before `assignRole` to prevent assignment to a non-existent role. |

### Non-Responsibilities

| Non-Responsibility | Reason |
|-------------------|--------|
| **Defining permissions** | Permissions (create project, edit budget, etc.) are separate from roles. RoleGuard does not define or assign permissions. |
| **Enforcing authorization** | Authorization (can user X do action Y?) remains in policies and middleware. RoleGuard does not replace or override them. |
| **Replacing policies or middleware** | Policy layer and route middleware continue to enforce access control. RoleGuard only ensures roles exist; it does not change how access is checked. |
| **Removing existing role strings** | Backward compatibility is required. Existing role strings in middleware and elsewhere remain valid during migration. No forced removal. |
| **Changing business logic** | User creation flow, role assignment logic, and business rules are unchanged. RoleGuard adds a safety layer; it does not alter when or why roles are assigned. |

---

## 3. Core Concepts

### Role Contract

A **Role Contract** is a central enumeration or constant class that lists all application roles. Conceptually, it is the single source of truth: `applicant`, `executor`, `provincial`, `general`, and any other role the application uses. Each role has a canonical name (string) that maps to the Spatie `roles` table. The contract is used wherever role names are needed: user creation, policy checks, and middleware configuration. No role name is defined elsewhere; all reference the contract.

### RoleGuard

**RoleGuard** is a component that ensures required roles exist before they are used. Conceptually, it runs when a role is about to be assigned (e.g. before `assignRole`) or at a deploy-time hook. It verifies that each role in the contract exists in the Spatie `roles` table for the correct guard. If a role is missing, the guard either creates it (idempotent sync) or fails fast, depending on design choice. RoleGuard does not perform authorization; it only guarantees role existence.

### Sync Mechanism

A **sync mechanism** keeps the Spatie `roles` table aligned with the Role Contract. It runs at deploy-time (e.g. as part of deployment script or `php artisan migrate`) or via a scheduled command, or it can be invoked before role assignment. The sync is idempotent: for each role in the contract, it ensures a corresponding row exists in the Spatie table (e.g. via `firstOrCreate`). The mechanism does not remove roles; it only adds missing ones. This prevents "role does not exist" errors in production.

---

## 4. Role Contract Definition (Conceptual)

### Central Role List

The Role Contract exposes a finite list of role names. Conceptually: `UserRole::APPLICANT`, `UserRole::EXECUTOR`, etc., each yielding the string used in the Spatie table (e.g. `'applicant'`, `'executor'`). A method such as `all()` or `spatieRoles()` returns the full list for sync purposes. New roles are added by extending the contract; no scattered string literals.

### Mapping to Spatie roles Table

Each role in the contract corresponds to a row in the Spatie `roles` table with `name` and `guard_name`. The sync mechanism ensures that for every role in the contract, a row exists. The mapping is explicit: the contract defines what must exist; sync creates missing rows.

### Backward Compatibility Strategy

- Existing role strings (e.g. `'applicant'`, `'executor'`) remain valid. The contract's string values match current usage.
- Middleware and policies that use string literals today can migrate incrementally to use the contract. During migration, both string literals and contract references work.
- No role is removed or renamed without a coordinated migration plan. Phase 2.5 does not drop or rename roles.

---

## 5. RoleGuard Behavior (Conceptual)

### When RoleGuard Runs

RoleGuard runs **before** `assignRole` is invoked. That may be:

- At user creation time, when the application assigns a role to a new user.
- At deploy time, when the sync mechanism runs to ensure all contract roles exist.
- Optionally, at application bootstrap or before any role assignment, as a safeguard.

The exact trigger is an implementation choice. The design requires that role assignment is guarded so that assignment to a non-existent role cannot occur.

### How It Verifies Role Existence

RoleGuard checks that the role being assigned exists in the Spatie `roles` table for the given guard. It does this by querying the table or by relying on the sync mechanism having run. If the sync runs before any assignment, verification may be implicit: sync guarantees existence. If verification runs per-assignment, it may query the table or use a cached list.

### Create vs Fail-Fast

Two design options:

- **Create (sync)**: If a role is missing, create it (e.g. `firstOrCreate`). Idempotent. Safe for production. Matches Phase 0.1 fix.
- **Fail-fast**: If a role is missing, throw or log and refuse assignment. Forces operational fix (run sync). Stricter but may block user creation if sync was skipped.

The design does not mandate one over the other. Implementation may choose sync-by-default (create if missing) for backward compatibility, with an option for strict mode. The key is that some mechanism ensures roles exist; raw `assignRole` without any guard is the anti-pattern.

---

## 6. Integration Points

### User Creation Flow

When a Provincial or General user creates an applicant (or another role), the flow assigns a role. RoleGuard (or the sync mechanism run at deploy) ensures the role exists before assignment. The user creation controller or service does not need to check; the guard provides the guarantee. No change to business logic; only a safety layer before `assignRole`.

### Seeder / Deploy Hook

The sync mechanism can be invoked via:

- A seeder that runs as part of `db:seed` (e.g. `RolesAndPermissionsSeeder`).
- A dedicated command (e.g. `php artisan roles:sync`) run during deployment.
- A migration or post-migration hook.

The deploy process should run the sync so that production has all contract roles before the application handles requests. This prevents "role does not exist" on first user creation after deploy.

### Policy Layer Usage

Policies use role checks (e.g. `$user->hasRole('applicant')`). The Role Contract provides constants so policies can use `UserRole::APPLICANT` instead of string literals. This reduces typos and ensures policies reference the same names as the sync. Policy logic (what each role can do) is unchanged; only the source of the role name changes. Migration is incremental; existing string checks remain valid until migrated.

---

## 7. Explicit Anti-Patterns Replaced

| Anti-Pattern | Replacement |
|--------------|-------------|
| **assignRole with string literals** | Use Role Contract constant (e.g. `assignRole(UserRole::APPLICANT)`). Single source of truth. |
| **Assuming seeders always ran** | Sync mechanism runs on deploy. RoleGuard verifies before assignment. No assumption. |
| **Duplicated role definitions** | Role Contract centralizes. No role name defined in more than one place. |
| **No check before assignRole** | RoleGuard ensures role exists before assignment. Structural guarantee. |

---

## 8. Adoption Strategy

### Incremental

Adopt Role Contract and RoleGuard module by module. User creation is the first integration point; policy layer and middleware can follow. No big-bang rewrite.

### Backward Compatible

- Existing role strings continue to work. Contract values match current names.
- Sync adds missing roles; does not remove or rename.
- Controllers and policies can migrate to contract references over time. Both old and new styles work during transition.

### No Forced Rewrite

- No requirement to replace all string literals at once.
- No removal of existing role checks in middleware.
- RoleGuard and sync are additive. Existing flows remain valid.

---

## 9. What This Does NOT Solve

| Concern | Clarification |
|---------|---------------|
| **Permission redesign** | RoleGuard does not define or change permissions. Permission logic is unchanged. |
| **Middleware rewrite** | Route middleware (`role:applicant,executor`) is not modified. String-based middleware remains valid. |
| **RBAC changes** | Spatie's role-permission model is unchanged. RoleGuard ensures roles exist; it does not alter how permissions are assigned or checked. |
| **Authorization behavior** | Policies and `canEdit`, `canView`, etc. are unchanged. RoleGuard does not enforce authorization. |
| **Business logic** | When and why roles are assigned (e.g. applicant on creation) is unchanged. |

---

## 10. Exit Criteria (Design Phase)

- [ ] Design document approved
- [ ] Role Contract and RoleGuard concepts stable
- [ ] Sync mechanism approach documented
- [ ] Integration points (user creation, deploy, policy) described
- [ ] No production code written
- [ ] Implementation deferred to a separate phase

---

Design complete — implementation deferred

**Date**: 2026-02-08
