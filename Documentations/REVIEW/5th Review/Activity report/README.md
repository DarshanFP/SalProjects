# Activity Report - Documentation Index

**Date:** January 2025  
**Status:** 📋 **PLANNING**  
**Purpose:** Comprehensive activity/status history tracking system for projects and reports

---

## Overview

This folder contains all documentation for implementing a comprehensive **Activity Report** system that tracks status changes for both Projects and Reports with role-based access control.

---

## 📁 Files in This Directory

### 1. **Activity_Report_Requirements_And_Implementation_Plan.md**
   - **Purpose:** Main comprehensive requirements and phase-wise implementation plan
   - **Contents:**
     - Current state analysis
     - Complete requirements summary
     - User hierarchy & access requirements
     - Database design (unified vs separate tables)
     - 7-phase implementation plan (30 hours total)
     - Technical specifications
     - Testing strategy
   - **Status:** ✅ Complete

### 2. **Current_Statuses_Reference.md**
   - **Purpose:** Quick reference for all project and report statuses
   - **Contents:**
     - All 7 project statuses with descriptions
     - All 7 report statuses with descriptions
     - Status flow diagrams
     - Status badge colors
     - Quick reference code snippets
   - **Status:** ✅ Complete

### 3. **README.md** (This File)
   - **Purpose:** Index and overview of all documentation
   - **Status:** ✅ Complete

---

## 🎯 Key Requirements Summary

### Functional Requirements

1. **Unified Status History System**
   - Track status changes for both Projects and Reports
   - Single unified table (`activity_histories`) with `type` column
   - Migrate existing project status history data

2. **Role-Based Access Control**
   - **Executor/Applicant:** See own activities (projects/reports they own or are in-charge of)
   - **Provincial:** See all activities of their executors/applicants
   - **Coordinator:** See all activities in the system

3. **Activity Report Views**
   - My Activities (Executor/Applicant)
   - Team Activities (Provincial)
   - All Activities (Coordinator)
   - Project Activity History (All roles)
   - Report Activity History (All roles)

4. **Sidebar Links**
   - Executor/Applicant: "My Activities"
   - Provincial: "Team Activities"
   - Coordinator: "All Activities"

---

## 📊 Current Statuses

### Projects & Reports (7 Statuses Each)

1. `draft` - Draft (Executor still working)
2. `submitted_to_provincial` - Executor submitted to Provincial
3. `reverted_by_provincial` - Returned by Provincial for changes
4. `forwarded_to_coordinator` - Provincial sent to Coordinator
5. `reverted_by_coordinator` - Coordinator sent back for changes
6. `approved_by_coordinator` - Approved by Coordinator
7. `rejected_by_coordinator` - Rejected by Coordinator

**Analysis:** ✅ Current statuses are sufficient - no additional statuses needed

---

## 👥 User Hierarchy

```
Coordinator (Top Level)
  ├── Provincial 1
  │   ├── Executor 1
  │   ├── Executor 2
  │   ├── Applicant 1
  │   └── Applicant 2
  ├── Provincial 2
  │   ├── Executor 3
  │   └── Applicant 3
  └── ...
```

**Database:** `users.parent_id` links executor/applicant to provincial

---

## 🗄️ Database Design

### Recommended: Unified Table

**Table:** `activity_histories`

**Schema:**
- `type` ENUM('project', 'report')
- `related_id` VARCHAR(255) - project_id or report_id
- `previous_status` VARCHAR(255)
- `new_status` VARCHAR(255)
- `changed_by_user_id` BIGINT
- `changed_by_user_role` VARCHAR(50)
- `changed_by_user_name` VARCHAR(255)
- `notes` TEXT
- `created_at`, `updated_at`

**Advantages:**
- ✅ Single table for both projects and reports
- ✅ Easier to query unified activity feed
- ✅ Simpler codebase
- ✅ Better for reporting and analytics

---

## 📋 Implementation Phases

| Phase | Description | Duration | Priority |
|-------|-------------|----------|----------|
| **Phase 1** | Database & Model Setup | 4 hours | 🔴 High |
| **Phase 2** | Report Status Integration | 6 hours | 🔴 High |
| **Phase 3** | Service & Helpers | 4 hours | 🔴 High |
| **Phase 4** | Controller & Routes | 4 hours | 🔴 High |
| **Phase 5** | Views & UI | 6 hours | 🔴 High |
| **Phase 6** | Integration & Testing | 4 hours | 🟡 Medium |
| **Phase 7** | Documentation & Cleanup | 2 hours | 🟢 Low |
| **Total** | | **30 hours** | |

---

## ✅ Current State

### What Exists
- ✅ Project status history tracking (`project_status_histories` table)
- ✅ `ProjectStatusService::logStatusChange()` method
- ✅ Status history UI component (embedded in project show page)
- ✅ User hierarchy (`users.parent_id`)

### What's Missing
- ❌ Report status history tracking
- ❌ Unified activity history table
- ❌ Role-based activity views
- ❌ Sidebar links for activity reports
- ❌ Dedicated routes for activity history

---

## 🚀 Next Steps

1. **Review Requirements**
   - Read `Activity_Report_Requirements_And_Implementation_Plan.md`
   - Review `Current_Statuses_Reference.md`
   - Approve database design (unified table)

2. **Begin Implementation**
   - Start with Phase 1: Database & Model Setup
   - Proceed sequentially through all phases
   - Test thoroughly at each phase

3. **Testing**
   - Unit tests for services
   - Integration tests for access control
   - Manual testing for all user roles

---

## 📚 Related Documentation

- **Applicant Access Implementation:** `../Applicant user Access/`
- **Budget Standardization:** `../Budget_Standardization_*.md`
- **Report Views Enhancement:** `../Report Views/`
- **Consolidated Plan:** `../CONSOLIDATED_PHASE_WISE_IMPLEMENTATION_PLAN.md`

---

## 📝 Notes

### Status History Tracking
- **Projects:** ✅ Currently tracked in `project_status_histories`
- **Reports:** ❌ Not currently tracked (to be implemented)

### Access Control
- Executor/Applicant can edit projects/reports they own or are in-charge of
- Provincial can see all activities of their executors/applicants
- Coordinator can see all activities in the system

### Status Analysis
- Current 7 statuses are sufficient
- No additional statuses required
- Status flow is clear and logical

---

## ✨ Success Criteria

### Functional
- ✅ All project status changes logged
- ✅ All report status changes logged
- ✅ Executor/applicant can see own activities
- ✅ Provincial can see team activities
- ✅ Coordinator can see all activities
- ✅ Project history view works
- ✅ Report history view works

### Technical
- ✅ Database schema optimized
- ✅ Queries efficient (no N+1 problems)
- ✅ Code follows Laravel best practices
- ✅ All tests passing

### User Experience
- ✅ Easy to access activity reports
- ✅ Clear display of status changes
- ✅ Filters and search work
- ✅ Responsive design
- ✅ Fast page loads

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Ready for Review and Implementation

---

**End of README**
