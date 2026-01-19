# Province Management & Provincial Coordinator Assignment - Implementation Plan

**Date:** January 2025  
**Status:** 📋 **PLANNING**  
**Priority:** 🔴 **HIGH**  
**Requirement:** General users can be provincial coordinators for other provinces and should be able to create provinces and assign provincial coordinators from user list (including themselves)

---

## 📋 Executive Summary

This document outlines the implementation plan for adding **Province Management** and **Provincial Coordinator Assignment** functionality for General users. This allows General users to:

1. **Create and manage provinces** (add new provinces dynamically)
2. **Assign provincial coordinators to provinces** from a user list that includes:
   - All existing users (coordinator, provincial, executor, applicant, general)
   - **The General user themselves** (so they can be provincial for other provinces)

---

## 🎯 Requirements

### 1. Province Management
- General users can **create new provinces**
- General users can **view list of all provinces**
- General users can **edit province details** (name, centers)
- General users can **delete provinces** (with validation - cannot delete if province has users)

### 2. Provincial Coordinator Assignment
- General users can **assign provincial coordinators to provinces**
- Provincial coordinator can be **any user** from the system (including General user themselves)
- General users can **view which user is assigned as provincial coordinator for each province**
- General users can **change/reassign provincial coordinators**
- Provincial coordinator assignment should **update the user's province field** to match the assigned province

### 3. User List for Provincial Coordinator Selection
- Show **all users** in the system (coordinator, provincial, executor, applicant, general)
- **Include the General user themselves** in the list
- Display user name, email, current role, current province (if any)
- Allow filtering/searching users

---

## 🗄️ Database Structure

### Current Structure
- **`users` table** has `province` field (enum/string)
- **`users` table** has `role` field (enum: admin, coordinator, provincial, executor, general)
- **`users` table** has `parent_id` field (for hierarchy)

### Proposed Changes

#### Option 1: Use Existing Structure (Recommended)
- **No new tables needed**
- Use `users.province` field to store province assignment
- Use `users.role` field to identify provincial coordinators
- **Note:** A user can have `role='general'` but still be assigned to a `province` (acting as provincial coordinator)

#### Option 2: Create Province Management Table (If needed for province metadata)
- **`provinces` table** (optional - for province metadata like centers list)
  - `id` (primary key)
  - `name` (string, unique)
  - `centers` (json/text - list of centers)
  - `provincial_coordinator_id` (foreign key to users.id, nullable)
  - `created_by` (foreign key to users.id - General user who created it)
  - `created_at`, `updated_at`

**Recommendation:** Start with Option 1 (use existing structure). If province metadata (centers list) needs to be managed separately, we can add Option 2 later.

---

## 📁 Files to Create

### 1. Controller Methods (GeneralController.php)

**New Methods:**
1. `createProvince()` - Show create province form
2. `storeProvince()` - Store new province
3. `listProvinces()` - List all provinces with their provincial coordinators
4. `editProvince()` - Show edit province form
5. `updateProvince()` - Update province details
6. `deleteProvince()` - Delete province (with validation)
7. `assignProvincialCoordinator()` - Show assign provincial coordinator form
8. `storeProvincialCoordinator()` - Assign provincial coordinator to province
9. `updateProvincialCoordinator()` - Change/reassign provincial coordinator
10. `removeProvincialCoordinator()` - Remove provincial coordinator from province

### 2. Views

**New Views:**
1. `resources/views/general/provinces/index.blade.php` - List all provinces
2. `resources/views/general/provinces/create.blade.php` - Create province form
3. `resources/views/general/provinces/edit.blade.php` - Edit province form
4. `resources/views/general/provinces/assign-coordinator.blade.php` - Assign provincial coordinator form (with user list)

**Modified Views:**
1. `resources/views/general/sidebar.blade.php` - Add "Provinces" section

### 3. Routes

**New Routes:**
```php
// Province Management
Route::get('/general/provinces', [GeneralController::class, 'listProvinces'])->name('general.provinces');
Route::get('/general/provinces/create', [GeneralController::class, 'createProvince'])->name('general.createProvince');
Route::post('/general/provinces', [GeneralController::class, 'storeProvince'])->name('general.storeProvince');
Route::get('/general/provinces/{id}/edit', [GeneralController::class, 'editProvince'])->name('general.editProvince');
Route::post('/general/provinces/{id}/update', [GeneralController::class, 'updateProvince'])->name('general.updateProvince');
Route::post('/general/provinces/{id}/delete', [GeneralController::class, 'deleteProvince'])->name('general.deleteProvince');

// Provincial Coordinator Assignment
Route::get('/general/provinces/{province}/assign-coordinator', [GeneralController::class, 'assignProvincialCoordinator'])->name('general.assignProvincialCoordinator');
Route::post('/general/provinces/{province}/assign-coordinator', [GeneralController::class, 'storeProvincialCoordinator'])->name('general.storeProvincialCoordinator');
Route::post('/general/provinces/{province}/update-coordinator', [GeneralController::class, 'updateProvincialCoordinator'])->name('general.updateProvincialCoordinator');
Route::post('/general/provinces/{province}/remove-coordinator', [GeneralController::class, 'removeProvincialCoordinator'])->name('general.removeProvincialCoordinator');
```

---

## 🔧 Implementation Details

### 1. Province Management

#### Create Province
- **Form Fields:**
  - Province Name (text, required, unique)
  - Centers (textarea or multi-select - one per line or comma-separated)
- **Validation:**
  - Province name must be unique
  - Province name cannot be empty
  - Centers list is optional but recommended

#### List Provinces
- **Display:**
  - Province Name
  - Provincial Coordinator (user name, email, role)
  - Number of Centers
  - Number of Users in Province
  - Actions: Edit, Assign Coordinator, Delete
- **Filters:**
  - Filter by province name (search)
  - Filter by provincial coordinator

#### Edit Province
- **Editable Fields:**
  - Province Name (can change)
  - Centers List (can add/remove centers)
- **Validation:**
  - Province name must be unique (except current province)
  - Cannot change province name if users are assigned to it (or allow with confirmation)

#### Delete Province
- **Validation:**
  - Cannot delete if province has users assigned
  - Show warning with list of users in province
  - Require confirmation

### 2. Provincial Coordinator Assignment

#### Assign Provincial Coordinator Form
- **User Selection:**
  - Dropdown or searchable list of **all users** (including General user themselves)
  - Display: User Name, Email, Current Role, Current Province (if any)
  - **Include General user in the list** (so they can select themselves)
- **Province Selection:**
  - Show province name (from route parameter)
  - Display province details (centers list, etc.)

#### Store Provincial Coordinator
- **Actions:**
  1. Update selected user's `province` field to match assigned province
  2. **If user is General user:** Keep `role='general'` but set `province` field
  3. **If user is not Provincial:** Optionally change `role` to `'provincial'` OR keep existing role but assign province
  4. **If user already has a province:** Show warning/confirmation before reassigning

#### Update/Remove Provincial Coordinator
- **Update:** Same as assign (reassign to different user)
- **Remove:** Clear user's `province` field (set to null or 'none')

### 3. User List for Selection

#### Query All Users
```php
$users = User::whereIn('role', ['admin', 'coordinator', 'provincial', 'executor', 'applicant', 'general'])
    ->select('id', 'name', 'email', 'role', 'province')
    ->orderBy('name')
    ->get();
```

#### Include General User Themselves
```php
$general = Auth::user();
$users = $users->prepend($general); // Add General user to the list
// OR
$users = User::whereIn('role', [...])
    ->orWhere('id', $general->id) // Ensure General user is included
    ->get();
```

#### Display Format
- **User Name** (bold)
- **Email** (smaller text)
- **Current Role** (badge)
- **Current Province** (if assigned, show in parentheses)
- **Example:** "John Doe (john@example.com) - Coordinator (Bangalore)"

---

## 🎨 UI/UX Design

### Sidebar Addition
Add new section in `general/sidebar.blade.php`:
```
📁 Province Management
  ├─ View Provinces
  └─ Create Province
```

### Province List View
- **Table Columns:**
  - Province Name
  - Provincial Coordinator (Name, Email, Role)
  - Centers Count
  - Users Count
  - Actions (Edit, Assign Coordinator, Delete)
- **Filters:**
  - Search by province name
  - Filter by provincial coordinator

### Assign Coordinator View
- **Form Layout:**
  - Province Information (read-only)
  - User Selection Dropdown (searchable, includes General user)
  - Current Assignment (if any)
  - Warning if user already assigned to different province
  - Submit Button

---

## 🔍 Key Implementation Considerations

### 1. General User as Provincial Coordinator
- **Important:** General user can be assigned as provincial coordinator for a province
- **Behavior:**
  - General user keeps `role='general'`
  - General user's `province` field is set to the assigned province
  - General user can now act as provincial for that province
  - General user can still manage coordinators and direct team

### 2. Province Field Updates
- When assigning provincial coordinator:
  - Update user's `province` field
  - If user already has a province, show confirmation before reassigning
- When removing provincial coordinator:
  - Set user's `province` to null or 'none'
  - Optionally keep or remove `role='provincial'` (if it was changed)

### 3. Validation Rules
- **Province Name:** Must be unique, cannot be empty
- **User Assignment:** User must exist, cannot assign same user to multiple provinces (or allow with confirmation)
- **Deletion:** Cannot delete province if it has users assigned

### 4. Centers Management
- **Option 1:** Store centers as JSON in database (if using provinces table)
- **Option 2:** Keep centers in controller `$centersMap` array (current approach)
- **Option 3:** Store centers as comma-separated string in province metadata

**Recommendation:** For now, keep centers in controller array. If needed, we can migrate to database later.

---

## 📝 Implementation Steps

### Phase 1: Province Management (Basic CRUD)
1. ✅ Add routes for province management
2. ✅ Add controller methods (create, store, list, edit, update, delete)
3. ✅ Create views (index, create, edit)
4. ✅ Update sidebar with Province Management section
5. ✅ Test province CRUD operations

### Phase 2: Provincial Coordinator Assignment
1. ✅ Add routes for provincial coordinator assignment
2. ✅ Add controller methods (assign, store, update, remove)
3. ✅ Create view for assign coordinator (with user list including General user)
4. ✅ Implement user selection logic (include General user)
5. ✅ Update user's province field when assigned
6. ✅ Test assignment functionality

### Phase 3: Integration & Testing
1. ✅ Integrate with existing General user functionality
2. ✅ Test General user assigning themselves as provincial coordinator
3. ✅ Test province deletion validation
4. ✅ Test user reassignment
5. ✅ Update documentation

---

## 🧪 Testing Checklist

### Province Management
- [ ] General user can create new province
- [ ] General user can view list of provinces
- [ ] General user can edit province details
- [ ] General user cannot delete province with users assigned
- [ ] Province name validation (unique, required)

### Provincial Coordinator Assignment
- [ ] General user can assign provincial coordinator to province
- [ ] **General user can select themselves as provincial coordinator**
- [ ] User list includes all users (coordinator, provincial, executor, applicant, general)
- [ ] User's province field is updated when assigned
- [ ] Warning shown if user already assigned to different province
- [ ] General user can reassign provincial coordinator
- [ ] General user can remove provincial coordinator

### General User as Provincial Coordinator
- [ ] General user can be assigned as provincial coordinator
- [ ] General user's role remains 'general' when assigned
- [ ] General user's province field is updated
- [ ] General user can act as provincial for assigned province
- [ ] General user can still manage coordinators and direct team

---

## 📊 Database Considerations

### Current Province Field
- **Type:** Enum (in migration) but actually string (per documentation)
- **Values:** 'Bangalore', 'Vijayawada', 'Visakhapatnam', 'Generalate', 'Divyodaya', 'Indonesia', 'East Timor', 'East Africa', 'Luzern', 'none'
- **Note:** Can accept any string value (not strictly limited to enum)

### Migration (If Needed)
If we need to change province field from enum to string:
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('province')->nullable()->change();
});
```

**Recommendation:** Check current database structure first. If it's already string, no migration needed.

---

## 🚀 Next Steps

1. **Review and approve this plan**
2. **Check database structure** (verify province field type)
3. **Start Phase 1 implementation** (Province Management CRUD)
4. **Start Phase 2 implementation** (Provincial Coordinator Assignment)
5. **Test thoroughly** (especially General user assigning themselves)
6. **Update documentation**

---

## 📚 Related Documentation

- `General_User_Role_Implementation_Plan.md` - Original General user implementation
- `COMPLETE_IMPLEMENTATION_SUMMARY.md` - Current General user features
- `Guide_to_Add_New_Provinces.md` - Existing province management guide

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Ready for Implementation
