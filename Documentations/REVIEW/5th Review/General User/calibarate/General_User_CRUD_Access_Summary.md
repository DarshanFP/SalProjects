# General User CRUD Access Summary

## 📋 Overview

This document summarizes the complete CRUD (Create, Read, Update, Delete) access available to General users for managing Provinces, Provincials, Societies, and Centers.

**Date:** January 2025  
**Status:** ✅ Complete

---

## ✅ Complete CRUD Access Matrix

### 1. Provinces ✅ FULL CRUD

| Operation | Route | Method | Controller Method | Status |
|-----------|-------|--------|-------------------|--------|
| **Create** | `/general/provinces/create` | GET | `createProvince()` | ✅ |
| **Store** | `/general/provinces` | POST | `storeProvince()` | ✅ |
| **Read/List** | `/general/provinces` | GET | `listProvinces()` | ✅ |
| **Edit** | `/general/provinces/{provinceName}/edit` | GET | `editProvince()` | ✅ |
| **Update** | `/general/provinces/{provinceName}/update` | POST | `updateProvince()` | ✅ |
| **Delete** | `/general/provinces/{provinceName}/delete` | POST | `deleteProvince()` | ✅ |

**Features:**
- Create provinces with optional centers
- View all provinces with provincial users and center counts
- Edit province name and centers
- Assign/reassign provincial coordinators
- Delete provinces (with validation - cannot delete if has users)

---

### 2. Provincials (Provincial Users) ✅ FULL CRUD (via Coordinator Routes)

| Operation | Route | Method | Controller Method | Status |
|-----------|-------|--------|-------------------|--------|
| **Create** | `/coordinator/create-provincial` | GET | `CoordinatorController::createProvincial()` | ✅ |
| **Store** | `/coordinator/create-provincial` | POST | `CoordinatorController::storeProvincial()` | ✅ |
| **Read/List** | `/coordinator/provincials` | GET | `CoordinatorController::listProvincials()` | ✅ |
| **Edit** | `/coordinator/provincial/{id}/edit` | GET | `CoordinatorController::editProvincial()` | ✅ |
| **Update** | `/coordinator/provincial/{id}/update` | POST | `CoordinatorController::updateProvincial()` | ✅ |
| **Delete** | N/A (via status: inactive) | - | - | ✅ |
| **Activate** | `/coordinator/user/{id}/activate` | POST | `CoordinatorController::activateUser()` | ✅ |
| **Deactivate** | `/coordinator/user/{id}/deactivate` | POST | `CoordinatorController::deactivateUser()` | ✅ |
| **Reset Password** | `/coordinator/user/{id}/reset-password` | POST | `CoordinatorController::resetUserPassword()` | ✅ |

**Note:** General users have **COMPLETE coordinator access** (see route middleware: `role:coordinator,general`), so they can access all coordinator routes including provincial user management.

**Features:**
- Create provincial users (and coordinators, executors, applicants)
- View all provincials with filtering
- Edit provincial user details
- Activate/deactivate users
- Reset passwords
- Assign to provinces

---

### 3. Societies ✅ FULL CRUD

| Operation | Route | Method | Controller Method | Status |
|-----------|-------|--------|-------------------|--------|
| **Create** | `/general/societies/create` | GET | `createSociety()` | ✅ |
| **Store** | `/general/societies` | POST | `storeSociety()` | ✅ |
| **Read/List** | `/general/societies` | GET | `listSocieties()` | ✅ |
| **Edit** | `/general/societies/{id}/edit` | GET | `editSociety()` | ✅ |
| **Update** | `/general/societies/{id}` | PUT | `updateSociety()` | ✅ |
| **Delete** | `/general/societies/{id}` | DELETE | `deleteSociety()` | ✅ |

**Features:**
- Create societies linked to provinces
- View all societies with filtering by province
- Edit society name and province assignment
- Activate/deactivate societies
- Delete societies (with validation - cannot delete if has centers)

---

### 4. Centers ✅ FULL CRUD

| Operation | Route | Method | Controller Method | Status |
|-----------|-------|--------|-------------------|--------|
| **Create** | `/general/centers/create` | GET | `createCenter()` | ✅ |
| **Store** | `/general/centers` | POST | `storeCenter()` | ✅ |
| **Read/List** | `/general/centers` | GET | `listCenters()` | ✅ |
| **Edit** | `/general/centers/{id}/edit` | GET | `editCenter()` | ✅ |
| **Update** | `/general/centers/{id}` | PUT | `updateCenter()` | ✅ |
| **Delete** | `/general/centers/{id}` | DELETE | `deleteCenter()` | ✅ |

**Features:**
- Create centers linked to provinces
- View all centers with filtering by province and society
- Edit center name and province assignment
- Activate/deactivate centers
- Delete centers (with validation - cannot delete if has users)

**Additional Center Management:**
- Transfer centers between provinces: `/general/centers/{centerId}/transfer`
- Manage user centers: `/general/users/centers/manage`
- Update user centers: `/general/users/{userId}/centers/update`

---

## 📊 Summary Table

| Entity | Create | Read | Update | Delete | Additional Features |
|--------|--------|------|--------|--------|---------------------|
| **Provinces** | ✅ | ✅ | ✅ | ✅ | Assign provincial coordinators |
| **Provincials** | ✅ | ✅ | ✅ | ✅* | Activate/deactivate, Reset password |
| **Societies** | ✅ | ✅ | ✅ | ✅ | Filter by province |
| **Centers** | ✅ | ✅ | ✅ | ✅ | Transfer, Manage user centers |

*Delete for provincials is via status change (inactive), not hard delete

---

## 🔐 Access Control

### Route Middleware
- **General-only routes:** `role:general` - Provinces, Societies, Centers management
- **Coordinator + General routes:** `role:coordinator,general` - Provincial user management

### Authorization Checks
All controller methods include:
```php
if ($general->role !== 'general') {
    abort(403, 'Access denied. Only General users can...');
}
```

---

## 🎯 Navigation Access

General users can access all management features through the sidebar:

1. **Province Management**
   - View Provinces
   - Create Province

2. **Society Management** (NEW)
   - View Societies
   - Create Society

3. **Center Management** (NEW)
   - View Centers
   - Create Center

4. **Coordinator Management** (via coordinator routes)
   - View Coordinators
   - Create Coordinator

5. **Provincial Management** (via coordinator routes)
   - View Provincials
   - Create Provincial

6. **Direct Team Management**
   - View Executors/Applicants
   - Create Executor/Applicant

---

## ✅ Verification Checklist

- [x] Provinces: Full CRUD implemented
- [x] Provincials: Full CRUD via coordinator routes
- [x] Societies: Full CRUD implemented
- [x] Centers: Full CRUD implemented
- [x] All routes properly secured
- [x] All views created
- [x] Navigation links added to sidebar
- [x] Validation and error handling in place
- [x] Logging implemented for audit trail

---

## 📝 Notes

1. **Provincial Users:** General users manage provincials through coordinator routes, giving them the same access level as coordinators.

2. **Centers Relationship:** Centers belong to provinces, not directly to societies. All centers in a province are available to all societies in that province.

3. **Delete Protection:** 
   - Provinces cannot be deleted if they have users
   - Societies cannot be deleted if they have centers
   - Centers cannot be deleted if they have users

4. **Data Integrity:** All operations include proper validation, foreign key constraints, and cascade/set null behaviors to maintain data integrity.

---

**Status:** ✅ All CRUD operations are fully implemented and accessible to General users.
