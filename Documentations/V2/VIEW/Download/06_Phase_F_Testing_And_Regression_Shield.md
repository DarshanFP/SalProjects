# Phase F — Testing & Regression Shield

**Rule: Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.**

---

**Phase:** F  
**Objective:** Create a test blueprint to guarantee Provincial and Coordinator can download project attachments, with full regression coverage for Executor.  
**Scope:** Project attachments only. Reports OUT OF SCOPE.

---

## 1. Objective

Define and execute tests that verify:

- Provincial can download attachments for owner projects
- Provincial can download attachments for in-charge projects (in scope)
- Provincial is blocked for projects outside province
- Provincial is blocked when owner and in_charge are out of scope
- Coordinator can download attachments for all project types
- Coordinator cannot delete attachments (if restricted by canEdit)
- Executor and Applicant flows are unchanged

---

## 2. Scope — Exact Files Involved

| File | Purpose |
|------|---------|
| `tests/Feature/ProjectAttachment/ProvincialAttachmentDownloadTest.php` | (New) Provincial download tests |
| `tests/Feature/ProjectAttachment/CoordinatorAttachmentDownloadTest.php` | (New) Coordinator download tests |
| `tests/Feature/ProjectAttachment/ExecutorAttachmentDownloadTest.php` | (Optional, if exists) Executor regression |
| `tests/Unit/Services/ProjectAccessServiceTest.php` | (Optional) canViewProject unit tests |
| `tests/Unit/Helpers/ProjectPermissionHelperTest.php` | (Optional) passesProvinceCheck unit tests |

---

## 3. What Will NOT Be Touched

- Report attachment tests
- Report controller tests
- Production code (this phase is test authoring only per plan)

---

## 4. Pre-Implementation Checklist

- [ ] Phases A–E complete
- [ ] Test database/seeding strategy defined
- [ ] User factories or seeders for executor, provincial, coordinator
- [ ] Project factories with owner, in_charge
- [ ] Attachment fixtures for each project type (DP, IES, IIES, IAH, ILP)

---

## 5. Step-by-Step Implementation Plan

### Step F1: Data Setup Strategy

**Users needed:**
- 1 executor (province A)
- 1 executor as in_charge (province A)
- 1 provincial (province A, parent of both executors)
- 1 coordinator (province_id null or any)
- 1 executor (province B) — for cross-province block test

**Projects:**
- Project 1: owner = executor1, in_charge = null, province A
- Project 2: owner = executor1, in_charge = executor2, province A
- Project 3: owner = executor2, in_charge = null, province A (in_charge-only for provincial)
- Project 4: owner = executorB, in_charge = null, province B (out of provincial scope)

**Attachments:**
- DP: ProjectAttachment for project 1, 2, 3, 4
- IES: ProjectIESAttachmentFile for IES projects (if project types differ, map accordingly)
- IIES, IAH, ILP: Similar file records

### Step F2: Provincial Test Cases

| # | Scenario | Expected |
|---|----------|----------|
| 1 | Provincial downloads DP attachment (owner project) | 200, file stream |
| 2 | Provincial downloads DP attachment (in_charge project) | 200, file stream |
| 3 | Provincial downloads IES attachment (owner project) | 200 |
| 4 | Provincial downloads IES attachment (in_charge project) | 200 |
| 5 | Provincial downloads IIES, IAH, ILP (owner or in_charge) | 200 |
| 6 | Provincial tries download for project in other province | 403 |
| 7 | Provincial tries download for project where owner and in_charge out of scope | 403 |
| 8 | Provincial views attachment (view route) for allowed project | 200 |
| 9 | Provincial views attachment for disallowed project | 403 |

### Step F3: Coordinator Test Cases

| # | Scenario | Expected |
|---|----------|----------|
| 1 | Coordinator downloads DP attachment (any project) | 200 |
| 2 | Coordinator downloads IES attachment (any project) | 200 |
| 3 | Coordinator downloads IIES, IAH, ILP attachments | 200 |
| 4 | Coordinator views attachment (any project) | 200 |
| 5 | Coordinator tries delete (destroy) — if route allows but controller uses canEdit | 403 (coordinator typically cannot edit) |

### Step F4: Executor Regression Test Cases

| # | Scenario | Expected |
|---|----------|----------|
| 1 | Executor downloads own project DP attachment | 200 |
| 2 | Executor downloads in_charge project DP attachment | 200 |
| 3 | Executor downloads IES, IIES, IAH, ILP (own/in_charge) | 200 |
| 4 | Executor tries download for other's project | 403 |
| 5 | Applicant: same as executor | 200 for own/in_charge, 403 for other |

### Step F5: Test Implementation Outline

**ProvincialAttachmentDownloadTest:**

```php
// Example structure (Laravel Feature test)
public function test_provincial_can_download_dp_attachment_for_owner_project(): void
{
    $provincial = User::factory()->provincial()->create(['province_id' => 1]);
    $executor = User::factory()->executor()->create(['province_id' => 1, 'parent_id' => $provincial->id]);
    $project = Project::factory()->create(['user_id' => $executor->id, 'province_id' => 1]);
    $attachment = ProjectAttachment::factory()->create(['project_id' => $project->project_id]);

    $response = $this->actingAs($provincial)->get(route('projects.attachments.download', $attachment->id));
    $response->assertOk();
}

public function test_provincial_can_download_dp_attachment_for_in_charge_project(): void
{
    // Project where in_charge is in provincial scope, owner may be different
    // ...
}

public function test_provincial_blocked_for_project_outside_province(): void
{
    // ...
    $response->assertForbidden();
}

public function test_provincial_blocked_when_owner_and_in_charge_out_of_scope(): void
{
    // ...
    $response->assertForbidden();
}
```

**CoordinatorAttachmentDownloadTest:**

```php
public function test_coordinator_can_download_dp_attachment_for_any_project(): void
{
    $coordinator = User::factory()->coordinator()->create();
    $project = Project::factory()->create(['province_id' => 1]);
    $attachment = ProjectAttachment::factory()->create(['project_id' => $project->project_id]);

    $response = $this->actingAs($coordinator)->get(route('projects.attachments.download', $attachment->id));
    $response->assertOk();
}
```

### Step F6: IES, IIES, IAH, ILP Tests

Repeat similar structure for each project type:
- `projects.ies.attachments.download`
- `projects.iies.attachments.download`
- `projects.iah.documents.download`
- `projects.ilp.documents.download`

Use appropriate model and factory for file/attachment records.

### Step F7: Unit Tests (Optional)

**ProjectAccessService::canViewProject:**
- Coordinator + any project → true
- Provincial + owner in scope → true
- Provincial + in_charge in scope → true
- Provincial + both out of scope → false
- Provincial + wrong province → false (handled by passesProvinceCheck)

**ProjectPermissionHelper::passesProvinceCheck:**
- Coordinator + any project → true
- Provincial + same province → true
- Provincial + different province → false

---

## 6. Security Impact Analysis

- Tests do not change production security
- Tests validate that access control behaves as designed
- Negative tests (403) ensure unauthorized access is blocked

---

## 7. Performance Impact Analysis

- Feature tests add CI time
- Use RefreshDatabase or DatabaseTransactions to keep tests fast
- Minimal impact if tests are well-scoped

---

## 8. Rollback Strategy

- Remove or skip new test files if they block CI
- Do not rollback production code for test failures; fix tests or fix implementation

---

## 9. Deployment Checklist

- [ ] All new tests pass locally
- [ ] CI runs full test suite
- [ ] No flaky tests
- [ ] Test data does not conflict with other test suites

---

## 10. Regression Checklist

- [ ] Executor: all project types download
- [ ] Applicant: same as executor
- [ ] Provincial: owner + in_charge in scope
- [ ] Provincial: blocked out of scope / wrong province
- [ ] Coordinator: all project types
- [ ] Delete (destroy): coordinator/provincial get 403 when canEdit is false

---

## 11. Sign-Off Criteria

- [ ] ProvincialAttachmentDownloadTest covers owner, in_charge, blocked scenarios
- [ ] CoordinatorAttachmentDownloadTest covers all project types
- [ ] Executor regression tests pass
- [ ] All tests pass in CI
- [ ] Phase_F_Implementation_Summary.md created and updated
