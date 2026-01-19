# Coordinator User Manual

**SalProjects Application - Operational Manual for Coordinator Role**

**Version:** 1.0  
**Last Updated:** January 2025  
**Target Audience:** Coordinator Users

---

## Table of Contents

1. [Role Overview](#1-role-overview)
2. [Getting Started](#2-getting-started)
3. [Dashboard Overview](#3-dashboard-overview)
4. [Provincial Management](#4-provincial-management)
5. [Project Management](#5-project-management)
6. [Report Management](#6-report-management)
7. [Budget Management](#7-budget-management)
8. [Activity History](#8-activity-history)
9. [Profile Management](#9-profile-management)
10. [Notifications](#10-notifications)
11. [Common Workflows](#11-common-workflows)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. Role Overview

### 1.1 Your Role

As a **Coordinator**, you are the highest-level administrator in the system. You are responsible for:

- **Provincial Management:** Managing all Provincial users across the system
- **Final Approval Authority:** Approving/rejecting projects and reports from all Provincials
- **System-Wide Oversight:** Monitoring all projects, reports, and activities across the entire system
- **Strategic Decision-Making:** Making strategic decisions based on system-wide data
- **Performance Monitoring:** Tracking performance across all provinces and centers

### 1.2 Access Level

- **System-Wide Access:** Access to ALL data across all provinces, centers, and users
- **Provincial Management:** Create, edit, activate/deactivate Provincial users
- **Final Approval Authority:** Approve/reject projects and reports from Provincials
- **Budget Oversight:** View and analyze budgets across the entire system
- **Activity Monitoring:** View all activities across the system
- **Reports and Analytics:** Access comprehensive system-wide reports and analytics

### 1.3 System Hierarchy

- **Your Level:** Coordinator (Top-Level Administrator)
- **Under You:** Provincial users (who manage Executors/Applicants)
- **Full System Access:** All provinces, all centers, all users, all projects, all reports

---

## 2. Getting Started

### 2.1 Logging In

1. Navigate to the application login page
2. Enter your **email address** and **password**
3. Click the **Login** button
4. You will be redirected to your Coordinator dashboard

### 2.2 First Login Steps

1. **Change Your Password** (Recommended)
   - Click on your profile name in the top-right corner
   - Select **Profile** or **Change Password**
   - Enter your current password and new password
   - Click **Update Password**

2. **Familiarize Yourself with the Dashboard**
   - Review all dashboard widgets
   - Check pending approvals
   - Review system statistics
   - Explore provincial overview

---

## 3. Dashboard Overview

### 3.1 Dashboard Widgets

Your comprehensive dashboard includes:

1. **Pending Approvals Widget** - Projects and reports awaiting your approval
2. **Provincial Overview Widget** - Overview of all Provincials with statistics
3. **System Performance Summary Widget** - System-wide performance metrics
4. **Approval Queue Widget** - Dedicated approval queue management
5. **System Analytics Charts** - 7 interactive charts with time range selector
6. **System Activity Feed** - Timeline of recent system activities
7. **System Budget Overview** - Enhanced budget breakdowns with charts
8. **Province Performance Comparison** - Province rankings and comparisons
9. **Provincial Management Widget** - Detailed provincial management with scores
10. **System Health Indicators** - System health score, alerts, and trends

### 3.2 Dashboard Filters

You can filter dashboard data by:
- **Province:** Filter by specific provinces
- **Provincial:** Filter by specific Provincial users
- **Center:** Filter by centers/locations
- **Project Type:** Filter by project types
- **Time Range:** Select time periods for analytics

### 3.3 Quick Actions from Dashboard

- Review pending approvals
- Access provincial management
- View system analytics
- Access approval queue
- View system activity feed

---

## 4. Provincial Management

### 4.1 Viewing Provincials

**Steps:**
1. Click **"My Team"** or **"Provincials"** in the sidebar
2. View list of all Provincial users with:
   - Name, Email, Phone
   - Province, Center
   - Status (Active/Inactive)
   - Number of team members (Executors/Applicants)
   - Number of projects/reports
   - Performance metrics
   - Actions (Edit, Activate/Deactivate, Reset Password)

### 4.2 Creating a New Provincial User

**Steps:**

**Step 1:** Navigate to Provincial Management
- Click **"My Team"** → **"Create Provincial"** or **"Add Provincial"**

**Step 2:** Fill in User Information
- **Name** (Required)
- **Email** (Required, must be unique)
- **Username** (Optional)
- **Phone** (Optional)
- **Province** (Required - select from dropdown)
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
- User is now under your supervision

### 4.3 Editing a Provincial User

**Steps:**
1. Go to Provincials list
2. Find the Provincial user
3. Click **"Edit"** button
4. Update information (Name, Phone, Address, etc.)
5. Click **"Update"** to save

### 4.4 Activating/Deactivating Provincials

**To Activate:**
1. Find the inactive Provincial user
2. Click **"Activate"** button
3. User status changes to Active

**To Deactivate:**
1. Find the active Provincial user
2. Click **"Deactivate"** button
3. User status changes to Inactive

### 4.5 Resetting Provincial Password

**Steps:**
1. Go to Provincials list
2. Find the Provincial user
3. Click **"Reset Password"** button
4. Enter new temporary password
5. Click **"Reset Password"**
6. Notify the user of the new password

---

## 5. Project Management

### 5.1 Viewing All Projects

**Steps:**
1. Click **"Projects"** → **"Projects List"** in the sidebar
2. View ALL projects from ALL Provincials and their teams
3. View project information:
   - Project ID, Title, Type
   - Province, Provincial, Executor/Applicant, Center
   - Status
   - Date Submitted/Approved
   - Actions (View, Approve, Reject, Revert, Comment)

**Filter Options:**
- Filter by Status
- Filter by Province
- Filter by Provincial
- Filter by Center
- Filter by Project Type
- Search by project title or ID

### 5.2 Viewing Project Details

**Steps:**
1. Navigate to Projects list
2. Click on **Project ID** or **"View"** button
3. View complete project information:
   - All project details
   - Budget information
   - Attachments
   - Comments
   - Activity history
   - Status timeline

### 5.3 Approving a Project

**Prerequisites:**
- Project must be in "forwarded_to_coordinator" status
- Project must have been reviewed by Provincial
- You should review the project thoroughly

**Steps:**
1. Open the project you want to approve
2. **Review the project thoroughly:**
   - Check completeness of information
   - Verify budget details and calculations
   - Review attachments
   - Verify alignment with policies
3. **Set Commencement Date (Required):**
   - Select Commencement Month (1-12)
   - Select Commencement Year
   - **Note:** Commencement date cannot be in the past
4. Add comments if needed (optional)
5. Click **"Approve Project"** button
6. Confirm the action
7. Project status changes to "approved_by_coordinator"
8. Project becomes active and can receive monthly reports
9. Executor/Applicant and Provincial receive notifications

**Important:** Approval is final. Only approve projects that meet all requirements and policies.

### 5.4 Rejecting a Project

**When to reject:**
- Project does not meet requirements
- Budget is unrealistic or incorrect
- Project violates policies
- Major issues that cannot be corrected through revision

**Steps:**
1. Open the project you want to reject
2. Review the project to identify rejection reasons
3. Click **"Reject Project"** button
4. **Add Rejection Reason (Required):**
   - Explain why the project is rejected
   - Be specific and clear
   - Provide guidance if applicable
5. Click **"Reject"** to confirm
6. Project status changes to "rejected_by_coordinator"
7. Executor/Applicant and Provincial receive notifications

**Note:** Rejection is permanent. Consider reverting instead if corrections are possible.

### 5.5 Reverting a Project to Provincial

**When to revert:**
- Project needs corrections
- Additional information required
- Budget adjustments needed
- Issues that can be corrected

**Steps:**
1. Open the project you want to revert
2. Review the project to identify issues
3. Click **"Revert to Provincial"** button
4. **Add Revert Comment (Required):**
   - Explain what needs to be corrected
   - Be specific about issues
   - Provide clear guidance
5. Click **"Revert"** to confirm
6. Project status changes to "reverted_by_coordinator"
7. Provincial receives notification and can work with Executor/Applicant to make corrections

### 5.6 Adding Comments to Projects

**Steps:**
1. Open the project
2. Scroll to **"Comments"** section
3. Enter your comment
4. Click **"Add Comment"**
5. Comment is visible to Provincial and Executor/Applicant

### 5.7 Viewing Approved Projects

**Steps:**
1. Click **"Projects"** → **"Approved Projects"** in the sidebar
2. View all projects you have approved
3. These projects are active and can receive monthly reports

### 5.8 Downloading Project Documents

**To Download as PDF:**
1. Open the project
2. Click **"Download PDF"** button
3. PDF will be generated and downloaded

**To Download as DOC:**
1. Open the project
2. Click **"Download DOC"** button
3. Word document will be generated and downloaded

---

## 6. Report Management

### 6.1 Viewing All Reports

**Steps:**
1. Click **"Reports"** → **"Report List"** in the sidebar
2. View ALL monthly reports from ALL Provincials and their teams
3. View report information:
   - Report ID, Project Title
   - Province, Provincial, Executor/Applicant, Center
   - Reporting Period (Month/Year)
   - Status
   - Days Pending
   - Actions (View, Approve, Revert, Comment)

**Filter Options:**
- Filter by Status
- Filter by Province
- Filter by Provincial
- Filter by Urgency (Days Pending)
- Filter by Reporting Period
- Search by report ID or project title

### 6.2 Viewing Report Details

**Steps:**
1. Navigate to Reports list
2. Click on **Report ID** or **"View"** button
3. View complete report information:
   - Basic information
   - Objectives and activities
   - Account details (expenses)
   - Photos
   - Outlooks
   - Attachments
   - Comments
   - Activity history

### 6.3 Approving Reports

**Prerequisites:**
- Report must be in "forwarded_to_coordinator" status
- Report must have been reviewed by Provincial
- You should review the report thoroughly

**Steps:**
1. Open the report you want to approve
2. **Review the report thoroughly:**
   - Verify reporting period
   - Check expenses against budget
   - Review activities and objectives
   - Verify photos and attachments
   - Check accuracy and completeness
3. Add comments if needed (optional)
4. Click **"Approve Report"** button
5. Confirm the action
6. Report status changes to "approved_by_coordinator"
7. Report is finalized
8. Executor/Applicant and Provincial receive notifications

### 6.4 Bulk Approving Reports

**When to use:**
- Multiple reports are ready for approval
- Batch processing for efficiency

**Steps:**
1. Navigate to Reports list
2. Select reports you want to approve (checkboxes)
3. Click **"Bulk Approve"** or **"Bulk Actions"** → **"Approve"**
4. Confirm the action
5. All selected reports (if in correct status) will be approved
6. You'll see a summary of successful and failed approvals

### 6.5 Reverting Reports to Provincial

**When to revert:**
- Report needs corrections
- Expenses don't match budget
- Activities not properly documented
- Additional information required

**Steps:**
1. Open the report you want to revert
2. Review the report to identify issues
3. Click **"Revert Report"** button
4. **Add Revert Comment (Required):**
   - Explain what needs to be corrected
   - Be specific about issues
   - Provide clear guidance
5. Click **"Revert"** to confirm
6. Report status changes to "reverted_by_coordinator"
7. Provincial receives notification and can work with Executor/Applicant

### 6.6 Bulk Reverting Reports

**Steps:**
1. Navigate to Reports list
2. Select reports you want to revert (checkboxes)
3. Click **"Bulk Revert"** or **"Bulk Actions"** → **"Revert"**
4. Add revert comment (applied to all selected reports)
5. Confirm the action
6. Selected reports will be reverted

### 6.7 Adding Comments to Reports

**Steps:**
1. Open the report
2. Scroll to **"Comments"** section
3. Enter your comment
4. Click **"Add Comment"**
5. Comment is visible to Provincial and Executor/Applicant

### 6.8 Viewing Pending Reports

**Steps:**
1. Click **"Reports"** → **"Pending Reports"** in the sidebar
2. View all reports awaiting your approval
3. Reports are in "forwarded_to_coordinator" status
4. Review and approve or revert as needed

### 6.9 Viewing Approved Reports

**Steps:**
1. Click **"Reports"** → **"Approved Reports"** in the sidebar
2. View all reports you have approved
3. These are finalized reports

### 6.10 Downloading Report Documents

**To Download as PDF:**
1. Open the report
2. Click **"Download PDF"** button
3. PDF will be generated and downloaded

**To Download as DOC:**
1. Open the report
2. Click **"Download DOC"** button
3. Word document will be generated and downloaded

### 6.11 Viewing Aggregated Reports

You can view Quarterly, Half-Yearly, and Annual reports:

**Steps:**
1. Click **"Reports"** → **"Quarterly"**, **"Half-Yearly"**, or **"Annual"** in the sidebar
2. View aggregated reports created by Executors/Applicants
3. Download reports as PDF or Word documents

---

## 7. Budget Management

### 7.1 Viewing Budget Overview

**From Dashboard:**
- View "System Budget Overview" widget showing:
  - Total Budget (system-wide)
  - Total Expenses
  - Total Remaining
  - Breakdowns by Province, Project Type, Center

**From Budget Section:**
1. Click **"Budgets"** or **"Budget Overview"** in the sidebar
2. View comprehensive system-wide budget overview
3. Filter by province, project type, center, etc.

### 7.2 Viewing Project Budgets

**Steps:**
1. Open any project
2. Navigate to **"Budget"** section
3. View budget details and expenses

### 7.3 Budget Monitoring and Analysis

**Best Practices:**
- Regularly review budget utilization across the system
- Monitor expenses vs. approved budgets
- Identify projects/provinces with budget overruns
- Track budget trends over time
- Generate budget reports for analysis

---

## 8. Activity History

### 8.1 Viewing All Activities

**Steps:**
1. Click **"All Activities"** in the sidebar
2. View timeline of ALL activities across the entire system:
   - Project creation and updates
   - Report submissions
   - Status changes
   - Comments and interactions
   - User actions
   - Date and time stamps

**Filter Options:**
- Filter by Province
- Filter by Provincial
- Filter by Activity Type
- Filter by Date Range
- Search by user, project, or report

### 8.2 Activity History on Projects

**Steps:**
1. Open any project
2. Scroll to **"Activity History"** section
3. View all activities related to that project

### 8.3 Activity History on Reports

**Steps:**
1. Open any report
2. Scroll to **"Activity History"** section
3. View all activities related to that report

---

## 9. Profile Management

### 9.1 Viewing Your Profile

**Steps:**
1. Click on your name/profile picture in the top-right corner
2. Select **"Profile"**
3. View your profile information

### 9.2 Updating Profile Information

**Steps:**
1. Go to Profile page
2. Click **"Edit"** button
3. Update information
4. Click **"Update Profile"** to save

### 9.3 Changing Password

**Steps:**
1. Go to Profile page
2. Click **"Change Password"**
3. Enter current password and new password
4. Click **"Update Password"**

---

## 10. Notifications

### 10.1 Viewing Notifications

**Steps:**
1. Click the **notification bell icon** in the top navigation
2. View list of notifications:
   - New projects forwarded by Provincials
   - New reports forwarded by Provincials
   - System-wide alerts
   - Important updates

### 10.2 Managing Notifications

- Click on a notification to view details
- Mark as read
- Mark all as read
- Delete notifications

---

## 11. Common Workflows

### 11.1 Project Approval Workflow

1. **Receive Notification**
   - Provincial forwards a project
   - You receive notification

2. **Review Project**
   - Open the project
   - Review thoroughly:
     - Completeness
     - Budget accuracy
     - Policy compliance
     - Attachments

3. **Make Decision**

   **If Approving:**
   - Set commencement date (required)
   - Add comments if needed
   - Click "Approve Project"
   - Project becomes active

   **If Reverting:**
   - Click "Revert to Provincial"
   - Add detailed revert comments
   - Provincial receives notification

   **If Rejecting:**
   - Click "Reject Project"
   - Add rejection reason
   - Project is permanently rejected

### 11.2 Report Approval Workflow

1. **Receive Notification**
   - Provincial forwards reports
   - You receive notification

2. **Review Reports**
   - Open reports
   - Review:
     - Expenses vs. budget
     - Activities and objectives
     - Photos and attachments
     - Accuracy

3. **Make Decision**

   **If Approving:**
   - Click "Approve Report"
   - Report is finalized
   - OR use "Bulk Approve" for multiple reports

   **If Reverting:**
   - Click "Revert Report"
   - Add revert comments
   - Provincial receives notification

### 11.3 Provincial Management Workflow

1. **Create Provincial User**
   - Fill in information
   - Set initial password
   - Activate user

2. **Monitor Provincial Performance**
   - Review provincial statistics
   - Monitor approval rates
   - Track team performance

3. **Support Provincials**
   - Provide guidance when needed
   - Address questions
   - Manage access as needed

---

## 12. Troubleshooting

### 12.1 Common Issues and Solutions

**Issue: Cannot approve project - missing commencement date**
- **Solution:** Commencement date is required for approval. Select month and year (cannot be in the past).

**Issue: Cannot see all projects/reports**
- **Solution:** You have access to all projects/reports. Check filters if items are not visible.

**Issue: Bulk actions not working**
- **Solution:** Ensure all selected items are in the correct status for the action. Check each item individually if needed.

**Issue: Budget calculations seem incorrect**
- **Solution:** Review project budgets and expenses. Check if expenses are properly linked to budget items.

### 12.2 Getting Help

- Review system documentation
- Check activity history for details
- Contact system administrator if technical issues

### 12.3 Best Practices

1. **Thorough Review:** Always review items thoroughly before approving
2. **Clear Communication:** Provide clear, specific feedback when reverting
3. **Timely Processing:** Process approvals in a timely manner
4. **Policy Compliance:** Ensure all approvals align with policies
5. **System Monitoring:** Regularly monitor system-wide performance and trends
6. **Documentation:** Document important decisions and reasons

---

## Appendix A: Status Reference

### Project Statuses

- **draft:** Being prepared by Executor/Applicant
- **submitted_to_provincial:** Submitted to Provincial
- **approved_by_provincial/forwarded_to_coordinator:** Forwarded to you
- **approved_by_coordinator:** Approved by you (final approval)
- **reverted_by_coordinator:** Reverted by you
- **rejected_by_coordinator:** Rejected by you (permanent)

### Report Statuses

- **draft:** Being prepared
- **submitted_to_provincial:** Submitted to Provincial
- **forwarded_to_coordinator:** Forwarded to you
- **approved_by_coordinator:** Approved by you (final approval)
- **reverted_by_coordinator:** Reverted by you

---

**End of Coordinator User Manual**
