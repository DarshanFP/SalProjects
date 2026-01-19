# General User Manual

**SalProjects Application - Operational Manual for General Role**

**Version:** 2.0  
**Last Updated:** January 2025  
**Target Audience:** General Users (Sr. Elizabeth Antony)

---

## Table of Contents

1. [Role Overview](#1-role-overview)
2. [Getting Started](#2-getting-started)
3. [Dashboard Overview](#3-dashboard-overview)
4. [Coordinator Hierarchy Management](#4-coordinator-hierarchy-management)
5. [Provincial User Management](#5-provincial-user-management)
6. [Direct Team Management](#6-direct-team-management)
7. [Province Management](#7-province-management)
8. [Society Management](#8-society-management)
9. [Center Management](#9-center-management)
10. [Project Management](#10-project-management)
11. [Report Management](#11-report-management)
12. [Budget Management](#12-budget-management)
13. [Activity History](#13-activity-history)
14. [Profile Management](#14-profile-management)
15. [Notifications](#15-notifications)
16. [Common Workflows](#16-common-workflows)
17. [Troubleshooting](#17-troubleshooting)

---

## 1. Role Overview

### 1.1 Your Role

As a **General** user, you have a unique **dual-role** functionality:

1. **Coordinator-Level Access:** Complete coordinator access for managing Coordinators and their entire hierarchy
2. **Provincial-Level Access:** Acts as Provincial for managing Executors/Applicants directly under you

You are responsible for:
- **Managing Coordinators:** Create, edit, activate/deactivate Coordinator users
- **Managing Provincial Users:** Create, edit, activate/deactivate Provincial users
- **Managing Direct Team:** Create, edit, activate/deactivate Executors/Applicants directly under you
- **Province Management:** Create and manage provinces, assign Provincial Coordinators, use province filters
- **Society Management:** Create, edit, and manage societies within provinces
- **Center Management:** Create, edit, transfer centers, and manage user center assignments
- **Approval Authority:** Approve/revert projects and reports with coordinator-level authority
- **Report Aggregation:** Access quarterly, biannual, and annual aggregated reports
- **System Oversight:** Oversee both coordinator hierarchy and direct team

### 1.2 Access Level

- **Complete Coordinator Access:** ALL routes, permissions, and functionality available to Coordinators
- **Provincial-Level Access:** For direct Executors/Applicants under you
- **Province Management:** Create provinces, assign Provincial Coordinators (including yourself), use province filters
- **Society Management:** Full CRUD operations for societies
- **Center Management:** Full CRUD operations for centers, transfer centers, manage user centers
- **Aggregated Reports:** Access to quarterly, biannual, and annual report aggregations
- **Budget Reports:** Access to comprehensive budget reports and analysis
- **System-Wide Oversight:** Access to coordinator hierarchy + direct team data

### 1.3 Dual-Role Context

**As Coordinator Parent:**
- Manage Coordinators (who manage Provincials)
- View all projects/reports from Coordinators and their entire hierarchy
- Approve projects/reports with coordinator-level authority
- Same authorization level as Coordinators

**As Provincial (Direct Team):**
- Manage Executors/Applicants directly under General
- Forward projects/reports from direct team
- Approve/revert projects/reports from direct team (as Provincial)

### 1.4 Key Principle

**COMPLETE Coordinator Access Inheritance:** You have IDENTICAL coordinator access and authorization. The ONLY difference is SCOPE - you see coordinator hierarchy + direct team (broader scope, same authorization level).

---

## 2. Getting Started

### 2.1 Logging In

1. Navigate to the application login page
2. Enter your **email address** and **password**
3. Click the **Login** button
4. You will be redirected to your General dashboard

### 2.2 First Login Steps

1. **Change Your Password** (Recommended)
   - Click on your profile name in the top-right corner
   - Select **Profile** or **Change Password**
   - Enter your current password and new password
   - Click **Update Password**

2. **Familiarize Yourself with the Dashboard**
   - Review dashboard widgets (combined view of coordinator hierarchy + direct team)
   - Understand the dual-role context
   - Explore province management features

---

## 3. Dashboard Overview

### 3.1 Dashboard Sections

Your dashboard provides a unified view of:

1. **Combined Statistics** - From coordinator hierarchy + direct team
2. **Pending Approvals** - Projects/reports from both contexts
3. **Budget Overview** - Combined budget data
4. **Activity Feed** - Activities from both contexts
5. **Performance Metrics** - System-wide metrics

### 3.2 Context Selection

When approving/reverting projects or reports, you may need to select context:
- **As Coordinator:** For coordinator hierarchy (requires commencement date for project approval)
- **As Provincial:** For direct team (forwards to coordinator level)

---

## 4. Coordinator Hierarchy Management

### 4.1 Viewing Coordinators

**Steps:**
1. Click **"Coordinators"** or **"My Team"** → **"Coordinators"** in the sidebar
2. View all Coordinator users under your management
3. View coordinator information:
   - Name, Email, Phone
   - Status (Active/Inactive)
   - Number of Provincials under them
   - Performance metrics
   - Actions (Edit, Activate/Deactivate, Reset Password)

### 4.2 Creating a Coordinator User

**Steps:**

**Step 1:** Navigate to Coordinator Management
- Click **"Coordinators"** → **"Create Coordinator"**

**Step 2:** Fill in User Information
- **Name** (Required)
- **Email** (Required, must be unique)
- **Username** (Optional)
- **Phone** (Optional)
- **Province** (Required)
- **Center** (Optional)
- **Society Name** (Optional)
- **Address** (Optional)
- **Status:** Select **Active** or **Inactive**

**Step 3:** Set Initial Password
- Enter a temporary password
- User should change password on first login

**Step 4:** Save User
- Click **"Create"** or **"Save"**
- Coordinator user account is created

### 4.3 Managing Coordinators

- **Edit Coordinator:** Update coordinator information
- **Activate/Deactivate:** Manage coordinator access
- **Reset Password:** Reset coordinator passwords
- **Monitor Performance:** View coordinator performance metrics

---

## 5. Provincial User Management

### 5.1 Viewing Provincial Users

**Steps:**
1. Click **"My Team"** → **"Provincial Users"** in the sidebar
2. View all Provincial users in the system
3. View provincial user information:
   - Name, Email, Phone
   - Status (Active/Inactive)
   - Assigned Province
   - Number of Executors/Applicants under them
   - Actions (Edit, Activate/Deactivate, Reset Password)

### 5.2 Creating a Provincial User

**Steps:**

**Step 1:** Navigate to Provincial Management
- Click **"Provincial Users"** → **"Add Provincial"**

**Step 2:** Fill in User Information
- **Name** (Required)
- **Email** (Required, must be unique)
- **Username** (Optional)
- **Phone** (Optional)
- **Province** (Required)
- **Center** (Optional)
- **Society Name** (Optional)
- **Address** (Optional)
- **Status:** Select **Active** or **Inactive**

**Step 3:** Set Initial Password
- Enter a temporary password
- User should change password on first login

**Step 4:** Save User
- Click **"Create"** or **"Save"**
- Provincial user account is created

### 5.3 Managing Provincial Users

- **Edit Provincial:** Update provincial user information
- **Activate/Deactivate:** Manage provincial user access
- **Reset Password:** Reset provincial user passwords
- **Monitor Performance:** View provincial user performance metrics

**Note:** Provincial users are managed through coordinator routes, giving you the same access level as coordinators for managing provincials.

---

## 6. Direct Team Management

### 5.1 Viewing Direct Team (Executors/Applicants)

**Steps:**
1. Click **"Executors"** or **"My Team"** → **"Executors"** in the sidebar
2. View all Executors/Applicants directly under you (not under Coordinators)
3. View team member information and manage them

### 5.2 Creating Direct Team Members

**Steps:**

**Step 1:** Navigate to Executor Management
- Click **"Executors"** → **"Create Executor"**

**Step 2:** Fill in User Information
- **Name** (Required)
- **Email** (Required)
- **Role:** Select **Executor** or **Applicant**
- Other fields (Province, Center, etc.)
- **Status:** Select **Active** or **Inactive**

**Step 3:** Set Initial Password and Save
- Enter temporary password
- Save user account

### 5.3 Managing Direct Team

- **Edit Executors/Applicants:** Update user information
- **Activate/Deactivate:** Manage user access
- **Reset Password:** Reset user passwords

---

## 7. Province Management

### 7.1 Viewing Provinces

**Steps:**
1. Click **"Provinces"** in the sidebar
2. View list of all provinces in the system
3. View province information:
   - Province Name
   - Centers/Locations
   - Assigned Provincial Coordinator
   - Actions (Edit, Delete, Assign Coordinator)

### 7.2 Creating a Province

**Steps:**
1. Click **"Provinces"** → **"Create Province"**
2. Enter Province Name
3. Add Centers/Locations (optional)
4. Click **"Create"** to save

### 7.3 Editing a Province

**Steps:**
1. Go to Provinces list
2. Find the province
3. Click **"Edit"** button
4. Update province name or centers
5. Click **"Update"** to save

### 7.4 Assigning Provincial Coordinator

**Steps:**
1. Go to Provinces list
2. Find the province
3. Click **"Assign Coordinator"** button
4. Select a user from the list (can be any user, including yourself)
5. Click **"Assign"** to save
6. The user's province field is updated to match the assigned province

**Note:** You can assign yourself as Provincial Coordinator for provinces.

### 7.5 Updating/Removing Provincial Coordinator

- **Update Coordinator:** Change the assigned coordinator
- **Remove Coordinator:** Remove the assignment

### 7.6 Province Filter

**Steps:**
1. Use the province filter to focus on specific provinces
2. Select a province from the filter dropdown
3. The dashboard and lists will show data filtered by the selected province
4. Clear the filter to view all provinces again

**Note:** The province filter helps you manage multiple provinces more efficiently by focusing on one at a time.

---

## 8. Society Management

### 8.1 Viewing Societies

**Steps:**
1. Click **"Society Management"** in the sidebar
2. View list of all societies in the system
3. View society information:
   - Society Name
   - Associated Province
   - Status (Active/Inactive)
   - Actions (Edit, Delete)

### 8.2 Creating a Society

**Steps:**
1. Click **"Society Management"** → **"Create Society"**
2. Enter Society Name (Required)
3. Select Province (Required)
4. Click **"Create"** to save

**Note:** Society names must be unique within each province.

### 8.3 Editing a Society

**Steps:**
1. Go to Societies list
2. Find the society
3. Click **"Edit"** button
4. Update society name or province assignment
5. Click **"Update"** to save

### 8.4 Deleting a Society

**Steps:**
1. Go to Societies list
2. Find the society
3. Click **"Delete"** button
4. Confirm deletion

**Note:** A society cannot be deleted if it has centers associated with it.

---

## 9. Center Management

### 9.1 Viewing Centers

**Steps:**
1. Click **"Center Management"** in the sidebar
2. View list of all centers in the system
3. View center information:
   - Center Name
   - Associated Province
   - Status (Active/Inactive)
   - Actions (Edit, Delete, Transfer)

### 9.2 Creating a Center

**Steps:**
1. Click **"Center Management"** → **"Create Center"**
2. Enter Center Name (Required)
3. Select Province (Required)
4. Click **"Create"** to save

**Note:** Centers belong to provinces. All centers in a province are available to all societies in that province.

### 9.3 Editing a Center

**Steps:**
1. Go to Centers list
2. Find the center
3. Click **"Edit"** button
4. Update center name or province assignment
5. Click **"Update"** to save

### 9.4 Deleting a Center

**Steps:**
1. Go to Centers list
2. Find the center
3. Click **"Delete"** button
4. Confirm deletion

**Note:** A center cannot be deleted if it has users associated with it.

### 9.5 Transferring Centers Between Provinces

**Steps:**
1. Go to Centers list
2. Find the center you want to transfer
3. Click **"Transfer"** button
4. Select the target province
5. Click **"Transfer"** to confirm

**Note:** Transferring a center moves it from one province to another. This may affect users and projects associated with the center.

### 9.6 Managing User Centers

**Steps:**
1. Click **"Center Management"** → **"Manage User Centers"**
2. View all child users (coordinators, provincials, executors, applicants)
3. Select a user to manage their centers
4. Update center assignments for the selected user
5. Optionally update centers for child users as well
6. Click **"Update Centers"** to save

**Note:** This feature allows you to manage center assignments for users in your hierarchy.

---

## 10. Project Management

### 10.1 Viewing Projects

Your project list shows projects from:
- **Coordinator Hierarchy:** All projects from Coordinators and their entire hierarchy
- **Direct Team:** All projects from Executors/Applicants directly under you

**Steps:**
1. Click **"Projects"** → **"Projects List"** in the sidebar
2. View combined list of projects
3. Filter by context if needed

### 10.2 Approving Projects

**Context Selection Required:**

**As Coordinator (for Coordinator Hierarchy):**
1. Open the project
2. Select **"Approve as Coordinator"** context
3. Set Commencement Date (Required):
   - Select Commencement Month
   - Select Commencement Year
   - Cannot be in the past
4. Review project thoroughly
5. Click **"Approve Project"**
6. Project status: "approved_by_general_as_coordinator"

**As Provincial (for Direct Team):**
1. Open the project
2. Select **"Approve as Provincial"** context
3. Review project
4. Click **"Forward to Coordinator"**
5. Project is forwarded (no commencement date needed at this level)

### 10.3 Reverting Projects

**As Coordinator:**
- Can revert to Provincial or Coordinator level
- Select revert level when reverting
- Add revert comments

**As Provincial:**
- Can revert to Executor/Applicant
- Add revert comments

### 10.4 Adding Comments to Projects

**Steps:**
1. Open a project
2. Scroll to the **"Comments"** section
3. Enter your comment
4. Click **"Add Comment"**
5. Your comment is saved and visible to relevant users

### 10.5 Editing Project Comments

**Steps:**
1. Open a project with comments
2. Find your comment
3. Click **"Edit"** button
4. Update the comment text
5. Click **"Update Comment"** to save

**Note:** You can edit your own comments. Comments help communicate feedback and track project discussions.

---

## 11. Report Management

### 11.1 Viewing Reports

Your report list shows reports from:
- **Coordinator Hierarchy:** All reports from Coordinators and their entire hierarchy
- **Direct Team:** All reports from Executors/Applicants directly under you

### 11.2 Approving Reports

**Context Selection Required:**

**As Coordinator (for Coordinator Hierarchy):**
1. Open the report
2. Select **"Approve as Coordinator"** context
3. Review report thoroughly
4. Click **"Approve Report"**
5. Report status: "approved_by_general_as_coordinator"

**As Provincial (for Direct Team):**
1. Open the report
2. Select **"Forward as Provincial"** context
3. Review report
4. Click **"Forward to Coordinator"**
5. Report is forwarded to coordinator level

### 11.3 Reverting Reports

**As Coordinator:**
- Can revert to Provincial or Coordinator level
- Select revert level

**As Provincial:**
- Can revert to Executor/Applicant
- Add revert comments

### 11.4 Adding Comments to Reports

**Steps:**
1. Open a report
2. Scroll to the **"Comments"** section
3. Enter your comment
4. Click **"Add Comment"**
5. Your comment is saved and visible to relevant users

### 11.5 Editing Report Comments

**Steps:**
1. Open a report with comments
2. Find your comment
3. Click **"Edit"** button
4. Update the comment text
5. Click **"Update Comment"** to save

### 11.6 Bulk Actions on Reports

**Steps:**
1. Go to Reports list
2. Select multiple reports using checkboxes
3. Choose an action from the bulk actions dropdown:
   - **Bulk Approve:** Approve all selected reports
   - **Bulk Revert:** Revert all selected reports
4. Select the context (Coordinator or Provincial) if required
5. Add comments if needed
6. Click **"Apply Action"** to process all selected reports

**Note:** Bulk actions allow you to process multiple reports at once, saving time when handling many reports.

### 11.7 Aggregated Reports

#### 11.7.1 Quarterly Reports

**Steps:**
1. Click **"Reports"** → **"Quarterly Reports"** → **"View Quarterly Reports"**
2. View aggregated quarterly reports from coordinator hierarchy and direct team
3. Filter by date range, province, or other criteria
4. Download or export reports as needed

#### 11.7.2 Biannual (Half-Yearly) Reports

**Steps:**
1. Click **"Reports"** → **"Biannual Reports"** → **"View Half-Yearly Reports"**
2. View aggregated half-yearly reports
3. Filter and analyze data as needed

#### 11.7.3 Annual Reports

**Steps:**
1. Click **"Reports"** → **"Annual Reports"** → **"View Annual Reports"**
2. View aggregated annual reports
3. Filter and analyze data as needed

**Note:** Aggregated reports provide consolidated views of reports across different time periods, helping with analysis and decision-making.

---

## 12. Budget Management

### 12.1 Viewing Budget Overview

Your budget overview combines:
- Budgets from Coordinator hierarchy
- Budgets from Direct team
- System-wide budget statistics

**Steps:**
1. Click **"Budgets"** or **"Budget Overview"** in the sidebar
2. View combined budget data
3. Filter by context if needed

### 12.2 Viewing Project Budgets

**Steps:**
1. Click **"Budget & Finance"** → **"Project Budgets"** in the sidebar
2. View all project budgets from:
   - Coordinator hierarchy
   - Direct team
3. Filter by project, province, date range, etc.
4. View budget details, allocations, and expenditures

### 12.3 Budget Reports

**Steps:**
1. Click **"Budget & Finance"** → **"Budget Reports"** in the sidebar
2. View comprehensive budget reports
3. Analyze budget utilization and trends
4. Export reports for further analysis

### 12.4 Budget Monitoring

- Monitor budget utilization across both contexts
- Track expenses vs. budgets
- Identify budget overruns or underutilization
- Generate budget reports for analysis

---

## 13. Activity History

### 13.1 Viewing All Activities

**Steps:**
1. Click **"All Activities"** in the sidebar
2. View comprehensive activity history from:
   - Coordinator hierarchy
   - Direct team
3. See activities including:
   - Project creation and updates
   - Report submissions
   - Status changes (approvals, rejections, reversions)
   - Comments added
   - User actions

### 13.2 Filtering Activities

**Steps:**
1. Use filters to narrow down activities:
   - **Context:** Filter by coordinator hierarchy or direct team
   - **User:** Filter by specific user
   - **Date Range:** Filter by time period
   - **Activity Type:** Filter by type of activity
   - **Project/Report:** Filter by specific project or report
2. Apply filters to see relevant activities

### 13.3 Activity Details

Each activity shows:
- **Who:** User who performed the action
- **What:** Action performed
- **When:** Date and time
- **Context:** Whether from coordinator hierarchy or direct team
- **Related Item:** Project or report involved
- **Comments:** Any comments or notes associated

### 13.4 Activity History on Projects and Reports

**Steps:**
1. Open any project or report
2. Scroll to the **"Activity History"** section
3. View complete timeline of all activities related to that item
4. Track the item's progress through the approval workflow

**Note:** Activity history provides complete transparency and accountability for all system activities.

---

## 14. Profile Management

### 14.1 Viewing Your Profile

**Steps:**
1. Click on your name/profile picture
2. Select **"Profile"**
3. View your profile information

### 14.2 Updating Profile

**Steps:**
1. Go to Profile page
2. Click **"Edit"** button
3. Update the fields you want to change:
   - Name
   - Phone
   - Address
   - (Note: Email and Role cannot be changed by you)
4. Click **"Update Profile"** to save changes

### 14.3 Changing Password

**Steps:**
1. Click **"Settings"** → **"Change Password"** in the sidebar
2. Enter:
   - Current Password
   - New Password
   - Confirm New Password
3. Click **"Update Password"**
4. You will be logged out and need to login with new password

---

## 15. Notifications

### 15.1 Viewing Notifications

**Steps:**
1. Click the **notification bell icon** in the top navigation
2. View list of notifications:
   - Project approvals/rejections from coordinator hierarchy
   - Project approvals/rejections from direct team
   - Report approvals/rejections
   - Comments on projects/reports
   - Reminders and alerts
   - System-wide updates

### 15.2 Notification Types

- **Project Status Changes:** When projects are approved, rejected, or reverted
- **Report Status Changes:** When reports are approved, rejected, or reverted
- **Comments:** When someone adds a comment to a project or report
- **Reminders:** Deadlines and important dates
- **System Alerts:** Important system-wide notifications

### 15.3 Managing Notifications

**Steps:**
1. Click on a notification to view details
2. Click **"Mark as Read"** to mark individual notifications as read
3. Click **"Mark All as Read"** to mark all notifications as read
4. Delete notifications you no longer need

### 15.4 Notification Center

**Steps:**
1. Click **"Notifications"** in the sidebar
2. View all notifications in a dedicated page
3. Filter notifications by type, date, or status
4. Manage notification preferences if available

**Note:** Notifications help you stay informed about important updates without having to constantly check the system.

---

## 16. Common Workflows

### 16.1 Approving Projects from Coordinator Hierarchy

1. Receive notification (project forwarded by Coordinator)
2. Open the project
3. Select "Approve as Coordinator" context
4. Set commencement date (required)
5. Review project
6. Approve project
7. Project becomes active

### 16.2 Approving Projects from Direct Team

1. Receive notification (project submitted by direct Executor/Applicant)
2. Open the project
3. Select "Forward as Provincial" context
4. Review project
5. Forward to Coordinator
6. Project moves to coordinator level

### 16.3 Managing Coordinators

1. Create Coordinator user
2. Provide access information
3. Monitor Coordinator performance
4. Support Coordinators as needed

### 16.4 Managing Provinces

1. Create new province
2. Assign Provincial Coordinator (can assign yourself)
3. Update province information as needed
4. Manage province assignments

### 16.5 Managing Societies and Centers

1. Create new society and assign to province
2. Create centers within provinces
3. Transfer centers between provinces if needed
4. Manage user center assignments

### 16.6 Processing Multiple Reports

1. Go to Reports list
2. Select multiple reports using checkboxes
3. Use bulk actions to approve or revert multiple reports at once
4. Add comments if needed
5. Process all selected reports efficiently

---

## 17. Troubleshooting

### 17.1 Common Issues

**Issue: Cannot approve project - missing commencement date**
- **Solution:** When approving as Coordinator, commencement date is required. Select month and year.

**Issue: Confused about which context to use**
- **Solution:** 
  - Use "Coordinator" context for items from Coordinator hierarchy
  - Use "Provincial" context for items from direct team

**Issue: Cannot assign myself as Provincial Coordinator**
- **Solution:** You can assign yourself. Select your user from the coordinator assignment list.

**Issue: Cannot see projects/reports from both contexts**
- **Solution:** Your lists show both contexts combined. Use filters if needed to separate them.

**Issue: Cannot delete a society**
- **Solution:** A society cannot be deleted if it has centers. Remove or reassign centers first.

**Issue: Cannot delete a center**
- **Solution:** A center cannot be deleted if it has users. Reassign users to other centers first.

**Issue: Cannot delete a province**
- **Solution:** A province cannot be deleted if it has users. Reassign users to other provinces first.

### 17.2 Best Practices

1. **Context Awareness:** Always understand which context you're working in
2. **Clear Communication:** Provide clear feedback when approving/reverting
3. **Thorough Review:** Review items thoroughly before approval
4. **Timely Processing:** Process approvals in a timely manner
5. **System Monitoring:** Monitor both coordinator hierarchy and direct team performance

---

## Appendix A: Key Differences from Coordinator Role

### Similarities

- **Complete Coordinator Access:** You have ALL coordinator routes, permissions, and functionality
- **Same Authorization Level:** Your approval authority is identical to Coordinators
- **Same Business Logic:** Your actions work exactly the same as Coordinator actions

### Differences

- **Broader Scope:** You see Coordinator hierarchy + Direct team (more data)
- **Dual-Role Context:** You also act as Provincial for direct team
- **Province Management:** You can create/manage provinces (Coordinators cannot)
- **Coordinator Management:** You manage Coordinators (they manage Provincials)

---

## Appendix B: Status Reference

### Project Statuses

**From Coordinator Hierarchy:**
- **forwarded_to_coordinator:** Forwarded by Provincial, awaiting your approval
- **approved_by_general_as_coordinator:** Approved by you (as Coordinator)
- **reverted_by_general_as_coordinator:** Reverted by you (as Coordinator)

**From Direct Team:**
- **submitted_to_provincial:** Submitted by direct Executor/Applicant
- **forwarded_to_coordinator:** Forwarded by you (as Provincial)

### Report Statuses

Similar patterns apply to reports with General-specific statuses when approving as Coordinator.

---

**End of General User Manual**
