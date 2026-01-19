# Phase 3 Task 3.2: Extract Common Logic to Services - Progress

**Date:** January 2025  
**Status:** 🔄 **IN PROGRESS** (60% Complete)  
**Task:** Task 3.2 - Extract Common Logic to Services

---

## Completed Work

### ✅ Created ProjectQueryService

**File:** `app/Services/ProjectQueryService.php`

**Methods Created:**
1. ✅ `getProjectsForUserQuery(User $user): Builder` - Get query builder for user's projects
2. ✅ `getProjectIdsForUser(User $user): Collection` - Get project IDs for user
3. ✅ `getProjectsForUser(User $user, array $with = []): Collection` - Get projects with relationships
4. ✅ `getProjectsForUsersQuery($userIds): Builder` - Get query for multiple users
5. ✅ `getProjectIdsForUsers($userIds): Collection` - Get project IDs for multiple users
6. ✅ `getProjectsForUserByStatus(User $user, $statuses, array $with = []): Collection` - Get projects by status
7. ✅ `getApprovedProjectsForUser(User $user, array $with = []): Collection` - Get approved projects
8. ✅ `getEditableProjectsForUser(User $user, array $with = []): Collection` - Get editable projects
9. ✅ `getRevertedProjectsForUser(User $user, array $with = []): Collection` - Get reverted projects
10. ✅ `applySearchFilter(Builder $query, string $searchTerm): Builder` - Apply search filter

**Benefits:**
- Centralized project query logic
- Consistent patterns across controllers
- Easier to maintain and test
- Reusable methods

### ✅ Updated Controllers

**ExecutorController.php:**
- ✅ Replaced 8+ instances of duplicate project query patterns
- ✅ Uses `ProjectQueryService` for all project queries
- ✅ Uses service methods for approved, editable, reverted projects

**ProjectController.php:**
- ✅ Replaced 2 instances
- ✅ Uses `ProjectQueryService` for project queries

**GeneralController.php:**
- ✅ Replaced 3 instances (for multiple users pattern)
- ✅ Uses `ProjectQueryService::getProjectsForUsersQuery()`

---

## Remaining Work

### ⏳ Continue Controller Updates

**Files Still to Update:**
- ⏳ `ProvincialController.php` - Check for duplicate patterns
- ⏳ `CoordinatorController.php` - Check for duplicate patterns
- ⏳ Other controllers with project queries

### ⏳ Identify Other Common Patterns

**Potential Patterns to Extract:**
1. ⏳ Report query patterns (similar to project queries)
2. ⏳ Budget calculation patterns
3. ⏳ Status filtering patterns
4. ⏳ Permission check patterns (already in ProjectPermissionHelper)

---

## Statistics

### Files Created: 1
- `app/Services/ProjectQueryService.php`

### Files Updated: 3
- `app/Http/Controllers/ExecutorController.php` (8+ replacements)
- `app/Http/Controllers/Projects/ProjectController.php` (2 replacements)
- `app/Http/Controllers/GeneralController.php` (3 replacements)

### Patterns Extracted: 13+
- Project queries for single user: 8+
- Project queries for multiple users: 3
- Search filter: 1
- Status-specific queries: 3

---

## Next Steps

1. ⏳ Continue updating remaining controllers
2. ⏳ Check for report query patterns
3. ⏳ Verify all replacements work correctly
4. ⏳ Document service usage

---

**Status:** 🔄 **60% COMPLETE**  
**Last Updated:** January 2025
