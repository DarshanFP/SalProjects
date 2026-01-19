# Current Statuses Reference

**Date:** January 2025  
**Purpose:** Quick reference for all project and report statuses

---

## Project Statuses

**Source:** `app/Constants/ProjectStatus.php`

| Status | Constant | Label | Description | Editable? | Submittable? |
|--------|----------|-------|-------------|-----------|--------------|
| `draft` | `DRAFT` | Draft (Executor still working) | Initial state, executor/applicant can edit | ✅ Yes | ✅ Yes |
| `submitted_to_provincial` | `SUBMITTED_TO_PROVINCIAL` | Executor submitted to Provincial | Submitted by executor/applicant | ❌ No | ❌ No |
| `reverted_by_provincial` | `REVERTED_BY_PROVINCIAL` | Returned by Provincial for changes | Reverted by provincial with reason | ✅ Yes | ✅ Yes |
| `forwarded_to_coordinator` | `FORWARDED_TO_COORDINATOR` | Provincial sent to Coordinator | Forwarded by provincial | ❌ No | ❌ No |
| `reverted_by_coordinator` | `REVERTED_BY_COORDINATOR` | Coordinator sent back for changes | Reverted by coordinator with reason | ✅ Yes | ✅ Yes |
| `approved_by_coordinator` | `APPROVED_BY_COORDINATOR` | Approved by Coordinator | Final approved state | ❌ No | ❌ No |
| `rejected_by_coordinator` | `REJECTED_BY_COORDINATOR` | Rejected by Coordinator | Final rejected state | ❌ No | ❌ No |

**Total:** 7 statuses

**Editable Statuses:** `draft`, `reverted_by_provincial`, `reverted_by_coordinator`  
**Submittable Statuses:** `draft`, `reverted_by_provincial`, `reverted_by_coordinator`

---

## Report Statuses

**Source:** `app/Models/Reports/Monthly/DPReport.php`

| Status | Constant | Label | Description | Editable? | Submittable? |
|--------|----------|-------|-------------|-----------|--------------|
| `draft` | `STATUS_DRAFT` | Draft (Executor still working) | Initial state, executor/applicant can edit | ✅ Yes | ✅ Yes |
| `submitted_to_provincial` | `STATUS_SUBMITTED_TO_PROVINCIAL` | Executor submitted to Provincial | Submitted by executor/applicant | ❌ No | ❌ No |
| `reverted_by_provincial` | `STATUS_REVERTED_BY_PROVINCIAL` | Returned by Provincial for changes | Reverted by provincial with reason | ✅ Yes | ✅ Yes |
| `forwarded_to_coordinator` | `STATUS_FORWARDED_TO_COORDINATOR` | Provincial sent to Coordinator | Forwarded by provincial | ❌ No | ❌ No |
| `reverted_by_coordinator` | `STATUS_REVERTED_BY_COORDINATOR` | Coordinator sent back for changes | Reverted by coordinator with reason | ✅ Yes | ✅ Yes |
| `approved_by_coordinator` | `STATUS_APPROVED_BY_COORDINATOR` | Approved by Coordinator | Final approved state | ❌ No | ❌ No |
| `rejected_by_coordinator` | `STATUS_REJECTED_BY_COORDINATOR` | Rejected by Coordinator | Final rejected state | ❌ No | ❌ No |

**Total:** 7 statuses (identical to projects)

**Editable Statuses:** `draft`, `reverted_by_provincial`, `reverted_by_coordinator`  
**Submittable Statuses:** `draft`, `reverted_by_provincial`, `reverted_by_coordinator`

**Note:** Same statuses apply to:
- Monthly Reports (`DPReport`)
- Quarterly Reports (`QuarterlyReport`)
- Half-Yearly Reports (`HalfYearlyReport`)
- Annual Reports (`AnnualReport`)

---

## Status Flow Diagram

### Projects

```
                    [draft]
                       ↓
         (Executor/Applicant submits)
                       ↓
        [submitted_to_provincial]
                       ↓
    ┌──────────────────┴──────────────────┐
    ↓                                      ↓
(Provincial forwards)          (Provincial reverts)
    ↓                                      ↓
[forwarded_to_coordinator]    [reverted_by_provincial]
    ↓                                      ↓
    └──────────────┬──────────────────────┘
                   ↓
    ┌──────────────┴──────────────┐
    ↓              ↓              ↓
(Coordinator)  (Coordinator)  (Coordinator)
approves       reverts        rejects
    ↓              ↓              ↓
[approved_by]  [reverted_by]  [rejected_by]
[coordinator]  [coordinator]  [coordinator]
```

### Reports

```
                    [draft]
                       ↓
         (Executor/Applicant submits)
                       ↓
        [submitted_to_provincial]
                       ↓
    ┌──────────────────┴──────────────────┐
    ↓                                      ↓
(Provincial forwards)          (Provincial reverts)
    ↓                                      ↓
[forwarded_to_coordinator]    [reverted_by_provincial]
    ↓                                      ↓
    └──────────────┬──────────────────────┘
                   ↓
    ┌──────────────┴──────────────┐
    ↓              ↓              ↓
(Coordinator)  (Coordinator)  (Coordinator)
approves       reverts        rejects
    ↓              ↓              ↓
[approved_by]  [reverted_by]  [rejected_by]
[coordinator]  [coordinator]  [coordinator]
```

---

## Status Change Tracking

### Currently Tracked

**Projects:**
- ✅ All status changes are logged in `project_status_histories` table
- ✅ Tracks: previous_status, new_status, changed_by_user_id, changed_by_user_role, changed_by_user_name, notes, timestamps
- ✅ Service: `ProjectStatusService::logStatusChange()`

**Reports:**
- ❌ **NOT CURRENTLY TRACKED**
- ❌ No `report_status_histories` table
- ❌ No logging of report status changes

### Status Change Methods

**Projects:**
- `ProjectStatusService::submitToProvincial()` - Logs submission
- `ProjectStatusService::forwardToCoordinator()` - Logs forwarding
- `ProjectStatusService::approve()` - Logs approval
- `ProjectStatusService::revertByProvincial()` - Logs revert with reason
- `ProjectStatusService::revertByCoordinator()` - Logs revert with reason
- `CoordinatorController::rejectProject()` - Logs rejection

**Reports:**
- `ReportController::submit()` - **NOT LOGGED** (needs implementation)
- `ReportController::forward()` - **NOT LOGGED** (needs implementation)
- `ReportController::approve()` - **NOT LOGGED** (needs implementation)
- `ReportController::revert()` - **NOT LOGGED** (needs implementation)

---

## Status Analysis

### Are Current Statuses Sufficient?

**✅ YES - Current statuses are sufficient:**

1. **Complete Workflow Coverage:**
   - All workflow states are covered
   - No gaps in the approval process
   - Clear progression from draft to final state

2. **Consistent Across Entities:**
   - Projects and reports use identical status flow
   - Same statuses for all report types (monthly, quarterly, half-yearly, annual)

3. **Revert Capability:**
   - Both provincial and coordinator can revert
   - Revert reasons are tracked (via `notes` field)

4. **Final States:**
   - Clear final states: `approved_by_coordinator` and `rejected_by_coordinator`
   - No ambiguity in workflow completion

### Recommendations

**No Additional Statuses Required:**
- Current 7 statuses cover all workflow scenarios
- Status flow is clear and logical
- No gaps identified in workflow

**However, consider:**
- ✅ **Status History Tracking for Reports** - Currently missing (to be implemented)
- ✅ **Unified Activity View** - Currently missing (to be implemented)
- ✅ **Role-Based Access** - Currently missing (to be implemented)

---

## Status Badge Colors

### Projects & Reports (Same Colors)

| Status | Badge Color | CSS Class |
|--------|-------------|-----------|
| `draft` | Gray | `bg-secondary` |
| `submitted_to_provincial` | Blue | `bg-primary` |
| `reverted_by_provincial` | Yellow | `bg-warning` |
| `forwarded_to_coordinator` | Cyan | `bg-info` |
| `reverted_by_coordinator` | Yellow | `bg-warning` |
| `approved_by_coordinator` | Green | `bg-success` |
| `rejected_by_coordinator` | Red | `bg-danger` |

---

## Quick Reference Code

### Check if Status is Editable

**Projects:**
```php
use App\Constants\ProjectStatus;

if (ProjectStatus::isEditable($project->status)) {
    // Can edit
}
```

**Reports:**
```php
use App\Models\Reports\Monthly\DPReport;

$editableStatuses = [
    DPReport::STATUS_DRAFT,
    DPReport::STATUS_REVERTED_BY_PROVINCIAL,
    DPReport::STATUS_REVERTED_BY_COORDINATOR,
];

if (in_array($report->status, $editableStatuses)) {
    // Can edit
}
```

### Get Status Label

**Projects:**
```php
$label = Project::$statusLabels[$project->status] ?? 'Unknown';
```

**Reports:**
```php
$label = DPReport::$statusLabels[$report->status] ?? 'Unknown';
```

### Get Status Badge Class

**Projects:**
```php
// Custom method needed or use switch statement
```

**Reports:**
```php
$badgeClass = $report->getStatusBadgeClass();
```

---

## Summary

- **Total Statuses:** 7 (same for projects and reports)
- **Editable Statuses:** 3 (`draft`, `reverted_by_provincial`, `reverted_by_coordinator`)
- **Final Statuses:** 2 (`approved_by_coordinator`, `rejected_by_coordinator`)
- **Status History:** ✅ Projects (tracked), ❌ Reports (not tracked)
- **Additional Statuses Needed:** None

---

**Document Version:** 1.0  
**Last Updated:** January 2025

---

**End of Current Statuses Reference**
