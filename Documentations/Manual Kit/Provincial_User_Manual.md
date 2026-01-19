# Provincial User Manual

**SalProjects Application - Operational Manual for Provincial Role**

**Version:** 1.0  
**Last Updated:** January 2025  
**Target Audience:** Provincial Users

---

## Table of Contents

1. [Role Overview](#1-role-overview)
2. [Getting Started](#2-getting-started)
3. [Dashboard Overview](#3-dashboard-overview)
4. [Team Management (Executor/Applicant Users)](#4-team-management-executorapplicant-users)
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

As a **Provincial**, you are responsible for:

- **Team Management:** Managing Executor and Applicant users under your supervision
- **Project Review:** Reviewing and forwarding projects from your team members to Coordinator
- **Report Review:** Reviewing, approving, and forwarding monthly reports from your team to Coordinator
- **Team Oversight:** Monitoring team performance, budget utilization, and project progress
- **Approval Authority:** Approving/reverting projects and reports from your team members

### 1.2 Access Level

- You can view and manage all projects and reports from Executors/Applicants under your supervision
- You can create, edit, activate/deactivate Executor and Applicant users
- You can forward projects and reports to Coordinator after review
- You can revert projects and reports back to team members for corrections
- You can add comments to projects and reports
- You can download project and report documents in PDF/DOC formats
- You have access to team activity history

### 1.3 Team Structure

- **Your Team:** All Executor and Applicant users where you are their parent (supervisor)
- **Team Members:** Can belong to different centers/locations
- **Your Supervisor:** Coordinator (who reviews your forwarded projects/reports)

---

## 2. Getting Started

### 2.1 Logging In

1. Navigate to the application login page
2. Enter your **email address** and **password**
3. Click the **Login** button
4. You will be redirected to your Provincial dashboard

### 2.2 First Login Steps

1. **Change Your Password** (Recommended)
   - Click on your profile name in the top-right corner
   - Select **Profile** or **Change Password**
   - Enter your current password and new password
   - Click **Update Password**

2. **Review Your Profile Information**
   - Ensure your name, email, phone number, center, and province information are correct
   - Update any incorrect information if needed

3. **Familiarize Yourself with the Dashboard**
   - Review dashboard widgets showing team overview
   - Check pending approvals (projects and reports)
   - Review team statistics

---

## 3. Dashboard Overview

### 3.1 Dashboard Sections

Your dashboard provides a comprehensive overview of your team:

1. **Budget Overview** - Total budget, expenses, and remaining amounts across all team projects
2. **Pending Approvals Widget** - Projects and reports awaiting your review
3. **Team Overview** - Statistics about your team members
4. **Budget Summary by Project Type** - Breakdown of budgets by project type
5. **Budget Summary by Center** - Breakdown of budgets by center/location
6. **Approval Queue** - Detailed list of pending items
7. **Team Performance Metrics** - Various performance indicators

### 3.2 Dashboard Filters

You can filter dashboard data by:
- **Center:** Filter by team member's center/location
- **Role:** Filter by Executor or Applicant
- **Project Type:** Filter by specific project types

### 3.3 Quick Actions from Dashboard

- View pending projects requiring review
- View pending reports requiring review
- Access team management
- View team activities
- Access notifications

---

## 4. Team Management (Executor/Applicant Users)

### 4.1 Viewing Your Team

**Steps:**
1. Click **"My Team"** in the sidebar menu
2. You will see a list of all Executors and Applicants under your supervision
3. View information for each team member:
   - Name
   - Email
   - Phone
   - Center/Location
   - Role (Executor/Applicant)
   - Status (Active/Inactive)
   - Number of projects
   - Actions (Edit, Activate/Deactivate, Reset Password)

### 4.2 Creating a New Team Member (Executor/Applicant)

**Steps:**

**Step 1:** Navigate to Team Management
- Click **"My Team"** in the sidebar
- Click **"Create Executor"** or **"Add Member"** button

**Step 2:** Fill in User Information
- **Name** (Required)
- **Email** (Required, must be unique)
- **Username** (Optional)
- **Phone** (Optional)
- **Center** (Optional)
- **Province** (Required - select from dropdown)
- **Society Name** (Optional)
- **Address** (Optional)
- **Role:** Select **Executor** or **Applicant**
- **Status:** Select **Active** or **Inactive**

**Step 3:** Set Initial Password
- Enter a temporary password for the user
- User should change password on first login

**Step 4:** Save User
- Click **"Create"** or **"Save"** button
- User account is created
- User will be under your supervision (parent_id set to your user ID)

### 4.3 Editing a Team Member

**Steps:**
1. Go to **"My Team"** list
2. Find the team member you want to edit
3. Click **"Edit"** button
4. Update the information you want to change:
   - Name, Phone, Center, Address, etc.
   - (Note: Email and Role typically cannot be changed)
5. Click **"Update"** to save changes

### 4.4 Activating/Deactivating Team Members

**To Activate a User:**
1. Go to **"My Team"** list
2. Find the inactive user
3. Click **"Activate"** button
4. User status changes to Active
5. User can now log in and access the system

**To Deactivate a User:**
1. Go to **"My Team"** list
2. Find the active user
3. Click **"Deactivate"** button
4. User status changes to Inactive
5. User cannot log in (but data is preserved)

### 4.5 Resetting Team Member Password

**Steps:**
1. Go to **"My Team"** list
2. Find the team member
3. Click **"Reset Password"** button
4. Enter new temporary password
5. Click **"Reset Password"**
6. Notify the user of the new password (they should change it on first login)

---

## 5. Project Management

### 5.1 Viewing Team Projects

**Steps:**
1. Click **"Projects"** → **"Projects List"** in the sidebar
2. You will see all projects from your team members
3. View project information:
   - Project ID
   - Project Title
   - Project Type
   - Executor/Applicant Name
   - Center
   - Status
   - Date Submitted
   - Actions (View, Forward, Revert, Comment)

**Filter Options:**
- Filter by Status (All, Pending, Approved, Reverted)
- Filter by Center
- Filter by Executor/Applicant
- Filter by Project Type
- Search by project title or ID

### 5.2 Viewing Project Details

**Steps:**
1. Navigate to Projects list
2. Click on **Project Title** or **"View"** button
3. View complete project information:
   - All project details
   - Budget information
   - Attachments
   - Comments
   - Activity history
   - Status timeline

### 5.3 Forwarding a Project to Coordinator

**Prerequisites:**
- Project must be in "submitted_to_provincial" status
- You should review the project for completeness and accuracy

**Steps:**
1. Open the project you want to forward
2. Review all project information:
   - Check completeness of information
   - Verify budget details
   - Review attachments
   - Check for any issues
3. Add comments if needed (optional)
4. Click **"Forward to Coordinator"** button
5. Confirm the action
6. Project status changes to "approved_by_provincial"
7. Coordinator receives notification

**Note:** Forwarding to Coordinator indicates your approval. Only forward projects that are complete and accurate.

### 5.4 Reverting a Project to Executor/Applicant

**When to revert:**
- Project has incomplete information
- Budget details are missing or incorrect
- Attachments are missing
- Project needs corrections

**Steps:**
1. Open the project you want to revert
2. Review the project to identify issues
3. Click **"Revert to Executor"** button
4. **Add Revert Comment (Required):**
   - Explain what needs to be corrected
   - Be specific about issues
   - Provide guidance on what to fix
5. Click **"Revert"** to confirm
6. Project status changes to "reverted"
7. Executor/Applicant receives notification with your comments

### 5.5 Adding Comments to Projects

**Steps:**
1. Open the project
2. Scroll to **"Comments"** section
3. Enter your comment in the text box
4. Click **"Add Comment"**
5. Comment is added and visible to:
   - The project owner (Executor/Applicant)
   - Other reviewers (Coordinator)

**Use comments for:**
- Asking questions
- Providing feedback
- Requesting clarifications
- Sharing notes with team

### 5.6 Viewing Approved Projects

**Steps:**
1. Click **"Projects"** → **"Approved Projects"** in the sidebar
2. View all projects that have been approved by Coordinator
3. These projects can be used by team members to create monthly reports

### 5.7 Downloading Project Documents

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

### 6.1 Viewing Team Reports

**Steps:**
1. Click **"Reports"** → **"Report List"** in the sidebar
2. You will see all monthly reports from your team members
3. View report information:
   - Report ID
   - Project Title
   - Executor/Applicant Name
   - Reporting Period (Month/Year)
   - Status
   - Date Submitted
   - Actions (View, Forward, Revert, Comment)

**Filter Options:**
- Filter by Status (All, Pending, Approved, Reverted)
- Filter by Project
- Filter by Executor/Applicant
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

### 6.3 Forwarding Reports to Coordinator

**Prerequisites:**
- Report must be in "submitted_to_provincial" status
- You should review the report for completeness and accuracy

**Steps:**
1. Open the report you want to forward
2. Review the report:
   - Check reporting period
   - Verify expenses/account details
   - Review activities and objectives
   - Check photos and attachments
   - Verify accuracy
3. Add comments if needed (optional)
4. Click **"Forward to Coordinator"** button
5. Confirm the action
6. Report status changes to "forwarded_to_coordinator"
7. Coordinator receives notification

**Note:** Forwarding indicates your approval. Only forward reports that are complete and accurate.

### 6.4 Bulk Forwarding Reports

**When to use:**
- Multiple reports are ready to forward
- Batch processing for efficiency

**Steps:**
1. Navigate to Reports list
2. Select reports you want to forward (checkboxes)
3. Click **"Bulk Forward"** or **"Bulk Actions"** button
4. Confirm the action
5. All selected reports (if in correct status) will be forwarded
6. You'll see a summary of successful and failed forwards

**Note:** Only reports in "submitted_to_provincial" status can be bulk forwarded.

### 6.5 Reverting Reports to Executor/Applicant

**When to revert:**
- Report has incomplete information
- Expenses don't match budget
- Activities are not properly documented
- Photos or attachments are missing
- Report needs corrections

**Steps:**
1. Open the report you want to revert
2. Review the report to identify issues
3. Click **"Revert Report"** button
4. **Add Revert Comment (Required):**
   - Explain what needs to be corrected
   - Be specific about issues
   - Provide guidance on corrections
5. Click **"Revert"** to confirm
6. Report status changes to "reverted"
7. Executor/Applicant receives notification with your comments

### 6.6 Adding Comments to Reports

**Steps:**
1. Open the report
2. Scroll to **"Comments"** section
3. Enter your comment in the text box
4. Click **"Add Comment"**
5. Comment is added and visible to:
   - The report creator (Executor/Applicant)
   - Other reviewers (Coordinator)

**Use comments for:**
- Asking questions
- Providing feedback
- Requesting clarifications
- Sharing notes

### 6.7 Viewing Pending Reports

**Steps:**
1. Click **"Reports"** → **"Pending Reports"** in the sidebar
2. View all reports awaiting your review
3. These reports are in "submitted_to_provincial" status
4. Review and forward or revert as needed

### 6.8 Viewing Approved Reports

**Steps:**
1. Click **"Reports"** → **"Approved Reports"** in the sidebar
2. View all reports that have been approved by Coordinator
3. These are finalized reports

### 6.9 Downloading Report Documents

**To Download as PDF:**
1. Open the report
2. Click **"Download PDF"** button
3. PDF will be generated and downloaded

**To Download as DOC:**
1. Open the report
2. Click **"Download DOC"** button
3. Word document will be generated and downloaded

### 6.10 Viewing Aggregated Reports

You can view Quarterly, Half-Yearly, and Annual reports created by your team members:

**Steps:**
1. Click **"Reports"** → **"Quarterly"**, **"Half-Yearly"**, or **"Annual"** in the sidebar
2. View aggregated reports created by team members
3. Download reports as PDF or Word documents

---

## 7. Budget Management

### 7.1 Viewing Budget Overview

**From Dashboard:**
- View "Budget Overview" widget showing:
  - Total Budget (across all team projects)
  - Total Expenses (from approved reports)
  - Total Remaining
  - Budget Summary by Project Type
  - Budget Summary by Center

**From Budget Section:**
1. Click **"Budgets"** in the sidebar (if available)
2. View comprehensive budget overview
3. Filter by center, project type, etc.

### 7.2 Viewing Project Budgets

**Steps:**
1. Open a project
2. Navigate to **"Budget"** section or tab
3. View budget details:
   - Budget items by phase
   - Rates and quantities
   - Total amounts
   - Expenses vs. Budget
   - Remaining budget

### 7.3 Budget Monitoring

**Best Practices:**
- Regularly review budget utilization across team projects
- Monitor expenses vs. approved budgets
- Identify projects with budget overruns
- Track budget by center and project type
- Ensure team members are staying within budget

---

## 8. Activity History

### 8.1 Viewing Team Activities

**Steps:**
1. Click **"Team Activities"** in the sidebar
2. View timeline of activities from all team members:
   - Project creation and updates
   - Report submissions
   - Status changes
   - Comments and interactions
   - Date and time stamps

**Filter Options:**
- Filter by team member
- Filter by activity type
- Filter by date range

### 8.2 Activity History on Projects

**Steps:**
1. Open any project
2. Scroll to **"Activity History"** section
3. View all activities related to that project:
   - Who performed the action (team member or reviewer)
   - What action was taken
   - When it occurred
   - Comments or notes

### 8.3 Activity History on Reports

**Steps:**
1. Open any report
2. Scroll to **"Activity History"** section
3. View all activities related to that report
4. Track the report's progress through the workflow

---

## 9. Profile Management

### 9.1 Viewing Your Profile

**Steps:**
1. Click on your name/profile picture in the top-right corner
2. Select **"Profile"**
3. View your profile information:
   - Name
   - Email
   - Phone
   - Center
   - Province
   - Society Name
   - Address
   - Role (Provincial)

### 9.2 Updating Profile Information

**Steps:**
1. Go to Profile page
2. Click **"Edit"** button
3. Update the fields you want to change:
   - Name
   - Phone
   - Address
   - (Note: Email and Role typically cannot be changed by you)
4. Click **"Update Profile"** to save changes

### 9.3 Changing Password

**Steps:**
1. Go to Profile page
2. Click **"Change Password"** or navigate to password change section
3. Enter:
   - Current Password
   - New Password
   - Confirm New Password
4. Click **"Update Password"**
5. You will be logged out and need to login with new password

---

## 10. Notifications

### 10.1 Viewing Notifications

**Steps:**
1. Click the **notification bell icon** in the top navigation
2. View list of notifications:
   - New projects submitted by team members
   - New reports submitted by team members
   - Project/report approvals from Coordinator
   - Project/report reverts from Coordinator
   - Comments on projects/reports

### 10.2 Notification Types

- **New Submissions:** When team members submit projects or reports
- **Status Changes:** When Coordinator approves or reverts items
- **Comments:** When comments are added to projects or reports
- **Team Member Activity:** Important team member actions

### 10.3 Managing Notifications

- Click on a notification to view details
- Click **"Mark as Read"** to mark individual notifications
- Click **"Mark All as Read"** to mark all as read
- Delete notifications you no longer need

---

## 11. Common Workflows

### 11.1 Project Review and Forwarding Workflow

1. **Receive Notification**
   - Team member submits a project
   - You receive notification

2. **Review Project**
   - Open the project from Projects list or notification
   - Review all information:
     - Completeness of details
     - Budget accuracy
     - Attachments
     - Overall quality

3. **Make Decision**

   **If Project is Complete:**
   - Add comments if needed (optional)
   - Click "Forward to Coordinator"
   - Project is forwarded for final approval

   **If Project Needs Corrections:**
   - Click "Revert to Executor"
   - Add detailed revert comments
   - Explain what needs to be fixed
   - Team member receives notification and can make corrections

4. **Monitor Progress**
   - Track project status
   - Check if reverted projects are resubmitted
   - Monitor Coordinator's decision

### 11.2 Report Review and Forwarding Workflow

1. **Receive Notification**
   - Team member submits a monthly report
   - You receive notification

2. **Review Report**
   - Open the report from Reports list or notification
   - Review:
     - Reporting period accuracy
     - Expenses and account details
     - Activities and objectives
     - Photos and attachments
     - Budget alignment

3. **Make Decision**

   **If Report is Complete and Accurate:**
   - Add comments if needed (optional)
   - Click "Forward to Coordinator"
   - Report is forwarded for final approval
   - OR use "Bulk Forward" for multiple reports

   **If Report Needs Corrections:**
   - Click "Revert Report"
   - Add detailed revert comments
   - Explain what needs to be corrected
   - Team member receives notification and can make corrections

4. **Monitor Progress**
   - Track report status
   - Check if reverted reports are resubmitted
   - Monitor Coordinator's decision

### 11.3 Team Member Management Workflow

1. **Create New Team Member**
   - Click "Create Executor" or "Add Member"
   - Fill in all required information
   - Set initial password
   - Set status to Active
   - Save user

2. **Provide Access Information**
   - Notify new team member of:
     - Email (username)
     - Initial password
     - Login URL
     - Instructions to change password on first login

3. **Ongoing Management**
   - Monitor team member activity
   - Activate/deactivate as needed
   - Reset passwords when requested
   - Update information when needed

### 11.4 Handling Reverted Items from Coordinator

1. **Receive Notification**
   - Coordinator reverts a project or report you forwarded
   - You receive notification with Coordinator's comments

2. **Review Coordinator's Feedback**
   - Read comments carefully
   - Understand what needs to be fixed

3. **Communicate with Team Member**
   - Review the item with your team member
   - Explain what needs to be corrected
   - Provide guidance if needed

4. **Monitor Resubmission**
   - Wait for team member to make corrections
   - Review resubmitted item
   - Forward again if satisfactory

---

## 12. Troubleshooting

### 12.1 Common Issues and Solutions

**Issue: Cannot see team member's projects/reports**
- **Solution:** Ensure the team member has you set as their parent (supervisor). Check user management to verify relationship.

**Issue: Cannot forward project/report**
- **Solution:** Check the status - items can only be forwarded when in "submitted_to_provincial" status. Ensure you've reviewed the item first.

**Issue: Team member cannot log in**
- **Solution:** Check if user status is Active. Reset password if needed. Verify email is correct.

**Issue: Budget calculations seem incorrect**
- **Solution:** Review project budgets and expenses from reports. Check if expenses are properly linked to budget items.

**Issue: Cannot bulk forward reports**
- **Solution:** Ensure all selected reports are in "submitted_to_provincial" status. Check each report individually if bulk forward fails.

**Issue: Team member not receiving notifications**
- **Solution:** Verify team member's email is correct. Check notification settings. Ensure user is active.

### 12.2 Getting Help

If you encounter issues not covered here:

1. **Contact Your Coordinator**
   - They can help with approval-related questions
   - They can address system-wide issues
   - They can clarify policies and procedures

2. **Check System Notifications**
   - Important messages and updates are shown in notifications

3. **Review Activity History**
   - Check activity history to understand what happened with projects/reports

### 12.3 Best Practices

1. **Regular Review**
   - Review pending items regularly
   - Don't let items pile up
   - Process items in a timely manner

2. **Clear Communication**
   - Provide clear, specific feedback when reverting items
   - Use comments to communicate with team members
   - Be constructive in your feedback

3. **Quality Control**
   - Thoroughly review items before forwarding
   - Check for completeness and accuracy
   - Verify budget alignment
   - Ensure attachments are present

4. **Team Support**
   - Guide team members when they need help
   - Provide training on system usage
   - Address questions promptly

5. **Monitor Team Performance**
   - Regularly review team activity
   - Monitor budget utilization
   - Identify training needs
   - Track project/report quality

6. **Documentation**
   - Keep records of important decisions
   - Document reasons for reversions
   - Maintain communication logs if needed

---

## Appendix A: Status Reference

### Project Statuses (from Team Members)

- **draft:** Project is being prepared by team member
- **submitted_to_provincial:** Submitted to you, awaiting your review
- **approved_by_provincial:** You've forwarded to Coordinator
- **approved_by_coordinator:** Fully approved by Coordinator
- **reverted:** Sent back to team member for corrections

### Report Statuses (from Team Members)

- **draft:** Report is being prepared
- **underwriting:** Ready for submission
- **submitted_to_provincial:** Submitted to you, awaiting your review
- **forwarded_to_coordinator:** You've forwarded to Coordinator
- **approved_by_coordinator:** Fully approved by Coordinator
- **reverted:** Sent back to team member for corrections

---

**End of Provincial User Manual**
