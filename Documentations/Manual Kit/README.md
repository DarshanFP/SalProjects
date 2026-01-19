# SalProjects Application - User Manuals

**Version:** 1.0  
**Last Updated:** January 2025

---

## Overview

This folder contains comprehensive operational manuals for each user role in the SalProjects application. Each manual provides step-by-step guides for performing operations and using features available to that specific role.

---

## Available Manuals

### 1. [Executor User Manual](Executor_User_Manual.md)
**Target Audience:** Executor Users

**Contents:**
- Project creation and management
- Monthly reporting
- Quarterly, Half-Yearly, and Annual reports
- Budget management
- Activity tracking
- Complete workflows and troubleshooting

**Key Operations:**
- Create and submit projects
- Create and submit monthly reports
- Manage project budgets
- View approved projects
- Track activities

---

### 2. [Applicant User Manual](Applicant_User_Manual.md)
**Target Audience:** Applicant Users

**Contents:**
- Project management (for projects where you are owner or in-charge)
- Monthly reporting
- Aggregated reports
- Budget management
- Activity tracking
- Complete workflows and troubleshooting

**Key Operations:**
- Manage projects where you are owner or in-charge
- Create and submit reports for assigned projects
- Full executor-level access for assigned projects
- Track activities

**Note:** Applicant users have the same functionality as Executors for projects where they are the owner or in-charge person.

---

### 3. [Provincial User Manual](Provincial_User_Manual.md)
**Target Audience:** Provincial Users

**Contents:**
- Team management (Executors/Applicants)
- Project review and forwarding
- Report review and forwarding
- Budget oversight
- Team activity monitoring
- Complete workflows and troubleshooting

**Key Operations:**
- Manage Executor/Applicant users
- Review and forward projects to Coordinator
- Review and forward reports to Coordinator
- Monitor team performance
- View team activities

---

### 4. [Coordinator User Manual](Coordinator_User_Manual.md)
**Target Audience:** Coordinator Users

**Contents:**
- Provincial management
- Final project approval/rejection
- Final report approval
- System-wide oversight
- Budget management
- Activity monitoring
- Complete workflows and troubleshooting

**Key Operations:**
- Manage Provincial users
- Approve/reject projects (final authority)
- Approve/revert reports (final authority)
- Monitor system-wide performance
- View all activities across the system

---

### 5. [General User Manual](General_User_Manual.md)
**Target Audience:** General Users (Sr. Elizabeth Antony)

**Contents:**
- Coordinator hierarchy management
- Direct team management
- Province management
- Dual-role project/report approval
- Combined oversight
- Complete workflows and troubleshooting

**Key Operations:**
- Manage Coordinator users
- Manage Executors/Applicants directly under General
- Create and manage provinces
- Approve projects/reports (coordinator-level authority)
- Oversee both coordinator hierarchy and direct team

**Note:** General users have dual-role functionality - complete Coordinator access + Provincial access for direct team.

---

### 6. [Admin User Manual](Admin_User_Manual.md)
**Target Audience:** Admin Users

**Contents:**
- Full system access
- User management (all roles)
- System configuration
- System monitoring and oversight
- Complete workflows and troubleshooting

**Key Operations:**
- Access all system features
- Manage users across all roles
- Monitor system performance
- System configuration and maintenance

---

## Quick Reference Guide

### Which Manual Should I Use?

| Your Role | Manual to Use |
|-----------|---------------|
| Executor | [Executor User Manual](Executor_User_Manual.md) |
| Applicant | [Applicant User Manual](Applicant_User_Manual.md) |
| Provincial | [Provincial User Manual](Provincial_User_Manual.md) |
| Coordinator | [Coordinator User Manual](Coordinator_User_Manual.md) |
| General | [General User Manual](General_User_Manual.md) |
| Admin | [Admin User Manual](Admin_User_Manual.md) |

---

## Manual Structure

Each manual follows a consistent structure:

1. **Role Overview** - Understanding your role and responsibilities
2. **Getting Started** - First login and initial setup
3. **Dashboard Overview** - Understanding your dashboard
4. **Feature Sections** - Detailed guides for each feature
5. **Common Workflows** - Step-by-step workflows for common tasks
6. **Troubleshooting** - Solutions to common issues
7. **Appendices** - Reference materials and quick guides

---

## User Role Hierarchy

Understanding the system hierarchy helps clarify responsibilities:

```
Admin (Full System Access)
    │
    ├─ General (Coordinator Access + Direct Team + Province Management)
    │     │
    │     ├─ Coordinators (Final Approval Authority)
    │     │     │
    │     │     └─ Provincials (Review and Forward)
    │     │           │
    │     │           └─ Executors/Applicants (Create Projects/Reports)
    │     │
    │     └─ Executors/Applicants (Direct Team - under General)
    │
    └─ (Direct access to all levels)
```

---

## Key Concepts

### Project Workflow

1. **Executor/Applicant** creates project
2. **Executor/Applicant** submits to Provincial
3. **Provincial** reviews and forwards to Coordinator
4. **Coordinator** approves (final approval) or reverts
5. Approved projects can receive monthly reports

### Report Workflow

1. **Executor/Applicant** creates monthly report for approved project
2. **Executor/Applicant** submits to Provincial
3. **Provincial** reviews and forwards to Coordinator
4. **Coordinator** approves (final approval) or reverts
5. Approved reports are finalized

### Status Flow

- **Projects:** draft → submitted_to_provincial → forwarded_to_coordinator → approved_by_coordinator
- **Reports:** draft → underwriting → submitted_to_provincial → forwarded_to_coordinator → approved_by_coordinator
- Items can be reverted at any approval stage for corrections

---

## Getting Help

If you need help:

1. **Check Your Manual:** Review the manual for your user role
2. **Troubleshooting Section:** Check the troubleshooting section in your manual
3. **Contact Your Supervisor:**
   - Executors/Applicants → Contact your Provincial supervisor
   - Provincials → Contact your Coordinator
   - Coordinators → Contact General or Admin
   - General/Admin → Contact system administrator

---

## Updates and Version History

**Version 1.0** (January 2025)
- Initial release of all user manuals
- Comprehensive coverage of all user roles
- Step-by-step guides for all operations
- Troubleshooting sections included

---

## Additional Resources

- **System Documentation:** Check the main Documentations folder for technical documentation
- **Database Documentation:** See `Database_Tables_and_Relationships.md` for database structure
- **Implementation Documentation:** Review implementation plans in REVIEW folders for feature details

---

**Note:** These manuals are based on the system state as of January 2025. System features may be updated over time. Please refer to system notifications for any changes to workflows or features.
